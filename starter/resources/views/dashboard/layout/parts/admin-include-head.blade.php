






<link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/pickr/pickr-themes.css" />


@isset($swipper)
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/swiper/swiper.css" />
@endisset

@isset($waves)
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/node-waves/node-waves.css" />
@endisset


@isset($charts)
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/apex-charts/apex-charts.css" />
@endisset
@isset($boundary)
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
@endisset


<link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
<link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/notyf/notyf.css">



{{--<link rel="stylesheet" href="{{asset('dashboard/')}}/css/demo.css" />--}}
{{--<link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/css/pages/cards-advance.css" />--}}




@isset($datatable)

    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/datatables-bs5/datatables.bootstrap5.css" />
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css" />
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css">
@endisset


<link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/sweetalert2/sweetalert2.css">

@isset($select2)
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/select2/select2.css">
@endisset
@isset($html_input)
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/quill/typography.css">
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/highlight/highlight.css">
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/quill/katex.css">
    <link rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/quill/editor.css">
@endisset
@isset($daterangepicker)
    <!-- Daterangepikcer CSS -->
    <link rel="stylesheet" href="{{asset('dashboard/js')}}/daterangepicker/daterangepicker.css">
@endisset



@isset($support_chat)
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @dashboardVite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
@endisset

@isset($chat)
    <link  rel="stylesheet" href="{{asset('dashboard/')}}/vendor/libs/chat/chat.css"></link>
    <style>
        #userListChat{
            height: 150px;
            overflow-y: scroll;
        }
        #userListChat::-webkit-scrollbar {
            display: none;
        }
        #chat-container{
            height: 100%;
            overflow-y: scroll;
        }
        #chat-container::-webkit-scrollbar {
            display: none;
        }
        /*body.no-scroll {*/
        /*    overflow: hidden;*/
        /*}*/
    </style>
@endisset

@isset($dropzone)
    <link rel="stylesheet" href="{{asset('dashboard')}}/vendor/libs/dropzone/dropzone.css">
@endisset




