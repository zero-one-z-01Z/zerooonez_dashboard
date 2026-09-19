<div class="col-md-6 col-lg-{{ $fieldWidth }} col-12 p-2">
    <label for="{{ $fieldId }}" class="form-label">{{ $input['label'] }}</label>
    <textarea class="form-control" rows="{{ $input['rows'] ?? $fieldWidth }}" cols="50"
              id="{{ $fieldId }}" name="{{ $fieldName }}"
              @if($input['read_only'] ?? false) readonly @endif
              @if($isNestedField) data-field="{{ $input['id'] }}" data-validation='@json($input['validation'] ?? [])' @endif></textarea>
</div>
