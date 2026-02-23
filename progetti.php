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
        </div>
    </div>
</div>

<!-- Filtri -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
    <!-- Mobile: Accordion Header -->
    <button type="button" onclick="toggleFiltriMobile()" class="sm:hidden w-full flex items-center justify-between mb-2">
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
                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
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
            <select id="coloreFilter" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                <option value="">Tutti i colori</option>
                <option value="#FFFFFF">⚪ Bianco</option>
                <option value="#22D3EE">🔵 Ciano</option>
                <option value="#60A5FA">🔵 Blu</option>
                <option value="#34D399">🟢 Verde</option>
                <option value="#A3E635">🟢 Lime</option>
                <option value="#FACC15">🟡 Giallo</option>
                <option value="#FB923C">🟠 Arancione</option>
                <option value="#F87171">🔴 Rosso</option>
                <option value="#F472B6">🩷 Rosa</option>
                <option value="#C084FC">🟣 Viola</option>
                <option value="#A78BFA">💜 Indaco</option>
                <option value="#94A3B8">⚪ Grigio</option>
                <option value="#78350F">🟤 Marrone</option>
                <option value="#14B8A6">🩵 Turchese</option>
                <option value="#E879F9">💗 Fucsia</option>
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
</style>

<!-- Lista Progetti -->
<div id="progettiContainer" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
    <div class="col-span-full text-center py-12">
        <div class="animate-spin w-8 h-8 border-2 border-cyan-500 border-t-transparent rounded-full mx-auto"></div>
        <p class="text-slate-500 mt-2">Caricamento progetti...</p>
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
                    
                    <!-- Tipologie -->
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Tipologie</label>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach (TIPOLOGIE_PROGETTO as $tipo): ?>
                            <label class="inline-flex items-center px-3 py-1.5 rounded-full border border-slate-200 cursor-pointer hover:bg-slate-50 transition-colors has-[:checked]:bg-cyan-50 has-[:checked]:border-cyan-500 has-[:checked]:text-cyan-700">
                                <input type="checkbox" name="tipologie[]" value="<?php echo $tipo; ?>" class="sr-only">
                                <span class="text-xs sm:text-sm"><?php echo $tipo; ?></span>
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
                                <option value="<?php echo $key; ?>" <?php echo $key === 'da_iniziare' ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Stato Pagamento</label>
                            <select name="stato_pagamento" id="statoPagamentoSelect" onchange="toggleAccontoPercentuale()" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                                <?php foreach (STATI_PAGAMENTO as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $key === 'da_pagare' ? 'selected' : ''; ?>><?php echo $label; ?></option>
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
});

async function loadProgetti() {
    const search = document.getElementById('searchInput').value;
    const stato = document.getElementById('statoFilter').value;
    const cliente = document.getElementById('clienteFilter').value;
    const partecipante = document.getElementById('partecipanteFilter').value;
    const colore = document.getElementById('coloreFilter').value;
    
    let url = 'api/progetti.php?action=list';
    url += '&archiviati=' + (mostraArchiviati ? '1' : '0');
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

function renderProgetti(progetti) {
    console.log('renderProgetti called', progetti?.length, 'items');
    const container = document.getElementById('progettiContainer');
    
    if (!container) {
        console.error('Container not found!');
        return;
    }
    
    if (!progetti || progetti.length === 0) {
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
            const grouped = progetti.reduce((acc, p) => {
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
        container.innerHTML = progetti.map(p => renderProgettoCard(p)).join('');
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
    const statoColor = COLORI_STATO_PROGETTO[p.stato_progetto] || 'gray';
    const statoLabel = <?php echo json_encode(STATI_PROGETTO); ?>[p.stato_progetto] || p.stato_progetto;
    
    const statoPagamentoColor = COLORI_STATO_PAGAMENTO[p.stato_pagamento] || 'gray';
    
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
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-${statoColor}-100 text-${statoColor}-700">
                            ${statoLabel}
                        </span>
                        ${p.nuove_task > 0 ? `
                            <span class="px-2 py-1 rounded-full text-xs font-bold bg-red-500 text-white animate-pulse" title="${p.nuove_task} nuove task">
                                +${p.nuove_task}
                            </span>
                        ` : ''}
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-${statoPagamentoColor}-100 text-${statoPagamentoColor}-700">
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
    const wrapper = document.getElementById('accontoPercentualeWrapper');
    const input = document.getElementById('accontoPercentuale');
    
    if (select.value === 'da_pagare_acconto') {
        wrapper.classList.remove('hidden');
        input.required = true;
    } else {
        wrapper.classList.add('hidden');
        input.required = false;
        input.value = '';
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
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
