<select class="{{ ($filter['select2'] ?? false) ? 'select2' : 'form-select' }}"
        id="{{ $filterId }}" name="{{ $filter['id'] }}"
        data-filter-name="{{ $filter['label'] }}"
        @required($filter['required'] ?? false)>
    @unless($filter['select2'] ?? false)
        @foreach($filter['items'] ?? [] as $item)
            <option value="{{ $item['value'] }}" @selected($item['selected'] ?? false)>{{ $item['text'] }}</option>
        @endforeach
    @endunless
</select>
