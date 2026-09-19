<div class="row">
    @foreach($filters ?? [] as $filter)
        @include('dashboard.admin-components.table-filters.field', compact('filter'))
    @endforeach
</div>
