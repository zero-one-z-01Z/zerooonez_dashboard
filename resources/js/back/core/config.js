/** قراءة بيانات Blade الآمنة من عناصر JSON بدلا من كتابة JavaScript داخل القوالب. */

/**
 * يقرأ عنصر application/json ويعيد قيمة افتراضية عند غيابه أو فساد محتواه.
 * @param {string|Element|null} source معرف العنصر أو العنصر نفسه.
 * @param {*} fallback القيمة المستخدمة عند تعذر القراءة.
 * @returns {*} البيانات المفكوكة أو fallback.
 */
export function readJsonConfig(source, fallback = {}) {
    if (typeof document === 'undefined') return fallback;
    const element = typeof source === 'string'
        ? (typeof document.getElementById === 'function' ? document.getElementById(source) : null)
        : source;
    if (!element) return fallback;

    try {
        return JSON.parse(element.textContent || '');
    } catch (error) {
        console.error(`Invalid dashboard JSON config: ${element.id || 'anonymous'}`, error);
        return fallback;
    }
}

/** يعيد إعدادات تشغيل الداشبورد المشتركة التي يرسمها layout مرة واحدة. */
export function dashboardRuntimeConfig() {
    return readJsonConfig('dashboard-runtime-config', {});
}
