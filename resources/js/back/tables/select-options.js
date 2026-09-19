/** تحويل بيانات Select2 إلى معاملات بحث وعلاقات، ثم تهيئة استجابة الخيارات والتصفح. */

/**
 * يجهز معاملات بحث Select2 والعلاقة بالأب والقيم المطلوبة من الحقول الأخرى.
 * @param {object} item تعريف الحقل بما فيه parent_id وrelations.
 * @param {object} params بيانات بحث Select2 والصفحة.
 * @param {Function} readValue قارئ قيمة الحقل بالمعرف.
 * @returns {{query: object, missing: string[]}} معاملات الطلب وأسماء العلاقات المطلوبة الناقصة؛ بلا DOM أو شبكة.
 */
export function buildSelectQuery(item, params, readValue) {
    const query = { search: params.term, page: params.page || 1 };
    const missing = [];
    if (item.parent_id != null) {
        const parentValue = readValue(item.parent_id);
        if (parentValue) {
            query.parent_id = parentValue;
            query.parent_key = item.parent_key;
            if (item.child_key != null) query.child_key = item.child_key;
        }
    }
    for (const [key, rule] of Object.entries(item.relations ?? {})) {
        const id = isNaN(key) ? key : rule;
        const required = isNaN(key) && rule === 'required';
        const value = readValue(id);
        const empty = value === undefined || value === null || value === '' || (Array.isArray(value) && !value.length);
        if (required && empty) missing.push(id);
        if (!empty) query[id] = value;
    }
    return { query: missing.length ? {} : query, missing };
}

/**
 * يحول استجابة Select2 الحالية {data: [...]} إلى results وpagination.
 * @param {object} response رد الخادم.
 * @param {object} params خيارات Select2، ويحدث رقم الصفحة عند غيابه.
 * @returns {object} الخيارات مع استمرار التصفح إلى أن يرجع الخادم صفحة فارغة.
 */
export function mapSelectResults(response, params) {
    params.page = params.page || 1;
    const records = response.data ?? [];
    return { results: records.map(record => ({ id: record.id, text: record.text })), pagination: { more: records.length > 0 } };
}
