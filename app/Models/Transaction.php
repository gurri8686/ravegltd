<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    protected $fillable = [
        'passbook_id',
        'payment_id',
        'purpose',
        'reference_id',
    ];

    public function payment(){
        return $this->hasOne('App\Models\Payment','id','payment_id');
    }
}
