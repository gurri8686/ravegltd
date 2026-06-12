<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\Product;
use App\Models\SupplierInvoiceOrder;
use App\Models\SupplierInvoiceProduct;
use App\Models\CompanyDetailModel;
use App\Models\SupplierPayment;
use App\Mail\InvoiceMail;
use App\Services\StockProducts;
use Validator;
use Response;
use Session;
use Auth;
use App\Lib\Response as CustomResponse;
use Illuminate\Support\Facades\Mail;
use \PDF;
use Carbon\Carbon;
use Redirect;
use DB;
use Illuminate\Database\QueryException;

class PurchaseController extends Controller
{
    use CustomResponse;

    public function index(Request $request)
    {
        return view('purchase.index');
    }

    public function newInvoiceForm()
    {
        $suppliers = Supplier::where('is_active', 1)->get();
        return view('purchase.new-invoice', compact('suppliers'));
    }

    public function generateInvoice(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'products' => 'nullable|array',
                'products.*.product_id' => 'required',
                'products.*.quantity' => 'required|numeric|min:0.01',
                'products.*.price' => 'required|numeric|min:0.01',
            ]);

            // Create invoice
            $obj = new SupplierInvoice();
            $obj->salesman_id = Auth::user()->id;
            $obj->supplier_id = $request->supplier_id;
            $obj->other_invoice_id = $request->other_invoice_id ?? "";
            if (\Schema::hasColumn('supplier_invoices', 'delivery_no')) {
                $obj->delivery_no = $request->delivery_no ?? null;
                $obj->supplier_invoice_no = $request->supplier_invoice_no ?? null;
                $obj->awb = $request->awb ?? null;
                $obj->remarks = $request->remarks ?? null;
            }
            if ($request->date) {
                $obj->created_at = \Carbon\Carbon::parse($request->date)->setTime(12, 0, 0);
            }
            $obj->save();

            // Create payment record
            SupplierPayment::create([
                'supplier_id' => $obj->supplier_id,
                'supplier_invoice_id' => $obj->id,
                'amount' => 0,
                'initiated' => 1,
            ]);

            // Add products
            foreach (($request->products ?? []) as $product) {
                $sip = new SupplierInvoiceProduct();
                $sip->supplier_invoice_id = $obj->id;
                $sip->supplier_id = $request->supplier_id;
                $sip->product_id = $product['product_id'];
                $sip->quantity = $product['quantity'];
                $sip->unit_price = $product['price'];
                $sip->sub_total = $product['quantity'] * $product['price'];
                $sip->remarks = $product['remarks'] ?? '';
                $sip->save();

                // Create order start record
                SupplierInvoiceOrder::create([
                    'supplier_invoice_product_id' => $sip->id,
                    'sub_total' => $sip->sub_total,
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'invoice_id' => $obj->id]);
        } catch(\Exception $ex) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        }
    }

    public function create(Request $request)
    {
        // Redirect to new invoice form instead of auto-creating
        return redirect()->route('data_entry.purchase_entry.invoice.new');
    }

    public function store(Request $request)
    {
		//print_r($request->all()); exit;
		DB::beginTransaction();
		try {
            $obj = new SupplierInvoice();
            $obj->salesman_id = Auth::user()->id;
            $obj->supplier_id = $request->input('supplier');
            $obj->other_invoice_id = $request->has('other_invoice_id') ? $request->input('other_invoice_id') : "";
			$obj->save();
			
			SupplierPayment::create([
				'supplier_id' => $obj->supplier_id,
				'supplier_invoice_id' => $obj->id,
				'amount' => 0,
				'initiated' => 1,
			]);
			
			DB::commit();
            \Session::flash('redirect', ['type' => 'success', 'message' => "Invoice Created Successfully."]);
            return $this->successResponse(['redirect' => route('data_entry.purchase_entry.invoice.index',['invoice' => $obj->id])]);
        
		}catch(\Exception $ex){
			DB::rollback();
			\Session::flash('redirect', ['type' => 'error', 'message' => $ex->getMessage()]);
			return $this->exceptionResponse(['redirect' => route('data_entry.purchase_entry.create.index')]);
        }
    }

    public function edit(Request $request, $invoice)
    {
        $i = SupplierInvoice::where('id', $invoice)->first();
        if(empty($i)){
            abort(404);
        }
		
		return view('purchase.edit',['invoice' => $invoice ,
			'productsCount' => Product::where('is_active', 1)->count(),
			'supplierCount' => Supplier::where('is_active', 1)->count()
			]
		);
    }
    public function ajaxProductsList(){
        // Cache for 5 minutes — products list rarely changes and is large.
        $cacheKey = 'purchase_products_list_' . (request()->getHost() ?: 'default');
        $productsList = \Cache::remember($cacheKey, 300, function () {
            return (new Product())->get();
        });
        return json_encode($productsList);
    }

    public function ajaxCreateInvoice(Request $request){

        $getsupplierinvoice = SupplierInvoiceOrder::where('supplier_invoice_id',$request->invoiceId)->first();
         if($getsupplierinvoice){
             return response()->json([
                 'server' => $this->serverResponse(),
                  'status' => '208',
             ]);
          }

         $getinvoiceinformation = getinvoiceinformation();
         $getporterage = $getinvoiceinformation['porterage'];
         $getvat = $getinvoiceinformation['vat'];

         DB::beginTransaction();
         $total = [];

         try {
             $rules = [
                 'invoiceId' => 'required'
             ];
             $validator = Validator::make($request->all(), $rules);
             if ($validator->fails()) {
                 return $this->validationErrorResponse($validator->errors()->messages());
             }

             $getSupplierInvoice = SupplierInvoice::find($request->input('invoiceId'));
             foreach($request->input('rowsdata') as $key => $value){
                 $data['product_id'] = $value['product'];
                 $data['quantity'] = $value['quantity'];
                 $data['unit_price'] = $value['price'];
                 $data['sub_total'] = $value['totalPrice'];
                 $data['supplier_id'] = $getSupplierInvoice['supplier_id'];
                 $data['supplier_invoice_id'] = $request->input('invoiceId');
                 $data['product_info'] = json_encode(Product::where('id',$value['product'])->first());
                 $total[] = $value['quantity'] * $value['price'];

                 // SupplierInvoiceProduct::create($data);
             }

             $cio = new SupplierInvoiceOrder();
             $cio->supplier_invoice_id = $request->input('invoiceId');
             $cio->supplier_id = $getSupplierInvoice['supplier_id'];
             $cio->sub_total = array_sum($total);
             $cio->total = (array_sum($total) + $getporterage) + ((array_sum($total) + $getporterage) * 2 ) / 100;
             $cio->vat = $getvat;
             $cio->status = $request->input('status');
             $cio->porterage = $getporterage;
             $cio->created_at = date('Y-m-d H:i:s');
             $cio->updated_at = date('Y-m-d H:i:s');

              $cio->save();

             DB::commit();
             return $this->successResponse(['id' => $request->input('invoiceId')]);

         }catch(\Exception $ex){
             DB::rollback();
             $this->exceptionResponse($ex);
         }
     }
     public function ajaxCreateSingalInvoice(Request $request){
         $getsupplierinvoice = SupplierInvoiceOrder::where('supplier_invoice_id',$request->invoiceId)->first();
         if($getsupplierinvoice){
              return response()->json([
                 'server' => $this->serverResponse(),
                         'status' => '208',
             ]);
          }

         try {
                 $getSupplierInvoice = SupplierInvoice::find($request->invoiceId);

                 if($request->invoiceproductid == 0){
                     $data['product_id'] = $request->product;
                     $data['quantity'] = $request->quantity;
                     $data['unit_price'] = $request->price;
                     $data['sub_total'] = $request->totalPrice;
                     $data['supplier_id'] =$getSupplierInvoice['supplier_id'];
                     $data['supplier_invoice_id'] = $request->invoiceId;
                     $data['product_info'] = json_encode(Product::where('id',$request->product)->first());
                     $save = SupplierInvoiceProduct::create($data);
                     return $this->successResponse(['invoiceproductid' => $save->id,'indexvalue' => $request->indexvalue]);


                 }else{

                     $updateData = SupplierInvoiceProduct::find($request->invoiceproductid);
                     $updateData->product_id = $request->product;
                     $updateData->quantity = $request->quantity;
                     $updateData->unit_price = $request->price;
                     $updateData->sub_total = $request->totalPrice;
                     $updateData->product_info = json_encode(Product::where('id',$request->product)->first());
                     $update = $updateData->update();
                     return $this->successResponse(['invoiceproductid' => $request->invoiceproductid,'indexvalue' => $request->indexvalue]);


                 }



                 return $this->successResponse(['invoiceproductid' => $save->id,'indexvalue' => $request->indexvalue]);
             }catch(\Exception $ex){
                 $this->exceptionResponse($ex);
             }

     }

    public function ajaxDeleteSingalInvoice(Request $request, 
		\App\Services\SalesPurchaseValidations $salesPurchaseValidations,
		StockProducts $stockProducts){
		
		$rules = [
			'invoiceId' => ['required','integer'],
			'invoiceproductid' => ['required','integer']
		];
		
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			if ($validator->fails()) {
				// Get all errors
				$errors = $validator->errors();
				return $this->errorResponse(json_encode($errors));
			}
		}
		
		$getdata = SupplierInvoiceProduct::where('supplier_invoice_id', $request->invoiceId)
			->where('id', $request->invoiceproductid)->count();
		
		// validation.
		try{
			$record = SupplierInvoiceProduct::where('supplier_invoice_id', $request->invoiceId)
				->where('id', $request->invoiceproductid)->first();
				
			if(empty($record)){
				throw new \Exception("Invalid Record Requested To Delete!");
			}
			
			$salesPurchaseValidations->canDeletePurchaseEntryFromInvoice($request->invoiceproductid, $record->supplier_invoice_id, $record->supplier_id,$record->product_id);
		
		}catch(\Exception $ex){
			return $this->errorResponse($ex->getMessage());
		}
		
		DB::beginTransaction();
		try {
			
			if($getdata == 1){
				$deleteproduct  = SupplierInvoiceProduct::where('id', $request->invoiceproductid)->delete();
				$deletecustmor = SupplierInvoiceOrder::where('supplier_invoice_id', $request->invoiceId)->delete();
				// stock record.
				$stockProducts->removeStock([
					'type' => 'supplier',
					'invoice_id' => $request->invoiceId,
					'event' => 'stock_deleted',
					'ref_id' => $request->input('invoiceproductid')
				]);
				$pdfshow = 0;
			}else{
				$getproductdetail = SupplierInvoiceProduct::where('id', $request->invoiceproductid)->first();
				$subtotal =  $getproductdetail->sub_total;
				$getinvoicedetail = SupplierInvoiceOrder::where('supplier_invoice_id', $request->invoiceId)->first();
				$invoicesub_total = $getinvoicedetail->sub_total;
				$invoicetotal = $getinvoicedetail->total;
				$finalsubtotal = $invoicesub_total - $subtotal;
				$finaltotal = $invoicetotal - $subtotal;
				$getinvoicedetail->sub_total =  $finalsubtotal;
				$getinvoicedetail->total = $finaltotal;

				$update = $getinvoicedetail->update();
				
			if($update){
				
				$deleteproduct  = SupplierInvoiceProduct::where('id', $request->invoiceproductid)->delete();
				// stock record.
				$stockProducts->removeStock([
					'type' => 'supplier',
					'invoice_id' => $request->invoiceId,
					'event' => 'stock_deleted',
					'ref_id' => $request->input('invoiceproductid')
				]);
				$pdfshow = 1;
			}

		}
			DB::commit();
			return $this->successResponse(['invoiceproductid' =>$request->invoiceproductid, 'pdfshow' =>$pdfshow]);
		}catch(\Exception $ex){
			DB::rollback();
			return $this->exceptionResponse($ex);
		}
    }

     public function ajaxDailyBookPurchase(Request $request){
        $totalFilteredRecord = $totalDataRecord = $draw_val = "";
        $columns_list = array(0 =>'id',1 =>'created_at',2=> 'salesman',3=> 'id',4=> 'supplier_name',5=> 'vat');
        $totalDataRecord = SupplierInvoice::whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->count();
        $totalFilteredRecord = $totalDataRecord;
        $limit = $request->input('length');
        $start = $request->input('start');
        $fromDate = $request->input('min');
        $toDate = $request->input('max');
        $order = $columns_list[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $supplierData = (new SupplierInvoice)->with(['order', 'salesman', 'supplier'])->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->offset($start)->limit($limit)->orderBy('created_at','desc')->get();
        if($order == 'supplier_name'){
            $supplierData = (new SupplierInvoice)->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->with(['order', 'salesman', 'supplier'=> function ($query) use($dir){
                $query->orderBy('name', $dir);
                }])->offset($start)->limit($limit)->get();
        }  elseif($order == 'salesman'){
            $supplierData = (new SupplierInvoice)->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->with(['supplier', 'order', 'salesman'=> function ($query) use($dir) {
                $query->orderBy('first_name', $dir);
                }])->offset($start)->limit($limit)->get();
        }   elseif($order == 'vat'){
            $supplierData = (new SupplierInvoice)->with(['supplier', 'salesman', 'order'=> function ($query) use($dir) {
                $query->orderBy('vat', $dir);
                }])->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->offset($start)->limit($limit)->get();
        } elseif(!empty($request->input('min'))){
                $supplierData =(new SupplierInvoice)->with(['supplier', 'salesman', 'order'])->whereBetween('created_at', [$fromDate." 00:00:00", $toDate." 23:59:59"])->offset($start)->limit($limit)->orderBy($order,$dir)->get();
                $totalFilteredRecord = (new SupplierInvoice)->with(['supplier', 'salesman', 'order'])->whereBetween('created_at', [$fromDate." 00:00:00", $toDate." 23:59:59"])->orderBy($order,$dir)->count();
            } elseif(!empty($request->input('search.value'))){
                $search_text = $request->input('search.value');
                $supplierData =  (new SupplierInvoice)->whereHas('supplier', function($query) use ($search_text){
                    $query->where('name', 'like', '%'.$search_text.'%');
                })->orWhere('id', 'LIKE',"%{$search_text}%")->with(['supplier', 'order'])->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])
                ->offset($start)
                ->limit($limit)
                ->orderBy($order,$dir)
                ->get();
                $totalFilteredRecord = (new SupplierInvoice)->whereHas('supplier', function($query) use ($search_text){
                    $query->where('name', 'like', '%'.$search_text.'%');
                })->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->orWhere('id', 'LIKE',"%{$search_text}%")->with(['supplier', 'order'])
                ->count();
            }
        $suppliers = array();
        if(!empty($supplierData))
        {
            $index=1;
            foreach ($supplierData as $sale)
            {
                $postnestedData['invoice_id'] ='<a href="'.route("data_entry.purchase_entry.invoice.index",['invoice' => $sale->id]).'">'.$sale->id.'</a>';
                //$postnestedData['created_at'] = \Carbon\Carbon::parse($sale->created_at)->format('Y-m-d');
                $postnestedData['created_at'] = $sale->created_at;
				$postnestedData['salesman'] = !empty($sale->salesman)?$sale->salesman->first_name:"";
                
				/*$postnestedData['supplier_name'] = "
				<i class='fa fa-regular text-danger fa-print font-large-1' data-bs-toggle='tooltip' data-bs-placement='top' title='Print Invoice' onClick='print(".$sale->id.", 1)'></i>&emsp;
				<i class='fa fa-regular text-info font-large-1 fa-print' data-bs-toggle='tooltip' data-bs-placement='top' title='Print Delivery' onClick='print(".$sale->id.", 1)'></i>&emsp;
				<i class='fa fa-regular text-success fa-download font-large-1' title='Download Invoice' data-bs-toggle='tooltip' data-bs-placement='top' onClick='download(".$sale->id.", 1)'></i>&emsp;<i data-bs-toggle='tooltip' data-bs-placement='top' title='Email To Customer' class='fa fa-envelope font-large-1' onClick='mail(".$sale->id.", 1)'></i>&emsp;"
				. $sale->supplier->name;*/
				
                $postnestedData['supplier_name'] = $sale->supplier->name;
				
				$postnestedData['vat'] = ($sale->order)?$sale->order->vat:"";
                $postnestedData['amount'] = ($sale->order)?formatTwoDecimalCurrenty($sale->order->total):"";
                //$postnestedData['action'] ='<a href="'.route("data_entry.purchase_entry.invoice.index",['invoice' => $sale->id]).'"  class="btn btn-primary mr-1 btn-sm"><i class="feather icon-edit"></i></a> <a href="#" onclick="deleteinvoice('.$sale->id.')" class="btn btn-primary mr-1 btn-sm"><i class="feather icon-delete"></i></a>';
                $postnestedData['action'] = '
					<div class="dropstart">
					  <button class="btn btn-warning btn-sm dropdown-toggle" type="button" id="actionDropdown'.$sale->id.'" data-bs-toggle="dropdown" aria-expanded="false">
						Actions
					  </button>
					  <ul class="dropdown-menu" aria-labelledby="actionDropdown'.$sale->id.'">
						<li class="p-1 cursor-pointer" onClick="print('.$sale->id.', 1)">
							<i class="fa fa-print" data-bs-toggle="tooltip" data-bs-placement="top" title="Print Invoice"></i> Print
						</li>
						<li class="p-1 cursor-pointer" onClick="download('.$sale->id.', 1)">
						  <i class="fa fa-download" title="Download Invoice" data-bs-toggle="tooltip" data-bs-placement="top"></i> Download
						</li>
							
						<li class="p-1 cursor-pointer">
						  <a class="dropdown-item p-0" href="'.route("data_entry.purchase_entry.invoice.index", ['invoice' => $sale->id]).'">
							<i class="feather icon-edit"></i> Edit
						  </a>
						</li>
						
					  </ul>
					</div>';
				
				$suppliers[] = $postnestedData;
            }
        }
        $draw_val = $request->input('draw');
        $allData = array(
            "draw"            => intval($draw_val),
            "recordsTotal"    => intval($totalDataRecord),
            "recordsFiltered" => intval($totalFilteredRecord),
            "data"            => $suppliers
        );
        echo json_encode($allData);
    }

    public function ajaxEditSingleInvoice(Request $request, 
		\App\Services\SalesPurchaseValidations $salesPurchaseValidations,
		StockProducts $stockProducts){
		
		$rules = [
			'product' => ['required', 'array'],
			'product.label' => ['required', 'string'],
			'product.value' => ['required','integer'],
			
			'invoiceId' => ['required','integer'],
			'invoiceproductid' => ['required','integer'],
			
			'quantity' => ['required','numeric'],
			'price' => ['required','numeric']
		];
		
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			if ($validator->fails()) {
				// Get all errors
				$errors = $validator->errors();
				return $this->errorResponse(json_encode($errors));
			}
		}
		$request->product = is_array($request->product) ? $request->product['value'] : $request->product;
		$invoice = SupplierInvoiceProduct::
			where("supplier_invoice_id",$request->invoiceId)
			->where("id",$request->invoiceproductid)->first();
					
		//print_r($invoice->toArray()); exit;
		
		if(empty($invoice)){
			$this->errorResponse("Invalid Invoice or Invoice Not Found!");
		}
		
		// validation.
		try{
			//echo '<pre>'; print_r($request->all()); exit;
			//print_r($invoice); exit;
			if($invoice->product_id == $request->product){
				$salesPurchaseValidations->canEditPurchaseQtyFromInvoice(
					$request->invoiceproductid, 
					$request->invoiceId, 
					$invoice->supplier_id,
					$request->product, 
					$request->quantity);
			}else{
				/*$salesPurchaseValidations->canDeletePurchaseEntryFromInvoice(
				$request->invoiceproductid, 
				$request->invoiceId, 
				$invoice->supplier_id,
				$invoice->product_id, 
				"edit");*/
				throw new \Exception("Product Can't be Changed!");
			}
			
		}catch(\Exception $ex){
			return $this->errorResponse($ex->getMessage());
		}
		
		
		DB::beginTransaction();
		try{
			$data= [
				'product_id'=>$request->product,
				'remarks'=>$request->remarks,
				'quantity'=>$request->input('quantity'),
				'unit_price'=>$request->input('price'),
				'sale_price'=>$request->input('sale_price') ? $request->input('sale_price') : null,
				'sub_total'=>$request->input('totalPrice'),
				'product_info' => json_encode(Product::where('id',$request->product)->first())
			];
			SupplierInvoiceProduct::where('id',$request->input('invoiceproductid'))->update($data);
		
			// stock record.
			$stockProducts->recordStock([
				'supplier_id' => $invoice->supplier_id,
				'product_id' => $request->product,
				'stock' => $request->quantity,
				'type' => 'supplier',
				'invoice_id' => $request->invoiceId,
				'event' => 'stock_updated',
				'price' => $request->price,
				'ref_id' => $request->input('invoiceproductid')
			]);
			
			DB::commit();
			return $this->successResponse(['invoiceproductid' => $request->input('invoiceproductid'),'indexvalue' => $request->input('indexvalue')]);
		
		}catch (QueryException $e) {
			DB::rollback();
			if ($e->getCode() == 23000) {
				return $this->errorResponse('This combination of supplier, invoice, and product already exists.');
			}
			return $this->exceptionResponse($e);
		}
	}

    public function ajaxCreateSingalInvoicenew(StockProducts $stockProducts, Request $request){
	
		//print_r($request->all()); exit;
	
		$rules = [
			'product' => ['required', 'array'],
			'product.label' => ['required', 'string'],
			'product.value' => ['required','integer'],
			
			'invoiceId' => ['required','integer'],
			
			'quantity' => ['required','numeric'],
			'price' => ['required','numeric']
		];
		
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			if ($validator->fails()) {
				// Get all errors
				$errors = $validator->errors();
				return $this->errorResponse(json_encode($errors));
			}
		}
		
		$request->product = is_array($request->product) ? $request->product['value'] : $request->product;
		
		DB::beginTransaction();
		try{
		
        $getinvoiceinformation = getinvoiceinformation();
        $getporterage = $getinvoiceinformation['porterage'];
        $getvat = $getinvoiceinformation['vat'];

        $getsupplierinvoice = SupplierInvoiceOrder::where('supplier_invoice_id',$request->invoiceId)->first();

        $getSupplierInvoice = SupplierInvoice::find($request->invoiceId);
		
        if(!$getsupplierinvoice){

                    $data['product_id'] = $request->product;
                    $data['quantity'] = $request->quantity;
                    $data['unit_price'] = $request->price;
					$data['sale_price'] = $request->sale_price ? $request->sale_price : null;
					$data['remarks'] = $request->remarks;
                    $data['sub_total'] = $request->totalPrice;
                    $data['supplier_id'] =$getSupplierInvoice['supplier_id'];
                    $data['supplier_invoice_id'] = $request->invoiceId;
                    $data['product_info'] = json_encode(Product::where('id',$request->product)->first());
                    $save = SupplierInvoiceProduct::create($data);

					// stock record.
					$stockProducts->recordStock([
						'supplier_id' => $getSupplierInvoice['supplier_id'],
						'product_id' => $request->product,
						'stock' => $request->quantity,
						'type' => 'supplier',
						'invoice_id' => $request->invoiceId,
						'event' => 'stock_added',
						'price' => $request->price,
						'ref_id' => $save->id
					]);

                    $subtotal = $request->quantity * $request->price;
                    $total = $subtotal + $getporterage + $getvat;
                    $cio = new SupplierInvoiceOrder();
                    $cio->supplier_invoice_id = $request->input('invoiceId');
                    $cio->supplier_id = $getSupplierInvoice['supplier_id'];
                    $cio->sub_total = $subtotal;
                    $cio->total = $total;
                    $cio->vat = $getvat;
                    $cio->porterage = $getporterage;
                    $cio->created_at = date('Y-m-d H:i:s');
                    $cio->updated_at = date('Y-m-d H:i:s');

                    $cio->save();
					DB::commit();
                    return $this->successResponse(['invoiceproductid' => $save->id,'indexvalue' => $request->indexvalue]);
        }else{
                $data['product_id'] = $request->product;
                $data['quantity'] = $request->quantity;
				$data['remarks'] = $request->remarks;
                $data['unit_price'] = $request->price;
				$data['sale_price'] = $request->sale_price ? $request->sale_price : null;
                $data['sub_total'] = $request->totalPrice;
                $data['supplier_id'] =$getSupplierInvoice['supplier_id'];
                $data['supplier_invoice_id'] = $request->invoiceId;
                $data['product_info'] = json_encode(Product::where('id',$request->product)->first());
                $save = SupplierInvoiceProduct::create($data);
				
				// stock record.
				$stockProducts->recordStock([
					'supplier_id' => $getSupplierInvoice['supplier_id'],
					'product_id' => $request->product,
					'type' => 'supplier',
					'stock' => $request->quantity,
					'invoice_id' => $request->invoiceId,
					'event' => 'stock_added',
					'price' => $request->price,
					'ref_id' => $save->id
				]);


                $subtotal = $request->quantity * $request->price;
                $allsubtotal = $subtotal + $getsupplierinvoice->sub_total;
                $alltotal = $subtotal + $getsupplierinvoice->total;
                $getsupplierinvoice->sub_total = $allsubtotal;
                $getsupplierinvoice->total = $alltotal;


                $update = $getsupplierinvoice->update();
				DB::commit();
                return $this->successResponse(['invoiceproductid' => $save->id,'indexvalue' => $request->indexvalue]);
        }
		
		
		}catch (QueryException $e) {
			DB::rollback();
			if ($e->getCode() == 23000) {
				return $this->errorResponse('This combination of supplier, invoice, and product already exists.');
			}
			return $this->exceptionResponse($e);
		}
    }
	
	public function suppliers(Request $request)
    {
        return $this->successResponse(\App\Models\Supplier::getActive());
    }
	
	public function print(Request $request)
    {
		$supplier_id = $start_date = $end_date = "";
		extract($request->only('supplier_id', 'start_date', 'end_date'));
		$invoiceIds = $request->input('invoices', []);

		$data = (new SupplierInvoice());

		if (!empty($start_date)) {
			$data = $data->whereDate('created_at', '>=', $start_date);
		}
		if (!empty($end_date)) {
			$data = $data->whereDate('created_at', '<=', $end_date);
		}
		if (!empty($supplier_id)) {
			$data->where('supplier_id', $supplier_id);
		}
		if (!empty($invoiceIds)) {
			$data->whereIn('id', (array) $invoiceIds);
		}

		$invoices = $data->withSum(['products as total' => function ($q) {
				$q->where('is_archive', 0);
			}], 'sub_total')
			->withSum('payments as total_paid', 'amount')
			->with('supplier')
			->orderBy('created_at', 'desc')
			->get();

		$companyDetails = CompanyDetailModel::first();
		$currency = env('CURRENCY_SYMBOL', '£');

		return view('daily-report.purchase-print', compact('invoices', 'start_date', 'end_date', 'companyDetails', 'currency'));
    }

	public function statementDailyBookPurchase(Request $request)
	{
		$start_date = $request->start_date;
		$end_date = $request->end_date;
		$supplier_id = $request->supplier_id;

		$data = (new SupplierInvoice());

		if (!empty($start_date)) {
			$data = $data->whereDate('created_at', '>=', $start_date);
		}
		if (!empty($end_date)) {
			$data = $data->whereDate('created_at', '<=', $end_date);
		}
		if (!empty($supplier_id)) {
			$data->where('supplier_id', $supplier_id);
		}

		$invoices = $data->withSum(['products as total' => function ($q) {
				$q->where('is_archive', 0);
			}], 'sub_total')
			->withSum('payments as total_paid', 'amount')
			->with('supplier')
			->orderBy('created_at', 'desc')
			->get();

		$currency = env('CURRENCY_SYMBOL', '£');
		$rows = [];
		foreach ($invoices as $invoice) {
			$total = (float)($invoice->total ?? 0);
			$rows[] = [
				'#' . ($invoice->other_invoice_id ?? $invoice->id),
				uk_ts($invoice->created_at, 'd M Y'),
				$invoice->supplier->name ?? '',
				number_format($total, 2),
			];
		}

		$headings = ['Invoice No.', 'Date', 'Supplier Name', 'Amount (' . $currency . ')'];

		$export = new class($rows, $headings) implements
			\Maatwebsite\Excel\Concerns\FromArray,
			\Maatwebsite\Excel\Concerns\WithHeadings {
			protected $rows;
			protected $headings;
			public function __construct($rows, $headings) {
				$this->rows = $rows;
				$this->headings = $headings;
			}
			public function array(): array { return $this->rows; }
			public function headings(): array { return $this->headings; }
		};

		$range = ($start_date && $end_date) ? "{$start_date}-to-{$end_date}" : 'all';
		$fileName = "daily-purchase-statement-{$range}.xlsx";
		return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
	}

	private function configureMailerFromEnv()
	{
		config([
			'mail.default'                => env('MAIL_MAILER', 'smtp'),
			'mail.mailers.smtp.transport' => 'smtp',
			'mail.mailers.smtp.host'      => env('MAIL_HOST'),
			'mail.mailers.smtp.port'      => (int) env('MAIL_PORT', 587),
			'mail.mailers.smtp.encryption'=> env('MAIL_ENCRYPTION', 'tls'),
			'mail.mailers.smtp.username'  => env('MAIL_USERNAME'),
			'mail.mailers.smtp.password'  => env('MAIL_PASSWORD'),
			'mail.from.address'           => env('MAIL_FROM_ADDRESS'),
			'mail.from.name'              => env('MAIL_FROM_NAME', 'R & A Veg Ltd'),
		]);
		app()->forgetInstance('mailer');
		app()->forgetInstance('swift.mailer');
		app()->forgetInstance('swift.transport');
		\Illuminate\Support\Facades\Mail::clearResolvedInstances();
	}

	public function emailDailyBookPurchase(Request $request)
	{
		$rules = [
			'to_email'   => 'required|email',
		];
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'payload' => 'Recipient email is required',
			]);
		}
		$ccEmail = $request->input('cc_email');
		if (!empty($ccEmail) && !filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
			return response()->json(['success' => false, 'payload' => 'Enter a valid CC email address.']);
		}

		$start_date = $request->start_date;
		$end_date = $request->end_date;
		$supplier_id = $request->supplier_id;

		$data = (new SupplierInvoice());

		if (!empty($start_date)) {
			$data = $data->whereDate('created_at', '>=', $start_date);
		}
		if (!empty($end_date)) {
			$data = $data->whereDate('created_at', '<=', $end_date);
		}
		if (!empty($supplier_id)) {
			$data->where('supplier_id', $supplier_id);
		}
		$invoices = $data->withSum(['products as total' => function ($q) {
				$q->where('is_archive', 0);
			}], 'sub_total')
			->withSum('payments as total_paid', 'amount')
			->with('supplier')
			->orderBy('created_at', 'desc')
			->get();

		if ($invoices->isEmpty()) {
			return response()->json([
				'success' => false,
				'payload' => 'No purchase invoices found in the selected period. Nothing to email.',
			]);
		}

		$companyDetails = \App\Models\CompanyDetailModel::first();
		$companyName = $companyDetails->company_name ?? 'R & A Veg Ltd';
		$currency = env('CURRENCY_SYMBOL', '£');

		$totalAmount  = $invoices->sum('total');
		$totalPaid    = $invoices->sum('total_paid');
		$totalPending = $totalAmount - $totalPaid;

		$rows = [];
		foreach ($invoices as $invoice) {
			$total = (float)($invoice->total ?? 0);
			$rows[] = [
				'#' . ($invoice->other_invoice_id ?? $invoice->id),
				uk_ts($invoice->created_at, 'd M Y'),
				$invoice->supplier->name ?? '',
				number_format($total, 2),
			];
		}
		$headings = ['Invoice No.', 'Date', 'Supplier Name', 'Amount (' . $currency . ')'];

		$export = new class($rows, $headings) implements
			\Maatwebsite\Excel\Concerns\FromArray,
			\Maatwebsite\Excel\Concerns\WithHeadings {
			protected $rows; protected $headings;
			public function __construct($rows, $headings) { $this->rows = $rows; $this->headings = $headings; }
			public function array(): array { return $this->rows; }
			public function headings(): array { return $this->headings; }
		};
		$emailRange = ($start_date && $end_date) ? "{$start_date}-to-{$end_date}" : 'all';
		$excelName = "daily-purchase-statement-{$emailRange}.xlsx";
		$excelBinary = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

		$periodText = ($start_date && $end_date)
			? \Carbon\Carbon::parse($start_date)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($end_date)->format('d M Y')
			: 'All time';
		$defaultSubject = "Daily Purchase Report — {$periodText}";
		$defaultMessage = "Dear team,\n\nPlease find attached the daily purchase report for the period {$periodText}.\n\n" .
			"Total Invoices: " . $invoices->count() . "\n" .
			"Total Amount: " . $currency . " " . number_format($totalAmount, 2) . "\n" .
			"Total Paid: " . $currency . " " . number_format($totalPaid, 2) . "\n" .
			"Total Pending: " . $currency . " " . number_format($totalPending, 2);

		$mailData = [
			'company_name'  => $companyName,
			'report_title'  => 'Daily Purchase Report',
			'subject'       => $request->input('subject') ?: $defaultSubject,
			'message'       => $request->input('message') ?: $defaultMessage,
			'cc_email'      => $ccEmail,
			'period'        => $periodText,
			'invoice_count' => $invoices->count(),
			'total_amount'  => $currency . ' ' . number_format($totalAmount, 2),
			'total_paid'    => $currency . ' ' . number_format($totalPaid, 2),
			'total_pending' => $currency . ' ' . number_format($totalPending, 2),
			'generated_on'  => date('d M Y'),
			'excel_name'    => $excelName,
			'excel_binary'  => $excelBinary,
		];

		try {
			$this->configureMailerFromEnv();
			\Illuminate\Support\Facades\Mail::to($request->to_email)
				->send(new \App\Mail\DailyReportMail($mailData));
			return response()->json(['success' => true, 'payload' => 'Report emailed to ' . $request->to_email]);
		} catch (\Exception $ex) {
			return response()->json(['success' => false, 'payload' => 'Could not send email: ' . $ex->getMessage()]);
		}
	}

	public function list(Request $request)
    {
		$supplier_id = $product_id = $start_date = $end_date = "";

		extract($request->only('product_id', 'supplier_id', 'start_date', 'end_date'));

		$data = \App\Models\SupplierInvoice::select('*', DB::raw("DATE(created_at) as date_only"));

		if(!empty($start_date)){
			$data = $data->whereRaw('DATE(created_at) >= ?', [$start_date]);
		}
		if(!empty($end_date)){
			$data = $data->whereRaw('DATE(created_at) <= ?', [$end_date]);
		}

		if(!empty($supplier_id)){
			$data = $data->where('supplier_id', $supplier_id);
		}

		$data = $data->withSum(['products as total' => function ($q) {
				$q->where('is_archive', 0);
			}], 'sub_total')->withCount(['products as products_count' => function ($q) {
				$q->where('is_archive', 0);
			}])->with('supplier')->orderBy('created_at', 'desc')->get();
		$results = $data->map(function($item) {
			$arr = $item->toArray();
			$dateOnly = $item->date_only ?? substr($item->getAttributes()['created_at'] ?? '', 0, 10);
			if ($dateOnly) {
				$ts = strtotime($dateOnly . ' 12:00:00');
				$arr['created_at'] = date('d M Y', $ts);
			}
			return $arr;
		});
		return $this->successResponse($results);
    }
	
    public function dailyBookSales(){
        $allData = (new SupplierInvoice)->with(['supplier', 'order'])->get();
        //return view('daily-report.purchases',['data'=> $allData]);
        return view('daily-report.view',['data'=> $allData]);
    }

  public function ajaxfetchInvoiceDetail(Request $request){

    try {

        $dataget = SupplierInvoice::where('id', $request->getInvoiceId)->with(['supplier','payments'])->first();
           $total = $dataget->products()->where('is_archive',0)->sum('sub_total');
           $paid = $dataget->payments->sum('amount');
           $dataget->total_amount = $total;
           $dataget->paid_amount = $paid;
           $dataget->pending_amount = $total - $paid;
           $dt = $dataget->getAttributes()['created_at'] ?? '';
           $dataget->formatted_date = $dt ? substr($dt, 0, 10) : '';
           return json_encode($dataget);
        }catch(\Exception $ex){
            $this->exceptionResponse($ex);
        }


  }
  public function invoiceview($id){

    $getinvoiceinformation = getinvoiceinformation();
    $data =  SupplierInvoice::where('id',$id)->with('product')->with('order')->first();
    $companyDetails = CompanyDetailModel::first();
    return view('purchase.invoice',compact('data','companyDetails'));
}
public function invoicedownload($id){

    $data =  SupplierInvoice::where('id',$id)->with('product')->with('order')->first();
    $companyDetails = CompanyDetailModel::first();
    $pdf = PDF::loadView('purchase-invoice',compact('data','companyDetails'));

	//return $pdf->stream('pdf_file.pdf');
    return $pdf->download('pdf_file.pdf');
}

