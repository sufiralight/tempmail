import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import test from 'node:test';
import {runInNewContext} from 'node:vm';

const source = readFileSync(new URL('../../resources/js/media.js', import.meta.url), 'utf8');

function fixture() {
    const requests = [];
    let upload;
    class Request {
        upload = {};
        open() {}
        setRequestHeader() {}
        getResponseHeader() { return null; }
        send(body) { this.file = body.get('image'); requests.push(this); }
        respond(status) {
            this.status = status;
            this.responseText = '<html>Private debug text</html>';
            this.onload();
            this.onloadend();
        }
        abort() { this.onabort(); this.onloadend(); }
    }
    class FormData extends Map {
        constructor(form) { super([['_token', form.token]]); }
    }
    runInNewContext(source.replace(/^import Alpine.*;$/m, ''), {
        Alpine: {data(name, factory) { if (name === 'mediaUpload') upload = factory(); }},
        XMLHttpRequest: Request,
        FormData,
    });
    upload.$refs = {files: {files: [{name: 'one.png'}, {name: 'two.png'}, {name: 'three.png'}]}};
    return {upload, requests};
}

test('measures progress, prevents duplicate submissions and preserves partial success on cancellation', async () => {
    const {upload, requests} = fixture();
    const batch = upload.upload({action: '/media', token: 'fixture'});
    await upload.upload({action: '/media'});
    assert.equal(requests.length, 1);
    requests[0].upload.onprogress({lengthComputable: true, loaded: 1, total: 4});
    assert.equal(upload.entries[0].progress, 25);
    requests[0].respond(201);
    await Promise.resolve();
    assert.equal(requests[1].file.name, 'two.png');
    upload.cancel();
    await batch;
    assert.equal(upload.entries[0].state, 'Uploaded');
    assert.match(upload.entries[1].state, /^Cancelled/);
    assert.equal(upload.entries[2].state, 'Not uploaded');
    assert.equal(upload.busy, false);
    assert.equal(upload.request, null);
});

test('stops after an HTML 419 without exposing its body or replaying the batch', async () => {
    const {upload, requests} = fixture();
    const batch = upload.upload({action: '/media', token: 'stale'});
    requests[0].respond(419);
    await batch;
    assert.equal(requests.length, 1);
    assert.match(upload.entries[0].state, /security token expired/);
    assert.equal(upload.entries[1].state, 'Not uploaded');
});
test('handles status-specific HTML errors without displaying response content', async () => {
    for (const [status, expected] of [
        [401, /Sign in/], [403, /permission/], [413, /request size/], [422, /validated/],
        [429, /Too many uploads/], [500, /could not be confirmed/], [503, /temporarily unavailable/],
        [507, /Storage could not save/],
    ]) {
        const {upload, requests} = fixture();
        upload.$refs.files.files.length = 1;
        const batch = upload.upload({action: '/media', token: 'fixture'});
        requests[0].respond(status);
        await batch;
        assert.match(upload.entries[0].state, expected);
        assert.equal(upload.busy, false);
    }
});

test('selection tracks only rendered checkboxes and prevents restoring pending purges', () => {
    let selection;
    runInNewContext(source.replace(/^import Alpine.*;$/m, ''), {
        Alpine: {data(name, factory) { if (name === 'mediaSelection') selection = factory(); }},
    });
    const inputs = [
        {value: '2', checked: true, dataset: {}},
        {value: '5', checked: false, dataset: {purgePending: '1'}},
    ];
    selection.$el = {querySelectorAll: () => inputs};
    selection.$refs = {master: {}};
    selection.sync();
    assert.equal(selection.$refs.master.indeterminate, true);
    assert.equal(selection.canRestore, true);
    selection.togglePage(true);
    assert.equal(selection.selected.length, 2);
    assert.equal(selection.$refs.master.checked, true);
    assert.equal(selection.canRestore, false);
    selection.clear(true);
    assert.equal(selection.selected.length, 0);
    assert.equal(selection.$refs.master.checked, false);
    assert.equal(selection.notice, 'Selection cleared.');
});

test('refreshes the current collection after partial success and preserves per-file results', async () => {
    const {upload, requests} = fixture();
    let refreshes = 0;
    upload.refreshCollection = async () => { refreshes++; };
    upload.$refs.files.files.length = 2;
    const batch = upload.upload({action: '/media', token: 'fixture'});
    requests[0].status = 201;
    requests[0].responseText = '{"id":42}';
    requests[0].onload();
    requests[0].onloadend();
    await Promise.resolve();
    requests[1].respond(422);
    await batch;
    assert.equal(refreshes, 1);
    assert.equal(upload.entries[0].id, 42);
    assert.match(upload.entries[1].state, /validated/);
});
