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
    
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicons/favicon-16x16.png">
    <link rel="manifest" href="assets/favicons/site.webmanifest">
    <link rel="shortcut icon" href="assets/favicons/favicon.ico">
    <meta name="apple-mobile-web-app-title" content="Eterea">
    <meta name="application-name" content="Eterea">
    
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
                        primary: '#2d2d2d',
                        secondary: '#f5f3ef',
                        eterea: {
                            azzurro: '#9bc4d0',
                            verde: '#a8b5a0',
                            lilla: '#c4b5d0',
                            giallo: '#e8e4b8',
                            pesca: '#e8c4b8',
                            bg: '#f5f3ef',
                            dark: '#2d2d2d'
                        }
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

        /* BOTTOM NAVIGATION - Mobile */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #2d2d2d;
            border-top: 1px solid #3d3d3d;
            z-index: 50;
            padding-bottom: env(safe-area-inset-bottom, 0);
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 56px;
            min-width: 56px;
            padding: 8px 4px;
            color: #a0a0a0;
            transition: all 0.2s ease;
            border-radius: 12px;
            margin: 0 2px;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
        }
        
        .bottom-nav-item:active {
            transform: scale(0.95);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .bottom-nav-item.active {
            color: #9bc4d0;
        }
        
        .bottom-nav-item svg {
            width: 24px;
            height: 24px;
            margin-bottom: 2px;
        }
        
        .bottom-nav-item span {
            font-size: 11px;
            font-weight: 500;
            line-height: 1.2;
        }
        
        /* Menu completo mobile - Palette Eterea */
        .mobile-full-menu {
            position: fixed;
            inset: 0;
            background: #2d2d2d;
            z-index: 60;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            padding-bottom: 80px;
        }
        
        .mobile-full-menu.open {
            transform: translateY(0);
        }
        
        .mobile-full-menu-header {
            position: sticky;
            top: 0;
            background: #2d2d2d;
            padding: 16px;
            border-bottom: 1px solid #3d3d3d;
            z-index: 10;
        }
        
        .mobile-menu-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding: 16px;
        }
        
        .mobile-menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 80px;
            padding: 16px 8px;
            background: #3d3d3d;
            border-radius: 16px;
            color: #d0d0d0;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }
        
        .mobile-menu-item:active {
            transform: scale(0.96);
            background: #4d4d4d;
        }
        
        .mobile-menu-item.active {
            background: rgba(155, 196, 208, 0.2);
            border-color: #9bc4d0;
            color: #9bc4d0;
        }
        
        .mobile-menu-item svg {
            width: 28px;
            height: 28px;
            margin-bottom: 8px;
        }
        
        .mobile-menu-item span {
            font-size: 13px;
            font-weight: 500;
            text-align: center;
        }

        /* Main content padding per bottom nav su mobile */
        @media (max-width: 1023px) {
            .main-wrapper {
                padding-bottom: 80px;
            }
            main {
                padding-bottom: calc(80px + env(safe-area-inset-bottom, 0px));
            }
        }
        
        /* Touch targets minimum 44px */
        .touch-target {
            min-height: 44px;
            min-width: 44px;
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
    </style>
    
    <!-- CSRF Token per protezione form -->
    <meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
</head>
<body class="bg-[#f5f3ef] font-sans text-[#2d2d2d]">
    <!-- Mobile Menu Overlay -->
    <div id="mobileOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden" onclick="closeMobileMenu()"></div>
    
    <!-- Sidebar / Mobile Menu -->
    <aside id="sidebar" class="mobile-menu fixed left-0 top-0 h-full bg-[#2d2d2d] text-white z-50 flex flex-col hidden lg:flex lg:translate-x-0 sidebar-collapsed">
        <!-- Logo -->
        <div class="p-4 border-b border-[#3d3d3d] sidebar-logo flex items-center gap-3 h-16">
            <?php if ($logoNavbar): ?>
                <div class="w-10 h-10 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                    <img src="assets/uploads/logo/<?php echo e($logoNavbar); ?>?v=<?php echo time(); ?>" 
                         alt="Logo" class="w-full h-full object-contain p-1">
                </div>
            <?php else: ?>
                <div class="w-10 h-10 bg-gradient-to-br from-[#9bc4d0] via-[#a8b5a0] to-[#c4b5d0] rounded-lg flex items-center justify-center font-bold text-xl shadow-lg flex-shrink-0 text-[#2d2d2d]">
                    LDE
                </div>
            <?php endif; ?>
            <div class="sidebar-logo-text overflow-hidden">
                <h1 class="font-bold text-base sm:text-lg leading-tight">Eterea Gestionale</h1>
                <p class="text-xs text-[#a0a0a0]">Gestionale</p>
            </div>
        </div>
        
        <!-- User Info Mobile -->
        <div class="lg:hidden p-4 border-b border-[#3d3d3d] bg-[#3d3d3d]/50">
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
                    <p class="font-medium text-sm text-[#f5f3ef]"><?php echo e($currentUser['nome']); ?></p>
                    <p class="text-xs text-[#a0a0a0]">Online</p>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-4">
            <ul class="space-y-1 px-2">
                <li>
                    <a href="dashboard.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'dashboard' ? 'bg-[#9bc4d0] text-[#2d2d2d] font-medium' : 'text-[#d0d0d0] hover:bg-[#3d3d3d] hover:text-[#f5f3ef]'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="clienti.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'clienti' ? 'bg-[#9bc4d0] text-[#2d2d2d] font-medium' : 'text-[#d0d0d0] hover:bg-[#3d3d3d] hover:text-[#f5f3ef]'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="sidebar-text">Clienti</span>
                    </a>
                </li>
                <li>
                    <a href="progetti.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo in_array($currentPage, ['progetti', 'progetto_dettaglio']) ? 'bg-[#9bc4d0] text-[#2d2d2d] font-medium' : 'text-[#d0d0d0] hover:bg-[#3d3d3d] hover:text-[#f5f3ef]'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span class="sidebar-text">Progetti</span>
                    </a>
                </li>
                <li>
                    <a href="scadenze.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'scadenze' ? 'bg-[#e8c4b8] text-[#2d2d2d] font-medium' : 'text-[#d0d0d0] hover:bg-[#3d3d3d] hover:text-[#f5f3ef]'; ?> relative">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="sidebar-text">Scadenze</span>
                        <span id="scadenzeBadgeSidebar" class="hidden absolute right-2 top-1/2 -translate-y-1/2 min-w-[20px] h-5 px-1.5 bg-[#e8c4b8] text-[#2d2d2d] text-xs font-bold rounded-full flex items-center justify-center">0</span>
                        <!-- Icona avviso scadenze -->
                        <span id="scadenzeAlertIcon" class="hidden absolute right-2 top-1/2 -translate-y-1/2">
                            <svg class="w-5 h-5 text-[#e8e4b8]" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </span>
                    </a>
                </li>
                <li>
                    <a href="preventivi.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'preventivi' ? 'bg-[#9bc4d0] text-[#2d2d2d] font-medium' : 'text-[#d0d0d0] hover:bg-[#3d3d3d] hover:text-[#f5f3ef]'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 15.536c-1.171 1.952-3.07 1.952-4.242 0-1.172-1.953-1.172-5.119 0-7.072 1.171-1.952 3.07-1.952 4.242 0M8 10.5h4m-4 3h4m9-1.5a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="sidebar-text">Preventivi</span>
                    </a>
                </li>
                <li>
                    <a href="calendario.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'calendario' ? 'bg-[#9bc4d0] text-[#2d2d2d] font-medium' : 'text-[#d0d0d0] hover:bg-[#3d3d3d] hover:text-[#f5f3ef]'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="sidebar-text">Calendario</span>
                    </a>
                </li>
                <li>
                    <a href="finanze.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'finanze' ? 'bg-[#9bc4d0] text-[#2d2d2d] font-medium' : 'text-[#d0d0d0] hover:bg-[#3d3d3d] hover:text-[#f5f3ef]'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="sidebar-text">Finanze</span>
                    </a>
                </li>
                <li>
                    <a href="tasse.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'tasse' ? 'bg-[#9bc4d0] text-[#2d2d2d] font-medium' : 'text-[#d0d0d0] hover:bg-[#3d3d3d] hover:text-[#f5f3ef]'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span class="sidebar-text">Tasse</span>
                    </a>
                </li>
                <li>
                    <a href="briefing_ai.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'briefing_ai' ? 'bg-[#9bc4d0] text-[#2d2d2d] font-medium' : 'text-[#d0d0d0] hover:bg-[#3d3d3d] hover:text-[#f5f3ef]'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                        </svg>
                        <span class="sidebar-text">Briefing</span>
                    </a>
                </li>
                <li>
                    <a href="report.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'report' ? 'bg-[#9bc4d0] text-[#2d2d2d] font-medium' : 'text-[#d0d0d0] hover:bg-[#3d3d3d] hover:text-[#f5f3ef]'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="sidebar-text">Report</span>
                    </a>
                </li>
                <?php if (($_SESSION['user_id'] ?? '') === 'ucwurog3xr8tf'): ?>
                <li>
                    <a href="blog_clienti.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'blog_clienti' ? 'bg-[#9bc4d0] text-[#2d2d2d] font-medium' : 'text-[#d0d0d0] hover:bg-[#3d3d3d] hover:text-[#f5f3ef]'; ?> relative">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                        </svg>
                        <span class="sidebar-text">Blog Clienti</span>
                        <span id="blogClientiBadge" class="hidden absolute right-2 top-1/2 -translate-y-1/2 min-w-[20px] h-5 px-1.5 bg-[#e8e4b8] text-[#2d2d2d] text-xs font-bold rounded-full flex items-center justify-center">0</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="impostazioni.php" 
                       class="flex items-center gap-3 px-3 py-3 rounded-lg transition-colors <?php echo $currentPage === 'impostazioni' ? 'bg-[#9bc4d0] text-[#2d2d2d] font-medium' : 'text-[#d0d0d0] hover:bg-[#3d3d3d] hover:text-[#f5f3ef]'; ?>">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="sidebar-text">Impostazioni</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Visita il Sito (in fondo, sempre visibile) -->
        <div class="mt-auto p-4 border-t border-[#3d3d3d] bg-[#2d2d2d]">
            <a href="https://www.etereastudio.it" target="_blank" rel="noopener noreferrer"
               class="flex items-center justify-center px-3 py-2 rounded-lg bg-[#3d3d3d] text-[#d0d0d0] hover:bg-[#4d4d4d] hover:text-[#f5f3ef] transition-colors text-sm">
                <span class="sidebar-text">Visita il sito</span>
            </a>
        </div>
        
        <!-- Logout Mobile -->
        <div class="lg:hidden p-4 border-t border-[#3d3d3d]">
            <a href="api/auth.php?action=logout" class="flex items-center gap-3 px-4 py-3 rounded-lg text-[#e8c4b8] hover:bg-[#e8c4b8]/10 transition-colors">
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
                <!-- Page Title -->
                <h2 class="text-base sm:text-lg font-semibold text-[#2d2d2d] lg:hidden truncate max-w-[150px]"><?php echo e($pageTitle ?? 'Eterea Gestionale'); ?></h2>
                
                <!-- Right: Actions -->
                <div class="flex items-center gap-4 ml-auto">
                    <!-- Notifications -->
                    <div class="dropdown relative" id="notificheDropdown">
                        <button onclick="toggleNotifiche()" class="relative p-2 rounded-lg text-[#6d6d6d] hover:bg-[#ebe9e5] transition-colors touch-target">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span id="notificheBadge" class="absolute top-1 right-1 w-5 h-5 bg-[#e8c4b8] text-[#2d2d2d] text-xs rounded-full flex items-center justify-center font-medium hidden">0</span>
                        </button>
                        
                        <!-- Dropdown Notifiche -->
                        <div id="notificheMenu" class="dropdown-menu absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-[#e5e5e5] py-2 z-50 hidden">
                            <div class="px-4 py-2 border-b border-[#f0f0f0] flex items-center justify-between">
                                <h3 class="text-sm sm:text-base font-semibold text-[#2d2d2d]">Notifiche</h3>
                                <button onclick="markAllNotificheLette()" class="text-xs sm:text-sm text-[#9bc4d0] hover:text-[#7aa4b0]">Segna tutte lette</button>
                            </div>
                            <div id="notificheList" class="max-h-64 overflow-y-auto">
                                <p class="px-4 py-8 text-center text-[#909090] text-xs sm:text-sm">
                                    Caricamento...
                                </p>
                            </div>
                            <div class="px-4 py-2 border-t border-[#f0f0f0] flex items-center justify-between">
                                <button onclick="deleteAllNotifiche()" class="text-sm text-[#e8c4b8] hover:text-[#c8a498] font-medium flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    Elimina tutte
                                </button>
                                <span id="notificheCount" class="text-xs text-[#909090]">0 notifiche</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="dropdown relative hidden lg:block">
                        <button class="flex items-center gap-3 p-2 rounded-lg hover:bg-[#ebe9e5] transition-colors touch-target">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-semibold overflow-hidden" 
                                 style="background-color: <?php echo e($currentUser['colore']); ?>">
                                <?php if (!empty($currentUser['avatar']) && file_exists(__DIR__ . '/../assets/uploads/avatars/' . $currentUser['avatar'])): ?>
                                    <img src="assets/uploads/avatars/<?php echo e($currentUser['avatar']); ?>" 
                                         alt="Avatar" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?php echo e(substr($currentUser['nome'], 0, 2)); ?>
                                <?php endif; ?>
                            </div>
                            <span class="text-sm font-medium text-[#2d2d2d] hidden xl:block"><?php echo e($currentUser['nome']); ?></span>
                            <svg class="w-4 h-4 text-[#a0a0a0] hidden xl:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        
                        <!-- Dropdown User -->
                        <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-[#e5e5e5] py-2 z-50">
                            <div class="px-4 py-2 border-b border-[#f0f0f0]">
                                <p class="font-medium text-[#2d2d2d]"><?php echo e($currentUser['nome']); ?></p>
                                <p class="text-xs text-[#909090]">Utente</p>
                            </div>
                            <a href="api/auth.php?action=logout" class="flex items-center gap-2 px-4 py-2 text-sm text-[#e8c4b8] hover:bg-[#e8c4b8]/10 transition-colors">
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

<!-- Bottom Navigation - Solo Mobile -->
<nav class="bottom-nav lg:hidden">
    <div class="flex items-center justify-around px-2 py-1 max-w-lg mx-auto">
        <!-- Dashboard -->
        <a href="dashboard.php" class="bottom-nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Dashboard</span>
        </a>
        
        <!-- Progetti -->
        <a href="progetti.php" class="bottom-nav-item <?php echo in_array($currentPage, ['progetti', 'progetto_dettaglio']) ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            <span>Progetti</span>
        </a>
        
        <!-- Clienti -->
        <a href="clienti.php" class="bottom-nav-item <?php echo $currentPage === 'clienti' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
            </svg>
            <span>Clienti</span>
        </a>
        
        <!-- Calendario -->
        <a href="calendario.php" class="bottom-nav-item <?php echo $currentPage === 'calendario' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>Calendario</span>
        </a>
        
        <!-- Menu (apre menu completo) -->
        <button onclick="openMobileFullMenu()" class="bottom-nav-item" type="button">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <span>Menu</span>
        </button>
    </div>
</nav>

<!-- Menu Completo Mobile (Overlay) -->
<div id="mobileFullMenu" class="mobile-full-menu lg:hidden">
    <div class="mobile-full-menu-header flex items-center justify-between">
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
                <p class="font-medium text-[#f5f3ef] text-sm"><?php echo e($currentUser['nome']); ?></p>
                <p class="text-xs text-[#a0a0a0]">Menu</p>
            </div>
        </div>
        <button onclick="closeMobileFullMenu()" class="p-2 rounded-lg text-[#a0a0a0] hover:text-[#f5f3ef] hover:bg-[#3d3d3d] touch-target">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    
    <div class="mobile-menu-grid">
        <!-- Dashboard -->
        <a href="dashboard.php" class="mobile-menu-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span>Dashboard</span>
        </a>
        
        <!-- Progetti -->
        <a href="progetti.php" class="mobile-menu-item <?php echo in_array($currentPage, ['progetti', 'progetto_dettaglio']) ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            <span>Progetti</span>
        </a>
        
        <!-- Clienti -->
        <a href="clienti.php" class="mobile-menu-item <?php echo $currentPage === 'clienti' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span>Clienti</span>
        </a>
        
        <!-- Scadenze -->
        <a href="scadenze.php" class="mobile-menu-item <?php echo $currentPage === 'scadenze' ? 'active' : ''; ?> relative">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Scadenze</span>
            <span id="scadenzeBadgeMobile" class="hidden absolute top-1 right-1 w-5 h-5 bg-[#e8c4b8] text-[#2d2d2d] text-xs font-bold rounded-full flex items-center justify-center">0</span>
        </a>
        
        <!-- Preventivi -->
        <a href="preventivi.php" class="mobile-menu-item <?php echo $currentPage === 'preventivi' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 15.536c-1.171 1.952-3.07 1.952-4.242 0-1.172-1.953-1.172-5.119 0-7.072 1.171-1.952 3.07-1.952 4.242 0M8 10.5h4m-4 3h4m9-1.5a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Preventivi</span>
        </a>
        
        <!-- Calendario -->
        <a href="calendario.php" class="mobile-menu-item <?php echo $currentPage === 'calendario' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>Calendario</span>
        </a>
        
        <!-- Finanze -->
        <a href="finanze.php" class="mobile-menu-item <?php echo $currentPage === 'finanze' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Finanze</span>
        </a>
        
        <!-- Tasse -->
        <a href="tasse.php" class="mobile-menu-item <?php echo $currentPage === 'tasse' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <span>Tasse</span>
        </a>
        
        <!-- Briefing -->
        <a href="briefing_ai.php" class="mobile-menu-item <?php echo $currentPage === 'briefing_ai' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
            </svg>
            <span>Briefing</span>
        </a>
        
        <!-- Report -->
        <a href="report.php" class="mobile-menu-item <?php echo $currentPage === 'report' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span>Report</span>
        </a>
        
        <?php if (($_SESSION['user_id'] ?? '') === 'ucwurog3xr8tf'): ?>
        <!-- Blog Clienti -->
        <a href="blog_clienti.php" class="mobile-menu-item <?php echo $currentPage === 'blog_clienti' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
            </svg>
            <span>Blog Clienti</span>
        </a>
        <?php endif; ?>
        
        <!-- Impostazioni -->
        <a href="impostazioni.php" class="mobile-menu-item <?php echo $currentPage === 'impostazioni' ? 'active' : ''; ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>Impostazioni</span>
        </a>
        
        <!-- Logout -->
        <a href="api/auth.php?action=logout" class="mobile-menu-item text-[#e8c4b8]">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            <span>Logout</span>
        </a>
    </div>
    
    <!-- Info versione -->
    <div class="px-4 py-4 text-center">
        <p class="text-xs text-[#909090]">Eterea Gestionale</p>
    </div>
</div>

<script>
// Mobile Menu Functions
function openMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    if (sidebar && overlay) {
        sidebar.classList.add('open');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobileOverlay');
    if (sidebar && overlay) {
        sidebar.classList.remove('open');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

// Mobile Full Menu Functions
function openMobileFullMenu() {
    const menu = document.getElementById('mobileFullMenu');
    if (menu) {
        menu.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function closeMobileFullMenu() {
    const menu = document.getElementById('mobileFullMenu');
    if (menu) {
        menu.classList.remove('open');
        document.body.style.overflow = '';
    }
}

// Gestione resize - chiudi menu mobile quando si passa a desktop
window.addEventListener('resize', function() {
    if (window.innerWidth >= 1024) {
        closeMobileMenu();
        closeMobileFullMenu();
    }
});

// Chiudi menu con tasto ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMobileMenu();
        closeMobileFullMenu();
    }
});

// Toggle Notifiche
function toggleNotifiche() {
    const menu = document.getElementById('notificheMenu');
    if (menu) {
        menu.classList.toggle('hidden');
        menu.classList.toggle('show');
        
        // Carica notifiche se aperto
        if (!menu.classList.contains('hidden')) {
            loadNotifiche();
        }
    }
}

// Chiudi dropdown notifiche quando si clicca fuori
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notificheDropdown');
    const menu = document.getElementById('notificheMenu');
    if (dropdown && menu && !dropdown.contains(e.target)) {
        menu.classList.add('hidden');
        menu.classList.remove('show');
    }
});

// ============================================
// BADGE SCADENZE IN SCADENZA
// ============================================

// Carica il conteggio delle scadenze in scadenza e aggiorna i badge
async function aggiornaBadgeScadenze() {
    try {
        // Ottieni i giorni di preavviso salvati (default: 1 - come nella pagina scadenze)
        const giorniPreavviso = parseInt(localStorage.getItem('scadenze_giorni_preavviso')) || 1;
        
        // Carica tutte le scadenze aperte
        const response = await fetch('api/scadenze.php?action=list', { credentials: 'same-origin' });
        const data = await response.json();
        
        if (!data.success) return;
        
        // Filtra le scadenze in scadenza (entro X giorni)
        const oggi = new Date();
        oggi.setHours(0, 0, 0, 0);
        
        const scadenzeInScadenza = data.data.filter(s => {
            if (s.stato === 'completata' || s.stato === 'scaduta') return false;
            const scadenza = new Date(s.data_scadenza);
            scadenza.setHours(0, 0, 0, 0);
            const diffTime = scadenza - oggi;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            return diffDays >= 0 && diffDays <= giorniPreavviso;
        });
        
        const count = scadenzeInScadenza.length;
        const haScadenzeImminenti = count > 0;
        
        // Aggiorna badge sidebar desktop
        const badgeSidebar = document.getElementById('scadenzeBadgeSidebar');
        const alertIcon = document.getElementById('scadenzeAlertIcon');
        
        // Mostra l'icona di avviso se ci sono scadenze in scadenza
        if (alertIcon) {
            if (haScadenzeImminenti) {
                alertIcon.classList.remove('hidden');
            } else {
                alertIcon.classList.add('hidden');
            }
        }
        
        // Nascondi il badge numerico (usiamo solo l'icona)
        if (badgeSidebar) {
            badgeSidebar.classList.add('hidden');
        }
        
        // Aggiorna badge mobile
        const badgeMobile = document.getElementById('scadenzeBadgeMobile');
        if (badgeMobile) {
            if (count > 0) {
                badgeMobile.textContent = count > 99 ? '99+' : count;
                badgeMobile.classList.remove('hidden');
            } else {
                badgeMobile.classList.add('hidden');
            }
        }
        
    } catch (error) {
        console.error('Errore aggiornamento badge scadenze:', error);
    }
}

// Aggiorna il badge ogni 5 minuti
setInterval(aggiornaBadgeScadenze, 5 * 60 * 1000);

// Aggiorna quando la pagina è visibile
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        aggiornaBadgeScadenze();
    }
});

