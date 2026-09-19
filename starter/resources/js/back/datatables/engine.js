/** المحول إلى DataTables: طلبات الخادم وتخطيط الجدول وتجميع الأدوات والفلاتر. */

import { buildColumnDefinitions } from './actions.js';
import { buildToolbarButtons } from './toolbar.js';
import { buildRequestFilters } from './filter-values.js';
import { bindFilterControls } from './filters.js';

/**
 * يقرأ وجود عنصر فلترة وقيمته واسم عمود التاريخ من DOM وقت إرسال الطلب.
 * @param {string} selector محدد عنصر الفلتر.
 * @returns {object} وجود العنصر وقيمته واسمه، دون تغيير العنصر.
 */
function readFilterElement(selector) {
    const element = $(selector);
    return { exists: element.length > 0, value: element.val(), name: element.prop('name') };
}

/**
 * ينشئ DataTable بتحميل الخادم وأدواته ويربط الفلاتر بالمثيل الجديد.
 * @param {string} tableSelector محدد الجدول.
 * @param {string} ajaxUrl عنوان POST لجلب الصفوف.
 * @param {object} externalFilters خريطة الفلاتر إلى مصادرها.
 * @param {object[]} columns أعمدة البيانات بترتيب Blade.
 * @param {object[]} actions إجراءات الصفوف وتعريف الصلاحيات.
 * @param {object} options إعدادات DataTables إضافية تتغلب على الافتراضيات.
 * @returns {object} مثيل DataTables؛ يبدأ طلب البيانات ويربط أحداث الواجهة.
 * can_edit_by_developer
 */
export function initializeDataTable(tableSelector, ajaxUrl, externalFilters, columns, actions, options = {}) {
    const defaults = {
        searchDelay: 700, processing: false, serverSide: true, scrollX: true, scrollY: '70vh',
        scrollCollapse: true, autoWidth: false,
        /**
         * يجهز قوائم Bootstrap بعد رسم صفوف الجدول.
         * @returns {jQuery} مجموعة dropdown-toggle التي هيأتها إضافة dropdown.
         */
        drawCallback: () => $('.dropdown-toggle').dropdown(),
        ajax: { url: ajaxUrl, type: 'POST',
            /**
             * يقرأ الفلاتر عند كل طلب ليشمل القيم الحالية بعد تغيير الحقول.
             * @param {object} request حمولة DataTables التي ستُرسل إلى الخادم.
             * @returns {void} يكتب request.filters في الكائن نفسه قبل الإرسال.
             */
            data: request => { request.filters = buildRequestFilters(externalFilters, readFilterElement, window); } },
        columnDefs: buildColumnDefinitions(actions, window), columns,
        layout: {
            topStart: {
                rowClass: 'card-header d-flex border-top rounded-0 flex-wrap py-0 flex-column flex-md-row align-items-start',
                features: [{ search: { className: 'me-5 ms-n4 pe-5 mb-n6 mb-md-0', placeholder: window.search_hint, text: '_INPUT_' }, pageLength: { menu: [10, 25, 50, 100], text: '_MENU_' } }],
            },
            topEnd: { rowClass: 'row m-3 my-0 justify-content-between', features: [{ buttons: buildToolbarButtons(window) }] },
            bottomStart: { rowClass: 'row mx-3 justify-content-between', features: ['info'] }, bottomEnd: 'paging',
        },
    };
    const table = $(tableSelector).DataTable($.extend(true, {}, defaults, options));
    bindFilterControls(table);
    return table;
}
