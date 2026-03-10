<?php
/**
 * Eterea Gestionale
 * API Blog Clienti
 */

require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Per azioni pubbliche (upload cliente), non serve auth
$isPublicAction = in_array($action, ['get_by_token', 'upload_contenuto']);

if (!$isPublicAction) {
    // Richiedi autenticazione per azioni protette
    require_once __DIR__ . '/../includes/auth_check.php';
    
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
        } elseif ($action === 'count_unread') {
            countUnread();
        } elseif ($action === 'list_links') {
            listLinks();
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
        } elseif ($action === 'elimina_link' && isset($_POST['id'])) {
            eliminaLink($_POST['id']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Lista contenuti con filtri
 * Mostra SOLO i record che hanno effettivamente del contenuto (titolo o immagini)
 */
function listContenuti() {
    global $pdo;
    
    try {
        $where = ["c.stato != 'eliminato'", "(c.titolo IS NOT NULL AND c.titolo != '')"];
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
        // Totali (SOLO con contenuto effettivo - titolo non vuoto)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM cliente_contenuti 
            WHERE stato != 'eliminato'
              AND titolo IS NOT NULL 
              AND titolo != ''
        ");
        $totali = $stmt->fetchColumn();
        
        // Da leggere (SOLO con contenuto effettivo)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM cliente_contenuti 
            WHERE letto = 0 
              AND stato = 'attivo'
              AND titolo IS NOT NULL 
              AND titolo != ''
        ");
        $daLeggere = $stmt->fetchColumn();
        
        // Clienti attivi (con contenuti effettivi)
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT cliente_id) 
            FROM cliente_contenuti 
            WHERE stato != 'eliminato'
              AND titolo IS NOT NULL 
              AND titolo != ''
        ");
        $clientiAttivi = $stmt->fetchColumn();
        
        // Ultimi 7 giorni (SOLO con contenuto effettivo)
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM cliente_contenuti 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND titolo IS NOT NULL 
              AND titolo != ''
        ");
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
 * Conta contenuti non letti (per badge notifica)
 */
function countUnread() {
    global $pdo;
    
    try {
        // Conta SOLO i contenuti effettivamente inviati (con titolo) e non letti
        $stmt = $pdo->query("
            SELECT COUNT(*) 
            FROM cliente_contenuti 
            WHERE letto = 0 
              AND stato = 'attivo'
              AND titolo IS NOT NULL 
              AND titolo != ''
        ");
        $count = $stmt->fetchColumn();
        
        jsonResponse(true, ['count' => (int)$count]);
        
    } catch (PDOException $e) {
        error_log("Errore count unread: " . $e->getMessage());
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Lista link generati
 */
function listLinks() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT c.id, c.token, c.cliente_id, c.created_at, c.titolo,
                   cl.ragione_sociale as cliente_nome,
                   CASE 
                       WHEN c.titolo IS NOT NULL AND c.titolo != '' THEN 'usato'
                       WHEN c.immagini IS NOT NULL AND c.immagini != '[]' THEN 'usato'
                       ELSE 'libero'
                   END as stato_link
            FROM cliente_contenuti c
            LEFT JOIN clienti cl ON c.cliente_id = cl.id
            WHERE c.stato = 'attivo'
            ORDER BY c.created_at DESC
            LIMIT 100
        ");
        $links = $stmt->fetchAll();
        
        // Aggiungi URL completo
        foreach ($links as &$link) {
            $link['url'] = BASE_URL . '/upload_cliente.php?token=' . $link['token'];
        }
        
        jsonResponse(true, $links);
        
    } catch (PDOException $e) {
        error_log("Errore lista links: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento link');
    }
}

/**
 * Genera link per cliente (o recupera esistente)
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
        $stmt = $pdo->prepare("SELECT ragione_sociale FROM clienti WHERE id = ?");
        $stmt->execute([$clienteId]);
        $cliente = $stmt->fetch();
        if (!$cliente) {
            jsonResponse(false, null, 'Cliente non trovato');
        }
        
        // Verifica se esiste già un link per questo cliente (vuoto, mai utilizzato)
        $stmt = $pdo->prepare("
            SELECT id, token FROM cliente_contenuti 
            WHERE cliente_id = ? AND stato = 'attivo' AND (titolo IS NULL OR titolo = '') AND (immagini IS NULL OR immagini = '[]')
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$clienteId]);
        $esistente = $stmt->fetch();
        
        if ($esistente) {
            // Restituisci link esistente
            $url = BASE_URL . '/upload_cliente.php?token=' . $esistente['token'];
            jsonResponse(true, [
                'url' => $url, 
                'token' => $esistente['token'],
                'esistente' => true,
                'message' => 'Link esistente recuperato per ' . $cliente['ragione_sociale']
            ]);
            return;
        }
        
        // Genera nuovo token
        $token = bin2hex(random_bytes(32));
        $id = generateEntityId('ccnt');
        
        $stmt = $pdo->prepare("
            INSERT INTO cliente_contenuti (id, cliente_id, token, stato, created_by)
            VALUES (?, ?, ?, 'attivo', ?)
        ");
        $stmt->execute([$id, $clienteId, $token, $_SESSION['user_id'] ?? null]);
        
        // URL pubblico
        $url = BASE_URL . '/upload_cliente.php?token=' . $token;
        
        jsonResponse(true, [
            'url' => $url, 
            'token' => $token,
            'esistente' => false,
            'message' => 'Nuovo link generato per ' . $cliente['ragione_sociale']
        ]);
        
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
    $autore = trim($_POST['autore'] ?? '');
    
    if (empty($token)) {
        jsonResponse(false, null, 'Token mancante');
    }
    
    if (empty($titolo)) {
        jsonResponse(false, null, 'Inserisci un titolo');
    }
    
    try {
        // Verifica token - ottiene cliente_id
        $stmt = $pdo->prepare("
            SELECT id, cliente_id, titolo, immagini FROM cliente_contenuti 
            WHERE token = ? AND stato = 'attivo'
        ");
        $stmt->execute([$token]);
        $contenuto = $stmt->fetch();
        
        if (!$contenuto) {
            jsonResponse(false, null, 'Link non valido o scaduto');
        }
        
        $clienteId = $contenuto['cliente_id'];
        $isLinkGiaUsato = !empty($contenuto['titolo']);
        
        // Gestisci upload immagini
        $immagini = [];
        $uploadDir = __DIR__ . '/../assets/uploads/clienti_contenuti/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Max 10 immagini per invio
        $maxImages = 10;
        
        // Processa nuove immagini
        if (!empty($_FILES['immagini'])) {
            $files = $_FILES['immagini'];
            $fileCount = is_array($files['name']) ? count($files['name']) : 1;
            
            for ($i = 0; $i < min($fileCount, $maxImages); $i++) {
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
        
        $immaginiJson = json_encode($immagini);
        
        if ($isLinkGiaUsato) {
            // Link già usato: crea NUOVO contenuto separato
            $newToken = bin2hex(random_bytes(16));
            $newId = generateEntityId('cc');
            
            $stmt = $pdo->prepare("
                INSERT INTO cliente_contenuti (id, cliente_id, token, titolo, autore, testo, immagini, stato, letto, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'attivo', 0, NOW())
            ");
            $stmt->execute([
                $newId,
                $clienteId,
                $newToken,
                $titolo,
                $autore,
                $testo,
                $immaginiJson
            ]);
        } else {
            // Primo uso del link: aggiorna record esistente
            $stmt = $pdo->prepare("
                UPDATE cliente_contenuti 
                SET titolo = ?, autore = ?, testo = ?, immagini = ?, letto = 0
                WHERE id = ?
            ");
            $stmt->execute([
                $titolo,
                $autore,
                $testo,
                $immaginiJson,
                $contenuto['id']
            ]);
        }
        
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
 * Elimina contenuto - mantiene il link attivo per nuovi invii
 * Non elimina il record, solo resetta i dati del contenuto
 */
function eliminaContenuto($id) {
    global $pdo;
    
    try {
        // Recupera immagini per eliminarle fisicamente
        $stmt = $pdo->prepare("SELECT immagini, token FROM cliente_contenuti WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        
        if (!$row) {
            jsonResponse(false, null, 'Contenuto non trovato');
            return;
        }
        
        // Elimina file immagini
        $immagini = json_decode($row['immagini'] ?? '[]', true);
        $uploadDir = __DIR__ . '/../assets/uploads/clienti_contenuti/';
        foreach ($immagini as $img) {
            $path = $uploadDir . $img;
            if (file_exists($path)) {
                unlink($path);
            }
        }
        
        // RESET del contenuto (non eliminare il record!)
        // Mantieni token, cliente_id, stato='attivo' per permettere nuovi invii
        $stmt = $pdo->prepare("
            UPDATE cliente_contenuti 
            SET titolo = NULL, 
                testo = NULL, 
                immagini = '[]', 
                autore = NULL,
                letto = 0
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        
        jsonResponse(true, null);
    } catch (PDOException $e) {
        error_log("Errore elimina contenuto: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione');
    }
}

/**
 * Elimina un link (e i suoi contenuti associati)
 */
function eliminaLink($id) {
    global $pdo;
    
    try {
        // Recupera immagini associate per eliminarle fisicamente
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
        error_log("Errore elimina link: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione link');
    }
}
