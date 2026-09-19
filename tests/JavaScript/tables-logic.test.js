import test from 'node:test';
import assert from 'node:assert/strict';
import { buildTableConfiguration, getNestedValue } from '../../resources/js/back/tables/configuration.js';
import { collectValidationRules, initializeFormValidation } from '../../resources/js/back/tables/validation.js';
import { buildSelectQuery, mapSelectResults } from '../../resources/js/back/tables/select-options.js';
import { buildRequestFilters } from '../../resources/js/back/datatables/filter-values.js';
import { hasPermission, isActionVisible, buildColumnDefinitions, renderActions } from '../../resources/js/back/datatables/actions.js';
import { replaceUrlPlaceholderV2 } from '../../resources/js/back/datatables/urls.js';
import { buildColumns, renderColumn } from '../../resources/js/back/tables/columns.js';
import { escapeHtml, renderToggleText } from '../../resources/js/back/tables/text.js';
import { dataColumnIndexes } from '../../resources/js/back/tables/lifecycle.js';
import { formatExportCell, buildToolbarButtons } from '../../resources/js/back/datatables/toolbar.js';
import { toolbarLabel } from '../../resources/js/back/tables/lifecycle.js';

test('configuration intersects permissions, supports open, maps filters, and preserves route contract', () => {
    const data = {
        permissions: ['open', 'view_city', 'delete_city'], filters: [{ id: 'country' }],
        internal_filters: [{ key: 'city_id', value: 9 }], daterange_filter: true,
        store_url: '/admin/city/store', update_url: '/admin/city/#placeholder#/update', dropzone: true,
    };
    const state = buildTableConfiguration(data, ['view_city']);
    assert.deepEqual(state.user_permissions, ['open', 'view_city']);
    assert.deepEqual(state.external_filters, { country: '#filtercountry', created_at: '#datatable_daterange_filter' });
    assert.deepEqual(state.optioanl_filter, { city_id: 9 });
    assert.equal(state.store_url, data.store_url);
    assert.equal(state.update_url, data.update_url);
    assert.equal(state.datatable_id, '#table_id');
    assert.deepEqual(data.internal_filters, [{ key: 'city_id', value: 9 }]);
});

test('a fresh AJAX tab definition clears previous optional filters and uploader state', () => {
    const state = buildTableConfiguration({ internal_filters: [], filters: [] });
    assert.deepEqual(state.optioanl_filter, {});
    assert.equal(state.dropzone, false);
    for (const key of ['inputs', 'update_inputs', 'modals', 'show_list', 'datatable_actions']) assert.deepEqual(state[key], []);
});

test('validation keeps create, edit, and custom modal rules separate including multiform field names', () => {
    const calls = [];
    globalThis.$ = () => ({ length: 1 });
    globalThis.window = { defineFormValidation: (selector, rules) => calls.push([selector, rules]) };
    initializeFormValidation({
        inputs: [{ id: 'name', validation: { required: true } }],
        update_inputs: [{ id: 'name', validation: { minlength: 2 } }, { id: 'rows', input: 'multiform', inputs: [{ id: 'amount', validation: { min: 1 } }] }],
        modals: [{ form_id: '#approve_form', inputs: [{ id: 'reason', validation: { required: true } }] }],
    });
    assert.deepEqual(calls, [
        ['#add_form', { name: { required: true } }],
        ['#edit_form', { name: { minlength: 2 }, 'rows[0][amount]': { min: 1 } }],
        ['#approve_form', { reason: { required: true } }],
    ]);
    assert.deepEqual(collectValidationRules([{ id: 'note' }]), {});
});

test('Select2 request preserves parent key, child key, search, page and relation values', () => {
    const values = { city_id: 4, status: 0, tags: ['a', 'b'] };
    const result = buildSelectQuery({ parent_id: 'city_id', parent_key: 'city_id', child_key: 'area_id', relations: ['status', 'tags'] },
        { term: 'شرق', page: 3 }, id => values[id]);
    assert.deepEqual(result, { query: { search: 'شرق', page: 3, parent_id: 4, parent_key: 'city_id', child_key: 'area_id', status: 0, tags: ['a', 'b'] }, missing: [] });
});

test('Select2 required relations report missing values without leaking partial queries', () => {
    const item = { relations: { required_city: 'required', optional_city: 'optional' } };
    assert.deepEqual(buildSelectQuery(item, {}, () => ''), { query: {}, missing: ['required_city'] });
    assert.deepEqual(buildSelectQuery(item, {}, () => []), { query: {}, missing: ['required_city'] });
    assert.deepEqual(buildSelectQuery({ relations: { enabled: 'required' } }, {}, () => 0).missing, []);
});

test('Select2 response mapper preserves API pagination convention', () => {
    const params = {};
    assert.deepEqual(mapSelectResults({ data: [{ id: 10, text: 'اسم', ignored: true }] }, params), { results: [{ id: 10, text: 'اسم' }], pagination: { more: true } });
    assert.equal(params.page, 1);
    assert.equal(mapSelectResults({ data: [] }, {}).pagination.more, false);
});

