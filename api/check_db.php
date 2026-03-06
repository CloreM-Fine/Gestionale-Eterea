<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

global $pdo;

echo "=== CHECK progetti TABLE ===\n";
$stmt = $pdo->query("SHOW COLUMNS FROM progetti");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\n=== CHECK progetti DATA ===\n";
$stmt = $pdo->query("SELECT id, titolo, cliente_id FROM progetti LIMIT 3");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo print_r($row, true);
}

echo "\n=== CHECK clienti TABLE ===\n";
$stmt = $pdo->query("SHOW COLUMNS FROM clienti");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
