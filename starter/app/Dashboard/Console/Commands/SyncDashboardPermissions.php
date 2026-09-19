<?php

namespace App\Dashboard\Console\Commands;

use App\Dashboard\Contracts\PermissionStore;
use App\Dashboard\Services\Dashboard\ResourceDefinitionV2;
use App\Dashboard\Services\Dashboard\ResourceRegistry;
use Illuminate\Console\Command;

/** Manual, repeatable insertion. Never grants or removes role memberships. */
class SyncDashboardPermissions extends Command
{
    protected $signature = 'dashboard:sync-permissions {resource? : Resource key; omitted means all registered resources}';
    protected $description = 'Insert missing dashboard permission keys without granting any roles';

    public function handle(ResourceRegistry $registry, ResourceDefinitionV2 $definitions, PermissionStore $permissions): int
    {
        $resources = $registry->all();
        if ($key = $this->argument('resource')) {
            if (!isset($resources[$key])) { $this->error('تعريف المورد غير موجود أو غير صالح.'); return self::FAILURE; }
            $resources = [$key => $resources[$key]];
        }
        $created = 0;
        foreach ($resources as $definition) {
            $keys = ($definition['schema_version'] ?? 1) === 2 ? $definitions->permissions($definition)
                : array_map(fn ($action) => $action.'_'.$definition['permission'], ['view', 'create', 'update', 'delete']);
            foreach ($keys as $key) $created += (int) $permissions->firstOrCreate($key);
        }
        $this->info("أُضيفت {$created} صلاحيات مفقودة. لم تتغير أدوار المستخدمين.");
        return self::SUCCESS;
    }
}
