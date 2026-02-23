<?php
/**
 * Eterea Gestionale
 * API Tasse
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'POST':
        if ($action === 'salva_calcolo_tasse') {
            salvaCalcoloTasse();
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Salva un calcolo tasse nella cronologia
 */
function salvaCalcoloTasse(): void {
    global $pdo;
    
    $userId = $_SESSION['user_id'] ?? '';
    
    if (empty($userId)) {
        jsonResponse(false, null, 'Utente non autenticato');
        return;
    }
    
    $fatturato = floatval($_POST['fatturato'] ?? 0);
    $codiceAteco = trim($_POST['codice_ateco'] ?? '');
    $descrizioneAteco = trim($_POST['descrizione_ateco'] ?? '');
    $coefficiente = floatval($_POST['coefficiente'] ?? 0);
    $redditoImponibile = floatval($_POST['reddito_imponibile'] ?? 0);
    $aliquotaIrpef = floatval($_POST['aliquota_irpef'] ?? 0);
    $impostaIrpef = floatval($_POST['imposta_irpef'] ?? 0);
    $inpsPercentuale = floatval($_POST['inps_percentuale'] ?? 0);
    $contributiInps = floatval($_POST['contributi_inps'] ?? 0);
    $accontoPercentuale = floatval($_POST['acconto_percentuale'] ?? 0);
    $acconti = floatval($_POST['acconti'] ?? 0);
    $totaleTasse = floatval($_POST['totale_tasse'] ?? 0);
    $netto = floatval($_POST['netto'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    
    if ($fatturato <= 0) {
        jsonResponse(false, null, 'Fatturato non valido');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cronologia_calcoli_tasse 
            (user_id, fatturato, codice_ateco, descrizione_ateco, coefficiente, 
             reddito_imponibile, aliquota_irpef, imposta_irpef, 
             inps_percentuale, contributi_inps, acconto_percentuale, acconti,
             totale_tasse, netto, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId, $fatturato, $codiceAteco, $descrizioneAteco, $coefficiente,
            $redditoImponibile, $aliquotaIrpef, $impostaIrpef,
            $inpsPercentuale, $contributiInps, $accontoPercentuale, $acconti,
            $totaleTasse, $netto, $note
        ]);
        
        $id = $pdo->lastInsertId();
        
        jsonResponse(true, ['id' => $id], 'Calcolo salvato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore salva calcolo tasse: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante il salvataggio');
    }
}
