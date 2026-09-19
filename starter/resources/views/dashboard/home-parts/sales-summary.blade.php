<div class="col-xl-3 col-sm-6">
    <div class="card h-100">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <p class="mb-0 text-body">{{$sales['title']}}</p>
                <p class="card-text fw-medium text-{{$sales['class']}}">{{$sales['growth']}}</p>
            </div>
            <h4 class="card-title mb-1">{{$sales['value']}} SAR</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-4">
                    <div class="d-flex gap-2 align-items-center mb-2">
                        <span class="badge bg-label-info p-1 rounded"><i class="icon-base ti tabler-shopping-cart icon-sm"></i></span>
                        <p class="mb-0">{{$sales['left_title']}}</p>
                    </div>
                    <h5 class="mb-0 pt-1">{{$sales['left_percentage']}}%</h5>
                    <small class="text-body-secondary">{{$sales['left_value']}} sar</small>
                </div>
                <div class="col-4">
                    <div class="divider divider-vertical">
                        <div class="divider-text">
                            <span class="badge-divider-bg bg-label-secondary">VS</span>
                        </div>
                    </div>
                </div>
                <div class="col-4 text-end">
                    <div class="d-flex gap-2 justify-content-end align-items-center mb-2">
                        <p class="mb-0">{{$sales['right_title']}}</p>
                        <span class="badge bg-label-primary p-1 rounded"><i class="icon-base ti tabler-home icon-sm"></i></span>
                    </div>
                    <h5 class="mb-0 pt-1">{{$sales['right_percentage']}}%</h5>
                    <small class="text-body-secondary">{{$sales['right_value']}} sar</small>
                </div>
            </div>
            <div class="d-flex align-items-center mt-6">
                <div class="progress w-100" style="height: 10px;">
                    <div class="progress-bar bg-info" style="width: {{$sales['left_percentage']}}%" role="progressbar" aria-valuenow="{{$sales['left_percentage']}}" aria-valuemin="0" aria-valuemax="100"></div>
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{$sales['right_percentage']}}%" aria-valuenow="{{$sales['right_percentage']}}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>
</div>
