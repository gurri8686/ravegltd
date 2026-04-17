


@extends('layouts.login')



@section('content')
<style>
.pw-wrap{position:relative;display:block;}.pw-input{padding-right:42px!important;}.pw-eye{position:absolute;right:13px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;font-size:14px;transition:color 0.15s;user-select:none;}.pw-eye:hover{color:#f97316;}
</style>

<form method="POST" action="{{ route('password.update') }}">
@csrf
      <input type="hidden" name="token" value="{{ $token }}">
    <h2 class="text-center"><strong>Reset Password</strong></h2>
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif
    <div class="input-group form-group">
        <!-- <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="Enter Email" required autocomplete="email" autofocus> -->
        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>

        @error('email')
            <span class="invalid-feedback  feedback-error" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror

    </div>
    <div class="input-group form-group">
        <!-- <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="Enter Email" required autocomplete="email" autofocus> -->
        <div class="pw-wrap" style="width:100%;">
          <input id="password" type="password" class="form-control pw-input @error('password') is-invalid @enderror" name="password" placeholder="Enter Password" required autocomplete="new-password">
          <span class="pw-eye" onclick="togglePw('password',this)"><i class="fa fa-eye-slash"></i></span>
        </div>
          @error('password')
              <span class="invalid-feedback feedback-error" role="alert">
                  <strong>{{ $message }}</strong>
              </span>
          @enderror

    </div>
    <div class="input-group form-group">
        <!-- <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="Enter Email" required autocomplete="email" autofocus> -->
        <div class="pw-wrap" style="width:100%;">
          <input id="password-confirm" type="password" class="form-control pw-input" name="password_confirmation" placeholder="password-confirm" required autocomplete="new-password">
          <span class="pw-eye" onclick="togglePw('password-confirm',this)"><i class="fa fa-eye-slash"></i></span>
        </div>

    </div>


    <div class="form-group">
        <button class="btn btn-success btn-block btn-info" type="submit"><i class="fas fa-unlock"></i>&nbsp;    {{ __('Reset Password') }}</button>
    </div>
</form>
<script>
function togglePw(fieldId,btn){var input=document.getElementById(fieldId);var icon=btn.querySelector('i');if(input.type==='password'){input.type='text';icon.className='fa fa-eye';btn.style.color='#f97316';}else{input.type='password';icon.className='fa fa-eye-slash';btn.style.color='';}}
</script>

@endsection
