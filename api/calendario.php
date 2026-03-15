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
                'cliente_nome' => '', // Popolato dopo
                'utente_id' => $row['utente_id'] ?? null,
                'note' => $row['note'] ?? '',
                'partecipanti' => json_decode($row['partecipanti'] ?? '[]', true) ?: []
            ];
        }
        
        // Recupera nomi clienti e utenti
        if (count($events) > 0) {
            try {
                // Mappa clienti
                $clientiIds = array_filter(array_column($events, 'cliente_id'));
                if (count($clientiIds) > 0) {
                    $placeholders = implode(',', array_fill(0, count($clientiIds), '?'));
                    $stmt = $pdo->prepare("SELECT id, ragione_sociale FROM clienti WHERE id IN ({$placeholders})");
                    $stmt->execute($clientiIds);
                    $clientiMap = [];
                    while ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $clientiMap[$c['id']] = $c['ragione_sociale'];
                    }
                    foreach ($events as &$e) {
                        if ($e['cliente_id'] && isset($clientiMap[$e['cliente_id']])) {
                            $e['cliente_nome'] = $clientiMap[$e['cliente_id']];
                        }
                    }
                    unset($e);
                }
                
                // Mappa utenti (partecipanti)
                $partecipantiIds = [];
                foreach ($events as $e) {
                    if (!empty($e['partecipanti']) && is_array($e['partecipanti'])) {
                        $partecipantiIds = array_merge($partecipantiIds, $e['partecipanti']);
                    }
                }
                $partecipantiIds = array_unique($partecipantiIds);
                if (count($partecipantiIds) > 0) {
                    $placeholders = implode(',', array_fill(0, count($partecipantiIds), '?'));
                    $stmt = $pdo->prepare("SELECT id, nome, colore FROM utenti WHERE id IN ({$placeholders})");
                    $stmt->execute($partecipantiIds);
                    $utentiMap = [];
                    while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $utentiMap[$u['id']] = $u;
                    }
                    foreach ($events as &$e) {
                        if (!empty($e['partecipanti']) && is_array($e['partecipanti'])) {
                            $e['partecipanti_dettagli'] = [];
                            foreach ($e['partecipanti'] as $pid) {
                                if (isset($utentiMap[$pid])) {
                                    $e['partecipanti_dettagli'][] = $utentiMap[$pid];
                                }
                            }
                        }
                    }
                    unset($e);
                }
            } catch (Exception $e) {
                error_log("Errore recupero clienti/utenti: " . $e->getMessage());
            }
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
        // Verifica quali colonne esistono
        $colonne = $pdo->query("SHOW COLUMNS FROM appuntamenti")->fetchAll(PDO::FETCH_COLUMN);
        
        $hasProgettoId = in_array('progetto_id', $colonne);
        $hasClienteId = in_array('cliente_id', $colonne);
        $hasPartecipanti = in_array('partecipanti', $colonne);
        
        $fields = ['id', 'titolo', 'tipo', 'data_inizio', 'data_fine', 'note'];
        $values = [
            generateEntityId('evt'),
            $titolo,
            $_POST['tipo'] ?? 'appuntamento',
            $dataInizio,
            $_POST['data_fine'] ?: null,
            $_POST['note'] ?? ''
        ];
        $placeholders = ['?', '?', '?', '?', '?', '?'];
        
        if ($hasProgettoId) {
            $fields[] = 'progetto_id';
            $values[] = $_POST['progetto_id'] ?: null;
            $placeholders[] = '?';
        }
        if ($hasClienteId) {
            $fields[] = 'cliente_id';
            $values[] = $_POST['cliente_id'] ?: null;
            $placeholders[] = '?';
        }
        if ($hasPartecipanti) {
            $fields[] = 'partecipanti';
            $values[] = json_encode($_POST['partecipanti'] ?? []);
            $placeholders[] = '?';
        }
        
        $sql = "INSERT INTO appuntamenti (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        jsonResponse(true, ['id' => $values[0]], 'Appuntamento creato');
    } catch (Exception $e) {
        error_log("Errore creazione: " . $e->getMessage());
        jsonResponse(false, null, 'Errore creazione: ' . $e->getMessage());
    }
}

function updateEvent($id) {
    global $pdo;
    
    try {
        // Verifica quali colonne esistono
        $colonne = $pdo->query("SHOW COLUMNS FROM appuntamenti")->fetchAll(PDO::FETCH_COLUMN);
        
        $hasProgettoId = in_array('progetto_id', $colonne);
        $hasClienteId = in_array('cliente_id', $colonne);
        $hasPartecipanti = in_array('partecipanti', $colonne);
        
        $fields = ['titolo = ?', 'tipo = ?', 'data_inizio = ?', 'data_fine = ?', 'note = ?'];
        $values = [
            $_POST['titolo'],
            $_POST['tipo'] ?? 'appuntamento',
            $_POST['data_inizio'],
            $_POST['data_fine'] ?: null,
            $_POST['note'] ?? ''
        ];
        
        if ($hasProgettoId) {
            $fields[] = 'progetto_id = ?';
            $values[] = $_POST['progetto_id'] ?: null;
        }
        if ($hasClienteId) {
            $fields[] = 'cliente_id = ?';
            $values[] = $_POST['cliente_id'] ?: null;
        }
        if ($hasPartecipanti) {
            $fields[] = 'partecipanti = ?';
            $values[] = json_encode($_POST['partecipanti'] ?? []);
        }
        
        $values[] = $id;
        $sql = "UPDATE appuntamenti SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        jsonResponse(true, null, 'Appuntamento aggiornato');
    } catch (Exception $e) {
        error_log("Errore update: " . $e->getMessage());
        jsonResponse(false, null, 'Errore aggiornamento: ' . $e->getMessage());
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
