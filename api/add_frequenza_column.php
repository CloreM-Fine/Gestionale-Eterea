<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    // Aggiungi colonna frequenza se non esiste
    $pdo->exec("ALTER TABLE listino_voci ADD COLUMN IF NOT EXISTS frequenza INT DEFAULT 1");
    
    echo json_encode(['success' => true, 'message' => 'Colonna frequenza aggiunta']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
