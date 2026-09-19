import test from 'node:test';
import assert from 'node:assert/strict';

class Element {
    constructor(tag = 'div') { this.tagName = tag; this.children = []; this.textContent = ''; this.htmlAssignments = []; this.attributes = {}; if (tag === 'template') this.content = new Element('fragment'); }
    querySelectorAll() { return []; }
    contains(node) { return this.children.includes(node); }
    setAttribute(key, value) { this.attributes[key] = value; }
    append(...nodes) { this.children.push(...nodes); }
    replaceChildren(...nodes) { this.children = nodes; }
    set innerHTML(value) { this.htmlAssignments.push(value); if (this.content) this.content.html = value; }
}

const nodes = new Map();
const domReady = [];
const jqueryReady = [];
const readiness = [];
globalThis.document = {
    readyState: 'loading',
    querySelector: selector => nodes.get(selector) ?? null,
    querySelectorAll: () => [], getElementById: id => nodes.get(`#${id}`) ?? null,
    createElement: tag => new Element(tag),
    addEventListener: (event, callback, options) => { domReady.push({ event, callback, options }); },
};
globalThis.CustomEvent = class { constructor(type) { this.type = type; } };
globalThis.window = { dispatchEvent: event => readiness.push(event.type) };
globalThis.$ = () => ({ length: 0, ready: callback => jqueryReady.push(callback), attr: () => 'fake-csrf', off() { return this; }, on() { return this; }, find() { return this; }, each() { return this; } });
$.ajaxSetup = () => {};

await import('../../resources/js/back/page-table.js');

test('page entry statically loads table dependencies and waits for the server JSON node at DOM readiness', () => {
    assert.equal(typeof window.prepareDataTable, 'function');
    assert.equal(typeof window.setUpDatatable, 'function');
    assert.equal(typeof window.initializeDataTable, 'function');
    assert.equal(typeof window.ajax_exe, 'function');
    assert.ok(readiness.includes('dashboard:table-api-ready'));
    assert.equal(domReady.length, 1);
    assert.deepEqual(domReady[0].options, { once: true });
    const definition = { title: '</script><img src=x>', show_list: [{ key: 'name' }] };
    nodes.set('#dashboard-table-definition', { textContent: JSON.stringify(definition) });
    const calls = [];
    window.prepareDataTable = data => calls.push(['prepare', data]);
    window.setUpDatatable = () => calls.push(['setup']);
    domReady[0].callback();
    assert.deepEqual(calls, [['prepare', definition], ['setup']]);
    assert.ok(jqueryReady.length, 'dependency ready handlers remain queued separately');
});

test('page entry starts immediately when imported after DOMContentLoaded', async () => {
    document.readyState = 'complete';
    nodes.set('#dashboard-table-definition', { textContent: '{"title":"late"}' });
    const calls = [];
    window.prepareDataTable = data => calls.push(data.title);
    window.setUpDatatable = () => calls.push('setup');
    await import('../../resources/js/back/page-table.js?late-import');
    assert.deepEqual(calls, ['late', 'setup']);
});

test('item headers insert column titles as text and exactly match optional system columns', async () => {
    document.readyState = 'loading';
    nodes.set('#table_id thead', new Element('thead'));
    await import('../../resources/js/back/item-table.js');
    const payload = '<img src=x onerror=alert(1)>';
    window.actionsText = '<script>actions</script>';
    for (const checkbox of [false, true]) for (const actions of [false, true]) {
        window.generateTableHeaders({ have_check_box: checkbox, have_actions: actions, show_list: [{ title: payload }, { title: 'اسم' }] });
        const row = nodes.get('#table_id thead').children[0];
        assert.equal(row.children.length, 3 + Number(checkbox) + Number(actions));
        const firstDataCell = row.children[1 + Number(checkbox)];
        assert.equal(firstDataCell.textContent, payload);
        assert.deepEqual(firstDataCell.htmlAssignments, []);
        assert.ok(row.children.flatMap(cell => cell.htmlAssignments).every(html => !html.includes(payload)));
        if (actions) assert.equal(row.children.at(-1).textContent, window.actionsText);
    }
});

