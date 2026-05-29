@extends('admin.layout')

@section('title', 'Notifications')
@section('subtitle', 'Compose & manage platform announcements')

@section('content')
@php
    use Illuminate\Support\Carbon;
    $levelMeta = [
        'info'     => ['ic-blue',   'bi-info-circle',        'Info'],
        'success'  => ['ic-green',  'bi-check-circle',       'Success'],
        'warning'  => ['ic-amber',  'bi-exclamation-triangle','Warning'],
        'critical' => ['ic-red',    'bi-exclamation-octagon','Critical'],
    ];
    $audienceIc = ['all' => 'bi-people-fill', 'active' => 'bi-person-check-fill', 'inactive' => 'bi-person-dash-fill'];
@endphp

{{-- KPIs --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-4"><div class="kpi-card"><div class="kpi-top"><div><div class="kpi-label">Announcements</div><div class="kpi-value">{{ number_format($totalSent) }}</div></div><div class="kpi-ic ic-violet"><i class="bi bi-megaphone"></i></div></div></div></div>
    <div class="col-6 col-xl-4"><div class="kpi-card"><div class="kpi-top"><div><div class="kpi-label">Total Reach</div><div class="kpi-value">{{ number_format($totalReach) }}</div></div><div class="kpi-ic ic-green"><i class="bi bi-broadcast"></i></div></div><span class="na-tag">recipients across all posts</span></div></div>
    <div class="col-12 col-xl-4"><div class="kpi-card"><div class="kpi-top"><div><div class="kpi-label">Last Posted</div><div class="kpi-value" style="font-size:18px;">{{ $lastSent ? Carbon::parse($lastSent)->diffForHumans() : '—' }}</div></div><div class="kpi-ic ic-amber"><i class="bi bi-clock-history"></i></div></div></div></div>
</div>

<div class="row g-3">
    {{-- Compose --}}
    <div class="col-12 col-lg-5">
        <div class="panel">
            <div class="panel-head"><h6 class="mb-0 fw-semibold"><i class="bi bi-pencil-square me-1" style="color:var(--accent);"></i> New Announcement</h6></div>
            <form method="POST" action="{{ route('admin.notifications.store') }}" class="p-3">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" maxlength="160" value="{{ old('title') }}" placeholder="e.g. Scheduled maintenance this Sunday" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                    <textarea name="message" class="form-control" rows="4" maxlength="2000" placeholder="What do you want vendors to know?" required>{{ old('message') }}</textarea>
                </div>
                <div class="row g-2">
                    <div class="col-7">
                        <label class="form-label fw-semibold">Audience</label>
                        <select name="audience" class="form-select">
                            @foreach ($audiences as $key => $label)
                                <option value="{{ $key }}" {{ old('audience') === $key ? 'selected' : '' }}>{{ $label }} ({{ $audienceCounts[$key] ?? 0 }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-5">
                        <label class="form-label fw-semibold">Level</label>
                        <select name="level" class="form-select">
                            @foreach ($levels as $key => $label)
                                <option value="{{ $key }}" {{ old('level', 'info') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="d-flex align-items-center mt-3">
                    <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Stored & counted now; vendor-facing delivery is a follow-up.</span>
                    <button type="submit" class="btn btn-accent btn-sm ms-auto"><i class="bi bi-send me-1"></i> Post</button>
                </div>
            </form>
        </div>
    </div>

    {{-- History --}}
    <div class="col-12 col-lg-7">
        <div class="panel h-100">
            <div class="panel-head"><h6 class="mb-0 fw-semibold">Recent Announcements</h6><span class="ms-auto soft-pill">{{ $notifications->count() }} shown</span></div>
            <div class="p-2">
                @forelse ($notifications as $n)
                    @php [$icCls, $icon, $lvlLabel] = $levelMeta[$n->level] ?? $levelMeta['info']; @endphp
                    <div class="ann-row">
                        <span class="ann-ic {{ $icCls }}"><i class="bi {{ $icon }}"></i></span>
                        <div class="ann-body">
                            <div class="d-flex align-items-start">
                                <div class="ann-title">{{ $n->title }}</div>
                                <span class="lvl-tag {{ $icCls }} ms-2">{{ $lvlLabel }}</span>
                                <form method="POST" action="{{ route('admin.notifications.destroy', $n->id) }}" class="m-0 ms-auto" onsubmit="return confirm('Delete this announcement?');">@csrf @method('DELETE')
                                    <button class="ann-del" title="Delete"><i class="bi bi-trash3"></i></button>
                                </form>
                            </div>
                            <div class="ann-msg">{{ $n->message }}</div>
                            <div class="ann-meta">
                                <span><i class="bi {{ $audienceIc[$n->audience] ?? 'bi-people' }} me-1"></i>{{ $audiences[$n->audience] ?? ucfirst($n->audience) }}</span>
                                <span><i class="bi bi-broadcast me-1"></i>{{ number_format($n->recipients) }} recipients</span>
                                <span class="ms-auto"><i class="bi bi-clock me-1"></i>{{ Carbon::parse($n->created_at)->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">
                        <div style="font-size:40px;color:var(--muted);"><i class="bi bi-megaphone"></i></div>
                        <p class="mb-0 mt-2">No announcements yet. Post your first one.</p>
                    </div>
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
.ic-amber{ background:rgba(245,158,11,.16); color:#f59e0b; } .ic-red{ background:rgba(239,68,68,.16); color:#ef4444; } .ic-blue{ background:rgba(59,130,246,.16); color:#3b82f6; }
.na-tag{ display:inline-block; margin-top:8px; font-size:10px; font-weight:600; color:var(--muted); background:var(--surface-2); border:1px solid var(--border); border-radius:6px; padding:1px 7px; }
.soft-pill{ font-size:12px; font-weight:500; color:var(--muted); background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:4px 10px; }
.h-100{ height:100%; }

.ann-row{ display:flex; gap:12px; padding:13px 12px; border-bottom:1px solid var(--border); }
.ann-row:last-child{ border-bottom:0; }
.ann-ic{ width:38px; height:38px; flex:0 0 38px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:17px; }
.ann-body{ flex:1; min-width:0; }
.ann-title{ font-weight:700; font-size:14px; color:var(--text); }
.ann-msg{ font-size:13px; color:var(--muted); margin-top:3px; line-height:1.45; white-space:pre-line; }
.ann-meta{ display:flex; align-items:center; gap:14px; margin-top:8px; font-size:11.5px; color:var(--muted); flex-wrap:wrap; }
.lvl-tag{ font-size:10.5px; font-weight:700; padding:2px 9px; border-radius:999px; text-transform:uppercase; letter-spacing:.04em; flex:0 0 auto; }
.ann-del{ border:0; background:transparent; color:var(--muted); font-size:14px; padding:2px 4px; border-radius:6px; line-height:1; }
.ann-del:hover{ color:#ef4444; background:rgba(239,68,68,.1); }
</style>
@endsection
