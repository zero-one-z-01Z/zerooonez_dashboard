<input type="hidden" class="check-html" name="{{ $fieldName }}" id="{{ $scopedFieldId }}_html">
<div class="col-md-6 col-lg-{{ $fieldWidth }} col-12 p-2">
    <div class="card">
        <h5 class="card-header">{{ $input['label'] }}</h5>
        <div class="card-body">
            <div id="{{ $scopedFieldId }}_editor" data-dashboard-editor="{{ $fieldName }}" style="height: 300px;"></div>
        </div>
    </div>
</div>
