<?php
/**
 * ETEREA STUDIO - Gestionale
 * API: Mail - Gestione email IMAP/SMTP
 * 
 * @copyright 2024-2025 Eterea Studio
 * @license MIT
 */

// Debug - log errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Fatal error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']]);
    }
});

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/functions_security.php';
require_once __DIR__ . '/../includes/auth.php';

// Polyfill per str_starts_with (PHP < 8.0)
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    listMessages();
                    break;
                case 'detail':
                    getMessageDetail();
                    break;
                case 'list_files':
                    listFiles();
                    break;
                case 'list_preventivi':
                    listPreventivi();
                    break;
                case 'sync':
                    syncEmails();
                    break;
                case 'download_attachment':
                    downloadAttachment();
                    break;
                default:
                    jsonResponse(false, null, 'Azione non valida');
            }
            break;
            
        case 'POST':
            // Verifica CSRF
            $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!verifyCsrfToken($csrfToken)) {
                jsonResponse(false, null, 'Token CSRF non valido');
            }
            
            switch ($action) {
                case 'save_account':
                    saveAccount();
                    break;
                case 'send_email':
                    sendEmail();
                    break;
                case 'save_draft':
                    saveDraft();
                    break;
                case 'associa_cliente':
                    associaCliente();
                    break;
                case 'rimuovi_associazione':
                    rimuoviAssociazione();
                    break;
                case 'toggle_important':
                    toggleImportant();
                    break;
                case 'move_trash':
                    moveToTrash();
                    break;
                case 'mark_all_read':
                    markAllRead();
                    break;
                case 'set_default':
                    setDefaultAccount();
                    break;
                case 'delete_account':
                    deleteAccount();
                    break;
                case 'test_connection':
                    testConnection();
                    break;
                default:
                    jsonResponse(false, null, 'Azione non valida');
            }
            break;
            
        case 'DELETE':
            deleteMessage();
            break;
            
        default:
            jsonResponse(false, null, 'Metodo non supportato');
    }
} catch (Exception $e) {
    error_log("Errore API Mail: " . $e->getMessage());
    jsonResponse(false, null, 'Errore interno del server');
}

/**
 * Salva o aggiorna un account email
 */
function saveAccount() {
    global $pdo;
    
    // Debug log
    error_log("saveAccount called with POST data: " . print_r($_POST, true));
    
    $accountId = $_POST['account_id'] ?? '';
    $email = sanitizeInput($_POST['email'] ?? '');
    $nomeVisualizzato = sanitizeInput($_POST['nome_visualizzato'] ?? '');
    $imapServer = sanitizeInput($_POST['imap_server'] ?? '');
    $imapPort = intval($_POST['imap_port'] ?? 993);
    $imapSsl = isset($_POST['imap_ssl']) ? 1 : 0;
    $smtpServer = sanitizeInput($_POST['smtp_server'] ?? '');
    $smtpPort = intval($_POST['smtp_port'] ?? 587);
    $smtpSsl = isset($_POST['smtp_ssl']) ? 1 : 0;
    $imapUsername = sanitizeInput($_POST['imap_username'] ?? $email);
    $imapPassword = $_POST['imap_password'] ?? '';
    
    // Validazione
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, null, 'Email non valida');
    }
    if (!$imapServer || !$smtpServer) {
        jsonResponse(false, null, 'Server IMAP e SMTP obbligatori');
    }
    
    // Cifra password (temporarily using base64 for testing)
    $encryptedPassword = '';
    if ($imapPassword) {
        $encryptedPassword = base64_encode($imapPassword);
    }
    
    try {
        if ($accountId) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE mail_accounts SET
                    email = ?,
                    nome_visualizzato = ?,
                    imap_server = ?,
                    imap_port = ?,
                    imap_ssl = ?,
                    smtp_server = ?,
                    smtp_port = ?,
                    smtp_ssl = ?,
                    imap_username = ?,
                    " . ($imapPassword ? "imap_password = ?," : "") . "
                    aggiornato_il = NOW()
                WHERE id = ? AND utente_id = ?
            ");
            
            $params = [
                $email, $nomeVisualizzato, $imapServer, $imapPort, $imapSsl,
                $smtpServer, $smtpPort, $smtpSsl, $imapUsername ?: $email
            ];
            if ($imapPassword) {
                $params[] = $encryptedPassword;
            }
            $params[] = $accountId;
            $params[] = currentUserId();
            
            $stmt->execute($params);
        } else {
            // Insert
            $accountId = generateEntityId('mailacc');
            
            // Se è il primo account, impostalo come default
            $stmt = $pdo->query("SELECT COUNT(*) FROM mail_accounts WHERE utente_id = '" . currentUserId() . "'");
            $isDefault = $stmt->fetchColumn() == 0 ? 1 : 0;
            
            $stmt = $pdo->prepare("
                INSERT INTO mail_accounts (
                    id, utente_id, email, nome_visualizzato,
                    imap_server, imap_port, imap_ssl, imap_username, imap_password,
                    smtp_server, smtp_port, smtp_ssl, smtp_username, smtp_password,
                    is_default
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $accountId, currentUserId(), $email, $nomeVisualizzato,
                $imapServer, $imapPort, $imapSsl, $imapUsername ?: $email, $encryptedPassword,
                $smtpServer, $smtpPort, $smtpSsl, $imapUsername ?: $email, $encryptedPassword,
                $isDefault
            ]);
        }
        
        jsonResponse(true, ['id' => $accountId], 'Account salvato con successo');
        
    } catch (Exception $e) {
        error_log("Errore salvataggio account: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante il salvataggio');
    }
}

