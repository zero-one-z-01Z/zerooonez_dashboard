# دليل كنترولرز الداشبورد

كل شاشة قديمة أصبحت تحتوي تعريف موردها ودوال العمل الخاصة بها، بينما تجميع بيانات الصفحة وتشغيل DataTables موجودان في ملفين مشتركين. يوجد PHPDoc عربي فوق كل دالة مسماة في الكنترولرز الأصلية: الغرض، المدخلات، النتيجة، وما تنفذه من حفظ أو إرسال أو تغيير حالة.

## اقرأ الملفات بهذا الترتيب

1. [BrandController](../../app/Http/Controllers/Admin/BrandController.php): مثال بسيط على الاسم العربي والإنجليزي، الجدول، الإضافة، التعديل والاستيراد.
2. [AreaController](../../app/Http/Controllers/Admin/AreaController.php): مثال علاقة city وحدود المنطقة وحقول Select2.
3. [BuildsDashboardPage](../../app/Http/Controllers/Admin/Concerns/BuildsDashboardPage.php): يجمع بيانات Blade وروابط الشاشة.
4. [HandlesDashboardTable](../../app/Http/Controllers/Admin/Concerns/HandlesDashboardTable.php): يشغل مسار طلب الجدول.
5. [GeneratedResourceController](../../app/Http/Controllers/Admin/GeneratedResourceController.php): نقطة البداية للصفحات الجديدة التي ينشئها المولّد من تعريف الحقول.

## دورة الشاشة القديمة

```text
route في admin
  → index / prepareData
  → dashboardPageSections: العنوان + المرشحات + الإحصاءات + الأعمدة + النماذج + الأزرار
  → تعديلات الدور الخاصة بالكنترولر، إن وجدت
  → dashboardPageData: إعدادات الشاشة + الروابط + الإضافات
  → dashboard.screen.table_view
  → JavaScript يقرأ data ويرسل طلب الجدول
  → get_datatable
  → dashboardTableData
  → initializeDataTableQuery + preprocessFilters + applyCustomFilters
  → DataTableService: البحث + التصفية + الترتيب + الترقيم
  → JSON يعرضه الجدول
```

وجود `prepareData` في بعض الكنترولرز مقصود: تستخدمه الصفحة والتبويبات التي تطلب تعريف جدول آخر عبر `get_data`. لا تغير شكل الاستجابة لتلك الدوال عند إضافة شاشة متداخلة.

## الفرق بين دوال الطلبات

| الدالة | متى تستدعيها الواجهة؟ | ماذا تعيد؟ |
| --- | --- | --- |
| `index()` | فتح صفحة جدول مستقلة | View فيه تعريف الشاشة داخل `data` |
| `show($id)` | فتح تفاصيل سجل يدعمها كنترولره | View للتفاصيل، وقد يحتوي روابط `tabs` ومرشحات السجل الأب |
| `prepareData()` | تستدعيها `index` و`get_data` داخل PHP | مصفوفة إعدادات؛ ليست استجابة HTTP ولا قائمة سجلات |
| `get_data()` | اختيار تبويب داخل صفحة تفاصيل | `status/message/data` لتعريف الجدول، و`fragments.modals` للنماذج المرسومة |
| `get_datatable(Request)` | أول عرض للجدول، ثم البحث والترتيب والانتقال بين الصفحات | `draw/recordsTotal/recordsFiltered/data`، حيث `data` هنا صفوف فعلية |
| `get_single_item($id)` | الضغط على تعديل أو إجراء يحتاج تعبئة نموذج | سجل واحد داخل `data`، بالحقول والعلاقات التي يحددها الكنترولر |
| `get_list(Request)` | البحث في قائمة Select2 | اختيارات قائمة؛ التنفيذ الخاص بكل مورد هو المرجع لشكلها |
| `store/update/delete/delete_all` | حفظ نموذج أو تنفيذ الحذف | نتيجة العملية وفق الكنترولر والصلاحيات الخاصة بها |

