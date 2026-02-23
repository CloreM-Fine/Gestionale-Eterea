# Eterea Gestionale

## Panoramica del Progetto

**Eterea Gestionale** è un sistema di gestione aziendale completo (ERP) sviluppato per uno studio creativo. L'applicazione gestisce progetti, clienti, preventivi, finanze, task, calendario e briefing, con supporto per la distribuzione economica dei profitti tra i membri del team.

L'applicazione è scritta in **PHP vanilla** (senza framework) con un'architettura monolitica tradizionale, utilizzando **MySQL** come database e **Tailwind CSS** per l'interfaccia utente.

---

## Stack Tecnologico

### Backend
- **PHP 8.x** - Linguaggio principale (nessun framework PHP)
- **MySQL/MariaDB** - Database relazionale
- **PDO** - Database abstraction layer
- **Sessioni PHP** - Gestione autenticazione

### Frontend
- **Tailwind CSS** (via CDN) - Framework CSS utility-first
- **Vanilla JavaScript** - Nessun framework JS
- **Font Inter** (Google Fonts) - Tipografia

### Librerie di Terze Parti
- **DOMPDF** (`vendor/dompdf/`) - Generazione PDF
- **HTML5 Parser** (`vendor/masterminds/`) - Parsing HTML5 per DOMPDF
- **PHP Font Lib** (`vendor/phenx/`) - Gestione font per DOMPDF

### Infrastruttura
- **Apache 2.4+** con `mod_rewrite` (Pretty URLs)
- **SiteGround** - Hosting di riferimento
- **HTTPS obbligatorio** (redirect automatico)

---

## Struttura delle Directory

```
/Users/lorenzopuccetti/Lavoro/Eterea Studio/Gestionale/
├── api/                    # Endpoint API REST
│   ├── auth.php           # Login/logout/sessione
│   ├── clienti.php        # CRUD clienti
│   ├── progetti.php       # CRUD progetti
│   ├── task.php           # Gestione task
│   ├── calendario.php     # Eventi calendario
│   ├── finanze.php        # Transazioni economiche
│   ├── preventivi.php     # Gestione preventivi
│   ├── briefing_ai.php    # Salvataggio briefing PDF
│   ├── checklist_controllo.php  # Checklist progetti
│   └── ...
├── includes/              # File di supporto PHP
│   ├── config.php        # Configurazione DB e costanti
│   ├── functions.php     # Funzioni utility globali
│   ├── auth_check.php    # Verifica autenticazione pagine
│   ├── auth.php          # Verifica autenticazione API
│   ├── header.php        # Header HTML comune
│   └── footer.php        # Footer HTML comune
├── config/               # Configurazioni specifiche
│   └── openai.config.php # API key OpenAI
├── assets/               # Asset statici
│   ├── css/             # Fogli di stile (Tailwind)
│   ├── js/              # JavaScript (app.js, components.js)
│   ├── uploads/         # File caricati dagli utenti
│   └── temp/            # File temporanei
├── vendor/              # Librerie di terze parti
│   ├── autoload.php     # Autoloader custom per DOMPDF
│   ├── dompdf/          # Libreria PDF
│   ├── masterminds/     # HTML5 Parser
│   └── phenx/           # Font Lib
├── src/                 # Sorgenti preprocessati
│   └── input.css        # Sorgente Tailwind CSS
├── *.php               # Pagine principali dell'applicazione
├── .htaccess           # Configurazione Apache
└── cron_pulizia.php    # Script cron per pulizia dati
```

---

## Pagine Principali

| File | Descrizione | Linee |
|------|-------------|-------|
| `index.php` | Pagina di login | 294 |
| `dashboard.php` | Dashboard principale con statistiche | 856 |
| `progetti.php` | Lista e gestione progetti | 960 |
| `progetto_dettaglio.php` | Dettaglio singolo progetto | 2,061 |
| `clienti.php` | Gestione anagrafica clienti | 821 |
| `preventivi.php` | Creazione e gestione preventivi | 1,430 |
| `listini.php` | Gestione listini prezzi | 546 |
| `finanze.php` | Gestione economica e wallet | 696 |
| `calendario.php` | Calendario appuntamenti | 862 |
| `briefing_ai.php` | Form per briefing progetti | 544 |
| `impostazioni.php` | Configurazione sistema | 722 |

---

## Architettura Database

