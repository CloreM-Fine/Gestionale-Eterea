<?php
/**
 * ETEREA STUDIO - Gestionale
 * Sezione: Mail (Client Email Integrato)
 * 
 * @copyright 2024-2025 Eterea Studio
 * @license MIT
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentPage = 'mail';
$pageTitle = 'Mail';

// Verifica e crea tabelle se necessario
try {
    global $pdo;
    
    // Tabella account email
    $pdo->exec("CREATE TABLE IF NOT EXISTS mail_accounts (
        id VARCHAR(20) PRIMARY KEY,
        utente_id VARCHAR(20) NOT NULL,
        email VARCHAR(255) NOT NULL,
        nome_visualizzato VARCHAR(255),
        imap_server VARCHAR(255),
        imap_port INT DEFAULT 993,
        imap_ssl TINYINT DEFAULT 1,
        imap_username VARCHAR(255),
        imap_password TEXT,
        smtp_server VARCHAR(255),
        smtp_port INT DEFAULT 587,
        smtp_ssl TINYINT DEFAULT 1,
        smtp_username VARCHAR(255),
        smtp_password TEXT,
        is_default TINYINT DEFAULT 0,
        attivo TINYINT DEFAULT 1,
        ultima_sincronizzazione DATETIME,
        creato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        aggiornato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_utente (utente_id),
        INDEX idx_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Tabella messaggi email
    $pdo->exec("CREATE TABLE IF NOT EXISTS mail_messages (
        id VARCHAR(20) PRIMARY KEY,
        account_id VARCHAR(20) NOT NULL,
        message_id VARCHAR(500),
        parent_id VARCHAR(20),
        cliente_id VARCHAR(20),
        progetto_id VARCHAR(20),
        cartella VARCHAR(50) DEFAULT 'inbox',
        mittente_email VARCHAR(255),
        mittente_nome VARCHAR(255),
        destinatari TEXT,
        cc TEXT,
        bcc TEXT,
        oggetto VARCHAR(500),
        corpo_text TEXT,
        corpo_html LONGTEXT,
        data_ricezione DATETIME,
        data_invio DATETIME,
        is_letta TINYINT DEFAULT 0,
        is_inviata TINYINT DEFAULT 0,
        is_bozza TINYINT DEFAULT 0,
        is_importante TINYINT DEFAULT 0,
        is_spam TINYINT DEFAULT 0,
        is_cestinata TINYINT DEFAULT 0,
        has_allegati TINYINT DEFAULT 0,
        allegati_count INT DEFAULT 0,
        uid_imap VARCHAR(255),
        size_bytes INT,
        creato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_account_cartella (account_id, cartella),
        INDEX idx_cliente (cliente_id),
        INDEX idx_progetto (progetto_id),
        INDEX idx_data (data_ricezione),
        INDEX idx_message_id (message_id(255))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Tabella allegati email
    $pdo->exec("CREATE TABLE IF NOT EXISTS mail_attachments (
        id VARCHAR(20) PRIMARY KEY,
        message_id VARCHAR(20) NOT NULL,
        filename VARCHAR(255),
        original_filename VARCHAR(255),
        mime_type VARCHAR(100),
        file_size INT,
        file_path VARCHAR(500),
        is_inline TINYINT DEFAULT 0,
        content_id VARCHAR(255),
        creato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_message (message_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
} catch (Exception $e) {
    error_log("Errore creazione tabelle mail: " . $e->getMessage());
}

// Parametri GET
$folder = $_GET['folder'] ?? 'inbox';
$accountId = $_GET['account'] ?? '';
$view = $_GET['view'] ?? 'list'; // list, compose, detail, settings
$messageId = $_GET['message'] ?? '';

// Carica account email utente
$accounts = [];
$currentUserId = $_SESSION['user_id'] ?? '';
try {
    if ($currentUserId) {
        $stmt = $pdo->prepare("SELECT * FROM mail_accounts WHERE utente_id = ? AND attivo = 1 ORDER BY is_default DESC, email ASC");
        $stmt->execute([$currentUserId]);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Errore caricamento account: " . $e->getMessage());
}

$hasAccounts = count($accounts) > 0;
$activeAccount = null;
if ($accountId && $hasAccounts) {
    foreach ($accounts as $acc) {
        if ($acc['id'] === $accountId) {
            $activeAccount = $acc;
            break;
        }
    }
}
if (!$activeAccount && $hasAccounts) {
    $activeAccount = $accounts[0];
    $accountId = $activeAccount['id'];
}

// Carica conteggio email per cartelle
$folderCounts = [];
if ($activeAccount) {
    try {
        $stmt = $pdo->prepare("
            SELECT cartella, COUNT(*) as totale, SUM(CASE WHEN is_letta = 0 THEN 1 ELSE 0 END) as non_letti
            FROM mail_messages 
            WHERE account_id = ? AND is_cestinata = 0 AND is_spam = 0
            GROUP BY cartella
        ");
        $stmt->execute([$accountId]);
        $folderCountsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $folderCounts = [];
        foreach ($folderCountsRaw as $row) {
            $folderCounts[$row['cartella']] = $row;
        }
    } catch (Exception $e) {
        error_log("Errore conteggio cartelle: " . $e->getMessage());
    }
}

// Carica clienti per associazione
$clienti = [];
try {
    $stmt = $pdo->query("SELECT id, ragione_sociale as nome, email FROM clienti WHERE email IS NOT NULL AND email != '' ORDER BY ragione_sociale");
    $clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Errore caricamento clienti: " . $e->getMessage());
}

// Carica progetti attivi
$progetti = [];
try {
    // Query disabilitata temporaneamente - verifica struttura tabella progetti
    // $stmt = $pdo->query("SELECT id, titolo, cliente_id FROM progetti WHERE stato IN ('in_corso') ORDER BY titolo");
    // $progetti = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Errore caricamento progetti: " . $e->getMessage());
}

// Carica email bozze per composizione
$bozze = [];
if ($view === 'compose') {
    try {
        $stmt = $pdo->prepare("SELECT id, oggetto, destinatari, is_bozza FROM mail_messages WHERE account_id = ? AND is_bozza = 1 ORDER BY creato_il DESC LIMIT 10");
        $stmt->execute([$accountId]);
        $bozze = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Errore caricamento bozze: " . $e->getMessage());
    }
}

// Se c'è un messaggio specifico, caricalo
$currentMessage = null;
if ($messageId && $view === 'detail') {
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, c.ragione_sociale as cliente_nome, p.titolo as progetto_nome
            FROM mail_messages m
            LEFT JOIN clienti c ON m.cliente_id = c.id
            LEFT JOIN progetti p ON m.progetto_id = p.id
            WHERE m.id = ? AND m.account_id = ?
        ");
        $stmt->execute([$messageId, $accountId]);
        $currentMessage = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Marca come letta
        if ($currentMessage && !$currentMessage['is_letta']) {
            $pdo->prepare("UPDATE mail_messages SET is_letta = 1 WHERE id = ?")->execute([$messageId]);
        }
        
        // Carica allegati
        if ($currentMessage) {
            $stmt = $pdo->prepare("SELECT * FROM mail_attachments WHERE message_id = ?");
            $stmt->execute([$messageId]);
            $currentMessage['allegati'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Errore caricamento messaggio: " . $e->getMessage());
    }
}

// Carica lista email per la cartella corrente
$messages = [];
$totalMessages = 0;
if ($activeAccount && $view === 'list') {
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
            case 'spam':
                $where .= " AND is_spam = 1";
                break;
            case 'trash':
                $where = "account_id = ? AND is_cestinata = 1";
                break;
            case 'inbox':
            default:
                $where .= " AND is_inviata = 0 AND is_bozza = 0 AND is_spam = 0";
                break;
        }
        
        // Conteggio totale
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM mail_messages WHERE $where");
        $stmt->execute($params);
        $totalMessages = $stmt->fetchColumn();
        
        // Email recenti (limit 50)
        $stmt = $pdo->prepare("
            SELECT m.*, c.ragione_sociale as cliente_nome
            FROM mail_messages m
            LEFT JOIN clienti c ON m.cliente_id = c.id
            WHERE $where
            ORDER BY COALESCE(m.data_ricezione, m.data_invio, m.creato_il) DESC
            LIMIT 50
        ");
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Errore caricamento messaggi: " . $e->getMessage());
    }
}

// Carica template risposta rapida
$templateRisposte = [
    "Grazie per la tua email.\n\nTi rispondo al più presto.\n\nCordiali saluti,",
    "Gentile Cliente,\n\nGrazie per averci contattato.\n\nCordiali saluti,",
    "Ciao,\n\nricevuta, procedo con le verifiche del caso.\n\nA presto,",
];

try {
    $stmt = $pdo->query("SELECT valore FROM impostazioni WHERE chiave = 'mail_template_risposte' LIMIT 1");
    $customTemplates = $stmt->fetchColumn();
    if ($customTemplates) {
        $decoded = json_decode($customTemplates, true);
        if ($decoded && is_array($decoded)) {
            $templateRisposte = $decoded;
        }
    }
} catch (Exception $e) {
    // Ignora errore
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Layout Mail */
.mail-container {
    display: flex;
    height: calc(100vh - 160px);
    min-height: 500px;
    background: white;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
}

