/** جلب السجلات ومسح النماذج وتوزيع قيمها على الحقول. */

import { populateField } from './fields.js';
import { orderFieldsByDependencies } from './select2.js';
import { resetDropzones } from './dropzones.js';
import { showToast, togglePageLoader } from './feedback.js';

/**
 * يعيد حالة التحقق وقيم عناصر النموذج إلى القيم الأصلية.
 * @param {string|jQuery} form النموذج المراد مسحه.
 * @returns {void} يستدعي resetForm للتحقق ثم reset الأصلي للنموذج؛ يحتاج نموذجًا موجودًا.
 */
export function resetTheForm(form) {
    var $form = $(form);

    if ($.validator) {
        $form.validate().resetForm();
    }
    $form[0].reset();
}

// لكل نموذج طلب تعبئة واحد؛ السجل المنفصل للتحميل يمنع طلبًا قديمًا من إخفاء مؤشر طلب أحدث.
const pendingPopulations = new Map();
const loadingPopulations = new Set();

/**
 * يتحقق من أن الطلب هو الأحدث وأن نفس عنصر النموذج ما زال متصلًا ومطابقًا للمحدد.
 * @param {Object} entry سجل الطلب الذي يحتفظ بعنصر النموذج ومحدده.
 * @returns {boolean} هل يجوز لهذا الطلب تعديل النموذج الحالي؟
 */
function isCurrentPopulation(entry) {
    return pendingPopulations.get(entry.form) === entry && entry.form.isConnected
        && $(entry.selector)[0] === entry.form;
}

/**
 * ينهي حصة الطلب من مؤشر التحميل مرة واحدة، دون مسح سجل طلب أحدث على النموذج نفسه.
 * @param {Object} entry سجل الطلب المنتهي أو الملغى.
 * @returns {void} يحرر مرجع القراءة ويخفي المؤشر فقط عند انتهاء آخر قراءة تعبئة.
 */
function finishPopulation(entry) {
    if (pendingPopulations.get(entry.form) === entry) pendingPopulations.delete(entry.form);
    if (loadingPopulations.delete(entry) && loadingPopulations.size === 0) togglePageLoader('off');
}

/**
 * يبطل الطلب قبل إلغائه حتى لا تعالج callbacks المتزامنة للإلغاء أخطاء أو بيانات قديمة.
 * @param {Object} entry سجل طلب GET المراد إلغاؤه.
 * @returns {void} يحرر سجل القراءة ثم يستدعي abort على jqXHR إن كان قد أنشئ.
 */
function cancelPopulation(entry) {
    finishPopulation(entry);
    entry.request?.abort();
}

/**
 * يلغي طلبات تعبئة النماذج داخل حاوية ستزال أو سيستبدل محتواها، دون لمس طلبات الحفظ POST.
 * @param {HTMLElement|jQuery|string} root حاوية النوافذ أو نموذج واحد؛ القيمة الغائبة لا تلغي شيئًا.
 * @returns {number} عدد طلبات GET التي أبطلها هذا الاستدعاء.
 */
export function cancelPendingFormPopulation(root) {
    const container = typeof root === 'string' ? $(root)[0] : root?.jquery ? root[0] : root;
    if (!container) return 0;
    let cancelled = 0;
    for (const entry of [...pendingPopulations.values()]) {
        if (container === entry.form || container.contains?.(entry.form)) {
            cancelPopulation(entry);
            cancelled++;
        }
    }
    return cancelled;
}

/**
 * يجلب بيانات سجل لنفس عنصر النموذج فقط؛ يلغي قراءة سابقة ويهمل نتائج النماذج المستبدلة أو المنفصلة.
 * @param {string} url مسار GET للسجل.
 * @param {string|jQuery|HTMLElement} formSelector محدد النموذج المستهدف؛ تبقى محددات Blade النصية مدعومة.
 * @returns {?Object} طلب jqXHR لإمكانية الإلغاء، أو null إذا لم يعد النموذج موجودًا.
 */
