<?php
/**
 * Eterea Gestionale
 * Calendario
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Calendario';

// Recupera clienti per il selettore
$clienti = [];
try {
    $stmt = $pdo->query("SELECT id, ragione_sociale FROM clienti ORDER BY ragione_sociale");
    $clienti = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Errore recupero clienti: ' . $e->getMessage());
}

include __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="mb-3 sm:mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-4">
        <div>
            <h1 class="text-lg sm:text-2xl font-bold text-slate-800">Calendario</h1>
            <p class="text-xs text-slate-500 mt-0.5 sm:mt-1">Gestisci appuntamenti e scadenze</p>
        </div>
        <button onclick="openEventModal()" 
                class="bg-cyan-600 hover:bg-cyan-700 text-white px-3 sm:px-4 py-2 sm:py-2.5 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors text-xs sm:text-sm min-h-[44px]">
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Nuovo Evento</span>
        </button>
    </div>
</div>

<!-- Layout: calendario sopra, lista eventi sotto su mobile -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-6">
    <!-- Colonna Sinistra: Calendario -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <!-- Header Calendario - Compatto su mobile -->
            <div class="p-2 sm:p-4 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2 sm:gap-4">
                    <button onclick="changeMonth(-1)" class="p-1.5 sm:p-2 hover:bg-slate-100 rounded-lg text-slate-600 min-h-[36px] min-w-[36px] flex items-center justify-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <h2 id="currentMonth" class="text-base sm:text-xl font-bold text-slate-800 min-w-[120px] sm:min-w-[160px] text-center"></h2>
                    <button onclick="changeMonth(1)" class="p-1.5 sm:p-2 hover:bg-slate-100 rounded-lg text-slate-600 min-h-[36px] min-w-[36px] flex items-center justify-center">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
                <button onclick="goToToday()" class="px-2 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-medium text-cyan-600 hover:bg-cyan-50 rounded-lg min-h-[36px]">
                    Oggi
                </button>
            </div>
            
            <!-- Griglia Calendario -->
            <div class="grid grid-cols-7 border-b border-slate-100">
                <?php 
                $giorni = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];
                foreach ($giorni as $g): 
                ?>
                <div class="p-1 sm:p-3 text-center text-xs sm:text-sm font-medium text-slate-500 border-r border-slate-100 last:border-r-0">
                    <span class="hidden sm:inline"><?php echo $g; ?></span>
                    <span class="sm:hidden"><?php echo substr($g, 0, 1); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div id="calendarGrid" class="grid grid-cols-7 auto-rows-fr">
                <!-- Generato via JS -->
            </div>
        </div>
        
        <!-- Lista eventi del giorno - Sotto il calendario su mobile -->
        <div class="lg:hidden mt-3 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-3 border-b border-slate-100 bg-gradient-to-r from-cyan-50 to-white">
                <h3 class="font-bold text-sm text-slate-800" id="mobileDayDetailTitle">Seleziona una data</h3>
                <p class="text-xs text-slate-500" id="mobileDayDetailSubtitle">Clicca sul calendario per vedere gli eventi</p>
            </div>
            
            <div id="mobileDayDetailContent" class="p-3 space-y-2 max-h-[300px] overflow-y-auto">
                <div class="text-center py-6 text-slate-400">
                    <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm">Seleziona un giorno dal calendario</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Colonna Destra: Dettagli Eventi del Giorno - Solo desktop -->
    <div class="hidden lg:block lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-4">
            <div class="p-4 border-b border-slate-100 bg-gradient-to-r from-cyan-50 to-white">
                <h3 class="font-bold text-slate-800" id="dayDetailTitle">Seleziona una data</h3>
                <p class="text-xs text-slate-500" id="dayDetailSubtitle">Clicca sul calendario per vedere gli eventi</p>
            </div>
            
            <div id="dayDetailContent" class="p-4 space-y-3 max-h-[calc(100vh-300px)] overflow-y-auto">
                <div class="text-center py-8 text-slate-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm">Seleziona un giorno dal calendario</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Prossimi Appuntamenti (1 settimana) -->
<div class="mt-4 sm:mt-6 bg-white rounded-xl sm:rounded-2xl shadow-sm border border-slate-200 p-3 sm:p-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-xs sm:text-sm font-semibold text-slate-700 flex items-center gap-2">
            <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Prossimi Appuntamenti (7 giorni)
        </h3>
        <span id="prossimiCount" class="text-xs bg-cyan-100 text-cyan-700 px-2 py-1 rounded-full">0</span>
    </div>
    <div id="prossimiAppuntamenti" class="flex gap-3 overflow-x-auto pb-2 scrollbar-thin">
        <div class="text-center py-4 text-slate-400 text-sm w-full">
            Caricamento appuntamenti...
        </div>
    </div>
</div>

<!-- Legenda Tipi Evento -->
<div class="mt-4 sm:mt-6 bg-white rounded-xl sm:rounded-2xl shadow-sm border border-slate-200 p-3 sm:p-4">
    <h3 class="text-xs sm:text-sm font-semibold text-slate-700 mb-2 sm:mb-3">Tipi di Evento</h3>
    <div class="flex flex-wrap gap-2 sm:gap-4">
        <div class="flex items-center gap-1.5 sm:gap-2">
            <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-cyan-500"></span>
            <span class="text-xs sm:text-sm text-slate-600">Appuntamento</span>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2">
            <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-blue-500"></span>
            <span class="text-xs sm:text-sm text-slate-600">Appuntamento Online</span>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2">
            <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-pink-500"></span>
            <span class="text-xs sm:text-sm text-slate-600">Shooting Cliente</span>
        </div>
        <div class="flex items-center gap-1.5 sm:gap-2">
            <span class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-purple-500"></span>
            <span class="text-xs sm:text-sm text-slate-600">Scadenza Progetto</span>
        </div>
    </div>
</div>

<!-- Modal Evento -->
<div id="eventModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModal('eventModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-2xl sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[85vh] sm:max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-3 sm:p-5 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
                <h3 class="text-sm sm:text-lg font-bold text-slate-800" id="eventModalTitle">Nuovo Evento</h3>
                <button onclick="closeModal('eventModal')" class="text-slate-400 hover:text-slate-600 p-1 min-h-[44px] min-w-[44px] flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="eventForm" class="p-3 sm:p-5 space-y-3 sm:space-y-4 overflow-y-auto flex-1">
                <input type="hidden" name="event_id" id="eventId" value="">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5">Titolo *</label>
                        <input type="text" name="titolo" required
                               class="w-full px-3 sm:px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm sm:text-base min-h-[44px]">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5">Tipo</label>
                        <select name="tipo" class="w-full px-3 sm:px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm sm:text-base min-h-[44px] bg-white">
                            <option value="appuntamento">Appuntamento</option>
                            <option value="appuntamento_online">Appuntamento Online</option>
                            <option value="shooting_cliente">Shooting Da Cliente</option>
                            <option value="scadenza_progetto">Scadenza Progetto</option>
                            <option value="promemoria">Promemoria</option>
                        </select>
                    </div>
                </div>
                
                <!-- Cliente (opzionale) -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5">Cliente (opzionale)</label>
                    <select name="cliente_id" id="eventClienteId" class="w-full px-3 sm:px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm sm:text-base min-h-[44px] bg-white">
                        <option value="">-- Seleziona cliente --</option>
                        <?php foreach ($clienti as $cliente): ?>
                        <option value="<?php echo e($cliente['id']); ?>"><?php echo e($cliente['ragione_sociale']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Data/Ora - Layout mobile-friendly -->
                <div class="space-y-3 sm:space-y-0 sm:grid sm:grid-cols-2 sm:gap-4">
                    <!-- Data Inizio -->
                    <div class="bg-slate-50 rounded-lg p-2 sm:p-0 sm:bg-transparent">
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5">Data Inizio *</label>
                        <div class="flex flex-col sm:grid sm:grid-cols-2 gap-2">
                            <div class="relative">
                                <input type="date" name="data_inizio_date" id="eventDataInizioDate" required
                                       class="w-full px-3 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm text-center bg-white min-h-[44px]"
                                       onfocus="this.classList.add('text-slate-900'); this.classList.remove('text-slate-400');"
                                       onblur="if(!this.value) { this.classList.remove('text-slate-900'); this.classList.add('text-slate-400'); }">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none sm:hidden" id="placeholder-data-inizio">Data</span>
                            </div>
                            <div class="relative">
                                <input type="time" name="data_inizio_time" id="eventDataInizioTime" required
                                       class="w-full px-3 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm text-center bg-white min-h-[44px]"
                                       onfocus="this.classList.add('text-slate-900'); this.classList.remove('text-slate-400'); document.getElementById('placeholder-ora-inizio').style.display='none';"
                                       onblur="if(!this.value) { this.classList.remove('text-slate-900'); this.classList.add('text-slate-400'); document.getElementById('placeholder-ora-inizio').style.display='block'; }">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none sm:hidden" id="placeholder-ora-inizio">Ora</span>
                            </div>
                        </div>
                        <input type="hidden" name="data_inizio" id="eventDataInizio">
                    </div>
                    <!-- Data Fine -->
                    <div class="bg-slate-50 rounded-lg p-2 sm:p-0 sm:bg-transparent">
                        <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5">Data Fine (opz.)</label>
                        <div class="flex flex-col sm:grid sm:grid-cols-2 gap-2">
                            <div class="relative">
                                <input type="date" name="data_fine_date" id="eventDataFineDate"
                                       class="w-full px-3 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm text-center bg-white min-h-[44px]"
                                       onfocus="this.classList.add('text-slate-900'); this.classList.remove('text-slate-400'); document.getElementById('placeholder-data-fine').style.display='none';"
                                       onblur="if(!this.value) { this.classList.remove('text-slate-900'); this.classList.add('text-slate-400'); document.getElementById('placeholder-data-fine').style.display='block'; }">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none sm:hidden" id="placeholder-data-fine">Data</span>
                            </div>
                            <div class="relative">
                                <input type="time" name="data_fine_time" id="eventDataFineTime"
                                       class="w-full px-3 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none text-sm text-center bg-white min-h-[44px]"
                                       onfocus="this.classList.add('text-slate-900'); this.classList.remove('text-slate-400'); document.getElementById('placeholder-ora-fine').style.display='none';"
                                       onblur="if(!this.value) { this.classList.remove('text-slate-900'); this.classList.add('text-slate-400'); document.getElementById('placeholder-ora-fine').style.display='block'; }">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 pointer-events-none sm:hidden" id="placeholder-ora-fine">Ora</span>
                            </div>
                        </div>
                        <input type="hidden" name="data_fine" id="eventDataFine">
                    </div>
                </div>
                
                <!-- Partecipanti -->
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Partecipanti</label>
                    <div class="grid grid-cols-1 sm:flex sm:flex-wrap gap-2 p-2 sm:p-3 border border-slate-200 rounded-lg bg-slate-50">
                        <?php foreach (USERS as $id => $u): ?>
                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-white transition-colors bg-white min-h-[44px]">
                            <input type="checkbox" name="partecipanti[]" value="<?php echo $id; ?>" class="rounded text-cyan-600 focus:ring-cyan-500 w-5 h-5 flex-shrink-0">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-medium flex-shrink-0" style="background-color: <?php echo $u['colore']; ?>">
                                <?php echo substr($u['nome'], 0, 1); ?>
                            </span>
                            <span class="text-sm truncate"><?php echo e($u['nome']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-1.5">Note</label>
                    <textarea name="note" rows="3"
                              class="w-full px-3 sm:px-4 py-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-cyan-500 outline-none resize-none text-sm sm:text-base min-h-[80px]"></textarea>
                </div>
            </form>
            
            <div class="p-4 sm:p-6 border-t border-slate-100 flex flex-row justify-end gap-2 sm:gap-3">
                <button type="button" onclick="closeModal('eventModal')" class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">
                    Annulla
                </button>
                <button type="button" onclick="saveEvent()" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg font-medium min-h-[44px] transition-colors text-sm sm:text-base">
                    Salva
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Fix per iOS - previene zoom su input */
@supports (-webkit-touch-callout: none) {
    input[type="date"],
    input[type="time"],
    input[type="text"],
    select,
    textarea {
        font-size: 16px !important;
    }
}
/* Safe area per iPhone con notch */
.pb-safe {
    padding-bottom: max(16px, env(safe-area-inset-bottom));
}
/* Celle calendario touch-friendly */
.calendar-cell {
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

/* Scrollbar per prossimi appuntamenti */
.scrollbar-thin::-webkit-scrollbar {
    height: 6px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Card prossimi appuntamenti */
.appuntamento-card {
    min-width: 250px;
    max-width: 300px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
    cursor: pointer;
}
.appuntamento-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
    transform: translateY(-2px);
}
.appuntamento-card .data-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #f1f5f9;
    color: #475569;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 9999px;
}
.appuntamento-card .cliente-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #dbeafe;
    color: #1e40af;
    font-size: 11px;
    font-weight: 500;
    padding: 3px 8px;
    border-radius: 9999px;
    margin-top: 6px;
}
</style>

