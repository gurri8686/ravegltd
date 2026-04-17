<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoiceOrder extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
    use HasFactory;
	
	protected $fillable = ["supplier_invoice_id","supplier_id","sub_total","total","vat","porterage"];
}
