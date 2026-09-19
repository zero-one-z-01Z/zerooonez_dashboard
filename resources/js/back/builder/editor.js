/** محرر عقد Builder v2. لا يكتب PHP ولا يقبل مفاتيح غير معرّفة من الاستيراد. */

export const INPUTS = ['text', 'textarea', 'single', 'multi_select', 'switch', 'hidden', 'empty', 'image', 'file', 'upload_images', 'image_preview', 'html', 'boundary', 'map', 'multiform', 'permissions'];
export const CAPABILITIES = ['create', 'update', 'delete', 'delete_all', 'export', 'filters', 'show'];
const SPECIAL = new Set(['multi_select', 'image', 'file', 'upload_images', 'image_preview', 'html', 'boundary', 'map', 'multiform', 'permissions']);
const RELATION = new Set(['single', 'multi_select']);
let translationCatalog = null;

/** Presets are data only: the runtime still receives the normal, allow-listed action contract. */
export const ACTION_PRESETS = {
    edit: { name_key: 'buttons.edit', name_ar: 'تعديل', name_en: 'Edit', type: 'modal', link: '#edit_modal', form_id: '#edit_form', icon: 'icon-base ti tabler-edit', onclick: 'edit_item', operation: 'edit' },
    delete: { name_key: 'buttons.delete', name_ar: 'حذف', name_en: 'Delete', type: 'delete', icon: 'icon-base ti tabler-trash', value: 'id', onclick: 'delete_item', operation: 'delete' },
    show: { name_key: 'buttons.view', name_ar: 'عرض', name_en: 'View', type: 'link', icon: 'icon-base ti tabler-eye', value: 'id', operation: 'show', pending: true },
};

const suffixed = (value, suffix) => value && value.endsWith(`_${suffix}`) ? value : (value ? `${value}_${suffix}` : '');
const translationGroupAndKey = key => {
    const trimmed = String(key || '').trim();
    if (!trimmed) return null;
    const match = trimmed.match(/^(admin|inputs)\.([A-Za-z][A-Za-z0-9_]*)$/);
    if (match) return { group: match[1], key: match[2], qualified: `${match[1]}.${match[2]}` };
    if (trimmed.includes('.') || !/^[A-Za-z][A-Za-z0-9_]*$/.test(trimmed)) return null;
    return { group: 'inputs', key: trimmed, qualified: `inputs.${trimmed}` };
};

/** Resolves only one explicit namespace; bare keys intentionally mean inputs.key. */
export function resolveCatalogTranslation(catalog, key) {
    const target = translationGroupAndKey(key);
    if (!target || !catalog?.translations?.[target.group]) return { target, ar: '', en: '' };
    const translations = catalog.translations[target.group];
    return { target, ar: translations.ar?.[target.key] ?? '', en: translations.en?.[target.key] ?? '' };
}

export function languagePreviewFields(field) {
    if (field?.language !== 'both') return [field];
    const name = field.name || '';
    const column = field.column || name;
    const key = field.label_key || name;
    return [
        { ...field, name: suffixed(name, 'ar'), column: suffixed(column, 'ar'), label_key: suffixed(key, 'ar'), language: 'ar', label_ar: `${field.label_ar || 'الحقل'} (عربي)`, label_en: `${field.label_en || field.label_ar || 'Field'} (Arabic)` },
        { ...field, name: suffixed(name, 'en'), column: suffixed(column, 'en'), label_key: suffixed(key, 'en'), language: 'en', label_ar: `${field.label_ar || 'الحقل'} (إنجليزي)`, label_en: `${field.label_en || field.label_ar || 'Field'} (English)` },
    ];
}

export function emptyDefinition() {
    return { schema_version: 2, model: '', controller: '', resource: '', permission: '', title_key: '', title_ar: '', title_en: '', create_model: false, timestamps: true, page_type: 'table', capabilities: Object.fromEntries(CAPABILITIES.map(key => [key, key !== 'delete_all' && key !== 'export'])), fields: [], edit_mode: 'same', edit_fields: [], columns: [], filters: [], actions: [], modals: [], sidebar: { mode: 'root', group: '', group_ar: '', group_en: '', icon: '', order: 0 }, notes: '' };
}

