<?php

namespace App\Dashboard\Console\Commands;

use Illuminate\Console\Command;
use App\Dashboard\Services\Installation\InstallationService;

final class UpdateDashboard extends Command
{
    protected $signature = 'dashboard:update {--force : Back up and overwrite conflicting source files} {--dry-run : Show what would be updated without writing files}';
    protected $description = 'Update source files previously installed by the dashboard installer';

    public function handle(): int
    {
        try { $result = (new InstallationService(dirname(__DIR__, 3), base_path()))->update((bool) $this->option('force'), (bool) $this->option('dry-run')); }
        catch (\Throwable $exception) { $this->error($exception->getMessage()); return self::FAILURE; }
        $this->info(sprintf('Written %d; adopted %d; unchanged %d; conflicts %d.', count($result->written), count($result->adopted), count($result->unchanged), count($result->conflicts)));
        foreach ($result->conflicts as $file) $this->warn('Preserved modified file: '.$file);
        return $result->hasConflicts() ? self::FAILURE : self::SUCCESS;
    }
}
