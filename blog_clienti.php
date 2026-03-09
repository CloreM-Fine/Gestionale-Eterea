<?php
/**
 * Eterea Gestionale
 * Blog Clienti - Sezione per Lorenzo Puccetti
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

// Accesso solo per Lorenzo Puccetti
$userId = $_SESSION['user_id'] ?? '';
if ($userId !== 'ugv7adudxudhx') {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = 'Blog Clienti';
include __DIR__ . '/includes/header.php';

// Carica lista clienti per filtri
$clienti = [];
try {
    $stmt = $pdo->query("SELECT id, ragione_sociale FROM clienti ORDER BY ragione_sociale ASC");
    $clienti = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Errore caricamento clienti: " . $e->getMessage());
}
?>

<!-- Header -->
<div class="mb-4 sm:mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Blog Clienti</h1>
            <p class="text-sm text-slate-500 mt-1">Gestisci contenuti caricati dai clienti</p>
        </div>
        <button onclick="openGeneraLinkModal()" 
                class="w-full sm:w-auto bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-3 sm:py-2.5 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors min-h-[44px]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            Genera Link
        </button>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200">
        <p class="text-xs text-slate-500 mb-1">Contenuti Totali</p>
        <p class="text-2xl font-bold text-slate-800" id="statTotali">0</p>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200">
        <p class="text-xs text-slate-500 mb-1">Da Leggere</p>
        <p class="text-2xl font-bold text-amber-600" id="statDaLeggere">0</p>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200">
        <p class="text-xs text-slate-500 mb-1">Clienti Attivi</p>
        <p class="text-2xl font-bold text-cyan-600" id="statClienti">0</p>
    </div>
    <div class="bg-white p-4 rounded-xl border border-slate-200">
        <p class="text-xs text-slate-500 mb-1">Ultimi 7 Giorni</p>
        <p class="text-2xl font-bold text-emerald-600" id="statRecenti">0</p>
    </div>
</div>

<!-- Filtri -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6 overflow-hidden">
    <div class="p-4 border-b border-slate-100">
        <div class="flex items-center gap-2 text-slate-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            <span class="font-medium">Filtri</span>
        </div>
    </div>
    <div class="p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <select id="filtroCliente" class="w-full sm:w-64 px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                <option value="">Tutti i clienti</option>
                <?php foreach ($clienti as $c): ?>
                <option value="<?php echo e($c['id']); ?>"><?php echo e($c['ragione_sociale']); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filtroStato" class="w-full sm:w-48 px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                <option value="">Tutti gli stati</option>
                <option value="da_leggere">Da leggere</option>
                <option value="letto">Letti</option>
                <option value="attivo">Attivi</option>
                <option value="archiviato">Archiviati</option>
            </select>
            <button onclick="loadContenuti()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium transition-colors">
                Aggiorna
            </button>
        </div>
    </div>
</div>

<!-- Lista Contenuti -->
<div id="contenutiContainer" class="space-y-4">
    <div class="text-center py-12">
        <div class="animate-spin w-8 h-8 border-2 border-cyan-500 border-t-transparent rounded-full mx-auto"></div>
        <p class="text-sm text-slate-500 mt-2">Caricamento contenuti...</p>
    </div>
</div>

<!-- Modal Genera Link -->
<div id="generaLinkModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModal('generaLinkModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-lg sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[85vh] overflow-hidden flex flex-col">
            <div class="p-4 sm:p-6 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg sm:text-xl font-bold text-slate-800">Genera Link per Cliente</h2>
                <button onclick="closeModal('generaLinkModal')" class="p-2 -mr-2 text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="generaLinkForm" class="p-4 sm:p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Cliente *</label>
                    <select name="cliente_id" id="linkClienteId" required class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                        <option value="">Seleziona cliente...</option>
                        <?php foreach ($clienti as $c): ?>
                        <option value="<?php echo e($c['id']); ?>"><?php echo e($c['ragione_sociale']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Note (opzionale)</label>
                    <textarea name="note" rows="3" class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none" placeholder="Aggiungi note per identificare questo link..."></textarea>
                </div>
                
                <div class="p-4 bg-amber-50 rounded-lg border border-amber-200">
                    <p class="text-sm text-amber-800">
                        <strong>Il link generato permetterà al cliente di:</strong>
                    </p>
                    <ul class="text-sm text-amber-700 mt-2 space-y-1">
                        <li>• Caricare fino a 10 immagini (JPG, PNG, WEBP)</li>
                        <li>• Aggiungere un testo descrittivo</li>
                        <li>• Inserire un titolo</li>
                    </ul>
                </div>
            </form>
            
            <div class="p-4 border-t border-slate-100 flex flex-row justify-end gap-2">
                <button type="button" onclick="closeModal('generaLinkModal')" class="px-4 py-2 text-slate-600 hover:text-slate-800 font-medium">
                    Annulla
                </button>
                <button type="button" onclick="generaLink()" class="px-6 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium">
                    Genera Link
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Link Generato -->
<div id="linkGeneratoModal" class="fixed inset-0 z-[70] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModal('linkGeneratoModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-lg sm:rounded-2xl rounded-t-2xl shadow-2xl">
            <div class="p-4 sm:p-6 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-lg sm:text-xl font-bold text-slate-800">Link Generato!</h2>
                <button onclick="closeModal('linkGeneratoModal')" class="p-2 -mr-2 text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="p-4 sm:p-6 space-y-4">
                <p class="text-slate-600">Invia questo link al cliente per permettergli di caricare contenuti:</p>
                
                <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <input type="text" id="linkGeneratoUrl" readonly class="w-full bg-transparent text-sm text-slate-700 outline-none" value="">
                </div>
                
                <div class="flex gap-2">
                    <button onclick="copiaLink()" class="flex-1 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Copia Link
                    </button>
                    <button onclick="condividiLink()" class="flex-1 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        Condividi
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dettaglio Contenuto -->
<div id="dettaglioContenutoModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/60" onclick="closeModal('dettaglioContenutoModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-2xl sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-4 sm:p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-slate-800" id="dettaglioClienteNome">Contenuto</h2>
                    <p class="text-sm text-slate-500" id="dettaglioData">-</p>
                </div>
                <button onclick="closeModal('dettaglioContenutoModal')" class="p-2 -mr-2 text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div id="dettaglioContent" class="flex-1 overflow-y-auto p-4 sm:p-6">
                <!-- Popolato via JS -->
            </div>
            
            <div class="p-4 border-t border-slate-100 flex flex-row justify-end gap-2">
                <button type="button" onclick="archiviaContenuto()" id="btnArchivia" class="px-4 py-2 text-amber-600 hover:text-amber-700 font-medium">
                    Archivia
                </button>
                <button type="button" onclick="eliminaContenuto()" class="px-4 py-2 text-red-600 hover:text-red-700 font-medium">
                    Elimina
                </button>
                <button type="button" onclick="closeModal('dettaglioContenutoModal')" class="px-6 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium">
                    Chiudi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let contenutiData = [];
let currentContenutoId = null;

document.addEventListener('DOMContentLoaded', function() {
    loadContenuti();
    loadStats();
});

async function loadContenuti() {
    const clienteId = document.getElementById('filtroCliente').value;
    const stato = document.getElementById('filtroStato').value;
    
    let url = 'api/blog_clienti.php?action=list';
    if (clienteId) url += '&cliente_id=' + encodeURIComponent(clienteId);
    if (stato) url += '&stato=' + encodeURIComponent(stato);
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            contenutiData = data.data;
            renderContenuti(data.data);
        } else {
            showToast(data.message || 'Errore caricamento', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

async function loadStats() {
    try {
        const response = await fetch('api/blog_clienti.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            const s = data.data;
            document.getElementById('statTotali').textContent = s.totali || 0;
            document.getElementById('statDaLeggere').textContent = s.da_leggere || 0;
            document.getElementById('statClienti').textContent = s.clienti_attivi || 0;
            document.getElementById('statRecenti').textContent = s.recenti || 0;
        }
    } catch (error) {
        console.error('Errore stats:', error);
    }
}

function renderContenuti(contenuti) {
    const container = document.getElementById('contenutiContainer');
    
    if (contenuti.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 bg-white rounded-xl border border-slate-200">
                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-slate-600">Nessun contenuto</h3>
                <p class="text-slate-400 mt-1">Genera un link e invialo al cliente per ricevere contenuti</p>
            </div>
        `;
        return;
    }
    
    // Raggruppa per cliente
    const perCliente = {};
    contenuti.forEach(c => {
        const nome = c.cliente_nome || 'Cliente sconosciuto';
        if (!perCliente[nome]) perCliente[nome] = [];
        perCliente[nome].push(c);
    });
    
    let html = '';
    for (const [clienteNome, items] of Object.entries(perCliente)) {
        html += `
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="font-semibold text-slate-800">${clienteNome}</h3>
                    <p class="text-xs text-slate-500">${items.length} contenuto/i</p>
                </div>
                <div class="divide-y divide-slate-100">
                    ${items.map(c => {
                        const immagini = JSON.parse(c.immagini || '[]');
                        const hasImmagini = immagini.length > 0;
                        const isUnread = !c.letto;
                        
                        return `
                            <div class="p-4 hover:bg-slate-50 cursor-pointer transition-colors ${isUnread ? 'bg-amber-50/50' : ''}" 
                                 onclick="showDettaglio('${c.id}')">
                                <div class="flex items-start gap-4">
                                    ${hasImmagini ? `
                                        <div class="w-16 h-16 rounded-lg bg-slate-200 flex-shrink-0 overflow-hidden">
                                            <img src="assets/uploads/clienti_contenuti/${immagini[0]}" 
                                                 alt="" class="w-full h-full object-cover">
                                        </div>
                                    ` : `
                                        <div class="w-16 h-16 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                    `}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-medium text-slate-800 truncate">${c.titolo || 'Senza titolo'}</h4>
                                            ${isUnread ? `<span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs rounded-full">Nuovo</span>` : ''}
                                        </div>
                                        <p class="text-sm text-slate-500 mt-1 line-clamp-2">${c.testo || 'Nessun testo'}</p>
                                        <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                                            <span>${new Date(c.created_at).toLocaleDateString('it-IT')}</span>
                                            ${hasImmagini ? `<span>${immagini.length} immagine/i</span>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }
    
    container.innerHTML = html;
}

function openGeneraLinkModal() {
    document.getElementById('generaLinkForm').reset();
    openModal('generaLinkModal');
}

async function generaLink() {
    const form = document.getElementById('generaLinkForm');
    const formData = new FormData(form);
    
    const clienteId = document.getElementById('linkClienteId').value;
    if (!clienteId) {
        showToast('Seleziona un cliente', 'error');
        return;
    }
    
    formData.append('action', 'genera_link');
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
    
    try {
        const response = await fetch('api/blog_clienti.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeModal('generaLinkModal');
            document.getElementById('linkGeneratoUrl').value = data.data.url;
            openModal('linkGeneratoModal');
            showToast('Link generato con successo', 'success');
        } else {
            showToast(data.message || 'Errore generazione link', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

function copiaLink() {
    const url = document.getElementById('linkGeneratoUrl').value;
    navigator.clipboard.writeText(url).then(() => {
        showToast('Link copiato!', 'success');
    });
}

function condividiLink() {
    const url = document.getElementById('linkGeneratoUrl').value;
    if (navigator.share) {
        navigator.share({
            title: 'Carica contenuti',
            text: 'Clicca sul link per caricare immagini e testo',
            url: url
        });
    } else {
        copiaLink();
    }
}

async function showDettaglio(id) {
    currentContenutoId = id;
    const contenuto = contenutiData.find(c => c.id === id);
    if (!contenuto) return;
    
    document.getElementById('dettaglioClienteNome').textContent = contenuto.cliente_nome || 'Cliente';
    document.getElementById('dettaglioData').textContent = new Date(contenuto.created_at).toLocaleString('it-IT');
    
    const immagini = JSON.parse(contenuto.immagini || '[]');
    
    let html = '';
    
    if (contenuto.titolo) {
        html += `<h3 class="text-xl font-bold text-slate-800 mb-4">${contenuto.titolo}</h3>`;
    }
    
    if (contenuto.testo) {
        html += `<div class="prose max-w-none mb-6"><p class="text-slate-700 whitespace-pre-wrap">${contenuto.testo}</p></div>`;
    }
    
    if (immagini.length > 0) {
        html += `<div class="grid grid-cols-2 sm:grid-cols-3 gap-3">`;
        immagini.forEach(img => {
            html += `
                <div class="aspect-square rounded-lg overflow-hidden bg-slate-100 cursor-pointer hover:opacity-90" onclick="openImageModal('assets/uploads/clienti_contenuti/${img}')">
                    <img src="assets/uploads/clienti_contenuti/${img}" alt="" class="w-full h-full object-cover">
                </div>
            `;
        });
        html += `</div>`;
    }
    
    document.getElementById('dettaglioContent').innerHTML = html;
    
    // Mostra/nascondi bottone archivia
    const btnArchivia = document.getElementById('btnArchivia');
    if (contenuto.stato === 'archiviato') {
        btnArchivia.textContent = 'Ripristina';
        btnArchivia.onclick = ripristinaContenuto;
    } else {
        btnArchivia.textContent = 'Archivia';
        btnArchivia.onclick = archiviaContenuto;
    }
    
    openModal('dettaglioContenutoModal');
    
    // Segna come letto
    if (!contenuto.letto) {
        segnaLetto(id);
    }
}

async function segnaLetto(id) {
    try {
        await fetch('api/blog_clienti.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=segna_letto&id=${id}&csrf_token=${document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''}`
        });
        
        // Aggiorna locale
        const c = contenutiData.find(x => x.id === id);
        if (c) c.letto = 1;
        renderContenuti(contenutiData);
        loadStats();
    } catch (e) {}
}

async function archiviaContenuto() {
    if (!currentContenutoId) return;
    
    try {
        const response = await fetch('api/blog_clienti.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=archivia&id=${currentContenutoId}&csrf_token=${document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Contenuto archiviato', 'success');
            closeModal('dettaglioContenutoModal');
            loadContenuti();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function ripristinaContenuto() {
    if (!currentContenutoId) return;
    
    try {
        const response = await fetch('api/blog_clienti.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=ripristina&id=${currentContenutoId}&csrf_token=${document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Contenuto ripristinato', 'success');
            closeModal('dettaglioContenutoModal');
            loadContenuti();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function eliminaContenuto() {
    if (!currentContenutoId) return;
    if (!confirm('Sei sicuro di voler eliminare questo contenuto?')) return;
    
    try {
        const response = await fetch('api/blog_clienti.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=elimina&id=${currentContenutoId}&csrf_token=${document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Contenuto eliminato', 'success');
            closeModal('dettaglioContenutoModal');
            loadContenuti();
            loadStats();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
