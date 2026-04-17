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

class StockClosingController extends Controller
{
	use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
    }
	
	public function products(Request $request)
    {
		try{
			$rules = [
                'date' => 'required',
            ];
			
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			$date = $request->date;
			$query = Product::where('is_active',1);
			$query->with(['stockClosing' => function($q) use($date){
				$q->whereDate('created_at', $date);
			}]);
			$query->withSum(['supplierProducts as total_purchased'], 'quantity');
			$query->withSum(['customerProducts as total_sold'], 'quantity');
			$query->withSum(['customerReturns as total_customer_returns'], 'stock');
			$query->withSum(['supplierReturns as total_supplier_returns'], 'stock');
			$query = $query->orderBy('name','ASC')->get();

			$query->each(function($product) {
				$purchased = (float)($product->total_purchased ?? 0);
				$sold = (float)($product->total_sold ?? 0);
				$custReturns = (float)($product->total_customer_returns ?? 0);
				$suppReturns = (float)($product->total_supplier_returns ?? 0);
				$product->system_stock = $purchased - $sold + $custReturns - $suppReturns;
			});

			return $this->successResponse($query);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
    }
	
	public function stockManager()
    {
        return view('stock-manager.index');
    }

	public function crud()
    {
        return view('stock-closing.crud');
    }
	
	/**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try{
			$rules = [
                'date' => 'required',
				'product_id' => ['required','integer'],
				'stock' => ['required','integer'],
            ];
			
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			
			$record = StockClosing::whereDate('created_at',$request->date)->where('product_id', $request->product_id)->first();
			
			if(empty($record)){
				$obj = new StockClosing();
				$obj->created_at = $request->date . ' ' . now()->format('H:i:s');
				$obj->updated_at = now();
			}else{
				$obj = $record;
				$obj->updated_at = now();
			}
			$obj->product_id = $request->product_id;
			$obj->stock = $request->stock;
			$obj->save();
			
			return $this->successResponse($obj);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function saveAll(Request $request)
    {
		try{
			$rules = [
                'date' => 'required|date',
				'products' => 'required|array',
				'products.*.id' => 'required|integer',
				'products.*.stock' => 'required|numeric',
            ];
			
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			
			$date = $request->date; // 👈 from frontend
			$now = Carbon::now();
			
			$out = "";
			foreach($request->products as $product){
				$record = StockClosing::whereDate('created_at', $date)->where('product_id',$product['id'])->first();
				if(empty($record)){
					$new = new StockClosing();
					$new->created_at = $date . ' ' . now()->format('H:i:s');
					$new->updated_at = now();
					$new->product_id = $product['id'];
					$new->stock = $product['stock'];
					$new->save();
				}else{
					$record->updated_at = now();
					$record->product_id = $product['id'];
					$record->stock = $product['stock'];
					$record->save();
				}
			}
		
			return $this->successResponse([]);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
    }
	
	public function saveOne(Request $request)
    {
        try{
			$rules = [
                'date' => 'required',
				'product_id' => ['required','integer'],
				'stock' => ['required','integer'],
            ];
			
			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			
			$record = StockClosing::whereDate('created_at',$request->date)->where('product_id', $request->product_id)->first();
			
			if(empty($record)){
				$obj = new StockClosing();
				$obj->created_at = $request->date . ' ' . now()->format('H:i:s');
				$obj->updated_at = now();
			}else{
				$obj = $record;
				$obj->updated_at = now();
			}
			$obj->product_id = $request->product_id;
			$obj->stock = $request->stock;
			$obj->save();
			
			return $this->successResponse($obj);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
