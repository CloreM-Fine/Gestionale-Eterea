<?php
// Debug dettagliato API Scadenze
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== DEBUG DETTAGLIATO SCADENZE ===\n\n";

try {
    echo "Step 1: Include file...\n";
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    echo "OK: File inclusi\n\n";
    
    echo "Step 2: Verifica isLoggedIn...\n";
    $logged = isLoggedIn();
    echo "isLoggedIn(): " . ($logged ? 'true' : 'false') . "\n";
    echo "SESSION: " . print_r($_SESSION, true) . "\n";
    
    echo "Step 3: Verifica isAdmin...\n";
    $admin = isAdmin();
    echo "isAdmin(): " . ($admin ? 'true' : 'false') . "\n\n";
    
    echo "Step 4: Test query countScadenzeOggi...\n";
    $userId = $_SESSION['user_id'] ?? '';
    $isAdmin = isAdmin();
    
    $sql = "SELECT COUNT(*) as count FROM scadenze WHERE data_scadenza = CURDATE() AND stato = 'aperta'";
    $params = [];
    
    if (!$isAdmin) {
        $sql .= " AND (user_id = ? OR user_id IS NULL)";
        $params[] = $userId;
    }
    
    echo "SQL: $sql\n";
    echo "Params: " . print_r($params, true) . "\n";
    
    $stmt = $pdo->prepare($sql);
    if ($stmt === false) {
        echo "ERRORE: prepare() ha restituito false\n";
        $errorInfo = $pdo->errorInfo();
        echo "PDO Error: " . print_r($errorInfo, true) . "\n";
    } else {
        echo "OK: prepare() riuscito\n";
        $result = $stmt->execute($params);
        if ($result === false) {
            echo "ERRORE: execute() ha restituito false\n";
            $errorInfo = $stmt->errorInfo();
            echo "Stmt Error: " . print_r($errorInfo, true) . "\n";
        } else {
            echo "OK: execute() riuscito\n";
            $count = $stmt->fetchColumn();
            echo "Count: $count\n";
        }
    }
    
} catch (Throwable $e) {
    echo "ERRORE: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FINE DEBUG ===\n";
