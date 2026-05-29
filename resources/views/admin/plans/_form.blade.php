@php $isEdit = isset($plan) && $plan; @endphp

<div class="vf-section"><i class="bi bi-tag"></i> Plan details</div>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label class="vf-label">Plan name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $plan->name : '') }}" placeholder="Professional" required>
    </div>
    <div class="col-md-3">
        <label class="vf-label">Price <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $isEdit ? $plan->price : '') }}" placeholder="49.00" required>
    </div>
    <div class="col-md-3">
        <label class="vf-label">Billing cycle</label>
        <select name="billing_cycle" class="form-control">
            @foreach (['monthly' => 'Monthly', 'yearly' => 'Yearly'] as $v => $l)
                <option value="{{ $v }}" {{ old('billing_cycle', $isEdit ? $plan->billing_cycle : 'monthly') === $v ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="vf-label">Currency</label>
        <input type="text" name="currency" class="form-control" value="{{ old('currency', $isEdit ? $plan->currency : 'GBP') }}" maxlength="8">
    </div>
    <div class="col-md-3">
        <label class="vf-label">Sort order</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $isEdit ? $plan->sort_order : 0) }}">
    </div>
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check form-switch mt-1">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $isEdit ? $plan->is_active : '1') ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
    </div>
</div>

<div class="vf-section"><i class="bi bi-sliders"></i> Limits <span class="text-muted small fw-normal text-lowercase ms-1">(leave blank = unlimited)</span></div>
<div class="row g-3 mb-4">
    @foreach ($limits as $col => $label)
        <div class="col-6 col-md-4 col-lg">
            <label class="vf-label">{{ $label }}</label>
            <input type="number" min="0" name="{{ $col }}" class="form-control" value="{{ old($col, $isEdit ? $plan->$col : '') }}" placeholder="∞">
        </div>
    @endforeach
</div>

<div class="vf-section"><i class="bi bi-toggles"></i> Feature access</div>
<div class="row g-2 mb-2">
    @foreach ($features as $col => $label)
        <div class="col-6 col-md-4">
            <label class="feat-toggle">
                <input type="checkbox" name="{{ $col }}" value="1" class="form-check-input" {{ old($col, $isEdit ? $plan->$col : ($col === 'f_purchases' || $col === 'f_customers' || $col === 'f_suppliers' ? '1' : '')) ? 'checked' : '' }}>
                <span>{{ $label }}</span>
            </label>
        </div>
    @endforeach
</div>

<style>
.vf-section{ display:flex; align-items:center; gap:8px; font-size:12px; text-transform:uppercase; letter-spacing:.07em; color:var(--accent); font-weight:700; padding-bottom:9px; margin-bottom:6px; border-bottom:2px solid var(--accent-soft); }
.vf-label{ font-size:12.5px; font-weight:600; color:var(--text); margin-bottom:5px; }
.form-control{ min-height:44px; background:var(--input-bg); border-color:var(--input-border); color:var(--input-text); }
.feat-toggle{ display:flex; align-items:center; gap:9px; background:var(--surface-2); border:1px solid var(--border); border-radius:10px; padding:11px 14px; cursor:pointer; font-size:13.5px; font-weight:500; }
.feat-toggle input:checked + span{ color:var(--accent); font-weight:600; }
</style>
