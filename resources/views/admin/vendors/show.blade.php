@extends('admin.layout')

@section('title', 'Vendor')
@section('subtitle', 'Vendor overview — sales, domain & subscription')

@section('content')
@php
    use Illuminate\Support\Carbon;
    $name   = trim($vendor->first_name . ' ' . $vendor->last_name) ?: $vendor->email;
    $net    = $sales - $purchases;
    $margin = $sales > 0 ? round(($net / $sales) * 100, 1) : 0;
    $avgSale = $salesInvoices > 0 ? $sales / $salesInvoices : 0;
    $peak = 0;
    foreach ($months as $m) { $peak = max($peak, $m->sales, $m->purchases); }

    // subscription state
    $today = today();
    $curSub = $subs->first();
    $subActive = $curSub && Carbon::parse($curSub->expire)->gte($today);
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
        <div class="text-muted small">Subscription</div>
        <div>
            @if ($subActive)
                <span class="pill pill-on">Active</span>
            @elseif ($curSub)
                <span class="pill pill-warn">Expired</span>
            @else
                <span class="pill pill-off">None</span>
            @endif
        </div>
    </div>
</div>

{{-- ===== Tabs: Sales & Purchases | Domain | Subscription ===== --}}
<div class="v-tabs">
    <button class="v-tab active" data-tab="sales"><i class="bi bi-bar-chart-line"></i> Sales &amp; Purchases</button>
    <button class="v-tab" data-tab="domain"><i class="bi bi-globe2"></i> Domain</button>
    <button class="v-tab" data-tab="subscription"><i class="bi bi-patch-check"></i> Subscription
        @if ($subs->count())<span class="v-tab-badge">{{ $subs->count() }}</span>@endif
    </button>
</div>

{{-- ───────── Sales & Purchases ───────── --}}
<div class="v-pane active" id="pane-sales">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon si-green"><i class="bi bi-cash-stack"></i></div><div><div class="label">Total Sales</div><div class="value">£{{ number_format($sales, 2) }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-bag"></i></div><div><div class="label">Total Purchases</div><div class="value">£{{ number_format($purchases, 2) }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon {{ $net >= 0 ? 'si-green' : 'si-grey' }}"><i class="bi bi-graph-up-arrow"></i></div><div><div class="label">Net (Sales − Purch.)</div><div class="value" style="color:{{ $net >= 0 ? '#10b981' : '#ef4444' }};">£{{ number_format($net, 2) }}</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-percent"></i></div><div><div class="label">Margin</div><div class="value">{{ $margin }}%</div></div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="mini-card"><i class="bi bi-receipt"></i><div><div class="mini-v">{{ number_format($salesInvoices) }} / {{ number_format($purchaseInvoices) }}</div><div class="mini-l">Invoices (sales / purch.)</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="mini-card"><i class="bi bi-people"></i><div><div class="mini-v">{{ number_format($customers) }}</div><div class="mini-l">Customers served</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="mini-card"><i class="bi bi-truck"></i><div><div class="mini-v">{{ number_format($suppliers) }}</div><div class="mini-l">Suppliers used</div></div></div></div>
        <div class="col-6 col-lg-3"><div class="mini-card"><i class="bi bi-box-seam"></i><div><div class="mini-v">{{ number_format($itemsSold) }}</div><div class="mini-l">Items sold · avg £{{ number_format($avgSale, 0) }}/inv</div></div></div></div>
    </div>

    @php $asc = collect($months)->reverse()->values(); @endphp
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
                            @php $mn = $m->sales - $m->purchases; @endphp
                            <tr>
                                <td class="fw-semibold">{{ $m->label }}</td>
                                <td class="text-end" style="color:#10b981;">£{{ number_format($m->sales, 0) }}</td>
                                <td class="text-end" style="color:var(--accent);">£{{ number_format($m->purchases, 0) }}</td>
                                <td class="text-end fw-semibold" style="color:{{ $mn >= 0 ? '#10b981' : '#ef4444' }};">£{{ number_format($mn, 0) }}</td>
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
</div>

{{-- ───────── Domain ───────── --}}
<div class="v-pane" id="pane-domain">
    <div class="panel">
        <div class="panel-head"><h6 class="mb-0 fw-semibold"><i class="bi bi-globe2 me-1" style="color:var(--accent);"></i> Linked Domain</h6>
            <a href="{{ route('admin.vendors.edit', $vendor->id) }}" class="ms-auto small" style="color:var(--accent);">Change</a></div>
        <div class="p-3">
            @if ($site)
                @php
                    $ssl = $site->ssl_status ?? 'none';
                    $dns = (int) ($site->dns_verified ?? 0) === 1;
                @endphp
                <div class="dom-grid">
                    <div class="dom-cell"><span class="dom-k">Domain</span><span class="dom-v">{{ $site->domain ?: '—' }}</span></div>
                    <div class="dom-cell"><span class="dom-k">Subdomain</span><span class="dom-v">{{ $site->subdomain ?: '—' }}</span></div>
                    <div class="dom-cell"><span class="dom-k">Status</span><span class="dom-v"><span class="pill {{ (int) ($site->status ?? 0) === 1 ? 'pill-on' : 'pill-off' }}">{{ (int) ($site->status ?? 0) === 1 ? 'Active' : 'Inactive' }}</span></span></div>
                    <div class="dom-cell"><span class="dom-k">SSL</span><span class="dom-v">
                        @if ($ssl === 'active')<span class="pill pill-on">Valid</span>
                        @elseif ($ssl === 'expired')<span class="pill pill-warn">Expired</span>
                        @else<span class="pill pill-off">None</span>@endif
                    </span></div>
                    <div class="dom-cell"><span class="dom-k">DNS</span><span class="dom-v"><span class="pill {{ $dns ? 'pill-on' : 'pill-off' }}">{{ $dns ? 'Verified' : 'Unverified' }}</span></span></div>
                    <div class="dom-cell"><span class="dom-k">Last checked</span><span class="dom-v">{{ ($site->verified_at ?? null) ? Carbon::parse($site->verified_at)->diffForHumans() : '—' }}</span></div>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-accent" id="domVerifyBtn" data-id="{{ $site->id }}"><i class="bi bi-shield-check me-1"></i> Verify now</button>
                    <span class="text-muted small ms-2">Runs a live DNS + SSL check.</span>
                </div>
            @else
                <div class="text-center text-muted py-4">
                    <div style="font-size:38px;"><i class="bi bi-globe2"></i></div>
                    <p class="mb-2 mt-2">No domain linked to this vendor.</p>
                    <a href="{{ route('admin.vendors.edit', $vendor->id) }}" class="btn btn-sm btn-accent"><i class="bi bi-link-45deg"></i> Link / provision a domain</a>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ───────── Subscription ───────── --}}
