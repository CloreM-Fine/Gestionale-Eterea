<?php
// Debug API Scadenze
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== DEBUG API SCADENZE ===\n\n";

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    
    echo "Step 1: Verifica tabelle...\n";
    $tables = ['scadenze', 'scadenze_tipologie', 'utenti', 'clienti'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->fetch();
            echo "  $table: " . ($exists ? "OK" : "NON TROVATA") . "\n";
        } catch (PDOException $e) {
            echo "  $table: ERRORE - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nStep 2: Verifica colonne tabella scadenze...\n";
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM scadenze");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "  Colonne: " . implode(', ', $columns) . "\n";
    } catch (PDOException $e) {
        echo "  ERRORE: " . $e->getMessage() . "\n";
    }
    
    echo "\nStep 3: Test query count_oggi...\n";
    try {
        $sql = "SELECT COUNT(*) as count FROM scadenze WHERE data_scadenza = CURDATE() AND stato = 'aperta'";
        echo "  Query: $sql\n";
        $stmt = $pdo->query($sql);
        if ($stmt === false) {
            echo "  ERRORE: Query ha restituito false\n";
        } else {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "  Risultato: " . print_r($result, true) . "\n";
        }
    } catch (PDOException $e) {
        echo "  ERRORE PDO: " . $e->getMessage() . "\n";
    }
    
    echo "\nStep 4: Test con user_id...\n";
    $userId = $_SESSION['user_id'] ?? 'ucwurog3xr8tf';
    try {
        $sql = "SELECT COUNT(*) as count FROM scadenze WHERE data_scadenza = CURDATE() AND stato = 'aperta' AND (user_id = ? OR user_id IS NULL)";
        echo "  Query: $sql\n";
        echo "  User ID: $userId\n";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  Risultato: " . print_r($result, true) . "\n";
    } catch (PDOException $e) {
        echo "  ERRORE PDO: " . $e->getMessage() . "\n";
    }
    
} catch (Throwable $e) {
    echo "ERRORE GENERALE: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== FINE DEBUG ===\n";
