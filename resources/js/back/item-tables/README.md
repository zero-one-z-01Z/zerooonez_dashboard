# جداول تبويبات التفاصيل

صفحة `resources/views/dashboard/screen/item_view.blade.php` تحمل مدخل `item-table.js`، وهو يستورد `table.js` ثم هذه الوحدات:

| الملف | المسؤولية |
| --- | --- |
| `navigation.js` | تحميل تعريف التبويب، إلغاء الطلب الأقدم، قفل الإجراءات أثناء الانتقال، ثم تثبيت أحدث نتيجة |
| `modals.js` | قراءة HTML النماذج الذي رسمه Blade، إغلاق النوافذ القديمة وتحرير أدواتها، وتجهيز البديل |
| `headers.js` | بناء رؤوس الجدول حسب أعمدة الخادم باستخدام نصوص آمنة |

## عقد الخادم

داخل الكنترولر الذي يستخدم `BuildsDashboardPage`:

```php
public function get_data()
{
    return $this->dashboardTabResponse($this->prepareData());
}
```

الاستجابة تحتفظ بـ `status: Success` و`data`، وتضيف `fragments.modals` و`assets`. يرسم Laravel النماذج من `dashboard.admin-components.modal-content` بنفس بيانات التبويب، ويستنتج `DashboardAssetResolver` المكتبات المطلوبة من تعريف الحقول. لا يمكن تنفيذ `@include` في المتصفح؛ لذلك يعاد رسم القالب في كل طلب للخادم.

`all-modals.blade.php` يظل غلاف `@push('modals')` للصفحات الكاملة. أما fragment المباشر فلا يعتمد على Blade stack، ومضيفه الوحيد بصفحة التفاصيل هو `#dashboard-item-table-modals`. يستبدل محتواه بالكامل، فلا تتكرر معرفات `add_form` و`edit_form` أو تتبقى قواعد تحقق وحقول من تبويب سابق.

قبل تثبيت النتيجة، يحمل `Dashboard.assets` مكتبات التبويب المطلوبة مرة واحدة. ثم تغلق نوافذ Bootstrap القديمة حتى تنتهي الخلفية عبر `forms/modal-lifecycle.js`، ويطلق `dashboard:content-unmounted` قبل إزالة Select2 وDropzone والخرائط والمحررات والتحقق. بعد تركيب HTML الجديد، تعمل `prepareDataTable` ثم `setUpDatatable` ويطلق `dashboard:content-mounted`. تستمر أحداث `data-call_function` والأحداث المفوضة على document في العمل مع العناصر الجديدة.

طلبات قراءة السجل تحتفظ بهوية عنصر النموذج، وتلغى عند التنقل؛ استجابة قديمة لا تملأ نموذجًا جديدًا بنفس المعرف. طلب حفظ بدأ بالفعل يستمر، لكن نتيجته لا تمس جدول تبويب بديل. فشل تحميل التعريف أو غياب `fragments.modals` يبقي التبويب السابق ويعرض السبب.

مرشحات سجل التفاصيل `internal_filter` تظل ثابتة؛ أما `internal_filters` الخاصة بكل تعريف فتستبدل مع التبويب. عناصر الخرائط والمحررات ورفع الملفات تستخدم adapters متعددة النسخ، ويحمل AssetLoader مكتبتها إن لم تكن موجودة في الصفحة الحالية.
