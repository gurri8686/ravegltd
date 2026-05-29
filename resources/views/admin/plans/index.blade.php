@extends('admin.layout')

@section('title', 'Plans')
@section('subtitle', 'Subscription plans, limits & features')

@section('content')
@php
    $limitVal = function ($v) {
        if ($v === null) return '∞';
        return number_format($v);
    };
    $storageVal = function ($mb) {
        if ($mb === null) return '∞';
        return $mb >= 1024 ? round($mb / 1024, 1) . ' GB' : $mb . ' MB';
    };
@endphp

<div class="d-flex align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Subscription Plans</h5>
        <div class="text-muted small">Configure tiers, pricing, limits and feature access</div>
    </div>
    <a href="{{ route('admin.plans.create') }}" class="btn-newvendor ms-auto"><i class="bi bi-plus-lg"></i> Add Plan</a>
</div>

{{-- ===== Pricing cards ===== --}}
<div class="row g-3 mb-4">
    @forelse ($plans as $plan)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="plan-card {{ $plan->is_active ? '' : 'plan-off' }}">
                <div class="plan-head">
                    <div>
                        <div class="plan-name">{{ $plan->name }}</div>
                        <div class="plan-cycle">billed {{ $plan->billing_cycle }}</div>
                    </div>
                    <span class="pill {{ $plan->is_active ? 'pill-on' : 'pill-off' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="plan-price">
                    <span class="cur">{{ $plan->currency === 'GBP' ? '£' : $plan->currency . ' ' }}</span>{{ rtrim(rtrim(number_format($plan->price, 2), '0'), '.') }}
                    <span class="per">/{{ $plan->billing_cycle === 'yearly' ? 'yr' : 'mo' }}</span>
                </div>
                <div class="plan-limits">
                    <div><i class="bi bi-people"></i> {{ $limitVal($plan->user_limit) }} users</div>
                    <div><i class="bi bi-box-seam"></i> {{ $limitVal($plan->product_limit) }} products</div>
                    <div><i class="bi bi-person-lines-fill"></i> {{ $limitVal($plan->customer_limit) }} customers</div>
                    <div><i class="bi bi-hdd"></i> {{ $storageVal($plan->storage_limit_mb) }} storage</div>
                </div>
                <ul class="plan-feats">
                    @foreach ($features as $col => $label)
                        <li class="{{ $plan->$col ? 'on' : 'off' }}"><i class="bi bi-{{ $plan->$col ? 'check-circle-fill' : 'dash-circle' }}"></i> {{ $label }}</li>
                    @endforeach
                </ul>
                <div class="plan-actions">
                    <a href="{{ route('admin.plans.edit', $plan->id) }}" class="btn btn-sm btn-light w-100"><i class="bi bi-pencil"></i> Edit</a>
                    <div class="form-check form-switch m-0 ms-2 d-inline-flex align-items-center">
                        <input class="form-check-input plan-toggle" type="checkbox" role="switch" data-id="{{ $plan->id }}" {{ $plan->is_active ? 'checked' : '' }} title="Active / inactive">
                    </div>
                    <form method="POST" action="{{ route('admin.plans.destroy', $plan->id) }}" class="m-0 ms-1" onsubmit="return confirm('Delete plan &quot;{{ $plan->name }}&quot;?');">@csrf @method('DELETE')
                        <button class="act-btn act-del" title="Delete"><i class="bi bi-trash3"></i></button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="panel p-5 text-center text-muted">No plans yet. <a href="{{ route('admin.plans.create') }}" style="color:var(--accent);">Add your first plan</a>.</div></div>
    @endforelse
</div>

{{-- ===== Comparison table ===== --}}
@if ($plans->count())
<div class="panel">
    <div class="panel-head"><h6 class="mb-0 fw-semibold">Plan Comparison</h6></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0 cmp-table">
            <thead>
                <tr>
                    <th>Feature</th>
                    @foreach ($plans as $plan)<th class="text-center">{{ $plan->name }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                <tr><td>Price</td>@foreach ($plans as $plan)<td class="text-center fw-semibold">{{ $plan->currency === 'GBP' ? '£' : '' }}{{ rtrim(rtrim(number_format($plan->price,2),'0'),'.') }}/{{ $plan->billing_cycle === 'yearly' ? 'yr' : 'mo' }}</td>@endforeach</tr>
                @foreach ($limits as $col => $label)
                    <tr><td>{{ $label }}</td>@foreach ($plans as $plan)<td class="text-center">{{ $col === 'storage_limit_mb' ? $storageVal($plan->$col) : $limitVal($plan->$col) }}</td>@endforeach</tr>
                @endforeach
                @foreach ($features as $col => $label)
                    <tr><td>{{ $label }}</td>@foreach ($plans as $plan)<td class="text-center">@if($plan->$col)<i class="bi bi-check-circle-fill" style="color:#10b981;"></i>@else<i class="bi bi-dash" style="color:var(--muted);"></i>@endif</td>@endforeach</tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<style>
.plan-card{ background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:22px; box-shadow:var(--shadow); height:100%; display:flex; flex-direction:column; }
.plan-card.plan-off{ opacity:.62; }
.plan-head{ display:flex; align-items:flex-start; justify-content:space-between; }
.plan-name{ font-size:18px; font-weight:800; color:var(--text); }
.plan-cycle{ font-size:11.5px; color:var(--muted); text-transform:uppercase; letter-spacing:.05em; }
.plan-price{ font-size:34px; font-weight:800; color:var(--text); margin:14px 0 4px; }
.plan-price .cur{ font-size:20px; vertical-align:super; }
.plan-price .per{ font-size:14px; font-weight:500; color:var(--muted); }
.plan-limits{ display:grid; grid-template-columns:1fr 1fr; gap:7px 12px; margin:12px 0; font-size:12.5px; color:var(--text); }
.plan-limits i{ color:var(--accent); margin-right:5px; }
.plan-feats{ list-style:none; padding:0; margin:6px 0 16px; border-top:1px solid var(--border); padding-top:12px; }
.plan-feats li{ font-size:13px; padding:4px 0; display:flex; align-items:center; gap:8px; }
.plan-feats li.on{ color:var(--text); } .plan-feats li.on i{ color:#10b981; }
.plan-feats li.off{ color:var(--muted); } .plan-feats li.off i{ color:var(--muted); }
.plan-actions{ display:flex; align-items:center; gap:6px; margin-top:auto; }
.act-btn{ width:34px; height:34px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); background:var(--surface); color:var(--muted); font-size:15px; transition:.15s; }
.act-del{ color:#ef4444; border-color:rgba(239,68,68,.35); } .act-del:hover{ background:#ef4444; color:#fff; }
.cmp-table td:first-child, .cmp-table th:first-child{ text-align:left; font-weight:600; }
.cmp-table tbody td{ border-color:var(--border); }
</style>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.plan-toggle').forEach(function (cb) {
    cb.addEventListener('change', function () {
        var csrf = document.querySelector('meta[name=csrf-token]').content;
        cb.disabled = true;
        fetch("{{ url('admin/plans') }}/" + cb.dataset.id + "/toggle", {
            method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); }).then(function (d) {
            cb.checked = d.is_active === 1; cb.disabled = false;
            cb.closest('.plan-card').classList.toggle('plan-off', d.is_active !== 1);
            saToast(d.message || 'Updated.', 'success');
        }).catch(function () { cb.checked = !cb.checked; cb.disabled = false; saToast('Failed — retry.', 'error'); });
    });
});
</script>
@endsection
