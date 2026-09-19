import { cancelPendingFormPopulation } from '../forms/population.js';

import { closeModal } from '../forms/modal-lifecycle.js';
import { destroyDropzones } from '../forms/dropzones.js';
import { destroyMaps } from '../forms/maps.js';
import { destroyEditors } from '../forms/editors.js';
import { unmountContent } from '../core/lifecycle.js';

/**
 * يغلق نوافذ التبويب السابق قبل الاستبدال؛ لا يمس نوافذ صفحة التفاصيل الأخرى.
 * @param {HTMLElement} host مضيف النماذج الخاصة بالجدول.
 * @returns {Promise<void>} ينتظر اكتمال إغلاق جميع النوافذ الموجودة فيه.
 */
export async function closeTabModals(host) {
    await Promise.all([...host.querySelectorAll('.modal')].map(closeModal));
}

/**
 * يحرر أدوات الحقول والمرفقات والتحقق وBootstrap قبل تركيب نماذج التبويب الجديد.
 * @param {HTMLElement} host المضيف الذي ستستبدل محتوياته؛ الإزالة تتم بعد هذه الدالة.
 * @returns {void} يدمر أدوات النماذج داخل المضيف فقط، ولا يحذف عناصر المضيف بنفسه.
 */
export function disposeTabModals(host) {
    unmountContent(host, { reason: 'tab-replace' });
    cancelPendingFormPopulation(host);
    destroyDropzones(host);
    destroyMaps(host);
    destroyEditors(host);
    $(host).find('.select2-hidden-accessible').each(function () {
        $(this).off('.dashboardChild');
        if ($(this).data('select2')) $(this).select2('destroy');
    });
    $(host).find('form').each(function () { $(this).data('validator')?.destroy(); });
    // استدع closeTabModals أولًا: dispose وحده لا ينتظر اختفاء خلفية Bootstrap.
    for (const element of host.querySelectorAll('.modal')) window.bootstrap?.Modal.getInstance(element)?.dispose();
}

/**
 * يتحقق من عقد استجابة التبويب ويجهز HTML الذي رسمه Blade؛ لا يعيد تنفيذ سكربتات داخلية.
 * @param {object} result JSON القادم من get_data.
 * @returns {DocumentFragment} النماذج جاهزة للتركيب، أو خطأ واضح عند استجابة قديمة أو ناقصة.
 * @throws {Error} إذا غاب status الصحيح أو show_list أو fragments.modals؛ لا تعدل الصفحة عند ذلك.
 */
export function prepareTabModals(result) {
    if (result.status !== 'Success' || !Array.isArray(result.data?.show_list) || typeof result.fragments?.modals !== 'string') {
        throw new Error('استجابة التبويب لا تحتوي على النماذج المطلوبة. حدّث الصفحة وحاول مرة أخرى.');
    }
    const template = document.createElement('template');
    // المصدر هو القالب المرسوم على الخادم. الأحداث موجودة في الوحدات المفوضة، لا في سكربت جديد لكل تبويب.
    template.innerHTML = result.fragments.modals;
    for (const script of template.content.querySelectorAll('script')) script.remove();
    return template.content;
}
/**
 * دورة HTML النوافذ داخل #dashboard-item-table-modals فقط.
 * الترتيب المقصود: prepare للتحقق، ثم close للانتظار، ثم dispose قبل replaceChildren.
 * لا تستخدم all-modals مباشرة كـ fragment لأنه غلاف Blade يدفع المحتوى إلى stack.
 */
