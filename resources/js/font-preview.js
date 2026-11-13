const fallbackStacks = {
    system: 'ui-sans-serif, system-ui, sans-serif',
    sans: 'Arial, sans-serif',
    serif: 'Georgia, serif',
};

export function fontPreview() {
    return {
        update(event) {
            const form = event.target.closest('[data-font-role]');
            if (!form) return;

            const role = form.dataset.fontRole;
            const name = form.elements.font_family_id.selectedOptions[0].dataset.family;
            const weight = form.elements.weight.value;
            const fallback = fallbackStacks[form.elements.fallback.value] ?? fallbackStacks.system;
            const sample = this.$refs.sample;

            if (name && /^[A-Za-z ]+$/.test(name) && ['400', '600'].includes(weight)) {
                const url = new URL('https://fonts.googleapis.com/css2');
                url.searchParams.set('family', name + ':wght@' + weight);
                url.searchParams.set('display', 'swap');
                const key = name + ':' + weight;
                const existing = Array.from(document.querySelectorAll('link[rel=stylesheet]')).some(link =>
                    new URL(link.href).searchParams.getAll('family').some(value => {
                        const [family, weights] = value.split(':wght@');
                        return family === name && weights?.split(';').includes(weight);
                    }));
                if (!existing) {
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = url.href;
                    link.dataset.fontPreview = key;
                    link.addEventListener('error', () => {
                        this.$refs.status.textContent = 'The preview font could not load. The fallback stack is in use.';
                        link.remove();
                    }, { once: true });
                    document.head.append(link);
                }
                sample.style.setProperty('--font-' + role, '"' + name + '", Geist Variable, ' + fallback);
            } else {
                sample.style.setProperty('--font-' + role, 'Geist Variable, ' + fallback);
            }
            sample.style.setProperty('--weight-' + role, weight);
            this.$refs.status.textContent = 'Preview updated. Save to apply this change.';
        },
    };
}
