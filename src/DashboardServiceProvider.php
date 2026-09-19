<?php

namespace ZeroOneZ\Dashboard;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use ZeroOneZ\Dashboard\Console\Commands\InstallDashboard;
use ZeroOneZ\Dashboard\Console\Commands\MakeDashboard;
use ZeroOneZ\Dashboard\Console\Commands\SyncDashboardPermissions;
use ZeroOneZ\Dashboard\Console\Commands\UpdateDashboardAssets;
use ZeroOneZ\Dashboard\Contracts\PermissionStore;
use ZeroOneZ\Dashboard\Services\EloquentPermissionStore;

final class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/dashboard.php', 'dashboard');
        $this->app->singleton(PermissionStore::class, EloquentPermissionStore::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views/dashboard', 'zerooonez-dashboard');
        // Transitional alias for existing applications. New code should use
        // zerooonez-dashboard:: so project overrides remain explicit.
        $this->loadViewsFrom(__DIR__.'/../resources/views/dashboard', 'dashboard');
        $this->app['view']->addLocation(__DIR__.'/../resources/views');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'zerooonez-dashboard');
        $this->loadRoutesFrom(__DIR__.'/../routes/dashboard.php');
        Blade::directive('dashboardVite', static fn (string $expression): string => "<?php echo dashboard_vite({$expression}); ?>");

        $this->publishes([__DIR__.'/../config/dashboard.php' => config_path('dashboard.php')], 'zerooonez-dashboard-config');
        $this->publishes([__DIR__.'/../resources/views/dashboard' => resource_path('views/vendor/zerooonez-dashboard')], 'zerooonez-dashboard-views');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallDashboard::class, UpdateDashboardAssets::class, MakeDashboard::class, SyncDashboardPermissions::class]);
        }
    }
}
