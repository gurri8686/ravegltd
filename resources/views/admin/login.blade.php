<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Superadmin — Sign in</title>
    <link rel="shortcut icon" type="image/x-icon" href="/app-assets/images/ico/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script>(function(){var t=localStorage.getItem('sa-theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
    <style>
        :root, [data-theme="light"]{
            --accent:#8b5cf6; --accent-dark:#7c3aed; --accent-soft:rgba(139,92,246,.14);
            --bg:#f3f4f6; --surface:#ffffff; --surface-2:#f8fafc;
            --text:#111827; --muted:#6b7280; --border:#e5e7eb;
            --input-bg:#ffffff; --input-border:#d1d5db; --input-text:#111827;
            --shadow:0 12px 40px rgba(17,24,39,.10);
        }
        [data-theme="dark"]{
            --accent:#8b5cf6; --accent-dark:#7c3aed; --accent-soft:rgba(139,92,246,.22);
            --bg:#0b0e16; --surface:#1a1d27; --surface-2:#21242f;
            --text:#e5e7eb; --muted:#9ca3af; --border:#262a36;
            --input-bg:#1f2330; --input-border:#374151; --input-text:#e5e7eb;
            --shadow:0 12px 50px rgba(0,0,0,.55);
        }
        *{ box-sizing:border-box; }
        body{ margin:0; min-height:100vh; background:var(--bg); color:var(--text);
              font-family:'Segoe UI',system-ui,-apple-system,sans-serif;
              display:flex; align-items:center; justify-content:center; padding:24px;
              position:relative; overflow:hidden; }
        /* ambient violet glow so it never reads like the orange app login */
        body::before{ content:""; position:absolute; inset:0; z-index:0;
              background:
                radial-gradient(620px 420px at 12% 8%, var(--accent-soft), transparent 60%),
                radial-gradient(560px 420px at 92% 96%, rgba(124,58,237,.18), transparent 60%); }

        .sa-login{ position:relative; z-index:1; width:100%; max-width:404px; }
        .sa-card{ background:var(--surface); border:1px solid var(--border); border-radius:20px;
                  box-shadow:var(--shadow); padding:36px 34px 32px; }
        .sa-logo{ width:58px; height:58px; border-radius:16px; margin:0 auto 16px;
                  background:linear-gradient(135deg,var(--accent),var(--accent-dark));
                  display:flex; align-items:center; justify-content:center; color:#fff; font-size:26px;
                  box-shadow:0 8px 22px rgba(139,92,246,.45); }
        .sa-title{ text-align:center; font-size:21px; font-weight:700; margin:0; color:var(--text); }
        .sa-sub{ text-align:center; color:var(--muted); font-size:13px; margin:4px 0 0; }
        .sa-badge{ display:block; width:max-content; margin:14px auto 22px; background:var(--accent-soft);
                   color:var(--accent); font-size:10.5px; font-weight:700; letter-spacing:.14em;
                   padding:5px 14px; border-radius:999px; text-transform:uppercase; }

        .fld{ margin-bottom:16px; }
        .fld-label{ font-size:12px; font-weight:600; color:var(--muted); margin-bottom:6px; display:block;
                    text-transform:uppercase; letter-spacing:.04em; }
        .fld-wrap{ position:relative; }
        .fld-wrap > .ico{ position:absolute; left:14px; top:50%; transform:translateY(-50%);
                          color:var(--muted); font-size:16px; pointer-events:none; }
        .fld-wrap .form-control{ background:var(--input-bg); border:1px solid var(--input-border); color:var(--input-text);
                          height:48px; border-radius:12px; padding-left:42px; font-size:14.5px; }
        .fld-wrap .form-control:focus{ border-color:var(--accent); box-shadow:0 0 0 .22rem var(--accent-soft);
                          background:var(--input-bg); color:var(--input-text); }
        .fld-wrap .form-control::placeholder{ color:var(--muted); opacity:.8; }
        .pw-eye{ position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer;
                 color:var(--muted); font-size:15px; user-select:none; }
        .pw-eye:hover{ color:var(--accent); }

        .sa-row{ display:flex; align-items:center; justify-content:space-between; margin:4px 0 22px; }
        .sa-row label{ font-size:13px; color:var(--muted); display:flex; align-items:center; gap:7px; cursor:pointer; margin:0; }
        .form-check-input{ background-color:var(--input-bg); border-color:var(--input-border); margin:0; cursor:pointer; }
        .form-check-input:checked{ background-color:var(--accent); border-color:var(--accent); }
        .form-check-input:focus{ border-color:var(--accent); box-shadow:0 0 0 .2rem var(--accent-soft); }

        .btn-login{ width:100%; height:48px; border:0; border-radius:12px; font-weight:600; font-size:15px; color:#fff;
                    background:linear-gradient(135deg,var(--accent),var(--accent-dark));
                    display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:.15s; }
        .btn-login:hover{ filter:brightness(1.06); box-shadow:0 8px 22px rgba(139,92,246,.4); }

        .sa-err{ background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.35); color:#ef4444;
                 border-radius:11px; padding:11px 14px; font-size:13px; margin-bottom:18px;
                 display:flex; align-items:center; gap:9px; }
        .sa-foot{ text-align:center; color:var(--muted); font-size:12px; margin-top:20px; }

        .theme-toggle{ position:absolute; top:18px; right:18px; z-index:2; width:40px; height:40px; border-radius:11px;
                       border:1px solid var(--border); background:var(--surface); color:var(--text);
                       display:inline-flex; align-items:center; justify-content:center; cursor:pointer; font-size:17px; }
        .theme-toggle:hover{ border-color:var(--accent); color:var(--accent); }
    </style>
