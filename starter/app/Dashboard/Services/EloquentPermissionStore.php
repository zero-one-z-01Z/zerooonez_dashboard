<?php

namespace App\Dashboard\Services;

use RuntimeException;
use App\Dashboard\Contracts\PermissionStore;

final class EloquentPermissionStore implements PermissionStore
{
    public function firstOrCreate(string $key): bool
    {
        $model = config('dashboard.permission_model');
        if (! is_string($model) || ! is_a($model, \Illuminate\Database\Eloquent\Model::class, true)) {
            throw new RuntimeException('dashboard.permission_model must be an Eloquent model class.');
        }
        $column = (string) config('dashboard.permission_key_column', 'key_name');
        return (bool) $model::query()->firstOrCreate([$column => $key])->wasRecentlyCreated;
    }
}
