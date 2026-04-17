<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\LogsActivityTrait;
use DB;
use App\Models\CustomerInvoice;
use App\Models\SupplierInvoiceProduct;
use App\Models\SupplierInvoice;

class StockProduct extends Model
{
	use LogsActivityTrait;
	use \Awobaz\Compoships\Compoships;
    use HasFactory;
	
	protected $fillable = [
        'product_id','stock',
        'type',
        'invoice_id',
        'event',
        'price',
		'ref_id','is_archived','supplier_id','customer_id','remarks','supplier_invoice_product_id','supplier_invoice_id'
    ];
	
	public function product(){
        return $this->hasOne('App\Models\Product','id','product_id');
    }
	
	public function supplier(){
        return $this->hasOne('App\Models\Supplier','id','supplier_id');
    }
	
	public function customer(){
        return $this->hasOne('App\Models\Customer','id','customer_id');
    }
	
	public function customerInvoice(){
        return $this->hasOne('App\Models\CustomerInvoice','id','invoice_id');
    }
	
	public function supplierInvoice(){
        return $this->hasOne('App\Models\SupplierInvoice','id','invoice_id');
    }
	
	public function customerStocks()
	{
		return $this->hasOne(
			StockProduct::class,
			['supplier_invoice_product_id', 'supplier_invoice_id'],
			['ref_id', 'invoice_id']
		)
		->where('type', 'customer')
		->where('is_archived', 0)
		->select(
			'supplier_invoice_product_id',
			'supplier_invoice_id',
			DB::raw("SUM(CASE WHEN event = 'stock_consumed' THEN stock ELSE 0 END) AS total_consumed"),
			DB::raw("SUM(CASE WHEN event = 'customer_return' THEN stock ELSE 0 END) AS total_returned"),
			DB::raw("(SUM(CASE WHEN event = 'stock_consumed' THEN stock ELSE 0 END)
					- SUM(CASE WHEN event = 'customer_return' THEN stock ELSE 0 END)) AS net_stock")
		)
		->groupBy('supplier_invoice_product_id', 'supplier_invoice_id');
	}
	
	public function invoiceProduct()
	{
		return $this->hasOne(
			SupplierInvoiceProduct::class,
			['id', 'supplier_invoice_id'],
			['ref_id', 'invoice_id']
		);
	}
	
	public static function customerSupplierStock($invoice_id, $ref_id, $map = []){
		$supplier = self::whereIn('invoice_id', $invoice_id)->where('ref_id',$ref_id)->first();
		
		if(empty($supplier)){
			return "";
		}
		
		return self::stock([
			'product_id' => $supplier->product_id,
			'invoice_id' =>[$supplier->supplier_invoice_id],
			'ref_id' => $supplier->supplier_invoice_product_id
		]);
	}
	
