@extends('zerooonez-dashboard::layout.admin-main-layout')
@push('head')
    <link rel="stylesheet" href="{{asset('dashboard/vendor/libs/swiper/swiper.css')}}"/>

    <!-- Page CSS -->
    <link rel="stylesheet" href="{{asset('dashboard/vendor/css/pages/ui-carousel.css')}}"/>
    @component('zerooonez-dashboard::layout.parts.admin-include-head', [
        'datatable' => true,
        'select2' => true,
        'daterangepicker' => true,
        'boundary' => isset($data['boundary']),
        'dropzone' => isset($data['dropzone']),
        'html_input' => isset($data['html_editor']),
        'map' => isset($data['map']),
    ])
    @endcomponent
@endpush
@section('content')
    <div class="content-wrapper" style="margin-top: {{ '0px' }}">
        <!-- Content -->
        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row g-6">
                <!-- Product List Table -->
                <div class="card">
                    <div class="row pt-6">
                        <!-- Customer-detail Sidebar -->
                        <div class="col-12 order-1 order-md-0">
                            <!-- Customer-detail Card -->
                            <div class="card mb-6">
                                <div class="card-body pt-12">
                                    <div class="customer-avatar-section">
                                        <div class="d-flex align-items-center flex-column">
                                            @isset($data['image'])
                                                <img class="img-fluid rounded mb-4" src="{{ $data['image'] }}"
                                                     height="120"
                                                     width="120" alt="User avatar">
                                            @endif
                                            <div class="customer-info text-center mb-6">
                                                @isset($data['title'])
                                                    <h5 class="mb-0">{{ $data['title'] }}</h5>
                                                @endisset
                                                @isset($data['sub_title'])
                                                    <span>{{ $data['sub_title'] }}</span>
                                                @endisset
                                            </div>
                                        </div>
                                    </div>
                                    @isset($data['statistics'])
                                        <div
                                            class="d-flex justify-content-around flex-wrap mb-6 gap-0 gap-md-3 gap-lg-4">
                                            @foreach ($data['statistics'] as $statistic)
                                                <div class="d-flex align-items-center gap-4 me-5">
                                                    <div class="avatar">
                                                        <div class="avatar-initial rounded bg-label-primary">
                                                            <i class="icon-base ti tabler-{{ $statistic['icon'] }} icon-lg"></i>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h5 class="mb-0">{{ $statistic['title'] }}</h5>
                                                        <span>{{ $statistic['description'] }}</span>
                                                    </div>
                                                </div>
                                            @endforeach

                                        </div>
                                    @endisset


                                    @isset($data['details'])
                                        <div class="info-container">
                                            <h5 class="pb-4 border-bottom text-capitalize mt-6 mb-4">{{ __('admin.details') }}</h5>
                                            <div class="row g-4">
                                                @foreach ($data['details'] as $detail)
                                                    <div class="col-md-6 col-12">
                                                        <div class="d-flex align-items-center">
                                                            <div
                                                                class="badge rounded-pill bg-label-primary p-2 me-3">
                                                                <i class="ti tabler-list-details ti-sm"></i>
                                                            </div>
                                                            <div>
                                                                <p class="mb-0 text-muted">{{ $detail['title'] }}</p>
                                                                <h6 class="mb-0 text-body @isset($detail['class']){{ $detail['class'] }}@endisset">
                                                                    {{ $detail['description'] }}</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endisset

                                    @isset($data['tab_details'])
                                        <div class="col-12">
                                            <h5 class="pb-4 border-bottom text-capitalize mt-6 mb-4">{{ __('admin.details') }}</h5>
                                            <div class="nav-align-top">
                                                <ul class="nav nav-pills mb-4 nav-fill flex-wrap justify-content-center"
                                                    role="tablist">
                                                    @foreach ($data['tab_details'] as $tabKey => $tabContent)
                                                        <li class="nav-item mb-1 mb-sm-0">
                                                            <button type="button"
                                                                    class="nav-link {{ $loop->index == 0 ? 'active' : '' }}"
                                                                    role="tab" data-bs-toggle="tab"
                                                                    data-bs-target="#navs-pills-justified-{{ $tabKey }}"
                                                                    aria-controls="navs-pills-justified-{{ $tabKey }}"
                                                                    aria-selected="false">
                                                                    <span
                                                                        class="d-none d-sm-inline-flex align-items-center">
                                                                        @isset($tabContent['icon'])
                                                                            <i
                                                                                class="icon-base ti tabler-{{ $tabContent['icon'] }} icon-sm me-1_5"></i>
                                                                        @endisset
                                                                        {{ __('admin.' . $tabKey) }}
                                                                    </span>
                                                            </button>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                                <div class="tab-content">
                                                    @foreach ($data['tab_details'] as $tabKey => $tabContent)
                                                        <div
                                                            class="tab-pane fade {{ $loop->index == 0 ? 'show active' : '' }}"
                                                            id="navs-pills-justified-{{ $tabKey }}" role="tabpanel">
                                                            <div class="row g-4">
                                                                @foreach ($tabContent as $key => $value)

                                                                    @if ($key !== 'icon')
                                                                        @if($key === 'images' && !empty($value))
                                                                            <div class="col-12">
                                                                                <h6 class="text-body-secondary mt-4">@lang('admin.images')</h6>
                                                                                <div id="swiper-gallery">
                                                                                    <div class="swiper gallery-top">
                                                                                        <div class="swiper-wrapper">
                                                                                            @foreach($value as $image)

                                                                                                <div
                                                                                                    class="swiper-slide"
                                                                                                    style="background-image:url('{{$image}}');background-size: contain; background-repeat: no-repeat; background-position: center;">
                                                                                                </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                        <!-- Add Arrows -->
                                                                                        <div
                                                                                            class="swiper-button-next swiper-button-white"></div>
                                                                                        <div
                                                                                            class="swiper-button-prev swiper-button-white"></div>
                                                                                    </div>
                                                                                    <div class="swiper gallery-thumbs">
                                                                                        <div class="swiper-wrapper">
                                                                                            @foreach($value as $image)
                                                                                                <div
                                                                                                    class="swiper-slide"
                                                                                                    style="background-image:url('{{$image}}');background-size: contain; background-repeat: no-repeat; background-position: center;">
                                                                                                </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                        @else
                                                                            <div class="col-md-6 col-12">
                                                                                <div class="d-flex align-items-center">
                                                                                    <div
                                                                                        class="badge rounded-pill bg-label-primary p-2 me-3">
                                                                                        <i class="ti tabler-list-details ti-sm"></i>
                                                                                    </div>
                                                                                    <div>
                                                                                        <p class="mb-0 text-muted">
                                                                                            {{ __('inputs.' . $key) }}</p>
                                                                                        @if(is_bool($value) || $value === 0 || $value === 1)
                                                                                            @if($value)
                                                                                                <span
                                                                                                    class="badge bg-success">@lang('admin.yes')</span>
                                                                                            @else
                                                                                                <span
                                                                                                    class="badge bg-danger">@lang('admin.no')</span>
                                                                                            @endif

                                                                                        @elseif(!empty($value))
                                                                                            @php
                                                                                                // Better URL detection
                                                                                                $isUrl = is_string($value) && (
                                                                                                    filter_var($value, FILTER_VALIDATE_URL) !== false ||
                                                                                                    preg_match('/^(https?:\/\/|www\.)/i', $value)
                                                                                                );
                                                                                            @endphp

                                                                                            @if($isUrl)
                                                                                                <a href="{{ $value }}"
                                                                                                   target="_blank"
                                                                                                   class="btn btn-sm btn-primary">
                                                                                                    @lang('buttons.view')
                                                                                                </a>
                                                                                            @else
                                                                                                <span class="d-inline-block text-break" style="max-width: 100%; word-wrap: break-word;">
        {{ $value }}
    </span>
                                                                                            @endif
                                                                                        @else
                                                                                            -
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endisset

                                    <!-- Gallery effect-->
                                    {{--                                    @isset($data['images'])--}}
                                    {{--                                        <div class="col-12">--}}
                                    {{--                                            <h6 class="text-body-secondary mt-4">Thumbs Gallery</h6>--}}
                                    {{--                                            <div id="swiper-gallery">--}}
                                    {{--                                                <div class="swiper gallery-top">--}}
                                    {{--                                                    <div class="swiper-wrapper">--}}
                                    {{--                                                        @foreach($data['images'] as $image)--}}
                                    {{--                                                            <div class="swiper-slide"--}}
                                    {{--                                                                 style="background-image:url('{{$image}}')">--}}
                                    {{--                                                            </div>--}}
                                    {{--                                                        @endforeach--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                    <!-- Add Arrows -->--}}
                                    {{--                                                    <div class="swiper-button-next swiper-button-white"></div>--}}
                                    {{--                                                    <div class="swiper-button-prev swiper-button-white"></div>--}}
                                    {{--                                                </div>--}}
                                    {{--                                                <div class="swiper gallery-thumbs">--}}
                                    {{--                                                    <div class="swiper-wrapper">--}}
                                    {{--                                                        @foreach($data['images'] as $image)--}}
                                    {{--                                                            <div class="swiper-slide"--}}
                                    {{--                                                                 style="background-image:url('{{$image}}')">--}}
                                    {{--                                                            </div>--}}
                                    {{--                                                        @endforeach--}}
                                    {{--                                                    </div>--}}
                                    {{--                                                </div>--}}
                                    {{--                                            </div>--}}
                                    {{--                                        </div>--}}
                                    {{--                                    @endisset--}}
                                </div>
                            </div>

                            <!-- /Plan Card -->
                        </div>
                        <!--/ Customer Sidebar -->

                        <!-- Customer Content -->
                        @isset($data['tabs'])
                            <div class="col-12 order-0 order-md-1">
                                <!-- Customer Pills -->
                                <div class="nav-align-top">
                                    <ul class="nav nav-pills flex-column flex-md-row mb-6 row-gap-2 flex-wrap">
                                        {{-- data-link يشير إلى get_data: تعريف الجدول ونوافذه، وليس get_datatable الذي يعيد الصفوف. --}}
                                        @foreach ($data['tabs'] as $index => $tab)
                                            <li class="nav-item">
                                                <a class="nav-link {{ $index === 0 ? 'active' : '' }}"
                                                   href="javascript:void(0);" data-link="{{ $tab['link'] }}">
                                                    {{ $tab['title'] }}
                                                </a>
                                            </li>
                                        @endforeach

                                    </ul>
                                </div>
                                <!--/ Customer Pills -->
                                @isset($data['details2'])
                                    @include('zerooonez-dashboard::item-components.info', $data['details2'])
                                @endisset
                                <!-- Invoice table -->
                                {{-- navigation.js يعرض نتيجة التحميل هنا ويقفل منطقة الجدول مؤقتًا باستخدام inert. --}}
                                <div id="dashboard-item-table-status" role="status" aria-live="polite" class="px-4 py-2" hidden></div>
                                <div class="card-datatable" id="dashboard-item-table-region">
                                    <table class="datatables table" id="table_id">
                                        <thead></thead>
                                    </table>
                                </div>
                                <!-- /Invoice table -->
                            </div>
                        @endisset
                        <!--/ Customer Content -->
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection


