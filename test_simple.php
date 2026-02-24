<?php
// Test semplice
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== TEST SEMPLICE ===\n\n";

try {
    echo "1. Includo database.php\n";
    require_once __DIR__ . '/config/database.php';
    echo "   OK\n\n";
    
    echo "2. Verifico variabile \$pdo\n";
    if (!isset($pdo)) {
        echo "   ERRORE: \$pdo non esiste\n";
        exit;
    }
    echo "   OK - \$pdo esiste\n\n";
    
    echo "3. Test query\n";
    $stmt = $pdo->query("SELECT 1 as test");
    if ($stmt === false) {
        echo "   ERRORE: query ha restituito false\n";
        exit;
    }
    $result = $stmt->fetch();
    echo "   OK - Risultato: " . $result['test'] . "\n\n";
    
    echo "4. Test query progetti\n";
    $stmt = $pdo->query("SELECT COUNT(*) as totale FROM progetti");
    if ($stmt === false) {
        echo "   ERRORE: query progetti ha restituito false\n";
        exit;
    }
    $result = $stmt->fetch();
    echo "   OK - Progetti: " . $result['totale'] . "\n\n";
    
    echo "5. Test query con prepare\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) as totale FROM progetti WHERE stato_progetto = ?");
    if ($stmt === false) {
        echo "   ERRORE: prepare ha restituito false\n";
        $err = $pdo->errorInfo();
        echo "   ErrorInfo: " . print_r($err, true) . "\n";
        exit;
    }
    echo "   OK - prepare riuscito\n";
    
    $res = $stmt->execute(['completato']);
    if ($res === false) {
        echo "   ERRORE: execute ha restituito false\n";
        exit;
    }
    echo "   OK - execute riuscito\n";
    
    $result = $stmt->fetch();
    echo "   Progetti completati: " . $result['totale'] . "\n\n";
    
    echo "=== TUTTO OK ===\n";
    
} catch (Throwable $e) {
    echo "ERRORE: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
