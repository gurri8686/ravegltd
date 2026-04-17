
@extends('layouts.login')



@section('content')

<form method="POST" action="{{ route('password.email') }}">
@csrf

    <h2 class="text-center"><strong>Reset Password</strong></h2>
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif
    <div class="input-group form-group">
        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="Enter Email" required autocomplete="email" autofocus>

        @error('email')
            <span class="invalid-feedback feedback-error" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </div>


    <div class="form-group">
        <button class="btn btn-success btn-block btn-info" type="submit"><i class="fas fa-unlock"></i>&nbsp;   {{ __('Send Password Reset Link') }}</button>
    </div>
</form>


@endsection
