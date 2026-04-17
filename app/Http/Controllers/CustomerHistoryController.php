<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use DB;
use App\Services\CustomerPayments;

class CustomerHistoryController extends Controller
{
	use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('customer_history.index',[]);
    }
	
	public function customers()
    {
        return $this->successResponse(\App\Models\Customer::getActive());
    }
	
	public function history(Request $request, CustomerPayments $customerPayments){
		try{
			$rules = [
                'currentCustomer' => 'required',
            ];

			$validator = Validator::make($request->all(), $rules);

			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
			$fromDate = $request->fromDate ?: '2000-01-01';
			$toDate = $request->toDate ?: now()->toDateString();
			$option = $request->option ?: 'all';
			$pastBalance = $customerPayments::pastBalance($request->currentCustomer, $fromDate);
			$invoices = $customerPayments::invoicePaymentsHistory($request->currentCustomer, $fromDate, $toDate, $option);
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
