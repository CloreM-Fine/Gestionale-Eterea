<?php
/**
 * Eterea Gestionale
 * API Calendario
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

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
 * Ottieni eventi per il calendario
 */
function getEvents(): void {
    global $pdo;
    
    $start = $_GET['start'] ?? date('Y-m-01');
    $end = $_GET['end'] ?? date('Y-m-t');
    
    try {
        // Appuntamenti
        $stmt = $pdo->prepare("
            SELECT a.*, p.titolo as progetto_titolo, u.nome as utente_nome, u.colore as utente_colore, u.avatar as utente_avatar,
                   a.partecipanti as partecipanti_json
            FROM appuntamenti a
            LEFT JOIN progetti p ON a.progetto_id = p.id
            LEFT JOIN utenti u ON a.utente_id = u.id
            WHERE DATE(a.data_inizio) BETWEEN ? AND ?
            ORDER BY a.data_inizio ASC
        ");
        $stmt->execute([$start, $end]);
        $events = $stmt->fetchAll();
        
        // Arricchisci con dati partecipanti
        $allUtenti = [];
        $utentiStmt = $pdo->query("SELECT id, nome, colore, avatar FROM utenti");
        while ($u = $utentiStmt->fetch()) {
            $allUtenti[$u['id']] = ['nome' => $u['nome'], 'colore' => $u['colore'], 'avatar' => $u['avatar']];
        }
        
        foreach ($events as &$event) {
            $partecipantiIds = json_decode($event['partecipanti_json'] ?? '[]', true) ?: [];
            $event['partecipanti_list'] = [];
            foreach ($partecipantiIds as $pid) {
                if (isset($allUtenti[$pid])) {
                    $event['partecipanti_list'][] = $allUtenti[$pid];
                }
            }
        }
        unset($event);
        
        // Progetti con consegna prevista
        $stmt = $pdo->prepare("
            SELECT id, titolo, data_consegna_prevista, stato_progetto
            FROM progetti
            WHERE DATE(data_consegna_prevista) BETWEEN ? AND ?
            AND stato_progetto NOT IN ('consegnato', 'archiviato')
        ");
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
        
        jsonResponse(true, $events);
        
    } catch (PDOException $e) {
        error_log("Errore caricamento eventi: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento eventi');
    }
}

/**
 * Crea evento
 */
function createEvent(): void {
    global $pdo;
    
    $titolo = trim($_POST['titolo'] ?? '');
    $dataInizio = $_POST['data_inizio'] ?? '';
    
    if (empty($titolo) || empty($dataInizio)) {
        jsonResponse(false, null, 'Titolo e data sono obbligatori');
    }
    
    try {
        $id = generateEntityId('evt');
        $partecipanti = json_encode($_POST['partecipanti'] ?? []);
        
        $stmt = $pdo->prepare("
            INSERT INTO appuntamenti (
                id, titolo, tipo, data_inizio, data_fine, progetto_id, task_id, utente_id, note, partecipanti, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $id,
            $titolo,
            $_POST['tipo'] ?? 'appuntamento',
            $dataInizio,
            $_POST['data_fine'] ?: null,
            $_POST['progetto_id'] ?: null,
            $_POST['task_id'] ?: null,
            $_POST['utente_id'] ?: null,
            $_POST['note'] ?? '',
            $partecipanti,
            $_SESSION['user_id']
        ]);
        
        logTimeline($_SESSION['user_id'], 'creato_appuntamento', 'appuntamento', $id, "Creato: {$titolo}");
        
        // Crea notifica per tutti gli utenti
        creaNotifica(
            'appuntamento',
            'Nuovo Appuntamento',
            "{$titolo} - " . date('d/m/Y H:i', strtotime($dataInizio)),
            'appuntamento',
            $id,
            $_SESSION['user_id']
        );
        
        jsonResponse(true, ['id' => $id], 'Appuntamento creato');
        
    } catch (PDOException $e) {
        error_log("Errore creazione evento: " . $e->getMessage());
        jsonResponse(false, null, 'Errore creazione evento');
    }
}

/**
 * Aggiorna evento
 */
function updateEvent(string $id): void {
    global $pdo;
    
    try {
        $partecipanti = json_encode($_POST['partecipanti'] ?? []);
        
        $stmt = $pdo->prepare("
            UPDATE appuntamenti SET
                titolo = ?,
                data_inizio = ?,
                data_fine = ?,
                note = ?,
                partecipanti = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['titolo'],
            $_POST['data_inizio'],
            $_POST['data_fine'] ?: null,
            $_POST['note'] ?? '',
            $partecipanti,
            $id
        ]);
        
        jsonResponse(true, null, 'Appuntamento aggiornato');
        
    } catch (PDOException $e) {
        error_log("Errore aggiornamento evento: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento evento');
    }
}

/**
 * Elimina evento
 */
function deleteEvent(string $id): void {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM appuntamenti WHERE id = ?");
        $stmt->execute([$id]);
        
        jsonResponse(true, null, 'Appuntamento eliminato');
        
    } catch (PDOException $e) {
        error_log("Errore eliminazione evento: " . $e->getMessage());
        jsonResponse(false, null, 'Errore eliminazione evento');
    }
}
