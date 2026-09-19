<div class="col-md-6 col-lg-{{ $fieldWidth }} col-12 p-2">
    @if($isNestedField)<label class="form-label d-block">{{ $input['label'] }}</label>@endif
    <div class="form-check form-switch">
        <input type="hidden" name="{{ $fieldName }}" value="0"
               @if($isNestedField) data-field="{{ $input['id'] }}" @endif>
        <input class="form-check-input" type="checkbox" id="{{ $fieldId }}"
               name="{{ $fieldName }}" value="1"
               @checked(in_array($input['default'] ?? false, [true, 1, '1', 'true'], true))
               @if($isNestedField) data-field="{{ $input['id'] }}" data-validation='@json($input['validation'] ?? [])' @endif>
        @unless($isNestedField)
            <label class="form-check-label" for="{{ $fieldId }}">{{ $input['label'] }}</label>
        @endunless
    </div>
</div>
