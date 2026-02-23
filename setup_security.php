<?php
/**
 * ETEREA GESTIONALE - Setup Sicurezza Automatico
 * 
 * Eseguire una volta dopo il deploy, poi eliminare questo file
 * URL: https://gestionale.etereastudio.it/setup_security.php
 */

// Impedisci esecuzione se già configurato
if (file_exists(__DIR__ . '/.env') && !isset($_GET['force'])) {
    die('<h2>✅ Setup già completato!</h2><p>Il file .env esiste già.</p><p>Per forzare la riconfigurazione aggiungi ?force=1 all\'URL</p>');
}

$step = $_GET['step'] ?? 'welcome';
$messages = [];
$errors = [];

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Sicurezza - Eterea Gestionale</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #f8fafc 0%, #ecfeff 50%, #f0f9ff 100%); }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-800">Setup Sicurezza Enterprise</h1>
                <p class="text-slate-500">Configurazione automatica hardening sicurezza</p>
            </div>

            <?php if ($step === 'welcome'): ?>
                <div class="space-y-6">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <h3 class="font-semibold text-amber-800 mb-2">⚠️ Importante</h3>
                        <ul class="text-sm text-amber-700 space-y-1">
                            <li>• Questo script configurerà il file .env con le credenziali</li>
                            <li>• Verranno generate chiavi crittografiche sicure</li>
                            <li>• Dopo il setup, <strong>elimina questo file</strong> per sicurezza</li>
                        </ul>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-4">
                        <h3 class="font-semibold text-slate-800 mb-2">Cosa verrà configurato:</h3>
                        <ul class="text-sm text-slate-600 space-y-1">
                            <li>✅ File .env con password database</li>
                            <li>✅ Hash password sezione Tasse</li>
                            <li>✅ Chiavi CSRF e crittografia</li>
                            <li>✅ Directory logs con permessi corretti</li>
                            <li>✅ Verifica configurazione sicurezza</li>
                        </ul>
                    </div>

                    <a href="?step=configure" class="block w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium text-center transition-colors">
                        Inizia Configurazione
                    </a>
                </div>

            <?php elseif ($step === 'configure'): ?>
                <?php
                // Genera chiavi sicure
                $csrfSecret = bin2hex(random_bytes(32));
                $encryptionKey = base64_encode(random_bytes(32));
                
                // Password Tasse (da modificare se diversa)
                $tassePassword = 'Tomato2399!?';
                $tasseHash = password_hash($tassePassword, PASSWORD_BCRYPT);
                
                // Database credentials (da SiteGround)
                $dbHost = 'localhost';
                $dbName = 'db4qhf5gnmj3lz';
                $dbUser = 'ucwurog3xr8tf';
                $dbPass = 'Lorenzo2026!';
                
                // Crea contenuto .env
                $envContent = <<<ENV
# =====================================================
# ETEREA GESTIONALE - CONFIGURAZIONE AMBIENTE
# Generato automaticamente: {date('Y-m-d H:i:s')}
# =====================================================

# -----------------------------------------------------
# DATABASE - SiteGround
# -----------------------------------------------------
DB_HOST={$dbHost}
DB_NAME={$dbName}
DB_USER={$dbUser}
DB_PASS={$dbPass}

# -----------------------------------------------------
# SICUREZZA - Password e Chiavi
# -----------------------------------------------------
TASSE_PASSWORD_HASH={$tasseHash}
CSRF_SECRET_KEY={$csrfSecret}
ENCRYPTION_KEY={$encryptionKey}

# -----------------------------------------------------
# API ESTERNE
# -----------------------------------------------------
OPENAI_API_KEY=

# -----------------------------------------------------
# CONFIGURAZIONE APPLICAZIONE
# -----------------------------------------------------
APP_ENV=production
BASE_URL=https://gestionale.etereastudio.it
APP_DEBUG=false

# -----------------------------------------------------
# SESSIONE E SICUREZZA
# -----------------------------------------------------
SESSION_LIFETIME=7200
COOKIE_LIFETIME=2592000
MAX_LOGIN_ATTEMPTS=5
LOGIN_LOCKOUT_MINUTES=15

