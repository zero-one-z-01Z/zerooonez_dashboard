import test from 'node:test';
import assert from 'node:assert/strict';
import { registerFormReady, registerSwitchReady, defineEvents, registerPermissionReady } from '../../resources/js/back/forms/events.js';
import { createFormDom } from './helpers/form-dom.js';

/** Adds a separate role form with its own select-all and permission checkboxes. */
function addPermissionForm(dom, mode, count = 2) {
    const form = dom.document.append(dom.element('form'));
    const toggle = form.append(dom.element('input', { id: mode === 'edit' ? 'selectAllEdit' : 'selectAll' }));
    const boxes = Array.from({ length: count }, () => form.append(dom.element('input', { class: mode === 'edit' ? 'permission-checkbox-edit' : 'permission-checkbox' })));
    return { form, toggle, boxes };
}

test('modal forms inserted after registration reset once, keep unrelated listeners, and do not reset other modals', () => {
    const dom = createFormDom();
    let externalHides = 0;
    dom.$(document).on('hide.bs.modal.otherFeature', '.modal', () => { externalHides++; });
    registerFormReady();
    registerFormReady();
    const modal = document.append(dom.element('div', { class: 'modal', 'reset-on-close': '' }));
    const first = modal.append(dom.element('form'));
    const second = modal.append(dom.element('form'));
    const untouchedModal = document.append(dom.element('div', { class: 'modal' }));
    const untouchedForm = untouchedModal.append(dom.element('form'));
    dom.dispatch(modal, 'hide.bs.modal');
    assert.equal(first.resetCalls, 1);
    assert.equal(second.resetCalls, 1);
    assert.equal(first.validationResets, 1);
    assert.equal(untouchedForm.resetCalls, 0);
    assert.equal(externalHides, 1);
    dom.dispatch(untouchedModal, 'hide.bs.modal');
    assert.equal(untouchedForm.resetCalls, 0, 'reset-on-close remains opt-in');
});

test('a nested modal hide does not clear forms belonging to an outer modal', () => {
    const dom = createFormDom();
    registerFormReady();
    const outer = document.append(dom.element('div', { class: 'modal', 'reset-on-close': '' }));
    const outerForm = outer.append(dom.element('form'));
    const inner = outer.append(dom.element('div', { class: 'modal', 'reset-on-close': '' }));
    const innerForm = inner.append(dom.element('form'));
    dom.dispatch(inner, 'hide.bs.modal');
    assert.equal(innerForm.resetCalls, 1);
    assert.equal(outerForm.resetCalls, 0);
});

test('late custom switches dispatch one payload after repeated registration and leave ordinary switches alone', () => {
    const dom = createFormDom();
    const calls = [];
    let unrelatedChanges = 0;
    window.update_switch_val = (url, data) => calls.push({ url, data });
    dom.$(document).on('change.otherFeature', '.switch-input.custom', () => { unrelatedChanges++; });
    registerSwitchReady();
    registerSwitchReady();
    const lateSwitch = document.append(dom.element('input', { class: 'switch-input custom', 'data-id': 9, 'data-link': '/update/custom' }));
    lateSwitch.checked = true;
    assert.equal(dom.dispatch(lateSwitch, 'change').defaultPrevented, true);
    assert.deepEqual(calls, [{ url: '/update/custom', data: { id: 9, value: 1 } }]);
    assert.equal(unrelatedChanges, 1);
    const normal = document.append(dom.element('input', { class: 'switch-input' }));
    dom.dispatch(normal, 'change');
    assert.equal(calls.length, 1);
    lateSwitch.remove();
    const replacement = document.append(dom.element('input', { class: 'switch-input custom', 'data-id': 10, 'data-link': '/update/new' }));
    dom.dispatch(replacement, 'change');
    assert.deepEqual(calls[1], { url: '/update/new', data: { id: 10, value: 0 } });
});

test('declarative actions on forms added later run once and preserve third-party event listeners', () => {
    const dom = createFormDom();
    let actionCalls = 0;
    let thirdPartyCalls = 0;
    dom.$(document).on('submit.validation', '[data-event_on="submit"]', () => { thirdPartyCalls++; });
    defineEvents();
    defineEvents();
    const form = document.append(dom.element('form', { 'data-event_on': 'submit', 'data-call_function': 'saveLateForm' }));
    window.saveLateForm = (event, $form) => { actionCalls++; event.preventDefault(); assert.equal($form[0], form); };
    dom.dispatch(form, 'submit');
    assert.equal(actionCalls, 1);
    assert.equal(thirdPartyCalls, 1);
});

test('permission select-all and individual changes stay in the same late-added form, even with repeated toggle IDs', () => {
    const dom = createFormDom();
    registerPermissionReady();
    registerPermissionReady();
    const createA = addPermissionForm(dom, 'create');
    const createB = addPermissionForm(dom, 'create');
    const edit = addPermissionForm(dom, 'edit');
    createA.toggle.checked = true;
    dom.dispatch(createA.toggle, 'change');
    assert.ok(createA.boxes.every(box => box.checked));
    assert.ok(createB.boxes.every(box => !box.checked));
    assert.ok(edit.boxes.every(box => !box.checked));
    createA.boxes[0].checked = false;
    dom.dispatch(createA.boxes[0], 'change');
    assert.equal(createA.toggle.checked, false);
    edit.boxes.forEach(box => { box.checked = true; });
    dom.dispatch(edit.boxes[0], 'change');
    assert.equal(edit.toggle.checked, true);
    assert.equal(createB.toggle.checked, false);
    createB.boxes.forEach(box => { box.checked = true; });
    dom.dispatch(createB.boxes[0], 'change');
    assert.equal(createB.toggle.checked, true);
    assert.equal(createA.toggle.checked, false);
});
