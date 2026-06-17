@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }

/* Reserve the vertical scrollbar track at all times so switching between a tall tab (e.g. Roles)
   and a short one (e.g. General) does NOT add/remove the scrollbar. Without this, the scrollbar
   appearing/disappearing changes the viewport width by ~15px and the whole card visibly shifts.
   scrollbar-gutter is the modern fix; overflow-y:scroll is the universal fallback. */
html { scrollbar-gutter: stable; overflow-y: scroll; }

/* ── Navigation card ──────────────────────────── */
/* Card — EXACT spec: white, 14px radius, hairline border, soft shadow, padding 18px 22px 16px. */
.settings-nav-card {
    background: #fff;
    border-radius: 16px 16px 0 0;
    border: 1px solid #eaecf2;
    border-bottom: none;
    box-shadow: none;
    margin-bottom: 0;
    overflow: hidden;
    position: relative;
    padding: 18px 22px 0;
}
/* Each tab section sits flush against the nav card above it. */
.tab-content-section.active { margin-top: 0; }

/* Import Data header icon: desktop/tablet shows the default icon; the mobile-only
   variant is hidden here and revealed inside the ≤767px media query. */
#tab-importdata .sform-card-header .sform-icon .sform-icon-mobile { display: none; }

/* Blade-rendered tabs (Account / General / Delete Data / Import Data) get the
   unified card-bottom treatment, so they sit flush below the Settings nav card. */
#tab-account.tab-content-section,
#tab-general.tab-content-section,
#tab-deletedata.tab-content-section,
#tab-importdata.tab-content-section {
    background: #fff;
    border: 1px solid #eaecf2;
    border-top: none;
    border-radius: 0 0 16px 16px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.04);
    overflow: hidden;
}
/* Strip the inner sform-card boxes so they appear as sections within the merged card. */
#tab-account .sform-card,
#tab-general .sform-card,
#tab-deletedata .sform-card,
#tab-importdata .sform-card {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    overflow: visible !important;
}
#tab-account .sa-account-row { margin: 0; --bs-gutter-x: 0; }
@media (min-width: 992px) {
    #tab-account .sa-account-col-pw {
        border-left: 1px solid #eaecf2;
    }
}

/* Force the table header to match Stock Manager exactly (defeats any inline
   style precedence issues in react-data-table-component). */
.tab-content-section .rdt_TableHeadRow {
    background-color: #fafbfc !important;
    border-bottom: 2px solid #eef2f7 !important;
    min-height: 44px !important;
}
/* Header text — applies to every nested element so styled-components inner divs
   inherit the same font-size/weight/colour as Stock Check. */
.tab-content-section .rdt_TableHeadRow,
.tab-content-section .rdt_TableHeadRow * {
    color: #64748b !important;
    font-size: 11px !important;
    font-weight: 700 !important;
    letter-spacing: 0.7px !important;
    text-transform: uppercase !important;
}
.settings-nav-card::before { display: none; }
/* ── Header row — icon + title block on the left, Docs link on the right. margin-bottom:14px per spec. ── */
.settings-nav-bar {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 14px;
    background: transparent;
}
.settings-nav-left {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-shrink: 0;
}
.settings-nav-icon {
    width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0;
    background: rgb(234, 88, 12);
    display: flex; align-items: center; justify-content: center;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.25), 0 6px 14px -4px rgba(234,88,12,0.45);
}
.settings-nav-icon i { font-size: 18px; color: #fff; }
/* ── Tab tray wrapper — transparent; the .settings-tabs inside is the bordered pill tray. ── */
.settings-tabs-strip {
    padding: 0;
    background: transparent;
}
/* Tab tray — Stock Manager style: transparent strip, top-border accent on active. */
.settings-tabs {
    display: flex;
    align-items: center;
    gap: 4px;
    background: transparent;
    border: none;
    border-radius: 0;
    padding: 0;
    overflow-x: auto;
    margin: 0;
}
.settings-tabs::-webkit-scrollbar { display: none; }
/* Nav tabs — equal-width (flex:1 1 0), 48px height, no radius, transparent. */
.settings-tab {
    flex: 1 1 0;
    justify-content: center;
    height: 48px;
    padding: 0 20px;
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    border: none;
    border-top: 3px solid transparent;
    background: transparent;
    outline: none !important;
    box-shadow: none !important;
    white-space: nowrap;
    border-radius: 0;
    transition: all 0.16s ease;
}
.settings-tab i { font-size: 13px; transition: color 0.16s; }
.settings-tab svg { flex-shrink: 0; }
.settings-tab:hover { color: #f97316; background: #fff7ed; }
.settings-tab.active {
    color: #f97316 !important;
    background: #fff7ed !important;
    border-top: 3px solid #f97316;
    font-weight: 700;
    box-shadow: none !important;
}
.settings-tab.active i { color: #f97316; }
/* Vertical divider between the nav tabs and the Import Data CTA */
.settings-tab-divider {
    width: 1px;
    height: 24px;
    background: #ececef;
    margin: 0 6px;
    flex-shrink: 0;
}
/* Import Data — content-sized tab (not equal-width like the nav tabs). It behaves like any other
   tab: muted when inactive, orange ONLY when active (clicked). Same 48px height as other tabs. */
.settings-tab.settings-tab-cta {
    flex: 0 0 auto;
    padding: 0 20px;
}

/* ── Mobile-only tabs dropdown — EXACT copy of Stock Manager pattern (stock_check/index.blade.php) ── */
.settings-tabs-mobile-dd { display: none; } /* desktop/tablet: hidden — only shown on ≤767px */

@media (max-width: 767px) {
    /* Allow the dropdown panel to escape the card + match bottom card radius.
       Inner nav-bar has its own gradient background — give it the same radius so the visible
       corners aren't squared off when overflow:visible exposes the inner element's edges. */
    .settings-nav-card { overflow: visible !important; border-radius: 16px !important; padding: 14px 16px !important; position: relative !important; }
    .settings-nav-bar {
        flex-direction: row !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 10px !important;
        margin-bottom: 0 !important;
        flex-wrap: nowrap !important;
        overflow: visible !important;
        min-height: 36px !important;
    }
    /* Compact the left side: small icon + just "Settings" text on one line, hide subtitle */
    .settings-nav-left {
        gap: 8px !important;
        flex-shrink: 0 !important;
        min-width: 0 !important;
        display: flex !important;
        align-items: center !important;
    }
    .settings-nav-icon {
        width: 40px !important;
        height: 40px !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 12px rgba(249,115,22,0.28) !important;
        flex-shrink: 0 !important;
    }
    .settings-nav-icon i { font-size: 17px !important; }
    .settings-nav-left > div:last-child > div:first-child {
        font-size: 14px !important;
        font-weight: 800 !important;
        letter-spacing: -0.2px !important;
    }
    .settings-nav-left > div:last-child > div:first-child > span:first-child {
        font-size: 16px !important;
        font-weight: 800 !important;
        color: #0f1115 !important;
    }
    /* Hide ADMIN badge + the long desktop subtitle on mobile */
    .settings-nav-left > div:last-child > div:first-child > span:last-child { display: none !important; }
    .settings-subtitle-desktop { display: none !important; }
    /* Show the short mobile subtitle "Manage your workspace" under the title */
    .settings-subtitle-mobile {
        display: block !important;
        font-size: 12.5px !important;
        font-weight: 500 !important;
        color: #6b7280 !important;
        margin-top: 2px !important;
        line-height: 1.3 !important;
        white-space: nowrap !important;
    }
    /* Left column takes available width; reserve room on the right for the dropdown so neither
       the title nor subtitle ever sits under it. */
    .settings-nav-left { min-width: 0 !important; flex: 1 1 auto !important; gap: 11px !important; }
    .settings-nav-left > div:last-child { min-width: 0 !important; flex: 1 1 auto !important; padding-right: 110px !important; }
    /* "Roles" dropdown — pinned to the TOP-right (aligned with the title row), not
       vertically centred, so a long label (e.g. "Delete Data") never overlaps the
       "Manage your workspace" subtitle sitting below the title. */
    /* Align the dropdown vertically with the "Settings" title text (centre of the
       title row / icon), sitting to its right — not centred on the whole card. */
    .settings-tabs-strip { top: 28px !important; transform: translateY(-50%) !important; right: 14px !important; }
    .settings-tabs-mobile-dd .stmdd-trigger {
        width: auto !important;
        min-width: 84px !important;
        height: 34px !important;
        padding: 0 12px !important;
        gap: 8px !important;
        background: #fff5ec !important;
        border: 1.5px solid #fed7aa !important;
        border-radius: 10px !important;
        box-shadow: none !important;
    }
    .settings-tabs-mobile-dd .stmdd-label { color: #ea580c !important; font-weight: 700 !important; font-size: 13.5px !important; }
    .settings-tabs-mobile-dd .stmdd-caret { stroke: #ea580c !important; }

    /* Hide the original horizontal tabs pill row */
    .settings-tabs-wrap { display: none !important; }

    /* Gap between the Settings header card and the Roles/Permissions/Users card
       (these tabs render as their own standalone card on mobile). */
    #tab-roles.tab-content-section.active,
    #tab-permissions.tab-content-section.active,
    #tab-users.tab-content-section.active {
        margin-top: 14px !important;
    }

    /* Account + General tabs — no outer background card; inner cards float on their own. */
    #tab-account.tab-content-section.active,
    #tab-general.tab-content-section.active {
        margin-top: 14px !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        overflow: visible !important;
    }

    /* Delete Data tab — left transparent because it splits into its own inner cards below. */
    #tab-deletedata.tab-content-section.active {
        margin-top: 14px !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        overflow: visible !important;
    }

    /* Delete Data tab — split into separate cards with gaps:
       header card · warning card · recommended-order card · delete cards. */
    #tab-deletedata .sdd-spec-card {
        background: transparent !important; border: none !important; box-shadow: none !important;
        border-radius: 0 !important; overflow: visible !important;
    }
    #tab-deletedata .sform-card-header {
        background: #fff !important; border: 1px solid #eaecf2 !important; border-radius: 16px !important;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06) !important; padding: 16px !important; margin-bottom: 12px !important;
    }
    #tab-deletedata .sform-card-body { padding: 0 !important; }
    #tab-deletedata .sdd-warning-banner,
    #tab-deletedata .sdd-order-banner {
        box-shadow: 0 1px 4px rgba(0,0,0,0.05) !important;
        margin-bottom: 12px !important;
    }
    /* Description aligns under the heading text (not under the icon):
       indent it by the icon width (36px) + the row gap (11px). */
    #tab-deletedata .sdd-card-desc { padding-left: 47px !important; }

    /* General tab — TWO standalone cards: header (gear) + Show Suppliers (truck + toggle) */
    #tab-general .sg-header-card {
        background: transparent !important; border: none !important; box-shadow: none !important;
        display: flex !important; flex-direction: column !important; gap: 12px !important;
    }
    #tab-general .sg-header-card .sform-card-header {
        background: #fff !important; border: 1px solid #eaecf2 !important; border-radius: 16px !important;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06) !important; padding: 16px !important;
        border-bottom: 1px solid #eaecf2 !important;
    }
    #tab-general .sg-body {
        background: #fff !important; border: 1px solid #eaecf2 !important; border-radius: 16px !important;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06) !important; padding: 16px !important;
    }
    #tab-general .stoggle-row { display: flex !important; align-items: center !important; gap: 12px !important; }
    #tab-general .sg-toggle-icon {
        width: 38px !important; height: 38px !important; border-radius: 10px !important;
        background: #f1f5f9 !important; color: #64748b !important; flex-shrink: 0 !important;
        display: inline-flex !important; align-items: center !important; justify-content: center !important;
    }
    /* The profile card inside becomes the visible white card */
    #tab-account .sa-profile-card {
        background: #fff !important;
        border: 1px solid #eaecf2 !important;
        border-radius: 16px !important;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06) !important;
        overflow: hidden !important;
    }

    /* Tab strip on mobile — transparent; pulled up into the header row so the
       "Roles" dropdown sits on the SAME line as the Settings title (right-aligned). */
    /* Absolutely centre the dropdown on the right edge of the header card so it stays
       perfectly inline (vertically centred) with the Settings title, regardless of
       the title's exact height. */
    .settings-tabs-strip {
        padding: 0 !important;
        background: transparent !important;
        margin: 0 !important;
        position: absolute !important;
        top: 50% !important;
        right: 14px !important;
        transform: translateY(-50%) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
        z-index: 5 !important;
    }

    /* Dropdown wrapper — compact, sized to content (not full row) */
    .settings-tabs-mobile-dd {
        display: inline-block;
        position: relative;
        flex: 0 0 auto;
        margin-left: auto;
        min-width: 0;
    }
    .stmdd-trigger {
        width: 150px;
        height: 36px;
        padding: 0 10px 0 12px;
        border: 1.5px solid #e8edf2;
        border-radius: 10px;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        cursor: pointer;
        outline: none !important;
        box-shadow: none !important;
        -webkit-tap-highlight-color: transparent;
        transition: all 0.15s;
    }
    .stmdd-trigger:focus,
    .stmdd-trigger:focus-visible,
    .stmdd-trigger:active,
    .stmdd-trigger:hover {
        outline: none !important;
        box-shadow: none !important;
        border-color: #f97316 !important;
    }
    .stmdd-trigger.open,
    .stmdd-trigger.open:focus,
    .stmdd-trigger.open:active {
        background: #fff7ed;
        border-color: #f97316 !important;
        outline: none !important;
        box-shadow: none !important;
    }
    .stmdd-trigger .stmdd-label {
        font-size: 13px;
        font-weight: 700;
        color: #f97316;
        flex: 1;
        text-align: left;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .stmdd-trigger .stmdd-caret {
        flex-shrink: 0;
        transition: transform 0.2s;
        width: 13px;
        height: 13px;
        color: #f97316;
    }
    .stmdd-trigger.open .stmdd-caret { transform: rotate(180deg); }

    .stmdd-panel {
        display: none;
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        min-width: 200px;
        background: #fff;
        border-radius: 16px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 16px 48px rgba(0,0,0,0.16);
        overflow: hidden;
        z-index: 9999;
        animation: stmddIn 0.18s cubic-bezier(0.16,1,0.3,1);
    }
    .stmdd-panel.open { display: block; }
    @keyframes stmddIn { from{opacity:0;transform:translateY(-8px) scale(0.97)} to{opacity:1;transform:translateY(0) scale(1)} }

    .stmdd-opt {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 12px 16px;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        cursor: pointer;
        transition: all 0.15s;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        outline: none !important;
        box-shadow: none !important;
    }
    .stmdd-opt:focus { outline: none !important; box-shadow: none !important; }
    .stmdd-opt:hover { background: #F27420; color: #fff; }
    .stmdd-opt:active { background: #ea580c; color: #fff; }
    .stmdd-opt.active { color: #f97316; background: #fff7ed; font-weight: 700; }
    .stmdd-opt.active:hover { background: #F27420; color: #fff; }
    .stmdd-opt.active .stmdd-check { display: flex; }
    .stmdd-check {
        display: none;
        align-items: center;
        justify-content: center;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #f97316;
        flex-shrink: 0;
    }
}
@media (max-width: 767px) {
    .settings-nav-icon { width: 40px; height: 40px; border-radius: 12px; }
    .settings-tab { padding: 0 12px; font-size: 12px; height: 34px; border-radius: 8px; }
    .settings-tab i { font-size: 12px; }
}
@media (max-width: 520px) {
    .settings-nav-bar { padding: 0; gap: 10px; }
    .settings-nav-left { gap: 10px; }
    .settings-nav-icon { width: 42px; height: 42px; border-radius: 10px; }
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
/* Account tab only — solid rgb(234, 88, 12) background on Profile Info + Change Password icons */
#tab-account .sform-icon {
    background: rgb(234, 88, 12);
}
/* General tab — solid rgb(234, 88, 12) background on General Settings icon */
#tab-general .sform-icon {
    background: rgb(234, 88, 12);
}
/* Account tab — Save Profile + Update Password buttons */
#tab-account .sform-btn-save {
    background: rgb(234, 88, 12);
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

    /* Inputs — white background (not grey) */
    .sform-control {
        height: 46px !important; font-size: 13px !important;
        border-radius: 11px !important; padding: 0 14px !important;
        background: #fff !important; border-color: #e2e8f0 !important;
    }
    .sform-control:focus { background: #fff !important; border-color: #f97316 !important; }
    .sform-control:disabled { background: #fff !important; color: #94a3b8 !important; }

    /* 2-col grid */
    .sa-grid-2 { display: grid !important; grid-template-columns: 1fr 1fr !important; gap: 12px !important; }
    /* Email + Username — stacked (Email full width on top, Username below) */
    .sa-grid-email-user { grid-template-columns: 1fr !important; }
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
    /* Make the Profile card exactly as wide as the Settings header card above:
       kill the Bootstrap row's negative margins AND the column's gutter padding,
       so the card runs edge-to-edge of the page like the header card. */
    #tab-account .sa-account-row { margin: 0 !important; --bs-gutter-x: 0 !important; }
    #tab-account .sa-account-col-profile,
    #tab-account .sa-account-col-pw { padding-left: 0 !important; padding-right: 0 !important; }
    #tab-account .sform-card { width: 100% !important; margin: 0 !important; }
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

    /* Profile card header — title + subtitle on their own lines (no wrap), and the
       Change Password button drops to a new line when there isn't room beside it. */
    #tab-account .sform-card-header {
        align-items: center !important; gap: 10px !important; flex-wrap: wrap !important;
    }
    #tab-account .sform-card-header > div:first-child { min-width: 0 !important; flex: 1 1 auto !important; }
    /* "Profile Information" title — single line */
    #tab-account .sform-card-header > div:first-child > div > div:first-child {
        white-space: nowrap !important;
    }
    .sa-inline-pw-trigger {
        height: 32px !important; padding: 0 12px !important; font-size: 10.5px !important;
        white-space: nowrap !important; flex-shrink: 0 !important;
    }

    /* Modal password fields */
    .sa-pw-fields { gap: 0 !important; }
    /* Un-boxed fields (reference UI): just label + input, no grey wrapper box */
    .sa-pw-field {
        padding: 0 !important; background: transparent !important;
        border: none !important; border-radius: 0 !important;
        margin-bottom: 16px !important;
    }
    .sa-pw-field:last-of-type { margin-bottom: 0 !important; }
    .sa-pw-field .sform-label {
        color: #64748b !important; margin-bottom: 7px !important;
        font-size: 10.5px !important; font-weight: 700 !important;
        letter-spacing: 0.5px !important; text-transform: uppercase !important;
    }
    .sa-pw-field .sform-control {
        background: #fff !important; border: 1.5px solid #e2e8f0 !important;
        border-radius: 11px !important; height: 46px !important; font-size: 13.5px !important;
    }
    .sa-pw-field .sform-control:focus {
        border-color: rgb(234, 88, 12) !important;
        box-shadow: 0 0 0 3px rgba(234,88,12,0.1) !important; outline: none !important;
    }
    #pwStrengthWrap { margin-top: 6px !important; }
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
/* Show-Suppliers truck icon — mobile reference only; hidden on desktop/tablet */
.sg-toggle-icon { display: none; }
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
    background: rgb(234, 88, 12);
}
.stoggle-switch input:checked + .stoggle-track::before { transform: translateX(20px); }
.stoggle-switch input:disabled + .stoggle-track { opacity: 0.55; cursor: not-allowed; }

/* ── Global: remove black focus outline from all buttons/icons ── */
button:focus, button:active, .btn:focus, .btn:active,
.settings-tab:focus, .settings-tab:active,
.import-type-btn:focus, .import-type-btn:active,
.sdd-delete-btn:focus, .sdd-delete-btn:active,
.sform-btn-save:focus, .sform-btn-save:active {
    outline: none !important;
    box-shadow: none !important;
    border-color: inherit !important;
}

/* ── Import Data tab — Purchase/Sales selector buttons (EXACT spec) ──
   Inactive: white, 1px border, soft shadow. Active: solid orange, white text, inset highlight + shadows.
   min-width 180px, padding 10px 22px, radius 10px, 14px/800, gap 10px. */
.import-type-btn {
    min-width: 180px; padding: 10px 22px; border-radius: 10px;
    border: 1px solid #e7e7eb; background: #ffffff; color: #1a1d24;
    font-size: 14px; font-weight: 800; letter-spacing: -0.1px; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center; gap: 10px;
    box-shadow: 0 1px 2px rgba(15,17,21,0.04);
    transition: 0.15s; outline: none !important;
}
.import-type-btn:hover { border-color: #f97316; color: #f97316; }
.import-type-btn.active {
    background: rgb(234, 88, 12); color: #fff;
    border-color: transparent;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.25), 0 1px 2px rgba(234,88,12,0.4), 0 6px 14px -4px rgba(234,88,12,0.45);
}
.import-type-btn:hover.active { color: #fff; }
.import-type-btn.active i { color: #fff; }
.import-section { display: none !important; }
.import-section.active { display: block !important; }
.import-upload-zone {
    border: 2px dashed #cbd5e1; border-radius: 14px; padding: 40px 20px;
    text-align: center; cursor: pointer; transition: all 0.2s; background: #f8fafc;
}
.import-upload-zone:hover, .import-upload-zone.dragover {
    border-color: #f97316; background: #fff7ed;
}
.import-upload-icon { font-size: 36px; color: #94a3b8; margin-bottom: 10px; }
.import-upload-browse { color: rgb(234, 88, 12); font-weight: 700; }
/* Mobile-only reference card (hidden by default; shown only at ≤767px) */
.iuz-mobile { display: none; }
.irc-mobile { display: none; }

/* ── Hide uploaded-file pill (and its Replace button) whenever preview is visible.
   Applies across ALL viewports (desktop / tablet / mobile). JS toggles
   `body.import-preview-active` on #importPreviewArea show/hide. ── */
/* NOTE: the uploaded file pill stays VISIBLE during preview (it shows the selected file + Replace).
   Only the drop zone is hidden once a file is chosen — see the rule further below. */

/* ── Uploaded file pill (Sales / Purchase) — exact match with reference ──
   Soft mint-green container · square green Excel icon · two-line filename + meta · Replace + × close. */
/* ── Uploaded file pill — EXACT spec. Inline styles in the markup are authoritative;
   these class rules align with them so nothing clips or overrides incorrectly. ── */
.import-file-pill {
    padding: 14px 16px;
    background: #f1fbf4;
    border: 1px solid #bbf2cc;
    border-radius: 14px;
    display: flex;
    align-items: center;
    gap: 14px;
}
.import-file-pill .ifp-icon {
    width: 46px; height: 46px;
    flex-shrink: 0;
    border-radius: 11px;
    background: #ffffff;
    border: 1px solid #bbf2cc;
    color: #0f7a38;
    display: flex;
    align-items: center;
    justify-content: center;
}
.import-file-pill .ifp-body {
    flex: 1 1 0%;
    min-width: 0;
}
.import-file-pill .ifp-name {
    font-size: 15px;
    font-weight: 800;
    color: #0f1115;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.import-file-pill .ifp-meta {
    font-size: 12px;
    font-weight: 600;
    color: #0f7a38;
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.import-file-pill .ifp-replace {
    flex-shrink: 0;
    height: 42px;
    padding: 0 16px;
    border-radius: 10px;
    border: 1px solid #e7e7eb;
    background: #ffffff;
    color: #0f1115;
    font-family: inherit;
    font-size: 13.5px;
    font-weight: 700;
    letter-spacing: -0.1px;
    cursor: pointer;
    outline: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    box-shadow: 0 1px 2px rgba(15,17,21,0.04);
    transition: transform 0.04s, filter 0.15s;
}
.import-file-pill .ifp-replace:hover {
    filter: brightness(0.97);
}
.import-file-pill .ifp-close {
    flex-shrink: 0;
    width: 28px; height: 28px;
    border-radius: 7px;
    border: none;
    background: transparent;
    color: #6b7280;
    cursor: pointer;
    outline: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: background 0.15s, color 0.15s;
}
.import-file-pill .ifp-close:hover {
    background: rgba(0,0,0,0.04);
    color: #1f2937;
}
/* Mobile fine-tunes — keep the pill compact but readable */
@media (max-width: 767px) {
    .import-file-pill { padding: 10px 12px; gap: 10px; }
    .import-file-pill .ifp-icon { width: 30px; height: 30px; border-radius: 7px; }
    .import-file-pill .ifp-icon i { font-size: 14px; }
    .import-file-pill .ifp-name { font-size: 12.5px; }
    .import-file-pill .ifp-meta { font-size: 10.5px; }
    .import-file-pill .ifp-replace { height: 28px; padding: 0 10px; font-size: 11px; }
    .import-file-pill .ifp-close { width: 24px; height: 24px; font-size: 12px; }
}
.import-upload-zone:hover .import-upload-icon,
.import-upload-zone.dragover .import-upload-icon { color: #f97316; }
.import-upload-text { font-size: 14px; font-weight: 600; color: #374151; }
.import-upload-hint { font-size: 12px; color: #94a3b8; margin-top: 4px; }
.import-preview-table {
    width: 100%; border-collapse: collapse; font-size: 12px;
}
.import-preview-table th {
    background: #f8fafc; padding: 10px 14px; text-align: left;
    font-weight: 700; color: #64748b; font-size: 10px; text-transform: uppercase;
    letter-spacing: 0.5px; border-bottom: 1.5px solid #e2e8f0;
    white-space: nowrap;
    position: sticky; top: 0; z-index: 2;
}
/* Cell base — inline spec styles in renderPreviewPage are authoritative; this is a clean fallback.
   Padding 12px 14px gives rows breathing space (was 9px — too cramped for modern feel).
   Border-bottom soft #eef0f2 (was #f1f5f9 — harsher). Ink color #1a1d24 sharp+neutral. */
.import-preview-table td {
    padding: 12px 14px;
    border-bottom: 1px solid #eef0f2;
    color: #1a1d24;
    white-space: nowrap;
    font-size: 13px;
}
.import-preview-table tbody tr { transition: background 0.1s; }
.import-preview-table tbody tr:hover td { background: #ffffff; }
.import-preview-table tbody tr:last-child td { border-bottom: none; }

/* Price/amount cells — £ symbol same weight/size as the number for a balanced numeric column.
   Slight kerning gap (2px) so currency reads as a unit, not loose. */
.imp-price-cell {
    display: inline-flex;
    align-items: baseline;
    gap: 2px;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.imp-pound {
    color: inherit;
    font-weight: inherit;
    font-size: 1em;
}
/* ── TOTAL column cell — clean white background on Ready/Duplicate rows.
   Error rows pick up the rose bg via .imp-cell-error (inline _cellStyle takes precedence). ── */
.import-preview-table td.imp-total-cell {
    background: transparent;
}
.import-preview-table td.imp-total-cell .imp-price-cell {
    color: #1a1d24;                                          /* ink-2 */
    white-space: nowrap;
    font-family: 'JetBrains Mono', 'SFMono-Regular', 'Menlo', 'Consolas', monospace; /* --ff-mono */
    font-weight: 600;
}

/* ── Empty-state block — shown when filters/search return zero rows (matches reference design) ── */
.import-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 56px 24px 60px;
    background: #ffffff;
    gap: 0;
}
.import-empty-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 18px;
}
.import-empty-icon svg {
    width: 26px;
    height: 26px;
    color: #94a3b8;
}
.import-empty-title {
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.2px;
    line-height: 1.3;
    margin-bottom: 8px;
}
.import-empty-sub {
    font-size: 13.5px;
    font-weight: 500;
    color: #64748b;
    line-height: 1.55;
    max-width: 420px;
    margin: 0 auto 20px;
}
.import-empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 40px;
    padding: 0 18px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    color: #0f172a;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    outline: none !important;
    transition: border-color 0.15s, background 0.15s;
}
.import-empty-btn svg { width: 15px; height: 15px; flex-shrink: 0; color: #64748b; }
.import-empty-btn:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}

/* ── Status column header + pill (Ready / Duplicate / Error) — matches reference exactly ── */
.import-preview-table th.import-status-th { text-align: left; }
.import-preview-table td.import-status-td { white-space: nowrap; }
/* ── Status pill — EXACT spec values: 11px/700, 3px 9px padding, radius 999px, line-height 1.4.
   Inline styles in the row render are authoritative; these CSS rules only act as a fallback. ── */
.import-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.4;
    letter-spacing: 0.1px;
    white-space: nowrap;
    border: 1px solid transparent;
}
.import-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}
/* Ready — green */
.import-status-pill.is-ready {
    background: #e9f9ef;
    color: #0f7a38;
    border-color: #bbf2cc;
}
.import-status-pill.is-ready .import-status-dot { background: #0f7a38; }
/* Duplicate — light blue */
.import-status-pill.is-duplicate {
    background: #eaf2fd;
    color: #1e6bce;
    border-color: #bcd6f7;
}
.import-status-pill.is-duplicate .import-status-dot { background: #1e6bce; }
/* Error — soft red */
.import-status-pill.is-error {
    background: #feecec;
    color: #b11212;
    border-color: #fbc7c7;
}
.import-status-pill.is-error .import-status-dot { background: #b11212; }
/* ── All preview rows stay WHITE (no per-state row tint) and STAY WHITE on hover.
   Only the status pill colors differ between Ready / Duplicate / Error. ── */
.import-preview-table tbody tr td,
.import-preview-table tbody tr.import-row-duplicate td,
.import-preview-table tbody tr.import-row-invalid td { background: #ffffff; }
.import-preview-table tbody tr:hover td,
.import-preview-table tbody tr.import-row-duplicate:hover td,
.import-preview-table tbody tr.import-row-invalid:hover td { background: #ffffff; }
/* ── Specific invalid CELL highlight (only the wrong-value field, not the whole row) ── */
.import-preview-table tbody tr.import-row-invalid td.imp-cell-error {
    background: #fdecec;
    color: #b11212;
}

/* ── Import pagination ─────────────────────────── */
.import-pagination {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 14px; background: #f8fafc; border-top: 1.5px solid #f1f5f9;
    flex-wrap: wrap; gap: 10px;
}
.import-pagination-info { font-size: 12px; color: #64748b; font-weight: 500; }
.import-pagination-info strong { color: #0f172a; font-weight: 700; }
.import-pagination-controls { display: flex; align-items: center; gap: 6px; }
.import-page-nav { display: contents; }
.import-page-ellipsis { color: #94a3b8; font-size: 12px; padding: 0 2px; }
.import-page-btn {
    width: 34px; height: 34px; border-radius: 8px; border: 1.5px solid #e2e8f0;
    background: #fff; color: #374151; font-size: 12px; font-weight: 600;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all 0.15s; outline: none !important;
}
.import-page-btn:hover { border-color: #f97316; color: #f97316; }
.import-page-btn.active {
    background: linear-gradient(135deg, #f97316, #ea580c); color: #fff;
    border-color: transparent; box-shadow: 0 2px 8px rgba(249,115,22,0.3);
}
.import-page-btn:disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
.import-page-btn.nav-btn { width: auto; padding: 0 12px; gap: 4px; }
.import-per-page-wrap {
    position: relative; display: inline-block;
}
.import-per-page-btn {
    height: 34px; padding: 0 28px 0 12px; border-radius: 8px;
    border: 1.5px solid #e2e8f0; background: #fff; color: #374151;
    font-size: 12px; font-weight: 600; cursor: pointer; outline: none !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23F27420' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 8px center;
    transition: border-color 0.15s, box-shadow 0.15s;
    display: flex; align-items: center;
}
.import-per-page-btn:hover { border-color: #f97316; }
.import-per-page-dropdown {
    display: none; position: absolute; bottom: 100%; left: 0; margin-bottom: 4px;
    background: #fff; border: 1.5px solid #e2e8f0; border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12); overflow: hidden; z-index: 20;
    min-width: 80px;
}
.import-per-page-dropdown.open { display: block; }
.import-per-page-item {
    padding: 8px 14px; font-size: 12px; font-weight: 600; color: #374151;
    cursor: pointer; transition: all 0.1s; border: none; background: none;
    width: 100%; text-align: left; outline: none !important; display: block;
}
.import-per-page-item:hover { background: #f97316; color: #fff; }
.import-per-page-item.active { background: #fff7ed; color: #f97316; }
/* ── Import checkbox — custom orange filled rounded-square (matches reference) ── */
.import-cb {
    /* Reset native appearance */
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    width: 18px;
    height: 18px;
    border-radius: 5px;
    border: 1.5px solid #d1d5db;
    background: #ffffff;
    cursor: pointer;
    margin: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    position: relative;
    transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
    flex-shrink: 0;
    vertical-align: middle;
}
.import-cb:hover:not(:checked) {
    border-color: #f97316;
    background: #fffbf5;
}
.import-cb:checked {
    background: #f97316;
    border-color: #f97316;
    box-shadow: 0 1px 3px rgba(249,115,22,0.30);
}
/* White check mark — drawn via inline SVG mask so it stays crisp at any size */
.import-cb:checked::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none' stroke='%23ffffff' stroke-width='2.6' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='3.5 8.5 6.5 11.5 12.5 5'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: center;
    background-size: 80% 80%;
}
.import-cb:focus { outline: none; box-shadow: 0 0 0 3px rgba(249,115,22,0.18); }
.import-cb:focus:checked { box-shadow: 0 0 0 3px rgba(249,115,22,0.18), 0 1px 3px rgba(249,115,22,0.30); }
.import-cb:disabled { opacity: 0.5; cursor: not-allowed; }
.import-edit-btn {
    width: 32px; height: 32px; border-radius: 8px; border: none;
    background: #f3f4f6; color: #94a3b8; cursor: pointer; display: inline-flex;
    align-items: center; justify-content: center;
    transition: all 0.15s; outline: none !important; padding: 0;
}
.import-edit-btn .import-edit-ico { width: 15px; height: 15px; display: block; }
.import-edit-btn:hover { background: #fff7ed; color: #f97316; }
.import-edit-btn:active { background: #ffedd5; }
.import-edit-input {
    width: 100%; min-width: 110px; height: 34px; border: 1.5px solid #f97316; border-radius: 8px;
    padding: 0 10px; font-size: 13px; font-weight: 500; background: #fff7ed; color: #0f172a;
    outline: none; box-sizing: border-box;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.import-edit-input:focus { box-shadow: 0 0 0 3px rgba(249,115,22,0.18); }
/* Date input has a calendar icon overlaid on the right (padding-right:30px set inline) — needs
   more horizontal room so DD/MM/YYYY + icon both fit without clipping. */
.import-edit-input.imp-date-input { min-width: 130px; }
/* Give the editing row more breathing room so inputs don't clip. Apply only to <tr> containing
   edit inputs — the regular rows stay compact. */
.import-preview-table tbody tr:has(.import-edit-input) td {
    padding-left: 12px !important;
    padding-right: 12px !important;
    vertical-align: middle;
}
.import-preview-table tbody tr:has(.import-edit-input) td:not(:first-child):not(:nth-child(2)) {
    min-width: 130px;
}
.import-edit-actions {
    display: inline-flex; gap: 4px; margin-left: 4px;
}
.import-edit-save, .import-edit-cancel {
    width: 24px; height: 24px; border-radius: 5px; border: none;
    cursor: pointer; display: inline-flex; align-items: center;
    justify-content: center; font-size: 10px; outline: none !important; padding: 0;
}
.import-edit-save { background: #22c55e; color: #fff; }
.import-edit-save:hover { background: #16a34a; }
.import-edit-cancel { background: #e2e8f0; color: #64748b; }
.import-edit-cancel:hover { background: #cbd5e1; }
/* ── Import Error Dashboard & Filter Bar ──────── */
.import-error-dashboard {
    padding: 14px 22px;
    border-bottom: 1.5px solid #f1f5f9;
    background: #fff;
}
.import-error-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 10px;
}
.import-error-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1.5px solid #fecaca;
    background: #fef2f2;
    color: #991b1b;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
    outline: none !important;
    white-space: nowrap;
}
.import-error-chip:hover {
    border-color: #f97316;
    background: #fff7ed;
    color: #f97316;
}
.import-error-chip.active {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 2px 8px rgba(249,115,22,0.3);
}
.import-error-chip .chip-count {
    background: rgba(255,255,255,0.2);
    padding: 1px 6px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
}
.import-error-chip.active .chip-count {
    background: rgba(255,255,255,0.25);
}
/* Custom dropdown (react-select style) */
.import-dd-option:hover {
    background: #f9fafb;
    color: #0f172a;
}
/* Filter trigger button — keep neutral on hover. NO orange tint on border / icons / chevron. */
#importFilterDDBtn:hover,
#importFilterDDBtn:focus,
#importFilterDDBtn:focus-visible {
    border-color: #e5e7eb !important;
    box-shadow: 0 1px 2px rgba(15,23,42,0.04) !important;
}
/* Lock funnel + chevron strokes so they NEVER become orange on hover/focus */
#importFilterDDBtn:hover .imptb-funnel-ico,
#importFilterDDBtn:focus .imptb-funnel-ico,
#importFilterDDBtn:active .imptb-funnel-ico { stroke: #475569 !important; }
#importFilterDDBtn:hover .imptb-chev-ico,
#importFilterDDBtn:focus .imptb-chev-ico,
#importFilterDDBtn:active .imptb-chev-ico { stroke: #94a3b8 !important; }
/* Kill any legacy 'fill' rule that might tint the icons orange */
#importFilterDDBtn svg, #importFilterDDBtn:hover svg, #importFilterDDBtn:focus svg, #importFilterDDBtn:active svg {
    fill: none !important;
}
.import-filter-bar {
    padding: 10px 22px;
    border-bottom: 1.5px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    background: #fff;
}
.import-filter-btn {
    height: 32px;
    padding: 0 14px;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s;
    outline: none !important;
    white-space: nowrap;
}
.import-filter-btn:hover { border-color: #f97316; color: #f97316; }
.import-filter-btn.active {
    background: #f97316;
    color: #fff;
    border-color: transparent;
}
.import-filter-btn.danger {
    border-color: #fecaca;
    color: #dc2626;
}
.import-filter-btn.danger:hover {
    background: #dc2626;
    color: #fff;
    border-color: transparent;
}
.import-bulk-bar {
    padding: 12px 22px;
    border-bottom: 1.5px solid #f1f5f9;
    background: #fff7ed;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.import-bulk-input {
    height: 32px;
    border: 1.5px solid #f97316;
    border-radius: 8px;
    padding: 0 10px;
    font-size: 12px;
    font-weight: 600;
    outline: none;
    width: 140px;
    background: #fff;
    color: #1e293b;
}
.import-bulk-input:focus { box-shadow: 0 0 0 3px rgba(249,115,22,0.1); }

/* ══════════════════════════════════════════════════════════════
   Import Preview Toolbar — pill-shaped controls matching reference exactly
   Search · All Rows filter · Select all · Fix Errors · Import button
   ══════════════════════════════════════════════════════════════ */

/* ── Search input: very soft grey fill, barely-there border (matches reference) ── */
.imptb-search {
    position: relative;
    flex: 0 0 auto;
    width: 220px;
    height: 38px;
}
.imptb-search .imptb-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    pointer-events: none;
    flex-shrink: 0;
}
.imptb-search input {
    width: 100%;
    height: 38px;
    border-radius: 7px;
    border: 1px solid #f1f5f9;
    background: #f9fafb;
    padding: 0 36px 0 38px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 500;
    color: #0f172a;
    outline: none;
    transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}
.imptb-search input::placeholder { color: #9ca3af; font-weight: 500; }
.imptb-search input:hover {
    background: #f3f4f6;
    border-color: #e5e7eb;
}
.imptb-search input:focus {
    background: #ffffff;
    border-color: #f97316;
    box-shadow: 0 0 0 3px rgba(249,115,22,0.10);
}
.imptb-search input:focus + #importSearchClear,
.imptb-search input:focus ~ #importSearchClear { /* keep clear button visible on focus */ }
#importSearchClear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: none;
    background: #e5e7eb;
    color: #6b7280;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    padding: 0;
    outline: none;
    transition: background 0.12s, color 0.12s;
}
#importSearchClear:hover { background: #d1d5db; color: #111827; }

/* ── Filter dropdown: pill, white bg, light border, funnel + chevron ── */
.imptb-filter-wrap {
    position: relative;
    flex-shrink: 0;
}
/* Filter dropdown menu — absolutely positioned so it floats over content
   instead of pushing the toolbar/card height when opened. */
.imptb-filter-menu {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    min-width: 220px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 12px 28px rgba(15,23,42,0.12);
    z-index: 50;
    overflow: hidden;
    padding: 6px;
}
.import-dd-option {
    padding: 11px 14px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 700;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 2px;
    color: #0f172a;
    transition: background 0.12s;
}
.import-dd-option:last-child { margin-bottom: 0; }
.import-dd-option:hover { background: #f9fafb; }
/* Selected option — peach highlight + orange label (matches reference) */
.import-dd-option.is-selected {
    background: #fff7ed !important;
    color: #ea580c !important;
}
.import-dd-opt-count {
    font-size: 12px;
    font-weight: 800;
    border-radius: 999px;
    padding: 3px 11px;
    line-height: 1.4;
    font-variant-numeric: tabular-nums;
    min-width: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.imptb-filter-btn {
    height: 38px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    padding: 0 14px;
    min-width: 170px;
    height: 42px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    cursor: pointer;
    outline: none !important;
    -webkit-tap-highlight-color: transparent;
    white-space: nowrap;
    transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}
.imptb-filter-btn:hover { border-color: #d1d5db; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
/* No orange focus ring — keep the button visually neutral after tap/click on mobile.
   Filter selection state is communicated via the count chip + label color, not the button border. */
.imptb-filter-btn:focus,
.imptb-filter-btn:focus-visible,
.imptb-filter-btn:active {
    outline: none !important;
    box-shadow: none !important;
    border-color: #e2e8f0 !important;
    -webkit-tap-highlight-color: transparent !important;
}
.imptb-filter-btn .imptb-filter-label { color: #0f172a; font-weight: 700; white-space: nowrap; flex-shrink: 0; }
.imptb-filter-btn .imptb-filter-count {
    font-size: 12px;
    font-weight: 700;
    background: #f1f5f9;
    color: #64748b;
    border-radius: 999px;
    padding: 3px 11px;
    line-height: 1.4;
    font-variant-numeric: tabular-nums;
    min-width: 38px;
    text-align: center;
    white-space: nowrap;
    flex-shrink: 0;
}
.imptb-filter-btn svg { flex-shrink: 0; }
/* Chevron pushed to the far right */
.imptb-filter-btn .imptb-chev-ico { margin-left: auto; }

/* ── Select all label ── */
.imptb-select-all {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    margin: 0 0 0 12px;
    height: 42px;
    padding: 0 16px;
    white-space: nowrap;
    user-select: none;
    font-family: inherit;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #ffffff;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.imptb-select-all:hover { border-color: #d1d5db; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
.imptb-select-all span {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
}
/* Custom checkbox — square outlined, orange when checked (matches reference) */
.imptb-select-all .import-cb {
    appearance: none;
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border: 1.5px solid #cbd5e1;
    border-radius: 5px;
    background: #ffffff;
    cursor: pointer;
    position: relative;
    flex-shrink: 0;
    transition: background 0.15s, border-color 0.15s;
}
.imptb-select-all .import-cb:checked {
    background: #f97316;
    border-color: #f97316;
}
.imptb-select-all .import-cb:checked::after {
    content: '';
    position: absolute;
    left: 50%;
    top: 47%;
    width: 4px;
    height: 8px;
    border-right: 2.2px solid #ffffff;
    border-bottom: 2.2px solid #ffffff;
    transform: translate(-50%, -55%) rotate(45deg);
    box-sizing: content-box;
}

/* ── Right cluster ── */
.imptb-right-cluster {
    margin-left: auto;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

/* ── Fix Errors button: peach pill, outlined orange icon, orange label, solid orange count chip ── */
.imptb-fix-errors-btn {
    height: 38px;
    border-radius: 7px;
    border: 1px solid #fdba74;
    background: #fff3e6;
    padding: 0 6px 0 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: inherit;
    font-size: 13.5px;
    font-weight: 700;
    color: #ea580c;
    cursor: pointer;
    outline: none;
    white-space: nowrap;
    letter-spacing: -0.005em;
    transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
}
.imptb-fix-errors-btn:hover {
    border-color: #fb923c;
    background: #ffe7d0;
    box-shadow: 0 1px 3px rgba(249,115,22,0.15);
}
/* Disabled — no errors to fix; greyed-out pill, no hover lift */
.imptb-fix-errors-btn:disabled,
.imptb-fix-errors-btn.is-disabled {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #94a3b8;
    cursor: not-allowed;
    box-shadow: none;
    opacity: 0.85;
}
.imptb-fix-errors-btn:disabled:hover,
.imptb-fix-errors-btn.is-disabled:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
    box-shadow: none;
}
.imptb-fix-errors-btn:disabled .imptb-fix-errors-count,
.imptb-fix-errors-btn.is-disabled .imptb-fix-errors-count {
    background: #cbd5e1;
    color: #ffffff;
}
.imptb-fix-errors-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 24px;
    padding: 0 10px;
    background: #f97316;
    color: #ffffff;
    font-size: 12.5px;
    font-weight: 700;
    border-radius: 5px;
    border: none;
    line-height: 1;
    font-family: inherit;
    letter-spacing: 0;
}

/* ── Import button: solid orange pill, white check + bold label ── */
.imptb-import-btn {
    height: 38px;
    border-radius: 7px;
    border: none;
    background: #f97316;
    color: #ffffff;
    padding: 0 20px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    outline: none;
    white-space: nowrap;
    letter-spacing: -0.005em;
    box-shadow: 0 1px 2px rgba(249,115,22,0.30), 0 2px 4px rgba(15,23,42,0.04);
    transition: background 0.15s, box-shadow 0.15s, transform 0.05s;
}
.imptb-import-btn:hover {
    background: #ea580c;
    box-shadow: 0 2px 6px rgba(234,88,12,0.30), 0 1px 3px rgba(15,23,42,0.08);
}
.imptb-import-btn.is-disabled,
.imptb-import-btn:disabled {
    background: #f3f4f6;
    color: #9ca3af;
    cursor: not-allowed;
    box-shadow: none;
    border: 1px solid #e5e7eb;
}
.imptb-import-btn.is-disabled:hover,
.imptb-import-btn:disabled:hover {
    background: #f3f4f6;
    box-shadow: none;
}

/* ══════════════════════════════════════════════════════════════
   DESKTOP-ONLY (≥1200px) — Import Data Preview redesign
   Matches the reference: peach-tinted invalid rows, red "missing" placeholders,
   STATUS pill column, kebab-style action button, larger stat values with
   progress bars under Ready/Skipped, auto-mapping badge in header.
   Mobile and tablet styles are NOT touched.
   ══════════════════════════════════════════════════════════════ */
@media (min-width: 1200px) {
    /* ── Import Data card — merges as the bottom half of the unified Settings card. ── */
    #tab-importdata .sform-card {
        padding: 22px 24px !important;
        border-radius: 0 !important;
        border: none !important;
        box-shadow: none !important;
        overflow: visible !important;
        background: transparent !important;
    }
    /* Header — icon tile (42×42) + h2 title + p subtitle on the left, Download template ghost link on the right. */
    #tab-importdata .sform-card-header {
        padding: 0 !important;
        margin-bottom: 18px !important;
        gap: 14px !important;
        align-items: flex-start !important;
        display: flex !important;
        border-bottom: none !important;
    }
    #tab-importdata .sform-card-header .sform-icon {
        width: 42px !important;
        height: 42px !important;
        border-radius: 11px !important;
        background: rgb(234, 88, 12) !important;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.25), 0 6px 14px -4px rgba(234,88,12,0.45) !important;
        flex-shrink: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        color: #fff !important;
    }
    /* Body — zero padding; the tab bar / sections inside carry their own margins. */
    #tab-importdata .sform-card-body { padding: 0 !important; }
    /* Upload section content (banner + drop zone + file pill) — no extra padding; the card already pads. */
    #tab-importdata .import-section { padding: 0 !important; }
    /* Stack the children (Required-columns banner, then Drop-file zone) vertically,
       never side-by-side. */
    #tab-importdata .import-section.active {
        display: flex !important;
        flex-direction: column !important;
        gap: 14px !important;
        width: 100% !important;
    }
    #tab-importdata .import-section .irc-banner,
    #tab-importdata .import-section .import-upload-zone {
        width: 100% !important;
        margin: 0 !important;
        box-sizing: border-box !important;
    }
    /* Preview area — small top gap from the section above. */
    #tab-importdata #importPreviewArea { padding: 0 !important; margin-top: 18px !important; }

    /* ── Data Preview wrapper card ── */
    #importPreviewArea { margin-top: 20px !important; }
    #importPreviewArea > div {
        border-radius: 16px !important;
        border: 1px solid #e5e7eb !important;
        box-shadow: 0 1px 3px rgba(15,23,42,0.04) !important;
    }
    /* Header */
    #importPreviewArea > div > div:first-child {
        padding: 18px 22px !important;
        gap: 12px !important;
    }
    #importPreviewArea > div > div:first-child > div:first-child {
        width: 38px !important;
        height: 38px !important;
        border-radius: 10px !important;
        background: #f97316 !important;
        box-shadow: 0 2px 6px rgba(249,115,22,0.20) !important;
    }
    #importPreviewArea > div > div:first-child > div:first-child i { font-size: 14px !important; color: #ffffff !important; }
    #importPreviewArea > div > div:first-child > div:nth-child(2) > div:first-child {
        font-size: 16px !important;
        font-weight: 700 !important;
        color: #0f172a !important;
        letter-spacing: -0.2px !important;
    }
    #importPreviewArea > div > div:first-child > div:nth-child(2) > div:last-child {
        font-size: 12px !important;
        color: #94a3b8 !important;
        margin-top: 3px !important;
        font-weight: 500 !important;
    }
    /* Summary cards section */
    #importPreviewArea > div > div:nth-child(2) {
        padding: 16px 22px !important;
        background: #ffffff !important;
    }
    #importSummaryCards {
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 14px !important;
    }
    /* Each stat card — white surface, soft border, no shadow */
    .import-stat-card {
        background: #ffffff;
        border: 1px solid #eef2f7;
        border-radius: 12px;
        padding: 18px 20px 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .import-stat-card .isc-row {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    /* Icon — 36×36 ROUNDED-SQUARE (not circle), matches reference */
    .import-stat-card .isc-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 14px;
    }
    .import-stat-card .isc-body { min-width: 0; flex: 1; }
    /* Value — 28px bold, color-coded per card */
    .import-stat-card .isc-value {
        font-size: 28px;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -0.3px;
    }
    /* Label — small uppercase caps, BLACK across all three cards */
    .import-stat-card .isc-label {
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin-top: 5px;
        color: #0f172a !important;
        white-space: nowrap;
    }
    /* Progress bar — thin (3px), full-width below the row, only for Ready/Skipped */
    .import-stat-card .isc-progress {
        height: 3px;
        background: #f1f5f9;
        border-radius: 10px;
        overflow: hidden;
    }
    .import-stat-card .isc-progress-fill {
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s ease;
    }

    /* ── Auto-detected mapping badges (top-right of Data Preview header) ── */
    .import-mapping-badges {
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }
    .import-mapping-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 28px;
        padding: 0 12px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #eef2f7;
        font-size: 11.5px;
        font-weight: 600;
        color: #475569;
        white-space: nowrap;
    }
    .import-mapping-badge .import-mapping-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .import-mapping-badge .import-mapping-count {
        color: #0f172a;
        font-weight: 700;
        margin-right: 2px;
    }

    /* ── Preview table — exact reference styling ── */
    .import-preview-table {
        font-size: 13px !important;
    }
    .import-preview-table th {
        background: #fafbfc !important;
        padding: 11px 16px !important;
        font-size: 10.5px !important;
        font-weight: 700 !important;
        color: #6b7280 !important;
        letter-spacing: 0.7px !important;
        text-transform: uppercase !important;
        border-bottom: 1px solid #eef2f7 !important;
        border-top: 1px solid #eef2f7 !important;
    }
    .import-preview-table td {
        padding: 12px 16px !important;
        font-size: 13px !important;
        color: #0f172a !important;
        font-weight: 500 !important;
        border-bottom: 1px solid #f1f5f9 !important;
        background: #ffffff;
    }
    /* Row-number column — orange & bold when invalid (matches reference) */
    .import-preview-table tbody tr td:nth-child(2) {
        font-weight: 600;
        color: #94a3b8;
    }
    /* Per latest spec: ALL preview rows stay WHITE (no peach/rose tint, no hover tint).
       Only the status pill colors differ between Ready / Duplicate / Error. */
    .import-preview-table tbody tr.import-row-invalid td,
    .import-preview-table tbody tr.import-row-invalid:hover td,
    .import-preview-table tbody tr:not(.import-row-invalid):hover td {
        background: #ffffff !important;
    }
    /* Invoice number column — use a monospace-style font for the codes (INV-1042) */
    .import-preview-table td[data-col="invoice_no"],
    .import-preview-table tbody tr td:nth-child(4) {
        font-family: 'SFMono-Regular', 'Menlo', 'Consolas', monospace;
        font-size: 12px;
        letter-spacing: 0.2px;
        color: #475569;
    }
    /* Missing-value cells — italic red, matches reference exactly */
    .import-cell-missing {
        color: #ef4444 !important;
        font-style: italic !important;
        font-weight: 500 !important;
    }
    /* STATUS column */
    .import-th-status { text-align: left !important; }
    .import-td-status { white-space: nowrap !important; }
    /* Status pill — EXACT spec (11px/700, padding 3px 9px, radius 999px, line-height 1.4).
       Inline styles in the row render are authoritative; these rules align so any inherited
       desktop-only overrides don't fight the spec. */
    .import-status-pill {
        display: inline-flex !important;
        align-items: center !important;
        gap: 5px !important;
        padding: 3px 9px !important;
        border-radius: 999px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        line-height: 1.4 !important;
        letter-spacing: 0.1px !important;
        white-space: nowrap !important;
        height: auto !important;
        border: 1px solid transparent !important;
    }
    /* Edit row action button — light-grey rounded square with thin pencil SVG (matches reference) */
    .import-edit-btn {
        width: 32px !important;
        height: 32px !important;
        border-radius: 8px !important;
        border: none !important;
        background: #f3f4f6 !important;
        color: #94a3b8 !important;
    }
    .import-edit-btn .import-edit-ico {
        width: 15px !important;
        height: 15px !important;
        display: block !important;
    }
    .import-edit-btn:hover {
        background: #fff7ed !important;
        color: #f97316 !important;
        border: none !important;
    }
    /* Checkbox column — custom orange filled checkbox styled globally; nothing to override here */
    .import-preview-table .import-cb { width: 18px !important; height: 18px !important; }
}

/* ── Mobile: Import Data tab ──────────────────── */
@media (max-width: 767px) {
    /* ── Import Data tab — mobile redesign matching the provided reference ──
       Spec: orange rounded-square icon, segmented Purchase/Sales pill (active = solid black),
       peach "Required columns" banner with stacked columns, centered drop zone with
       "Drop your file or browse" prompt. Only mobile (≤767px) — desktop/tablet untouched. */

    /* Outer card — merged with the unified Settings card above. */
    #tab-importdata .sform-card {
        border-radius: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }
    /* ── Separate the Import Data card from the Settings nav card above with a gap.
         The two are normally merged into one card (the content card has no top
         border/radius and sits flush below the nav card). For the Import Data tab
         only, give the content card its own top border + full rounding and push it
         down with a margin so a clear gap shows between the two cards. The nav card
         above is already fully rounded on mobile, so it isn't touched. ── */
    #tab-importdata.tab-content-section {
        background: #ffffff !important;
        border: 1px solid #eaecf2 !important;          /* full border all around the card */
        border-radius: 16px !important;                /* round all 4 corners */
        box-shadow: 0 4px 16px rgba(0,0,0,0.05) !important;
        margin: 12px 0px 0 !important;                /* gap above only; full-width card */
        overflow: hidden !important;
    }
    /* ── Preview-active: split the Import-Data block and the Data-Preview block into
       two separate floating cards with a clear gap. Normally both live inside the one
       outer white card (.tab-content-section); during preview we neutralise that outer
       card and give the inner .sform-card its own card look, so the Data Preview
       (#importPreviewArea — already has its own wrapper card) sits below with a gap.
       Placed AFTER the base .tab-content-section rule so it wins on order + specificity. ── */
    body.import-preview-active #tab-importdata.tab-content-section {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        overflow: visible !important;
    }
    body.import-preview-active #tab-importdata .sform-card {
        background: #ffffff !important;
        border: 1px solid #eaecf2 !important;
        border-radius: 16px !important;
        box-shadow: 0 4px 16px rgba(0,0,0,0.05) !important;
        overflow: hidden !important;
    }
    body.import-preview-active #tab-importdata #importPreviewArea {
        margin-top: 14px !important;   /* the gap between the two cards */
    }
    /* Header row: icon + title block — reference: pad 16px 16px 12px, gap 12px, top-aligned */
    #tab-importdata .sform-card-header {
        padding: 16px 16px 12px !important;
        gap: 12px !important;
        border-bottom: none !important;
        align-items: flex-start !important;
    }
    /* Orange rounded-square upload icon — reference: 36×36, radius 10px, inset+drop shadow */
    #tab-importdata .sform-card-header .sform-icon {
        width: 36px !important;
        height: 36px !important;
        border-radius: 10px !important;
        background: rgb(234, 88, 12) !important;
        box-shadow: rgba(255,255,255,0.25) 0 1px 0 inset, rgba(234,88,12,0.5) 0 4px 10px -3px !important;
        flex-shrink: 0 !important;
    }
    #tab-importdata .sform-card-header .sform-icon i {
        font-size: 16px !important;
        color: #fff !important;
    }
    /* Mobile: show the reference upload icon, hide the desktop one */
    #tab-importdata .sform-card-header .sform-icon .sform-icon-desktop { display: none !important; }
    #tab-importdata .sform-card-header .sform-icon .sform-icon-mobile { display: block !important; width: 18px !important; height: 18px !important; color: #ffffff !important; stroke: #ffffff !important; }
    /* Title — "Import Data" — reference: 16px/800, ink, -0.2px */
    #tab-importdata .sform-card-header div:last-child > div:first-child {
        font-size: 16px !important;
        font-weight: 800 !important;
        color: #0f1115 !important;
        letter-spacing: -0.2px !important;
        line-height: 1.2 !important;
    }
    /* Subtitle — reference: 12.5px, muted, line-height 1.4 */
    #tab-importdata .sform-card-header div:last-child > div:last-child {
        font-size: 12.5px !important;
        color: #6b7280 !important;
        font-weight: 500 !important;
        margin-top: 2px !important;
        line-height: 1.4 !important;
    }
    /* Body padding */
    #tab-importdata .sform-card-body { padding: 0 18px 18px !important; }

    /* ── Segmented toggle (Purchase / Sales) — reference: grey tray rgb(241,241,244),
         radius 14px, pad 5px, gap 6px, 1px border.
         NOTE: the toggle bar is #importTabBar, which sits BEFORE .sform-card-body
         (not its first child) and carries inline background:transparent — so target
         it directly and override the inline style with !important. ── */
    #tab-importdata #importTabBar.import-tab-bar {
        background: rgb(241, 241, 244) !important;
        border: 1px solid #eaecf2 !important;
        border-radius: 14px !important;
        padding: 5px !important;
        gap: 6px !important;
        /* 18px side margin matches .sform-card-body's horizontal padding, so the toggle
           tray lines up exactly with the banner + upload card below it (same width). */
        margin: 0 18px 18px !important;
        display: flex !important;
    }
    .import-type-btn {
        flex: 1 1 0 !important;
        min-width: 0 !important;            /* allow the button to shrink so text never overflows */
        justify-content: center !important;
        height: 46px !important;
        padding: 0 8px !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        letter-spacing: -0.2px !important;
        border-radius: 12px !important;
        border: none !important;
        background: transparent !important;
        color: #0f1115 !important;
        gap: 6px !important;
        white-space: nowrap !important;
        box-shadow: none !important;
    }
    .import-type-btn i {
        font-size: 13px !important;
        flex-shrink: 0 !important;
        color: currentColor !important;
    }
    /* Active segment — pure BLACK across EVERY state (hover, focus, :active, tap-highlight, after-click).
       Order matters: cover all interaction pseudo-classes with !important so no other rule wins. */
    .import-type-btn.active,
    .import-type-btn.active:hover,
    .import-type-btn.active:focus,
    .import-type-btn.active:focus-visible,
    .import-type-btn.active:active,
    .import-type-btn.active:active:hover,
    .import-type-btn.active:focus:hover {
        background: rgb(15, 17, 21) !important;
        background-color: rgb(15, 17, 21) !important;
        background-image: none !important;
        color: #ffffff !important;
        border-color: rgb(15, 17, 21) !important;
        box-shadow: rgba(255,255,255,0.08) 0 1px 0 inset, rgba(15,17,21,0.18) 0 4px 14px !important;
        opacity: 1 !important;
        filter: none !important;
        -webkit-tap-highlight-color: transparent !important;
    }
    /* Icon color locked white across all states */
    .import-type-btn.active i,
    .import-type-btn.active:hover i,
    .import-type-btn.active:focus i,
    .import-type-btn.active:active i {
        color: #ffffff !important;
    }
    /* Kill browser native tap-highlight overlay on mobile (the grey flash on click) */
    .import-type-btn {
        -webkit-tap-highlight-color: transparent !important;
        -webkit-touch-callout: none !important;
        -webkit-user-select: none !important;
        user-select: none !important;
    }
    /* Reference keeps the full "Purchase Import" / "Sales Import" labels on mobile */
    .import-type-btn .itb-suffix { display: inline !important; }

    /* ── Required Columns banner (mobile) — pixel-exact match of reference:
         Cool light-grey card, slightly visible grey border, slate label + black-circle "7" + orange Template,
         chips row with bigger padding, monospace text, slate color. ── */
    /* ── Required-columns banner — reference: bg rgb(250,250,251), DASHED border,
         radius 14px, padding 12px 14px ── */
    .irc-banner {
        background: rgb(250, 250, 251) !important;
        border: 1px dashed #eaecf2 !important;
        border-radius: 14px !important;
        padding: 12px 14px !important;
        margin: 0 0 14px !important;
        display: block !important;
        gap: 0 !important;
        box-shadow: none !important;
    }
    /* Hide original desktop info-icon + paragraph on mobile */
    .irc-desktop-only { display: none !important; }
    /* Show mobile structure */
    .irc-mobile { display: block !important; width: 100%; }
    /* Head row: label+pill on the left, Template button pushed right (space-between) */
    .irc-mobile-head {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 8px !important;
        margin-bottom: 8px !important;
    }
    /* Left cluster wrapper — label + count pill, gap 6px */
    .irc-mobile-head-left {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
    }
    /* "REQUIRED COLUMNS" label — reference: 10.5px/800, muted, UPPERCASE, letter-spacing 0.8px */
    .irc-mobile-title {
        font-size: 10.5px !important;
        font-weight: 800 !important;
        color: #6b7280 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.8px !important;
        line-height: 1.4 !important;
    }
    /* Count pill — reference: dark rgb(15,17,21) pill, white "7", radius 999px, pad 1px 6px, 10px/700 */
    .irc-mobile-count-pill {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: auto !important;
        height: auto !important;
        min-width: 0 !important;
        padding: 1px 6px !important;
        border-radius: 999px !important;
        background: rgb(15, 17, 21) !important;
        border: 1px solid rgb(15, 17, 21) !important;
        color: #ffffff !important;
        font-size: 10px !important;
        font-weight: 700 !important;
        letter-spacing: 0.1px !important;
        line-height: 1.4 !important;
        font-variant-numeric: tabular-nums !important;
    }
    /* Template button — reference: transparent, orange-deep text, 12px/700, icon 12px, no bg/border */
    .irc-mobile-template {
        margin-left: auto !important;
        height: auto !important;
        padding: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        border: none !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        color: #c2410c !important;                 /* --orange-deep */
        text-decoration: none !important;
        white-space: nowrap !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
    }
    .irc-mobile-template:hover { background: transparent !important; color: #c2410c !important; }
    .irc-tpl-icon { width: 12px !important; height: 12px !important; color: currentColor !important; }

    /* Chips row — flex wrap, gap 5px */
    .irc-mobile-chips {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 5px !important;
    }
    /* Chip (code.mono) — reference: white bg, border, radius 7px, pad 3px 7px, 11px/600, ink-2 */
    .irc-chip {
        display: inline-flex !important;
        align-items: center !important;
        height: auto !important;
        padding: 3px 7px !important;
        background: #ffffff !important;
        border: 1px solid #eaecf2 !important;
        border-radius: 7px !important;
        font-family: 'SFMono-Regular','Menlo','Monaco','Consolas','Liberation Mono','Courier New',monospace !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        color: #475569 !important;                 /* --ink-2 */
        line-height: 1 !important;
        white-space: nowrap !important;
        letter-spacing: -0.2px !important;
    }

    /* ── When preview is active on MOBILE, hide the upload zone, the Required-columns
       banner (.irc-banner) AND the uploaded file-info pill (.import-file-pill) so only
       the data-preview table is shown. JS toggles `body.import-preview-active` on
       #importPreviewArea show/hide. ── */
    body.import-preview-active #import-section-purchase .import-upload-zone,
    body.import-preview-active #import-section-sales .import-upload-zone,
    body.import-preview-active #import-section-purchase .irc-banner,
    body.import-preview-active #import-section-sales .irc-banner,
    body.import-preview-active #import-section-purchase .import-file-pill,
    body.import-preview-active #import-section-sales .import-file-pill {
        display: none !important;
    }

    /* ── Upload zone (mobile) — reference design v2:
         White card, dashed orange-tinted border, document icon with orange "+" badge,
         title/subtitle, solid orange "Choose file" CTA pill. ── */
    .import-upload-zone {
        border: 1.5px dashed rgb(242, 179, 136) !important;
        background: rgb(255, 252, 250) !important;
        border-radius: 16px !important;
        padding: 22px 14px 18px !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 10px !important;
        text-align: center !important;
        cursor: pointer !important;
    }
    .import-upload-zone:hover, .import-upload-zone.dragover {
        border-color: #f97316 !important;
        background: #fffaf3 !important;
    }
    /* Hide the original desktop icon + texts on mobile */
    .iuz-desktop-icon, .iuz-desktop-only { display: none !important; }
    /* Show the mobile structure */
    .iuz-mobile {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 10px !important;
        width: 100%;
    }
    /* Document icon tile — reference: 54×54, radius 14px, peach gradient, inset+drop shadow.
       Plus badge sits at BOTTOM-RIGHT with a 3px white ring. */
    .iuz-doc-icon {
        position: relative;
        width: 54px;
        height: 54px;
        border-radius: 14px;
        background: linear-gradient(rgb(255, 228, 209), rgb(255, 208, 174));
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0;
        box-shadow: rgba(255,255,255,0.7) 0 1px 0 inset, rgba(234,88,12,0.4) 0 6px 14px -6px;
    }
    .iuz-doc-svg {
        width: 22px;
        height: 22px;
    }
    .iuz-doc-plus {
        position: absolute;
        bottom: -4px;
        right: -4px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgb(234, 88, 12);
        display: flex;
        align-items: center;
        justify-content: center;
        /* White outer ring — gives the badge that "punched-out" lift against the peach tile */
        box-shadow: rgb(255,255,255) 0 0 0 3px;
    }
    .iuz-doc-plus svg {
        width: 13px;
        height: 13px;
    }

    /* Title — reference: 15px/800, ink, centered */
    .iuz-mobile-title {
        font-size: 15px;
        font-weight: 800;
        color: #0f1115;
        line-height: 1.3;
        letter-spacing: -0.1px;
        text-align: center;
        margin-top: 0;
    }
    /* Subtitle — reference: 12.5px, muted, centered */
    .iuz-mobile-sub {
        font-size: 12.5px;
        font-weight: 500;
        color: #6b7280;
        text-align: center;
        margin-top: 3px;
        margin-bottom: 0;
        line-height: 1.4;
    }
    /* CTA button — reference: solid orange, white, radius 11px, pad 9px 14px, 13px/700, inset+drop shadow */
    .iuz-mobile-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        height: auto;
        padding: 9px 14px;
        border-radius: 11px;
        background: rgb(234, 88, 12);
        color: #ffffff;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0;
        box-shadow: rgba(255,255,255,0.3) 0 1px 0 inset, rgba(234,88,12,0.45) 0 6px 14px -4px;
        white-space: nowrap;
        margin-top: 4px;
        pointer-events: none; /* parent zone handles the click */
    }

    /* ─────────────────────────────────────────────────────────────────────────
       Mobile-only "Data Preview" card redesign — matches reference spec exactly.
       Applies on mobile (≤767px) ONLY. Desktop/tablet preview untouched.
       ───────────────────────────────────────────────────────────────────────── */

    /* Outer preview card */
    #importPreviewArea > div {
        border-radius: 16px !important;
        box-shadow: 0 1px 4px rgba(15,23,42,0.04) !important;
        border: 1.5px solid #f1f5f9 !important;
        background: #ffffff !important;
        overflow: visible !important;
    }

    /* ── 1. Header row — icon + title + Auto-mapped badge ── */
    #importPreviewArea > div > div:first-child {
        padding: 20px 20px 0 20px !important;
        gap: 12px !important;
        border-bottom: none !important;
        align-items: flex-start !important;
        display: flex !important;
    }
    /* Icon tile — 40×40 orange */
    #importPreviewArea > div > div:first-child > div:first-child {
        width: 40px !important;
        height: 40px !important;
        border-radius: 10px !important;
        background: #f97316 !important;
        flex-shrink: 0 !important;
        box-shadow: none !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    #importPreviewArea > div > div:first-child > div:first-child i {
        font-size: 16px !important;
        color: #ffffff !important;
    }
    /* Title "Data Preview" */
    #importPreviewArea > div > div:first-child > div:last-child > div:first-child {
        font-size: 17px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        line-height: 1.2 !important;
        letter-spacing: -0.3px !important;
    }
    /* Subtitle — multi-line allowed, muted slate */
    #importPreviewArea > div > div:first-child > div:last-child > div:last-child,
    #importPreviewSubtitle {
        font-size: 12.5px !important;
        font-weight: 500 !important;
        color: #94a3b8 !important;
        line-height: 1.45 !important;
        margin-top: 2px !important;
        white-space: normal !important;
    }
    /* Auto-mapped badges (mapping count pill) — green capsule top-right */
    .import-mapping-badges {
        margin-left: auto !important;
        flex-shrink: 0 !important;
        gap: 4px !important;
    }
    .import-mapping-badge {
        background: #ecfdf5 !important;
        border: 1px solid #a7f3d0 !important;
        color: #16a34a !important;
        border-radius: 999px !important;
        padding: 5px 11px !important;
        font-size: 11.5px !important;
        font-weight: 700 !important;
        letter-spacing: 0.2px !important;
        gap: 5px !important;
        height: auto !important;
        display: inline-flex !important;
        align-items: center !important;
    }
    .import-mapping-badge i,
    .import-mapping-badge svg {
        color: #16a34a !important;
        font-size: 12px !important;
        width: 12px !important;
        height: 12px !important;
    }
    .import-mapping-badge .import-mapping-dot,
    .import-mapping-badge .import-mapping-count {
        color: #16a34a !important;
        font-weight: 700 !important;
    }

    /* ── 2. Summary Cards — 2×2 grid with colored backgrounds ── */
    #importPreviewArea > div > div:nth-child(2) {
        padding: 18px 20px 0 20px !important;
        background: #ffffff !important;
        border-bottom: none !important;
    }
    /* ── Mobile: swap the 4 colored stat cards for the Stock-Check–style collapsible
         "STOCK SUMMARY" card (matches Stock Check page). Hide the grid, show #importSummaryMobile. ── */
    #importSummaryCardsWrap { display: none !important; }
    #importSummaryMobile { display: block !important; }

    #importSummaryCards {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 12px !important;
    }
    /* Each card — colored bg + border, 14px radius, label-icon row + big value + optional progress */
    #importSummaryCards > div {
        flex-direction: column !important;
        align-items: stretch !important;
        justify-content: flex-start !important;
        text-align: left !important;
        gap: 8px !important;
        padding: 14px 16px !important;
        border-radius: 14px !important;
        background: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: none !important;
        min-height: 0 !important;
        position: relative !important;
    }
    /* Card variants by index — Card 1 grey (default above), Card 2 green, Card 3 red, Card 4 indigo */
    #importSummaryCards > div:nth-child(2) {
        background: #f0fdf4 !important;
        border-color: #bbf7d0 !important;
    }
    #importSummaryCards > div:nth-child(3) {
        background: #fef2f2 !important;
        border-color: #fecaca !important;
    }
    #importSummaryCards > div:nth-child(4) {
        background: #eef2ff !important;
        border-color: #c7d2fe !important;
    }
    /* Remove the divider line from old design */
    #importSummaryCards > div:not(:last-child)::after { content: none !important; }

    /* Inner row (.isc-row) wraps icon + body — restructure on mobile */
    #importSummaryCards .import-stat-card .isc-row {
        display: block !important;
        position: relative !important;
        gap: 0 !important;
    }

    /* Icon tile — moved to top-right corner of card as a small inline glyph */
    #importSummaryCards .import-stat-card .isc-icon {
        position: absolute !important;
        top: 0 !important;
        right: 0 !important;
        width: auto !important;
        height: auto !important;
        background: transparent !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
        display: inline-flex !important;
    }
    #importSummaryCards .import-stat-card .isc-icon i { font-size: 14px !important; }
    /* Per-card icon colors */
    #importSummaryCards > div:nth-child(1) .isc-icon i { color: #94a3b8 !important; }
    #importSummaryCards > div:nth-child(2) .isc-icon i { color: #16a34a !important; }
    #importSummaryCards > div:nth-child(3) .isc-icon i { color: #dc2626 !important; }
    #importSummaryCards > div:nth-child(4) .isc-icon i { color: #6366f1 !important; }

    /* Body — flip so label is on top, value is below */
    #importSummaryCards .import-stat-card .isc-body {
        display: flex !important;
        flex-direction: column !important;
        gap: 8px !important;
        width: 100% !important;
    }

    /* Label — UPPERCASE, small, sits ABOVE the value */
    #importSummaryCards .import-stat-card .isc-label {
        font-size: 10.5px !important;
        letter-spacing: 0.6px !important;
        font-weight: 700 !important;
        line-height: 1.2 !important;
        text-transform: uppercase !important;
        margin: 0 !important;
        white-space: nowrap !important;
        order: 0 !important;
    }
    #importSummaryCards > div:nth-child(1) .isc-label { color: #64748b !important; }
    #importSummaryCards > div:nth-child(2) .isc-label { color: #16a34a !important; }
    #importSummaryCards > div:nth-child(3) .isc-label { color: #dc2626 !important; }
    #importSummaryCards > div:nth-child(4) .isc-label { color: #6366f1 !important; }

    /* Value — large bold number, sits BELOW the label */
    #importSummaryCards .import-stat-card .isc-value {
        font-size: 28px !important;
        font-weight: 800 !important;
        line-height: 1.05 !important;
        letter-spacing: -0.5px !important;
        font-variant-numeric: tabular-nums !important;
        margin: 0 !important;
        order: 1 !important;
    }
    #importSummaryCards > div:nth-child(1) .isc-value { color: #0f172a !important; }
    #importSummaryCards > div:nth-child(2) .isc-value { color: #16a34a !important; }
    #importSummaryCards > div:nth-child(3) .isc-value { color: #dc2626 !important; }
    #importSummaryCards > div:nth-child(4) .isc-value { color: #6366f1 !important; }

    /* Progress bar — pill, 4px, full-rounded */
    #importSummaryCards .import-stat-card .isc-progress {
        height: 4px !important;
        border-radius: 999px !important;
        margin-top: 4px !important;
        overflow: hidden !important;
        background: transparent !important;
    }
    #importSummaryCards > div:nth-child(2) .isc-progress { background: #d1fae5 !important; }
    #importSummaryCards > div:nth-child(3) .isc-progress { background: #fee2e2 !important; }
    #importSummaryCards .import-stat-card .isc-progress-fill {
        height: 100% !important;
        border-radius: 999px !important;
        transition: width 0.3s ease !important;
    }
    #importSummaryCards > div:nth-child(2) .isc-progress-fill { background: #16a34a !important; }
    #importSummaryCards > div:nth-child(3) .isc-progress-fill { background: #dc2626 !important; }

    /* Table */
    .import-preview-table th { padding: 7px 8px !important; font-size: 9px !important; }
    .import-preview-table td { padding: 6px 8px !important; font-size: 11px !important; }
    .import-cb { width: 14px !important; height: 14px !important; }
    .import-edit-btn { width: 28px !important; height: 28px !important; border-radius: 7px !important; }
    .import-edit-btn .import-edit-ico { width: 13px !important; height: 13px !important; }

    /* Pagination — mobile redesign: airy spacing, larger touch targets, clean hierarchy */
    .import-pagination {
        padding: 16px 14px 18px !important;
        flex-direction: column !important;
        align-items: center !important;
        gap: 14px !important;
        border-top: 1px solid #f1f5f9 !important;
        background: #ffffff !important;
    }
    .import-pagination-controls {
        order: 1 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: nowrap !important;
        gap: 8px !important;
        width: 100% !important;
    }
    /* Per-page dropdown — left side, separated from page nav */
    .import-per-page-wrap { flex-shrink: 0 !important; }
    .import-per-page-btn {
        height: 36px !important;
        min-width: 56px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        padding: 0 24px 0 12px !important;
        border-radius: 9px !important;
        border: 1.5px solid #e5e7eb !important;
        color: #374151 !important;
        background: #ffffff !important;
    }
    .import-per-page-dropdown {
        border-radius: 9px !important;
        min-width: 70px !important;
        border: 1px solid #e5e7eb !important;
        box-shadow: 0 6px 16px rgba(15,23,42,0.10) !important;
    }
    .import-per-page-item {
        padding: 9px 14px !important;
        font-size: 12px !important;
        font-weight: 600 !important;
    }
    /* Page nav cluster — right side, grouped together with internal gaps */
    .import-page-nav {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        flex-shrink: 1 !important;
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
    }
    .import-page-nav::-webkit-scrollbar { display: none !important; }
    .import-page-ellipsis {
        font-size: 12px !important;
        color: #cbd5e1 !important;
        padding: 0 4px !important;
        line-height: 36px !important;
    }
    /* Individual page buttons */
    .import-page-btn {
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
        font-size: 12.5px !important;
        font-weight: 600 !important;
        border-radius: 9px !important;
        border: 1.5px solid #e5e7eb !important;
        background: #ffffff !important;
        color: #374151 !important;
        padding: 0 !important;
        margin: 0 !important;
        flex-shrink: 0 !important;
    }
    .import-page-btn.nav-btn { width: 36px !important; min-width: 36px !important; padding: 0 !important; }
    .import-page-btn.active {
        background: #f97316 !important;
        border-color: #f97316 !important;
        color: #ffffff !important;
        box-shadow: 0 1px 3px rgba(249,115,22,0.30) !important;
    }
    .import-page-btn:disabled { opacity: 0.35 !important; }
    /* Info line — soft, secondary, sits below the controls */
    .import-pagination-info {
        font-size: 11.5px !important;
        text-align: center !important;
        order: 2 !important;
        color: #94a3b8 !important;
        font-weight: 500 !important;
        letter-spacing: 0.01em !important;
        line-height: 1.4 !important;
    }
    .import-pagination-info strong {
        color: #475569 !important;
        font-weight: 700 !important;
    }

    /* Error Dashboard & Filter Bar */
    .import-error-dashboard { padding: 10px 14px !important; }
    .import-error-chips { gap: 6px !important; }
    .import-error-chip { padding: 5px 8px !important; font-size: 10px !important; border-radius: 6px !important; }
    .import-filter-bar { padding: 8px 14px !important; gap: 8px !important; }
    .import-filter-btn { height: 28px !important; font-size: 10px !important; padding: 0 10px !important; border-radius: 6px !important; }
    .import-bulk-bar { padding: 10px 14px !important; gap: 8px !important; }
    .import-bulk-input { height: 28px !important; font-size: 11px !important; width: 120px !important; }

    /* Errors section */
    #importErrors { margin: 10px 12px 0 !important; padding: 10px 12px !important; border-radius: 8px !important; }

    /* Action buttons at bottom */
    #importPreviewArea > div > div:last-child { padding: 12px 14px !important; }
    #importPreviewArea > div > div:last-child button:first-child { height: 34px !important; font-size: 11px !important; padding: 0 12px !important; }
    #importPreviewArea > div > div:last-child button:last-child { height: 34px !important; font-size: 11px !important; padding: 0 14px !important; }

    /* Edit inline inputs */
    .import-edit-input { height: 24px !important; font-size: 11px !important; padding: 0 4px !important; border-radius: 4px !important; }
    .import-edit-save, .import-edit-cancel { width: 20px !important; height: 20px !important; font-size: 8px !important; border-radius: 4px !important; }
}

/* ── Mobile rules for new Sales-Import components (added 2026-05-11) ───────────── */
@media (max-width: 767px) {
    /* ── Toolbar (search + filter + select-all + actions) — matches reference spec ── */
    #importErrorDashboard > div {
        padding: 18px 20px 20px 20px !important;
        border-bottom: none !important;
        background: #ffffff !important;
    }
    #importErrorDashboard > div > div:first-child {
        display: grid !important;
        /* Row 2: "All Rows" + "Select all" each as wide as their content (max-content),
           24px gap, then an empty filler column for the rest of the row. */
        grid-template-columns: max-content max-content minmax(0,1fr) !important;
        column-gap: 24px !important;
        row-gap: 12px !important;
        align-items: center !important;
        justify-items: start !important;
        min-width: 0 !important;
    }
    /* All Rows — content-width box (wrapper + button both shrink to fit) */
    #importErrorDashboard #importFilterDDWrap { width: max-content !important; min-width: 0 !important; max-width: 100% !important; }
    #importErrorDashboard #importFilterDDBtn  { width: max-content !important; min-width: 0 !important; }
    /* Select all — content-width box too */
    #importErrorDashboard #importSelectAllBtn { width: max-content !important; min-width: 0 !important; max-width: 100% !important; white-space: nowrap !important; }

    /* Row 1 — Search input — pixel-exact match: white pill with light grey border, no fill */
    #importSearchWrap {
        order: 1 !important;
        grid-column: 1 / -1 !important;
        width: 100% !important;
        background: #ffffff !important;
        border-radius: 12px !important;                /* reference: 12px */
        border: 1.5px solid #e2e8f0 !important;        /* reference: 1.5px */
        height: 40px !important;                       /* reference: 40px */
        padding: 0 10px 0 34px !important;             /* tighter, icon 15px @ ~10px left */
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        box-shadow: none !important;
    }
    #importSearchWrap .imptb-search-icon {
        position: absolute !important;
        left: 11px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        color: #94a3b8 !important;
        width: 15px !important;
        height: 15px !important;
        pointer-events: none !important;
    }
    #importSearchInput {
        height: 100% !important;
        font-size: 13.5px !important;
        font-weight: 400 !important;
        background: transparent !important;
        border: none !important;
        color: #0f172a !important;
        width: 100% !important;
        padding: 0 !important;
        outline: none !important;
        box-shadow: none !important;
        -webkit-appearance: none !important;
        appearance: none !important;
    }
    /* Kill inner input focus ring + browser default border on ALL interaction states —
       only the OUTER wrap should show the focus outline, never the input itself. */
    #importSearchInput:focus,
    #importSearchInput:focus-visible,
    #importSearchInput:active,
    #importSearchInput:hover {
        outline: none !important;
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        -webkit-tap-highlight-color: transparent !important;
    }
    #importSearchInput::placeholder {
        color: #64748b !important;
        font-weight: 400 !important;
        letter-spacing: -0.1px !important;
    }
    #importSearchWrap:focus-within {
        border-color: #f97316 !important;
        box-shadow: none !important;
    }

    /* Row 2 — All Rows filter (left) + Select all (right) */
    #importFilterDDWrap {
        order: 2 !important;
        grid-column: 1 !important;
        min-width: 0 !important;
        width: 100% !important;
    }
    #importFilterDDBtn {
        width: 100% !important;
        height: 38px !important;                    /* reference: 38px */
        background: #ffffff !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 11px !important;             /* reference: 11px */
        padding: 0 10px !important;                 /* reference: 0 10px */
        font-size: 12.5px !important;               /* reference: 12.5px */
        font-weight: 700 !important;
        color: #0f1115 !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        -webkit-tap-highlight-color: transparent !important;
        box-shadow: none !important;
    }
    /* Kill the orange focus ring after tap on mobile — keep border neutral grey */
    #importFilterDDBtn:focus,
    #importFilterDDBtn:focus-visible,
    #importFilterDDBtn:active {
        border-color: #e5e7eb !important;
        box-shadow: 0 1px 2px rgba(15,23,42,0.02) !important;
        outline: none !important;
    }
    /* Funnel icon — slate, reference 13px */
    .imptb-funnel-ico { width: 13px !important; height: 13px !important; flex-shrink: 0 !important; }
    /* Chevron — slate, reference 13px, pushed to far right via margin-left:auto */
    .imptb-chev-ico { width: 13px !important; height: 13px !important; flex-shrink: 0 !important; margin-left: auto !important; }
    /* Label — bold ink, sits next to funnel */
    .imptb-filter-label {
        font-size: 12.5px !important;
        font-weight: 700 !important;
        color: #0f1115 !important;
        white-space: nowrap !important;
    }
    /* Count chip — light grey pill rgb(244,244,246), slate text (reference exact) */
    .imptb-filter-count {
        border-radius: 999px !important;
        padding: 1px 7px !important;
        font-size: 11px !important;
        font-weight: 700 !important;
        line-height: 1.4 !important;
        font-variant-numeric: tabular-nums !important;
        min-width: 0 !important;
        text-align: center !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        letter-spacing: 0.1px !important;
        background: rgb(244, 244, 246) !important;
        color: rgb(55, 65, 81) !important;
        border: 1px solid #e5e7eb !important;
    }
    /* When filter is active (Valid/Invalid/Duplicates selected), the trigger button border + bg lift */
    #importFilterDDBtn.is-active {
        background: #ffffff !important;
        border-color: #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(15,23,42,0.05) !important;
    }

    /* Dropdown menu — opens beneath the (now content-width) "All Rows" trigger.
       At least as wide as the trigger, with a sensible minimum so the options read well. */
    .imptb-filter-menu {
        position: absolute !important;
        top: calc(100% + 6px) !important;
        left: 0 !important;
        right: auto !important;
        width: 100% !important;       /* exactly as wide as the "All Rows" trigger button */
        min-width: 0 !important;      /* override the desktop base min-width:220px so it matches the button */
        max-width: 100% !important;
        background: #ffffff !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 12px !important;
        box-shadow: 0 12px 28px rgba(15,23,42,0.12) !important;
        z-index: 50 !important;
        overflow: hidden !important;
        padding: 6px !important;
        margin-top: 0 !important;
    }
    .import-dd-option {
        padding: 10px 12px !important;
        cursor: pointer !important;
        font-size: 13.5px !important;
        font-weight: 700 !important;
        border-radius: 8px !important;
        transition: background 0.12s !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 10px !important;
        margin-bottom: 2px !important;
    }
    .import-dd-option:last-child { margin-bottom: 0 !important; }
    .import-dd-opt-label { font-weight: 700 !important; }
    .import-dd-opt-count {
        border-radius: 999px !important;
        padding: 2px 9px !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        min-width: 28px !important;
        text-align: center !important;
        line-height: 1.5 !important;
        font-variant-numeric: tabular-nums !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    /* Select all — peach-tinted active state when a filter is applied (matches reference image 3) */
    #importFilterDDWrap[data-active="1"] ~ label[title*="Select or deselect"],
    #importErrorDashboard label[title*="Select or deselect"].is-filter-active {
        background: #fff7ed !important;
        border-color: #fed7aa !important;
    }
    #importFilterDDWrap[data-active="1"] ~ label[title*="Select or deselect"] .import-cb,
    #importErrorDashboard label[title*="Select or deselect"].is-filter-active .import-cb {
        border-color: #f97316 !important;
    }
    #importFilterDDWrap[data-active="1"] ~ label[title*="Select or deselect"] span,
    #importErrorDashboard label[title*="Select or deselect"].is-filter-active span {
        color: #ea580c !important;
    }
    /* Select all — it's a <button id="importSelectAllBtn"> (NOT a label). Sits in Row 2,
       right column, next to the All Rows filter. Reference: h38, radius 11px, font 12.5px. */
    #importErrorDashboard #importSelectAllBtn {
        order: 3 !important;
        grid-column: 2 !important;
        flex: 0 0 auto !important;
        width: 100% !important;
        height: 38px !important;                    /* reference: 38px */
        padding: 0 10px !important;
        justify-content: center !important;
        gap: 6px !important;
        border: 1px solid #e5e7eb !important;
        border-radius: 11px !important;             /* reference: 11px */
        background: #ffffff !important;
        display: flex !important;
        align-items: center !important;
        cursor: pointer !important;
        transition: background 0.15s, border-color 0.15s !important;
        -webkit-tap-highlight-color: transparent !important;
    }
    #importErrorDashboard #importSelectAllBtn .imp-selall-box {
        width: 16px !important;
        height: 16px !important;
        border-radius: 5px !important;
        flex-shrink: 0 !important;
    }
    #importErrorDashboard #importSelectAllBtn > span:not(.imp-selall-box) {
        font-size: 12.5px !important;               /* reference: 12.5px */
        font-weight: 700 !important;
        color: #0f1115 !important;
    }
    /* ── Active "Select all" state — when checkbox is checked, whole pill turns peach + orange.
       This matches the reference: peach bg, orange border, orange-filled check icon, orange text. */
    #importErrorDashboard label[title*="Select or deselect"]:has(.import-cb:checked) {
        background: #fff7ed !important;
        border-color: #fed7aa !important;
    }
    #importErrorDashboard label[title*="Select or deselect"]:has(.import-cb:checked) span {
        color: #ea580c !important;
    }
    /* Checked state — WHITE fill with ORANGE border + ORANGE checkmark (matches reference exactly).
       Reference shows a rounded square with orange outline (not filled) and an orange tick inside. */
    #importErrorDashboard label[title*="Select or deselect"] .import-cb:checked {
        background: #ffffff !important;
        border: 2px solid #f97316 !important;
        border-radius: 6px !important;
    }
    /* Render the ORANGE checkmark inside the white square */
    #importErrorDashboard label[title*="Select or deselect"] .import-cb:checked::after {
        content: '' !important;
        position: absolute !important;
        left: 50% !important;
        top: 47% !important;
        width: 4px !important;
        height: 8px !important;
        border-right: 2.2px solid #f97316 !important;
        border-bottom: 2.2px solid #f97316 !important;
        transform: translate(-50%, -55%) rotate(45deg) !important;
        box-sizing: content-box !important;
    }

    /* Row 3 — Fix Errors + Import side by side (inline) on one row. Fix Errors sizes to its
       content; Import grows to fill the rest so "Import 456 Rows" still shows in full. */
    #importErrorDashboard .imptb-right-cluster {
        order: 4 !important;
        grid-column: 1 / -1 !important;
        margin-left: 0 !important;
        margin-top: 0 !important;
        width: 100% !important;
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        gap: 8px !important;
        flex-wrap: nowrap !important;
        min-width: 0 !important;
    }
    /* Fix Errors button — reference: peach pill rgb(254,246,231), text rgb(154,74,7),
       border rgb(246,206,139), h42, radius 12px, font 14px, flex:1, count badge inside.
       min-width:0 so it can shrink and never push the Import button off-screen. */
    /* Fix Errors button — COMPACT on mobile: wrench icon + count only ("Fix Errors" text
       hidden) so the Import CTA gets the full remaining width and its label never truncates. */
    #importErrorDashboard .imptb-right-cluster .imptb-fix-errors-btn {
        flex: 0 0 auto !important;            /* fixed to its (compact) content width */
        min-width: 0 !important;
        height: 42px !important;
        padding: 0 9px !important;
        gap: 5px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        letter-spacing: -0.3px !important;
        color: rgb(154, 74, 7) !important;
        background: rgb(254, 246, 231) !important;
        border: 1px solid rgb(246, 206, 139) !important;
        border-radius: 12px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        white-space: nowrap !important;
    }
    #importErrorDashboard .imptb-right-cluster .imptb-fix-errors-btn svg { flex-shrink: 0 !important; }
    #importErrorDashboard .imptb-right-cluster .imptb-fix-errors-btn svg {
        stroke: currentColor !important;
        width: 16px !important;
        height: 16px !important;
        flex-shrink: 0 !important;
    }
    #importErrorDashboard .imptb-right-cluster .imptb-fix-errors-btn > span:not(.imptb-fix-errors-count) {
        font-size: 14px !important;
        font-weight: 700 !important;
        color: rgb(154, 74, 7) !important;
    }
    /* Count — small rounded badge with translucent-white bg (reference) */
    #importErrorDashboard .imptb-right-cluster .imptb-fix-errors-count {
        min-width: 18px !important;
        height: 18px !important;
        padding: 0 5px !important;
        margin-left: 0 !important;
        border-radius: 9px !important;
        background: rgba(255, 255, 255, 0.55) !important;
        color: rgb(154, 74, 7) !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        line-height: 1 !important;
        font-variant-numeric: tabular-nums !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    /* Disabled Fix Errors — no errors to fix; show greyed-out pill (still visible per spec) */
    #importErrorDashboard .imptb-right-cluster .imptb-fix-errors-btn.is-disabled,
    #importErrorDashboard .imptb-right-cluster .imptb-fix-errors-btn:disabled {
        background: #f8fafc !important;
        border-color: #e2e8f0 !important;
        color: #94a3b8 !important;
        cursor: not-allowed !important;
        opacity: 0.85 !important;
    }
    #importErrorDashboard .imptb-right-cluster .imptb-fix-errors-btn.is-disabled > span:not(.imptb-fix-errors-count),
    #importErrorDashboard .imptb-right-cluster .imptb-fix-errors-btn:disabled > span:not(.imptb-fix-errors-count) {
        color: #94a3b8 !important;
    }
    #importErrorDashboard .imptb-right-cluster .imptb-fix-errors-btn.is-disabled .imptb-fix-errors-count,
    #importErrorDashboard .imptb-right-cluster .imptb-fix-errors-btn:disabled .imptb-fix-errors-count {
        color: #94a3b8 !important;
    }
    /* Import button — primary CTA, solid orange with shadow, flex-grow to fill remaining width */
    #importErrorDashboard .imptb-right-cluster #importConfirmBtnTop {
        flex: 1 1 auto !important;                  /* grow to fill the row so the label fits */
        min-width: 0 !important;
        height: 42px !important;                    /* reference: 42px */
        padding: 0 10px !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        background: rgb(234, 88, 12) !important;     /* reference: solid orange (not gradient) */
        color: #ffffff !important;
        border: 1px solid transparent !important;
        border-radius: 12px !important;
        box-shadow: rgba(255,255,255,0.3) 0 1px 0 inset, rgba(234,88,12,0.4) 0 1px 2px, rgba(234,88,12,0.45) 0 6px 14px -4px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
        letter-spacing: -0.1px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
    }
    /* Import label — keep on one line, ellipsis if truly too narrow (never clip past the edge) */
    #importErrorDashboard .imptb-right-cluster #importConfirmBtnTop > span {
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        min-width: 0 !important;
    }
    #importErrorDashboard .imptb-right-cluster #importConfirmBtnTop svg { flex-shrink: 0 !important; }
    #importErrorDashboard .imptb-right-cluster #importConfirmBtnTop svg {
        stroke: #ffffff !important;
        width: 16px !important;
        height: 16px !important;
        stroke-width: 2.6 !important;
    }
    #importErrorDashboard .imptb-right-cluster #importConfirmBtnTop.is-disabled,
    #importErrorDashboard .imptb-right-cluster #importConfirmBtnTop:disabled {
        background: #cbd5e1 !important;
        box-shadow: none !important;
        opacity: 0.65 !important;
    }

    /* ── Mobile-only Row Edit bottom-sheet — exact-match reference design ──────── */
    #rowEditModal { padding: 0 !important; align-items: flex-end !important; }
    #rowEditModalCard {
        max-width: 100% !important;
        max-height: 90vh !important;
        border-radius: 20px 20px 0 0 !important;
        animation: rowEditSlideUp 0.22s cubic-bezier(0.16,1,0.3,1) !important;
    }
    @keyframes rowEditSlideUp {
        from { transform: translateY(20px); opacity: 0; }
        to   { transform: translateY(0); opacity: 1; }
    }
    #rowEditModalHeader {
        padding: 18px 20px !important;
        border-bottom: 1px solid #f1f5f9 !important;
        background: #ffffff !important;
    }
    #rowEditModalTitle {
        font-size: 16px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        letter-spacing: -0.2px !important;
        line-height: 1.2 !important;
    }
    #rowEditModalBody {
        padding: 18px 20px 24px !important;
        background: #f8fafc !important;
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 16px 12px !important;
    }
    /* Each field cell */
    .rem-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
    /* Long fields (product / customer / supplier) span full row */
    .rem-field.rem-field-wide { grid-column: 1 / -1; }
    /* Label — small caps, slate */
    .rem-label {
        font-size: 11px !important;
        font-weight: 700 !important;
        color: #64748b !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        line-height: 1 !important;
        margin: 0 !important;
    }
    /* Input — white pill, 1.5px slate border, slate text */
    .rem-input {
        width: 100% !important;
        height: 44px !important;
        padding: 0 14px !important;
        background: #ffffff !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        color: #0f172a !important;
        outline: none !important;
        box-shadow: none !important;
        -webkit-appearance: none !important;
        appearance: none !important;
    }
    .rem-input:focus, .rem-input:focus-visible {
        border-color: #f97316 !important;
        box-shadow: 0 0 0 3px rgba(249,115,22,0.08) !important;
    }
    .rem-input::placeholder { color: #cbd5e1 !important; font-weight: 500 !important; }
    /* Read-only input (Invoice number) — grey fill, no focus glow, cursor:not-allowed */
    .rem-input.rem-input-readonly,
    .rem-input.rem-input-readonly:focus,
    .rem-input.rem-input-readonly:focus-visible {
        background: #f1f5f9 !important;
        border-color: #e2e8f0 !important;
        color: #64748b !important;
        cursor: not-allowed !important;
        box-shadow: none !important;
    }
    /* Entity picker inside the row-edit modal — match the rem-input visual style exactly */
    #rowEditModalBody .serr-picker { width: 100% !important; }
    #rowEditModalBody .serr-picker-trigger {
        height: 44px !important;
        padding: 0 14px !important;
        border-radius: 10px !important;
        border: 1.5px solid #e2e8f0 !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        background: #ffffff !important;
    }
    #rowEditModalBody .serr-picker-label { font-size: 14px !important; }
    /* Error highlight propagates to the picker trigger too */
    #rowEditModalBody .rem-field-err .serr-picker-trigger {
        border-color: #fca5a5 !important;
        background: #fef2f2 !important;
    }
    /* Prefixed input (£ amounts) */
    .rem-input-wrap { position: relative; display: block; }
    .rem-prefix {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 14px;
        font-weight: 700;
        color: #64748b;
        pointer-events: none;
    }
    .rem-input.rem-input-prefixed { padding-left: 30px !important; }
    /* Error-state highlight on a field (invalid value before save) */
    .rem-field.rem-field-err .rem-input { border-color: #fca5a5 !important; background: #fef2f2 !important; }
    .rem-field.rem-field-err .rem-label { color: #dc2626 !important; }

    /* ── Preview/Import lock blocker — mobile reference design ────────────────────
       Big circular ring with centred % number, bold "Importing… N%" title,
       muted subtitle "Batch X of Y (N rows)", peach warning pill, neutral cancel pill. */
    #importPreviewBlocker { padding: 16px !important; }
    #importPreviewBlockerCard {
        padding: 30px 24px 22px !important;
        max-width: 360px !important;
        width: 100% !important;
        border-radius: 18px !important;
        box-shadow: 0 16px 40px rgba(15,23,42,0.18) !important;
    }
    /* Progress ring — bigger on mobile, centred percentage text */
    #importPreviewBlockerRing {
        width: 120px !important;
        height: 120px !important;
        margin-bottom: 4px !important;
    }
    #importPreviewBlockerRing svg {
        width: 120px !important;
        height: 120px !important;
    }
    #importPreviewBlockerPercent {
        font-size: 22px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        letter-spacing: -0.5px !important;
    }
    /* Title — "Importing… 100%" bold black */
    #importPreviewBlockerTitle {
        font-size: 17px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        margin-top: 18px !important;
        letter-spacing: -0.2px !important;
        line-height: 1.25 !important;
    }
    /* Subtitle — "Batch 3 of 3 (198 rows)" muted slate */
    #importPreviewBlockerMsg {
        font-size: 13px !important;
        color: #94a3b8 !important;
        margin-top: 4px !important;
        font-weight: 500 !important;
        line-height: 1.4 !important;
    }
    /* Warning pill — full width, soft peach with warning triangle */
    #importPreviewBlockerWarn {
        margin-top: 20px !important;
        padding: 12px 14px !important;
        gap: 10px !important;
        background: #fef3c7 !important;
        border: 1px solid #fde68a !important;
        border-radius: 12px !important;
        align-self: stretch !important;
        display: flex !important;
        align-items: flex-start !important;
    }
    #importPreviewBlockerWarn span {
        font-size: 12px !important;
        font-weight: 500 !important;
        color: #92400e !important;
        line-height: 1.4 !important;
        text-align: left !important;
    }
    #importPreviewBlockerWarn svg {
        width: 16px !important;
        height: 16px !important;
        flex-shrink: 0 !important;
        margin-top: 1px !important;
    }
    /* Cancel button — neutral white pill, full width on mobile */
    #importPreviewBlockerCancelBtn {
        margin-top: 14px !important;
        height: 46px !important;
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 22px !important;
        border-radius: 12px !important;
        border: 1.5px solid #e2e8f0 !important;
        background: #ffffff !important;
        color: #0f172a !important;
        font-size: 14px !important;
        font-weight: 700 !important;
        gap: 10px !important;
    }
    #importPreviewBlockerCancelBtn svg {
        width: 14px !important;
        height: 14px !important;
    }

    /* ── Field Mapping modal ── */
    #fieldMappingModal { padding: 12px !important; }
    #fieldMappingModal > div { max-width: 100% !important; max-height: 94vh !important; border-radius: 12px !important; }
    #fieldMappingModal > div > div:first-child { padding: 14px 16px !important; gap: 8px !important; }
    #fieldMappingBody { padding: 14px 16px !important; }
    /* Mapping rows: stack vertically (system field above its dropdown) */
    #fieldMappingBody > div[style*="grid-template-columns:1fr 24px 1fr"] {
        grid-template-columns: 1fr !important;
        gap: 6px 0 !important;
    }
    #fieldMappingBody > div[style*="grid-template-columns:1fr 24px 1fr"] > div:nth-child(3n+2) {
        display: none !important; /* arrow column */
    }
    /* Supplier toggle card in mapping modal */
    #fieldMappingBody > div[style*="background:#fff7ed"][style*="border:1.5px solid #fed7aa"] {
        padding: 10px 12px !important;
    }
    #fieldMappingFooter { padding: 12px 16px !important; flex-direction: column !important; align-items: stretch !important; gap: 10px !important; }
    #fieldMappingFooter > div:last-child { width: 100% !important; }
    #fieldMappingFooter > div:last-child button { flex: 1 !important; }

    /* ── Fix Errors centralized modal ── */
    #salesErrorFixerModal { padding: 12px !important; }
    #salesErrorFixerModal > div { max-width: 100% !important; max-height: 94vh !important; border-radius: 12px !important; }
    #salesErrorFixerModal > div > div:first-child { padding: 14px 16px !important; gap: 10px !important; }
    #salesErrorFixerModal > div > div:first-child > div:nth-child(2) > div:first-child { font-size: 14px !important; }
    #salesErrFixerSubtitle { font-size: 11px !important; }
    /* Tabs strip — smaller, horizontally scrollable */
    #salesErrFixerBody > div:first-child { padding: 0 12px !important; }
    #salesErrFixerBody > div:first-child button { height: 42px !important; padding: 0 12px !important; font-size: 12px !important; gap: 6px !important; }
    #salesErrFixerBody > div:first-child button i { font-size: 11px !important; }
    #salesErrFixerBody > div:first-child button span:last-child { min-width: 18px !important; height: 18px !important; padding: 0 5px !important; font-size: 10px !important; }
    /* Section card */
    #salesErrFixerBody > div:nth-child(2) { padding: 14px 12px !important; }
    /* Section header — stack label and bulk-apply */
    #salesErrFixerBody > div:nth-child(2) > div > div:first-child {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px !important;
        padding: 12px 14px !important;
    }
    #salesErrFixerBody > div:nth-child(2) > div > div:first-child > div:last-child {
        width: 100% !important;
    }
    #salesErrFixerBody > div:nth-child(2) > div > div:first-child input[id^="serrBulkInput__"] { flex: 1 !important; width: auto !important; }
    /* Compact the Apply-to-all (Card 1) header on mobile — stacked, tighter, no wasted space */
    #salesErrFixerBody > div:nth-child(2) > div:first-child {
        flex-direction: column !important;
        flex-wrap: nowrap !important;
        align-items: stretch !important;
        padding: 14px 14px !important;
        gap: 10px !important;
        border-radius: 10px !important;
    }
    /* Top row (icon + title + desc) — full width, no wrap of description */
    #salesErrFixerBody > div:nth-child(2) > div:first-child > div:first-child {
        gap: 10px !important;
        min-width: 0 !important;
        flex: 0 0 auto !important;
        width: 100% !important;
    }
    /* Icon tile — smaller (was 36px) */
    #salesErrFixerBody > div:nth-child(2) > div:first-child > div:first-child > div:first-child {
        width: 32px !important;
        height: 32px !important;
        border-radius: 8px !important;
    }
    #salesErrFixerBody > div:nth-child(2) > div:first-child > div:first-child > div:first-child i {
        font-size: 13px !important;
    }
    /* Title text wrap — take remaining width */
    #salesErrFixerBody > div:nth-child(2) > div:first-child > div:first-child > div:last-child {
        flex: 1 1 auto !important;
        min-width: 0 !important;
    }
    /* Title (e.g. "Price missing") */
    #salesErrFixerBody > div:nth-child(2) > div:first-child > div:first-child > div:last-child > div:first-child {
        font-size: 14px !important;
        line-height: 1.25 !important;
    }
    /* Description ("136 rows affected · ...") — smaller, tighter, no narrow-column wrap */
    #salesErrFixerBody > div:nth-child(2) > div:first-child > div:first-child > div:last-child > div:last-child {
        font-size: 11.5px !important;
        line-height: 1.35 !important;
        margin-top: 2px !important;
        white-space: normal !important;
        word-break: normal !important;
    }
    /* Input + Apply button row — full width below title row */
    #salesErrFixerBody > div:nth-child(2) > div:first-child > div:last-child {
        width: 100% !important;
        gap: 8px !important;
        flex-shrink: 0 !important;
    }
    #salesErrFixerBody > div:nth-child(2) > div:first-child > div:last-child > div:first-child {
        flex: 1 1 auto !important;
        width: auto !important;
        height: 36px !important;
    }
    #salesErrFixerBody > div:nth-child(2) > div:first-child > div:last-child input[id^="serrBulkInput__"] {
        font-size: 13px !important;
    }
    #salesErrFixerBody > div:nth-child(2) > div:first-child > div:last-child > button {
        height: 36px !important;
        padding: 0 14px !important;
        font-size: 12.5px !important;
        gap: 6px !important;
        flex-shrink: 0 !important;
    }
    /* Table inside fix-errors modal — let it scroll horizontally */
    #salesErrFixerBody table { font-size: 11px !important; }
    #salesErrFixerBody table th { padding: 8px 10px !important; font-size: 9px !important; }
    #salesErrFixerBody table td { padding: 8px 10px !important; font-size: 11px !important; }
    #salesErrFixerBody table td input[type="text"],
    #salesErrFixerBody table td input[type="number"] { height: 32px !important; font-size: 11px !important; }
    /* Save / Remove icon buttons in fix-errors rows */
    #salesErrFixerBody table td button { width: 28px !important; height: 28px !important; }
    /* Footer */
    #salesErrorFixerModal > div > div:last-child { padding: 12px 16px !important; flex-direction: column-reverse !important; align-items: stretch !important; gap: 8px !important; }
    #salesErrorFixerModal > div > div:last-child > div:last-child { width: 100% !important; display: flex !important; gap: 8px !important; }
    #salesErrorFixerModal > div > div:last-child > div:last-child button { flex: 1 !important; }
    #salesErrFixerStatus { font-size: 11px !important; text-align: center !important; }

    /* Entity picker panel — fill width on mobile, tighter list */
    #serrPickerPanel { min-width: 0 !important; max-width: calc(100vw - 24px) !important; }
    #serrPickerPanel #serrPickerSearch { height: 36px !important; font-size: 13px !important; }
    #serrPickerPanel .serr-picker-opt { padding: 10px 12px !important; font-size: 13px !important; }
    #serrPickerPanel .serr-picker-addnew { padding: 12px !important; font-size: 13px !important; }
    .serr-picker-trigger { height: 36px !important; font-size: 13px !important; }
}

/* ── Tablet rules (768–1199px) for new Sales/Purchase Import components ────────── */
@media (min-width: 768px) and (max-width: 1199px) {
    /* Toolbar wrap tighter so Fix Errors + Import button always fit on one row */
    #importErrorDashboard > div { padding: 12px 16px !important; }
    #importErrorDashboard > div > div:first-child { gap: 10px !important; }
    #importFilterDDWrap { min-width: 170px !important; }

    /* Summary cards: keep 3-up grid but slightly tighter */
    #importSummaryCards > div { padding: 14px 16px !important; }
    #importSummaryCards > div > div:first-child { width: 38px !important; height: 38px !important; }
    #importSummaryCards > div > div:first-child i { font-size: 15px !important; }
    #importSummaryCards > div > div:last-child > div:first-child { font-size: 20px !important; }

    /* Field Mapping modal — slightly wider on tablet, but keep two-column layout */
    #fieldMappingModal > div { max-width: 720px !important; }
    #fieldMappingBody > div[style*="grid-template-columns:1fr 24px 1fr"] {
        gap: 12px 14px !important;
    }

    /* Fix Errors centralized modal — fit comfortably on tablet */
    #salesErrorFixerModal > div { max-width: 92vw !important; max-height: 90vh !important; }
    #salesErrFixerBody > div:first-child { padding: 0 18px !important; }
    #salesErrFixerBody > div:first-child button { height: 44px !important; padding: 0 14px !important; }
    /* Section card stays horizontal layout on tablet */
    #salesErrFixerBody > div:nth-child(2) { padding: 16px 18px !important; }
    /* Tighten Fix Errors per-row table on tablet */
    #salesErrFixerBody table th { padding: 9px 12px !important; }
    #salesErrFixerBody table td { padding: 9px 12px !important; font-size: 12px !important; }

    /* Entity picker panel comfortable width on tablet */
    #serrPickerPanel { min-width: 260px !important; max-width: 360px !important; }

    /* Quick-Add Entity modal */
    #serrAddEntityModal > div { max-width: 460px !important; }

    /* Preview Loading Blocker */
    #importPreviewBlocker > div { max-width: 440px !important; }

    /* Import type selector buttons (Purchase / Sales) */
    .import-type-btn { height: 40px !important; padding: 0 16px !important; font-size: 13px !important; }
}

/* ── Delete Data tab ───────────────────────────── */
/* ── Delete Data card — EXACT spec: 14px radius, hairline border, soft shadow ── */
.sdd-spec-card {
    border-radius: 14px !important;
    border: 1px solid #e7e7eb !important;
    box-shadow: 0 1px 3px rgba(15,23,42,0.04), 0 1px 2px rgba(15,23,42,0.03) !important;
}
/* ── Delete Data cards — EXACT spec UI ── */
.sdd-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
@media (max-width: 991px) { .sdd-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 575px) { .sdd-grid { grid-template-columns: 1fr; } }
/* Card — white surface, red hairline border, radius 14px (reference spec) */
.sdd-card {
    padding: 14px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #fbc7c7;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
/* Top row — icon tile + title inline on the left, count pill on the right */
.sdd-card-top {
    display: flex;
    align-items: center;
    gap: 11px;
}
/* Title sits inline beside the icon and pushes the count pill to the far right */
.sdd-card-top .sdd-card-title { flex: 1 1 auto; min-width: 0; font-size: 14.5px; }
/* Icon tile — white 38×38, red stroke, red hairline border */
.sdd-card-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: #fef4f4;
    border: 1px solid #f5c6c6;
    color: #dc2626;
    display: inline-flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.sdd-card-icon i { font-size: 16px; }
/* Count pill — soft-red capsule, red hairline border */
.sdd-card-count {
    height: 22px;
    padding: 0 9px;
    border-radius: 99px;
    background: #fef4f4;
    border: 1px solid #f5c6c6;
    color: #b11212;
    font-size: 11px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
    white-space: nowrap;
}
.sdd-card-title { font-size: 14.5px; font-weight: 800; color: #0f1115; letter-spacing: -0.1px; }
.sdd-card-desc { font-size: 11.5px; color: #6b7280; font-weight: 500; margin-top: 6px; line-height: 1.4; }
/* Delete button — full-width white pill, red text + red hairline border, trash icon */
.sdd-delete-btn {
    height: 38px; padding: 0 12px; border-radius: 10px;
    background: #ffffff; color: #dc2626;
    border: 1.5px solid #fbc7c7;
    font-weight: 800; font-size: 12.5px; letter-spacing: -0.1px;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    box-shadow: none; width: 100%; cursor: pointer;
    transition: transform 0.04s, filter 0.15s;
}
.sdd-delete-btn:hover { background: #dc2626; color: #ffffff; border-color: #dc2626; }
.sdd-delete-btn:hover svg { stroke: #ffffff; }

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
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:18px;font-weight:800;color:#0f1115;letter-spacing:-0.2px;">Settings</span>
                        <span style="display:inline-flex;align-items:center;font-size:11px;font-weight:700;color:#F27420;background:#fff5ec;border:1px solid #fed7aa;padding:3px 9px;border-radius:999px;letter-spacing:0.1px;line-height:1.4;">ADMIN</span>
                    </div>
                    <div class="settings-subtitle-desktop" style="font-size:13px;color:#6b7280;margin-top:2px;">Roles, permissions, users &amp; account</div>
                    <div class="settings-subtitle-mobile" style="display:none;">Manage your workspace</div>
                </div>
            </div>
        </div>
        {{-- Tab navigation strip — separate light-grey bar below the header --}}
        <div class="settings-tabs-strip">
            {{-- Mobile-only dropdown (≤767px) — exact copy of Stock Manager dropdown pattern --}}
            <div class="settings-tabs-mobile-dd" id="settingsTabsMobileDd">
                <button type="button" class="stmdd-trigger" id="stmddTrigger" onclick="toggleSettingsTabsDd(event)">
                    <span class="stmdd-label" id="stmddLabel">Roles</span>
                    <svg class="stmdd-caret" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="stmdd-panel" id="stmddPanel">
                    <button type="button" class="stmdd-opt active" data-tab="roles" onclick="pickSettingsTab('roles', 'Roles')">
                        Roles
                        <span class="stmdd-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                    </button>
                    <button type="button" class="stmdd-opt" data-tab="permissions" onclick="pickSettingsTab('permissions', 'Permissions')">
                        Permissions
                        <span class="stmdd-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                    </button>
                    <button type="button" class="stmdd-opt" data-tab="users" onclick="pickSettingsTab('users', 'Users')">
                        Users
                        <span class="stmdd-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                    </button>
                    <button type="button" class="stmdd-opt" data-tab="account" onclick="pickSettingsTab('account', 'Account')">
                        Account
                        <span class="stmdd-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                    </button>
                    <button type="button" class="stmdd-opt" data-tab="general" onclick="pickSettingsTab('general', 'General')">
                        General
                        <span class="stmdd-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                    </button>
                    <button type="button" class="stmdd-opt" data-tab="deletedata" onclick="pickSettingsTab('deletedata', 'Delete Data')">
                        Delete Data
                        <span class="stmdd-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                    </button>
                    <button type="button" class="stmdd-opt" data-tab="importdata" onclick="pickSettingsTab('importdata', 'Import Data')">
                        Import Data
                        <span class="stmdd-check"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                    </button>
                </div>
            </div>
            {{-- Tab tray (desktop/tablet — hidden on mobile via CSS).
                 Bordered pill container holding the 6 nav tabs + a divider + the Import Data CTA. --}}
            <div class="settings-tabs-wrap">
            <div class="settings-tabs">
                {{-- Thin line-style (lucide) icons to match the reference exactly --}}
                <button class="settings-tab active" onclick="switchSettingsTab('roles', this)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
                    <span class="tab-label">Roles</span>
                </button>
                <button class="settings-tab" onclick="switchSettingsTab('permissions', this)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <span class="tab-label">Permissions</span>
                </button>
                <button class="settings-tab" onclick="switchSettingsTab('users', this)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
                    <span class="tab-label">Users</span>
                </button>
                <button class="settings-tab" onclick="switchSettingsTab('account', this)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span class="tab-label">Account</span>
                </button>
                <button class="settings-tab" onclick="switchSettingsTab('general', this)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                    <span class="tab-label">General</span>
                </button>
                <button class="settings-tab" onclick="switchSettingsTab('deletedata', this)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    <span class="tab-label">Delete Data</span>
                </button>
                {{-- Vertical divider between the nav tabs and the Import Data CTA --}}
                <div class="settings-tab-divider"></div>
                {{-- Import Data — distinct orange CTA button (not part of the equal-width tab distribution) --}}
                <button class="settings-tab settings-tab-cta" onclick="switchSettingsTab('importdata', this)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                    <span class="tab-label">Import Data</span>
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
                            <div class="sa-grid-2 sa-grid-email-user row g-3" style="margin-top:10px;">
                                <div class="col-md-6 sa-field">
                                    <label class="sform-label">Email</label>
                                    <div style="position:relative;">
                                        <input type="email" class="sform-control" value="{{ $adminData->email ?? '' }}" style="padding-right:40px;" disabled>
                                        <span style="position:absolute;right:14px;top:50%;transform:translateY(-50%);color:#94a3b8;display:flex;align-items:center;pointer-events:none;">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                        </span>
                                    </div>
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
                                                onerror="this.onerror=null;this.src='{{ asset('img/1024px-User_icon_2.svg.png') }}';"
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

    {{-- ── Delete Data Tab — EXACT spec UI. Functionality unchanged: same openDeleteConfirm() onclicks. --}}
    <div id="tab-deletedata" class="tab-content-section">
        <div class="sform-card sdd-spec-card">
            {{-- Header — red icon tile + title + subtitle --}}
            <div class="sform-card-header" style="padding:18px 22px 14px;display:flex;align-items:center;gap:14px;border-bottom:none;">
                <span class="sform-icon" style="width:42px;height:42px;border-radius:11px;background:#dc2626;color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:inset 0 1px 0 rgba(255,255,255,0.25),0 6px 14px -4px rgba(234,88,12,0.45);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </span>
                <div style="flex:1 1 0%;min-width:0;">
                    <h2 style="margin:0;font-size:18px;font-weight:800;color:#0f1115;letter-spacing:-0.2px;">Delete Data</h2>
                    <p style="margin:2px 0 0;font-size:13.5px;color:#6b7280;">Permanently remove data from specific sections</p>
                </div>
            </div>
            <div class="sform-card-body" style="padding:4px 22px 22px;">
                {{-- Warning banner — red --}}
                <div class="sdd-warning-banner" style="padding:14px 16px;border-radius:11px;background:#fef4f4;border:1px solid #fbc7c7;display:flex;align-items:flex-start;gap:10px;margin-bottom:12px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4M12 17h.01"/></svg>
                    <span style="font-size:13px;color:#6b7280;line-height:1.5;"><b style="color:#dc2626;">This action is irreversible.</b> All selected data will be permanently deleted from the database. Please ensure you have a backup before proceeding.</span>
                </div>
                {{-- Recommended delete order banner — amber --}}
                <div class="sdd-order-banner" style="padding:14px 16px;border-radius:11px;background:#fffaeb;border:1px solid #fde68a;display:flex;align-items:flex-start;gap:10px;margin-bottom:18px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                    <div style="font-size:13px;line-height:1.6;">
                        <div style="color:#b45309;font-weight:700;margin-bottom:3px;">Recommended delete order</div>
                        <div style="color:#7a4f09;">
                            <b style="color:#d97706;">&#9312; Sales</b> clears invoices, payments &amp; consumed stock first &middot;
                            <b style="color:#d97706;">&#9313; Purchases</b> next &middot;
                            <b style="color:#d97706;">&#9314; Products / Customers / Suppliers</b> &mdash; master data cleanup last.
                        </div>
                    </div>
                </div>
                {{-- Delete cards grid — 3 columns. Same 5 active sections + same onclick handlers. --}}
                <div class="sdd-grid">
                    {{-- Sales Data --}}
                    <div class="sdd-card">
                        <div class="sdd-card-top">
                            <span class="sdd-card-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M7 17V11M12 17V7M17 17v-4"/></svg>
                            </span>
                            <span class="sdd-card-title">Sales Data</span>
                            <span class="sdd-card-count" id="sddCount-sales" data-suffix="records">{{ number_format($deleteCounts['sales'] ?? 0) }} records</span>
                        </div>
                        <div>
                            <div class="sdd-card-desc">Customer invoices, payments &amp; consumed stock</div>
                        </div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('sales','Sales Data')">
                            <svg width="14.5" height="14.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            <span>Delete&nbsp;Sales Data</span>
                        </button>
                    </div>
                    {{-- Purchases Data --}}
                    <div class="sdd-card">
                        <div class="sdd-card-top">
                            <span class="sdd-card-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                            </span>
                            <span class="sdd-card-title">Purchases Data</span>
                            <span class="sdd-card-count" id="sddCount-purchases" data-suffix="records">{{ number_format($deleteCounts['purchases'] ?? 0) }} records</span>
                        </div>
                        <div>
                            <div class="sdd-card-desc">Supplier invoices, payments, supplier-returns, dumps &amp; added stock</div>
                        </div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('purchases','Purchases Data')">
                            <svg width="14.5" height="14.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            <span>Delete&nbsp;Purchases Data</span>
                        </button>
                    </div>
                    {{-- Products --}}
                    <div class="sdd-card">
                        <div class="sdd-card-top">
                            <span class="sdd-card-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 9.4l-9-5.2M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/></svg>
                            </span>
                            <span class="sdd-card-title">Products</span>
                            <span class="sdd-card-count" id="sddCount-products" data-suffix="records">{{ number_format($deleteCounts['products'] ?? 0) }} records</span>
                        </div>
                        <div>
                            <div class="sdd-card-desc">Product master records</div>
                        </div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('products','Products')">
                            <svg width="14.5" height="14.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            <span>Delete&nbsp;Products</span>
                        </button>
                    </div>
                    {{-- Customers --}}
                    <div class="sdd-card">
                        <div class="sdd-card-top">
                            <span class="sdd-card-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/></svg>
                            </span>
                            <span class="sdd-card-title">Customers</span>
                            <span class="sdd-card-count" id="sddCount-customers" data-suffix="records">{{ number_format($deleteCounts['customers'] ?? 0) }} records</span>
                        </div>
                        <div>
                            <div class="sdd-card-desc">Customer master records</div>
                        </div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('customers','Customers')">
                            <svg width="14.5" height="14.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            <span>Delete&nbsp;Customers</span>
                        </button>
                    </div>
                    {{-- Suppliers --}}
                    <div class="sdd-card">
                        <div class="sdd-card-top">
                            <span class="sdd-card-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14"/><path d="M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"/></svg>
                            </span>
                            <span class="sdd-card-title">Suppliers</span>
                            <span class="sdd-card-count" id="sddCount-suppliers" data-suffix="records">{{ number_format($deleteCounts['suppliers'] ?? 0) }} records</span>
                        </div>
                        <div>
                            <div class="sdd-card-desc">Supplier master records</div>
                        </div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('suppliers','Suppliers')">
                            <svg width="14.5" height="14.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            <span>Delete&nbsp;Suppliers</span>
                        </button>
                    </div>
                    {{-- Stock & Payments hidden — cascaded automatically by Sales/Purchases delete.
                    <div class="sdd-card">
                        <div class="sdd-card-top">
                            <span class="sdd-card-icon"><i class="fa fa-archive"></i></span>
                            <span class="sdd-card-count">0 rows</span>
                        </div>
                        <div>
                            <div class="sdd-card-title">Stock</div>
                            <div class="sdd-card-desc">All stock movements and closing stock records</div>
                        </div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('stock','Stock')"><i class="fa fa-trash"></i> Delete Stock</button>
                    </div>
                    <div class="sdd-card">
                        <div class="sdd-card-top">
                            <span class="sdd-card-icon"><i class="fa fa-credit-card"></i></span>
                            <span class="sdd-card-count">0 rows</span>
                        </div>
                        <div>
                            <div class="sdd-card-title">Payments</div>
                            <div class="sdd-card-desc">All customer payment records and payment entries</div>
                        </div>
                        <button class="sdd-delete-btn" onclick="openDeleteConfirm('payments','Payments')"><i class="fa fa-trash"></i> Delete Payments</button>
                    </div>
                    --}}
                </div>
            </div>
        </div>
    </div>

    {{-- ── Import Data Tab ─────────────────────────── --}}
    <div id="tab-importdata" class="tab-content-section">
        <div class="sform-card">
            {{-- Header — EXACT spec: icon tile + title + subtitle (left) --}}
            <div class="sform-card-header">
                <span class="sform-icon">
                    {{-- Desktop/tablet — original header icon (unchanged) --}}
                    <svg class="sform-icon-desktop" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                    {{-- Mobile-only (≤767px) — reference upload icon: up-arrow into tray --}}
                    <svg class="sform-icon-mobile" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 9 12 4 17 9"/><line x1="12" y1="4" x2="12" y2="16"/></svg>
                </span>
                <div style="flex:1 1 0%;">
                    <h2 style="margin:0;font-size:18px;font-weight:800;color:#0f1115;letter-spacing:-0.2px;">Import Data</h2>
                    <p style="margin:3px 0 0;font-size:13.5px;color:#6b7280;">Upload Excel files to import Sales or Purchase data</p>
                </div>
            </div>
            {{-- Import Type Selector tab bar — clean inline (no gray tray) on desktop. --}}
            <div id="importTabBar" class="import-tab-bar" style="display:flex;gap:10px;margin-bottom:18px;padding:0;background:transparent;border:none;">
                <button type="button" class="import-type-btn active" data-type="purchase" onclick="switchImportType('purchase', this)">
                    <i class="fa fa-shopping-cart"></i> Purchase<span class="itb-suffix"> Import</span>
                </button>
                <button type="button" class="import-type-btn" data-type="sales" onclick="switchImportType('sales', this)">
                    <i class="fa fa-line-chart"></i> Sales<span class="itb-suffix"> Import</span>
                </button>
            </div>
            <div class="sform-card-body">

                {{-- Purchase Import Section (default active — stock IN comes before stock OUT) --}}
                <div id="import-section-purchase" class="import-section active">
                    <div data-upload-ui="purchase" class="irc-banner" style="margin-bottom:18px;padding:11px 14px;border-radius:11px;background:#fff5ec;border:1px solid #fed7aa;display:flex;align-items:center;gap:10px;">
                        {{-- Desktop/tablet layout — EXACT UI from user spec.
                             Orange-filled 24×24 circle + info icon, "Required columns:" label,
                             code-style mono chips for each required column. --}}
                        <span class="irc-desktop-only" style="width:24px;height:24px;border-radius:50%;background:rgb(234, 88, 12);color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        </span>
                        <span class="irc-desktop-only" style="font-size:12.5px;font-weight:700;color:#F27420;">Required columns:</span>
                        <span class="irc-desktop-only" style="flex:1 1 0%;display:flex;flex-wrap:wrap;gap:6px;">
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">date</code>
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">invoice_no</code>
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">supplier_name</code>
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">product_name</code>
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">qty</code>
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">unit_price</code>
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">total</code>
                        </span>
                        {{-- Mobile-only reference design — shown via CSS at ≤767px (unchanged) --}}
                        <div class="irc-mobile">
                            <div class="irc-mobile-head">
                                <span class="irc-mobile-head-left">
                                    <span class="irc-mobile-title">Required columns</span><span class="irc-mobile-count-pill">7</span>
                                </span>
                                <a href="#" onclick="event.preventDefault();" class="irc-mobile-template">
                                    <svg class="irc-tpl-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                                    Template
                                </a>
                            </div>
                            <div class="irc-mobile-chips">
                                <span class="irc-chip">date</span>
                                <span class="irc-chip">invoice_no</span>
                                <span class="irc-chip">supplier_name</span>
                                <span class="irc-chip">product_name</span>
                                <span class="irc-chip">qty</span>
                                <span class="irc-chip">unit_price</span>
                                <span class="irc-chip">total</span>
                            </div>
                        </div>
                    </div>
                    <div data-upload-ui="purchase" class="import-upload-zone" id="purchaseUploadZone" onclick="document.getElementById('purchaseFileInput').click();"
                         ondragover="event.preventDefault();this.classList.add('dragover');"
                         ondragleave="this.classList.remove('dragover');"
                         ondrop="event.preventDefault();this.classList.remove('dragover');handleFileDrop(event,'purchase');">
                        <input type="file" id="purchaseFileInput" accept=".xlsx,.xls,.csv" style="display:none;" onchange="handleFileSelect(this,'purchase')">
                        {{-- Desktop / tablet — EXACT spec UI: light-peach square icon tile + cloud-upload outline icon, title + browse link, subtitle, 3 footer chips --}}
                        <div class="import-upload-icon iuz-desktop-icon" style="width:56px;height:56px;border-radius:14px;background:#ffe5d0;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                {{-- Cloud silhouette (open at bottom so the arrow rises into it) --}}
                                <path d="M16 16.5h2.5a3.5 3.5 0 0 0 0-7 5 5 0 0 0-9.793-1.5A4 4 0 0 0 6.5 16.5H8"/>
                                {{-- Up-arrow inside the cloud --}}
                                <polyline points="9 13 12 10 15 13"/>
                                <line x1="12" y1="10" x2="12" y2="18"/>
                            </svg>
                        </div>
                        <div class="import-upload-text iuz-desktop-only" style="font-size:18px;font-weight:800;color:#0f1115;letter-spacing:-0.2px;margin-bottom:6px;">Drop your file or <span class="import-upload-browse" style="color:rgb(234, 88, 12);text-decoration:underline;text-underline-offset:3px;font-weight:800;">browse</span></div>
                        <div class="import-upload-hint iuz-desktop-only" style="font-size:13px;color:#6b7280;font-weight:500;margin-bottom:18px;">Excel or CSV · .xlsx, .xls, .csv · up to 10 MB · 50,000 rows max</div>
                        <div class="iuz-desktop-only" style="display:flex;align-items:center;justify-content:center;gap:22px;flex-wrap:wrap;font-size:12px;color:#9ca3af;font-weight:500;">
                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Files never leave your browser
                            </span>
                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/></svg>
                                Auto-detects column headers
                            </span>
                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><polyline points="9 11 12 14 16 9"/></svg>
                                Validates before saving
                            </span>
                        </div>
                        {{-- Mobile-only reference design — shown via CSS at ≤767px --}}
                        <div class="iuz-mobile">
                            <div class="iuz-doc-icon">
                                {{-- File-text icon — orange stroke, no fill (reference lucide icon) --}}
                                <svg class="iuz-doc-svg" viewBox="0 0 24 24" fill="none" stroke="#c2410c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <path d="M14 2v6h6"/>
                                    <path d="M8 13h8M8 17h8M10 9h.01"/>
                                </svg>
                                {{-- Plus badge at the BOTTOM-RIGHT with white ring --}}
                                <span class="iuz-doc-plus" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                </span>
                            </div>
                            <div class="iuz-mobile-title">Drop your Excel file</div>
                            <div class="iuz-mobile-sub">or tap below to choose · .xlsx, .xls, .csv · 10 MB max</div>
                            <span class="iuz-mobile-btn">Choose file <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7"/></svg></span>
                        </div>
                    </div>
                    {{-- Uploaded file pill — EXACT spec: green card, white file-icon tile, name + meta line, Replace button --}}
                    <div id="purchaseFileName" class="import-file-pill" style="display:none;padding:14px 16px;border-radius:14px;background:#f1fbf4;border:1px solid #bbf2cc;align-items:center;gap:14px;">
                        <span class="ifp-icon" style="width:46px;height:46px;border-radius:11px;background:#ffffff;color:#0f7a38;flex-shrink:0;display:flex;align-items:center;justify-content:center;border:1px solid #bbf2cc;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M10 9h.01"/></svg>
                        </span>
                        <div class="ifp-body" style="flex:1 1 0%;min-width:0;">
                            <div class="ifp-name" id="purchaseFileNameText" style="font-weight:800;font-size:15px;color:#0f1115;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
                            <div class="ifp-meta" id="purchaseFileMeta" style="font-size:12px;color:#0f7a38;margin-top:3px;display:flex;align-items:center;gap:8px;font-weight:600;"></div>
                        </div>
                        <button type="button" class="ifp-replace" onclick="document.getElementById('purchaseFileInput').click();" style="height:42px;padding:0 16px;border-radius:10px;background:#ffffff;color:#0f1115;border:1px solid #e7e7eb;font-weight:700;font-size:13.5px;letter-spacing:-0.1px;display:inline-flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 1px 2px rgba(15,17,21,0.04);width:auto;cursor:pointer;transition:transform 0.04s, filter 0.15s;">
                            <svg width="15.5" height="15.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                            <span>Replace</span>
                        </button>
                    </div>
                </div>

                {{-- Sales Import Section --}}
                <div id="import-section-sales" class="import-section">
                    <div data-upload-ui="sales" class="irc-banner" style="margin-bottom:18px;padding:11px 14px;border-radius:11px;background:#fff5ec;border:1px solid #fed7aa;display:flex;align-items:center;gap:10px;">
                        {{-- Desktop/tablet layout — EXACT UI from user spec.
                             Orange-filled 24×24 circle + info icon, "Required columns:" label,
                             code-style mono chips for each required column. --}}
                        <span class="irc-desktop-only" style="width:24px;height:24px;border-radius:50%;background:rgb(234, 88, 12);color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        </span>
                        <span class="irc-desktop-only" style="font-size:12.5px;font-weight:700;color:#F27420;">Required columns:</span>
                        <span class="irc-desktop-only" style="flex:1 1 0%;display:flex;flex-wrap:wrap;gap:6px;">
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">date</code>
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">invoice_no</code>
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">customer_name</code>
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">product_name</code>
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">qty</code>
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">unit_price</code>
                            <code style="font-family:'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace;font-size:11.5px;font-weight:700;color:#F27420;background:#ffffff;border:1px solid #fed7aa;border-radius:6px;padding:2px 7px;letter-spacing:-0.2px;">total</code>
                        </span>
                        {{-- Mobile-only reference design --}}
                        <div class="irc-mobile">
                            <div class="irc-mobile-head">
                                <span class="irc-mobile-head-left">
                                    <span class="irc-mobile-title">Required columns</span><span class="irc-mobile-count-pill">7</span>
                                </span>
                                <a href="#" onclick="event.preventDefault();" class="irc-mobile-template">
                                    <svg class="irc-tpl-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                                    Template
                                </a>
                            </div>
                            <div class="irc-mobile-chips">
                                <span class="irc-chip">date</span>
                                <span class="irc-chip">invoice_no</span>
                                <span class="irc-chip">customer_name</span>
                                <span class="irc-chip">product_name</span>
                                <span class="irc-chip">qty</span>
                                <span class="irc-chip">unit_price</span>
                                <span class="irc-chip">total</span>
                            </div>
                        </div>
                    </div>
                    <div data-upload-ui="sales" class="import-upload-zone" id="salesUploadZone" onclick="document.getElementById('salesFileInput').click();"
                         ondragover="event.preventDefault();this.classList.add('dragover');"
                         ondragleave="this.classList.remove('dragover');"
                         ondrop="event.preventDefault();this.classList.remove('dragover');handleFileDrop(event,'sales');">
                        <input type="file" id="salesFileInput" accept=".xlsx,.xls,.csv" style="display:none;" onchange="handleFileSelect(this,'sales')">
                        {{-- Desktop / tablet — EXACT spec UI: light-peach square icon tile + cloud-upload outline icon, title + browse link, subtitle, 3 footer chips --}}
                        <div class="import-upload-icon iuz-desktop-icon" style="width:56px;height:56px;border-radius:14px;background:#ffe5d0;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                {{-- Cloud silhouette (open at bottom so the arrow rises into it) --}}
                                <path d="M16 16.5h2.5a3.5 3.5 0 0 0 0-7 5 5 0 0 0-9.793-1.5A4 4 0 0 0 6.5 16.5H8"/>
                                {{-- Up-arrow inside the cloud --}}
                                <polyline points="9 13 12 10 15 13"/>
                                <line x1="12" y1="10" x2="12" y2="18"/>
                            </svg>
                        </div>
                        <div class="import-upload-text iuz-desktop-only" style="font-size:18px;font-weight:800;color:#0f1115;letter-spacing:-0.2px;margin-bottom:6px;">Drop your file or <span class="import-upload-browse" style="color:rgb(234, 88, 12);text-decoration:underline;text-underline-offset:3px;font-weight:800;">browse</span></div>
                        <div class="import-upload-hint iuz-desktop-only" style="font-size:13px;color:#6b7280;font-weight:500;margin-bottom:18px;">Excel or CSV · .xlsx, .xls, .csv · up to 10 MB · 50,000 rows max</div>
                        <div class="iuz-desktop-only" style="display:flex;align-items:center;justify-content:center;gap:22px;flex-wrap:wrap;font-size:12px;color:#9ca3af;font-weight:500;">
                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Files never leave your browser
                            </span>
                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/></svg>
                                Auto-detects column headers
                            </span>
                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><polyline points="9 11 12 14 16 9"/></svg>
                                Validates before saving
                            </span>
                        </div>
                        {{-- Mobile-only reference design — shown via CSS at ≤767px --}}
                        <div class="iuz-mobile">
                            <div class="iuz-doc-icon">
                                {{-- File-text icon — orange stroke, no fill (reference lucide icon) --}}
                                <svg class="iuz-doc-svg" viewBox="0 0 24 24" fill="none" stroke="#c2410c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <path d="M14 2v6h6"/>
                                    <path d="M8 13h8M8 17h8M10 9h.01"/>
                                </svg>
                                {{-- Plus badge at the BOTTOM-RIGHT with white ring --}}
                                <span class="iuz-doc-plus" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                                </span>
                            </div>
                            <div class="iuz-mobile-title">Drop your Excel file</div>
                            <div class="iuz-mobile-sub">or tap below to choose · .xlsx, .xls, .csv · 10 MB max</div>
                            <span class="iuz-mobile-btn">Choose file <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7"/></svg></span>
                        </div>
                    </div>
                    {{-- Uploaded file pill — EXACT spec: green card, white file-icon tile, name + meta line, Replace button --}}
                    <div id="salesFileName" class="import-file-pill" style="display:none;padding:14px 16px;border-radius:14px;background:#f1fbf4;border:1px solid #bbf2cc;align-items:center;gap:14px;">
                        <span class="ifp-icon" style="width:46px;height:46px;border-radius:11px;background:#ffffff;color:#0f7a38;flex-shrink:0;display:flex;align-items:center;justify-content:center;border:1px solid #bbf2cc;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h8M10 9h.01"/></svg>
                        </span>
                        <div class="ifp-body" style="flex:1 1 0%;min-width:0;">
                            <div class="ifp-name" id="salesFileNameText" style="font-weight:800;font-size:15px;color:#0f1115;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></div>
                            <div class="ifp-meta" id="salesFileMeta" style="font-size:12px;color:#0f7a38;margin-top:3px;display:flex;align-items:center;gap:8px;font-weight:600;"></div>
                        </div>
                        <button type="button" class="ifp-replace" onclick="document.getElementById('salesFileInput').click();" style="height:42px;padding:0 16px;border-radius:10px;background:#ffffff;color:#0f1115;border:1px solid #e7e7eb;font-weight:700;font-size:13.5px;letter-spacing:-0.1px;display:inline-flex;align-items:center;justify-content:center;gap:7px;box-shadow:0 1px 2px rgba(15,17,21,0.04);width:auto;cursor:pointer;transition:transform 0.04s, filter 0.15s;">
                            <svg width="15.5" height="15.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                            <span>Replace</span>
                        </button>
                    </div>
                </div>

                {{-- Field Mapping styles --}}
                <style>
                    .fmap-dd:focus { outline: none; }
                    .fmap-dd-trigger:hover { border-color: #f97316 !important; }
                    .fmap-dd-opt:hover {
                        background: linear-gradient(135deg,#f97316,#ea580c) !important;
                        color: #fff !important;
                        font-weight: 600 !important;
                        font-style: normal !important;
                    }
                    .fmap-dd-opt:hover .fmap-dd-sample {
                        color: rgba(255,255,255,0.85) !important;
                    }
                    .fmap-dd-menu::-webkit-scrollbar { width: 6px; }
                    .fmap-dd-menu::-webkit-scrollbar-track { background: #f8fafc; }
                    .fmap-dd-menu::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
                    .fmap-dd-menu::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
                </style>

                {{-- Field Mapping Modal --}}
                <div id="fieldMappingModal" style="position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:99998;display:none;align-items:center;justify-content:center;padding:20px;">
                    <div style="background:#fff;border-radius:16px;width:100%;max-width:760px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                        <div style="padding:18px 22px;border-bottom:1.5px solid #f1f5f9;display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#f97316,#ea580c);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i class="fa fa-exchange" style="font-size:14px;color:#fff;"></i>
                            </div>
                            <div style="flex:1;">
                                <div style="font-size:15px;font-weight:800;color:#0f172a;">Map Your Columns</div>
                                <div style="font-size:11px;color:#94a3b8;margin-top:1px;">Match each column from your file to a system field. Required fields are marked with <span style="color:#f97316;">*</span>.</div>
                            </div>
                            <button type="button" onclick="cancelMappingModal()" style="background:#f1f5f9;border:none;width:32px;height:32px;border-radius:50%;font-size:18px;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;">&times;</button>
                        </div>
                        <div id="fieldMappingBody" style="padding:18px 22px;overflow-y:auto;flex:1;">
                            <div style="text-align:center;padding:30px;color:#94a3b8;"><i class="fa fa-spinner fa-spin" style="font-size:24px;"></i><div style="margin-top:8px;font-size:13px;">Reading file headers...</div></div>
                        </div>
                        <div id="fieldMappingFooter" style="display:flex;justify-content:space-between;align-items:center;gap:8px;padding:14px 22px;border-top:1.5px solid #f1f5f9;">
                            <div id="fieldMappingHint" style="font-size:11px;color:#94a3b8;"></div>
                            <div style="display:flex;gap:8px;">
                                <button type="button" onclick="cancelMappingModal()" style="height:38px;padding:0 18px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:12px;font-weight:600;cursor:pointer;">Cancel</button>
                                <button type="button" id="fieldMappingContinueBtn" onclick="confirmMapping()" style="height:38px;padding:0 20px;border-radius:8px;border:none;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;font-size:12px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;box-shadow:0 2px 8px rgba(249,115,22,0.3);">
                                    <i class="fa fa-arrow-right"></i> Continue to Preview
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Mobile-only "Edit Row" bottom-sheet modal ─────────────────────────────
                     Opened by startRowEdit() on screens ≤767px. On wider screens, the original
                     inline-edit row remains active (no change in behavior). --}}
                <div id="rowEditModal" style="position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:99998;display:none;align-items:flex-end;justify-content:center;padding:0;backdrop-filter:blur(2px);">
                    <div id="rowEditModalCard" style="background:#fff;border-radius:20px 20px 0 0;width:100%;max-width:480px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 -8px 32px rgba(15,23,42,0.18);overflow:hidden;">
                        {{-- Header --}}
                        <div id="rowEditModalHeader" style="display:flex;align-items:center;gap:12px;padding:18px 20px;border-bottom:1px solid #f1f5f9;">
                            <div style="width:36px;height:36px;border-radius:10px;background:#fff7ed;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div id="rowEditModalTitle" style="font-size:15px;font-weight:800;color:#0f172a;line-height:1.2;letter-spacing:-0.2px;">Edit row</div>
                                <div style="font-size:12px;color:#94a3b8;margin-top:2px;font-weight:500;line-height:1.3;">Fix any invalid fields and save</div>
                            </div>
                            <button type="button" onclick="closeRowEditModal()" aria-label="Close" style="width:32px;height:32px;border-radius:10px;background:#f1f5f9;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748b;flex-shrink:0;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                        {{-- Form body --}}
                        <div id="rowEditModalBody" style="padding:18px 20px;overflow-y:auto;flex:1;display:grid;grid-template-columns:1fr 1fr;gap:14px 12px;">
                            {{-- Filled dynamically by openRowEditModal() --}}
                        </div>
                        {{-- Footer --}}
                        <div style="display:flex;gap:10px;padding:14px 20px;border-top:1px solid #f1f5f9;background:#ffffff;">
                            <button type="button" onclick="closeRowEditModal()" style="flex:1;height:46px;border-radius:12px;border:1.5px solid #e2e8f0;background:#ffffff;color:#0f172a;font-size:14px;font-weight:700;cursor:pointer;outline:none;">Cancel</button>
                            <button type="button" id="rowEditModalSaveBtn" onclick="submitRowEditModal()" style="flex:2;height:46px;border-radius:12px;border:none;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;font-size:14px;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 12px rgba(249,115,22,0.30);">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                <span>Save Row</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>{{-- /.sform-card-body --}}
        </div>{{-- /.sform-card (Import Data card ends here) --}}

        {{-- Preview Area — its OWN separate card, sibling of the Import Data card above --}}
        <div id="importPreviewArea" style="display:none;margin-top:20px;">
            {{-- Wrapper card --}}
            <div style="background:#fff;border-radius:16px;border:1px solid #e5e7eb;box-shadow:0 2px 12px rgba(0,0,0,0.05);overflow:hidden;">

                        {{-- 1. Header bar — EXACT UI from user-provided spec.
                             Orange 42×42 rounded grid-icon tile + "Data Preview" title + green "Auto-mapped N columns" pill
                             + muted subtitle + "Re-map columns" ghost button (right-aligned). --}}
                        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:0;padding:18px 22px;">
                            <span style="width:42px;height:42px;border-radius:11px;background:rgb(234, 88, 12);color:#ffffff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:inset 0 1px 0 rgba(255,255,255,0.25),0 6px 14px -4px rgba(234,88,12,0.45);">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/></svg>
                            </span>
                            <div style="flex:1 1 0%;">
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <h2 style="margin:0;font-size:18px;font-weight:800;color:#0f1115;letter-spacing:-0.2px;white-space:nowrap;">Data Preview</h2>
                                </div>
                                <p id="importPreviewSubtitle" style="margin:3px 0 0;font-size:13.5px;color:#6b7280;">Only "Ready to Import" rows will be saved to the database.</p>
                            </div>
                        </div>

                        {{-- 2. Summary Cards (desktop/tablet — 4 colored stat cards) --}}
                        <div id="importSummaryCardsWrap" style="padding:16px 22px 0;background:#ffffff;">
                            <div id="importSummaryCards" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:14px;"></div>
                        </div>
                        {{-- 2-mobile. Stock-Check–style collapsible STOCK SUMMARY card (≤767px only).
                             Filled by renderImportSummaryMobile(); toggled via toggleImportSummary(). --}}
                        <div id="importSummaryMobile" style="display:none;padding:10px 16px 0;background:#ffffff;"></div>

                        {{-- 2b. Error Dashboard & Filter --}}
                        <div id="importErrorDashboard" style="display:none;"></div>
                        <div id="importFilterBar" style="display:none;"></div>
                        <div id="importBulkEditBar" style="display:none;"></div>

                        {{-- 3. Table --}}
                        <div id="importPreviewTable"></div>

                        {{-- 4. Errors --}}
                        <div id="importErrors" style="display:none;margin:16px 22px 0;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px 18px;max-height:150px;overflow-y:auto;"></div>

                        {{-- 5. Action Buttons --}}
                        <div style="display:flex;justify-content:flex-end;gap:8px;padding:16px 22px;border-top:1.5px solid #f1f5f9;">
                            <button type="button" onclick="clearImport()" style="height:38px;padding:0 18px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:12px;font-weight:600;cursor:pointer;outline:none !important;display:inline-flex;align-items:center;gap:5px;transition:all 0.15s;">
                                <i class="fa fa-times" style="font-size:10px;"></i> Cancel
                            </button>
                            <button type="button" id="importConfirmBtn" onclick="executeImport()" style="height:38px;padding:0 20px;border-radius:8px;border:none;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;font-size:12px;font-weight:700;cursor:pointer;outline:none !important;display:inline-flex;align-items:center;gap:5px;box-shadow:0 2px 8px rgba(249,115,22,0.3);transition:opacity 0.15s;">
                                <i class="fa fa-check"></i> Import Data
                            </button>
                        </div>

            </div>{{-- /.preview wrapper card --}}
        </div>{{-- /#importPreviewArea --}}
    </div>{{-- /#tab-importdata --}}

    {{-- ── Sales Import: Centralized Error Fixer Modal ───────────────────────── --}}
    <div id="salesErrorFixerModal" style="position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:99998;display:none;align-items:center;justify-content:center;padding:24px;backdrop-filter:blur(2px);">
        <div style="background:#fff;border-radius:18px;width:100%;max-width:980px;max-height:92vh;display:flex;flex-direction:column;box-shadow:0 24px 60px rgba(15,23,42,0.25);overflow:hidden;">
            {{-- Header --}}
            <div style="padding:18px 24px;border-bottom:1.5px solid #f1f5f9;display:flex;align-items:center;gap:14px;background:#ffffff;">
                <div style="width:40px;height:40px;border-radius:10px;background:#f97316;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 6px rgba(249,115,22,0.25);">
                    <i class="fa fa-wrench" style="font-size:16px;color:#fff;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <span style="font-size:18px;font-weight:800;color:#0f172a;letter-spacing:-0.2px;">Fix import errors</span>
                        <span id="salesErrFixerCountBadge" style="display:inline-flex;align-items:center;height:22px;padding:0 10px;border-radius:2px;background:#fff7ed;color:#ea580c;font-size:11.5px;font-weight:700;letter-spacing:0.4px;text-transform:uppercase;">0 rows</span>
                    </div>
                    <div id="salesErrFixerSubtitle" style="font-size:13px;color:#64748b;margin-top:3px;line-height:1.4;">Resolve issues row-by-row, or apply a value to all at once.</div>
                </div>
                <button type="button" onclick="closeErrorFixerModal()" style="background:#f1f5f9;border:none;width:34px;height:34px;border-radius:50%;font-size:18px;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;flex-shrink:0;transition:background 0.15s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">&times;</button>
            </div>

            {{-- Body --}}
            <div id="salesErrFixerBody" style="padding:0;overflow-y:auto;flex:1;background:#ffffff;">
                {{-- Sections injected by JS --}}
            </div>

            {{-- Footer --}}
            <div style="padding:16px 24px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;gap:12px;background:#fff;">
                <div id="salesErrFixerStatus" style="font-size:13px;color:#64748b;font-weight:500;display:flex;align-items:center;gap:14px;"></div>
                <div style="display:flex;gap:8px;">
                    <button type="button" onclick="closeErrorFixerModal()" style="height:38px;padding:0 18px;border-radius:9px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:13px;font-weight:600;cursor:pointer;outline:none;transition:border-color 0.15s,background 0.15s;" onmouseover="this.style.borderColor='#d1d5db';this.style.background='#f9fafb'" onmouseout="this.style.borderColor='#e5e7eb';this.style.background='#fff'">Close</button>
                    <button type="button" id="salesErrFixerSaveContinueBtn" onclick="closeErrorFixerModal()" style="height:38px;padding:0 18px;border-radius:9px;border:none;background:#f97316;color:#fff;font-size:13px;font-weight:700;cursor:pointer;outline:none;display:inline-flex;align-items:center;gap:7px;box-shadow:0 1px 3px rgba(249,115,22,0.30);transition:background 0.15s;" onmouseover="this.style.background='#ea580c'" onmouseout="this.style.background='#f97316'">
                        <i class="fa fa-check" style="font-size:11px;"></i> Save &amp; continue
                    </button>
                    <button type="button" id="salesErrFixerDoneBtn" onclick="closeErrorFixerModal()" style="height:38px;padding:0 18px;border-radius:9px;border:none;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;font-size:13px;font-weight:700;cursor:pointer;outline:none;display:none;align-items:center;gap:7px;box-shadow:0 1px 3px rgba(22,163,74,0.30);">
                        <i class="fa fa-check" style="font-size:11px;"></i> All Fixed — Return to Preview
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Quick-Add Entity Popup (Customer / Supplier) ───────────────────────
         Triggered from the Sales-Import "Fix Errors" entity dropdown.
         Single Name field — full profile can be edited later from Customers/Suppliers page. --}}
    <div id="serrAddEntityModal" style="position:fixed;inset:0;background:rgba(15,23,42,0.55);z-index:100001;display:none;align-items:center;justify-content:center;padding:24px;backdrop-filter:blur(2px);">
        <div style="background:#fff;border-radius:14px;width:100%;max-width:420px;box-shadow:0 20px 50px rgba(0,0,0,0.2);overflow:hidden;">
            <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;">
                <div style="width:34px;height:34px;border-radius:9px;background:#fff7ed;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i id="serrAddEntityIcon" class="fa fa-user" style="font-size:14px;color:#ea580c;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div id="serrAddEntityTitle" style="font-size:14px;font-weight:800;color:#0f172a;">Add New Customer</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:1px;">More details can be added later from the Customers page.</div>
                </div>
                <button type="button" onclick="_serrCloseAddPopup()" style="background:#f1f5f9;border:none;width:30px;height:30px;border-radius:50%;font-size:16px;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;flex-shrink:0;">&times;</button>
            </div>
            <div style="padding:18px 20px;">
                <label id="serrAddEntityLabel" for="serrAddEntityName" style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:6px;">Customer Name</label>
                <input type="text" id="serrAddEntityName" placeholder="Enter name" autocomplete="off" style="width:100%;height:38px;padding:0 12px;border-radius:8px;border:1.5px solid #e5e7eb;background:#fafafa;font-size:13px;font-weight:500;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s,background 0.15s;" onfocus="this.style.borderColor='#f97316';this.style.background='#fff';this.style.boxShadow='0 0 0 3px rgba(249,115,22,0.08)'" onblur="this.style.borderColor='#e5e7eb';this.style.background='#fafafa';this.style.boxShadow='none'">
                <div id="serrAddEntityError" style="display:none;margin-top:8px;font-size:12px;font-weight:600;color:#dc2626;line-height:1.4;"></div>
            </div>
            <div style="padding:12px 20px 16px;display:flex;justify-content:flex-end;gap:8px;border-top:1px solid #f1f5f9;background:#fafafa;">
                <button type="button" onclick="_serrCloseAddPopup()" style="height:36px;padding:0 16px;border-radius:8px;border:1.5px solid #e5e7eb;background:#fff;color:#64748b;font-size:12px;font-weight:600;cursor:pointer;outline:none;transition:all 0.15s;">Cancel</button>
                <button type="button" id="serrAddEntitySaveBtn" onclick="_serrSubmitAddPopup()" style="height:36px;padding:0 18px;border-radius:8px;border:none;background:#f97316;color:#fff;font-size:12px;font-weight:700;cursor:pointer;outline:none;box-shadow:0 1px 3px rgba(249,115,22,0.25);transition:background 0.15s;display:inline-flex;align-items:center;gap:6px;" onmouseover="this.style.background='#ea580c'" onmouseout="this.style.background='#f97316'">
                    <i class="fa fa-check" style="font-size:11px;"></i> Save
                </button>
            </div>
        </div>
    </div>

    {{-- ── Import Preview Loading Blocker ─────────────────────────────────
         Full-screen overlay shown while the preview table is being built so the
         user cannot navigate elsewhere mid-parse. --}}
    <div id="importPreviewBlocker" style="position:fixed;inset:0;z-index:99997;background:rgba(15,23,42,0.55);display:none;align-items:center;justify-content:center;padding:24px;backdrop-filter:blur(2px);">
        <div id="importPreviewBlockerCard" style="background:#fff;border-radius:16px;padding:36px 40px;box-shadow:0 24px 60px rgba(15,23,42,0.25);display:flex;flex-direction:column;align-items:center;text-align:center;width:100%;max-width:480px;box-sizing:border-box;">
            {{-- Progress ring — outer track + percentage-driven arc + centered percent text.
                 In preview-parse mode (no pct available) the arc animates as a spinner;
                 in import mode (pct available) the arc fills proportionally and centre text shows live %. --}}
            <div id="importPreviewBlockerRing" style="position:relative;width:120px;height:120px;flex-shrink:0;">
                {{-- Outer ring — conic-gradient drives the orange arc; CSS var --pct sets fill percentage.
                     Background = orange arc up to --pct, peach track for the rest. Inner white circle hides the centre. --}}
                <div id="importPreviewBlockerArc" style="position:absolute;inset:0;border-radius:50%;background:conic-gradient(#f97316 calc(var(--pct,0) * 1%), #fff3e6 0);transition:background 0.3s ease;"></div>
                {{-- Inner mask — white circle that masks the inner area so only a 10px-wide ring remains visible --}}
                <div style="position:absolute;inset:10px;border-radius:50%;background:#ffffff;"></div>
                {{-- Centred percentage text --}}
                <div id="importPreviewBlockerPercent" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.5px;font-variant-numeric:tabular-nums;">0%</div>
            </div>
            <div id="importPreviewBlockerTitle" style="font-size:18px;font-weight:700;color:#0f172a;line-height:1.3;letter-spacing:-0.01em;margin-top:20px;">
                Preparing your data preview
            </div>
            <div id="importPreviewBlockerMsg" style="font-size:13px;color:#64748b;margin-top:6px;line-height:1.55;max-width:380px;">
                We're reading and validating your file. This may take a moment for large imports.
            </div>
            {{-- Subtle warning pill — peach background, warning icon, short cautionary message (exact spec) --}}
            <div id="importPreviewBlockerWarn" style="margin-top:20px;display:inline-flex;align-items:flex-start;gap:8px;padding:11px 14px;background:#fef3c7;border:1px solid #fde68a;border-radius:10px;max-width:100%;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;">
                    <path d="M12 9v4"/><path d="M12 17h.01"/>
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                </svg>
                <span style="font-size:12px;font-weight:600;color:#92400e;letter-spacing:0.01em;line-height:1.4;text-align:left;">Please don't switch tabs or leave this page</span>
            </div>
            {{-- Cancel button — coral/red outlined pill per spec (red border + red text + red X icon) --}}
            <button type="button" id="importPreviewBlockerCancelBtn" onclick="_cancelRunningImport()" style="display:none;margin-top:18px;height:44px;width:100%;max-width:340px;padding:0 22px;border-radius:12px;border:1.5px solid #fca5a5;background:#ffffff;color:#ef4444;font-size:13.5px;font-weight:700;cursor:pointer;outline:none;transition:background 0.15s,border-color 0.15s;align-items:center;justify-content:center;gap:8px;" onmouseover="this.style.background='#fef2f2';this.style.borderColor='#f87171'" onmouseout="this.style.background='#ffffff';this.style.borderColor='#fca5a5'">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                <span>Cancel Import</span>
            </button>
        </div>
    </div>

    {{-- ── Import Complete Modal ──────────────────────────────────────────
         Shown after all chunks finish successfully. Green check tile + summary + 2 CTA buttons.
         Numbers ({imported}/{skipped}/{duplicates}) populated dynamically from totals before showing. --}}
    <div id="importCompleteModal" style="position:fixed;inset:0;z-index:99998;background:rgba(15,23,42,0.55);display:none;align-items:center;justify-content:center;padding:24px;backdrop-filter:blur(2px);">
        <div style="position:relative;background:#fff;border-radius:18px;padding:36px 40px 32px;box-shadow:0 24px 60px rgba(15,23,42,0.25);display:flex;flex-direction:column;align-items:center;text-align:center;width:100%;max-width:480px;box-sizing:border-box;">
            {{-- Close (×) — lets the user dismiss the toast manually without waiting for auto-dismiss --}}
            <button type="button" onclick="_dismissImportCompleteModal()" aria-label="Close" style="position:absolute;top:12px;right:12px;width:30px;height:30px;border-radius:9px;background:#f4f4f6;border:none;color:#6b7280;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.15s,color 0.15s;" onmouseover="this.style.background='#e7e7eb';this.style.color='#1a1d24'" onmouseout="this.style.background='#f4f4f6';this.style.color='#6b7280'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            {{-- Green check tile — soft green circle with thicker check SVG --}}
            <div style="width:64px;height:64px;border-radius:50%;background:#e9f9ef;border:1px solid #bbf2cc;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#0f7a38" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
            {{-- Title --}}
            <div style="font-size:18px;font-weight:800;color:#0f1115;line-height:1.3;letter-spacing:-0.01em;margin-top:18px;">Import complete</div>
            {{-- Summary line 1 — "We saved N rows to your purchase/sales ledger." (bold green count) --}}
            <div id="importCompleteSummary1" style="font-size:13.5px;color:#475569;margin-top:10px;line-height:1.55;">
                We saved <strong id="importCompleteImported" style="color:#0f7a38;font-weight:800;">0</strong> rows to your <span id="importCompleteLedgerLabel">ledger</span>.
            </div>
            {{-- Summary line 2 — "N rows were skipped · M duplicates detected." Only shown when skipped > 0. --}}
            <div id="importCompleteSummary2" style="font-size:13.5px;color:#475569;margin-top:2px;line-height:1.55;display:none;">
                <strong id="importCompleteSkipped" style="color:#475569;font-weight:700;">0</strong> rows were skipped · <strong id="importCompleteDuplicates" style="color:#475569;font-weight:700;">0</strong> duplicates detected.
            </div>
        </div>
    </div>
    <style>
        @keyframes importBlockerSpin {
            0%   { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    {{-- ── General Tab ─────────────────────────────── --}}
    <div id="tab-general" class="tab-content-section">
        <div class="sform-card sg-header-card">
            <div class="sform-card-header">
                <div class="sform-icon">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </div>
                <div>
                    <div style="font-size:14px;font-weight:800;color:#0f172a;">General Settings</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:1px;">Configure application-wide options</div>
                </div>
            </div>
            <div class="sform-card-body sg-body">
                <div class="stoggle-row">
                    <span class="sg-toggle-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></span>
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
    <div class="sa-pw-modal-box" onclick="event.stopPropagation()">
        {{-- Modal Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:10px;background:#fff1e6;border:1px solid #f6c9a8;display:flex;align-items:center;justify-content:center;">
                    <i class="fa fa-lock" style="font-size:14px;color:rgb(234, 88, 12);"></i>
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
                    {{-- Segmented strength meter — directly below New Password (reference UI) --}}
                    <div id="mobPwStrengthWrap" style="display:none;margin-top:8px;">
                        <div style="display:flex;gap:5px;">
                            <div class="mob-pw-seg" style="flex:1;height:5px;border-radius:99px;background:#e5e7eb;transition:all 0.25s;"></div>
                            <div class="mob-pw-seg" style="flex:1;height:5px;border-radius:99px;background:#e5e7eb;transition:all 0.25s;"></div>
                            <div class="mob-pw-seg" style="flex:1;height:5px;border-radius:99px;background:#e5e7eb;transition:all 0.25s;"></div>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px;">
                            <span id="mobPwStrengthLabel" style="font-size:12px;font-weight:700;color:#16a34a;"></span>
                            <span id="mobPwStrengthHint" style="font-size:11px;color:#94a3b8;font-weight:500;"></span>
                        </div>
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
    // On mobile push the toast below the fixed header so it doesn't overlap the nav bar.
    var topPos = window.innerWidth <= 767 ? '74px' : '24px';
    el.style.cssText = 'position:fixed;top:'+topPos+';right:24px;z-index:99999;background:'+bg+';color:#fff;padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 6px 24px rgba(0,0,0,0.15);display:flex;align-items:center;gap:10px;max-width:340px;transition:opacity 0.3s;';
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
    if (btn) btn.classList.add('active');
    // Keep mobile dropdown in sync (label + checkmark) whenever a tab change happens.
    syncSettingsTabsDd(tabName);
    // Opening the Delete Data tab pulls fresh live counts so the cards reflect any
    // imports/deletes done elsewhere, without needing a page reload.
    if (tabName === 'deletedata' && typeof refreshDeleteCounts === 'function') {
        refreshDeleteCounts();
    }
}

/* ── Mobile-only tabs dropdown (≤767px) — mirrors Stock Manager pattern ── */
function toggleSettingsTabsDd(e) {
    if (e) e.stopPropagation();
    var trig = document.getElementById('stmddTrigger');
    var panel = document.getElementById('stmddPanel');
    if (!trig || !panel) return;
    var isOpen = panel.classList.toggle('open');
    trig.classList.toggle('open', isOpen);
}
function pickSettingsTab(tabName, label) {
    // Drive the canonical tab switcher via the matching desktop button so all wiring stays consistent.
    var desktopBtn = document.querySelector('.settings-tab[onclick*="\'' + tabName + '\'"]');
    if (desktopBtn) {
        switchSettingsTab(tabName, desktopBtn);
    } else {
        switchSettingsTab(tabName, null);
    }
    var lbl = document.getElementById('stmddLabel');
    if (lbl && label) lbl.textContent = label;
    // Close the panel after selection
    var trig = document.getElementById('stmddTrigger');
    var panel = document.getElementById('stmddPanel');
    if (panel) panel.classList.remove('open');
    if (trig) trig.classList.remove('open');
}
function syncSettingsTabsDd(tabName) {
    var labelMap = {
        roles: 'Roles', permissions: 'Permissions', users: 'Users',
        account: 'Account', general: 'General', deletedata: 'Delete Data', importdata: 'Import Data'
    };
    var lbl = document.getElementById('stmddLabel');
    if (lbl && labelMap[tabName]) lbl.textContent = labelMap[tabName];
    document.querySelectorAll('.stmdd-opt').forEach(function(o){
        o.classList.toggle('active', o.getAttribute('data-tab') === tabName);
    });
}
// Click-outside closes the dropdown
document.addEventListener('click', function(e){
    var dd = document.getElementById('settingsTabsMobileDd');
    if (!dd || !dd.contains(e.target)) {
        var panel = document.getElementById('stmddPanel');
        var trig = document.getElementById('stmddTrigger');
        if (panel) panel.classList.remove('open');
        if (trig) trig.classList.remove('open');
    }
});

(function(){
    var validTabs = ['roles','permissions','users','account','general','deletedata','importdata'];
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
// Pull fresh live row counts from the server and repaint every Delete-Data count pill,
// so the cards stay accurate (e.g. after a sales/purchase import) without a page reload.
function refreshDeleteCounts() {
    $.ajax({
        url: "{{ route('management.settings.data.counts') }}",
        method: 'GET',
        success: function(r) {
            var counts = (r && r.counts) || {};
            ['sales','purchases','products','customers','suppliers'].forEach(function(sec) {
                var el = document.getElementById('sddCount-' + sec);
                if (!el || counts[sec] == null) return;
                var suffix = el.getAttribute('data-suffix') || 'rows';
                el.textContent = Number(counts[sec]).toLocaleString() + ' ' + suffix;
            });
        }
    });
}
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
            // Capture the section BEFORE closeDeleteConfirm() — that resets _ddSection to null.
            var deletedSection = _ddSection;
            closeDeleteConfirm();
            showSettingsToast(r.message || 'Data deleted successfully.', 'success');
            // Refresh EVERY Delete-Data count pill from the live counts the server returned —
            // a delete in one section can affect others, so all cards stay accurate without a reload.
            var counts = r.counts || {};
            ['sales','purchases','products','customers','suppliers'].forEach(function(sec) {
                var el = document.getElementById('sddCount-' + sec);
                if (!el) return;
                var suffix = el.getAttribute('data-suffix') || 'rows';
                // Server count when available; otherwise fall back to 0 for the section just deleted.
                var n = (counts[sec] != null) ? counts[sec] : (sec === deletedSection ? 0 : null);
                if (n != null) el.textContent = Number(n).toLocaleString() + ' ' + suffix;
            });
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

// Segmented strength meter for the mobile Change Password modal (reference UI):
// 3 colour-coded segments + a label ("Strong password") + a hint ("12 chars · A1!").
function calcPwStrengthSeg(pw) {
    var wrap = document.getElementById('mobPwStrengthWrap');
    var segs = document.querySelectorAll('#mobPwStrengthWrap .mob-pw-seg');
    var lbl  = document.getElementById('mobPwStrengthLabel');
    var hint = document.getElementById('mobPwStrengthHint');
    if (!wrap) return;
    if (!pw) { wrap.style.display='none'; return; }
    wrap.style.display='block';

    var hasUpper = /[A-Z]/.test(pw);
    var hasNum   = /[0-9]/.test(pw);
    var hasSym   = /[^a-zA-Z0-9]/.test(pw);

    // 1 = weak, 2 = fair, 3 = strong
    var level = 1;
    if (pw.length >= 8 && (hasUpper || hasNum)) level = 2;
    if (pw.length >= 10 && hasUpper && hasNum) level = 3;

    var map = {
        1: { color:'#dc2626', text:'Weak password' },
        2: { color:'#f59e0b', text:'Fair password' },
        3: { color:'#16a34a', text:'Strong password' },
    };
    var m = map[level];
    segs.forEach(function(s, i){ s.style.background = (i < level) ? m.color : '#e5e7eb'; });
    if (lbl)  { lbl.textContent = m.text; lbl.style.color = m.color; }

    // Hint: "<len> chars · A1!" — show only the composition pieces present
    var comp = '';
    if (hasUpper) comp += 'A';
    if (hasNum)   comp += '1';
    if (hasSym)   comp += '!';
    if (hint) hint.textContent = pw.length + ' chars' + (comp ? ' · ' + comp : '');
}

document.getElementById('mob_new_password') && document.getElementById('mob_new_password').addEventListener('input', function(){
    calcPwStrengthSeg(this.value);
});

document.getElementById('new_password') && document.getElementById('new_password').addEventListener('input', function(){
    calcPwStrength(this.value,'pwStrengthBar','pwStrengthLabel','pwStrengthWrap');
});

/* ── Import Data ─────────────────────────────────── */
var _importType = 'purchase';
var _importFile = { sales: null, purchase: null };

// On load, force only the default (purchase) section visible — guards against the
// non-active section showing if any CSS/cache leaves both displayed.
// Also force the active section to stack its children vertically on mobile
// (Required-columns banner first, then the Drop-file zone) — inline so it works
// even if the stylesheet is served stale from cache.
function _applyImportSectionLayout() {
    var isMobile = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
    document.querySelectorAll('.import-section').forEach(function(s){
        var on = s.classList.contains('active');
        if (!on) { s.style.display = 'none'; return; }
        if (isMobile) {
            s.style.display = 'flex';
            s.style.flexDirection = 'column';
            s.style.gap = '14px';
            s.style.width = '100%';
            var kids = s.querySelectorAll(':scope > .irc-banner, :scope > .import-upload-zone');
            kids.forEach(function(k){ k.style.width = '100%'; k.style.margin = '0'; k.style.boxSizing = 'border-box'; });
        } else {
            s.style.display = '';
            s.style.flexDirection = '';
            s.style.gap = '';
        }
    });
}
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.import-section').forEach(function(s){
        s.classList.toggle('active', s.id === ('import-section-' + _importType));
    });
    _applyImportSectionLayout();
});
window.addEventListener('resize', _applyImportSectionLayout);

// ─── Per-type state buckets ────────────────────────────────────────────────
// Sales and Purchase imports keep independent state. When user clicks the
// other import-type button, we snapshot the current type's session and
// restore the target's session — so users can flip back and forth without
// losing their preview, mapping, edits, fixes, selection, etc.
var _importSessions = { sales: null, purchase: null };

// Snapshot every per-type state variable into _importSessions[type].
// Called BEFORE switching away from `type`. Stores nothing if no preview
// has been built yet for this type.
function _captureImportSession(type) {
    // Only capture when there's a meaningful state worth restoring
    if (!_previewData && !_fieldMapping && !_mappingMeta && !_importFile[type]) {
        _importSessions[type] = null;
        return;
    }
    _importSessions[type] = {
        previewData: _previewData,
        fieldMapping: _fieldMapping,
        mappingMeta: _mappingMeta,
        selectedRows: _selectedRows,
        editingRow: _editingRow,
        editValues: _editValues,
        filterMode: _filterMode,
        filteredIndices: _filteredIndices,
        searchTerm: _searchTerm,
        editedRowsMap: _editedRowsMap,
        previewPage: _previewPage,
        previewPerPage: _previewPerPage,
        salesSupplierRequired: _salesSupplierRequired,
        salesErrFixerActiveTab: _salesErrFixerActiveTab,
        previewVisible: document.getElementById('importPreviewArea').style.display !== 'none',
    };
}

// Show/hide the preview area and toggle the body class used by mobile CSS
// to hide the Required-columns banner + upload zone + file pill while preview is active.
// ── Strict numeric input guard ─────────────────────────────────────────────
// Used by every numeric edit field (qty / unit price / total / sell price).
// Behavior:
//   - Only digits 0-9 and ONE decimal point are accepted.
//   - Negative sign, plus, scientific notation (e/E), letters, spaces, symbols are all blocked at keypress.
//   - Paste / drop is sanitized — stripped down to a clean positive-decimal string.
//   - Empty input stays empty (placeholder still shows). Default min=0, step=any.
window._impNumGuard = {
    // onkeydown — allow digits, single dot, and a leading '-' (backend silently converts negatives to positive).
    // Block letters and any other symbols so the field can't hold a non-numeric string.
    keydown: function(e) {
        var k = e.key;
        // Allow navigation + control keys
        if (k === 'Backspace' || k === 'Delete' || k === 'Tab' || k === 'Enter' || k === 'Escape' ||
            k === 'ArrowLeft' || k === 'ArrowRight' || k === 'ArrowUp' || k === 'ArrowDown' ||
            k === 'Home' || k === 'End') return;
        // Allow Ctrl/Cmd combos (copy/paste/select-all)
        if (e.ctrlKey || e.metaKey) return;
        // Allow a single leading '-' (caret must be at position 0 and no '-' already present)
        if (k === '-' && (this.selectionStart || 0) === 0 && this.value.indexOf('-') === -1) return;
        // Allow one decimal point
        if (k === '.' && this.value.indexOf('.') === -1) return;
        // Allow digits
        if (/^[0-9]$/.test(k)) return;
        // Block everything else (letters, e/E, +, *, etc.)
        e.preventDefault();
    },
    // oninput — strip anything that isn't a digit, dot, or leading '-'
    input: function(e) {
        var v = this.value;
        var cleaned = v.replace(/[^\d.\-]/g, '');
        // Allow '-' only as the first character; strip all other '-'
        if (cleaned.length > 0) {
            var head = cleaned.charAt(0);
            var rest = cleaned.substring(1).replace(/-/g, '');
            cleaned = (head === '-' ? '-' : head) + rest;
        }
        // Keep only the FIRST decimal point
        var firstDot = cleaned.indexOf('.');
        if (firstDot !== -1) {
            cleaned = cleaned.substring(0, firstDot + 1) + cleaned.substring(firstDot + 1).replace(/\./g, '');
        }
        if (cleaned !== v) this.value = cleaned;
    },
    // onpaste — pre-sanitize clipboard before it reaches the input
    paste: function(e) {
        var dt = (e.clipboardData || window.clipboardData);
        if (!dt) return;
        var text = dt.getData('text');
        if (!text) return;
        e.preventDefault();
        var cleaned = String(text).replace(/[^\d.\-]/g, '');
        // Only allow '-' at position 0
        if (cleaned.length > 0) {
            var head = cleaned.charAt(0);
            var rest = cleaned.substring(1).replace(/-/g, '');
            cleaned = (head === '-' ? '-' : head) + rest;
        }
        var firstDot = cleaned.indexOf('.');
        if (firstDot !== -1) {
            cleaned = cleaned.substring(0, firstDot + 1) + cleaned.substring(firstDot + 1).replace(/\./g, '');
        }
        // Insert at the caret position
        var start = this.selectionStart || 0, end = this.selectionEnd || 0;
        this.value = this.value.slice(0, start) + cleaned + this.value.slice(end);
        var pos = start + cleaned.length;
        try { this.setSelectionRange(pos, pos); } catch (err) {}
        // Trigger oninput so any data binding updates
        var ev = new Event('input', { bubbles: true });
        this.dispatchEvent(ev);
    }
};

// ─────────────────────────────────────────────────────────────────────────────
// window._impDatePicker — minimal, dependency-free modern date picker.
// Used by the import preview edit row's Date column.
// Shows DD/MM/YYYY in the input; opens a small calendar popover anchored to the input.
// On day click: writes back DD/MM/YYYY, calls updateEditValue(ci, value), closes popover.
// ─────────────────────────────────────────────────────────────────────────────
window._impDatePicker = (function(){
    var pop = null, anchorEl = null, anchorCi = -1;
    var cur = new Date();           // current visible month
    var selected = null;            // selected Date or null
    var MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var DOW = ['Su','Mo','Tu','We','Th','Fr','Sa'];

    function pad(n){ return n < 10 ? ('0' + n) : ('' + n); }
    function toDDMMYYYY(d){ return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear(); }
    // Parse any of: DD/MM/YYYY, YYYY-MM-DD, "DD Mon YYYY", "DD-MM-YYYY". Returns Date or null.
    function parseAny(s){
        if (!s) return null;
        s = String(s).trim();
        if (s === '') return null;
        var m;
        // ISO YYYY-MM-DD
        m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})/);
        if (m) return new Date(+m[1], +m[2]-1, +m[3]);
        // DD/MM/YYYY or DD-MM-YYYY
        m = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
        if (m) return new Date(+m[3], +m[2]-1, +m[1]);
        // DD Mon YYYY
        m = s.match(/^(\d{1,2})\s+([A-Za-z]{3,})\s+(\d{4})/);
        if (m) {
            var mi = MONTH_NAMES.findIndex(function(mn){ return mn.toLowerCase().indexOf(m[2].toLowerCase()) === 0; });
            if (mi >= 0) return new Date(+m[3], mi, +m[1]);
        }
        var d = new Date(s);
        return isNaN(d.getTime()) ? null : d;
    }
    // Public: convert any stored value into DD/MM/YYYY for display.
    function toDisplay(v){
        var d = parseAny(v);
        return d ? toDDMMYYYY(d) : (v || '');
    }

    function ensurePopover(){
        if (pop) return pop;
        pop = document.createElement('div');
        pop.id = '_impDatePopover';
        pop.style.cssText = [
            'position:absolute','z-index:100100','display:none',
            'background:#ffffff','border:1px solid #e7e7eb','border-radius:14px',
            'box-shadow:0 12px 36px rgba(15,23,42,0.12),0 2px 6px rgba(15,23,42,0.05)',
            'padding:14px','width:286px','font-family:inherit','user-select:none'
        ].join(';');
        document.body.appendChild(pop);
        // Stop clicks inside popover from triggering outside-close
        pop.addEventListener('mousedown', function(e){ e.stopPropagation(); });
        // Global click-outside listener (attached once)
        document.addEventListener('mousedown', function(e){
            if (pop.style.display === 'none') return;
            if (anchorEl && (e.target === anchorEl || anchorEl.contains(e.target))) return;
            close();
        });
        return pop;
    }

    function render(){
        var year = cur.getFullYear();
        var month = cur.getMonth();
        var firstDow = new Date(year, month, 1).getDay();
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var prevMonthDays = new Date(year, month, 0).getDate();
        var today = new Date(); today.setHours(0,0,0,0);
        var sel = selected ? new Date(selected.getFullYear(), selected.getMonth(), selected.getDate()) : null;

        var h = '';
        // Header — month label (clickable to year jump later) + up/down arrows for prev/next month
        h += '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">';
        h +=   '<div style="font-size:14px;font-weight:700;color:#0f1115;letter-spacing:-0.1px;">' + MONTH_NAMES[month] + ' ' + year + '</div>';
        h +=   '<div style="display:flex;gap:6px;">';
        h +=     '<button type="button" aria-label="Previous month" onclick="window._impDatePicker._nav(-1)" style="width:26px;height:26px;border-radius:8px;border:1px solid #eef0f2;background:#ffffff;color:#6b7280;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;padding:0;">';
        h +=       '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
        h +=     '</button>';
        h +=     '<button type="button" aria-label="Next month" onclick="window._impDatePicker._nav(1)" style="width:26px;height:26px;border-radius:8px;border:1px solid #eef0f2;background:#ffffff;color:#6b7280;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;padding:0;">';
        h +=       '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
        h +=     '</button>';
        h +=   '</div>';
        h += '</div>';
        // Day-of-week row
        h += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:4px;">';
        for (var i = 0; i < 7; i++){
            h += '<div style="text-align:center;font-size:11px;font-weight:700;color:#6b7280;padding:6px 0;">' + DOW[i] + '</div>';
        }
        h += '</div>';
        // Grid
        h += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;">';
        // leading prev-month days
        for (var i = 0; i < firstDow; i++){
            var d = prevMonthDays - firstDow + 1 + i;
            h += '<button type="button" disabled style="height:34px;border-radius:8px;border:none;background:transparent;color:#cbd5e1;font-size:12.5px;font-weight:500;cursor:default;">' + d + '</button>';
        }
        // current month
        for (var d = 1; d <= daysInMonth; d++){
            var thisDate = new Date(year, month, d);
            var isToday = thisDate.getTime() === today.getTime();
            var isSelected = sel && thisDate.getFullYear() === sel.getFullYear() && thisDate.getMonth() === sel.getMonth() && thisDate.getDate() === sel.getDate();
            var bg = isSelected ? '#f97316' : 'transparent';
            var fg = isSelected ? '#ffffff' : (isToday ? '#f97316' : '#0f1115');
            var fw = (isSelected || isToday) ? '700' : '500';
            var bd = isToday && !isSelected ? '1px solid #f97316' : 'none';
            h += '<button type="button" onclick="window._impDatePicker._pick(' + year + ',' + month + ',' + d + ')"';
            h +=   ' style="height:34px;border-radius:8px;border:' + bd + ';background:' + bg + ';color:' + fg + ';font-size:12.5px;font-weight:' + fw + ';cursor:pointer;padding:0;transition:background 0.12s;"';
            if (!isSelected) h += ' onmouseover="this.style.background=\'#f4f6f8\'" onmouseout="this.style.background=\'transparent\'"';
            h += '>' + d + '</button>';
        }
        // trailing next-month days to fill grid
        var cells = firstDow + daysInMonth;
        var trail = (7 - (cells % 7)) % 7;
        for (var i = 1; i <= trail; i++){
            h += '<button type="button" disabled style="height:34px;border-radius:8px;border:none;background:transparent;color:#cbd5e1;font-size:12.5px;font-weight:500;cursor:default;">' + i + '</button>';
        }
        h += '</div>';
        // Footer — Clear + Today
        h += '<div style="display:flex;justify-content:space-between;margin-top:10px;padding-top:8px;border-top:1px solid #eef0f2;">';
        h +=   '<button type="button" onclick="window._impDatePicker._clear()" style="background:none;border:none;color:#f97316;font-size:12.5px;font-weight:600;cursor:pointer;padding:4px 6px;">Clear</button>';
        h +=   '<button type="button" onclick="window._impDatePicker._today()" style="background:none;border:none;color:#f97316;font-size:12.5px;font-weight:600;cursor:pointer;padding:4px 6px;">Today</button>';
        h += '</div>';
        pop.innerHTML = h;
    }

    function position(){
        var rect = anchorEl.getBoundingClientRect();
        var top = rect.bottom + window.scrollY + 6;
        var left = rect.left + window.scrollX;
        // Keep popover inside viewport horizontally
        var popWidth = 286 + 28; // include padding
        if (left + popWidth > window.scrollX + window.innerWidth - 12) {
            left = window.scrollX + window.innerWidth - popWidth - 12;
        }
        pop.style.top = top + 'px';
        pop.style.left = left + 'px';
    }

    function open(inputEl, ci){
        ensurePopover();
        anchorEl = inputEl;
        anchorCi = ci;
        // Initial month/selected from current value
        var d = parseAny(inputEl.value);
        if (d){ selected = d; cur = new Date(d.getFullYear(), d.getMonth(), 1); }
        else  { selected = null; cur = new Date(); cur.setDate(1); }
        render();
        pop.style.display = 'block';
        position();
    }

    function close(){
        if (pop) pop.style.display = 'none';
        anchorEl = null; anchorCi = -1;
    }

    function _nav(delta){
        cur = new Date(cur.getFullYear(), cur.getMonth() + delta, 1);
        render();
    }
    function _pick(y, m, d){
        var picked = new Date(y, m, d);
        selected = picked;
        var val = toDDMMYYYY(picked);
        if (anchorEl){
            anchorEl.value = val;
            if (typeof updateEditValue === 'function' && anchorCi >= 0) {
                updateEditValue(anchorCi, val);
            }
        }
        close();
    }
    function _clear(){
        if (anchorEl){
            anchorEl.value = '';
            if (typeof updateEditValue === 'function' && anchorCi >= 0) {
                updateEditValue(anchorCi, '');
            }
        }
        close();
    }
    function _today(){
        var t = new Date();
        _pick(t.getFullYear(), t.getMonth(), t.getDate());
    }

    return { open: open, close: close, toDisplay: toDisplay, _nav: _nav, _pick: _pick, _clear: _clear, _today: _today };
})();

function _setPreviewVisible(visible) {
    var pa = document.getElementById('importPreviewArea');
    if (!pa) return;
    pa.style.display = visible ? 'block' : 'none';
    document.body.classList.toggle('import-preview-active', !!visible);
}

// Restore the saved session for `type` into the live state variables and DOM.
// If no session exists, fully clear so the freshly-shown section is in pristine state.
function _restoreImportSession(type) {
    var s = _importSessions[type];
    if (!s) {
        // No prior state — clear everything except the file (which is type-keyed already)
        _previewData = null;
        _fieldMapping = null;
        _mappingMeta = null;
        _selectedRows = {};
        _editingRow = -1;
        _editValues = {};
        _filterMode = 'all';
        _filteredIndices = [];
        _searchTerm = '';
        _editedRowsMap = {};
        _previewPage = 1;
        _salesSupplierRequired = false;
        _salesErrFixerActiveTab = null;
        _setPreviewVisible(false);
        return;
    }
    _previewData            = s.previewData;
    _fieldMapping           = s.fieldMapping;
    _mappingMeta            = s.mappingMeta;
    _selectedRows           = s.selectedRows || {};
    _editingRow             = (s.editingRow != null) ? s.editingRow : -1;
    _editValues             = s.editValues || {};
    _filterMode             = s.filterMode || 'all';
    _filteredIndices        = s.filteredIndices || [];
    _searchTerm             = s.searchTerm || '';
    _editedRowsMap          = s.editedRowsMap || {};
    _previewPage            = s.previewPage || 1;
    _previewPerPage         = s.previewPerPage || 50;
    _salesSupplierRequired  = !!s.salesSupplierRequired;
    _salesErrFixerActiveTab = s.salesErrFixerActiveTab || null;
    // Re-render preview if it was visible when the user left
    if (s.previewVisible && _previewData) {
        _setPreviewVisible(true);
        renderPreview(_previewData, true);
    } else {
        _setPreviewVisible(false);
    }
}

function switchImportType(type, btn) {
    if (_importType === type) return; // no-op
    // Toggle the visible section FIRST so a failure later can't leave both showing.
    document.querySelectorAll('.import-type-btn').forEach(function(b){ b.classList.remove('active'); });
    if (btn) btn.classList.add('active');
    document.querySelectorAll('.import-section').forEach(function(s){
        s.classList.toggle('active', s.id === ('import-section-' + type));
    });
    _applyImportSectionLayout();
    // Snapshot the type we're leaving so user can come back to the same state
    try { _captureImportSession(_importType); } catch(e) {}
    _importType = type;
    // Restore (or clear) the target type's saved state
    _restoreImportSession(type);
}

function handleFileSelect(input, type) {
    var file = input.files[0];
    if (!file) return;
    // Fresh file picked — discard any old session for this type
    _importSessions[type] = null;
    _importFile[type] = file;
    showFileName(type, file.name);
    openMappingModal(type, file);
}

function handleFileDrop(event, type) {
    var file = event.dataTransfer.files[0];
    if (!file) return;
    // Fresh file dropped — discard any old session for this type
    _importSessions[type] = null;
    _importFile[type] = file;
    // Update the file input too
    var dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById(type + 'FileInput').files = dt.files;
    showFileName(type, file.name);
    openMappingModal(type, file);
}

// ─── Field Mapping ───────────────────────────────────────
var _fieldMapping = null;       // { system_field_key => file_column_key }
var _mappingMeta = null;        // { file_columns, system_fields, suggested } from server
var _salesSupplierRequired = false;  // Sales-only: Supplier toggle state (default OFF = optional)

function openMappingModal(type, file) {
    var modal = document.getElementById('fieldMappingModal');
    var body = document.getElementById('fieldMappingBody');
    body.innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8;"><i class="fa fa-spinner fa-spin" style="font-size:24px;"></i><div style="margin-top:8px;font-size:13px;">Reading file headers...</div></div>';
    document.getElementById('fieldMappingHint').textContent = '';
    // Reset Sales-only supplier toggle to OFF each time the modal opens
    _salesSupplierRequired = false;
    modal.style.display = 'flex';
    _setPreviewVisible(false);

    var formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);
    formData.append('_token', '{{ csrf_token() }}');

    $.ajax({
        url: "{{ route('management.settings.import.headers') }}",
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if (!res.success) {
                body.innerHTML = '<div style="text-align:center;padding:30px;color:#dc2626;"><i class="fa fa-exclamation-circle" style="font-size:24px;"></i><div style="margin-top:8px;font-size:13px;">' + (res.message || 'Failed to read headers.') + '</div></div>';
                return;
            }
            _mappingMeta = res;
            _fieldMapping = Object.assign({}, res.suggested || {});
            renderMappingForm();
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to read headers.';
            body.innerHTML = '<div style="text-align:center;padding:30px;color:#dc2626;"><i class="fa fa-exclamation-circle" style="font-size:24px;"></i><div style="margin-top:8px;font-size:13px;">' + msg + '</div></div>';
        }
    });
}

function renderMappingForm() {
    if (!_mappingMeta) return;
    var body = document.getElementById('fieldMappingBody');
    var sysFields = (_mappingMeta.system_fields || []).map(function(sf) {
        // Sales-only: Supplier field's required flag is controlled by the toggle (default OFF = optional).
        if (_importType === 'sales' && sf.key === 'entry_by') {
            return Object.assign({}, sf, { required: !!_salesSupplierRequired });
        }
        return sf;
    });
    var fileCols  = _mappingMeta.file_columns || [];

    var html = '';

    // Sales-only Supplier toggle row (mirrors Settings → General "Show Suppliers" style)
    if (_importType === 'sales') {
        html += '<div style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 14px;margin-bottom:14px;background:#fff7ed;border:1.5px solid #fed7aa;border-radius:10px;">';
        html +=   '<div style="flex:1;">';
        html +=     '<div style="font-size:13px;font-weight:700;color:#0f172a;">Require Supplier</div>';
        html +=     '<div style="font-size:11px;color:#94a3b8;margin-top:2px;">When OFF, the Supplier column is optional and can be skipped. Turn ON to make it mandatory.</div>';
        html +=   '</div>';
        html +=   '<label class="stoggle-switch" style="flex-shrink:0;">';
        html +=     '<input type="checkbox" id="toggleSalesSupplierRequired" ' + (_salesSupplierRequired ? 'checked' : '') + ' onchange="onSalesSupplierToggle(this)">';
        html +=     '<span class="stoggle-track"></span>';
        html +=   '</label>';
        html += '</div>';
    }

    html += '<div style="display:grid;grid-template-columns:1fr 24px 1fr;gap:14px 14px;align-items:center;">';
    html += '<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;">System Field</div>';
    html += '<div></div>';
    html += '<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;">Your File Column</div>';

    sysFields.forEach(function(sf) {
        var current = _fieldMapping[sf.key] || '';
        var currentLabel = '— Skip —';
        var sample = '';
        if (current) {
            for (var i = 0; i < fileCols.length; i++) {
                if (fileCols[i].key === current) {
                    currentLabel = fileCols[i].label;
                    sample = fileCols[i].sample || '';
                    break;
                }
            }
        }
        html += '<div style="font-size:13px;color:#0f172a;font-weight:600;">' + escapeHtml(sf.label) + (sf.required ? ' <span style="color:#f97316;">*</span>' : '') + '</div>';
        html += '<div style="text-align:center;color:#cbd5e1;"><i class="fa fa-arrow-right"></i></div>';
        html += '<div>';

        // Custom dropdown trigger
        html += '<div class="fmap-dd" data-sysfield="' + escapeHtml(sf.key) + '" data-value="' + escapeHtml(current) + '" tabindex="0" onclick="toggleMapDropdown(this,event)" onkeydown="mapDdKey(this,event)" style="position:relative;">';
        html +=   '<div class="fmap-dd-trigger" style="display:flex;align-items:center;justify-content:space-between;height:38px;padding:0 12px;border-radius:9px;border:1.5px solid #e2e8f0;background:#fff;font-size:13px;color:' + (current ? '#0f172a' : '#94a3b8') + ';font-weight:' + (current ? '600' : '500') + ';cursor:pointer;transition:border-color 0.15s,box-shadow 0.15s;user-select:none;">';
        html +=     '<span class="fmap-dd-label" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escapeHtml(currentLabel) + '</span>';
        html +=     '<i class="fa fa-chevron-down fmap-dd-caret" style="font-size:10px;color:#94a3b8;margin-left:8px;transition:transform 0.15s;"></i>';
        html +=   '</div>';
        // Menu
        html +=   '<div class="fmap-dd-menu" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;z-index:100;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;box-shadow:0 8px 24px rgba(15,23,42,0.12);overflow:hidden;max-height:240px;overflow-y:auto;">';
        html +=     '<div class="fmap-dd-opt" data-val="" onclick="selectMapOption(this,event)" style="padding:9px 12px;font-size:13px;color:#94a3b8;font-style:italic;cursor:pointer;transition:background 0.12s,color 0.12s;' + (!current ? 'background:#fff7ed;color:#ea580c;font-style:normal;font-weight:600;' : '') + '">— Skip —</div>';
        fileCols.forEach(function(fc) {
            var isSel = (fc.key === current);
            var selStyle = isSel ? 'background:#fff7ed;color:#ea580c;font-weight:600;' : '';
            html += '<div class="fmap-dd-opt" data-val="' + escapeHtml(fc.key) + '" onclick="selectMapOption(this,event)" style="padding:9px 12px;font-size:13px;color:#0f172a;cursor:pointer;transition:background 0.12s,color 0.12s;display:flex;align-items:center;justify-content:space-between;gap:8px;' + selStyle + '">';
            html +=   '<span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escapeHtml(fc.label) + '</span>';
            if (fc.sample) {
                html += '<span class="fmap-dd-sample" style="font-size:11px;color:#94a3b8;font-weight:400;flex-shrink:0;max-width:45%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escapeHtml(fc.sample) + '</span>';
            }
            html += '</div>';
        });
        html +=   '</div>';
        html += '</div>';

        if (sample) {
            html += '<div data-sample-for="' + escapeHtml(sf.key) + '" style="font-size:11px;color:#94a3b8;margin-top:5px;padding-left:2px;">e.g. ' + escapeHtml(sample) + '</div>';
        } else {
            html += '<div data-sample-for="' + escapeHtml(sf.key) + '" style="font-size:11px;color:#94a3b8;margin-top:5px;padding-left:2px;"></div>';
        }
        html += '</div>';
    });
    html += '</div>';

    body.innerHTML = html;
    updateMappingHint();
}

function toggleMapDropdown(el, ev) {
    if (ev) ev.stopPropagation();
    var menu = el.querySelector('.fmap-dd-menu');
    var trigger = el.querySelector('.fmap-dd-trigger');
    var caret = el.querySelector('.fmap-dd-caret');
    var isOpen = menu.style.display === 'block';
    closeAllMapDropdowns();
    if (!isOpen) {
        menu.style.display = 'block';
        trigger.style.borderColor = '#f97316';
        trigger.style.boxShadow = '0 0 0 3px rgba(249,115,22,0.12)';
        caret.style.transform = 'rotate(180deg)';
    }
}

function closeAllMapDropdowns() {
    document.querySelectorAll('.fmap-dd').forEach(function(dd) {
        var menu = dd.querySelector('.fmap-dd-menu');
        var trigger = dd.querySelector('.fmap-dd-trigger');
        var caret = dd.querySelector('.fmap-dd-caret');
        if (menu) menu.style.display = 'none';
        if (trigger) { trigger.style.borderColor = '#e2e8f0'; trigger.style.boxShadow = 'none'; }
        if (caret) caret.style.transform = 'rotate(0deg)';
    });
}

function selectMapOption(opt, ev) {
    if (ev) ev.stopPropagation();
    var dd = opt.closest('.fmap-dd');
    if (!dd) return;
    var val = opt.getAttribute('data-val') || '';
    var sysKey = dd.getAttribute('data-sysfield');
    var labelText = opt.querySelector('span') ? opt.querySelector('span').textContent : (val || '— Skip —');

    dd.setAttribute('data-value', val);
    var trigger = dd.querySelector('.fmap-dd-trigger');
    var labelEl = dd.querySelector('.fmap-dd-label');
    labelEl.textContent = val ? labelText : '— Skip —';
    trigger.style.color = val ? '#0f172a' : '#94a3b8';
    trigger.style.fontWeight = val ? '600' : '500';

    // Update internal mapping state
    if (val) _fieldMapping[sysKey] = val; else delete _fieldMapping[sysKey];

    // Refresh sample text under the dropdown
    var sample = '';
    if (val && _mappingMeta) {
        for (var i = 0; i < _mappingMeta.file_columns.length; i++) {
            if (_mappingMeta.file_columns[i].key === val) { sample = _mappingMeta.file_columns[i].sample || ''; break; }
        }
    }
    var sampleEl = document.querySelector('[data-sample-for="' + sysKey + '"]');
    if (sampleEl) sampleEl.textContent = sample ? 'e.g. ' + sample : '';

    // Update selected highlighting inside menu
    dd.querySelectorAll('.fmap-dd-opt').forEach(function(o) {
        var isSel = (o.getAttribute('data-val') || '') === val;
        if (isSel) {
            o.style.background = '#fff7ed';
            o.style.color = '#ea580c';
            o.style.fontWeight = '600';
            if (!o.getAttribute('data-val')) o.style.fontStyle = 'normal';
        } else {
            o.style.background = '';
            o.style.color = o.getAttribute('data-val') ? '#0f172a' : '#94a3b8';
            o.style.fontWeight = o.getAttribute('data-val') ? '500' : '500';
            if (!o.getAttribute('data-val')) o.style.fontStyle = 'italic';
        }
    });

    closeAllMapDropdowns();
    updateMappingHint();
}

function mapDdKey(el, ev) {
    if (ev.key === 'Enter' || ev.key === ' ') {
        ev.preventDefault();
        toggleMapDropdown(el, ev);
    } else if (ev.key === 'Escape') {
        closeAllMapDropdowns();
    }
}

// Close dropdowns on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.fmap-dd')) closeAllMapDropdowns();
});

function updateMappingHint() {
    if (!_mappingMeta) return;
    var hint = document.getElementById('fieldMappingHint');
    var missing = [];
    var seen = {};
    var dup = false;
    (_mappingMeta.system_fields || []).forEach(function(sf) {
        var v = _fieldMapping[sf.key];
        var isRequired = sf.required;
        // Sales-only: the Supplier field (entry_by) is NEVER a hard blocker at the mapping modal —
        // even when the "Require Supplier" toggle is ON. If the toggle is ON and the column is
        // left unmapped, that is surfaced as a per-row "Supplier missing" error on the PREVIEW
        // page instead, so the user isn't stopped here.
        if (_importType === 'sales' && sf.key === 'entry_by') {
            isRequired = false;
        }
        if (isRequired && !v) missing.push(sf.label);
        if (v) {
            if (seen[v]) dup = true;
            seen[v] = true;
        }
    });
    var msgs = [];
    if (missing.length) msgs.push('Missing: ' + missing.join(', '));
    if (dup) msgs.push('Same column mapped twice');
    hint.textContent = msgs.join(' · ');
    hint.style.color = msgs.length ? '#dc2626' : '#94a3b8';
    document.getElementById('fieldMappingContinueBtn').disabled = (missing.length > 0);
    document.getElementById('fieldMappingContinueBtn').style.opacity = missing.length ? '0.55' : '1';
}

function onSalesSupplierToggle(el) {
    _salesSupplierRequired = !!el.checked;
    renderMappingForm();
}

function closeMappingModal() {
    document.getElementById('fieldMappingModal').style.display = 'none';
}

// User dismissed the mapping modal without confirming → treat the import as abandoned:
// reset the file, mapping, session, and bring the upload UI back to its initial empty state.
// Business logic: user said "I don't want to import this file" so we should NOT leave a "file uploaded"
// pill hanging around — the page should look like a fresh import attempt.
function cancelMappingModal() {
    var type = _importType;
    closeMappingModal();
    if (type) {
        // clearFile() already handles: resetting _importFile, _importSessions, hiding the file pill,
        // restoring the required-columns banner + drop zone, and clearing the preview area.
        clearFile(type);
    }
}

function confirmMapping() {
    closeMappingModal();
    var file = _importFile[_importType];
    if (!file) return;
    uploadPreview(_importType, file);
}

// Re-open the column-mapping modal for the file that's already in preview.
// Triggered by the "Re-map columns" ghost button in the Data Preview header.
//
// Business logic: "Re-map" means TWEAK the existing mapping — so the user's CURRENT
// column choices (_fieldMapping) must stay intact, not reset to the auto-suggested ones.
// If headers are already loaded (_mappingMeta present, normal case), reopen the modal
// instantly from that cached metadata with the current mapping pre-selected.
// Only if _mappingMeta is somehow missing do we fall back to a fresh header fetch.
function reopenMappingModal() {
    var type = _importType;
    var file = _importFile[type];
    if (!file) {
        if (typeof showSettingsToast === 'function') showSettingsToast('No file to re-map — please upload one first.', 'error');
        return;
    }
    var modal = document.getElementById('fieldMappingModal');
    // Fast path — headers + mapping already known: reopen with the user's current mapping intact.
    if (modal && _mappingMeta && _fieldMapping && Object.keys(_fieldMapping).length > 0) {
        modal.style.display = 'flex';
        _setPreviewVisible(false);
        document.getElementById('fieldMappingHint').textContent = '';
        try { renderMappingForm(); } catch (e) {
            // If re-render fails for any reason, fall back to a clean reload of the modal.
            openMappingModal(type, file);
        }
        return;
    }
    // Fallback — metadata not available; fetch headers fresh (mapping resets to suggested).
    openMappingModal(type, file);
}

function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function(c) {
        return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c];
    });
}

// Build the styled file-pill meta line per spec — mono number spans, muted `·` separators,
// trailing green check + "uploaded just now". `parts` is an array of {text, mono?} segments.
function _buildFilePillMeta(parts) {
    var html = '';
    parts.forEach(function(seg, i) {
        if (i > 0) html += '<span style="color:#9ca3af;">·</span>';
        if (seg.mono) {
            html += '<span style="font-family:\'JetBrains Mono\',\'SFMono-Regular\',\'Menlo\',\'Consolas\',monospace;">' + seg.text + '</span>';
        } else {
            html += '<span>' + seg.text + '</span>';
        }
    });
    // Trailing "uploaded just now" with a green check icon
    html += '<span style="color:#9ca3af;">·</span>';
    html += '<span style="display:inline-flex;align-items:center;gap:4px;">';
    html +=   '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
    html +=   'uploaded just now';
    html += '</span>';
    return html;
}

function showFileName(type, name) {
    var el = document.getElementById(type + 'FileName');
    document.getElementById(type + 'FileNameText').textContent = name;

    // Build the meta line — initially just size (rows/cols filled in by renderPreview() later).
    var file = _importFile[type];
    var metaEl = document.getElementById(type + 'FileMeta');
    if (metaEl) {
        var parts = [];
        if (file && file.size != null) parts.push({ text: _formatFileSize(file.size), mono: true });
        metaEl.innerHTML = _buildFilePillMeta(parts);
        // Remember the upload time so we can recompute "X minutes ago" if needed
        metaEl.dataset.uploadedAt = Date.now();
    }

    el.style.display = 'flex';
    // Hide ONLY the drag-drop zone for this import type — user already picked a file.
    // The Required-columns banner stays visible (per spec it shows alongside the file pill + preview).
    document.querySelectorAll('[data-upload-ui="' + type + '"]').forEach(function(node) {
        if (node.classList.contains('import-upload-zone')) {
            node.style.display = 'none';
        }
    });
}

// Human-readable file size (KB / MB)
function _formatFileSize(bytes) {
    if (bytes == null || isNaN(bytes)) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// Update the file-pill meta with rows/columns once the preview response arrives.
// Called from renderPreview(). Renders the styled spec layout (mono numbers, separators, green check).
function updateFilePillMeta(type, totalRows, totalCols) {
    var metaEl = document.getElementById(type + 'FileMeta');
    if (!metaEl) return;
    var file = _importFile[type];
    var parts = [];
    if (totalRows != null) parts.push({ text: totalRows.toLocaleString() + ' rows', mono: true });
    if (totalCols != null) parts.push({ text: totalCols + ' columns', mono: true });
    if (file && file.size != null) parts.push({ text: _formatFileSize(file.size), mono: true });
    metaEl.innerHTML = _buildFilePillMeta(parts);
}

function clearFile(type) {
    _importFile[type] = null;
    // Also wipe this type's stored session — user explicitly removed the file
    _importSessions[type] = null;
    // Only reset the live state vars when clearing the currently-active type;
    // the inactive type's state is already preserved in its session bucket.
    if (type === _importType) {
        _previewData = null;
        _fieldMapping = null;
        _mappingMeta = null;
        _selectedRows = {};
        _editingRow = -1;
        _editValues = {};
        _filterMode = 'all';
        _filteredIndices = [];
        _searchTerm = '';
        _editedRowsMap = {};
        _previewPage = 1;
        _salesSupplierRequired = false;
        _salesErrFixerActiveTab = null;
    }
    document.getElementById(type + 'FileInput').value = '';
    document.getElementById(type + 'FileName').style.display = 'none';
    // ── Full preview reset ──
    // Hiding alone isn't enough — the inner DOM (mapping badge, summary cards, toolbar, table)
    // is still left over from the previous preview. Clear all that so a freshly-uploaded file
    // never sees stale UI from the old import, and `display:none` covers any node we missed.
    var previewArea = document.getElementById('importPreviewArea');
    if (previewArea) {
        _setPreviewVisible(false);
        // Clear inner stale DOM (auto-detected mapping badge, summary cards, error dashboard, table, action footer)
        var stale = previewArea.querySelector('.import-mapping-badges');
        if (stale && stale.parentNode) stale.parentNode.removeChild(stale);
        var summaryCards = document.getElementById('importSummaryCards');
        if (summaryCards) summaryCards.innerHTML = '';
        var summaryMobile = document.getElementById('importSummaryMobile');
        if (summaryMobile) summaryMobile.innerHTML = '';
        var errDash = document.getElementById('importErrorDashboard');
        if (errDash) { errDash.innerHTML = ''; errDash.style.display = 'none'; }
        var filterBar = document.getElementById('importFilterBar');
        if (filterBar) { filterBar.innerHTML = ''; filterBar.style.display = 'none'; }
        var bulkBar = document.getElementById('importBulkEditBar');
        if (bulkBar) { bulkBar.innerHTML = ''; bulkBar.style.display = 'none'; }
        var previewTable = document.getElementById('importPreviewTable');
        if (previewTable) previewTable.innerHTML = '';
        var errors = document.getElementById('importErrors');
        if (errors) { errors.innerHTML = ''; errors.style.display = 'none'; }
        var subtitle = document.getElementById('importPreviewSubtitle');
        if (subtitle) subtitle.textContent = '';
    }
    // Also close the centralized Fix-Errors modal if open
    var fxModal = document.getElementById('salesErrorFixerModal');
    if (fxModal && fxModal.style.display !== 'none') {
        fxModal.style.display = 'none';
    }
    // Restore required-columns banner + drag-drop zone so user can pick another file
    document.querySelectorAll('[data-upload-ui="' + type + '"]').forEach(function(node) {
        if (node.classList.contains('import-upload-zone')) {
            node.style.display = '';
        } else {
            node.style.display = 'flex';
        }
    });
}

function clearImport() {
    clearFile(_importType);
}

// ── Import Complete modal — auto-dismissing success toast (no action buttons) ──
// _showImportCompleteModal — fills in the numbers, shows the modal, and starts a ~5s auto-dismiss timer.
// _dismissImportCompleteModal — closes the modal (called by the × button OR the auto-dismiss timer)
//   and resets the import state so the user can start a fresh import.
var _importCompleteTimer = null;
function _showImportCompleteModal(totals) {
    var modal = document.getElementById('importCompleteModal');
    if (!modal) return;
    var importedEl   = document.getElementById('importCompleteImported');
    var skippedEl    = document.getElementById('importCompleteSkipped');
    var duplicatesEl = document.getElementById('importCompleteDuplicates');
    var line2El      = document.getElementById('importCompleteSummary2');
    var ledgerEl     = document.getElementById('importCompleteLedgerLabel');
    var imported = (totals && totals.imported) || 0;
    var skipped  = (totals && totals.skipped)  || 0;
    var dups     = (totals && totals.duplicates) || 0;
    if (importedEl)   importedEl.textContent   = imported.toLocaleString();
    if (skippedEl)    skippedEl.textContent    = skipped.toLocaleString();
    if (duplicatesEl) duplicatesEl.textContent = dups.toLocaleString();
    // An import changed the DB — refresh the Delete Data count pills so they stay
    // in sync without a page reload.
    if (imported > 0 && typeof refreshDeleteCounts === 'function') {
        refreshDeleteCounts();
    }
    if (line2El)      line2El.style.display    = (skipped > 0 || dups > 0) ? 'block' : 'none';
    if (ledgerEl)     ledgerEl.textContent     = (_importType === 'sales') ? 'sales ledger' : 'purchase ledger';
    modal.style.display = 'flex';
    // Auto-dismiss after 5s — long enough to read the summary, short enough to not get in the way.
    if (_importCompleteTimer) clearTimeout(_importCompleteTimer);
    _importCompleteTimer = setTimeout(_dismissImportCompleteModal, 5000);
}
function _dismissImportCompleteModal() {
    if (_importCompleteTimer) { clearTimeout(_importCompleteTimer); _importCompleteTimer = null; }
    var modal = document.getElementById('importCompleteModal');
    if (modal) modal.style.display = 'none';
    // Reset import state so a fresh import can be started.
    try { clearImport(); } catch (e) {}
}

// ─── Generic Import Busy lock ─────────────────────────────────────────────
// While the preview is being built OR while an import is running, the user is
// locked on this page: no tab switch, no sidebar/menu navigation, no browser
// tab close, no other button clicks. Once the operation finishes, navigation
// is free again. Shared blocker overlay shows context-aware title/message.
var _importBusy = false;
var _importBusyReason = '';   // 'preview' | 'import'

// Backward-compatible accessor: anything that previously read this flag still works.
Object.defineProperty(window, '_importPreviewLoading', {
    get: function() { return _importBusy; }
});

function _setImportBusy(on, reason) {
    _importBusy = !!on;
    _importBusyReason = on ? (reason || '') : '';
    var blocker = document.getElementById('importPreviewBlocker');
    if (blocker) {
        if (on) {
            var title = (reason === 'import') ? 'Importing… 0%' : 'Preparing your data preview';
            var msg = (reason === 'import')
                ? "Starting import…"
                : "We're reading and validating your file. This may take a moment for large imports.";
            var titleEl = document.getElementById('importPreviewBlockerTitle');
            var msgEl   = document.getElementById('importPreviewBlockerMsg');
            if (titleEl) titleEl.textContent = title;
            if (msgEl)   msgEl.textContent   = msg;
            // Reset the progress ring + center percent text whenever the blocker opens.
            var arc = document.getElementById('importPreviewBlockerArc');
            var pctEl = document.getElementById('importPreviewBlockerPercent');
            var ring = document.getElementById('importPreviewBlockerRing');
            if (reason === 'import') {
                // Reset progress arc to 0% — conic-gradient fills based on the --pct CSS variable.
                if (arc) { arc.style.animation = 'none'; arc.style.setProperty('--pct', '0'); }
                if (ring) ring.style.animation = 'none';
                if (pctEl) { pctEl.textContent = '0%'; pctEl.style.display = 'flex'; }
            } else {
                // Preview parse — indeterminate spinner.
                // Show a small fixed arc (~22%) and rotate the whole ring continuously.
                // Force-restart the rotation by clearing the animation first, then setting it on the next frame
                // (otherwise the browser may not restart the keyframes when the same value is re-assigned).
                if (arc) { arc.style.setProperty('--pct', '22'); arc.style.animation = 'none'; }
                if (ring) {
                    ring.style.animation = 'none';
                    // Force reflow, then re-apply animation
                    void ring.offsetWidth;
                    ring.style.animation = 'importBlockerSpin 1.1s linear infinite';
                }
                if (pctEl) pctEl.style.display = 'none';
            }
            // Show Cancel button only during the actual Import flow (not the preview parse)
            var cancelBtn = document.getElementById('importPreviewBlockerCancelBtn');
            if (cancelBtn) {
                if (reason === 'import') {
                    cancelBtn.style.display = 'inline-flex';
                    cancelBtn.disabled = false;
                    cancelBtn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg><span>Cancel Import</span>';
                } else {
                    cancelBtn.style.display = 'none';
                }
            }
        } else {
            // Reset arc styles when closing so next open starts clean.
            var arcOff = document.getElementById('importPreviewBlockerArc');
            var ringOff = document.getElementById('importPreviewBlockerRing');
            if (arcOff) { arcOff.style.animation = ''; arcOff.style.setProperty('--pct', '0'); }
            if (ringOff) ringOff.style.animation = '';
        }
        blocker.style.display = on ? 'flex' : 'none';
    }

    // Visually + functionally disable settings tab buttons while busy
    document.querySelectorAll('.settings-tab').forEach(function(btn) {
        if (on) {
            btn.setAttribute('data-import-locked', '1');
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.5';
        } else {
            btn.removeAttribute('data-import-locked');
            btn.style.pointerEvents = '';
            btn.style.opacity = '';
        }
    });

    if (on) {
        window.addEventListener('beforeunload', _importBeforeUnload);
    } else {
        window.removeEventListener('beforeunload', _importBeforeUnload);
    }
}

// Backward-compatible: previous code called _setPreviewLoading(true/false)
function _setPreviewLoading(on) {
    _setImportBusy(on, 'preview');
}

function _importBeforeUnload(e) {
    if (!_importBusy) return;
    e.preventDefault();
    var msg = _importBusyReason === 'import'
        ? 'Your import is still running. Leaving now may interrupt it.'
        : 'Your import data is still being prepared. Are you sure you want to leave?';
    e.returnValue = msg;
    return msg;
}

// Capture-phase guard: any click is blocked while busy except inside the blocker itself.
document.addEventListener('click', function(e) {
    if (!_importBusy) return;
    // Allow clicks inside the blocker overlay
    if (e.target.closest('#importPreviewBlocker')) return;
    var target = e.target.closest('a, button, .settings-tab, [data-nav-link], [role="button"], [onclick]');
    if (!target) return;
    e.preventDefault();
    e.stopPropagation();
    var msg = _importBusyReason === 'import'
        ? 'Please wait — your import is still running.'
        : 'Please wait — preview is still loading.';
    showSettingsToast(msg, 'error');
}, true);

function uploadPreview(type, file) {
    var formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);
    if (_fieldMapping && Object.keys(_fieldMapping).length > 0) {
        formData.append('mapping', JSON.stringify(_fieldMapping));
    }
    formData.append('_token', '{{ csrf_token() }}');

    // Show loading state + lock navigation
    _setPreviewLoading(true);
    var previewArea = document.getElementById('importPreviewArea');
    _setPreviewVisible(true);
    document.getElementById('importPreviewTable').innerHTML = '<div style="text-align:center;padding:30px;color:#94a3b8;"><i class="fa fa-spinner fa-spin" style="font-size:24px;"></i><div style="margin-top:8px;font-size:13px;">Loading file...</div></div>';
    document.getElementById('importSummaryCards').innerHTML = '';
    var _ismEl = document.getElementById('importSummaryMobile');
    if (_ismEl) _ismEl.innerHTML = '';
    document.getElementById('importErrors').style.display = 'none';

    $.ajax({
        url: "{{ route('management.settings.import.preview') }}",
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if (!res.success) {
                document.getElementById('importPreviewTable').innerHTML = '<div style="text-align:center;padding:30px;color:#dc2626;"><i class="fa fa-exclamation-circle" style="font-size:24px;"></i><div style="margin-top:8px;font-size:13px;">' + res.message + '</div></div>';
                _setPreviewLoading(false);
                return;
            }
            renderPreview(res);
            _setPreviewLoading(false);
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to parse file.';
            document.getElementById('importPreviewTable').innerHTML = '<div style="text-align:center;padding:30px;color:#dc2626;"><i class="fa fa-exclamation-circle" style="font-size:24px;"></i><div style="margin-top:8px;font-size:13px;">' + msg + '</div></div>';
            _setPreviewLoading(false);
        }
    });
}

var _previewData = null;
var _previewPage = 1;
var _previewPerPage = 50;
var _selectedRows = {};  // { rowIndex: true/false }
var _editingRow = -1;    // currently editing row index
var _editValues = {};    // temp edited values
var _filterMode = 'all';       // 'all', 'invalid', or error_type string
var _sortInvalidFirst = true;  // default: sort invalid to top
var _filteredIndices = [];     // computed filtered+sorted indices
var _editedRowsMap = {};       // track all edits for sending to server: { rowIdx: { colIdx: value } }
var _searchTerm = '';          // preview-table free-text search (matches any cell, case-insensitive)

var _errorTypeLabels = {
    'missing_product': 'Product missing',
    'missing_quantity': 'Quantity missing',
    'invalid_quantity': 'Invalid quantity',
    'missing_price': 'Price missing',
    'invalid_price': 'Invalid price',
    'invalid_total': 'Invalid total',
    'invalid_sell_price': 'Invalid sell price',
    'invalid_paid': 'Invalid paid amount',
    'invalid_return_qty': 'Invalid return quantity',
    'invalid_dump_qty': 'Invalid dump quantity',
    'missing_customer': 'Customer missing',
    'missing_supplier': 'Supplier missing',
    'missing_date': 'Date missing',
    'invalid_date': 'Invalid date',
    'missing_invoice': 'Invoice missing',
    'summary_row': 'Summary/Total row',
    'duplicate': 'Duplicate'
};

function computeFilteredIndices() {
    var res = _previewData;
    if (!res) { _filteredIndices = []; return; }
    var indices = [];
    res.rows.forEach(function(row, i) {
        if (_filterMode === 'all') {
            indices.push(i);
        } else if (_filterMode === 'valid') {
            if (row.status === 'valid') indices.push(i);
        } else if (_filterMode === 'invalid') {
            if (row.status !== 'valid') indices.push(i);
        } else if (_filterMode === 'duplicates') {
            // Duplicate rows — the preview duplicate pass tags these (match an existing DB invoice
            // OR an earlier identical row in the same file). They are always skipped on import.
            if (row.error_types && row.error_types.indexOf('duplicate') !== -1) indices.push(i);
        } else {
            // Filter by specific error type
            if (row.error_types && row.error_types.indexOf(_filterMode) !== -1) indices.push(i);
        }
    });
    // Free-text search across raw row values (any cell, case-insensitive)
    var q = (_searchTerm || '').trim().toLowerCase();
    if (q !== '') {
        indices = indices.filter(function(i) {
            var raw = res.rows[i] && res.rows[i].raw;
            if (!raw) return false;
            for (var ci = 0; ci < raw.length; ci++) {
                var v = raw[ci];
                if (v == null) continue;
                if (String(v).toLowerCase().indexOf(q) !== -1) return true;
            }
            return false;
        });
    }
    // ── Ordered preview: ERRORS first → DUPLICATE PAIRS next → remaining VALID (Ready) last ──
    // Each duplicate row is followed IMMEDIATELY by the original "Ready" row it duplicates
    // (when that original is a file row — dup_of >= 0). A DB-existing duplicate (dup_of === -1)
    // has no file pair, so it shows alone. Applied only on the "All Rows" view.
    if (_sortInvalidFirst && _filterMode === 'all') {
        var _isDupRow = function(r) {
            return r && r.error_types && r.error_types.indexOf('duplicate') !== -1;
        };
        var errorIdx = [];      // invalid rows that are NOT duplicates (genuine errors)
        var duplicateIdx = [];  // rows flagged as duplicate
        var validIdx = [];      // clean, ready-to-import rows
        indices.forEach(function(i) {
            var r = res.rows[i];
            if (_isDupRow(r)) {
                duplicateIdx.push(i);
            } else if (r.status !== 'valid') {
                errorIdx.push(i);
            } else {
                validIdx.push(i);
            }
        });

        // Build the duplicate section — each duplicate followed by its original Ready row.
        // Track which valid rows got pulled up as a pair so they aren't repeated in the tail.
        var pairedValid = {};   // valid row index => true (already placed beside its duplicate)
        var dupSection = [];
        duplicateIdx.forEach(function(di) {
            dupSection.push(di);
            var origin = res.rows[di] ? res.rows[di].dup_of : undefined;
            // dup_of >= 0 → a file row; pull it right under the duplicate if it's a visible valid row.
            if (typeof origin === 'number' && origin >= 0 && res.rows[origin]
                && validIdx.indexOf(origin) !== -1 && !pairedValid[origin]) {
                dupSection.push(origin);
                pairedValid[origin] = true;
            }
        });
        // Remaining valid rows (those not already paired beside a duplicate).
        var tailValid = validIdx.filter(function(i){ return !pairedValid[i]; });

        indices = errorIdx.concat(dupSection, tailValid);
    }
    _filteredIndices = indices;
}

// Live search handler — debounced re-render so typing stays snappy on large previews.
// Only the table body & pagination re-render; the toolbar (and the input itself) is left alone
// so the user's focus and caret position are preserved while typing.
var _searchDebounceT = null;
function onImportSearchInput(val) {
    _searchTerm = val || '';
    if (_searchDebounceT) clearTimeout(_searchDebounceT);
    _searchDebounceT = setTimeout(function() {
        _previewPage = 1;
        computeFilteredIndices();
        renderPreviewPage();
    }, 120);
}

function clearImportSearch() {
    _searchTerm = '';
    var el = document.getElementById('importSearchInput');
    if (el) el.value = '';
    var clr = document.getElementById('importSearchClear');
    if (clr) clr.style.display = 'none';
    _previewPage = 1;
    computeFilteredIndices();
    renderPreviewPage();
}

// Empty-state "Clear filters" button — resets search + filter mode back to defaults in one tap.
function _importEmptyClearAll() {
    _searchTerm = '';
    _filterMode = 'all';
    var el = document.getElementById('importSearchInput');
    if (el) el.value = '';
    var clr = document.getElementById('importSearchClear');
    if (clr) clr.style.display = 'none';
    _previewPage = 1;
    // Re-render the full preview so the filter dropdown trigger updates back to "All Rows".
    renderPreview(_previewData, true);
}

function getErrorIcon(et) {
    var map = {
        'missing_product': 'cube',
        'missing_quantity': 'hashtag',
        'invalid_quantity': 'hashtag',
        'missing_price': 'gbp',
        'invalid_price': 'gbp',
        'invalid_total': 'calculator',
        'missing_customer': 'user',
        'missing_supplier': 'building',
        'missing_date': 'calendar',
        'invalid_date': 'calendar',
        'missing_invoice': 'file-text-o',
        'duplicate': 'copy'
    };
    return map[et] || 'exclamation-circle';
}

function renderPreview(res, isRefresh) {
    _previewData = res;
    if (!isRefresh) {
        _previewPage = 1;
        _editingRow = -1;
        _editValues = {};
        _filterMode = 'all';
        _searchTerm = '';
        _editedRowsMap = {};
        // All rows unchecked by default
        _selectedRows = {};
        res.rows.forEach(function(row, i) {
            _selectedRows[i] = false;
        });
    }

    // Fill the file-pill meta line with rows + columns now that we know them
    var totalCols = (res.headers && res.headers.length) ? (res.headers.length - 1) : null; // last header is "Status"
    if (typeof updateFilePillMeta === 'function') {
        updateFilePillMeta(_importType, res.total, totalCols);
    }

    // Sales-only: when the "Require Supplier" toggle is ON, flag rows missing a Supplier value.
    // The backend leaves supplier optional for Sales, so we annotate the rows on the client.
    // Two cases:
    //   (a) Supplier column IS mapped → flag only the rows whose Supplier cell is empty.
    //   (b) Supplier column is NOT mapped at all → EVERY row is missing a supplier → flag all.
    // This is why the mapping modal no longer blocks on a missing Supplier when the toggle is ON:
    // the requirement is enforced here, as a per-row "Supplier missing" error on the preview.
    if (_importType === 'sales' && _salesSupplierRequired && res && res.headers && res.rows) {
        var supplierColIdx = -1;
        for (var hi = 0; hi < res.headers.length; hi++) {
            var hh = (res.headers[hi] || '').toLowerCase().trim();
            if (hh === 'supplier' || hh === 'supplier name') { supplierColIdx = hi; break; }
        }
        var supplierUnmapped = (supplierColIdx === -1); // column not present in the file at all
        var flaggedCount = 0;
        var flippedToInvalid = 0;
        res.rows.forEach(function(row) {
            if (!row || !row.raw) return;
            var isMissing;
            if (supplierUnmapped) {
                isMissing = true; // no Supplier column → every row lacks a supplier
            } else {
                var val = (row.raw[supplierColIdx] || '').toString().trim();
                if (val === '-') val = '';
                isMissing = (val === '');
            }
            if (!isMissing) return;
            var et = row.error_types || [];
            if (et.indexOf('missing_supplier') === -1) {
                et.push('missing_supplier');
                row.error_types = et;
                flaggedCount++;
                if (row.status === 'valid') {
                    flippedToInvalid++;
                    row.status = 'invalid';
                }
            }
        });
        if (flaggedCount > 0) {
            res.error_summary = res.error_summary || {};
            res.error_summary['missing_supplier'] = (res.error_summary['missing_supplier'] || 0) + flaggedCount;
            res.invalid = (res.invalid || 0) + flippedToInvalid;
            res.valid = Math.max(0, (res.valid || 0) - flippedToInvalid);
        }
    }

    // Summary Cards — Total Rows (neutral) · Ready to Import (green w/ progress bar) · Will be Skipped (red w/ progress bar)
    // Card spec from reference: white card, soft border, 36px rounded-square icon chip (NOT circle),
    // large bold value (color-coded), small uppercase label, thin progress bar at bottom for Ready/Skipped.
    var cardsHtml = '';
    var hasErr = res.invalid > 0;
    var validPct   = res.total > 0 ? Math.round((res.valid   / res.total) * 100) : 0;
    var invalidPct = res.total > 0 ? Math.round((res.invalid / res.total) * 100) : 0;

    // ── Stat cards — exact UI from user-provided spec (3 cards, inline styles verbatim) ──
    // Card 1: Total Rows (neutral grey tile with bars icon)
    // Card 2: Ready to Import (green tile, check icon, progress bar)
    // Card 3: Will Be Skipped (red tile, info-circle icon, progress bar)
    // All numbers / labels come from the preview response (res.total / res.valid / res.invalid).
    var _isMobilePreview = (window.matchMedia && window.matchMedia('(max-width: 767px)').matches);
    var _ffMono = "'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace";
    var _mute = '#6b7280';
    var _ink = '#0f1115';
    var _border = '#e7e7eb';
    var _greenLine = '#bbf2cc';
    var _greenInk = '#0f7a38';
    var _redLine = '#fbc7c7';
    var _redInk = '#b11212';

    // ── Card 1: Total Rows (neutral) ──
    cardsHtml += '<div style="padding:16px 18px;border-radius:14px;background:#fafafb;border:1px solid ' + _border + ';display:flex;align-items:center;gap:14px;">';
    cardsHtml +=   '<span style="width:44px;height:44px;border-radius:11px;background:#ffffff;color:' + _mute + ';border:1px solid ' + _border + ';display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    cardsHtml +=     '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9h16M4 15h16M10 3L8 21M16 3l-2 18"/></svg>';
    cardsHtml +=   '</span>';
    cardsHtml +=   '<div style="flex:1 1 0%;min-width:0;">';
    cardsHtml +=     '<div style="font-family:' + _ffMono + ';font-size:28px;font-weight:800;color:' + _ink + ';letter-spacing:-0.8px;line-height:1.05;">' + (res.total || 0).toLocaleString() + '</div>';
    cardsHtml +=     '<div style="font-size:10.5px;font-weight:800;letter-spacing:0.7px;margin-top:3px;color:' + _mute + ';text-transform:uppercase;">Total Rows</div>';
    cardsHtml +=   '</div>';
    cardsHtml += '</div>';

    // ── Card 2: Ready to Import (green) — with progress bar ──
    var validPctClamped = Math.max(0, Math.min(100, validPct));
    cardsHtml += '<div style="padding:16px 18px;border-radius:14px;background:#f4fcf7;border:1px solid ' + _greenLine + ';display:flex;align-items:center;gap:14px;">';
    cardsHtml +=   '<span style="width:44px;height:44px;border-radius:11px;background:#ffffff;color:' + _greenInk + ';border:1px solid ' + _greenLine + ';display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    cardsHtml +=     '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
    cardsHtml +=   '</span>';
    cardsHtml +=   '<div style="flex:1 1 0%;min-width:0;">';
    cardsHtml +=     '<div style="font-family:' + _ffMono + ';font-size:28px;font-weight:800;color:' + _greenInk + ';letter-spacing:-0.8px;line-height:1.05;">' + (res.valid || 0).toLocaleString() + '</div>';
    cardsHtml +=     '<div style="font-size:10.5px;font-weight:800;letter-spacing:0.7px;margin-top:3px;color:' + _mute + ';text-transform:uppercase;">Ready to Import</div>';
    cardsHtml +=     '<div style="height:3px;background:rgba(0,0,0,0.06);border-radius:99px;margin-top:7px;overflow:hidden;">';
    cardsHtml +=       '<div style="height:100%;width:' + validPctClamped + '%;background:' + _greenInk + ';border-radius:99px;transition:width 0.3s;"></div>';
    cardsHtml +=     '</div>';
    cardsHtml +=   '</div>';
    cardsHtml += '</div>';

    // ── Card 3: Will Be Skipped (red) — with progress bar ──
    var invalidPctClamped = Math.max(0, Math.min(100, invalidPct));
    cardsHtml += '<div style="padding:16px 18px;border-radius:14px;background:#fef4f4;border:1px solid ' + _redLine + ';display:flex;align-items:center;gap:14px;">';
    cardsHtml +=   '<span style="width:44px;height:44px;border-radius:11px;background:#ffffff;color:' + _redInk + ';border:1px solid ' + _redLine + ';display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    cardsHtml +=     '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>';
    cardsHtml +=   '</span>';
    cardsHtml +=   '<div style="flex:1 1 0%;min-width:0;">';
    cardsHtml +=     '<div style="font-family:' + _ffMono + ';font-size:28px;font-weight:800;color:' + _redInk + ';letter-spacing:-0.8px;line-height:1.05;">' + (res.invalid || 0).toLocaleString() + '</div>';
    cardsHtml +=     '<div style="font-size:10.5px;font-weight:800;letter-spacing:0.7px;margin-top:3px;color:' + _mute + ';text-transform:uppercase;">Will Be Skipped</div>';
    cardsHtml +=     '<div style="height:3px;background:rgba(0,0,0,0.06);border-radius:99px;margin-top:7px;overflow:hidden;">';
    cardsHtml +=       '<div style="height:100%;width:' + invalidPctClamped + '%;background:' + _redInk + ';border-radius:99px;transition:width 0.3s;"></div>';
    cardsHtml +=     '</div>';
    cardsHtml +=   '</div>';
    cardsHtml += '</div>';

    // ── Card 4: Duplicates (blue) — rows already in the database; always skipped on import ──
    var _dupCount = res.duplicates || 0;
    var _blueLine = '#bcd6f7';
    var _blueInk  = '#1e6bce';
    var dupPct = res.total > 0 ? Math.round((_dupCount / res.total) * 100) : 0;
    var dupPctClamped = Math.max(0, Math.min(100, dupPct));
    cardsHtml += '<div style="padding:16px 18px;border-radius:14px;background:#f3f8fe;border:1px solid ' + _blueLine + ';display:flex;align-items:center;gap:14px;">';
    cardsHtml +=   '<span style="width:44px;height:44px;border-radius:11px;background:#ffffff;color:' + _blueInk + ';border:1px solid ' + _blueLine + ';display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    cardsHtml +=     '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>';
    cardsHtml +=   '</span>';
    cardsHtml +=   '<div style="flex:1 1 0%;min-width:0;">';
    cardsHtml +=     '<div style="font-family:' + _ffMono + ';font-size:28px;font-weight:800;color:' + _blueInk + ';letter-spacing:-0.8px;line-height:1.05;">' + _dupCount.toLocaleString() + '</div>';
    cardsHtml +=     '<div style="font-size:10.5px;font-weight:800;letter-spacing:0.7px;margin-top:3px;color:' + _mute + ';text-transform:uppercase;">Duplicates</div>';
    cardsHtml +=     '<div style="height:3px;background:rgba(0,0,0,0.06);border-radius:99px;margin-top:7px;overflow:hidden;">';
    cardsHtml +=       '<div style="height:100%;width:' + dupPctClamped + '%;background:' + _blueInk + ';border-radius:99px;transition:width 0.3s;"></div>';
    cardsHtml +=     '</div>';
    cardsHtml +=   '</div>';
    cardsHtml += '</div>';

    document.getElementById('importSummaryCards').innerHTML = cardsHtml;

    // ── Mobile (≤767px): Stock-Check–style collapsible "STOCK SUMMARY" card ──
    renderImportSummaryMobile(res, validPct);

    // ── Data Preview header — green "Auto-mapped N columns" pill (single badge, exact spec UI) ──
    var _mappingBadge = document.getElementById('importMappingBadge');
    var _mappingBadgeText = document.getElementById('importMappingBadgeText');
    if (_mappingBadge && _mappingBadgeText) {
        var _mappedN = 0;
        if (_mappingMeta && _mappingMeta.mappedCount != null) {
            _mappedN = _mappingMeta.mappedCount;
        } else if (_fieldMapping) {
            for (var _mk in _fieldMapping) {
                if (_fieldMapping[_mk]) _mappedN++;
            }
        }
        if (_mappedN > 0) {
            _mappingBadgeText.textContent = 'Auto-mapped ' + _mappedN + ' column' + (_mappedN !== 1 ? 's' : '');
            _mappingBadge.style.display = 'inline-flex';
        } else {
            _mappingBadge.style.display = 'none';
        }
    }

    // Filter & Actions Bar
    var dashEl = document.getElementById('importErrorDashboard');
    var selCount = 0;
    for (var sk in _selectedRows) { if (_selectedRows[sk]) selCount++; }
    // Select all reflects all rows checked (visual). Backend skips invalid + duplicate at import time.
    var allChecked = (res.rows.length > 0 && selCount >= res.rows.length);
    var hasErrors = res.error_summary && Object.keys(res.error_summary).length > 0 && res.invalid > 0;

    // ── Toolbar — white background, pill-shaped controls ──
    var dashHtml = '<div style="padding:16px 22px;border-bottom:1px solid #f1f5f9;background:#ffffff;">';
    dashHtml += '<div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">';

    var _dupCountForFilter = (typeof res.duplicates === 'number') ? res.duplicates : 0;
    // Each option carries its own accent color (themed chips). Duplicates option shown on all viewports
    // so users can review the rows that already exist in the database and will be skipped.
    var filterOptions = [
        {value:'all',        label:'All Rows',   count: res.total,        accent: '#64748b', chipBg:'#f1f5f9', chipFg:'#64748b'},
        {value:'valid',      label:'Valid',      count: res.valid,        accent: '#16a34a', chipBg:'#dcfce7', chipFg:'#16a34a'},
        {value:'invalid',    label:'Invalid',    count: res.invalid,      accent: '#dc2626', chipBg:'#fee2e2', chipFg:'#dc2626'},
        {value:'duplicates', label:'Duplicates', count: _dupCountForFilter, accent: '#1e6bce', chipBg:'#e0e7ff', chipFg:'#1e6bce'}
    ];
    var activeOpt = filterOptions.find(function(o){ return o.value === _filterMode; }) || filterOptions[0];

    // ── Search input — pill-shaped, light grey fill, no visible border (matches reference) ──
    var searchVal = (_searchTerm || '').replace(/"/g, '&quot;');
    var hasSearch = searchVal !== '';
    dashHtml += '<div id="importSearchWrap" class="imptb-search">';
    dashHtml += '<svg class="imptb-search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
    dashHtml += '<input type="text" id="importSearchInput" value="' + searchVal + '" oninput="onImportSearchInput(this.value);document.getElementById(\'importSearchClear\').style.display=(this.value?\'flex\':\'none\')" placeholder="' + (_isMobilePreview ? 'Search rows by invoice, name, product…' : 'Search rows…') + '" autocomplete="off" spellcheck="false">';
    dashHtml += '<button type="button" id="importSearchClear" onclick="clearImportSearch()" title="Clear search" style="display:' + (hasSearch ? 'flex' : 'none') + ';">';
    dashHtml += '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
    dashHtml += '</button>';
    dashHtml += '</div>';

    // ── Filter dropdown — pixel-exact reference: funnel icon + "All Rows" + grey pill count + chevron ──
    // Filter trigger — exact UI from user-provided spec.
    // Structure: [funnel icon] [label]  [count chip]  [chevron]
    // Inline styles match the spec verbatim. Label + count populated dynamically from preview data.
    var _filterActive = (_filterMode && _filterMode !== 'all');
    dashHtml += '<div class="imptb-filter-wrap" id="importFilterDDWrap" data-active="' + (_filterActive ? '1' : '0') + '">';
    dashHtml += '<button type="button" onclick="toggleImportFilterDD(event)" id="importFilterDDBtn" class="imptb-filter-btn' + (_filterActive ? ' is-active' : '') + '" style="height:38px;padding:0 10px;border-radius:11px;background:#ffffff;border:1px solid #e5e7eb;display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:700;font-size:12.5px;color:#0f1115;">';
    // Funnel icon — slate stroke, fixed slate color (no orange leak)
    dashHtml += '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>';
    // Label — dynamic (All Rows / Valid / Invalid / Duplicates)
    dashHtml += '<span class="imptb-filter-label">' + activeOpt.label + '</span>';
    // Count chip — neutral grey pill (always slate text/grey bg, NOT themed)
    dashHtml += '<span class="imptb-filter-count" style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:999px;background:#f4f4f6;color:#374151;border:1px solid #e7e7eb;font-size:11px;font-weight:700;letter-spacing:0.1px;line-height:1.4;">' + activeOpt.count.toLocaleString() + '</span>';
    // Chevron-down — slate stroke, 13×13
    dashHtml += '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>';
    dashHtml += '</button>';
    dashHtml += '<div id="importFilterDDMenu" class="imptb-filter-menu" style="display:none;">';
    filterOptions.forEach(function(opt) {
        var isSel = (_filterMode === opt.value);
        // Selected row gets a soft peach highlight + orange label; others stay neutral. Count chip always themed by option.
        var rowBg = isSel ? '#fff7ed' : '#ffffff';
        var rowFg = isSel ? '#ea580c' : '#0f172a';
        var hoverIn = isSel ? '' : 'this.style.background=\'#f9fafb\';';
        var hoverOut = isSel ? '' : 'this.style.background=\'#ffffff\';';
        dashHtml += '<div onclick="selectImportFilter(\'' + opt.value + '\')" class="import-dd-option' + (isSel ? ' is-selected' : '') + '" onmouseover="' + hoverIn + '" onmouseout="' + hoverOut + '" style="background:' + rowBg + ';color:' + rowFg + ';">';
        dashHtml += '<span class="import-dd-opt-label">' + opt.label + '</span>';
        // Count chip — hidden on mobile (clean look), shown on desktop with themed colors.
        if (!_isMobilePreview) {
            dashHtml += '<span class="import-dd-opt-count" style="background:' + opt.chipBg + ';color:' + opt.chipFg + ';">' + opt.count.toLocaleString() + '</span>';
        }
        dashHtml += '</div>';
    });
    dashHtml += '</div></div>';

    // ── Select all — exact UI from user spec.
    //    Pill-shaped button with custom 18×18 square checkbox + "Select all" label.
    //    Click toggles allChecked state. Custom checkbox visually fills orange when checked
    //    (data-checked attribute drives the CSS so we don't depend on a native <input>).
    dashHtml += '<button type="button" id="importSelectAllBtn" data-checked="' + (allChecked ? '1' : '0') + '" onclick="toggleSelectAllGlobal(!(this.getAttribute(\'data-checked\')===\'1\'))" title="Select or deselect all rows" style="height:38px;padding:0 10px;border-radius:11px;background:' + (allChecked ? '#fff1e6' : '#ffffff') + ';border:1px solid ' + (allChecked ? '#f6c9a8' : '#e5e7eb') + ';display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:700;font-size:12.5px;color:' + (allChecked ? '#F27420' : '#0f1115') + ';transition:0.12s;">';
    dashHtml += '<span class="imp-selall-box" style="width:16px;height:16px;border-radius:5px;background:' + (allChecked ? '#f97316' : '#ffffff') + ';border:1.5px solid ' + (allChecked ? '#f97316' : '#c9cdd3') + ';display:inline-flex;align-items:center;justify-content:center;cursor:inherit;padding:0;opacity:1;transition:0.12s;flex-shrink:0;">';
    if (allChecked) {
        dashHtml += '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>';
    }
    dashHtml += '</span>';
    dashHtml += '<span>Select all</span>';
    dashHtml += '</button>';

    // ── Right cluster ──
    dashHtml += '<div class="imptb-right-cluster">';

    // ── Fix Errors button — EXACT UI from user-provided spec (inline styles verbatim) ─────────
    // Same button for mobile & desktop: peach pill (orange-soft bg), orange-deep text, orange-line
    // border, broken-link icon, solid orange count chip with white text. Data (count, disabled
    // state, onclick) still comes from existing logic so functionality is unchanged.
    var _fixDisabled = !hasErrors;
    var _fxBg = _fixDisabled ? '#f8fafc' : 'rgb(254, 246, 231)';   // reference peach
    var _fxBd = _fixDisabled ? '#e5e7eb' : 'rgb(246, 206, 139)';   // reference border
    var _fxFg = _fixDisabled ? '#94a3b8' : 'rgb(154, 74, 7)';      // reference amber-brown text
    var _fxChip = _fixDisabled ? '#cbd5e1' : 'rgba(255, 255, 255, 0.55)'; // reference translucent badge
    var _fxCursor = _fixDisabled ? 'not-allowed' : 'pointer';
    var _fxStyle = 'height:42px;padding:0 14px;border-radius:12px;background:' + _fxBg + ';color:' + _fxFg + ';border:1px solid ' + _fxBd + ';font-weight:700;font-size:14px;letter-spacing:-0.1px;display:inline-flex;align-items:center;justify-content:center;gap:7px;box-shadow:none;width:auto;cursor:' + _fxCursor + ';transition:transform 0.04s, filter 0.15s;flex:1 1 0;';
    dashHtml += '<button type="button" onclick="' + (_fixDisabled ? 'return false;' : 'openErrorFixerModal()') + '"' + (_fixDisabled ? ' disabled' : '') + ' class="imptb-fix-errors-btn' + (_filterActive ? ' is-filter-active' : '') + (_fixDisabled ? ' is-disabled' : '') + '" style="' + _fxStyle + '" title="' + (_fixDisabled ? 'No errors to fix' : 'Review and resolve all import errors') + '">';
    dashHtml += '<svg width="15.5" height="15.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">';
    dashHtml +=   '<path d="M14.7 6.3a4.5 4.5 0 0 0-6 6L3 18l3 3 5.7-5.7a4.5 4.5 0 0 0 6-6L15 12l-3-3 2.7-2.7z"/>';
    dashHtml += '</svg>';
    dashHtml += '<span>Fix Errors</span>';
    dashHtml += '<span class="imptb-fix-errors-count" style="min-width:18px;height:18px;padding:0 5px;border-radius:9px;background:' + _fxChip + ';color:' + _fxFg + ';font-size:11px;font-weight:800;line-height:1;display:inline-flex;align-items:center;justify-content:center;">' + (res.invalid || 0).toLocaleString() + '</span>';
    dashHtml += '</button>';

    // ── Primary CTA: Import button — solid orange pill with check icon ──
    var importDisabled = (selCount === 0);
    var importLabel = importDisabled ? 'Import Selected' : 'Import ' + selCount.toLocaleString() + ' Row' + (selCount !== 1 ? 's' : '');
    dashHtml += '<button type="button" id="importConfirmBtnTop" onclick="executeImport()"' + (importDisabled ? ' disabled' : '') + ' class="imptb-import-btn' + (importDisabled ? ' is-disabled' : '') + '" title="' + (importDisabled ? 'Select rows to import' : 'Import selected rows') + '">';
    dashHtml += '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>';
    dashHtml += '<span>' + importLabel + '</span>';
    dashHtml += '</button>';

    dashHtml += '</div>';

    dashHtml += '</div>';

    // Row 2: Error breakdown chips — hidden for both Sales & Purchase (replaced by Fix Errors modal).
    // Kept here behind an `if (false)` for now in case any other import type is added later.
    if (false && hasErrors) {
        dashHtml += '<div style="margin-top:12px;padding-top:12px;border-top:1px solid #eef2f7;">';
        dashHtml += '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">';
        dashHtml += '<span style="font-size:11px;font-weight:700;color:#64748b;white-space:nowrap;"><i class="fa fa-bug" style="font-size:10px;color:#f97316;margin-right:3px;"></i>Issues found:</span>';
        for (var et in res.error_summary) {
            var label = _errorTypeLabels[et] || et;
            var count = res.error_summary[et];
            var isActive = (_filterMode === et);
            var canAutoFix = (et === 'invalid_price' || et === 'invalid_quantity');
            dashHtml += '<button type="button" class="import-error-chip' + (isActive ? ' active' : '') + '" onclick="setFilterMode(\'' + et + '\')" title="Click to filter: ' + label + '" style="' + (isActive ? '' : '') + '">';
            dashHtml += '<i class="fa fa-' + getErrorIcon(et) + '" style="font-size:10px;"></i> ' + label;
            dashHtml += ' <span class="chip-count">' + count + '</span>';
            dashHtml += '</button>';
            if (canAutoFix) {
                dashHtml += '<button type="button" onclick="applyAutoFix(\'' + et + '\')" style="height:28px;padding:0 12px;border-radius:8px;border:1.5px solid #bbf7d0;background:#f0fdf4;color:#16a34a;font-size:11px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:4px;outline:none;transition:all 0.15s;" title="Auto-fix: convert negative to positive"><i class="fa fa-magic" style="font-size:10px;"></i> Auto-fix</button>';
            }
        }
        if (_filterMode !== 'all' && _filterMode !== 'valid' && _filterMode !== 'invalid') {
            dashHtml += '<button type="button" onclick="setFilterMode(\'all\')" style="height:28px;padding:0 12px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:11px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:4px;outline:none;transition:all 0.15s;"><i class="fa fa-times" style="font-size:9px;"></i> Clear filter</button>';
        }
        dashHtml += '</div></div>';
    }

    dashHtml += '</div>';
    dashEl.innerHTML = dashHtml;
    dashEl.style.display = 'block';

    // Hide separate filter bar
    document.getElementById('importFilterBar').style.display = 'none';

    // Bulk Edit Bar
    renderBulkEditBar();

    // Compute filtered indices
    computeFilteredIndices();

    // Subtitle
    document.getElementById('importPreviewSubtitle').textContent = 'Only "Ready to Import" rows will be saved to the database.';

    // Render table page
    renderPreviewPage();

    // Errors - hidden (error chips in dashboard replace this)
    document.getElementById('importErrors').style.display = 'none';

    // Import button state
    updateSelectedCount();
}

function renderPreviewPage() {
    var res = _previewData;
    if (!res) return;

    var totalRows = _filteredIndices.length;
    var totalPages = Math.ceil(totalRows / _previewPerPage);
    if (_previewPage > totalPages && totalPages > 0) _previewPage = totalPages;
    if (_previewPage < 1) _previewPage = 1;

    var startIdx = (_previewPage - 1) * _previewPerPage;
    var endIdx = Math.min(startIdx + _previewPerPage, totalRows);
    var pageIndices = _filteredIndices.slice(startIdx, endIdx);

    // Count selected on current page
    var pageAllChecked = pageIndices.length > 0;
    pageIndices.forEach(function(idx) {
        if (!_selectedRows[idx]) pageAllChecked = false;
    });

    // Table — wrap in its own horizontal-scroll container so pagination stays full-width below
    // ── Table header — EXACT UI from user-provided spec. Same columns as before (dynamic from res.headers),
    //    only styling is replaced: bg #fafafb, 10.5px/800 muted uppercase, letter-spacing 0.7px, 1px border-bottom.
    //    Numeric columns (Quantity / Unit Price / Total) right-aligned per spec; others left-aligned.
    var _thBase = 'text-align:left;padding:10px 12px;background:#fafafb;font-size:10.5px;font-weight:800;color:#6b7280;letter-spacing:0.7px;text-transform:uppercase;border-bottom:1px solid #eef0f2;white-space:nowrap;';
    var _thRight = 'text-align:right;padding:10px 12px;background:#fafafb;font-size:10.5px;font-weight:800;color:#6b7280;letter-spacing:0.7px;text-transform:uppercase;border-bottom:1px solid #eef0f2;white-space:nowrap;';
    // Column-specific widths from the user's spec: # → 50px, Date → 110px, Invoice No → 140px,
    // Quantity → 110px, Unit Price → 120px, Total → 120px, Status → 110px. Other columns flex.
    var _thWidthFor = function(hdr){
        var h = (hdr || '').toString().trim().toLowerCase();
        if (h === 'date') return 'width:110px;';
        if (h === 'invoice no' || h === 'invoice number' || h === 'invoice no.') return 'width:140px;';
        if (h === 'quantity' || h === 'qty') return 'width:110px;';
        if (h === 'unit price') return 'width:120px;';
        if (h === 'total') return 'width:120px;';
        return '';
    };
    var _thAlignFor = function(hdr){
        var h = (hdr || '').toString().trim().toLowerCase();
        if (h === 'quantity' || h === 'qty' || h === 'unit price' || h === 'total' || h === 'sell price' || h === 'paid' || h === 'return qty' || h === 'dump qty') return _thRight;
        return _thBase;
    };
    var html = '<div class="import-table-scroll" style="overflow-x:auto;width:100%;"><table class="import-preview-table"><thead><tr>';
    // Checkbox column — keep functional, apply spec styling (left-aligned, empty label)
    html += '<th style="' + _thBase + 'width:36px;text-align:center;"><input type="checkbox" class="import-cb" ' + (pageAllChecked ? 'checked' : '') + ' onchange="toggleSelectAll(this.checked)"></th>';
    // Row number column — width 50px per spec
    html += '<th style="' + _thBase + 'width:50px;">#</th>';
    // All headers except last one (Status — server already sent it as the last column header)
    for (var hi = 0; hi < res.headers.length - 1; hi++) {
        var _hdr = res.headers[hi];
        html += '<th style="' + _thAlignFor(_hdr) + _thWidthFor(_hdr) + '">' + _hdr + '</th>';
    }
    // STATUS column — width 110px per spec
    html += '<th class="import-status-th" style="' + _thBase + 'width:110px;">Status</th>';
    // Trailing edit column — width 50px per spec
    html += '<th style="' + _thBase + 'width:50px;"></th>';
    html += '</tr></thead><tbody>';

    // Empty state — search/filter returned no rows. Matches reference design exactly:
    // grey document tile icon, "No records found" title, helper subtitle, "Clear filters" button.
    if (pageIndices.length === 0) {
        var totalCols = (res.headers.length - 1) + 4; // checkbox + # + headers + status + edit
        var hasQuery = (_searchTerm || '').trim() !== '';
        var _filterIsActive = (_filterMode && _filterMode !== 'all');
        var showClearBtn = hasQuery || _filterIsActive;
        var subtitle = hasQuery
            ? 'No rows match your search. Try a different term or clear the filter.'
            : 'Try changing the date range or search term. New invoices appear here as soon as you create them.';

        html += '<tr><td colspan="' + totalCols + '" style="padding:0;background:#ffffff;">';
        html += '<div class="import-empty-state">';
        // Icon tile — light grey square with document SVG
        html +=   '<div class="import-empty-icon">';
        html +=     '<svg viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">';
        html +=       '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>';
        html +=       '<polyline points="14 2 14 8 20 8"/>';
        html +=     '</svg>';
        html +=   '</div>';
        html +=   '<div class="import-empty-title">No records found</div>';
        html +=   '<div class="import-empty-sub">' + subtitle + '</div>';
        if (showClearBtn) {
            html += '<button type="button" class="import-empty-btn" onclick="_importEmptyClearAll()" aria-label="Clear filters">';
            html +=   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>';
            html +=   '<span>Clear filters</span>';
            html += '</button>';
        }
        html += '</div>';
        html += '</td></tr>';
    }

    pageIndices.forEach(function(globalIdx, i) {
        var row = res.rows[globalIdx];
        var rowNum = globalIdx + 1;
        var isSelected = !!_selectedRows[globalIdx];
        var isEditing = (_editingRow === globalIdx);
        var isInvalid = (row.status !== 'valid');
        var isDup = (row.error_types && row.error_types.indexOf('duplicate') !== -1) || row.is_duplicate === true;
        // Invalid rows get a soft peach tint on desktop (CSS picks up the class); duplicates get indigo wash.
        var rowClasses = ['import-preview-row'];
        if (isDup) rowClasses.push('import-row-duplicate');
        else if (isInvalid) rowClasses.push('import-row-invalid');
        var rowStyle = !isSelected ? 'opacity:0.5;' : '';

        // ── Row UI — EXACT spec from user-provided HTML.
        //    All rows (Ready / Duplicate / Error) = white bg. Only the status pill colors differ.
        //    1px border-top across every row. Functionality (classes, onclicks, selection state) preserved.
        var _rowBg = '#ffffff';
        var _rowBorderTop = '1px solid #eef0f2';
        var _rowExtraStyle = (!isSelected && !isInvalid) ? 'opacity:0.5;' : '';
        html += '<tr class="' + rowClasses.join(' ') + '" style="background:' + _rowBg + ';border-top:' + _rowBorderTop + ';' + _rowExtraStyle + '">';
        // ── Checkbox cell — left-padded 24px per spec, vertical 12px for breathing room.
        html += '<td style="padding:12px 14px 12px 24px;font-size:13px;color:#1a1d24;white-space:nowrap;background:transparent;">';
        if (isSelected) {
            html += '<button type="button" onclick="toggleRowSelect(' + globalIdx + ',false)" style="width:18px;height:18px;border-radius:5px;background:#f97316;border:1.5px solid #f97316;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;padding:0;opacity:1;transition:0.12s;flex-shrink:0;">';
            html +=   '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
            html += '</button>';
        } else {
            html += '<button type="button" onclick="toggleRowSelect(' + globalIdx + ',true)" style="width:18px;height:18px;border-radius:5px;background:#ffffff;border:1.5px solid #d4d4d8;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;padding:0;opacity:1;transition:0.12s;flex-shrink:0;"></button>';
        }
        html += '</td>';
        // ── Row number — mono, weight 800, tabular numerals. Red for Error; orange-deep otherwise.
        var _rowNumColor = isInvalid ? '#b11212' : '#F27420';
        html += '<td style="padding:12px 14px;font-size:13px;color:' + _rowNumColor + ';white-space:nowrap;font-family:\'JetBrains Mono\',\'SFMono-Regular\',\'Menlo\',\'Consolas\',monospace;font-weight:800;font-variant-numeric:tabular-nums;background:transparent;">' + rowNum + '</td>';

        // Data cells — skip the trailing "Status" column from server, we render our own pill below.
        if (row.raw) {
            var rowErrors = row.error_types || [];
            var _dataColLimit = res.headers.length - 1; // server's last header is "Status"
            row.raw.forEach(function(val, ci) {
                if (ci >= _dataColLimit) return; // skip trailing status text cell
                var hdr = (res.headers[ci] || '').toLowerCase().trim();
                if (isEditing) {
                    var editVal = _editValues[ci] !== undefined ? _editValues[ci] : (val || '');
                    var placeholder = '';
                    var inputType = 'text';
                    // Smart placeholders & types based on header
                    if (hdr === 'date') { placeholder = 'DD/MM/YYYY'; inputType = 'imp-date'; }
                    else if (hdr === 'quantity' || hdr === 'qty') { placeholder = 'e.g. 10'; inputType = 'number'; }
                    else if (hdr.indexOf('price') !== -1 || hdr === 'rate') { placeholder = 'e.g. 5.50'; inputType = 'number'; }
                    else if (hdr === 'total' || hdr === 'amount' || hdr === 'sub_total' || hdr === 'subtotal') { placeholder = 'e.g. 55.00'; inputType = 'number'; }
                    else if (hdr === 'dump' || hdr.indexOf('dump') !== -1) { placeholder = '0'; inputType = 'number'; }
                    else if (hdr === 'paid' || hdr === 'paid amount' || hdr.indexOf('paid') !== -1) { placeholder = '0.00'; inputType = 'number'; }
                    else if (hdr.indexOf('return') !== -1 && hdr.indexOf('qty') !== -1) { placeholder = '0'; inputType = 'number'; }
                    // Supplier Return / Customer Return → numeric quantity (positive). NOT a name field.
                    else if (hdr.indexOf('supplier return') !== -1 || hdr.indexOf('customer return') !== -1) { placeholder = '0'; inputType = 'number'; }
                    else if (hdr.indexOf('supplier') !== -1) { placeholder = 'Supplier name'; }
                    else if (hdr.indexOf('customer') !== -1) { placeholder = 'Customer name'; }
                    else if (hdr.indexOf('product') !== -1) { placeholder = 'Product name'; }
                    // Check if this column has an error
                    var hasColError = false;
                    if (hdr === 'date' && (rowErrors.indexOf('missing_date') !== -1 || rowErrors.indexOf('invalid_date') !== -1)) hasColError = true;
                    if ((hdr === 'quantity' || hdr === 'qty') && (rowErrors.indexOf('missing_quantity') !== -1 || rowErrors.indexOf('invalid_quantity') !== -1)) hasColError = true;
                    if ((hdr.indexOf('price') !== -1 || hdr === 'rate') && hdr.indexOf('sale') === -1 && hdr.indexOf('total') === -1 && (rowErrors.indexOf('missing_price') !== -1 || rowErrors.indexOf('invalid_price') !== -1)) hasColError = true;
                    if ((hdr === 'total' || hdr === 'amount' || hdr === 'sub_total' || hdr === 'subtotal') && rowErrors.indexOf('invalid_total') !== -1) hasColError = true;
                    if (hdr.indexOf('supplier') !== -1 && rowErrors.indexOf('missing_supplier') !== -1) hasColError = true;
                    if (hdr.indexOf('customer') !== -1 && rowErrors.indexOf('missing_customer') !== -1) hasColError = true;
                    if (hdr.indexOf('product') !== -1 && rowErrors.indexOf('missing_product') !== -1) hasColError = true;
                    // Edit input keeps a neutral orange theme even for invalid cells (no red bg).
                    var borderColor = '#f97316';
                    var bgColor = '#fff7ed';
                    // Numeric cells: strict guard — digits + ONE dot only. No `-`, `+`, `e/E`, letters, spaces.
                    var numAttrs = '';
                    if (inputType === 'number') {
                        // Use type=text + inputmode=decimal so we can fully control input characters
                        // (type=number on mobile shows scientific-notation keys we don't want).
                        inputType = 'text';
                        numAttrs = ' inputmode="decimal" onkeydown="window._impNumGuard.keydown.call(this,event)" oninput="window._impNumGuard.input.call(this,event)" onpaste="window._impNumGuard.paste.call(this,event)"';
                    }
                    // Invoice number must NOT be editable — preserve the imported value as-is
                    var _inlineIsInvoice = (hdr === 'invoice' || hdr === 'invoice no' || hdr === 'invoice number' || hdr === 'inv_no' || hdr === 'invoice_no');
                    if (_inlineIsInvoice) {
                        numAttrs = ' readonly title="Invoice number cannot be edited" style="background:#f1f5f9;color:#64748b;cursor:not-allowed;border-color:#e2e8f0;"';
                        html += '<td><input type="text" class="import-edit-input" value="' + editVal.toString().replace(/"/g, '&quot;') + '" data-ci="' + ci + '"' + numAttrs + '></td>';
                    } else if (inputType === 'imp-date') {
                        // Custom modern date picker (replaces the odd native browser calendar).
                        // Calendar icon on the LEFT; input is readonly + key-blocked so user can only pick via the popup.
                        // White background + orange border (per spec reference) — distinct from the peach numeric inputs.
                        var _dShown = window._impDatePicker ? window._impDatePicker.toDisplay(editVal) : (editVal || '');
                        html += '<td style="position:relative;">';
                        html +=   '<input type="text" class="import-edit-input imp-date-input" value="' + _dShown.replace(/"/g, '&quot;') + '"';
                        html +=     ' placeholder="' + placeholder + '" data-ci="' + ci + '" readonly';
                        html +=     ' onclick="window._impDatePicker.open(this,' + ci + ')"';
                        html +=     ' onkeydown="event.preventDefault(); return false;"';
                        html +=     ' onpaste="event.preventDefault(); return false;"';
                        html +=     ' style="border:1.5px solid #f97316;background:#ffffff;color:#1a1d24;font-weight:600;cursor:pointer;padding-left:34px;padding-right:10px;"></input>';
                        html +=   '<svg class="imp-date-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);pointer-events:none;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
                        html += '</td>';
                    } else {
                        html += '<td><input type="' + inputType + '" class="import-edit-input" value="' + editVal.toString().replace(/"/g, '&quot;') + '" placeholder="' + placeholder + '"' + numAttrs + ' onchange="updateEditValue(' + ci + ',this.value)" data-ci="' + ci + '" style="border-color:' + borderColor + ';background:' + bgColor + ';"></td>';
                    }
                } else {
                    // Highlight specific error cells in red
                    var cellHasError = false;
                    if (isInvalid) {
                        if (hdr === 'date' && (rowErrors.indexOf('missing_date') !== -1 || rowErrors.indexOf('invalid_date') !== -1)) cellHasError = true;
                        if ((hdr === 'quantity' || hdr === 'qty') && (rowErrors.indexOf('missing_quantity') !== -1 || rowErrors.indexOf('invalid_quantity') !== -1)) cellHasError = true;
                        if ((hdr.indexOf('price') !== -1 || hdr === 'rate') && hdr.indexOf('sale') === -1 && hdr.indexOf('total') === -1 && (rowErrors.indexOf('missing_price') !== -1 || rowErrors.indexOf('invalid_price') !== -1)) cellHasError = true;
                        if ((hdr === 'total' || hdr === 'amount' || hdr === 'sub_total' || hdr === 'subtotal') && rowErrors.indexOf('invalid_total') !== -1) cellHasError = true;
                        if (hdr.indexOf('supplier') !== -1 && rowErrors.indexOf('missing_supplier') !== -1) cellHasError = true;
                        if (hdr.indexOf('customer') !== -1 && rowErrors.indexOf('missing_customer') !== -1) cellHasError = true;
                        if (hdr.indexOf('product') !== -1 && rowErrors.indexOf('missing_product') !== -1) cellHasError = true;
                    }
                    // Identify price/amount columns so we can prefix with £ on display.
                    // Qty stays plain (no symbol). Anything containing "price" or "rate" or matching total/amount/sub_total gets £.
                    var _isPriceCell = (
                        (hdr.indexOf('price') !== -1) ||
                        (hdr === 'rate') ||
                        (hdr === 'total') ||
                        (hdr === 'amount') ||
                        (hdr === 'sub_total') ||
                        (hdr === 'subtotal') ||
                        (hdr === 'paid') ||
                        (hdr === 'paid amount') ||
                        (hdr.indexOf('paid') !== -1 && hdr.indexOf('paid') === 0)
                    );
                    // Missing-value cells show italic red "missing" placeholder per spec
                    // (inner <span> styled italic + weight 500 — cell already gets bg #fdecec / color #b11212 / weight 700 below).
                    var displayVal;
                    if (cellHasError && (val === null || val === undefined || String(val).trim() === '')) {
                        displayVal = '<span style="font-style:italic;color:#b11212;font-weight:500;">missing</span>';
                    } else if (val === null || val === undefined || String(val).trim() === '') {
                        displayVal = '<span style="color:#cbd5e1;">—</span>';
                    } else if (_isPriceCell && !isNaN(parseFloat(val))) {
                        // Numeric price cell — render with £ prefix (formatted to 2 decimals when needed).
                        var _n = parseFloat(val);
                        var _formatted = (Math.abs(_n) >= 1 || _n === 0)
                            ? _n.toFixed(2)
                            : String(val); // keep as-is for tiny decimals like "0.05"
                        displayVal = '<span class="imp-price-cell"><span class="imp-pound">£</span>' + _formatted + '</span>';
                    } else {
                        displayVal = val;
                    }
                    // ── Cell styling — EXACT spec from user-provided HTML ──
                    // Base: padding:10px 12px; font-size:13px; color:#1a1d24; white-space:nowrap;
                    // Mono+800 for: row#, total. Mono+600 for: invoice no (12.5px), qty (right), unit price (right).
                    // Weight 600 (no mono) for: supplier/customer name.
                    var _ffMonoStack = "'JetBrains Mono','SFMono-Regular','Menlo','Consolas',monospace";
                    var _isQty       = (hdr === 'quantity' || hdr === 'qty');
                    var _isUnitPrice = ((hdr === 'unit price' || hdr === 'unitprice' || hdr === 'price' || hdr === 'rate') && hdr.indexOf('sale') === -1 && hdr.indexOf('total') === -1);
                    var _isTotal     = (hdr === 'total' || hdr === 'amount' || hdr === 'sub_total' || hdr === 'subtotal');
                    var _isSellPrice = (hdr === 'sell price' || hdr === 'sale price' || hdr === 'selling price' || hdr === 'sell_price' || hdr === 'sale_price');
                    var _isPaid      = (hdr === 'paid' || hdr === 'paid amount' || (hdr.indexOf('paid') !== -1 && hdr.indexOf('paid') === 0));
                    var _isInvoiceNo = (hdr === 'invoice no' || hdr === 'invoice number' || hdr === 'invoice no.' || hdr === 'inv_no' || hdr === 'invoice_no' || hdr === 'invoice');
                    var _isEntityName= (hdr.indexOf('supplier') !== -1 || hdr.indexOf('customer') !== -1);

                    // Padding 12px 14px gives a modern, premium feel (was 10px 12px — too tight).
                    // Background stays transparent so the row's white bg shows through cleanly.
                    var _cellStyle = 'padding:12px 14px;font-size:13px;color:#1a1d24;white-space:nowrap;background:transparent;';
                    if (_isInvoiceNo) {
                        _cellStyle = 'padding:12px 14px;font-size:12.5px;color:#1a1d24;white-space:nowrap;font-family:' + _ffMonoStack + ';letter-spacing:0.2px;background:transparent;';
                    } else if (_isEntityName) {
                        _cellStyle = 'padding:12px 14px;font-size:13px;color:#1a1d24;white-space:nowrap;font-weight:600;background:transparent;';
                    } else if (_isQty) {
                        _cellStyle = 'padding:12px 14px;font-size:13px;color:#1a1d24;white-space:nowrap;text-align:right;font-family:' + _ffMonoStack + ';font-weight:600;font-variant-numeric:tabular-nums;background:transparent;';
                    } else if (_isUnitPrice || _isSellPrice || _isPaid) {
                        _cellStyle = 'padding:12px 14px;font-size:13px;color:#1a1d24;white-space:nowrap;text-align:right;font-family:' + _ffMonoStack + ';font-weight:600;font-variant-numeric:tabular-nums;background:transparent;';
                    } else if (_isTotal) {
                        // Total cell — mono, weight 800, right-aligned. Pure white bg; error rows get rose via .imp-cell-error.
                        _cellStyle = 'padding:12px 14px;font-size:13px;color:#1a1d24;white-space:nowrap;text-align:right;font-family:' + _ffMonoStack + ';font-weight:800;font-variant-numeric:tabular-nums;background:transparent;';
                    }
                    // Per spec: error cells override bg + text + weight (#fdecec / #b11212 / 700).
                    if (cellHasError) {
                        _cellStyle += 'background:#fdecec;color:#b11212;font-weight:700;';
                    }
                    // Preserve original class set so existing CSS hooks (.imp-total-cell, .imp-cell-error) still work
                    // alongside the inline spec styles (inline takes precedence where they overlap).
                    var _tdCls = [];
                    if (_isTotal) _tdCls.push('imp-total-cell');
                    if (cellHasError) _tdCls.push('imp-cell-error');
                    var _tdClsAttr = _tdCls.length ? ' class="' + _tdCls.join(' ') + '"' : '';
                    html += '<td' + _tdClsAttr + ' style="' + _cellStyle + '">' + displayVal + '</td>';
                }
            });
        }

        // ── STATUS pill — EXACT spec from user-provided HTML.
        //    Ready  = green bg/border/ink + 6px dot
        //    Duplicate = blue bg/border/ink + cycle-arrows icon (11×11)
        //    Error  = red bg/border/ink (keeps existing palette pattern)
        var _isDup = (row.error_types && row.error_types.indexOf('duplicate') !== -1) || row.is_duplicate === true;
        var _statusCls, _statusLbl, _pillBg, _pillBorder, _pillInk;
        var _pillIconHtml = '';
        if (_isDup) {
            _statusCls = 'is-duplicate'; _statusLbl = 'Duplicate';
            _pillBg = '#eaf2fd'; _pillBorder = '#bcd6f7'; _pillInk = '#1e6bce';
            _pillIconHtml = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 1l4 4-4 4"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><path d="M7 23l-4-4 4-4"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>';
        } else if (isInvalid) {
            _statusCls = 'is-error'; _statusLbl = 'Error';
            _pillBg = '#feecec'; _pillBorder = '#fbc7c7'; _pillInk = '#b11212';
            // Info-circle SVG icon (per spec) — replaces the round dot used for Ready.
            _pillIconHtml = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>';
        } else {
            _statusCls = 'is-ready'; _statusLbl = 'Ready';
            _pillBg = '#e9f9ef'; _pillBorder = '#bbf2cc'; _pillInk = '#0f7a38';
            _pillIconHtml = '<span style="width:6px;height:6px;border-radius:50%;background:#0f7a38;"></span>';
        }
        // Status cell — uniform td wrapper across Ready / Duplicate / Error. Only the pill colors differ.
        html += '<td class="import-status-td" style="padding:12px 14px;font-size:13px;color:#1a1d24;white-space:nowrap;background:transparent;">';
        html += '<span class="import-status-pill ' + _statusCls + '" style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:999px;background:' + _pillBg + ';color:' + _pillInk + ';border:1px solid ' + _pillBorder + ';font-size:11px;font-weight:700;letter-spacing:0.1px;line-height:1.4;">' + _pillIconHtml + '<span>' + _statusLbl + '</span></span>';
        html += '</td>';

        // ── Action cell (right-padded 24px per spec) + edit button (28×28 outlined pencil) ──
        html += '<td style="padding:12px 24px 12px 14px;font-size:13px;color:#1a1d24;white-space:nowrap;background:transparent;">';
        if (isEditing) {
            html += '<div class="import-edit-actions">';
            html += '<button class="import-edit-save" onclick="saveRowEdit(' + globalIdx + ')" title="Save"><i class="fa fa-check"></i></button>';
            html += '<button class="import-edit-cancel" onclick="cancelRowEdit()" title="Cancel"><i class="fa fa-times"></i></button>';
            html += '</div>';
        } else {
            html += '<button class="import-edit-btn" onclick="startRowEdit(' + globalIdx + ')" title="Edit" aria-label="Edit row" style="width:28px;height:28px;border-radius:8px;background:transparent;border:1px solid #e7e7eb;color:#6b7280;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;padding:0;">';
            html +=   '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>';
            html += '</button>';
        }
        html += '</td>';

        html += '</tr>';
    });
    html += '</tbody></table></div>';

    // Pagination bar — sits outside the horizontal-scroll container so it stays full-width
    html += '<div class="import-pagination">';
    var infoText = 'Showing <strong>' + (totalRows > 0 ? startIdx + 1 : 0) + '</strong> to <strong>' + endIdx + '</strong> of <strong>' + totalRows + '</strong>';
    var isFiltered = (_filterMode !== 'all') || ((_searchTerm || '').trim() !== '');
    if (isFiltered) infoText += ' filtered';
    infoText += ' rows';
    if (isFiltered) infoText += ' <span style="color:#94a3b8;">(' + res.rows.length + ' total)</span>';
    html += '<div class="import-pagination-info">' + infoText + '</div>';
    html += '<div class="import-pagination-controls">';

    // Per page custom dropdown
    html += '<div class="import-per-page-wrap" id="perPageWrap">';
    html += '<button type="button" class="import-per-page-btn" onclick="togglePerPageDropdown(event)">' + _previewPerPage + '</button>';
    html += '<div class="import-per-page-dropdown" id="perPageDropdown">';
    [50, 100, 200, 500].forEach(function(n){
        html += '<button type="button" class="import-per-page-item' + (n === _previewPerPage ? ' active' : '') + '" onclick="selectPerPage(' + n + ',event)">' + n + '</button>';
    });
    html += '</div></div>';

    // Page navigation group (prev + pages + next) — wrapped so it stays as one cluster on mobile
    html += '<div class="import-page-nav">';

    // Prev button
    html += '<button class="import-page-btn nav-btn" onclick="goImportPage(' + (_previewPage - 1) + ')"' + (_previewPage <= 1 ? ' disabled' : '') + '><i class="fa fa-chevron-left" style="font-size:10px;"></i></button>';

    // Page buttons
    var pages = getVisiblePages(_previewPage, totalPages);
    pages.forEach(function(p) {
        if (p === '...') {
            html += '<span class="import-page-ellipsis">...</span>';
        } else {
            html += '<button class="import-page-btn' + (p === _previewPage ? ' active' : '') + '" onclick="goImportPage(' + p + ')">' + p + '</button>';
        }
    });

    // Next button
    html += '<button class="import-page-btn nav-btn" onclick="goImportPage(' + (_previewPage + 1) + ')"' + (_previewPage >= totalPages ? ' disabled' : '') + '><i class="fa fa-chevron-right" style="font-size:10px;"></i></button>';

    html += '</div>'; // /.import-page-nav

    html += '</div></div>';

    document.getElementById('importPreviewTable').innerHTML = html;
}

/* ── Import Summary toggle (mobile) ─────────────── */
function toggleImportSummary() {
    var panel = document.getElementById('importSummaryBarPanel');
    var icon = document.getElementById('importSummaryBarIcon');
    var toggle = document.getElementById('importSummaryBarToggle');
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        icon.className = 'fa fa-chevron-up';
        toggle.style.borderRadius = '12px 12px 0 0';
        toggle.style.borderBottom = '1px solid #f0f0f0';
    } else {
        panel.style.display = 'none';
        icon.className = 'fa fa-chevron-down';
        toggle.style.borderRadius = '12px';
        toggle.style.borderBottom = '1px solid #e5e7eb';
    }
}

/* ── Checkbox functions ──────────────────────────── */
function toggleSelectAll(checked) {
    var startIdx = (_previewPage - 1) * _previewPerPage;
    var endIdx = Math.min(startIdx + _previewPerPage, _filteredIndices.length);
    for (var i = startIdx; i < endIdx; i++) {
        _selectedRows[_filteredIndices[i]] = checked;
    }
    updateSelectedCount();
    renderPreviewPage();
}

function toggleSelectAllGlobal(checked) {
    // Select EVERY row visually — Ready, Duplicate, and Error all get checked.
    // The backend silently skips invalid + duplicate rows at import time, so the visual
    // "all rows selected" state is purely UX. Only the imported count reflects valid+unique rows.
    _previewData.rows.forEach(function(row, i) {
        _selectedRows[i] = checked;
    });
    var msg = checked
        ? 'All ' + _previewData.rows.length + ' row' + (_previewData.rows.length !== 1 ? 's' : '') + ' selected (invalid & duplicate will be skipped on import).'
        : 'All rows deselected.';
    showSettingsToast(msg, 'success');
    renderPreview(_previewData, true);
}

function toggleRowSelect(idx, checked) {
    _selectedRows[idx] = checked;
    updateSelectedCount();
    renderPreview(_previewData, true);
}

function updateSelectedCount() {
    var count = 0;
    for (var k in _selectedRows) {
        if (_selectedRows[k]) count++;
    }
    var btn = document.getElementById('importConfirmBtn');
    btn.disabled = (count === 0);
    btn.style.opacity = count === 0 ? '0.5' : '1';
    btn.style.cursor = count === 0 ? 'not-allowed' : 'pointer';
    // Update button text with count
    btn.innerHTML = '<i class="fa fa-check"></i> Import ' + count + ' Rows';

    // Mirror to top toolbar Import button (preview header) — keep visual states in sync
    var topBtn = document.getElementById('importConfirmBtnTop');
    if (topBtn) {
        var disabled = (count === 0);
        topBtn.disabled = disabled;
        topBtn.style.cursor = disabled ? 'not-allowed' : 'pointer';
        topBtn.style.background = disabled ? '#f3f4f6' : '#f97316';
        topBtn.style.color = disabled ? '#9ca3af' : '#ffffff';
        topBtn.style.borderColor = disabled ? '#e5e7eb' : 'transparent';
        topBtn.style.boxShadow = disabled ? 'none' : '0 1px 2px rgba(249,115,22,0.20), 0 1px 3px rgba(15,23,42,0.06)';
        topBtn.setAttribute('title', disabled ? 'Select rows to import' : 'Import selected rows');
        var label = disabled ? 'Import Selected' : 'Import ' + count.toLocaleString() + ' Row' + (count !== 1 ? 's' : '');
        topBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg><span>' + label + '</span>';
    }
}

/* ── Edit functions ─────────────────────────────── */
// Convert any human/Excel date format into the ISO YYYY-MM-DD that <input type="date"> needs.
// Accepts: "27/04/2026", "27-04-2026", "2026-04-27", "2026/04/27", Excel serial numbers, etc.
// Returns '' when unparseable (so the date input shows blank instead of crashing).
function _toIsoDate(v) {
    if (v === null || v === undefined || v === '') return '';
    var s = String(v).trim();
    // Already ISO (YYYY-MM-DD)? Strip any time portion and return.
    var iso = s.match(/^(\d{4})[-\/](\d{2})[-\/](\d{2})/);
    if (iso) return iso[1] + '-' + iso[2] + '-' + iso[3];
    // UK / EU style DD/MM/YYYY or DD-MM-YYYY
    var uk = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
    if (uk) {
        var d = uk[1].padStart(2, '0'), m = uk[2].padStart(2, '0'), y = uk[3];
        return y + '-' + m + '-' + d;
    }
    // "DD Mon YYYY" e.g. "01 May 2026"
    var monthMap = { jan:'01', feb:'02', mar:'03', apr:'04', may:'05', jun:'06', jul:'07', aug:'08', sep:'09', oct:'10', nov:'11', dec:'12' };
    var named = s.match(/^(\d{1,2})\s+([A-Za-z]{3,9})\s+(\d{4})/);
    if (named) {
        var mm = monthMap[named[2].toLowerCase().slice(0,3)];
        if (mm) return named[3] + '-' + mm + '-' + named[1].padStart(2, '0');
    }
    return '';
}

function startRowEdit(idx) {
    _editingRow = idx;
    var row = _previewData.rows[idx];
    var headers = _previewData.headers || [];
    _editValues = {};
    if (row.raw) {
        row.raw.forEach(function(val, ci) {
            var hdr = (headers[ci] || '').toLowerCase().trim();
            // Date columns: normalize to DD/MM/YYYY for the custom modern picker.
            // Mobile modal still uses native <input type="date"> via _toIsoDate when it opens.
            if (hdr === 'date') {
                _editValues[ci] = (window._impDatePicker ? window._impDatePicker.toDisplay(val) : (val || ''));
            } else {
                _editValues[ci] = val || '';
            }
        });
    }
    // On mobile (≤767px) show the bottom-sheet modal; on tablet/desktop keep inline-edit row.
    if (window.matchMedia && window.matchMedia('(max-width: 767px)').matches) {
        openRowEditModal(idx);
        return;
    }
    renderPreviewPage();
}

// ── Mobile-only row-edit bottom sheet ──────────────────────────────────────
// Builds form fields from preview headers (skips the trailing Status column),
// applies number-input safeguards (block -, +, e/E keys; coerce negatives to abs)
// for qty/price/total cells. Save reuses the existing saveRowEdit() pipeline.
function openRowEditModal(idx) {
    var modal = document.getElementById('rowEditModal');
    var body = document.getElementById('rowEditModalBody');
    var titleEl = document.getElementById('rowEditModalTitle');
    if (!modal || !body || !_previewData || !_previewData.headers) return;
    titleEl.textContent = 'Edit row ' + (idx + 1);

    var headers = _previewData.headers;
    var rowErrors = (_previewData.rows[idx] && _previewData.rows[idx].error_types) || [];
    // First pass: build a list of field-meta objects (we'll sort/reorder before emitting HTML).
    var fields = [];
    for (var ci = 0; ci < headers.length - 1; ci++) {
        var hdrRaw = headers[ci] || '';
        var hdr = hdrRaw.toLowerCase().trim();
        var val = (_editValues[ci] != null ? _editValues[ci] : '').toString();
        var labelText = hdrRaw.toUpperCase();
        var placeholder = '';
        var inputType = 'text';
        var inputPrefix = '';
        var isNumeric = false;
        if (hdr === 'quantity' || hdr === 'qty') { placeholder = 'e.g. 10'; inputType = 'number'; isNumeric = true; }
        else if ((hdr.indexOf('price') !== -1 || hdr === 'rate') && hdr.indexOf('total') === -1) { placeholder = 'e.g. 5.50'; inputType = 'number'; isNumeric = true; inputPrefix = '£'; }
        else if (hdr === 'total' || hdr === 'amount' || hdr === 'sub_total' || hdr === 'subtotal') { placeholder = 'e.g. 55.00'; inputType = 'number'; isNumeric = true; inputPrefix = '£'; }
        else if (hdr === 'date') { placeholder = 'YYYY-MM-DD'; inputType = 'date'; }
        // Supplier/Customer Return are NUMERIC quantities (how many units returned), not names.
        // Must be checked BEFORE the generic "supplier" / "customer" name match below.
        else if (hdr.indexOf('return') !== -1) { placeholder = '0'; inputType = 'number'; isNumeric = true; }
        else if (hdr === 'paid' || hdr === 'paid amount' || hdr.indexOf('paid') !== -1) { placeholder = '0.00'; inputType = 'number'; isNumeric = true; }
        else if (hdr === 'dump' || hdr.indexOf('dump') !== -1) { placeholder = '0'; inputType = 'number'; isNumeric = true; }
        else if (hdr.indexOf('supplier') !== -1) { placeholder = 'Supplier name'; }
        else if (hdr.indexOf('customer') !== -1) { placeholder = 'Customer name'; }
        else if (hdr.indexOf('product') !== -1) { placeholder = 'Product name'; }

        // Long-text fields span full row (product / customer / supplier name).
        // Paid + Dump + Supplier/Customer Return are NUMERIC inputs and stay half-width (inline pair).
        var spanFull = (
            hdr.indexOf('return') === -1 && (
                hdr.indexOf('product') !== -1 ||
                hdr.indexOf('customer') !== -1 ||
                hdr.indexOf('supplier') !== -1
            )
        );

        // Cell-level error detection mirrors the inline-edit logic.
        var hasErr = false;
        if (hdr === 'date' && (rowErrors.indexOf('missing_date') !== -1 || rowErrors.indexOf('invalid_date') !== -1)) hasErr = true;
        if ((hdr === 'quantity' || hdr === 'qty') && (rowErrors.indexOf('missing_quantity') !== -1 || rowErrors.indexOf('invalid_quantity') !== -1)) hasErr = true;
        if ((hdr.indexOf('price') !== -1 || hdr === 'rate') && hdr.indexOf('sale') === -1 && hdr.indexOf('total') === -1 && (rowErrors.indexOf('missing_price') !== -1 || rowErrors.indexOf('invalid_price') !== -1)) hasErr = true;
        if ((hdr === 'total' || hdr === 'amount') && rowErrors.indexOf('invalid_total') !== -1) hasErr = true;
        if (hdr.indexOf('supplier') !== -1 && rowErrors.indexOf('missing_supplier') !== -1) hasErr = true;
        if (hdr.indexOf('customer') !== -1 && rowErrors.indexOf('missing_customer') !== -1) hasErr = true;
        if (hdr.indexOf('product') !== -1 && rowErrors.indexOf('missing_product') !== -1) hasErr = true;

        fields.push({
            ci: ci, hdr: hdr, label: labelText, val: val,
            placeholder: placeholder, inputType: inputType, inputPrefix: inputPrefix,
            isNumeric: isNumeric, spanFull: spanFull, hasErr: hasErr,
            origIndex: ci
        });
    }

    // ── Reorder per UX rules ──────────────────────────────────────────────────────
    // Goal: keep Excel order generally, but ensure:
    //   1) Supplier Return appears BEFORE Paid and Dump
    //   2) Paid + Dump are pulled to the very end as an inline pair
    var priorityOf = function(f) {
        // Lower = earlier. Paid + Dump pushed to the bottom; Supplier Return slightly before them.
        if (f.hdr === 'paid' || f.hdr === 'paid amount' || f.hdr.indexOf('paid') !== -1) return 90;
        if (f.hdr === 'dump' || f.hdr.indexOf('dump') !== -1) return 91;
        if (f.hdr.indexOf('supplier return') !== -1) return 80;
        return 50;
    };
    fields.sort(function(a, b) {
        var pa = priorityOf(a), pb = priorityOf(b);
        if (pa !== pb) return pa - pb;
        return a.origIndex - b.origIndex; // preserve original Excel order within the same priority
    });

    var html = '';
    fields.forEach(function(f) {
        var safeVal = f.val.replace(/"/g, '&quot;');
        var numAttrs = '';
        if (f.isNumeric) {
            // Strict guard: digits + ONE dot only. No minus, plus, e/E, letters, spaces.
            // type=text + inputmode=decimal avoids mobile keyboard's scientific-notation keys.
            f.inputType = 'text';
            numAttrs = ' inputmode="decimal" onkeydown="window._impNumGuard.keydown.call(this,event)" oninput="window._impNumGuard.input.call(this,event)" onpaste="window._impNumGuard.paste.call(this,event)"';
        }

        // ── Special field types ────────────────────────────────────────────────
        // INVOICE NUMBER → read-only. User cannot edit; preserves the imported invoice id.
        var isInvoice = (f.hdr === 'invoice' || f.hdr === 'invoice no' || f.hdr === 'invoice number' || f.hdr === 'inv_no' || f.hdr === 'invoice_no');
        // Entity fields → render a searchable dropdown with "+ Add new" support (reuses the
        // existing Fix-Errors entity picker). Detect customer / supplier / supplier return / product.
        var entityKind = null;
        // Only NAME fields render as entity-picker dropdowns. Return columns ("Supplier Return" /
        // "Customer Return") are NUMERIC qty fields, not entities — skip dropdown for them.
        if (f.hdr.indexOf('return') !== -1) entityKind = null;
        else if (f.hdr.indexOf('customer') !== -1) entityKind = 'customer';
        else if (f.hdr.indexOf('supplier') !== -1) entityKind = 'supplier';
        else if (f.hdr.indexOf('product') !== -1) entityKind = 'product';

        html += '<div class="rem-field' + (f.spanFull ? ' rem-field-wide' : '') + (f.hasErr ? ' rem-field-err' : '') + '">';
        html +=   '<label class="rem-label">' + f.label + '</label>';

        if (entityKind) {
            // Searchable entity dropdown. slotId prefix `rem__` lets our patched _serrPickerSelect
            // (see below) propagate the selected name back to _editValues[ci].
            var slotId = 'rem__' + f.ci;
            html += _serrRenderEntityPicker(slotId, entityKind, f.val, { placeholder: 'Select ' + entityKind + '…' });
        } else if (isInvoice) {
            // Read-only invoice number — same look as other inputs but disabled with grey fill.
            html += '<input type="text" class="rem-input rem-input-readonly" data-ci="' + f.ci + '" value="' + safeVal + '" readonly title="Invoice number cannot be edited">';
        } else if (f.inputPrefix) {
            html += '<div class="rem-input-wrap">';
            html +=   '<span class="rem-prefix">' + f.inputPrefix + '</span>';
            html +=   '<input type="' + f.inputType + '" class="rem-input rem-input-prefixed" data-ci="' + f.ci + '" value="' + safeVal + '" placeholder="' + f.placeholder + '"' + numAttrs + ' oninput="_remOnInput(' + f.ci + ',this.value)">';
            html += '</div>';
        } else {
            html += '<input type="' + f.inputType + '" class="rem-input" data-ci="' + f.ci + '" value="' + safeVal + '" placeholder="' + f.placeholder + '"' + numAttrs + ' oninput="_remOnInput(' + f.ci + ',this.value)">';
        }
        html += '</div>';
    });

    body.innerHTML = html;
    modal.style.display = 'flex';
    // Lock body scroll while open
    document.body.style.overflow = 'hidden';
}

function _remOnInput(ci, val) {
    _editValues[ci] = val;
}

function closeRowEditModal() {
    var modal = document.getElementById('rowEditModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
    _editingRow = -1;
    _editValues = {};
    renderPreviewPage();
}

function submitRowEditModal() {
    if (_editingRow < 0) { closeRowEditModal(); return; }
    var idx = _editingRow;
    // Reuse the existing save pipeline (handles re-validation, error-summary recompute, etc.)
    saveRowEdit(idx);
    // saveRowEdit resets _editingRow + re-renders — just hide the modal.
    var modal = document.getElementById('rowEditModal');
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = '';
}

function cancelRowEdit() {
    _editingRow = -1;
    _editValues = {};
    renderPreviewPage();
}

function updateEditValue(ci, val) {
    _editValues[ci] = val;
}

function saveRowEdit(idx) {
    var row = _previewData.rows[idx];
    var headers = _previewData.headers;
    var wasInvalid = (row.status !== 'valid');

    // Update raw values
    for (var ci in _editValues) {
        row.raw[ci] = _editValues[ci];
        if (!_editedRowsMap[idx]) _editedRowsMap[idx] = {};
        _editedRowsMap[idx][ci] = _editValues[ci];
    }

    // ── Client-side re-validation (mirrors backend rules) ──
    // Numeric fields (qty/price/total/sell_price/paid):
    //   • digits + ONE optional dot, with OPTIONAL leading '-'
    //   • negative values are silently converted to positive (no error)
    //   • text/letters/scientific notation → invalid_*
    // Date: parseable date → ok; date-shaped (digits+separator) → auto-fixed silently;
    //       pure text → invalid_date
    // Entity fields (customer/supplier/product): must contain at least ONE letter (a-z/A-Z).
    var newErrors = [];
    var isPurchase = (_importType === 'purchase');
    // Accept optional leading '-' (negative); we'll abs() the value below.
    var NUM_RE = /^-?\d+(\.\d+)?$/;
    var foundDate = false, foundQty = false, foundPrice = false, foundTotal = false, foundSell = false, foundPaid = false;
    var foundProduct = false, foundCustomer = false, foundSupplier = false;
    // Helper: silently strip a leading '-' from the raw value in the row, so saved data is positive.
    var _absRaw = function(ci, v) {
        if (v.charAt(0) === '-') {
            var positive = v.substring(1);
            row.raw[ci] = positive;
            if (!_editedRowsMap[idx]) _editedRowsMap[idx] = {};
            _editedRowsMap[idx][ci] = positive;
            return positive;
        }
        return v;
    };
    row.raw.forEach(function(val, ci) {
        var h = (headers[ci] || '').toLowerCase().trim();
        var v = (val || '').toString().trim();
        if (v === '-') v = ''; // treat lone dash as empty

        // ── Date ──
        if (!foundDate && h === 'date') {
            foundDate = true;
            if (!v) {
                newErrors.push('missing_date');
            } else {
                // Recognised date shapes: ISO YYYY-MM-DD, UK DD/MM/YYYY, DD-MM-YYYY, DD Mon YYYY, etc.
                var dateShapeOk = /^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}/.test(v)
                    || /^\d{1,2}[-\/]\d{1,2}[-\/]\d{4}/.test(v)
                    || /^\d{1,2}\s+[A-Za-z]{3,}\s+\d{4}/.test(v)
                    || /^[A-Za-z]{3,}\s+\d{1,2},?\s+\d{4}/.test(v);
                // Pure text (no digits) → error. Any digits+separator → silent auto-fix (no error here).
                var hasDigits = /\d/.test(v);
                var hasSeparator = /[\-\/\.\s]/.test(v);
                if (!hasDigits || !hasSeparator) {
                    if (!dateShapeOk) newErrors.push('invalid_date');
                }
            }
        }
        // ── Product ──
        if (!foundProduct && (h === 'product' || h === 'product name')) {
            foundProduct = true;
            if (!v) newErrors.push('missing_product');
            else if (!/[a-zA-Z]/.test(v)) newErrors.push('missing_product');
        }
        // ── Customer ──
        if (!foundCustomer && (h === 'customer' || h === 'customer name')) {
            foundCustomer = true;
            if (!v) newErrors.push('missing_customer');
            else if (!/[a-zA-Z]/.test(v)) newErrors.push('missing_customer');
        }
        // ── Supplier ──
        if (!foundSupplier && (h === 'supplier' || h === 'supplier name')) {
            foundSupplier = true;
            var supplierRequired = !(_importType === 'sales' && !_salesSupplierRequired);
            if (supplierRequired) {
                if (!v) newErrors.push('missing_supplier');
                else if (!/[a-zA-Z]/.test(v)) newErrors.push('missing_supplier');
            }
        }
        // ── Quantity ── (digits + 1 dot, optional leading '-'; negatives silently abs)
        if (!foundQty && (h === 'quantity' || h === 'qty')) {
            foundQty = true;
            if (!v) newErrors.push('missing_quantity');
            else if (!NUM_RE.test(v)) newErrors.push('invalid_quantity');
            else {
                v = _absRaw(ci, v);
                if (parseFloat(v) === 0) newErrors.push('missing_quantity');
            }
        }
        // ── Unit Price ── (Purchase rejects 0; Sales allows 0)
        if (!foundPrice && (h === 'unit price' || h === 'unitprice' || h === 'price' || h === 'rate') && h.indexOf('sale') === -1 && h.indexOf('total') === -1) {
            foundPrice = true;
            if (!v) newErrors.push('missing_price');
            else if (!NUM_RE.test(v)) newErrors.push('invalid_price');
            else {
                v = _absRaw(ci, v);
                if (isPurchase && parseFloat(v) === 0) newErrors.push('missing_price');
            }
        }
        // ── Total ──
        if (!foundTotal && (h === 'total' || h === 'amount' || h === 'sub_total' || h === 'subtotal')) {
            foundTotal = true;
            if (!v) newErrors.push('invalid_total');
            else if (!NUM_RE.test(v)) newErrors.push('invalid_total');
            else {
                v = _absRaw(ci, v);
                if (isPurchase && parseFloat(v) === 0) newErrors.push('invalid_total');
            }
        }
        // ── Sell Price ── (optional; only validate when filled)
        if (!foundSell && (h === 'sell price' || h === 'sale price' || h === 'selling price' || h === 'sell_price' || h === 'sale_price')) {
            foundSell = true;
            if (v) {
                if (!NUM_RE.test(v)) newErrors.push('invalid_sell_price');
                else _absRaw(ci, v);
            }
        }
        // ── Paid Amount ── (optional; only validate when filled)
        if (!foundPaid && (h === 'paid' || h === 'paid amount' || h === 'amount paid' || h === 'received' || h === 'debit')) {
            foundPaid = true;
            if (v) {
                if (!NUM_RE.test(v)) newErrors.push('invalid_paid');
                else _absRaw(ci, v);
            }
        }
    });

    row.error_types = newErrors;
    row.status = newErrors.length === 0 ? 'valid' : 'invalid';

    _editingRow = -1;
    _editValues = {};

    // Auto-select if now valid
    if (row.status === 'valid') _selectedRows[idx] = true;

    // Recalculate stats + error_summary
    var valid = 0, invalid = 0, newSummary = {};
    _previewData.rows.forEach(function(r) {
        if (r.status === 'valid') valid++; else invalid++;
        if (r.error_types) r.error_types.forEach(function(et) { newSummary[et] = (newSummary[et] || 0) + 1; });
    });
    _previewData.valid = valid;
    _previewData.invalid = invalid;
    _previewData.error_summary = newSummary;

    // Toast feedback
    if (wasInvalid && row.status === 'valid') {
        showSettingsToast('Row ' + (idx + 1) + ' fixed! Now ready to import.', 'success');
    } else if (row.status !== 'valid') {
        showSettingsToast('Row ' + (idx + 1) + ' saved but still has errors: ' + newErrors.map(function(e){ return _errorTypeLabels[e] || e; }).join(', '), 'error');
    } else {
        showSettingsToast('Row ' + (idx + 1) + ' updated.', 'success');
    }

    renderPreview(_previewData, true);
}

/* ── Filter / Bulk Edit / Auto-Fix functions ──── */
/* ── Custom Filter Dropdown ─────────────────────── */
function toggleImportFilterDD(e) {
    e.stopPropagation();
    var menu = document.getElementById('importFilterDDMenu');
    if (menu.style.display === 'none') {
        menu.style.display = 'block';
        setTimeout(function(){ document.addEventListener('click', closeImportFilterDD); }, 10);
    } else {
        closeImportFilterDD();
    }
}
function closeImportFilterDD() {
    var menu = document.getElementById('importFilterDDMenu');
    if (menu) menu.style.display = 'none';
    document.removeEventListener('click', closeImportFilterDD);
}
// ── Mobile STOCK-SUMMARY–style collapsible card (matches Stock Check page UI) ──
// Renders into #importSummaryMobile using the same preview stats as the desktop cards:
// Total Rows · Ready · Skipped · Duplicates, plus a "Ready Rate" progress bar.
var _importSummaryOpen = false;
function renderImportSummaryMobile(res, validPct) {
    var el = document.getElementById('importSummaryMobile');
    if (!el) return;
    var total = res.total || 0;
    var ready = res.valid || 0;
    var skipped = res.invalid || 0;
    var dups = res.duplicates || 0;
    var rate = total > 0 ? Math.round((ready / total) * 100) : 0;
    // colours echo the desktop cards: total=ink, ready=green, skipped=red, dup=blue
    var cTotal = '#111827', cReady = '#16a34a', cSkip = '#dc2626', cDup = '#1e6bce';
    var open = _importSummaryOpen;

    var h = '';
    // Collapsed header bar — bar-chart icon + "STOCK SUMMARY" + the 4 counts + chevron
    h += '<div onclick="toggleImportSummary()" style="border-radius:' + (open ? '16px 16px 0 0' : '16px') + ';border:1px solid #e5e7eb;border-bottom:' + (open ? '1px solid #f0f0f0' : '1px solid #e5e7eb') + ';background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.05);padding:8px 14px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;margin-bottom:' + (open ? '0' : '8px') + ';">';
    h +=   '<div style="display:flex;align-items:center;gap:6px;">';
    h +=     '<i class="fa fa-bar-chart" style="font-size:11px;color:rgb(234, 88, 12);"></i>';
    h +=     '<span style="font-size:10px;font-weight:800;color:#374151;letter-spacing:0.6px;text-transform:uppercase;">Stock Summary</span>';
    h +=   '</div>';
    h +=   '<div style="display:flex;align-items:center;gap:10px;">';
    h +=     '<div style="display:flex;gap:8px;">';
    h +=       '<span style="font-size:12px;font-weight:700;color:' + cTotal + ';">' + total + '</span>';
    h +=       '<span style="font-size:12px;font-weight:700;color:' + cReady + ';">' + ready + '</span>';
    h +=       '<span style="font-size:12px;font-weight:700;color:' + cSkip + ';">' + skipped + '</span>';
    h +=       '<span style="font-size:12px;font-weight:700;color:' + cDup + ';">' + dups + '</span>';
    h +=     '</div>';
    h +=     '<i class="fa fa-chevron-' + (open ? 'up' : 'down') + '" style="font-size:9px;color:#9ca3af;"></i>';
    h +=   '</div>';
    h += '</div>';

    // Expanded body — Total/Ready/Skipped/Duplicates columns + Ready-rate bar
    if (open) {
        var cols = [
            {label:'Total',   value:total,   color:cTotal},
            {label:'Ready',   value:ready,   color:cReady},
            {label:'Skipped', value:skipped, color:cSkip},
            {label:'Dupes',   value:dups,    color:cDup},
        ];
        h += '<div style="border-radius:0 0 16px 16px;border:1px solid #e5e7eb;border-top:none;background:#fff;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);margin-bottom:8px;">';
        h +=   '<div style="display:flex;padding:10px 16px 12px;">';
        cols.forEach(function(c, i) {
            h += '<div style="flex:1;">';
            h +=   '<div style="font-size:9px;color:#9ca3af;font-weight:700;letter-spacing:0.7px;text-transform:uppercase;margin-bottom:4px;">' + c.label + '</div>';
            h +=   '<div style="font-size:24px;font-weight:700;color:' + c.color + ';line-height:1;letter-spacing:-1px;">' + c.value + '</div>';
            h += '</div>';
            if (i < cols.length - 1) h += '<div style="width:1px;background:#e5e7eb;margin:0 8px;align-self:stretch;"></div>';
        });
        h +=   '</div>';
        h +=   '<div style="height:1px;background:#e5e7eb;margin:0 16px;"></div>';
        h +=   '<div style="padding:8px 16px;">';
        h +=     '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">';
        h +=       '<span style="font-size:9px;color:#9ca3af;font-weight:700;letter-spacing:0.7px;text-transform:uppercase;">Ready Rate</span>';
        h +=       '<span style="font-size:10px;color:#9ca3af;font-weight:600;">' + rate + '%</span>';
        h +=     '</div>';
        h +=     '<div style="height:3px;border-radius:99px;background:#e5e7eb;overflow:hidden;">';
        h +=       '<div style="height:100%;width:' + rate + '%;border-radius:99px;background:rgb(234, 88, 12);"></div>';
        h +=     '</div>';
        h +=   '</div>';
        h += '</div>';
    }
    el.innerHTML = h;
}
function toggleImportSummary() {
    _importSummaryOpen = !_importSummaryOpen;
    if (typeof _previewData !== 'undefined' && _previewData) {
        var total = _previewData.total || 0;
        var validPct = total > 0 ? Math.round(((_previewData.valid || 0) / total) * 100) : 0;
        renderImportSummaryMobile(_previewData, validPct);
    }
}

function selectImportFilter(value) {
    closeImportFilterDD();
    setFilterMode(value);
}

function toggleInvalidFilter() {
    _filterMode = (_filterMode === 'invalid') ? 'all' : 'invalid';
    _previewPage = 1;
    renderPreview(_previewData, true);
}

function setFilterMode(mode) {
    // From dropdown: direct set. From chip: toggle.
    if (mode === 'all' || mode === 'valid' || mode === 'invalid') {
        _filterMode = mode;
    } else {
        _filterMode = (_filterMode === mode) ? 'all' : mode;
    }
    _previewPage = 1;
    renderPreview(_previewData, true);
}

function selectAllInvalid() {
    var count = 0;
    _previewData.rows.forEach(function(row, i) {
        if (row.status !== 'valid') { _selectedRows[i] = true; count++; }
    });
    showSettingsToast(count + ' invalid rows selected.', 'success');
    renderPreview(_previewData, true);
}

function deleteSelectedRows() {
    var count = 0;
    for (var k in _selectedRows) { if (_selectedRows[k]) count++; }
    if (count === 0) { showSettingsToast('No rows selected.', 'error'); return; }
    if (!confirm('Remove ' + count + ' selected rows from import preview? They will not be imported.')) return;

    // Remove selected rows (reverse order to preserve indices)
    var toRemove = [];
    for (var k in _selectedRows) { if (_selectedRows[k]) toRemove.push(parseInt(k)); }
    toRemove.sort(function(a,b){ return b - a; }); // reverse
    toRemove.forEach(function(idx) {
        _previewData.rows.splice(idx, 1);
    });

    // Rebuild selectedRows and stats
    _selectedRows = {};
    var valid = 0, invalid = 0;
    _previewData.rows.forEach(function(r, i) {
        _selectedRows[i] = false;
        if (r.status === 'valid') valid++; else invalid++;
    });
    _previewData.valid = valid;
    _previewData.invalid = invalid;
    _previewData.total = _previewData.rows.length;

    // Rebuild error_summary
    var newSummary = {};
    _previewData.rows.forEach(function(r) {
        if (r.error_types) {
            r.error_types.forEach(function(et) {
                newSummary[et] = (newSummary[et] || 0) + 1;
            });
        }
    });
    _previewData.error_summary = newSummary;

    _previewPage = 1;
    _filterMode = 'all';
    showSettingsToast(count + ' rows removed from import.', 'success');
    renderPreview(_previewData, true);
}

function applyAutoFix(errorType) {
    var res = _previewData;
    var headers = res.headers;
    var fixed = 0;

    res.rows.forEach(function(row, idx) {
        if (!row.error_types || row.error_types.indexOf(errorType) === -1) return;

        row.raw.forEach(function(val, ci) {
            var header = (headers[ci] || '').toLowerCase();
            if (errorType === 'invalid_price' && (header.indexOf('price') !== -1 || header.indexOf('rate') !== -1 || header.indexOf('unit') !== -1)) {
                var num = parseFloat(val);
                if (!isNaN(num) && num < 0) {
                    row.raw[ci] = Math.abs(num).toString();
                    if (!_editedRowsMap[idx]) _editedRowsMap[idx] = {};
                    _editedRowsMap[idx][ci] = row.raw[ci];
                }
            }
            if (errorType === 'invalid_quantity' && (header.indexOf('qty') !== -1 || header.indexOf('quantity') !== -1)) {
                var num = parseFloat(val);
                if (!isNaN(num) && num < 0) {
                    row.raw[ci] = Math.abs(num).toString();
                    if (!_editedRowsMap[idx]) _editedRowsMap[idx] = {};
                    _editedRowsMap[idx][ci] = row.raw[ci];
                }
            }
        });

        // Remove the fixed error type
        row.error_types = row.error_types.filter(function(et) { return et !== errorType; });
        if (row.error_types.length === 0) {
            row.status = 'valid';
            _selectedRows[idx] = true; // auto-select fixed rows
        }
        fixed++;
    });

    // Recalculate stats
    var valid = 0, invalid = 0;
    var newSummary = {};
    res.rows.forEach(function(r) {
        if (r.status === 'valid') valid++; else invalid++;
        if (r.error_types) r.error_types.forEach(function(et) { newSummary[et] = (newSummary[et] || 0) + 1; });
    });
    res.valid = valid;
    res.invalid = invalid;
    res.error_summary = newSummary;

    showSettingsToast('Auto-fixed ' + fixed + ' rows!', 'success');
    _filterMode = 'all';
    renderPreview(_previewData, true);
}

function renderBulkEditBar() {
    var barEl = document.getElementById('importBulkEditBar');
    // Both Sales & Purchase use the new "Fix Errors" modal — never show the inline bulk-edit bar.
    barEl.style.display = 'none';
    return;
    // Show bulk edit bar only when filtering by a specific error type that's bulk-editable
    if (_filterMode !== 'all' && _filterMode !== 'invalid' &&
        ['missing_quantity', 'missing_price', 'missing_product', 'missing_customer', 'missing_supplier'].indexOf(_filterMode) !== -1) {
        var label = _errorTypeLabels[_filterMode] || _filterMode;
        var count = _previewData.error_summary[_filterMode] || 0;
        var html = '<div class="import-bulk-bar">';
        html += '<i class="fa fa-magic" style="color:#f97316;font-size:14px;"></i>';
        html += '<span style="font-size:12px;font-weight:700;color:#374151;">Bulk Fix: ' + label + ' (' + count + ' rows)</span>';
        html += '<input type="text" class="import-bulk-input" id="bulkEditValue" placeholder="Enter value...">';
        html += '<button type="button" class="import-filter-btn active" onclick="applyBulkEdit()" style="height:32px;"><i class="fa fa-check"></i> Apply to All</button>';
        html += '<button type="button" class="import-filter-btn" onclick="setFilterMode(\'all\')" style="height:32px;"><i class="fa fa-times"></i> Cancel</button>';
        html += '</div>';
        barEl.innerHTML = html;
        barEl.style.display = 'block';
    } else {
        barEl.style.display = 'none';
    }
}

function applyBulkEdit() {
    var value = document.getElementById('bulkEditValue').value.trim();
    if (!value) { showSettingsToast('Please enter a value.', 'error'); return; }

    var res = _previewData;
    var headers = res.headers;
    var errorType = _filterMode;
    var fixed = 0;

    // Map error type to target column header keywords
    var colKeywords = {
        'missing_quantity': ['qty', 'quantity'],
        'missing_price': ['price', 'rate', 'unit price', 'unitprice'],
        'missing_product': ['product'],
        'missing_customer': ['customer'],
        'missing_supplier': ['supplier', 'supplier name']
    };
    var keywords = colKeywords[errorType] || [];

    // Find target column index
    var targetCol = -1;
    headers.forEach(function(h, ci) {
        var hl = h.toLowerCase();
        keywords.forEach(function(kw) {
            if (hl.indexOf(kw) !== -1 && targetCol === -1) targetCol = ci;
        });
    });

    if (targetCol === -1) { showSettingsToast('Could not find target column.', 'error'); return; }

    res.rows.forEach(function(row, idx) {
        if (!row.error_types || row.error_types.indexOf(errorType) === -1) return;
        row.raw[targetCol] = value;
        if (!_editedRowsMap[idx]) _editedRowsMap[idx] = {};
        _editedRowsMap[idx][targetCol] = value;

        // Remove fixed error type
        row.error_types = row.error_types.filter(function(et) { return et !== errorType; });
        if (row.error_types.length === 0) {
            row.status = 'valid';
            _selectedRows[idx] = true;
        }
        fixed++;
    });

    // Recalculate
    var valid = 0, invalid = 0;
    var newSummary = {};
    res.rows.forEach(function(r) {
        if (r.status === 'valid') valid++; else invalid++;
        if (r.error_types) r.error_types.forEach(function(et) { newSummary[et] = (newSummary[et] || 0) + 1; });
    });
    res.valid = valid;
    res.invalid = invalid;
    res.error_summary = newSummary;

    showSettingsToast('Applied to ' + fixed + ' rows!', 'success');
    _filterMode = 'all';
    renderPreview(_previewData, true);
}

/* ─────────────────────────────────────────────────────────────────────────────
   Sales Import: Centralized Error Fixer Modal
   Single popup that lists every invalid row grouped by error type.
   User edits cells inline + can bulk-apply a value to all rows in a group.
   ───────────────────────────────────────────────────────────────────────────── */
var _salesErrFixerErrorOrder = [
    'missing_customer', 'missing_product', 'missing_date', 'invalid_date',
    'missing_quantity', 'invalid_quantity', 'missing_price', 'invalid_price',
    'invalid_total', 'missing_supplier', 'missing_invoice',
    'duplicate' // duplicates last — info-only tab, no fixable input
];
var _salesErrFixerActiveTab = null; // currently selected error-type tab

// Cached entity lists for the Customer/Supplier/Product dropdowns inside the Fix-Errors modal.
// Populated lazily by _serrLoadEntities() on first need; refreshed when a new one is added.
var _serrEntities = { customer: null, supplier: null, product: null }; // arrays of { id, name }
var _serrLoadingPromise = { customer: null, supplier: null, product: null };

function _serrEntityTypeFor(errType) {
    if (errType === 'missing_customer') return 'customer';
    if (errType === 'missing_supplier') return 'supplier';
    if (errType === 'missing_product')  return 'product';
    return null;
}

// Convert any date-ish string (DD/MM/YYYY, YYYY-MM-DD, etc.) to ISO YYYY-MM-DD
// for use with native <input type="date">. Returns empty string if unparseable.
function _serrToISODate(v) {
    if (!v) return '';
    var s = String(v).trim();
    var m;
    // dd/mm/yyyy or dd-mm-yyyy
    m = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
    if (m) {
        var d = String(parseInt(m[1], 10)).padStart(2, '0');
        var mo = String(parseInt(m[2], 10)).padStart(2, '0');
        return m[3] + '-' + mo + '-' + d;
    }
    // yyyy-mm-dd or yyyy/mm/dd
    m = s.match(/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/);
    if (m) {
        return m[1] + '-' + String(parseInt(m[2], 10)).padStart(2, '0') + '-' + String(parseInt(m[3], 10)).padStart(2, '0');
    }
    return '';
}

// Convert ISO YYYY-MM-DD back to DD/MM/YYYY for backend (matches parseDateString expectations)
function _serrFromISODate(v) {
    if (!v) return '';
    var s = String(v).trim();
    var m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
    if (m) {
        return String(parseInt(m[3], 10)).padStart(2, '0') + '/' +
               String(parseInt(m[2], 10)).padStart(2, '0') + '/' + m[1];
    }
    return s;
}

function _serrLoadEntities(kind) {
    if (_serrEntities[kind]) return Promise.resolve(_serrEntities[kind]);
    if (_serrLoadingPromise[kind]) return _serrLoadingPromise[kind];
    var url;
    if (kind === 'customer')      url = "{{ route('management.settings.import.entities.customers') }}";
    else if (kind === 'supplier') url = "{{ route('management.settings.import.entities.suppliers') }}";
    else if (kind === 'product')  url = "{{ route('management.settings.import.entities.products') }}";
    else return Promise.resolve([]);
    _serrLoadingPromise[kind] = $.ajax({ url: url, method: 'GET' }).then(function(res) {
        _serrEntities[kind] = (res && res.success && Array.isArray(res.items)) ? res.items : [];
        _serrLoadingPromise[kind] = null;
        return _serrEntities[kind];
    }, function() {
        _serrLoadingPromise[kind] = null;
        _serrEntities[kind] = [];
        return [];
    });
    return _serrLoadingPromise[kind];
}

// Build the searchable entity picker (combobox) — used as both per-row input and bulk-apply input.
// `slotId` uniquely identifies this picker instance so we can find its trigger/panel.
// `kind` = 'customer' | 'supplier'. `currentValue` is the initial display name (or '').
function _serrRenderEntityPicker(slotId, kind, currentValue, opts) {
    opts = opts || {};
    var placeholder = opts.placeholder || ('Select ' + (kind || 'item') + '...');
    var widthCss = opts.width ? ('width:' + opts.width + ';') : 'width:100%;';
    var html = '';
    html += '<div class="serr-picker" data-slot="' + escapeHtml(slotId) + '" data-kind="' + kind + '" style="position:relative;' + widthCss + '">';
    // Trigger button
    html += '<div class="serr-picker-trigger" tabindex="0" onclick="_serrPickerToggle(\'' + escapeHtml(slotId) + '\',event)" onkeydown="if(event.key===\'Enter\'||event.key===\' \'){event.preventDefault();_serrPickerToggle(\'' + escapeHtml(slotId) + '\',event);}" style="display:flex;align-items:center;justify-content:space-between;height:34px;padding:0 12px;border-radius:8px;border:1.5px solid #e5e7eb;background:#ffffff;font-size:12px;font-weight:500;color:' + (currentValue ? '#0f172a' : '#9ca3af') + ';cursor:pointer;outline:none;transition:border-color 0.15s,box-shadow 0.15s;user-select:none;" onmouseover="this.style.borderColor=\'#f97316\'" onmouseout="if(!this.dataset.open){this.style.borderColor=\'#e5e7eb\'}">';
    html +=   '<span class="serr-picker-label" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;min-width:0;">' + escapeHtml(currentValue || placeholder) + '</span>';
    html +=   '<svg width="10" height="10" viewBox="0 0 20 20" style="fill:#9ca3af;flex-shrink:0;margin-left:8px;"><path d="M4.516 7.548c.436-.446 1.043-.481 1.576 0L10 11.295l3.908-3.747c.533-.481 1.141-.446 1.574 0 .436.445.408 1.197 0 1.615l-4.695 4.502c-.217.223-.502.335-.787.335s-.57-.112-.789-.335L4.516 9.163c-.408-.418-.436-1.17 0-1.615z"/></svg>';
    html += '</div>';
    // Hidden actual value carrier
    html += '<input type="hidden" data-picker-value="' + escapeHtml(slotId) + '" value="' + escapeHtml(currentValue || '') + '">';
    html += '</div>';
    return html;
}

// Floating panel state — only one open at a time
var _serrOpenPickerSlot = null;

function _serrPickerToggle(slotId, ev) {
    if (ev) { ev.stopPropagation(); }
    if (_serrOpenPickerSlot === slotId) { _serrPickerClose(); return; }
    _serrPickerClose();
    _serrOpenPickerSlot = slotId;
    var picker = document.querySelector('.serr-picker[data-slot="' + (window.CSS && CSS.escape ? CSS.escape(slotId) : slotId) + '"]');
    if (!picker) return;
    var kind = picker.getAttribute('data-kind');
    var trigger = picker.querySelector('.serr-picker-trigger');
    if (trigger) { trigger.dataset.open = '1'; trigger.style.borderColor = '#f97316'; trigger.style.boxShadow = '0 0 0 3px rgba(249,115,22,0.10)'; }

    // Compute trigger position so we can render a fixed-position panel (escapes overflow:auto parents)
    var rect = trigger.getBoundingClientRect();
    var preferredHeight = 280;
    var gap = 6;
    var margin = 8;
    var spaceBelow = window.innerHeight - rect.bottom - margin;
    var spaceAbove = rect.top - margin;
    // Prefer opening downward. Flip up ONLY if down has clearly less room than up.
    var openUp = (spaceBelow < 160) && (spaceAbove > spaceBelow);
    var availableHeight = openUp ? spaceAbove : spaceBelow;
    var panelHeight = Math.min(preferredHeight, Math.max(160, availableHeight - gap));
    var top = openUp ? (rect.top - panelHeight - gap) : (rect.bottom + gap);
    // Clamp horizontally inside viewport too
    var panelWidth = Math.max(rect.width, 240);
    var left = rect.left;
    if (left + panelWidth > window.innerWidth - margin) {
        left = Math.max(margin, window.innerWidth - panelWidth - margin);
    }
    // Build the panel as a fixed-position overlay so overflow:auto containers don't clip it
    var panel = document.createElement('div');
    panel.id = 'serrPickerPanel';
    panel.style.cssText = 'position:fixed;top:' + Math.max(margin, top) + 'px;left:' + left + 'px;width:' + panelWidth + 'px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 8px 24px rgba(15,23,42,0.14);z-index:99999;overflow:hidden;display:flex;flex-direction:column;max-height:' + panelHeight + 'px;';
    panel.setAttribute('data-picker-panel-for', slotId);
    panel.innerHTML =
        '<div style="padding:8px;border-bottom:1px solid #f1f5f9;">' +
            '<input type="text" id="serrPickerSearch" placeholder="Search or type to add new..." autocomplete="off" style="width:100%;height:32px;padding:0 10px;border-radius:7px;border:1.5px solid #e5e7eb;background:#fafafa;font-size:12px;font-weight:500;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s,background 0.15s;" onfocus="this.style.borderColor=\'#f97316\';this.style.background=\'#fff\';this.style.boxShadow=\'0 0 0 3px rgba(249,115,22,0.08)\'" onblur="this.style.borderColor=\'#e5e7eb\';this.style.background=\'#fafafa\';this.style.boxShadow=\'none\'">' +
        '</div>' +
        '<div id="serrPickerList" style="flex:1;overflow-y:auto;padding:4px;min-height:60px;">' +
            '<div style="padding:14px;text-align:center;color:#94a3b8;font-size:12px;"><i class="fa fa-spinner fa-spin" style="margin-right:6px;"></i>Loading…</div>' +
        '</div>';
    document.body.appendChild(panel);

    var searchInput = panel.querySelector('#serrPickerSearch');
    searchInput.addEventListener('input', function() { _serrPickerRefreshList(slotId, kind, this.value); });
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { _serrPickerClose(); }
        if (e.key === 'Enter') {
            e.preventDefault();
            var typed = this.value.trim();
            if (!typed) return;
            // Exact match → select; otherwise open the add popup (pre-seeded with the typed name)
            var list = _serrEntities[kind] || [];
            var hit = list.find(function(x) { return x.name.toLowerCase() === typed.toLowerCase(); });
            if (hit) _serrPickerSelect(slotId, hit.name);
            else _serrOpenAddPopup(slotId, kind);
        }
    });
    setTimeout(function(){ searchInput.focus(); }, 30);

    // Load + render list
    _serrLoadEntities(kind).then(function() { _serrPickerRefreshList(slotId, kind, ''); });
}

function _serrPickerClose() {
    var existing = document.getElementById('serrPickerPanel');
    if (existing && existing.parentNode) existing.parentNode.removeChild(existing);
    if (_serrOpenPickerSlot) {
        var picker = document.querySelector('.serr-picker[data-slot="' + (window.CSS && CSS.escape ? CSS.escape(_serrOpenPickerSlot) : _serrOpenPickerSlot) + '"]');
        if (picker) {
            var t = picker.querySelector('.serr-picker-trigger');
            if (t) { delete t.dataset.open; t.style.borderColor = '#e5e7eb'; t.style.boxShadow = 'none'; }
        }
    }
    _serrOpenPickerSlot = null;
}

function _serrPickerRefreshList(slotId, kind, query) {
    var listEl = document.getElementById('serrPickerList');
    if (!listEl) return;
    var items = _serrEntities[kind] || [];
    var q = (query || '').trim().toLowerCase();
    var filtered = q ? items.filter(function(x) { return x.name.toLowerCase().indexOf(q) !== -1; }) : items.slice();
    // Limit list to first 200 to keep DOM light
    var capped = filtered.slice(0, 200);
    var html = '';

    var kindLabelMap = { customer: 'customer', supplier: 'supplier', product: 'product' };
    var kindLabel = kindLabelMap[kind] || 'item';

    if (capped.length === 0 && !q) {
        // No data + no search — keep a soft empty hint above the always-present Add link
        html += '<div style="padding:14px;text-align:center;color:#94a3b8;font-size:12px;">No ' + kindLabel + 's yet.</div>';
    }

    capped.forEach(function(it) {
        html += '<div class="serr-picker-opt" onclick="_serrPickerSelect(\'' + escapeHtml(slotId) + '\',\'' + escapeHtml(it.name).replace(/'/g, '&#39;') + '\')" onmouseover="this.style.background=\'#fff7ed\';this.style.color=\'#ea580c\'" onmouseout="this.style.background=\'\';this.style.color=\'#0f172a\'" style="padding:8px 12px;cursor:pointer;font-size:12.5px;font-weight:500;color:#0f172a;border-radius:6px;transition:background 0.12s,color 0.12s;margin-bottom:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' + escapeHtml(it.name) + '">' + escapeHtml(it.name) + '</div>';
    });

    if (filtered.length > 200) {
        html += '<div style="padding:6px 12px;text-align:center;color:#94a3b8;font-size:11px;">+' + (filtered.length - 200) + ' more — refine your search</div>';
    }

    // Always-visible "Add new" footer link — opens a small popup with a Name field
    html += '<div class="serr-picker-addnew" onclick="_serrOpenAddPopup(\'' + escapeHtml(slotId) + '\',\'' + kind + '\')" onmouseover="this.style.background=\'#fff7ed\'" onmouseout="this.style.background=\'#ffffff\'" style="margin-top:4px;padding:10px 12px;cursor:pointer;font-size:12.5px;font-weight:700;color:#ea580c;border-top:1px dashed #fed7aa;background:#ffffff;transition:background 0.12s;display:flex;align-items:center;gap:8px;">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>' +
        '<span>Add new ' + kindLabel + '</span>' +
        '</div>';

    listEl.innerHTML = html;
}

function _serrPickerSelect(slotId, name) {
    var picker = document.querySelector('.serr-picker[data-slot="' + (window.CSS && CSS.escape ? CSS.escape(slotId) : slotId) + '"]');
    if (!picker) return;
    var labelEl = picker.querySelector('.serr-picker-label');
    var hidden = picker.querySelector('input[data-picker-value]');
    var trigger = picker.querySelector('.serr-picker-trigger');
    if (labelEl) labelEl.textContent = name;
    if (hidden) hidden.value = name;
    if (trigger) trigger.style.color = '#0f172a';
    // ── Row-edit modal hook ────────────────────────────────────────────────────
    // When the picker is embedded in the mobile Row-Edit modal (slot prefix `rem__`),
    // propagate the chosen name back to _editValues so saveRowEdit() picks it up.
    if (slotId && slotId.indexOf('rem__') === 0) {
        var ci = parseInt(slotId.substring(5), 10);
        if (!isNaN(ci) && typeof _remOnInput === 'function') {
            _remOnInput(ci, name);
            // Clear field error highlight now that a value is chosen
            if (picker.parentElement) picker.parentElement.classList.remove('rem-field-err');
        }
    }
    _serrPickerClose();
}


// Close picker on outside click (panel is in body so check both picker trigger AND panel)
document.addEventListener('click', function(e) {
    if (!_serrOpenPickerSlot) return;
    var slot = _serrOpenPickerSlot;
    var slotEsc = (window.CSS && CSS.escape ? CSS.escape(slot) : slot);
    if (e.target.closest('.serr-picker[data-slot="' + slotEsc + '"]')) return;
    if (e.target.closest('[data-picker-panel-for="' + slotEsc + '"]')) return;
    _serrPickerClose();
});

// Quick-Add Entity popup (opens from "+ Add new" in the picker dropdown).
// Holds which slot/kind to push back to after a successful save.
var _serrAddPopupCtx = null;

function _serrOpenAddPopup(slotId, kind) {
    _serrAddPopupCtx = { slotId: slotId, kind: kind };
    var modal = document.getElementById('serrAddEntityModal');
    var title = document.getElementById('serrAddEntityTitle');
    var label = document.getElementById('serrAddEntityLabel');
    var icon = document.getElementById('serrAddEntityIcon');
    var input = document.getElementById('serrAddEntityName');
    var subtitleEl = modal.querySelector('div > div > div:first-child > div:nth-child(2) > div:nth-child(2)');
    // Seed name from the picker's search box if user already typed something
    var searchEl = document.getElementById('serrPickerSearch');
    var seed = (searchEl && searchEl.value) ? searchEl.value.trim() : '';
    var kindCapMap = { customer: 'Customer', supplier: 'Supplier', product: 'Product' };
    var kindIconMap = { customer: 'user', supplier: 'building', product: 'cube' };
    var kindPageMap = { customer: 'Customers', supplier: 'Suppliers', product: 'Products' };
    var kindCap = kindCapMap[kind] || 'Item';
    title.textContent = 'Add New ' + kindCap;
    label.textContent = kindCap + ' Name';
    icon.className = 'fa fa-' + (kindIconMap[kind] || 'plus-circle');
    if (subtitleEl) {
        subtitleEl.textContent = 'More details can be added later from the ' + (kindPageMap[kind] || 'master') + ' page.';
    }
    input.value = seed;
    input.placeholder = 'Enter ' + kind + ' name';
    // Reset visual error state from any previous attempt.
    input.style.borderColor = '#e5e7eb';
    input.style.background = '#fafafa';
    input.style.boxShadow = 'none';
    var errEl = document.getElementById('serrAddEntityError');
    if (errEl) { errEl.style.display = 'none'; errEl.textContent = ''; }
    modal.style.display = 'flex';
    setTimeout(function() { input.focus(); input.select && input.select(); }, 30);

    // Enter to submit, Escape to close
    input.onkeydown = function(e) {
        if (e.key === 'Enter') { e.preventDefault(); _serrSubmitAddPopup(); }
        else if (e.key === 'Escape') { _serrCloseAddPopup(); }
    };
}

function _serrCloseAddPopup() {
    document.getElementById('serrAddEntityModal').style.display = 'none';
    _serrAddPopupCtx = null;
}

function _serrSubmitAddPopup() {
    if (!_serrAddPopupCtx) return;
    var input = document.getElementById('serrAddEntityName');
    var name = (input.value || '').trim();
    if (!name) {
        showSettingsToast('Please enter a name.', 'error');
        input.focus();
        return;
    }
    var ctx = _serrAddPopupCtx;
    var btn = document.getElementById('serrAddEntitySaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin" style="font-size:11px;"></i> Saving...';
    var url;
    if (ctx.kind === 'customer')      url = "{{ route('management.settings.import.entities.customer_quick_add') }}";
    else if (ctx.kind === 'supplier') url = "{{ route('management.settings.import.entities.supplier_quick_add') }}";
    else if (ctx.kind === 'product')  url = "{{ route('management.settings.import.entities.product_quick_add') }}";
    else { showSettingsToast('Unknown entity type.', 'error'); btn.disabled = false; btn.innerHTML = '<i class="fa fa-check" style="font-size:11px;"></i> Save'; return; }
    // Clear any prior inline error each time we attempt a save.
    var errEl = document.getElementById('serrAddEntityError');
    if (errEl) { errEl.style.display = 'none'; errEl.textContent = ''; }
    if (input) { input.style.borderColor = '#e5e7eb'; input.style.background = '#fafafa'; input.style.boxShadow = 'none'; }

    $.ajax({
        url: url,
        method: 'POST',
        data: { name: name, _token: "{{ csrf_token() }}" },
        success: function(res) {
            if (!res || !res.success || !res.item) {
                showSettingsToast((res && res.message) || 'Failed to add.', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-check" style="font-size:11px;"></i> Save';
                return;
            }
            // Refresh local cache so the picker list sees the entity next time.
            var list = _serrEntities[ctx.kind] || [];
            if (!list.find(function(x) { return x.id === res.item.id; })) list.push(res.item);
            list.sort(function(a, b) { return a.name.localeCompare(b.name); });
            _serrEntities[ctx.kind] = list;

            if (res.reused) {
                // Entity already exists in DB — surface inline error under the input and keep
                // the popup open so the user can dismiss + choose from the dropdown instead.
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-check" style="font-size:11px;"></i> Save';
                if (errEl) {
                    errEl.textContent = 'This ' + ctx.kind + ' already exists in your list — please choose it from the dropdown.';
                    errEl.style.display = 'block';
                }
                if (input) {
                    input.style.borderColor = '#fca5a5';
                    input.style.background = '#fef2f2';
                    input.focus();
                    input.select && input.select();
                }
                return;
            }

            // Fresh entity created — select it back in the originating picker and close popup.
            _serrPickerSelect(ctx.slotId, res.item.name);
            _serrCloseAddPopup();
            showSettingsToast('Added ' + ctx.kind + ': ' + res.item.name, 'success');
        },
        error: function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to add.';
            if (errEl) { errEl.textContent = msg; errEl.style.display = 'block'; }
            if (input) {
                input.style.borderColor = '#fca5a5';
                input.style.background = '#fef2f2';
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-check" style="font-size:11px;"></i> Save';
        }
    });
}

// Read value from picker by slotId
function _serrPickerValue(slotId) {
    var hidden = document.querySelector('input[data-picker-value="' + (window.CSS && CSS.escape ? CSS.escape(slotId) : slotId) + '"]');
    return hidden ? hidden.value.trim() : '';
}

// Which Excel column header keywords each error type targets — used to find the
// editable cell in the preview row data.
var _salesErrFixerColMap = {
    'missing_customer': ['customer'],
    'missing_product': ['product'],
    'missing_supplier': ['supplier'],
    'missing_date': ['date'],
    'invalid_date': ['date'],
    'missing_quantity': ['quantity', 'qty'],
    'invalid_quantity': ['quantity', 'qty'],
    'missing_price': ['unit price', 'unitprice', 'price', 'rate'],
    'invalid_price': ['unit price', 'unitprice', 'price', 'rate'],
    'invalid_total': ['total', 'amount', 'sub_total', 'subtotal'],
    'missing_invoice': ['invoice']
};

function _salesErrFixerFindCol(headers, errType) {
    var kws = _salesErrFixerColMap[errType] || [];
    for (var ci = 0; ci < headers.length; ci++) {
        var hl = (headers[ci] || '').toLowerCase();
        for (var k = 0; k < kws.length; k++) {
            // For price, exclude headers containing 'sale' or 'total'
            if ((errType === 'missing_price' || errType === 'invalid_price') &&
                (hl.indexOf('sale') !== -1 || hl.indexOf('total') !== -1)) continue;
            if (hl.indexOf(kws[k]) !== -1) return ci;
        }
    }
    return -1;
}

// Short helper sentence under each section title — tells user what they can do for THIS error type.
// $columnMissing = true when the file has NO column for this error type (e.g. Supplier never mapped):
// in that case there is no cell to write into, so the user must map the column via "Re-map columns".
function _salesErrFixerHelperText(errType, columnMissing) {
    // Entity errors with no mapped column → guide to Re-map (per-row picker needs a target cell).
    if (columnMissing) {
        var ek = _serrEntityTypeFor(errType);
        if (ek) {
            return 'no ' + ek + ' column is mapped — click "Re-map columns" to map it, then pick a ' + ek + ' for each row.';
        }
    }
    var map = {
        'missing_price':     'enter a unit price below or fix each row individually.',
        'invalid_price':     'enter a positive unit price below or fix each row individually.',
        'invalid_total':     'enter a valid total (numbers only) below or fix each row individually.',
        'missing_quantity':  'enter a quantity below or fix each row individually.',
        'invalid_quantity':  'enter a positive quantity below or fix each row individually.',
        'missing_date':      'pick a date below or fix each row individually.',
        'invalid_date':      'pick a valid date below or fix each row individually.',
        'missing_invoice':   'enter an invoice number below or fix each row individually.',
        'missing_customer':  'select a customer below or fix each row individually.',
        'missing_supplier':  'select a supplier below or fix each row individually.',
        'missing_product':   'this product is missing — these rows cannot be auto-fixed; you can remove them.',
        'summary_row':       'these summary/total rows will be skipped automatically.',
        'duplicate':         'these rows already exist in the database and will be skipped automatically — no action needed.'
    };
    return map[errType] || 'fix each row individually.';
}

function openErrorFixerModal() {
    if (!_previewData) return;
    _salesErrFixerActiveTab = null; // reset; renderer will pick the first available group
    // Pre-warm entity lists so dropdowns are instant
    _serrLoadEntities('customer');
    _serrLoadEntities('supplier');
    _serrLoadEntities('product');
    document.getElementById('salesErrorFixerModal').style.display = 'flex';
    renderErrorFixerModal();
}

function switchErrFixerTab(et) {
    _salesErrFixerActiveTab = et;
    renderErrorFixerModal();
}

function closeErrorFixerModal() {
    document.getElementById('salesErrorFixerModal').style.display = 'none';
    // After closing, refresh the preview so the user sees up-to-date row states + summary.
    if (_previewData) renderPreview(_previewData, true);
}

function renderErrorFixerModal() {
    var res = _previewData;
    if (!res) return;
    // Close any open entity picker; its panel lives in <body> so it survives body.innerHTML refresh.
    _serrPickerClose();
    var body = document.getElementById('salesErrFixerBody');
    var status = document.getElementById('salesErrFixerStatus');
    var doneBtn = document.getElementById('salesErrFixerDoneBtn');
    var subtitle = document.getElementById('salesErrFixerSubtitle');
    var countBadge = document.getElementById('salesErrFixerCountBadge');
    var headers = res.headers || [];

    // Group invalid rows by error type
    var groups = {};
    res.rows.forEach(function(row, idx) {
        if (!row || row.status === 'valid') return;
        var ets = row.error_types || [];
        if (!ets.length) return;
        ets.forEach(function(et) {
            if (!groups[et]) groups[et] = [];
            groups[et].push(idx);
        });
    });

    var totalGroups = Object.keys(groups).length;
    var totalInvalid = res.invalid || 0;
    // Count "fixed" rows = rows that had edits applied via _editedRowsMap during this Fix-Errors session.
    var fixedCount = 0;
    if (typeof _editedRowsMap === 'object' && _editedRowsMap) {
        for (var k in _editedRowsMap) {
            if (Object.prototype.hasOwnProperty.call(_editedRowsMap, k) && _editedRowsMap[k] && Object.keys(_editedRowsMap[k]).length > 0) {
                // Only count rows that are now valid (i.e. no longer in the groups map)
                var ridx = parseInt(k, 10);
                if (res.rows[ridx] && res.rows[ridx].status === 'valid') fixedCount++;
            }
        }
    }

    if (totalGroups === 0) {
        // All clean
        body.innerHTML = '' +
            '<div style="text-align:center;padding:50px 20px;">' +
                '<div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#dcfce7,#bbf7d0);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">' +
                    '<i class="fa fa-check" style="font-size:30px;color:#16a34a;"></i>' +
                '</div>' +
                '<div style="font-size:18px;font-weight:800;color:#0f172a;margin-bottom:6px;">All Errors Fixed!</div>' +
                '<div style="font-size:13px;color:#64748b;">All rows are ready to import. You can close this and continue.</div>' +
            '</div>';
        status.innerHTML = '<span style="display:inline-flex;align-items:center;gap:6px;color:#16a34a;font-weight:600;"><span style="width:7px;height:7px;border-radius:50%;background:#16a34a;display:inline-block;"></span>All ' + fixedCount + ' rows fixed</span>';
        doneBtn.style.display = 'inline-flex';
        var saveCont = document.getElementById('salesErrFixerSaveContinueBtn');
        if (saveCont) saveCont.style.display = 'none';
        // Header stays static ("Resolve issues row-by-row..."); count badge collapses to 0
        if (countBadge) {
            countBadge.textContent = '0 rows';
            countBadge.style.display = 'none';
        }
        return;
    }

    doneBtn.style.display = 'none';
    var saveCont = document.getElementById('salesErrFixerSaveContinueBtn');
    if (saveCont) saveCont.style.display = 'inline-flex';
    // Update count badge — "136 ROWS" pill (matches reference)
    if (countBadge) {
        countBadge.style.display = 'inline-flex';
        countBadge.textContent = totalInvalid.toLocaleString() + ' row' + (totalInvalid !== 1 ? 's' : '');
    }
    // Footer status — red dot "N rows still need attention" + green dot "M fixed" (matches reference)
    var statusParts = [];
    statusParts.push('<span style="display:inline-flex;align-items:center;gap:6px;color:#dc2626;font-weight:500;"><span style="width:7px;height:7px;border-radius:50%;background:#dc2626;display:inline-block;"></span><strong style="color:#dc2626;font-weight:700;">' + totalInvalid.toLocaleString() + '</strong> <span style="color:#64748b;font-weight:500;">rows still need attention</span></span>');
    if (fixedCount > 0) {
        statusParts.push('<span style="display:inline-flex;align-items:center;gap:6px;color:#16a34a;font-weight:500;"><span style="width:7px;height:7px;border-radius:50%;background:#16a34a;display:inline-block;"></span><strong style="color:#16a34a;font-weight:700;">' + fixedCount + '</strong>&nbsp;<span style="color:#16a34a;font-weight:500;">fixed</span></span>');
    }
    status.innerHTML = statusParts.join('');

    // Ordered list of present error types
    var ordered = _salesErrFixerErrorOrder.filter(function(et) { return groups[et]; });
    Object.keys(groups).forEach(function(et) { if (ordered.indexOf(et) === -1) ordered.push(et); });

    // Determine active tab — keep current if still valid, else pick first
    var activeTab = _salesErrFixerActiveTab;
    if (!activeTab || ordered.indexOf(activeTab) === -1) {
        activeTab = ordered[0];
        _salesErrFixerActiveTab = activeTab;
    }

    var html = '';

    // ── Tabs strip ──
    html += '<div style="display:flex;align-items:center;gap:2px;padding:0 24px;border-bottom:1.5px solid #f1f5f9;background:#ffffff;overflow-x:auto;">';
    ordered.forEach(function(et) {
        var label = _errorTypeLabels[et] || et;
        var icon = getErrorIcon(et);
        var count = groups[et].length;
        var isActive = (et === activeTab);
        html += '<button type="button" onclick="switchErrFixerTab(\'' + escapeHtml(et) + '\')" style="position:relative;display:inline-flex;align-items:center;gap:8px;height:46px;padding:0 16px;background:transparent;border:none;border-bottom:2.5px solid ' + (isActive ? '#f97316' : 'transparent') + ';color:' + (isActive ? '#ea580c' : '#64748b') + ';font-size:13px;font-weight:' + (isActive ? '700' : '600') + ';cursor:pointer;outline:none;transition:color 0.15s,border-color 0.15s;white-space:nowrap;flex-shrink:0;" onmouseover="if(!this.dataset.active){this.style.color=\'#374151\'}" onmouseout="if(!this.dataset.active){this.style.color=\'#64748b\'}"' + (isActive ? ' data-active="1"' : '') + '>';
        html +=   '<i class="fa fa-' + icon + '" style="font-size:12px;color:' + (isActive ? '#ea580c' : '#94a3b8') + ';"></i>';
        html +=   '<span>' + escapeHtml(label) + '</span>';
        html +=   '<span style="display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 6px;background:' + (isActive ? '#ffedd5' : '#f1f5f9') + ';color:' + (isActive ? '#ea580c' : '#64748b') + ';font-size:11px;font-weight:800;border-radius:2px;line-height:1;">' + count + '</span>';
        html += '</button>';
    });
    html += '</div>';

    // ── Active section body ──
    var et = activeTab;
    var rowIdxs = groups[et];
    var label = _errorTypeLabels[et] || et;
    var icon = getErrorIcon(et);
    var targetCol = _salesErrFixerFindCol(headers, et);
    // summary_row + duplicate are NOT fixable via input — they're info-only (auto-skipped on import).
    var canBulk = (targetCol !== -1) && (et !== 'summary_row') && (et !== 'duplicate');

    // Body padding wrap + vertical stack of 2 independent cards (matches reference exactly)
    html += '<div style="padding:20px 24px;background:#ffffff;display:flex;flex-direction:column;gap:14px;">';

    // ── Card 1: Apply-to-all header card (standalone) ──
    html += '<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:18px 22px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">';
    html +=   '<div style="display:flex;align-items:center;gap:14px;flex:1;min-width:260px;">';
    html +=     '<div style="width:36px;height:36px;border-radius:9px;background:#ffedd5;display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    html +=       '<i class="fa fa-' + icon + '" style="font-size:15px;color:#ea580c;"></i>';
    html +=     '</div>';
    html +=     '<div style="min-width:0;">';
    html +=       '<div style="font-size:15px;font-weight:700;color:#0f172a;letter-spacing:-0.1px;line-height:1.3;">' + escapeHtml(label) + '</div>';
    html +=       '<div style="font-size:13px;color:#64748b;margin-top:3px;font-weight:500;line-height:1.4;">' + rowIdxs.length + ' rows affected · ' + _salesErrFixerHelperText(et, (targetCol === -1)) + '</div>';
    html +=     '</div>';
    html +=   '</div>';

    if (canBulk) {
        var bulkEntityKind = _serrEntityTypeFor(et);
        html += '<div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">';
        if (bulkEntityKind) {
            // Searchable entity dropdown (customer/supplier) with quick-add support
            html += '<div style="width:220px;">' + _serrRenderEntityPicker('bulk__' + et, bulkEntityKind, '', { placeholder: 'Select ' + bulkEntityKind + ' to apply...' }) + '</div>';
        } else {
            var bulkIsDate = (et === 'missing_date' || et === 'invalid_date');
            var bulkIsNumber = (et === 'missing_quantity' || et === 'invalid_quantity' || et === 'missing_price' || et === 'invalid_price' || et === 'invalid_total');
            var bulkType, bulkAttrs, bulkPh;
            if (bulkIsDate) {
                bulkType = 'date'; bulkAttrs = ''; bulkPh = '';
            } else if (bulkIsNumber) {
                // Strict guard: digits + ONE dot only. text + inputmode=decimal avoids mobile sci-notation keys.
                bulkType = 'text';
                bulkPh = '0.00';
                bulkAttrs = ' inputmode="decimal" onkeydown="window._impNumGuard.keydown.call(this,event)" oninput="window._impNumGuard.input.call(this,event)" onpaste="window._impNumGuard.paste.call(this,event)"';
            } else {
                bulkType = 'text'; bulkAttrs = ''; bulkPh = 'Apply value to all...';
            }
            // Number inputs get a "$"/currency-style prefix; date inputs stay native
            if (bulkIsNumber) {
                html += '<div style="display:flex;align-items:center;height:38px;border-radius:8px;border:1px solid #e5e7eb;background:#ffffff;overflow:hidden;width:140px;">';
                html += '<span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:100%;color:#94a3b8;font-size:14px;font-weight:500;flex-shrink:0;">£</span>';
                html += '<input type="' + bulkType + '"' + bulkAttrs + ' placeholder="' + bulkPh + '" id="serrBulkInput__' + escapeHtml(et) + '" style="flex:1;height:100%;padding:0 12px 0 0;border:none;outline:none;font-size:13.5px;font-weight:500;color:#0f172a;background:transparent;min-width:0;width:100%;">';
                html += '</div>';
            } else {
                html += '<input type="' + bulkType + '"' + bulkAttrs + ' placeholder="' + bulkPh + '" id="serrBulkInput__' + escapeHtml(et) + '" style="height:38px;padding:0 14px;border-radius:8px;border:1px solid #e5e7eb;background:#ffffff;font-size:13.5px;font-weight:500;color:#0f172a;width:180px;outline:none;transition:border-color 0.15s,box-shadow 0.15s;" onfocus="this.style.borderColor=\'#f97316\';this.style.boxShadow=\'0 0 0 3px rgba(249,115,22,0.08)\'" onblur="this.style.borderColor=\'#e5e7eb\';this.style.boxShadow=\'none\'">';
            }
        }
        html +=   '<button type="button" onclick="errFixerBulkApply(\'' + escapeHtml(et) + '\')" style="height:38px;padding:0 18px;border-radius:8px;border:none;background:#f97316;color:#fff;font-size:13.5px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;outline:none;box-shadow:0 1px 3px rgba(249,115,22,0.25);transition:background 0.15s;white-space:nowrap;" onmouseover="this.style.background=\'#ea580c\'" onmouseout="this.style.background=\'#f97316\'">';
        html +=     '<i class="fa fa-check" style="font-size:11px;"></i>';
        html +=     '<span>Apply to all</span>';
        html +=   '</button>';
        html += '</div>';
    }

    html += '</div>'; // /Card 1 (Apply-to-all header)

    // ── Card 2: Table card (standalone, sits below Card 1) ──
    html += '<div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">';
    html += '<div style="max-height:360px;overflow-y:auto;">';
    html += '<table style="width:100%;border-collapse:collapse;font-size:12px;background:#ffffff;">';
    var thStyle = 'padding:12px 14px;text-align:left;font-size:11px;font-weight:700;color:#0f172a;text-transform:uppercase;letter-spacing:0.05em;border-bottom:1px solid #e5e7eb;white-space:nowrap;background:#fafafa;';
    // SUGGESTED column hidden — user wants compact 3-column layout (Row # · Product Name · Unit Price)
    var showSuggested = false;
    html += '<thead style="position:sticky;top:0;background:#fafafa;z-index:1;"><tr>';
    html += '<th style="' + thStyle + '">Row #</th>';
    var ctxCols = _salesErrFixerContextCols(headers, et);
    ctxCols.forEach(function(ci) {
        html += '<th style="' + thStyle + '">' + escapeHtml(headers[ci] || '') + '</th>';
    });
    if (showSuggested) {
        html += '<th style="' + thStyle + '">Suggested ($)</th>';
    }
    if (targetCol !== -1 && ctxCols.indexOf(targetCol) === -1) {
        html += '<th style="' + thStyle + '">' + escapeHtml(headers[targetCol] || '') + '</th>';
    } else if (targetCol === -1) {
        html += '<th style="' + thStyle + '">Action</th>';
    }
    html += '<th style="width:48px;padding:10px 8px;border-bottom:1px solid #e5e7eb;background:#fafafa;"></th>';
    html += '</tr></thead><tbody>';

    rowIdxs.forEach(function(rowIdx) {
        var row = res.rows[rowIdx];
        html += '<tr data-row-idx="' + rowIdx + '" data-error-type="' + escapeHtml(et) + '" style="border-bottom:1px solid #f1f5f9;background:#ffffff;transition:background 0.15s;">';
        html += '<td style="padding:12px 14px;color:#ea580c;font-weight:700;font-size:13px;background:inherit;">' + (rowIdx + 1) + '</td>';
        ctxCols.forEach(function(ci) {
            var v = (row.raw && row.raw[ci] != null) ? String(row.raw[ci]) : '';
            if (v === '' || v === '-') v = '—';
            // Invoice column rendered in monospace for that "INV-1042" code feel
            var headerLc = (headers[ci] || '').toLowerCase();
            var isInvoice = headerLc.indexOf('invoice') !== -1 && headerLc.indexOf('no') !== -1;
            var cellFontFamily = isInvoice ? "'SFMono-Regular','Menlo','Consolas',monospace" : 'inherit';
            var cellFontSize = isInvoice ? '12px' : '13px';
            var cellColor = isInvoice ? '#475569' : '#0f172a';
            html += '<td style="padding:12px 14px;color:' + cellColor + ';font-size:' + cellFontSize + ';font-family:' + cellFontFamily + ';max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;background:inherit;" title="' + escapeHtml(v) + '">' + escapeHtml(v) + '</td>';
        });
        // SUGGESTED column — peach pill with suggested value + grey "last 30 days" caption.
        // Backend doesn't currently provide price suggestions; placeholder '—' for now until wired up.
        if (showSuggested) {
            var suggested = (row.suggested_price != null) ? row.suggested_price : null;
            html += '<td style="padding:12px 14px;background:inherit;white-space:nowrap;">';
            if (suggested != null && suggested !== '') {
                html += '<span style="display:inline-flex;align-items:center;height:22px;padding:0 9px;background:#fff7ed;color:#ea580c;font-size:12px;font-weight:700;border-radius:6px;">$' + Number(suggested).toFixed(2) + '</span>';
                html += '<span style="font-size:11.5px;color:#94a3b8;margin-left:8px;font-weight:500;">last 30 days</span>';
            } else {
                html += '<span style="color:#cbd5e1;font-size:13px;">—</span>';
            }
            html += '</td>';
        }
        if (targetCol !== -1 && ctxCols.indexOf(targetCol) === -1) {
            var currentVal = (row.raw && row.raw[targetCol] != null) ? String(row.raw[targetCol]) : '';
            if (currentVal === '-') currentVal = '';
            var entityKind = _serrEntityTypeFor(et);
            if (entityKind) {
                // Searchable customer/supplier dropdown with inline quick-add
                html += '<td style="padding:10px 14px;background:inherit;">';
                html += _serrRenderEntityPicker('row__' + rowIdx + '__' + et, entityKind, currentVal, { placeholder: 'Select ' + entityKind + '...' });
                html += '</td>';
            } else {
                var isDateField = (et === 'missing_date' || et === 'invalid_date');
                var isNumberField = (et === 'missing_quantity' || et === 'invalid_quantity' || et === 'missing_price' || et === 'invalid_price');
                var inputType, attrs, placeholder, dateInitVal = '';
                if (isDateField) {
                    inputType = 'date';
                    placeholder = '';
                    // Native date input needs YYYY-MM-DD format
                    dateInitVal = _serrToISODate(currentVal);
                    attrs = '';
                } else if (isNumberField) {
                    // Strict numeric input — digits + ONE dot only. text + inputmode=decimal avoids
                    // mobile keyboard scientific-notation keys; window._impNumGuard handles the rest.
                    inputType = 'text';
                    placeholder = '0.00';
                    attrs = ' inputmode="decimal" onkeydown="window._impNumGuard.keydown.call(this,event)" oninput="window._impNumGuard.input.call(this,event)" onpaste="window._impNumGuard.paste.call(this,event)"';
                } else {
                    inputType = 'text';
                    placeholder = 'Enter value';
                    attrs = '';
                }
                var displayVal = isDateField ? dateInitVal : currentVal;
                var isPriceField = (et === 'missing_price' || et === 'invalid_price');
                html += '<td style="padding:10px 14px;background:inherit;">';
                if (isPriceField) {
                    // Wrap in a flex container so the "$" prefix sits inside the field outline
                    html += '<div class="serr-price-wrap" style="display:flex;align-items:center;width:100%;max-width:220px;height:38px;border-radius:8px;border:1px solid #e5e7eb;background:#ffffff;overflow:hidden;transition:border-color 0.15s,box-shadow 0.15s;">';
                    html +=   '<span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:100%;color:#94a3b8;font-size:13.5px;font-weight:500;flex-shrink:0;">£</span>';
                    html +=   '<input type="' + inputType + '"' + attrs + ' value="' + escapeHtml(displayVal) + '" placeholder="' + placeholder + '" data-fix-input="' + rowIdx + '__' + escapeHtml(et) + '" style="flex:1;height:100%;padding:0 12px 0 0;border:none;outline:none;font-size:13.5px;font-weight:500;color:#0f172a;background:transparent;min-width:0;width:100%;" onfocus="var p=this.parentElement;p.style.borderColor=\'#f97316\';p.style.boxShadow=\'0 0 0 3px rgba(249,115,22,0.08)\'" onblur="var p=this.parentElement;p.style.borderColor=\'#e5e7eb\';p.style.boxShadow=\'none\'">';
                    html += '</div>';
                } else {
                    html += '<input type="' + inputType + '"' + attrs + ' value="' + escapeHtml(displayVal) + '" placeholder="' + placeholder + '" data-fix-input="' + rowIdx + '__' + escapeHtml(et) + '" style="width:100%;max-width:220px;height:38px;padding:0 12px;border-radius:8px;border:1px solid #e5e7eb;background:#ffffff;font-size:13.5px;font-weight:500;color:#0f172a;outline:none;transition:border-color 0.15s,box-shadow 0.15s;" onfocus="this.style.borderColor=\'#f97316\';this.style.boxShadow=\'0 0 0 3px rgba(249,115,22,0.08)\'" onblur="this.style.borderColor=\'#e5e7eb\';this.style.boxShadow=\'none\'">';
                }
                html += '</td>';
            }
        } else if (targetCol === -1) {
            // No target column in the file for this error type.
            // For entity errors (customer/supplier/product) this means that column was never mapped —
            // guide the user to map it via "Re-map columns" so the per-row picker can appear.
            var _entKind = _serrEntityTypeFor(et);
            var _skipMsg;
            if (et === 'duplicate') {
                _skipMsg = 'Already in the database — will be skipped.';
            } else if (_entKind) {
                _skipMsg = 'No ' + _entKind + ' column mapped — use "Re-map columns" to map it, then pick a ' + _entKind + ' here.';
            } else {
                _skipMsg = 'This row will be skipped automatically.';
            }
            html += '<td style="padding:12px 14px;color:#94a3b8;font-size:12px;font-style:italic;background:inherit;">' + _skipMsg + '</td>';
        }
        html += '<td style="padding:8px;text-align:center;background:inherit;">';
        if (targetCol !== -1) {
            // Subtle grey "ready to save" check by default; turns green on hover (matches reference)
            html += '<button type="button" onclick="errFixerSaveRow(' + rowIdx + ',\'' + escapeHtml(et) + '\')" title="Save this row" style="width:32px;height:32px;border-radius:8px;border:1px solid #e5e7eb;background:#ffffff;color:#94a3b8;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;outline:none;transition:background 0.15s,color 0.15s,border-color 0.15s,box-shadow 0.15s;" onmouseover="this.style.background=\'#16a34a\';this.style.color=\'#ffffff\';this.style.borderColor=\'#16a34a\';this.style.boxShadow=\'0 1px 3px rgba(22,163,74,0.25)\'" onmouseout="this.style.background=\'#ffffff\';this.style.color=\'#94a3b8\';this.style.borderColor=\'#e5e7eb\';this.style.boxShadow=\'none\'">';
            html +=   '<i class="fa fa-check" style="font-size:11px;"></i>';
            html += '</button>';
        } else {
            html += '<button type="button" onclick="errFixerRemoveRow(' + rowIdx + ')" title="Remove this row from import" style="width:32px;height:32px;border-radius:8px;border:none;background:#dc2626;color:#fff;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;outline:none;transition:background 0.15s;box-shadow:0 1px 3px rgba(220,38,38,0.25);" onmouseover="this.style.background=\'#b91c1c\'" onmouseout="this.style.background=\'#dc2626\'">';
            html +=   '<i class="fa fa-trash-o" style="font-size:11px;"></i>';
            html += '</button>';
        }
        html += '</td>';
        html += '</tr>';
    });

    html += '</tbody></table></div>'; // closes tbody, table, scroll viewport
    html += '</div>'; // /Card 2 (table card)
    html += '</div>'; // /padding wrap (outer flex column)

    body.innerHTML = html;
}

// Pick the single contextual column (Product) to identify each row.
// Reference design uses a compact 3-column table: Row # · Product Name · Unit Price.
function _salesErrFixerContextCols(headers, errType) {
    var picks = [];
    var targetCol = _salesErrFixerFindCol(headers, errType);
    // Only show Product as the context column
    for (var ci = 0; ci < headers.length; ci++) {
        if (ci === targetCol) continue;
        var hl = (headers[ci] || '').toLowerCase();
        if (hl.indexOf('product') !== -1) {
            picks.push(ci);
            break;
        }
    }
    return picks;
}

// Validate a value for the given error type. Returns { ok: bool, msg: string }.
function _salesErrFixerValidate(value, errType) {
    var v = (value || '').toString().trim();
    if (errType === 'missing_quantity' || errType === 'invalid_quantity') {
        if (!v) return { ok:false, msg:'Quantity is required.' };
        var n = parseFloat(v);
        if (isNaN(n)) return { ok:false, msg:'Quantity must be a number.' };
        if (n <= 0) return { ok:false, msg:'Quantity must be greater than 0.' };
        return { ok:true, msg:'' };
    }
    if (errType === 'missing_price' || errType === 'invalid_price') {
        if (!v) return { ok:false, msg:'Price is required.' };
        var p = parseFloat(v);
        if (isNaN(p)) return { ok:false, msg:'Price must be a number.' };
        if (p <= 0) return { ok:false, msg:'Price must be greater than 0.' };
        return { ok:true, msg:'' };
    }
    if (errType === 'missing_date' || errType === 'invalid_date') {
        if (!v) return { ok:false, msg:'Date is required.' };
        if (!/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}/.test(v) && !/^\d{1,2}[-\/]\d{1,2}[-\/]\d{4}/.test(v))
            return { ok:false, msg:'Date must be DD/MM/YYYY or YYYY-MM-DD.' };
        return { ok:true, msg:'' };
    }
    // Generic missing_* / others
    if (!v) return { ok:false, msg:'Value is required.' };
    return { ok:true, msg:'' };
}

// Apply a fix to a single row+errorType. Returns true on success.
function _salesErrFixerApplyOne(rowIdx, errType, newValue) {
    var res = _previewData;
    if (!res || !res.rows[rowIdx]) return false;
    var row = res.rows[rowIdx];
    var targetCol = _salesErrFixerFindCol(res.headers || [], errType);
    if (targetCol === -1) return false;

    var v = (newValue || '').toString().trim();
    // Normalize values before validating/saving:
    // - Native date picker gives YYYY-MM-DD → convert to DD/MM/YYYY for the importer
    // - Quantity / Price negative values → take absolute (defensive, input already blocks `-`)
    if ((errType === 'missing_date' || errType === 'invalid_date') && /^\d{4}-\d{1,2}-\d{1,2}$/.test(v)) {
        v = _serrFromISODate(v);
    }
    if (errType === 'missing_quantity' || errType === 'invalid_quantity' ||
        errType === 'missing_price'    || errType === 'invalid_price') {
        var n = parseFloat(v);
        if (!isNaN(n) && n < 0) v = String(Math.abs(n));
    }
    var check = _salesErrFixerValidate(v, errType);
    if (!check.ok) {
        showSettingsToast(check.msg, 'error');
        return false;
    }

    row.raw[targetCol] = v;
    if (!_editedRowsMap[rowIdx]) _editedRowsMap[rowIdx] = {};
    _editedRowsMap[rowIdx][targetCol] = v;

    // Remove this error type from the row
    row.error_types = (row.error_types || []).filter(function(et) { return et !== errType; });

    // If row was missing both qty and "invalid_qty", clearing one also clears the other (same cell)
    if (errType === 'missing_quantity') row.error_types = row.error_types.filter(function(et) { return et !== 'invalid_quantity'; });
    if (errType === 'invalid_quantity') row.error_types = row.error_types.filter(function(et) { return et !== 'missing_quantity'; });
    if (errType === 'missing_price') row.error_types = row.error_types.filter(function(et) { return et !== 'invalid_price'; });
    if (errType === 'invalid_price') row.error_types = row.error_types.filter(function(et) { return et !== 'missing_price'; });
    if (errType === 'missing_date') row.error_types = row.error_types.filter(function(et) { return et !== 'invalid_date'; });
    if (errType === 'invalid_date') row.error_types = row.error_types.filter(function(et) { return et !== 'missing_date'; });

    if (row.error_types.length === 0) {
        row.status = 'valid';
        _selectedRows[rowIdx] = true;
    }
    return true;
}

function errFixerSaveRow(rowIdx, errType) {
    var value;
    if (_serrEntityTypeFor(errType)) {
        // Read from custom picker
        value = _serrPickerValue('row__' + rowIdx + '__' + errType);
    } else {
        var input = document.querySelector('input[data-fix-input="' + rowIdx + '__' + errType + '"]');
        if (!input) return;
        value = input.value;
    }
    if (!_salesErrFixerApplyOne(rowIdx, errType, value)) return;
    _salesErrFixerRecompute();
    showSettingsToast('Row ' + (rowIdx + 1) + ' fixed.', 'success');
    renderErrorFixerModal();
}

function errFixerBulkApply(errType) {
    var value;
    if (_serrEntityTypeFor(errType)) {
        value = _serrPickerValue('bulk__' + errType);
    } else {
        var input = document.getElementById('serrBulkInput__' + errType);
        if (!input) { showSettingsToast('Please enter a value.', 'error'); return; }
        value = (input.value || '').trim();
    }
    var check = _salesErrFixerValidate(value, errType);
    if (!check.ok) { showSettingsToast(check.msg, 'error'); return; }

    var res = _previewData;
    if (!res) return;
    var fixed = 0;
    res.rows.forEach(function(row, idx) {
        if (!row.error_types || row.error_types.indexOf(errType) === -1) return;
        if (_salesErrFixerApplyOne(idx, errType, value)) fixed++;
    });
    if (fixed === 0) { showSettingsToast('No rows updated.', 'error'); return; }
    _salesErrFixerRecompute();
    showSettingsToast('Applied to ' + fixed + ' row' + (fixed !== 1 ? 's' : '') + '.', 'success');
    renderErrorFixerModal();
}

function errFixerRemoveRow(rowIdx) {
    var res = _previewData;
    if (!res || !res.rows[rowIdx]) return;
    // "Remove" = user acknowledges the row will be skipped. Clear errors from view but keep row invalid.
    _selectedRows[rowIdx] = false;
    res.rows[rowIdx].error_types = [];
    res.rows[rowIdx].status = 'invalid'; // stays invalid so it's never imported
    res.rows[rowIdx]._fixer_dismissed = true;
    _salesErrFixerRecompute();
    showSettingsToast('Row ' + (rowIdx + 1) + ' will be skipped.', 'success');
    renderErrorFixerModal();
}

function _salesErrFixerRecompute() {
    var res = _previewData;
    if (!res) return;
    var valid = 0, invalid = 0, newSummary = {};
    res.rows.forEach(function(r) {
        if (r.status === 'valid') valid++; else invalid++;
        if (r.error_types) r.error_types.forEach(function(et) {
            newSummary[et] = (newSummary[et] || 0) + 1;
        });
    });
    res.valid = valid;
    res.invalid = invalid;
    res.error_summary = newSummary;
}

function togglePerPageDropdown(e) {
    e.stopPropagation();
    var dd = document.getElementById('perPageDropdown');
    dd.classList.toggle('open');
    // Close on outside click
    if (dd.classList.contains('open')) {
        setTimeout(function(){
            document.addEventListener('click', closePerPageDropdown);
        }, 10);
    }
}
function closePerPageDropdown() {
    var dd = document.getElementById('perPageDropdown');
    if (dd) dd.classList.remove('open');
    document.removeEventListener('click', closePerPageDropdown);
}
function selectPerPage(n, e) {
    e.stopPropagation();
    _previewPerPage = n;
    _previewPage = 1;
    closePerPageDropdown();
    renderPreviewPage();
}

function goImportPage(page) {
    _previewPage = page;
    renderPreviewPage();
    // Scroll to top of preview table
    document.getElementById('importPreviewTable').scrollTop = 0;
}

function getVisiblePages(current, total) {
    if (total <= 7) {
        var arr = [];
        for (var i = 1; i <= total; i++) arr.push(i);
        return arr;
    }
    var pages = [];
    pages.push(1);
    if (current > 3) pages.push('...');
    var start = Math.max(2, current - 1);
    var end = Math.min(total - 1, current + 1);
    for (var i = start; i <= end; i++) pages.push(i);
    if (current < total - 2) pages.push('...');
    pages.push(total);
    return pages;
}

function executeImport() {
    var file = _importFile[_importType];
    if (!file) {
        showSettingsToast('Please select a file first.', 'error');
        return;
    }

    // Collect selected row indices (0-based)
    var selected = [];
    for (var k in _selectedRows) {
        if (_selectedRows[k]) selected.push(parseInt(k));
    }
    if (selected.length === 0) {
        showSettingsToast('Please select at least one row to import.', 'error');
        return;
    }

    var btn = document.getElementById('importConfirmBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Importing...';

    // Mark this run as not-yet-cancelled, then lock the page. The cancel button
    // shown by _setImportBusy('import') will call _cancelRunningImport() which
    // aborts the AJAX request. The backend wraps the import in a DB transaction
    // and only DB::commit()s at the very end — so an aborted request leaves the
    // transaction uncommitted and the connection drop rolls it back automatically.
    // Net result: zero DB inserts when the user cancels.
    _importCancelled = false;
    _setImportBusy(true, 'import');

    // Convert _editedRowsMap (keyed by column index) to a row-key map the backend can apply.
    // Column index → system-field key when mapping is in use, else → normalized header.
    var editedForServer = {};
    if (_editedRowsMap && _previewData && _previewData.headers) {
        var headers = _previewData.headers;
        var sysFieldKeyByLabel = {};
        if (_mappingMeta && _mappingMeta.system_fields) {
            _mappingMeta.system_fields.forEach(function(sf) {
                sysFieldKeyByLabel[sf.label.toLowerCase().trim()] = sf.key;
            });
        }
        var colKeyForIndex = function(ci) {
            var hdr = (headers[ci] || '').toLowerCase().trim();
            if (sysFieldKeyByLabel[hdr]) return sysFieldKeyByLabel[hdr];
            // Fallback: same normalisation as backend's header → key
            return hdr.replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        };
        Object.keys(_editedRowsMap).forEach(function(idx) {
            var src = _editedRowsMap[idx];
            var dst = {};
            Object.keys(src).forEach(function(ci) {
                var key = colKeyForIndex(parseInt(ci, 10));
                if (key) dst[key] = src[ci];
            });
            if (Object.keys(dst).length) editedForServer[idx] = dst;
        });
    }

    // ── Chunked import to dodge Apache's 504 Gateway Timeout (default 60s ProxyTimeout) ──
    // Strategy: split the work into N small chunks. CRITICAL: each chunk must contain ALL rows
    // of the same invoice — never split one invoice across two chunks. Otherwise the backend's
    // group-by-invoice logic runs separately per chunk and produces duplicate invoices.
    //
    // Algorithm:
    //   1. Group selected preview-row indices by their invoice key (invoice_no, or
    //      customer+date when invoice_no is empty — matches backend's grouping logic).
    //   2. Fill chunks invoice-by-invoice, capping each chunk at ~CHUNK_SIZE rows
    //      (allowing slight overflow so one invoice is never split).
    var CHUNK_SIZE = 200;

    // Helper — find a header's column index (case-insensitive substring match)
    function _findHeaderIdx(headers, keys) {
        for (var hi = 0; hi < headers.length; hi++) {
            var h = String(headers[hi] || '').toLowerCase().trim();
            for (var ki = 0; ki < keys.length; ki++) {
                if (h === keys[ki] || h.indexOf(keys[ki]) !== -1) return hi;
            }
        }
        return -1;
    }

    // Build invoice-key for each selected row using the preview data
    var headers   = (_previewData && _previewData.headers) ? _previewData.headers : [];
    var rowsData  = (_previewData && _previewData.rows)    ? _previewData.rows    : [];
    var invColIdx = _findHeaderIdx(headers, ['invoice_no', 'invoice no', 'invoice', 'inv_no']);
    var custColIdx = _findHeaderIdx(headers, [_importType === 'sales' ? 'customer' : 'supplier', 'party', _importType === 'sales' ? 'customer_name' : 'supplier_name']);
    var dateColIdx = _findHeaderIdx(headers, ['date', 'inv_date', 'invoice_date']);

    // Group selected indices by their invoice key
    var groupsMap = {}; // invKey → [rowIdx, rowIdx, ...]
    var groupOrder = []; // preserves first-seen order of invoice keys
    selected.forEach(function(rowIdx) {
        var row = rowsData[rowIdx];
        var raw = (row && row.raw) ? row.raw : [];
        var invNo = invColIdx !== -1 ? String(raw[invColIdx] || '').trim() : '';
        var key;
        if (invNo !== '') {
            key = 'inv:' + invNo;
        } else {
            var cust = (custColIdx !== -1 ? String(raw[custColIdx] || '').toLowerCase().trim() : '');
            var dt   = (dateColIdx !== -1 ? String(raw[dateColIdx] || '').trim() : '');
            key = (cust === '' && dt === '') ? ('row:' + rowIdx) : ('auto:' + cust + '|' + dt);
        }
        if (!groupsMap[key]) { groupsMap[key] = []; groupOrder.push(key); }
        groupsMap[key].push(rowIdx);
    });

    // Pack groups into chunks — keep every invoice together; only roll over when next group
    // would push the chunk over CHUNK_SIZE. A single oversized invoice still goes in alone.
    var chunks = [];
    var current = [];
    for (var gi = 0; gi < groupOrder.length; gi++) {
        var grp = groupsMap[groupOrder[gi]];
        if (current.length > 0 && current.length + grp.length > CHUNK_SIZE) {
            chunks.push(current);
            current = [];
        }
        current = current.concat(grp);
    }
    if (current.length > 0) chunks.push(current);
    var totals = { imported: 0, skipped: 0, duplicates: 0, errors: [] };
    var chunkIdx = 0;

    function _showChunkProgress(pct, label) {
        var titleEl = document.getElementById('importPreviewBlockerTitle');
        var msgEl   = document.getElementById('importPreviewBlockerMsg');
        if (titleEl) titleEl.textContent = 'Importing… ' + pct + '%';
        // label is allowed to contain inline HTML (e.g. <strong>) for bolding the batch numbers per spec.
        if (msgEl)   msgEl.innerHTML     = label || 'Processing rows in batches';

        // Drive the conic-gradient progress arc + center percentage text.
        var arc = document.getElementById('importPreviewBlockerArc');
        var pctEl = document.getElementById('importPreviewBlockerPercent');
        var clamped = Math.max(0, Math.min(100, Number(pct) || 0));
        if (arc) {
            arc.style.setProperty('--pct', String(clamped));
        }
        if (pctEl) pctEl.textContent = clamped + '%';
    }

    function sendNextChunk() {
        if (_importCancelled) return;
        if (chunkIdx >= chunks.length) {
            // All chunks done — show success modal (replaces toast). Modal CTAs handle the cleanup.
            _runningImportXHR = null;
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-check"></i> Import Data';
            _setImportBusy(false);
            _showImportCompleteModal(totals);
            return;
        }
        var thisChunk = chunks[chunkIdx];
        var pct = Math.round(((chunkIdx) / chunks.length) * 100);
        // Subtitle per spec: "Batch <N> of <M> · uploading in chunks" — bold the numbers.
        _showChunkProgress(pct, 'Batch <strong style="color:#0f1115;font-weight:800;">' + (chunkIdx + 1) + '</strong> of <strong style="color:#0f1115;font-weight:800;">' + chunks.length + '</strong> · uploading in chunks');

        var formData = new FormData();
        formData.append('file', file);
        formData.append('type', _importType);
        formData.append('selected_rows', JSON.stringify(thisChunk));
        if (Object.keys(editedForServer).length > 0) {
            formData.append('edited_rows', JSON.stringify(editedForServer));
        }
        if (_fieldMapping && Object.keys(_fieldMapping).length > 0) {
            formData.append('mapping', JSON.stringify(_fieldMapping));
        }
        formData.append('_token', '{{ csrf_token() }}');

        _runningImportXHR = $.ajax({
            url: "{{ route('management.settings.import.import') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 0,
            success: function(res) {
                if (_importCancelled) return;
                totals.imported   += (res.imported   || 0);
                totals.skipped    += (res.skipped    || 0);
                totals.duplicates += (res.duplicates || 0);
                if (Array.isArray(res.errors)) totals.errors = totals.errors.concat(res.errors);
                chunkIdx++;
                sendNextChunk();
            },
            error: function(xhr, textStatus) {
                if (textStatus === 'abort' || _importCancelled) return;
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message
                        : ('Import failed at batch ' + (chunkIdx + 1) + ' of ' + chunks.length + '.');
                showSettingsToast(msg + ' Imported ' + totals.imported + ' rows before failure.', 'error');
                _runningImportXHR = null;
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-check"></i> Import Data';
                _setImportBusy(false);
            }
        });
    }

    sendNextChunk();
}

// Holds the in-flight import jqXHR so the Cancel button can abort it.
var _runningImportXHR = null;
var _importCancelled = false;

function _cancelRunningImport() {
    if (!_runningImportXHR) return;
    _importCancelled = true;
    var cancelBtn = document.getElementById('importPreviewBlockerCancelBtn');
    if (cancelBtn) {
        cancelBtn.disabled = true;
        cancelBtn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/></svg><span>Cancelling…</span>';
    }
    try { _runningImportXHR.abort(); } catch (_) { /* swallow */ }
    showSettingsToast('Import cancelled — no rows were saved.', 'success');
}
</script>
@endpush
