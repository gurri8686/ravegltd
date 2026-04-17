<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;
use Carbon\Carbon;

class CustomerPaymentHistoryController extends Controller
{
	use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('customer_payment_history.create');
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
    public function show(Request $request, $customer_id)
	{
		$perPage = $request->input('per_page', 10);
		$page = $request->input('page', 1);
		$search = $request->input('search', '');
		$sortBy = $request->input('sort_by', 'created_at');
		$sortDirection = $request->input('sort_direction', 'asc');
		$startDate = $request->input('start_date');
		$endDate = $request->input('end_date');

		$query = CustomerPayment::where('customer_id', $customer_id)
			->where('is_paid', 0)
			->where('initiated', 0)
			->where('is_archived', 0)
			->with('paymentMode')
			->with(['customer' => function ($q) {
				$q->select(['id', 'name']);
			}])
			->with(['childPayments' => function ($q) {
				$q->withSum('orderStart', 'sub_total')
					->with('paymentMode')
					->with(['customer' => function ($sub) {
						$sub->select(['id', 'name']);
					}]);
			}])
			->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
				$q->whereBetween('created_at', [
					Carbon::parse($startDate)->startOfDay(),
					Carbon::parse($endDate)->endOfDay(),
				]);
			})
			->when($search, function ($q) use ($search) {
				$q->where(function ($sub) use ($search) {
					$sub->where('note', 'like', "%{$search}%")
						->orWhere('amount', 'like', "%{$search}%")
						->orWhereHas('customer', function ($c) use ($search) {
							$c->where('name', 'like', "%{$search}%");
						})
						->orWhereHas('paymentMode', function ($pm) use ($search) {
							$pm->where('type', 'like', "%{$search}%");
						});
				});
			})
			->orderBy($sortBy, $sortDirection);

		$paginated = $query->paginate($perPage, ['*'], 'page', $page);

		return $this->successResponse($paginated);
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
    public function destroy($invoice_id)
    {
		try{
			$r = CustomerPayment::where('id', $invoice_id)->where('is_paid',0)->first();
			if(empty($r)){
				throw new \Exception('Invalid Payment Requested To Delete');
			}
			
			CustomerPayment::where('id',$invoice_id)->update(['is_archived' => 1]);
			CustomerPayment::where('customer_payment_id',$invoice_id)->update(['is_archived' => 1]);
			
			return $this->successResponse([]);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
    }
	
	public function customers(Request $request){
		return $this->successResponse(Customer::getActive());
	}
}