/**
 * Invia una email via SMTP/PHP mail()
 */
function sendEmail() {
    global $pdo;
    
    $accountId = $_POST['account_id'] ?? '';
    $to = $_POST['to'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $body = $_POST['body'] ?? '';
    $replyTo = $_POST['reply_to'] ?? '';
    $clienteId = $_POST['cliente_id'] ?? '';
    $progettoId = $_POST['progetto_id'] ?? '';
    
    if (!$accountId || !$to || !$subject) {
        jsonResponse(false, null, 'Dati mancanti');
    }
    
    // Carica account
    $stmt = $pdo->prepare("SELECT * FROM mail_accounts WHERE id = ? AND utente_id = ? AND attivo = 1");
    $stmt->execute([$accountId, currentUserId()]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$account) {
        jsonResponse(false, null, 'Account non trovato');
    }
    
    // Decifra password
    $key = getenv('ENCRYPTION_KEY') ?: 'default_key_change_in_production';
    $key = str_pad(substr($key, 0, 32), 32, '0');
    $password = decryptPassword($account['smtp_password'], $key);
    
    $fromName = $account['nome_visualizzato'] ?: $account['email'];
    $htmlBody = nl2br(htmlspecialchars($body));
    
    // Prova invio SMTP diretto se configurato
    $smtpSuccess = false;
    if ($account['smtp_server'] && $account['smtp_port']) {
        $smtpSuccess = sendViaSmtp(
            $account['smtp_server'],
            $account['smtp_port'],
            $account['smtp_ssl'],
            $account['smtp_username'] ?: $account['email'],
            $password,
            $account['email'],
            $fromName,
            $to,
            $subject,
            $htmlBody
        );
    }
    
    // Se SMTP fallisce, prova con mail() nativo di PHP
    if (!$smtpSuccess) {
        $headers = "From: \"" . $fromName . "\" <" . $account['email'] . ">\r\n";
        $headers .= "Reply-To: " . $account['email'] . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        $smtpSuccess = mail($to, $subject, $htmlBody, $headers);
    }
    
    if ($smtpSuccess) {
        // Salva copia in "Inviate"
        $messageId = generateEntityId('msg');
        $stmt = $pdo->prepare("
            INSERT INTO mail_messages (
                id, account_id, cliente_id, progetto_id, cartella,
                destinatari, oggetto, corpo_text, corpo_html,
                data_invio, is_inviata, is_letta
            ) VALUES (?, ?, ?, ?, 'sent', ?, ?, ?, ?, NOW(), 1, 1)
        ");
        $stmt->execute([
            $messageId, $accountId, $clienteId, $progettoId,
            $to, $subject, $body, $htmlBody
        ]);
        
        // Se era una reply, marca come risposto
        if ($replyTo) {
            $pdo->prepare("UPDATE mail_messages SET parent_id = ? WHERE id = ?")
                ->execute([$replyTo, $messageId]);
        }
        
        jsonResponse(true, ['message_id' => $messageId], 'Email inviata con successo');
    } else {
        jsonResponse(false, null, 'Errore durante l\'invio. Verifica le impostazioni SMTP.');
    }
}

/**
 * Invia email via SMTP diretto
 */
function sendViaSmtp($host, $port, $ssl, $username, $password, $from, $fromName, $to, $subject, $body) {
    $timeout = 10;
    $errno = 0;
    $errstr = '';
    
    // Connessione
    $protocol = $ssl ? 'ssl://' : '';
    $socket = @fsockopen($protocol . $host, $port, $errno, $errstr, $timeout);
    
    if (!$socket) {
        error_log("SMTP Connection failed: $errstr ($errno)");
        return false;
    }
    
    stream_set_timeout($socket, $timeout);
    
    // Leggi greeting
    $response = fgets($socket, 515);
    if (!str_starts_with($response, '220')) {
        fclose($socket);
        return false;
    }
    
    // EHLO
    fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    while ($line = fgets($socket, 515)) {
        if (str_starts_with($line, '250 ')) break;
    }
    
    // STARTTLS se porta 587
    if ($port == 587 && !$ssl) {
        fputs($socket, "STARTTLS\r\n");
        $response = fgets($socket, 515);
        if (!str_starts_with($response, '220')) {
            fclose($socket);
            return false;
        }
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        
        // EHLO again dopo TLS
        fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
        while ($line = fgets($socket, 515)) {
            if (str_starts_with($line, '250 ')) break;
        }
    }
    
    // AUTH LOGIN
    fputs($socket, "AUTH LOGIN\r\n");
    $response = fgets($socket, 515);
    if (!str_starts_with($response, '334')) {
        fclose($socket);
        return false;
    }
    
    fputs($socket, base64_encode($username) . "\r\n");
    $response = fgets($socket, 515);
    if (!str_starts_with($response, '334')) {
        fclose($socket);
        return false;
    }
    
    fputs($socket, base64_encode($password) . "\r\n");
    $response = fgets($socket, 515);
    if (!str_starts_with($response, '235')) {
        fclose($socket);
        return false;
    }
    
    // MAIL FROM
    fputs($socket, "MAIL FROM:<$from>\r\n");
    $response = fgets($socket, 515);
    if (!str_starts_with($response, '250')) {
        fclose($socket);
        return false;
    }
    
    // RCPT TO
    fputs($socket, "RCPT TO:<$to>\r\n");
    $response = fgets($socket, 515);
    if (!str_starts_with($response, '250') && !str_starts_with($response, '251')) {
        fclose($socket);
        return false;
    }
    
    // DATA
    fputs($socket, "DATA\r\n");
    $response = fgets($socket, 515);
    if (!str_starts_with($response, '354')) {
        fclose($socket);
        return false;
    }
    
    // Headers e body
    $message = "From: \"" . $fromName . "\" <$from>\r\n";
    $message .= "To: $to\r\n";
    $message .= "Subject: $subject\r\n";
    $message .= "MIME-Version: 1.0\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "\r\n";
    $message .= $body;
    $message .= "\r\n.\r\n";
    
    fputs($socket, $message);
    $response = fgets($socket, 515);
    if (!str_starts_with($response, '250')) {
        fclose($socket);
        return false;
    }
    
    // QUIT
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    return true;
}

/**
 * Salva una bozza
 */
function saveDraft() {
    global $pdo;
    
    $accountId = $_POST['account_id'] ?? '';
    $to = $_POST['to'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $body = $_POST['body'] ?? '';
    $clienteId = $_POST['cliente_id'] ?? '';
    $progettoId = $_POST['progetto_id'] ?? '';
    
    if (!$accountId) {
        jsonResponse(false, null, 'Account non specificato');
    }
    
    try {
        $messageId = generateEntityId('msg');
        $stmt = $pdo->prepare("
            INSERT INTO mail_messages (
                id, account_id, cliente_id, progetto_id, cartella,
                destinatari, oggetto, corpo_text, is_bozza
            ) VALUES (?, ?, ?, ?, 'drafts', ?, ?, ?, 1)
        ");
        $stmt->execute([
            $messageId, $accountId, $clienteId, $progettoId,
            $to, $subject, $body
        ]);
        
        jsonResponse(true, ['message_id' => $messageId], 'Bozza salvata');
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore nel salvataggio');
    }
}

/**
 * Associa email a cliente
 */
function associaCliente() {
    global $pdo;
    
    $messageId = $_POST['message_id'] ?? '';
    $clienteId = $_POST['cliente_id'] ?? '';
    $progettoId = $_POST['progetto_id'] ?? '';
    
    if (!$messageId || !$clienteId) {
        jsonResponse(false, null, 'Dati mancanti');
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE mail_messages 
            SET cliente_id = ?, progetto_id = ? 
            WHERE id = ?
        ");
        $stmt->execute([$clienteId, $progettoId ?: null, $messageId]);
        
        jsonResponse(true, null, 'Associazione salvata');
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Rimuovi associazione cliente
 */
function rimuoviAssociazione() {
    global $pdo;
    
    $messageId = $_POST['message_id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            UPDATE mail_messages 
            SET cliente_id = NULL, progetto_id = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$messageId]);
        
        jsonResponse(true, null, 'Associazione rimossa');
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Toggle importante
 */
function toggleImportant() {
    global $pdo;
    
    $messageId = $_POST['message_id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            UPDATE mail_messages 
            SET is_importante = NOT is_importante 
            WHERE id = ?
        ");
        $stmt->execute([$messageId]);
        
        jsonResponse(true, null, 'Aggiornato');
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Sposta nel cestino
 */
function moveToTrash() {
    global $pdo;
    
    $messageId = $_POST['message_id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            UPDATE mail_messages 
            SET is_cestinata = 1, cartella = 'trash' 
            WHERE id = ?
        ");
        $stmt->execute([$messageId]);
        
        jsonResponse(true, null, 'Spostato nel cestino');
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Marca tutte come lette
 */
function markAllRead() {
    global $pdo;
    
    $accountId = $_POST['account_id'] ?? '';
    $folder = $_POST['folder'] ?? 'inbox';
    
    try {
        $where = "account_id = ?";
        if ($folder === 'inbox') {
            $where .= " AND cartella = 'inbox'";
        }
        
        $stmt = $pdo->prepare("UPDATE mail_messages SET is_letta = 1 WHERE $where");
        $stmt->execute([$accountId]);
        
        jsonResponse(true, null, 'Tutte marcate come lette');
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Lista messaggi
 */
function listMessages() {
    global $pdo;
    
    $accountId = $_GET['account_id'] ?? '';
    $folder = $_GET['folder'] ?? 'inbox';
    
    try {
        $where = "account_id = ? AND is_cestinata = 0";
        $params = [$accountId];
        
        switch ($folder) {
            case 'sent':
                $where .= " AND is_inviata = 1";
                break;
            case 'drafts':
                $where .= " AND is_bozza = 1";
                break;
            case 'important':
                $where .= " AND is_importante = 1";
                break;
            case 'trash':
                $where = "account_id = ? AND is_cestinata = 1";
                break;
            default:
                $where .= " AND is_inviata = 0 AND is_bozza = 0 AND is_spam = 0";
        }
        
        $stmt = $pdo->prepare("
            SELECT m.*, c.nome as cliente_nome, c.cognome as cliente_cognome
            FROM mail_messages m
            LEFT JOIN clienti c ON m.cliente_id = c.id
            WHERE $where
            ORDER BY COALESCE(m.data_ricezione, m.data_invio, m.creato_il) DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        jsonResponse(true, $messages);
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore nel caricamento');
    }
}

/**
 * Dettaglio messaggio
 */
function getMessageDetail() {
    global $pdo;
    
    $messageId = $_GET['message_id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, c.nome as cliente_nome, c.cognome as cliente_cognome, c.azienda as cliente_azienda,
                   p.nome as progetto_nome
            FROM mail_messages m
            LEFT JOIN clienti c ON m.cliente_id = c.id
            LEFT JOIN progetti p ON m.progetto_id = p.id
            WHERE m.id = ?
        ");
        $stmt->execute([$messageId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($message) {
            // Carica allegati
            $stmt = $pdo->prepare("SELECT * FROM mail_attachments WHERE message_id = ?");
            $stmt->execute([$messageId]);
            $message['allegati'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Marca come letta
            $pdo->prepare("UPDATE mail_messages SET is_letta = 1 WHERE id = ?")->execute([$messageId]);
        }
        
        jsonResponse(true, $message);
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Lista file disponibili
 */
function listFiles() {
    global $pdo;
    
    $tipo = $_GET['tipo'] ?? 'clienti';
    $files = [];
    
    try {
        if ($tipo === 'clienti') {
            // Cerca in uploads/clienti
            $basePath = __DIR__ . '/../assets/uploads/clienti/';
            if (is_dir($basePath)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $files[] = [
                            'name' => $file->getFilename(),
                            'path' => str_replace(__DIR__ . '/../', '', $file->getPathname()),
                            'size' => $file->getSize(),
                            'size_formatted' => formatBytes($file->getSize()),
                            'folder' => basename(dirname($file->getPathname()))
                        ];
                    }
                }
            }
        } else {
            // Cerca in uploads/progetti
            $basePath = __DIR__ . '/../assets/uploads/progetti/';
            if (is_dir($basePath)) {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $files[] = [
                            'name' => $file->getFilename(),
                            'path' => str_replace(__DIR__ . '/../', '', $file->getPathname()),
                            'size' => $file->getSize(),
                            'size_formatted' => formatBytes($file->getSize()),
                            'folder' => 'Progetto ' . basename(dirname($file->getPathname()))
                        ];
                    }
                }
            }
        }
        
        // Limita a 50 file più recenti
        usort($files, function($a, $b) {
            return filemtime(__DIR__ . '/../' . $b['path']) - filemtime(__DIR__ . '/../' . $a['path']);
        });
        $files = array_slice($files, 0, 50);
        
        jsonResponse(true, ['files' => $files]);
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Lista preventivi
 */
function listPreventivi() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT p.*, c.nome as cliente_nome, c.cognome as cliente_cognome
            FROM preventivi_salvati p
            JOIN clienti c ON p.cliente_id = c.id
            WHERE p.stato IN ('bozza', 'inviato')
            ORDER BY p.creato_il DESC
            LIMIT 50
        ");
        $preventivi = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        jsonResponse(true, ['preventivi' => $preventivi]);
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Sincronizza email da IMAP
 * Nota: Per hosting condiviso come SiteGround, IMAP potrebbe non essere disponibile
 * In tal caso, le email vengono inserite manualmente o via forwarding
 */
function syncEmails() {
    global $pdo;
    
    $accountId = $_GET['account_id'] ?? '';
    
    // Verifica se IMAP è disponibile
    if (!function_exists('imap_open')) {
        jsonResponse(false, null, 'IMAP non disponibile su questo server. Usa l\'inoltro automatico delle email.');
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM mail_accounts WHERE id = ? AND attivo = 1");
        $stmt->execute([$accountId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$account) {
            jsonResponse(false, null, 'Account non trovato');
        }
        
        // Decifra password
        $key = getenv('ENCRYPTION_KEY') ?: 'default_key_change_in_production';
        $key = str_pad(substr($key, 0, 32), 32, '0');
        $password = decryptPassword($account['imap_password'], $key);
        
        // Connessione IMAP
        $mailbox = '{' . $account['imap_server'] . ':' . $account['imap_port'] . '/imap' . ($account['imap_ssl'] ? '/ssl' : '') . '}INBOX';
        $imap = @imap_open($mailbox, $account['imap_username'], $password);
        
        if (!$imap) {
            jsonResponse(false, null, 'Connessione IMAP fallita: ' . imap_last_error());
        }
        
        // Ottieni email non lette
        $emails = imap_search($imap, 'UNSEEN');
        $imported = 0;
        
        if ($emails) {
            rsort($emails); // Più recenti prima
            $emails = array_slice($emails, 0, 50); // Max 50 alla volta
            
            foreach ($emails as $emailId) {
                $header = imap_headerinfo($imap, $emailId);
                $structure = imap_fetchstructure($imap, $emailId);
                
                // Estrai body
                $body = '';
                $bodyHtml = '';
                
                if (isset($structure->parts) && count($structure->parts)) {
                    foreach ($structure->parts as $partNum => $part) {
                        if ($part->subtype == 'PLAIN') {
                            $body = imap_fetchbody($imap, $emailId, $partNum + 1);
                            if ($part->encoding == 3) { // BASE64
                                $body = base64_decode($body);
                            } elseif ($part->encoding == 4) { // QUOTED-PRINTABLE
                                $body = quoted_printable_decode($body);
                            }
                        } elseif ($part->subtype == 'HTML') {
                            $bodyHtml = imap_fetchbody($imap, $emailId, $partNum + 1);
                            if ($part->encoding == 3) {
                                $bodyHtml = base64_decode($bodyHtml);
                            } elseif ($part->encoding == 4) {
                                $bodyHtml = quoted_printable_decode($bodyHtml);
                            }
                        }
                    }
                } else {
                    $body = imap_body($imap, $emailId);
                    if ($structure->encoding == 3) {
                        $body = base64_decode($body);
                    } elseif ($structure->encoding == 4) {
                        $body = quoted_printable_decode($body);
                    }
                }
                
                // Verifica se esiste già
                $messageId = $header->message_id ?? '';
                $stmt = $pdo->prepare("SELECT id FROM mail_messages WHERE message_id = ? OR (account_id = ? AND data_ricezione = ? AND oggetto = ?)");
                $stmt->execute([$messageId, $accountId, date('Y-m-d H:i:s', strtotime($header->date)), $header->subject ?? '']);
                if ($stmt->fetch()) {
                    continue; // Già importata
                }
                
                // Cerca associazione cliente
                $fromEmail = $header->from[0]->mailbox . '@' . $header->from[0]->host;
                $clienteId = null;
                $stmt = $pdo->prepare("SELECT id FROM clienti WHERE email = ? LIMIT 1");
                $stmt->execute([$fromEmail]);
                $cliente = $stmt->fetch();
                if ($cliente) {
                    $clienteId = $cliente['id'];
                }
                
                // Salva nel database
                $newId = generateEntityId('msg');
                $stmt = $pdo->prepare("
                    INSERT INTO mail_messages (
                        id, account_id, message_id, cliente_id,
                        cartella, mittente_email, mittente_nome, destinatari, oggetto,
                        corpo_text, corpo_html, data_ricezione, size_bytes
                    ) VALUES (?, ?, ?, ?, 'inbox', ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $newId, $accountId, $messageId, $clienteId,
                    $fromEmail,
                    $header->from[0]->personal ?? $header->from[0]->mailbox,
                    $header->toaddress ?? '',
                    $header->subject ?? '(nessun oggetto)',
                    $body,
                    $bodyHtml,
                    date('Y-m-d H:i:s', strtotime($header->date)),
                    $header->Size ?? 0
                ]);
                
                $imported++;
                
                // Marca come letta sul server
                imap_setflag_full($imap, $emailId, '\\Seen');
            }
        }
        
        imap_close($imap);
        
        // Aggiorna timestamp sincronizzazione
        $pdo->prepare("UPDATE mail_accounts SET ultima_sincronizzazione = NOW() WHERE id = ?")
            ->execute([$accountId]);
        
        jsonResponse(true, ['imported' => $imported], "Sincronizzazione completata. $imported email importate.");
        
    } catch (Exception $e) {
        error_log("Errore sincronizzazione: " . $e->getMessage());
        jsonResponse(false, null, 'Errore durante la sincronizzazione');
    }
}

/**
 * Download allegato
 */
function downloadAttachment() {
    global $pdo;
    
    $attachmentId = $_GET['id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM mail_attachments WHERE id = ?");
        $stmt->execute([$attachmentId]);
        $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$attachment || !file_exists(__DIR__ . '/../' . $attachment['file_path'])) {
            http_response_code(404);
            exit('File non trovato');
        }
        
        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Disposition: attachment; filename="' . $attachment['original_filename'] . '"');
        header('Content-Length: ' . $attachment['file_size']);
        
        readfile(__DIR__ . '/../' . $attachment['file_path']);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        exit('Errore');
    }
}

/**
 * Imposta account predefinito
 */
function setDefaultAccount() {
    global $pdo;
    
    $accountId = $_POST['account_id'] ?? '';
    
    try {
        // Rimuovi default da tutti gli altri
        $pdo->prepare("UPDATE mail_accounts SET is_default = 0 WHERE utente_id = ?")
            ->execute([currentUserId()]);
        
        // Imposta questo come default
        $pdo->prepare("UPDATE mail_accounts SET is_default = 1 WHERE id = ?")
            ->execute([$accountId]);
        
        jsonResponse(true, null, 'Account predefinito aggiornato');
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Elimina account
 */
function deleteAccount() {
    global $pdo;
    
    $accountId = $_POST['account_id'] ?? '';
    
    try {
        // Disattiva invece di eliminare (soft delete)
        $pdo->prepare("UPDATE mail_accounts SET attivo = 0 WHERE id = ? AND utente_id = ?")
            ->execute([$accountId, currentUserId()]);
        
        jsonResponse(true, null, 'Account eliminato');
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Elimina messaggio
 */
function deleteMessage() {
    global $pdo;
    
    $messageId = $_GET['message_id'] ?? '';
    
    try {
        // Soft delete
        $pdo->prepare("UPDATE mail_messages SET is_cestinata = 1 WHERE id = ?")
            ->execute([$messageId]);
        
        jsonResponse(true, null, 'Messaggio eliminato');
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore');
    }
}

/**
 * Test connessione IMAP
 */
function testConnection() {
    global $pdo;
    
    $accountId = $_POST['account_id'] ?? '';
    
    if (!function_exists('imap_open')) {
        jsonResponse(false, null, 'IMAP non disponibile');
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM mail_accounts WHERE id = ?");
        $stmt->execute([$accountId]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$account) {
            jsonResponse(false, null, 'Account non trovato');
        }
        
        $key = getenv('ENCRYPTION_KEY') ?: 'default_key_change_in_production';
        $key = str_pad(substr($key, 0, 32), 32, '0');
        $password = decryptPassword($account['imap_password'], $key);
        
        $mailbox = '{' . $account['imap_server'] . ':' . $account['imap_port'] . '/imap' . ($account['imap_ssl'] ? '/ssl' : '') . '}INBOX';
        $imap = @imap_open($mailbox, $account['imap_username'], $password, OP_HALFOPEN);
        
        if ($imap) {
            imap_close($imap);
            jsonResponse(true, null, 'Connessione riuscita');
        } else {
            jsonResponse(false, null, 'Connessione fallita: ' . imap_last_error());
        }
    } catch (Exception $e) {
        jsonResponse(false, null, 'Errore: ' . $e->getMessage());
    }
}

/**
 * Decifra la password (temporarily using base64 for testing)
 */
function decryptPassword($encryptedData, $key) {
    if (empty($encryptedData)) {
        return '';
    }
    return base64_decode($encryptedData) ?: '';
}
