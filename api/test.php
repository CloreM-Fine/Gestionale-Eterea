<?php
// Test API
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    
    echo json_encode([
        'success' => true, 
        'message' => 'Test OK',
        'logged_in' => isLoggedIn(),
        'pdo_exists' => isset($pdo),
        'user_id' => $_SESSION['user_id'] ?? 'not set'
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
