import test from 'node:test';
import assert from 'node:assert/strict';
import { populateField } from '../../resources/js/back/forms/fields.js';
import { setFormData, populateForm } from '../../resources/js/back/forms/population.js';

function formFixture(metadata) {
    const values = new Map();
    const previews = new Map();
    const fields = new Map();
    const absent = { length: 0, first() { return this; } };
    for (const key of ['id', 'image', 'avatar', 'logo', 'banner', 'lat', 'lng', 'permissions', 'name']) {
        fields.set(key, {
            length: 1,
            is: selector => selector === (key === 'permissions' ? ':checkbox' : 'input'),
            hasClass: () => false,
            val(value) { values.set(key, value); return this; },
            prop(property, value) { values.set(`${key}.${property}`, value); return this; },
        });
    }
    const form = {
        0: { getAttribute: name => name === 'data-dashboard-field-types' ? metadata : null },
        find(selector) {
            const preview = /^#preview(.+)$/.exec(selector);
            if (preview) return { length: 1, attr: (key, value) => previews.set(`${preview[1]}.${key}`, value) };
            const match = /^\[name="([^"]+)"\]$/.exec(selector);
            return match ? fields.get(match[1]) ?? absent : absent;
        },
    };
    globalThis.$ = selector => {
        assert.equal(selector, '#edit_form');
        return form;
    };
    $.each = (data, callback) => Object.entries(data).forEach(([key, value]) => callback(key, value));
    return { values, previews };
}

test('generated declared image/logo/banner text and lng/lat numbers populate ordinary inputs without invoking specialized tools', () => {
    const fixture = formFixture(JSON.stringify({ image: 'text', logo: 'text', banner: 'text', lng: 'integer', lat: 'integer' }));
    const payload = '<img src=x onerror=alert(1)>';
    const data = { image: payload, logo: 'logo-name', banner: 'banner-name', lng: 0, lat: 25 };
    setFormData(data, '#edit_form');
    assert.deepEqual(Object.fromEntries(fixture.values), data);
    assert.equal(fixture.previews.size, 0);
});

test('generated boolean permissions field follows checkbox population for both on and off values', () => {
    const fixture = formFixture('{"permissions":"boolean"}');
    populateField('#edit_form', 'permissions', true, { permissions: true });
    assert.equal(fixture.values.get('permissions.checked'), true);
    populateField('#edit_form', 'permissions', false, { permissions: false });
    assert.equal(fixture.values.get('permissions.checked'), false);
});

test('legacy image population retains preview routing when metadata is absent or declares a different field', () => {
    for (const metadata of [null, '{"logo":"text"}']) {
        const fixture = formFixture(metadata);
        populateField('#edit_form', 'image', '/saved-image.png', {});
        assert.equal(fixture.previews.get('image.src'), '/saved-image.png');
        assert.equal(fixture.values.has('image'), false);
    }
});

test('malformed or unexpected field metadata falls back to legacy behavior without throwing', () => {
    for (const metadata of ['{broken', 'null', 'true', '[]', '"image"', '{"image":null}', '{"image":{}}']) {
        const fixture = formFixture(metadata);
        assert.doesNotThrow(() => populateField('#edit_form', 'image', '/legacy.png', {}));
        assert.equal(fixture.previews.get('image.src'), '/legacy.png', metadata);
        assert.equal(fixture.values.has('image'), false);
    }
});

test('typed v2 metadata dispatches a non-legacy field name by input kind', () => {
    const fixture = formFixture('{"avatar":{"type":"text","input":"image"}}');
    populateField('#edit_form', 'avatar', '/avatar.png', { avatar: '/avatar.png' });
    assert.equal(fixture.previews.get('avatar.src'), '/avatar.png');
    assert.equal(fixture.values.has('avatar'), false);
});

test('populateForm uses supplied data.id including zero and only populates the identifier', () => {
    const fixture = formFixture(null);
    globalThis.id = 999;
    try {
        populateForm({ id: 0, name: 'not-part-of-id-helper' }, '#edit_form');
        assert.deepEqual(Object.fromEntries(fixture.values), { id: 0 });
        populateForm({ id: 18 }, '#edit_form');
        assert.equal(fixture.values.get('id'), 18);
    } finally { delete globalThis.id; }
});
