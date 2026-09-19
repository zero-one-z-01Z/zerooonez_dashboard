<div class="col-md-6 col-lg-{{ $fieldWidth }} col-12 p-2">
    <label for="{{ $fieldId }}" class="form-label">{{ $input['label'] }}</label>
    <input type="{{ $input['type'] ?? 'text' }}" class="form-control" id="{{ $fieldId }}"
           name="{{ $fieldName }}" placeholder=""
           @isset($input['step']) step="{{ $input['step'] }}" @endisset
           @if($input['read_only'] ?? false) readonly @endif
           @if($isNestedField) data-field="{{ $input['id'] }}" data-validation='@json($input['validation'] ?? [])' @endif>
</div>
