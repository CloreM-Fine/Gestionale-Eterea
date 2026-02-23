<?php
/**
 * Eterea Gestionale
 * Script pulizia timeline (per cron job)
 * 
 * Uso:
 * - Via cron job: php /path/to/cron_pulizia.php
 * - Via web: https://tuo-sito.com/cron_pulizia.php?key=TUO_SECRET_KEY
 */

// Verifica esecuzione da CLI o con chiave segreta
$secretKey = 'ldetimeline2026'; // Cambiare in produzione!
$isCli = php_sapi_name() === 'cli';
$webKey = $_GET['key'] ?? '';

if (!$isCli && $webKey !== $secretKey) {
    http_response_code(403);
    die('Accesso negato');
}

require_once __DIR__ . '/includes/functions.php';

try {
    // Pulisci timeline
    $stmt = $pdo->prepare("DELETE FROM timeline WHERE auto_delete_date < CURDATE()");
    $stmt->execute();
    $deleted = $stmt->rowCount();
    
    $message = "Pulizia completata. Eliminate {$deleted} righe.";
    
    if ($isCli) {
        // Output per CLI
        echo date('Y-m-d H:i:s') . " - {$message}\n";
    } else {
        // Output per web
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'deleted' => $deleted,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Log
    error_log("[LDE CRON] {$message}");
    
} catch (PDOException $e) {
    $error = "Errore: " . $e->getMessage();
    
    if ($isCli) {
        echo date('Y-m-d H:i:s') . " - {$error}\n";
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $error]);
    }
    
    error_log("[LDE CRON ERROR] {$error}");
}
