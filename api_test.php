<?php
// Test API diretto
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain');

try {
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/config.php';
    
    echo "Includes OK\n";
    
    global $pdo;
    echo "PDO OK\n";
    
    // Test query progetti
    $stmt = $pdo->query("SELECT COUNT(*) FROM progetti");
    echo "Progetti: " . $stmt->fetchColumn() . "\n";
    
    // Test funzione reportProgettiStats
    echo "Test reportProgettiStats...\n";
    
    // Copio la funzione qui per test
    $stato = 'tutti';
    $where = '';
    $params = array();
    if ($stato !== 'tutti') {
        $where = "WHERE stato = ?";
        $params[] = $stato;
    }
    
    $sql = "SELECT id, titolo, stato, budget, data_inizio,
        (SELECT ragione_sociale FROM clienti c WHERE c.id = p.cliente_id) as cliente
        FROM progetti p $where ORDER BY data_inizio DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo "Query OK - rows: " . $stmt->rowCount() . "\n";
    
    echo "ALL TESTS PASSED";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
