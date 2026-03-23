<?php
/**
 * Eterea Gestionale
 * Lista Progetti
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Progetti';

// Carica clienti per filtro
$clienti = [];
try {
    $stmt = $pdo->query("SELECT id, ragione_sociale FROM clienti ORDER BY ragione_sociale ASC");
    $clienti = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Errore caricamento clienti: " . $e->getMessage());
}

include __DIR__ . '/includes/header.php';
?>

<!-- Header con filtri -->
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Progetti</h1>
            <p class="text-sm text-slate-500 mt-1">Gestisci tutti i progetti dello studio</p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
            <!-- Toggle Raggruppamento Cliente -->
            <label class="flex items-center justify-between sm:justify-start gap-2 cursor-pointer group bg-white px-3 py-2 rounded-lg border border-slate-200 hover:border-cyan-300 transition-colors">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span class="text-sm text-slate-600 group-hover:text-slate-800">Raggruppa</span>
                </div>
                <div class="relative">
                    <input type="checkbox" id="groupByClienteToggle" class="sr-only peer" onchange="toggleGroupByCliente()">
                    <div class="w-8 h-4 sm:w-9 sm:h-5 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-cyan-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 sm:after:h-4 sm:after:w-4 after:transition-all peer-checked:bg-cyan-600"></div>
                </div>
            </label>
            
            <button onclick="openModal('progettoModal')" 
                    class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2.5 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors min-h-[44px]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuovo Progetto
            </button>
            
            <button onclick="toggleArchiviati()" 
                    id="btnArchiviati"
                    class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-4 py-2.5 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors min-h-[44px]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                <span id="txtArchiviati">Mostra Archiviati</span>
            </button>
            
            <button onclick="togglePipeline()" 
                    id="btnPipeline"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors min-h-[44px]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
                <span>Pipeline</span>
            </button>
        </div>
    </div>
</div>

<!-- Filtri -->
<div id="filtriSection" class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
    <!-- Mobile: Accordion Header -->
    <button type="button" id="filtriMobileBtn" onclick="toggleFiltriMobile()" class="sm:hidden w-full flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-slate-700 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            Filtri
        </span>
        <svg id="filtriArrow" class="w-4 h-4 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    
    <!-- Filtri Grid -->
    <div id="filtriContainer" class="hidden sm:block">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Ricerca -->
            <div class="relative">
                <input type="text" id="searchInput" placeholder="Cerca progetto..."
                       class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none min-h-[44px]">
                <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            
            <!-- Filtro Stato -->
            <select id="statoFilter" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                <option value="">Tutti gli stati</option>
                <?php foreach (STATI_PROGETTO as $key => $label): ?>
                <option value="<?php echo e($key); ?>"><?php echo e($label); ?></option>
                <?php endforeach; ?>
            </select>
            
            <!-- Filtro Cliente -->
            <select id="clienteFilter" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                <option value="">Tutti i clienti</option>
                <?php foreach ($clienti as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo e($c['ragione_sociale']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <!-- Filtro Partecipante -->
            <select id="partecipanteFilter" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                <option value="">Tutti i partecipanti</option>
                <?php foreach (USERS as $id => $u): ?>
                <option value="<?php echo $id; ?>"><?php echo e($u['nome']); ?></option>
                <?php endforeach; ?>
            </select>
            
            <!-- Filtro Colore -->
            <select id="coloreFilter" onchange="loadProgetti()" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px] bg-white">
                <option value="">Tutti i colori</option>
                <?php
                $coloriTag = [
                    '#FFFFFF' => ['nome' => 'Bianco'],
                    '#BAE6FD' => ['nome' => 'Ciano'],
                    '#BFDBFE' => ['nome' => 'Blu'],
                    '#BBF7D0' => ['nome' => 'Verde'],
                    '#D9F99D' => ['nome' => 'Lime'],
                    '#FDE68A' => ['nome' => 'Giallo'],
                    '#FED7AA' => ['nome' => 'Arancione'],
                    '#FECACA' => ['nome' => 'Rosso'],
                    '#FBCFE8' => ['nome' => 'Rosa'],
                    '#E9D5FF' => ['nome' => 'Viola'],
                    '#C4B5FD' => ['nome' => 'Indaco'],
                    '#CBD5E1' => ['nome' => 'Grigio'],
                    '#99F6E4' => ['nome' => 'Turchese'],
                    '#F5D0FE' => ['nome' => 'Fucsia'],
                ];
                foreach ($coloriTag as $hex => $info): ?>
                <option value="<?php echo e($hex); ?>"><span class="inline-block w-3 h-3 rounded-sm mr-1 align-middle" style="background-color: <?php echo e($hex); ?>"></span> <?php echo e($info['nome']); ?></option>
                <?php endforeach; ?>
            </select>
            </select>
        </div>
    </div>
</div>

<!-- Stili per Card Stack Orizzontale -->
<style>
/* Fix per modal su mobile - gestione safe area e viewport */
@media (max-width: 640px) {
    #progettoModal {
        /* Supporto per safe area su iOS */
        padding-bottom: env(safe-area-inset-bottom, 0);
    }
    
    #progettoModal > div:last-child {
        /* Spazio per la bottom nav (64px) + safe area */
        max-height: calc(100vh - 80px - env(safe-area-inset-bottom, 0));
    }
    
    /* Assicura che il contenuto sia scrollabile */
    #progettoModal form {
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-y: contain;
    }
}

/* Container del gruppo cliente */
.cliente-group {
    background: linear-gradient(to bottom, #f8fafc 0%, #ffffff 100%);
    border-radius: 1rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

/* Container dello stack */
.card-stack-container {
    position: relative;
    width: 100%;
}

/* Stack orizzontale (stile carte di credito) */
.card-stack {
    display: flex;
    gap: 0;
    position: relative;
    min-height: 320px;
    padding: 10px 0;
}

/* Card nello stack */
.card-stack-item {
    flex-shrink: 0;
    width: 320px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    box-shadow: -4px 4px 12px rgba(0,0,0,0.15), -2px 0 8px rgba(0,0,0,0.1);
}

/* Stato collapsed: carte sovrapposte orizzontalmente */
.card-stack-container.collapsed .card-stack-item {
    margin-left: -220px; /* Sovrapposizione significativa */
}

.card-stack-container.collapsed .card-stack-item:first-child {
    margin-left: 0;
}

/* Effetto profondità per carte sovrapposte */
.card-stack-container.collapsed .card-stack-item:nth-child(1) { z-index: 10; transform: translateY(0); }
.card-stack-container.collapsed .card-stack-item:nth-child(2) { z-index: 9; transform: translateY(2px); }
.card-stack-container.collapsed .card-stack-item:nth-child(3) { z-index: 8; transform: translateY(4px); }
.card-stack-container.collapsed .card-stack-item:nth-child(4) { z-index: 7; transform: translateY(6px); }
.card-stack-container.collapsed .card-stack-item:nth-child(5) { z-index: 6; transform: translateY(8px); }
.card-stack-container.collapsed .card-stack-item:nth-child(6) { z-index: 5; transform: translateY(10px); }
.card-stack-container.collapsed .card-stack-item:nth-child(n+7) { z-index: 4; transform: translateY(12px); opacity: 0.9; }

/* Hover su card in stack: sporgi leggermente */
.card-stack-container.collapsed .card-stack-item:hover {
    transform: translateY(-10px) translateX(10px);
    z-index: 20;
}

/* Stato expanded: card affiancate con gap */
.card-stack-container.expanded .card-stack {
    flex-wrap: wrap;
    gap: 1rem;
}

.card-stack-container.expanded .card-stack-item {
    margin-left: 0 !important;
    transform: none !important;
    opacity: 1 !important;
    z-index: 1 !important;
}

/* Pulsante espandi/comprimi */
.stack-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    color: #475569;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.2s;
    white-space: nowrap;
}

.stack-toggle-btn:hover {
    border-color: #0891b2;
    color: #0891b2;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stack-toggle-btn svg {
    transition: transform 0.3s;
}

.card-stack-container.expanded ~ .stack-toggle-btn svg,
.card-stack-container.expanded + * .stack-toggle-btn svg {
    transform: rotate(180deg);
}

/* Scroll orizzontale per mobile */
@media (max-width: 768px) {
    .card-stack-container.collapsed {
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    
    .card-stack-container.collapsed::-webkit-scrollbar {
        display: none;
    }
    
    .card-stack-container.collapsed .card-stack {
        min-height: 300px;
    }
    
    .card-stack-item {
        width: 280px;
    }
    
    .card-stack-container.collapsed .card-stack-item {
        margin-left: -180px;
    }
    
    .stack-toggle-btn {
        margin-top: 0.5rem;
        width: 100%;
        justify-content: center;
    }
    
    /* Aumenta area touch */
    .card-stack-item button,
    .card-stack-item a {
        min-height: 44px;
        min-width: 44px;
    }
}

/* Per schermi molto piccoli */
@media (max-width: 480px) {
    .card-stack-item {
        width: 260px;
    }
    
    .card-stack-container.collapsed .card-stack-item {
        margin-left: -160px;
    }
}

/* ============================================
   PIPELINE WORKFLOW STILE n8n - LIGHT MODE
   ============================================ */

#pipelineContainer {
    overflow: hidden;
    cursor: grab;
}

#pipelineContainer:active {
    cursor: grabbing;
}

