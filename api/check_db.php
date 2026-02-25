<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

// Verifica colonne tabella listino_voci
$stmt = $pdo->query("DESCRIBE listino_voci");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'columns' => $columns,
    'has_frequenza' => in_array('frequenza', $columns)
]);
