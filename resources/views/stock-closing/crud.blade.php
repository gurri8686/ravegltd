@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }

/* ── Tablet / iPad (768px – 1024px) ── */
@media (min-width: 768px) and (max-width: 1024px) {

    /* Page header */
    .users-list-wrapper > div:first-child {
        padding: 14px 18px !important;
        border-radius: 14px !important;
        margin-bottom: 14px !important;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07) !important;
        border: none !important;
    }
    .users-list-wrapper > div:first-child h1 { font-size: 19px !important; }
    .users-list-wrapper > div:first-child p  { font-size: 12px !important; }
    .users-list-wrapper > div:first-child > div > div[style*="width:48px"] {
        width: 44px !important; height: 44px !important; border-radius: 13px !important;
    }

    /* Filter card */
    .sc-filter-card {
        border-radius: 14px !important;
        border: 1px solid #eef2f7 !important;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06) !important;
        overflow: hidden !important;
        margin-bottom: 14px !important;
    }

    /* Filter row */
    .sc-filter-row {
        padding: 18px 20px !important;
        gap: 16px !important;
        border-bottom: 1.5px solid #f1f5f9 !important;
        background: #fff !important;
        align-items: flex-end !important;
        flex-wrap: nowrap !important;
    }

    /* Date field */
    .sc-date-col { flex: 0 0 220px !important; }
    .sc-date-inner {
        height: 46px !important;
        border-radius: 12px !important;
        border: 1.5px solid #e2e8f0 !important;
        padding: 0 14px !important;
        min-width: unset !important;
        box-shadow: none !important;
    }
    .sc-date-inner:hover { border-color: #F27420 !important; }

    /* Stock Status dropdown — push to right end */
    .sc-stock-col { flex: 0 0 200px !important; min-width: unset !important; margin-left: auto !important; }
    .sc-stock-col .react-select__control {
        min-height: 46px !important; height: 46px !important;
        border-radius: 12px !important;
        border: 1.5px solid #e2e8f0 !important;
        box-shadow: none !important;
    }
    .sc-stock-col .react-select__value-container { height: 46px !important; padding: 0 14px !important; }
    .sc-stock-col .react-select__indicators { height: 46px !important; }

    /* Labels inside filter */
    .sc-filter-row label {
        font-size: 11px !important;
        font-weight: 700 !important;
        color: #64748b !important;
        letter-spacing: 0.7px !important;
        margin-bottom: 8px !important;
    }

    /* List/Table card */
    .sc-list-card {
        border-radius: 14px !important;
        border: 1px solid #eef2f7 !important;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06) !important;
        overflow: hidden !important;
    }

    /* Table header */
    .rdt_TableCol {
        padding: 10px 14px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        letter-spacing: 0.5px !important;
        min-height: 42px !important;
    }

    /* Table cells */
    .rdt_TableCell { padding: 12px 14px !important; font-size: 14px !important; }
}

@media (min-width: 1024px) {
	.sc-filter-row { display: grid !important; grid-template-columns: 1fr auto 1fr !important; align-items: center !important; gap: 16px !important; }
	.sc-search-col { justify-self: center !important; width: 340px !important; max-width: 100% !important; }
	.sc-saveall-col { justify-self: end !important; }
}
@media (max-width: 767px) {
	/* Header card compact */
	.users-list-wrapper > div:first-child { padding: 12px 14px !important; margin-bottom: 12px !important; border-radius: 12px !important; }
	.users-list-wrapper > div:first-child h1 { font-size: 17px !important; }
	.users-list-wrapper > div:first-child p { font-size: 11px !important; }
	.users-list-wrapper > div:first-child > div > div[style*="width:48px"] { width: 38px !important; height: 38px !important; border-radius: 10px !important; }
	.users-list-wrapper > div:first-child > div > div[style*="width:48px"] i { font-size: 16px !important; }

	/* Filter row: Date + Save All on row 1, Search on row 2 */
	.sc-filter-row { padding: 10px 12px !important; gap: 8px !important; align-items: center !important; }
	.sc-date-col { flex: 1 !important; min-width: 0 !important; height: 40px !important; }
	.sc-saveall-col { flex-shrink: 0 !important; }
	.sc-saveall-col button { height: 40px !important; padding: 0 18px !important; font-size: 13px !important; border-radius: 10px !important; }
	.sc-search-col { flex: 0 0 100% !important; width: 100% !important; max-width: 100% !important; order: 3 !important; }

	/* Table cell padding */
	.rdt_TableCell { padding: 10px 8px !important; }
	.rdt_TableCol { padding: 8px 8px !important; min-height: 36px !important; }

	/* Fix ACTIONS header cutoff — last column */
	.rdt_TableCol:last-child { padding: 8px 4px !important; }
	.rdt_TableCell:last-child { padding: 10px 4px !important; }

	/* Stock input compact */
	.rdt_TableCell input.form-control { max-width: 90px !important; height: 36px !important; padding: 0 8px !important; font-size: 13px !important; }
}
</style>
@endpush

@section('content')
<section class="users-list-wrapper">
<div class="sc-page-header" style="margin-bottom:18px;background:#fff;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;overflow:hidden;">
	<div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;">
		<div style="display:flex;align-items:center;gap:14px;">
			<a href="{{route('stock_check.view.index')}}" style="width:40px;height:40px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:#64748b;transition:all 0.15s;flex-shrink:0;" title="Back to Stock Check"
			   onmouseover="this.style.borderColor='#F27420';this.style.color='#F27420';this.style.background='#fff7ed';"
			   onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
				<i class="fa fa-arrow-left"></i>
			</a>
			<div style="width:48px;height:48px;border-radius:14px;background:#F27420;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(242,116,32,0.25);flex-shrink:0;">
				<i class="fa fa-cube" style="color:#fff;font-size:20px;"></i>
			</div>
			<div>
				<h1 class="sc-page-title" style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Stock Closing</h1>
				<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Manage daily stock closing records</p>
			</div>
		</div>
		</div>
	</div>
</div>
<style>
@media (max-width: 767px) {
	.sc-page-header { border-radius: 12px !important; }
	.sc-page-header > div:first-child { padding: 14px 16px !important; }
	.sc-page-header .sc-page-title { font-size: 17px !important; }
	.sc-page-header div[style*="width:48px"] { width: 40px !important; height: 40px !important; border-radius: 12px !important; }
	.sc-page-header div[style*="width:48px"] i { font-size: 17px !important; }
	.sc-header-btns { display: none !important; }
	.sc-mobile-btns { display: flex !important; }
}
</style>
	<div id="stock-closing-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-list-api="{{route('stock_closing.view.products')}}"
		data-save-one-api="{{route('stock_closing.create.save-one')}}"
		data-save-all-api="{{route('stock_closing.create.save-all')}}"
		data-edit-api="{{route('stock_closing.edit.edit')}}"
		data-query='@json(request()->query())'
	></div>
</section>
@endsection