	public static function stock($params = []){
		//print_R($params); exit;
		$suppliers = self
		//::where('is_archived', 0)
		//->where('type', 'supplier')
		/*->with(['supplier' => function($q){
			$q->select(['id','supplier_id','name']);
		}])*/
		/*->with(['invoiceProduct' => function($q){
			$q->select(['id','supplier_invoice_id','supplier_id']);
		}])*/
		::with(['customerStocks' => function($q){}]);
		
		if(isset($params['type']) && !empty($params['type']) ){
			$suppliers->where('type', ($params['type']));
		}else{
			$suppliers->where('type', 'supplier');
		}
		
		if(isset($params['is_archived']) && !empty($params['is_archived']) ){
			$suppliers->where('is_archived', ($params['is_archived']));
		}else{
			$suppliers->where('is_archived', 0);
		}
		
		if(isset($params['product_id']) && !empty($params['product_id']) ){
			$suppliers->where('product_id', ($params['product_id']));
		}
		
		if(isset($params['invoice_id']) && !empty($params['invoice_id']) ){
			$suppliers->whereIn('invoice_id', ($params['invoice_id']));
		}
		
		if(isset($params['supplier_id']) && !empty($params['supplier_id']) ){
			$suppliers->where('supplier_id', ($params['supplier_id']));
		}
				
		if(isset($params['ref_id']) && !empty($params['ref_id']) ){
			$suppliers->where('ref_id', $params['ref_id']);
		}
		
		$suppliers = $suppliers->select(
			'ref_id as id','price',
			'ref_id',
			'invoice_id',
			'supplier_id',
			'product_id',
			DB::raw("SUM(CASE WHEN event = 'stock_added' THEN stock ELSE 0 END) AS stock_added"),
			DB::raw("SUM(CASE WHEN event = 'stock_updated' THEN stock ELSE 0 END) AS stock_updated"),
			DB::raw("SUM(CASE WHEN event = 'supplier_return' THEN stock ELSE 0 END) AS supplier_return"),
			DB::raw("SUM(CASE WHEN event = 'dump' THEN stock ELSE 0 END) AS dump"),
			DB::raw("(
				(SUM(CASE WHEN event = 'stock_added' THEN stock ELSE 0 END)
				+ SUM(CASE WHEN event = 'stock_updated' THEN stock ELSE 0 END))
				- (SUM(CASE WHEN event = 'supplier_return' THEN stock ELSE 0 END)
				+ SUM(CASE WHEN event = 'dump' THEN stock ELSE 0 END))
			) AS net_stock"),
			DB::raw("CONCAT(invoice_id, ':left(',
				(SUM(CASE WHEN event = 'stock_added' THEN stock ELSE 0 END)
				+ SUM(CASE WHEN event = 'stock_updated' THEN stock ELSE 0 END))
				- (SUM(CASE WHEN event = 'supplier_return' THEN stock ELSE 0 END)
				+ SUM(CASE WHEN event = 'dump' THEN stock ELSE 0 END)),
			')') AS label")
		)
		->groupBy('invoice_id', 'ref_id', 'supplier_id', 'product_id');
		
		//$suppliers = $suppliers->toSql(); echo $suppliers; exit;
		
		$suppliers = $suppliers->get();
		
		//print_r($suppliers->toArray()); exit;
		
		$suppliers = $suppliers->map(function ($supplier) {
			$data = $supplier->toArray();
			
			//print_r($data); exit;

			// Adjust parent net_stock if customerStocks exist
			if (!empty($data['customer_stocks']) && isset($data['customer_stocks']['net_stock'])) {
				$childNet = (float) $data['customer_stocks']['net_stock'];
				$data['net_stock'] = (float) $data['net_stock'] - $childNet;
			}

			// Build supplier short name (before first space)
			$supplierName = $supplier->supplier?->name ?? 'Unknown';
			$shortName = explode(' ', trim($supplierName))[0] ?? $supplierName;

			// Final formatted label: "...|Qty:700|P:5.00|"
			$data['label'] = sprintf(
				"%s...|Qty:%s|P:%s|",
				$shortName,
				(float) $data['net_stock'],
				number_format((float) ($data['price'] ?? 0), 2)
			);
			
			$data['invoice_title'] = sprintf(
				"%s...|Qty:%s|P:%s|",
				$shortName,
				(float) $data['net_stock'],
				number_format((float) ($data['price'] ?? 0), 2)
			);
			
			$data['invoice_title_short'] = sprintf(
				"%s...|Qty:%s|P:%s|",
				$shortName,
				(float) $data['net_stock'],
				number_format((float) ($data['price'] ?? 0), 2)
			);

			return $data;
		})
		->values();
		
		if(isset($params['ref_id']) && !empty($params['ref_id']) ){
			return isset($suppliers[0]) ? $suppliers[0] : "";
		}else{
			return $suppliers;
		}
		
		return $suppliers;
	}
	
	public static function createOrUpdateStock($data)
	{
		return static::updateOrCreate(
			[
				'supplier_invoice_product_id' => isset($data['supplier_invoice_product_id']) ? $data['supplier_invoice_product_id'] : 0,
				'supplier_invoice_id' => isset($data['supplier_invoice_id']) ? $data['supplier_invoice_id'] : 0,
				'supplier_id' => isset($data['supplier_id']) ? $data['supplier_id'] : 0,
				'customer_id' => isset($data['customer_id']) ? $data['customer_id'] : 0,
				'product_id' => $data['product_id'],
				'type' => $data['type'],
				'invoice_id' => $data['invoice_id'],
				'ref_id' => $data['ref_id'],
			],
			[
				'stock' => $data['stock'],
				'event' => $data['event'],
				'price' => $data['price']
			]
		);
	}
	
	public static function removeStock($data){
		return self::where('type',$data['type'])
		->where('invoice_id',$data['invoice_id'])
		->where('ref_id',$data['ref_id'])
		->update(['event' => $data['event'],'is_archived' => 1]);
	}
	
	public static function record($data){
		return self::create([
			'product_id' => $data['product_id'],
			'type' => $data['type'],
			'invoice_id' => $data['invoice_id'],
			'ref_id' => $data['ref_id'],
			'stock' => $data['stock'],
			'event' => $data['event'],
			'price' => $data['price']
		]);
	}
	
