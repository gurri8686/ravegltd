@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18/build/css/intlTelInput.css">
<style>
.content-header { display: none !important; }

/* ── Select2 modern theme ── */
.select2-container--default .select2-selection--single {
    border-radius: 10px !important; border: 1px solid #E5E7EB !important;
    height: 42px !important; display: flex !important; align-items: center !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #1e293b !important; font-size: 14px !important; padding-left: 14px !important; line-height: 42px !important;
}
.select2-container--default .select2-selection--single .select2-selection__placeholder { color: #C5D0DC !important; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 42px !important; right: 8px !important; }
.select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #9CA3AF transparent transparent transparent !important;
}
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: rgb(234, 88, 12) !important; box-shadow: 0 0 0 3px rgba(234,88,12,0.10) !important;
}
.select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
    border-color: transparent transparent rgb(234, 88, 12) transparent !important;
}
.select2-dropdown {
    border-radius: 12px !important; border: 1px solid #E5E7EB !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.10) !important; overflow: hidden !important;
}
.select2-search--dropdown { padding: 8px !important; }
.select2-search--dropdown .select2-search__field {
    border-radius: 8px !important; border: 1px solid #E5E7EB !important;
    font-size: 13px !important; padding: 7px 12px !important; outline: none !important;
}
.select2-search--dropdown .select2-search__field:focus { border-color: rgb(234, 88, 12) !important; }
.select2-results__options { max-height: 220px !important; }
.select2-results__option { font-size: 13px !important; padding: 8px 14px !important; }
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background: #FFF7ED !important; color: rgb(234, 88, 12) !important;
}
.select2-container--default .select2-results__option[aria-selected=true] {
    background: #FEF3E6 !important; color: rgb(234, 88, 12) !important; font-weight: 600 !important;
}
.select2-container--default .select2-selection--single .select2-selection__clear { color: #9CA3AF !important; font-size: 16px !important; margin-right: 4px !important; }
.select2-container--disabled .select2-selection--single { background: #f9fafb !important; opacity: 0.6 !important; cursor: not-allowed !important; }
.cust-form-actions { display: flex !important; justify-content: flex-end !important; gap: 12px !important; align-items: center !important; }
.cfa-cancel {
    display: inline-flex !important; align-items: center !important; gap: 7px !important;
    height: 42px !important; padding: 0 22px !important; border-radius: 10px !important;
    border: 1.5px solid #e2e8f0 !important; background: #f8fafc !important; color: #64748b !important;
    font-weight: 600 !important; font-size: 14px !important; text-decoration: none !important;
    transition: background 0.18s, border-color 0.18s, color 0.18s; cursor: pointer !important; flex: none !important;
}
.cfa-cancel:hover, .cfa-cancel:active { background: #fff8f3 !important; border-color: rgb(234, 88, 12) !important; color: rgb(234, 88, 12) !important; text-decoration: none !important; }
.cfa-save {
    display: inline-flex !important; align-items: center !important; gap: 7px !important;
    height: 42px !important; padding: 0 26px !important; border-radius: 10px !important;
    background: rgb(234, 88, 12) !important; color: #fff !important;
    font-weight: 700 !important; font-size: 14px !important; border: none !important;
    box-shadow: 0 4px 14px rgba(234,88,12,0.35) !important; cursor: pointer !important;
    transition: box-shadow 0.18s, transform 0.18s; outline: none !important; flex: none !important;
}
.cfa-save:hover { box-shadow: 0 6px 20px rgba(234,88,12,0.45) !important; transform: translateY(-1px); }
.cfa-save:active { transform: translateY(0); }

@media (max-width: 767px), (min-width: 768px) and (max-width: 1024px) {
    .select2-container--default .select2-selection--single { height: 50px !important; border-radius: 12px !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 50px !important; font-size: 14px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 50px !important; }
}

@media (max-width: 767px), (min-width: 768px) and (max-width: 1024px) {
    /* ── Page background (white, no grey) ── */
    html, body, .app-content, .content-wrapper, .content-body, .users-list-wrapper { background: #fff !important; }
    .row.match-height { margin: 0 !important; }
    .row.match-height > .col-md-12 { padding: 0 !important; }

    /* ── Header card ── */
    .cust-create-header { padding: 12px 14px !important; border-radius: 12px !important; margin-bottom: 10px !important; flex-wrap: nowrap !important; }
    .cust-create-header .cust-back-btn { width: 32px !important; height: 32px !important; border-radius: 8px !important; flex-shrink: 0 !important; }
    .cust-create-header .cust-icon { width: 38px !important; height: 38px !important; border-radius: 10px !important; flex-shrink: 0 !important; }
    .cust-create-header .cust-icon i { font-size: 16px !important; }
    .cust-create-header h1 { font-size: 17px !important; font-weight: 800 !important; margin: 0 !important; }
    .cust-create-header p { font-size: 11px !important; margin: 2px 0 0 !important; }

    /* ── Main card → transparent wrapper ── */
    .users-list-wrapper .card, .users-list-wrapper .card-content, .users-list-wrapper .card-body,
    .app-content .content-body, .cust-form-actions { background: transparent !important; background-color: transparent !important; box-shadow: none !important; border: none !important; }
    .users-list-wrapper .card { height: auto !important; }
    .users-list-wrapper .card-body { padding: 0 !important; }

    /* ── Section cards ── */
    .cust-form-card {
        background: #fff !important;
        border-radius: 16px !important;
        box-shadow: none !important;
        border: 1px solid #ECECEC !important;
        padding: 8px 16px 16px !important;
        margin-bottom: 14px !important;
    }

    /* ── Section headers ── */
    h4.form-section {
        font-size: 14.5px !important; font-weight: 600 !important; color: #0A0A0A !important; line-height: 1.2 !important;
        border: none !important; padding: 0 0 14px !important; margin: 0 0 16px !important;
        display: flex !important; align-items: center !important; gap: 11px !important;
        background: none !important; border-bottom: 1px solid #F2F2F2 !important;
        letter-spacing: 0 !important;
    }
    h4.form-section i {
        width: 34px !important; height: 34px !important; min-width: 34px !important;
        border-radius: 9px !important; background: #FFF1E6 !important;
        color: #FF6B1A !important; font-size: 16px !important;
        display: inline-flex !important; align-items: center !important; justify-content: center !important;
    }
    h4.form-section .fs-tt { display: flex !important; flex-direction: column !important; gap: 2px !important; line-height: 1.2 !important; }
    h4.form-section .fs-tt small {
        font-size: 11.5px !important; font-weight: 400 !important; color: #9A9A9A !important;
        text-transform: none !important; letter-spacing: 0 !important; line-height: 1.2 !important;
    }

    /* ── Form group ── */
    .form-group { margin-bottom: 0 !important; padding-bottom: 0 !important; }

    /* ── Field spacing: gap between cols in same row, and between rows ── */
    .cust-form-card .row > [class*="col-"]:not(:last-child) { margin-bottom: 14px !important; }
    .cust-form-card .row + .row { margin-top: 14px !important; }

    /* ── Labels ── */
    .form-group label {
        font-size: 10.5px !important; font-weight: 700 !important; color: #6B6B6B !important;
        text-transform: uppercase !important; letter-spacing: 0.8px !important;
        margin-bottom: 7px !important; display: block !important;
        line-height: 1 !important;
    }

    /* ── Inputs ── */
    .form-control {
        border-radius: 10px !important;
        border: 1px solid #ECECEC !important;
        font-size: 14px !important; height: 44px !important; padding: 0 12px !important;
        color: #0A0A0A !important; background-color: #fff !important;
        box-shadow: none !important;
        transition: border-color 0.15s, box-shadow 0.15s !important;
    }
    .form-control::placeholder { color: #C5D0DC !important; font-size: 14px !important; }
    .form-control:focus {
        border-color: rgb(234, 88, 12) !important;
        box-shadow: 0 0 0 3px rgba(234,88,12,0.10) !important;
        outline: none !important;
    }
    textarea.form-control {
        height: 90px !important; padding: 13px 16px !important;
        resize: vertical !important; line-height: 1.55 !important;
    }
    select.form-control {
        -webkit-appearance: none !important; appearance: none !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23F27420' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important; background-position: right 14px center !important;
        background-size: 14px !important; padding-right: 38px !important;
    }
    select.form-control:disabled {
        opacity: 0.45 !important; cursor: not-allowed !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23C5D0DC' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
    }

    /* ── Icons inside inputs ── */
    input[name="website"] {
        padding-left: 36px !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none'%3E%3Ccircle cx='12' cy='12' r='8.5' stroke='%239A9A9A' stroke-width='1.6'%3E%3C/circle%3E%3Cpath d='M3.5 12h17M12 3.5c2.5 2.5 2.5 14.5 0 17M12 3.5c-2.5 2.5-2.5 14.5 0 17' stroke='%239A9A9A' stroke-width='1.4'%3E%3C/path%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important; background-position: 12px center !important; background-size: 15px !important;
    }
    input[name="email"] {
        padding-left: 36px !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none'%3E%3Crect x='3' y='5.5' width='18' height='13' rx='2.2' stroke='%239A9A9A' stroke-width='1.6'%3E%3C/rect%3E%3Cpath d='M4 7l8 6 8-6' stroke='%239A9A9A' stroke-width='1.6' stroke-linecap='round'%3E%3C/path%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important; background-position: 12px center !important; background-size: 15px !important;
    }
    input[name="first_name"] {
        padding-left: 36px !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none'%3E%3Ccircle cx='12' cy='8' r='3.4' stroke='%236B6B6B' stroke-width='1.7'%3E%3C/circle%3E%3Cpath d='M5 20c1.4-3.4 4-5 7-5s5.6 1.6 7 5' stroke='%236B6B6B' stroke-width='1.7' stroke-linecap='round'%3E%3C/path%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important; background-position: 12px center !important; background-size: 15px !important;
    }
    .form-group label .text-danger { color: #FF6B1A !important; margin-left: 2px !important; }
    /* ── Mobile: separate flag box + gap + input ── */
    .iti { width: 100% !important; display: flex !important; align-items: center !important; gap: 8px !important; }
    .iti__flag-container { position: relative !important; height: 44px !important; padding: 0 !important; flex-shrink: 0 !important; }
    .iti__selected-flag {
        height: 44px !important; background: #fff !important;
        border: 1px solid #ECECEC !important; border-radius: 10px !important;
        padding: 0 10px !important; gap: 6px !important;
        display: flex !important; align-items: center !important;
    }
    .iti__selected-flag:hover { background: #fafafa !important; }
    .iti__selected-dial-code { font-size: 13.5px !important; font-weight: 600 !important; color: #0A0A0A !important; }
    .iti__arrow { border-top-color: #9CA3AF !important; margin-left: 2px !important; }
    .iti__country-list { border-radius: 10px !important; border: 1px solid #E5E7EB !important; box-shadow: 0 8px 24px rgba(0,0,0,0.10) !important; font-size: 13px !important; z-index: 9999 !important; }
    .iti__country.iti__highlight { background: #fff7ed !important; }
    .iti__search-input { border-radius: 8px !important; border: 1px solid #E5E7EB !important; font-size: 13px !important; padding: 6px 10px !important; margin: 6px !important; width: calc(100% - 12px) !important; box-sizing: border-box !important; }
    .iti input[name="mobile"] {
        padding-left: 12px !important;
        background-image: none !important;
        flex: 1 1 0 !important; width: auto !important; min-width: 0 !important;
    }
    input[name="address1"], input[name="address2"] {
        padding-left: 46px !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z'%3E%3C/path%3E%3Ccircle cx='12' cy='10' r='3'%3E%3C/circle%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important; background-position: 15px center !important; background-size: 17px !important;
    }
    input[name="vat_number"] {
        padding-left: 46px !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='2' y='5' width='20' height='14' rx='2'%3E%3C/rect%3E%3Cline x1='2' y1='10' x2='22' y2='10'%3E%3C/line%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important; background-position: 15px center !important; background-size: 17px !important;
    }
    input[name="credit_limit"] {
        padding-left: 32px !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'%3E%3Ctext x='8' y='12.5' font-size='14' font-weight='600' fill='%239A9A9A' text-anchor='middle' font-family='Arial,Helvetica,sans-serif'%3E%C2%A3%3C/text%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important; background-position: 12px center !important; background-size: 14px !important;
    }
    select[name="currency"], #currency {
        padding-left: 38px !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239A9A9A' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='8' cy='8' r='6'/%3E%3Cpath d='M18.09 10.37A6 6 0 1 1 10.34 18'/%3E%3Cpath d='M7 6h1v4'/%3E%3Cpath d='m16.71 13.88.7.71-2.82 2.82'/%3E%3C/svg%3E"), url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%239A9A9A' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat, no-repeat !important;
        background-position: 12px center, right 14px center !important;
        background-size: 16px, 14px !important;
    }

    /* ── 2-column pairs ── */
    .cust-col2-row { display: flex !important; gap: 10px !important; margin: 0 !important; }
    .cust-col2-row > div { flex: 1 !important; min-width: 0 !important; padding: 0 !important; }

    /* ── Row gutters reset ── */
    .cust-form-card .row { margin: 0 !important; }
    .cust-form-card .row > [class*="col-"] { padding: 0 !important; }

    /* ── Others section: stack Active + Remarks vertically ── */
    .cust-others-row { display: flex !important; flex-direction: column !important; gap: 12px !important; }
    .cust-others-row > div { width: 100% !important; float: none !important; }
    .cust-others-row .form-group { margin-bottom: 0 !important; }
    /* Active toggle spacing */
    .form-group .switch + .bootstrap-switch,
    .form-group input.switch ~ .bootstrap-switch { margin-top: 6px !important; display: inline-flex !important; align-items: center !important; }

    /* ── Active toggle switch — No | Yes pill ── */
    .bootstrap-switch {
        border-radius: 9px !important;
        border: 1.5px solid #e8edf2 !important;
        background: transparent !important;
        box-shadow: none !important;
        cursor: pointer !important;
    }
    .bootstrap-switch.bootstrap-switch-on { border-color: rgb(234, 88, 12) !important; }
    .bootstrap-switch .bootstrap-switch-container { border-radius: 7px !important; }
    .bootstrap-switch .bootstrap-switch-handle-on {
        background: rgb(234, 88, 12) !important; color: #fff !important;
        font-size: 12px !important; font-weight: 700 !important;
        border-radius: 7px 0 0 7px !important; line-height: 1 !important;
    }
    .bootstrap-switch .bootstrap-switch-handle-off {
        background: #f8fafc !important; color: #94a3b8 !important;
        font-size: 12px !important; font-weight: 700 !important;
        border-radius: 0 7px 7px 0 !important; line-height: 1 !important;
    }
    .bootstrap-switch .bootstrap-switch-label {
        background: #fff !important;
        min-width: 2px !important; width: 2px !important; padding: 0 !important;
        border-left: 1px solid #e8edf2 !important;
        border-right: 1px solid #e8edf2 !important;
        box-shadow: none !important;
    }

    /* ── Form actions card ── */
    .cust-form-actions {
        background: transparent !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        display: flex !important; gap: 10px !important; justify-content: flex-end !important;
    }
    .cfa-cancel, .cfa-save { width: 130px !important; height: 44px !important; justify-content: center !important; margin: 0 !important; padding: 0 !important; }
    .form-actions { display: none !important; }

}

/* ── Tablet: restore horizontal gap between 2-col pairs ── */
@media (min-width: 768px) and (max-width: 1024px) {
    .cust-form-card .row > .col-md-6:first-child { padding-right: 10px !important; }
    .cust-form-card .row > .col-md-6:last-child  { padding-left: 10px !important; }

    /* Others: Active + Remarks equal columns */
    .cust-others-row { flex-direction: row !important; align-items: flex-start !important; gap: 20px !important; }
    .cust-others-row > div { flex: 1 !important; width: auto !important; background: none !important; border: none !important; padding: 0 !important; }
    /* Active toggle — full-width pill row: label left, toggle right */
    .cust-others-row > div:first-child .form-group { display: flex !important; flex-direction: column !important; gap: 10px !important; }
    /* Textarea fills height */
    .cust-others-row > div:last-child textarea.form-control { min-height: 100px !important; height: 100px !important; }
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
    formId='saveCustomerForm'
    :route="route('management.customers.create.store')"
    method='POST'
/>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18/build/js/intlTelInput.min.js"></script>
<script>
$(document).ready(function() {

    if (window.innerWidth > 1024) return;

    // ── intl-tel-input (mobile only) ──
    var phoneInput = document.getElementById('phone_input');
    if (!phoneInput) return;
    var iti = window.intlTelInput(phoneInput, {
        utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@18/build/js/utils.js',
        initialCountry: 'in',
        preferredCountries: ['in', 'gb', 'us', 'ae', 'pk'],
        separateDialCode: true,
        countrySearch: true,
    });
    document.getElementById('saveCustomerForm').addEventListener('submit', function() {
        var full = iti.getNumber();
        if (full) phoneInput.value = full;
    }, true);
});
</script>
@endpush

@section('content')
<section class="users-list-wrapper">
<div class="cust-create-header" style="margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px;padding:18px 24px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;">
	<div style="display:flex;align-items:center;gap:14px;">
		<a href="{{route('management.customers.view.index')}}" class="cust-back-btn" style="width:40px;height:40px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;color:#64748b;transition:all 0.15s;" title="Back to Customers"
		   onmouseover="this.style.borderColor='rgb(234, 88, 12)';this.style.color='rgb(234, 88, 12)';this.style.background='#fff7ed';"
		   onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
		    <i class="fa fa-arrow-left"></i>
		</a>
		<div class="cust-icon" style="width:48px;height:48px;border-radius:14px;background:rgb(234, 88, 12);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(234,88,12,0.25);">
			<i class="fa fa-user-plus" style="color:#fff;font-size:20px;"></i>
		</div>
		<div>
			<h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Add Customer</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Create a new customer profile</p>
		</div>
	</div>
</div>
<div class="row match-height">
    <div class="col-md-12">
        <div class="card" style="height: 988.725px;">
            <div class="card-content collapse show">
                <div class="card-body">
                    <form class="form" id="saveCustomerForm" name="saveCustomerForm">
                    @csrf
                        <div class="form-body">

                            {{-- ── Basic Information ── --}}
                            <div class="cust-form-card">
                            <h4 class="form-section"><i><svg width="17" height="17" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.4" stroke="#FF6B1A" stroke-width="1.7"></circle><path d="M5 20c1.4-3.4 4-5 7-5s5.6 1.6 7 5" stroke="#FF6B1A" stroke-width="1.7" stroke-linecap="round"></path></svg></i> <span class="fs-tt">Basic Information<small>Name &amp; contact details</small></span></h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name<span class="text-danger">*</span></label>
                                        <input type="text" id="first_name" class="form-control" placeholder="Enter First Name" name="first_name">
                                        <div class="row"><div class="col-sm-12" data-validate="first_name"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="website">Website</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Website" name="website">
                                        <div class="row"><div class="col-sm-12" data-validate="website"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" id="email" class="form-control" placeholder="Enter Email" name="email">
                                        <div class="row"><div class="col-sm-12" data-validate="email"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mobile">Mobile</label>
                                        <input type="text" id="phone_input" class="form-control" placeholder="Enter Mobile" name="mobile">
                                        <div class="row"><div class="col-sm-12" data-validate="mobile"></div></div>
                                    </div>
                                </div>
                            </div>
                            </div>{{-- /cust-form-card --}}

                            {{-- ── Address ── --}}
                            <div class="cust-form-card">
                            <h4 class="form-section"><i class="feather icon-home"></i> <span class="fs-tt">Address<small>Where they receive deliveries</small></span></h4>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="address1">Address Line 1</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Address Line 1" name="address1">
                                        <div class="row"><div class="col-sm-12" data-validate="address1"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="address2">Address Line 2</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Address Line 2" name="address2">
                                        <div class="row"><div class="col-sm-12" data-validate="address2"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row cust-col2-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="country">Country</label>
                                        <input type="text" class="form-control" placeholder="Enter Country" name="country" id="country_input">
                                        <div class="row"><div class="col-sm-12" data-validate="country"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="state">State</label>
                                        <input type="text" class="form-control" placeholder="Enter State" name="state" id="state_input">
                                        <div class="row"><div class="col-sm-12" data-validate="state"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row cust-col2-row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" class="form-control" placeholder="Enter City" name="city" id="city_input">
                                        <div class="row"><div class="col-sm-12" data-validate="city"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="zipcode">Zipcode</label>
                                        <input type="text" id="zipcode" class="form-control" placeholder="Enter Zipcode" name="zipcode" maxlength=12>
                                        <div class="row"><div class="col-sm-12" data-validate="zipcode"></div></div>
                                    </div>
                                </div>
                            </div>
                            </div>{{-- /cust-form-card --}}

                            {{-- ── Accounting ── --}}
                            <div class="cust-form-card">
                            <h4 class="form-section"><i class="feather icon-briefcase"></i> <span class="fs-tt">Accounting<small>Tax, currency &amp; credit terms</small></span></h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="vat_number">VAT number</label>
                                        <input type="text" id="vat_number" class="form-control" placeholder="Enter Vat Number" name="vat_number">
                                        <div class="row"><div class="col-sm-12" data-validate="vat_number"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role">Currency<span class="text-danger">*</span></label>
                                        <select class="form-control select2" name="currency" id="currency">
                                            <option value="pound">GBP/Pound</option>
                                            <option value="dollar">Dollar</option>
                                            <option value="inr">INR</option>
                                        </select>
                                        <div class="row"><div class="col-sm-12" data-validate="currency"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="credit_limit">Credit Limit</label>
                                        <input type="text" id="credit_limit" class="form-control" placeholder="Enter Credit Limit" name="credit_limit">
                                        <div class="row"><div class="col-sm-12" data-validate="credit_limit"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="terms">Terms</label>
                                        <textarea id="terms" class="form-control" placeholder="Enter Terms" name="terms"></textarea>
                                        <div class="row"><div class="col-sm-12" data-validate="terms"></div></div>
                                    </div>
                                </div>
                            </div>
                            </div>{{-- /cust-form-card --}}

                            {{-- ── Others ── --}}
                            <div class="cust-form-card">
                            <h4 class="form-section"><i class="feather icon-file-text"></i> <span class="fs-tt">Others<small>Status &amp; internal notes</small></span></h4>
                            <div class="row cust-others-row">
                                <div class="col-lg-6 col-md-6">
                                    <div class="form-group">
                                        <label for="status">Active</label><br>
                                        <input type="checkbox" name="is_active" class="switch" id="is_active" data-switch-always checked />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="remarks">Remarks/Notes</label>
                                        <textarea id="remarks" class="form-control" placeholder="Enter Remarks/Notes" name="remarks"></textarea>
                                        <div class="row"><div class="col-sm-12" data-validate="remarks"></div></div>
                                    </div>
                                </div>
                            </div>
                            </div>{{-- /cust-form-card --}}

                        </div>

                        {{-- Mobile form actions card --}}
                        <div class="cust-form-actions">
                            <a href="{{ route('management.customers.view.index') }}" class="cfa-cancel">
                                <i class="fa fa-times" style="font-size:11px;"></i> Cancel
                            </a>
                            <button type="submit" class="cfa-save">
                                <i class="fa fa-check"></i> Save
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
