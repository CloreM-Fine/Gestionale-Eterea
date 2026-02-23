<?php
/**
 * Eterea Gestionale
 * Funzioni di Sicurezza Avanzate
 */

// =====================================================
// RATE LIMITING
// =====================================================

/**
 * Verifica rate limiting per azione specifica
 * Usa file-based locking per compatibilità con hosting condiviso
 * NOTA: Configurazione permissiva per evitare blocchi fastidiosi
 * 
 * @param string $action Nome dell'azione (es: 'login', 'api_call')
 * @param int $maxAttempts Numero massimo di tentativi
 * @param int $windowMinutes Finestra temporale in minuti
 * @return bool True se consentito, false se limitato
 */
function checkRateLimit(string $action, int $maxAttempts = 20, int $windowMinutes = 5): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = md5($ip . '_' . $action);
    $lockDir = sys_get_temp_dir() . '/eterea_ratelimit/';
    
    if (!is_dir($lockDir)) {
        mkdir($lockDir, 0750, true);
    }
    
    $lockFile = $lockDir . $key . '.json';
    $now = time();
    $window = $windowMinutes * 60;
    
    $attempts = [];
    if (file_exists($lockFile)) {
        $data = json_decode(file_get_contents($lockFile), true);
        if ($data && isset($data['attempts'])) {
            // Filtra solo tentativi nella finestra temporale
            $attempts = array_filter($data['attempts'], function($time) use ($now, $window) {
                return ($now - $time) < $window;
            });
        }
    }
    
    if (count($attempts) >= $maxAttempts) {
        // Log tentativo bloccato
        securityLog('Rate limit exceeded', ['action' => $action, 'ip' => $ip]);
        return false;
    }
    
    // Aggiungi tentativo corrente
    $attempts[] = $now;
    file_put_contents($lockFile, json_encode(['attempts' => array_values($attempts)]), LOCK_EX);
    
    return true;
}

/**
 * Blocca un IP temporaneamente dopo troppi tentativi falliti
 * NOTA: Blocco breve (5 min) per evitare frustrazione utenti
 * 
 * @param string $ip Indirizzo IP
 * @param int $blockMinutes Durata del blocco in minuti
 */
function blockIp(string $ip, int $blockMinutes = 5): void {
    $blockDir = sys_get_temp_dir() . '/eterea_blocks/';
    if (!is_dir($blockDir)) {
        mkdir($blockDir, 0750, true);
    }
    
    $blockFile = $blockDir . md5($ip) . '.block';
    $expires = time() + ($blockMinutes * 60);
    file_put_contents($blockFile, $expires, LOCK_EX);
    
    securityLog('IP blocked', ['ip' => $ip, 'expires' => date('Y-m-d H:i:s', $expires)]);
}

/**
 * Verifica se un IP è bloccato
 * 
 * @param string $ip Indirizzo IP
 * @return bool True se bloccato
 */
function isIpBlocked(string $ip = ''): bool {
    if (empty($ip)) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $blockDir = sys_get_temp_dir() . '/eterea_blocks/';
    $blockFile = $blockDir . md5($ip) . '.block';
    
    if (!file_exists($blockFile)) {
        return false;
    }
    
    $expires = (int)file_get_contents($blockFile);
    
    if (time() > $expires) {
        // Blocco scaduto, rimuovi file
        @unlink($blockFile);
        return false;
    }
    
    return true;
}

/**
 * Pulisce periodicamente file di rate limiting vecchi
 */
function cleanRateLimitFiles(): void {
    $lockDir = sys_get_temp_dir() . '/eterea_ratelimit/';
    if (!is_dir($lockDir)) return;
    
    $files = glob($lockDir . '*.json');
    $now = time();
    
    foreach ($files as $file) {
        if ($now - filemtime($file) > 3600) { // Più vecchi di 1 ora
            @unlink($file);
        }
    }
    
    // Pulisci anche blocchi IP scaduti
    $blockDir = sys_get_temp_dir() . '/eterea_blocks/';
    if (!is_dir($blockDir)) return;
    
    $files = glob($blockDir . '*.block');
    foreach ($files as $file) {
        $expires = (int)file_get_contents($file);
        if ($now > $expires) {
            @unlink($file);
        }
    }
}

// =====================================================
// CSRF PROTECTION AVANZATA
// =====================================================

/**
 * Genera token CSRF semplice (senza scadenza per robustezza)
 * 
 * @return string Token CSRF
 */
