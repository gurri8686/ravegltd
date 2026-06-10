@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>.content-header { display: none !important; }</style>
@endpush

@section('content')
<section class="users-list-wrapper">
<div style="margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px;padding:18px 24px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;">
	<div style="display:flex;align-items:center;gap:14px;">
		<a href="{{route('management.suppliers.view.index')}}" style="width:40px;height:40px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:#64748b;transition:all 0.15s;" title="Back to Suppliers"
		   onmouseover="this.style.borderColor='rgb(234, 88, 12)';this.style.color='rgb(234, 88, 12)';this.style.background='#fff7ed';"
		   onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
		    <i class="fa fa-arrow-left"></i>
		</a>
		<div style="width:48px;height:48px;border-radius:14px;background:rgb(234, 88, 12);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(234,88,12,0.25);">
			<i class="fa fa-undo" style="color:#fff;font-size:20px;"></i>
		</div>
		<div>
			<h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Supplier Returns</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Manage return records</p>
		</div>
	</div>
	{{-- <a href="{{route('supplier_return.view.history')}}" style="height:38px;padding:0 18px;border-radius:10px;border:none;background:rgb(234, 88, 12);color:#fff;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(234,88,12,0.3);flex-shrink:0;">
		<i class="fa fa-history" style="font-size:12px;"></i> View Return History
	</a> --}}
</div>
	<div id="suppliers-return-app"
		data-no-header="1"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-suppliers-list-api="/supplier_return/view/suppliers"
		data-products-list-api="/supplier_return/view/products"
		data-invoices-returns-api="/supplier_return/view/returns"
		data-invoices-list-api="/supplier_return/view/invoices"
		data-supplier-products-api="/supplier_return/view/supplier-products"
		data-invoices-product-api="/supplier_return/view/product"
		data-invoices-return-create-api="/supplier_return/view/return/create"
		data-invoices-return-update-api="/supplier_return/view/return/update"
		data-invoices-return-delete-api="/supplier_return/view/return/delete"
		data-history-url="{{route('supplier_return.view.history')}}"
		data-print-url="{{route('print.supplier_return')}}"
		data-excel-url="{{route('excel.supplier_return')}}"
		data-returnable-print-url="{{route('print.supplier_returnable')}}"
		data-returnable-excel-url="{{route('excel.supplier_returnable')}}"
	></div>
</section>
@endsection