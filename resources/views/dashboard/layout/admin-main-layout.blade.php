<!doctype html>
<html lang="{{app()->getLocale()}}" class="layout-navbar-fixed layout-menu-fixed layout-compact" dir="{{app()->getLocale()=='ar'?"rtl":"ltr"}}" data-skin="default"
      data-bs-theme="light" data-bs-language="{{app()->getLocale()}}"  data-assets-path="{{asset('dashboard')}}/" data-template="vertical-menu-template" data-language="{{app()->getLocale()}}">

<head>
    <meta charset="utf-8" />
    <title>{{env('APP_NAME')}}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow" />
{{--    how search engin deal with you--}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, shrink-to-fit=no" />
{{--    how show on mobile--}}

    <link rel="icon" type="image/x-icon" href="{{asset('logo.svg')}}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/fonts/flag-icons.css" />


    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/css/core.css" />
    @if(app()->getLocale() == 'ar')
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/css/rtl.css" />
    @endif
    {{--    @dashboardVite(['resources/css/app.css', 'resources/js/app.js'])--}}
    <!-- Helpers  -->
    <script>
        var assetsPath = "{{asset('dashboard/')}}/";
        var mustSelectItem = "{{__('admin.must_select_item')}}";
        var areYouSure = "{{__('admin.are_you_sure')}}";
        var cantReturnBack = "{{__('admin.cant_return_back')}}";
        var yesDelete = "{{__('buttons.yes_delete')}}";
        var cancelText = "{{__('buttons.cancel')}}";
        var deleteSuccess = "{{__('admin.deleted_successfully')}}";
        var itemDeletedSuccess = "{{__('admin.item_deleted_successfully')}}";
        var addSuccess = "{{__('admin.added_successfully')}}";
        var updateSuccess = "{{__('admin.updated_successfully')}}";
        var success = "{{__('admin.success')}}";
        var okText = "{{__('buttons.done')}}";
        var addNewItem = "{{__('buttons.add_new_item')}}";
        var actionsText = "{{__('admin.actions')}}";
        window.seeMoreText = "{{__('admin.see_more')}}";
        window.view_text = '{{ __("buttons.view") }}';
        window.seeLessText = "{{__('admin.see_less')}}";
        window.choose = '{{ __("admin.choose") }}';
        window.import_text = '{{ __("admin.import") }}';
        window.export_text = '{{ __("admin.export") }}';
        window.delete_all_text = '{{ __("buttons.delete_all") }}';
        window.search_hint = '{{ __("admin.search") }}';
        window.all_permissions = ['open'];
        @php
            $show_password = show_password_modal();
            $change_lang_url = get_change_language_link();

        @endphp

        @if(admin()->check())
            @php
                $all_permissions = admin_user()->all_permissions();
            @endphp
        window.all_permissions = {!! json_encode($all_permissions) !!};

        @endif
        const langRouteBase = "{{ $change_lang_url }}";
            window.show_password_modal = {!! $show_password !!};
        window.current_direction = "{{app()->getLocale()=='ar'?'rtl':'ltr'}}";
    </script>
    <script src="{{asset('dashboard/')}}/vendor/js/helpers.js"></script>
    <script src="{{asset('dashboard/')}}/js/config.js"></script>
    <script src="{{asset('dashboard/')}}/vendor/js/template-customizer.js"></script>
    <script>
        (function () {
            // var templateName = "YourTemplateName";
            var a = localStorage.getItem("templateCustomizer-" + templateName + "--Theme") || (window.templateCustomizer?.settings?.defaultStyle ?? document.documentElement.getAttribute("data-bs-theme"));
            "light"; // fallback
            // alert(a);
            document.documentElement.setAttribute("data-bs-theme", a);
        })();
    </script>
    <style>
        #global-loader {
            display: none; /* Initially hide the loader */
            position: fixed;
            z-index: 9999; /* Ensure the loader appears above all other content */
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: transparent; /* Semi-transparent white background */
        }

        #global-loader .loader-img {
            position: absolute;
            top: 50%;
            left: 50%;
            background-color: transparent;
            transform: translate(-50%, -50%);
        }
        .select2-container {
            z-index: 999999 !important;
        }

        .modal {
            overflow: visible !important;
        }
        .modal-body {
            max-height: calc(100vh - 230px);
            overflow-y: auto;
        }
    </style>
    @stack('head')
</head>
<body class="layout-mode-rtl">
<div id="global-loader">
    <img src="{{asset("dashboard/img/loader.svg")}}" class="loader-img" alt="Loader">
</div>

<div class="layout-wrapper layout-content-navbar  ">
    <div class="layout-container">
        @include('zerooonez-dashboard::layout.parts.admin-sidebar')
        <div class="layout-page">
    @include('zerooonez-dashboard::layout.parts.admin-header')
            @yield('content')
        </div>


    </div>
    <div class="layout-overlay layout-menu-toggle"></div>
    <div class="drag-target"></div>
</div>
<!-- /Main Wrapper -->
@stack('modals')

{{--@stack('update_password_modal')--}}

@include('zerooonez-dashboard::admin-components.update-password')


@include('zerooonez-dashboard::layout.parts.dashboard-runtime-config')
@stack('scripts')
{{--<script>--}}
{{--    document.addEventListener("DOMContentLoaded", function () {--}}
{{--        var myModal = new bootstrap.Modal(document.getElementById('update_password_form_modal'));--}}
{{--        myModal.show();--}}
{{--    });--}}
{{--</script>--}}


</body>

</html>


