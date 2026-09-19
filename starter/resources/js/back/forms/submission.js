/** معالجات إرسال النماذج التي تستدعيها خصائص Blade. */

import { ajax_exe } from './ajax.js';
import { closeModal } from './modal-lifecycle.js';
import { appendDropzoneFiles, getDropzones } from './dropzones.js';

/**
 * يرسل تغيير المفتاح المخصص من القيم التي مررها معالج change مباشرة.
 * @param {string} link مسار التحديث.
 * @param {Object} data بيانات التغيير، عادة {value,id}.
 * @returns {void} يرسل POST ويعرض نتيجة العملية؛ لا يحتاج حدثا أو نموذجا عاما على window.
 */
export function update_switch_val(link,data) {

    const formData = new FormData();
    for (const [key, value] of Object.entries(data)) formData.append(key, value);
    ajax_exe({
        url: link,
        type: 'post',
        data:formData,
        showLoading: true,
        /**
         * يعرض إشعار اكتمال تغيير المفتاح المخصص.
         * @param {object} response استجابة النجاح؛ محتواها غير مستخدم في هذا الإشعار.
         * @returns {void} يستدعي notifySuccess بالنص العام updateSuccess.
         */
        success: function (response) {

            notifySuccess(updateSuccess);

        },
        /**
         * يسجل فشل الحفظ ويعرض message التي أعادها Laravel.
         * @param {jqXHR} xhr رد jQuery بما فيه الحالة وJSON الخطأ.
         * @param {string} status وصف حالة الطلب.
         * @param {string|Error} error الخطأ الذي مرره jQuery.
         * @returns {void} يعرض إشعار الخطأ ولا يعيد الإرسال.
         */
        error: function (xhr, status, error) {
            console.error(xhr);
            notifyError(xhr.responseJSON.message);
        }

    });
}

/**
 * يرسل نموذج CRUD مع ملفاته، ثم يحدث واجهته الأصلية عند النجاح إذا لم تستبدل أثناء الطلب.
 * @param {Event} event حدث الإرسال الذي يُمنع سلوكه الافتراضي.
 * @param {jQuery} $obj النموذج المحتوي على action وmethod.
 * @returns {void} يبدأ طلب الحفظ ولا يعيد jqXHR؛ نجاح الطلب القديم لا يصفر نموذجًا بديلًا أو يعيد تحميل جدوله.
 */
export function customFunction(event, $obj) {
    event.preventDefault();
    const submittedForm = $obj[0];
    const submittedTable = window.pageTable;
    const submittedDropzones = getDropzones(submittedForm);
    var formData = new FormData(submittedForm);
    var formUrl = $obj.attr('action');
    var formMethod = $obj.attr('method');
    if (!appendDropzoneFiles(formData, submittedForm)) return;
    ajax_exe({
        url: formUrl,
        type: formMethod,
        data: formData,
        showLoading: true,
        /**
         * يكمل آثار الحفظ على النموذج والجدول الملتقطين عند الإرسال فقط.
         * @param {object} response استجابة النجاح؛ لا تعتمد عملية تصفير النموذج على حقولها.
         * @returns {void} يطلب إغلاق النافذة ويحدث الجدول ويصفر النموذج وDropzone؛ يتجاهل الواجهة المستبدلة.
         */
        success: function (response) {
            if (!submittedForm.isConnected || window.pageTable !== submittedTable) return;
            var $form = $obj;
            closeModal($form.closest('.modal')[0]).catch(error => window.notifyError(error.message));
            submittedTable?.ajax?.reload(null, false);
            notifySuccess(success);
            $form[0].reset();
            $form.validate().resetForm();
            for (const dropzone of submittedDropzones) dropzone.removeAllFiles();

        },
        /**
         * يسجل فشل الحفظ ويعرض message التي أعادها Laravel.
         * @param {jqXHR} xhr رد jQuery بما فيه الحالة وJSON الخطأ.
         * @param {string} status وصف حالة الطلب.
         * @param {string|Error} error الخطأ الذي مرره jQuery.
         * @returns {void} يعرض إشعار الخطأ ولا يعيد الإرسال.
         */
        error: function (xhr, status, error) {
            console.error(xhr);
            notifyError(xhr.responseJSON.message);
        }

    });
}

/**
 * يرسل نموذج تغيير كلمة المرور ويغلق النافذة ويمسح الحقول عند النجاح.
 * @param {Event} event حدث إرسال النموذج.
 * @param {jQuery} $obj نموذج كلمة المرور.
 * @returns {void} يرسل بيانات النموذج ثم يغلق نافذته ويصفر قيمها عند النجاح.
 */
export function updatePassword(event, $obj) {
    event.preventDefault();
    var formData = new FormData($obj[0]);
    var formUrl = $obj.attr('action');
    var formMethod = $obj.attr('method');
    ajax_exe({
        url: formUrl,
        type: formMethod,
        data: formData,
        showLoading: true,
        /**
         * يغلق نافذة تغيير كلمة المرور ويصفر حقولها بعد نجاح الخادم.
         * @param {object} response استجابة النجاح؛ محتواها غير مستخدم هنا.
         * @returns {void} يعرض الإشعار ويصفر النموذج؛ لا يحدث أي جدول.
         */
        success: function (response) {
            var $form = $obj;
            $form.closest('.modal').modal('hide');
            notifySuccess(success);
            $form[0].reset();

        },
        /**
         * يسجل فشل الحفظ ويعرض message التي أعادها Laravel.
         * @param {jqXHR} xhr رد jQuery بما فيه الحالة وJSON الخطأ.
         * @param {string} status وصف حالة الطلب.
         * @param {string|Error} error الخطأ الذي مرره jQuery.
         * @returns {void} يعرض إشعار الخطأ ولا يعيد الإرسال.
         */
        error: function (xhr, status, error) {
            console.error(xhr);
            notifyError(xhr.responseJSON.message);
        }

    });
}
