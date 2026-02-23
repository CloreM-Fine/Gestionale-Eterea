<?php
/**
 * Eterea Gestionale
 * Finanze
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Finanze';

// Verifica se l'utente è Lorenzo Puccetti (admin)
$isLorenzo = ($_SESSION['user_id'] === 'ucwurog3xr8tf' || $_SESSION['user_name'] === 'Lorenzo Puccetti');

// Ottieni dati finanziari
try {
    // Cassa aziendale
    $stmt = $pdo->query("SELECT COALESCE(SUM(importo), 0) FROM transazioni_economiche WHERE tipo = 'cassa'");
    $cassaTotale = (float)$stmt->fetchColumn();
    
    // Wallet utenti (solo i 3 membri del team)
    $stmt = $pdo->query("SELECT id, nome, wallet_saldo, colore FROM utenti ORDER BY nome ASC");
    $allWallets = $stmt->fetchAll();
    // Filtra solo i 3 utenti validi (esclude "User" o altri)
    $wallets = array_filter($allWallets, function($u) {
        return isset(USERS[$u['id']]); // Solo utenti definiti in config
    });
    
    // Progetti consegnati con distribuzione
    $stmt = $pdo->query("
        SELECT p.*, c.ragione_sociale as cliente_nome
        FROM progetti p
        LEFT JOIN clienti c ON p.cliente_id = c.id
        WHERE p.distribuzione_effettuata = TRUE
        ORDER BY p.data_pagamento DESC
        LIMIT 10
    ");
    $progettiDistribuiti = $stmt->fetchAll();
    
    // Progetti CAT (Consegnati/Archiviati con pagamento CAT)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count, COALESCE(SUM(prezzo_totale), 0) as totale
        FROM progetti 
        WHERE stato_progetto IN ('consegnato', 'archiviato') 
        AND stato_pagamento = 'cat'
    ");
    $progettiCAT = $stmt->fetch();
    
    // Totale movimentato
    $stmt = $pdo->query("SELECT COALESCE(SUM(importo), 0) FROM transazioni_economiche WHERE tipo = 'wallet'");
    $totaleMovimentato = (float)$stmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Errore finanze: " . $e->getMessage());
    $cassaTotale = 0;
    $wallets = [];
    $progettiDistribuiti = [];
    $progettiCAT = ['count' => 0, 'totale' => 0];
    $totaleMovimentato = 0;
    $dbError = $e->getMessage();
}

include __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Finanze</h1>
    <p class="text-slate-500 mt-1">Riepilogo economico e distribuzioni</p>
</div>

<?php if (isset($dbError)): ?>
<div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl text-red-700">
    <p class="font-medium">Errore database:</p>
    <p class="text-sm"><?php echo e($dbError); ?></p>
</div>
<?php endif; ?>

<!-- Statistiche -->
<div class="space-y-4 md:space-y-6 mb-8">
    <!-- Prima riga: Cassa, Movimentato, CAT, Progetti Distribuiti -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
        <!-- Card Progetti CAT -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl md:rounded-2xl p-4 md:p-5 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-purple-100 text-xs md:text-sm font-medium">Progetti CAT</p>
                    <h3 class="text-lg md:text-xl lg:text-2xl font-bold mt-1 truncate"><?php echo formatCurrency($progettiCAT['totale']); ?></h3>
                    <p class="text-purple-100 text-xs mt-1"><?php echo $progettiCAT['count']; ?> progetti</p>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-white/20 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0 ml-2">
                    <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl md:rounded-2xl p-4 md:p-6 text-white shadow-lg relative">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-emerald-100 text-xs md:text-sm font-medium">Cassa Aziendale</p>
                    <h3 class="text-lg md:text-2xl lg:text-3xl font-bold mt-1 truncate"><?php echo formatCurrency($cassaTotale); ?></h3>
                </div>
                <div class="w-10 h-10 md:w-14 md:h-14 bg-white/20 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0 ml-2">
                    <svg class="w-5 h-5 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
            </div>
            <?php if ($isLorenzo): ?>
            <button onclick="openModalAggiunta('cassa')" 
                    class="absolute bottom-3 right-3 md:bottom-4 md:right-4 w-7 h-7 md:w-8 md:h-8 bg-white/30 hover:bg-white/50 rounded-full flex items-center justify-center text-white transition-colors" 
                    title="Aggiungi importo">
                <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
            </button>
            <?php endif; ?>
        </div>
        
        <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl md:rounded-2xl p-4 md:p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-cyan-100 text-xs md:text-sm font-medium">Totale Movimentato</p>
                    <h3 class="text-lg md:text-2xl lg:text-3xl font-bold mt-1 truncate"><?php echo formatCurrency($totaleMovimentato); ?></h3>
                </div>
                <div class="w-10 h-10 md:w-14 md:h-14 bg-white/20 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0 ml-2">
                    <svg class="w-5 h-5 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl md:rounded-2xl p-4 md:p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-purple-100 text-xs md:text-sm font-medium">Progetti Distribuiti</p>
                    <h3 class="text-lg md:text-2xl lg:text-3xl font-bold mt-1"><?php echo count($progettiDistribuiti); ?></h3>
                </div>
                <div class="w-10 h-10 md:w-14 md:h-14 bg-white/20 rounded-lg md:rounded-xl flex items-center justify-center flex-shrink-0 ml-2">
                    <svg class="w-5 h-5 md:w-7 md:h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
    <!-- Wallet Utenti -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 md:p-5 border-b border-slate-100">
                <h2 class="font-bold text-slate-800">Wallet Team</h2>
                <p class="text-xs md:text-sm text-slate-500">Crediti individuali</p>
            </div>
            
            <div class="divide-y divide-slate-100">
                <?php if (empty($wallets)): ?>
                <div class="p-8 text-center text-slate-400">
                    <p>Nessun wallet disponibile</p>
                    <p class="text-xs mt-2">Controlla la connessione al database</p>
                </div>
                <?php endif; ?>
                <?php foreach ($wallets as $w): 
                    $percentuale = $totaleMovimentato > 0 ? ($w['wallet_saldo'] / $totaleMovimentato) * 100 : 0;
                ?>
                <div class="p-4 md:p-5 relative">
                    <div class="flex items-center gap-3 md:gap-4">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full flex items-center justify-center text-white font-medium text-sm md:text-base flex-shrink-0" 
                             style="background-color: <?php echo $w['colore']; ?>">
                            <?php echo substr($w['nome'], 0, 2); ?>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-800 text-sm md:text-base truncate"><?php echo e($w['nome']); ?></p>
                            <p class="text-lg md:text-2xl font-bold text-slate-800"><?php echo formatCurrency($w['wallet_saldo']); ?></p>
                        </div>
                        <?php if ($isLorenzo): ?>
                        <button onclick="openModalAggiunta('wallet', '<?php echo $w['id']; ?>', '<?php echo e($w['nome']); ?>')" 
                                class="w-8 h-8 bg-slate-100 hover:bg-slate-200 rounded-full flex items-center justify-center text-slate-600 transition-colors flex-shrink-0" 
                                title="Aggiungi credito a <?php echo e($w['nome']); ?>">
                            <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                            <span>Quota sul totale</span>
                            <span><?php echo number_format($percentuale, 1); ?>%</span>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all" 
                                 style="width: <?php echo min($percentuale, 100); ?>%; background-color: <?php echo $w['colore']; ?>"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Progetti Distribuiti -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 md:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="font-bold text-slate-800">Progetti Distribuiti</h2>
                    <p class="text-xs md:text-sm text-slate-500">Ultimi progetti con profit sharing</p>
                </div>
                <a href="progetti.php?stato=consegnato" class="text-cyan-600 hover:text-cyan-700 text-xs md:text-sm font-medium">
                    Vedi tutti
                </a>
            </div>
            
            <div class="divide-y divide-slate-100">
                <?php if (empty($progettiDistribuiti)): ?>
                <div class="p-8 text-center text-slate-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p>Nessun progetto distribuito ancora</p>
                </div>
                <?php else: ?>
                    <?php foreach ($progettiDistribuiti as $p):
                        $partecipanti = json_decode($p['partecipanti'] ?? '[]', true);
                    ?>
                    <div class="p-4 md:p-5 hover:bg-slate-50 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-slate-800 text-sm md:text-base"><?php echo e($p['titolo']); ?></h3>
                                <p class="text-xs md:text-sm text-slate-500 mt-1">
                                    <?php echo e($p['cliente_nome'] ?: 'Nessun cliente'); ?> • 
                                    Pagato il <?php echo formatDate($p['data_pagamento']); ?>
                                </p>
                            </div>
                            <span class="font-bold text-slate-800 text-sm md:text-base whitespace-nowrap"><?php echo formatCurrency($p['prezzo_totale']); ?></span>
                        </div>
                        
                        <div class="mt-3 flex items-center gap-2 flex-wrap">
                            <span class="text-xs text-slate-500">Partecipanti:</span>
                            <div class="flex -space-x-2">
                                <?php foreach ($partecipanti as $pid): 
                                    if (!isset(USERS[$pid])) continue;
                                    $u = USERS[$pid];
                                ?>
                                <div class="w-6 h-6 md:w-7 md:h-7 rounded-full border-2 border-white flex items-center justify-center text-white text-xs font-medium" 
                                     style="background-color: <?php echo $u['colore']; ?>" 
                                     title="<?php echo e($u['nome']); ?>">
                                    <?php echo substr($u['nome'], 0, 1); ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($isLorenzo): ?>
        <!-- Transazioni Manuali Recenti -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 md:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-800">Transazioni Manuali</h2>
                    <p class="text-xs md:text-sm text-slate-500">Inserimenti ed eliminazioni recenti</p>
                </div>
                <button onclick="caricaTransazioni()" class="text-cyan-600 hover:text-cyan-700 text-xs md:text-sm font-medium flex items-center gap-1 self-start sm:self-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Aggiorna
                </button>
            </div>
            
            <div id="transazioniList" class="p-3 md:p-0 max-h-96 overflow-y-auto">
                <div class="p-8 text-center text-slate-400">
                    <p>Caricamento...</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Grafico distribuzione teorica -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 md:p-5 border-b border-slate-100">
                <h2 class="font-bold text-slate-800">Schema Distribuzione Profit Sharing</h2>
                <p class="text-xs md:text-sm text-slate-500">Come viene suddiviso l'importo in base ai partecipanti</p>
            </div>
            
            <div class="p-4 md:p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                    <!-- 1 Partecipante -->
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <h3 class="font-semibold text-slate-800 mb-3 text-center text-sm md:text-base">1 Partecipante</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-2 bg-blue-100 rounded-lg text-blue-800 text-sm md:text-base">
                                <span class="font-medium">Attivo</span>
                                <span class="font-bold">70%</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-slate-200 rounded-lg text-slate-600 text-xs md:text-sm">
                                <span>Passivo 1</span>
                                <span>10%</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-slate-200 rounded-lg text-slate-600 text-xs md:text-sm">
                                <span>Passivo 2</span>
                                <span>10%</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-emerald-100 rounded-lg text-emerald-800 text-sm md:text-base">
                                <span class="font-medium">Cassa</span>
                                <span class="font-bold">10%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 2 Partecipanti -->
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <h3 class="font-semibold text-slate-800 mb-3 text-center text-sm md:text-base">2 Partecipanti</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-2 bg-blue-100 rounded-lg text-blue-800 text-sm md:text-base">
                                <span class="font-medium">Attivo 1</span>
                                <span class="font-bold">40%</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-blue-100 rounded-lg text-blue-800 text-sm md:text-base">
                                <span class="font-medium">Attivo 2</span>
                                <span class="font-bold">40%</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-slate-200 rounded-lg text-slate-600 text-xs md:text-sm">
                                <span>Passivo</span>
                                <span>10%</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-emerald-100 rounded-lg text-emerald-800 text-sm md:text-base">
                                <span class="font-medium">Cassa</span>
                                <span class="font-bold">10%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 3 Partecipanti -->
                    <div class="p-4 bg-slate-50 rounded-xl">
                        <h3 class="font-semibold text-slate-800 mb-3 text-center text-sm md:text-base">3 Partecipanti</h3>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-2 bg-blue-100 rounded-lg text-blue-800 text-sm md:text-base">
                                <span class="font-medium">Attivo 1</span>
                                <span class="font-bold">30%</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-blue-100 rounded-lg text-blue-800 text-sm md:text-base">
                                <span class="font-medium">Attivo 2</span>
                                <span class="font-bold">30%</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-blue-100 rounded-lg text-blue-800 text-sm md:text-base">
                                <span class="font-medium">Attivo 3</span>
                                <span class="font-bold">30%</span>
                            </div>
                            <div class="flex items-center justify-between p-2 bg-emerald-100 rounded-lg text-emerald-800 text-sm md:text-base">
                                <span class="font-medium">Cassa</span>
                                <span class="font-bold">10%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($isLorenzo): ?>
<!-- Modal Aggiunta Importo -->
<div id="modalAggiunta" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModalAggiunta()"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-md sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[85vh] sm:max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-4 md:p-5 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="font-bold text-slate-800 text-lg" id="modalTitle">Aggiungi Importo</h3>
                <button onclick="closeModalAggiunta()" class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="p-4 md:p-5 space-y-4 overflow-y-auto flex-1">
                <input type="hidden" id="tipoAggiunta" value="">
                <input type="hidden" id="utenteIdAggiunta" value="">
                
                <div id="infoDestinatario" class="hidden p-3 bg-slate-50 rounded-lg">
                    <p class="text-sm text-slate-600">Destinatario: <span class="font-semibold" id="nomeDestinatario"></span></p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Importo (€)</label>
                    <input type="number" id="importoAggiunta" step="0.01" min="0.01" 
                           class="w-full px-4 py-4 text-lg font-semibold border border-slate-200 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none h-14 touch-manipulation"
                           placeholder="0.00"
                           inputmode="decimal">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Descrizione (opzionale)</label>
                    <input type="text" id="descrizioneAggiunta" 
                           class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none h-12"
                           placeholder="Es. Bonus progetto XYZ">
                </div>
            </div>
            
            <div class="p-4 sm:p-6 border-t border-slate-100 flex flex-row justify-end gap-2 sm:gap-3 sticky bottom-0 bg-white z-10">
                <button type="button" onclick="closeModalAggiunta()" class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">
                    Annulla
                </button>
                <button type="button" onclick="salvaAggiunta()" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium min-h-[44px] transition-colors text-sm sm:text-base">
                    Aggiungi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Apre il modal per l'aggiunta importo
 */