### Tabelle Principali

- **utenti** - Utenti del sistema (3 utenti fissi)
- **clienti** - Anagrafica clienti (privati/aziende)
- **progetti** - Progetti con stato, partecipanti, budget
- **task** - Task/attività associate ai progetti
- **appuntamenti** - Eventi calendario
- **preventivi_salvati** - Preventivi generati
- **transazioni_economiche** - Movimenti economici (wallet/cassa)
- **timeline** - Log attività (auto-delete dopo 15 giorni)
- **notifiche** - Sistema notifiche
- **impostazioni** - Configurazioni variabili
- **progetto_documenti** - File allegati ai progetti
- **progetti_checklist** - Checklist di controllo per progetti

### Relazioni Chiave

```
utenti (3 record fissi)
  ├── progetti (via partecipanti JSON)
  ├── task (via assegnato_a)
  ├── appuntamenti (via utente_id)
  └── transazioni_economiche (wallet)

clienti
  └── progetti (1:N via cliente_id)

progetti
  ├── task (1:N)
  ├── appuntamenti (1:N)
  ├── progetto_documenti (1:N)
  └── progetti_checklist (1:1 per tipologia)
```

---

## Convenzioni di Codice

### PHP
- **Namespace**: Nessun namespace (codice procedurale)
- **Funzioni**: `camelCase` per funzioni private, `PascalCase` per funzioni principali
- **Variabili**: `snake_case` per variabili database, `camelCase` per variabili locali
- **Costanti**: `MAIUSCOLE_CON_UNDERSCORE`
- **Commenti**: DocBlock PHPDoc per funzioni principali

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

