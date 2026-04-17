<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkTimePermission extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    protected $table = 'work_time_permissions';
}
