@extends('layouts.login')

@section('content')
<style>
.pw-wrap{position:relative;display:block;}.pw-input{padding-right:42px!important;}.pw-eye{position:absolute;right:13px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;font-size:14px;transition:color 0.15s;user-select:none;}.pw-eye:hover{color:#f97316;}
</style>
<div class="card-body mt-0 pt-0">
            <form method="POST" action="{{ route('login') }}">
            @csrf
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
            <div class="form-group row">
                <div class="col-sm-6 col-12 text-center text-sm-left pr-0">
                    <fieldset>
                        <input type="checkbox" id="remember-me" class="chk-remember">
                        <label for="remember-me"> Remember Me</label>
                    </fieldset>
                </div>
                <div class="col-sm-6 col-12 float-sm-left text-center text-sm-right"><a
                        href="{{ route('password.request') }}" class="card-link">Forgot Password?</a></div>
            </div>
            <button type="submit" class="btn btn-outline-primary btn-block"><i
                    class="feather icon-unlock"></i> Login</button>
        </form>
</div>
<script>
function togglePw(fieldId,btn){var input=document.getElementById(fieldId);var icon=btn.querySelector('i');if(input.type==='password'){input.type='text';icon.className='fa fa-eye';btn.style.color='#f97316';}else{input.type='password';icon.className='fa fa-eye-slash';btn.style.color='';}}
</script>
@endsection
