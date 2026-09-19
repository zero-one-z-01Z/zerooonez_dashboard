@extends('zerooonez-dashboard::layout.admin-main-layout')
@push('head')
    @component('zerooonez-dashboard::layout.parts.admin-include-head', [
    'char'=>true,
    ])
    @endcomponent
@endpush
@section('content')
<div class="content-wrapper">
    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">
        @canany(['view_dashboard_home'])
        <div class="row g-6">

            @foreach($numbers as $number)
                @include('zerooonez-dashboard::home-parts.numbers',$number)
            @endforeach
                @isset($avg)
                    @include('zerooonez-dashboard::home-parts.average-day',$avg)
                @endisset


                @isset($sales)
                    @include('zerooonez-dashboard::home-parts.sales-summary',$sales)
                @endisset

                @isset($total_earn)
                    @include('zerooonez-dashboard::home-parts.total_earn',$total_earn)
                @endisset

                @isset($circle_chart)
                    @include('zerooonez-dashboard::home-parts.circle_chat')
                @endisset


                @isset($list_compare)
                    @include('zerooonez-dashboard::home-parts.list_compare',$list_compare)
                @endisset

                @include('zerooonez-dashboard::home-parts.graph-year')

                @isset($progress)
                    @include('zerooonez-dashboard::home-parts.progress',$progress)
                @endisset






            <!-- Orders by Countries -->

        </div>
        @endcanany
    </div>
</div>
@endsection
@push('scripts')

    @component('zerooonez-dashboard::layout.parts.admin-include-scripts', [
    'analytics'=>true,
        ])
    @endcomponent
    @php
        $labels = [
    __('admin.January'),
    __('admin.February'),
    __('admin.March'),
    __('admin.April'),
    __('admin.May'),
    __('admin.June'),
    __('admin.July'),
    __('admin.August'),
    __('admin.September'),
    __('admin.October'),
    __('admin.November'),
    __('admin.December')
];
        $thisYear = __('admin.this_year');
        $previousYear = __('admin.previous_year');
    @endphp
    @php
        $dashboardHomeConfig = [
            'avg' => isset($avg) ? $avg['steps'] : null,
            'circle_chart' => $circle_chart ?? null,
            'categories' => $labels,
            'thisYear' => $thisYear,
            'previousYear' => $previousYear,
            'thisYearData' => $this_year_counts,
            'previousYearData' => $previous_Year_counts,
        ];
    @endphp
    <script type="application/json" id="dashboard-home-config">@json($dashboardHomeConfig)</script>

    @dashboardVite('resources/js/back/crud.js')
    @dashboardVite('resources/js/back/home.js')


@endpush
