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
        'supplier_invoice_id', 'supplier_id', 'product_id','remarks', 'product_details', 'quantity', 'unit_price','sale_price','sub_total', 'is_archive','product_info'
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
			$supplierName = $this->supplier?->name ?? 'N/A';
			return (explode(' ', $supplierName)[0]).'...'.'|Qty:'.($this->quantity - $stock).'|'.'P:'.$this->unit_price.'|'.$this->remarks;
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
			$supplierName = $this->supplier?->name ?? 'N/A';
			return (explode(' ', $supplierName)[0]).'...'.'|'.$this->created_at;
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
			'remarks','unit_price','sale_price',
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
                        "sale_price"=> $item->sale_price,
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

	/**
	 * BATCH version of getProductSuppliers — accepts an ARRAY of product IDs and returns a map
	 * keyed by product_id. Replaces the N+1 pattern in ajaxfetchInvoiceAllDetail() where the old
	 * loop called getProductSuppliers() once per product, each making its own stock query per row
	 * (6 products × ~100 rows × 1 query = 600 queries → ~25 seconds).
	 *
	 * After this batching: ONE supplier_invoice_products query + ONE stock-roll-up query, total
	 * 2 DB hits regardless of how many products on the invoice. Same data shape as the old method.
	 */
	public static function getProductSuppliersBatch(array $productIds){
		if (empty($productIds)) return [];

		// Step 1 — one query that pulls all rows for ALL products at once
		$rows = SupplierInvoiceProduct::select(
				'id', 'remarks', 'unit_price', 'sale_price',
				'supplier_invoice_id as invoice_id',
				'product_id', 'supplier_id',
				DB::raw('SUM(quantity) as total_quantity')
			)
			->with(['product' => function($q){ $q->select('id','product_id','name'); }])
			->with(['supplier' => function($q){ $q->select('id','supplier_id','name'); }])
			->whereIn('product_id', $productIds)
			->where('is_archive', 0)
			->groupBy('supplier_invoice_id', 'id')
			->get();

		if ($rows->isEmpty()) return [];

		// Step 2 — collect (product, supplier, invoice, ref) tuples and do ONE stock roll-up
		$invoiceIds = $rows->pluck('invoice_id')->unique()->values()->all();
		$refIds = $rows->pluck('id')->unique()->values()->all();

		$stockMap = [];
		if (!empty($invoiceIds) && !empty($refIds)) {
			$stockRows = StockProduct::select(
					'ref_id', 'invoice_id', 'supplier_id', 'product_id', 'price',
					DB::raw("SUM(CASE WHEN event = 'stock_added' THEN stock ELSE 0 END) AS stock_added"),
					DB::raw("SUM(CASE WHEN event = 'stock_updated' THEN stock ELSE 0 END) AS stock_updated"),
					DB::raw("SUM(CASE WHEN event = 'supplier_return' THEN stock ELSE 0 END) AS supplier_return"),
					DB::raw("SUM(CASE WHEN event = 'dump' THEN stock ELSE 0 END) AS dump"),
					DB::raw("(
						(SUM(CASE WHEN event = 'stock_added' THEN stock ELSE 0 END)
						+ SUM(CASE WHEN event = 'stock_updated' THEN stock ELSE 0 END))
						- (SUM(CASE WHEN event = 'supplier_return' THEN stock ELSE 0 END)
						+ SUM(CASE WHEN event = 'dump' THEN stock ELSE 0 END))
					) AS net_stock")
				)
				->with(['customerStocks' => function($q){}])
				->where('type', 'supplier')
				->where('is_archived', 0)
				->whereIn('product_id', $productIds)
				->whereIn('invoice_id', $invoiceIds)
				->whereIn('ref_id', $refIds)
				->groupBy('invoice_id', 'ref_id', 'supplier_id', 'product_id')
				->get();

			foreach ($stockRows as $sr) {
				$data = $sr->toArray();
				// Adjust net_stock by customerStocks if present (mirrors the per-row map() in stock())
				if (!empty($data['customer_stocks']) && isset($data['customer_stocks']['net_stock'])) {
					$data['net_stock'] = (float)$data['net_stock'] - (float)$data['customer_stocks']['net_stock'];
				}
				// Key by ref_id so the per-row loop below can O(1) look up its stock entry
				$stockMap[$sr->ref_id] = $data;
			}
		}

		// Step 3 — group rows back by product_id and build the same shape getProductSuppliers returned
		$out = [];
		foreach ($rows as $item) {
			$net = isset($stockMap[$item->id]) ? (float)($stockMap[$item->id]['net_stock'] ?? 0) : 0;
			$invoices = [];
			if ($net > 0) {
				$supplierName = $item->supplier?->name ?? 'N/A';
				$shortName = explode(' ', trim($supplierName))[0] ?? $supplierName;
				$title = sprintf("%s...|Qty:%s|P:%s|", $shortName, $net, number_format((float)$item->unit_price, 2)) . $item->remarks;
				$invoices[] = (object)[
					'id' => $item->id,
					'supplier_invoice_id' => $item->invoice_id,
					'remarks' => '',
					'supplier_id' => $item->supplier_id,
					'product_id' => $item->product_id,
					'quantity' => $net,
					'unit_price' => $item->unit_price,
					'sale_price' => $item->sale_price,
					'invoice_title' => $title,
					'stock_consumed' => '',
					'invoice_title_short' => $title,
				];
			}

			// Detach the supplier instance so each row carries its own invoices (matches old map() behavior)
			if ($item->relationLoaded('supplier') && $item->supplier) {
				$supplier = clone $item->supplier;
				$supplier->setRelation('invoices', collect($invoices));
				$item->setRelation('supplier', $supplier);
			}

			$out[$item->product_id][] = $item;
		}

		return $out;
	}
}
