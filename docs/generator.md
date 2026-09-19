# إنشاء صفحة داشبورد

> **Web Builder الحالي أصبح v2.** استخدم [دليل المعالج الجديد](BUILDER-V2-USER-GUIDE.md) و[عقد v2](BUILDER-V2-CONTRACT.md). الشرح أدناه محفوظ كمرجع لتعريفات v1 والأمر القديم؛ الواجهة الجديدة من ثماني خطوات، وتستخدم الأمر اليدوي `dashboard:sync-permissions` بدل اشتراط Seeder جديد لكل صفحة.

المولّد مناسب لصفحات CRUD ذات أعمدة مباشرة: نص، نص طويل، بريد إلكتروني، عدد صحيح، عدد عشري بمنزلتين، تشغيل/إيقاف، تاريخ، وتاريخ ووقت. يشارك الجدول والنوافذ والتصدير والصلاحيات مع الداشبورد الحالي. عمليات العلاقات والملفات والموافقات والمدفوعات تحتاج تخصيصًا واضحًا في الكنترولر.

## البدء من الواجهة أو من ملف

في بيئة تطوير `local`، فعّل `DASHBOARD_BUILDER_ENABLED=true`، وسجّل الدخول كأدمن ثم افتح `/admin/dashboard-builder` من اتصال `127.0.0.1` أو `::1`. إذا كانت إعدادات Laravel مخزنة مؤقتا، امسح config cache بعد تعديلها حتى يقرأ المفتاح الجديد. الأداة نفسها لها واجهة عربية مستقلة عن ملفات قالب الداشبورد.

1. اكتب عنوان الصفحة واسم الموديل والمسار ومفتاح الصلاحيات، أو اختر **تجربة مثال منتجات**.
2. حدد إنشاء موديل وجدول جديدين أو استخدام موديل موجود.
3. أضف الحقول وحدد الاسم والعنوان والنوع وكونه مطلوبا وظاهرا في الجدول وقابلا للتصفية.
4. اضغط **معاينة الملفات** واقرأ الملفات الناتجة. تعديل التعريف بعد المعاينة يتطلب معاينة جديدة.
5. اضغط **إنشاء الملفات في المشروع**. يعرض المولّد الملفات وخطوات Migration وSeeder؛ تنفيذ هذه الخطوات منفصل.

يمكن تنزيل التعريف بصيغة JSON أو استيراده من ملف، فيكون نقطة بداية قابلة لإعادة الاستخدام لمشروع آخر. للتشغيل من الطرفية، استخدم PHP المتوافق مع متطلبات Composer في مشروعك:

```bash
# قراءة مثال المشروع وعرض النص الكامل لكل ملف، دون كتابة.
php artisan dashboard:make dashboard/examples/product.json

# توليد نفس المثال في مشروع لا توجد فيه هذه الملفات أو المسارات.
php artisan dashboard:make dashboard/examples/product.json --write
```

أمر Artisan يسمح ببيئتي `local` و`testing`، ولا يحتاج جلسة متصفح أو مفتاح تفعيل واجهة الويب. وجود `--write` يعني طلب الكتابة صراحة، لكنه لا يشغّل قاعدة البيانات ولا يستبدل ملفات موجودة. استخدم أسماء أخرى في ملف التعريف إذا سبق توليد المثال نفسه.

## تعريف الصفحة

| المفتاح | معناه |
| --- | --- |
| `model` | اسم كلاس داخل `App\Models` بحروف إنجليزية مثل `Product` |
| `resource` | مقطع المسار واسم الجدول الجديد، مثل `products` أو `product_categories` |
| `permission` | جزء مفاتيح الصلاحية، مثل `product`؛ ينتج view/create/update/delete |
| `title` | العنوان المعروض للصفحة والقائمة ومحرر الصلاحيات |
| `create_model` | إنشاء Model وMigration جديدين، أو استخدام موديل موجود دون تغيير جدوله |
| `timestamps` | إضافة created_at/updated_at عند إنشاء موديل وجدول جديدين |
| `fields` | قائمة الحقول: name، label، type، required، in_table، filterable |

