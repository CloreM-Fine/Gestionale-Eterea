    </main>
    
    <!-- Toast Container -->
    <div id="toastContainer" class="fixed bottom-4 right-4 z-50 space-y-2"></div>
    
    <!-- Scripts -->
    <script>
    // Mobile menu functions
    function openMobileMenu() {
        document.getElementById('sidebar').classList.add('open');
        document.getElementById('mobileOverlay').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMobileMenu() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('mobileOverlay').classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    // Modal functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
    
    // Toast notification
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        
        const colors = {
            success: 'bg-emerald-500',
            error: 'bg-red-500',
            warning: 'bg-amber-500',
            info: 'bg-cyan-500'
        };
        
        const icons = {
            success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>',
            error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>',
            warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
            info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        };
        
        toast.className = `${colors[type]} text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 min-w-[300px] transform translate-x-full transition-transform duration-300`;
        toast.innerHTML = `
            ${icons[type]}
            <span class="font-medium">${message}</span>
            <button onclick="this.parentElement.remove()" class="ml-auto hover:opacity-70">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;
        
        container.appendChild(toast);
        
        // Animate in
        setTimeout(() => toast.classList.remove('translate-x-full'), 10);
        
        // Auto remove
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    
    // Format currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    }
    
    // Format date
    function formatDate(dateString) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('it-IT');
    }
    
    // Confirm modal
    function confirmAction(message, callback) {
        if (confirm(message)) {
            callback();
        }
    }
    
    // API helper
    async function apiGet(url) {
        const response = await fetch(url);
        return response.json();
    }
    
    async function apiPost(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            body: data instanceof FormData ? data : new URLSearchParams(data)
        });
        return response.json();
    }
    
    // =====================================================
    // SISTEMA NOTIFICHE
    // =====================================================
    let notificheAperte = false;
    
    async function caricaNotifiche() {
        try {
            const response = await fetch('api/notifiche.php?action=list');
            const data = await response.json();
            
            if (data.success) {
                renderNotifiche(data.data);
            }
        } catch (error) {
            console.error('Errore caricamento notifiche:', error);
        }
    }
    
    async function caricaCountNotifiche() {
        try {
            const response = await fetch('api/notifiche.php?action=count');
            const data = await response.json();
            
            if (data.success) {
                const badge = document.getElementById('notificheBadge');
                if (data.data.count > 0) {
                    badge.textContent = data.data.count > 9 ? '9+' : data.data.count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        } catch (error) {
            console.error('Errore count notifiche:', error);
        }
    }
    
    function renderNotifiche(notifiche) {
        const list = document.getElementById('notificheList');
        const countLabel = document.getElementById('notificheCount');
        
        // Aggiorna conteggio
        if (countLabel) {
            countLabel.textContent = notifiche.length + ' notifiche';
        }
        
        if (notifiche.length === 0) {
            list.innerHTML = '<p class="px-4 py-8 text-center text-slate-500 text-sm">Nessuna notifica</p>';
            return;
        }
        
        const iconMap = {
            'appuntamento': '<span class="w-8 h-8 bg-cyan-100 text-cyan-600 rounded-full flex items-center justify-center"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>',
            'cliente': '<span class="w-8 h-8 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg></span>',
            'progetto_creato': '<span class="w-8 h-8 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg></span>',
            'progetto_consegnato': '<span class="w-8 h-8 bg-green-100 text-green-600 rounded-full flex items-center justify-center"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>',
            'task': '<span class="w-8 h-8 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span>'
        };
        
        // Funzione per generare URL in base al tipo di notifica
        function getNotificaUrl(n) {
            if (n.entita_tipo === 'cliente' && n.entita_id) {
                return `clienti.php?open=${n.entita_id}`;
            } else if (n.entita_tipo === 'progetto' && n.entita_id) {
                return `progetto_dettaglio.php?id=${n.entita_id}`;
            } else if (n.entita_tipo === 'appuntamento') {
                return 'calendario.php';
            } else if (n.entita_tipo === 'task' && n.progetto_id) {
                // Per task, redirige al progetto con la sezione task aperta
                return 'progetto_dettaglio.php?id=' + n.progetto_id + '&section=task';
            }
            return null;
        }
        
        list.innerHTML = notifiche.map(n => {
            const url = getNotificaUrl(n);
            const cursorClass = url ? 'cursor-pointer' : 'cursor-default';
            const clickAction = url ? `onclick="handleNotificaClick('${n.id}', '${url}')"` : `onclick="markNotificaLette('${n.id}')"`;
            
            return `
            <div class="px-4 py-3 hover:bg-slate-50 border-b border-slate-100 last:border-0 ${n.letta ? 'opacity-60' : ''} ${cursorClass}" ${clickAction}>
                <div class="flex items-start gap-3">
                    ${iconMap[n.tipo] || iconMap['appuntamento']}
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-slate-800 text-sm">${n.titolo}</p>
                        <p class="text-slate-500 text-xs truncate">${n.messaggio}</p>
                        <p class="text-slate-400 text-xs mt-1">${new Date(n.data_creazione).toLocaleString('it-IT')}</p>
                    </div>
                    ${!n.letta ? '<span class="w-2 h-2 bg-cyan-500 rounded-full mt-2"></span>' : ''}
                </div>
            </div>
        `}).join('');
    }
    
    function toggleNotifiche() {
        const menu = document.getElementById('notificheMenu');
        notificheAperte = !notificheAperte;
        
        if (notificheAperte) {
            menu.classList.remove('hidden');
            caricaNotifiche();
        } else {
            menu.classList.add('hidden');
        }
    }
    
    async function handleNotificaClick(id, url) {
        // Marca come letta e naviga
        try {
            await fetch('api/notifiche.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=mark_read&id=${id}`
            });
            // Chiudi il menu notifiche
            document.getElementById('notificheMenu').classList.add('hidden');
            notificheAperte = false;
            // Naviga alla pagina
            window.location.href = url;
        } catch (error) {
            console.error('Errore:', error);
        }
    }
    
    async function markNotificaLette(id) {
        try {
            await fetch('api/notifiche.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=mark_read&id=${id}`
            });
            caricaNotifiche();
            caricaCountNotifiche();
        } catch (error) {
            console.error('Errore:', error);
        }
    }
    
    async function markAllNotificheLette() {
        try {
            await fetch('api/notifiche.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_all_read'
            });
            caricaNotifiche();
            caricaCountNotifiche();
        } catch (error) {
            console.error('Errore:', error);
        }
    }
    
    async function deleteAllNotifiche() {
        if (!confirm('Sei sicuro di voler eliminare tutte le notifiche?')) {
            return;
        }
        
        try {
            const response = await fetch('api/notifiche.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=delete_all'
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Tutte le notifiche eliminate', 'success');
                caricaNotifiche();
                caricaCountNotifiche();
            } else {
                showToast(data.message || 'Errore eliminazione', 'error');
            }
        } catch (error) {
            console.error('Errore:', error);
            showToast('Errore di connessione', 'error');
        }
    }
    
    // Carica notifiche all'avvio e ogni 30 secondi
    document.addEventListener('DOMContentLoaded', function() {
        caricaCountNotifiche();
        setInterval(caricaCountNotifiche, 30000);
        
        // Chiudi dropdown cliccando fuori
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notificheDropdown');
            if (dropdown && !dropdown.contains(e.target) && notificheAperte) {
                document.getElementById('notificheMenu').classList.add('hidden');
                notificheAperte = false;
            }
        });
        
        // Carica conteggio scadenze
        updateScadenzeBadge();
    });
    
    // =====================================================
    // NOTIFICHE SCADENZE
    // =====================================================
    async function updateScadenzeBadge() {
        try {
            const response = await fetch('api/scadenze.php?action=count_oggi', { credentials: 'same-origin' });
            const data = await response.json();
            
            if (data.success) {
                const count = data.count;
                const badgeSidebar = document.getElementById('scadenzeBadgeSidebar');
                const badgeMobile = document.getElementById('scadenzeBadgeMobile');
                
                if (count > 0) {
                    if (badgeSidebar) {
                        badgeSidebar.textContent = count > 99 ? '99+' : count;
                        badgeSidebar.classList.remove('hidden');
                    }
                    if (badgeMobile) {
                        badgeMobile.textContent = count > 99 ? '99+' : count;
                        badgeMobile.classList.remove('hidden');
                    }
                } else {
                    if (badgeSidebar) badgeSidebar.classList.add('hidden');
                    if (badgeMobile) badgeMobile.classList.add('hidden');
                }
            }
        } catch (error) {
            console.error('Errore caricamento scadenze:', error);
        }
    }
    
    // Espone la funzione globalmente per essere chiamata da altre pagine
    window.updateScadenzeBadge = updateScadenzeBadge;
    </script>
</body>
</html>
