import test from 'node:test';
import assert from 'node:assert/strict';

import { DashboardAssetLoader } from '../../resources/js/back/core/assets.js';
import { mountContent, registerCleanup, unmountContent } from '../../resources/js/back/core/lifecycle.js';

function fakeDocument() {
    const elements = [];
    let apiReady = false;
    const document = {
        baseURI: 'https://example.test/admin',
        head: {
            appendChild(element) {
                elements.push(element);
                queueMicrotask(() => {
                    apiReady = true;
                    element.listeners.load?.();
                });
            },
        },
        createElement(kind) {
            return {
                kind, dataset: {}, listeners: {},
                addEventListener(name, callback) { this.listeners[name] = callback; },
                getAttribute(name) { return this[name]; },
                remove() { elements.splice(elements.indexOf(this), 1); },
            };
        },
        querySelectorAll(selector) {
            return elements.filter(element => selector.startsWith(element.kind));
        },
    };
    return { document, elements, isReady: () => apiReady };
}

test('asset loader shares concurrent loads and does not append the same script twice', async () => {
    const fake = fakeDocument();
    const loader = new DashboardAssetLoader(fake.document);
    loader.register('maps', { scripts: ['/maps.js'], test: fake.isReady });
    const first = loader.load('maps');
    const second = loader.load('maps');
    assert.equal(first, second);
    await Promise.all([first, second]);
    assert.equal(fake.elements.length, 1);
    await loader.load('maps');
    assert.equal(fake.elements.length, 1);
});

test('content lifecycle dispatches both events and runs registered cleanup once', () => {
    const events = [];
    const root = { dispatchEvent(event) { events.push(event.type); } };
    let cleaned = 0;
    registerCleanup(root, () => { cleaned++; });
    mountContent(root);
    unmountContent(root);
    unmountContent(root);
    assert.deepEqual(events, ['dashboard:content-mounted', 'dashboard:content-unmounted', 'dashboard:content-unmounted']);
    assert.equal(cleaned, 1);
});

