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
.toggle-active-yes { background: #F27420; color: #fff; }
.toggle-inactive-yes { background: #fff; color: #94a3b8; }

/* ── Tablet / iPad (768px – 1024px) ── */
@media (min-width: 768px) and (max-width: 1024px) {

    /* Page header card */
    .users-list-wrapper > div:first-child {
        padding: 16px 20px !important;
        border-radius: 14px !important;
        margin-bottom: 16px !important;
        gap: 12px !important;
    }
    .users-list-wrapper > div:first-child h1 {
        font-size: 20px !important;
        font-weight: 800 !important;
        margin: 0 !important;
    }
    .users-list-wrapper > div:first-child p {
        font-size: 12px !important;
        margin: 2px 0 0 !important;
    }
    .users-list-wrapper > div:first-child a[title="Back to Products"] {
        width: 36px !important; height: 36px !important;
        border-radius: 9px !important;
    }
    .users-list-wrapper > div:first-child > div > div[style*="width:48px"] {
        width: 44px !important; height: 44px !important;
        border-radius: 13px !important;
    }

    /* Card container */
    .card {
        border-radius: 16px !important;
        border: 1px solid #eef2f7 !important;
        box-shadow: 0 2px 16px rgba(0,0,0,0.06) !important;
    }
    .card-body { padding: 26px 24px !important; }

    /* Section header (PRODUCT INFORMATION) */
    .form-section {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding-bottom: 14px !important;
        margin-bottom: 22px !important;
        border-bottom: 1.5px solid #f1f5f9 !important;
    }
    .form-section span {
        font-size: 11px !important;
        font-weight: 700 !important;
        color: #94a3b8 !important;
        letter-spacing: 1px !important;
        text-transform: uppercase !important;
    }
    .form-section a {
        font-size: 12.5px !important;
        font-weight: 600 !important;
        color: #F27420 !important;
        text-decoration: none !important;
    }

    /* Form group spacing */
    .form-group { margin-bottom: 20px !important; }

    /* Labels */
    .form-group label {
        font-size: 11.5px !important;
        font-weight: 700 !important;
        color: #64748b !important;
        text-transform: uppercase !important;
        letter-spacing: 0.6px !important;
        margin-bottom: 8px !important;
        display: block !important;
    }

    /* Text inputs */
    .form-control {
        height: 48px !important;
        border-radius: 12px !important;
        border: 1.5px solid #e5e7eb !important;
        font-size: 15px !important;
        font-weight: 500 !important;
        color: #1e293b !important;
        padding: 0 16px !important;
        background: #fff !important;
        box-shadow: none !important;
        transition: border-color 0.15s, box-shadow 0.15s !important;
    }
    .form-control::placeholder {
        color: #c5cdd8 !important;
        font-weight: 400 !important;
    }
    .form-control:focus {
        border-color: #F27420 !important;
        box-shadow: 0 0 0 3px rgba(242,116,32,0.1) !important;
        outline: none !important;
    }

    /* Textarea */
    textarea.form-control {
        height: auto !important;
        min-height: 104px !important;
        padding: 14px 16px !important;
        resize: none !important;
        line-height: 1.6 !important;
    }

    /* Active status toggle */
    .toggle-active-no, .toggle-inactive-no,
    .toggle-active-yes, .toggle-inactive-yes {
        height: 46px !important;
        padding: 0 32px !important;
        font-size: 14px !important;
        font-weight: 700 !important;
    }

    /* Action buttons row */
    .edit-product-actions {
        padding-top: 22px !important;
        margin-top: 22px !important;
        border-top: 1.5px solid #f1f5f9 !important;
        gap: 12px !important;
    }
    .edit-product-actions a {
        height: 48px !important;
        padding: 0 28px !important;
        border-radius: 12px !important;
        font-size: 14px !important;
        font-weight: 600 !important;
    }
    .edit-product-actions button[type="submit"] {
        height: 48px !important;
        padding: 0 32px !important;
        border-radius: 12px !important;
        font-size: 14px !important;
        font-weight: 700 !important;
    }
}

@media (max-width: 767px) {

    /* ── Header card ── */
    .users-list-wrapper > div:first-child {
        padding: 12px 14px !important;
        border-radius: 12px !important;
        margin-bottom: 12px !important;
        gap: 10px !important;
    }
    .users-list-wrapper > div:first-child h1 {
        font-size: 17px !important; font-weight: 800 !important; margin: 0 !important;
    }
    .users-list-wrapper > div:first-child p { font-size: 11px !important; }
    .users-list-wrapper > div:first-child a[title="Back to Products"] {
        width: 32px !important; height: 32px !important; border-radius: 8px !important;
    }
    .users-list-wrapper > div:first-child > div > div[style*="width:48px"] {
        width: 40px !important; height: 40px !important;
        border-radius: 12px !important;
        box-shadow: 0 3px 10px rgba(242,116,32,0.3) !important;
    }
    .users-list-wrapper > div:first-child > div > div[style*="width:48px"] i {
        font-size: 17px !important;
    }

    /* ── Card ── */
    .card {
        border-radius: 16px !important;
        border: none !important;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06) !important;
    }
    .card-body { padding: 16px 14px !important; }

    /* ── Section title (PRODUCT INFORMATION + Edit Now) ── */
    .form-section {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        font-size: 10.5px !important;
        font-weight: 700 !important;
        color: #94a3b8 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.7px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        padding-bottom: 12px !important;
        margin-bottom: 16px !important;
    }
    .form-section a {
        font-size: 11px !important;
        font-weight: 600 !important;
        color: #F27420 !important;
        text-decoration: none !important;
        text-transform: none !important;
        letter-spacing: 0 !important;
    }

    /* ── Single column layout ── */
    .form-body .row {
        display: flex !important; flex-direction: column !important; margin: 0 !important;
    }
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
    .form-control::placeholder { color: #c5cdd8 !important; font-weight: 400 !important; }
    .form-control:focus {
        border-color: #F27420 !important;
        box-shadow: 0 0 0 3px rgba(242,116,32,0.08) !important;
        outline: none !important;
    }

    /* ── Textarea ── */
    textarea.form-control {
        min-height: 88px !important; resize: none !important;
        line-height: 1.5 !important;
        padding: 13px 14px !important; height: auto !important;
    }

    /* ── Active Status — no card background ── */
    .col-active .form-group {
        background: transparent !important; border: none !important;
        border-radius: 0 !important; padding: 0 !important;
    }
    .col-active .form-group label {
        font-size: 10.5px !important; font-weight: 700 !important;
        color: #94a3b8 !important; text-transform: uppercase !important;
        letter-spacing: 0.6px !important; margin-bottom: 4px !important;
    }
    .col-active .form-group > div[style*="font-size:11px"] {
        font-size: 11px !important; color: #94a3b8 !important; margin-bottom: 10px !important;
    }

    /* ── Action buttons ── */
    .edit-product-actions {
        flex-direction: row !important; gap: 10px !important;
        padding-top: 16px !important; margin-top: 8px !important;
        border-top: 1px solid #f1f5f9 !important;
    }
    .edit-product-actions a, .edit-product-actions button {
        flex: 1 !important; height: 48px !important;
        justify-content: center !important;
        border-radius: 12px !important; font-size: 14px !important;
    }
}
</style>
@endpush

@push('scripts')
<x-ajax-form-data
    formId='updateProductForm'
    :route="route('management.products.edit.update', $data->id)"
    method='POST'
/>
@endpush

@section('content')
<section class="users-list-wrapper">
<div style="margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px;padding:18px 24px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;">
	<div style="display:flex;align-items:center;gap:14px;">
		<a href="{{route('management.products.view.index')}}" style="width:40px;height:40px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:#64748b;transition:all 0.15s;" title="Back to Products"
		   onmouseover="this.style.borderColor='#F27420';this.style.color='#F27420';this.style.background='#fff7ed';"
		   onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
		    <i class="fa fa-arrow-left"></i>
		</a>
		<div style="width:48px;height:48px;border-radius:14px;background:#F27420;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(242,116,32,0.25);">
			<i class="fa fa-shopping-bag" style="color:#fff;font-size:20px;"></i>
		</div>
		<div>
			<h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Edit Product</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Update product #{{$data->id}} details</p>
		</div>
	</div>
</div>
<div class="row match-height">
    <div class="col-md-12">
        <div class="card" style="border-radius:16px;border:1px solid #eaecf2;box-shadow:0 1px 2px rgba(0,0,0,0.03), 0 4px 16px rgba(0,0,0,0.04);">
            <div class="card-content collapse show">
                <div class="card-body">
                    <form class="form" id="updateProductForm" name="updateProductForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                        <div class="form-body">
                            <div class="form-section" style="display:flex;align-items:center;justify-content:space-between;">
                                <span style="font-size:11px;font-weight:700;color:#64748b;letter-spacing:0.8px;text-transform:uppercase;">Product Information</span>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Product Name<span class="text-danger">*</span></label>
                                        <input type="text" id="name" class="form-control" placeholder="Enter Product Name" name="name" value="{{$data->name}}">
                                        <div class="row"><div class="col-sm-12" data-validate="name"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="selling_price">Selling Price</label>
                                        <input type="text" id="selling_price" class="form-control" placeholder="Enter Selling Price" name="selling_price" value="{{$data->selling_price}}">
                                        <div class="row"><div class="col-sm-12" data-validate="selling_price"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tax_vat">Tax/Vat (%)</label>
                                        <input type="text" id="tax_vat" class="form-control" placeholder="Enter Tax/Vat" name="tax_vat" value="{{$data->tax_vat}}">
                                        <div class="row"><div class="col-sm-12" data-validate="tax_vat"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="unit_weight">Unit Weight (kg)</label>
                                        <input type="text" id="unit_weight" class="form-control" placeholder="Enter Unit Weight" name="unit_weight" value="{{$data->unit_weight}}">
                                        <div class="row"><div class="col-sm-12" data-validate="unit_weight"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row desc-active-row">
                                <div class="col-md-6 col-desc">
                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea id="description" class="form-control" placeholder="Enter detailed product description..." name="description">{{$data->description}}</textarea>
                                        <div class="row"><div class="col-sm-12" data-validate="description"></div></div>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-active">
                                    <div class="form-group">
                                        <label for="status">Active Status</label>
                                        <div style="font-size:11px;color:#94a3b8;margin-bottom:10px;">Is this product available for sale?</div>
                                        <input type="hidden" name="is_active" id="is_active_val" value="{{ $data->is_active ? '1' : '0' }}">
                                        <div style="display:inline-flex;border-radius:10px;overflow:hidden;border:1.5px solid #e2e8f0;background:#fff;">
                                            <button type="button" id="toggleNo" onclick="document.getElementById('is_active_val').value='0';document.getElementById('toggleNo').className='toggle-active-no';document.getElementById('toggleYes').className='toggle-inactive-yes';"
                                                class="{{ $data->is_active ? 'toggle-inactive-no' : 'toggle-active-no' }}">No</button>
                                            <button type="button" id="toggleYes" onclick="document.getElementById('is_active_val').value='1';document.getElementById('toggleYes').className='toggle-active-yes';document.getElementById('toggleNo').className='toggle-inactive-no';"
                                                class="{{ $data->is_active ? 'toggle-active-yes' : 'toggle-inactive-yes' }}">Yes</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="edit-product-actions" style="display:flex;align-items:center;justify-content:flex-end;gap:12px;padding-top:20px;border-top:1px solid #eef2f7;margin-top:20px;">
                            <a href="{{ route('management.products.view.index') }}" style="height:44px;padding:0 24px;border-radius:12px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:13.5px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all 0.15s;outline:none;" onmouseover="this.style.borderColor='#F27420';this.style.color='#F27420';this.style.background='#FFF8F3';" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
                                <i class="fa fa-times" style="font-size:11px;"></i> Cancel
                            </a>
                            <button type="submit" class="btn" style="height:44px;padding:0 28px;border-radius:12px;border:none;background:#F27420;color:#fff;font-size:13.5px;font-weight:700;box-shadow:0 2px 8px rgba(242,116,32,0.3);display:inline-flex;align-items:center;gap:6px;outline:none;transition:all 0.15s;" onmouseover="this.style.background='#e0600e';" onmouseout="this.style.background='#F27420';">
                                <i class="fa fa-floppy-o" style="font-size:13px;"></i> <span style="white-space:nowrap;">Save Changes</span>
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
