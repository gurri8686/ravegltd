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

{{-- Monthly comparison: sales vs purchases (chart + table) --}}
@php
    $asc = collect($months)->reverse()->values(); // chronological (oldest → newest)
@endphp
<div class="row g-3">
    <div class="col-12 col-xl-7">
        <div class="panel h-100">
            <div class="panel-head">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-bar-chart-line me-1"></i> Monthly — Sales vs Purchases</h6>
                <div class="ms-auto d-flex gap-3 small">
                    <span><span class="lg-dot" style="background:#10b981;"></span> Sales</span>
                    <span><span class="lg-dot" style="background:var(--accent);"></span> Purchases</span>
                    <span><span class="lg-dot" style="background:#f59e0b;"></span> Net</span>
                </div>
            </div>
            <div class="p-3">
                @if ($asc->count())
                    <canvas id="spChart" height="150"></canvas>
                @else
                    <div class="text-muted text-center py-4">No sales or purchases recorded.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-5">
        <div class="panel h-100">
            <div class="panel-head"><h6 class="mb-0 fw-semibold">Monthly breakdown</h6></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0 mth-table">
                    <thead><tr><th>Month</th><th class="text-end">Sales</th><th class="text-end">Purchases</th><th class="text-end">Net</th></tr></thead>
                    <tbody>
                    @forelse ($months as $m)
                        @php $net = $m->sales - $m->purchases; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $m->label }}</td>
                            <td class="text-end" style="color:#10b981;">£{{ number_format($m->sales, 0) }}</td>
                            <td class="text-end" style="color:var(--accent);">£{{ number_format($m->purchases, 0) }}</td>
                            <td class="text-end fw-semibold" style="color:{{ $net >= 0 ? '#10b981' : '#ef4444' }};">£{{ number_format($net, 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No data.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.mini-card{ background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:14px 16px; display:flex; align-items:center; gap:12px; height:100%; }
.mini-card > i{ font-size:20px; color:var(--accent); }
.mini-v{ font-weight:700; font-size:17px; color:var(--text); }
.mini-l{ font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.03em; }
.lg-dot{ display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:4px; }
.h-100{ height:100%; }
.mth-table thead th{ font-size:10.5px; }
.mth-table tbody td{ padding:9px 16px; border-color:var(--border); font-size:13px; }
.mth-table tbody tr:hover{ background:var(--surface-2); }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function(){
    var el = document.getElementById('spChart'); if (!el) return;
    var css = getComputedStyle(document.documentElement);
    var accent = (css.getPropertyValue('--accent') || '#8b5cf6').trim();
    var muted  = (css.getPropertyValue('--muted') || '#6b7280').trim();
    var border = (css.getPropertyValue('--border') || '#e5e7eb').trim();
    Chart.defaults.color = muted; Chart.defaults.font.family = "'Segoe UI',system-ui,sans-serif";

    var labels = @json($asc->pluck('label'));
    var sales  = @json($asc->pluck('sales'));
    var purch  = @json($asc->pluck('purchases'));
    var net    = @json($asc->map(fn ($m) => round($m->sales - $m->purchases, 2))->values());
    var gbp = function (v) { return '£' + Intl.NumberFormat('en').format(v); };

    new Chart(el, {
        data: {
            labels: labels,
            datasets: [
                { type: 'bar', label: 'Sales', data: sales, backgroundColor: '#10b981', borderRadius: 5, maxBarThickness: 22, order: 2 },
                { type: 'bar', label: 'Purchases', data: purch, backgroundColor: accent, borderRadius: 5, maxBarThickness: 22, order: 2 },
                { type: 'line', label: 'Net', data: net, borderColor: '#f59e0b', backgroundColor: '#f59e0b', borderWidth: 2.5, tension: .35, pointRadius: 3, pointBackgroundColor: '#f59e0b', order: 1 }
            ]
        },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + gbp(c.parsed.y); } } }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: border }, ticks: { callback: function (v) { return '£' + Intl.NumberFormat('en', { notation: 'compact' }).format(v); } } },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
@endsection
