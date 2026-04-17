<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
	
	public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d M Y'); // Example: 09 Oct 2025
    }
	
	public function invoices(){
        return $this->hasMany(\App\Models\SupplierInvoiceProduct::class,"supplier_id", "id");
    }
	
	public static function getActive(){
		return Supplier::all();
	}
	
	public static function info($id){
		return Supplier::where('id', $id)->first();
	}
}
