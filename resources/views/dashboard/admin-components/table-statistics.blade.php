@if(isset($data['statistics'])&&count($data['statistics'])>0)
    <div class="card mb-6">
        <div class="card-widget-separator-wrapper">
            <div class="card-body card-widget-separator">
                <div class="row gy-4 gy-sm-1">
                    {{--                primary - success - secondary - info - warning - danger - light - dark - gray - facebook  - twiiter - google - instagram - linkedin ---}}
                    @foreach($data['statistics'] as $statistic)
                        <div class="col-sm-6 col-lg-3">
                            <div
                                class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                                <div>
                                    <p class="mb-1">{{$statistic['title']}}</p>
                                    <h4 class="mb-1">{{$statistic['value']}}</h4>
                                    <p class="mb-0"><span class="me-2">{{$statistic['sub_title']}}</span><span
                                            class="badge bg-label-{{$statistic['percentage_class']}}">{{$statistic['percentage']}}</span></p>
                                </div>
                                <span class="avatar me-sm-6">
                          <span class="avatar-initial rounded"><i
                                  class="icon-base ti tabler-{{$statistic['icon']}} icon-28px text-heading"></i></span>
                        </span>
                            </div>
                            <hr class="d-none d-sm-block d-lg-none me-6">

                        </div>
                    @endforeach

                </div>
            </div>
        </div>
    </div>
@endif
