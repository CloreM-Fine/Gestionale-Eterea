<?php
/**
 * API Mail Semplificata - Test salvataggio
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_security.php';

header('Content-Type: application/json');

try {
    // Verifica autenticazione
    if (empty($_SESSION['user_id'])) {
        throw new Exception('Non autenticato');
    }
    
    $userId = $_SESSION['user_id'];
    
    // Verifica CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        throw new Exception('Token CSRF non valido');
    }
    
    // Verifica dati
    $email = $_POST['email'] ?? '';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email non valida');
    }
    
    global $pdo;
    
    // Genera ID
    $accountId = 'mail' . substr(md5(uniqid()), 0, 10);
    
    // Inserisci account (senza cifratura password per test)
    $stmt = $pdo->prepare("INSERT INTO mail_accounts (
        id, utente_id, email, nome_visualizzato,
        imap_server, imap_port, imap_ssl, imap_username, imap_password,
        smtp_server, smtp_port, smtp_ssl, smtp_username, smtp_password,
        is_default, attivo
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    
    $stmt->execute([
        $accountId,
        $userId,
        $email,
        $_POST['nome_visualizzato'] ?? '',
        $_POST['imap_server'] ?? '',
        intval($_POST['imap_port'] ?? 993),
        isset($_POST['imap_ssl']) ? 1 : 0,
        $_POST['imap_username'] ?? $email,
        $_POST['imap_password'] ?? '', // Non cifrata per test
        $_POST['smtp_server'] ?? '',
        intval($_POST['smtp_port'] ?? 587),
        isset($_POST['smtp_ssl']) ? 1 : 0,
        $_POST['imap_username'] ?? $email,
        $_POST['imap_password'] ?? '', // Non cifrata per test
        1
    ]);
    
    echo json_encode(['success' => true, 'id' => $accountId, 'message' => 'Account salvato']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
