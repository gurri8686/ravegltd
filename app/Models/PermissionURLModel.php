<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionURLModel extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    protected $table = 'permissions';
    public function permissions()
    {
        return $this->hasMany('App\Models\PermissionRoleModel','permission_id');
    }
}
