/** قيود أحرف الإدخال للعربية والإنجليزية. */

const LANGUAGE_PATTERNS = {
    ar: {
        pattern: /[\u0600-\u06FF]/,
        name: 'ar'
    },
    en: {
        pattern: /[\u0000-\u007F]/,
        name: 'en'
    }
};

const ALLOWED_KEYS = new Set([
    8,   // Backspace
    0,   // Special keys (like arrow keys)
    32   // Space
]);

/**
 * يقيّد ضغطات المفاتيح بنمط اللغة المحدد مع السماح بالمفاتيح الخاصة والمسافة.
 * @param {HTMLElement} element حقل الإدخال.
 * @param {"ar"|"en"} language رمز اللغة المدعوم.
 * @throws {Error} عند تمرير رمز لغة غير مدعوم.
 * @returns {void} يسجل keypress ويضيف data-language-restriction؛ لا يتحقق من اللصق أو من بيانات الخادم.
 */
export function restrictInputToLanguage(element, language) {
    if (!LANGUAGE_PATTERNS[language]) {
        throw new Error(`Unsupported language: ${language}. Supported languages are: ${Object.keys(LANGUAGE_PATTERNS).join(', ')}`);
    }

    const { pattern, name } = LANGUAGE_PATTERNS[language];

    element.addEventListener('keypress', (event) => {

        if (ALLOWED_KEYS.has(event.which)) {
            return true;
        }

        const char = String.fromCharCode(event.which);
        const isValidChar = pattern.test(char);

        if (!isValidChar) {
            event.preventDefault();
        }

        return isValidChar;
    });

    element.setAttribute('data-language-restriction', name);
}

/**
 * يطبّق قيد اللغة على جميع الحقول داخل مجموعة jQuery.
 * @param {jQuery} $field مجموعة الحقول.
 * @param {"ar"|"en"} language رمز اللغة.
 * @returns {void} يربط قيد keypress بكل عنصر في المجموعة، وقد يرمي خطأ اللغة غير المدعومة.
 */
export function restrictInputToLanguageJQuery($field, language) {
    $field.each((_, element) => restrictInputToLanguage(element, language));
}

/**
 * يجد حقولًا بالاسم داخل نموذج ويطبق قيد اللغة عليها.
 * @param {string|jQuery} form النموذج.
 * @param {string} element_name اسم الحقل.
 * @param {"ar"|"en"} language رمز اللغة.
 * @returns {void} يربط قيد keypress بالحقول المطابقة داخل النموذج فقط.
 */
export function restrictInputToLanguageInForm(form, element_name, language) {
    $(form).find(`[name="${element_name}"]`).each((_, element) => restrictInputToLanguage(element, language));
}
