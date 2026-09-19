<?php

namespace App\Dashboard\Services\Installation;

use InvalidArgumentException;
use RuntimeException;

/** Installs only the signed source inventory; it never publishes package runtime code. */
final class InstallationService
{
    private const STATE_FILE = '.dashboard/installed.json';
    private const PROVIDER = 'App\\Providers\\DashboardServiceProvider::class';

    public function __construct(private readonly string $packageRoot, private readonly string $hostRoot) {}

    public function preview(bool $update = false, bool $force = false): InstallationResult
    {
        return $this->run($update, $force, true);
    }

    public function install(bool $force = false, bool $dryRun = false): InstallationResult
    {
        return $this->locked(fn () => $this->run(false, $force, $dryRun), $dryRun);
    }

    public function update(bool $force = false, bool $dryRun = false): InstallationResult
    {
        return $this->locked(fn () => $this->run(true, $force, $dryRun), $dryRun);
    }

    private function run(bool $update, bool $force, bool $dryRun): InstallationResult
    {
        $entries = $this->entries(); // Validate all inputs before the first host write.
        $state = $this->state();
        $result = new InstallationResult();
        $next = $state['files'] ?? [];
        $changes = [];

        foreach ($entries as $entry) {
            $target = $this->targetPath($entry['target']);
            $incoming = $entry['sha256'];
            $current = is_file($target) ? hash_file('sha256', $target) : null;
            $known = $state['files'][$entry['target']]['sha256'] ?? null;
            if ($current === $incoming) {
                $next[$entry['target']] = ['sha256' => $incoming];
                $known === $incoming ? $result->unchanged[] = $entry['target'] : $result->adopted[] = $entry['target'];
                continue;
            }
            if ($current === null && $update && $known !== null && ! $force) {
                $result->conflicts[] = $entry['target'];
                continue;
            }
            if ($current !== null && ! $force && (! $update || $known === null || ($known !== $incoming && $current !== $known))) {
                $result->conflicts[] = $entry['target'];
                continue;
            }
            if ($current !== null && $update && $known === $incoming && ! $force) {
                $result->unchanged[] = $entry['target'];
                continue;
            }
            $changes[] = [$entry, $target, $current !== null];
            $next[$entry['target']] = ['sha256' => $incoming];
            $result->written[] = $entry['target'];
        }

        if ($dryRun) { $result->providerRegistered = ! $result->hasConflicts() && $this->providerNeedsRegistration(); return $result; }

        $undo = [];
        try {
            foreach ($changes as [$entry, $target, $replacing]) {
                $undo[] = $this->writeWithBackup($entry['sourcePath'], $target, $replacing);
            }
            $registered = ! $result->hasConflicts() && $this->registerProvider($undo);
            $result->providerRegistered = $registered;
            $this->writeState(['manifest_version' => 1, 'package_version' => $this->payloadVersion(), 'files' => $next]);
        } catch (\Throwable $exception) {
            $this->rollback($undo);
            throw $exception;
        }
        return $result;
    }

    /** @return list<array{source:string,sourcePath:string,target:string,sha256:string}> */
    private function entries(): array
    {
        $manifestPath = $this->packageRoot.'/starter-manifest.json';
        if (! is_file($manifestPath)) throw new RuntimeException('Dashboard source manifest is missing.');
        try { $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException $e) { throw new RuntimeException('Dashboard source manifest is invalid.', 0, $e); }
        if (($manifest['version'] ?? null) !== 1 || ! is_array($manifest['files'] ?? null)) throw new RuntimeException('Unsupported dashboard source manifest.');
        $seen = []; $entries = [];
        foreach ($manifest['files'] as $entry) {
            if (! is_array($entry) || ! is_string($entry['source'] ?? null) || ! is_string($entry['target'] ?? null) || ! is_string($entry['sha256'] ?? null)) throw new InvalidArgumentException('Malformed dashboard source manifest entry.');
            if (isset($seen[$entry['target']])) throw new InvalidArgumentException('Duplicate dashboard source target: '.$entry['target']);
            $source = $this->sourcePath($entry['source']);
            $this->targetPath($entry['target']);
            if (! preg_match('/^[a-f0-9]{64}$/', $entry['sha256']) || ! hash_equals($entry['sha256'], hash_file('sha256', $source))) throw new RuntimeException('Dashboard payload checksum mismatch: '.$entry['source']);
            $seen[$entry['target']] = true;
            $entries[] = ['source' => $entry['source'], 'sourcePath' => $source, 'target' => $entry['target'], 'sha256' => $entry['sha256']];
        }
        return $entries;
    }

