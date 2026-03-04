<?php
/**
 * Eterea Gestionale
 * API Task
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Leggi ID da GET o POST
$id = $_GET['id'] ?? $_POST['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($action === 'detail' && $id) {
            getTask($id);
        } elseif ($action === 'list') {
            listTask();
        } elseif ($action === 'delete' && $id) {
            deleteTask($id);
        } elseif ($action === 'change_status' && $id) {
            changeTaskStatus($id);
        } elseif ($action === 'list_commenti' && $id) {
            listCommenti($id);
        } elseif ($action === 'get_timer' && $id) {
            getTimerStatus($id);
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    case 'POST':
        if ($action === 'create') {
            createTask();
        } elseif ($action === 'update' && isset($_POST['id'])) {
            updateTask($_POST['id']);
        } elseif ($action === 'change_status' && $id) {
            changeTaskStatus($id);
        } elseif ($action === 'add_commento') {
            addCommento();
        } elseif ($action === 'delete_commento' && isset($_POST['commento_id'])) {
            deleteCommento($_POST['commento_id']);
        } elseif ($action === 'timer_start') {
            timerStart();
        } elseif ($action === 'timer_pause') {
            timerPause();
        } elseif ($action === 'timer_resume') {
            timerResume();
        } elseif ($action === 'timer_stop') {
            timerStop();
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Lista task con filtri
 */
