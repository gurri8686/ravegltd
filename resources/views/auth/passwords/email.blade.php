
@extends('layouts.login')

@section('content')

<style>
/* Email field focus — orange border + soft orange glow (matches the brand). */
.fp-form .form-control { border:1.5px solid #e5e7eb !important; border-radius:12px !important; box-shadow:none !important; transition:border-color 0.15s, box-shadow 0.15s !important; }
.fp-form .form-control:focus { border-color:rgb(234, 88, 12) !important; box-shadow:0 0 0 3px rgba(234,88,12,0.12) !important; background:#fff !important; }
.fp-form .has-icon-left:focus-within .form-control-position i { color:rgb(234, 88, 12) !important; }

/* Valid email — green border + soft green bg + a green check badge on the right. */
.fp-email .fp-valid-tick { position:absolute; right:12px; top:50%; transform:translateY(-50%); width:24px; height:24px; border-radius:50%; background:#16a34a; color:#fff; display:none; align-items:center; justify-content:center; z-index:2; box-shadow:0 2px 6px rgba(22,163,74,0.35); }
.fp-email .fp-valid-tick i { font-size:13px; }
/* Green valid state — only when there's NO server error on the field. */
.fp-email.is-valid:not(.has-error) .fp-valid-tick { display:inline-flex; }
.fp-email.is-valid:not(.has-error) .form-control { border-color:#16a34a !important; background:#f0fdf4 !important; box-shadow:0 0 0 3px rgba(22,163,74,0.10) !important; padding-right:42px !important; }
.fp-email.is-valid:not(.has-error) .form-control-position i { color:#16a34a !important; }
/* Server error (e.g. "no user with that email") → red state, never green. */
.fp-email.has-error .fp-valid-tick { display:none !important; }
.fp-email.has-error .form-control { border-color:#dc2626 !important; background:#fff !important; box-shadow:0 0 0 3px rgba(220,38,38,0.10) !important; }
.fp-email.has-error .form-control-position i { color:#dc2626 !important; }
.fp-email .feedback-error { display:block; color:#dc2626; font-size:12px; font-weight:600; margin-top:6px; }
</style>

@if (session('status'))
    {{-- ── Success state: link sent → "Check your inbox" ── --}}
    <div>
        <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin:0 0 8px;">
            <span style="width:40px;height:40px;border-radius:12px;background:#dcfce7;border:1px solid #bbf7d0;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="feather icon-mail" style="font-size:18px;color:#16a34a;"></i>
            </span>
            <h2 style="margin:0;"><strong>Check your inbox</strong></h2>
        </div>
        <p style="color:#6b7280;font-size:13.5px;line-height:1.55;margin:0 0 22px;">
            We've sent a password reset link{!! session('reset_email') ? ' to <strong style="color:#0f1115;">'.e(session('reset_email')).'</strong>' : '' !!}.<br>
            Follow the link to choose a new password.
        </p>

        <a href="{{ env('MAIL_INBOX_URL', 'https://mail.google.com') }}" target="_blank" rel="noopener" class="btn btn-block"
            style="height:52px;border:none;border-radius:14px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;font-size:15px;font-weight:700;letter-spacing:0.2px;display:flex;align-items:center;justify-content:center;gap:9px;text-decoration:none;box-shadow:0 10px 24px -6px rgba(234,88,12,0.55),0 4px 10px -4px rgba(234,88,12,0.4);transition:transform 0.12s,box-shadow 0.15s;"
            onmouseover="this.style.boxShadow='0 14px 30px -6px rgba(234,88,12,0.65),0 6px 14px -4px rgba(234,88,12,0.5)';this.style.transform='translateY(-1px)';"
            onmouseout="this.style.boxShadow='0 10px 24px -6px rgba(234,88,12,0.55),0 4px 10px -4px rgba(234,88,12,0.4)';this.style.transform='none';">
            <i class="feather icon-mail" style="font-size:16px;"></i>Open Email App
        </a>

        <hr style="border:none;border-top:1px solid #eef0f3;margin:18px 0 14px;">

        <div class="text-center" style="font-size:13px;color:#9ca3af;margin:0 0 12px;">
            Didn't get it? Check spam, or
            <form method="POST" action="{{ route('password.email') }}" style="display:inline;">
                @csrf
                <input type="hidden" name="email" value="{{ session('reset_email') ?? old('email') }}">
                <button type="submit" style="background:none;border:none;padding:0;color:rgb(234, 88, 12);font-weight:700;cursor:pointer;font-size:13px;">resend the link</button>
            </form>.
        </div>
        <div class="text-center">
            <a href="{{ route('login') }}" class="card-link" style="font-size:14px;font-weight:700;color:rgb(234, 88, 12);text-decoration:none;">
                <i class="feather icon-arrow-left" style="font-size:13px;"></i>&nbsp;Back to Login
            </a>
        </div>
    </div>
@else
<form class="fp-form" method="POST" action="{{ route('password.email') }}">
@csrf

    <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin:0 0 2px;">
        <span style="width:44px;height:44px;border-radius:13px;background:#fff1e6;border:1px solid #fed7aa;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="feather icon-mail" style="font-size:20px;color:rgb(234, 88, 12);"></i>
        </span>
        <h2 style="margin:0;"><strong>Reset Password</strong></h2>
    </div>
    <p class="text-center" style="color:#6b7280;font-size:13.5px;margin:6px 0 18px;">Enter your email and we'll send you a reset link.</p>

    <label for="email" style="display:block;font-size:11px;font-weight:700;color:#6b7280;letter-spacing:0.6px;text-transform:uppercase;margin:0 0 6px;">Email Address</label>
    <fieldset class="form-group position-relative has-icon-left fp-email @error('email') has-error @enderror" style="margin-bottom:8px;">
        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="you@company.com" required autocomplete="email" autofocus oninput="fpValidateEmail(this)">
        <div class="form-control-position">
            <i class="feather icon-mail"></i>
        </div>
        <span class="fp-valid-tick" aria-hidden="true">
            <i class="feather icon-check"></i>
        </span>
        @error('email')
            <span class="invalid-feedback feedback-error" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </fieldset>
    <div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:#9ca3af;margin:0 0 18px;">
        <i class="feather icon-lock" style="font-size:12px;"></i>
        <span>The link expires in 30 minutes for your security.</span>
    </div>

    <div class="form-group">
        <button class="btn btn-block" type="submit"
            style="height:52px;border:none;border-radius:14px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;font-size:15px;font-weight:700;letter-spacing:0.2px;display:flex;align-items:center;justify-content:center;gap:9px;box-shadow:0 10px 24px -6px rgba(234,88,12,0.55),0 4px 10px -4px rgba(234,88,12,0.4);transition:transform 0.12s,box-shadow 0.15s;"
            onmouseover="this.style.boxShadow='0 14px 30px -6px rgba(234,88,12,0.65),0 6px 14px -4px rgba(234,88,12,0.5)';this.style.transform='translateY(-1px)';"
            onmouseout="this.style.boxShadow='0 10px 24px -6px rgba(234,88,12,0.55),0 4px 10px -4px rgba(234,88,12,0.4)';this.style.transform='none';">
            <i class="feather icon-send" style="font-size:16px;"></i>{{ __('Send Password Reset Link') }}
        </button>
    </div>

    <div class="text-center" style="margin-top:16px;">
        <a href="{{ route('login') }}" class="card-link" style="font-size:14px;font-weight:700;color:rgb(234, 88, 12);text-decoration:none;">
            <i class="feather icon-arrow-left" style="font-size:13px;"></i>&nbsp;Back to Login
        </a>
    </div>

    <hr style="border:none;border-top:1px solid #eef0f3;margin:16px 0 14px;">

    <div class="text-center" style="font-size:13px;color:#9ca3af;">
        Don't have access to this email?
        <a href="mailto:support@ravegltd.com" style="color:rgb(234, 88, 12);font-weight:700;text-decoration:none;">Contact support</a>
    </div>
</form>
@endif

<script>
function fpValidateEmail(input){
    var wrap = input.closest('.fp-email');
    if(!wrap) return;
    // User started editing → clear the stale server error so green can show again.
    wrap.classList.remove('has-error');
    var ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim());
    wrap.classList.toggle('is-valid', ok);
}
// Run once on load (e.g. when the field is pre-filled with old input).
document.addEventListener('DOMContentLoaded', function(){
    var el = document.getElementById('email');
    if(!el) return;
    var wrap = el.closest('.fp-email');
    // If the server returned an error for this field, leave it in the red error state
    // (do NOT mark it valid/green even though the format looks fine).
    if(wrap && wrap.classList.contains('has-error')) return;
    if(el.value) fpValidateEmail(el);
});
</script>

@endsection
