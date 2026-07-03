<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchService
{
    /**
     * Esegue una ricerca web e restituisce i risultati per l'agente
     */
    public static function searchWeb(string $query): string
    {
        // Usiamo il config() o l'env() pulito da spazi
        $apiKey = trim(env('TAVILY_API_KEY', '')); 
        
        if (empty($apiKey)) {
            Log::error("TAVILY_API_KEY non trovata o vuota nel file .env.");
            return "Errore: Servizio di ricerca non configurato. Manca la chiave API nel server.";
        }

        try {
            Log::info("Esecuzione ricerca web su Tavily per la query: " . $query);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post('https://api.tavily.com/search', [
                'api_key' => $apiKey,
                'query' => $query,
                'search_depth' => 'basic',
                'include_answer' => true,
                'max_results' => 3
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Se Tavily restituisce un campo 'answer' già pronto, usiamo quello
                if (!empty($data['answer'])) {
                    return $data['answer'];
                }

                // Fallback: se manca 'answer' ma ci sono i risultati testuali, li uniamo
                if (!empty($data['results']) && is_array($data['results'])) {
                    $snippets = [];
                    foreach ($data['results'] as $result) {
                        $snippets[] = ($result['title'] ?? 'Risultato') . ": " . ($result['content'] ?? '');
                    }
                    return implode(" | ", $snippets);
                }

                return "La ricerca non ha prodotto risultati testuali analizzabili.";
            }

            // Se l'API restituisce un errore (es. 401 Unauthorized o 403) lo tracciamo nei log
            Log::error("Tavily API ha risposto con stato " . $response->status() . " - Errore: " . $response->body());
            return "Errore tecnico del provider di ricerca (Codice " . $response->status() . ").";

        } catch (\Exception $e) {
            Log::error("Eccezione durante la chiamata di ricerca web: " . $e->getMessage());
        }

        return "Errore imprevisto durante il collegamento con i servizi di ricerca online.";
    }
}
