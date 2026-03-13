# Eterea Gestionale

## Panoramica del Progetto

**Eterea Gestionale** è un sistema di gestione aziendale completo (ERP) sviluppato per uno studio creativo. L'applicazione gestisce progetti, clienti, preventivi, finanze, task, calendario, briefing, tasse e contenuti clienti, con supporto per la distribuzione economica dei profitti tra i membri del team.

L'applicazione è scritta in **PHP vanilla 8.x** (senza framework) con un'architettura monolitica tradizionale, utilizzando **MySQL/MariaDB** come database e **Tailwind CSS** per l'interfaccia utente.

---

## Stack Tecnologico

### Backend
- **PHP 8.x** - Linguaggio principale (nessun framework PHP)
- **MySQL/MariaDB** - Database relazionale
- **PDO** - Database abstraction layer con prepared statements
- **Sessioni PHP** - Gestione autenticazione

### Frontend
- **Tailwind CSS** (via CDN) - Framework CSS utility-first
- **Vanilla JavaScript** - Nessun framework JS (namespace globale `LDEApp`)
- **Font Inter** (Google Fonts) - Tipografia
- **SVG inline** - Icone (nessuna libreria esterna)

### Librerie di Terze Parti
- **DOMPDF** (`vendor/dompdf/`) - Generazione PDF
- **HTML5 Parser** (`vendor/masterminds/`) - Parsing HTML5 per DOMPDF
- **PHP Font Lib** (`vendor/phenx/`) - Gestione font per DOMPDF

### Infrastruttura
- **Apache 2.4+** con `mod_rewrite` (Pretty URLs)
- **SiteGround** - Hosting di produzione
- **HTTPS obbligatorio** (redirect automatico)
- **GitHub Actions** - Deploy automatico via FTP

---

## Struttura delle Directory

```
/Users/lorenzopuccetti/Lavoro/Eterea Studio/Gestionale/
├── api/                    # Endpoint API REST (20 file)
│   ├── auth.php           # Login/logout/sessione
│   ├── clienti.php        # CRUD clienti
│   ├── progetti.php       # CRUD progetti
│   ├── task.php           # Gestione task e timer
│   ├── calendario.php     # Eventi calendario
│   ├── finanze.php        # Transazioni economiche
│   ├── preventivi.php     # Gestione preventivi
│   ├── briefing_ai.php    # Salvataggio briefing PDF
│   ├── checklist_controllo.php  # Checklist progetti
│   ├── impostazioni.php   # Gestione impostazioni
│   ├── notifiche.php      # Sistema notifiche
│   ├── blog_clienti.php   # Gestione contenuti clienti
│   ├── tasse.php          # Calcolo tasse e codici ATECO
│   ├── report.php         # Reportistica
│   ├── scadenze.php       # Gestione scadenze
│   └── ...
├── includes/              # File di supporto PHP
│   ├── config.php        # Configurazione DB e costanti
│   ├── functions.php     # Funzioni utility globali (539 righe)
│   ├── functions_security.php  # Funzioni sicurezza avanzate (532 righe)
│   ├── env_loader.php    # Loader file .env
│   ├── auth_check.php    # Verifica autenticazione pagine
│   ├── auth.php          # Verifica autenticazione API
│   ├── header.php        # Header HTML comune
│   └── footer.php        # Footer HTML comune
├── config/               # Configurazioni specifiche e SQL
│   ├── openai.config.php # API key OpenAI
│   ├── database.php      # Utility database
│   ├── setup_tasse.sql   # Setup tabelle tasse
│   └── create_codici_ateco.sql  # Codici ATECO
├── assets/               # Asset statici
│   ├── css/             # Fogli di stile (Tailwind)
│   ├── js/              # JavaScript (app.js, components.js)
│   ├── uploads/         # File caricati dagli utenti
│   │   ├── avatars/     # Avatar utenti
│   │   ├── clienti/     # File clienti
│   │   ├── clienti_contenuti/  # Contenuti blog clienti
│   │   ├── progetti/    # File progetti (organizzati per ID progetto)
│   │   ├── task_files/  # Allegati task
│   │   └── task_images/ # Immagini task
│   ├── temp/            # File temporanei (preventivi HTML)
│   └── favicons/        # Favicon del sito
├── vendor/              # Librerie di terze parti
│   ├── autoload.php     # Autoloader custom per DOMPDF
│   ├── dompdf/          # Libreria PDF
│   ├── masterminds/     # HTML5 Parser
│   └── phenx/           # Font Lib e SVG Lib
├── src/                 # Sorgenti preprocessati
│   └── input.css        # Sorgente Tailwind CSS
├── logs/                # Log di sicurezza
├── _unused_files/       # File non più utilizzati (backup)
├── *.php               # Pagine principali dell'applicazione
├── .htaccess           # Configurazione Apache (security, rewrite, gzip)
├── .env                # Configurazione ambiente (NON committare!)
├── .env.example        # Template configurazione ambiente
├── cron_pulizia.php    # Script cron per pulizia dati
└── database_blog_clienti.sql  # Schema database blog clienti
```

