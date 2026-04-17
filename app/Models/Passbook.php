<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Passbook extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    protected $fillable = [
        'user_id',
        'user_type',
        'amount',
        'type',
    ];

    public function transaction(){
        return $this->hasOne('App\Models\Transaction','passbook_id','id');
    } 

    public function customer(){
        return $this->hasOne('App\Models\User','id','user_id');
    }
}