public function invoiceExcel($id){
	$invoice = SupplierInvoice::where('id', $id)
		->with(['product', 'supplier', 'order'])
		->first();

	if (!$invoice) {
		return response('Invoice not found', 404);
	}

	$currency = env('CURRENCY_SYMBOL', '£');
	$rows = [];
	$grandTotal = 0;
	$products = $invoice->product ?? collect();
	foreach ($products as $p) {
		if (!empty($p->is_archive)) continue;
		$qty = (float)($p->quantity ?? 0);
		$price = (float)($p->unit_price ?? 0);
		$sub = (float)($p->sub_total ?? ($qty * $price));
		$sellPrice = $p->sale_price;
		$grandTotal += $sub;
		$rows[] = [
			$p->product->name ?? ($p->product_name ?? ''),
			$p->remarks ?? '',
			$qty,
			number_format($price, 2),
			!empty($sellPrice) ? number_format((float)$sellPrice, 2) : '',
			number_format($sub, 2),
		];
	}

	$rows[] = ['TOTAL', '', '', '', '', number_format($grandTotal, 2)];

	$headings = ['Product', 'Remarks', 'Qty', 'Price (' . $currency . ')', 'Sell Price (' . $currency . ')', 'Total (' . $currency . ')'];

	$export = new class($rows, $headings) implements
		\Maatwebsite\Excel\Concerns\FromArray,
		\Maatwebsite\Excel\Concerns\WithHeadings {
		protected $rows;
		protected $headings;
		public function __construct($rows, $headings) {
			$this->rows = $rows;
			$this->headings = $headings;
		}
		public function array(): array { return $this->rows; }
		public function headings(): array { return $this->headings; }
	};

	$supplierName = $invoice->supplier->name ?? 'supplier';
	$supplierSlug = preg_replace('/[^A-Za-z0-9]+/', '-', $supplierName);
	$fileName = "purchase-invoice-{$invoice->id}-{$supplierSlug}.xlsx";
	return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
}

