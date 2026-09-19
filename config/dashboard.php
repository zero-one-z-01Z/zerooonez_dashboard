<?php

use ZeroOneZ\Dashboard\Models\DashboardPermission;

return [
    'builder_enabled' => env('DASHBOARD_BUILDER_ENABLED', false),
    'route_prefix' => env('DASHBOARD_ROUTE_PREFIX', 'admin'),
    'route_name_prefix' => 'admin.',
    'middleware' => ['web'],
    'builder_middleware' => [],
    'view_namespace' => 'zerooonez-dashboard',
    'asset_path' => 'dashboard',
    'permission_model' => DashboardPermission::class,
    'permission_table' => 'permissions',
    'permission_key_column' => 'key_name',
    'generator' => [
        'controller_namespace' => 'App\\Http\\Controllers\\Admin\\Generated',
        'controller_path' => 'app/Http/Controllers/Admin/Generated',
        'base_controller' => ZeroOneZ\Dashboard\Http\Controllers\GeneratedResourceController::class,
        'base_controller_v2' => ZeroOneZ\Dashboard\Http\Controllers\GeneratedResourceControllerV2::class,
        'model_namespace' => 'App\\Models',
        'model_path' => 'app/Models',
        'definition_path' => 'dashboard/resources',
        'routes_path' => 'routes/admin-generated',
        'translations_path' => 'resources/lang',
    ],
];
