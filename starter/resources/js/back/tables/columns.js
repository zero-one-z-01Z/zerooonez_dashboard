/** ترجمة أنواع show_list إلى خلايا العرض مع حفظ ترتيب أعمدة التحكم والتحديد والإجراءات. */

import { getNestedValue } from './configuration.js';
import { escapeHtml, renderToggleText } from './text.js';
import { replaceUrlPlaceholder } from '../datatables/urls.js';

const statusColors = {
    pending: 'bg-label-warning', accepted: 'bg-label-success', prepared: 'bg-label-info',
    delivery: 'bg-label-secondary', delivery_to_user: 'bg-label-primary', completed: 'bg-label-success',
    out_of_platform: 'bg-label-success', paid: 'bg-label-success', not_paid: 'bg-label-danger',
    user_cancelled: 'bg-label-danger', delivery_cancelled: 'bg-label-danger', admin_cancelled: 'bg-label-danger',
    branch_cancelled: 'bg-label-danger', high: 'bg-label-danger', medium: 'bg-label-warning', low: 'bg-label-success',
};

/**
 * يرسم اسم العنصر وصورته ووصفه باستخدام مسارات details التي يحددها الكنترولر.
 * @param {object} row سجل الخادم.
 * @param {object} details مسارات title وdescription وimage.
 * @returns {string} HTML خلية الاسم والوصف والصورة؛ لا يعدل DOM.
 */
function renderImageDescription(row, details) {
    const title = getNestedValue(row, details.title) ?? '';
    const description = getNestedValue(row, details.description) ?? '';
    const image = escapeHtml(getNestedValue(row, details.image));
    return `<div class="d-flex justify-content-start align-items-center product-name">
        ${image ? `<div class="avatar-wrapper"><div class="avatar avatar me-2 me-sm-4 rounded-2 bg-label-secondary" style="width:50px;height:50px;overflow:hidden">
            <a href="${image}" target="_blank"><img src="${image}" alt="" class="rounded" style="width:100%;height:100%;object-fit:cover"></a>
        </div></div>` : ''}
        <div class="d-flex flex-column"><h6 class="mb-0">${renderToggleText(title, 'title')}</h6>
        <div>${renderToggleText(description, 'desc')}</div></div></div>`;
}

/**
 * يحول نوع الحقل في show_list إلى HTML العرض المتوافق مع شاشة الجدول.
 * @param {object} column تعريف العمود من الكنترولر.
 * @param {*} data قيمة الخلية.
 * @param {object} row السجل الكامل لاستخراج الحقول المرتبطة.
 * @returns {*} محتوى الخلية؛ الروابط من نوع text_button تحدث القوالب المطابقة أيضا.
 * can_edit_by_developer
 */
export function renderColumn(column, data, row) {
    const value = escapeHtml(data);
    switch (column.type) {
        case 'text': return renderToggleText(data, 'text');
        case 'html_text': return renderToggleText(data, 'html', true);
        case 'button_url': return data ? `<a href="${value}" target="_blank" class="btn btn-primary waves-effect waves-light">${escapeHtml(window.view_text)}</a>` : '';
        case 'bool': return `<span class="${escapeHtml(column.class)}">${escapeHtml(data === 1 ? window.yesText : window.noText)}</span>`;
        case 'status': return `<span class="badge px-2 ${statusColors[getNestedValue(row, column.column)] ?? ''}">${value}</span>`;
        case 'text_button': return `<a href="${escapeHtml(replaceUrlPlaceholder(column.link, getNestedValue(row, column.column)))}" target="_blank" class="text-primary">${value}</a>`;
        case 'id': return `<span class="text-primary">${value}</span>`;
        case 'image': return `<div class="avatar-wrapper"><a class="avatar avatar me-2 me-sm-4 rounded-2" href="${value}" target="_blank"><img src="${escapeHtml(getNestedValue(row, column.key))}" alt="" class="rounded" style="max-width:50px;height:auto"></a></div>`;
        case 'image_description': return renderImageDescription(row, column.details);
        case 'switch': return `<span class="text-truncate"><label class="switch switch-primary switch-sm">
            <input type="checkbox" class="switch-input" id="${escapeHtml(row.id)}${escapeHtml(column.key)}" data-id="${escapeHtml(row.id)}" data-link="${escapeHtml(column.link)}" ${data === 1 ? 'checked' : ''}>
            <span class="switch-toggle-slider"><span class="switch-${data === 1 ? 'on' : 'off'}"></span></span></label></span>`;
        default: return data ?? '';
    }
}

/**
 * يبني أعمدة DataTables بالترتيب نفسه الموجود في رأس جدول Blade.
 * @param {object} state إعدادات show_list وأعلام التحديد والإجراءات.
 * @returns {object[]} عمود التحكم ثم التحديد الاختياري والبيانات ثم الإجراءات الاختيارية.
 */
export function buildColumns(state) {
    const columns = [{ data: null }];
    if (state.have_check_box) columns.push({ data: null });
    for (const column of state.show_list) {
        columns.push({ data: column.key,
            /**
             * يعرض قيمة العمود وفق النوع الذي حدده show_list.
             * @param {*} data قيمة خلية DataTables.
             * @param {string} type نوع طلب العرض أو البحث أو الترتيب من DataTables.
             * @param {object} row السجل الكامل الذي تنتمي إليه الخلية.
             * @returns {*} ناتج renderColumn؛ type محفوظ في توقيع DataTables دون معالجة خاصة.
             */
            render: (data, type, row) => renderColumn(column, data, row) });
    }
    if (state.have_actions) columns.push({ data: null });
    return columns;
}
