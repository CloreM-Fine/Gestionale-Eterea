<?php
/**
 * Eterea Gestionale
 * API Blog Clienti
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Per azioni pubbliche (upload cliente), non serve auth
$isPublicAction = in_array($action, ['get_by_token', 'upload_contenuto']);

if (!$isPublicAction) {
    // Accesso solo per Lorenzo
    $userId = $_SESSION['user_id'] ?? '';
    if ($userId !== 'ucwurog3xr8tf') {
        jsonResponse(false, null, 'Accesso non autorizzato');
    }
}

switch ($method) {
    case 'GET':
        if ($action === 'list') {
            listContenuti();
        } elseif ($action === 'stats') {
            getStats();
        } elseif ($action === 'get_by_token' && isset($_GET['token'])) {
            getByToken($_GET['token']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    case 'POST':
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($csrfToken) || !verifyCsrfToken($csrfToken)) {
            if (!$isPublicAction) {
                jsonResponse(false, null, 'Token CSRF non valido');
                break;
            }
        }
        
        if ($action === 'genera_link') {
            generaLink();
        } elseif ($action === 'upload_contenuto') {
            uploadContenuto();
        } elseif ($action === 'segna_letto' && isset($_POST['id'])) {
            segnaLetto($_POST['id']);
        } elseif ($action === 'archivia' && isset($_POST['id'])) {
            cambiaStato($_POST['id'], 'archiviato');
        } elseif ($action === 'ripristina' && isset($_POST['id'])) {
            cambiaStato($_POST['id'], 'attivo');
        } elseif ($action === 'elimina' && isset($_POST['id'])) {
            eliminaContenuto($_POST['id']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Lista contenuti con filtri
 */
