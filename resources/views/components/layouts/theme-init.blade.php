@php($accountAppearance = auth()->user()?->appearance)
<script>
    (() => {
        const key = 'tempmail.theme';
        const normalize = value => ['light', 'dark'].includes(value) ? value : 'system';
        const media = window.matchMedia?.('(prefers-color-scheme: dark)');
        let preference = 'system';
        const accountPreference = @js($accountAppearance);

        try {
            preference = normalize(window.localStorage.getItem(key));
        } catch {}
        if (accountPreference !== null) preference = normalize(accountPreference);

        const theme = {
            preference,
            systemDark: media?.matches ?? false,
            get effective() {
                return this.preference === 'system' ? (this.systemDark ? 'dark' : 'light') : this.preference;
            },
            apply() {
                const root = document.documentElement;
                if (this.preference === 'system') delete root.dataset.theme;
                else root.dataset.theme = this.preference;
            },
            set(value, persist = true) {
                this.preference = normalize(value);
                this.apply();

                if (persist) {
                    try {
                        window.localStorage.setItem(key, this.preference);
                    } catch {}
                }
            },
            init() {
                media?.addEventListener?.('change', event => { this.systemDark = event.matches; });
                window.addEventListener('storage', event => {
                    if (event.key === key || event.key === null) this.set(event.newValue, false);
                });
            },
        };

        theme.apply();
        window.tempMailTheme = theme;
    })();
</script>
