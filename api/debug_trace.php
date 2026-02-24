<?php
/**
 * Debug Trace - Identifica il punto esatto di fallimento
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];
$trace = [];

// Step 1: Verifica file esistono
$trace[] = "Step 1: Verifica file";
$files = [
    __DIR__ . '/../config/database.php',
    __DIR__ . '/../includes/functions.php',
    __DIR__ . '/../includes/config.php'
];
foreach ($files as $f) {
    $exists = file_exists($f);
    $trace[] = "  - $f: " . ($exists ? 'OK' : 'MANCANTE');
    if (!$exists) {
        $errors[] = "File mancante: $f";
    }
}

// Step 2: Prova a includere database.php
$trace[] = "Step 2: Include database.php";
try {
    ob_start();
    require_once __DIR__ . '/../config/database.php';
    $output = ob_get_clean();
    if ($output) {
        $errors[] = "Output inatteso da database.php: $output";
    }
    $trace[] = "  - database.php: OK";
    $trace[] = "  - PDO definito: " . (isset($pdo) ? 'SI' : 'NO');
} catch (Throwable $e) {
    ob_end_clean();
    $errors[] = "Errore database.php: " . $e->getMessage();
    $trace[] = "  - ERRORE: " . $e->getMessage();
}

// Step 3: Prova a includere functions.php
$trace[] = "Step 3: Include functions.php";
try {
    ob_start();
    require_once __DIR__ . '/../includes/functions.php';
    $output = ob_get_clean();
    if ($output) {
        $errors[] = "Output inatteso da functions.php: $output";
    }
    $trace[] = "  - functions.php: OK";
    $trace[] = "  - isLoggedIn esiste: " . (function_exists('isLoggedIn') ? 'SI' : 'NO');
} catch (Throwable $e) {
    ob_end_clean();
    $errors[] = "Errore functions.php: " . $e->getMessage();
    $trace[] = "  - ERRORE: " . $e->getMessage();
}

// Step 4: Verifica sessione
$trace[] = "Step 4: Sessione";
if (session_status() === PHP_SESSION_NONE) {
    $trace[] = "  - Sessione non avviata, provo...";
    try {
        session_start();
        $trace[] = "  - Sessione avviata: OK";
    } catch (Throwable $e) {
        $errors[] = "Errore sessione: " . $e->getMessage();
        $trace[] = "  - ERRORE: " . $e->getMessage();
    }
} else {
    $trace[] = "  - Sessione già attiva";
}
$trace[] = "  - SESSION: " . json_encode($_SESSION);

// Step 5: Verifica isLoggedIn
$trace[] = "Step 5: Test isLoggedIn()";
if (function_exists('isLoggedIn')) {
    try {
        $logged = isLoggedIn();
        $trace[] = "  - Risultato: " . ($logged ? 'LOGGATO' : 'NON LOGGATO');
    } catch (Throwable $e) {
        $errors[] = "Errore isLoggedIn(): " . $e->getMessage();
        $trace[] = "  - ERRORE: " . $e->getMessage();
    }
} else {
    $errors[] = "isLoggedIn() non esiste";
}

// Step 6: Test query semplice
$trace[] = "Step 6: Test query";
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        $trace[] = "  - Query: OK (result=" . json_encode($result) . ")";
    } catch (Throwable $e) {
        $errors[] = "Errore query: " . $e->getMessage();
        $trace[] = "  - ERRORE: " . $e->getMessage();
    }
} else {
    $errors[] = "PDO non disponibile";
}

// Output
header('Content-Type: application/json');
echo json_encode([
    'success' => empty($errors),
    'errors' => $errors,
    'trace' => $trace,
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
], JSON_PRETTY_PRINT);
