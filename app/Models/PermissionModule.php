<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionModule extends Model
{

    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    protected $table = 'permission_modules';
    public function groups()
    {
        return $this->hasMany('App\Models\PermissionGroup');
    }
}
