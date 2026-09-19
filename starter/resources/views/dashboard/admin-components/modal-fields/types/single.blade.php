<div class="col-md-6 col-lg-{{ $fieldWidth }} col-12 p-2">
    <label for="{{ $fieldId }}" class="form-label">{{ $input['label'] }}</label>
    <select class="{{ ($input['select2'] ?? false) ? 'select2' : 'form-select' }}"
            id="{{ $fieldId }}" name="{{ $fieldName }}"
            data-dashboard-select="{{ $input['id'] }}"
            @isset($input['parent_id']) data-parent-id="{{ $input['parent_id'] }}" @endisset
            @isset($input['parent_key']) data-parent-key="{{ $input['parent_key'] }}" @endisset
            @isset($input['child_id']) data-child-id="{{ $input['child_id'] }}" @endisset
            @isset($input['child_key']) data-child-key="{{ $input['child_key'] }}" @endisset
            @if($isNestedField)
                data-field="{{ $input['id'] }}" data-item='@json($input)'
                data-modal="{{ $modalId }}" data-validation='@json($input['validation'] ?? [])'
                data-class="{{ $fieldContext['multiform_prefix'] }}"
            @endif>
        @foreach($input['items'] ?? [] as $item)
            <option value="{{ $item['value'] }}" @selected($item['selected'] ?? false)>{{ $item['text'] }}</option>
        @endforeach
    </select>
</div>
