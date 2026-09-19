/** بناء حمولة الفلاتر كمنطق مستقل؛ يستقبل قارئ DOM بدل ربطه مباشرة بصفحة معينة. */

/**
 * يبني فلاتر طلب الجدول، مع دعم محدد DOM أو var[name] أو نطاق التاريخ.
 * @param {object} selectors خريطة اسم الفلتر إلى مصدر القيمة.
 * @param {Function} readElement قارئ يرجع {exists,value,name} لمحدد DOM.
 * @param {object} state متغيرات الشاشة والفلاتر الداخلية.
 * @returns {object} فلاتر مستقلة؛ الداخلية الاختيارية تتغلب على الخارجية، ثم يستخدم fallback عند غيابها.
 */
export function buildRequestFilters(selectors, readElement, state) {
    const filters = {};
    for (const [key, selector] of Object.entries(selectors ?? {})) {
        const variable = selector.match(/^var\[(.+)\]$/);
        if (variable) {
            filters[key] = state[variable[1]] ?? null;
            continue;
        }
        const element = readElement(selector);
        if (selector === '#datatable_daterange_filter') {
            filters.date_column = element.name;
            filters.date_range = element.value;
        } else if (element.exists) filters[key] = element.value;
    }
    const internal = state.optioanl_filter && Object.keys(state.optioanl_filter).length
        ? state.optioanl_filter : (state.internal_filter ?? {});
    return { ...filters, ...internal };
}
