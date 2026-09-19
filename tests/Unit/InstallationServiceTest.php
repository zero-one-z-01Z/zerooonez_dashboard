<?php

namespace ZeroOneZ\Dashboard\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ZeroOneZ\Dashboard\Services\Installation\InstallationService;

final class InstallationServiceTest extends TestCase
{
    private string $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workspace = sys_get_temp_dir().'/dashboard-installer-'.bin2hex(random_bytes(6));
        mkdir($this->workspace.'/package/starter/resources/lang/en', 0777, true);
        mkdir($this->workspace.'/host', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->remove($this->workspace);
        parent::tearDown();
    }

    public function test_install_copies_manifest_files_and_records_their_checksums(): void
    {
        $this->payload('resources/lang/en/admin.php', 'first');
        $service = $this->service();

        $result = $service->install();

        $this->assertSame(['resources/lang/en/admin.php'], $result->written);
        $this->assertSame('first', file_get_contents($this->workspace.'/host/resources/lang/en/admin.php'));
        $state = json_decode((string) file_get_contents($this->workspace.'/host/.dashboard/installed.json'), true);
        $this->assertSame(hash('sha256', 'first'), $state['files']['resources/lang/en/admin.php']['sha256']);
    }

    public function test_install_adopts_an_identical_existing_file_without_overwriting_it(): void
    {
        $this->payload('resources/lang/en/admin.php', 'first');
        mkdir($this->workspace.'/host/resources/lang/en', 0777, true);
        file_put_contents($this->workspace.'/host/resources/lang/en/admin.php', 'first');

        $result = $this->service()->install();

        $this->assertSame(['resources/lang/en/admin.php'], $result->adopted);
        $this->assertSame([], $result->conflicts);
    }

    public function test_update_replaces_an_unmodified_previous_install(): void
    {
        $this->payload('resources/lang/en/admin.php', 'first');
        $service = $this->service();
        $service->install();
        $this->payload('resources/lang/en/admin.php', 'second');

        $result = $service->update();

        $this->assertSame(['resources/lang/en/admin.php'], $result->written);
        $this->assertSame('second', file_get_contents($this->workspace.'/host/resources/lang/en/admin.php'));
    }

    public function test_update_preserves_a_host_modified_file_as_a_conflict(): void
    {
        $this->payload('resources/lang/en/admin.php', 'first');
        $service = $this->service();
        $service->install();
        file_put_contents($this->workspace.'/host/resources/lang/en/admin.php', 'custom');
        $this->payload('resources/lang/en/admin.php', 'second');

        $result = $service->update();

        $this->assertSame(['resources/lang/en/admin.php'], $result->conflicts);
        $this->assertSame('custom', file_get_contents($this->workspace.'/host/resources/lang/en/admin.php'));
    }

    public function test_invalid_target_path_is_rejected_before_any_file_is_written(): void
    {
        $this->payload('../outside.php', 'unsafe');

        $this->expectException(\InvalidArgumentException::class);
        $this->service()->install();
    }

    public function test_failed_copy_rolls_back_files_written_in_the_same_operation(): void
    {
        $this->payload('resources/lang/en/admin.php', 'first');
        $this->payload('resources/lang/en/second.php', 'second');
        unlink($this->workspace.'/package/starter/resources/lang/en/second.php');

        try {
            $this->service()->install();
            self::fail('Expected a missing payload source to fail.');
        } catch (\RuntimeException) {
            $this->assertFileDoesNotExist($this->workspace.'/host/resources/lang/en/admin.php');
        }
    }

    public function test_install_safely_merges_the_provider_into_laravel_bootstrap_file(): void
    {
        $this->payload('resources/lang/en/admin.php', 'first');
        mkdir($this->workspace.'/host/bootstrap', 0777, true);
        file_put_contents($this->workspace.'/host/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n");

        $this->service()->install();

        $providers = file_get_contents($this->workspace.'/host/bootstrap/providers.php');
        $this->assertStringContainsString('App\\Providers\\AppServiceProvider::class', $providers);
        $this->assertStringContainsString('App\\Providers\\DashboardServiceProvider::class', $providers);
    }

    public function test_provider_merge_keeps_a_standard_laravel_trailing_comma_valid(): void
    {
        $this->payload('resources/lang/en/admin.php', 'first');
        mkdir($this->workspace.'/host/bootstrap', 0777, true);
        file_put_contents($this->workspace.'/host/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n");

        $this->service()->install();

        $providers = file_get_contents($this->workspace.'/host/bootstrap/providers.php');
        $this->assertStringNotContainsString(',,', $providers);
        $this->assertSame(0, $this->lint($this->workspace.'/host/bootstrap/providers.php'));
    }

    public function test_provider_merge_adds_a_comma_when_the_existing_entry_has_none(): void
    {
        $this->payload('resources/lang/en/admin.php', 'first');
        mkdir($this->workspace.'/host/bootstrap', 0777, true);
        file_put_contents($this->workspace.'/host/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class\n];\n");

        $this->service()->install();

        $providers = file_get_contents($this->workspace.'/host/bootstrap/providers.php');
        $this->assertStringContainsString('AppServiceProvider::class,', $providers);
        $this->assertSame(0, $this->lint($this->workspace.'/host/bootstrap/providers.php'));
    }

    private function service(): InstallationService
    {
        return new InstallationService($this->workspace.'/package', $this->workspace.'/host');
    }

    private function payload(string $target, string $contents): void
    {
        $source = 'starter/'.ltrim($target, '/');
        $path = $this->workspace.'/package/'.$source;
        if (! is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
        file_put_contents($path, $contents);
        $manifestPath = $this->workspace.'/package/starter-manifest.json';
        $manifest = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : ['version' => 1, 'files' => []];
        $manifest['files'] = array_values(array_filter($manifest['files'], fn (array $file) => $file['target'] !== $target));
        $manifest['files'][] = ['source' => $source, 'target' => $target, 'sha256' => hash('sha256', $contents)];
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function remove(string $path): void
    {
        if (is_link($path) || is_file($path)) { @unlink($path); return; }
        if (! is_dir($path)) return;
        foreach (scandir($path) ?: [] as $item) if ($item !== '.' && $item !== '..') $this->remove($path.'/'.$item);
        @rmdir($path);
    }

    private function lint(string $path): int
    {
        exec(PHP_BINARY.' -l '.escapeshellarg($path), $output, $exit);
        return $exit;
    }
}
