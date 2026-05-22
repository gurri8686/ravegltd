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
        if (empty($value)) return null;
        try {
            return \Carbon\Carbon::parse($value)->format('d M Y');
        } catch (\Exception $e) {
            return $value;
        }
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
