<?php
/**
 * Eterea Gestionale
 * Impostazioni di sistema
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Impostazioni';

include __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-slate-800">Impostazioni</h1>
    <p class="text-slate-500 mt-1">Gestione avanzata del sistema</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <!-- Sezione: Pulizia Dati -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800">Pulizia Dati</h3>
                    <p class="text-xs sm:text-sm text-slate-500">Operazioni irreversibili</p>
                </div>
            </div>
        </div>
        
        <div class="p-5 space-y-4">
            <!-- Elimina Cronologia -->
            <div class="p-4 bg-slate-50 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-800">Elimina Cronologia</p>
                        <p class="text-xs sm:text-sm text-slate-500">Cancella tutta la timeline delle attività</p>
                    </div>
                    <button onclick="confirmDeleteCronologia()" 
                            class="px-4 py-2 bg-orange-100 hover:bg-orange-200 text-orange-700 rounded-lg font-medium transition-colors">
                        Elimina
                    </button>
                </div>
            </div>
            
            <!-- Azzera Saldi -->
            <div class="p-4 bg-slate-50 rounded-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-800">Azzera Saldi Utenti</p>
                        <p class="text-xs sm:text-sm text-slate-500">Riporta a zero tutti i wallet di tutti gli utenti</p>
                    </div>
                    <button onclick="confirmResetSaldi()" 
                            class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg font-medium transition-colors">
                        Azzera
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sezione: Backup Dati -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800">Backup Dati</h3>
                    <p class="text-xs sm:text-sm text-slate-500">Esporta in formato CSV</p>
                </div>
            </div>
        </div>
        
        <div class="p-5 space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <a href="api/impostazioni.php?action=backup&tipo=clienti" 
                   class="flex items-center justify-center gap-2 p-3 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="font-medium text-slate-700">Clienti</span>
                </a>
                
                <a href="api/impostazioni.php?action=backup&tipo=progetti" 
                   class="flex items-center justify-center gap-2 p-3 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    <span class="font-medium text-slate-700">Progetti</span>
                </a>
                
                <a href="api/impostazioni.php?action=backup&tipo=finanze" 
                   class="flex items-center justify-center gap-2 p-3 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-medium text-slate-700">Finanze</span>
                </a>
                
                <a href="api/impostazioni.php?action=backup&tipo=appuntamenti" 
                   class="flex items-center justify-center gap-2 p-3 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="font-medium text-slate-700">Appuntamenti</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Sezione: Profilo Utente -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-cyan-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800">Profilo</h3>
                    <p class="text-xs sm:text-sm text-slate-500">Gestisci la tua immagine profilo</p>
                </div>
            </div>
        </div>
        
        <div class="p-5">
            <div class="flex flex-col sm:flex-row items-center gap-6">
                <!-- Avatar Preview -->
                <div class="relative">
                    <div id="avatarPreview" class="w-24 h-24 rounded-full overflow-hidden border-4 border-slate-100 shadow-lg">
                        <?php if (!empty($currentUser['avatar']) && file_exists(__DIR__ . '/assets/uploads/avatars/' . $currentUser['avatar'])): ?>
                            <img src="assets/uploads/avatars/<?php echo e($currentUser['avatar']); ?>?v=<?php echo time(); ?>" 
                                 alt="Avatar" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-white text-2xl font-bold" 
                                 style="background-color: <?php echo e($currentUser['colore']); ?>">
                                <?php echo substr($currentUser['nome'], 0, 2); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button onclick="document.getElementById('avatarInput').click()" 
                            class="absolute -bottom-1 -right-1 w-8 h-8 bg-cyan-600 hover:bg-cyan-700 text-white rounded-full flex items-center justify-center shadow-lg transition-colors"
                            title="Cambia avatar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Upload Form -->
                <div class="flex-1 w-full">
                    <form id="avatarForm" enctype="multipart/form-data">
                        <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" 
                               class="hidden" onchange="uploadAvatar(this)">
                        <div class="text-center sm:text-left">
                            <p class="font-medium text-slate-800"><?php echo e($currentUser['nome']); ?></p>
                            <p class="text-sm text-slate-500 mb-3">Clicca sull'icona della fotocamera per cambiare avatar</p>
                            <p class="text-xs text-slate-400">Formati accettati: JPG, PNG, GIF, WEBP<br>Max 2MB - Dimensione consigliata: 400x400px</p>
                        </div>
                    </form>
                    
                    <!-- Progress bar (hidden by default) -->
                    <div id="uploadProgress" class="hidden mt-3">
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-cyan-500 rounded-full transition-all" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sezione: Personalizzazione -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800">Personalizzazione</h3>
                    <p class="text-xs sm:text-sm text-slate-500">Logo e branding del gestionale</p>
                </div>
            </div>
        </div>
        
        <div class="p-5">
            <div class="flex flex-col sm:flex-row items-center gap-6">
                <!-- Logo Preview -->
                <div class="relative">
                    <div id="logoPreview" class="w-32 h-32 rounded-xl overflow-hidden border-4 border-slate-100 shadow-lg bg-white flex items-center justify-center">
                        <div id="logoPlaceholder" class="text-center text-slate-400">
                            <svg class="w-12 h-12 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs">Nessun logo</span>
                        </div>
                        <img id="logoImg" src="" alt="Logo" class="w-full h-full object-contain hidden">
                    </div>
                    <button onclick="document.getElementById('logoInput').click()" 
                            class="absolute -bottom-1 -right-1 w-8 h-8 bg-purple-600 hover:bg-purple-700 text-white rounded-full flex items-center justify-center shadow-lg transition-colors"
                            title="Cambia logo">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Upload Form -->
                <div class="flex-1 w-full">
                    <form id="logoForm" enctype="multipart/form-data">
                        <input type="file" id="logoInput" name="logo" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,.svg" 
                               class="hidden" onchange="uploadLogo(this)">
                        <div class="text-center sm:text-left">
                            <p class="font-medium text-slate-800">Logo Gestionale</p>
                            <p class="text-sm text-slate-500 mb-3">Clicca sull'icona per cambiare il logo visualizzato nel login e nella navbar</p>
                            <p class="text-xs text-slate-400">Formati accettati: JPG, PNG, GIF, WEBP, SVG<br>Max 2MB per immagini, 5MB per SVG</p>
                        </div>
                    </form>
                    
                    <div id="logoActions" class="mt-4 flex gap-2 hidden">
                        <button onclick="rimuoviLogo()" class="px-3 py-1.5 text-sm text-red-600 hover:text-red-700 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                            Rimuovi logo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sezione: Dati Azienda -->
    <div class="md:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800">Dati Azienda</h3>
                    <p class="text-xs sm:text-sm text-slate-500">Dati per fatturazione e preventivi</p>
                </div>
                <div class="ml-auto">
                    <span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs rounded-lg font-medium">
                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Protetto
                    </span>
                </div>
            </div>
        </div>
        
        <div class="p-5">
            <!-- Stato Dati -->
            <div id="datiAziendaStatus" class="mb-5 p-4 bg-slate-50 rounded-xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div id="statusIcon" class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p id="statusText" class="font-medium text-slate-700">Dati non configurati</p>
                        <p class="text-xs text-slate-500">Inserisci i dati della tua attività</p>
                    </div>
                </div>
                <button onclick="toggleDatiAziendaForm()" 
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors text-sm">
                    Configura
                </button>
            </div>
            
            <!-- Form Dati Azienda (inizialmente nascosto) -->
            <div id="datiAziendaForm" class="hidden">
                <!-- Password Protection -->
                <div class="mb-5 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <label class="block text-sm font-medium text-amber-800 mb-2">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Password di modifica
                    </label>
                    <input type="password" id="aziendaPassword" 
                           class="w-full px-4 py-2.5 border border-amber-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none bg-white"
                           placeholder="Inserisci la password per modificare...">
                    <p class="text-xs text-amber-600 mt-1">Questa operazione richiede l'autenticazione</p>
                </div>
                
                <!-- Logo Aziendale -->
                <div class="mb-6 p-4 bg-slate-50 rounded-xl">
                    <label class="block text-sm font-medium text-slate-700 mb-3">Logo Aziendale</label>
                    <div class="flex flex-col sm:flex-row items-start gap-4">
                        <div class="relative">
                            <div id="logoAziendaPreview" class="w-32 h-32 rounded-xl overflow-hidden border-2 border-slate-200 bg-white flex items-center justify-center">
                                <div id="logoAziendaPlaceholder" class="text-center text-slate-400">
                                    <svg class="w-10 h-10 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-xs">Nessun logo</span>
                                </div>
                                <img id="logoAziendaImg" src="" alt="Logo Azienda" class="w-full h-full object-contain hidden">
                            </div>
                            <button onclick="document.getElementById('logoAziendaInput').click()" 
                                    class="absolute -bottom-2 -right-2 w-8 h-8 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full flex items-center justify-center shadow-lg transition-colors"
                                    title="Carica logo">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex-1">
                            <input type="file" id="logoAziendaInput" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,.svg" 
                                   class="hidden" onchange="uploadLogoAzienda(this)">
                            <p class="text-sm text-slate-600 mb-2">Logo per i preventivi</p>
                            <p class="text-xs text-slate-400">Formati: JPG, PNG, GIF, WEBP, SVG<br>Max 5MB</p>
                            <button type="button" onclick="rimuoviLogoAzienda()" id="btnRimuoviLogoAzienda"
                                    class="mt-3 px-3 py-1.5 text-xs text-red-600 hover:text-red-700 border border-red-200 rounded-lg hover:bg-red-50 transition-colors hidden">
                                Rimuovi logo
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Form Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ragione Sociale *</label>
                        <input type="text" id="aziendaRagioneSociale" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               placeholder="es. Eterea Studio SRL">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Indirizzo</label>
                        <input type="text" id="aziendaIndirizzo" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               placeholder="es. Via Roma 123">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">CAP</label>
                        <input type="text" id="aziendaCap" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               placeholder="es. 00100" maxlength="5">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Città</label>
                        <input type="text" id="aziendaCitta" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               placeholder="es. Roma">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Provincia</label>
                        <input type="text" id="aziendaProvincia" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               placeholder="es. RM" maxlength="2">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Partita IVA</label>
                        <input type="text" id="aziendaPiva" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               placeholder="es. 12345678901" maxlength="11">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Codice Fiscale</label>
                        <input type="text" id="aziendaCf" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               placeholder="es. RSSMRA80A01H501Z" maxlength="16">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" id="aziendaEmail" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               placeholder="es. info@etereastudio.it">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Telefono</label>
                        <input type="tel" id="aziendaTelefono" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               placeholder="es. +39 06 1234567">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">PEC</label>
                        <input type="email" id="aziendaPec" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               placeholder="es. eterea@pec.it">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Codice SDI</label>
                        <input type="text" id="aziendaSdi" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                               placeholder="es. ABC1234" maxlength="7">
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="mt-6 flex flex-row justify-end gap-2">
                    <button type="button" onclick="toggleDatiAziendaForm()" 
                            class="flex-1 sm:flex-none px-4 py-2.5 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">
                        Annulla
                    </button>
                    <button type="button" onclick="salvaDatiAzienda()" 
                            class="flex-1 sm:flex-none px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium min-h-[44px] transition-colors text-sm sm:text-base">
                        Salva Dati
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sezione: Impostazioni Tasse -->
    <div class="md:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-slate-800">Impostazioni Tasse</h3>
                    <p class="text-xs sm:text-sm text-slate-500">Codici ATECO e parametri fiscali</p>
                </div>
                <div class="ml-auto">
                    <span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs rounded-lg font-medium">
                        <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Protetto
                    </span>
                </div>
            </div>
        </div>
        
        <div class="p-5">
            <!-- Stato -->
            <div id="tasseStatus" class="mb-5 p-4 bg-slate-50 rounded-xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div id="tasseStatusIcon" class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p id="tasseStatusText" class="font-medium text-slate-700">Impostazioni tasse</p>
                        <p class="text-xs text-slate-500">Configura codici ATECO e aliquote</p>
                    </div>
                </div>
                <button onclick="toggleTasseForm()" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors text-sm">
                    Configura
                </button>
            </div>
            
            <!-- Form Impostazioni Tasse (inizialmente nascosto) -->
            <div id="tasseForm" class="hidden">
                <!-- Password Protection -->
                <div class="mb-5 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                    <label class="block text-sm font-medium text-amber-800 mb-2">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Password di modifica
                    </label>
                    <input type="password" id="tassePassword" 
                           class="w-full px-4 py-2.5 border border-amber-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none bg-white"
                           placeholder="Inserisci la password...">
                </div>
                
                <!-- Aliquote Generali -->
                <div class="mb-6 p-4 bg-slate-50 rounded-xl">
                    <h4 class="font-medium text-slate-800 mb-4">Aliquote Generali</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">INPS (%)</label>
                            <input type="number" id="tassaInps" step="0.01" min="0" max="100"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                                   placeholder="es. 25.72">
                            <p class="text-xs text-slate-500 mt-1">Contributi INPS gestione separata</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Acconto Tasse (%)</label>
                            <input type="number" id="tassaAcconto" step="0.01" min="0" max="100"
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                                   placeholder="es. 100">
                            <p class="text-xs text-slate-500 mt-1">Percentuale acconto sul netto</p>
                        </div>
                    </div>
                    <button type="button" onclick="salvaImpostazioniTasse()" 
                            class="mt-4 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors text-sm">
                        Salva Aliquote
                    </button>
                </div>
                
                <!-- Codici ATECO -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-medium text-slate-800">Codici ATECO</h4>
                        <button type="button" onclick="apriModalCodiceAteco()" 
                                class="px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-700 rounded-lg text-sm font-medium transition-colors">
                            + Aggiungi Codice
                        </button>
                    </div>
                    
                    <div id="listaCodiciAteco" class="space-y-2">
                        <p class="text-slate-400 text-center py-4">Nessun codice ATECO configurato</p>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="flex flex-row justify-end gap-2">
                    <button type="button" onclick="toggleTasseForm()" 
                            class="flex-1 sm:flex-none px-4 py-2.5 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">
                        Chiudi
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Aggiungi/Modifica Codice ATECO -->
    <div id="modalCodiceAteco" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/50" onclick="chiudiModalCodiceAteco()"></div>
        <div class="absolute inset-0 flex items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="bg-white w-full max-w-lg sm:rounded-2xl rounded-t-2xl shadow-2xl max-h-[85vh] sm:max-h-[90vh] overflow-hidden flex flex-col">
                <div class="p-4 sm:p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
                    <h3 class="text-lg sm:text-xl font-bold text-slate-800" id="titoloModalAteco">Nuovo Codice ATECO</h3>
                    <button onclick="chiudiModalCodiceAteco()" class="p-2 -mr-2 text-slate-400 hover:text-slate-600 min-h-[44px] min-w-[44px] flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4">
                    <input type="hidden" id="atecoId">
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Codice ATECO *</label>
                        <input type="text" id="atecoCodice" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                               placeholder="es. 73.11.02">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Descrizione Attività</label>
                        <input type="text" id="atecoDescrizione" 
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                               placeholder="es. Attività degli studi di progettazione">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Coefficiente di Redditività (%)</label>
                        <input type="number" id="atecoCoefficiente" step="0.01" min="0" max="100"
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                               placeholder="es. 78">
                        <p class="text-xs text-slate-500 mt-1">Percentuale del fatturato considerata reddito imponibile</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Tassazione IRPEF (%)</label>
                        <input type="number" id="atecoTassazione" step="0.01" min="0" max="100"
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                               placeholder="es. 15">
                        <p class="text-xs text-slate-500 mt-1">Aliquota fiscale applicata (flat tax o ordinaria)</p>
                    </div>
                </div>
                
                <div class="p-4 sm:p-6 border-t border-slate-100 flex flex-row justify-end gap-2 sm:gap-3">
                    <button type="button" onclick="chiudiModalCodiceAteco()" 
                            class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">
                        Annulla
                    </button>
                    <button type="button" onclick="salvaCodiceAteco()" 
                            class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium min-h-[44px] transition-colors text-sm sm:text-base">
                        Salva
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sezione: ELIMINA TUTTO (Pericolo Estremo) -->
    <div class="md:col-span-2 bg-gradient-to-r from-red-50 to-red-100 rounded-2xl shadow-sm border border-red-200 overflow-hidden">
        <div class="p-5 border-b border-red-200">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-red-800">⚠️ Zona di Pericolo - Elimina Tutto</h3>
                    <p class="text-sm text-red-600">Operazione IRREVERSIBILE - Richiede 3 livelli di conferma</p>
                </div>
            </div>
        </div>
        
        <div class="p-5">
            <div class="bg-white rounded-xl p-6 border border-red-200">
                <p class="text-slate-700 mb-4">
                    Questa operazione eliminerà <strong>TUTTI I DATI</strong> dal sistema:<br>
                    <span class="text-red-600">Appuntamenti, Saldi, Cronologia, Progetti, Clienti, Task, Transazioni</span>
                </p>
                
                <button onclick="showDeleteAllModal()" 
                        class="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                    AVVIA PROCEDURA DI ELIMINAZIONE TOTALE
                </button>
            </div>
        </div>
    </div>
    
</div>

<!-- Modal Elimina Cronologia - Conferma 1 -->
<div id="deleteCronologiaModal1" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('deleteCronologiaModal1')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="p-5 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">⚠️ Conferma Eliminazione</h3>
            </div>
            <div class="p-5">
                <p class="text-slate-600">Sei sicuro di voler eliminare tutta la cronologia delle attività?</p>
                <p class="text-sm text-red-600 mt-2">Questa operazione è irreversibile.</p>
            </div>
            <div class="p-4 sm:p-5 border-t border-slate-100 flex flex-row justify-end gap-2 sm:gap-3">
                <button onclick="closeModal('deleteCronologiaModal1')" class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">Annulla</button>
                <button onclick="showDeleteCronologiaStep2()" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium min-h-[44px] transition-colors text-sm sm:text-base">Procedi</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Elimina Cronologia - Conferma 2 -->
<div id="deleteCronologiaModal2" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('deleteCronologiaModal2')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="p-5 border-b border-slate-100">
                <h3 class="font-bold text-red-800">⚠️ ULTIMA CONFERMA</h3>
            </div>
            <div class="p-5">
                <p class="text-slate-600">Stai per eliminare definitivamente tutta la cronologia.</p>
                <div class="mt-4 p-3 bg-red-50 rounded-lg">
                    <p class="text-sm text-red-700 font-medium">Dopo questa operazione non potrai più recuperare i dati!</p>
                </div>
            </div>
            <div class="p-4 sm:p-5 border-t border-slate-100 flex flex-row justify-end gap-2 sm:gap-3">
                <button onclick="closeModal('deleteCronologiaModal2')" class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">Annulla</button>
                <button onclick="executeDeleteCronologia()" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium min-h-[44px] transition-colors text-sm sm:text-base"><span class="hidden sm:inline">ELIMINA DEFINITIVAMENTE</span><span class="sm:hidden">Elimina</span></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Azzera Saldi - Conferma 1 -->
<div id="resetSaldiModal1" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('resetSaldiModal1')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="p-5 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">⚠️ Conferma Azzeramento</h3>
            </div>
            <div class="p-5">
                <p class="text-slate-600">Sei sicuro di voler azzerare i saldi di TUTTI gli utenti?</p>
                <p class="text-sm text-red-600 mt-2">Tutti i wallet verranno portati a zero. Operazione irreversibile.</p>
            </div>
            <div class="p-4 sm:p-5 border-t border-slate-100 flex flex-row justify-end gap-2 sm:gap-3">
                <button onclick="closeModal('resetSaldiModal1')" class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">Annulla</button>
                <button onclick="showResetSaldiStep2()" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium min-h-[44px] transition-colors text-sm sm:text-base">Procedi</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Azzera Saldi - Conferma 2 -->
<div id="resetSaldiModal2" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('resetSaldiModal2')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="p-5 border-b border-slate-100">
                <h3 class="font-bold text-red-800">⚠️ ULTIMA CONFERMA</h3>
            </div>
            <div class="p-5">
                <p class="text-slate-600">Stai per azzerare definitivamente tutti i saldi.</p>
                <div class="mt-4 p-3 bg-red-50 rounded-lg">
                    <p class="text-sm text-red-700 font-medium">Questo influenzerà TUTTI gli utenti del sistema!</p>
                </div>
            </div>
            <div class="p-4 sm:p-5 border-t border-slate-100 flex flex-row justify-end gap-2 sm:gap-3">
                <button onclick="closeModal('resetSaldiModal2')" class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">Annulla</button>
                <button onclick="executeResetSaldi()" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium min-h-[44px] transition-colors text-sm sm:text-base"><span class="hidden sm:inline">AZZERA DEFINITIVAMENTE</span><span class="sm:hidden">Azzera</span></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal ELIMINA TUTTO - 3 Livelli di Sicurezza -->

<!-- Livello 1 -->
<div id="deleteAllModal1" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('deleteAllModal1')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="p-5 border-b border-red-100 bg-red-50">
                <h3 class="font-bold text-red-800">🔴 LIVELLO 1/3 - Pericolo Estremo</h3>
            </div>
            <div class="p-5">
                <p class="text-slate-700">Stai per eliminare <strong>TUTTI I DATI</strong> dal sistema:</p>
                <ul class="mt-2 text-sm text-red-600 list-disc list-inside">
                    <li>Appuntamenti</li>
                    <li>Saldi di tutti gli utenti</li>
                    <li>Cronologia timeline</li>
                    <li>Progetti</li>
                    <li>Clienti</li>
                    <li>Task</li>
                    <li>Transazioni economiche</li>
                </ul>
            </div>
            <div class="p-4 sm:p-5 border-t border-slate-100 flex flex-row justify-end gap-2 sm:gap-3">
                <button onclick="closeModal('deleteAllModal1')" class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">Annulla</button>
                <button onclick="showDeleteAllStep2()" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium min-h-[44px] transition-colors text-sm sm:text-base"><span class="hidden sm:inline">Ho capito, procedi</span><span class="sm:hidden">Procedi</span></button>
            </div>
        </div>
    </div>
</div>

<!-- Livello 2 -->
<div id="deleteAllModal2" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('deleteAllModal2')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border-2 border-red-500">
            <div class="p-5 border-b border-red-100 bg-red-50">
                <h3 class="font-bold text-red-800">🔴 LIVELLO 2/3 - ULTIMO AVVISO</h3>
            </div>
            <div class="p-5">
                <p class="text-slate-700">Questa è l'ultima schermata prima della distruzione totale.</p>
                <div class="mt-4 p-4 bg-red-100 rounded-lg border border-red-200">
                    <p class="text-red-800 font-bold text-center">⚠️ NON POTRAI MAI RECUPERARE I DATI ⚠️</p>
                </div>
            </div>
            <div class="p-4 sm:p-5 border-t border-slate-100 flex flex-row justify-end gap-2 sm:gap-3">
                <button onclick="closeModal('deleteAllModal2')" class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">Annulla</button>
                <button onclick="showDeleteAllStep3()" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg font-medium min-h-[44px] transition-colors text-sm sm:text-base"><span class="hidden sm:inline">Continua comunque</span><span class="sm:hidden">Continua</span></button>
            </div>
        </div>
    </div>
</div>

<!-- Livello 3 - Parola chiave -->
<div id="deleteAllModal3" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal('deleteAllModal3')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border-2 border-red-600">
            <div class="p-5 border-b border-red-100 bg-red-600">
                <h3 class="font-bold text-white">🔴 LIVELLO 3/3 - AUTENTICAZIONE RICHIESTA</h3>
            </div>
            <div class="p-5">
                <p class="text-slate-700 mb-4">Per procedere con l'eliminazione totale, inserisci la parola chiave di sicurezza:</p>
                
                <div class="mb-4">
                    <label class="block text-xs sm:text-sm font-medium text-slate-700 mb-2">Parola chiave</label>
                    <input type="password" id="deleteAllKeyword" 
                           class="w-full px-4 py-2.5 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 outline-none"
                           placeholder="Inserisci la parola chiave...">
                    <p class="text-xs text-slate-500 mt-1">Suggerimento: combinazione di due password che conosci...</p>
                </div>
                
                <div class="p-3 bg-red-50 rounded-lg border border-red-200">
                    <p class="text-sm text-red-800 font-medium text-center">⚠️ Dopo questa operazione il sistema sarà vuoto ⚠️</p>
                </div>
            </div>
            <div class="p-4 sm:p-5 border-t border-slate-100 flex flex-row justify-end gap-2 sm:gap-3">
                <button onclick="closeModal('deleteAllModal3')" class="flex-1 sm:flex-none px-4 py-2.5 sm:py-2 text-slate-600 hover:text-slate-800 font-medium min-h-[44px] rounded-lg hover:bg-slate-100 transition-colors text-sm sm:text-base">Annulla</button>
                <button onclick="executeDeleteAll()" class="flex-1 sm:flex-none px-4 sm:px-6 py-2.5 sm:py-2 bg-red-700 hover:bg-red-800 text-white rounded-lg font-medium min-h-[44px] transition-colors text-sm sm:text-base"><span class="hidden sm:inline">ELIMINA TUTTO</span><span class="sm:hidden">Elimina</span></button>
            </div>
        </div>
    </div>
</div>

<script>
const KEYWORD_SICUREZZA = 'Tomato2399Andromeda2399!?';

// Elimina Cronologia
function confirmDeleteCronologia() {
    openModal('deleteCronologiaModal1');
}

function showDeleteCronologiaStep2() {
    closeModal('deleteCronologiaModal1');
    openModal('deleteCronologiaModal2');
}

async function executeDeleteCronologia() {
    try {
        const response = await fetch('api/impostazioni.php?action=delete_cronologia', {
            method: 'POST'
        });
        const data = await response.json();
        
        closeModal('deleteCronologiaModal2');
        
        if (data.success) {
            showToast('Cronologia eliminata con successo', 'success');
        } else {
            showToast(data.message || 'Errore eliminazione', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

// Azzera Saldi
function confirmResetSaldi() {
    openModal('resetSaldiModal1');
}

function showResetSaldiStep2() {
    closeModal('resetSaldiModal1');
    openModal('resetSaldiModal2');
}

async function executeResetSaldi() {
    try {
        const response = await fetch('api/impostazioni.php?action=reset_saldi', {
            method: 'POST'
        });
        const data = await response.json();
        
        closeModal('resetSaldiModal2');
        
        if (data.success) {
            showToast('Saldi azzerati con successo', 'success');
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

// Upload Avatar
async function uploadAvatar(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validazione
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showToast('Formato non valido. Usa JPG, PNG, GIF o WEBP', 'error');
        return;
    }
    
    if (file.size > 2 * 1024 * 1024) {
        showToast('File troppo grande. Max 2MB', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('action', 'upload_avatar');
    
    // Mostra progress bar
    const progressDiv = document.getElementById('uploadProgress');
    const progressBar = progressDiv.querySelector('div');
    progressDiv.classList.remove('hidden');
    progressBar.style.width = '30%';
    
    try {
        const response = await fetch('api/impostazioni.php', {
            method: 'POST',
            body: formData
        });
        
        progressBar.style.width = '70%';
        
        const data = await response.json();
        
        progressBar.style.width = '100%';
        
        if (data.success) {
            showToast('Avatar aggiornato con successo!', 'success');
            // Ricarica la pagina dopo 500ms per vedere il nuovo avatar
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(data.message || 'Errore upload', 'error');
            progressDiv.classList.add('hidden');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
        progressDiv.classList.add('hidden');
    }
}

// Logo Gestionale
let currentLogo = '';
let isLogoSvg = false;

// Carica logo all'avvio
document.addEventListener('DOMContentLoaded', loadLogo);

async function loadLogo() {
    try {
        const response = await fetch('api/impostazioni.php?action=get_logo');
        const data = await response.json();
        
        if (data.success && data.data.logo) {
            currentLogo = data.data.logo;
            isLogoSvg = data.data.is_svg;
            updateLogoPreview();
        }
    } catch (error) {
        console.error('Errore caricamento logo:', error);
    }
}

function updateLogoPreview() {
    const img = document.getElementById('logoImg');
    const placeholder = document.getElementById('logoPlaceholder');
    const actions = document.getElementById('logoActions');
    
    if (currentLogo) {
        img.src = 'assets/uploads/logo/' + currentLogo;
        img.classList.remove('hidden');
        placeholder.classList.add('hidden');
        actions.classList.remove('hidden');
    } else {
        img.classList.add('hidden');
        placeholder.classList.remove('hidden');
        actions.classList.add('hidden');
    }
}

async function uploadLogo(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validazione dimensione (5MB max per SVG, 2MB per immagini)
    const isSvg = file.name.toLowerCase().endsWith('.svg');
    const maxSize = isSvg ? 5 * 1024 * 1024 : 2 * 1024 * 1024;
    
    if (file.size > maxSize) {
        showToast('File troppo grande. Max ' + (isSvg ? '5MB' : '2MB'), 'error');
        input.value = '';
        return;
    }
    
    const formData = new FormData();
    formData.append('logo', file);
    formData.append('action', 'save_logo');
    
    try {
        const response = await fetch('api/impostazioni.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentLogo = data.data.logo;
            isLogoSvg = data.data.is_svg;
            updateLogoPreview();
            showToast('Logo aggiornato con successo!', 'success');
            // Aggiorna anche il logo nella navbar
            updateNavbarLogo();
        } else {
            showToast(data.message || 'Errore upload', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
    
    input.value = '';
}

async function rimuoviLogo() {
    confirmAction('Rimuovere il logo?', async () => {
        try {
            const response = await fetch('api/impostazioni.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=save_logo&remove=true'
            });
            
            const data = await response.json();
            
            if (data.success) {
                currentLogo = '';
                isLogoSvg = false;
                updateLogoPreview();
                showToast('Logo rimosso', 'success');
                updateNavbarLogo();
            }
        } catch (error) {
            showToast('Errore di connessione', 'error');
        }
    });
}

function updateNavbarLogo() {
    // Ricarica la pagina per aggiornare il logo nella navbar
    location.reload();
}

// Elimina Tutto - 3 Livelli
function showDeleteAllModal() {
    openModal('deleteAllModal1');
}

function showDeleteAllStep2() {
    closeModal('deleteAllModal1');
    openModal('deleteAllModal2');
}

function showDeleteAllStep3() {
    closeModal('deleteAllModal2');
    openModal('deleteAllModal3');
}

async function executeDeleteAll() {
    const keyword = document.getElementById('deleteAllKeyword').value;
    
    if (keyword !== KEYWORD_SICUREZZA) {
        showToast('Parola chiave errata! Operazione annullata.', 'error');
        return;
    }
    
    try {
        const response = await fetch('api/impostazioni.php?action=delete_all', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `keyword=${encodeURIComponent(keyword)}`
        });
        const data = await response.json();
        
        closeModal('deleteAllModal3');
        document.getElementById('deleteAllKeyword').value = '';
        
        if (data.success) {
            showToast('Tutti i dati sono stati eliminati', 'success');
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 2000);
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        showToast('Errore di connessione', 'error');
    }
}

// ==================== DATI AZIENDA ====================

let datiAziendaCorrenti = {};
let logoAziendaFilename = '';

// Carica dati azienda all'avvio
document.addEventListener('DOMContentLoaded', function() {
    caricaDatiAzienda();
});

async function caricaDatiAzienda() {
    try {
        const response = await fetch('api/impostazioni.php?action=get_dati_azienda');
        const data = await response.json();
        
        if (data.success) {
            datiAziendaCorrenti = data.data;
            
            // Popola i campi
            document.getElementById('aziendaRagioneSociale').value = datiAziendaCorrenti.ragione_sociale || '';
            document.getElementById('aziendaIndirizzo').value = datiAziendaCorrenti.indirizzo || '';
            document.getElementById('aziendaCap').value = datiAziendaCorrenti.cap || '';
            document.getElementById('aziendaCitta').value = datiAziendaCorrenti.citta || '';
            document.getElementById('aziendaProvincia').value = datiAziendaCorrenti.provincia || '';
            document.getElementById('aziendaPiva').value = datiAziendaCorrenti.piva || '';
            document.getElementById('aziendaCf').value = datiAziendaCorrenti.cf || '';
            document.getElementById('aziendaEmail').value = datiAziendaCorrenti.email || '';
            document.getElementById('aziendaTelefono').value = datiAziendaCorrenti.telefono || '';
            document.getElementById('aziendaPec').value = datiAziendaCorrenti.pec || '';
            document.getElementById('aziendaSdi').value = datiAziendaCorrenti.sdi || '';
            
            // Logo
            if (datiAziendaCorrenti.logo) {
                logoAziendaFilename = datiAziendaCorrenti.logo;
                mostraLogoAzienda(datiAziendaCorrenti.logo_url);
            }
            
            // Aggiorna stato
            aggiornaStatoDatiAzienda();
        }
    } catch (error) {
        console.error('Errore caricamento dati azienda:', error);
    }
}

function aggiornaStatoDatiAzienda() {
    const hasData = datiAziendaCorrenti.ragione_sociale || datiAziendaCorrenti.piva;
    const statusIcon = document.getElementById('statusIcon');
    const statusText = document.getElementById('statusText');
    
    if (hasData) {
        statusIcon.className = 'w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center';
        statusIcon.innerHTML = '<svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
        statusText.textContent = 'Dati configurati';
        statusText.className = 'font-medium text-emerald-700';
    } else {
        statusIcon.className = 'w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center';
        statusIcon.innerHTML = '<svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
        statusText.textContent = 'Dati non configurati';
        statusText.className = 'font-medium text-slate-700';
    }
}

function toggleDatiAziendaForm() {
    const form = document.getElementById('datiAziendaForm');
    const status = document.getElementById('datiAziendaStatus');
    
    if (form.classList.contains('hidden')) {
        form.classList.remove('hidden');
        status.classList.add('hidden');
    } else {
        form.classList.add('hidden');
        status.classList.remove('hidden');
        // Reset password field
        document.getElementById('aziendaPassword').value = '';
    }
}

function mostraLogoAzienda(url) {
    const img = document.getElementById('logoAziendaImg');
    const placeholder = document.getElementById('logoAziendaPlaceholder');
    const btnRimuovi = document.getElementById('btnRimuoviLogoAzienda');
    
    img.src = url;
    img.classList.remove('hidden');
    placeholder.classList.add('hidden');
    btnRimuovi.classList.remove('hidden');
}

function nascondiLogoAzienda() {
    const img = document.getElementById('logoAziendaImg');
    const placeholder = document.getElementById('logoAziendaPlaceholder');
    const btnRimuovi = document.getElementById('btnRimuoviLogoAzienda');
    
    img.src = '';
    img.classList.add('hidden');
    placeholder.classList.remove('hidden');
    btnRimuovi.classList.add('hidden');
    logoAziendaFilename = '';
}

async function uploadLogoAzienda(input) {
    const file = input.files[0];
    if (!file) return;
    
    const password = document.getElementById('aziendaPassword').value;
    if (!password) {
        showToast('Inserisci prima la password di modifica', 'error');
        input.value = '';
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'upload_logo_azienda');
    formData.append('logo', file);
    formData.append('password', password);
    
    try {
        showToast('Caricamento logo...', 'info');
        
        const response = await fetch('api/impostazioni.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            logoAziendaFilename = data.data.logo;
            mostraLogoAzienda(data.data.logo_url);
            showToast('Logo caricato con successo', 'success');
        } else {
            showToast(data.message || 'Errore caricamento logo', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore durante l\'upload', 'error');
    }
    
    input.value = '';
}

async function rimuoviLogoAzienda() {
    const password = document.getElementById('aziendaPassword').value;
    if (!password) {
        showToast('Inserisci prima la password di modifica', 'error');
        return;
    }
    
    if (!confirm('Sei sicuro di voler rimuovere il logo?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'upload_logo_azienda');
        formData.append('remove', 'true');
        formData.append('password', password);
        
        const response = await fetch('api/impostazioni.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            nascondiLogoAzienda();
            showToast('Logo rimosso', 'success');
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore durante la rimozione', 'error');
    }
}

async function salvaDatiAzienda() {
    const password = document.getElementById('aziendaPassword').value;
    
    if (!password) {
        showToast('Inserisci la password di modifica', 'error');
        document.getElementById('aziendaPassword').focus();
        return;
    }
    
    const ragioneSociale = document.getElementById('aziendaRagioneSociale').value.trim();
    
    if (!ragioneSociale) {
        showToast('La ragione sociale è obbligatoria', 'error');
        document.getElementById('aziendaRagioneSociale').focus();
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'save_dati_azienda');
    formData.append('password', password);
    formData.append('ragione_sociale', ragioneSociale);
    formData.append('indirizzo', document.getElementById('aziendaIndirizzo').value.trim());
    formData.append('cap', document.getElementById('aziendaCap').value.trim());
    formData.append('citta', document.getElementById('aziendaCitta').value.trim());
    formData.append('provincia', document.getElementById('aziendaProvincia').value.trim());
    formData.append('piva', document.getElementById('aziendaPiva').value.trim());
    formData.append('cf', document.getElementById('aziendaCf').value.trim());
    formData.append('email', document.getElementById('aziendaEmail').value.trim());
    formData.append('telefono', document.getElementById('aziendaTelefono').value.trim());
    formData.append('pec', document.getElementById('aziendaPec').value.trim());
    formData.append('sdi', document.getElementById('aziendaSdi').value.trim());
    
    try {
        showToast('Salvataggio in corso...', 'info');
        
        const response = await fetch('api/impostazioni.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Dati salvati con successo', 'success');
            
            // Aggiorna i dati in memoria
            datiAziendaCorrenti = {
                ragione_sociale: formData.get('ragione_sociale'),
                indirizzo: formData.get('indirizzo'),
                cap: formData.get('cap'),
                citta: formData.get('citta'),
                provincia: formData.get('provincia'),
                piva: formData.get('piva'),
                cf: formData.get('cf'),
                email: formData.get('email'),
                telefono: formData.get('telefono'),
                pec: formData.get('pec'),
                sdi: formData.get('sdi'),
                logo: logoAziendaFilename
            };
            
            aggiornaStatoDatiAzienda();
            toggleDatiAziendaForm();
        } else {
            if (data.message === 'Password errata') {
                showToast('Password errata', 'error');
                document.getElementById('aziendaPassword').focus();
            } else {
                showToast(data.message || 'Errore durante il salvataggio', 'error');
            }
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore durante il salvataggio', 'error');
    }
}

// ==================== IMPOSTAZIONI TASSE ====================

let codiciAteco = [];
let impostazioniTasse = {};

// Carica dati all'avvio
document.addEventListener('DOMContentLoaded', function() {
    caricaCodiciAteco();
    caricaImpostazioniTasse();
});

async function caricaCodiciAteco() {
    try {
        const response = await fetch('api/impostazioni.php?action=get_codici_ateco');
        const data = await response.json();
        
        if (data.success) {
            codiciAteco = data.data;
            renderizzaCodiciAteco();
        }
    } catch (error) {
        console.error('Errore caricamento codici ATECO:', error);
    }
}

async function caricaImpostazioniTasse() {
    try {
        const response = await fetch('api/impostazioni.php?action=get_impostazioni_tasse');
        const data = await response.json();
        
        if (data.success) {
            impostazioniTasse = data.data;
            document.getElementById('tassaInps').value = impostazioniTasse.inps_percentuale || '';
            document.getElementById('tassaAcconto').value = impostazioniTasse.acconto_percentuale || '';
        }
    } catch (error) {
        console.error('Errore caricamento impostazioni tasse:', error);
    }
}

function toggleTasseForm() {
    const form = document.getElementById('tasseForm');
    const status = document.getElementById('tasseStatus');
    
    if (form.classList.contains('hidden')) {
        form.classList.remove('hidden');
        status.classList.add('hidden');
    } else {
        form.classList.add('hidden');
        status.classList.remove('hidden');
        document.getElementById('tassePassword').value = '';
    }
}

function renderizzaCodiciAteco() {
    const container = document.getElementById('listaCodiciAteco');
    
    if (codiciAteco.length === 0) {
        container.innerHTML = '<p class="text-slate-400 text-center py-4">Nessun codice ATECO configurato</p>';
        return;
    }
    
    container.innerHTML = codiciAteco.map(c => `
        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between">
            <div>
                <p class="font-medium text-slate-800">${escapeHtml(c.codice)}</p>
                <p class="text-sm text-slate-600">${escapeHtml(c.descrizione || '')}</p>
                <p class="text-xs text-slate-500 mt-1">
                    Coeff: ${c.coefficiente_redditivita}% | Tax: ${c.tassazione}%
                </p>
            </div>
            <div class="flex gap-2">
                <button onclick="modificaCodiceAteco(${c.id})" 
                        class="p-2 text-slate-400 hover:text-emerald-600 transition-colors" title="Modifica">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </button>
                <button onclick="eliminaCodiceAteco(${c.id})" 
                        class="p-2 text-slate-400 hover:text-red-600 transition-colors" title="Elimina">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
    `).join('');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function apriModalCodiceAteco(id = null) {
    const password = document.getElementById('tassePassword').value;
    if (!password) {
        showToast('Inserisci prima la password', 'error');
        document.getElementById('tassePassword').focus();
        return;
    }
    
    document.getElementById('atecoId').value = id || '';
    document.getElementById('atecoCodice').value = '';
    document.getElementById('atecoDescrizione').value = '';
    document.getElementById('atecoCoefficiente').value = '';
    document.getElementById('atecoTassazione').value = '';
    
    if (id) {
        const codice = codiciAteco.find(c => c.id == id);
        if (codice) {
            document.getElementById('titoloModalAteco').textContent = 'Modifica Codice ATECO';
            document.getElementById('atecoCodice').value = codice.codice;
            document.getElementById('atecoDescrizione').value = codice.descrizione || '';
            document.getElementById('atecoCoefficiente').value = codice.coefficiente_redditivita;
            document.getElementById('atecoTassazione').value = codice.tassazione;
        }
    } else {
        document.getElementById('titoloModalAteco').textContent = 'Nuovo Codice ATECO';
    }
    
    openModal('modalCodiceAteco');
}

function modificaCodiceAteco(id) {
    apriModalCodiceAteco(id);
}

function chiudiModalCodiceAteco() {
    closeModal('modalCodiceAteco');
}

async function salvaCodiceAteco() {
    const password = document.getElementById('tassePassword').value;
    const id = document.getElementById('atecoId').value;
    const codice = document.getElementById('atecoCodice').value.trim();
    
    if (!codice) {
        showToast('Il codice ATECO è obbligatorio', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'save_codice_ateco');
    formData.append('password', password);
    formData.append('id', id);
    formData.append('codice', codice);
    formData.append('descrizione', document.getElementById('atecoDescrizione').value.trim());
    formData.append('coefficiente_redditivita', document.getElementById('atecoCoefficiente').value || 0);
    formData.append('tassazione', document.getElementById('atecoTassazione').value || 0);
    
    try {
        showToast('Salvataggio...', 'info');
        
        const response = await fetch('api/impostazioni.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Codice ATECO salvato', 'success');
            chiudiModalCodiceAteco();
            await caricaCodiciAteco();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore durante il salvataggio', 'error');
    }
}

async function eliminaCodiceAteco(id) {
    const password = document.getElementById('tassePassword').value;
    if (!password) {
        showToast('Inserisci la password', 'error');
        return;
    }
    
    if (!confirm('Sei sicuro di voler eliminare questo codice ATECO?')) return;
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_codice_ateco');
        formData.append('password', password);
        formData.append('id', id);
        
        const response = await fetch('api/impostazioni.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Codice eliminato', 'success');
            await caricaCodiciAteco();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore durante l\'eliminazione', 'error');
    }
}

async function salvaImpostazioniTasse() {
    const password = document.getElementById('tassePassword').value;
    if (!password) {
        showToast('Inserisci la password', 'error');
        document.getElementById('tassePassword').focus();
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'save_impostazioni_tasse');
    formData.append('password', password);
    formData.append('inps_percentuale', document.getElementById('tassaInps').value || 0);
    formData.append('acconto_percentuale', document.getElementById('tassaAcconto').value || 0);
    
    try {
        showToast('Salvataggio...', 'info');
        
        const response = await fetch('api/impostazioni.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Impostazioni salvate', 'success');
            await caricaImpostazioniTasse();
        } else {
            showToast(data.message || 'Errore', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore durante il salvataggio', 'error');
    }
}

</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
