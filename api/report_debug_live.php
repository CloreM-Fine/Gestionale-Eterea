<?php
// Debug LIVE - mostra tutti gli errori
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain');

try {
    echo "=== STEP 1: Includes ===\n";
    require_once __DIR__ . '/../includes/functions.php';
    echo "functions.php OK\n";
    require_once __DIR__ . '/../includes/config.php';
    echo "config.php OK\n";
    
    echo "\n=== STEP 2: Session ===\n";
    session_start();
    echo "Session ID: " . session_id() . "\n";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
    
    echo "\n=== STEP 3: Database ===\n";
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM progetti");
    echo "Progetti count: " . $stmt->fetchColumn() . "\n";
    
    echo "\n=== STEP 4: Test function ===\n";
    
    // Test dashboard query
    echo "\n--- Testing Dashboard ---\n";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as totale FROM progetti");
        $progetti = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Progetti query OK: " . print_r($progetti, true);
        
        $stmt = $pdo->query("SELECT COUNT(*) as totale FROM task");
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Task query OK: " . print_r($task, true);
        
        // Test colonna opzionale
        try {
            $stmt = $pdo->query("SELECT SUM(tempo_impiegato_seconds) FROM task");
            echo "Tempo OK: " . $stmt->fetchColumn() . "\n";
        } catch (Exception $e) {
            echo "Tempo column not found (expected)\n";
        }
        
        echo "Dashboard queries OK!\n";
    } catch (Exception $e) {
        echo "Dashboard ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n--- Testing Progetti ---\n";
    try {
        $stmt = $pdo->query("SELECT id, titolo, stato, budget, data_inizio, cliente_id FROM progetti ORDER BY data_inizio DESC");
        echo "Progetti query OK, rows: " . $stmt->rowCount() . "\n";
    } catch (Exception $e) {
        echo "Progetti ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n--- Testing Temporale ---\n";
    try {
        $stmt = $pdo->query("SELECT DATE_FORMAT(updated_at, '%Y-%m') as periodo, COUNT(*) as task_completate FROM task WHERE stato = 'completato' AND updated_at > DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(updated_at, '%Y-%m')");
        echo "Temporale query OK\n";
    } catch (Exception $e) {
        echo "Temporale ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== ALL TESTS COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "\nFATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
