<Script>
    var currentLocale = "{{ App::currentLocale() }}"

</Script>



<script src="{{asset('dashboard/')}}/vendor/libs/jquery/jquery.js"></script>
@isset($popper)
    <script src="{{asset('dashboard/')}}/vendor/libs/popper/popper.js"></script>
@endisset
<script src="{{asset('dashboard/')}}/vendor/js/bootstrap.js"></script>
<script src="{{asset('dashboard/')}}/js/ui-toasts.js"></script>
<script src="{{asset('dashboard/')}}/vendor/libs/notyf/notyf.js"></script>
@isset($waves)
    <script src="{{asset('dashboard/')}}/vendor/libs/node-waves/node-waves.js"></script>
@endisset
@isset($boundary)
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <style>
        #map { height: 500px; }
        #edit_map { height: 500px; }
        #create_map { height: 500px; }
        /*button { margin-top: 10px; padding: 10px; background: #28a745; color: white; border: none; cursor: pointer; }*/
    </style>
@endisset

{{--@isset($picker)--}}
<script src="{{asset('dashboard/')}}/vendor/libs/pickr/pickr.js"></script>
{{--@endisset--}}


<script src="{{asset('dashboard/')}}/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>


<script src="{{asset('dashboard/')}}/vendor/libs/hammer/hammer.js"></script>
<script src="{{asset('dashboard/')}}/vendor/libs/i18n/i18n.js"></script>
<script src="{{asset('dashboard/')}}/vendor/js/menu.js"></script>
@isset($charts)
    <script src="{{asset('dashboard/')}}/vendor/libs/apex-charts/apexcharts.js"></script>
@endisset

@isset($swiper)
    <script src="{{asset('dashboard/')}}/vendor/libs/swiper/swiper.js"></script>
@endisset
@isset($datatable)
    @dashboardVite('resources/js/back/datatable-init.js')
    <script src="{{asset('dashboard/')}}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"></script>
@endisset
@isset($select2)
    <script src="{{asset('dashboard/')}}/vendor/libs/select2/select2.js"></script>
    <script>
        if ($.fn.select2) {
            $.fn.select2.defaults.set("dir", window.current_direction);
        }
    </script>
@endisset
<script src="{{asset('dashboard/')}}/js/main.js"></script>
@isset($analytics)
    <script src="{{asset('dashboard/')}}/js/dashboards-analytics.js"></script>
    <script src="{{asset('dashboard/')}}/vendor/libs/apex-charts/apexcharts.js"></script>
@endisset
@isset($daterangepicker)

    <script src="{{asset('dashboard/')}}/vendor/libs/moment/moment.js"></script>
    <script src="{{asset('dashboard/')}}/js/daterangepicker/daterangepicker.js"></script>
@endisset
@isset($html_input)
    <script src="{{asset('dashboard/')}}/vendor/libs/quill/katex.js"></script>
    <script src="{{asset('dashboard/')}}/vendor/libs/highlight/highlight.js"></script>
    <script src="{{asset('dashboard/')}}/vendor/libs/quill/quill.js"></script>
@endisset

@isset($dropzone)
    <script src="{{asset('dashboard/')}}/vendor/libs/dropzone/dropzone.js"></script>
    <script src="{{asset('dashboard/')}}/js/forms-file-upload.js"></script>
@endisset

@isset($have_validation)
    <script src="{{asset('dashboard/')}}/js/form-validation.js"></script>
    <!-- jQuery Validation Plugin (Minified Version) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>

    <!-- Additional Methods (Minified Version) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/additional-methods.min.js"></script>
@endisset
@dashboardVite('resources/js/back/crud.js')
@isset($chat)
    <script src="{{asset("dashboard/vendor/libs/glightbox/js/glightbox.min.js")}}"></script>
    <script src="{{asset("dashboard/vendor/libs/fg-emoji-picker/fgEmojiPicker.js")}}"></script>
    <script src="{{asset('dashboard/')}}/vendor/libs/chat//chat.init.js"></script>
@endisset
<script src="{{asset('dashboard/')}}/vendor/libs/sweetalert2/sweetalert2.js"></script>






<script>

    @if(session()->has('message'))
    window.addEventListener('DOMContentLoaded', function () {
        var type = "{{ session()->get('type') }}";
        var message = "{{ session()->get('message') }}";
        showNotification(type,message);
    });

    @endif
</script>