تشابه أسماء `data` لا يعني تشابه محتواها: `get_data` يبني الشاشة، و`get_datatable` يقرأ صفوفها. المولّد الحالي ينشئ صفحة مستقلة بدوال CRUD الفعلية ولا يضيف `show` أو `get_data` تلقائيا. كذلك الحزمة القديمة `all_routes()` تسجل بعض أسماء الدوال حتى عندما لا ينفذها كل كنترولر؛ راجع الكنترولر قبل استخدام أي مسار كتبويب.

## أين تعدل كل جزء؟

| الجزء | مكان التعديل | العقد الذي تعتمد عليه الواجهة |
| --- | --- | --- |
| الموديل | `model()` | يعيد Eloquent Builder، وليس مجموعة نتائج |
| الأعمدة القابلة للبحث | `$columns` و`get_datatable()` | أسماء أعمدة SQL ومسارات علاقات معلومة |
| العلاقات وأعمدة العرض الإضافية | `initializeDataTableQuery()` | `select` و`with` والقيود الخاصة بالدور |
| شكل خلايا الجدول | `show_list()` | `key` و`title` و`type` مثل text أو image أو switch |
| أزرار الصف | `datatable_actions()` | نوع التفاعل والرابط والصلاحية واسم دالة JavaScript |
| مرشحات الشاشة | `filters()` | تعريف المدخلات وروابط قوائم Select2 |
| مرشح لا تعالجه الخدمة العامة | `preprocessFilters()` ثم `applyCustomFilters()` | استبعاد المفتاح من dataTableFilters ومعالجته في الاستعلام المخصص |
| نموذج الإضافة | `inputs_list()` | `id` و`input` و`type` و`validation` |
| نموذج التعديل | `update_inputs_list()` | نفس العقد مع id مخفي وحقول تعبئة العلاقات إن لزم |
| نافذة إضافية | `modals()` | `id` و`form_id` و`link` و`inputs` وإعدادات الأزرار |
| حفظ بيانات المورد | `store()` و`update()` | قواعد المورد والعلاقات والملفات والخدمات الخارجية |
| الحذف | `delete()` و`delete_all()` | تبقى شروط المورد وأحداث Eloquent في هذا المسار |

تعريف `validation` في مدخلات الشاشة يخص تجربة الواجهة. عند كتابة عملية جديدة، ضع قواعد Laravel المناسبة على الخادم وحدد الحقول المسموح بحفظها. الكنترولر المولد يستخدم `validatedData()` لهذا الغرض.

## بناء بيانات الصفحة دون تكرار

| الدالة المشتركة | مدخلاتها | ما تفعله وأين يمكن تخصيصها |
| --- | --- | --- |
| `dashboardPageSections()` | خصائص ودوال الكنترولر | يحسب العنوان والمرشحات والإحصاءات والأعمدة ومدخلات الإضافة والتعديل وأزرار الصف بالترتيب القديم |
| `dashboardPageData($sections, $extra)` | الأقسام المحسوبة وإضافات الشاشة | يربط الأقسام بأعلام العرض وروابط CRUD المسماة؛ `$extra` يتقدم عند تكرار المفتاح |
| `dashboardTabResponse($data)` | التعريف الكامل للتبويب | يحتفظ بغلاف `successResponse` ويضيف HTML النوافذ من `modal-content` |
| `dashboardTableData($request, $columns, $sortBy, $direction)` | طلب الصفوف والأعمدة وترتيب اختياري | ينشئ استعلام المورد ويفصل المرشحات ويشغل `DataTableService`، ثم يعيد مصفوفة قابلة للتجهيز قبل JSON |
| `preprocessFilters($filters)` | مرشحات الطلب الأصلية | يعيد الأصل في `filters` وما يصل للخدمة العامة في `dataTableFilters`؛ يمكن للكنترولر استبعاد مفاتيحه الخاصة |
| `applyCustomFilters($query, $filters)` | الاستعلام والمرشحات الأصلية | يضيف شروط المورد إلى نفس Builder؛ التطبيق الافتراضي فارغ |

النمط المعتاد في الكنترولر القديم:

```php
public function index()
{
    $sections = $this->dashboardPageSections();
    $data = $this->dashboardPageData($sections, [
        'columns' => $this->columns,
        'modals' => $this->modals(),
    ]);

    return view('dashboard.screen.table_view', compact('data'));
}
```