---

## Pagine Principali

| File | Descrizione | Linee | Funzione Principale |
|------|-------------|-------|---------------------|
| `index.php` | Pagina di login | 294 | Autenticazione utenti |
| `dashboard.php` | Dashboard principale | 1,018 | Statistiche, task, timeline |
| `progetti.php` | Lista progetti | 2,054 | Gestione progetti |
| `progetto_dettaglio.php` | Dettaglio progetto | 3,253 | Task, documenti, checklist |
| `clienti.php` | Gestione clienti | 1,128 | Anagrafica clienti |
| `preventivi.php` | Preventivi | 2,558 | Creazione PDF preventivi |
| `listini.php` | Listini prezzi | ~550 | Gestione listini |
| `finanze.php` | Gestione economica | 642 | Wallet, cassa, transazioni |
| `calendario.php` | Calendario | 1,076 | Appuntamenti, scadenze |
| `briefing_ai.php` | Form briefing | 545 | Raccolta briefing progetti |
| `impostazioni.php` | Configurazione | 2,760 | Impostazioni sistema |
| `scadenze.php` | Scadenze | 781 | Gestione scadenze fiscali |
| `tasse.php` | Calcolo tasse | 723 | Calcolo con codici ATECO |
| `blog_clienti.php` | Blog clienti | 864 | Contenuti caricati da clienti |
| `report.php` | Reportistica | 775 | Report progetti/finanze |
| `upload_cliente.php` | Upload pubblico | 750 | Form upload per clienti |

---

## Architettura Database

### Tabelle Principali

| Tabella | Descrizione | Note |
|---------|-------------|------|
| **utenti** | Utenti del sistema (3 record fissi) | ID codificati in config |
| **clienti** | Anagrafica clienti (privati/aziende) | JSON per dati extra |
| **progetti** | Progetti con stato, partecipanti, budget | Partecipanti in JSON |
| **task** | Task/attività associate ai progetti | Con timer integrato |
| **task_timer** | Time tracking per task | Avvio/pausa/ripresa/stop |
| **task_commenti** | Commenti sulle task | - |
| **appuntamenti** | Eventi calendario | Linkati a progetti/task |
| **preventivi_salvati** | Preventivi generati | HTML + metadata |
| **preventivi_categorie** | Categorie servizi preventivi | - |
| **preventivi_voci** | Voci singole preventivi | - |
| **transazioni_economiche** | Movimenti economici (wallet/cassa) | - |
| **timeline** | Log attività | Auto-delete dopo 15 giorni |
| **notifiche** | Sistema notifiche | Per tutti gli utenti |
| **impostazioni** | Configurazioni variabili | Key-value store |
| **progetto_documenti** | File allegati ai progetti | - |
| **progetti_checklist** | Checklist di controllo | Per tipologia progetto |
| **codici_ateco** | Codici ATECO per calcolo tasse | Con coefficienti |
| **cronologia_calcoli_tasse** | Storico calcoli tasse | Per utente |
| **cliente_contenuti** | Contenuti caricati dai clienti | Token-based access |
| **cliente_link** | Link generati per upload clienti | Con scadenza |

### Sistema Timer Task

