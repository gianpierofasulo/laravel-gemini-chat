<?php

namespace App\Neuron;

use App\Models\NeuronChatMessage;
use App\Neuron\Tools\WebSearchTool;
use App\Neuron\Providers\GroqProvider;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\History\EloquentChatHistory;
use NeuronAI\Chat\History\InMemoryChatHistory;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Gemini\Gemini;
use NeuronAI\Router\RouterProvider;

class PdfChatAgent extends Agent
{
    public ?RouterProvider $routerInstance = null;

    protected ?string $threadId = null;

    public static function forChat(int|string $chatId): static
    {
        $agent = static::make();
        $agent->threadId = (string) $chatId;

        return $agent;
    }

    protected function chatHistory(): ChatHistoryInterface
    {
        if ($this->threadId === null) {
            return new InMemoryChatHistory(
                contextWindow: (int) env('NEURON_CONTEXT_WINDOW', 150000)
            );
        }

        return new EloquentChatHistory(
            threadId: $this->threadId,
            modelClass: NeuronChatMessage::class,
            contextWindow: (int) env('NEURON_CONTEXT_WINDOW', 150000),
        );
    }

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