function generateCsrfTokenSecure(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 * 
 * @param string $token Token da verificare
 * @param bool $regenerate Se rigenerare il token dopo verifica
 * @return bool
 */
function verifyCsrfTokenSecure(string $token, bool $regenerate = false): bool {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Verifica corrispondenza
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    
    if ($regenerate) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return true;
}

/**
 * Middleware CSRF per API - da chiamare all'inizio di ogni API POST
 * 
 * @return void
 */
function requireCsrfToken(): void {
    // Per API JSON, il token può essere nell'header X-CSRF-Token
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    
    if (empty($token) || !verifyCsrfTokenSecure($token)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
        exit;
    }
}

// =====================================================
// UPLOAD SECURITY
// =====================================================

/**
 * Lista estensioni pericolose da bloccare
 */
const DANGEROUS_EXTENSIONS = [
    'php', 'php3', 'php4', 'php5', 'phtml', 'phar',
    'exe', 'bat', 'cmd', 'sh', 'bash', 'zsh',
    'js', 'jsp', 'jspx', 'asp', 'aspx', 'cfm',
    'py', 'pl', 'cgi', 'rb', 'dll', 'so',
    'htaccess', 'htpasswd', 'ini', 'log'
];

/**
 * Verifica se un file è potenzialmente pericoloso
 * 
 * @param string $filename Nome del file
 * @return bool True se sicuro, false se pericoloso
 */
function isSafeFilename(string $filename): bool {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($extension, DANGEROUS_EXTENSIONS)) {
        return false;
    }
    
    // Controlla doppie estensioni (es: file.php.jpg)
    if (substr_count($filename, '.') > 1) {
        $parts = explode('.', $filename);
        foreach ($parts as $part) {
            if (in_array(strtolower($part), DANGEROUS_EXTENSIONS)) {
                return false;
            }
        }
    }
    
    // Controlla path traversal
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        return false;
    }
    
    return true;
}

/**
 * Upload file hardenizzato
 * 
 * @param array $file Array $_FILES
 * @param string $destinationDir Directory di destinazione
 * @param array $allowedTypes Tipi MIME consentiti
 * @param int $maxSize Dimensione massima
 * @param bool $randomizeName Se rinominare con hash random
 * @return array|false
 */
function uploadFileSecure(array $file, string $destinationDir, array $allowedTypes, int $maxSize, bool $randomizeName = true): array|false {
    // Verifica errori
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("Upload error code: " . $file['error']);
        return false;
    }
    
    // Verifica dimensione
    if ($file['size'] > $maxSize) {
        error_log("File size exceeds limit: " . $file['size']);
        return false;
    }
    
    // Verifica sicurezza nome file
    if (!isSafeFilename($file['name'])) {
        error_log("Unsafe filename detected: " . $file['name']);
        securityLog('Unsafe upload attempt', ['filename' => $file['name']]);
        return false;
    }
    
    // Verifica tipo MIME reale (non solo quello inviato dal browser)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        error_log("MIME type not allowed: " . $mimeType);
        return false;
    }
    
    // Verifica estensione vs MIME type (solo warning, non blocca)
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $expectedMime = getMimeFromExtension($extension);
    
    if ($expectedMime && $mimeType !== $expectedMime) {
        error_log("MIME/Extension mismatch: {$mimeType} vs {$expectedMime} for {$file['name']}");
        // Non blocchiamo, solo logghiamo il warning
    }
    
    // Crea directory se non esiste
    $fullPath = UPLOAD_PATH . $destinationDir;
    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0750, true);
    }
    
    // Genera nome file sicuro
    if ($randomizeName) {
        // Nome completamente random, nessuna estensione visibile
        $hash = bin2hex(random_bytes(16));
        $newFilename = $hash;
        $destination = $fullPath . '/' . $newFilename;
    } else {
        // Nome sanitizzato ma mantenuto
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
        $hash = bin2hex(random_bytes(8));
        $newFilename = $hash . '_' . $safeName;
        $destination = $fullPath . '/' . $newFilename;
    }
    
    // Verifica che il file temporaneo sia un upload valido
    if (!is_uploaded_file($file['tmp_name'])) {
        error_log("Possible upload attack: not a valid uploaded file");
        return false;
    }
    
    // Sposta file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Imposta permessi restrittivi
        chmod($destination, 0640);
        
        return [
            'path' => $destinationDir . '/' . $newFilename,
            'filename' => $file['name'],
            'size' => $file['size'],
            'mime_type' => $mimeType
        ];
    }
    
    return false;
}

/**
 * Ottiene il MIME type atteso da un'estensione
 * 
 * @param string $extension Estensione file
 * @return string|null MIME type atteso o null
 */
