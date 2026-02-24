<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$results = [];

// Colonne tabella progetti
$stmt = $pdo->query("DESCRIBE progetti");
$results['progetti_columns'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Colonne tabella scadenze
$stmt = $pdo->query("DESCRIBE scadenze");
$results['scadenze_columns'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($results, JSON_PRETTY_PRINT);
