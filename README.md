```
# Neuron AI & Laravel PDF Chat Agent

Benvenuti in **Laravel Gemini Chat**, un'applicazione enterprise-ready progettata per l'orchestrazione agentica, la chat contestuale basata su documenti (RAG) e la gestione dinamica dei Large Language Models (LLM). 

Il progetto sfrutta la potenza del framework **Neuron AI** per implementare un sistema di **Routing nativo (Waterfall)** capace di garantire l'alta affidabilità (High Availability) delle chiamate AI, integrando dinamicamente **Google Gemini** e un provider custom ottimizzato per **Groq**.

---

## 🛠 Tech Stack & Versioni

L'applicazione è containerizzata e configurata per girare in ambienti di sviluppo isolati tramite le seguenti tecnologie standard:

* **Framework PHP:** Laravel 13.18.0
* **Runtime Engine:** PHP 8.5.7 (CLI / FPM)
* **Ambiente Docker:** Laravel Sail (Docker Compose orchestration)
* **Framework AI:** Neuron AI Core
* **Parser Documentale:** Smalot PDF Parser

---

## Architettura di IA: Router & Failover Nativo

La caratteristica core di questo repository è la gestione resiliente dei modelli linguistici attraverso il **RouterProvider** di Neuron AI. 

L'applicazione non si affida a chiamate API statiche. Al contrario, implementa una pipeline di routing **Waterfall (a cascata)** all'interno dell'agente dedicato `PdfChatAgent.php`:

1.  **Primary Provider:** Guidato dalle preferenze dell'utente salvate a runtime tramite il pannello di configurazione UI (es. Google Gemini o Groq con modelli personalizzati come `qwen/qwen3-32b`).
2.  **Backup Provider:** Configurato a livello di infrastruttura (variabili `.env`). Se il provider primario fallisce per timeout, superamento dei rate-limit o credenziali errate (HTTP 4xx/5xx), il Router intercetta l'eccezione a runtime ed esegue uno switch istantaneo e trasparente sul provider secondario, iniettando la risposta nella chat e notificando l'avvenuto failover all'utente.

Inoltre, per rimuovere del tutto le dipendenze da OpenAI, è stato sviluppato un **Custom Provider (`GroqProvider`)** che estende le interfacce di Neuron per dialogare direttamente con gli endpoint ultra-veloci di Groq (`api.groq.com`), allineando la mappatura dei messaggi e i tipi nativi basati sull'Enum `MessageRole`.

---

##  Guida all'Installazione in Locale (Passo-Passo)

Segui questi passaggi per clonare, configurare e avviare l'applicazione nel tuo ambiente di sviluppo locale utilizzando Docker.

### 1. Clonare il Repository
Apri il terminale e clona il progetto posizionandoti nella cartella dei tuoi progetti:
```bash
git clone [https://github.com/gianpierofasulo/laravel-gemini-chat.git](https://github.com/gianpierofasulo/laravel-gemini-chat.git)
cd laravel-gemini-chat

```

### 2. Installare le Dipendenze di Composer (Bootstrapping)

Poiché PHP e Composer gireranno all'interno di Docker, usiamo un container temporaneo per installare le dipendenze iniziali necessarie a mappare Laravel Sail:

Bash

```
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php85-composer:latest \
    composer install --ignore-platform-reqs

```

### 3. Configurare il File d'Ambiente (`.env`)

Copia il file di configurazione d'esempio:

Bash

```
cp .env.example .env

```

Apri il file `.env` appena creato e configura le tue API Key essenziali. L'architettura è ottimizzata per escludere OpenAI e focalizzarsi su Gemini e Groq:

Snippet di codice

```
# Configurazione Primaria di Default
GEMINI_API_KEY=IL_TUO_TOKEN_GEMINI_REALE
GEMINI_MODEL="gemini-3.1-flash-lite"

# Configurazione di Fallback (Backup trasparente per il Router)
GROQ_API_KEY=IL_TUO_TOKEN_GROQ_REALE
GROQ_MODEL="groq/compound"

# Tooling Opzionale (Web Search dell'Agente)
TAVILY_API_KEY=LA_TUA_TAVILY_KEY

```

### 4. Avviare lo Stack con Laravel Sail

Avvia i container Docker in modalità background (detached):

Bash

```
./vendor/bin/sail up -d

```

### 5. Generare la Application Key e configurare il database

Esegui la generazione della chiave di cifratura di Laravel e lancia le migrazioni per generare le tabelle SQL delle Chat e dei Messaggi all'interno del container:

Bash

```
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate

```

### 6. Ottimizzare e Allineare l'Autoloading delle Classi AI

Dato che l'applicazione introduce componenti architetturali custom (il `GroqProvider` in `app/Neuron/Providers`), è fondamentale rigenerare la mappa delle classi e ripulire le cache di runtime:

Bash

```
./vendor/bin/sail composer dump-autoload
./vendor/bin/sail artisan cache:clear

```

L'applicazione è ora attiva e raggiungibile nel tuo browser all'indirizzo standard: `http://localhost:8081` (o la porta configurata nel tuo `APP_PORT`).

## ⚙️ Modifica Dinamica dei Modelli da UI

L'interfaccia grafica mette a disposizione un pannello di configurazione avanzata (icona `⚙️`).

- Il campo **AI Provider** mappa i driver strutturati (`google` o `groq`).
- Il campo **Modello IA** è un input di testo libero: questo consente di testare istantaneamente qualsiasi modello supportato dai server remoti (es. `llama3-70b-8192`, `mixtral-8x7b-32768`, o l'ecosistema `Qwen`).

In caso di stringhe errate digitate dall'utente nel form, l'applicazione non andrà in crash: il `GroqProvider` intercetterà il fallimento HTTP a runtime e passerà la richiesta a **Gemini**, preservando la continuità della sessione dell'utente.

## 🐳 Comandi Utili per il Mantenimento

- **Arrestare lo stack Docker:** `./vendor/bin/sail down`
- **Svuotare le cache in caso di modifiche strutturali all'Agente:** `./vendor/bin/sail artisan cache:clear`
- **Ispezionare i log di errore dell'Agente AI:** `./vendor/bin/sail artisan log:tail`  


