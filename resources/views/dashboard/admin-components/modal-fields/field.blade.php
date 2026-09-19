@php
    $fieldType = $input['input'] ?? '';
    $formId = $fieldContext['form_id'];
    $modalId = $fieldContext['modal_id'];
    $fieldMode = $fieldContext['mode'];
    $fieldWidth = $input['width'] ?? 6;
    $fieldName = isset($fieldContext['name_prefix'])
        ? $fieldContext['name_prefix'].'['.($input['id'] ?? '').']'
        : ($input['id'] ?? '');
    $fieldId = $fieldContext['id_prefix'].($input['id'] ?? '');
    $scopedFieldId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $formId.'_'.$fieldId);
    $isNestedField = isset($fieldContext['name_prefix']);
    $fieldView = preg_match('/^[a-z0-9_]+$/', $fieldType)
        ? 'zerooonez-dashboard::admin-components.modal-fields.types.'.$fieldType
        : null;
@endphp

@if($fieldView && view()->exists($fieldView))
    @include($fieldView)
@else
    {{-- نوع غير مدعوم: أضف ملفًا واحدًا باسم النوع داخل modal-fields/types. --}}
@endif
