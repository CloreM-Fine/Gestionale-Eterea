<?php
/**
 * Eterea Gestionale
 * API Report - Statistiche e analisi
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth_check.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

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
            case 'dettaglio_utente':
                getDettaglioUtente($_GET['utente_id'] ?? '');
                break;
            case 'dettaglio_progetto':
                getDettaglioProgetto($_GET['progetto_id'] ?? '');
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
function getDashboardStats(): void {
    global $pdo;
    
    try {
        // Progetti
        $stmt = $pdo->query("SELECT 
            COUNT(*) as totale,
            SUM(CASE WHEN stato = 'in_corso' THEN 1 ELSE 0 END) as in_corso,
            SUM(CASE WHEN stato = 'completato' THEN 1 ELSE 0 END) as completati,
            SUM(CASE WHEN stato = 'archiviato' THEN 1 ELSE 0 END) as archiviati
            FROM progetti");
        $progetti = $stmt->fetch();
        
        // Task
        $stmt = $pdo->query("SELECT 
            COUNT(*) as totale,
            SUM(CASE WHEN stato = 'da_fare' THEN 1 ELSE 0 END) as da_fare,
            SUM(CASE WHEN stato = 'in_lavorazione' THEN 1 ELSE 0 END) as in_lavorazione,
            SUM(CASE WHEN stato = 'completato' THEN 1 ELSE 0 END) as completate
            FROM task");
        $task = $stmt->fetch();
        
        // Tempo totale registrato
        $stmt = $pdo->query("SELECT SUM(tempo_impiegato_seconds) as totale FROM task");
        $tempo = $stmt->fetch();
        
        // Fatturato totale
        $stmt = $pdo->query("SELECT 
            SUM(costo_calcolato) as costi_task,
            SUM(budget) as budget_progetti
            FROM task t 
            LEFT JOIN progetti p ON t.progetto_id = p.id");
        $economico = $stmt->fetch();
        
        // Utenti attivi
        $stmt = $pdo->query("SELECT COUNT(DISTINCT utente_id) as attivi FROM task_timer WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $utentiAttivi = $stmt->fetch();
        
        jsonResponse(true, [
            'progetti' => [
                'totale' => (int)$progetti['totale'],
                'in_corso' => (int)$progetti['in_corso'],
                'completati' => (int)$progetti['completati'],
                'archiviati' => (int)$progetti['archiviati']
            ],
            'task' => [
                'totale' => (int)$task['totale'],
                'da_fare' => (int)$task['da_fare'],
                'in_lavorazione' => (int)$task['in_lavorazione'],
                'completate' => (int)$task['completate']
            ],
            'tempo' => [
                'totale_secondi' => (int)$tempo['totale'],
                'ore' => round($tempo['totale'] / 3600, 1)
            ],
            'economico' => [
                'costi_task' => round($economico['costi_task'] ?? 0, 2),
                'budget_progetti' => round($economico['budget_progetti'] ?? 0, 2)
            ],
            'utenti_attivi_30gg' => (int)$utentiAttivi['attivi']
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore dashboard stats: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento statistiche');
    }
}

/**
 * Report per utenti
 */
