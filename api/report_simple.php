<?php
// API SEMPLICE PER TEST
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/config.php';
    
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM progetti");
    $count = $stmt->fetchColumn();
    
    echo json_encode(array(
        'success' => true,
        'data' => array('progetti_count' => $count),
        'message' => 'OK'
    ));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ));
}