public function mail($id){
    $response = 0;
    $dataget =  SupplierInvoice::where('id',$id)->with(['supplier', 'order'])->first();
    if(!empty($dataget->supplier) && ($dataget->supplier->email)){

            $subtotal = 0;

            $productdata = array();
            foreach($dataget->product as $key => $product){
                $getjson = json_decode($product->product_info);
                $productdata[$key]['quantity'] =  $product->quantity;
                $productdata[$key]['unit_price'] =  $product->unit_price;
                $productdata[$key]['sub_total'] =  $product->sub_total;
                $productdata[$key]['product_id'] = $getjson->product_id;
                $subtotal = $subtotal + $product->sub_total;
            }
            $supplier =  \App\Models\Supplier::where(['id' => $dataget->supplier_id])->first();
            $data = [
            'id' => $dataget->id,
            'date' => $dataget->created_at,
            'type' => "Supplier",
            'subtotal' => ($dataget->order)?$dataget->order->sub_total:"",
            'porterage' => ($dataget->order)?$dataget->order->porterage:"",
            'vat' => ($dataget->order)?$dataget->order->vat:"",
            'total' => ($dataget->order)?$dataget->order->total:"",
            'name' => $supplier->name,
            'productdata' => $productdata,
        ];
        Mail::to($dataget->supplier->email)->send(new InvoiceMail($data));
        $response = 1;
    } else {
        $response = 0;
    }
    return $response;
  }

  public function ajaxfetchInvoiceAllDetail($id){

    try {
        $dataget = SupplierInvoiceProduct::where('supplier_invoice_id', $id)->with(['product'])->get();
		
        $products=[];
        foreach ($dataget as $data)
            {
                $postnestedData['product_id'] = $data['product_id'];
                $postnestedData['payment'] = "";
                $postnestedData['product'] = ["label" => $data["product"] ? $data["product"]['name'] : 'Unknown', "value" => $data['product_id']];
                $postnestedData['quantity'] = $data['quantity'];
                $postnestedData['price'] = $data['unit_price'];
				$postnestedData['sale_price'] = $data['sale_price'];
				$postnestedData['remarks'] = $data['remarks'];
                $postnestedData['totalPrice'] = (float)$data['sub_total'];
                $postnestedData['fieldToggle'] = "checked";
                $postnestedData['invoiceproductid'] = $data['id'];
                $products[] = $postnestedData;
            }
           
		   return json_encode($products);
        }catch(\Exception $ex){
            return $this->exceptionResponse($ex);
        }
  }

    public function delete($id){
      $supplierInvoice = SupplierInvoice::find($id);
            if($supplierInvoice){
              $supplierInvoiceOrder = SupplierInvoiceOrder::where('supplier_invoice_id',$supplierInvoice->id)->first();
              if($supplierInvoiceOrder){
                $supplierInvoiceProduct = SupplierInvoiceProduct::where('supplier_invoice_id',$supplierInvoiceOrder->supplier_invoice_id)->delete();
                  $supplierInvoiceOrder->delete();

              }
              $supplierInvoice->delete();
            }


          \Session::flash('redirect', ['type' => 'success', 'message' => "Supplier Invoice Deleted Successfully."]);
          return Redirect::to('daily_report/daily_book_purchase/view/index');



    }
	
	public function addStockInvoice(Request $request, StockProducts $stockProducts){
		DB::beginTransaction();
		
		try{
			$request->supplier = is_array($request->supplier) ? $request->supplier['value'] :  $request->supplier;
			$request->product = is_array($request->product) ? $request->product['value'] :  $request->product;
			
			$invoice = \App\Models\SupplierInvoice::create([
				'supplier_id' =>  $request->supplier,
				'invoice_type' =>  'advance',
				'salesman_id' =>  '1',
			]);
			
			$product = \App\Models\SupplierInvoiceProduct::create([
				'supplier_invoice_id' => $invoice->id,
				'supplier_id' => $request->supplier,
				'product_id' => $request->product,
				'quantity' => $request->quantity,
				'unit_price' => $request->price,
				'sub_total' => (int)$request->quantity  * (int)$request->price,
				'product_info' => Product::find($request->product)
			]);
			
			$order = \App\Models\SupplierInvoiceOrder::create([
				'supplier_invoice_id' => $invoice->id,
				'supplier_id' => $request->supplier,
				'sub_total' => (int)$request->quantity  * (int)$request->price,
				'total' => (int)$request->quantity  * (int)$request->price,
				'vat' => 0,
				'porterage' => 0
			]);
			
			// add to stock.
			$stockProducts->recordStock([
				'supplier_id' => $invoice->supplier_id,
				'product_id' => $request->product,
				'stock' => $request->quantity,
				'type' => 'supplier',
				'invoice_id' => $invoice->id,
				'event' => 'stock_added',
				'price' => $request->price,
				'ref_id' => $product->id
			]);
		
			DB::commit();
			
			$supplier = Supplier::find($product->supplier_id);
			$suppliers = SupplierInvoiceProduct::getProductSuppliers($product->product_id);
			
			return $this->successResponse(
			["suppliers" => $suppliers, "current" => 
				["label" => (explode(' ', $supplier->name)[0])."...|Qty:".$product->quantity."|P:".$product->unit_price.'|'.$product->remarks, 
					"value" => ["product" => $product->product_id, 
					"supplier" => $product->supplier_id , 
					"supplier_invoice" => $product->supplier_invoice_id,
					"supplier_invoice_product_id" => $product->id]]
					]
			);
		
		}catch(\Excepttion $ex){
			DB::rollback();
			$this->exceptionResponse($ex);
		}
		
	}
	
	public function deleteInvoiceProducts(Request $request, \App\Services\SalesPurchaseValidations $salesPurchaseValidations){
		
		$success = [];
		$error = [];
		
		foreach($request->all() as $r){
			try{
				if($r['invoiceproductid'] != 0){
					$e = $salesPurchaseValidations->canDeletePurchaseEntryFromProductInvoiceId($r['invoiceproductid']);
				}
			}catch(\Exception $ex){
			
			}
		}
		
	}
	
	public function changeSupplier(Request $request){
		try{
			if(!$request->has('invoice_id') || !$request->has('supplier_id')){
				throw new \Exception('Invoice ID and Supplier ID required!');
			}
			$supplier = Supplier::findOrFail($request->supplier_id);
			SupplierInvoice::where('id', $request->invoice_id)->update(['supplier_id' => $supplier->id]);
			SupplierPayment::where('supplier_invoice_id', $request->invoice_id)->update(['supplier_id' => $supplier->id]);
			return $this->successResponse(['supplier_name' => $supplier->name]);
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}

	public function changeOtherInvoiceId(Request $request){
		try{
			if(!$request->has('id')){
				throw new \Exception('Invoice Id required!');
			}
			if(empty($request->input('id'))){
				throw new \Exception('Invoice Id required!');
			}
			$update = [];
			if($request->has('other_invoice_id')){
				$update['other_invoice_id'] = $request->other_invoice_id;
			}
			if($request->has('date')){
				$update['created_at'] = $request->date;
			}
			if(!empty($update)){
				SupplierInvoice::where('id',$request->id)->update($update);
			}
			return $this->successResponse(true);

		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}
}
