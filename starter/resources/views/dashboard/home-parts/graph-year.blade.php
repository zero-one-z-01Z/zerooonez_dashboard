<div class="col-xxl-6 col-lg-7">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="card-title mb-0">
                <h5 class="mb-1">{{__('admin.activity_this_month')}}</h5>
                <p class="card-subtitle">{{__('admin.records_this_month')}}: {{$total_orders}}</p>
            </div>
{{--            <div class="btn-group">--}}
{{--                <button type="button" class="btn btn-label-primary">January</button>--}}
{{--                <button type="button" class="btn btn-label-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">--}}
{{--                    <span class="visually-hidden">Toggle Dropdown</span>--}}
{{--                </button>--}}
{{--                <ul class="dropdown-menu">--}}
{{--                    <li><a class="dropdown-item" href="javascript:void(0);">January</a></li>--}}
{{--                    <li><a class="dropdown-item" href="javascript:void(0);">February</a></li>--}}
{{--                    <li><a class="dropdown-item" href="javascript:void(0);">March</a></li>--}}
{{--                    <li><a class="dropdown-item" href="javascript:void(0);">April</a></li>--}}
{{--                    <li><a class="dropdown-item" href="javascript:void(0);">May</a></li>--}}
{{--                    <li><a class="dropdown-item" href="javascript:void(0);">June</a></li>--}}
{{--                    <li><a class="dropdown-item" href="javascript:void(0);">July</a></li>--}}
{{--                    <li><a class="dropdown-item" href="javascript:void(0);">August</a></li>--}}
{{--                    <li><a class="dropdown-item" href="javascript:void(0);">September</a></li>--}}
{{--                    <li><a class="dropdown-item" href="javascript:void(0);">October</a></li>--}}
{{--                    <li><a class="dropdown-item" href="javascript:void(0);">November</a></li>--}}
{{--                    <li><a class="dropdown-item" href="javascript:void(0);">December</a></li>--}}
{{--                </ul>--}}
{{--            </div>--}}
        </div>
        <div class="card-body">
            <div id="yearStatisticsChart"></div>
        </div>
    </div>
</div>
