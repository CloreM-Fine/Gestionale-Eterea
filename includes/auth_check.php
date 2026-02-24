<?php
/**
 * Eterea Gestionale
 * Verifica autenticazione utente - Security Hardened
 * 
 * Da includere in tutte le pagine protette
 */

// Configurazione sessione sicura
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// Detect HTTPS (supporta anche reverse proxy come SiteGround)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

// Durata cookie: da config
$cookieLifetime = defined('COOKIE_LIFETIME') ? COOKIE_LIFETIME : 2592000;
ini_set('session.cookie_lifetime', $cookieLifetime);
ini_set('session.gc_maxlifetime', $cookieLifetime);

// SameSite Lax per permettere chiamate API fetch
ini_set('session.cookie_samesite', 'Lax');

session_start();

// Security: rigenera session ID periodicamente per prevenire fixation
if (isset($_SESSION['last_regeneration'])) {
    $regenInterval = 1800; // 30 minuti
    if (time() - $_SESSION['last_regeneration'] > $regenInterval) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
} else {
    $_SESSION['last_regeneration'] = time();
}

// Security: binding a IP e User Agent (opzionale, può creare problemi con mobile)
if (isset($_SESSION['ip_address'])) {
    $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Verifica solo se l'IP è cambiato drasticamente (subnet diversa)
    // o se lo User Agent è completamente diverso
    if ($_SESSION['ip_address'] !== $currentIp || $_SESSION['user_agent'] !== $currentUa) {
        // Log potenziale hijacking
        error_log("Session security warning: IP/UA mismatch for user " . ($_SESSION['user_id'] ?? 'unknown'));
    }
} else {
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}

// Controlla se è una richiesta API/AJAX
$isApiRequest = strpos($_SERVER['REQUEST_URI'], '/api/') !== false || 
                (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');

// Verifica login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    if ($isApiRequest) {
        // Per API, restituisci JSON errore invece di redirect
        header('Content-Type: application/json');
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['success' => false, 'message' => 'Sessione non valida. Effettua il login.']);
        exit;
    }
    // Salva URL richiesto per redirect dopo login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: index.php');
    exit;
}

// Verifica timeout sessione (da config)
$sessionTimeout = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 7200;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
    // Sessione scaduta
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    session_destroy();
    
    if ($isApiRequest) {
        header('Content-Type: application/json');
        header('HTTP/1.1 401 Unauthorized');
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

// Header sicurezza
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// HSTS solo in HTTPS
if ($isHttps) {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// Rimuovi header che espongono informazioni
header_remove("X-Powered-By");
header_remove("Server");
