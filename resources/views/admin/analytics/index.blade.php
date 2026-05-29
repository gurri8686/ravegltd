@extends('admin.layout')

@section('title', 'Analytics')
@section('subtitle', 'Platform performance & trends')

@section('content')
@php $money = fn ($n) => '£' . number_format($n, $n >= 1000 ? 0 : 2); @endphp

{{-- KPI cards --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="kpi-card"><div class="kpi-top"><div><div class="kpi-label">Total Revenue</div><div class="kpi-value">{{ $money($revenue) }}</div></div><div class="kpi-ic ic-violet"><i class="bi bi-cash-stack"></i></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="kpi-card"><div class="kpi-top"><div><div class="kpi-label">Active Vendors</div><div class="kpi-value">{{ number_format($activeVendors) }}</div></div><div class="kpi-ic ic-green"><i class="bi bi-check-lg"></i></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="kpi-card"><div class="kpi-top"><div><div class="kpi-label">Avg Revenue / Vendor</div><div class="kpi-value">{{ $money($avgPerVendor) }}</div></div><div class="kpi-ic ic-blue"><i class="bi bi-bar-chart-line-fill"></i></div></div></div></div>
    <div class="col-6 col-xl-3"><div class="kpi-card"><div class="kpi-top"><div><div class="kpi-label">Conversion (subscribed)</div><div class="kpi-value">{{ $conversion }}%</div></div><div class="kpi-ic ic-amber"><i class="bi bi-graph-up-arrow"></i></div></div></div></div>
</div>

{{-- Revenue trend + Subscription distribution --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-lg-8"><div class="panel h-100"><div class="panel-head"><h6 class="mb-0 fw-semibold">Revenue Trend</h6><span class="ms-auto soft-pill">last 12 months</span></div><div class="p-3"><canvas id="revChart" height="115"></canvas></div></div></div>
    <div class="col-12 col-lg-4"><div class="panel h-100"><div class="panel-head"><h6 class="mb-0 fw-semibold">Subscription Distribution</h6></div>
        <div class="p-3 d-flex align-items-center gap-3 flex-wrap flex-md-nowrap">
            <div class="donut-wrap"><canvas id="subChart"></canvas><div class="donut-center"><div class="donut-total">{{ $totalVendors }}</div><div class="donut-cap">Vendors</div></div></div>
            <div class="flex-grow-1">
                <div class="leg-row"><span><span class="lg-dot" style="background:#8b5cf6;"></span> Active</span><b>{{ $subActive }}</b></div>
                <div class="leg-row"><span><span class="lg-dot" style="background:#ef4444;"></span> Expired</span><b>{{ $subExpired }}</b></div>
                <div class="leg-row"><span><span class="lg-dot" style="background:#cbd5e1;"></span> None</span><b>{{ $subNone }}</b></div>
            </div>
        </div></div></div>
</div>

{{-- Vendor growth + Top vendors --}}
<div class="row g-3">
    <div class="col-12 col-lg-7"><div class="panel h-100"><div class="panel-head"><h6 class="mb-0 fw-semibold">Vendor Growth</h6><span class="ms-auto soft-pill">cumulative</span></div><div class="p-3"><canvas id="growthChart" height="140"></canvas></div></div></div>
    <div class="col-12 col-lg-5"><div class="panel h-100"><div class="panel-head"><h6 class="mb-0 fw-semibold">Top Vendors by Revenue</h6></div>
        <div class="p-2">
            @forelse ($topVendors as $i => $v)
                <div class="tv-row"><span class="tv-rank">{{ $i + 1 }}</span><span class="tv-name">{{ $v->name }}</span><span class="tv-total">{{ $money($v->total) }}</span></div>
            @empty
                <div class="text-muted text-center py-4">No sales yet.</div>
            @endforelse
        </div></div></div>
</div>

<style>
.kpi-card{ background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:18px 20px; box-shadow:var(--shadow); height:100%; }
.kpi-top{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.kpi-label{ font-size:13px; color:var(--muted); font-weight:500; }
.kpi-value{ font-size:24px; font-weight:800; color:var(--text); line-height:1.15; margin-top:4px; }
.kpi-ic{ width:46px; height:46px; flex:0 0 46px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; }
.ic-violet{ background:rgba(139,92,246,.15); color:#8b5cf6; } .ic-green{ background:rgba(16,185,129,.16); color:#10b981; }
.ic-amber{ background:rgba(245,158,11,.16); color:#f59e0b; } .ic-blue{ background:rgba(59,130,246,.16); color:#3b82f6; }
.soft-pill{ font-size:12px; font-weight:500; color:var(--muted); background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:4px 10px; }
.h-100{ height:100%; }
.donut-wrap{ position:relative; width:130px; height:130px; flex:0 0 130px; }
.donut-center{ position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none; }
.donut-total{ font-size:24px; font-weight:800; color:var(--text); line-height:1; } .donut-cap{ font-size:10px; color:var(--muted); text-transform:uppercase; }
.leg-row{ display:flex; align-items:center; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border); font-size:13px; } .leg-row:last-child{ border-bottom:0; }
.lg-dot{ display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; }
.tv-row{ display:flex; align-items:center; gap:12px; padding:10px 12px; border-bottom:1px solid var(--border); } .tv-row:last-child{ border-bottom:0; }
.tv-rank{ width:22px; height:22px; flex:0 0 22px; border-radius:6px; background:var(--accent-soft); color:var(--accent); font-weight:700; font-size:12px; display:flex; align-items:center; justify-content:center; }
.tv-name{ flex:1; font-weight:600; font-size:13.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; } .tv-total{ font-weight:700; color:#10b981; font-size:13px; }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function(){
    var css = getComputedStyle(document.documentElement);
    var accent=(css.getPropertyValue('--accent')||'#8b5cf6').trim(), muted=(css.getPropertyValue('--muted')||'#6b7280').trim(), border=(css.getPropertyValue('--border')||'#e5e7eb').trim();
    Chart.defaults.color = muted; Chart.defaults.font.family = "'Segoe UI',system-ui,sans-serif";
    var labels=@json($labels), revenue=@json($revenueSeries), cumulative=@json($cumulativeSeries);
    var rc=document.getElementById('revChart').getContext('2d'); var g=rc.createLinearGradient(0,0,0,240); g.addColorStop(0,'rgba(139,92,246,.30)'); g.addColorStop(1,'rgba(139,92,246,0)');
    new Chart(rc,{type:'line',data:{labels:labels,datasets:[{data:revenue,borderColor:accent,backgroundColor:g,fill:true,tension:.4,borderWidth:2.5,pointRadius:2}]},options:{plugins:{legend:{display:false},tooltip:{callbacks:{label:function(c){return '£'+Intl.NumberFormat('en').format(c.parsed.y);}}}},scales:{y:{beginAtZero:true,grid:{color:border},ticks:{callback:function(v){return '£'+Intl.NumberFormat('en',{notation:'compact'}).format(v);}}},x:{grid:{display:false}}}}});
    new Chart(document.getElementById('growthChart'),{type:'line',data:{labels:labels,datasets:[{data:cumulative,borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.12)',fill:true,tension:.35,borderWidth:2.5,pointRadius:2}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:border},ticks:{precision:0}},x:{grid:{display:false}}}}});
    new Chart(document.getElementById('subChart'),{type:'doughnut',data:{labels:['Active','Expired','None'],datasets:[{data:[{{ $subActive }},{{ $subExpired }},{{ $subNone }}],backgroundColor:['#8b5cf6','#ef4444','#cbd5e1'],borderWidth:0,spacing:2}]},options:{cutout:'80%',plugins:{legend:{display:false}}}});
})();
</script>
@endsection
