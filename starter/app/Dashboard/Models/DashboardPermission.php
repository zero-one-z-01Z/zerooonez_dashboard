<?php

namespace App\Dashboard\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardPermission extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public function getTable(): string
    {
        return (string) config('dashboard.permission_table', parent::getTable());
    }
}
