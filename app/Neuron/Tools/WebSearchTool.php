<?php

namespace App\Neuron\Tools;

use NeuronAI\Tools\Tool;
use App\Services\SearchService;
use Illuminate\Support\Facades\Log;

class WebSearchTool extends Tool
{
    /**
     * Inizializzazione dei metadati base del Tool
     */
    public function __construct()
    {
        parent::__construct(
            name: 'web_search',
            description: 'Usa questo strumento per cercare informazioni in tempo reale su internet, notizie recenti o dati meteo.'
        );
    }

    /**
     * Struttura dello schema dei parametri richiesti da Gemini
     */
    protected function tools(): array
    {
        return [
            'queries' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'description' => 'Elenco di stringhe di ricerca per i motori di ricerca',
                'required' => true
            ]
        ];
    }

    /**
     * METODO DI ESECUZIONE NATIVO DI NEURON AI
     */
    public function execute(): void
    {
        Log::info("=== WEB_SEARCH TOOL - ESECUZIONE DEFINITIVA ===");
        
        $inputs = $this->inputs ?? [];
        $searchQuery = '';

        if (!empty($inputs['queries']) && is_array($inputs['queries'])) {
            $searchQuery = $inputs['queries'][0]; 
        }

        if (empty($searchQuery)) {
            $searchQuery = $inputs['query'] ?? 'Meteo attuale Potenza';
        }

        Log::info("Query estratta con successo: " . $searchQuery);

        // Chiamata reale a Internet tramite il servizio Tavily
        $webResult = SearchService::searchWeb($searchQuery);

        // Assegnazione alla proprietà nativa
        $this->result = $webResult;
        
        Log::info("Risultato salvato nella proprietà \$result di Neuron.");
    }
}
