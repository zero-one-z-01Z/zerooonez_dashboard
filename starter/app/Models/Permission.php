<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        "id" => "integer",
    ];
    protected $table = 'permissions';

    public function roles(){
        return $this->belongsToMany(RolePermission::class,'role_permissions','permission_id', 'role_id');
    }
}
