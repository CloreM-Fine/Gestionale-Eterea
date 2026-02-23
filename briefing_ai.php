<?php
/**
 * Eterea Gestionale - Briefing
 * Form per compilazione e salvataggio briefing progetti
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Briefing';

// Carica clienti per selezione
$clienti = [];
try {
    $stmt = $pdo->query("SELECT id, ragione_sociale FROM clienti ORDER BY ragione_sociale ASC");
    $clienti = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Errore caricamento clienti: " . $e->getMessage());
}

// Carica tutti i progetti per associazione
$progetti = [];
try {
    $stmt = $pdo->query("
        SELECT p.id, p.titolo, p.cliente_id, c.ragione_sociale AS cliente_nome 
        FROM progetti p 
        LEFT JOIN clienti c ON p.cliente_id = c.id 
        ORDER BY c.ragione_sociale ASC, p.titolo ASC
    ");
    $progetti = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Errore caricamento progetti: " . $e->getMessage());
}

include __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Briefing</h1>
            <p class="text-sm text-slate-500 mt-1">Compila e salva il briefing del progetto</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="esportaPDF()" 
                    class="bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 px-4 py-2.5 rounded-lg font-medium flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Esporta PDF
            </button>
            <button onclick="apriModalSalva()" 
                    class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2.5 rounded-lg font-medium flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                Salva nel Progetto
            </button>
        </div>
    </div>
</div>

<!-- Form Briefing -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200">
    <!-- Header Form -->
    <div class="p-6 border-b border-slate-100">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-slate-700 mb-2">Nome Briefing</label>
                <input type="text" id="nomeBriefing" placeholder="Es. Sito Web - Nome Cliente" 
                       class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none">
            </div>
        </div>
    </div>

    <!-- Sezioni Briefing -->
    <div class="p-6 space-y-6">
        
        <!-- Cliente -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">1. Cliente</label>
            <textarea id="field-cliente" rows="2" placeholder="Inserisci nome e dati del cliente..."
                      class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none resize-none"></textarea>
        </div>

        <!-- Obiettivo -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">2. Obiettivo del Sito</label>
            <textarea id="field-obiettivo" rows="3" placeholder="Descrivi l'obiettivo principale del sito..."
                      class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none resize-none"></textarea>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Target -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">3. Target</label>
                <textarea id="field-target" rows="4" placeholder="• Tipologia di clientela&#10;• Fascia d'età&#10;• Requisiti specifici"
                          class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none resize-none"></textarea>
            </div>

            <!-- Tono di voce -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">4. Tono di Voce</label>
                <textarea id="field-tono" rows="4" placeholder="Descrivi il tono di comunicazione..."
                          class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none resize-none"></textarea>
            </div>
        </div>

        <!-- Struttura del sito -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">5. Struttura del Sito</label>
            <textarea id="field-struttura" rows="4" placeholder="• Home - Presentazione sintetica&#10;• Chi Siamo - Storia e valori&#10;• Servizi - Dettaglio offerta&#10;• Contatti - Form e recapiti"
                      class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none resize-none"></textarea>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Funzionalità -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">6. Funzionalità Richieste</label>
                <textarea id="field-funzionalita" rows="4" placeholder="• Responsive design&#10;• Form di contatto&#10;• Integrazione social"
                          class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none resize-none"></textarea>
            </div>

            <!-- Elementi distintivi -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">7. Elementi Distintivi</label>
                <textarea id="field-distintivi" rows="4" placeholder="Punti di forza e caratteristiche uniche..."
                          class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none resize-none"></textarea>
            </div>
        </div>

        <!-- Design -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">8. Design e Stile</label>
            <textarea id="field-design" rows="3" placeholder="Indicazioni stilistiche, colori, mood..."
                      class="w-full px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none resize-none"></textarea>
        </div>

        <!-- Contenuti Sezioni -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-medium text-slate-700">9. Contenuti Sezioni</label>
                <button onclick="aggiungiSezione()" class="text-sm text-cyan-600 hover:text-cyan-700 font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Aggiungi sezione
                </button>
            </div>
            <div id="contenutiContainer" class="space-y-3">
                <div class="contenuto-sezione bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <input type="text" placeholder="Nome sezione (es. Home)" 
                           class="w-full mb-2 px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm font-medium">
                    <textarea placeholder="Descrivi il contenuto di questa sezione..." rows="2"
                              class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none text-sm"></textarea>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL SALVA -->
<div id="modalSalva" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="chiudiModalSalva()"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white sm:rounded-2xl rounded-t-2xl shadow-2xl w-full max-w-md max-h-[85vh] sm:max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-xl font-bold text-slate-800">Salva Briefing</h2>
                <button onclick="chiudiModalSalva()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="p-6 space-y-4">
                <!-- Filtro Cliente -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Cerca Cliente</label>
                    <div class="relative">
                        <input type="text" id="filtroCliente" onkeyup="filtraClienti()" 
                               class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none" 
                               placeholder="Digita per filtrare...">
                        <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>

                <!-- Cliente -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Cliente *</label>
                    <select id="selectCliente" onchange="filtraProgetti()" 
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                        <option value="">-- Seleziona cliente --</option>
                        <?php foreach ($clienti as $c): ?>
                        <option value="<?php echo $c['id']; ?>" data-nome="<?php echo e(strtolower($c['ragione_sociale'])); ?>"><?php echo e($c['ragione_sociale']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Progetto -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Progetto *</label>
                    <select id="selectProgetto" 
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none" disabled>
                        <option value="">-- Prima seleziona il cliente --</option>
                    </select>
                </div>

                <!-- Nome File -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nome File</label>
                    <input type="text" id="nomeFileSalva" 
                           class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none" 
                           placeholder="Briefing - Nome Cliente">
                </div>
            </div>
            
            <div class="p-4 sm:p-6 border-t border-slate-100 flex flex-row justify-end gap-2 sm:gap-3">
                <button onclick="chiudiModalSalva()" 
                        class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">
                    Annulla
                </button>
                <button onclick="salvaBriefing()" 
                        class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium min-h-[44px] transition-colors text-sm sm:text-base">
                    <span class="hidden sm:inline">Salva nel Progetto</span>
                    <span class="sm:hidden">Salva</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ==================== VARIABILI GLOBALI ====================
const progettiData = <?php echo json_encode($progetti); ?>;

// ==================== SEZIONI CONTENUTI ====================
function aggiungiSezione() {
    const container = document.getElementById('contenutiContainer');
    const div = document.createElement('div');
    div.className = 'contenuto-sezione bg-slate-50 rounded-lg p-4 border border-slate-200';
    div.innerHTML = `
        <div class="flex items-center gap-2 mb-2">
            <input type="text" placeholder="Nome sezione" 
                   class="flex-1 px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm font-medium">
            <button onclick="this.closest('.contenuto-sezione').remove()" 
                    class="p-2 text-slate-400 hover:text-red-500 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
        <textarea placeholder="Descrivi il contenuto di questa sezione..." rows="2"
                  class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none text-sm"></textarea>
    `;
    container.appendChild(div);
}

// ==================== EXPORT PDF ====================
function esportaPDF() {
    const nomeBriefing = document.getElementById('nomeBriefing').value || 'Briefing';
    const nomeCliente = document.getElementById('field-cliente').value.trim() || 'Cliente';
    
    const html = generaHTMLStampa(nomeCliente);
    
    const win = window.open('', '_blank');
    win.document.write(html);
    win.document.close();
}

function generaHTMLStampa(nomeCliente) {
    const nomeBriefing = document.getElementById('nomeBriefing').value || 'Briefing';
    const cliente = document.getElementById('field-cliente').value || '';
    const obiettivo = document.getElementById('field-obiettivo').value || '';
    const target = document.getElementById('field-target').value || '';
    const tono = document.getElementById('field-tono').value || '';
    const struttura = document.getElementById('field-struttura').value || '';
    const funzionalita = document.getElementById('field-funzionalita').value || '';
    const distintivi = document.getElementById('field-distintivi').value || '';
    const design = document.getElementById('field-design').value || '';
    
    // Raccogli contenuti sezioni
    let contenutiSezioniHTML = '';
    const sezioni = document.querySelectorAll('.contenuto-sezione');
    sezioni.forEach(sez => {
        const titolo = sez.querySelector('input')?.value || '';
        const contenuto = sez.querySelector('textarea')?.value || '';
        if (titolo || contenuto) {
            contenutiSezioniHTML += `
                <div style="margin-bottom: 15px;">
                    <h4 style="font-size: 11pt; color: #334155; margin: 0 0 5px 0; font-weight: 600;">${escapeHtml(titolo)}</h4>
                    <div style="color: #475569;">${escapeHtml(contenuto).replace(/\n/g, '<br>')}</div>
                </div>
            `;
        }
    });
    
    const sezioneContenuti = contenutiSezioniHTML ? `
        <div style="margin-bottom: 20px; page-break-inside: avoid;">
            <h2 style="font-size: 13pt; color: #0891B2; border-bottom: 2px solid #0891B2; padding-bottom: 6px; margin-bottom: 12px; font-weight: 600;">9. Contenuti Sezioni</h2>
            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border-left: 3px solid #0891B2;">
                ${contenutiSezioniHTML}
            </div>
        </div>
    ` : '';
    
    return `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Briefing - ${escapeHtml(nomeCliente)}</title>
    <style>
        @page { size: A4; margin: 15mm; }
        body { font-family: system-ui, -apple-system, sans-serif; font-size: 10.5pt; line-height: 1.6; color: #1e293b; max-width: 180mm; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #0891B2; padding-bottom: 20px; }
        .header h1 { color: #0891B2; font-size: 20pt; margin: 0 0 10px 0; font-weight: 700; }
        .header .cliente { font-size: 13pt; color: #475569; font-weight: 500; }
        .header .data { font-size: 9pt; color: #94a3b8; margin-top: 5px; }
        .section { margin-bottom: 20px; page-break-inside: avoid; }
        .section h2 { font-size: 13pt; color: #0891B2; border-bottom: 2px solid #0891B2; padding-bottom: 6px; margin-bottom: 12px; font-weight: 600; }
        .section-content { background: #f8fafc; padding: 15px; border-radius: 8px; border-left: 3px solid #0891B2; color: #475569; white-space: pre-wrap; }
        ul, ol { margin: 8px 0; padding-left: 20px; }
        li { margin-bottom: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BRIEFING PROGETTO</h1>
        <p class="cliente">${escapeHtml(nomeCliente)}</p>
        <p class="data">Generato il ${new Date().toLocaleDateString('it-IT')} alle ${new Date().toLocaleTimeString('it-IT', {hour: '2-digit', minute:'2-digit'})}</p>
    </div>
    
    <div class="section">
        <h2>1. Cliente</h2>
        <div class="section-content">${escapeHtml(cliente)}</div>
    </div>
    
    <div class="section">
        <h2>2. Obiettivo del Sito</h2>
        <div class="section-content">${escapeHtml(obiettivo)}</div>
    </div>
    
    <div class="section">
        <h2>3. Target</h2>
        <div class="section-content">${escapeHtml(target)}</div>
    </div>
    
    <div class="section">
        <h2>4. Tono di Voce</h2>
        <div class="section-content">${escapeHtml(tono)}</div>
    </div>
    
    <div class="section">
        <h2>5. Struttura del Sito</h2>
        <div class="section-content">${escapeHtml(struttura)}</div>
    </div>
    
    <div class="section">
        <h2>6. Funzionalità Richieste</h2>
        <div class="section-content">${escapeHtml(funzionalita)}</div>
    </div>
    
    <div class="section">
        <h2>7. Elementi Distintivi</h2>
        <div class="section-content">${escapeHtml(distintivi)}</div>
    </div>
    
    <div class="section">
        <h2>8. Design e Stile</h2>
        <div class="section-content">${escapeHtml(design)}</div>
    </div>
    
    ${sezioneContenuti}
    
    <script>window.onload = () => setTimeout(() => window.print(), 300);<\/script>
</body>
</html>`;
}

// ==================== SALVA NEL PROGETTO ====================
function apriModalSalva() {
    const nomeBriefing = document.getElementById('nomeBriefing').value;
    document.getElementById('nomeFileSalva').value = nomeBriefing || 'Briefing';
    document.getElementById('modalSalva').classList.remove('hidden');
}

function chiudiModalSalva() {
    document.getElementById('modalSalva').classList.add('hidden');
}

function filtraClienti() {
    const filtro = document.getElementById('filtroCliente').value.toLowerCase().trim();
    const selectCliente = document.getElementById('selectCliente');
    const options = selectCliente.querySelectorAll('option');
    
    options.forEach((option, index) => {
        if (index === 0) return;
        
        const nomeCliente = option.getAttribute('data-nome') || '';
        if (filtro === '' || nomeCliente.includes(filtro)) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
    
    document.getElementById('selectProgetto').innerHTML = '<option value="">-- Prima seleziona il cliente --</option>';
    document.getElementById('selectProgetto').disabled = true;
    selectCliente.value = '';
}

function filtraProgetti() {
    const clienteId = document.getElementById('selectCliente').value;
    const selectProgetto = document.getElementById('selectProgetto');
    
    selectProgetto.innerHTML = '<option value="">-- Seleziona progetto --</option>';
    
    if (!clienteId) {
        selectProgetto.disabled = true;
        return;
    }
    
    const progettiFiltrati = progettiData.filter(p => p.cliente_id === clienteId);
    
    progettiFiltrati.forEach(p => {
        const opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.titolo;
        selectProgetto.appendChild(opt);
    });
    
    selectProgetto.disabled = progettiFiltrati.length === 0;
}

async function salvaBriefing() {
    const progettoId = document.getElementById('selectProgetto').value;
    const nomeFile = document.getElementById('nomeFileSalva').value;
    
    if (!progettoId) {
        showToast('Seleziona un progetto', 'warning');
        return;
    }
    
    if (!nomeFile) {
        showToast('Inserisci un nome per il file', 'warning');
        return;
    }
    
    const nomeCliente = document.getElementById('field-cliente').value.trim() || 'Cliente';
    const htmlContent = generaHTMLStampa(nomeCliente);
    
    showToast('Generazione PDF in corso...', 'info');
    
    try {
        const response = await fetch('api/briefing_pdf.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                html: htmlContent,
                filename: nomeFile + '.pdf'
            })
        });
        
        if (response.status === 501) {
            const errData = await response.json().catch(() => ({}));
            if (errData.error === 'PDF_NON_CONFIGURATO') {
                showToast('PDF non disponibile, salvo come HTML...', 'warning');
                await salvaBriefingFallback(progettoId, nomeFile, htmlContent);
                return;
            }
        }
        
        if (!response.ok) {
            const errData = await response.json().catch(() => ({}));
            throw new Error(errData.message || 'Errore PDF: ' + response.status);
        }
        
        const blob = await response.blob();
        
        const formData = new FormData();
        formData.append('action', 'save_to_project');
        formData.append('progetto_id', progettoId);
        formData.append('filename', nomeFile + '.pdf');
        formData.append('documento', blob, nomeFile + '.pdf');
        
        const saveResponse = await fetch('api/briefing_ai.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await saveResponse.json();
        
        if (data.success) {
            showToast('Briefing salvato nel progetto!', 'success');
            chiudiModalSalva();
        } else {
            throw new Error(data.message || 'Errore salvataggio');
        }
    } catch (err) {
        console.error(err);
        showToast('Errore: ' + err.message, 'error');
    }
}

async function salvaBriefingFallback(progettoId, nomeFile, htmlContent) {
    try {
        const htmlBlob = new Blob([htmlContent], { type: 'text/html' });
        
        const formData = new FormData();
        formData.append('action', 'save_to_project');
        formData.append('progetto_id', progettoId);
        formData.append('filename', nomeFile + '.html');
        formData.append('documento', htmlBlob, nomeFile + '.html');
        
        const saveResponse = await fetch('api/briefing_ai.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await saveResponse.json();
        
        if (data.success) {
            showToast('Briefing salvato come HTML (PDF non disponibile)', 'success');
            chiudiModalSalva();
        } else {
            throw new Error(data.message || 'Errore salvataggio');
        }
    } catch (err) {
        console.error(err);
        showToast('Errore fallback: ' + err.message, 'error');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
