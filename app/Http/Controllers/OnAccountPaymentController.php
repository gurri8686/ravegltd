<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CustomerPayments;
use Validator;
use App\Lib\Response as CustomResponse;

class OnAccountPaymentController extends Controller
{
	use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, CustomerPayments $customerPayments)
    {	
		$rules = [
			'customer_id' => ['required','integer']
		];
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			if ($validator->fails()) {
				return $this->errorResponse(json_encode($errors));
			}
		}
        return $this->successResponse($customerPayments::onAccountPaymentList($request->customer_id));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('on_account_payment.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, CustomerPayments $customerPayments)
    {
        $rules = [
			'payment_mode' => ['required', 'integer'],
			'amount' => ['required', 'integer'],
			'date' => ['required','date'],
			'customer_id' => ['required','integer']
		];
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			if ($validator->fails()) {
				return $this->errorResponse(json_encode($errors));
			}
		}
		
		$r = $customerPayments::onAccountPayment($request->all());
		return $this->successResponse($r);
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
