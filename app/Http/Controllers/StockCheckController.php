<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use DB;
use App\Lib\Response as CustomResponse;
use Carbon\Carbon;
use App\Models\StockClosing;
use App\Models\StockProduct;
use App\Models\Product;

class StockCheckController extends Controller
{
	use CustomResponse;
	
	protected $ns_identifier = ['stock_added','stock_updated'];
	
	protected $sales_identifier = ['stock_consumed'];
	
	protected $cr_identifier = ['customer_return'];
	
	protected $sr_identifier = ['supplier_return'];
	
	protected $dump_identifier = ['dump'];
	
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('stock_check.index');
    }

    public function print(Request $request)
    {
        return view('stock_check.print');
    }
	
	public function list(Request $request){
		try{
			$rules = [
                'date' => 'required',
				'mode' => ['required','string'],
				'to_date' => ['required'],
            ];

			$validator = Validator::make($request->all(), $rules);
			if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }

			$from_date = $request->date;
			$to_date = $request->to_date;

			// All active products
			$products = Product::where('is_active', 1)->orderBy('name','ASC')->get()->keyBy('id');

			// Per-row breakdown: one row per (product_id, supplier_invoice_id, date).
			// For stock_added/stock_updated/supplier_return/dump: invoice_id IS the supplier_invoice_id.
			// For stock_consumed/customer_return: supplier_invoice_id field links back to source supplier invoice.
			$srcInvoiceExpr = "(CASE WHEN event IN ('stock_added','stock_updated','supplier_return','dump') THEN invoice_id ELSE supplier_invoice_id END)";

			// Carry-forward stock from before $from_date per (product, supplier_invoice).
			// Used to populate Opening Stock when saved closing isn't available for that
			// exact invoice key — keeps math correct without inventing rows that wouldn't
			// have shown otherwise (display logic still gates on activity / saved closing).
			$preHistory = StockProduct::select(
				'product_id',
				DB::raw("$srcInvoiceExpr as src_supplier_invoice_id"),
				DB::raw("SUM(CASE WHEN event IN ('stock_added','stock_updated') THEN stock ELSE 0 END) as ns_total"),
				DB::raw("SUM(CASE WHEN event IN ('stock_consumed') THEN stock ELSE 0 END) as sales_total"),
				DB::raw("SUM(CASE WHEN event IN ('customer_return') THEN stock ELSE 0 END) as crtn_total"),
				DB::raw("SUM(CASE WHEN event IN ('supplier_return') THEN stock ELSE 0 END) as srtn_total"),
				DB::raw("SUM(CASE WHEN event IN ('dump') THEN stock ELSE 0 END) as dmps_total")
			)
			->where('is_archived', 0)
			->whereDate('created_at', '<', $from_date)
			->groupBy('product_id', DB::raw("$srcInvoiceExpr"))
			->get();

			$carryForward = [];
			foreach ($preHistory as $p) {
				$k = $p->product_id . '|' . ((int)$p->src_supplier_invoice_id);
				$carry = (int)$p->ns_total - (int)$p->sales_total + (int)$p->crtn_total - (int)$p->srtn_total - (int)$p->dmps_total;
				$carryForward[$k] = max(0, $carry);
			}

			$allHistory = StockProduct::select(
				'product_id',
				DB::raw("$srcInvoiceExpr as src_supplier_invoice_id"),
				DB::raw("DATE(created_at) as activity_date"),
				DB::raw("SUM(CASE WHEN event IN ('stock_added','stock_updated') THEN stock ELSE 0 END) as ns"),
				DB::raw("SUM(CASE WHEN event IN ('stock_consumed') THEN stock ELSE 0 END) as sales"),
				DB::raw("SUM(CASE WHEN event IN ('customer_return') THEN stock ELSE 0 END) as crtn"),
				DB::raw("SUM(CASE WHEN event IN ('supplier_return') THEN stock ELSE 0 END) as srtn"),
				DB::raw("SUM(CASE WHEN event IN ('dump') THEN stock ELSE 0 END) as dmps")
			)
			->where('is_archived', 0)
			->whereDate('created_at', '<=', $to_date)
			->groupBy('product_id', DB::raw("$srcInvoiceExpr"), DB::raw("DATE(created_at)"))
			->orderBy('product_id')
			->orderBy(DB::raw("$srcInvoiceExpr"))
			->orderBy(DB::raw("DATE(created_at)"))
			->get();

			// Look up supplier_id + name + unit price per supplier_invoice_id we touched
			$invoiceIds = $allHistory->pluck('src_supplier_invoice_id')->filter()->unique()->values();
			$supplierInvoices = \App\Models\SupplierInvoice::whereIn('id', $invoiceIds)
				->with('supplier:id,name')
				->get()
				->keyBy('id');

			// Closing stock saved keyed by (product_id, supplier_invoice_id) if column exists,
			// otherwise fall back to product-wise keys for backward compatibility.
			$hasInvCol = \Schema::hasColumn('stock_closings', 'supplier_invoice_id');
			$savedClosingStock = [];
			$prevDayClosing = [];
			$previousDay = (new \DateTime($from_date))->modify('-1 day')->format('Y-m-d');
			if ($hasInvCol) {
				StockClosing::whereDate('created_at', $to_date)->where('is_reviewed', true)->get()
					->each(function($sc) use (&$savedClosingStock) {
						$k = $sc->product_id . '|' . ((int)($sc->supplier_invoice_id ?? 0));
						$savedClosingStock[$k] = (int)$sc->stock;
					});
				StockClosing::whereDate('created_at', $previousDay)->where('is_reviewed', true)->get()
					->each(function($sc) use (&$prevDayClosing) {
						$k = $sc->product_id . '|' . ((int)($sc->supplier_invoice_id ?? 0));
						$prevDayClosing[$k] = (int)$sc->stock;
					});
			} else {
				StockClosing::whereDate('created_at', $to_date)->where('is_reviewed', true)->get()
					->each(function($sc) use (&$savedClosingStock) {
						$savedClosingStock[$sc->product_id . '|0'] = (int)$sc->stock;
					});
				StockClosing::whereDate('created_at', $previousDay)->where('is_reviewed', true)->get()
					->each(function($sc) use (&$prevDayClosing) {
						$prevDayClosing[$sc->product_id . '|0'] = (int)$sc->stock;
					});
			}

			// Group history by (product_id, src_supplier_invoice_id) → list of date rows
			$grouped = [];
			foreach ($allHistory as $h) {
				$key = $h->product_id . '|' . ($h->src_supplier_invoice_id ?: 0);
				$grouped[$key][] = $h;
			}

			// Build list of all dates in range.
			// Clamp the day-loop start to the earliest stock activity so a far-back
			// $from_date (e.g. "Clear filters" → show all data) doesn't iterate over
			// empty years. Safe: days before the earliest activity have no activity
			// and (for such an early $from_date) no prior-day closing, so they would
			// never emit a row, and the running opening stock starts at 0 either way.
			$earliestActivity = StockProduct::where('is_archived', 0)->min(DB::raw('DATE(created_at)'));
			$loopStart = ($earliestActivity && $earliestActivity > $from_date) ? $earliestActivity : $from_date;
			$dates = [];
			$d = new \DateTime($loopStart);
			$end = new \DateTime($to_date);
			while ($d <= $end) {
				$dates[] = $d->format('Y-m-d');
				$d->modify('+1 day');
			}

			$data = [];
			foreach ($grouped as $key => $rows) {
				[$productId, $invId] = explode('|', $key);
				$productId = (int)$productId; $invId = (int)$invId;
				$product = $products[$productId] ?? null;
				if (!$product) continue;

				$invModel = $supplierInvoices[$invId] ?? null;
				$supplierName = $invModel && $invModel->supplier ? $invModel->supplier->name : '-';
				$supplierId   = $invModel ? (int)$invModel->supplier_id : 0;
				$invoiceDate  = $invModel ? \Carbon\Carbon::parse($invModel->created_at)->format('Y-m-d') : null;

				// Index this group's history by date
				$histByDate = [];
				foreach ($rows as $hist) {
					$histByDate[$hist->activity_date] = $hist;
				}

				$rowKey = $productId . '|' . $invId;
				// Use saved previous-day closing as the inclusion trigger so
				// rows only appear when the user has actually performed stock
				// closing OR there is fresh activity on a date in range.
				$prevClose = isset($prevDayClosing[$rowKey]) ? $prevDayClosing[$rowKey] : null;
				// Carry-forward is the displayed Opening Stock fallback: shows
				// real on-hand from prior history when no closing was saved
				// for this exact (product, supplier_invoice).
				$carryOs   = isset($carryForward[$rowKey]) ? $carryForward[$rowKey] : 0;
				$runningOs = $prevClose !== null ? $prevClose : $carryOs;

				foreach ($dates as $date) {
					$hist = $histByDate[$date] ?? null;
					$ns    = $hist ? (int)$hist->ns    : 0;
					$sales = $hist ? (int)$hist->sales : 0;
					$crtn  = $hist ? (int)$hist->crtn  : 0;
					$srtn  = $hist ? (int)$hist->srtn  : 0;
					$dmps  = $hist ? (int)$hist->dmps  : 0;

					// Show the row whenever the product has ANY activity on this date —
					// not just opening stock or new purchases. Sales / customer returns /
					// supplier returns / dumps on a day with no fresh stock must still
					// appear so the page mirrors what Customer/Supplier Return pages show.
					// Display gate matches earlier behavior: prev-day-closing OR activity
					// (carry-forward alone never invents a row).
					$hasAnyActivity = ($ns > 0) || ($sales > 0) || ($crtn > 0) || ($srtn > 0) || ($dmps > 0);
					if (($prevClose !== null && $prevClose > 0) || $hasAnyActivity) {
						$data[] = [
							'product_id'         => $productId,
							'product_name'       => $product->name,
							'supplier_invoice_id'=> $invId,
							'supplier_id'        => $supplierId,
							'supplier_name'      => $supplierName,
							'invoice_date'       => $invoiceDate,
							'date'               => $date,
							'last_activity_date' => $date,
							'os'                 => $runningOs,
							'ns'                 => $ns > 0 ? [$ns] : [0],
							'sales'              => $sales > 0 ? [$sales] : [],
							'crtn'               => $crtn > 0 ? [$crtn] : [],
							'srtn'               => $srtn > 0 ? [$srtn] : [],
							'dmps'               => $dmps > 0 ? [$dmps] : [],
							'stock'              => 0,
							'cl_stock'           => isset($savedClosingStock[$rowKey]) ? $savedClosingStock[$rowKey] : ($runningOs + $ns - $sales + $crtn - $srtn - $dmps),
							'result'             => 0,
						];
					}

					$runningOs += $ns - $sales + $crtn - $srtn - $dmps;
					$runningOs = max(0, $runningOs);
				}
			}

			// Sort by product → supplier → date
			usort($data, function($a, $b){
				$n = strcmp($a['product_name'], $b['product_name']);
				if ($n !== 0) return $n;
				$s = strcmp($a['supplier_name'], $b['supplier_name']);
				if ($s !== 0) return $s;
				return strcmp($a['date'], $b['date']);
			});

			return $this->successResponse($data);
			
		}catch(\Exception $ex){
			return $this->exceptionResponse($ex);
		}
	}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){}
	
    public function openingStock(Request $request){
		$rules = ['date' => 'required', 'to_date' => 'required', 'product_id' => 'required'];
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) { return $this->validationErrorResponse($validator->errors()->messages()); }

		$os_date = \Carbon\Carbon::parse($request->date)->toDateString();
		$product_id = $request->product_id;
		$product = Product::where('id', $product_id)->first();
		if (!$product) return $this->successResponse([]);

		// Get all SupplierInvoiceProducts for this product from before the selected date
		$items = \App\Models\SupplierInvoiceProduct::where('product_id', $product_id)
			->where('is_archive', 0)
			->whereDate('created_at', '<', $os_date)
			->with('product')
			->with('supplier')
			->orderBy('id', 'desc')
			->get();

		$result = [];
		foreach ($items as $item) {
			// Stock added
			$added = StockProduct::where('product_id', $product_id)
				->where('invoice_id', $item->supplier_invoice_id)
				->where('is_archived', 0)
				->where('type', 'supplier')
				->whereIn('event', ['stock_added', 'stock_updated'])
				->sum('stock');

			if ($added == 0) continue;

			// Supplier reductions before selected date
			$supplierUsed = StockProduct::where('product_id', $product_id)
				->where('invoice_id', $item->supplier_invoice_id)
				->where('is_archived', 0)
				->where('type', 'supplier')
				->whereIn('event', ['supplier_return', 'dump'])
				->whereDate('updated_at', '<', $os_date)
				->sum('stock');

			// Customer net consumption before selected date
			$customerConsumed = StockProduct::where('is_archived', 0)
				->where('type', 'customer')
				->where('event', 'stock_consumed')
				->where('supplier_invoice_product_id', $item->id)
				->where('supplier_invoice_id', $item->supplier_invoice_id)
				->whereDate('updated_at', '<', $os_date)
				->sum('stock');

			$customerReturned = StockProduct::where('is_archived', 0)
				->where('type', 'customer')
				->where('event', 'customer_return')
				->where('supplier_invoice_product_id', $item->id)
				->where('supplier_invoice_id', $item->supplier_invoice_id)
				->whereDate('updated_at', '<', $os_date)
				->sum('stock');

			$netStock = abs($added) - abs($supplierUsed) - (abs($customerConsumed) - abs($customerReturned));

			if ($netStock <= 0) continue;

			// Build a response in the same shape as new_stock so frontend renders identically
			$result[] = [
				'id'         => $item->id,
				'invoice_id' => $item->supplier_invoice_id,
				'updated_at' => \Carbon\Carbon::parse($item->created_at)->format('d M Y'),
				'product'    => ['name' => $item->product?->name ?? '-'],
				'supplier'   => ['name' => $item->supplier?->name ?? '-'],
				'remarks'    => $item->remarks,
				'stock'      => $netStock,
				'price'      => $item->unit_price,
			];
		}

		return $this->successResponse($result);
	}
	
    public function newStock(Request $request){
		$rules = [
			'date' => 'required',
			//'mode' => ['required','string'],
			'to_date' => ['required'],
			'product_id' => 'required',
		];

		$date_ns = $request->date;
		$to_date = $request->to_date;

		if (empty($date_ns) || empty($to_date) || empty($request->product_id)) {
			return $this->successResponse([]);
		}

		$date = \Carbon\Carbon::parse($date_ns)->subDay()->toDateString();

		$records = StockProduct::whereDate('updated_at', '>=', $date_ns)
			->whereDate('updated_at', '<=', $to_date)
			->where('type', 'supplier')
			->where('is_archived', 0)
			->where('product_id', $request->product_id)
			->where(function ($q) {
				$q->where('event', 'stock_added')
				  ->orWhere('event', 'stock_updated');
			})
			->with('supplier')
			->with('product')
			->get();
			  
		return $this->successResponse($records);
	}
	
    public function sales(Request $request){
		$rules = [
			'date' => 'required',
			'product_id' => 'required',
			//'mode' => ['required','string'],
			'to_date' => ['required'],
		];

		$date_ns = $request->date;
		$to_date = $request->to_date;

		if (empty($date_ns) || empty($to_date) || empty($request->product_id)) {
			return $this->successResponse([]);
		}

		$date = \Carbon\Carbon::parse($date_ns)->subDay()->toDateString();

		$records = StockProduct::whereDate('updated_at', '>=', $date_ns)
			->whereDate('updated_at', '<=', $to_date)
			->where('type', 'customer')
			->where('product_id', $request->product_id)
			->where('is_archived', 0)
			->where(function ($q) {
				$q->where('event', 'stock_consumed')
				  //->orWhere('event', 'stock_updated')
				  ;
			})
			->with('supplier')
			->with('customer')
			->with('product')
			->get();
			
		return $this->successResponse($records);
	}
	
    public function customerReturn(Request $request){
		$rules = [
			'date' => 'required',
			//'mode' => ['required','string'],
			'to_date' => ['required'],
			'product_id' => 'required',
		];

		$date_ns = $request->date;
		$to_date = $request->to_date;

		if (empty($date_ns) || empty($to_date) || empty($request->product_id)) {
			return $this->successResponse([]);
		}

		$date = \Carbon\Carbon::parse($date_ns)->subDay()->toDateString();

		$records = StockProduct::whereDate('updated_at', '>=', $date_ns)
			->whereDate('updated_at', '<=', $to_date)
			->where('type', 'customer')
			->where('is_archived', 0)
			->where('product_id', $request->product_id)
			->where(function ($q) {
				$q->where('event', 'customer_return')
				  //->orWhere('event', 'stock_updated')
				  ;
			})
			->with('supplier')
			->with('product')
			->with('customer')
			->get();
			  
		return $this->successResponse($records);
	}
	
    public function dumps(Request $request){
		$rules = [
			'date' => 'required',
			//'mode' => ['required','string'],
			'to_date' => ['required'],
			'product_id' => 'required',
		];

		$date_ns = $request->date;
		$to_date = $request->to_date;

		if (empty($date_ns) || empty($to_date) || empty($request->product_id)) {
			return $this->successResponse([]);
		}

		$date = \Carbon\Carbon::parse($date_ns)->subDay()->toDateString();

		$records = StockProduct::whereDate('updated_at', '>=', $date_ns)
			->whereDate('updated_at', '<=', $to_date)
			->where('type', 'supplier')
			->where('is_archived', 0)
			->where('product_id', $request->product_id)
			->where(function ($q) {
				$q->where('event', 'dump')
				  //->orWhere('event', 'stock_updated')
				  ;
			})
			->with('supplier')
			->with('product')
			->get();
			  
		return $this->successResponse($records);
	}
	
    public function supplierReturn(Request $request){
		$rules = [
			'date' => 'required',
			//'mode' => ['required','string'],
			'to_date' => ['required'],
			'product_id' => 'required',
		];

		$date_ns = $request->date;
		$to_date = $request->to_date;

		if (empty($date_ns) || empty($to_date) || empty($request->product_id)) {
			return $this->successResponse([]);
		}

		$date = \Carbon\Carbon::parse($date_ns)->subDay()->toDateString();

		$records = StockProduct::whereDate('updated_at', '>=', $date_ns)
			->whereDate('updated_at', '<=', $to_date)
			->where('type', 'supplier')
			->where('is_archived', 0)
			->where('product_id', $request->product_id)
			->where(function ($q) {
				$q->where('event', 'supplier_return')
				  //->orWhere('event', 'stock_updated')
				  ;
			})
			->with('supplier')
			->with('product')
			->get();
			  
		return $this->successResponse($records);
	}
	
    public function closingStock(Request $request){
		$rules = [
			'date' => 'required',
			'to_date' => ['required'],
			'product_id' => 'required',
		];

		$to_date = $request->to_date;
		$product_id = $request->product_id;

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}

		$records = Product::where('is_active', 1)
		->whereHas('stockClosing', function ($q) use ($to_date, $product_id) {
			$q->whereDate('created_at', $to_date)
			->where('product_id', $product_id)
			->where('stock', '>', 0);
		})
		->with(['stockClosing' => function ($q) use ($to_date, $product_id) {
			$q->whereDate('created_at', $to_date)
			->where('product_id', $product_id)
			->where('stock', '>', 0);
		}])
		->orderBy('name', 'ASC')
		->get();

		// Calculate system stock for each product up to to_date
		$systemStocks = StockProduct::select('product_id',
			DB::raw("SUM(CASE WHEN event IN ('stock_added','stock_updated') THEN stock ELSE 0 END) as total_in"),
			DB::raw("SUM(CASE WHEN event IN ('stock_consumed') THEN stock ELSE 0 END) as total_out"),
			DB::raw("SUM(CASE WHEN event IN ('customer_return') THEN stock ELSE 0 END) as total_crtn"),
			DB::raw("SUM(CASE WHEN event IN ('supplier_return') THEN stock ELSE 0 END) as total_srtn"),
			DB::raw("SUM(CASE WHEN event IN ('dump') THEN stock ELSE 0 END) as total_dump")
		)
		->where('is_archived', 0)
		->whereDate('updated_at', '<=', $to_date)
		->whereIn('product_id', $records->pluck('id'))
		->groupBy('product_id')
		->get()
		->mapWithKeys(function($item){
			$s = $item->total_in - $item->total_out + $item->total_crtn - $item->total_srtn - $item->total_dump;
			return [$item->product_id => round($s, 2)];
		})->toArray();

		$result = $records->map(function($product) use ($systemStocks) {
			$recorded = optional($product->stockClosing)->stock ?? 0;
			$system   = $systemStocks[$product->id] ?? 0;
			$variance = round($recorded - $system, 2);
			return [
				'product_id'     => $product->id,
				'product_name'   => $product->name,
				'system_stock'   => $system,
				'recorded_stock' => (float) $recorded,
				'variance'       => $variance,
				'date'           => optional($product->stockClosing)->created_at
					? \Carbon\Carbon::parse($product->stockClosing->created_at)->format('d M Y')
					: '—',
			];
		});

		return $this->successResponse($result);
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
    public function destroy($id)
    {
        //
    }
}
