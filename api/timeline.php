<?php
/**
 * Eterea Gestionale
 * API Timeline
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'recent':
        getRecent();
        break;
        
    case 'pulisci':
        pulisci();
        break;
        
    default:
        jsonResponse(false, null, 'Azione non valida');
}

/**
 * Ottieni timeline recente
 */
function getRecent(): void {
    global $pdo;
    
    $limit = intval($_GET['limit'] ?? 20);
    if ($limit > 100) $limit = 100;
    
    try {
        $stmt = $pdo->prepare("
            SELECT tl.*, u.nome as utente_nome, u.colore as utente_colore
            FROM timeline tl
            LEFT JOIN utenti u ON tl.utente_id = u.id
            ORDER BY tl.timestamp DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $timeline = $stmt->fetchAll();
        
        jsonResponse(true, $timeline);
        
    } catch (PDOException $e) {
        error_log("Errore timeline: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento timeline');
    }
}

/**
 * Forza pulizia timeline (per cron job)
 */
function pulisci(): void {
    // Verifica se chiamato da CLI o con token segreto
    $secret = $_GET['secret'] ?? '';
    $expectedSecret = 'ldetimeline2026'; // Cambiare in produzione
    
    if (php_sapi_name() !== 'cli' && $secret !== $expectedSecret) {
        jsonResponse(false, null, 'Accesso negato');
    }
    
    pulisciTimeline();
    jsonResponse(true, null, 'Pulizia completata');
}