لا تضف `id` أو حقول timestamps ضمن الحقول. المولّد يفترض مفتاح `id` رقميًا تلقائيًا. أنواع المفاتيح الأخرى تحتاج كنترولرًا مخصصًا. عند استخدام موديل موجود، اختر أعمدة فعلية قابلة للحفظ في `fillable` أو المسموح بها عبر `guarded`.

راجع [مثال المنتجات](../../dashboard/examples/product.json). الواجهة تسمح باستيراد وتصدير التعريف وإضافة أو حذف الحقول.

| نوع الحقل | عنصر النموذج | عمود Migration الجديدة | تحقق الخادم وقراءة الموديل |
| --- | --- | --- | --- |
| `text` | حقل نص | `string` | نص حتى 255 حرفا |
| `textarea` | مساحة نص | `text` | نص حتى 10000 حرف |
| `email` | `input type=email` | `string` | صيغة بريد وحد 255 حرفا |
| `integer` | `input type=number` | `bigInteger` | integer وcast integer |
| `decimal` | number و`step=0.01` | `decimal(12,2)` | numeric ضمن ±9999999999.99 وcast `decimal:2` |
| `boolean` | مفتاح تشغيل/إيقاف | `boolean` | boolean وcast boolean؛ النموذج يرسل قيمة 0 عند الإغلاق |
| `date` | `input type=date` | `date` | `date_format:Y-m-d`، والرد بصيغة `Y-m-d` |
| `datetime` | `input type=datetime-local` | `dateTime` | قاعدة Laravel `date`، والرد بصيغة `Y-m-d\TH:i` |

`required=false` يجعل قاعدة التحقق `nullable` وعمود Migration الجديدة قابلا لـNULL. تغير `required` في JSON لاحقا يغير التحقق فقط؛ تغيير قابلية NULL في جدول موجود يحتاج Migration. `filterable=true` ينشئ فلتر نصيا في الصفحة: البحث في الأنواع النصية جزئي، وبقية الأنواع بالمساواة. `in_table=false` يخفي عمود العرض، لكنه لا يزيل الحقل من النموذج أو من بيانات السجل المصرّح بها.

## الملفات الناتجة

لمثال `DemoProduct`:

```text
app/Http/Controllers/Admin/Generated/DemoProductController.php
routes/admin-generated/demo_products.php
dashboard/resources/demo_products.json
database/seeders/DemoProductDashboardPermissionsSeeder.php
app/Models/DemoProduct.php                                 # للموديل الجديد فقط
database/migrations/<date>_000000_create_demo_products_table.php
```

كل ملف route يُحمّل تلقائيًا من `routes/dashboard-tools.php` داخل مجموعة `admin` الحالية. لم يتم استخدام `all_routes()` للتوليد لأنه يضيف دوال مثل show/get_data لا تحتاجها صفحة CRUD البسيطة.

| اسم route | HTTP والمسار | الدالة |
| --- | --- | --- |
| `admin.demo_products.index` | GET `/admin/demo_products` | `index` |
| `.datatable` | POST `/get_datatable` | `get_datatable` |
| `.list` | GET `/list` | `get_list` |
| `.get_single_item` | GET `/get_single_item/{id}` | `get_single_item` |
| `.store` | POST `/store` | `store` |
| `.update` | POST `/update/{id}` | `update` |
| `.delete` | POST `/delete/{id}` | `delete` |
| `.delete_all` | POST `/delete_all` | `delete_all` |

المسارات كلها تتطلب دخول الأدمن وصلاحية العرض، وعمليات الحفظ والحذف تتطلب صلاحياتها الخاصة. حتى `.list` يظل محميًا بصلاحية العرض. لا تمنح أداة التوليد صلاحيات تلقائيًا لأي حساب.

ملف Seeder ينشئ المفاتيح المفقودة فقط. بعد تشغيله، ستجد المجموعة الجديدة بعنوانها في إدارة الأدوار والصلاحيات، ويمكنك اختيار صلاحيات الدور. `ResourceRegistry` يجمع التعريفات للقائمة الجانبية ومحرر الصلاحيات دون تكرار مجموعة تستخدم نفس مفتاح `permission`.

