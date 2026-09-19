import test from 'node:test';
import assert from 'node:assert/strict';
import { setTimeout as delay } from 'node:timers/promises';
import { ajax_exe } from '../../resources/js/back/forms/ajax.js';
import { handleValidationErrors } from '../../resources/js/back/forms/validation-errors.js';
import { setFormData, resetTheForm } from '../../resources/js/back/forms/population.js';
import { restrictInputToLanguage } from '../../resources/js/back/forms/language-input.js';
import { formatDateAgo } from '../../resources/js/back/forms/formatting.js';
import { replaceUrlPlaceholder_v2, isValidUrl } from '../../resources/js/back/forms/urls.js';
import { registerValidationRules } from '../../resources/js/back/forms/validation-rules.js';

// These are the public names invoked by existing Blade scripts and data-call_function.
const publicNames = [
    'ajax_exe', 'update_switch_val', 'customFunction', 'updatePassword', 'showToast',
    'defineFormValidation', 'resetTheForm', 'populateFormFromAjax', 'setFormData',
    'replaceUrlPlaceholder', 'replaceUrlPlaceholder_v2', 'populateIdField', 'populateForm',
    'togglePageLoader', 'updateSelect2Options', 'validateSummerNotes', 'direction',
    'initializeSelect2', 'restrictInputToLanguageJQuery', 'restrictInputToLanguageInForm',
    'formatDateAgo', 'resetMultiForm', 'addItemMultiForm', 'removeItemMultiForm'
];

/** Creates a small observable DOM adapter for form contracts, without loading browser plugins. */
function createFormFixture() {
    const values = new Map();
    const errors = [];
    const animations = [];
    const events = [];
    let resets = 0;
    const validator = {
        resetForm() { resets++; },
        showErrors(value) { errors.push(value); }
    };
    const missing = { length: 0, first() { return this; } };
    const fields = new Map();
    for (const name of ['id', 'name_ar', 'name_en', 'active', 'category_id', 'rooms[0][name]']) {
        fields.set(name, {
            length: 1,
            is(type) { return type === (name === 'active' ? ':checkbox' : name === 'category_id' ? 'select' : 'input'); },
            hasClass() { return false; },
            val(value) { if (arguments.length) values.set(name, value); return this; },
            prop(property, value) { values.set(`${name}.${property}`, value); return this; },
            trigger(event) { events.push([name, event]); return this; },
            closest() { return missing; },
            offset() { return { top: 100 }; },
            outerHeight() { return 20; }
        });
    }
    const form = {
        length: 1,
        0: { reset() { values.clear(); } },
        find(selector) {
            const match = /^\[name="(.+)"\]$/.exec(selector);
            return match ? fields.get(match[1]) ?? missing : missing;
        },
        validate() { return validator; }
    };
    const $ = (selector) => {
        if (selector === '#edit_form') return form;
        if (selector === globalThis.window) return { height: () => 800 };
        if (selector === 'html, body') return { animate: (...args) => animations.push(args) };
        throw new Error(`Unexpected jQuery selection: ${String(selector)}`);
    };
    $.each = (items, callback) => Object.entries(items).forEach(([key, value]) => callback(key, value));
    $.validator = {};
    return { $, values, errors, animations, events, get resets() { return resets; } };
}

test('crud entry publishes the full Blade API before any ready callback and delegates declarative events', async () => {
    const handlers = [];
    const ready = [];
    const setup = [];
    globalThis.window = globalThis;
    globalThis.document = {};
    const $ = (selector) => ({
        length: 0,
        ready(callback) { ready.push(callback); },
        off() { return this; },
        on(event, selector, callback) { handlers.push({ event, selector, callback }); return this; },
        attr() { return 'csrf-example'; },
        data(key) { return selector[key]; }
    });
    $.ajaxSetup = value => setup.push(value);
    globalThis.$ = $;
    await import('../../resources/js/back/crud.js');
    for (const name of publicNames) assert.equal(typeof window[name], 'function', name);
    assert.equal(ready.length, 1);
    assert.deepEqual(setup, [{ headers: { 'X-CSRF-TOKEN': 'csrf-example' } }]);
    assert.deepEqual(handlers.filter(handler => handler.selector.startsWith('[data-event_on=')).map(handler => handler.event.split('.')[0]), ['click', 'keyup', 'submit', 'change']);
    let delivered;
    window.testDeclaredAction = (event, element) => { delivered = { event, element }; };
    const event = { type: 'submit' };
    handlers.find(handler => handler.event.startsWith('submit.')).callback.call({ call_function: 'testDeclaredAction' }, event);
    assert.equal(delivered.event, event);
    assert.equal(typeof delivered.element.data, 'function');
    delete window.testDeclaredAction;
});

