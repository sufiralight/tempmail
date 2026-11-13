import Alpine from 'alpinejs';

function uploadResult(xhr) {
    switch (xhr.status) {
        case 201: return 'Uploaded';
        case 401: return 'Sign in again before uploading.';
        case 403: return 'You do not have permission to upload images.';
        case 413: return 'The server rejected the request size. Choose a smaller image.';
        case 419: return 'The session or security token expired. Refresh this page or sign in again before retrying.';
        case 422: {
            if (xhr.getResponseHeader('Content-Type')?.includes('application/json')) {
                try {
                    const message = JSON.parse(xhr.responseText)?.errors?.image?.[0];
                    if (typeof message === 'string') return message;
                } catch {}
            }
            return 'The file could not be validated. Check its format and size.';
        }
        case 429: {
            const seconds = xhr.getResponseHeader('Retry-After');
            return /^[0-9]{1,6}$/.test(seconds ?? '')
                ? 'Too many uploads. Wait ' + seconds + ' seconds before retrying.'
                : 'Too many uploads. Wait before retrying.';
        }
        case 503: return 'The service is temporarily unavailable. Try again later.';
        case 507: return 'Storage could not save this image. Try again later.';
        default: return 'The upload could not be confirmed. Refresh the library before retrying.';
    }
}

Alpine.data('mediaUpload', () => ({
    busy: false, dragging: false, entries: [], request: null, stopped: false, notice: '',

    drop(event, input) {
        this.dragging = false;
        if (!this.busy && event.dataTransfer.files.length) input.files = event.dataTransfer.files;
    },

    async upload(form) {
        const input = this.$refs.files;
        if (this.busy || !input.files.length) return;
        const files = [...input.files];
        const data = new FormData(form);
        this.entries = files.map(file => ({name: file.name, progress: null, state: 'Waiting'}));
        this.busy = true;
        this.stopped = false;
        try {
            for (const [index, file] of files.entries()) {
                if (this.stopped) break;
                const entry = this.entries[index];
                entry.state = 'Uploading';
                this.notice = 'Uploading ' + entry.name + '.';
                data.set('image', file);
                try {
                    await this.send(form.action, data, entry);
                } catch {
                    this.stopped = true;
                    entry.state = 'Upload could not start. Refresh the page before retrying.';
                }
                this.notice = entry.name + ': ' + entry.state;
            }
        } finally {
            for (const entry of this.entries) {
                if (entry.state === 'Waiting') entry.state = 'Not uploaded';
            }
            if (this.entries.some(entry => entry.id)) await this.refreshCollection();
            this.request = null;
            this.busy = false;
            input.value = '';
        }
    },

    async refreshCollection() {
        try {
            const response = await fetch(location.href, {
                headers: {Accept: 'text/html'}, redirect: 'error', signal: AbortSignal.timeout(10000),
            });
            if (!response.ok) throw new Error('Collection unavailable');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const updated = page.querySelector('[data-media-collection]');
            const current = document.querySelector('[data-media-collection]');
            if (!updated || !current) throw new Error('Collection unavailable');
            current.replaceWith(updated);
            const count = document.querySelector('[data-media-count]');
            const updatedCount = page.querySelector('[data-media-count]');
            if (count && updatedCount) count.textContent = updatedCount.textContent;
            const uploaded = this.entries.filter(entry => entry.id);
            for (const entry of uploaded) {
                entry.visible = !!updated.querySelector('[data-media-id="' + entry.id + '"]');
            }
            this.notice = uploaded.length + ' of ' + this.entries.length + ' files uploaded. '
                + uploaded.filter(entry => entry.visible).length + ' on this page.';
            this.$dispatch('media-collection-updated');
        } catch {
            this.notice = 'Uploads saved. The collection could not be updated. Use the links below to find uploaded images.';
        }
    },

    send(url, data, entry) {
        return new Promise(resolve => {
            const xhr = new XMLHttpRequest();
            this.request = xhr;
            xhr.open('POST', url);
            xhr.timeout = 120000;
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.onprogress = event => {
                if (event.lengthComputable && event.total > 0) entry.progress = 100 * event.loaded / event.total;
            };
            xhr.onload = () => {
                entry.state = uploadResult(xhr);
                if (xhr.status === 201) {
                    try {
                        const {id} = JSON.parse(xhr.responseText);
                        if (Number.isSafeInteger(id) && id > 0) entry.id = id;
                    } catch {}
                }
                if (![201, 413, 422].includes(xhr.status)) this.stopped = true;
            };
            xhr.onerror = () => {
                entry.state = 'Network error. Refresh the library before retrying.';
                this.stopped = true;
            };
            xhr.ontimeout = () => {
                entry.state = 'The upload timed out. Refresh the library before retrying.';
                this.stopped = true;
            };
            xhr.onabort = () => {
                entry.state = 'Cancelled. Refresh to check whether the server saved it.';
            };
            xhr.onloadend = () => {
                xhr.upload.onprogress = null;
                xhr.onload = xhr.onerror = xhr.ontimeout = xhr.onabort = xhr.onloadend = null;
                this.request = null;
                resolve();
            };
            xhr.send(data);
        });
    },

    cancel() {
        this.stopped = true;
        this.request?.abort();
    },

    destroy() {
        this.cancel();
    },
}));

Alpine.data('mediaSelection', () => ({
    selected: [], canRestore: false, notice: '',

    inputs() {
        return [...this.$el.querySelectorAll('input[type="checkbox"][form="bulk-media"]')];
    },

    sync() {
        const inputs = this.inputs();
        const checked = inputs.filter(input => input.checked);
        this.selected = checked.map(input => input.value);
        this.canRestore = checked.length > 0 && checked.every(input => !input.dataset.purgePending);
        this.$refs.master.checked = checked.length > 0 && checked.length === inputs.length;
        this.$refs.master.indeterminate = checked.length > 0 && checked.length < inputs.length;
        this.notice = '';
    },

    togglePage(checked) {
        for (const input of this.inputs()) input.checked = checked;
        this.sync();
    },

    clear(announce = false) {
        this.togglePage(false);
        if (announce) this.notice = 'Selection cleared.';
    },
}));

Alpine.data('mediaPicker', () => ({
    opened: false, preview: '', status: '',
    listeners: null,

    init() {
        this.listeners = new AbortController();
        window.addEventListener('message', event => {
            const frame = this.$el.querySelector('dialog[open] iframe');
            if (!frame || event.origin !== location.origin || event.source !== frame.contentWindow
                || event.data?.type !== 'tempmail-media' || !/^\d+$/.test(event.data.id)) return;
            const expected = '/media/' + event.data.id + '/file?preview=1';
            if (event.data.preview !== expected) return;
            this.$refs.selection.value = event.data.id;
            this.preview = expected;
            this.status = 'Image selected.';
            frame.closest('dialog').close();
        }, {signal: this.listeners.signal});
    },

    destroy() {
        this.listeners?.abort();
    },
}));
