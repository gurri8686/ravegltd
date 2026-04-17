<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    protected $fillable = [
        'type'
    ];
}
