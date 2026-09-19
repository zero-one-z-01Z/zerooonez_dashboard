<div class="row">
    @foreach($inputs ?? [] as $input)
        @include('zerooonez-dashboard::admin-components.modal-fields.field', compact('input', 'fieldContext'))
    @endforeach
</div>
