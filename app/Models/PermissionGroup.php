<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionGroup extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    protected $table = 'permission_groups';
    public function modules()
    {
        return $this->belongsTo('App\Models\PermissionModule','permission_module_id');
    }
}