الإضافات اختيارية وصريحة: `columns` و`modals` و`map` و`boundary` و`have_import`. لا تضف المفتاح تلقائيا لكل الشاشات؛ بعض الشاشات القديمة لا ترسله أصلا. القيم الإضافية تتقدم على القيم العامة عند وجود نفس المفتاح، مثل صلاحيات الإضافة والحذف في شاشة موظفي خدمة العملاء.

`dashboardPageSections()` يحسب الأقسام أولا، و`dashboardPageData()` يقرأ إعدادات الكنترولر عند استدعائه. هذا الفصل يحافظ على ترتيب تغيير روابط الشركة في شاشات الفحص والمزادات. راجع `ScannerController::prepareData()` قبل نقل شرط دور من مكانه.

الأسماء القديمة مثل `daterannge_filter_name` والرمز `#placeholder#` جزء من العقد الحالي بين PHP وBlade وJavaScript. تغييرها يتطلب تحديث جميع المستهلكين معا.

## تشغيل الجدول وتخصيص مرشح

الشاشة البسيطة تحتاج فقط:

```php
public function get_datatable(Request $request)
{
    return response()->json(
        $this->dashboardTableData($request, $this->columns)
    );
}
```

إن احتجت البحث في علاقة، أضف مسارها إلى قائمة الأعمدة الممررة مع تحميل العلاقة في `initializeDataTableQuery()`. شاشات CarOption وFeature وPart تمرر أيضا `'sort_number', 'asc'` للحفاظ على ترتيبها الخاص. بقية الشاشات تترك قيم الترتيب `null` لسلوك DataTableService الحالي.

عند وجود مرشح خاص، تعرف نفس الدالتين داخل الكنترولر؛ تعريف الكنترولر يتقدم على التعريف الافتراضي في الـ trait. المثال التالي يوضح فرع `buyer_id` الموجود في AuctionController، والذي يقابل عمود `best_user_id`. الكنترولر الكامل يعالج أيضا `company_id` و`user_id`؛ احتفظ بتلك الفروع عند تعديله:

```php
private function preprocessFilters(array $filters): array
{
    $general = $filters;
    unset($general['buyer_id']);

    return ['filters' => $filters, 'dataTableFilters' => $general];
}

private function applyCustomFilters($query, $filters)
{
    if (!empty($filters['buyer_id'])) {
        $query->where('best_user_id', $filters['buyer_id']);
    }
}
```

النسخة الافتراضية تمرر المرشحات دون استبعاد، ولا تضيف شروطا مخصصة. لا تحتاج إلى نسخ دالتين فارغتين في كل كنترولر جديد. الدالة المشتركة تعيد مصفوفة؛ لذلك يظل بإمكان AuctionController ترجمة بعض قيم الصفوف قبل تحويلها إلى JSON.

## كيف تصل نماذج التبويب الجديد؟

المشكلة كانت أن التبويب يجلب تعريف الجدول، بينما نوافذ الإضافة والتعديل لا تأتي معه. `@push('modals')` يضيف المحتوى إلى stack في صفحة Blade الكاملة؛ لا يرسل هذا المحتوى تلقائيا داخل رد AJAX.

أصبح [modal-content.blade.php](../../resources/views/dashboard/admin-components/modal-content.blade.php) هو محتوى النوافذ المشترك. [all-modals.blade.php](../../resources/views/dashboard/admin-components/all-modals.blade.php) ما زال يدفعه إلى stack في صفحات الجداول العادية. أما `dashboardTabResponse` فيرسم نفس المحتوى مباشرة ويرسله مع تعريف التبويب:

```php
public function get_data()
{
    $data = $this->prepareData();
    return $this->dashboardTabResponse($data);
}
```

شكل الرد، مع اختصار المحتوى للتوضيح:

```json
{
  "status": "Success",
  "message": null,
  "data": {"title": "عنوان الشاشة", "show_list": [], "inputs": [], "update_inputs": []},
  "fragments": {"modals": "<div class=\"modal\" id=\"add_modal\">...</div>..."}
}
```

