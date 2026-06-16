@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>.content-header { display: none !important; }</style>
@endPush

@section('content')
<section class="users-list-wrapper">
	<div id="new-sales-invoice-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-generate-api="/data_entry/sales_entry/invoice/generate"
		data-customer-create-api="{{ route('management.settings.import.entities.customer_quick_add') }}"
		data-back-url="{{route('daily_report.daily_book_sales.view.index')}}"
		data-show-suppliers="{{ (int) (\App\Models\GeneralSetting::where('setting', 'show_suppliers')->value('status') ?? 1) }}"
	></div>
</section>
@endsection
