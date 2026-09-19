
@extends('dashboard.layout.admin-main-layout')

@push('head')

    @component('dashboard.layout.parts.admin-include-head', [
        'datatable'=>$data['datatable'],
        'select2'=>$data['select2'],
        'daterangepicker'=>$data['daterange_filter'],
        "boundary"=>isset($data['boundary']),
        'dropzone'=>isset($data['dropzone']),
        'html_input'=>isset($data['html_editor']),
        'map'=>isset($data['map']),
    ])
    @endcomponent
    @foreach($data['inputs'] as $input)
        @if($input['input']=='html')
            <style>
                #full-editoradd_form{{$input['id']}} .ql-editor {
                    height: 200px;
                    overflow-y: auto;
                }

            </style>
        @endif
    @endforeach
    @foreach($data['update_inputs'] as $input)
        @if($input['input']=='html')
            <style>
                #full-editoredit_form{{$input['id']}} .ql-editor {
                    height: 200px;
                    overflow-y: auto;
                }

            </style>
        @endif
    @endforeach
   @isset($data['modals'])
       @foreach($data['modals'] as $modal)
           @php
               $form = ltrim($modal['form_id'], '#')
           @endphp
           @foreach($modal['inputs'] as $input)
               @if($input['input']=='html')
                   <style>
                       #full-editor{{$form}}{{$input['id']}} .ql-editor {
                           height: 200px;
                           overflow-y: auto;
                       }

                   </style>
               @endif
           @endforeach

       @endforeach
   @endisset

@endpush
@section('content')
    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-6">
                @include('dashboard.admin-components.table-statistics')
                <!-- Product List Table -->
                <div class="card">
                    @component('dashboard.admin-components.table-card', [
                'card_title' => $data['title'],
                'daterange_filter' => $data['daterange_filter'],
                'daterannge_filter_name' => $data['daterannge_filter_name'],
                'daterange_filter_tooltip' => $data['daterange_filter_tooltip'],
                'table_id' => 'table_id',
                'filter_drop_style' => 'min-width: 500px;',
                'filter_badges' => isset($data['filters']),
                'filter_display' => $data['filter_display'],
            ])
                        @if(!empty($data['filters']))
                            @slot('filter')
                                @include('dashboard.admin-components.table-filters.filters', [
                                    'filters' => $data['filters'],
                                ])
                            @endslot
                        @endif
                        @isset($data['show_columns'])
                            @if($data['show_columns'])
                                @slot('additional_element')
                                    <div class="dropdown mb-3 me-2">
                                        <a href="javascript:void(0);" class="btn bg-primary dropdown-toggle text-white"
                                           data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class=" me-2"></i>الأعمدة</a>
                                        <div class="dropdown-menu drop-width">
                                            <select name="columns" id="columns" class="form-control custom-select" multiple="multiple"
                                                    style="height: 300px;">
                                                @foreach($data['show_list'] as $key => $item)
                                                    <option selected value="{{ $key + 1 + (int) $data['have_check_box'] }}">{{$item['title']}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endslot
                            @endif
                        @endisset
                    @endcomponent

                    <div class="card-datatable">
                        <table class="datatables table" id="table_id">
                            <thead class="border-top">
                            <tr>
                                <th></th>
                                @if($data['have_check_box'])
                                    <th ><div class="form-check form-check-md"><input class="form-check-input" id="master-check" type="checkbox"></div></th>
                                @endif
                                @foreach($data['show_list'] as $column)
                                    <th>{{$column['title']}}</th>
                                @endforeach
                                @if($data['have_actions'])
                                    <th>{{__('admin.actions')}}</th>
                                @endif
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

{{--
    هذه صفحة كاملة: all-modals يدفع نماذج تعريف $data إلى stack('modals') في layout.
    صفحة التفاصيل المتغيرة تستخدم مضيفًا فارغًا وfragments.modals بدل هذا الإدراج الثابت.
--}}
@include('dashboard.admin-components.all-modals',$data)

@push('scripts')

    @component('dashboard.layout.parts.admin-include-scripts', [
    'datatable'=>$data['datatable'],
    'select2'=>$data['select2'],
    'daterangepicker'=>$data['daterange_filter'],
    'have_validation'=>$data['have_validation'],
    'boundary'=>isset($data['boundary']),
    'dropzone'=>isset($data['dropzone']),
    'html_input'=>isset($data['html_editor']),
    'map'=>isset($data['map']),
        ])
    @endcomponent
    @php
    if(!isset($data['auto_start'])){
        $data['auto_start'] = false;
    }
     @endphp
    {{-- بيانات فقط: page-table.js يقرأ JSON بعد جاهزية DOM، ثم يجهز الجدول ونماذج الصفحة الموجودة بالفعل. --}}
    <script type="application/json" id="dashboard-table-definition">@json($data)</script>
    @dashboardVite('resources/js/back/page-table.js')

@endpush
