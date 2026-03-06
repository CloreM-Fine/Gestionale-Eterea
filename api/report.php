<?php
/**
 * Eterea Gestionale
 * API Report - Statistiche e analisi
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Abilita error reporting per debug
error_reporting(E_ALL);
ini_set('display_errors', '0');

switch ($method) {
    case 'GET':
        switch ($action) {
            case 'dashboard':
                getDashboardStats();
                break;
            case 'utenti':
                getUtentiReport();
                break;
            case 'progetti':
                getProgettiReport();
                break;
            case 'economico':
                getEconomicoReport();
                break;
            case 'temporale':
                getTemporaleReport();
                break;
            default:
                jsonResponse(false, null, 'Azione non valida');
        }
        break;
    default:
        jsonResponse(false, null, 'Metodo non consentito');
}

/**
 * Statistiche generali per dashboard
 */
function getDashboardStats() {
    global $pdo;
    
    try {
        // Progetti
        $stmt = $pdo->query("SELECT 
            COUNT(*) as totale,
            SUM(CASE WHEN stato = 'in_corso' THEN 1 ELSE 0 END) as in_corso,
            SUM(CASE WHEN stato = 'completato' THEN 1 ELSE 0 END) as completati,
            SUM(CASE WHEN stato = 'archiviato' THEN 1 ELSE 0 END) as archiviati
            FROM progetti");
        $progetti = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Task
        $stmt = $pdo->query("SELECT 
            COUNT(*) as totale,
            SUM(CASE WHEN stato = 'da_fare' THEN 1 ELSE 0 END) as da_fare,
            SUM(CASE WHEN stato = 'in_lavorazione' THEN 1 ELSE 0 END) as in_lavorazione,
            SUM(CASE WHEN stato = 'completato' THEN 1 ELSE 0 END) as completate
            FROM task");
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Valori di default
        $tempoTotale = 0;
        $costiTask = 0;
        $budgetProgetti = 0;
        $utentiAttivi = 0;
        
        // Prova colonne opzionali
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
                'totale' => (int)$progetti['totale'],
                'in_corso' => (int)$progetti['in_corso'],
                'completati' => (int)$progetti['completati'],
                'archiviati' => (int)$progetti['archiviati']
            ),
            'task' => array(
                'totale' => (int)$task['totale'],
                'da_fare' => (int)$task['da_fare'],
                'in_lavorazione' => (int)$task['in_lavorazione'],
                'completate' => (int)$task['completate']
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

/**
 * Report per utenti
 */
function getUtentiReport() {
    global $pdo;
    
    $periodo = intval($_GET['periodo'] ?? 30);
    
    try {
        $utenti = array();
        
        // Query semplice senza join complessi
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
        
        // Conta task per utente
        try {
            $stmt = $pdo->query("SELECT id, assegnati, stato FROM task WHERE assegnati IS NOT NULL");
            while ($t = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $assegnati = json_decode($t['assegnati'], true);
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
        
        // Calcola efficienza
        foreach ($utenti as &$u) {
            if ($u['task_assegnate'] > 0) {
                $u['efficienza'] = round(($u['task_completate'] / $u['task_assegnate']) * 100, 1);
            }
        }
        
        jsonResponse(true, array(
            'periodo_giorni' => $periodo,
            'utenti' => $utenti
        ));
        
    } catch (Exception $e) {
        error_log("Errore utenti: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento report utenti');
    }
}

/**
 * Report progetti
 */
function getProgettiReport() {
    global $pdo;
    
    $stato = $_GET['stato'] ?? 'tutti';
    
    try {
        $where = '';
        $params = array();
        if ($stato !== 'tutti') {
            $where = "WHERE stato = ?";
            $params[] = $stato;
        }
        
        $stmt = $pdo->prepare("SELECT 
            id, titolo, stato, budget, data_inizio,
            (SELECT ragione_sociale FROM clienti c WHERE c.id = p.cliente_id) as cliente
            FROM progetti p
            $where
            ORDER BY data_inizio DESC");
        $stmt->execute($params);
        
        $progetti = array();
        while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $progetti[] = array(
                'id' => $p['id'],
                'titolo' => $p['titolo'],
                'stato' => $p['stato'],
                'cliente' => $p['cliente'] ?? '-',
                'budget' => floatval($p['budget'] ?? 0),
                'totale_task' => 0,
                'task_completate' => 0,
                'costo_totale' => 0,
                'avanzamento' => 0,
                'margine' => 0
            );
        }
        
        // Conta task per progetto
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
        
        // Calcola avanzamento e margine
        foreach ($progetti as &$p) {
            if ($p['totale_task'] > 0) {
                $p['avanzamento'] = round(($p['task_completate'] / $p['totale_task']) * 100, 1);
            }
            $p['margine'] = $p['budget'] - $p['costo_totale'];
        }
        
        jsonResponse(true, array(
            'progetti' => $progetti,
            'riepilogo_stati' => array()
        ));
        
    } catch (Exception $e) {
        error_log("Errore progetti: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento report progetti');
    }
}

/**
 * Report economico
 */
function getEconomicoReport() {
    global $pdo;
    
    $anno = intval($_GET['anno'] ?? date('Y'));
    
    try {
        $mensile = array();
        $costiProgetto = array();
        $totaleEntrate = 0;
        $totaleUscite = 0;
        
        // Prova a leggere transazioni
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
            'costi_progetto' => $costiProgetto,
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

/**
 * Report temporale
 */
function getTemporaleReport() {
    global $pdo;
    
    $mesi = intval($_GET['mesi'] ?? 6);
    
    try {
        $taskCompletate = array();
        $oreLavorate = array();
        $nuoviProgetti = array();
        
        // Task completate per mese
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
        
        // Nuovi progetti
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
            'ore_lavorate' => $oreLavorate,
            'nuovi_progetti' => $nuoviProgetti
        ));
        
    } catch (Exception $e) {
        error_log("Errore temporale: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento report temporale');
    }
}
