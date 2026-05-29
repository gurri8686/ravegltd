@extends('admin.layout')

@section('title', 'Audit Logs')
@section('subtitle', 'Platform & business activity trail')

@section('content')
@if ($noTable)
    <div class="panel p-5 text-center text-muted">No activity log table found on this database.</div>
@else

<div class="panel mb-3">
    <form method="GET" action="{{ route('admin.audit.index') }}" class="al-filters">
        <div class="al-search">
            <i class="bi bi-search"></i>
            <input type="text" name="q" value="{{ $filters['search'] }}" placeholder="Search description...">
        </div>
        <select name="module" class="al-select">
            <option value="">All modules</option>
            @foreach ($modules as $val => $label)
                <option value="{{ $val }}" {{ $filters['module'] === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <select name="event" class="al-select">
            <option value="">All actions</option>
            @foreach ($events as $ev)
                <option value="{{ $ev }}" {{ $filters['event'] === $ev ? 'selected' : '' }}>{{ ucfirst($ev) }}</option>
            @endforeach
        </select>
        <select name="causer" class="al-select">
            <option value="">All users</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" {{ (string) $filters['causer'] === (string) $u->id ? 'selected' : '' }}>{{ trim($u->first_name . ' ' . $u->last_name) ?: $u->email }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ $filters['from'] }}" class="al-select" title="From">
        <input type="date" name="to" value="{{ $filters['to'] }}" class="al-select" title="To">
        <button type="submit" class="btn-newvendor"><i class="bi bi-funnel"></i> Filter</button>
        <a href="{{ route('admin.audit.index') }}" class="btn btn-light">Reset</a>
    </form>
</div>

<div class="panel">
    <div class="panel-head"><h6 class="mb-0 fw-semibold">Activity</h6>
        <span class="ms-auto soft-pill">{{ number_format($logs->total()) }} events</span></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>When</th><th>User</th><th>Action</th><th>Module</th><th>Description</th></tr>
            </thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td><div class="fw-semibold small">{{ $log->when_exact }}</div><div class="text-muted" style="font-size:11px;">{{ $log->when_human }}</div></td>
                    <td>{{ $log->who }}</td>
                    <td><span class="al-event al-{{ $log->event ?: 'info' }}">{{ ucfirst($log->event ?: '—') }}</span></td>
                    <td><span class="al-mod">{{ $log->module_label }}</span></td>
                    <td class="text-muted small">{{ ucfirst($log->description ?: '—') }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="text-center text-muted py-5">No activity matches these filters.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-3 py-3" style="border-top:1px solid var(--border);">{{ $logs->links() }}</div>
</div>

<style>
.al-filters{ display:flex; flex-wrap:wrap; gap:10px; padding:16px 18px; align-items:center; }
.al-search{ position:relative; }
.al-search i{ position:absolute; left:12px; top:11px; color:var(--muted); font-size:14px; }
.al-search input{ height:40px; width:240px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--input-text); border-radius:10px; padding:0 14px 0 34px; font-size:13.5px; }
.al-select{ height:40px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--input-text); border-radius:10px; padding:0 12px; font-size:13.5px; }
.al-search input:focus, .al-select:focus{ outline:none; border-color:var(--accent); box-shadow:0 0 0 .2rem var(--accent-soft); }
.al-event{ font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:999px; text-transform:capitalize; }
.al-created,.al-restored{ background:rgba(16,185,129,.16); color:#10b981; }
.al-updated{ background:rgba(59,130,246,.16); color:#3b82f6; }
.al-deleted{ background:rgba(239,68,68,.16); color:#ef4444; }
.al-info{ background:rgba(148,163,184,.18); color:#94a3b8; }
.al-mod{ font-size:12.5px; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:3px 9px; }
.soft-pill{ font-size:12px; font-weight:500; color:var(--muted); background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:4px 10px; }
.pagination{ margin:0; flex-wrap:wrap; gap:4px; }
.page-link{ color:var(--accent); border-color:var(--border); border-radius:8px !important; background:var(--surface); }
.page-item.active .page-link{ background:var(--accent); border-color:var(--accent); color:#fff; }
.page-link:focus{ box-shadow:0 0 0 .2rem var(--accent-soft); }
</style>
@endif
@endsection
