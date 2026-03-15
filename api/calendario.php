<?php
/**
 * Eterea Gestionale
 * API Calendario - Versione semplificata
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($method === 'GET' && $action === 'events') {
    getEvents();
} elseif ($method === 'POST' && $action === 'create') {
    createEvent();
} elseif ($method === 'POST' && $action === 'update' && isset($_POST['id'])) {
    updateEvent($_POST['id']);
} elseif ($method === 'POST' && $action === 'delete') {
    $id = $_POST['id'] ?? $_GET['id'] ?? null;
    if ($id) deleteEvent($id);
    else jsonResponse(false, null, 'ID mancante');
} elseif ($method === 'POST' && $action === 'complete') {
    $id = $_POST['id'] ?? $_GET['id'] ?? null;
    if ($id) completeEvent($id);
    else jsonResponse(false, null, 'ID mancante');
} else {
    jsonResponse(false, null, 'Azione non valida');
}

function getEvents() {
    global $pdo;
    
    $start = $_GET['start'] ?? date('Y-m-01');
    $end = $_GET['end'] ?? date('Y-m-t');
    
    $events = [];
    
    // Query base appuntamenti
    try {
        $stmt = $pdo->prepare("SELECT * FROM appuntamenti WHERE data_inizio BETWEEN ? AND ? ORDER BY data_inizio ASC");
        $stmt->execute([$start . ' 00:00:00', $end . ' 23:59:59']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as $row) {
            $events[] = [
                'id' => $row['id'],
                'titolo' => $row['titolo'],
                'data_inizio' => $row['data_inizio'],
                'data_fine' => $row['data_fine'],
                'tipo' => $row['tipo'] ?? 'appuntamento',
                'progetto_id' => $row['progetto_id'],
                'cliente_id' => $row['cliente_id'] ?? null,
                'note' => $row['note'] ?? '',
                'partecipanti' => json_decode($row['partecipanti'] ?? '[]', true) ?: []
            ];
        }
    } catch (Exception $e) {
        error_log("Errore appuntamenti: " . $e->getMessage());
    }
    
    // Progetti
    try {
        $stmt = $pdo->prepare("SELECT id, titolo, data_consegna_prevista FROM progetti WHERE data_consegna_prevista BETWEEN ? AND ?");
        $stmt->execute([$start, $end]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as $row) {
            $events[] = [
                'id' => 'prj_' . $row['id'],
                'titolo' => 'Consegna: ' . $row['titolo'],
                'data_inizio' => $row['data_consegna_prevista'] . ' 00:00:00',
                'data_fine' => $row['data_consegna_prevista'] . ' 23:59:59',
                'tipo' => 'scadenza_progetto',
                'progetto_id' => $row['id'],
                'note' => '',
                'partecipanti_list' => []
            ];
        }
    } catch (Exception $e) {
        error_log("Errore progetti: " . $e->getMessage());
    }
    
    jsonResponse(true, $events);
}

function createEvent() {
    global $pdo;
    
    $titolo = trim($_POST['titolo'] ?? '');
    $dataInizio = $_POST['data_inizio'] ?? '';
    
    if (empty($titolo) || empty($dataInizio)) {
        jsonResponse(false, null, 'Titolo e data sono obbligatori');
        return;
    }
    
    try {
        $id = generateEntityId('evt');
        $stmt = $pdo->prepare("INSERT INTO appuntamenti (id, titolo, tipo, data_inizio, data_fine, progetto_id, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id,
            $titolo,
            $_POST['tipo'] ?? 'appuntamento',
            $dataInizio,
            $_POST['data_fine'] ?: null,
            $_POST['progetto_id'] ?: null,
            $_POST['note'] ?? ''
        ]);
        jsonResponse(true, ['id' => $id], 'Appuntamento creato');
    } catch (Exception $e) {
        error_log("Errore creazione: " . $e->getMessage());
        jsonResponse(false, null, 'Errore creazione: ' . $e->getMessage());
    }
}

function updateEvent($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE appuntamenti SET titolo = ?, tipo = ?, data_inizio = ?, data_fine = ?, note = ? WHERE id = ?");
        $stmt->execute([
            $_POST['titolo'],
            $_POST['tipo'] ?? 'appuntamento',
            $_POST['data_inizio'],
            $_POST['data_fine'] ?: null,
            $_POST['note'] ?? '',
            $id
        ]);
        jsonResponse(true, null, 'Appuntamento aggiornato');
    } catch (Exception $e) {
        error_log("Errore update: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento');
    }
}

function deleteEvent($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM appuntamenti WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, null, 'Appuntamento eliminato');
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore eliminazione');
    }
}

function completeEvent($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM appuntamenti WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true, null, 'Appuntamento completato');
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore');
    }
}