### من تعريف الصلاحية إلى السماح بالطلب

```text
permission = demo_product داخل JSON
  → Seeder يضيف مفاتيح view/create/update/delete_demo_product إلى permissions
  → AuthServiceProvider يعرّف Gates من مفاتيح permissions الموجودة
  → PermissionController.permissionList + ResourceRegistry يعرضان اختيارات المورد داخل محرر الأدوار
  → حفظ الدور يربطه بالاختيارات عبر role_permissions
  → generated-navigation يعرض رابط الصفحة فقط عند السماح بـ view_demo_product
  → middleware المسار يتحقق من view ومن صلاحية الكتابة المطلوبة
```

ملف JSON يحدد العنوان والاختيارات المعروضة؛ صفوف `permissions` وعلاقة الدور تحددان التفويض الفعلي. اسم checkbox هو الفعل ثم `_` ثم مفتاح الصلاحية. عند اشتراك موردين في `permission` يستخدمان نفس مفاتيح التفويض ونفس مجموعة المحرر؛ اختر مفتاحا مستقلا عندما تحتاج منح كل صفحة منفصلة. المجموعة القديمة، إن كانت موجودة، تحتفظ بعنوانها وترتيبها، وتضاف الأفعال الناقصة دون تكرار.

المسارات الجديدة تُقرأ في إقلاع Laravel التالي. إن كان route cache مفعّلا، نفّذ خطوة `route:clear` التي يعرضها المولّد حتى تُقرأ الملفات الجديدة. فتح صفحة تحتوي جدولا جديدا يتطلب تنفيذ Migration الخاصة به أولا، ثم Seeder الصلاحيات ومنح الدور المناسب.

## تعديل صفحة مولّدة

| التعديل | أين تنفّذه؟ |
| --- | --- |
| عنوان الصفحة أو الحقل، الظهور بالجدول، الفلتر، required | ملف `dashboard/resources/<resource>.json`؛ ينعكس في الطلب التالي |
| إضافة أو حذف عمود فعلي أو تغيير نوعه | JSON **وملف Model (fillable/casts)** وMigration جديدة لتعديل الجدول |
| تغيير نوع المفتاح الأساسي أو أسماء المسارات أو الموديل | تعديل صريح للكنترولر والـ routes والموديل والتعريف معًا |
| إضافة علاقة أو رفع ملف أو منطق عمل | override للدالة المناسبة في الكنترولر المولّد |
| تغيير شكل المخرجات المستقبلية لكل صفحة | قوالب `stubs/dashboard/` وخدمة `ScaffoldTemplates` |

المولّد لا يفتح ملفات موجودة لإعادة توليدها؛ هذا يحمي أي تخصيص يدوي. تغيير JSON لا يغيّر بنية قاعدة البيانات أو `fillable/casts` تلقائيًا.

```php
// داخل كنترولر مثال المنتجات: تخصيص بسيط بعد قواعد التحقق المشتركة.
protected function validatedData(\Illuminate\Http\Request $request): array
{
    $data = parent::validatedData($request);
    $data['name_ar'] = trim($data['name_ar']);
    return $data;
}
```

هذا المثال يستخدم حقل `name_ar` الموجود في تعريف المنتجات. عند إضافة علاقات أو عمليات أخرى، اجعل مصدر الصلاحية والقيم الحساسة من الخادم وفق قواعد المشروع.

لتخصيص عنوان الحقل العربي في المثال، ابحث عن العنصر الذي يحمل `"name": "name_ar"` في `dashboard/resources/demo_products.json` وغيّر `label` إلى `"اسم المنتج بالعربية"`. لتغيير ظهوره في الجدول فقط، غيّر `in_table`. الموديل ومسار الصفحة وSeeder لا يحتاجون تعديلا لهذين التغييرين.

