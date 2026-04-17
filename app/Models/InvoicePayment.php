<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
    protected $fillable = [
        'customer_invoice_id',
        'payment_id'
    ];
    public function payment(){
        return $this->hasOne('App\Models\Payment','id','payment_id');
    }
}
