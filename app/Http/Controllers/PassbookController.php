<?php

namespace App\Http\Controllers;

use App\Models\Passbook;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Validator;
use Response;
use Session;
use DB;
use App\Lib\Response as CustomResponse;

class PassbookController extends Controller
{
    use CustomResponse;

    public function index()
    {
        return view('passbook.index');
    }

    public function create(Request $request)
    {
        return view('passbook.create',['customers' => Customer::all(), 'payments'=> Payment::all(),'purposes'=> Config('purposes')]);
    }

    public function ajaxPassbooksList(Request $request){
        $totalFilteredRecord = $totalDataRecord = $draw_val = "";
        $columns_list = array(0 =>'id',1 =>'created_at',2=> 'customer',3=> 'amount',4=> 'payment_id',5=> 'type');
        $totalDataRecord = Passbook::count();
        $totalFilteredRecord = $totalDataRecord;
        $limit = $request->input('length');
        $start = $request->input('start');
        $fromDate = $request->input('min');
        $toDate = $request->input('max');
        $order = $columns_list[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        if($order == 'customer'){
            $passbookData = (new Passbook)->with(['transaction', 'transaction.payment', 'customer'=> function ($query) use($dir){
                $query->orderBy('first_name', $dir); 
                }])->offset($start)->limit($limit)->get();
        } elseif($order == 'amount'){
            $passbookData = (new Passbook)->with(['transaction', 'transaction.payment', 'customer'])->offset($start)->limit($limit)->orderBy('amount', $dir)->get();
        } elseif($order == 'type'){
            $passbookData = (new Passbook)->with(['transaction', 'transaction.payment', 'customer'])->offset($start)->limit($limit)->orderBy('type', $dir)->get();
        } else{
            if(!empty($request->input('min'))){
                $passbookData = (new Passbook)->with(['transaction', 'transaction.payment', 'customer'])->whereBetween('created_at', [$fromDate." 00:00:00", $toDate." 23:59:59"])->offset($start)->limit($limit)->orderBy($order,$dir)->get();
                $totalFilteredRecord = (new Passbook)->with(['transaction', 'payment', 'customer'])->whereBetween('created_at', [$fromDate." 00:00:00", $toDate." 23:59:59"])->orderBy($order,$dir)->count();
            } elseif(empty($request->input('search.value')))
                {
                    $passbookData = (new Passbook)->with(['transaction', 'customer', 'transaction.payment'])->offset($start)->limit($limit)->orderBy($order,$dir)->get();
                } else {
                $search_text = $request->input('search.value');
                $passbookData =  (new Passbook)->whereHas('customer', function($query) use ($search_text){
                    $query->where('first_name', 'like', '%'.$search_text.'%');
                })->orWhereHas('transaction.payment', function($query) use ($search_text){
                    $query->where('type', 'like', '%'.$search_text.'%');
                })->with(['transaction'])
                ->offset($start)
                ->limit($limit)
                ->orderBy($order,$dir)
                ->get();
                $totalFilteredRecord = (new Passbook)->whereHas('customer', function($query) use ($search_text){
                    $query->where('first_name', 'like', '%'.$search_text.'%');
                })->orWhere('id', 'LIKE',"%{$search_text}%")->with(['transaction.payment', 'transaction'])
                ->count();
            }
        }
        $passbooks = array();
        if(!empty($passbookData))
        {
            $index=1;
            foreach ($passbookData as $passbook)
            {
                $passData['s_no'] = $index++;
                $passData['created_at'] = \Carbon\Carbon::parse($passbook->created_at)->format('Y-m-d');
                $passData['customer'] = $passbook->customer->first_name;
                $passData['amount'] = $passbook->amount;
                $passData['payment_id'] = !empty($passbook->transaction->payment)?$passbook->transaction->payment->type:"";
                $passData['type'] = $passbook->type;
                $passData['purpose'] = !empty($passbook->transaction)?$passbook->transaction->purpose:"";
                $passbooks[] = $passData;
            }
        }
        $draw_val = $request->input('draw');
        $allData = array(
            "draw"            => intval($draw_val),
            "recordsTotal"    => intval($totalDataRecord),
            "recordsFiltered" => intval($totalFilteredRecord),
            "data"            => $passbooks
        );
        echo json_encode($allData);
    }

    public function store(Request $request){
        $getId = Passbook::create(['user_id'=> $request->input('customer'),'user_type'=>0, 'amount'=> $request->input('amount'), 'type'=> $request->input('type')])->id;
        Transaction::create(['passbook_id'=> $getId, 'payment_id'=> $request->input('payment_id'), 'purpose'=> $request->input('purpose'), 'reference_id'=> $request->input('reference_id')]);
        \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully added."]);
        return $this->successResponse(['redirect' => route('management.passbooks.view.index')]);
    }
}