#pipelineCanvas {
    transform-origin: center top;
    transition: transform 0.1s ease-out;
}

.pipeline-node {
    width: 320px;
    background: #ffffff;
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
    position: absolute;
    cursor: grab;
    user-select: none;
    /* Ottimizzazione GPU */
    backface-visibility: hidden;
    -webkit-font-smoothing: antialiased;
}

.pipeline-node:hover {
    border-color: #cbd5e1;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15), 0 4px 6px -2px rgba(0, 0, 0, 0.1);
}

.pipeline-node.dragging {
    cursor: grabbing;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(59, 130, 246, 0.5);
    z-index: 1000;
    border-color: #3b82f6;
    /* Nessuna transition durante il drag per massima fluidità */
    transition: none;
}

.node-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.node-header svg {
    flex-shrink: 0;
}

.node-count {
    margin-left: auto;
    background: rgba(255,255,255,0.25);
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 12px;
    min-width: 28px;
    text-align: center;
}

.node-content {
    padding: 12px;
    max-height: 400px;
    overflow-y: auto;
}

/* Mini card progetto per pipeline */
.pipeline-project-card {
    background: #f8fafc;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid #e2e8f0;
}

.pipeline-project-card:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
    transform: translateX(4px);
}

.pipeline-project-card:last-child {
    margin-bottom: 0;
}

.pipeline-project-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

.pipeline-project-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.pipeline-project-title {
    color: #1e293b;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pipeline-project-client {
    color: #64748b;
    font-size: 11px;
    margin-left: 16px;
    margin-bottom: 6px;
}

.pipeline-project-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-left: 16px;
    font-size: 11px;
    color: #94a3b8;
}

.pipeline-project-price {
    color: #059669;
    font-weight: 600;
}

/* Badge stato pagamento */
.pipeline-project-payment {
    margin-left: 16px;
}

.pipeline-project-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
}

.payment-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 9999px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
    white-space: nowrap;
}

/* Avatars partecipanti */
.pipeline-project-avatars {
    display: flex;
    align-items: center;
    gap: -4px;
}

.pipeline-avatar {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 700;
    color: white;
    border: 2px solid white;
    margin-left: -6px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.pipeline-avatar:first-child {
    margin-left: 0;
}

.pipeline-avatar-more {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    font-weight: 600;
    color: #64748b;
    background-color: #e2e8f0;
    border: 2px solid white;
    margin-left: -6px;
}

/* Connessioni SVG */
.pipeline-connection {
    fill: none;
    stroke: #94a3b8;
    stroke-width: 2;
    /* Transizione fluida quando i nodi si muovono */
    transition: d 0.1s ease-out;
}

.pipeline-connection.active {
    stroke: #0891b2;
    stroke-width: 3;
}

/* Animazione per nuove connessioni */
@keyframes connectionDraw {
    from {
        stroke-dasharray: 1000;
        stroke-dashoffset: 1000;
    }
    to {
        stroke-dasharray: 1000;
        stroke-dashoffset: 0;
    }
}

.pipeline-connection.animate {
    animation: connectionDraw 0.3s ease-out;
}

/* Controlli Zoom */
.pipeline-controls {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    z-index: 100;
    background: white;
    padding: 8px;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e2e8f0;
}

.pipeline-controls button {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: white;
    color: #475569;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.pipeline-controls button:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #1e293b;
}

.pipeline-controls button:active {
    transform: scale(0.95);
}

.pipeline-zoom-level {
    text-align: center;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    padding: 4px 0;
}

/* Custom scrollbar per nodi */
.node-content::-webkit-scrollbar {
    width: 4px;
}

.node-content::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.node-content::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

/* Responsive pipeline */
@media (max-width: 1024px) {
    .pipeline-node {
        width: 280px;
    }
}

@media (max-width: 768px) {
    #pipelineContainer {
        overflow-x: auto;
    }
    
    #pipelineContainer > .relative {
        min-width: 800px;
    }
    
    .pipeline-node {
        width: 260px;
    }
}
</style>

<!-- Lista Progetti -->
<div id="progettiContainer" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <div class="col-span-full text-center py-12">
        <div class="animate-spin w-8 h-8 border-2 border-cyan-500 border-t-transparent rounded-full mx-auto"></div>
        <p class="text-slate-500 mt-2">Caricamento progetti...</p>
    </div>
</div>

<!-- Vista Pipeline (Workflow Stile n8n) - Light Mode ORIZZONTALE -->
<div id="pipelineContainer" class="hidden relative" style="height: calc(100vh - 200px); min-height: 600px; background-color: #f8f9fa; background-image: radial-gradient(#d1d5db 1px, transparent 1px); background-size: 20px 20px; overflow: hidden;">
    
    <!-- Canvas per Pan & Zoom -->
    <div id="pipelineCanvas" style="position: absolute; width: 3000px; height: 1200px; left: 50px; top: 50px; transform-origin: left top;">
        <!-- SVG per le connessioni -->
        <svg id="pipelineConnections" style="position: absolute; width: 100%; height: 100%; pointer-events: none; z-index: 1;">
            <defs>
                <marker id="arrowhead" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto">
                    <polygon points="0 0, 10 3, 0 6" fill="#94a3b8" />
                </marker>
            </defs>
        </svg>
        
        <!-- Ghost node per feedback drag (inizialmente nascosto) -->
        <div id="pipelineGhost" class="pipeline-node" style="display: none; opacity: 0.4; border-style: dashed; z-index: 99;"></div>
        
        <!-- Nodi posizionati assolutamente - Layout ORIZZONTALE -->
        <!-- 
            Layout:
            [DA INIZIARE] → [IN CORSO] → [IN CONSEGNA] → [COMPLETATI] → [ARCHIVIATI]
                                 ↑
                           [IN PAUSA]
        -->
        
        <!-- Col 1: Da Iniziare -->
        <div id="node-da_iniziare" class="pipeline-node" style="left: 50px; top: 300px;">
            <div class="node-header bg-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Da Iniziare</span>
                <span class="node-count" id="count-da_iniziare">0</span>
            </div>
            <div id="pipeline-da_iniziare" class="node-content"></div>
        </div>
        
        <!-- Col 2: In Corso -->
        <div id="node-in_corso" class="pipeline-node" style="left: 450px; top: 300px;">
            <div class="node-header bg-cyan-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>In Corso</span>
                <span class="node-count" id="count-in_corso">0</span>
            </div>
            <div id="pipeline-in_corso" class="node-content"></div>
        </div>
        
        <!-- Col 2 (sopra): In Pausa -->
        <div id="node-in_pausa" class="pipeline-node" style="left: 450px; top: 50px;">
            <div class="node-header bg-amber-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>In Pausa</span>
                <span class="node-count" id="count-in_pausa">0</span>
            </div>
            <div id="pipeline-in_pausa" class="node-content"></div>
        </div>
        
        <!-- Col 3: Completati -->
        <div id="node-completato" class="pipeline-node" style="left: 850px; top: 300px;">
            <div class="node-header bg-purple-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Completati</span>
                <span class="node-count" id="count-completato">0</span>
            </div>
            <div id="pipeline-completato" class="node-content"></div>
        </div>
        
        <!-- Col 4: Consegnato (progetti con tag/stato consegnato) -->
        <div id="node-consegnato" class="pipeline-node" style="left: 1250px; top: 300px;">
            <div class="node-header bg-emerald-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>Consegnato</span>
                <span class="node-count" id="count-consegnato">0</span>
            </div>
            <div id="pipeline-consegnato" class="node-content"></div>
        </div>
        
        <!-- Col 5: Archiviati -->
        <div id="node-archiviato" class="pipeline-node" style="left: 1650px; top: 300px;">
            <div class="node-header bg-gray-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                <span>Archiviati</span>
                <span class="node-count" id="count-archiviato">0</span>
            </div>
            <div id="pipeline-archiviato" class="node-content"></div>
        </div>
    </div>
    
    <!-- Controlli Zoom -->
    <div class="pipeline-controls">
        <button onclick="pipelineZoomIn()" title="Zoom In">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
        </button>
        <div class="pipeline-zoom-level" id="zoomLevel">100%</div>
        <button onclick="pipelineZoomOut()" title="Zoom Out">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
            </svg>
        </button>
        <button onclick="pipelineResetView()" title="Reset View">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
            </svg>
        </button>
    </div>