export function blankField(input = 'text') {
    return { name: '', column: '', input, type: input === 'switch' ? 'boolean' : input === 'textarea' ? 'textarea' : SPECIAL.has(input) ? 'json' : 'text', html_type: 'text', label_key: '', label_ar: '', label_en: '', language: 'neutral', validation: { required: false, min: '', max: '' }, width: '12', default: '', read_only: false, in_table: false, filterable: false, items: [], select2: false, route: '', relation: { name: '', model: '', foreign_key: '', owner_key: 'id', value_name: 'name', key_name: 'id', table: '' }, storage: { strategy: input === 'multiform' ? 'children' : 'scalar', relation: '', disk: '', directory: '', deleted_key: '' }, max_files: '', max_file_size: '', accepted_files: '', min_rows: '', max_rows: '', inputs: [], read_path: '', parent_id: '', child_id: '', parent_key: '', child_key: '', lat_field: '', lng_field: '', allowed_groups: [] };
}

const bool = value => Boolean(value);
const value = control => control?.type === 'checkbox' ? control.checked : (control?.value ?? '').trim();
const pathGet = (object, path, fallback = '') => path.split('.').reduce((current, key) => current?.[key], object) ?? fallback;
function pathSet(object, path, next) { const parts = path.split('.'); const key = parts.pop(); const parent = parts.reduce((current, part) => current[part] ??= {}, object); parent[key] = next; }
function cloneTemplate(id) { return document.querySelector(id).content.firstElementChild.cloneNode(true); }
function controlsInScope(row, selector, scope = null) { return [...row.querySelectorAll(selector)].filter(control => !scope || control.closest('.field-card') === scope); }
function setControls(row, selector, source, scope = null) { controlsInScope(row, selector, scope).forEach(control => { const key = control.dataset[selector.slice(6, -1)]; if (key) { const next = pathGet(source, key); if (control.type === 'checkbox') control.checked = bool(next); else control.value = Array.isArray(next) ? next.join(', ') : next; } }); }
function readControls(row, selector, target, scope = null) { controlsInScope(row, selector, scope).forEach(control => { const key = control.dataset[selector.slice(6, -1)]; if (key) pathSet(target, key, value(control)); }); }
function optional(object, keys) { keys.forEach(key => { if (object[key] === '' || object[key] === false || object[key] == null) delete object[key]; }); return object; }

function translationStatus(row, selector) {
    const labelSelector = key => `${selector.slice(0, -1)}="${key}"]`;
    const keyControl = row.querySelector(labelSelector('label_key'));
    const arControl = row.querySelector(labelSelector('label_ar'));
    const enControl = row.querySelector(labelSelector('label_en'));
    const status = row.querySelector('[data-translation-status]');
    if (!keyControl || !arControl || !enControl || !status) return;
    const found = resolveCatalogTranslation(translationCatalog, keyControl.value);
    const parts = [];
    [['ar', arControl, 'العربية'], ['en', enControl, 'الإنجليزية']].forEach(([locale, control, label]) => {
        const catalogValue = found[locale];
        const auto = control.dataset.translationSource === 'catalog';
        if (catalogValue && (!control.value || auto)) {
            control.value = catalogValue;
            control.dataset.translationSource = 'catalog';
            parts.push(`${label}: مستخدمة من ترجمة موجودة (${found.target?.qualified})`);
        } else if (!control.value && found.target) {
            parts.push(`اكتب الترجمة ${label} لهذا المفتاح`);
        }
    });
    status.textContent = parts.join(' · ');
    status.hidden = !parts.length;
}

function wireTranslationLookup(row, selector, onChange) {
    const labelSelector = key => `${selector.slice(0, -1)}="${key}"]`;
    const keyControl = row.querySelector(labelSelector('label_key'));
    const arControl = row.querySelector(labelSelector('label_ar'));
    const enControl = row.querySelector(labelSelector('label_en'));
    if (!keyControl || !arControl || !enControl) return;
    const refresh = () => { translationStatus(row, selector); onChange(); };
    keyControl.addEventListener('input', refresh);
    [arControl, enControl].forEach(control => control.addEventListener('input', () => {
        delete control.dataset.translationSource;
        translationStatus(row, selector);
    }));
    translationStatus(row, selector);
}

