<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Stessa configurazione delle API
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

echo json_encode([
    'cookies' => $_COOKIE,
    'session' => $_SESSION,
    'headers' => getallheaders(),
    'isLoggedIn' => isset($_SESSION['user_id']),
], JSON_PRETTY_PRINT);
