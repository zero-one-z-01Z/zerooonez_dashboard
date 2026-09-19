<?php

namespace ZeroOneZ\Dashboard\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

class HomeController extends Controller
{
    public function index(): View
    {
        $routeNamePrefix = trim((string) config('dashboard.route_name_prefix', 'admin.'), '.');
        $builderRouteName = ($routeNamePrefix === '' ? '' : $routeNamePrefix.'.').'dashboard-builder.index';
        $logoutRouteName = ($routeNamePrefix === '' ? '' : $routeNamePrefix.'.').'logout';

        return view((string) config('dashboard.home_view', 'zerooonez-dashboard::home.index'), [
            'builderEnabled' => (bool) config('dashboard.builder_enabled', false),
            'builderUrl' => Route::has($builderRouteName) ? route($builderRouteName) : null,
            'logoutUrl' => Route::has($logoutRouteName) ? route($logoutRouteName) : null,
        ]);
    }
}
