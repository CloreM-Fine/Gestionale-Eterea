<?php
/**
 * Eterea Gestionale
 * API Finanze - Gestione inserimenti manuali (solo admin)
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Verifica che l'utente sia Lorenzo Puccetti (admin)
$isAdmin = ($_SESSION['user_id'] === 'ucwurog3xr8tf' || $_SESSION['user_name'] === 'Lorenzo Puccetti');

if (!$isAdmin) {
    jsonResponse(false, null, 'Non autorizzato');
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'list_transazioni') {
            listTransazioni();
        } elseif ($action === 'delete' && isset($_GET['id'])) {
            eliminaTransazione($_GET['id']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    case 'POST':
        if ($action === 'aggiungi_cassa') {
            aggiungiCassa();
        } elseif ($action === 'aggiungi_wallet') {
            aggiungiWallet();
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            eliminaTransazione($_POST['id']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Aggiungi importo alla cassa aziendale
 */
function aggiungiCassa(): void {
    global $pdo;
    
    $importo = floatval($_POST['importo'] ?? 0);
    $descrizione = trim($_POST['descrizione'] ?? '');
    
    if ($importo <= 0) {
        jsonResponse(false, null, 'Importo non valido');
    }
    
    try {
        // Genera ID transazione
        $transId = 'trc' . substr(md5(uniqid(mt_rand(), true)), 0, 10);
        
        // Inserisci transazione cassa
        $stmt = $pdo->prepare("
            INSERT INTO transazioni_economiche 
            (id, tipo, importo, percentuale, descrizione, data)
            VALUES (?, 'cassa', ?, 0, ?, NOW())
        ");
        $stmt->execute([$transId, $importo, $descrizione ?: 'Inserimento manuale']);
        
        // Log
        logTimeline($_SESSION['user_id'], 'aggiunto_cassa', 'cassa', $transId, "Aggiunto € {$importo} alla cassa");
        
        jsonResponse(true, ['id' => $transId], 'Importo aggiunto alla cassa con successo');
        
    } catch (PDOException $e) {
        error_log("Errore aggiunta cassa: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante l\'inserimento');
    }
}

/**
 * Aggiungi importo al wallet di un utente
 */
function aggiungiWallet(): void {
    global $pdo;
    
    $utenteId = $_POST['utente_id'] ?? '';
    $importo = floatval($_POST['importo'] ?? 0);
    $descrizione = trim($_POST['descrizione'] ?? '');
    
    if (empty($utenteId) || $importo <= 0) {
        jsonResponse(false, null, 'Dati non validi');
    }
    
    // Verifica che l'utente esista
    $stmt = $pdo->prepare("SELECT id, nome FROM utenti WHERE id = ?");
    $stmt->execute([$utenteId]);
    $utente = $stmt->fetch();
    
    if (!$utente) {
        jsonResponse(false, null, 'Utente non trovato');
    }
    
    try {
        $pdo->beginTransaction();
        
        // Genera ID transazione
        $transId = 'trw' . substr(md5(uniqid(mt_rand(), true)), 0, 10);
        
        // Inserisci transazione wallet
        $stmt = $pdo->prepare("
            INSERT INTO transazioni_economiche 
            (id, tipo, utente_id, importo, percentuale, descrizione, data)
            VALUES (?, 'wallet', ?, ?, 0, ?, NOW())
        ");
        $stmt->execute([$transId, $utenteId, $importo, $descrizione ?: 'Inserimento manuale']);
        
        // Aggiorna saldo wallet utente
        $stmt = $pdo->prepare("
            UPDATE utenti 
            SET wallet_saldo = wallet_saldo + ? 
            WHERE id = ?
        ");
        $stmt->execute([$importo, $utenteId]);
        
        $pdo->commit();
        
        // Log
        logTimeline($_SESSION['user_id'], 'aggiunto_wallet', 'utente', $utenteId, "Aggiunto € {$importo} al wallet di {$utente['nome']}");
        
        jsonResponse(true, ['id' => $transId], 'Importo aggiunto al wallet con successo');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Errore aggiunta wallet: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante l\'inserimento');
    }
}


/**
 * Lista transazioni recenti (inserimenti manuali)
 */
function listTransazioni(): void {
    global $pdo;
    
    try {
        // Recupera ultime 20 transazioni
        $stmt = $pdo->query("
            SELECT t.*, u.nome as utente_nome
            FROM transazioni_economiche t
            LEFT JOIN utenti u ON t.utente_id = u.id
            WHERE t.progetto_id IS NULL
            ORDER BY t.data DESC
            LIMIT 20
        ");
        $transazioni = $stmt->fetchAll();
        
        jsonResponse(true, $transazioni);
        
    } catch (PDOException $e) {
        error_log("Errore lista transazioni: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento transazioni');
    }
}

/**
 * Elimina una transazione e aggiorna i saldi
 */
function eliminaTransazione(string $id): void {
    global $pdo;
    
    try {
        // Recupera la transazione
        $stmt = $pdo->prepare("
            SELECT * FROM transazioni_economiche WHERE id = ?
        ");
        $stmt->execute([$id]);
        $trans = $stmt->fetch();
        
        if (!$trans) {
            jsonResponse(false, null, 'Transazione non trovata');
        }
        
        // Solo transazioni manuali (senza progetto_id)
        if (!empty($trans['progetto_id'])) {
            jsonResponse(false, null, 'Non puoi eliminare transazioni di progetti');
        }
        
        $pdo->beginTransaction();
        
        // Se è una transazione wallet, sottrai dal saldo utente
        if ($trans['tipo'] === 'wallet' && !empty($trans['utente_id'])) {
            $stmt = $pdo->prepare("
                UPDATE utenti 
                SET wallet_saldo = wallet_saldo - ? 
                WHERE id = ?
            ");
            $stmt->execute([$trans['importo'], $trans['utente_id']]);
        }
        
        // Elimina la transazione
        $stmt = $pdo->prepare("DELETE FROM transazioni_economiche WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        
        // Log
        logTimeline($_SESSION['user_id'], 'eliminata_transazione', 'finanze', $id, "Eliminata transazione di € {$trans['importo']}");
        
        jsonResponse(true, null, 'Transazione eliminata con successo');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Errore eliminazione transazione: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante l\'eliminazione');
    }
}
