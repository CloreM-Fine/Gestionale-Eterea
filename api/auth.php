<?php
/**
 * Eterea Gestionale
 * API Autenticazione
 */

// Abilita error reporting per debug
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Header JSON sempre
header('Content-Type: application/json');

// Configurazione sessione PRIMA di qualsiasi output
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
ini_set('session.cookie_secure', $isHttps ? 1 : 0);
ini_set('session.cookie_lifetime', 2592000); // 30 giorni
ini_set('session.gc_maxlifetime', 2592000);

try {
    require_once __DIR__ . '/../includes/functions.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore caricamento: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            handleLogin();
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        case 'check':
            checkSession();
            break;
            
        default:
            jsonResponse(false, null, 'Azione non valida');
    }
} catch (Throwable $e) {
    error_log("Auth API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore server: ' . $e->getMessage()]);
}

/**
 * Gestisce il login con rate limiting e sicurezza avanzata
 */
function handleLogin(): void {
    global $pdo;
    
    // Verifica metodo
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, null, 'Metodo non consentito');
    }
    
    // Rate limiting
    if (isIpBlocked()) {
        securityLog('Login blocked - IP blocked', ['ip' => $_SERVER['REMOTE_ADDR']]);
        jsonResponse(false, null, 'Accesso temporaneamente bloccato. Riprova più tardi.');
    }
    
    if (!checkRateLimit('login', MAX_LOGIN_ATTEMPTS, LOGIN_LOCKOUT_MINUTES)) {
        blockIp($_SERVER['REMOTE_ADDR'] ?? 'unknown', LOGIN_LOCKOUT_MINUTES);
        securityLog('Login blocked - Rate limit exceeded', ['ip' => $_SERVER['REMOTE_ADDR']]);
        jsonResponse(false, null, 'Troppi tentativi falliti. Accesso bloccato per ' . LOGIN_LOCKOUT_MINUTES . ' minuti.');
    }
    
    // Recupera dati
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';
    
    // Validazione CSRF
    if (!verifyCsrfTokenSecure($csrfToken)) {
        securityLog('Login failed - Invalid CSRF', ['ip' => $_SERVER['REMOTE_ADDR']]);
        jsonResponse(false, null, 'Token di sicurezza non valido. Ricarica la pagina.');
    }
    
    // Validazione input
    $username = validateInput($username, 'string', ['max_length' => 100]);
    if (!$username || empty($password)) {
        jsonResponse(false, null, 'Inserire username e password validi');
    }
    
    // Cerca utente per nome
    try {
        $stmt = $pdo->prepare("SELECT id, nome, password, colore, avatar, is_active FROM utenti WHERE nome = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Prova a cercare per ID (per compatibilità)
            $stmt = $pdo->prepare("SELECT id, nome, password, colore, avatar, is_active FROM utenti WHERE id = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
        }
        
        if (!$user) {
            jsonResponse(false, null, 'Credenziali non valide');
        }
        
        if (!$user['is_active']) {
            jsonResponse(false, null, 'Account disattivato');
        }
        
        // Verifica password
        $passwordValid = false;
        $needsRehash = false;
        
        // Verifica se la password è già hashata
        if (password_verify($password, $user['password'])) {
            $passwordValid = true;
        } elseif ($password === $user['password']) {
            // Password in chiaro (primo accesso)
            $passwordValid = true;
            $needsRehash = true;
        }
        
        if (!$passwordValid) {
            jsonResponse(false, null, 'Credenziali non valide');
        }
        
        // Se password in chiaro, converti in hash
        if ($needsRehash) {
            $newHash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE utenti SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $user['id']]);
        }
        
        // Avvia sessione
        session_start();
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nome'];
        $_SESSION['user_color'] = $user['colore'];
        $_SESSION['user_avatar'] = $user['avatar'] ?? null;
        $_SESSION['last_activity'] = time();
        
        error_log("Login - User avatar from DB: " . ($user['avatar'] ?? 'NULL'));
        
        // Aggiorna ultimo login
        $stmt = $pdo->prepare("UPDATE utenti SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Pulisci timeline scaduta
        pulisciTimeline();
        
        // Log
        logTimeline($user['id'], 'login', 'utente', $user['id'], 'Login effettuato');
        
        jsonResponse(true, [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'colore' => $user['colore'],
            'redirect' => $_SESSION['redirect_after_login'] ?? 'dashboard.php'
        ], 'Login effettuato con successo');
        
    } catch (PDOException $e) {
        error_log("Errore login: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante il login');
    }
}

/**
 * Gestisce il logout
 */
function handleLogout(): void {
    session_start();
    
    if (isset($_SESSION['user_id'])) {
        logTimeline($_SESSION['user_id'], 'logout', 'utente', $_SESSION['user_id'], 'Logout effettuato');
    }
    
    // Distruggi sessione
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    
    session_destroy();
    
    // Redirect alla pagina di login
    header('Location: ../index.php');
    exit;
}

/**
 * Verifica stato sessione
 */
function checkSession(): void {
    session_start();
    
    if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
        // Verifica timeout
        if (time() - $_SESSION['last_activity'] <= 86400) {
            jsonResponse(true, [
                'id' => $_SESSION['user_id'],
                'nome' => $_SESSION['user_name'] ?? 'Utente',
                'colore' => $_SESSION['user_color'] ?? '#3B82F6',
                'avatar' => $_SESSION['user_avatar'] ?? null
            ]);
        }
    }
    
    jsonResponse(false, null, 'Sessione non valida');
}
