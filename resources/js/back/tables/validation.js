/** استخراج قواعد تعريف الحقول وربط مجموعة مستقلة بكل نموذج موجود في الشاشة. */

/**
 * يجمع قواعد نموذج واحد، بما فيها الصف الأول من الحقول المتكررة.
 * الصفوف اللاحقة تضيف قواعدها عند إنشائها بواسطة وحدة multiform.
 * @param {object[]} inputs تعريفات الحقول الخاصة بهذا النموذج فقط.
 * @returns {object} قواعد jQuery Validation حسب أسماء الحقول الفعلية؛ لا يعدل المدخلات.
 */
export function collectValidationRules(inputs = []) {
    const rules = {};
    for (const input of inputs) {
        if (input.validation && Object.keys(input.validation).length) rules[input.id] = input.validation;
        if (input.input === 'multiform') {
            for (const nested of input.inputs ?? []) {
                if (nested.validation && Object.keys(nested.validation).length) {
                    rules[`${input.id}[0][${nested.id}]`] = nested.validation;
                }
            }
        }
    }
    return rules;
}

/**
 * يربط قواعد مستقلة بنموذج الإضافة والتعديل وكل نافذة مخصصة.
 * @param {object} state إعدادات الشاشة المنشورة على window.
 * @returns {void} يسجل قواعد التحقق على النماذج الموجودة في DOM.
 */
export function initializeFormValidation(state = window) {
    const forms = [
        ['#add_form', state.inputs],
        ['#edit_form', state.update_inputs],
        ...(state.modals ?? []).map(modal => [modal.form_id, modal.inputs]),
    ];
    for (const [selector, inputs] of forms) {
        if ($(selector).length) window.defineFormValidation(selector, collectValidationRules(inputs));
    }
}
