@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }
@media (max-width: 767px) {
    .cr-header { padding: 12px 14px !important; border-radius: 12px !important; margin-bottom: 10px !important; flex-wrap: nowrap !important; }
    .cr-header .cr-back { width: 32px !important; height: 32px !important; border-radius: 8px !important; flex-shrink: 0 !important; }
    .cr-header .cr-icon { width: 38px !important; height: 38px !important; border-radius: 10px !important; flex-shrink: 0 !important; }
    .cr-header .cr-icon i { font-size: 16px !important; }
    .cr-header h1 { font-size: 17px !important; font-weight: 800 !important; margin: 0 !important; }
    .cr-header p { font-size: 11px !important; margin: 2px 0 0 !important; }
}
</style>
@endpush

@section('content')
<section class="users-list-wrapper">
<div class="cr-header" style="margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px;padding:18px 24px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;">
	<div style="display:flex;align-items:center;gap:14px;">
		<a href="{{route('management.customers.view.index')}}" class="cr-back" style="width:40px;height:40px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:#64748b;transition:all 0.15s;" title="Back to Customers"
		   onmouseover="this.style.borderColor='#F27420';this.style.color='#F27420';this.style.background='#fff7ed';"
		   onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
		    <i class="fa fa-arrow-left"></i>
		</a>
		<div class="cr-icon" style="width:48px;height:48px;border-radius:14px;background:#F27420;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(242,116,32,0.25);">
			<i class="fa fa-undo" style="color:#fff;font-size:20px;"></i>
		</div>
		<div>
			<h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Customer Returns</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Manage and track your product return records</p>
		</div>
	</div>
</div>
	<div id="customers-return-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-customer-id="{{request()->query('customer', '')}}"
		data-customers-list-api="/customer_return/view/customers"
		data-products-list-api="/customer_return/view/products"
		data-invoices-returns-api="/customer_return/view/returns"
		data-invoices-list-api="/customer_return/view/invoices"
		data-invoices-product-api="/customer_return/view/product"
		data-invoices-return-create-api="/customer_return/view/return/create"
		data-invoices-return-update-api="/customer_return/view/return/update"
		data-invoices-return-delete-api="/customer_return/view/return/delete"
	></div>
</section>
@endsection