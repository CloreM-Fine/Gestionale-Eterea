<?php
/**
 * API Scadenze
 * Gestisce CRUD scadenze e tipologie
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Verifica autenticazione
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':
                getScadenze();
                break;
            case 'tipologie':
                getTipologie();
                break;
            case 'count_oggi':
                countScadenzeOggi();
                break;
            case 'detail':
                getScadenzaDetail($_GET['id'] ?? '');
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Azione non valida']);
        }
        break;
        
    case 'POST':
        switch ($action) {
            case 'create':
                createScadenza();
                break;
            case 'update':
                updateScadenza();
                break;
            case 'delete':
                deleteScadenza();
                break;
            case 'complete':
                completeScadenza();
                break;
            case 'create_tipologia':
                createTipologia();
                break;
            case 'update_tipologia':
                updateTipologia();
                break;
            case 'delete_tipologia':
                deleteTipologia();
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Azione non valida']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
}

/**
 * Ottiene tutte le scadenze con filtri
 */
function getScadenze() {
    global $pdo;
    
    $userId = $_SESSION['user_id'] ?? '';
    $isAdmin = isAdmin();
    
    // Filtri
    $stato = $_GET['stato'] ?? '';
    $tipologia = $_GET['tipologia'] ?? '';
    $mese = $_GET['mese'] ?? '';
    $anno = $_GET['anno'] ?? '';
    $clienteId = $_GET['cliente_id'] ?? '';
    
    try {
        $sql = "
            SELECT s.*, 
                   st.nome as tipologia_nome, 
                   st.colore as tipologia_colore,
                   c.nome as cliente_nome,
                   u.nome as user_nome
            FROM scadenze s
            LEFT JOIN scadenze_tipologie st ON s.tipologia_id = st.id
            LEFT JOIN clienti c ON s.cliente_id = c.id
            LEFT JOIN utenti u ON s.user_id = u.id
            WHERE 1=1
        ";
        $params = [];
        
        // Non admin vedono solo le proprie scadenze
        if (!$isAdmin) {
            $sql .= " AND (s.user_id = ? OR s.user_id IS NULL)";
            $params[] = $userId;
        }
        
        if ($stato) {
            $sql .= " AND s.stato = ?";
            $params[] = $stato;
        }
        
        if ($tipologia) {
            $sql .= " AND s.tipologia_id = ?";
            $params[] = $tipologia;
        }
        
        if ($mese && $anno) {
            $sql .= " AND MONTH(s.data_scadenza) = ? AND YEAR(s.data_scadenza) = ?";
            $params[] = $mese;
            $params[] = $anno;
        }
        
        if ($clienteId) {
            $sql .= " AND s.cliente_id = ?";
            $params[] = $clienteId;
        }
        
        $sql .= " ORDER BY s.data_scadenza ASC, s.id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $scadenze = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Aggiorna stato a "scaduta" se necessario
        $oggi = date('Y-m-d');
        foreach ($scadenze as &$scadenza) {
            if ($scadenza['data_scadenza'] < $oggi && $scadenza['stato'] === 'aperta') {
                $scadenza['stato'] = 'scaduta';
            }
        }
        
        echo json_encode(['success' => true, 'data' => $scadenze]);
        
    } catch (PDOException $e) {
        error_log("Errore get scadenze: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore database: ' . $e->getMessage()]);
    } catch (Throwable $e) {
        error_log("Errore generico get scadenze: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
    }
}

/**
 * Conta scadenze per oggi (per notifica sidebar)
 */
function countScadenzeOggi() {
    global $pdo;
    
    $userId = $_SESSION['user_id'] ?? '';
    $isAdmin = isAdmin();
    
    try {
        $sql = "
            SELECT COUNT(*) as count 
            FROM scadenze 
            WHERE data_scadenza = CURDATE() 
            AND stato = 'aperta'
        ";
        $params = [];
        
        if (!$isAdmin) {
            $sql .= " AND (user_id = ? OR user_id IS NULL)";
            $params[] = $userId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        
        echo json_encode(['success' => true, 'count' => intval($count)]);
        
    } catch (PDOException $e) {
        error_log("Errore count scadenze: " . $e->getMessage());
        echo json_encode(['success' => false, 'count' => 0, 'message' => $e->getMessage()]);
    } catch (Throwable $e) {
        error_log("Errore generico count scadenze: " . $e->getMessage());
        echo json_encode(['success' => false, 'count' => 0, 'message' => $e->getMessage()]);
    }
}

/**
 * Ottiene una singola scadenza
 */
function getScadenzaDetail($id) {
    global $pdo;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID richiesto']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   st.nome as tipologia_nome, 
                   st.colore as tipologia_colore,
                   c.nome as cliente_nome,
                   u.nome as user_nome
            FROM scadenze s
            LEFT JOIN scadenze_tipologie st ON s.tipologia_id = st.id
            LEFT JOIN clienti c ON s.cliente_id = c.id
            LEFT JOIN utenti u ON s.user_id = u.id
            WHERE s.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $scadenza = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($scadenza) {
            echo json_encode(['success' => true, 'data' => $scadenza]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Scadenza non trovata']);
        }
        
    } catch (PDOException $e) {
        error_log("Errore get scadenza: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore database']);
    }
}

/**
 * Crea una nuova scadenza
 */
function createScadenza() {
    global $pdo;
    
    $titolo = trim($_POST['titolo'] ?? '');
    $dataScadenza = $_POST['data_scadenza'] ?? '';
    $tipologiaId = intval($_POST['tipologia_id'] ?? 0);
    $descrizione = trim($_POST['descrizione'] ?? '');
    $userId = $_POST['user_id'] ?? null;
    $clienteId = intval($_POST['cliente_id'] ?? 0) ?: null;
    $link = trim($_POST['link'] ?? '');
    
    if (empty($titolo) || empty($dataScadenza)) {
        echo json_encode(['success' => false, 'message' => 'Titolo e data scadenza sono obbligatori']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO scadenze 
            (titolo, data_scadenza, tipologia_id, descrizione, user_id, cliente_id, link, stato)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'aperta')
        ");
        
        $stmt->execute([
            $titolo,
            $dataScadenza,
            $tipologiaId ?: null,
            $descrizione,
            $userId,
            $clienteId,
            $link
        ]);
        
        $id = $pdo->lastInsertId();
        
        // Log
        logTimeline($_SESSION['user_id'], 'create', 'scadenza', $id, "Creata scadenza: {$titolo}");
        
        echo json_encode(['success' => true, 'message' => 'Scadenza creata', 'id' => $id]);
        
    } catch (PDOException $e) {
        error_log("Errore create scadenza: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore durante la creazione: ' . $e->getMessage()]);
    }
}

/**
 * Aggiorna una scadenza
 */
function updateScadenza() {
    global $pdo;
    
    $id = intval($_POST['id'] ?? 0);
    $titolo = trim($_POST['titolo'] ?? '');
    $dataScadenza = $_POST['data_scadenza'] ?? '';
    $tipologiaId = intval($_POST['tipologia_id'] ?? 0);
    $descrizione = trim($_POST['descrizione'] ?? '');
    $userId = $_POST['user_id'] ?? null;
    $clienteId = intval($_POST['cliente_id'] ?? 0) ?: null;
    $link = trim($_POST['link'] ?? '');
    
    if (!$id || empty($titolo) || empty($dataScadenza)) {
        echo json_encode(['success' => false, 'message' => 'Dati non validi']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE scadenze 
            SET titolo = ?, 
                data_scadenza = ?, 
                tipologia_id = ?, 
                descrizione = ?, 
                user_id = ?, 
                cliente_id = ?, 
                link = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $titolo,
            $dataScadenza,
            $tipologiaId ?: null,
            $descrizione,
            $userId,
            $clienteId,
            $link,
            $id
        ]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'update', 'scadenza', $id, "Aggiornata scadenza: {$titolo}");
        
        echo json_encode(['success' => true, 'message' => 'Scadenza aggiornata']);
        
    } catch (PDOException $e) {
        error_log("Errore update scadenza: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore durante aggiornamento']);
    }
}

/**
 * Elimina una scadenza
 */
function deleteScadenza() {
    global $pdo;
    
    $id = intval($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID richiesto']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM scadenze WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'delete', 'scadenza', $id, "Eliminata scadenza #{$id}");
        
        echo json_encode(['success' => true, 'message' => 'Scadenza eliminata']);
        
    } catch (PDOException $e) {
        error_log("Errore delete scadenza: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore durante eliminazione']);
    }
}

/**
 * Marca una scadenza come completata
 */
function completeScadenza() {
    global $pdo;
    
    $id = intval($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID richiesto']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE scadenze SET stato = 'completata' WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'complete', 'scadenza', $id, "Completata scadenza #{$id}");
        
        echo json_encode(['success' => true, 'message' => 'Scadenza completata']);
        
    } catch (PDOException $e) {
        error_log("Errore complete scadenza: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore']);
    }
}

// ============================================
// TIPOLOGIE
// ============================================

/**
 * Ottiene tutte le tipologie
 */
function getTipologie() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM scadenze_tipologie ORDER BY nome ASC");
        $tipologie = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $tipologie]);
        
    } catch (PDOException $e) {
        error_log("Errore get tipologie: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore database']);
    }
}

/**
 * Crea una nuova tipologia
 */
function createTipologia() {
    global $pdo;
    
    $nome = trim($_POST['nome'] ?? '');
    $colore = trim($_POST['colore'] ?? '#64748b');
    
    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Nome obbligatorio']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO scadenze_tipologie (nome, colore) VALUES (?, ?)");
        $stmt->execute([$nome, $colore]);
        
        $id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Tipologia creata', 'id' => $id]);
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Tipologia già esistente']);
        } else {
            error_log("Errore create tipologia: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Errore durante la creazione']);
        }
    }
}

/**
 * Aggiorna una tipologia
 */
function updateTipologia() {
    global $pdo;
    
    $id = intval($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $colore = trim($_POST['colore'] ?? '#64748b');
    
    if (!$id || empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Dati non validi']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE scadenze_tipologie SET nome = ?, colore = ? WHERE id = ?");
        $stmt->execute([$nome, $colore, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Tipologia aggiornata']);
        
    } catch (PDOException $e) {
        error_log("Errore update tipologia: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore durante aggiornamento']);
    }
}

/**
 * Elimina una tipologia
 */
function deleteTipologia() {
    global $pdo;
    
    $id = intval($_POST['id'] ?? 0);
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID richiesto']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM scadenze_tipologie WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Tipologia eliminata']);
        
    } catch (PDOException $e) {
        error_log("Errore delete tipologia: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore durante eliminazione']);
    }
}
