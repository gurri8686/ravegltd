@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection



@push('stylesheets')
<style>.content-header { display: none !important; }</style>
@endpush

@section('content')
<section class="users-list-wrapper">
	<div id="daily-book-purchase-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-supplier-list-api="{{route('daily_report.daily_book_purchase.view.suppliers')}}"
		data-list-api="{{route('daily_report.daily_book_purchase.view.list')}}"
		data-print-api="{{route('daily_report.daily_book_purchase.view.print')}}"
	></div>
</section>
@endsection
