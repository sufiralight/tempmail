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
    busy: false, dragging: false, finished: false, entries: [], request: null, stopped: false, notice: '',

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
        this.finished = false;
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
                this.finished ||= entry.state === 'Uploaded';
                this.notice = entry.name + ': ' + entry.state;
            }
        } finally {
            for (const entry of this.entries) {
                if (entry.state === 'Waiting') entry.state = 'Not uploaded';
            }
            this.request = null;
            this.busy = false;
            input.value = '';
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