<div class="v-pane" id="pane-subscription">
    <div class="row g-3">
        {{-- Current + add/renew --}}
        @php
            // Smart form defaults: when renewing an ACTIVE sub, start where the
            // current period ends (so renewals chain, no overlap). Plan defaults
            // to current plan. Expire = start + the plan's billing cycle.
            // default plan = current vendor's plan, else the first available plan
            $defPlanId  = ($curSub && $curSub->plan_tier_id) ? $curSub->plan_tier_id : optional($plans->first())->id;
            $defStart   = ($curSub && $subActive) ? Carbon::parse($curSub->expire)->format('Y-m-d') : now()->format('Y-m-d');
            $curPlanRow = ($curSub && $curSub->plan_tier_id) ? $plans->firstWhere('id', $curSub->plan_tier_id) : null;
            $defCycle   = ($defPlanId ? optional($plans->firstWhere('id', $defPlanId))->billing_cycle : null) ?: 'yearly';
            $defExpire  = $defCycle === 'monthly'
                ? Carbon::parse($defStart)->addMonth()->format('Y-m-d')
                : Carbon::parse($defStart)->addYear()->format('Y-m-d');

            // Progress bar + days-left for the current period
            $progPct = 0; $daysLeft = 0; $daysTotal = 0;
            if ($curSub) {
                $cs = Carbon::parse($curSub->start);
                $ce = Carbon::parse($curSub->expire);
                $daysTotal = max(1, $cs->diffInDays($ce));
                $daysLeft  = $subActive ? max(0, (int) now()->diffInDays($ce, false)) : 0;
                $progPct   = $subActive ? min(100, max(0, round(($daysTotal - $daysLeft) / $daysTotal * 100))) : 100;
            }
            $primaryLabel = $curSub ? ($subActive ? 'Renew' : 'Resubscribe') : 'Subscribe';
        @endphp

        <div class="col-12 col-lg-5">
            {{-- ── Plan summary card ── --}}
            <div class="sub-card mb-3">
                @if ($curSub)
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div>
                            <div class="sub-plan-name">
                                @if ($curPlanRow){{ $curPlanRow->name }}@else<span class="text-muted">No plan assigned</span>@endif
                            </div>
                            @if ($curPlanRow)
                                <div class="sub-plan-price">{{ $curPlanRow->currency === 'GBP' ? '£' : ($curPlanRow->currency.' ') }}{{ number_format($curPlanRow->price, 2) }} <span class="text-muted">/ {{ $curPlanRow->billing_cycle === 'yearly' ? 'year' : 'month' }}</span></div>
                            @endif
                        </div>
                        <span class="pill {{ $subActive ? 'pill-on' : 'pill-warn' }}">{{ $subActive ? 'Active' : 'Expired' }}</span>
                    </div>
                    <div class="text-muted small mb-2"><i class="bi bi-calendar3 me-1"></i>{{ Carbon::parse($curSub->start)->format('d M Y') }} → {{ Carbon::parse($curSub->expire)->format('d M Y') }}</div>
                    <div class="sub-prog" title="{{ $progPct }}% elapsed"><span style="width:{{ $progPct }}%"></span></div>
                    <div class="d-flex align-items-center mt-3 flex-wrap gap-2">
                        <span class="text-muted small">
                            @if ($subActive)
                                <i class="bi bi-hourglass-split"></i> {{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }} left
                            @else
                                <i class="bi bi-exclamation-circle"></i> Expired {{ Carbon::parse($curSub->expire)->diffForHumans() }}
                            @endif
                        </span>
                        <div class="ms-auto d-flex gap-1">
                            <a href="#renewForm" class="btn btn-sm btn-accent" onclick="document.getElementById('subPlanSel')?.focus();"><i class="bi bi-arrow-clockwise"></i> {{ $subActive ? 'Renew' : 'Resubscribe' }}</a>
                            <form method="POST" action="{{ route('admin.subscriptions.destroy', $curSub->id) }}" class="m-0" onsubmit="return confirm('Cancel and remove the current subscription period? This cannot be undone.');">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Cancel subscription"><i class="bi bi-x-circle"></i> Cancel</button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="text-center py-3">
                        <div style="font-size:38px;color:var(--muted);"><i class="bi bi-patch-check"></i></div>
                        <div class="fw-semibold mt-2">No active subscription</div>
                        <div class="text-muted small">Use the form below to start one.</div>
                    </div>
                @endif
            </div>

            {{-- ── Renew / Add form ── --}}
            <div class="panel" id="renewForm">
                <div class="panel-head"><h6 class="mb-0 fw-semibold">{{ $curSub ? ($subActive ? 'Renew or change plan' : 'Resubscribe') : 'Start subscription' }}</h6></div>
                <form method="POST" action="{{ route('admin.subscriptions.store') }}" class="p-3" id="subForm">
                    @csrf
                    <input type="hidden" name="vendor_id" value="{{ $vendor->id }}">
                    <div class="mb-2">
                        <div class="d-flex align-items-baseline">
                            <label class="form-label fw-semibold mb-1">Plan</label>
                            <span id="subPlanPrice" class="ms-auto small" style="color:var(--accent);font-weight:600;"></span>
                        </div>
                        <select name="plan_tier_id" class="form-select" id="subPlanSel" required>
                            @foreach ($plans as $p)
                                <option value="{{ $p->id }}" data-cycle="{{ $p->billing_cycle }}" data-price="{{ $p->price }}" data-currency="{{ $p->currency }}" {{ $defPlanId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label fw-semibold">Start <span class="text-danger">*</span></label><input type="date" name="start" id="subStart" class="form-control" value="{{ $defStart }}" required></div>
                        <div class="col-6"><label class="form-label fw-semibold">Expire <span class="text-danger">*</span></label><input type="date" name="expire" id="subExpire" class="form-control" value="{{ $defExpire }}" required></div>
                    </div>
                    <div class="d-flex align-items-center gap-1 mt-2 flex-wrap">
                        <span class="text-muted small me-1">Quick:</span>
                        <button type="button" class="btn-quick" data-add="1m">+1 mo</button>
                        <button type="button" class="btn-quick" data-add="3m">+3 mo</button>
                        <button type="button" class="btn-quick" data-add="6m">+6 mo</button>
                        <button type="button" class="btn-quick" data-add="1y">+1 yr</button>
                    </div>
                    <button type="submit" class="btn btn-accent btn-sm mt-3"><i class="bi bi-check-lg me-1"></i> {{ $primaryLabel }}</button>
                </form>
            </div>
        </div>

        {{-- History --}}
        <div class="col-12 col-lg-7">
            <div class="panel h-100">
                <div class="panel-head"><h6 class="mb-0 fw-semibold">History</h6><span class="ms-auto soft-pill">{{ $subs->count() }} period(s)</span></div>
                <div class="p-3">
                    @if ($subs->count())
                        <div class="sub-tl">
                            @foreach ($subs as $h)
                                @php
                                    $ha = Carbon::parse($h->expire)->gte($today);
                                    $isCurrent = $curSub && $h->id === $curSub->id;
                                    $dur = Carbon::parse($h->start)->diffForHumans(Carbon::parse($h->expire), ['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
                                @endphp
                                <div class="sub-tl-item">
                                    <span class="sub-tl-dot {{ $ha ? 'on' : 'off' }}"></span>
                                    <div class="sub-tl-card {{ $isCurrent ? 'is-current' : '' }}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-semibold d-flex align-items-center gap-2 flex-wrap">{{ Carbon::parse($h->start)->format('d M Y') }} <i class="bi bi-arrow-right text-muted"></i> {{ Carbon::parse($h->expire)->format('d M Y') }}
                                                    @if ($h->plan_tier_id && isset($planNames[$h->plan_tier_id]))<span class="plan-badge">{{ $planNames[$h->plan_tier_id] }}</span>@endif
                                                    @if ($isCurrent)<span class="pill-accent-tag">Current</span>@endif
                                                </div>
                                                <div class="text-muted small mt-1"><i class="bi bi-hourglass-split"></i> {{ $dur }}{{ $ha ? ' · '.Carbon::parse($h->expire)->diffForHumans(null, true).' left' : '' }}</div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="pill {{ $ha ? 'pill-on' : 'pill-off' }}">{{ $ha ? 'Active' : 'Expired' }}</span>
                                                <form method="POST" action="{{ route('admin.subscriptions.destroy', $h->id) }}" class="m-0" onsubmit="return confirm('Remove this period?');">@csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger btn-icon" title="Remove"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted text-center py-4">No subscription periods yet.</div>
                    @endif
                </div>
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

/* tabs */
.v-tabs{ display:flex; gap:6px; margin-bottom:16px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
.v-tab{ display:inline-flex; align-items:center; gap:8px; border:0; background:transparent; color:var(--muted);
        font-size:14px; font-weight:600; padding:10px 16px; border-bottom:2px solid transparent; margin-bottom:-1px; cursor:pointer; }
.v-tab:hover{ color:var(--text); }
.v-tab.active{ color:var(--accent); border-bottom-color:var(--accent); }
.v-tab-badge{ font-size:11px; font-weight:700; background:var(--accent-soft); color:var(--accent); border-radius:999px; padding:1px 8px; }
.v-pane{ display:none; }
.v-pane.active{ display:block; }

.plan-badge{ display:inline-flex; align-items:center; font-size:12px; font-weight:600; color:var(--accent); background:var(--accent-soft); border:1px solid var(--accent); border-radius:999px; padding:2px 11px; }
.pill-warn{ background:rgba(245,158,11,.16); color:#f59e0b; }
.soft-pill{ font-size:12px; font-weight:500; color:var(--muted); background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:4px 10px; }

.dom-grid{ display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:14px; }
.dom-cell{ display:flex; flex-direction:column; gap:4px; }
.dom-k{ font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
.dom-v{ font-size:14px; font-weight:600; color:var(--text); }

.sub-tl{ position:relative; padding-left:28px; }
.sub-tl::before{ content:''; position:absolute; left:9px; top:8px; bottom:8px; width:2px; background:var(--border); }
.sub-tl-item{ position:relative; margin-bottom:14px; }
.sub-tl-item:last-child{ margin-bottom:0; }
.sub-tl-dot{ position:absolute; left:-28px; top:16px; width:18px; height:18px; border-radius:50%; border:3px solid var(--surface); box-shadow:0 0 0 1px var(--border); }
.sub-tl-dot.on{ background:#10b981; box-shadow:0 0 0 1px #10b981, 0 0 0 4px rgba(16,185,129,.18); }
.sub-tl-dot.off{ background:#cbd5e1; }
.sub-tl-card{ background:var(--surface-2); border:1px solid var(--border); border-radius:12px; padding:12px 14px; }
.sub-tl-card.is-current{ border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-soft); }

/* plan summary card + progress + quick buttons */
.sub-card{ background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:18px 20px; box-shadow:var(--shadow); }
.sub-plan-name{ font-size:22px; font-weight:800; color:var(--text); line-height:1.1; }
.sub-plan-price{ font-size:14px; font-weight:700; color:var(--accent); margin-top:4px; }
.sub-plan-price .text-muted{ font-weight:500; font-size:12px; }
.sub-prog{ height:7px; border-radius:999px; background:var(--surface-2); overflow:hidden; }
.sub-prog span{ display:block; height:100%; border-radius:999px; background:linear-gradient(90deg, var(--accent), #10b981); transition:width .4s; }
.btn-quick{ font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:999px; border:1px solid var(--border); background:var(--surface); color:var(--muted); cursor:pointer; transition:.15s; }
.btn-quick:hover{ background:var(--accent-soft); color:var(--accent); border-color:var(--accent); }
.pill-accent-tag{ background:var(--accent); color:#fff; font-size:10.5px; padding:2px 8px; border-radius:999px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// ── tab switching (with #hash support so the vendor list can deep-link) ──
(function () {
    var tabs = document.querySelectorAll('.v-tab');
    function show(name) {
        tabs.forEach(function (t) { t.classList.toggle('active', t.dataset.tab === name); });
        document.querySelectorAll('.v-pane').forEach(function (p) { p.classList.toggle('active', p.id === 'pane-' + name); });
    }
    tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.tab); history.replaceState(null, '', '#' + t.dataset.tab); }); });
    var h = (location.hash || '').replace('#', '');
    if (h && document.getElementById('pane-' + h)) show(h);
})();

// ── domain verify (AJAX → reload to show fresh status) ──
(function () {
    var btn = document.getElementById('domVerifyBtn'); if (!btn) return;
    var csrf = document.querySelector('meta[name=csrf-token]').content;
    btn.addEventListener('click', function () {
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Checking…';
        fetch("{{ url('admin/domains') }}/" + btn.dataset.id + "/verify", {
            method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); }).then(function () { location.reload(); })
          .catch(function () { btn.disabled = false; btn.innerHTML = '<i class="bi bi-shield-check me-1"></i> Verify now'; if (window.saToast) saToast('Verification failed — retry.', 'error'); });
    });
})();

// ── subscription form: live plan price + auto-compute expire + quick buttons ──
(function () {
    var sel = document.getElementById('subPlanSel');
    var start = document.getElementById('subStart');
    var expire = document.getElementById('subExpire');
    var price = document.getElementById('subPlanPrice');
    if (!sel || !start || !expire) return;

    // parse YYYY-MM-DD as a local date (avoid UTC-shift bugs in negative TZs)
    function parseYmd(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function ymd(d) { var m = String(d.getMonth() + 1).padStart(2, '0'); var dd = String(d.getDate()).padStart(2, '0'); return d.getFullYear() + '-' + m + '-' + dd; }
    function addMonths(d, n) { var x = new Date(d); x.setMonth(x.getMonth() + n); return x; }

    function refreshPrice() {
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.dataset.price) { price.textContent = ''; return; }
        var p = parseFloat(opt.dataset.price);
        var cur = opt.dataset.currency || 'GBP';
        var cyc = opt.dataset.cycle || 'yearly';
        price.textContent = (cur === 'GBP' ? '£' : cur + ' ') + p.toFixed(2) + ' / ' + (cyc === 'yearly' ? 'year' : 'month');
    }
    function recomputeExpire() {
        if (!start.value) return;
        var opt = sel.options[sel.selectedIndex];
        var cyc = (opt && opt.dataset.cycle) || 'yearly';
        var s = parseYmd(start.value);
        expire.value = ymd(cyc === 'yearly' ? addMonths(s, 12) : addMonths(s, 1));
    }
    sel.addEventListener('change', function () { refreshPrice(); recomputeExpire(); });
    start.addEventListener('change', recomputeExpire);
    refreshPrice(); // initial label (defaults already set in PHP)

    document.querySelectorAll('.btn-quick').forEach(function (b) {
        b.addEventListener('click', function () {
            if (!start.value) start.value = ymd(new Date());
            var s = parseYmd(start.value);
            var spec = b.dataset.add; var n = parseInt(spec, 10);
            expire.value = ymd(addMonths(s, spec.slice(-1) === 'y' ? n * 12 : n));
        });
    });
})();

// ── sales chart ──
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
