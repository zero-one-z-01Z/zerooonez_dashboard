<div class="col-12 col-md-6 col-xxl-4 order-2 order-xl-0">
    <div class="card h-100">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 card-title">{{__('admin.total_earn_title')}}</h5>
            </div>
{{--            <div class="d-flex align-items-center">--}}
{{--                <h2 class="mb-0 me-2">87%</h2>--}}
{{--                <i class="icon-base ti tabler-chevron-up text-success me-1"></i>--}}
{{--                <h6 class="text-success mb-0">25.8%</h6>--}}
{{--            </div>--}}
        </div>
        <div class="card-body">
            <div id="totalEarningChart"></div>

            @foreach($total_earn as $item)
                <div class="d-flex align-items-start mb-2">
                    <div class="badge rounded bg-label-secondary p-2 me-4 rounded"><i class="icon-base ti tabler-currency-dollar icon-md"></i></div>
                    <div class="d-flex justify-content-between w-100 gap-2 align-items-center">
                        <div class="me-2">
                            <h6 class="mb-0">{{$item['title']}}</h6>
                            <small class="text-body">{{$item['description']}}</small>
                        </div>
                        <div class="d-flex align-items-center">
                            {{--                <h2 class="mb-0 me-2">87%</h2>--}}
                            <i class="{{$item['percentage']>0?"icon-base ti tabler-chevron-up":($item['percentage']==0?'':"icon-base ti tabler-chevron-down")}} text-{{$item['class']}} me-0"></i>
                            <h6 class="text-{{$item['class']}} mb-0">{{$item['percentage']}}%</h6>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
