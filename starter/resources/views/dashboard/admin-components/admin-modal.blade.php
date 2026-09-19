<div class="modal fade" id="{{$id}}"
@if (isset($reset_on_close) && $reset_on_close)
reset-on-close
@endif
>
    <div class="modal-dialog modal-content modal-xl
{{--    @isset($modal_size)--}}
{{--        {{$modal_size}} modal-dialog-scrollable--}}
{{--    @endisset--}}
     ">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">{{$title}}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    {{-- <i class="ti ti-x"></i> --}}
                </button>
            </div>
            <form novalidate action="{{$form_action}}" id="{{$form_id}}"
            @isset($schema_fields) data-dashboard-field-types='@json($schema_fields)' @endisset
            data-event_on="submit"  method="POST"
            @isset($call_function)
            data-call_function="{{$call_function}}"
            @endisset
            @isset($scroll)
                style="overflow-y: scroll;"
            @endisset
            autocomplete="false"
            >
                <div class="modal-body">
                    {{-- @if (!isset($remove_csrf))
                    @csrf
                    @endif --}}

                    {{$body}}
                </div>
                <div class="modal-footer
                @isset($footer_class)
                    {{$footer_class}}
                @endisset
                ">
                    @if((!isset($hide_buttons))||!$hide_buttons)
                    @if($action=='custom')
                        <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">{{$button['cancel']}}</a>
                        <button type="submit" class="btn btn-primary">{{$button['submit']}}</button>
                    @elseif ($action == 'add')
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">{{__('buttons.cancel')}}</a>
                    <button type="submit" class="btn btn-primary">{{__('buttons.save')}}</button>
                    @elseif($action == 'edit')
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal">{{__('buttons.cancel')}}</a>
                    <button type="submit" class="btn btn-primary">{{__('buttons.update')}}</button>
                    @elseif ($action == 'confirm')
                    <a href="#" class="btn btn-light me-2" data-bs-dismiss="modal"
                    @isset($return_call_function)
                        data-event_on="click" data-call_function="{{$return_call_function}}"
                    @endisset
                    >{{__('general.cancel')}}</a>
                    <button type="submit" class="btn btn-primary">{{__('buttons.yes')}}</button>
                    @else
                    @endif
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