// Aggiorna al caricamento della pagina
document.addEventListener('DOMContentLoaded', aggiornaBadgeScadenze);

// Esponi funzione globalmente per essere chiamata da altre pagine
window.updateScadenzeBadge = aggiornaBadgeScadenze;

// ============================================
// BADGE BLOG CLIENTI - Notifica nuovi contenuti
// ============================================

async function aggiornaBadgeBlogClienti() {
    try {
        const response = await fetch('api/blog_clienti.php?action=count_unread', { credentials: 'same-origin' });
        const data = await response.json();
        
        if (!data.success) return;
        
        const count = data.data.count;
        const badge = document.getElementById('blogClientiBadge');
        
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    } catch (error) {
        console.error('Errore aggiornamento badge blog clienti:', error);
    }
}

// Aggiorna ogni 2 minuti
setInterval(aggiornaBadgeBlogClienti, 2 * 60 * 1000);

// Aggiorna quando la pagina è visibile
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        aggiornaBadgeBlogClienti();
    }
});

// Aggiorna al caricamento della pagina
document.addEventListener('DOMContentLoaded', aggiornaBadgeBlogClienti);

// ============================================
// CSRF PROTECTION - Aggiunge token a tutte le richieste POST
// ============================================

// Ottieni CSRF token dal meta tag
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

// Intercetta tutte le richieste fetch e aggiunge CSRF token ai POST
const originalFetch = window.fetch;
window.fetch = function(url, options = {}) {
    // Se è una richiesta POST, aggiungi il token CSRF
    if (options.method === 'POST' || (!options.method && url instanceof Request && url.method === 'POST')) {
        options = options || {};
        
        // Se il body è FormData, aggiungi il token
        if (options.body instanceof FormData) {
            options.body.append('csrf_token', getCsrfToken());
        }
    }
    
    return originalFetch.call(this, url, options);
};
</script>
