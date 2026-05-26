@php $isEdit = isset($vendor) && $vendor; @endphp

{{-- Profile photo --}}
<div class="vf-photo mb-4">
    @if ($isEdit && $vendor->image && \Illuminate\Support\Str::startsWith($vendor->image, 'uploads/'))
        <img id="avatarPreview" class="vf-photo-img" src="{{ asset($vendor->image) }}">
    @else
        <div id="avatarPreview" class="vf-photo-img vf-photo-ph">{{ strtoupper(substr($isEdit ? ($vendor->first_name ?: $vendor->email) : 'V', 0, 1)) }}</div>
    @endif
    <div>
        <label class="vf-upload">
            <i class="bi bi-image"></i> <span>Upload logo</span>
            <input type="file" name="image" accept="image/*" class="d-none" onchange="saPreview(this)">
        </label>
        <div class="text-muted small mt-2">JPG / PNG, up to 2 MB · optional</div>
    </div>
</div>

<div class="vf-section"><i class="bi bi-person-badge"></i> Account details</div>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="vf-label">First name <span class="text-danger">*</span></label>
        <div class="input-group vf-ig"><span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $isEdit ? $vendor->first_name : '') }}" autocomplete="off" required>
        </div>
    </div>
    <div class="col-md-6">
        <label class="vf-label">Last name</label>
        <div class="input-group vf-ig"><span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $isEdit ? $vendor->last_name : '') }}" autocomplete="off">
        </div>
    </div>
    <div class="col-md-6">
        <label class="vf-label">Email <span class="text-danger">*</span></label>
        <div class="input-group vf-ig"><span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" name="email" class="form-control" value="{{ old('email', $isEdit ? $vendor->email : '') }}" autocomplete="off" required>
        </div>
    </div>
    <div class="col-md-6">
        <label class="vf-label">Username</label>
        <div class="input-group vf-ig"><span class="input-group-text"><i class="bi bi-at"></i></span>
            <input type="text" name="username" class="form-control" value="{{ old('username', $isEdit ? $vendor->username : '') }}" placeholder="Defaults to email" autocomplete="off">
        </div>
    </div>
    <div class="col-md-6">
        <label class="vf-label">Phone</label>
        <div class="input-group vf-ig"><span class="input-group-text"><i class="bi bi-telephone"></i></span>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $isEdit ? $vendor->phone : '') }}" autocomplete="off">
        </div>
    </div>
    <div class="col-md-6">
        <label class="vf-label">Password @if($isEdit)<span class="text-muted small fw-normal">(leave blank to keep)</span>@else<span class="text-danger">*</span>@endif</label>
        <div class="input-group vf-ig"><span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
        </div>
    </div>
    <div class="col-12">
        <div class="form-check form-switch mt-1">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $isEdit ? $vendor->is_active : '1') ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>

<div class="vf-section"><i class="bi bi-geo-alt"></i> Contact &amp; address</div>
<div class="row g-3">
    <div class="col-md-4">
        <label class="vf-label">Country</label>
        <input type="text" name="country" class="form-control" value="{{ old('country', $isEdit ? $vendor->country : '') }}" autocomplete="off">
    </div>
    <div class="col-md-4">
        <label class="vf-label">State</label>
        <input type="text" name="state" class="form-control" value="{{ old('state', $isEdit ? $vendor->state : '') }}" autocomplete="off">
    </div>
    <div class="col-md-4">
        <label class="vf-label">City</label>
        <input type="text" name="city" class="form-control" value="{{ old('city', $isEdit ? $vendor->city : '') }}" autocomplete="off">
    </div>
    <div class="col-md-4">
        <label class="vf-label">Zip / Postcode</label>
        <input type="text" name="zipcode" class="form-control" value="{{ old('zipcode', $isEdit ? $vendor->zipcode : '') }}" autocomplete="off">
    </div>
    <div class="col-md-8">
        <label class="vf-label">Address</label>
        <input type="text" name="address" class="form-control" value="{{ old('address', $isEdit ? $vendor->address : '') }}" autocomplete="off">
    </div>
</div>

<style>
.vf-section{ display:flex; align-items:center; gap:8px; font-size:12px; text-transform:uppercase; letter-spacing:.07em; color:var(--accent); font-weight:700; padding-bottom:9px; margin-bottom:6px; border-bottom:2px solid var(--accent-soft); }
.vf-label{ font-size:12.5px; font-weight:600; color:var(--text); margin-bottom:5px; }
.vf-ig .input-group-text{ background:var(--surface-2); border-color:var(--input-border); color:var(--muted); }
.vf-ig .form-control{ border-left:0; }
.vf-ig .input-group-text + .form-control:focus{ border-left:0; }
.form-control{ min-height:44px; }
.vf-photo{ display:flex; align-items:center; gap:18px; }
.vf-photo-img{ width:92px; height:92px; border-radius:16px; object-fit:contain; background:var(--surface-2); border:1px solid var(--border); padding:6px; display:flex; align-items:center; justify-content:center; }
.vf-photo-ph{ background:var(--accent-soft); color:var(--accent); font-size:30px; font-weight:700; }
.vf-upload{ display:inline-flex; align-items:center; gap:7px; cursor:pointer; background:var(--accent-soft); color:var(--accent); font-weight:600; font-size:13px; padding:8px 16px; border-radius:10px; transition:.15s; }
.vf-upload:hover{ background:var(--accent); color:#fff; }
</style>
<script>
function saPreview(input){
    if(!input.files || !input.files[0]) return;
    var img = document.createElement('img');
    img.id = 'avatarPreview'; img.className = 'vf-photo-img'; img.src = URL.createObjectURL(input.files[0]);
    document.getElementById('avatarPreview').replaceWith(img);
}
</script>
