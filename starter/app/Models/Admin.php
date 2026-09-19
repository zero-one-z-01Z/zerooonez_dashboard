<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasFactory;
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];
    protected $appends = ['online'];
    protected $table='admins';

    protected $casts = [
        "id"=>"integer",
        "super"=>"integer",
        "active"=>"boolean",
        'role_id' => 'integer',
    ];

    public function role(){
        return $this->belongsTo(Role::class);
    }

    public function admin_company()
    {
        return $this->belongsTo(Admin::class,'admin_company_id');
    }

    public function havePermission($permission) {
        if($this->super){
            return true;
        }
        return $this->role?->permissions()->where('key_name',$permission)->exists() ?? false;
    }
    public function customer_service()
    {
        return $this->hasOne(CustomerService::class);
    }
    public function customer_service_employee()
    {
        return $this->hasOne(CustomerServiceEmployee::class);
    }

    public function all_permissions()
    {
        if($this->super){
            return Permission::pluck('key_name')->toArray();
        }
        return $this->role?->permissions()->pluck('key_name')->toArray() ?? [];
    }

    public function have_permission($permission)
    {
        return collect($this->all_permissions())->contains($permission);
    }


    public function getOnlineAttribute()
    {
        $check = $this->last_login && $this->last_login >= Carbon::now()->subMinutes(30);
        return __('admin.'.$check?"yes":"no");
    }


}
