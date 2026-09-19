<?php

namespace ZeroOneZ\Dashboard\Tests\Feature;

use ZeroOneZ\Dashboard\Contracts\PermissionStore;
use ZeroOneZ\Dashboard\Services\EloquentPermissionStore;
use ZeroOneZ\Dashboard\Tests\TestCase;

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
}
