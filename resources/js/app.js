import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import Chart from 'chart.js/auto';

// Register Alpine.js plugins
Alpine.plugin(collapse);
Alpine.plugin(focus);

// Make Alpine and Chart available globally
window.Alpine = Alpine;
window.Chart = Chart;

// Start Alpine
Alpine.start();
