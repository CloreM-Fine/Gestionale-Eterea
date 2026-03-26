<?php
/**
 * Mail - Debug version
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

echo "<pre>";
echo "=== DEBUG MAIL ===\n\n";

// Test 1: Session
echo "1. Session user_id: " . ($_SESSION['user_id'] ?? 'NULL') . "\n";

// Test 2: Database
try {
    global $pdo;
    $pdo->query("SELECT 1");
    echo "2. Database: OK\n";
} catch (Exception $e) {
    echo "2. Database ERROR: " . $e->getMessage() . "\n";
}

// Test 3: Tabelle mail
try {
    $tables = $pdo->query("SHOW TABLES LIKE 'mail_%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "3. Tabelle mail trovate: " . implode(', ', $tables) . "\n";
} catch (Exception $e) {
    echo "3. Tabelle ERROR: " . $e->getMessage() . "\n";
}

// Test 4: Query account
try {
    $userId = $_SESSION['user_id'] ?? '';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM mail_accounts WHERE utente_id = ?");
    $stmt->execute([$userId]);
    $count = $stmt->fetchColumn();
    echo "4. Account utente: $count\n";
} catch (Exception $e) {
    echo "4. Account ERROR: " . $e->getMessage() . "\n";
}

// Test 5: Clienti
try {
    $clienti = $pdo->query("SELECT COUNT(*) FROM clienti WHERE email IS NOT NULL AND email != ''")->fetchColumn();
    echo "5. Clienti con email: $clienti\n";
} catch (Exception $e) {
    echo "5. Clienti ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== TUTTI I TEST COMPLETATI ===";
echo "</pre>";
