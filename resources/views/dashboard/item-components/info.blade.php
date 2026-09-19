<div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
    <div class="card mb-6">
        <div class="card-body">
            <div class="info-container">
                <h5 class="pb-2 border-bottom text-capitalize mt-2 mb-2">{{__('admin.details')}}</h5>
                <ul class="list-unstyled mb-6">
                    @foreach($details as $detail)
                        <li class="mb-2">
                            <span class="h6 me-1">{{$detail['title']}}:</span>
                            <span class="@isset($detail['class']){{$detail['class']}}@endisset">{{$detail['description']}}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
