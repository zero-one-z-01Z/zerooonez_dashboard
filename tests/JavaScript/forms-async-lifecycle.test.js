import test from 'node:test';
import assert from 'node:assert/strict';
import { setTimeout as delay } from 'node:timers/promises';
import { populateFormFromAjax, cancelPendingFormPopulation } from '../../resources/js/back/forms/population.js';
import { customFunction } from '../../resources/js/back/forms/submission.js';
import { createFormDom } from './helpers/form-dom.js';

/** Keeps HTTP completion under test control so old responses can arrive after replacement or cancellation. */
function createAsyncForms() {
    const dom = createFormDom();
    const requests = [];
    const filled = [];
    dom.window.setFormData = (data, selector) => filled.push({ data, form: dom.$(selector)[0] });
    dom.$.ajax = options => {
        const xhr = {
            options, aborted: false,
            abort() {
                this.aborted = true;
                options.error?.(this, 'abort', 'abort');
                options.complete?.();
            }
        };
        options.beforeSend?.();
        requests.push(xhr);
        return xhr;
    };
    return { ...dom, requests, filled };
}

test('newer edit GET wins over an older same-form request and the older completion cannot hide its loader', () => {
    const dom = createAsyncForms();
    const form = document.append(dom.element('form', { id: 'edit_form' }));
    const old = populateFormFromAjax('/records/1', '#edit_form');
    const current = populateFormFromAjax('/records/2', '#edit_form');
    assert.equal(old.aborted, true);
    assert.equal(current.aborted, false);
    old.options.success({ data: { id: 1 } });
    old.options.complete();
    assert.equal(dom.loader.includes('off'), false);
    current.options.success({ data: { id: 2 } });
    assert.deepEqual(dom.filled, [{ data: { id: 2 }, form }]);
    assert.equal(form.resetCalls, 1);
    current.options.complete();
    assert.equal(dom.loader.filter(value => value === 'off').length, 1);
});

test('a late GET cannot reset or populate a replacement form with the same id or clear another Dropzone', () => {
    const dom = createAsyncForms();
    const original = document.append(dom.element('form', { id: 'edit_form' }));
    const request = populateFormFromAjax('/records/old', '#edit_form');
    original.remove();
    const replacement = document.append(dom.element('form', { id: 'edit_form' }));
    const uploader = replacement.append(dom.element('div'));
    let cleared = 0;
    window.myDropzone = { element: uploader, removeAllFiles() { cleared++; } };
    request.options.success({ data: { id: 5 } });
    request.options.error({}, 'error', 'A late network error');
    request.options.complete();
    assert.equal(original.resetCalls, 0);
    assert.equal(replacement.resetCalls, 0);
    assert.equal(cleared, 0);
    assert.deepEqual(dom.filled, []);
});

test('cancelling one modal host aborts only its edit GETs, preserving another form request and POST mutations', () => {
    const dom = createAsyncForms();
    const firstHost = document.append(dom.element('div', { id: 'first-host' }));
    const secondHost = document.append(dom.element('div', { id: 'second-host' }));
    const first = firstHost.append(dom.element('form', { id: 'first-form' }));
    const second = secondHost.append(dom.element('form', { id: 'second-form' }));
    const old = populateFormFromAjax('/records/1', '#first-form');
    const other = populateFormFromAjax('/records/2', '#second-form');
    const mutation = $.ajax({ type: 'POST', url: '/records/save' });
    assert.equal(cancelPendingFormPopulation(firstHost), 1);
    assert.equal(old.aborted, true);
    assert.equal(other.aborted, false);
    assert.equal(mutation.aborted, false);
    assert.equal(dom.loader.includes('off'), false);
    assert.equal(cancelPendingFormPopulation(firstHost), 0);
    old.options.success({ data: { id: 1 } });
    assert.equal(first.resetCalls, 0);
    other.options.success({ data: { id: 2 } });
    other.options.complete();
    assert.deepEqual(dom.filled, [{ data: { id: 2 }, form: second }]);
    assert.equal(dom.loader.at(-1), 'off');
});

