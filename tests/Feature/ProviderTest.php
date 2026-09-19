<?php

namespace ZeroOneZ\Dashboard\Tests\Feature;

use ZeroOneZ\Dashboard\Contracts\PermissionStore;
use ZeroOneZ\Dashboard\DashboardServiceProvider;
use ZeroOneZ\Dashboard\Http\Controllers\HomeController;
use ZeroOneZ\Dashboard\Services\EloquentPermissionStore;
use ZeroOneZ\Dashboard\Tests\TestCase;
use Illuminate\Support\ServiceProvider;

final class ProviderTest extends TestCase
{
    public function test_provider_registers_defaults_routes_views_and_store(): void
    {
        $this->assertSame('admin', config('dashboard.route_prefix'));
        $this->assertInstanceOf(EloquentPermissionStore::class, app(PermissionStore::class));
        $this->assertTrue(app('router')->has('admin.dashboard-builder.index'));
        $this->assertTrue(view()->exists('zerooonez-dashboard::builder.index'));
    }

    public function test_builder_is_hidden_by_default(): void
    {
        $this->get('/admin/dashboard-builder')->assertNotFound();
    }

    public function test_package_registers_a_standalone_dashboard_home_controller(): void
    {
        $route = app('router')->getRoutes()->getByName('admin.home');

        $this->assertNotNull($route);
        $this->assertSame(HomeController::class.'@index', ltrim($route->getActionName(), '\\'));
        $this->get('/admin/home')->assertRedirect('/admin/login');
    }

    public function test_enabled_builder_requires_dashboard_authentication(): void
    {
        app()->detectEnvironment(static fn (): string => 'local');
        config()->set('dashboard.builder_enabled', true);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/admin/dashboard-builder')
            ->assertRedirect('/admin/login');
    }

    public function test_package_translations_can_be_published_for_project_overrides(): void
    {
        $paths = ServiceProvider::pathsToPublish(DashboardServiceProvider::class, 'zerooonez-dashboard-translations');

        $this->assertContains(resource_path('lang/vendor/zerooonez-dashboard'), array_values($paths));
    }
}
