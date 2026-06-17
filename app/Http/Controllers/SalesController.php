<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\Product;
use App\Models\Payment;
use App\Models\CustomerInvoiceOrder;
use App\Models\CustomerInvoiceProduct;
use App\Models\StockProduct;
use App\Models\CompanyDetailModel;
use App\Models\InvoicePayment;
use App\Mail\InvoiceMail;
use App\Http\Controllers\Concerns\SendsResendMail;
use Validator;
use Response;
use Session;
use DB;
use Illuminate\Support\Facades\Mail;
use Auth;
use App\Lib\Response as CustomResponse;
use \PDF;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use App\Services\StockProducts;
use Redirect;

class SalesController extends Controller
{
    use CustomResponse;
    use SendsResendMail;

    protected $porterage = 5.25;

    protected $vat = 2;

    public function index(Request $request)
    {

        return view('sales.index');
    }

    public function create(Request $request)
    {
        // Redirect to new invoice form instead of auto-creating
        return redirect()->route('data_entry.sales_entry.invoice.new');
    }

    public function newInvoiceForm()
    {
        return view('sales.new-invoice');
    }

    public function generateSalesInvoice(Request $request, StockProducts $stockProducts)
    {
        DB::beginTransaction();
        try {
            // Determine customer
            $c_id = $request->customer_id;
            if (!$c_id) {
                $findcustomer = Customer::where('name', 'Guest')->first();
                if ($findcustomer) {
                    $c_id = $findcustomer->id;
                } else {
                    $cusObj = new Customer();
                    $cusObj->name = 'Guest';
                    $cusObj->is_active = 1;
                    $cusObj->save();
                    $cusObj->customer_id = 'C'.(100 + $cusObj->id);
                    $cusObj->update();
                    $c_id = $cusObj->id;
                }
            }

            $obj = new CustomerInvoice();
            $obj->customer_id = $c_id;
            $obj->salesman_id = Auth::user()->id;
            $obj->status = 1;
            if ($request->date) {
                $obj->created_at = $request->date . ' ' . date('H:i:s');
            } else {
                $obj->created_at = date('Y-m-d H:i:s');
            }
            $obj->updated_at = date('Y-m-d H:i:s');
            $obj->save();

            \App\Services\CustomerPayments::initiateInvoice($obj->id, $c_id);

            // Add products if provided
            if ($request->has('products') && is_array($request->products) && count($request->products) > 0) {
                $getinvoiceinformation = getinvoiceinformation();
                $getporterage = $getinvoiceinformation['porterage'];
                $getvat = $getinvoiceinformation['vat'];
                $subTotal = 0;

                foreach ($request->products as $product) {
                    $productModel = Product::find($product['product_id']);
                    $subtotalRow = $product['quantity'] * $product['price'];
                    $subTotal += $subtotalRow;

                    $data = [
                        'product_id' => $product['product_id'],
                        'supplier_id' => $product['supplier_id'],
                        'quantity' => $product['quantity'],
                        'remarks' => $product['remarks'] ?? '',
                        'supplier_invoice_product_id' => $product['supplier_invoice_product_id'],
                        'unit_price' => $product['price'],
                        'sub_total' => $subtotalRow,
                        'customer_id' => $c_id,
                        'supplier_invoice_id' => $product['supplier_invoice_id'],
                        'customer_invoice_id' => $obj->id,
                        'product_info' => json_encode($productModel),
                    ];

                    $save = CustomerInvoiceProduct::create($data);

                    // Record stock
                    $stockProducts->recordStock([
                        'supplier_invoice_product_id' => $product['supplier_invoice_product_id'],
                        'supplier_invoice_id' => $product['supplier_invoice_id'],
                        'customer_id' => $c_id,
                        'product_id' => $product['product_id'],
                        'stock' => $product['quantity'],
                        'type' => 'customer',
                        'invoice_id' => $obj->id,
                        'event' => 'stock_consumed',
                        'price' => $product['price'],
                        'ref_id' => $save->id,
                    ]);
                }

                // Create/update invoice order totals
                $total = $subTotal + $getporterage + $getvat;
                $cio = new CustomerInvoiceOrder();
                $cio->customer_invoice_id = $obj->id;
                $cio->customer_id = $c_id;
                $cio->sub_total = $subTotal;
                $cio->total = $total;
                $cio->vat = $getvat;
                $cio->porterage = $getporterage;
                $cio->created_at = date('Y-m-d H:i:s');
                $cio->updated_at = date('Y-m-d H:i:s');
                $cio->save();
            }

            DB::commit();
            return response()->json(['success' => true, 'invoice_id' => $obj->id]);
        } catch(\Exception $ex) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        }
    }

    public function grid(Request $request) {
        $params = $request->all();

        $start = $params['start'];
        $length = $params['length'];
        $draw = $params['draw'];

        $query = new CustomerInvoice();

        if (isset($params['search']['value']) && !empty($params['search']['value']) ) {
            //$query = $query->where('name', 'like', '%'.$params['search']['value'].'%');
        }

        $recordsFiltered = $query->count();
        $data = $query->with('customer')->with('order')->offset($start)->limit($length)->get();

        $recordsTotal = count($data);

        echo json_encode([
            "draw" => $draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data
        ]);
    }

    public function store(Request $request, \App\Services\CustomerPayments $customerPayments)
    {
        try {
			$c_id = 0;
			$old_invlice_present = 0;
			// check any pending invoice is present for today.
			$past_id = (new CustomerInvoice())->getNotUsedInvoice();
			
			if(!empty($past_id)){
				$obj = CustomerInvoice::where('id',$past_id->id)->first();
			}else{
				$obj = new CustomerInvoice();
			}	
			
            if($request->input('customer') =="#"){
             $findcustmer = Customer::where('name','Guest')->first();
			 
             if($findcustmer){
               $customerID = $obj->customer_id = $findcustmer->id;
			   $c_id = $customerID = $obj->customer_id;
             }else{
               $cusObj = new Customer();
			   $c_id = $cusObj->id;
               // $cusObj->name = 'Guest '.Customer::latest()->first()->id;
               $cusObj->name = 'Guest';
               $cusObj->is_active = 1;
               $record = $cusObj->save();
               $insertedId = $cusObj->id;
               $customerID = 'C'.(100+$insertedId);
               $cusObj->customer_id = $customerID;
               $recordUpdate = $cusObj->update();
               $obj->customer_id = $insertedId;
             }
            } else {
                $obj->customer_id = $request->input('customer');
				$c_id = $obj->customer_id;
            }
            $obj->salesman_id = Auth::user()->id;
			$obj->created_at = date('Y-m-d H:i:s');
			$obj->updated_at = date('Y-m-d H:i:s');
            
			
			$obj->save();
			
			/*
			if(!empty($past_id)){
				$invicepaymeant = InvoicePayment::where('customer_invoice_id',$past_id->id)->first();
			}else{
				$invicepaymeant = new InvoicePayment();
			}
			
            $invicepaymeant->customer_invoice_id = $obj->id;
            $invicepaymeant->payment_id = 1;
			$invicepaymeant->created_at = date('Y-m-d H:i:s');
			$invicepaymeant->updated_at = date('Y-m-d H:i:s');
            $invicepaymeant->save();
			*/
			$customerPayments::initiateInvoice($obj->id, $c_id);
			
			// initiate invoice new.
			//echo $obj->id; echo $c_id;            
            \Session::flash('redirect', ['type' => 'none', 'message' => "Invoice Created Successfully."]);
            return $this->successResponse(['redirect' => route('data_entry.sales_entry.invoice.index',['invoice' => $obj->id])]);
        }catch(\Exception $ex){

        }
    }

    public function edit(Request $request, $invoice)
    {

        $i = CustomerInvoice::where('id', $invoice)->first();
        if(empty($i)){
            abort(404);
        }
        $showSuppliers = (int) (\App\Models\GeneralSetting::where('setting', 'show_suppliers')->value('status') ?? 1);
        return view('sales.edit',['invoice' => $invoice, 'showSuppliers' => $showSuppliers]);
    }

    public function storeProducts(Request $request, $invoice)
    {
        return view('sales.edit');
    }

    public function ajaxProductsList(){
        // Cache for 5 minutes — products list is large (~60KB) and changes infrequently.
        // Per-tenant cache key so each domain has its own copy.
        $cacheKey = 'sales_products_list_' . (request()->getHost() ?: 'default');
        $productsList = \Cache::remember($cacheKey, 300, function () {
            return (new Product())->get();
        });
        return json_encode($productsList);
    }

    public function ajaxPaymentsList(){
        // Cache for 10 minutes — payment methods are static reference data.
        $cacheKey = 'sales_payments_list_' . (request()->getHost() ?: 'default');
        $paymentsList = \Cache::remember($cacheKey, 600, function () {
            return (new Payment())->get();
        });
        return json_encode($paymentsList);
    }

    public function ajaxCreateInvoice(Request $request){

       $getcustmmoreinvoice = CustomerInvoiceOrder::where('customer_invoice_id',$request->invoiceId)->first();
        if($getcustmmoreinvoice){
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

            $getCustomerInvoice = CustomerInvoice::find($request->input('invoiceId'));
            foreach($request->input('rowsdata') as $key => $value){
                $data['product_id'] = $value['product'];
                $data['quantity'] = $value['quantity'];
                $data['unit_price'] = $value['price'];
                $data['sub_total'] = $value['totalPrice'];
                $data['customer_id'] = $getCustomerInvoice['customer_id'];
                $data['customer_invoice_id'] = $request->input('invoiceId');
                $data['product_info'] = json_encode(Product::where('id',$value['product'])->first());
                $total[] = $value['quantity'] * $value['price'];

                // CustomerInvoiceProduct::create($data);
            }

            $cio = new CustomerInvoiceOrder();
            $cio->customer_invoice_id = $request->input('invoiceId');
            $cio->customer_id = $getCustomerInvoice['customer_id'];
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
        $getcustmmoreinvoice = CustomerInvoiceOrder::where('customer_invoice_id',$request->invoiceId)->first();
        if($getcustmmoreinvoice){
             return response()->json([
                'server' => $this->serverResponse(),
                        'status' => '208',
            ]);
         }

        try {
                $getCustomerInvoice = CustomerInvoice::find($request->invoiceId);

                if($request->invoiceproductid == 0){
                    $data['product_id'] = $request->product;
                    $data['quantity'] = $request->quantity;
                    $data['unit_price'] = $request->price;
                    $data['sub_total'] = $request->totalPrice;
                    $data['customer_id'] =$getCustomerInvoice['customer_id'];
                    $data['customer_invoice_id'] = $request->invoiceId;
                    $data['product_info'] = json_encode(Product::where('id',$request->product)->first());
                    $save = CustomerInvoiceProduct::create($data);
                    return $this->successResponse(['invoiceproductid' => $save->id,'indexvalue' => $request->indexvalue]);


                }else{

                    $updateData = CustomerInvoiceProduct::find($request->invoiceproductid);
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
    public function ajaxDeleteSingalInvoice(Request $request, StockProducts $stockProducts, \App\Services\DBCountBlocks $DBCountBlocks){
		DB::rollback();
		try {
            $getdata = CustomerInvoiceProduct::where('customer_invoice_id', $request->invoiceId)->count();
            if($getdata == 1){
				$deleteproduct  = CustomerInvoiceProduct::where('id', $request->invoiceproductid)->delete();
				$deletecustmor = CustomerInvoiceOrder::where('customer_invoice_id', $request->invoiceId)->delete();
				$pdfshow = 0;
				$deleteproduct  = CustomerInvoiceProduct::where('id', $request->invoiceproductid)->update(['is_archive'=>1]);
            }else{
                /*$getproductdetail = CustomerInvoiceProduct::where('id', $request->invoiceproductid)->first();
				$subtotal =  $getproductdetail->sub_total;
				$getinvoicedetail = CustomerInvoiceOrder::where('customer_invoice_id', $request->invoiceId)->first();
                $invoicesub_total = $getinvoicedetail->sub_total;
                $invoicetotal = $getinvoicedetail->total;
                $finalsubtotal = $invoicesub_total - $subtotal;
                $finaltotal = $invoicetotal - $subtotal;
                $getinvoicedetail->sub_total =  $finalsubtotal;
                $getinvoicedetail->total = $finaltotal;
                $update = $getinvoicedetail->update();
                if($update){
                $deleteproduct  = CustomerInvoiceProduct::where('id', $request->invoiceproductid)->update(['is_archive'=>1]);
                $pdfshow = 1;
                }*/
				$deleteproduct  = CustomerInvoiceProduct::where('id', $request->invoiceproductid)->update(['is_archive'=>1]);
                $pdfshow = 1;
            }
			
			// stock record.
			$stockProducts->removeStock([
				'type' => 'customer',
				'invoice_id' => $request->invoiceId,
				'event' => 'customer_stock_deleted',
				'ref_id' => $request->input('invoiceproductid')
			]);
						
			DB::commit();
			return $this->successResponse([
				'invoiceproductid' =>$request->invoiceproductid, 
				'pdfshow' =>$pdfshow, 
				//'stock' => $DBCountBlocks::invoiceStockCount($request->invoiceId)]
				//'stock' => StockProduct::customerSupplierStock([$request->invoiceId],$request->input('invoiceproductid'))
				'stock' => []
				]
			);

        }catch(\Exception $ex){
			DB::rollback();
            return $this->exceptionResponse($ex);
        }
    }
    public function invoiceview($id){

        $getinvoiceinformation = getinvoiceinformation();
        $data =  CustomerInvoice::where('id',$id)->with('product')->with('order')->first();
        $companyDetails = CompanyDetailModel::first();

        return view('sales.invoice',compact('data','companyDetails'));
    }
	
	public function invoiceviewDelivery($id){

        $getinvoiceinformation = getinvoiceinformation();
        $data =  CustomerInvoice::where('id',$id)->with('product')->with('order')->first();
        $companyDetails = CompanyDetailModel::first();

        return view('sales.invoice-delivery',compact('data','companyDetails'));
    }
	
    public function invoicedownload($id){

        $data =  CustomerInvoice::where('id',$id)->with('product')->with('order')->first();
        $companyDetails = CompanyDetailModel::first();

		//return view('invoice', compact('data', 'companyDetails'));
		$pdf = PDF::loadView('invoice',compact('data','companyDetails'));
		//return $pdf->stream('pdf_file.pdf');
		return $pdf->download('pdf_file.pdf');
  }

  public function invoiceExcel($id){
		$invoice = CustomerInvoice::where('id', $id)
			->with(['product', 'customer', 'invoicePayment'])
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
			$grandTotal += $sub;
			$rows[] = [
				$p->product->name ?? ($p->product_name ?? ''),
				$p->remarks ?? '',
				$qty,
				number_format($price, 2),
				number_format($sub, 2),
			];
		}

		// Total row
		$rows[] = ['TOTAL', '', '', '', number_format($grandTotal, 2)];

		$headings = ['Product', 'Remarks', 'Qty', 'Price (' . $currency . ')', 'Total (' . $currency . ')'];

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

		$customerName = $invoice->customer->name ?? 'customer';
		$customerSlug = preg_replace('/[^A-Za-z0-9]+/', '-', $customerName);
		$fileName = "invoice-{$invoice->id}-{$customerSlug}.xlsx";
		return \Maatwebsite\Excel\Facades\Excel::download($export, $fileName);
  }

  public function ajaxfetchInvoiceDetail(Request $request){

    try {

        $dataget = CustomerInvoice::where('id', $request->getInvoiceId)->with(['customer', 'invoicePayment'])->first();
        if (!$dataget) {
            return $this->errorResponse('Invoice not found');
        }
        $data = $dataget->toArray();
        $data['payment_summary'] = \App\Services\CustomerPayments::details($request->getInvoiceId);
        return json_encode($data);
        }catch(\Exception $ex){
            $this->exceptionResponse($ex);
        }


  }


  public function ajaxCreateSingalInvoicenew(Request $request, 
	StockProducts $stockProducts,
	\App\Services\SalesPurchaseValidations $salePurchaseValidation,
	\App\Services\DBCountBlocks $DBCountBlocks,
	\App\Services\CustomerPayments $customerPayments){ 
		
	$showSuppliers = (bool) (\App\Models\GeneralSetting::where('setting', 'show_suppliers')->value('status') ?? 1);

	$rules = [
		'product' => ['required', 'array'],
		'product.label' => ['required', 'string'],
		'product.value' => ['required','integer'],
		'quantity' => ['required','numeric'],
		'price' => ['nullable','numeric']
	];

	if ($showSuppliers) {
		$rules['supplier_id'] = ['required', 'array'];
		$rules['supplier_id.label'] = ['required', 'string'];
		$rules['supplier_id.value.supplier_invoice'] = ['required','integer'];
		$rules['supplier_id.value.supplier'] = ['required','integer'];
		$rules['supplier_id.value.product'] = ['required','integer'];
		$rules['supplier_id.value.supplier_invoice_product_id'] = ['required','integer'];
	}

	$validator = Validator::make($request->all(), $rules);
	if ($validator->fails()) {
		return $this->errorResponse(json_encode($validator->errors()));
	}

	$request->product = is_array($request->product) ? $request->product['value'] : $request->product;

	if ($showSuppliers) {
		if (!$request->has('supplier_id')) {
			return $this->errorResponse("Supplier Entry Is Required!");
		}
		// supplier invoice ID.
		$request->supplier_invoice_product_id = is_array($request->supplier_id) ? $request->supplier_id['value']['supplier_invoice_product_id'] : $request->supplier_invoice_product_id;
		$request->invoice_id = is_array($request->supplier_id) ? $request->supplier_id['value']['supplier_invoice'] : $request->supplier_invoice;
		$request->supplier_id = is_array($request->supplier) ? $request->supplier_id['value']['supplier'] : $request->supplier;
		// validation: should not add quantity more than stock.
		try {
			$e = $salePurchaseValidation->canAddSaleEntryFromInvoice($request->supplier_invoice_product_id, $request->quantity);
		} catch(\Exception $ex) {
			return $this->errorResponse($ex->getMessage());
		}
	} else {
		$request->supplier_invoice_product_id = 0;
		$request->invoice_id = 0;
		$request->supplier_id = 0;
		// Supplier Required (show_suppliers) is OFF: do NOT enforce stock /
		// quantity-availability validation — the sale is allowed regardless of how
		// much stock the product has on hand.
	}
	
	// validation: only one combination of product is allowed to add. (supplier_invoice_id, customer_invoice_id, supplier_id, product_id)
	/**
	 * @description: settle with the unique(index) combinations of keys in table: customer_invoice_products
	 */
	DB::beginTransaction();
	try{
		$getinvoiceinformation = getinvoiceinformation();
		$getporterage = $getinvoiceinformation['porterage'];
		$getvat = $getinvoiceinformation['vat'];

		$getcustmmoreinvoice = CustomerInvoiceOrder::where('customer_invoice_id',$request->invoiceId)->first();

		$getCustomerInvoice = CustomerInvoice::find($request->invoiceId);
		
		// update just to avoid new ID generation.
		CustomerInvoice::where('id',$request->invoiceId)->update(['status'=>1]);
		
		$supplier = \App\Models\SupplierInvoiceProduct::getProductSupplier($request->product,$request->supplier_id);
		
		if(empty($supplier)){
			//return $this->errorResponse("No supplier found for this product.");
		}
		if($supplier && $request->input('quantity') > $supplier->total_quantity){
			//return $this->errorResponse("Total stock left is ".$supplier->total_quantity. " for Product: ".$supplier->product->name.", Supplier: ".$supplier->supplier->name);
		}
		
		if(!$getcustmmoreinvoice){
			$product = Product::where('id',$request->product)->first();
			$data['product_id'] = is_array($request->product) ? $request->product['value'] : $request->product;
			$data['supplier_id'] = $request->supplier_id;
			$data['quantity'] = $request->quantity;
			$data['remarks'] = $request->remarks;
			$data['supplier_invoice_product_id'] = $request->supplier_invoice_product_id;
			$data['unit_price'] = $request->price;
			$data['sub_total'] = $request->totalPrice;
			$data['customer_id'] = $getCustomerInvoice['customer_id'];
			// supplier invoice ID.
			$data['supplier_invoice_id'] = $request->invoice_id;
			$data['customer_invoice_id'] = $request->invoiceId;
			$data['product_info'] = json_encode($product);
			
			$save = CustomerInvoiceProduct::create($data);


			$subtotal = $request->quantity * $request->price;
			$total = $subtotal + $getporterage + $getvat;
			$cio = new CustomerInvoiceOrder();
			$cio->customer_invoice_id = $request->input('invoiceId');
			$cio->customer_id = $getCustomerInvoice['customer_id'];
			$cio->sub_total = $subtotal;
			$cio->total = $total;
			$cio->vat = $getvat;
			$cio->porterage = $getporterage;
			$cio->created_at = date('Y-m-d H:i:s');
			$cio->updated_at = date('Y-m-d H:i:s');

			$cio->save();

			if ($showSuppliers && $request->supplier_invoice_product_id) {
				$stockProducts->recordStock([
					'supplier_invoice_product_id' => $request->supplier_invoice_product_id,
					'supplier_invoice_id' => $request->invoice_id,
					'customer_id' => $getCustomerInvoice['customer_id'],
					'product_id' => $data['product_id'],
					'stock' => $request->quantity,
					'type' => 'customer',
					'invoice_id' => $request->invoiceId,
					'event' => 'stock_consumed',
					'price' => $request->price,
					'ref_id' => $save->id
				]);
			}

			DB::commit();
			return $this->successResponse([
				'invoiceproductid' => $save->id,
				'indexvalue' => $request->indexvalue,
				'stock' => $DBCountBlocks::invoiceStockCount($request->invoiceId),
				'stock_selected_row' => $request->supplier_invoice_product_id ? $DBCountBlocks::productStockCount($request->supplier_invoice_product_id) : null
			]);
		}else{
			$product = Product::where('id',$request->product)->first();
            $data['product_id'] = is_array($request->product) ? $request->product['value'] : $request->product;
            $data['supplier_id'] = $request->supplier_id;
            $data['quantity'] = $request->quantity;
			$data['remarks'] = $request->remarks;
			$data['supplier_invoice_product_id'] = $request->supplier_invoice_product_id;
            $data['unit_price'] = $request->price;
            $data['sub_total'] = $request->totalPrice;
            $data['customer_id'] =$getCustomerInvoice['customer_id'];
			// supplier invoice ID.
			$data['supplier_invoice_id'] = $request->invoice_id;
            $data['customer_invoice_id'] = $request->invoiceId;
            $data['product_info'] = json_encode($product);
			
			$save = CustomerInvoiceProduct::create($data);
			//print_r($save); exit;

            $subtotal = $request->quantity * $request->price;
            $allsubtotal = $subtotal + $getcustmmoreinvoice->sub_total;
            $alltotal = $subtotal + $getcustmmoreinvoice->total;
            $getcustmmoreinvoice->sub_total = $allsubtotal;
            $getcustmmoreinvoice->total = $alltotal;

			if ($showSuppliers && $request->supplier_invoice_product_id) {
				$stockProducts->recordStock([
					'supplier_invoice_product_id' => $request->supplier_invoice_product_id,
					'supplier_invoice_id' => $request->invoice_id,
					'customer_id' => $getCustomerInvoice['customer_id'],
					'product_id' => $data['product_id'],
					'stock' => $request->quantity,
					'type' => 'customer',
					'invoice_id' => $request->invoiceId,
					'event' => 'stock_consumed',
					'price' => $request->price,
					'ref_id' => $save->id
				]);
			}

            $update = $getcustmmoreinvoice->update();
			DB::commit();
            //return $this->successResponse(['invoiceproductid' => $save->id,'indexvalue' => $request->indexvalue]);
			return $this->successResponse([
				//'invoiceproductid' => ['label' => $product->name, 'value' => $save->id],
				'invoiceproductid' => $save->id,
				'indexvalue' => $request->indexvalue,
				'stock' => $DBCountBlocks::invoiceStockCount($request->invoiceId),
				'stock_selected_row' => $request->supplier_invoice_product_id ? $DBCountBlocks::productStockCount($request->supplier_invoice_product_id) : null
			]);

			}
		}catch (QueryException $e) {
			DB::rollback();
			if ($e->getCode() == 23000) {
				return $this->errorResponse('This combination of supplier, customer, and product already exists.');
			}
			return $this->exceptionResponse($e);
		}
	}
	
	public function customers(Request $request)
    {
        return $this->successResponse(\App\Models\Customer::getActive());
    }
	
	public function print(Request $request)
    {
		$customer_id = $start_date = $end_date = "";
		extract($request->only('customer_id', 'start_date', 'end_date'));
		$invoiceIds = $request->input('invoices', []);

		$data = (new \App\Models\CustomerInvoice());

		if (!empty($start_date)) {
			$data = $data->whereDate('created_at', '>=', $start_date);
		}
		if (!empty($end_date)) {
			$data = $data->whereDate('created_at', '<=', $end_date);
		}
		if (!empty($customer_id)) {
			$data->where('customer_id', $customer_id);
		}
		if (!empty($invoiceIds)) {
			$data->whereIn('id', (array) $invoiceIds);
		}

		$invoices = $data->withSum(['products as total' => function ($q) {
				$q->where('is_archive', 0);
			}], 'sub_total')
			->withSum(['payments as total_paid' => function ($q) {
				$q->where('initiated', 0);
			}], 'amount')
			->with('customer')
			->with(['payments' => function($q){
				$q->where('initiated', 0)
					->with('paymentMode')
					->select('customer_invoice_id', 'payment_id', \DB::raw('SUM(amount) as total_amount'))
					->groupBy('customer_invoice_id', 'payment_id');
			}])
			->orderBy('created_at', 'desc')
			->get()->map(function($invoice){
				$invoice->payments_list = $invoice->payments->map(function($p){
					return [
						'total_amount' => $p->total_amount,
						'payment_mode_type' => $p->paymentMode->type ?? null,
					];
				})->toArray();
				return $invoice;
			});

		$companyDetails = \App\Models\CompanyDetailModel::first();
		$currency = env('CURRENCY_SYMBOL', '£');

		return view('daily-report.sales-print', compact('invoices', 'start_date', 'end_date', 'companyDetails', 'currency'));
    }
	
	public function statementDailyBookSales(Request $request)
	{
		$start_date = $request->start_date;
		$end_date = $request->end_date;
		$customer_id = $request->customer_id;

		$data = (new \App\Models\CustomerInvoice());

		if (!empty($start_date)) {
			$data = $data->whereDate('created_at', '>=', $start_date);
		}
		if (!empty($end_date)) {
			$data = $data->whereDate('created_at', '<=', $end_date);
		}
		if (!empty($customer_id)) {
			$data->where('customer_id', $customer_id);
		}

		$invoices = $data->withSum(['products as total' => function ($q) {
				$q->where('is_archive', 0);
			}], 'sub_total')
			->with('customer')
			->with(['payments' => function($q){
				$q->where('initiated', 0)
					->with('paymentMode')
					->select('customer_invoice_id', 'payment_id', DB::raw('SUM(amount) as total_amount'))
					->groupBy('customer_invoice_id', 'payment_id');
			}])
			->get()->map(function($invoice){
				$totalPaid = [];
				foreach($invoice->payments as $payment){
					$totalPaid[] = $payment->total_amount;
				}
				$invoice->total_paid = array_sum($totalPaid);
				return $invoice;
			});

		$currency = env('CURRENCY_SYMBOL', '£');
		$rows = [];
		foreach ($invoices as $invoice) {
			$total = (float)($invoice->total ?? 0);
			$paid = (float)($invoice->total_paid ?? 0);
			$status = ($paid >= $total && $total > 0) ? 'Paid' : ($paid > 0 ? 'Partial' : 'Unpaid');
			// Build payments column (e.g. "Cash £50.00, Card £20.00")
			$paymentTotals = [];
			foreach ($invoice->payments as $p) {
				$mode = optional($p->paymentMode)->type;
				if (!$mode || $mode === 'Unknown') continue;
				$paymentTotals[$mode] = ($paymentTotals[$mode] ?? 0) + (float)$p->total_amount;
			}
			$paymentsText = '';
			foreach ($paymentTotals as $mode => $amt) {
				$paymentsText .= ($paymentsText ? ', ' : '') . $mode . ' ' . $currency . ' ' . number_format($amt, 2);
			}
			$rows[] = [
				'#' . $invoice->id,
				uk_ts($invoice->created_at, 'd M Y'),
				$invoice->customer->name ?? '',
				number_format($total, 2),
				$paid > 0 ? number_format($paid, 2) : '',
				$status,
				$paymentsText,
			];
		}

		$headings = ['Invoice No.', 'Date', 'Customer', 'Total (' . $currency . ')', 'Paid (' . $currency . ')', 'Status', 'Payments'];

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
		$fileName = "daily-sales-statement-{$range}.xlsx";
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

	public function emailDailyBookSales(Request $request)
	{
		$rules = [
			'to_email' => 'required|email',
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
		$customer_id = $request->customer_id;

		$data = (new \App\Models\CustomerInvoice());

		if (!empty($start_date)) {
			$data = $data->whereDate('created_at', '>=', $start_date);
		}
		if (!empty($end_date)) {
			$data = $data->whereDate('created_at', '<=', $end_date);
		}
		if (!empty($customer_id)) {
			$data->where('customer_id', $customer_id);
		}

		$invoices = $data->withSum(['products as total' => function ($q) {
				$q->where('is_archive', 0);
			}], 'sub_total')
			->with('customer')
			->with(['payments' => function($q){
				$q->where('initiated', 0)
					->with('paymentMode')
					->select('customer_invoice_id', 'payment_id', DB::raw('SUM(amount) as total_amount'))
					->groupBy('customer_invoice_id', 'payment_id');
			}])
			->get()->map(function($invoice){
				$invoice->total_paid = $invoice->payments->sum('total_amount');
				return $invoice;
			});

		if ($invoices->isEmpty()) {
			return response()->json([
				'success' => false,
				'payload' => 'No sales invoices found in the selected period. Nothing to email.',
			]);
		}

		// Build Excel attachment using the same export shape
		$companyDetails = \App\Models\CompanyDetailModel::first();
		$companyName = $companyDetails->company_name ?? 'R & A Veg Ltd';
		$currency = env('CURRENCY_SYMBOL', '£');

		$totalAmount = $invoices->sum('total');
		$totalPaid   = $invoices->sum('total_paid');
		$totalPending = $totalAmount - $totalPaid;

		$rows = [];
		foreach ($invoices as $invoice) {
			$total = (float)($invoice->total ?? 0);
			$paid = (float)($invoice->total_paid ?? 0);
			$status = ($paid >= $total && $total > 0) ? 'Paid' : ($paid > 0 ? 'Partial' : 'Unpaid');
			$paymentTotals = [];
			foreach ($invoice->payments as $p) {
				$mode = optional($p->paymentMode)->type;
				if (!$mode || $mode === 'Unknown') continue;
				$paymentTotals[$mode] = ($paymentTotals[$mode] ?? 0) + (float)$p->total_amount;
			}
			$paymentsText = '';
			foreach ($paymentTotals as $mode => $amt) {
				$paymentsText .= ($paymentsText ? ', ' : '') . $mode . ' ' . $currency . ' ' . number_format($amt, 2);
			}
			$rows[] = [
				'#' . $invoice->id,
				uk_ts($invoice->created_at, 'd M Y'),
				$invoice->customer->name ?? '',
				number_format($total, 2),
				$paid > 0 ? number_format($paid, 2) : '',
				$status,
				$paymentsText,
			];
		}
		$headings = ['Invoice No.', 'Date', 'Customer', 'Total (' . $currency . ')', 'Paid (' . $currency . ')', 'Status', 'Payments'];

		$export = new class($rows, $headings) implements
			\Maatwebsite\Excel\Concerns\FromArray,
			\Maatwebsite\Excel\Concerns\WithHeadings {
			protected $rows; protected $headings;
			public function __construct($rows, $headings) { $this->rows = $rows; $this->headings = $headings; }
			public function array(): array { return $this->rows; }
			public function headings(): array { return $this->headings; }
		};

		$emailRange = ($start_date && $end_date) ? "{$start_date}-to-{$end_date}" : 'all';
		$excelName = "daily-sales-statement-{$emailRange}.xlsx";
		$excelBinary = \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);

		$periodText = ($start_date && $end_date)
			? \Carbon\Carbon::parse($start_date)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($end_date)->format('d M Y')
			: 'All time';

		$defaultSubject = "Daily Sales Report — {$periodText}";
		$defaultMessage = "Dear team,\n\nPlease find attached the daily sales report for the period {$periodText}.\n\n" .
			"Total Invoices: " . $invoices->count() . "\n" .
			"Total Amount: " . $currency . " " . number_format($totalAmount, 2) . "\n" .
			"Total Paid: " . $currency . " " . number_format($totalPaid, 2) . "\n" .
			"Total Pending: " . $currency . " " . number_format($totalPending, 2);

		$mailData = [
			'company_name'  => $companyName,
			'report_title'  => 'Daily Sales Report',
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
			$this->sendMailable($request->to_email, new \App\Mail\DailyReportMail($mailData));
			return response()->json(['success' => true, 'payload' => 'Report emailed to ' . $request->to_email]);
		} catch (\Exception $ex) {
			return response()->json(['success' => false, 'payload' => 'Could not send email: ' . $ex->getMessage()]);
		}
	}

	public function list(Request $request)
    {
		// Read filters explicitly — avoids `extract()` swallowing typos/missing keys silently
		$customer_id = $request->input('customer_id');
		$start_date  = $request->input('start_date');
		$end_date    = $request->input('end_date');

		// Normalize: trim whitespace; treat empty/null/'null'/'undefined' as no filter
		$normalizeDate = function($v) {
			if ($v === null) return null;
			$v = trim((string)$v);
			if ($v === '' || $v === 'null' || $v === 'undefined') return null;
			// Accept either YYYY-MM-DD or anything Carbon can parse; always emit YYYY-MM-DD
			try { return \Carbon\Carbon::parse($v)->toDateString(); }
			catch (\Throwable $e) { return null; }
		};
		$start_date = $normalizeDate($start_date);
		$end_date   = $normalizeDate($end_date);
		// If user sent only one bound, mirror it so a single-day pick still filters strictly.
		if ($start_date && !$end_date) $end_date = $start_date;
		if ($end_date && !$start_date) $start_date = $end_date;
		// If reversed, swap.
		if ($start_date && $end_date && $start_date > $end_date) {
			[$start_date, $end_date] = [$end_date, $start_date];
		}

		$data = (new \App\Models\CustomerInvoice());

		if ($start_date && $end_date) {
			// Use a single whereBetween — guarantees both bounds get applied (vs two separate ifs)
			$data = $data->whereBetween(\DB::raw('DATE(created_at)'), [$start_date, $end_date]);
		} elseif ($start_date) {
			$data = $data->whereDate('created_at', '>=', $start_date);
		} elseif ($end_date) {
			$data = $data->whereDate('created_at', '<=', $end_date);
		}

		if(!empty($customer_id)){
			$data = $data->where('customer_id', $customer_id);
		}

		$data = $data->withSum(['products as total' => function ($q) {
				$q->where('is_archive', 0);
			}], 'sub_total')
			->withCount(['products as products_count' => function ($q) {
				$q->where('is_archive', 0);
			}])
			->with('customer')
			->with('salesman')
			->with(['payments' => function($q){
				$q->where(function($q){ $q->where('initiated', 0)->orWhereNull('initiated'); })
					->with('paymentMode')
					->select('customer_invoice_id', 'payment_id', DB::raw('MIN(id) as id'), DB::raw('SUM(amount) as total_amount'))
					->groupBy('customer_invoice_id', 'payment_id');
			}])
			->orderBy('created_at', 'desc')
			->get()->map(function($invoice){
				// Fallback mode names keyed by payment_id — the `payments` (modes) lookup table can be
				// unseeded in some environments, leaving the relation/DB type null. These ids are fixed
				// app-wide: Cash=2, Cheque=3, Card=4, Bank Transfer=5, Credit=6.
				$modeNames = [2 => 'Cash', 3 => 'Cheque', 4 => 'Card', 5 => 'Bank Transfer', 6 => 'Credit'];
				$totalPaid = [];
				foreach($invoice->payments as $payment){
					$totalPaid[]= $payment->total_amount;
				}
				// Check for credit usage and subtract from cash payment display
				$paymentIds = $invoice->payments->pluck('id')->filter()->values()->toArray();
				$creditTotal = count($paymentIds) > 0
					? \App\Models\CustomerCreditUsage::whereIn('customer_payment_id', $paymentIds)->sum('amount')
					: 0;
				if ($creditTotal > 0) {
					// Subtract credit from the cash payment total_amount for display
					$paymentsArr = $invoice->payments->map(function($p) use ($creditTotal, $modeNames) {
						$item = [
							'customer_invoice_id' => $p->customer_invoice_id,
							'payment_id' => $p->payment_id,
							'id' => $p->id,
							'total_amount' => ($p->id && $p->total_amount > $creditTotal) ? $p->total_amount - $creditTotal : $p->total_amount,
							'payment_mode_type' => $p->payment_mode_type ?? (optional($p->paymentMode)->type ?? ($modeNames[$p->payment_id] ?? null)),
						];
						return $item;
					})->toArray();
					$paymentsArr[] = [
						'customer_invoice_id' => $invoice->id,
						'payment_id' => null,
						'id' => null,
						'total_amount' => $creditTotal,
						'payment_mode_type' => 'Credit',
					];
					$invoice->setRelation('payments', collect());
					$invoice->payments_list = $paymentsArr;
				} else {
					$invoice->payments_list = $invoice->payments->map(function($p) use ($modeNames) {
						return [
							'customer_invoice_id' => $p->customer_invoice_id,
							'payment_id' => $p->payment_id,
							'id' => $p->id,
							'total_amount' => $p->total_amount,
							'payment_mode_type' => $p->payment_mode_type ?? (optional($p->paymentMode)->type ?? ($modeNames[$p->payment_id] ?? null)),
						];
					})->toArray();
				}
				$invoice->total_paid = array_sum($totalPaid);
				if($invoice->total_paid <= 0){
					$invoice->paid_type = 'not-paid';
				}else{
					if( $invoice->total > $invoice->total_paid ){
						$invoice->paid_type = 'partial-paid';
					} elseif( $invoice->total_paid > $invoice->total && $invoice->total > 0 ){
						$invoice->paid_type = 'overpaid';
					} else {
						$invoice->paid_type = 'all-paid';
					}
				}

				return $invoice;
			});
		return $this->successResponse($data);
		
    }
	
    public function dailyBookSales(){

        $allData = (new CustomerInvoice)->with(['customer', 'order'])->get();
        //return view('daily-report.sales',['data'=> $allData]);
        return view('daily-report.sales-new',['data'=> $allData]);
    }
    public function ajaxDailyBookSales(Request $request){
        $totalFilteredRecord = $totalDataRecord = $draw_val = "";
        $columns_list = array(0 =>'id',1 =>'created_at',2=> 'salesman',3=> 'id',4=> 'customer_name',5=> 'vat');
        $totalDataRecord = CustomerInvoice::whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->count();
        
		$totalFilteredRecord = $totalDataRecord;
        $limit = $request->input('length');
        $start = $request->input('start');
        $fromDate = $request->input('min');
        $toDate = $request->input('max');
        $order = $columns_list[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');
        $salesData = (new CustomerInvoice)->with(['customer','product', 'invoicePayment', 'invoicePayment.payment', 'salesman', 'order'])->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->offset($start)->limit($limit)->orderBy('created_at','desc')->get();
		//print_r($salesData->toArray());exit;
		if($order == 'customer_name'){
            $salesData = (new CustomerInvoice)->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->with(['order','product', 'invoicePayment', 'invoicePayment.payment', 'salesman', 'customer'=> function ($query) use($dir){
                $query->orderBy('name', $dir);
                }])->offset($start)->limit($limit)->get();
        } elseif($order == 'salesman'){
            $salesData = (new CustomerInvoice)->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->with(['customer','product', 'invoicePayment', 'invoicePayment.payment', 'order', 'salesman'=> function ($query) use($dir) {
                $query->orderBy('first_name', $dir);
                }])->offset($start)->limit($limit)->get();
        } elseif($order == 'vat'){
            $salesData = (new CustomerInvoice)->with(['customer','product', 'invoicePayment', 'invoicePayment.payment', 'salesman', 'order'=> function ($query) use($dir) {
                $query->orderBy('vat', $dir);
                }])->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->offset($start)->limit($limit)->get();
        } elseif(!empty($request->input('min'))){
            $salesData = (new CustomerInvoice)->with(['customer','product', 'invoicePayment', 'invoicePayment.payment', 'salesman', 'order'])->whereBetween('created_at', [$fromDate." 00:00:00", $toDate." 23:59:59"])->offset($start)->limit($limit)->orderBy($order,$dir)->get();
            $totalFilteredRecord = (new CustomerInvoice)->with(['customer', 'order'])->whereBetween('created_at', [$fromDate." 00:00:00", $toDate." 23:59:59"])->orderBy($order,$dir)->count();
        } elseif(!empty($request->input('search.value'))){
            $search_text = $request->input('search.value');
            $salesData =  (new CustomerInvoice)->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->whereHas('customer', function($query) use ($search_text){
                $query->where('name', 'like', '%'.$search_text.'%');
            })->orWhere('id', 'LIKE',"%{$search_text}%")->with(['customer','product', 'invoicePayment', 'invoicePayment.payment', 'order'])
            ->offset($start)
            ->limit($limit)
            ->orderBy($order,$dir)
            ->get();
            $totalFilteredRecord = (new CustomerInvoice)->whereBetween('created_at', [ Carbon::today()->toDateString()." 00:00:00",  Carbon::today()->toDateString()." 23:59:59"])->whereHas('customer', function($query) use ($search_text){
                $query->where('name', 'like', '%'.$search_text.'%');
            })->orWhere('id', 'LIKE',"%{$search_text}%")->with(['customer','product', 'invoicePayment', 'invoicePayment.payment', 'order'])
            ->count();
        }
        $sales = array();
		//print_r($salesData->toArray()); exit;
        if(!empty($salesData))
        {
            $index=1;
            foreach ($salesData as $sale)
            {
            if($sale->product->sum('sub_total') > 0){
			
					$printButton = "<span onClick='print(".$sale->id.", 0)'><i class='fa fa-regular fa-print btn btn-sm' title='Print'></i> Invoice Print</span>";
					$deliveryButton = "<span onClick='delivery(".$sale->id.", 0)'><i class='fa fa-regular fa-print btn btn-sm' title='Print'></i> Delivery Print</span>";
					$downloadButton = "<span onClick='download(".$sale->id.", 0)'><i class='fa fa-regular fa-download btn btn-sm' title='download'></i> Invoice Download</span>";

					$emailButton = "<span onClick='email(".$sale->id.", 0)'><i class='fa fa-envelope btn btn-sm' title='email'> </i> Invoice Email</span>";
			
                  $postnestedData['invoice_id'] = '<a href="'.route("data_entry.sales_entry.invoice.index",['invoice' => $sale->id]).'" ><b>'.$sale->id.'</b></a>';
                  //$postnestedData['created_at'] = \Carbon\Carbon::parse($sale->created_at)->format('Y-m-d');
                  $postnestedData['created_at'] = $sale->created_at;
				  $postnestedData['salesman'] = !empty($sale->salesman)?$sale->salesman->first_name:"";
                  $postnestedData['quantity'] = $sale->product->sum('quantity');
                  //$postnestedData['customer_name'] = "&emsp;<i class='fa fa-regular fa-print' onClick='print(".$sale->id.", 0)'></i>&emsp;<i class='fa fa-regular fa-download' onClick='download(".$sale->id.", 0)'></i>&emsp;<i class='fa fa-envelope' onClick='mail(".$sale->id.", 0)'></i>&emsp;". $sale->customer->name;
				  
				  $postnestedData['customer_name'] = "";
				  
				  /*if($sale->invoicePayment->payment->type == 'None'){
					$postnestedData['customer_name'] .= '<span class="text-danger"><span><a href="'.route("data_entry.sales_entry.invoice.index",['invoice' => $sale->id]).'" class="text-danger"><b>'.$sale->customer->name.'</b></a></span>';
				  }else{
					$postnestedData['customer_name'] .= '<span class=""><span><a href="'.route("data_entry.sales_entry.invoice.index",['invoice' => $sale->id]).'" class=""><b>'.$sale->customer->name.'</b></a></span>';
				  }*/
                  $postnestedData['customer_name'] .= '<span class=""><span><a href="'.route("data_entry.sales_entry.invoice.index",['invoice' => $sale->id]).'" class=""><b>'.$sale->customer->name.'</b></a></span>';
				  
				  /*$postnestedData['vat'] = !empty($sale->order)?$sale->order->vat:"";
                  $postnestedData['amount'] = formatTwoDecimalCurrenty($sale->product->sum('sub_total'));
                  $postnestedData['mode'] = !empty($sale->invoicePayment->payment)?$sale->invoicePayment->payment->type:"";
                  $postnestedData['action'] ='<span style="display:flex">'.$printButton.$downloadButton.$emailButton.'<a href="'.route("data_entry.sales_entry.invoice.index",['invoice' => $sale->id]).'" class="btn btn-primary mr-1 btn-sm"><i class="feather icon-edit"></i></a> <a href="#" onclick="deleteinvoice('.$sale->id.')" class="btn btn-primary mr-1 btn-sm"><i class="feather icon-delete"></i></a></span>';
				  */
				  $postnestedData['vat'] = 'NA';
                  $postnestedData['amount'] = 'NA';
                  $postnestedData['mode'] = 'NA';
                  $postnestedData['action'] = 'NA';
                  
				  //$postnestedData['balance'] = $sale->invoicePayment->payment->type == "None" ? formatTwoDecimalCurrenty($sale->product->sum('sub_total')) : "";
				  //$postnestedData['cash'] = $sale->invoicePayment->payment->type == 'Cash' ? $sale->product->sum('sub_total') : "";
				  //$postnestedData['cheque'] =  $sale->invoicePayment->payment->type == 'Cheque' ? $sale->product->sum('sub_total') : "";
				  //$postnestedData['card'] = $sale->invoicePayment->payment->type == 'Cheque' ? $sale->product->sum('sub_total') : "";
				  
				  $postnestedData['balance'] = 'NA';
				  $postnestedData['cash'] = 'NA';
				  $postnestedData['cheque'] =  'NA';
				  $postnestedData['card'] = 'NA';
				  
				 
				$postnestedData['action'] = '
					<div class="dropstart">
					  <button class="btn btn-warning btn-sm dropdown-toggle" type="button" id="actionDropdown'.$sale->id.'" data-bs-toggle="dropdown" aria-expanded="false">
						Actions
					  </button>
					  <ul class="dropdown-menu" style="min-width:188px;" aria-labelledby="actionDropdown'.$sale->id.'">
						<li class="cursor-pointer pb-1">
						  '.$printButton.'
						</li>
						<li class="cursor-pointer pb-1">
						  '.$deliveryButton.'
						</li>
						<li class="cursor-pointer pb-1">
						  '.$downloadButton.'
						</li>
						<li class="cursor-pointer pb-1">
						  '.$emailButton.'
						</li>
						<li class="cursor-pointer pb-1">
						  <span><a href="'.route("data_entry.sales_entry.invoice.index",['invoice' => $sale->id]).'" class=""><i class="btn btn-sm feather icon-edit"></i> Edit</a></span>
						</li>
						<li class="cursor-pointer pb-1">
							<span><a href="#" onclick="deleteinvoice('.$sale->id.')" class=""><i class="feather icon-delete btn btn-sm"></i> Delete</a></span>
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

    public function ajaxEditSingleInvoice(Request $request, 
		\App\Services\SalesPurchaseValidations $salePurchaseValidation,
		StockProducts $stockProducts,
		\App\Services\DBCountBlocks $DBCountBlocks){
			$showSuppliers = (bool) (\App\Models\GeneralSetting::where('setting', 'show_suppliers')->value('status') ?? 1);

			$rules = [
				'product.label' => ['required', 'string'],
				'product.value' => ['required','integer'],
				'quantity' => ['required','numeric'],
				'price' => ['nullable','numeric'],
				'customer_id' => ['required','numeric']
			];

			$hasSupplierData = is_array($request->supplier_id) && !empty($request->supplier_id['value']) && is_array($request->supplier_id['value']) && !empty($request->supplier_id['value']['supplier']);
			if ($hasSupplierData) {
				$rules['supplier_id'] = ['required', 'array'];
				$rules['supplier_id.label'] = ['required', 'string'];
				$rules['supplier_id.value.product'] = ['required','integer'];
				$rules['supplier_id.value.supplier'] = ['required','integer'];
				$rules['supplier_id.value.supplier_invoice'] = ['required','integer'];
				$rules['supplier_id.value.supplier_invoice_product_id'] = ['required','integer'];
			}

			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
				return $this->errorResponse(json_encode($validator->errors()));
			}

			// check total stock.
			$request->product = is_array($request->product) ? $request->product['value'] : $request->product;
			$request->invoiceproductid = is_array($request->invoiceproductid) ? $request->invoiceproductid['value'] : $request->invoiceproductid;

			if ($hasSupplierData && is_array($request->supplier_id)) {
				$request->supplier_invoice_product_id = $request->supplier_id['value']['supplier_invoice_product_id'];
				$request->invoice_id = $request->supplier_id['value']['supplier_invoice'];
				$request->supplier_id = $request->supplier_id['value']['supplier'];
				try {
					$e = $salePurchaseValidation->canEditSaleEntryQtyFromInvoice($request->supplier_invoice_product_id, $request->invoiceproductid, $request->quantity);
				} catch(\Exception $ex) {
					return $this->errorResponse($ex->getMessage());
				}
			} else {
				$request->supplier_invoice_product_id = 0;
				$request->invoice_id = 0;
				$request->supplier_id = 0;
				// Supplier Required (show_suppliers) ON but no supplier bound: guard qty
				// against available product stock. When Supplier Required is OFF, skip
				// the stock/quantity-availability check entirely (sale allowed regardless).
				try {
					if ($showSuppliers) $salePurchaseValidation->canAddSaleEntryByProductStock($request->product, $request->quantity, $request->invoiceproductid);
				} catch(\Exception $ex) {
					return $this->errorResponse($ex->getMessage());
				}
			}

			// validation: only one combination of product is allowed to add. (supplier_invoice_id, customer_invoice_id, supplier_id, product_id)
			/**
			 * @description: settle with the unique(index) combinations of keys in table: customer_invoice_products
			 */
			DB::beginTransaction();
			try{
				$supplier = \App\Models\SupplierInvoiceProduct::getProductSupplier($request->product,$request->supplier_id);
				//print_r($supplier->toArray()); exit;
				if(empty($supplier)){
					//return $this->errorResponse("No supplier found for this product.");
				}
				
				if($supplier && $request->input('quantity') > $supplier->total_quantity){
					//return $this->errorResponse("Total stock left is ".$supplier->total_quantity. " for Product: ".$supplier->product->name.", Supplier: ".$supplier->supplier->name);
				}
				
				$data= [
					'product_id'=>$request->product,
					'supplier_id' => $request->supplier_id,
					'supplier_invoice_id' => $request->invoice_id,
					'quantity'=>$request->input('quantity'),
					'supplier_invoice_product_id' => $request->supplier_invoice_product_id,
					'remarks'=>$request->input('remarks'),
					'unit_price'=>$request->input('price'),
					'sub_total'=>$request->input('totalPrice'),
					'product_info' => json_encode(Product::where('id',$request->product)->first())
				];
				//print_r($data); exit;
				CustomerInvoiceProduct::where('id',$request->invoiceproductid)->where('customer_invoice_id', $request->invoiceId)->update($data);

				if ($hasSupplierData && $request->supplier_invoice_product_id) {
					$stockProducts->recordStock([
						'supplier_invoice_product_id' => $request->supplier_invoice_product_id,
						'supplier_invoice_id' => $request->invoice_id,
						'customer_id' => $request->customer_id,
						'product_id' => $request->product,
						'stock' => $request->quantity,
						'type' => 'customer',
						'invoice_id' => $request->invoiceId,
						'event' => 'stock_consumed',
						'price' => $request->price,
						'ref_id' => $request->invoiceproductid
					]);
				}
				DB::commit();
				return $this->successResponse([
						'invoiceproductid' => $request->invoiceproductid,
						'indexvalue' => $request->input('indexvalue'),
						'stock' => $DBCountBlocks::invoiceStockCount($request->invoiceId),
						'stock_selected_row' => $request->supplier_invoice_product_id ? $DBCountBlocks::productStockCount($request->supplier_invoice_product_id) : null
					]
				);
			
			}catch (QueryException $e) {
				DB::rollback();
				if ($e->getCode() == 23000) {
					return $this->errorResponse('This combination of supplier, customer, and product already exists.');
				}
				return $this->exceptionResponse($e);
			}
	}
	
	public function ajaxSuppliersList($product_id){
		//$stock = StockProduct::stock(['product_id' => 10]);
		//print_r($stock->toArray()); exit;
		$data = \App\Models\SupplierInvoiceProduct::getProductSuppliers($product_id);
		return $this->successResponse($data);
	}
	
	public function ajaxSuppliersListAll(){
		$data = \App\Models\Supplier::get();
		return $this->successResponse($data);
	}	

    public function ajaxEditPayment(Request $request){

        try {
            $getInvoicePayment = (new InvoicePayment)->where('customer_invoice_id', $request->input('customer_invoice_id'))->first();
            if(!empty($getInvoicePayment)){
                (new InvoicePayment)->where('customer_invoice_id', $request->input('customer_invoice_id'))->update(['payment_id'=>$request->input('payment_id')]);
            } else{
                InvoicePayment::create($request->all());
            }
                $getData = InvoicePayment::where('customer_invoice_id', $request->input('customer_invoice_id'))->with('payment')->first();
                return $this->successResponse($getData);
            }catch(\Exception $ex){
                $this->exceptionResponse($ex);
            }
    }

  public function ajaxfetchInvoiceAllDetail($id){
	try {
		// Load invoice rows once — same as before
		$dataget = CustomerInvoiceProduct::where('customer_invoice_id', $id)
			->where('is_archive', 0)
			->with('supplier')
			->with(['product'])
			->get();

		if ($dataget->isEmpty()) {
			return json_encode([]);
		}

		// ── PERFORMANCE FIX (was N+1, now batched) ──────────────────────────────────
		// Old code: per-row calls to getProductSuppliers() + getProductSupplierInvoices(),
		//   each running its own stock query → ~600 DB hits for 6 products → 25s response.
		// New code: TWO batched queries cover every product on this invoice → ~500ms.
		$productIds = $dataget->pluck('product_id')->unique()->values()->all();

		// Pre-load supplier dropdown options for EVERY product in one shot (keyed by product_id)
		$suppliersByProduct = \App\Models\SupplierInvoiceProduct::getProductSuppliersBatch($productIds);

		// Pre-load the (supplier_invoice_id, supplier_invoice_product_id) → row tuple
		// so we can resolve each invoice row's "supplier_id" payload without re-querying.
		$selectedKeys = [];
		foreach ($dataget as $d) {
			if (!empty($d['supplier_invoice_id']) && !empty($d['supplier_invoice_product_id'])) {
				$selectedKeys[] = $d['supplier_invoice_id'] . '|' . $d['supplier_invoice_product_id'];
			}
		}
		$selectedRowsMap = [];
		if (!empty($selectedKeys)) {
			$selRows = \App\Models\SupplierInvoiceProduct::with(['supplier' => function($q){
					$q->select('id', 'supplier_id', 'name');
				}])
				->whereIn('id', $dataget->pluck('supplier_invoice_product_id')->filter()->unique()->values()->all())
				->get();
			foreach ($selRows as $sr) {
				$selectedRowsMap[$sr->supplier_invoice_id . '|' . $sr->id] = $sr;
			}
		}

		$products = [];
		foreach ($dataget as $data) {
			$suppliers = $suppliersByProduct[$data['product_id']] ?? [];

			$postnestedData = [];
			$postnestedData['product_id']      = $data['product_id'];
			$postnestedData['payment']         = '';
			$postnestedData['product']         = ['label' => $data['product'] ? $data['product']['name'] : 'Unknown', 'value' => $data['product_id']];
			$postnestedData['quantity']        = $data['quantity'];
			$postnestedData['remarks']         = $data['remarks'];
			$postnestedData['price']           = $data['unit_price'];
			$postnestedData['totalPrice']      = (float)$data['sub_total'];
			$postnestedData['fieldToggle']     = 'checked';
			$postnestedData['invoiceproductid'] = $data['id'];

			// Supplier dropdown options (same shape as before)
			$postnestedData['supplier'] = (function() use ($suppliers) {
				if (count($suppliers) === 0) return [];
				$arr = [];
				$i = 0;
				foreach ($suppliers as $sup) {
					if (!$sup->supplier) continue;
					$arr[$i] = ['label' => $sup->supplier->name];
					if ($sup->supplier->invoices) {
						foreach ($sup->supplier->invoices as $invoice) {
							$arr[$i]['options'][] = [
								'label' => $invoice->invoice_title,
								'sale_price' => $invoice->sale_price ?? null,
								'value' => [
									'supplier' => $invoice->supplier_id,
									'supplier_invoice' => $invoice->supplier_invoice_id,
									'product' => $invoice->product_id,
									'supplier_invoice_product_id' => $invoice->id,
								],
							];
						}
					}
					$i++;
				}
				return $arr;
			})();

			// Currently-selected supplier_id payload — resolved via the pre-loaded map (no extra query)
			$selKey = ($data['supplier_invoice_id'] ?? '') . '|' . ($data['supplier_invoice_product_id'] ?? '');
			$selRow = $selectedRowsMap[$selKey] ?? null;
			$postnestedData['supplier_id'] = $selRow ? [
				'label' => $selRow->supplier ? $selRow->supplier->name : 'Unknown',
				'value' => [
					'product'   => $selRow->product_id,
					'supplier'  => $selRow->supplier_id,
					'supplier_invoice' => $selRow->supplier_invoice_id,
					'supplier_invoice_product_id' => $selRow->id,
				],
			] : [];

			$products[] = $postnestedData;
		}
		return json_encode($products);
	} catch (\Exception $ex) {
		$this->exceptionResponse($ex);
	}
  }

  public function mail($id){
    $response = 0;
    $dataget =  CustomerInvoice::where('id',$id)->with('customer')->first();
    if(!empty($dataget->customer) && ($dataget->customer->email)){
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

            $customer =  \App\Models\Customer::where(['id' => $dataget->customer_id])->first();
            $data = [
            'id' => $dataget->id,
            'date' => $dataget->created_at,
            'type' => "Customer",
            'subtotal' => !empty($dataget->order)?$dataget->order->sub_total:"",
            'porterage' => !empty($dataget->order)?$dataget->order->porterage:"",
            'vat' => !empty($dataget->order)?$dataget->order->vat:"",
            'total' => !empty($dataget->order)?$dataget->order->total:"",
            'name' => $customer->name,
            'productdata' => $productdata,
        ];
        $this->sendMailable($dataget->customer->email, new InvoiceMail($data));
        $response = 1;
    } else {
        $response = 0;
    }
    return $response;
  }
  public function delete($id){

    $customerInvoice = CustomerInvoice::find($id);
    if($customerInvoice){
      $customerInvoiceOrder = CustomerInvoiceOrder::where('customer_invoice_id',$customerInvoice->id)->first();
      if($customerInvoiceOrder){
        $customerInvoiceOrderProduct = CustomerInvoiceProduct::where('customer_invoice_id',$customerInvoiceOrder->customer_invoice_id)->delete();
          $customerInvoiceOrder->delete();

      }
      $customerInvoice->delete();
    }


        \Session::flash('redirect', ['type' => 'success', 'message' => "Invoice Deleted Successfully."]);

  return Redirect::to('daily_report/daily_book_sales/view/index');



  }
	public function ajaxEditInvoiceDetail(Request $request){
		//print_r($request->all()); exit;
		DB::beginTransaction();
		try {
           $custmorinvoice =   CustomerInvoice::where('id',$request->id)->first();
			   if($custmorinvoice){
				if($request->created_at){
				   $custmorinvoice->created_at = $request->created_at;
				}
				if($request->customer_id){
					$custmorinvoice->customer_id = $request->customer_id;
				}
				$custmorinvoice->update();
				// update stock_products.
				StockProduct::where('invoice_id', $request->id)->where('type','customer')->update(['customer_id' => $request->customer_id]);
				// update customer_invoice_products.
				CustomerInvoiceProduct::where('customer_invoice_id', $request->id)->update(['customer_id' => $request->customer_id]);
				DB::commit();
				return $this->successResponse(['id' => $request->id]);
			}
        }catch(\Exception $ex){
			DB::rollback();
            $this->exceptionResponse($ex);
        }
    }
    
	public function saveInvoiceNotes(Request $request){
		$invoice = CustomerInvoice::where('id', $request->id)->first();
		if (!$invoice) {
			return $this->errorResponse('Invoice not found');
		}
		$invoice->notes = $request->notes ?? '';
		$invoice->save();
		return $this->successResponse([]);
	}

	public function fetchuser(){

       $custmar = Customer::where('is_active',1)->get();
        return $this->successResponse($custmar);

     }

	public function productSupplierInvoices($product_id, $supplier_id){
		$product_id = is_array($product_id) ? $product_id['value'] : $product_id;
		$supplier_id = is_array($supplier_id) ? $supplier_id['value'] : $supplier_id;
		
		$invoices = \App\Models\SupplierInvoiceProduct::getProductSupplierInvoices($product_id, $supplier_id);
		return $this->successResponse($invoices);
	}


}
