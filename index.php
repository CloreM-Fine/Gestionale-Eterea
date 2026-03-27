<?php
/**
 * Eterea Gestionale
 * Login Page
 */

// Se già loggato, redirect a dashboard
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Recupera logo personalizzato
try {
    $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'logo_azienda'");
    $stmt->execute();
    $logoGestionale = $stmt->fetchColumn() ?: '';
} catch (PDOException $e) {
    $logoGestionale = '';
}

$error = $_GET['error'] ?? '';
$errorMsg = '';

switch ($error) {
    case 'session_expired':
        $errorMsg = 'Sessione scaduta. Effettua nuovamente il login.';
        break;
    case 'unauthorized':
        $errorMsg = 'Accesso non autorizzato.';
        break;
}

// Genera Token CSRF sicuro
require_once __DIR__ . '/includes/functions.php';
$csrfToken = generateCsrfTokenSecure();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Eterea Gestionale</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com" crossorigin="anonymous"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Palette Eterea Studio */
        :root {
            --eterea-bg: #f5f3ef;
            --eterea-azzurro: #9bc4d0;
            --eterea-verde: #a8b5a0;
            --eterea-lilla: #c4b5d0;
            --eterea-giallo: #e8e4b8;
            --eterea-pesca: #e8c4b8;
            --eterea-text: #1a1a1a;
            --eterea-dark: #2d2d2d;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #f5f3ef 0%, #ebe7e0 50%, #f0ece4 100%);
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.6);
        }
        
        .eterea-gradient {
            background: linear-gradient(135deg, #9bc4d0 0%, #c4b5d0 50%, #e8c4b8 100%);
        }
        
        .eterea-circles {
            background: linear-gradient(135deg, #9bc4d0, #a8b5a0, #c4b5d0, #e8e4b8, #e8c4b8);
            background-size: 300% 300%;
            animation: gradientShift 8s ease infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md mx-auto fade-in">
        <!-- Logo -->
        <div class="text-center mb-6">
            <?php if ($logoGestionale): ?>
                <div class="inline-flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24 rounded-2xl shadow-xl mb-4 overflow-hidden">
                    <img src="<?php echo e($logoGestionale); ?>?v=<?php echo time(); ?>" 
                         alt="Logo" class="w-full h-full object-contain p-2">
                </div>
            <?php else: ?>
                <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 eterea-circles rounded-2xl shadow-xl mb-4">
                    <span class="text-white font-bold text-xl sm:text-2xl drop-shadow-md">LDE</span>
                </div>
            <?php endif; ?>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Eterea Gestionale</h1>
            <p class="text-slate-500 mt-1 text-sm sm:text-base">Gestionale Progetti</p>
        </div>
        
        <!-- Login Card -->
        <div class="glass-card rounded-2xl shadow-2xl p-6 sm:p-8">
            <h2 class="text-base sm:text-lg font-semibold text-slate-800 mb-6 text-center">Accedi al sistema</h2>
            
            <?php if ($errorMsg): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm"><?php echo htmlspecialchars($errorMsg); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <form id="loginForm" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="action" value="login">
                
                <!-- Username -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Nome Utente
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            name="username" 
                            required
                            class="w-full pl-10 pr-4 py-4 text-base min-h-[48px] border border-slate-200 rounded-xl focus:ring-2 focus:ring-[#9bc4d0] focus:border-[#9bc4d0] transition-all outline-none"
                            placeholder="Inserisci il tuo nome"
                            autocomplete="username"
                        >
                    </div>
                </div>
                
                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <input 
                            type="password" 
                            name="password" 
                            required
                            class="w-full pl-10 pr-12 py-4 text-base min-h-[48px] border border-slate-200 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all outline-none"
                            placeholder="Inserisci la password"
                            autocomplete="current-password"
                        >
                        <button 
                            type="button" 
                            onclick="togglePassword()"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                        >
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Submit -->
                <button 
                    type="submit" 
                    id="submitBtn"
                    class="w-full bg-[#2d2d2d] text-white py-4 text-lg font-medium rounded-xl hover:bg-[#1a1a1a] focus:ring-4 focus:ring-[#e8c4b8]/50 transition-all flex items-center justify-center gap-2 shadow-lg"
                >
                    <span id="btnText">Accedi</span>
                    <svg id="btnSpinner" class="w-5 h-5 animate-spin hidden" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>
            
            <!-- Error Message Container -->
            <div id="errorContainer" class="hidden mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p id="errorText" class="text-red-600 text-sm text-center"></p>
            </div>
        </div>
        
        <!-- Footer -->
        <p class="text-center text-slate-400 text-xs sm:text-sm mt-6 sm:mt-8">
            © <?php echo date('Y'); ?> Eterea Gestionale. Tutti i diritti riservati.
        </p>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const input = document.querySelector('input[name="password"]');
            const icon = document.getElementById('eyeIcon');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            }
        }
        
        // Login form handler
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('submitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            const errorContainer = document.getElementById('errorContainer');
            const errorText = document.getElementById('errorText');
            
            // Reset error
            errorContainer.classList.add('hidden');
            
            // Loading state
            btn.disabled = true;
            btnText.textContent = 'Accesso in corso...';
            btnSpinner.classList.remove('hidden');
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Redirect
                    window.location.href = data.data.redirect || 'dashboard.php';
                } else {
                    // Show error
                    errorText.textContent = data.message || 'Errore durante il login';
                    errorContainer.classList.remove('hidden');
                    
                    // Shake animation
                    document.querySelector('.glass-card').classList.add('shake');
                    setTimeout(() => {
                        document.querySelector('.glass-card').classList.remove('shake');
                    }, 500);
                }
            } catch (error) {
                console.error('Login error:', error);
                errorText.textContent = 'Errore: ' + error.message;
                errorContainer.classList.remove('hidden');
            } finally {
                // Reset button
                btn.disabled = false;
                btnText.textContent = 'Accedi';
                btnSpinner.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
