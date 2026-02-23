<?php
/**
 * Eterea Gestionale
 * Dashboard
 */

// Debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/auth_check.php';
} catch (Throwable $e) {
    die('Errore: ' . $e->getMessage());
}

$pageTitle = 'Dashboard';

// Ottieni statistiche
$stats = getDashboardStats($_SESSION['user_id']);

// Ottieni tutti gli utenti per riferimento
$users = USERS;

include __DIR__ . '/includes/header.php';
?>

<!-- Resoconto Progetti e Cassa (Accordion) -->
<div class="mb-8">
    <button onclick="toggleResoconto()" class="w-full flex items-center justify-between p-4 bg-white rounded-2xl shadow-sm border border-slate-200 hover:shadow-md transition-shadow">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span class="font-semibold text-slate-800">Resoconto Progetti e Cassa</span>
        </div>
        <svg id="resocontoIcon" class="w-5 h-5 text-slate-400 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    
    <div id="resocontoContent" class="hidden mt-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Cassa Aziendale -->
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-emerald-100 text-sm font-medium mb-1">Cassa Aziendale</p>
                        <h3 class="text-3xl font-bold"><?php echo formatCurrency($stats['cassa_aziendale']); ?></h3>
                    </div>
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-emerald-100">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 8.586 15.293 4.293A1 1 0 0115 4.293V7z" clip-rule="evenodd"/>
                    </svg>
                    Totale accumulato
                </div>
            </div>
            
            <!-- Miei Crediti -->
            <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-cyan-100 text-sm font-medium mb-1">I Miei Crediti</p>
                        <h3 class="text-3xl font-bold"><?php echo formatCurrency($stats['miei_crediti']); ?></h3>
                    </div>
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-cyan-100">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                    </svg>
                    Dal tuo profit sharing
                </div>
            </div>
            
            <!-- Progetti Attivi -->
            <div class="bg-gradient-to-br from-slate-600 to-slate-700 rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-slate-300 text-sm font-medium mb-1">Progetti Attivi</p>
                        <h3 class="text-3xl font-bold"><?php echo $stats['progetti_attivi']; ?></h3>
                    </div>
                    <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-slate-300">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    In cui sei coinvolto
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleResoconto() {
    const content = document.getElementById('resocontoContent');
    const icon = document.getElementById('resocontoIcon');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}
</script>

<?php
// Recupera dati per il Team Dashboard
$teamMembers = [
    ['id' => 'ucwurog3xr8tf', 'nome' => 'Lorenzo', 'cognome' => 'Puccetti', 'colore' => '#0891B2'],
    ['id' => 'ukl9ipuolsebn', 'nome' => 'Daniele', 'cognome' => 'Giuliani', 'colore' => '#10B981'],
    ['id' => 'u3ghz4f2lnpkx', 'nome' => 'Edmir', 'cognome' => 'Likaj', 'colore' => '#F59E0B']
];

// Recupera avatar per ogni membro
foreach ($teamMembers as &$member) {
    try {
        $stmt = $pdo->prepare("SELECT avatar FROM utenti WHERE id = ?");
        $stmt->execute([$member['id']]);
        $member['avatar'] = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $member['avatar'] = null;
    }
}

