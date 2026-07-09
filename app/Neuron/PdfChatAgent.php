<?php

namespace App\Neuron;

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Providers\AIProviderInterface;
use App\Neuron\Tools\WebSearchTool;

use NeuronAI\Router\RouterProvider;
use NeuronAI\Providers\Gemini\Gemini; 
use App\Neuron\Providers\GroqProvider;

class PdfChatAgent extends Agent
{
    public ?RouterProvider $routerInstance = null;

    protected function provider(): AIProviderInterface
    {
        $primaryProvider = session('ai_provider', env('AI_PROVIDER', 'google'));
        $primaryModel = session('ai_model', env('GEMINI_MODEL', 'gemini-3.1-flash-lite'));
        $inputKey = session('ai_key');

        $backupGeminiModel = env('GEMINI_MODEL', 'gemini-3.1-flash-lite');
        $backupGroqModel = env('GROQ_MODEL', 'groq/compound');

        if ($primaryProvider === 'groq') {
            $groqKey   = $inputKey ?: env('GROQ_API_KEY') ?: '';
            $geminiKey = env('GEMINI_API_KEY') ?: '';
        } else {
            $geminiKey = $inputKey ?: env('GEMINI_API_KEY') ?: '';
            $groqKey   = env('GROQ_API_KEY') ?: '';
        }

        $this->routerInstance = RouterProvider::make();

        if ($primaryProvider === 'groq') {
            $this->routerInstance->addProvider('primary', new GroqProvider($groqKey, $primaryModel));
            $this->routerInstance->addProvider('backup', new Gemini($geminiKey, $backupGeminiModel));
        } else {
            $this->routerInstance->addProvider('primary', new Gemini($geminiKey, $primaryModel));
            $this->routerInstance->addProvider('backup', new GroqProvider($groqKey, $backupGroqModel));
        }

        return $this->routerInstance->setFallbackOrder('primary', 'backup');
    }

    public function instructions(): string
    {
        return (string) new SystemPrompt(
            background: ["Sei un assistente AI avanzato integrato con il framework Neuron AI."],
            steps: ["Rispondi sempre in modo chiaro, preciso e cordiale in lingua italiana."]
        );
    }

    public function tools(): array
    {
        return session('disable_search', false) ? [] : [new WebSearchTool()];
    }
}
