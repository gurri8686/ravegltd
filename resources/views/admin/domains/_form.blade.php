@php
    $isEdit = isset($site) && $site;
    $linkedVendorId = $linkedVendorId ?? null;
@endphp

<div class="vf-section"><i class="bi bi-diagram-3"></i> Domain</div>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="vf-label">Link to vendor</label>
        <select name="vendor_id" id="vendorSelect" class="form-control">
            <option value="">— Unassigned —</option>
            @foreach ($vendors as $v)
                @php $vn = trim($v->first_name . ' ' . $v->last_name) ?: $v->email; @endphp
                <option value="{{ $v->id }}" data-name="{{ $vn }}" {{ (string) old('vendor_id', $linkedVendorId) === (string) $v->id ? 'selected' : '' }}>{{ $vn }} ({{ $v->email }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="vf-label">Subdomain <span class="text-danger">*</span></label>
        <div class="input-group vf-ig">
            <span class="input-group-text"><i class="bi bi-diagram-3"></i></span>
            <input type="text" name="subdomain" id="subdomainInput" class="form-control"
                   value="{{ old('subdomain', $isEdit ? $site->subdomain : '') }}" placeholder="nike.{{ $base }}" required>
            <button type="button" class="btn btn-light" id="genBtn" title="Auto-generate from vendor"><i class="bi bi-magic"></i></button>
        </div>
        <div class="text-muted small mt-1">Auto-generates <code>vendor.{{ $base }}</code> from the selected vendor.</div>
    </div>
    <div class="col-md-6">
        <label class="vf-label">Custom domain</label>
        <div class="input-group vf-ig">
            <span class="input-group-text"><i class="bi bi-globe2"></i></span>
            <input type="text" name="domain" class="form-control" value="{{ old('domain', $isEdit ? $site->domain : '') }}" placeholder="nike.com">
        </div>
        <div class="text-muted small mt-1">Optional — the vendor's own domain mapped to this site.</div>
    </div>
    <div class="col-md-6">
        <label class="vf-label">Database <span class="text-muted small fw-normal">(auto)</span></label>
        <div class="input-group vf-ig">
            <span class="input-group-text"><i class="bi bi-database"></i></span>
            <input type="text" name="database" id="databaseInput" class="form-control" value="{{ old('database', $isEdit ? $site->database : '') }}" placeholder="auto-created on save" readonly>
        </div>
        <div class="text-muted small mt-1">Auto-created for the vendor (<code>slug_id_ravegltd</code>) — no need to type it.</div>
    </div>
    <div class="col-12">
        <div class="form-check form-switch mt-1">
            <input type="checkbox" name="status" value="1" class="form-check-input" id="status" {{ old('status', $isEdit ? $site->status : '1') ? 'checked' : '' }}>
            <label class="form-check-label" for="status">Active</label>
        </div>
    </div>
</div>

<div class="reserved-note">
    <i class="bi bi-shield-exclamation"></i>
    <div><b>Reserved names</b> can't be used as a subdomain: <span>admin, api, app, mail, www, dev, staging, …</span></div>
</div>

<style>
.vf-section{ display:flex; align-items:center; gap:8px; font-size:12px; text-transform:uppercase; letter-spacing:.07em; color:var(--accent); font-weight:700; padding-bottom:9px; margin-bottom:6px; border-bottom:2px solid var(--accent-soft); }
.vf-label{ font-size:12.5px; font-weight:600; color:var(--text); margin-bottom:5px; }
.vf-ig .input-group-text{ background:var(--surface-2); border-color:var(--input-border); color:var(--muted); }
.form-control{ min-height:44px; background:var(--input-bg); border-color:var(--input-border); color:var(--input-text); }
code{ color:var(--accent); }
.reserved-note{ display:flex; gap:10px; align-items:flex-start; background:rgba(245,158,11,.10); border:1px solid rgba(245,158,11,.3); border-radius:11px; padding:12px 14px; font-size:12.5px; color:var(--text); }
.reserved-note > i{ color:#f59e0b; font-size:17px; margin-top:1px; }
.reserved-note span{ color:var(--muted); }
</style>
<script>
(function(){
    var base = @json($base);
    var sel = document.getElementById('vendorSelect');
    var sub = document.getElementById('subdomainInput');
    var dbf = document.getElementById('databaseInput');
    var gen = document.getElementById('genBtn');
    function slug(s){ return (s||'').toLowerCase().trim().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,''); }

    // Fill subdomain + database preview from the selected vendor.
    function fillFromVendor(){
        var opt = sel && sel.options[sel.selectedIndex];
        var name = opt ? (opt.getAttribute('data-name') || '') : '';
        var id   = opt ? opt.value : '';
        if (!id || !name) return false;
        sub.value = slug(name) + '.' + base;
        if (dbf) dbf.value = slug(name).replace(/-/g, '_') + '_' + id + '_ravegltd';
        return true;
    }

    // Auto-fill the moment a vendor is selected.
    if (sel) sel.addEventListener('change', fillFromVendor);

    // Magic button does the same (handy if they edit the subdomain then want to reset).
    if (gen) gen.addEventListener('click', function(){
        if (!fillFromVendor()) { saToast && saToast('Pick a vendor first to auto-generate.', 'info'); }
    });
})();
</script>
