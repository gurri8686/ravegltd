<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\LogsActivityTrait;

class StockClosing extends Model
{
    use LogsActivityTrait;
    use HasFactory;
	
	protected $fillable = [
        'product_id','stock',
        'created_at','updated_at'
    ];
}
