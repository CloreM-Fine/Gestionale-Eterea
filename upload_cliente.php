<?php
/**
 * Eterea Gestionale
 * Pagina pubblica per upload contenuti da parte dei clienti
 */

require_once __DIR__ . '/includes/functions.php';

// Avvia sessione per CSRF token (pagina pubblica ma serve sessione)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$clienteNome = '';
$isValid = false;
$existingImages = [];

// Verifica token
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, cl.ragione_sociale as cliente_nome
            FROM cliente_contenuti c
            LEFT JOIN clienti cl ON c.cliente_id = cl.id
            WHERE c.token = ? AND c.stato = 'attivo'
        ");
        $stmt->execute([$token]);
        $contenuto = $stmt->fetch();
        
        if ($contenuto) {
            $isValid = true;
            $clienteNome = $contenuto['cliente_nome'] ?? 'Cliente';
            $existingImages = json_decode($contenuto['immagini'] ?? '[]', true);
            
            // Se ha già titolo, il link è già stato usato
            if (!empty($contenuto['titolo'])) {
                // Permetti aggiunta immagini se < 10
                if (count($existingImages) >= 10) {
                    $error = 'Hai già caricato il numero massimo di immagini. Contatta lo studio per ulteriori informazioni.';
                    $isValid = false;
                }
            }
        } else {
            $error = 'Link non valido o scaduto.';
        }
    } catch (PDOException $e) {
        error_log("Errore verifica token: " . $e->getMessage());
        $error = 'Errore di sistema. Riprova più tardi.';
    }
} else {
    $error = 'Link mancante.';
}

