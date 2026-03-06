<?php
// API MINIMAL - Solo PDO
header('Content-Type: application/json');

try {
    // Connessione diretta senza includes
    $host = 'localhost';
    $db   = 'dbqdsx4jwrcdsg';
    $user = 'ugv7adudxudhx';
    $pass = '';
    
    // Prova a leggere da .env
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'DB_PASS=') === 0) {
                $pass = substr($line, 8);
                break;
            }
        }
    }
    
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $action = $_GET['action'] ?? 'dashboard';
    
    if ($action === 'dashboard') {
        $stmt = $pdo->query("SELECT COUNT(*) FROM progetti");
        $progetti = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM task");
        $task = $stmt->fetchColumn();
        
        echo json_encode(array(
            'success' => true,
            'data' => array(
                'progetti' => array('totale' => (int)$progetti, 'in_corso' => 0, 'completati' => 0, 'archiviati' => 0),
                'task' => array('totale' => (int)$task, 'da_fare' => 0, 'in_lavorazione' => 0, 'completate' => 0),
                'tempo' => array('totale_secondi' => 0, 'ore' => 0),
                'economico' => array('costi_task' => 0, 'budget_progetti' => 0),
                'utenti_attivi_30gg' => 0
            )
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Azione non supportata in minimal'));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