أما إضافة عمود `stock` من نوع `integer` فتحتاج تعريفه في JSON، وإضافته إلى `$fillable` و`casts()` في `DemoProduct`، وMigration جديدة تضيف العمود إلى `demo_products`. لا تعدّل Migration إنشاء سبق تشغيلها على بيئة فيها بيانات لتتوقع أن Laravel سيعيد تنفيذها. عند العمل على موديل موجود، يظل اسم جدوله وcasts وتواريخه مأخوذة من كود ذلك الموديل؛ خيارا `resource` و`timestamps` في التعريف لا يعيدان كتابة الموديل الموجود.

## دورة التنفيذ

```text
واجهة الحقول أو ملف JSON
→ ResourceDefinition: تحقق وتطبيع
→ ScaffoldTemplates: قوالب الملفات
→ ScaffoldGenerator.preview: تعارضات + النصوص + بصمة
→ ScaffoldGenerator.generate: مطابقة البصمة + قفل + كتابة ملفات جديدة
→ route محمي
→ GeneratedResourceController
→ ResourcePage / ResourceTable / ResourceRecord
→ نفس table_view ونفس وحدات JavaScript
```

`ResourcePage` يجهز النماذج والأعمدة. `ResourceTable` يحصر ترتيب وبحث وفلاتر DataTables في الحقول المعرّفة، ويعيد العدد قبل الفلترة وبعدها، بحد 500 سجل للطلب. التصدير الافتراضي يخص بيانات الجدول المحمّلة، ولا يمثل تقريرًا كاملًا لكل قاعدة البيانات.

`ResourceRecord` يعرض فقط `id` والحقول المصرّح بها، مع casts/accessors الموديل وتنسيق مناسب للتواريخ. اختيار حقل hidden ضمن التعريف يصرّح بعرض ذلك الحقل للأدمن فقط، دون إظهار باقي الحقول المخفية أو تغيير استجابات API. وصف الحقول يوضع على النموذج كـ `data-dashboard-field-types` حتى يظل حقل نصي اسمه image أو permissions نصيًا ولا يمر إلى معالجات الوسائط أو صلاحيات الأدوار القديمة.

## أين توجد كل مسؤولية؟

### الكنترولر الذي يدير السجلات

الكنترولر الناتج من [controller.stub](../../stubs/dashboard/controller.stub) يضيف `definition()` فقط. يرث بقية الدوال من [GeneratedResourceController](../../app/Http/Controllers/Admin/GeneratedResourceController.php):

| الدالة | المدخلات والنتيجة | متى تخصصها؟ |
| --- | --- | --- |
| `definition(): array` | يعيد تعريف المورد عبر `loadDefinition` | عند تغيير مصدر التعريف نفسه؛ الملف المولد يقرأ JSON جاهزا |
| `loadDefinition($resource): array` | يقرأ JSON ويطبعه ويحفظه داخل مثيل الكنترولر خلال الطلب | عادة لا تحتاج تغييره؛ التعريف غير الصالح يفشل بدلا من حفظ قيم مجهولة |
| `model(): Builder` | يبدأ استعلام الموديل مع global scopes | لإضافة نطاق مؤسسة أو قيود قراءة وكتابة؛ بقية العمليات تستدعيه |
| `index()` | يرسم `table_view` بتعريف `ResourcePage` | عند الحاجة إلى قالب مختلف أو بيانات إضافية للصفحة |
| `get_datatable($request)` | يعيد JSON الخاص بصفوف DataTables عبر `ResourceTable` | عندما تحتاج علاقات أو حسابات لا يصفها الجدول البسيط |
| `get_single_item($id)` | `findOrFail` ثم `recordData` داخل غلاف Success | لإضافة بيانات تعبئة علاقة إلى نموذج متخصص |
| `store($request)` | تحقق الخادم ثم `create` ثم سجل النجاح | لعملية إنشاء ذات علاقات أو ملفات أو خطوات عمل |
| `update($request, $id)` | يجد سجل المسار ثم يتحقق ويحفظ | لعملية تعديل مخصصة؛ معرف النموذج لا يتغلب على معرف المسار |
| `delete($id)` | يجد السجل ثم يحذف عبر Eloquent | لمنع الحذف وفق قواعد موردك؛ الحذف الحالي يحترم أحداث الموديل وSoftDeletes |
| `delete_all($request)` | يتحقق من `ids` المميزة بحد 500 ويستدعي delete داخل معاملة | عند اختلاف قواعد الحذف الجماعي؛ المعاملة تخص قاعدة البيانات، لا ترجع آثار خدمات خارجية تلقائيا |
| `get_list($request)` | يبحث في أول text أو email، أو id، ويعيد حتى 10 عناصر `id/text` للصفحة | عندما تحتاج اسما مركبا أو نطاقا مختلفا للقائمة |
| `validatedData($request): array` | ينفذ قواعد `ResourceDefinition::rules` ويعيد المدخلات المقبولة | لإضافة تحقق Laravel أو تحويل قيم قبل الحفظ |
| `recordData($record): array` | يمرر السجل والحقول إلى `ResourceRecord` | عندما يحتاج العرض بيانات إضافية مصرحا بها صراحة |

