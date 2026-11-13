import Alpine from 'alpinejs';
import './ui.js';
import './media.js';
import './password-strength.js';
import {mountPageProgress} from './page-progress.js';

Alpine.store('theme', window.tempMailTheme);
window.Alpine = Alpine;
window.tempMailPageProgress = mountPageProgress(document.querySelector('[data-page-progress-root]'));
Alpine.start();
