@extends('admin.layout')

@section('title', 'Admin Users')
@section('subtitle', 'Platform admin accounts & access control')

@section('content')
@php
    use Illuminate\Support\Carbon;
    $roleColors = ['superadmin' => 'rc-violet', 'finance' => 'rc-green', 'operations' => 'rc-blue', 'technical' => 'rc-amber'];
@endphp

<div class="d-flex align-items-center mb-3">
    <div><h5 class="mb-0 fw-bold">Admin Users</h5><div class="text-muted small">Platform team accounts</div></div>
    <a href="{{ route('admin.adminusers.create') }}" class="btn-newvendor ms-auto"><i class="bi bi-plus-lg"></i> Add Admin</a>
</div>

<div class="panel mb-4">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($admins as $a)
                <tr>
                    <td class="fw-semibold">{{ trim($a->first_name . ' ' . $a->last_name) ?: '—' }}</td>
                    <td class="text-muted">{{ $a->email }}</td>
                    <td><span class="role-chip {{ $roleColors[$a->role] ?? 'rc-grey' }}">{{ ucfirst($a->role) }}</span></td>
                    <td><span class="pill {{ $a->is_active ? 'pill-on' : 'pill-off' }}">{{ $a->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-muted small">{{ $a->last_login_at ? uk_ts($a->last_login_at, 'd M Y H:i') : 'Never' }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('admin.adminusers.edit', $a->id) }}" class="act-btn act-edit" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('admin.adminusers.destroy', $a->id) }}" class="m-0" onsubmit="return confirm('Delete admin &quot;{{ $a->email }}&quot;?');">@csrf @method('DELETE')
                                <button class="act-btn act-del" title="Delete"><i class="bi bi-trash3"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="text-center text-muted py-4">No admin users.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ===== Permissions matrix ===== --}}
<div class="panel">
    <div class="panel-head"><h6 class="mb-0 fw-semibold"><i class="bi bi-grid-3x3-gap me-1"></i> Permissions Matrix</h6>
        <span class="ms-auto text-muted small">Superadmin always has full access</span></div>
    <form method="POST" action="{{ route('admin.adminusers.matrix') }}">
        @csrf
        <div class="table-responsive">
            <table class="table align-middle mb-0 matrix">
                <thead>
                    <tr>
                        <th>Section</th>
                        <th class="text-center"><span class="role-chip rc-violet">Superadmin</span></th>
                        <th class="text-center"><span class="role-chip rc-green">Finance</span></th>
                        <th class="text-center"><span class="role-chip rc-blue">Operations</span></th>
                        <th class="text-center"><span class="role-chip rc-amber">Technical</span></th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($sections as $key => $label)
                    <tr>
                        <td class="fw-semibold">{{ $label }}</td>
                        <td class="text-center"><i class="bi bi-check-circle-fill" style="color:#10b981;"></i></td>
                        @foreach (['finance', 'operations', 'technical'] as $role)
                            <td class="text-center">
                                @if ($key === 'dashboard')
                                    <i class="bi bi-check-circle-fill" style="color:#10b981;" title="Always allowed"></i>
                                @else
                                    <input type="checkbox" class="form-check-input" name="access[{{ $role }}][{{ $key }}]" value="1" {{ ($matrix[$role][$key] ?? false) ? 'checked' : '' }}>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-3" style="border-top:1px solid var(--border);">
            <button type="submit" class="btn-newvendor"><i class="bi bi-check-lg"></i> Save Matrix</button>
        </div>
    </form>
</div>

<style>
.role-chip{ font-size:11px; font-weight:700; padding:3px 11px; border-radius:999px; text-transform:capitalize; }
.rc-violet{ background:var(--accent-soft); color:var(--accent); }
.rc-green{ background:rgba(16,185,129,.16); color:#10b981; }
.rc-blue{ background:rgba(59,130,246,.16); color:#3b82f6; }
.rc-amber{ background:rgba(245,158,11,.16); color:#f59e0b; }
.rc-grey{ background:rgba(148,163,184,.18); color:#94a3b8; }
.act-btn{ width:34px; height:34px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); background:var(--surface); color:var(--muted); font-size:15px; transition:.15s; }
.act-edit:hover{ background:var(--accent); border-color:var(--accent); color:#fff; }
.act-del{ color:#ef4444; border-color:rgba(239,68,68,.35); } .act-del:hover{ background:#ef4444; color:#fff; }
.matrix tbody td{ border-color:var(--border); } .matrix .form-check-input{ width:18px; height:18px; }
</style>
@endsection
