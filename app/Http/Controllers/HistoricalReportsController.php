<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\Product;
use App\Models\Payment;
use App\Models\CustomerInvoiceOrder;
use App\Models\CustomerInvoiceProduct;
use App\Models\CompanyDetailModel;
use App\Models\InvoicePayment;
use App\Mail\InvoiceMail;
use Validator;
use Response;
use Session;
use DB;
use Illuminate\Support\Facades\Mail;
use Auth;
use App\Lib\Response as CustomResponse;
use \PDF;
use Carbon\Carbon;
use Redirect;

class HistoricalReportsController extends Controller
{
      use CustomResponse;
      public function index(Request $request)
      {
        $custmar = Customer::where('is_active',1)->get();
          return view('historicalreports.index',['customers' => $custmar]);
      }
      public function customerHistory(Request $request)
      {
        $custmerid = $request->custmer;
        $fromDate = $request->min;
        $toDate = $request->max;

          $totalFilteredRecord = $totalDataRecord = $draw_val = "";
            $columns_list = array(0 =>'id',1 =>'created_at',2=> 'salesman',3=> 'id',4=> 'customer_name',5=> 'vat');
            $totalDataRecord = CustomerInvoice::whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->count();
            $totalFilteredRecord = $totalDataRecord;
            $limit = $request->input('length');
            $start = $request->input('start');
            $fromDate = $request->input('min');
            $toDate = $request->input('max');
            // $salesData = (new CustomerInvoice)->with(['customer','product', 'invoicePayment', 'invoicePayment.payment', 'salesman', 'order'])->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->orderBy('created_at','desc')->get();
            $salesData = (new CustomerInvoice)->with(['customer','product', 'invoicePayment', 'invoicePayment.payment', 'salesman', 'order'])->whereBetween('created_at', [$fromDate." 00:00:00", $toDate." 23:59:59"])->where('customer_id',$custmerid)->orderBy('created_at','desc')->get();


            // echo "<pre>";print_r($salesData);die;
            $sales = array();
            if(!empty($salesData))
            {
                $index=1;
                foreach ($salesData as $sale)
                {
                  $creadit = 0;
                  if(!empty($sale->invoicePayment->payment) && $sale->invoicePayment->payment->id == 3){
                  $creadit =  $sale->product->sum('sub_total');
                  }else{
                  $creadit =  0;
                   }
                   $cash = 0;
                  if(!empty($sale->invoicePayment->payment) && $sale->invoicePayment->payment->id == 1){
                  $cash =  $sale->product->sum('sub_total');
                  }else{
                  $cash =  0;
                   }
                if($sale->product->sum('sub_total') > 0){
                        $postnestedData['invoice_id'] = '<a href="'.route("data_entry.sales_entry.invoice.index",['invoice' => $sale->id]).'" >'.$sale->id.'</a>';
                        $postnestedData['created_at'] = $sale->created_at;
                        $postnestedData['salesman'] = !empty($sale->salesman)?$sale->salesman->first_name:"";
                        $postnestedData['allcheck'] = '<input type="checkbox" class="checkAlllist" id="checkAlllist" name="checkAlllist" value="'.$sale->id.'">';
                        $postnestedData['quantity'] = $sale->product->sum('quantity');
                        $postnestedData['credit'] = $creadit;
                        $postnestedData['cash'] = $cash;
                        $postnestedData['vat'] = !empty($sale->order)?$sale->order->vat:"";
                        $postnestedData['amount'] = $sale->product->sum('sub_total');
                        $postnestedData['mode'] = !empty($sale->invoicePayment->payment)?$sale->invoicePayment->payment->type:"";
                        $postnestedData['running_balance'] = 0;
						
						/** Actions */
						$postnestedData['actions'] = '
							<div class="dropstart">
							  <button class="btn btn-warning btn-sm dropdown-toggle" type="button" id="actionDropdown'.$sale->id.'" data-bs-toggle="dropdown" aria-expanded="false">
								Actions
							  </button>
							  <ul class="dropdown-menu" style="min-width:188px;" aria-labelledby="actionDropdown'.$sale->id.'">
								<li class="cursor-pointer pb-1">
								  
								</li>
								<li class="cursor-pointer pb-1">
								  
								</li>
								<li class="cursor-pointer pb-1">
								  
								</li>
								<li class="cursor-pointer pb-1">
								  
								</li>
								<li class="cursor-pointer pb-1">
								  
								</li>
								<li class="cursor-pointer pb-1">
									
								</li>
							  </ul>
							</div>';
						
                        $sales[] = $postnestedData;
                      }
                }
            }
            $draw_val = $request->input('draw');
            $allData = array(
                "draw"            => intval($draw_val),
                "recordsTotal"    => intval($totalDataRecord),
                "recordsFiltered" => intval($totalFilteredRecord),
                "data"            => $sales
            );
            echo json_encode($allData);


      }

      public function customerhistorypdf(Request $request){

        $ids = $request->invoices;
        $datas =  CustomerInvoice::whereIn('id',$ids)->with('product')->with('order')->get();

        $companyDetails = CompanyDetailModel::first();
        $pdf = PDF::loadView('history_invoice',compact('datas','companyDetails'));
        $rnumber = rand(100, 1000);
        $cdate = date('Y-m-d');


        $path = 'pdffile/'.$cdate;
        // $path = '/pdffile/test';
        // $path = '/newpdftest1/test';
        $invoicename = "invoice".$rnumber.".pdf";

        if (!file_exists($path)) {

              mkdir($path, 0777, true);
            }
         $savepath =$path.'/'.$invoicename;
         // echo $savepath;die;
         // $savepublicpath = public_path($savepath);
         // if($pdf->save(public_path($savepath))){
         //      return url($savepath);
         //   }

        // return url($savepath);
        if($pdf->save(public_path($savepath))){

          $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition: attachment; filename='.$savepath,
             ];
           return  \Response::download(public_path($savepath), 'filename.pdf', $headers);
        }


        // return url($savepath);


      }
      public function customer_invoice_print(Request $request){


        $getdata = $_COOKIE['invoices'];
        $ids = explode(',',$getdata);

         $datas =  CustomerInvoice::whereIn('id',$ids)->with('product')->with('order')->get();
         $companyDetails = CompanyDetailModel::first();

          return view('historicalreports.history_invoice',compact('datas','companyDetails'));

      }
}
