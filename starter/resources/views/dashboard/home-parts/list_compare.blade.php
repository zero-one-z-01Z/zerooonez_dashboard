<div class="col-xxl-4 col-md-6">
    <div class="card h-100">
        <div class="card-header d-flex justify-content-between">
            <div class="card-title mb-0">
                <h5 class="mb-1">{{__('admin.order_analysis')}}</h5>
{{--                <p class="card-subtitle">8.52k Social Visiters</p>--}}
            </div>
        </div>
        <div class="card-body">
            <ul class="p-0 m-0">
                @foreach($list_compare as $item)
                    <li class="mb-6 d-flex justify-content-between align-items-center">
                        <div class="badge bg-label-{{$item['icon_class']}} rounded p-1_5"><i class="icon-base ti tabler-shopping-cart icon-md"></i></div>
                        <div class="d-flex justify-content-between w-100 flex-wrap">
                            <div class="me-2 ms-2">
                                <h6 class="mb-0">{{$item['title']}}</h6>
                                <small class="text-body">{{$item['description']}}</small>
                            </div>
                            <div class="d-flex">

                                <i class="{{$item['percentage']>0?"icon-base ti tabler-chevron-up":($item['percentage']==0?'':"icon-base ti tabler-chevron-down")}} text-{{$item['class']}} me-1"></i>
                                <p class="ms-4 text-{{$item['class']}} mb-0">{{$item['percentage']}}%</p>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