test('AJAX sends FormData unchanged with deferred execution and honors explicit callbacks/options', async () => {
    const sent = [];
    globalThis.$ = { ajax: settings => sent.push(settings) };
    const payload = new FormData();
    payload.append('name_ar', 'منتج');
    const success = () => {};
    const error = () => {};
    ajax_exe({ url: '/admin/items', type: 'POST', data: payload, success, error, showLoading: true });
    assert.equal(sent.length, 0);
    await delay(75);
    assert.equal(sent.length, 1);
    assert.equal(sent[0].data, payload);
    assert.equal(sent[0].success, success);
    assert.equal(sent[0].error, error);
    assert.equal(sent[0].processData, false);
    assert.equal(sent[0].contentType, false);
    assert.equal(typeof sent[0].beforeSend, 'function');
    assert.equal(typeof sent[0].complete, 'function');
    ajax_exe({ url: '/admin/items', type: 'POST', data: {}, processData: true, contentType: 'application/json' });
    await delay(75);
    assert.equal(sent[1].processData, true);
    assert.equal(sent[1].contentType, 'application/json');
});

test('Laravel validation errors target bracketed repeated-row names and reveal the first field', () => {
    const fixture = createFormFixture();
    globalThis.$ = fixture.$;
    handleValidationErrors({ 'rooms.0.name': ['اسم الغرفة مطلوب', 'رسالة ثانية'] }, '#edit_form');
    assert.equal(fixture.resets, 1);
    assert.deepEqual(fixture.errors, [{ 'rooms[0][name]': 'اسم الغرفة مطلوب' }]);
    assert.equal(fixture.animations.length, 1);
    assert.equal(fixture.animations[0][0].scrollTop, 0);
});

test('population expands translations, checks explicit booleans and triggers plain select changes', () => {
    const fixture = createFormFixture();
    globalThis.$ = fixture.$;
    setFormData({ id: 7, name: { ar: 'كرسي', en: 'Chair' }, active: '1', category_id: 12 }, '#edit_form');
    assert.equal(fixture.values.get('id'), 7);
    assert.equal(fixture.values.get('name_ar'), 'كرسي');
    assert.equal(fixture.values.get('name_en'), 'Chair');
    assert.equal(fixture.values.get('active.checked'), true);
    assert.equal(fixture.values.get('category_id'), 12);
    assert.deepEqual(fixture.events, [['category_id', 'change']]);
    setFormData({ active: '0' }, '#edit_form');
    assert.equal(fixture.values.get('active.checked'), false);
    resetTheForm('#edit_form');
    assert.equal(fixture.resets, 1);
    assert.equal(fixture.values.size, 0);
});

test('language input allows the requested alphabet and editing keys while rejecting unsupported characters', () => {
    const handlers = new Map();
    const attrs = new Map();
    const element = { addEventListener: (name, fn) => handlers.set(name, fn), setAttribute: (key, value) => attrs.set(key, value) };
    restrictInputToLanguage(element, 'ar');
    assert.equal(attrs.get('data-language-restriction'), 'ar');
    const sendKey = charCode => {
        let prevented = false;
        handlers.get('keypress')({ which: charCode, preventDefault() { prevented = true; } });
        return prevented;
    };
    assert.equal(sendKey('س'.charCodeAt(0)), false);
    assert.equal(sendKey(8), false);
    assert.equal(sendKey(32), false);
    assert.equal(sendKey('A'.charCodeAt(0)), true);
    assert.throws(() => restrictInputToLanguage(element, 'fr'), /Unsupported language/);
});

test('validation rules preserve comparison boundaries and optional predecessor dates', () => {
    const methods = new Map();
    globalThis.currentLocale = 'ar';
    const $ = () => ({ find: () => ({ val: () => '5' }) });
    $.validator = { addMethod: (name, fn) => methods.set(name, fn), messages: {}, format: text => text };
    $.extend = Object.assign;
    globalThis.$ = $;
    registerValidationRules();
    assert.equal(methods.get('greaterThan').call({ currentForm: {} }, '5', {}, 'from'), false);
    assert.equal(methods.get('greaterThanOrEqual').call({ currentForm: {} }, '5', {}, 'from'), true);
    assert.equal(methods.get('maxFileCount')('', { files: [{}, {}] }, 1), false);
    assert.equal(methods.get('filesize').call({ optional: () => false }, '', { files: [{ size: 1048576 }] }, 1), true);
    globalThis.currentLocale = 'en';
    methods.clear();
    registerValidationRules();
    assert.equal(methods.size, 0, 'The existing Arabic-only registration boundary must remain explicit');
});

test('formatters preserve URL placeholders, origin checks and local date formatting', () => {
    globalThis.window = { location: { origin: 'https://example.test' } };
    assert.equal(replaceUrlPlaceholder_v2('/admin/products/#placeholder#/edit', 12), '/admin/products/12/edit');
    assert.equal(isValidUrl('/admin/products'), true);
    assert.equal(isValidUrl('https://unrelated.test/admin'), false);
    assert.equal(formatDateAgo('2026-09-08T00:05:00'), '2026 Sep 8 12:05 AM');
    assert.equal(formatDateAgo('2026-09-08T13:09:00'), '2026 Sep 8 1:09 PM');
});
