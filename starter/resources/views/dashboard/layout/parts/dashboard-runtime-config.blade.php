@php
    $dashboardAssetPath = rtrim(asset('dashboard'), '/');
    $googleMapsKey = config('services.google_maps.browser_key');
    $dashboardAssets = [
        'select2' => ['styles' => ["{$dashboardAssetPath}/vendor/libs/select2/select2.css"], 'scripts' => ["{$dashboardAssetPath}/vendor/libs/select2/select2.js"]],
        'datatable' => [
            'styles' => [
                "{$dashboardAssetPath}/vendor/libs/datatables-bs5/datatables.bootstrap5.css",
                "{$dashboardAssetPath}/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css",
                "{$dashboardAssetPath}/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.css",
            ],
            'scripts' => ["{$dashboardAssetPath}/vendor/libs/datatables-bs5/datatables-bootstrap5.js"],
        ],
        'dropzone' => [
            'styles' => ["{$dashboardAssetPath}/vendor/libs/dropzone/dropzone.css"],
            'scripts' => ["{$dashboardAssetPath}/vendor/libs/dropzone/dropzone.js", "{$dashboardAssetPath}/js/forms-file-upload.js"],
        ],
        'quill' => [
            'styles' => [
                "{$dashboardAssetPath}/vendor/libs/quill/typography.css",
                "{$dashboardAssetPath}/vendor/libs/highlight/highlight.css",
                "{$dashboardAssetPath}/vendor/libs/quill/katex.css",
                "{$dashboardAssetPath}/vendor/libs/quill/editor.css",
            ],
            'scripts' => [
                "{$dashboardAssetPath}/vendor/libs/quill/katex.js",
                "{$dashboardAssetPath}/vendor/libs/highlight/highlight.js",
                "{$dashboardAssetPath}/vendor/libs/quill/quill.js",
            ],
        ],
        'leaflet' => [
            'styles' => ['https://unpkg.com/leaflet/dist/leaflet.css', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css'],
            'scripts' => ['https://unpkg.com/leaflet/dist/leaflet.js', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js'],
        ],
        'swiper' => [
            'styles' => ["{$dashboardAssetPath}/vendor/libs/swiper/swiper.css"],
            'scripts' => ["{$dashboardAssetPath}/vendor/libs/swiper/swiper.js"],
        ],
        'daterangepicker' => [
            'styles' => ["{$dashboardAssetPath}/js/daterangepicker/daterangepicker.css"],
            'scripts' => ["{$dashboardAssetPath}/vendor/libs/moment/moment.js", "{$dashboardAssetPath}/js/daterangepicker/daterangepicker.js"],
        ],
    ];
    if ($googleMapsKey) {
        $dashboardAssets['googleMaps'] = ['styles' => [], 'scripts' => ['https://maps.googleapis.com/maps/api/js?key='.rawurlencode($googleMapsKey)]];
    }
    $dashboardRuntimeConfig = [
        'assets' => $dashboardAssets,
        'locale' => app()->getLocale(),
        'direction' => app()->getLocale() === 'ar' ? 'rtl' : 'ltr',
    ];
@endphp
<script type="application/json" id="dashboard-runtime-config">@json($dashboardRuntimeConfig)</script>
