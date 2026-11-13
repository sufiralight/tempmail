import Alpine from 'alpinejs';

Alpine.data('mediaUpload', () => ({
    busy: false, dragging: false, finished: false, entries: [], request: null, stopped: false,

    drop(event, input) {
        this.dragging = false;
        if (!this.busy && event.dataTransfer.files.length) input.files = event.dataTransfer.files;
    },

    async upload(form) {
        if (this.busy) return;
        const files = [...this.$refs.files.files];
        this.entries = files.map(file => ({name: file.name, progress: null, state: 'Waiting'}));
        this.busy = true;
        this.finished = false;
        this.stopped = false;
        try {
            for (const [index, file] of files.entries()) {
                if (this.stopped) break;
                const entry = this.entries[index];
                entry.state = 'Uploading';
                const data = new FormData(form);
                data.set('image', file);
                await new Promise(resolve => {
                    const xhr = new XMLHttpRequest();
                    this.request = xhr;
                    xhr.open('POST', form.action);
                    xhr.timeout = 120000;
                    xhr.setRequestHeader('Accept', 'application/json');
                    xhr.upload.onprogress = event => {
                        if (event.lengthComputable) entry.progress = 100 * event.loaded / event.total;
                    };
                    xhr.onload = () => {
                        if (xhr.status === 201) {
                            entry.state = 'Uploaded';
                            this.finished = true;
                        } else {
                            let message;
                            try { message = JSON.parse(xhr.responseText).errors?.image?.[0]; } catch {}
                            entry.state = message ?? 'Upload failed. Check the file and try again.';
                        }
                    };
                    xhr.onerror = xhr.ontimeout = () => { entry.state = 'Upload interrupted.'; };
                    xhr.onabort = () => { entry.state = 'Cancelled. Refresh to check whether the server saved it.'; };
                    xhr.onloadend = resolve;
                    xhr.send(data);
                });
            }
        } finally {
            for (const entry of this.entries) if (entry.state === 'Waiting') entry.state = 'Cancelled';
            this.request = null;
            this.busy = false;
            this.$refs.files.value = '';
        }
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
            const expected = '/media-library/' + event.data.id + '/file?preview=1';
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
