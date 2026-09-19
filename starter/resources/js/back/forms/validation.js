/** توصيل التحقق للنماذج والمحررات وتنسيق الحقول. */

import { scrollToElement, switchToTabWithError } from './validation-errors.js';

/**
 * يربط قواعد التحقق بالنموذج ويعرض الأخطاء بجوار Select2 والملفات ويكشف تبويب أول خطأ.
 * @param {string|jQuery} form محدد النموذج.
 * @param {Object} rules قواعد jQuery Validate بأسماء الحقول.
 * @param {Object} messages رسائل خاصة بالحقول.
 * @param {?Function} [submithandler=null] المعالج الاختياري بعد نجاح التحقق.
 * @returns {void} يركب إعدادات jQuery Validate؛ المعالج الاختياري مسؤول عن إجراء الإرسال الفعلي.
 */
export function defineFormValidation(form, rules, messages, submithandler = null) {
    let object = {
        ignore: "",
        rules: rules,
        messages: messages,
        errorClass: 'is-invalid',
        validClass: 'is-valid',
        errorElement: 'div',
        /**
         * يميز الحقل الخاطئ وواجهة Select2 أو رفع الملف ورابط تبويبه.
         * @param {HTMLElement} element الحقل الذي أخفق التحقق منه.
         * @returns {void} يعدل أصناف CSS الخاصة بالحقل والتبويب.
         */
        highlight: function (element) {

            var $element = $(element);
            if ($element.hasClass('select2-hidden-accessible')) {
                $element.next('.select2-container').find('.select2-selection').addClass('is-invalid').removeClass('is-valid');
            } else {
                $element.addClass('is-invalid').removeClass('is-valid');
            }

            if ($element.attr('type') == 'file' && $element.parent().hasClass('drag-upload-btn')) {
                let parent = $element.parent();
                parent.addClass('border-danger');
            }

            let tabPane = $(element).closest('.tab-pane');
            if (tabPane.length) {
                let tabId = tabPane.attr('id');
                let tabLink = $('[href=\'#' + tabId + '\']');

                tabLink.addClass('text-danger');

            }
        },
        /**
         * يستبدل حالة الخطأ بحالة النجاح على الحقل وواجهته.
         * @param {HTMLElement} element الحقل الذي اجتاز التحقق.
         * @returns {void} يزيل علامة الخطأ من رابط التبويب ويحدث الأصناف.
         */
        unhighlight: function (element) {

            var $element = $(element);
            if ($element.hasClass('select2-hidden-accessible')) {
                $element.next('.select2-container').find('.select2-selection').addClass('is-valid').removeClass('is-invalid');
            } else {
                $element.removeClass('is-invalid').addClass('is-valid');
            }

            if ($element.attr('type') == 'file' && $element.parent().hasClass('drag-upload-btn')) {
                let parent = $element.parent();
                parent.removeClass('border-danger').addClass('border-success');
            }

            let tabPane = $element.closest('.tab-pane');
            if (tabPane.length) {
                let tabId = tabPane.attr('id');
                let tabLink = $('[href=\'#' + tabId + '\']');
                tabLink.removeClass('text-danger');
            }
        },
        /**
         * يضع رسالة التحقق بعد واجهة الحقل المرئية وليس داخل إضافة Select2.
         * @param {jQuery} error عنصر رسالة الخطأ الذي أنشأته الإضافة.
         * @param {jQuery} $element الحقل المعني بالرسالة.
         * @returns {void} يلحق عنصر الخطأ بعد الحقل أو حاوية رفعه.
         */
        errorPlacement: function (error, $element) {

            if ($element.hasClass('select2-hidden-accessible')) {
                error.addClass('invalid-feedback');
                error.insertAfter($element.next());
            }
            else if ($element.attr('type') == 'file' && $element.parent().hasClass('drag-upload-btn')) {
                let parent = $element.parent();
                error.addClass('invalid-feedback');
                error.insertAfter(parent);
            }
            else {
                error.addClass('invalid-feedback');
                error.insertAfter($element);

            }
        },
        /**
         * يكشف أول خطأ خارج التبويب الحالي عند فشل محاولة الإرسال.
         * @param {Event} event حدث فشل التحقق؛ لا يستخدم مباشرة داخل المعالج.
         * @param {object} validator مثيل التحقق الذي يحتوي errorList.
         * @returns {void} يفتح التبويب أو يمرر إلى الحقل إذا لم يوجد خطأ ظاهر في التبويب الحالي.
         */
        invalidHandler: function (event, validator) {
            const $currentPane = $(form).find('.tab-pane.active');
            const currentPaneHasErrors = $currentPane.find('.is-invalid:visible').length > 0;

            if (!currentPaneHasErrors) {
                const $firstErrorElement = $(validator.errorList[0].element);
                if (!switchToTabWithError($firstErrorElement)) {
                    scrollToElement($firstErrorElement);
                }
            }
        }
    };

    if (submithandler !== null) {
        object.submitHandler = submithandler;
    }
    $(form).validate(object);
}

/**
 * يتحقق من أن محررات Summernote ليست فارغة ويبرز أول موضع يحتاج إدخالًا.
 * @param {Array} summernotes محددات المحررات.
 * @param {*} errors وسيط قديم محفوظ للتوافق.
 * @returns {boolean} true إذا لم يوجد محرر فارغ.
 */
export function validateSummerNotes(summernotes, errors) {
    let validError = false;
    if (summernotes) {
        summernotes.forEach(element => {
            if ($(element).summernote('isEmpty')) {
                validError = true;
                $(element).summernote('focus');
                $(element).next().addClass('border-danger');
                if (!$(element).next().next().hasClass('invalid-feedback')) {
                    $(element).next().after('<div id="ar[name]-error" class="is-invalid invalid-feedback">هذا الحقل إلزامي</div>');
                }

                $('[href=\'#' + $('element').closest('.tab-pane').attr('id') + '\']');
                let offset = $(element).offset().top - ($(window).height() / 2) + ($(element).outerHeight() / 2);
                $('html, body').animate({
                    scrollTop: offset
                }, 500);
            }
            else {
                $(element).next().removeClass('border-danger');
                $(element).next().next('.invalid-feedback').remove();
            }
        });
    }

    return !validError;
}
