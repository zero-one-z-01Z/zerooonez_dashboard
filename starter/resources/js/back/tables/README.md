# دورة عمل جدول الداشبورد

تستدعي Blade مدخل `page-table.js` للجدول العادي أو `item-table.js` لجداول التفاصيل. كلاهما يستورد `table.js` استيرادًا ثابتًا؛ وهو يجمع واجهة CRUD ومحرك DataTables، ثم يسجل الأسماء القديمة على `window` ويطلق `dashboard:table-api-ready`. لا تحتاج صفحة جديدة إلى نسخ هذه الملفات. [مرجع الدوال والعقود الكامل](../../../../docs/dashboard/javascript-reference.md) يشرح كل وحدة وموضع تعديلها.

1. ترسل الصفحة تعريف الكنترولر إلى `window.prepareDataTable(data)`.
2. تنقل `configuration.js` الروابط والحقول والفلاتر والصلاحيات إلى الحالة الحالية. كل تعريف جديد يستبدل الفلاتر الداخلية وإعداد الرفع حتى لا يرث تبويب AJAX إعدادات سابقه.
3. يستدعي `window.setUpDatatable()` وحدة `lifecycle.js` لإنشاء الأعمدة ومحرك الجدول ثم حقول النماذج وقواعدها. استدعاء الدالة مرتين على نفس الجدول يرجع المثيل الموجود.
4. في صفحات التفاصيل، تحمل [وحدة التبويبات](../item-tables/README.md) تعريف الجدول وHTML نماذجه معًا. تغلق النوافذ القديمة وتحرر أدواتها، وتدمر الجدول السابق، ثم تركب النماذج الجديدة قبل `prepareDataTable` و`setUpDatatable`.

| التعديل المطلوب | مكانه |
| --- | --- |
| إضافة مفتاح مشترك لتعريف الشاشة | `configuration.js` |
| دعم طريقة عرض عمود جديدة | `columns.js`، داخل `renderColumn` |
| النص المختصر وزر المزيد | `text.js` |
| قوائم البحث والعلاقات والصفحات | `select-options.js` للمنطق، و`fields.js` لربط Select2 |
| محررات HTML | `fields.js`، داخل `initializeEditor` |
| قواعد نماذج الإضافة والتعديل والنوافذ | `validation.js` |
| طلب إضافة/تعديل/حذف أو تغيير switch | `records.js` |
| إظهار الأعمدة ودورة إنشاء وتدمير الجدول | `lifecycle.js` |
| إعدادات DataTables وتنسيق شريطه | `../datatables/engine.js` |
| صلاحيات وشروط وإجراءات كل صف | `../datatables/actions.js` |
| التصدير وأزرار الإضافة والاستيراد والحذف | `../datatables/toolbar.js` |
| قيم فلاتر الطلب وأولوية الفلاتر الداخلية | `../datatables/filter-values.js` |
| تطبيق ومسح الفلاتر وشاراتها والتاريخ | `../datatables/filters.js` |
| تأكيد الحذف والتحديد الجماعي | `../datatables/deletion.js` |
| نافذة الإضافة والاستيراد وDropzone | `../datatables/modals.js` |
| خرائط الحدود والموقع | `../datatables/maps.js` |
| استبدال `#placeholder#` بمعرف السجل | `../datatables/urls.js` |

تعريف الحقل `text` يعرض نصا مهربا. استخدم `html_text` فقط عندما يكون المحتوى HTML مقصودا من محرر الصفحة. تحقق الصلاحيات في JavaScript يحدد ظهور الإجراء؛ صلاحيات المسارات والتحقق من البيانات تظل مسؤولية Laravel.

لا تعدل أسماء `window.create_item` و`window.update_item` و`window.fill_form` وغيرها دون تحديث `data-call_function` في Blade. كل دالة مسماة في الوحدات تحمل تعليقا عربيا يوضح مدخلاتها ونتيجتها وما تغيره.

لتشغيل اختبارات منطق الجداول والتوافق مع الأسماء العامة:

```sh
node --test tests/JavaScript/tables*.test.js
```

هذه اختبارات Node بوحدات وواجهة DOM محدودة لمحاكاة العقود؛ لا تغني عن تجربة المتصفح مع ملفات jQuery وDataTables وSelect2 وQuill الفعلية وLaravel متصل بقاعدة بيانات اختبار.
