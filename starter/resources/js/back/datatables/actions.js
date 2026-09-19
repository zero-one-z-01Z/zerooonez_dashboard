/** تكوين إجراءات الصف وأعمدة التحكم والتحديد؛ صلاحيات العرض تكمل التحقق الإلزامي في Laravel.
 * الاكشن داخل الجدول*/

import { escapeHtml } from '../tables/text.js';
import { replaceUrlPlaceholder, replaceUrlPlaceholderV2 } from './urls.js';

/**
 * يتحقق من امتلاك صلاحية واحدة أو أي صلاحية ضمن قائمة، بدون تعديل الحالة.
 * @param {string|string[]} permission الصلاحية المطلوبة للإجراء.
 * @param {string[]} permissions صلاحيات الشاشة بعد التقاطع مع صلاحيات المستخدم.
 * @returns {boolean} هل يمكن عرض الإجراء؛ يظل تفويض الخادم هو المرجع عند التنفيذ.
 */
export function hasPermission(permission, permissions = []) {
    return Array.isArray(permission) ? permission.some(item => permissions.includes(item)) : permissions.includes(permission);
}

/**
 * يجمع شرط الصلاحيات وشروط قيم السجل للإجراء قبل رسمه.
 * @param {object} action تعريف الإجراء، بما فيه permission وif الاختيارية.
 * @param {object} row سجل الخادم.
 * @param {string[]} permissions صلاحيات المستخدم لهذه الشاشة.
 * @returns {boolean} true إذا اجتاز الإجراء كل شروط العرض؛ لا ينفذ الإجراء.
 */
export function isActionVisible(action, row, permissions) {
    return hasPermission(action.permission, permissions)
        && Object.values(action.if ?? {}).every(condition => row[condition.key] === condition.value);
}

/**
 * يرسم إجراء واحد ويحافظ على data-call_function التي يستخدمها موزع أحداث CRUD.
 * @param {object} action وصف الرابط أو النافذة أو الحذف.
 * @param {object} row سجل الخادم الذي يؤخذ منه المعرف.
 * @returns {string} HTML الإجراء؛ يحدث قوالب الروابط عند استخدام نوع link بقيمة id.
 * شكل الزر في popmenu
 * can_edit_by_developer
 */
function renderAction(action, row) {
    const icon = `<i class="${escapeHtml(action.icon)} me-2"></i>${escapeHtml(action.name)}`;
    const event = action.onclick !== undefined ? `data-event_on="click" data-call_function="${escapeHtml(action.onclick)}"` : '';
    if (action.type === 'link') {
        const url = action.value === 'id'
            ? replaceUrlPlaceholder(action.link, row.id)
            : replaceUrlPlaceholderV2(action.link, row[action.value]);
        return `<a class="dropdown-item" href="${escapeHtml(url)}" target="${action.blank ? '_blank' : ''}">${icon}</a>`;
    }
    if (action.type === 'modal' || action.type === 'custom_modal') {
        const form = action.type === 'custom_modal' ? `data-form="${escapeHtml(action.form_id)}"` : '';
        return `<a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="${escapeHtml(action.link)}" ${form} data-id="${escapeHtml(row.id)}" ${event}>${icon}</a>`;
    }
    if (action.type === 'delete') {
        const url = replaceUrlPlaceholderV2(action.link, action.value === 'id' ? row.id : row[action.value]);
        const handler = `window.deleteButton(${action.onclick}, ${JSON.stringify(url)})`;
        return `<button class="dropdown-item" onclick="${escapeHtml(handler)}">${icon}</button>`;
    }
    return '';
}

/**
 * يرسم قائمة إجراءات السجل ويترك render المخصص يعمل بتوقيعه القديم.
 * @param {object[]} actions تعريفات الإجراءات.
 * @param {*} data قيمة خلية الإجراءات.
 * @param {string} type نوع عملية DataTables.
 * @param {object} row سجل الخادم.
 * @param {string[]} permissions صلاحيات الشاشة.
 * @returns {*} HTML القائمة، أو القيمة الأصلية عند غياب تعريفات الإجراءات.
 */
export function renderActions(actions, data, type, row, permissions) {
    if (!actions.length) return data;
    const items = actions.filter(action => isActionVisible(action, row, permissions))
        .map(action => action.render ? action.render(data, type, row) : renderAction(action, row)).join('');
    return `<div class="d-inline-block text-nowrap">
        <button class="btn btn-text-secondary rounded-pill waves-effect btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false" data-bs-boundary="viewport"><i class="icon-base ti tabler-dots-vertical icon-22px"></i></button>
        <div class="dropdown-menu dropdown-menu-end m-0">${items}</div></div>`;
}

/**
 * يبني إعدادات عمود التحكم والتحديد والإجراءات وفق أعلام الشاشة.
 * @param {object[]} actions تعريفات الإجراءات.
 * @param {object} state إعدادات الشاشة وصلاحياتها.
 * @returns {object[]} إعدادات columnDefs المتوافقة مع ترتيب أعمدة Blade.
 */
export function buildColumnDefinitions(actions, state) {
    const definitions = [{ targets: 0, orderable: false, searchable: false, className: 'control',
        /**
         * يترك عمود تحكم Responsive فارغًا ليضيف DataTables علامته.
         * @returns {string} نص فارغ دون لمس DOM.
         */
        render: () => '' }];
    if (state.have_check_box) {
        definitions.push({ targets: 1, orderable: false, searchable: false,
            /**
             * يبني مربع تحديد السجل للحذف الجماعي.
             * @param {*} data قيمة خلية DataTables.
             * @param {string} type نوع طلب العرض أو البحث أو الترتيب من DataTables.
             * @param {object} row السجل الكامل الذي تنتمي إليه الخلية.
             * @returns {string} HTML يحمل data-id مهربًا؛ data وtype محفوظان في توقيع DataTables.
             */
            render: (data, type, row) => `<div class="form-check form-check-md"><input class="form-check-input sub_check" name="check-${escapeHtml(row.id)}" type="checkbox" data-id="${escapeHtml(row.id)}"></div>` });
    }
    definitions.push({ targets: '_all', responsivePriority: 1 });
    if (state.have_actions) {
        definitions.push({ targets: -1, orderable: false, searchable: false,
            /**
             * يمرر خلية الإجراءات إلى مرشح الصلاحيات وشروط السجل.
             * @param {*} data قيمة خلية DataTables.
             * @param {string} type نوع طلب العرض أو البحث أو الترتيب من DataTables.
             * @param {object} row السجل الكامل الذي تنتمي إليه الخلية.
             * @returns {string} HTML قائمة الإجراءات المسموحة في حالة الشاشة الحالية.
             */
            render: (data, type, row) => renderActions(actions, data, type, row, state.user_permissions ?? []) });
    }
    return definitions;
}
