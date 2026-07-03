<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Services\AI\LLMServiceInterface;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected LLMServiceInterface $aiService;

    public function __construct(LLMServiceInterface $aiService)
    {
        $this->aiService = $aiService;
    }

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
     * Invia il messaggio leggendo correttamente il testo in qualsiasi formato di richiesta
     */
    public function send(Request $request, $id = null)
    {
        // Estrazione sicura: controlla prima l'input standard, poi il flusso JSON
        $prompt = $request->input('message') ?? ($request->isJson() ? $request->json('message') : '');
        $prompt = trim($prompt);

        $context = "";
        $fileName = null;

        if ($id) {
            $chat = Chat::findOrFail($id);
        } else {
            $chat = Chat::create([
                'title' => $request->hasFile('file') ? 'Analisi: ' . substr($request->file('file')->getClientOriginalName(), 0, 20) : (substr($prompt, 0, 30) ?: 'Nuova Chat')
            ]);
        }

        // Gestione dell'allegato PDF
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
                    $context = "[NOTA DI SISTEMA: Il file PDF '$fileName' è stato caricato ma non contiene testo vettoriale selezionabile. Potrebbe essere una scansione o un'immagine. Metadati estratti: $metaString]";
                }

            } catch (\Exception $e) {
                Log::error('Errore estrazione PDF: ' . $e->getMessage());
                $context = "[NOTA DI SISTEMA: Errore di lettura dei vettori di testo per il file '$fileName'. Dimensione: " . $file->getSize() . " bytes]";
            }

            // SALVA LA TUA VERA DOMANDA (Usa la stringa fissa SOLO se hai lasciato il campo vuoto)
            $chat->messages()->create([
                'role' => 'user',
                'content' => !empty($prompt) ? $prompt : "Analizza il file allegato ed esponi il contenuto.",
                'file_name' => $fileName
            ]);

            // Sistema di istruzioni per Gemini basato sulla TUA domanda reale
            $finalQuestion = !empty($prompt) ? $prompt : "Fai un riassunto di questo documento";
            $systemInstruction = "L'utente ti ha inviato un file denominato '$fileName'. Se il contesto allegato indica che non è stato possibile estrarre testo nativo (perché è un PDF scansionato o protetto), non limitarti a dire che è vuoto. Spiega all'utente in modo cortese che il documento appare come un'immagine/scansione non digitalizzata, formula un'ipotesi sul contenuto basandoti sul nome del file e sulla domanda dell'utente ('{$finalQuestion}'), e offri indicazioni su come procedere.\n\n";

            $aiResponse = $this->aiService->chat($systemInstruction . $finalQuestion, $context);

            $chat->messages()->create([
                'role' => 'assistant',
                'content' => $aiResponse
            ]);

            return redirect()->route('chat.show', $chat->id);
        }

        // Caso standard di solo testo senza file
        if (!empty($prompt)) {
            $chat->messages()->create([
                'role' => 'user',
                'content' => $prompt
            ]);

            $aiResponse = $this->aiService->chat($prompt, null);
            
            $chat->messages()->create([
                'role' => 'assistant',
                'content' => $aiResponse
            ]);
        }

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json(['success' => true, 'chat_id' => $chat->id]);
        }

        return redirect()->route('chat.show', $chat->id);
    }
}
