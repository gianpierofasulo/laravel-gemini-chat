<?php

namespace App\Neuron;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use App\Neuron\Tools\WebSearchTool;

// Namespace ufficiali di Neuron AI
use NeuronAI\Providers\Gemini\Gemini; 
use NeuronAI\Providers\OpenAI\OpenAI;

class PdfChatAgent extends Agent
{
    /**
     * Definizione dinamica del Provider AI conforme a AIProviderInterface
     */
    protected function provider(): AIProviderInterface
    {
        $providerName = session('ai_provider', env('AI_PROVIDER', 'google'));
        $model = session('ai_model', env('GEMINI_MODEL', 'gemini-1.5-flash'));
        
        // 🎯 RECUPERO CHIAVE CON FALLBACK MULTIPLI PER EVITARE IL VALORE NULL
        $apiKey = session('ai_key') 
            ?: env('GEMINI_API_KEY') 
            ?: env('GEMINI_KEY') 
            ?: env('GOOGLE_API_KEY') 
            ?: ''; // Se non trova nulla, passa una stringa vuota anziché null per evitare il crash di tipo

        if ($providerName === 'openai') {
            $openAiKey = session('ai_key') ?: env('OPENAI_API_KEY') ?: '';
            return new OpenAI(key: $openAiKey, model: $model);
        }

        // Default: Google Gemini - Riceve sempre una stringa (anche se vuota) evitando l'errore di tipo
        return new Gemini(key: $apiKey, model: $model);
    }

    /**
     * Istruzioni di sistema dell'agente (System Prompt)
     */
    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ["Sei un assistente AI avanzato integrato con il framework Neuron AI."],
            steps: ["Rispondi sempre in modo chiaro, preciso e cordiale in lingua italiana."]
        );
    }

    /**
     * Registrazione condizionale dei Tool (Ricerca Internet)
     */
    public function tools(): array
    {
        if (session('disable_search', false) === true) {
            return [];
        }

        return [
            new WebSearchTool()
        ];
    }
}
