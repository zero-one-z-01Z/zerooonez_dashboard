<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Role extends \Illuminate\Database\Eloquent\Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        "id" => "integer",
    ];
    protected $table='roles';
    protected $appends = ['name'];

    public function permissions(){
        return $this->belongsToMany(Permission::class,'role_permissions','role_id', 'permission_id');
    }


    public function getNameAttribute(){
        $name = $this->attributes['name_ar'];
        $lang = request()->header('lang') ?: request()->get('lang') ?: request()->lang;
        if ($lang && isset($this->attributes["name_$lang"])) {
            $name = $this->attributes["name_$lang"];
        }
        return $name;
    }

    public function delete()
    {
        if (Admin::query()->where('role_id', $this->id)->exists()) {
            throw new \LogicException('A role assigned to administrators cannot be deleted.');
        }
        return DB::transaction(function () {
            $this->permissions()->detach();
            return parent::delete();
        });
    }
}
