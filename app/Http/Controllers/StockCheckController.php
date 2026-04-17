<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use DB;
use App\Lib\Response as CustomResponse;
use Carbon\Carbon;
use App\Models\StockClosing;
use App\Models\StockProduct;
use App\Models\Product;

class StockCheckController extends Controller
{
	use CustomResponse;
	
	protected $ns_identifier = ['stock_added','stock_updated'];
	
	protected $sales_identifier = ['stock_consumed'];
	
	protected $cr_identifier = ['customer_return'];
	
	protected $sr_identifier = ['supplier_return'];
	
	protected $dump_identifier = ['dump'];
	
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('stock_check.index');
    }
	
	public function list(Request $request){
		try{
			$rules = [
                'date' => 'required',
				'mode' => ['required','string'],
				'to_date' => ['required'],
            ];
			
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			
			$opening_stock = Product::where('is_active', 1);
			$date_ns = $request->date;
			$to_date = $request->to_date;
			$date = \Carbon\Carbon::parse($request->date)->subDay()->toDateString();
			
			$opening_stock = $opening_stock->with(['stockClosing' => function($q) use($date){
				$q->whereDate('created_at', $date);
			}])
			->with(['stockProducts' => function($q) use($date_ns,$to_date){
				$q->whereDate('updated_at', '>=', $date_ns)
					->where('is_archived',0)
					->whereDate('updated_at', '<=', $to_date);
			}]);
			$opening_stock = $opening_stock->orderBy('name','ASC')->get();
			
			//print_r($opening_stock->toArray()); exit;
			
			$closing_stock = Product::where('is_active', 1)->with(['stockClosing' => function($q) use($to_date){
				$q->whereDate('created_at', $to_date);
			}])->get();
			$closing_stock_data = $closing_stock->mapWithKeys(function ($item) {
				return [
					$item->id => optional($item->stockClosing)->stock ?? 0,
				];
			})->toArray();
			
			//echo $to_date; print_r($closing_stock_data); exit;
			
			
			
			$data = [];
			$i = 0;
			
			foreach($opening_stock as $product){
				$data[$i]['product_id'] = $product->id;
				$data[$i]['product_name'] = $product->name;
				$data[$i]['os'] = $product->stockClosing->stock;
				
				if( sizeof($product->stockProducts) > 0 ){
					$ns_sum = 0;
					foreach($product->stockProducts as $p){
						if(in_array($p->event, $this->ns_identifier)){
							$data[$i]['ns'][] = $p->stock;
						}
					}
				}else{
					$data[$i]['ns'] = [0];
					$data[$i]['ns_sum'] = 0;
				}
				
				if( sizeof($product->stockProducts) > 0 ){
					foreach($product->stockProducts as $p){
						if(in_array($p->event, $this->sales_identifier)){
							$data[$i]['sales'][] = $p->stock;
						}
					}
				}else{
					$data[$i]['sales'] = 0;
				}
				
				if( sizeof($product->stockProducts) > 0 ){
					foreach($product->stockProducts as $p){
						if(in_array($p->event, $this->cr_identifier)){
							$data[$i]['crtn'][] = $p->stock;
						}
					}
				}else{
					$data[$i]['crtn'] = 0;
				}				
				
				if( sizeof($product->stockProducts) > 0 ){
					foreach($product->stockProducts as $p){
						if(in_array($p->event, $this->dump_identifier)){
							$data[$i]['dmps'][] = $p->stock;
						}
					}
				}else{
					$data[$i]['dmps'] = 0;
				}
				
				if( sizeof($product->stockProducts) > 0 ){
					foreach($product->stockProducts as $p){
						if(in_array($p->event, $this->sr_identifier)){
							$data[$i]['srtn'][] = $p->stock;
						}
					}
				}else{
					$data[$i]['srtn'] = 0;
				}
				
				$data[$i]['stock'] = 0;
				$data[$i]['cl_stock'] = (int)$closing_stock_data[$product->id];
				$data[$i]['result'] = 0;
				
				$i++;
			}
			
			return $this->successResponse($data);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){}
	
    public function openingStock(Request $request){
		$rules = [
			'date' => 'required',
			//'mode' => ['required','string'],
			'to_date' => ['required'],
			'product_id' => 'required',
		];
		
		$date_ns = $request->date;
		$to_date = $request->to_date;
		$date = \Carbon\Carbon::parse($request->date)->subDay()->toDateString();
		$product_id = $request->product_id;
		
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}
		$opening_stock = Product::where('is_active', 1)
		->whereHas('stockClosing', function ($q) use ($date,$product_id) {
			$q->whereDate('created_at', $date)
				->where('product_id', $product_id)
			  ->where('stock', '>', 0);
		})
		->with(['stockClosing' => function ($q) use ($date,$product_id) {
			$q->whereDate('created_at', $date)
			->where('product_id', $product_id)
			  ->where('stock', '>', 0);
		}])
		->with(['stockProducts' => function ($q) use ($date_ns, $to_date) {
			$q->whereDate('updated_at', '>=', $date_ns)
			  ->whereDate('updated_at', '<=', $to_date)
			  ->where('is_archived', 0);
		}])
		->orderBy('name', 'ASC')
		->get();
		
		return $this->successResponse($opening_stock);
	}
	
    public function newStock(Request $request){
		$rules = [
			'date' => 'required',
			//'mode' => ['required','string'],
			'to_date' => ['required'],
			'product_id' => 'required',
		];
		
		$date_ns = $request->date;
		$to_date = $request->to_date;
		$date = \Carbon\Carbon::parse($request->date)->subDay()->toDateString();
		
		$records = StockProduct::whereDate('updated_at', '>=', $date_ns)
			->whereDate('updated_at', '<=', $to_date)
			->where('type', 'supplier')
			->where('is_archived', 0)
			->where('product_id', $request->product_id)
			->where(function ($q) {
				$q->where('event', 'stock_added')
				  ->orWhere('event', 'stock_updated');
			})
			->with('supplier')
			->with('product')
			->get();
			  
		return $this->successResponse($records);
	}
	
    public function sales(Request $request){
		$rules = [
			'date' => 'required',
			'product_id' => 'required',
			//'mode' => ['required','string'],
			'to_date' => ['required'],
		];
		
		$date_ns = $request->date;
		$to_date = $request->to_date;
		$date = \Carbon\Carbon::parse($request->date)->subDay()->toDateString();
		
		$records = StockProduct::whereDate('updated_at', '>=', $date_ns)
			->whereDate('updated_at', '<=', $to_date)
			->where('type', 'customer')
			->where('product_id', $request->product_id)
			->where('is_archived', 0)
			->where(function ($q) {
				$q->where('event', 'stock_consumed')
				  //->orWhere('event', 'stock_updated')
				  ;
			})
			->with('supplier')
			->with('customer')
			->with('product')
			->get();
			
		return $this->successResponse($records);
	}
	
    public function customerReturn(Request $request){
		$rules = [
			'date' => 'required',
			//'mode' => ['required','string'],
			'to_date' => ['required'],
			'product_id' => 'required',
		];
		
		$date_ns = $request->date;
		$to_date = $request->to_date;
		$date = \Carbon\Carbon::parse($request->date)->subDay()->toDateString();
		
		$records = StockProduct::whereDate('updated_at', '>=', $date_ns)
			->whereDate('updated_at', '<=', $to_date)
			->where('type', 'customer')
			->where('is_archived', 0)
			->where('product_id', $request->product_id)
			->where(function ($q) {
				$q->where('event', 'customer_return')
				  //->orWhere('event', 'stock_updated')
				  ;
			})
			->with('supplier')
			->with('product')
			->with('customer')
			->get();
			  
		return $this->successResponse($records);
	}
	
    public function dumps(Request $request){
		$rules = [
			'date' => 'required',
			//'mode' => ['required','string'],
			'to_date' => ['required'],
			'product_id' => 'required',
		];
		
		$date_ns = $request->date;
		$to_date = $request->to_date;
		$date = \Carbon\Carbon::parse($request->date)->subDay()->toDateString();
		
		$records = StockProduct::whereDate('updated_at', '>=', $date_ns)
			->whereDate('updated_at', '<=', $to_date)
			->where('type', 'supplier')
			->where('is_archived', 0)
			->where('product_id', $request->product_id)
			->where(function ($q) {
				$q->where('event', 'dump')
				  //->orWhere('event', 'stock_updated')
				  ;
			})
			->with('supplier')
			->with('product')
			->get();
			  
		return $this->successResponse($records);
	}
	
    public function supplierReturn(Request $request){
		$rules = [
			'date' => 'required',
			//'mode' => ['required','string'],
			'to_date' => ['required'],
			'product_id' => 'required',
		];
		
		$date_ns = $request->date;
		$to_date = $request->to_date;
		$date = \Carbon\Carbon::parse($request->date)->subDay()->toDateString();
		
		$records = StockProduct::whereDate('updated_at', '>=', $date_ns)
			->whereDate('updated_at', '<=', $to_date)
			->where('type', 'supplier')
			->where('is_archived', 0)
			->where('product_id', $request->product_id)
			->where(function ($q) {
				$q->where('event', 'supplier_return')
				  //->orWhere('event', 'stock_updated')
				  ;
			})
			->with('supplier')
			->with('product')
			->get();
			  
		return $this->successResponse($records);
	}
	
    public function closingStock(Request $request){
		$rules = [
			'date' => 'required',
			//'mode' => ['required','string'],
			'to_date' => ['required'],
			'product_id' => 'required',
		];
		
		$date_ns = $request->date;
		$to_date = $request->to_date;
		$date = \Carbon\Carbon::parse($request->date)->subDay()->toDateString();
		$product_id = $request->product_id;
		
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}
		
		$records = Product::where('is_active', 1)
		->whereHas('stockClosing', function ($q) use ($to_date, $product_id) {
			$q->whereDate('created_at', $to_date)
			->where('product_id', $product_id)
			->where('stock', '>', 0);
		})
		->with(['stockClosing' => function ($q) use ($to_date, $product_id) {
			$q->whereDate('created_at', $to_date)
			->where('product_id', $product_id)
			->where('stock', '>', 0);
		}])
		->orderBy('name', 'ASC')
		->get();
		
		return $this->successResponse($records);
	}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
