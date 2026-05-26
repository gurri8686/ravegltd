@extends('admin.layout')

@section('title', 'Subscription')

@section('content')
@php
    use Illuminate\Support\Carbon;
    $grouped = $subscriptions->groupBy('vendor_id');
    $withCurrent = $vendors->filter(function ($v) use ($grouped) { return optional(optional($grouped->get($v->id))->first()); });
    $activeCount = $vendors->filter(function ($v) use ($grouped) {
        $cur = optional($grouped->get($v->id))->first();
        return $cur && Carbon::parse($cur->expire)->isFuture();
    })->count();
    $expiredCount = $vendors->filter(function ($v) use ($grouped) {
        $cur = optional($grouped->get($v->id))->first();
        return $cur && !Carbon::parse($cur->expire)->isFuture();
    })->count();
@endphp

<div class="row g-3 mb-4">
    <div class="col-sm-4"><div class="stat-card"><div class="stat-icon si-green"><i class="bi bi-patch-check-fill"></i></div><div><div class="label">Active</div><div class="value">{{ $activeCount }}</div></div></div></div>
    <div class="col-sm-4"><div class="stat-card"><div class="stat-icon si-grey"><i class="bi bi-x-circle-fill"></i></div><div><div class="label">Expired / None</div><div class="value">{{ $vendors->count() - $activeCount }}</div></div></div></div>
    <div class="col-sm-4"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-clock-history"></i></div><div><div class="label">Total periods</div><div class="value">{{ $subscriptions->count() }}</div></div></div></div>
</div>

<div class="panel">
    <div class="panel-head">
        <div>
            <h5 class="mb-0 fw-semibold">Subscriptions</h5>
            <small class="text-muted">All vendors — current status, with history in a popup</small>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Vendor</th><th>Current period</th><th>Status</th><th>Periods</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($vendors as $v)
                @php
                    $subs = $grouped->get($v->id) ?? collect();
                    $cur  = $subs->first();
                    $active = $cur && Carbon::parse($cur->expire)->isFuture();
                    $vname = trim($v->first_name.' '.$v->last_name) ?: $v->email;
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="av">{{ strtoupper(substr($v->first_name ?: $v->email,0,1)) }}</span>
                            <div><div class="fw-semibold">{{ $vname }}</div><div class="text-muted small">{{ $v->email }}</div></div>
                        </div>
                    </td>
                    <td class="text-muted">{{ $cur ? Carbon::parse($cur->start)->format('d M Y').' → '.Carbon::parse($cur->expire)->format('d M Y') : '—' }}</td>
                    <td>
                        @if ($cur)
                            <span class="pill {{ $active ? 'pill-on' : 'pill-off' }}">{{ $active ? 'Active' : 'Expired' }}</span>
                            @if ($active)<div class="text-muted small mt-1">{{ Carbon::parse($cur->expire)->diffForHumans(null, true) }} left</div>@endif
                        @else
                            <span class="pill pill-off">No subscription</span>
                        @endif
                    </td>
                    <td>
                        @if ($subs->count())
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#hist{{ $v->id }}"><i class="bi bi-clock-history"></i> {{ $subs->count() }}</button>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <button type="button" class="btn btn-sm btn-accent sub-add" data-id="{{ $v->id }}" data-name="{{ $vname }}" data-bs-toggle="modal" data-bs-target="#addSubModal" style="padding:4px 12px;">
                                <i class="bi bi-plus-lg"></i> {{ $active ? 'Renew' : 'Subscribe' }}
                            </button>
                            @if ($cur)
                                <form method="POST" action="{{ route('admin.subscriptions.destroy', $cur->id) }}" class="m-0" onsubmit="return confirm('Remove the current subscription for {{ $vname }}?');">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger btn-icon" title="Remove current"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No vendors yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Per-vendor history popups --}}
