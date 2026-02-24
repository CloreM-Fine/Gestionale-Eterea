<?php
// Test scadenze step by step
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "=== TEST SCADENZE ===\n\n";

try {
    // Step 1: Start session
    echo "Step 1: Start session\n";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "OK - Session ID: " . session_id() . "\n\n";
    
    // Step 2: Include files
    echo "Step 2: Include database.php\n";
    require_once __DIR__ . '/config/database.php';
    echo "OK\n\n";
    
    echo "Step 3: Include functions.php\n";
    require_once __DIR__ . '/includes/functions.php';
    echo "OK\n\n";
    
    // Step 4: Check isLoggedIn
    echo "Step 4: Check isLoggedIn\n";
    $logged = isLoggedIn();
    echo "isLoggedIn: " . ($logged ? 'true' : 'false') . "\n";
    if (!$logged) {
        $_SESSION['user_id'] = 'ucwurog3xr8tf';
        $_SESSION['user_name'] = 'Test';
        echo "Settato user_id in sessione\n";
    }
    echo "\n";
    
    // Step 5: Check isAdmin
    echo "Step 5: Check isAdmin\n";
    $admin = isAdmin();
    echo "isAdmin: " . ($admin ? 'true' : 'false') . "\n\n";
    
    // Step 6: Test query scadenze
    echo "Step 6: Test query scadenze count_oggi\n";
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
        $err = $pdo->errorInfo();
        echo "PDO ErrorInfo: " . print_r($err, true) . "\n";
    } else {
        echo "prepare() OK\n";
        $res = $stmt->execute($params);
        if ($res === false) {
            echo "ERRORE: execute() ha restituito false\n";
            $err = $stmt->errorInfo();
            echo "Stmt ErrorInfo: " . print_r($err, true) . "\n";
        } else {
            echo "execute() OK\n";
            $count = $stmt->fetchColumn();
            echo "Count: $count\n";
        }
    }
    
    echo "\n=== TEST COMPLETATO ===\n";
    
} catch (Throwable $e) {
    echo "\nERRORE: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
