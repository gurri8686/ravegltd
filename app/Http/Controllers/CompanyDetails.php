<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;
use App\Models\CompanyDetailModel;
class CompanyDetails extends Controller
{
    use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $allData = CompanyDetailModel::all();
        return view('CompanyDetails.index',['data'=> $allData]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('CompanyDetails.create');
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
                'company_name' => 'required',
                'mobile' => 'required',
                'email' => 'required'
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
            $companyDetailsID='';
            $Alreadyexist = CompanyDetailModel::where(['company_name'=>$request->input('company_name')])->first();;
            if($Alreadyexist=='')
            {
                $createData = new CompanyDetailModel;
                $createData->company_name = $request->input('company_name');
                $createData->email = $request->input('email');
                $createData->mobile = $request->input('mobile');
                $createData->website = $request->input('website');
                $createData->telephone = $request->input('telephone');
                $createData->fax = $request->input('fax');
                $createData->address1 = $request->input('address1');
                $createData->address2 = $request->input('address2');
                $createData->country = $request->input('country');
                $createData->state = $request->input('state');
                $createData->city = $request->input('city');
                $createData->zipcode = $request->input('zipcode');
                $createData->vat_no = $request->input('vat_number');
                $createData->comp_reg_no = $request->input('comp_reg_no');
                $createData->bank_name = $request->input('bank_name');
                $createData->account_no = $request->input('account_no');
                $createData->ifsc_code = $request->input('ifsc_code');
                $createData->eirl_no = $request->input('eirl_no');
                $createData->remarks = $request->input('remarks');
                $record = $createData->save();
                $insertedId = $createData->id;
                $companyDetailsID = 'CD'.(100+$insertedId);
                $createData->company_id = $companyDetailsID;
                $recordUpdate = $createData->update();
            }
            else
            {
                throw new \Exception("That Company Details already exists.");
            }
            if (!$record || !$recordUpdate) {
                throw new \Exception("Error to add");
            }
            \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully added."]);
            return $this->successResponse(['redirect' => route('CompanyDetails.index')]);

        } catch (\Exception $ex) {

            return $this->exceptionResponse($ex);
        }
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
        $UserDetails = CompanyDetailModel::where(['id'=>$id])->first();
        return view('CompanyDetails.edit',['data'=>$UserDetails]);
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
        try {
            $rules = [
                'company_name' => 'required',
                'mobile' => 'required',
                'email' => 'required'
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }

            $updateQuery = 0;
            $updateData = CompanyDetailModel::find($id);
            if($request->input('company_name') == $updateData->company_name)
            {
                $updateQuery = 1;
            }
            else
            {
                $Alreadyexist = CompanyDetailModel::where(['company_name'=>$request->input('company_name')])->first();;
                if($Alreadyexist=='')
                {
                    $updateQuery = 1;
                }
                else
                {
                    throw new \Exception("That Company already exists.");
                }

            }
            if($updateQuery==1)
            {
                $updateData->company_name = $request->input('company_name');
                $updateData->email = $request->input('email');
                $updateData->mobile = $request->input('mobile');
                $updateData->website = $request->input('website');
                $updateData->telephone = $request->input('telephone');
                $updateData->fax = $request->input('fax');
                $updateData->address1 = $request->input('address1');
                $updateData->address2 = $request->input('address2');
                $updateData->country = $request->input('country');
                $updateData->state = $request->input('state');
                $updateData->city = $request->input('city');
                $updateData->zipcode = $request->input('zipcode');
                $updateData->vat_no = $request->input('vat_number');
                $updateData->comp_reg_no = $request->input('comp_reg_no');
                $updateData->bank_name = $request->input('bank_name');
                $updateData->account_no = $request->input('account_no');
                $updateData->ifsc_code = $request->input('ifsc_code');
                $updateData->eirl_no = $request->input('eirl_no');
                $updateData->remarks = $request->input('remarks');
                $record = $updateData->update();
            }
            if (!$record) {
                throw new \Exception("Error to Updated");
            }
            \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully updated."]);
            return $this->successResponse(['redirect' => route('CompanyDetails.index')]);

        } catch (\Exception $ex) {

            return $this->exceptionResponse($ex);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ID = CompanyDetailModel::find($id);
        $ID->delete();
        \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully removed."]);
        return redirect()->route('CompanyDetails.index');
    }
}