@foreach ($vendors as $v)
    @php $subs = $grouped->get($v->id) ?? collect(); @endphp
    @if ($subs->count())
        <div class="modal fade" id="hist{{ $v->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="background:var(--surface);color:var(--text);border:1px solid var(--border);border-radius:16px;">
                    <div class="modal-header" style="border-color:var(--border);">
                        <div class="d-flex align-items-center gap-2">
                            <span class="av">{{ strtoupper(substr($v->first_name ?: $v->email,0,1)) }}</span>
                            <div>
                                <h6 class="modal-title fw-semibold mb-0">{{ trim($v->first_name.' '.$v->last_name) ?: $v->email }}</h6>
                                <small class="text-muted">Subscription history · {{ $subs->count() }} period(s)</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="sub-tl">
                            @foreach ($subs as $h)
                                @php
                                    $ha = Carbon::parse($h->expire)->isFuture();
                                    $dur = Carbon::parse($h->start)->diffForHumans(Carbon::parse($h->expire), ['parts' => 1, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]);
                                @endphp
                                <div class="sub-tl-item">
                                    <span class="sub-tl-dot {{ $ha ? 'on' : 'off' }}"></span>
                                    <div class="sub-tl-card">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <div class="fw-semibold">{{ Carbon::parse($h->start)->format('d M Y') }} <i class="bi bi-arrow-right text-muted"></i> {{ Carbon::parse($h->expire)->format('d M Y') }}</div>
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
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

{{-- Add subscription modal --}}
<div class="modal fade" id="addSubModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background:var(--surface);color:var(--text);border:1px solid var(--border);">
            <form method="POST" action="{{ route('admin.subscriptions.store') }}">
                @csrf
                <div class="modal-header" style="border-color:var(--border);">
                    <h6 class="modal-title fw-semibold"><i class="bi bi-patch-check me-1"></i> Subscription — <span id="addSubName" class="text-accent" style="color:var(--accent);"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="vendor_id" id="addSubVendor">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label fw-semibold">Start <span class="text-danger">*</span></label><input type="date" name="start" class="form-control" value="{{ now()->format('Y-m-d') }}" required></div>
                        <div class="col-6"><label class="form-label fw-semibold">Expire <span class="text-danger">*</span></label><input type="date" name="expire" class="form-control" value="{{ now()->addYear()->format('Y-m-d') }}" required></div>
                    </div>
                </div>
                <div class="modal-footer" style="border-color:var(--border);">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-accent btn-sm"><i class="bi bi-check-lg"></i> Add Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.sub-tl{ position:relative; padding-left:28px; }
.sub-tl::before{ content:''; position:absolute; left:9px; top:8px; bottom:8px; width:2px; background:var(--border); }
.sub-tl-item{ position:relative; margin-bottom:14px; }
.sub-tl-item:last-child{ margin-bottom:0; }
.sub-tl-dot{ position:absolute; left:-28px; top:16px; width:18px; height:18px; border-radius:50%; border:3px solid var(--surface); box-shadow:0 0 0 1px var(--border); }
.sub-tl-dot.on{ background:#10b981; box-shadow:0 0 0 1px #10b981, 0 0 0 4px rgba(16,185,129,.18); }
.sub-tl-dot.off{ background:#cbd5e1; }
.sub-tl-card{ background:var(--surface-2); border:1px solid var(--border); border-radius:12px; padding:12px 14px; }
</style>
@endsection

@section('scripts')
<script>
function saSetSubVendor(id, name){
    document.getElementById('addSubVendor').value = id || '';
    var n = document.getElementById('addSubName'); if (n) n.textContent = name || '';
}
document.querySelectorAll('.sub-add').forEach(function (b) {
    b.addEventListener('click', function () { saSetSubVendor(b.dataset.id, b.dataset.name); });
});
@if ($selectedVendor)
document.addEventListener('DOMContentLoaded', function(){
    var b = document.querySelector('.sub-add[data-id="{{ $selectedVendor }}"]');
    if (b) {
        saSetSubVendor(b.dataset.id, b.dataset.name);
        new bootstrap.Modal(document.getElementById('addSubModal')).show();
    }
});
@endif
</script>
@endsection
