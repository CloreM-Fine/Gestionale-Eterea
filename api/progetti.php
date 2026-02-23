<?php
/**
 * Eterea Gestionale
 * API Progetti
 */

// Debug errori
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/auth_check.php';
} catch (Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Debug
error_log("API Progetti - Method: $method, Action: $action, GET: " . print_r($_GET, true) . ", POST: " . print_r($_POST, true));

switch ($method) {
    case 'GET':
        if ($action === 'detail' && isset($_GET['id'])) {
            getProgetto($_GET['id']);
        } elseif ($action === 'list') {
            listProgetti();
        } elseif ($action === 'delete' && isset($_GET['id'])) {
            deleteProgetto($_GET['id']);
        } elseif ($action === 'list_documenti' && isset($_GET['progetto_id'])) {
            listDocumenti($_GET['progetto_id']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    case 'POST':
        if ($action === 'create') {
            createProgetto();
        } elseif ($action === 'update' && isset($_POST['id'])) {
            updateProgetto($_POST['id']);
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            deleteProgetto($_POST['id']);
        } elseif ($action === 'distribuisci' && isset($_POST['id'])) {
            $includiCassa = isset($_POST['includi_cassa']) ? (bool)$_POST['includi_cassa'] : true;
            $utentiEsclusi = isset($_POST['utenti_esclusi']) ? json_decode($_POST['utenti_esclusi'], true) : [];
            distribuisciProgetto($_POST['id'], $includiCassa, $utentiEsclusi);
        } elseif ($action === 'revoca_distribuzione' && isset($_POST['id'])) {
            revocaDistribuzione($_POST['id']);
        } elseif ($action === 'upload_documento') {
            uploadDocumento();
        } elseif ($action === 'delete_documento' && isset($_POST['id'])) {
            deleteDocumento($_POST['id']);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Lista progetti con filtri
 */
function listProgetti(): void {
    global $pdo;
    
    try {
        $where = [];
        $params = [];
        
        // Filtro archiviati: di default nascondi archiviati, se richiesto mostra solo archiviati
        $mostraArchiviati = !empty($_GET['archiviati']) && $_GET['archiviati'] === '1';
        if ($mostraArchiviati) {
            $where[] = "p.stato_progetto = 'archiviato'";
        } else {
            $where[] = "p.stato_progetto != 'archiviato'";
        }
        
        // Filtro stato
        if (!empty($_GET['stato'])) {
            $where[] = "p.stato_progetto = ?";
            $params[] = $_GET['stato'];
        }
        
        // Filtro cliente
        if (!empty($_GET['cliente'])) {
            $where[] = "p.cliente_id = ?";
            $params[] = $_GET['cliente'];
        }
        
        // Filtro partecipante
        if (!empty($_GET['partecipante'])) {
            $where[] = "JSON_SEARCH(p.partecipanti, 'one', ?) IS NOT NULL";
            $params[] = $_GET['partecipante'];
        }
        
        // Filtro ricerca
        if (!empty($_GET['search'])) {
            $where[] = "(p.titolo LIKE ? OR c.ragione_sociale LIKE ?)";
            $params[] = "%{$_GET['search']}%";
            $params[] = "%{$_GET['search']}%";
        }
        
        // Filtro colore
        if (!empty($_GET['colore'])) {
            $where[] = "p.colore_tag = ?";
            $params[] = $_GET['colore'];
        }
        
        $userId = $_SESSION['user_id'] ?? '';
        
        // Verifica se tabella task_visualizzazioni esiste
        $hasNotificationTable = false;
        try {
            $checkStmt = $pdo->query("SELECT 1 FROM task_visualizzazioni LIMIT 1");
            $hasNotificationTable = true;
        } catch (PDOException $e) {
            $hasNotificationTable = false;
        }
        
        // Usa interpolazione sicura per user_id (solo alfanumerico e underscore)
        $safeUserId = preg_replace('/[^a-zA-Z0-9_-]/', '', $userId);
        
        $nuoveTaskSql = $hasNotificationTable && $safeUserId ? 
            "COALESCE((
                SELECT COUNT(*) 
                FROM task t2 
                LEFT JOIN task_visualizzazioni tv ON t2.progetto_id = tv.progetto_id COLLATE utf8mb4_0900_ai_ci 
                    AND tv.user_id COLLATE utf8mb4_0900_ai_ci = '{$safeUserId}' COLLATE utf8mb4_0900_ai_ci
                WHERE t2.progetto_id = p.id 
                AND t2.created_at > COALESCE(tv.last_viewed, '1970-01-01')
            ), 0) as nuove_task" : 
            "0 as nuove_task";
        
        $sql = "
            SELECT p.*, c.ragione_sociale as cliente_nome, c.logo_path as cliente_logo,
                   COUNT(t.id) as num_task,
                   SUM(CASE WHEN t.stato = 'completato' THEN 1 ELSE 0 END) as task_completati,
                   {$nuoveTaskSql}
            FROM progetti p
            LEFT JOIN clienti c ON p.cliente_id = c.id
            LEFT JOIN task t ON p.id = t.progetto_id
        ";
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $progetti = $stmt->fetchAll();
        
        // Decodifica JSON e imposta colore default
        foreach ($progetti as &$p) {
            $p['tipologie'] = json_decode($p['tipologie'] ?? '[]', true);
            $p['partecipanti'] = json_decode($p['partecipanti'] ?? '[]', true);
            $p['colore_tag'] = $p['colore_tag'] ?: '#FFFFFF';
        }
        
        // Recupera avatar utenti per i partecipanti
        $utentiStmt = $pdo->prepare("SELECT id, avatar FROM utenti WHERE id IN (?, ?, ?)");
        $utentiAvatar = [];
        try {
            $utentiStmt->execute(['ucwurog3xr8tf', 'ukl9ipuolsebn', 'u3ghz4f2lnpkx']);
            while ($row = $utentiStmt->fetch()) {
                $utentiAvatar[$row['id']] = $row['avatar'];
            }
        } catch (PDOException $e) {
            // Ignora errore
        }
        
        // Aggiungi avatar ai partecipanti
        foreach ($progetti as &$p) {
            $p['partecipanti_avatar'] = [];
            foreach ($p['partecipanti'] as $uid) {
                $p['partecipanti_avatar'][$uid] = $utentiAvatar[$uid] ?? null;
            }
        }
        
        jsonResponse(true, $progetti);
        
    } catch (PDOException $e) {
        jsonResponse(false, null, 'Errore caricamento progetti');
    }
}

/**
 * Dettaglio progetto
 */
function getProgetto(string $id): void {
    global $pdo;
    
    try {
        // Progetto
        $stmt = $pdo->prepare("
            SELECT p.*, c.ragione_sociale as cliente_nome, c.email as cliente_email, c.telefono as cliente_telefono
            FROM progetti p
            LEFT JOIN clienti c ON p.cliente_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $progetto = $stmt->fetch();
        
        if (!$progetto) {
            jsonResponse(false, null, 'Progetto non trovato');
        }
        
        // Decodifica JSON
        $progetto['tipologie'] = json_decode($progetto['tipologie'] ?? '[]', true);
        $progetto['partecipanti'] = json_decode($progetto['partecipanti'] ?? '[]', true);
        
        // Recupera avatar partecipanti
        $progetto['partecipanti_avatar'] = [];
        if (!empty($progetto['partecipanti'])) {
            $placeholders = implode(',', array_fill(0, count($progetto['partecipanti']), '?'));
            $stmtAvatar = $pdo->prepare("SELECT id, avatar FROM utenti WHERE id IN ($placeholders)");
            $stmtAvatar->execute($progetto['partecipanti']);
            while ($row = $stmtAvatar->fetch()) {
                $progetto['partecipanti_avatar'][$row['id']] = $row['avatar'];
            }
        }
        
        // Task
        $stmt = $pdo->prepare("
            SELECT t.*, u.nome as assegnato_nome, u.colore as assegnato_colore
            FROM task t
            LEFT JOIN utenti u ON t.assegnato_a = u.id
            WHERE t.progetto_id = ?
            ORDER BY t.priorita DESC, t.scadenza ASC
        ");
        $stmt->execute([$id]);
        $progetto['task'] = $stmt->fetchAll();
        
        // Transazioni economiche
        $stmt = $pdo->prepare("
            SELECT te.*, u.nome as utente_nome
            FROM transazioni_economiche te
            LEFT JOIN utenti u ON te.utente_id = u.id
            WHERE te.progetto_id = ?
            ORDER BY te.data DESC
        ");
        $stmt->execute([$id]);
        $progetto['transazioni'] = $stmt->fetchAll();
        
        // Timeline specifica progetto
        $stmt = $pdo->prepare("
            SELECT tl.*, u.nome as utente_nome
            FROM timeline tl
            LEFT JOIN utenti u ON tl.utente_id = u.id
            WHERE tl.entita_id = ? OR tl.entita_id IN (SELECT id FROM task WHERE progetto_id = ?)
            ORDER BY tl.timestamp DESC
            LIMIT 20
        ");
        $stmt->execute([$id, $id]);
        $progetto['timeline'] = $stmt->fetchAll();
        
        jsonResponse(true, $progetto);
        
    } catch (PDOException $e) {
        error_log("Errore dettaglio progetto: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento progetto');
    }
}

/**
 * Crea nuovo progetto
 */
function createProgetto(): void {
    global $pdo;
    
    // Validazione
    $titolo = trim($_POST['titolo'] ?? '');
    $clienteId = $_POST['cliente_id'] ?? '';
    
    if (empty($titolo)) {
        jsonResponse(false, null, 'Il titolo è obbligatorio');
    }
    
    try {
        $id = generateEntityId('prj');
        $tipologie = json_encode($_POST['tipologie'] ?? []);
        $partecipanti = json_encode($_POST['partecipanti'] ?? []);
        
        $prezzoTotale = floatval($_POST['prezzo_totale'] ?? 0);
        $statoPagamento = $_POST['stato_pagamento'] ?? 'da_pagare';
        $accontoPercentuale = intval($_POST['acconto_percentuale'] ?? 0);
        
        // Calcola importo acconto se stato è 'da_pagare_acconto'
        $accontoImporto = 0;
        if ($statoPagamento === 'da_pagare_acconto' && $accontoPercentuale > 0) {
            $accontoImporto = ($prezzoTotale * $accontoPercentuale) / 100;
        }
        
        $coloreTag = $_POST['colore_tag'] ?? '#FFFFFF';
        
        $stmt = $pdo->prepare("
            INSERT INTO progetti (
                id, titolo, cliente_id, descrizione, tipologie, prezzo_totale,
                stato_progetto, stato_pagamento, acconto_percentuale, acconto_importo, saldo_importo,
                partecipanti, data_inizio, data_consegna_prevista, colore_tag, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id,
            $titolo,
            $clienteId ?: null,
            $_POST['descrizione'] ?? '',
            $tipologie,
            $prezzoTotale,
            $_POST['stato_progetto'] ?? 'da_iniziare',
            $statoPagamento,
            $accontoPercentuale,
            $accontoImporto,
            $prezzoTotale - $accontoImporto, // saldo = totale - acconto
            $partecipanti,
            $_POST['data_inizio'] ?: null,
            $_POST['data_consegna_prevista'] ?: null,
            $coloreTag,
            $_SESSION['user_id']
        ]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'creato_progetto', 'progetto', $id, "Creato progetto: {$titolo}");
        
        // Crea notifica per tutti gli utenti
        creaNotifica(
            'progetto_creato',
            'Nuovo Progetto',
            $titolo,
            'progetto',
            $id,
            $_SESSION['user_id']
        );
        
        jsonResponse(true, ['id' => $id], 'Progetto creato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore creazione progetto: " . $e->getMessage());
        jsonResponse(false, null, 'Errore creazione progetto');
    }
}

/**
 * Aggiorna progetto
 */
function updateProgetto(string $id): void {
    global $pdo;
    
    // Verifica esistenza e leggi stato attuale
    $stmt = $pdo->prepare("SELECT stato_progetto FROM progetti WHERE id = ?");
    $stmt->execute([$id]);
    $progetto = $stmt->fetch();
    if (!$progetto) {
        jsonResponse(false, null, 'Progetto non trovato');
    }
    
    $statoPrecedente = $progetto['stato_progetto'];
    $nuovoStato = $_POST['stato_progetto'];
    
    try {
        $tipologie = json_encode($_POST['tipologie'] ?? []);
        $partecipanti = json_encode($_POST['partecipanti'] ?? []);
        
        // Calcola importi automaticamente
        $prezzoTotale = floatval($_POST['prezzo_totale'] ?? 0);
        $statoPagamento = $_POST['stato_pagamento'] ?? 'da_pagare';
        $accontoPercentuale = intval($_POST['acconto_percentuale'] ?? 0);
        
        // Calcola importo acconto se stato è 'da_pagare_acconto'
        $acconto = floatval($_POST['acconto_importo'] ?? 0);
        if ($statoPagamento === 'da_pagare_acconto' && $accontoPercentuale > 0) {
            $acconto = ($prezzoTotale * $accontoPercentuale) / 100;
        }
        $saldo = $prezzoTotale - $acconto;
        
        // Se stato è "consegnato" e non c'è data consegna, impostala
        $dataConsegnaEffettiva = $_POST['data_consegna_effettiva'] ?: null;
        if ($nuovoStato === 'consegnato' && !$dataConsegnaEffettiva) {
            $dataConsegnaEffettiva = date('Y-m-d');
        }
        
        // Se stato pagamento è "pagamento_completato", imposta data pagamento
        $dataPagamento = $_POST['data_pagamento'] ?: null;
        if (in_array($statoPagamento, ['pagamento_completato', 'da_saldare']) && !$dataPagamento) {
            $dataPagamento = date('Y-m-d');
        }
        
        $coloreTag = $_POST['colore_tag'] ?? '#FFFFFF';
        
        $stmt = $pdo->prepare("
            UPDATE progetti SET
                titolo = ?,
                cliente_id = ?,
                descrizione = ?,
                tipologie = ?,
                prezzo_totale = ?,
                stato_progetto = ?,
                stato_pagamento = ?,
                acconto_percentuale = ?,
                acconto_importo = ?,
                saldo_importo = ?,
                partecipanti = ?,
                data_inizio = ?,
                data_consegna_prevista = ?,
                data_consegna_effettiva = ?,
                data_pagamento = ?,
                colore_tag = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['titolo'],
            $_POST['cliente_id'] ?: null,
            $_POST['descrizione'] ?? '',
            $tipologie,
            $prezzoTotale,
            $_POST['stato_progetto'],
            $statoPagamento,
            $accontoPercentuale,
            $acconto,
            $saldo,
            $partecipanti,
            $_POST['data_inizio'] ?: null,
            $_POST['data_consegna_prevista'] ?: null,
            $dataConsegnaEffettiva,
            $dataPagamento,
            $coloreTag,
            $id
        ]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'aggiornato_progetto', 'progetto', $id, "Aggiornato progetto: {$_POST['titolo']}");
        
        // Se il progetto è stato appena consegnato, crea notifica
        if ($nuovoStato === 'consegnato' && $statoPrecedente !== 'consegnato') {
            creaNotifica(
                'progetto_consegnato',
                'Progetto Consegnato',
                "{$_POST['titolo']} è stato consegnato",
                'progetto',
                $id,
                $_SESSION['user_id']
            );
        }
        
        jsonResponse(true, ['id' => $id], 'Progetto aggiornato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore aggiornamento progetto: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento progetto');
    }
}

/**
 * Elimina progetto
 */
function deleteProgetto(string $id): void {
    global $pdo;
    
    // Debug
    error_log("Delete progetto - ID ricevuto: " . $id);
    
    if (empty($id)) {
        jsonResponse(false, null, 'ID progetto mancante');
    }
    
    try {
        // Verifica esistenza
        $stmt = $pdo->prepare("SELECT titolo FROM progetti WHERE id = ?");
        $stmt->execute([$id]);
        $progetto = $stmt->fetch();
        
        if (!$progetto) {
            jsonResponse(false, null, 'Progetto non trovato');
        }
        
        // Elimina (cascade su task e transazioni)
        $stmt = $pdo->prepare("DELETE FROM progetti WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'eliminato_progetto', 'progetto', $id, "Eliminato progetto: {$progetto['titolo']}");
        
        jsonResponse(true, null, 'Progetto eliminato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore eliminazione progetto: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione progetto: ' . $e->getMessage());
    }
}

/**
 * Distribuisci economia progetto
 */
function distribuisciProgetto(string $id, bool $includiCassa = true, array $utentiEsclusi = []): void {
    global $pdo;
    
    try {
        // Recupera progetto
        $stmt = $pdo->prepare("
            SELECT * FROM progetti WHERE id = ? AND distribuzione_effettuata = FALSE
        ");
        $stmt->execute([$id]);
        $progetto = $stmt->fetch();
        
        if (!$progetto) {
            jsonResponse(false, null, 'Progetto non trovato o distribuzione già effettuata');
        }
        
        // Verifica stato
        if ($progetto['stato_progetto'] !== 'consegnato') {
            jsonResponse(false, null, 'Il progetto deve essere in stato "Consegnato"');
        }
        
        $partecipanti = json_decode($progetto['partecipanti'] ?? '[]', true);
        if (empty($partecipanti)) {
            jsonResponse(false, null, 'Nessun partecipante al progetto');
        }
        
        $totale = floatval($progetto['prezzo_totale']);
        
        // Filtra partecipanti esclusi
        $partecipantiFiltrati = array_diff($partecipanti, $utentiEsclusi);
        
        if (empty($partecipantiFiltrati)) {
            jsonResponse(false, null, 'Nessun partecipante selezionato per la distribuzione');
        }
        
        // Esegui distribuzione
        if (eseguiDistribuzione($id, $totale, array_values($partecipantiFiltrati), $includiCassa)) {
            logTimeline($_SESSION['user_id'], 'distribuito_economia', 'progetto', $id, "Distribuiti €{$totale}");
            jsonResponse(true, null, 'Distribuzione effettuata con successo');
        } else {
            jsonResponse(false, null, 'Errore durante la distribuzione');
        }
        
    } catch (PDOException $e) {
        error_log("Errore distribuzione: " . $e->getMessage());
        jsonResponse(false, null, 'Errore distribuzione');
    }
}

/**
 * Revoca distribuzione economia progetto
 */
function revocaDistribuzione(string $id): void {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Recupera progetto
        $stmt = $pdo->prepare("
            SELECT * FROM progetti WHERE id = ? AND distribuzione_effettuata = TRUE
        ");
        $stmt->execute([$id]);
        $progetto = $stmt->fetch();
        
        if (!$progetto) {
            jsonResponse(false, null, 'Progetto non trovato o distribuzione non effettuata');
            return;
        }
        
        // Recupera tutte le transazioni wallet del progetto
        $stmt = $pdo->prepare("
            SELECT * FROM transazioni_economiche 
            WHERE progetto_id = ? AND tipo = 'wallet'
        ");
        $stmt->execute([$id]);
        $transazioni = $stmt->fetchAll();
        
        // Sottrai gli importi dai wallet degli utenti
        foreach ($transazioni as $t) {
            $stmt = $pdo->prepare("
                UPDATE utenti SET wallet_saldo = wallet_saldo - ? WHERE id = ?
            ");
            $stmt->execute([$t['importo'], $t['utente_id']]);
        }
        
        // Elimina tutte le transazioni economiche del progetto
        $stmt = $pdo->prepare("DELETE FROM transazioni_economiche WHERE progetto_id = ?");
        $stmt->execute([$id]);
        
        // Segna distribuzione come non effettuata
        $stmt = $pdo->prepare("
            UPDATE progetti SET distribuzione_effettuata = FALSE WHERE id = ?
        ");
        $stmt->execute([$id]);
        
        $pdo->commit();
        
        logTimeline($_SESSION['user_id'], 'revocata_distribuzione', 'progetto', $id, "Revocata distribuzione progetto: {$progetto['titolo']}");
        
        jsonResponse(true, null, 'Distribuzione revocata con successo. Ora puoi ricalcolare e ridistribuire.');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Errore revoca distribuzione: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante la revoca della distribuzione');
    }
}

/**
 * Lista documenti di un progetto
 */
function listDocumenti(string $progettoId): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT d.*, u.nome as uploaded_by_nome
            FROM progetto_documenti d
            LEFT JOIN utenti u ON d.uploaded_by = u.id
            WHERE d.progetto_id = ?
            ORDER BY d.uploaded_at DESC
        ");
        $stmt->execute([$progettoId]);
        $documenti = $stmt->fetchAll();
        
        jsonResponse(true, $documenti);
    } catch (PDOException $e) {
        error_log("Errore lista documenti: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento documenti');
    }
}

/**
 * Upload documento progetto
 */
function uploadDocumento(): void {
    global $pdo;
    
    $progettoId = $_POST['progetto_id'] ?? '';
    
    if (empty($progettoId)) {
        jsonResponse(false, null, 'ID progetto mancante');
        return;
    }
    
    // Verifica numero massimo documenti (5)
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM progetto_documenti WHERE progetto_id = ?");
        $stmt->execute([$progettoId]);
        $count = (int)$stmt->fetchColumn();
        
        if ($count >= 5) {
            jsonResponse(false, null, 'Limite massimo di 5 documenti raggiunto');
            return;
        }
    } catch (PDOException $e) {
        error_log("Errore conteggio documenti: " . $e->getMessage());
        jsonResponse(false, null, 'Errore verifica documenti');
        return;
    }
    
    // Verifica file
    if (empty($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, null, 'Nessun file caricato o errore upload');
        return;
    }
    
    $file = $_FILES['documento'];
    
    // Verifica tipo (PDF o ZIP)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimeTypes = ['application/pdf', 'application/zip', 'application/x-zip-compressed'];
    if (!in_array($mimeType, $allowedMimeTypes)) {
        jsonResponse(false, null, 'Il file deve essere PDF o ZIP');
        return;
    }
    
    // Verifica dimensione (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        jsonResponse(false, null, 'Il file non deve superare i 5MB');
        return;
    }
    
    // Crea directory se non esiste
    $uploadDir = UPLOAD_PATH . 'progetti/' . $progettoId;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Genera nome file univoco
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'doc_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = 'progetti/' . $progettoId . '/' . $filename;
    $fullPath = $uploadDir . '/' . $filename;
    
    // Sposta file
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        jsonResponse(false, null, 'Errore salvataggio file');
        return;
    }
    
    // Salva nel database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO progetto_documenti (progetto_id, filename, file_path, file_size, mime_type, note, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $progettoId,
            $file['name'],
            $filePath,
            $file['size'],
            $mimeType,
            $_POST['note'] ?? '',
            $_SESSION['user_id']
        ]);
        
        logTimeline($_SESSION['user_id'], 'upload_documento', 'progetto', $progettoId, "Caricato documento: {$file['name']}");
        
        jsonResponse(true, null, 'Documento caricato con successo');
    } catch (PDOException $e) {
        // Elimina file se errore DB
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        error_log("Errore salvataggio documento DB: " . $e->getMessage());
        jsonResponse(false, null, 'Errore salvataggio documento');
    }
}

/**
 * Elimina documento progetto
 */
function deleteDocumento(string $id): void {
    global $pdo;
    
    $progettoId = $_POST['progetto_id'] ?? '';
    
    try {
        // Recupera info documento
        $stmt = $pdo->prepare("SELECT * FROM progetto_documenti WHERE id = ? AND progetto_id = ?");
        $stmt->execute([$id, $progettoId]);
        $doc = $stmt->fetch();
        
        if (!$doc) {
            jsonResponse(false, null, 'Documento non trovato');
            return;
        }
        
        // Elimina file fisico
        $fullPath = UPLOAD_PATH . $doc['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
        // Elimina dal database
        $stmt = $pdo->prepare("DELETE FROM progetto_documenti WHERE id = ?");
        $stmt->execute([$id]);
        
        logTimeline($_SESSION['user_id'], 'delete_documento', 'progetto', $progettoId, "Eliminato documento: {$doc['filename']}");
        
        jsonResponse(true, null, 'Documento eliminato');
    } catch (PDOException $e) {
        error_log("Errore eliminazione documento: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione documento');
    }
}
