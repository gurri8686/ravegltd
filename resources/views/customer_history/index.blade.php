@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }
.ch-icon-m { display: none; }
@media (max-width: 767px) {
	/* Mobile: heading card becomes a clean standalone rounded card (matches reference) */
	.ch-header-card { border-radius: 14px !important; border: 1px solid #eaecf2 !important; box-shadow: 0 1px 4px rgba(0,0,0,0.06) !important; margin-bottom: 14px !important; padding: 14px 16px !important; }
	/* Mobile: swap the users icon for the transaction/exchange icon */
	.ch-icon-d { display: none !important; }
	.ch-icon-m { display: block !important; }
}
</style>
@endpush

@section('content')
<section class="users-list-wrapper">
<div class="ch-header-card" style="margin-bottom:0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px 16px 0 0;padding:18px 24px;box-shadow:none;border:1px solid #eaecf2;border-bottom:none;">
	<div style="display:flex;align-items:center;gap:14px;">
		@if(request()->query('customer'))
		<a href="{{ route('management.customers.view.index') }}" class="ch-back-btn" style="width:40px;height:40px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:#64748b;transition:all 0.15s;flex-shrink:0;" title="Back to Customers"
		   onmouseover="this.style.borderColor='rgb(234, 88, 12)';this.style.color='rgb(234, 88, 12)';this.style.background='#fff7ed';"
		   onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
			<i class="fa fa-arrow-left"></i>
		</a>
		@endif
		<div style="width:40px;height:40px;border-radius:12px;background:rgb(234, 88, 12);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(234,88,12,0.3);flex-shrink:0;">
			<i class="fa fa-users ch-icon-d" style="color:#fff;font-size:18px;"></i>
			<svg class="ch-icon-m" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
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
