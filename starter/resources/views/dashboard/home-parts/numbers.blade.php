
{{--            [primary - secondary - success - info - warning - danger - light - dark - gray]--}}


<a href="{{$number['link']}}" class="col-lg-3 col-sm-6">

        <div class="card card-border-shadow-{{$number['class']}} h-100">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2">
                    <div class="avatar me-4">
                        <span class="avatar-initial rounded bg-label-{{$number['class']}}"><i class="icon-base ti tabler-{{$number['icon']}} icon-28px"></i></span>
                    </div>
                    <h4 class="mb-0">{{$number['value']}}</h4>
                </div>
                <p class="mb-1">{{$number['title']}}</p>
                <p class="mb-0">
                    <span class="text-heading fw-medium me-2">{{$number['growth']}}</span>
                    <small class="text-body-secondary">{{$number['description']}}</small>
                </p>
            </div>
        </div>

</a>
