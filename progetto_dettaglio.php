<?php
/**
 * Eterea Gestionale
 * Dettaglio Progetto
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

// Verifica ID progetto
$progettoId = $_GET['id'] ?? '';
if (empty($progettoId)) {
    header('Location: progetti.php');
    exit;
}

// Carica progetto
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.ragione_sociale as cliente_nome, c.logo_path as cliente_logo, c.email as cliente_email, c.telefono as cliente_telefono
        FROM progetti p
        LEFT JOIN clienti c ON p.cliente_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$progettoId]);
    $progetto = $stmt->fetch();
    
    if (!$progetto) {
        header('Location: progetti.php');
        exit;
    }
    
    $progetto['tipologie'] = json_decode($progetto['tipologie'] ?? '[]', true);
    $progetto['partecipanti'] = json_decode($progetto['partecipanti'] ?? '[]', true);
    
    // Recupera avatar partecipanti
    $progetto['partecipanti_avatar'] = [];
    if (!empty($progetto['partecipanti'])) {
        $placeholders = implode(',', array_fill(0, count($progetto['partecipanti']), '?'));
        $stmtAvatar = $pdo->prepare("SELECT id, avatar FROM utenti WHERE id IN ($placeholders)");
        $stmtAvatar->execute($progetto['partecipanti']);
        while ($row = $stmtAvatar->fetch()) {
            $progetto['partecipanti_avatar'][$row['id']] = $row['avatar'];
        }
    }
    
    // Marca progetto come visualizzato (azzera notifiche)
    try {
        $stmt = $pdo->prepare("
            INSERT INTO task_visualizzazioni (user_id, progetto_id, last_viewed) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE last_viewed = NOW()
        ");
        $stmt->execute([$_SESSION['user_id'], $progettoId]);
    } catch (PDOException $e) {
        error_log("Errore aggiornamento visualizzazione: " . $e->getMessage());
    }
    
} catch (PDOException $e) {
    header('Location: progetti.php');
    exit;
}

$pageTitle = $progetto['titolo'];

// Carica clienti per select
$clienti = [];
try {
    $stmt = $pdo->query("SELECT id, ragione_sociale FROM clienti ORDER BY ragione_sociale ASC");
    $clienti = $stmt->fetchAll();
} catch (PDOException $e) {}

include __DIR__ . '/includes/header.php';
?>

<!-- Stili per i commenti task -->
<style>
/* Tooltip custom */
.tooltip-container {
    position: relative;
    cursor: pointer;
}

.tooltip-container .tooltip-text {
    visibility: hidden;
    width: max-content;
    max-width: 200px;
    background-color: #1e293b;
    color: white;
    text-align: center;
    padding: 6px 10px;
    border-radius: 6px;
    position: absolute;
    z-index: 100;
    bottom: 125%;
    left: 50%;
    transform: translateX(-50%);
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.2s;
    pointer-events: none;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.tooltip-container .tooltip-text::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -5px;
    border-width: 5px;
    border-style: solid;
    border-color: #1e293b transparent transparent transparent;
}

.tooltip-container:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
}

/* Nuvoletta commento */
.task-commento {
    position: relative;
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 12px;
    padding: 10px 14px;
    margin-bottom: 10px;
    display: inline-flex;
    align-items: flex-start;
    gap: 10px;
    max-width: 100%;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

@media (max-width: 640px) {
    .task-commento {
        padding: 12px 16px;
        margin-bottom: 12px;
        border-radius: 16px;
    }
}

.task-commento::before {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 20px;
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid #bae6fd;
}

.task-commento::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 21px;
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 5px solid #f0f9ff;
}

.task-commento .commento-text {
    font-size: 13px;
    color: #334155;
    line-height: 1.4;
    flex: 1;
    word-break: break-word;
}

.task-commento .commento-autore {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    flex-shrink: 0;
    overflow: hidden;
    border: 2px solid white;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.task-commento .commento-autore img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.task-commento .btn-elimina-commento {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 18px;
    height: 18px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    font-size: 10px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
    box-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.task-commento:hover .btn-elimina-commento {
    opacity: 1;
}

/* Form aggiunta commento */
.task-commento-form {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px dashed #e2e8f0;
}

.task-commento-form input {
    flex: 1;
    padding: 6px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 20px;
    font-size: 13px;
    outline: none;
}

.task-commento-form input:focus {
    border-color: #0891b2;
    box-shadow: 0 0 0 3px rgba(8, 145, 178, 0.1);
}

.task-commento-form button {
    padding: 6px 12px;
    background: #0891b2;
    color: white;
    border: none;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.2s;
}

.task-commento-form button:hover {
    background: #0e7490;
}

/* Container commenti */
.task-commenti-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 10px;
}

@media (max-width: 640px) {
    .task-commenti-container {
        gap: 12px;
        margin-bottom: 12px;
    }
}
</style>

<!-- Header -->
<div class="mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="progetti.php" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-slate-800"><?php echo e($progetto['titolo']); ?></h1>
                <?php 
                $statoColor = COLORI_STATO_PROGETTO[$progetto['stato_progetto']] ?? 'gray';
                $statoLabel = STATI_PROGETTO[$progetto['stato_progetto']] ?? $progetto['stato_progetto'];
                ?>
                <span class="px-3 py-1 rounded-full text-xs font-medium bg-<?php echo $statoColor; ?>-100 text-<?php echo $statoColor; ?>-700">
                    <?php echo $statoLabel; ?>
                </span>
            </div>
            <p class="text-sm text-slate-500 flex items-center gap-2">
                <?php if ($progetto['cliente_id']): ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <a href="clienti.php?id=<?php echo $progetto['cliente_id']; ?>" class="hover:text-cyan-600">
                    <?php echo e($progetto['cliente_nome']); ?>
                </a>
                <?php else: ?>
                <span>Nessun cliente assegnato</span>
                <?php endif; ?>
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <?php if ($progetto['stato_progetto'] === 'consegnato'): ?>
                <?php if (!$progetto['distribuzione_effettuata']): ?>
                <button onclick="calcolaDistribuzione()" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Calcola Distribuzione
                </button>
                <?php else: ?>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs sm:text-sm font-medium">
                        ✓ Distribuzione Effettuata
                    </span>
                    <button onclick="revocaDistribuzione()" 
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-medium flex items-center gap-2"
                            title="Revoca la distribuzione per correggere eventuali errori">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Revoca
                    </button>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Variabili globali del progetto
const progettoId = '<?php echo $progettoId; ?>';
const progettoData = <?php echo json_encode($progetto); ?>;

// Tab switching function - inline per evitare errori di caricamento
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('[id^="tab-"]').forEach(el => {
        el.classList.remove('text-cyan-600', 'border-b-2', 'border-cyan-600');
        el.classList.add('text-slate-500');
    });
    document.getElementById('content-' + tabName).classList.remove('hidden');
    const tabBtn = document.getElementById('tab-' + tabName);
    tabBtn.classList.remove('text-slate-500');
    tabBtn.classList.add('text-cyan-600', 'border-b-2', 'border-cyan-600');
    if (tabName === 'economia' && typeof loadTransazioni === 'function') loadTransazioni();
    if (tabName === 'documenti' && typeof loadDocumenti === 'function') loadDocumenti();
}
</script>

<!-- Tabs -->
<div class="bg-white rounded-t-2xl shadow-sm border border-slate-200 border-b-0">
    <div class="flex overflow-x-auto border-b border-slate-200 scrollbar-hide">
        <button onclick="switchTab('dettagli')" id="tab-dettagli" 
                class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm font-medium text-cyan-600 border-b-2 border-cyan-600 whitespace-nowrap flex-shrink-0">
            Dettagli
        </button>
        <button onclick="switchTab('task')" id="tab-task" 
                class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap flex-shrink-0">
            Task
            <span id="taskCountBadge" class="ml-1 sm:ml-2 px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-xs">0</span>
        </button>
        <button onclick="switchTab('documenti')" id="tab-documenti" 
                class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap flex-shrink-0">
            Documenti
            <span id="docCountBadge" class="ml-1 sm:ml-2 px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-xs">0</span>
        </button>
        <button onclick="switchTab('economia')" id="tab-economia" 
                class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap flex-shrink-0">
            Economia
        </button>
        <button onclick="switchTab('controllo')" id="tab-controllo" 
                class="px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap flex-shrink-0">
            Controllo
        </button>
    </div>
</div>

