@php
    $isEdit = isset($admin) && $admin;
    $roleLabels = ['superadmin' => 'Super Admin (full access)', 'finance' => 'Finance', 'operations' => 'Operations', 'technical' => 'Technical'];
@endphp

<div class="vf-section"><i class="bi bi-person-badge"></i> Admin details</div>
<div class="row g-3 mb-2">
    <div class="col-md-6">
        <label class="vf-label">First name <span class="text-danger">*</span></label>
        <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $isEdit ? $admin->first_name : '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="vf-label">Last name</label>
        <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $isEdit ? $admin->last_name : '') }}">
    </div>
    <div class="col-md-6">
        <label class="vf-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $isEdit ? $admin->email : '') }}" autocomplete="off" required>
    </div>
    <div class="col-md-6">
        <label class="vf-label">Role <span class="text-danger">*</span></label>
        <select name="role" class="form-control" required>
            @foreach ($roles as $r)
                <option value="{{ $r }}" {{ old('role', $isEdit ? $admin->role : '') === $r ? 'selected' : '' }}>{{ $roleLabels[$r] ?? ucfirst($r) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="vf-label">Password @if($isEdit)<span class="text-muted small fw-normal">(blank = keep)</span>@else<span class="text-danger">*</span>@endif</label>
        <input type="password" name="password" class="form-control" minlength="6" autocomplete="new-password" {{ $isEdit ? '' : 'required' }}>
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check form-switch mt-1">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $isEdit ? $admin->is_active : '1') ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>
<div class="text-muted small mt-2"><i class="bi bi-info-circle me-1"></i>Section access for Finance/Operations/Technical is controlled by the Permissions Matrix on the Admin Users page.</div>

<style>
.vf-section{ display:flex; align-items:center; gap:8px; font-size:12px; text-transform:uppercase; letter-spacing:.07em; color:var(--accent); font-weight:700; padding-bottom:9px; margin-bottom:6px; border-bottom:2px solid var(--accent-soft); }
.vf-label{ font-size:12.5px; font-weight:600; color:var(--text); margin-bottom:5px; }
.form-control{ min-height:44px; background:var(--input-bg); border-color:var(--input-border); color:var(--input-text); }
</style>