</div>
</div>

<!-- Modal Nuovo/Edit Progetto -->
<div id="progettoModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModal('progettoModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-2xl sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[85vh] sm:max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-4 sm:p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h2 class="text-lg sm:text-xl font-bold text-slate-800" id="modalTitle">Nuovo Progetto</h2>
                <button onclick="closeModal('progettoModal')" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 min-h-[44px] min-w-[44px] flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="progettoForm" class="p-4 sm:p-6 overflow-y-auto flex-1">
                <input type="hidden" name="id" id="progettoId">
                
                <div class="space-y-5">
                    <!-- Titolo -->
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Titolo *</label>
                        <input type="text" name="titolo" required
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]"
                               placeholder="Nome del progetto">
                    </div>
                    
                    <!-- Cliente -->
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Cliente</label>
                        <select name="cliente_id" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                            <option value="">Seleziona cliente...</option>
                            <?php foreach ($clienti as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo e($c['ragione_sociale']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Descrizione -->
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Descrizione</label>
                        <textarea name="descrizione" rows="3"
                                  class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none"
                                  placeholder="Descrizione del progetto..."></textarea>
                    </div>
                    
                    <!-- Note -->
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Note</label>
                        <textarea name="note" rows="3"
                                  class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none"
                                  placeholder="Note interne sul progetto..."></textarea>
                        <p class="text-xs text-slate-500 mt-1">Note visibili solo ai membri del team</p>
                    </div>
                    
                    <!-- Tipologie -->
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Tipologie</label>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (TIPOLOGIE_PROGETTO as $tipo): ?>
                            <label class="inline-flex items-center px-3 py-1.5 rounded-full border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors has-[:checked]:bg-cyan-50 has-[:checked]:border-cyan-500 has-[:checked]:text-cyan-700">
                                <input type="checkbox" name="tipologie[]" value="<?php echo $tipo; ?>" class="sr-only">
                                <span class="text-xs sm:text-sm"><?php echo e($tipo); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Prezzo e Stati -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Prezzo Totale</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-slate-400">€</span>
                                <input type="number" name="prezzo_totale" step="0.01" min="0"
                                       class="w-full pl-8 pr-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]"
                                       placeholder="0.00">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Stato Progetto</label>
                            <select name="stato_progetto" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                                <?php foreach (STATI_PROGETTO as $key => $label): ?>
                                <option value="<?php echo e($key); ?>" <?php echo $key === 'da_iniziare' ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Stato Pagamento</label>
                            <select name="stato_pagamento" id="statoPagamentoSelect" onchange="toggleAccontoPercentuale()" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                                <?php foreach (STATI_PAGAMENTO as $key => $label): ?>
                                <option value="<?php echo e($key); ?>" <?php echo $key === 'da_pagare' ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Percentuale Acconto (mostrata solo se stato = da_pagare_acconto) -->
                    <div id="accontoPercentualeWrapper" class="hidden">
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Percentuale Acconto (%)</label>
                        <input type="number" name="acconto_percentuale" id="accontoPercentuale" min="0" max="100" step="1" placeholder="Es: 30"
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                    </div>
                    
                    <!-- Pagamento Ricorrente (mostrato solo se stato = mensile) -->
                    <div id="pagamentoRicorrenteWrapper" class="hidden border border-slate-200 rounded-xl p-4 bg-slate-50/50">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h3 class="font-medium text-slate-800">Configurazione Pagamento Ricorrente</h3>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-2">Importo per pagamento (€)</label>
                                <input type="number" name="importo_ricorrente" id="importoRicorrente" step="0.01" min="0"
                                       class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                                       placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-2">Frequenza</label>
                                <select name="frequenza_ricorrente" id="frequenzaRicorrente" 
                                        class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                                    <option value="settimanale">Settimanale</option>
                                    <option value="mensile" selected>Mensile</option>
                                    <option value="bimestrale">Bimestrale</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-xs font-medium text-slate-700 mb-2">Prossima data pagamento</label>
                            <input type="date" name="prossima_data_ricorrente" id="prossimaDataRicorrente"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                        </div>
                        
                        <!-- Distribuzione percentuale -->
                        <div class="border-t border-slate-200 pt-4">
                            <label class="block text-xs font-medium text-slate-700 mb-3">Distribuzione importo</label>
                            <p class="text-xs text-slate-500 mb-3">Definisci come dividere l'importo (deve sommare a 100%)</p>
                            
                            <div class="space-y-2" id="distribuzioneRicorrenteContainer">
                                <?php foreach (USERS as $uid => $user): ?>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-medium" style="background-color: <?php echo $user['colore']; ?>">
                                        <?php echo substr($user['nome'], 0, 1); ?>
                                    </div>
                                    <span class="text-sm text-slate-700 flex-1"><?php echo e($user['nome']); ?></span>
                                    <div class="relative w-24">
                                        <input type="number" name="distribuzione_ricorrente[<?php echo $uid; ?>]" 
                                               class="distribuzione-ricorrente-input w-full px-3 py-2 border border-slate-200 rounded-lg text-right text-sm"
                                               placeholder="0" min="0" max="100" value="30"
                                               onchange="validaDistribuzioneRicorrente()">
                                        <span class="absolute right-3 top-2 text-slate-400 text-sm">%</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-medium bg-emerald-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm text-slate-700 flex-1">Cassa Aziendale</span>
                                    <div class="relative w-24">
                                        <input type="number" name="distribuzione_ricorrente[cassa]" 
                                               class="distribuzione-ricorrente-input w-full px-3 py-2 border border-slate-200 rounded-lg text-right text-sm"
                                               placeholder="0" min="0" max="100" value="10"
                                               onchange="validaDistribuzioneRicorrente()">
                                        <span class="absolute right-3 top-2 text-slate-400 text-sm">%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-200">
                                <span class="text-sm text-slate-600">Totale:</span>
                                <span id="totaleDistribuzioneRicorrente" class="text-sm font-semibold text-slate-800">100%</span>
                            </div>
                            <p id="erroreDistribuzioneRicorrente" class="text-xs text-red-500 mt-1 hidden">La somma deve essere 100%</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    </div>
                    
                    <!-- Partecipanti -->
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Partecipanti</label>
                        <div class="flex flex-wrap gap-3">
                            <?php foreach (USERS as $id => $u): ?>
                            <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors has-[:checked]:bg-slate-100 has-[:checked]:border-slate-300">
                                <input type="checkbox" name="partecipanti[]" value="<?php echo $id; ?>" class="rounded text-cyan-600 focus:ring-cyan-500">
                                <span class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-medium" style="background-color: <?php echo $u['colore']; ?>">
                                    <?php echo substr($u['nome'], 0, 1); ?>
                                </span>
                                <span class="text-xs sm:text-sm"><?php echo e($u['nome']); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Tag Colore -->
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Tag Colore Progetto</label>
                        <div class="flex flex-wrap gap-2">
                            <?php
                            $coloriTag = [
                                '#FFFFFF' => ['nome' => 'Bianco'],
                                '#BAE6FD' => ['nome' => 'Ciano'],
                                '#BFDBFE' => ['nome' => 'Blu'],
                                '#BBF7D0' => ['nome' => 'Verde'],
                                '#D9F99D' => ['nome' => 'Lime'],
                                '#FDE68A' => ['nome' => 'Giallo'],
                                '#FED7AA' => ['nome' => 'Arancione'],
                                '#FECACA' => ['nome' => 'Rosso'],
                                '#FBCFE8' => ['nome' => 'Rosa'],
                                '#E9D5FF' => ['nome' => 'Viola'],
                                '#C4B5FD' => ['nome' => 'Indaco'],
                                '#CBD5E1' => ['nome' => 'Grigio'],
                                '#99F6E4' => ['nome' => 'Turchese'],
                                '#F5D0FE' => ['nome' => 'Fucsia'],
                            ];
                            foreach ($coloriTag as $hex => $info): ?>
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="colore_tag" value="<?php echo $hex; ?>" 
                                       class="peer sr-only" <?php echo $hex === '#FFFFFF' ? 'checked' : ''; ?>>
                                <div class="w-10 h-10 rounded-lg border-2 border-slate-200 peer-checked:border-cyan-500 peer-checked:ring-2 peer-checked:ring-cyan-200 transition-all"
                                     style="background-color: <?php echo $hex; ?>;"
                                     title="<?php echo $info['nome']; ?>">
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Il colore verrà applicato come sfondo della card progetto</p>
                    </div>
                    
                    <!-- Date -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Data Inizio</label>
                            <input type="date" name="data_inizio"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Data Consegna Prevista</label>
                            <input type="date" name="data_consegna_prevista"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                        </div>
                    </div>
                </div>
            </form>
            
            <div class="p-3 sm:p-6 border-t border-slate-100 flex flex-row justify-end gap-2 sticky bottom-0 bg-white z-10">
                <button type="button" onclick="closeModal('progettoModal')" 
                        class="px-3 py-2 sm:px-4 sm:py-2 text-sm sm:text-base text-slate-600 hover:text-slate-800 font-medium rounded-lg hover:bg-slate-100 transition-colors">
                    Annulla
                </button>
                <button type="button" onclick="saveProgetto()" 
                        class="px-4 py-2 sm:px-6 sm:py-2 text-sm sm:text-base bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium transition-colors">
                    Salva
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Costanti PHP passate a JavaScript
const COLORI_STATO_PROGETTO = <?php echo json_encode(COLORI_STATO_PROGETTO); ?>;
const COLORI_STATO_PAGAMENTO = <?php echo json_encode(COLORI_STATO_PAGAMENTO); ?>;
const STATI_PROGETTO = <?php echo json_encode(STATI_PROGETTO); ?>;
const STATI_PAGAMENTO = <?php echo json_encode(STATI_PAGAMENTO); ?>;
const USERS = <?php echo json_encode(USERS); ?>;

let progettiData = [];
let mostraArchiviati = false;

// Toggle filtri mobile
function toggleFiltriMobile() {
    const container = document.getElementById('filtriContainer');
    const arrow = document.getElementById('filtriArrow');
    if (container.classList.contains('hidden')) {
        container.classList.remove('hidden');
        arrow.style.transform = 'rotate(180deg)';
    } else {
        container.classList.add('hidden');
        arrow.style.transform = 'rotate(0deg)';
    }
}

// Toggle archiviati
function toggleArchiviati() {
    mostraArchiviati = !mostraArchiviati;
    
    const btn = document.getElementById('btnArchiviati');
    const txt = document.getElementById('txtArchiviati');
    
    if (mostraArchiviati) {
        btn.classList.remove('bg-slate-200', 'text-slate-700');
        btn.classList.add('bg-amber-600', 'text-white', 'hover:bg-amber-700');
        txt.textContent = 'Mostra Attivi';
    } else {
        btn.classList.remove('bg-amber-600', 'text-white', 'hover:bg-amber-700');
        btn.classList.add('bg-slate-200', 'text-slate-700');
        txt.textContent = 'Mostra Archiviati';
    }
    
    loadProgetti();
}

// Toggle vista Pipeline
let vistaPipeline = false;

function togglePipeline() {
    vistaPipeline = !vistaPipeline;
    
    const btn = document.getElementById('btnPipeline');
    const progettiContainer = document.getElementById('progettiContainer');
    const pipelineContainer = document.getElementById('pipelineContainer');
    const filtriSection = document.getElementById('filtriSection');
    
    if (vistaPipeline) {
        btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
        btn.classList.add('bg-slate-600', 'hover:bg-slate-700');
        progettiContainer.classList.add('hidden');
        pipelineContainer.classList.remove('hidden');
        // Nascondi barra filtri in modalità pipeline
        if (filtriSection) filtriSection.classList.add('hidden');
        // Reset view per layout orizzontale
        pipelineResetView();
        renderPipeline();
    } else {
        btn.classList.remove('bg-slate-600', 'hover:bg-slate-700');
        btn.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
        progettiContainer.classList.remove('hidden');
        pipelineContainer.classList.add('hidden');
        // Mostra barra filtri tornando alla griglia
        if (filtriSection) filtriSection.classList.remove('hidden');
    }
}

// Renderizza vista Pipeline (Workflow n8n style) - ORIZZONTALE con Archiviati
function renderPipeline() {
    // Tutti gli stati inclusi archiviati
    const stati = ['da_iniziare', 'in_corso', 'in_pausa', 'completato', 'consegnato', 'archiviato'];
    
    // Svuota tutti i nodi
    stati.forEach(stato => {
        const colonna = document.getElementById(`pipeline-${stato}`);
        if (colonna) colonna.innerHTML = '';
        const counter = document.getElementById(`count-${stato}`);
        if (counter) counter.textContent = '0';
    });
    
    // Raggruppa TUTTI i progetti per stato (non filtriamo più qui)
    const progettiPerStato = {};
    stati.forEach(stato => progettiPerStato[stato] = []);
    
    allProgetti.forEach(p => {
        const stato = p.stato_progetto || 'da_iniziare';
        if (progettiPerStato[stato]) {
            progettiPerStato[stato].push(p);
        }
    });
    
    // Renderizza card per ogni nodo
    stati.forEach(stato => {
        const colonna = document.getElementById(`pipeline-${stato}`);
        const counter = document.getElementById(`count-${stato}`);
        if (!colonna) return;
        
        counter.textContent = progettiPerStato[stato].length;
        
        progettiPerStato[stato].forEach(p => {
            const card = createPipelineCard(p);
            colonna.appendChild(card);
        });
    });
    
    // Disegna le connessioni dopo che il DOM è aggiornato
    setTimeout(drawPipelineConnections, 100);
}

// Crea card progetto per pipeline workflow
function createPipelineCard(p) {
    const div = document.createElement('div');
    div.className = 'pipeline-project-card';
    div.onclick = () => window.location.href = `progetto_dettaglio.php?id=${p.id}`;
    
    const colore = p.colore_tag || '#10b981';
    const scadenza = p.data_consegna_prevista 
        ? new Date(p.data_consegna_prevista).toLocaleDateString('it-IT', {day: '2-digit', month: '2-digit'})
        : 'N/D';
    const prezzo = p.prezzo_totale 
        ? '€' + parseFloat(p.prezzo_totale).toLocaleString('it-IT', {minimumFractionDigits: 0, maximumFractionDigits: 0})
        : '-';
    
    // Stato pagamento
    const statoPagamento = p.stato_pagamento || 'da_pagare';
    const statoPagamentoLabel = STATI_PAGAMENTO[statoPagamento] || statoPagamento;
    const statoPagamentoColor = COLORI_STATO_PAGAMENTO[statoPagamento] || 'gray';
    
    // Colori badge (palette Eterea Studio)
    const badgeColors = {
        // Colori Tailwind legacy
        'emerald': { bg: '#d1fae5', text: '#065f46' },
        'green': { bg: '#d1fae5', text: '#065f46' },
        'cyan': { bg: '#cffafe', text: '#155e75' },
        'blue': { bg: '#dbeafe', text: '#1e40af' },
        'amber': { bg: '#fef3c7', text: '#92400e' },
        'yellow': { bg: '#fef3c7', text: '#92400e' },
        'red': { bg: '#fee2e2', text: '#991b1b' },
        'gray': { bg: '#f3f4f6', text: '#374151' },
        'slate': { bg: '#f1f5f9', text: '#475569' },
        'purple': { bg: '#f3e8ff', text: '#6b21a8' },
        // Colori Eterea (esadecimali)
        '#9bc4d0': { bg: '#e8f4f6', text: '#5a8a96' },
        '#a8b5a0': { bg: '#eef1ec', text: '#788570' },
        '#c4b5d0': { bg: '#f3eff6', text: '#8a7a96' },
        '#e8e4b8': { bg: '#faf9ef', text: '#9a9668' },
        '#e8c4b8': { bg: '#faf0ed', text: '#9a7668' },
        '#9ca3af': { bg: '#f3f4f6', text: '#6b7280' },
        '#909090': { bg: '#f0f0f0', text: '#505050' }
    };
    const badgeColor = badgeColors[statoPagamentoColor] || badgeColors['gray'];
    
    // Avatars partecipanti
    let avatarsHtml = '';
    if (p.partecipanti) {
        let partecipantiIds = [];
        try {
            // Prova a fare il parse come JSON
            const parsed = JSON.parse(p.partecipanti);
            // Se è un array, usalo direttamente
            if (Array.isArray(parsed)) {
                partecipantiIds = parsed;
            } else {
                // Se è una stringa, tratta come separata da virgole
                partecipantiIds = String(parsed).split(',').filter(id => id.trim());
            }
        } catch (e) {
            // Se fallisce, tratta come stringa separata da virgole
            partecipantiIds = String(p.partecipanti).split(',').filter(id => id.trim());
        }
        
        if (partecipantiIds.length > 0) {
            avatarsHtml = `<div class="pipeline-project-avatars">`;
            partecipantiIds.slice(0, 3).forEach(userId => {
                const user = USERS[userId];
                if (user) {
                    const iniziale = user.nome.charAt(0).toUpperCase();
                    avatarsHtml += `
                        <div class="pipeline-avatar" style="background-color: ${user.colore};" title="${escapeHtml(user.nome)}">
                            ${iniziale}
                        </div>
                    `;
                }
            });
            if (partecipantiIds.length > 3) {
                avatarsHtml += `<div class="pipeline-avatar-more">+${partecipantiIds.length - 3}</div>`;
            }
            avatarsHtml += `</div>`;
        }
    }
    
    div.innerHTML = `
        <div class="pipeline-project-header">
            <div class="pipeline-project-dot" style="background-color: ${colore}"></div>
            <div class="pipeline-project-title">${escapeHtml(p.titolo)}</div>
        </div>
        <div class="pipeline-project-client">${escapeHtml(p.cliente_nome || 'Nessun cliente')}</div>
        <div class="pipeline-project-meta">
            <span>📅 ${scadenza}</span>
            <span class="pipeline-project-price">${prezzo}</span>
        </div>
        <div class="pipeline-project-footer">
            <div class="pipeline-project-payment">
                <span class="payment-badge" style="background-color: ${badgeColor.bg}; color: ${badgeColor.text};">
                    ${statoPagamentoLabel}
                </span>
            </div>
            ${avatarsHtml}
        </div>
    `;
    
    return div;
}

// Disegna le connessioni SVG tra i nodi
function drawPipelineConnections() {
    const svg = document.getElementById('pipelineConnections');
    const canvas = document.getElementById('pipelineCanvas');
    if (!svg || !canvas) return;
    
    // Pulisci linee esistenti (tranne defs)
    const existingLines = svg.querySelectorAll('.pipeline-connection');
    existingLines.forEach(l => l.remove());
    
    const nodeWidth = 320;
    
    // Per ogni nodo, calcola il centro attuale (considerando il transform durante il drag)
    function getNodeCenter(node) {
        const left = parseInt(node.style.left) || 0;
        const top = parseInt(node.style.top) || 0;
        
        // Se il nodo è in drag, considera anche il transform
        let translateX = 0, translateY = 0;
        if (node.classList.contains('dragging')) {
            const transform = node.style.transform;
            const match = transform.match(/translate3d\(([-\d.]+)px,\s*([-\d.]+)px/);
            if (match) {
                translateX = parseFloat(match[1]);
                translateY = parseFloat(match[2]);
            }
        }
        
        return {
            x: left + translateX + nodeWidth / 2,
            y: top + translateY + node.offsetHeight / 2,
            width: nodeWidth,
            height: node.offsetHeight,
            left: left + translateX,
            top: top + translateY
        };
    }
    
    // Definisci le connessioni con anchor points specifici
    const connections = [
        // Flusso principale orizzontale
        { from: 'node-da_iniziare', to: 'node-in_corso', fromAnchor: 'right', toAnchor: 'left' },
        { from: 'node-in_corso', to: 'node-completato', fromAnchor: 'right', toAnchor: 'left' },
        { from: 'node-completato', to: 'node-consegnato', fromAnchor: 'right', toAnchor: 'left' },
        { from: 'node-consegnato', to: 'node-archiviato', fromAnchor: 'right', toAnchor: 'left' },
        // Branch In Corso ↔ In Pausa (con offset per evitare sovrapposizione)
        { from: 'node-in_corso', to: 'node-in_pausa', fromAnchor: 'top', toAnchor: 'bottom', offset: -40 },
        { from: 'node-in_pausa', to: 'node-in_corso', fromAnchor: 'bottom', toAnchor: 'top', offset: 40 }
    ];
    
    connections.forEach((conn, index) => {
        const fromNode = document.getElementById(conn.from);
        const toNode = document.getElementById(conn.to);
        if (!fromNode || !toNode) return;
        
        const fromCenter = getNodeCenter(fromNode);
        const toCenter = getNodeCenter(toNode);
        
        let x1, y1, x2, y2;
        
        // Calcola punti di connessione basati sugli anchor
        switch (conn.fromAnchor) {
            case 'right':
                x1 = fromCenter.left + fromCenter.width;
                y1 = fromCenter.y + (conn.offset || 0);
                break;
            case 'left':
                x1 = fromCenter.left;
                y1 = fromCenter.y + (conn.offset || 0);
                break;
            case 'top':
                x1 = fromCenter.x + (conn.offset || 0);
                y1 = fromCenter.top;
                break;
            case 'bottom':
                x1 = fromCenter.x + (conn.offset || 0);
                y1 = fromCenter.top + fromCenter.height;
                break;
        }
        
        switch (conn.toAnchor) {
            case 'right':
                x2 = toCenter.left + toCenter.width;
                y2 = toCenter.y + (conn.offset || 0);
                break;
            case 'left':
                x2 = toCenter.left;
                y2 = toCenter.y + (conn.offset || 0);
                break;
            case 'top':
                x2 = toCenter.x + (conn.offset || 0);
                y2 = toCenter.top;
                break;
            case 'bottom':
                x2 = toCenter.x + (conn.offset || 0);
                y2 = toCenter.top + toCenter.height;
                break;
        }
        
        // Crea path con curva di Bezier più ampia
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        
        // Calcola punti di controllo per curve morbide
        const dx = x2 - x1;
        const dy = y2 - y1;
        
        let cp1x, cp1y, cp2x, cp2y;
        
        if (Math.abs(dx) > Math.abs(dy)) {
            // Connessione prevalentemente orizzontale
            const tension = 0.5;
            cp1x = x1 + dx * tension;
            cp1y = y1;
            cp2x = x2 - dx * tension;
            cp2y = y2;
        } else {
            // Connessione prevalentemente verticale
            const tension = 0.5;
            cp1x = x1;
            cp1y = y1 + dy * tension;
            cp2x = x2;
            cp2y = y2 - dy * tension;
        }
        
        const d = `M ${x1} ${y1} C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${x2} ${y2}`;
        
        path.setAttribute('d', d);
        path.setAttribute('class', 'pipeline-connection');
        path.setAttribute('marker-end', 'url(#arrowhead)');
        
        svg.appendChild(path);
    });
}

// ============================================
// ZOOM E PAN PIPELINE
// ============================================

let pipelineState = {
    scale: 0.85,  // Zoom iniziale per vedere tutta la pipeline
    panX: 30,     // Margine sinistro
    panY: 100,    // Centrato verticalmente
    isPanning: false,
    startX: 0,
    startY: 0
};

function pipelineZoomIn() {
    pipelineState.scale = Math.min(pipelineState.scale + 0.1, 2);
    updatePipelineTransform();
}

function pipelineZoomOut() {
    pipelineState.scale = Math.max(pipelineState.scale - 0.1, 0.5);
    updatePipelineTransform();
}

function pipelineResetView() {
    pipelineState.scale = 0.9; // Zoom leggermente ridotto per vedere tutto
    pipelineState.panX = 50;   // Margine sinistro
    pipelineState.panY = 50;   // Margine superiore
    updatePipelineTransform();
}

function updatePipelineTransform() {
    const canvas = document.getElementById('pipelineCanvas');
    if (!canvas) return;
    
    // Transform con GPU acceleration (translate3d)
    canvas.style.transform = `translate3d(${pipelineState.panX}px, ${pipelineState.panY}px, 0) scale(${pipelineState.scale})`;
    document.getElementById('zoomLevel').textContent = Math.round(pipelineState.scale * 100) + '%';
    
    // Ridisegna connessioni dopo il render
    requestAnimationFrame(drawPipelineConnections);
}

// Inizializza pan del canvas
function initPipelinePan() {
    const container = document.getElementById('pipelineContainer');
    const canvas = document.getElementById('pipelineCanvas');
    if (!container || !canvas) return;
    
    let panRafId = null;
    
    // Pan con middle mouse o space+drag
    container.addEventListener('mousedown', (e) => {
        // Solo se clic sul container (non sui nodi)
        if (e.target === container || e.target === canvas) {
            pipelineState.isPanning = true;
            pipelineState.startX = e.clientX - pipelineState.panX;
            pipelineState.startY = e.clientY - pipelineState.panY;
            container.style.cursor = 'grabbing';
            canvas.style.willChange = 'transform';
        }
    });
    
    document.addEventListener('mousemove', (e) => {
        if (!pipelineState.isPanning) return;
        
        pipelineState.panX = e.clientX - pipelineState.startX;
        pipelineState.panY = e.clientY - pipelineState.startY;
        
        // RAF per pan fluido
        if (!panRafId) {
            panRafId = requestAnimationFrame(() => {
                updatePipelineTransform();
                panRafId = null;
            });
        }
    });
    
    document.addEventListener('mouseup', () => {
        if (pipelineState.isPanning) {
            pipelineState.isPanning = false;
            container.style.cursor = 'grab';
            canvas.style.willChange = 'auto';
        }
    });
    
    // Zoom con wheel
    container.addEventListener('wheel', (e) => {
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            pipelineState.scale = Math.max(0.5, Math.min(2, pipelineState.scale + delta));
            updatePipelineTransform();
        }
    }, { passive: false });
}

// ============================================
// DRAG & DROP NODI - ULTRA FLUIDO con GPU acceleration
// ============================================

let draggedNode = null;
let nodeStartX = 0;
let nodeStartY = 0;
let dragStartMouseX = 0;
let dragStartMouseY = 0;
let rafId = null;
let ghostNode = null;

function initNodeDragging() {
    ghostNode = document.getElementById('pipelineGhost');
    const nodes = document.querySelectorAll('.pipeline-node');
    
    nodes.forEach(node => {
        const header = node.querySelector('.node-header');
        if (!header || node.id === 'pipelineGhost') return;
        
        header.addEventListener('mousedown', (e) => {
            e.stopPropagation(); // Non attivare il pan
            e.preventDefault();  // Prevenire selezione testo
            
            draggedNode = node;
            
            // Salva posizione iniziale del nodo
            nodeStartX = parseInt(node.style.left) || 0;
            nodeStartY = parseInt(node.style.top) || 0;
            
            // Salva posizione iniziale del mouse
            dragStartMouseX = e.clientX;
            dragStartMouseY = e.clientY;
            
            // Mostra ghost node alla posizione originale
            if (ghostNode) {
                ghostNode.style.display = 'block';
                ghostNode.style.left = nodeStartX + 'px';
                ghostNode.style.top = nodeStartY + 'px';
                ghostNode.style.width = node.offsetWidth + 'px';
                ghostNode.style.height = node.offsetHeight + 'px';
            }
            
            // Aggiungi classe per styling drag
            node.classList.add('dragging');
            
            // Ottimizzazione GPU
            node.style.willChange = 'transform';
        });
    });
    
    document.addEventListener('mousemove', (e) => {
        if (!draggedNode) return;
        
        // Calcola delta considerando lo zoom
        const deltaX = (e.clientX - dragStartMouseX) / pipelineState.scale;
        const deltaY = (e.clientY - dragStartMouseY) / pipelineState.scale;
        
        // Nuova posizione target
        const targetX = Math.max(0, nodeStartX + deltaX);
        const targetY = Math.max(0, nodeStartY + deltaY);
        
        // Snap-to-grid (10px per default)
        const snapSize = 10;
        const snappedX = Math.round(targetX / snapSize) * snapSize;
        const snappedY = Math.round(targetY / snapSize) * snapSize;
        
        // Applica transform per GPU acceleration (60fps smooth)
        const translateX = snappedX - nodeStartX;
        const translateY = snappedY - nodeStartY;
        draggedNode.style.transform = `translate3d(${translateX}px, ${translateY}px, 0)`;
        
        // Throttle del ridisegno connessioni con RAF
        if (!rafId) {
            rafId = requestAnimationFrame(() => {
                drawPipelineConnections();
                rafId = null;
            });
        }
    });
    
    document.addEventListener('mouseup', () => {
        if (draggedNode) {
            // Commit della posizione finale
            const transform = draggedNode.style.transform;
            const match = transform.match(/translate3d\(([-\d.]+)px,\s*([-\d.]+)px/);
            
            if (match) {
                const translateX = parseFloat(match[1]);
                const translateY = parseFloat(match[2]);
                
                // Applica posizione finale a left/top
                draggedNode.style.left = (nodeStartX + translateX) + 'px';
                draggedNode.style.top = (nodeStartY + translateY) + 'px';
            }
            
            // Reset transform
            draggedNode.style.transform = '';
            draggedNode.style.willChange = 'auto';
            draggedNode.classList.remove('dragging');
            
            // Nascondi ghost
            if (ghostNode) {
                ghostNode.style.display = 'none';
            }
            
            // Ridisegna connessioni finali
            drawPipelineConnections();
            
            draggedNode = null;
        }
    });
}

// Carica progetti
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza toggle raggruppamento
    const toggle = document.getElementById('groupByClienteToggle');
    if (toggle) toggle.checked = groupByCliente;
    
    loadProgetti();
    
    // Event listeners filtri
    ['searchInput', 'statoFilter', 'clienteFilter', 'partecipanteFilter', 'coloreFilter'].forEach(id => {
        document.getElementById(id).addEventListener('change', loadProgetti);
        if (id === 'searchInput') {
            document.getElementById(id).addEventListener('input', debounce(loadProgetti, 300));
        }
    });
    
    // Ridisegna connessioni pipeline al resize
    window.addEventListener('resize', debounce(() => {
        if (vistaPipeline) {
            drawPipelineConnections();
        }
    }, 200));
    
    // Inizializza pan e drag della pipeline
    initPipelinePan();
    initNodeDragging();
});

async function loadProgetti() {
    const search = document.getElementById('searchInput').value;
    const stato = document.getElementById('statoFilter').value;
    const cliente = document.getElementById('clienteFilter').value;
    const partecipante = document.getElementById('partecipanteFilter').value;
    const colore = document.getElementById('coloreFilter').value;
    
    let url = 'api/progetti.php?action=list';
    // Nella pipeline carichiamo SEMPRE tutti i progetti (inclusi archiviati)
    // per permettere la visualizzazione completa del workflow
    url += '&archiviati=all';
    if (search) url += '&search=' + encodeURIComponent(search);
    if (stato) url += '&stato=' + encodeURIComponent(stato);
    if (cliente) url += '&cliente=' + encodeURIComponent(cliente);
    if (partecipante) url += '&partecipante=' + encodeURIComponent(partecipante);
    if (colore) url += '&colore=' + encodeURIComponent(colore);
    
    console.log('Caricamento progetti da:', url);
    
    try {
        const response = await fetch(url);
        console.log('Response status:', response.status);
        
        const text = await response.text();
        console.log('Response text (primi 200 caratteri):', text.substring(0, 200));
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Errore parsing JSON:', e);
            showToast('Errore risposta server. Controlla la console.', 'error');
            document.getElementById('progettiContainer').innerHTML = `
                <div class="col-span-full text-center py-12 text-red-500">
                    <p>Errore caricamento dati.</p>
                    <p class="text-sm mt-2">Risposta non valida dal server.</p>
                    <button onclick="location.reload()" class="mt-4 px-4 py-2 bg-cyan-600 text-white rounded-lg">Ricarica pagina</button>
                </div>
            `;
            return;
        }
        
        if (data.success) {
            progettiData = data.data;
            renderProgetti(data.data);
        } else {
            showToast(data.message || 'Errore caricamento progetti', 'error');
            if (data.message && data.message.includes('Sessione')) {
                setTimeout(() => window.location.href = 'index.php', 2000);
            }
        }
    } catch (error) {
        console.error('Errore fetch:', error);
        showToast('Errore di connessione: ' + error.message, 'error');
    }
}