function listContenuti() {
    global $pdo;
    
    try {
        $where = ["c.stato != 'eliminato'"];
        $params = [];
        
        if (!empty($_GET['cliente_id'])) {
            $where[] = "c.cliente_id = ?";
            $params[] = $_GET['cliente_id'];
        }
        
        if (!empty($_GET['stato'])) {
            if ($_GET['stato'] === 'da_leggere') {
                $where[] = "c.letto = 0";
            } elseif ($_GET['stato'] === 'letto') {
                $where[] = "c.letto = 1";
            } else {
                $where[] = "c.stato = ?";
                $params[] = $_GET['stato'];
            }
        }
        
        $sql = "
            SELECT c.*, cl.ragione_sociale as cliente_nome, cl.logo_path as cliente_logo
            FROM cliente_contenuti c
            LEFT JOIN clienti cl ON c.cliente_id = cl.id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY c.created_at DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $contenuti = $stmt->fetchAll();
        
        jsonResponse(true, $contenuti);
        
    } catch (PDOException $e) {
        error_log("Errore lista contenuti: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento contenuti');
    }
}

/**
 * Statistiche
 */
function getStats() {
    global $pdo;
    
    try {
        // Totali
        $stmt = $pdo->query("SELECT COUNT(*) FROM cliente_contenuti WHERE stato != 'eliminato'");
        $totali = $stmt->fetchColumn();
        
        // Da leggere
        $stmt = $pdo->query("SELECT COUNT(*) FROM cliente_contenuti WHERE letto = 0 AND stato = 'attivo'");
        $daLeggere = $stmt->fetchColumn();
        
        // Clienti attivi (con contenuti)
        $stmt = $pdo->query("SELECT COUNT(DISTINCT cliente_id) FROM cliente_contenuti WHERE stato != 'eliminato'");
        $clientiAttivi = $stmt->fetchColumn();
        
        // Ultimi 7 giorni
        $stmt = $pdo->query("SELECT COUNT(*) FROM cliente_contenuti WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $recenti = $stmt->fetchColumn();
        
        jsonResponse(true, [
            'totali' => $totali,
            'da_leggere' => $daLeggere,
            'clienti_attivi' => $clientiAttivi,
            'recenti' => $recenti
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore stats: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento statistiche');
    }
}

/**
 * Genera link per cliente
 */
function generaLink() {
    global $pdo;
    
    $clienteId = $_POST['cliente_id'] ?? '';
    $note = $_POST['note'] ?? '';
    
    if (empty($clienteId)) {
        jsonResponse(false, null, 'Cliente obbligatorio');
    }
    
    try {
        // Verifica cliente esiste
        $stmt = $pdo->prepare("SELECT id FROM clienti WHERE id = ?");
        $stmt->execute([$clienteId]);
        if (!$stmt->fetch()) {
            jsonResponse(false, null, 'Cliente non trovato');
        }
        
        // Genera token univoco
        $token = bin2hex(random_bytes(32));
        $id = generateEntityId('ccnt');
        
        $stmt = $pdo->prepare("
            INSERT INTO cliente_contenuti (id, cliente_id, token, stato, created_by)
            VALUES (?, ?, ?, 'attivo', ?)
        ");
        $stmt->execute([$id, $clienteId, $token, $_SESSION['user_id'] ?? null]);
        
        // URL pubblico
        $url = BASE_URL . '/upload_cliente.php?token=' . $token;
        
        jsonResponse(true, ['url' => $url, 'token' => $token]);
        
    } catch (PDOException $e) {
        error_log("Errore genera link: " . $e->getMessage());
        jsonResponse(false, null, 'Errore generazione link');
    }
}

/**
 * Recupera contenuto by token (per pagina pubblica)
 */
function getByToken($token) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, cl.ragione_sociale as cliente_nome
            FROM cliente_contenuti c
            LEFT JOIN clienti cl ON c.cliente_id = cl.id
            WHERE c.token = ? AND c.stato = 'attivo'
        ");
        $stmt->execute([$token]);
        $contenuto = $stmt->fetch();
        
        if (!$contenuto) {
            jsonResponse(false, null, 'Link non valido o scaduto');
        }
        
        jsonResponse(true, $contenuto);
        
    } catch (PDOException $e) {
        error_log("Errore get by token: " . $e->getMessage());
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Upload contenuto da cliente (pagina pubblica)
 */
function uploadContenuto() {
    global $pdo;
    
    $token = $_POST['token'] ?? '';
    $titolo = trim($_POST['titolo'] ?? '');
    $testo = trim($_POST['testo'] ?? '');
    
    if (empty($token)) {
        jsonResponse(false, null, 'Token mancante');
    }
    
    try {
        // Verifica token
        $stmt = $pdo->prepare("
            SELECT id, cliente_id FROM cliente_contenuti 
            WHERE token = ? AND stato = 'attivo' AND (titolo IS NULL OR titolo = '')
        ");
        $stmt->execute([$token]);
        $contenuto = $stmt->fetch();
        
        if (!$contenuto) {
            jsonResponse(false, null, 'Link non valido o già utilizzato');
        }
        
        $contenutoId = $contenuto['id'];
        
        // Gestisci upload immagini
        $immagini = [];
        $uploadDir = __DIR__ . '/../assets/uploads/clienti_contenuti/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Conta immagini già caricate
        $stmt = $pdo->prepare("SELECT immagini FROM cliente_contenuti WHERE id = ?");
        $stmt->execute([$contenutoId]);
        $existing = $stmt->fetch();
        $existingImages = json_decode($existing['immagini'] ?? '[]', true);
        $currentCount = count($existingImages);
        
        // Max 10 immagini totali
        $maxImages = 10;
        $remainingSlots = $maxImages - $currentCount;
        
        if ($remainingSlots <= 0) {
            jsonResponse(false, null, 'Limite di 10 immagini raggiunto');
        }
        
        // Processa nuove immagini
        if (!empty($_FILES['immagini'])) {
            $files = $_FILES['immagini'];
            $fileCount = is_array($files['name']) ? count($files['name']) : 1;
            
            for ($i = 0; $i < min($fileCount, $remainingSlots); $i++) {
                $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $fileTmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];
                $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
                
                if ($fileError !== UPLOAD_ERR_OK) continue;
                
                // Verifica tipo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $fileTmp);
                finfo_close($finfo);
                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                if (!in_array($mimeType, $allowedTypes)) {
                    continue;
                }
                
                // Verifica dimensione (8MB)
                if ($fileSize > 8 * 1024 * 1024) {
                    continue;
                }
                
                // Genera nome file
                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $newName = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $destPath = $uploadDir . $newName;
                
                if (move_uploaded_file($fileTmp, $destPath)) {
                    $immagini[] = $newName;
                }
            }
        }
        
        // Merge con immagini esistenti
        $allImmagini = array_merge($existingImages, $immagini);
        
        // Aggiorna contenuto
        $stmt = $pdo->prepare("
            UPDATE cliente_contenuti 
            SET titolo = ?, testo = ?, immagini = ?, letto = 0
            WHERE id = ?
        ");
        $stmt->execute([
            $titolo ?: 'Contenuto del cliente',
            $testo,
            json_encode($allImmagini),
            $contenutoId
        ]);
        
        jsonResponse(true, null, 'Contenuto caricato con successo!');
        
    } catch (PDOException $e) {
        error_log("Errore upload contenuto: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento contenuto');
    }
}

/**
 * Segna come letto
 */
function segnaLetto($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE cliente_contenuti SET letto = 1 WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, null);
    } catch (PDOException $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Cambia stato
 */
function cambiaStato($id, $stato) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE cliente_contenuti SET stato = ? WHERE id = ?");
        $stmt->execute([$stato, $id]);
        jsonResponse(true, null);
    } catch (PDOException $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Elimina contenuto
 */
function eliminaContenuto($id) {
    global $pdo;
    
    try {
        // Recupera immagini per eliminarle fisicamente
        $stmt = $pdo->prepare("SELECT immagini FROM cliente_contenuti WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $immagini = json_decode($row['immagini'] ?? '[]', true);
            $uploadDir = __DIR__ . '/../assets/uploads/clienti_contenuti/';
            
            foreach ($immagini as $img) {
                $path = $uploadDir . $img;
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }
        
        // Elimina record
        $stmt = $pdo->prepare("DELETE FROM cliente_contenuti WHERE id = ?");
        $stmt->execute([$id]);
        
        jsonResponse(true, null);
    } catch (PDOException $e) {
        error_log("Errore elimina contenuto: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione');
    }
}
