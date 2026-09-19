# وحدات النماذج

يظل `../crud.js` هو مدخل Vite الذي تستدعيه Blade. يستورد الوحدات ويسجلها تحت `window.Dashboard.forms` و`window.Dashboard.fields`، ثم ينشر الأسماء القديمة التي تستدعيها Blade كطبقة توافق ويسجل أحداث الصفحة. لا تضف `<script>` لكل وحدة؛ Vite يتتبع الاستيرادات ويبني الأجزاء المطلوبة.

| الملف | ما الذي تعدّله هنا؟ | أهم الدوال |
| --- | --- | --- |
| `ajax.js` | سياسة الطلبات وCSRF وأخطاء HTTP | `configureAjax`، `ajax_exe` |
| `submission.js` | ما يحدث عند إرسال سجل أو كلمة مرور أو مفتاح | `customFunction`، `updatePassword`، `update_switch_val` |
| `events.js` | توصيل `data-event_on` و`data-call_function` ودورة حياة النوافذ والصلاحيات | `defineEvents`، `registerFormReady`، `registerSwitchReady`، `registerPermissionReady` |
| `validation-rules.js` | قواعد المقارنة والملفات والتواريخ وترجمة الرسائل | `registerValidationRules` |
| `validation.js` | شكل أخطاء الحقول وربط قواعد النموذج وSummernote | `defineFormValidation`، `validateSummerNotes` |
| `validation-errors.js` | أخطاء Laravel المتداخلة والتنقل إلى أول خطأ | `handleValidationErrors`، `scrollToElement`، `switchToTabWithError` |
| `modal-lifecycle.js` | إغلاق Bootstrap بعد اكتمال حركة الفتح أو الإغلاق | `closeModal` |
| `population.js` | تحميل سجل وتفريغ النموذج وتوزيع الترجمات | `populateFormFromAjax`، `cancelPendingFormPopulation`، `setFormData`، `resetTheForm`، `populateIdField`، `populateForm` |
| `fields.js` | نوع الحقل الذي ستذهب إليه كل قيمة | `populateField`، `populateRegularField`، `populatePermissions` |
| `repeaters.js` | إضافة صفوف مصفوفات النماذج وحذفها ومسحها | `addItemMultiForm`، `resetMultiForm`، `removeItemMultiForm`، `addValidationForRow` |
| `select2.js` | قوائم البحث والعلاقات والقوائم التابعة | `initializeSelect2`، `setupSelect2`، `updateSelect2Options`، `initSelect2`، `formatColorOption`، `getNestedValue` |
| `media.js` | الصور المحفوظة وDropzone والفيديو والمعاينات | `populateImages`، `populateCarousel`، `populateImage`، `populateVideo`، `removeVideoPreview` |
| `dropzones.js` | نسخة Dropzone مستقلة لكل حقل وجمع ملفات النموذج وتنظيف الحاوية | `createDropzone`، `getDropzones`، `destroyDropzones` |
| `editors.js` | إنشاء Quill لكل محرر ومزامنته مع الحقل المخفي وتنظيفه | `createEditor`، `destroyEditors` |
| `maps.js` | adapters متعددة النسخ لحدود Leaflet وموقع Google Maps | `createBoundaryMap`، `createLocationMap`، `destroyMaps` |
| `feedback.js` | رسائل Bootstrap Toast ومؤشر الصفحة | `showToast`، `togglePageLoader` |
| `language-input.js` | قيود ضغطات المفاتيح بالعربية والإنجليزية | `restrictInputToLanguage` ونسختا jQuery والنموذج |
| `formatting.js` | اتجاه اللغة وشكل التاريخ | `direction`، `formatDateAgo` |
| `urls.js` | استبدال معرف السجل في الروابط | `replaceUrlPlaceholder`، `replaceUrlPlaceholder_v2`، `isValidUrl` |
| `date-range.js` | منتقي نطاق تاريخ الحجز | `initializeBookingRange` |