    private function sourcePath(string $source): string
    {
        if (! str_starts_with($source, 'starter/') || ! $this->safeRelative($source)) throw new InvalidArgumentException('Unsafe dashboard payload source: '.$source);
        $path = $this->packageRoot.'/'.$source;
        $root = realpath($this->packageRoot.'/starter'); $real = realpath($path);
        if ($root === false || $real === false || ! is_file($real) || ! str_starts_with($real, $root.DIRECTORY_SEPARATOR)) throw new RuntimeException('Dashboard payload source is missing or escapes starter: '.$source);
        return $real;
    }

    private function targetPath(string $target): string
    {
        if (! $this->safeRelative($target)) throw new InvalidArgumentException('Unsafe dashboard payload target: '.$target);
        $host = realpath($this->hostRoot);
        if ($host === false) throw new RuntimeException('Dashboard host root does not exist.');
        $path = $host.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $target);
        if (is_link($path)) throw new InvalidArgumentException('Dashboard target is a symbolic link: '.$target);
        $cursor = dirname($path);
        while ($cursor !== $host && ! file_exists($cursor)) $cursor = dirname($cursor);
        $existing = realpath($cursor);
        if ($existing === false || ($existing !== $host && ! str_starts_with($existing, $host.DIRECTORY_SEPARATOR))) throw new InvalidArgumentException('Dashboard target escapes host through a symbolic link: '.$target);
        return $path;
    }

    private function safeRelative(string $path): bool
    {
        return $path !== '' && ! str_contains($path, "\0") && ! str_starts_with($path, '/') && ! str_starts_with($path, '\\') && ! str_contains($path, '//') && ! preg_match('#(^|[\\/])\.{1,2}([\\/]|$)#', $path) && ! preg_match('#^[A-Za-z]:#', $path);
    }

    /** @return array{target:string,backup:?string,created:bool} */
    private function writeWithBackup(string $source, string $target, bool $replacing): array
    {
        $this->targetPath(substr($target, strlen(rtrim(realpath($this->hostRoot), DIRECTORY_SEPARATOR)) + 1));
        if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0775, true) && ! is_dir(dirname($target))) throw new RuntimeException('Cannot create dashboard target directory.');
        $backup = null;
        if ($replacing) {
            $backup = $this->backupPath($target);
            if (! is_dir(dirname($backup))) mkdir(dirname($backup), 0775, true);
            if (! copy($target, $backup)) throw new RuntimeException('Cannot back up dashboard file: '.$target);
        }
        try { $this->atomicCopy($source, $target); }
        catch (\Throwable $exception) { if ($backup !== null) @copy($backup, $target); elseif (! $replacing) @unlink($target); throw $exception; }
        return ['target' => $target, 'backup' => $backup, 'created' => ! $replacing];
    }

    /** @param list<array{target:string,backup:?string,created:bool}> $undo */
    private function registerProvider(array &$undo): bool
    {
        $path = $this->targetPath('bootstrap/providers.php');
        if (! is_file($path)) return false;
        $contents = (string) file_get_contents($path);
        if (! $this->providerNeedsRegistration()) return false;
        $updated = preg_match('/return\s*\[\s*\];\s*$/', $contents)
            ? preg_replace('/\];\s*$/', "    ".self::PROVIDER.",\n];\n", $contents, 1)
            : preg_replace_callback('/(\S)(\s*\n\];\s*)$/s', static function (array $match): string {
                $comma = str_ends_with($match[1], ',') ? '' : ',';
                return $match[1].$comma."\n    ".self::PROVIDER.",\n];\n";
            }, $contents, 1);
        if (! is_string($updated)) throw new RuntimeException('Cannot safely patch bootstrap/providers.php.');
        $undo[] = $this->writeStringWithBackup($updated, $path);
        return true;
    }

    /** @return array{target:string,backup:?string,created:bool} */
    private function writeStringWithBackup(string $contents, string $target): array
    {
        $backup = $this->backupPath($target); if (! is_dir(dirname($backup))) mkdir(dirname($backup), 0775, true);
        if (! copy($target, $backup)) throw new RuntimeException('Cannot back up bootstrap/providers.php.');
        try { $this->atomicWrite($contents, $target); } catch (\Throwable $exception) { @copy($backup, $target); throw $exception; }
        return ['target' => $target, 'backup' => $backup, 'created' => false];
    }

    /** @param list<array{target:string,backup:?string,created:bool}> $undo */
    private function rollback(array $undo): void
    {
        foreach (array_reverse($undo) as $change) {
            if ($change['created']) @unlink($change['target']);
            elseif ($change['backup'] !== null) @copy($change['backup'], $change['target']);
        }
    }

    private function backupPath(string $target): string
    {
        $host = rtrim((string) realpath($this->hostRoot), DIRECTORY_SEPARATOR);
        return $this->targetPath('.dashboard/backups/'.date('YmdHis').'-'.bin2hex(random_bytes(4)).'/'.ltrim(substr($target, strlen($host)), DIRECTORY_SEPARATOR));
    }
    private function state(): array { $path = $this->targetPath(self::STATE_FILE); if (! is_file($path)) return ['files' => []]; try { $state = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); return is_array($state) && is_array($state['files'] ?? null) ? $state : ['files' => []]; } catch (\JsonException) { throw new RuntimeException('Dashboard install state is invalid.'); } }
    private function writeState(array $state): void { $path = $this->targetPath(self::STATE_FILE); if (! is_dir(dirname($path))) mkdir(dirname($path), 0775, true); $this->atomicWrite(json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n", $path); }
    private function payloadVersion(): ?string { $path = $this->packageRoot.'/starter-manifest.json'; try { $version = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR)['package_version'] ?? null; return is_string($version) ? $version : null; } catch (\Throwable) { return null; } }
    private function atomicCopy(string $source, string $target): void { $temp = dirname($target).'/.'.basename($target).'.dashboard-'.bin2hex(random_bytes(5)); if (! copy($source, $temp) || ! rename($temp, $target)) { @unlink($temp); throw new RuntimeException('Cannot install dashboard file: '.$target); } }
    private function atomicWrite(string $contents, string $target): void { $temp = dirname($target).'/.'.basename($target).'.dashboard-'.bin2hex(random_bytes(5)); if (file_put_contents($temp, $contents) === false || ! rename($temp, $target)) { @unlink($temp); throw new RuntimeException('Cannot write dashboard file: '.$target); } }
    private function providerNeedsRegistration(): bool { $path = $this->targetPath('bootstrap/providers.php'); if (! is_file($path)) return false; $contents = (string) file_get_contents($path); if (preg_match('/^\s*App\\\\Providers\\\\DashboardServiceProvider::class\s*,?\s*(?:\/\/.*)?$/m', $contents)) return false; if (! preg_match('/return\s*\[/', $contents) || ! preg_match('/\];\s*$/', $contents)) throw new RuntimeException('Cannot safely register DashboardServiceProvider in bootstrap/providers.php.'); return true; }
    private function locked(\Closure $operation, bool $dryRun): InstallationResult { if ($dryRun) return $operation(); $lockPath = $this->targetPath('.dashboard/install.lock'); if (! is_dir(dirname($lockPath))) mkdir(dirname($lockPath), 0775, true); $lock = fopen($lockPath, 'c'); if ($lock === false || ! flock($lock, LOCK_EX)) throw new RuntimeException('Cannot acquire dashboard installer lock.'); try { return $operation(); } finally { flock($lock, LOCK_UN); fclose($lock); } }
}