/** Catalog is loaded on demand; refreshing never overwrites a value typed by the user. */
export function setTranslationCatalog(catalog, root = document) {
    translationCatalog = catalog || null;
    root.querySelectorAll('[data-translation-row]').forEach(row => translationStatus(row, row.dataset.translationRow));
}

function setInputOptions(select) { select.replaceChildren(); INPUTS.forEach(input => { const option = document.createElement('option'); option.value = input; option.textContent = input; select.append(option); }); }
function addTextControl(grid, key, label, placeholder = '') { const wrap = document.createElement('label'); wrap.textContent = label; const input = document.createElement('input'); input.dataset.field = key; input.dir = 'ltr'; input.placeholder = placeholder; wrap.append(input); grid.append(wrap); return input; }
function addSpecializedControls(row) { const grid = row.querySelector('.specialized-settings .form-grid'); const map = document.createElement('div'); map.className = 'map-coordinates'; map.style.display = 'contents'; addTextControl(map, 'lat_field', 'حقل خط العرض', 'latitude'); addTextControl(map, 'lng_field', 'حقل خط الطول', 'longitude'); grid.append(map); const permissions = document.createElement('label'); permissions.className = 'permission-groups'; permissions.textContent = 'المجموعات المسموح تعديلها'; const input = document.createElement('input'); input.dataset.field = 'allowed_groups'; input.dir = 'ltr'; input.placeholder = 'admins, managers'; permissions.append(input); grid.append(permissions); }
function addOrderControls(row, onChange) { const controls = document.createElement('div'); controls.className = 'order-controls'; [['up', '↑'], ['down', '↓']].forEach(([direction, text]) => { const button = document.createElement('button'); button.type = 'button'; button.className = 'text-button'; button.textContent = text; button.title = direction === 'up' ? 'Move up' : 'Move down'; button.addEventListener('click', () => { const sibling = direction === 'up' ? row.previousElementSibling : row.nextElementSibling; if (!sibling) return; if (direction === 'up') row.parentElement.insertBefore(row, sibling); else row.parentElement.insertBefore(sibling, row); onChange(); }); controls.append(button); }); const head = row.querySelector('.field-card-head'); if (head) head.append(controls); else row.prepend(controls); }
function refreshField(row) {
    const input = row.querySelector('[data-field="input"]').value;
    row.querySelector('.relation-settings').classList.toggle('is-hidden', !RELATION.has(input));
    row.querySelector('.item-settings').classList.toggle('is-hidden', input !== 'single' && input !== 'multi_select');
    row.querySelector('.specialized-settings').classList.toggle('is-hidden', !SPECIAL.has(input));
    row.querySelector('[data-children]').hidden = input !== 'multiform';
    row.querySelector('.map-coordinates').classList.toggle('is-hidden', input !== 'map');
    row.querySelector('.permission-groups').classList.toggle('is-hidden', input !== 'permissions');
    row.querySelector('[data-field-title]').textContent = input === 'empty' ? 'فاصل تنسيق (لا يُحفظ)' : (row.querySelector('[data-field="label_ar"]').value || input);
    const name = row.querySelector('[data-field="name"]');
    const column = row.querySelector('[data-field="column"]');
    name.closest('label').classList.toggle('is-hidden', input === 'empty');
    column.closest('label').classList.toggle('is-hidden', input === 'empty');
    const expansion = row.querySelector('[data-language-expansion]');
    if (expansion) {
        const language = row.querySelector('[data-field="language"]').value;
        const field = { name: name.value.trim(), column: column.value.trim(), label_key: row.querySelector('[data-field="label_key"]').value.trim(), label_ar: row.querySelector('[data-field="label_ar"]').value.trim(), label_en: row.querySelector('[data-field="label_en"]').value.trim(), language };
        const expanded = languagePreviewFields(field);
        expansion.hidden = language !== 'both';
        expansion.replaceChildren();
        if (language === 'both') expanded.forEach(next => {
            const item = document.createElement('span'); item.className = 'language-chip';
            item.textContent = `${next.name || 'name'} · ${next.column || 'column'} · ${next.label_key || 'inputs.key'}`;
            expansion.append(item);
        });
    }
}

