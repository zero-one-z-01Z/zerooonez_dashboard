/** انتظار نهاية انتقال نافذة Bootstrap قبل أن تستبدل تبويبات AJAX نماذجها. */

/**
 * ينتظر إغلاق نافذة Bootstrap حتى تنتهي الخلفية وحالة تمرير الصفحة قبل إزالة DOM.
 * @param {HTMLElement} element النافذة القديمة، سواء كانت ظاهرة أو أثناء الحركة.
 * @returns {Promise<void>} يرفض إذا منع كود آخر الإغلاق، حتى تبقى النماذج القديمة قابلة للاستخدام.
 */
export function closeModal(element) {
    if (!element) return Promise.resolve();
    const instance = window.bootstrap?.Modal.getInstance(element);
    if (!instance) return Promise.resolve();
    // Bootstrap 5 يبدأ حركة الخلفية قبل إضافة show/aria-modal إلى النافذة نفسها.
    const opening = instance._isShown === true || instance._isTransitioning === true;
    if (!opening && !element.classList.contains('show') && element.getAttribute('aria-modal') !== 'true') return Promise.resolve();
    return new Promise((resolve, reject) => {
        /**
         * ينهي انتظار إغلاق النافذة وينظف المؤقت والمستمعين في مساري النجاح والفشل.
         * @param {Error} [error] خطأ مهلة الإغلاق إن لم يصل hidden.
         * @returns {void} يحسم وعد closeModal؛ يحافظ على DOM عندما يفشل الإغلاق.
         */
        const finish = error => {
            clearTimeout(timeout);
            element.removeEventListener('hidden.bs.modal', onHidden);
            element.removeEventListener('shown.bs.modal', onShown);
            if (error) reject(error);
            else resolve();
        };
        /**
         * يعتبر حدث hidden.bs.modal دليل اكتمال إغلاق النافذة وخلفيتها.
         * @returns {void} يفوض تنظيف المستمعين وحسم الوعد إلى finish.
         */
        const onHidden = () => finish();
        /**
         * يعيد طلب hide بعد اكتمال حركة الفتح إذا تجاهله Bootstrap أثناء الانتقال.
         * @returns {void} يطلب إغلاق نفس مثيل النافذة الملتقط في بداية العملية.
         */
        const onShown = () => instance.hide();
        const timeout = setTimeout(() => finish(new Error('تعذّر إغلاق النموذج الحالي. أغلقه ثم أعد اختيار التبويب.')), 2500);
        element.addEventListener('hidden.bs.modal', onHidden);
        element.addEventListener('shown.bs.modal', onShown);
        instance.hide();
    });
}
