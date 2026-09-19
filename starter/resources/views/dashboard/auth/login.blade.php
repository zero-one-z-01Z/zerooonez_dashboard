<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->isLocale('ar') ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('dashboard.auth.title') }}</title>
    @dashboardVite('resources/css/dashboard-builder.css')
</head>
<body>
<main class="builder-shell">
    <section class="builder-hero" style="max-width: 34rem; margin: 8vh auto;">
        <p class="eyebrow">ZeroOneZ Dashboard</p>
        <h1>{{ __('dashboard.auth.title') }}</h1>

        <form action="{{ $loginUrl }}" method="POST">
            @csrf

            <label>
                {{ __('dashboard.auth.'.$field) }}
                <input
                    type="{{ $field === 'email' ? 'email' : 'text' }}"
                    name="{{ $field }}"
                    value="{{ old($field) }}"
                    autocomplete="username"
                    required
                    autofocus
                >
            </label>
            @error($field)<p role="alert">{{ $message }}</p>@enderror

            <label>
                {{ __('dashboard.auth.password') }}
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            @error('password')<p role="alert">{{ $message }}</p>@enderror

            <label class="check">
                <input type="checkbox" name="remember" value="1">
                {{ __('dashboard.auth.remember') }}
            </label>

            <button class="button primary" type="submit">{{ __('dashboard.auth.submit') }}</button>
        </form>
    </section>
</main>
</body>
</html>