function listTask(): void {
    global $pdo;
    
    try {
        $where = [];
        $params = [];
        
        // Filtro progetto
        if (!empty($_GET['progetto_id'])) {
            $where[] = "t.progetto_id = ?";
            $params[] = $_GET['progetto_id'];
        }
        
        // Filtro assegnato a (cerca in assegnati JSON o assegnato_a legacy)
        if (!empty($_GET['assegnato_a'])) {
            $where[] = "(t.assegnato_a = ? OR JSON_CONTAINS(t.assegnati, JSON_QUOTE(?)))";
            $params[] = $_GET['assegnato_a'];
            $params[] = $_GET['assegnato_a'];
        }
        
        // Filtro stato
        if (!empty($_GET['stato'])) {
            $where[] = "t.stato = ?";
            $params[] = $_GET['stato'];
        }
        
        // Filtro scadenza
        if (!empty($_GET['scadenza_da'])) {
            $where[] = "DATE(t.scadenza) >= ?";
            $params[] = $_GET['scadenza_da'];
        }
        if (!empty($_GET['scadenza_a'])) {
            $where[] = "DATE(t.scadenza) <= ?";
            $params[] = $_GET['scadenza_a'];
        }
        
        $sql = "
            SELECT t.*, p.titolo as progetto_titolo, u.nome as assegnato_nome, u.colore as assegnato_colore,
                   c.nome as creato_nome, c.avatar as creato_avatar, c.colore as creato_colore
            FROM task t
            JOIN progetti p ON t.progetto_id = p.id
            LEFT JOIN utenti u ON t.assegnato_a = u.id
            LEFT JOIN utenti c ON t.created_by = c.id
        ";
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $task = $stmt->fetchAll();
        
        // Arricchisci con dati assegnati multipli
        foreach ($task as &$t) {
            $assegnatiIds = json_decode($t['assegnati'] ?? '[]', true) ?: [];
            if (!empty($t['assegnato_a']) && !in_array($t['assegnato_a'], $assegnatiIds)) {
                $assegnatiIds[] = $t['assegnato_a'];
            }
            
            $t['assegnati_list'] = [];
            foreach ($assegnatiIds as $uid) {
                if (isset(USERS[$uid])) {
                    $t['assegnati_list'][] = [
                        'id' => $uid,
                        'nome' => USERS[$uid]['nome'],
                        'colore' => USERS[$uid]['colore']
                    ];
                }
            }
        }
        unset($t);
        
        jsonResponse(true, $task);
        
    } catch (PDOException $e) {
        error_log("Errore lista task: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento task');
    }
}

/**
 * Dettaglio task
 */
function getTask(string $id): void {
    global $pdo;
    
    try {
        // Task
        $stmt = $pdo->prepare("
            SELECT t.*, p.titolo as progetto_titolo, u.nome as assegnato_nome, c.nome as creato_nome
            FROM task t
            JOIN progetti p ON t.progetto_id = p.id
            LEFT JOIN utenti u ON t.assegnato_a = u.id
            LEFT JOIN utenti c ON t.created_by = c.id
            WHERE t.id = ?
        ");
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        
        if (!$task) {
            jsonResponse(false, null, 'Task non trovata');
        }
        
        // Arricchisci con assegnati multipli
        $assegnatiIds = json_decode($task['assegnati'] ?? '[]', true) ?: [];
        if (!empty($task['assegnato_a']) && !in_array($task['assegnato_a'], $assegnatiIds)) {
            $assegnatiIds[] = $task['assegnato_a'];
        }
        
        $task['assegnati_list'] = [];
        foreach ($assegnatiIds as $uid) {
            if (isset(USERS[$uid])) {
                $task['assegnati_list'][] = [
                    'id' => $uid,
                    'nome' => USERS[$uid]['nome'],
                    'colore' => USERS[$uid]['colore']
                ];
            }
        }
        
        // Allegati
        $stmt = $pdo->prepare("
            SELECT ta.*, u.nome as uploaded_nome
            FROM task_allegati ta
            LEFT JOIN utenti u ON ta.uploaded_by = u.id
            WHERE ta.task_id = ?
            ORDER BY ta.uploaded_at DESC
        ");
        $stmt->execute([$id]);
        $task['allegati'] = $stmt->fetchAll();
        
        jsonResponse(true, $task);
        
    } catch (PDOException $e) {
        error_log("Errore dettaglio task: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento task');
    }
}

/**
 * Crea nuova task
 */
function createTask(): void {
    global $pdo;
    
    // Validazione
    $titolo = trim($_POST['titolo'] ?? '');
    $progettoId = $_POST['progetto_id'] ?? '';
    
    if (empty($titolo) || empty($progettoId)) {
        jsonResponse(false, null, 'Titolo e progetto sono obbligatori');
    }
    
    // Gestisci array assegnati
    $assegnati = $_POST['assegnati'] ?? [];
    if (!is_array($assegnati)) {
        $assegnati = [$assegnati];
    }
    $assegnatiJson = !empty($assegnati) ? json_encode($assegnati) : null;
    $primoAssegnato = $assegnati[0] ?? null; // Retrocompatibilità
    
    // Gestisci upload immagine
    $immaginePath = null;
    if (!empty($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['immagine'], 'task_images', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 5 * 1024 * 1024);
        if ($upload) {
            $immaginePath = $upload['path'];
        }
    }
    
    try {
        $id = generateEntityId('tsk');
        
        $stmt = $pdo->prepare("
            INSERT INTO task (
                id, progetto_id, titolo, descrizione, immagine, colore, assegnato_a, assegnati, scadenza,
                stato, priorita, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id,
            $progettoId,
            $titolo,
            $_POST['descrizione'] ?? '',
            $immaginePath,
            $_POST['colore'] ?: null,
            $primoAssegnato,
            $assegnatiJson,
            $_POST['scadenza'] ?: null,
            $_POST['stato'] ?? 'da_fare',
            $_POST['priorita'] ?? 'media',
            $_SESSION['user_id']
        ]);
        
        // Crea appuntamento se c'è scadenza e almeno un assegnato
        if (!empty($_POST['scadenza']) && !empty($assegnati)) {
            foreach ($assegnati as $utenteId) {
                creaAppuntamentoTask($id, $progettoId, $titolo, $_POST['scadenza'], $utenteId);
            }
        }
        
        // Crea notifiche per gli assegnati
        if (!empty($assegnati)) {
            // Recupera nome progetto
            $stmtProgetto = $pdo->prepare("SELECT titolo FROM progetti WHERE id = ?");
            $stmtProgetto->execute([$progettoId]);
            $progettoNome = $stmtProgetto->fetchColumn() ?: 'Progetto';
            
            // Recupera nome creatore
            $stmtCreatore = $pdo->prepare("SELECT nome FROM utenti WHERE id = ?");
            $stmtCreatore->execute([$_SESSION['user_id']]);
            $creatoreNome = $stmtCreatore->fetchColumn() ?: 'Qualcuno';
            
            $stmtNotifica = $pdo->prepare("
                INSERT INTO notifiche (id, tipo, titolo, messaggio, entita_tipo, entita_id, progetto_id, utente_destinatario, creato_da)
                VALUES (?, 'task', ?, ?, 'task', ?, ?, ?, ?)
            ");
            
            foreach ($assegnati as $utenteId) {
                if ($utenteId !== $_SESSION['user_id']) { // Non notificare il creatore
                    $notificaId = generateEntityId('ntf');
                    $stmtNotifica->execute([
                        $notificaId,
                        "Nuova task: {$titolo}",
                        "{$creatoreNome} ti ha assegnato una nuova task nel progetto '{$progettoNome}'",
                        $id,
                        $progettoId,
                        $utenteId,
                        $_SESSION['user_id']
                    ]);
                }
            }
        }
        
        // Log
        logTimeline($_SESSION['user_id'], 'creato_task', 'task', $id, "Creata task: {$titolo}");
        
        jsonResponse(true, ['id' => $id], 'Task creata con successo');
        
    } catch (PDOException $e) {
        error_log("Errore creazione task: " . $e->getMessage());
        jsonResponse(false, null, 'Errore creazione task');
    }
}

/**
 * Aggiorna task
 */
function updateTask(string $id): void {
    global $pdo;
    
    // Verifica esistenza
    $stmt = $pdo->prepare("SELECT immagine FROM task WHERE id = ?");
    $stmt->execute([$id]);
    $task = $stmt->fetch();
    if (!$task) {
        jsonResponse(false, null, 'Task non trovata');
    }
    
    // Gestisci array assegnati
    $assegnati = $_POST['assegnati'] ?? [];
    if (!is_array($assegnati)) {
        $assegnati = [$assegnati];
    }
    $assegnatiJson = !empty($assegnati) ? json_encode($assegnati) : null;
    $primoAssegnato = $assegnati[0] ?? null;
    
    // Gestisci upload immagine
    $immaginePath = $task['immagine'];
    if (!empty($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['immagine'], 'task_images', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 5 * 1024 * 1024);
        if ($upload) {
            // Elimina vecchia immagine
            if ($immaginePath && file_exists(UPLOAD_PATH . $immaginePath)) {
                unlink(UPLOAD_PATH . $immaginePath);
            }
            $immaginePath = $upload['path'];
        }
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE task SET
                titolo = ?,
                descrizione = ?,
                immagine = ?,
                colore = ?,
                assegnato_a = ?,
                assegnati = ?,
                scadenza = ?,
                stato = ?,
                priorita = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['titolo'],
            $_POST['descrizione'] ?? '',
            $immaginePath,
            $_POST['colore'] ?: null,
            $primoAssegnato,
            $assegnatiJson,
            $_POST['scadenza'] ?: null,
            $_POST['stato'],
            $_POST['priorita'],
            $id
        ]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'aggiornato_task', 'task', $id, "Aggiornata task: {$_POST['titolo']}");
        
        jsonResponse(true, ['id' => $id], 'Task aggiornata con successo');
        
    } catch (PDOException $e) {
        error_log("Errore aggiornamento task: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento task');
    }
}

/**
 * Cambia stato task rapidamente
 */
function changeTaskStatus(string $id): void {
    global $pdo;
    
    $nuovoStato = $_GET['stato'] ?? $_POST['stato'] ?? '';
    if (!in_array($nuovoStato, ['da_fare', 'in_corso', 'completato'])) {
        jsonResponse(false, null, 'Stato non valido');
    }
    
    try {
        $completedAt = null;
        if ($nuovoStato === 'completato') {
            $completedAt = date('Y-m-d H:i:s');
        }
        
        $stmt = $pdo->prepare("
            UPDATE task SET stato = ?, completed_at = ? WHERE id = ?
        ");
        $stmt->execute([$nuovoStato, $completedAt, $id]);
        
        // Recupera titolo per log
        $stmt = $pdo->prepare("SELECT titolo FROM task WHERE id = ?");
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        
        $azione = $nuovoStato === 'completato' ? 'completato_task' : 'cambiato_stato_task';
        logTimeline($_SESSION['user_id'], $azione, 'task', $id, "Task {$task['titolo']} -> {$nuovoStato}");
        
        jsonResponse(true, null, 'Stato aggiornato');
        
    } catch (PDOException $e) {
        error_log("Errore cambio stato: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento stato');
    }
}

/**
 * Elimina task
 */
function deleteTask(string $id): void {
    global $pdo;
    
    try {
        // Recupera titolo per log
        $stmt = $pdo->prepare("SELECT titolo FROM task WHERE id = ?");
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        
        if (!$task) {
            jsonResponse(false, null, 'Task non trovata');
        }
        
        // Elimina (cascade sugli allegati)
        $stmt = $pdo->prepare("DELETE FROM task WHERE id = ?");
        $stmt->execute([$id]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'eliminato_task', 'task', $id, "Eliminata task: {$task['titolo']}");
        
        jsonResponse(true, null, 'Task eliminata con successo');
        
    } catch (PDOException $e) {
        error_log("Errore eliminazione task: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione task');
    }
}


/**
 * Lista commenti di una task
 */
function listCommenti(string $taskId): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.nome as utente_nome, u.avatar as utente_avatar, u.colore as utente_colore
            FROM task_commenti c
            JOIN utenti u ON c.utente_id = u.id
            WHERE c.task_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$taskId]);
        $commenti = $stmt->fetchAll();
        
        jsonResponse(true, $commenti);
        
    } catch (PDOException $e) {
        error_log("Errore lista commenti: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento commenti');
    }
}

/**
 * Aggiungi commento a una task
 */
function addCommento(): void {
    global $pdo;
    
    $taskId = $_POST['task_id'] ?? '';
    $commento = trim($_POST['commento'] ?? '');
    
    if (empty($taskId) || empty($commento)) {
        jsonResponse(false, null, 'Task e commento sono obbligatori');
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO task_commenti (task_id, utente_id, commento)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$taskId, $_SESSION['user_id'], $commento]);
        
        $commentoId = $pdo->lastInsertId();
        
        // Recupera il commento appena creato con i dati utente
        $stmt = $pdo->prepare("
            SELECT c.*, u.nome as utente_nome, u.avatar as utente_avatar, u.colore as utente_colore
            FROM task_commenti c
            JOIN utenti u ON c.utente_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$commentoId]);
        $nuovoCommento = $stmt->fetch();
        
        // Log
        logTimeline($_SESSION['user_id'], 'aggiunto_commento', 'task', $taskId, 'Aggiunto commento a task');
        
        jsonResponse(true, $nuovoCommento, 'Commento aggiunto con successo');
        
    } catch (PDOException $e) {
        error_log("Errore aggiunta commento: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiunta commento');
    }
}

/**
 * Elimina commento
 */
function deleteCommento(string $commentoId): void {
    global $pdo;
    
    try {
        // Verifica che il commento esista e che l'utente sia l'autore
        $stmt = $pdo->prepare("
            SELECT task_id, utente_id FROM task_commenti WHERE id = ?
        ");
        $stmt->execute([$commentoId]);
        $commento = $stmt->fetch();
        
        if (!$commento) {
            jsonResponse(false, null, 'Commento non trovato');
        }
        
        // Solo l'autore può eliminare il proprio commento
        if ($commento['utente_id'] !== $_SESSION['user_id']) {
            jsonResponse(false, null, 'Non autorizzato');
        }
        
        $stmt = $pdo->prepare("DELETE FROM task_commenti WHERE id = ?");
        $stmt->execute([$commentoId]);
        
        // Log
        logTimeline($_SESSION['user_id'], 'eliminato_commento', 'task', $commento['task_id'], 'Eliminato commento');
        
        jsonResponse(true, null, 'Commento eliminato');
        
    } catch (PDOException $e) {
        error_log("Errore eliminazione commento: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione commento');
    }
}


/**
 * ============================================================================
 * GESTIONE TIMER TASK
 * ============================================================================
 */

/**
 * Avvia il timer per una task
 */
function timerStart(): void {
    global $pdo;
    
    $taskId = $_POST['task_id'] ?? '';
    $utenteId = $_SESSION['user_id'] ?? '';
    
    if (empty($taskId)) {
        jsonResponse(false, null, 'Task ID mancante');
        return;
    }
    
    try {
        // Verifica se c'è già un timer attivo per questa task
        $stmt = $pdo->prepare("
            SELECT id FROM task_timer 
            WHERE task_id = ? AND utente_id = ? AND is_running = 1
        ");
        $stmt->execute([$taskId, $utenteId]);
        if ($stmt->fetch()) {
            jsonResponse(false, null, 'Timer già attivo per questa task');
            return;
        }
        
        // Ferma eventuali altri timer attivi dell'utente
        $stmt = $pdo->prepare("
            UPDATE task_timer 
            SET is_running = 0, stopped_at = NOW(),
                total_seconds = total_seconds + TIMESTAMPDIFF(SECOND, started_at, NOW())
            WHERE utente_id = ? AND is_running = 1
        ");
        $stmt->execute([$utenteId]);
        
        // Crea nuovo timer
        $stmt = $pdo->prepare("
            INSERT INTO task_timer (task_id, utente_id, started_at, is_running, total_seconds)
            VALUES (?, ?, NOW(), 1, 0)
        ");
        $stmt->execute([$taskId, $utenteId]);
        
        $timerId = $pdo->lastInsertId();
        
        jsonResponse(true, [
            'timer_id' => $timerId,
            'started_at' => date('Y-m-d H:i:s'),
            'message' => 'Timer avviato'
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore avvio timer: " . $e->getMessage());
        jsonResponse(false, null, 'Errore avvio timer');
    }
}

/**
 * Mette in pausa il timer
 */
function timerPause(): void {
    global $pdo;
    
    $taskId = $_POST['task_id'] ?? '';
    $utenteId = $_SESSION['user_id'] ?? '';
    
    if (empty($taskId)) {
        jsonResponse(false, null, 'Task ID mancante');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE task_timer 
            SET is_paused = 1, paused_at = NOW(),
                total_seconds = total_seconds + TIMESTAMPDIFF(SECOND, started_at, NOW())
            WHERE task_id = ? AND utente_id = ? AND is_running = 1 AND is_paused = 0
        ");
        $stmt->execute([$taskId, $utenteId]);
        
        if ($stmt->rowCount() === 0) {
            jsonResponse(false, null, 'Nessun timer attivo trovato');
            return;
        }
        
        jsonResponse(true, ['message' => 'Timer in pausa']);
        
    } catch (PDOException $e) {
        error_log("Errore pausa timer: " . $e->getMessage());
        jsonResponse(false, null, 'Errore pausa timer');
    }
}

/**
 * Riprende il timer dalla pausa
 */
function timerResume(): void {
    global $pdo;
    
    $taskId = $_POST['task_id'] ?? '';
    $utenteId = $_SESSION['user_id'] ?? '';
    
    if (empty($taskId)) {
        jsonResponse(false, null, 'Task ID mancante');
        return;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE task_timer 
            SET is_paused = 0, resumed_at = NOW(), started_at = NOW()
            WHERE task_id = ? AND utente_id = ? AND is_running = 1 AND is_paused = 1
        ");
        $stmt->execute([$taskId, $utenteId]);
        
        if ($stmt->rowCount() === 0) {
            jsonResponse(false, null, 'Nessun timer in pausa trovato');
            return;
        }
        
        jsonResponse(true, ['message' => 'Timer ripreso']);
        
    } catch (PDOException $e) {
        error_log("Errore resume timer: " . $e->getMessage());
        jsonResponse(false, null, 'Errore resume timer');
    }
}

/**
 * Ferma il timer e calcola il tempo totale
 */
function timerStop(): void {
    global $pdo;
    
    $taskId = $_POST['task_id'] ?? '';
    $utenteId = $_SESSION['user_id'] ?? '';
    
    if (empty($taskId)) {
        jsonResponse(false, null, 'Task ID mancante');
        return;
    }
    
    try {
        // Ottieni il timer attivo
        $stmt = $pdo->prepare("
            SELECT id, total_seconds, started_at, is_paused
            FROM task_timer 
            WHERE task_id = ? AND utente_id = ? AND is_running = 1
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$taskId, $utenteId]);
        $timer = $stmt->fetch();
        
        if (!$timer) {
            jsonResponse(false, null, 'Nessun timer attivo trovato');
            return;
        }
        
        // Calcola secondi finali
        $additionalSeconds = 0;
        if (!$timer['is_paused']) {
            $additionalSeconds = time() - strtotime($timer['started_at']);
        }
        $totalSeconds = $timer['total_seconds'] + $additionalSeconds;
        
        // Aggiorna timer
        $stmt = $pdo->prepare("
            UPDATE task_timer 
            SET is_running = 0, stopped_at = NOW(), total_seconds = ?
            WHERE id = ?
        ");
        $stmt->execute([$totalSeconds, $timer['id']]);
        
        // Ottieni paga oraria
        $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'paga_oraria'");
        $stmt->execute();
        $pagaOraria = floatval($stmt->fetchColumn() ?: '25.00');
        
        // Calcola costo
        $oreLavorate = $totalSeconds / 3600;
        $costo = round($oreLavorate * $pagaOraria, 2);
        
        // Aggiorna task con tempo e costo
        $stmt = $pdo->prepare("
            UPDATE task 
            SET tempo_impiegato_seconds = tempo_impiegato_seconds + ?,
                costo_calcolato = costo_calcolato + ?
            WHERE id = ?
        ");
        $stmt->execute([$totalSeconds, $costo, $taskId]);
        
        // Formatta tempo per risposta
        $ore = floor($totalSeconds / 3600);
        $min = floor(($totalSeconds % 3600) / 60);
        $sec = $totalSeconds % 60;
        $tempoFormattato = sprintf('%02d:%02d:%02d', $ore, $min, $sec);
        
        jsonResponse(true, [
            'total_seconds' => $totalSeconds,
            'tempo_formattato' => $tempoFormattato,
            'costo' => $costo,
            'paga_oraria' => $pagaOraria,
            'message' => 'Timer fermato'
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore stop timer: " . $e->getMessage());
        jsonResponse(false, null, 'Errore stop timer');
    }
}

/**
 * Ottiene lo stato del timer per una task
 */
function getTimerStatus(string $taskId): void {
    global $pdo;
    
    $utenteId = $_SESSION['user_id'] ?? '';
    
    try {
        // Ottieni timer attivo
        $stmt = $pdo->prepare("
            SELECT id, started_at, is_running, is_paused, total_seconds
            FROM task_timer 
            WHERE task_id = ? AND utente_id = ? AND is_running = 1
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$taskId, $utenteId]);
        $timer = $stmt->fetch();
        
        // Ottieni totali accumulati dalla task
        $stmt = $pdo->prepare("
            SELECT tempo_impiegato_seconds, costo_calcolato 
            FROM task WHERE id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        $response = [
            'has_active_timer' => false,
            'total_seconds' => intval($task['tempo_impiegato_seconds'] ?? 0),
            'costo_calcolato' => floatval($task['costo_calcolato'] ?? 0)
        ];
        
        if ($timer) {
            $response['has_active_timer'] = true;
            $response['timer_id'] = $timer['id'];
            $response['is_paused'] = (bool)$timer['is_paused'];
            $response['started_at'] = $timer['started_at'];
            
            // Calcola tempo corrente se non in pausa
            $currentTotal = $timer['total_seconds'];
            if (!$timer['is_paused']) {
                $currentTotal += time() - strtotime($timer['started_at']);
            }
            $response['current_session_seconds'] = $currentTotal;
        }
        
        jsonResponse(true, $response);
        
    } catch (PDOException $e) {
        error_log("Errore get timer status: " . $e->getMessage());
        jsonResponse(false, null, 'Errore lettura timer');
    }
}
