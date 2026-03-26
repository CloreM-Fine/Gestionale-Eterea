<?php
/**
 * Mail - Versione Test
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$currentPage = 'mail';
$pageTitle = 'Mail';

// Test semplice
try {
    global $pdo;
    $test = $pdo->query("SELECT 1")->fetch();
    $dbStatus = "OK";
} catch (Exception $e) {
    $dbStatus = "ERRORE: " . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="p-8">
    <h1 class="text-2xl font-bold mb-4">Mail - Test</h1>
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <p>Database: <?php echo $dbStatus; ?></p>
        <p>User ID: <?php echo e($_SESSION['user_id'] ?? 'non loggato'); ?></p>
        <p class="mt-4">
            <a href="mail.php" class="text-blue-600 hover:underline">Vai a Mail completa →</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
