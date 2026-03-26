<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_security.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'method' => $_SERVER['REQUEST_METHOD'],
    'post' => $_POST,
    'files' => $_FILES,
    'csrf_valid' => verifyCsrfToken($_POST['csrf_token'] ?? '')
]);
