<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelHasRole extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    protected $table='model_has_roles';
}
