<?php
/**
 * Eterea Gestionale
 * Pagina pubblica per upload contenuti da parte dei clienti
 */

// Header anti-cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');

require_once __DIR__ . '/includes/functions.php';

// Avvia sessione per CSRF token (pagina pubblica ma serve sessione)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

$token = $_GET['token'] ?? '';
$error = '';
$success = isset($_GET['success']) && $_GET['success'] === '1';
$clienteNome = '';
$isValid = false;
$existingImages = [];
$justSubmitted = false;

// Verifica se è stato appena inviato (per non mostrare immagini precedenti)
if ($success) {
    $justSubmitted = true;
}

// Verifica token
if (!empty($token) && !$justSubmitted) {
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
            
            // Se ha già titolo, il link è già stato usato - nascondi immagini precedenti
            // per dare un'esperienza pulita al cliente che vuole caricare altro
            if (!empty($contenuto['titolo'])) {
                $existingImages = []; // Non mostrare immagini vecchie nel form
                if (count(json_decode($contenuto['immagini'] ?? '[]', true)) >= 10) {
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
} elseif ($justSubmitted) {
    // Dopo invio, mostra comunque il form vuoto con un nuovo link
    // Ma per sicurezza verifichiamo il token per mostrare il nome cliente
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
            // Non mostrare immagini esistenti dopo invio - form pulito
            $existingImages = [];
        }
    } catch (PDOException $e) {
        $error = 'Errore di sistema.';
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
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Editor WYSIWYG Styles */
        .editor-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-bottom: none;
            border-radius: 8px 8px 0 0;
        }
        .editor-toolbar button {
            padding: 6px 10px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .editor-toolbar button:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        .editor-toolbar button.active {
            background: #0891b2;
            color: white;
            border-color: #0891b2;
        }
        .editor-content {
            min-height: 200px;
            padding: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 0 0 8px 8px;
            outline: none;
            font-size: 14px;
            line-height: 1.6;
        }
        .editor-content:focus {
            border-color: #0891b2;
            box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
        }
        .editor-content h2 { font-size: 1.5em; font-weight: bold; margin: 1em 0 0.5em; }
        .editor-content h3 { font-size: 1.25em; font-weight: bold; margin: 1em 0 0.5em; }
        .editor-content p { margin: 0.5em 0; }
        .editor-content ul { list-style-type: disc; padding-left: 2em; margin: 0.5em 0; }
        .editor-content ol { list-style-type: decimal; padding-left: 2em; margin: 0.5em 0; }
        .editor-content blockquote { border-left: 4px solid #e2e8f0; padding-left: 1em; margin: 0.5em 0; color: #64748b; }
        .editor-content b, .editor-content strong { font-weight: bold; }
        .editor-content i, .editor-content em { font-style: italic; }
        .editor-content u { text-decoration: underline; }
        .editor-content s, .editor-content strike { text-decoration: line-through; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    
    <!-- Header -->
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 py-4">
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
    <main class="max-w-7xl mx-auto px-4 lg:px-8 py-8">
        
        <?php if (!empty($error)): ?>
        <!-- Errore -->
        <div class="max-w-2xl mx-auto bg-red-50 border border-red-200 rounded-xl p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-red-800 mb-2">Errore</h2>
            <p class="text-red-600"><?php echo e($error); ?></p>
        </div>
        
        <?php elseif ($success): ?>
        <!-- Successo -->
        <div class="max-w-2xl mx-auto bg-emerald-50 border border-emerald-200 rounded-xl p-6 text-center">
            <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-emerald-800 mb-2">Contenuto inviato!</h2>
            <p class="text-emerald-600 mb-4">Grazie! I tuoi contenuti sono stati inviati con successo allo studio.</p>
            <a href="?token=<?php echo e($token); ?>" class="inline-block px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                Carica altro
            </a>
        </div>
        
        <?php elseif ($isValid): ?>
        <!-- Layout a due colonne: form largo, assistenza stretta -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <!-- Colonna sinistra: Form (più larga) -->
            <div class="lg:col-span-4">
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
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Nome e cognome autore *</label>
                            <input type="text" name="autore" required
                                   class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                                   placeholder="Es: Mario Rossi">
                        </div>
                
                        <!-- Titolo -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Titolo *</label>
                            <input type="text" name="titolo" required
                                   class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                                   placeholder="Es: Foto evento aziendale, Materiale per campagna...">
                        </div>
                        
                        <!-- Testo con Editor WYSIWYG -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Descrizione</label>
                            
                            <!-- Toolbar Editor -->
                            <div class="editor-toolbar">
                                <button type="button" data-command="bold" title="Grassetto (Ctrl+B)">
                                    <b>B</b>
                                </button>
                                <button type="button" data-command="italic" title="Corsivo (Ctrl+I)">
                                    <i>I</i>
                                </button>
                                <button type="button" data-command="underline" title="Sottolineato (Ctrl+U)">
                                    <u>U</u>
                                </button>
                                <button type="button" data-command="strikeThrough" title="Barrato">
                                    <s>S</s>
                                </button>
                                <span class="w-px h-6 bg-slate-300 mx-1"></span>
                                <button type="button" data-command="formatBlock" data-value="H2" title="Titolo grande">
                                    H1
                                </button>
                                <button type="button" data-command="formatBlock" data-value="H3" title="Sottotitolo">
                                    H2
                                </button>
                                <span class="w-px h-6 bg-slate-300 mx-1"></span>
                                <button type="button" data-command="insertUnorderedList" title="Elenco puntato">
                                    • Lista
                                </button>
                                <button type="button" data-command="insertOrderedList" title="Elenco numerato">
                                    1. Lista
                                </button>
                                <span class="w-px h-6 bg-slate-300 mx-1"></span>
                                <button type="button" data-command="justifyLeft" title="Allinea a sinistra">
                                    ⬅️
                                </button>
                                <button type="button" data-command="justifyCenter" title="Centra">
                                    ↔️
                                </button>
                                <button type="button" data-command="justifyRight" title="Allinea a destra">
                                    ➡️
                                </button>
                                <span class="w-px h-6 bg-slate-300 mx-1"></span>
                                <button type="button" data-command="removeFormat" title="Rimuovi formattazione">
                                    🧹
                                </button>
                            </div>
                            
                            <!-- Editor Content -->
                            <div id="richTextEditor" class="editor-content" contenteditable="true" 
                                 placeholder="Descrivi brevemente il contenuto che stai caricando...">
                            </div>
                            
                            <!-- Hidden textarea per inviare il contenuto -->
                            <textarea name="testo" id="testoHidden" class="hidden"></textarea>
                        </div>
                
                <!-- Immagini Esistenti -->
                <?php if (!empty($existingImages)): ?>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Immagini già caricate (<?php echo count($existingImages); ?>/10)</label>
                    <div class="grid grid-cols-5 gap-2">
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
                    <div id="previewContainer" class="grid grid-cols-5 gap-2 mt-4 hidden"></div>
                    
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
            </div>
            
            <!-- Colonna destra: Assistenza (più stretta) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden sticky top-4">
                    <div class="p-4 border-b border-slate-100 bg-cyan-50">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Assistenza
                        </h3>
                    </div>
                    <div class="p-4 space-y-3">
                        <p class="text-xs text-slate-600">
                            Hai bisogno di aiuto? Contatta lo studio.
                        </p>
                        
                        <!-- Telefono -->
                        <a href="tel:+393465728606" class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                            <div class="w-8 h-8 bg-cyan-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Telefono</p>
                                <p class="font-medium text-slate-800 text-sm">346 572 8606</p>
                            </div>
                        </a>
                        
                        <!-- WhatsApp -->
                        <a href="https://wa.me/393465728606" target="_blank" rel="noopener noreferrer" 
                           class="flex items-center gap-2 p-2 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition-colors">
                            <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">WhatsApp</p>
                                <p class="font-medium text-emerald-700 text-sm">Scrivimi</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </main>
    
    <!-- Footer -->
    <footer class="max-w-7xl mx-auto px-4 py-6 text-center">
        <p class="text-sm text-slate-400">© <?php echo date('Y'); ?> Eterea Studio - Tutti i diritti riservati</p>
    </footer>
    
    <script>
    let selectedFiles = [];
    const maxFiles = <?php echo $remaining ?? 10; ?>;
    
    // ========== EDITOR WYSIWYG ==========
    const editor = document.getElementById('richTextEditor');
    const hiddenTextarea = document.getElementById('testoHidden');
    const toolbarButtons = document.querySelectorAll('.editor-toolbar button');
    
    // Aggiorna textarea nascosta quando l'editor cambia
    editor.addEventListener('input', () => {
        hiddenTextarea.value = editor.innerHTML;
    });
    
    // Gestione pulsanti toolbar
    toolbarButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const command = button.dataset.command;
            const value = button.dataset.value || null;
            
            document.execCommand(command, false, value);
            editor.focus();
            
            // Aggiorna stato pulsanti
            updateToolbarState();
            hiddenTextarea.value = editor.innerHTML;
        });
    });
    
    // Aggiorna stato pulsanti (active/inactive)
    function updateToolbarState() {
        toolbarButtons.forEach(button => {
            const command = button.dataset.command;
            if (document.queryCommandState(command)) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }
    
    // Aggiorna stato quando il cursore si muove
    editor.addEventListener('keyup', updateToolbarState);
    editor.addEventListener('mouseup', updateToolbarState);
    editor.addEventListener('click', updateToolbarState);
    
    // Gestione incolla: mantieni formattazione base ma pulisci stili esterni
    editor.addEventListener('paste', (e) => {
        e.preventDefault();
        
        // Ottieni il testo HTML dagli appunti
        let html = '';
        if (e.clipboardData && e.clipboardData.getData) {
            html = e.clipboardData.getData('text/html') || e.clipboardData.getData('text/plain');
        } else if (window.clipboardData && window.clipboardData.getData) {
            html = window.clipboardData.getData('Text');
        }
        
        // Pulisci HTML mantenendo solo tag consentiti
        const allowedTags = ['b', 'i', 'u', 'strong', 'em', 'p', 'br', 'h1', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote'];
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Funzione ricorsiva per pulire i nodi
        function cleanNode(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                return node.textContent;
            }
            if (node.nodeType === Node.ELEMENT_NODE) {
                const tagName = node.tagName.toLowerCase();
                if (allowedTags.includes(tagName)) {
                    const cleaned = document.createElement(tagName);
                    // Copia solo il testo interno, ignora stili inline
                    Array.from(node.childNodes).forEach(child => {
                        const cleanedChild = cleanNode(child);
                        if (cleanedChild) {
                            if (typeof cleanedChild === 'string') {
                                cleaned.appendChild(document.createTextNode(cleanedChild));
                            } else {
                                cleaned.appendChild(cleanedChild);
                            }
                        }
                    });
                    return cleaned;
                } else {
                    // Tag non consentito: estrai solo il testo
                    return node.textContent;
                }
            }
            return '';
        }
        
        const fragment = document.createDocumentFragment();
        Array.from(tempDiv.childNodes).forEach(node => {
            const cleaned = cleanNode(node);
            if (cleaned) {
                if (typeof cleaned === 'string') {
                    fragment.appendChild(document.createTextNode(cleaned));
                } else {
                    fragment.appendChild(cleaned);
                }
            }
        });
        
        // Inserisci il contenuto pulito
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            range.insertNode(fragment);
            
            // Sposta cursore dopo il contenuto incollato
            range.collapse(false);
            selection.removeAllRanges();
            selection.addRange(range);
        }
        
        hiddenTextarea.value = editor.innerHTML;
        updateToolbarState();
    });
    
    // Shortcuts tastiera
    editor.addEventListener('keydown', (e) => {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key.toLowerCase()) {
                case 'b':
                    e.preventDefault();
                    document.execCommand('bold', false, null);
                    break;
                case 'i':
                    e.preventDefault();
                    document.execCommand('italic', false, null);
                    break;
                case 'u':
                    e.preventDefault();
                    document.execCommand('underline', false, null);
                    break;
            }
        }
        hiddenTextarea.value = editor.innerHTML;
    });
    
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
        
        // Aggiorna textarea nascosta con contenuto editor
        hiddenTextarea.value = editor.innerHTML;
        
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
                // Reindirizza a pagina di successo (form pulito)
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
