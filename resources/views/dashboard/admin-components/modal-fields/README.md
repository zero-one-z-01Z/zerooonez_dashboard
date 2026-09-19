# Renderer حقول المودالات

جميع مودالات الإضافة والتعديل والمودالات المخصصة تستعمل نفس المسار:

1. `modal-content.blade.php` يحدد بيانات المودال والسياق فقط.
2. `fields.blade.php` يمر على تعريفات الحقول.
3. `field.blade.php` يحول قيمة `input` إلى partial داخل `types/`.
4. ملف النوع يرسم الحقل في الحالات الثلاث ويقرأ اختلاف المعرف والنموذج من `$fieldContext`.

## إضافة نوع جديد

إذا أرسل الكنترولر تعريفًا مثل:

```php
[
    'id' => 'starts_at',
    'input' => 'datetime',
    'label' => __('inputs.starts_at'),
    'width' => 6,
]
```

أنشئ ملفًا واحدًا فقط:

```text
modal-fields/types/datetime.blade.php
```

مثال محتواه:

```blade
<div class="col-md-6 col-lg-{{ $fieldWidth }} col-12 p-2">
    <label for="{{ $fieldId }}" class="form-label">{{ $input['label'] }}</label>
    <input type="datetime-local" class="form-control"
           id="{{ $fieldId }}" name="{{ $fieldName }}">
</div>
```

سيظهر النوع تلقائيًا في `inputs` و`update_inputs` و`modals.*.inputs`. لا تضف شرطًا إلى `modal-content.blade.php`.

## المتغيرات الجاهزة لكل نوع

| المتغير | المعنى |
| --- | --- |
| `$input` | تعريف الحقل الكامل القادم من الكنترولر. |
| `$fieldName` | اسم الطلب الصحيح، بما فيه اسم حقل `multiform` المتداخل. |
| `$fieldId` | معرف فريد مبني على سياق الإضافة أو التعديل أو المودال المخصص. |
| `$fieldWidth` | العرض، وافتراضيه 6. |
| `$formId` | معرف النموذج الفعلي مثل `add_form`. |
| `$modalId` | معرف المودال الذي يحتوي الحقل. |
| `$fieldMode` | `create` أو `edit` أو `custom`. |
| `$isNestedField` | يحدد أن الحقل داخل `multiform`. |

إذا احتاج النوع مكتبة JavaScript، أضف اكتشافه في `DashboardAssetResolver` وتهيئته في مدير الحقول العام. حفظ البيانات والتحقق النهائي يظلان في Controller أو Service.