foreach ($teamMembers as &$member) {
    // Progetti assegnati (dove il membro è nei partecipanti)
    try {
        $stmt = $pdo->prepare("
            SELECT p.id, p.titolo, p.data_consegna_prevista, p.colore_tag, c.ragione_sociale as cliente
            FROM progetti p
            LEFT JOIN clienti c ON p.cliente_id = c.id
            WHERE p.stato_progetto NOT IN ('archiviato', 'annullato', 'consegnato')
            AND JSON_CONTAINS(p.partecipanti, JSON_QUOTE(?))
            ORDER BY p.data_consegna_prevista ASC
        ");
        $stmt->execute([$member['id']]);
        $member['progetti'] = $stmt->fetchAll();
    } catch (PDOException $e) {
        $member['progetti'] = [];
    }
    
    // Task assegnate (non completate) - cerca in assegnati JSON
    try {
        $stmt = $pdo->prepare("
            SELECT t.id, t.titolo, t.scadenza, t.priorita, t.progetto_id, p.titolo as progetto_titolo
            FROM task t
            JOIN progetti p ON t.progetto_id = p.id
            WHERE (t.assegnato_a = ? OR JSON_CONTAINS(t.assegnati, JSON_QUOTE(?)))
            AND t.stato != 'completato'
            AND p.stato_progetto NOT IN ('archiviato', 'annullato')
            ORDER BY t.scadenza ASC
        ");
        $stmt->execute([$member['id'], $member['id']]);
        $member['task'] = $stmt->fetchAll();
    } catch (PDOException $e) {
        $member['task'] = [];
    }
}
unset($member);
?>

<!-- Row Team: Dashboard Membri -->
<div class="mb-8">
    <h2 class="text-base sm:text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        Team - Progetti e Task
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php foreach ($teamMembers as $member): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Header Card -->
            <div class="p-4 border-b border-slate-100" style="background: linear-gradient(135deg, <?php echo $member['colore']; ?>15 0%, <?php echo $member['colore']; ?>05 100%);">
                <div class="flex items-center gap-3">
                    <?php if (!empty($member['avatar']) && file_exists(__DIR__ . '/assets/uploads/avatars/' . $member['avatar'])): ?>
                        <img src="assets/uploads/avatars/<?php echo e($member['avatar']); ?>" 
                             alt="<?php echo e($member['nome']); ?>" 
                             class="w-12 h-12 rounded-xl object-cover border-2 border-white shadow-sm">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-lg" style="background-color: <?php echo $member['colore']; ?>">
                            <?php echo substr($member['nome'], 0, 1); ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-800"><?php echo $member['nome'] . ' ' . $member['cognome']; ?></h3>
                        <p class="text-xs text-slate-500"><?php echo count($member['progetti']); ?> progetti attivi</p>
                    </div>
                </div>
            </div>
            
            <!-- Progetti -->
            <div class="p-4">
                <?php if (!empty($member['progetti'])): ?>
                    <h4 class="text-xs sm:text-sm font-semibold text-slate-400 uppercase tracking-wider mb-3">Progetti (<?php echo count($member['progetti']); ?>)</h4>
                    <div class="space-y-2 mb-4 max-h-64 overflow-y-auto pr-1 custom-scrollbar">
                        <?php foreach ($member['progetti'] as $progetto): 
                            $scadenza = $progetto['data_consegna_prevista'] ? date('d/m', strtotime($progetto['data_consegna_prevista'])) : 'N/D';
                            $isScaduto = $progetto['data_consegna_prevista'] && strtotime($progetto['data_consegna_prevista']) < strtotime('today');
                            $coloreProgetto = $progetto['colore_tag'] ?? '#F8FAFC';
                            $isDefaultColor = $coloreProgetto === '#FFFFFF' || $coloreProgetto === '#F8FAFC';
                        ?>
                        <a href="progetto_dettaglio.php?id=<?php echo $progetto['id']; ?>" class="block p-3 rounded-lg hover:brightness-95 transition-all group border border-slate-100" 
                           style="background-color: <?php echo $coloreProgetto; ?>;">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-slate-700 group-hover:text-slate-900 truncate"><?php echo e($progetto['titolo']); ?></p>
                                    <p class="text-xs text-slate-500 mt-1"><?php echo e($progetto['cliente'] ?? 'Cliente non specificato'); ?></p>
                                </div>
                                <span class="text-xs font-medium px-2 py-1 rounded <?php echo $isScaduto ? 'bg-red-100 text-red-600' : 'bg-white/70 text-slate-600'; ?>">
                                    <?php echo $scadenza; ?>
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Task -->
                <?php if (!empty($member['task'])): ?>
                    <h4 class="text-xs sm:text-sm font-semibold text-slate-400 uppercase tracking-wider mb-3">Task in corso (<?php echo count($member['task']); ?>)</h4>
                    <div class="space-y-2 max-h-64 overflow-y-auto pr-1 custom-scrollbar">
                        <?php foreach ($member['task'] as $task):
                            $prioritaColor = [
                                'alta' => 'bg-red-100 text-red-700',
                                'media' => 'bg-yellow-100 text-yellow-700',
                                'bassa' => 'bg-blue-100 text-blue-700'
                            ][$task['priorita']] ?? 'bg-slate-100 text-slate-700';
                            $scadenzaTask = $task['scadenza'] ? date('d/m', strtotime($task['scadenza'])) : '';
                        ?>
                        <a href="progetto_dettaglio.php?id=<?php echo $task['progetto_id']; ?>" class="block p-2 rounded-lg hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: <?php echo $member['colore']; ?>"></span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-slate-700 truncate"><?php echo e($task['titolo']); ?></p>
                                    <p class="text-xs text-slate-400"><?php echo e($task['progetto_titolo']); ?></p>
                                </div>
                                <?php if ($scadenzaTask): ?>
                                <span class="text-xs text-slate-400"><?php echo $scadenzaTask; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <?php if (empty($member['progetti'])): ?>
                    <div class="text-center py-4">
                        <p class="text-sm text-slate-400">Nessun progetto o task assegnato</p>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Row 2: Task e Calendario -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
    <!-- Colonna Sinistra: Task -->
    <div class="space-y-6">
        <!-- Task di Oggi -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-semibold text-slate-800">Task di Oggi</h3>
                        <p class="text-sm text-slate-500"><?php echo date('d F Y'); ?></p>
                    </div>
                </div>
                <span class="bg-orange-100 text-orange-700 text-xs font-medium px-2.5 py-1 rounded-full">
                    <?php echo count($stats['task_oggi']); ?> da fare
                </span>
            </div>
            
            <div class="divide-y divide-slate-100">
                <?php if (empty($stats['task_oggi'])): ?>
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <p class="text-sm text-slate-500">Nessuna task per oggi!</p>
                    <p class="text-xs text-slate-400 mt-1">Goditi la giornata</p>
                </div>
                <?php else: ?>
                    <?php foreach ($stats['task_oggi'] as $task): 
                        $prioritaColor = COLORI_PRIORITA[$task['priorita']] ?? 'gray';
                    ?>
                    <div class="p-4 hover:bg-slate-50 transition-colors flex items-start gap-4">
                        <button onclick="toggleTaskStatus('<?php echo $task['id']; ?>')" 
                                class="flex-shrink-0 w-6 h-6 rounded-full border-2 border-slate-300 hover:border-cyan-500 hover:bg-cyan-50 transition-colors mt-0.5">
                        </button>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-medium text-slate-800 truncate"><?php echo e($task['titolo']); ?></p>
                                <span class="flex-shrink-0 w-2 h-2 rounded-full bg-<?php echo $prioritaColor; ?>-500 mt-2"></span>
                            </div>
                            <p class="text-sm text-slate-500 mt-1">
                                <a href="progetto_dettaglio.php?id=<?php echo $task['progetto_id']; ?>" class="hover:text-cyan-600">
                                    <?php echo e($task['progetto_titolo']); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Prossime Scadenze -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-semibold text-slate-800">Prossime Scadenze</h3>
                        <p class="text-sm text-slate-500">Progetti - Prossimi 7 giorni</p>
                    </div>
                </div>
            </div>
            
            <div class="divide-y divide-slate-100">
                <?php if (empty($stats['prossime_scadenze'])): ?>
                <div class="p-6 text-center text-slate-500">
                    <p>Nessuna scadenza imminente</p>
                </div>
                <?php else: ?>
                    <?php foreach ($stats['prossime_scadenze'] as $progetto): 
                        $scadenza = checkScadenza($progetto['data_consegna_prevista']);
                        $badgeClass = $scadenza === 'scaduto' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700';
                    ?>
                    <div class="p-4 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-slate-800"><?php echo e($progetto['titolo']); ?></p>
                                <p class="text-sm text-slate-500"><?php echo e($progetto['cliente_nome'] ?? 'Cliente non specificato'); ?></p>
                            </div>
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full <?php echo $badgeClass; ?>">
                                <?php echo formatDate($progetto['data_consegna_prevista']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Colonna Destra: Calendario Mini -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-cyan-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800">Prossimi Appuntamenti</h3>
                    <p class="text-sm text-slate-500">Calendario</p>
                </div>
            </div>
            <a href="calendario.php" class="text-sm text-cyan-600 hover:text-cyan-700 font-medium">
                Vedi tutti
            </a>
        </div>
        
        <div class="p-4">
            <!-- Calendario semplificato -->
            <div id="miniCalendar" class="grid grid-cols-7 gap-1 text-center text-sm">
                <!-- Generato via JS -->
            </div>
            
            <!-- Lista eventi del giorno selezionato -->
            <div class="mt-4">
                <div class="flex items-center justify-between mb-3">
                    <div id="selectedDayTitle" class="text-sm font-medium text-slate-600">
                        Prossimi appuntamenti
                    </div>
                    <button id="backToUpcoming" onclick="resetToUpcoming()" class="hidden text-xs text-cyan-600 hover:text-cyan-700 font-medium">
                        ← Torna ai prossimi
                    </button>
                </div>
                <div class="space-y-3" id="upcomingEvents">
                    <p class="text-center text-slate-400 text-sm py-4">Caricamento...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dettaglio Appuntamento -->
<div id="eventDetailModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('eventDetailModal')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-800">Dettagli Appuntamento</h3>
                <button onclick="closeModal('eventDetailModal')" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div id="eventDetailContent" class="p-5 space-y-4">
                <!-- Popolato via JS -->
            </div>
            
            <div class="p-5 border-t border-slate-100 flex justify-between gap-3">
                <button type="button" onclick="deleteEventFromDashboard()" class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg font-medium flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Elimina
                </button>
                <button type="button" onclick="closeModal('eventDetailModal')" class="px-6 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium">
                    Chiudi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Timeline -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-5 border-b border-slate-100">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-slate-800">Attività Recenti</h3>
                <p class="text-sm text-slate-500">Ultime 10 azioni nel sistema</p>
            </div>
        </div>
    </div>
    
    <div class="p-6">
        <div class="relative">
            <!-- Timeline line -->
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-slate-200"></div>
            
            <!-- Timeline items -->
            <div class="space-y-6">
                <?php foreach ($stats['timeline'] as $item): 
                    $icon = match($item['azione']) {
                        'creato_progetto', 'creato_task', 'creato_cliente' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
                        'completato_task' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
                        'upload_file' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>',
                        'login' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>',
                        default => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                    };
                    
                    $color = match($item['azione']) {
                        'creato_progetto' => 'bg-blue-500',
                        'creato_task' => 'bg-cyan-500',
                        'creato_cliente' => 'bg-emerald-500',
                        'completato_task' => 'bg-green-500',
                        'upload_file' => 'bg-purple-500',
                        'login' => 'bg-slate-500',
                        default => 'bg-slate-400'
                    };
                ?>
                <div class="relative flex items-start gap-4">
                    <div class="relative z-10 w-8 h-8 rounded-full <?php echo $color; ?> text-white flex items-center justify-center flex-shrink-0">
                        <?php echo $icon; ?>
                    </div>
                    <div class="flex-1 min-w-0 pt-1">
                        <p class="text-sm text-slate-800">
                            <span class="font-medium"><?php echo e($item['utente_nome'] ?? 'Sistema'); ?></span>
                            <?php echo e($item['dettagli']); ?>
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            <?php echo formatDateTime($item['timestamp'], 'd M Y H:i'); ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Variabile globale per eventi del mese
let monthEventsData = {};

// Genera calendario mini
document.addEventListener('DOMContentLoaded', function() {
    loadMiniCalendar();
    loadUpcomingEvents();
});

async function loadMiniCalendar() {
    const calendarEl = document.getElementById('miniCalendar');
    const today = new Date();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();
    
    // Giorni della settimana
    const days = ['L', 'M', 'M', 'G', 'V', 'S', 'D'];
    days.forEach(day => {
        calendarEl.innerHTML += `<div class="text-xs font-medium text-slate-400 py-2">${day}</div>`;
    });
    
    // Primo giorno del mese
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const startOffset = firstDay === 0 ? 6 : firstDay - 1;
    
    // Giorni vuoti
    for (let i = 0; i < startOffset; i++) {
        calendarEl.innerHTML += '<div></div>';
    }
    
    // Carica eventi del mese
    const startDate = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-01`;
    const endDate = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${new Date(currentYear, currentMonth + 1, 0).getDate()}`;
    
    try {
        const response = await fetch(`api/calendario.php?action=events&start=${startDate}&end=${endDate}`);
        const data = await response.json();
        if (data.success && data.data) {
            // Raggruppa eventi per giorno
            data.data.forEach(event => {
                const eventDate = event.data_inizio.split(' ')[0]; // YYYY-MM-DD
                if (!monthEventsData[eventDate]) {
                    monthEventsData[eventDate] = [];
                }
                monthEventsData[eventDate].push(event);
            });
        }
    } catch (e) {
        console.error('Errore caricamento eventi calendario:', e);
    }
    
    // Giorni del mese
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    for (let i = 1; i <= daysInMonth; i++) {
        const isToday = i === today.getDate();
        const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
        const dayEvents = monthEventsData[dateStr] || [];
        
        // Genera puntini colorati (max 3)
        let dotsHtml = '';
        if (dayEvents.length > 0) {
            const visibleEvents = dayEvents.slice(0, 3);
            dotsHtml = `<div class="flex justify-center gap-0.5 mt-1">${visibleEvents.map(e => {
                const color = e.utente_colore || '#06B6D4'; // Default cyan
                return `<div class="w-1.5 h-1.5 rounded-full" style="background-color: ${color}"></div>`;
            }).join('')}</div>`;
        }
        
        const className = isToday 
            ? 'bg-cyan-500 text-white rounded-lg font-medium' 
            : 'text-slate-700 hover:bg-slate-100 rounded-lg';
        
        calendarEl.innerHTML += `
            <div class="${className} py-1 cursor-pointer flex flex-col items-center day-cell" data-date="${dateStr}" onclick="selectDay('${dateStr}')" title="Vedi appuntamenti">
                <span>${i}</span>
                ${dotsHtml}
            </div>`;
    }
}

// Seleziona un giorno e mostra i suoi eventi
function selectDay(dateStr) {
    // Rimuovi selezione precedente
    document.querySelectorAll('.day-cell').forEach(cell => {
        cell.classList.remove('bg-cyan-100', 'text-cyan-700');
    });
    
    // Aggiungi selezione al giorno cliccato
    const selectedCell = document.querySelector(`.day-cell[data-date="${dateStr}"]`);
    if (selectedCell) {
        selectedCell.classList.add('bg-cyan-100', 'text-cyan-700');
    }
    
    // Formatta data per il titolo
    const date = new Date(dateStr);
    const formattedDate = date.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long' });
    
    // Aggiorna titolo e mostra bottone "torna indietro"
    document.getElementById('selectedDayTitle').innerHTML = `Appuntamenti del <span class="text-cyan-600">${formattedDate}</span>`;
    document.getElementById('backToUpcoming').classList.remove('hidden');
    
    // Carica eventi del giorno
    const dayEvents = monthEventsData[dateStr] || [];
    const container = document.getElementById('upcomingEvents');
    
    if (dayEvents.length === 0) {
        container.innerHTML = '<p class="text-center text-slate-400 text-sm py-6">Nessun appuntamento questo giorno</p>';
        return;
    }
    
    // Ordina eventi per ora
    dayEvents.sort((a, b) => new Date(a.data_inizio) - new Date(b.data_inizio));
    
    container.innerHTML = dayEvents.map(event => {
        const eventDate = new Date(event.data_inizio);
        const timeStr = eventDate.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
        
        const iconColor = event.tipo === 'appuntamento' ? 'bg-cyan-100 text-cyan-600' : 
                         event.tipo === 'scadenza_task' ? 'bg-orange-100 text-orange-600' :
                         'bg-purple-100 text-purple-600';
        
        return `
            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer" onclick='showEventDetail(${JSON.stringify(event)})'>
                <div class="w-10 h-10 ${iconColor} rounded-lg flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-800 truncate">${event.titolo}</p>
                    <p class="text-xs text-slate-500">${timeStr !== '00:00' ? timeStr : 'Tutto il giorno'}</p>
                </div>
            </div>
        `;
    }).join('');
}

// Torna alla visualizzazione "Prossimi appuntamenti"
function resetToUpcoming() {
    // Rimuovi selezione
    document.querySelectorAll('.day-cell').forEach(cell => {
        cell.classList.remove('bg-cyan-100', 'text-cyan-700');
    });
    
    // Nascondi bottone torna indietro
    document.getElementById('backToUpcoming').classList.add('hidden');
    
    // Ricarica prossimi eventi
    loadUpcomingEvents();
}

async function loadUpcomingEvents() {
    const start = new Date().toISOString().split('T')[0];
    const end = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    try {
        const response = await fetch(`api/calendario.php?action=events&start=${start}&end=${end}`);
        const data = await response.json();
        
        const container = document.getElementById('upcomingEvents');
        
        if (!data.success || !data.data || data.data.length === 0) {
            container.innerHTML = '<p class="text-center text-slate-400 text-sm py-4">Nessun evento imminente</p>';
            return;
        }
        
        // Prendi i primi 5 eventi
        const events = data.data.slice(0, 5);
        
        container.innerHTML = events.map(event => {
            const date = new Date(event.data_inizio);
            const dateStr = date.toLocaleDateString('it-IT', { day: 'numeric', month: 'short' });
            const timeStr = date.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
            
            const iconColor = event.tipo === 'appuntamento' ? 'bg-cyan-100 text-cyan-600' : 
                             event.tipo === 'scadenza_task' ? 'bg-orange-100 text-orange-600' :
                             'bg-purple-100 text-purple-600';
            
            return `
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl hover:bg-slate-100 transition-colors cursor-pointer" onclick='showEventDetail(${JSON.stringify(event)})'>
                    <div class="w-10 h-10 ${iconColor} rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-800 truncate">${event.titolo}</p>
                        <p class="text-xs text-slate-500">${dateStr} ${timeStr !== '00:00' ? '- ' + timeStr : ''}</p>
                    </div>
                </div>
            `;
        }).join('');
        
    } catch (error) {
        document.getElementById('upcomingEvents').innerHTML = 
            '<p class="text-center text-slate-400 text-sm py-4">Errore caricamento eventi</p>';
    }
}

async function toggleTaskStatus(taskId) {
    try {
        const response = await fetch('api/task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=change_status&id=${taskId}&stato=completato`
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Errore:', error);
    }
}

let currentEventId = null;

function showEventDetail(event) {
    currentEventId = event.id;
    
    const date = new Date(event.data_inizio);
    const dateStr = date.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    const timeStr = date.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
    
    const tipoLabels = {
        'appuntamento': 'Appuntamento',
        'scadenza_task': 'Scadenza Task',
        'scadenza_progetto': 'Scadenza Progetto',
        'promemoria': 'Promemoria'
    };
    
    const tipoColors = {
        'appuntamento': 'bg-cyan-100 text-cyan-700',
        'scadenza_task': 'bg-orange-100 text-orange-700',
        'scadenza_progetto': 'bg-purple-100 text-purple-700',
        'promemoria': 'bg-emerald-100 text-emerald-700'
    };
    
    document.getElementById('eventDetailContent').innerHTML = `
        <div class="space-y-4">
            <div>
                <span class="inline-block px-3 py-1 rounded-full text-sm font-medium ${tipoColors[event.tipo] || 'bg-slate-100 text-slate-700'}">
                    ${tipoLabels[event.tipo] || event.tipo}
                </span>
            </div>
            
            <div>
                <h4 class="text-lg font-semibold text-slate-800">${event.titolo}</h4>
            </div>
            
            <div class="flex items-center gap-2 text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>${dateStr}</span>
            </div>
            
            <div class="flex items-center gap-2 text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>${timeStr}</span>
            </div>
            
            ${event.progetto_titolo ? `
            <div class="flex items-center gap-2 text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <span>Progetto: ${event.progetto_titolo}</span>
            </div>
            ` : ''}
            
            ${event.utente_nome ? `
            <div class="flex items-center gap-3">
                <span class="text-slate-500 text-sm">Assegnato a:</span>
                <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg">
                    ${event.utente_avatar ? 
                        `<div class="w-8 h-8 rounded-full overflow-hidden border-2" style="border-color: ${event.utente_colore || '#94A3B8'}"><img src="assets/uploads/avatars/${event.utente_avatar}" alt="${event.utente_nome}" class="w-full h-full object-cover"></div>` :
                        `<div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium" style="background-color: ${event.utente_colore || '#94A3B8'}">${event.utente_nome.charAt(0).toUpperCase()}</div>`
                    }
                    <span class="font-medium text-slate-700">${event.utente_nome}</span>
                </div>
            </div>
            ` : ''}
            
            ${event.partecipanti_list && event.partecipanti_list.length > 0 ? `
            <div class="flex items-center gap-3">
                <span class="text-slate-500 text-sm">Partecipanti:</span>
                <div class="flex items-center gap-2">
                    ${event.partecipanti_list.map(p => {
                        const avatarHtml = p.avatar ? 
                            `<div class="w-8 h-8 rounded-full overflow-hidden border-2" style="border-color: ${p.colore || '#94A3B8'}"><img src="assets/uploads/avatars/${p.avatar}" alt="${p.nome}" class="w-full h-full object-cover" title="${p.nome}"></div>` :
                            `<div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium" style="background-color: ${p.colore || '#94A3B8'}" title="${p.nome}">${p.nome.charAt(0).toUpperCase()}</div>`;
                        return avatarHtml;
                    }).join('')}
                </div>
            </div>
            ` : ''}
            
            ${event.note ? `
            <div class="bg-slate-50 p-3 rounded-lg">
                <p class="text-sm text-slate-600">${event.note}</p>
            </div>
            ` : ''}
        </div>
    `;
    
    openModal('eventDetailModal');
}

async function deleteEventFromDashboard() {
    if (!currentEventId) return;
    
    confirmAction('Eliminare questo appuntamento?', async () => {
        try {
            const response = await fetch('api/calendario.php?action=delete&id=' + encodeURIComponent(currentEventId), {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Appuntamento eliminato', 'success');
                closeModal('eventDetailModal');
                loadUpcomingEvents();
            } else {
                showToast(data.message || 'Errore eliminazione', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione', 'error');
        }
    });
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
