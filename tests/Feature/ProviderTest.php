<?php

namespace ZeroOneZ\Dashboard\Tests\Feature;

use ZeroOneZ\Dashboard\DashboardServiceProvider;
use ZeroOneZ\Dashboard\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

final class ProviderTest extends TestCase
{
    public function test_package_provider_exposes_only_install_and_update_commands(): void
    {
        $commands = Artisan::all();
        $this->assertArrayHasKey('dashboard:install', $commands);
        $this->assertArrayHasKey('dashboard:update', $commands);
        $this->assertArrayHasKey('dashboard:update-assets', $commands);
        $this->assertArrayNotHasKey('dashboard:make', $commands);
        $this->assertArrayNotHasKey('dashboard:sync-permissions', $commands);
        $this->assertArrayNotHasKey('admin.home', $commands);
    }

    public function test_package_provider_does_not_register_dashboard_runtime_routes_or_views(): void
    {
        $this->assertNull(app('router')->getRoutes()->getByName('admin.home'));
        $this->assertFalse(view()->exists('zerooonez-dashboard::builder.index'));
    }
}