$action = $_GET['action'] ?? $_POST['action'] ?? '';
switch ($action) {
    case 'create': createEntity(); break;
    case 'update': updateEntity(); break;
    case 'delete': deleteEntity(); break;
    default: jsonResponse(false, null, 'Azione non valida');
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
- **Funzioni JS**: `camelCase`, spesso globali per onclick inline

---

## Funzioni Utility Principali (functions.php)

| Funzione | Descrizione |
|----------|-------------|
| `requireAuth()` | Verifica autenticazione per API |
| `currentUserId()` | Restituisce ID utente corrente |
| `generateId()` | Genera ID univoco formato `u[hash]` |
| `generateEntityId($prefix)` | Genera ID con prefisso (t=task, p=progetto) |
| `e($text)` | Escape HTML per output (XSS protection) |
| `sanitizeInput($input)` | Sanitizza input utente |
| `generateCsrfToken()` | Genera token CSRF |
| `verifyCsrfToken($token)` | Verifica token CSRF |
| `jsonResponse()` | Formatta risposta JSON |
| `logTimeline()` | Scrive nel log attività |
| `calcolaDistribuzione()` | Calcola split economico progetto |
| `eseguiDistribuzione()` | Esegue transazioni wallet |
| `formatCurrency($amount)` | Formatta importo in € |
| `formatDate($date)` | Formatta data in italiano |
| `uploadFile()` | Gestisce upload sicuro file |
| `getDashboardStats()` | Statistiche per dashboard |

---

## Sicurezza

### Implementazioni Attive
- **Password hashing** con `password_hash()` (BCRYPT)
- **CSRF token** su form e API
- **Sessioni sicure**: httponly, secure flag, 30 giorni durata
- **XSS protection** via `htmlspecialchars()` su output
- **SQL Injection prevention** tramite PDO prepared statements
- **File upload validation** (MIME type, dimensione)
- **Header sicurezza**: X-Frame-Options, X-Content-Type-Options
- **HTTPS redirect** forzato
- **Directory protection** via `.htaccess`

### Utenti Predefiniti
```php
USERS = [
    'ugv7adudxudhx' => ['nome' => 'Lorenzo Puccetti', 'colore' => '#0891B2'],
    'ugl368yvg6vsj' => ['nome' => 'Daniele Giuliani', 'colore' => '#10B981'],
    'uhr15idk3qwpg' => ['nome' => 'Edmir Likaj', 'colore' => '#F59E0B']
];
```

---

## Distribuzione Economica (Profit Sharing)

Il sistema implementa una logica di profit sharing automatica per i progetti:

| Partecipanti Attivi | Distribuzione |
|---------------------|---------------|
| 3 utenti | 30% ciascuno + 10% cassa |
| 2 utenti | 40% ciascuno attivo + 10% al terzo + 10% cassa |
| 1 utente | 70% attivo + 10% ciascun altro + 10% cassa |

Le transazioni vengono registrate in `transazioni_economiche` e i saldi utente in `utenti.wallet_saldo`.

---

## API Endpoints

Tutti gli endpoint si trovano in `/api/` e restituiscono JSON.

| Endpoint | Azioni |
|----------|--------|
| `auth.php` | `login`, `logout`, `check` |
| `clienti.php` | `list`, `detail`, `create`, `update`, `delete`, `search` |
| `progetti.php` | `list`, `detail`, `create`, `update`, `delete`, `stats` |
| `task.php` | `create`, `update`, `delete`, `list` |
| `calendario.php` | `events`, `create`, `update`, `delete` |
| `finanze.php` | Transazioni wallet e cassa |
| `preventivi.php` | CRUD preventivi e categorie |
| `briefing_ai.php` | `save_to_project` (upload PDF) |
| `checklist_controllo.php` | CRUD checklist progetti |
| `impostazioni.php` | Gestione impostazioni sistema |
| `notifiche.php` | Gestione notifiche |
| `upload.php` | Upload file generico |

---

## Task Cron

Il file `cron_pulizia.php` gestisce la pulizia automatica dei dati:

```bash
# Esecuzione via CLI
php cron_pulizia.php

# Esecuzione via web (richiede chiave)
https://gestionale.etereastudio.it/cron_pulizia.php?key=ldetimeline2026
```

**Funzioni:**
- Pulizia record `timeline` scaduti (auto_delete_date < CURDATE())

---

## Configurazione

### Database (`includes/config.php`)
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dbqdsx4jwrcdsg');
define('DB_USER', 'ugv7adudxudhx');
define('DB_PASS', '...');
```

### Applicazione
```php
define('APP_NAME', 'Eterea Gestionale');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'https://gestionale.etereastudio.it');
```

### OpenAI (`config/openai.config.php`)
Configurazione API OpenAI per funzionalità AI (Whisper per trascrizioni).

---

## Build e Deploy

### Nessun Processo di Build
L'applicazione **non richiede build**: è PHP puro con Tailwind via CDN.

### Deploy Manuale
1. Upload file via FTP/SFTP su hosting Apache
2. Importare database MySQL
3. Configurare `includes/config.php` con credenziali DB
4. Verificare permessi directory `assets/uploads/` (755)
5. Configurare cron job per `cron_pulizia.php`

### Note Hosting (SiteGround)
- Compatibile con Apache 2.4+
- `.htaccess` preconfigurato per pretty URLs (rimozione `.php`)
- Supporto HTTPS automatico
- Compressione GZIP abilitata
- Cache browser configurata

---

## Testing

Il progetto **non ha test automatizzati**. Il testing avviene manualmente:

1. **Testing funzionale**: verifica flussi utente principali
2. **Testing API**: chiamate manuali agli endpoint
3. **Testing sicurezza**: verifica autenticazione e autorizzazioni
4. **Testing cross-browser**: compatibilità principali browser

---

## Considerazioni per Sviluppo

### Quando Modificare
- **Pagine UI**: Modificare file `.php` root
- **API Backend**: Modificare file in `/api/`
- **Funzioni comuni**: Modificare `includes/functions.php`
- **Configurazione**: Modificare `includes/config.php`
- **Stili CSS**: Aggiornare `assets/css/tailwind.min.css` o usare CDN
- **JavaScript**: Modificare `assets/js/app.js` o `components.js`

### Attenzione
- Mantenere compatibilità con SiteGround (PHP 8.x)
- Non rimuovere protezioni CSRF/XSS esistenti
- Verificare sempre prepared statements per query dinamiche
- Testare upload file su ambiente di staging
- Non esporre credenziali DB in repository pubblici

---

## Dipendenze Esterne (CDN)

- **Tailwind CSS**: `https://cdn.tailwindcss.com`
- **Font Inter**: Google Fonts
- **Icone**: SVG inline (nessuna libreria icone esterna)

---

## Contatti e Riferimenti

- **Progetto**: Eterea Gestionale
- **URL**: https://gestionale.etereastudio.it
- **Hosting**: SiteGround
- **Linguaggio**: Italiano (tutti i testi e commenti)