function openModalAggiunta(tipo, utenteId = '', nomeUtente = '') {
    document.getElementById('tipoAggiunta').value = tipo;
    document.getElementById('utenteIdAggiunta').value = utenteId;
    document.getElementById('importoAggiunta').value = '';
    document.getElementById('descrizioneAggiunta').value = '';
    
    const infoDiv = document.getElementById('infoDestinatario');
    const nomeSpan = document.getElementById('nomeDestinatario');
    const title = document.getElementById('modalTitle');
    
    if (tipo === 'cassa') {
        title.textContent = 'Aggiungi a Cassa Aziendale';
        infoDiv.classList.add('hidden');
    } else {
        title.textContent = 'Aggiungi Crediti a Utente';
        nomeSpan.textContent = nomeUtente;
        infoDiv.classList.remove('hidden');
    }
    
    document.getElementById('modalAggiunta').classList.remove('hidden');
    // Focus sull'input importo dopo apertura
    setTimeout(() => document.getElementById('importoAggiunta').focus(), 100);
}

/**
 * Chiude il modal
 */
function closeModalAggiunta() {
    document.getElementById('modalAggiunta').classList.add('hidden');
}

/**
 * Salva l'aggiunta
 */
async function salvaAggiunta() {
    const tipo = document.getElementById('tipoAggiunta').value;
    const utenteId = document.getElementById('utenteIdAggiunta').value;
    const importo = parseFloat(document.getElementById('importoAggiunta').value);
    const descrizione = document.getElementById('descrizioneAggiunta').value;
    
    if (!importo || importo <= 0) {
        showToast('Inserisci un importo valido', 'error');
        return;
    }
    
    const action = tipo === 'cassa' ? 'aggiungi_cassa' : 'aggiungi_wallet';
    let body = `action=${action}&importo=${importo}&descrizione=${encodeURIComponent(descrizione)}`;
    
    if (tipo === 'wallet') {
        body += `&utente_id=${encodeURIComponent(utenteId)}`;
    }
    
    try {
        const response = await fetch('api/finanze.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Importo aggiunto con successo', 'success');
            closeModalAggiunta();
            // Ricarica la pagina per aggiornare i valori
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore durante l\'inserimento', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

/**
 * Carica le transazioni manuali recenti
 */
async function caricaTransazioni() {
    const list = document.getElementById('transazioniList');
    if (!list) return;
    
    list.innerHTML = '<div class="p-8 text-center text-slate-400"><p>Caricamento...</p></div>';
    
    try {
        const response = await fetch('api/finanze.php?action=list_transazioni');
        const data = await response.json();
        
        if (!data.success || !data.data || data.data.length === 0) {
            list.innerHTML = `
                <div class="p-8 text-center text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p>Nessuna transazione manuale</p>
                </div>
            `;
            return;
        }
        
        // Mobile: Card layout, Desktop: List layout
        list.innerHTML = data.data.map(t => {
            const dataFormattata = new Date(t.data).toLocaleString('it-IT', {
                day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit'
            });
            const isCassa = t.tipo === 'cassa';
            const tipoLabel = isCassa ? 'Cassa Aziendale' : `Wallet: ${t.utente_nome || 'Utente'}`;
            const tipoColor = isCassa ? 'emerald' : 'cyan';
            
            return `
                <!-- Mobile: Card layout -->
                <div class="md:hidden bg-white rounded-xl p-4 mb-3 shadow-sm border border-slate-100" id="trans-mobile-${t.id}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="px-2 py-1 bg-${tipoColor}-100 text-${tipoColor}-700 rounded-lg text-xs font-medium">
                                    ${tipoLabel}
                                </span>
                            </div>
                            <p class="text-lg font-bold text-slate-800 mb-1">+${formatCurrency(t.importo)}</p>
                            <p class="text-sm text-slate-600 line-clamp-2">${t.descrizione || 'Inserimento manuale'}</p>
                            <p class="text-xs text-slate-400 mt-2">${dataFormattata}</p>
                        </div>
                        <button onclick="eliminaTransazione('${t.id}')" 
                                class="w-10 h-10 bg-red-100 hover:bg-red-200 rounded-full flex items-center justify-center text-red-600 transition-colors flex-shrink-0"
                                title="Elimina transazione">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Desktop: List layout -->
                <div class="hidden md:flex p-4 hover:bg-slate-50 transition-colors items-center justify-between gap-4" id="trans-desktop-${t.id}">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 bg-${tipoColor}-100 text-${tipoColor}-700 rounded text-xs font-medium">
                                ${tipoLabel}
                            </span>
                            <span class="text-lg font-bold text-slate-800">+${formatCurrency(t.importo)}</span>
                        </div>
                        <p class="text-sm text-slate-600 truncate">${t.descrizione || 'Inserimento manuale'}</p>
                        <p class="text-xs text-slate-400">${dataFormattata}</p>
                    </div>
                    <button onclick="eliminaTransazione('${t.id}')" 
                            class="w-8 h-8 bg-red-100 hover:bg-red-200 rounded-full flex items-center justify-center text-red-600 transition-colors flex-shrink-0"
                            title="Elimina transazione">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            `;
        }).join('');
        
    } catch (error) {
        console.error('Errore caricamento transazioni:', error);
        list.innerHTML = '<div class="p-8 text-center text-slate-400"><p>Errore caricamento</p></div>';
    }
}

/**
 * Elimina una transazione
 */
async function eliminaTransazione(id) {
    if (!confirm('Sei sicuro di voler eliminare questa transazione?\nL\'importo verrà sottratto dal saldo.')) {
        return;
    }
    
    try {
        const response = await fetch('api/finanze.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=delete&id=${encodeURIComponent(id)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Transazione eliminata', 'success');
            // Rimuovi dal DOM
            const elMobile = document.getElementById(`trans-mobile-${id}`);
            const elDesktop = document.getElementById(`trans-desktop-${id}`);
            if (elMobile) elMobile.remove();
            if (elDesktop) elDesktop.remove();
            // Ricarica la pagina dopo 1 secondo per aggiornare i totali
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Errore eliminazione', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

/**
 * Formatta importo come valuta
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
}

// Carica transazioni all'avvio
document.addEventListener('DOMContentLoaded', function() {
    caricaTransazioni();
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