function getMimeFromExtension(string $extension): ?string {
    $map = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'zip' => 'application/zip',
    ];
    
    return $map[strtolower($extension)] ?? null;
}

// =====================================================
// LOGGING SICURO
// =====================================================

/**
 * Log sicuro che rimuove dati sensibili
 * 
 * @param string $event Tipo evento
 * @param array $context Dati contestuali (verranno sanitizzati)
 */
function securityLog(string $event, array $context = []): void {
    // Campi sensibili da mascherare
    $sensitiveFields = ['password', 'pwd', 'token', 'csrf_token', 'secret', 'key', 'credit_card', 'ssn'];
    
    $sanitizedContext = [];
    foreach ($context as $key => $value) {
        $lowerKey = strtolower($key);
        $isSensitive = false;
        
        foreach ($sensitiveFields as $sensitive) {
            if (strpos($lowerKey, $sensitive) !== false) {
                $isSensitive = true;
                break;
            }
        }
        
        if ($isSensitive) {
            $sanitizedContext[$key] = '[REDACTED]';
        } elseif (is_string($value) && strlen($value) > 100) {
            // Trunca valori lunghi
            $sanitizedContext[$key] = substr($value, 0, 100) . '...[truncated]';
        } else {
            $sanitizedContext[$key] = $value;
        }
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s.u'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200),
        'user_id' => $_SESSION['user_id'] ?? null,
        'context' => $sanitizedContext
    ];
    
    // Log su file separato per sicurezza
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0750, true);
    }
    
    $logFile = $logDir . '/security-' . date('Y-m-d') . '.log';
    $logLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES) . "\n";
    
    error_log($logLine, 3, $logFile);
}

/**
 * Sanitizza dati per logging
 * 
 * @param mixed $data
 * @return mixed
 */
function sanitizeForLog($data) {
    if (is_string($data)) {
        // Rimuovi caratteri di controllo
        $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
        // Trunca stringhe lunghe
        if (strlen($data) > 500) {
            $data = substr($data, 0, 500) . '...[truncated]';
        }
        return $data;
    }
    if (is_array($data)) {
        $result = [];
        foreach ($data as $k => $v) {
            $result[$k] = sanitizeForLog($v);
        }
        return $result;
    }
    return $data;
}

// =====================================================
// INPUT VALIDATION
// =====================================================

/**
 * Validazione input avanzata
 * 
 * @param mixed $input Input da validare
 * @param string $type Tipo atteso (string, int, float, email, url, uuid, alphanum)
 * @param array $options Opzioni aggiuntive
 * @return mixed Input sanitizzato o false se invalido
 */
function validateInput($input, string $type = 'string', array $options = []) {
    switch ($type) {
        case 'string':
            $input = trim((string)$input);
            $maxLen = $options['max_length'] ?? 1000;
            if (strlen($input) > $maxLen) {
                return false;
            }
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            
        case 'int':
            if (!filter_var($input, FILTER_VALIDATE_INT)) {
                return false;
            }
            $min = $options['min'] ?? PHP_INT_MIN;
            $max = $options['max'] ?? PHP_INT_MAX;
            $val = (int)$input;
            return ($val >= $min && $val <= $max) ? $val : false;
            
        case 'float':
            if (!filter_var($input, FILTER_VALIDATE_FLOAT)) {
                return false;
            }
            $min = $options['min'] ?? PHP_FLOAT_MIN;
            $max = $options['max'] ?? PHP_FLOAT_MAX;
            $val = (float)$input;
            return ($val >= $min && $val <= $max) ? $val : false;
            
        case 'email':
            $email = filter_var($input, FILTER_VALIDATE_EMAIL);
            return $email !== false ? strtolower($email) : false;
            
        case 'url':
            $url = filter_var($input, FILTER_VALIDATE_URL);
            return $url !== false ? $url : false;
            
        case 'uuid':
            if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $input)) {
                return false;
            }
            return strtolower($input);
            
        case 'alphanum':
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $input)) {
                return false;
            }
            return $input;
            
        case 'id':
            // Per ID del sistema (es: ucwurog3xr8tf)
            if (!preg_match('/^[a-z0-9]+$/i', $input)) {
                return false;
            }
            return $input;
            
        default:
            return false;
    }
}

/**
 * Verifica che tutti i campi richiesti siano presenti
 * 
 * @param array $data Array dati
 * @param array $required Campi richiesti
 * @return bool
 */
function validateRequired(array $data, array $required): bool {
    foreach ($required as $field) {
        if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
            return false;
        }
    }
    return true;
}

// Esegui pulizia file vecchi occasionalmente
if (rand(1, 100) === 1) {
    cleanRateLimitFiles();
}
