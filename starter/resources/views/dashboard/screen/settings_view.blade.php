@extends('dashboard.layout.admin-main-layout')

@push('head')

    @component('dashboard.layout.parts.admin-include-head', [
        'html_input'=>true,
    ])
    @endcomponent
    @foreach($data['inputs'] as $input)
        @if($input['input']=='html')
            <style>
                #full-editor{{$input['id']}} .ql-editor {
                    height: 240px;
                    overflow-y: auto;
                }
            </style>
        @endif
    @endforeach

@endpush
@section('content')
    <div class="content-wrapper">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-6">
                <form novalidate action="{{route('admin.settings.update',[$data['id']])}}" id="settings_form"
                      data-event_on="submit"  method="POST"
                      enctype="multipart/form-data"
                      style="overflow-y: scroll;"
                >
                    <div class="modal-body row">
                        @csrf
                        @isset($data['inputs'])
                            @foreach($data['inputs'] as $input)
                                @if($input['input']=='hidden')
                                    <input type="hidden"  name="{{$input['id']}}" id="{{$data['id']}}{{$input['id']}}" value="{{$input['value']}}">
                                @elseif($input['input']=='text')
                                    <div class="col-md-6 col-lg-{{isset($input['width'])?$input['width']:'6'}} col-12 p-2">
                                        <div>
                                            <label for="Name" class="form-label">{{$input['label']}}</label>
                                            <input type="{{$input['type']}}" class="form-control" id="{{$data['id']}}{{$input['id']}}"  @isset($input['read_only']) @if($input['read_only']) readonly @endif  @endisset placeholder="" name="{{$input['id']}}" >
                                        </div>
                                    </div>
                                @elseif($input['input']=='single')
                                    <div class="col-md-6 col-lg-{{isset($input['width'])?$input['width']:'6'}} col-12 p-2">

                                    <label for="Image" class="form-label">{{$input['label']}}</label>
                                    <select class=" @if($input['select2']) select2 @else form-select @endif" id="edit{{$input['id']}}" name="{{$input['id']}}">
                                        @if(!$input['select2'])
                                            @foreach($input['items'] as $item)
                                                <option value="{{$item['value']}}" @if($item['selected']) selected @endif> {{$item['text']}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                    </div>
                                @elseif($input['input']=='textarea')
                                    <div class="col-md-6 col-lg-{{isset($input['width'])?$input['width']:'6'}} col-12 p-2">
                                        <div>
                                            <label for="textarea" class="form-label">{{$input['label']}}</label>
                                            <textarea class="form-control" rows="{{isset($input['width'])?$input['width']:'6'}}" cols="50" id="edit{{$input['id']}}" name="{{$input['id']}}" @isset($input['read_only']) @if($input['read_only']) readonly @endif  @endisset></textarea>
                                        </div>
                                    </div>
                                @elseif($input['input']=='html')
                                    <input type="hidden"  name="{{$input['id']}}" id="{{$input['id']}}" value="{{$input['value']}}">
                                    <div class="col-12">
                                        <div class="card">
                                            <h5 class="card-header">{{$input['label']}}</h5>
                                            <div class="card-body">
                                                <div id="full-editor{{$input['id']}}" style="height: 300px;">

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                @elseif($input['input']=='file')
                                    <div class="col-md-6 col-lg-{{isset($input['width'])?$input['width']:'6'}} col-12 p-2">
                                        <div>
                                            <label for="textarea" class="form-label">{{$input['label']}}</label>
                                            <input class="form-control" type="file"  id="edit{{$input['id']}}" name="{{$input['id']}}" >
                                        </div>
                                    </div>

                                @endif
                            @endforeach
                        @endisset
                    </div>
                    <button type="submit" class="btn btn-primary mt-8">{{__('buttons.save')}}</button>
                </form>

            </div>
        </div>
    </div>
@endsection




@push('scripts')

    @component('dashboard.layout.parts.admin-include-scripts', [
    'html_input'=>true,
        ])
    @endcomponent


    @php
        $dashboardSettingsConfig = [
            'inputs' => $data['inputs'],
            'assets' => array_values(array_filter([
            'quill',
            collect($data['inputs'])->contains(fn ($input) => $input['select2'] ?? false) ? 'select2' : null,
            ])),
        ];
    @endphp
    <script type="application/json" id="dashboard-settings-config">@json($dashboardSettingsConfig)</script>
    @dashboardVite('resources/js/back/pages/settings.js')


@endpush
