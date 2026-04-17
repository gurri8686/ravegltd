<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionRoleModel extends Model
{
    //use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    public $timestamps = false;
    protected $table = 'role_has_permissions';

    public function permissions()
    {
        return $this->hasOne('App\Models\PermissionURLModel','id','permission_id');
    }
}
