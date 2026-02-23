<?php
/**
 * Eterea Gestionale
 * Configurazione Database e Ambiente
 * 
 * ISTRUZIONI:
 * 1. Copiare .env.example in .env e compilare i valori
 * 2. Assicurarsi che il file .env non sia accessibile pubblicamente
 */

// Se chiamato direttamente, restituisci errore JSON
if (basename($_SERVER['PHP_SELF']) === 'config.php') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accesso diretto non consentito']);
    exit;
}

// Carica funzioni env
require_once __DIR__ . '/env_loader.php';

// Carica file .env
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// -----------------------------------------------------
// DATABASE - Da .env con fallback
// -----------------------------------------------------
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'db4qhf5gnmj3lz'));
define('DB_USER', env('DB_USER', 'ucwurog3xr8tf'));
define('DB_PASS', env('DB_PASS', ''));

// -----------------------------------------------------
// SICUREZZA - Da .env
// -----------------------------------------------------
define('CSRF_SECRET_KEY', env('CSRF_SECRET_KEY', bin2hex(random_bytes(32))));
define('ENCRYPTION_KEY', env('ENCRYPTION_KEY', ''));
define('TASSE_PASSWORD_HASH', env('TASSE_PASSWORD_HASH', ''));

// -----------------------------------------------------
// CONFIGURAZIONE APPLICAZIONE
// -----------------------------------------------------
define('APP_NAME', 'Eterea Gestionale');
define('APP_VERSION', '1.0.0');
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_DEBUG', env('APP_DEBUG', false));

// URL base
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
define('BASE_URL', env('BASE_URL', $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')));

// Path upload
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', BASE_URL . '/assets/uploads/');

// -----------------------------------------------------
// SESSIONE E SICUREZZA
// -----------------------------------------------------
define('SESSION_LIFETIME', env('SESSION_LIFETIME', 7200)); // 2 ore
define('COOKIE_LIFETIME', env('COOKIE_LIFETIME', 2592000)); // 30 giorni
define('MAX_LOGIN_ATTEMPTS', env('MAX_LOGIN_ATTEMPTS', 5));
define('LOGIN_LOCKOUT_MINUTES', env('LOGIN_LOCKOUT_MINUTES', 15));

// -----------------------------------------------------
// UPLOAD
// -----------------------------------------------------
define('MAX_UPLOAD_SIZE_MB', env('MAX_UPLOAD_SIZE_MB', 10));
define('ALLOWED_UPLOAD_TYPES', env('ALLOWED_UPLOAD_TYPES', 'application/pdf,image/jpeg,image/png,image/webp'));

// -----------------------------------------------------
// ID UTENTI FISSI
// -----------------------------------------------------
define('USERS', [
    'ucwurog3xr8tf' => ['nome' => 'Lorenzo Puccetti', 'colore' => '#0891B2'],
    'ukl9ipuolsebn' => ['nome' => 'Daniele Giuliani', 'colore' => '#10B981'],
    'u3ghz4f2lnpkx' => ['nome' => 'Edmir Likaj', 'colore' => '#F59E0B']
]);

// -----------------------------------------------------
// TIPOLOGIE E STATI
// -----------------------------------------------------
define('TIPOLOGIE_PROGETTO', [
    'Sito Web',
    'Grafica',
    'Video',
    'Social Media',
    'Branding',
    'SEO',
    'Fotografia',
    'Altro'
]);

define('STATI_PROGETTO', [
    'da_iniziare' => 'Da Iniziare',
    'in_corso' => 'In Corso',
    'completato' => 'Completato',
    'consegnato' => 'Consegnato',
    'archiviato' => 'Archiviato'
]);

define('STATI_PAGAMENTO', [
    'da_pagare' => 'Da Pagare',
    'da_pagare_acconto' => 'Da Pagare Acconto',
    'acconto_pagato' => 'Acconto Pagato',
    'da_saldare' => 'Da Saldare',
    'cat' => 'CAT',
    'pagamento_completato' => 'Pagamento Completato'
]);

// Colori stati
define('COLORI_STATO_PROGETTO', [
    'da_iniziare' => 'gray',
    'in_corso' => 'cyan',
    'completato' => 'emerald',
    'consegnato' => 'blue',
    'archiviato' => 'slate'
]);

define('COLORI_STATO_PAGAMENTO', [
    'da_pagare' => 'red',
    'da_pagare_acconto' => 'amber',
    'acconto_pagato' => 'yellow',
    'da_saldare' => 'orange',
    'cat' => 'purple',
    'pagamento_completato' => 'green'
]);

define('COLORI_PRIORITA', [
    'bassa' => 'blue',
    'media' => 'yellow',
    'alta' => 'red'
]);

// -----------------------------------------------------
// API ESTERNE
// -----------------------------------------------------
define('OPENAI_API_KEY', env('OPENAI_API_KEY', ''));

// -----------------------------------------------------
// CONNESSIONE PDO
// -----------------------------------------------------
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => APP_DEBUG ? PDO::ERRMODE_EXCEPTION : PDO::ERRMODE_SILENT,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Errore connessione DB: " . $e->getMessage());
    if (APP_DEBUG) {
        throw new Exception("Errore connessione database: " . $e->getMessage());
    }
    // In produzione, non esporre dettagli
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore di sistema']);
    exit;
}
