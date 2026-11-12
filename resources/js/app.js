import Alpine from 'alpinejs';
import './ui.js';

Alpine.store('theme', window.tempMailTheme);
window.Alpine = Alpine;
Alpine.start();
