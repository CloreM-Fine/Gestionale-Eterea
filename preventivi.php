<?php
/**
 * Eterea Gestionale
 * Preventivi
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/config.php';

// Carica preventivi salvati
$preventiviSalvati = [];
$preventiviError = '';
try {
    // Query senza JOIN per evitare problemi di collation
    $stmt = $pdo->query("
        SELECT * FROM preventivi_salvati 
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $preventiviSalvati = $stmt->fetchAll();
} catch (PDOException $e) {
    $preventiviError = $e->getMessage();
    error_log("Errore caricamento preventivi salvati: " . $e->getMessage());
}

// Carica progetti per associazione
$progetti = [];
try {
    $stmt = $pdo->query("
        SELECT p.id, p.titolo, c.ragione_sociale as cliente_nome, p.cliente_id
        FROM progetti p
        LEFT JOIN clienti c ON p.cliente_id = c.id
        WHERE p.stato_progetto != 'completato'
        ORDER BY p.created_at DESC
    ");
    $progetti = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Errore caricamento progetti: " . $e->getMessage());
}

$pageTitle = 'Preventivi';
include __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Preventivi</h1>
            <p class="text-sm text-slate-500 mt-1">Gestisci i servizi e crea preventivi</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openCategoriaModal()" 
                    class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-medium flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Nuova Categoria
            </button>
            <button onclick="openPreventivoModal()" 
                    class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Crea Preventivo
            </button>
        </div>
    </div>
</div>

<!-- Preventivi Salvati -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-slate-800">Preventivi Salvati</h2>
        <span id="countPreventiviSalvati" class="text-sm text-slate-500"></span>
    </div>
    <div id="preventiviSalvatiContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php if ($preventiviError): ?>
            <div class="col-span-full text-center py-8 text-red-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>Errore caricamento: <?php echo e($preventiviError); ?></p>
            </div>
        <?php elseif (empty($preventiviSalvati)): ?>
            <div class="col-span-full text-center py-8 text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p>Nessun preventivo salvato</p>
            </div>
        <?php else: ?>
            <?php foreach ($preventiviSalvati as $p): 
                $data = date('d/m/Y', strtotime($p['created_at']));
                $servizi = json_decode($p['servizi_json'] ?? '[]', true);
                $numServizi = count($servizi);
                $clienteId = $p['cliente_id'] ?? '';
                $clienteNome = $p['cliente_nome'] ?? 'Cliente';
                $progettoId = $p['progetto_id'] ?? '';
                $isAssociato = !empty($progettoId);
            ?>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <span class="text-xs text-slate-500"><?php echo e($p['numero']); ?></span>
                            <h3 class="font-semibold text-slate-800"><?php echo e($clienteNome); ?></h3>
                        </div>
                        <span class="text-xs text-slate-400"><?php echo $data; ?></span>
                    </div>
                    
                    <div class="text-sm text-slate-600 mb-2">
                        <?php echo $numServizi; ?> servizi • Totale: €<?php echo number_format(floatval($p['totale']), 2); ?>
                    </div>
                    
                    <div class="text-xs mb-3">
                        <?php if ($isAssociato): ?>
                            <span class="text-emerald-600 font-medium">✓ Associato a progetto</span>
                        <?php else: ?>
                            <span class="text-amber-600 font-medium">⚠ Non associato</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex gap-2">
                        <button onclick="associaAProgetto(<?php echo $p['id']; ?>, '<?php echo e($clienteId); ?>', '<?php echo e($clienteNome); ?>')"
                                class="flex-1 px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-sm font-medium transition-colors"
                                title="Associa a progetto">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            Associa a Progetto
                        </button>
                        <?php if (!empty($p['file_path'])): ?>
                        <a href="assets/uploads/preventivi/<?php echo e($p['file_path']); ?>" target="_blank"
                           class="px-3 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-sm font-medium transition-colors"
                           title="Apri file">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <button onclick="eliminaPreventivo(<?php echo $p['id']; ?>)"
                                class="px-3 py-2 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg text-sm font-medium transition-colors"
                                title="Elimina preventivo">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Listino Prezzi -->
<div class="border-t border-slate-200 pt-8 mb-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Listino Prezzi</h2>
</div>

<!-- Preventivi Container -->
<div id="preventiviContainer" class="space-y-6">
    <div class="text-center py-12">
        <div class="animate-spin w-8 h-8 border-2 border-cyan-500 border-t-transparent rounded-full mx-auto"></div>
        <p class="text-sm text-slate-500 mt-2">Caricamento listino...</p>
    </div>
</div>

<!-- Modal Nuova/Modifica Voce -->
<div id="voceModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('voceModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-800" id="voceModalTitle">Nuovo Servizio</h2>
                <button onclick="closeModal('voceModal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="voceForm" class="flex-1 overflow-y-auto p-6 space-y-4">
                <input type="hidden" name="id" id="voceId">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Categoria *</label>
                    <select name="categoria_id" id="voceCategoria" required
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tipo Servizio *</label>
                    <input type="text" name="tipo_servizio" id="voceTipo" required
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                           placeholder="es. Sito Web Vetrina">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Descrizione</label>
                    <textarea name="descrizione" id="voceDescrizione" rows="3"
                              class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none"
                              placeholder="Descrivi il servizio..."></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Prezzo (€) *</label>
                        <input type="number" name="prezzo" id="vocePrezzo" required min="0" step="0.01"
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                               placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Sconto %</label>
                        <input type="number" name="sconto_percentuale" id="voceSconto" min="0" max="100" value="0"
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                    </div>
                </div>
            </form>
            
            <div class="p-6 border-t border-slate-100 flex justify-end gap-3">
                <button type="button" onclick="closeModal('voceModal')" 
                        class="px-4 py-2 text-slate-600 hover:text-slate-800 font-medium">
                    Annulla
                </button>
                <button type="button" onclick="saveVoce()" 
                        class="px-6 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium">
                    Salva
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuova Categoria -->
<div id="categoriaModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('categoriaModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-800">Nuova Categoria</h2>
                <button onclick="closeModal('categoriaModal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="p-6">
                <label class="block text-sm font-medium text-slate-700 mb-2">Nome Categoria *</label>
                <input type="text" id="categoriaNome" 
                       class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                       placeholder="es. Consulenza">
            </div>
            
            <div class="p-6 border-t border-slate-100 flex justify-end gap-3">
                <button onclick="closeModal('categoriaModal')" 
                        class="px-4 py-2 text-slate-600 hover:text-slate-800 font-medium">
                    Annulla
                </button>
                <button onclick="saveCategoria()" 
                        class="px-6 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium">
                    Salva
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crea Preventivo -->
<div id="preventivoModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('preventivoModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-800">Crea Preventivo</h2>
                <button onclick="closeModal('preventivoModal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Dati Cliente -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Cliente *</label>
                        <select id="prevClienteSelect" onchange="updateClienteInput()"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none mb-2">
                            <option value="">-- Seleziona cliente --</option>
                        </select>
                        <input type="text" id="prevCliente" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                               placeholder="Oppure scrivi nome cliente manualmente">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">N. Preventivo</label>
                        <input type="text" id="prevNumero" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                               placeholder="PREV-2024-001">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Valido fino al *</label>
                        <input type="date" id="prevScadenza" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Sconto Globale %</label>
                        <input type="number" id="prevScontoGlobale" min="0" max="100" value="0"
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                               onchange="updatePreventivoPreview()">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Note</label>
                    <textarea id="prevNote" rows="2"
                              class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none"
                              placeholder="Note aggiuntive per il cliente..."></textarea>
                </div>
                
                <!-- Selezione Fonte Dati -->
                <div class="mb-4 p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <label class="block text-sm font-medium text-slate-700 mb-3">Seleziona la fonte dei servizi</label>
                    
                    <div class="flex flex-wrap gap-4 mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="fonte_dati" value="listini_nuovi" checked 
                                   onchange="onFonteChange()" class="w-4 h-4 text-cyan-600">
                            <span class="text-sm">Listini (nuovi)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="fonte_dati" value="listino_vecchio" 
                                   onchange="onFonteChange()" class="w-4 h-4 text-cyan-600">
                            <span class="text-sm">Listino Prezzi (vecchio)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="fonte_dati" value="entrambi" 
                                   onchange="onFonteChange()" class="w-4 h-4 text-cyan-600">
                            <span class="text-sm">Entrambi</span>
                        </label>
                    </div>
                    
                    <!-- Select Listini Nuovi -->
                    <div id="selectListiniNuovi">
                        <select id="prevListinoSelect" onchange="onListinoChange()"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none bg-white">
                            <option value="">-- Seleziona un listino --</option>
                        </select>
                    </div>
                    
                    <!-- Messaggio Listino Vecchio -->
                    <div id="msgListinoVecchio" class="hidden text-sm text-slate-500">
                        Verranno mostrati i servizi dal Listino Prezzi esistente
                    </div>
                </div>
                
                <!-- Selezione Servizi -->
                <h3 class="font-semibold text-slate-800 mb-3">Seleziona i Servizi</h3>
                <div id="preventivoServizi" class="space-y-3 max-h-96 overflow-y-auto border border-slate-200 rounded-xl p-4">
                    <p class="text-slate-400 text-center py-4">Seleziona una fonte per vedere i servizi disponibili</p>
                </div>
                
                <!-- Riepilogo -->
                <div class="mt-6 p-4 bg-slate-50 rounded-xl">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-slate-600">Subtotale:</span>
                        <span class="font-semibold" id="prevSubtotale">€ 0,00</span>
                    </div>
                    <div class="flex items-center justify-between mb-2" id="prevScontoRow" style="display:none">
                        <span class="text-slate-600">Sconto globale:</span>
                        <span class="font-semibold text-red-500" id="prevScontoVal">-€ 0,00</span>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-slate-200">
                        <span class="text-lg font-bold text-slate-800">TOTALE:</span>
                        <span class="text-2xl font-bold text-cyan-600" id="prevTotale">€ 0,00</span>
                    </div>
                </div>
            </div>
            
            <div class="p-6 border-t border-slate-100 flex justify-end gap-3">
                <button onclick="closeModal('preventivoModal')" 
                        class="px-4 py-2 text-slate-600 hover:text-slate-800 font-medium">
                    Annulla
                </button>
                <button onclick="salvaPreventivoGestionale()" 
                        class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Salva nel Gestionale
                </button>
                <button onclick="generaPreventivo()" 
                        class="px-6 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Scarica PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/80" onclick="closeModal('previewModal')"></div>
    <div class="absolute inset-4 bg-white rounded-xl overflow-hidden flex flex-col">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
            <h3 class="font-bold text-slate-800">Anteprima Preventivo</h3>
            <div class="flex items-center gap-2">
                <button onclick="stampaPreventivo()" class="px-4 py-2 bg-cyan-600 text-white rounded-lg text-sm font-medium hover:bg-cyan-700">
                    🖨️ Stampa PDF
                </button>
                <button onclick="closeModal('previewModal')" class="p-2 text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
        <iframe id="previewFrame" class="flex-1 w-full border-0"></iframe>
    </div>
</div>

<script>
let preventiviData = [];
let categorieData = [];
let clientiData = [];

// Carica listino e clienti all'avvio
document.addEventListener('DOMContentLoaded', function() {
    loadPreventivi();
    // I preventivi salvati sono già caricati dal PHP
    loadClientiForSelect();
    
    // Imposta data scadenza default (30 giorni)
    const scadenzaDefault = new Date();
    scadenzaDefault.setDate(scadenzaDefault.getDate() + 30);
    document.getElementById('prevScadenza').valueAsDate = scadenzaDefault;
});

// Carica e visualizza i preventivi salvati
async function loadPreventiviSalvati() {
    try {
        const response = await fetch('api/preventivi.php?action=list_preventivi_salvati');
        const data = await response.json();
        
        if (data.success) {
            renderPreventiviSalvati(data.data);
        }
    } catch (error) {
        console.error('Errore caricamento preventivi salvati:', error);
    }
}

function renderPreventiviSalvati(preventivi) {
    const container = document.getElementById('preventiviSalvatiContainer');
    const countLabel = document.getElementById('countPreventiviSalvati');
    
    if (preventivi.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-8 text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p>Nessun preventivo salvato</p>
            </div>
        `;
        countLabel.textContent = '';
        return;
    }
    
    countLabel.textContent = `${preventivi.length} preventivi`;
    
    container.innerHTML = preventivi.map(p => {
        const data = new Date(p.created_at).toLocaleDateString('it-IT');
        const servizi = JSON.parse(p.servizi_json || '[]');
        const numServizi = servizi.length;
        
        return `
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <span class="text-xs text-slate-500">${p.numero}</span>
                        <h3 class="font-semibold text-slate-800">${p.cliente_nome}</h3>
                    </div>
                    <span class="text-xs text-slate-400">${data}</span>
                </div>
                
                <div class="text-sm text-slate-600 mb-3">
                    ${numServizi} servizi • Totale: €${parseFloat(p.totale).toFixed(2)}
                </div>
                
                <div class="flex gap-2">
                    <button onclick="visualizzaPreventivoSalvato(${p.id})" 
                            class="flex-1 px-3 py-2 bg-cyan-50 hover:bg-cyan-100 text-cyan-700 rounded-lg text-sm font-medium transition-colors">
                        Visualizza
                    </button>
                    ${p.file_path ? `
                    <a href="assets/uploads/preventivi/${p.file_path}" target="_blank"
                       class="px-3 py-2 bg-slate-50 hover:bg-slate-100 text-slate-700 rounded-lg text-sm font-medium transition-colors"
                       title="Apri file">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                    ` : ''}
                </div>
            </div>
        `;
    }).join('');
}

async function visualizzaPreventivoSalvato(id) {
    try {
        const response = await fetch(`api/preventivi.php?action=view_preventivo&id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const p = data.data;
            const servizi = JSON.parse(p.servizi_json || '[]');
            
            // Crea modal per visualizzare
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm';
            modal.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden flex flex-col">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-slate-800">${p.numero}</h3>
                            <p class="text-sm text-slate-500">${p.cliente_nome}</p>
                        </div>
                        <button onclick="this.closest('.fixed').remove()" class="text-slate-400 hover:text-slate-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-5">
                        <div class="space-y-3">
                            ${servizi.map(s => `
                                <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-slate-800">${s.tipo_servizio}</p>
                                        <p class="text-sm text-slate-500">${s.descrizione || ''}</p>
                                    </div>
                                    <p class="font-semibold text-cyan-600">€${parseFloat(s.prezzo).toFixed(2)}</p>
                                </div>
                            `).join('')}
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-slate-200">
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-slate-600">Subtotale:</span>
                                <span class="font-medium">€${parseFloat(p.subtotale).toFixed(2)}</span>
                            </div>
                            ${p.sconto_globale > 0 ? `
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-slate-600">Sconto ${p.sconto_globale}%:</span>
                                <span class="font-medium text-green-600">-€${(p.subtotale * p.sconto_globale / 100).toFixed(2)}</span>
                            </div>
                            ` : ''}
                            <div class="flex justify-between text-lg font-bold mt-3">
                                <span>Totale:</span>
                                <span class="text-cyan-600">€${parseFloat(p.totale).toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 border-t border-slate-100 flex justify-end gap-3">
                        <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 text-slate-600 font-medium">
                            Chiudi
                        </button>
                        ${p.file_path ? `
                        <a href="assets/uploads/preventivi/${p.file_path}" target="_blank"
                           class="px-6 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Apri PDF
                        </a>
                        ` : ''}
                    </div>
                </div>
            `;
            modal.onclick = (e) => {
                if (e.target === modal) modal.remove();
            };
            document.body.appendChild(modal);
        }
    } catch (error) {
        showToast('Errore caricamento preventivo', 'error');
    }
}

// Associa preventivo a progetto
function associaAProgetto(preventivoId, clienteId, clienteNome) {
    document.getElementById('associaPreventivoId').value = preventivoId;
    document.getElementById('associaClienteId').value = clienteId;
    document.getElementById('associaPreventivoNome').textContent = 'Preventivo #' + preventivoId;
    document.getElementById('associaClienteNome').textContent = clienteNome || 'Cliente non specificato';
    
    // Filtra i progetti per cliente - MOSTRA SOLO quelli del cliente
    // Match per ID o per nome (per compatibilità con preventivi vecchi)
    const select = document.getElementById('associaProgettoSelect');
    const options = Array.from(select.querySelectorAll('option'));
    let hasVisibleOptions = false;
    
    options.forEach(opt => {
        if (opt.value === '') {
            opt.style.display = '';
            return;
        }
        
        const optClienteId = opt.getAttribute('data-cliente') || '';
        const optText = opt.textContent || '';
        
        // Mostra SOLO se il progetto è dello stesso cliente (match per ID o per nome)
        const idMatch = clienteId && optClienteId === clienteId;
        const nomeMatch = clienteNome && optText.toLowerCase().includes(clienteNome.toLowerCase());
        
        if (idMatch || nomeMatch) {
            opt.style.display = '';
            hasVisibleOptions = true;
        } else {
            opt.style.display = 'none';
        }
    });
    
    // Se non ci sono progetti per questo cliente, mostra option disabilitata
    if (!hasVisibleOptions) {
        const noProjectOption = select.querySelector('option[value=""]');
        if (noProjectOption) {
            noProjectOption.textContent = '-- Nessun progetto disponibile per questo cliente --';
        }
    } else {
        const defaultOption = select.querySelector('option[value=""]');
        if (defaultOption) {
            defaultOption.textContent = '-- Seleziona un progetto --';
        }
    }
    
    select.value = '';
    openModal('associaProgettoModal');
}

async function salvaAssociazioneProgetto() {
    const preventivoId = document.getElementById('associaPreventivoId').value;
    const progettoId = document.getElementById('associaProgettoSelect').value;
    
    if (!progettoId) {
        showToast('Seleziona un progetto', 'error');
        return;
    }
    
    try {
        const response = await fetch('api/preventivi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=associa_progetto&preventivo_id=${preventivoId}&progetto_id=${progettoId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Preventivo associato al progetto con successo!', 'success');
            closeModal('associaProgettoModal');
        } else {
            showToast(data.message || 'Errore associazione', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function eliminaPreventivo(id) {
    confirmAction('Sei sicuro di voler eliminare questo preventivo?', async () => {
        try {
            const response = await fetch('api/preventivi.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_preventivo&id=${id}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Preventivo eliminato', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || 'Errore eliminazione', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione', 'error');
        }
    });
}

async function loadClientiForSelect() {
    try {
        const response = await fetch('api/clienti.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            clientiData = data.data;
            const select = document.getElementById('prevClienteSelect');
            select.innerHTML = '<option value="">-- Seleziona cliente --</option>' + 
                clientiData.map(c => `<option value="${c.id}" data-nome="${c.ragione_sociale}">${c.ragione_sociale}</option>`).join('');
        }
    } catch (error) {
        console.error('Errore caricamento clienti:', error);
    }
}

function updateClienteInput() {
    const select = document.getElementById('prevClienteSelect');
    const input = document.getElementById('prevCliente');
    const selectedOption = select.options[select.selectedIndex];
    if (select.value && selectedOption) {
        input.value = selectedOption.getAttribute('data-nome') || select.value;
    }
}

async function loadPreventivi() {
    try {
        const response = await fetch('api/preventivi.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            preventiviData = data.data;
            renderPreventivi();
        }
    } catch (error) {
        console.error('Errore:', error);
    }
}

function renderPreventivi() {
    const container = document.getElementById('preventiviContainer');
    
    if (preventiviData.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 bg-white rounded-2xl border border-slate-200">
                <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-slate-500 mb-4">Nessuna categoria nei preventivi</p>
                <button onclick="openCategoriaModal()" class="px-4 py-2 bg-cyan-600 text-white rounded-lg">
                    Aggiungi Categoria
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = preventiviData.map(cat => `
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-bold text-slate-800">${cat.nome}</h2>
                    <span class="px-2 py-0.5 bg-slate-200 text-slate-600 rounded-full text-xs">${cat.voci.length} servizi</span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="openVoceModal(${cat.id})" 
                            class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-medium flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Aggiungi
                    </button>
                    <button onclick="deleteCategoria(${cat.id}, '${cat.nome.replace(/'/g, "\\'")}')" 
                            class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            <th class="px-5 py-3">Servizio</th>
                            <th class="px-5 py-3">Descrizione</th>
                            <th class="px-5 py-3 text-right">Prezzo</th>
                            <th class="px-5 py-3 text-center">Sconto</th>
                            <th class="px-5 py-3 text-right">Prezzo Finale</th>
                            <th class="px-5 py-3 text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        ${cat.voci.map(v => {
                            const prezzoFinale = v.prezzo * (1 - v.sconto_percentuale / 100);
                            return `
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4 font-medium text-slate-800">${v.tipo_servizio}</td>
                                <td class="px-5 py-4 text-sm text-slate-500">${v.descrizione || '-'}</td>
                                <td class="px-5 py-4 text-right font-medium">€ ${parseFloat(v.prezzo).toLocaleString('it-IT', {minimumFractionDigits: 2})}</td>
                                <td class="px-5 py-4 text-center">
                                    ${v.sconto_percentuale > 0 ? `<span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">-${v.sconto_percentuale}%</span>` : '-'}
                                </td>
                                <td class="px-5 py-4 text-right font-bold text-cyan-600">€ ${prezzoFinale.toLocaleString('it-IT', {minimumFractionDigits: 2})}</td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button onclick="editVoce(${v.id})" class="p-1.5 text-slate-400 hover:text-cyan-600 hover:bg-cyan-50 rounded">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button onclick="deleteVoce(${v.id})" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            `;
                        }).join('')}
                        ${cat.voci.length === 0 ? '<tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">Nessun servizio in questa categoria</td></tr>' : ''}
                    </tbody>
                </table>
            </div>
        </div>
    `).join('');
}

// Gestione Modal Voce
function openVoceModal(categoriaId, voceId = null) {
    document.getElementById('voceForm').reset();
    document.getElementById('voceId').value = voceId || '';
    document.getElementById('voceModalTitle').textContent = voceId ? 'Modifica Servizio' : 'Nuovo Servizio';
    
    // Popola select categorie
    const select = document.getElementById('voceCategoria');
    select.innerHTML = preventiviData.map(c => `<option value="${c.id}" ${c.id == categoriaId ? 'selected' : ''}>${c.nome}</option>`).join('');
    
    if (voceId) {
        // Trova voce esistente
        for (const cat of preventiviData) {
            const voce = cat.voci.find(v => v.id == voceId);
            if (voce) {
                document.getElementById('voceTipo').value = voce.tipo_servizio;
                document.getElementById('voceDescrizione').value = voce.descrizione || '';
                document.getElementById('vocePrezzo').value = voce.prezzo;
                document.getElementById('voceSconto').value = voce.sconto_percentuale;
                break;
            }
        }
    }
    
    openModal('voceModal');
}

async function saveVoce() {
    const form = document.getElementById('voceForm');
    const formData = new FormData(form);
    
    if (!formData.get('tipo_servizio') || !formData.get('prezzo')) {
        showToast('Compila tutti i campi obbligatori', 'error');
        return;
    }
    
    try {
        const response = await fetch('api/preventivi.php?action=save_voce', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            showToast(formData.get('id') ? 'Servizio aggiornato' : 'Servizio creato', 'success');
            closeModal('voceModal');
            loadPreventivi();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

function editVoce(id) {
    let catId = null;
    for (const cat of preventiviData) {
        if (cat.voci.find(v => v.id == id)) {
            catId = cat.id;
            break;
        }
    }
    openVoceModal(catId, id);
}

async function deleteVoce(id) {
    if (!confirm('Eliminare questo servizio?')) return;
    
    try {
        const response = await fetch('api/preventivi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_voce&id=${id}`
        });
        
        const data = await response.json();
        if (data.success) {
            showToast('Servizio eliminato', 'success');
            loadPreventivi();
        }
    } catch (error) {
        showToast('Errore', 'error');
    }
}

// Gestione Categorie
function openCategoriaModal() {
    document.getElementById('categoriaNome').value = '';
    openModal('categoriaModal');
}

async function saveCategoria() {
    const nome = document.getElementById('categoriaNome').value.trim();
    
    if (!nome) {
        showToast('Inserisci il nome della categoria', 'error');
        return;
    }
    
    try {
        const response = await fetch('api/preventivi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=save_categoria&nome=${encodeURIComponent(nome)}`
        });
        
        const data = await response.json();
        if (data.success) {
            showToast('Categoria creata', 'success');
            closeModal('categoriaModal');
            loadPreventivi();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function deleteCategoria(id, nome) {
    if (!confirm(`Eliminare la categoria "${nome}" e tutti i suoi servizi?`)) return;
    
    try {
        const response = await fetch('api/preventivi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_categoria&id=${id}`
        });
        
        const data = await response.json();
        if (data.success) {
            showToast('Categoria eliminata', 'success');
            loadPreventivi();
        }
    } catch (error) {
        showToast('Errore', 'error');
    }
}

// Preventivo
let preventivoVoci = [];

let listiniDataSelect = [];

async function loadListiniSelect() {
    try {
        const response = await fetch('api/listini.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            listiniDataSelect = data.data;
            const select = document.getElementById('prevListinoSelect');
            select.innerHTML = '<option value="">-- Seleziona un listino --</option>' + 
                listiniDataSelect.map(l => `<option value="${l.id}">${l.titolo} (${l.num_servizi} servizi)</option>`).join('');
        }
    } catch (error) {
        console.error('Errore caricamento listini:', error);
    }
}

function onFonteChange() {
    const fonte = document.querySelector('input[name="fonte_dati"]:checked').value;
    const selectNuovi = document.getElementById('selectListiniNuovi');
    const msgVecchio = document.getElementById('msgListinoVecchio');
    
    if (fonte === 'listini_nuovi') {
        selectNuovi.classList.remove('hidden');
        msgVecchio.classList.add('hidden');
        // Reset e ricarica se c'è un listino selezionato
        onListinoChange();
    } else if (fonte === 'listino_vecchio') {
        selectNuovi.classList.add('hidden');
        msgVecchio.classList.remove('hidden');
        // Carica dal listino vecchio
        renderPreventivoServizi();
    } else if (fonte === 'entrambi') {
        selectNuovi.classList.remove('hidden');
        msgVecchio.classList.add('hidden');
        msgVecchio.classList.remove('hidden');
        msgVecchio.textContent = 'Verranno mostrati i servizi sia dal listino selezionato che dal Listino Prezzi';
        // Carica entrambi
        loadEntrambiListini();
    }
}

async function onListinoChange() {
    const fonte = document.querySelector('input[name="fonte_dati"]:checked').value;
    const listinoId = document.getElementById('prevListinoSelect').value;
    
    if (fonte === 'entrambi') {
        await loadEntrambiListini();
        return;
    }
    
    if (!listinoId) {
        document.getElementById('preventivoServizi').innerHTML = 
            '<p class="text-slate-400 text-center py-4">Seleziona un listino per vedere i servizi disponibili</p>';
        return;
    }
    
    try {
        const response = await fetch(`api/listini.php?action=detail&id=${listinoId}`);
        const data = await response.json();
        
        if (data.success) {
            // Trasforma i servizi del listino nel formato delle voci preventivo
            const serviziListino = [{
                id: 'listino_' + listinoId,
                nome: data.data.titolo,
                voci: data.data.servizi.map(s => ({
                    id: 'srv_' + s.id,
                    tipo_servizio: s.nome,
                    descrizione: s.descrizione || '',
                    prezzo: parseFloat(s.prezzo),
                    sconto_percentuale: 0
                }))
            }];
            
            renderPreventivoServiziFromListino(serviziListino);
        }
    } catch (error) {
        console.error('Errore caricamento servizi listino:', error);
    }
}

async function loadEntrambiListini() {
    const listinoId = document.getElementById('prevListinoSelect').value;
    let tutteLeCategorie = [];
    
    // 1. Carica dal listino vecchio (se esistono dati)
    if (preventiviData && preventiviData.length > 0) {
        tutteLeCategorie = [...preventiviData];
    }
    
    // 2. Carica dal listino nuovo se selezionato
    if (listinoId) {
        try {
            const response = await fetch(`api/listini.php?action=detail&id=${listinoId}`);
            const data = await response.json();
            
            if (data.success && data.data.servizi.length > 0) {
                const categoriaNuova = {
                    id: 'listino_' + listinoId,
                    nome: '📋 ' + data.data.titolo + ' (Listino)',
                    voci: data.data.servizi.map(s => ({
                        id: 'srv_' + s.id,
                        tipo_servizio: s.nome,
                        descrizione: s.descrizione || '',
                        prezzo: parseFloat(s.prezzo),
                        sconto_percentuale: 0
                    }))
                };
                tutteLeCategorie.push(categoriaNuova);
            }
        } catch (error) {
            console.error('Errore caricamento listino nuovo:', error);
        }
    }
    
    if (tutteLeCategorie.length === 0) {
        document.getElementById('preventivoServizi').innerHTML = 
            '<p class="text-slate-400 text-center py-4">Nessun servizio disponibile nelle fonti selezionate</p>';
        return;
    }
    
    renderPreventivoServiziFromListino(tutteLeCategorie);
}

function renderPreventivoServiziFromListino(categorie) {
    const container = document.getElementById('preventivoServizi');
    
    if (categorie.length === 0 || categorie[0].voci.length === 0) {
        container.innerHTML = '<p class="text-slate-400 text-center py-4">Nessun servizio in questo listino</p>';
        return;
    }
    
    container.innerHTML = categorie.map(cat => `
        <div class="border border-slate-200 rounded-lg overflow-hidden">
            <div class="px-4 py-2 bg-slate-100 font-medium text-slate-700 text-sm">${cat.nome}</div>
            <div class="divide-y divide-slate-100">
                ${cat.voci.map(v => {
                    const prezzoFinale = v.prezzo * (1 - v.sconto_percentuale / 100);
                    return `
                    <div class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50">
                        <input type="checkbox" id="prev-voce-${v.id}" value="${v.id}" 
                               data-prezzo="${v.prezzo}"
                               data-nome="${escapeHtml(v.tipo_servizio)}"
                               class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500"
                               onchange="updatePreventivoPreview()">
                        <label for="prev-voce-${v.id}" class="flex-1 cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-slate-800">${v.tipo_servizio}</p>
                                    <p class="text-xs text-slate-500">${v.descrizione ? v.descrizione.substring(0, 60) + '...' : ''}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-cyan-600">€ ${prezzoFinale.toLocaleString('it-IT', {minimumFractionDigits: 2})}</p>
                                </div>
                            </div>
                        </label>
                    </div>
                    `;
                }).join('')}
            </div>
        </div>
    `).join('');
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function openPreventivoModal() {
    document.getElementById('prevCliente').value = '';
    document.getElementById('prevNumero').value = 'PREV-' + new Date().getFullYear() + '-' + String(Math.floor(Math.random() * 900) + 100);
    document.getElementById('prevScontoGlobale').value = '0';
    document.getElementById('prevNote').value = '';
    document.getElementById('prevListinoSelect').value = '';
    
    // Reset radio button a "Listini (nuovi)"
    const radioNuovi = document.querySelector('input[name="fonte_dati"][value="listini_nuovi"]');
    if (radioNuovi) radioNuovi.checked = true;
    
    // Aggiorna UI
    onFonteChange();
    
    loadListiniSelect();
    updatePreventivoPreview();
    openModal('preventivoModal');
}

function renderPreventivoServizi() {
    const container = document.getElementById('preventivoServizi');
    const fonte = document.querySelector('input[name="fonte_dati"]:checked')?.value;
    
    // Se è selezionato "listini_nuovi" e non c'è un listino selezionato
    const listinoSelect = document.getElementById('prevListinoSelect');
    if (fonte === 'listini_nuovi' && listinoSelect && !listinoSelect.value) {
        container.innerHTML = '<p class="text-slate-400 text-center py-4">Seleziona un listino per vedere i servizi disponibili</p>';
        return;
    }
    
    if (preventiviData.length === 0) {
        container.innerHTML = '<p class="text-slate-400 text-center py-4">Nessun servizio disponibile nel Listino Prezzi</p>';
        return;
    }
    
    container.innerHTML = preventiviData.map(cat => `
        <div class="border border-slate-200 rounded-lg overflow-hidden">
            <div class="px-4 py-2 bg-slate-100 font-medium text-slate-700 text-sm">${cat.nome}</div>
            <div class="divide-y divide-slate-100">
                ${cat.voci.map(v => {
                    const prezzoFinale = v.prezzo * (1 - v.sconto_percentuale / 100);
                    return `
                    <div class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50">
                        <input type="checkbox" id="prev-voce-${v.id}" value="${v.id}" 
                               class="w-4 h-4 text-cyan-600 rounded border-slate-300 focus:ring-cyan-500"
                               onchange="updatePreventivoPreview()">
                        <label for="prev-voce-${v.id}" class="flex-1 cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-slate-800">${v.tipo_servizio}</p>
                                    <p class="text-xs text-slate-500">${v.descrizione ? v.descrizione.substring(0, 60) + '...' : ''}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-cyan-600">€ ${prezzoFinale.toLocaleString('it-IT', {minimumFractionDigits: 2})}</p>
                                    ${v.sconto_percentuale > 0 ? `<p class="text-xs text-green-600">-${v.sconto_percentuale}%</p>` : ''}
                                </div>
                            </div>
                        </label>
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-slate-500">Q.tà:</label>
                            <input type="number" id="prev-qty-${v.id}" value="1" min="1" max="99"
                                   class="w-16 px-2 py-1 border border-slate-200 rounded text-center text-sm"
                                   onchange="updatePreventivoPreview()">
                        </div>
                    </div>
                    `;
                }).join('')}
            </div>
        </div>
    `).join('');
}

function updatePreventivoPreview() {
    preventivoVoci = [];
    let subtotale = 0;
    
    // Raccogli voci selezionate
    for (const cat of preventiviData) {
        for (const v of cat.voci) {
            const checkbox = document.getElementById(`prev-voce-${v.id}`);
            if (checkbox && checkbox.checked) {
                const qty = parseInt(document.getElementById(`prev-qty-${v.id}`).value) || 1;
                const prezzoScontato = v.prezzo * (1 - v.sconto_percentuale / 100);
                const totale = prezzoScontato * qty;
                
                preventivoVoci.push({
                    id: v.id,
                    quantita: qty
                });
                
                subtotale += totale;
            }
        }
    }
    
    const scontoGlobale = parseFloat(document.getElementById('prevScontoGlobale').value) || 0;
    const scontoImporto = subtotale * (scontoGlobale / 100);
    const totale = subtotale - scontoImporto;
    
    document.getElementById('prevSubtotale').textContent = '€ ' + subtotale.toLocaleString('it-IT', {minimumFractionDigits: 2});
    
    const scontoRow = document.getElementById('prevScontoRow');
    if (scontoGlobale > 0) {
        scontoRow.style.display = 'flex';
        document.getElementById('prevScontoVal').textContent = '-€ ' + scontoImporto.toLocaleString('it-IT', {minimumFractionDigits: 2});
    } else {
        scontoRow.style.display = 'none';
    }
    
    document.getElementById('prevTotale').textContent = '€ ' + totale.toLocaleString('it-IT', {minimumFractionDigits: 2});
}

async function generaPreventivo() {
    if (preventivoVoci.length === 0) {
        showToast('Seleziona almeno un servizio', 'error');
        return;
    }
    
    const cliente = document.getElementById('prevCliente').value.trim();
    if (!cliente) {
        showToast('Inserisci il nome del cliente', 'error');
        return;
    }
    
    const scadenza = document.getElementById('prevScadenza').value;
    if (!scadenza) {
        showToast('Inserisci la data di validità', 'error');
        return;
    }
    
    try {
        const response = await fetch('api/preventivi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'genera_preventivo',
                voci: JSON.stringify(preventivoVoci),
                cliente_nome: cliente,
                preventivo_num: document.getElementById('prevNumero').value,
                note: document.getElementById('prevNote').value,
                sconto_globale: document.getElementById('prevScontoGlobale').value,
                data_scadenza: document.getElementById('prevScadenza').value
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Mostra preview
            const frame = document.getElementById('previewFrame');
            frame.srcdoc = data.data.preview_html;
            openModal('previewModal');
            closeModal('preventivoModal');
        } else {
            showToast(data.message || 'Errore generazione', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

function stampaPreventivo() {
    const frame = document.getElementById('previewFrame');
    frame.contentWindow.print();
}

async function salvaPreventivoGestionale() {
    if (preventivoVoci.length === 0) {
        showToast('Seleziona almeno un servizio', 'error');
        return;
    }
    
    const cliente = document.getElementById('prevCliente').value.trim();
    if (!cliente) {
        showToast('Inserisci il nome del cliente', 'error');
        return;
    }
    
    const clienteSelect = document.getElementById('prevClienteSelect').value;
    const numero = document.getElementById('prevNumero').value;
    const scadenza = document.getElementById('prevScadenza').value;
    const sconto = document.getElementById('prevScontoGlobale').value;
    const note = document.getElementById('prevNote').value;
    
    // Calcola totali
    let subtotale = 0;
    preventivoVoci.forEach(v => {
        const prezzoFinale = v.prezzo * (1 - (v.sconto_percentuale || 0) / 100);
        subtotale += prezzoFinale;
    });
    const totale = subtotale * (1 - parseFloat(sconto || 0) / 100);
    
    const formData = new FormData();
    formData.append('action', 'salva_preventivo');
    formData.append('numero', numero);
    formData.append('cliente_id', clienteSelect);
    formData.append('cliente_nome', cliente);
    formData.append('data_scadenza', scadenza);
    formData.append('sconto_globale', sconto);
    formData.append('note', note);
    formData.append('servizi', JSON.stringify(preventivoVoci));
    formData.append('subtotale', subtotale.toFixed(2));
    formData.append('totale', totale.toFixed(2));
    
    try {
        const response = await fetch('api/preventivi.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Preventivo salvato nel gestionale!', 'success');
            closeModal('preventivoModal');
            // Ricarica la pagina per vedere il preventivo salvato
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(data.message || 'Errore salvataggio', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}
</script>

<!-- Modal Associa a Progetto -->
<div id="associaProgettoModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('associaProgettoModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Associa a Progetto</h3>
                <button onclick="closeModal('associaProgettoModal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-5">
                <input type="hidden" id="associaPreventivoId">
                <input type="hidden" id="associaClienteId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Preventivo</label>
                    <p id="associaPreventivoNome" class="text-slate-800 font-medium"></p>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Cliente</label>
                    <p id="associaClienteNome" class="text-slate-600"></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Seleziona Progetto *</label>
                    <select id="associaProgettoSelect" class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                        <option value="">-- Seleziona un progetto --</option>
                        <?php foreach ($progetti as $prog): ?>
                            <option value="<?php echo e($prog['id']); ?>" data-cliente="<?php echo e($prog['cliente_id'] ?? ''); ?>">
                                <?php echo e($prog['titolo']); ?> 
                                <?php if ($prog['cliente_nome']): ?>
                                    (<?php echo e($prog['cliente_nome']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-slate-500 mt-1">Verranno mostrati solo i progetti dello stesso cliente</p>
                </div>
            </div>
            <div class="p-5 border-t border-slate-100 flex justify-end gap-3">
                <button onclick="closeModal('associaProgettoModal')" class="px-4 py-2 text-slate-600 font-medium">
                    Annulla
                </button>
                <button onclick="salvaAssociazioneProgetto()" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                    Associa
                </button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
