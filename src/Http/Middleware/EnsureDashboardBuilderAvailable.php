<?php

namespace ZeroOneZ\Dashboard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureDashboardBuilderAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            app()->environment('local')
            && (bool) config('dashboard.builder_enabled', false)
            && in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true),
            Response::HTTP_NOT_FOUND
        );

        return $next($request);
    }
}
