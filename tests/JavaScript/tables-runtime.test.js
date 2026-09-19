import test from 'node:test';
import assert from 'node:assert/strict';
import { collectFormData } from '../../resources/js/back/tables/records.js';

const readyCallbacks = [];
const events = [];
const tableOptions = [];
let initialized = false;
let clearCount = 0;
let destroyCount = 0;
let abortedDraws = 0;
let reloads = [];
const table = {
    settings: () => [{ jqXHR: { abort() { abortedDraws++; } } }],
    ajax: { reload: (...args) => reloads.push(args) },
    clear() { clearCount++; return this; },
    destroy() { destroyCount++; initialized = false; },
};

globalThis.document = { getElementById: () => null, querySelector: () => null };
globalThis.CustomEvent = class { constructor(type) { this.type = type; } };
globalThis.window = {
    dispatchEvent(event) {
        events.push({ type: event.type, ready: ['prepareDataTable', 'destroyDataTable', 'setUpDatatable', 'initializeDataTable', 'ajax_exe', 'defineFormValidation', 'populateFormFromAjax', 'create_item', 'update_item', 'update_val'].every(key => typeof this[key] === 'function') });
    },
};

globalThis.$ = selector => ({
    length: selector === '#table_id' || selector === document ? 1 : 0,
    ready: callback => { readyCallbacks.push(callback); },
    attr() { return 'test-csrf'; },
    text() { return this; },
    off() { return this; },
    on() { return this; },
    DataTable(options) {
        if (options) { initialized = true; tableOptions.push(options); }
        return table;
    },
});
$.ajaxSetup = () => {};
$.escapeSelector = value => value;
$.fn = { dataTable: { isDataTable: () => initialized } };
$.extend = (deep, target, defaults, overrides) => Object.assign(target, defaults, overrides);

await import('../../resources/js/back/table.js');

test('legacy entry publishes every table API and dependency before readiness/DOMContentLoaded', () => {
    const globals = ['prepareDataTable', 'setUpDatatable', 'destroyDataTable', 'edit_item', 'fill_form', 'create_item', 'update_item', 'delete_item', 'update_val',
        'initializeDataTable', 'replaceUrlPlaceholder', 'replaceUrlPlaceholder_v2', 'show_add_modal', 'show_import_modal', 'closeFilterModal', 'deleteButton', 'clickFilterBadge', 'clearFilters'];
    for (const name of globals) assert.equal(typeof window[name], 'function', `${name} is synchronously ready`);
    assert.ok(readyCallbacks.length > 0, 'DOM ready callbacks have been queued, not executed');
    assert.ok(events.some(event => event.type === 'dashboard:table-api-ready' && event.ready));
});

test('actual engine initializes once, supports repeated setup, and starts a clean AJAX tab after destroy', () => {
    window.all_permissions = ['view'];
    window.prepareDataTable({ show_list: [{ key: 'name', type: 'text' }], permissions: ['view'], datatable_url: '/first', internal_filters: [{ key: 'owner', value: 5 }] });
    assert.equal(window.setUpDatatable(), table);
    assert.equal(window.setUpDatatable(), table);
    assert.equal(tableOptions.length, 1);
    assert.equal(tableOptions[0].ajax.type, 'POST');
    assert.equal(tableOptions[0].ajax.url, '/first');
    const firstRequest = { draw: 1 };
    tableOptions[0].ajax.data(firstRequest);
    assert.deepEqual(firstRequest, { draw: 1, filters: { owner: 5 } });
    window.destroyDataTable();
    assert.equal(clearCount, 1);
    assert.equal(destroyCount, 1);
    assert.equal(abortedDraws, 1);
    assert.equal(window.pageTable, null);
    window.prepareDataTable({ show_list: [{ key: 'title', type: 'text' }], datatable_url: '/second' });
    window.setUpDatatable();
    assert.equal(tableOptions.length, 2);
    const secondRequest = {};
    tableOptions[1].ajax.data(secondRequest);
    assert.deepEqual(secondRequest.filters, {});
    assert.equal(tableOptions[1].columns[1].data, 'title');
});

test('switch updates use explicit data without implicit browser event or global temporary form', () => {
    let request;
    delete globalThis.event;
    delete globalThis.$obj;
    window.ajax_exe = value => { request = value; };
    window.update_val('/status', { id: 8, value: 0 });
    assert.equal(request.url, '/status');
    assert.equal(request.type, 'post');
    assert.deepEqual([...request.data.entries()], [['id', '8'], ['value', '0']]);
    assert.equal(request.showLoading, true);
});

test('record create/update preserve POST endpoints, id substitution and paging behavior', () => {
    const OriginalFormData = globalThis.FormData;
    globalThis.FormData = class {
        constructor(form) { this.values = new Map(form?.values ?? []); }
        append(key, value) { this.values.set(key, value); }
        get(key) { return this.values.get(key); }
    };
    try {
        let request;
        let reset = 0;
        const notifications = [];
        const form = {
            0: { values: [['id', 12], ['name', 'سجل']], reset: () => { reset++; }, querySelector: () => null },
            closest: () => ({ modal: () => {} }), validate: () => ({ resetForm: () => {} }),
        };
        window.ajax_exe = value => { request = value; };
        window.notifySuccess = value => notifications.push(value);
        window.store_url = '/items/store';
        window.update_url = '/items/#placeholder#/update';
        window.addSuccess = 'added';
        window.updateSuccess = 'updated';
        window.myDropzone = null;
        reloads = [];
        window.create_item({ preventDefault() {} }, form);
        assert.equal(request.url, '/items/store');
        assert.equal(request.type, 'post');
        request.success();
        window.update_item({ preventDefault() {} }, form);
        assert.equal(request.url, '/items/12/update');
        request.success();
        assert.deepEqual(reloads, [[], [null, false]]);
        assert.deepEqual(notifications, ['added', 'updated']);
        assert.equal(reset, 2);
        window.create_item({ preventDefault() {} }, form);
        form[0].isConnected = false;
        window.pageTable = { ajax: { reload() { assert.fail('must not reload another tab after an old POST'); } } };
        window.myDropzone = { removeAllFiles() { assert.fail('must not reset new tab files'); } };
        request.success();
        assert.equal(reset, 2);
        window.pageTable = table;
        window.myDropzone = null;
    } finally { globalThis.FormData = OriginalFormData; }
});

test('required Dropzone checks the current form and appends only queued files with configured name', () => {
    const OriginalFormData = globalThis.FormData;
    globalThis.FormData = class { constructor() { this.files = []; } append(...args) { this.files.push(args); } };
    try {
        let highlighted = false;
        const element = { dataset: { required: 'true' }, classList: { add: () => { highlighted = true; } } };
        const form = { 0: { querySelector: () => element, contains: target => target === element } };
        window.myDropzone = null;
        assert.equal(collectFormData(form), null);
        assert.equal(highlighted, true);
        const queued = { status: 'queued', name: 'one.png' };
        window.myDropzone = { element, getAcceptedFiles: () => [queued], files: [queued, { status: 'success', name: 'old.png' }], options: { paramName: 'pictures[]' } };
        assert.deepEqual(collectFormData(form).files, [['pictures[]', queued, 'one.png']]);
        window.myDropzone.element = {};
        assert.equal(collectFormData(form), null, 'another modal cannot satisfy this form required upload');
    } finally { globalThis.FormData = OriginalFormData; }
});
