<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\StockProduct;
use DB;

class SupplierInvoiceProduct extends Model
{
    use \App\Models\Traits\LogsActivityTrait;
	use \Awobaz\Compoships\Compoships;
    use HasFactory;
	
	protected $casts = [
        'product_info' => 'array',
    ];
	
    protected $fillable = [
        'supplier_invoice_id', 'supplier_id', 'product_id','remarks', 'product_details', 'quantity', 'unit_price','sub_total', 'is_archive','product_info'
    ];
	
	protected $appends = ['invoice_title','stock_consumed','invoice_title_short','available_qty'];
	
	public function getInvoiceTitleAttribute()
    {
		if(!empty($this->created_at)){
			$stock = 0;
			if(sizeof($this->customers) > 0){
				$arr = [];
				foreach($this->customers as $c){
					if($c->is_archive == 0){
						$arr[] = $c->quantity;
					}
				}
				$stock = array_sum($arr);
			}
			return (explode(' ', $this->supplier->name)[0]).'...'.'|Qty:'.($this->quantity - $stock).'|'.'P:'.$this->unit_price.'|'.$this->remarks;
		}
    }
	
	public function getInvoiceTitleShortAttribute()
    {
		if(!empty($this->created_at)){
			$stock = 0;
			if(sizeof($this->customers) > 0){
				$arr = [];
				foreach($this->customers as $c){
					if($c->is_archive == 0){
						$arr[] = $c->quantity;
					}
				}
				$stock = array_sum($arr);
			}
			return (explode(' ', $this->supplier->name)[0]).'...'.'|'.$this->created_at;
		}
    }
	
	public function getAvailableQtyAttribute(){
		$stock = 0;
		if(sizeof($this->customers) > 0){
			foreach($this->customers as $c){
				if($c->is_archive == 0){
					$stock += $c->quantity;
				}
			}
		}
		return $this->quantity - $stock;
	}

	public function getStockConsumedAttribute(){
		if(sizeof($this->customers) > 0){
			$arr = [];
			foreach($this->customers as $c){
				$arr[] = $c->quantity;
			}
			return array_sum($arr);
		}
		
		return 0;
	}
        
    public function product(){
        return $this->hasOne('App\Models\Product','id','product_id');
    }
	
	public function invoice(){
        return $this->hasOne('App\Models\SupplierInvoice','id','supplier_invoice_id');
    }
	
	public function customers(){
        return $this->hasMany(\App\Models\CustomerInvoiceProduct::class, 
			['supplier_invoice_id','supplier_id','product_id','supplier_invoice_product_id'], ['supplier_invoice_id','supplier_id','product_id','id']);
    }
	
	public function supplier(){
        return $this->hasOne('App\Models\Supplier','id','supplier_id');
    }
	
	public function returns(){
        return $this->hasMany('App\Models\StockProduct','invoice_id','supplier_invoice_id')
			->where('type','supplier')
			->where('is_archived',0)
			->where('event','supplier_return');
    }
	
	public static function getSuppliers(){
		return SupplierInvoiceProduct::
		with('product')->with('supplier')
		->groupBy('product_id', 'supplier_id')
		->get();
	}
	
	public static function getProductSuppliers($product_id){
		$results = SupplierInvoiceProduct::select(
			'id',
			'remarks','unit_price',
			'supplier_invoice_id as invoice_id',
			'product_id',
			'supplier_id',
			DB::raw('SUM(quantity) as total_quantity')
		)->with(['product' => function($query){
			return $query->select("id","product_id","name");
		}])
		->with(['supplier' => function($query) use ($product_id){
			$query->select(['id','supplier_id','name']);
			/* ****** old approach ***** */
			/*return $query->select("id","supplier_id","name","email")
				->with(['invoices' => function($query) use ($product_id){
				return $query
					->with(['customers' => function($query){
						return $query
							->select("id","is_archive","supplier_invoice_id","supplier_invoice_product_id","remarks","customer_invoice_id","supplier_id","customer_id","product_id","quantity")
							->where('is_archive',0);
					}])
					->select("id","created_at","supplier_invoice_id","remarks","supplier_id","product_id","quantity","unit_price")
					->where("product_id",$product_id);
			}]);*/
		}])
		->where('product_id',$product_id)
		->where('is_archive',0)
		//->groupBy('product_id', 'supplier_id')
		//->groupBy('supplier_id')
		->groupBy('supplier_invoice_id' ,'id')
		->get();
		
		//print_r($results->toArray()); exit;
		$results = $results->map(function ($item) {
			$invoices = [];
            $exists = StockProduct::stock([
                'supplier_id' => $item->supplier_id,
                'product_id'  => $item->product_id,
				'ref_id' => $item->id,
				'invoice_id' => [$item->invoice_id]
            ]);
			if(!empty($exists)){
				if($exists['net_stock'] > 0){
					$invoices[] = $exists;
				}
			}
			//print_r($invoices);
			//$item->supplier->invoices = $invoices;
			$data = [];
			if(sizeof($invoices) > 0){
			
				$i = 0;
				foreach($invoices as $invoice){ 
					$invoice = (object) $invoice;
					//print_R($invoice); exit;
					$data[$i] = (object) [
						"id"=> $invoice->id,
                        //"created_at" => "12 Nov 2025",
                        "supplier_invoice_id" => $item->invoice_id,
                        "remarks"=> "",
                        "supplier_id"=> $invoice->supplier_id,
                        "product_id"=> $invoice->product_id,
                        "quantity"=> $invoice->net_stock,
                        "unit_price"=> $item->unit_price,
                        "invoice_title"=> $invoice->invoice_title.$item->remarks,
                        "stock_consumed"=> "",
                        "invoice_title_short"=> $invoice->invoice_title_short.$item->remarks,
					];
					$i++;
				}
			}
			//$item->data = $data;
			//$item->supplier->invoices = $data;
			//$item->supplier->setRelation('invoices', collect($data));
			
			/** 🔑 KEY FIX: CLONE THE SUPPLIER */
			if ($item->relationLoaded('supplier')) {
				$supplier = clone $item->supplier; // 🔥 detach shared instance
				$supplier->setRelation('invoices', collect($data));
				$item->setRelation('supplier', $supplier);
			}
			
			return $item;
        });
		return $results;
	}
	
	public static function getProductSupplier($product_id, $supplier_id){
		$customer = \App\Models\CustomerInvoiceProduct::getProductSupplier($product_id, $supplier_id);
		return SupplierInvoiceProduct::select(
			'id',
			'product_id',
			'supplier_id',
			DB::raw('SUM(quantity) as total_quantity')
		)->with('product')->with('supplier')
		->where('product_id',$product_id)
		->where('supplier_id',$supplier_id)
		->groupBy('product_id', 'supplier_id')
		->first();
	}
	
	public static function getProductSupplierInvoices($product_id, $supplier_id){
		$invoices = self
			::where("supplier_id",$supplier_id)
			->with('customers')
			->where("product_id",$product_id)
			->get();
			
		return $invoices;
	}
}
