# Renderer فلاتر الجداول

`table_view.blade.php` لا يرسم أنواع الفلاتر بنفسه. يمرر التعريفات إلى `filters.blade.php`، ثم يحول `field.blade.php` قيمة `input` إلى ملف داخل `types/`.

الأنواع المستخدمة حاليًا في المشروع هي:

- `single`: فلاتر الكنترولرز القديمة وعلاقات Select2.
- `text`: فلاتر الموارد العامة، ويغطي `text` و`number` و`date` و`email` من خلال قيمة `type`.

لهذا لا نحتاج نسخ جميع أنواع حقول المودالات إلى الفلاتر. حقول الصور وHTML والخرائط و`multiform` لا تمثل قيمة فلترة مناسبة في العقد الحالي، وإضافتها ستحتاج صيغة طلب ومعالجة Backend واضحة.

## إضافة نوع فلتر جديد

إذا أصبح هناك تعريف:

```php
[
    'id' => 'status',
    'input' => 'radio',
    'label' => __('inputs.status'),
    'width' => 6,
]
```

أنشئ ملفًا واحدًا:

```text
table-filters/types/radio.blade.php
```

المتغيرات الجاهزة هي `$filter` و`$filterId` و`$filterWidth`. يجب أن يحمل العنصر `name="{{ $filter['id'] }}"` و`id="{{ $filterId }}"` حتى تقرأه DataTables وتستطيع الشارات مسحه. إذا كانت قيمة النوع ليست قيمة input/select عادية، حدّث قارئ الفلاتر واختباره مع هذا الملف.
