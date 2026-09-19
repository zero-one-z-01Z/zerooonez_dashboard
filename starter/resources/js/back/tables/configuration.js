/** تحويل تعريف الكنترولر إلى حالة شاشة مستقلة ثم نشر عقد window القديم للدوال المشتركة. */

/**
 * يقرأ مسارا منقطا من سجل، مثل customer.name، بدون تغيير السجل.
 * @param {object} record سجل الخادم.
 * @param {string} path مسار الحقل.
 * @returns {*} قيمة الحقل أو undefined عند غياب أحد الأجزاء.
 */
export function getNestedValue(record, path) {
    return String(path ?? '').split('.').reduce((value, key) => value?.[key], record);
}

/**
 * يحول تعريف الشاشة إلى حالة مستقلة؛ لا يعدل التعريف أو حالة الشاشة السابقة.
 * يبقي أسماء المفاتيح القديمة لأن Blade ودوال CRUD تعتمد عليها.
 * @param {object} data تعريف الشاشة القادم من الكنترولر.
 * @param {string[]} permissions صلاحيات المستخدم الحالي.
 * @returns {object} حالة الجدول والقوائم والروابط والفلاتر.
 */
export function buildTableConfiguration(data, permissions = []) {
    const state = {};
    const lists = ['filters', 'show_list', 'inputs', 'update_inputs', 'datatable_actions', 'modals'];
    const fields = [
        'have_actions', 'have_check_box', 'have_import', 'have_export', 'have_add',
        'have_delete_all', 'auto_start', 'delete_all_url', 'get_single_item',
        'update_url', 'delete_url', 'datatable_url', 'store_url', 'title',
    ];
    for (const key of fields) state[key] = data[key];
    for (const key of lists) state[key] = data[key] ?? [];
    state.user_permissions = (data.permissions ?? []).filter(permission =>
        permission === 'open' || permissions.includes(permission));
    state.optioanl_filter = Object.fromEntries(
        (data.internal_filters ?? []).map(filter => [filter.key, filter.value]),
    );
    state.external_filters = Object.fromEntries(
        state.filters.map(filter => [filter.id, `#filter${filter.id}`]),
    );
    if (data.daterange_filter) state.external_filters.created_at = '#datatable_daterange_filter';
    state.dropzone = data.dropzone ?? false;
    state.datatable_id = '#table_id';
    return state;
}

/**
 * ينشر تعريف شاشة جديدة للدوال العامة، ويستبدل الفلاتر القديمة بالكامل.
 * @param {object} data تعريف الكنترولر للصفحة أو تبويب AJAX.
 * @returns {void} يحدث خصائص window المتوافقة مع النظام الحالي.
 */
export function prepareDataTable(data) {
    Object.assign(window, buildTableConfiguration(data, window.all_permissions ?? []));
}
