/** تركيب Select2 وQuill على الفلاتر ونماذج الإضافة والتعديل والنوافذ المخصصة. */

import { buildSelectQuery, mapSelectResults } from './select-options.js';
import { escapeHtml } from './text.js';
import { createEditor } from '../forms/editors.js';
import { createDropzone } from '../forms/dropzones.js';
import { clearSelectDescendants } from '../forms/select2.js';

/**
 * يجد حقل النموذج بالمعرف الفعلي، مع دعم معرفات النوافذ القديمة التي تبدأ بعلامة #.
 * @param {string} prefix بادئة create أو edit أو معرف النافذة.
 * @param {string} id معرف الحقل من تعريف الكنترولر.
 * @returns {jQuery} عنصر الحقل أو مجموعة فارغة؛ لا يعدل DOM.
 */
function fieldElement(prefix, id, scope = null) {
    if (scope) {
        const nested = [...(scope.querySelectorAll?.('[data-field], [data-dashboard-select]') ?? [])]
            .find(element => element.dataset.field === id || element.dataset.dashboardSelect === id);
        if (nested) return $(nested);
    }
    return $(document.getElementById(`${prefix}${id}`) ?? document.getElementById(`${prefix.replace(/^#/, '')}${id}`));
}

/**
 * يرسم خيار اللون مع مربع معاينة، ويبقي بقية الخيارات نصا عاديا.
 * @param {string} id معرف الحقل.
 * @param {object} state خيار Select2.
 * @returns {string|jQuery} نص الخيار أو عنصر آمن يعرض اللون؛ لا يضيف العنصر للصفحة.
 */
function formatColorOption(id, state) {
    if (id !== 'color_id') return escapeHtml(state.text);
    const wrapper = $('<div>').css({ display: 'flex', alignItems: 'center', gap: '8px' });
    wrapper.append($('<span>').css({ width: '16px', height: '16px', borderRadius: '4px', border: '1px solid #ccc', backgroundColor: state.text }));
    wrapper.append($('<span>').text(state.text));
    return wrapper;
}

/**
 * يهيئ قائمة بحث واحدة ويربط علاقة الأب والابن دون تكرار أحداثها عند إعادة التهيئة.
 * @param {object} item تعريف الحقل وعنوان البحث والعلاقات.
 * @param {string} prefix بادئة معرف الحقل في DOM.
 * @param {jQuery|null} dropdownParent حاوية القائمة داخل النافذة.
 * @param {boolean} colorOptions هل تستخدم معاينة اللون لحقول color_id.
 * @returns {void} يركب Select2 وطلبات البحث ومستمع تغيير الحقل الأب.
 * can_edit_by_developer
 */
function initializeSelect(item, prefix, dropdownParent, colorOptions = false, concreteElement = null) {
    const element = concreteElement ? $(concreteElement) : fieldElement(prefix, item.id);
    if (!element.length) return;
    const node = element[0];
    const scope = node.closest?.('[data-multiform-group]') ?? node.form ?? document;
    const localField = id => fieldElement(prefix, id, scope);
    if (element.hasClass('select2-hidden-accessible')) element.select2('destroy');
    if (item.parent_id != null) element.prop('disabled', !localField(item.parent_id).val());
    const options = {
        dropdownParent,
        dir: $.fn.select2.defaults.defaults.dir,
        placeholder: window.choose,
        ajax: {
            url: item.url,
            dataType: 'json',
            /**
             * يجمع بحث Select2 ويبرز حقول العلاقات المطلوبة التي لم تُملأ.
             * @param {object} params مصطلح البحث ورقم الصفحة من Select2.
             * @returns {object} معاملات الخادم؛ يحدث صنف is-invalid ويعرض إشعارات العلاقات الناقصة.
             */
            data: params => {
                const result = buildSelectQuery(item, params, id => localField(id).val());
                for (const [key, rule] of Object.entries(item.relations ?? {})) {
                    const id = isNaN(key) ? key : rule;
                    localField(id).toggleClass('is-invalid', result.missing.includes(id));
                }
                for (const id of result.missing) window.notifyError(`${id} is required`);
                return result.query;
            },
            processResults: mapSelectResults,
        },
    };
    if (colorOptions) {
        /**
         * ينسق خيار القائمة أو القيمة المختارة وفق تعريف حقل اللون.
         * @param {object} state خيار Select2 الذي يحتوي النص والمعرف.
         * @returns {string|jQuery} نص مهرب أو عنصر معاينة لون من formatColorOption.
         */
        options.templateResult = state => formatColorOption(item.id, state);
        /**
         * ينسق خيار القائمة أو القيمة المختارة وفق تعريف حقل اللون.
         * @param {object} state خيار Select2 الذي يحتوي النص والمعرف.
         * @returns {string|jQuery} نص مهرب أو عنصر معاينة لون من formatColorOption.
         */
        options.templateSelection = state => formatColorOption(item.id, state);
        /**
         * يحافظ على التنسيق الذي أعده قالب الخيار في هذه الوحدة.
         * @param {string} markup النص الذي سبق تهريبه داخل formatColorOption.
         * @returns {string} النص نفسه دون إعادة تهريب؛ عناصر jQuery تنشأ بالنص وCSS.
         */
        options.escapeMarkup = markup => markup;
    }
    element.select2(options);
    if (item.child_id != null) {
        element.off('change.dashboardChild').on('change.dashboardChild', () => {
            clearSelectDescendants(node, scope);
            localField(item.child_id).prop('disabled', !element.val());
        });
    }
}

