<?php

namespace ZeroOneZ\Dashboard;

use Illuminate\Support\ServiceProvider;
use ZeroOneZ\Dashboard\Console\Commands\InstallDashboard;
use ZeroOneZ\Dashboard\Console\Commands\UpdateDashboard;
use ZeroOneZ\Dashboard\Console\Commands\UpdateDashboardAssets;

/**
 * Installer-only provider. The installed application registers the local
 * App\Providers\DashboardServiceProvider for every runtime concern.
 */
final class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([InstallDashboard::class, UpdateDashboard::class, UpdateDashboardAssets::class]);
        }
    }
}
