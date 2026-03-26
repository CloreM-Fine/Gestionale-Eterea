<?php
/**
 * Eterea Gestionale
 * Piano Editoriale - Gestione Social Media
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Piano Editoriale';

// Recupera progetti con gestione social attiva
try {
    $stmt = $pdo->query("
        SELECT p.*, c.ragione_sociale as cliente_nome 
        FROM progetti p
        LEFT JOIN clienti c ON p.cliente_id = c.id
        WHERE p.gestione_social = 1
        ORDER BY p.created_at DESC
    ");
    $progettiSocial = $stmt->fetchAll();
} catch (PDOException $e) {
    $progettiSocial = [];
}

// Recupera tutti i clienti per i filtri
try {
    $stmt = $pdo->query("SELECT id, ragione_sociale FROM clienti ORDER BY ragione_sociale");
    $clienti = $stmt->fetchAll();
} catch (PDOException $e) {
    $clienti = [];
}

// Piattaforme social
$piattaforme = [
    'instagram' => ['nome' => 'Instagram', 'colore' => '#E4405F', 'icona' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z'],
    'facebook' => ['nome' => 'Facebook', 'colore' => '#1877F2', 'icona' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z'],
    'tiktok' => ['nome' => 'TikTok', 'colore' => '#000000', 'icona' => 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z'],
    'linkedin' => ['nome' => 'LinkedIn', 'colore' => '#0A66C2', 'icona' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z'],
    'twitter' => ['nome' => 'Twitter/X', 'colore' => '#000000', 'icona' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z'],
    'youtube' => ['nome' => 'YouTube', 'colore' => '#FF0000', 'icona' => 'M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z'],
    'pinterest' => ['nome' => 'Pinterest', 'colore' => '#BD081C', 'icona' => 'M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z'],
    'altro' => ['nome' => 'Altro', 'colore' => '#6B7280', 'icona' => 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z']
];

// Tipologie post
$tipologiePost = [
    'feed' => 'Feed',
    'stories' => 'Stories',
    'reels' => 'Reels',
    'carousel' => 'Carousel',
    'video' => 'Video',
    'live' => 'Live',
    'sponsored' => 'Sponsorizzato',
    'altro' => 'Altro'
];

// Stati post
$statiPost = [
    'bozza' => ['label' => 'Bozza', 'colore' => 'bg-slate-200 text-slate-700'],
    'in_revisione' => ['label' => 'In Revisione', 'colore' => 'bg-yellow-100 text-yellow-700'],
    'approvato' => ['label' => 'Approvato', 'colore' => 'bg-blue-100 text-blue-700'],
    'programmato' => ['label' => 'Programmato', 'colore' => 'bg-purple-100 text-purple-700'],
    'pubblicato' => ['label' => 'Pubblicato', 'colore' => 'bg-green-100 text-green-700'],
    'archiviato' => ['label' => 'Archiviato', 'colore' => 'bg-gray-100 text-gray-700']
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Main Content -->
<main class="flex-1 p-4 lg:p-8 pb-24 lg:pb-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#2d2d2d]">Piano Editoriale</h1>
                <p class="text-sm text-slate-500 mt-1">Gestione contenuti social media</p>
            </div>
            <button onclick="openModal('postModal')" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white rounded-xl font-medium hover:shadow-lg hover:shadow-purple-500/30 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Nuovo Post</span>
            </button>
        </div>
    </div>

    <!-- Filtri e Vista -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Filtro Progetto -->
            <div class="flex-1">
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Progetto</label>
                <select id="filtroProgetto" onchange="caricaPosts()" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                    <option value="">Tutti i progetti</option>
                    <?php foreach ($progettiSocial as $p): ?>
                    <option value="<?php echo e($p['id']); ?>"><?php echo e($p['titolo']); ?> (<?php echo e($p['cliente_nome'] ?? 'Senza cliente'); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Filtro Piattaforma -->
            <div class="flex-1">
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Piattaforma</label>
                <select id="filtroPiattaforma" onchange="caricaPosts()" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                    <option value="">Tutte le piattaforme</option>
                    <?php foreach ($piattaforme as $key => $p): ?>
                    <option value="<?php echo e($key); ?>"><?php echo e($p['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Filtro Stato -->
            <div class="flex-1">
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Stato</label>
                <select id="filtroStato" onchange="caricaPosts()" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                    <option value="">Tutti gli stati</option>
                    <?php foreach ($statiPost as $key => $s): ?>
                    <option value="<?php echo e($key); ?>"><?php echo e($s['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Filtro Data -->
            <div class="flex-1">
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Mese</label>
                <input type="month" id="filtroMese" onchange="caricaPosts()" 
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm"
                       value="<?php echo date('Y-m'); ?>">
            </div>
        </div>
    </div>

    <!-- Vista Calendario / Lista -->
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2 bg-white rounded-lg p-1 shadow-sm border border-slate-100">
            <button onclick="switchVista('calendario')" id="btnVistaCalendario" class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors bg-pink-100 text-pink-700">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Calendario
                </span>
            </button>
            <button onclick="switchVista('lista')" id="btnVistaLista" class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-slate-600 hover:bg-slate-100">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    Lista
                </span>
            </button>
        </div>
        
        <div class="text-sm text-slate-500">
            <span id="contatorePosts">0</span> post trovati
        </div>
    </div>

    <!-- Contenuto Calendario -->
    <div id="vistaCalendario" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Header Calendario -->
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
            <?php $giorni = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom']; ?>
            <?php foreach ($giorni as $g): ?>
            <div class="py-3 text-center text-sm font-medium text-slate-600"><?php echo $g; ?></div>
            <?php endforeach; ?>
        </div>
        
        <!-- Griglia Calendario -->
        <div id="calendarioGrid" class="grid grid-cols-7 auto-rows-fr">
            <!-- Popolato via JS -->
        </div>
    </div>

    <!-- Contenuto Lista (nascosto di default) -->
    <div id="vistaLista" class="hidden space-y-4">
        <div id="listaPosts" class="space-y-3">
            <!-- Popolato via JS -->
        </div>
    </div>

    <!-- Progetti Social Card -->
    <div class="mt-8">
        <h2 class="text-lg font-semibold text-[#2d2d2d] mb-4">Progetti con Gestione Social</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($progettiSocial as $p): ?>
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 hover:shadow-md transition-shadow cursor-pointer" onclick="filtraPerProgetto('<?php echo e($p['id']); ?>')">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="font-medium text-slate-800 line-clamp-1"><?php echo e($p['titolo']); ?></h3>
                    <span class="w-2 h-2 rounded-full" style="background-color: <?php echo e($p['colore_tag'] ?? '#9bc4d0'); ?>"></span>
                </div>
                <p class="text-sm text-slate-500 mb-3"><?php echo e($p['cliente_nome'] ?? 'Senza cliente'); ?></p>
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="posts-count" data-progetto="<?php echo e($p['id']); ?>">Caricamento...</span>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($progettiSocial)): ?>
            <div class="col-span-full text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-pink-100 to-purple-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                </div>
                <h3 class="text-slate-700 font-medium mb-1">Nessun progetto con gestione social</h3>
                <p class="text-sm text-slate-500 mb-4">Attiva la gestione social nei progetti per vederli qui</p>
                <a href="progetti.php" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 text-white rounded-lg text-sm hover:bg-slate-700 transition-colors">
                    Vai ai Progetti
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal Nuovo/Modifica Post -->
<div id="postModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('postModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white sm:rounded-2xl rounded-t-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-pink-50 to-purple-50">
                <h3 id="postModalTitle" class="font-bold text-slate-800">Nuovo Post</h3>
                <button onclick="closeModal('postModal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="postForm" class="flex-1 overflow-y-auto p-5 space-y-4">
                <input type="hidden" name="id" id="postId">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <!-- Progetto -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Progetto *</label>
                    <select name="progetto_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                        <option value="">Seleziona progetto...</option>
                        <?php foreach ($progettiSocial as $p): ?>
                        <option value="<?php echo e($p['id']); ?>"><?php echo e($p['titolo']); ?> - <?php echo e($p['cliente_nome'] ?? 'Senza cliente'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Titolo e Piattaforma -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Titolo *</label>
                        <input type="text" name="titolo" required 
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm"
                               placeholder="Titolo del post">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Piattaforma *</label>
                        <select name="piattaforma" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                            <?php foreach ($piattaforme as $key => $p): ?>
                            <option value="<?php echo e($key); ?>"><?php echo e($p['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Tipologia e Stato -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Tipologia *</label>
                        <select name="tipologia" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                            <?php foreach ($tipologiePost as $key => $t): ?>
                            <option value="<?php echo e($key); ?>"><?php echo e($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Stato *</label>
                        <select name="stato" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                            <?php foreach ($statiPost as $key => $s): ?>
                            <option value="<?php echo e($key); ?>"><?php echo e($s['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Data e Ora -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Data pubblicazione *</label>
                        <input type="date" name="data_prevista" required 
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Ora (opzionale)</label>
                        <input type="time" name="ora_prevista"
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                    </div>
                </div>
                
                <!-- Descrizione -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Testo / Didascalia</label>
                    <textarea name="descrizione" rows="4"
                              class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none resize-none text-sm"
                              placeholder="Scrivi qui il testo del post... Usa # per gli hashtag"></textarea>
                </div>
                
                <!-- Hashtag e Menzioni -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Hashtag</label>
                        <input type="text" name="hashtag" 
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm"
                               placeholder="#hashtag1 #hashtag2">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Menzioni</label>
                        <input type="text" name="menzioni" 
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm"
                               placeholder="@account1 @account2">
                    </div>
                </div>
                
                <!-- Assegnazione -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Assegnato a</label>
                    <select name="assegnato_a" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                        <option value="">-- Non assegnato --</option>
                        <?php foreach (USERS as $uid => $user): ?>
                        <option value="<?php echo e($uid); ?>"><?php echo e($user['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Sponsorizzato -->
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
                    <input type="checkbox" name="is_sponsored" id="isSponsored" value="1" class="w-4 h-4 text-pink-600 rounded border-slate-300 focus:ring-pink-500">
                    <label for="isSponsored" class="text-sm font-medium text-slate-700 cursor-pointer">Post sponsorizzato</label>
                    <input type="number" name="budget_sponsorizzato" step="0.01" min="0" placeholder="Budget €"
                           class="ml-auto w-24 px-2 py-1 text-sm border border-slate-200 rounded focus:ring-2 focus:ring-pink-500 outline-none">
                </div>
                
                <!-- Note -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Note interne</label>
                    <textarea name="note" rows="2"
                              class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none resize-none text-sm"
                              placeholder="Note visibili solo al team..."></textarea>
                </div>
                
                <!-- Upload Contenuti -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1.5">Contenuti multimediali</label>
                    <div id="contenutiPreview" class="grid grid-cols-3 gap-2 mb-2">
                        <!-- Preview contenuti caricati -->
                    </div>
                    <input type="file" id="contenutiUpload" multiple accept="image/*,video/*"
                           class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-medium file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100">
                </div>
            </form>
            
            <div class="p-5 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal('postModal')" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-medium transition-colors">
                    Annulla
                </button>
                <button type="button" onclick="salvaPost()" class="px-4 py-2 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white rounded-lg text-sm font-medium hover:shadow-lg hover:shadow-purple-500/30 transition-all">
                    Salva Post
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dettaglio Post -->
<div id="dettaglioPostModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('dettaglioPostModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white sm:rounded-2xl rounded-t-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
            <div id="dettaglioHeader" class="p-5 border-b border-slate-100 flex items-center justify-between">
                <!-- Popolato via JS -->
            </div>
            <div id="dettaglioContent" class="flex-1 overflow-y-auto p-5">
                <!-- Popolato via JS -->
            </div>
            <div class="p-5 border-t border-slate-100 flex items-center justify-between">
                <button type="button" onclick="eliminaPost()" class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg text-sm font-medium transition-colors">
                    Elimina
                </button>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="modificaPost()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-200 transition-colors">
                        Modifica
                    </button>
                    <button type="button" onclick="cambiaStatoPost()" class="px-4 py-2 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white rounded-lg text-sm font-medium hover:shadow-lg transition-all">
                        Cambia Stato
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Variabili globali
let postsData = [];
let currentPostId = null;
let currentVista = 'calendario';

// Inizializzazione
document.addEventListener('DOMContentLoaded', function() {
    caricaPosts();
});

// Carica posts dal server
async function caricaPosts() {
    try {
        const params = new URLSearchParams({
            action: 'list',
            progetto_id: document.getElementById('filtroProgetto').value,
            piattaforma: document.getElementById('filtroPiattaforma').value,
            stato: document.getElementById('filtroStato').value,
            mese: document.getElementById('filtroMese').value
        });
        
        const response = await fetch(`api/piano_editoriale.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            postsData = data.data || [];
            document.getElementById('contatorePosts').textContent = postsData.length;
            
            if (currentVista === 'calendario') {
                renderCalendario();
            } else {
                renderLista();
            }
            
            // Aggiorna contatori per progetto
            aggiornaContatoriProgetti();
        }
    } catch (error) {
        console.error('Errore caricamento posts:', error);
        showToast('Errore caricamento posts', 'error');
    }
}

// Render calendario
function renderCalendario() {
    const grid = document.getElementById('calendarioGrid');
    const mese = document.getElementById('filtroMese').value;
    const [anno, meseNum] = mese.split('-').map(Number);
    
    const primoGiorno = new Date(anno, meseNum - 1, 1);
    const ultimoGiorno = new Date(anno, meseNum, 0);
    const giorniInMese = ultimoGiorno.getDate();
    const giornoSettimana = (primoGiorno.getDay() + 6) % 7; // 0 = Lunedi
    
    let html = '';
    
    // Celle vuote prima dell'inizio del mese
    for (let i = 0; i < giornoSettimana; i++) {
        html += '<div class="min-h-[100px] bg-slate-50/50 border-b border-r border-slate-100"></div>';
    }
    
    // Giorni del mese
    const oggi = new Date();
    const isOggi = (d) => oggi.getDate() === d && oggi.getMonth() + 1 === meseNum && oggi.getFullYear() === anno;
    
    for (let giorno = 1; giorno <= giorniInMese; giorno++) {
        const dataGiorno = `${anno}-${String(meseNum).padStart(2, '0')}-${String(giorno).padStart(2, '0')}`;
        const postsGiorno = postsData.filter(p => p.data_prevista === dataGiorno);
        
        const bgClass = isOggi(giorno) ? 'bg-pink-50/50' : 'bg-white';
        const borderClass = isOggi(giorno) ? 'border-pink-200' : 'border-slate-100';
        
        html += `
            <div class="min-h-[100px] ${bgClass} border-b border-r ${borderClass} p-2 relative group cursor-pointer hover:bg-slate-50 transition-colors"
                 onclick="apriNuovoPost('${dataGiorno}')">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium ${isOggi(giorno) ? 'text-pink-600' : 'text-slate-600'}">${giorno}</span>
                    ${postsGiorno.length > 0 ? `<span class="text-xs bg-pink-100 text-pink-700 px-1.5 py-0.5 rounded-full">${postsGiorno.length}</span>` : ''}
                </div>
                <div class="space-y-1">
                    ${postsGiorno.slice(0, 3).map(p => `
                        <div onclick="event.stopPropagation(); apriDettaglioPost('${p.id}')" 
                             class="text-xs p-1.5 rounded ${getStatoBgClass(p.stato)} truncate cursor-pointer hover:opacity-80">
                            ${getPiattaformaIcona(p.piattaforma, 'w-3 h-3 inline mr-1')} ${escapeHtml(p.titolo)}
                        </div>
                    `).join('')}
                    ${postsGiorno.length > 3 ? `<div class="text-xs text-slate-400 text-center">+${postsGiorno.length - 3} altri</div>` : ''}
                </div>
                <button onclick="event.stopPropagation(); apriNuovoPost('${dataGiorno}')" 
                        class="absolute bottom-2 right-2 w-6 h-6 bg-pink-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            </div>
        `;
    }
    
    grid.innerHTML = html;
}

// Render lista
function renderLista() {
    const container = document.getElementById('listaPosts');
    
    if (postsData.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gradient-to-br from-pink-100 to-purple-100 flex items-center justify-center">
                    <svg class="w-8 h-8 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h3 class="text-slate-700 font-medium mb-1">Nessun post trovato</h3>
                <p class="text-sm text-slate-500">Crea il tuo primo post per questo periodo</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = postsData.map(p => `
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 hover:shadow-md transition-shadow cursor-pointer"
             onclick="apriDettaglioPost('${p.id}')">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background-color: ${getPiattaformaColore(p.piattaforma)}20">
                    ${getPiattaformaIcona(p.piattaforma, 'w-5 h-5')}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h4 class="font-medium text-slate-800 line-clamp-1">${escapeHtml(p.titolo)}</h4>
                            <p class="text-sm text-slate-500">${escapeHtml(p.progetto_titolo || 'Progetto')}</p>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-medium ${getStatoBgClass(p.stato)}">
                            ${getStatoLabel(p.stato)}
                        </span>
                    </div>
                    <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            ${formatDate(p.data_prevista)}
                        </span>
                        ${p.ora_prevista ? `<span>${p.ora_prevista}</span>` : ''}
                        ${p.is_sponsored ? '<span class="text-pink-600 font-medium">Sponsorizzato</span>' : ''}
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Helper per piattaforme
function getPiattaformaColore(piattaforma) {
    const colori = {
        'instagram': '#E4405F',
        'facebook': '#1877F2',
        'tiktok': '#000000',
        'linkedin': '#0A66C2',
        'twitter': '#000000',
        'youtube': '#FF0000',
        'pinterest': '#BD081C',
        'altro': '#6B7280'
    };
    return colori[piattaforma] || '#6B7280';
}

function getPiattaformaIcona(piattaforma, className = 'w-5 h-5') {
    // Semplice rappresentazione con iniziale
    const iniziali = {
        'instagram': 'IG',
        'facebook': 'FB',
        'tiktok': 'TT',
        'linkedin': 'LI',
        'twitter': 'X',
        'youtube': 'YT',
        'pinterest': 'PI',
        'altro': '??'
    };
    const colore = getPiattaformaColore(piattaforma);
    return `<span class="${className} flex items-center justify-center text-xs font-bold" style="color: ${colore}">${iniziali[piattaforma] || '??'}</span>`;
}

function getStatoBgClass(stato) {
    const classi = {
        'bozza': 'bg-slate-100 text-slate-700',
        'in_revisione': 'bg-yellow-100 text-yellow-700',
        'approvato': 'bg-blue-100 text-blue-700',
        'programmato': 'bg-purple-100 text-purple-700',
        'pubblicato': 'bg-green-100 text-green-700',
        'archiviato': 'bg-gray-100 text-gray-500'
    };
    return classi[stato] || 'bg-slate-100 text-slate-700';
}

function getStatoLabel(stato) {
    const labels = {
        'bozza': 'Bozza',
        'in_revisione': 'In Revisione',
        'approvato': 'Approvato',
        'programmato': 'Programmato',
        'pubblicato': 'Pubblicato',
        'archiviato': 'Archiviato'
    };
    return labels[stato] || stato;
}

// Switch vista
function switchVista(vista) {
    currentVista = vista;
    document.getElementById('vistaCalendario').classList.toggle('hidden', vista !== 'calendario');
    document.getElementById('vistaLista').classList.toggle('hidden', vista !== 'lista');
    
    document.getElementById('btnVistaCalendario').className = vista === 'calendario' 
        ? 'px-3 py-1.5 rounded-md text-sm font-medium transition-colors bg-pink-100 text-pink-700'
        : 'px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-slate-600 hover:bg-slate-100';
    document.getElementById('btnVistaLista').className = vista === 'lista'
        ? 'px-3 py-1.5 rounded-md text-sm font-medium transition-colors bg-pink-100 text-pink-700'
        : 'px-3 py-1.5 rounded-md text-sm font-medium transition-colors text-slate-600 hover:bg-slate-100';
    
    if (vista === 'calendario') {
        renderCalendario();
    } else {
        renderLista();
    }
}

// Apri modal nuovo post
function apriNuovoPost(data = null) {
    document.getElementById('postModalTitle').textContent = 'Nuovo Post';
    document.getElementById('postForm').reset();
    document.getElementById('postId').value = '';
    
    if (data) {
        document.querySelector('input[name="data_prevista"]').value = data;
    }
    
    openModal('postModal');
}

// Salva post
async function salvaPost() {
    const form = document.getElementById('postForm');
    const formData = new FormData(form);
    
    // Aggiungi contenuti upload
    const files = document.getElementById('contenutiUpload').files;
    for (let i = 0; i < files.length; i++) {
        formData.append('contenuti[]', files[i]);
    }
    
    try {
        const response = await fetch('api/piano_editoriale.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message || 'Post salvato con successo', 'success');
            closeModal('postModal');
            caricaPosts();
        } else {
            showToast(data.message || 'Errore salvataggio', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

// Apri dettaglio post
async function apriDettaglioPost(id) {
    currentPostId = id;
    
    try {
        const response = await fetch(`api/piano_editoriale.php?action=detail&id=${id}`);
        const data = await response.json();
        
        if (!data.success) {
            showToast('Errore caricamento dettaglio', 'error');
            return;
        }
        
        const p = data.data;
        
        // Header
        document.getElementById('dettaglioHeader').innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: ${getPiattaformaColore(p.piattaforma)}20">
                    ${getPiattaformaIcona(p.piattaforma, 'w-5 h-5')}
                </div>
                <div>
                    <h3 class="font-bold text-slate-800">${escapeHtml(p.titolo)}</h3>
                    <span class="text-xs ${getStatoBgClass(p.stato)}">${getStatoLabel(p.stato)}</span>
                </div>
            </div>
            <button onclick="closeModal('dettaglioPostModal')" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;
        
        // Content
        document.getElementById('dettaglioContent').innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center gap-4 text-sm text-slate-500">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        ${formatDate(p.data_prevista)}
                    </span>
                    ${p.ora_prevista ? `<span>${p.ora_prevista}</span>` : ''}
                </div>
                
                ${p.descrizione ? `<div class="bg-slate-50 p-3 rounded-lg"><p class="text-sm text-slate-700 whitespace-pre-wrap">${escapeHtml(p.descrizione)}</p></div>` : ''}
                
                ${p.hashtag ? `<div><span class="text-xs font-medium text-slate-500">Hashtag:</span> <span class="text-sm text-pink-600">${escapeHtml(p.hashtag)}</span></div>` : ''}
                
                ${p.menzioni ? `<div><span class="text-xs font-medium text-slate-500">Menzioni:</span> <span class="text-sm text-blue-600">${escapeHtml(p.menzioni)}</span></div>` : ''}
                
                ${p.note ? `<div><span class="text-xs font-medium text-slate-500">Note:</span> <p class="text-sm text-slate-600">${escapeHtml(p.note)}</p></div>` : ''}
                
                ${p.is_sponsored ? `<div class="flex items-center gap-2 text-pink-600 text-sm font-medium"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg> Sponsorizzato ${p.budget_sponsorizzato ? `(${p.budget_sponsorizzato}€)` : ''}</div>` : ''}
            </div>
        `;
        
        openModal('dettaglioPostModal');
    } catch (error) {
        showToast('Errore caricamento dettaglio', 'error');
    }
}

// Modifica post
async function modificaPost() {
    if (!currentPostId) return;
    
    closeModal('dettaglioPostModal');
    
    try {
        const response = await fetch(`api/piano_editoriale.php?action=detail&id=${currentPostId}`);
        const data = await response.json();
        
        if (!data.success) {
            showToast('Errore caricamento post', 'error');
            return;
        }
        
        const p = data.data;
        
        document.getElementById('postModalTitle').textContent = 'Modifica Post';
        document.getElementById('postId').value = p.id;
        document.querySelector('select[name="progetto_id"]').value = p.progetto_id;
        document.querySelector('input[name="titolo"]').value = p.titolo;
        document.querySelector('select[name="piattaforma"]').value = p.piattaforma;
        document.querySelector('select[name="tipologia"]').value = p.tipologia;
        document.querySelector('select[name="stato"]').value = p.stato;
        document.querySelector('input[name="data_prevista"]').value = p.data_prevista;
        document.querySelector('input[name="ora_prevista"]').value = p.ora_prevista || '';
        document.querySelector('textarea[name="descrizione"]').value = p.descrizione || '';
        document.querySelector('input[name="hashtag"]').value = p.hashtag || '';
        document.querySelector('input[name="menzioni"]').value = p.menzioni || '';
        document.querySelector('select[name="assegnato_a"]').value = p.assegnato_a || '';
        document.querySelector('textarea[name="note"]').value = p.note || '';
        document.getElementById('isSponsored').checked = p.is_sponsored == 1;
        document.querySelector('input[name="budget_sponsorizzato"]').value = p.budget_sponsorizzato || '';
        
        openModal('postModal');
    } catch (error) {
        showToast('Errore caricamento post', 'error');
    }
}

// Elimina post
async function eliminaPost() {
    if (!currentPostId) return;
    
    if (!confirm('Sei sicuro di voler eliminare questo post?')) return;
    
    try {
        const response = await fetch('api/piano_editoriale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete&id=${currentPostId}&csrf_token=${getCsrfToken()}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Post eliminato', 'success');
            closeModal('dettaglioPostModal');
            caricaPosts();
        } else {
            showToast(data.message || 'Errore eliminazione', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

// Cambia stato post
async function cambiaStatoPost() {
    if (!currentPostId) return;
    
    const nuovoStato = prompt('Nuovo stato (bozza, in_revisione, approvato, programmato, pubblicato, archiviato):');
    if (!nuovoStato) return;
    
    try {
        const response = await fetch('api/piano_editoriale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=change_stato&id=${currentPostId}&stato=${nuovoStato}&csrf_token=${getCsrfToken()}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Stato aggiornato', 'success');
            closeModal('dettaglioPostModal');
            caricaPosts();
        } else {
            showToast(data.message || 'Errore aggiornamento', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

// Filtra per progetto
function filtraPerProgetto(progettoId) {
    document.getElementById('filtroProgetto').value = progettoId;
    caricaPosts();
}

// Aggiorna contatori progetti
function aggiornaContatoriProgetti() {
    document.querySelectorAll('.posts-count').forEach(el => {
        const progettoId = el.dataset.progetto;
        const count = postsData.filter(p => p.progetto_id === progettoId).length;
        el.textContent = count + (count === 1 ? ' post' : ' posts');
    });
}

// Helper functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT', { day: '2-digit', month: 'short', year: 'numeric' });
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
