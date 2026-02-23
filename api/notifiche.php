<?php
/**
 * Eterea Gestionale
 * API Notifiche
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            getNotifiche();
        } elseif ($action === 'count') {
            countNotificheNonLette();
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    case 'POST':
        if ($action === 'mark_read') {
            markNotificaLetta($_POST['id'] ?? null);
        } elseif ($action === 'mark_all_read') {
            markAllNotificheLette();
        } elseif ($action === 'delete_all') {
            deleteAllNotifiche();
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Ottieni notifiche dell'utente corrente
 */
function getNotifiche(): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT n.*, u.nome as creato_da_nome
            FROM notifiche n
            LEFT JOIN utenti u ON n.creato_da = u.id
            WHERE n.utente_destinatario IS NULL OR n.utente_destinatario = ?
            ORDER BY n.data_creazione DESC
            LIMIT 20
        ");
        // NOTA: Se il campo progetto_id non esiste, eseguire prima la migration
        // backup/migration_notifiche_progetto_id.sql
        $stmt->execute([$_SESSION['user_id']]);
        $notifiche = $stmt->fetchAll();
        
        jsonResponse(true, $notifiche);
    } catch (PDOException $e) {
        error_log("Errore get notifiche: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento notifiche');
    }
}

/**
 * Conta notifiche non lette
 */
function countNotificheNonLette(): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM notifiche 
            WHERE (utente_destinatario IS NULL OR utente_destinatario = ?) 
            AND letta = FALSE
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $count = (int)$stmt->fetchColumn();
        
        jsonResponse(true, ['count' => $count]);
    } catch (PDOException $e) {
        error_log("Errore count notifiche: " . $e->getMessage());
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Marca una notifica come letta
 */
function markNotificaLetta(?string $id): void {
    global $pdo;
    
    if (!$id) {
        jsonResponse(false, null, 'ID mancante');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifiche 
            SET letta = TRUE, data_lettura = NOW() 
            WHERE id = ? AND (utente_destinatario IS NULL OR utente_destinatario = ?)
        ");
        $stmt->execute([$id, $_SESSION['user_id']]);
        
        jsonResponse(true, null, 'Notifica marcata come letta');
    } catch (PDOException $e) {
        error_log("Errore mark notifica: " . $e->getMessage());
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Marca tutte le notifiche come lette
 */
function markAllNotificheLette(): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifiche 
            SET letta = TRUE, data_lettura = NOW() 
            WHERE (utente_destinatario IS NULL OR utente_destinatario = ?) AND letta = FALSE
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        jsonResponse(true, null, 'Tutte le notifiche marcate come lette');
    } catch (PDOException $e) {
        error_log("Errore mark all notifiche: " . $e->getMessage());
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Elimina tutte le notifiche dell'utente
 */
function deleteAllNotifiche(): void {
    global $pdo;
    
    try {
        // Elimina solo le notifiche destinate all'utente corrente o broadcast
        $stmt = $pdo->prepare("
            DELETE FROM notifiche 
            WHERE utente_destinatario IS NULL OR utente_destinatario = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        $deletedCount = $stmt->rowCount();
        
        jsonResponse(true, ['deleted' => $deletedCount], 'Tutte le notifiche eliminate');
    } catch (PDOException $e) {
        error_log("Errore delete all notifiche: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione notifiche');
    }
}
