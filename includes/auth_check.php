<?php
/**
 * Eterea Gestionale
 * Verifica autenticazione utente
 * 
 * Da includere in tutte le pagine protette
 */

// Configurazione sessione sicura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Detect HTTPS (supporta anche reverse proxy come SiteGround)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

// Durata cookie: 30 giorni (2592000 secondi)
ini_set('session.cookie_lifetime', 2592000);
ini_set('session.gc_maxlifetime', 2592000);

session_start();

// Controlla se è una richiesta API/AJAX
$isApiRequest = strpos($_SERVER['REQUEST_URI'], '/api/') !== false || 
                (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

// Verifica login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    if ($isApiRequest) {
        // Per API, restituisci JSON errore invece di redirect
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sessione non valida. Effettua il login.']);
        exit;
    }
    // Salva URL richiesto per redirect dopo login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: index.php');
    exit;
}

// Verifica timeout sessione (24 ore)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 86400)) {
    // Sessione scaduta
    session_unset();
    session_destroy();
    if ($isApiRequest) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sessione scaduta. Effettua il login.']);
        exit;
    }
    header('Location: index.php?error=session_expired');
    exit;
}

// Aggiorna timestamp ultima attività
$_SESSION['last_activity'] = time();

// Dati utente corrente
$currentUser = [
    'id' => $_SESSION['user_id'],
    'nome' => $_SESSION['user_name'] ?? 'Utente',
    'colore' => $_SESSION['user_color'] ?? '#3B82F6',
    'avatar' => $_SESSION['user_avatar'] ?? null
];

error_log("Auth check - Session avatar: " . ($_SESSION['user_avatar'] ?? 'NULL') . " - currentUser avatar: " . ($currentUser['avatar'] ?? 'NULL'));

// Header sicurezza (CSP disabilitato per compatibilità CDN)
// header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob: https:; connect-src 'self';");
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
