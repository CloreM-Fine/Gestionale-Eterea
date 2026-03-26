<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "Test API Mail - Start\n";
try {
    require_once __DIR__ . '/../includes/functions.php';
    echo "functions.php OK\n";
    require_once __DIR__ . '/../includes/functions_security.php';
    echo "functions_security.php OK\n";
    require_once __DIR__ . '/../includes/auth.php';
    echo "auth.php OK\n";
    echo "All includes OK\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
