<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\NeuronChatMessage;
use App\Neuron\PdfChatAgent;
use App\Services\NeuronChatHistorySeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use NeuronAI\Chat\Messages\UserMessage;
use Smalot\PdfParser\Parser;

class ChatController extends Controller
{
    /**
     * Mostra la schermata principale con lo storico delle chat
     */
    public function index()
    {
        $chats = Chat::with('messages')->latest()->get();
        return view('chat', compact('chats'));
    }

    /**
     * Mostra una specifica sessione di chat attiva
     */
    public function show($id)
    {
        $chats = Chat::latest()->get();
        $currentChat = Chat::with('messages')->findOrFail($id);
        return view('chat', compact('chats', 'currentChat'));
    }

    /**
     * Salva le configurazioni della modale (Provider, Modello, API Key) in Sessione
     */
    public function saveConfig(Request $request)
    {
        $request->validate([
            'ai_provider' => 'required|string',
            'ai_model'    => 'required|string',
            'ai_key'      => 'nullable|string',
            'tavily_key'  => 'nullable|string',
        ]);

        session([
            'ai_provider'    => $request->input('ai_provider'),
            'ai_model'       => $request->input('ai_model'),
            'ai_key'         => $request->input('ai_key'),
            'tavily_key'     => $request->input('tavily_key'),
            'disable_search' => $request->has('disable_search'),
        ]);

        return redirect()->back();
    }

    /**
     * Gestisce l'invio dei messaggi (Testo semplice o file PDF)
     */
    public function send(Request $request, $id = null)
    {
        $prompt = $request->input('message') ? trim($request->input('message')) : '';
        $contextText = "";
        $fileName = null;

        // Recupera o crea la sessione di chat
        if ($id) {
            $chat = Chat::findOrFail($id);
        } else {
            $chat = Chat::create([
                'title' => $request->hasFile('file') ? 'Analisi: ' . substr($request->file('file')->getClientOriginalName(), 0, 20) : (substr($prompt, 0, 30) ?: 'Nuova Chat')
            ]);
        }

        // Recuperiamo i dati correnti per l'eventuale stringa di errore del banner
        $chosenProvider = session('ai_provider', env('AI_PROVIDER', 'google'));
        $chosenModel = session('ai_model', env('GEMINI_MODEL', 'gemini-3.1-flash-lite'));

        // SCENARIO 1: Elaborazione file PDF
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            
            try {
                $pdfParser = new Parser();
                $pdfDocument = $pdfParser->parseFile($file->getPathname());
                $contextText = mb_convert_encoding($pdfDocument->getText(), 'UTF-8', 'UTF-8');
                $contextText = preg_replace('/\s+/', ' ', str_replace(["\r", "\n", "\t"], " ", $contextText));
            } catch (\Exception $e) {
                Log::error('Errore PDF: ' . $e->getMessage());
                $contextText = "[Errore lettura file]";
            }

            NeuronChatHistorySeeder::seedIfNeeded($chat);

            $chat->messages()->create(['role' => 'user', 'content' => !empty($prompt) ? $prompt : "Analizza file", 'file_name' => $fileName]);
            $fullAgentPrompt = "Nome: $fileName\nContesto: $contextText\n\nRichiesta: " . (!empty($prompt) ? $prompt : "Riassumi");

            $agent = PdfChatAgent::forChat($chat->id);
            $hasFailedover = false;

            try {
                $aiResponse = $agent->chat(new UserMessage($fullAgentPrompt))->getMessage()->getContent();
                
                // Verifica reale del failover tramite l'istanza del Router nativo di Neuron
                if ($agent->routerInstance && method_exists($agent->routerInstance, 'getActiveProviderId')) {
                    if ($agent->routerInstance->getActiveProviderId() === 'backup') {
                        $hasFailedover = true;
                    }
                }
            } catch (\Exception $e) {
                $aiResponse = "❌ **Errore critico:** Tutti i modelli configurati nel Router hanno fallito.\nDettaglio: " . $e->getMessage();
            }

            // Aggiungi il banner solo se il failover è avvenuto realmente
            if ($hasFailedover) {
                $aiResponse = "⚠️ **Sistema di Failover Attivo (Neuron AI):** Il modello principale configurato (*" . ucfirst($chosenProvider) . " - " . $chosenModel . "*) ha risposto con un errore. La richiesta è stata completata usando il modello di riserva configurato nel Router.\n\n---\n\n" . $aiResponse;
            }

            $chat->messages()->create(['role' => 'assistant', 'content' => $aiResponse]);
            return redirect()->route('chat.show', $chat->id);
        }

        // SCENARIO 2: Elaborazione messaggio di testo ordinario
        if (!empty($prompt)) {
            NeuronChatHistorySeeder::seedIfNeeded($chat);

            $chat->messages()->create(['role' => 'user', 'content' => $prompt]);

            $agent = PdfChatAgent::forChat($chat->id);
            $hasFailedover = false;

            try {
                $aiResponse = $agent->chat(new UserMessage($prompt))->getMessage()->getContent();
                
                // Verifica reale del failover tramite l'istanza del Router nativo di Neuron
                if ($agent->routerInstance && method_exists($agent->routerInstance, 'getActiveProviderId')) {
                    if ($agent->routerInstance->getActiveProviderId() === 'backup') {
                        $hasFailedover = true;
                    }
                }
            } catch (\Exception $e) {
                $aiResponse = "❌ **Servizio non disponibile:** Il Router di Neuron non è riuscito a ricevere risposta da nessun LLM.\nDettaglio: `" . $e->getMessage() . "`";
            }

            // Aggiungi il banner solo se il failover è avvenuto realmente
            if ($hasFailedover) {
                $aiResponse = "⚠️ **Sistema di Failover Attivo (Neuron AI):** Il modello principale configurato (*" . ucfirst($chosenProvider) . " - " . $chosenModel . "*) ha risposto con un errore. La richiesta è stata completata usando il modello di riserva configurato nel Router.\n\n---\n\n" . $aiResponse;
            }
            
            $chat->messages()->create(['role' => 'assistant', 'content' => $aiResponse]);
        }

        return redirect()->to('/chat/' . $chat->id);
    }

    /**
     * Rinomina il titolo di una chat esistente
     */
    public function update(Request $request, $id)
    {
        $chat = Chat::findOrFail($id);
        $request->validate(['title' => 'required|string|max:255']);
        $chat->update(['title' => $request->input('title')]);
        return redirect()->back();
    }

    /**
     * Elimina una chat e tutti i suoi messaggi collegati
     */
    public function destroy($id)
    {
        $chat = Chat::findOrFail($id);
        NeuronChatMessage::where('thread_id', (string) $chat->id)->delete();
        $chat->messages()->delete();
        $chat->delete();
        return redirect()->route('chat.index');
    }
}
