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
     * Gestione dell'invio messaggi con Neuron AI Framework
     */
    public function send(Request $request, $id = null)
    {
        $prompt = $request->input('message');
        $prompt = $prompt ? trim($prompt) : '';

        $context = "";
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

                $context = mb_convert_encoding($rawText, 'UTF-8', 'UTF-8');
                $context = str_replace(["\r", "\n", "\t"], " ", $context);
                $context = preg_replace('/\s+/', ' ', $context);
                $context = trim($context);

                if (empty($context)) {
                    $context = "[Il file PDF '$fileName' non contiene testo selezionabile]";
                }

            } catch (\Exception $e) {
                Log::error('Errore estrazione PDF: ' . $e->getMessage());
                $context = "[Errore di lettura del file '$fileName']";
            }

            $chat->messages()->create([
                'role' => 'user',
                'content' => !empty($prompt) ? $prompt : "Analizza il file allegato ed esponi il contenuto.",
                'file_name' => $fileName
            ]);

            $finalQuestion = !empty($prompt) ? $prompt : "Fai un riassunto di questo documento";
            $fullAgentPrompt = "Nome Documento: $fileName\nContesto Estratto: $context\n\nRichiesta Utente: $finalQuestion";

            try {
                $agent = PdfChatAgent::make();
                $neuronResponse = $agent->chat(new UserMessage($fullAgentPrompt));
                $aiResponse = $neuronResponse->getMessage()->getContent();
            } catch (\Exception $e) {
                Log::error("Errore agente su PDF: " . $e->getMessage());
                $aiResponse = "Si è verificato un errore nell'elaborazione del documento.";
            }

            $chat->messages()->create([
                'role' => 'assistant',
                'content' => $aiResponse
            ]);

            return redirect()->route('chat.show', $chat->id);
        }

        // 2. Caso standard: solo messaggio testuale senza file (Attiva la ricerca Web)
        if (!empty($prompt)) {
            $chat->messages()->create([
                'role' => 'user',
                'content' => $prompt
            ]);

            try {
                Log::info("Inizio sessione agente Neuron per prompt testuale.");
                $agent = PdfChatAgent::make();
                
                $neuronResponse = $agent->chat(new UserMessage($prompt));
                $aiResponse = $neuronResponse->getMessage()->getContent();
                
                Log::info("Risposta ricevuta dall'agente Neuron: " . ($aiResponse ?? 'VUOTA'));

                if (empty($aiResponse)) {
                    $aiResponse = "L'agente ha eseguito la ricerca richiesta su Internet, ma la risposta è rimasta vuota. Riprova.";
                }

            } catch (\Exception $e) {
                Log::error("CRASH NEURON FRAMEWORK NELLA RISPOSTA: " . $e->getMessage());
                $aiResponse = "Errore di comunicazione interna durante la ricerca online.";
            }
            
            $chat->messages()->create([
                'role' => 'assistant',
                'content' => $aiResponse
            ]);
        }

        return redirect()->to('/chat/' . $chat->id);
    }

    /**
     * 🎯 NUOVO: Rinomina la chat
     */
    public function update(Request $request, $id)
    {
        $chat = Chat::findOrFail($id);
        $request->validate([
            'title' => 'required|string|max:255'
        ]);

        $chat->update([
            'title' => $request->input('title')
        ]);

        return redirect()->back();
    }

    /**
     * 🎯 NUOVO: Elimina la chat e tutti i suoi messaggi collegati
     */
    public function destroy($id)
    {
        $chat = Chat::findOrFail($id);
        
        // Se eliminiamo la chat attualmente visualizzata, dovremo reindirizzare alla home
        $currentUrl = url()->previous();
        $isCurrent = str_contains($currentUrl, '/chat/' . $id);

        // I messaggi associati si cancellano automaticamente se hai impostato la foreign key con onDelete('cascade')
        // Altrimenti li eliminiamo esplicitamente qui prima della chat:
        $chat->messages()->delete();
        $chat->delete();

        if ($isCurrent) {
            return redirect()->route('chat.index');
        }

        return redirect()->back();
    }
}