	public static function _productsWithInvoiceStockSupplier($invoice_id, $supplier_id, $product_id, $ref_id = "", $ignore = []){
		$suppliers = self::where('product_id', $product_id)
		->where('is_archived', 0)
		->where('invoice_id', $invoice_id)
		->where('type', 'supplier')
		->with('customerStocks')
		->where('supplier_id', $supplier_id);

		if(!empty($ignore)){
			$suppliers->whereNotIn('id', $ignore);
		}
		
		if(!empty($ref_id)){
			$suppliers->where('ref_id', $ref_id);
		}
		
		$suppliers = $suppliers->select(
			'ref_id as id',
			'ref_id',
			'invoice_id',
			'supplier_id',
			'product_id',
			DB::raw("SUM(CASE WHEN event = 'stock_added' THEN stock ELSE 0 END) AS stock_added"),
			DB::raw("SUM(CASE WHEN event = 'stock_updated' THEN stock ELSE 0 END) AS stock_updated"),
			DB::raw("SUM(CASE WHEN event = 'supplier_return' THEN stock ELSE 0 END) AS supplier_return"),
			DB::raw("SUM(CASE WHEN event = 'dump' THEN stock ELSE 0 END) AS dump"),
			DB::raw("(
				(SUM(CASE WHEN event = 'stock_added' THEN stock ELSE 0 END)
				+ SUM(CASE WHEN event = 'stock_updated' THEN stock ELSE 0 END))
				- (SUM(CASE WHEN event = 'supplier_return' THEN stock ELSE 0 END)
				+ SUM(CASE WHEN event = 'dump' THEN stock ELSE 0 END))
			) AS net_stock"),
			DB::raw("CONCAT(invoice_id, ':left(',
				(SUM(CASE WHEN event = 'stock_added' THEN stock ELSE 0 END)
				+ SUM(CASE WHEN event = 'stock_updated' THEN stock ELSE 0 END))
				- (SUM(CASE WHEN event = 'supplier_return' THEN stock ELSE 0 END)
				+ SUM(CASE WHEN event = 'dump' THEN stock ELSE 0 END)),
			')') AS label")
		)
		->groupBy('invoice_id', 'ref_id', 'supplier_id', 'product_id')
		->get()
		->map(function ($supplier) {
			// Convert Eloquent model to array (optional if already array)
			$data = $supplier->toArray();

			// Handle child if exists
			if (!empty($data['customer_stocks']) && isset($data['customer_stocks']['net_stock'])) {
				$childNet = (float) $data['customer_stocks']['net_stock'];
				$data['net_stock'] = (float) $data['net_stock'] - $childNet;

				// Update label accordingly
				$data['label'] = "{$data['invoice_id']}:left({$data['net_stock']})";
			}

			return $data;
		})
		->values();
		
		return isset($suppliers[0]) ? $suppliers[0] : [];
	}
	
	public static function _productsWithInvoiceStockCustomer($invoice_id, $customer_id, $product_id, $ref_id = "", $ignore = []){
		$records = self::where('product_id', $product_id)
		->where('is_archived', 0)
		->where('customer_id', $customer_id)
		->where('invoice_id', $invoice_id)
		->where('type', 'customer');
		
		if(!empty($ref_id)){
			$records->where('ref_id', $ref_id);
		}
		
		if(!empty($ignore)){
			$records->whereNotIn('id', $ignore);
		}
		
		$records = $records->select(
			'ref_id as id',
			'invoice_id',
			'customer_id',
			'product_id',
			'ref_id',
			'supplier_invoice_product_id',
			'supplier_invoice_id',
			DB::raw("SUM(CASE WHEN event = 'stock_consumed' THEN stock ELSE 0 END) AS total_consumed"),
			DB::raw("SUM(CASE WHEN event = 'customer_return' THEN stock ELSE 0 END) AS total_returned"),
			DB::raw("(
				SUM(CASE WHEN event = 'stock_consumed' THEN stock ELSE 0 END)
				- SUM(CASE WHEN event = 'customer_return' THEN stock ELSE 0 END)
			) AS net_stock"),
			DB::raw("CONCAT('Invoice #', invoice_id, '  —  ',
				SUM(CASE WHEN event = 'stock_consumed' THEN stock ELSE 0 END)
				- SUM(CASE WHEN event = 'customer_return' THEN stock ELSE 0 END),
			' remaining') AS label")
		)
		->groupBy('invoice_id', 'supplier_invoice_product_id', 'supplier_invoice_id', 'customer_id', 'product_id');
		
		if(!empty($ref_id)){
			$records = $records->first();
		}else{
			$records = $records->get();
		}
		return $records;
	}
	