</head>
<body>
    <button type="button" class="theme-toggle" id="themeToggle" title="Toggle theme"><i class="bi bi-moon-stars"></i></button>

    <div class="sa-login">
        <div class="sa-card">
            <div class="sa-logo"><i class="bi bi-shield-lock-fill"></i></div>
            <h1 class="sa-title">Superadmin</h1>
            <p class="sa-sub">Sign in to manage vendors & subscriptions</p>
            <span class="sa-badge">Control Panel</span>

            @if ($errors->any())
                <div class="sa-err"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $errors->first() }}</span></div>
            @endif

            <form method="POST" action="{{ route('admin.login.post') }}">
                @csrf
                <div class="fld">
                    <label class="fld-label" for="email">Email or username</label>
                    <div class="fld-wrap">
                        <i class="bi bi-person ico"></i>
                        <input type="text" name="email" id="email" class="form-control"
                               value="{{ old('email') }}" placeholder="superadmin@example.com" required autofocus>
                    </div>
                </div>

                <div class="fld">
                    <label class="fld-label" for="password">Password</label>
                    <div class="fld-wrap">
                        <i class="bi bi-key ico"></i>
                        <input type="password" name="password" id="password" class="form-control"
                               placeholder="Enter password" required style="padding-right:42px;">
                        <span class="pw-eye" onclick="togglePw('password',this)"><i class="bi bi-eye-slash"></i></span>
                    </div>
                </div>

                <div class="sa-row">
                    <label><input type="checkbox" name="remember" class="form-check-input"> Remember me</label>
                </div>

                <button type="submit" class="btn-login"><i class="bi bi-box-arrow-in-right"></i> Sign in</button>
            </form>

            <div class="sa-foot">Restricted area · authorized superadmins only</div>
        </div>
    </div>

    <script>
        function togglePw(id, el){
            var input = document.getElementById(id), icon = el.querySelector('i');
            if (input.type === 'password'){ input.type = 'text'; icon.className = 'bi bi-eye'; }
            else { input.type = 'password'; icon.className = 'bi bi-eye-slash'; }
        }
        (function(){
            var btn = document.getElementById('themeToggle');
            function paint(){ var d = document.documentElement.getAttribute('data-theme') === 'dark';
                btn.innerHTML = d ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>'; }
            paint();
            btn.addEventListener('click', function(){
                var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('sa-theme', next); paint();
            });
        })();
    </script>
</body>
</html>