// Stato raggruppamento
// Stato raggruppamento (inizializzato prima del DOMContentLoaded)
let groupByCliente = localStorage.getItem('groupByCliente') === 'true';

function toggleGroupByCliente() {
    const toggle = document.getElementById('groupByClienteToggle');
    groupByCliente = toggle.checked;
    localStorage.setItem('groupByCliente', groupByCliente);
    
    // Se abbiamo i dati, rilancia il render
    if (progettiData) {
        renderProgetti(progettiData);
    }
}

// Variabile globale per tutti i progetti (per pipeline)
let allProgetti = [];

function renderProgetti(progetti) {
    console.log('renderProgetti called', progetti?.length, 'items');
    
    // Salva riferimento globale per la pipeline (tutti i progetti)
    allProgetti = progetti || [];
    
    // Se in vista pipeline, aggiorna anche quella
    if (vistaPipeline) {
        renderPipeline();
    }
    
    // Filtra progetti per la vista griglia
    // Se mostraArchiviati è false: mostra solo non-archiviati
    // Se mostraArchiviati è true: mostra solo archiviati
    let progettiDaMostrare = progetti || [];
    if (!mostraArchiviati) {
        // Modalità default: nascondi archiviati
        progettiDaMostrare = progettiDaMostrare.filter(p => p.stato_progetto !== 'archiviato');
    } else {
        // Modalità archiviati: mostra SOLO archiviati
        progettiDaMostrare = progettiDaMostrare.filter(p => p.stato_progetto === 'archiviato');
    }
    
    const container = document.getElementById('progettiContainer');
    
    if (!container) {
        console.error('Container not found!');
        return;
    }
    
    if (!progettiDaMostrare || progettiDaMostrare.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <h3 class="text-base sm:text-lg font-medium text-slate-600">Nessun progetto trovato</h3>
                <p class="text-slate-400 mt-1">Prova a modificare i filtri o crea un nuovo progetto</p>
            </div>
        `;
        return;
    }
    
    // Se raggruppamento attivo, raggruppa per cliente
    if (groupByCliente) {
        console.log('Rendering grouped view');
        container.className = 'space-y-6';
        
        try {
            // Raggruppa per cliente
            const grouped = progettiDaMostrare.reduce((acc, p) => {
                const key = p.cliente_id || 'no-cliente';
                const name = p.cliente_nome || 'Senza cliente';
                const logo = p.cliente_logo || null;
                if (!acc[key]) {
                    acc[key] = { name, logo, progetti: [] };
                }
                acc[key].progetti.push(p);
                return acc;
            }, {});
            
            console.log('Grouped data:', Object.keys(grouped).length, 'groups');
            
            // Ordina i gruppi per nome cliente
            const sortedKeys = Object.keys(grouped).sort((a, b) => {
                return grouped[a].name.localeCompare(grouped[b].name);
            });
            
            let html = '';
            for (const key of sortedKeys) {
                const group = grouped[key];
                const groupId = `group-${key.replace(/[^a-zA-Z0-9]/g, '-')}`;
                const needsToggle = group.progetti.length > 1;
                
                console.log('Rendering group:', group.name, group.progetti.length, 'projects');
                
                try {
                    const cardsHtml = group.progetti.map((p) => renderProgettoCardStack(p)).join('');
                    
                    html += `
                        <div class="cliente-group" data-group-id="${groupId}">
                            <!-- Header Cliente -->
                            <div class="flex items-center gap-3 mb-4 px-2">
                                ${group.logo ? 
                                    `<img src="assets/uploads/${group.logo}" alt="" class="w-8 h-8 rounded-full object-cover">` :
                                    `<div class="w-8 h-8 rounded-full bg-gradient-to-br from-cyan-500 to-cyan-600 flex items-center justify-center text-white text-sm font-bold">
                                        ${group.name.charAt(0).toUpperCase()}
                                    </div>`
                                }
                                <h3 class="text-lg font-bold text-slate-800">${group.name}</h3>
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-xs">${group.progetti.length} progetti</span>
                                ${needsToggle ? `
                                    <button onclick="toggleGroupExpand('${groupId}')" id="btn-${groupId}" class="stack-toggle-btn">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        <span>Espandi</span>
                                    </button>
                                ` : ''}
                            </div>
                            
                            <!-- Stack di Card Orizzontale -->
                            <div class="card-stack-container ${needsToggle ? 'collapsed' : 'expanded'}" id="stack-${groupId}">
                                <div class="card-stack">
                                    ${cardsHtml}
                                </div>

                            </div>
                        </div>
                    `;
                } catch (groupError) {
                    console.error('Error rendering group:', group.name, groupError);
                    html += `<div class="p-4 bg-red-100 text-red-600 rounded">Errore caricamento gruppo ${group.name}</div>`;
                }
            }
            
            container.innerHTML = html;
        } catch (e) {
            console.error('Error in grouped rendering:', e);
            container.innerHTML = '<div class="text-center py-12 text-red-500">Errore nel raggruppamento progetti</div>';
        }
        
        return;
    }
    
    // Vista normale (griglia)
    console.log('Rendering normal grid view');
    container.className = 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6';
    try {
        container.innerHTML = progettiDaMostrare.map(p => renderProgettoCard(p)).join('');
    } catch (e) {
        console.error('Error in normal rendering:', e);
        container.innerHTML = '<div class="text-center py-12 text-red-500">Errore nel caricamento progetti</div>';
    }
}

// Genera card per lo stack (usa la stessa struttura della card normale)
function renderProgettoCardStack(p) {
    try {
        return renderProgettoCard(p, true);
    } catch (e) {
        console.error('Error in renderProgettoCardStack:', e, p);
        return '<div class="p-4 bg-red-100 text-red-600 rounded">Errore caricamento progetto</div>';
    }
}

// Genera card normale o per stack
function renderProgettoCard(p, isStackItem = false) {
    try {
    const statoColor = COLORI_STATO_PROGETTO[p.stato_progetto] || '#9ca3af';
    const statoLabel = <?php echo json_encode(STATI_PROGETTO); ?>[p.stato_progetto] || p.stato_progetto;
    
    const statoPagamentoColor = COLORI_STATO_PAGAMENTO[p.stato_pagamento] || '#9ca3af';
    
    // Colori badge per stati (palette Eterea)
    const statoBadgeColors = {
        '#9bc4d0': { bg: '#e8f4f6', text: '#5a8a96' },
        '#a8b5a0': { bg: '#eef1ec', text: '#788570' },
        '#c4b5d0': { bg: '#f3eff6', text: '#8a7a96' },
        '#e8e4b8': { bg: '#faf9ef', text: '#9a9668' },
        '#e8c4b8': { bg: '#faf0ed', text: '#9a7668' },
        '#9ca3af': { bg: '#f3f4f6', text: '#6b7280' },
        '#909090': { bg: '#f0f0f0', text: '#505050' }
    };
    const statoBadge = statoBadgeColors[statoColor] || statoBadgeColors['#9ca3af'];
    const pagamentoBadge = statoBadgeColors[statoPagamentoColor] || statoBadgeColors['#9ca3af'];
    
    const tipologie = (p.tipologie || []).map(t => 
        `<span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded">${t}</span>`
    ).join('');
    
    const partecipanti = (p.partecipanti || []).map(id => {
        const user = <?php echo json_encode(USERS); ?>[id];
        const avatar = p.partecipanti_avatar?.[id];
        if (!user) return '';
        if (avatar) {
            return `<img src="assets/uploads/avatars/${avatar}" class="w-7 h-7 rounded-full object-cover -ml-2 first:ml-0 border-2 border-white" title="${user.nome}">`;
        }
        return `<div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-medium -ml-2 first:ml-0 border-2 border-white" style="background-color: ${user.colore}" title="${user.nome}">${user.nome.charAt(0)}</div>`;
    }).join('');
    
    const taskProgress = p.num_task > 0 ? Math.round((p.task_completati / p.num_task) * 100) : 0;
    
    const coloreSfondo = p.colore_tag || '#FFFFFF';
    const isDefaultColor = coloreSfondo === '#FFFFFF';
    
    const cardClass = isStackItem ? 'card-stack-item' : 'card-hover';
    
    return `
        <div class="${cardClass} rounded-2xl shadow-sm border border-slate-200 overflow-hidden bg-white" 
             style="background-color: ${coloreSfondo}; ${!isDefaultColor ? 'border-color: ' + coloreSfondo.replace('FF', 'DD') : ''}">
            <div class="p-5">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium" style="background-color: ${statoBadge.bg}; color: ${statoBadge.text};">
                            ${statoLabel}
                        </span>
                        ${p.nuove_task > 0 ? `
                            <span class="px-2 py-1 rounded-full text-xs font-bold bg-red-500 text-white animate-pulse" title="${p.nuove_task} nuove task">
                                +${p.nuove_task}
                            </span>
                        ` : ''}
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium" style="background-color: ${pagamentoBadge.bg}; color: ${pagamentoBadge.text};">
                        ${<?php echo json_encode(STATI_PAGAMENTO); ?>[p.stato_pagamento]}
                    </span>
                </div>
                
                <h3 class="text-sm sm:text-base font-semibold text-slate-800 mb-2 line-clamp-2">
                    <a href="progetto_dettaglio.php?id=${p.id}" class="hover:text-cyan-600">${p.titolo}</a>
                </h3>
                
                <p class="text-xs sm:text-sm text-slate-500 mb-3">
                    ${p.cliente_nome ? `
                        <span class="flex items-center gap-2">
                            ${p.cliente_logo ? 
                                `<img src="assets/uploads/${p.cliente_logo}" alt="" class="w-5 h-5 rounded-full object-cover flex-shrink-0">` : 
                                `<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`
                            }
                            <span class="truncate">${p.cliente_nome}</span>
                        </span>
                    ` : 'Nessun cliente'}
                </p>
                
                <div class="flex flex-wrap gap-2 mb-4">
                    ${tipologie}
                </div>
                
                <!-- Progress task -->
                <div class="mb-4">
                    <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                        <span>Task completate</span>
                        <span>${p.task_completati || 0}/${p.num_task || 0}</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-cyan-500 rounded-full transition-all" style="width: ${taskProgress}%"></div>
                    </div>
                </div>
                
                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <div class="flex items-center">
                        ${partecipanti}
                    </div>
                    <span class="font-semibold text-slate-800">€${parseFloat(p.prezzo_totale).toFixed(2)}</span>
                </div>
            </div>
            
            <div class="px-5 py-3 border-t border-slate-100/50 flex gap-2" style="background-color: ${coloreSfondo}; filter: brightness(0.97);">
                <a href="progetto_dettaglio.php?id=${p.id}" class="flex-1 text-center py-2 bg-white border border-slate-200 rounded-lg text-xs sm:text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors min-h-[44px] flex items-center justify-center">
                    Dettagli
                </a>
                <button onclick="editProgetto('${p.id}')" class="p-2 text-slate-400 hover:text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button onclick="deleteProgetto('${p.id}', '${(p.titolo || '').replace(/'/g, '&#39;')}')" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
    `;
    } catch (e) {
        console.error('Error in renderProgettoCard:', e, p);
        return '<div class="p-4 bg-red-100 text-red-600 rounded">Errore caricamento progetto</div>';
    }
}

