<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    // Verifica se colonna esiste
    $stmt = $pdo->query("SHOW COLUMNS FROM listino_voci LIKE 'frequenza'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE listino_voci ADD COLUMN frequenza INT DEFAULT 1");
        echo json_encode(['success' => true, 'message' => 'Colonna frequenza aggiunta']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Colonna frequenza già esistente']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
