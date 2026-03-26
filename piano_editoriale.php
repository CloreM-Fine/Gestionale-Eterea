<?php
/**
 * Eterea Gestionale - Piano Editoriale
 * Design pulito e professionale
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Piano Editoriale';

// Recupera dati
$progettiSocial = $pdo->query("
    SELECT p.*, c.ragione_sociale as cliente_nome 
    FROM progetti p
    LEFT JOIN clienti c ON p.cliente_id = c.id
    WHERE p.gestione_social = 1
    ORDER BY p.titolo ASC
")->fetchAll();

// Stats
$mese = date('Y-m');
$stats = $pdo->prepare("
    SELECT stato, COUNT(*) as count 
    FROM piano_editoriale 
    WHERE DATE_FORMAT(data_prevista, '%Y-%m') = ?
    GROUP BY stato
");
$stats->execute([$mese]);
$statsStato = $stats->fetchAll(PDO::FETCH_KEY_PAIR);

// Configurazioni
$piattaforme = [
    'instagram' => ['nome' => 'Instagram', 'colore' => '#E4405F'],
    'facebook' => ['nome' => 'Facebook', 'colore' => '#1877F2'],
    'tiktok' => ['nome' => 'TikTok', 'colore' => '#000000'],
    'linkedin' => ['nome' => 'LinkedIn', 'colore' => '#0A66C2'],
    'twitter' => ['nome' => 'X', 'colore' => '#000000'],
    'youtube' => ['nome' => 'YouTube', 'colore' => '#FF0000'],
    'pinterest' => ['nome' => 'Pinterest', 'colore' => '#BD081C'],
];

$stati = [
    'bozza' => ['label' => 'Bozza', 'colore' => 'bg-slate-100 text-slate-700'],
    'in_revisione' => ['label' => 'In revisione', 'colore' => 'bg-yellow-100 text-yellow-700'],
    'approvato' => ['label' => 'Approvato', 'colore' => 'bg-blue-100 text-blue-700'],
    'programmato' => ['label' => 'Programmato', 'colore' => 'bg-purple-100 text-purple-700'],
    'pubblicato' => ['label' => 'Pubblicato', 'colore' => 'bg-emerald-100 text-emerald-700'],
];

require_once __DIR__ . '/includes/header.php';
?>

<main class="flex-1 p-4 lg:p-8 pb-24 lg:pb-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Piano Editoriale</h1>
            <p class="text-sm text-slate-500 mt-1">Gestisci i contenuti social dei tuoi progetti</p>
        </div>
        <button onclick="openPostModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-800 text-white rounded-lg hover:bg-slate-700 transition-colors text-sm font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuovo Post
        </button>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-2xl font-bold text-slate-800"><?php echo array_sum($statsStato); ?></div>
            <div class="text-xs text-slate-500">Post questo mese</div>
        </div>
        <?php foreach ($stati as $key => $s): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <div class="text-2xl font-bold text-slate-800"><?php echo $statsStato[$key] ?? 0; ?></div>
            <div class="flex items-center gap-2 mt-1">
                <span class="w-2 h-2 rounded-full <?php echo str_replace('bg-', 'bg-', explode(' ', $s['colore'])[0]); ?>"></span>
                <span class="text-xs text-slate-500"><?php echo $s['label']; ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtri -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Progetto</label>
                <select id="filtroProgetto" onchange="loadPosts()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none">
                    <option value="">Tutti i progetti</option>
                    <?php foreach ($progettiSocial as $p): ?>
                    <option value="<?php echo e($p['id']); ?>"><?php echo e($p['titolo']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Stato</label>
                <select id="filtroStato" onchange="loadPosts()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none">
                    <option value="">Tutti gli stati</option>
                    <?php foreach ($stati as $key => $s): ?>
                    <option value="<?php echo e($key); ?>"><?php echo e($s['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1.5">Mese</label>
                <input type="month" id="filtroMese" onchange="loadPosts()" value="<?php echo date('Y-m'); ?>" 
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none">
            </div>
            <div class="flex items-end">
                <button onclick="loadPosts()" class="w-full px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors text-sm font-medium">
                    Aggiorna
                </button>
            </div>
        </div>
    </div>

    <!-- Vista Calendario -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <!-- Header Giorni -->
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">
            <?php foreach (['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'] as $g): ?>
            <div class="py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider"><?php echo $g; ?></div>
            <?php endforeach; ?>
        </div>
        
        <!-- Griglia -->
        <div id="calendarGrid" class="grid grid-cols-7">
            <!-- JS popolerà -->
        </div>
    </div>

    <!-- Lista Post (alternativa al calendario) -->
    <div id="postsList" class="mt-6 space-y-3 hidden">
        <!-- JS popolerà -->
    </div>
</main>

<!-- Modal Post - Design Pulito -->
<div id="postModal" class="fixed inset-0 z-50 hidden" onclick="if(event.target === this) closePostModal()">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <h3 id="modalTitle" class="text-lg font-semibold text-slate-800">Nuovo Post</h3>
                <button onclick="closePostModal()" class="p-2 hover:bg-slate-200 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Body -->
            <form id="postForm" class="flex-1 overflow-y-auto p-6">
                <input type="hidden" name="id" id="postId">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <!-- Progetto e Piattaforma -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Progetto *</label>
                        <select name="progetto_id" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none">
                            <option value="">Seleziona progetto...</option>
                            <?php foreach ($progettiSocial as $p): ?>
                            <option value="<?php echo e($p['id']); ?>"><?php echo e($p['titolo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Piattaforma *</label>
                        <div class="flex gap-2 flex-wrap">
                            <?php foreach ($piattaforme as $key => $p): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="piattaforma" value="<?php echo e($key); ?>" class="peer sr-only" <?php echo $key === 'instagram' ? 'checked' : ''; ?>>
                                <div class="px-3 py-2 rounded-lg border-2 border-slate-200 peer-checked:border-slate-800 peer-checked:bg-slate-800 peer-checked:text-white transition-all text-sm font-medium">
                                    <?php echo e($p['nome']); ?>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Titolo -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Titolo *</label>
                    <input type="text" name="titolo" required 
                           class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none"
                           placeholder="Es: Post di presentazione nuovo servizio">
                </div>
                
                <!-- Data, Ora e Stato -->
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Data *</label>
                        <input type="date" name="data_prevista" required 
                               class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Ora</label>
                        <input type="time" name="ora_prevista"
                               class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Stato *</label>
                        <select name="stato" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none">
                            <?php foreach ($stati as $key => $s): ?>
                            <option value="<?php echo e($key); ?>"><?php echo e($s['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Sponsorizzato -->
                <div class="mb-4 p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_sponsored" id="isSponsored" value="1" 
                               class="w-4 h-4 text-slate-800 rounded border-slate-300 focus:ring-slate-500"
                               onchange="document.getElementById('budgetWrapper').classList.toggle('hidden', !this.checked)">
                        <label for="isSponsored" class="text-sm font-medium text-slate-700 cursor-pointer flex-1">
                            Post sponsorizzato (paid)
                        </label>
                        <div id="budgetWrapper" class="hidden">
                            <input type="number" name="budget_sponsorizzato" step="0.01" min="0" placeholder="Budget €"
                                   class="w-28 px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-slate-500 outline-none">
                        </div>
                    </div>
                </div>
                
                <!-- Contenuto -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Contenuto</label>
                    <textarea name="descrizione" rows="4" 
                              class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none resize-none"
                              placeholder="Scrivi qui il testo del post..."></textarea>
                    <div class="flex justify-between mt-1.5">
                        <span class="text-xs text-slate-400">0/2200 caratteri</span>
                        <div class="flex gap-1">
                            <?php foreach (['😊', '❤️', '🔥', '👍', '✨'] as $e): ?>
                            <button type="button" onclick="addEmoji('<?php echo $e; ?>')" class="p-1 hover:bg-slate-100 rounded text-lg"><?php echo $e; ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Hashtag -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Hashtag</label>
                    <input type="text" name="hashtag" 
                           class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none"
                           placeholder="#socialmedia #marketing #design">
                </div>
                
                <!-- Assegnazione -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Assegnato a</label>
                        <select name="assegnato_a" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none">
                            <option value="">-- Nessuno --</option>
                            <?php foreach (USERS as $uid => $u): ?>
                            <option value="<?php echo e($uid); ?>"><?php echo e($u['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Tipologia</label>
                        <select name="tipologia" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none">
                            <option value="feed">Feed</option>
                            <option value="stories">Stories</option>
                            <option value="reels">Reels</option>
                            <option value="carousel">Carousel</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                </div>
                
                <!-- Note -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Note interne</label>
                    <textarea name="note" rows="2" 
                              class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-slate-500 outline-none resize-none"
                              placeholder="Note visibili solo al team..."></textarea>
                </div>
                
                <!-- Upload Contenuti -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Contenuti multimediali</label>
                    <div id="filePreview" class="grid grid-cols-4 gap-2 mb-2">
                        <!-- Preview files -->
                    </div>
                    <label class="flex items-center justify-center w-full h-20 border-2 border-dashed border-slate-300 rounded-lg cursor-pointer hover:border-slate-500 hover:bg-slate-50 transition-colors">
                        <div class="text-center">
                            <svg class="mx-auto h-6 w-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs text-slate-500 mt-1">Carica foto/video</span>
                        </div>
                        <input type="file" name="contenuti[]" multiple accept="image/*,video/*" class="hidden" onchange="previewFiles(this)">
                    </label>
                </div>
            </form>
            
            <!-- Footer -->
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50">
                <button type="button" onclick="closePostModal()" class="px-4 py-2 text-slate-600 hover:text-slate-800 font-medium text-sm">
                    Annulla
                </button>
                <button type="button" onclick="savePost()" class="px-6 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 font-medium text-sm">
                    Salva Post
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dettaglio Post -->
<div id="detailModal" class="fixed inset-0 z-50 hidden" onclick="if(event.target === this) closeDetailModal()">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col">
            <div id="detailContent" class="flex-1 overflow-y-auto p-6">
                <!-- JS popolerà -->
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between bg-slate-50">
                <button onclick="deletePost()" class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg font-medium text-sm">
                    Elimina
                </button>
                <div class="flex gap-2">
                    <button onclick="editPost()" class="px-4 py-2 bg-slate-800 text-white rounded-lg hover:bg-slate-700 font-medium text-sm">
                        Modifica
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let posts = [];
let currentPostId = null;

// Carica posts
async function loadPosts() {
    const params = new URLSearchParams({
        action: 'list',
        progetto_id: document.getElementById('filtroProgetto').value,
        stato: document.getElementById('filtroStato').value,
        mese: document.getElementById('filtroMese').value
    });
    
    try {
        const res = await fetch(`api/piano_editoriale.php?${params}`);
        const data = await res.json();
        if (data.success) {
            posts = data.data || [];
            renderCalendar();
        }
    } catch (e) {
        console.error('Errore:', e);
    }
}

// Render calendario
function renderCalendar() {
    const grid = document.getElementById('calendarGrid');
    const mese = document.getElementById('filtroMese').value;
    const [anno, meseNum] = mese.split('-').map(Number);
    
    const primo = new Date(anno, meseNum - 1, 1);
    const ultimo = new Date(anno, meseNum, 0);
    const giorni = ultimo.getDate();
    const offset = (primo.getDay() + 6) % 7;
    
    let html = '';
    
    // Celle vuote
    for (let i = 0; i < offset; i++) {
        html += '<div class="min-h-[100px] bg-slate-25 border-b border-r border-slate-100"></div>';
    }
    
    const oggi = new Date();
    
    for (let g = 1; g <= giorni; g++) {
        const data = `${anno}-${String(meseNum).padStart(2, '0')}-${String(g).padStart(2, '0')}`;
        const postGiorno = posts.filter(p => p.data_prevista === data);
        const isOggi = oggi.getDate() === g && oggi.getMonth() + 1 === meseNum;
        
        html += `
            <div class="min-h-[100px] bg-white border-b border-r border-slate-100 p-2 relative group ${isOggi ? 'bg-blue-50/50' : ''}" onclick="openPostModal('${data}')">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-semibold ${isOggi ? 'text-blue-600' : 'text-slate-700'}">${g}</span>
                    ${postGiorno.length > 0 ? `<span class="text-xs bg-slate-800 text-white px-1.5 py-0.5 rounded">${postGiorno.length}</span>` : ''}
                </div>
                <div class="space-y-1">
                    ${postGiorno.slice(0, 2).map(p => `
                        <div onclick="event.stopPropagation(); openDetailModal('${p.id}')" 
                             class="text-xs p-1.5 rounded cursor-pointer hover:opacity-80 truncate ${getStatoClass(p.stato)}">
                            ${escapeHtml(p.titolo.substring(0, 20))}${p.titolo.length > 20 ? '...' : ''}
                        </div>
                    `).join('')}
                    ${postGiorno.length > 2 ? `<div class="text-xs text-slate-400 text-center">+${postGiorno.length - 2}</div>` : ''}
                </div>
            </div>
        `;
    }
    
    grid.innerHTML = html;
}

function getStatoClass(stato) {
    const classi = {
        'bozza': 'bg-slate-100 text-slate-700',
        'in_revisione': 'bg-yellow-100 text-yellow-700',
        'approvato': 'bg-blue-100 text-blue-700',
        'programmato': 'bg-purple-100 text-purple-700',
        'pubblicato': 'bg-emerald-100 text-emerald-700'
    };
    return classi[stato] || 'bg-slate-100';
}

// Modal functions
function openPostModal(data = null) {
    document.getElementById('postForm').reset();
    document.getElementById('postId').value = '';
    document.getElementById('modalTitle').textContent = 'Nuovo Post';
    
    if (data) {
        document.querySelector('input[name="data_prevista"]').value = data;
    }
    
    document.getElementById('postModal').classList.remove('hidden');
}

function closePostModal() {
    document.getElementById('postModal').classList.add('hidden');
}

async function openDetailModal(id) {
    currentPostId = id;
    const post = posts.find(p => p.id == id);
    if (!post) return;
    
    const piattaforme = <?php echo json_encode($piattaforme); ?>;
    const stati = <?php echo json_encode($stati); ?>;
    const p = piattaforme[post.piattaforma] || { nome: post.piattaforma, colore: '#999' };
    const s = stati[post.stato] || { label: post.stato };
    
    document.getElementById('detailContent').innerHTML = `
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold" style="background-color: ${p.colore}">
                ${p.nome.substring(0, 2).toUpperCase()}
            </div>
            <div>
                <h3 class="font-semibold text-slate-800">${escapeHtml(post.titolo)}</h3>
                <span class="inline-block px-2 py-0.5 rounded text-xs ${s.colore}">${s.label}</span>
            </div>
        </div>
        
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-slate-500">Data:</span>
                    <span class="font-medium ml-1">${formatDate(post.data_prevista)}</span>
                </div>
                <div>
                    <span class="text-slate-500">Piattaforma:</span>
                    <span class="font-medium ml-1">${p.nome}</span>
                </div>
            </div>
            
            ${post.descrizione ? `
            <div class="bg-slate-50 rounded-lg p-4">
                <p class="text-sm text-slate-700 whitespace-pre-wrap">${escapeHtml(post.descrizione)}</p>
            </div>
            ` : ''}
            
            ${post.hashtag ? `
            <div>
                <span class="text-xs text-slate-500 uppercase tracking-wider">Hashtag</span>
                <p class="text-sm text-blue-600 mt-1">${escapeHtml(post.hashtag)}</p>
            </div>
            ` : ''}
            
            ${post.note ? `
            <div>
                <span class="text-xs text-slate-500 uppercase tracking-wider">Note</span>
                <p class="text-sm text-slate-600 mt-1">${escapeHtml(post.note)}</p>
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('detailModal').classList.remove('hidden');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
}

async function editPost() {
    closeDetailModal();
    const post = posts.find(p => p.id == currentPostId);
    if (!post) return;
    
    openPostModal();
    document.getElementById('modalTitle').textContent = 'Modifica Post';
    document.getElementById('postId').value = post.id;
    
    // Fill form
    document.querySelector('select[name="progetto_id"]').value = post.progetto_id;
    document.querySelector('input[name="titolo"]').value = post.titolo;
    document.querySelector('input[name="data_prevista"]').value = post.data_prevista;
    document.querySelector('input[name="ora_prevista"]').value = post.ora_prevista || '';
    document.querySelector('select[name="stato"]').value = post.stato;
    document.querySelector('textarea[name="descrizione"]').value = post.descrizione || '';
    document.querySelector('input[name="hashtag"]').value = post.hashtag || '';
    document.querySelector('select[name="assegnato_a"]').value = post.assegnato_a || '';
    document.querySelector('select[name="tipologia"]').value = post.tipologia;
    document.querySelector('textarea[name="note"]').value = post.note || '';
    
    // Sponsorizzato
    if (post.is_sponsored == 1) {
        document.getElementById('isSponsored').checked = true;
        document.getElementById('budgetWrapper').classList.remove('hidden');
        document.querySelector('input[name="budget_sponsorizzato"]').value = post.budget_sponsorizzato || '';
    }
    
    // Radio piattaforma
    document.querySelector(`input[name="piattaforma"][value="${post.piattaforma}"]`).checked = true;
}

async function savePost() {
    const form = document.getElementById('postForm');
    const formData = new FormData(form);
    
    // Aggiungi action
    formData.append('action', formData.get('id') ? 'update' : 'create');
    
    try {
        const res = await fetch('api/piano_editoriale.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        
        if (data.success) {
            showToast('Post salvato con successo', 'success');
            closePostModal();
            loadPosts();
        } else {
            showToast(data.message || 'Errore salvataggio', 'error');
        }
    } catch (e) {
        showToast('Errore di connessione', 'error');
    }
}

async function deletePost() {
    if (!confirm('Eliminare questo post?')) return;
    
    try {
        const res = await fetch('api/piano_editoriale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete&id=${currentPostId}&csrf_token=${document.querySelector('input[name="csrf_token"]').value}`
        });
        
        const data = await res.json();
        
        if (data.success) {
            showToast('Post eliminato', 'success');
            closeDetailModal();
            loadPosts();
        }
    } catch (e) {
        showToast('Errore eliminazione', 'error');
    }
}

function previewFiles(input) {
    const preview = document.getElementById('filePreview');
    preview.innerHTML = '';
    
    Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'aspect-square rounded-lg overflow-hidden bg-slate-100 relative';
            
            if (file.type.startsWith('image/')) {
                div.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
            } else if (file.type.startsWith('video/')) {
                div.innerHTML = `
                    <video src="${e.target.result}" class="w-full h-full object-cover"></video>
                    <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                        </svg>
                    </div>
                `;
            }
            
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

function addEmoji(emoji) {
    const textarea = document.querySelector('textarea[name="descrizione"]');
    textarea.value += emoji;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' });
}

// Inizializza
document.addEventListener('DOMContentLoaded', loadPosts);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