<!-- Modal Lista Eventi Giorno -->
<div id="dayEventsModal" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeModal('dayEventsModal')"></div>
    <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
        <div class="bg-white w-full max-w-md sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[85vh] sm:max-h-[90vh] overflow-hidden flex flex-col">
            <div class="p-3 sm:p-5 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
                <h3 id="dayEventsTitle" class="text-sm sm:text-base font-bold text-slate-800 truncate pr-4">Eventi</h3>
                <button onclick="closeModal('dayEventsModal')" class="text-slate-400 hover:text-slate-600 p-1 min-h-[44px] min-w-[44px] flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div id="dayEventsList" class="p-3 sm:p-5 space-y-2 sm:space-y-3 overflow-y-auto flex-1">
                <!-- Popolato via JS -->
            </div>
        </div>
    </div>
</div>

<script>
let currentDate = new Date();
let eventsData = [];

const mesi = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 
              'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];

const coloriTipo = {
    appuntamento: 'bg-cyan-500',
    appuntamento_online: 'bg-blue-500',
    shooting_cliente: 'bg-pink-500',
    scadenza_task: 'bg-orange-500',
    scadenza_progetto: 'bg-purple-500',
    promemoria: 'bg-emerald-500'
};

document.addEventListener('DOMContentLoaded', function() {
    renderCalendar();
    loadEvents().then(() => {
        // Mostra gli eventi del giorno corrente
        const today = new Date();
        const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        const todayEvents = eventsData.filter(e => e.data_inizio.startsWith(todayStr));
        updateDaySidebar(todayStr, todayEvents, today);
        updateMobileDaySidebar(todayStr, todayEvents, today);
        // Carica prossimi appuntamenti
        loadProssimiAppuntamenti();
    });
    
    // Controlla se arriva dalla dashboard con data preselezionata
    const urlParams = new URLSearchParams(window.location.search);
    const newDate = urlParams.get('new');
    if (newDate) {
        openEventModalWithDate(newDate);
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    document.getElementById('currentMonth').textContent = `${mesi[month]} ${year}`;
    
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const startOffset = firstDay === 0 ? 6 : firstDay - 1;
    
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';
    
    // Giorni vuoti
    for (let i = 0; i < startOffset; i++) {
        grid.innerHTML += `<div class="h-10 sm:h-14 bg-slate-50 border-r border-b border-slate-100"></div>`;
    }
    
    // Giorni del mese
    const today = new Date();
    
    for (let day = 1; day <= daysInMonth; day++) {
        const isToday = today.getDate() === day && 
                       today.getMonth() === month && 
                       today.getFullYear() === year;
        
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        
        grid.innerHTML += `
            <div class="calendar-cell h-10 sm:h-14 p-1 sm:p-2 border-r border-b border-slate-100 hover:bg-slate-50 cursor-pointer transition-colors overflow-hidden ${isToday ? 'bg-cyan-50' : ''}"
                 onclick="showDayEvents('${dateStr}')">
                <div class="flex items-center justify-between mb-0.5 sm:mb-1">
                    <span class="text-xs sm:text-sm font-medium ${isToday ? 'text-cyan-600' : 'text-slate-700'}">${day}</span>
                    ${isToday ? '<span class="w-1.5 h-1.5 sm:w-2 sm:h-2 bg-cyan-500 rounded-full"></span>' : ''}
                </div>
                <div id="events-${dateStr}" class="flex flex-wrap gap-0.5 overflow-hidden" style="max-height: 28px;">
                    <!-- Eventi caricati via JS -->
                </div>
            </div>
        `;
    }
    
    // Calcola quante celle servono per completare la griglia
    const totalCells = startOffset + daysInMonth;
    const rows = Math.ceil(totalCells / 7);
    const targetCells = rows * 7;
    const remaining = targetCells - totalCells;
    
    for (let i = 0; i < remaining; i++) {
        grid.innerHTML += `<div class="h-10 sm:h-14 bg-slate-50 border-r border-b border-slate-100"></div>`;
    }
}

async function loadEvents() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const start = `${year}-${String(month + 1).padStart(2, '0')}-01`;
    const end = `${year}-${String(month + 1).padStart(2, '0')}-${new Date(year, month + 1, 0).getDate()}`;
    
    try {
        const response = await fetch(`api/calendario.php?action=events&start=${start}&end=${end}`);
        const data = await response.json();
        
        if (data.success) {
            const seen = new Set();
            eventsData = data.data.filter(event => {
                if (seen.has(event.id)) return false;
                seen.add(event.id);
                return true;
            });
            renderEvents();
        }
    } catch (error) {
        console.error('Errore caricamento eventi:', error);
    }
}

function renderEvents() {
    document.querySelectorAll('[id^="events-"]').forEach(el => el.innerHTML = '');
    
    const isMobile = window.innerWidth < 640;
    
    // Raggruppa eventi per data
    const eventsByDate = {};
    eventsData.forEach(event => {
        const date = event.data_inizio.split(' ')[0];
        if (!eventsByDate[date]) eventsByDate[date] = [];
        eventsByDate[date].push(event);
    });
    
    // Renderizza eventi per ogni data
    Object.keys(eventsByDate).forEach(date => {
        const container = document.getElementById(`events-${date}`);
        if (!container) return;
        
        const events = eventsByDate[date];
        const maxVisible = isMobile ? 3 : 2; // Massimo eventi visibili
        const visibleEvents = events.slice(0, maxVisible);
        const hiddenCount = events.length - maxVisible;
        
        visibleEvents.forEach(event => {
            const color = coloriTipo[event.tipo] || 'bg-slate-500';
            
            // Stile per eventi completati
            const isCompletato = event.completato == 1;
            const opacityClass = isCompletato ? 'opacity-50' : '';
            const strikethroughClass = isCompletato ? 'line-through' : '';
            const completatoIcon = isCompletato ? '✓ ' : '';
            
            // Su mobile mostriamo solo i puntini colorati
            if (isMobile) {
                container.innerHTML += `
                    <div class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full ${color} flex-shrink-0 ${opacityClass}" title="${event.titolo}${isCompletato ? ' (Completato)' : ''}"></div>
                `;
            } else {
                // Desktop: titolo + indicatore
                const titoloBreve = event.titolo.length > 12 ? event.titolo.substring(0, 12) + '...' : event.titolo;
                container.innerHTML += `
                    <div class="flex items-center gap-1 text-xs rounded ${color} text-white px-1.5 py-0.5 truncate cursor-pointer ${opacityClass}" title="${event.titolo}${isCompletato ? ' (Completato)' : ''}">
                        <span class="truncate ${strikethroughClass}">${completatoIcon}${titoloBreve}</span>
                    </div>
                `;
            }
        });
        
        // Mostra indicatore "+X" se ci sono altri eventi
        if (hiddenCount > 0) {
            container.innerHTML += `
                <div class="flex items-center justify-center text-xs text-slate-500 font-medium bg-slate-100 rounded px-1 py-0.5">
                    +${hiddenCount}
                </div>
            `;
        }
    });
}

// Ridisegna calendario quando cambia la dimensione della finestra
let resizeTimeout;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function() {
        renderCalendar();
        renderEvents();
    }, 250);
});

