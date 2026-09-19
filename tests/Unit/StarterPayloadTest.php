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
}
