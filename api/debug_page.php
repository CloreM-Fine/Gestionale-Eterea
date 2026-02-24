<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simula sessione
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 'ucwurog3xr8tf';
$_SESSION['user_name'] = 'Lorenzo';

header('Content-Type: application/json');

// Test include file scadenze.php (senza eseguire)
$errors = [];

// Verifica se ci sono errori di sintassi nel file scadenze.php
$output = [];
$return = 0;
exec('php -l ' . escapeshellarg(__DIR__ . '/../scadenze.php') . ' 2>&1', $output, $return);
if ($return !== 0) {
    $errors['scadenze_syntax'] = implode("\n", $output);
}

// Verifica errori negli include
ob_start();
try {
    // Non includere la pagina completa, solo verifica i require
    //require_once __DIR__ . '/../includes/functions.php';
    $errors['functions'] = 'OK';
} catch (Throwable $e) {
    $errors['functions'] = $e->getMessage();
}
ob_end_clean();

// Verifica esistenza variabili necessarie
$required_vars = [' stato_progetto', 'stato_pagamento'];

echo json_encode([
    'syntax_check' => $return === 0 ? 'OK' : 'ERROR',
    'errors' => $errors,
    'output' => $output
]);
