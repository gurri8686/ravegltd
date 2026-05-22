@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>.content-header { display: none !important; }</style>
@endpush

@section('content')
<section class="users-list-wrapper">
<div style="margin-bottom:18px;background:#fff;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;overflow:hidden;">
	<div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;">
		<div style="display:flex;align-items:center;gap:14px;">
			<div style="width:48px;height:48px;border-radius:14px;background:#F27420;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(242,116,32,0.25);flex-shrink:0;">
				<i class="fa fa-cubes" style="color:#fff;font-size:20px;"></i>
			</div>
			<div>
				<h1 class="sm-page-title" style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Stock Manager</h1>
				<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Manage closing stock and check stock levels</p>
			</div>
		</div>
	</div>
	<div class="sm-buttons-row" style="display:flex;gap:10px;padding:0 24px 18px;flex-wrap:wrap;">
		<a href="{{ route('stock_closing.view.index') }}" style="height:38px;padding:0 16px;border-radius:10px;border:none;background:#F27420;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;text-decoration:none;box-shadow:0 2px 8px rgba(242,116,32,0.2);white-space:nowrap;">
			<i class="fa fa-cube" style="font-size:12px;"></i> Stock Closing
		</a>
		<a href="{{ route('customer_return.view.index') }}" style="height:38px;padding:0 16px;border-radius:10px;border:none;background:#0ea5e9;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;text-decoration:none;box-shadow:0 2px 8px rgba(14,165,233,0.2);white-space:nowrap;">
			<i class="fa fa-undo" style="font-size:12px;"></i> Customer Return
		</a>
		<a href="{{ route('supplier_return.view.index') }}" style="height:38px;padding:0 16px;border-radius:10px;border:none;background:#8b5cf6;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;text-decoration:none;box-shadow:0 2px 8px rgba(139,92,246,0.2);white-space:nowrap;">
			<i class="fa fa-truck" style="font-size:12px;"></i> Supplier Return
		</a>
		<a href="{{ route('dump_return.view.index') }}" style="height:38px;padding:0 16px;border-radius:10px;border:none;background:#ef4444;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;text-decoration:none;box-shadow:0 2px 8px rgba(239,68,68,0.2);white-space:nowrap;">
			<i class="fa fa-trash" style="font-size:12px;"></i> Dump
		</a>
		<a href="{{ route('stock_check.view.index') }}" style="height:38px;padding:0 16px;border-radius:10px;border:none;background:#16a34a;color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;text-decoration:none;box-shadow:0 2px 8px rgba(22,163,74,0.2);white-space:nowrap;">
			<i class="fa fa-bar-chart" style="font-size:12px;"></i> Stock Check
		</a>
	</div>
</div>
<style>
@media (max-width: 767px) {
	.sm-page-title { font-size: 17px !important; }
	.sm-buttons-row { padding: 0 16px 14px !important; gap: 8px !important; }
	.sm-buttons-row a { font-size: 12px !important; height: 34px !important; padding: 0 12px !important; }
}
</style>
	<div id="stock-manager-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-list-api="{{route('management.products.view.list')}}"
		data-excel-url="{{route('excel.stock_manager')}}"
	></div>
</section>
@endsection
