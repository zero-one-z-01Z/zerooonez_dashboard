<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        "id" => "integer",
        "permission_id" => "integer",
        "role_id" => "integer"
    ];

    protected $table = 'role_permissions';
}
