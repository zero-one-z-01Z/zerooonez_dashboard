<?php

namespace App\Dashboard\Services\Installation;

final class InstallationResult
{
    /** @param list<string> $written @param list<string> $adopted @param list<string> $unchanged @param list<string> $conflicts */
    public function __construct(
        public array $written = [],
        public array $adopted = [],
        public array $unchanged = [],
        public array $conflicts = [],
        public bool $providerRegistered = false,
    ) {}

    public function hasConflicts(): bool
    {
        return $this->conflicts !== [];
    }
}
