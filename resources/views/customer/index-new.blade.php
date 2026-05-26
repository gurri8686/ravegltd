@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection



@push('stylesheets')
<style>
.content-header { display: none !important; }


@media (max-width: 767px) {
    .cust-header-card {
        padding: 12px 14px !important;
        border-radius: 12px !important;
        margin-bottom: 14px !important;
        flex-wrap: nowrap !important;
    }
    .cust-header-card .cust-icon {
        width: 38px !important; height: 38px !important;
        border-radius: 10px !important; flex-shrink: 0 !important;
    }
    .cust-header-card .cust-icon i { font-size: 16px !important; }
    .cust-header-card h1 { font-size: 17px !important; font-weight: 800 !important; margin: 0 !important; }
    .cust-header-card p { font-size: 11px !important; margin: 2px 0 0 !important; }
    .cust-header-card .cust-create-btn {
        height: 34px !important; padding: 0 14px !important;
        font-size: 12px !important; border-radius: 9px !important;
        flex-shrink: 0 !important; white-space: nowrap !important;
        align-self: center !important;
    }
}
@media (min-width: 768px) {
    .cust-header-card { flex-wrap: nowrap !important; align-items: center !important; }
    .cust-create-btn { align-self: center !important; }
}
</style>
@endPush

@section('content')
<section class="users-list-wrapper">
<div class="cust-header-card" style="margin-bottom:0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px 16px 0 0;padding:18px 24px;box-shadow:none;border:1px solid #eaecf2;border-bottom:none;">
	<div style="display:flex;align-items:center;gap:14px;">
		<div class="cust-icon" style="width:48px;height:48px;border-radius:14px;background:rgb(234, 88, 12);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(234,88,12,0.25);">
			<i class="fa fa-users" style="color:#fff;font-size:20px;"></i>
		</div>
		<div>
			<h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Customers</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Manage customer records</p>
		</div>
	</div>
	<a href="{{route('management.customers.create.create')}}" class="cust-create-btn" style="height:40px;padding:0 20px;border-radius:10px;border:none;background:rgb(234, 88, 12);color:#fff;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(234,88,12,0.3);">
		<span style="font-size:16px;font-weight:400;line-height:1;">+</span> Create New
	</a>
</div>
	<div id="customers-index-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-list-api="{{route('management.customers.view.list')}}"
	></div>
</section>
@endsection
