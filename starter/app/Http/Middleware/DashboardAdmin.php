<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class DashboardAdmin
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse('admin');
        if (! admin()->check() || ! admin_user()?->active) return redirect()->route('admin.login');
        admin_user()->forceFill(['last_login' => now()])->save();
        return $next($request);
    }
}
