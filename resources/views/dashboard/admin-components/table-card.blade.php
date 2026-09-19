<!-- Filter Section -->

    @if (!isset($header) || $header == true)
        <div class=" border-bottom d-flex align-items-center justify-content-between flex-wrap pt-3">
            <h4 class="mb-3">{{ $card_title }}</h4>
            <div class="d-flex align-items-center flex-wrap ">
                @if ($daterange_filter)
                    <div class="input-icon-start mb-3 me-2 position-relative">
{{--                        <span class="icon-addon">--}}
{{--                            <i class="icon-base ti tabler-calendar-week"></i>--}}
{{--                        </span>--}}
                        <input type="text" id="datatable_daterange_filter" name="{{ $daterannge_filter_name }}"
                            class="form-control date-range
                            @isset($daterange_class)
                              {{ $daterange_class }}
                            @else
                            bookingrange
                            @endisset
"
                            @isset($daterange_filter_tooltip)
                            data-bs-toggle="tooltip" title="{{ $daterange_filter_tooltip }}"
                            @endisset>
                    </div>
                @endif
               @isset($filter)
                        @if ($filter)
                            <div class="dropdown mb-3 me-2">
                                <a href="javascript:void(0);" class="btn btn-info dropdown-toggle"
                                   @if (isset($filter_display) && $filter_display == 'modal') data-bs-toggle="modal" data-bs-target="#filters_modal"
                                   @else
                                       data-bs-toggle="dropdown" data-bs-auto-close="outside" @endif><i
                                        class="me-2"></i>{{ __('admin.filters') }}</a>

                                @if (isset($filter_display) && $filter_display == 'modal')
                                    @push('modals')
                                        @component('zerooonez-dashboard::admin-components.admin-modal', [
                                            'form_id' => 'datatable_filter_form',
                                            'form_action' => '#',
                                            'id' => 'filters_modal',
                                            'title' =>  __('admin.filters'),
                                            'action' => 'none',
                                            'modal_size' => 'modal-lg',
                                            'remove_csrf' => true,
                                        ])
                                            @slot('body')
                                                <div class="">
                                                    {{ $filter }}
                                                </div>
                                                <div class="p-3 d-flex align-items-center justify-content-end">
                                                    <a id="datatable_filter_cancel" href="#" onclick="closeFilterModal()"
                                                       class="btn btn-light me-3">{{ __('buttons.cancel') }}</a>
                                                    <button id="datatable_filter_submit" type="submit"
                                                            class="btn btn-primary">{{ __('buttons.confirm') }}</button>
                                                </div>
                                            @endslot
                                        @endcomponent
                                    @endpush
                                @else
                                    <div class="dropdown-menu drop-width"
                                         @isset($filter_drop_style)
                                             style="{{ $filter_drop_style }}"
                                        @endisset>
                                        <form action="#" id="datatable_filter_form">
                                            <div class="d-flex align-items-center border-bottom p-3">
                                                <h4>{{ __('admin.filters') }}</h4>
                                            </div>
                                            <div class="p-3 border-bottom">
                                                {{ $filter }}
                                            </div>
                                            <div class="p-3 d-flex align-items-center justify-content-end">
                                                <button id="datatable_filter_cancel"  class="btn btn-light me-3" onclick="closeFilterModal()">{{ __('buttons.cancel') }}</button>


                                                <button id="datatable_filter_submit" type="submit"
                                                        class="btn btn-primary">{{ __('buttons.confirm') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                @endif


                            </div>
                        @endif
                    @endisset
                @isset($additional_element)
                    {{ $additional_element }}
                @endisset
            </div>
        </div>
    @endif
@if (isset($filter_badges) && $filter_badges)
    <div class="mb-0 mt-3 ms-3 filterBadges">

    </div>
@endif




<!-- /Filter Section -->