Il sistema include un timer integrato per tracciare il tempo impiegato su ogni task:

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `task_id` | VARCHAR(20) | Riferimento alla task |
| `utente_id` | VARCHAR(20) | Utente che traccia il tempo |
| `started_at` | TIMESTAMP | Avvio timer |
| `paused_at` | TIMESTAMP | Pausa |
| `resumed_at` | TIMESTAMP | Ripresa |
| `stopped_at` | TIMESTAMP | Stop finale |
| `total_seconds` | INT | Secondi totali accumulati |
| `is_running` | TINYINT | Timer attivo |
| `is_paused` | TINYINT | Timer in pausa |

**API Endpoints Timer:**
- `timer_start` - Avvia il timer
- `timer_pause` - Mette in pausa
- `timer_resume` - Riprende dalla pausa
- `timer_stop` - Ferma e calcola costo
- `timer_reset` - Resetta il timer
- `get_timer` - Ottiene stato timer

### Relazioni Chiave

```
utenti (3 record fissi)
  ├── progetti (via partecipanti JSON)
  ├── task (via assegnato_a)
  ├── appuntamenti (via utente_id)
  ├── transazioni_economiche (wallet)
  └── cronologia_calcoli_tasse

clienti
  ├── progetti (1:N via cliente_id)
  ├── cliente_contenuti (1:N)
  └── cliente_link (1:N)

progetti
  ├── task (1:N)
  ├── appuntamenti (1:N)
  ├── progetto_documenti (1:N)
  ├── progetti_checklist (1:1 per tipologia)
  └── preventivi_salvati (1:N)
```

---

## Convenzioni di Codice

### PHP
- **Namespace**: Nessun namespace (codice procedurale)
- **Funzioni**: `camelCase` per funzioni (es. `isLoggedIn()`, `generateId()`)
- **Variabili**: `snake_case` per variabili database, `camelCase` per variabili locali
- **Costanti**: `MAIUSCOLE_CON_UNDERSCORE` (es. `DB_HOST`, `USERS`)
- **Commenti**: DocBlock PHPDoc per funzioni principali
- **Classi**: `PascalCase` (solo per librerie esterne)

### Pattern Comuni

#### Connessione Database
```php
require_once __DIR__ . '/includes/functions.php';  // Include anche config.php
global $pdo;  // Connessione PDO disponibile
```

#### Verifica Autenticazione (Pagine)
```php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';  // Redirect a login se non autenticato
```

#### API Endpoint
```php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'list') listItems();
        break;
    case 'POST':
        // Verifica CSRF per operazioni state-changing
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($csrfToken)) {
            jsonResponse(false, null, 'Token CSRF non valido');
        }
        if ($action === 'create') createItem();
        break;
}
```

#### Risposta JSON Standard
```php
function jsonResponse(bool $success, $data = null, string $message = ''): void {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}
```

### Frontend
- **Classi Tailwind**: Utility-first, nessun CSS custom se non necessario
- **ID Elementi**: `camelCase` (es. `progettoModal`, `searchInput`)
- **Funzioni JS**: `camelCase`, globali in namespace `LDEApp`
- **Event Listeners**: Arrow functions, delegazione eventi

---

## Funzioni Utility Principali

### Autenticazione e Sessione (functions.php)

| Funzione | Descrizione |
|----------|-------------|
| `isLoggedIn()` | Verifica se utente autenticato |
| `isAdmin()` | Verifica se utente è admin (Lorenzo) |
| `requireAuth()` | Termina con 401 se non autenticato (API) |
| `currentUserId()` | Restituisce ID utente corrente |

### Generazione ID

| Funzione | Descrizione | Esempio |
|----------|-------------|---------|
| `generateId()` | ID univoco utente | `u[a-z0-9]{13}` |
| `generateEntityId($prefix)` | ID con prefisso | `t=task, p=progetto, a=appuntamento` |

### Sicurezza

| Funzione | Descrizione |
|----------|-------------|
| `e($text)` | Escape HTML per output (XSS protection) |
| `sanitizeInput($input)` | Sanitizza input utente |
| `generateCsrfToken()` | Genera token CSRF |
| `verifyCsrfToken($token)` | Verifica token CSRF |
| `jsonResponse()` | Formatta risposta JSON standard |

### Logging e Timeline