`get_datatable` يعيد `draw/recordsTotal/recordsFiltered/data`. عمليات CRUD والقائمة تستخدم غلاف `status/message/data`. لا تخلط ذلك مع `get_data` الخاص بتبويبات الكنترولرز القديمة؛ شرحه في [دليل الكنترولرز](controllers.md).

### خدمات القراءة والعرض والتحقق

| الملف والدالة | ما تنفذه فعليا |
| --- | --- |
| [ResourceDefinition::normalize](../../app/Services/Dashboard/ResourceDefinition.php) | يتحقق من الأسماء والخيارات و1–40 حقلا، يرفض الحقول المحجوزة والتكرار والأنواع غير المدعومة، ويعيد المفاتيح المطبعة؛ لا يطابق الحقول مع schema قاعدة موجودة |
| `ResourceDefinition::rules` | يحول الحقول إلى قواعد Laravel حسب النوع وrequired؛ تنفيذ التحقق نفسه في Request داخل الكنترولر |
| [ResourcePage::data](../../app/Services/Dashboard/ResourcePage.php) | يحول التعريف إلى أعمدة ونماذج وفلاتر وروابط وصلاحيات وأزرار وإعدادات table_view |
| `ResourcePage::input` | دالة خاصة تحوّل نوع حقل واحد إلى عنصر Blade مدعوم وقاعدة required للمتصفح |
| [ResourceRecord::data](../../app/Services/Dashboard/ResourceRecord.php) | يقرأ id والحقول المعرّفة بواسطة getAttribute، ويطبق casts/accessors وصيغة التاريخ دون تغيير الموديل |
| [ResourceTable::response](../../app/Services/Dashboard/ResourceTable.php) | يتحقق من طلب الصفوف، يحسب الإجمالي داخل نطاق model، يطبق البحث والمرشحات وترتيب الأعمدة المسموح، ويعيد الصفحة عبر ResourceRecord |
| [ResourceRegistry::__construct](../../app/Services/Dashboard/ResourceRegistry.php) | يحقن ResourceDefinition لتوحيد تحقق ملفات التعريف في القوائم والمحرر |
| `ResourceRegistry::all` | يقرأ ملفات dashboard/resources المطابقة لاسم resource؛ يتجاوز JSON التالف أو غير الصالح دون تعطيل بقية القوائم |
| `ResourceRegistry::permissionGroups` | يضيف مجموعات الصلاحيات الصالحة بعد القديمة، ويدمج الأفعال الناقصة حسب id دون تبديل عناوين المجموعات الموجودة |

تجاوز الملف التالف في `ResourceRegistry` يحمي القائمة ومحرر الأدوار؛ لا يصلح الملف نفسه. الكنترولر الذي يقرأ ذلك التعريف مباشرة سيظل بحاجة إلى JSON صالح.

### خدمات توليد الملفات

