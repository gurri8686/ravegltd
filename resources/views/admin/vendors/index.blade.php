@extends('admin.layout')

@section('title', 'Vendors')
@section('subtitle', 'Manage all vendors on the platform')

@section('content')
@php
    use Illuminate\Support\Carbon;
    $total = $vendors->count();
    $activeCount = $vendors->where('is_active', 1)->count();
    $subscribed = $vendors->filter(fn($v) => $v->subscription && Carbon::parse($v->subscription->expire)->isFuture())->count();
@endphp

<div class="row g-3 mb-3">
    <div class="col-sm-4"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-people-fill"></i></div><div><div class="label">Total Vendors</div><div class="value">{{ $total }}</div></div></div></div>
    <div class="col-sm-4"><div class="stat-card"><div class="stat-icon si-green"><i class="bi bi-check-circle-fill"></i></div><div><div class="label">Active</div><div class="value">{{ $activeCount }}</div></div></div></div>
    <div class="col-sm-4"><div class="stat-card"><div class="stat-icon si-neutral"><i class="bi bi-patch-check-fill"></i></div><div><div class="label">Subscribed</div><div class="value">{{ $subscribed }}</div></div></div></div>
</div>

<div class="panel">
    <div class="vt-head">
        <div>
            <h5 class="mb-0 fw-bold">Vendors</h5>
            <div class="text-muted small">Manage all vendors on the platform</div>
        </div>
        <div class="vt-tools">
            <select id="statusFilter" class="vt-select">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <select id="subFilter" class="vt-select">
                <option value="">All Subscriptions</option>
                <option value="active">Subscribed</option>
                <option value="expired">Expired</option>
                <option value="none">No subscription</option>
            </select>
            <div class="vt-search">
                <i class="bi bi-search"></i>
                <input id="vendorSearch" type="text" placeholder="Search vendor...">
            </div>
            <a href="{{ route('admin.vendors.create') }}" class="btn-newvendor"><i class="bi bi-plus-lg"></i> Add Vendor</a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0" id="vendorTable">
            <thead>
                <tr>
                    <th class="sortable" data-col="0">Vendor <i class="bi bi-arrow-down-up sort-ic"></i></th>
                    <th>Subdomain</th>
                    <th data-col="2">Subscription</th>
                    <th>Plan</th>
                    <th class="sortable text-end" data-col="4">Sales <i class="bi bi-arrow-down-up sort-ic"></i></th>
                    <th>Status</th>
                    <th class="sortable" data-col="6">Created <i class="bi bi-arrow-down-up sort-ic"></i></th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($vendors as $vendor)
                @php
                    $name = trim($vendor->first_name . ' ' . $vendor->last_name);
                    $sub  = $vendor->subscription;
                    $subActive = $sub && Carbon::parse($sub->expire)->isFuture();
                    $subState = $subActive ? 'active' : ($sub ? 'expired' : 'none');
                @endphp
                <tr data-active="{{ $vendor->is_active }}" data-sub="{{ $subState }}">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            @if ($vendor->image && \Illuminate\Support\Str::startsWith($vendor->image, 'uploads/'))
                                <img src="{{ asset($vendor->image) }}" class="av-img" alt="">
                            @else
                                <span class="av">{{ strtoupper(substr($vendor->first_name ?: $vendor->email, 0, 1)) }}</span>
                            @endif
                            <div>
                                <a href="{{ route('admin.vendors.show', $vendor->id) }}" class="fw-semibold acct-name">{{ $name ?: '—' }}</a>
                                <div class="text-muted small">{{ $vendor->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if ($vendor->domain)
                            <span class="dom-pill"><i class="bi bi-globe2 me-1"></i>{{ $vendor->domain }}</span>
                        @else
                            <span class="text-muted small">Not linked</span>
                        @endif
                    </td>
                    <td>
                        @if ($subActive)
                            <span class="pill pill-on">Active</span>
                            <div class="text-muted small mt-1">until {{ Carbon::parse($sub->expire)->format('d M Y') }}</div>
                        @elseif ($sub)
                            <span class="pill pill-warn">Expired</span>
                            <a href="{{ route('admin.subscriptions.index', ['vendor' => $vendor->id]) }}" class="lnk-accent small ms-1">Renew</a>
                        @else
                            <a href="{{ route('admin.subscriptions.index', ['vendor' => $vendor->id]) }}" class="lnk-accent small"><i class="bi bi-patch-check"></i> Subscribe</a>
                        @endif
                    </td>
                    <td>
                        @if ($vendor->plan)
                            <span class="plan-badge">{{ $vendor->plan }}</span>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                    <td class="text-end fw-semibold">£{{ number_format($vendor->sales, 2) }}</td>
                    <td>
                        <div class="form-check form-switch m-0 d-inline-flex align-items-center">
                            <input class="form-check-input vendor-toggle" type="checkbox" role="switch"
                                   data-id="{{ $vendor->id }}" data-name="{{ $name ?: $vendor->email }}" {{ $vendor->is_active ? 'checked' : '' }}
                                   title="{{ $vendor->is_active ? 'Active — toggle to deactivate' : 'Inactive — toggle to activate' }}">
                            <span class="spinner-border spinner-border-sm tg-spin d-none ms-2" style="width:16px;height:16px;color:var(--accent);"></span>
                        </div>
                    </td>
                    <td class="text-muted small">{{ $vendor->created_at ? Carbon::parse($vendor->created_at)->format('d M Y') : '—' }}</td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('admin.vendors.show', $vendor->id) }}" class="act-btn act-view" title="Sales & Purchases"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('admin.vendors.edit', $vendor->id) }}" class="act-btn act-edit" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('admin.vendors.destroy', $vendor->id) }}" class="m-0" onsubmit="return confirm('Delete vendor &quot;{{ $name ?: $vendor->email }}&quot;?');">@csrf @method('DELETE')
                                <button class="act-btn act-del" title="Delete"><i class="bi bi-trash3"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr id="emptyRow"><td colspan="8"><div class="text-center py-5"><div style="font-size:42px;color:var(--muted);"><i class="bi bi-people"></i></div><p class="text-muted mb-3 mt-2">No vendors yet.</p><a href="{{ route('admin.vendors.create') }}" class="btn-newvendor d-inline-flex"><i class="bi bi-plus-lg"></i> Add your first vendor</a></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="vt-foot">
        <span id="vInfo" class="text-muted small"></span>
        <div id="vPager" class="vt-pager"></div>
    </div>
