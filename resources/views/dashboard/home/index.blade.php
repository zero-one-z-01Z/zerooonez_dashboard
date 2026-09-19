<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ app()->isLocale('ar') ? 'الرئيسية' : 'Dashboard home' }}</title>
    @dashboardVite('resources/css/dashboard-builder.css')
</head>
<body>
<header class="builder-header">
    <a class="brand" href="{{ request()->url() }}">
        ZeroOneZ Dashboard
    </a>
    @if ($logoutUrl)
        <form action="{{ $logoutUrl }}" method="POST">
            @csrf
            <button class="button secondary" type="submit">
                {{ app()->isLocale('ar') ? 'تسجيل الخروج' : 'Sign out' }}
            </button>
        </form>
    @endif
</header>

<main class="builder-shell">
    <section class="builder-hero">
        <p class="eyebrow">ZeroOneZ Dashboard</p>
        <h1>{{ app()->isLocale('ar') ? 'الرئيسية' : 'Dashboard home' }}</h1>
        <p>
            {{ app()->isLocale('ar')
                ? 'نقطة البداية العامة للوحة التحكم. يمكن للمشروع استبدال هذا القالب أو تعطيله من الإعدادات.'
                : 'The reusable dashboard starting point. Projects may override this view or disable it in configuration.' }}
        </p>

        @if ($builderEnabled && $builderUrl)
            <a class="primary-button" href="{{ $builderUrl }}">
                {{ app()->isLocale('ar') ? 'فتح مصنع الصفحات' : 'Open dashboard builder' }}
            </a>
        @endif
    </section>
</main>
</body>
</html>
