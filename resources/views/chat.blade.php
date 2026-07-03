<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neuron AI Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #030712; 
            color: #f3f4f6;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        .sidebar { 
            height: 100vh; 
            display: flex;
            flex-direction: column;
            border-right: 1px solid #1f2937; 
            background-color: #0b0f19; 
            padding: 0 !important;
        }
        
        .sidebar-scroll-content {
            flex: 1;
            overflow-y: auto;
            padding: 20px 15px;
            scrollbar-width: none; 
            -ms-overflow-style: none;  
        }
        
        .sidebar-scroll-content::-webkit-scrollbar {
            display: none;
            width: 0 !important;
            background: transparent;
        }

        .model-status-box {
            background-color: #111827;
            border-top: 1px solid #1f2937;
            padding: 15px;
            font-size: 0.85rem;
            color: #9ca3af;
        }

        .model-status-badge {
            display: inline-block;
            background-color: #1e293b;
            color: #38bdf8;
            padding: 5px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.8rem;
            margin-top: 5px;
            border: 1px solid #334155;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .chat-container { 
            max-width: 1000px; 
            margin: 0 auto; 
            height: 100vh; 
            display: flex; 
            flex-direction: column; 
            background-color: #030712;
        }

        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #0b0f19;
            border-bottom: 1px solid #1f2937;
        }

        .config-btn {
            background: transparent;
            border: none;
            color: #9ca3af;
            font-size: 1.4rem;
            cursor: pointer;
            transition: color 0.2s;
            line-height: 1;
        }

        .config-btn:hover { color: #ffffff; }

        .chat-messages { 
            flex: 1; 
            overflow-y: auto; 
            padding: 30px 20px; 
            scrollbar-width: none; 
            -ms-overflow-style: none;  
        }

        .chat-messages::-webkit-scrollbar { display: none; width: 0 !important; background: transparent; }

        .message-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            width: 100%;
        }

        .message-row.user { flex-direction: row-reverse; }

        .avatar-badge {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .message-row.user .avatar-badge {
            background-color: #1e293b; color: #38bdf8; margin-left: 15px; border: 1px solid #334155;
        }

        .message-row.assistant .avatar-badge {
            background-color: #064e3b; color: #34d399; margin-right: 15px; border: 1px solid #065f46;
        }

        .message-content { padding: 20px; border-radius: 12px; max-width: 75%; line-height: 1.6; font-size: 0.95rem; }
        .message-row.user .message-content { background-color: #3b82f6; color: #ffffff; border-top-right-radius: 4px; }
        .message-row.assistant .message-content { background-color: #0b1329; color: #e5e7eb; border-top-left-radius: 4px; border: 1px solid #1e293b; }

        .pdf-attachment {
            background-color: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px; padding: 8px 12px; display: inline-flex; align-items: center;
            font-family: monospace; font-size: 0.85rem; color: #f3f4f6; max-width: 100%;
        }

        .chat-item-wrapper { position: relative; display: flex; align-items: center; margin-bottom: 6px; }
        
        .list-group-item {
            background-color: #0b0f19; color: #9ca3af; border: 1px solid #1f2937;
            border-radius: 8px !important; padding-right: 40px; flex: 1; width: 100%; text-align: left;
        }
        .list-group-item:hover { background-color: #111827; color: #ffffff; }
        .list-group-item.active { background-color: #1f2937 !important; border-color: #3b82f6 !important; color: #ffffff !important; }

        .chat-options-btn {
            position: absolute; right: 10px; background: transparent; border: none; color: #6b7280;
            padding: 2px 6px; font-size: 1.1rem; cursor: pointer; border-radius: 4px; display: none; z-index: 10;
        }
        .chat-item-wrapper:hover .chat-options-btn, .chat-options-btn.show { display: block; }
        .chat-options-btn:hover { color: #ffffff; background-color: #1f2937; }

        .input-panel { background-color: #0b0f19; border-top: 1px solid #1f2937; }
        
        .form-control, .form-control:focus, .form-select, .form-select:focus {
            background-color: #111827; border: 1px solid #374151; color: #ffffff;
            box-shadow: none !important;
        }
        .form-control::placeholder { color: #4b5563; }

        .modal-content-dark { background-color: #0b0f19; border: 1px solid #1f2937; color: #f3f4f6; border-radius: 12px; }
        .modal-header-dark { border-bottom: 1px solid #1f2937; padding: 20px; }
        .modal-footer-dark { border-top: 1px solid #1f2937; padding: 15px 20px; }

        /* 🎯 NUOVO STILE PREMIUM PER IL BOX INTERNET (Rif. image_04a354.png) */
        .search-toggle-box {
            background-color: rgba(17, 24, 39, 0.6);
            border: 1px solid #1f2937;
            border-radius: 8px;
            padding: 14px 16px;
            transition: all 0.25s ease-in-out;
        }
        
        .search-toggle-box:hover {
            background-color: rgba(31, 41, 55, 0.4);
            border-color: #374151;
        }

        /* Cambiamo colore allo switch di Bootstrap per farlo sposare con il look scuro */
        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.35em;
            background-color: #374151;
            border-color: #4b5563;
            cursor: pointer;
            transition: background-position .15s ease-in-out, background-color .15s ease-in-out;
        }

        .form-switch .form-check-input:checked {
            background-color: #ef4444; /* Rosso morbido/Pastello solo quando disattiva internet */
            border-color: #f87171;
        }

        .form-check-input:focus {
            box-shadow: none !important;
            border-color: #4b5563;
        }

        body::-webkit-scrollbar { display: none; width: 0; }
        body { scrollbar-width: none; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 sidebar d-none d-md-flex">
            <div class="sidebar-scroll-content">
                <a href="{{ route('chat.index') }}" class="btn btn-outline-secondary w-100 mb-4 text-light border-secondary">+ Nuova Chat</a>
                <div class="list-group">
                    @foreach($chats as $c)
                        <div class="chat-item-wrapper">
                            <a href="{{ route('chat.show', $c->id) }}" 
                               class="list-group-item list-group-item-action @if(isset($currentChat) && $currentChat->id == $c->id) active @endif"
                               title="{{ $c->title }}">
                                💬 {{ Str::limit($c->title, 20) }}
                            </a>
                            
                            <div class="dropdown">
                                <button class="chat-options-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">⋮</button>
                                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="renameChat({{ $c->id }}, '{{ addslashes($c->title) }}')">✏️ Rinomina</a></li>
                                    <li><hr class="dropdown-divider border-secondary"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteChat({{ $c->id }})">🗑️ Cancella</a></li>
                                </ul>
                            </div>

                            <form id="form-rename-{{ $c->id }}" action="{{ route('chat.update', $c->id) }}" method="POST" style="display:none;">
                                @csrf @method('PUT')
                                <input type="hidden" name="title" id="input-rename-{{ $c->id }}">
                            </form>
                            <form id="form-delete-{{ $c->id }}" action="{{ route('chat.destroy', $c->id) }}" method="POST" style="display:none;">
                                @csrf @method('DELETE')
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Box modello API in uso -->
            <div class="model-status-box">
                <div class="model-status-badge">
                    ⚡ Modello API in uso: {{ session('ai_model', env('GEMINI_MODEL', 'gemini-1.5-flash')) }}
                </div>
            </div>
        </div>

        <!-- Finestra principale della Chat -->
        <div class="col-md-9 p-0">
            <div class="chat-container">
                
                <!-- Intestazione chat con Ingranaggio -->
                <div class="chat-header">
                    <h6 class="mb-0 text-muted">
                        {{ isset($currentChat) ? $currentChat->title : 'Nuova Sessione Agentica' }}
                    </h6>
                    <button class="config-btn" type="button" data-bs-toggle="modal" data-bs-target="#configModal" title="Configura Modelli e API">
                        ⚙️
                    </button>
                </div>

                <div class="chat-messages" id="chatBox">
                    @if(isset($currentChat) && $currentChat->messages->count() > 0)
                        @foreach($currentChat->messages as $msg)
                            <div class="message-row {{ $msg->role }}">
                                <div class="avatar-badge">{{ $msg->role == 'user' ? 'U' : 'AI' }}</div>
                                <div class="message-content">
                                    @if($msg->file_name)
                                        <div class="pdf-attachment mb-3">
                                            <span class="text-danger me-2">📄 PDF</span> {{ $msg->file_name }}
                                        </div>
                                    @endif
                                    <p class="mb-0">{!! nl2br(e($msg->content)) !!}</p>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted mt-5">
                            <h4 class="fw-light">Pronto ad aiutarti.</h4>
                            <p class="small text-secondary">Invia un messaggio o esegui l'analisi di un documento PDF.</p>
                        </div>
                    @endif
                </div>

                <!-- Input Panel -->
                <div class="p-3 input-panel">
                    <form id="chatForm" action="{{ isset($currentChat) ? route('chat.send', $currentChat->id) : route('chat.send') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="input-group mb-2">
                            <input type="file" name="file" id="fileInput" class="form-control form-control-sm" accept=".pdf">
                        </div>
                        <div class="input-group">
                            <input type="text" name="message" id="messageInput" class="form-control" placeholder="Chiedi qualcosa..." required autocomplete="off">
                            <button class="btn btn-primary px-4" type="submit" id="btnInvia">Invia</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Finestra Modale Impostazioni -->
<div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="configModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-dark">
            <div class="modal-header modal-header-dark">
                <h5 class="modal-title d-flex align-items-center" id="configModalLabel">
                    <span class="me-2">⚙️</span> Impostazioni IA e API Key
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('chat.saveConfig') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold">AI Provider</label>
                        <select name="ai_provider" class="form-select form-select-sm">
                            <option value="google" {{ session('ai_provider') == 'google' ? 'selected' : '' }}>Google Gemini</option>
                            <option value="openai" {{ session('ai_provider') == 'openai' ? 'selected' : '' }}>OpenAI</option>
                            <option value="anthropic" {{ session('ai_provider') == 'anthropic' ? 'selected' : '' }}>Anthropic Claude</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold">Modello IA</label>
                        <input type="text" name="ai_model" class="form-control form-control-sm" placeholder="es. gemini-1.5-pro, gpt-4o" value="{{ session('ai_model', 'gemini-1.5-flash') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-secondary fw-semibold">AI API Key <span class="text-muted fw-normal">(Lascia vuoto per usare .env)</span></label>
                        <input type="password" name="ai_key" class="form-control form-control-sm" placeholder="Incolla l'API Key del Provider" value="{{ session('ai_key') }}">
                    </div>
                    <div class="mb-3 class-tavily-section">
                        <label class="form-label small text-secondary fw-semibold">Tavily Search API Key <span class="text-muted fw-normal">(Lascia vuoto per usare .env)</span></label>
                        <input type="password" name="tavily_key" class="form-control form-control-sm" placeholder="Incolla la Tavily API Key" value="{{ session('tavily_key') }}">
                    </div>

                    <!-- 🎯 BOX AGGIORNATO E MIGLIORATO GRAFICAMENTE (Rif. image_04a354.png) -->
                    <div class="search-toggle-box d-flex justify-content-between align-items-center mt-4">
                        <div class="d-flex align-items-center">
                            <span class="fs-5 me-2">🌐</span>
                            <div>
                                <div class="small fw-semibold text-light">Modalità Offline</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Disabilita la ricerca Web in tempo reale</div>
                            </div>
                        </div>
                        <div class="form-check form-switch p-0 m-0">
                            <input class="form-check-input" type="checkbox" name="disable_search" id="disableSearchCheck" value="1" {{ session('disable_search') ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
                <div class="modal-footer modal-footer-dark">
                    <button type="button" class="btn btn-sm btn-outline-secondary text-light border-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3">Salva Configurazione</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const chatBox = document.getElementById('chatBox');
    if(chatBox) { chatBox.scrollTop = chatBox.scrollHeight; }

    document.getElementById('chatForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('btnInvia');
        const input = document.getElementById('messageInput');
        if (input.value.trim() === '' && document.getElementById('fileInput').files.length === 0) { e.preventDefault(); return; }
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    });

    function renameChat(id, currentTitle) {
        let newTitle = prompt("Inserisci il nuovo nome per la chat:", currentTitle);
        if (newTitle !== null && newTitle.trim() !== "") {
            document.getElementById('input-rename-' + id).value = newTitle.trim();
            document.getElementById('form-rename-' + id).submit();
        }
    }

    function deleteChat(id) {
        if (confirm("Sei sicuro di voler eliminare questa conversazione?")) {
            document.getElementById('form-delete-' + id).submit();
        }
    }
</script>

</body>
</html>
