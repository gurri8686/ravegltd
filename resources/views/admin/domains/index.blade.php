@extends('admin.layout')

@section('title', 'Domains')
@section('subtitle', 'Subdomains & custom domain mapping')

@section('content')
@php
    use Illuminate\Support\Carbon;
    $active = $sites->where('status', 1)->count();
    $sslOn  = $sites->where('ssl_status', 'active')->count();
@endphp

<div class="row g-3 mb-3">
    <div class="col-sm-4"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-globe2"></i></div><div><div class="label">Domains</div><div class="value">{{ $sites->count() }}</div></div></div></div>
    <div class="col-sm-4"><div class="stat-card"><div class="stat-icon si-green"><i class="bi bi-check-circle-fill"></i></div><div><div class="label">Active</div><div class="value">{{ $active }}</div></div></div></div>
    <div class="col-sm-4"><div class="stat-card"><div class="stat-icon si-green"><i class="bi bi-shield-lock-fill"></i></div><div><div class="label">SSL secured</div><div class="value">{{ $sslOn }}</div></div></div></div>
</div>

<div class="panel">
    <div class="vt-head">
        <div>
            <h5 class="mb-0 fw-bold">Domain Management</h5>
            <div class="text-muted small">Subdomains, custom domains, SSL &amp; DNS</div>
        </div>
        <div class="vt-tools">
            <div class="vt-search">
                <i class="bi bi-search"></i>
                <input id="domSearch" type="text" placeholder="Search domain...">
            </div>
            <a href="{{ route('admin.domains.create') }}" class="btn-newvendor"><i class="bi bi-plus-lg"></i> Add Domain</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0" id="domTable">
            <thead>
                <tr>
                    <th>Vendor</th>
                    <th>Subdomain</th>
                    <th>Custom Domain</th>
                    <th>SSL</th>
                    <th>DNS</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($sites as $s)
                <tr data-id="{{ $s->id }}">
                    <td>
                        @if ($s->vendor)
                            <span class="fw-semibold">{{ $s->vendor }}</span>
                            @if ($s->vendor_more)<span class="text-muted small"> +{{ $s->vendor_more }}</span>@endif
                        @else
                            <span class="text-muted small">Unassigned</span>
                        @endif
                    </td>
                    <td><span class="dom-pill"><i class="bi bi-diagram-3 me-1"></i>{{ $s->subdomain }}</span></td>
                    <td>
                        @if ($s->domain && $s->domain !== $s->subdomain)
                            <span class="dom-pill"><i class="bi bi-globe2 me-1"></i>{{ $s->domain }}</span>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="ssl-cell">@include('admin.domains._ssl', ['s' => $s])</td>
                    <td class="dns-cell">@include('admin.domains._dns', ['s' => $s])</td>
                    <td>
                        <div class="form-check form-switch m-0 d-inline-flex align-items-center">
                            <input class="form-check-input dom-toggle" type="checkbox" role="switch"
                                   data-id="{{ $s->id }}" data-name="{{ $s->subdomain }}" {{ $s->status ? 'checked' : '' }}>
                            <span class="spinner-border spinner-border-sm tg-spin d-none ms-2" style="width:16px;height:16px;color:var(--accent);"></span>
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <button class="act-btn act-verify" data-id="{{ $s->id }}" title="Verify DNS & SSL"><i class="bi bi-arrow-repeat"></i></button>
                            <a href="{{ route('admin.domains.edit', $s->id) }}" class="act-btn act-edit" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('admin.domains.destroy', $s->id) }}" class="m-0" onsubmit="return confirm('Remove domain &quot;{{ $s->subdomain }}&quot;? (its database is NOT dropped)');">@csrf @method('DELETE')
                                <button class="act-btn act-del" title="Delete"><i class="bi bi-trash3"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr id="emptyRow"><td colspan="7"><div class="text-center py-5"><div style="font-size:42px;color:var(--muted);"><i class="bi bi-globe2"></i></div><p class="text-muted mb-3 mt-2">No domains yet.</p><a href="{{ route('admin.domains.create') }}" class="btn-newvendor d-inline-flex"><i class="bi bi-plus-lg"></i> Add your first domain</a></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
