{{--
    Vendor-facing platform announcements banner.

    Read-only: shows the latest published announcements (from the control-plane
    organizations DB) to vendor-tier users, filtered by the audience they fall
    into. Dismissal is client-side (localStorage) so there is no per-user read
    table and no tenant-DB schema change. Wrapped in try/catch so any data issue
    (e.g. the table not yet migrated on a host) degrades to nothing rather than
    breaking the page for every vendor.
--}}
@php
    $vendorAnnouncements = collect();
    try {
        $u = auth()->user();
        if ($u && in_array($u->role ?? '', ['admin', 'vendor'], true)) {
            $isActive = (int) ($u->is_active ?? 1) === 1;
            $audiences = ['all', $isActive ? 'active' : 'inactive'];
            $vendorAnnouncements = \Illuminate\Support\Facades\DB::connection('organizations')
                ->table('platform_notifications')
                ->where('is_published', 1)
                ->whereIn('audience', $audiences)
                ->where('created_at', '>=', now()->subDays(60))
                ->orderByDesc('created_at')
                ->limit(3)
                ->get(['id', 'title', 'message', 'level', 'created_at']);
        }
    } catch (\Throwable $e) {
        $vendorAnnouncements = collect(); // never break the vendor app over a banner
    }

    // inline SVG paths (16×16 viewBox) so the banner needs no icon font — the
    // main vendor app does not load Bootstrap Icons.
    $annStyle = [
        'info'     => ['#3b82f6', 'M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z'],
        'success'  => ['#10b981', 'M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z'],
        'warning'  => ['#f59e0b', 'M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z'],
        'critical' => ['#ef4444', 'M11.46.146A.5.5 0 0 0 11.107 0H4.893a.5.5 0 0 0-.353.146L.146 4.54A.5.5 0 0 0 0 4.893v6.214a.5.5 0 0 0 .146.353l4.394 4.394a.5.5 0 0 0 .353.146h6.214a.5.5 0 0 0 .353-.146l4.394-4.394a.5.5 0 0 0 .146-.353V4.893a.5.5 0 0 0-.146-.353L11.46.146zM8 4c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995A.905.905 0 0 1 8 4zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z'],
    ];
@endphp

@if ($vendorAnnouncements->isNotEmpty())
<div id="vendorAnnounce" class="va-wrap mb-2">
    @foreach ($vendorAnnouncements as $a)
        @php [$color, $path] = $annStyle[$a->level] ?? $annStyle['info']; @endphp
        <div class="va-card" data-ann-id="{{ $a->id }}" style="--va-color:{{ $color }};">
            <span class="va-ic"><svg viewBox="0 0 16 16" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="{{ $path }}"/></svg></span>
            <div class="va-body">
                <div class="va-title">{{ $a->title }}</div>
                <div class="va-msg">{{ $a->message }}</div>
            </div>
            <button type="button" class="va-close" aria-label="Dismiss" title="Dismiss">
                <svg viewBox="0 0 16 16" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854z"/></svg>
            </button>
        </div>
    @endforeach
</div>

<style>
.va-wrap{ display:flex; flex-direction:column; gap:10px; }
.va-card{ display:flex; align-items:flex-start; gap:12px; background:#fff; border:1px solid #e9ecf3;
          border-left:4px solid var(--va-color); border-radius:12px; padding:13px 14px; box-shadow:0 2px 8px rgba(16,24,40,.05); }
.va-ic{ width:30px; height:30px; flex:0 0 30px; display:flex; align-items:center; justify-content:center;
        color:var(--va-color); margin-top:1px; }
.va-body{ flex:1; min-width:0; }
.va-title{ font-weight:700; font-size:14px; color:#1f2937; }
.va-msg{ font-size:13px; color:#6b7280; margin-top:2px; line-height:1.45; white-space:pre-line; }
.va-close{ border:0; background:transparent; color:#9aa1ad; font-size:14px; line-height:1; padding:4px 6px; border-radius:7px; flex:0 0 auto; }
.va-close:hover{ color:#ef4444; background:rgba(239,68,68,.1); }
</style>

<script>
(function () {
    var KEY = 'vendorAnnounceDismissed';
    var dismissed;
    try { dismissed = JSON.parse(localStorage.getItem(KEY) || '[]'); } catch (e) { dismissed = []; }
    if (!Array.isArray(dismissed)) dismissed = [];

    var wrap = document.getElementById('vendorAnnounce');
    if (!wrap) return;

    wrap.querySelectorAll('.va-card').forEach(function (card) {
        var id = card.getAttribute('data-ann-id');
        if (dismissed.indexOf(id) !== -1) { card.remove(); return; }
        var btn = card.querySelector('.va-close');
        if (btn) btn.addEventListener('click', function () {
            card.remove();
            if (dismissed.indexOf(id) === -1) dismissed.push(id);
            try { localStorage.setItem(KEY, JSON.stringify(dismissed)); } catch (e) {}
            if (!wrap.querySelector('.va-card')) wrap.remove();
        });
    });
    if (!wrap.querySelector('.va-card')) wrap.remove();
})();
</script>
@endif
