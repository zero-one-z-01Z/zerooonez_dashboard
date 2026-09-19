{{--
    يرسم كل مودالات الشاشة، بينما يرسم modal-fields جميع أنواع الحقول من مصدر واحد.
    لإضافة نوع input جديد أنشئ ملفًا واحدًا فقط داخل modal-fields/types باسم النوع.
--}}
@component('dashboard.admin-components.admin-modal', [
    'form_id' => 'add_form',
    'schema_fields' => $data['schema_fields'] ?? null,
    'form_action' => '#',
    'id' => 'add_modal',
    'title' => __('admin.add_item'),
    'action' => 'add',
    'call_function' => 'create_item',
    'reset_on_close' => true,
    'scroll' => true,
])
    @slot('body')
        @include('dashboard.admin-components.modal-fields.fields', [
            'inputs' => $data['inputs'] ?? [],
            'fieldContext' => [
                'form_id' => 'add_form',
                'modal_id' => 'add_modal',
                'id_prefix' => 'create',
                'multiform_prefix' => 'create',
                'mode' => 'create',
            ],
        ])
    @endslot
@endcomponent

@component('dashboard.admin-components.admin-modal', [
    'form_id' => 'edit_form',
    'schema_fields' => $data['schema_fields'] ?? null,
    'form_action' => '#',
    'id' => 'edit_modal',
    'title' => __('admin.edit_item'),
    'action' => 'edit',
    'call_function' => 'update_item',
    'scroll' => true,
    'reset_on_close' => true,
])
    @slot('body')
        @include('dashboard.admin-components.modal-fields.fields', [
            'inputs' => $data['update_inputs'] ?? [],
            'fieldContext' => [
                'form_id' => 'edit_form',
                'modal_id' => 'edit_modal',
                'id_prefix' => 'edit',
                'multiform_prefix' => 'edit',
                'mode' => 'edit',
            ],
        ])
    @endslot
@endcomponent

@foreach($data['modals'] ?? [] as $modal)
    @php
        $customFormId = ltrim($modal['form_id'], '#');
        $customModalId = ltrim($modal['id'], '#');
    @endphp
    @component('dashboard.admin-components.admin-modal', [
        'form_id' => $customFormId,
        'form_action' => $modal['link'] ?? $modal['route'] ?? '#',
        'id' => $customModalId,
        'title' => $modal['title'],
        'action' => 'custom',
        'call_function' => 'customFunction',
        'scroll' => true,
        'reset_on_close' => true,
        'hide_buttons' => $modal['hide_buttons'] ?? false,
        'button' => $modal['button'] ?? ['cancel' => __('buttons.cancel'), 'submit' => __('buttons.save')],
    ])
        @slot('body')
            @isset($modal['details'])
                @include('dashboard.item-components.info', $modal['details'])
            @endisset

            @include('dashboard.admin-components.modal-fields.fields', [
                'inputs' => $modal['inputs'] ?? [],
                'fieldContext' => [
                    'form_id' => $customFormId,
                    'modal_id' => $customModalId,
                    'id_prefix' => $customModalId,
                    'multiform_prefix' => $customModalId,
                    'mode' => 'custom',
                ],
            ])

            @if($modal['images'] ?? false)
                <div id="carouselExample" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-indicators"></div>
                    <div class="carousel-inner"></div>
                    <a class="carousel-control-prev" href="#carouselExample" role="button" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </a>
                    <a class="carousel-control-next" href="#carouselExample" role="button" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </a>
                </div>
            @endif
        @endslot
    @endcomponent
@endforeach
