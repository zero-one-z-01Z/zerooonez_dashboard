@php
    $multiformPrefix = $fieldContext['multiform_prefix'];
    $minimumRows = max(0, (int) ($input['min_rows'] ?? 1));
    $initialRows = isset($input['max_rows']) ? min($minimumRows, max(0, (int) $input['max_rows'])) : $minimumRows;
    $hasChildId = collect($input['inputs'] ?? [])->contains(fn ($child) => ($child['id'] ?? null) === 'id');
@endphp
<div class="col-12 p-2">
    <input type="hidden" name="{{ $input['id'] }}__present" value="1"
           data-multiform-presence="{{ $input['id'] }}">
    <div id="{{ $multiformPrefix }}_{{ $input['id'] }}" data-multiform-container
         data-multiform-name="{{ $input['id'] }}"
         data-id-prefix="{{ $multiformPrefix.$input['id'].'_' }}"
         data-inputs='@json($input['inputs'] ?? [])'
         data-min-rows="{{ $minimumRows }}"
         @isset($input['max_rows']) data-max-rows="{{ (int) $input['max_rows'] }}" @endisset>
        <h5 class="text-center fw-bold mb-3">{{ $input['label'] ?? __('form.items') }}</h5>
        @for($rowIndex = 0; $rowIndex < $initialRows; $rowIndex++)
            @php
                $nestedContext = [
                    'form_id' => $formId, 'modal_id' => $modalId,
                    'id_prefix' => $multiformPrefix.$input['id'].'_'.$rowIndex.'_',
                    'multiform_prefix' => $multiformPrefix, 'mode' => $fieldMode,
                    'name_prefix' => $input['id'].'['.$rowIndex.']',
                ];
            @endphp
            <div class="row p-2 border mb-3" data-multiform-group>
                @if($fieldMode === 'edit' && !$hasChildId)
                    <input type="hidden" name="{{ $input['id'] }}[{{ $rowIndex }}][id]"
                           id="{{ $multiformPrefix.$input['id'].'_'.$rowIndex.'_id' }}" data-field="id">
                @endif
                @foreach($input['inputs'] ?? [] as $formInput)
                    @include('zerooonez-dashboard::admin-components.modal-fields.field', ['input' => $formInput, 'fieldContext' => $nestedContext])
                @endforeach
                <button type="button" class="btn btn-danger mt-2" onclick="removeItemMultiForm(this)">
                    {{ __('buttons.remove_item') }}
                </button>
            </div>
        @endfor
        @php
            $templateContext = [
                'form_id' => $formId, 'modal_id' => $modalId,
                'id_prefix' => $multiformPrefix.$input['id'].'___INDEX___',
                'multiform_prefix' => $multiformPrefix, 'mode' => $fieldMode,
                'name_prefix' => $input['id'].'[__INDEX__]',
            ];
        @endphp
        <template data-multiform-template>
            <div class="row p-2 border mb-3" data-multiform-group>
                @if($fieldMode === 'edit' && !$hasChildId)
                    <input type="hidden" name="{{ $input['id'] }}[__INDEX__][id]"
                           id="{{ $multiformPrefix.$input['id'].'___INDEX___id' }}" data-field="id">
                @endif
                @foreach($input['inputs'] ?? [] as $formInput)
                    @include('zerooonez-dashboard::admin-components.modal-fields.field', ['input' => $formInput, 'fieldContext' => $templateContext])
                @endforeach
                <button type="button" class="btn btn-danger mt-2" onclick="removeItemMultiForm(this)">
                    {{ __('buttons.remove_item') }}
                </button>
            </div>
        </template>
    </div>
    <button type="button" class="btn btn-primary mt-2"
            onclick="addItemMultiForm('{{ $multiformPrefix }}','{{ $input['id'] }}')">
        {{ __('buttons.add_item') }}
    </button>
</div>
