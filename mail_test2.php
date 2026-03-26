<?php
/**
 * Mail - Test step by step
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentPage = 'mail';
$pageTitle = 'Mail';

// Step 1: Crea tabelle
try {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS mail_accounts (
        id VARCHAR(20) PRIMARY KEY, utente_id VARCHAR(20) NOT NULL, email VARCHAR(255),
        nome_visualizzato VARCHAR(255), imap_server VARCHAR(255), imap_port INT DEFAULT 993,
        imap_ssl TINYINT DEFAULT 1, imap_username VARCHAR(255), imap_password TEXT,
        smtp_server VARCHAR(255), smtp_port INT DEFAULT 587, smtp_ssl TINYINT DEFAULT 1,
        smtp_username VARCHAR(255), smtp_password TEXT, is_default TINYINT DEFAULT 0,
        attivo TINYINT DEFAULT 1, ultima_sincronizzazione DATETIME,
        creato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS mail_messages (
        id VARCHAR(20) PRIMARY KEY, account_id VARCHAR(20), message_id VARCHAR(500),
        cliente_id VARCHAR(20), progetto_id VARCHAR(20), cartella VARCHAR(50) DEFAULT 'inbox',
        mittente_email VARCHAR(255), mittente_nome VARCHAR(255), destinatari TEXT,
        oggetto VARCHAR(500), corpo_text TEXT, corpo_html LONGTEXT,
        data_ricezione DATETIME, data_invio DATETIME, is_letta TINYINT DEFAULT 0,
        is_inviata TINYINT DEFAULT 0, is_bozza TINYINT DEFAULT 0,
        is_importante TINYINT DEFAULT 0, is_spam TINYINT DEFAULT 0,
        is_cestinata TINYINT DEFAULT 0, has_allegati TINYINT DEFAULT 0,
        allegati_count INT DEFAULT 0, creato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS mail_attachments (
        id VARCHAR(20) PRIMARY KEY, message_id VARCHAR(20), filename VARCHAR(255),
        original_filename VARCHAR(255), mime_type VARCHAR(100), file_size INT,
        file_path VARCHAR(500), creato_il TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
} catch (Exception $e) {
    die("DB Error: " . $e->getMessage());
}

// Step 2: Carica account
$accounts = [];
$userId = $_SESSION['user_id'] ?? '';
try {
    $stmt = $pdo->prepare("SELECT * FROM mail_accounts WHERE utente_id = ? AND attivo = 1 ORDER BY is_default DESC");
    $stmt->execute([$userId]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Account Error: " . $e->getMessage());
}

// Step 3: Clienti
$clienti = [];
try {
    $stmt = $pdo->query("SELECT id, nome, cognome, email, azienda FROM clienti WHERE email IS NOT NULL AND email != '' ORDER BY nome");
    $clienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Ignora
}

$hasAccounts = count($accounts) > 0;

require_once __DIR__ . '/includes/header.php';
?>

<div class="p-6">
    <h1 class="text-2xl font-bold text-slate-800 mb-4">Mail - Test</h1>
    
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <h2 class="font-semibold mb-2">Stato</h2>
        <p>Utente: <?php echo e($userId); ?></p>
        <p>Account configurati: <?php echo count($accounts); ?></p>
        <p>Clienti con email: <?php echo count($clienti); ?></p>
    </div>
    
    <?php if (!$hasAccounts): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
        <h2 class="text-lg font-semibold text-amber-800 mb-2">Nessun account configurato</h2>
        <p class="text-amber-700">Configura il tuo primo account email.</p>
    </div>
    <?php else: ?>
    <div class="bg-green-50 border border-green-200 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-green-800 mb-2">Account trovati</h2>
        <?php foreach ($accounts as $acc): ?>
        <p class="text-green-700"><?php echo e($acc['email']); ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
