<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use DB;
use App\Services\SupplierPayments;

class SupplierHistoryController extends Controller
{
	use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('supplier_history.index',[]);
    }
	
	public function suppliers()
    {
        return $this->successResponse(\App\Models\Supplier::getActive());
    }
	
	public function history(Request $request, SupplierPayments $supplierPayments){
		try{
			$rules = [
                'currentSupplier' => 'required',
            ];

			$validator = Validator::make($request->all(), $rules);

			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }

			$fromDate = $request->fromDate ?: '2000-01-01';
			$toDate = $request->toDate ?: now()->toDateString();

			$pastBalance = $supplierPayments::pastBalance($request->currentSupplier, $fromDate);
			$invoices = $supplierPayments::invoicePaymentsHistory($request->currentSupplier, $fromDate, $toDate);
			return $this->successResponse(['past_balance' => $pastBalance, 'invoices' => $invoices]);

		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}
	
	public function email(Request $request, CustomerPayments $customerPayments){
	
	}
	
	public function print(Request $request, CustomerPayments $customerPayments){
	
	}
	
	public function statement(Request $request, CustomerPayments $customerPayments){
	
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
