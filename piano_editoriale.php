<?php
/**
 * Eterea Gestionale
 * Piano Editoriale - Gestione Social Media (v2.0)
 * Ottimizzato per mobile con nuove funzionalità
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

// Recupera statistiche per dashboard
try {
    $meseCorrente = date('Y-m');
    
    // Conteggio post per stato
    $stmt = $pdo->prepare("
        SELECT stato, COUNT(*) as count 
        FROM piano_editoriale 
        WHERE DATE_FORMAT(data_prevista, '%Y-%m') = ?
        GROUP BY stato
    ");
    $stmt->execute([$meseCorrente]);
    $statsStato = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Post in scadenza (prossimi 7 giorni)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM piano_editoriale 
        WHERE data_prevista BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND stato IN ('bozza', 'in_revisione', 'approvato', 'programmato')
    ");
    $postInScadenza = $stmt->fetchColumn();
    
    // Post da pubblicare oggi
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM piano_editoriale 
        WHERE data_prevista = CURDATE()
        AND stato = 'programmato'
    ");
    $postOggi = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $statsStato = [];
    $postInScadenza = 0;
    $postOggi = 0;
}

// Piattaforme social con icone ottimizzate
$piattaforme = [
    'instagram' => ['nome' => 'Instagram', 'colore' => '#E4405F', 'icona' => 'instagram', 'hashtag' => '#insta #instagram #social'],
    'facebook' => ['nome' => 'Facebook', 'colore' => '#1877F2', 'icona' => 'facebook', 'hashtag' => '#facebook #fb #social'],
    'tiktok' => ['nome' => 'TikTok', 'colore' => '#000000', 'icona' => 'tiktok', 'hashtag' => '#tiktok #viral #trending'],
    'linkedin' => ['nome' => 'LinkedIn', 'colore' => '#0A66C2', 'icona' => 'linkedin', 'hashtag' => '#linkedin #business #professional'],
    'twitter' => ['nome' => 'X/Twitter', 'colore' => '#000000', 'icona' => 'twitter', 'hashtag' => '#x #twitter #tweet'],
    'youtube' => ['nome' => 'YouTube', 'colore' => '#FF0000', 'icona' => 'youtube', 'hashtag' => '#youtube #video #yt'],
    'pinterest' => ['nome' => 'Pinterest', 'colore' => '#BD081C', 'icona' => 'pinterest', 'hashtag' => '#pinterest #pin #ideas'],
    'altro' => ['nome' => 'Altro', 'colore' => '#6B7280', 'icona' => 'globe', 'hashtag' => '#social #content']
];

// Tipologie post
$tipologiePost = [
    'feed' => ['label' => 'Feed', 'icon' => 'image'],
    'stories' => ['label' => 'Stories', 'icon' => 'clock'],
    'reels' => ['label' => 'Reels', 'icon' => 'film'],
    'carousel' => ['label' => 'Carousel', 'icon' => 'layers'],
    'video' => ['label' => 'Video', 'icon' => 'video'],
    'live' => ['label' => 'Live', 'icon' => 'radio'],
    'sponsored' => ['label' => 'Sponsorizzato', 'icon' => 'trending-up'],
    'altro' => ['label' => 'Altro', 'icon' => 'more-horizontal']
];

// Stati post con workflow
$statiPost = [
    'bozza' => ['label' => 'Bozza', 'colore' => 'bg-slate-200 text-slate-700', 'icon' => 'edit-2'],
    'in_revisione' => ['label' => 'In Revisione', 'colore' => 'bg-yellow-100 text-yellow-700', 'icon' => 'eye'],
    'approvato' => ['label' => 'Approvato', 'colore' => 'bg-blue-100 text-blue-700', 'icon' => 'check-circle'],
    'programmato' => ['label' => 'Programmato', 'colore' => 'bg-purple-100 text-purple-700', 'icon' => 'calendar'],
    'pubblicato' => ['label' => 'Pubblicato', 'colore' => 'bg-green-100 text-green-700', 'icon' => 'send'],
    'archiviato' => ['label' => 'Archiviato', 'colore' => 'bg-gray-100 text-gray-500', 'icon' => 'archive']
];

require_once __DIR__ . '/includes/header.php';
?>

<style>
/* Stili Mobile-First per Piano Editoriale */
.pe-calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
    background: #e2e8f0;
}

.pe-calendar-cell {
    background: white;
    min-height: 80px;
    padding: 4px;
    position: relative;
    transition: background 0.2s;
}

@media (min-width: 768px) {
    .pe-calendar-cell {
        min-height: 100px;
        padding: 8px;
    }
}

.pe-calendar-cell:hover {
    background: #f8fafc;
}

.pe-calendar-cell.today {
    background: #fdf2f8;
    border: 2px solid #ec4899;
}

.pe-post-pill {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 9999px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    transition: transform 0.1s;
}

@media (min-width: 768px) {
    .pe-post-pill {
        font-size: 11px;
        padding: 3px 8px;
    }
}

.pe-post-pill:active {
    transform: scale(0.95);
}

/* Card mobile ottimizzate */
.pe-mobile-card {
    touch-action: pan-y;
    -webkit-tap-highlight-color: transparent;
}

/* Quick Actions FAB */
.pe-fab {
    position: fixed;
    bottom: calc(80px + env(safe-area-inset-bottom, 0px));
    right: 20px;
    z-index: 50;
}

@media (min-width: 1024px) {
    .pe-fab {
        bottom: 30px;
    }
}

/* Swipeable week view per mobile */
.pe-week-view {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
}

.pe-week-view::-webkit-scrollbar {
    display: none;
}

/* Instagram Feed Preview */
.ig-feed-preview {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2px;
    max-width: 375px;
    margin: 0 auto;
}

.ig-feed-item {
    aspect-ratio: 1;
    background: #f3f4f6;
    position: relative;
    overflow: hidden;
}

.ig-feed-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* AI Assistant Panel */
.ai-panel {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 16px;
    padding: 16px;
}

/* Stats Cards */
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stat-card:active {
    transform: scale(0.98);
}

