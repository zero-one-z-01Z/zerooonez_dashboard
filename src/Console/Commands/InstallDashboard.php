<?php

namespace ZeroOneZ\Dashboard\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallDashboard extends Command
{
    protected $signature = 'dashboard:install {--force : Overwrite package-owned assets and published config/views}';
    protected $description = 'Install config, optional view sources, generator directories, and dashboard assets';

    public function handle(Filesystem $files): int
    {
        $root = dirname(__DIR__, 3);
        $force = (bool) $this->option('force');
        $copies = [
            $root.'/config/dashboard.php' => config_path('dashboard.php'),
        ];
        foreach ($copies as $source => $target) {
            if (is_file($target) && ! $force) {
                $this->line('Kept existing '.$target);
                continue;
            }
            $files->ensureDirectoryExists(dirname($target));
            $files->copy($source, $target);
        }
        foreach (['definition_path', 'routes_path'] as $key) {
            $files->ensureDirectoryExists(base_path(trim((string) config('dashboard.generator.'.$key), '/')));
        }
        $exit = $this->call('dashboard:update-assets', $force ? ['--force' => true] : []);
        $this->newLine();
        $this->info('Dashboard installed. Enable the local builder explicitly with DASHBOARD_BUILDER_ENABLED=true.');
        return $exit;
    }
}
