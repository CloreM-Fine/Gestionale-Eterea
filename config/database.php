<?php
/**
 * Configurazione Database
 * Carica le variabili d'ambiente da .env
 */

// Carica il loader delle variabili d'ambiente
require_once __DIR__ . '/../includes/env_loader.php';

// Carica le variabili d'ambiente
loadEnv(__DIR__ . '/../.env');

// Recupera le credenziali dalle variabili d'ambiente
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'db4qhf5gnmj3lz';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

// Costruisci il DSN
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

// Opzioni PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // In produzione, logga l'errore ma non esporre dettagli
    error_log("Database connection error: " . $e->getMessage());
    
    // Rilancia l'eccezione per gestirla a livello superiore
    throw new PDOException("Errore connessione database. Riprova più tardi.");
}
