import test from 'node:test';
import assert from 'node:assert/strict';
import { closeModal } from '../../resources/js/back/forms/modal-lifecycle.js';

/** Models Bootstrap's rule: hide is ignored until its opening transition ends. */
function openingModal() {
    const element = new EventTarget();
    let visible = true;
    let opening = true;
    let hideCalls = 0;
    element.classList = { contains: () => visible };
    element.getAttribute = () => visible ? 'true' : null;
    const instance = {
        _isShown: true,
        hide() {
            hideCalls++;
            if (opening) return;
            visible = false;
            this._isShown = false;
            element.dispatchEvent(new Event('hidden.bs.modal'));
        }
    };
    globalThis.window = { bootstrap: { Modal: { getInstance: () => instance } } };
    return { element, instance, calls: () => hideCalls, finishOpening() { opening = false; element.dispatchEvent(new Event('shown.bs.modal')); } };
}

test('save completed during opening closes after Bootstrap shown event and cleans its listeners', async () => {
    const modal = openingModal();
    const closed = closeModal(modal.element);
    assert.equal(modal.calls(), 1);
    modal.finishOpening();
    await closed;
    assert.equal(modal.calls(), 2);
    modal.element.dispatchEvent(new Event('shown.bs.modal'));
    assert.equal(modal.calls(), 2, 'removed listeners cannot hide a future use of the modal');
});

test('already closed or absent modals need no timeout or Bootstrap operation', async () => {
    const modal = openingModal();
    modal.finishOpening();
    modal.instance.hide();
    const before = modal.calls();
    await closeModal(modal.element);
    await closeModal(null);
    assert.equal(modal.calls(), before);
});


test('the opening backdrop is also awaited before the modal gets its visible DOM attributes', async () => {
    const modal = openingModal();
    modal.element.classList.contains = () => false;
    modal.element.getAttribute = () => null;
    let resolved = false;
    const closed = closeModal(modal.element).then(() => { resolved = true; });
    await Promise.resolve();
    assert.equal(resolved, false);
    modal.finishOpening();
    await closed;
    assert.equal(modal.calls(), 2);
});
