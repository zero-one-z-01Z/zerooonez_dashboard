@php
    $filterType = $filter['input'] ?? '';
    $filterId = 'filter'.$filter['id'];
    $filterWidth = $filter['width'] ?? 6;
    $filterView = preg_match('/^[a-z0-9_]+$/', $filterType)
        ? 'zerooonez-dashboard::admin-components.table-filters.types.'.$filterType
        : null;
@endphp

@if($filterView && view()->exists($filterView))
    <div class="col-md-{{ $filterWidth }}">
        <div class="mb-3">
            <label for="{{ $filterId }}" class="form-label">{{ $filter['label'] }}</label>
            @include($filterView)
        </div>
    </div>
@else
    {{-- نوع غير مدعوم: أضف ملفًا واحدًا باسم النوع داخل table-filters/types. --}}
@endif
