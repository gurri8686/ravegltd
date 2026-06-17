


@extends('layouts.login')



@section('content')
<style>
.pw-wrap{position:relative;display:block;}.pw-input{padding-right:42px!important;}.pw-eye{position:absolute;right:13px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;font-size:14px;transition:color 0.15s;user-select:none;}.pw-eye:hover{color:#f97316;}
</style>

<form id="resetForm" method="POST" action="{{ route('password.update') }}" onsubmit="return pwOnSubmit()">
@csrf
      <input type="hidden" name="token" value="{{ $token }}">
    <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin:0 0 8px;">
        <span style="width:40px;height:40px;border-radius:12px;background:#fff1e6;border:1px solid #fed7aa;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="feather icon-mail" style="font-size:18px;color:rgb(234, 88, 12);"></i>
        </span>
        <h2 style="margin:0;"><strong>Reset Password</strong></h2>
    </div>
    <p class="text-center" style="color:#6b7280;font-size:13.5px;margin:6px 0 18px;">Choose a new password for your account.</p>

    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    {{-- Email comes from the reset link; kept hidden so it submits for validation but
         isn't shown/edited on screen (matches how Google/GitHub reset pages work). --}}
    <input id="email" type="hidden" name="email" value="{{ $email ?? old('email') }}">
    @error('email')
        <div class="invalid-feedback feedback-error d-block text-center" role="alert" style="margin-bottom:12px;">
            <strong>{{ $message }}</strong>
        </div>
    @enderror

    <label for="password" style="display:block;font-size:11px;font-weight:700;color:#6b7280;letter-spacing:0.6px;text-transform:uppercase;margin:0 0 6px;">New Password</label>
    <fieldset class="form-group position-relative has-icon-left">
        <div class="pw-wrap" style="width:100%;">
          <input id="password" type="password" class="form-control pw-input @error('password') is-invalid @enderror" name="password" placeholder="Enter new password" required autocomplete="new-password" autofocus oninput="pwOnInput()">
          <div class="form-control-position"><i class="fa fa-key"></i></div>
          <span class="pw-eye" onclick="togglePw('password',this)"><i class="fa fa-eye-slash"></i></span>
        </div>
        @error('password')
            <span class="invalid-feedback feedback-error" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </fieldset>

    {{-- Password strength meter --}}
    <div id="pw-strength" style="margin:-4px 0 16px;display:none;">
        <div style="display:flex;gap:6px;margin-bottom:6px;">
            <span class="pw-bar" style="flex:1;height:5px;border-radius:99px;background:#e5e7eb;transition:background 0.2s;"></span>
            <span class="pw-bar" style="flex:1;height:5px;border-radius:99px;background:#e5e7eb;transition:background 0.2s;"></span>
            <span class="pw-bar" style="flex:1;height:5px;border-radius:99px;background:#e5e7eb;transition:background 0.2s;"></span>
            <span class="pw-bar" style="flex:1;height:5px;border-radius:99px;background:#e5e7eb;transition:background 0.2s;"></span>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:12px;color:#9ca3af;font-weight:600;">Password strength</span>
            <span id="pw-strength-label" style="font-size:12px;font-weight:800;"></span>
        </div>
    </div>

    <label for="password-confirm" style="display:block;font-size:11px;font-weight:700;color:#6b7280;letter-spacing:0.6px;text-transform:uppercase;margin:0 0 6px;">Confirm Password</label>
    <fieldset class="form-group position-relative has-icon-left" style="margin-bottom:8px;">
        <div class="pw-wrap" style="width:100%;">
          <input id="password-confirm" type="password" class="form-control pw-input" name="password_confirmation" placeholder="Re-enter new password" required autocomplete="new-password" oninput="pwOnInput()">
          <div class="form-control-position"><i class="fa fa-key"></i></div>
          <span class="pw-eye" onclick="togglePw('password-confirm',this)"><i class="fa fa-eye-slash"></i></span>
        </div>
    </fieldset>
    {{-- Match feedback --}}
    <div id="pw-match" style="display:none;align-items:center;gap:6px;font-size:12.5px;font-weight:700;margin:-4px 0 14px;">
        <i id="pw-match-icon" class="feather" style="font-size:14px;"></i>
        <span id="pw-match-text"></span>
    </div>
    <div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:#9ca3af;margin:0 0 18px;">
        <i class="feather icon-shield" style="font-size:12px;"></i>
        <span>Use at least 8 characters with a mix of letters &amp; numbers.</span>
    </div>

    <div class="form-group">
        <button id="resetBtn" class="btn btn-block" type="submit"
            style="height:52px;border:none;border-radius:14px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;font-size:15px;font-weight:700;letter-spacing:0.2px;display:flex;align-items:center;justify-content:center;gap:9px;box-shadow:0 10px 24px -6px rgba(234,88,12,0.55),0 4px 10px -4px rgba(234,88,12,0.4);transition:transform 0.12s,box-shadow 0.15s,background 0.25s;"
            onmouseover="this.style.boxShadow='0 14px 30px -6px rgba(234,88,12,0.65),0 6px 14px -4px rgba(234,88,12,0.5)';this.style.transform='translateY(-1px)';"
            onmouseout="this.style.boxShadow='0 10px 24px -6px rgba(234,88,12,0.55),0 4px 10px -4px rgba(234,88,12,0.4)';this.style.transform='none';">
            <i id="resetBtnIcon" class="feather icon-unlock" style="font-size:16px;"></i><span id="resetBtnText">{{ __('Reset Password') }}</span>
        </button>
    </div>

    <div class="text-center" style="margin-top:16px;">
        <a href="{{ route('login') }}" class="card-link" style="font-size:14px;font-weight:700;color:rgb(234, 88, 12);text-decoration:none;">
            <i class="feather icon-arrow-left" style="font-size:13px;"></i>&nbsp;Back to Login
        </a>
    </div>
</form>
<script>
function togglePw(fieldId,btn){var input=document.getElementById(fieldId);var icon=btn.querySelector('i');if(input.type==='password'){input.type='text';icon.className='fa fa-eye';btn.style.color='#f97316';}else{input.type='password';icon.className='fa fa-eye-slash';btn.style.color='';}}

// ── Password strength + confirm-match ──────────────────────────────
function pwScore(v){
    var s = 0;
    if (v.length >= 8) s++;
    if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;      // upper + lower
    if (/\d/.test(v)) s++;                             // a number
    if (/[^A-Za-z0-9]/.test(v)) s++;                   // a symbol
    if (v && s === 0) s = 1;                           // something typed → at least 1
    return Math.min(s, 4);
}
function pwOnInput(){
    var pw = document.getElementById('password');
    var cf = document.getElementById('password-confirm');
    var meter = document.getElementById('pw-strength');
    var bars = document.querySelectorAll('#pw-strength .pw-bar');
    var label = document.getElementById('pw-strength-label');
    var v = pw.value;

    // Strength meter
    if (v) {
        meter.style.display = 'block';
        var score = pwScore(v);
        var levels = [
            {n:'Weak',  c:'#dc2626'},
            {n:'Weak',  c:'#dc2626'},
            {n:'Fair',  c:'#d97706'},
            {n:'Good',  c:'#ea580c'},
            {n:'Strong',c:'#16a34a'}
        ];
        var lvl = levels[score];
        bars.forEach(function(b,i){ b.style.background = i < score ? lvl.c : '#e5e7eb'; });
        label.textContent = lvl.n;
        label.style.color = lvl.c;
    } else {
        meter.style.display = 'none';
    }

    // Confirm match
    var box = document.getElementById('pw-match');
    var icon = document.getElementById('pw-match-icon');
    var text = document.getElementById('pw-match-text');
    if (cf.value) {
        box.style.display = 'flex';
        if (cf.value === v) {
            icon.className = 'feather icon-check-circle'; icon.style.color = '#16a34a';
            text.textContent = 'Passwords match'; box.style.color = '#16a34a';
        } else {
            icon.className = 'feather icon-x-circle'; icon.style.color = '#dc2626';
            text.textContent = "Passwords don't match yet"; box.style.color = '#dc2626';
        }
    } else {
        box.style.display = 'none';
    }
}

// On submit: if passwords match, show the green "Password Updated" confirmation
// (the form still posts to the server for the real reset).
function pwOnSubmit(){
    var pw = document.getElementById('password').value;
    var cf = document.getElementById('password-confirm').value;
    if (pw !== cf) {
        pwOnInput();
        document.getElementById('password-confirm').focus();
        return false;
    }
    var btn = document.getElementById('resetBtn');
    document.getElementById('resetBtnIcon').className = 'feather icon-check';
    document.getElementById('resetBtnText').textContent = 'Password Updated';
    btn.style.background = 'linear-gradient(135deg,#22c55e,#16a34a)';
    btn.style.boxShadow = '0 10px 24px -6px rgba(22,163,74,0.5)';
    return true; // let the form submit
}
</script>

@endsection