test('cancellation accepts a concrete detached form and ignores missing hosts or absent forms', () => {
    const dom = createAsyncForms();
    assert.equal(populateFormFromAjax('/records/1', '#missing-form'), null);
    assert.equal(dom.requests.length, 0);
    const form = document.append(dom.element('form', { id: 'edit_form' }));
    const request = populateFormFromAjax('/records/1', '#edit_form');
    assert.equal(cancelPendingFormPopulation(null), 0);
    form.remove();
    assert.equal(cancelPendingFormPopulation(dom.$(form)), 1);
    assert.equal(request.aborted, true);
    request.options.success({ data: { id: 1 } });
    assert.deepEqual(dom.filled, []);
});

test('current GET resets only the uploader belonging to its own form', () => {
    const dom = createAsyncForms();
    const form = document.append(dom.element('form', { id: 'edit_form' }));
    const other = document.append(dom.element('form', { id: 'other-form' }));
    const uploader = other.append(dom.element('div'));
    let cleared = 0;
    window.myDropzone = { element: uploader, removeAllFiles() { cleared++; } };
    const first = populateFormFromAjax('/records/1', '#edit_form');
    first.options.success({ data: { id: 1 } });
    first.options.complete();
    assert.equal(cleared, 0);
    const ownUploader = form.append(dom.element('div'));
    window.myDropzone = { element: ownUploader, removeAllFiles() { cleared++; } };
    const second = populateFormFromAjax('/records/2', '#edit_form');
    second.options.success({ data: { id: 2 } });
    second.options.complete();
    assert.equal(cleared, 1);
    assert.equal(form.resetCalls, 2);
});

/** Supplies only FormData's constructor/append contract so the request keeps the concrete submitted form. */
function captureFormData(t) {
    const original = globalThis.FormData;
    globalThis.FormData = class {
        constructor(form) { this.form = form; this.files = []; }
        append(...args) { this.files.push(args); }
    };
    t.after(() => { globalThis.FormData = original; });
}

test('custom mutation completes but its old success does not reset or reload a replaced tab', async t => {
    captureFormData(t);
    const dom = createAsyncForms();
    const form = document.append(dom.element('form', { id: 'custom-form', action: '/old-record/store', method: 'POST' }));
    let oldReloads = 0;
    let newReloads = 0;
    let notifications = 0;
    let newUploaderResets = 0;
    window.pageTable = { ajax: { reload() { oldReloads++; } } };
    window.myDropzone = { files: [], removeAllFiles() { throw new Error('A stale form uploader must remain untouched'); } };
    globalThis.notifySuccess = () => { notifications++; };
    globalThis.success = 'Saved';
    customFunction({ preventDefault() {} }, dom.$(form));
    form.remove();
    window.pageTable = { ajax: { reload() { newReloads++; } } };
    window.myDropzone = { removeAllFiles() { newUploaderResets++; } };
    await delay(75);
    const request = dom.requests[0];
    assert.equal(request.options.type, 'POST');
    assert.equal(request.options.url, '/old-record/store');
    assert.equal(request.options.data.form, form);
    assert.equal(request.aborted, false);
    request.options.success({});
    request.options.complete();
    assert.equal(form.resetCalls, 0);
    assert.equal(oldReloads, 0);
    assert.equal(newReloads, 0);
    assert.equal(newUploaderResets, 0);
    assert.equal(notifications, 0);
});

test('custom mutation success uses its captured table and uploader while its form still exists', async t => {
    captureFormData(t);
    const dom = createAsyncForms();
    const modal = document.append(dom.element('div', { class: 'modal' }));
    const form = modal.append(dom.element('form', { action: '/record/store', method: 'POST' }));
    let reloads = 0;
    let originalUploads = 0;
    let newUploads = 0;
    let notifications = 0;
    window.pageTable = { ajax: { reload(reset, paging) { assert.equal(reset, null); assert.equal(paging, false); reloads++; } } };
    window.myDropzone = { files: [], removeAllFiles() { originalUploads++; } };
    globalThis.notifySuccess = () => { notifications++; };
    globalThis.success = 'Saved';
    customFunction({ preventDefault() {} }, dom.$(form));
    window.myDropzone = { removeAllFiles() { newUploads++; } };
    await delay(75);
    dom.requests[0].options.success({});
    dom.requests[0].options.complete();
    assert.equal(reloads, 1);
    assert.equal(form.resetCalls, 1);
    assert.equal(form.validationResets, 1);
    assert.equal(originalUploads, 1);
    assert.equal(newUploads, 0);
    assert.equal(notifications, 1);
});
