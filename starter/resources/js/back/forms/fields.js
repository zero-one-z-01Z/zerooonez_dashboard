/** توجيه تعبئة الحقول ومعالجة الحقول العادية والمترجمة والمتكررة. */

import { orderFieldsByDependencies, updateSelect2Options } from './select2.js';
import { addItemMultiForm, clearMultiFormRows } from './repeaters.js';
import { populateBoundary, initMap, populateLocationMap } from './maps.js';
import { populateCarousel, populateImages, populateImage, populateVideo } from './media.js';
import { createEditor } from './editors.js';

/**
 * يتحقق من تعريف الحقل الصريح في نموذج مولد، دون افتراض معنى خاص لاسمه.
 * @param {string} formSelector محدد النموذج الذي يحمل data-dashboard-field-types اختياريا.
 * @param {string} key اسم الحقل المطلوب تعبئته.
 * @returns {boolean} true للحقل المعلن فقط؛ البيانات الغائبة أو JSON غير الصالح يحافظان على السلوك القديم.
 */
function dashboardFieldDescriptor(formSelector, key) {
    const metadata = $(formSelector)[0]?.getAttribute?.('data-dashboard-field-types');
    if (!metadata) return null;
    try {
        const fields = JSON.parse(metadata);
        if (fields === null || typeof fields !== 'object' || Array.isArray(fields)
            || !Object.prototype.hasOwnProperty.call(fields, key)) return null;
        const descriptor = fields[key];
        if (typeof descriptor === 'string') return { type: descriptor, input: 'regular', legacy: true };
        if (Array.isArray(descriptor) && descriptor.length > 0 && descriptor.every(value => typeof value === 'string')) {
            return { type: descriptor[0] ?? 'text', input: descriptor[1] ?? descriptor[0] ?? 'regular' };
        }
        return descriptor && typeof descriptor === 'object' && !Array.isArray(descriptor)
            && (typeof descriptor.type === 'string' || typeof descriptor.input === 'string')
            ? descriptor : null;
    } catch {
        return null;
    }
}

function nestedFieldDescriptor(formSelector, key) {
    const element = $(formSelector).find(`[name="${key}"], [name="${key}[]"]`)[0];
    try {
        const descriptor = JSON.parse(element?.dataset?.item ?? '{}');
        return descriptor?.input ? descriptor : null;
    } catch { return null; }
}

/**
 * يملأ الحقول المعلنة في النماذج المولدة كحقول عادية، ثم يطبق توجيه الأسماء الخاص بالنماذج القديمة.
 * @param {string} formSelector محدد النموذج.
 * @param {string} key اسم الحقل.
 * @param {*} value قيمة الحقل.
 * @param {Object} data بيانات السجل أو العلاقة اللازمة للقوائم والخرائط.
 * @returns {void} يفوض الكتابة إلى الحقل أو الوسيط المناسب؛ أسماء خاصة قديمة غير مدعومة في فروع التنفيذ تبقى بلا تغيير.
 * can_edit_by_developer
 */
export function populateField(formSelector, key, value, data) {
    const descriptor = dashboardFieldDescriptor(formSelector, key) ?? nestedFieldDescriptor(formSelector, key);
    if (descriptor) {
        const input = descriptor.input ?? 'regular';
        if (['regular', 'text', 'textarea', 'single', 'multi_select', 'switch', 'hidden', 'html', 'multiform'].includes(input)) {
            return populateRegularField(formSelector, key, value, data, descriptor);
        }
        if (input === 'upload_images') return populateImages(formSelector, value, key);
        if (input === 'image_preview') return populateCarousel(value);
        if (input === 'permissions') return populatePermissions(formSelector, value);
        if (input === 'image') return populateImage(formSelector, key, value);
        if (input === 'boundary') return populateBoundary(formSelector, key, value);
        if (input === 'map') {
            return populateLocationMap(formSelector, key, data ?? {});
        }
        if (input === 'file') return;
    }
    const specialized = ['lat', 'lng', 'image', 'banner', 'logo', 'images', 'permissions', 'boundary', 'preview_images', 'image_ar', 'image_en', 'video'];
    if (!specialized.includes(key)) return populateRegularField(formSelector, key, value, data);
    if (key === 'preview_images') return populateCarousel(value);
    if (key === 'images') return populateImages(formSelector, value);
    if (key === 'permissions') return populatePermissions(formSelector, value);
    if (key === 'image') return populateImage(formSelector, key, value);
    if (key === 'boundary') return populateBoundary(formSelector, key, value);
    if (key === 'lat') return initMap(data.lat, data.lng, formSelector);
    if (key === 'video' && data.video != null) return populateVideo(formSelector, data.video);
}

