/** تهيئة Select2 وخيارات التعديل والقوائم التابعة. */

import { direction } from './formatting.js';

/**
 * يقرأ مسارًا متداخلًا مفصولًا بالنقاط دون رمي خطأ عند غياب أحد أجزائه.
 * @param {Object} obj الكائن المصدر.
 * @param {string} path مسار مثل user.name.
 * @returns {*} القيمة، أو undefined عند غياب المسار.
 */
export function getNestedValue(obj, path) {
    if (!path) return undefined;
    return path.split('.').reduce((acc, key) => {
        return acc && acc[key] !== undefined ? acc[key] : undefined;
    }, obj);
}

function leafFieldName(key) {
    const bracket = /\[([^\]]+)]$/.exec(key);
    return bracket ? bracket[1] : key.split('.').pop();
}

/** يرتب مفاتيح السجل بحيث يُعبأ كل parent قبل child مع إبقاء المفاتيح غير المعرفة. */
export function orderFieldsByDependencies(data, inputs = []) {
    const keys = Object.keys(data ?? {});
    const definitions = new Map((inputs ?? []).filter(item => item?.id).map(item => [item.id, item]));
    const ordered = [];
    const visiting = new Set();
    const visited = new Set();
    const visit = key => {
        if (visited.has(key)) return;
        if (visiting.has(key)) return;
        visiting.add(key);
        const parent = definitions.get(key)?.parent_id;
        if (parent && keys.includes(parent)) visit(parent);
        visiting.delete(key);
        visited.add(key);
        ordered.push(key);
    };
    keys.forEach(visit);
    return ordered;
}

function selectDefinition(element, key) {
    try {
        const item = JSON.parse(element?.dataset?.item ?? '{}');
        if (item && typeof item === 'object' && item.id) return item;
    } catch { /* use page metadata */ }
    const field = leafFieldName(key);
    return (window.update_inputs ?? []).find(item => item?.id === field) ?? {};
}

function selectScope(element, formSelector = null) {
    return element?.closest?.('[data-multiform-group]') ?? (formSelector ? $(formSelector)[0] : element?.form) ?? document;
}

function scopedField(scope, id) {
    if (!scope || !id) return null;
    return [...scope.querySelectorAll?.('[data-field], [data-dashboard-select]') ?? []]
        .find(element => element.dataset.field === id || element.dataset.dashboardSelect === id)
        ?? scope.querySelector?.(`[name="${id}"], [name="${id}[]"]`)
        ?? null;
}

function clearSelect(element) {
    if (!element) return;
    element.querySelectorAll?.('option[data-dashboard-dynamic]').forEach(option => option.remove());
    if (element.multiple) [...element.options].forEach(option => { option.selected = false; });
    else element.value = '';
    element.disabled = true;
    if (typeof $ === 'function') $(element).trigger('change.select2');
}

/** يمسح كل الأبناء داخل النموذج أو صف multiform نفسه، دون التأثير في صف مجاور. */
export function clearSelectDescendants(source, scope = selectScope(source)) {
    if (!source || !scope) return [];
    const cleared = [];
    const queue = [source.dataset.field ?? source.dataset.dashboardSelect ?? source.id];
    while (queue.length) {
        const parent = queue.shift();
        const children = [...scope.querySelectorAll?.('[data-parent-id]') ?? []]
            .filter(element => element.dataset.parentId === parent);
        for (const child of children) {
            if (cleared.includes(child)) continue;
            clearSelect(child);
            cleared.push(child);
            queue.push(child.dataset.field ?? child.dataset.dashboardSelect ?? child.id);
        }
    }
    return cleared;
}

/**
 * يضيف الخيارات المحددة من بيانات التعديل ويفعّل قوائم Select2 التابعة حسب update_inputs.
 * @param {Object} data بيانات السجل والعلاقات.
 * @param {string} key اسم الحقل.
 * @param {*} value المعرف أو مجموعة الخيارات المحددة.
 * @param {jQuery} $element القائمة المستهدفة.
 * @param {string} formSelector محدد نموذج التعديل.
 * @returns {void} يلحق الخيارات المحددة ويطلق change ويفعّل القوائم المرتبطة المتاحة.
 * can_edit_by_developer
 */
export function updateSelect2Options(data, key, value, $element,formSelector) {
    const element = $element?.[0] ?? $element;
    if (!element) return;
    const input = selectDefinition(element, key);
    if (!input.select2 && !element.classList?.contains('select2')) return;
    const valueName = input.value_name ?? 'text';
    const related = getNestedValue(data, input.key_name);
    element.querySelectorAll?.('option[data-dashboard-dynamic]').forEach(option => option.remove());
    const multiple = Boolean(element.multiple || `${element.name ?? ''}`.endsWith('[]'));
    if (multiple) {
        for (const option of Array.isArray(value) ? value : []) {
            const optionId = typeof option === 'object' ? (option.id ?? option.value) : option;
            const optionText = typeof option === 'object' ? (getNestedValue(option, valueName) ?? option.text ?? optionId) : option;
            const node = new Option(optionText, optionId, false, true);
            node.dataset.dashboardDynamic = 'true';
            element.append(node);
        }
    } else if (value !== null && value !== undefined && value !== '') {
        const label = related && typeof related === 'object'
            ? (getNestedValue(related, valueName) ?? value) : value;
        const node = new Option(label, value, false, true);
        node.dataset.dashboardDynamic = 'true';
        element.append(node);
    }
    element.disabled = false;
    $(element).trigger('change');
}

