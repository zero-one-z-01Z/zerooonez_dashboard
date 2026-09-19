# محرك DataTables وأدواته

`../datatable-init.js` يسجل أسماء Blade العامة. ينشئ `engine.js` الجدول من الأعمدة والإجراءات والفلاتر التي جهزتها `../tables`، ثم يربط أدواته بمثيل DataTables الجديد. لا تخلط بين **تعريف الشاشة** في `tables/configuration.js` و**خيارات المكتبة** في `engine.js`.

| الملف | نقطة التعديل |
| --- | --- |
| [engine.js](engine.js) | طلب POST، serverSide، تخطيط الجدول، وأحداث الرسم. |
| [actions.js](actions.js) | صلاحيات وشروط إجراءات السجل وأعمدة التحكم والتحديد. |
| [filter-values.js](filter-values.js) | تحويل مصادر الفلاتر إلى حمولة request.filters. |
| [filters.js](filters.js) | تطبيق الفلاتر ومسحها وشاراتها ونطاق التاريخ. |
| [deletion.js](deletion.js) | تأكيد الحذف وتحديد الكل وتجميع ids[]. |
| [toolbar.js](toolbar.js) | الإضافة والاستيراد والتصدير والطباعة والحذف الجماعي. |
| [modals.js](modals.js) | فتح الإضافة والاستيراد وتجهيز أدوات النموذج. |
| [maps.js](maps.js) | خرائط الإضافة وكتابة إحداثياتها. |
| [urls.js](urls.js) | استبدال معرفات روابط السجلات؛ النسخة V2 لا تعدل DOM. |

اقرأ [مرجع الدوال](../../../../docs/dashboard/javascript-reference.md) قبل تغيير توقيع دالة عامة. خيارات `initializeDataTable(..., options)` تتغلب على الافتراضيات؛ callbacks المكتبة مثل `render` و`data` و`action` موثقة داخل إعدادها. أدوات الواجهة لا تمنح صلاحية تنفيذ؛ Laravel يفحص الصلاحيات والحقول عند كل طلب.
