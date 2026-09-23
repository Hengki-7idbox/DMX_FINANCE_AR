import './bootstrap';
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';

// Register plugins
Alpine.plugin(persist);

// Make Alpine available globally
window.Alpine = Alpine;

// Start Alpine
Alpine.start();

// Global utility functions
window.FinanceAR = {
    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    },

    formatDate(date) {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    },

    formatDateTime(date) {
        if (!date) return '-';
        return new Date(date).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },

    async confirm(message = 'Are you sure?') {
        return window.confirm(message);
    },

    initDataTable(selector, options = {}) {
        const defaults = {
            pageLength: 25,
            order: [],
            language: {
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data',
                infoFiltered: '(disaring dari _MAX_ total data)',
                paginate: {
                    first: 'Pertama',
                    last: 'Terakhir',
                    next: 'Selanjutnya',
                    previous: 'Sebelumnya'
                }
            }
        };

        return $(selector).DataTable({ ...defaults, ...options });
    }
};
