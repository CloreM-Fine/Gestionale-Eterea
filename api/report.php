<?php
/**
 * Eterea Gestionale
 * API Report - Statistiche e analisi
 */

// DEBUG - Identificazione errore
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

$debug_log = [];
$debug_log[] = 'Start';

try {
    $debug_log[] = 'Before functions.php';
    require_once __DIR__ . '/../includes/functions.php';
    $debug_log[] = 'After functions.php';
    
    $debug_log[] = 'Before config.php';
    require_once __DIR__ . '/../includes/config.php';
    $debug_log[] = 'After config.php';
} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Debug: " . print_r($debug_log, true);
    exit;
}

// Nessun output prima di jsonResponse
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Autenticazione manuale (senza auth_check.php che manda redirect)
session_start();
if (empty($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => false, 'message' => 'Non autenticato'));
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($method !== 'GET') {
    jsonResponse(false, null, 'Metodo non consentito');
}

switch ($action) {
    case 'dashboard':
        reportDashboardStats();
        break;
    case 'utenti':
        reportUtentiStats();
        break;
    case 'progetti':
        reportProgettiStats();
        break;
    case 'economico':
        reportEconomicoStats();
        break;
    case 'temporale':
        reportTemporaleStats();
        break;
    default:
        jsonResponse(false, null, 'Azione non valida');
}

function reportDashboardStats() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT 
            COUNT(*) as totale,
            SUM(CASE WHEN stato = 'in_corso' THEN 1 ELSE 0 END) as in_corso,
            SUM(CASE WHEN stato = 'completato' THEN 1 ELSE 0 END) as completati,
            SUM(CASE WHEN stato = 'archiviato' THEN 1 ELSE 0 END) as archiviati
            FROM progetti");
        $progetti = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SELECT 
            COUNT(*) as totale,
            SUM(CASE WHEN stato = 'da_fare' THEN 1 ELSE 0 END) as da_fare,
            SUM(CASE WHEN stato = 'in_lavorazione' THEN 1 ELSE 0 END) as in_lavorazione,
            SUM(CASE WHEN stato = 'completato' THEN 1 ELSE 0 END) as completate
            FROM task");
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $tempoTotale = 0;
        $costiTask = 0;
        $budgetProgetti = 0;
        $utentiAttivi = 0;
        
        try {
            $stmt = $pdo->query("SELECT SUM(tempo_impiegato_seconds) FROM task");
            $tempoTotale = (int)$stmt->fetchColumn();
        } catch (Exception $e) {}
        
        try {
            $stmt = $pdo->query("SELECT SUM(costo_calcolato) FROM task");
            $costiTask = (float)$stmt->fetchColumn();
        } catch (Exception $e) {}
        
        try {
            $stmt = $pdo->query("SELECT SUM(budget) FROM progetti");
            $budgetProgetti = (float)$stmt->fetchColumn();
        } catch (Exception $e) {}
        
        try {
            $stmt = $pdo->query("SELECT COUNT(DISTINCT utente_id) FROM task_timer WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $utentiAttivi = (int)$stmt->fetchColumn();
        } catch (Exception $e) {}
        
        jsonResponse(true, array(
            'progetti' => array(
                'totale' => (int)($progetti['totale'] ?? 0),
                'in_corso' => (int)($progetti['in_corso'] ?? 0),
                'completati' => (int)($progetti['completati'] ?? 0),
                'archiviati' => (int)($progetti['archiviati'] ?? 0)
            ),
            'task' => array(
                'totale' => (int)($task['totale'] ?? 0),
                'da_fare' => (int)($task['da_fare'] ?? 0),
                'in_lavorazione' => (int)($task['in_lavorazione'] ?? 0),
                'completate' => (int)($task['completate'] ?? 0)
            ),
            'tempo' => array(
                'totale_secondi' => $tempoTotale,
                'ore' => round($tempoTotale / 3600, 1)
            ),
            'economico' => array(
                'costi_task' => round($costiTask, 2),
                'budget_progetti' => round($budgetProgetti, 2)
            ),
            'utenti_attivi_30gg' => $utentiAttivi
        ));
        
    } catch (Exception $e) {
        error_log("Errore dashboard: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento dashboard');
    }
}

