<?php
/**
 * Eterea Gestionale
 * API Clienti
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'detail' && isset($_GET['id'])) {
            getCliente($_GET['id']);
        } elseif ($action === 'list') {
            listClienti();
        } elseif ($action === 'search') {
            searchClienti();
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    case 'POST':
        error_log("POST request received - action: {$action}, id: " . ($_POST['id'] ?? 'none') . ", FILES: " . print_r($_FILES, true));
        if ($action === 'create') {
            createCliente();
        } elseif ($action === 'update' && isset($_POST['id'])) {
            updateCliente($_POST['id']);
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            deleteCliente($_POST['id']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Lista clienti
 */
function listClienti(): void {
    global $pdo;
    
    try {
        $where = [];
        $params = [];
        
        // Filtro tipo
        if (!empty($_GET['tipo'])) {
            $where[] = "tipo = ?";
            $params[] = $_GET['tipo'];
        }
        
        // Ricerca
        if (!empty($_GET['search'])) {
            $where[] = "(ragione_sociale LIKE ? OR email LIKE ? OR piva_cf LIKE ?)";
            $params[] = "%{$_GET['search']}%";
            $params[] = "%{$_GET['search']}%";
            $params[] = "%{$_GET['search']}%";
        }
        
        $sql = "
            SELECT c.id, c.ragione_sociale, c.tipo, c.piva_cf, c.email, c.telefono, c.cellulare, c.indirizzo, c.citta, c.cap, c.provincia, c.logo_path,
                   COUNT(p.id) as num_progetti,
                   SUM(CASE WHEN p.stato_progetto NOT IN ('consegnato','archiviato') THEN 1 ELSE 0 END) as progetti_attivi
            FROM clienti c
            LEFT JOIN progetti p ON c.id = p.cliente_id
        ";
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.ragione_sociale ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $clienti = $stmt->fetchAll();
        
        jsonResponse(true, $clienti);
        
    } catch (PDOException $e) {
        error_log("Errore lista clienti: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento clienti');
    }
}

/**
 * Ricerca clienti (per autocomplete)
 */
function searchClienti(): void {
    global $pdo;
    
    $q = $_GET['q'] ?? '';
    if (strlen($q) < 2) {
        jsonResponse(true, []);
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, ragione_sociale, email, telefono
            FROM clienti
            WHERE ragione_sociale LIKE ? OR email LIKE ?
            ORDER BY ragione_sociale ASC
            LIMIT 10
        ");
        $stmt->execute(["%{$q}%", "%{$q}%"]);
        $clienti = $stmt->fetchAll();
        
        jsonResponse(true, $clienti);
        
    } catch (PDOException $e) {
        error_log("Errore ricerca clienti: " . $e->getMessage());
        jsonResponse(false, null, 'Errore ricerca');
    }
}

/**
 * Dettaglio cliente
 */
function getCliente(string $id): void {
    global $pdo;
    
    try {
        // Cliente
        $stmt = $pdo->prepare("SELECT * FROM clienti WHERE id = ?");
        $stmt->execute([$id]);
        $cliente = $stmt->fetch();
        
        if (!$cliente) {
            jsonResponse(false, null, 'Cliente non trovato');
        }
        
        // Progetti
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   COUNT(t.id) as num_task,
                   SUM(CASE WHEN t.stato = 'completato' THEN 1 ELSE 0 END) as task_completati
            FROM progetti p
            LEFT JOIN task t ON p.id = t.progetto_id
            WHERE p.cliente_id = ?
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$id]);
        $cliente['progetti'] = $stmt->fetchAll();
        
        // Decodifica JSON
        foreach ($cliente['progetti'] as &$p) {
            $p['tipologie'] = json_decode($p['tipologie'] ?? '[]', true);
            $p['partecipanti'] = json_decode($p['partecipanti'] ?? '[]', true);
        }
        
        jsonResponse(true, $cliente);
        
    } catch (PDOException $e) {
        error_log("Errore dettaglio cliente: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento cliente');
    }
}

/**
 * Crea nuovo cliente
 */
function createCliente(): void {
    global $pdo;
    
    // Validazione
    $ragioneSociale = trim($_POST['ragione_sociale'] ?? '');
    
    if (empty($ragioneSociale)) {
        jsonResponse(false, null, 'La ragione sociale è obbligatoria');
    }
    
    try {
        $id = generateEntityId('clt');
        
        $stmt = $pdo->prepare("
            INSERT INTO clienti (
                id, ragione_sociale, tipo, piva_cf, indirizzo, citta, cap, provincia,
                telefono, cellulare, email, pec, instagram, facebook, linkedin, sito_web, note, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id,
            $ragioneSociale,
            $_POST['tipo'] ?? 'Azienda',
            $_POST['piva_cf'] ?? '',
            $_POST['indirizzo'] ?? '',
            $_POST['citta'] ?? '',
            $_POST['cap'] ?? '',
            $_POST['provincia'] ?? '',
            $_POST['telefono'] ?? '',
            $_POST['cellulare'] ?? '',
            $_POST['email'] ?? '',
            $_POST['pec'] ?? '',
            $_POST['instagram'] ?? '',
            $_POST['facebook'] ?? '',
            $_POST['linkedin'] ?? '',
            $_POST['sito_web'] ?? '',
            $_POST['note'] ?? '',
            $_SESSION['user_id']
        ]);
        
        // Gestisci upload logo se presente
        if (!empty($_FILES['logo'])) {
            error_log("Logo upload attempt - error code: " . $_FILES['logo']['error']);
            if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                error_log("Logo file: " . $_FILES['logo']['name'] . " size: " . $_FILES['logo']['size']);
                $upload = uploadFile($_FILES['logo'], 'clienti', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 2 * 1024 * 1024);
                if ($upload) {
                    error_log("Logo uploaded successfully to: " . $upload['path']);
                    $stmt = $pdo->prepare("UPDATE clienti SET logo_path = ? WHERE id = ?");
                    $stmt->execute([$upload['path'], $id]);
                } else {
                    error_log("Logo upload failed in uploadFile function");
                }
            } else {
                error_log("Logo upload error code: " . $_FILES['logo']['error']);
            }
        } else {
            error_log("No logo file in \$_FILES");
        }
        
        // Log
        logTimeline($_SESSION['user_id'], 'creato_cliente', 'cliente', $id, "Creato cliente: {$ragioneSociale}");
        
        // Crea notifica per tutti gli utenti
        creaNotifica(
            'cliente',
            'Nuovo Cliente',
            $ragioneSociale,
            'cliente',
            $id,
            $_SESSION['user_id']
        );
        
        jsonResponse(true, ['id' => $id], 'Cliente creato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore creazione cliente: " . $e->getMessage());
        jsonResponse(false, null, 'Errore creazione cliente');
    }
}