	public static function getProductsWithInvoiceStockCustomer($customer_id, $product_id, $date, $ref_id = ""){
	
		$invoices = CustomerInvoice::whereDate('created_at', '>=', $date)
			->where('status', 1)
			->whereHas('oneProduct', function ($q) use ($product_id) {
				$q->where('product_id', $product_id);
			})
			->pluck('id');
		
		if(sizeof($invoices) <= 0){
			return [];
		}
		
		$records = self::where('product_id', $product_id)
		->where('is_archived', 0)
		->where('customer_id', $customer_id)
		->whereIn('invoice_id', $invoices)
		->where('type', 'customer');
		
		if(!empty($ref_id)){
			$records->where('ref_id', $ref_id);
		}
		
		$records = $records->select(
			'ref_id as id',
			'invoice_id',
			'customer_id',
			'product_id',
			'ref_id',
			'supplier_invoice_product_id',
			'supplier_invoice_id',
			DB::raw("SUM(CASE WHEN event = 'stock_consumed' THEN stock ELSE 0 END) AS total_consumed"),
			DB::raw("SUM(CASE WHEN event = 'customer_return' THEN stock ELSE 0 END) AS total_returned"),
			DB::raw("(
				SUM(CASE WHEN event = 'stock_consumed' THEN stock ELSE 0 END)
				- SUM(CASE WHEN event = 'customer_return' THEN stock ELSE 0 END)
			) AS net_stock"),
			DB::raw("CONCAT('Invoice #', invoice_id, '  —  ',
				SUM(CASE WHEN event = 'stock_consumed' THEN stock ELSE 0 END)
				- SUM(CASE WHEN event = 'customer_return' THEN stock ELSE 0 END),
			' remaining') AS label")
		)
		// added ref_id later.
		->groupBy('ref_id', 'invoice_id', 'supplier_invoice_product_id', 'supplier_invoice_id', 'customer_id', 'product_id');
		
		if(!empty($ref_id)){
			$records = $records->first();
		}else{
			$records = $records->get();
		}
		return $records;	
	}
	
	public static function getProductsWithInvoiceStockSupplier($supplier_id, $product_id = "", $date = "", $ref_id = ""){
		$supplier_invoices = SupplierInvoice::whereDate('created_at', $date)
			//->where('status', 1)
			->whereHas('oneProduct', function ($q) use ($product_id) {
				$q->where('product_id', $product_id);
			})
			->pluck('id');
		
		if(sizeof($supplier_invoices) < 0){
			return [];
		}
		//print_r($supplier_invoices); exit;
		$suppliers = self::where('product_id', $product_id)
		->where('is_archived', 0)
		->where('type', 'supplier')
		->with('customerStocks')
		->whereIn('invoice_id',$supplier_invoices)
		->where('supplier_id', $supplier_id);
		
		if(!empty($ref_id)){
			$suppliers->where('ref_id', $ref_id);
		}
		
		$suppliers = $suppliers->select(
			'ref_id as id',
			'ref_id',
			'invoice_id',
			'supplier_id',
			'product_id',
			DB::raw("SUM(CASE WHEN event = 'stock_added' THEN stock ELSE 0 END) AS stock_added"),
			DB::raw("SUM(CASE WHEN event = 'stock_updated' THEN stock ELSE 0 END) AS stock_updated"),
			DB::raw("SUM(CASE WHEN event = 'supplier_return' THEN stock ELSE 0 END) AS supplier_return"),
			DB::raw("SUM(CASE WHEN event = 'dump' THEN stock ELSE 0 END) AS dump"),
			DB::raw("(
				(SUM(CASE WHEN event = 'stock_added' THEN stock ELSE 0 END)
				+ SUM(CASE WHEN event = 'stock_updated' THEN stock ELSE 0 END))
				- (SUM(CASE WHEN event = 'supplier_return' THEN stock ELSE 0 END)
				+ SUM(CASE WHEN event = 'dump' THEN stock ELSE 0 END))
			) AS net_stock"),
			DB::raw("CONCAT(invoice_id, ':left(',
				(SUM(CASE WHEN event = 'stock_added' THEN stock ELSE 0 END)
				+ SUM(CASE WHEN event = 'stock_updated' THEN stock ELSE 0 END))
				- (SUM(CASE WHEN event = 'supplier_return' THEN stock ELSE 0 END)
				+ SUM(CASE WHEN event = 'dump' THEN stock ELSE 0 END)),
			')') AS label")
		)
		->groupBy('invoice_id', 'ref_id', 'supplier_id', 'product_id')
		->get()
		->map(function ($supplier) {
			// Convert Eloquent model to array (optional if already array)
			$data = $supplier->toArray();

			// Handle child if exists
			if (!empty($data['customer_stocks']) && isset($data['customer_stocks']['net_stock'])) {
				$childNet = (float) $data['customer_stocks']['net_stock'];
				$data['net_stock'] = (float) $data['net_stock'] - $childNet;

				// Update label accordingly
				$data['label'] = "{$data['invoice_id']}:left({$data['net_stock']})";
			}

			return $data;
		})
		->values();
		
		return $suppliers;
	}
}