test('item tab loader aborts stale requests and only commits the latest successful response', async () => {
    const requests = [];
    const calls = [];
    globalThis.fetch = (url, options) => new Promise(resolve => requests.push({ url, options, resolve }));
    window.destroyDataTable = () => calls.push('destroy');
    window.prepareDataTable = data => calls.push(data.title);
    window.setUpDatatable = () => calls.push('setup');
    nodes.set('#dashboard-item-table-modals', new Element());
    nodes.set('#dashboard-item-table-region', new Element());
    nodes.set('#dashboard-item-table-status', new Element());
    const older = window.loadTableData('/old');
    const newer = window.loadTableData('/new');
    assert.equal(requests[0].options.signal.aborted, true);
    assert.equal(requests[1].options.signal.aborted, false);
    assert.equal(requests[1].options.headers.Accept, 'application/json');
    requests[1].resolve({ ok: true, json: async () => ({ status: 'Success', data: { title: 'newest', show_list: [{ title: 'new' }] }, fragments: { modals: '<form id="new_form"></form>' } }) });
    await newer;
    requests[0].resolve({ ok: true, json: async () => ({ status: 'Success', data: { title: 'stale', show_list: [{ title: 'old' }] }, fragments: { modals: '<form id="old_form"></form>' } }) });
    await older;
    assert.deepEqual(calls, ['destroy', 'newest', 'setup']);
    assert.equal(nodes.get('#table_id thead').children[0].children[1].textContent, 'new');
    assert.equal(nodes.get('#dashboard-item-table-modals').children[0].html, '<form id="new_form"></form>');
    assert.equal(nodes.get('#dashboard-item-table-region').inert, false);
    assert.equal(nodes.get('#dashboard-item-table-status').hidden, true);
});

test('an unsuccessful item request leaves the current table intact and reports the HTTP error', async () => {
    const calls = [];
    const originalError = console.error;
    console.error = () => {};
    try {
        window.destroyDataTable = () => calls.push('destroy');
        window.showNotification = (...args) => calls.push(args);
        globalThis.fetch = async () => ({ ok: false, status: 403 });
        await window.loadTableData('/forbidden');
        assert.equal(calls.length, 1);
        assert.equal(calls[0][0], 'error');
        assert.match(calls[0][1], /403/);
    } finally { console.error = originalError; }
});

test('item startup reads numeric internal filters and suppresses auto-start when there are no tabs', () => {
    nodes.set('#dashboard-item-table-definition', { textContent: '{"user_id":42,"active":false}' });
    window.auto_start = true;
    domReady.at(-1).callback();
    assert.deepEqual(window.internal_filter, { user_id: 42, active: false });
    assert.equal(window.auto_start, false);
});


test('an old endpoint without fragments cannot replace current fields or table configuration', async () => {
    const host = nodes.get('#dashboard-item-table-modals');
    const oldFragment = host.children[0];
    const errors = [];
    const originalError = console.error;
    console.error = () => {};
    try {
        window.destroyDataTable = () => assert.fail('must not destroy the current table');
        window.showNotification = (...args) => errors.push(args);
        globalThis.fetch = async () => ({ ok: true, json: async () => ({ status: 'Success', data: { show_list: [] } }) });
        assert.equal(await window.loadTableData('/outdated'), false);
        assert.equal(host.children[0], oldFragment);
        assert.equal(errors.length, 1);
        assert.match(errors[0][1], /النماذج/);
        assert.equal(nodes.get('#dashboard-item-table-region').inert, false);
    } finally { console.error = originalError; }
});

test('read-only tabs replace previous CRUD forms with the empty server fragment', async () => {
    window.destroyDataTable = () => {};
    window.prepareDataTable = () => {};
    window.setUpDatatable = () => {};
    globalThis.fetch = async () => ({ ok: true, json: async () => ({ status: 'Success', data: { show_list: [], have_add: false }, fragments: { modals: '' } }) });
    assert.equal(await window.loadTableData('/read-only'), true);
    assert.equal(nodes.get('#dashboard-item-table-modals').children[0].html, '');
});