هذا مثال لشكل الرد فقط؛ `data` الفعلية تحتوي أيضا الروابط والصلاحيات وبقية إعدادات الجدول، وHTML يحتوي النموذجين والنوافذ المخصصة. كود `item-tables/navigation.js` يستبدل حاوية نوافذ الجدول بالتزامن مع تعريفه، ثم يهيئ الحقول والتحقق. يحتفظ `internal_filter` بمرشح سجل التفاصيل، بينما تعريف التبويب يستطيع إضافة مرشحات داخلية خاصة به.

| مصدر تعريف التبويب | نقاط الوصول التي تستعمل الرد الجديد |
| --- | --- |
| `AuctionController` | `/admin/auctions/get_data` و`/company/auctions/get_data` |
| `AuctionController::get_data_buyer($id)` | `/admin/auctions/get_data_buyer/{id}`؛ يضيف `buyer_id` إلى `data.internal_filters` |
| `AuctionFeaturesController` | `/admin/auction_features/get_data` ونظيره `/company/...` |
| `AuctionCarOptionsController` | `/admin/auction_car_options/get_data` ونظيره `/company/...` |
| `AuctionReportController` | `/admin/auction_reports/get_data` ونظيره `/company/...` |
| `BillController` | `/admin/bills/get_data` |
| `CouponsController` | `/admin/coupons/get_data` |
| `SellerCouponsController` | `/admin/seller_coupons/get_data` |
| `TicketController` | `/admin/tickets/get_data` |

هي 9 دوال فعلية على 13 مسارا. المستهلكان الحاليان في صفحات التفاصيل هما `UserController::show` لتبويبات المزادات والمشتريات والفواتير، و`AuctionController::show` لتبويبات الخصائص والتجهيزات والتقارير، مع نسخ الشركة. وجود endpoints أخرى يجعلها جاهزة للاستخدام عند إضافة روابطها إلى تعريف `tabs`.

## مثال تعديل حقل موجود

في [BrandController::inputs_list](../../app/Http/Controllers/Admin/BrandController.php) يوجد حقلا `name_ar` و`name_en`. تغيير `label` يغير عنوان الحقل، وإضافة `width => 12` تجعل عرضه كامل الصف في الشبكة الحالية، وتغيير `validation` يغير تحقق المتصفح. لأن `update_inputs_list()` يعيد استخدام `inputs_list()` هنا، تنعكس هذه التغييرات على النموذجين. أما عنوان عمود الجدول فمصدره `show_list()`، وقواعد حفظ البيانات فمصدرها `store()` و`update()`.

عند إضافة حقل جديد فعليا، راجع المواضع المترابطة: عمود قاعدة البيانات، تعريف الموديل، حقول النموذج، أعمدة الاستعلام والعرض، ومنطق الحفظ. وجود حقل في Blade وحده لا يجعله محفوظا تلقائيا في الكنترولرز القديمة.

## نموذج تعديل يعيد استخدام الإضافة

إذا كانت حقول النموذجين متطابقة تماما باستثناء المعرف، استخدم:

```php
public function update_inputs_list()
{
    return array_merge([['id' => 'id', 'input' => 'hidden']], $this->inputs_list());
}
```

تم تطبيق ذلك على 16 شاشة فقط بعد مطابقة التعريفين. في شاشات مثل Area، يحتاج التعديل `key_name` و`value_name` لتعبئة العلاقة؛ وفي شاشات الملفات قد يختلف كون الملف مطلوبا. تظل هذه التعريفات منفصلة حيث يوجد اختلاف فعلي.

## إنشاء شاشة جديدة

المولّد يكتب كنترولرا صغيرا يرث من `GeneratedResourceController` ويعيد تعريف المورد من `definition()`. يتكون التعريف من الموديل، اسم المورد، بادئة الصلاحية، عنوان الصفحة، الحقول وخيار التواريخ. تقوم خدمات `ResourceDefinition` و`ResourcePage` و`ResourceTable` بترجمة هذا التعريف إلى تحقق الخادم ونماذج Blade وطلب الجدول.

