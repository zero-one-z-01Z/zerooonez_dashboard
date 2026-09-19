<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8" />
    <title>{{env('APP_NAME')}}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow" />
    {{--    how search engin deal with you--}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, shrink-to-fit=no" />
    {{--    how show on mobile--}}

    <link rel="icon" type="image/x-icon" href="{{asset('logo.svg')}}" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="{{asset('dashboard/')}}/vendor/js/helpers.js"></script>
    <link rel="stylesheet" href="{{asset('dashboard/auth')}}/css/style.css">
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/notyf/notyf.css">
    <script src="{{asset('dashboard/')}}/js/config.js"></script>
</head>



<body class="img js-fullheight" style="background-image: url({{ asset('dashboard/auth/images/bg.jpg') }});">

<div id="global-loader">
    <div class="page-loader"></div>
</div>

<section class="ftco-section">
    <div class="container">

        <div class="row justify-content-center mb-4">
            <img src="{{ asset('logo.svg') }}" width="50">
        </div>

        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-wrap p-0">

                    <h3 class="text-center mb-4">@yield('title')</h3>

                    @yield('content')

                </div>
            </div>
        </div>

    </div>
</section>

<script src="{{asset('dashboard/auth')}}/js/jquery.min.js"></script>
<script src="{{asset('dashboard/auth')}}/js/popper.js"></script>
<script src="{{asset('dashboard/auth')}}/js/bootstrap.min.js"></script>
<script src="{{asset('dashboard/auth')}}/js/main.js"></script>
<script src="{{asset('dashboard/')}}/vendor/libs/notyf/notyf.js"></script>
<script src="{{asset('dashboard/')}}/js/ui-toasts.js"></script>

@if(session()->has('message'))
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            showNotification(
                @json(session('type')),
                @json(session('message'))
            );
        });
    </script>
@endif
@stack('scripts')

</body>
</html>
