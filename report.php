<?php
/**
 * Eterea Gestionale
 * Report e Analisi
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Report e Analisi';

include __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Report e Analisi</h1>
    <p class="text-slate-500 mt-1">Statistiche dettagliate del team e dei progetti</p>
</div>

<!-- Toolbar -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div class="flex gap-2">
        <button onclick="exportReportPDF()" class="flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Esporta PDF
        </button>
    </div>
    <button onclick="loadReportData()" class="flex items-center gap-2 px-4 py-2 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-lg font-medium transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Aggiorna Dati
    </button>
</div>

<!-- Tab Navigation -->
<div class="bg-white rounded-t-2xl shadow-sm border border-slate-200 border-b-0 overflow-x-auto">
    <div class="flex min-w-max">
        <button onclick="switchReportTab('dashboard')" id="tab-dashboard" class="report-tab active px-6 py-4 text-sm font-medium border-b-2 border-indigo-500 text-indigo-600 bg-indigo-50/50">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                Dashboard
            </span>
        </button>
        <button onclick="switchReportTab('utenti')" id="tab-utenti" class="report-tab px-6 py-4 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Team
            </span>
        </button>
        <button onclick="switchReportTab('progetti')" id="tab-progetti" class="report-tab px-6 py-4 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                Progetti
            </span>
        </button>
        <button onclick="switchReportTab('economico')" id="tab-economico" class="report-tab px-6 py-4 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Economico
            </span>
        </button>
        <button onclick="switchReportTab('temporale')" id="tab-temporale" class="report-tab px-6 py-4 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                Andamento
            </span>
        </button>
    </div>
</div>

<!-- Content -->
<div class="bg-white rounded-b-2xl shadow-sm border border-slate-200 p-5 sm:p-6">
    
    <!-- TAB: DASHBOARD -->
    <div id="report-content-dashboard" class="report-content">
        <!-- KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl p-4 text-white">
                <div class="text-indigo-100 text-sm mb-1">Progetti Totali</div>
                <div class="text-2xl font-bold" id="kpi-progetti-totali">-</div>
                <div class="text-indigo-200 text-xs mt-1"><span id="kpi-progetti-attivi">-</span> in corso</div>
            </div>
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white">
                <div class="text-emerald-100 text-sm mb-1">Task Completate</div>
                <div class="text-2xl font-bold" id="kpi-task-completate">-</div>
                <div class="text-emerald-200 text-xs mt-1">su <span id="kpi-task-totali">-</span> totali</div>
            </div>
            <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl p-4 text-white">
                <div class="text-amber-100 text-sm mb-1">Ore Lavorate</div>
                <div class="text-2xl font-bold" id="kpi-ore-lavorate">-</div>
                <div class="text-amber-200 text-xs mt-1">registrate totali</div>
            </div>
            <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl p-4 text-white">
                <div class="text-rose-100 text-sm mb-1">Costi Task</div>
                <div class="text-2xl font-bold" id="kpi-costi-totali">-</div>
                <div class="text-rose-200 text-xs mt-1">€ calcolati</div>
            </div>
        </div>
        
        <!-- Charts Row 1 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-slate-50 rounded-xl p-4">
                <h4 class="font-semibold text-slate-700 mb-4">Distribuzione Progetti</h4>
                <div class="h-64 relative">
                    <canvas id="chart-progetti-stato"></canvas>
                </div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4">
                <h4 class="font-semibold text-slate-700 mb-4">Stato Task</h4>
                <div class="h-64 relative">
                    <canvas id="chart-task-stato"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Quick Stats Row -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-slate-50 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-indigo-600" id="quick-budget">-</div>
                <div class="text-slate-500 text-sm">Budget Progetti</div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-emerald-600" id="quick-utenti">-</div>
                <div class="text-slate-500 text-sm">Utenti Attivi (30gg)</div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4 text-center">
                <div class="text-3xl font-bold text-cyan-600" id="quick-task-dafare">-</div>
                <div class="text-slate-500 text-sm">Task Da Fare</div>
            </div>
        </div>
    </div>
    
    <!-- TAB: UTENTI -->
    <div id="report-content-utenti" class="report-content hidden">
        <div class="mb-4 flex flex-col sm:flex-row gap-3">
            <select id="filter-utenti-periodo" onchange="loadUtentiReport()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="7">Ultimi 7 giorni</option>
                <option value="30" selected>Ultimi 30 giorni</option>
                <option value="90">Ultimi 3 mesi</option>
                <option value="365">Ultimo anno</option>
            </select>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-slate-50 rounded-xl p-4">
                <h4 class="font-semibold text-slate-700 mb-4">Ore Lavorate per Membro</h4>
                <div class="h-64 relative">
                    <canvas id="chart-utenti-ore"></canvas>
                </div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4">
                <h4 class="font-semibold text-slate-700 mb-4">Efficienza Team (% Task Completate)</h4>
                <div class="h-64 relative">
                    <canvas id="chart-utenti-efficienza"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tabella Utenti -->
        <div class="bg-slate-50 rounded-xl overflow-hidden">
            <div class="px-4 py-3 bg-slate-100 border-b border-slate-200">
                <h4 class="font-semibold text-slate-700">Dettaglio Team</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">Membro</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-600">Progetti</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-600">Task</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-600">Completate</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-600">Efficienza</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-600">Ore</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600">Costo</th>
                        </tr>
                    </thead>
                    <tbody id="table-utenti-body" class="divide-y divide-slate-100 bg-white">
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Caricamento...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- TAB: PROGETTI -->
    <div id="report-content-progetti" class="report-content hidden">
        <div class="mb-4 flex flex-col sm:flex-row gap-3">
            <select id="filter-progetti-stato" onchange="loadProgettiReport()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="tutti">Tutti gli stati</option>
                <option value="in_corso">In corso</option>
                <option value="completato">Completati</option>
                <option value="archiviato">Archiviati</option>
            </select>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-slate-50 rounded-xl p-4">
                <h4 class="font-semibold text-slate-700 mb-4">Budget vs Costi (Top 8)</h4>
                <div class="h-64 relative">
                    <canvas id="chart-progetti-budget"></canvas>
                </div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4">
                <h4 class="font-semibold text-slate-700 mb-4">Avanzamento Progetti</h4>
                <div class="h-64 relative">
                    <canvas id="chart-progetti-avanzamento"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tabella Progetti -->
        <div class="bg-slate-50 rounded-xl overflow-hidden">
            <div class="px-4 py-3 bg-slate-100 border-b border-slate-200">
                <h4 class="font-semibold text-slate-700">Dettaglio Progetti</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">Progetto</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">Cliente</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-600">Stato</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-600">Task</th>
                            <th class="px-4 py-3 text-center font-medium text-slate-600">Avanzamento</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600">Budget</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600">Costo</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600">Margine</th>
                        </tr>
                    </thead>
                    <tbody id="table-progetti-body" class="divide-y divide-slate-100 bg-white">
                        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">Caricamento...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- TAB: ECONOMICO -->
    <div id="report-content-economico" class="report-content hidden">
        <div class="mb-4 flex flex-col sm:flex-row gap-3">
            <select id="filter-economico-anno" onchange="loadEconomicoReport()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="2026" selected>2026</option>
                <option value="2025">2025</option>
            </select>
        </div>
        
        <!-- Riepilogo -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-emerald-50 rounded-xl p-6 text-center">
                <div class="text-emerald-600 text-sm mb-2">Entrate Totali</div>
                <div class="text-3xl font-bold text-emerald-700" id="eco-entrate">-</div>
            </div>
            <div class="bg-rose-50 rounded-xl p-6 text-center">
                <div class="text-rose-600 text-sm mb-2">Uscite Totali</div>
                <div class="text-3xl font-bold text-rose-700" id="eco-uscite">-</div>
            </div>
            <div class="bg-indigo-50 rounded-xl p-6 text-center">
                <div class="text-indigo-600 text-sm mb-2">Saldo</div>
                <div class="text-3xl font-bold" id="eco-saldo">-</div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-slate-50 rounded-xl p-4">
                <h4 class="font-semibold text-slate-700 mb-4">Entrate/Uscite Mensili</h4>
                <div class="h-64 relative">
                    <canvas id="chart-economico-mensile"></canvas>
                </div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4">
                <h4 class="font-semibold text-slate-700 mb-4">Costi per Progetto (Top 10)</h4>
                <div class="h-64 relative">
                    <canvas id="chart-economico-progetti"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- TAB: TEMPORALE -->
    <div id="report-content-temporale" class="report-content hidden">
        <div class="mb-4 flex flex-col sm:flex-row gap-3">
            <select id="filter-temporale-mesi" onchange="loadTemporaleReport()" class="px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <option value="3">Ultimi 3 mesi</option>
                <option value="6" selected>Ultimi 6 mesi</option>
                <option value="12">Ultimo anno</option>
            </select>
        </div>
        
        <!-- Statistiche -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-indigo-50 rounded-xl p-6 text-center">
                <div class="text-4xl font-bold text-indigo-600" id="temp-task-totali">-</div>
                <div class="text-indigo-500 text-sm mt-1">Task Completate</div>
            </div>
            <div class="bg-cyan-50 rounded-xl p-6 text-center">
                <div class="text-4xl font-bold text-cyan-600" id="temp-ore-totali">-</div>
                <div class="text-cyan-500 text-sm mt-1">Ore Lavorate</div>
            </div>
            <div class="bg-emerald-50 rounded-xl p-6 text-center">
                <div class="text-4xl font-bold text-emerald-600" id="temp-progetti-nuovi">-</div>
                <div class="text-emerald-500 text-sm mt-1">Nuovi Progetti</div>
            </div>
        </div>
        
        <div class="bg-slate-50 rounded-xl p-4">
            <h4 class="font-semibold text-slate-700 mb-4">Andamento nel Tempo</h4>
            <div class="h-80 relative">
                <canvas id="chart-temporale-andamento"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
let reportCharts = {};

// Switch tra tab
function switchReportTab(tabName) {
    document.querySelectorAll('.report-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.report-tab').forEach(el => {
        el.classList.remove('active', 'border-indigo-500', 'text-indigo-600', 'bg-indigo-50/50');
        el.classList.add('border-transparent', 'text-slate-500');
    });
    
    document.getElementById(`report-content-${tabName}`).classList.remove('hidden');
    const activeTab = document.getElementById(`tab-${tabName}`);
    activeTab.classList.add('active', 'border-indigo-500', 'text-indigo-600', 'bg-indigo-50/50');
    activeTab.classList.remove('border-transparent', 'text-slate-500');
    
    switch(tabName) {
        case 'dashboard': loadDashboardStats(); break;
        case 'utenti': loadUtentiReport(); break;
        case 'progetti': loadProgettiReport(); break;
        case 'economico': loadEconomicoReport(); break;
        case 'temporale': loadTemporaleReport(); break;
    }
}

function loadReportData() {
    const activeTab = document.querySelector('.report-tab.active');
    if (activeTab) {
        const tabName = activeTab.id.replace('tab-', '');
        switchReportTab(tabName);
    }
}

async function loadDashboardStats() {
    try {
        const response = await fetch('api/report.php?action=dashboard');
        
        if (!response.ok) {
            console.error('Errore HTTP:', response.status);
            // Mostra dati vuoti o default
            return;
        }
        const text = await response.text();
        if (!text) {
            console.error('Risposta vuota');
            return;
        }
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON non valido:', text);
            return;
        }
        
        if (!data.success) {
            console.error('API error:', data.message || 'Errore sconosciuto');
            return;
        }
        
        const s = data.data || {};
        
        // Fallback per dati mancanti
        const progetti = s.progetti || {};
        const task = s.task || {};
        const tempo = s.tempo || {};
        const economico = s.economico || {};
        
        // Verifica esistenza elementi DOM prima di usarli
        const kpiProgettiTotali = document.getElementById('kpi-progetti-totali');
        const kpiProgettiAttivi = document.getElementById('kpi-progetti-attivi');
        const kpiTaskCompletate = document.getElementById('kpi-task-completate');
        const kpiTaskTotali = document.getElementById('kpi-task-totali');
        const kpiOreLavorate = document.getElementById('kpi-ore-lavorate');
        const kpiCostiTotali = document.getElementById('kpi-costi-totali');
        const quickBudget = document.getElementById('quick-budget');
        const quickUtenti = document.getElementById('quick-utenti');
        const quickTaskDaFare = document.getElementById('quick-task-dafare');
        
        if (kpiProgettiTotali) kpiProgettiTotali.textContent = progetti.totale ?? '-';
        if (kpiProgettiAttivi) kpiProgettiAttivi.textContent = progetti.in_corso ?? '-';
        if (kpiTaskCompletate) kpiTaskCompletate.textContent = task.completate ?? '-';
        if (kpiTaskTotali) kpiTaskTotali.textContent = task.totale ?? '-';
        if (kpiOreLavorate) kpiOreLavorate.textContent = tempo.ore ?? '-';
        if (kpiCostiTotali) kpiCostiTotali.textContent = '€' + (economico.costi_task || 0).toLocaleString('it-IT');
        
        if (quickBudget) quickBudget.textContent = '€' + ((economico.budget_progetti || 0)/1000).toFixed(1) + 'k';
        if (quickUtenti) quickUtenti.textContent = s.utenti_attivi_30gg ?? '-';
        if (quickTaskDaFare) quickTaskDaFare.textContent = task.da_fare ?? '-';
        
        createChart('progetti-stato', 'doughnut', {
            labels: ['In Corso', 'Completati', 'Archiviati'],
            datasets: [{
                data: [progetti.in_corso || 0, progetti.completati || 0, progetti.archiviati || 0],
                backgroundColor: ['#3B82F6', '#10B981', '#64748B'],
                borderWidth: 0
            }]
        });
        
        createChart('task-stato', 'doughnut', {
            labels: ['Da Fare', 'In Lavorazione', 'Completate'],
            datasets: [{
                data: [task.da_fare || 0, task.in_lavorazione || 0, task.completate || 0],
                backgroundColor: ['#F59E0B', '#3B82F6', '#10B981'],
                borderWidth: 0
            }]
        });
    } catch (error) {
        console.error('Errore dashboard:', error);
    }
}

async function loadUtentiReport() {
    const periodo = document.getElementById('filter-utenti-periodo')?.value || 30;
    
    try {
        const response = await fetch(`api/report.php?action=utenti&periodo=${periodo}`);
        
        if (!response.ok) {
            console.error('Errore HTTP:', response.status);
            return;
        }
        const text = await response.text();
        if (!text) {
            console.error('Risposta vuota');
            return;
        }
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON non valido:', text);
            return;
        }
        
        if (!data.success) {
            console.error('API error:', data.message || 'Errore sconosciuto');
            return;
        }
        
        const utenti = data.data?.utenti || [];
        
        if (utenti.length === 0) {
            const tableBody = document.getElementById('table-utenti-body');
            if (tableBody) tableBody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Nessun dato disponibile</td></tr>';
            return;
        }
        
        createChart('utenti-ore', 'bar', {
            labels: utenti.map(u => u.nome || '-'),
            datasets: [{
                label: 'Ore Lavorate',
                data: utenti.map(u => u.tempo_ore || 0),
                backgroundColor: utenti.map(u => u.colore || '#6366f1'),
                borderRadius: 6
            }]
        }, { legend: { display: false }, scales: { y: { beginAtZero: true } } });
        
        createChart('utenti-efficienza', 'bar', {
            labels: utenti.map(u => u.nome || '-'),
            datasets: [{
                label: 'Efficienza %',
                data: utenti.map(u => u.efficienza || 0),
                backgroundColor: '#10b981',
                borderRadius: 6
            }]
        }, { legend: { display: false }, scales: { y: { beginAtZero: true, max: 100 } } });
        
        const tableBody = document.getElementById('table-utenti-body');
        if (tableBody) {
            tableBody.innerHTML = utenti.map(u => `
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold" style="background-color: ${u.colore || '#6366f1'}">${(u.nome || '?').charAt(0)}</div>
                            <span class="font-medium text-slate-700">${u.nome || '-'}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">${u.progetti_coinvolti || 0}</td>
                    <td class="px-4 py-3 text-center">${u.task_assegnate || 0}</td>
                    <td class="px-4 py-3 text-center text-emerald-600 font-medium">${u.task_completate || 0}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-16 h-2 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full ${(u.efficienza || 0) >= 80 ? 'bg-emerald-500' : (u.efficienza || 0) >= 50 ? 'bg-amber-500' : 'bg-rose-500'}" style="width: ${u.efficienza || 0}%"></div>
                            </div>
                            <span class="text-xs">${u.efficienza || 0}%</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center font-medium">${u.tempo_ore || 0}h</td>
                    <td class="px-4 py-3 text-right font-medium">€${(u.costo_generato || 0).toFixed(2)}</td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Errore utenti:', error);
    }
}

async function loadProgettiReport() {
    const stato = document.getElementById('filter-progetti-stato')?.value || 'tutti';
    
    try {
        const response = await fetch(`api/report.php?action=progetti&stato=${stato}`);
        
        if (!response.ok) {
            console.error('Errore HTTP:', response.status);
            return;
        }
        const text = await response.text();
        if (!text) {
            console.error('Risposta vuota');
            return;
        }
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON non valido:', text);
            return;
        }
        
        if (!data.success) {
            console.error('API error:', data.message || 'Errore sconosciuto');
            return;
        }
        
        const progetti = data.data?.progetti || [];
        
        if (progetti.length === 0) {
            const tableBody = document.getElementById('table-progetti-body');
            if (tableBody) tableBody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">Nessun progetto disponibile</td></tr>';
            return;
        }
        
        createChart('progetti-budget', 'bar', {
            labels: progetti.slice(0, 8).map(p => (p.titolo || '-').substring(0, 15) + '...'),
            datasets: [
                { label: 'Budget', data: progetti.slice(0, 8).map(p => p.budget || 0), backgroundColor: '#3b82f6', borderRadius: 4 },
                { label: 'Costo', data: progetti.slice(0, 8).map(p => p.costo_totale || 0), backgroundColor: '#ef4444', borderRadius: 4 }
            ]
        }, { scales: { y: { beginAtZero: true } } });
        
        createChart('progetti-avanzamento', 'bar', {
            labels: progetti.slice(0, 8).map(p => (p.titolo || '-').substring(0, 15) + '...'),
            datasets: [{
                label: '% Completamento',
                data: progetti.slice(0, 8).map(p => p.avanzamento || 0),
                backgroundColor: '#10b981',
                borderRadius: 4
            }]
        }, { indexAxis: 'y', scales: { x: { beginAtZero: true, max: 100 } } });
        
        const tableBody = document.getElementById('table-progetti-body');
        if (tableBody) {
            tableBody.innerHTML = progetti.map(p => {
                const statoClass = { 'in_corso': 'bg-blue-100 text-blue-700', 'completato': 'bg-emerald-100 text-emerald-700', 'archiviato': 'bg-slate-100 text-slate-700' }[p.stato] || 'bg-slate-100 text-slate-700';
                const margineClass = (p.margine || 0) >= 0 ? 'text-emerald-600' : 'text-rose-600';
                return `
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-medium text-slate-700">${p.titolo || '-'}</td>
                        <td class="px-4 py-3 text-slate-500">${p.cliente || '-'}</td>
                        <td class="px-4 py-3 text-center"><span class="px-2 py-1 rounded-full text-xs font-medium ${statoClass}">${(p.stato || '-').replace('_', ' ')}</span></td>
                        <td class="px-4 py-3 text-center">${p.task_completate || 0}/${p.totale_task || 0}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-16 h-2 bg-slate-200 rounded-full overflow-hidden"><div class="h-full bg-emerald-500" style="width: ${p.avanzamento || 0}%"></div></div>
                                <span class="text-xs">${p.avanzamento || 0}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">€${(p.budget || 0).toLocaleString('it-IT')}</td>
                        <td class="px-4 py-3 text-right">€${(p.costo_totale || 0).toLocaleString('it-IT')}</td>
                        <td class="px-4 py-3 text-right font-medium ${margineClass}">€${(p.margine || 0).toLocaleString('it-IT')}</td>
                    </tr>
                `;
            }).join('');
        }
    } catch (error) {
        console.error('Errore progetti:', error);
    }
}

async function loadEconomicoReport() {
    const anno = document.getElementById('filter-economico-anno')?.value || new Date().getFullYear();
    
    try {
        const response = await fetch(`api/report.php?action=economico&anno=${anno}`);
        
        if (!response.ok) {
            console.error('Errore HTTP:', response.status);
            return;
        }
        const text = await response.text();
        if (!text) {
            console.error('Risposta vuota');
            return;
        }
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON non valido:', text);
            return;
        }
        
        if (!data.success) {
            console.error('API error:', data.message || 'Errore sconosciuto');
            return;
        }
        
        const r = data.data || {};
        const totale = r.totale || {};
        
        const ecoEntrate = document.getElementById('eco-entrate');
        const ecoUscite = document.getElementById('eco-uscite');
        const ecoSaldo = document.getElementById('eco-saldo');
        
        if (ecoEntrate) ecoEntrate.textContent = '€' + (totale.entrate || 0).toLocaleString('it-IT');
        if (ecoUscite) ecoUscite.textContent = '€' + (totale.uscite || 0).toLocaleString('it-IT');
        if (ecoSaldo) {
            ecoSaldo.textContent = '€' + (totale.saldo || 0).toLocaleString('it-IT');
            ecoSaldo.className = 'text-3xl font-bold ' + ((totale.saldo || 0) >= 0 ? 'text-emerald-700' : 'text-rose-700');
        }
        
        const mesiLabels = ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'];
        const entrateData = new Array(12).fill(0);
        const usciteData = new Array(12).fill(0);
        (r.mensile || []).forEach(m => { 
            if (m.mese >= 1 && m.mese <= 12) {
                entrateData[m.mese - 1] = m.entrate || 0; 
                usciteData[m.mese - 1] = m.uscite || 0; 
            }
        });
        
        createChart('economico-mensile', 'line', {
            labels: mesiLabels,
            datasets: [
                { label: 'Entrate', data: entrateData, borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: true, tension: 0.4 },
                { label: 'Uscite', data: usciteData, borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', fill: true, tension: 0.4 }
            ]
        }, { interaction: { intersect: false, mode: 'index' } });
        
        const costiProgetto = r.costi_progetto || [];
        createChart('economico-progetti', 'bar', {
            labels: costiProgetto.map(p => (p.titolo || '-').substring(0, 12) + '...'),
            datasets: [{ label: 'Costo', data: costiProgetto.map(p => p.costo_totale || 0), backgroundColor: '#6366f1', borderRadius: 4 }]
        }, { indexAxis: 'y', plugins: { legend: { display: false } } });
    } catch (error) {
        console.error('Errore economico:', error);
    }
}

async function loadTemporaleReport() {
    const mesi = document.getElementById('filter-temporale-mesi')?.value || 6;
    
    try {
        const response = await fetch(`api/report.php?action=temporale&mesi=${mesi}`);
        
        if (!response.ok) {
            console.error('Errore HTTP:', response.status);
            return;
        }
        const text = await response.text();
        if (!text) {
            console.error('Risposta vuota');
            return;
        }
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON non valido:', text);
            return;
        }
        
        if (!data.success) {
            console.error('API error:', data.message || 'Errore sconosciuto');
            return;
        }
        
        const r = data.data || {};
        const taskCompletate = r.task_completate || [];
        const oreLavorate = r.ore_lavorate || [];
        const nuoviProgetti = r.nuovi_progetti || [];
        
        const totaliTask = taskCompletate.reduce((a, b) => a + parseInt(b.task_completate || 0), 0);
        const totaliOre = oreLavorate.reduce((a, b) => a + parseFloat(b.ore_lavorate || 0), 0);
        const totaliProgetti = nuoviProgetti.reduce((a, b) => a + parseInt(b.nuovi_progetti || 0), 0);
        
        const tempTaskTotali = document.getElementById('temp-task-totali');
        const tempOreTotali = document.getElementById('temp-ore-totali');
        const tempProgettiNuovi = document.getElementById('temp-progetti-nuovi');
        
        if (tempTaskTotali) tempTaskTotali.textContent = totaliTask;
        if (tempOreTotali) tempOreTotali.textContent = Math.round(totaliOre);
        if (tempProgettiNuovi) tempProgettiNuovi.textContent = totaliProgetti;
        
        const periodi = [...new Set([...taskCompletate.map(t => t.periodo), ...oreLavorate.map(o => o.periodo), ...nuoviProgetti.map(p => p.periodo)])].sort();
        const labels = periodi.map(p => { const [a, m] = p.split('-'); return `${m}/${a}`; });
        
        createChart('temporale-andamento', 'line', {
            labels: labels,
            datasets: [
                { label: 'Task Completate', data: periodi.map(p => { const f = taskCompletate.find(t => t.periodo === p); return f ? parseInt(f.task_completate || 0) : 0; }), borderColor: '#6366f1', backgroundColor: 'rgba(99, 102, 241, 0.1)', fill: true, tension: 0.4, yAxisID: 'y' },
                { label: 'Ore Lavorate', data: periodi.map(p => { const f = oreLavorate.find(o => o.periodo === p); return f ? parseFloat(f.ore_lavorate || 0) : 0; }), borderColor: '#06b6d4', backgroundColor: 'rgba(6, 182, 212, 0.1)', fill: true, tension: 0.4, yAxisID: 'y1' },
                { label: 'Nuovi Progetti', data: periodi.map(p => { const f = nuoviProgetti.find(n => n.periodo === p); return f ? parseInt(f.nuovi_progetti || 0) : 0; }), borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0)', fill: false, tension: 0.4, yAxisID: 'y' }
            ]
        }, {
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Quantità' } },
                y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Ore' }, grid: { drawOnChartArea: false } }
            }
        });
    } catch (error) {
        console.error('Errore temporale:', error);
    }
}

function createChart(id, type, data, customOptions = {}) {
    destroyChart(id);
    const ctx = document.getElementById(`chart-${id}`);
    if (!ctx) return;
    
    reportCharts[id] = new Chart(ctx, {
        type: type,
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
            ...customOptions
        }
    });
}

function destroyChart(chartId) {
    if (reportCharts[chartId]) {
        reportCharts[chartId].destroy();
        delete reportCharts[chartId];
    }
}

function exportReportPDF() {
    window.print();
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => loadDashboardStats(), 100);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