| Funzione | Descrizione |
|----------|-------------|
| `logTimeline()` | Scrive nel log attività (auto-delete 15 giorni) |
| `pulisciTimeline()` | Pulisce record vecchi |

### Formattazione

| Funzione | Descrizione |
|----------|-------------|
| `formatCurrency($amount)` | Formatta importo in € (es. "€ 1.234,56") |
| `formatDate($date)` | Formatta data in italiano (d/m/Y) |
| `formatDateTime($datetime)` | Formatta datetime con timezone Europe/Rome |

### Utenti

| Funzione | Descrizione |
|----------|-------------|
| `getUserName($userId)` | Nome utente da ID |
| `getUserColor($userId)` | Colore associato all'utente |
| `getDashboardStats($utenteId)` | Statistiche per dashboard |

### Upload

| Funzione | Descrizione |
|----------|-------------|
| `uploadFileSecure()` | Upload file hardenizzato (verifica MIME, estensione) |

### Utility

| Funzione | Descrizione |
|----------|-------------|
| `checkScadenza($scadenza, $giorni)` | Verifica se data scaduta/in scadenza |
| `creaAppuntamentoTask()` | Crea appuntamento automatico da task |
| `creaNotifica()` | Crea notifica nel database |

---

## Funzioni di Sicurezza Avanzate (functions_security.php)

### Rate Limiting

| Funzione | Descrizione |
|----------|-------------|
| `checkRateLimit($action, $max, $window)` | Verifica rate limiting file-based |
| `blockIp($ip, $minutes)` | Blocca IP temporaneamente |
| `isIpBlocked($ip)` | Verifica se IP è bloccato |
| `cleanRateLimitFiles()` | Pulizia file vecchi |

### CSRF Avanzato

| Funzione | Descrizione |
|----------|-------------|
| `generateCsrfTokenSecure()` | Genera token sicuro |
| `verifyCsrfTokenSecure($token, $regenerate)` | Verifica con opzione rigenerazione |
| `requireCsrfToken()` | Middleware per API (termina con 403) |

### Upload Security

| Costante | Valore |
|----------|--------|
| `DANGEROUS_EXTENSIONS` | Array estensioni pericolose bloccate |

| Funzione | Descrizione |
|----------|-------------|
| `isSafeFilename($filename)` | Verifica sicurezza nome file |
| `uploadFileSecure($file, $dir, $types, $maxSize, $randomize)` | Upload con validazione MIME |
| `getMimeFromExtension($ext)` | Mapping estensione → MIME |

### Logging Sicuro

| Funzione | Descrizione |
|----------|-------------|
| `securityLog($event, $context)` | Log su file JSON con mascheramento dati sensibili |
| `sanitizeForLog($data)` | Sanitizza dati per logging |

### Validazione Input

| Funzione | Tipi supportati |
|----------|-----------------|
| `validateInput($input, $type, $options)` | string, int, float, email, url, uuid, alphanum, id |
| `validateRequired($data, $required)` | Verifica campi obbligatori |

---

## Distribuzione Economica (Profit Sharing)

Il sistema implementa una logica automatica di profit sharing per i progetti:

| Partecipanti Attivi | Distribuzione |
|---------------------|---------------|
| 3 utenti | 30% ciascuno + 10% cassa |
| 2 utenti | 40% ciascuno attivo + 10% al terzo + 10% cassa |
| 1 utente | 70% attivo + 10% ciascun altro + 10% cassa |

**Funzioni:**
- `calcolaDistribuzione($totale, $partecipantiIds, $includiCassa, $includiPassivo)`
- `eseguiDistribuzione($progettoId, $totale, $partecipantiIds, ...)`

Le transazioni vengono registrate in `transazioni_economiche` e i saldi utente in `utenti.wallet_saldo`.

---

## API Endpoints

Tutti gli endpoint si trovano in `/api/` e restituiscono JSON.

