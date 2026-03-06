<?php
// Cattura TUTTI gli errori
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// Buffer per catturare output
ob_start();

try {
    echo "Step 1: Loading functions.php...\n";
    require_once __DIR__ . '/../includes/functions.php';
    echo "Step 1: OK\n";
    
    echo "Step 2: Loading config.php...\n";
    require_once __DIR__ . '/../includes/config.php';
    echo "Step 2: OK\n";
    
    echo "Step 3: Session start...\n";
    session_start();
    echo "Step 3: OK - User: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
    
    echo "Step 4: Testing PDO...\n";
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM progetti");
    $count = $stmt->fetchColumn();
    echo "Step 4: OK - Progetti: $count\n";
    
    echo "Step 5: Testing jsonResponse...\n";
    // Non usare jsonResponse, testa json_encode direttamente
    $test = array('success' => true, 'data' => array('test' => 'ok'));
    $json = json_encode($test);
    echo "Step 5: OK - JSON: $json\n";
    
    echo "\n=== ALL TESTS PASSED ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

// Ottieni buffer
$output = ob_get_clean();

// Mostra come testo
header('Content-Type: text/plain');
echo $output;
