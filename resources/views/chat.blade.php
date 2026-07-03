<!DOCTYPE html>
<html lang="it" class="h-full bg-slate-950 text-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neuron AI Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-full flex font-sans antialiased selection:bg-indigo-500/30">

    <!-- SIDEBAR -->
    <aside class="w-80 bg-slate-900 border-r border-slate-800 flex flex-col justify-between hidden md:flex">
        <div class="p-4 flex flex-col h-full overflow-hidden">
            <a href="{{ route('chat.index') }}" class="flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 font-medium rounded-lg text-sm transition-all shadow-lg shadow-indigo-600/10 mb-6">
                <i class="fa-solid fa-plus text-xs"></i> Nuova Chat
            </a>

            <div class="flex-1 overflow-y-auto space-y-1.5 pr-1">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider px-2 mb-2">Cronologia Chat</h3>
                @forelse($chats as $chat)
                    <a href="{{ route('chat.show', $chat->id) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-all {{ (isset($currentChat) && $currentChat->id === $chat->id) ? 'bg-slate-800 text-white font-medium border border-slate-700/50' : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200' }}">
                        <i class="fa-regular fa-comment text-slate-500"></i>
                        <span class="truncate">{{ $chat->title }}</span>
                    </a>
                @empty
                    <p class="text-xs text-slate-500 px-2 italic">Nessuna chat recente.</p>
                @endforelse
            </div>
        </div>

        <div class="p-4 border-t border-slate-800 bg-slate-950/40 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center font-bold text-xs">U</div>
            <div class="truncate">
                <p class="text-sm font-medium leading-none">Utente Workspace</p>
                <p class="text-xs text-slate-500 truncate mt-0.5">docker-env@neuron-ai.dev</p>
            </div>
        </div>
    </aside>

    <!-- MAIN CHAT AREA -->
    <main class="flex-1 flex flex-col h-full bg-slate-950 relative">
        
        <header class="h-14 border-b border-slate-800/80 px-6 flex items-center justify-between bg-slate-950/50 backdrop-blur-md sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-6 h-6 rounded bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">N</div>
                <h2 class="text-sm font-semibold tracking-tight">
                    {{ isset($currentChat) ? $currentChat->title : 'Neuron RAG Agent & Gemini' }}
                </h2>
            </div>
            <div class="flex items-center gap-2 text-xs text-slate-400 bg-slate-900 border border-slate-800 px-2.5 py-1 rounded-full font-mono">
                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span> Docker Active
            </div>
        </header>

        <div id="chat-window" class="flex-1 overflow-y-auto p-6 space-y-6 pb-36">
            @if(!isset($currentChat) || $currentChat->messages->isEmpty())
                <div id="welcome-screen" class="max-w-2xl mx-auto text-center pt-20 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl mx-auto">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                    <h1 class="text-xl font-semibold text-slate-200">Come posso aiutarti oggi?</h1>
                    <p class="text-sm text-slate-400 max-w-md mx-auto">Carica un documento in formato <strong>PDF</strong> ed effettua una domanda.</p>
                </div>
                <div id="messages-container" class="max-w-3xl mx-auto space-y-6 hidden"></div>
            @else
                <div id="messages-container" class="max-w-3xl mx-auto space-y-6">
                    @foreach($currentChat->messages as $message)
                        <div class="flex gap-4 {{ $message->role === 'user' ? 'justify-end' : 'justify-start' }}">
                            
                            @if($message->role === 'assistant')
                                <div class="w-8 h-8 rounded-lg bg-emerald-600/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs shrink-0 font-bold">AI</div>
                            @endif

                            <div class="max-w-[80%] rounded-2xl p-4 text-sm leading-relaxed shadow-sm
                                {{ $message->role === 'user' 
                                    ? 'bg-indigo-600 text-white rounded-br-none font-medium' 
                                    : 'bg-slate-900 text-slate-200 border border-slate-800 rounded-bl-none' }}">
                                
                                @if($message->role === 'user' && $message->file_name)
                                    <div class="mb-3 p-2 bg-black/20 rounded-lg flex items-center gap-2 text-xs text-indigo-200 border border-indigo-400/10">
                                        <i class="fa-regular fa-file-pdf text-sm text-red-400"></i>
                                        <span class="truncate font-mono">{{ $message->file_name }}</span>
                                    </div>
                                @endif

                                <p class="whitespace-pre-line">{{ $message->content }}</p>
                            </div>

                            @if($message->role === 'user')
                                <div class="w-8 h-8 rounded-lg bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xs shrink-0 font-bold">U</div>
                            @endif

                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- FLOATING INPUT PANEL -->
        <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-slate-950 via-slate-950/95 to-transparent pt-10 pb-6 px-6">
            <div class="max-w-3xl mx-auto">
                
                <form id="chat-form" action="{{ isset($currentChat) ? route('chat.send', $currentChat->id) : route('chat.send') }}" method="POST" enctype="multipart/form-data" class="bg-slate-900 border border-slate-800 rounded-xl p-2 shadow-2xl focus-within:border-slate-700 transition-all flex flex-col gap-2">
                    @csrf
                    
                    <!-- BOX ANTEPRIMA FILE -->
                    <div id="file-preview" class="hidden px-3 py-2 flex items-center justify-between text-xs text-slate-300 bg-slate-950 rounded-lg border border-slate-800/80">
                        <div class="flex items-center gap-2 truncate">
                            <i class="fa-solid fa-file-pdf text-red-400 text-sm"></i>
                            <span id="file-preview-name" class="truncate font-mono font-medium text-slate-200">nessun file</span>
                        </div>
                        <button type="button" onclick="clearFile()" class="text-slate-500 hover:text-red-400 p-1 transition-colors">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <div class="flex items-end gap-2">
                        <label class="cursor-pointer flex items-center justify-center w-10 h-10 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-slate-200 transition-colors shrink-0 mb-0.5">
                            <input type="file" name="file" id="file-input" class="hidden" accept="application/pdf" onchange="handleFileSelected(this)">
                            <i class="fa-solid fa-paperclip text-lg"></i>
                        </label>

                        <textarea name="message" id="message-textarea" rows="1" placeholder="Fai una domanda o allega un documento..." class="flex-1 bg-transparent border-0 focus:ring-0 text-sm text-slate-100 placeholder-slate-500 resize-none py-2.5 focus:outline-none min-h-[40px] max-h-[160px] font-medium" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>

                        <button type="submit" id="submit-btn" class="w-10 h-10 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white flex items-center justify-center transition-all shrink-0 shadow-md shadow-indigo-600/10 mb-0.5">
                            <i class="fa-solid fa-arrow-up text-sm"></i>
                        </button>
                    </div>
                </form>
                
                <div class="text-[11px] text-center text-slate-600 mt-2 font-medium tracking-wide">
                    Neuron AI Stack • Modellazione guidata tramite Gemini Pro e Docker Container.
                </div>
            </div>
        </div>
    </main>

    <!-- INTERFACCIA JAVASCRIPT DEFINITIVA -->
    <script>
        const chatId = "{{ isset($currentChat) ? $currentChat->id : '' }}";

        // FORZA LO SCROLL IN BASSO ALL'APERTURA DELLA CHAT O DOPO IL REFRESH PDF
        document.addEventListener("DOMContentLoaded", function() {
            const chatWindow = document.getElementById('chat-window');
            if (chatWindow) {
                chatWindow.scrollTop = chatWindow.scrollHeight;
            }
        });

        function handleFileSelected(input) {
            const preview = document.getElementById('file-preview');
            const nameSpan = document.getElementById('file-preview-name');
            if (input.files && input.files[0]) {
                nameSpan.innerText = input.files[0].name;
                preview.classList.remove('hidden');
            }
        }

        function clearFile() {
            const input = document.getElementById('file-input');
            const preview = document.getElementById('file-preview');
            input.value = '';
            preview.classList.add('hidden');
        }

        document.getElementById('chat-form').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('file-input');
            const textarea = document.getElementById('message-textarea');
            const prompt = textarea.value.trim();

            const welcomeScreen = document.getElementById('welcome-screen');
            const container = document.getElementById('messages-container');
            const chatWindow = document.getElementById('chat-window');

            // --- CASO 1: INVIO CON FILE PDF ALLEGATO ---
            if (fileInput.files.length > 0) {
                if (welcomeScreen) welcomeScreen.classList.add('hidden');
                container.classList.remove('hidden');

                // Disegna il box dell'utente preservando la domanda corretta
                let fileBadgeHtml = `
                    <div class="mb-3 p-2 bg-black/20 rounded-lg flex items-center gap-2 text-xs text-indigo-200 border border-indigo-400/10">
                        <i class="fa-regular fa-file-pdf text-sm text-red-400"></i>
                        <span class="truncate font-mono">${escapeHtml(fileInput.files[0].name)}</span>
                    </div>
                `;

                const userBox = `
                    <div class="flex gap-4 justify-end mb-4">
                        <div class="max-w-[80%] rounded-2xl p-4 text-sm leading-relaxed shadow-sm bg-indigo-600 text-white rounded-br-none font-medium">
                            ${fileBadgeHtml}
                            <p class="whitespace-pre-line">${escapeHtml(prompt || "Analizza questo documento allegato.")}</p>
                        </div>
                        <div class="w-8 h-8 rounded-lg bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xs shrink-0 font-bold">U</div>
                    </div>
                `;
                container.innerHTML += userBox;

                // Genera il loader animato in fondo alla pagina
                const aiMessageId = 'ai-chunked-' + Date.now();
                container.innerHTML += `
                    <div class="flex gap-4 justify-start mb-4" id="${aiMessageId}-row">
                        <div class="w-8 h-8 rounded-lg bg-emerald-600/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs shrink-0 font-bold">AI</div>
                        <div class="max-w-[80%] rounded-2xl p-4 text-sm leading-relaxed shadow-sm bg-slate-900 text-slate-200 border border-slate-800 rounded-bl-none">
                            <div id="${aiMessageId}" class="flex items-center gap-2 text-slate-400 font-medium italic">
                                <i class="fa-solid fa-circle-notch animate-spin text-emerald-400"></i> Elaborazione del documento e generazione risposta...
                            </div>
                        </div>
                    </div>
                `;
                
                chatWindow.scrollTop = chatWindow.scrollHeight;

                this.action = chatId ? `/chat/send/${chatId}` : '/chat/send';
                document.getElementById('submit-btn').disabled = true;
                textarea.disabled = true; 
                
                return; // Consente la sottomissione sincrona multipart
            }

            // --- CASO 2: SOLO TESTO SEMPLICE ---
            e.preventDefault(); 
            if (!prompt) return;

            if (welcomeScreen) welcomeScreen.classList.add('hidden');
            container.classList.remove('hidden');

            const userBox = `
                <div class="flex gap-4 justify-end mb-4">
                    <div class="max-w-[80%] rounded-2xl p-4 text-sm leading-relaxed shadow-sm bg-indigo-600 text-white rounded-br-none font-medium">
                        <p class="whitespace-pre-line">${escapeHtml(prompt)}</p>
                    </div>
                    <div class="w-8 h-8 rounded-lg bg-indigo-600/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xs shrink-0 font-bold">U</div>
                </div>
            `;
            container.innerHTML += userBox;
            
            textarea.value = '';
            textarea.style.height = '40px';

            const aiMessageId = 'ai-chunked-' + Date.now();
            container.innerHTML += `
                <div class="flex gap-4 justify-start mb-4" id="${aiMessageId}-row">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs shrink-0 font-bold">AI</div>
                    <div class="max-w-[80%] rounded-2xl p-4 text-sm leading-relaxed shadow-sm bg-slate-900 text-slate-200 border border-slate-800 rounded-bl-none">
                        <div id="${aiMessageId}" class="flex items-center gap-2 text-slate-400 font-medium italic">
                            <i class="fa-solid fa-circle-notch animate-spin text-emerald-400"></i> Sto elaborando la risposta...
                        </div>
                    </div>
                </div>
            `;
            chatWindow.scrollTop = chatWindow.scrollHeight;

            const targetAction = chatId ? `/chat/send/${chatId}` : '/chat/send';
            
            fetch(targetAction, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({ message: prompt })
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload();
                } else {
                    throw new Error();
                }
            })
            .catch(err => {
                const aiTarget = document.getElementById(aiMessageId);
                if (aiTarget) {
                    aiTarget.innerHTML = `<span class="text-red-400"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Errore temporaneo nella risposta. Riprova.</span>`;
                }
            });
        });

        function escapeHtml(text) {
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>
