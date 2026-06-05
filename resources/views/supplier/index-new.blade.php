@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }
@media (max-width: 767px) {
    .supp-header-card {
        padding: 14px 16px !important;
        border-radius: 16px !important;
        margin-bottom: 14px !important;
        flex-wrap: nowrap !important;
        border: 1px solid #eaecf2 !important;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06) !important;
    }
    .supp-header-card .supp-icon {
        width: 44px !important; height: 44px !important;
        border-radius: 13px !important; flex-shrink: 0 !important;
        box-shadow: none !important;
    }
    .supp-header-card .supp-icon i { font-size: 18px !important; }
    .supp-header-card h1 { font-size: 19px !important; font-weight: 800 !important; margin: 0 !important; }
    .supp-header-card p { font-size: 12px !important; margin: 2px 0 0 !important; }
    .supp-header-card .supp-create-btn {
        height: 38px !important; padding: 0 16px !important;
        font-size: 12.5px !important; font-weight: 700 !important; border-radius: 10px !important;
        background: rgb(234, 88, 12) !important; color: #fff !important;
        border: none !important; box-shadow: 0 2px 8px rgba(234,88,12,0.3) !important;
        flex-shrink: 0 !important; white-space: nowrap !important;
        align-self: center !important;
    }
}
@media (min-width: 768px) {
    .supp-header-card { flex-wrap: nowrap !important; align-items: center !important; }
    .supp-create-btn { align-self: center !important; }
}
</style>
@endpush

@section('content')
<section class="users-list-wrapper">
<div class="supp-header-card" style="margin-bottom:0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px 16px 0 0;padding:18px 24px;box-shadow:none;border:1px solid #eaecf2;border-bottom:none;">
	<div style="display:flex;align-items:center;gap:14px;">
		<div class="supp-icon" style="width:48px;height:48px;border-radius:14px;background:rgb(234, 88, 12);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(242,116,32,0.25);">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
		</div>
		<div>
			<h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Suppliers</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Manage supplier records</p>
		</div>
	</div>
	<a href="{{route('management.suppliers.create.create')}}" class="supp-create-btn" style="height:40px;padding:0 20px;border-radius:10px;border:none;background:rgb(234, 88, 12);color:#fff;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(242,116,32,0.3);">
		<span style="font-size:16px;font-weight:400;line-height:1;">+</span> Create New
	</a>
</div>
	<div id="suppliers-index-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-list-api="{{route('management.suppliers.view.list')}}"
	></div>
</section>
@endsection