كل دالة مسماة موثقة بالعربية بجانب تعريفها مع المدخلات والنتيجة أو أثرها على الصفحة. لتعديل حالة معينة ابدأ بالملف المسؤول في الجدول بدل تعديل مدخل `crud.js`.

## دورة النموذج

1. Blade تضيف `data-event_on="submit"` و`data-call_function="اسم_الدالة"` إلى النموذج.
2. `defineEvents` يمرر الحدث ونسخة jQuery من العنصر إلى الدالة المسجلة على `window`.
3. `defineFormValidation` يربط قواعد الحقول؛ `ajax_exe` يتولى إرسال `FormData`.
4. عند خطأ 422 يحوّل `handleValidationErrors` اسمًا مثل `rooms.0.name` إلى `rooms[0][name]`، ثم يعرض الخطأ في الحقل والتبويب المناسبين.
5. عند التعديل، يتوقع `populateFormFromAjax` استجابة بالشكل `{ "data": { ...حقول السجل } }`؛ الكائن المترجم `{ "ar": "...", "en": "..." }` يذهب إلى حقول ذات لاحقة `_ar` و`_en`.

عند إضافة معالج تستخدمه Blade، صدّره من الوحدة المسؤولة وسجله عبر `registerDashboardApi` داخل `crud.js`. الدوال الداخلية تظل استيرادات ES Modules ولا تحتاج alias عامًا.

## النماذج التي تتغير عبر AJAX

الأحداث مفوضة على document وبأسماء خاصة لكل وحدة، فتعمل مع النماذج الجديدة دون إعادة تسجيلها. مفتاح تحديد كل الصلاحيات يؤثر في أقرب form فقط. قراءة التعديل تحتفظ بهوية عنصر النموذج وتلغي الطلب السابق عليه؛ `cancelPendingFormPopulation(host)` يلغي قراءات GET في الحاوية ويعيد عددها. لا يلغي طلب حفظ بدأ بالفعل.

`customFunction` يحتفظ بالنموذج والجدول وDropzone وقت الإرسال؛ إذا استبدل التبويب قبل رد النجاح، يتجاوز الآثار التي كانت ستغير النموذج الجديد. `closeModal(element)` يعيد وعدًا ينتظر اكتمال انتقال Bootstrap قبل إزالة النماذج في منسق التبويبات. الحقول التي يعلنها المولد في `data-dashboard-field-types` تملأ كحقول عادية حتى لو وافق اسمها اسمًا قديمًا خاصًا مثل image أو lat.

[المرجع الكامل](../../../../docs/dashboard/javascript-reference.md) يحتوي signatures ومسار القراءة والحفظ وتفسير جميع callbacks المسماة وأسماء window العامة.

## الاعتماديات والعقود الحالية

الوحدات تستخدم مكتبات الصفحة القائمة: jQuery وjQuery Validate وBootstrap، ومعها Select2 وDropzone وQuill وLeaflet أو Google Maps عندما تطلب صفحة Blade هذه الأنواع. الرسائل مثل `notifySuccess` وبيانات `update_inputs` و`currentLocale` تأتي من القالب الحالي. لا تضف أيًا منها كحزمة جديدة لمجرد تقسيم الملفات.

معالج المفتاح المخصص يستقبل `{value,id}` مباشرة ويستخدم حدث `change` الممرر إليه؛ لا يحتاج نموذجًا مؤقتًا أو `$obj` عامًا، ويتركه معالج مفاتيح الجدول دون إرسال طلب ثانٍ. أزيل التنبيه التشخيصي القديم من اختيار نطاق الحجز.


## التحقق

من جذر المشروع:

```sh
node --test tests/JavaScript/forms*.test.js
npm run build
```

الاختبارات تغطي تسجيل واجهة Blade قبل جاهزية DOM، تمرير الأحداث، عقد AJAX، أخطاء الحقول المتكررة، تعبئة الترجمات والمفاتيح والقوائم، قواعد اللغة والتنسيق، وبيانات الرسوم وتحديثها. اختبارات Node لا تستبدل فحص إضافات المتصفح الفعلية والخرائط ورفع الملفات في بيئة Laravel مكتملة.