function reportUtentiStats() {
    global $pdo;
    
    try {
        $utenti = array();
        $stmt = $pdo->query("SELECT id, nome, colore FROM utenti ORDER BY nome");
        while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $utenti[] = array(
                'id' => $u['id'],
                'nome' => $u['nome'],
                'colore' => $u['colore'],
                'task_assegnate' => 0,
                'task_completate' => 0,
                'tempo_ore' => 0,
                'costo_generato' => 0,
                'efficienza' => 0,
                'progetti_coinvolti' => 0
            );
        }
        
        try {
            $stmt = $pdo->query("SELECT id, assegnati, stato FROM task WHERE assegnati IS NOT NULL AND assegnati != ''");
            while ($t = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $assegnati = @json_decode($t['assegnati'], true);
                if (is_array($assegnati)) {
                    foreach ($assegnati as $uid) {
                        foreach ($utenti as &$u) {
                            if ($u['id'] === $uid) {
                                $u['task_assegnate']++;
                                if ($t['stato'] === 'completato') {
                                    $u['task_completate']++;
                                }
                                break;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {}
        
        foreach ($utenti as &$u) {
            if ($u['task_assegnate'] > 0) {
                $u['efficienza'] = round(($u['task_completate'] / $u['task_assegnate']) * 100, 1);
            }
        }
        
        jsonResponse(true, array(
            'periodo_giorni' => 30,
            'utenti' => $utenti
        ));
        
    } catch (Exception $e) {
        error_log("Errore utenti: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento report utenti');
    }
}

function reportProgettiStats() {
    global $pdo;
    
    $stato = $_GET['stato'] ?? 'tutti';
    
    try {
        // Query semplice senza subquery
        if ($stato !== 'tutti') {
            $stmt = $pdo->prepare("SELECT id, titolo, stato, budget, data_inizio, cliente_id FROM progetti WHERE stato = ? ORDER BY data_inizio DESC");
            $stmt->execute(array($stato));
        } else {
            $stmt = $pdo->query("SELECT id, titolo, stato, budget, data_inizio, cliente_id FROM progetti ORDER BY data_inizio DESC");
        }
        
        // Carica clienti per lookup
        $clienti = array();
        try {
            $stmtClienti = $pdo->query("SELECT id, ragione_sociale FROM clienti");
            while ($c = $stmtClienti->fetch(PDO::FETCH_ASSOC)) {
                $clienti[$c['id']] = $c['ragione_sociale'];
            }
        } catch (Exception $e) {}
        
        $progetti = array();
        while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $progetti[] = array(
                'id' => $p['id'],
                'titolo' => $p['titolo'],
                'stato' => $p['stato'],
                'cliente' => $clienti[$p['cliente_id']] ?? '-',
                'budget' => floatval($p['budget'] ?? 0),
                'totale_task' => 0,
                'task_completate' => 0,
                'costo_totale' => 0,
                'avanzamento' => 0,
                'margine' => 0
            );
        }
        
        try {
            $stmt = $pdo->query("SELECT progetto_id, stato FROM task");
            while ($t = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach ($progetti as &$p) {
                    if ($p['id'] === $t['progetto_id']) {
                        $p['totale_task']++;
                        if ($t['stato'] === 'completato') {
                            $p['task_completate']++;
                        }
                        break;
                    }
                }
            }
        } catch (Exception $e) {}
        
        foreach ($progetti as &$p) {
            if ($p['totale_task'] > 0) {
                $p['avanzamento'] = round(($p['task_completate'] / $p['totale_task']) * 100, 1);
            }
            $p['margine'] = $p['budget'] - $p['costo_totale'];
        }
        
        jsonResponse(true, array('progetti' => $progetti, 'riepilogo_stati' => array()));
        
    } catch (Exception $e) {
        error_log("Errore progetti: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento report progetti');
    }
}

function reportEconomicoStats() {
    global $pdo;
    
    $anno = intval($_GET['anno'] ?? date('Y'));
    
    try {
        $mensile = array();
        $totaleEntrate = 0;
        $totaleUscite = 0;
        
        try {
            $stmt = $pdo->prepare("SELECT 
                MONTH(data) as mese,
                SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END) as entrate,
                SUM(CASE WHEN tipo = 'uscita' THEN importo ELSE 0 END) as uscite
            FROM transazioni_economiche
            WHERE YEAR(data) = ?
            GROUP BY MONTH(data)");
            $stmt->execute(array($anno));
            $mensile = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("SELECT 
                SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END),
                SUM(CASE WHEN tipo = 'uscita' THEN importo ELSE 0 END)
            FROM transazioni_economiche WHERE YEAR(data) = ?");
            $stmt->execute(array($anno));
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $totaleEntrate = floatval($row[0] ?? 0);
            $totaleUscite = floatval($row[1] ?? 0);
        } catch (Exception $e) {}
        
        jsonResponse(true, array(
            'anno' => $anno,
            'mensile' => $mensile,
            'costi_progetto' => array(),
            'costi_utente' => array(),
            'totale' => array(
                'entrate' => round($totaleEntrate, 2),
                'uscite' => round($totaleUscite, 2),
                'saldo' => round($totaleEntrate - $totaleUscite, 2)
            )
        ));
        
    } catch (Exception $e) {
        error_log("Errore economico: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento report economico');
    }
}

function reportTemporaleStats() {
    global $pdo;
    
    $mesi = intval($_GET['mesi'] ?? 6);
    
    try {
        $taskCompletate = array();
        $nuoviProgetti = array();
        
        try {
            $stmt = $pdo->prepare("SELECT 
                DATE_FORMAT(updated_at, '%Y-%m') as periodo,
                COUNT(*) as task_completate
            FROM task
            WHERE stato = 'completato' AND updated_at > DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(updated_at, '%Y-%m')");
            $stmt->execute(array($mesi));
            $taskCompletate = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        
        try {
            $stmt = $pdo->prepare("SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as periodo,
                COUNT(*) as nuovi_progetti
            FROM progetti
            WHERE created_at > DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')");
            $stmt->execute(array($mesi));
            $nuoviProgetti = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        
        jsonResponse(true, array(
            'periodo_mesi' => $mesi,
            'task_completate' => $taskCompletate,
            'ore_lavorate' => array(),
            'nuovi_progetti' => $nuoviProgetti
        ));
        
    } catch (Exception $e) {
        error_log("Errore temporale: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento report temporale');
    }
}
