<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

// Classi native del framework Neuron AI
use App\Neuron\PdfChatAgent;
use NeuronAI\Chat\Messages\UserMessage;

class ChatController extends Controller
{
    public function index()
    {
        $chats = Chat::with('messages')->latest()->get();
        return view('chat', compact('chats'));
    }

    public function show($id)
    {
        $chats = Chat::latest()->get();
        $currentChat = Chat::with('messages')->findOrFail($id);
        
        return view('chat', compact('chats', 'currentChat'));
    }

    /**
     * Salva i parametri di configurazione dell'utente nella Sessione di Laravel
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
     * Gestione dell'invio messaggi tramite la sintassi ufficiale di Neuron AI Agent
     */
    public function send(Request $request, $id = null)
    {
        $prompt = $request->input('message');
        $prompt = $prompt ? trim($prompt) : '';

        $contextText = "";
        $fileName = null;

        if ($id) {
            $chat = Chat::findOrFail($id);
        } else {
            $chat = Chat::create([
                'title' => $request->hasFile('file') ? 'Analisi: ' . substr($request->file('file')->getClientOriginalName(), 0, 20) : (substr($prompt, 0, 30) ?: 'Nuova Chat')
            ]);
        }

        // 1. Caso: Caricamento ed Estrazione testo dal file PDF
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            
            try {
                $pdfParser = new Parser();
                $pdfDocument = $pdfParser->parseFile($file->getPathname());
                $rawText = $pdfDocument->getText();

                $contextText = mb_convert_encoding($rawText, 'UTF-8', 'UTF-8');
                $contextText = str_replace(["\r", "\n", "\t"], " ", $contextText);
                $contextText = preg_replace('/\s+/', ' ', $contextText);
                $contextText = trim($contextText);

                if (empty($contextText)) {
                    $contextText = "[Il file PDF '$fileName' non contiene testo selezionabile]";
                }

            } catch (\Exception $e) {
                Log::error('Errore estrazione PDF: ' . $e->getMessage());
                $contextText = "[Errore di lettura del file '$fileName']";
            }

            $chat->messages()->create([
                'role' => 'user',
                'content' => !empty($prompt) ? $prompt : "Analizza il file allegato ed esponi il contenuto.",
                'file_name' => $fileName
            ]);

            $finalQuestion = !empty($prompt) ? $prompt : "Fai un riassunto di questo documento";
            $fullAgentPrompt = "Nome Documento: $fileName\nContesto Estratto: $contextText\n\nRichiesta Utente: $finalQuestion";

            try {
                $agentInstance = PdfChatAgent::make()
                    ->chat(new UserMessage($fullAgentPrompt));
                    
                $messageInstance = $agentInstance->getMessage();
                $aiResponse = $messageInstance->getContent();
            } catch (\Exception $e) {
                Log::error("Errore agente su PDF: " . $e->getMessage());
                
                // 🎯 GESTIONE ERRORE NOT FOUND (404) SUL MODELLO
                if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'not found')) {
                    $aiResponse = "❌ **Errore di Configurazione Modello (HTTP 404)**\n\nIl modello selezionato (*" . session('ai_model') . "*) non è stato trovato o non è supportato dal provider AI.\n\nPer favore, clicca sull'icona a forma di ingranaggio ⚙️ in alto a destra per cambiare il modello di IA (es. inserisci `gemini-1.5-flash` o `gemini-1.5-pro`) e riprova.";
                } else {
                    $aiResponse = "Si è verificato un errore nell'elaborazione del documento: " . $e->getMessage();
                }
            }

            $chat->messages()->create([
                'role' => 'assistant',
                'content' => $aiResponse
            ]);

            return redirect()->route('chat.show', $chat->id);
        }

        // 2. Caso standard: solo messaggio testuale senza file
        if (!empty($prompt)) {
            $chat->messages()->create([
                'role' => 'user',
                'content' => $prompt
            ]);

            try {
                Log::info("Inizio esecuzione Agente Neuron per prompt testuale.");
                
                $agentInstance = PdfChatAgent::make()
                    ->chat(new UserMessage($prompt));

                $messageInstance = $agentInstance->getMessage();
                $aiResponse = $messageInstance->getContent();
                
                Log::info("Risposta ricevuta dall'Agente Neuron: " . ($aiResponse ?? 'VUOTA'));

                if (empty($aiResponse)) {
                    $aiResponse = "L'agente ha elaborato la richiesta, ma la risposta è rimasta vuota.";
                }

            } catch (\Exception $e) {
                Log::error("CRASH NEURON FRAMEWORK NELLA RISPOSTA: " . $e->getMessage());
                
                // 🎯 GESTIONE ERRORE NOT FOUND (404) SUL MODELLO PER MESSAGGI TESTUALI
                if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), 'not found')) {
                    $aiResponse = "❌ **Errore di Configurazione Modello (HTTP 404)**\n\nIl modello selezionato (*" . session('ai_model') . "*) non è stato trovato o non è più supportato per questa chiamata.\n\nPer favore, clicca sull'icona a forma di ingranaggio ⚙️ in alto a destra, sostituisci il testo del modello attuale con un modello valido (come `gemini-1.5-flash`) e salva la configurazione.";
                } else {
                    $aiResponse = "Errore di comunicazione interna durante l'esecuzione dell'agente: " . $e->getMessage();
                }
            }
            
            $chat->messages()->create([
                'role' => 'assistant',
                'content' => $aiResponse
            ]);
        }

        return redirect()->to('/chat/' . $chat->id);
    }

    public function update(Request $request, $id)
    {
        $chat = Chat::findOrFail($id);
        $request->validate(['title' => 'required|string|max:255']);
        $chat->update(['title' => $request->input('title')]);
        return redirect()->back();
    }

    public function destroy($id)
    {
        $chat = Chat::findOrFail($id);
        $currentUrl = url()->previous();
        $isCurrent = str_contains($currentUrl, '/chat/' . $id);

        $chat->messages()->delete();
        $chat->delete();

        if ($isCurrent) {
            return redirect()->route('chat.index');
        }
        return redirect()->back();
    }
}
