<?php

namespace ZeroOneZ\Dashboard\Contracts;

interface PermissionStore
{
    public function firstOrCreate(string $key): bool;
}