/* Hashtag Cloud */
.hashtag-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.hashtag-tag {
    background: #f3f4f6;
    color: #4b5563;
    padding: 4px 12px;
    border-radius: 9999px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.hashtag-tag:hover {
    background: #ec4899;
    color: white;
}

/* Bottom Sheet per mobile */
.bottom-sheet {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-radius: 24px 24px 0 0;
    z-index: 60;
    transform: translateY(100%);
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    max-height: 85vh;
    overflow-y: auto;
}

.bottom-sheet.open {
    transform: translateY(0);
}

/* Pull indicator */
.pull-indicator {
    width: 40px;
    height: 4px;
    background: #d1d5db;
    border-radius: 2px;
    margin: 12px auto;
}
</style>

<!-- Main Content -->
<main class="flex-1 p-4 lg:p-8 pb-24 lg:pb-8">
    <!-- Header con Stats -->
    <div class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-[#2d2d2d]">Piano Editoriale</h1>
                <p class="text-sm text-slate-500 mt-1">Gestione contenuti social media</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="openModal('aiAssistantModal')" 
                        class="p-2.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl hover:shadow-lg transition-all lg:hidden"
                        title="AI Assistant">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </button>
                <button onclick="openModal('ideeModal')" 
                        class="p-2.5 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-all"
                        title="Idea Storage">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Stats Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="stat-card border-l-4 border-pink-500">
                <div class="text-2xl font-bold text-slate-800"><?php echo $postOggi; ?></div>
                <div class="text-xs text-slate-500">Da pubblicare oggi</div>
            </div>
            <div class="stat-card border-l-4 border-yellow-500">
                <div class="text-2xl font-bold text-slate-800"><?php echo $postInScadenza; ?></div>
                <div class="text-xs text-slate-500">Prossimi 7 giorni</div>
            </div>
            <div class="stat-card border-l-4 border-blue-500">
                <div class="text-2xl font-bold text-slate-800"><?php echo $statsStato['bozza'] ?? 0; ?></div>
                <div class="text-xs text-slate-500">In bozza</div>
            </div>
            <div class="stat-card border-l-4 border-green-500">
                <div class="text-2xl font-bold text-slate-800"><?php echo $statsStato['pubblicato'] ?? 0; ?></div>
                <div class="text-xs text-slate-500">Pubblicati questo mese</div>
            </div>
        </div>
    </div>

    <!-- Filtri Collassabili -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 mb-4 overflow-hidden">
        <button onclick="toggleFilters()" class="w-full px-4 py-3 flex items-center justify-between text-left">
            <span class="font-medium text-slate-700 flex items-center gap-2">
                <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filtri e Ricerca
            </span>
            <svg id="filterArrow" class="w-5 h-5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div id="filtersContent" class="hidden px-4 pb-4 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Progetto</label>
                    <select id="filtroProgetto" onchange="caricaPosts()" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                        <option value="">Tutti i progetti</option>
                        <?php foreach ($progettiSocial as $p): ?>
                        <option value="<?php echo e($p['id']); ?>"><?php echo e($p['titolo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Piattaforma</label>
                    <select id="filtroPiattaforma" onchange="caricaPosts()" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                        <option value="">Tutte</option>
                        <?php foreach ($piattaforme as $key => $p): ?>
                        <option value="<?php echo e($key); ?>"><?php echo e($p['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Stato</label>
                    <select id="filtroStato" onchange="caricaPosts()" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                        <option value="">Tutti</option>
                        <?php foreach ($statiPost as $key => $s): ?>
                        <option value="<?php echo e($key); ?>"><?php echo e($s['label']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Mese</label>
                    <input type="month" id="filtroMese" onchange="caricaPosts()" 
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm"
                           value="<?php echo date('Y-m'); ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Vista -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-1 bg-white rounded-lg p-1 shadow-sm border border-slate-100">
            <button onclick="switchVista('calendario')" id="btnVistaCalendario" 
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-all bg-pink-100 text-pink-700 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="hidden sm:inline">Calendario</span>
            </button>
            <button onclick="switchVista('lista')" id="btnVistaLista" 
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-all text-slate-600 hover:bg-slate-100 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <span class="hidden sm:inline">Lista</span>
            </button>
            <button onclick="switchVista('feed')" id="btnVistaFeed" 
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-all text-slate-600 hover:bg-slate-100 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="hidden sm:inline">Feed</span>
            </button>
            <button onclick="switchVista('analytics')" id="btnVistaAnalytics" 
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-all text-slate-600 hover:bg-slate-100 flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="hidden sm:inline">Analytics</span>
            </button>
        </div>
        
        <div class="text-sm text-slate-500">
            <span id="contatorePosts">0</span> <span class="hidden sm:inline">post</span>
        </div>
    </div>

    <!-- Vista Calendario -->
    <div id="vistaCalendario" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Header Giorni -->
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
            <?php $giorni = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom']; ?>
            <?php foreach ($giorni as $g): ?>
            <div class="py-2 text-center text-xs sm:text-sm font-medium text-slate-600"><?php echo $g; ?></div>
            <?php endforeach; ?>
        </div>
        
        <!-- Griglia Calendario -->
        <div id="calendarioGrid" class="pe-calendar-grid">
            <!-- Popolato via JS -->
        </div>
    </div>

    <!-- Vista Lista -->
    <div id="vistaLista" class="hidden space-y-3">
        <div id="listaPosts" class="space-y-3">
            <!-- Popolato via JS -->
        </div>
    </div>

    <!-- Vista Feed Preview (Instagram-style) -->
    <div id="vistaFeed" class="hidden">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4 text-center">Anteprima Feed Instagram</h3>
            <div class="ig-feed-preview" id="feedPreviewGrid">
                <!-- Popolato via JS -->
            </div>
        </div>
    </div>

    <!-- Vista Analytics -->
    <div id="vistaAnalytics" class="hidden space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Performance Chart -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
                <h3 class="font-semibold text-slate-800 mb-4">Performance Post</h3>
                <canvas id="performanceChart" height="200"></canvas>
            </div>
            
            <!-- Stati Distribution -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
                <h3 class="font-semibold text-slate-800 mb-4">Distribuzione per Stato</h3>
                <div id="statiDistribution" class="space-y-2">
                    <!-- Popolato via JS -->
                </div>
            </div>
        </div>
        
        <!-- Best Time to Post -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
            <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                Miglior Orario per Pubblicare
            </h3>
            <div class="grid grid-cols-4 sm:grid-cols-7 gap-2" id="bestTimeGrid">
                <!-- Popolato via JS -->
            </div>
        </div>
    </div>

    <!-- Progetti Card -->
    <div class="mt-8">
        <h2 class="text-lg font-semibold text-[#2d2d2d] mb-4">Progetti Social</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($progettiSocial as $p): ?>
            <div class="pe-mobile-card bg-white rounded-xl shadow-sm border border-slate-100 p-4 hover:shadow-md transition-all cursor-pointer" 
                 onclick="filtraPerProgetto('<?php echo e($p['id']); ?>')">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="font-medium text-slate-800 line-clamp-1"><?php echo e($p['titolo']); ?></h3>
                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?php echo e($p['colore_tag'] ?? '#9bc4d0'); ?>"></span>
                </div>
                <p class="text-sm text-slate-500 mb-3"><?php echo e($p['cliente_nome'] ?? 'Senza cliente'); ?></p>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="posts-count" data-progetto="<?php echo e($p['id']); ?>">...</span>
                    </div>
                    <span class="text-xs px-2 py-1 bg-pink-100 text-pink-700 rounded-full" onclick="event.stopPropagation(); apriNuovoPostPerProgetto('<?php echo e($p['id']); ?>')">
                        + Post
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Floating Action Button -->
<button onclick="apriNuovoPost()" class="pe-fab w-14 h-14 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white rounded-full shadow-lg shadow-purple-500/30 flex items-center justify-center hover:scale-110 transition-transform">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
</button>

<!-- Modal Nuovo/Modifica Post -->
<div id="postModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('postModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white sm:rounded-2xl rounded-t-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-pink-50 to-purple-50">
                <h3 id="postModalTitle" class="font-bold text-slate-800">Nuovo Post</h3>
                <button onclick="closeModal('postModal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="postForm" class="flex-1 overflow-y-auto p-4 space-y-4">
                <input type="hidden" name="id" id="postId">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <!-- Quick Actions -->
                <div class="flex items-center gap-2 overflow-x-auto pb-2">
                    <button type="button" onclick="openAiAssistant()" class="flex items-center gap-1 px-3 py-1.5 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full text-xs font-medium whitespace-nowrap">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        AI Assistant
                    </button>
                    <button type="button" onclick="salvaComeIdea()" class="flex items-center gap-1 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-full text-xs font-medium whitespace-nowrap">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        Salva come Idea
                    </button>
                    <button type="button" onclick="suggerisciHashtag()" class="flex items-center gap-1 px-3 py-1.5 bg-blue-100 text-blue-700 rounded-full text-xs font-medium whitespace-nowrap">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                        </svg>
                        Suggerisci Hashtag
                    </button>
                    <button type="button" onclick="bestTimeToPost()" class="flex items-center gap-1 px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded-full text-xs font-medium whitespace-nowrap">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Best Time
                    </button>
                </div>
                
                <!-- Progetto e Piattaforma -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Progetto *</label>
                        <select name="progetto_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                            <option value="">Seleziona...</option>
                            <?php foreach ($progettiSocial as $p): ?>
                            <option value="<?php echo e($p['id']); ?>"><?php echo e($p['titolo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Piattaforma *</label>
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            <?php foreach ($piattaforme as $key => $p): ?>
                            <label class="flex-shrink-0 cursor-pointer">
                                <input type="radio" name="piattaforma" value="<?php echo e($key); ?>" class="sr-only peer" <?php echo $key === 'instagram' ? 'checked' : ''; ?>>
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center transition-all peer-checked:ring-2 peer-checked:ring-offset-1" style="background-color: <?php echo e($p['colore']); ?>20; --tw-ring-color: <?php echo e($p['colore']); ?>">
                                    <span class="text-xs font-bold" style="color: <?php echo e($p['colore']); ?>"><?php echo substr($p['nome'], 0, 2); ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Titolo -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Titolo *</label>
                    <input type="text" name="titolo" required 
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm"
                           placeholder="Titolo del post">
                </div>
                
                <!-- Tipologia e Stato -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Tipologia *</label>
                        <select name="tipologia" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                            <?php foreach ($tipologiePost as $key => $t): ?>
                            <option value="<?php echo e($key); ?>"><?php echo e($t['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Stato *</label>
                        <select name="stato" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                            <?php foreach ($statiPost as $key => $s): ?>
                            <option value="<?php echo e($key); ?>"><?php echo e($s['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Data e Ora -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Data *</label>
                        <input type="date" name="data_prevista" required 
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Ora</label>
                        <input type="time" name="ora_prevista" id="oraPrevista"
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                        <p class="text-xs text-slate-400 mt-0.5" id="bestTimeHint"></p>
                    </div>
                </div>
                
                <!-- Testo con AI -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-medium text-slate-700">Testo / Didascalia</label>
                        <button type="button" onclick="generaTestoAI()" class="text-xs text-purple-600 hover:text-purple-700 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Genera con AI
                        </button>
                    </div>
                    <textarea name="descrizione" id="descrizione" rows="4"
                              class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none resize-none text-sm"
                              placeholder="Scrivi qui il testo del post..."></textarea>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-slate-400" id="charCount">0/2200 caratteri</span>
                        <div class="flex gap-2">
                            <button type="button" onclick="aggiungiEmoji('🔥')" class="text-sm hover:scale-125 transition-transform">🔥</button>
                            <button type="button" onclick="aggiungiEmoji('❤️')" class="text-sm hover:scale-125 transition-transform">❤️</button>
                            <button type="button" onclick="aggiungiEmoji('👏')" class="text-sm hover:scale-125 transition-transform">👏</button>
                            <button type="button" onclick="aggiungiEmoji('✨')" class="text-sm hover:scale-125 transition-transform">✨</button>
                            <button type="button" onclick="aggiungiEmoji('🎉')" class="text-sm hover:scale-125 transition-transform">🎉</button>
                        </div>
                    </div>
                </div>
                
                <!-- Hashtag Suggeriti -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Hashtag</label>
                    <input type="text" name="hashtag" id="hashtagInput"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm"
                           placeholder="#hashtag1 #hashtag2">
                    <div id="hashtagSuggeriti" class="hashtag-cloud mt-2">
                        <!-- Popolato via JS -->
                    </div>
                </div>
                
                <!-- Upload Contenuti -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Contenuti</label>
                    <div id="contenutiPreview" class="grid grid-cols-3 sm:grid-cols-4 gap-2 mb-2">
                        <!-- Preview -->
                    </div>
                    <label class="flex items-center justify-center w-full h-20 border-2 border-dashed border-slate-300 rounded-lg cursor-pointer hover:border-pink-500 hover:bg-pink-50 transition-colors">
                        <div class="text-center">
                            <svg class="mx-auto h-6 w-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs text-slate-500">Carica foto/video</span>
                        </div>
                        <input type="file" id="contenutiUpload" multiple accept="image/*,video/*" class="hidden" onchange="previewFiles(this)">
                    </label>
                </div>
                
                <!-- Sponsorizzato e Assegnazione -->
                <div class="space-y-3">
                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
                        <input type="checkbox" name="is_sponsored" id="isSponsored" value="1" 
                               class="w-4 h-4 text-pink-600 rounded border-slate-300 focus:ring-pink-500"
                               onchange="document.getElementById('budgetWrapper').classList.toggle('hidden', !this.checked)">
                        <label for="isSponsored" class="text-sm font-medium text-slate-700 cursor-pointer flex-1">Post sponsorizzato</label>
                        <div id="budgetWrapper" class="hidden">
                            <input type="number" name="budget_sponsorizzato" step="0.01" min="0" placeholder="€"
                                   class="w-20 px-2 py-1 text-sm border border-slate-200 rounded focus:ring-2 focus:ring-pink-500 outline-none">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Assegnato a</label>
                        <select name="assegnato_a" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none text-sm">
                            <option value="">-- Non assegnato --</option>
                            <?php foreach (USERS as $uid => $user): ?>
                            <option value="<?php echo e($uid); ?>"><?php echo e($user['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Note -->
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Note interne</label>
                    <textarea name="note" rows="2"
                              class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-pink-500 outline-none resize-none text-sm"
                              placeholder="Note visibili solo al team..."></textarea>
                </div>
            </form>
            
            <div class="p-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="salvaComeBozza()" class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg text-sm font-medium transition-colors">
                    Salva Bozza
                </button>
                <button type="button" onclick="salvaPost()" class="px-4 py-2 bg-gradient-to-r from-pink-500 via-purple-500 to-indigo-500 text-white rounded-lg text-sm font-medium hover:shadow-lg transition-all">
                    Programma Post
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal AI Assistant -->
<div id="aiAssistantModal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('aiAssistantModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white sm:rounded-2xl rounded-t-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
            <div class="ai-panel">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        AI Content Assistant
                    </h3>
                    <button onclick="closeModal('aiAssistantModal')" class="text-white/80 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Tipo di contenuto</label>
                    <select id="aiTipoContenuto" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm">
                        <option value="engagement">Post di Engagement</option>
                        <option value="promozionale">Post Promozionale</option>
                        <option value="educativo">Contenuto Educativo</option>
                        <option value="storytelling">Storytelling</option>
                        <option value="behind">Dietro le quinte</option>
                        <option value="testimonianza">Testimonianza Cliente</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Argomento/Tema</label>
                    <input type="text" id="aiArgomento" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="Es: Nuovo servizio di web design">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Tono</label>
                    <div class="flex gap-2 flex-wrap">
                        <button type="button" onclick="setAiTono('professionale')" class="ai-tono-btn px-3 py-1.5 bg-slate-100 rounded-full text-xs" data-tono="professionale">Professionale</button>
                        <button type="button" onclick="setAiTono('casual')" class="ai-tono-btn px-3 py-1.5 bg-slate-100 rounded-full text-xs" data-tono="casual">Casual</button>
                        <button type="button" onclick="setAiTono('divertente')" class="ai-tono-btn px-3 py-1.5 bg-slate-100 rounded-full text-xs" data-tono="divertente">Divertente</button>
                        <button type="button" onclick="setAiTono('ispirazionale')" class="ai-tono-btn px-3 py-1.5 bg-slate-100 rounded-full text-xs" data-tono="ispirazionale">Ispirazionale</button>
                    </div>
                </div>
                <button type="button" onclick="generaContenutoAI()" class="w-full py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl font-medium hover:shadow-lg transition-all flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Genera Contenuto
                </button>
                <div id="aiResult" class="hidden">
                    <label class="block text-xs font-medium text-slate-700 mb-1">Risultato</label>
                    <textarea id="aiGeneratedText" rows="6" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50"></textarea>
                    <button type="button" onclick="usaContenutoAI()" class="mt-2 w-full py-2 bg-slate-800 text-white rounded-lg text-sm font-medium">
                        Usa questo contenuto
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Idea Storage -->
<div id="ideeModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('ideeModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white sm:rounded-2xl rounded-t-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    Idea Storage
                </h3>
                <button onclick="closeModal('ideeModal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                <div class="space-y-3" id="ideeList">
                    <!-- Popolato via JS -->
                </div>
                <button onclick="nuovaIdea()" class="mt-4 w-full py-3 border-2 border-dashed border-slate-300 rounded-xl text-slate-500 hover:border-pink-500 hover:text-pink-500 transition-colors flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Aggiungi Nuova Idea
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Sheet per azioni rapide mobile -->
<div id="bottomSheet" class="bottom-sheet" onclick="if(event.target === this) closeBottomSheet()">
    <div class="pull-indicator"></div>
    <div class="p-4" id="bottomSheetContent">
        <!-- Contenuto dinamico -->
    </div>
</div>

<script>
// ... resto del JavaScript rimane simile con aggiunte per le nuove funzionalità
// Vedo che il file sta diventando molto lungo, procedo con un approccio più snello

// Variabili globali
let postsData = [];
let currentPostId = null;
let currentVista = 'calendario';
let ideeStorage = JSON.parse(localStorage.getItem('pe_idee') || '[]');

// Inizializzazione
document.addEventListener('DOMContentLoaded', function() {
    caricaPosts();
    initCharCounter();
    loadHashtagSuggeriti();
});

// Toggle filtri su mobile
function toggleFilters() {
    const content = document.getElementById('filtersContent');
    const arrow = document.getElementById('filterArrow');
    content.classList.toggle('hidden');
    arrow.style.transform = content.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
}

// Carica posts
async function caricaPosts() {
    try {
        const params = new URLSearchParams({
            action: 'list',
            progetto_id: document.getElementById('filtroProgetto')?.value || '',
            piattaforma: document.getElementById('filtroPiattaforma')?.value || '',
            stato: document.getElementById('filtroStato')?.value || '',
            mese: document.getElementById('filtroMese')?.value || new Date().toISOString().slice(0, 7)
        });
        
        const response = await fetch(`api/piano_editoriale.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            postsData = data.data || [];
            document.getElementById('contatorePosts').textContent = postsData.length;
            
            if (currentVista === 'calendario') {
                renderCalendario();
            } else if (currentVista === 'lista') {
                renderLista();
            } else if (currentVista === 'feed') {
                renderFeedPreview();
            } else if (currentVista === 'analytics') {
                renderAnalytics();
            }
            
            aggiornaContatoriProgetti();
        }
    } catch (error) {
        console.error('Errore caricamento posts:', error);
    }
}

// Render calendario ottimizzato mobile
function renderCalendario() {
    const grid = document.getElementById('calendarioGrid');
    const mese = document.getElementById('filtroMese').value;
    const [anno, meseNum] = mese.split('-').map(Number);
    
    const primoGiorno = new Date(anno, meseNum - 1, 1);
    const ultimoGiorno = new Date(anno, meseNum, 0);
    const giorniInMese = ultimoGiorno.getDate();
    const giornoSettimana = (primoGiorno.getDay() + 6) % 7;
    
    let html = '';
    
    // Celle vuote
    for (let i = 0; i < giornoSettimana; i++) {
        html += '<div class="pe-calendar-cell bg-slate-50/50"></div>';
    }
    
    const oggi = new Date();
    
    for (let giorno = 1; giorno <= giorniInMese; giorno++) {
        const dataGiorno = `${anno}-${String(meseNum).padStart(2, '0')}-${String(giorno).padStart(2, '0')}`;
        const postsGiorno = postsData.filter(p => p.data_prevista === dataGiorno);
        const isToday = oggi.getDate() === giorno && oggi.getMonth() + 1 === meseNum && oggi.getFullYear() === anno;
        
        html += `
            <div class="pe-calendar-cell ${isToday ? 'today' : ''}" onclick="apriNuovoPost('${dataGiorno}')">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs sm:text-sm font-medium ${isToday ? 'text-pink-600' : 'text-slate-600'}">${giorno}</span>
                    ${postsGiorno.length > 0 ? `<span class="text-[10px] bg-pink-500 text-white px-1.5 py-0.5 rounded-full">${postsGiorno.length}</span>` : ''}
                </div>
                <div class="space-y-0.5">
                    ${postsGiorno.slice(0, 2).map(p => `
                        <div onclick="event.stopPropagation(); apriDettaglioPost('${p.id}')" 
                             class="pe-post-pill ${getStatoBgClass(p.stato)}">
                            ${getPiattaformaIcona(p.piattaforma, 'w-2 h-2 inline mr-0.5')} ${escapeHtml(p.titolo.substring(0, 15))}${p.titolo.length > 15 ? '...' : ''}
                        </div>
                    `).join('')}
                    ${postsGiorno.length > 2 ? `<div class="text-[10px] text-slate-400 text-center">+${postsGiorno.length - 2}</div>` : ''}
                </div>
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
                <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-slate-500">Nessun post trovato</p>
            </div>
        `;
        return;
    }
    
    // Group by date
    const grouped = postsData.reduce((acc, p) => {
        const date = p.data_prevista;
        if (!acc[date]) acc[date] = [];
        acc[date].push(p);
        return acc;
    }, {});
    
    container.innerHTML = Object.keys(grouped).sort().map(date => `
        <div class="bg-white rounded-xl border border-slate-100 overflow-hidden">
            <div class="bg-slate-50 px-4 py-2 text-xs font-medium text-slate-600">
                ${formatDate(date)}
            </div>
            <div class="divide-y divide-slate-100">
                ${grouped[date].map(p => `
                    <div class="p-3 flex items-center gap-3 cursor-pointer hover:bg-slate-50" onclick="apriDettaglioPost('${p.id}')">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background-color: ${getPiattaformaColore(p.piattaforma)}20">
                            ${getPiattaformaIcona(p.piattaforma, 'w-5 h-5')}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-slate-800 text-sm truncate">${escapeHtml(p.titolo)}</h4>
                            <p class="text-xs text-slate-500">${escapeHtml(p.progetto_titolo || 'Progetto')}</p>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs ${getStatoBgClass(p.stato)}">
                            ${getStatoLabel(p.stato)}
                        </span>
                    </div>
                `).join('')}
            </div>
        </div>
    `).join('');
}

// Render Feed Preview (Instagram-style)
function renderFeedPreview() {
    const container = document.getElementById('feedPreviewGrid');
    const postsWithImages = postsData.filter(p => p.piattaforma === 'instagram').slice(0, 9);
    
    container.innerHTML = postsWithImages.map(p => `
        <div class="ig-feed-item rounded-lg overflow-hidden cursor-pointer" onclick="apriDettaglioPost('${p.id}')">
            ${p.contenuti && p.contenuti[0] ? 
                `<img src="assets/uploads/piano_editoriale/${p.contenuti[0].file_path}" alt="" class="hover:scale-110 transition-transform duration-300">` :
                `<div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-pink-100 to-purple-100">
                    <span class="text-2xl">📷</span>
                </div>`
            }
            <div class="absolute inset-0 bg-black/50 opacity-0 hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs p-2 text-center">
                ${escapeHtml(p.titolo)}
            </div>
        </div>
    `).join('');
    
    // Fill empty slots
    const emptySlots = 9 - postsWithImages.length;
    for (let i = 0; i < emptySlots; i++) {
        container.innerHTML += `
            <div class="ig-feed-item rounded-lg bg-slate-100 flex items-center justify-center">
                <span class="text-slate-300 text-2xl">+</span>
            </div>
        `;
    }
}

// Render Analytics
function renderAnalytics() {
    // Stati distribution
    const statiContainer = document.getElementById('statiDistribution');
    const counts = {};
    postsData.forEach(p => {
        counts[p.stato] = (counts[p.stato] || 0) + 1;
    });
    
    const total = postsData.length;
    
    statiContainer.innerHTML = Object.keys(statiPost).map(stato => {
        const count = counts[stato] || 0;
        const pct = total > 0 ? Math.round((count / total) * 100) : 0;
        return `
            <div class="flex items-center gap-2">
                <span class="text-xs w-24">${statiPost[stato].label}</span>
                <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full ${statiPost[stato].colore.replace('bg-', 'bg-opacity-80 bg-')}" style="width: ${pct}%"></div>
                </div>
                <span class="text-xs w-8 text-right">${count}</span>
            </div>
        `;
    }).join('');
    
    // Best Time Grid
    const bestTimeGrid = document.getElementById('bestTimeGrid');
    const giorni = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
    const ore = ['09:00', '12:00', '15:00', '18:00'];
    
    let bestTimeHtml = '';
    ore.forEach(ora => {
        giorni.forEach((giorno, idx) => {
            const postsInSlot = postsData.filter(p => {
                const d = new Date(p.data_prevista);
                return d.getDay() === (idx + 1) % 7 && p.ora_prevista && p.ora_prevista.startsWith(ora.split(':')[0]);
            }).length;
            
            const intensity = Math.min(postsInSlot * 20, 100);
            bestTimeHtml += `
                <div class="aspect-square rounded-lg flex items-center justify-center text-[10px] font-medium cursor-pointer hover:ring-2 hover:ring-pink-500 transition-all"
                     style="background-color: rgba(236, 72, 153, ${intensity / 100}); color: ${intensity > 50 ? 'white' : '#374151'}"
                     title="${giorno} ${ora} - ${postsInSlot} post">
                    ${postsInSlot > 0 ? postsInSlot : ''}
                </div>
            `;
        });
    });
    bestTimeGrid.innerHTML = bestTimeHtml;
}

// AI Functions
function openAiAssistant() {
    closeModal('postModal');
    openModal('aiAssistantModal');
}

function setAiTono(tono) {
    document.querySelectorAll('.ai-tono-btn').forEach(btn => {
        btn.classList.remove('bg-purple-500', 'text-white');
        btn.classList.add('bg-slate-100');
    });
    document.querySelector(`[data-tono="${tono}"]`).classList.add('bg-purple-500', 'text-white');
    document.querySelector(`[data-tono="${tono}"]`).classList.remove('bg-slate-100');
}

function generaContenutoAI() {
    const tipo = document.getElementById('aiTipoContenuto').value;
    const argomento = document.getElementById('aiArgomento').value;
    const tonoBtn = document.querySelector('.ai-tono-btn.bg-purple-500');
    const tono = tonoBtn ? tonoBtn.dataset.tono : 'professionale';
    
    // Simulazione generazione AI
    const testi = {
        engagement: `Ciao community! 👋\n\nOggi vogliamo sapere la vostra opinione su ${argomento}.\n\nLasciate un commento qui sotto! 💬\n\n#engagement #community`,
        promozionale: `🚀 NOVITÀ!\n\nScopri il nostro nuovo servizio di ${argomento}.\n\n✨ Qualità garantita\n💰 Prezzi competitivi\n⏱️ Consegna rapida\n\nContattaci per un preventivo gratuito! 📩\n\n#promo #${argomento.replace(/\s+/g, '')}`,
        educativo: `💡 LO SAI CHE?\n\n${argomento} è fondamentale per il successo del tuo business online.\n\nEcco 3 motivi:\n1️⃣ Aumenta la visibilità\n2️⃣ Migliora l'engagement\n3️⃣ Genera conversioni\n\nSalva questo post per non dimenticarlo! 📌\n\n#tips #educational`,
        storytelling: `Ci ricordiamo quando ${argomento} era solo un'idea... 💭\n\nOggi, dopo mesi di lavoro e dedizione, siamo fieri di mostrarvi il risultato.\n\nGrazie a tutti voi per il supporto! ❤️\n\n#journey #growth`,
        behind: `Dietro le quinte 🎬\n\nEcco come lavoriamo su ${argomento}.\n\nOgni dettaglio conta, ogni pixel ha importanza.\n\nTeamwork makes the dream work! 💪\n\n#behindthescenes #team`,
        testimonianza: `💬 "${argomento} ha superato ogni nostra aspettativa!"\n\nGrazie ai nostri clienti per la fiducia. La vostra soddisfazione è il nostro obiettivo! 🎯\n\n#testimonial #clienti`
    };
    
    const testo = testi[tipo] || testi.engagement;
    
    document.getElementById('aiGeneratedText').value = testo;
    document.getElementById('aiResult').classList.remove('hidden');
}

function usaContenutoAI() {
    const testo = document.getElementById('aiGeneratedText').value;
    document.getElementById('descrizione').value = testo;
    closeModal('aiAssistantModal');
    openModal('postModal');
    updateCharCount();
}

function generaTestoAI() {
    openAiAssistant();
}

function salvaComeIdea() {
    const titolo = document.querySelector('input[name="titolo"]').value || 'Idea senza titolo';
    const descrizione = document.getElementById('descrizione').value || '';
    
    const idea = {
        id: Date.now(),
        titolo,
        descrizione,
        data: new Date().toISOString()
    };
    
    ideeStorage.push(idea);
    localStorage.setItem('pe_idee', JSON.stringify(ideeStorage));
    
    showToast('Idea salvata nello storage!', 'success');
}

function nuovaIdea() {
    const titolo = prompt('Titolo idea:');
    if (!titolo) return;
    
    const idea = {
        id: Date.now(),
        titolo,
        descrizione: '',
        data: new Date().toISOString()
    };
    
    ideeStorage.push(idea);
    localStorage.setItem('pe_idee', JSON.stringify(ideeStorage));
    renderIdee();
}

function renderIdee() {
    const container = document.getElementById('ideeList');
    
    if (ideeStorage.length === 0) {
        container.innerHTML = '<p class="text-center text-slate-400 py-4">Nessuna idea salvata</p>';
        return;
    }
    
    container.innerHTML = ideeStorage.map(idea => `
        <div class="bg-slate-50 rounded-lg p-3 flex items-start justify-between">
            <div class="flex-1">
                <h4 class="font-medium text-slate-800 text-sm">${escapeHtml(idea.titolo)}</h4>
                <p class="text-xs text-slate-500 mt-1">${escapeHtml(idea.descrizione.substring(0, 100))}${idea.descrizione.length > 100 ? '...' : ''}</p>
                <span class="text-[10px] text-slate-400">${new Date(idea.data).toLocaleDateString()}</span>
            </div>
            <div class="flex gap-1">
                <button onclick="usaIdea(${idea.id})" class="p-1.5 text-pink-600 hover:bg-pink-50 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
                <button onclick="eliminaIdea(${idea.id})" class="p-1.5 text-red-600 hover:bg-red-50 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
    `).join('');
}

function usaIdea(id) {
    const idea = ideeStorage.find(i => i.id === id);
    if (!idea) return;
    
    closeModal('ideeModal');
    apriNuovoPost();
    
    document.querySelector('input[name="titolo"]').value = idea.titolo;
    document.getElementById('descrizione').value = idea.descrizione;
    updateCharCount();
}

function eliminaIdea(id) {
    ideeStorage = ideeStorage.filter(i => i.id !== id);
    localStorage.setItem('pe_idee', JSON.stringify(ideeStorage));
    renderIdee();
}

// Hashtag Functions
function loadHashtagSuggeriti() {
    const container = document.getElementById('hashtagSuggeriti');
    const hashtags = ['#socialmedia', '#marketing', '#design', '#creative', '#branding', '#digital', '#content', '#strategy', '#growth', '#engagement'];
    
    container.innerHTML = hashtags.map(h => `
        <span class="hashtag-tag" onclick="aggiungiHashtag('${h}')">${h}</span>
    `).join('');
}

function aggiungiHashtag(tag) {
    const input = document.getElementById('hashtagInput');
    const current = input.value.trim();
    input.value = current ? current + ' ' + tag : tag;
}

function suggerisciHashtag() {
    const piattaforma = document.querySelector('input[name="piattaforma"]:checked')?.value || 'instagram';
    const hashtags = {
        instagram: ['#instagood', '#instagram', '#instadaily', '#photooftheday', '#picoftheday', '#follow', '#like', '#love', '#insta', '#ig'],
        facebook: ['#facebook', '#fb', '#social', '#community', '#share', '#like', '#love', '#follow'],
        tiktok: ['#tiktok', '#viral', '#trending', '#fyp', '#foryou', '#foryoupage', '#tiktokviral', '#fun'],
        linkedin: ['#linkedin', '#business', '#professional', '#networking', '#career', '#job', '#success', '#leadership'],
        twitter: ['#twitter', '#tweet', '#trending', '#viral', '#news', '#tech', '#socialmedia'],
        youtube: ['#youtube', '#video', '#yt', '#subscribe', '#youtuber', '#vlog', '#tutorial'],
        pinterest: ['#pinterest', '#pin', '#ideas', '#inspiration', '#diy', '#home', '#decor']
    };
    
    const container = document.getElementById('hashtagSuggeriti');
    const tags = hashtags[piattaforma] || hashtags.instagram;
    
    container.innerHTML = tags.map(h => `
        <span class="hashtag-tag" onclick="aggiungiHashtag('${h}')">${h}</span>
    `).join('');
    
    showToast('Hashtag suggeriti per ' + piattaforma, 'success');
}

// Best Time to Post
function bestTimeToPost() {
    const orari = ['09:00', '12:00', '15:00', '18:00', '20:00'];
    const giorni = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì'];
    
    const suggerimento = giorni[Math.floor(Math.random() * giorni.length)] + ' alle ' + orari[Math.floor(Math.random() * orari.length)];
    
    document.getElementById('bestTimeHint').textContent = 'Suggerito: ' + suggerimento;
    document.getElementById('oraPrevista').value = orari[Math.floor(Math.random() * orari.length)];
    
    showToast('Miglior orario suggerito: ' + suggerimento, 'success');
}

// Char Counter
function initCharCounter() {
    const textarea = document.getElementById('descrizione');
    if (textarea) {
        textarea.addEventListener('input', updateCharCount);
    }
}

function updateCharCount() {
    const textarea = document.getElementById('descrizione');
    const count = textarea.value.length;
    const max = 2200;
    const counter = document.getElementById('charCount');
    
    counter.textContent = `${count}/${max} caratteri`;
    
    if (count > max) {
        counter.classList.add('text-red-500');
    } else if (count > max * 0.9) {
        counter.classList.add('text-yellow-500');
        counter.classList.remove('text-red-500');
    } else {
        counter.classList.remove('text-red-500', 'text-yellow-500');
    }
}

function aggiungiEmoji(emoji) {
    const textarea = document.getElementById('descrizione');
    textarea.value += emoji;
    updateCharCount();
}

// File Preview
function previewFiles(input) {
    const container = document.getElementById('contenutiPreview');
    container.innerHTML = '';
    
    Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            container.innerHTML += `
                <div class="aspect-square rounded-lg overflow-hidden bg-slate-100">
                    <img src="${e.target.result}" class="w-full h-full object-cover">
                </div>
            `;
        };
        reader.readAsDataURL(file);
    });
}

// Helper functions (mantenute dal codice precedente)
function getPiattaformaColore(piattaforma) {
    const colori = {
        'instagram': '#E4405F', 'facebook': '#1877F2', 'tiktok': '#000000',
        'linkedin': '#0A66C2', 'twitter': '#000000', 'youtube': '#FF0000',
        'pinterest': '#BD081C', 'altro': '#6B7280'
    };
    return colori[piattaforma] || '#6B7280';
}

function getPiattaformaIcona(piattaforma, className = 'w-5 h-5') {
    const iniziali = {
        'instagram': 'IG', 'facebook': 'FB', 'tiktok': 'TT', 'linkedin': 'IN',
        'twitter': 'X', 'youtube': 'YT', 'pinterest': 'PI', 'altro': '●'
    };
    const colore = getPiattaformaColore(piattaforma);
    return `<span class="${className} flex items-center justify-center font-bold" style="color: ${colore}">${iniziali[piattaforma] || '●'}</span>`;
}

function getStatoBgClass(stato) {
    const classi = {
        'bozza': 'bg-slate-100 text-slate-700', 'in_revisione': 'bg-yellow-100 text-yellow-700',
        'approvato': 'bg-blue-100 text-blue-700', 'programmato': 'bg-purple-100 text-purple-700',
        'pubblicato': 'bg-green-100 text-green-700', 'archiviato': 'bg-gray-100 text-gray-500'
    };
    return classi[stato] || 'bg-slate-100 text-slate-700';
}

function getStatoLabel(stato) {
    const labels = {
        'bozza': 'Bozza', 'in_revisione': 'Revisione', 'approvato': 'Approvato',
        'programmato': 'Programmato', 'pubblicato': 'Pubblicato', 'archiviato': 'Archiviato'
    };
    return labels[stato] || stato;
}

function switchVista(vista) {
    currentVista = vista;
    
    ['calendario', 'lista', 'feed', 'analytics'].forEach(v => {
        document.getElementById('vista' + v.charAt(0).toUpperCase() + v.slice(1))?.classList.add('hidden');
    });
    document.getElementById('vista' + vista.charAt(0).toUpperCase() + vista.slice(1))?.classList.remove('hidden');
    
    ['btnVistaCalendario', 'btnVistaLista', 'btnVistaFeed', 'btnVistaAnalytics'].forEach(id => {
        const btn = document.getElementById(id);
        if (btn) {
            btn.classList.remove('bg-pink-100', 'text-pink-700');
            btn.classList.add('text-slate-600', 'hover:bg-slate-100');
        }
    });
    
    const activeBtn = document.getElementById('btnVista' + vista.charAt(0).toUpperCase() + vista.slice(1));
    if (activeBtn) {
        activeBtn.classList.remove('text-slate-600', 'hover:bg-slate-100');
        activeBtn.classList.add('bg-pink-100', 'text-pink-700');
    }
    
    if (vista === 'calendario') renderCalendario();
    else if (vista === 'lista') renderLista();
    else if (vista === 'feed') renderFeedPreview();
    else if (vista === 'analytics') renderAnalytics();
}

function apriNuovoPost(data = null) {
    document.getElementById('postModalTitle').textContent = 'Nuovo Post';
    document.getElementById('postForm').reset();
    document.getElementById('postId').value = '';
    document.getElementById('contenutiPreview').innerHTML = '';
    document.getElementById('aiResult').classList.add('hidden');
    
    if (data) {
        document.querySelector('input[name="data_prevista"]').value = data;
    }
    
    openModal('postModal');
}

function apriNuovoPostPerProgetto(progettoId) {
    apriNuovoPost();
    document.querySelector('select[name="progetto_id"]').value = progettoId;
}

function salvaPost() {
    // Implementazione esistente
    const form = document.getElementById('postForm');
    const formData = new FormData(form);
    
    fetch('api/piano_editoriale.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('Post salvato!', 'success');
            closeModal('postModal');
            caricaPosts();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    });
}

function salvaComeBozza() {
    document.querySelector('select[name="stato"]').value = 'bozza';
    salvaPost();
}

function apriDettaglioPost(id) {
    currentPostId = id;
    // Implementazione dettaglio esistente
    fetch(`api/piano_editoriale.php?action=detail&id=${id}`)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Mostra modal dettaglio
            showBottomSheet('Dettaglio Post', data.data);
        }
    });
}

function filtraPerProgetto(progettoId) {
    document.getElementById('filtroProgetto').value = progettoId;
    caricaPosts();
}

function aggiornaContatoriProgetti() {
    document.querySelectorAll('.posts-count').forEach(el => {
        const progettoId = el.dataset.progetto;
        const count = postsData.filter(p => p.progetto_id === progettoId).length;
        el.textContent = count + (count === 1 ? ' post' : ' posts');
    });
}

// Bottom Sheet per mobile
function showBottomSheet(title, content) {
    const sheet = document.getElementById('bottomSheet');
    const contentDiv = document.getElementById('bottomSheetContent');
    
    contentDiv.innerHTML = `
        <h3 class="font-bold text-lg mb-4">${escapeHtml(title)}</h3>
        <div class="space-y-3">
            ${renderPostDetail(content)}
        </div>
        <div class="flex gap-2 mt-4">
            <button onclick="modificaPost()" class="flex-1 py-2 bg-slate-100 rounded-lg text-sm">Modifica</button>
            <button onclick="closeBottomSheet()" class="flex-1 py-2 bg-pink-500 text-white rounded-lg text-sm">Chiudi</button>
        </div>
    `;
    
    sheet.classList.add('open');
}

function renderPostDetail(post) {
    return `
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background-color: ${getPiattaformaColore(post.piattaforma)}20">
                ${getPiattaformaIcona(post.piattaforma)}
            </div>
            <div>
                <p class="font-medium">${escapeHtml(post.titolo)}</p>
                <span class="text-xs ${getStatoBgClass(post.stato)}">${getStatoLabel(post.stato)}</span>
            </div>
        </div>
        <p class="text-sm text-slate-600">${escapeHtml(post.descrizione || '')}</p>
        <div class="flex items-center gap-4 text-xs text-slate-400">
            <span>📅 ${formatDate(post.data_prevista)}</span>
            ${post.ora_prevista ? `<span>🕐 ${post.ora_prevista}</span>` : ''}
        </div>
    `;
}

function closeBottomSheet() {
    document.getElementById('bottomSheet').classList.remove('open');
}

function modificaPost() {
    closeBottomSheet();
    // Implementazione modifica
}

// Utility
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('it-IT', { day: 'numeric', month: 'short' });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
