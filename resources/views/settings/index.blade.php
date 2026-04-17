@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }

/* ── Navigation card ──────────────────────────── */
.settings-nav-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #f0f0f0;
    box-shadow: 0 4px 24px rgba(0,0,0,0.07), 0 1px 3px rgba(0,0,0,0.04);
    margin-bottom: 24px;
    overflow: hidden;
    position: relative;
}
.settings-nav-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, #f97316 0%, #fb923c 55%, #fdba74 100%);
    border-radius: 18px 18px 0 0;
    z-index: 1;
}
/* ── Single horizontal bar: left = icon+title, right = tabs ── */
.settings-nav-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 16px 20px 16px 22px;
    background: linear-gradient(110deg, #fff9f5 0%, #fff 55%);
    flex-wrap: wrap;
}
.settings-nav-left {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-shrink: 0;
}
.settings-nav-icon {
    width: 46px; height: 46px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 5px 16px rgba(249,115,22,0.35), 0 2px 5px rgba(249,115,22,0.15);
}
/* ── Tabs on the right ── */
.settings-tabs {
    display: flex;
    align-items: center;
    gap: 3px;
    background: #f4f4f5;
    border-radius: 12px;
    padding: 4px;
    flex-shrink: 0;
    overflow-x: auto;
}
.settings-tabs::-webkit-scrollbar { display: none; }
.settings-tab {
    height: 36px;
    padding: 0 16px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    border: none;
    background: transparent;
    outline: none !important;
    box-shadow: none !important;
    white-space: nowrap;
    border-radius: 9px;
    border-bottom: none;
    transition: all 0.16s ease;
}
.settings-tab i { font-size: 13px; transition: color 0.16s; }
.settings-tab:hover { color: #f97316; background: rgba(255,255,255,0.8); }
.settings-tab.active {
    color: #fff;
    background: linear-gradient(135deg, #f97316, #ea580c);
    box-shadow: 0 3px 10px rgba(249,115,22,0.35) !important;
}
.settings-tab.active i { color: #fff; }
@media (max-width: 767px) {
    .settings-nav-bar { flex-direction: column; align-items: flex-start; gap: 10px; padding: 14px 16px; }
    .settings-tabs-wrap { position: relative; width: 100%; }
    .settings-tabs-wrap::after {
        content: '';
        position: absolute;
        right: 0; top: 0; bottom: 0;
        width: 36px;
        background: linear-gradient(to right, transparent, rgba(255,255,255,0.97));
        pointer-events: none;
        border-radius: 0 12px 12px 0;
        z-index: 2;
    }
    .settings-tabs { width: 100%; overflow-x: auto; scrollbar-width: none; }
    .settings-tabs::-webkit-scrollbar { display: none; }
}
@media (max-width: 767px) {
    .settings-nav-icon { width: 40px; height: 40px; border-radius: 12px; }
    .settings-tab { padding: 0 12px; font-size: 12px; height: 34px; border-radius: 8px; }
    .settings-tab i { font-size: 12px; }
}
@media (max-width: 520px) {
    .settings-nav-bar { padding: 12px 14px; gap: 10px; }
    .settings-nav-left { gap: 10px; }
    .settings-nav-icon { width: 36px; height: 36px; border-radius: 10px; }
    .settings-nav-icon i { font-size: 16px !important; }
    .settings-tabs {
        width: 100%;
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 6px;
        background: #f1f5f9;
        border-radius: 12px;
        padding: 4px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .settings-tabs::-webkit-scrollbar { display: none; }
    .settings-tab {
        flex-direction: row;
        flex-shrink: 0;
        height: 34px;
        padding: 0 12px;
        gap: 5px;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 600;
        background: transparent;
        border: none;
        color: #64748b;
        white-space: nowrap;
    }
    .settings-tab i { font-size: 12px !important; }
    .tab-label {
        display: inline;
        font-size: 12px !important;
        font-weight: 600;
        white-space: nowrap;
    }
    .settings-tab.active {
        background: linear-gradient(135deg, #f97316, #ea580c) !important;
        color: #fff !important;
        box-shadow: 0 3px 10px rgba(249,115,22,0.35) !important;
    }
    .settings-tab.active i { color: #fff !important; }
    .settings-tab.active .tab-label { color: #fff !important; }
}

/* ── Tab content ───────────────────────────────── */
.tab-content-section { display: none; }
.tab-content-section.active { display: block; }

/* ── Form shared ───────────────────────────────── */
.sform-label {
    font-size: 11px; font-weight: 700; color: #64748b;
    letter-spacing: 0.5px; text-transform: uppercase;
    margin-bottom: 6px; display: block;
}
.sform-control {
    width: 100%; height: 42px; border-radius: 10px;
    border: 1.5px solid #e2e8f0; padding: 0 14px; font-size: 13px;
    background: #f8fafc; color: #0f172a; outline: none; transition: all 0.2s;
    box-sizing: border-box;
}
.sform-control:focus { border-color: #f97316; background: #fff; box-shadow: none; }
.sform-control:disabled { opacity: 0.55; cursor: not-allowed; }
textarea.sform-control { height: auto; padding: 10px 14px; resize: vertical; }
.sform-section {
    font-size: 11px; font-weight: 700; color: #f97316; text-transform: uppercase;
    letter-spacing: 0.7px; margin: 24px 0 14px; padding-bottom: 8px;
    border-bottom: 1.5px solid #fff7ed; display: flex; align-items: center; gap: 7px;
}
.sform-card {
    background: #fff; border-radius: 16px; border: 1px solid #e5e7eb;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05); overflow: hidden;
}
.sform-card-header {
    padding: 18px 24px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 12px;
}
.sform-card-body { padding: 24px; }
.sform-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, #f97316, #fb923c);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(249,115,22,0.2);
}
.sform-btn-save {
    height: 42px; padding: 0 28px; border-radius: 10px; border: none;
    background: linear-gradient(135deg, #f97316, #ea580c); color: #fff;
    font-size: 13.5px; font-weight: 700; cursor: pointer; outline: none !important;
    box-shadow: 0 3px 10px rgba(249,115,22,0.3); display: inline-flex; align-items: center; gap: 7px;
    transition: opacity 0.15s;
}
.sform-btn-save:hover { opacity: 0.92; }

/* ── Password toggle ───────────────────────────── */
.pw-wrap { position: relative; }
.pw-input { padding-right: 42px !important; }
.pw-eye {
    position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
    cursor: pointer; color: #9ca3af; font-size: 14px;
    transition: color 0.15s; user-select: none;
}
.pw-eye:hover { color: #f97316; }

/* ── Mobile: My Account ─────────────────────────── */
@media (max-width: 767px) {
    /* Card base */
    .sform-card { border-radius: 16px !important; }
    .sform-card-header {
        padding: 16px 18px !important; gap: 12px !important;
        border-bottom: 1.5px solid #f1f5f9 !important;
    }
    .sform-card-header .sform-icon {
        width: 36px !important; height: 36px !important; border-radius: 10px !important; flex-shrink: 0 !important;
    }
    .sform-card-body { padding: 20px 18px !important; }

    /* Section labels */
    .sform-section {
        font-size: 10px !important; margin: 18px 0 12px !important;
        padding: 5px 10px !important; background: #fff7ed !important;
        border-radius: 8px !important; border-bottom: none !important;
        border-left: 3px solid #f97316 !important;
    }

    /* Field labels */
    .sform-label {
        font-size: 10px !important; margin-bottom: 6px !important;
        color: #94a3b8 !important; text-transform: uppercase !important;
        letter-spacing: 0.5px !important; font-weight: 700 !important;
    }

    /* Inputs */
    .sform-control {
        height: 46px !important; font-size: 13px !important;
        border-radius: 11px !important; padding: 0 14px !important;
        background: #f8fafc !important; border-color: #e2e8f0 !important;
    }
    .sform-control:focus { background: #fff !important; border-color: #f97316 !important; }
    .sform-control:disabled { background: #f1f5f9 !important; color: #94a3b8 !important; }

    /* 2-col grid */
    .sa-grid-2 { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 12px !important; }
    .sa-field { margin-bottom: 0 !important; }

    /* Row spacing between field groups */
    .sa-account-col-profile .row.g-3 { --bs-gutter-y: 0.9rem !important; }

    /* Profile photo row */
    .sa-photo-row {
        flex-direction: row !important; align-items: center !important;
        gap: 14px !important; margin-top: 6px !important;
    }
    .sa-photo-row img { width: 58px !important; height: 58px !important; }
    .sa-upload-box {
        padding: 12px 14px !important; border-radius: 11px !important;
        border-style: dashed !important; border-width: 1.5px !important;
        gap: 10px !important;
    }
    .sa-upload-box i { font-size: 16px !important; }
    .sa-upload-box div:first-child { font-size: 13px !important; font-weight: 600 !important; }
    .sa-upload-box div:last-child { font-size: 11px !important; margin-top: 2px !important; }

    /* Save button */
    .sa-btn-row {
        padding-top: 18px !important; margin-top: 20px !important;
        border-top: 1.5px solid #f1f5f9 !important;
    }
    .sa-btn-row .sform-btn-save {
        width: 100% !important; justify-content: center !important;
        height: 52px !important; font-size: 15px !important;
        border-radius: 14px !important; letter-spacing: 0.3px !important;
        box-shadow: 0 4px 14px rgba(249,115,22,0.3) !important;
    }

    /* Merge two cards — no gap, clean divider */
    .sa-account-row { --bs-gutter-y: 0 !important; row-gap: 0 !important; }
    .sa-account-col-profile, .sa-account-col-pw { padding-bottom: 0 !important; }
    .sa-profile-card {
        border-radius: 16px 16px 0 0 !important;
        border-bottom: none !important;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06) !important;
    }
    .sa-pw-card {
        border-radius: 0 0 16px 16px !important;
        border-top: 2px solid #fff7ed !important;
        box-shadow: 0 4px 16px rgba(0,0,0,0.07) !important;
    }

    /* Hide entire Change Password card on mobile — button is inside profile card */
    .sa-account-col-pw { display: none !important; }
    .sa-profile-card { border-radius: 16px !important; box-shadow: 0 2px 12px rgba(0,0,0,0.07) !important; }
    .sa-inline-pw-trigger { display: inline-flex !important; }

    /* Modal password fields */
    .sa-pw-fields { gap: 0 !important; }
    .sa-pw-field {
        padding: 14px !important; background: #fafafa !important;
        border: 1px solid #f0f0f0 !important; border-radius: 11px !important;
        margin-bottom: 10px !important;
    }
    .sa-pw-field:last-of-type { margin-bottom: 0 !important; }
    .sa-pw-field .sform-label { color: #64748b !important; margin-bottom: 6px !important; }
    .sa-pw-field .sform-control {
        background: #fff !important; border-color: #e2e8f0 !important; height: 44px !important;
    }
    #pwStrengthWrap, #mobPwStrengthWrap { margin-top: 6px !important; }
}

/* ── General tab: toggle rows ──────────────────── */
.stoggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 0;
    border-bottom: 1px solid #f1f5f9;
}
.stoggle-row:last-child { border-bottom: none; padding-bottom: 0; }
.stoggle-info { flex: 1; min-width: 0; }
.stoggle-label { font-size: 14px; font-weight: 600; color: #0f172a; }
.stoggle-desc { font-size: 12px; color: #94a3b8; margin-top: 3px; line-height: 1.45; }
.stoggle-switch {
    position: relative; width: 44px; height: 24px; flex-shrink: 0; margin-left: 20px;
}
.stoggle-switch input { opacity: 0; width: 0; height: 0; position: absolute; }
.stoggle-track {
    position: absolute; cursor: pointer; inset: 0;
    background: #e2e8f0; border-radius: 24px; transition: background 0.2s;
}
.stoggle-track::before {
    content: ''; position: absolute;
    width: 18px; height: 18px; left: 3px; bottom: 3px;
    background: #fff; border-radius: 50%;
    box-shadow: 0 1px 4px rgba(0,0,0,0.18);
    transition: transform 0.2s;
}
.stoggle-switch input:checked + .stoggle-track {
    background: linear-gradient(135deg, #f97316, #ea580c);
}
.stoggle-switch input:checked + .stoggle-track::before { transform: translateX(20px); }
.stoggle-switch input:disabled + .stoggle-track { opacity: 0.55; cursor: not-allowed; }

/* ── Delete Data tab ───────────────────────────── */
.sdd-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
@media (max-width: 991px) { .sdd-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 575px) { .sdd-grid { grid-template-columns: 1fr; } }
.sdd-card {
    border: 1.5px solid #fecaca;
    border-radius: 14px;
    padding: 20px;
    background: #fffbfb;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
    transition: all 0.15s;
}
.sdd-card:hover { border-color: #dc2626; background: #fff5f5; box-shadow: 0 4px 16px rgba(220,38,38,0.08); }
.sdd-card-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    display: flex; align-items: center; justify-content: center;
    color: #dc2626; font-size: 16px; flex-shrink: 0;
}
.sdd-card-title { font-size: 14px; font-weight: 700; color: #0f172a; }
.sdd-card-desc { font-size: 12px; color: #6b7280; line-height: 1.45; flex: 1; }
.sdd-delete-btn {
    margin-top: 8px;
    height: 36px; padding: 0 18px; border-radius: 8px; border: 1.5px solid #dc2626;
    background: transparent; color: #dc2626;
    font-size: 12px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    transition: all 0.15s;
}
.sdd-delete-btn:hover { background: #dc2626; color: #fff; }

/* ── Tablet view: Change Password button in header ── */
@media (min-width: 768px) and (max-width: 1199px) {
    .sa-account-col-pw { display: none !important; }
    .sa-inline-pw-trigger { display: inline-flex !important; }
    .sa-account-row { --bs-gutter-y: 0 !important; }
    .sa-account-col-profile { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
    .sa-profile-card { border-radius: 16px !important; }
}

/* ── Password modal — mobile (bottom sheet) ── */
.sa-pw-modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.55);
    z-index: 99998; display: none; align-items: flex-end; justify-content: center;
}
.sa-pw-modal-box {
    background: #fff; width: 100%;
    border-radius: 24px 24px 0 0;
    padding: 26px 20px 40px; max-height: 92vh; overflow-y: auto;
    box-shadow: 0 -8px 40px rgba(0,0,0,0.18);
}

/* ── Password modal — tablet (centered dialog) ── */
@media (min-width: 768px) and (max-width: 1199px) {
    .sa-pw-modal-overlay {
        align-items: center !important;
        justify-content: center !important;
        padding: 24px !important;
    }
    .sa-pw-modal-box {
        width: 100% !important; max-width: 460px !important;
        border-radius: 20px !important;
        padding: 32px 32px 36px !important;
        max-height: 88vh !important;
        box-shadow: 0 20px 60px rgba(0,0,0,0.22) !important;
        animation: modalFadeIn 0.2s ease;
    }
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.96); }
        to   { opacity: 1; transform: scale(1); }
    }
    /* Bigger inputs inside modal on tablet */
    .sa-pw-modal-box .sform-control { height: 48px !important; font-size: 14px !important; }
    .sa-pw-modal-box .sform-label { font-size: 11px !important; }
    .sa-pw-modal-box #mobPwSubmitBtn { height: 52px !important; font-size: 15px !important; border-radius: 14px !important; }
}
</style>
@endpush

@section('content')
<section>

    {{-- ── Unified nav card ────────────────────────── --}}
    <div class="settings-nav-card">
        <div class="settings-nav-bar">
            {{-- Left: icon + title --}}
            <div class="settings-nav-left">
                <div class="settings-nav-icon">
                    <i class="fa fa-cog" style="font-size:18px;color:#fff;"></i>
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:17px;font-weight:800;color:#0f172a;line-height:1.2;letter-spacing:-0.3px;">Settings</span>
                        <span style="font-size:9.5px;font-weight:700;color:#f97316;background:#fff7ed;border:1px solid #fed7aa;padding:2px 8px;border-radius:20px;letter-spacing:0.5px;text-transform:uppercase;">Admin</span>
                    </div>
                    <div style="font-size:12px;color:#94a3b8;font-weight:500;margin-top:2px;">Roles, permissions, users &amp; account</div>
                </div>
            </div>
            {{-- Right: tabs --}}
            <div class="settings-tabs-wrap">
            <div class="settings-tabs">
                <button class="settings-tab active" onclick="switchSettingsTab('roles', this)">
                    <i class="fa fa-shield"></i> <span class="tab-label">Roles</span>
                </button>
                <button class="settings-tab" onclick="switchSettingsTab('permissions', this)">
                    <i class="fa fa-lock"></i> <span class="tab-label">Permissions</span>
                </button>
                <button class="settings-tab" onclick="switchSettingsTab('users', this)">
                    <i class="fa fa-user"></i> <span class="tab-label">Users</span>
                </button>
                <button class="settings-tab" onclick="switchSettingsTab('account', this)">
                    <i class="fa fa-cog"></i> <span class="tab-label">Account</span>
                </button>
                <button class="settings-tab" onclick="switchSettingsTab('general', this)">
                    <i class="fa fa-sliders"></i> <span class="tab-label">General</span>
                </button>
                <button class="settings-tab" onclick="switchSettingsTab('deletedata', this)">
                    <i class="fa fa-trash"></i> <span class="tab-label">Delete Data</span>
                </button>
            </div>
            </div>
        </div>
    </div>

    {{-- ── Roles Tab ────────────────────────────────── --}}
    <div id="tab-roles" class="tab-content-section active">
        <div id="roles-index-app"
            data-currency="{{ env('CURRENCY_SYMBOL', '£') }}"
            data-list-api="{{ route('management.roles.role.view.list') }}"
        ></div>
    </div>

    {{-- ── Permissions Tab ─────────────────────────── --}}
    <div id="tab-permissions" class="tab-content-section">
        <div id="roles-permission-index-app"
            data-currency="{{ env('CURRENCY_SYMBOL', '£') }}"
            data-list-api="{{ route('management.roles.permission.view.list') }}"
        ></div>
    </div>

    {{-- ── Users Tab ────────────────────────────────── --}}
    <div id="tab-users" class="tab-content-section">
        <div id="users-index-app"
            data-currency="{{ env('CURRENCY_SYMBOL', '£') }}"
            data-list-api="{{ route('management.users.view.list') }}"
        ></div>
    </div>

    {{-- ── My Account Tab ───────────────────────────── --}}
    <div id="tab-account" class="tab-content-section">
        <div class="row g-3 sa-account-row">

            {{-- Profile Info --}}
            <div class="col-lg-7 sa-account-col-profile">
                <div class="sform-card sa-profile-card">
                    <div class="sform-card-header" style="display:flex;align-items:center;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <div class="sform-icon"><i class="fa fa-user" style="font-size:13px;color:#fff;"></i></div>
                            <div>
                                <div style="font-size:14px;font-weight:800;color:#0f172a;">Profile Information</div>
                                <div style="font-size:11px;color:#94a3b8;margin-top:1px;">Update your name, username and address</div>
                            </div>
                        </div>
                        {{-- Mobile-only Change Password button in header --}}
                        <button type="button" onclick="openPwModal()" class="sa-inline-pw-trigger"
                            style="display:none;align-items:center;gap:5px;height:34px;padding:0 12px;border:1.5px solid #f97316;border-radius:10px;background:#fff7ed;color:#f97316;font-size:11px;font-weight:700;cursor:pointer;outline:none;white-space:nowrap;flex-shrink:0;">
                            <i class="fa fa-lock" style="font-size:10px;"></i> Change Password
                        </button>
                    </div>
                    <div class="sform-card-body">
                        <form id="updateAdminForm" enctype="multipart/form-data">
                        @csrf
                            {{-- Name row --}}
                            <div class="sa-grid-2 row g-3">
                                <div class="col-md-6 sa-field">
                                    <label class="sform-label">First Name <span style="color:#f97316;">*</span></label>
                                    <input type="text" name="first_name" class="sform-control" placeholder="First name" value="{{ $adminData->first_name ?? '' }}">
                                    <div data-validate="first_name" style="font-size:11px;color:#dc2626;margin-top:3px;"></div>
                                </div>
                                <div class="col-md-6 sa-field">
                                    <label class="sform-label">Last Name <span style="color:#f97316;">*</span></label>
                                    <input type="text" name="last_name" class="sform-control" placeholder="Last name" value="{{ $adminData->last_name ?? '' }}">
                                    <div data-validate="last_name" style="font-size:11px;color:#dc2626;margin-top:3px;"></div>
                                </div>
                            </div>
                            {{-- Email + Username --}}
                            <div class="sa-grid-2 row g-3" style="margin-top:10px;">
                                <div class="col-md-6 sa-field">
                                    <label class="sform-label">Email</label>
                                    <input type="email" class="sform-control" value="{{ $adminData->email ?? '' }}" disabled>
                                    <div style="font-size:10px;color:#94a3b8;margin-top:3px;">Email cannot be changed</div>
                                </div>
                                <div class="col-md-6 sa-field">
                                    <label class="sform-label">Username <span style="color:#f97316;">*</span></label>
                                    <input type="text" name="username" class="sform-control" placeholder="Unique username" value="{{ $adminData->username ?? '' }}">
                                    <div data-validate="username" style="font-size:11px;color:#dc2626;margin-top:3px;"></div>
                                </div>
                            </div>
                            {{-- City + Zipcode --}}
                            <div class="sa-grid-2 row g-3" style="margin-top:10px;">
                                <div class="col-md-6 sa-field">
                                    <label class="sform-label">City</label>
                                    <input type="text" name="city" class="sform-control" placeholder="City" value="{{ $adminData->city ?? '' }}">
                                </div>
                                <div class="col-md-6 sa-field">
                                    <label class="sform-label">Zipcode</label>
                                    <input type="text" name="zipcode" class="sform-control" placeholder="Zipcode" value="{{ $adminData->zipcode ?? '' }}">
                                </div>
                            </div>
                            {{-- Address --}}
                            <div class="row g-3" style="margin-top:10px;">
                                <div class="col-12">
                                    <label class="sform-label">Address</label>
                                    <input type="text" name="address" class="sform-control" placeholder="Address" value="{{ $adminData->address ?? '' }}">
                                </div>
                            </div>
                            {{-- Profile Photo --}}
                            <div class="row g-3" style="margin-top:10px;">
                                <div class="col-12">
                                    <label class="sform-label">Profile Photo</label>
                                    <div class="sa-photo-row" style="display:flex;align-items:center;gap:16px;margin-top:4px;">
                                        <div id="avatarWrap" style="flex-shrink:0;position:relative;">
                                            <img id="avatarPreview"
                                                src="{{ !empty($adminData->image) ? asset('storage/'.$adminData->image) : asset('img/1024px-User_icon_2.svg.png') }}"
                                                style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:3px solid #f97316;box-shadow:0 2px 8px rgba(249,115,22,0.2);">
                                            <div style="position:absolute;bottom:0;right:0;width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,#f97316,#ea580c);display:flex;align-items:center;justify-content:center;border:2px solid #fff;cursor:pointer;box-shadow:0 1px 4px rgba(249,115,22,0.4);" onclick="document.getElementById('profileImage').click();">
                                                <i class="fa fa-camera" style="font-size:9px;color:#fff;"></i>
                                            </div>
                                        </div>
                                        <div style="flex:1;">
                                            <div class="sa-upload-box" onclick="document.getElementById('profileImage').click();"
                                                style="border:2px dashed #e2e8f0;border-radius:12px;padding:14px 18px;background:#f8fafc;cursor:pointer;transition:all 0.2s;display:flex;align-items:center;gap:10px;"
                                                onmouseover="this.style.borderColor='#f97316';this.style.background='#fff7ed';"
                                                onmouseout="this.style.borderColor='#e2e8f0';this.style.background='#f8fafc';">
                                                <i class="fa fa-upload" style="font-size:15px;color:#f97316;flex-shrink:0;"></i>
                                                <div>
                                                    <div id="uploadFileName" style="font-size:13px;font-weight:600;color:#374151;">Click to upload photo</div>
                                                    <div style="font-size:11px;color:#94a3b8;margin-top:1px;">JPG, PNG or GIF — max 2MB</div>
                                                </div>
                                            </div>
                                            <input type="file" name="image" id="profileImage" accept="image/jpg,image/jpeg,image/png,image/gif" style="display:none;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="sa-btn-row" style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid #f1f5f9;">
                                <button type="submit" class="sform-btn-save">
                                    <i class="fa fa-check"></i> Save Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Change Password --}}
            <div class="col-lg-5 sa-account-col-pw">
                <div class="sform-card sa-pw-card">
                    <div class="sform-card-header">
                        <div class="sform-icon"><i class="fa fa-lock" style="font-size:13px;color:#fff;"></i></div>
                        <div>
                            <div style="font-size:14px;font-weight:800;color:#0f172a;">Change Password</div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:1px;">Update your account password</div>
                        </div>
                    </div>
                    {{-- Mobile-only trigger button --}}
                    <div class="sa-pw-trigger" style="display:none;padding:16px 20px;">
                        <button type="button" onclick="openPwModal()"
                            style="width:100%;height:50px;border:none;border-radius:14px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;font-size:15px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:9px;box-shadow:0 4px 14px rgba(249,115,22,0.35);outline:none;">
                            <i class="fa fa-lock" style="font-size:14px;"></i> Change Password
                        </button>
                    </div>
                    <div class="sform-card-body" id="sa-pw-card-body">
                        <form id="updatePasswordForm">
                        @csrf
                            <div class="sa-pw-fields" style="display:flex;flex-direction:column;gap:14px;">
                                <div class="sa-pw-field">
                                    <label class="sform-label">Current Password <span style="color:#f97316;">*</span></label>
                                    <div class="pw-wrap">
                                        <input type="password" name="current_password" id="current_password" class="sform-control pw-input" placeholder="Enter current password">
                                        <span class="pw-eye" onclick="togglePw('current_password',this)"><i class="fa fa-eye-slash"></i></span>
                                    </div>
                                    <div data-validate="current_password" style="font-size:11px;color:#dc2626;margin-top:3px;"></div>
                                </div>
                                <div class="sa-pw-field">
                                    <label class="sform-label">New Password <span style="color:#f97316;">*</span></label>
                                    <div class="pw-wrap">
                                        <input type="password" name="new_password" id="new_password" class="sform-control pw-input" placeholder="Min. 6 characters">
                                        <span class="pw-eye" onclick="togglePw('new_password',this)"><i class="fa fa-eye-slash"></i></span>
                                    </div>
                                    <div data-validate="new_password" style="font-size:11px;color:#dc2626;margin-top:3px;"></div>
                                </div>
                                <div class="sa-pw-field">
                                    <label class="sform-label">Confirm Password <span style="color:#f97316;">*</span></label>
                                    <div class="pw-wrap">
                                        <input type="password" name="confirm_password" id="confirm_password" class="sform-control pw-input" placeholder="Repeat new password">
                                        <span class="pw-eye" onclick="togglePw('confirm_password',this)"><i class="fa fa-eye-slash"></i></span>
                                    </div>
                                    <div data-validate="confirm_password" style="font-size:11px;color:#dc2626;margin-top:3px;"></div>
                                </div>

                                {{-- Strength bar --}}
                                <div id="pwStrengthWrap" style="display:none;">
                                    <div style="font-size:11px;color:#64748b;margin-bottom:5px;">Strength: <span id="pwStrengthLabel" style="font-weight:700;"></span></div>
                                    <div style="height:4px;background:#e2e8f0;border-radius:10px;overflow:hidden;">
                                        <div id="pwStrengthBar" style="height:100%;width:0;border-radius:10px;transition:all 0.3s;"></div>
                                    </div>
                                </div>

                                <div class="sa-btn-row" style="display:flex;justify-content:flex-end;padding-top:8px;border-top:1px solid #f1f5f9;">
                                    <button type="submit" class="sform-btn-save">
                                        <i class="fa fa-lock"></i> Update Password
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Delete Data Tab ────────────────────────────── --}}
    <div id="tab-deletedata" class="tab-content-section">
        <div class="sform-card">
            <div class="sform-card-header">
                <div class="sform-icon" style="background:linear-gradient(135deg,#dc2626,#b91c1c);"><i class="fa fa-trash" style="font-size:13px;color:#fff;"></i></div>
                <div>
                    <div style="font-size:14px;font-weight:800;color:#0f172a;">Delete Data</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:1px;">Permanently remove data from specific sections</div>
                </div>
            </div>
            <div class="sform-card-body">
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:20px;display:flex;align-items:flex-start;gap:10px;">
                    <i class="fa fa-exclamation-triangle" style="color:#dc2626;font-size:14px;margin-top:1px;flex-shrink:0;"></i>
                    <div style="font-size:12px;color:#991b1b;line-height:1.5;"><strong>Warning:</strong> This action is <strong>irreversible</strong>. All selected data will be permanently deleted from the database. Please ensure you have a backup before proceeding.</div>
                </div>
                <div class="sdd-grid">
                    <div class="sdd-card">
                        <div class="sdd-card-icon"><i class="fa fa-line-chart"></i></div>
                        <div class="sdd-card-title">Sales Data</div>
                        <div class="sdd-card-desc">Customer invoices, invoice items, orders, and invoice payments</div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('sales','Sales Data')"><i class="fa fa-trash"></i> Delete</button>
                    </div>
                    <div class="sdd-card">
                        <div class="sdd-card-icon"><i class="fa fa-shopping-cart"></i></div>
                        <div class="sdd-card-title">Purchases Data</div>
                        <div class="sdd-card-desc">Supplier invoices, invoice items, orders, and supplier payments</div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('purchases','Purchases Data')"><i class="fa fa-trash"></i> Delete</button>
                    </div>
                    <div class="sdd-card">
                        <div class="sdd-card-icon"><i class="fa fa-users"></i></div>
                        <div class="sdd-card-title">Customers</div>
                        <div class="sdd-card-desc">All customer records from the database</div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('customers','Customers')"><i class="fa fa-trash"></i> Delete</button>
                    </div>
                    <div class="sdd-card">
                        <div class="sdd-card-icon"><i class="fa fa-truck"></i></div>
                        <div class="sdd-card-title">Suppliers</div>
                        <div class="sdd-card-desc">All supplier records from the database</div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('suppliers','Suppliers')"><i class="fa fa-trash"></i> Delete</button>
                    </div>
                    <div class="sdd-card">
                        <div class="sdd-card-icon"><i class="fa fa-cube"></i></div>
                        <div class="sdd-card-title">Products</div>
                        <div class="sdd-card-desc">All product records and product info from the database</div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('products','Products')"><i class="fa fa-trash"></i> Delete</button>
                    </div>
                    <div class="sdd-card">
                        <div class="sdd-card-icon"><i class="fa fa-archive"></i></div>
                        <div class="sdd-card-title">Stock</div>
                        <div class="sdd-card-desc">All stock movements and closing stock records</div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('stock','Stock')"><i class="fa fa-trash"></i> Delete</button>
                    </div>
                    <div class="sdd-card">
                        <div class="sdd-card-icon"><i class="fa fa-credit-card"></i></div>
                        <div class="sdd-card-title">Payments</div>
                        <div class="sdd-card-desc">All customer payment records and payment entries</div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('payments','Payments')"><i class="fa fa-trash"></i> Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── General Tab ─────────────────────────────── --}}
    <div id="tab-general" class="tab-content-section">
        <div class="sform-card">
            <div class="sform-card-header">
                <div class="sform-icon"><i class="fa fa-sliders" style="font-size:13px;color:#fff;"></i></div>
                <div>
                    <div style="font-size:14px;font-weight:800;color:#0f172a;">General Settings</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:1px;">Configure application-wide options</div>
                </div>
            </div>
            <div class="sform-card-body">
                <div class="stoggle-row">
                    <div class="stoggle-info">
                        <div class="stoggle-label">Show Suppliers</div>
                        <div class="stoggle-desc">Enable or disable the Suppliers section across the application</div>
                    </div>
                    <label class="stoggle-switch">
                        <input type="checkbox" id="toggleShowSuppliers" {{ $showSuppliers ? 'checked' : '' }}>
                        <span class="stoggle-track"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>

</section>

{{-- ── Delete Data Confirmation Modal ──────────────────── --}}
<div id="deleteConfirmModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:99998;display:none;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:440px;padding:28px 24px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
            <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#dc2626,#b91c1c);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa fa-trash" style="font-size:16px;color:#fff;"></i>
            </div>
            <div>
                <div style="font-size:16px;font-weight:800;color:#0f172a;">Confirm Delete</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:1px;">This action cannot be undone</div>
            </div>
        </div>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:20px;">
            <div style="font-size:13px;color:#991b1b;line-height:1.5;">Are you sure you want to delete all <strong id="deleteConfirmName">data</strong>? This action is <strong>permanent</strong> and cannot be undone.</div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="closeDeleteConfirm()" style="height:40px;padding:0 20px;border-radius:10px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:13px;font-weight:600;cursor:pointer;">Cancel</button>
            <button id="deleteConfirmBtn" onclick="executeDelete()" style="height:40px;padding:0 20px;border-radius:10px;border:none;background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;"><i class="fa fa-trash"></i> Yes, Delete</button>
        </div>
    </div>
</div>

{{-- ── Mobile Change Password Modal ──────────────────── --}}
<div id="pwModal" class="sa-pw-modal-overlay" style="display:none;" onclick="closePwModalOverlay(event)">
    <div class="sa-pw-modal-box">
        {{-- Modal Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#f97316,#fb923c);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 10px rgba(249,115,22,0.3);">
                    <i class="fa fa-lock" style="font-size:14px;color:#fff;"></i>
                </div>
                <div>
                    <div style="font-size:16px;font-weight:800;color:#0f172a;line-height:1.2;">Change Password</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:1px;">Update your account password</div>
                </div>
            </div>
            <button type="button" onclick="closePwModal()"
                style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:50%;font-size:18px;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;">&times;</button>
        </div>
        {{-- Modal Form --}}
        <form id="mobUpdatePasswordForm">
            @csrf
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div class="sa-pw-field">
                    <label class="sform-label">Current Password <span style="color:#f97316;">*</span></label>
                    <div class="pw-wrap">
                        <input type="password" name="current_password" id="mob_current_password" class="sform-control pw-input" placeholder="Enter current password">
                        <span class="pw-eye" onclick="togglePw('mob_current_password',this)"><i class="fa fa-eye-slash"></i></span>
                    </div>
                    <div data-validate="current_password" style="font-size:11px;color:#dc2626;margin-top:3px;"></div>
                </div>
                <div class="sa-pw-field">
                    <label class="sform-label">New Password <span style="color:#f97316;">*</span></label>
                    <div class="pw-wrap">
                        <input type="password" name="new_password" id="mob_new_password" class="sform-control pw-input" placeholder="Min. 6 characters">
                        <span class="pw-eye" onclick="togglePw('mob_new_password',this)"><i class="fa fa-eye-slash"></i></span>
                    </div>
                    <div data-validate="new_password" style="font-size:11px;color:#dc2626;margin-top:3px;"></div>
                </div>
                <div class="sa-pw-field">
                    <label class="sform-label">Confirm Password <span style="color:#f97316;">*</span></label>
                    <div class="pw-wrap">
                        <input type="password" name="confirm_password" id="mob_confirm_password" class="sform-control pw-input" placeholder="Repeat new password">
                        <span class="pw-eye" onclick="togglePw('mob_confirm_password',this)"><i class="fa fa-eye-slash"></i></span>
                    </div>
                    <div data-validate="confirm_password" style="font-size:11px;color:#dc2626;margin-top:3px;"></div>
                </div>
                {{-- Strength bar --}}
                <div id="mobPwStrengthWrap" style="display:none;">
                    <div style="font-size:11px;color:#64748b;margin-bottom:5px;">Strength: <span id="mobPwStrengthLabel" style="font-weight:700;"></span></div>
                    <div style="height:4px;background:#e2e8f0;border-radius:10px;overflow:hidden;">
                        <div id="mobPwStrengthBar" style="height:100%;width:0;border-radius:10px;transition:all 0.3s;"></div>
                    </div>
                </div>
                <button type="submit" id="mobPwSubmitBtn"
                    style="width:100%;height:50px;border:none;border-radius:14px;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;font-size:15px;font-weight:700;cursor:pointer;margin-top:6px;box-shadow:0 4px 14px rgba(249,115,22,0.3);">
                    <i class="fa fa-lock"></i> Update Password
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
{{-- Password form handled via custom JS below --}}

<script>
/* ── Password visibility toggle ───────────────── */
function togglePw(fieldId, btn) {
    var input = document.getElementById(fieldId);
    var icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa fa-eye';
        btn.style.color = '#f97316';
    } else {
        input.type = 'password';
        icon.className = 'fa fa-eye-slash';
        btn.style.color = '';
    }
}

/* ── Inline toast ──────────────────────────────── */
function showSettingsToast(message, type) {
    var existing = document.getElementById('settings-toast');
    if (existing) existing.remove();
    var bg   = type === 'success' ? '#059669' : '#dc2626';
    var icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';
    var el = document.createElement('div');
    el.id = 'settings-toast';
    el.style.cssText = 'position:fixed;top:24px;right:24px;z-index:99999;background:'+bg+';color:#fff;padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 6px 24px rgba(0,0,0,0.15);display:flex;align-items:center;gap:10px;max-width:340px;transition:opacity 0.3s;';
    el.innerHTML = '<i class="fa '+icon+'" style="font-size:16px;"></i><span>'+message+'</span>';
    document.body.appendChild(el);
    setTimeout(function(){ el.style.opacity='0'; setTimeout(function(){ el.remove(); }, 350); }, 3000);
}

/* ── Generic AJAX form helper (no redirect) ──────── */
function handleSettingsForm(formId, url) {
    $('#'+formId).submit(function(e){
        e.preventDefault();
        $('#'+formId+' [data-validate]').html('');
        var btn = $('#'+formId+' button[type=submit]');
        btn.prop('disabled', true);
        $.ajax({
            url: url,
            method: 'POST',
            data: new FormData(document.getElementById(formId)),
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success === false) {
                    if (typeof response.form_errors !== 'undefined') {
                        $.each(response.form_errors, function(key, msgs){
                            $('#'+formId+' [data-validate="'+key+'"]').html(msgs[0]);
                        });
                    } else {
                        showSettingsToast(response.payload, 'error');
                    }
                } else {
                    showSettingsToast(response.payload.message || 'Saved successfully!', 'success');
                    if (formId === 'updatePasswordForm') {
                        document.getElementById(formId).reset();
                        document.getElementById('pwStrengthWrap').style.display = 'none';
                    }
                    if (formId === 'updateAdminForm') {
                        // Sync navbar avatar
                        var navImg     = document.getElementById('navbarAvatarImg');
                        var navInitial = document.getElementById('navbarAvatarInitial');
                        var imageUrl   = response.payload && response.payload.image_url ? response.payload.image_url : null;
                        if (navImg && imageUrl) {
                            navImg.onerror = function(){ this.style.display='none'; if(navInitial) navInitial.style.display='flex'; };
                            navImg.src              = imageUrl;
                            navImg.style.display    = 'block';
                            navImg.style.width      = '36px';
                            navImg.style.height     = '36px';
                            navImg.style.borderRadius = '50%';
                            navImg.style.objectFit  = 'cover';
                            if (navInitial) navInitial.style.display = 'none';
                        }
                        // Sync navbar username
                        var navName = document.getElementById('navbarUserName');
                        var firstNameInput = document.querySelector('#updateAdminForm [name="first_name"]');
                        if (navName && firstNameInput && firstNameInput.value.trim()) {
                            var fname = firstNameInput.value.trim();
                            navName.textContent = fname.charAt(0).toUpperCase() + fname.slice(1);
                        }
                        // Sync navbar initial letter if shown
                        if (navInitial && firstNameInput && firstNameInput.value.trim()) {
                            navInitial.textContent = firstNameInput.value.trim().charAt(0).toUpperCase();
                        }
                    }
                }
                btn.prop('disabled', false);
            },
            error: function(){
                showSettingsToast('Something went wrong. Please try again.', 'error');
                btn.prop('disabled', false);
            }
        });
    });
}

/* ── Mobile password modal ─────────────────────── */
function openPwModal() {
    document.getElementById('mobUpdatePasswordForm').reset();
    document.getElementById('mobPwStrengthWrap').style.display = 'none';
    document.querySelectorAll('#mobUpdatePasswordForm [data-validate]').forEach(function(el){ el.innerHTML=''; });
    document.getElementById('pwModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closePwModal() {
    document.getElementById('pwModal').style.display = 'none';
    document.body.style.overflow = '';
}
function closePwModalOverlay(e) {
    if (e.target.id === 'pwModal') closePwModal();
}

/* ── Profile form — no redirect ─────────────────── */
$(document).ready(function(){
    handleSettingsForm('updatePasswordForm', "{{ route('management.settings.password.update') }}");
    handleSettingsForm('updateAdminForm', "{{ route('admin.edit.update') }}");

    /* Mobile modal password form */
    $('#mobUpdatePasswordForm').submit(function(e){
        e.preventDefault();
        $('#mobUpdatePasswordForm [data-validate]').html('');
        var btn = $('#mobPwSubmitBtn');
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Updating...');
        $.ajax({
            url: "{{ route('management.settings.password.update') }}",
            method: 'POST',
            data: new FormData(document.getElementById('mobUpdatePasswordForm')),
            processData: false, contentType: false,
            success: function(response) {
                if (response.success === false) {
                    if (typeof response.form_errors !== 'undefined') {
                        $.each(response.form_errors, function(key, msgs){
                            $('#mobUpdatePasswordForm [data-validate="'+key+'"]').html(msgs[0]);
                        });
                    } else {
                        showSettingsToast(response.payload, 'error');
                    }
                } else {
                    showSettingsToast(response.payload.message || 'Password updated!', 'success');
                    document.getElementById('mobUpdatePasswordForm').reset();
                    document.getElementById('mobPwStrengthWrap').style.display = 'none';
                    closePwModal();
                }
                btn.prop('disabled', false).html('<i class="fa fa-lock"></i> Update Password');
            },
            error: function(){
                showSettingsToast('Something went wrong. Please try again.', 'error');
                btn.prop('disabled', false).html('<i class="fa fa-lock"></i> Update Password');
            }
        });
    });
});

function switchSettingsTab(tabName, btn) {
    document.querySelectorAll('.tab-content-section').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.settings-tab').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tabName).classList.add('active');
    btn.classList.add('active');
}

(function(){
    var validTabs = ['roles','permissions','users','account','general','deletedata'];
    // Check ?tab= query param first, then #hash
    var urlParams = new URLSearchParams(window.location.search);
    var tab = urlParams.get('tab') || window.location.hash.replace('#','');
    if (tab && validTabs.includes(tab)) {
        var btn = document.querySelector('.settings-tab[onclick*="' + tab + '"]');
        if (btn) switchSettingsTab(tab, btn);
    }
})();

/* ── Supplier toggle ────────────────────────────── */
(function(){
    var toggle = document.getElementById('toggleShowSuppliers');
    if (!toggle) return;
    toggle.addEventListener('change', function(){
        var checked = this.checked;
        toggle.disabled = true;
        $.ajax({
            url: "{{ route('general_settings.save.save') }}",
            method: 'POST',
            data: { _token: "{{ csrf_token() }}", show_suppliers: checked ? 1 : 0 },
            success: function(){
                showSettingsToast('Supplier setting updated.', 'success');
            },
            error: function(){
                toggle.checked = !checked;
                showSettingsToast('Something went wrong. Please try again.', 'error');
            },
            complete: function(){ toggle.disabled = false; }
        });
    });
})();

/* ── Delete Data ──────────────────────────────────── */
var _ddSection = null, _ddName = null;
function openDeleteConfirm(section, name) {
    _ddSection = section; _ddName = name;
    document.getElementById('deleteConfirmName').textContent = name;
    document.getElementById('deleteConfirmModal').style.display = 'flex';
}
function closeDeleteConfirm() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    _ddSection = null; _ddName = null;
}
function executeDelete() {
    if (!_ddSection) return;
    var btn = $('#deleteConfirmBtn');
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Deleting...');
    $.ajax({
        url: "{{ route('management.settings.data.delete') }}",
        method: 'POST',
        data: { _token: "{{ csrf_token() }}", section: _ddSection },
        success: function(r) {
            closeDeleteConfirm();
            showSettingsToast(r.message || 'Data deleted successfully.', 'success');
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong.';
            showSettingsToast(msg, 'error');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fa fa-trash"></i> Yes, Delete');
        }
    });
}

document.getElementById('profileImage') && document.getElementById('profileImage').addEventListener('change', function(){
    var file = this.files[0];
    if (!file) return;
    document.getElementById('uploadFileName').textContent = file.name;
    var reader = new FileReader();
    reader.onload = function(e){ document.getElementById('avatarPreview').src = e.target.result; };
    reader.readAsDataURL(file);
});

function calcPwStrength(pw, barId, lblId, wrapId) {
    var wrap = document.getElementById(wrapId);
    var bar  = document.getElementById(barId);
    var lbl  = document.getElementById(lblId);
    if (!pw) { wrap.style.display='none'; return; }
    wrap.style.display='block';
    var score = 0;
    if (pw.length >= 6)  score++;
    if (pw.length >= 10) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^a-zA-Z0-9]/.test(pw)) score++;
    var levels = [
        {w:'20%',color:'#dc2626',text:'Very Weak'},{w:'40%',color:'#f97316',text:'Weak'},
        {w:'60%',color:'#f59e0b',text:'Fair'},{w:'80%',color:'#22c55e',text:'Strong'},
        {w:'100%',color:'#16a34a',text:'Very Strong'},
    ];
    var l = levels[score-1] || levels[0];
    bar.style.width=l.w; bar.style.background=l.color;
    lbl.textContent=l.text; lbl.style.color=l.color;
}

document.getElementById('mob_new_password') && document.getElementById('mob_new_password').addEventListener('input', function(){
    calcPwStrength(this.value,'mobPwStrengthBar','mobPwStrengthLabel','mobPwStrengthWrap');
});

document.getElementById('new_password') && document.getElementById('new_password').addEventListener('input', function(){
    calcPwStrength(this.value,'pwStrengthBar','pwStrengthLabel','pwStrengthWrap');
});
</script>
@endpush
