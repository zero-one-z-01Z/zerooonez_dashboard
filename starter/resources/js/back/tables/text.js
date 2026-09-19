/** تهريب النصوص وعرض اختصار قابل للتوسيع؛ وضع html_text يحتفظ بعقد HTML المقصود. */

/**
 * يحول النص إلى HTML آمن للاستعمال داخل النصوص أو خصائص العناصر.
 * @param {*} value قيمة نصية قادمة من السجل أو الفلتر.
 * @returns {string} نص مهرب، دون تعديل الصفحة.
 */
export function escapeHtml(value) {
    const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    return String(value ?? '').replace(/[&<>"']/g, character => entities[character]);
}

/**
 * يبني معاينة مختصرة مع زر توسيع؛ يحتفظ بمحتوى محرر HTML في وضع html فقط.
 * @param {*} content محتوى الحقل.
 * @param {string} idPrefix بادئة المعرف الفريد لربط الزر بالنص.
 * @param {boolean} isHtml هل الحقل من نوع html_text.
 * @param {number} limit طول المعاينة بالأحرف.
 * @returns {string} HTML العرض؛ لا يربط أحداثا أو يعدل DOM.
 */
export function renderToggleText(content, idPrefix, isHtml = false, limit = 100) {
    const value = String(content ?? '');
    const plain = isHtml ? $('<div>').html(value).text() : value;
    const full = isHtml ? value : escapeHtml(value);
    if (plain.length <= limit) return isHtml ? full : `<span>${full}</span>`;
    const id = `${idPrefix}-${Math.random().toString(36).slice(2, 11)}`;
    return `<span class="truncated" id="${id}-short">${escapeHtml(plain.slice(0, limit))}...</span>
        <span class="full-text d-none" id="${id}-full">${full}</span>
        <a href="javascript:void(0);" class="toggle-text ms-1" data-id="${id}">${escapeHtml(window.seeMoreText)}</a>`;
}

/**
 * يربط زر المزيد/أقل مرة واحدة باستخدام تفويض الأحداث ليعمل بعد إعادة رسم الجدول.
 * @returns {void} يحدث إظهار النص واسم الزر فقط عند النقر.
 */
export function bindTextToggles() {
    $(document).off('click.dashboardText', '.toggle-text').on('click.dashboardText', '.toggle-text', function () {
        const id = $(this).data('id');
        const short = $(`#${id}-short`);
        const full = $(`#${id}-full`);
        const expanded = !full.hasClass('d-none');
        full.toggleClass('d-none', expanded);
        short.toggleClass('d-none', !expanded);
        $(this).text(expanded ? window.seeMoreText : window.seeLessText);
    });
}