function getUtentiReport(): void {
    global $pdo;
    
    $periodo = $_GET['periodo'] ?? '30'; // giorni
    
    try {
        // Statistiche per ogni utente
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.nome,
                u.colore,
                COUNT(DISTINCT t.id) as task_assegnate,
                SUM(CASE WHEN t.stato = 'completato' THEN 1 ELSE 0 END) as task_completate,
                SUM(tt.total_seconds) as tempo_lavorato,
                SUM(t.costo_calcolato) as costo_generato
            FROM utenti u
            LEFT JOIN task t ON JSON_CONTAINS(t.assegnati, JSON_QUOTE(u.id))
            LEFT JOIN task_timer tt ON tt.utente_id = u.id 
                AND tt.created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY u.id, u.nome, u.colore
            ORDER BY tempo_lavorato DESC
        ");
        $stmt->execute([$periodo]);
        $utenti = $stmt->fetchAll();
        
        // Formatta dati
        foreach ($utenti as &$u) {
            $u['tempo_ore'] = round(($u['tempo_lavorato'] ?? 0) / 3600, 1);
            $u['costo_generato'] = round($u['costo_generato'] ?? 0, 2);
            $u['efficienza'] = $u['task_assegnate'] > 0 
                ? round(($u['task_completate'] / $u['task_assegnate']) * 100, 1) 
                : 0;
        }
        
        // Progetti per utente
        $stmt = $pdo->query("
            SELECT 
                u.id as utente_id,
                COUNT(DISTINCT p.id) as progetti_coinvolti
            FROM utenti u
            LEFT JOIN progetti p ON JSON_CONTAINS(p.partecipanti, JSON_QUOTE(u.id))
            GROUP BY u.id
        ");
        $progettiPerUtente = [];
        while ($row = $stmt->fetch()) {
            $progettiPerUtente[$row['utente_id']] = $row['progetti_coinvolti'];
        }
        
        foreach ($utenti as &$u) {
            $u['progetti_coinvolti'] = $progettiPerUtente[$u['id']] ?? 0;
        }
        
        jsonResponse(true, [
            'periodo_giorni' => (int)$periodo,
            'utenti' => $utenti
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore utenti report: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento report utenti');
    }
}

/**
 * Report progetti
 */
function getProgettiReport(): void {
    global $pdo;
    
    $stato = $_GET['stato'] ?? 'tutti';
    
    try {
        $where = '';
        $params = [];
        if ($stato !== 'tutti') {
            $where = "WHERE p.stato = ?";
            $params[] = $stato;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.titolo,
                p.stato,
                p.budget,
                p.data_inizio,
                p.data_fine_prevista,
                p.data_fine_reale,
                c.ragione_sociale as cliente,
                COUNT(DISTINCT t.id) as totale_task,
                SUM(CASE WHEN t.stato = 'completato' THEN 1 ELSE 0 END) as task_completate,
                SUM(t.tempo_impiegato_seconds) as tempo_impiegato,
                SUM(t.costo_calcolato) as costo_totale
            FROM progetti p
            LEFT JOIN clienti c ON p.cliente_id = c.id
            LEFT JOIN task t ON t.progetto_id = p.id
            $where
            GROUP BY p.id, p.titolo, p.stato, p.budget, p.data_inizio, p.data_fine_prevista, p.data_fine_reale, c.ragione_sociale
            ORDER BY p.data_inizio DESC
        ");
        $stmt->execute($params);
        $progetti = $stmt->fetchAll();
        
        // Formatta dati
        foreach ($progetti as &$p) {
            $p['tempo_ore'] = round(($p['tempo_impiegato'] ?? 0) / 3600, 1);
            $p['costo_totale'] = round($p['costo_totale'] ?? 0, 2);
            $p['budget'] = round($p['budget'] ?? 0, 2);
            $p['margine'] = $p['budget'] - $p['costo_totale'];
            $p['margine_percentuale'] = $p['budget'] > 0 
                ? round(($p['margine'] / $p['budget']) * 100, 1) 
                : 0;
            $p['avanzamento'] = $p['totale_task'] > 0 
                ? round(($p['task_completate'] / $p['totale_task']) * 100, 1) 
                : 0;
        }
        
        // Riepilogo per stato
        $stmt = $pdo->query("
            SELECT 
                stato,
                COUNT(*) as count,
                SUM(budget) as budget_totale
            FROM progetti
            GROUP BY stato
        ");
        $riepilogo = $stmt->fetchAll();
        
        jsonResponse(true, [
            'progetti' => $progetti,
            'riepilogo_stati' => $riepilogo
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore progetti report: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento report progetti');
    }
}

/**
 * Report economico
 */
function getEconomicoReport(): void {
    global $pdo;
    
    $anno = $_GET['anno'] ?? date('Y');
    
    try {
        // Entrate/uscite per mese
        $stmt = $pdo->prepare("
            SELECT 
                MONTH(data) as mese,
                SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END) as entrate,
                SUM(CASE WHEN tipo = 'uscita' THEN importo ELSE 0 END) as uscite,
                SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE -importo END) as saldo
            FROM transazioni_economiche
            WHERE YEAR(data) = ?
            GROUP BY MONTH(data)
            ORDER BY mese
        ");
        $stmt->execute([$anno]);
        $mensile = $stmt->fetchAll();
        
        // Costi per progetto (anno corrente)
        $stmt = $pdo->query("
            SELECT 
                p.titolo,
                SUM(t.costo_calcolato) as costo_totale
            FROM progetti p
            LEFT JOIN task t ON t.progetto_id = p.id
            WHERE t.costo_calcolato > 0
            GROUP BY p.id, p.titolo
            ORDER BY costo_totale DESC
            LIMIT 10
        ");
        $costiProgetto = $stmt->fetchAll();
        
        // Costi per utente
        $stmt = $pdo->query("
            SELECT 
                u.nome,
                SUM(t.costo_calcolato) as costo_generato
            FROM utenti u
            LEFT JOIN task t ON JSON_CONTAINS(t.assegnati, JSON_QUOTE(u.id))
            WHERE t.costo_calcolato > 0
            GROUP BY u.id, u.nome
            ORDER BY costo_generato DESC
        ");
        $costiUtente = $stmt->fetchAll();
        
        // Totale economico
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN tipo = 'entrata' THEN importo ELSE 0 END) as totale_entrate,
                SUM(CASE WHEN tipo = 'uscita' THEN importo ELSE 0 END) as totale_uscite
            FROM transazioni_economiche
            WHERE YEAR(data) = ?
        ");
        $stmt->execute([$anno]);
        $totale = $stmt->fetch();
        
        jsonResponse(true, [
            'anno' => (int)$anno,
            'mensile' => $mensile,
            'costi_progetto' => $costiProgetto,
            'costi_utente' => $costiUtente,
            'totale' => [
                'entrate' => round($totale['totale_entrate'] ?? 0, 2),
                'uscite' => round($totale['totale_uscite'] ?? 0, 2),
                'saldo' => round(($totale['totale_entrate'] ?? 0) - ($totale['totale_uscite'] ?? 0), 2)
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore economico report: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento report economico');
    }
}

/**
 * Report temporale - andamento nel tempo
 */
function getTemporaleReport(): void {
    global $pdo;
    
    $mesi = $_GET['mesi'] ?? 6;
    
    try {
        // Task completate per mese
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(completato_il, '%Y-%m') as periodo,
                COUNT(*) as task_completate
            FROM task
            WHERE completato_il > DATE_SUB(NOW(), INTERVAL ? MONTH)
                AND stato = 'completato'
            GROUP BY DATE_FORMAT(completato_il, '%Y-%m')
            ORDER BY periodo
        ");
        $stmt->execute([$mesi]);
        $taskCompletate = $stmt->fetchAll();
        
        // Ore lavorate per mese
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as periodo,
                SUM(total_seconds) / 3600 as ore_lavorate
            FROM task_timer
            WHERE created_at > DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY periodo
        ");
        $stmt->execute([$mesi]);
        $oreLavorate = $stmt->fetchAll();
        
        // Nuovi progetti per mese
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as periodo,
                COUNT(*) as nuovi_progetti
            FROM progetti
            WHERE created_at > DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY periodo
        ");
        $stmt->execute([$mesi]);
        $nuoviProgetti = $stmt->fetchAll();
        
        jsonResponse(true, [
            'periodo_mesi' => (int)$mesi,
            'task_completate' => $taskCompletate,
            'ore_lavorate' => $oreLavorate,
            'nuovi_progetti' => $nuoviProgetti
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore temporale report: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento report temporale');
    }
}

/**
 * Dettaglio per singolo utente
 */
function getDettaglioUtente(string $utenteId): void {
    global $pdo;
    
    if (empty($utenteId)) {
        jsonResponse(false, null, 'ID utente mancante');
        return;
    }
    
    try {
        // Info utente
        $stmt = $pdo->prepare("SELECT id, nome, email, colore FROM utenti WHERE id = ?");
        $stmt->execute([$utenteId]);
        $utente = $stmt->fetch();
        
        if (!$utente) {
            jsonResponse(false, null, 'Utente non trovato');
            return;
        }
        
        // Task recenti
        $stmt = $pdo->prepare("
            SELECT t.*, p.titolo as progetto_titolo
            FROM task t
            JOIN progetti p ON t.progetto_id = p.id
            WHERE JSON_CONTAINS(t.assegnati, JSON_QUOTE(?))
            ORDER BY t.updated_at DESC
            LIMIT 20
        ");
        $stmt->execute([$utenteId]);
        $task = $stmt->fetchAll();
        
        // Timeline lavoro
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as data,
                SUM(total_seconds) / 3600 as ore
            FROM task_timer
            WHERE utente_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY data DESC
        ");
        $stmt->execute([$utenteId]);
        $timeline = $stmt->fetchAll();
        
        jsonResponse(true, [
            'utente' => $utente,
            'task_recenti' => $task,
            'timeline_30gg' => $timeline
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore dettaglio utente: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento dettaglio');
    }
}

/**
 * Dettaglio per singolo progetto
 */
function getDettaglioProgetto(string $progettoId): void {
    global $pdo;
    
    if (empty($progettoId)) {
        jsonResponse(false, null, 'ID progetto mancante');
        return;
    }
    
    try {
        // Info progetto
        $stmt = $pdo->prepare("
            SELECT p.*, c.ragione_sociale as cliente
            FROM progetti p
            LEFT JOIN clienti c ON p.cliente_id = c.id
            WHERE p.id = ?
        ");
        $stmt->execute([$progettoId]);
        $progetto = $stmt->fetch();
        
        if (!$progetto) {
            jsonResponse(false, null, 'Progetto non trovato');
            return;
        }
        
        // Task per stato
        $stmt = $pdo->prepare("
            SELECT stato, COUNT(*) as count
            FROM task
            WHERE progetto_id = ?
            GROUP BY stato
        ");
        $stmt->execute([$progettoId]);
        $taskStati = $stmt->fetchAll();
        
        // Tempo per utente su questo progetto
        $stmt = $pdo->prepare("
            SELECT 
                u.nome,
                SUM(tt.total_seconds) / 3600 as ore
            FROM task_timer tt
            JOIN task t ON tt.task_id = t.id
            JOIN utenti u ON tt.utente_id = u.id
            WHERE t.progetto_id = ?
            GROUP BY u.id, u.nome
        ");
        $stmt->execute([$progettoId]);
        $orePerUtente = $stmt->fetchAll();
        
        jsonResponse(true, [
            'progetto' => $progetto,
            'task_stati' => $taskStati,
            'ore_per_utente' => $orePerUtente
        ]);
        
    } catch (PDOException $e) {
        error_log("Errore dettaglio progetto: " . $e->getMessage());
        jsonResponse(false, null, 'Errore caricamento dettaglio');
    }
}
