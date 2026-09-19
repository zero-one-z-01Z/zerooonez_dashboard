<div class="col-md-6 col-lg-{{ $fieldWidth }} col-12 p-2">
    <label for="{{ $fieldId }}" class="form-label">{{ $input['label'] }}</label>
    <input type="file" class="form-control" id="{{ $fieldId }}" name="{{ $fieldName }}"
           accept="{{ $input['accepted_files'] ?? (($input['type'] ?? 'image') === 'image' ? 'image/*' : 'video/*') }}"
           @if($isNestedField) data-field="{{ $input['id'] }}" data-validation='@json($input['validation'] ?? [])' @endif>
</div>
