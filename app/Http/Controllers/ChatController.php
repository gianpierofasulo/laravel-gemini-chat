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

        // Recuperiamo o creiamo la chat nel database locale
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
                    $details = $pdfDocument->getDetails();
                    $metaString = "";
                    if (is_array($details)) {
                        foreach ($details as $key => $value) {
                            if (is_string($value)) {
                                $metaString .= "$key: $value; ";
                            }
                        }
                    }
                    $context = "[Il file PDF '$fileName' non contiene testo selezionabile]";
                }

            } catch (\Exception $e) {
                Log::error('Errore estrazione PDF: ' . $e->getMessage());
                $context = "[Errore di lettura del file '$fileName']";
            }

            // Salva il messaggio utente nel DB locale
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
            // Salva immediatamente il messaggio dell'utente nel DB
            $chat->messages()->create([
                'role' => 'user',
                'content' => $prompt
            ]);

            try {
                Log::info("Inizio sessione agente Neuron per prompt testuale.");
                $agent = PdfChatAgent::make();
                
                // Chiamata singola sincrona che gestisce internamente il tool
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
            
            // Salvataggio sul DB locale
            $chat->messages()->create([
                'role' => 'assistant',
                'content' => $aiResponse
            ]);
            
            Log::info("Risposta dell'assistente salvata con successo nel DB per la chat ID: " . $chat->id);
        }

        // Reindirizziamo in modo rigido e sincrono alla rotta della chat specifica
        return redirect()->to('/chat/' . $chat->id);
    }
}
