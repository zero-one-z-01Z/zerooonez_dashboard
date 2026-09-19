<?php

namespace App\Dashboard\Console\Commands;

use Illuminate\Console\Command;
use App\Dashboard\Services\Installation\InstallationService;

class UpdateDashboardAssets extends Command
{
    protected $signature = 'dashboard:update-assets {--force : Back up and overwrite conflicting source files} {--dry-run : Show what would change without writing files}';
    protected $description = 'Legacy alias for dashboard:update (updates the source payload)';

    public function handle(): int
    {
        return $this->call('dashboard:update', array_filter(['--force' => (bool) $this->option('force'), '--dry-run' => (bool) $this->option('dry-run')]));
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
