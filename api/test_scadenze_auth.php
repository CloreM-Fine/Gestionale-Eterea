<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurazione sessione
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simula login
$_SESSION['user_id'] = 'ucwurog3xr8tf';
$_SESSION['user_name'] = 'Lorenzo';

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$results = [];

// Test 1: isLoggedIn
$results['isLoggedIn'] = isLoggedIn();
$results['isAdmin'] = isAdmin();

// Test 2: Query scadenze semplice
try {
    $sql = "SELECT COUNT(*) FROM scadenze WHERE stato = 'aperta'";
    $stmt = $pdo->prepare($sql);
    $results['prepare_count'] = ($stmt === false) ? 'FALSE' : 'OK';
    if ($stmt) {
        $stmt->execute();
        $results['count'] = $stmt->fetchColumn();
    }
} catch (Throwable $e) {
    $results['count_error'] = $e->getMessage();
}

// Test 3: Query completa getScadenze
try {
    $sql = "SELECT s.*, st.nome as tipologia_nome, st.colore as tipologia_colore,
                   c.nome as cliente_nome, u.nome as user_nome
            FROM scadenze s
            LEFT JOIN scadenze_tipologie st ON s.tipologia_id = st.id
            LEFT JOIN clienti c ON s.cliente_id = c.id
            LEFT JOIN utenti u ON s.user_id = u.id
            WHERE 1=1
            ORDER BY s.data_scadenza ASC, s.id DESC
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $results['prepare_full'] = ($stmt === false) ? 'FALSE' : 'OK';
    if ($stmt) {
        $stmt->execute();
        $results['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $results['full_error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
