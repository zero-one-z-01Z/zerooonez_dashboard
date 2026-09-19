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
  <div id="global-loader">
      <div class="page-loader"></div>
  </div>
	<body class="img js-fullheight" style="background-image: url({{asset('dashboard/auth')}}/images/bg.jpg);">
	<section class="ftco-section">
		<div class="container">
			<div class="row justify-content-center">
				<span class="app-brand-logo demo">
              <span class="text-primary">
                <img src="{{asset('logo.svg')}}" style="width:50px; height:50px">
              </span>
            </span>
			</div>

			<div class="row justify-content-center">
				<div class="col-md-6 col-lg-4">
					<div class="login-wrap p-0">
		      	<h3 class="mb-4 text-center">{{__('admin.app_dashboard')}}</h3>
		      	<form id="login_form" action="{{route((isset($type))?($type.'.send_login'): 'admin.send_login')}}" method="post" class="signin-form">
                    @csrf
                    @php
                    $required = false;
//                    if(isset($type)&&in_array($type,['customer_service','customer_service_employee'])){
//                        $required = false;
//                    }
                    @endphp
                    @if(isset($type)&&in_array($type,['customer_service','customer_service_employee']))
                        <div class="form-group">
                            <input type="tel" class="form-control" name="phone" placeholder="{{__('inputs.phone')}}" required>
                        </div>
                    @else
                        <div class="form-group">
                            <input type="email" class="form-control" name="email" placeholder="{{__('inputs.email')}}" required>
                        </div>
                    @endif
	            <div class="form-group">
	              <input id="password-field" type="password" name="password" class="form-control" placeholder="{{__('inputs.password')}}" @if($required) required @endif>
	            </div>
	            <div class="form-group">
	            	<button type="submit" class="form-control btn btn-primary submit px-3">{{__('buttons.sign_in')}}</button>
	            </div>
	          </form>
{{--	          <p class="w-100 text-center">&mdash; Or Sign In With &mdash;</p>--}}
{{--	          <div class="social d-flex text-center">--}}
{{--	          	<a href="#" class="px-2 py-2 mr-md-1 rounded"><span class="ion-logo-facebook mr-2"></span> Facebook</a>--}}
{{--	          	<a href="#" class="px-2 py-2 ml-md-1 rounded"><span class="ion-logo-twitter mr-2"></span> Twitter</a>--}}
{{--	          </div>--}}
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
	@dashboardVite('resources/js/back/pages/login.js')
		</body>
</html>
