<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

final class CreateDashboardAdmin extends Command
{
    protected $signature = 'dashboard:create-admin {name} {email} {phone} {--password=}';
    protected $description = 'Create the first active dashboard super administrator.';

    public function handle(): int
    {
        $password = (string) ($this->option('password') ?: $this->secret('Password'));
        if (strlen($password) < 8) { $this->error('Password must contain at least 8 characters.'); return self::FAILURE; }
        Admin::query()->create(['name' => $this->argument('name'), 'email' => $this->argument('email'), 'phone' => $this->argument('phone'), 'password' => Hash::make($password), 'super' => true, 'active' => true, 'role_id' => 0]);
        $this->info('Dashboard administrator created.');
        return self::SUCCESS;
    }
}