export function populateFormFromAjax(url, formSelector) {
    const form = $(formSelector)[0];
    if (!form || !form.isConnected) return null;
    const previous = pendingPopulations.get(form);
    const entry = { form, selector: formSelector, request: null };
    pendingPopulations.set(form, entry);
    loadingPopulations.add(entry);
    if (previous) cancelPopulation(previous);
    try {
        entry.request = $.ajax({
            url,
            type: 'GET',
            dataType: 'json',
            /**
             * يظهر المؤشر قبل بدء قراءة النموذج الحالية.
             * @returns {void} يحافظ حساب القراءات المعلقة خارج هذا المعالج.
             */
            beforeSend() {
                togglePageLoader('on');
            },
            /**
             * يملأ نفس نموذج الطلب فقط إذا ظل الأحدث والمتصل بالصفحة.
             * @param {object} data استجابة الخادم بالشكل {data: record}.
             * @returns {void} يمسح ملفات Dropzone التابعة للنموذج ويعيد ضبطه ثم يوزع حقول السجل.
             */
            success(data) {
                if (!isCurrentPopulation(entry)) return;
                resetDropzones(form);
                resetTheForm(form);
                if (isCurrentPopulation(entry)) window.setFormData(data.data, formSelector);
            },
            /**
             * يعرض فشل القراءة الحالية ويتجاهل الإلغاء والردود التي فقدت ملكية النموذج.
             * @param {jqXHR} xhr رد jQuery بما فيه الحالة وJSON الخطأ.
             * @param {string} status وصف حالة الطلب.
             * @param {string|Error} error الخطأ الذي مرره jQuery.
             * @returns {void} يسجل الخطأ ويعرض Toast عندما يكون الطلب ما زال صالحًا للواجهة.
             */
            error(xhr, status, error) {
                if (status === 'abort' || !isCurrentPopulation(entry)) return;
                console.error(error);
                showToast('error');
            },
            /**
             * ينهي حصة القراءة المنتهية من مؤشر التحميل.
             * @returns {void} يستدعي finishPopulation دون إخفاء مؤشر قراءة أخرى معلقة.
             */
            complete() {
                finishPopulation(entry);
            }
        });
    } catch (error) {
        finishPopulation(entry);
        throw error;
    }
    return entry.request;
}

/**
 * يوزع بيانات السجل على الحقول، ويحوّل الكائنات المترجمة إلى حقول مثل name_ar وname_en.
 * @param {Object} data بيانات السجل.
 * @param {string} formSelector محدد النموذج.
 * @returns {void} يكتب قيم الحقول المناسبة عبر populateField؛ لا يحفظ البيانات بالخادم.
 */
export function setFormData(data,formSelector){
    const selector = typeof formSelector === 'string' ? formSelector : `#${formSelector?.id ?? ''}`;
    const formId = selector.replace(/^#/, '');
    const state = globalThis.window ?? {};
    const inputs = formId === 'edit_form' ? (state.update_inputs ?? [])
        : formId === 'add_form' ? (state.inputs ?? [])
            : ((state.modals ?? []).find(modal => `${modal.form_id}`.replace(/^#/, '') === formId)?.inputs ?? []);
    orderFieldsByDependencies(data, inputs).forEach(key => {
        const value = data[key];

        var isTranslated = typeof value === 'object' && value !== null && value.hasOwnProperty('en');
        if (isTranslated) {
            try {
                var translations = value;
                $.each(translations, function (lang, translatedValue) {
                    var fieldName = key + '_' + lang;
                    populateField(formSelector, fieldName, translatedValue, data);
                });
            } catch (e) {
                console.error("Error parsing translated value for " + key, e);
            }
        } else {
            populateField(formSelector, key, value, data);
        }
    });
}

/**
 * يضع معرف السجل في الحقل المخفي id داخل النموذج.
 * @param {number|string} id معرف السجل.
 * @param {string} formId محدد النموذج.
 * @returns {void} يحدث قيمة [name="id"] داخل النموذج المحدد فقط.
 */
export function populateIdField(id, formId) {
    $(formId).find('[name="id"]').val(id);
}

/**
 * يضع data.id في حقل المعرف فقط؛ استخدم setFormData لتعبئة بقية بيانات السجل.
 * @param {Object} data بيانات السجل التي تحتوي على id.
 * @param {string} formId محدد النموذج.
 * @returns {void} يحدث قيمة الحقل المخفي id دون الاعتماد على معرف عام في المتصفح.
 */
export function populateForm(data, formId) {
    populateIdField(data.id, formId);
}
