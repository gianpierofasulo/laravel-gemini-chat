<?php

namespace App\Neuron;

use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Gemini\Gemini;

// Importa il tuo nuovo Tool
use App\Neuron\Tools\WebSearchTool;

class PdfChatAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new Gemini(
            key: env('GEMINI_API_KEY'),
            model: env('GEMINI_MODEL', 'gemini-2.0-flash')
        );
    }

    /**
     * 🌐 REGISTRAZIONE DEI TOOL 🌐
     * Fornisce all'agente l'accesso a strumenti esterni
     */
    protected function tools(): array
    {
        return [
            new WebSearchTool()
        ];
    }

    protected function instructions(): string
    {
        return "Sei un assistente AI avanzato integrato nell'ecosistema Neuron AI Stack. " .
               "Hai la capacità di combinare i documenti PDF forniti dall'utente con ricerche in tempo reale su Internet. " .
               "Se l'utente ti fa una domanda su eventi recenti, codici, leggi aggiornate o dati mancanti, utilizza immediatamente lo strumento 'web_search' per verificare le informazioni prima di rispondere.";
    }
}
