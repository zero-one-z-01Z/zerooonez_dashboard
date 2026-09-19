import test from 'node:test';
import assert from 'node:assert/strict';
import { repeatedFieldName, reindexMultiForm } from '../../resources/js/back/forms/repeaters.js';
import { clearSelectDescendants, orderFieldsByDependencies } from '../../resources/js/back/forms/select2.js';

function repeatedControl(field, id, { multiple = false, tagName = 'INPUT' } = {}) {
    return { dataset: { field }, id, name: '', tagName, multiple };
}

function attributeNode(attributes) {
    return {
        hasAttribute: name => Object.hasOwn(attributes, name),
        getAttribute: name => attributes[name],
        setAttribute: (name, value) => { attributes[name] = value; },
        attributes,
    };
}

function repeatedGroup(controls, markers = [], extra = []) {
    return {
        dataset: {},
        querySelectorAll(selector) {
            if (selector === '[data-field]') return controls;
            if (selector === '[data-multi-select-presence]') return markers;
            if (selector === '*') return [...controls, ...markers, ...extra];
            if (selector.startsWith('label[')) return [];
            return [];
        },
    };
}

test('multiform reindex compacts deleted gaps and preserves array names without future duplicates', () => {
    const first = repeatedGroup([
        repeatedControl('id', 'editchildren_0_id'),
        repeatedControl('name', 'editchildren_0_name'),
        repeatedControl('tags', 'editchildren_0_tags', { multiple: true, tagName: 'SELECT' }),
    ], [{ dataset: { multiSelectPresence: 'tags' }, name: '' }]);
    const formerThird = repeatedGroup([
        repeatedControl('id', 'editchildren_2_id'),
        repeatedControl('name', 'editchildren_2_name'),
        repeatedControl('tags', 'editchildren_2_tags', { multiple: true, tagName: 'SELECT' }),
    ], [{ dataset: { multiSelectPresence: 'tags' }, name: '' }], [
        attributeNode({ id: 'edit_form_editchildren_2_description_editor', 'data-dashboard-editor': 'children[2][description]' }),
        attributeNode({ id: 'edit_form_editchildren_2_images_dropzone', 'data-upload-field': 'children[2][images]', 'data-param-name': 'children[2][images][]' }),
        attributeNode({ id: 'edit_form_editchildren_2_boundary', 'data-dashboard-boundary': 'children[2][boundary]' }),
        attributeNode({ id: 'edit_form_editchildren_2_map', 'data-dashboard-location-map': 'children[2][location]', 'data-lat-field': 'children[2][lat]', 'data-lng-field': 'children[2][lng]' }),
    ]);
    const container = {
        id: 'edit_children',
        dataset: { multiformName: 'children', idPrefix: 'editchildren_' },
        querySelectorAll: selector => selector === '[data-multiform-group]' ? [first, formerThird] : [],
    };

    reindexMultiForm(container);
    assert.deepEqual(formerThird.querySelectorAll('[data-field]').map(control => control.name), [
        'children[1][id]', 'children[1][name]', 'children[1][tags][]',
    ]);
    assert.equal(formerThird.querySelectorAll('[data-field]')[1].id, 'editchildren_1_name');
    assert.equal(formerThird.querySelectorAll('[data-multi-select-presence]')[0].name, 'children[1][tags__present]');
    const [editor, upload, boundary, map] = formerThird.querySelectorAll('*').slice(-4);
    assert.equal(editor.attributes.id, 'edit_form_editchildren_1_description_editor');
    assert.equal(editor.attributes['data-dashboard-editor'], 'children[1][description]');
    assert.equal(upload.attributes['data-upload-field'], 'children[1][images]');
    assert.equal(upload.attributes['data-param-name'], 'children[1][images][]');
    assert.equal(boundary.attributes.id, 'edit_form_editchildren_1_boundary');
    assert.equal(boundary.attributes['data-dashboard-boundary'], 'children[1][boundary]');
    assert.equal(map.attributes.id, 'edit_form_editchildren_1_map');
    assert.equal(map.attributes['data-dashboard-location-map'], 'children[1][location]');
    assert.equal(map.attributes['data-lat-field'], 'children[1][lat]');
    assert.equal(map.attributes['data-lng-field'], 'children[1][lng]');
    assert.equal(repeatedFieldName('children', 2, 'name'), 'children[2][name]');
});

test('dependency hydration is parent first even when the server returns descendants first', () => {
    const data = { area_id: 8, name: 'Branch', company_id: 2, city_id: 4 };
    const inputs = [
        { id: 'area_id', parent_id: 'city_id' },
        { id: 'city_id', parent_id: 'company_id' },
        { id: 'company_id' },
        { id: 'name' },
    ];
    const order = orderFieldsByDependencies(data, inputs);
    assert.ok(order.indexOf('company_id') < order.indexOf('city_id'));
    assert.ok(order.indexOf('city_id') < order.indexOf('area_id'));
    assert.deepEqual(new Set(order), new Set(Object.keys(data)));
});

test('clearing a parent clears and disables every descendant only inside the same multiform row', () => {
    const source = { dataset: { field: 'company_id' } };
    const city = {
        dataset: { field: 'city_id', parentId: 'company_id' }, value: '4', disabled: false, multiple: false,
        options: [], querySelectorAll: () => [],
    };
    const area = {
        dataset: { field: 'area_id', parentId: 'city_id' }, value: '8', disabled: false, multiple: false,
        options: [], querySelectorAll: () => [],
    };
    const otherRowCity = {
        dataset: { field: 'city_id', parentId: 'company_id' }, value: '99', disabled: false, multiple: false,
        options: [], querySelectorAll: () => [],
    };
    const scope = { querySelectorAll: selector => selector === '[data-parent-id]' ? [city, area] : [] };
    globalThis.$ = () => ({ trigger() { return this; } });

    assert.deepEqual(clearSelectDescendants(source, scope), [city, area]);
    assert.equal(city.value, ''); assert.equal(city.disabled, true);
    assert.equal(area.value, ''); assert.equal(area.disabled, true);
    assert.equal(otherRowCity.value, '99'); assert.equal(otherRowCity.disabled, false);
});
