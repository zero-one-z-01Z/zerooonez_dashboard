<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class DashboardLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale', session('lang', config('app.locale')));
        app()->setLocale(in_array($locale, ['ar', 'en'], true) ? $locale : config('app.locale'));
        return $next($request);
    }
}
