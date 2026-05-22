<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\ProductHistory;

class ProductHistoryController extends Controller
{
    use CustomResponse;
	
	public function index(Request $request){
		return view('product-history.index');
	}
	
	public function customers(Request $request){
		return $this->successResponse(\App\Models\Customer::getActive());
	}
	
	public function suppliers(Request $request){
		return $this->successResponse(\App\Models\Supplier::getActive());
	}
	
	public function products(Request $request){
		return $this->successResponse(\App\Models\Product::getActive());
	}
	
	// --------------
	/**
	 * Paginate a history query and attach grand totals computed over the FULL filtered set.
	 */
	private function buildHistoryResponse($base, Request $request, $qtyCol, $priceCol, $selectCols, $withRelations, $hidden = [], $searchSpec = [])
	{
		// Optional search — matches party name, product name and invoice number.
		$search = trim((string) $request->input('search', ''));
		if ($search !== '' && !empty($searchSpec)) {
			$base->where(function($q) use ($search, $searchSpec) {
				foreach (($searchSpec['columns'] ?? []) as $col) {
					$q->orWhere($col, 'like', '%'.$search.'%');
				}
				foreach (($searchSpec['relations'] ?? []) as $rel => $col) {
					$q->orWhereHas($rel, function($r) use ($col, $search) {
						$r->where($col, 'like', '%'.$search.'%');
					});
				}
			});
		}

		$perPage = (int) $request->input('per_page', 10);
		if ($perPage < 1) { $perPage = 10; }
		$page = (int) $request->input('page', 1);
		if ($page < 1) { $page = 1; }

		$totalCount  = (clone $base)->count();
		$totalQty    = (float) (clone $base)->sum($qtyCol);
		$avgPrice    = (float) (clone $base)->avg($priceCol);
		$totalAmount = (float) (clone $base)->sum(DB::raw($qtyCol.' * '.$priceCol));

		$query = (clone $base)->with($withRelations);
		if (!empty($selectCols)) {
			$query->select($selectCols);
		}
		$records = $query->orderBy('created_at', 'desc')
			->forPage($page, $perPage)
			->get();
		if (!empty($hidden)) {
			$records->makeHidden($hidden);
		}

		return $this->successResponse([
			'data'        => $records,
			'total_count' => $totalCount,
			'page'        => $page,
			'per_page'    => $perPage,
			'totals'      => [
				'qty'   => $totalQty,
				'price' => $avgPrice,
				'total' => $totalAmount,
			],
		]);
	}

