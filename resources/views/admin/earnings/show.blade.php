@extends('admin.layout')

@section('title', 'Earnings')

@section('content')
@php
    use Illuminate\Support\Carbon;
    $s = $site->stats;
@endphp

<div class="d-flex align-items-center mb-3">
    <a href="{{ route('admin.earnings.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to earnings</a>
    <span class="pill {{ $site->status ? 'pill-on' : 'pill-off' }} ms-auto">{{ $site->status ? 'Active' : 'Inactive' }}</span>
</div>

<div class="panel p-4 mb-3 d-flex align-items-center gap-3">
    <div class="stat-icon si-neutral" style="width:54px;height:54px;font-size:24px;"><i class="bi bi-buildings"></i></div>
    <div>
        <h4 class="mb-0">{{ $site->domain ?: $site->database }}</h4>
        <div class="text-muted">{{ $site->subdomain }} · <code>{{ $site->database }}</code></div>
    </div>
</div>

@if (!$s)
    <div class="alert alert-warning">Earnings could not be read from this business's database (<code>{{ $site->database }}</code>) — it may be offline or empty.</div>
@endif

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-4"><div class="stat-card"><div class="stat-icon si-green"><i class="bi bi-cash-stack"></i></div><div><div class="label">Total Sales</div><div class="value">{{ $s ? '£'.number_format($s['sales'], 2) : '—' }}</div></div></div></div>
    <div class="col-6 col-lg-4"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-bag"></i></div><div><div class="label">Total Purchases</div><div class="value">{{ $s ? '£'.number_format($s['purchases'], 2) : '—' }}</div></div></div></div>
    <div class="col-6 col-lg-4"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-receipt"></i></div><div><div class="label">Invoices</div><div class="value">{{ $s ? number_format($s['invoices']) : '—' }}</div></div></div></div>
</div>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-4"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-people"></i></div><div><div class="label">Customers</div><div class="value">{{ $s ? number_format($s['customers']) : '—' }}</div></div></div></div>
    <div class="col-6 col-lg-4"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-box-seam"></i></div><div><div class="label">Products</div><div class="value">{{ $s ? number_format($s['products']) : '—' }}</div></div></div></div>
    <div class="col-6 col-lg-4"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-truck"></i></div><div><div class="label">Suppliers</div><div class="value">{{ $s ? number_format($s['suppliers']) : '—' }}</div></div></div></div>
</div>

<div class="panel">
    <div class="panel-head"><h6 class="mb-0 fw-semibold"><i class="bi bi-hdd-network me-1"></i> Business details</h6></div>
    <div class="p-4">
        <div class="row g-3">
            <div class="col-sm-6"><div class="info-label">Domain</div><div class="info-val">{{ $site->domain ?: '—' }}</div></div>
            <div class="col-sm-6"><div class="info-label">Subdomain</div><div class="info-val">{{ $site->subdomain ?: '—' }}</div></div>
            <div class="col-sm-6"><div class="info-label">Database</div><div class="info-val"><code>{{ $site->database }}</code></div></div>
            <div class="col-sm-6"><div class="info-label">Created</div><div class="info-val">{{ $site->created_at ? uk_ts($site->created_at, 'd M Y') : '—' }}</div></div>
        </div>
    </div>
</div>

<style>
.info-label{ font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); }
.info-val{ font-weight:600; color:var(--text); word-break:break-word; }
</style>
@endsection
