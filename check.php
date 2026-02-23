<?php
// Script di diagnostica
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnostica Server</h2>";

// Verifica PHP
echo "<p>✅ PHP Version: " . phpversion() . "</p>";

// Verifica PDO MySQL
if (extension_loaded('pdo_mysql')) {
    echo "<p>✅ PDO MySQL installato</p>";
} else {
    echo "<p>❌ PDO MySQL NON installato</p>";
}

// Verifica file config
if (file_exists(__DIR__ . '/includes/config.php')) {
    echo "<p>✅ includes/config.php esiste</p>";
} else {
    echo "<p>❌ includes/config.php MANCANTE</p>";
}

// Verifica file openai.config
if (file_exists(__DIR__ . '/config/openai.config.php')) {
    echo "<p>✅ config/openai.config.php esiste</p>";
} else {
    echo "<p>⚠️ config/openai.config.php mancante (opzionale)</p>";
}

// Test connessione DB
try {
    require_once __DIR__ . '/includes/config.php';
    echo "<p>✅ Config caricata</p>";
    
    if (isset($pdo)) {
        $pdo->query("SELECT 1");
        echo "<p>✅ Connessione database OK</p>";
    } else {
        echo "<p>❌ Variabile PDO non definita</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Errore: " . $e->getMessage() . "</p>";
}

echo "<hr><p><a href='index.php'>Vai alla login</a></p>";
