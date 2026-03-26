<?php
/**
 * Mail - Test con view completa
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentPage = 'mail';
$pageTitle = 'Mail';

// Setup DB
$pdo->exec("CREATE TABLE IF NOT EXISTS mail_accounts (id VARCHAR(20) PRIMARY KEY, utente_id VARCHAR(20), email VARCHAR(255), nome_visualizzato VARCHAR(255), attivo TINYINT DEFAULT 1, is_default TINYINT DEFAULT 0)");
$pdo->exec("CREATE TABLE IF NOT EXISTS mail_messages (id VARCHAR(20) PRIMARY KEY, account_id VARCHAR(20), cliente_id VARCHAR(20), cartella VARCHAR(50), mittente_email VARCHAR(255), oggetto VARCHAR(500), corpo_text TEXT, is_letta TINYINT DEFAULT 0, data_ricezione DATETIME)");

// Parametri
$folder = $_GET['folder'] ?? 'inbox';
$view = $_GET['view'] ?? 'list';
$userId = $_SESSION['user_id'] ?? '';

// Carica account
$stmt = $pdo->prepare("SELECT * FROM mail_accounts WHERE utente_id = ? AND attivo = 1");
$stmt->execute([$userId]);
$accounts = $stmt->fetchAll();

$hasAccounts = count($accounts) > 0;
$activeAccount = $accounts[0] ?? null;

// Carica clienti
$clienti = $pdo->query("SELECT id, nome, cognome, email FROM clienti WHERE email != '' LIMIT 50")->fetchAll();

// Carica messaggi se c'è account
$messages = [];
if ($activeAccount && $view === 'list') {
    $stmt = $pdo->prepare("SELECT * FROM mail_messages WHERE account_id = ? AND cartella = ? ORDER BY data_ricezione DESC LIMIT 50");
    $stmt->execute([$activeAccount['id'], $folder]);
    $messages = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="p-4">
    <h1 class="text-2xl font-bold mb-4">Mail</h1>
    
    <?php if (!$hasAccounts): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
        <h2 class="text-lg font-semibold text-amber-800">Nessun account configurato</h2>
        <a href="?view=settings" class="mt-4 inline-block px-6 py-2 bg-[#9bc4d0] text-[#2d2d2d] rounded-lg font-medium">Configura</a>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <h2 class="font-semibold">Email - <?php echo e($activeAccount['email']); ?></h2>
            <span class="text-sm text-slate-500"><?php echo count($messages); ?> messaggi</span>
        </div>
        <?php if (empty($messages)): ?>
        <div class="p-8 text-center text-slate-500">Nessuna email</div>
        <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($messages as $msg): ?>
            <div class="p-4 hover:bg-slate-50">
                <p class="font-medium"><?php echo e($msg['mittente_email'] ?? 'Sconosciuto'); ?></p>
                <p class="text-sm text-slate-600"><?php echo e($msg['oggetto'] ?? '(nessun oggetto)'); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
