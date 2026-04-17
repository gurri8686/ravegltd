<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;

class ProfitLossController extends Controller
{
    use CustomResponse;
	
	public function index(Request $request){
		return view('profit-loss.index');
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
	
	public function profitLoss(Request $request){
		$rules = [
			//'product_id' => 'required|integer',
			'start_date' => 'required',
			'end_date' => 'required',
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
		  return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));
		
		//echo $product_id.'-'.$start_date.'-'.$end_date; exit;
		
		$records = \App\Models\Product::where('is_active', 1);
		
		if(sizeof($product_id) > 0){
			$records->whereIn('id', $product_id);
		}
		
		$records = $records->with(['supplierProducts' => function($q) use($product_id, $start_date, $end_date){
				$q->select(['supplier_invoice_id', 'supplier_id','product_id','unit_price','sub_total','quantity'])
				->whereDate('created_at', '>=', $start_date)
				->whereDate('created_at', '<=', $end_date);
			}])
			->with(['customerProducts' => function($q) use($product_id, $start_date, $end_date){
				$q->select(['customer_invoice_id', 'customer_id','product_id','unit_price','sub_total','quantity','supplier_invoice_product_id'])
				->with('supplierInvoice')
				->whereDate('created_at', '>=', $start_date)
				->whereDate('created_at', '<=', $end_date);
			}])
			->with(['supplierReturns' => function($q) use($product_id, $start_date, $end_date){
				$q->whereDate('created_at', '>=', $start_date)
					->whereDate('created_at', '<=', $end_date);
			}])
			->with(['Dumps' => function($q) use($product_id, $start_date, $end_date){
				$q->whereDate('created_at', '>=', $start_date)
					->whereDate('created_at', '<=', $end_date);
			}])
			->orderBy('name', 'ASC')
			->get()
			->each(function ($product) {
				$product->supplierProducts->each(function ($sp) {
					$sp->makeHidden(['invoice_title', 'invoice_title_short','customers']);
				});
				$product->customerProducts->each(function ($cp) {
					// supplierInvoice is a single model (not a collection)
					if ($cp->supplierInvoice) {
						$cp->supplierInvoice->makeHidden([
							'invoice_title',
							'invoice_title_short',
							'customers',
						]);
					}
				});
			});
		
		//return $this->successResponse($records);
		
		$data = [];
		$i = 0;
		foreach($records as $record){
			$data[$i]['product'] = $record->name;
			$data[$i]['id'] = $record->id;
			$data[$i]['stock_quantity'] = (sizeof($record->customerProducts) > 0) ? $record->customerProducts->sum('quantity') : 0;
			$data[$i]['avg_purchase'] = (sizeof($record->customerProducts) > 0) ? $record->supplierProducts->avg('unit_price') : 0;
			$data[$i]['avg_sales'] = (sizeof($record->customerProducts) > 0) ? $record->customerProducts->avg('unit_price') : 0;
			$data[$i]['avg_profit'] = round($data[$i]['avg_sales'] - $data[$i]['avg_purchase'],2);
			
			$data[$i]['tot_purchase'] = $data[$i]['stock_quantity'] * $data[$i]['avg_purchase'];
			//$data[$i]['tot_profit'] = 
			//$data[$i]['tot_sales'] = 
			
			$data[$i]['avg_profit_percentage'] = 0;
			if ($data[$i]['avg_purchase'] > 0) {
				$data[$i]['avg_profit_percentage'] = round((($data[$i]['avg_sales'] - $data[$i]['avg_purchase']) / $data[$i]['avg_purchase']) * 100, 2);
			}
			
			$i++;
		}
					
		return $this->successResponse($data);
	}
	
}
