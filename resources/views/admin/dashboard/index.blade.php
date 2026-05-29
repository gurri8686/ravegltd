@extends('admin.layout')

@section('title', 'Dashboard')
@section('subtitle', 'Overview of your platform')

@section('content')
@php
    $money = fn ($n) => '£' . number_format($n, $n >= 1000 || $n <= -1000 ? 0 : 2);
    $subTotal = max(1, $subActive + $subExpired + $subNone);
    $pct = fn ($n) => round($n / $subTotal * 100, 1);
@endphp

{{-- ===== Headline platform KPIs ===== --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-top">
                <div><div class="kpi-label">Total Vendors</div><div class="kpi-value">{{ number_format($totalVendors) }}</div></div>
                <div class="kpi-ic ic-violet"><i class="bi bi-people-fill"></i></div>
            </div>
            @if (!is_null($vendorTrend))
                <div class="kpi-trend {{ $vendorTrend >= 0 ? 'up' : 'down' }}"><i class="bi bi-arrow-{{ $vendorTrend >= 0 ? 'up' : 'down' }}"></i> {{ abs($vendorTrend) }}% <span>vs last month</span></div>
            @else
                <div class="kpi-trend flat"><i class="bi bi-plus-lg"></i> {{ $vendorsThisMonth }} <span>this month</span></div>
            @endif
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-top">
                <div><div class="kpi-label">Active Vendors</div><div class="kpi-value">{{ number_format($activeVendors) }}</div></div>
                <div class="kpi-ic ic-green"><i class="bi bi-check-lg"></i></div>
            </div>
            <div class="kpi-trend flat"><i class="bi bi-pie-chart"></i> {{ $activePct }}% active <span>· {{ $pendingApproval }} pending</span></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <a href="{{ route('admin.domains.index') }}" class="kpi-card d-block kpi-link">
            <div class="kpi-top">
                <div><div class="kpi-label">Domains</div><div class="kpi-value">{{ number_format($totalDomains) }}</div></div>
                <div class="kpi-ic ic-amber"><i class="bi bi-globe2"></i></div>
            </div>
            <div class="kpi-trend flat"><i class="bi bi-check-circle"></i> {{ number_format($activeDomains) }} <span>active</span></div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi-card">
            <div class="kpi-top">
                <div><div class="kpi-label">Total Revenue</div><div class="kpi-value">{{ $money($revenue) }}</div></div>
                <div class="kpi-ic ic-blue"><i class="bi bi-cash-stack"></i></div>
            </div>
            @if (!is_null($revenueTrend))
                <div class="kpi-trend {{ $revenueTrend >= 0 ? 'up' : 'down' }}"><i class="bi bi-arrow-{{ $revenueTrend >= 0 ? 'up' : 'down' }}"></i> {{ abs($revenueTrend) }}% <span>vs last month</span></div>
            @else
                <div class="kpi-trend flat"><i class="bi bi-plus-lg"></i> {{ $money($revenueMonth) }} <span>this month</span></div>
            @endif
        </div>
    </div>
</div>

{{-- ===== Secondary platform metrics ===== --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="ops-card"><div class="ops-head"><span class="ops-ic ic-blue"><i class="bi bi-person-plus-fill"></i></span><span class="ops-label">New Signups</span></div>
            <div class="ops-value">{{ number_format($signupsToday) }}</div><span class="ops-sub">Today · {{ number_format($newSignups) }} in last 30 days</span></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="ops-card"><div class="ops-head"><span class="ops-ic ic-amber"><i class="bi bi-patch-question-fill"></i></span><span class="ops-label">Pending Approval</span></div>
            <div class="ops-value">{{ number_format($pendingApproval) }}</div><span class="ops-sub">inactive vendors</span></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="ops-card"><div class="ops-head"><span class="ops-ic ic-green"><i class="bi bi-arrow-repeat"></i></span><span class="ops-label">Recurring Revenue</span></div>
            <div class="ops-value">{{ $money($mrr) }}<small style="font-size:12px;color:var(--muted);font-weight:600;">/mo</small></div><span class="ops-sub">ARR {{ $money($arr) }} · {{ $mrrActive }} active {{ \Illuminate\Support\Str::plural('plan', $mrrActive) }}</span></div>
    </div>
    <div class="col-6 col-lg-3">
        <a href="{{ route('admin.domains.index') }}" class="ops-card d-block kpi-link"><div class="ops-head"><span class="ops-ic ic-violet"><i class="bi bi-shield-check"></i></span><span class="ops-label">Verified Domains</span></div>
            <div class="ops-value">{{ number_format($verifiedDomains) }} <small class="text-muted" style="font-size:13px;">/ {{ number_format($totalDomains) }}</small></div><span class="ops-sub">DNS verified</span></a>
    </div>
</div>

{{-- ===== Revenue overview + Subscription distribution ===== --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-lg-8">
        <div class="panel h-100">
            <div class="panel-head"><h6 class="mb-0 fw-semibold">Revenue Overview</h6>
                <span class="ms-auto soft-pill"><i class="bi bi-calendar3 me-1"></i> Last 12 months</span></div>
            <div class="p-3"><canvas id="revChart" height="115"></canvas></div>
        </div>
    </div>
    <div class="col-12 col-lg-4">
        <div class="panel h-100">
            <div class="panel-head"><h6 class="mb-0 fw-semibold">Subscriptions &amp; Plans</h6></div>
            <div class="p-3 pb-2 d-flex align-items-center gap-3 flex-wrap flex-md-nowrap">
                <div class="donut-wrap">
                    <canvas id="subChart"></canvas>
                    <div class="donut-center"><div class="donut-total">{{ $totalVendors }}</div><div class="donut-cap">Total</div></div>
                </div>
                <div class="flex-grow-1">
                    <div class="leg-row"><span class="leg-l"><span class="lg-dot" style="background:#8b5cf6;"></span> Active</span><span class="leg-c">{{ $subActive }}</span><span class="leg-p">{{ $pct($subActive) }}%</span></div>
                    <div class="leg-row"><span class="leg-l"><span class="lg-dot" style="background:#ef4444;"></span> Expired</span><span class="leg-c">{{ $subExpired }}</span><span class="leg-p">{{ $pct($subExpired) }}%</span></div>
                    <div class="leg-row"><span class="leg-l"><span class="lg-dot" style="background:#cbd5e1;"></span> None</span><span class="leg-c">{{ $subNone }}</span><span class="leg-p">{{ $pct($subNone) }}%</span></div>
                </div>
            </div>
            @if ($planMix->isNotEmpty())
                <div class="px-3 pb-3">
                    <div class="pm-divider"></div>
                    <div class="pm-head">Active plans <span class="ms-auto">{{ $money($mrr) }}/mo</span></div>
                    @foreach ($planMix as $name => $row)
                        @php $pmPct = $mrrActive > 0 ? round($row['count'] / $mrrActive * 100) : 0; @endphp
                        <div class="pm-row">
                            <span class="pm-name">{{ $name }}</span>
                            <div class="pm-bar"><span style="width:{{ $pmPct }}%"></span></div>
                            <span class="pm-val">{{ $row['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ===== Vendor growth + Newest vendors ===== --}}
<div class="row g-3 mb-3">
    <div class="col-12 col-lg-7">
        <div class="panel h-100">
            <div class="panel-head"><h6 class="mb-0 fw-semibold">Vendor Growth</h6><span class="ms-auto soft-pill">signups / month</span></div>
            <div class="p-3"><canvas id="growthChart" height="135"></canvas></div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="panel h-100">
            <div class="panel-head"><h6 class="mb-0 fw-semibold">Newest Vendors</h6>
                <a href="{{ route('admin.vendors.index') }}" class="ms-auto small" style="color:var(--accent);">View all</a></div>
            <div class="p-2">
                @forelse ($newestVendors as $v)
                    <div class="nv-row">
                        <span class="av">{{ $v->initial }}</span>
                        <span class="nv-meta"><span class="nv-name">{{ $v->name }}</span><span class="nv-sub">{{ $v->email }}</span></span>
                        <span class="text-end">
                            <span class="pill {{ $v->active ? 'pill-on' : 'pill-off' }}">{{ $v->active ? 'Active' : 'Inactive' }}</span>
                            <div class="nv-when">{{ $v->when }}</div>
                        </span>
                    </div>
                @empty
                    <div class="text-muted text-center py-4">No vendors yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ===== Subscriptions & renewals ===== --}}
@php
    $subPill = ['active' => ['pill-on','Active'], 'expiring' => ['pill-warn','Expiring soon'], 'expired' => ['pill-off','Expired'], 'none' => ['pill-off','—']];
@endphp
<div class="row g-3">
    <div class="col-12">
        <div class="panel">
            <div class="panel-head"><h6 class="mb-0 fw-semibold">Subscriptions &amp; Renewals</h6>
                <a href="{{ route('admin.subscriptions.index') }}" class="ms-auto small" style="color:var(--accent);">Manage</a></div>
            <div class="p-2">
                @forelse ($subscriptionFeed as $s)
                    @php [$cls, $lbl] = $subPill[$s->state] ?? ['pill-off','—']; @endphp
                    <div class="act-row">
                        <span class="av">{{ $s->initial }}</span>
                        <span class="act-meta"><span class="act-text">{{ $s->name }}</span><span class="act-sub"><i class="bi bi-calendar3 me-1"></i>{{ $s->period }}</span></span>
                        <span class="text-end">
                            <span class="pill {{ $cls }}">{{ $lbl }}</span>
                            <div class="act-when mt-1">{{ $s->when }}</div>
                        </span>
                    </div>
                @empty
                    <div class="text-muted text-center py-4">No subscriptions yet. <a href="{{ route('admin.subscriptions.index') }}" style="color:var(--accent);">Add one</a>.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<style>
.kpi-card{ background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:18px 20px; box-shadow:var(--shadow); height:100%; }
.kpi-top{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
.kpi-label{ font-size:13px; color:var(--muted); font-weight:500; }
.kpi-value{ font-size:25px; font-weight:800; color:var(--text); line-height:1.15; margin-top:4px; }
.kpi-ic{ width:46px; height:46px; flex:0 0 46px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:20px; }
.ic-violet{ background:rgba(139,92,246,.15); color:#8b5cf6; } .ic-green{ background:rgba(16,185,129,.16); color:#10b981; }
.ic-amber{ background:rgba(245,158,11,.16); color:#f59e0b; } .ic-blue{ background:rgba(59,130,246,.16); color:#3b82f6; }
.kpi-trend{ display:flex; align-items:center; gap:5px; margin-top:14px; font-size:12.5px; font-weight:700; }
.kpi-trend span{ font-weight:500; color:var(--muted); }
.kpi-trend.up{ color:#10b981; } .kpi-trend.down{ color:#ef4444; } .kpi-trend.flat{ color:var(--muted); }
.kpi-link{ text-decoration:none; color:inherit; }
.kpi-link:hover{ border-color:var(--accent); box-shadow:0 4px 14px var(--accent-soft); color:inherit; }
.soft-pill{ font-size:12px; font-weight:500; color:var(--muted); background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:4px 10px; }

.donut-wrap{ position:relative; width:130px; height:130px; flex:0 0 130px; }
.donut-center{ position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none; }
.donut-total{ font-size:24px; font-weight:800; color:var(--text); line-height:1; }
.donut-cap{ font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; }
.leg-row{ display:grid; grid-template-columns:1fr auto auto; align-items:center; gap:12px; padding:7px 0; border-bottom:1px solid var(--border); font-size:13px; }
.leg-row:last-child{ border-bottom:0; }
.leg-c{ font-weight:700; color:var(--text); } .leg-p{ color:var(--muted); font-size:12px; min-width:44px; text-align:right; }
.lg-dot{ display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px; }
.pm-divider{ height:1px; background:var(--border); margin:2px 0 12px; }
.pm-head{ display:flex; align-items:center; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); margin-bottom:11px; }
.pm-head span{ color:var(--accent); font-size:12px; letter-spacing:0; }
.pm-row{ display:flex; align-items:center; gap:10px; margin-bottom:9px; }
.pm-row:last-child{ margin-bottom:0; }
.pm-name{ flex:0 0 92px; font-size:12.5px; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pm-bar{ flex:1; height:7px; border-radius:999px; background:var(--surface-2); overflow:hidden; }
.pm-bar span{ display:block; height:100%; border-radius:999px; background:var(--accent); }
.pm-val{ flex:0 0 24px; text-align:right; font-size:12.5px; font-weight:700; color:var(--text); }

.ops-card{ background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:15px 16px; box-shadow:var(--shadow); height:100%; transition:.15s; }
a.ops-card:hover{ border-color:var(--accent); box-shadow:0 4px 14px var(--accent-soft); }
.ops-head{ display:flex; align-items:center; gap:9px; margin-bottom:9px; }
.ops-ic{ width:36px; height:36px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:16px; }
.ops-label{ font-size:12.5px; color:var(--muted); font-weight:600; }
.ops-value{ font-size:20px; font-weight:800; color:var(--text); }
.ops-sub{ font-size:11px; color:var(--muted); }
.h-100{ height:100%; }

.nv-row{ display:flex; align-items:center; gap:11px; padding:9px 12px; border-bottom:1px solid var(--border); }
.nv-row:last-child{ border-bottom:0; }
.nv-meta{ display:flex; flex-direction:column; min-width:0; flex:1; }
.nv-name{ font-weight:600; font-size:13.5px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.nv-sub{ font-size:11px; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.nv-when{ font-size:10.5px; color:var(--muted); margin-top:3px; }

.act-row{ display:flex; align-items:flex-start; gap:11px; padding:9px 12px; border-bottom:1px solid var(--border); }
.act-row:last-child{ border-bottom:0; }
.act-dot{ width:9px; height:9px; border-radius:50%; margin-top:5px; flex:0 0 9px; background:var(--accent); }
.act-created,.act-restored{ background:#10b981; } .act-deleted{ background:#ef4444; } .act-updated{ background:#3b82f6; }
.act-meta{ display:flex; flex-direction:column; min-width:0; flex:1; }
.act-text{ font-size:13.5px; font-weight:500; line-height:1.3; }
.act-sub{ font-size:11px; color:var(--muted); }
.act-when{ font-size:11px; color:var(--muted); white-space:nowrap; }
.pill-warn{ background:rgba(245,158,11,.16); color:#f59e0b; }

@media (max-width:575px){ .donut-wrap{ margin:0 auto; } }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function(){
    var css = getComputedStyle(document.documentElement);
    var accent = (css.getPropertyValue('--accent') || '#8b5cf6').trim();
    var muted  = (css.getPropertyValue('--muted') || '#6b7280').trim();
    var border = (css.getPropertyValue('--border') || '#e5e7eb').trim();
    Chart.defaults.color = muted;
    Chart.defaults.font.family = "'Segoe UI',system-ui,sans-serif";

    var labels  = @json($chartLabels);
    var revenue = @json($revenueSeries);
    var growth  = @json($growthSeries);

    var rc = document.getElementById('revChart').getContext('2d');
    var grad = rc.createLinearGradient(0, 0, 0, 240);
    grad.addColorStop(0, 'rgba(139,92,246,.30)'); grad.addColorStop(1, 'rgba(139,92,246,0)');
    new Chart(rc, {
        type: 'line',
        data: { labels: labels, datasets: [{ data: revenue, borderColor: accent, backgroundColor: grad,
            fill: true, tension: .4, borderWidth: 2.5, pointRadius: 2, pointHoverRadius: 5 }] },
        options: { plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(c){ return '£' + Intl.NumberFormat('en').format(c.parsed.y); } } } },
            scales: { y: { beginAtZero: true, grid: { color: border }, ticks: { callback: function(v){ return '£' + Intl.NumberFormat('en',{notation:'compact'}).format(v); } } }, x: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('growthChart'), {
        type: 'bar',
        data: { labels: labels, datasets: [{ data: growth, backgroundColor: accent, borderRadius: 6, maxBarThickness: 26 }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: border }, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('subChart'), {
        type: 'doughnut',
        data: { labels: ['Active','Expired','None'], datasets: [{ data: [{{ $subActive }},{{ $subExpired }},{{ $subNone }}], backgroundColor: ['#8b5cf6','#ef4444','#cbd5e1'], borderWidth: 0, spacing: 2 }] },
        options: { cutout: '80%', plugins: { legend: { display: false } } }
    });
})();
</script>
@endsection
