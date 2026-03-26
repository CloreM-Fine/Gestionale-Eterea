<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';
?><!DOCTYPE html>
<html>
<head>
    <title>Mail Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-4">Mail - Versione Minimale</h1>
        <p class="text-green-600">✅ Se vedi questo messaggio, il PHP funziona!</p>
        <p class="mt-4 text-gray-600">Utente: <?php echo e($_SESSION['user_id'] ?? 'non loggato'); ?></p>
        <div class="mt-6 flex gap-4">
            <a href="mail.php" class="px-4 py-2 bg-blue-500 text-white rounded">Vai a Mail Completa</a>
            <a href="dashboard.php" class="px-4 py-2 bg-gray-500 text-white rounded">Dashboard</a>
        </div>
    </div>
</body>
</html>
