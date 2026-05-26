@extends('layouts.login')

@section('content')
<style>
.pw-wrap{position:relative;display:block;}.pw-input{padding-right:42px!important;}.pw-eye{position:absolute;right:13px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;font-size:14px;transition:color 0.15s;user-select:none;}.pw-eye:hover{color:#f97316;}

/* Mobile-only login heading + label (hidden on desktop/tablet) */
.mlogin-heading { display: none; }
.mlogin-label { display: none; }

@media (max-width: 767px) {
    /* ── Single login card only — inner card-body stays transparent so the
       outer .card frame is the only visible card. Body background is left
       untouched so the original page background stays. ── */
    .card-body.mt-0.pt-0 { background: transparent !important; border: none !important; box-shadow: none !important; border-radius: 0 !important; padding: 22px 22px 24px !important; }
    /* Hide the shared logo + "Login" subtitle on mobile so the new card heading is the only header */
    .card .card-header { display: none !important; }
    .card-content { padding-top: 0 !important; margin-top: 0 !important; }

    /* ── Welcome back + Sign in to continue ── */
    .mlogin-heading { display: block !important; text-align: center; margin: 0 0 22px; }
    .mlogin-heading h2 { font-size: 22px; font-weight: 800; color: #0f1115; margin: 0 0 4px; line-height: 1.2; }
    .mlogin-heading p { font-size: 13.5px; color: #6b7280; margin: 0; font-weight: 500; }

    /* ── Field labels (EMAIL OR USERNAME / PASSWORD) ── */
    .mlogin-label { display: block !important; font-size: 11px; font-weight: 700; color: #6b7280; letter-spacing: 0.6px; text-transform: uppercase; margin: 0 0 6px; }

    /* ── Form group spacing + input styling ── */
    .card-body.mt-0.pt-0 .form-group { margin-bottom: 16px !important; }
    .card-body.mt-0.pt-0 .form-control { height: 46px !important; border-radius: 12px !important; border: 1.5px solid #e5e7eb !important; background: #fff !important; font-size: 14px !important; color: #0f1115 !important; padding-left: 42px !important; box-shadow: none !important; }
    .card-body.mt-0.pt-0 .form-control:focus { border-color: rgb(234, 88, 12) !important; box-shadow: 0 0 0 3px rgba(234,88,12,0.10) !important; background: #fff !important; }
    /* Kill browser autofill yellow/blue background — keep white */
    .card-body.mt-0.pt-0 .form-control:-webkit-autofill,
    .card-body.mt-0.pt-0 .form-control:-webkit-autofill:hover,
    .card-body.mt-0.pt-0 .form-control:-webkit-autofill:focus,
    .card-body.mt-0.pt-0 .form-control:-webkit-autofill:active {
        -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
        box-shadow: 0 0 0 1000px #fff inset !important;
        -webkit-text-fill-color: #0f1115 !important;
        caret-color: #0f1115 !important;
        background-color: #fff !important;
        background-image: none !important;
        transition: background-color 9999s ease-in-out 0s !important;
    }
    .card-body.mt-0.pt-0 .form-control-position { width: 42px !important; height: 46px !important; line-height: 46px !important; color: rgb(234, 88, 12) !important; text-align: center !important; }
    .card-body.mt-0.pt-0 .form-control-position i { font-size: 15px !important; }
    .card-body.mt-0.pt-0 .pw-eye { right: 14px !important; color: #9ca3af !important; font-size: 16px !important; }

    /* ── Remember me + Forgot password row ── */
    .login-remember-row { display: flex !important; align-items: center !important; justify-content: space-between !important; margin: 0 0 18px !important; gap: 8px !important; flex-wrap: nowrap !important; min-height: 22px !important; }
    .login-remember-row .login-remember-cell { flex: 0 0 auto !important; width: auto !important; max-width: none !important; padding: 0 !important; text-align: left !important; display: inline-flex !important; align-items: center !important; }
    .login-remember-row .login-forgot-cell { flex: 0 0 auto !important; width: auto !important; max-width: none !important; padding: 0 !important; text-align: right !important; display: inline-flex !important; align-items: center !important; line-height: 1 !important; }
    .login-remember-row fieldset { display: inline-flex !important; align-items: center !important; gap: 8px !important; margin: 0 !important; padding: 0 !important; border: 0 !important; line-height: 1 !important; }
    .login-remember-row .chk-remember { appearance: none !important; -webkit-appearance: none !important; width: 18px !important; height: 18px !important; border-radius: 4px !important; border: 1.5px solid #d1d5db !important; background: #fff !important; cursor: pointer !important; position: relative !important; margin: 0 !important; flex-shrink: 0 !important; vertical-align: middle !important; }
    .login-remember-row .chk-remember:checked { background: rgb(234, 88, 12) !important; border-color: rgb(234, 88, 12) !important; }
    .login-remember-row .chk-remember:checked::after { content: '\2713'; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); color: #fff; font-size: 13px; font-weight: 800; line-height: 1; }
    .login-remember-row label[for="remember-me"] { margin: 0 !important; padding: 0 !important; font-size: 13px !important; font-weight: 600 !important; color: #374151 !important; line-height: 1 !important; cursor: pointer !important; text-transform: none !important; letter-spacing: 0 !important; font-family: inherit !important; display: inline-flex !important; align-items: center !important; vertical-align: middle !important; }
    .login-remember-row .card-link { margin: 0 !important; padding: 0 !important; font-size: 13px !important; font-weight: 600 !important; color: rgb(234, 88, 12) !important; white-space: nowrap !important; text-decoration: none !important; line-height: 1 !important; text-transform: none !important; letter-spacing: 0 !important; font-family: inherit !important; display: inline-flex !important; align-items: center !important; vertical-align: middle !important; }

    /* ── Password key icon → lock (visual swap only) ── */
    .card-body.mt-0.pt-0 .form-control-position .fa-key { font-size: 0 !important; width: 16px; display: inline-block; position: relative; }
    .card-body.mt-0.pt-0 .form-control-position .fa-key::before { content: '\f023' !important; font-size: 15px !important; }

    /* ── Sign In button (was "Login") ── */
    .card-body.mt-0.pt-0 .btn-outline-primary { height: 50px !important; border-radius: 12px !important; background: rgb(234, 88, 12) !important; border: none !important; color: #fff !important; font-size: 15px !important; font-weight: 800 !important; box-shadow: 0 6px 16px rgba(234,88,12,0.30) !important; display: flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; letter-spacing: 0.2px !important; }
    .card-body.mt-0.pt-0 .btn-outline-primary .mlogin-btn-icon { font-size: 0 !important; width: 16px; display: inline-block; position: relative; }
    .card-body.mt-0.pt-0 .btn-outline-primary .mlogin-btn-icon::before { font-family: 'FontAwesome' !important; content: '\f023' !important; font-size: 14px !important; }
    .card-body.mt-0.pt-0 .btn-outline-primary .mlogin-btn-label { font-size: 0 !important; }
    .card-body.mt-0.pt-0 .btn-outline-primary .mlogin-btn-label::before { content: 'Sign In'; font-size: 15px; font-weight: 800; }
}
</style>
<div class="card-body mt-0 pt-0">
            <div class="mlogin-heading">
                <h2>Welcome back</h2>
                <p>Sign in to continue</p>
            </div>
            <form method="POST" action="{{ route('login') }}">
            @csrf
            <label class="mlogin-label" for="user-name">Email or Username</label>
            <fieldset class="form-group position-relative has-icon-left">
                <input type="text" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" id="user-name" placeholder="Email / Username"
                    required>
                <div class="form-control-position">
                    <i class="feather icon-user"></i>
                </div>
            </fieldset>
            @error('email')
                <span class="invalid-feedback" style="" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
            <label class="mlogin-label" for="user-password">Password</label>
            <fieldset class="form-group position-relative has-icon-left">
                <input type="password" class="form-control pw-input @error('password') is-invalid @enderror" id="user-password" name="password"
                    placeholder="Enter Password" required>
                <div class="form-control-position">
                    <i class="fa fa-key"></i>
                </div>
                <span class="pw-eye" onclick="togglePw('user-password',this)"><i class="fa fa-eye-slash"></i></span>
            </fieldset>
            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
            <div class="form-group row login-remember-row">
                <div class="col-sm-6 col-12 text-center text-sm-left pr-0 login-remember-cell">
                    <fieldset>
                        <input type="checkbox" id="remember-me" class="chk-remember">
                        <label for="remember-me"> Remember Me</label>
                    </fieldset>
                </div>
                <div class="col-sm-6 col-12 float-sm-left text-center text-sm-right login-forgot-cell"><a
                        href="{{ route('password.request') }}" class="card-link">Forgot Password?</a></div>
            </div>
            <button type="submit" class="btn btn-outline-primary btn-block"><i
                    class="feather icon-unlock mlogin-btn-icon"></i><span class="mlogin-btn-label"> Login</span></button>
        </form>
</div>
<script>
function togglePw(fieldId,btn){var input=document.getElementById(fieldId);var icon=btn.querySelector('i');if(input.type==='password'){input.type='text';icon.className='fa fa-eye';btn.style.color='#f97316';}else{input.type='password';icon.className='fa fa-eye-slash';btn.style.color='';}}
</script>
@endsection
