@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }
@media (max-width: 767px) {
    .cp-header { padding: 12px 14px !important; border-radius: 12px !important; margin-bottom: 10px !important; flex-wrap: nowrap !important; }
    .cp-header .cp-back { width: 32px !important; height: 32px !important; border-radius: 8px !important; flex-shrink: 0 !important; }
    .cp-header .cp-icon { width: 38px !important; height: 38px !important; border-radius: 10px !important; flex-shrink: 0 !important; }
    .cp-header .cp-icon i { font-size: 16px !important; }
    .cp-header h1 { font-size: 17px !important; font-weight: 800 !important; margin: 0 !important; }
    .cp-header p { font-size: 11px !important; margin: 2px 0 0 !important; }
}

/* ── Tablet: date picker matches Amount / Note style ── */
@media (min-width: 768px) and (max-width: 1024px) {
    .odp-tablet-wrap,
    .odp-tablet-wrap .react-datepicker-wrapper,
    .odp-tablet-wrap .react-datepicker__input-container { display: block !important; width: 100% !important; }
    .odp-tablet-wrap input.odp-tablet-inp,
    .odp-tablet-wrap .odp-tablet-inp {
        height: 44px !important;
        border-radius: 12px !important;
        border: 1.5px solid #e2e8f0 !important;
        background: #f8fafc !important;
        padding: 0 14px !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        color: #1e293b !important;
        outline: none !important;
        width: 100% !important;
        box-sizing: border-box !important;
        cursor: pointer !important;
    }
    .odp-tablet-wrap input.odp-tablet-inp:focus {
        border-color: #F27420 !important;
        background: #fff !important;
        box-shadow: 0 0 0 4px rgba(242,116,32,0.08) !important;
    }
}
</style>
@endpush

@section('content')
<section class="users-list-wrapper">
<div class="cp-header" style="margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px;padding:18px 24px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;">
	<div style="display:flex;align-items:center;gap:14px;">
		<a href="{{route('management.customers.view.index')}}" class="cp-back" style="width:40px;height:40px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:#64748b;transition:all 0.15s;" title="Back to Customers"
		   onmouseover="this.style.borderColor='rgb(234, 88, 12)';this.style.color='rgb(234, 88, 12)';this.style.background='#fff7ed';"
		   onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
		    <i class="fa fa-arrow-left"></i>
		</a>
		<div class="cp-icon" style="width:48px;height:48px;border-radius:14px;background:rgb(234, 88, 12);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(234,88,12,0.25);">
			<i class="fa fa-credit-card" style="color:#fff;font-size:20px;"></i>
		</div>
		<div>
			<h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Customer Payments</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Make payments</p>
		</div>
	</div>
</div>
	<div id="customer-payment-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-customer-id="{{request()->query('customer', '')}}"
	></div>
</section>
@endsection
