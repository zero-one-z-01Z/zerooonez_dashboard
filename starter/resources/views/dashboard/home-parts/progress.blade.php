<div class="col-xxl-6">
    <div class="card h-100">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div class="card-title mb-0">
                <h5 class="m-0 me-2">{{__('admin.current_orders')}}</h5>
            </div>
        </div>

        <div class="card-body">
            <div class="d-none d-lg-flex vehicles-progress-labels mb-6">
                @foreach($progress as $item)
                    <div class="vehicles-progress-label on-the-way-text" style="width: {{$item['value']}}%;max-height: 20px;overflow: auto">{{$item['title']}}</div>
                @endforeach

            </div>
            <div class="vehicles-overview-progress progress rounded-3 mb-3 bg-transparent overflow-hidden" style="height: 46px;">
                @foreach($progress as $item)
                    <div class="progress-bar fw-medium text-start shadow-none bg-{{$item['class']}} text-heading px-4 rounded-0" role="progressbar" style="width: {{$item['value']}}%" aria-valuenow="{{$item['value']}}" aria-valuemin="0" aria-valuemax="100">{{$item['value']}}%</div>
                @endforeach
            </div>
            <div class="table-responsive">
                <table class="table card-table table-border-top-0 table-border-bottom-0">
                    <tbody>
                    @foreach($progress as $item)
                        <tr>
                            <td class="w-50 ps-0">
                                <div class="d-flex justify-content-start align-items-center">
                                    <div class="me-2">
                                        <i class="icon-base ti tabler-{{$item['icon']}} icon-lg text-heading"></i>
                                    </div>
                                    <h6 class="mb-0 fw-normal">{{$item['title']}}</h6>
                                </div>
                            </td>
                            <td class="text-end pe-0 text-nowrap">
                                <h6 class="mb-0">{{$item['description']}}</h6>
                            </td>
                            <td class="text-end pe-0">
                                <span>{{$item['value']}}%</span>
                            </td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