export function addField(container, field = {}, onChange = () => {}) {
    const row = cloneTemplate('#field-template');
    const complete = { ...blankField(field.input || 'text'), ...field, validation: { ...blankField().validation, ...(field.validation || {}) }, relation: { ...blankField().relation, ...(field.relation || {}) }, storage: { ...blankField().storage, ...(field.storage || {}) } };
    setInputOptions(row.querySelector('[data-field="input"]')); addSpecializedControls(row); addOrderControls(row, onChange);
    setControls(row, '[data-field]', complete);
    wireTranslationLookup(row, '[data-field]', onChange);
    (complete.items || []).forEach(item => addItem(row.querySelector('[data-items]'), item, onChange));
    (complete.inputs || []).forEach(child => addChild(row.querySelector('[data-child-inputs]'), child, onChange));
    row.querySelector('[data-remove]').addEventListener('click', () => { row.remove(); onChange(); });
    row.querySelector('[data-add-item]').addEventListener('click', () => { addItem(row.querySelector('[data-items]'), {}, onChange); onChange(); });
    row.querySelector('[data-add-child]').addEventListener('click', () => { addChild(row.querySelector('[data-child-inputs]'), {}, onChange); onChange(); });
    row.addEventListener('input', () => { refreshField(row); onChange(); });
    row.addEventListener('change', event => { if (event.target.dataset.item === 'selected' && row.querySelector('[data-field="input"]').value === 'single' && event.target.checked) row.querySelectorAll('[data-item="selected"]').forEach(control => { if (control !== event.target) control.checked = false; }); refreshField(row); onChange(); });
    container.append(row); refreshField(row); return row;
}
function addItem(container, item = {}, onChange = () => {}) { const row = cloneTemplate('#item-template'); setControls(row, '[data-item]', item); row.querySelector('[data-remove-item]').addEventListener('click', () => { row.remove(); onChange(); }); container.append(row); return row; }
function addChild(container, field = {}, onChange = () => {}) { return addField(container, field, onChange); }
function pruneRelation(relation) { if (!relation || (!relation.name && !relation.model && !relation.foreign_key)) return null; Object.keys(relation).forEach(key => { if (relation[key] === '') delete relation[key]; }); return relation; }
function readField(row) { const field = blankField(row.querySelector('[data-field="input"]').value); readControls(row, '[data-field]', field, row); field.items = [...row.querySelectorAll('[data-items] .item-row')].filter(item => item.closest('.field-card') === row).map(item => { const next = {}; readControls(item, '[data-item]', next); return optional(next, ['selected']); }); field.inputs = [...row.querySelectorAll('[data-child-inputs] > .field-card')].map(readField);
    if (field.input === 'empty') return { input: 'empty', width: field.width };
    if (!RELATION.has(field.input)) delete field.relation; else { field.relation = pruneRelation(field.relation); if (field.relation === null) delete field.relation; }
    if (!['single', 'multi_select'].includes(field.input)) delete field.items;
    if (!SPECIAL.has(field.input)) ['storage', 'max_files', 'max_file_size', 'accepted_files', 'min_rows', 'max_rows', 'inputs'].forEach(key => delete field[key]);
    if (field.input !== 'multiform') delete field.inputs;
    if (field.input === 'permissions') field.allowed_groups = String(field.allowed_groups || '').split(',').map(group => group.trim()).filter(Boolean); else delete field.allowed_groups;
    if (field.input !== 'map') { delete field.lat_field; delete field.lng_field; }
    if (!field.validation.min) delete field.validation.min; if (!field.validation.max) delete field.validation.max; if (!field.validation.required) delete field.validation.required;
    return optional(field, ['column', 'html_type', 'label_key', 'label_ar', 'label_en', 'default', 'read_path', 'route', 'parent_id', 'child_id', 'parent_key', 'child_key']);
}