.mail-sidebar {
    width: 260px;
    min-width: 260px;
    background: #f8fafc;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
}

.mail-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Sidebar Buttons */
.btn-compose {
    background: linear-gradient(135deg, #9bc4d0 0%, #8ab4c0 100%);
    color: #2d2d2d;
    font-weight: 600;
    padding: 12px 20px;
    border-radius: 8px;
    margin: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-compose:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(155, 196, 208, 0.4);
}

/* Folders */
.mail-folders {
    flex: 1;
    overflow-y: auto;
    padding: 0 8px;
}

.folder-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s;
    color: #475569;
    font-size: 14px;
}

.folder-item:hover {
    background: #e2e8f0;
}

.folder-item.active {
    background: #9bc4d0;
    color: #2d2d2d;
    font-weight: 500;
}

.folder-item svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
}

.folder-name {
    flex: 1;
}

.folder-count {
    background: #e2e8f0;
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 10px;
    min-width: 20px;
    text-align: center;
}

.folder-item.active .folder-count {
    background: rgba(45, 45, 45, 0.2);
    color: #2d2d2d;
}

.folder-item.unread .folder-count {
    background: #9bc4d0;
    color: #2d2d2d;
}

/* Email List */
.mail-list {
    flex: 1;
    overflow-y: auto;
    border-right: 1px solid #e2e8f0;
    min-width: 350px;
}

.mail-list-header {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.mail-search {
    position: relative;
    flex: 1;
    margin-right: 12px;
}

.mail-search input {
    width: 100%;
    padding: 8px 12px 8px 36px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    background: white;
}

.mail-search svg {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    width: 18px;
    height: 18px;
}

.mail-actions {
    display: flex;
    gap: 4px;
}

.mail-action-btn {
    padding: 8px;
    border-radius: 6px;
    color: #64748b;
    transition: all 0.15s;
}

.mail-action-btn:hover {
    background: #e2e8f0;
    color: #475569;
}

/* Email Item */
.email-item {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: all 0.15s;
    position: relative;
}

.email-item:hover {
    background: #f8fafc;
}

.email-item.unread {
    background: #f0f9ff;
}

.email-item.unread .email-from,
.email-item.unread .email-subject {
    font-weight: 600;
}

.email-item.selected {
    background: #e0f2fe;
    border-left: 3px solid #9bc4d0;
}

.email-item.important {
    border-left: 3px solid #f59e0b;
}

.email-header-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.email-from {
    flex: 1;
    font-size: 14px;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.email-date {
    font-size: 12px;
    color: #94a3b8;
    white-space: nowrap;
}

.email-subject {
    font-size: 13px;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

.email-preview {
    font-size: 12px;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.email-badges {
    display: flex;
    gap: 4px;
    margin-top: 6px;
}

.email-badge {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
}

.badge-client {
    background: #a8b5a0;
    color: white;
}

.badge-project {
    background: #c4b5d0;
    color: white;
}

.badge-attachment {
    background: #e2e8f0;
    color: #64748b;
}

/* Email Detail */
.mail-detail {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: white;
}

.detail-header {
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.detail-actions {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.detail-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.15s;
}

.detail-btn-primary {
    background: #9bc4d0;
    color: #2d2d2d;
}

.detail-btn-primary:hover {
    background: #8ab4c0;
}

.detail-btn-secondary {
    background: #e2e8f0;
    color: #475569;
}

.detail-btn-secondary:hover {
    background: #cbd5e1;
}

.detail-subject {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 16px;
    line-height: 1.3;
}

.detail-meta {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.detail-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: linear-gradient(135deg, #9bc4d0 0%, #a8b5a0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
    flex-shrink: 0;
}

.detail-sender-info {
    flex: 1;
}

.detail-from {
    font-weight: 600;
    color: #1e293b;
    font-size: 14px;
}

.detail-to {
    color: #64748b;
    font-size: 13px;
    margin-top: 2px;
}

.detail-date {
    color: #94a3b8;
    font-size: 13px;
}

.detail-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    font-size: 14px;
    line-height: 1.7;
    color: #334155;
}

.detail-body img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}

.detail-attachments {
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}

.attachment-title {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 12px;
}

.attachment-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.attachment-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 13px;
    color: #475569;
    transition: all 0.15s;
}

.attachment-item:hover {
    border-color: #9bc4d0;
    background: #f0f9ff;
}

.attachment-icon {
    width: 20px;
    height: 20px;
    color: #9bc4d0;
}

/* Compose Area */
.compose-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
}

.compose-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.compose-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.compose-actions {
    display: flex;
    gap: 8px;
}

.compose-form {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.compose-fields {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
}

.compose-field {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}

.compose-field:last-child {
    border-bottom: none;
}

.compose-field label {
    width: 60px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    flex-shrink: 0;
}

.compose-field input,
.compose-field select {
    flex: 1;
    border: none;
    outline: none;
    font-size: 14px;
    padding: 4px 0;
    background: transparent;
}

.compose-field input::placeholder {
    color: #94a3b8;
}

.compose-editor {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}

.compose-editor textarea {
    width: 100%;
    height: 100%;
    border: none;
    outline: none;
    resize: none;
    font-size: 14px;
    line-height: 1.7;
    font-family: inherit;
}

.compose-toolbar {
    padding: 12px 20px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.compose-toolbar-left {
    display: flex;
    gap: 8px;
}

.toolbar-btn {
    padding: 8px;
    border-radius: 6px;
    color: #64748b;
    transition: all 0.15s;
}

.toolbar-btn:hover {
    background: #e2e8f0;
    color: #475569;
}

.attach-menu {
    position: relative;
}

.attach-dropdown {
    position: absolute;
    bottom: 100%;
    left: 0;
    margin-bottom: 8px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    min-width: 220px;
    display: none;
    z-index: 100;
}

.attach-dropdown.show {
    display: block;
}

.attach-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    font-size: 13px;
    color: #475569;
    cursor: pointer;
    transition: all 0.15s;
}

.attach-option:hover {
    background: #f8fafc;
}

.attach-option:first-child {
    border-radius: 8px 8px 0 0;
}

.attach-option:last-child {
    border-radius: 0 0 8px 8px;
}

.attach-option svg {
    width: 18px;
    height: 18px;
    color: #9bc4d0;
}

/* Settings */
.settings-container {
    padding: 24px;
    max-width: 700px;
}

.settings-title {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 24px;
}

.settings-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.settings-section h3 {
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 16px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Account Card */
.account-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
}

.account-card.active {
    border-color: #9bc4d0;
    box-shadow: 0 0 0 2px rgba(155, 196, 208, 0.2);
}

.account-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.account-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #9bc4d0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
}

.account-info {
    flex: 1;
}

.account-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 14px;
}

.account-email {
    font-size: 13px;
    color: #64748b;
}

.account-status {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 10px;
    font-weight: 500;
}

.status-active {
    background: #a8b5a0;
    color: white;
}

.status-inactive {
    background: #e2e8f0;
    color: #64748b;
}

/* Responsive */
@media (max-width: 1024px) {
    .mail-container {
        height: calc(100vh - 180px);
    }
    
    .mail-sidebar {
        width: 220px;
        min-width: 220px;
    }
}

@media (max-width: 768px) {
    .mail-container {
        flex-direction: column;
        height: auto;
        min-height: calc(100vh - 200px);
    }
    
    .mail-sidebar {
        width: 100%;
        min-width: auto;
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
        max-height: 200px;
    }
    
    .mail-list {
        border-right: none;
        min-width: auto;
        max-height: 400px;
    }
    
    .detail-subject {
        font-size: 16px;
    }
    
    .compose-field {
        flex-wrap: wrap;
    }
    
    .compose-field label {
        width: auto;
    }
    
    .compose-field input,
    .compose-field select {
        width: 100%;
    }
}

/* Empty States */
.empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    text-align: center;
    color: #94a3b8;
}

