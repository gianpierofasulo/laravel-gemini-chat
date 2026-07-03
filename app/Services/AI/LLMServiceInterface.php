<?php

namespace App\Services\AI;

interface LLMServiceInterface
{
    /**
     * Invia un messaggio all'LLM insieme al contesto dei documenti.
     *
     * @param string $prompt Il messaggio dell'utente
     * @param string|null $context Il testo estratto dai file allegati
     * @param array $history La cronologia dei messaggi passati
     * @return string La risposta dell'LLM
     */
    public function chat(string $prompt, ?string $context = null, array $history = []): string;
}
