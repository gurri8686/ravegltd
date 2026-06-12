<?php
/**
 * https://docs.laravel-excel.com/3.1/getting-started/installation.html
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Services\CustomerPayments;
use App\Services\SupplierPayments;
use App\Services\ProductHistory;
use App\Models\Product;
use App\Models\StockProduct;
use App\Models\StockClosing;

class ExcelController extends Controller
{
	use CustomResponse;

	/**
	 * Build a simple class-based export with an array of rows + headings.
	 */
	private function makeExport(array $rows, array $headings) {
		return new class($rows, $headings) implements FromArray, WithHeadings {
			protected $rows;
			protected $headings;
			public function __construct($rows, $headings) {
				$this->rows = $rows;
				$this->headings = $headings;
			}
			public function array(): array { return $this->rows; }
			public function headings(): array { return $this->headings; }
		};
	}

	public function customerHistory(Request $request, CustomerPayments $customerPayments){
		$rules = [
			'customer_id' => 'required',
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}

		$start_date = $end_date = "";
		extract($request->only('customer_id', 'start_date', 'end_date', 'type', 'invoices'));

		$invoices = $customerPayments::invoicePaymentsHistory($customer_id, $start_date, $end_date);

		$rows = [];
		$currency = env('CURRENCY_SYMBOL', '£');
		foreach ($invoices as $inv) {
			if (($inv['is_credited'] ?? 0) == 1) continue;
			$rows[] = [
				$inv['id'] ?? '',
				isset($inv['created_at']) ? uk_ts($inv['created_at'], 'd M Y') : '',
				number_format((float)($inv['net_amount'] ?? 0), 2),
				number_format((float)($inv['total_paid'] ?? 0), 2),
				number_format((float)($inv['credit_adj'] ?? 0), 2),
				number_format((float)($inv['total_discounted'] ?? 0), 2),
				number_format((float)($inv['balance'] ?? 0), 2),
			];
		}

		$headings = ['Invoice','Date','Amount','Paid','Credit/Adj','Discount/Adj','Balance'];
		$range = (!empty($start_date) && !empty($end_date)) ? ($start_date . '_to_' . $end_date) : 'all';
		$fileName = 'customer_history-' . $range . '.xlsx';

		return Excel::download($this->makeExport($rows, $headings), $fileName);
	}

	public function supplierHistory(Request $request, SupplierPayments $supplierPayments){
		$rules = [
			'supplier_id' => 'required',
		];

		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}

		$start_date = $end_date = "";
		extract($request->only('supplier_id', 'start_date', 'end_date', 'type', 'invoices'));

		$invoices = $supplierPayments::invoicePaymentsHistory($supplier_id, $start_date, $end_date);

		$rows = [];
		foreach ($invoices as $inv) {
			if (($inv['is_credited'] ?? 0) == 1) continue;
			$rows[] = [
				$inv['id'] ?? '',
				isset($inv['created_at']) ? uk_ts($inv['created_at'], 'd M Y') : '',
				number_format((float)($inv['net_amount'] ?? 0), 2),
				number_format((float)($inv['total_paid'] ?? 0), 2),
				number_format((float)($inv['credit_adj'] ?? 0), 2),
				number_format((float)($inv['total_discounted'] ?? 0), 2),
				number_format((float)($inv['balance'] ?? 0), 2),
			];
		}

		$headings = ['Invoice','Date','Amount','Paid','Credit/Adj','Discount/Adj','Balance'];
		$range = (!empty($start_date) && !empty($end_date)) ? ($start_date . '_to_' . $end_date) : 'all';
		$fileName = 'supplier_history-' . $range . '.xlsx';

		return Excel::download($this->makeExport($rows, $headings), $fileName);
	}

	public function productHistory(Request $request){
		$rules = ['product_id' => 'required'];
		$validator = Validator::make($request->all(), $rules);
		if ($validator->fails()) {
			return $this->validationErrorResponse($validator->errors()->messages());
		}

		$supplier_id = $product_id = $customer_id = $start_date = $end_date = "";
		extract($request->only('product_id', 'supplier_id', 'customer_id', 'start_date', 'end_date'));

		$suppliers        = ProductHistory::supplierInvoices($product_id, $start_date, $end_date, $supplier_id);
		$supplier_returns = ProductHistory::supplierReturns($product_id, $start_date, $end_date, $supplier_id);
		$sales            = ProductHistory::sales($product_id, $start_date, $end_date, $customer_id, $supplier_id);
		$customer_returns = ProductHistory::customerReturns($product_id, $start_date, $end_date, $customer_id);
		$dumps            = ProductHistory::dumps($product_id, $start_date, $end_date, $supplier_id);

		$rows = [];
		$pushRows = function ($collection, $type, $partyKey, $qtyKey, $priceKey) use (&$rows) {
			foreach ($collection as $entry) {
				$party = $entry[$partyKey]['name'] ?? '';
				$qty = (float)($entry[$qtyKey] ?? 0);
				$price = (float)($entry[$priceKey] ?? 0);
				$rows[] = [
					$type,
					isset($entry['created_at']) ? uk_ts($entry['created_at'], 'd M Y') : '',
					$entry['product']['name'] ?? '',
					$party,
					$qty,
					number_format($price, 2),
					number_format($qty * $price, 2),
				];
			}
		};

		$pushRows($suppliers,        'Supplier',         'supplier', 'quantity', 'unit_price');
		$pushRows($supplier_returns, 'Supplier Return',  'supplier', 'stock',    'price');
		$pushRows($sales,            'Sale',             'customer', 'quantity', 'unit_price');
		$pushRows($customer_returns, 'Customer Return',  'customer', 'stock',    'price');
		$pushRows($dumps,            'Dump',             'supplier', 'stock',    'price');

		$headings = ['Type','Date','Product','Party','Quantity','Unit Price','Total'];
		$range = (!empty($start_date) && !empty($end_date)) ? ($start_date . '_to_' . $end_date) : 'all';
		$fileName = 'product_history-' . $range . '.xlsx';

		return Excel::download($this->makeExport($rows, $headings), $fileName);
	}

	/**
	 * Stock Check Excel export — mirrors stock_check/print page data.
	 */
	public function stockCheck(Request $request){
		$date    = $request->input('date') ?: date('Y-m-d');
		$to_date = $request->input('to_date') ?: $date;
		$stock   = $request->input('stock') ?: 'all';
		$mode    = $request->input('mode') ?: 'show-all';

		// Reuse StockCheckController->list() logic via internal call
		$stockReq = new Request(['date' => $date, 'to_date' => $to_date, 'mode' => $mode]);
		$controller = app(\App\Http\Controllers\StockCheckController::class);
		$response = $controller->list($stockReq);
		$payload = json_decode($response->getContent(), true);
		$products = $payload['payload'] ?? [];

		$rows = [];
		foreach ($products as $p) {
			$os    = (int)($p['os'] ?? 0);
			$ns    = array_sum($p['ns'] ?? []);
			$sales = array_sum($p['sales'] ?? []);
			$crtn  = array_sum($p['crtn'] ?? []);
			$srtn  = array_sum($p['srtn'] ?? []);
			$dmps  = array_sum($p['dmps'] ?? []);
			$expected = $os + $ns - $sales + $crtn - $srtn - $dmps;
			$cl = $p['cl_stock'] ?? null;
			$hasCl = !($cl === null || $cl === '' || $cl === 0 || $cl === '0');
			$diff = $hasCl ? (float)$cl - $expected : 0;

			// Match UI table filter: skip only when product has zero opening AND zero activity
			if ($os <= 0 && $ns <= 0 && $sales <= 0 && $crtn <= 0 && $srtn <= 0 && $dmps <= 0) continue;

			$result = !$hasCl ? '' : ($diff == 0 ? 'OK' : ($diff > 0 ? '+'.$diff.' Excess' : abs($diff).' Short'));

			$rows[] = [
				$p['product_name'] ?? '',
				$p['supplier_name'] ?? '',
				$os, $ns, $sales, $crtn, $dmps, $srtn,
				$expected,
				$hasCl ? (float)$cl : '',
				$result,
			];
		}

		$headings = ['Product','Supplier','O.S','N.S','Sales','C.Rtn','Dumps','S.Rtn','Stock','Cl. Stock','Result'];
		$fileName = 'stock_check-' . $date . '.xlsx';
		return Excel::download($this->makeExport($rows, $headings), $fileName);
	}

	/**
	 * Stock Closing Excel export.
	 */
	public function stockClosing(Request $request){
		$date = $request->input('date') ?: date('Y-m-d');
		$stock = $request->input('stock') ?: 'in-stock';
		$tab = $request->input('tab') ?: 'unreviewed';

		$closingReq = new Request(['date' => $date]);
		$controller = app(\App\Http\Controllers\StockClosingController::class);
		$response = $controller->products($closingReq);
		$payload = json_decode($response->getContent(), true);
		$products = $payload['payload'] ?? [];

		$rows = [];
		foreach ($products as $p) {
			$system   = (float)($p['system_stock'] ?? 0);
			$totalIn  = (float)($p['total_in'] ?? 0);
			$totalOut = (float)($p['total_out'] ?? 0);
			$recorded = isset($p['stock_closing']['stock']) ? (float)$p['stock_closing']['stock'] : 0;
			$reviewed = !empty($p['stock_closing']['is_reviewed']);

			// Match UI default filter: in-stock only (system_stock > 0)
			if ($stock === 'in-stock' && $system <= 0) continue;
			if ($stock === 'out-of-stock' && $system > 0) continue;

			// Match active tab: Reviewed vs Unreviewed
			if ($tab === 'reviewed' && !$reviewed) continue;
			if ($tab === 'unreviewed' && $reviewed) continue;

			$productName = ($p['name'] ?? '') . ' (In:' . (int)$totalIn . ' Out:' . (int)$totalOut . ')';

			$rows[] = [
				$productName,
				$p['stock_closing']['remark'] ?? '',
				$system,
				$recorded > 0 ? $recorded : '',
			];
		}

		$headings = ['Product','Remark','System Stock','Closing Stock'];
		$fileName = 'stock_closing-' . $tab . '-' . $date . '.xlsx';
		return Excel::download($this->makeExport($rows, $headings), $fileName);
	}

	/**
	 * Unassigned Suppliers Excel export.
	 */
	public function unassignedSuppliers(Request $request){
		$req = new Request([
			'search' => $request->input('search'),
			'from_date' => $request->input('from_date'),
			'to_date' => $request->input('to_date'),
		]);
		$controller = app(\App\Http\Controllers\StockClosingController::class);
		$response = $controller->unassignedSuppliersList($req);
		$payload = json_decode($response->getContent(), true);
		$products = $payload['payload'] ?? [];

		$currency = env('CURRENCY_SYMBOL', '£');
		$rows = [];
		foreach ($products as $p) {
			$rows[] = [
				$p['invoice_id'] ?? '',
				$p['created_at'] ?? '',
				$p['product_name'] ?? '',
				$p['customer_name'] ?? '',
				$p['quantity'] ?? '',
				$currency . ' ' . number_format((float)($p['unit_price'] ?? 0), 2),
				$p['remarks'] ?? '',
			];
		}

		$headings = ['Invoice','Date','Product','Customer','Qty','Price','Note'];
		$fileName = 'unassigned_suppliers-' . date('Y-m-d') . '.xlsx';
		return Excel::download($this->makeExport($rows, $headings), $fileName);
	}

	/**
	 * Customer Return — Add Return Credit tab (returnable products) Excel export.
	 */
	public function customerReturnable(Request $request){
		$req = new Request([
			'customer_id' => $request->input('customer_id', ''),
			'from_date' => $request->input('from_date') ?: $request->input('date'),
			'to_date' => $request->input('to_date'),
		]);
		$controller = app(\App\Http\Controllers\CustomerReturnController::class);
		$response = $controller->customerProducts($req);
		$payload = json_decode($response->getContent(), true);
		$items = $payload['payload'] ?? [];

		$currency = env('CURRENCY_SYMBOL', '£');
		$rows = [];
		foreach ($items as $r) {
			$rows[] = [
				$r['invoice_id'] ?? '',
				$r['date'] ?? '',
				$r['customer_name'] ?? '',
				$r['product_name'] ?? '',
				$r['remarks'] ?? '',
				$r['quantity'] ?? '',
				$r['returned'] ?? 0,
				$r['available'] ?? 0,
				$currency . ' ' . number_format((float)($r['unit_price'] ?? 0), 2),
			];
		}

		$headings = ['Invoice','Date','Customer','Product','Remark','Purchased','Returned','Available','Price'];
		$fileName = 'customer_returnable-' . date('Y-m-d') . '.xlsx';
		return Excel::download($this->makeExport($rows, $headings), $fileName);
	}

	/**
	 * Customer Return Excel export.
	 */
	public function customerReturn(Request $request){
		$req = new Request([
			'customer_id' => $request->input('customer_id', ''),
			'date' => $request->input('date'),
			'to_date' => $request->input('to_date'),
		]);
		$controller = app(\App\Http\Controllers\CustomerReturnController::class);
		$response = $controller->returns($req);
		$payload = json_decode($response->getContent(), true);
		$items = $payload['payload'] ?? [];

		return $this->buildReturnsExcel($items, 'customer_return', 'customer');
	}

	/**
	 * Supplier Return — Add Return Credit tab (returnable products) Excel export.
	 */
	public function supplierReturnable(Request $request){
		$req = new Request([
			'supplier_id' => $request->input('supplier_id', ''),
			'from_date' => $request->input('from_date') ?: $request->input('date'),
			'to_date' => $request->input('to_date') ?: $request->input('end_date'),
		]);
		$controller = app(\App\Http\Controllers\SupplierReturnController::class);
		$response = $controller->supplierProducts($req);
		$payload = json_decode($response->getContent(), true);
		$items = $payload['payload'] ?? [];

		$currency = env('CURRENCY_SYMBOL', '£');
		$rows = [];
		foreach ($items as $r) {
			$rows[] = [
				$r['invoice_id'] ?? '',
				$r['date'] ?? '',
				$r['supplier_name'] ?? '',
				$r['product_name'] ?? '',
				$r['remarks'] ?? '',
				$r['quantity'] ?? '',
				$r['returned'] ?? 0,
				$r['available'] ?? 0,
				$currency . ' ' . number_format((float)($r['unit_price'] ?? 0), 2),
			];
		}

		$headings = ['Invoice','Date','Supplier','Product','Remark','Purchased','Returned','Available','Price'];
		$fileName = 'supplier_returnable-' . date('Y-m-d') . '.xlsx';
		return Excel::download($this->makeExport($rows, $headings), $fileName);
	}

	/**
	 * Dump — Add Dump tab (dumpable products) Excel export.
	 */
	public function dumpable(Request $request){
		$req = new Request([
			'supplier_id' => $request->input('supplier_id', ''),
			'from_date' => $request->input('from_date') ?: $request->input('date'),
			'to_date' => $request->input('to_date') ?: $request->input('end_date'),
		]);
		$controller = app(\App\Http\Controllers\DumpController::class);
		$response = $controller->supplierProducts($req);
		$payload = json_decode($response->getContent(), true);
		$items = $payload['payload'] ?? [];

		$currency = env('CURRENCY_SYMBOL', '£');
		$rows = [];
		foreach ($items as $r) {
			$rows[] = [
				$r['invoice_id'] ?? '',
				$r['date'] ?? '',
				$r['supplier_name'] ?? '',
				$r['product_name'] ?? $r['name'] ?? '',
				$r['remarks'] ?? '',
				$r['quantity'] ?? '',
				$r['stock'] ?? 0,
				$currency . ' ' . number_format((float)($r['unit_price'] ?? 0), 2),
			];
		}

		$headings = ['Invoice','Date','Supplier','Product','Remark','Purchased','Available','Price'];
		$fileName = 'dumpable-' . date('Y-m-d') . '.xlsx';
		return Excel::download($this->makeExport($rows, $headings), $fileName);
	}

	/**
	 * Supplier Return Excel export.
	 */
	public function supplierReturn(Request $request){
		$req = new Request([
			'supplier_id' => $request->input('supplier_id', ''),
			'date' => $request->input('date'),
			'end_date' => $request->input('end_date'),
		]);
		$controller = app(\App\Http\Controllers\SupplierReturnController::class);
		$response = $controller->returns($req);
		$payload = json_decode($response->getContent(), true);
		$items = $payload['payload'] ?? [];

		return $this->buildReturnsExcel($items, 'supplier_return', 'supplier');
	}

	/**
	 * Dump Excel export.
	 */
	public function dump(Request $request){
		$req = new Request([
			'supplier_id' => $request->input('supplier_id', ''),
			'date' => $request->input('date'),
			'end_date' => $request->input('end_date'),
		]);
		$controller = app(\App\Http\Controllers\DumpController::class);
		$response = $controller->returns($req);
		$payload = json_decode($response->getContent(), true);
		$items = $payload['payload'] ?? [];

		return $this->buildReturnsExcel($items, 'dump', 'supplier');
	}

	private function buildReturnsExcel($items, $fileType, $partyKey){
		$currency = env('CURRENCY_SYMBOL', '£');
		$rows = [];
		foreach ($items as $r) {
			$rows[] = [
				$r['invoice_id'] ?? '',
				isset($r['date']) ? \Carbon\Carbon::parse($r['date'])->format('d M Y') : '',
				$r['product_id'] ?? '',
				$r[$partyKey] ?? '',
				$r['note'] ?? '',
				$r['quantity'] ?? '',
				$currency . ' ' . number_format((float)($r['price'] ?? 0), 2),
				$currency . ' ' . number_format((float)($r['total'] ?? 0), 2),
			];
		}
		$partyLabel = ucfirst($partyKey);
		$headings = ['Invoice','Date','Product',$partyLabel,'Note','Qty','Price','Total'];
		$fileName = $fileType . '-' . date('Y-m-d') . '.xlsx';
		return Excel::download($this->makeExport($rows, $headings), $fileName);
	}

	/**
	 * Stock Manager Excel export — all active products listing.
	 */
	public function stockManager(Request $request){
		$products = Product::orderBy('name','ASC')->get();
		$status = $request->input('status') ?: 'all';

		$rows = [];
		foreach ($products as $p) {
			if ($status === 'active' && $p->is_active != 1) continue;
			if ($status === 'inactive' && $p->is_active == 1) continue;
			$rows[] = [
				$p->product_id ?? '',
				$p->name ?? '',
				$p->is_active == 1 ? 'Active' : 'Inactive',
			];
		}

		$headings = ['Product ID','Name','Status'];
		$fileName = 'stock_manager-' . date('Y-m-d') . '.xlsx';
		return Excel::download($this->makeExport($rows, $headings), $fileName);
	}

	/**
	 * Products listing Excel export — matches /management/products/view DataTable.
	 */
	public function products(Request $request){
		$products = Product::orderBy('name','ASC')->get();
		$status = $request->input('status') ?: 'all';
		$search = strtolower(trim((string) $request->input('search', '')));
		$currency = env('CURRENCY_SYMBOL', '£');

		// Fetch profit/loss data internally — same source the UI uses.
		$plMap = [];
		try {
			$plRequest = new Request([
				'product_id' => [],
				'start_date' => $request->input('start_date') ?: '2000-01-01',
				'end_date'   => $request->input('end_date')   ?: date('Y-m-d'),
			]);
			$plController = app(\App\Http\Controllers\ProfitLossController::class);
			$plResponse = $plController->profitLoss($plRequest);
			$plPayload = json_decode($plResponse->getContent(), true);
			$plProducts = $plPayload['payload'] ?? [];
			foreach ($plProducts as $pl) {
				if (!isset($pl['id'])) continue;
				$qty = (float)($pl['stock_quantity'] ?? 0);
				$avg = (float)($pl['avg_profit'] ?? 0);
				$plMap[$pl['id']] = [
					'value' => round($qty * $avg),
					'qty'   => $qty,
					'pct'   => (float)($pl['avg_profit_percentage'] ?? 0),
				];
			}
		} catch (\Exception $e) {
			// If profit/loss API fails, leave column blank
		}

		$rows = [];
		foreach ($products as $p) {
			if ($status === 'active' && $p->is_active != 1) continue;
			if ($status === 'inactive' && $p->is_active == 1) continue;
			if ($search !== '') {
				$hay = strtolower(($p->name ?? '') . ' ' . ($p->selling_price ?? '') . ' ' . ($p->unit_weight ?? '') . ' ' . ($p->tax_vat ?? ''));
				if (strpos($hay, $search) === false) continue;
			}

			$pl = $plMap[$p->id] ?? null;
			$plText = '';
			if ($pl && ($pl['value'] != 0 || $pl['qty'] > 0)) {
				$isProfit = $pl['value'] >= 0;
				$plText = ($isProfit ? 'Profit' : 'Loss') . ' ' . $currency . ' ' . number_format(abs($pl['value']), 0)
					. ' (' . $pl['qty'] . ' sold, ' . ($pl['pct'] >= 0 ? '+' : '') . $pl['pct'] . '% margin)';
			}

			$rows[] = [
				$p->name ?? '',
				$p->selling_price !== null && $p->selling_price !== '' ? $currency . ' ' . $p->selling_price : '',
				$p->unit_weight ?? '',
				$p->tax_vat ?? '',
				$p->created_at ? uk_ts($p->created_at, 'd M Y') : '',
				$p->is_active == 1 ? 'Active' : 'Inactive',
				$plText,
			];
		}

		$headings = ['Name','Selling Price','Weight / Unit','Tax / VAT','Date','Status','Profit / Loss'];
		$fileName = 'products-' . date('Y-m-d') . '.xlsx';
		return Excel::download($this->makeExport($rows, $headings), $fileName);
	}
}
