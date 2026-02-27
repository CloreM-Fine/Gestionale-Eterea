<?php
/**
 * Esecuzione migrazione database preventivi
 */
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    // Aggiunge colonna frequenza a preventivi_salvati
    $stmt = $pdo->query("SHOW COLUMNS FROM preventivi_salvati LIKE 'frequenza'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE preventivi_salvati ADD COLUMN frequenza INT DEFAULT 1");
    }
    
    // Aggiunge colonna frequenza_testo a preventivi_salvati
    $stmt = $pdo->query("SHOW COLUMNS FROM preventivi_salvati LIKE 'frequenza_testo'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE preventivi_salvati ADD COLUMN frequenza_testo VARCHAR(50) DEFAULT 'Una tantum'");
    }
    
    // Verifica colonna frequenza in listino_voci
    $stmt = $pdo->query("SHOW COLUMNS FROM listino_voci LIKE 'frequenza'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE listino_voci ADD COLUMN frequenza INT DEFAULT 1");
    }
    
    echo json_encode(['success' => true, 'message' => 'Migrazione eseguita con successo']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
