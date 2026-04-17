@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>.content-header { display: none !important; }</style>
@endPush

@section('content')
<section class="users-list-wrapper">
	<div id="new-purchase-invoice-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-suppliers-api="/payments/supplier_payment/create/suppliers/list"
		data-products-api="/data_entry/purchase_entry/invoice/products/list"
		data-generate-api="/data_entry/purchase_entry/invoice/generate"
		data-back-url="{{route('daily_report.daily_book_purchase.view.index')}}"
	></div>
</section>
@endsection
