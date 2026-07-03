<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neuron AI - Dark Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #121212; 
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar { 
            height: 100vh; 
            overflow-y: auto; 
            border-right: 1px solid #2d2d2d; 
            background-color: #1e1e1e; 
        }
        .chat-container { 
            max-width: 1000px; 
            margin: 0 auto; 
            height: 100vh; 
            display: flex; 
            flex-direction: column; 
            background-color: #121212;
        }
        .chat-messages { 
            flex: 1; 
            overflow-y: auto; 
            padding: 30px 20px; 
        }
        .message { 
            margin-bottom: 20px; 
            padding: 14px 18px; 
            border-radius: 12px; 
            max-width: 80%; 
            line-height: 1.5;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .message.user { 
            background-color: #0d6efd; 
            color: #ffffff; 
            margin-left: auto; 
            border-bottom-right-radius: 2px; 
        }
        .message.assistant { 
            background-color: #2a2a2a; 
            color: #e0e0e0; 
            margin-right: auto; 
            border-bottom-left-radius: 2px; 
            border: 1px solid #383838;
        }
        .list-group-item {
            background-color: #1e1e1e;
            color: #bcbcbc;
            border: 1px solid #2d2d2d;
            margin-bottom: 5px;
            border-radius: 6px !important;
        }
        .list-group-item:hover {
            background-color: #2d2d2d;
            color: #ffffff;
        }
        .list-group-item.active {
            background-color: #2b2b2b !important;
            border-color: #0d6efd !important;
            color: #ffffff !important;
            font-weight: 600;
        }
        .input-panel {
            background-color: #1e1e1e;
            border-top: 1px solid #2d2d2d;
        }
        .form-control, .form-control:focus {
            background-color: #2a2a2a;
            border: 1px solid #3d3d3d;
            color: #ffffff;
        }
        .form-control::placeholder {
            color: #777777;
        }
        .btn-custom-dark {
            background-color: #2d2d2d;
            color: #ffffff;
            border: 1px solid #444444;
        }
        .btn-custom-dark:hover {
            background-color: #3d3d3d;
            color: #ffffff;
        }
        /* Custom Scrollbar per l'ambiente Dark */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #121212; }
        ::-webkit-scrollbar-thumb { background: #3a3a3a; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #4a4a4a; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Storico Chat -->
        <div class="col-md-3 sidebar p-3 d-none d-md-block">
            <a href="{{ route('chat.index') }}" class="btn btn-outline-primary w-100 mb-4">+ Nuova Conversazione</a>
            <div class="list-group">
                @foreach($chats as $c)
                    <a href="{{ route('chat.show', $c->id) }}" class="list-group-item list-group-item-action @if(isset($currentChat) && $currentChat->id == $c->id) active @endif">
                        💬 {{ Str::limit($c->title, 25) }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Area di Chat -->
        <div class="col-md-9 p-0">
            <div class="chat-container">
                <div class="chat-messages" id="chatBox">
                    @if(isset($currentChat) && $currentChat->messages->count() > 0)
                        @foreach($currentChat->messages as $msg)
                            <div class="message {{ $msg->role }}">
                                <span class="d-block small text-muted mb-1" style="font-size: 0.75rem;">
                                    {{ $msg->role == 'user' ? 'Tu' : 'Neuron Agent' }}
                                </span>
                                <p class="mb-0">{!! nl2br(e($msg->content)) !!}</p>
                                @if($msg->file_name)
                                    <small class="d-block text-info mt-2">📁 File analizzato: {{ $msg->file_name }}</small>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <div class="text-center text-muted mt-5">
                            <h4 class="fw-light">Pronto ad assisterti.</h4>
                            <p class="small text-secondary">Fai una domanda o allega un PDF per iniziare la sessione agentica.</p>
                        </div>
                    @endif
                </div>

                <!-- Pannello Input Inferiore -->
                <div class="p-3 input-panel">
                    <form id="chatForm" action="{{ isset($currentChat) ? route('chat.send', $currentChat->id) : route('chat.send') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="input-group mb-2">
                            <input type="file" name="file" id="fileInput" class="form-control form-control-sm" accept=".pdf">
                        </div>
                        <div class="input-group">
                            <input type="text" name="message" id="messageInput" class="form-control" placeholder="Invia un messaggio all'agente (es. meteo, news...)" required autocomplete="off">
                            <button class="btn btn-primary" type="submit" id="btnInvia">Invia</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Forza lo scroll automatico all'ultimo messaggio ricevuto
    const chatBox = document.getElementById('chatBox');
    if(chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Gestione dell'invio con blocco interfaccia per caricamento asincrono naturale
    document.getElementById('chatForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('btnInvia');
        const input = document.getElementById('messageInput');
        
        if (input.value.trim() === '' && document.getElementById('fileInput').files.length === 0) {
            e.preventDefault();
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    });
</script>

</body>
</html>
