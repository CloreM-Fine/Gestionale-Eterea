<?php
/**
 * Test pagina Scadenze con sessione simulata
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Avvia sessione
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simula login admin
$_SESSION['user_id'] = 'ucwurog3xr8tf';
$_SESSION['user_name'] = 'Lorenzo';
$_SESSION['last_activity'] = time();

// Ora includi la pagina scadenze ma cattura eventuali errori
ob_start();

try {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/includes/functions.php';
    
    // Verifica autenticazione
    if (!isLoggedIn()) {
        echo "ERRORE: isLoggedIn() = false";
        exit;
    }
    
    echo "Autenticazione OK<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    
    // Test query scadenze
    $sql = "SELECT s.*, st.nome as tipologia_nome, st.colore as tipologia_colore,
                   c.ragione_sociale as cliente_nome, u.nome as user_nome
            FROM scadenze s
            LEFT JOIN scadenze_tipologie st ON s.tipologia_id = st.id
            LEFT JOIN clienti c ON s.cliente_id = c.id
            LEFT JOIN utenti u ON s.user_id = u.id
            WHERE 1=1
            ORDER BY s.data_scadenza ASC, s.id DESC
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    if ($stmt === false) {
        echo "ERRORE PREPARE: " . print_r($pdo->errorInfo(), true) . "<br>";
    } else {
        $stmt->execute();
        $scadenze = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Query OK: trovate " . count($scadenze) . " scadenze<br>";
    }
    
    // Test tipologie
    $stmt = $pdo->query("SELECT * FROM scadenze_tipologie ORDER BY nome ASC");
    $tipologie = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Tipologie: " . count($tipologie) . "<br>";
    
    echo "<hr><h3>La pagina scadenze.php dovrebbe funzionare. Output HTML:</h3>";
    
} catch (Throwable $e) {
    echo "ERRORE: " . $e->getMessage() . "<br>";
    echo "Trace: " . $e->getTraceAsString() . "<br>";
}

$output = ob_get_clean();
echo $output;