export function addColumn(container, column = {}, onChange = () => {}) { const row = cloneTemplate('#column-template'); addOrderControls(row, onChange); setControls(row, '[data-column]', column); wireTranslationLookup(row, '[data-column]', onChange); row.querySelector('[data-remove]').addEventListener('click', () => { row.remove(); onChange(); }); row.addEventListener('input', onChange); row.addEventListener('change', onChange); container.append(row); return row; }
function readColumn(row) { const next = {}; readControls(row, '[data-column]', next); return optional(next, ['searchable', 'sortable']); }
function refreshFilter(row) { row.querySelector('[data-filter-single]').classList.toggle('is-hidden', row.querySelector('[data-filter="input"]').value !== 'single'); }
function addFilterItem(container, item = {}, onChange = () => {}) { const row = cloneTemplate('#item-template'); setControls(row, '[data-item]', item); row.querySelector('[data-remove-item]').addEventListener('click', () => { row.remove(); onChange(); }); container.append(row); return row; }
export function addFilter(container, filter = {}, onChange = () => {}) { const row = cloneTemplate('#filter-template'); const complete = { name: '', target: '', input: 'text', html_type: 'text', type: 'text', operator: 'contains', audience: 'all', label_key: '', label_ar: '', label_en: '', select2: false, route: '', relation: { name: '', model: '', foreign_key: '', owner_key: 'id', value_name: 'name', key_name: 'id' }, items: [], ...filter, relation: { name: '', model: '', foreign_key: '', owner_key: 'id', value_name: 'name', key_name: 'id', ...(filter.relation || {}) } }; setControls(row, '[data-filter]', complete); (complete.items || []).forEach(item => addFilterItem(row.querySelector('[data-filter-items]'), item, onChange)); row.querySelector('[data-remove]').addEventListener('click', () => { row.remove(); onChange(); }); row.querySelector('[data-add-filter-item]').addEventListener('click', () => { addFilterItem(row.querySelector('[data-filter-items]'), {}, onChange); onChange(); }); row.addEventListener('input', onChange); row.addEventListener('change', () => { refreshFilter(row); onChange(); }); container.append(row); refreshFilter(row); return row; }
export function normalizeFilterData(next) { if (!next.items?.length) delete next.items; if (!next.select2) delete next.select2; if (!next.route) delete next.route; if (!next.relation?.name && !next.relation?.model) delete next.relation; if (!next.target) next.target = next.name; if (!next.label_key) next.label_key = next.name; return optional(next, ['html_type']); }
function readFilter(row) { const next = {}; readControls(row, '[data-filter]', next); next.items = [...row.querySelectorAll('[data-filter-items] .item-row')].map(item => { const option = {}; readControls(item, '[data-item]', option); return optional(option, ['selected']); }); return normalizeFilterData(next); }
export function addAction(container, action = {}, onChange = () => {}) { const row = cloneTemplate('#action-template'); const grid = row.querySelector('.form-grid'); const handler = document.createElement('label'); handler.textContent = 'المعالج'; const select = document.createElement('select'); select.dataset.action = 'onclick'; ['', 'edit_item', 'delete_item', 'fill_form'].forEach(value => { const option = document.createElement('option'); option.value = value; option.textContent = value || 'بدون معالج'; select.append(option); }); handler.append(select); setControls(row, '[data-action]', action); if (action.operation && Object.hasOwn(ACTION_PRESETS, action.operation)) row.dataset.preset = action.operation; if (action.pending === true) row.dataset.pending = 'true'; const pending = row.querySelector('[data-action-pending]'); if (pending && action.pending === true) pending.hidden = false; (action.if || []).forEach(condition => addCondition(row.querySelector('[data-conditions]'), condition, onChange)); row.querySelector('[data-remove]').addEventListener('click', () => { row.remove(); onChange(); }); row.querySelector('[data-add-condition]').addEventListener('click', () => { addCondition(row.querySelector('[data-conditions]'), {}, onChange); onChange(); }); row.addEventListener('input', onChange); row.addEventListener('change', onChange); container.append(row); return row; }

