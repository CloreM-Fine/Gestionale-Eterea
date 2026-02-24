<?php
// Test caricamento database
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== TEST CARICAMENTO DATABASE ===\n\n";

echo "Step 1: Verifica file env_loader.php...\n";
if (!file_exists(__DIR__ . '/includes/env_loader.php')) {
    die("ERRORE: env_loader.php non trovato\n");
}
echo "OK: env_loader.php esiste\n\n";

echo "Step 2: Include env_loader.php...\n";
try {
    require_once __DIR__ . '/includes/env_loader.php';
    echo "OK: env_loader.php caricato\n\n";
} catch (Throwable $e) {
    die("ERRORE: " . $e->getMessage() . "\n");
}

echo "Step 3: Verifica file .env...\n";
if (!file_exists(__DIR__ . '/.env')) {
    die("ERRORE: .env non trovato\n");
}
echo "OK: .env esiste\n";
echo "Contenuto .env (prime 5 righe):\n";
$lines = file(__DIR__ . '/.env');
for ($i = 0; $i < min(5, count($lines)); $i++) {
    echo "  " . $lines[$i];
}
echo "\n";

echo "Step 4: Carica variabili d'ambiente...\n";
try {
    loadEnv(__DIR__ . '/.env');
    echo "OK: Variabili caricate\n";
    echo "DB_HOST: " . (getenv('DB_HOST') ?: 'NON SETTATO') . "\n";
    echo "DB_NAME: " . (getenv('DB_NAME') ?: 'NON SETTATO') . "\n";
    echo "DB_USER: " . (getenv('DB_USER') ?: 'NON SETTATO') . "\n";
    echo "DB_PASS: " . (strlen(getenv('DB_PASS') ?: '') > 0 ? 'SETTATO' : 'NON SETTATO') . "\n\n";
} catch (Throwable $e) {
    die("ERRORE: " . $e->getMessage() . "\n");
}

echo "Step 5: Include database.php...\n";
try {
    require_once __DIR__ . '/config/database.php';
    echo "OK: database.php caricato\n\n";
} catch (Throwable $e) {
    die("ERRORE: " . $e->getMessage() . "\n");
}

echo "Step 6: Verifica connessione PDO...\n";
if (!isset($pdo)) {
    die("ERRORE: Variabile PDO non definita\n");
}
echo "OK: PDO definito\n";

echo "\n=== TUTTI I TEST PASSATI! ===\n";
