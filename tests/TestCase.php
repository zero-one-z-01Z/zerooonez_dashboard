<?php

namespace ZeroOneZ\Dashboard\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use ZeroOneZ\Dashboard\DashboardServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('z', 32)));
        $app['config']->set('dashboard', require dirname(__DIR__).'/config/dashboard.php');
    }

    protected function getPackageProviders($app): array
    {
        return [DashboardServiceProvider::class];
    }
}
