<?php
/**
 * Eterea Gestionale
 * API Listini Prezzi
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            listListini();
        } elseif ($action === 'detail' && !empty($_GET['id'])) {
            getListino($_GET['id']);
        } elseif ($action === 'servizi' && !empty($_GET['listino_id'])) {
            getServizi($_GET['listino_id']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    case 'POST':
        if ($action === 'create') {
            createListino();
        } elseif ($action === 'update' && !empty($_POST['id'])) {
            updateListino($_POST['id']);
        } elseif ($action === 'delete' && !empty($_POST['id'])) {
            deleteListino($_POST['id']);
        } elseif ($action === 'add_servizio') {
            addServizio();
        } elseif ($action === 'update_servizio' && !empty($_POST['servizio_id'])) {
            updateServizio($_POST['servizio_id']);
        } elseif ($action === 'delete_servizio' && !empty($_POST['servizio_id'])) {
            deleteServizio($_POST['servizio_id']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Lista di tutti i listini
 */
function listListini(): void {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM listini WHERE attivo = TRUE ORDER BY titolo ASC");
        $listini = $stmt->fetchAll();
        
        // Conta servizi per ogni listino
        foreach ($listini as &$l) {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM listini_servizi WHERE listino_id = ? AND attivo = TRUE");
            $stmtCount->execute([$l['id']]);
            $l['num_servizi'] = (int)$stmtCount->fetchColumn();
        }
        
        jsonResponse(true, $listini);
    } catch (PDOException $e) {
        error_log("Errore lista listini: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento listini');
    }
}

/**
 * Dettaglio listino con servizi
 */
function getListino(string $id): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM listini WHERE id = ?");
        $stmt->execute([$id]);
        $listino = $stmt->fetch();
        
        if (!$listino) {
            jsonResponse(false, null, 'Listino non trovato');
        }
        
        // Recupera servizi
        $stmt = $pdo->prepare("SELECT * FROM listini_servizi WHERE listino_id = ? ORDER BY ordine ASC, nome ASC");
        $stmt->execute([$id]);
        $listino['servizi'] = $stmt->fetchAll();
        
        jsonResponse(true, $listino);
    } catch (PDOException $e) {
        error_log("Errore dettaglio listino: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento listino');
    }
}

/**
 * Servizi di un listino
 */
function getServizi(string $listinoId): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM listini_servizi WHERE listino_id = ? AND attivo = TRUE ORDER BY ordine ASC, nome ASC");
        $stmt->execute([$listinoId]);
        $servizi = $stmt->fetchAll();
        
        jsonResponse(true, $servizi);
    } catch (PDOException $e) {
        error_log("Errore servizi: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento servizi');
    }
}

/**
 * Crea nuovo listino
 */
function createListino(): void {
    global $pdo;
    
    $titolo = trim($_POST['titolo'] ?? '');
    if (empty($titolo)) {
        jsonResponse(false, null, 'Il titolo è obbligatorio');
    }
    
    // Gestisci upload immagine
    $immaginePath = null;
    if (!empty($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['immagine'], 'listini', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 2 * 1024 * 1024);
        if ($upload) {
            $immaginePath = $upload['path'];
        }
    }
    
    try {
        $id = generateEntityId('lst');
        
        $stmt = $pdo->prepare("INSERT INTO listini (id, titolo, descrizione, colore, immagine, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id,
            $titolo,
            $_POST['descrizione'] ?? '',
            $_POST['colore'] ?? '#FFFFFF',
            $immaginePath,
            $_SESSION['user_id']
        ]);
        
        logTimeline($_SESSION['user_id'], 'creato_listino', 'listino', $id, "Creato listino: {$titolo}");
        
        jsonResponse(true, ['id' => $id], 'Listino creato con successo');
    } catch (PDOException $e) {
        error_log("Errore creazione listino: " . $e->getMessage());
        jsonResponse(false, null, 'Errore creazione listino');
    }
}

/**
 * Aggiorna listino
 */
