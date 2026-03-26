<?php
/**
 * Eterea Gestionale
 * API Piano Editoriale
 * 
 * Endpoint per la gestione completa del piano editoriale social media
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'list':
                getPosts();
                break;
            case 'detail':
                getPostDetail($_GET['id'] ?? '');
                break;
            case 'stats':
                getStats();
                break;
            case 'templates':
                getTemplates();
                break;
            default:
                jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    case 'POST':
        // Verifica CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($csrfToken)) {
            jsonResponse(false, null, 'Token CSRF non valido');
        }
        
        switch ($action) {
            case 'create':
                createPost();
                break;
            case 'update':
                updatePost($_POST['id'] ?? '');
                break;
            case 'delete':
                deletePost($_POST['id'] ?? '');
                break;
            case 'change_stato':
                changeStato($_POST['id'] ?? '', $_POST['stato'] ?? '');
                break;
            case 'upload_contenuto':
                uploadContenuto($_POST['post_id'] ?? '');
                break;
            case 'delete_contenuto':
                deleteContenuto($_POST['contenuto_id'] ?? '');
                break;
            case 'approva':
                approvaPost($_POST['id'] ?? '');
                break;
            case 'save_template':
                saveTemplate();
                break;
            case 'update_metriche':
                updateMetriche($_POST['id'] ?? '');
                break;
            default:
                // Se non c'è azione specifica, controlla se è un create o update
                if (!empty($_POST['id'])) {
                    updatePost($_POST['id']);
                } else {
                    createPost();
                }
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non supportato');
}

/**
 * Recupera lista posts con filtri
 */