/**
 * يركب محرر Quill مرة واحدة ويزامن HTML مع الحقل المخفي الذي يرسل للخادم.
 * @param {object} item تعريف حقل html.
 * @param {string} formName معرف النموذج مع # اختيارية.
 * @returns {void} ينشئ المحرر ويربط حدث التغيير عندما توجد عناصره في الصفحة.
 */
function initializeEditor(item, formName) {
    const id = `${formName.replace(/^#/, '')}${item.id}`;
    const form = document.getElementById(formName.replace(/^#/, ''));
    const container = [...(form?.querySelectorAll('[data-dashboard-editor]') ?? [])].find(el => el.dataset.dashboardEditor === item.id) ?? document.getElementById(`full-editor${id}`);
    const input = form?.elements.namedItem(item.id) ?? document.getElementById(id);
    createEditor(container, input);
}

/**
 * يهيئ حقول نموذج واحد، وينزل إلى أول صف من حقول multiform المتداخلة.
 * @param {object[]} inputs تعريفات حقول النموذج.
 * @param {string} prefix بادئة معرفات الحقول في Blade.
 * @param {string} formName معرف النموذج المستخدم لمحررات HTML.
 * @returns {void} يركب Select2 وQuill على الحقول الموجودة.
 */
function initializeInputs(inputs, prefix, formName) {
    for (const item of inputs ?? []) {
        if (item.input === 'multiform') {
            const container = document.getElementById(`${prefix}_${item.id}`);
            const groups = [...(container?.querySelectorAll('[data-multiform-group]') ?? [])];
            groups.forEach((group, rowIndex) => {
                for (const child of item.inputs ?? []) {
                    if (child.select2) {
                        const element = [...group.querySelectorAll('[data-field]')].find(node => node.dataset.field === child.id);
                        initializeSelect(child, `${prefix}${item.id}_${rowIndex}_`, $(group).closest('.modal'), true, element);
                    }
                }
            });
            initializeInputs((item.inputs ?? []).filter(child => !child.select2), `${prefix}${item.id}_0_`, formName);
        }
        else if (item.select2) initializeSelect(item, prefix, fieldElement(prefix, item.id).closest('.modal'), true);
        if (item.input === 'html') initializeEditor(item, formName);
        if (item.input === 'upload_images') {
            const formId = formName.replace(/^#/, '');
            const form = document.getElementById(formId);
            const fieldName = `${item.id}`.replace(/\[\]$/, '');
            const target = [...(form?.querySelectorAll('[data-upload-field]') ?? [])].find(el => el.dataset.uploadField === fieldName);
            createDropzone(target ?? document.getElementById(`${formId}dropzone-multi`));
        }
    }
}

/**
 * يجهز قوائم الفلاتر وحقول الإضافة والتعديل والنوافذ المخصصة من تعريف الشاشة.
 * @param {object} state إعدادات الشاشة الحالية.
 * @returns {void} يركب إضافات الحقول، دون إنشاء أو تعديل أي سجل بالخادم.
 */
export function initializeFields(state = window) {
    const filterModal = $('#filters_modal');
    for (const filter of state.filters) {
        if (filter.select2) initializeSelect(filter, 'filter', filterModal.length ? filterModal : null);
    }
    initializeInputs(state.inputs, 'create', 'add_form');
    initializeInputs(state.update_inputs, 'edit', 'edit_form');
    for (const modal of state.modals ?? []) initializeInputs(modal.inputs, modal.id, modal.form_id);
}
