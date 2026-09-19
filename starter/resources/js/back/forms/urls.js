/** تعبئة مسارات السجلات وفحص أصل الرابط. */

/**
 * يستبدل #placeholder# في المسار ويحدّث روابط ونماذج الصفحة المطابقة للمسار القديم.
 * @param {string} url المسار قبل الاستبدال.
 * @param {string|number} value المعرف البديل.
 * @returns {string} المسار الجديد.
 */
export function replaceUrlPlaceholder(url, value) {

    var escapedUrl = $.escapeSelector(url);

    var newUrl = url.replace('#placeholder#', value);

    $('a[href="' + escapedUrl + '"]').attr('href', newUrl);
    $('form[action="' + escapedUrl + '"]').attr('action', newUrl);

    return newUrl;
}

/**
 * يبني مسار السجل باستبدال #placeholder# دون تغيير عناصر الصفحة.
 * @param {string} url المسار القالب.
 * @param {string|number} value قيمة الاستبدال.
 * @returns {string} المسار الجديد.
 */
export function replaceUrlPlaceholder_v2(url, value) {
    return url.replace("#placeholder#", value);
}

/**
 * يفحص أن عنوان URL صالح وينتمي إلى نفس أصل الصفحة.
 * @param {string} url عنوان نسبي أو مطلق.
 * @returns {boolean} صلاحية الأصل، ولا يمثل هذا فحص صلاحيات خادم.
 */
export function isValidUrl(url) {
    try {
        const parsedUrl = new URL(url, window.location.origin);

        return parsedUrl.origin === window.location.origin;
    } catch (e) {
        return false;
    }
}
