<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class RoleModel extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    protected $table = 'roles';

    public function customerrole(){
        return $this->hasMany('App\Models\ModelHasRole','role_id','id');
    }
}
