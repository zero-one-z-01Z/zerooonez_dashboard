<?php

declare(strict_types=1);

namespace ZeroOneZ\Dashboard\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class StarterPayloadTest extends TestCase
{
    public function test_starter_payload_has_a_manifest_and_local_runtime_entrypoint(): void
    {
        $root = dirname(__DIR__, 2);

        self::assertFileExists($root.'/starter-manifest.json');
        self::assertFileExists($root.'/starter/app/Providers/DashboardServiceProvider.php');
        self::assertFileExists($root.'/starter/routes/admin.php');
    }

    public function test_language_switch_runtime_values_are_valid_json_backed_globals(): void
    {
        $root = dirname(__DIR__, 2);
        $layout = (string) file_get_contents($root.'/starter/resources/views/dashboard/layout/admin-main-layout.blade.php');
        $script = (string) file_get_contents($root.'/starter/public/dashboard/js/main.js');

        self::assertStringContainsString('window.langRouteBase = @json($change_lang_url);', $layout);
        self::assertStringContainsString('window.show_password_modal = @json((bool) $show_password);', $layout);
        self::assertStringNotContainsString('const langRouteBase =', $layout);
        self::assertStringContainsString('window.langRouteBase.replace', $script);
    }
}
