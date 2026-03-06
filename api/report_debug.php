<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    require_once __DIR__ . '/../includes/functions.php';
    echo "Functions loaded OK<br>";
    
    require_once __DIR__ . '/../includes/auth_check.php';
    echo "Auth check OK<br>";
    
    global $pdo;
    echo "PDO connected OK<br>";
    
    // Test query semplice
    $stmt = $pdo->query("SELECT COUNT(*) FROM progetti");
    $count = $stmt->fetchColumn();
    echo "Progetti count: $count<br>";
    
    // Test jsonResponse
    jsonResponse(true, array('test' => 'ok'));
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
