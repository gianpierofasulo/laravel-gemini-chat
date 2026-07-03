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
            overflow-y: auto; 
            border-right: 1px solid #1f2937; 
            background-color: #0b0f19; 
            scrollbar-width: none; 
            -ms-overflow-style: none;  
        }
        
        .sidebar::-webkit-scrollbar {
            display: none;
            width: 0 !important;
            background: transparent;
        }

        .chat-container { 
            max-width: 1000px; 
            margin: 0 auto; 
            height: 100vh; 
            display: flex; 
            flex-direction: column; 
            background-color: #030712;
        }

        .chat-messages { 
            flex: 1; 
            overflow-y: auto; 
            padding: 40px 20px; 
            scrollbar-width: none; 
            -ms-overflow-style: none;  
        }

        .chat-messages::-webkit-scrollbar { 
            display: none; 
            width: 0 !important;
            height: 0 !important;
            background: transparent;
        }

        .message-row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            width: 100%;
        }

        .message-row.user {
            flex-direction: row-reverse;
        }

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
            background-color: #1e293b;
            color: #38bdf8;
            margin-left: 15px;
            border: 1px solid #334155;
        }

        .message-row.assistant .avatar-badge {
            background-color: #064e3b;
            color: #34d399;
            margin-right: 15px;
            border: 1px solid #065f46;
        }

        .message-content { 
            padding: 20px; 
            border-radius: 12px; 
            max-width: 75%; 
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .message-row.user .message-content { 
            background-color: #3b82f6; 
            color: #ffffff; 
            border-top-right-radius: 4px;
        }

        .message-row.assistant .message-content { 
            background-color: #0b1329; 
            color: #e5e7eb; 
            border-top-left-radius: 4px;
            border: 1px solid #1e293b;
        }

        .pdf-attachment {
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            padding: 8px 12px;
            display: inline-flex;
            align-items: center;
            font-family: monospace;
            font-size: 0.85rem;
            color: #f3f4f6;
            max-width: 100%;
        }

        /* STRUTTURA SIDEBAR CON TRE PUNTINI */
        .chat-item-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            margin-bottom: 6px;
        }

        .list-group-item {
            background-color: #0b0f19;
            color: #9ca3af;
            border: 1px solid #1f2937;
            border-radius: 8px !important;
            padding-right: 40px; /* Spazio per l'icona opzioni */
            flex: 1;
            width: 100%;
            transition: all 0.2s;
            text-align: left;
        }

        .list-group-item:hover {
            background-color: #111827;
            color: #ffffff;
        }

        .list-group-item.active {
            background-color: #1f2937 !important;
            border-color: #3b82f6 !important;
            color: #ffffff !important;
        }

        /* Bottone dei tre puntini di opzione, visibile nitidamente su hover */
        .chat-options-btn {
            position: absolute;
            right: 10px;
            background: transparent;
            border: none;
            color: #6b7280;
            padding: 2px 6px;
            font-size: 1.1rem;
            cursor: pointer;
            border-radius: 4px;
            display: none;
            z-index: 10;
        }

        .chat-item-wrapper:hover .chat-options-btn,
        .chat-options-btn.show {
            display: block;
        }

        .chat-options-btn:hover {
            color: #ffffff;
            background-color: #1f2937;
        }

        /* Dropdown personalizzato scuro */
        .dropdown-menu-dark {
            background-color: #111827;
            border: 1px solid #374151;
        }

        .input-panel {
            background-color: #0b0f19;
            border-top: 1px solid #1f2937;
        }

        .form-control, .form-control:focus {
            background-color: #111827;
            border: 1px solid #374151;
            color: #ffffff;
        }

        .form-control::placeholder {
            color: #4b5563;
        }

        body::-webkit-scrollbar { display: none; width: 0; }
        body { scrollbar-width: none; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 sidebar p-3 d-none d-md-block">
            <a href="{{ route('chat.index') }}" class="btn btn-outline-secondary w-100 mb-4 text-light border-secondary">+ Nuova Chat</a>
            <div class="list-group">
                @foreach($chats as $c)
                    <div class="chat-item-wrapper">
                        <a href="{{ route('chat.show', $c->id) }}" 
                           class="list-group-item list-group-item-action @if(isset($currentChat) && $currentChat->id == $c->id) active @endif"
                           title="{{ $c->title }}">
                            💬 {{ Str::limit($c->title, 20) }}
                        </a>
                        
                        <!-- Menu Dropdown a Tre Puntini -->
                        <div class="dropdown">
                            <button class="chat-options-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                ⋮
                            </button>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="renameChat({{ $c->id }}, '{{ addslashes($c->title) }}')">✏️ Rinomina</a>
                                </li>
                                <li><hr class="dropdown-divider border-secondary"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="deleteChat({{ $c->id }})">🗑️ Cancella</a>
                                </li>
                            </ul>
                        </div>

                        <!-- Form Nascosti per la gestione asincrona/sincrona nativa -->
                        <form id="form-rename-{{ $c->id }}" action="{{ route('chat.update', $c->id) }}" method="POST" style="display:none;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="title" id="input-rename-{{ $c->id }}">
                        </form>

                        <form id="form-delete-{{ $c->id }}" action="{{ route('chat.destroy', $c->id) }}" method="POST" style="display:none;">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Finestra principale della Chat -->
        <div class="col-md-9 p-0">
            <div class="chat-container">
                <div class="chat-messages" id="chatBox">
                    @if(isset($currentChat) && $currentChat->messages->count() > 0)
                        @foreach($currentChat->messages as $msg)
                            <div class="message-row {{ $msg->role }}">
                                <div class="avatar-badge">
                                    {{ $msg->role == 'user' ? 'U' : 'AI' }}
                                </div>
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

<!-- Carichiamo il JS di Bootstrap indispensabile per attivare i menu dei tre puntini -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const chatBox = document.getElementById('chatBox');
    if(chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    document.getElementById('chatForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('btnInvia');
        const input = document.getElementById('messageInput');
        
        if (input.value.trim() === '' && document.getElementById('fileInput').files.length === 0) {
            e.preventDefault();
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    });

    // 🎯 LOGICA INTERATTIVA DI RINOMINA CON PROMPT
    function renameChat(id, currentTitle) {
        let newTitle = prompt("Inserisci il nuovo nome per la chat:", currentTitle);
        if (newTitle !== null && newTitle.trim() !== "") {
            document.getElementById('input-rename-' + id).value = newTitle.trim();
            document.getElementById('form-rename-' + id).submit();
        }
    }

    // 🎯 LOGICA DI CONFERMA CANCELLAZIONE CON CONFIRM
    function deleteChat(id) {
        if (confirm("Sei sicuro di voler eliminare questa conversazione e tutti i suoi messaggi? L'azione è irreversibile.")) {
            document.getElementById('form-delete-' + id).submit();
        }
    }
</script>

</body>
</html>
