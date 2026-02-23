<?php
/**
 * Eterea Gestionale
 * Configurazione Database
 * 
 * ISTRUZIONI:
 * 1. Modificare i valori qui sotto con quelli corretti per SiteGround
 * 2. Assicurarsi che il file non sia accessibile pubblicamente (.htaccess protegge questa cartella)
 */

// Se chiamato direttamente, restituisci errore JSON
if (basename($_SERVER['PHP_SELF']) === 'config.php') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accesso diretto non consentito']);
    exit;
}

// Impostazioni database - CONFIGURATO
define('DB_HOST', 'localhost');
define('DB_NAME', 'db4qhf5gnmj3lz');
define('DB_USER', 'ucwurog3xr8tf');      // Utente MySQL (Lorenzo)
define('DB_PASS', 'Lorenzo2026!');       // Password MySQL

// Impostazioni applicazione
define('APP_NAME', 'Eterea Gestionale');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'https://gestionale.etereastudio.it');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', BASE_URL . '/assets/uploads/');

// ID utenti fissi
define('USERS', [
    'ucwurog3xr8tf' => ['nome' => 'Lorenzo Puccetti', 'colore' => '#0891B2'],
    'ukl9ipuolsebn' => ['nome' => 'Daniele Giuliani', 'colore' => '#10B981'],
    'u3ghz4f2lnpkx' => ['nome' => 'Edmir Likaj', 'colore' => '#F59E0B']
]);

// Tipologie progetto
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

// Stati progetto
define('STATI_PROGETTO', [
    'da_iniziare' => 'Da Iniziare',
    'in_corso' => 'In Corso',
    'completato' => 'Completato',
    'consegnato' => 'Consegnato',
    'archiviato' => 'Archiviato'
]);

// Stati pagamento
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

// Colori stati pagamento
define('COLORI_STATO_PAGAMENTO', [
    'da_pagare' => 'red',
    'da_pagare_acconto' => 'amber',
    'acconto_pagato' => 'yellow',
    'da_saldare' => 'orange',
    'cat' => 'purple',
    'pagamento_completato' => 'green'
]);

// Colori priorità
define('COLORI_PRIORITA', [
    'bassa' => 'blue',
    'media' => 'yellow',
    'alta' => 'red'
]);

// Configurazione OpenAI
$openaiConfig = require __DIR__ . '/../config/openai.config.php';
define('OPENAI_API_KEY', $openaiConfig['api_key'] ?? '');

// Connessione PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Errore connessione DB: " . $e->getMessage());
    // Non fare die(), lascia che l'errore venga gestito dall'API
    throw new Exception("Errore connessione database: " . $e->getMessage());
}
