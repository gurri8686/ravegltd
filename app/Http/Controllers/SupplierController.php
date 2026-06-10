<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Validator;
use Response;
use Session;
use DB;
use App\Lib\Response as CustomResponse;
class SupplierController extends Controller
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

          $allData = Supplier::where('is_active',1)->get();
          $selecteddata = 'active';
        }
        elseif(request()->user == 'inactive'){

          $allData = Supplier::where('is_active',0)->get();
              $selecteddata = 'inactive';
        }else{
            $allData = Supplier::all();
              $selecteddata = 'all';
        }

      }else{
        $allData = Supplier::where('is_active',1)->get();
      $selecteddata = 'active';
      }

      //return view('supplier.index',['data'=> $allData,'selecteddata'=> $selecteddata]);
      return view('supplier.index-new',['data'=> $allData,'selecteddata'=> $selecteddata]);
    }
	
	public function list(Request $request){
		$suppliers = Supplier::query();

		if($request->filled('status')){
			if($request->status === 'active'){
				$suppliers->where('is_active', 1);
			} elseif($request->status === 'disabled'){
				$suppliers->where('is_active', 0);
			}
		}

		$data = $suppliers->orderBy('created_at', 'desc')->get();

		$supplierPayments = new \App\Services\SupplierPayments();

		$data->each(function($supplier) use ($supplierPayments) {
			$invoices = $supplierPayments::invoicePaymentsHistory($supplier->id, '2000-01-01', now()->toDateString());

			$validInvoices = collect($invoices)->filter(function($inv) {
				return ($inv['net_amount'] ?? 0) > 0 || ($inv['total_paid'] ?? 0) > 0 || ($inv['balance'] ?? 0) != 0;
			});

			$supplier->total_invoices = $validInvoices->count();
			$supplier->total_purchases = $validInvoices->sum('net_amount');
			$supplier->total_paid = $validInvoices->sum('total_paid');
			$supplier->unpaid_balance = $validInvoices->sum('balance');
			$supplier->unpaid_invoices = $validInvoices->where('balance', '>', 0)->count();
		});

		return $this->successResponse($data);
	}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('supplier.create');
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
                'first_name' => 'required'
            ];
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
            $supplierID='';
            $Alreadyexist = Supplier::where(['name'=>$request->input('first_name')])->first();;
            if($Alreadyexist=='')
            {
                $createData = new Supplier;
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
                $createData->bank_name = $request->input('bank_name');
                $createData->bank_address = $request->input('bank_address');
                $createData->bank_account_name = $request->input('bank_account_name');
                $createData->bank_account_number = $request->input('bank_account_number');
                $createData->sort_code = $request->input('sort_code');
                $createData->remarks = $request->input('remarks');
                $createData->is_active = $is_active;
                $record = $createData->save();
                $insertedId = $createData->id;
                $supplierID = 'S'.(100+$insertedId);
                $createData->supplier_id = $supplierID;
                $recordUpdate = $createData->update();
            }
            else
            {
                return $this->validationErrorResponse(['first_name' => ["That Supplier already exists."]]);
            }
            if (!$record || !$recordUpdate) {
                throw new \Exception("Error to add Module");
            }
            \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully added."]);
            return $this->successResponse(['redirect' => route('management.suppliers.view.index')]);

        } catch (\Exception $ex) {

            return $this->exceptionResponse($ex);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function show(Supplier $supplier)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $UserDetails = Supplier::where(['id'=>$id])->first();
        return view('supplier.edit',['data'=>$UserDetails]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        try {
            $rules = [
                'first_name' => 'required'
            ];
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
            $updateData = Supplier::find($id);
            if($request->input('first_name') == $updateData->name)
            {
                $updateQuery = 1;
            }
            else
            {
                $Alreadyexist = Supplier::where(['name'=>$request->input('first_name')])->first();;
                if($Alreadyexist=='')
                {
                    $updateQuery = 1;
                }
                else
                {
                    return $this->validationErrorResponse(['first_name' => ["That Supplier already exists."]]);
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
                $updateData->bank_name = $request->input('bank_name');
                $updateData->bank_address = $request->input('bank_address');
                $updateData->bank_account_name = $request->input('bank_account_name');
                $updateData->bank_account_number = $request->input('bank_account_number');
                $updateData->sort_code = $request->input('sort_code');
                $updateData->remarks = $request->input('remarks');
                $updateData->is_active = $is_active;

                $record = $updateData->update();
            }
            if (!$record) {
                throw new \Exception("Error to Updated");
            }
            \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully updated."]);
            return $this->successResponse(['redirect' => route('management.suppliers.view.index')]);

        } catch (\Exception $ex) {

            return $this->exceptionResponse($ex);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ID = Supplier::find($id);
        $ID->delete();
        \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully removed."]);
        return redirect()->route('management.suppliers.view.index');
    }
}
