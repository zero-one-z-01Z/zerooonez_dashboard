<?php

namespace App\Dashboard\Console\Commands;

use Illuminate\Console\Command;
use App\Dashboard\Services\Installation\InstallationService;

class InstallDashboard extends Command
{
    protected $signature = 'dashboard:install {--force : Back up and overwrite conflicting source files} {--dry-run : Show what would be installed without writing files}';
    protected $description = 'Install the dashboard source payload into this Laravel application';

    public function handle(): int
    {
        return $this->executeInstallation(new InstallationService(dirname(__DIR__, 3), base_path()), false);
    }

    private function executeInstallation(InstallationService $installer, bool $update): int
    {
        try { $result = $update ? $installer->update((bool) $this->option('force'), (bool) $this->option('dry-run')) : $installer->install((bool) $this->option('force'), (bool) $this->option('dry-run')); }
        catch (\Throwable $exception) { $this->error($exception->getMessage()); return self::FAILURE; }
        $this->info(sprintf('Written %d; adopted %d; unchanged %d; conflicts %d.', count($result->written), count($result->adopted), count($result->unchanged), count($result->conflicts)));
        foreach ($result->conflicts as $file) $this->warn('Preserved modified file: '.$file);
        if ($result->providerRegistered) $this->line('Registered App\\Providers\\DashboardServiceProvider.');
        return $result->hasConflicts() ? self::FAILURE : self::SUCCESS;
    }
}
