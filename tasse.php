<?php
/**
 * Eterea Gestionale
 * Calcolatore Tasse
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

include __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Calcolo Tasse</h1>
    <p class="text-slate-500 mt-1">Simulatore fiscale per partita IVA</p>
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
    
    <!-- Input Dati -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <h2 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Dati di Calcolo
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Fatturato Annuo (€)</label>
                <input type="number" id="fatturato" step="0.01" min="0"
                       class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none"
                       placeholder="es. 50000" onchange="calcolaTasse()">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Costi Deductibili (€)</label>
                <input type="number" id="costi" step="0.01" min="0"
                       class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none"
                       placeholder="es. 10000" onchange="calcolaTasse()">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Codice ATECO</label>
                <select id="codiceAteco" onchange="calcolaTasse()"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none bg-white">
                    <option value="">-- Seleziona --</option>
                    <?php foreach ($codiciAteco as $c): ?>
                    <option value="<?php echo e($c['id']); ?>" 
                            data-coefficiente="<?php echo e($c['coefficiente_redditivita']); ?>"
                            data-tassazione="<?php echo e($c['tassazione']); ?>">
                        <?php echo e($c['codice']); ?> - <?php echo e($c['descrizione'] ?: 'N/A'); ?>
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
        </div>
        
        <div class="mt-4 p-4 bg-slate-50 rounded-xl">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">INPS (%)</label>
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
                    <label class="block text-xs font-medium text-slate-500 mb-1">Acconti (%)</label>
                    <input type="number" id="accontoPerc" step="0.01" value="<?php echo e($accontoPerc); ?>"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"
                           onchange="calcolaTasse()">
                </div>
            </div>
        </div>
        
        <button onclick="calcolaTasse()" 
                class="mt-4 w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-medium transition-colors">
            Calcola Tasse
        </button>
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
            <h3 class="font-semibold text-slate-800 mb-4">Dettaglio Imposte</h3>
            
            <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-600">Fatturato Lordo</span>
                    <span class="font-medium" id="detFatturato">€ 0,00</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100">
                    <span class="text-slate-600">- Costi Deductibili</span>
                    <span class="font-medium text-slate-500" id="detCosti">€ 0,00</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-100 bg-slate-50 px-3 rounded-lg">
                    <span class="font-medium text-slate-700">Reddito Lordo</span>
                    <span class="font-semibold" id="detLordo">€ 0,00</span>
                </div>
                
                <div class="py-2">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-slate-600">Coefficiente ATECO</span>
                        <span class="font-medium text-emerald-600" id="detCoeff">0%</span>
                    </div>
                </div>
                
                <div class="flex justify-between items-center py-2 border-b border-slate-100 bg-emerald-50 px-3 rounded-lg">
                    <span class="font-medium text-emerald-800">Reddito Imponibile</span>
                    <span class="font-bold text-emerald-700" id="detImponibile">€ 0,00</span>
                </div>
                
                <div class="pt-2 space-y-2">
                    <div class="flex justify-between items-center py-2">
                        <span class="text-slate-600">Imposta IRPEF</span>
                        <span class="font-medium text-red-600" id="detIrpef">€ 0,00</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-slate-600">Contributi INPS</span>
                        <span class="font-medium text-orange-600" id="detInps">€ 0,00</span>
                    </div>
                    <div class="flex justify-between items-center py-2 bg-amber-50 px-3 rounded-lg">
                        <span class="text-amber-800 font-medium">Acconti Tasse (+<span id="detAccontoPerc">100</span>%)</span>
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
        
        <!-- Note -->
        <div class="bg-amber-50 rounded-xl p-4 border border-amber-200">
            <p class="text-sm text-amber-800">
                <strong>Nota:</strong> Questi calcoli sono indicativi e non sostituiscono la consulenza di un commercialista. 
                I valori si basano sui coefficienti ATECO e sulle aliquote configurate nelle impostazioni.
            </p>
        </div>
    </div>
</div>

<script>
const PASSWORD_CORRETTA = 'Tomato2399!?';

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

function calcolaTasse() {
    const fatturato = parseFloat(document.getElementById('fatturato').value) || 0;
    const costi = parseFloat(document.getElementById('costi').value) || 0;
    const inpsPerc = parseFloat(document.getElementById('inpsPerc').value) || 0;
    const accontoPerc = parseFloat(document.getElementById('accontoPerc').value) || 0;
    
    // Recupera dati dal codice ATECO selezionato
    const selectAteco = document.getElementById('codiceAteco');
    const option = selectAteco.options[selectAteco.selectedIndex];
    
    let coefficiente = 0;
    let aliquotaIrpef = 0;
    
    if (option && option.value) {
        coefficiente = parseFloat(option.dataset.coefficiente) || 0;
        aliquotaIrpef = parseFloat(option.dataset.tassazione) || 0;
        document.getElementById('coeffAteco').value = coefficiente;
        document.getElementById('aliquotaIrpef').value = aliquotaIrpef;
    } else {
        coefficiente = parseFloat(document.getElementById('coeffAteco').value) || 0;
        aliquotaIrpef = parseFloat(document.getElementById('aliquotaIrpef').value) || 0;
    }
    
    if (fatturato <= 0) {
        document.getElementById('risultatiCalcolo').classList.add('hidden');
        return;
    }
    
    // Calcoli
    const lordo = fatturato - costi;
    const imponibile = lordo * (coefficiente / 100);
    const irpef = imponibile * (aliquotaIrpef / 100);
    const inps = imponibile * (inpsPerc / 100);
    const acconto = (irpef + inps) * (accontoPerc / 100);
    const totaleTasse = irpef + inps + acconto;
    const netto = fatturato - totaleTasse;
    
    // Aggiorna UI
    document.getElementById('resFatturato').textContent = formatCurrency(fatturato);
    document.getElementById('resImponibile').textContent = formatCurrency(imponibile);
    document.getElementById('resCoeffText').textContent = `Coefficiente: ${coefficiente}%`;
    document.getElementById('resNetto').textContent = formatCurrency(netto);
    
    document.getElementById('detFatturato').textContent = formatCurrency(fatturato);
    document.getElementById('detCosti').textContent = formatCurrency(costi);
    document.getElementById('detLordo').textContent = formatCurrency(lordo);
    document.getElementById('detCoeff').textContent = coefficiente + '%';
    document.getElementById('detImponibile').textContent = formatCurrency(imponibile);
    document.getElementById('detIrpef').textContent = formatCurrency(irpef);
    document.getElementById('detInps').textContent = formatCurrency(inps);
    document.getElementById('detAccontoPerc').textContent = accontoPerc;
    document.getElementById('detAcconto').textContent = formatCurrency(acconto);
    document.getElementById('detTotaleTasse').textContent = formatCurrency(totaleTasse);
    document.getElementById('detNetto').textContent = formatCurrency(netto);
    
    document.getElementById('risultatiCalcolo').classList.remove('hidden');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
