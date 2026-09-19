/** إضافة ومسح وحذف صفوف النماذج المتكررة مع أسماء وفهارس مستقرة. */

import { setupSelect2 } from './select2.js';
import { createDropzone, destroyDropzones } from './dropzones.js';
import { createEditor, destroyEditors } from './editors.js';
import { createBoundaryMap, createLocationMap, destroyMaps } from './maps.js';

/** يبني اسم حقل صف متكرر، ويُستخدم نفسه عند الإضافة وإعادة الترقيم. */
export function repeatedFieldName(groupName, rowIndex, field, multiple = false) {
    return `${groupName}[${rowIndex}][${field}]${multiple ? '[]' : ''}`;
}

/** يعيد فهرسة كل الصفوف بعد الحذف كي لا تتكرر names أو ids عند الإضافة التالية. */
export function reindexMultiForm(container) {
    if (!container) return [];
    const groupName = container.dataset.multiformName ?? container.id?.replace(/^.*?_/, '') ?? '';
    const idPrefix = container.dataset.idPrefix ?? `${container.id?.replace('_', '') ?? groupName}_`;
    const groups = [...container.querySelectorAll('[data-multiform-group]')];
    groups.forEach((group, rowIndex) => {
        group.dataset.rowIndex = `${rowIndex}`;
        const stableAttributes = [
            'id', 'name', 'for', 'data-upload-field', 'data-param-name', 'data-deleted-key',
            'data-lat-field', 'data-lng-field',
        ];
        for (const element of [group, ...group.querySelectorAll('*')]) {
            const ownAttributes = [...attributeNames(element)].filter(attribute => attribute.startsWith('data-dashboard-'));
            for (const attribute of [...new Set([...stableAttributes, ...ownAttributes])]) {
                if (!element.hasAttribute?.(attribute)) continue;
                const original = element.getAttribute(attribute);
                if (typeof original !== 'string') continue;
                const updated = original
                    .replaceAll('__INDEX__', `${rowIndex}`)
                    .replace(new RegExp(`${escapeRegExp(groupName)}\\[\\d+]`, 'g'), `${groupName}[${rowIndex}]`)
                    .replace(new RegExp(`${escapeRegExp(idPrefix)}\\d+_`, 'g'), `${idPrefix}${rowIndex}_`);
                if (updated !== original) element.setAttribute(attribute, updated);
            }
        }
        group.querySelectorAll('[data-field]').forEach(element => {
            const field = element.dataset.field;
            if (!field) return;
            const multiple = element.tagName === 'SELECT' && element.multiple;
            element.name = repeatedFieldName(groupName, rowIndex, field, multiple);
            if (element.id) {
                const oldId = element.id;
                element.id = `${idPrefix}${rowIndex}_${field}`;
                group.querySelectorAll(`label[for="${oldId}"]`).forEach(label => { label.htmlFor = element.id; });
            }
        });
        group.querySelectorAll('[data-multi-select-presence]').forEach(marker => {
            marker.name = repeatedFieldName(groupName, rowIndex, `${marker.dataset.multiSelectPresence}__present`);
        });
    });
    return groups;
}

function attributeNames(element) {
    const attributes = element?.attributes;
    if (!attributes) return [];
    if (typeof attributes[Symbol.iterator] === 'function') {
        return [...attributes].map(attribute => attribute.name).filter(Boolean);
    }
    return Object.keys(attributes);
}