function actionMatchesPreset(row, preset) {
    const action = preset === 'custom' ? { name_key: 'custom_action', form_id: 'custom_form' } : ACTION_PRESETS[preset];
    return action && Object.entries(action).every(([key, expected]) => {
        const control = row.querySelector(`[data-action="${key}"]`);
        return !control || String(control.value) === String(expected);
    });
}

/** Adds one canonical shortcut only. Selecting it again returns the existing row. */
export function addActionPreset(actions, modals, preset, context = {}, onChange = () => {}) {
    const existing = [...actions.querySelectorAll(':scope > .action-card')].find(row => row.dataset.preset === preset || actionMatchesPreset(row, preset));
    if (existing) return { action: existing, modal: null, created: false };
    const permission = String(context.permission || '').trim();
    const resource = String(context.resource || '').trim();
    if (preset === 'custom') {
        const modalId = 'custom_modal';
        const formId = 'custom_form';
        const modal = [...modals.querySelectorAll(':scope > .action-card')].find(row => row.querySelector('[data-modal="id"]')?.value === modalId) || addModal(modals, { id: modalId, form_id: formId, title_key: 'custom_action', title_ar: 'إجراء مخصص', title_en: 'Custom action', route: '', method: 'POST', permission: '', operation: 'custom', inputs: [] }, onChange);
        const action = addAction(actions, { name_key: 'custom_action', name_ar: 'إجراء مخصص', name_en: 'Custom action', type: 'custom_modal', link: `#${modalId}`, form_id: formId, icon: 'icon-base ti tabler-bolt', onclick: 'fill_form', permission: '', operation: 'custom' }, onChange);
        action.dataset.preset = 'custom';
        onChange();
        return { action, modal, created: true };
    }
    if (!ACTION_PRESETS[preset]) return { action: null, modal: null, created: false };
    const action = { ...ACTION_PRESETS[preset] };
    if (preset === 'edit') action.permission = permission ? `update_${permission}` : '';
    if (preset === 'delete') { action.permission = permission ? `delete_${permission}` : ''; action.route = resource ? `admin.${resource}.delete` : ''; }
    if (preset === 'show') { action.permission = permission ? `view_${permission}` : ''; action.route = resource ? `admin.${resource}.show` : ''; }
    const row = addAction(actions, action, onChange);
    row.dataset.preset = preset;
    if (preset === 'show') row.querySelector('[data-action-pending]')?.removeAttribute('hidden');
    onChange();
    return { action: row, modal: null, created: true };
}
function addCondition(container, condition, onChange) { const row = cloneTemplate('#condition-template'); setControls(row, '[data-condition]', condition); row.querySelector('[data-remove-condition]').addEventListener('click', () => { row.remove(); onChange(); }); container.append(row); }
export function actionRowMetadata(row) { return row?.dataset?.pending === 'true' ? { pending: true } : {}; }
function readAction(row) { const next = {}; readControls(row, '[data-action]', next); Object.assign(next, actionRowMetadata(row)); next.if = [...row.querySelectorAll('[data-conditions] .compact-row')].map(row => { const item = {}; readControls(row, '[data-condition]', item); return item; }).filter(item => item.key); if (!next.if.length) delete next.if; return optional(next, ['route', 'link', 'form_id', 'icon', 'value', 'permission', 'operation', 'onclick', 'blank']); }
export function addModal(container, modal = {}, onChange = () => {}) { const row = cloneTemplate('#modal-template'); setControls(row, '[data-modal]', modal); (modal.inputs || []).forEach(field => addField(row.querySelector('[data-modal-inputs]'), field, onChange)); row.querySelector('[data-remove]').addEventListener('click', () => { row.remove(); onChange(); }); row.querySelector('[data-add-modal-input]').addEventListener('click', () => { addField(row.querySelector('[data-modal-inputs]'), {}, onChange); onChange(); }); row.addEventListener('input', onChange); row.addEventListener('change', onChange); container.append(row); return row; }
function readModal(row) { const next = {}; readControls(row, '[data-modal]', next); next.inputs = [...row.querySelectorAll('[data-modal-inputs] .field-card')].map(readField); return optional(next, ['permission', 'operation']); }

