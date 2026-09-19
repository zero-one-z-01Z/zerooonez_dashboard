<?php

use Illuminate\Support\Facades\Route;
use ZeroOneZ\Dashboard\Http\Controllers\DashboardBuilderController;

Route::middleware(config('dashboard.middleware', ['web']))
    ->prefix(config('dashboard.route_prefix', 'admin'))
    ->name(config('dashboard.route_name_prefix', 'admin.'))
    ->group(function (): void {
        Route::middleware(config('dashboard.builder_middleware', []))
            ->prefix('dashboard-builder')->name('dashboard-builder.')
            ->controller(DashboardBuilderController::class)->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('/catalog', 'catalog')->name('catalog');
                Route::post('/preview', 'preview')->name('preview');
                Route::post('/generate', 'generate')->name('generate');
            });
        foreach (glob(base_path(trim((string) config('dashboard.generator.routes_path'), '/').'/*.php')) ?: [] as $file) require $file;
    });
