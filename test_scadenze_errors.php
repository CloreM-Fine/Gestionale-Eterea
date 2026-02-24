<?php
/**
 * Test errore pagina Scadenze
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simula sessione
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 'ucwurog3xr8tf';
$_SESSION['user_name'] = 'Lorenzo';

echo "<h2>Test Scadenze - Error Check</h2>";
echo "<pre>";

// Cattura output
ob_start();
try {
    require_once __DIR__ . '/scadenze.php';
    $content = ob_get_clean();
    
    // Cerca errori PHP nell'output
    if (strpos($content, 'Warning:') !== false || 
        strpos($content, 'Error:') !== false ||
        strpos($content, 'Notice:') !== false) {
        echo "TROVATO ERRORE PHP:\n";
        echo strip_tags($content);
    } else {
        echo "Nessun errore PHP rilevato nell'output\n";
        echo "Lunghezza HTML: " . strlen($content) . " bytes\n";
        
        // Verifica se c'è il tag script
        if (strpos($content, '<script>') !== false) {
            echo "Tag <script> presente: SI\n";
        } else {
            echo "Tag <script> presente: NO\n";
        }
        
        // Verifica se c'è console.log
        if (strpos($content, 'console.log') !== false) {
            echo "console.log presente: SI\n";
        } else {
            echo "console.log presente: NO\n";
        }
    }
} catch (Throwable $e) {
    ob_end_clean();
    echo "ECCEZIONE: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "</pre>";
