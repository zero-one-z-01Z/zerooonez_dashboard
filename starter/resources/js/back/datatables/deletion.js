/** تأكيد الحذف وتجميع المعرفات المحددة وربط أدوات التحديد بالتفويض. */

/**
 * يعرض تأكيد الحذف ثم ينفذ callback بالعنوان المحدد إذا وافق المستخدم.
 * @param {Function} callback دالة الحذف الفردي أو الجماعي.
 * @param {string} url رابط الحذف الفردي الاختياري.
 * @returns {Promise} وعد نافذة التأكيد؛ لا يرسل الطلب عند الإلغاء.
 */
export function deleteButton(callback, url) {
    return Swal.fire({
        title: window.areYouSure, text: window.cantReturnBack, icon: 'warning', showCancelButton: true,
        confirmButtonText: window.yesDelete, cancelButtonText: window.cancelText,
        customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-label-secondary' }, buttonsStyling: false,
    }).then(result => { if ((result.isConfirmed ?? result.value) && typeof callback === 'function') callback(url); });
}

/**
 * يجمع المعرفات المحددة ويرسلها بعقد ids[] الحالي بعد استدعائه من تأكيد الحذف.
 * @returns {void} يحدث الجدول ويعرض النتيجة؛ يبلغ عن غياب التحديد دون طلب شبكة.
 */
export function deleteAll() {
    const ids = $('.sub_check:checked').map((index, element) => $(element).attr('data-id')).get();
    if (!ids.length) { window.notifyError(window.mustSelectItem); return; }
    const data = new FormData();
    data.append('_token', $('meta[name="csrf-token"]').attr('content'));
    for (const id of ids) data.append('ids[]', id);
    window.ajax_exe({
        url: window.delete_all_url, type: 'post', data, showLoading: true,
        /**
         * يعيد تحميل الجدول ويعرض نتيجة الحذف الجماعي الناجح.
         * @returns {void} يعرض نافذة SweetAlert الخاصة بالنجاح.
         */
        success: () => {
            window.pageTable.ajax.reload();
            Swal.fire({ icon: 'success', title: window.deleteSuccess, text: window.itemDeletedSuccess,
                confirmButtonText: window.okText, customClass: { confirmButton: 'btn btn-success waves-effect waves-light' } });
        },
        /**
         * يعرض رسالة فشل الحذف الجماعي أو حالة HTTP عند غياب message.
         * @param {jqXHR} xhr رد الطلب الفاشل.
         * @returns {void} يستدعي showToast بنوع error.
         */
        error: xhr => window.showToast('error', xhr.responseJSON?.message ?? xhr.statusText),
    });
}

/**
 * يربط تحديد الكل وزر الحذف الجماعي بالتفويض ليعمل بعد تحميل أي تبويب.
 * @returns {void} يستبدل مستمعي هذه الوحدة فقط، ويترك أحداث الصفحة الأخرى كما هي.
 */
export function bindRowSelection() {
    $(document).off('click.dashboardSelection', '#master-check').on('click.dashboardSelection', '#master-check', function () {
        $('.sub_check').prop('checked', $(this).is(':checked'));
    });
    $(document).off('click.dashboardSelection', '#multiDeleteBtn').on('click.dashboardSelection', '#multiDeleteBtn', () => deleteButton(deleteAll));
}
