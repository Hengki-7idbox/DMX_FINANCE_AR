import './bootstrap';
import Alpine from 'alpinejs';
import { createIcons, icons } from 'lucide';

// Make Alpine available globally
window.Alpine = Alpine;

// Initialize Lucide icons
window.lucide = { createIcons: () => createIcons({ icons }) };

// Start Alpine
Alpine.start();
