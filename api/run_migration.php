<?php
/**
 * Esecuzione migrazione database preventivi
 */
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

try {
    $sql = file_get_contents(__DIR__ . '/../database_migration_preventivi.sql');
    $pdo->exec($sql);
    echo json_encode(['success' => true, 'message' => 'Migrazione eseguita con successo']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