function toggleGroupExpand(groupId) {
    const stack = document.getElementById(`stack-${groupId}`);
    const btn = document.getElementById(`btn-${groupId}`);
    
    if (!stack || !btn) return;
    
    const isExpanded = stack.classList.contains('expanded');
    
    if (isExpanded) {
        // Comprimi
        stack.classList.remove('expanded');
        stack.classList.add('collapsed');
        btn.querySelector('span').textContent = 'Espandi';
    } else {
        // Espandi
        stack.classList.remove('collapsed');
        stack.classList.add('expanded');
        btn.querySelector('span').textContent = 'Comprimi';
    }
}

async function editProgetto(id) {
    try {
        const response = await fetch(`api/progetti.php?action=detail&id=${id}`);
        const data = await response.json();
        
        if (!data.success) {
            showToast('Errore caricamento progetto', 'error');
            return;
        }
        
        const p = data.data;
        document.getElementById('modalTitle').textContent = 'Modifica Progetto';
        document.getElementById('progettoId').value = p.id;
        document.querySelector('input[name="titolo"]').value = p.titolo;
        document.querySelector('select[name="cliente_id"]').value = p.cliente_id || '';
        document.querySelector('textarea[name="descrizione"]').value = p.descrizione || '';
        document.querySelector('input[name="prezzo_totale"]').value = p.prezzo_totale;
        document.querySelector('select[name="stato_progetto"]').value = p.stato_progetto;
        document.querySelector('select[name="stato_pagamento"]').value = p.stato_pagamento;
        document.querySelector('input[name="acconto_percentuale"]').value = p.acconto_percentuale || '';
        document.querySelector('input[name="data_inizio"]').value = p.data_inizio || '';
        document.querySelector('input[name="data_consegna_prevista"]').value = p.data_consegna_prevista || '';
        
        // Aggiorna visibilità campo percentuale acconto
        toggleAccontoPercentuale();
        
        // Checkboxes tipologie
        document.querySelectorAll('input[name="tipologie[]"]').forEach(cb => {
            cb.checked = (p.tipologie || []).includes(cb.value);
        });
        
        // Checkboxes partecipanti
        document.querySelectorAll('input[name="partecipanti[]"]').forEach(cb => {
            cb.checked = (p.partecipanti || []).includes(cb.value);
        });
        
        // Colore tag
        const coloreTag = p.colore_tag || '#FFFFFF';
        document.querySelectorAll('input[name="colore_tag"]').forEach(rb => {
            rb.checked = (rb.value === coloreTag);
        });
        
        openModal('progettoModal');
    } catch (error) {
        showToast('Errore caricamento progetto', 'error');
    }
}