.vt-head{ display:flex; align-items:center; gap:14px; padding:18px 20px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
.vt-tools{ margin-left:auto; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.vt-search{ position:relative; }
.vt-search i{ position:absolute; left:12px; top:11px; color:var(--muted); font-size:14px; }
.vt-search input{ height:38px; width:230px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--input-text); border-radius:10px; padding:0 14px 0 34px; font-size:13.5px; }
.vt-search input:focus{ outline:none; border-color:var(--accent); box-shadow:0 0 0 .2rem var(--accent-soft); }
.dom-pill{ display:inline-flex; align-items:center; font-size:12.5px; color:var(--text); background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:4px 10px; }
.badge-dot{ display:inline-flex; align-items:center; gap:6px; font-size:12px; font-weight:600; padding:4px 10px; border-radius:999px; }
.bd-green{ background:rgba(16,185,129,.16); color:#10b981; } .bd-red{ background:rgba(239,68,68,.16); color:#ef4444; }
.bd-grey{ background:rgba(148,163,184,.18); color:#94a3b8; }
.act-btn{ width:34px; height:34px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); background:var(--surface); color:var(--muted); font-size:15px; transition:.15s; }
.act-btn:hover{ color:#fff; transform:translateY(-1px); }
.act-verify:hover{ background:#10b981; border-color:#10b981; }
.act-edit:hover{ background:var(--accent); border-color:var(--accent); }
.act-del{ color:#ef4444; border-color:rgba(239,68,68,.35); }
.act-del:hover{ background:#ef4444; border-color:#ef4444; color:#fff; }
.spin-go i{ animation:spin .7s linear infinite; }
@keyframes spin{ to{ transform:rotate(360deg); } }
</style>
@endsection

@section('scripts')
<script>
(function () {
    var search = document.getElementById('domSearch');
    var tbody = document.querySelector('#domTable tbody');
    if (search) search.addEventListener('keyup', function () {
        var q = search.value.toLowerCase();
        tbody.querySelectorAll('tr').forEach(function (r) {
            if (r.id === 'emptyRow') return;
            r.style.display = r.innerText.toLowerCase().indexOf(q) > -1 ? '' : 'none';
        });
    });
})();

(function () {
    var base = "{{ url('admin/domains') }}";
    var csrf = document.querySelector('meta[name=csrf-token]').content;

    // status toggle
    document.querySelectorAll('.dom-toggle').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var spin = cb.parentElement.querySelector('.tg-spin');
            cb.disabled = true; if (spin) spin.classList.remove('d-none');
            fetch(base + '/' + cb.dataset.id + '/toggle', {
                method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (r) { return r.json(); }).then(function (d) {
                cb.checked = d.status === 1; cb.disabled = false; if (spin) spin.classList.add('d-none');
                saToast(d.message || 'Updated.', 'success');
            }).catch(function () { cb.checked = !cb.checked; cb.disabled = false; if (spin) spin.classList.add('d-none'); saToast('Failed — retry.', 'error'); });
        });
    });

    // verify DNS + SSL
    function sslBadge(v) {
        if (v === 'active') return '<span class="badge-dot bd-green"><i class="bi bi-shield-lock-fill"></i> Active</span>';
        if (v === 'expired') return '<span class="badge-dot bd-red"><i class="bi bi-shield-exclamation"></i> Expired</span>';
        return '<span class="badge-dot bd-grey"><i class="bi bi-shield-slash"></i> No SSL</span>';
    }
    function dnsBadge(ok) {
        return ok ? '<span class="badge-dot bd-green"><i class="bi bi-check-circle"></i> Verified</span>'
                  : '<span class="badge-dot bd-red"><i class="bi bi-x-circle"></i> Unverified</span>';
    }
    document.querySelectorAll('.act-verify').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.dataset.id, row = btn.closest('tr');
            btn.classList.add('spin-go'); btn.disabled = true;
            fetch(base + '/' + id + '/verify', {
                method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (r) { return r.json(); }).then(function (d) {
                row.querySelector('.ssl-cell').innerHTML = sslBadge(d.ssl_status);
                row.querySelector('.dns-cell').innerHTML = dnsBadge(d.dns_verified);
                btn.classList.remove('spin-go'); btn.disabled = false;
                saToast(d.message || 'Verified.', d.dns_verified ? 'success' : 'info');
            }).catch(function () { btn.classList.remove('spin-go'); btn.disabled = false; saToast('Verify failed — retry.', 'error'); });
        });
    });
})();
</script>
@endsection
