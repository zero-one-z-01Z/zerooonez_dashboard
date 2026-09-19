/** عرض أخطاء الخادم والتبديل بين تبويبات النموذج. */

/**
 * يعرض أخطاء Laravel داخل النموذج، ويحوّل أسماء المصفوفات إلى صيغة HTML ثم يكشف أول حقل خاطئ.
 * @param {Object} errors أخطاء Laravel بأسماء الحقول.
 * @param {string|jQuery} form النموذج المقصود.
 * @param {Object} [options={}] يمكن تمرير summernotes لربط المحررات.
 * @returns {void} يعيد رسم أخطاء التحقق ويكشف أو يمرر إلى أول حقل معروف؛ يسجل الحقول الغائبة.
 */
export function handleValidationErrors(errors, form, options = {}) {
    const $form = $(form);

    if (!$form.length) {
        console.error(`Form ${form} not found`);
        return;
    }

    const validator = $form.validate();
    if (!validator) {
        console.error('Validator not found');
        return;
    }

    validator.resetForm();
    let firstErrorElement = null;

    Object.entries(errors).forEach(([key, value]) => {

        const fieldName = key.includes('.')
            ? key.split('.').reduce((acc, part) => acc + (acc ? `[${part}]` : part), '')
            : key;

        const $element = $form.find(`[name="${fieldName}"]`);

        if ($element.length) {

            validator.showErrors({
                [fieldName]: Array.isArray(value) ? value[0] : value
            });

            if (!firstErrorElement) {
                firstErrorElement = $element;
            }
        } else if (options.summernotes?.[fieldName]) {

            const $editor = $(options.summernotes[fieldName]);
            const $editorContainer = $editor.next();

            $editorContainer.addClass('border-danger');

            $editorContainer.next('.invalid-feedback').remove();

            $editorContainer.after(`
                <div class="invalid-feedback d-block">
                ${Array.isArray(value) ? value[0] : value}
                </div>
            `);

            if (!firstErrorElement) {
                firstErrorElement = $editor;
            }
        } else {
            console.error(`Element ${fieldName} not found in form`);
        }
    });

    if (!firstErrorElement) return;

    const $errorPane = firstErrorElement.closest('.tab-pane');
    const $currentPane = $form.find('.tab-pane.active');

    if ($errorPane.length) {

        if (!$errorPane.hasClass('active')) {
            const tabId = $errorPane.attr('id');
            $(`[href="#${tabId}"]`).tab('show');

            setTimeout(() => scrollToElement(firstErrorElement), 150);
        } else {
            scrollToElement(firstErrorElement);
        }
    } else {

        scrollToElement(firstErrorElement);
    }
}

/**
 * يمرر الصفحة حتى يتمركز الحقل المرئي مع منع الإزاحة السالبة.
 * @param {jQuery} $element الحقل الذي سيجري التمرير إليه.
 * @returns {void} يحرك تمرير html/body خلال 400 مللي ثانية، أو يتوقف عند غياب الحقل.
 */
export const scrollToElement = ($element) => {
    if (!$element.length) return;

    const elementTop = $element.offset().top;

    const elementHeight = $element.outerHeight();

    const windowHeight = $(window).height();

    const scrollTo = elementTop - (windowHeight / 2) + (elementHeight / 2);

    $('html, body').animate({
        scrollTop: Math.max(0, scrollTo) // Prevent negative scroll positions
    }, {
        duration: 400,
        easing: 'swing'
    });
};

/**
 * يفتح التبويب الذي يحتوي الحقل الخاطئ ثم ينتظر انتقال Bootstrap قبل التمرير.
 * @param {jQuery} $element الحقل الذي يحتوي الخطأ.
 * @returns {boolean} هل تم تبديل التبويب؟
 */
export const switchToTabWithError = ($element) => {
    const $errorPane = $element.closest('.tab-pane');
    if ($errorPane.length && !$errorPane.hasClass('active')) {
        const tabId = $errorPane.attr('id');
        $(`[href="#${tabId}"]`).tab('show');

        setTimeout(() => scrollToElement($element), 150);
        return true;
    }
    return false;
};
