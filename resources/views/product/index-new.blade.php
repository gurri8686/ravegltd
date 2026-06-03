@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection



@push('stylesheets')
<style>
.content-header { display: none !important; }
.pid-badge { display:none; }
.dropdown-menu .dropdown-item:hover,
.dropdown-menu .dropdown-item:focus {
	background: #FFF5ED !important;
	color: rgb(234, 88, 12) !important;
}
@media (max-width: 767px) {
	/* Header — clean white card (mobile): white surface, soft shadow, light-grey border, 14px radius. */
	.products-header-card {
		display: flex !important; align-items: center !important;
		gap: 13px !important;
		padding: 14px !important;
		border-radius: 14px !important;
		margin-bottom: 14px !important;
		flex-wrap: nowrap !important;
		background: rgb(255, 255, 255) !important;
		border: 1px solid rgb(234, 236, 242) !important;
		box-shadow: rgba(0, 0, 0, 0.06) 0px 1px 4px !important;
	}
	.products-header-card > div:first-child {
		flex: 1 1 0; min-width: 0;
	}
	.products-create-btn {
		flex-shrink: 0 !important;
		align-self: center !important;
	}
	.products-header-card > div > div[style*="width:40px"] {
		width: 38px !important; height: 38px !important; border-radius: 10px !important;
	}
	.products-header-card > div > div[style*="width:40px"] i { font-size: 16px !important; }
	.products-header-card h1 { font-size: 17px !important; }
	.products-header-card p { font-size: 10.5px !important; }
	.products-create-btn { height: 34px !important; padding: 0 14px !important; font-size: 12px !important; border-radius: 9px !important; }

	/* (Mobile now renders the React card UI in ProductsIndexApp.js — no table wrapper to de-card here.
	   The old `#products-index-app > div > div` reset was removed because it would strip the new
	   summary/search/category/product cards' own backgrounds & borders.) */

	/* Filter row — on the page directly under the header card (no card background, no divider) */
	#products-index-app .pi-header {
		padding: 0 !important; gap: 8px !important;
		border-bottom: none !important; background: transparent !important;
		margin-bottom: 12px !important;
	}
	#products-index-app .pi-header > div[style*="width:200px"] { width: auto !important; min-width: 85px !important; order: 2 !important; }
	#products-index-app .pi-header > div[style*="width:200px"] .react-select__control { min-height: 34px !important; height: 34px !important; border-radius: 8px !important; font-size: 12px !important; }
	#products-index-app .pi-header > div[style*="width:200px"] .react-select__value-container { height: 34px !important; padding: 0 8px !important; }
	#products-index-app .pi-header > div[style*="width:200px"] .react-select__indicators { height: 34px !important; }
	#products-index-app .pi-header > div[style*="width:200px"] .react-select__single-value { font-size: 12px !important; }
	/* Search box → .sm-search UI: flex row [icon + input], white surface, line-2 border, r-md radius, sh-1.
	   Tokens: --surface #fff, --line-2 #eaecf2, --r-md 11px, --sh-1 0 1px 2px rgba(16,24,40,.04), --faint #9ca3af. */
	#products-index-app .pi-search {
		flex: 1 !important; order: 1 !important; min-width: 0 !important; max-width: none !important;
		display: flex !important; align-items: center !important; gap: 9px !important;
		height: 46px !important; padding: 0 13px !important;
		background: #ffffff !important;
		border: 1px solid #eaecf2 !important;
		border-radius: 11px !important;
		box-shadow: 0 1px 2px rgba(16,24,40,0.04) !important;
	}
	/* icon becomes an in-flow flex item (was absolutely positioned over the input) */
	#products-index-app .pi-search svg {
		position: static !important; left: auto !important; top: auto !important; transform: none !important;
		width: 18px !important; height: 18px !important; flex-shrink: 0 !important;
		color: #9ca3af !important; stroke: #9ca3af !important;
	}
	/* input fills the box, borderless/transparent (the container carries the chrome) */
	#products-index-app .pi-search input {
		flex: 1 !important; height: 100% !important;
		border: none !important; background: transparent !important; box-shadow: none !important;
		border-radius: 0 !important; padding: 0 !important;
		font-size: 13.5px !important; color: #1e293b !important;
	}

	/* Force ALL columns visible on mobile — override DataTable responsive hiding */
	#products-index-app .rdt_TableCol,
	#products-index-app .rdt_TableCell,
	#products-index-app [data-column-id] { display: flex !important; white-space: nowrap !important; }
	#products-index-app .rdt_TableCol[style*="display: none"],
	#products-index-app .rdt_TableCell[style*="display: none"] { display: flex !important; }

	/* Pagination */
	#products-index-app .rdt_Pagination {
		padding: 8px 10px !important; min-height: 40px !important;
		font-size: 11px !important;
	}

	/* Actions */
	#products-index-app .pi-action-btns { gap: 4px !important; }
	#products-index-app .pi-action-btns a {
		width: 28px !important; height: 28px !important;
		font-size: 11px !important; border-radius: 8px !important;
	}
}
@media (min-width: 768px) {
	.products-header-card { flex-wrap: nowrap !important; gap: 8px !important; align-items: center !important; }
}
</style>
@endPush

@section('content')
<section class="users-list-wrapper">
<div class="products-header-card" style="margin-bottom:0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px 16px 0 0;padding:18px 24px;box-shadow:none;border:1px solid #eaecf2;border-bottom:none;">
	<div style="display:flex;align-items:center;gap:14px;">
		<div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,rgb(234, 88, 12) 0%,#c2410c 100%);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(234,88,12,0.3);flex-shrink:0;">
			<i class="fa fa-shopping-bag" style="color:#fff;font-size:18px;"></i>
		</div>
		<div>
			<h1 style="font-size:18px;font-weight:800;color:#0f172a;line-height:1.2;letter-spacing:-0.3px;margin:0;font-family:inherit;">Products</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Manage your product catalog</p>
		</div>
	</div>
	<a class="products-create-btn" href="/management/products/create/create" style="height:40px;padding:0 20px;border-radius:10px;border:none;background:linear-gradient(135deg,rgb(234, 88, 12),#e0600e);color:#fff;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:8px;box-shadow:0 2px 8px rgba(234,88,12,0.3);">
		<i class="fa fa-plus"></i> Create Product
	</a>
</div>
	<div id="products-index-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-list-api="{{route('management.products.view.list')}}"
		data-profit-loss-api="{{route('product_profit_loss.view.profit_loss')}}"
		data-print-url="{{route('print.products')}}"
		data-excel-url="{{route('excel.products')}}"
	></div>
</section>
@endsection
