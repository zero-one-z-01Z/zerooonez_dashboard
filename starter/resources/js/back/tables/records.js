/** عمليات قراءة وإضافة وتعديل وحذف السجلات وإرسال ملفات النموذج وفق روابط الشاشة. */

import { replaceUrlPlaceholder } from '../datatables/urls.js';
import { closeModal } from '../forms/modal-lifecycle.js';
import { appendDropzoneFiles, getDropzones, resetDropzones } from '../forms/dropzones.js';

/**
 * يجلب سجل التعديل ويملأ النموذج الحالي عن طريق دالة CRUD المشتركة.
 * @param {Event} event حدث الإجراء الممرر من موزع الأحداث.
 * @param {jQuery} object زر الإجراء الذي يحمل data-id.
 * @returns {void} يبدأ طلب جلب السجل ويملأ #edit_form عند النجاح.
 */
export function editItem(event, object) {
    window.populateFormFromAjax(replaceUrlPlaceholder(window.get_single_item, object.data('id')), '#edit_form');
}

/**
 * يجلب السجل ويملأ النموذج الذي يحدده إجراء نافذة مخصصة.
 * @param {Event} event حدث الإجراء.
 * @param {jQuery} object عنصر يحمل data-id وdata-form.
 * @returns {void} يبدأ طلب جلب السجل ويملأ النموذج المقصود.
 */
export function fillForm(event, object) {
    window.populateFormFromAjax(replaceUrlPlaceholder(window.get_single_item, object.data('id')), object.data('form'));
}

/**
 * يبني بيانات النموذج ويضيف ملفات Dropzone المنتظرة، ويطبق شرط الملفات إن وجد.
 * @param {jQuery} form النموذج الجاري إرساله.
 * @returns {FormData|null} البيانات الجاهزة أو null عند غياب ملف مطلوب؛ يميز حقل الرفع الناقص.
 */
export function collectFormData(form) {
    const data = new FormData(form[0]);
    if (!appendDropzoneFiles(data, form[0])) return null;
    return data;
}

/**
 * يوحد حفظ الإضافة والتعديل مع الاحتفاظ باختلاف الرابط وصفحة الجدول بعد النجاح.
 * @param {Event} event حدث إرسال النموذج.
 * @param {jQuery} form النموذج الحالي.
 * @param {boolean} update هل العملية تعديل لسجل موجود.
 * @returns {void} يرسل POST ويغلق ويصفر النموذج عند النجاح، أو يعرض خطأ الخادم.
 */
function saveRecord(event, form, update) {
    event.preventDefault();
    const data = collectFormData(form);
    if (!data) return;
    const url = update ? replaceUrlPlaceholder(window.update_url, data.get('id')) : window.store_url;
    // نتيجة حفظ التبويب السابق لا تمس النموذج أو المرفقات أو الجدول البديل.
    const table = window.pageTable;
    const dropzones = getDropzones(form[0]);
    window.ajax_exe({
        url, type: 'post', data, showLoading: true,
        /**
         * يطبق نجاح الإضافة أو التعديل على نفس نموذج ومثيل جدول الإرسال.
         * @returns {void} يطلب إغلاق النافذة ويعيد التحميل ويصفر النموذج وملفاته إذا لم يستبدل التبويب.
         */
        success: () => {
            if (form[0].isConnected === false || window.pageTable !== table) return;
            closeModal(form.closest('.modal')[0]).catch(error => window.notifyError(error.message));
            if (update) table.ajax.reload(null, false);
            else table.ajax.reload();
            window.notifySuccess(update ? window.updateSuccess : window.addSuccess);
            form[0].reset();
            form.validate().resetForm();
            for (const dropzone of dropzones) dropzone.removeAllFiles();
        },
        /**
         * يعرض رسالة الخادم للعملية الفاشلة أو وصف حالة HTTP.
         * @param {jqXHR} xhr رد الحفظ أو الحذف أو التحديث الفاشل.
         * @returns {void} يستدعي notifyError دون تعديل السجل في الواجهة.
         */
        error: xhr => window.notifyError(xhr.responseJSON?.message ?? xhr.statusText),
    });
}

/**
 * يستقبل إرسال نموذج الإضافة من data-call_function="create_item".
 * @param {Event} event حدث الإرسال.
 * @param {jQuery} form نموذج الإضافة.
 * @returns {void} يفوض التحقق والإرسال وإعادة التحميل إلى مسار الحفظ المشترك.
 */
export function createItem(event, form) { saveRecord(event, form, false); }

/**
 * يستقبل إرسال نموذج التعديل من data-call_function="update_item".
 * @param {Event} event حدث الإرسال.
 * @param {jQuery} form نموذج التعديل الذي يحتوي على id.
 * @returns {void} يحفظ السجل مع الاحتفاظ بصفحة الجدول الحالية بعد النجاح.
 */
export function updateItem(event, form) { saveRecord(event, form, true); }

/**
 * يحذف السجل بعد المرور بتأكيد deleteButton ويحافظ على صفحة الجدول الحالية.
 * @param {string} link عنوان حذف السجل.
 * @returns {void} يرسل POST ويعرض نجاح الحذف أو رسالة الخطأ.
 */
export function deleteItem(link) {
    window.ajax_exe({ url: link, type: 'post', showLoading: true,
        /**
         * يحدث الصفوف بعد نجاح حذف السجل ويحافظ على صفحة الجدول.
         * @returns {void} يعيد تحميل pageTable ويعرض deleteSuccess.
         */
        success: () => { window.pageTable.ajax.reload(null, false); window.notifySuccess(window.deleteSuccess); },
        /**
         * يعرض رسالة الخادم للعملية الفاشلة أو وصف حالة HTTP.
         * @param {jqXHR} xhr رد الحفظ أو الحذف أو التحديث الفاشل.
         * @returns {void} يستدعي notifyError دون تعديل السجل في الواجهة.
         */
        error: xhr => window.notifyError(xhr.responseJSON?.message ?? xhr.statusText),
    });
}

/**
 * يحفظ تغييرا مباشرا مثل switch من البيانات الممررة، دون الاعتماد على event أو نموذج عام.
 * @param {string} link عنوان تحديث القيمة.
 * @param {object} values القيم المرسلة، عادة {id,value}.
 * @returns {void} يرسل FormData عبر POST ويحدث صفوف الجدول بعد النجاح.
 */
export function updateValue(link, values) {
    const data = new FormData();
    for (const [key, value] of Object.entries(values)) data.append(key, value);
    window.ajax_exe({ url: link, type: 'post', data, showLoading: true,
        /**
         * يحدث الصفوف بعد نجاح حفظ القيمة المباشرة.
         * @returns {void} يعيد تحميل pageTable مع حفظ الصفحة ويعرض updateSuccess.
         */
        success: () => { window.pageTable.ajax.reload(null, false); window.notifySuccess(window.updateSuccess); },
        /**
         * يعرض رسالة الخادم للعملية الفاشلة أو وصف حالة HTTP.
         * @param {jqXHR} xhr رد الحفظ أو الحذف أو التحديث الفاشل.
         * @returns {void} يستدعي notifyError دون تعديل السجل في الواجهة.
         */
        error: xhr => window.notifyError(xhr.responseJSON?.message ?? xhr.statusText),
    });
}
