/** قائمة فلاتر الجدول وشارات القيم ونطاق التاريخ ودورة تطبيقها أو مسحها. */

import { escapeHtml } from '../tables/text.js';

/**
 * يغلق قائمة الفلاتر أو نافذتها إذا كانت موجودة ومفتوحة.
 * @returns {void} يحدث حالة عرض Bootstrap فقط، دون تغيير قيم الفلاتر.
 */
export function closeFilterModal() {
    document.querySelector('.dropdown-menu.show')?.classList.remove('show');
    const modal = document.getElementById('filters_modal');
    if (modal) bootstrap.Modal.getInstance(modal)?.hide();
}

/**
 * يفرغ حقل فلترة مفردا ويحافظ على حدث change لقوائم Select2.
 * @param {jQuery} input الحقل المطلوب تفريغه.
 * @returns {void} يحدث قيمة الحقل فقط، دون إرسال طلب الجدول.
 */
export function clearInput(input) {
    if (input.is('input')) input.val('');
    else if (input.is('select')) input.val(input.prop('multiple') ? [] : '').trigger('change');
}

/**
 * يعيد بناء شارات الفلاتر من قيم النموذج الحالية مع تهريب النص المعروض.
 * @returns {void} يحدث عناصر filterBadges؛ أسماء الاستدعاءات تبقى متوافقة مع موزع CRUD.
 */
export function placeFilterBadges() {
    const form = $('#datatable_filter_form');
    const badges = [];
    for (const item of form.serializeArray()) {
        const element = form.find(`[name="${$.escapeSelector(item.name)}"]`);
        if (!element.length) continue;
        const label = element.data('filter-name') || element.closest('.mb-3').find('label').text().trim();
        const selected = element.find('option:selected');
        const value = element.is('select') ? (selected.val() !== '' ? selected.text() : '') : item.value;
        if (value) badges.push(`<span class="badge bg-outline-danger ms-1 me-1 mb-1 clearall"><span class="badge-label cursor-pointer" data-event_on="click" data-call_function="clickFilterBadge" data-filter="#${escapeHtml(element.attr('id'))}">${escapeHtml(`${label}: ${value}`)}</span></span>`);
    }
    const clear = '<span class="badge bg-outline-danger me-1 mb-1 cursor-pointer"><span class="badge-label" data-event_on="click" data-call_function="clearFilters">مسح الكل</span></span>';
    $('.filterBadges').html(badges.length ? clear + badges.join('') : '');
}

/**
 * يزيل فلتر الشارة المضغوطة ويحدث الجدول والشارات من الحالة الفعلية للنموذج.
 * @param {Event} event حدث النقر الذي يمرره موزع CRUD.
 * @param {jQuery} object عنصر الشارة الذي يحمل data-filter.
 * @returns {void} يفرغ الحقل ويعيد تحميل بيانات الجدول.
 */
export function clickFilterBadge(event, object) {
    event?.preventDefault();
    clearInput($(object.data('filter')));
    window.pageTable.ajax.reload();
    placeFilterBadges();
}

/**
 * يفوض مسح كل الفلاتر إلى زر الإلغاء للحفاظ على مسار تحديث واحد.
 * @param {Event} event حدث النقر الاختياري.
 * @returns {void} يصفر النموذج ويحدث الجدول عبر معالج زر الإلغاء.
 */
export function clearFilters(event) {
    event?.preventDefault();
    $('#datatable_filter_cancel').trigger('click');
    $('.filterBadges').empty();
}

/**
 * يربط تطبيق الفلاتر ومسحها وتغيير التاريخ بالجدول الحالي دون تراكم المستمعين.
 * @param {object} table مثيل DataTables المراد تحديثه.
 * @returns {void} يستبدل أحداث هذه الوحدة فقط، ويعيد تحميل AJAX عند تفاعل المستخدم.
 */
export function bindFilterControls(table) {
    $('#datatable_filter_submit').off('click.dashboardFilters').on('click.dashboardFilters', event => {
        event.preventDefault();
        placeFilterBadges();
        closeFilterModal();
        table.ajax.reload();
    });
    $('#datatable_filter_cancel').off('click.dashboardFilters').on('click.dashboardFilters', event => {
        event.preventDefault();
        const form = $('#datatable_filter_form');
        form[0]?.reset();
        form.find('select').each(function () { $(this).val($(this).prop('multiple') ? [] : null).trigger('change'); });
        $('.filterBadges').empty();
        table.ajax.reload();
    });
    $('#datatable_daterange_filter').off('change.dashboardFilters').on('change.dashboardFilters', () => table.ajax.reload());
}

/**
 * يحدث النص الظاهر لنطاق التاريخ المختار.
 * @param {object} start تاريخ البداية من moment.
 * @param {object} end تاريخ النهاية من moment.
 * @returns {void} يحدث نص bookingrange فقط.
 */
function displayDateRange(start, end) {
    if (start && end) $('.bookingrange span').text(`${start.format('M/D/YYYY')} - ${end.format('M/D/YYYY')}`);
}

/**
 * يهيئ منتقي نطاق التاريخ بالقيم الافتراضية الحالية عند وجوده في الصفحة.
 * @returns {void} يركب daterangepicker؛ يحتفظ بمدة الاثني عشر شهرا الحالية.
 */
export function initializeDateRange() {
    if (!$('.bookingrange').length) return;
    const start = moment().subtract(12, 'months');
    const end = moment();
    $('.bookingrange').daterangepicker({
        startDate: start, endDate: end,
        ranges: {
            Today: [moment(), moment()], 'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 1 Month': [moment().subtract(1, 'months'), moment()],
            'Last 6 Months': [moment().subtract(6, 'months'), moment()],
            'This Year': [moment().startOf('year'), moment().endOf('year')],
        },
    }, displayDateRange);
    displayDateRange(start, end);
}
