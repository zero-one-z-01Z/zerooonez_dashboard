/** رسائل العمليات ومؤشر تحميل الصفحة. */

/**
 * يعرض رسالة Bootstrap Toast بلون النوع المطلوب والنص الافتراضي عند عدم تمرير رسالة.
 * @param {string} [type="success"] نوع الرسالة.
 * @param {?string} [text=null] النص الذي يراه المستخدم.
 * @returns {void} يحدث ألوان ونص #Toast ويعرضه بواسطة Bootstrap.
 */
export function showToast(type = 'success', text = null) {
    const toastElement = $("#Toast");

    const toastClasses = 'bg-success bg-danger bg-warning bg-info bg-secondary text-fixed-white text-fixed-dark';
    toastElement.removeClass(toastClasses);

    const toastTypes = {
        success: { class: 'bg-success text-fixed-white', defaultText: 'تمت العملية بنجاح' },
        error: { class: 'bg-danger text-fixed-white', defaultText: 'حدث خطأ' },
        warning: { class: 'bg-warning text-fixed-dark', defaultText: 'تحذير' },
        info: { class: 'bg-info text-fixed-white', defaultText: 'معلومات' },
        default: { class: 'bg-secondary text-fixed-white', defaultText: 'تنبيه عام' }
    };

    const toastConfig = toastTypes[type] || toastTypes.default;
    toastElement.addClass(toastConfig.class);

    toastElement.find('.toast-body').text(text || toastConfig.defaultText);

    const toast = new bootstrap.Toast(toastElement[0]);
    toast.show();
}

/**
 * يظهر مؤشر تحميل الصفحة أو يخفيه أو يعكس حالته.
 * @param {string} status استخدم on أو off، وأي قيمة أخرى تعكس الحالة.
 * @param {string} [opacity="100%"] عتامة المؤشر عند إظهاره.
 * @returns {void} يبدأ حركة إظهار أو إخفاء #global-loader؛ لا يمثل عدادًا عامًا لكل الطلبات.
 */
export function togglePageLoader(status, opacity = '100%') {

    if (status == 'on') {
        $("#global-loader").css('opacity', opacity);
        $("#global-loader").fadeIn("slow");
    }
    else if (status == 'off') {
        $("#global-loader").fadeOut("slow");
    }
    else {
        $("#global-loader").fadeToggle("slow");
    }
}
