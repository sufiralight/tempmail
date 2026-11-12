import Alpine from 'alpinejs';

function fitOverlay(element) {
    element.style.removeProperty('--overlay-shift');
    const {left, right} = element.getBoundingClientRect();
    const width = document.documentElement.clientWidth;
    const offset = Math.max(0, 8 - left) - Math.max(0, right - width + 8);
    element.style.setProperty('--overlay-shift', offset + 'px');
}

Alpine.data('uiTooltip', () => ({
    dismissed: false,

    show() {
        this.dismissed = false;
        this.$nextTick(() => fitOverlay(this.$refs.tooltip));
    },
}));

Alpine.data('uiMenu', () => ({
    opened: false,

    items() {
        return [...this.$refs.menu.querySelectorAll('[role^="menuitem"]')]
            .filter(item => !item.disabled && item.getAttribute('aria-disabled') !== 'true' && item.getClientRects().length);
    },

    show(edge = 'first') {
        this.opened = true;
        this.$nextTick(() => {
            this.position();
            const items = this.items();
            ((edge === 'last' ? items.at(-1) : items[0]) ?? this.$refs.menu).focus();
        });
    },

    position() {
        if (!this.opened) return;
        const menu = this.$refs.menu;
        menu.classList.remove('ui-menu-above');
        fitOverlay(menu);
        const {bottom, height} = menu.getBoundingClientRect();
        if (bottom > window.innerHeight && this.$refs.trigger.getBoundingClientRect().top > height + 8) {
            menu.classList.add('ui-menu-above');
        }
    },

    close(restore = false) {
        if (!this.opened) return;
        this.opened = false;
        if (restore) this.$refs.trigger.focus();
    },

    navigate(event) {
        const items = this.items();
        if (!items.length) return;
        const current = items.indexOf(document.activeElement);
        const next = {
            ArrowDown: () => (current + 1) % items.length,
            ArrowUp: () => current <= 0 ? items.length - 1 : current - 1,
            Home: () => 0,
            End: () => items.length - 1,
        }[event.key];
        if (!next) return;
        event.preventDefault();
        items[next()].focus();
    },
}));

let openDialogs = 0;

Alpine.data('uiDialog', () => ({
    opener: null,
    active: false,

    show(opener) {
        if (this.active) return;
        this.opener = opener;
        this.$refs.dialog.showModal();
        this.active = true;
        openDialogs++;
        document.documentElement.classList.add('ui-dialog-open');
    },

    finish() {
        if (!this.active) return;
        this.active = false;
        if (--openDialogs === 0) document.documentElement.classList.remove('ui-dialog-open');
        if (this.opener?.isConnected) this.opener.focus();
    },

    trap(event) {
        const dialog = this.$refs.dialog;
        const elements = [...dialog.querySelectorAll('a[href], button, input, select, textarea, [tabindex], [contenteditable="true"]')]
            .filter(element => element.tabIndex >= 0 && !element.matches(':disabled') && element.getClientRects().length && getComputedStyle(element).visibility === 'visible');
        const first = elements[0];
        const last = elements.at(-1);
        const current = document.activeElement;

        if (!first || (event.shiftKey && current === first) || (!event.shiftKey && current === last)) {
            event.preventDefault();
            (event.shiftKey ? last : first)?.focus();
        }
    },

    destroy() {
        this.finish();
    },
}));

Alpine.data('uiTabs', initial => ({
    selected: initial,

    activate(id) {
        this.selected = id;
    },

    navigate(event) {
        const tabs = [...event.currentTarget.querySelectorAll('[role="tab"]:not(:disabled)')];
        const index = tabs.indexOf(document.activeElement);
        if (index < 0) return;
        const next = {
            ArrowRight: (index + 1) % tabs.length,
            ArrowLeft: (index + tabs.length - 1) % tabs.length,
            Home: 0,
            End: tabs.length - 1,
        }[event.key];
        if (next === undefined) return;
        event.preventDefault();
        tabs[next].focus();
        tabs[next].click();
    },
}));

const busyForms = new WeakSet();

document.addEventListener('submit', event => {
    const form = event.target;
    if (busyForms.has(form)) {
        event.preventDefault();
        return;
    }

    const submitter = event.submitter;
    if (!submitter?.matches('[data-ui-submit]')) return;
    busyForms.add(form);

    // Leave the submitter enabled while the browser serializes its name and value.
    queueMicrotask(() => {
        if (event.defaultPrevented) {
            busyForms.delete(form);
            return;
        }
        submitter.dataset.loading = 'true';
        submitter.setAttribute('aria-busy', 'true');
        submitter.setAttribute('aria-disabled', 'true');
    });
});

window.addEventListener('pageshow', () => {
    for (const button of document.querySelectorAll('[data-ui-submit][data-loading="true"]')) {
        busyForms.delete(button.form);
        delete button.dataset.loading;
        button.removeAttribute('aria-busy');
        button.removeAttribute('aria-disabled');
    }
});
