<?php

use App\Dashboard\Models\DashboardPermission;

return [
    'builder_enabled' => env('DASHBOARD_BUILDER_ENABLED', false),
    'route_prefix' => env('DASHBOARD_ROUTE_PREFIX', 'admin'),
    'route_name_prefix' => 'admin.',
    'middleware' => ['web'],
    'home_enabled' => env('DASHBOARD_HOME_ENABLED', true),
    'home_path' => 'home',
    'home_view' => 'dashboard.home.index',
    'home_middleware' => [],
    'auth' => [
        'enabled' => env('DASHBOARD_AUTH_ENABLED', true),
        'guard' => env('DASHBOARD_AUTH_GUARD', 'web'),
        'login_field' => env('DASHBOARD_AUTH_LOGIN_FIELD', 'email'),
        'login_view' => 'dashboard.auth.login',
    ],
    'builder_middleware' => [],
    'view_namespace' => 'dashboard',
    'asset_path' => 'dashboard',
    'permission_model' => DashboardPermission::class,
    'permission_table' => 'permissions',
    'permission_key_column' => 'key_name',
    'generator' => [
        'controller_namespace' => 'App\\Http\\Controllers\\Admin\\Generated',
        'controller_path' => 'app/Http/Controllers/Admin/Generated',
        'base_controller' => App\Dashboard\Http\Controllers\GeneratedResourceController::class,
        'base_controller_v2' => App\Dashboard\Http\Controllers\GeneratedResourceControllerV2::class,
        'model_namespace' => 'App\\Models',
        'model_path' => 'app/Models',
        'definition_path' => 'dashboard/resources',
        'routes_path' => 'routes/admin-generated',
        'translations_path' => 'resources/lang',
        'stubs_path' => base_path('stubs'),
    ],
];
