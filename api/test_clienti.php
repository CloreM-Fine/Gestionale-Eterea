<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

$stmt = $pdo->query("DESCRIBE clienti");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['clienti_columns' => $columns], JSON_PRETTY_PRINT);