	public function supplierInvoices(Request $request){
		$validator = Validator::make($request->all(), ['product_id' => 'required|integer']);
		if ($validator->fails()) {
		  return $this->validationErrorResponse($validator->errors()->messages());
		}

		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));

		$base = \App\Models\SupplierInvoiceProduct
				::where('product_id', $product_id)
				->where('is_archive', 0)
				->whereDate('created_at', '>=', $start_date)
				->whereDate('created_at', '<=', $end_date);

		if(!empty($supplier_id)){
			$base->where('supplier_id', $supplier_id);
		}

		return $this->buildHistoryResponse($base, $request, 'quantity', 'unit_price',
			['supplier_invoice_id','supplier_id','product_id','quantity','unit_price','sub_total','created_at','updated_at'],
			[
				'product'  => function($q){ $q->select(['name','id','product_id']); },
				'supplier' => function($q){ $q->select(['name','email','id','supplier_id']); },
				'invoice',
			],
			['invoice_title','invoice_title_short','customers'],
			['columns' => ['supplier_invoice_id'], 'relations' => ['product' => 'name', 'supplier' => 'name']]
		);
	}

	public function supplierReturns(Request $request){
		$validator = Validator::make($request->all(), ['product_id' => 'required|integer']);
		if ($validator->fails()) {
		  return $this->validationErrorResponse($validator->errors()->messages());
		}

		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));

		$base = \App\Models\StockProduct::where('is_archived',0)
			->where('product_id', $product_id)
			->where('type', 'supplier')
			->where('event', 'supplier_return')
			->whereDate('created_at', '>=', $start_date)
			->whereDate('created_at', '<=', $end_date);

		if(!empty($supplier_id)){
			$base->where('supplier_id', $supplier_id);
		}

		return $this->buildHistoryResponse($base, $request, 'stock', 'price', [],
			[
				'supplier' => function($q){ $q->select(['name','email','id','supplier_id']); },
				'product'  => function($q){ $q->select(['name','id','product_id']); },
			],
			[],
			['columns' => ['invoice_id'], 'relations' => ['product' => 'name', 'supplier' => 'name']]
		);
	}

	public function sales(Request $request){
		$validator = Validator::make($request->all(), ['product_id' => 'required|integer']);
		if ($validator->fails()) {
		  return $this->validationErrorResponse($validator->errors()->messages());
		}

		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));

		$base = \App\Models\CustomerInvoiceProduct
			::where('is_archive',0)
			->where('product_id', $product_id)
			->whereDate('created_at', '>=', $start_date)
			->whereDate('created_at', '<=', $end_date);

		if(!empty($supplier_id)){
			$base->where('supplier_id', $supplier_id);
		}

		if(!empty($customer_id)){
			$base->where('customer_id', $customer_id);
		}

		return $this->buildHistoryResponse($base, $request, 'quantity', 'unit_price',
			['supplier_invoice_id','customer_invoice_id','customer_id','supplier_id','product_id','quantity','unit_price','sub_total','created_at','updated_at'],
			[
				'supplier' => function($q){ $q->select(['name','email','id','supplier_id']); },
				'product'  => function($q){ $q->select(['name','id','product_id']); },
				'customer' => function($q){ $q->select(['name','id','email']); },
				'customerInvoice',
			],
			[],
			['columns' => ['customer_invoice_id'], 'relations' => ['product' => 'name', 'customer' => 'name']]
		);
	}

	public function customerReturns(Request $request){
		$validator = Validator::make($request->all(), ['product_id' => 'required|integer']);
		if ($validator->fails()) {
		  return $this->validationErrorResponse($validator->errors()->messages());
		}

		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));

		$base = \App\Models\StockProduct
			::where('is_archived',0)
			->where('type', 'customer')
			->where('event', 'customer_return')
			->where('product_id', $product_id)
			->whereDate('created_at', '>=', $start_date)
			->whereDate('created_at', '<=', $end_date);

		if(!empty($customer_id)){
			$base->where('customer_id', $customer_id);
		}

		return $this->buildHistoryResponse($base, $request, 'stock', 'price', [],
			[
				'product'  => function($q){ $q->select(['name','id','product_id']); },
				'customer' => function($q){ $q->select(['name','id','email']); },
				'customerInvoice',
			],
			[],
			['columns' => ['invoice_id'], 'relations' => ['product' => 'name', 'customer' => 'name']]
		);
	}

	public function dumps(Request $request){
		$validator = Validator::make($request->all(), ['product_id' => 'required|integer']);
		if ($validator->fails()) {
		  return $this->validationErrorResponse($validator->errors()->messages());
		}

		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));

		$base = \App\Models\StockProduct
			::where('is_archived',0)
			->where('type', 'supplier')
			->where('event', 'dump')
			->where('product_id', $product_id)
			->whereDate('created_at', '>=', $start_date)
			->whereDate('created_at', '<=', $end_date);

		if(!empty($supplier_id)){
			$base->where('supplier_id', $supplier_id);
		}

		return $this->buildHistoryResponse($base, $request, 'stock', 'price', [],
			[
				'product'  => function($q){ $q->select(['name','id','product_id']); },
				'supplier' => function($q){ $q->select(['name','id']); },
				'supplierInvoice',
			],
			[],
			['columns' => ['invoice_id'], 'relations' => ['product' => 'name', 'supplier' => 'name']]
		);
	}
	
	
}