| الملف والدالة | المدخلات والنتيجة والأثر |
| --- | --- |
| [ScaffoldGenerator::__construct](../../app/Services/Dashboard/ScaffoldGenerator.php) | يربط ResourceDefinition وScaffoldTemplates؛ نفس الخدمتين للويب وArtisan |
| `ScaffoldGenerator::preview($input, $root)` | يطبع التعريف ويفحص الموديل والمسارات والملفات، ثم يعيد `definition/files/fingerprint/next_steps` دون كتابة |
| `ScaffoldGenerator::generate($input, $fingerprint, $root)` | يقفل `storage/app/dashboard-generator.lock`، يعيد المعاينة، يقارن SHA-256، ويكتب ملفات جديدة ثم يعيد `paths/url/next_steps` |
| `ScaffoldGenerator::assertNewPath($root, $relative)` | يفحص أجزاء المسار ضد ملف موجود أو اختلاف حالة الأحرف أو رابط رمزي؛ لا يكتب شيئا |
| `ScaffoldGenerator::directory($path)` | ينشئ المجلدات الناقصة قبل فتح القفل أو كتابة ملف؛ يرمي خطأ إذا تعذر الإنشاء |
| `ScaffoldGenerator::nextSteps($definition, $files)` | يبني أوامر Migration المنشأة وSeeder وroute:clear وتعليمات الدور؛ يعرض نصوصا ولا ينفذها |
| [ScaffoldTemplates::files($definition)](../../app/Services/Dashboard/ScaffoldTemplates.php) | ينتج 4 ملفات دائما، أو 6 مع Model وMigration؛ يعيد المسارات النسبية ونصوصها |
| `ScaffoldTemplates::controller($model, $resource)` | يملأ اسم كلاس المورد واسم JSON في قالب الكنترولر |
| `ScaffoldTemplates::routes($model, $resource, $permission)` | يملأ بادئات المسار واسمه وكلاس الكنترولر وصلاحيات العمليات |
| `ScaffoldTemplates::model($definition)` | يجهز اسم الجدول وfillable وtimestamps وcasts الثابتة في الموديل الجديد |
| `ScaffoldTemplates::migration($definition)` | يجهز id والحقول وnullable والتواريخ الاختيارية لجدول جديد |
| `ScaffoldTemplates::permissions($model, $permission)` | ينتج Seeder يستعمل firstOrCreate للمفاتيح الأربعة دون ربطها بدور |
| `ScaffoldTemplates::render($stub, $replacements)` | يقرأ stub من جذر المصدر ويستبدل المواضع بواسطة strtr، دون eval أو كتابة |

`$root` اختياري في `preview/generate` ويستخدم `base_path()` افتراضيا. الاختبارات تمرر مجلدا مؤقتا لكي لا تكتب ملفات التطبيق. القوالب نفسها تُقرأ من جذر المصدر الثابت `dirname(__DIR__, 3)`، ولذلك لا تختفي عندما يغير الاختبار جذر الكتابة. اسم Migration يتضمن تاريخ UTC؛ عبور يوم جديد بين المعاينة والإنشاء يغير النصوص والمسار، فتحتاج معاينة جديدة.

### واجهة الأداة وأمر Artisan

| الملف والدالة | المسؤولية |
| --- | --- |
| [DashboardBuilderController::ensureLocal](../../app/Http/Controllers/Admin/DashboardBuilderController.php) | يتحقق من local والتفعيل وREMOTE_ADDR؛ يعيد 404 عند فشل أحد الشروط |
| `DashboardBuilderController::index` | يعرض واجهة الحقول والأنواع المتاحة |
| `DashboardBuilderController::preview` | يمرر تعريف الطلب إلى preview ويرجع JSON المعاينة |
| `DashboardBuilderController::generate` | يتحقق من fingerprint ثم يرجع الملفات المنشأة باستجابة HTTP 201 |
| [MakeDashboard::handle](../../app/Console/Commands/MakeDashboard.php) | يقرأ مسار JSON، يطبع نصوص الملفات والخطوات، ويكتب فقط مع --write؛ يعيد رمز نجاح أو فشل ويشرح أخطاء التحقق والملفات |

