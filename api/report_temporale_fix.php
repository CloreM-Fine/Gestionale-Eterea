<?php
// Temporale fix - versione semplificata
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/config.php';

session_start();
if (empty($_SESSION['user_id'])) {
    echo json_encode(array('success' => false, 'message' => 'Non autenticato'));
    exit;
}

try {
    global $pdo;
    $mesi = intval($_GET['mesi'] ?? 6);
    
    $taskCompletate = array();
    $nuoviProgetti = array();
    
    // Task per mese
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(updated_at, '%Y-%m') as periodo, COUNT(*) as task_completate FROM task WHERE updated_at > DATE_SUB(NOW(), INTERVAL ? MONTH) GROUP BY DATE_FORMAT(updated_at, '%Y-%m')");
    $stmt->execute(array($mesi));
    $taskCompletate = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Progetti per mese
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as periodo, COUNT(*) as nuovi_progetti FROM progetti WHERE created_at > DATE_SUB(NOW(), INTERVAL ? MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m')");
    $stmt->execute(array($mesi));
    $nuoviProgetti = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(array(
        'success' => true,
        'data' => array(
            'periodo_mesi' => $mesi,
            'task_completate' => $taskCompletate,
            'ore_lavorate' => array(),
            'nuovi_progetti' => $nuoviProgetti
        )
    ));
    
} catch (Exception $e) {
    error_log("Errore temporale: " . $e->getMessage());
    echo json_encode(array('success' => false, 'message' => 'Errore: ' . $e->getMessage()));
}
