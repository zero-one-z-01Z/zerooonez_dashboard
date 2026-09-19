<?php

use Illuminate\Support\Facades\Route;
use ZeroOneZ\Dashboard\Http\Controllers\AuthController;
use ZeroOneZ\Dashboard\Http\Controllers\DashboardBuilderController;
use ZeroOneZ\Dashboard\Http\Controllers\HomeController;
use ZeroOneZ\Dashboard\Http\Middleware\AuthenticateDashboard;
use ZeroOneZ\Dashboard\Http\Middleware\EnsureDashboardBuilderAvailable;

Route::middleware(config('dashboard.middleware', ['web']))
    ->prefix(config('dashboard.route_prefix', 'admin'))
    ->name(config('dashboard.route_name_prefix', 'admin.'))
    ->group(function (): void {
        $authEnabled = (bool) config('dashboard.auth.enabled', true);

        if ($authEnabled) {
            Route::get('login', [AuthController::class, 'showLogin'])->name('login');
            Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
        }

        Route::middleware($authEnabled ? [AuthenticateDashboard::class] : [])->group(function () use ($authEnabled): void {
            if ($authEnabled) {
                Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            }

            if (config('dashboard.home_enabled', true)) {
                Route::middleware(config('dashboard.home_middleware', []))
                    ->get(config('dashboard.home_path', 'home'), [HomeController::class, 'index'])
                    ->name('home');
            }

            foreach (glob(base_path(trim((string) config('dashboard.generator.routes_path'), '/').'/*.php')) ?: [] as $file) require $file;
        });

        $builderMiddleware = [EnsureDashboardBuilderAvailable::class];
        if ($authEnabled) {
            $builderMiddleware[] = AuthenticateDashboard::class;
        }
        $builderMiddleware = array_merge($builderMiddleware, config('dashboard.builder_middleware', []));

        Route::middleware($builderMiddleware)
            ->prefix('dashboard-builder')->name('dashboard-builder.')
            ->controller(DashboardBuilderController::class)->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('/catalog', 'catalog')->name('catalog');
                Route::post('/preview', 'preview')->name('preview');
                Route::post('/generate', 'generate')->name('generate');
            });
    });
