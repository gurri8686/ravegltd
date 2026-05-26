@extends('admin.layout')

@section('title', 'My Account')

@section('content')
<div class="row g-3" style="max-width:980px;">
    {{-- Profile --}}
    <div class="col-lg-7">
        <div class="panel h-100">
            <div class="panel-head"><h6 class="mb-0 fw-semibold"><i class="bi bi-person-gear me-1"></i> Profile</h6></div>
            <div class="p-4">
                <form method="POST" action="{{ route('admin.account.update') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="d-flex align-items-center gap-3 mb-4">
                        @if ($user->image && \Illuminate\Support\Str::startsWith($user->image, 'uploads/'))
                            <img id="avatarPreview" src="{{ asset($user->image) }}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
                        @else
                            <div id="avatarPreview" class="av" style="width:72px;height:72px;font-size:26px;">{{ strtoupper(substr($user->first_name ?: $user->email, 0, 1)) }}</div>
                        @endif
                        <div>
                            <label class="form-label fw-semibold mb-1">Profile photo</label>
                            <input type="file" name="image" accept="image/*" class="form-control form-control-sm" style="max-width:260px;" onchange="saPreview(this)">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">First name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Last name</label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Username</label>
                            <input type="text" name="username" class="form-control" value="{{ old('username', $user->username) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                        </div>
                    </div>
                    <div class="mt-4"><button class="btn btn-accent"><i class="bi bi-check-lg"></i> Save Profile</button></div>
                </form>
            </div>
        </div>
    </div>

    {{-- Change password --}}
    <div class="col-lg-5">
        <div class="panel h-100">
            <div class="panel-head"><h6 class="mb-0 fw-semibold"><i class="bi bi-shield-lock me-1"></i> Change Password</h6></div>
            <div class="p-4">
                <form method="POST" action="{{ route('admin.account.password') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current password <span class="text-danger">*</span></label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm new password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" minlength="6" required>
                    </div>
                    <button class="btn btn-accent"><i class="bi bi-key"></i> Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function saPreview(input){
    if(!input.files || !input.files[0]) return;
    var img = document.createElement('img');
    img.id = 'avatarPreview'; img.src = URL.createObjectURL(input.files[0]);
    img.style.cssText = 'width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid var(--border);';
    document.getElementById('avatarPreview').replaceWith(img);
}
</script>
@endsection