[config/dashboard.php](../../config/dashboard.php) يحتوي مفتاح إظهار وتشغيل واجهة المطوّر. [routes/dashboard-tools.php](../../routes/dashboard-tools.php) يسجل مسارات الأداة ويحمّل ملفات الموارد المولدة. تعطيل واجهة الأداة لا يعطل صفحات الموارد التي أُنشئت بالفعل؛ تظل خاضعة لمساراتها وصلاحياتها.

## تعديل القوالب للصفحات القادمة

| القالب | ما تعدله فيه |
| --- | --- |
| [controller.stub](../../stubs/dashboard/controller.stub) | هيكل الكنترولر وdefinition التي تربطه بملف JSON |
| [model.stub](../../stubs/dashboard/model.stub) | هيكل Eloquent ومواضع table/fillable/timestamps، ودالة casts |
| [routes.stub](../../stubs/dashboard/routes.stub) | أسماء المسارات ودوالها وصلاحيات القراءة والكتابة |
| [migration.stub](../../stubs/dashboard/migration.stub) | up لإنشاء الجدول وdown لحذفه عند rollback الصريح |
| [permissions.stub](../../stubs/dashboard/permissions.stub) | Seeder ودالة run لإضافة مفاتيح الصلاحيات |

المواضع مثل `{{ model }}` و`{{ fillable }}` يستبدلها `ScaffoldTemplates` نصيا. إذا أضفت موضعا جديدا، أضف قيمته إلى خريطة الدالة المناسبة في الخدمة. أسماء الكلاسات والمورد تمر بتحقق الأسماء، والقيم النصية داخل PHP تجهز بـ`var_export`. لا تضع كود PHP قادما من قيمة حرة في واجهة الحقول.

تعديل stub يؤثر في عمليات التوليد التالية فقط، ولا يفتح الملفات المنشأة سابقا لتغيير تخصيصاتها. إذا تغيّرت القوالب بعد المعاينة، تصبح البصمة مختلفة ويطلب الخادم معاينة جديدة قبل الإنشاء.

## سلوك المعاينة والكتابة

- لا كتابة قبل المعاينة. تعديل البيانات يلغي المعاينة السابقة، والخادم يتحقق أيضًا من البصمة.
- رفض الكلمات المحجوزة والمسارات غير الصالحة وتعارض الملفات والمسارات وMigration موجودة لنفس الجدول.
- لا استبدال لملف موجود ولا مرور داخل رابط رمزي في مسار ملف ناتج.
- عند فشل الكتابة، تُحذف فقط الملفات التي أنشأتها العملية نفسها؛ تظل الملفات السابقة كما هي.
- لا تشغيل تلقائي لـ Migration أو Seeder أو أمر shell من الواجهة.
- الواجهة تتطلب `local` و`DASHBOARD_BUILDER_ENABLED=true` واتصال loopback وجلسة أدمن وCSRF. تفعيل القيمة على خادم production لا يفتح أداة كتابة المصدر.

التراجع عن فشل التوليد قد يترك مجلدات فارغة وملف القفل، لكنه لا ينفذ Migration أو يحاول التراجع عن قاعدة بيانات. كذلك معاينة موديل موجود تتحقق من وجود الكلاس والمفتاح الأساسي؛ اختيار أعمدة حقيقية قابلة للكتابة ومتوافقة مع casts مسؤولية تعريف الصفحة، وتتحقق منها اختبارات المورد عند تخصيصه.

## ما الذي اختُبر فعليا؟

اختبارات [Feature/Dashboard](../../tests/Feature/Dashboard) تستخدم قاعدة SQLite داخل الذاكرة وملفات مولدة في مجلد مؤقت، وتنفذ مسارات Laravel الفعلية: CRUD والتحقق والصلاحيات وموديل موجود وقراءة السجلات وتنسيق الأنواع ومحرر الأدوار وأمر Artisan. اختبارات [Unit/Dashboard](../../tests/Unit/Dashboard) تغطي المعاينة والقوالب والبصمة والتعارضات والتراجع عند فشل الكتابة. هذه اختبارات سلوك، وليست مجرد مقارنة نصوص القوالب. ارجع إلى سجل التحقق العام لعدد الاختبارات ونتائج التشغيل الأحدث وحدود ما اختُبر في المتصفح.