| Endpoint | Azioni |
|----------|--------|
| `auth.php` | `login`, `logout`, `check` |
| `clienti.php` | `list`, `detail`, `create`, `update`, `delete`, `search` |
| `progetti.php` | `list`, `detail`, `create`, `update`, `delete`, `stats`, `change_status` |
| `task.php` | `create`, `update`, `delete`, `list`, `detail`, `change_status`, `timer_*` |
| `calendario.php` | `events`, `create`, `update`, `delete` |
| `finanze.php` | Transazioni wallet e cassa |
| `preventivi.php` | CRUD preventivi e categorie |
| `briefing_ai.php` | `save_to_project` (upload PDF) |
| `checklist_controllo.php` | CRUD checklist progetti |
| `impostazioni.php` | Gestione impostazioni sistema |
| `notifiche.php` | Gestione notifiche |
| `upload.php` | Upload file generico |
| `blog_clienti.php` | Gestione contenuti clienti |
| `tasse.php` | Calcolo tasse, codici ATECO |
| `report.php` | Generazione report |
| `scadenze.php` | Gestione scadenze |
| `timeline.php` | Log attività |
| `contabilita.php` | Dati contabili |

---

## Configurazione

### File .env

Copiare `.env.example` in `.env` e configurare:

```bash
# Database
DB_HOST=localhost
DB_NAME=nome_database
DB_USER=username
DB_PASS=password

# Sicurezza
TASSE_PASSWORD_HASH=$2y$10$...  # hash bcrypt
CSRF_SECRET_KEY=random_32_char_string
ENCRYPTION_KEY=base64_32_char_key

# API Esterne
OPENAI_API_KEY=sk-...

# Applicazione
APP_ENV=production|development
BASE_URL=https://gestionale.etereastudio.it
APP_DEBUG=false

# Sessione
SESSION_LIFETIME=7200
COOKIE_LIFETIME=2592000
MAX_LOGIN_ATTEMPTS=20
LOGIN_LOCKOUT_MINUTES=5

# Upload
MAX_UPLOAD_SIZE_MB=10
ALLOWED_UPLOAD_TYPES=application/pdf,image/jpeg,image/png,image/webp,image/svg+xml
```

### Database (includes/config.php)

```php
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'db4qhf5gnmj3lz'));
define('DB_USER', env('DB_USER', 'ucwurog3xr8tf'));
define('DB_PASS', env('DB_PASS', ''));
```

### Utenti Predefiniti (includes/config.php)

```php
USERS = [
    'ucwurog3xr8tf' => ['nome' => 'Lorenzo Puccetti', 'colore' => '#0891B2'],
    'ukl9ipuolsebn' => ['nome' => 'Daniele Giuliani', 'colore' => '#10B981'],
    'u3ghz4f2lnpkx' => ['nome' => 'Edmir Likaj', 'colore' => '#F59E0B']
];
```

### Tipologie e Stati

```php
TIPOLOGIE_PROGETTO = ['Sito Web', 'Grafica', 'Video', 'Social Media', 'Branding', 'SEO', 'Fotografia', 'Altro']

STATI_PROGETTO = [
    'da_iniziare' => 'Da Iniziare',
    'in_corso' => 'In Corso', 
    'completato' => 'Completato',
    'consegnato' => 'Consegnato',
    'archiviato' => 'Archiviato'
]

STATI_PAGAMENTO = [
    'da_pagare' => 'Da Pagare',
    'da_pagare_acconto' => 'Da Pagare Acconto',
    'acconto_pagato' => 'Acconto Pagato',
    'da_saldare' => 'Da Saldare',
    'cat' => 'CAT',
    'pagamento_completato' => 'Pagamento Completato'
]
```

---

## Build e Deploy

### Nessun Processo di Build

L'applicazione **non richiede build**: è PHP puro con Tailwind via CDN.

### Deploy Automatico (GitHub Actions)

Il progetto è configurato per deploy automatico su SiteGround:

**Trigger:** Push sul branch `main`

**Workflow** (`.github/workflows/deploy.yml`):
```yaml
- Usa SamKirkland/FTP-Deploy-Action@v4.3.5
- Protocollo: FTPS
- Porta: 21
- Timeout: 600000ms (10 min)
```

**Secrets GitHub richiesti:**
- `FTP_SERVER` - Server FTP SiteGround
- `FTP_USERNAME` - Username FTP
- `FTP_PASSWORD` - Password FTP

