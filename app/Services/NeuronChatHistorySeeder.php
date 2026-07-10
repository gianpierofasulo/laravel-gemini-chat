<?php

namespace App\Services;

use App\Models\Chat;
use App\Models\NeuronChatMessage;

class NeuronChatHistorySeeder
{
    /**
     * Importa i messaggi UI esistenti nella chat history di Neuron,
     * solo se la conversazione non ha ancora una cronologia Neuron.
     */
    public static function seedIfNeeded(Chat $chat): void
    {
        $threadId = (string) $chat->id;

        if (NeuronChatMessage::where('thread_id', $threadId)->exists()) {
            return;
        }

        foreach ($chat->messages()->orderBy('id')->get() as $message) {
            NeuronChatMessage::create([
                'thread_id' => $threadId,
                'role' => $message->role,
                'content' => $message->content,
                'meta' => $message->file_name ? ['file_name' => $message->file_name] : null,
            ]);
        }
    }
}
