@php
    $platformName = 'R & A Veg';
    $accentColor  = null;
    $platformLogo = null;
    $platformFav  = null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $platformName }} — @yield('title', 'Dashboard')</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ $platformFav && \Illuminate\Support\Str::startsWith($platformFav, 'uploads/') ? asset($platformFav) : '/app-assets/images/ico/favicon.ico' }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    {{-- set theme before paint to avoid flash; default = light (white) --}}
    <script>(function(){var t=localStorage.getItem('sa-theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <style>
        /* ---- Theme tokens ---- */
        :root, [data-theme="light"]{
            --accent:#8b5cf6; --accent-dark:#7c3aed; --accent-soft:rgba(139,92,246,.14);
            --bg:#f3f4f6; --surface:#ffffff; --surface-2:#f8fafc;
            --text:#111827; --muted:#6b7280; --border:#e5e7eb;
            --side-bg:#ffffff; --side-strong:#0f172a; --side-text:#475569; --side-hover:#f1f5f9; --side-border:#e5e7eb;
            --top-bg:#ffffff; --input-bg:#ffffff; --input-border:#d1d5db; --input-text:#111827;
            --shadow:0 1px 3px rgba(0,0,0,.07);
        }
        [data-theme="dark"]{
            --accent:#8b5cf6; --accent-dark:#7c3aed; --accent-soft:rgba(139,92,246,.22);
            --bg:#0f1117; --surface:#1a1d27; --surface-2:#21242f;
            --text:#e5e7eb; --muted:#9ca3af; --border:#262a36;
            --side-bg:#0b0e16; --side-strong:#ffffff; --side-text:#94a3b8; --side-hover:rgba(255,255,255,.06); --side-border:rgba(255,255,255,.06);
            --top-bg:#14161d; --input-bg:#1f2330; --input-border:#374151; --input-text:#e5e7eb;
            --shadow:0 1px 3px rgba(0,0,0,.4);
        }
        *{ box-sizing:border-box; }
        body{ margin:0; background:var(--bg); color:var(--text); font-family:'Segoe UI',system-ui,-apple-system,sans-serif; transition:background .2s,color .2s; }
        a{ text-decoration:none; }

        .sa-shell{ display:flex; min-height:100vh; }

        /* Sidebar */
        .sa-side{ width:248px; flex:0 0 248px; background:var(--side-bg); color:var(--side-text);
                  border-right:1px solid var(--side-border); display:flex; flex-direction:column; position:sticky; top:0; height:100vh; }
        .sa-brand{ padding:22px 20px; border-bottom:1px solid var(--side-border); flex:0 0 auto; }
        .sa-brand b{ color:var(--side-strong); font-size:18px; letter-spacing:.3px; }
        .sa-brand .tag{ display:inline-block; margin-top:6px; font-size:10px; letter-spacing:.04em; text-transform:uppercase;
                        background:var(--accent-soft); color:var(--accent); font-weight:700; padding:3px 10px; border-radius:999px; }
        /* nav scrolls on its own when items exceed height; brand + footer stay pinned */
        .sa-nav{ padding:14px 12px; flex:1 1 auto; min-height:0; overflow-y:auto; overscroll-behavior:contain; }
        .sa-nav::-webkit-scrollbar{ width:7px; }
        .sa-nav::-webkit-scrollbar-thumb{ background:var(--side-border); border-radius:8px; }
        .sa-nav::-webkit-scrollbar-thumb:hover{ background:var(--accent); }
        .sa-nav{ scrollbar-width:thin; scrollbar-color:var(--side-border) transparent; }
        .sa-nav a{ display:flex; align-items:center; gap:12px; padding:11px 14px; border-radius:10px;
                   color:var(--side-text); font-size:14px; font-weight:500; margin-bottom:4px; transition:.15s; }
        .sa-nav a i{ font-size:17px; }
        .sa-nav a:hover{ background:var(--side-hover); color:var(--side-strong); }
        .sa-nav a.active{ background:var(--accent); color:#fff; box-shadow:0 4px 12px rgba(139,92,246,.4); }
        .sa-side-foot{ padding:12px 14px; border-top:1px solid var(--side-border); flex:0 0 auto; }
        .sa-acct{ display:flex; align-items:center; gap:10px; }
        .sa-foot-av{ position:relative; width:34px; height:34px; flex:0 0 34px; border-radius:50%; color:#fff; font-weight:700; font-size:13px;
                     display:flex; align-items:center; justify-content:center;
                     background:linear-gradient(135deg,var(--accent),var(--accent-dark)); }
        .sa-dot{ position:absolute; right:-1px; bottom:-1px; width:10px; height:10px; border-radius:50%;
                 background:#10b981; border:2px solid var(--side-bg); }
        .sa-foot-meta{ min-width:0; flex:1; }
        .sa-foot-name{ color:var(--side-strong); font-weight:600; font-size:13px; line-height:1.2;
                       white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .sa-foot-role{ color:var(--accent); font-size:9.5px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; margin-top:1px; }
        .sa-logout{ width:34px; height:34px; flex:0 0 34px; display:inline-flex; align-items:center; justify-content:center; border:0;
                    background:var(--accent-soft); color:var(--accent); border-radius:9px; font-size:16px; transition:.15s; }
        .sa-logout:hover{ background:var(--accent); color:#fff; }

        /* Main */
        .sa-main{ flex:1; display:flex; flex-direction:column; min-width:0; }
        .sa-top{ min-height:68px; background:var(--top-bg); border-bottom:1px solid var(--border); display:flex; align-items:center;
                 padding:10px 26px; position:sticky; top:0; z-index:5; }
        .sa-top h1{ font-size:20px; font-weight:700; margin:0; color:var(--text); }
        .sa-top-sub{ font-size:12.5px; color:var(--muted); margin-top:1px; }
        .sa-user{ margin-left:auto; display:flex; align-items:center; gap:12px; }
        .btn-newvendor{ display:inline-flex; align-items:center; gap:7px; background:linear-gradient(135deg,var(--accent),var(--accent-dark));
                        color:#fff; border:0; border-radius:10px; padding:9px 16px; font-size:13.5px; font-weight:600;
                        box-shadow:0 4px 12px rgba(139,92,246,.32); transition:.15s; }
        .btn-newvendor:hover{ filter:brightness(1.06); box-shadow:0 6px 18px rgba(139,92,246,.45); color:#fff; }
        .sa-avatar{ width:36px; height:36px; border-radius:50%; background:var(--accent); color:#fff;
                    display:flex; align-items:center; justify-content:center; font-weight:600; font-size:14px; }
        .sa-user .email{ font-size:13px; color:var(--text); font-weight:500; }
        .theme-toggle{ width:38px; height:38px; border-radius:10px; border:1px solid var(--border); background:var(--surface);
                       color:var(--text); display:inline-flex; align-items:center; justify-content:center; cursor:pointer; font-size:16px; }
        .theme-toggle:hover{ border-color:var(--accent); color:var(--accent); }
        .sa-content{ padding:26px; }

        /* Cards / stats */
        .stat-card{ background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:18px 20px; box-shadow:var(--shadow);
                    display:flex; align-items:center; gap:16px; height:100%; }
        .stat-icon{ width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; }
        .si-neutral{ background:var(--accent-soft); color:var(--accent); }
        .si-green{ background:rgba(16,185,129,.16); color:#10b981; }
        .si-grey{ background:rgba(148,163,184,.18); color:#94a3b8; }
        .stat-card .label{ font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:.04em; }
        .stat-card .value{ font-size:24px; font-weight:700; line-height:1.1; color:var(--text); }

        .panel{ background:var(--surface); border:1px solid var(--border); border-radius:14px; box-shadow:var(--shadow); overflow:hidden; }
        .panel-head{ padding:16px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:14px; color:var(--text); }

        .table{ color:var(--text); margin:0; }
        .table thead th{ font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:var(--muted); border:0;
                         padding:12px 16px; background:var(--surface-2); }
        .table tbody td{ padding:14px 16px; vertical-align:middle; border-color:var(--border); color:var(--text); }
        .table tbody tr:hover{ background:var(--surface-2); }
        .av{ width:34px; height:34px; border-radius:50%; background:var(--accent-soft); color:var(--accent); display:inline-flex;
             align-items:center; justify-content:center; font-weight:600; font-size:13px; flex:0 0 auto; }
        .av-img{ width:36px; height:36px; border-radius:9px; object-fit:contain; background:var(--surface-2); border:1px solid var(--border); padding:2px; flex:0 0 auto; }
        .acct-name{ color:var(--text); }
        .acct-name:hover{ color:var(--accent); }
        .role-pill{ background:var(--accent-soft); color:var(--accent); text-transform:capitalize; }
        .pill{ font-size:11px; font-weight:600; padding:4px 11px; border-radius:999px; }
        .pill-on{ background:rgba(16,185,129,.16); color:#10b981; } .pill-off{ background:rgba(148,163,184,.18); color:#94a3b8; }
        .btn-icon{ width:34px; height:34px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:9px; }
        .btn-accent{ background:var(--accent); border-color:var(--accent); color:#fff; }
        .btn-accent:hover{ background:var(--accent-dark); border-color:var(--accent-dark); color:#fff; }
        .text-muted{ color:var(--muted) !important; }

        /* Form controls adapt to theme */
        .form-control{ background:var(--input-bg); border-color:var(--input-border); color:var(--input-text); }
        .form-control:focus{ background:var(--input-bg); color:var(--input-text); border-color:var(--accent); box-shadow:0 0 0 .2rem var(--accent-soft); }
        .form-control::placeholder{ color:var(--muted); }
        .form-label{ color:var(--text); }
        /* Toggles / checkboxes — theme color + consistent size everywhere */
        .form-check-input{ background-color:var(--input-bg); border-color:var(--input-border); }
        .form-check-input:checked{ background-color:var(--accent); border-color:var(--accent); }
        .form-check-input:focus{ border-color:var(--accent); box-shadow:0 0 0 .2rem var(--accent-soft); }
        .form-switch .form-check-input{ width:2.6em; height:1.35em; cursor:pointer; margin-top:.1em; }
        .form-switch .form-check-label{ margin-left:.4em; }
        [data-theme="dark"] .btn-light{ background:#262a36; border-color:#374151; color:#e5e7eb; }
        [data-theme="dark"] .btn-outline-secondary{ color:#cbd5e1; border-color:#374151; }
        [data-theme="dark"] .btn-outline-secondary:hover{ background:#262a36; color:#fff; }

        @media (max-width:768px){ .sa-side{ display:none; } }

        /* Toasts (top-right) */
        #saToasts{ position:fixed; top:18px; right:18px; z-index:1090; display:flex; flex-direction:column; gap:10px; }
        .sa-toast{ display:flex; align-items:center; gap:10px; min-width:240px; max-width:360px; background:var(--surface); color:var(--text);
                   border:1px solid var(--border); border-left:4px solid var(--accent); border-radius:12px; padding:12px 16px;
                   box-shadow:0 8px 24px rgba(0,0,0,.14); font-size:14px; transform:translateX(120%); opacity:0; transition:.28s ease; }
        .sa-toast.show{ transform:translateX(0); opacity:1; }
        .sa-toast.t-success{ border-left-color:#10b981; } .sa-toast.t-error{ border-left-color:#ef4444; } .sa-toast.t-info{ border-left-color:#6366f1; }
        .sa-toast i{ font-size:18px; }
    </style>
    @if ($accentColor)
    {{-- live accent colour from Settings --}}
    <style>
        :root, [data-theme="light"], [data-theme="dark"]{
            --accent: {{ $accentColor }}; --accent-dark: {{ $accentColor }}; --accent-soft: {{ $accentColor }}22;
        }
    </style>
    @endif
</head>
<body>
<div class="sa-shell">
    <aside class="sa-side">
        <div class="sa-brand">
            @if ($platformLogo && \Illuminate\Support\Str::startsWith($platformLogo, 'uploads/'))
                <img src="{{ asset($platformLogo) }}" alt="{{ $platformName }}" style="max-height:32px;max-width:160px;margin-bottom:4px;display:block;">
            @endif
            <b>{{ $platformName }}</b><br><span class="tag">Platform Control</span>
        </div>
        @php $can = fn ($s) => \App\Http\Controllers\Admin\AdminUserController::allows(optional(auth()->user())->role, $s); @endphp
        <nav class="sa-nav">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            @if ($can('vendors'))
            <a href="{{ route('admin.vendors.index') }}" class="{{ request()->routeIs('admin.vendors.*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i> Vendors
            </a>
            @endif
            @if ($can('plans'))
            <a href="{{ route('admin.plans.index') }}" class="{{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Plans
            </a>
            @endif
            @if ($can('notifications'))
            <a href="{{ route('admin.notifications.index') }}" class="{{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone"></i> Notifications
            </a>
            @endif
            @if ($can('audit'))
            <a href="{{ route('admin.audit.index') }}" class="{{ request()->routeIs('admin.audit.*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-data"></i> Audit Logs
            </a>
            @endif
            @if ($can('admin_users'))
            <a href="{{ route('admin.adminusers.index') }}" class="{{ request()->routeIs('admin.adminusers.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Admin Users
            </a>
            @endif
            <a href="{{ route('admin.account.edit') }}" class="{{ request()->routeIs('admin.account.*') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i> My Account
            </a>
        </nav>
        <div class="sa-side-foot">
            @php
                $saU = auth()->user();
                $saName = $saU ? (trim($saU->first_name . ' ' . $saU->last_name) ?: \Illuminate\Support\Str::before($saU->email, '@')) : 'Superadmin';
            @endphp
            <div class="sa-acct">
                <div class="sa-foot-av">{{ strtoupper(substr($saName, 0, 1)) }}<span class="sa-dot"></span></div>
                <div class="sa-foot-meta">
                    <div class="sa-foot-name">{{ $saName }}</div>
                    <div class="sa-foot-role">Superadmin</div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}" class="m-0">
                    @csrf
                    <button class="sa-logout" type="submit" title="Logout"><i class="bi bi-box-arrow-right"></i></button>
                </form>
            </div>
        </div>
    </aside>

    <div class="sa-main">
        <header class="sa-top">
            <div class="sa-top-title">
                <h1>@yield('title', 'Dashboard')</h1>
                @hasSection('subtitle')<div class="sa-top-sub">@yield('subtitle')</div>@endif
            </div>
            <div class="sa-user">
                <a href="{{ route('admin.vendors.create') }}" class="btn-newvendor"><i class="bi bi-plus-lg"></i> New Vendor</a>
                <button type="button" class="theme-toggle" onclick="saToggleTheme()" title="Toggle light / dark">
                    <i id="themeIcon" class="bi bi-moon-stars"></i>
                </button>
                <div class="sa-avatar">{{ strtoupper(substr(optional(auth()->user())->first_name ?: optional(auth()->user())->email, 0, 1)) }}</div>
            </div>
        </header>

        <main class="sa-content">
            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<div id="saToasts"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function saToast(message, type){
    type = type || 'success';
    var icons = { success:'bi-check-circle-fill', error:'bi-exclamation-triangle-fill', info:'bi-info-circle-fill' };
    var box = document.getElementById('saToasts');
    var el = document.createElement('div');
    el.className = 'sa-toast t-' + type;
    el.innerHTML = '<i class="bi ' + (icons[type] || icons.success) + '"></i><span></span>';
    el.querySelector('span').textContent = message;
    box.appendChild(el);
    requestAnimationFrame(function(){ el.classList.add('show'); });
    setTimeout(function(){ el.classList.remove('show'); setTimeout(function(){ el.remove(); }, 320); }, 3500);
}
@if (session('status'))
document.addEventListener('DOMContentLoaded', function(){ saToast(@json(session('status')), 'success'); });
@endif
function saSyncThemeIcon(){
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    var i = document.getElementById('themeIcon');
    if (i) i.className = dark ? 'bi bi-sun-fill' : 'bi bi-moon-stars';
}
function saToggleTheme(){
    var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('sa-theme', next);
    saSyncThemeIcon();
}
saSyncThemeIcon();
</script>
@yield('scripts')
</body>
</html>
