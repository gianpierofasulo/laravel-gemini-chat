<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiNeuronService implements LLMServiceInterface
{
    protected string $model;
    protected string $apiKey;

    public function __construct()
    {
        $this->model = env('GEMINI_MODEL', 'gemini-1.5-flash');
        $this->apiKey = env('GEMINI_API_KEY', '');
    }

    /**
     * Metodo sincrono con gestione degli errori dettagliata per la UI
     */
    public function chat(string $prompt, ?string $context = null, array $history = []): string
    {
        $fullPrompt = "";
        if ($context) {
            $fullPrompt .= "Usa queste informazioni:\n" . $context . "\n\n";
        }
        $fullPrompt .= "Domanda: " . $prompt;

        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->withoutVerifying()
            ->timeout(60)
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $fullPrompt]
                        ]
                    ]
                ]
            ]);

            // --- GESTIONE TRASPARENTE DEGLI ERRORI GOOGLE ---
            if ($response->failed()) {
                Log::error('Errore API Gemini: ' . $response->body());
                
                $errorData = $response->json('error');
                if (isset($errorData['message'])) {
                    // Restituiamo l'errore reale di Google formattato in modo pulito per l'utente
                    return "⚠️ Errore API Google Gemini ({$errorData['code']}): " . $errorData['message'];
                }
                
                return "⚠️ Si è verificato un errore sconosciuto nella comunicazione con Google AI (Status: " . $response->status() . ").";
            }

            return $response->json('candidates.0.content.parts.0.text') ?? 'Nessuna risposta generata dal modello.';

        } catch (\Exception $e) {
            Log::error('Eccezione Gemini: ' . $e->getMessage());
            return '❌ Impossibile connettersi ai server di Google. Verifica la connessione o il timeout del container Docker.';
        }
    }

    public function chatStream(string $prompt, ?string $context = null, callable $onChunk)
    {
        // Metodo stream non utilizzato nel flusso sincrono originale
    }
}
