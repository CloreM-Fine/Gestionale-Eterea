/**
 * Eterea Gestionale
 * Main Application JavaScript
 */

// Global app namespace
const LDEApp = {
    // Configuration
    config: {
        apiBaseUrl: 'api/',
        debounceDelay: 300,
        toastDuration: 5000
    },

    // State
    state: {
        currentUser: null,
        isLoading: false,
        activeModal: null
    },

    /**
     * Initialize application
     */
    init() {
        this.setupEventListeners();
        this.setupTooltips();
        this.checkSession();
    },

    /**
     * Setup global event listeners
     */
    setupEventListeners() {
        // Close modals on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.state.activeModal) {
                this.closeModal(this.state.activeModal);
            }
        });

        // Handle API errors globally
        window.addEventListener('unhandledrejection', (e) => {
            if (e.reason?.message?.includes('Failed to fetch')) {
                this.showToast('Errore di connessione al server', 'error');
            }
        });

        // Auto-hide dropdowns on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
    },

    /**
     * Setup tooltips
     */
    setupTooltips() {
        const tooltips = document.querySelectorAll('[data-tooltip]');
        tooltips.forEach(el => {
            el.classList.add('tooltip');
        });
    },

    /**
     * Check user session
     */
    async checkSession() {
        try {
            const response = await fetch('api/auth.php?action=check');
            const data = await response.json();
            
            if (data.success) {
                this.state.currentUser = data.data;
            } else if (!window.location.href.includes('index.php')) {
                window.location.href = 'index.php?error=session_expired';
            }
        } catch (error) {
            console.error('Session check failed:', error);
        }
    },

    /**
     * Show toast notification
     */
    showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

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
        requestAnimationFrame(() => {
            toast.classList.remove('translate-x-full');
        });

        // Auto remove
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, this.config.toastDuration);
    },

    /**
     * Open modal
     */
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            this.state.activeModal = modalId;
            document.body.style.overflow = 'hidden';
        }
    },

    /**
     * Close modal
     */
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            if (this.state.activeModal === modalId) {
                this.state.activeModal = null;
            }
            document.body.style.overflow = '';
        }
    },

    /**
     * Confirm action
     */
    confirm(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },

    /**
     * Format currency
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('it-IT', {
            style: 'currency',
            currency: 'EUR'
        }).format(amount);
    },

    /**
     * Format date
     */
    formatDate(dateString, options = {}) {
        if (!dateString) return '-';
        
        const defaultOptions = { day: 'numeric', month: 'short', year: 'numeric' };
        return new Date(dateString).toLocaleDateString('it-IT', { ...defaultOptions, ...options });
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * API GET request
     */
    async get(url) {
        const response = await fetch(this.config.apiBaseUrl + url);
        return response.json();
    },

    /**
     * API POST request
     */
    async post(url, data) {
        const options = {
            method: 'POST',
            body: data instanceof FormData ? data : new URLSearchParams(data)
        };
        
        const response = await fetch(this.config.apiBaseUrl + url, options);
        return response.json();
    },

    /**
     * Set loading state
     */
    setLoading(element, loading) {
        if (loading) {
            element.classList.add('opacity-50', 'pointer-events-none');
            element.dataset.originalText = element.innerHTML;
            element.innerHTML = `
                <svg class="animate-spin h-5 w-5 text-current" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            `;
        } else {
            element.classList.remove('opacity-50', 'pointer-events-none');
            if (element.dataset.originalText) {
                element.innerHTML = element.dataset.originalText;
            }
        }
    },

    /**
     * Handle API errors
     */
    handleError(error, defaultMessage = 'Si è verificato un errore') {
        console.error('API Error:', error);
        this.showToast(error.message || defaultMessage, 'error');
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    LDEApp.init();
});

// Expose globally for inline scripts
window.LDEApp = LDEApp;
window.showToast = (message, type) => LDEApp.showToast(message, type);
window.openModal = (modalId) => LDEApp.openModal(modalId);
window.closeModal = (modalId) => LDEApp.closeModal(modalId);
