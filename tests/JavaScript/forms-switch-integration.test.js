import test from 'node:test';
import assert from 'node:assert/strict';
import { setTimeout as delay } from 'node:timers/promises';
import { update_switch_val } from '../../resources/js/back/forms/submission.js';
import { registerSwitchReady } from '../../resources/js/back/forms/events.js';
import { initializeBookingRange } from '../../resources/js/back/forms/date-range.js';
import { setUpDatatable } from '../../resources/js/back/tables/lifecycle.js';

test('custom switch POST serializes the explicit payload without a global event or temporary form', async () => {
    globalThis.window = globalThis;
    delete globalThis.event;
    delete globalThis.$obj;
    const requests = [];
    const successMessages = [];
    const errorMessages = [];
    globalThis.$ = { ajax: settings => requests.push(settings) };
    globalThis.notifySuccess = message => successMessages.push(message);
    globalThis.notifyError = message => errorMessages.push(message);
    globalThis.updateSuccess = 'تم التعديل';
    const identifier = 'row-7\" data-extra="unchanged';
    update_switch_val('/admin/settings/toggle', { value: 0, id: identifier });
    await delay(75);
    assert.equal(requests.length, 1);
    assert.equal(requests[0].url, '/admin/settings/toggle');
    assert.equal(requests[0].type, 'post');
    assert.deepEqual([...requests[0].data.entries()], [['value', '0'], ['id', identifier]]);
    assert.equal(requests[0].processData, false);
    assert.equal(requests[0].contentType, false);
    requests[0].success({});
    assert.deepEqual(successMessages, ['تم التعديل']);
    const originalError = console.error;
    console.error = () => {};
    try { requests[0].error({ responseJSON: { message: 'رفض الخادم' } }); }
    finally { console.error = originalError; }
    assert.deepEqual(errorMessages, ['رفض الخادم']);
    assert.equal('$obj' in globalThis, false);
});

test('custom switch bubbling reaches both listeners but produces exactly one payload; normal table switch uses its own handler', () => {
    const ready = [];
    const handlers = {};
    const calls = [];
    globalThis.document = {};
    const chain = { length: 0, off() { return this; }, on() { return this; }, text() { return this; } };
    globalThis.$ = selector => {
        if (selector === document) return {
            ready: callback => ready.push(callback), off() { return this; },
            on(event, target, callback) {
                if (event === 'change.dashboardSwitch') handlers.table = callback;
                if (event === 'change.dashboardCustomSwitch') handlers.custom = callback;
                return this;
            },
        };
        if (selector === '.switch-input.custom') return {
            off() { return this; }, on(event, callback) { handlers.custom = callback; return this; },
        };
        if (selector === '#table_id') return { length: 1 };
        if (selector && typeof selector === 'object' && selector.dataset) return {
            is: query => query === ':checked' && selector.checked,
            data: key => selector.dataset[key],
            hasClass: name => selector.classes.includes(name),
            closest: () => ({ length: selector.inTable ? 1 : 0 }),
        };
        return chain;
    };
    $.fn = { dataTable: { isDataTable: () => false } };
    Object.assign(window, {
        datatable_id: '#table_id', datatable_url: '/rows', external_filters: {}, datatable_actions: [],
        show_list: [], filters: [], inputs: [], update_inputs: [], modals: [],
        initializeDataTable: () => ({}),
        update_switch_val: (url, data) => calls.push(['custom', url, data]),
        update_val: (url, data) => calls.push(['table', url, data]),
    });
    registerSwitchReady();
    setUpDatatable();
    let prevented = 0;
    const event = { preventDefault: () => { prevented++; } };
    const custom = { classes: ['switch-input', 'custom'], checked: false, inTable: true, dataset: { link: '/custom', id: 4 } };
    handlers.custom.call(custom, event);
    handlers.table.call(custom, event);
    assert.deepEqual(calls, [['custom', '/custom', { value: 0, id: 4 }]]);
    assert.equal(prevented, 1);
    assert.equal('$obj' in window, false);
    const regular = { classes: ['switch-input'], checked: true, inTable: true, dataset: { link: '/table', id: 8 } };
    handlers.table.call(regular, event);
    assert.deepEqual(calls[1], ['table', '/table', { value: 1, id: 8 }]);
    handlers.table.call({ ...regular, inTable: false }, event);
    assert.equal(calls.length, 2, 'unrelated form switches do not issue table requests');
});

test('booking date selection keeps its displayed dates and no longer opens a diagnostic alert', () => {
    let chooseRange;
    let displayed;
    globalThis.alert = () => { throw new Error('An ordinary date selection must not open a diagnostic alert'); };
    globalThis.moment = () => ({ subtract() { return this; }, add() { return this; }, startOf() { return this; }, endOf() { return this; } });
    globalThis.$ = selector => selector === '.bookingrange'
        ? { length: 1, daterangepicker: (options, callback) => { chooseRange = callback; } }
        : { html: value => { displayed = value; } };
    initializeBookingRange();
    chooseRange({ format: format => { assert.equal(format, 'M/D/YYYY'); return '1/2/2026'; } }, { format: () => '2/3/2026' });
    assert.equal(displayed, '1/2/2026 - 2/3/2026');
    delete globalThis.alert;
});