function changeMonth(delta) {
    currentDate.setMonth(currentDate.getMonth() + delta);
    renderCalendar();
    loadEvents();
}

function goToToday() {
    currentDate = new Date();
    renderCalendar();
    loadEvents();
}

function showDayEvents(dateStr) {
    const dayEvents = eventsData.filter(e => e.data_inizio.startsWith(dateStr));
    const date = new Date(dateStr);
    
    // Aggiorna entrambe le sidebar (desktop e mobile)
    updateDaySidebar(dateStr, dayEvents, date);
    updateMobileDaySidebar(dateStr, dayEvents, date);
    
    // Su mobile apri il modal
    if (window.innerWidth < 640) {
        openDayEventsModal(dateStr, dayEvents, date);
    }
}

function openDayEventsModal(dateStr, dayEvents, date) {
    document.getElementById('dayEventsTitle').textContent = 
        `Eventi ${date.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long' })}`;
    
    const list = document.getElementById('dayEventsList');
    
    if (dayEvents.length === 0) {
        list.innerHTML = `
            <div class="text-center py-8 text-slate-400">
                <p>Nessun evento</p>
                <button onclick="closeModal('dayEventsModal'); openEventModalWithDate('${dateStr}')" 
                        class="mt-3 text-cyan-600 hover:underline text-sm font-medium min-h-[44px] px-4 py-2">
                    + Aggiungi evento
                </button>
            </div>
        `;
    } else {
        list.innerHTML = dayEvents.map(e => {
            const color = coloriTipo[e.tipo] || 'bg-slate-500';
            const time = e.data_inizio.includes(' ') ? e.data_inizio.split(' ')[1].substring(0, 5) : '';
            const isEditable = e.id && !e.id.startsWith('task_') && !e.id.startsWith('prj_');
            const isTaskScadenza = e.id && e.id.startsWith('task_');
            const isProgettoScadenza = e.id && e.id.startsWith('prj_');
            
            // Per scadenze task mostra info dettagliate
            let dettagliExtra = '';
            if (isTaskScadenza) {
                dettagliExtra = `
                    ${e.progetto_titolo ? `<p class="text-xs text-slate-500 mt-1 truncate">📁 <strong>Progetto:</strong> ${e.progetto_titolo}</p>` : ''}
                    ${e.assegnato_nome ? `<p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full" style="background-color: ${e.assegnato_colore || '#ccc'}"></span>
                        <strong>Assegnato a:</strong> ${e.assegnato_nome}
                    </p>` : ''}
                `;
            } else if (e.progetto_titolo) {
                dettagliExtra = `<p class="text-xs text-slate-400 mt-1 truncate">📁 ${e.progetto_titolo}</p>`;
            }
            
            // Mostra cliente se presente
            if (e.cliente_nome) {
                dettagliExtra += `<p class="text-xs text-slate-500 mt-1 truncate">👤 <strong>Cliente:</strong> ${e.cliente_nome}</p>`;
            }
            
            // Mostra partecipanti se presenti
            if (e.partecipanti_dettagli && e.partecipanti_dettagli.length > 0) {
                const partecipantiHtml = e.partecipanti_dettagli.map(p => 
                    `<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs" style="background-color: ${p.colore}20; color: ${p.colore}">
                        <span class="w-1.5 h-1.5 rounded-full" style="background-color: ${p.colore}"></span>
                        ${p.nome}
                    </span>`
                ).join(' ');
                dettagliExtra += `<p class="text-xs text-slate-500 mt-1 flex flex-wrap gap-1"><strong>Partecipanti:</strong> ${partecipantiHtml}</p>`;
            }
            
            return `
                <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl">
                    <div class="w-3 h-3 rounded-full ${color} mt-1.5 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-800 text-sm">${isTaskScadenza ? e.task_titolo : e.titolo}</p>
                        <p class="text-xs text-slate-500">${isTaskScadenza ? '⏰ Scadenza Task' : (isProgettoScadenza ? '📅 Consegna Progetto' : time + ' - ' + e.tipo.replace('_', ' '))}</p>
                        ${dettagliExtra}
                        ${e.note && !isTaskScadenza ? `<p class="text-xs text-slate-400 mt-1 line-clamp-2">📝 ${e.note}</p>` : ''}
                        ${e.cliente_nome ? `<p class="text-xs text-slate-500 mt-1 truncate">👤 <strong>Cliente:</strong> ${e.cliente_nome}</p>` : ''}
                        ${e.assegnato_nome ? `<p class="text-xs text-slate-500 mt-1 flex items-center gap-1"><span class="w-2 h-2 rounded-full" style="background-color: ${e.assegnato_colore || '#ccc'}"></span><strong>Assegnato a:</strong> ${e.assegnato_nome}</p>` : ''}
                        ${e.partecipanti_dettagli && e.partecipanti_dettagli.length > 0 ? `<p class="text-xs text-slate-500 mt-1"><strong>Partecipanti:</strong> ${e.partecipanti_dettagli.map(p => p.nome).join(', ')}</p>` : ''}
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        ${isEditable && !e.completato ? `
                            <button onclick="completaEvento('${e.id}')" 
                                    class="text-slate-400 hover:text-emerald-500 transition-colors p-2 min-h-[44px] min-w-[44px] flex items-center justify-center"
                                    title="Completato">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                            <button onclick="closeModal('dayEventsModal'); openEditEventModal('${e.id}')" 
                                    class="text-slate-400 hover:text-cyan-600 transition-colors p-2 min-h-[44px] min-w-[44px] flex items-center justify-center"
                                    title="Modifica">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                        ` : isEditable ? `
                            <span class="text-emerald-500 p-2" title="Completato">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                        ` : ''}
                        <button onclick="deleteEvent('${e.id}')" 
                                class="text-slate-400 hover:text-red-500 transition-colors p-2 min-h-[44px] min-w-[44px] flex items-center justify-center"
                                title="Elimina">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    openModal('dayEventsModal');
}

function updateMobileDaySidebar(dateStr, dayEvents, date) {
    const titleEl = document.getElementById('mobileDayDetailTitle');
    const subtitleEl = document.getElementById('mobileDayDetailSubtitle');
    const contentEl = document.getElementById('mobileDayDetailContent');
    
    if (!titleEl || !contentEl) return;
    
    const giornoSettimana = date.toLocaleDateString('it-IT', { weekday: 'long' });
    const giornoMese = date.toLocaleDateString('it-IT', { day: 'numeric', month: 'long' });
    titleEl.textContent = `${giornoSettimana.charAt(0).toUpperCase() + giornoSettimana.slice(1)} ${giornoMese}`;
    
    if (dayEvents.length === 0) {
        subtitleEl.textContent = 'Nessun evento programmato';
        contentEl.innerHTML = `
            <div class="text-center py-4 text-slate-400">
                <p class="text-xs mb-2">Nessun evento per questa data</p>
                <button onclick="openEventModalWithDate('${dateStr}')" 
                        class="text-cyan-600 hover:text-cyan-700 font-medium text-xs min-h-[44px] px-4 py-2">
                    + Aggiungi evento
                </button>
            </div>
        `;
    } else {
        subtitleEl.textContent = `${dayEvents.length} evento${dayEvents.length > 1 ? 'i' : ''}`;
        
        contentEl.innerHTML = dayEvents.slice(0, 3).map(e => {
            const color = coloriTipo[e.tipo] || 'bg-slate-500';
            const time = e.data_inizio.includes(' ') ? e.data_inizio.split(' ')[1].substring(0, 5) : '';
            const isTaskScadenza = e.id && e.id.startsWith('task_');
            
            // Info extra per task
            let taskInfo = '';
            if (isTaskScadenza) {
                taskInfo = `
                    ${e.progetto_titolo ? `<span class="text-[10px] text-slate-400">📁 ${e.progetto_titolo}</span>` : ''}
                    ${e.assegnato_nome ? `<span class="text-[10px] text-slate-400 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full" style="background-color: ${e.assegnato_colore || '#ccc'}"></span>${e.assegnato_nome}</span>` : ''}
                `;
            }
            
            // Cliente, assegnatario e partecipanti
            if (e.cliente_nome) {
                taskInfo += `<span class="text-[10px] text-slate-400">👤 ${e.cliente_nome}</span>`;
            }
            if (e.assegnato_nome && !isTaskScadenza) {
                taskInfo += `<span class="text-[10px] text-slate-400 flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full" style="background-color: ${e.assegnato_colore || '#ccc'}"></span>${e.assegnato_nome}</span>`;
            }
            if (e.partecipanti_dettagli && e.partecipanti_dettagli.length > 0) {
                const partecipantiText = e.partecipanti_dettagli.map(p => p.nome).join(', ');
                taskInfo += `<span class="text-[10px] text-slate-400">👥 ${partecipantiText}</span>`;
            }
            
            return `
                <div class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg">
                    <div class="w-2 h-2 rounded-full ${color} flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-800 truncate">${isTaskScadenza ? e.task_titolo : e.titolo}</p>
                        <p class="text-xs text-slate-500">${isTaskScadenza ? 'Scadenza Task' : time}</p>
                        ${taskInfo ? `<div class="flex flex-wrap gap-1 mt-0.5">${taskInfo}</div>` : ''}
                    </div>
                </div>
            `;
        }).join('') + (dayEvents.length > 3 ? `
            <button onclick="openModal('dayEventsModal')" class="w-full text-center text-xs text-cyan-600 py-2 min-h-[44px]">
                + ${dayEvents.length - 3} altri eventi
            </button>
        ` : '');
    }
}

function updateDaySidebar(dateStr, dayEvents, date) {
    const titleEl = document.getElementById('dayDetailTitle');
    const subtitleEl = document.getElementById('dayDetailSubtitle');
    const contentEl = document.getElementById('dayDetailContent');
    
    if (!titleEl || !contentEl) return;
    
    const giornoSettimana = date.toLocaleDateString('it-IT', { weekday: 'long' });
    const giornoMese = date.toLocaleDateString('it-IT', { day: 'numeric', month: 'long' });
    titleEl.textContent = `${giornoSettimana.charAt(0).toUpperCase() + giornoSettimana.slice(1)} ${giornoMese}`;
    
    if (dayEvents.length === 0) {
        subtitleEl.textContent = 'Nessun evento programmato';
        contentEl.innerHTML = `
            <div class="text-center py-8 text-slate-400">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm mb-3">Nessun evento per questa data</p>
                <button onclick="openEventModalWithDate('${dateStr}')" 
                        class="text-cyan-600 hover:text-cyan-700 font-medium text-sm min-h-[44px] px-4 py-2">
                    + Aggiungi evento
                </button>
            </div>
        `;
    } else {
        subtitleEl.textContent = `${dayEvents.length} evento${dayEvents.length > 1 ? 'i' : ''} programmato${dayEvents.length > 1 ? 'i' : ''}`;
        
        contentEl.innerHTML = dayEvents.map(e => {
            const color = coloriTipo[e.tipo] || 'bg-slate-500';
            const time = e.data_inizio.includes(' ') ? e.data_inizio.split(' ')[1].substring(0, 5) : '';
            const isEditable = e.id && !e.id.startsWith('task_') && !e.id.startsWith('prj_');
            const isTaskScadenza = e.id && e.id.startsWith('task_');
            const isProgettoScadenza = e.id && e.id.startsWith('prj_');
            
            // Per scadenze task, mostra info dettagliate
            let dettagliTask = '';
            if (isTaskScadenza) {
                dettagliTask = `
                    ${e.progetto_titolo ? `<p class="text-xs text-slate-500 mt-1">📁 <strong>Progetto:</strong> ${e.progetto_titolo}</p>` : ''}
                    ${e.assegnato_nome ? `<p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full" style="background-color: ${e.assegnato_colore || '#ccc'}"></span>
                        <strong>Assegnato a:</strong> ${e.assegnato_nome}
                    </p>` : ''}
                `;
            }
            
            let partecipantiHtml = '';
            if (e.partecipanti_list && e.partecipanti_list.length > 0) {
                const partecipantiAvatars = e.partecipanti_list.map(p => {
                    const color = p.colore || '#94A3B8';
                    if (p.avatar) {
                        return `<div class="w-6 h-6 rounded-full overflow-hidden border-2 border-white -ml-2 first:ml-0" title="${p.nome}"><img src="assets/uploads/avatars/${p.avatar}" alt="${p.nome}" class="w-full h-full object-cover"></div>`;
                    }
                    const initial = p.nome.charAt(0).toUpperCase();
                    return `<div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-medium -ml-2 first:ml-0 border-2 border-white" style="background-color: ${color}" title="${p.nome}">${initial}</div>`;
                }).join('');
                partecipantiHtml = `<div class="flex pl-2 mt-2">${partecipantiAvatars}</div>`;
            }
            
            let progettoHtml = '';
            let progettoId = e.progetto_id || '';
            if (e.progetto_titolo) {
                progettoHtml = `
                    <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span class="truncate">${e.progetto_titolo}</span>
                    </div>`;
            }
            
            // Cliente
            let clienteHtml = '';
            if (e.cliente_nome) {
                clienteHtml = `
                    <div class="flex items-center gap-2 mt-1 text-xs text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="truncate">${e.cliente_nome}</span>
                    </div>`;
            }
            
            // Assegnatario
            let assegnatarioHtml = '';
            if (e.assegnato_nome) {
                assegnatarioHtml = `
                    <div class="flex items-center gap-2 mt-1 text-xs text-slate-500">
                        <span class="w-2 h-2 rounded-full" style="background-color: ${e.assegnato_colore || '#ccc'}"></span>
                        <span class="truncate"><strong>Assegnato a:</strong> ${e.assegnato_nome}</span>
                    </div>`;
            }
            
            // Partecipanti (usa partecipanti_dettagli se disponibile, altrimenti partecipanti_list)
            let partecipantiData = e.partecipanti_dettagli || e.partecipanti_list || [];
            if (partecipantiData.length > 0) {
                const partecipantiAvatars = partecipantiData.map(p => {
                    const color = p.colore || '#94A3B8';
                    if (p.avatar) {
                        return `<div class="w-6 h-6 rounded-full overflow-hidden border-2 border-white -ml-2 first:ml-0" title="${p.nome}"><img src="assets/uploads/avatars/${p.avatar}" alt="${p.nome}" class="w-full h-full object-cover"></div>`;
                    }
                    const initial = p.nome.charAt(0).toUpperCase();
                    return `<div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-medium -ml-2 first:ml-0 border-2 border-white" style="background-color: ${color}" title="${p.nome}">${initial}</div>`;
                }).join('');
                partecipantiHtml = `<div class="flex pl-2 mt-2">${partecipantiAvatars}</div>`;
            }
            
            return `
                <div class="bg-white border border-slate-200 rounded-xl p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-3">
                        <div class="w-3 h-3 rounded-full ${color} mt-1.5 flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <h4 class="font-semibold text-slate-800 text-sm">${isTaskScadenza ? e.task_titolo : e.titolo}</h4>
                                <span class="text-xs font-medium text-slate-500 whitespace-nowrap">${time}</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1 capitalize">${isTaskScadenza ? '⏰ Scadenza Task' : (isProgettoScadenza ? '📅 Consegna Progetto' : e.tipo.replace('_', ' '))}</p>
                            ${dettagliTask}
                            ${progettoHtml}
                            ${clienteHtml}
                            ${assegnatarioHtml}
                            ${partecipantiHtml}
                            ${e.note && !isTaskScadenza ? `<div class="mt-2 text-sm text-slate-600 bg-slate-50 p-2 rounded-lg">${e.note}</div>` : ''}
                        </div>
                    </div>
                    ${isEditable ? `
                        <div class="flex items-center justify-end gap-2 mt-3 pt-3 border-t border-slate-100">
                            ${!e.completato ? `
                            <button onclick="completaEvento('${e.id}')" 
                                    class="text-xs text-emerald-600 hover:text-emerald-700 font-medium px-3 py-2 hover:bg-emerald-50 rounded-lg transition-colors min-h-[36px]" title="Segna come completato">
                                ✓ Completato
                            </button>
                            ` : `
                            <span class="text-xs text-emerald-600 font-medium px-3 py-2 bg-emerald-50 rounded-lg min-h-[36px] flex items-center">
                                ✓ Completato
                            </span>
                            `}
                            <button onclick="openEditEventModal('${e.id}')" 
                                    class="text-xs text-cyan-600 hover:text-cyan-700 font-medium px-3 py-2 hover:bg-cyan-50 rounded-lg transition-colors min-h-[36px]">
                                Modifica
                            </button>
                            <button onclick="deleteEvent('${e.id}')" 
                                    class="text-xs text-red-600 hover:text-red-700 font-medium px-3 py-2 hover:bg-red-50 rounded-lg transition-colors min-h-[36px]">
                                Elimina
                            </button>
                        </div>
                    ` : (progettoId ? `
                        <div class="flex items-center justify-end gap-2 mt-3 pt-3 border-t border-slate-100">
                            <a href="progetto_dettaglio.php?id=${progettoId}" 
                               class="text-xs text-cyan-600 hover:text-cyan-700 font-medium px-3 py-2 hover:bg-cyan-50 rounded-lg transition-colors">
                                Vedi Progetto →
                            </a>
                        </div>
                    ` : '')}
                </div>
            `;
        }).join('');
    }
}

