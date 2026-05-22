@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>.content-header { display: none !important; }</style>
@endpush

@section('content')
<section class="users-list-wrapper">
<div style="margin-bottom:0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px 16px 0 0;padding:18px 24px;box-shadow:none;border:1px solid #eaecf2;border-bottom:none;">
	<div style="display:flex;align-items:center;gap:14px;">
		<div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#f97316 0%,#ea580c 100%);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(249,115,22,0.3);flex-shrink:0;">
			<i class="fa fa-users" style="color:#fff;font-size:18px;"></i>
		</div>
		<div>
			<h1 style="font-size:18px;font-weight:800;color:#0f172a;line-height:1.2;letter-spacing:-0.3px;margin:0;font-family:inherit;">Customer History</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Transaction history</p>
		</div>
	</div>
</div>
	<div id="customer-history-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-customer-id="{{request()->query('customer', '')}}"
		data-customer-list-api="{{route('customer_history.view.customers')}}"
		data-history-list-api="{{route('customer_history.view.history')}}"
		data-history-email-api="{{route('customer_history.view.email')}}"
		data-history-print-api="{{route('customer_history.view.print')}}"
		data-history-statement-api="{{route('customer_history.view.statement')}}"
		data-print-api="{{route('print.customer_history')}}"
		data-excel-api="{{route('excel.customer_history')}}"
	></div>
</section>
@endsection