# -----------------------------------------------------
# UPLOAD E FILE
# -----------------------------------------------------
MAX_UPLOAD_SIZE_MB=10
ALLOWED_UPLOAD_TYPES=application/pdf,image/jpeg,image/png,image/webp
ENV;

                // Scrivi file .env
                $envPath = __DIR__ . '/.env';
                if (file_put_contents($envPath, $envContent)) {
                    chmod($envPath, 0640);
                    $messages[] = "File .env creato con successo";
                } else {
                    $errors[] = "Impossibile creare il file .env - verifica i permessi";
                }

                // Crea directory logs
                $logsDir = __DIR__ . '/logs';
                if (!is_dir($logsDir)) {
                    if (mkdir($logsDir, 0750, true)) {
                        $messages[] = "Directory logs/ creata";
                    } else {
                        $errors[] = "Impossibile creare directory logs/";
                    }
                }

                // Proteggi logs con .htaccess
                $htaccessContent = "Order deny,allow\nDeny from all\n";
                $htaccessPath = $logsDir . '/.htaccess';
                if (file_put_contents($htaccessPath, $htaccessContent)) {
                    $messages[] = "Protezione .htaccess su logs/ creata";
                }

                // Verifica caricamento env
                require_once __DIR__ . '/includes/env_loader.php';
                loadEnv($envPath);
                
                if (env('DB_PASS') === $dbPass) {
                    $messages[] = "Test caricamento .env: OK";
                } else {
                    $errors[] = "Test caricamento .env: FALLITO";
                }
                ?>

                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-slate-800">Risultato Configurazione</h2>
                    
                    <?php if (!empty($messages)): ?>
                        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                            <h3 class="font-semibold text-emerald-800 mb-2">✅ Completato</h3>
                            <ul class="text-sm text-emerald-700 space-y-1">
                                <?php foreach ($messages as $msg): ?>
                                    <li>• <?= htmlspecialchars($msg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                            <h3 class="font-semibold text-red-800 mb-2">❌ Errori</h3>
                            <ul class="text-sm text-red-700 space-y-1">
                                <?php foreach ($errors as $err): ?>
                                    <li>• <?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <h3 class="font-semibold text-blue-800 mb-2">🔐 Chiavi Generate</h3>
                        <div class="text-xs text-blue-700 space-y-1 font-mono">
                            <p>CSRF Secret: <?= substr($csrfSecret, 0, 16) ?>...</p>
                            <p>Encryption: <?= substr($encryptionKey, 0, 16) ?>...</p>
                            <p>Tasse Hash: <?= substr($tasseHash, 0, 30) ?>...</p>
                        </div>
                    </div>

                    <a href="?step=verify" class="block w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium text-center transition-colors">
                        Verifica Configurazione
                    </a>
                </div>

            <?php elseif ($step === 'verify'): ?>
                <?php
                require_once __DIR__ . '/includes/env_loader.php';
                loadEnv(__DIR__ . '/.env');
                
                $checks = [];
                
                // Check 1: .env esiste
                $checks[] = [
                    'name' => 'File .env esiste',
                    'status' => file_exists(__DIR__ . '/.env'),
                    'message' => file_exists(__DIR__ . '/.env') ? 'OK' : 'File mancante'
                ];
                
                // Check 2: DB configurato
                $checks[] = [
                    'name' => 'Database configurato',
                    'status' => !empty(env('DB_PASS')),
                    'message' => !empty(env('DB_PASS')) ? 'OK' : 'Password DB mancante'
                ];
                
                // Check 3: CSRF configurato
                $checks[] = [
                    'name' => 'CSRF Secret configurato',
                    'status' => !empty(env('CSRF_SECRET_KEY')),
                    'message' => !empty(env('CSRF_SECRET_KEY')) ? 'OK' : 'Chiave mancante'
                ];
                
                // Check 4: Tasse password
                $checks[] = [
                    'name' => 'Password Tasse hashata',
                    'status' => !empty(env('TASSE_PASSWORD_HASH')),
                    'message' => !empty(env('TASSE_PASSWORD_HASH')) ? 'OK' : 'Hash mancante'
                ];
                
                // Check 5: logs directory
                $checks[] = [
                    'name' => 'Directory logs protetta',
                    'status' => is_dir(__DIR__ . '/logs') && file_exists(__DIR__ . '/logs/.htaccess'),
                    'message' => is_dir(__DIR__ . '/logs') ? 'OK' : 'Directory mancante'
                ];
                
                // Check 6: Test connessione DB
                try {
                    require_once __DIR__ . '/includes/config.php';
                    $checks[] = [
                        'name' => 'Connessione Database',
                        'status' => true,
                        'message' => 'OK - Connesso a ' . DB_NAME
                    ];
                } catch (Exception $e) {
                    $checks[] = [
                        'name' => 'Connessione Database',
                        'status' => false,
                        'message' => 'Errore: ' . $e->getMessage()
                    ];
                }
                
                $allOk = array_reduce($checks, function($carry, $check) {
                    return $carry && $check['status'];
                }, true);
                ?>

                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-slate-800">Verifica Configurazione</h2>
                    
                    <div class="space-y-2">
                        <?php foreach ($checks as $check): ?>
                            <div class="flex items-center justify-between p-3 rounded-lg <?= $check['status'] ? 'bg-emerald-50' : 'bg-red-50' ?>">
                                <span class="text-sm font-medium <?= $check['status'] ? 'text-emerald-800' : 'text-red-800' ?>">
                                    <?= $check['status'] ? '✅' : '❌' ?> <?= htmlspecialchars($check['name']) ?>
                                </span>
                                <span class="text-xs <?= $check['status'] ? 'text-emerald-600' : 'text-red-600' ?>">
                                    <?= htmlspecialchars($check['message']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($allOk): ?>
                        <div class="bg-emerald-100 border border-emerald-300 rounded-xl p-4 text-center">
                            <h3 class="font-bold text-emerald-800 mb-2">🎉 Setup Completato!</h3>
                            <p class="text-sm text-emerald-700">Tutti i controlli sono passati.</p>
                        </div>

                        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                            <h3 class="font-semibold text-red-800 mb-2">⚠️ AZIONE RICHIESTA</h3>
                            <p class="text-sm text-red-700 mb-2">
                                <strong>Elimina immediatamente questo file per sicurezza:</strong>
                            </p>
                            <code class="block bg-slate-800 text-slate-200 p-2 rounded text-xs">
                                rm <?= basename(__FILE__) ?>
                            </code>
                        </div>

                        <a href="index.php" class="block w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium text-center transition-colors">
                            Vai al Login
                        </a>
                    <?php else: ?>
                        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                            <h3 class="font-semibold text-amber-800">⚠️ Alcuni controlli sono falliti</h3>
                            <p class="text-sm text-amber-700">Verifica gli errori sopra e riprova.</p>
                        </div>
                        <a href="?step=configure" class="block w-full py-3 bg-slate-600 hover:bg-slate-700 text-white rounded-xl font-medium text-center transition-colors">
                            Riprova Configurazione
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
