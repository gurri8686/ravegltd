<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Response;
use Session;
use DB;
use Auth;

use App\Lib\Response as CustomResponse;
use App\Models\CompanyDetailModel;
use App\Models\ModelHasRole;
use App\Models\RoleModel;

use Redirect;

class CompanyDetailController extends Controller
{
    use CustomResponse;

    public function index(){
        $allData = CompanyDetailModel::all();
        return view('company_details.index',['data'=> $allData]);
    }

    public function edit(){
		//print_r(config('filesystems')); exit;

        $id = \Auth::user()->id;
        $checkadmin = checkadmin($id);
        if(!$checkadmin){
          \Session::flash('redirect', ['type' => 'success', 'message' => "You have No Access."]);
          // return $this->successResponse(['redirect' => route('dashboard.view.index')]);
          return Redirect::to('dashboard/view/index');
        }

        $companyDetails=[];
        $companyDetails = CompanyDetailModel::first();
        // $companyDetails = CompanyDetailModel::where(['id'=>Auth::user()->id])->first();
        // $companyDetails = CompanyDetailModel::where(['id'=> 1])->first();
        return view('company_details.edit',['data'=>$companyDetails]);
        // return view('company_details.create');
    }

    public function store(Request $request){
        // $companyDetails = CompanyDetailModel::where(['id'=>Auth::user()->id])->first();
        // return view('company_details.edit',['data'=>$companyDetails]);
    }

    public function update(Request $request){

      $rules = [
          'company_name' => 'required',
          'mobile' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
          'telephone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
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



      $existcompany = CompanyDetailModel::first();

      // echo "<pre>";print_r($existcompany);die;
      if($existcompany){
        
        $existcompany->company_name = $request->input('company_name');
        $existcompany->email = $request->input('email');
        $existcompany->mobile = $request->input('mobile');
        $existcompany->website = $request->input('website');
        $existcompany->telephone = $request->input('telephone');
         // $existcompany->fax = $request->input('fax');
        $existcompany->address1 = $request->input('address1');
        $existcompany->address2 = $request->input('address2');
        $existcompany->country = $request->input('country');
        $existcompany->state = $request->input('state');
        $existcompany->city = $request->input('city');
        $existcompany->zipcode = $request->input('zipcode');
         // $existcompany->vat_no = $request->input('vat_number');
         // $existcompany->comp_reg_no = $request->input('comp_reg_no');
         // $existcompany->bank_name = $request->input('bank_name');
         // $existcompany->account_no = $request->input('account_no');
         // $existcompany->ifsc_code = $request->input('ifsc_code');
         // $existcompany->eirl_no = $request->input('eirl_no');
        $existcompany->remarks = $request->input('remarks');

        if ($request->hasFile('website_logo')) {
          //$image_website_logo = 'website_logo_'.time().'.'.$request->website_logo->extension();
          //$request->website_logo->move(public_path('img'), $image_website_logo);
          //$existcompany->website_logo = $image_website_logo;
		  $wfile = $request->file('website_logo');
          $wfilename = 'website_logo_' . time() . '.' . $wfile->getClientOriginalExtension();
          // Save to storage/app/public/invoices
          $wpath = $wfile->storeAs('website_logo', $wfilename, 'public');
          // Save filename/path in database
          $existcompany->website_logo = $wpath;
        }

        if ($request->hasFile('invoice_logo')) {
          /*$image_invoice_logo = 'invoice_logo_'.time().'.'.$request->invoice_logo->extension();
          $request->invoice_logo->move(public_path('img'), $image_invoice_logo);
          $existcompany->invoice_logo = $image_invoice_logo;*/
          $file = $request->file('invoice_logo');
          $filename = 'invoice_logo_' . time() . '.' . $file->getClientOriginalExtension();
          // Save to storage/app/public/invoices
          $path = $file->storeAs('invoice_logo', $filename, 'public');
          // Save filename/path in database
          $existcompany->invoice_logo = $path;
        }
        $existcompany->update();
        \Session::flash('redirect', ['type' => 'success', 'message' => "Record Updated Successfully."]);


      }else{

        $createData = new CompanyDetailModel;
        $createData->company_name = $request->input('company_name');
        $createData->email = $request->input('email');
        $createData->mobile = $request->input('mobile');
        $createData->website = $request->input('website');
        $createData->telephone = $request->input('telephone');
        // $createData->fax = $request->input('fax');
        $createData->address1 = $request->input('address1');
        $createData->address2 = $request->input('address2');
        $createData->country = $request->input('country');
        $createData->state = $request->input('state');
        $createData->city = $request->input('city');
        $createData->zipcode = $request->input('zipcode');
        // $createData->vat_no = $request->input('vat_number');
        // $createData->comp_reg_no = $request->input('comp_reg_no');
        // $createData->bank_name = $request->input('bank_name');
        // $createData->account_no = $request->input('account_no');
        // $createData->ifsc_code = $request->input('ifsc_code');
        // $createData->eirl_no = $request->input('eirl_no');
        $createData->remarks = $request->input('remarks');

        if ($request->hasFile('website_logo')) {
          //$image_website_logo = 'website_logo'.time().'.'.$request->website_logo->extension();
          //$request->website_logo->move(public_path('img'), $image_website_logo);
          //$createData->website_logo = $image_website_logo;
          $w_file = $request->file('website_logo');
          $w_filename = 'website_logo' . time() . '.' . $w_file->getClientOriginalExtension();
          // Save to storage/app/public/invoices
          $w_path = $w_file->storeAs('website_logo', $w_filename, 'public');
          // Save filename/path in database
          $createData->website_logo = $w_path;
        }

        if ($request->hasFile('invoice_logo')) {
          //$image_invoice_logo = time().'.'.$request->invoice_logo->extension();
          //$request->invoice_logo->move(public_path('img'), $image_invoice_logo);
          //$createData->invoice_logo = $image_invoice_logo;
          $file = $request->file('invoice_logo');
          $filename = 'invoice_logo_' . time() . '.' . $file->getClientOriginalExtension();
          // Save to storage/app/public/invoices
          $path = $file->storeAs('invoice_logo', $filename, 'public');
          // Save filename/path in database
          $createData->invoice_logo = $path;
        }

        $createData->save();
        \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully added."]);



      }
      return $this->successResponse(['redirect' => route('company_details.edit.index')]);
      return \Redirect::back();






    }
}