بعد التوليد، تستطيع تخصيص `model()` لإضافة نطاق بيانات، أو `validatedData()` لإضافة قواعد، أو عملية محددة مثل `store()` عند وجود علاقة أو تدفق عمل. العمليات المالية أو نشر المزادات أو مراسلة العملاء تحتاج قواعدها الخاصة وخدماتها الموجودة في المشروع؛ تعريف الحقول وحده لا يصف تلك العمليات.

الشرح الكامل للدوال الـ13 في `GeneratedResourceController`، وخدمات بناء الصفحة والسجلات والملفات، موجود في [دليل المولّد](generator.md). `DashboardBuilderController` نفسه يدير شاشة إنشاء المصدر ومعاينته وكتابته؛ لا يدير سجلات الموارد التي تنشئها الأداة.

الكنترولرز القديمة تستعمل الـ concerns للاحتفاظ بعقودها، والصفحات المولدة تستعمل تعريف المورد المختصر. كلا المسارين يعرض قالب `table_view` نفسه.

يقرأ `ResourceRegistry` التعريفات الصالحة ويضيف مجموعات صلاحياتها إلى محرر الأدوار بعنوان الصفحة نفسه. إذا اشترك موردان في بادئة الصلاحية، تظهر مجموعة واحدة وتندمج الأفعال الناقصة؛ تظل المجموعات القديمة بترتيبها وعناوينها. تشغيل seeder يضيف مفاتيح الصلاحيات إلى قاعدة البيانات فقط، ثم تختار الدور الذي تمنحها له من شاشة الصلاحيات.

## التحقق من إعادة التنظيم

- أزيل تكرار تجميع الصفحة وتشغيل الجدول من 44 كنترولرا.
- انتقلت 40 دالة تمرير مرشحات و38 دالة تصفية فارغة إلى التطبيق الافتراضي المشترك.
- أعيد استخدام حقول الإضافة في 16 نموذج تعديل متطابق.
- وثقت 823 دالة متبقية في الكنترولرز الـ46 الأصلية، مع توثيق الدوال المشتركة الجديدة.
- بقي كود عمليات الحفظ والحذف والمصادقة والمزادات والإشعارات والمحافظ كما كان؛ هذه الخطوة تعيد التنظيم ولا تصلح تلقائيا مشكلات أعمال سابقة.

شغل فحص المقارنة على نسخة المشروع الأصلية:

```bash
php tests/Standalone/dashboard-controller-refactor.php /path/to/original-project
```

يشغل الفحص دوال الصفحة والجدول الفعلية في النسختين داخل عمليتين منفصلتين. يستخدم بدائل معزولة لـLaravel والاستعلامات حتى لا يصل إلى قاعدة بيانات أو بريد أو إشعارات، وسجل موارد مولدة فارغا صراحة لقياس توافق الشاشات القديمة. يقارن 960 حالة: بيانات الصفحة وترتيب حساب الأقسام وتعديلات الخصائص، شروط الاستعلام والمرشحات والترتيب واستجابة DataTables، وحقول نماذج التعديل المعاد استخدامها. تشمل السيناريوهات الإدارة مع الصلاحيات وبدونها، ارتباط خدمة العملاء والموظف، ودخول الشركة. يغطي `GeneratedPermissionEditorTest` داخل اختبارات Laravel ظهور الصلاحيات الجديدة وحفظها واستعادتها.

هذا الفحص يثبت توافق الجزء الذي أعيد تنظيمه. اختبار اتصال قاعدة البيانات ونتائج SQL الفعلية وتدفقات الأعمال والواجهة داخل المتصفح له نطاق آخر ويحتاج بيئة Laravel وبيانات اختبار مناسبة.

بعد إصلاح التبويبات أضيف [DashboardTabFragmentsTest](../../tests/Feature/Dashboard/DashboardTabFragmentsTest.php): يرسل HTTP إلى المسارات الـ13 الفعلية ويرسم Blade، ويتحقق من عدم تكرار النماذج عند العودة إلى تبويب سابق، ومن استمرار صفحة `/admin/coupons` العادية. بيانات الدخول والجداول المستخدمة فيه معزولة في SQLite داخل الذاكرة. راجع سجل التحقق العام لمعرفة الأوامر والنتائج الأحدث؛ نجاح هذه الاختبارات لا يعني اختبار كل عملية أعمال قديمة على بيانات فعلية.

