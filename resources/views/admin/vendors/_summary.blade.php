@php
    $isEdit = isset($vendor) && $vendor;
    $pvName = $isEdit ? (trim($vendor->first_name . ' ' . $vendor->last_name) ?: $vendor->email) : '';
    $pvInit = $isEdit ? strtoupper(substr($vendor->first_name ?: $vendor->email, 0, 1)) : 'V';
@endphp
<div class="panel vs-card">
    <div class="vs-head">{{ $isEdit ? 'Vendor Summary' : 'New Vendor' }}</div>
    <div class="p-3 text-center vs-preview">
        @if ($isEdit && $vendor->image && \Illuminate\Support\Str::startsWith($vendor->image, 'uploads/'))
            <img id="pvAvatar" class="vs-av" src="{{ asset($vendor->image) }}">
        @else
            <div id="pvAvatar" class="vs-av vs-av-ph">{{ $pvInit }}</div>
        @endif
        <div id="pvName" class="vs-name">{{ $pvName ?: 'Vendor name' }}</div>
        <div id="pvEmail" class="vs-email">{{ $isEdit ? $vendor->email : 'email@example.com' }}</div>
        <span id="pvStatus" class="pill {{ (!$isEdit || $vendor->is_active) ? 'pill-on' : 'pill-off' }} mt-2 d-inline-block">
            {{ (!$isEdit || $vendor->is_active) ? 'Active' : 'Inactive' }}
        </span>
    </div>

    <div class="vs-list">
        <div class="vs-li"><i class="bi bi-shield-check"></i> Account access to the main app</div>
        <div class="vs-li"><i class="bi bi-person-badge"></i> Created with <b>admin</b> role</div>
        <div class="vs-li"><i class="bi bi-patch-check"></i> Assign a subscription anytime</div>
        @if ($isEdit)
            <a href="{{ route('admin.vendors.show', $vendor->id) }}" class="vs-li vs-link"><i class="bi bi-bar-chart-line"></i> View Sales &amp; Purchases</a>
        @endif
    </div>
</div>

<style>
.vs-card{ position:sticky; top:84px; }
.vs-head{ padding:14px 18px; border-bottom:1px solid var(--border); font-weight:700; font-size:14px; color:var(--text); }
.vs-preview{ border-bottom:1px solid var(--border); }
.vs-av{ width:88px; height:88px; border-radius:16px; object-fit:contain; background:var(--surface-2); border:1px solid var(--border); padding:6px; margin:6px auto 12px; display:flex; align-items:center; justify-content:center; }
.vs-av-ph{ background:linear-gradient(135deg,var(--accent),var(--accent-dark)); color:#fff; font-size:34px; font-weight:700; border:0; padding:0; }
.vs-name{ font-weight:700; font-size:16px; color:var(--text); }
.vs-email{ font-size:13px; color:var(--muted); word-break:break-all; }
.vs-list{ padding:14px 18px; }
.vs-li{ display:flex; align-items:center; gap:10px; padding:9px 0; font-size:13px; color:var(--text); border-bottom:1px solid var(--border); }
.vs-li:last-child{ border-bottom:0; }
.vs-li > i{ color:var(--accent); font-size:16px; }
.vs-link{ color:var(--accent); font-weight:600; }
</style>
<script>
(function(){
    var f = document.getElementById('vendorForm'); if(!f) return;
    var fn = f.querySelector('[name=first_name]'), ln = f.querySelector('[name=last_name]'),
        em = f.querySelector('[name=email]'), act = f.querySelector('[name=is_active]');
    var nameEl = document.getElementById('pvName'), emailEl = document.getElementById('pvEmail'),
        avEl = document.getElementById('pvAvatar'), stEl = document.getElementById('pvStatus');
    function sync(){
        var nm = ((fn.value||'') + ' ' + (ln && ln.value || '')).trim();
        nameEl.textContent = nm || 'Vendor name';
        emailEl.textContent = (em.value||'').trim() || 'email@example.com';
        if(avEl && avEl.classList.contains('vs-av-ph')){
            avEl.textContent = (nm || em.value || 'V').trim().charAt(0).toUpperCase();
        }
        if(stEl && act){
            var on = act.checked;
            stEl.textContent = on ? 'Active' : 'Inactive';
            stEl.className = 'pill ' + (on ? 'pill-on' : 'pill-off') + ' mt-2 d-inline-block';
        }
    }
    [fn, ln, em].forEach(function(el){ if(el) el.addEventListener('input', sync); });
    if(act) act.addEventListener('change', sync);
})();
</script>
