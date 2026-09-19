@php
    $permissionCreate = $fieldMode === 'create';
    $permissionEdit = $fieldMode === 'edit';
@endphp
<div class="table-responsive">
    <table class="table table-flush-spacing">
        <thead>
        <tr>
            <th class="text-nowrap fw-medium">
                {{ __('admin.permissions') }}
                <i class="icon-base ti tabler-info-circle icon-xs" data-bs-toggle="tooltip"
                   data-bs-placement="top" title="{{ __('admin.allow_permissions') }}"></i>
            </th>
            @if($permissionCreate || $permissionEdit)
                <th class="text-end">
                    <div class="form-check">
                        <input type="checkbox" id="{{ $permissionCreate ? 'selectAll' : 'selectAllEdit' }}"
                               class="form-check-input" @checked($permissionCreate)>
                        <label class="form-check-label" for="{{ $permissionCreate ? 'selectAll' : 'selectAllEdit' }}">
                            {{ __('admin.select_all') }}
                        </label>
                    </div>
                </th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($input['items'] ?? [] as $item)
            <tr>
                <td class="text-nowrap fw-medium text-heading">
                    {{ $item['label'] ?? __('admin.'.$item['name']) }}
                </td>
                <td>
                    <div class="d-flex justify-content-end">
                        @foreach($item['permissions'] as $key => $permission)
                            @php $permissionName = $key.'_'.$item['id']; @endphp
                            <div class="form-check mb-0 me-4 me-lg-12">
                                <input class="form-check-input {{ $permissionCreate ? 'permission-checkbox' : ($permissionEdit ? 'permission-checkbox-edit' : '') }}"
                                       type="checkbox" name="{{ $permissionName }}" id="{{ $formId }}{{ $permissionName }}"
                                       @checked($permissionCreate)>
                                <label class="form-check-label" for="{{ $formId }}{{ $permissionName }}">
                                    {{ __('admin.'.($permissionCreate ? $permission : $key)) }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
