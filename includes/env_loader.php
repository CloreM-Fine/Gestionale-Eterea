<?php
/**
 * Eterea Gestionale
 * Caricatore Environment - Semplice e leggero
 * 
 * Non richiede librerie esterne, compatibile con PHP 7.4+
 */

function loadEnv(string $path): void {
    if (!file_exists($path)) {
        error_log("ENV file not found: " . $path);
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        error_log("Cannot read ENV file: " . $path);
        return;
    }
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip commenti e righe vuote
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Parse VAR=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Rimuovi virgolette se presenti
            if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }
            
            // Imposta solo se non già definita
            if (!defined($key) && !isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

function env(string $key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key);
    
    if ($value === false || $value === null) {
        return $default;
    }
    
    // Conversione tipi
    $value = trim($value);
    
    if (strtolower($value) === 'true') return true;
    if (strtolower($value) === 'false') return false;
    if (strtolower($value) === 'null') return null;
    if (is_numeric($value)) {
        return strpos($value, '.') !== false ? (float)$value : (int)$value;
    }
    
    return $value;
}

// Carica il file .env
define('ENV_LOADED', true);
