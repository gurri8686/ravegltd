@extends('admin.layout')

@section('title', 'Sales & Purchases')

@section('content')
@php
    use Illuminate\Support\Carbon;
    $name   = trim($vendor->first_name . ' ' . $vendor->last_name) ?: $vendor->email;
    $net    = $sales - $purchases;
    $margin = $sales > 0 ? round(($net / $sales) * 100, 1) : 0;
    $avgSale = $salesInvoices > 0 ? $sales / $salesInvoices : 0;
    $peak = 0;
    foreach ($months as $m) { $peak = max($peak, $m->sales, $m->purchases); }
@endphp

<div class="d-flex align-items-center mb-3">
    <a href="{{ route('admin.vendors.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to vendors</a>
    <a href="{{ route('admin.vendors.edit', $vendor->id) }}" class="btn btn-sm btn-accent ms-auto"><i class="bi bi-pencil"></i> Edit</a>
</div>

<div class="panel p-4 mb-3 d-flex align-items-center gap-3">
    @if ($vendor->image && \Illuminate\Support\Str::startsWith($vendor->image, 'uploads/'))
        <img src="{{ asset($vendor->image) }}" style="width:60px;height:60px;border-radius:12px;object-fit:contain;background:var(--surface-2);border:1px solid var(--border);padding:4px;">
    @else
        <span class="av" style="width:56px;height:56px;font-size:22px;">{{ strtoupper(substr($vendor->first_name ?: $vendor->email, 0, 1)) }}</span>
    @endif
    <div class="flex-grow-1">
        <h4 class="mb-0">{{ $name }}</h4>
        <div class="text-muted">{{ $vendor->email }}</div>
    </div>
    <div class="text-end d-none d-md-block">
        <div class="text-muted small">Last sale</div>
        <div class="fw-semibold">{{ $lastSale ? Carbon::parse($lastSale)->format('d M Y') : '—' }}</div>
    </div>
</div>

{{-- Headline KPIs --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon si-green"><i class="bi bi-cash-stack"></i></div><div><div class="label">Total Sales</div><div class="value">£{{ number_format($sales, 2) }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-bag"></i></div><div><div class="label">Total Purchases</div><div class="value">£{{ number_format($purchases, 2) }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon {{ $net >= 0 ? 'si-green' : 'si-grey' }}"><i class="bi bi-graph-up-arrow"></i></div><div><div class="label">Net (Sales − Purch.)</div><div class="value" style="color:{{ $net >= 0 ? '#10b981' : '#ef4444' }};">£{{ number_format($net, 2) }}</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-percent"></i></div><div><div class="label">Margin</div><div class="value">{{ $margin }}%</div></div></div></div>
</div>

{{-- Secondary KPIs --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="mini-card"><i class="bi bi-receipt"></i><div><div class="mini-v">{{ number_format($salesInvoices) }} / {{ number_format($purchaseInvoices) }}</div><div class="mini-l">Invoices (sales / purch.)</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="mini-card"><i class="bi bi-people"></i><div><div class="mini-v">{{ number_format($customers) }}</div><div class="mini-l">Customers served</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="mini-card"><i class="bi bi-truck"></i><div><div class="mini-v">{{ number_format($suppliers) }}</div><div class="mini-l">Suppliers used</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="mini-card"><i class="bi bi-box-seam"></i><div><div class="mini-v">{{ number_format($itemsSold) }}</div><div class="mini-l">Items sold · avg £{{ number_format($avgSale, 0) }}/inv</div></div></div></div>
</div>

{{-- Monthly comparison: sales vs purchases --}}
<div class="panel">
    <div class="panel-head">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart-line me-1"></i> Monthly — Sales vs Purchases</h6>
        <div class="ms-auto d-flex gap-3 small">
            <span><span class="lg-dot" style="background:#10b981;"></span> Sales</span>
            <span><span class="lg-dot" style="background:var(--accent);"></span> Purchases</span>
        </div>
    </div>
    <div class="p-3">
        @forelse ($months as $m)
            <div class="cmp-row">
                <div class="cmp-month">{{ $m->label }}</div>
                <div class="cmp-side">
                    <div class="cmp-bar-wrap"><div class="cmp-bar" style="width:{{ $peak > 0 ? max(round(($m->sales / $peak) * 100), $m->sales > 0 ? 3 : 0) : 0 }}%;background:#10b981;"></div></div>
                    <div class="cmp-val">£{{ number_format($m->sales, 2) }} <span class="text-muted">· {{ $m->salesInv }}</span></div>
                </div>
                <div class="cmp-side">
                    <div class="cmp-bar-wrap"><div class="cmp-bar" style="width:{{ $peak > 0 ? max(round(($m->purchases / $peak) * 100), $m->purchases > 0 ? 3 : 0) : 0 }}%;background:var(--accent);"></div></div>
                    <div class="cmp-val">£{{ number_format($m->purchases, 2) }} <span class="text-muted">· {{ $m->purchInv }}</span></div>
                </div>
            </div>
        @empty
            <div class="text-muted text-center py-4">No sales or purchases recorded.</div>
        @endforelse
    </div>
</div>

<style>
.mini-card{ background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:14px 16px; display:flex; align-items:center; gap:12px; height:100%; }
.mini-card > i{ font-size:20px; color:var(--accent); }
.mini-v{ font-weight:700; font-size:17px; color:var(--text); }
.mini-l{ font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.03em; }
.lg-dot{ display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:4px; }
.cmp-row{ display:grid; grid-template-columns:96px 1fr 1fr; align-items:center; gap:14px; padding:9px 6px; border-bottom:1px solid var(--border); }
.cmp-row:last-child{ border-bottom:0; }
.cmp-month{ font-weight:600; font-size:13px; }
.cmp-side{ display:grid; grid-template-columns:1fr auto; align-items:center; gap:10px; }
.cmp-bar-wrap{ background:var(--surface-2); border-radius:999px; height:9px; overflow:hidden; }
.cmp-bar{ height:100%; border-radius:999px; }
.cmp-val{ font-weight:600; font-size:12.5px; white-space:nowrap; }
@media (max-width:768px){ .cmp-row{ grid-template-columns:1fr; gap:6px; } }
</style>
@endsection
