<div class="row">
    @foreach($inputs ?? [] as $input)
        @include('dashboard.admin-components.modal-fields.field', compact('input', 'fieldContext'))
    @endforeach
</div>
