/** مساعدات قوالب روابط الجدول؛ نسخة تحدث DOM ونسخة تبني النص فقط للتوافق مع Blade. */

/**
 * يستبدل موضع المعرف في رابط Laravel مرة واحدة دون تعديل الصفحة.
 * @param {string} url الرابط الذي قد يحتوي على #placeholder#.
 * @param {string|number} value قيمة المعرف.
 * @returns {string} الرابط بعد الاستبدال، مع حفظ بقية الرابط كما هو.
 */
export function replaceUrlPlaceholderV2(url, value) {
    return url.replace('#placeholder#', value);
}

/**
 * يستبدل المعرف في الرابط ويحدث الروابط والنماذج التي تحمل نفس القالب.
 * @param {string} url قالب الرابط القديم.
 * @param {string|number} value المعرف المطلوب.
 * @returns {string} الرابط النهائي؛ يحدث href وaction للعناصر المطابقة.
 */
export function replaceUrlPlaceholder(url, value) {
    const result = replaceUrlPlaceholderV2(url, value);
    const escaped = $.escapeSelector(url);
    $('a[href="' + escaped + '"]').attr('href', result);
    $('form[action="' + escaped + '"]').attr('action', result);
    return result;
}