**File esclusi dal deploy:**
```
**/.git*/**
**/.git*
**/.github/**
**/README.md
**/.gitignore
**/*.sql
**/aggiorna_password.php
**/.env
**/.env.example
**/assets/temp/**
**/.DS_Store
```

### Deploy Manuale

Se necessario, deploy manuale via FTP/SFTP:

1. Upload file su hosting Apache
2. Importare database MySQL (se nuova installazione)
3. Configurare `.env` con credenziali DB
4. Verificare permessi directory:
   - `assets/uploads/` (755)
   - `logs/` (750)
5. Configurare cron job per `cron_pulizia.php`

### Task Cron

Il file `cron_pulizia.php` gestisce la pulizia automatica:

```bash
# Via CLI (consigliato)
php /path/to/cron_pulizia.php

# Via web (richiede chiave)
https://gestionale.etereastudio.it/cron_pulizia.php?key=ldetimeline2026
```

**Configurazione cron (SiteGround/cPanel):**
```bash
0 2 * * * /usr/bin/php /home/username/public_html/cron_pulizia.php >/dev/null 2>&1
```

---

## Sicurezza

### Implementazioni Attive

| Feature | Implementazione |
|---------|-----------------|
| Password hashing | `password_hash()` (BCRYPT) |
| CSRF token | Token in sessione, verificato su POST |
| Sessioni sicure | httponly, secure flag, durata configurabile |
| XSS protection | `htmlspecialchars()` su output |
| SQL Injection | PDO prepared statements |
| File upload | Validazione MIME, estensione, dimensione |
| Rate limiting | File-based (20 tentativi / 5 min) |
| IP blocking | Blocco temporaneo dopo tentativi falliti |
| Header sicurezza | X-Frame-Options, X-Content-Type-Options, CSP, HSTS |
| HTTPS redirect | Forzato via .htaccess |
| Directory protection | Via .htaccess (includes/, logs/) |
| File protection | Blocco file .env, .sql, .log, ecc. |

### Header di Sicurezza (.htaccess)

```apache
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'; ...
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

### Logging Sicurezza

I log di sicurezza vengono salvati in `logs/security-YYYY-MM-DD.log` in formato JSON.

---

## Testing

Il progetto **non ha test automatizzati**. Il testing avviene manualmente:

1. **Testing funzionale**: Verifica flussi utente principali
2. **Testing API**: Chiamate manuali agli endpoint
3. **Testing sicurezza**: Verifica autenticazione e autorizzazioni
4. **Testing cross-browser**: Compatibilità principali browser

---

## Dipendenze Esterne (CDN)

| Libreria | URL |
|----------|-----|
| Tailwind CSS | `https://cdn.tailwindcss.com` |
| Font Inter | Google Fonts |
| FontAwesome | `https://cdnjs.cloudflare.com` (alcune pagine) |

---

## Note Hosting (SiteGround)

- Compatibile con Apache 2.4+
- `.htaccess` preconfigurato per pretty URLs (rimozione `.php`)
- Supporto HTTPS automatico
- Compressione GZIP abilitata
- Cache browser configurata
- PHP 8.x richiesto

---

## Considerazioni per Sviluppo

### Quando Modificare

| Componente | File/Direttoria |
|------------|-----------------|
| Pagine UI | File `.php` root |
| API Backend | File in `/api/` |
| Funzioni comuni | `includes/functions.php` |
| Funzioni sicurezza | `includes/functions_security.php` |
| Configurazione | `includes/config.php`, `.env` |
| Stili CSS | CDN Tailwind (inline o `assets/css/`) |
| JavaScript | `assets/js/app.js`, `components.js` |

### Attenzione

- Mantenere compatibilità con PHP 8.x
- Non rimuovere protezioni CSRF/XSS esistenti
- Verificare sempre prepared statements per query dinamiche
- Testare upload file su ambiente di staging
- Non esporre credenziali DB in repository pubblici
- Aggiornare `.env.example` se si aggiungono nuove variabili

---

## Contatti e Riferimenti

- **Progetto**: Eterea Gestionale
- **URL**: https://gestionale.etereastudio.it
- **Hosting**: SiteGround
- **Linguaggio**: Italiano (tutti i testi e commenti)
- **Repository**: GitHub (private)
