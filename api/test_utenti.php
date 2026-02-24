<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
$stmt = $pdo->query("DESCRIBE utenti");
echo json_encode(['utenti_columns' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_PRETTY_PRINT);