test('request filters support DOM, variable sources, date pairs and optional override precedence', () => {
    const elements = {
        '#status': { exists: true, value: 'pending' }, '#missing': { exists: false },
        '#datatable_daterange_filter': { exists: true, value: '1/1 - 1/31', name: 'paid_at' },
    };
    const state = { customer: 7, optioanl_filter: { status: 'completed', tenant: 3 }, internal_filter: { ignored: 9 } };
    const filters = buildRequestFilters({ status: '#status', absent: '#missing', customer_id: 'var[customer]', missing_var: 'var[unknown]', created_at: '#datatable_daterange_filter' }, selector => elements[selector], state);
    assert.deepEqual(filters, { status: 'completed', customer_id: 7, missing_var: null, date_column: 'paid_at', date_range: '1/1 - 1/31', tenant: 3 });
    assert.deepEqual(state.optioanl_filter, { status: 'completed', tenant: 3 });
    assert.deepEqual(buildRequestFilters({}, () => {}, { optioanl_filter: {}, internal_filter: { id: 3 } }), { id: 3 });
});

test('action visibility requires a permitted alternative and every strict row condition', () => {
    assert.equal(hasPermission(['edit', 'view'], ['view']), true);
    const action = { permission: ['edit', 'view'], if: [{ key: 'status', value: 'new' }, { key: 'active', value: 1 }] };
    assert.equal(isActionVisible(action, { status: 'new', active: 1 }, ['view']), true);
    assert.equal(isActionVisible(action, { status: 'new', active: '1' }, ['view']), false);
    assert.equal(isActionVisible(action, { status: 'new', active: 1 }, []), false);
});

test('row actions preserve custom modal dispatch attributes and hide unauthorized actions', () => {
    const actions = [
        { type: 'custom_modal', permission: 'edit', link: '#wallet_modal', form_id: '#wallet_form', onclick: 'fill_form', name: 'Wallet', icon: 'icon' },
        { type: 'modal', permission: 'delete', link: '#delete_modal', name: 'Forbidden' },
    ];
    const html = renderActions(actions, null, 'display', { id: 17 }, ['edit']);
    assert.match(html, /data-form="#wallet_form"/);
    assert.match(html, /data-call_function="fill_form"/);
    assert.match(html, /data-id="17"/);
    assert.doesNotMatch(html, /Forbidden/);
});

test('column positions match tables with and without checkboxes or actions', () => {
    for (const checkbox of [true, false]) for (const actions of [true, false]) {
        const state = { have_check_box: checkbox, have_actions: actions, show_list: [{ key: 'name', type: 'text' }, { key: 'id', type: 'id' }] };
        assert.deepEqual(dataColumnIndexes(state), checkbox ? [2, 3] : [1, 2]);
        assert.equal(buildColumns(state).length, 3 + Number(checkbox) + Number(actions));
        const defs = buildColumnDefinitions([], state);
        assert.equal(defs.some(definition => definition.targets === 1), checkbox);
        assert.equal(defs.some(definition => definition.targets === -1), actions);
    }
});

test('nested column paths tolerate missing relations and retain numeric zero', () => {
    assert.equal(getNestedValue({ child: { value: 0 } }, 'child.value'), 0);
    assert.equal(getNestedValue({ child: null }, 'child.value'), undefined);
    assert.equal(getNestedValue({}, 'missing.child.value'), undefined);
});

test('text cells escape untrusted HTML while explicit rich-text cells retain their markup', () => {
    globalThis.window = { seeMoreText: 'المزيد' };
    assert.equal(renderColumn({ type: 'text' }, '<img onerror="alert(1)">', {}), '<span>&lt;img onerror=&quot;alert(1)&quot;&gt;</span>');
    assert.equal(escapeHtml("a&b<'\">"), 'a&amp;b&lt;&#39;&quot;&gt;');
    const html = renderToggleText('<'.repeat(110), 'desc');
    assert.match(html, /data-id="desc-/);
    assert.match(html, /&lt;/);
    assert.doesNotMatch(html, /<span[^>]*><{2}/);
    globalThis.$ = () => ({ html: () => ({ text: () => 'Hello' }) });
    assert.equal(renderColumn({ type: 'html_text' }, '<b>Hello</b>', {}), '<b>Hello</b>');
});

test('URL replacement keeps trailing route/query content and handles zero identifiers', () => {
    assert.equal(replaceUrlPlaceholderV2('/admin/items/#placeholder#/edit?tab=details', 0), '/admin/items/0/edit?tab=details');
    assert.equal(replaceUrlPlaceholderV2('/admin/items', 2), '/admin/items');
});

test('export formats share the same formatter and accept numeric or empty cells', () => {
    assert.equal(formatExportCell(0), 0);
    assert.equal(formatExportCell(35), 35);
    assert.equal(formatExportCell(null), '');
    assert.equal(formatExportCell('عادي'), 'عادي');
    const [collection] = buildToolbarButtons({ have_export: true });
    assert.deepEqual(collection.buttons.map(button => button.extend), ['print', 'csv', 'excel', 'copy']);
    assert.ok(collection.buttons.every(button => button.exportOptions.format.body === formatExportCell));
});

test('toolbar labels use DOM-node text instead of coercing nodes to object strings', () => {
    assert.equal(toolbarLabel({ textContent: 'Add city' }), 'Add city');
    assert.equal(toolbarLabel('Export'), 'Export');
    assert.equal(toolbarLabel(null), '');
});
