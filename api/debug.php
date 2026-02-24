<?php
// Debug API - Mostra errori dettagliati
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== DEBUG API CONTABILITA ===\n\n";

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    
    echo "PDO Status: " . ($pdo ? "OK" : "FAILED") . "\n";
    echo "PDO Error Mode: " . $pdo->getAttribute(PDO::ATTR_ERRMODE) . "\n\n";
    
    // Test 1: Verifica tabella progetti
    echo "Test 1: Verifica tabella progetti...\n";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM progetti");
        echo "OK: Tabella progetti esiste\n";
    } catch (PDOException $e) {
        echo "ERRORE: " . $e->getMessage() . "\n";
    }
    
    // Test 2: Verifica tabella contabilita_mensile
    echo "\nTest 2: Verifica tabella contabilita_mensile...\n";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM contabilita_mensile");
        echo "OK: Tabella contabilita_mensile esiste\n";
    } catch (PDOException $e) {
        echo "ERRORE: " . $e->getMessage() . "\n";
    }
    
    // Test 3: Query con prepare
    echo "\nTest 3: Query con prepare...\n";
    $stmt = $pdo->prepare("SELECT SUM(prezzo_totale) as totale FROM progetti WHERE stato = 'consegnato'");
    if ($stmt === false) {
        $errorInfo = $pdo->errorInfo();
        echo "ERRORE PREPARE: " . print_r($errorInfo, true) . "\n";
    } else {
        echo "OK: Prepare riuscito\n";
        $result = $stmt->execute();
        if ($result === false) {
            $errorInfo = $stmt->errorInfo();
            echo "ERRORE EXECUTE: " . print_r($errorInfo, true) . "\n";
        } else {
            echo "OK: Execute riuscito\n";
            $data = $stmt->fetch();
            echo "Risultato: " . print_r($data, true) . "\n";
        }
    }
    
} catch (Throwable $e) {
    echo "ERRORE GENERALE: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== FINE DEBUG ===\n";
