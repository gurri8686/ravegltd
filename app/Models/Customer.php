<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\LogsActivityTrait;

class Customer extends Model
{
    use LogsActivityTrait;
    use HasFactory;
	
	public static function getActive(){
		return Customer::all();
	}
	
	public static function info($customer_id){
		return Customer::where('id', $customer_id)->first();
	}
}