## خريطة الكنترولرز الأصلية

| الكنترولر | الموديل | مفتاح الشاشة | المسارات المتخصصة |
| --- | --- | --- | --- |
| [AdminController](../../app/Http/Controllers/Admin/AdminController.php) | `Admin` | `admins` | `login`, `forget_password`, `reset_password`, `update_password`, `send_forget_password`, `send_reset_password`, `send_login`, `get_list_super`, `get_list_super_customer_service`, `update_admin_tickets`, `update_active`, `logout` |
| [AreaController](../../app/Http/Controllers/Admin/AreaController.php) | `Area` | `areas` | تعريف المورد وعملياته |
| [AreaTimeController](../../app/Http/Controllers/Admin/AreaTimeController.php) | `AreaTime` | `area_times` | تعريف المورد وعملياته |
| [AuctionCarOptionsController](../../app/Http/Controllers/Admin/AuctionCarOptionsController.php) | `AuctionCarOption` | `car_options` | تعريف المورد وعملياته |
| [AuctionController](../../app/Http/Controllers/Admin/AuctionController.php) | `Auction` | `auctions` | `get_data_buyer`, `show`, `publish`, `cartrust_pdf`, `send_notification`, `reset`, `send_email_for_transportation`, `update_is_soon`, `exportPdf`, `available_times` |
| [AuctionFeaturesController](../../app/Http/Controllers/Admin/AuctionFeaturesController.php) | `AuctionFeature` | `features` | تعريف المورد وعملياته |
| [AuctionReportController](../../app/Http/Controllers/Admin/AuctionReportController.php) | `AuctionReport` | `reports` | تعريف المورد وعملياته |
| [BannerController](../../app/Http/Controllers/Admin/BannerController.php) | `Banner` | `banners` | تعريف المورد وعملياته |
| [BillController](../../app/Http/Controllers/Admin/BillController.php) | `Bill` | `bills` | تعريف المورد وعملياته |
| [BrandController](../../app/Http/Controllers/Admin/BrandController.php) | `Brand` | `brands` | `import` |
| [BrandModelClassController](../../app/Http/Controllers/Admin/BrandModelClassController.php) | `BrandModelClass` | `brand_model_classes` | `import` |
| [BrandModelController](../../app/Http/Controllers/Admin/BrandModelController.php) | `BrandModel` | `brand_models` | `import` |
| [CarOptionController](../../app/Http/Controllers/Admin/CarOptionController.php) | `CarOption` | `car_options` | `update_required` |
| [CityController](../../app/Http/Controllers/Admin/CityController.php) | `City` | `areas` | تعريف المورد وعملياته |
| [CityTransportationController](../../app/Http/Controllers/Admin/CityTransportationController.php) | `CityTransportation` | `transportations` | `import` |
| [CouponsController](../../app/Http/Controllers/Admin/CouponsController.php) | `Coupon` | `coupons` | تعريف المورد وعملياته |
| [CustomerServiceController](../../app/Http/Controllers/Admin/CustomerServiceController.php) | `CustomerService` | `customer_service` | `show`, `update_active`, `login`, `update_password`, `send_login`, `logout`, `change_lang` |
| [CustomerServiceEmployeeController](../../app/Http/Controllers/Admin/CustomerServiceEmployeeController.php) | `CustomerServiceEmployee` | `customer_service_employees` | `add_wallet`, `update_active` |
| [CustomerServiceEmployeeFeeController](../../app/Http/Controllers/Admin/CustomerServiceEmployeeFeeController.php) | `CustomerServiceEmployeeFee` | `customer_service_employee_fees` | تعريف المورد وعملياته |
| [CustomerServiceExtraFeesController](../../app/Http/Controllers/Admin/CustomerServiceExtraFeesController.php) | `CustomerServiceExtraFee` | `customer_service_fees` | تعريف المورد وعملياته |
| [EmailTypeController](../../app/Http/Controllers/Admin/EmailTypeController.php) | `EmailType` | `emails` | تعريف المورد وعملياته |
| [FaqCategoryController](../../app/Http/Controllers/Admin/FaqCategoryController.php) | `FaqCategory` | `faq_categories` | تعريف المورد وعملياته |
| [FaqController](../../app/Http/Controllers/Admin/FaqController.php) | `Faq` | `faqs` | تعريف المورد وعملياته |
| [FeatureController](../../app/Http/Controllers/Admin/FeatureController.php) | `Feature` | `features` | `update_required` |
| [HighlightsController](../../app/Http/Controllers/Admin/HighlightsController.php) | `Highlight` | `highlights` | تعريف المورد وعملياته |
| [HomeController](../../app/Http/Controllers/Admin/HomeController.php) | `عدة موديلات` | `home` | `compare_month`, `dailyCountsArray`, `prepare_data`, `getChangeGrowth`, `getOrderSales`, `getCircleChartPrepare`, `byYear`, `change_lang` |
| [HowKnowUsController](../../app/Http/Controllers/Admin/HowKnowUsController.php) | `HowKnowUs` | `how_know_us` | تعريف المورد وعملياته |
| [IgnoreDateController](../../app/Http/Controllers/Admin/IgnoreDateController.php) | `IgnoreDate` | `ignore_dates` | تعريف المورد وعملياته |
| [InsuranceController](../../app/Http/Controllers/Admin/InsuranceController.php) | `Insurance` | `insurance` | تعريف المورد وعملياته |
| [InsurancePackageController](../../app/Http/Controllers/Admin/InsurancePackageController.php) | `InsurancePackage` | `insurance_packages` | تعريف المورد وعملياته |
| [NotificationController](../../app/Http/Controllers/Admin/NotificationController.php) | `Notification` | `notifications` | `send_noti`, `resend`, `store_noti` |
| [OptionalPhonesController](../../app/Http/Controllers/Admin/OptionalPhonesController.php) | `OptionalPhone` | `optional_phones` | تعريف المورد وعملياته |
| [PartController](../../app/Http/Controllers/Admin/PartController.php) | `Part` | `parts` | تعريف المورد وعملياته |
| [PermissionController](../../app/Http/Controllers/Admin/PermissionController.php) | `Role` | `permissions` | `permissionList` |
| [PriceStepController](../../app/Http/Controllers/Admin/PriceStepController.php) | `PriceStep` | `price_steps` | تعريف المورد وعملياته |
| [ScannerCompanyController](../../app/Http/Controllers/Admin/ScannerCompanyController.php) | `ScannerCompany` | `companies` | `show`, `update_block`, `update_active`, `update_password`, `login`, `send_login`, `logout`, `change_lang` |
| [ScannerController](../../app/Http/Controllers/Admin/ScannerController.php) | `Scanner` | `scanners` | `show`, `reset`, `update_block`, `update_is_admin`, `update_active` |
| [SellerCouponsController](../../app/Http/Controllers/Admin/SellerCouponsController.php) | `SellerCoupon` | `seller_coupons` | تعريف المورد وعملياته |
| [ServiceController](../../app/Http/Controllers/Admin/ServiceController.php) | `Service` | `services` | تعريف المورد وعملياته |
| [ServiceRequestController](../../app/Http/Controllers/Admin/ServiceRequestController.php) | `ServiceRequest` | `service_requests` | `update_complete` |
| [SettingController](../../app/Http/Controllers/Admin/SettingController.php) | `Setting` | `settings` | `privacy`, `insurance`, `terms`, `about`, `refund` |
| [TicketCategoryController](../../app/Http/Controllers/Admin/TicketCategoryController.php) | `TicketCategory` | `ticket_categories` | تعريف المورد وعملياته |
| [TicketController](../../app/Http/Controllers/Admin/TicketController.php) | `Ticket` | `tickets` | `chat`, `store_message` |
| [TimeController](../../app/Http/Controllers/Admin/TimeController.php) | `WorkTime` | `scan_times` | تعريف المورد وعملياته |
| [UserController](../../app/Http/Controllers/Admin/UserController.php) | `User` | `users` | `show`, `update_block`, `update_verify` |
| [WantedController](../../app/Http/Controllers/Admin/WantedController.php) | `Wanted` | `wanted` | تعريف المورد وعملياته |