</div>

{{-- Deactivate confirmation --}}
<div class="modal fade" id="deactivateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--surface);color:var(--text);border:1px solid var(--border);">
            <div class="modal-body text-center p-4">
                <div style="width:64px;height:64px;border-radius:50%;background:rgba(239,68,68,.12);color:#ef4444;display:flex;align-items:center;justify-content:center;font-size:30px;margin:0 auto 14px;"><i class="bi bi-pause-circle"></i></div>
                <h5 class="fw-semibold mb-1">Deactivate vendor?</h5>
                <p class="text-muted mb-4"><strong id="deactivateName"></strong> won't be able to log in until reactivated.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="deactivateConfirm"><i class="bi bi-pause-circle"></i> Deactivate</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.vt-head{ display:flex; align-items:center; gap:14px; padding:18px 20px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
.vt-tools{ margin-left:auto; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.vt-select{ height:38px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--input-text); border-radius:10px; padding:0 30px 0 12px; font-size:13.5px; cursor:pointer; }
.vt-search{ position:relative; }
.vt-search i{ position:absolute; left:12px; top:11px; color:var(--muted); font-size:14px; }
.vt-search input{ height:38px; width:230px; border:1px solid var(--input-border); background:var(--input-bg); color:var(--input-text); border-radius:10px; padding:0 14px 0 34px; font-size:13.5px; }
.vt-search input:focus{ outline:none; border-color:var(--accent); box-shadow:0 0 0 .2rem var(--accent-soft); }
#vendorTable th.sortable{ cursor:pointer; user-select:none; }
#vendorTable th .sort-ic{ font-size:11px; color:var(--muted); margin-left:3px; }
.dom-pill{ display:inline-flex; align-items:center; font-size:12.5px; color:var(--text); background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:4px 10px; }
.pill-warn{ background:rgba(245,158,11,.16); color:#f59e0b; }
.plan-badge{ display:inline-flex; align-items:center; font-size:12px; font-weight:600; color:var(--accent); background:var(--accent-soft); border:1px solid var(--accent); border-radius:999px; padding:2px 11px; }
.lnk-accent{ color:var(--accent); font-weight:600; }
.act-btn{ width:34px; height:34px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center;
          border:1px solid var(--border); background:var(--surface); color:var(--muted); font-size:15px; transition:.15s; }
.act-btn:hover{ color:#fff; transform:translateY(-1px); }
.act-view:hover{ background:#6366f1; border-color:#6366f1; }
.act-edit:hover{ background:var(--accent); border-color:var(--accent); }
.act-del{ color:#ef4444; border-color:rgba(239,68,68,.35); }
.act-del:hover{ background:#ef4444; border-color:#ef4444; color:#fff; }
.vt-foot{ display:flex; justify-content:space-between; align-items:center; padding:14px 20px; border-top:1px solid var(--border); }
.vt-pager{ display:flex; gap:6px; }
.vt-pg{ min-width:34px; height:34px; border-radius:9px; border:1px solid var(--border); background:var(--surface); color:var(--text);
        font-size:13px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; padding:0 6px; }
.vt-pg:hover{ border-color:var(--accent); color:var(--accent); }
.vt-pg.active{ background:var(--accent); border-color:var(--accent); color:#fff; }
.vt-pg:disabled{ opacity:.45; cursor:default; }
</style>
@endsection

@section('scripts')
<script>
(function () {
    var search = document.getElementById('vendorSearch');
    var statusF = document.getElementById('statusFilter');
    var subF = document.getElementById('subFilter');
    var tbody = document.querySelector('#vendorTable tbody');
    var infoEl = document.getElementById('vInfo');
    var pagerEl = document.getElementById('vPager');
    var allRows = Array.prototype.slice.call(tbody.querySelectorAll('tr')).filter(function (r) { return r.id !== 'emptyRow'; });
    var sortCol = null, sortDir = 1, page = 1, size = 10;

    function filtered() {
        var q = (search.value || '').toLowerCase();
        var st = statusF.value, sb = subF.value;
        var rows = allRows.filter(function (r) {
            if (q && r.innerText.toLowerCase().indexOf(q) === -1) return false;
            if (st !== '' && r.dataset.active !== st) return false;
            if (sb !== '' && r.dataset.sub !== sb) return false;
            return true;
        });
        if (sortCol !== null) {
            rows.sort(function (a, b) {
                var x = (a.children[sortCol] ? a.children[sortCol].innerText : '').trim().toLowerCase();
                var y = (b.children[sortCol] ? b.children[sortCol].innerText : '').trim().toLowerCase();
                return x.localeCompare(y, undefined, { numeric: true }) * sortDir;
            });
        }
        return rows;
    }

    function render() {
        var rows = filtered();
        var pages = Math.max(1, Math.ceil(rows.length / size));
        if (page > pages) page = pages;
        allRows.forEach(function (r) { r.style.display = 'none'; });
        rows.forEach(function (r, i) {
            tbody.appendChild(r);
            r.style.display = (i >= (page - 1) * size && i < page * size) ? '' : 'none';
        });
        var from = rows.length ? (page - 1) * size + 1 : 0;
        var to = Math.min(page * size, rows.length);
        if (infoEl) infoEl.textContent = 'Showing ' + from + ' to ' + to + ' of ' + rows.length + ' vendors';
        // pager
        pagerEl.innerHTML = '';
        var mk = function (label, p, opts) {
            var b = document.createElement('button');
            b.className = 'vt-pg' + (opts && opts.active ? ' active' : '');
            b.innerHTML = label;
            if (opts && opts.disabled) b.disabled = true;
            else b.addEventListener('click', function () { page = p; render(); });
            pagerEl.appendChild(b);
        };
        mk('<i class="bi bi-chevron-left"></i>', page - 1, { disabled: page <= 1 });
        for (var p = 1; p <= pages; p++) mk(String(p), p, { active: p === page });
        mk('<i class="bi bi-chevron-right"></i>', page + 1, { disabled: page >= pages });
    }

    [search, statusF, subF].forEach(function (el) { if (el) el.addEventListener('input', function () { page = 1; render(); }); });
    document.querySelectorAll('#vendorTable th.sortable').forEach(function (th) {
        th.addEventListener('click', function () {
            var col = parseInt(th.dataset.col, 10);
            if (sortCol === col) { sortDir = -sortDir; } else { sortCol = col; sortDir = 1; }
            document.querySelectorAll('#vendorTable th .sort-ic').forEach(function (i) { i.className = 'bi bi-arrow-down-up sort-ic'; });
            var ic = th.querySelector('.sort-ic');
            if (ic) ic.className = 'bi ' + (sortDir === 1 ? 'bi-sort-down-alt' : 'bi-sort-up') + ' sort-ic';
            render();
        });
    });
    render();
})();

(function () {
    var base = "{{ url('admin/vendors') }}";
    var csrf = document.querySelector('meta[name=csrf-token]').content;
    var pending = null;

    function doToggle(cb) {
        var spin = cb.parentElement.querySelector('.tg-spin');
        cb.disabled = true; if (spin) spin.classList.remove('d-none');
        fetch(base + '/' + cb.dataset.id + '/toggle', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            cb.checked = (data.is_active === 1 || data.is_active === true);
            cb.title = cb.checked ? 'Active — toggle to deactivate' : 'Inactive — toggle to activate';
            cb.closest('tr').dataset.active = cb.checked ? '1' : '0';
            cb.disabled = false; if (spin) spin.classList.add('d-none');
            saToast(data.message || 'Updated.', 'success');
        }).catch(function () {
            cb.checked = !cb.checked; cb.disabled = false; if (spin) spin.classList.add('d-none');
            saToast('Something went wrong — please retry.', 'error');
        });
    }

    document.querySelectorAll('.vendor-toggle').forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (!cb.checked) {
                cb.checked = true;
                pending = cb;
                document.getElementById('deactivateName').textContent = cb.dataset.name;
                new bootstrap.Modal(document.getElementById('deactivateModal')).show();
            } else {
                doToggle(cb);
            }
        });
    });

    document.getElementById('deactivateConfirm')?.addEventListener('click', function () {
        var m = bootstrap.Modal.getInstance(document.getElementById('deactivateModal'));
        if (m) m.hide();
        if (pending) { doToggle(pending); pending = null; }
    });
})();
</script>
@endsection
