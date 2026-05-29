@extends('admin.layout')

@section('title', 'Billing')
@section('subtitle', 'Revenue, invoices & subscription payments')

@section('content')
@php $money = fn ($n) => '£' . number_format($n, $n >= 1000 ? 0 : 2); @endphp

{{-- KPI cards --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="kpi-card"><div class="kpi-top"><div><div class="kpi-label">This Month</div><div class="kpi-value">{{ $money($month) }}</div></div><div class="kpi-ic ic-green"><i class="bi bi-cash-stack"></i></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="kpi-card"><div class="kpi-top"><div><div class="kpi-label">This Year</div><div class="kpi-value">{{ $money($year) }}</div></div><div class="kpi-ic ic-violet"><i class="bi bi-calendar3"></i></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="kpi-card"><div class="kpi-top"><div><div class="kpi-label">Outstanding</div><div class="kpi-value text-muted">—</div></div><div class="kpi-ic ic-amber"><i class="bi bi-hourglass-split"></i></div></div><span class="na-tag">No gateway data</span></div></div>
    <div class="col-6 col-xl-3"><div class="kpi-card"><div class="kpi-top"><div><div class="kpi-label">Failed Payments</div><div class="kpi-value text-muted">—</div></div><div class="kpi-ic ic-red"><i class="bi bi-exclamation-octagon"></i></div></div><span class="na-tag">No gateway data</span></div></div>
</div>

{{-- Revenue report --}}
<div class="panel mb-3">
    <div class="panel-head"><h6 class="mb-0 fw-semibold">Revenue Report</h6><span class="ms-auto soft-pill">last 12 months · total {{ $money($windowTotal) }}</span></div>
    <div class="p-3"><canvas id="revChart" height="90"></canvas></div>
</div>

<div class="row g-3">
    {{-- Recent invoices (real) --}}
    <div class="col-12 col-lg-7">
        <div class="panel h-100">
            <div class="panel-head"><h6 class="mb-0 fw-semibold">Recent Invoices</h6></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>#</th><th>Customer</th><th>Date</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    @forelse ($invoices as $inv)
                        <tr>
                            <td class="text-muted">#{{ $inv->id }}</td>
                            <td class="fw-semibold">{{ $inv->customer ?: '—' }}</td>
                            <td class="text-muted small">{{ \Illuminate\Support\Carbon::parse($inv->created_at)->format('d M Y') }}</td>
                            <td class="text-end fw-semibold">£{{ number_format($inv->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No invoices.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {{-- Subscription payments (real records) --}}
    <div class="col-12 col-lg-5">
        <div class="panel h-100">
            <div class="panel-head"><h6 class="mb-0 fw-semibold">Subscription Payments</h6>
                <a href="{{ route('admin.subscriptions.index') }}" class="ms-auto small" style="color:var(--accent);">Manage</a></div>
            <div class="p-2">
                @forelse ($subscriptions as $s)
                    <div class="bil-row">
                        <span class="bil-meta"><span class="bil-name">{{ $s->vendor }}</span><span class="bil-sub">{{ $s->start }} → {{ $s->expire }}</span></span>
                        <span class="pill {{ $s->active ? 'pill-on' : 'pill-off' }}">{{ $s->active ? 'Active' : 'Expired' }}</span>
                    </div>
                @empty
                    <div class="text-muted text-center py-4">No subscriptions.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<style>
.kpi-card{ background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:18px 20px; box-shadow:var(--shadow); height:100%; }
.kpi-top{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.kpi-label{ font-size:13px; color:var(--muted); font-weight:500; } .kpi-value{ font-size:24px; font-weight:800; color:var(--text); line-height:1.15; margin-top:4px; }
.kpi-ic{ width:46px; height:46px; flex:0 0 46px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; }
.ic-violet{ background:rgba(139,92,246,.15); color:#8b5cf6; } .ic-green{ background:rgba(16,185,129,.16); color:#10b981; }
.ic-amber{ background:rgba(245,158,11,.16); color:#f59e0b; } .ic-red{ background:rgba(239,68,68,.16); color:#ef4444; }
.na-tag{ display:inline-block; margin-top:8px; font-size:10px; font-weight:600; color:var(--muted); background:var(--surface-2); border:1px solid var(--border); border-radius:6px; padding:1px 7px; }
.soft-pill{ font-size:12px; font-weight:500; color:var(--muted); background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:4px 10px; }
.h-100{ height:100%; }
.bil-row{ display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border-bottom:1px solid var(--border); } .bil-row:last-child{ border-bottom:0; }
.bil-meta{ display:flex; flex-direction:column; min-width:0; } .bil-name{ font-weight:600; font-size:13.5px; } .bil-sub{ font-size:11.5px; color:var(--muted); }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function(){
    var css=getComputedStyle(document.documentElement), accent=(css.getPropertyValue('--accent')||'#8b5cf6').trim(), muted=(css.getPropertyValue('--muted')||'#6b7280').trim(), border=(css.getPropertyValue('--border')||'#e5e7eb').trim();
    Chart.defaults.color=muted; Chart.defaults.font.family="'Segoe UI',system-ui,sans-serif";
    var rc=document.getElementById('revChart').getContext('2d'); var g=rc.createLinearGradient(0,0,0,200); g.addColorStop(0,'rgba(16,185,129,.30)'); g.addColorStop(1,'rgba(16,185,129,0)');
    new Chart(rc,{type:'line',data:{labels:@json($labels),datasets:[{data:@json($revenueSeries),borderColor:'#10b981',backgroundColor:g,fill:true,tension:.4,borderWidth:2.5,pointRadius:2}]},options:{plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return '£'+Intl.NumberFormat('en').format(c.parsed.y);}}}},scales:{y:{beginAtZero:true,grid:{color:border},ticks:{callback:function(v){return '£'+Intl.NumberFormat('en',{notation:'compact'}).format(v);}}},x:{grid:{display:false}}}}});
})();
</script>
@endsection
