<?php
/**
 * Eterea Gestionale
 * Gestione Clienti
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Clienti';

include __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="mb-4 sm:mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Clienti</h1>
            <p class="text-sm text-slate-500 mt-1">Gestisci la rubrica clienti</p>
        </div>
        <button onclick="openModal('clienteModal'); resetClienteForm();" 
                class="w-full sm:w-auto bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-3 sm:py-2.5 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors min-h-[44px]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuovo Cliente
        </button>
    </div>
</div>

<!-- Filtri Mobile (Accordion) -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-4 sm:mb-6 overflow-hidden">
    <!-- Mobile Toggle -->
    <button onclick="toggleFilters()" class="w-full sm:hidden flex items-center justify-between p-4 min-h-[44px]">
        <div class="flex items-center gap-2 text-slate-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            <span class="font-medium">Filtri</span>
        </div>
        <svg id="filterArrow" class="w-5 h-5 text-slate-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    
    <!-- Filters Content -->
    <div id="filtersContent" class="hidden sm:block p-4 border-t sm:border-t-0 border-slate-200">
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
            <div class="relative flex-1">
                <input type="text" id="searchInput" placeholder="Cerca cliente..."
                       class="w-full pl-10 pr-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none min-h-[44px]">
                <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3.5 sm:top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <select id="tipoFilter" class="w-full sm:w-48 px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                <option value="">Tutti i tipi</option>
                <option value="Azienda">Azienda</option>
                <option value="Privato">Privato</option>
                <option value="Partita IVA">Partita IVA</option>
            </select>
        </div>
    </div>
</div>

<!-- Lista Clienti -->
<div id="clientiContainer" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
    <div class="col-span-full text-center py-12">
        <div class="animate-spin w-8 h-8 border-2 border-cyan-500 border-t-transparent rounded-full mx-auto"></div>
        <p class="text-sm text-slate-500 mt-2">Caricamento clienti...</p>
    </div>
</div>

<!-- Modal Cliente -->
<div id="clienteModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModal('clienteModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-2xl sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[85vh] sm:max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-4 sm:p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h2 class="text-lg sm:text-xl font-bold text-slate-800" id="modalTitle">Nuovo Cliente</h2>
                <button onclick="closeModal('clienteModal')" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 min-h-[44px] min-w-[44px] flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="clienteForm" class="flex-1 overflow-y-auto p-4 sm:p-6" enctype="multipart/form-data">
                <input type="hidden" name="id" id="clienteId">
                
                <div class="space-y-5">
                    <!-- Logo Upload -->
                    <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-xl">
                        <div id="logoPreview" class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl flex items-center justify-center text-white text-xl font-bold overflow-hidden flex-shrink-0">
                            <span id="logoPlaceholder">C</span>
                            <img id="logoImg" src="" alt="Logo" class="w-full h-full object-cover hidden">
                        </div>
                        <div class="flex-1 min-w-0">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Logo Cliente</label>
                            <input type="file" name="logo" id="logoInput" accept="image/jpeg,image/png,image/gif,image/webp"
                                   class="w-full text-sm text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 min-h-[44px]"
                                   onchange="previewLogo(this)">
                            <p class="text-xs text-slate-500 mt-1">JPG, PNG o GIF. Max 2MB</p>
                            <input type="hidden" name="logo_existing" id="logoExisting">
                        </div>
                    </div>
                    
                    <!-- Ragione Sociale e Tipo -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Ragione Sociale *</label>
                            <input type="text" name="ragione_sociale" id="ragioneSocialeInput" required
                                   class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]"
                                   oninput="updateLogoPlaceholder()">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Tipo</label>
                            <select name="tipo" class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                                <option value="Azienda">Azienda</option>
                                <option value="Privato">Privato</option>
                                <option value="Partita IVA">Partita IVA</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- P.IVA/CF -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Partita IVA / Codice Fiscale</label>
                        <input type="text" name="piva_cf"
                               class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                    </div>
                    
                    <!-- Indirizzo -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-2">Indirizzo</label>
                            <input type="text" name="indirizzo"
                                   class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Città</label>
                            <input type="text" name="citta"
                                   class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">CAP</label>
                                <input type="text" name="cap"
                                       class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Prov.</label>
                                <input type="text" name="provincia"
                                       class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contatti -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Telefono</label>
                            <input type="tel" name="telefono"
                                   class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Cellulare</label>
                            <input type="tel" name="cellulare"
                                   class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                            <input type="email" name="email"
                                   class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">PEC</label>
                            <input type="email" name="pec"
                                   class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                        </div>
                    </div>
                    
                    <!-- Social / Web -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Sito Web</label>
                        <input type="url" name="sito_web"
                               class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]"
                               placeholder="https://...">
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Instagram</label>
                            <input type="text" name="instagram"
                                   class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]"
                                   placeholder="@username">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Facebook</label>
                            <input type="text" name="facebook"
                                   class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">LinkedIn</label>
                            <input type="text" name="linkedin"
                                   class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none min-h-[44px]">
                        </div>
                    </div>
                    
                    <!-- Note -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Note</label>
                        <textarea name="note" rows="3"
                                  class="w-full px-4 py-3 sm:py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none min-h-[100px]"></textarea>
                    </div>
                </div>
            </form>
            
            <div class="p-3 sm:p-6 border-t border-slate-100 flex flex-row justify-end gap-2 sticky bottom-0 bg-white z-10">
                <button type="button" onclick="closeModal('clienteModal')" 
                        class="px-3 py-2 sm:px-4 sm:py-2 text-sm sm:text-base text-slate-600 hover:text-slate-800 font-medium rounded-lg hover:bg-slate-100 transition-colors">
                    Annulla
                </button>
                <button type="button" onclick="saveCliente()" 
                        class="w-full sm:w-auto px-6 py-3 sm:py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium min-h-[44px]">
                    Salva
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dettaglio Cliente -->
<div id="dettaglioModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModal('dettaglioModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-2xl sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[85vh] sm:max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-4 sm:p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h2 class="text-lg sm:text-xl font-bold text-slate-800" id="dettaglioNome">Cliente</h2>
                <button onclick="closeModal('dettaglioModal')" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 min-h-[44px] min-w-[44px] flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div id="dettaglioContent" class="flex-1 overflow-y-auto p-4 sm:p-6">
                <!-- Popolato via JS -->
            </div>
            
            <div class="p-3 sm:p-6 border-t border-slate-100 flex flex-row justify-end gap-2 sticky bottom-0 bg-white z-10">
                <button type="button" onclick="closeModal('dettaglioModal')" 
                        class="w-full sm:w-auto px-4 py-3 sm:py-2 text-slate-600 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors">
                    Chiudi
                </button>
                <button type="button" onclick="editClienteFromDetail()" 
                        class="w-full sm:w-auto px-6 py-3 sm:py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium min-h-[44px]">
                    Modifica
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let clientiData = [];
let currentClienteId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadClienti().then(() => {
        // Controlla se c'è un parametro 'open' per aprire un cliente specifico
        const urlParams = new URLSearchParams(window.location.search);
        const openClienteId = urlParams.get('open');
        if (openClienteId) {
            showDettaglio(openClienteId);
            // Rimuovi il parametro dall'URL senza ricaricare
            urlParams.delete('open');
            const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
            window.history.replaceState({}, '', newUrl);
        }
    });
    
    document.getElementById('searchInput').addEventListener('input', debounce(loadClienti, 300));
    document.getElementById('tipoFilter').addEventListener('change', loadClienti);
});

// Toggle filtri mobile
function toggleFilters() {
    const content = document.getElementById('filtersContent');
    const arrow = document.getElementById('filterArrow');
    content.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');
}

async function loadClienti() {
    const search = document.getElementById('searchInput').value;
    const tipo = document.getElementById('tipoFilter').value;
    
    let url = 'api/clienti.php?action=list';
    if (search) url += '&search=' + encodeURIComponent(search);
    if (tipo) url += '&tipo=' + encodeURIComponent(tipo);
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            clientiData = data.data;
            renderClienti(data.data);
        }
    } catch (error) {
        showToast('Errore caricamento clienti', 'error');
    }
}

async function loadPreventiviCliente(clienteId, clienteNome) {
    const container = document.getElementById('preventiviClienteContent');
    if (!container) return;
    
    try {
        const response = await fetch(`api/preventivi.php?action=list_preventivi_salvati`);
        const data = await response.json();
        
        if (data.success) {
            // Filtra preventivi per questo cliente (per ID o per nome)
            const preventivi = data.data.filter(p => {
                const matchId = p.cliente_id === clienteId;
                const matchNome = p.cliente_nome && clienteNome && 
                    p.cliente_nome.toLowerCase() === clienteNome.toLowerCase();
                return matchId || matchNome;
            });
            
            if (preventivi.length === 0) {
                container.innerHTML = '<p class="text-slate-400">Nessun preventivo associato</p>';
                return;
            }
            
            container.innerHTML = preventivi.map(p => {
                const servizi = JSON.parse(p.servizi_json || '[]');
                const numServizi = servizi.length;
                const isAssociato = p.progetto_id ? true : false;
                const dataPrev = new Date(p.created_at).toLocaleDateString('it-IT');
                
                return `
                    <div class="p-3 bg-slate-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-medium text-slate-800">${p.numero}</span>
                                <span class="text-xs text-slate-500 ml-2">${dataPrev}</span>
                            </div>
                            <span class="text-sm font-medium text-cyan-600">€${parseFloat(p.totale).toFixed(2)}</span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-sm text-slate-500">${numServizi} servizi</span>
                            ${isAssociato ? 
                                '<span class="text-xs text-emerald-600">✓ Associato</span>' : 
                                '<span class="text-xs text-amber-600">⚠ Non associato</span>'
                            }
                        </div>
                        ${p.file_path ? `
                        <div class="mt-2">
                            <a href="assets/uploads/preventivi/${p.file_path}" target="_blank" 
                               class="text-sm text-cyan-600 hover:underline">Visualizza preventivo</a>
                        </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<p class="text-slate-400">Errore caricamento preventivi</p>';
        }
    } catch (error) {
        container.innerHTML = '<p class="text-slate-400">Errore caricamento</p>';
    }
}

function renderClienti(clienti) {
    const container = document.getElementById('clientiContainer');
    
    if (clienti.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-12">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-600">Nessun cliente trovato</h3>
            </div>
        `;
        return;
    }
    
    container.innerHTML = clienti.map(c => `
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-slate-200 overflow-hidden card-hover h-full flex flex-col">
            <div class="p-4 sm:p-5 flex-1">
                <div class="flex items-start justify-between mb-3">
                    ${c.logo_path ? `
                        <div class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0 bg-white border border-slate-100">
                            <img src="assets/uploads/${c.logo_path}" alt="Logo ${c.ragione_sociale}" class="w-full h-full object-cover">
                        </div>
                    ` : `
                        <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
                            ${c.ragione_sociale.charAt(0).toUpperCase()}
                        </div>
                    `}
                    <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-medium">
                        ${c.tipo}
                    </span>
                </div>
                
                <h3 class="font-semibold text-base sm:text-lg text-slate-800 mb-1">${c.ragione_sociale}</h3>
                <p class="text-sm text-slate-500 mb-3">${c.piva_cf || 'Nessun codice fiscale/partita IVA'}</p>
                
                <div class="space-y-2 text-sm">
                    ${c.telefono ? `
                        <div class="flex items-center justify-between">
                            <p class="flex items-center gap-2 text-slate-600 truncate pr-2">
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span class="truncate">${c.telefono}</span>
                            </p>
                            <a href="tel:${c.telefono.replace(/\s/g, '')}" class="p-2 text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center" title="Chiama">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </a>
                        </div>
                    ` : ''}
                    ${c.cellulare ? `
                        <div class="flex items-center justify-between">
                            <p class="flex items-center gap-2 text-slate-600 truncate pr-2">
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                <span class="truncate">${c.cellulare}</span>
                            </p>
                            <div class="flex items-center gap-1">
                                <a href="tel:${c.cellulare.replace(/\s/g, '')}" class="p-2 text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center" title="Chiama">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </a>
                                <a href="https://wa.me/${c.cellulare.replace(/[^0-9]/g, '').replace(/^0/, '39')}" target="_blank" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center" title="WhatsApp">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </a>
                            </div>
                        </div>
                    ` : ''}
                    ${c.email ? `
                        <div class="flex items-center justify-between">
                            <p class="flex items-center gap-2 text-slate-600 truncate pr-2">
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span class="truncate">${c.email}</span>
                            </p>
                            <a href="mailto:${c.email}" class="p-2 text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center" title="Invia email">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            </a>
                        </div>
                    ` : ''}
                </div>
                
                <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-sm text-slate-500">${c.num_progetti || 0} progetti</span>
                    <span class="text-sm ${c.progetti_attivi > 0 ? 'text-cyan-600 font-medium' : 'text-slate-400'}">${c.progetti_attivi || 0} attivi</span>
                </div>
            </div>
            
            <div class="px-4 sm:px-5 py-3 bg-slate-50 border-t border-slate-100 flex gap-2 mt-auto">
                <button onclick="showDettaglio('${c.id}')" class="flex-1 text-center py-2.5 sm:py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors min-h-[44px]">
                    Dettagli
                </button>
                <button onclick="editCliente('${c.id}')" class="p-2.5 sm:p-2 text-slate-400 hover:text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button onclick="deleteCliente('${c.id}', '${c.ragione_sociale.replace(/'/g, "\\'")}')" class="p-2.5 sm:p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
    `).join('');
}

async function showDettaglio(id) {
    currentClienteId = id;
    try {
        const response = await fetch(`api/clienti.php?action=detail&id=${id}`);
        const data = await response.json();
        
        if (!data.success) {
            showToast('Errore caricamento', 'error');
            return;
        }
        
        const c = data.data;
        document.getElementById('dettaglioNome').textContent = c.ragione_sociale;
        
        document.getElementById('dettaglioContent').innerHTML = `
            <div class="space-y-6">
                <!-- Logo e nome -->
                <div class="flex items-center gap-4">
                    ${c.logo_path ? `
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden flex-shrink-0 bg-white border border-slate-200 p-2">
                            <img src="assets/uploads/${c.logo_path}" alt="Logo ${c.ragione_sociale}" class="w-full h-full object-contain">
                        </div>
                    ` : `
                        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
                            ${c.ragione_sociale.charAt(0).toUpperCase()}
                        </div>
                    `}
                    <div class="min-w-0">
                        <h3 class="text-lg sm:text-xl font-bold text-slate-800 truncate">${c.ragione_sociale}</h3>
                        <p class="text-slate-500">${c.tipo}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <p class="text-sm text-slate-500 mb-1">P.IVA / CF</p>
                        <p class="font-medium text-slate-800 text-sm sm:text-base">${c.piva_cf || '-'}</p>
                    </div>
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <p class="text-sm text-slate-500 mb-1">Progetti</p>
                        <p class="font-medium text-slate-800 text-sm sm:text-base">${c.progetti ? c.progetti.length : 0}</p>
                    </div>
                </div>
                
                ${c.indirizzo ? `
                <div>
                    <h4 class="font-medium text-slate-700 mb-2">Indirizzo</h4>
                    <p class="text-slate-600">${c.indirizzo}</p>
                    <p class="text-slate-600">${c.cap} ${c.citta} (${c.provincia})</p>
                </div>
                ` : ''}
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    ${c.telefono ? `
                        <div class="p-3 bg-slate-50 rounded-xl">
                            <p class="text-sm text-slate-500 mb-1">Telefono</p>
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium text-sm sm:text-base truncate">${c.telefono}</p>
                                <a href="tel:${c.telefono.replace(/\s/g, '')}" class="p-2 text-cyan-600 hover:bg-cyan-100 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center flex-shrink-0" title="Chiama">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </a>
                            </div>
                        </div>
                    ` : ''}
                    ${c.cellulare ? `
                        <div class="p-3 bg-slate-50 rounded-xl">
                            <p class="text-sm text-slate-500 mb-1">Cellulare</p>
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium text-sm sm:text-base truncate">${c.cellulare}</p>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <a href="tel:${c.cellulare.replace(/\s/g, '')}" class="p-2 text-cyan-600 hover:bg-cyan-100 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center" title="Chiama">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    </a>
                                    <a href="https://wa.me/${c.cellulare.replace(/[^0-9]/g, '').replace(/^0/, '39')}" target="_blank" class="p-2 text-emerald-600 hover:bg-emerald-100 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center" title="WhatsApp">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    ${c.email ? `
                        <div class="p-3 bg-slate-50 rounded-xl">
                            <p class="text-sm text-slate-500 mb-1">Email</p>
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium text-sm sm:text-base truncate">${c.email}</p>
                                <a href="mailto:${c.email}" class="p-2 text-cyan-600 hover:bg-cyan-100 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center flex-shrink-0" title="Invia email">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                </a>
                            </div>
                        </div>
                    ` : ''}
                    ${c.pec ? `
                        <div class="p-3 bg-slate-50 rounded-xl">
                            <p class="text-sm text-slate-500 mb-1">PEC</p>
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium text-sm sm:text-base truncate">${c.pec}</p>
                                <a href="mailto:${c.pec}" class="p-2 text-cyan-600 hover:bg-cyan-100 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center flex-shrink-0" title="Invia PEC">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                </a>
                            </div>
                        </div>
                    ` : ''}
                </div>
                
                ${(c.sito_web || c.instagram || c.facebook || c.linkedin) ? `
                <div>
                    <h4 class="font-medium text-slate-700 mb-2">Web & Social</h4>
                    <div class="flex flex-wrap gap-2">
                        ${c.sito_web ? `<a href="${c.sito_web}" target="_blank" class="px-3 py-2 bg-cyan-50 text-cyan-700 rounded-lg text-sm hover:bg-cyan-100 min-h-[44px] flex items-center">🌐 Sito Web</a>` : ''}
                        ${c.instagram ? `<a href="https://instagram.com/${c.instagram.replace('@', '')}" target="_blank" class="px-3 py-2 bg-pink-50 text-pink-700 rounded-lg text-sm hover:bg-pink-100 min-h-[44px] flex items-center">📷 Instagram</a>` : ''}
                        ${c.facebook ? `<span class="px-3 py-2 bg-blue-50 text-blue-700 rounded-lg text-sm min-h-[44px] flex items-center">📘 Facebook</span>` : ''}
                        ${c.linkedin ? `<span class="px-3 py-2 bg-blue-50 text-blue-700 rounded-lg text-sm min-h-[44px] flex items-center">💼 LinkedIn</span>` : ''}
                    </div>
                </div>
                ` : ''}
                
                ${c.note ? `
                <div>
                    <h4 class="font-medium text-slate-700 mb-2">Note</h4>
                    <p class="text-slate-600 whitespace-pre-wrap">${c.note}</p>
                </div>
                ` : ''}
                
                <div>
                    <h4 class="font-medium text-slate-700 mb-3">Progetti</h4>
                    ${c.progetti && c.progetti.length > 0 ? `
                        <div class="space-y-2">
                            ${c.progetti.map(p => `
                                <a href="progetto_dettaglio.php?id=${p.id}" class="block p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-slate-800">${p.titolo}</span>
                                        <span class="text-sm text-slate-500">${p.stato_progetto}</span>
                                    </div>
                                    <p class="text-sm text-slate-500 mt-1">€${parseFloat(p.prezzo_totale).toFixed(2)}</p>
                                </a>
                            `).join('')}
                        </div>
                    ` : '<p class="text-slate-400">Nessun progetto</p>'}
                </div>
                
                <!-- Preventivi Associati -->
                <div id="preventiviClienteSection">
                    <h4 class="font-medium text-slate-700 mb-3">Preventivi</h4>
                    <div id="preventiviClienteContent" class="space-y-2">
                        <p class="text-slate-400">Caricamento...</p>
                    </div>
                </div>
            </div>
        `;
        
        // Carica preventivi associati al cliente
        loadPreventiviCliente(c.id, c.ragione_sociale);
        
        openModal('dettaglioModal');
    } catch (error) {
        showToast('Errore caricamento', 'error');
    }
}

function editClienteFromDetail() {
    closeModal('dettaglioModal');
    editCliente(currentClienteId);
}

async function editCliente(id) {
    const cliente = clientiData.find(c => c.id === id);
    if (!cliente) {
        // Carica da API
        try {
            const response = await fetch(`api/clienti.php?action=detail&id=${id}`);
            const data = await response.json();
            if (data.success) fillClienteForm(data.data);
        } catch (e) {}
    } else {
        fillClienteForm(cliente);
    }
}

function resetClienteForm() {
    document.getElementById('clienteForm').reset();
    document.getElementById('clienteId').value = '';
    document.getElementById('modalTitle').textContent = 'Nuovo Cliente';
    resetLogoPreview();
}

function resetLogoPreview() {
    const logoImg = document.getElementById('logoImg');
    const logoPlaceholder = document.getElementById('logoPlaceholder');
    const logoExisting = document.getElementById('logoExisting');
    
    logoImg.src = '';
    logoImg.classList.add('hidden');
    logoPlaceholder.classList.remove('hidden');
    logoPlaceholder.textContent = 'C';
    logoExisting.value = '';
    document.getElementById('logoInput').value = '';
}

function previewLogo(input) {
    const preview = document.getElementById('logoImg');
    const placeholder = document.getElementById('logoPlaceholder');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const maxSize = 2 * 1024 * 1024; // 2MB
        
        // Verifica dimensione
        if (file.size > maxSize) {
            showToast('Il file è troppo grande. Massimo 2MB consentiti.', 'error');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updateLogoPlaceholder() {
    const nome = document.getElementById('ragioneSocialeInput').value;
    const placeholder = document.getElementById('logoPlaceholder');
    const logoExisting = document.getElementById('logoExisting').value;
    
    // Solo se non c'è un logo esistente o caricato
    if (!logoExisting && !document.getElementById('logoInput').files[0]) {
        placeholder.textContent = nome ? nome.charAt(0).toUpperCase() : 'C';
    }
}

function fillClienteForm(c) {
    document.getElementById('modalTitle').textContent = 'Modifica Cliente';
    document.getElementById('clienteId').value = c.id;
    document.getElementById('ragioneSocialeInput').value = c.ragione_sociale;
    document.querySelector('input[name="ragione_sociale"]').value = c.ragione_sociale;
    document.querySelector('select[name="tipo"]').value = c.tipo;
    document.querySelector('input[name="piva_cf"]').value = c.piva_cf || '';
    document.querySelector('input[name="indirizzo"]').value = c.indirizzo || '';
    document.querySelector('input[name="citta"]').value = c.citta || '';
    document.querySelector('input[name="cap"]').value = c.cap || '';
    document.querySelector('input[name="provincia"]').value = c.provincia || '';
    document.querySelector('input[name="telefono"]').value = c.telefono || '';
    document.querySelector('input[name="cellulare"]').value = c.cellulare || '';
    document.querySelector('input[name="email"]').value = c.email || '';
    document.querySelector('input[name="pec"]').value = c.pec || '';
    document.querySelector('input[name="sito_web"]').value = c.sito_web || '';
    document.querySelector('input[name="instagram"]').value = c.instagram || '';
    document.querySelector('input[name="facebook"]').value = c.facebook || '';
    document.querySelector('input[name="linkedin"]').value = c.linkedin || '';
    document.querySelector('textarea[name="note"]').value = c.note || '';
    
    // Gestione logo
    const logoImg = document.getElementById('logoImg');
    const logoPlaceholder = document.getElementById('logoPlaceholder');
    const logoExisting = document.getElementById('logoExisting');
    
    if (c.logo_path) {
        logoImg.src = 'assets/uploads/' + c.logo_path;
        logoImg.classList.remove('hidden');
        logoPlaceholder.classList.add('hidden');
        logoExisting.value = c.logo_path;
    } else {
        logoImg.src = '';
        logoImg.classList.add('hidden');
        logoPlaceholder.classList.remove('hidden');
        logoPlaceholder.textContent = c.ragione_sociale ? c.ragione_sociale.charAt(0).toUpperCase() : 'C';
        logoExisting.value = '';
    }
    
    // Reset input file
    document.getElementById('logoInput').value = '';
    
    openModal('clienteModal');
}

async function saveCliente() {
    const form = document.getElementById('clienteForm');
    const formData = new FormData(form);
    const id = document.getElementById('clienteId').value;
    
    const action = id ? 'update' : 'create';
    if (id) formData.append('id', id);
    
    // Debug: log formData contents
    for (let pair of formData.entries()) {
        console.log('FormData:', pair[0], pair[1] instanceof File ? `File: ${pair[1].name} (${pair[1].size} bytes)` : pair[1]);
    }
    
    try {
        const response = await fetch(`api/clienti.php?action=${action}`, {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', response.status);
        
        const text = await response.text();
        console.log('Response text:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            showToast('Errore nel parsing della risposta', 'error');
            return;
        }
        
        if (data.success) {
            showToast(id ? 'Cliente aggiornato' : 'Cliente creato', 'success');
            closeModal('clienteModal');
            form.reset();
            document.getElementById('clienteId').value = '';
            loadClienti();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        console.error('Fetch error:', error);
        showToast('Errore di connessione', 'error');
    }
}

function deleteCliente(id, nome) {
    confirmAction(`Eliminare il cliente "${nome}"?`, async () => {
        try {
            const response = await fetch('api/clienti.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete&id=${id}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Cliente eliminato', 'success');
                loadClienti();
            } else {
                showToast(data.message || 'Errore', 'error');
            }
        } catch (error) {
            showToast('Errore', 'error');
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