.empty-state svg {
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
    color: #cbd5e1;
}

.empty-state h3 {
    font-size: 16px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 14px;
    max-width: 300px;
}

/* Account Selector */
.account-selector {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
}

.account-selector select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 13px;
    background: white;
}

/* Reply Modal */
.reply-modal {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.reply-modal-content {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.reply-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.reply-header h3 {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.reply-close {
    padding: 8px;
    border-radius: 6px;
    color: #64748b;
}

.reply-close:hover {
    background: #f1f5f9;
}

/* Client Association */
.client-association {
    padding: 12px 20px;
    background: #f0fdf4;
    border-bottom: 1px solid #e2e8f0;
}

.client-association.associated {
    background: #f0f9ff;
}

.association-label {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    margin-bottom: 8px;
}

.association-select {
    display: flex;
    gap: 8px;
}

.association-select select {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 13px;
    background: white;
}

.association-btn {
    padding: 8px 14px;
    background: #a8b5a0;
    color: white;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
}

.association-btn:hover {
    background: #97a48f;
}

.current-association {
    display: flex;
    align-items: center;
    gap: 12px;
}

.association-info {
    flex: 1;
}

.association-name {
    font-weight: 500;
    color: #1e293b;
    font-size: 14px;
}

.association-type {
    font-size: 12px;
    color: #64748b;
}
</style>

<div class="space-y-4">
    <!-- Banner In Lavorazione -->
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300 rounded-xl p-8 text-center">
        <div class="flex flex-col items-center justify-center gap-4">
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-amber-800">Sezione in Lavorazione</h2>
            <p class="text-amber-700 max-w-md">La sezione Mail è attualmente in fase di sviluppo e sarà disponibile a breve.</p>
            <a href="dashboard.php" class="mt-2 px-6 py-2 bg-amber-500 text-white rounded-lg font-medium hover:bg-amber-600 transition-colors">
                Torna alla Dashboard
            </a>
        </div>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-[#2d2d2d]">Mail</h1>
        <div class="flex items-center gap-3">
            <?php if ($hasAccounts): ?>
            <button onclick="openSyncModal()" class="flex items-center gap-2 px-4 py-2 bg-white border border-[#e2e8f0] rounded-lg text-sm font-medium text-[#475569] hover:bg-[#f8fafc] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Sincronizza
            </button>
            <?php endif; ?>
            <a href="?view=settings" class="flex items-center gap-2 px-4 py-2 bg-[#9bc4d0] text-[#2d2d2d] rounded-lg text-sm font-medium hover:bg-[#8ab4c0] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Configura
            </a>
        </div>
    </div>

    <?php if (!$hasAccounts && $view !== 'settings'): ?>
    <!-- Empty State - No Accounts -->
    <div class="bg-white rounded-xl border border-[#e2e8f0] p-12 text-center">
        <div class="w-20 h-20 bg-[#9bc4d0]/20 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-[#9bc4d0]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h2 class="text-xl font-semibold text-[#2d2d2d] mb-2">Nessun account email configurato</h2>
        <p class="text-[#64748b] mb-6 max-w-md mx-auto">Configura il tuo primo account email per iniziare a inviare e ricevere messaggi direttamente dal gestionale.</p>
        <a href="?view=settings" class="inline-flex items-center gap-2 px-6 py-3 bg-[#9bc4d0] text-[#2d2d2d] rounded-lg font-medium hover:bg-[#8ab4c0] transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Aggiungi Account Email
        </a>
    </div>
    <?php elseif ($view === 'settings'): ?>
    <!-- Settings View -->
    <div class="bg-white rounded-xl border border-[#e2e8f0] overflow-hidden">
        <div class="p-6 border-b border-[#e2e8f0]">
            <h2 class="text-lg font-semibold text-[#2d2d2d]">Configurazione Email</h2>
            <p class="text-sm text-[#64748b] mt-1">Gestisci i tuoi account email e le impostazioni IMAP/SMTP</p>
        </div>
        
        <div class="settings-container">
            <!-- Account esistenti -->
            <div class="settings-section">
                <h3>Account Configurati</h3>
                <?php if ($accounts): ?>
                    <?php foreach ($accounts as $account): ?>
                    <div class="account-card <?php echo $account['is_default'] ? 'active' : ''; ?>">
                        <div class="account-header">
                            <div class="account-avatar"><?php echo strtoupper(substr($account['nome_visualizzato'] ?: $account['email'], 0, 1)); ?></div>
                            <div class="account-info">
                                <div class="account-name"><?php echo e($account['nome_visualizzato'] ?: $account['email']); ?></div>
                                <div class="account-email"><?php echo e($account['email']); ?></div>
                            </div>
                            <span class="account-status <?php echo $account['attivo'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $account['attivo'] ? 'Attivo' : 'Inattivo'; ?>
                            </span>
                        </div>
                        <div class="flex gap-2">
                            <?php if (!$account['is_default']): ?>
                            <button onclick="setDefaultAccount('<?php echo $account['id']; ?>')" class="text-xs px-3 py-1.5 bg-[#e2e8f0] text-[#475569] rounded hover:bg-[#cbd5e1]">Imposta Predefinito</button>
                            <?php endif; ?>
                            <button onclick="editAccount('<?php echo $account['id']; ?>')" class="text-xs px-3 py-1.5 bg-[#9bc4d0] text-[#2d2d2d] rounded hover:bg-[#8ab4c0]">Modifica</button>
                            <button onclick="deleteAccount('<?php echo $account['id']; ?>')" class="text-xs px-3 py-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200">Elimina</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-sm text-[#64748b]">Nessun account configurato</p>
                <?php endif; ?>
            </div>
            
            <!-- Form nuovo account -->
            <div class="settings-section">
                <h3>Aggiungi Nuovo Account</h3>
                <form id="accountForm" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="save_account">
                    <input type="hidden" name="account_id" id="account_id" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-[#64748b] mb-1">Indirizzo Email *</label>
                            <input type="email" name="email" required class="w-full px-3 py-2 border border-[#e2e8f0] rounded-lg text-sm focus:outline-none focus:border-[#9bc4d0]">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-[#64748b] mb-1">Nome Visualizzato</label>
                            <input type="text" name="nome_visualizzato" placeholder="Es: Lorenzo Puccetti" class="w-full px-3 py-2 border border-[#e2e8f0] rounded-lg text-sm focus:outline-none focus:border-[#9bc4d0]">
                        </div>
                    </div>
                    
                    <div class="border-t border-[#e2e8f0] pt-4">
                        <h4 class="text-sm font-medium text-[#334155] mb-3">Impostazioni IMAP (Ricezione)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-[#64748b] mb-1">Server IMAP *</label>
                                <input type="text" name="imap_server" placeholder="Es: imap.gmail.com" required class="w-full px-3 py-2 border border-[#e2e8f0] rounded-lg text-sm focus:outline-none focus:border-[#9bc4d0]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[#64748b] mb-1">Porta</label>
                                <input type="number" name="imap_port" value="993" class="w-full px-3 py-2 border border-[#e2e8f0] rounded-lg text-sm focus:outline-none focus:border-[#9bc4d0]">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="imap_ssl" value="1" checked class="rounded border-[#e2e8f0] text-[#9bc4d0] focus:ring-[#9bc4d0]">
                                <span class="text-sm text-[#64748b]">Usa SSL/TLS</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="border-t border-[#e2e8f0] pt-4">
                        <h4 class="text-sm font-medium text-[#334155] mb-3">Impostazioni SMTP (Invio)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-[#64748b] mb-1">Server SMTP *</label>
                                <input type="text" name="smtp_server" placeholder="Es: smtp.gmail.com" required class="w-full px-3 py-2 border border-[#e2e8f0] rounded-lg text-sm focus:outline-none focus:border-[#9bc4d0]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[#64748b] mb-1">Porta</label>
                                <select name="smtp_port" class="w-full px-3 py-2 border border-[#e2e8f0] rounded-lg text-sm focus:outline-none focus:border-[#9bc4d0]">
                                    <option value="587">587 (STARTTLS)</option>
                                    <option value="465">465 (SSL)</option>
                                    <option value="25">25 (Non sicura)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="smtp_ssl" value="1" checked class="rounded border-[#e2e8f0] text-[#9bc4d0] focus:ring-[#9bc4d0]">
                                <span class="text-sm text-[#64748b]">Usa SSL/TLS</span>
                            </label>
                        </div>
                        <p class="text-xs text-[#94a3b8] mt-2">Nota: Porta 465 = SSL, Porta 587 = STARTTLS</p>
                    </div>
                    
                    <div class="border-t border-[#e2e8f0] pt-4">
                        <h4 class="text-sm font-medium text-[#334155] mb-3">Credenziali</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-[#64748b] mb-1">Username (se diverso dall'email)</label>
                                <input type="text" name="imap_username" placeholder="esempio@mail.com" class="w-full px-3 py-2 border border-[#e2e8f0] rounded-lg text-sm focus:outline-none focus:border-[#9bc4d0]">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-[#64748b] mb-1">Password / App Password *</label>
                                <input type="password" name="imap_password" required class="w-full px-3 py-2 border border-[#e2e8f0] rounded-lg text-sm focus:outline-none focus:border-[#9bc4d0]">
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="px-6 py-2.5 bg-[#9bc4d0] text-[#2d2d2d] rounded-lg font-medium hover:bg-[#8ab4c0] transition-colors">Salva Account</button>
                        <a href="?" class="px-6 py-2.5 border border-[#e2e8f0] text-[#64748b] rounded-lg font-medium hover:bg-[#f8fafc] transition-colors">Annulla</a>
                    </div>
                </form>
            </div>
            
            <!-- Configurazione rapida -->
            <div class="settings-section">
                <h3>Configurazione Rapida Provider</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <button onclick="fillProviderConfig('gmail')" class="p-3 border border-[#e2e8f0] rounded-lg text-left hover:border-[#9bc4d0] hover:bg-[#f8fafc] transition-colors">
                        <div class="font-medium text-sm text-[#334155]">Gmail</div>
                        <div class="text-xs text-[#94a3b8]">Google Workspace</div>
                    </button>
                    <button onclick="fillProviderConfig('outlook')" class="p-3 border border-[#e2e8f0] rounded-lg text-left hover:border-[#9bc4d0] hover:bg-[#f8fafc] transition-colors">
                        <div class="font-medium text-sm text-[#334155]">Outlook</div>
                        <div class="text-xs text-[#94a3b8]">Microsoft 365</div>
                    </button>
                    <button onclick="fillProviderConfig('yahoo')" class="p-3 border border-[#e2e8f0] rounded-lg text-left hover:border-[#9bc4d0] hover:bg-[#f8fafc] transition-colors">
                        <div class="font-medium text-sm text-[#334155]">Yahoo</div>
                        <div class="text-xs text-[#94a3b8]">Yahoo Mail</div>
                    </button>
                    <button onclick="fillProviderConfig('aruba')" class="p-3 border border-[#e2e8f0] rounded-lg text-left hover:border-[#9bc4d0] hover:bg-[#f8fafc] transition-colors">
                        <div class="font-medium text-sm text-[#334155]">Aruba</div>
                        <div class="text-xs text-[#94a3b8]">Pec / Mail</div>
                    </button>
                    <button onclick="fillProviderConfig('siteground')" class="p-3 border border-[#e2e8f0] rounded-lg text-left hover:border-[#9bc4d0] hover:bg-[#f8fafc] transition-colors">
                        <div class="font-medium text-sm text-[#334155]">SiteGround</div>
                        <div class="text-xs text-[#94a3b8]">Eterea Mail</div>
                    </button>
                </div>
                <p class="text-xs text-[#64748b] mt-3">Nota: Per Gmail usa una "App Password". Vai su Google Account → Sicurezza → Verifica in 2 passaggi → App Password.</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Main Mail View -->
    <div class="mail-container">
        <!-- Sidebar -->
        <aside class="mail-sidebar">
            <a href="?view=compose&account=<?php echo $accountId; ?>" class="btn-compose">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Nuovo Messaggio
            </a>
            
            <div class="account-selector">
                <select onchange="changeAccount(this.value)">
                    <?php foreach ($accounts as $acc): ?>
                    <option value="<?php echo $acc['id']; ?>" <?php echo $acc['id'] === $accountId ? 'selected' : ''; ?>>
                        <?php echo e($acc['nome_visualizzato'] ?: $acc['email']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mail-folders">
                <a href="?folder=inbox&account=<?php echo $accountId; ?>" class="folder-item <?php echo $folder === 'inbox' ? 'active' : ''; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <span class="folder-name">Posta in arrivo</span>
                    <?php if (($folderCounts['inbox']['non_letti'] ?? 0) > 0): ?>
                    <span class="folder-count"><?php echo $folderCounts['inbox']['non_letti']; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="?folder=sent&account=<?php echo $accountId; ?>" class="folder-item <?php echo $folder === 'sent' ? 'active' : ''; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <span class="folder-name">Inviate</span>
                </a>
                
                <a href="?folder=drafts&account=<?php echo $accountId; ?>" class="folder-item <?php echo $folder === 'drafts' ? 'active' : ''; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span class="folder-name">Bozze</span>
                </a>
                
                <a href="?folder=important&account=<?php echo $accountId; ?>" class="folder-item <?php echo $folder === 'important' ? 'active' : ''; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    <span class="folder-name">Importanti</span>
                </a>
                
                <div class="border-t border-[#e2e8f0] my-2"></div>
                
                <a href="?folder=spam&account=<?php echo $accountId; ?>" class="folder-item <?php echo $folder === 'spam' ? 'active' : ''; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    <span class="folder-name">Spam</span>
                </a>
                
                <a href="?folder=trash&account=<?php echo $accountId; ?>" class="folder-item <?php echo $folder === 'trash' ? 'active' : ''; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span class="folder-name">Cestino</span>
                </a>
            </div>
        </aside>
        
        <?php if ($view === 'compose'): ?>
        <!-- Compose View -->
        <div class="compose-container">
            <div class="compose-header">
                <h2 class="compose-title"><?php echo isset($_GET['reply']) ? 'Rispondi' : (isset($_GET['forward']) ? 'Inoltra' : 'Nuovo Messaggio'); ?></h2>
                <div class="compose-actions">
                    <button type="button" onclick="saveDraft()" class="detail-btn detail-btn-secondary">Salva Bozza</button>
                    <button type="button" onclick="sendEmail()" class="detail-btn detail-btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Invia
                    </button>
                </div>
            </div>
            
            <form id="composeForm" class="compose-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="send_email">
                <input type="hidden" name="account_id" value="<?php echo $accountId; ?>">
                <input type="hidden" name="reply_to" value="<?php echo e($_GET['reply'] ?? ''); ?>">
                <input type="hidden" name="forward_from" value="<?php echo e($_GET['forward'] ?? ''); ?>">
                
                <div class="compose-fields">
                    <div class="compose-field">
                        <label>Da:</label>
                        <select name="from_account" class="flex-1 bg-transparent border-none outline-none text-sm">
                            <?php foreach ($accounts as $acc): ?>
                            <option value="<?php echo $acc['id']; ?>" <?php echo $acc['id'] === $accountId ? 'selected' : ''; ?>>
                                <?php echo e(($acc['nome_visualizzato'] ?: $acc['email']) . ' <' . $acc['email'] . '>'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="compose-field">
                        <label>A:</label>
                        <input type="text" name="to" id="composeTo" placeholder="Inserisci destinatari separati da virgola" 
                               value="<?php echo isset($_GET['to']) ? e($_GET['to']) : (isset($_GET['reply']) && $currentMessage ? e($currentMessage['mittente_email']) : ''); ?>">
                        <select onchange="addRecipient(this.value)" class="w-40 text-xs border-l border-[#e2e8f0] pl-3">
                            <option value="">+ Cliente</option>
                            <?php foreach ($clienti as $cli): ?>
                            <option value="<?php echo e($cli['email']); ?>"><?php echo e(($cli['nome'] . ' ' . $cli['cognome'])); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="compose-field">
                        <label>Oggetto:</label>
                        <input type="text" name="subject" id="composeSubject" placeholder="Oggetto della email" 
                               value="<?php echo isset($_GET['reply']) && $currentMessage ? 'Re: ' . e($currentMessage['oggetto']) : (isset($_GET['forward']) && $currentMessage ? 'Fwd: ' . e($currentMessage['oggetto']) : ''); ?>">
                    </div>
                    
                    <div class="compose-field">
                        <label>Associato a:</label>
                        <select name="cliente_id" id="composeCliente" onchange="updateProgetti(this.value)">
                            <option value="">-- Seleziona cliente --</option>
                            <?php foreach ($clienti as $cli): ?>
                            <option value="<?php echo $cli['id']; ?>" <?php echo (isset($_GET['cliente_id']) && $_GET['cliente_id'] === $cli['id']) ? 'selected' : ''; ?>>
                                <?php echo e($cli['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="progetto_id" id="composeProgetto" class="ml-2">
                            <option value="">-- Progetto (opzionale) --</option>
                            <?php foreach ($progetti as $prj): ?>
                            <option value="<?php echo $prj['id']; ?>" data-cliente="<?php echo $prj['cliente_id']; ?>">
                                <?php echo e($prj['nome']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="compose-editor">
                    <textarea name="body" id="composeBody" placeholder="Scrivi il tuo messaggio..."><?php 
                        if (isset($_GET['reply']) && $currentMessage) {
                            echo "\n\n\n--- Messaggio originale ---\nDa: " . e($currentMessage['mittente_nome'] ?: $currentMessage['mittente_email']) . "\n";
                            echo "Data: " . formatDateTime($currentMessage['data_ricezione']) . "\n";
                            echo "Oggetto: " . e($currentMessage['oggetto']) . "\n\n";
                            echo strip_tags($currentMessage['corpo_text'] ?: $currentMessage['corpo_html']);
                        } elseif (isset($_GET['forward']) && $currentMessage) {
                            echo "\n\n\n--- Messaggio inoltrato ---\nDa: " . e($currentMessage['mittente_nome'] ?: $currentMessage['mittente_email']) . "\n";
                            echo "Data: " . formatDateTime($currentMessage['data_ricezione']) . "\n";
                            echo "Oggetto: " . e($currentMessage['oggetto']) . "\n\n";
                            echo strip_tags($currentMessage['corpo_text'] ?: $currentMessage['corpo_html']);
                        }
                    ?></textarea>
                </div>
                
                <div class="compose-toolbar">
                    <div class="compose-toolbar-left">
                        <div class="attach-menu">
                            <button type="button" class="toolbar-btn" onclick="toggleAttachMenu()">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                            </button>
                            <div class="attach-dropdown" id="attachDropdown">
                                <div class="attach-option" onclick="document.getElementById('fileUpload').click()">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                    Carica file
                                    <input type="file" id="fileUpload" multiple class="hidden" onchange="handleFileUpload(this)">
                                </div>
                                <div class="attach-option" onclick="openFileSelector('clienti')">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    Da file cliente
                                </div>
                                <div class="attach-option" onclick="openFileSelector('progetti')">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                    </svg>
                                    Da progetto
                                </div>
                                <div class="attach-option" onclick="openPreventiviSelector()">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 15.536c-1.171 1.952-3.07 1.952-4.242 0-1.172-1.953-1.172-5.119 0-7.072 1.171-1.952 3.07-1.952 4.242 0M8 10.5h4m-4 3h4m9-1.5a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Preventivo (PDF)
                                </div>
                                <!-- Blog link disabilitato temporaneamente -->
                            </div>
                        </div>
                        
                        <button type="button" class="toolbar-btn" onclick="insertTemplate()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div id="attachmentPreview" class="flex items-center gap-2 flex-wrap">
                        <!-- Preview allegati caricati -->
                    </div>
                </div>
            </form>
        </div>
        
        <?php elseif ($view === 'detail' && $currentMessage): ?>
        <!-- Detail View -->
        <div class="mail-detail">
            <div class="detail-header">
                <div class="detail-actions">
                    <a href="?folder=<?php echo $folder; ?>&account=<?php echo $accountId; ?>" class="detail-btn detail-btn-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Indietro
                    </a>
                    <a href="?view=compose&reply=<?php echo $currentMessage['id']; ?>&account=<?php echo $accountId; ?>" class="detail-btn detail-btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Rispondi
                    </a>
                    <a href="?view=compose&replyall=<?php echo $currentMessage['id']; ?>&account=<?php echo $accountId; ?>" class="detail-btn detail-btn-secondary">
                        Rispondi a tutti
                    </a>
                    <a href="?view=compose&forward=<?php echo $currentMessage['id']; ?>&account=<?php echo $accountId; ?>" class="detail-btn detail-btn-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                        </svg>
                        Inoltra
                    </a>
                    <button onclick="toggleImportant('<?php echo $currentMessage['id']; ?>')" class="detail-btn detail-btn-secondary <?php echo $currentMessage['is_importante'] ? 'text-yellow-500' : ''; ?>">
                        <svg class="w-4 h-4" fill="<?php echo $currentMessage['is_importante'] ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </button>
                    <button onclick="moveToTrash('<?php echo $currentMessage['id']; ?>')" class="detail-btn detail-btn-secondary text-red-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
                
                <h1 class="detail-subject"><?php echo e($currentMessage['oggetto'] ?: '(nessun oggetto)'); ?></h1>
                
                <div class="detail-meta">
                    <div class="detail-avatar">
                        <?php echo strtoupper(substr($currentMessage['mittente_nome'] ?: $currentMessage['mittente_email'], 0, 1)); ?>
                    </div>
                    <div class="detail-sender-info">
                        <div class="detail-from">
                            <?php echo e($currentMessage['mittente_nome'] ?: $currentMessage['mittente_email']); ?>
                            <span class="text-[#64748b] font-normal">&lt;<?php echo e($currentMessage['mittente_email']); ?>&gt;</span>
                        </div>
                        <div class="detail-to">A: <?php echo e($currentMessage['destinatari']); ?></div>
                    </div>
                    <div class="detail-date">
                        <?php echo formatDateTime($currentMessage['data_ricezione'] ?: $currentMessage['data_invio']); ?>
                    </div>
                </div>
            </div>
            
            <?php if (!$currentMessage['cliente_id']): ?>
            <div class="client-association">
                <div class="association-label">Associa a cliente:</div>
                <div class="association-select">
                    <select id="associaClienteSelect">
                        <option value="">-- Seleziona cliente --</option>
                        <?php foreach ($clienti as $cli): ?>
                        <?php if ($cli['email'] === $currentMessage['mittente_email']): ?>
                        <option value="<?php echo $cli['id']; ?>" selected><?php echo e($cli['nome'] . ' - MATCH EMAIL'); ?></option>
                        <?php else: ?>
                        <option value="<?php echo $cli['id']; ?>"><?php echo e($cli['nome']); ?></option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <select id="associaProgettoSelect">
                        <option value="">-- Progetto (opzionale) --</option>
                        <?php foreach ($progetti as $prj): ?>
                        <option value="<?php echo $prj['id']; ?>"><?php echo e($prj['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="associaCliente('<?php echo $currentMessage['id']; ?>')" class="association-btn">Associa</button>
                </div>
            </div>
            <?php else: ?>
            <div class="client-association associated">
                <div class="current-association">
                    <svg class="w-5 h-5 text-[#a8b5a0]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <div class="association-info">
                        <div class="association-name">
                            Associato a: <?php echo e($currentMessage['cliente_nome'] ?? 'Cliente'); ?>
                        </div>
                        <?php if ($currentMessage['progetto_nome']): ?>
                        <div class="association-type">Progetto: <?php echo e($currentMessage['progetto_nome']); ?></div>
                        <?php endif; ?>
                    </div>
                    <button onclick="rimuoviAssociazione('<?php echo $currentMessage['id']; ?>')" class="text-sm text-red-500 hover:underline">Rimuovi</button>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="detail-body">
                <?php if ($currentMessage['corpo_html']): ?>
                <div class="email-html-content">
                    <?php echo $currentMessage['corpo_html'] ? strip_tags($currentMessage['corpo_html'], '<p><br><strong><em><a><ul><ol><li><h1><h2><h3><h4><blockquote>') : ''; ?>
                </div>
                <?php else: ?>
                <pre class="whitespace-pre-wrap font-sans"><?php echo nl2br(e($currentMessage['corpo_text'])); ?></pre>
                <?php endif; ?>
            </div>
            
            <?php if ($currentMessage['has_allegati'] && !empty($currentMessage['allegati'])): ?>
            <div class="detail-attachments">
                <div class="attachment-title">
                    <?php echo count($currentMessage['allegati']); ?> Allegat<?php echo count($currentMessage['allegati']) === 1 ? 'o' : 'i'; ?>
                </div>
                <div class="attachment-list">
                    <?php foreach ($currentMessage['allegati'] as $att): ?>
                    <a href="<?php echo e($att['file_path']); ?>" target="_blank" class="attachment-item" download="<?php echo e($att['original_filename']); ?>">
                        <svg class="attachment-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <span><?php echo e($att['original_filename']); ?></span>
                        <span class="text-xs text-[#94a3b8]">(<?php echo formatBytes($att['file_size']); ?>)</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        <!-- List View -->
        <div class="mail-list">
            <div class="mail-list-header">
                <div class="mail-search">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" id="emailSearch" placeholder="Cerca nelle email..." onkeyup="searchEmails()">
                </div>
                <div class="mail-actions">
                    <button onclick="refreshEmails()" class="mail-action-btn" title="Aggiorna">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                    <button onclick="markAllRead()" class="mail-action-btn" title="Segna tutte come lette">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <?php if (empty($messages)): ?>
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <h3>Nessuna email</h3>
                <p>Non ci sono email in questa cartella</p>
            </div>
            <?php else: ?>
            <div id="emailListContainer">
                <?php foreach ($messages as $msg): ?>
                <a href="?view=detail&message=<?php echo $msg['id']; ?>&folder=<?php echo $folder; ?>&account=<?php echo $accountId; ?>" 
                   class="email-item <?php echo !$msg['is_letta'] ? 'unread' : ''; ?> <?php echo $msg['is_importante'] ? 'important' : ''; ?>"
                   data-subject="<?php echo e(strtolower($msg['oggetto'])); ?>"
                   data-from="<?php echo e(strtolower($msg['mittente_nome'] . ' ' . $msg['mittente_email'])); ?>">
                    <div class="email-header-row">
                        <span class="email-from">
                            <?php echo e($msg['mittente_nome'] ?: $msg['mittente_email']); ?>
                        </span>
                        <span class="email-date">
                            <?php 
                            $date = strtotime($msg['data_ricezione'] ?: $msg['data_invio']);
                            if (date('Y-m-d') === date('Y-m-d', $date)) {
                                echo date('H:i', $date);
                            } elseif (date('Y') === date('Y', $date)) {
                                echo date('d M', $date);
                            } else {
                                echo date('d/m/Y', $date);
                            }
                            ?>
                        </span>
                    </div>
                    <div class="email-subject"><?php echo e($msg['oggetto'] ?: '(nessun oggetto)'); ?></div>
                    <div class="email-preview"><?php echo e(substr(strip_tags($msg['corpo_text'] ?: $msg['corpo_html'] ?: ''), 0, 80)); ?>...</div>
                    
                    <?php if ($msg['cliente_id'] || $msg['has_allegati']): ?>
                    <div class="email-badges">
                        <?php if ($msg['cliente_id']): ?>
                        <span class="email-badge badge-client">
                            <?php echo e(substr($msg['cliente_nome'] ?? '', 0, 20)); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($msg['has_allegati']): ?>
                        <span class="email-badge badge-attachment">
                            <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                            </svg>
                            <?php echo $msg['allegati_count']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Preview Panel (placeholder per funzionalità future) -->
        <div class="hidden lg:flex flex-col w-80 border-l border-[#e2e8f0] bg-[#f8fafc]">
            <div class="p-4 border-b border-[#e2e8f0]">
                <h3 class="font-semibold text-[#2d2d2d]">Email collegate</h3>
            </div>
            <div class="p-4 text-center text-sm text-[#94a3b8]">
                Seleziona un'email per vedere la conversazione
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Selettore File -->
<div id="fileSelectorModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-2xl max-h-[80vh] flex flex-col">
        <div class="p-4 border-b border-[#e2e8f0] flex items-center justify-between">
            <h3 class="font-semibold text-[#2d2d2d]" id="fileSelectorTitle">Seleziona file</h3>
            <button onclick="closeFileSelector()" class="p-2 hover:bg-[#f8fafc] rounded-lg">
                <svg class="w-5 h-5 text-[#64748b]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-4 overflow-y-auto flex-1" id="fileSelectorContent">
            <!-- Contenuto caricato dinamicamente -->
        </div>
    </div>
</div>

<!-- Modal Selettore Preventivi -->
<div id="preventiviModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-2xl max-h-[80vh] flex flex-col">
        <div class="p-4 border-b border-[#e2e8f0] flex items-center justify-between">
            <h3 class="font-semibold text-[#2d2d2d]">Seleziona Preventivo</h3>
            <button onclick="closePreventiviSelector()" class="p-2 hover:bg-[#f8fafc] rounded-lg">
                <svg class="w-5 h-5 text-[#64748b]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-4 overflow-y-auto flex-1" id="preventiviContent">
            <!-- Caricato dinamicamente -->
        </div>
    </div>
</div>

<!-- Modal Sincronizzazione -->
<div id="syncModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-md p-6">
        <div class="text-center">
            <div class="w-16 h-16 bg-[#9bc4d0]/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-[#9bc4d0] animate-spin" id="syncIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-[#2d2d2d] mb-2">Sincronizzazione in corso</h3>
            <p class="text-sm text-[#64748b] mb-4">Stiamo scaricando le email dal server...</p>
            <div class="w-full bg-[#e2e8f0] rounded-full h-2 mb-4">
                <div id="syncProgress" class="bg-[#9bc4d0] h-2 rounded-full transition-all" style="width: 0%"></div>
            </div>
            <p class="text-xs text-[#94a3b8]" id="syncStatus">Connessione al server...</p>
            <button onclick="closeSyncModal()" class="mt-4 px-4 py-2 text-sm text-[#64748b] hover:text-[#2d2d2d]">Chiudi</button>
        </div>
    </div>
</div>

<script>
// Cambio account
function changeAccount(accountId) {
    window.location.href = '?account=' + accountId + '&folder=inbox';
}

// Ricerca email
function searchEmails() {
    const query = document.getElementById('emailSearch').value.toLowerCase();
    const items = document.querySelectorAll('.email-item');
    
    items.forEach(item => {
        const subject = item.dataset.subject;
        const from = item.dataset.from;
        if (subject.includes(query) || from.includes(query)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

// Gestione allegati
function toggleAttachMenu() {
    document.getElementById('attachDropdown').classList.toggle('show');
}

// Chiudi dropdown quando si clicca fuori
document.addEventListener('click', function(e) {
    const menu = document.querySelector('.attach-menu');
    if (menu && !menu.contains(e.target)) {
        const dropdown = document.getElementById('attachDropdown');
        if (dropdown) dropdown.classList.remove('show');
    }
});

// File allegati temporanei
let tempAttachments = [];

function handleFileUpload(input) {
    const files = Array.from(input.files);
    const preview = document.getElementById('attachmentPreview');
    
    files.forEach(file => {
        tempAttachments.push(file);
        
        const badge = document.createElement('span');
        badge.className = 'inline-flex items-center gap-1 px-2 py-1 bg-[#f0f9ff] text-[#334155] text-xs rounded border border-[#9bc4d0]';
        badge.innerHTML = `
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
            ${file.name}
            <button type="button" onclick="removeAttachment(this, '${file.name}')" class="ml-1 text-[#94a3b8] hover:text-red-500">×</button>
        `;
        preview.appendChild(badge);
    });
    
    toggleAttachMenu();
}

function removeAttachment(btn, filename) {
    tempAttachments = tempAttachments.filter(f => f.name !== filename);
    btn.parentElement.remove();
}

// Aggiungi destinatario
function addRecipient(email) {
    const input = document.getElementById('composeTo');
    if (input.value) {
        input.value += ', ' + email;
    } else {
        input.value = email;
    }
}

// Aggiorna progetti in base al cliente
function updateProgetti(clienteId) {
    const select = document.getElementById('composeProgetto');
    const options = select.querySelectorAll('option[data-cliente]');
    
    options.forEach(opt => {
        if (!clienteId || opt.dataset.cliente === clienteId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });
    
    select.value = '';
}

// Template risposta
function insertTemplate() {
    const templates = <?php echo json_encode($templateRisposte ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const choice = prompt('Scegli template:\n1. Rispondi breve\n2. Saluto formale\n3. Ricevuta in lavorazione');
    
    if (choice && templates && templates[choice - 1]) {
        const body = document.getElementById('composeBody');
        body.value = templates[choice - 1] + '\n\n' + body.value;
    }
}

// Invia email
async function sendEmail() {
    const form = document.getElementById('composeForm');
    const formData = new FormData(form);
    
    // Aggiungi allegati
    tempAttachments.forEach(file => {
        formData.append('attachments[]', file);
    });
    
    try {
        const response = await fetch('api/mail_simple_save.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Email inviata con successo!');
            window.location.href = '?folder=sent&account=' + formData.get('account_id');
        } else {
            alert('Errore: ' + result.message);
        }
    } catch (error) {
        alert('Errore durante l\'invio: ' + error.message);
    }
}

// Salva bozza
async function saveDraft() {
    const form = document.getElementById('composeForm');
    const formData = new FormData(form);
    formData.set('action', 'save_draft');
    
    try {
        const response = await fetch('api/mail.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Bozza salvata!');
        } else {
            alert('Errore: ' + result.message);
        }
    } catch (error) {
        alert('Errore: ' + error.message);
    }
}

// Selettori file
function openFileSelector(tipo) {
    document.getElementById('fileSelectorModal').classList.remove('hidden');
    document.getElementById('fileSelectorModal').classList.add('flex');
    document.getElementById('fileSelectorTitle').textContent = tipo === 'clienti' ? 'File Clienti' : 'File Progetti';
    
    // Carica contenuto
    fetch(`api/mail.php?action=list_files&tipo=${tipo}`)
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('fileSelectorContent');
            if (data.success && data.files.length > 0) {
                container.innerHTML = data.files.map(f => `
                    <div class="flex items-center gap-3 p-3 hover:bg-[#f8fafc] rounded-lg cursor-pointer" onclick="selectFile('${f.path}', '${f.name}')">
                        <svg class="w-8 h-8 text-[#9bc4d0]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div class="flex-1">
                            <div class="font-medium text-sm text-[#2d2d2d]">${f.name}</div>
                            <div class="text-xs text-[#94a3b8]">${f.size_formatted} • ${f.folder}</div>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="text-center text-[#94a3b8] py-8">Nessun file trovato</p>';
            }
        });
}

function closeFileSelector() {
    document.getElementById('fileSelectorModal').classList.add('hidden');
    document.getElementById('fileSelectorModal').classList.remove('flex');
}

function selectFile(path, name) {
    // Aggiungi alla lista allegati
    const preview = document.getElementById('attachmentPreview');
    const badge = document.createElement('span');
    badge.className = 'inline-flex items-center gap-1 px-2 py-1 bg-[#f0f9ff] text-[#334155] text-xs rounded border border-[#9bc4d0]';
    badge.innerHTML = `
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        ${name}
        <input type="hidden" name="existing_attachments[]" value="${path}">
        <button type="button" onclick="this.parentElement.remove()" class="ml-1 text-[#94a3b8] hover:text-red-500">×</button>
    `;
    preview.appendChild(badge);
    closeFileSelector();
}

// Selettore preventivi
function openPreventiviSelector() {
    document.getElementById('preventiviModal').classList.remove('hidden');
    document.getElementById('preventiviModal').classList.add('flex');
    
    fetch('api/mail.php?action=list_preventivi')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('preventiviContent');
            if (data.success && data.preventivi.length > 0) {
                container.innerHTML = data.preventivi.map(p => `
                    <div class="flex items-center gap-3 p-3 hover:bg-[#f8fafc] rounded-lg cursor-pointer border-b border-[#f1f5f9]" onclick="selectPreventivo('${p.id}', '${p.codice}')">
                        <svg class="w-8 h-8 text-[#9bc4d0]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 15.536c-1.171 1.952-3.07 1.952-4.242 0-1.172-1.953-1.172-5.119 0-7.072 1.171-1.952 3.07-1.952 4.242 0M8 10.5h4m-4 3h4m9-1.5a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div class="flex-1">
                            <div class="font-medium text-sm text-[#2d2d2d]">${p.codice}</div>
                            <div class="text-xs text-[#64748b]">${p.cliente_nome} - €${p.totale}</div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded ${p.stato === 'accettato' ? 'bg-green-100 text-green-700' : 'bg-[#e2e8f0] text-[#64748b]'}">${p.stato}</span>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p class="text-center text-[#94a3b8] py-8">Nessun preventivo trovato</p>';
            }
        });
}

function closePreventiviSelector() {
    document.getElementById('preventiviModal').classList.add('hidden');
    document.getElementById('preventiviModal').classList.remove('flex');
}

function selectPreventivo(id, codice) {
    const preview = document.getElementById('attachmentPreview');
    const badge = document.createElement('span');
    badge.className = 'inline-flex items-center gap-1 px-2 py-1 bg-[#c4b5d0] text-white text-xs rounded';
    badge.innerHTML = `
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 15.536c-1.171 1.952-3.07 1.952-4.242 0-1.172-1.953-1.172-5.119 0-7.072 1.171-1.952 3.07-1.952 4.242 0M8 10.5h4m-4 3h4m9-1.5a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        ${codice}
        <input type="hidden" name="preventivo_attachment" value="${id}">
        <button type="button" onclick="this.parentElement.remove()" class="ml-1 hover:text-white/70">×</button>
    `;
    preview.appendChild(badge);
    closePreventiviSelector();
}

// Associa cliente
async function associaCliente(messageId) {
    const clienteId = document.getElementById('associaClienteSelect').value;
    const progettoId = document.getElementById('associaProgettoSelect').value;
    
    if (!clienteId) {
        alert('Seleziona un cliente');
        return;
    }
    
    try {
        const response = await fetch('api/mail.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'associa_cliente',
                message_id: messageId,
                cliente_id: clienteId,
                progetto_id: progettoId,
                csrf_token: '<?php echo generateCsrfToken(); ?>'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Errore: ' + result.message);
        }
    } catch (error) {
        alert('Errore: ' + error.message);
    }
}

function rimuoviAssociazione(messageId) {
    if (!confirm('Rimuovere l\'associazione con questo cliente?')) return;
    
    fetch('api/mail.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'rimuovi_associazione',
            message_id: messageId,
            csrf_token: '<?php echo generateCsrfToken(); ?>'
        })
    }).then(() => location.reload());
}

// Azioni email
function toggleImportant(messageId) {
    fetch('api/mail.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'toggle_important',
            message_id: messageId,
            csrf_token: '<?php echo generateCsrfToken(); ?>'
        })
    }).then(() => location.reload());
}

function moveToTrash(messageId) {
    if (!confirm('Spostare nel cestino?')) return;
    
    fetch('api/mail.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'move_trash',
            message_id: messageId,
            csrf_token: '<?php echo generateCsrfToken(); ?>'
        })
    }).then(() => window.location.href = '?folder=inbox&account=<?php echo $accountId; ?>');
}

function markAllRead() {
    fetch('api/mail.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'mark_all_read',
            account_id: '<?php echo $accountId; ?>',
            folder: '<?php echo $folder; ?>',
            csrf_token: '<?php echo generateCsrfToken(); ?>'
        })
    }).then(() => location.reload());
}

function refreshEmails() {
    openSyncModal();
    
    fetch('api/mail.php?action=sync&account_id=<?php echo $accountId; ?>')
        .then(r => r.json())
        .then(result => {
            document.getElementById('syncProgress').style.width = '100%';
            document.getElementById('syncStatus').textContent = result.message || 'Completato!';
            setTimeout(() => {
                closeSyncModal();
                location.reload();
            }, 1000);
        })
        .catch(err => {
            document.getElementById('syncStatus').textContent = 'Errore: ' + err.message;
        });
}

// Sincronizzazione
function openSyncModal() {
    document.getElementById('syncModal').classList.remove('hidden');
    document.getElementById('syncModal').classList.add('flex');
    document.getElementById('syncProgress').style.width = '30%';
}

function closeSyncModal() {
    document.getElementById('syncModal').classList.add('hidden');
    document.getElementById('syncModal').classList.remove('flex');
}

// Impostazioni account
function fillProviderConfig(provider) {
    const configs = {
        gmail: { imap: 'imap.gmail.com', imapPort: 993, smtp: 'smtp.gmail.com', smtpPort: 587 },
        outlook: { imap: 'outlook.office365.com', imapPort: 993, smtp: 'smtp.office365.com', smtpPort: 587 },
        yahoo: { imap: 'imap.mail.yahoo.com', imapPort: 993, smtp: 'smtp.mail.yahoo.com', smtpPort: 587 },
        aruba: { imap: 'imaps.aruba.it', imapPort: 993, smtp: 'smtps.aruba.it', smtpPort: 465 },
        siteground: { imap: 'mail.etereastudio.it', imapPort: 993, smtp: 'mail.etereastudio.it', smtpPort: 465 }
    };
    
    const c = configs[provider];
    if (c) {
        document.querySelector('input[name="imap_server"]').value = c.imap;
        document.querySelector('input[name="imap_port"]').value = c.imapPort;
        document.querySelector('input[name="smtp_server"]').value = c.smtp;
        document.querySelector('select[name="smtp_port"]').value = c.smtpPort;
    }
}

// Gestione form impostazioni
document.getElementById('accountForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('api/mail.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Account salvato con successo!');
            location.reload();
        } else {
            alert('Errore: ' + result.message);
        }
    } catch (error) {
        alert('Errore: ' + error.message);
    }
});

function setDefaultAccount(accountId) {
    fetch('api/mail.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'set_default',
            account_id: accountId,
            csrf_token: '<?php echo generateCsrfToken(); ?>'
        })
    }).then(() => location.reload());
}

function deleteAccount(accountId) {
    if (!confirm('Eliminare questo account? Le email salvate non verranno eliminate.')) return;
    
    fetch('api/mail.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action: 'delete_account',
            account_id: accountId,
            csrf_token: '<?php echo generateCsrfToken(); ?>'
        })
    }).then(() => location.reload());
}

// Utility
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
