<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 'ucwurog3xr8tf';

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$tests = [];

// Test 1: Solo scadenze
$sql = "SELECT * FROM scadenze LIMIT 1";
$stmt = $pdo->prepare($sql);
$tests['solo_scadenze'] = ($stmt === false) ? 'FALSE: ' . print_r($pdo->errorInfo(), true) : 'OK';

// Test 2: JOIN scadenze_tipologie
$sql = "SELECT s.*, st.nome as tipologia_nome FROM scadenze s LEFT JOIN scadenze_tipologie st ON s.tipologia_id = st.id LIMIT 1";
$stmt = $pdo->prepare($sql);
$tests['join_tipologie'] = ($stmt === false) ? 'FALSE: ' . print_r($pdo->errorInfo(), true) : 'OK';

// Test 3: JOIN clienti
$sql = "SELECT s.*, c.nome as cliente_nome FROM scadenze s LEFT JOIN clienti c ON s.cliente_id = c.id LIMIT 1";
$stmt = $pdo->prepare($sql);
$tests['join_clienti'] = ($stmt === false) ? 'FALSE: ' . print_r($pdo->errorInfo(), true) : 'OK';

// Test 4: JOIN utenti
$sql = "SELECT s.*, u.nome as user_nome FROM scadenze s LEFT JOIN utenti u ON s.user_id = u.id LIMIT 1";
$stmt = $pdo->prepare($sql);
$tests['join_utenti'] = ($stmt === false) ? 'FALSE: ' . print_r($pdo->errorInfo(), true) : 'OK';

// Verifica esistenza tabelle
$tables = ['scadenze', 'scadenze_tipologie', 'clienti', 'utenti'];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM $t LIMIT 1");
        $tests["table_$t"] = 'ESISTE';
    } catch (Throwable $e) {
        $tests["table_$t"] = 'ERRORE: ' . $e->getMessage();
    }
}

echo json_encode($tests, JSON_PRETTY_PRINT);
