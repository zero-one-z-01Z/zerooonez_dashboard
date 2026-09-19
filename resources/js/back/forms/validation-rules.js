/** قواعد jQuery Validate وترجمة رسائل الإدخال. */

/**
 * يسجل قواعد المقارنة والتواريخ والملفات ورسائلها العربية عند وجود jQuery Validate واللغة ar.
 * @returns {void} يحافظ على شرط اللغة الأصلي ولا يسجل القواعد في الإنجليزية.
 */
export function registerValidationRules() {
    if ($.validator && currentLocale == 'ar') {
        /**
         * يقارن value رقميًا بقيمة الحقل الذي يحدده params داخل النموذج الحالي؛ المساواة مرفوضة.
         * @param {string|number} value قيمة الحقل الجاري التحقق منه.
         * @param {HTMLElement} element عنصر الحقل؛ مقارنة القيم تعتمد على currentForm.
         * @param {string} params اسم الحقل الآخر داخل النموذج.
         * @returns {boolean} true عندما تتجاوز القيمة قيمة الحقل الآخر بعد parseFloat.
         */
        $.validator.addMethod("greaterThan", function (value, element, params) {
            var fromValue = $(this.currentForm).find(`[name=${params}]`).val()
            return parseFloat(value) > parseFloat(fromValue);
        }, "The 'To' value must be greater than the 'From' value.");

        /**
         * يسمح بالحقل الاختياري أو بملف لا يتجاوز param ميجابايت.
         * @param {string} value قيمة الإدخال؛ حجم الملف يؤخذ من element.files.
         * @param {HTMLInputElement} element حقل الملف الجاري فحصه.
         * @param {number|string} param الحد الأقصى بالميجابايت.
         * @returns {boolean} صلاحية الاختيار؛ لا يرفع الملف أو يغير قيمته.
         */
        $.validator.addMethod('filesize', function (value, element, param) {
            var maxSize = parseFloat(param) * 1024 * 1024;
            return this.optional(element) || (element.files[0].size <= maxSize)
        }, 'File size must be less than {0} MB');

        $.validator.addMethod(
            "maxFileCount",
            /**
         * يرفض الاختيار عندما يتجاوز عدد element.files الحد param.
         * @param {string} value قيمة الإدخال؛ العدد يؤخذ من FileList.
         * @param {HTMLInputElement} element حقل الملفات.
         * @param {number} param أقصى عدد ملفات مسموح.
         * @returns {boolean} هل عدد الملفات أقل من الحد أو يساويه؟
         */
            function (value, element, param) {
                if (element.files.length > param) {
                    return false; // Invalid if the number of files exceeds the parameter
                }
                return true;
            },
            `You can only upload up to {0} files.` // Default error message
        );

        /**
         * يقارن value رقميًا بقيمة الحقل الذي يحدده params داخل النموذج الحالي؛ المساواة مرفوضة.
         * @param {string|number} value قيمة الحقل الجاري التحقق منه.
         * @param {HTMLElement} element عنصر الحقل؛ مقارنة القيم تعتمد على currentForm.
         * @param {string} params اسم الحقل الآخر داخل النموذج.
         * @returns {boolean} true عندما تتجاوز القيمة قيمة الحقل الآخر بعد parseFloat.
         */
        $.validator.addMethod("greaterThan", function (value, element, params) {
            var fromValue = $(this.currentForm).find(`[name=${params}]`).val()
            return parseFloat(value) > parseFloat(fromValue);
        }, "The 'To' value must be greater than the 'From' value.");

        /**
         * يقارن value رقميًا بالحقل المحدد في params ويسمح بالمساواة.
         * @param {string|number} value قيمة الحقل الحالي.
         * @param {HTMLElement} element عنصر الحقل؛ غير مستخدم مباشرة.
         * @param {string} params اسم الحقل المقارن داخل currentForm.
         * @returns {boolean} true إذا كانت القيمة أكبر أو مساوية بعد parseFloat.
         */
        $.validator.addMethod("greaterThanOrEqual", function (value, element, params) {
            var fromValue = $(this.currentForm).find(`[name=${params}]`).val()
            return parseFloat(value) >= parseFloat(fromValue);
        }, "The 'To' value must be greater than the 'From' value.");

        /**
         * يشترط أن يكون value بعد تاريخ الحقل params، ويسمح بغياب تاريخ البداية.
         * @param {string} value تاريخ النهاية الحالي.
         * @param {HTMLElement} element عنصر الحقل؛ غير مستخدم مباشرة.
         * @param {string} params اسم حقل تاريخ البداية.
         * @returns {boolean} true عند غياب البداية أو عندما تكون النهاية بعدها حصريًا.
         */
        $.validator.addMethod("afterDate", function (value, element, params) {
            var startDate = $(this.currentForm).find(`[name=${params}]`).val();
            if (!startDate) {
                return true;
            }
            return new Date(value) > new Date(startDate);
        }, "End date must be after start date.");

        /**
         * يشترط أن يكون value بعد تاريخ البداية params أو مساويًا له.
         * @param {string} value تاريخ النهاية الحالي.
         * @param {HTMLElement} element عنصر الحقل؛ غير مستخدم مباشرة.
         * @param {string} params اسم حقل تاريخ البداية.
         * @returns {boolean} true عند غياب البداية أو عندما تكون النهاية مساوية أو لاحقة.
         */
        $.validator.addMethod("afterOrEqualDate", function (value, element, params) {
            var startDate = $(this.currentForm).find(`[name=${params}]`).val();
            if (!startDate) {
                return true;
            }
            return new Date(value) >= new Date(startDate);
        }, "End date must be after or equal to the start date.");

        /**
         * يشترط أن يسبق value تاريخ النهاية params، ويسمح بغياب تاريخ النهاية.
         * @param {string} value تاريخ البداية الحالي.
         * @param {HTMLElement} element عنصر الحقل؛ غير مستخدم مباشرة.
         * @param {string} params اسم حقل تاريخ النهاية.
         * @returns {boolean} true عند غياب النهاية أو عندما تسبقها البداية حصريًا.
         */
        $.validator.addMethod("beforeDate", function (value, element, params) {
            var endDate = $(this.currentForm).find(`[name=${params}]`).val();
            if (!endDate) {
                return true;
            }
            return new Date(value) < new Date(endDate);
        }, "Start date must be before end date.");

        /**
         * يشترط أن يسبق value تاريخ النهاية params أو يساويه.
         * @param {string} value تاريخ البداية الحالي.
         * @param {HTMLElement} element عنصر الحقل؛ غير مستخدم مباشرة.
         * @param {string} params اسم حقل تاريخ النهاية.
         * @returns {boolean} true عند غياب النهاية أو عندما تكون البداية مساوية أو أسبق.
         */
        $.validator.addMethod("beforeOrEqualDate", function (value, element, params) {
            var endDate = $(this.currentForm).find(`[name=${params}]`).val();
            if (!endDate) {
                return true;
            }
            return new Date(value) <= new Date(endDate);
        }, "Start date must be before or Equal to the end date.");

        /**
         * يقارن تاريخ value بالوقت الحالي الفعلي؛ يجب أن يكون في المستقبل.
         * @param {string} value التاريخ المطلوب فحصه.
         * @param {HTMLElement} element عنصر الحقل؛ غير مستخدم مباشرة.
         * @returns {boolean} true إذا كانت القيمة بعد اللحظة الحالية، وليس بعد بداية اليوم فقط.
         */
        $.validator.addMethod("afterToday", function (value, element) {
            var today = new Date();
            var inputDate = new Date(value);
            return inputDate > today;
        }, "The date must be after today.");

        $.extend($.validator.messages, {
            required: "هذا الحقل إلزامي",
            remote: "يرجى تصحيح هذا الحقل للمتابعة",
            email: "رجاء إدخال عنوان بريد إلكتروني صحيح",
            url: "رجاء إدخال عنوان موقع إلكتروني صحيح",
            date: "رجاء إدخال تاريخ صحيح",
            dateISO: "رجاء إدخال تاريخ صحيح (ISO)",
            number: "رجاء إدخال عدد بطريقة صحيحة",
            digits: "رجاء إدخال أرقام فقط",
            creditcard: "رجاء إدخال رقم بطاقة ائتمان صحيح",
            equalTo: "رجاء إدخال نفس القيمة",
            extension: "رجاء إدخال ملف بامتداد موافق عليه",
            maxlength: $.validator.format("الحد الأقصى لعدد الحروف هو {0}"),
            minlength: $.validator.format("الحد الأدنى لعدد الحروف هو {0}"),
            rangelength: $.validator.format("عدد الحروف يجب أن يكون بين {0} و {1}"),
            range: $.validator.format("رجاء إدخال عدد قيمته بين {0} و {1}"),
            max: $.validator.format("رجاء إدخال عدد أقل من أو يساوي {0}"),
            min: $.validator.format("رجاء إدخال عدد أكبر من أو يساوي {0}"),
            accept: "الرجاء ادخل الملف بالصيغة المطلوبة",
            filesize: "حجم الملف يجب أن يكون أقل من {0} ميجا بايت",
            greaterThan: "قيمة '{1}' يجب أن تكون أكبر من قيمة '{0}'.",
            greaterThanOrEqual: "قيمة '{1}' يجب أن تكون أكبر من أو تساوي قيمة '{0}'.",
            afterDate: "تاريخ النهاية '{1}' يجب أن يكون بعد تاريخ البداية '{0}'.",
            afterOrEqualDate: "تاريخ النهاية '{1}' يجب أن يكون بعد أو يساوي تاريخ البداية '{0}'.",
            beforeDate: "تاريخ البداية '{0}' يجب أن يكون قبل تاريخ النهاية '{1}'.",
            beforeOrEqualDate: "تاريخ البداية '{0}' يجب أن يكون قبل أو يساوي تاريخ النهاية '{1}'.",
            afterToday: "التاريخ '{0}' يجب أن يكون بعد اليوم."
        });
    }
}