// CSRF token per il form
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <title>Carica Contenuti - Eterea Studio</title>
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16x16.png">
    <link rel="manifest" href="assets/favicons/site.webmanifest">
    <link rel="shortcut icon" href="assets/favicons/favicon.ico">
    
    <!-- Open Graph / Meta per condivisione link -->
    <meta property="og:title" content="Carica Contenuti - Eterea Studio">
    <meta property="og:description" content="Carica qui le immagini e il testo per il tuo progetto con Eterea Studio">
    <meta property="og:image" content="<?php echo BASE_URL; ?>/assets/favicons/android-chrome-512x512.png">
    <meta property="og:url" content="<?php echo BASE_URL . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Carica Contenuti - Eterea Studio">
    <meta name="twitter:description" content="Carica qui le immagini e il testo per il tuo progetto con Eterea Studio">
    <meta name="twitter:image" content="<?php echo BASE_URL; ?>/assets/favicons/android-chrome-512x512.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen">
    
    <!-- Header -->
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-2xl lg:max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center gap-3">
                <img src="assets/favicons/apple-touch-icon.png" alt="Eterea Studio" class="w-10 h-10 rounded-xl">
                <div>
                    <h1 class="font-bold text-slate-800">Eterea Studio</h1>
                    <p class="text-xs text-slate-500">Carica contenuti</p>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="max-w-2xl lg:max-w-6xl mx-auto px-4 lg:px-8 py-8">
        
        <?php if (!empty($error)): ?>
        <!-- Errore -->
        <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-red-800 mb-2">Errore</h2>
            <p class="text-red-600"><?php echo e($error); ?></p>
        </div>
        
        <?php elseif (!empty($success)): ?>
        <!-- Successo -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-6 text-center">
            <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-emerald-800 mb-2">Contenuto inviato!</h2>
            <p class="text-emerald-600 mb-4">Grazie! I tuoi contenuti sono stati inviati con successo allo studio.</p>
            <button onclick="location.reload()" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                Carica altro
            </button>
        </div>
        
        <?php elseif ($isValid): ?>
        <!-- Form Upload -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h2 class="text-xl font-bold text-slate-800">Ciao <?php echo e($clienteNome); ?>!</h2>
                <p class="text-slate-500 mt-1">Carica qui le immagini e il testo per il tuo progetto.</p>
            </div>
            
            <form id="uploadForm" class="p-6 space-y-6">
                <input type="hidden" name="action" value="upload_contenuto">
                <input type="hidden" name="token" value="<?php echo e($token); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <!-- Autore -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nome *</label>
                        <input type="text" name="autore_nome" required
                               class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                               placeholder="Il tuo nome">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Cognome *</label>
                        <input type="text" name="autore_cognome" required
                               class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                               placeholder="Il tuo cognome">
                    </div>
                </div>
                
                <!-- Titolo -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Titolo *</label>
                    <input type="text" name="titolo" required
                           class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                           placeholder="Es: Foto evento aziendale, Materiale per campagna...">
                </div>
                
                <!-- Testo -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Descrizione</label>
                    <textarea name="testo" rows="4"
                              class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none"
                              placeholder="Descrivi brevemente il contenuto che stai caricando..."></textarea>
                </div>
                
                <!-- Immagini Esistenti -->
                <?php if (!empty($existingImages)): ?>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Immagini già caricate (<?php echo count($existingImages); ?>/10)</label>
                    <div class="grid grid-cols-4 gap-2">
                        <?php foreach ($existingImages as $img): ?>
                        <div class="aspect-square rounded-lg overflow-hidden bg-slate-100">
                            <img src="assets/uploads/clienti_contenuti/<?php echo e($img); ?>" alt="" class="w-full h-full object-cover">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Upload Nuove Immagini -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Carica nuove immagini 
                        <?php $remaining = 10 - count($existingImages); ?>
                        <span class="text-slate-400">(max <?php echo $remaining; ?> rimanenti)</span>
                    </label>
                    
                    <div id="dropZone" class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-cyan-500 hover:bg-cyan-50 transition-colors cursor-pointer"
                         onclick="document.getElementById('fileInput').click()">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-slate-600 font-medium">Clicca o trascina qui le immagini</p>
                        <p class="text-sm text-slate-400 mt-1">JPG, PNG, WEBP - Max 8MB ciascuna</p>
                    </div>
                    
                    <input type="file" id="fileInput" name="immagini[]" multiple accept="image/jpeg,image/png,image/webp,image/jpg"
                           class="hidden" onchange="handleFileSelect(this)">
                    
                    <!-- Preview -->
                    <div id="previewContainer" class="grid grid-cols-4 gap-2 mt-4 hidden"></div>
                    
                    <p id="fileError" class="text-sm text-red-500 mt-2 hidden"></p>
                </div>
                
                <!-- Info -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <p class="text-sm text-amber-800">
                        <strong>Nota:</strong> Puoi caricare fino a 10 immagini in totale. 
                        Formati accettati: JPG, PNG, WEBP.
                    </p>
                </div>
                
                <!-- Submit -->
                <button type="submit" id="submitBtn"
                        class="w-full py-3 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium flex items-center justify-center gap-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Invia Contenuti
                </button>
            </form>
        </div>
        <?php endif; ?>
        
    </main>
    
    <!-- Footer -->
    <footer class="max-w-2xl lg:max-w-7xl mx-auto px-4 py-6 text-center">
        <p class="text-sm text-slate-400">© <?php echo date('Y'); ?> Eterea Studio - Tutti i diritti riservati</p>
    </footer>
    
    <script>
    let selectedFiles = [];
    const maxFiles = <?php echo $remaining ?? 10; ?>;
    
    function handleFileSelect(input) {
        const files = Array.from(input.files);
        const errorEl = document.getElementById('fileError');
        const previewContainer = document.getElementById('previewContainer');
        
        errorEl.classList.add('hidden');
        
        // Verifica numero file
        if (files.length > maxFiles) {
            errorEl.textContent = `Puoi caricare al massimo ${maxFiles} immagini`;
            errorEl.classList.remove('hidden');
            input.value = '';
            return;
        }
        
        selectedFiles = files;
        
        // Mostra preview
        if (files.length > 0) {
            previewContainer.classList.remove('hidden');
            previewContainer.innerHTML = '';
            
            files.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'aspect-square rounded-lg overflow-hidden bg-slate-100 relative';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="" class="w-full h-full object-cover">
                        <button type="button" onclick="removeFile(${index})" 
                                class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs hover:bg-red-600">
                            ×
                        </button>
                    `;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        } else {
            previewContainer.classList.add('hidden');
        }
    }
    
    function removeFile(index) {
        selectedFiles.splice(index, 1);
        
        // Ricostruisci input
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        document.getElementById('fileInput').files = dt.files;
        
        // Aggiorna preview
        handleFileSelect(document.getElementById('fileInput'));
    }
    
    // Drag and drop
    const dropZone = document.getElementById('dropZone');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('border-cyan-500', 'bg-cyan-50');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('border-cyan-500', 'bg-cyan-50');
        }, false);
    });
    
    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        document.getElementById('fileInput').files = files;
        handleFileSelect(document.getElementById('fileInput'));
    }, false);
    
    // Submit form
    document.getElementById('uploadForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = document.getElementById('submitBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<div class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full"></div> Invio in corso...';
        
        try {
            const formData = new FormData(e.target);
            
            const response = await fetch('api/blog_clienti.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Reindirizza a pagina di successo (senza mostrare immagini precedenti)
                window.location.href = window.location.pathname + '?token=<?php echo e($token); ?>&success=1';
            } else {
                alert(data.message || 'Errore durante l\'invio');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Errore:', error);
            alert('Errore di connessione. Riprova.');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
    </script>
    
</body>
</html>
