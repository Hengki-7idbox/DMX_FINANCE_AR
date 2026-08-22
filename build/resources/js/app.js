import Alpine from 'alpinejs';
import * as lucide from 'lucide';

// Make Alpine available globally
window.Alpine = Alpine;

// Initialize Lucide icons
lucide.createIcons();

// Re-initialize icons after Alpine updates
document.addEventListener('alpine:initialized', () => {
    const observer = new MutationObserver(() => {
        lucide.createIcons();
    });
    observer.observe(document.body, { childList: true, subtree: true });
});

// Toast notification system
Alpine.store('toast', {
    toasts: [],
    show(message, type = 'success', duration = 3000) {
        const id = Date.now();
        this.toasts.push({ id, message, type, show: true });
        setTimeout(() => this.dismiss(id), duration);
    },
    dismiss(id) {
        this.toasts = this.toasts.filter(t => t.id !== id);
    }
});

// Dark mode
Alpine.store('darkMode', {
    on: localStorage.getItem('theme') === 'dark' ||
        (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),

    toggle() {
        this.on = !this.on;
        localStorage.setItem('theme', this.on ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.on);
    },

    init() {
        document.documentElement.classList.toggle('dark', this.on);
    }
});

// Sidebar state
Alpine.store('sidebar', {
    collapsed: localStorage.getItem('sidebar-collapsed') === 'true',
    mobileOpen: false,

    toggle() {
        this.collapsed = !this.collapsed;
        localStorage.setItem('sidebar-collapsed', this.collapsed);
    },

    closeMobile() {
        this.mobileOpen = false;
    }
});

Alpine.start();
