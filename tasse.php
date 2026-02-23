<?php
/**
 * Eterea Gestionale
 * Calcolatore Tasse - Regime Forfettario
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Calcolo Tasse';

// Recupera i codici ATECO per il select
try {
    $stmt = $pdo->query("SELECT * FROM codici_ateco ORDER BY codice ASC");
    $codiciAteco = $stmt->fetchAll();
} catch (PDOException $e) {
    $codiciAteco = [];
}

// Recupera impostazioni tasse
try {
    $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'tassa_inps_percentuale'");
    $stmt->execute();
    $inpsPerc = floatval($stmt->fetchColumn() ?: 25.72);
    
    $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'tassa_acconto_percentuale'");
    $stmt->execute();
    $accontoPerc = floatval($stmt->fetchColumn() ?: 100);
} catch (PDOException $e) {
    $inpsPerc = 25.72;
    $accontoPerc = 100;
}

// Recupera cronologia calcoli utente
try {
    $stmt = $pdo->prepare("
        SELECT * FROM cronologia_calcoli_tasse 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cronologia = $stmt->fetchAll();
} catch (PDOException $e) {
    $cronologia = [];
}

// Recupera totali per card riepilogative
try {
    // Totale progetti CAT (consegnati/archiviati con pagamento CAT)
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(prezzo_totale), 0) as totale
        FROM progetti 
        WHERE stato_progetto IN ('consegnato', 'archiviato') 
        AND stato_pagamento = 'cat'
    ");
    $totaleCAT = floatval($stmt->fetchColumn());
    
    // Totale progetti SENZA CAT (consegnati/archiviati con altro pagamento)
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(prezzo_totale), 0) as totale
        FROM progetti 
        WHERE stato_progetto IN ('consegnato', 'archiviato') 
        AND stato_pagamento != 'cat'
    ");
    $totaleSenzaCAT = floatval($stmt->fetchColumn());
    
    // Cassa aziendale
    $stmt = $pdo->query("SELECT COALESCE(SUM(importo), 0) FROM transazioni_economiche WHERE tipo = 'cassa'");
    $cassaAziendale = floatval($stmt->fetchColumn());
    
} catch (PDOException $e) {
    $totaleCAT = 0;
    $totaleSenzaCAT = 0;
    $cassaAziendale = 0;
}

include __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Calcolo Tasse</h1>
    <p class="text-slate-500 mt-1">Simulatore fiscale per regime forfettario</p>
</div>

<!-- Password Protection -->
<div id="passwordSection" class="max-w-md mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-800">Accesso Protetto</h2>
            <p class="text-sm text-slate-500">Inserisci la password per accedere al calcolatore</p>
        </div>
        
        <div class="space-y-4">
            <input type="password" id="accessPassword" 
                   class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-center text-lg"
                   placeholder="Password...">
            <button onclick="verificaPassword()" 
                    class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium transition-colors">
                Accedi
            </button>
        </div>
        
        <p id="passwordError" class="text-red-500 text-sm text-center mt-4 hidden">Password errata</p>
    </div>
</div>

<!-- Calcolatore (nascosto finché non si inserisce la password) -->
<div id="calcolatoreSection" class="hidden space-y-6">
    
    <!-- Card Totali - Cliccabili per inserire nel fatturato -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <button onclick="inserisciFatturato(<?php echo $totaleCAT; ?>)" 
                class="group text-left bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 rounded-2xl p-4 border border-purple-200 transition-all hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-purple-600 uppercase tracking-wide">Totale CAT</span>
                <svg class="w-4 h-4 text-purple-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <p class="text-xl font-bold text-purple-800">€ <?php echo number_format($totaleCAT, 2, ',', '.'); ?></p>
            <p class="text-xs text-purple-500 mt-1">Clicca per usare</p>
        </button>
        
        <button onclick="inserisciFatturato(<?php echo $totaleSenzaCAT; ?>)" 
                class="group text-left bg-gradient-to-br from-emerald-50 to-emerald-100 hover:from-emerald-100 hover:to-emerald-200 rounded-2xl p-4 border border-emerald-200 transition-all hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Totale senza CAT</span>
                <svg class="w-4 h-4 text-emerald-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <p class="text-xl font-bold text-emerald-800">€ <?php echo number_format($totaleSenzaCAT, 2, ',', '.'); ?></p>
            <p class="text-xs text-emerald-500 mt-1">Clicca per usare</p>
        </button>
        
        <button onclick="inserisciFatturato(<?php echo $cassaAziendale; ?>)" 
                class="group text-left bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-2xl p-4 border border-blue-200 transition-all hover:shadow-md hover:-translate-y-0.5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">Cassa Aziendale</span>
                <svg class="w-4 h-4 text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <p class="text-xl font-bold text-blue-800">€ <?php echo number_format($cassaAziendale, 2, ',', '.'); ?></p>
            <p class="text-xs text-blue-500 mt-1">Clicca per usare</p>
        </button>
    </div>
    
    <!-- Input Dati -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <h2 class="font-semibold text-slate-800">Dati di Calcolo</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Fatturato Annuo (€) *</label>
                <input type="number" id="fatturato" step="0.01" min="0"
                       class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none"
                       placeholder="es. 50000" onchange="calcolaTasse()">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Codice ATECO *</label>
                <select id="codiceAteco" onchange="onCodiceAtecoChange()"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none bg-white">
                    <option value="">-- Seleziona --</option>
                    <?php foreach ($codiciAteco as $c): ?>
                    <option value="<?php echo e($c['id']); ?>" 
                            data-codice="<?php echo e($c['codice']); ?>"
                            data-descrizione="<?php echo e($c['descrizione']); ?>"
                            data-coefficiente="<?php echo e($c['coefficiente_redditivita']); ?>"
                            data-tassazione="<?php echo e($c['tassazione']); ?>">
                        <?php echo e($c['codice']); ?> - <?php echo e($c['descrizione'] ?: 'N/A'); ?> (Coeff: <?php echo e($c['coefficiente_redditivita']); ?>%)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Aliquota IRPEF (%)</label>
                <input type="number" id="aliquotaIrpef" step="0.01" min="0" max="100"
                       class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none"
                       placeholder="es. 15 per flat tax">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Note (opzionale)</label>
                <input type="text" id="noteCalcolo" 
                       class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none"
                       placeholder="es. Previsione 2024, Progetto X...">
            </div>
        </div>
        
        <div class="mt-4 p-4 bg-slate-50 rounded-xl">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">INPS Gestione Separata (%)</label>
                    <input type="number" id="inpsPerc" step="0.01" value="<?php echo e($inpsPerc); ?>"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"
                           onchange="calcolaTasse()">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Coefficiente ATECO (%)</label>
                    <input type="number" id="coeffAteco" step="0.01" readonly
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-slate-100 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Acconti anno successivo (%)</label>
                    <input type="number" id="accontoPerc" step="0.01" value="<?php echo e($accontoPerc); ?>"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"
                           onchange="calcolaTasse()">
                </div>
            </div>
        </div>
        
        <div class="mt-4 flex gap-3">
            <button onclick="calcolaTasse()" 
                    class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium transition-colors">
                Calcola Tasse
            </button>
            <button onclick="salvaCalcolo()" id="btnSalvaCalcolo" disabled
                    class="px-6 py-3 bg-slate-200 text-slate-400 rounded-xl font-medium transition-colors cursor-not-allowed"
                    title="Calcola prima per abilitare il salvataggio">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
            </button>
        </div>
    </div>
    
    <!-- Risultati -->
    <div id="risultatiCalcolo" class="hidden space-y-4">
        
        <!-- Riepilogo Principale -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-emerald-50 rounded-2xl p-5 border border-emerald-200">
                <p class="text-sm text-emerald-600 font-medium mb-1">Fatturato Lordo</p>
                <p class="text-2xl font-bold text-emerald-800" id="resFatturato">€ 0,00</p>
            </div>
            <div class="bg-blue-50 rounded-2xl p-5 border border-blue-200">
                <p class="text-sm text-blue-600 font-medium mb-1">Reddito Imponibile</p>
                <p class="text-2xl font-bold text-blue-800" id="resImponibile">€ 0,00</p>
                <p class="text-xs text-blue-500" id="resCoeffText">Coefficiente: 0%</p>
            </div>
            <div class="bg-purple-50 rounded-2xl p-5 border border-purple-200">
                <p class="text-sm text-purple-600 font-medium mb-1">Netto Stimato</p>
                <p class="text-2xl font-bold text-purple-800" id="resNetto">€ 0,00</p>
            </div>
        </div>
        
        <!-- Dettaglio Tasse -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-800 mb-4">Dettaglio Imposte - Regime Forfettario</h3>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-slate-100 bg-emerald-50 px-3 rounded-lg">
                    <span class="font-medium text-emerald-800">Fatturato Lordo</span>
                    <span class="font-bold text-emerald-700" id="detFatturato">€ 0,00</span>
                </div>
                
                <div class="flex justify-between items-center py-2">
                    <span class="text-slate-600">Coefficiente ATECO</span>
                    <span class="font-medium text-emerald-600" id="detCoeff">0%</span>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b border-slate-100 bg-blue-50 px-3 rounded-lg">
                    <span class="font-medium text-blue-800">Reddito Imponibile</span>
                    <span class="font-bold text-blue-700" id="detImponibile">€ 0,00</span>
                </div>
                
                <div class="pt-2 space-y-2">
                    <div class="flex justify-between items-center py-2">
                        <span class="text-slate-600">Imposta IRPEF <span id="detAliquotaIrpef" class="text-xs text-slate-400">(0%)</span></span>
                        <span class="font-medium text-red-600" id="detIrpef">€ 0,00</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-slate-600">Contributi INPS <span id="detAliquotaInps" class="text-xs text-slate-400">(0%)</span></span>
                        <span class="font-medium text-orange-600" id="detInps">€ 0,00</span>
                    </div>
                    <div class="flex justify-between items-center py-2 bg-amber-50 px-3 rounded-lg">
                        <span class="text-amber-800 font-medium">Acconti anno succ. (+<span id="detAccontoPerc">100</span>%)</span>
                        <span class="font-medium text-amber-700" id="detAcconto">€ 0,00</span>
                    </div>
                </div>
                
                <div class="flex justify-between items-center py-3 border-t-2 border-slate-200 mt-3">
                    <span class="font-semibold text-slate-800">TOTALE TASSE</span>
                    <span class="text-xl font-bold text-red-600" id="detTotaleTasse">€ 0,00</span>
                </div>
                
                <div class="flex justify-between items-center py-3 bg-emerald-100 px-4 rounded-xl mt-3">
                    <span class="font-semibold text-emerald-900">NETTO IN TASCA</span>
                    <span class="text-2xl font-bold text-emerald-700" id="detNetto">€ 0,00</span>
                </div>
            </div>
        </div>
        
        <!-- Note informative -->
        <div class="bg-amber-50 rounded-xl p-4 border border-amber-200">
            <p class="text-sm text-amber-800">
                <strong>Regime Forfettario:</strong> Il reddito imponibile si calcola applicando il coefficiente ATECO al fatturato. 
                Non si detraggono i costi. I contributi INPS sono deducibili dall'imponibile.<br>
                <strong>Acconti:</strong> Si pagano in base alle imposte dell'anno precedente (100% di solito).
            </p>
        </div>
    </div>
    
    <!-- Cronologia Calcoli -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 bg-cyan-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="font-semibold text-slate-800">Cronologia Calcoli</h2>
            </div>
            <?php if (count($cronologia) > 0): ?>
            <span class="text-sm text-slate-500"><?php echo count($cronologia); ?> calcoli salvati</span>
            <?php endif; ?>
        </div>
        
        <?php if (count($cronologia) > 0): ?>
        <div class="space-y-3 max-h-96 overflow-y-auto">
            <?php foreach ($cronologia as $calc): ?>
            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 hover:border-emerald-300 transition-colors">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-semibold text-slate-800">€ <?php echo number_format($calc['fatturato'], 2, ',', '.'); ?></span>
                            <span class="text-xs px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full">
                                Coeff: <?php echo $calc['coefficiente']; ?>%
                            </span>
                        </div>
                        <p class="text-sm text-slate-600"><?php echo e($calc['codice_ateco']); ?> - <?php echo e($calc['descrizione_ateco'] ?: ''); ?></p>
                        <?php if ($calc['note']): ?>
                        <p class="text-xs text-slate-500 mt-1">📝 <?php echo e($calc['note']); ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-slate-400 mt-2">
                            IRPEF: € <?php echo number_format($calc['imposta_irpef'], 2, ',', '.'); ?> | 
                            INPS: € <?php echo number_format($calc['contributi_inps'], 2, ',', '.'); ?> | 
                            Netto: € <?php echo number_format($calc['netto'], 2, ',', '.'); ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-emerald-700">€ <?php echo number_format($calc['netto'], 2, ',', '.'); ?></p>
                        <p class="text-xs text-slate-400"><?php echo date('d/m/Y H:i', strtotime($calc['created_at'])); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="text-slate-500">Nessun calcolo salvato</p>
            <p class="text-sm text-slate-400 mt-1">I tuoi calcoli appariranno qui</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
const PASSWORD_CORRETTA = 'Tomato2399!?';
let ultimoCalcolo = null;

function verificaPassword() {
    const pwd = document.getElementById('accessPassword').value;
    if (pwd === PASSWORD_CORRETTA) {
        document.getElementById('passwordSection').classList.add('hidden');
        document.getElementById('calcolatoreSection').classList.remove('hidden');
        document.getElementById('passwordError').classList.add('hidden');
    } else {
        document.getElementById('passwordError').classList.remove('hidden');
    }
}

// Permetti invio con Enter
document.getElementById('accessPassword')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') verificaPassword();
});

function formatCurrency(value) {
    return '€ ' + parseFloat(value).toLocaleString('it-IT', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function onCodiceAtecoChange() {
    const select = document.getElementById('codiceAteco');
    const option = select.options[select.selectedIndex];
    
    if (option && option.value) {
        const coeff = option.dataset.coefficiente;
        const tax = option.dataset.tassazione;
        document.getElementById('coeffAteco').value = coeff;
        document.getElementById('aliquotaIrpef').value = tax;
    } else {
        document.getElementById('coeffAteco').value = '';
        document.getElementById('aliquotaIrpef').value = '';
    }
    
    calcolaTasse();
}

function inserisciFatturato(valore) {
    document.getElementById('fatturato').value = valore.toFixed(2);
    calcolaTasse();
    
    // Feedback visivo - scrolla al campo
    document.getElementById('fatturato').scrollIntoView({ behavior: 'smooth', block: 'center' });
    document.getElementById('fatturato').classList.add('ring-2', 'ring-emerald-500');
    setTimeout(() => {
        document.getElementById('fatturato').classList.remove('ring-2', 'ring-emerald-500');
    }, 1000);
}

function calcolaTasse() {
    const fatturato = parseFloat(document.getElementById('fatturato').value) || 0;
    const inpsPerc = parseFloat(document.getElementById('inpsPerc').value) || 0;
    const accontoPerc = parseFloat(document.getElementById('accontoPerc').value) || 0;
    
    // Recupera dati dal codice ATECO
    const selectAteco = document.getElementById('codiceAteco');
    const option = selectAteco.options[selectAteco.selectedIndex];
    
    let coefficiente = 0;
    let aliquotaIrpef = 0;
    let codiceAtecoVal = '';
    let descAteco = '';
    
    if (option && option.value) {
        coefficiente = parseFloat(option.dataset.coefficiente) || 0;
        aliquotaIrpef = parseFloat(option.dataset.tassazione) || 0;
        codiceAtecoVal = option.dataset.codice;
        descAteco = option.dataset.descrizione;
        document.getElementById('coeffAteco').value = coefficiente;
        document.getElementById('aliquotaIrpef').value = aliquotaIrpef;
    } else {
        coefficiente = parseFloat(document.getElementById('coeffAteco').value) || 0;
        aliquotaIrpef = parseFloat(document.getElementById('aliquotaIrpef').value) || 0;
    }
    
    if (fatturato <= 0 || coefficiente <= 0) {
        document.getElementById('risultatiCalcolo').classList.add('hidden');
        document.getElementById('btnSalvaCalcolo').disabled = true;
        document.getElementById('btnSalvaCalcolo').classList.add('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
        document.getElementById('btnSalvaCalcolo').classList.remove('bg-emerald-600', 'text-white', 'hover:bg-emerald-700');
        return;
    }
    
    // Calcoli Regime Forfettario
    // Reddito Imponibile = Fatturato × Coefficiente
    // Contributi INPS sono deducibili, quindi calcoliamo prima l'INPS approssimativo
    
    const redditoImponibile = fatturato * (coefficiente / 100);
    
    // Contributi INPS (deducibili, quindi riducono l'imponibile IRPEF)
    const inps = redditoImponibile * (inpsPerc / 100);
    
    // IRPEF sul reddito imponibile (i contributi INPS sono deducibili)
    const imponibileIrpef = Math.max(0, redditoImponibile - inps);
    const irpef = imponibileIrpef * (aliquotaIrpef / 100);
    
    // Acconti (da pagare l'anno dopo, pari alle imposte dell'anno corrente)
    const acconto = (irpef + inps) * (accontoPerc / 100);
    
    const totaleTasse = irpef + inps + acconto;
    const netto = fatturato - totaleTasse;
    
    // Salva per il salvataggio
    ultimoCalcolo = {
        fatturato: fatturato,
        codice_ateco: codiceAtecoVal,
        descrizione_ateco: descAteco,
        coefficiente: coefficiente,
        reddito_imponibile: redditoImponibile,
        aliquota_irpef: aliquotaIrpef,
        imposta_irpef: irpef,
        inps_percentuale: inpsPerc,
        contributi_inps: inps,
        acconto_percentuale: accontoPerc,
        acconti: acconto,
        totale_tasse: totaleTasse,
        netto: netto,
        note: document.getElementById('noteCalcolo').value
    };
    
    // Aggiorna UI
    document.getElementById('resFatturato').textContent = formatCurrency(fatturato);
    document.getElementById('resImponibile').textContent = formatCurrency(redditoImponibile);
    document.getElementById('resCoeffText').textContent = `Coefficiente: ${coefficiente}%`;
    document.getElementById('resNetto').textContent = formatCurrency(netto);
    
    document.getElementById('detFatturato').textContent = formatCurrency(fatturato);
    document.getElementById('detCoeff').textContent = coefficiente + '%';
    document.getElementById('detImponibile').textContent = formatCurrency(redditoImponibile);
    document.getElementById('detAliquotaIrpef').textContent = `(${aliquotaIrpef}%)`;
    document.getElementById('detIrpef').textContent = formatCurrency(irpef);
    document.getElementById('detAliquotaInps').textContent = `(${inpsPerc}%)`;
    document.getElementById('detInps').textContent = formatCurrency(inps);
    document.getElementById('detAccontoPerc').textContent = accontoPerc;
    document.getElementById('detAcconto').textContent = formatCurrency(acconto);
    document.getElementById('detTotaleTasse').textContent = formatCurrency(totaleTasse);
    document.getElementById('detNetto').textContent = formatCurrency(netto);
    
    document.getElementById('risultatiCalcolo').classList.remove('hidden');
    
    // Abilita bottone salva
    document.getElementById('btnSalvaCalcolo').disabled = false;
    document.getElementById('btnSalvaCalcolo').classList.remove('bg-slate-200', 'text-slate-400', 'cursor-not-allowed');
    document.getElementById('btnSalvaCalcolo').classList.add('bg-emerald-600', 'text-white', 'hover:bg-emerald-700');
    document.getElementById('btnSalvaCalcolo').title = 'Salva questo calcolo';
}

async function salvaCalcolo() {
    if (!ultimoCalcolo) {
        showToast('Calcola prima le tasse', 'error');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'salva_calcolo_tasse');
        Object.keys(ultimoCalcolo).forEach(key => {
            formData.append(key, ultimoCalcolo[key]);
        });
        
        const response = await fetch('api/tasse.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Calcolo salvato!', 'success');
            // Ricarica la pagina per vedere la cronologia aggiornata
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast(data.message || 'Errore durante il salvataggio', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
