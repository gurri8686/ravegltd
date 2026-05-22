<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerHistoryEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'to_email',
        'cc_email',
        'subject',
        'period_from',
        'period_to',
        'invoice_count',
        'status',
        'error',
        'sent_by',
    ];
}
