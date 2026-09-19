<?php

namespace ZeroOneZ\Dashboard\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use ZeroOneZ\Dashboard\DashboardServiceProvider;
use ZeroOneZ\Dashboard\Tests\Fixtures\DashboardUser;

abstract class TestCase extends Orchestra
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('z', 32)));
        $app['config']->set('auth.guards.dashboard', ['driver' => 'session', 'provider' => 'dashboard_users']);
        $app['config']->set('auth.providers.dashboard_users', ['driver' => 'eloquent', 'model' => DashboardUser::class]);
        $app['config']->set('dashboard.auth.enabled', true);
        $app['config']->set('dashboard.auth.guard', 'dashboard');
    }

    protected function getPackageProviders($app): array
    {
        return [DashboardServiceProvider::class];
    }
}
