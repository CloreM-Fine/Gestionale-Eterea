<?php
/**
 * Cattura output pagina Scadenze
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = 'ucwurog3xr8tf';
$_SESSION['user_name'] = 'Lorenzo';
$_SESSION['last_activity'] = time();

echo "<!-- INIZIO PAGINA SCADENZE -->\n";

try {
    require_once __DIR__ . '/scadenze.php';
} catch (Throwable $e) {
    echo "ERRORE: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n<!-- FINE PAGINA SCADENZE -->";
