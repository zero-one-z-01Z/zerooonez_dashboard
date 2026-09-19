<?php

namespace ZeroOneZ\Dashboard\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

final class DashboardUser extends Authenticatable
{
    protected $table = 'dashboard_test_users';

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];
}