function toggleAccontoPercentuale() {
    const select = document.getElementById('statoPagamentoSelect');
    const wrapperAcconto = document.getElementById('accontoPercentualeWrapper');
    const inputAcconto = document.getElementById('accontoPercentuale');
    const wrapperRicorrente = document.getElementById('pagamentoRicorrenteWrapper');
    
    // Gestione acconto
    if (select.value === 'da_pagare_acconto') {
        wrapperAcconto.classList.remove('hidden');
        inputAcconto.required = true;
    } else {
        wrapperAcconto.classList.add('hidden');
        inputAcconto.required = false;
        inputAcconto.value = '';
    }
    
    // Gestione pagamento ricorrente
    if (select.value === 'mensile') {
        wrapperRicorrente.classList.remove('hidden');
        // Imposta default data prossima pagamento a oggi + 1 mese
        const oggi = new Date();
        oggi.setMonth(oggi.getMonth() + 1);
        document.getElementById('prossimaDataRicorrente').valueAsDate = oggi;
    } else {
        wrapperRicorrente.classList.add('hidden');
    }
}

// Validazione distribuzione ricorrente
function validaDistribuzioneRicorrente() {
    const inputs = document.querySelectorAll('.distribuzione-ricorrente-input');
    let totale = 0;
    inputs.forEach(input => {
        totale += parseFloat(input.value) || 0;
    });
    
    const totaleEl = document.getElementById('totaleDistribuzioneRicorrente');
    const erroreEl = document.getElementById('erroreDistribuzioneRicorrente');
    
    totaleEl.textContent = totale + '%';
    
    if (totale !== 100) {
        totaleEl.classList.add('text-red-500');
        totaleEl.classList.remove('text-slate-800', 'text-emerald-600');
        erroreEl.classList.remove('hidden');
        return false;
    } else {
        totaleEl.classList.remove('text-red-500');
        totaleEl.classList.add('text-emerald-600');
        erroreEl.classList.add('hidden');
        return true;
    }
}

