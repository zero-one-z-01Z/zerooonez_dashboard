<?php

namespace ZeroOneZ\Dashboard\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ZeroOneZ\Dashboard\Console\Commands\UpdateDashboardAssets;

final class UpdateDashboardAssetsTest extends TestCase
{
    public function test_theme_assets_do_not_gain_a_second_dashboard_segment(): void
    {
        $this->assertSame(
            'dashboard/vendor/css/core.css',
            UpdateDashboardAssets::publishedRelativePath('dashboard/vendor/css/core.css', 'dashboard')
        );
    }

    public function test_build_assets_follow_the_configured_asset_path(): void
    {
        $this->assertSame(
            'custom-dashboard/build/assets/app.js',
            UpdateDashboardAssets::publishedRelativePath('build/assets/app.js', '/custom-dashboard/')
        );
    }

    public function test_unknown_or_traversal_manifest_paths_are_rejected(): void
    {
        $this->assertNull(UpdateDashboardAssets::publishedRelativePath('../secret', 'dashboard'));
        $this->assertNull(UpdateDashboardAssets::publishedRelativePath('misc/file.js', 'dashboard'));
    }
}
