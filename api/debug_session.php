<?php
/**
 * Debug Session - Verifica cookie e sessione
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$info = [
    'cookies_ricevuti' => $_COOKIE,
    'session_name' => session_name(),
    'session_id' => session_id(),
    'session_status' => session_status(),
    'headers_sent' => headers_sent(),
    'server' => [
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'none',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'none',
        'HTTPS' => $_SERVER['HTTPS'] ?? 'off',
    ]
];

// Prova ad avviare sessione
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $info['session_after_start'] = [
        'session_id' => session_id(),
        'session_data' => $_SESSION
    ];
}

echo json_encode($info, JSON_PRETTY_PRINT);
