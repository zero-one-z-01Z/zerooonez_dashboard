import test from 'node:test';
import assert from 'node:assert/strict';
import { fetchCatalog, sendDefinition } from '../../resources/js/back/builder/api.js';
import { ACTION_PRESETS, CAPABILITIES, INPUTS, actionRowMetadata, blankField, emptyDefinition, languagePreviewFields, normalizeFilterData, normalizeImportedDefinition, resolveCatalogTranslation } from '../../resources/js/back/builder/editor.js';
import { EXAMPLE_NAMES, V2_EXAMPLES } from '../../resources/js/back/builder/examples.js';

test('v2 editor exposes all contracted inputs and a complete root contract', () => {
    assert.deepEqual(INPUTS, ['text', 'textarea', 'single', 'multi_select', 'switch', 'hidden', 'empty', 'image', 'file', 'upload_images', 'image_preview', 'html', 'boundary', 'map', 'multiform', 'permissions']);
    const definition = emptyDefinition();
    assert.equal(definition.schema_version, 2);
    assert.deepEqual(Object.keys(definition.capabilities), CAPABILITIES);
    assert.equal(definition.sidebar.mode, 'root');
    assert.deepEqual(definition.edit_fields, []);
});

test('specialized field defaults keep storage separate from labels and request names', () => {
    const media = blankField('upload_images');
    assert.equal(media.name, '');
    assert.equal(media.column, '');
    assert.equal(media.type, 'json');
    assert.equal(media.storage.strategy, 'scalar');
    const children = blankField('multiform');
    assert.equal(children.storage.strategy, 'children');
    assert.deepEqual(children.inputs, []);
    assert.deepEqual(blankField('permissions').allowed_groups, []);
    assert.equal(blankField('map').lat_field, '');
    assert.equal(blankField('map').lng_field, '');
    assert.deepEqual(blankField('text').validation, { required: false, min: '', max: '' });
});

test('v1 imports are upgraded to v2 without carrying unknown executable metadata', () => {
    const definition = normalizeImportedDefinition({ title: 'المنتجات', model: 'Product', resource: 'products', permission: 'product', create_model: true, fields: [{ name: 'active', label: 'مفعّل', type: 'boolean', required: true, malicious: '<script>' }] });
    assert.equal(definition.schema_version, 2);
    assert.equal(definition.controller, 'ProductController');
    assert.equal(definition.title_ar, 'المنتجات');
    assert.equal(definition.title_en, 'المنتجات');
    assert.equal(definition.fields[0].input, 'switch');
    assert.equal(definition.fields[0].validation.required, true);
    assert.equal('malicious' in definition.fields[0], false);
});

test('DOM-free acceptance examples are v2, isolated, and preserve product limits in MB', () => {
    assert.deepEqual(EXAMPLE_NAMES, ['simple', 'area', 'chain', 'features', 'email', 'product', 'actions']);
    for (const definition of Object.values(V2_EXAMPLES)) {
        assert.equal(definition.schema_version, 2);
        assert.equal(definition.create_model, true);
        assert.match(definition.resource, /^demo_/);
        assert.match(definition.title_key, /^demo_/);
    }
    assert.equal(V2_EXAMPLES.product.fields.find(field => field.input === 'upload_images').max_file_size, 2);
    assert.notEqual(V2_EXAMPLES.chain.fields[1].child_id, V2_EXAMPLES.chain.fields[1].name);
});

test('filter editor normalization retains static choices and complete remote relation settings', () => {
    const filter = normalizeFilterData({ name: 'status', target: '', input: 'single', html_type: 'text', type: 'text', operator: 'equals', audience: 'admin', label_key: '', label_ar: 'الحالة', label_en: 'Status', select2: true, route: 'admin.statuses.list', relation: { name: 'status', model: 'Status', foreign_key: 'status_id', owner_key: 'id', value_name: 'name', key_name: 'id' }, items: [{ value: 'ready', text_ar: 'جاهز', text_en: 'Ready', selected: true }] });
    assert.equal(filter.target, 'status');
    assert.equal(filter.label_key, 'status');
    assert.equal(filter.route, 'admin.statuses.list');
    assert.deepEqual(filter.relation, { name: 'status', model: 'Status', foreign_key: 'status_id', owner_key: 'id', value_name: 'name', key_name: 'id' });
    assert.equal(filter.items[0].text_en, 'Ready');
    assert.equal(filter.html_type, 'text');
});

test('builder shortcut data, catalog translation lookup, and bilingual preview expansion are deterministic', () => {
    assert.equal(ACTION_PRESETS.edit.link, '#edit_modal');
    assert.equal(ACTION_PRESETS.edit.form_id, '#edit_form');
    assert.equal(ACTION_PRESETS.edit.onclick, 'edit_item');
    assert.equal(ACTION_PRESETS.delete.onclick, 'delete_item');
    assert.equal(ACTION_PRESETS.show.pending, true);
    assert.deepEqual(actionRowMetadata({ dataset: { pending: 'true' } }), { pending: true });
    const catalog = { translations: { inputs: { ar: { name: 'الاسم' }, en: { name: 'Name' } }, admin: { ar: { orders: 'الطلبات' }, en: { orders: 'Orders' } } } };
    assert.deepEqual(resolveCatalogTranslation(catalog, 'name'), { target: { group: 'inputs', key: 'name', qualified: 'inputs.name' }, ar: 'الاسم', en: 'Name' });
    assert.deepEqual(resolveCatalogTranslation(catalog, 'admin.orders'), { target: { group: 'admin', key: 'orders', qualified: 'admin.orders' }, ar: 'الطلبات', en: 'Orders' });
    assert.deepEqual(resolveCatalogTranslation(catalog, 'buttons.edit').target, null);
    const expanded = languagePreviewFields({ name: 'demo_category_name', column: 'demo_category_name', label_key: 'demo_category_name', label_ar: 'اسم التصنيف', label_en: 'Category name', language: 'both' });
    assert.deepEqual(expanded.map(field => field.name), ['demo_category_name_ar', 'demo_category_name_en']);
    assert.deepEqual(expanded.map(field => field.label_key), ['demo_category_name_ar', 'demo_category_name_en']);
    assert.deepEqual(expanded.map(field => field.label_en), ['Category name (Arabic)', 'Category name (English)']);
    assert.equal(languagePreviewFields({ name: 'name_ar', language: 'both' })[0].name, 'name_ar');
});

test('builder API uses JSON, CSRF, catalog model query, and useful backend failures', async () => {
    globalThis.document = { querySelector: () => ({ content: 'csrf' }) };
    globalThis.window = { location: { origin: 'https://builder.test' } };
    const requests = [];
    globalThis.fetch = async (url, options) => { requests.push({ url, options }); return { ok: true, json: async () => ({ models: ['Product'] }) }; };
    await sendDefinition('/preview', { schema_version: 2 });
    await fetchCatalog('/catalog', 'Product');
    assert.equal(requests[0].options.headers['X-CSRF-TOKEN'], 'csrf');
    assert.deepEqual(JSON.parse(requests[0].options.body), { schema_version: 2 });
    assert.equal(new URL(requests[1].url).searchParams.get('model'), 'Product');
    globalThis.fetch = async () => ({ ok: false, status: 422, json: async () => ({ errors: { title_ar: ['Arabic title is required'] } }) });
    await assert.rejects(sendDefinition('/preview', {}), /Arabic title is required/);
});
