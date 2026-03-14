<?php
/**
 * Eterea Gestionale
 * API Calendario
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Handler globale per catturare tutte le eccezioni non gestite
set_exception_handler(function($e) {
    error_log("Errore non gestito in calendario.php: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore interno: ' . $e->getMessage()]);
    exit;
});

// Handler per errori PHP
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    error_log("PHP Error in calendario.php: {$message} at line {$line}");
    throw new ErrorException($message, 0, $severity, $file, $line);
});

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($method) {
    case 'GET':
        if ($action === 'events') {
            getEvents();
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    case 'POST':
        if ($action === 'create') {
            createEvent();
        } elseif ($action === 'update' && isset($_POST['id'])) {
            updateEvent($_POST['id']);
        } elseif ($action === 'complete') {
            $id = $_POST['id'] ?? $_GET['id'] ?? null;
            if ($id) {
                completeEvent($id);
            } else {
                jsonResponse(false, null, 'ID mancante');
            }
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? $_GET['id'] ?? null;
            if ($id) {
                deleteEvent($id);
            } else {
                jsonResponse(false, null, 'ID mancante');
            }
        } else {
            jsonResponse(false, null, 'Azione non valida');
        }
        break;
        
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Verifica se una colonna esiste nella tabella
 */
function colonnaEsiste($pdo, $tabella, $colonna) {
    try {
        $pdo->query("SELECT {$colonna} FROM {$tabella} LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Ottieni eventi per il calendario
 */
function getEvents() {
    global $pdo;
    
    $start = $_GET['start'] ?? date('Y-m-01');
    $end = $_GET['end'] ?? date('Y-m-t');
    
    try {
        $events = [];
        
        // Verifica colonne necessarie
        $hasCompletato = colonnaEsiste($pdo, 'appuntamenti', 'completato');
        $hasPartecipanti = colonnaEsiste($pdo, 'appuntamenti', 'partecipanti');
        $hasTaskId = colonnaEsiste($pdo, 'appuntamenti', 'task_id');
        $hasUtenteId = colonnaEsiste($pdo, 'appuntamenti', 'utente_id');
        
        // Costruisci query appuntamenti dinamicamente
        $colonneApp = ['id', 'titolo', 'data_inizio', 'data_fine', 'progetto_id', 'note'];
        if ($hasCompletato) $colonneApp[] = 'completato';
        if ($hasPartecipanti) $colonneApp[] = 'partecipanti';
        if ($hasTaskId) $colonneApp[] = 'task_id';
        if ($hasUtenteId) $colonneApp[] = 'utente_id';
        
        $colonneStr = implode(', ', $colonneApp);
        
        // Query appuntamenti semplice
        try {
            $stmt = $pdo->prepare("SELECT {$colonneStr} FROM appuntamenti WHERE DATE(data_inizio) BETWEEN ? AND ? ORDER BY data_inizio ASC");
            $stmt->execute([$start, $end]);
            $appuntamenti = $stmt->fetchAll();
            
            foreach ($appuntamenti as $a) {
                $event = [
                    'id' => $a['id'],
                    'titolo' => $a['titolo'],
                    'data_inizio' => $a['data_inizio'],
                    'data_fine' => $a['data_fine'],
                    'progetto_id' => $a['progetto_id'],
                    'note' => $a['note'] ?? '',
                    'tipo' => 'appuntamento',
                    'partecipanti_list' => []
                ];
                
                if ($hasPartecipanti && !empty($a['partecipanti'])) {
                    $partecipantiIds = json_decode($a['partecipanti'], true) ?: [];
                    // Qui potremmo arricchire con dati utente se necessario
                    $event['partecipanti_list'] = $partecipantiIds;
                }
                
                $events[] = $event;
            }
        } catch (PDOException $e) {
            error_log("Errore query appuntamenti: " . $e->getMessage());
        }
        
        // Progetti con consegna prevista
        try {
            $colStato = colonnaEsiste($pdo, 'progetti', 'stato_progetto') ? 'stato_progetto' : 
                       (colonnaEsiste($pdo, 'progetti', 'stato') ? 'stato' : null);
            
            if ($colStato && colonnaEsiste($pdo, 'progetti', 'data_consegna_prevista')) {
                $stmt = $pdo->prepare("SELECT id, titolo, data_consegna_prevista FROM progetti WHERE DATE(data_consegna_prevista) BETWEEN ? AND ? AND {$colStato} NOT IN ('consegnato', 'archiviato')");
                $stmt->execute([$start, $end]);
                $progetti = $stmt->fetchAll();
                
                foreach ($progetti as $p) {
                    $events[] = [
                        'id' => 'prj_' . $p['id'],
                        'titolo' => 'Consegna: ' . $p['titolo'],
                        'data_inizio' => $p['data_consegna_prevista'] . ' 00:00:00',
                        'data_fine' => $p['data_consegna_prevista'] . ' 23:59:59',
                        'tipo' => 'scadenza_progetto',
                        'progetto_id' => $p['id'],
                        'progetto_titolo' => $p['titolo'],
                        'note' => '',
                        'partecipanti_list' => []
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Errore query progetti: " . $e->getMessage());
        }
        
        // Task con scadenza
        try {
            if (colonnaEsiste($pdo, 'task', 'data_scadenza')) {
                $colStatoTask = colonnaEsiste($pdo, 'task', 'stato') ? 'stato' : null;
                
                if ($colStatoTask) {
                    $stmt = $pdo->prepare("SELECT id, titolo, data_scadenza, progetto_id FROM task WHERE data_scadenza IS NOT NULL AND DATE(data_scadenza) BETWEEN ? AND ? AND {$colStatoTask} != 'completato'");
                } else {
                    $stmt = $pdo->prepare("SELECT id, titolo, data_scadenza, progetto_id FROM task WHERE data_scadenza IS NOT NULL AND DATE(data_scadenza) BETWEEN ? AND ?");
                }
                
                $stmt->execute([$start, $end]);
                $tasks = $stmt->fetchAll();
                
                foreach ($tasks as $t) {
                    $events[] = [
                        'id' => 'task_' . $t['id'],
                        'titolo' => 'Scadenza: ' . $t['titolo'],
                        'task_titolo' => $t['titolo'],
                        'data_inizio' => $t['data_scadenza'] . ' 00:00:00',
                        'data_fine' => $t['data_scadenza'] . ' 23:59:59',
                        'tipo' => 'scadenza_task',
                        'progetto_id' => $t['progetto_id'],
                        'progetto_titolo' => '',
                        'assegnato_a' => '',
                        'assegnato_nome' => '',
                        'assegnato_colore' => '',
                        'note' => '',
                        'partecipanti_list' => []
                    ];
                }
            }
        } catch (Exception $e) {
            error_log("Errore query task: " . $e->getMessage());
        }
        
        jsonResponse(true, $events);
        
    } catch (Exception $e) {
        error_log("Errore caricamento eventi: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento eventi');
    }
}

/**
 * Crea evento
 */
function createEvent() {
    global $pdo;
    
    $titolo = trim($_POST['titolo'] ?? '');
    $dataInizio = $_POST['data_inizio'] ?? '';
    
    if (empty($titolo) || empty($dataInizio)) {
        jsonResponse(false, null, 'Titolo e data sono obbligatori');
    }
    
    try {
        // Verifica colonne
        $hasPartecipanti = colonnaEsiste($pdo, 'appuntamenti', 'partecipanti');
        $hasTipo = colonnaEsiste($pdo, 'appuntamenti', 'tipo');
        $hasCreatedBy = colonnaEsiste($pdo, 'appuntamenti', 'created_by');
        $hasTaskId = colonnaEsiste($pdo, 'appuntamenti', 'task_id');
        $hasUtenteId = colonnaEsiste($pdo, 'appuntamenti', 'utente_id');
        
        $id = generateEntityId('evt');
        $partecipanti = json_encode($_POST['partecipanti'] ?? []);
        
        // Costruisci query dinamicamente
        $colonne = ['id', 'titolo', 'data_inizio', 'data_fine', 'progetto_id', 'note'];
        $valori = [$id, $titolo, $dataInizio, $_POST['data_fine'] ?: null, $_POST['progetto_id'] ?: null, $_POST['note'] ?? ''];
        
        if ($hasTipo) {
            $colonne[] = 'tipo';
            $valori[] = $_POST['tipo'] ?? 'appuntamento';
        }
        if ($hasTaskId) {
            $colonne[] = 'task_id';
            $valori[] = $_POST['task_id'] ?: null;
        }
        if ($hasUtenteId) {
            $colonne[] = 'utente_id';
            $valori[] = $_POST['utente_id'] ?: null;
        }
        if ($hasPartecipanti) {
            $colonne[] = 'partecipanti';
            $valori[] = $partecipanti;
        }
        if ($hasCreatedBy) {
            $colonne[] = 'created_by';
            $valori[] = $_SESSION['user_id'] ?? null;
        }
        
        $colonneStr = implode(', ', $colonne);
        $placeholders = implode(', ', array_fill(0, count($colonne), '?'));
        
        $stmt = $pdo->prepare("INSERT INTO appuntamenti ({$colonneStr}) VALUES ({$placeholders})");
        $stmt->execute($valori);
        
        try {
            logTimeline($_SESSION['user_id'], 'creato_appuntamento', 'appuntamento', $id, "Creato: {$titolo}");
        } catch (Exception $e) {
            error_log("Errore logTimeline: " . $e->getMessage());
        }
        
        try {
            creaNotifica(
                'appuntamento',
                'Nuovo Appuntamento',
                "{$titolo} - " . date('d/m/Y H:i', strtotime($dataInizio)),
                'appuntamento',
                $id,
                $_SESSION['user_id']
            );
        } catch (Exception $e) {
            error_log("Errore creaNotifica: " . $e->getMessage());
        }
        
        jsonResponse(true, ['id' => $id], 'Appuntamento creato');
        
    } catch (Exception $e) {
        error_log("Errore creazione evento: " . $e->getMessage());
        jsonResponse(false, null, 'Errore creazione evento: ' . $e->getMessage());
    }
}

/**
 * Aggiorna evento
 */
function updateEvent($id) {
    global $pdo;
    
    try {
        // Verifica colonne
        $hasPartecipanti = colonnaEsiste($pdo, 'appuntamenti', 'partecipanti');
        $hasTipo = colonnaEsiste($pdo, 'appuntamenti', 'tipo');
        
        $partecipanti = json_encode($_POST['partecipanti'] ?? []);
        
        // Costruisci query dinamicamente
        $set = ['titolo = ?', 'data_inizio = ?', 'data_fine = ?', 'note = ?'];
        $valori = [$_POST['titolo'], $_POST['data_inizio'], $_POST['data_fine'] ?: null, $_POST['note'] ?? ''];
        
        if ($hasTipo) {
            $set[] = 'tipo = ?';
            $valori[] = $_POST['tipo'] ?? 'appuntamento';
        }
        if ($hasPartecipanti) {
            $set[] = 'partecipanti = ?';
            $valori[] = $partecipanti;
        }
        
        $valori[] = $id;
        $setStr = implode(', ', $set);
        
        $stmt = $pdo->prepare("UPDATE appuntamenti SET {$setStr} WHERE id = ?");
        $stmt->execute($valori);
        
        jsonResponse(true, null, 'Appuntamento aggiornato');
        
    } catch (Exception $e) {
        error_log("Errore aggiornamento evento: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento evento: ' . $e->getMessage());
    }
}

/**
 * Elimina evento
 */
function deleteEvent($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM appuntamenti WHERE id = ?");
        $stmt->execute([$id]);
        
        jsonResponse(true, null, 'Appuntamento eliminato');
        
    } catch (Exception $e) {
        error_log("Errore eliminazione evento: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione evento');
    }
}

/**
 * Segna evento come completato
 */
function completeEvent($id) {
    global $pdo;
    
    try {
        // Verifica esistenza colonna completato
        if (!colonnaEsiste($pdo, 'appuntamenti', 'completato')) {
            try {
                $pdo->exec("ALTER TABLE appuntamenti ADD COLUMN completato TINYINT(1) DEFAULT 0");
            } catch (PDOException $e) {
                jsonResponse(false, null, 'Errore database: impossibile creare colonna completato');
                return;
            }
        }
        
        // Esegui UPDATE
        $stmt = $pdo->prepare("UPDATE appuntamenti SET completato = 1 WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result && $stmt->rowCount() > 0) {
            jsonResponse(true, null, 'Appuntamento completato');
        } elseif ($result) {
            jsonResponse(true, null, 'Evento già completato o non trovato');
        } else {
            jsonResponse(false, null, 'Errore aggiornamento database');
        }
        
    } catch (Exception $e) {
        error_log("Errore completamento evento: " . $e->getMessage());
        jsonResponse(false, null, 'Errore: ' . $e->getMessage());
    }
}
