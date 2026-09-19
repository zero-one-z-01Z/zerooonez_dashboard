/** أزرار شريط الجدول وتنسيق تصدير الخلايا ونافذة الطباعة. */

import { deleteAll } from './deletion.js';

/**
 * يستخرج نص الخلية للتصدير مع إعطاء أولوية لاسم المنتج كما في القالب الحالي.
 * @param {*} value قيمة خلية DataTables، نص أو HTML أو رقم.
 * @returns {string|number} النص القابل للطباعة والنسخ؛ لا يعدل عناصر الصفحة الأصلية.
 */
export function formatExportCell(value) {
    if (value == null || value === '') return '';
    if (typeof value !== 'string' || !value.includes('<')) return value;
    const parsed = new DOMParser().parseFromString(value, 'text/html');
    const products = [...parsed.querySelectorAll('.product-name')];
    if (products.length) {
        return products.map(product => (product.querySelector('.fw-medium')?.textContent
            || product.querySelector('.d-block')?.textContent || product.textContent).trim()).join(' ').trim();
    }
    return (parsed.body.textContent || parsed.body.innerText || '').trim();
}

/**
 * يطبق ألوان القالب على نافذة الطباعة المستقلة.
 * @param {Window} printWindow نافذة الطباعة التي أنشأتها DataTables.
 * @returns {void} يعدل تنسيق جسم النافذة وجدولها فقط.
 */
function customizePrint(printWindow) {
    const body = printWindow.document.body;
    const colors = window.config?.colors;
    if (colors) {
        body.style.color = colors.headingColor;
        body.style.borderColor = colors.borderColor;
        body.style.backgroundColor = colors.bodyBg;
    }
    const table = body.querySelector('table');
    if (table) {
        table.classList.add('compact');
        Object.assign(table.style, { color: 'inherit', borderColor: 'inherit', backgroundColor: 'inherit' });
    }
}

/**
 * يبني خيارات التصدير الأربعة باستخدام منسق نص واحد مشترك.
 * @returns {object} زر collection للطباعة وCSV وExcel والنسخ، دون تنفيذ تصدير.
 */
function exportButton() {
    const formats = [['print', 'printer', 'Print'], ['csv', 'file', 'Csv'], ['excel', 'download', 'Excel'], ['copy', 'copy', 'Copy']];
    return {
        extend: 'collection', className: 'btn btn-label-primary dropdown-toggle me-4',
        text: '<span class="d-flex align-items-center gap-1"><i class="icon-base ti tabler-download icon-xs"></i><span class="d-none d-sm-inline-block" id="export_text"></span></span>',
        buttons: formats.map(([extend, icon, label]) => ({
            extend, text: `<span class="d-flex align-items-center"><i class="icon-base ti tabler-${icon} me-1"></i>${label}</span>`,
            className: 'dropdown-item', exportOptions: { columns: ':visible', format: { body: formatExportCell } },
            ...(extend === 'print' ? { customize: customizePrint } : {}),
        })),
    };
}

/**
 * يختار أزرار شريط الجدول حسب أعلام الكنترولر مع إبقاء معرفات نصوص الترجمة.
 * @param {object} state إعدادات have_import/export/add/delete_all.
 * @returns {object[]} أزرار DataTables؛ تنفذ النوافذ أو تأكيد الحذف عند النقر فقط.
 * can_edit_by_developer
 */
export function buildToolbarButtons(state) {
    const buttons = [];
    if (state.have_import) buttons.push({ text: '<i class="icon-base ti tabler-upload me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block" id="import_text"></span>', className: 'import-file btn btn-dark me-4',
        /**
         * يفتح نافذة استيراد الملف من زر شريط الجدول.
         * @returns {void} يفوض عرض النافذة إلى show_import_modal.
         */
        action: () => window.show_import_modal() });
    if (state.have_export) buttons.push(exportButton());
    if (state.have_add) buttons.push({ text: '<i class="icon-base ti tabler-plus me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block" id="add_new_item_text"></span>', className: 'add-new btn btn-success me-4',
        /**
         * يفتح نموذج الإضافة مع أدواته من شريط الجدول.
         * @returns {void} يفوض تهيئة النافذة إلى show_add_modal.
         */
        action: () => window.show_add_modal() });
    if (state.have_delete_all) buttons.push({ text: '<i class="icon-base ti tabler-trash me-0 me-sm-1 icon-16px"></i><span class="d-none d-sm-inline-block" id="delete_all_text"></span>', className: 'btn btn-danger',
        /**
         * يطلب تأكيد الحذف قبل تمرير السجلات المحددة إلى deleteAll.
         * @returns {Promise} وعد التأكيد؛ يبدأ الحذف فقط عند الموافقة.
         */
        action: () => window.deleteButton(deleteAll) });
    return buttons;
}
