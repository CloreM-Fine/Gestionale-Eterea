<?php
/**
 * Eterea Gestionale
 * Listini Prezzi
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Listini';
include __DIR__ . '/includes/header.php';

// Colori disponibili
$listiniColors = [
    '#FFFFFF' => ['nome' => 'Bianco'],
    '#BAE6FD' => ['nome' => 'Ciano'],
    '#BFDBFE' => ['nome' => 'Blu'],
    '#BBF7D0' => ['nome' => 'Verde'],
    '#FDE68A' => ['nome' => 'Giallo'],
    '#FED7AA' => ['nome' => 'Arancione'],
    '#FECACA' => ['nome' => 'Rosso'],
    '#FBCFE8' => ['nome' => 'Rosa'],
    '#E9D5FF' => ['nome' => 'Viola'],
    '#C4B5FD' => ['nome' => 'Indaco'],
    '#99F6E4' => ['nome' => 'Turchese'],
    '#F5D0FE' => ['nome' => 'Fucsia'],
];
?>

<!-- Header -->
<div class="mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Listini Prezzi</h1>
            <p class="text-sm text-slate-500 mt-1">Gestisci i cataloghi servizi per i preventivi</p>
        </div>
        
        <button onclick="openListinoModal()" 
                class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2.5 rounded-lg font-medium flex items-center gap-2 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuovo Listino
        </button>
    </div>
</div>

<!-- Listini Grid -->
<div id="listiniContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <div class="col-span-full text-center py-12 text-slate-400">
        <svg class="w-12 h-12 mx-auto mb-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <p>Caricamento listini...</p>
    </div>
</div>

<!-- Modal Listino -->
<div id="listinoModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('listinoModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white">
                <h3 class="font-bold text-slate-800" id="listinoModalTitle">Nuovo Listino</h3>
                <button onclick="closeModal('listinoModal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="listinoForm" class="p-5 space-y-4">
                <input type="hidden" id="listinoIdInput" name="id">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Titolo *</label>
                    <input type="text" name="titolo" required
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                           placeholder="es. Listino Siti Web 2025">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Descrizione</label>
                    <textarea name="descrizione" rows="3"
                              class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none"
                              placeholder="Descrizione del listino..."></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Colore</label>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($listiniColors as $hex => $info): ?>
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="colore" value="<?php echo $hex; ?>" 
                                   class="peer sr-only" <?php echo $hex === '#FFFFFF' ? 'checked' : ''; ?>>
                            <div class="w-10 h-10 rounded-xl border-2 border-slate-200 peer-checked:border-cyan-500 peer-checked:ring-2 peer-checked:ring-cyan-200 transition-all shadow-sm"
                                 style="background-color: <?php echo $hex; ?>;"
                                 title="<?php echo $info['nome']; ?>">
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Immagine Copertina</label>
                    <div class="flex items-center gap-3">
                        <div id="listinoImagePreview" class="hidden w-20 h-20 rounded-xl overflow-hidden border border-slate-200 shadow-sm">
                            <img src="" alt="Preview" class="w-full h-full object-cover">
                        </div>
                        <label class="flex-1 cursor-pointer">
                            <input type="file" name="immagine" accept="image/*" class="hidden" onchange="previewListinoImage(this)">
                            <div class="px-4 py-3 border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors text-center text-sm text-slate-600 border-dashed">
                                <svg class="w-5 h-5 mx-auto mb-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span id="listinoImageLabel">Clicca per aggiungere immagine</span>
                            </div>
                        </label>
                        <button type="button" id="removeListinoImageBtn" onclick="removeListinoImage()" class="hidden text-red-500 hover:text-red-700 p-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Max 2MB (JPG, PNG, GIF, WEBP)</p>
                </div>
            </form>
            
            <div class="p-5 border-t border-slate-100 flex justify-end gap-3 sticky bottom-0 bg-white">
                <button type="button" onclick="closeModal('listinoModal')" class="px-4 py-2 text-slate-600 font-medium">
                    Annulla
                </button>
                <button type="button" onclick="saveListino()" class="px-6 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium">
                    Salva
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Servizi Listino -->
<div id="serviziModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('serviziModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800" id="serviziModalTitle">Servizi Listino</h3>
                    <p class="text-sm text-slate-500" id="serviziModalSubtitle"></p>
                </div>
                <button onclick="closeModal('serviziModal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="p-5 border-b border-slate-100 bg-slate-50">
                <form id="servizioForm" class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <input type="hidden" id="servizioIdInput" name="servizio_id">
                    <input type="hidden" id="servizioListinoId" name="listino_id">
                    
                    <div class="md:col-span-5">
                        <input type="text" name="nome" required placeholder="Nome servizio"
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm">
                    </div>
                    <div class="md:col-span-4">
                        <input type="text" name="descrizione" placeholder="Descrizione breve"
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <input type="number" name="prezzo" step="0.01" min="0" placeholder="Prezzo"
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm">
                    </div>
                    <div class="md:col-span-1">
                        <button type="button" onclick="saveServizio()" class="w-full h-full bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="flex-1 overflow-y-auto p-5">
                <div id="serviziList" class="space-y-2">
                    <!-- Servizi caricati qui -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let listiniData = [];
let currentListinoId = null;

// Carica listini all'avvio
document.addEventListener('DOMContentLoaded', () => {
    loadListini();
});

async function loadListini() {
    try {
        const response = await fetch('api/listini.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            listiniData = data.data;
            renderListini();
        } else {
            showToast(data.message || 'Errore caricamento', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

function renderListini() {
    const container = document.getElementById('listiniContainer');
    
    if (listiniData.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-12 text-slate-400">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <p class="text-lg mb-2">Nessun listino creato</p>
                <p class="text-sm">Crea il tuo primo listino prezzi</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = listiniData.map(l => {
        const coloreSfondo = l.colore || '#FFFFFF';
        const isDefaultColor = coloreSfondo === '#FFFFFF';
        
        return `
            <div class="rounded-2xl shadow-sm border border-slate-200 overflow-hidden bg-white hover:shadow-lg transition-shadow cursor-pointer group"
                 style="background-color: ${coloreSfondo}; ${!isDefaultColor ? 'border-color: ' + coloreSfondo.replace('FF', 'DD') : ''}"
                 onclick="openServiziModal('${l.id}')">
                
                ${l.immagine ? `
                    <div class="h-32 overflow-hidden">
                        <img src="assets/uploads/${l.immagine}" alt="${l.titolo}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                    </div>
                ` : `
                    <div class="h-24 flex items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200">
                        <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </div>
                `}
                
                <div class="p-5">
                    <h3 class="font-bold text-slate-800 mb-1 line-clamp-1" style="${!isDefaultColor ? 'color: #1e293b;' : ''}">${l.titolo}</h3>
                    <p class="text-sm text-slate-500 line-clamp-2 mb-3" style="${!isDefaultColor ? 'color: #475569;' : ''}">${l.descrizione || 'Nessuna descrizione'}</p>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full ${l.num_servizi > 0 ? 'bg-cyan-100 text-cyan-700' : 'bg-slate-100 text-slate-500'}">
                            ${l.num_servizi} servizi
                        </span>
                        
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity" onclick="event.stopPropagation()">
                            <button onclick="editListino('${l.id}')" class="text-slate-400 hover:text-cyan-600 p-1.5 rounded-lg hover:bg-white/50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button onclick="deleteListino('${l.id}')" class="text-slate-400 hover:text-red-500 p-1.5 rounded-lg hover:bg-white/50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Preview immagine listino
function previewListinoImage(input) {
    const preview = document.getElementById('listinoImagePreview');
    const img = preview.querySelector('img');
    const label = document.getElementById('listinoImageLabel');
    const removeBtn = document.getElementById('removeListinoImageBtn');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        if (file.size > 2 * 1024 * 1024) {
            showToast('Immagine troppo grande (max 2MB)', 'error');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            preview.classList.remove('hidden');
            label.textContent = file.name;
            removeBtn.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
}

function removeListinoImage() {
    const input = document.querySelector('[name="immagine"]');
    const preview = document.getElementById('listinoImagePreview');
    const img = preview.querySelector('img');
    const label = document.getElementById('listinoImageLabel');
    const removeBtn = document.getElementById('removeListinoImageBtn');
    
    input.value = '';
    img.src = '';
    preview.classList.add('hidden');
    label.textContent = 'Clicca per aggiungere immagine';
    removeBtn.classList.add('hidden');
}

// Modal Listino
function openListinoModal() {
    document.getElementById('listinoForm').reset();
    document.getElementById('listinoIdInput').value = '';
    document.getElementById('listinoModalTitle').textContent = 'Nuovo Listino';
    removeListinoImage();
    
    // Seleziona colore default
    const defaultColore = document.querySelector('#listinoForm [name="colore"][value="#FFFFFF"]');
    if (defaultColore) defaultColore.checked = true;
    
    openModal('listinoModal');
}

async function editListino(id) {
    const listino = listiniData.find(l => l.id === id);
    if (!listino) return;
    
    document.getElementById('listinoIdInput').value = listino.id;
    document.querySelector('#listinoForm [name="titolo"]').value = listino.titolo;
    document.querySelector('#listinoForm [name="descrizione"]').value = listino.descrizione || '';
    
    // Seleziona colore
    const coloreInput = document.querySelector(`#listinoForm [name="colore"][value="${listino.colore || '#FFFFFF'}"]`);
    if (coloreInput) coloreInput.checked = true;
    
    // Mostra immagine se presente
    if (listino.immagine) {
        const preview = document.getElementById('listinoImagePreview');
        const img = preview.querySelector('img');
        const label = document.getElementById('listinoImageLabel');
        const removeBtn = document.getElementById('removeListinoImageBtn');
        
        img.src = 'assets/uploads/' + listino.immagine;
        preview.classList.remove('hidden');
        label.textContent = 'Immagine caricata';
        removeBtn.classList.remove('hidden');
    }
    
    document.getElementById('listinoModalTitle').textContent = 'Modifica Listino';
    openModal('listinoModal');
}

async function saveListino() {
    const form = document.getElementById('listinoForm');
    const formData = new FormData(form);
    const id = document.getElementById('listinoIdInput').value;
    
    const action = id ? 'update' : 'create';
    if (id) formData.append('id', id);
    
    try {
        const response = await fetch(`api/listini.php?action=${action}`, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            showToast(id ? 'Listino aggiornato' : 'Listino creato', 'success');
            closeModal('listinoModal');
            loadListini();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function deleteListino(id) {
    confirmAction('Eliminare questo listino?', async () => {
        try {
            const response = await fetch('api/listini.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete&id=${encodeURIComponent(id)}`
            });
            
            const data = await response.json();
            if (data.success) {
                showToast('Listino eliminato', 'success');
                loadListini();
            } else {
                showToast(data.message || 'Errore', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione', 'error');
        }
    });
}

// Modal Servizi
let serviziData = [];

async function openServiziModal(listinoId) {
    currentListinoId = listinoId;
    const listino = listiniData.find(l => l.id === listinoId);
    
    document.getElementById('serviziModalTitle').textContent = listino.titolo;
    document.getElementById('serviziModalSubtitle').textContent = listino.descrizione || '';
    document.getElementById('servizioListinoId').value = listinoId;
    
    await loadServizi(listinoId);
    openModal('serviziModal');
}

async function loadServizi(listinoId) {
    try {
        const response = await fetch(`api/listini.php?action=servizi&listino_id=${listinoId}`);
        const data = await response.json();
        
        if (data.success) {
            serviziData = data.data;
            renderServizi();
        }
    } catch (error) {
        showToast('Errore caricamento servizi', 'error');
    }
}

function renderServizi() {
    const container = document.getElementById('serviziList');
    
    if (serviziData.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-slate-400">
                <p>Nessun servizio in questo listino</p>
                <p class="text-sm mt-1">Aggiungi il primo servizio qui sopra</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = serviziData.map((s, index) => `
        <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-slate-200 hover:shadow-sm transition-shadow">
            <div class="flex-1 min-w-0">
                <div class="font-medium text-slate-800">${s.nome}</div>
                ${s.descrizione ? `<div class="text-sm text-slate-500 truncate">${s.descrizione}</div>` : ''}
            </div>
            <div class="text-right">
                <div class="font-semibold text-slate-800">€${parseFloat(s.prezzo).toFixed(2)}</div>
                ${s.durata_minuti ? `<div class="text-xs text-slate-500">${s.durata_minuti} min</div>` : ''}
            </div>
            <div class="flex items-center gap-1">
                <button onclick="editServizio(${s.id})" class="text-slate-400 hover:text-cyan-600 p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button onclick="deleteServizio(${s.id})" class="text-slate-400 hover:text-red-500 p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
    `).join('');
}

async function saveServizio() {
    const form = document.getElementById('servizioForm');
    const formData = new FormData(form);
    const servizioId = document.getElementById('servizioIdInput').value;
    
    const action = servizioId ? 'update_servizio' : 'add_servizio';
    
    try {
        const response = await fetch(`api/listini.php?action=${action}`, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            form.reset();
            document.getElementById('servizioIdInput').value = '';
            loadServizi(currentListinoId);
            loadListini(); // Aggiorna conteggio
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

function editServizio(id) {
    const servizio = serviziData.find(s => s.id == id);
    if (!servizio) return;
    
    document.getElementById('servizioIdInput').value = servizio.id;
    document.querySelector('#servizioForm [name="nome"]').value = servizio.nome;
    document.querySelector('#servizioForm [name="descrizione"]').value = servizio.descrizione || '';
    document.querySelector('#servizioForm [name="prezzo"]').value = servizio.prezzo;
}

async function deleteServizio(id) {
    confirmAction('Eliminare questo servizio?', async () => {
        try {
            const response = await fetch('api/listini.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_servizio&servizio_id=${id}`
            });
            
            const data = await response.json();
            if (data.success) {
                loadServizi(currentListinoId);
                loadListini(); // Aggiorna conteggio
            } else {
                showToast(data.message || 'Errore', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione', 'error');
        }
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
