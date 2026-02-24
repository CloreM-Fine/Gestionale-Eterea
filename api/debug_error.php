<?php
/**
 * Debug errore specifico
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurazione sessione
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Simula login per test
$_SESSION['user_id'] = 'ucwurog3xr8tf';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$results = [];

// Test 1: Verifica connessione
$results['pdo_defined'] = isset($pdo);
$results['pdo_class'] = isset($pdo) ? get_class($pdo) : 'null';

// Test 2: Query semplice
if (isset($pdo)) {
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->query("SELECT 1 as test");
        $results['query_simple'] = $stmt->fetch();
    } catch (Throwable $e) {
        $results['query_simple_error'] = $e->getMessage();
    }
}

// Test 3: Query scadenze (come in countScadenzeOggi)
if (isset($pdo)) {
    try {
        $sql = "SELECT COUNT(*) as count FROM scadenze WHERE data_scadenza = CURDATE() AND stato = 'aperta'";
        $stmt = $pdo->prepare($sql);
        $results['prepare_scadenze'] = ($stmt === false) ? 'FALSE' : 'OK';
        if ($stmt) {
            $stmt->execute([]);
            $results['execute_scadenze'] = $stmt->fetchColumn();
        }
    } catch (Throwable $e) {
        $results['scadenze_error'] = $e->getMessage();
        $results['scadenze_trace'] = $e->getTraceAsString();
    }
}

// Test 4: Query contabilita (come in getRiepilogoMensile)
if (isset($pdo)) {
    try {
        $dataInizio = '2025-02-01';
        $dataFine = '2025-02-28';
        $sql = "SELECT SUM(prezzo_totale) as totale, COUNT(*) as numero FROM progetti WHERE stato_progetto = 'completato' AND DATE(data_consegna) BETWEEN :inizio AND :fine";
        $stmt = $pdo->prepare($sql);
        $results['prepare_contabilita'] = ($stmt === false) ? 'FALSE' : 'OK';
        if ($stmt) {
            $stmt->execute([':inizio' => $dataInizio, ':fine' => $dataFine]);
            $results['execute_contabilita'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        $results['contabilita_error'] = $e->getMessage();
        $results['contabilita_trace'] = $e->getTraceAsString();
    }
}

// Test 5: Verifica esistenza tabelle
if (isset($pdo)) {
    try {
        $tables = ['scadenze', 'scadenze_tipologie', 'contabilita_mensile', 'progetti'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $results["table_$table"] = $stmt->fetch() ? 'ESISTE' : 'MANCANTE';
        }
    } catch (Throwable $e) {
        $results['tables_error'] = $e->getMessage();
    }
}

// Test 6: Error info PDO
if (isset($pdo)) {
    $results['pdo_error_info'] = $pdo->errorInfo();
}

echo json_encode($results, JSON_PRETTY_PRINT);
