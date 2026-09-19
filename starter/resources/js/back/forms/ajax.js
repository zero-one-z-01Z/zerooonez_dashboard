/** إعداد CSRF وإرسال طلبات النماذج ومعالجة فشلها. */

import { handleValidationErrors } from './validation-errors.js';
import { showToast, togglePageLoader } from './feedback.js';

/**
 * يضبط ترويسة CSRF لجميع طلبات jQuery من وسم الصفحة قبل إرسال أي نموذج.
 * @returns {void} يضبط إعداد jQuery العام؛ تعتمد الطلبات اللاحقة عليه.
 */
export function configureAjax() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
}

/**
 * يرسل بيانات النموذج ويعرض أخطاء Laravel 422 والصلاحيات 403، مع دعم استبدال معالج الخطأ.
 * @param {Object} props إعدادات url وtype وdata وsuccess وerror وform وshowLoading.
 * @returns {void} يجدول الإرسال بعد 50 مللي ثانية؛ لا يعيد jqXHR.
 */
export function ajax_exe(props) {
    let request_url = props.url;
    let request_type = props.type;
    let data = props.data;
    let successFunction = props.success;
    let errorFunction = props.error;
    let showLoading = props.showLoading;
    let other = {};
    if (props.summernotes) {
        other.summernotes = props.summernotes;
    }

    let form = '';
    if (typeof create_form !== 'undefined') {
        form = create_form;
    }
    else if (typeof edit_form !== 'undefined') {
        form = edit_form
    } else if (props.form) {
        form = props.form
    }

    let ajaxSettings = {
        url: request_url,
        type: request_type,
        data: data,
        processData: false,
        contentType: false,
        success: successFunction,
        /**
         * يوجه أخطاء 422 إلى النموذج، و403 إلى تنبيه الصلاحية، وبقية الفشل إلى الرسالة العامة.
         * @param {jqXHR} xhr رد jQuery بما فيه الحالة وJSON الخطأ.
         * @param {string} status وصف حالة الطلب.
         * @param {string|Error} error الخطأ الذي مرره jQuery.
         * @returns {void} يعرض التنبيه والأخطاء؛ يتجاوزه props.error إذا قدمه المستدعي.
         */
        error: function (xhr, status, error) {
            if (xhr.status === 422) {

                var errors = xhr.responseJSON.errors;
                if (form.includes(',')) {
                    form.split(',').forEach(function (element) {
                        handleValidationErrors(errors, element, other);
                    });
                }
                else {
                    handleValidationErrors(errors, form, other);
                }

                showToast('warning', 'الرجاء التأكد من البيانات المدخلة');
            }
            else if (xhr.status === 403) {
                showToast('error', 'ليس لديك الصلاحية لهذه العملية');
                console.error('Error occurred:', status, error);
            }
            else {
                showToast('error');
                console.error('Error occurred:', status, error);
            }

        },
    }

    if (showLoading) {
        /**
         * يظهر مؤشر الصفحة مباشرة قبل بدء طلب يحمل showLoading.
         * @returns {void} يغير ظهور المؤشر فقط.
         */
        ajaxSettings.beforeSend = function () {
            togglePageLoader('on');
        }

        /**
         * يخفي المؤشر عند اكتمال طلب يحمل showLoading سواء نجح أم فشل.
         * @returns {void} لا يغير نتيجة الطلب أو يعيد إرساله.
         */
        ajaxSettings.complete = function () {
            togglePageLoader('off');
        }
    }

    if (errorFunction) {
        ajaxSettings.error = errorFunction;
    }
    if (props.processData) {
        ajaxSettings.processData = props.processData;
    }
    if (props.contentType) {
        ajaxSettings.contentType = props.contentType;
    }

    setTimeout(() => {
        $.ajax(ajaxSettings);
    }, 50);
}
