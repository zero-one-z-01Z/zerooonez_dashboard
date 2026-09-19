@php
    $latName = $input['lat_field'] ?? 'lat';
    $lngName = $input['lng_field'] ?? 'lng';
    if ($isNestedField) {
        $latName = $fieldContext['name_prefix'].'['.$latName.']';
        $lngName = $fieldContext['name_prefix'].'['.$lngName.']';
    }
@endphp
<input type="hidden" class="form-control" id="{{ $scopedFieldId }}_lat" name="{{ $latName }}" required>
<input type="hidden" class="form-control" id="{{ $scopedFieldId }}_lng" name="{{ $lngName }}" required>
<div class="col-md-6 col-lg-{{ $fieldWidth }} col-12 p-2">
    <div id="{{ $scopedFieldId }}_map" data-dashboard-location-map="{{ $fieldName }}" data-lat-field="{{ $latName }}" data-lng-field="{{ $lngName }}" style="height: 400px; border-radius: 16px;"></div>
</div>
