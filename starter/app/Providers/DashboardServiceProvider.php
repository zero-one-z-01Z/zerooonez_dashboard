<?php

namespace App\Providers;

use App\Dashboard\Contracts\PermissionStore;
use App\Dashboard\Services\EloquentPermissionStore;
use App\Console\Commands\CreateDashboardAdmin;
use App\Dashboard\Console\Commands\MakeDashboard;
use App\Dashboard\Console\Commands\SyncDashboardPermissions;
use App\Http\Middleware\DashboardAdmin;
use App\Http\Middleware\DashboardLocale;
use App\Models\Admin;
use App\Models\Permission;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('dashboard.php'), 'dashboard');
        $this->app->singleton(PermissionStore::class, EloquentPermissionStore::class);
        config()->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'dashboard-admins']);
        config()->set('auth.providers.dashboard-admins', ['driver' => 'eloquent', 'model' => Admin::class]);
    }

    public function boot(): void
    {
        require_once app_path('Dashboard/helpers.php');
        Blade::directive('dashboardVite', static fn (string $expression): string => "<?php echo dashboard_vite({$expression}); ?>");
        $router = $this->app['router'];
        $router->aliasMiddleware('admin', DashboardAdmin::class);
        $router->aliasMiddleware('lang', DashboardLocale::class);
        $this->loadViewsFrom(resource_path('views/dashboard'), 'dashboard');
        $this->loadTranslationsFrom(resource_path('lang'), 'dashboard');
        Route::middleware('web')->group(base_path('routes/admin.php'));
        if ($this->app->runningInConsole()) $this->commands([CreateDashboardAdmin::class, MakeDashboard::class, SyncDashboardPermissions::class]);
        try {
            Gate::before(function ($user, string $ability): ?bool {
                if (! $user instanceof Admin) return null;
                $core = ['view_dashboard_home', 'view_admin', 'create_admin', 'update_admin', 'delete_admin', 'view_permission', 'create_permission', 'update_permission', 'delete_permission', 'view_setting', 'update_setting'];
                return $user->super && in_array($ability, $core, true) ? true : null;
            });
            Permission::query()->each(fn (Permission $permission) => Gate::define($permission->key_name, fn ($user) => $user instanceof Admin && $user->havePermission($permission->key_name)));
        } catch (\Throwable) {
            // Migrations may not have run yet.
        }
    }
}
