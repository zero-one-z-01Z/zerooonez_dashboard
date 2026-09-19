/**
 * تنقل جداول صفحة التفاصيل: كل طلب ناجح يحمل تعريف الجدول وHTML نماذجه من Laravel.
 * لا يرسل هذا الملف عمليات حفظ السجلات؛ تبقى عمليات الإضافة والتعديل في tables/records.js.
 * الشرح الكامل: docs/dashboard/CHANGES-EXPLAINED.ar.md وREADME.md في هذا المجلد.
 */
import { generateTableHeaders } from './headers.js';
import { closeTabModals, disposeTabModals, prepareTabModals } from './modals.js';
import { cancelPendingFormPopulation } from '../forms/population.js';
import { Dashboard } from '../core/dashboard.js';
import { mountContent } from '../core/lifecycle.js';

// يحتفظ بآخر طلب تعريف فقط؛ يستخدم للإلغاء ولمنع finally قديم من فك قفل طلب أحدث.
let activeRequest = null;

/**
 * يمنع إجراءات الجدول القديم أثناء التحميل ويعرض حالته دون منع اختيار تبويب أحدث.
 * @param {boolean} loading حالة الطلب الحالي.
 * @param {string} message وصف التحميل أو سبب الفشل المعروض لقارئ الشاشة والمستخدم.
 * @returns {void} يحدث inert وaria-busy ورسالة الحالة في المضيف والجدول، دون تعطيل روابط التبويبات.
 */
function setTabLoading(loading, message = '') {
    for (const id of ['dashboard-item-table-region', 'dashboard-item-table-modals']) {
        const element = document.getElementById(id);
        if (!element) continue;
        element.inert = loading;
        element.setAttribute('aria-busy', String(loading));
    }
    const status = document.getElementById('dashboard-item-table-status');
    if (status) { status.textContent = message; status.hidden = !message; }
}

/**
 * يجلب تعريف الجدول ونماذجه كوحدة واحدة، ويلغي الطلب الأقدم عند التنقل السريع.
 * يحتفظ بالجدول الحالي إذا فشل التحميل، ويجهز حقول النماذج الجديدة قبل إتاحة الأزرار.
 * @param {string} link مسار get_data الذي حددته Blade.
 * @param {HTMLElement|null} activeLink رابط التبويب الذي سيصبح نشطا عند النجاح فقط.
 * @returns {Promise<boolean>} true إذا ثبت أحدث تبويب بنجاح، وfalse عند الفشل أو تجاوزه بطلب أحدث.
 */
export async function loadTableData(link, activeLink = null) {
    // 1) أبطل تعريفًا سابقًا وقراءات تعديل تخص النماذج الحالية، ثم امنع إجراءات الجدول أثناء الانتقال.
    activeRequest?.abort();
    const request = new AbortController();
    activeRequest = request;
    const host = document.getElementById('dashboard-item-table-modals');
    if (host) cancelPendingFormPopulation(host);
    setTabLoading(true, 'جارٍ تحميل الجدول والنماذج…');
    let errorMessage = '';
    try {
        // 2) اقرأ التعريف وHTML معًا. أي فشل هنا يترك الجدول والنماذج الحالية كما هي.
        const response = await fetch(link, { signal: request.signal, headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(`تعذّر تحميل التبويب (${response.status}).`);
        const result = await response.json();
        if (request.signal.aborted) return false;
        if (!host) throw new Error('حاوية نماذج التبويبات غير موجودة. حدّث الصفحة وحاول مرة أخرى.');
        const assets = result.assets || result.data?.assets || [];
        if (assets.length) await Dashboard.assets.loadMany(assets);
        if (request.signal.aborted) return false;
        const fragment = prepareTabModals(result);
        // 3) الإغلاق غير متزامن بسبب حركة Bootstrap؛ افحص الإلغاء مرة ثانية بعد انتهاء الحركة.
        await closeTabModals(host);
        if (request.signal.aborted) return false;
        // 4) حرر الأدوات قبل إزالة عناصرها، ثم ركّب HTML قبل تهيئة الحقول وقواعد التحقق.
        disposeTabModals(host);
        window.destroyDataTable();
        host.replaceChildren(fragment);
        window.prepareDataTable(result.data);
        generateTableHeaders(result.data);
        window.setUpDatatable();
        mountContent(host, { definition: result.data, reason: 'tab-load' });
        if (activeLink) {
            document.querySelectorAll('.nav-link[data-link]').forEach(tab => {
                const active = tab === activeLink;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', String(active));
            });
        }
        return true;
    } catch (error) {
        if (!request.signal.aborted && error.name !== 'AbortError') {
            errorMessage = error.message;
            console.error('Failed to load table data:', error);
            window.showNotification?.('error', errorMessage);
        }
        return false;
    } finally {
        // 5) رد أقدم لا يغير حالة التحميل أو الخطأ التي يملكها التبويب المطلوب حاليًا.
        if (activeRequest === request) setTabLoading(false, errorMessage);
    }
}

/**
 * يقرأ مرشحات سجل التفاصيل مرة واحدة ويربط التنقل دون إعادة تحميل الصفحة.
 * تبقى internal_filter للسجل الأب منفصلة عن مرشحات التبويب التي تستبدلها prepareDataTable.
 * @returns {void} يسجل أحداث روابط data-link ويبدأ تحميل أول تبويب إن وجد.
 */
export function startItemTables() {
    const config = document.querySelector('#dashboard-item-table-definition');
    window.internal_filter = config ? JSON.parse(config.textContent) : {};
    window.auto_start = false;
    const links = document.querySelectorAll('.nav-link[data-link]');
    links.forEach(link => link.addEventListener('click', event => {
        event.preventDefault();
        loadTableData(link.dataset.link, link);
    }));
    if (links.length) loadTableData(links[0].dataset.link, links[0]);
}
