<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Validator;
use Response;
use Session;
use DB;
use App\Lib\Response as CustomResponse;
class CustomerController extends Controller
{
    use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {


            if (request()->has('user')) {

              if(request()->user == 'active'){

                $allData = Customer::where('is_active',1)->get();
                $selecteddata = 'active';
              }
              elseif(request()->user == 'inactive'){

                  $allData = Customer::where('is_active',0)->get();
                  $selecteddata = 'inactive';
              }else{
                  $allData = Customer::all();
                    $selecteddata = 'all';
              }

            }else{
              $allData = Customer::where('is_active',1)->get();
            $selecteddata = 'active';
            }

        //return view('customer.index',['data'=> $allData,'selecteddata'=> $selecteddata]);
        return view('customer.index-new',['data'=> $allData,'selecteddata'=> $selecteddata]);
    }
	
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //$roleDetails = Customer::all();
        return view('customer.create');
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
                'first_name' => 'required|min:3|max:30|regex:/(^[A-Za-z ]+$)+/'
            ];
            if($request->zipcode){
              $rules['zipcode'] = 'max:12|regex:/(^[A-Za-z0-9 ]+$)+/';
            }
            if($request->email){

              $rules['email'] = 'required|email:filter|regex:/^[A-z0-9!@£$%^&*().\'~*_+\-]+$/';
            }

            if($request->country){
              $rules['country'] = 'max:12|regex:/(^[A-Za-z0-9 ]+$)+/';
            }
            if($request->city){
              $rules['city'] = 'max:12|regex:/(^[A-Za-z0-9 ]+$)+/';
            }
            if($request->state){
              $rules['state'] = 'max:12|regex:/(^[A-Za-z0-9 ]+$)+/';
            }
            if($request->vat_number){
              $rules['vat_number'] = 'max:12|regex:/(^[A-Za-z0-9 ]+$)+/';
            }
            if($request->currency){
              $rules['currency'] = 'max:12|regex:/(^[A-Za-z0-9 ]+$)+/';
            }
            if($request->credit_limit){
              $rules['credit_limit'] = 'max:12|regex:/(^[A-Za-z0-9 ]+$)+/';
            }
            if($request->terms){
              $rules['terms'] = 'max:25';
            }




            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
            if($request->input('is_active')=='on')
            {
                    $is_active=1;
            }else{
                    $is_active=0;
            }
            $customerID='';
            $Alreadyexist = Customer::where(['name'=>$request->input('first_name')])->first();;
            if($Alreadyexist=='')
            {
                $createData = new Customer;
                $createData->name = $request->input('first_name');
                $createData->email = $request->input('email');
                $createData->mobile = $request->input('mobile');
                $createData->website = $request->input('website');
                $createData->address1 = $request->input('address1');
                $createData->address2 = $request->input('address2');
                $createData->country = $request->input('country');
                $createData->state = $request->input('state');
                $createData->city = $request->input('city');
                $createData->zipcode = $request->input('zipcode');
                $createData->vat_number = $request->input('vat_number');
                $createData->currency = $request->input('currency');
                $createData->credit_limit = $request->input('credit_limit');
                $createData->terms = $request->input('terms');
                $createData->remarks = $request->input('remarks');
                $createData->is_active = $is_active;
                $record = $createData->save();
                $insertedId = $createData->id;
                $customerID = 'C'.(100+$insertedId);
                $createData->customer_id = $customerID;
                $recordUpdate = $createData->update();
            }
            else
            {
                throw new \Exception("That Customer already exists.");
            }
            if (!$record || !$recordUpdate) {
                throw new \Exception("Error to add Module");
            }
            \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully added."]);
            return $this->successResponse(['redirect' => route('management.customers.view.index')]);

        } catch (\Exception $ex) {

            return $this->exceptionResponse($ex);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $UserDetails = Customer::where(['id'=>$id])->first();
        return view('customer.edit',['data'=>$UserDetails]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $rules = [
                'first_name' => 'required'
            ];
            if($request->zipcode){
              $rules['zipcode'] = 'max:12|regex:/(^[A-Za-z0-9 ]+$)+/';
            }
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
            if($request->input('is_active')=='on')
            {
                    $is_active=1;
            }else{
                $is_active=0;
            }
            $updateQuery = 0;
            $updateData = Customer::find($id);
            if($request->input('first_name') == $updateData->name)
            {
                $updateQuery = 1;
            }
            else
            {
                $Alreadyexist = Customer::where(['name'=>$request->input('first_name')])->first();;
                if($Alreadyexist=='')
                {
                    $updateQuery = 1;
                }
                else
                {
                    throw new \Exception("That Customer already exists.");
                }

            }
            if($updateQuery==1)
            {
                $updateData->name = $request->input('first_name');
                $updateData->email = $request->input('email');
                $updateData->mobile = $request->input('mobile');
                $updateData->website = $request->input('website');
                $updateData->address1 = $request->input('address1');
                $updateData->address2 = $request->input('address2');
                $updateData->country = $request->input('country');
                $updateData->state = $request->input('state');
                $updateData->city = $request->input('city');
                $updateData->zipcode = $request->input('zipcode');
                $updateData->vat_number = $request->input('vat_number');
                $updateData->currency = $request->input('currency');
                $updateData->credit_limit = $request->input('credit_limit');
                $updateData->terms = $request->input('terms');
                $updateData->remarks = $request->input('remarks');
                $updateData->is_active = $is_active;
                $record = $updateData->update();
            }
            if (!$record) {
                throw new \Exception("Error to Updated Module");
            }
            \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully updated."]);
            return $this->successResponse(['redirect' => route('management.customers.view.index')]);

        } catch (\Exception $ex) {

            return $this->exceptionResponse($ex);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ID = Customer::find($id);
        $ID->delete();
        \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully removed."]);
        return redirect()->route('management.customers.view.index');
    }
	
	public function list(Request $request){
		$customers = Customer::query();

		if($request->filled('status')){
			if($request->status === 'active'){
				$customers->where('is_active', 1);
			} elseif($request->status === 'disabled'){
				$customers->where('is_active', 0);
			}
		}

		if($request->filled('start_date')){
			$customers->whereDate('created_at', '>=', $request->start_date);
		}
		if($request->filled('end_date')){
			$customers->whereDate('created_at', '<=', $request->end_date);
		}

		$data = $customers->orderBy('created_at', 'desc')->get();

		$customerPayments = new \App\Services\CustomerPayments();

		$data->each(function($customer) use ($customerPayments) {
			$invoices = $customerPayments::invoicePaymentsHistory($customer->id, '2000-01-01', now()->toDateString(), 'all');

			$validInvoices = collect($invoices)->filter(function($inv) {
				return ($inv['net_amount'] ?? 0) > 0 || ($inv['total_paid'] ?? 0) > 0 || ($inv['balance'] ?? 0) != 0;
			});

			$customer->total_invoices = $validInvoices->count();
			$customer->total_sales = $validInvoices->sum('net_amount');
			$customer->total_paid = $validInvoices->sum('total_paid');
			$customer->unpaid_balance = $validInvoices->sum('balance');
			$customer->unpaid_invoices = $validInvoices->where('balance', '>', 0)->count();
		});

		return $this->successResponse($data);
	}
}
