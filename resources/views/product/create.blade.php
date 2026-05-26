@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }

/* Toggle buttons */
.toggle-active-no, .toggle-inactive-no, .toggle-active-yes, .toggle-inactive-yes {
    height: 40px; padding: 0 24px; border: none; font-size: 13px; font-weight: 700;
    cursor: pointer; outline: none !important; transition: all 0.15s;
    -webkit-tap-highlight-color: transparent;
}
.toggle-active-no { background: #dc2626; color: #fff; }
.toggle-inactive-no { background: #fff; color: #94a3b8; }
.toggle-active-yes { background: rgb(234, 88, 12); color: #fff; }
.toggle-inactive-yes { background: #fff; color: #94a3b8; }

/* Input with icon */
.input-icon-wrap { position: relative; display: flex; align-items: center; }
.input-icon {
    position: absolute; left: 14px; color: #b0bec5; font-size: 14px;
    z-index: 1; pointer-events: none;
    display: inline-flex; align-items: center; justify-content: center;
}
.input-with-icon { padding-left: 40px !important; }

/* Mobile-only elements hidden by default */
.m-only { display: none !important; }

/* ── Tablet / iPad (768px – 1024px) — matches Edit Product page exactly ── */
@media (min-width: 768px) and (max-width: 1024px) {

    /* Page header card */
    .create-header-card {
        padding: 16px 20px !important;
        border-radius: 14px !important;
        margin-bottom: 16px !important;
        gap: 12px !important;
        box-shadow: 0 2px 16px rgba(0,0,0,0.06) !important;
        border: none !important;
    }
    .create-header-card h1 {
        font-size: 20px !important; font-weight: 800 !important;
        margin: 0 !important; color: #0f172a !important;
    }
    .create-header-card p {
        font-size: 12px !important; margin: 2px 0 0 !important;
    }
    .create-header-card .back-btn {
        width: 36px !important; height: 36px !important; border-radius: 9px !important;
    }
    .create-header-card .create-icon {
        width: 44px !important; height: 44px !important; border-radius: 13px !important;
    }

    /* Card — identical to edit page */
    .card {
        border-radius: 16px !important;
        border: 1px solid #eef2f7 !important;
        box-shadow: 0 2px 16px rgba(0,0,0,0.06) !important;
    }
    .card-body { padding: 26px 24px !important; }

    /* Form group */
    .form-group { margin-bottom: 20px !important; }

    /* Labels — identical to edit page */
    .form-group label {
        font-size: 11.5px !important; font-weight: 700 !important;
        color: #64748b !important; text-transform: uppercase !important;
        letter-spacing: 0.6px !important; margin-bottom: 8px !important;
        display: block !important;
    }

    /* Hide input icons on tablet — clean look like edit page */
    .input-icon { display: none !important; }

    /* Inputs — identical to edit page (no icon padding) */
    .form-control {
        height: 48px !important;
        border-radius: 12px !important;
        border: 1.5px solid #e5e7eb !important;
        font-size: 15px !important; font-weight: 500 !important;
        color: #1e293b !important; padding: 0 16px !important;
        background: #fff !important; box-shadow: none !important;
        transition: border-color 0.15s, box-shadow 0.15s !important;
    }
    .input-with-icon { padding-left: 16px !important; }
    .form-control::placeholder { color: #c5cdd8 !important; font-weight: 400 !important; }
    .form-control:focus {
        border-color: rgb(234, 88, 12) !important;
        box-shadow: 0 0 0 3px rgba(234,88,12,0.1) !important;
        outline: none !important;
    }

    /* Textarea */
    textarea.form-control {
        height: auto !important; min-height: 104px !important;
        padding: 14px 16px !important;
        resize: none !important; line-height: 1.6 !important;
    }

    /* Active status toggle — identical to edit page */
    .toggle-active-no, .toggle-inactive-no,
    .toggle-active-yes, .toggle-inactive-yes {
        height: 46px !important; padding: 0 32px !important;
        font-size: 14px !important; font-weight: 700 !important;
    }

    /* Action buttons — identical to edit page */
    .create-actions {
        padding-top: 22px !important; margin-top: 22px !important;
        border-top: 1.5px solid #f1f5f9 !important; gap: 12px !important;
    }
    .create-actions a {
        height: 48px !important; padding: 0 28px !important;
        border-radius: 12px !important; font-size: 14px !important; font-weight: 600 !important;
    }
    .create-actions button[type="submit"] {
        height: 48px !important; padding: 0 32px !important;
        border-radius: 12px !important; font-size: 14px !important; font-weight: 700 !important;
    }
}

@media (max-width: 767px) {
    /* Show/hide helpers */
    .m-only { display: block !important; }
    .d-only { display: none !important; }

    /* ── Header card ── */
    .create-header-card {
        padding: 12px 14px !important;
        margin-bottom: 12px !important;
        border-radius: 12px !important;
        gap: 10px !important;
    }
    .create-header-card .back-btn {
        width: 32px !important; height: 32px !important;
        border-radius: 8px !important; flex-shrink: 0 !important;
    }
    .create-header-card .create-icon {
        width: 40px !important; height: 40px !important;
        border-radius: 12px !important; flex-shrink: 0 !important;
        background: rgb(234, 88, 12) !important;
        box-shadow: 0 3px 10px rgba(234,88,12,0.3) !important;
    }
    .create-header-card .create-icon i { font-size: 17px !important; color: #fff !important; }
    .create-header-card h1 { font-size: 17px !important; font-weight: 800 !important; margin: 0 !important; }
    .create-header-card p { font-size: 11px !important; margin: 2px 0 0 !important; }

    /* ── "Product Information" section title inside card ── */
    .m-card-title {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        font-size: 14px !important;
        font-weight: 700 !important;
        color: #1e293b !important;
        padding-bottom: 14px !important;
        margin-bottom: 16px !important;
        border-bottom: 1px solid #f1f5f9 !important;
    }
    .m-card-title i { color: #9ca3af !important; font-size: 15px !important; }

    /* ── Card ── */
    .card {
        border-radius: 16px !important;
        border: none !important;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06) !important;
    }
    .card-body { padding: 16px 14px !important; }

    /* ── Single column form ── */
    .form-body .row { display: flex !important; flex-direction: column !important; margin: 0 !important; }
    .form-body .col-md-6, .form-body .col-lg-6 {
        width: 100% !important; flex: 0 0 100% !important;
        max-width: 100% !important; padding: 0 !important;
    }
    .form-group { margin-bottom: 16px !important; }
    .form-group .row { margin: 0 !important; }

    /* ── Labels ── */
    .form-group label {
        font-size: 10.5px !important; font-weight: 700 !important;
        color: #94a3b8 !important; text-transform: uppercase !important;
        letter-spacing: 0.6px !important; margin-bottom: 6px !important;
        display: block !important;
    }

    /* ── Inputs ── */
    .input-icon { left: 13px !important; color: #9ca3af !important; font-size: 13px !important; }
    .form-control {
        height: 48px !important;
        border-radius: 12px !important;
        border: 1px solid #E5E7EB !important;
        font-size: 14px !important;
        padding: 0 14px !important;
        background: #fff !important;
        color: #1e293b !important;
        width: 100% !important;
        box-shadow: none !important;
    }
    .input-with-icon { padding-left: 40px !important; }
    .form-control::placeholder { color: #c5cdd8 !important; font-weight: 400 !important; }
    .form-control:focus {
        border-color: rgb(234, 88, 12) !important;
        box-shadow: 0 0 0 3px rgba(234,88,12,0.08) !important;
        outline: none !important;
    }

    /* ── Textarea ── */
    textarea.form-control {
        min-height: 88px !important; resize: none !important;
        line-height: 1.5 !important;
        padding: 13px 14px 13px 40px !important;
        height: auto !important;
    }

    /* ── Active toggle — no background card ── */
    .col-active .form-group {
        background: transparent !important; border: none !important;
        border-radius: 0 !important; padding: 0 !important;
    }

    /* ── Action buttons ── */
    .create-actions {
        flex-direction: row !important; gap: 10px !important;
        padding-top: 16px !important; margin-top: 8px !important;
        border-top: 1px solid #f1f5f9 !important;
    }
    .create-actions a, .create-actions button {
        flex: 1 !important; height: 48px !important;
        justify-content: center !important;
        border-radius: 12px !important; font-size: 14px !important;
    }
}

/* ── Bootstrap-checkbox toggle (Yes pill) — apply on all viewports ── */
.checkbox-success .btn-success,
.btn-group .btn-success,
input.switch ~ .btn-group .btn-success,
.btn.btn-success.active { background-color: rgb(234, 88, 12) !important; border-color: rgb(234, 88, 12) !important; color: #fff !important; }
</style>
@endpush

@push('scripts')
<x-ajax-form-data
    formId='saveProductForm'
    :route="route('management.products.create.store')"
    method='POST'
/>
@endpush

@section('content')
<section class="users-list-wrapper">
<div class="create-header-card" style="margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px;padding:18px 24px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;">
	<div style="display:flex;align-items:center;gap:14px;">
		<a href="{{route('management.products.view.index')}}" class="back-btn" style="width:40px;height:40px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:#64748b;transition:all 0.15s;" title="Back to Products"
		   onmouseover="this.style.borderColor='rgb(234, 88, 12)';this.style.color='rgb(234, 88, 12)';this.style.background='#fff7ed';"
		   onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
		    <i class="fa fa-arrow-left"></i>
		</a>
		<div class="create-icon" style="width:48px;height:48px;border-radius:14px;background:rgb(234, 88, 12);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(234,88,12,0.25);">
			<i class="fa fa-shopping-bag" style="color:#fff;font-size:20px;"></i>
		</div>
		<div>
			<h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">
				<span class="d-only">Product Information</span>
				<span class="m-only" style="display:none;">Add Product</span>
			</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">
				<span class="d-only">Create a new product</span>
				<span class="m-only" style="display:none;">Create a new product listing</span>
			</p>
		</div>
	</div>
</div>
<div class="row match-height">
    <div class="col-md-12">
        <div class="card" style="border-radius:16px;border:1px solid #eaecf2;box-shadow:0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04);">
            <div class="card-content collapse show">
                <div class="card-body">
                    <form class="form" id="saveProductForm" name="saveProductForm">
                    @csrf
                        {{-- Mobile-only section title --}}
                        <div class="m-card-title" style="display:none;">
                            <i class="fa fa-clipboard"></i> Product Information
                        </div>
                        <div class="form-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Product Name <span class="text-danger">*</span></label>
                                        <div class="input-icon-wrap">
                                            <i class="fa fa-circle-o input-icon"></i>
                                            <input type="text" id="name" class="form-control input-with-icon" placeholder="Enter Product Name" name="name">
                                        </div>
                                        <div class="row"><div class="col-sm-12" data-validate="name"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="selling_price">Selling Price</label>
                                        <div class="input-icon-wrap">
                                            <span class="input-icon" style="font-weight:600;font-size:15px;">$</span>
                                            <input type="text" id="selling_price" class="form-control input-with-icon" placeholder="0.00" name="selling_price">
                                        </div>
                                        <div class="row"><div class="col-sm-12" data-validate="selling_price"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tax_vat">Tax/Vat (%)</label>
                                        <div class="input-icon-wrap">
                                            <i class="fa fa-info-circle input-icon"></i>
                                            <input type="text" id="tax_vat" class="form-control input-with-icon" placeholder="Enter Tax/Vat percentage" name="tax_vat">
                                        </div>
                                        <div class="row"><div class="col-sm-12" data-validate="tax_vat"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="unit_weight">Unit Weight (kg)</label>
                                        <div class="input-icon-wrap">
                                            <i class="fa fa-clipboard input-icon"></i>
                                            <input type="text" id="unit_weight" class="form-control input-with-icon" placeholder="Enter Unit Weight" name="unit_weight">
                                        </div>
                                        <div class="row"><div class="col-sm-12" data-validate="unit_weight"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <div class="input-icon-wrap" style="align-items:flex-start;">
                                            <i class="fa fa-file-text input-icon" style="top:16px;position:absolute;"></i>
                                            <textarea id="description" class="form-control input-with-icon" placeholder="Enter short description about the product..." name="description"></textarea>
                                        </div>
                                        <div class="row"><div class="col-sm-12" data-validate="description"></div></div>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-active">
                                    <div class="form-group">
                                        <label for="status">Active</label>
                                        <input type="hidden" name="is_active" id="is_active_val" value="1">
                                        <div style="display:inline-flex;border-radius:10px;overflow:hidden;border:1.5px solid #e2e8f0;background:#fff;">
                                            <button type="button" id="toggleNo" onclick="document.getElementById('is_active_val').value='0';document.getElementById('toggleNo').className='toggle-active-no';document.getElementById('toggleYes').className='toggle-inactive-yes';"
                                                class="toggle-inactive-no">No</button>
                                            <button type="button" id="toggleYes" onclick="document.getElementById('is_active_val').value='1';document.getElementById('toggleYes').className='toggle-active-yes';document.getElementById('toggleNo').className='toggle-inactive-no';"
                                                class="toggle-active-yes">Yes</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="create-actions" style="display:flex;align-items:center;justify-content:flex-end;gap:12px;padding-top:20px;border-top:1px solid #eef2f7;margin-top:20px;">
                            <a href="{{ route('management.products.view.index') }}" style="height:44px;padding:0 24px;border-radius:12px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:13.5px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all 0.15s;outline:none;" onmouseover="this.style.borderColor='rgb(234, 88, 12)';this.style.color='rgb(234, 88, 12)';this.style.background='#FFF8F3';" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
                                <i class="fa fa-times" style="font-size:11px;color:#000;margin-right:4px;"></i> Cancel
                            </a>
                            <button type="submit" class="btn" style="height:44px;padding:0 28px;border-radius:12px;border:none;background:rgb(234, 88, 12);color:#fff;font-size:13.5px;font-weight:700;box-shadow:0 2px 8px rgba(234,88,12,0.3);display:inline-flex;align-items:center;gap:6px;outline:none;transition:all 0.15s;white-space:nowrap;" onmouseover="this.style.background='#e0600e';" onmouseout="this.style.background='rgb(234, 88, 12)';">
                                <i class="fa fa-check" style="font-size:11px;"></i> <span style="white-space:nowrap;">Save Product</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
@endsection
