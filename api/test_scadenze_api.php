<?php
/**
 * Test API scadenze con autenticazione simulata
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurazione come nelle API
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simula login
$_SESSION['user_id'] = 'ucwurog3xr8tf';
$_SESSION['user_name'] = 'Lorenzo';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$results = [];

// Test isLoggedIn
$results['isLoggedIn'] = isLoggedIn();
$results['isAdmin'] = isAdmin();

// Test getScadenze completa
try {
    $userId = $_SESSION['user_id'] ?? '';
    $isAdmin = isAdmin();
    
    $sql = "
        SELECT s.*, 
               st.nome as tipologia_nome, 
               st.colore as tipologia_colore,
               c.ragione_sociale as cliente_nome,
               u.nome as user_nome
        FROM scadenze s
        LEFT JOIN scadenze_tipologie st ON s.tipologia_id = st.id
        LEFT JOIN clienti c ON s.cliente_id = c.id
        LEFT JOIN utenti u ON s.user_id = u.id
        WHERE 1=1
        ORDER BY s.data_scadenza ASC, s.id DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    if ($stmt === false) {
        $results['prepare_error'] = print_r($pdo->errorInfo(), true);
    } else {
        $stmt->execute([]);
        $scadenze = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results['scadenze_count'] = count($scadenze);
        $results['scadenze_sample'] = array_slice($scadenze, 0, 2);
    }
} catch (Throwable $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
