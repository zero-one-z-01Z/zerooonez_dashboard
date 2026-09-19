/** دورة حياة موحدة للمحتوى الثابت والمحتوى الذي يستبدل عبر AJAX. */

const cleanups = new WeakMap();

function event(name, detail) {
    if (typeof CustomEvent === 'function') return new CustomEvent(name, { detail, bubbles: false });
    return { type: name, detail };
}

/**
 * يسجل دالة تنظيف مرتبطة بجذر محدد حتى لا تبقى مكونات أو listeners بعد استبداله.
 * @param {Element|Document} root جذر المحتوى.
 * @param {Function} cleanup دالة التنظيف.
 * @returns {Function} نفس الدالة لتسهيل استخدامها في الاختبارات.
 */
export function registerCleanup(root, cleanup) {
    if (!root || typeof cleanup !== 'function') return cleanup;
    const entries = cleanups.get(root) || new Set();
    entries.add(cleanup);
    cleanups.set(root, entries);
    return cleanup;
}

/** ينفذ عمليات التنظيف المسجلة ويعلن أن المحتوى سيزال. */
export function unmountContent(root, detail = {}) {
    if (!root) return;
    if (typeof root.dispatchEvent === 'function') root.dispatchEvent(event('dashboard:content-unmounted', { root, ...detail }));

    const entries = cleanups.get(root);
    if (!entries) return;
    cleanups.delete(root);
    for (const cleanup of entries) {
        try { cleanup(); } catch (error) { console.error('Dashboard cleanup failed', error); }
    }
}

/** يعلن اكتمال تركيب المحتوى بعد تحميل أصوله وتهيئة حقوله. */
export function mountContent(root, detail = {}) {
    if (!root || typeof root.dispatchEvent !== 'function') return;
    root.dispatchEvent(event('dashboard:content-mounted', { root, ...detail }));
}

/** ينظف ثم يهيئ جذرًا في خطوة واحدة عند إعادة استخدام نفس الحاوية. */
export function remountContent(root, detail = {}) {
    unmountContent(root, detail);
    mountContent(root, detail);
}

