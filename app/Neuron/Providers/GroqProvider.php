<?php

namespace App\Neuron\Providers;

use NeuronAI\Providers\Gemini\Gemini;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Enums\MessageRole;
use Illuminate\Support\Facades\Http;
use Exception;

class GroqProvider extends Gemini
{
    protected string $key;
    protected string $model;

    public function __construct(string $key, string $model)
    {
        parent::__construct(key: $key, model: $model);
        
        $this->key = $key;
        $this->model = $model;
    }

    /**
     * Sovrascriviamo il metodo chat per indirizzarlo sui server di Groq
     */
    public function chat(Message ...$messages): Message
    {
        // Formattiamo i messaggi nel formato standard richiesto dalle API di Groq
        $formattedMessages = array_map(function ($msg) {
            return [
                'role'    => $msg->getRole() instanceof MessageRole ? $msg->getRole()->value : (string) $msg->getRole(),
                'content' => $msg->getContent(),
            ];
        }, $messages);

        // Eseguiamo la richiesta HTTP isolata direttamente verso Groq
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'    => $this->model,
            'messages' => $formattedMessages,
        ]);

        // Se Groq fallisce, lanciamo l'eccezione per far scattare il Router di Neuron
        if ($response->failed()) {
            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? 'Errore sconosciuto nelle API di Groq';
            throw new Exception("Groq Error: " . $errorMessage);
        }

        $responseData = $response->json();
        $textResult = $responseData['choices'][0]['message']['content'] ?? '';

        // Restituiamo l'oggetto messaggio allineando getRole() al tipo string richiesto
        return new class($textResult) extends Message {
            protected MessageRole $role;
            protected string $content;

            public function __construct(string $content) {
                $this->content = $content;
                $this->role = MessageRole::ASSISTANT;
            }
            
            // 🎯 FIX: Restituiamo una stringa (il valore dell'Enum) come richiesto dal framework
            public function getRole(): string 
            { 
                return $this->role->value; 
            }
            
            public function getContent(): string 
            { 
                return $this->content; 
            }
        };
    }
}