function readDefinitionControl(form, path) { return value(form.querySelector(`[data-definition="${path}"]`)); }
export function readDefinition(form) { const definition = emptyDefinition(); form.querySelectorAll('[data-definition]').forEach(control => pathSet(definition, control.dataset.definition, value(control))); definition.schema_version = 2; definition.capabilities = Object.fromEntries(CAPABILITIES.map(key => [key, value(form.querySelector(`[data-capability="${key}"]`))])); definition.fields = [...form.querySelectorAll('#fields > .field-card')].map(readField); definition.edit_fields = definition.edit_mode === 'custom' ? [...form.querySelectorAll('#edit-fields > .field-card')].map(readField) : []; definition.columns = [...form.querySelectorAll('#columns > .list-row')].map(readColumn); definition.filters = [...form.querySelectorAll('#filters > .filter-card')].map(readFilter); definition.actions = [...form.querySelectorAll('#actions > .action-card')].map(readAction); definition.modals = [...form.querySelectorAll('#modals > .action-card')].map(readModal); return definition; }

/** يحوّل تعريف v1 الضيق إلى v2 دون الاحتفاظ بمفاتيح غير مدعومة. */
export function normalizeImportedDefinition(source) { if (!source || typeof source !== 'object') throw new Error('ملف التعريف غير صالح.'); if (source.schema_version === 2) return source; const title = source.title || source.resource || ''; return { ...emptyDefinition(), schema_version: 2, model: source.model || '', controller: source.controller || `${source.model || 'Resource'}Controller`, resource: source.resource || '', permission: source.permission || '', title_key: source.title_key || source.resource || '', title_ar: source.title_ar || title, title_en: source.title_en || title, create_model: Boolean(source.create_model), timestamps: source.timestamps !== false, fields: (source.fields || []).map(field => ({ ...blankField(field.type || 'text'), name: field.name || '', column: field.column || field.name || '', input: field.input || (field.type === 'boolean' ? 'switch' : field.type === 'textarea' ? 'textarea' : 'text'), type: field.type || 'text', label_key: field.label_key || field.name || '', label_ar: field.label_ar || field.label || '', label_en: field.label_en || field.label || '', validation: { required: Boolean(field.required), min: field.min ?? '', max: field.max ?? '' }, in_table: Boolean(field.in_table), filterable: Boolean(field.filterable) })) }; }
export function fillDefinition(form, source, onChange = () => {}) { const definition = normalizeImportedDefinition(source); form.querySelectorAll('[data-definition]').forEach(control => { const next = pathGet(definition, control.dataset.definition, control.type === 'checkbox' ? false : ''); if (control.type === 'checkbox') control.checked = bool(next); else control.value = next; }); CAPABILITIES.forEach(key => { const control = form.querySelector(`[data-capability="${key}"]`); if (control) control.checked = bool(definition.capabilities?.[key]); }); const populate = (selector, fields, adder) => { const container = form.querySelector(selector); container.replaceChildren(); (fields || []).forEach(field => adder(container, field, onChange)); }; populate('#fields', definition.fields, addField); populate('#edit-fields', definition.edit_fields, addField); populate('#columns', definition.columns, addColumn); populate('#filters', definition.filters, addFilter); populate('#actions', definition.actions, addAction); populate('#modals', definition.modals, addModal); syncEditMode(form); onChange(); return definition; }
export function syncEditMode(form) { const custom = readDefinitionControl(form, 'edit_mode') === 'custom'; form.querySelector('#edit-fields').hidden = !custom; form.querySelector('.same-edit-message').hidden = custom; form.querySelector('[data-add="edit-field"]').hidden = !custom; }
export function downloadDefinition(definition, filename = `${definition.resource || 'dashboard'}-v2.json`) { const blob = new Blob([JSON.stringify(definition, null, 2)], { type: 'application/json' }); const url = URL.createObjectURL(blob); const link = document.createElement('a'); link.href = url; link.download = filename; link.click(); URL.revokeObjectURL(url); }
