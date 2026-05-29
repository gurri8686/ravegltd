@extends('admin.layout')

@section('title', 'Settings')
@section('subtitle', 'Platform configuration')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
    @csrf
    <div class="row g-3">
        {{-- Tab nav --}}
        <div class="col-12 col-lg-3">
            <div class="panel p-2 set-nav">
                @foreach ($tabs as $key => $tab)
                    <button type="button" class="set-tab {{ $loop->first ? 'active' : '' }}" data-tab="{{ $key }}">
                        <i class="bi {{ $tab['icon'] }}"></i> {{ $tab['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Tab panels --}}
        <div class="col-12 col-lg-9">
            @foreach ($tabs as $key => $tab)
                <div class="panel p-4 set-panel" id="panel-{{ $key }}" style="{{ $loop->first ? '' : 'display:none;' }}">
                    <div class="vf-section"><i class="bi {{ $tab['icon'] }}"></i> {{ $tab['label'] }}</div>
                    <div class="row g-3 mt-1">
                        @foreach ($tab['fields'] as $fkey => $def)
                            @php $val = $values[$fkey] ?? ''; @endphp
                            <div class="col-md-6">
                                <label class="vf-label">{{ $def['label'] }}</label>
                                @if ($def['type'] === 'select')
                                    <select name="{{ $fkey }}" class="form-control">
                                        @foreach ($def['options'] as $ov => $ol)
                                            <option value="{{ $ov }}" {{ (string) $val === (string) $ov ? 'selected' : '' }}>{{ $ol }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($def['type'] === 'color')
                                    <input type="color" name="{{ $fkey }}" class="form-control form-control-color" value="{{ $val ?: '#8b5cf6' }}" style="height:44px;width:80px;">
                                @elseif ($def['type'] === 'toggle')
                                    <div class="form-check form-switch mt-2"><input type="checkbox" name="{{ $fkey }}" value="1" class="form-check-input" {{ $val ? 'checked' : '' }}></div>
                                @elseif ($def['type'] === 'file')
                                    @if ($val && \Illuminate\Support\Str::startsWith($val, 'uploads/'))
                                        <div class="mb-2"><img src="{{ asset($val) }}" style="height:42px;border-radius:8px;border:1px solid var(--border);background:var(--surface-2);padding:3px;"></div>
                                    @endif
                                    <input type="file" name="{{ $fkey }}" class="form-control" accept="image/*">
                                @elseif ($def['type'] === 'password')
                                    <input type="password" name="{{ $fkey }}" class="form-control" placeholder="{{ $val ? '•••••••• (set — leave blank to keep)' : '' }}" autocomplete="new-password">
                                @else
                                    <input type="{{ $def['type'] }}" name="{{ $fkey }}" class="form-control" value="{{ $val }}" placeholder="{{ $def['placeholder'] ?? '' }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if ($key === 'payments' || $key === 'email')
                        <div class="text-muted small mt-3"><i class="bi bi-info-circle me-1"></i>Stored securely for when the {{ $key === 'payments' ? 'payment gateway' : 'mailer' }} integration is enabled.</div>
                    @endif
                </div>
            @endforeach
            <div class="mt-3"><button type="submit" class="btn-newvendor"><i class="bi bi-check-lg"></i> Save Settings</button></div>
        </div>
    </div>
</form>

<style>
.vf-section{ display:flex; align-items:center; gap:8px; font-size:12px; text-transform:uppercase; letter-spacing:.07em; color:var(--accent); font-weight:700; padding-bottom:9px; border-bottom:2px solid var(--accent-soft); }
.vf-label{ font-size:12.5px; font-weight:600; color:var(--text); margin-bottom:5px; }
.form-control{ min-height:44px; background:var(--input-bg); border-color:var(--input-border); color:var(--input-text); }
.set-nav{ display:flex; flex-direction:column; gap:4px; }
.set-tab{ display:flex; align-items:center; gap:10px; text-align:left; border:0; background:transparent; color:var(--side-text); border-radius:10px; padding:11px 14px; font-size:14px; font-weight:500; cursor:pointer; transition:.15s; }
.set-tab i{ font-size:16px; }
.set-tab:hover{ background:var(--surface-2); color:var(--text); }
.set-tab.active{ background:var(--accent); color:#fff; }
</style>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.set-tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.set-tab').forEach(function (b) { b.classList.remove('active'); });
        document.querySelectorAll('.set-panel').forEach(function (p) { p.style.display = 'none'; });
        btn.classList.add('active');
        document.getElementById('panel-' + btn.dataset.tab).style.display = '';
    });
});
</script>
@endsection