function getPosts() {
    global $pdo;
    
    $progettoId = $_GET['progetto_id'] ?? '';
    $piattaforma = $_GET['piattaforma'] ?? '';
    $stato = $_GET['stato'] ?? '';
    $mese = $_GET['mese'] ?? date('Y-m');
    
    try {
        $where = ['1=1'];
        $params = [];
        
        if ($progettoId) {
            $where[] = 'pe.progetto_id = ?';
            $params[] = $progettoId;
        }
        
        if ($piattaforma) {
            $where[] = 'pe.piattaforma = ?';
            $params[] = $piattaforma;
        }
        
        if ($stato) {
            $where[] = 'pe.stato = ?';
            $params[] = $stato;
        }
        
        if ($mese) {
            $where[] = 'DATE_FORMAT(pe.data_prevista, "%Y-%m") = ?';
            $params[] = $mese;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "
            SELECT 
                pe.*,
                p.titolo as progetto_titolo,
                c.ragione_sociale as cliente_nome,
                u1.nome as creato_da_nome,
                u2.nome as assegnato_a_nome,
                u3.nome as approvato_da_nome
            FROM piano_editoriale pe
            LEFT JOIN progetti p ON pe.progetto_id = p.id
            LEFT JOIN clienti c ON pe.cliente_id = c.id
            LEFT JOIN utenti u1 ON pe.creato_da = u1.id
            LEFT JOIN utenti u2 ON pe.assegnato_a = u2.id
            LEFT JOIN utenti u3 ON pe.approvato_da = u3.id
            WHERE {$whereClause}
            ORDER BY pe.data_prevista ASC, pe.ora_prevista ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $posts = $stmt->fetchAll();
        
        // Per ogni post, recupera i contenuti allegati
        foreach ($posts as &$post) {
            $stmt = $pdo->prepare("
                SELECT * FROM piano_editoriale_contenuti 
                WHERE post_id = ? 
                ORDER BY ordine ASC
            ");
            $stmt->execute([$post['id']]);
            $post['contenuti'] = $stmt->fetchAll();
        }
        
        jsonResponse(true, $posts);
        
    } catch (PDOException $e) {
        error_log("Errore getPosts: " . $e->getMessage());
        jsonResponse(false, null, 'Errore recupero posts');
    }
}

/**
 * Recupera dettaglio singolo post
 */
function getPostDetail($id) {
    global $pdo;
    
    if (empty($id)) {
        jsonResponse(false, null, 'ID post mancante');
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                pe.*,
                p.titolo as progetto_titolo,
                c.ragione_sociale as cliente_nome,
                u1.nome as creato_da_nome,
                u2.nome as assegnato_a_nome,
                u3.nome as approvato_da_nome
            FROM piano_editoriale pe
            LEFT JOIN progetti p ON pe.progetto_id = p.id
            LEFT JOIN clienti c ON pe.cliente_id = c.id
            LEFT JOIN utenti u1 ON pe.creato_da = u1.id
            LEFT JOIN utenti u2 ON pe.assegnato_a = u2.id
            LEFT JOIN utenti u3 ON pe.approvato_da = u3.id
            WHERE pe.id = ?
        ");
        $stmt->execute([$id]);
        $post = $stmt->fetch();
        
        if (!$post) {
            jsonResponse(false, null, 'Post non trovato');
        }
        
        // Recupera contenuti
        $stmt = $pdo->prepare("
            SELECT * FROM piano_editoriale_contenuti 
            WHERE post_id = ? 
            ORDER BY ordine ASC
        ");
        $stmt->execute([$id]);
        $post['contenuti'] = $stmt->fetchAll();
        
        // Recupera storico approvazioni
        $stmt = $pdo->prepare("
            SELECT pa.*, u.nome as utente_nome
            FROM piano_editoriale_approvazioni pa
            LEFT JOIN utenti u ON pa.utente_id = u.id
            WHERE pa.post_id = ?
            ORDER BY pa.created_at DESC
        ");
        $stmt->execute([$id]);
        $post['approvazioni'] = $stmt->fetchAll();
        
        jsonResponse(true, $post);
        
    } catch (PDOException $e) {
        error_log("Errore getPostDetail: " . $e->getMessage());
        jsonResponse(false, null, 'Errore recupero dettaglio post');
    }
}

/**
 * Crea nuovo post
 */
function createPost() {
    global $pdo;
    
    try {
        // Validazione campi obbligatori
        $required = ['progetto_id', 'titolo', 'piattaforma', 'tipologia', 'stato', 'data_prevista'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                jsonResponse(false, null, "Campo obbligatorio mancante: {$field}");
            }
        }
        
        // Recupera cliente_id dal progetto
        $stmt = $pdo->prepare("SELECT cliente_id FROM progetti WHERE id = ?");
        $stmt->execute([$_POST['progetto_id']]);
        $progetto = $stmt->fetch();
        $clienteId = $progetto['cliente_id'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO piano_editoriale (
                progetto_id, cliente_id, titolo, descrizione, piattaforma, tipologia, stato,
                data_prevista, ora_prevista, creato_da, assegnato_a, hashtag, menzioni,
                note, is_sponsored, budget_sponsorizzato, link_esterno
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['progetto_id'],
            $clienteId,
            $_POST['titolo'],
            $_POST['descrizione'] ?? '',
            $_POST['piattaforma'],
            $_POST['tipologia'],
            $_POST['stato'],
            $_POST['data_prevista'],
            $_POST['ora_prevista'] ?: null,
            $_SESSION['user_id'],
            $_POST['assegnato_a'] ?: null,
            $_POST['hashtag'] ?? '',
            $_POST['menzioni'] ?? '',
            $_POST['note'] ?? '',
            isset($_POST['is_sponsored']) ? 1 : 0,
            $_POST['budget_sponsorizzato'] ?: null,
            $_POST['link_esterno'] ?? ''
        ]);
        
        $postId = $pdo->lastInsertId();
        
        // Gestione upload contenuti
        if (!empty($_FILES['contenuti'])) {
            handleContenutiUpload($postId, $_FILES['contenuti']);
        }
        
        // Log
        logTimeline($_SESSION['user_id'], 'creato_post_editoriale', 'piano_editoriale', $postId, "Creato post: {$_POST['titolo']}");
        
        jsonResponse(true, ['id' => $postId], 'Post creato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore createPost: " . $e->getMessage());
        jsonResponse(false, null, 'Errore creazione post');
    }
}

/**
 * Aggiorna post esistente
 */
function updatePost($id) {
    global $pdo;
    
    if (empty($id)) {
        jsonResponse(false, null, 'ID post mancante');
    }
    
    try {
        // Verifica esistenza
        $stmt = $pdo->prepare("SELECT id FROM piano_editoriale WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            jsonResponse(false, null, 'Post non trovato');
        }
        
        $stmt = $pdo->prepare("
            UPDATE piano_editoriale SET
                progetto_id = ?,
                titolo = ?,
                descrizione = ?,
                piattaforma = ?,
                tipologia = ?,
                stato = ?,
                data_prevista = ?,
                ora_prevista = ?,
                assegnato_a = ?,
                hashtag = ?,
                menzioni = ?,
                note = ?,
                is_sponsored = ?,
                budget_sponsorizzato = ?,
                link_esterno = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['progetto_id'],
            $_POST['titolo'],
            $_POST['descrizione'] ?? '',
            $_POST['piattaforma'],
            $_POST['tipologia'],
            $_POST['stato'],
            $_POST['data_prevista'],
            $_POST['ora_prevista'] ?: null,
            $_POST['assegnato_a'] ?: null,
            $_POST['hashtag'] ?? '',
            $_POST['menzioni'] ?? '',
            $_POST['note'] ?? '',
            isset($_POST['is_sponsored']) ? 1 : 0,
            $_POST['budget_sponsorizzato'] ?: null,
            $_POST['link_esterno'] ?? '',
            $id
        ]);
        
        // Gestione upload contenuti
        if (!empty($_FILES['contenuti'])) {
            handleContenutiUpload($id, $_FILES['contenuti']);
        }
        
        // Log
        logTimeline($_SESSION['user_id'], 'aggiornato_post_editoriale', 'piano_editoriale', $id, "Aggiornato post: {$_POST['titolo']}");
        
        jsonResponse(true, ['id' => $id], 'Post aggiornato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore updatePost: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento post');
    }
}

/**
 * Elimina post
 */
function deletePost($id) {
    global $pdo;
    
    if (empty($id)) {
        jsonResponse(false, null, 'ID post mancante');
    }
    
    try {
        // Recupera info per log
        $stmt = $pdo->prepare("SELECT titolo FROM piano_editoriale WHERE id = ?");
        $stmt->execute([$id]);
        $post = $stmt->fetch();
        
        if (!$post) {
            jsonResponse(false, null, 'Post non trovato');
        }
        
        // Elimina contenuti fisici
        $stmt = $pdo->prepare("SELECT file_path FROM piano_editoriale_contenuti WHERE post_id = ?");
        $stmt->execute([$id]);
        $contenuti = $stmt->fetchAll();
        
        foreach ($contenuti as $c) {
            $filePath = __DIR__ . '/../assets/uploads/piano_editoriale/' . $c['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Elimina record (cascade su contenuti e approvazioni)
        $stmt = $pdo->prepare("DELETE FROM piano_editoriale WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'eliminato_post_editoriale', 'piano_editoriale', $id, "Eliminato post: {$post['titolo']}");
        
        jsonResponse(true, null, 'Post eliminato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore deletePost: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione post');
    }
}

/**
 * Cambia stato post
 */
function changeStato($id, $nuovoStato) {
    global $pdo;
    
    if (empty($id) || empty($nuovoStato)) {
        jsonResponse(false, null, 'Parametri mancanti');
    }
    
    $statiValidi = ['bozza', 'in_revisione', 'approvato', 'programmato', 'pubblicato', 'archiviato'];
    if (!in_array($nuovoStato, $statiValidi)) {
        jsonResponse(false, null, 'Stato non valido');
    }
    
    try {
        $dataPubblicazione = ($nuovoStato === 'pubblicato') ? date('Y-m-d H:i:s') : null;
        
        $stmt = $pdo->prepare("
            UPDATE piano_editoriale 
            SET stato = ?, data_pubblicazione = ?
            WHERE id = ?
        ");
        $stmt->execute([$nuovoStato, $dataPubblicazione, $id]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'cambiato_stato_post', 'piano_editoriale', $id, "Stato cambiato in: {$nuovoStato}");
        
        jsonResponse(true, null, 'Stato aggiornato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore changeStato: " . $e->getMessage());
        jsonResponse(false, null, 'Errore cambio stato');
    }
}

/**
 * Approva post
 */
function approvaPost($id) {
    global $pdo;
    
    if (empty($id)) {
        jsonResponse(false, null, 'ID post mancante');
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE piano_editoriale 
            SET stato = 'approvato', approvato_da = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $id]);
        
        // Aggiungi a storico approvazioni
        $stmt = $pdo->prepare("
            INSERT INTO piano_editoriale_approvazioni (post_id, utente_id, azione, commento)
            VALUES (?, ?, 'approvato', ?)
        ");
        $stmt->execute([$id, $_SESSION['user_id'], $_POST['commento'] ?? null]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'approvato_post', 'piano_editoriale', $id, 'Post approvato');
        
        jsonResponse(true, null, 'Post approvato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore approvaPost: " . $e->getMessage());
        jsonResponse(false, null, 'Errore approvazione post');
    }
}

/**
 * Gestione upload contenuti
 */
function handleContenutiUpload($postId, $files) {
    global $pdo;
    
    $uploadDir = __DIR__ . '/../assets/uploads/piano_editoriale/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Recupera ordine massimo attuale
    $stmt = $pdo->prepare("SELECT MAX(ordine) as max_ordine FROM piano_editoriale_contenuti WHERE post_id = ?");
    $stmt->execute([$postId]);
    $result = $stmt->fetch();
    $ordine = ($result['max_ordine'] ?? 0) + 1;
    
    // Gestione multi-file
    $fileCount = is_array($files['name']) ? count($files['name']) : 1;
    
    for ($i = 0; $i < $fileCount; $i++) {
        $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
        $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $type = is_array($files['type']) ? $files['type'][$i] : $files['type'];
        $size = is_array($files['size']) ? $files['size'][$i] : $files['size'];
        $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
        
        if ($error !== UPLOAD_ERR_OK) continue;
        
        // Validazione tipo file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/quicktime'];
        if (!in_array($type, $allowedTypes)) {
            continue;
        }
        
        // Genera nome file univoco
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $filename = 'pe_' . $postId . '_' . time() . '_' . $i . '.' . $ext;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($tmpName, $filepath)) {
            $tipo = (strpos($type, 'video') !== false) ? 'video' : 'immagine';
            
            $stmt = $pdo->prepare("
                INSERT INTO piano_editoriale_contenuti 
                (post_id, filename, file_path, file_type, file_size, tipo, ordine, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $postId,
                $name,
                $filename,
                $type,
                $size,
                $tipo,
                $ordine++,
                $_SESSION['user_id']
            ]);
        }
    }
}

/**
 * Elimina contenuto
 */
function deleteContenuto($contenutoId) {
    global $pdo;
    
    if (empty($contenutoId)) {
        jsonResponse(false, null, 'ID contenuto mancante');
    }
    
    try {
        $stmt = $pdo->prepare("SELECT file_path FROM piano_editoriale_contenuti WHERE id = ?");
        $stmt->execute([$contenutoId]);
        $contenuto = $stmt->fetch();
        
        if ($contenuto) {
            $filePath = __DIR__ . '/../assets/uploads/piano_editoriale/' . $contenuto['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $stmt = $pdo->prepare("DELETE FROM piano_editoriale_contenuti WHERE id = ?");
            $stmt->execute([$contenutoId]);
        }
        
        jsonResponse(true, null, 'Contenuto eliminato');
        
    } catch (PDOException $e) {
        error_log("Errore deleteContenuto: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione contenuto');
    }
}

/**
 * Aggiorna metriche post
 */
function updateMetriche($id) {
    global $pdo;
    
    if (empty($id)) {
        jsonResponse(false, null, 'ID post mancante');
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE piano_editoriale SET
                impressions = ?,
                reach = ?,
                engagement = ?,
                likes = ?,
                comments = ?,
                shares = ?,
                saves = ?,
                clicks = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['impressions'] ?: null,
            $_POST['reach'] ?: null,
            $_POST['engagement'] ?: null,
            $_POST['likes'] ?: null,
            $_POST['comments'] ?: null,
            $_POST['shares'] ?: null,
            $_POST['saves'] ?: null,
            $_POST['clicks'] ?: null,
            $id
        ]);
        
        jsonResponse(true, null, 'Metriche aggiornate');
        
    } catch (PDOException $e) {
        error_log("Errore updateMetriche: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento metriche');
    }
}

/**
 * Recupera statistiche
 */
function getStats() {
    global $pdo;
    
    $progettoId = $_GET['progetto_id'] ?? '';
    $mese = $_GET['mese'] ?? date('Y-m');
    
    try {
        $where = 'DATE_FORMAT(data_prevista, "%Y-%m") = ?';
        $params = [$mese];
        
        if ($progettoId) {
            $where .= ' AND progetto_id = ?';
            $params[] = $progettoId;
        }
        
        // Conteggio per stato
        $stmt = $pdo->prepare("
            SELECT stato, COUNT(*) as count 
            FROM piano_editoriale 
            WHERE {$where}
            GROUP BY stato
        ");
        $stmt->execute($params);
        $perStato = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Conteggio per piattaforma
        $stmt = $pdo->prepare("
            SELECT piattaforma, COUNT(*) as count 
            FROM piano_editoriale 
            WHERE {$where}
            GROUP BY piattaforma
        ");
        $stmt->execute($params);
        $perPiattaforma = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Totale sponsorizzato
        $stmt = $pdo->prepare("
            SELECT SUM(budget_sponsorizzato) as totale
            FROM piano_editoriale 
            WHERE {$where} AND is_sponsored = 1
        ");
        $stmt->execute($params);
        $totaleSponsorizzato = $stmt->fetchColumn() ?: 0;
        
        jsonResponse(true, [
            'per_stato' => $perStato,
            'per_piattaforma' => $perPiattaforma,
            'totale_sponsorizzato' => $totaleSponsorizzato
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore getStats: " . $e->getMessage());
        jsonResponse(false, null, 'Errore recupero statistiche');
    }
}

/**
 * Recupera templates
 */
function getTemplates() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT t.*, u.nome as created_by_nome
            FROM piano_editoriale_template t
            LEFT JOIN utenti u ON t.created_by = u.id
            WHERE t.is_active = 1
            ORDER BY t.created_at DESC
        ");
        $templates = $stmt->fetchAll();
        
        jsonResponse(true, $templates);
        
    } catch (PDOException $e) {
        error_log("Errore getTemplates: " . $e->getMessage());
        jsonResponse(false, null, 'Errore recupero templates');
    }
}

/**
 * Salva template
 */
function saveTemplate() {
    global $pdo;
    
    if (empty($_POST['nome'])) {
        jsonResponse(false, null, 'Nome template obbligatorio');
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO piano_editoriale_template
            (nome, descrizione, titolo_template, testo_template, piattaforma, tipologia, hashtag_default, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['nome'],
            $_POST['descrizione'] ?? '',
            $_POST['titolo_template'] ?? '',
            $_POST['testo_template'] ?? '',
            $_POST['piattaforma'] ?? 'multi',
            $_POST['tipologia'] ?? 'feed',
            $_POST['hashtag_default'] ?? '',
            $_SESSION['user_id']
        ]);
        
        jsonResponse(true, ['id' => $pdo->lastInsertId()], 'Template salvato');
        
    } catch (PDOException $e) {
        error_log("Errore saveTemplate: " . $e->getMessage());
        jsonResponse(false, null, 'Errore salvataggio template');
    }
}
