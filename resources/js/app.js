import Alpine from 'alpinejs';

const themeKey = 'tempmail.theme';
const explicitThemes = ['light', 'dark'];
let savedTheme;

try {
    savedTheme = localStorage.getItem(themeKey);
} catch {
    // Theme selection still works when browser storage is unavailable.
}

Alpine.store('theme', {
    preference: explicitThemes.includes(savedTheme) ? savedTheme : 'system',

    init() {
        this.apply();
    },

    set(preference) {
        this.preference = explicitThemes.includes(preference) ? preference : 'system';
        this.apply();

        try {
            localStorage.setItem(themeKey, this.preference);
        } catch {
            // Keep the selected theme for the current page.
        }
    },

    apply() {
        if (this.preference === 'system') {
            delete document.documentElement.dataset.theme;
        } else {
            document.documentElement.dataset.theme = this.preference;
        }
    },
});

window.Alpine = Alpine;
Alpine.start();
