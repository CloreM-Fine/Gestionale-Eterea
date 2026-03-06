<?php
// Test minimale - nessun include
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Test</title></head><body>\n";
echo "<h1>Test Minimal</h1>\n";

// Test 1: PHP funziona
echo "<p>PHP OK</p>\n";

// Test 2: Sessione
session_start();
echo "<p>Session: " . ($_SESSION['user_id'] ?? 'not set') . "</p>\n";

// Test 3: Connessione DB
$host = 'localhost';
$db   = 'dbqdsx4jwrcdsg';
$user = 'ugv7adudxudhx';
$pass = '';

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'DB_PASS=') === 0) {
            $pass = substr($line, 8);
            break;
        }
    }
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM progetti");
    $count = $stmt->fetchColumn();
    echo "<p>DB OK - Progetti: $count</p>\n";
} catch (Exception $e) {
    echo "<p>DB Error: " . $e->getMessage() . "</p>\n";
}

// Test 4: Chiamata API Report
$apiUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/api/report.php?action=dashboard';
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>API Test</h2>\n";
echo "<p>HTTP Code: $httpCode</p>\n";
echo "<p>Response:</p>\n";
echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>\n";

echo "</body></html>\n";
