<?php
/**
 * Eterea Gestionale
 * Scadenze
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Scadenze';
include __DIR__ . '/includes/header.php';

// Carica utenti per select
$utenti = [];
try {
    $stmt = $pdo->query("SELECT id, nome FROM utenti ORDER BY nome ASC");
    $utenti = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Errore caricamento utenti: " . $e->getMessage());
}

// Carica clienti per select
$clienti = [];
try {
    $stmt = $pdo->query("SELECT id, nome FROM clienti ORDER BY nome ASC");
    $clienti = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Errore caricamento clienti: " . $e->getMessage());
}
?>

<!-- Header -->
<div class="mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Scadenze</h1>
            <p class="text-sm text-slate-500 mt-1">Gestisci le tue scadenze e impegni</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openTipologiaModal()" 
                    class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-medium flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Tipologie
            </button>
            <button onclick="openScadenzaModal()" 
                    class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-medium flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Nuova Scadenza
            </button>
        </div>
    </div>
</div>

<!-- Filtri -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
    <div class="flex flex-col lg:flex-row gap-4">
        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1">Stato</label>
            <select id="filtroStato" onchange="caricaScadenze()" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-rose-500 outline-none">
                <option value="">Tutti gli stati</option>
                <option value="aperta">Aperte</option>
                <option value="completata">Completate</option>
                <option value="scaduta">Scadute</option>
            </select>
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1">Tipologia</label>
            <select id="filtroTipologia" onchange="caricaScadenze()" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-rose-500 outline-none">
                <option value="">Tutte le tipologie</option>
            </select>
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1">Mese</label>
            <select id="filtroMese" onchange="caricaScadenze()" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-rose-500 outline-none">
                <option value="">Tutti i mesi</option>
                <option value="1">Gennaio</option>
                <option value="2">Febbraio</option>
                <option value="3">Marzo</option>
                <option value="4">Aprile</option>
                <option value="5">Maggio</option>
                <option value="6">Giugno</option>
                <option value="7">Luglio</option>
                <option value="8">Agosto</option>
                <option value="9">Settembre</option>
                <option value="10">Ottobre</option>
                <option value="11">Novembre</option>
                <option value="12">Dicembre</option>
            </select>
        </div>
        <div class="flex-1">
            <label class="block text-sm font-medium text-slate-700 mb-1">Anno</label>
            <select id="filtroAnno" onchange="caricaScadenze()" class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-rose-500 outline-none">
                <option value="">Tutti gli anni</option>
                <option value="2024">2024</option>
                <option value="2025" selected>2025</option>
                <option value="2026">2026</option>
                <option value="2027">2027</option>
            </select>
        </div>
    </div>
</div>

<!-- Lista Scadenze -->
<div id="scadenzeContainer" class="space-y-4">
    <div class="text-center py-12 text-slate-400">
        <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p>Caricamento scadenze...</p>
    </div>
</div>

<!-- Modal Scadenza (Crea/Modifica) -->
<div id="modalScadenza" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeScadenzaModal()"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-lg sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-4 sm:p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="text-lg sm:text-xl font-bold text-slate-800" id="titoloModalScadenza">Nuova Scadenza</h3>
                <button onclick="closeScadenzaModal()" class="p-2 -mr-2 text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
                <input type="hidden" id="scadenzaId">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Titolo *</label>
                    <input type="text" id="scadenzaTitolo" 
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-rose-500 outline-none"
                           placeholder="es. Pagamento fattura cliente X">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Data Scadenza *</label>
                        <input type="date" id="scadenzaData" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-rose-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tipologia</label>
                        <select id="scadenzaTipologia" 
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-rose-500 outline-none">
                            <option value="">Seleziona tipologia</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Descrizione</label>
                    <textarea id="scadenzaDescrizione" rows="3"
                              class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-rose-500 outline-none resize-none"
                              placeholder="Inserisci una descrizione..."></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Assegnata a</label>
                        <select id="scadenzaUser" 
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-rose-500 outline-none">
                            <option value="">Nessuno</option>
                            <?php foreach ($utenti as $u): ?>
                                <option value="<?php echo e($u['id']); ?>"><?php echo e($u['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cliente</label>
                        <select id="scadenzaCliente" 
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-rose-500 outline-none">
                            <option value="">Nessuno</option>
                            <?php foreach ($clienti as $c): ?>
                                <option value="<?php echo e($c['id']); ?>"><?php echo e($c['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Link</label>
                    <input type="url" id="scadenzaLink" 
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-rose-500 outline-none"
                           placeholder="https://...">
                </div>
            </div>
            
            <div class="p-4 sm:p-6 border-t border-slate-100 flex flex-row justify-end gap-3">
                <button type="button" onclick="closeScadenzaModal()" 
                        class="px-4 py-2.5 text-slate-600 hover:text-slate-800 font-medium rounded-lg hover:bg-slate-100 transition-colors">
                    Annulla
                </button>
                <button type="button" onclick="salvaScadenza()" 
                        class="px-6 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-medium rounded-lg transition-colors">
                    Salva Scadenza
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tipologie -->
<div id="modalTipologie" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeTipologiaModal()"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-md sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[85vh] overflow-hidden flex flex-col">
            <div class="p-4 sm:p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="text-lg sm:text-xl font-bold text-slate-800">Gestione Tipologie</h3>
                <button onclick="closeTipologiaModal()" class="p-2 -mr-2 text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                <!-- Aggiungi nuova -->
                <div class="mb-6 p-4 bg-slate-50 rounded-xl">
                    <h4 class="text-sm font-medium text-slate-700 mb-3">Nuova Tipologia</h4>
                    <div class="flex gap-2">
                        <input type="text" id="nuovaTipologiaNome" 
                               class="flex-1 px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-rose-500 outline-none"
                               placeholder="Nome tipologia">
                        <input type="color" id="nuovaTipologiaColore" value="#ef4444"
                               class="w-12 h-10 border border-slate-200 rounded-lg cursor-pointer">
                        <button onclick="salvaTipologia()" 
                                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-medium">
                            Aggiungi
                        </button>
                    </div>
                </div>
                
                <!-- Lista tipologie -->
                <div>
                    <h4 class="text-sm font-medium text-slate-700 mb-3">Tipologie Esistenti</h4>
                    <div id="listaTipologie" class="space-y-2">
                        <p class="text-slate-400 text-center py-4">Caricamento...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// SCADENZE
// ============================================

let tipologieCache = [];
let scadenzeCache = [];

// Carica scadenze al caricamento della pagina
document.addEventListener('DOMContentLoaded', function() {
    caricaTipologie();
    caricaScadenze();
});

async function caricaScadenze() {
    const stato = document.getElementById('filtroStato').value;
    const tipologia = document.getElementById('filtroTipologia').value;
    const mese = document.getElementById('filtroMese').value;
    const anno = document.getElementById('filtroAnno').value;
    
    let url = 'api/scadenze.php?action=list';
    if (stato) url += '&stato=' + stato;
    if (tipologia) url += '&tipologia=' + tipologia;
    if (mese && anno) {
        url += '&mese=' + mese + '&anno=' + anno;
    }
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        const container = document.getElementById('scadenzeContainer');
        
        if (!data.success) {
            container.innerHTML = '<div class="text-center py-8 text-red-500">Errore caricamento scadenze</div>';
            return;
        }
        
        scadenzeCache = data.data;
        
        if (scadenzeCache.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12 text-slate-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-lg">Nessuna scadenza trovata</p>
                    <p class="text-sm mt-2">Crea una nuova scadenza per iniziare</p>
                </div>
            `;
            return;
        }
        
        // Raggruppa per mese
        const gruppi = {};
        scadenzeCache.forEach(s => {
            const data = new Date(s.data_scadenza);
            const key = data.toLocaleDateString('it-IT', { month: 'long', year: 'numeric' });
            if (!gruppi[key]) gruppi[key] = [];
            gruppi[key].push(s);
        });
        
        let html = '';
        for (const [mese, scadenze] of Object.entries(gruppi)) {
            html += `
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3 px-1">${mese}</h3>
                    <div class="space-y-3">
                        ${scadenze.map(s => renderScadenzaCard(s)).join('')}
                    </div>
                </div>
            `;
        }
        
        container.innerHTML = html;
        
    } catch (error) {
        console.error('Errore:', error);
        document.getElementById('scadenzeContainer').innerHTML = 
            '<div class="text-center py-8 text-red-500">Errore di connessione</div>';
    }
}

function renderScadenzaCard(s) {
    const data = new Date(s.data_scadenza);
    const giorno = data.getDate();
    const settimana = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'][data.getDay()];
    
    const isScaduta = s.stato === 'scaduta' || (new Date(s.data_scadenza) < new Date().setHours(0,0,0,0) && s.stato === 'aperta');
    const isCompletata = s.stato === 'completata';
    
    let statoClass = 'bg-emerald-100 text-emerald-700';
    let statoText = 'Aperta';
    if (isCompletata) {
        statoClass = 'bg-slate-100 text-slate-600';
        statoText = 'Completata';
    } else if (isScaduta) {
        statoClass = 'bg-red-100 text-red-700';
        statoText = 'Scaduta';
    }
    
    const tipologiaColore = s.tipologia_colore || '#64748b';
    const tipologiaNome = s.tipologia_nome || 'Altro';
    
    return `
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 hover:shadow-md transition-shadow ${isCompletata ? 'opacity-60' : ''}">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 text-center w-14">
                    <div class="text-xs text-slate-500 uppercase">${settimana}</div>
                    <div class="text-2xl font-bold ${isScaduta && !isCompletata ? 'text-red-600' : 'text-slate-800'}">${giorno}</div>
                </div>
                
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h4 class="font-semibold text-slate-800 ${isCompletata ? 'line-through' : ''}">${escapeHtml(s.titolo)}</h4>
                            ${s.cliente_nome ? `<p class="text-sm text-slate-500">Cliente: ${escapeHtml(s.cliente_nome)}</p>` : ''}
                            ${s.user_nome ? `<p class="text-sm text-slate-500">Assegnata a: ${escapeHtml(s.user_nome)}</p>` : ''}
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-medium ${statoClass}">${statoText}</span>
                    </div>
                    
                    ${s.descrizione ? `<p class="text-sm text-slate-600 mt-2">${escapeHtml(s.descrizione)}</p>` : ''}
                    
                    <div class="flex items-center gap-2 mt-3">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium" style="background-color: ${tipologiaColore}20; color: ${tipologiaColore}">
                            <span class="w-2 h-2 rounded-full" style="background-color: ${tipologiaColore}"></span>
                            ${escapeHtml(tipologiaNome)}
                        </span>
                        
                        ${s.link ? `<a href="${escapeHtml(s.link)}" target="_blank" class="text-xs text-rose-600 hover:text-rose-700 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Link
                        </a>` : ''}
                    </div>
                </div>
                
                <div class="flex items-center gap-1">
                    ${!isCompletata ? `
                        <button onclick="completaScadenza(${s.id})" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg" title="Completa">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    ` : ''}
                    <button onclick="modificaScadenza(${s.id})" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-50 rounded-lg" title="Modifica">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button onclick="eliminaScadenza(${s.id})" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Elimina">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================
// MODAL SCADENZA
// ============================================

function openScadenzaModal() {
    document.getElementById('scadenzaId').value = '';
    document.getElementById('scadenzaTitolo').value = '';
    document.getElementById('scadenzaData').value = '';
    document.getElementById('scadenzaTipologia').value = '';
    document.getElementById('scadenzaDescrizione').value = '';
    document.getElementById('scadenzaUser').value = '';
    document.getElementById('scadenzaCliente').value = '';
    document.getElementById('scadenzaLink').value = '';
    document.getElementById('titoloModalScadenza').textContent = 'Nuova Scadenza';
    document.getElementById('modalScadenza').classList.remove('hidden');
}

function closeScadenzaModal() {
    document.getElementById('modalScadenza').classList.add('hidden');
}

async function modificaScadenza(id) {
    try {
        const response = await fetch(`api/scadenze.php?action=detail&id=${id}`);
        const data = await response.json();
        
        if (!data.success) {
            showToast('Errore caricamento scadenza', 'error');
            return;
        }
        
        const s = data.data;
        document.getElementById('scadenzaId').value = s.id;
        document.getElementById('scadenzaTitolo').value = s.titolo;
        document.getElementById('scadenzaData').value = s.data_scadenza;
        document.getElementById('scadenzaTipologia').value = s.tipologia_id || '';
        document.getElementById('scadenzaDescrizione').value = s.descrizione || '';
        document.getElementById('scadenzaUser').value = s.user_id || '';
        document.getElementById('scadenzaCliente').value = s.cliente_id || '';
        document.getElementById('scadenzaLink').value = s.link || '';
        document.getElementById('titoloModalScadenza').textContent = 'Modifica Scadenza';
        document.getElementById('modalScadenza').classList.remove('hidden');
        
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

async function salvaScadenza() {
    const id = document.getElementById('scadenzaId').value;
    const titolo = document.getElementById('scadenzaTitolo').value.trim();
    const dataScadenza = document.getElementById('scadenzaData').value;
    
    if (!titolo || !dataScadenza) {
        showToast('Titolo e data scadenza sono obbligatori', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', id ? 'update' : 'create');
    if (id) formData.append('id', id);
    formData.append('titolo', titolo);
    formData.append('data_scadenza', dataScadenza);
    formData.append('tipologia_id', document.getElementById('scadenzaTipologia').value);
    formData.append('descrizione', document.getElementById('scadenzaDescrizione').value);
    formData.append('user_id', document.getElementById('scadenzaUser').value);
    formData.append('cliente_id', document.getElementById('scadenzaCliente').value);
    formData.append('link', document.getElementById('scadenzaLink').value);
    
    try {
        showToast('Salvataggio...', 'info');
        
        const response = await fetch('api/scadenze.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(id ? 'Scadenza aggiornata' : 'Scadenza creata', 'success');
            closeScadenzaModal();
            caricaScadenze();
            aggiornaNotificaSidebar();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore durante il salvataggio', 'error');
    }
}

async function completaScadenza(id) {
    confirmAction('Contrassegnare questa scadenza come completata?', async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'complete');
            formData.append('id', id);
            
            const response = await fetch('api/scadenze.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Scadenza completata', 'success');
                caricaScadenze();
                aggiornaNotificaSidebar();
            } else {
                showToast(data.message || 'Errore', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione', 'error');
        }
    });
}

async function eliminaScadenza(id) {
    confirmAction('Eliminare questa scadenza?', async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            const response = await fetch('api/scadenze.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Scadenza eliminata', 'success');
                caricaScadenze();
                aggiornaNotificaSidebar();
            } else {
                showToast(data.message || 'Errore', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione', 'error');
        }
    });
}

// ============================================
// TIPOLOGIE
// ============================================

async function caricaTipologie() {
    try {
        const response = await fetch('api/scadenze.php?action=tipologie');
        const data = await response.json();
        
        if (!data.success) return;
        
        tipologieCache = data.data;
        
        // Popola select nel form
        const selectForm = document.getElementById('scadenzaTipologia');
        const selectFiltro = document.getElementById('filtroTipologia');
        
        let options = '<option value="">Seleziona tipologia</option>';
        let optionsFiltro = '<option value="">Tutte le tipologie</option>';
        
        tipologieCache.forEach(t => {
            options += `<option value="${t.id}">${escapeHtml(t.nome)}</option>`;
            optionsFiltro += `<option value="${t.id}">${escapeHtml(t.nome)}</option>`;
        });
        
        selectForm.innerHTML = options;
        selectFiltro.innerHTML = optionsFiltro;
        
        // Popola lista nel modal
        renderListaTipologie();
        
    } catch (error) {
        console.error('Errore caricamento tipologie:', error);
    }
}

function renderListaTipologie() {
    const container = document.getElementById('listaTipologie');
    
    if (tipologieCache.length === 0) {
        container.innerHTML = '<p class="text-slate-400 text-center py-4">Nessuna tipologia</p>';
        return;
    }
    
    container.innerHTML = tipologieCache.map(t => `
        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full" style="background-color: ${t.colore || '#64748b'}"></span>
                <span class="text-sm font-medium text-slate-700">${escapeHtml(t.nome)}</span>
            </div>
            <button onclick="eliminaTipologia(${t.id}, '${escapeHtml(t.nome)}')" 
                    class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    `).join('');
}

function openTipologiaModal() {
    document.getElementById('modalTipologie').classList.remove('hidden');
    caricaTipologie();
}

function closeTipologiaModal() {
    document.getElementById('modalTipologie').classList.add('hidden');
}

async function salvaTipologia() {
    const nome = document.getElementById('nuovaTipologiaNome').value.trim();
    const colore = document.getElementById('nuovaTipologiaColore').value;
    
    if (!nome) {
        showToast('Inserisci un nome', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'create_tipologia');
        formData.append('nome', nome);
        formData.append('colore', colore);
        
        const response = await fetch('api/scadenze.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Tipologia creata', 'success');
            document.getElementById('nuovaTipologiaNome').value = '';
            caricaTipologie();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function eliminaTipologia(id, nome) {
    confirmAction(`Eliminare la tipologia "${nome}"?`, async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'delete_tipologia');
            formData.append('id', id);
            
            const response = await fetch('api/scadenze.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Tipologia eliminata', 'success');
                caricaTipologie();
            } else {
                showToast(data.message || 'Errore', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione', 'error');
        }
    });
}

// Aggiorna notifica in sidebar
async function aggiornaNotificaSidebar() {
    // Questa funzione verrà chiamata dal file sidebar
    if (typeof window.updateScadenzeBadge === 'function') {
        window.updateScadenzeBadge();
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
