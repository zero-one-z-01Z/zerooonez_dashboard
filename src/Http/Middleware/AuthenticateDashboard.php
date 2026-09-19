<?php

namespace ZeroOneZ\Dashboard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard((string) config('dashboard.auth.guard', 'web'))->check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $prefix = trim((string) config('dashboard.route_name_prefix', 'admin.'), '.');
        $loginRoute = ($prefix === '' ? '' : $prefix.'.').'login';

        return redirect()->guest(route($loginRoute));
    }
}