function updateListino(string $id): void {
    global $pdo;
    
    // Verifica esistenza
    $stmt = $pdo->prepare("SELECT immagine FROM listini WHERE id = ?");
    $stmt->execute([$id]);
    $listino = $stmt->fetch();
    if (!$listino) {
        jsonResponse(false, null, 'Listino non trovato');
    }
    
    $titolo = trim($_POST['titolo'] ?? '');
    if (empty($titolo)) {
        jsonResponse(false, null, 'Il titolo è obbligatorio');
    }
    
    // Gestisci upload immagine
    $immaginePath = $listino['immagine'];
    if (!empty($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['immagine'], 'listini', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 2 * 1024 * 1024);
        if ($upload) {
            if ($immaginePath && file_exists(UPLOAD_PATH . $immaginePath)) {
                unlink(UPLOAD_PATH . $immaginePath);
            }
            $immaginePath = $upload['path'];
        }
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE listini SET titolo = ?, descrizione = ?, colore = ?, immagine = ? WHERE id = ?");
        $stmt->execute([
            $titolo,
            $_POST['descrizione'] ?? '',
            $_POST['colore'] ?? '#FFFFFF',
            $immaginePath,
            $id
        ]);
        
        logTimeline($_SESSION['user_id'], 'aggiornato_listino', 'listino', $id, "Aggiornato listino: {$titolo}");
        
        jsonResponse(true, ['id' => $id], 'Listino aggiornato con successo');
    } catch (PDOException $e) {
        error_log("Errore aggiornamento listino: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento listino');
    }
}

/**
 * Elimina listino (soft delete)
 */
function deleteListino(string $id): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE listini SET attivo = FALSE WHERE id = ?");
        $stmt->execute([$id]);
        
        logTimeline($_SESSION['user_id'], 'eliminato_listino', 'listino', $id, "Eliminato listino");
        
        jsonResponse(true, null, 'Listino eliminato con successo');
    } catch (PDOException $e) {
        error_log("Errore eliminazione listino: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione listino');
    }
}

/**
 * Aggiungi servizio a listino
 */
function addServizio(): void {
    global $pdo;
    
    $listinoId = $_POST['listino_id'] ?? '';
    $nome = trim($_POST['nome'] ?? '');
    $prezzo = floatval($_POST['prezzo'] ?? 0);
    
    if (empty($listinoId) || empty($nome)) {
        jsonResponse(false, null, 'Listino e nome servizio sono obbligatori');
    }
    
    try {
        // Trova ordine massimo
        $stmt = $pdo->prepare("SELECT MAX(ordine) FROM listini_servizi WHERE listino_id = ?");
        $stmt->execute([$listinoId]);
        $maxOrdine = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("INSERT INTO listini_servizi (listino_id, nome, descrizione, prezzo, durata_minuti, ordine) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $listinoId,
            $nome,
            $_POST['descrizione'] ?? '',
            $prezzo,
            $_POST['durata_minuti'] ?: null,
            $maxOrdine + 1
        ]);
        
        $servizioId = $pdo->lastInsertId();
        
        jsonResponse(true, ['id' => $servizioId], 'Servizio aggiunto con successo');
    } catch (PDOException $e) {
        error_log("Errore aggiunta servizio: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiunta servizio');
    }
}

/**
 * Aggiorna servizio
 */
function updateServizio(int $servizioId): void {
    global $pdo;
    
    $nome = trim($_POST['nome'] ?? '');
    $prezzo = floatval($_POST['prezzo'] ?? 0);
    
    if (empty($nome)) {
        jsonResponse(false, null, 'Il nome è obbligatorio');
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE listini_servizi SET nome = ?, descrizione = ?, prezzo = ?, durata_minuti = ? WHERE id = ?");
        $stmt->execute([
            $nome,
            $_POST['descrizione'] ?? '',
            $prezzo,
            $_POST['durata_minuti'] ?: null,
            $servizioId
        ]);
        
        jsonResponse(true, ['id' => $servizioId], 'Servizio aggiornato con successo');
    } catch (PDOException $e) {
        error_log("Errore aggiornamento servizio: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento servizio');
    }
}

/**
 * Elimina servizio
 */
function deleteServizio(int $servizioId): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM listini_servizi WHERE id = ?");
        $stmt->execute([$servizioId]);
        
        jsonResponse(true, null, 'Servizio eliminato con successo');
    } catch (PDOException $e) {
        error_log("Errore eliminazione servizio: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione servizio');
    }
}
