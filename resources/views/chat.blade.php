<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neuron AI Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/chat.css') }}">
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
                            <option value="groq" {{ session('ai_provider') == 'groq' ? 'selected' : '' }}>Groq</option>
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

                    <!-- Box Offline / Internet Toggle -->
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
<script src="{{ asset('js/chat.js') }}"></script>

</body>
</html>