/**
 * Aggiorna cliente
 */
function updateCliente(string $id): void {
    global $pdo;
    
    // Verifica esistenza
    $stmt = $pdo->prepare("SELECT id FROM clienti WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        jsonResponse(false, null, 'Cliente non trovato');
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE clienti SET
                ragione_sociale = ?,
                tipo = ?,
                piva_cf = ?,
                indirizzo = ?,
                citta = ?,
                cap = ?,
                provincia = ?,
                telefono = ?,
                cellulare = ?,
                email = ?,
                pec = ?,
                instagram = ?,
                facebook = ?,
                linkedin = ?,
                sito_web = ?,
                note = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['ragione_sociale'],
            $_POST['tipo'],
            $_POST['piva_cf'] ?? '',
            $_POST['indirizzo'] ?? '',
            $_POST['citta'] ?? '',
            $_POST['cap'] ?? '',
            $_POST['provincia'] ?? '',
            $_POST['telefono'] ?? '',
            $_POST['cellulare'] ?? '',
            $_POST['email'] ?? '',
            $_POST['pec'] ?? '',
            $_POST['instagram'] ?? '',
            $_POST['facebook'] ?? '',
            $_POST['linkedin'] ?? '',
            $_POST['sito_web'] ?? '',
            $_POST['note'] ?? '',
            $id
        ]);
        
        // Gestisci upload logo se presente
        if (!empty($_FILES['logo'])) {
            error_log("Logo upload attempt in update - error code: " . $_FILES['logo']['error']);
            if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                error_log("Logo file: " . $_FILES['logo']['name'] . " size: " . $_FILES['logo']['size']);
                $upload = uploadFile($_FILES['logo'], 'clienti', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 2 * 1024 * 1024);
                if ($upload) {
                    error_log("Logo uploaded successfully to: " . $upload['path']);
                    // Elimina logo precedente se esiste
                    $stmt = $pdo->prepare("SELECT logo_path FROM clienti WHERE id = ?");
                    $stmt->execute([$id]);
                    $old = $stmt->fetch();
                    if ($old && $old['logo_path'] && file_exists(UPLOAD_PATH . $old['logo_path'])) {
                        unlink(UPLOAD_PATH . $old['logo_path']);
                    }
                    
                    $stmt = $pdo->prepare("UPDATE clienti SET logo_path = ? WHERE id = ?");
                    $stmt->execute([$upload['path'], $id]);
                } else {
                    error_log("Logo upload failed in uploadFile function");
                }
            } else {
                error_log("Logo upload error code: " . $_FILES['logo']['error']);
            }
        } else {
            error_log("No logo file in \$_FILES during update");
        }
        
        // Log
        logTimeline($_SESSION['user_id'], 'aggiornato_cliente', 'cliente', $id, "Aggiornato cliente: {$_POST['ragione_sociale']}");
        
        jsonResponse(true, ['id' => $id], 'Cliente aggiornato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore aggiornamento cliente: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento cliente');
    }
}

/**
 * Elimina cliente
 */
function deleteCliente(string $id): void {
    global $pdo;
    
    try {
        // Verifica esistenza
        $stmt = $pdo->prepare("SELECT ragione_sociale, logo_path FROM clienti WHERE id = ?");
        $stmt->execute([$id]);
        $cliente = $stmt->fetch();
        
        if (!$cliente) {
            jsonResponse(false, null, 'Cliente non trovato');
        }
        
        // Verifica se ha progetti
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM progetti WHERE cliente_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(false, null, 'Non è possibile eliminare un cliente con progetti associati');
        }
        
        // Elimina logo se presente
        if ($cliente['logo_path'] && file_exists(UPLOAD_PATH . $cliente['logo_path'])) {
            unlink(UPLOAD_PATH . $cliente['logo_path']);
        }
        
        // Elimina
        $stmt = $pdo->prepare("DELETE FROM clienti WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'eliminato_cliente', 'cliente', $id, "Eliminato cliente: {$cliente['ragione_sociale']}");
        
        jsonResponse(true, null, 'Cliente eliminato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore eliminazione cliente: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione cliente');
    }
}