{{--
    المضيف ثابت في layout وخارج الجدول، أما النماذج داخله فتستبدل في كل طلب ناجح.
    dashboardTabResponse يرسم modal-content على الخادم ويعيد fragments.modals.
    لا تضف all-modals هنا ببيانات صفحة التفاصيل: نماذج التبويب تختلف عن بيانات السجل الأب.
    راجع resources/js/back/item-tables/README.md لترتيب الإغلاق والاستبدال والتهيئة.
--}}
@push('modals')
    <div id="dashboard-item-table-modals"></div>
@endpush

@push('scripts')

    <!-- Vendors JS -->
    <script src="{{asset('dashboard/vendor/libs/swiper/swiper.js')}}"></script>
    <!-- Page JS -->
    <script src="{{asset('dashboard/js/ui-carousel.js')}}"></script>

    @component('zerooonez-dashboard::layout.parts.admin-include-scripts', [
        'datatable' => true,
        'select2' => true,
        'daterangepicker' => true,
        'have_validation' => true,
        'boundary' => isset($data['boundary']),
        'dropzone' => isset($data['dropzone']),
        'html_input' => isset($data['html_editor']),
        'map' => isset($data['map']),
    ])
    @endcomponent
    {{-- مرشحات السجل الأب ثابتة أثناء التنقل؛ تعريف كل تبويب وحقوله يأتي لاحقًا من مساره. --}}
    <script type="application/json" id="dashboard-item-table-definition">@json(array_column($data['internal_filters'] ?? [], 'value', 'key'))</script>
    @dashboardVite('resources/js/back/item-table.js')

@endpush