function openEventModal() {
    document.getElementById('eventForm').reset();
    document.getElementById('eventId').value = '';
    document.getElementById('eventModalTitle').textContent = 'Nuovo Evento';
    openModal('eventModal');
}

function openEventModalWithDate(dateStr) {
    document.getElementById('eventForm').reset();
    document.getElementById('eventId').value = '';
    document.getElementById('eventModalTitle').textContent = 'Nuovo Evento';
    document.getElementById('eventDataInizioDate').value = dateStr;
    document.getElementById('eventDataInizioTime').value = '09:00';
    openModal('eventModal');
}

async function openEditEventModal(eventId) {
    const event = eventsData.find(e => e.id === eventId);
    if (!event) {
        showToast('Evento non trovato', 'error');
        return;
    }
    
    // DEBUG
    console.log('=== DEBUG EVENT OBJECT ===', event);
    console.log('cliente_id:', event.cliente_id, 'type:', typeof event.cliente_id);
    console.log('==========================');
    
    document.getElementById('eventForm').reset();
    document.getElementById('eventId').value = event.id;
    document.getElementById('eventModalTitle').textContent = 'Modifica Evento';
    
    document.querySelector('[name="titolo"]').value = event.titolo;
    document.querySelector('[name="tipo"]').value = event.tipo || 'appuntamento';
    document.querySelector('[name="note"]').value = event.note || '';
    
    // Popola cliente se presente
    const clienteSelect = document.getElementById('eventClienteId');
    if (clienteSelect) {
        console.log('DEBUG cliente_id evento:', event.cliente_id);
        console.log('DEBUG opzioni disponibili:', Array.from(clienteSelect.options).map(o => o.value));
        
        // Imposta direttamente il valore
        if (event.cliente_id) {
            clienteSelect.value = event.cliente_id;
            console.log('DEBUG valore impostato:', clienteSelect.value);
            
            // Se il valore non è stato impostato, cerca l'opzione manualmente
            if (clienteSelect.value !== event.cliente_id) {
                const option = Array.from(clienteSelect.options).find(o => o.value === event.cliente_id);
                if (option) {
                    clienteSelect.selectedIndex = option.index;
                    console.log('DEBUG impostato manualmente via index');
                } else {
                    console.log('DEBUG cliente_id non trovato nelle opzioni!');
                }
            }
        } else {
            clienteSelect.value = '';
        }
    }
    
    // Popola partecipanti (checkbox)
    const partecipantiCheckboxes = document.querySelectorAll('input[name="partecipanti[]"]');
    partecipantiCheckboxes.forEach(cb => cb.checked = false); // Reset
    if (event.partecipanti && Array.isArray(event.partecipanti)) {
        event.partecipanti.forEach(pid => {
            const cb = document.querySelector(`input[name="partecipanti[]"][value="${pid}"]`);
            if (cb) cb.checked = true;
        });
    }
    
    const dataInizio = event.data_inizio.split(' ');
    document.getElementById('eventDataInizioDate').value = dataInizio[0];
    document.getElementById('eventDataInizioTime').value = dataInizio[1] ? dataInizio[1].substring(0, 5) : '09:00';
    
    if (event.data_fine) {
        const dataFine = event.data_fine.split(' ');
        document.getElementById('eventDataFineDate').value = dataFine[0];
        document.getElementById('eventDataFineTime').value = dataFine[1] ? dataFine[1].substring(0, 5) : '';
    }
    
    openModal('eventModal');
}

