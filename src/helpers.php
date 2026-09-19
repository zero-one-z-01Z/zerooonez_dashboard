<?php

use Illuminate\Support\Facades\Route;
use ZeroOneZ\Dashboard\Http\Controllers\GeneratedResourceControllerV2;
use ZeroOneZ\Dashboard\Http\Controllers\GeneratedResourceController;

if (! function_exists('deleted_routes')) {
    function deleted_routes(?string $permission = null): void
    {
        $delete = Route::post('/delete/{id}', 'delete')->whereNumber('id')->name('delete');
        $deleteAll = Route::post('/delete_all', 'delete_all')->name('delete_all');
        if ($permission) {
            $delete->middleware('can:delete_'.$permission);
            $deleteAll->middleware('can:delete_'.$permission);
        }
    }
}

if (! function_exists('all_routes')) {
    function all_routes(?string $permission = null): void
    {
        $groups = Route::getGroupStack();
        $group = $groups === [] ? [] : $groups[array_key_last($groups)];
        $controller = $group['controller'] ?? null;
        if (is_string($controller) && (is_subclass_of($controller, GeneratedResourceControllerV2::class)
            || is_subclass_of($controller, GeneratedResourceController::class))) {
            $capabilities = $controller::routeCapabilities();
            Route::get('/', 'index')->name('index');
            Route::post('/get_datatable', 'get_datatable')->name('datatable');
            Route::get('/list', 'get_list')->name('list');
            if ($capabilities['update'] ?? false) {
                Route::get('/get_single_item/{id}', 'get_single_item')->whereNumber('id')->name('get_single_item');
                Route::post('/update/{id}', 'update')->whereNumber('id')->middleware('can:update_'.$permission)->name('update');
            }
            if ($capabilities['create'] ?? false) Route::post('/store', 'store')->middleware('can:create_'.$permission)->name('store');
            if ($capabilities['delete'] ?? false) Route::post('/delete/{id}', 'delete')->whereNumber('id')->middleware('can:delete_'.$permission)->name('delete');
            if ($capabilities['delete_all'] ?? false) Route::post('/delete_all', 'delete_all')->middleware('can:delete_'.$permission)->name('delete_all');
            return;
        }
        Route::get('/', 'index')->name('index');
        Route::get('/get_data', 'get_data')->name('get_data');
        Route::post('/get_datatable', 'get_datatable')->name('datatable');
        $list = Route::get('/list', 'get_list')->name('list');
        Route::get('/get_single_item/{id}', 'get_single_item')->name('get_single_item');
        Route::get('/show/{id}', 'show')->name('show');
        $store = Route::post('/store', 'store')->name('store');
        $update = Route::post('/update/{id}', 'update')->name('update');
        if ($permission) {
            // Legacy relation pickers are shared by pages that may not have the
            // viewed resource permission. Preserve the host dashboard contract.
            $list->withoutMiddleware('can:view_'.$permission);
            $store->middleware('can:create_'.$permission);
            $update->middleware('can:update_'.$permission);
        }
        deleted_routes($permission);
    }
}

if (! function_exists('dashboard_asset')) {
    function dashboard_asset(string $path = ''): string
    {
        return asset(trim((string) config('dashboard.asset_path', 'dashboard'), '/').'/'.ltrim($path, '/'));
    }
}

if (! function_exists('dashboard_vite')) {
    function dashboard_vite(string|array $entries): string
    {
        $entries = (array) $entries;
        $manifestPath = public_path(trim((string) config('dashboard.asset_path', 'dashboard'), '/').'/build/.vite/manifest.json');
        if (! is_file($manifestPath)) return '';
        $manifest = json_decode((string) file_get_contents($manifestPath), true) ?: [];
        $html = [];
        foreach ($entries as $entry) {
            $item = $manifest[$entry] ?? null;
            if (! is_array($item) || empty($item['file'])) continue;
            $url = dashboard_asset('build/'.$item['file']);
            $html[] = str_ends_with($item['file'], '.css')
                ? '<link rel="stylesheet" href="'.e($url).'">'
                : '<script type="module" src="'.e($url).'"></script>';
            foreach ($item['css'] ?? [] as $css) $html[] = '<link rel="stylesheet" href="'.e(dashboard_asset('build/'.$css)).'">';
        }
        return implode("\n", array_unique($html));
    }
}