async function saveProgetto() {
    const form = document.getElementById('progettoForm');
    const formData = new FormData(form);
    const id = document.getElementById('progettoId').value;
    
    const action = id ? 'update' : 'create';
    if (id) formData.append('id', id);
    
    // Prendi dati per calendario
    const titolo = form.querySelector('[name="titolo"]')?.value;
    const dataConsegna = form.querySelector('[name="data_consegna_prevista"]')?.value;
    
    console.log('Salvataggio progetto...', action);
    
    try {
        const response = await fetch(`api/progetti.php?action=${action}`, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        console.log('Risposta:', data);
        
        if (data.success) {
            showToast(id ? 'Progetto aggiornato' : 'Progetto creato', 'success');
            closeModal('progettoModal');
            form.reset();
            document.getElementById('progettoId').value = '';
            document.getElementById('modalTitle').textContent = 'Nuovo Progetto';
            // Reset visibilità campo percentuale
            toggleAccontoPercentuale();
            // Piccolo delay per dare tempo al DB
            setTimeout(() => {
                loadProgetti();
            }, 300);
        } else {
            showToast(data.message || 'Errore salvataggio', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

function deleteProgetto(id, titolo) {
    console.log('Eliminazione progetto:', id, titolo);
    confirmAction(`Sei sicuro di voler eliminare il progetto "${titolo}"?`, async () => {
        try {
            // Usiamo GET invece di POST per l'eliminazione
            const url = `api/progetti.php?action=delete&id=${encodeURIComponent(id)}`;
            console.log('Invio richiesta a:', url);
            
            const response = await fetch(url, {
                method: 'GET'
            });
            
            const data = await response.json();
            console.log('Risposta:', data);
            
            if (data.success) {
                showToast('Progetto eliminato', 'success');
                loadProgetti();
            } else {
                showToast(data.message || 'Errore eliminazione', 'error');
            }
        } catch (error) {
            console.error('Errore:', error);
            showToast('Errore di connessione', 'error');
        }
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Utility: escape HTML per sicurezza
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}


</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
