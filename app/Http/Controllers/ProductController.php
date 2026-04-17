<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Validator;
use Response;
use Session;
use DB;
use Auth;
use App\Lib\Response as CustomResponse;

class ProductController extends Controller
{
    use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $allData = Product::all();
        //return view('product.index',['data'=> $allData]);
        return view('product.index-new',['data' => $allData]);
    }
	
	public function list(Request $request){
		return $this->successResponse(Product::all());
	}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('product.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{
            $rules = [
                'name' => 'required'
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
            $is_active = 1;
            $ProductID='';
            $Alreadyexist = Product::where('name',$request->input('name'))->first();
            if(!$Alreadyexist)
            {
                $createData = new Product;
                $createData->name = $request->input('name');
                $createData->selling_price = $request->input('selling_price');
                $createData->tax_vat = $request->input('tax_vat');
                $createData->unit_weight = $request->input('unit_weight');
                $createData->description = $request->input('description');
                $createData->is_active = $is_active;
                $createData->user_id = Auth::user()->id;
                $record = $createData->save();
                $insertedId = $createData->id;
                $ProductID = 'P'.(100+$insertedId);
                $createData->product_id = $ProductID;
                $recordUpdate = $createData->update();
            }
            else
            {
                throw new \Exception("That Product name already exists.");
            }
            if (!$record || !$recordUpdate) {
                throw new \Exception("Error to add");
            }
            \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully added."]);
            return $this->successResponse(['redirect' => route('management.products.view.index')]);

        } catch (\Exception $ex) {

            return $this->exceptionResponse($ex);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $ProductDetails = Product::where(['id'=>$id])->first();
        return view('product.edit',['data'=>$ProductDetails]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $rules = [
                'name' => 'required'
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
            $is_active = $request->input('is_active') == '1' ? 1 : 0;
            $updateQuery = 0;
            $updateData = Product::find($id);
            if($request->input('name') == $updateData->name)
            {
                $updateQuery = 1;
            }else{
                $Alreadyexist = Product::where('name',$request->input('name'))->first();
                if(!$Alreadyexist)
                {
                    $updateQuery = 1;
                }else
                {
                    throw new \Exception("That Product name already exists.");
                }
            }
            if($updateQuery==1)
            {
                $updateData->name = $request->input('name');
                $updateData->selling_price = $request->input('selling_price');
                $updateData->tax_vat = $request->input('tax_vat');
                $updateData->unit_weight = $request->input('unit_weight');
                $updateData->description = $request->input('description');
                $updateData->is_active = $is_active;
                $updateData->user_id = Auth::user()->id;
                $record = $updateData->update();
            }
            if (!$record) {
                throw new \Exception("Error to Updated");
            }
            \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully updated."]);
            return $this->successResponse(['redirect' => route('management.products.view.index')]);

        } catch (\Exception $ex) {

            return $this->exceptionResponse($ex);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