<!-- Tab Content -->
<div class="bg-white rounded-b-2xl shadow-sm border border-slate-200 border-t-0 p-4 sm:p-6">
    
    <!-- Tab: Dettagli -->
    <div id="content-dettagli" class="tab-content">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-8">
            <!-- Info Generali -->
            <div>
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Informazioni
                </h3>
                
                <div class="space-y-4">
                    <div class="bg-slate-50 rounded-xl p-4">
                        <label class="text-sm text-slate-500">Descrizione</label>
                        <p class="text-slate-800 mt-2 leading-relaxed"><?php echo nl2br(e($progetto['descrizione'] ?: 'Nessuna descrizione')); ?></p>
                    </div>
                    
                    <div>
                        <label class="text-sm text-slate-500">Tipologie</label>
                        <div class="flex flex-wrap gap-2 mt-1">
                            <?php foreach ($progetto['tipologie'] as $tipo): ?>
                            <span class="px-3 py-1 bg-slate-100 text-slate-700 rounded-full text-sm"><?php echo $tipo; ?></span>
                            <?php endforeach; ?>
                            <?php if (empty($progetto['tipologie'])): ?>
                            <span class="text-slate-400">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="text-sm text-slate-500">Colore Progetto</label>
                        <div class="flex items-center gap-3 mt-1">
                            <?php 
                            $coloreTag = $progetto['colore_tag'] ?: '#FFFFFF';
                            $isDefault = $coloreTag === '#FFFFFF';
                            $coloriNomi = [
                                '#FFFFFF' => 'Bianco (default)',
                                '#22D3EE' => 'Ciano',
                                '#60A5FA' => 'Blu',
                                '#34D399' => 'Verde',
                                '#A3E635' => 'Lime',
                                '#FACC15' => 'Giallo',
                                '#FB923C' => 'Arancione',
                                '#F87171' => 'Rosso',
                                '#F472B6' => 'Rosa',
                                '#C084FC' => 'Viola',
                                '#A78BFA' => 'Indaco',
                                '#94A3B8' => 'Grigio',
                                '#78350F' => 'Marrone',
                                '#14B8A6' => 'Turchese',
                                '#E879F9' => 'Fucsia'
                            ];
                            ?>
                            <div class="w-8 h-8 rounded-lg border border-slate-200" style="background-color: <?php echo $coloreTag; ?>"></div>
                            <span class="text-slate-700 text-sm"><?php echo $coloriNomi[$coloreTag] ?? 'Personalizzato'; ?></span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-xl p-3">
                            <label class="text-sm text-slate-500">Data Inizio</label>
                            <p class="text-slate-800 mt-1 font-medium"><?php echo formatDate($progetto['data_inizio']); ?></p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-3">
                            <label class="text-sm text-slate-500">Consegna Prevista</label>
                            <p class="text-slate-800 mt-1 font-medium"><?php echo formatDate($progetto['data_consegna_prevista']); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($progetto['data_consegna_effettiva']): ?>
                    <div>
                        <label class="text-sm text-slate-500">Consegna Effettiva</label>
                        <p class="text-slate-800 mt-1"><?php echo formatDate($progetto['data_consegna_effettiva']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Partecipanti -->
            <div>
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Partecipanti
                </h3>
                
                <div class="space-y-3">
                    <?php 
                    $countPartecipanti = count($progetto['partecipanti']);
                    foreach ($progetto['partecipanti'] as $userId):
                        $user = USERS[$userId] ?? null;
                        if (!$user) continue;
                        
                        // Calcola percentuale
                        $percentuale = match($countPartecipanti) {
                            1 => 70,
                            2 => 40,
                            3 => 30,
                            default => 0
                        };
                    ?>
                    <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl">
                        <?php 
                        $avatar = $progetto['partecipanti_avatar'][$userId] ?? null;
                        if ($avatar && file_exists(__DIR__ . '/assets/uploads/avatars/' . $avatar)): 
                        ?>
                            <img src="assets/uploads/avatars/<?php echo e($avatar); ?>" 
                                 alt="<?php echo e($user['nome']); ?>"
                                 class="w-10 h-10 rounded-full object-cover border-2 border-white">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-medium" 
                                 style="background-color: <?php echo $user['colore']; ?>">
                                <?php echo substr($user['nome'], 0, 2); ?>
                            </div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <p class="font-medium text-slate-800"><?php echo $user['nome']; ?></p>
                            <p class="text-sm text-slate-500">Attivo sul progetto</p>
                        </div>
                        <span class="text-sm font-semibold text-cyan-600"><?php echo $percentuale; ?>%</span>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($countPartecipanti > 0): ?>
                    <div class="flex items-center gap-3 p-3 bg-emerald-50 rounded-xl border border-emerald-100">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-medium bg-emerald-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-slate-800">Cassa Aziendale</p>
                            <p class="text-sm text-slate-500">Contributo</p>
                        </div>
                        <span class="text-sm font-semibold text-emerald-600">10%</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tab: Task -->
    <div id="content-task" class="tab-content hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h3 class="font-semibold text-slate-800">Task del Progetto</h3>
            <button onclick="openTaskModal()" 
                    class="w-full sm:w-auto px-4 py-3 min-h-[44px] bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium text-sm flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuova Task
            </button>
        </div>
        
        <div id="taskList" class="space-y-3">
            <p class="text-center text-slate-400 py-8">Caricamento task...</p>
        </div>
    </div>
    
    <!-- Tab: Documenti -->
    <div id="content-documenti" class="tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Upload Documenti -->
            <div>
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Documenti del Progetto
                </h3>
                
                <!-- Lista Documenti -->
                <div id="documentiList" class="space-y-3 mb-6">
                    <p class="text-center text-slate-400 py-4">Caricamento documenti...</p>
                </div>
                
                <!-- Form Upload -->
                <div class="bg-slate-50 rounded-xl p-4 sm:p-5 border border-slate-200">
                    <h4 class="font-medium text-slate-700 mb-3">Aggiungi Documento</h4>
                    <form id="documentoForm" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="progetto_id" value="<?php echo $progettoId; ?>">
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">File PDF o ZIP (max 5MB)</label>
                            <input type="file" name="documento" id="documentoInput" accept=".pdf,.zip,application/zip" 
                                   class="w-full text-base text-slate-600 file:mr-4 file:py-3 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100"
                                   onchange="validateDocumento(this)">
                            <p class="text-xs text-slate-500 mt-2">
                                Massimo 5 documenti per progetto. Formato: PDF o ZIP
                            </p>
                            <p id="documentoError" class="text-xs text-red-500 mt-1 hidden"></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Note</label>
                            <textarea name="note" rows="3" 
                                      class="w-full text-base px-3 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none"
                                      placeholder="Aggiungi note opzionali sul documento..."></textarea>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <span id="documentiCounter" class="text-sm text-slate-500">0/5 documenti</span>
                            <button type="button" onclick="uploadDocumento()" 
                                    class="w-full sm:w-auto px-4 py-3 min-h-[44px] bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Carica Documento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Anteprima e Info -->
            <div>
                <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Informazioni
                </h3>
                
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <div>
                            <p class="font-medium text-amber-800 text-sm">Limiti Upload</p>
                            <ul class="text-sm text-amber-700 mt-1 space-y-1">
                                <li>• Massimo 5 documenti per progetto</li>
                                <li>• Formato accettato: PDF o ZIP</li>
                                <li>• Dimensione massima: 5MB per file</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div id="documentoPreview" class="hidden bg-slate-50 rounded-xl border border-slate-200 p-4">
                    <p class="text-sm font-medium text-slate-700 mb-2">Anteprima Documento</p>
                    <div class="aspect-[3/4] bg-white rounded-lg border border-slate-200 flex items-center justify-center">
                        <div class="text-center p-4">
                            <svg class="w-16 h-16 text-red-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm text-slate-500" id="previewFilename">documento.pdf</p>
                            <p class="text-xs text-slate-400" id="previewSize">0 KB</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tab: Economia -->
    <div id="content-economia" class="tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            <!-- Riepilogo -->
            <div class="lg:col-span-1 space-y-3 sm:space-y-4">
                <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl p-4 sm:p-5 text-white">
                    <p class="text-cyan-100 text-sm">Prezzo Totale</p>
                    <p class="text-2xl sm:text-3xl font-bold"><?php echo formatCurrency($progetto['prezzo_totale']); ?></p>
                </div>
                
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5">
                    <p class="text-slate-500 text-sm">Acconto</p>
                    <p class="text-lg sm:text-xl font-semibold text-slate-800"><?php echo formatCurrency($progetto['acconto_importo']); ?></p>
                </div>
                
                <div class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5">
                    <p class="text-slate-500 text-sm">Saldo</p>
                    <p class="text-lg sm:text-xl font-semibold text-slate-800"><?php echo formatCurrency($progetto['saldo_importo']); ?></p>
                </div>
                
                <?php if ($progetto['distribuzione_effettuata']): ?>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 sm:p-4 flex items-center gap-3">
                    <svg class="w-6 h-6 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-medium text-emerald-800">Distribuzione Effettuata</p>
                        <p class="text-xs sm:text-sm text-emerald-600">L'importo è stato suddiviso</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Transazioni -->
            <div class="lg:col-span-2">
                <h4 class="font-semibold text-slate-800 mb-3 sm:mb-4">Transazioni</h4>
                <div id="transazioniList" class="space-y-2 sm:space-y-3">
                    <p class="text-center text-slate-400 py-6 sm:py-8">Nessuna transazione</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tab: Controllo -->
    <div id="content-controllo" class="tab-content hidden">
        <div class="space-y-4 sm:space-y-6">
            <!-- Header con info -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="font-semibold text-slate-800">Checklist di Controllo</h3>
                    <p class="text-sm text-slate-500">Verifica completamento per ogni tipologia di lavoro</p>
                </div>
                <div id="lastSaveControllo" class="text-sm text-slate-400 hidden">
                    Ultimo salvataggio: <span class="font-medium"></span>
                </div>
            </div>
            
            <!-- Selettore Tipologia -->
            <div class="bg-slate-50 rounded-xl p-3 sm:p-4">
                <label class="block text-sm font-medium text-slate-700 mb-3">Seleziona tipologia</label>
                <div class="flex flex-wrap gap-2" id="tipologieControlloButtons">
                    <?php foreach (TIPOLOGIE_PROGETTO as $tipo): ?>
                    <button type="button" onclick="showChecklist('<?php echo e($tipo); ?>')" 
                            id="btn-checklist-<?php echo e(str_replace(' ', '_', $tipo)); ?>"
                            class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 hover:border-cyan-500 hover:text-cyan-600 transition-colors">
                        <?php echo e($tipo); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Contenuto Checklist -->
            <div id="checklistContainer" class="hidden">
                <!-- Sito Web - Linguaggio -->
                <div id="sitoWebLinguaggio" class="hidden mb-4 sm:mb-6 bg-cyan-50 border border-cyan-200 rounded-xl p-3 sm:p-4">
                    <label class="block text-sm font-medium text-cyan-800 mb-2">
                        Linguaggio/Framework utilizzato
                    </label>
                    <input type="text" id="linguaggioSito" placeholder="Es: PHP/Laravel, React, Next.js, WordPress..."
                           class="w-full text-base px-3 py-3 border border-cyan-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none bg-white">
                </div>
                
                <!-- Lista Checklist -->
                <div id="checklistItems" class="space-y-2 sm:space-y-3">
                    <!-- Popolato via JS -->
                </div>
                
                <!-- Pulsante Salva -->
                <div class="mt-4 sm:mt-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-4">
                    <button onclick="salvaChecklist()" 
                            class="w-full sm:w-auto px-6 py-3 min-h-[44px] bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-xl transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Salva Checklist
                    </button>
                    <span id="saveStatusControllo" class="text-sm hidden"></span>
                </div>
            </div>
            
            <!-- Messaggio iniziale -->
            <div id="checklistEmptyState" class="text-center py-8 sm:py-12 text-slate-400">
                <svg class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <p class="text-sm sm:text-base">Seleziona una tipologia per visualizzare la checklist</p>
            </div>
        </div>
    </div>
    
</div>

<!-- Modal Nuova Task -->
<div id="taskModal" class="fixed inset-0 z-[60] hidden">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('taskModal')"></div>
    
    <!-- Modal Content -->
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-2xl sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[85vh] sm:max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="font-bold text-slate-800" id="taskModalTitle">Nuova Task</h3>
                <button onclick="closeModal('taskModal')" class="text-slate-400 hover:text-slate-600 p-2 -mr-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Body -->
            <form id="taskForm" class="p-6 space-y-4 overflow-y-auto flex-1">
                <input type="hidden" name="progetto_id" value="<?php echo $progettoId; ?>">
                <input type="hidden" name="task_id" id="taskIdInput">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Titolo *</label>
                    <input type="text" name="titolo" required
                           class="w-full text-base px-3 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Descrizione</label>
                    <textarea name="descrizione" rows="3"
                              class="w-full text-base px-3 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none"></textarea>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Assegnato a</label>
                        <div class="space-y-2 max-h-40 sm:max-h-32 overflow-y-auto border border-slate-200 rounded-lg p-3 bg-slate-50">
                            <?php foreach (USERS as $id => $u): ?>
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-slate-50 p-1.5 rounded">
                                <input type="checkbox" name="assegnati[]" value="<?php echo $id; ?>" class="w-4 h-4 text-cyan-600 border-slate-300 rounded focus:ring-cyan-500">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-medium" style="background-color: <?php echo $u['colore'] ?? '#0891B2'; ?>">
                                        <?php echo substr($u['nome'], 0, 1); ?>
                                    </div>
                                    <span class="text-sm text-slate-700"><?php echo e($u['nome']); ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Priorità</label>
                        <select name="priorita" class="w-full text-base px-3 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none bg-white">
                            <option value="bassa">Bassa</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Scadenza</label>
                    <input type="date" name="scadenza"
                           class="w-full text-base px-3 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Colore Task</label>
                    <div class="flex flex-wrap gap-2">
                        <?php
                        $taskColors = [
                            '#FFFFFF' => ['nome' => 'Default'],
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
                        foreach ($taskColors as $hex => $info): ?>
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="colore" value="<?php echo $hex; ?>" 
                                   class="peer sr-only" <?php echo $hex === '#FFFFFF' ? 'checked' : ''; ?>>
                            <div class="w-8 h-8 rounded-lg border-2 border-slate-200 peer-checked:border-cyan-500 peer-checked:ring-2 peer-checked:ring-cyan-200 transition-all"
                                 style="background-color: <?php echo $hex; ?>;"
                                 title="<?php echo $info['nome']; ?>">
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Immagine</label>
                    <div class="flex items-center gap-3">
                        <div id="taskImagePreview" class="hidden w-16 h-16 rounded-lg overflow-hidden border border-slate-200">
                            <img src="" alt="Preview" class="w-full h-full object-cover">
                        </div>
                        <label class="flex-1 cursor-pointer">
                            <input type="file" name="immagine" accept="image/*" class="hidden" onchange="previewTaskImage(this)">
                            <div class="px-4 py-2 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors text-center text-sm text-slate-600">
                                <span id="taskImageLabel">Scegli immagine...</span>
                            </div>
                        </label>
                        <button type="button" id="removeTaskImageBtn" onclick="removeTaskImage()" class="hidden text-red-500 hover:text-red-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Max 5MB (JPG, PNG, GIF, WEBP)</p>
                </div>
            </form>
            
            <!-- Footer -->
            <div class="p-3 sm:p-6 border-t border-slate-100 flex flex-row justify-end gap-2 sticky bottom-0 bg-white z-10">
                <button type="button" onclick="closeModal('taskModal')" class="px-3 py-2 sm:px-4 sm:py-2 text-sm sm:text-base text-slate-600 font-medium rounded-lg hover:bg-slate-100 transition-colors">
                    Annulla
                </button>
                <button type="button" onclick="saveTask()" class="px-4 py-2 sm:px-6 sm:py-2 text-sm sm:text-base bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium">
                    Salva
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Distribuzione -->
<div id="distribuzioneModal" class="fixed inset-0 z-[60] hidden">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('distribuzioneModal')"></div>
    
    <!-- Modal Content -->
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-md sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[85vh] sm:max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="font-bold text-slate-800">Anteprima Distribuzione</h3>
                <button onclick="closeModal('distribuzioneModal')" class="text-slate-400 hover:text-slate-600 p-2 -mr-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1">
                <!-- Toggle Cassa -->
                <div class="mb-4 p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" id="includiCassa" checked onchange="ricalcolaDistribuzione()"
                               class="w-5 h-5 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                        <div class="flex-1">
                            <span class="font-medium text-slate-800">Includi Cassa Aziendale (10%)</span>
                            <p class="text-xs text-slate-500">Deseleziona per distribuire solo ai membri</p>
                        </div>
                        <span class="text-lg">💰</span>
                    </label>
                </div>
                
                <!-- Selezione utenti da escludere -->
                <div class="mb-4 p-3 bg-slate-50 rounded-xl border border-slate-200">
                    <p class="font-medium text-slate-800 mb-2">Membri da includere</p>
                    <p class="text-xs text-slate-500 mb-3">Deseleziona chi non deve ricevere il compenso</p>
                    <div id="utentiDistribuzioneList" class="space-y-2">
                        <!-- Popolato via JS -->
                    </div>
                </div>
                
                <div id="distribuzionePreview">
                    <!-- Popolato via JS -->
                </div>
            </div>
            
            <!-- Footer -->
            <div class="p-3 sm:p-6 border-t border-slate-100 flex flex-row justify-end gap-2 sticky bottom-0 bg-white z-10">
                <button type="button" onclick="closeModal('distribuzioneModal')" class="px-3 py-2 sm:px-4 sm:py-2 text-sm sm:text-base text-slate-600 font-medium rounded-lg hover:bg-slate-100 transition-colors">
                    Annulla
                </button>
                <button type="button" onclick="confermaDistribuzione()" class="px-4 py-2 sm:px-6 sm:py-2 text-sm sm:text-base bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium">
                    Conferma Distribuzione
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Carica task all'apertura
document.addEventListener('DOMContentLoaded', function() {
    loadTask();
    
    // Controlla se c'è un parametro 'section' per aprire un tab specifico
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get('section');
    if (section && ['dettagli', 'task', 'documenti', 'economia'].includes(section)) {
        switchTab(section);
    }
});

function formatCurrency(val) {
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(val);
}

// Variabile globale per memorizzare l'ultima distribuzione calcolata
let lastDistribuzione = [];
let lastDistribuzioneConfig = { includiCassa: true, utentiEsclusi: [] };

function calcolaDistribuzione() {
    const includiCassa = document.getElementById('includiCassa')?.checked ?? true;
    const utentiEsclusi = getUtentiEsclusi();
    lastDistribuzioneConfig.includiCassa = includiCassa;
    lastDistribuzioneConfig.utentiEsclusi = utentiEsclusi;
    
    // Render lista utenti con checkbox
    renderUtentiDistribuzioneList();
    
    const result = generaDistribuzione(includiCassa, utentiEsclusi);
    lastDistribuzione = result.distribuzione;
    
    renderDistribuzione(result.distribuzione, result.totale);
    
    openModal('distribuzioneModal');
}

function ricalcolaDistribuzione() {
    const includiCassa = document.getElementById('includiCassa')?.checked ?? true;
    const utentiEsclusi = getUtentiEsclusi();
    lastDistribuzioneConfig.includiCassa = includiCassa;
    lastDistribuzioneConfig.utentiEsclusi = utentiEsclusi;
    
    const result = generaDistribuzione(includiCassa, utentiEsclusi);
    lastDistribuzione = result.distribuzione;
    
    renderDistribuzione(result.distribuzione, result.totale);
}

function getUtentiEsclusi() {
    const checkboxes = document.querySelectorAll('.utente-distribuzione-checkbox');
    const esclusi = [];
    checkboxes.forEach(cb => {
        if (!cb.checked) {
            esclusi.push(cb.value);
        }
    });
    return esclusi;
}

function renderUtentiDistribuzioneList() {
    const users = <?php echo json_encode(USERS); ?>;
    const partecipanti = progettoData.partecipanti || [];
    
    // Mostra tutti e 3 gli utenti con checkbox
    const tuttiUtenti = ['ucwurog3xr8tf', 'ukl9ipuolsebn', 'u3ghz4f2lnpkx'];
    
    const container = document.getElementById('utentiDistribuzioneList');
    if (!container) return;
    
    container.innerHTML = tuttiUtenti.map(uid => {
        const user = users[uid];
        const isPartecipante = partecipanti.includes(uid);
        const isChecked = isPartecipante ? 'checked' : '';
        const isDisabled = !isPartecipante ? 'disabled' : '';
        const opacity = !isPartecipante ? 'opacity-50' : '';
        
        return `
            <label class="flex items-center gap-3 cursor-pointer ${opacity}">
                <input type="checkbox" value="${uid}" ${isChecked} ${isDisabled}
                       onchange="ricalcolaDistribuzione()"
                       class="utente-distribuzione-checkbox w-5 h-5 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-medium" style="background-color: ${user?.colore || '#3B82F6'}">
                    ${user?.nome?.charAt(0) || '?'}
                </div>
                <span class="text-sm ${isPartecipante ? 'text-slate-800' : 'text-slate-400'}">${user?.nome || uid}</span>
                ${!isPartecipante ? '<span class="text-xs text-slate-400">(non partecipante)</span>' : ''}
            </label>
        `;
    }).join('');
}

function generaDistribuzione(includiCassa = true, utentiEsclusi = []) {
    const totale = parseFloat(progettoData.prezzo_totale) || 0;
    const partecipanti = progettoData.partecipanti || [];
    
    // Filtra partecipanti esclusi
    const partecipantiEffettivi = partecipanti.filter(uid => !utentiEsclusi.includes(uid));
    const count = partecipantiEffettivi.length;
    
    if (count === 0) {
        showToast('Nessun partecipante selezionato per la distribuzione', 'error');
        return { distribuzione: [], totale: 0 };
    }
    
    const distribuzione = [];
    
    // Calcola percentuali in base a se include o meno la cassa
    const cassaPercent = includiCassa ? 0.10 : 0;
    const remainingPercent = 1 - cassaPercent;
    
    switch(count) {
        case 3:
            // 3 partecipanti: dividi equamente il rimanente
            const share3 = remainingPercent / 3;
            partecipantiEffettivi.forEach(uid => {
                distribuzione.push({ id: uid, importo: totale * share3, percentuale: Math.round(share3 * 100), tipo: 'attivo' });
            });
            if (includiCassa) {
                distribuzione.push({ id: 'cassa', importo: totale * 0.10, percentuale: 10, tipo: 'cassa' });
            }
            break;
        case 2:
            const tutti = ['ucwurog3xr8tf', 'ukl9ipuolsebn', 'u3ghz4f2lnpkx'];
            // Inattivi = quelli non nei partecipanti effettivi, ma facenti parte del progetto originale
            const inattivi = tutti.filter(id => partecipanti.includes(id) && !partecipantiEffettivi.includes(id));
            // 2 partecipanti attivi: 40% ciascuno (o 45% senza cassa), 1 escluso: 10% (o 10% senza cassa)
            const activeShare2 = includiCassa ? 0.40 : 0.45;
            const passiveShare2 = 0.10;
            partecipantiEffettivi.forEach(uid => {
                distribuzione.push({ id: uid, importo: totale * activeShare2, percentuale: Math.round(activeShare2 * 100), tipo: 'attivo' });
            });
            inattivi.forEach(uid => {
                distribuzione.push({ id: uid, importo: totale * passiveShare2, percentuale: Math.round(passiveShare2 * 100), tipo: 'passivo' });
            });
            if (includiCassa) {
                distribuzione.push({ id: 'cassa', importo: totale * 0.10, percentuale: 10, tipo: 'cassa' });
            }
            break;
        case 1:
            const tutti2 = ['ucwurog3xr8tf', 'ukl9ipuolsebn', 'u3ghz4f2lnpkx'];
            // Inattivi = partecipanti del progetto ma esclusi dalla distribuzione
            const inattivi2 = tutti2.filter(id => partecipanti.includes(id) && !partecipantiEffettivi.includes(id));
            // 1 partecipante attivo: 70% (o 80% senza cassa), 2 esclusi: 10% ciascuno
            const activeShare1 = includiCassa ? 0.70 : 0.80;
            const passiveShare1 = 0.10;
            distribuzione.push({ id: partecipantiEffettivi[0], importo: totale * activeShare1, percentuale: Math.round(activeShare1 * 100), tipo: 'attivo' });
            inattivi2.forEach(uid => {
                distribuzione.push({ id: uid, importo: totale * passiveShare1, percentuale: Math.round(passiveShare1 * 100), tipo: 'passivo' });
            });
            if (includiCassa) {
                distribuzione.push({ id: 'cassa', importo: totale * 0.10, percentuale: 10, tipo: 'cassa' });
            }
            break;
    }
    
    return { distribuzione, totale };
}

function renderDistribuzione(distribuzione, totale) {
    const users = <?php echo json_encode(USERS); ?>;
    
    document.getElementById('distribuzionePreview').innerHTML = `
        <div class="space-y-3">
            <p class="text-sm text-slate-500 mb-4">Totale da distribuire: <strong>${formatCurrency(totale)}</strong></p>
            ${distribuzione.map(d => {
                const nome = d.id === 'cassa' ? 'Cassa Aziendale' : (users[d.id]?.nome || d.id);
                const colore = d.id === 'cassa' ? '#10B981' : (users[d.id]?.colore || '#3B82F6');
                return `
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-medium" style="background-color: ${colore}">
                                ${d.id === 'cassa' ? '💰' : nome.charAt(0)}
                            </div>
                            <div>
                                <p class="font-medium">${nome}</p>
                                <p class="text-xs text-slate-500">${d.tipo}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">${formatCurrency(d.importo)}</p>
                            <p class="text-xs text-slate-500">${d.percentuale}%</p>
                        </div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
    
    openModal('distribuzioneModal');
}

async function loadTask() {
    try {
        const response = await fetch(`api/task.php?action=list&progetto_id=${progettoId}`);
        const data = await response.json();
        
        const list = document.getElementById('taskList');
        document.getElementById('taskCountBadge').textContent = data.data?.length || 0;
        
        if (!data.success || !data.data || data.data.length === 0) {
            list.innerHTML = `
                <div class="text-center py-8 text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <p>Nessuna task</p>
                </div>
            `;
            return;
        }
        
        // Renderizza le task (senza commenti, che verranno caricati dopo)
        list.innerHTML = data.data.map(t => {
            const prioritaColor = {bassa: 'blue', media: 'yellow', alta: 'red'}[t.priorita];
            const statoClass = t.stato === 'completato' ? 'line-through text-slate-400' : '';
            const hasDescrizione = t.descrizione && t.descrizione.trim().length > 0;
            const descrizioneBreve = hasDescrizione ? (t.descrizione.length > 80 ? t.descrizione.substring(0, 80) + '...' : t.descrizione) : '';
            const taskBorderColor = t.colore && t.colore !== '#FFFFFF' ? `border-left: 4px solid ${t.colore}` : '';
            const taskBgColor = t.colore && t.colore !== '#FFFFFF' ? `background-color: ${t.colore}15` : '';
            
            const hasExpandableContent = hasDescrizione || t.assegnati_list?.length > 0 || t.scadenza;
            
            return `
                <div class="bg-white rounded-xl p-4 mb-3 shadow-sm border border-slate-100 sm:bg-slate-50 sm:rounded-xl sm:shadow-none sm:border-0" style="${taskBorderColor};${taskBgColor}" id="task-${t.id}">
                    <!-- Commenti (nuvolette) - caricati dinamicamente -->
                    <div class="task-commenti-container mb-3" id="commenti-container-${t.id}">
                        <!-- Caricamento... -->
                    </div>
                    
                    <!-- Mobile Card Layout -->
                    <div class="sm:hidden">
                        <div class="flex justify-between items-start gap-3 mb-2">
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <button onclick="toggleTaskStatus('${t.id}', '${t.stato === 'completato' ? 'da_fare' : 'completato'}')" 
                                        class="w-6 h-6 mt-0.5 rounded-full border-2 ${t.stato === 'completato' ? 'bg-cyan-500 border-cyan-500 text-white' : 'border-slate-300 hover:border-cyan-500'} flex items-center justify-center transition-colors flex-shrink-0">
                                    ${t.stato === 'completato' ? '✓' : ''}
                                </button>
                                ${t.immagine ? `
                                    <div class="flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden border border-slate-200 cursor-pointer" onclick="openImageModal('assets/uploads/${t.immagine}')">
                                        <img src="assets/uploads/${t.immagine}" alt="Task" class="w-full h-full object-cover">
                                    </div>
                                ` : ''}
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium ${statoClass} text-slate-800">${t.titolo}</p>
                                </div>
                            </div>
                            <span class="px-2 py-1 bg-${prioritaColor}-100 text-${prioritaColor}-700 rounded-lg text-xs font-medium flex-shrink-0">${t.priorita}</span>
                        </div>
                        
                        ${hasDescrizione ? `
                            <p class="text-sm text-slate-500 mt-2 line-clamp-2 pl-9">${descrizioneBreve}</p>
                        ` : ''}
                        
                        <div class="flex items-center justify-between mt-3 pl-9">
                            <div class="flex items-center gap-2">
                                ${t.assegnati_list && t.assegnati_list.length > 0 ? `
                                    <span class="flex -space-x-2">
                                        ${t.assegnati_list.slice(0, 3).map(a => `<span class="w-7 h-7 rounded-full border-2 border-white flex items-center justify-center text-white text-xs font-medium" style="background-color: ${a.colore}">${a.nome.charAt(0)}</span>`).join('')}
                                        ${t.assegnati_list.length > 3 ? `<span class="w-7 h-7 rounded-full border-2 border-white bg-slate-200 flex items-center justify-center text-xs text-slate-600">+${t.assegnati_list.length - 3}</span>` : ''}
                                    </span>
                                ` : ''}
                                ${t.scadenza ? `<span class="text-xs text-slate-400">📅 ${new Date(t.scadenza).toLocaleDateString('it-IT')}</span>` : ''}
                            </div>
                        </div>
                        
                        <!-- Bottoni azione mobile -->
                        <div class="flex gap-2 mt-4">
                            <button onclick="toggleTaskStatus('${t.id}', '${t.stato === 'completato' ? 'da_fare' : 'completato'}')" 
                                    class="flex-1 py-3 min-h-[44px] ${t.stato === 'completato' ? 'bg-emerald-600 text-white' : 'bg-cyan-600 text-white'} rounded-lg font-medium text-sm">
                                ${t.stato === 'completato' ? '✓ Completata' : 'Completa'}
                            </button>
                            <button onclick="editTask('${t.id}')" class="flex-1 py-3 min-h-[44px] bg-slate-100 text-slate-700 rounded-lg font-medium text-sm">
                                Modifica
                            </button>
                            <button onclick="deleteTask('${t.id}')" class="px-4 py-3 min-h-[44px] bg-red-50 text-red-600 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Dettagli espandibili mobile -->
                        <div class="mt-3 pt-3 border-t border-slate-100">
                            ${hasDescrizione ? `
                                <div class="text-sm text-slate-600 mb-3">${t.descrizione.replace(/\n/g, '<br>')}</div>
                            ` : ''}
                            ${t.assegnati_list && t.assegnati_list.length > 0 ? `
                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                    <span class="text-xs text-slate-400">Assegnati:</span>
                                    ${t.assegnati_list.map(a => `<span class="text-xs bg-slate-100 px-2 py-1 rounded">${a.nome}</span>`).join('')}
                                </div>
                            ` : ''}
                            ${t.stato === 'completato' ? `<span class="text-xs bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-lg inline-block">✓ Task Completata</span>` : `<span class="text-xs bg-amber-100 text-amber-700 px-3 py-1.5 rounded-lg inline-block">⏳ Da completare</span>`}
                            
                            <!-- Form aggiunta commento mobile -->
                            <div class="flex gap-2 mt-4 pt-3 border-t border-slate-100">
                                <input type="text" 
                                       id="commento-input-${t.id}" 
                                       placeholder="Aggiungi un commento..." 
                                       maxlength="500"
                                       class="flex-1 text-base px-3 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none"
                                       onkeypress="if(event.key==='Enter') aggiungiCommento('${t.id}')">
                                <button onclick="aggiungiCommento('${t.id}')" class="px-4 py-2.5 bg-cyan-600 text-white rounded-lg font-medium">
                                    Invia
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Desktop Layout (originale) -->
                    <div class="hidden sm:flex sm:flex-col">
                        <div class="flex items-start gap-4">
                            <button onclick="toggleTaskStatus('${t.id}', '${t.stato === 'completato' ? 'da_fare' : 'completato'}')" 
                                    class="w-6 h-6 mt-0.5 rounded-full border-2 ${t.stato === 'completato' ? 'bg-cyan-500 border-cyan-500 text-white' : 'border-slate-300 hover:border-cyan-500'} flex items-center justify-center transition-colors flex-shrink-0">
                                ${t.stato === 'completato' ? '✓' : ''}
                            </button>
                            ${t.immagine ? `
                                <div class="flex-shrink-0 w-14 h-14 rounded-lg overflow-hidden border border-slate-200 cursor-pointer" onclick="openImageModal('assets/uploads/${t.immagine}')">
                                    <img src="assets/uploads/${t.immagine}" alt="Task" class="w-full h-full object-cover hover:scale-110 transition-transform">
                                </div>
                            ` : ''}
                            <div class="flex-1 min-w-0">
                                <p class="font-medium ${statoClass}">${t.titolo}</p>
                                ${hasDescrizione ? `
                                    <p class="text-sm text-slate-500 mt-1 line-clamp-2" id="desc-preview-${t.id}">
                                        ${descrizioneBreve}
                                    </p>
                                ` : ''}
                            </div>
                            <span class="px-2 py-1 bg-${prioritaColor}-100 text-${prioritaColor}-700 rounded text-xs font-medium flex-shrink-0">${t.priorita}</span>
                            
                            <!-- Avatar creatore task -->
                            ${t.creato_nome ? `
                                <div class="flex-shrink-0 tooltip-container">
                                    <span class="tooltip-text">Creata da: ${t.creato_nome}</span>
                                    ${t.creato_avatar ? `
                                        <img src="assets/uploads/avatars/${t.creato_avatar}" alt="${t.creato_nome}" class="w-6 h-6 rounded-full border-2 border-white object-cover" style="box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                    ` : `
                                        <div class="w-6 h-6 rounded-full border-2 border-white flex items-center justify-center text-white text-xs font-medium" style="background-color: ${t.creato_colore || '#94A3B8'}; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">${t.creato_nome.charAt(0)}</div>
                                    `}
                                </div>
                            ` : ''}
                            
                            ${hasExpandableContent ? `
                                <button onclick="toggleTaskExpand('${t.id}')" class="text-slate-400 hover:text-cyan-600 p-1 transition-colors" title="Espandi/Collassa">
                                    <svg class="w-5 h-5 transform transition-transform" id="expand-icon-${t.id}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            ` : ''}
                            <div class="flex items-center gap-1 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="editTask('${t.id}')" class="text-slate-400 hover:text-cyan-600 p-1" title="Modifica">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button onclick="deleteTask('${t.id}')" class="text-slate-400 hover:text-red-500 p-1" title="Elimina">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Contenuto espandibile -->
                        <div class="hidden mt-3 pt-3 border-t border-slate-200" id="task-expand-${t.id}">
                            ${hasDescrizione ? `
                                <div class="text-sm text-slate-600 mb-3">${t.descrizione.replace(/\n/g, '<br>')}</div>
                            ` : ''}
                            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
                                ${t.assegnati_list && t.assegnati_list.length > 0 ? `
                                    <span class="flex items-center gap-1">
                                        <span class="text-xs text-slate-400">Assegnati:</span>
                                        <span class="flex -space-x-2">
                                            ${t.assegnati_list.map(a => `<span class="w-6 h-6 rounded-full border-2 border-white flex items-center justify-center text-white text-xs font-medium" style="background-color: ${a.colore}" title="${a.nome}">${a.nome.charAt(0)}</span>`).join('')}
                                        </span>
                                    </span>
                                ` : ''}
                                ${t.scadenza ? `<span class="text-xs bg-slate-100 px-2 py-1 rounded">📅 Scadenza: ${new Date(t.scadenza).toLocaleDateString('it-IT')}</span>` : ''}
                                ${t.stato === 'completato' ? `<span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded">✓ Completata</span>` : `<span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">⏳ Da fare</span>`}
                            </div>
                            
                            <!-- Form aggiunta commento -->
                            <div class="task-commento-form">
                                <input type="text" 
                                       id="commento-input-${t.id}" 
                                       placeholder="Aggiungi un commento..." 
                                       maxlength="500"
                                       onkeypress="if(event.key==='Enter') aggiungiCommento('${t.id}')">
                                <button onclick="aggiungiCommento('${t.id}')">Invia</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        // Carica i commenti per tutte le task in parallelo
        data.data.forEach(t => loadCommentiForTask(t.id));
        
    } catch (error) {
        showToast('Errore caricamento task', 'error');
    }
}

async function toggleTaskStatus(taskId, newStato) {
    try {
        const response = await fetch(`api/task.php?action=change_status&id=${encodeURIComponent(taskId)}&stato=${newStato}`);
        
        const data = await response.json();
        if (data.success) {
            loadTask();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function deleteTask(taskId) {
    confirmAction('Eliminare questa task?', async () => {
        try {
            const response = await fetch(`api/task.php?action=delete&id=${encodeURIComponent(taskId)}`);
            
            const data = await response.json();
            if (data.success) {
                showToast('Task eliminata', 'success');
                loadTask();
            } else {
                showToast(data.message || 'Errore eliminazione', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione', 'error');
        }
    });
}

// Preview immagine task
function previewTaskImage(input) {
    const preview = document.getElementById('taskImagePreview');
    const img = preview.querySelector('img');
    const label = document.getElementById('taskImageLabel');
    const removeBtn = document.getElementById('removeTaskImageBtn');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validazione dimensione (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showToast('Immagine troppo grande (max 5MB)', 'error');
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

function removeTaskImage() {
    const input = document.querySelector('[name="immagine"]');
    const preview = document.getElementById('taskImagePreview');
    const img = preview.querySelector('img');
    const label = document.getElementById('taskImageLabel');
    const removeBtn = document.getElementById('removeTaskImageBtn');
    
    input.value = '';
    img.src = '';
    preview.classList.add('hidden');
    label.textContent = 'Scegli immagine...';
    removeBtn.classList.add('hidden');
}

async function saveTask() {
    const form = document.getElementById('taskForm');
    const formData = new FormData(form);
    const taskId = document.getElementById('taskIdInput').value;
    
    // Prendi dati per calendario prima di inviare
    const titolo = form.querySelector('[name="titolo"]')?.value;
    const scadenza = form.querySelector('[name="scadenza"]')?.value;
    
    const action = taskId ? 'update' : 'create';
    if (taskId) formData.append('id', taskId);
    
    try {
        const response = await fetch(`api/task.php?action=${action}`, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            showToast(taskId ? 'Task aggiornata' : 'Task creata', 'success');
            closeModal('taskModal');
            form.reset();
            document.getElementById('taskIdInput').value = '';
            loadTask();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore', 'error');
    }
}

function toggleDescrizione(taskId) {
    const breve = document.getElementById(`desc-${taskId}`);
    const full = document.getElementById(`desc-full-${taskId}`);
    if (breve && full) {
        breve.classList.toggle('hidden');
        full.classList.toggle('hidden');
    }
}

// Espandi/Collassa task
function toggleTaskExpand(taskId) {
    const expandContent = document.getElementById(`task-expand-${taskId}`);
    const icon = document.getElementById(`expand-icon-${taskId}`);
    
    if (expandContent && icon) {
        const isHidden = expandContent.classList.contains('hidden');
        
        if (isHidden) {
            expandContent.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            expandContent.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }
}

// Modal immagine task
function openImageModal(src) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm';
    modal.innerHTML = `
        <div class="relative max-w-4xl max-h-[90vh]">
            <button onclick="this.closest('.fixed').remove()" class="absolute -top-10 right-0 text-white hover:text-gray-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <img src="${src}" alt="Task Image" class="max-w-full max-h-[85vh] rounded-lg shadow-2xl">
        </div>
    `;
    modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
    };
    document.body.appendChild(modal);
}

async function editTask(taskId) {
    try {
        const response = await fetch(`api/task.php?action=detail&id=${encodeURIComponent(taskId)}`);
        const data = await response.json();
        
        if (!data.success || !data.data) {
            showToast('Errore caricamento task', 'error');
            return;
        }
        
        const t = data.data;
        
        // Popola il form
        document.getElementById('taskIdInput').value = t.id;
        document.querySelector('#taskForm [name="titolo"]').value = t.titolo;
        document.querySelector('#taskForm [name="descrizione"]').value = t.descrizione || '';
        document.querySelector('#taskForm [name="priorita"]').value = t.priorita;
        document.querySelector('#taskForm [name="scadenza"]').value = t.scadenza || '';
        
        // Seleziona assegnati
        const assegnatiCheckboxes = document.querySelectorAll('#taskForm [name="assegnati[]"]');
        assegnatiCheckboxes.forEach(cb => {
            cb.checked = t.assegnati && t.assegnati.includes(cb.value);
        });
        
        // Seleziona colore
        const coloreInput = document.querySelector(`#taskForm [name="colore"][value="${t.colore || '#FFFFFF'}"]`);
        if (coloreInput) coloreInput.checked = true;
        
        // Mostra immagine esistente se presente
        const preview = document.getElementById('taskImagePreview');
        const img = preview.querySelector('img');
        const label = document.getElementById('taskImageLabel');
        const removeBtn = document.getElementById('removeTaskImageBtn');
        
        if (t.immagine) {
            img.src = 'assets/uploads/' + t.immagine;
            preview.classList.remove('hidden');
            label.textContent = 'Immagine caricata';
            removeBtn.classList.remove('hidden');
        } else {
            img.src = '';
            preview.classList.add('hidden');
            label.textContent = 'Scegli immagine...';
            removeBtn.classList.add('hidden');
        }
        
        // Cambia titolo modal
        document.getElementById('taskModalTitle').textContent = 'Modifica Task';
        
        openModal('taskModal');
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function confermaDistribuzione() {
    try {
        const includiCassa = lastDistribuzioneConfig.includiCassa ?? true;
        const utentiEsclusi = lastDistribuzioneConfig.utentiEsclusi ?? [];
        
        const response = await fetch('api/progetti.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=distribuisci&id=${progettoId}&includi_cassa=${includiCassa ? 1 : 0}&utenti_esclusi=${encodeURIComponent(JSON.stringify(utentiEsclusi))}`
        });
        
        const data = await response.json();
        if (data.success) {
            showToast('Distribuzione effettuata!', 'success');
            closeModal('distribuzioneModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore', 'error');
    }
}

async function revocaDistribuzione() {
    if (!confirm('Sei sicuro di voler revocare la distribuzione?\n\nQuesto:\n• Eliminerà le transazioni economiche\n• Sottrarrà gli importi dai wallet\n• Permetterà di rifare la distribuzione\n\nContinuare?')) {
        return;
    }
    
    try {
        const response = await fetch('api/progetti.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=revoca_distribuzione&id=${progettoId}`
        });
        
        const data = await response.json();
        if (data.success) {
            showToast('Distribuzione revocata! Ora puoi ricalcolare.', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function loadTransazioni() {
    try {
        const response = await fetch(`api/progetti.php?action=detail&id=${progettoId}`);
        const data = await response.json();
        
        if (!data.success) return;
        
        const transazioni = data.data.transazioni || [];
        const list = document.getElementById('transazioniList');
        
        if (transazioni.length === 0) {
            list.innerHTML = '<p class="text-center text-slate-400 py-8">Nessuna transazione registrata</p>';
            return;
        }
        
        const users = <?php echo json_encode(USERS); ?>;
        
        list.innerHTML = transazioni.map(t => {
            const nome = t.tipo === 'cassa' ? 'Cassa Aziendale' : (t.utente_nome || t.utente_id);
            const icon = t.tipo === 'cassa' ? '🏢' : '👤';
            return `
                <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">${icon}</span>
                        <div>
                            <p class="font-medium text-slate-800">${nome}</p>
                            <p class="text-sm text-slate-500">${t.descrizione || ''}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-emerald-600">+${formatCurrency(t.importo)}</p>
                        <p class="text-xs text-slate-400">${t.percentuale}%</p>
                    </div>
                </div>
            `;
        }).join('');
        
    } catch (error) {
        console.error('Errore:', error);
    }
}

function openTaskModal() {
    document.getElementById('taskForm').reset();
    document.getElementById('taskIdInput').value = '';
    document.getElementById('taskModalTitle').textContent = 'Nuova Task';
    
    // Reset colore (seleziona bianco default)
    const defaultColore = document.querySelector('#taskForm [name="colore"][value="#FFFFFF"]');
    if (defaultColore) defaultColore.checked = true;
    
    // Reset immagine
    removeTaskImage();
    
    openModal('taskModal');
}

// =====================================================
// GESTIONE DOCUMENTI
// =====================================================

function validateDocumento(input) {
    const errorEl = document.getElementById('documentoError');
    const previewEl = document.getElementById('documentoPreview');
    const previewFilename = document.getElementById('previewFilename');
    const previewSize = document.getElementById('previewSize');
    
    errorEl.classList.add('hidden');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Verifica tipo (PDF o ZIP)
        const allowedTypes = ['application/pdf', 'application/zip', 'application/x-zip-compressed'];
        if (!allowedTypes.includes(file.type) && !file.name.toLowerCase().endsWith('.zip')) {
            errorEl.textContent = 'Il file deve essere PDF o ZIP';
            errorEl.classList.remove('hidden');
            input.value = '';
            previewEl.classList.add('hidden');
            return;
        }
        
        // Verifica dimensione (5MB)
        if (file.size > 5 * 1024 * 1024) {
            errorEl.textContent = 'Il file non deve superare i 5MB';
            errorEl.classList.remove('hidden');
            input.value = '';
            previewEl.classList.add('hidden');
            return;
        }
        
        // Mostra anteprima
        previewFilename.textContent = file.name;
        previewSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
        previewEl.classList.remove('hidden');
    }
}

async function loadDocumenti() {
    try {
        const response = await fetch(`api/progetti.php?action=list_documenti&progetto_id=${progettoId}`);
        const data = await response.json();
        
        const list = document.getElementById('documentiList');
        const counter = document.getElementById('documentiCounter');
        const badge = document.getElementById('docCountBadge');
        
        if (data.success) {
            const documenti = data.data || [];
            
            // Aggiorna contatori
            counter.textContent = `${documenti.length}/5 documenti`;
            badge.textContent = documenti.length;
            
            // Disabilita upload se max raggiunto
            if (documenti.length >= 5) {
                document.getElementById('documentoInput').disabled = true;
            }
            
            if (documenti.length === 0) {
                list.innerHTML = `
                    <div class="text-center py-8 bg-slate-50 rounded-xl border border-dashed border-slate-300">
                        <svg class="w-12 h-12 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-slate-400 text-sm">Nessun documento caricato</p>
                    </div>
                `;
                return;
            }
            
            list.innerHTML = documenti.map(d => `
                <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-xl group">
                    <div class="w-12 h-12 bg-red-100 text-red-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-800 truncate">${d.filename}</p>
                        <p class="text-sm text-slate-500">${(d.file_size / 1024).toFixed(1)} KB • ${new Date(d.uploaded_at).toLocaleDateString('it-IT')}</p>
                        ${d.note ? `<p class="text-sm text-slate-600 mt-1 italic">"${d.note}"</p>` : ''}
                    </div>
                    <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <a href="assets/uploads/${d.file_path}" target="_blank" 
                           class="p-2 text-cyan-600 hover:bg-cyan-50 rounded-lg" title="Visualizza">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </a>
                        <button onclick="deleteDocumento(${d.id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Elimina">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Errore caricamento documenti:', error);
    }
}

async function uploadDocumento() {
    const form = document.getElementById('documentoForm');
    const formData = new FormData(form);
    
    if (!formData.get('documento') || formData.get('documento').size === 0) {
        showToast('Seleziona un file PDF o ZIP', 'error');
        return;
    }
    
    try {
        const response = await fetch('api/progetti.php?action=upload_documento', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Documento caricato con successo', 'success');
            form.reset();
            document.getElementById('documentoPreview').classList.add('hidden');
            loadDocumenti();
        } else {
            showToast(data.message || 'Errore upload', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

async function deleteDocumento(docId) {
    confirmAction('Eliminare questo documento?', async () => {
        try {
            const response = await fetch('api/progetti.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_documento&id=${docId}&progetto_id=${progettoId}`
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Documento eliminato', 'success');
                loadDocumenti();
            } else {
                showToast(data.message || 'Errore eliminazione', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione', 'error');
        }
    });
}

// =====================================================
// GESTIONE COMMENTI TASK
// =====================================================

/**
 * Carica i commenti per una specifica task e aggiorna il DOM
 */
async function loadCommentiForTask(taskId) {
    const container = document.getElementById(`commenti-container-${taskId}`);
    if (!container) return;
    
    try {
        const response = await fetch(`api/task.php?action=list_commenti&id=${encodeURIComponent(taskId)}`);
        const data = await response.json();
        
        if (!data.success || !data.data || data.data.length === 0) {
            container.innerHTML = ''; // Nessun commento
            return;
        }
        
        const currentUserId = '<?php echo $_SESSION['user_id']; ?>';
        
        container.innerHTML = data.data.map(c => {
            const isAutore = c.utente_id === currentUserId;
            const avatarHtml = c.utente_avatar 
                ? `<img src="assets/uploads/avatars/${c.utente_avatar}" alt="${c.utente_nome}">`
                : `<div style="width:100%;height:100%;background:${c.utente_colore};display:flex;align-items:center;justify-content:center;color:white;font-size:10px;font-weight:bold;">${c.utente_nome.charAt(0)}</div>`;
            
            return `
                <div class="task-commento" id="commento-${c.id}">
                    <div class="commento-autore" title="${c.utente_nome}">
                        ${avatarHtml}
                    </div>
                    <span class="commento-text">${escapeHtml(c.commento)}</span>
                    ${isAutore ? `<button class="btn-elimina-commento" onclick="eliminaCommento(${c.id}, '${taskId}')" title="Elimina commento">×</button>` : ''}
                </div>
            `;
        }).join('');
        
    } catch (error) {
        console.error('Errore caricamento commenti:', error);
        container.innerHTML = '';
    }
}

/**
 * Aggiunge un nuovo commento a una task
 */
async function aggiungiCommento(taskId) {
    const input = document.getElementById(`commento-input-${taskId}`);
    const commento = input.value.trim();
    
    if (!commento) return;
    
    try {
        const response = await fetch('api/task.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=add_commento&task_id=${encodeURIComponent(taskId)}&commento=${encodeURIComponent(commento)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            input.value = '';
            // Ricarica i commenti
            await loadCommentiForTask(taskId);
            showToast('Commento aggiunto', 'success');
        } else {
            showToast(data.message || 'Errore aggiunta commento', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

/**
 * Elimina un commento
 */
async function eliminaCommento(commentoId, taskId) {
    if (!confirm('Eliminare questo commento?')) return;
    
    try {
        const response = await fetch('api/task.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=delete_commento&commento_id=${encodeURIComponent(commentoId)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Rimuovi il commento dal DOM
            const commentoEl = document.getElementById(`commento-${commentoId}`);
            if (commentoEl) commentoEl.remove();
            showToast('Commento eliminato', 'success');
        } else {
            showToast(data.message || 'Errore eliminazione', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

/**
 * Utility per escape HTML (previene XSS)
 */
// ==================== CHECKLIST CONTROLLO ====================

const CHECKLIST_TEMPLATES = {
    'Sito Web': [
        { id: 'sw_analisi', label: 'Analisi requisiti e briefing completato' },
        { id: 'sw_wireframe', label: 'Wireframe e mockup approvati' },
        { id: 'sw_design_desktop', label: 'Design desktop completato' },
        { id: 'sw_design_mobile', label: 'Design mobile/responsive completato' },
        { id: 'sw_font', label: 'Font e tipografie implementati correttamente' },
        { id: 'sw_colori', label: 'Palette colori rispettata' },
        { id: 'sw_html', label: 'Struttura HTML semantica valida' },
        { id: 'sw_css', label: 'CSS ottimizzato e responsive' },
        { id: 'sw_js', label: 'JavaScript funzionante e ottimizzato' },
        { id: 'sw_seo_meta', label: 'Meta tag SEO inseriti (title, description, Open Graph)' },
        { id: 'sw_seo_heading', label: 'Heading hierarchy corretta (H1, H2, H3...)' },
        { id: 'sw_seo_alt', label: 'Alt text immagini compilati' },
        { id: 'sw_ssl', label: 'Certificato SSL installato (HTTPS)' },
        { id: 'sw_cookie', label: 'Banner cookie GDPR compliant' },
        { id: 'sw_privacy', label: 'Pagina Privacy Policy presente' },
        { id: 'sw_form', label: 'Form contatto testati e funzionanti' },
        { id: 'sw_mail', label: 'Configurazione email corretta' },
        { id: 'sw_velocita', label: 'Test velocità (PageSpeed > 70)' },
        { id: 'sw_responsive', label: 'Test su vari dispositivi e browser' },
        { id: 'sw_backup', label: 'Sistema backup configurato' },
        { id: 'sw_security', label: 'Security headers e protezioni attive' },
        { id: 'sw_404', label: 'Pagina 404 personalizzata' },
        { id: 'sw_sitemap', label: 'Sitemap XML generata e inviata' },
        { id: 'sw_robots', label: 'File robots.txt configurato' },
        { id: 'sw_analytics', label: 'Google Analytics/Tag Manager installato' },
        { id: 'sw_accessibilita', label: 'Test accessibilità WCAG base' },
        { id: 'sw_contenuti', label: 'Contenuti inseriti e formattati' },
        { id: 'sw_link', label: 'Link interni ed esterni verificati' },
        { id: 'sw_favicon', label: 'Favicon e icona PWA' },
        { id: 'sw_lancio', label: 'Check pre-lancio finale completato' }
    ],
    'Grafica': [
        { id: 'gr_briefing', label: 'Briefing cliente completato' },
        { id: 'gr_concorrenza', label: 'Analisi concorrenza effettuata' },
        { id: 'gr_moodboard', label: 'Moodboard approvata' },
        { id: 'gr_colori', label: 'Palette colori definita' },
        { id: 'gr_font', label: 'Font e tipografie scelti' },
        { id: 'gr_concept', label: 'Concept iniziale presentato' },
        { id: 'gr_revisioni', label: 'Revisioni incorporate' },
        { id: 'gr_fidelity', label: 'Alta fedeltà completata' },
        { id: 'gr_formati', label: 'Tutti i formati richiesti prodotti' },
        { id: 'gr_stampa', label: 'File pronti per stampa (PDF, bleed, crop marks)' },
        { id: 'gr_digital', label: 'Versioni digitali ottimizzate' },
        { id: 'gr_font_licenze', label: 'Licenze font verificate' },
        { id: 'gr_immagini_licenze', label: 'Licenze immagini stock verificate' },
        { id: 'gr_vector', label: 'File vettoriali corretti' },
        { id: 'gr_risoluzione', label: 'Risoluzione immagini appropriata' },
        { id: 'gr_cmky', label: 'Colori CMYK per stampa' },
        { id: 'gr_rgb', label: 'Colori RGB per digitale' },
        { id: 'gr_consegna', label: 'File consegnati organizzati' }
    ],
    'Video': [
        { id: 'vd_briefing', label: 'Briefing e storyboard approvati' },
        { id: 'vd_sceneggiatura', label: 'Sceneggiatura/script definitivo' },
        { id: 'vd_location', label: 'Location e permessi gestiti' },
        { id: 'vd_cast', label: 'Cast/attori confermati' },
        { id: 'vd_attrezzatura', label: 'Attrezzatura pronta' },
        { id: 'vd_riprese', label: 'Riprese completate' },
        { id: 'vd_audio', label: 'Audio registrato di qualità' },
        { id: 'vd_backup', label: 'Backup footage sicuro' },
        { id: 'vd_selezione', label: 'Selezione clip migliori' },
        { id: 'vd_montaggio', label: 'Montaggio rough cut' },
        { id: 'vd_color', label: 'Color correction e grading' },
        { id: 'vd_audio_edit', label: 'Editing audio e mix' },
        { id: 'vd_music', label: 'Musica e sound design' },
        { id: 'vd_titoli', label: 'Titoli e grafiche inseriti' },
        { id: 'vd_effetti', label: 'VFX e effetti applicati' },
        { id: 'vd_transizioni', label: 'Transizioni fluide' },
        { id: 'vd_format', label: 'Formato di output corretto' },
        { id: 'vd_sottotitoli', label: 'Sottotitoli inseriti (se richiesto)' },
        { id: 'vd_logo', label: 'Logo cliente in apertura/chiusura' },
        { id: 'vd_revisione', label: 'Revisione cliente e modifiche' },
        { id: 'vd_export', label: 'Export finale alta qualità' },
        { id: 'vd_versioni', label: 'Versioni multiple (full, teaser, social)' }
    ],
    'Social Media': [
        { id: 'sm_strategy', label: 'Strategia contenuti definita' },
        { id: 'sm_calendario', label: 'Editorial calendar pianificato' },
        { id: 'sm_target', label: 'Target audience identificato' },
        { id: 'sm_tono', label: 'Tono di voce definito' },
        { id: 'sm_grafica', label: 'Grafiche social create' },
        { id: 'sm_copy', label: 'Copy post scritti' },
        { id: 'sm_hashtag', label: 'Hashtag research effettuata' },
        { id: 'sm_storie', label: 'Storie Instagram preparate' },
        { id: 'sm_reels', label: 'Reels/video short creati' },
        { id: 'sm_programmazione', label: 'Post programmati' },
        { id: 'sm_bio', label: 'Bio e link ottimizzati' },
        { id: 'sm_highlight', label: 'Highlight covers create' },
        { id: 'sm_risposte', label: 'Template risposte veloci' },
        { id: 'sm_analisi', label: 'Analisi competitor effettuata' },
        { id: 'sm_reporting', label: 'Sistema reporting configurato' },
        { id: 'sm_ads', label: 'Campagne ads pianificate (se previste)' }
    ],
    'Branding': [
        { id: 'br_workshop', label: 'Workshop brand completato' },
        { id: 'br_valori', label: 'Valori e mission definiti' },
        { id: 'br_posizionamento', label: 'Posizionamento strategico' },
        { id: 'br_personality', label: 'Personalità brand definita' },
        { id: 'br_nome', label: 'Naming (se richiesto)' },
        { id: 'br_payoff', label: 'Payoff/slogan creato' },
        { id: 'br_logo', label: 'Logo primario e varianti' },
        { id: 'br_colori', label: 'Palette colori brand' },
        { id: 'br_font', label: 'Font aziendali' },
        { id: 'br_pattern', label: 'Pattern/texture brand' },
        { id: 'br_fotografia', label: 'Stile fotografico' },
        { id: 'br_illustrazione', label: 'Stile illustrazione' },
        { id: 'br_iconografia', label: 'Sistema iconografico' },
        { id: 'br_applicazioni', label: 'Mockup applicazioni' },
        { id: 'br_stationery', label: 'Stationery aziendale' },
        { id: 'br_guidelines', label: 'Brand guidelines complete' },
        { id: 'br_file', label: 'File sorgenti consegnati' }
    ],
    'SEO': [
        { id: 'seo_audit', label: 'SEO audit iniziale' },
        { id: 'seo_keyword', label: 'Keyword research completa' },
        { id: 'seo_concorrenza', label: 'Analisi concorrenza SERP' },
        { id: 'seo_tecnica', label: 'Ottimizzazione tecnica' },
        { id: 'seo_velocita', label: 'Ottimizzazione velocità' },
        { id: 'seo_mobile', label: 'Mobile optimization' },
        { id: 'seo_struttura', label: 'Struttura URL ottimizzata' },
        { id: 'seo_interna', label: 'Link building interna' },
        { id: 'seo_title', label: 'Title tags ottimizzati' },
        { id: 'seo_meta', label: 'Meta descriptions' },
        { id: 'seo_heading', label: 'Heading optimization' },
        { id: 'seo_content', label: 'Content optimization' },
        { id: 'seo_immagini', label: 'Ottimizzazione immagini' },
        { id: 'seo_schema', label: 'Schema markup implementato' },
        { id: 'seo_local', label: 'SEO locale (GMB, NAP)' },
        { id: 'seo_backlink', label: 'Link building esterna' },
        { id: 'seo_analytics', label: 'Analytics e Search Console' },
        { id: 'seo_report', label: 'Reporting mensile' }
    ],
    'Fotografia': [
        { id: 'ft_briefing', label: 'Briefing fotografico' },
        { id: 'ft_moodboard', label: 'Moodboard riferimenti' },
        { id: 'ft_location', label: 'Location scout' },
        { id: 'ft_attrezzatura', label: 'Attrezzatura preparata' },
        { id: 'ft_luci', label: 'Setup luci' },
        { id: 'ft_scatti', label: 'Scatti effettuati' },
        { id: 'ft_backup', label: 'Backup file RAW' },
        { id: 'ft_selezione', label: 'Selezione best shots' },
        { id: 'ft_sviluppo', label: 'Sviluppo RAW' },
        { id: 'ft_color', label: 'Color grading' },
        { id: 'ft_ritocco', label: 'Ritocco avanzato' },
        { id: 'ft_export', label: 'Export formati diversi' },
        { id: 'ft_consegna', label: 'Galleria consegna' }
    ],
    'Altro': [
        { id: 'alt_briefing', label: 'Briefing dettagliato' },
        { id: 'alt_proposta', label: 'Proposta approvata' },
        { id: 'alt_contratto', label: 'Contratto firmato' },
        { id: 'alt_lavoro', label: 'Lavoro eseguito' },
        { id: 'alt_revisioni', label: 'Revisioni incorporate' },
        { id: 'alt_consegna', label: 'Consegna finale' },
        { id: 'alt_fattura', label: 'Fatturazione' }
    ]
};

let currentTipologia = null;
let checklistsData = {};

async function loadChecklists() {
    try {
        const response = await fetch(`api/checklist_controllo.php?progetto_id=${progettoId}`);
        const data = await response.json();
        
        if (data.success) {
            checklistsData = data.data || {};
            
            // Mostra ultimo salvataggio se presente
            let lastSave = null;
            Object.values(checklistsData).forEach(c => {
                if (c.ultimo_salvataggio && (!lastSave || c.ultimo_salvataggio > lastSave)) {
                    lastSave = c.ultimo_salvataggio;
                }
            });
            
            if (lastSave) {
                const saveEl = document.getElementById('lastSaveControllo');
                saveEl.classList.remove('hidden');
                saveEl.querySelector('span').textContent = formatDateTime(lastSave);
            }
        }
    } catch (e) {
        console.error('Errore caricamento checklist:', e);
    }
}

function showChecklist(tipologia) {
    currentTipologia = tipologia;
    
    // Aggiorna bottoni
    document.querySelectorAll('[id^="btn-checklist-"]').forEach(btn => {
        btn.classList.remove('bg-cyan-50', 'border-cyan-500', 'text-cyan-700');
        btn.classList.add('bg-white', 'border-slate-200');
    });
    const activeBtn = document.getElementById('btn-checklist-' + tipologia.replace(' ', '_'));
    if (activeBtn) {
        activeBtn.classList.remove('bg-white', 'border-slate-200');
        activeBtn.classList.add('bg-cyan-50', 'border-cyan-500', 'text-cyan-700');
    }
    
    // Mostra container
    document.getElementById('checklistEmptyState').classList.add('hidden');
    document.getElementById('checklistContainer').classList.remove('hidden');
    
    // Mostra/nascondi campo linguaggio per Sito Web
    const linguaggioDiv = document.getElementById('sitoWebLinguaggio');
    if (tipologia === 'Sito Web') {
        linguaggioDiv.classList.remove('hidden');
        const saved = checklistsData[tipologia];
        document.getElementById('linguaggioSito').value = saved?.linguaggio_sito || '';
    } else {
        linguaggioDiv.classList.add('hidden');
    }
    
    // Genera checklist
    const container = document.getElementById('checklistItems');
    container.innerHTML = '';
    
    const items = CHECKLIST_TEMPLATES[tipologia] || [];
    const saved = checklistsData[tipologia]?.checklist || {};
    
    items.forEach((item, index) => {
        const isChecked = saved[item.id]?.checked || false;
        const commento = saved[item.id]?.commento || '';
        
        const div = document.createElement('div');
        div.className = 'bg-white border border-slate-200 rounded-xl p-3 sm:p-4 hover:border-cyan-300 transition-colors';
        div.innerHTML = `
            <div class="flex items-start gap-3">
                <input type="checkbox" id="check_${item.id}" 
                       ${isChecked ? 'checked' : ''}
                       class="w-5 h-5 mt-0.5 text-cyan-600 border-slate-300 rounded focus:ring-cyan-500 cursor-pointer flex-shrink-0">
                <div class="flex-1 min-w-0">
                    <label for="check_${item.id}" class="text-sm font-medium text-slate-700 cursor-pointer select-none block leading-relaxed">
                        ${item.label}
                    </label>
                    <input type="text" id="comment_${item.id}" 
                           value="${escapeHtml(commento)}"
                           placeholder="Aggiungi un commento..."
                           class="mt-2 w-full text-base px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none">
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

async function salvaChecklist() {
    if (!currentTipologia) return;
    
    const items = CHECKLIST_TEMPLATES[currentTipologia] || [];
    const checklist = {};
    
    items.forEach(item => {
        const checkbox = document.getElementById(`check_${item.id}`);
        const comment = document.getElementById(`comment_${item.id}`);
        checklist[item.id] = {
            checked: checkbox?.checked || false,
            commento: comment?.value || ''
        };
    });
    
    const linguaggioSito = currentTipologia === 'Sito Web' 
        ? document.getElementById('linguaggioSito').value 
        : '';
    
    try {
        const response = await fetch('api/checklist_controllo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                progetto_id: progettoId,
                tipologia: currentTipologia,
                checklist: checklist,
                linguaggio_sito: linguaggioSito
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Aggiorna dati locali
            checklistsData[currentTipologia] = {
                checklist: checklist,
                linguaggio_sito: linguaggioSito,
                ultimo_salvataggio: data.ultimo_salvataggio
            };
            
            // Mostra conferma
            const statusEl = document.getElementById('saveStatusControllo');
            statusEl.textContent = `Salvato alle ${formatTime(data.ultimo_salvataggio)}`;
            statusEl.className = 'text-sm text-emerald-600 font-medium';
            statusEl.classList.remove('hidden');
            
            // Aggiorna ultimo salvataggio globale
            const saveEl = document.getElementById('lastSaveControllo');
            saveEl.classList.remove('hidden');
            saveEl.querySelector('span').textContent = formatDateTime(data.ultimo_salvataggio);
            
            setTimeout(() => {
                statusEl.classList.add('hidden');
            }, 3000);
            
            showToast('Checklist salvata', 'success');
        } else {
            throw new Error(data.error || 'Errore salvataggio');
        }
    } catch (e) {
        console.error('Errore:', e);
        const statusEl = document.getElementById('saveStatusControllo');
        statusEl.textContent = 'Errore salvataggio';
        statusEl.className = 'text-sm text-red-600 font-medium';
        statusEl.classList.remove('hidden');
        showToast('Errore salvataggio', 'error');
    }
}

function formatDateTime(datetime) {
    const d = new Date(datetime);
    return d.toLocaleString('it-IT', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit', 
        minute: '2-digit' 
    });
}

function formatTime(datetime) {
    const d = new Date(datetime);
    return d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Carica documenti quando si apre la tab
document.addEventListener('DOMContentLoaded', function() {
    // Carica documenti all'avvio se la tab è attiva
    if (!document.getElementById('content-documenti').classList.contains('hidden')) {
        loadDocumenti();
    }
    
    // Carica checklist quando si apre la tab controllo
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (!document.getElementById('content-controllo').classList.contains('hidden')) {
                loadChecklists();
            }
        });
    });
    observer.observe(document.getElementById('content-controllo'), { attributes: true, attributeFilter: ['class'] });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
