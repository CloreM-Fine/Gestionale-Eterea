<?php
// Test API - Debug completo
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

$response = ['steps' => []];

try {
    // Step 1: Verifica file config
    $response['steps'][] = 'Step 1: Checking config file...';
    $configFile = __DIR__ . '/../config/database.php';
    if (!file_exists($configFile)) {
        throw new Exception("Config file not found: " . $configFile);
    }
    $response['steps'][] = 'Step 1: OK - Config file exists';
    
    // Step 2: Include config
    $response['steps'][] = 'Step 2: Including config...';
    require_once $configFile;
    $response['steps'][] = 'Step 2: OK - Config included';
    
    // Step 3: Verifica PDO
    $response['steps'][] = 'Step 3: Checking PDO...';
    if (!isset($pdo)) {
        throw new Exception("PDO not initialized");
    }
    $response['steps'][] = 'Step 3: OK - PDO initialized';
    
    // Step 4: Test query
    $response['steps'][] = 'Step 4: Testing query...';
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    $response['steps'][] = 'Step 4: OK - Query executed';
    
    // Step 5: Test tabella contabilita
    $response['steps'][] = 'Step 5: Checking contabilita_mensile table...';
    $stmt = $pdo->query("SHOW TABLES LIKE 'contabilita_mensile'");
    $tableExists = $stmt->fetch();
    $response['table_contabilita_exists'] = $tableExists ? true : false;
    $response['steps'][] = 'Step 5: OK - Table check done';
    
    // Step 6: Test tabella scadenze
    $response['steps'][] = 'Step 6: Checking scadenze table...';
    $stmt = $pdo->query("SHOW TABLES LIKE 'scadenze'");
    $tableExists = $stmt->fetch();
    $response['table_scadenze_exists'] = $tableExists ? true : false;
    $response['steps'][] = 'Step 6: OK - Table check done';
    
    $response['success'] = true;
    $response['message'] = 'All tests passed!';
    
} catch (Throwable $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    $response['file'] = $e->getFile();
    $response['line'] = $e->getLine();
    $response['trace'] = $e->getTraceAsString();
}

echo json_encode($response, JSON_PRETTY_PRINT);
