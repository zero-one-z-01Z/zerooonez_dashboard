/** إنشاء وتدمير مثيل الجدول وربط ظهور الأعمدة ومفاتيح الصفوف دون تكرار المستمعين. */

import { buildColumns } from './columns.js';
import { initializeFields } from './fields.js';
import { initializeFormValidation } from './validation.js';

/**
 * يحسب أعمدة البيانات التي يتحكم فيها اختيار الأعمدة، بدون التحكم والتحديد والإجراءات.
 * @param {object} state تعريف الشاشة الحالي.
 * @returns {number[]} فهارس أعمدة البيانات المطابقة لرأس Blade؛ لا يعدل الجدول.
 */
export function dataColumnIndexes(state) {
    const offset = state.have_check_box ? 2 : 1;
    return state.show_list.map((column, index) => offset + index);
}

/**
 * يربط اختيار الأعمدة بالأعمدة الفعلية ويستبدل مستمعي الوحدة فقط عند إعادة التهيئة.
 * @param {object} table مثيل DataTables الحالي.
 * @returns {void} يحدث ظهور الأعمدة عند التغيير مع إبقاء أعمدة النظام ظاهرة.
 */
function bindColumnVisibility(table) {
    $('#columns').off('change.dashboardColumns').on('change.dashboardColumns', function () {
        const selected = ($(this).val() ?? []).map(String);
        for (const index of dataColumnIndexes(window)) table.column(index).visible(selected.includes(String(index)));
        table.draw();
    });
    $('#columns option').off('mousedown.dashboardColumns').on('mousedown.dashboardColumns', function (event) {
        event.preventDefault();
        $(this).prop('selected', !$(this).prop('selected')).parent().trigger('change');
        return false;
    });
}

/**
 * يربط مفاتيح صفوف الجدول بالتفويض، ويترك المفاتيح المخصصة لمعالج النماذج وحده.
 * @returns {void} يرسل id/value إلى update_val عند تغيير المستخدم للمفتاح.
 */
function bindSwitchInputs() {
    $(document).off('change.dashboardSwitch', '.switch-input').on('change.dashboardSwitch', '.switch-input', function (event) {
        const checkbox = $(this);
        if (checkbox.hasClass('custom') || !checkbox.closest(window.datatable_id).length) return;
        event.preventDefault();
        window.update_val(checkbox.data('link'), { value: checkbox.is(':checked') ? 1 : 0, id: checkbox.data('id') });
    });
}

/**
 * ينشئ جدول الشاشة وأدوات الحقول والتحقق بعد prepareDataTable، ويمنع إنشاء المثيل مرتين.
 * @returns {object|null} مثيل DataTables الحالي أو null عند غياب الجدول في DOM؛ يبدأ طلب البيانات عند أول إنشاء.
 */
export function setUpDatatable() {
    if (!window.datatable_id || !$(window.datatable_id).length) return null;
    if ($.fn.dataTable.isDataTable(window.datatable_id)) {
        window.pageTable = $(window.datatable_id).DataTable();
        return window.pageTable;
    }
    window.pageTable = window.initializeDataTable(window.datatable_id, window.datatable_url,
        window.external_filters, buildColumns(window), window.datatable_actions);
    $('#import_text').text(window.import_text);
    $('#export_text').text(window.export_text);
    $('#delete_all_text').text(window.delete_all_text);
    $('#add_new_item_text').text(window.addNewItem);
    bindColumnVisibility(window.pageTable);
    initializeFields();
    initializeFormValidation();
    bindSwitchInputs();
    return window.pageTable;
}

/**
 * يدمر مثيل الجدول قبل تبديل تبويب AJAX ويزيل مرجعه حتى لا يعاد استخدامه.
 * @returns {void} يصفر بيانات المثيل وأحداثه الداخلية ويحرر window.pageTable.
 */
export function destroyDataTable() {
    if (window.pageTable) {
        // قراءة صفوف التبويب السابق قد تنتهي بعد تركيب جدول آخر بنفس المعرف.
        window.pageTable.settings?.()[0]?.jqXHR?.abort();
        window.pageTable.clear().destroy();
    }
    window.pageTable = null;
    $(document).off('change.dashboardSwitch', '.switch-input');
}
