<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "Step 1: Prima di includes\n";
try {
    require_once __DIR__ . '/../includes/functions.php';
    echo "Step 2: functions.php OK\n";
} catch (Throwable $e) {
    echo "ERROR in functions.php: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit;
}
try {
    require_once __DIR__ . '/../includes/functions_security.php';
    echo "Step 3: functions_security.php OK\n";
} catch (Throwable $e) {
    echo "ERROR in functions_security.php: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit;
}
try {
    require_once __DIR__ . '/../includes/auth.php';
    echo "Step 4: auth.php OK\n";
} catch (Throwable $e) {
    echo "ERROR in auth.php: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit;
}
echo "All OK!\n";
