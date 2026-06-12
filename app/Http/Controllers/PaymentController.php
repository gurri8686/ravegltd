<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Validator;
use Response;
use Session;
use DB;
use App\Lib\Response as CustomResponse;

class PaymentController extends Controller
{
    use CustomResponse;

    public function index(){
        $allData = (new Payment)->get();
        return view('payment.index',['data'=> $allData]);
    }
    
    public function create(){
        return view('payment.create');
    }
    
    public function store(Request $request){
        // print_r($request->all());
        // die;
        Payment::create($request->all());
        \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully added."]);
        return $this->successResponse(['redirect' => route('settings.payment_methods.view.index')]);
    }

    public function edit($id)
    {
        $payment = Payment::where(['id'=>$id])->first();
        return view('payment.edit',['data'=>$payment]);
    }

    public function update(Request $request, $id)
    {
        Payment::where(['id'=>$id])->update(['type' => $request->input('type')]);
        \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully updated."]);
        return $this->successResponse(['redirect' => route('settings.payment_methods.view.index')]);
    }

    public function destroy($id)
    {
        $ID = Payment::find($id);
        $ID->delete();
        \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully removed."]);
        return redirect()->route('settings.payment_methods.view.index');
    }
    public function ajaxPaymentsList(Request $request){
            $totalFilteredRecord = $totalDataRecord = $draw_val = "";
            $columns_list = array(0 =>'id',1 =>'type',2=> 'created_at');
            $totalDataRecord = Payment::count();
            $totalFilteredRecord = $totalDataRecord;
            $limit = $request->input('length');
            $start = $request->input('start');
            $fromDate = $request->input('min');
            $toDate = $request->input('max');
            $order = $columns_list[$request->input('order.0.column')];
            $dir = $request->input('order.0.dir');
            if($order == 'type'){
                $paymentsData = (new Payment)->orderBy('type', $dir)->offset($start)->limit($limit)->get();
            } else{
                if(!empty($request->input('min'))){
                    $paymentsData = (new Payment)->whereBetween('created_at', [$fromDate." 00:00:00", $toDate." 23:59:59"])->offset($start)->limit($limit)->orderBy($order,$dir)->get();
                    $totalFilteredRecord = (new Payment)->whereBetween('created_at', [$fromDate." 00:00:00", $toDate." 23:59:59"])->offset($start)->limit($limit)->count();
                } elseif(empty($request->input('search.value')))
                {
                    $paymentsData = (new Payment)->offset($start)->limit($limit)->orderBy($order,$dir)->get();
                } 
                else {
                    $search_text = $request->input('search.value');
                    $paymentsData =  (new Payment)->where('type', 'like', '%'.$search_text.'%')->offset($start)
                    ->limit($limit)
                    ->orderBy($order,$dir)
                    ->get();
                    $totalFilteredRecord = (new Payment)->where('type', 'like', '%'.$search_text.'%')->offset($start)
                    ->limit($limit)->count();
                }
            }
            $payments = array();
            if(!empty($paymentsData))
            {
                $index=1;
                foreach ($paymentsData as $payment)
                {
                    $paymentData['s_no'] = $index++;
                    $paymentData['type'] = $payment->type;
                    $paymentData['created_at'] = uk_ts($payment->created_at, 'Y-m-d');
                    $paymentData['options'] = "&emsp;<i class='fa fa-regular fa-print' onClick='print(".$payment->id.")'></i>&emsp;<i class='fa fa-regular fa-print' onClick='print(".$payment->id.")'></i>&emsp;<i class='fa fa-plus' onClick='expand(".$payment->id.")'></i>&emsp;<i class='fa fa-envelope' onClick='mail(".$payment->id.")'></i>&emsp;". $payment->type;
                    $payments[] = $paymentData;
                }
            }
            $draw_val = $request->input('draw');
            $allData = array(
                "draw"            => intval($draw_val),
                "recordsTotal"    => intval($totalDataRecord),
                "recordsFiltered" => intval($totalFilteredRecord),
                "data"            => $payments
            );
            echo json_encode($allData);
    }
}