/**
 * يملأ الحقول العادية والمتداخلة والمتكررة ويربط المحرر النصي بحقل HTML المخفي.
 * @param {string} formSelector محدد النموذج.
 * @param {string} key اسم الحقل أو المسار المتداخل.
 * @param {*} value القيمة المطلوب عرضها.
 * @param {Object} data بيانات العلاقة الخاصة بالحقل.
 * @returns {void} يغير الحقول الموجودة وقد يضيف صفوف multiform أو يهيئ Quill وSelect2.
 */
function populateRegularField(formSelector, key, value, data, descriptor = null) {
    if (Array.isArray(value)) {

        const $container = $(formSelector)
            .find(`[data-multiform-container][id$='_${key}']`)
            .first();

        if ($container.length){
            const container = $container[0];
            clearMultiFormRows(container);
            for (let i = 0; i < value.length; i++) addItemMultiForm('edit', key, container);
            let inputs = descriptor?.inputs ?? [];
            if (!inputs.some(input => input?.id)) {
                try { inputs = JSON.parse(container.dataset.inputs ?? '[]'); } catch { inputs = []; }
            }
            value.forEach((rowData, index) => {
                orderFieldsByDependencies(rowData, inputs).forEach(fieldKey => {
                    const fieldName = `${key}[${index}][${fieldKey}]`;
                    populateField(formSelector, fieldName, rowData[fieldKey], rowData);
                });
            });

            return;
        }

    }

    var $element = $(formSelector).find('[name="' + key + '"]');
    if ($element.length === 0) {
        $element = $(formSelector).find('[name="' + key+'[]' + '"]');
    }
    if (typeof value === 'object' && value !== null) {
        $.each(value, function (key2, value) {
            var final_key = key+'.'+key2;
            var isTranslated = typeof value === 'object' && value !== null && value.hasOwnProperty('en');
            if (isTranslated) {
                try {
                    var translations = value;
                    $.each(translations, function (lang, translatedValue) {
                        var fieldName = final_key + '_' + lang;
                        populateField(formSelector, fieldName, translatedValue, value);
                    });
                } catch (e) {
                    console.error("Error parsing translated value for " + final_key, e);
                }
            } else {
                populateField(formSelector, final_key, value, value);
            }
        });

    }
    if ($element.length > 0) {
        if ($element.is(':checkbox') || $element.is(':radio')) {
            const isChecked =
                value === true ||
                value === 1 ||
                value === '1' ||
                value === 'true';

            $element.prop('checked', isChecked);
            return;
        }

        if ($element.is('select')) {

            if ($element.hasClass('select2')) {
                updateSelect2Options(data,key, value, $element,formSelector);
            } else {
                $element.val(value).trigger('change');
            }
        } else {
            $element.val(value);
        }
        if ($element.hasClass('check-html')) {
            const form = $(formSelector)[0];
            const editor = [...(form?.querySelectorAll?.('[data-dashboard-editor]') ?? [])]
                .find(node => node.dataset.dashboardEditor === key)
                ?? document.getElementById(`full-editor${formSelector.substring(1)}${key}`);
            createEditor(editor, $element[0]);
        }
    }
}

/**
 * يفعّل مربعات الصلاحيات التي يعيدها الخادم بمفتاح key_name.
 * @param {string} formSelector محدد النموذج.
 * @param {Array} value قائمة الصلاحيات.
 * @returns {void} يحدد مربعات key_name المطابقة فقط؛ تصفير القيم يتم في مسار إعادة ضبط النموذج.
 */
function populatePermissions(formSelector, value) {
    $.each(value, function (key,permission) {

        var $element = $(formSelector).find('[name="' + permission.key_name + '"]');

        if ($element.length > 0) {

            $element.prop('checked', true);
        }
    });
}
