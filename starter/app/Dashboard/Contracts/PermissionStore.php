<?php

namespace App\Dashboard\Contracts;

interface PermissionStore
{
    public function firstOrCreate(string $key): bool;
}
