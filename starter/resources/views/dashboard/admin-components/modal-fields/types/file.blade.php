<div class="col-md-6 col-lg-{{ $fieldWidth }} col-12 p-2">
    <label for="{{ $fieldId }}" class="form-label">{{ $input['label'] }}</label>
    <input type="file" class="form-control" id="{{ $fieldId }}" name="{{ $fieldName }}"
           @isset($input['accepted_files']) accept="{{ $input['accepted_files'] }}" @endisset
           @if($isNestedField) data-field="{{ $input['id'] }}" data-validation='@json($input['validation'] ?? [])' @endif>
</div>
