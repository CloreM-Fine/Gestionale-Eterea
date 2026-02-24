<?php
/**
 * Debug Cookie Config
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$info = [
    'php_version' => PHP_VERSION,
    'session_cookie_params' => session_get_cookie_params(),
    'ini_settings' => [
        'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
        'session.cookie_path' => ini_get('session.cookie_path'),
        'session.cookie_domain' => ini_get('session.cookie_domain'),
        'session.cookie_secure' => ini_get('session.cookie_secure'),
        'session.cookie_httponly' => ini_get('session.cookie_httponly'),
        'session.cookie_samesite' => ini_get('session.cookie_samesite'),
        'session.use_cookies' => ini_get('session.use_cookies'),
        'session.use_only_cookies' => ini_get('session.use_only_cookies'),
    ]
];

// Prova a settare un cookie di test
setcookie('test_cookie', 'value', [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);
$info['test_cookie_set'] = true;

echo json_encode($info, JSON_PRETTY_PRINT);
