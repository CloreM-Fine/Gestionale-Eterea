<?php
/**
 * Eterea Gestionale
 * Header comune
 */

// Determina pagina corrente per menu attivo
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Ottieni statistiche per notifiche (placeholder)
$notificheCount = 0; // TODO: Implementare conteggio notifiche

// Recupera logo personalizzato
try {
    $stmt = $pdo->prepare("SELECT valore FROM impostazioni WHERE chiave = 'logo_gestionale'");
    $stmt->execute();
    $logoNavbar = $stmt->fetchColumn() ?: '';
    $isLogoNavbarSvg = $logoNavbar && str_ends_with(strtolower($logoNavbar), '.svg');
} catch (PDOException $e) {
    $logoNavbar = '';
    $isLogoNavbarSvg = false;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo e($pageTitle ?? 'Eterea Gestionale'); ?> - Gestionale</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#0891B2',
                        secondary: '#1E293B',
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Fix per app iOS - FORZA SCHERMO PIENO */
        html, body {
            height: 100% !important;
            min-height: 100vh !important;
            min-height: -webkit-fill-available !important;
        }
        
        .ios-app html,
        .ios-app body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100% !important;
            width: 100% !important;
            overflow-x: hidden !important;
        }
        
        /* Rimuovi padding del main per app iOS */
        .ios-app main {
            padding-bottom: 0 !important;
        }
        
        /* Header senza padding top in app */
        .ios-app header {
            padding-top: 0 !important;
        }
        
        /* Animazioni personalizzate */
        @keyframes slideIn {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 0.5; }
            100% { transform: scale(1.3); opacity: 0; }
        }
        
        .slide-in { animation: slideIn 0.3s ease-out; }
        .fade-in { animation: fadeIn 0.3s ease-out; }
        
        /* Scrollbar personalizzata */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Custom scrollbar per card team */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 2px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Menu mobile */
        .mobile-menu {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .mobile-menu.open {
            transform: translateX(0);
        }
        
        /* Sidebar collapsible */
        .sidebar-collapsed {
            width: 64px !important;
        }
        .sidebar-collapsed .sidebar-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
            transition: opacity 0.2s ease, width 0.2s ease;
        }
        .sidebar-collapsed .sidebar-logo-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
            transition: opacity 0.2s ease, width 0.2s ease;
        }
        .sidebar-collapsed .sidebar-logo {
            justify-content: center;
        }
        .sidebar-collapsed:hover {
            width: 256px !important;
        }
        .sidebar-collapsed:hover .sidebar-text,
        .sidebar-collapsed:hover .sidebar-logo-text {
            opacity: 1;
            width: auto;
        }
        .sidebar-collapsed:hover .sidebar-logo {
            justify-content: flex-start;
        }
        .sidebar-collapsed nav a {
            justify-content: center;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .sidebar-collapsed:hover nav a {
            justify-content: flex-start;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        /* Transition per sidebar */
        #sidebar {
            transition: width 0.3s ease;
        }
        .sidebar-text, .sidebar-logo-text {
            transition: opacity 0.2s ease 0.1s;
            white-space: nowrap;
        }
        
        /* Dropdown */
        .dropdown-menu {
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
        }
        .dropdown:hover .dropdown-menu,
        .dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        /* Card hover effect */
        .card-hover {
            transition: all 0.2s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        /* Main content wrapper transition */
        .main-wrapper {
            transition: margin-left 0.3s ease;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">
    <!-- Mobile Menu Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden" onclick="closeMobileMenu()"></div>
    
    <!-- Sidebar / Mobile Menu -->
    <aside id="sidebar" class="mobile-menu fixed left-0 top-0 h-full bg-slate-800 text-white z-50 flex flex-col lg:translate-x-0 sidebar-collapsed">
        <!-- Logo -->
        <div class="p-4 border-b border-slate-700 sidebar-logo flex items-center gap-3 h-16">
            <?php if ($logoNavbar): ?>
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-lg overflow-hidden flex-shrink-0">
                    <img src="assets/uploads/logo/<?php echo e($logoNavbar); ?>?v=<?php echo time(); ?>" 
                         alt="Logo" class="w-full h-full object-contain p-1">
                </div>
            <?php else: ?>
                <div class="w-10 h-10 bg-gradient-to-br from-cyan-400 to-cyan-600 rounded-lg flex items-center justify-center font-bold text-xl shadow-lg flex-shrink-0">
                    LDE
                </div>
            <?php endif; ?>
            <div class="sidebar-logo-text overflow-hidden">
                <h1 class="font-bold text-base sm:text-lg leading-tight">Eterea Gestionale</h1>
                <p class="text-xs text-slate-400">Gestionale</p>
            </div>
        </div>
        
        <!-- User Info Mobile -->
        <div class="lg:hidden p-4 border-b border-slate-700 bg-slate-700/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold overflow-hidden" 
                     style="background-color: <?php echo e($currentUser['colore']); ?>">
                    <?php if (!empty($currentUser['avatar']) && file_exists(__DIR__ . '/../assets/uploads/avatars/' . $currentUser['avatar'])): ?>
                        <img src="assets/uploads/avatars/<?php echo e($currentUser['avatar']); ?>" 
                             alt="Avatar" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?php echo e(substr($currentUser['nome'], 0, 2)); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="font-medium text-sm"><?php echo e($currentUser['nome']); ?></p>
                    <p class="text-xs text-slate-400">Online</p>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4">
            <ul class="space-y-1 px-2">
                <li>
                    <a href="dashboard.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'dashboard' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="progetti.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo in_array($currentPage, ['progetti', 'progetto_dettaglio']) ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span class="sidebar-text">Progetti</span>
                    </a>
                </li>
                <li>
                    <a href="clienti.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'clienti' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="sidebar-text">Clienti</span>
                    </a>
                </li>
                <li>
                    <a href="preventivi.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'preventivi' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 15.536c-1.171 1.952-3.07 1.952-4.242 0-1.172-1.953-1.172-5.119 0-7.072 1.171-1.952 3.07-1.952 4.242 0M8 10.5h4m-4 3h4m9-1.5a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="sidebar-text">Preventivi</span>
                    </a>
                </li>
                <li>
                    <a href="calendario.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'calendario' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="sidebar-text">Calendario</span>
                    </a>
                </li>
                <li>
                    <a href="finanze.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'finanze' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="sidebar-text">Finanze</span>
                    </a>
                </li>
                <li>
                    <a href="briefing_ai.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'briefing_ai' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0019.414 9H21a2 2 0 012 2v9a2 2 0 01-2 2h-1.586l-3.707-3.707A1 1 0 0014.586 18H9a1 1 0 00-1 1v3.586l-3.707-3.707A1 1 0 003.586 18H3a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0017.414 9H19a2 2 0 012 2v9a2 2 0 01-2 2h-1.586l-3.707-3.707A1 1 0 0014.586 18z"/>
                        </svg>
                        <span class="sidebar-text">Briefing</span>
                    </a>
                </li>
                <li>
                    <a href="impostazioni.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'impostazioni' ? 'bg-cyan-600 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="sidebar-text">Impostazioni</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Logout Mobile -->
        <div class="lg:hidden p-4 border-t border-slate-700">
            <a href="api/auth.php?action=logout" class="flex items-center gap-3 px-4 py-3 rounded-lg text-red-400 hover:bg-red-500/10 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content Wrapper -->
    <div class="main-wrapper lg:ml-16 min-h-screen">
        <!-- Top Header -->
        <header class="bg-white shadow-sm sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Left: Mobile Menu Button -->
                <button onclick="openMobileMenu()" class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                
                <!-- Center: Page Title -->
                <h2 class="text-base sm:text-lg font-semibold text-slate-800 lg:hidden"><?php echo e($pageTitle ?? 'Eterea Gestionale'); ?></h2>
                
                <!-- Right: Actions -->
                <div class="flex items-center gap-4 ml-auto">
                    <!-- Notifications -->
                    <div class="dropdown relative" id="notificheDropdown">
                        <button onclick="toggleNotifiche()" class="relative p-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span id="notificheBadge" class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-medium hidden">0</span>
                        </button>
                        
                        <!-- Dropdown Notifiche -->
                        <div id="notificheMenu" class="dropdown-menu absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-slate-200 py-2 z-50 hidden">
                            <div class="px-4 py-2 border-b border-slate-100 flex items-center justify-between">
                                <h3 class="text-sm sm:text-base font-semibold text-slate-800">Notifiche</h3>
                                <button onclick="markAllNotificheLette()" class="text-xs sm:text-sm text-cyan-600 hover:text-cyan-700">Segna tutte lette</button>
                            </div>
                            <div id="notificheList" class="max-h-64 overflow-y-auto">
                                <p class="px-4 py-8 text-center text-slate-500 text-xs sm:text-sm">
                                    Caricamento...
                                </p>
                            </div>
                            <div class="px-4 py-2 border-t border-slate-100 flex items-center justify-between">
                                <button onclick="deleteAllNotifiche()" class="text-sm text-red-600 hover:text-red-700 font-medium flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Elimina tutte
                                </button>
                                <span id="notificheCount" class="text-xs text-slate-500">0 notifiche</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="dropdown relative hidden lg:block">
                        <button class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-100 transition-colors">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-semibold overflow-hidden" 
                                 style="background-color: <?php echo e($currentUser['colore']); ?>">
                                <?php if (!empty($currentUser['avatar']) && file_exists(__DIR__ . '/../assets/uploads/avatars/' . $currentUser['avatar'])): ?>
                                    <img src="assets/uploads/avatars/<?php echo e($currentUser['avatar']); ?>" 
                                         alt="Avatar" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?php echo e(substr($currentUser['nome'], 0, 2)); ?>
                                <?php endif; ?>
                            </div>
                            <span class="text-sm font-medium text-slate-700 hidden xl:block"><?php echo e($currentUser['nome']); ?></span>
                            <svg class="w-4 h-4 text-slate-400 hidden xl:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        <!-- Dropdown User -->
                        <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-slate-200 py-2 z-50">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="font-medium text-slate-800"><?php echo e($currentUser['nome']); ?></p>
                                <p class="text-xs text-slate-500">Utente</p>
                            </div>
                            <a href="api/auth.php?action=logout" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <main class="p-4 sm:p-6 lg:p-8">