function escapeRegExp(value) {
    return `${value}`.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function destroyRowWidgets(group) {
    destroyDropzones(group);
    destroyEditors(group);
    destroyMaps(group);
    group?.querySelectorAll('select.select2').forEach(element => {
        if (typeof $ === 'function' && $(element).hasClass('select2-hidden-accessible')) $(element).select2('destroy');
        element.classList.remove('select2-hidden-accessible');
        element.removeAttribute('data-select2-id');
        const widget = element.nextElementSibling;
        if (widget?.classList.contains('select2')) widget.remove();
    });
}

function resetRowValues(group) {
    group.querySelectorAll('input, textarea, select').forEach(element => {
        const field = element.dataset.field;
        if (element.type === 'file') element.value = '';
        else if (element.type === 'checkbox' || element.type === 'radio') {
            element.checked = false;
            element.value = '1';
        } else if (element.type === 'hidden') {
            const checkbox = field && group.querySelector(`input[type="checkbox"][data-field="${field}"]`);
            element.value = checkbox ? '0' : '';
        } else if (element.tagName === 'SELECT') {
            element.querySelectorAll('option[data-dashboard-dynamic]').forEach(option => option.remove());
            if (element.multiple) [...element.options].forEach(option => { option.selected = false; });
            else element.selectedIndex = 0;
        } else element.value = '';
    });
    group.querySelectorAll('[data-dashboard-editor], [data-dashboard-boundary], [data-dashboard-location-map]').forEach(element => {
        element.replaceChildren();
        element.classList.remove('ql-container', 'ql-snow', 'leaflet-container');
    });
    group.querySelectorAll('.dropzone').forEach(element => {
        element.querySelectorAll('.dz-preview').forEach(preview => preview.remove());
        element.classList.remove('dz-started');
    });
}

function cloneRow(container) {
    const template = container.querySelector('template[data-multiform-template]');
    if (template?.content?.firstElementChild) return template.content.firstElementChild.cloneNode(true);
    const first = container.querySelector('[data-multiform-group]');
    return first ? first.cloneNode(true) : null;
}

function initializeRowSelects(group) {
    group.querySelectorAll('select.select2').forEach(element => {
        let item = {};
        try { item = JSON.parse(element.dataset.item ?? '{}'); } catch { item = {}; }
        setupSelect2({
            element,
            modalName: element.dataset.modal,
            url: item.url,
            parent_id: item.parent_id ?? null,
            child_id: item.child_id ?? null,
            parent_key: item.parent_key ?? null,
            child_key: item.child_key ?? null,
        });
    });
}

function namedElement(group, name) {
    return [...group.querySelectorAll('[name]')].find(element => element.name === name) ?? null;
}

function initializeRowWidgets(group) {
    initializeRowSelects(group);
    group.querySelectorAll('.dropzone[data-upload-field]').forEach(createDropzone);
    group.querySelectorAll('[data-dashboard-editor]').forEach(container => {
        createEditor(container, namedElement(group, container.dataset.dashboardEditor));
    });
    group.querySelectorAll('[data-dashboard-boundary]').forEach(element => {
        createBoundaryMap(element, namedElement(group, element.dataset.dashboardBoundary));
    });
    group.querySelectorAll('[data-dashboard-location-map]').forEach(element => {
        const latInput = namedElement(group, element.dataset.latField);
        const lngInput = namedElement(group, element.dataset.lngField);
        createLocationMap(element, latInput, lngInput, { lat: latInput?.value, lng: lngInput?.value });
    });
}

/** يدمر مكونات الصفوف قبل إزالتها، ويترك template وعلامة الحضور دون تغيير. */
export function clearMultiFormRows(container) {
    if (!container) return;
    container.querySelectorAll('[data-multiform-group]').forEach(group => {
        destroyRowWidgets(group);
        group.remove();
    });
}

/** يعيد المجموعة إلى الحد الأدنى؛ min_rows=0 يترك علامة الحضور بلا صفوف. */
export function resetMultiForm(containerId, id) {
    const container = document.getElementById(`${containerId}_${id}`);
    if (!container) return;
    clearMultiFormRows(container);
    const minimum = Math.max(0, Number(container.dataset.minRows ?? 1));
    for (let index = 0; index < minimum; index++) addItemMultiForm(containerId, id, container);
}

/** ينقل قواعد data-validation إلى jQuery Validate لعناصر الصف المضاف. */
export function addValidationForRow(containerId, rowIndex) {
    const container = document.getElementById(containerId);
    const newGroup = container?.querySelectorAll('[data-multiform-group]')?.[rowIndex];
    if (!newGroup || typeof $ !== 'function') return;
    newGroup.querySelectorAll('input, textarea, select').forEach(element => {
        if (!element.dataset.validation) return;
        try { $(element).rules('add', JSON.parse(element.dataset.validation)); } catch { /* invalid metadata is ignored */ }
    });
}

/** يضيف صفًا نظيفًا من template ثابت ثم يعيد فهرسة المجموعة ويهيئ Select2 داخل الصف نفسه. */
export function addItemMultiForm(containerId, id, defaultContainer = null) {
    const container = defaultContainer ?? document.getElementById(`${containerId}_${id}`);
    if (!container) return null;
    const current = container.querySelectorAll('[data-multiform-group]').length;
    const maximum = container.dataset.maxRows === undefined || container.dataset.maxRows === ''
        ? Number.POSITIVE_INFINITY : Number(container.dataset.maxRows);
    if (current >= maximum) return null;
    const row = cloneRow(container);
    if (!row) return null;
    destroyRowWidgets(row);
    resetRowValues(row);
    container.appendChild(row);
    const groups = reindexMultiForm(container);
    initializeRowWidgets(row);
    addValidationForRow(container.id, groups.indexOf(row));
    return row;
}

/** يحذف الصف ضمن min_rows ثم يعيد ترقيم الباقي كي تكون الإضافة التالية فريدة. */
export function removeItemMultiForm(button) {
    const container = button?.closest?.('[data-multiform-container]');
    const group = button?.closest?.('[data-multiform-group]');
    if (!container || !group) return false;
    const groups = container.querySelectorAll('[data-multiform-group]');
    const minimum = Math.max(0, Number(container.dataset.minRows ?? 0));
    if (groups.length <= minimum) return false;
    destroyRowWidgets(group);
    group.remove();
    reindexMultiForm(container);
    return true;
}