function combineDateTime() {
    const startDate = document.getElementById('eventDataInizioDate').value;
    const startTime = document.getElementById('eventDataInizioTime').value;
    const endDate = document.getElementById('eventDataFineDate').value;
    const endTime = document.getElementById('eventDataFineTime').value;
    
    if (startDate && startTime) {
        document.getElementById('eventDataInizio').value = startDate + 'T' + startTime;
    }
    if (endDate && endTime) {
        document.getElementById('eventDataFine').value = endDate + 'T' + endTime;
    }
}

// Carica prossimi appuntamenti (7 giorni)
async function loadProssimiAppuntamenti() {
    const container = document.getElementById('prossimiAppuntamenti');
    const countBadge = document.getElementById('prossimiCount');
    
    if (!container) return;
    
    try {
        // Calcola date: oggi e +7 giorni
        const today = new Date();
        const nextWeek = new Date(today);
        nextWeek.setDate(today.getDate() + 7);
        
        const startStr = today.toISOString().split('T')[0];
        const endStr = nextWeek.toISOString().split('T')[0];
        
        const response = await fetch(`api/calendario.php?action=events&start=${startStr}&end=${endStr}`);
        const data = await response.json();
        
        if (!data.success || !data.data || data.data.length === 0) {
            container.innerHTML = `
                <div class="text-center py-6 text-slate-400 text-sm w-full">
                    <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Nessun appuntamento nei prossimi 7 giorni
                </div>
            `;
            if (countBadge) countBadge.textContent = '0';
            return;
        }
        
        // Filtra solo eventi futuri (da oggi in poi), escludi scadenze progetti e completati
        const now = new Date();
        const appuntamenti = data.data
            .filter(e => {
                const eventDate = new Date(e.data_inizio);
                return eventDate >= new Date(today.setHours(0,0,0,0)) && 
                       e.tipo !== 'scadenza_progetto' &&
                       !e.id.startsWith('prj_') &&
                       !e.completato;
            })
            .sort((a, b) => new Date(a.data_inizio) - new Date(b.data_inizio))
            .slice(0, 10); // Massimo 10 eventi
        
        if (countBadge) countBadge.textContent = appuntamenti.length;
        
        if (appuntamenti.length === 0) {
            container.innerHTML = `
                <div class="text-center py-6 text-slate-400 text-sm w-full">
                    Nessun appuntamento nei prossimi 7 giorni
                </div>
            `;
            return;
        }
        
        // Genera HTML delle card
        const html = appuntamenti.map(event => {
            const eventDate = new Date(event.data_inizio);
            const dateStr = eventDate.toLocaleDateString('it-IT', { 
                weekday: 'short', 
                day: 'numeric', 
                month: 'short' 
            });
            const timeStr = eventDate.toLocaleTimeString('it-IT', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            const colorClass = coloriTipo[event.tipo] || 'bg-gray-500';
            
            // Nome cliente
            const clienteNome = event.cliente_nome || '';
            
            return `
                <div class="appuntamento-card" onclick="openEditEventModal('${event.id}')">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full ${colorClass}"></span>
                            <span class="text-xs font-medium text-slate-600">${dateStr}</span>
                        </div>
                        <span class="data-badge">${timeStr}</span>
                    </div>
                    <h4 class="font-semibold text-slate-800 text-sm truncate">${escapeHtml(event.titolo)}</h4>
                    ${clienteNome ? `<div class="cliente-badge"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>${escapeHtml(clienteNome)}</div>` : ''}
                </div>
            `;
        }).join('');
        
        container.innerHTML = html;
        
    } catch (error) {
        console.error('Errore caricamento prossimi appuntamenti:', error);
        container.innerHTML = `
            <div class="text-center py-6 text-slate-400 text-sm w-full">
                Errore caricamento appuntamenti
            </div>
        `;
    }
}

async function saveEvent() {
    combineDateTime();
    
    const form = document.getElementById('eventForm');
    const formData = new FormData(form);
    
    // DEBUG: Controlla direttamente il select
    const clienteSelect = document.getElementById('eventClienteId');
    console.log('DEBUG SELECT cliente_id value:', clienteSelect ? clienteSelect.value : 'SELECT NON TROVATO');
    
    // Log per debug - TUTTI i campi
    console.log('=== DEBUG FORM DATA ===');
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value);
    }
    console.log('========================');
    
    const eventId = document.getElementById('eventId').value;
    
    const titolo = formData.get('titolo');
    const dataInizio = formData.get('data_inizio');
    
    if (!titolo || titolo.trim() === '') {
        showToast('Inserisci il titolo', 'error');
        return;
    }
    
    if (!dataInizio || dataInizio.trim() === '') {
        showToast('Seleziona la data di inizio', 'error');
        return;
    }
    
    const isUpdate = !!eventId;
    
    if (isUpdate) {
        formData.append('id', eventId);
    }
    
    try {
        const url = isUpdate ? 'api/calendario.php?action=update' : 'api/calendario.php?action=create';
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        // Log per debug
        const responseText = await response.text();
        console.log('Response:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            showToast('Errore risposta server: ' + responseText.substring(0, 100), 'error');
            return;
        }
        
        // DEBUG: Mostra la risposta completa
        console.log('=== SERVER RESPONSE ===', data);
        if (data.data && data.data.debug) {
            console.log('=== DEBUG INFO ===', data.data.debug);
        }
        
        if (data.success) {
            showToast(isUpdate ? 'Evento aggiornato' : 'Evento creato', 'success');
            closeModal('eventModal');
            form.reset();
            location.reload();
        } else {
            showToast(data.message || 'Errore salvataggio', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Errore di connessione: ' + error.message, 'error');
    }
}

async function deleteEvent(eventId) {
    if (!eventId) {
        showToast('ID evento mancante', 'error');
        return;
    }
    
    confirmAction('Eliminare questo evento?', async () => {
        try {
            const response = await fetch('api/calendario.php?action=delete&id=' + encodeURIComponent(eventId), {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Evento eliminato', 'success');
                closeModal('dayEventsModal');
                location.reload();
            } else {
                showToast(data.message || 'Errore eliminazione', 'error');
            }
        } catch (error) {
            showToast('Errore di connessione', 'error');
        }
    });
}

async function completaEvento(eventId) {
    if (!eventId) {
        showToast('ID evento mancante', 'error');
        return;
    }
    
    try {
        const response = await fetch('api/calendario.php?action=complete&id=' + encodeURIComponent(eventId), {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Appuntamento completato', 'success');
            location.reload();
        } else {
            showToast(data.message || 'Errore completamento', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

// Utility: escape HTML per sicurezza
function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
