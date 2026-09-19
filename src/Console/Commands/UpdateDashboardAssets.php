<?php

namespace ZeroOneZ\Dashboard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class UpdateDashboardAssets extends Command
{
    protected $signature = 'dashboard:update-assets {--force : Overwrite changed published files}';
    protected $description = 'Publish package dashboard assets using the checked-in checksum manifest';

    public function handle(Filesystem $files): int
    {
        $root = dirname(__DIR__, 3);
        $manifestPath = $root.'/assets-manifest.json';
        if (! is_file($manifestPath)) {
            $this->error('Package asset manifest is missing. Run npm run manifest before distribution.');
            return self::FAILURE;
        }
        $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $written = $unchanged = $skipped = 0;
        foreach ($manifest['files'] ?? [] as $relative => $checksum) {
            $source = $root.'/public/'.$relative;
            if (! is_file($source) || ! hash_equals($checksum, hash_file('sha256', $source))) {
                $this->error('Asset source is missing or changed: '.$relative);
                return self::FAILURE;
            }
            $published = self::publishedRelativePath($relative, (string) config('dashboard.asset_path', 'dashboard'));
            if ($published === null) {
                $this->error('Unsupported asset manifest path: '.$relative);
                return self::FAILURE;
            }
            $target = public_path($published);
            if (is_file($target)) {
                if (hash_equals($checksum, hash_file('sha256', $target))) { $unchanged++; continue; }
                if (! $this->option('force')) { $this->warn('Skipped modified asset: '.$relative); $skipped++; continue; }
            }
            $files->ensureDirectoryExists(dirname($target));
            $files->copy($source, $target);
            $written++;
        }
        $this->info("Published {$written}; unchanged {$unchanged}; skipped {$skipped}.");
        return $skipped > 0 ? self::FAILURE : self::SUCCESS;
    }

    public static function publishedRelativePath(string $relative, string $assetPath): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        $assetPath = trim(str_replace('\\', '/', $assetPath), '/');
        if ($relative === '' || str_contains($relative, '../')) return null;
        if (str_starts_with($relative, 'dashboard/')) return $relative;
        if (str_starts_with($relative, 'build/')) {
            return ($assetPath === '' ? '' : $assetPath.'/').$relative;
        }
        return null;
    }
}
