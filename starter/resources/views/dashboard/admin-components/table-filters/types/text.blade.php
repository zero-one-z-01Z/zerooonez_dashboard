<input type="{{ $filter['type'] ?? 'text' }}" class="form-control"
       id="{{ $filterId }}" name="{{ $filter['id'] }}"
       value="{{ $filter['value'] ?? '' }}" placeholder="{{ $filter['placeholder'] ?? '' }}"
       data-filter-name="{{ $filter['label'] }}"
       @isset($filter['step']) step="{{ $filter['step'] }}" @endisset
       @required($filter['required'] ?? false)>
