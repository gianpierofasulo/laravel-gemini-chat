# Chat Neuron

Applicazione Laravel con chat AI, supporto PDF e ricerca web opzionale.
L'applicazione permette di gestire sul prorpio PC/Server una vera a propria chat personalizzata
permettendo di scegliere quale modello usare per le API e se poi usare un'altro servizio 
per dare la possibilità anche di ricerche su Internet.
E' possibile altresì allegare dei pdf per farli analizzare e ricevere riassunti o qualsiasi tipo di analisi.


Stack principale:
1. PHP 8.3+
2. Laravel 13
3. Vite 8
4. MySQL o SQLite
5. Redis opzionale
6. Provider AI configurabili da interfaccia (Google, OpenAI, Anthropic)

## Requisiti

Per esecuzione locale senza Docker:
1. PHP 8.3+
2. Composer 2+
3. Node.js 20+ e npm
4. Database MySQL 8+ oppure SQLite

Per esecuzione containerizzata:
1. Docker
2. Docker Compose

## Installazione rapida in locale

1. Clona il repository e entra nella cartella progetto.
2. Installa dipendenze backend.

	composer install

3. Crea il file ambiente.

	cp .env.example .env

4. Genera la chiave applicativa.

	php artisan key:generate

5. Configura il database in .env.
	Opzione consigliata rapida con SQLite:

	DB_CONNECTION=sqlite

	Poi crea il file database:

	touch database/database.sqlite

6. Esegui le migrazioni.

	php artisan migrate

7. Installa dipendenze frontend e compila gli asset.

	npm install
	npm run build

8. Avvia in sviluppo.

	composer run dev

Questo comando avvia contemporaneamente:
1. Server Laravel
2. Queue listener
3. Log stream
4. Vite dev server

## Installazione con Docker (Laravel Sail)

1. Installa dipendenze PHP localmente per generare la cartella vendor (necessaria anche al build Sail).

	composer install

2. Crea il file ambiente.

	cp .env.example .env

3. Avvia i container.

	./vendor/bin/sail up -d

4. Inizializza l'app dentro il container.

	./vendor/bin/sail artisan key:generate
	./vendor/bin/sail artisan migrate
	./vendor/bin/sail npm install
	./vendor/bin/sail npm run dev

5. Apri l'app su:
	http://localhost

## Configurazione AI

Il progetto usa di default Gemini, ma puoi scegliere provider e modello anche dalla modale impostazioni in chat.

Variabili ambiente utili:
1. GEMINI_API_KEY
2. GEMINI_MODEL
3. TAVILY_API_KEY (opzionale, per ricerca web)
4. OPENAI_API_KEY (opzionale)
5. ANTHROPIC_API_KEY (opzionale)

Nota: se lasci i campi API key vuoti nella UI, l'app usa i valori presenti nel file .env.

## Script utili

1. Setup completo:

	composer run setup

2. Avvio sviluppo:

	composer run dev

3. Test:

	composer run test

## Rotte principali

1. GET /: lista chat / home
2. GET /chat/{id}: dettaglio chat
3. POST /chat/send/{id?}: invio messaggio
4. PUT /chat/{id}: rinomina chat
5. DELETE /chat/{id}: elimina chat
6. POST /chat/config: salva configurazione AI

## Troubleshooting rapido

1. Errore modello AI non trovato:
	cambia modello dalla modale impostazioni oppure aggiorna GEMINI_MODEL.
2. Ricerca web non funziona:
	verifica TAVILY_API_KEY e che la modalità offline sia disabilitata.
3. Migrazioni falliscono con SQLite:
	assicurati che esista il file database/database.sqlite.
