@extends('admin.layout')

@section('title', 'Earnings')

@section('content')
@php $activeVendors = $vendors->where('is_active', 1)->count(); @endphp

<div class="row g-3 mb-4">
    <div class="col-sm-4"><div class="stat-card"><div class="stat-icon si-green"><i class="bi bi-cash-stack"></i></div><div><div class="label">Total Sales</div><div class="value">£{{ number_format($totalSales, 2) }}</div></div></div></div>
    <div class="col-sm-4"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-people-fill"></i></div><div><div class="label">Vendors</div><div class="value">{{ $vendors->count() }}</div></div></div></div>
    <div class="col-sm-4"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-receipt"></i></div><div><div class="label">Total Invoices</div><div class="value">{{ number_format($vendors->sum('invoices')) }}</div></div></div></div>
</div>

<div class="panel">
    <div class="panel-head">
        <div>
            <h5 class="mb-0 fw-semibold">Earnings</h5>
            <small class="text-muted">Per-vendor sales — open History for the full monthly breakdown</small>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Vendor</th><th class="text-end">Sales</th><th class="text-end">Invoices</th><th class="text-end">Customers</th><th class="text-end">History</th></tr></thead>
            <tbody>
            @forelse ($vendors as $v)
                @php $vname = trim($v->first_name.' '.$v->last_name) ?: $v->email; @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="av">{{ strtoupper(substr($v->first_name ?: $v->email,0,1)) }}</span>
                            <div><div class="fw-semibold">{{ $vname }}</div><div class="text-muted small">{{ $v->email }}</div></div>
                        </div>
                    </td>
                    <td class="text-end fw-semibold">£{{ number_format($v->sales, 2) }}</td>
                    <td class="text-end text-muted">{{ number_format($v->invoices) }}</td>
                    <td class="text-end text-muted">{{ number_format($v->customers) }}</td>
                    <td class="text-end">
                        @if ($v->history->count())
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#earn{{ $v->id }}"><i class="bi bi-graph-up"></i> {{ $v->history->count() }} mo</button>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No vendors yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Per-vendor earnings history popups --}}
@foreach ($vendors as $v)
    @if ($v->history->count())
        @php $vname = trim($v->first_name.' '.$v->last_name) ?: $v->email; $peak = $v->history->max('total'); @endphp
        <div class="modal fade" id="earn{{ $v->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content" style="background:var(--surface);color:var(--text);border:1px solid var(--border);border-radius:16px;">
                    <div class="modal-header" style="border-color:var(--border);">
                        <div class="d-flex align-items-center gap-2">
                            <span class="av">{{ strtoupper(substr($v->first_name ?: $v->email,0,1)) }}</span>
                            <div>
                                <h6 class="modal-title fw-semibold mb-0">{{ $vname }}</h6>
                                <small class="text-muted">Monthly earnings · £{{ number_format($v->sales, 2) }} total · {{ number_format($v->invoices) }} invoices</small>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @foreach ($v->history as $m)
                            <div class="earn-row">
                                <div class="earn-month">{{ $m->label }}</div>
                                <div class="earn-bar-wrap"><div class="earn-bar" style="width:{{ $peak > 0 ? max(round(($m->total / $peak) * 100), 3) : 0 }}%;"></div></div>
                                <div class="earn-val">£{{ number_format($m->total, 2) }}</div>
                                <div class="earn-inv text-muted small">{{ $m->invoices }} inv</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

<style>
.earn-row{ display:grid; grid-template-columns:110px 1fr 110px 70px; align-items:center; gap:12px; padding:9px 0; border-bottom:1px solid var(--border); }
.earn-row:last-child{ border-bottom:0; }
.earn-month{ font-weight:600; font-size:13px; }
.earn-bar-wrap{ background:var(--surface-2); border-radius:999px; height:10px; overflow:hidden; }
.earn-bar{ height:100%; background:var(--accent); border-radius:999px; }
.earn-val{ text-align:right; font-weight:700; font-size:13px; }
.earn-inv{ text-align:right; }
@media (max-width:520px){ .earn-row{ grid-template-columns:90px 1fr 90px; } .earn-inv{ display:none; } }
</style>
@endsection
