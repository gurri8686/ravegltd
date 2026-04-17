@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection



@push('stylesheets')
<style>.content-header { display: none !important; }</style>
@endpush

@section('content')
<section class="users-list-wrapper">
	<div id="daily-book-sales-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-customer-list-api="{{route('daily_report.daily_book_sales.view.customers')}}"
		data-list-api="{{route('daily_report.daily_book_sales.view.list')}}"
		data-print-api="{{route('daily_report.daily_book_sales.view.print')}}"
		data-email-api="{{route('daily_report.daily_book_sales.view.email')}}"
		data-statement-api="{{route('daily_report.daily_book_sales.view.statement')}}"
	></div>
</section>
@endsection
