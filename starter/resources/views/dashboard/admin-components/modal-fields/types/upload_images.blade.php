<div class="col-md-6 col-lg-12 col-12 p-2">
    <div class="card-body">
        <div class="dropzone needsclick p-0" id="{{ $scopedFieldId }}_dropzone" data-upload-field="{{ str_ends_with($fieldName, '[]') ? substr($fieldName, 0, -2) : $fieldName }}"
             data-max-files="{{ $input['max_files'] ?? $input['max'] ?? 1 }}"
             data-param-name="{{ str_ends_with($fieldName, '[]') ? $fieldName : $fieldName.'[]' }}"
             data-deleted-key="{{ $isNestedField ? $fieldContext['name_prefix'].'['.($input['storage']['deleted_key'] ?? ('deleted_'.rtrim($input['id'], '[]'))).']' : ($input['storage']['deleted_key'] ?? ('deleted_'.rtrim($input['id'], '[]'))) }}"
             data-required="{{ ($input['validation']['required'] ?? $input['required'] ?? false) ? 'true' : 'false' }}"
             data-accepted-files="{{ $input['accepted_files'] ?? 'image/*' }}"
             data-max-file-size="{{ $input['max_file_size'] ?? 2 }}">
            <div class="dz-message needsclick">
                <p class="h4 needsclick pt-3 mb-2">{{ __('inputs.drag_drop_your_image_here') }}</p>
                <p class="h6 text-body-secondary d-block fw-normal mb-2">{{ __('inputs.or') }}</p>
                <span class="needsclick btn btn-sm btn-label-primary">{{ __('inputs.browse_images') }}</span>
            </div>
        </div>
    </div>
</div>
