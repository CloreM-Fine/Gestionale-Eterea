<?php
// Test API contabilita
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== TEST API CONTABILITA ===\n\n";

try {
    echo "Step 1: Include database e functions...\n";
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/includes/functions.php';
    echo "OK: File inclusi\n\n";
    
    echo "Step 2: Verifica autenticazione...\n";
    if (!isLoggedIn()) {
        die("ERRORE: Non autenticato\n");
    }
    echo "OK: Utente autenticato\n";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'non settato') . "\n\n";
    
    echo "Step 3: Test query progetti...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as totale FROM progetti WHERE stato = 'consegnato'");
    $result = $stmt->fetch();
    echo "OK: Query eseguita. Progetti consegnati: " . $result['totale'] . "\n\n";
    
    echo "Step 4: Test query contabilita_mensile...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as totale FROM contabilita_mensile");
    $result = $stmt->fetch();
    echo "OK: Query eseguita. Record contabilita: " . $result['totale'] . "\n\n";
    
    echo "Step 5: Simula chiamata API riepilogo...\n";
    $_GET['mese'] = 2;
    $_GET['anno'] = 2026;
    
    // Include l'API e cattura l'output
    ob_start();
    require_once __DIR__ . '/api/contabilita.php';
    $output = ob_get_clean();
    
    echo "Output API (primi 500 caratteri):\n";
    echo substr($output, 0, 500) . "\n\n";
    
    $data = json_decode($output, true);
    if ($data === null) {
        echo "ERRORE: JSON non valido\n";
        echo "Output completo:\n" . $output . "\n";
    } else {
        echo "OK: JSON valido\n";
        echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        if (isset($data['message'])) {
            echo "Message: " . $data['message'] . "\n";
        }
    }
    
} catch (Throwable $e) {
    echo "ERRORE: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETATO ===\n";