/**
 * يهيئ Select2 باتجاه اللغة مع السماح بتجاوز الإعدادات الافتراضية.
 * @param {string|HTMLElement} selector القائمة المستهدفة.
 * @param {Object} [options={}] خيارات Select2 الإضافية.
 * @returns {void} يركب Select2 على العناصر المطابقة بالخيارات المدمجة.
 */
export function initializeSelect2(selector, options = {}) {
    const defaultOptions = {
        dir: direction(),
        placeholder: 'اختر',
    }

    const elements = $(selector);
    elements.each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
    });
    elements.select2({
        ...defaultOptions,
        ...options
    });
}

/**
 * يدمر نسخة Select2 السابقة قبل إعادة تهيئتها بخيارات جديدة.
 * @param {HTMLElement} element القائمة.
 * @param {Object} [options={}] إعدادات Select2.
 * @returns {void} يدمر مثيل العنصر السابق عند وجوده ثم يركب الإعدادات المطلوبة.
 */
export function initSelect2(element, options = {}) {
    if ($(element).hasClass("select2-hidden-accessible")) {
        $(element).select2('destroy');
    }

    $(element).select2(options);
}

/**
 * يجهّز قائمة Select2 بعيدة داخل الصف المتكرر مع البحث والتصفح وربط قائمة الأب والابن.
 * @param {Object} options إعدادات elementId وurl وعلاقات parent_id وchild_id وchild_key وclassName.
 * @returns {void} يركب بحث AJAX والتصفح وفتح القائمة التابعة عند تغيير الأب.
 * can_edit_by_developer
 */
export function setupSelect2({
                          elementId,
                          element = null,
                          modalName,
                          url,
                          parent_id = null,
                          child_id = null,
                          parent_key = null,
                          child_key = null,
                          className = ''
                      }) {
    const target = element ?? document.getElementById(elementId);
    if (!target) return;
    const $target = $(target);
    const scope = selectScope(target);
    const parent = parent_id !== null ? scopedField(scope, parent_id) : null;
    target.dataset.dashboardSelect ||= target.dataset.field ?? elementId;
    if (parent_id !== null) target.disabled = !(parent?.value);

    if ($target.hasClass('select2-hidden-accessible')) $target.select2('destroy');
    $target.select2({
        dropdownParent: $target.closest('.modal, .modal-content, .modal-body'),

        dir: $.fn.select2.defaults.defaults.dir,
        placeholder: window.choose,
        templateResult: formatColorOption,
        templateSelection: formatColorOption,
        /**
         * يبقي تنسيق الخيار الذي أرجعه قالب اللون القديم.
         * @param {string} markup نص القالب الممرر من Select2.
         * @returns {string} النص كما هو؛ يعتمد هذا المسار على مصدر موثوق للقوالب.
         */
        escapeMarkup: markup => markup,
        ajax: {
            url: url,
            dataType: 'json',
            /**
             * يبني معاملات بحث القائمة وصفحتها ويرفق معرف الأب ومفتاح العلاقة إن وجدا.
             * @param {object} params term وpage من Select2.
             * @returns {object} search وpage، وparent_id وchild_key عند توفر قيمة الأب.
             */
            data: function (params) {
                let queryParams = {
                    search: params.term,
                    page: params.page || 1
                };

                if (parent_id !== null) {
                    const parentValue = scopedField(scope, parent_id)?.value;
                    if (parentValue !== undefined && parentValue !== null && parentValue !== '') {
                        queryParams.parent_id = parentValue;
                        if (parent_key) queryParams.parent_key = parent_key;
                        if (child_key) queryParams.child_key = child_key;
                    }
                }

                return queryParams;
            },
            /**
             * يحول data.data إلى خيارات Select2 ومعلومة استمرار الصفحات.
             * @param {object} data استجابة الخادم التي تحتوي قائمة data بمعرفات ونصوص.
             * @param {object} params معاملات Select2؛ يثبت page=1 عند غيابه.
             * @returns {object} results وpagination.more؛ تعد الصفحة غير الفارغة قابلة لاستكمال البحث.
             */
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.data.map(r => ({
                        id: r.id,
                        text: r.text
                    })),
                    pagination: {
                        more: data.data.length > 0
                    }
                };
            }
        }
    });

    if (child_id !== null) {
        $target.off('change.dashboardChild').on('change.dashboardChild', function () {
            clearSelectDescendants(target, scope);
            const child = scopedField(scope, child_id);
            if (child) child.disabled = !target.value;
        });
    }
}

/**
 * يعرض اسم الخيار، أو مربع لون عندما يكون معرف الخيار color_id وفق العقد الحالي.
 * @param {Object} state خيار Select2 بمعرف id ونص text.
 * @returns {string} نص أو HTML تنسيق اللون.
 */
export function formatColorOption(state) {
    if (!state.id) return state.text;

    if (state.id === 'color_id') {
        const color = state.text;
        return `
            <div style="display:flex;align-items:center;gap:8px;">
                <span style="width:16px;height:16px;background:${color};border:1px solid #ccc"></span>
                <span>${state.text}</span>
            </div>
        `;
    }
    return state.text;
}
