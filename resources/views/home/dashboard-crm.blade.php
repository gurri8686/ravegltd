@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }
@keyframes dcFadeIn{from{opacity:0;transform:translateY(-6px);filter:blur(2px)}to{opacity:1;transform:translateY(0);filter:blur(0)}}
.dc-day{width:40px;height:40px;border-radius:12px;border:none;font-size:13px;font-weight:600;cursor:pointer;outline:none;transition:all 0.15s;background:transparent;color:#1e293b;position:relative;}
.dc-day:hover{background:linear-gradient(135deg,#FFF5ED,#fff7ed);color:rgb(234, 88, 12);transform:scale(1.08);}
.dc-day.dc-today{background:linear-gradient(135deg,#fef3e2,#fff7ed);color:#c2410c;font-weight:800;box-shadow:inset 0 0 0 2px #fed7aa;}
.dc-day.dc-selected{background:rgb(234, 88, 12) !important;color:#fff !important;box-shadow:0 4px 12px rgba(234,88,12,0.4);transform:scale(1.05);font-weight:700;}
.dc-day.dc-other{color:#e8ecf0;font-weight:400;pointer-events:none;opacity:0.15;}
.dc-day.dc-disabled{color:#e2e8f0;cursor:not-allowed;opacity:0.4;}
.dc-day.dc-disabled:hover{background:transparent;color:#e2e8f0;transform:none;}
.dc-nav{width:34px;height:34px;border-radius:10px;border:1.5px solid #e5e7eb;background:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:15px;color:#64748b;font-weight:700;transition:all 0.15s;outline:none;}
.dc-nav:hover{background:rgb(234, 88, 12);border-color:rgb(234, 88, 12);color:#fff;box-shadow:0 3px 10px rgba(234,88,12,0.3);transform:scale(1.05);}
.dc-hdr-select{border:1.5px solid #e2e8f0;border-radius:10px;padding:7px 28px 7px 12px;font-size:13px;font-weight:700;color:#1e293b;background:#fff;cursor:pointer;outline:none;appearance:none;-webkit-appearance:none;transition:all 0.15s;background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2710%27 height=%2710%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%23F27420%27 stroke-width=%273%27 stroke-linecap=%27round%27%3E%3Cpolyline points=%276 9 12 15 18 9%27/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 8px center;box-shadow:0 1px 3px rgba(0,0,0,0.04);}
.dc-hdr-select:hover{border-color:rgb(234, 88, 12);background:#FFF5ED;}
.dc-hdr-select:focus{border-color:rgb(234, 88, 12);background:#fff;box-shadow:none;outline:none;}
.dc-hdr-select option{padding:8px 12px;font-weight:500;font-size:13px;}
.dc-hdr-select option:checked{background:rgb(234, 88, 12) linear-gradient(rgb(234, 88, 12),rgb(234, 88, 12));color:#fff;}
.dc-hdr-select option:hover{background:#FFF5ED linear-gradient(#FFF5ED,#FFF5ED);}
.dc-hdr-select::-webkit-scrollbar{width:4px;}
.dc-hdr-select::-webkit-scrollbar-thumb{background:rgb(234, 88, 12);border-radius:4px;}
.dc-footer-btn{border:none;background:none;font-size:12px;font-weight:700;cursor:pointer;padding:6px 14px;border-radius:8px;transition:all 0.15s;}
.dc-footer-btn:hover{transform:scale(1.03);}
/* Date filter — preset buttons + custom range inputs */
.dash-preset-btn {
    height: 34px; border-radius: 9px; border: 1.5px solid #e5e7eb; background: #fff;
    color: #475569; font-size: 12.5px; font-weight: 600; cursor: pointer; outline: none;
    transition: border-color 0.15s, background 0.15s, color 0.15s; padding: 0 10px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dash-preset-btn:hover { border-color: rgb(234, 88, 12); color: rgb(234, 88, 12); background: #fffbf5; }
.dash-preset-btn.active { border-color: rgb(234, 88, 12); background: rgb(234, 88, 12); color: #fff; }
.dash-date-input {
    width: 100%; height: 36px; padding: 0 10px; border-radius: 9px;
    border: 1.5px solid #e5e7eb; background: #fafbfc; font-size: 12.5px;
    font-weight: 600; color: #0f172a; outline: none; transition: border-color 0.15s, background 0.15s;
    font-family: inherit;
}
.dash-date-input:focus { border-color: rgb(234, 88, 12); background: #fff; }
/* Custom calendar trigger (replaces native <input type="date">) */
.dash-cal-trigger {
    width: 100%; height: 38px; padding: 0 12px; border-radius: 10px;
    border: 1.5px solid #e5e7eb; background: #fafbfc;
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 12.5px; font-weight: 600; color: #0f172a;
    cursor: pointer; outline: none;
    transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
    font-family: inherit; text-align: left;
}
.dash-cal-trigger:hover, .dash-cal-trigger.active { border-color: rgb(234, 88, 12); background: #fff; }
.dash-cal-trigger.active { box-shadow: 0 0 0 3px rgba(234,88,12,0.10); }
/* Calendar navigation arrows */
.dash-cal-nav-btn {
    width: 28px; height: 28px; border-radius: 8px;
    border: 1.5px solid #e5e7eb; background: #fff; color: #475569;
    cursor: pointer; outline: none; display: inline-flex;
    align-items: center; justify-content: center;
    transition: border-color 0.15s, color 0.15s, background 0.15s;
}
.dash-cal-nav-btn:hover { border-color: rgb(234, 88, 12); color: rgb(234, 88, 12); background: #fffbf5; }
/* Calendar day cells — matched to daily_book_sales picker theme */
.dash-cal-dow {
    height: 28px; display: flex; align-items: center; justify-content: center;
    font-size: 10.5px; font-weight: 700; color: #9ca3af;
    text-transform: uppercase; letter-spacing: 0.4px;
}
.dash-cal-day {
    height: 34px; display: flex; align-items: center; justify-content: center;
    font-size: 12.5px; font-weight: 600; color: #1f2937;
    border-radius: 50%; cursor: pointer; border: none; background: transparent;
    outline: none; transition: background 0.1s, color 0.1s;
    font-family: inherit; padding: 0;
}
.dash-cal-day:hover:not(.disabled):not(.selected):not(.in-range) { background: #f1f5f9; color: #1f2937; }
.dash-cal-day.muted { color: transparent; pointer-events: none; }
.dash-cal-day.today { color: rgb(234, 88, 12); font-weight: 700; background: transparent; }
.dash-cal-day.selected { background: rgb(234, 88, 12); color: #fff; font-weight: 700; border-radius: 50%; z-index: 2; position: relative; }
.dash-cal-day.selected:hover { background: rgb(234, 88, 12); color: #fff; }
.dash-cal-day.disabled { color: #e2e8f0; cursor: not-allowed; pointer-events: none; }
/* Range selection — soft peach fill between range endpoints */
.dash-cal-day.in-range {
    background: #ffedd5; color: rgb(234, 88, 12); border-radius: 0; font-weight: 600;
    box-shadow: none;
}
.dash-cal-day.in-range:hover { background: #fed7aa; color: rgb(234, 88, 12); }
.dash-cal-day.in-range.muted { color: #fed7aa; }
.dash-cal-day.range-start { border-radius: 50% 0 0 50%; background: rgb(234, 88, 12); color: #fff; font-weight: 700; }
.dash-cal-day.range-end   { border-radius: 0 50% 50% 0; background: rgb(234, 88, 12); color: #fff; font-weight: 700; }
.dash-cal-day.range-start.range-end { border-radius: 50%; }
/* Range summary pill — read-only display of the picked range */
.dash-range-summary {
    width: 100%;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    height: 38px;
    padding: 0 12px;
    border-radius: 10px;
    border: 1.5px solid #fed7aa;
    background: #fff7ed;
    font-size: 12.5px;
    font-weight: 600;
    color: #0f172a;
    font-family: inherit;
}
.dash-range-label { color: #0f172a; font-weight: 700; }
.dash-range-sep { color: rgb(234, 88, 12); font-weight: 700; flex-shrink: 0; }
/* Pending state — when user picked start but not yet end, dim the To label */
.dash-range-summary.pending .dash-range-label.to-label { color: #cbd5e1; font-weight: 500; }
.dash-card {
    background: #fff; border-radius: 16px; border: 1px solid #f0f0f0;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04); padding: 22px 24px;
    display: flex; align-items: flex-start; gap: 16px; transition: all 0.15s;
}
.dash-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-1px); }
.dash-icon {
    width: 48px; height: 48px; border-radius: 14px; display: flex;
    align-items: center; justify-content: center; flex-shrink: 0; font-size: 20px;
}
.dash-label { font-size: 12px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
.dash-value { font-size: 24px; font-weight: 800; color: #0f172a; line-height: 1.1; }
.dash-sub { font-size: 11.5px; font-weight: 500; color: #94a3b8; margin-top: 4px; }
.dash-panel {
    background: #fff; border-radius: 16px; border: 1px solid #f0f0f0;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04); overflow: hidden;
}
.dash-panel-header {
    padding: 18px 24px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
}
.dash-panel-title { font-size: 15px; font-weight: 700; color: #0f172a; }
.dash-bar { height: 100%; border-radius: 6px 6px 0 0; min-width: 1px; transition: all 0.3s; }
.chart-col{display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;cursor:pointer;position:relative;transition:all 0.15s;border-radius:8px;padding:4px 0;}
.chart-col:hover{background:rgba(234,88,12,0.04);}
.chart-col:hover .chart-bar-s{filter:brightness(1.1);transform:scaleX(1.15);}
.chart-col:hover .chart-bar-p{filter:brightness(1.1);transform:scaleX(1.15);}
.chart-col:hover .chart-day{color:rgb(234, 88, 12) !important;font-weight:800 !important;}
#chartContainer{scrollbar-width:none !important;-ms-overflow-style:none !important;}
#chartContainer::-webkit-scrollbar{display:none !important;}
.chart-scroll-bar{height:4px;border-radius:10px;background:#f1f5f9;margin:8px 24px 0;position:relative;overflow:hidden;}
.chart-scroll-thumb{height:100%;border-radius:10px;background:rgb(234, 88, 12);position:absolute;left:0;top:0;transition:left 0.1s;}

@media (max-width: 991px) {
    .dash-bottom-grid { grid-template-columns: 1fr !important; }
}

/* ══════════════════════════════════════════════════
   TABLET — shared base (both orientations)
   768px – 1024px
   ══════════════════════════════════════════════════ */
@media (min-width: 768px) and (max-width: 1024px) {

    /* ── Wrapper padding ── */
    .dash-header-card {
        padding: 16px 20px !important;
        margin-bottom: 16px !important;
        border-radius: 16px !important;
        gap: 12px !important;
    }
    .dash-header-card h1 { font-size: 20px !important; }
    .dash-header-card p  { font-size: 12px !important; margin: 2px 0 0 !important; }
    .dash-header-icon { width: 44px !important; height: 44px !important; border-radius: 13px !important; }
    .dash-header-icon i { font-size: 18px !important; }
    /* Date filter — slightly smaller button on tablet */
    #dashDateBtn { height: 40px !important; font-size: 12.5px !important; padding: 0 14px !important; }
    #dashDatePopover { min-width: 280px !important; }

    /* ── Stat cards ── */
    .dash-card {
        padding: 16px 14px !important;
        gap: 12px !important;
        border-radius: 14px !important;
        align-items: center !important;
        flex-direction: row !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important;
        border: 1px solid #f0f2f5 !important;
        transition: box-shadow 0.18s, transform 0.18s !important;
    }
    .dash-card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.09) !important;
        transform: translateY(-2px) !important;
    }
    .dash-icon {
        width: 42px !important; height: 42px !important;
        border-radius: 12px !important; font-size: 16px !important;
        flex-shrink: 0 !important;
    }
    .dash-label {
        font-size: 9.5px !important;
        font-weight: 700 !important;
        letter-spacing: 0.5px !important;
        margin-bottom: 3px !important;
        text-transform: uppercase !important;
        color: #94a3b8 !important;
    }
    .dash-value {
        font-size: 20px !important;
        font-weight: 800 !important;
        line-height: 1.15 !important;
        word-break: break-all !important;
        overflow-wrap: anywhere !important;
    }
    .dash-sub {
        font-size: 10px !important;
        margin-top: 2px !important;
        color: #b0b8c4 !important;
    }

    /* ── All-time row ── */
    .dash-row-alltime .dash-icon { display: none !important; }
    .dash-row-alltime .dash-card {
        flex-direction: column !important;
        align-items: flex-start !important;
        padding: 14px 16px !important;
        gap: 3px !important;
        background: linear-gradient(135deg,#fafbfd,#fff) !important;
    }
    .dash-row-alltime .dash-value { font-size: 18px !important; }
    .dash-row-alltime .dash-label { font-size: 9px !important; }

    /* ── Panels ── */
    .dash-panel {
        border-radius: 16px !important;
        border: 1px solid #f0f2f5 !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important;
        overflow: hidden !important;
    }
    .dash-panel-header {
        padding: 14px 18px !important;
        gap: 8px !important;
        border-bottom: 1px solid #f4f6f8 !important;
    }
    .dash-panel-title { font-size: 14px !important; font-weight: 700 !important; }
    .dash-chart-legend { padding: 10px 18px 0 !important; gap: 14px !important; }
    .dash-chart-area {
        height: 210px !important;
        padding: 12px 16px 18px !important;
        gap: 2px !important;
    }
    .chart-scroll-bar { margin: 6px 18px 14px !important; }
    .dash-product-item { padding: 11px 18px !important; gap: 12px !important; }
    .dash-product-icon { width: 36px !important; height: 36px !important; border-radius: 10px !important; }
    .dash-product-icon i { font-size: 12px !important; }
}

/* ══════════════════════════════════════════════════
   TABLET LANDSCAPE — 4-col today, side-by-side bottom
   ══════════════════════════════════════════════════ */
@media (min-width: 768px) and (max-width: 1024px) and (orientation: landscape) {

    .dash-row-today {
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 12px !important;
        margin-bottom: 16px !important;
    }
    .dash-row-alltime {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 12px !important;
        margin-bottom: 16px !important;
    }
    .dash-bottom-grid {
        grid-template-columns: 1fr 300px !important;
        gap: 14px !important;
    }
    .dash-card { padding: 13px 12px !important; gap: 10px !important; }
    .dash-icon { width: 38px !important; height: 38px !important; font-size: 14px !important; }
    .dash-value { font-size: 18px !important; }
    .dash-label { font-size: 9px !important; }
    .dash-chart-area { height: 190px !important; }
}

/* ══════════════════════════════════════════════════
   TABLET PORTRAIT — 2×2 today, stacked bottom
   ══════════════════════════════════════════════════ */
@media (min-width: 768px) and (max-width: 1024px) and (orientation: portrait) {

    .dash-row-today {
        grid-template-columns: 1fr 1fr !important;
        gap: 13px !important;
        margin-bottom: 16px !important;
    }
    .dash-row-alltime {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 13px !important;
        margin-bottom: 16px !important;
    }
    .dash-bottom-grid {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
    }
    .dash-value { font-size: 22px !important; }
    .dash-icon { width: 44px !important; height: 44px !important; font-size: 17px !important; }
    .dash-chart-area { height: 220px !important; }
}

@media (max-width: 767px) {
    /* Mobile-only top hero (greeting + Today's Sales) */
    .dash-mob-hero { display: block !important; }
    /* Visually hide the original Dashboard header on mobile (replaced by the greeting + hero) but
       keep it in the DOM so the date-filter popover (#dashDatePopover) can position itself.
       The mobile date button still calls dashToggleDatePicker() which opens the same popover. */
    .dash-header-card {
        position: absolute !important; left: 8px !important; right: 8px !important; top: 8px !important;
        height: 0 !important; min-height: 0 !important; padding: 0 !important; margin: 0 !important;
        border: none !important; box-shadow: none !important; background: transparent !important;
        overflow: visible !important; pointer-events: none !important;
    }
    .dash-header-card > div:first-child { display: none !important; }
    .dash-header-card #dashDateFilterWrap { visibility: hidden !important; height: 0 !important; }
    .dash-header-card #dashDatePopover { visibility: visible !important; pointer-events: auto !important; left: 0 !important; right: 0 !important; }
    /* Recent invoices — mobile only */
    .dash-recent-invoices { display: block !important; margin-top: 14px; }
    /* Header — single row: icon + title block (left) + date filter (right end).
       flex-wrap:nowrap ensures filter stays on same line; min-width:0 lets the title block
       shrink/ellipsis cleanly on small phones so date filter is never cut off. */
    .dash-header-card {
        padding: 12px 14px !important;
        margin-bottom: 12px !important;
        border-radius: 14px !important;
        gap: 10px !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
    }
    /* Title block (icon + h1 + p) — shrinkable, takes remaining space */
    .dash-header-card > div:first-child {
        flex: 1 1 auto !important;
        min-width: 0 !important;
        gap: 10px !important;
    }
    .dash-header-icon { width: 36px !important; height: 36px !important; border-radius: 10px !important; flex-shrink: 0 !important; }
    .dash-header-icon i { font-size: 14px !important; }
    .dash-header-card h1 {
        font-size: 16px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    .dash-header-card p {
        font-size: 10px !important;
        margin: 0 !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* Date filter — compact pill on the right end, never cut off */
    #dashDateFilterWrap {
        width: auto !important;
        flex-shrink: 0 !important;
    }
    #dashDateBtn {
        width: auto !important;
        height: 36px !important;
        padding: 0 10px !important;
        font-size: 11.5px !important;
        gap: 6px !important;
        justify-content: center !important;
        white-space: nowrap !important;
    }
    #dashDateBtnLabel {
        max-width: 90px !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }
    #dashDatePopover {
        position: fixed !important;
        top: auto !important; bottom: 0 !important;
        left: 0 !important; right: 0 !important;
        border-radius: 16px 16px 0 0 !important;
        max-width: 100% !important; min-width: 0 !important;
        padding: 16px !important;
        box-shadow: 0 -8px 24px rgba(15,23,42,0.18) !important;
        z-index: 1000 !important;
    }
    .dash-preset-btn { height: 38px !important; font-size: 13px !important; }
    .dash-date-input { height: 40px !important; font-size: 13px !important; }
    /* Backdrop for the bottom-sheet popover */
    #dashDatePopoverBackdrop {
        position: fixed; inset: 0; background: rgba(15,23,42,0.45);
        z-index: 999; opacity: 0; transition: opacity 0.18s;
    }
    #dashDatePopoverBackdrop.open { opacity: 1; }

    /* Show the mobile-only Cancel / Apply footer at the bottom of the date popover */
    #dashMobileFooter { display: flex !important; }

    /* ── Stat cards — premium business-app look (2x2 grid, equal height, clean rhythm) ── */
    .dash-row-today,
    .dash-row-alltime {
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important; margin-bottom: 10px !important;
        background: transparent !important; border: none !important; box-shadow: none !important;
        border-radius: 0 !important; overflow: visible !important;
        align-items: stretch !important;
    }
    /* If the row has an odd number of children, make the last one span both columns so we never get a half-empty row. */
    .dash-row-today .dash-card:last-child:nth-child(odd),
    .dash-row-alltime .dash-card:last-child:nth-child(odd) { grid-column: 1 / -1 !important; }
    /* ── Stat cards — Icon + LABEL inline top row, amount big below ── */
    .dash-card,
    .dash-row-alltime .dash-card {
        padding: 14px !important; gap: 0 !important;
        border-radius: 14px !important;
        display: grid !important;
        grid-template-columns: auto 1fr !important;
        grid-template-rows: auto auto !important;
        column-gap: 10px !important; row-gap: 8px !important;
        align-items: center !important;
        text-align: left !important;
        border: 1px solid #eef0f3 !important;
        box-shadow: 0 1px 2px rgba(15,17,21,0.04) !important;
        background: #fff !important;
        min-height: 96px !important;
        position: relative !important;
        transition: box-shadow 0.18s ease, transform 0.18s ease !important;
    }
    .dash-card:active { transform: scale(0.985) !important; }
    .dash-card:hover,
    .dash-row-alltime .dash-card:hover { transform: none !important; box-shadow: 0 1px 2px rgba(15,17,21,0.04) !important; }
    .dash-icon,
    .dash-row-alltime .dash-icon {
        grid-column: 1 !important; grid-row: 1 !important;
        display: inline-flex !important;
        width: 34px !important; height: 34px !important;
        border-radius: 10px !important; font-size: 14px !important;
        margin: 0 !important; flex-shrink: 0 !important;
        align-self: center !important;
        box-shadow: none !important;
        align-items: center !important; justify-content: center !important;
    }
    .dash-row-alltime .dash-label i { display: none !important; }
    /* Inner text wrapper occupies the right column on row 1 + spans full width on row 2 */
    .dash-card > div:not(.dash-icon) {
        grid-column: 2 !important; grid-row: 1 / 3 !important;
        display: grid !important;
        grid-template-rows: auto auto !important;
        row-gap: 6px !important;
        min-width: 0 !important;
        margin: 0 !important; padding: 0 !important;
    }
    .dash-value,
    .dash-row-alltime .dash-value {
        grid-row: 2 !important;
        font-size: 20px !important; line-height: 1.1 !important;
        font-weight: 800 !important; color: #0f1115 !important;
        letter-spacing: -0.3px !important;
        text-align: left !important;
        margin: 0 !important;
        font-family: ui-monospace,SFMono-Regular,Menlo,monospace;
        white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important;
    }
    .dash-label,
    .dash-row-alltime .dash-label {
        grid-row: 1 !important;
        font-size: 10px !important; letter-spacing: 0.6px !important;
        font-weight: 700 !important; color: #9ca3af !important;
        text-transform: uppercase !important;
        text-align: left !important;
        margin: 0 !important;
        white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important;
    }
    .dash-sub { display: none !important; }
    /* When a card spans both columns (e.g. Net Loss alone in an odd row), give the value a touch more presence */
    .dash-card:last-child:nth-child(odd) .dash-value { font-size: 22px !important; }

    /* Chart panel */
    .dash-panel {
        border-radius: 14px !important;
        border: 1px solid #f0f2f5 !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03) !important;
    }
    .dash-panel-header { padding: 12px 16px !important; gap: 8px !important; }
    .dash-panel-title { font-size: 14px !important; }
    .dash-chart-legend { padding: 8px 16px 0 !important; gap: 12px !important; }
    .dash-chart-legend div div:first-child { width: 8px !important; height: 8px !important; }
    .dash-chart-legend span { font-size: 10px !important; }
    .dash-chart-area {
        padding: 10px 10px 12px !important; height: 150px !important;
        gap: 1px !important; overflow-x: scroll !important;
        -webkit-overflow-scrolling: touch !important;
    }
    .chart-col { min-width: 24px !important; padding: 2px 0 !important; }
    .chart-day { font-size: 8px !important; }
    .chart-scroll-bar { margin: 6px 16px 14px !important; height: 3px !important; }

    /* Products list */
    .dash-product-item { padding: 10px 14px !important; gap: 10px !important; }
    .dash-product-icon { width: 30px !important; height: 30px !important; border-radius: 8px !important; }
    .dash-product-icon i { font-size: 10px !important; }

    /* Bottom grid */
    .dash-bottom-grid { gap: 10px !important; margin-bottom: 12px !important; }

    /* Dropdowns */
    .dc-hdr-select { font-size: 11px !important; padding: 5px 20px 5px 8px !important; border-radius: 8px !important; }

    /* Tooltip */
    #chartTip { min-width: 160px !important; padding: 10px 14px !important; border-radius: 10px !important; }
}

/* ─────────────────────────────────────────────────────────────
   Mobile date-range bottom sheet — reference-exact (≤767px ONLY).
   The desktop popover is untouched: the handle/header elements are
   display:none by default, and every override below is mobile-scoped.
   ───────────────────────────────────────────────────────────── */
.dash-sheet-handle, .dash-sheet-header { display: none; }
/* Mobile-only custom-range connectors (revealed at ≤767px; desktop keeps the plain "→") */
.dash-range-line, .dash-range-arrow { display: none; }

@media (max-width: 767px) {
    /* Sheet shell — scrollable, rounded top, comfortable padding (footer adds the bottom space) */
    /* Bottom sheet = flex column: fixed handle + header, scrollable body, fixed footer.
       Only .dash-sheet-body scrolls — not the whole sheet or the page. */
    #dashDatePopover {
        padding: 8px 0 0 !important;
        border-radius: 20px 20px 0 0 !important;
        max-height: 70vh !important;   /* partial sheet — body (calendar) scrolls inside, page does not */
        display: flex !important; flex-direction: column !important;
        overflow: hidden !important;
        transform: translateY(100%);
        transition: transform 0.30s cubic-bezier(0.22, 1, 0.36, 1);
    }
    #dashDatePopover.dash-sheet-open { transform: translateY(0); }
    .dash-sheet-body {
        flex: 1 1 auto !important; min-height: 0 !important;
        overflow-y: auto !important; overflow-x: hidden !important;
        -webkit-overflow-scrolling: touch !important;
        padding: 0 18px 16px !important;   /* equal left/right */
    }
    /* Drag handle */
    .dash-sheet-handle {
        display: block !important;
        width: 40px; height: 5px; border-radius: 999px;
        background: #d8dde5; margin: 0 auto 12px; flex-shrink: 0;
    }
    /* Header — orange calendar icon + title (left), round close button (right). Fixed (doesn't scroll). */
    .dash-sheet-header {
        display: flex !important; align-items: center; justify-content: space-between;
        flex-shrink: 0; padding: 0 18px; margin: 0 0 16px;
    }
    .dash-sheet-title {
        display: inline-flex; align-items: center; gap: 9px;
        font-size: 16px; font-weight: 800; color: #0f1115; letter-spacing: -0.2px;
    }
    .dash-sheet-close {
        width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
        background: #f1f5f9; border: none; color: #64748b;
        display: inline-flex; align-items: center; justify-content: center; cursor: pointer;
    }
    .dash-sheet-close:active { background: #e5e7eb; }

    /* Hide the uppercase "Quick select" / "Custom range" mini-labels on mobile (Sales sheet has none) */
    .dash-mini-label { display: none !important; }
    /* Presets → horizontal-scroll pill row (Sales sheet) */
    .dash-preset-grid {
        display: flex !important; grid-template-columns: none !important;
        flex-wrap: nowrap !important; overflow-x: auto !important;
        gap: 8px !important; margin-bottom: 14px !important;
        -webkit-overflow-scrolling: touch !important;
        scrollbar-width: none !important; -ms-overflow-style: none !important;
    }
    .dash-preset-grid::-webkit-scrollbar { display: none !important; width: 0 !important; height: 0 !important; }
    .dash-preset-btn {
        flex: 0 0 auto !important;
        height: 34px !important; border-radius: 999px !important; padding: 0 16px !important;
        font-size: 13px !important; font-weight: 700 !important; letter-spacing: 0 !important;
        color: #475569 !important;
        border: 1.5px solid #e5e7eb !important; background: #fff !important;
        box-shadow: none !important; white-space: nowrap !important;
    }
    /* Active preset (set by JS on open — mobile only): solid black pill (Sales sheet) */
    .dash-preset-btn.active {
        background: #111827 !important; border-color: #111827 !important; color: #fff !important;
    }

    /* Custom-range row — Sales sheet: two white boxes (orange border) + 36px orange arrow button */
    .dash-range-summary {
        height: auto !important; display: flex !important; align-items: stretch !important;
        gap: 10px !important; padding: 0 !important;
        border-radius: 0 !important; background: transparent !important; border: none !important;
    }
    .dash-range-summary > svg { display: none !important; }    /* hide the single leading icon — boxes carry their own */
    .dash-range-line { display: none !important; }
    .dash-range-sep { display: none !important; }
    /* FROM / TO white boxes with caption (icon + label via ::before) */
    .dash-range-label {
        flex: 1 1 0% !important; min-width: 0 !important;
        display: flex !important; flex-direction: column !important; gap: 3px !important;
        background: #fff !important; border: 2px solid rgb(234, 88, 12) !important; border-radius: 12px !important;
        padding: 8px 12px !important; text-align: center !important;
        font-family: inherit !important; font-size: 14px !important; font-weight: 700 !important;
        color: #0f172a !important; white-space: nowrap !important; overflow: hidden !important; text-overflow: ellipsis !important;
    }
    .dash-range-label::before {
        display: inline-flex !important; align-items: center !important; justify-content: center !important;
        font-size: 10px !important; font-weight: 800 !important; letter-spacing: 0.6px !important;
        color: rgb(234, 88, 12) !important; text-transform: uppercase !important;
        padding-left: 15px !important; min-height: 12px !important;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23ea580c' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='3' y='4' width='18' height='18' rx='2'/%3E%3Cline x1='16' y1='2' x2='16' y2='6'/%3E%3Cline x1='8' y1='2' x2='8' y2='6'/%3E%3Cline x1='3' y1='10' x2='21' y2='10'/%3E%3C/svg%3E") no-repeat left center !important;
    }
    .dash-range-label.from-label::before { content: 'From' !important; }
    .dash-range-label.to-label::before   { content: 'To' !important; }
    /* Mid-selection (To not picked yet) → grey To box, like Sales */
    .dash-range-summary.pending .dash-range-label.to-label { border-color: #e5e7eb !important; color: #cbd5e1 !important; }
    /* Orange square arrow button between the boxes */
    .dash-range-arrow {
        display: inline-flex !important; align-items: center !important; justify-content: center !important;
        flex: 0 0 auto !important; align-self: center !important;
        width: 36px !important; height: 36px !important; border-radius: 10px !important;
        background: rgb(234, 88, 12) !important; color: #fff !important;
        box-shadow: 0 3px 10px rgba(234,88,12,0.35) !important;
    }
    .dash-range-arrow svg { stroke: #fff !important; }

    /* Calendar card — reference spec (pad 13/13/9, 14px/800 title) */
    #dashCalWrap { border-radius: 16px !important; padding: 13px 13px 9px !important; }
    #dashCalTitle { font-size: 14px !important; font-weight: 800 !important; color: #0f1115 !important; letter-spacing: -0.2px !important; }
    .dash-cal-nav-btn { width: 34px !important; height: 34px !important; border-radius: 50% !important; border: 1.5px solid #e5e7eb !important; color: #475569 !important; }
    #dashCalGrid { gap: 0 !important; row-gap: 1px !important; }
    .dash-cal-dow { height: auto !important; font-family: inherit !important; font-size: 11px !important; font-weight: 600 !important; padding: 4px 0 !important; color: #9ca3af !important; letter-spacing: 0.5px !important; text-transform: uppercase !important; }
    /* Day cell = 38px wrapper holding a 32px circle (::before). The range fill lives on
       the wrapper so ranges connect, while the selected/today highlight is a perfect circle. */
    .dash-cal-day {
        position: relative !important; isolation: isolate !important;
        height: 42px !important; width: 100% !important; margin: 0 !important;
        display: flex !important; align-items: center !important; justify-content: center !important;
        font-family: inherit !important;
        font-size: 14px !important; font-weight: 500 !important; color: #334155 !important;
        background: transparent !important; border: none !important; border-radius: 0 !important; box-shadow: none !important;
    }
    .dash-cal-day.muted { color: #cbd5e1 !important; }
    /* the 32px circle (sits behind the number via z-index, contained by isolation:isolate) */
    .dash-cal-day.selected::before, .dash-cal-day.today:not(.selected)::before {
        content: '' !important; position: absolute; left: 50%; top: 50%;
        width: 34px; height: 34px; border-radius: 50%; transform: translate(-50%, -50%); z-index: -1;
    }
    .dash-cal-day.today:not(.selected) { color: #c2410c !important; font-weight: 800 !important; }
    .dash-cal-day.today:not(.selected)::before { background: #fff7ed; box-shadow: inset 0 0 0 1.5px #fed7aa; }
    .dash-cal-day.selected { color: #fff !important; font-weight: 800 !important; }
    .dash-cal-day.selected::before { background: rgb(234, 88, 12); box-shadow: rgba(234,88,12,0.55) 0 4px 10px -3px; }
    /* range fill on the wrapper — connects start → end */
    .dash-cal-day.in-range { background: #fff7f0 !important; }
    .dash-cal-day.range-start { background: #fff7f0 !important; border-radius: 99px 0 0 99px !important; }
    .dash-cal-day.range-end { background: #fff7f0 !important; border-radius: 0 99px 99px 0 !important; }
    .dash-cal-day.selected.range-start.range-end { background: transparent !important; border-radius: 0 !important; }
    #dashCalHint { display: none !important; }
    #dashCalWrap > .dash-cal-actions { display: none !important; }

    /* Cancel / ✓ Apply — pinned to the bottom of the sheet */
    /* Footer — fixed (doesn't scroll); equal 18px sides */
    #dashMobileFooter {
        display: flex !important; flex-shrink: 0; gap: 12px !important;
        margin: 0 !important;
        padding: 14px 18px calc(14px + env(safe-area-inset-bottom)) !important;
        background: #fff; border-top: 1px solid #f1f5f9;
    }
    /* Footer is mobile-only — size its buttons to the Sales sheet (52px / radius 14px) */
    #dashMobileFooter > button { height: 52px !important; border-radius: 14px !important; font-size: 15px !important; }
    #dashMobileFooter > button:first-child { flex: 1 1 0% !important; font-weight: 700 !important; }
    #dashMobileFooter > button:last-child  { flex: 1.6 1 0% !important; font-weight: 800 !important; box-shadow: 0 6px 16px rgba(234,88,12,0.35) !important; }
}
</style>
@endpush

@php
    $currency = env('CURRENCY_SYMBOL', '£');
    $salesAllTime = $blocks['sales_alltime'][0]->amount ?? 0;
    $purchaseAllTime = $blocks['purchase_alltime'][0]->amount ?? 0;
    $net = $salesAllTime - $purchaseAllTime;
    $chartMax = $salesChart->max('amount') ?: 1;
@endphp

@php
    $authUser    = \Auth::user();
    $greetName   = $authUser ? ($authUser->first_name ?: explode('@', $authUser->email ?? '')[0]) : 'there';
    $hourNow     = (int) now()->format('H');
    $greetWord   = $hourNow < 12 ? 'Good morning' : ($hourNow < 17 ? 'Good afternoon' : 'Good evening');
    $yesterdayAmt = (float) \DB::table('customer_invoice_products')
        ->join('invoice_payments', 'invoice_payments.customer_invoice_id', '=', 'customer_invoice_products.customer_invoice_id')
        ->join('payments', 'payments.id', '=', 'invoice_payments.payment_id')
        ->where('payments.type', 'None')
        ->whereDate('customer_invoice_products.updated_at', $selectedCarbon->copy()->subDay()->toDateString())
        ->sum('customer_invoice_products.sub_total');
    $salesDelta = ((float) $salesToday) - $yesterdayAmt;
    $salesDeltaPct = $yesterdayAmt > 0 ? round((($salesToday - $yesterdayAmt) / $yesterdayAmt) * 100, 1) : 0;
    // Build a tiny polyline path for the hero sparkline from $salesChart
    $heroChart = $salesChart->pluck('amount')->toArray();
    $heroMax = max($heroChart) ?: 1;
    $heroPts = [];
    $heroW = 320; $heroH = 60; $heroN = max(count($heroChart) - 1, 1);
    foreach ($heroChart as $i => $v) {
        $x = round(($i / $heroN) * $heroW, 1);
        $y = round($heroH - (($v / $heroMax) * ($heroH - 8)) - 4, 1);
        $heroPts[] = "$x,$y";
    }
    // Fallback to a flat baseline so the sparkline area is never empty on first render
    if (empty($heroPts)) {
        $heroPts = ['0,'.($heroH - 4), $heroW.','.($heroH - 4)];
    }
    $heroPath = implode(' ', $heroPts);
    $heroArea = '0,'.$heroH.' '.$heroPath.' '.$heroW.','.$heroH;
@endphp

@section('content')
<div style="max-width:1440px;margin:0 auto;">

    {{-- ── Mobile-only greeting + Today's Sales hero card ── --}}
    <div class="dash-mob-hero" style="display:none;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin:4px 0 14px;">
            <div style="min-width:0;flex:1;">
                <div id="dashHeroDate" style="font-size:11px;font-weight:700;color:rgb(234, 88, 12);letter-spacing:0.6px;text-transform:uppercase;">{{ $selectedCarbon->format('l') }} · {{ $selectedCarbon->format('d M') }}</div>
                <h2 style="margin:4px 0 0;font-size:22px;font-weight:800;color:#0f1115;letter-spacing:-0.3px;line-height:1.2;"><span id="dashHeroGreetWord">{{ $greetWord }}</span>,<br>{{ $greetName }}</h2>
            </div>
            {{-- Mobile date filter button — opens the same popover the desktop button uses --}}
            <button type="button" id="dashMobDateBtn" onclick="dashToggleDatePicker(event)" aria-label="Change date" style="flex-shrink:0;display:inline-flex;align-items:center;gap:6px;height:38px;padding:0 12px;border-radius:10px;border:1px solid #eaecf2;background:#fff;color:#0f1115;font-size:12.5px;font-weight:700;cursor:pointer;outline:none;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg>
                <span id="dashMobDateBtnLabel">{{ $selectedCarbon->isToday() ? 'Today' : $selectedCarbon->format('d M') }}</span>
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M6 9l6 6 6-6"></path></svg>
            </button>
        </div>

        {{-- Big orange "TODAY'S SALES" hero card --}}
        <a href="/data_entry/sales_entry/view" id="dashHeroCard" style="display:block;text-decoration:none;color:inherit;background:linear-gradient(135deg,rgb(234, 88, 12),#fb923c);border-radius:20px;padding:18px 18px 0;color:#fff;box-shadow:0 12px 28px -8px rgba(234,88,12,0.55),0 4px 10px -4px rgba(234,88,12,0.35);margin-bottom:18px;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <span style="display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:800;color:#fff;letter-spacing:1px;text-transform:uppercase;opacity:0.95;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    <span id="dashHeroLabel">Today's sales</span>
                </span>
                <span id="dashHeroDeltaPill" style="display:{{ $salesDeltaPct != 0 ? 'inline-flex' : 'none' }};align-items:center;gap:4px;font-size:11px;font-weight:800;background:rgba(255,255,255,0.18);color:#fff;border-radius:999px;padding:3px 10px;">
                    <span id="dashHeroDeltaPillText">{{ $salesDeltaPct >= 0 ? '↑' : '↓' }} {{ abs($salesDeltaPct) }}%</span>
                </span>
            </div>
            @php
                $salesIntPart = number_format((int) $salesToday);
                $salesDecPart = number_format(($salesToday - (int) $salesToday) * 100, 0, '', '');
                $salesDecPart = str_pad($salesDecPart, 2, '0', STR_PAD_LEFT);
            @endphp
            <div style="display:flex;align-items:baseline;gap:2px;margin-top:14px;line-height:1;">
                <span id="dashHeroCurrency" style="font-size:18px;font-weight:800;opacity:0.85;">{{ $currency }}</span>
                <span id="dashHeroIntPart" style="font-size:38px;font-weight:900;letter-spacing:-1.5px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">{{ $salesIntPart }}</span>
                <span id="dashHeroDecPart" style="font-size:18px;font-weight:800;opacity:0.85;">.{{ $salesDecPart }}</span>
            </div>
            <div id="dashHeroSub" style="margin-top:6px;font-size:12.5px;font-weight:600;opacity:0.95;">
                @if($salesDelta != 0)
                    {{ $salesDelta >= 0 ? '+' : '-' }}{{ $currency }}{{ number_format(abs($salesDelta), 0) }} vs yesterday
                    @if($todayOrders) · {{ $todayOrders }} {{ $todayOrders == 1 ? 'invoice' : 'invoices' }} @endif
                @elseif($todayOrders)
                    {{ $todayOrders }} {{ $todayOrders == 1 ? 'invoice' : 'invoices' }}
                @endif
            </div>
            <svg id="dashHeroSpark" viewBox="0 0 {{ $heroW }} {{ $heroH }}" preserveAspectRatio="none" style="display:block;width:100%;height:60px;margin-top:8px;">
                <defs>
                    <linearGradient id="heroSparkFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="rgba(255,255,255,0.45)"/>
                        <stop offset="100%" stop-color="rgba(255,255,255,0)"/>
                    </linearGradient>
                </defs>
                <polygon id="dashHeroSparkArea" points="{{ $heroArea }}" fill="url(#heroSparkFill)"/>
                <polyline id="dashHeroSparkLine" points="{{ $heroPath }}" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
    </div>


    {{-- Header — exact spec UI --}}
    <div class="dash-header-card" style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#ffffff;border-radius:12px;padding:18px 22px;box-shadow:0 1px 2px rgba(15,17,21,0.04),0 6px 18px -8px rgba(15,17,21,0.12);border:1px solid #e8e8ec;">
        <div style="display:flex;align-items:center;gap:14px;">
            <span class="dash-header-icon" style="width:44px;height:44px;border-radius:11px;background:rgb(234, 88, 12);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:inset 0 1px 0 rgba(255,255,255,0.25),0 6px 14px -4px rgba(234,88,12,0.45);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14"/><path d="M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"/></svg>
            </span>
            <div>
                <h2 style="margin:0;font-size:20px;font-weight:800;color:#0f1115;letter-spacing:-0.2px;">Dashboard</h2>
                <p style="margin:2px 0 0;font-size:13px;color:#6b7280;">Business overview at a glance</p>
            </div>
        </div>

        {{-- Date filter — real-time updates Today's Sales/Purchases/Orders without a page reload --}}
        <div id="dashDateFilterWrap" style="position:relative;">
            <button type="button" id="dashDateBtn" onclick="dashToggleDatePicker(event)" style="display:inline-flex;align-items:center;gap:8px;height:38px;padding:0 16px;border-radius:99px;border:1px solid #e8e8ec;background:#ffffff;color:#0f1115;font-size:13px;font-weight:700;cursor:pointer;outline:none;box-shadow:0 1px 2px rgba(15,17,21,0.04);transition:border-color 0.15s,box-shadow 0.15s,background 0.15s;" onmouseover="this.style.borderColor='rgb(234, 88, 12)';this.style.background='#fffbf5'" onmouseout="if(!this._open){this.style.borderColor='#e8e8ec';this.style.background='#fff';}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg>
                <span id="dashDateBtnLabel">{{ $selectedCarbon->isToday() ? 'Today' : $selectedCarbon->format('d M Y') }}</span>
                <svg id="dashDateBtnCaret" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;transition:transform 0.15s;"><path d="M6 9l6 6 6-6"></path></svg>
            </button>
            <div id="dashDatePopover" style="display:none;position:absolute;top:calc(100% + 8px);right:0;background:#fff;border:1px solid #e5e7eb;border-radius:14px;box-shadow:0 12px 36px rgba(15,23,42,0.12), 0 2px 6px rgba(15,23,42,0.05);padding:14px;min-width:300px;z-index:100;">
                {{-- Mobile-only bottom-sheet handle + header (base display:none → revealed at ≤767px; desktop never shows these) --}}
                <div class="dash-sheet-handle"></div>
                <div class="dash-sheet-header">
                    <span class="dash-sheet-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg>
                        Select date range
                    </span>
                    <button type="button" class="dash-sheet-close" onclick="dashMobileCancel()" aria-label="Close">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                {{-- Scrollable body — ONLY this area scrolls; handle/header/footer stay fixed (mobile) --}}
                <div class="dash-sheet-body">
                {{-- Quick presets --}}
                <div class="dash-mini-label" style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:8px;">Quick select</div>
                <div class="dash-preset-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:14px;">
                    <button type="button" onclick="dashSetPreset('today')"     class="dash-preset-btn">Today</button>
                    <button type="button" onclick="dashSetPreset('yesterday')" class="dash-preset-btn">Yesterday</button>
                    <button type="button" onclick="dashSetPreset('last7')"     class="dash-preset-btn">Last 7 days</button>
                    <button type="button" onclick="dashSetPreset('last30')"    class="dash-preset-btn">Last 30 days</button>
                    <button type="button" onclick="dashSetPreset('thisMonth')" class="dash-preset-btn">This month</button>
                    <button type="button" onclick="dashSetPreset('lastMonth')" class="dash-preset-btn">Last month</button>
                </div>
                {{-- Custom range — single calendar with range selection (click start, click end) --}}
                <div class="dash-mini-label" style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:8px;">Custom range</div>
                {{-- Range summary pill — read-only display of the picked From → To range --}}
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <input type="hidden" id="dashDateFrom" value="{{ $selectedDate }}">
                    <input type="hidden" id="dashDateTo"   value="{{ $selectedDate }}">
                    <div id="dashRangeSummary" class="dash-range-summary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span id="dashRangeFromLabel" class="dash-range-label from-label">{{ $selectedCarbon->format('d M Y') }}</span>
                        <span class="dash-range-line"></span>
                        <span class="dash-range-sep">→</span>
                        <span class="dash-range-arrow"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"></path></svg></span>
                        <span class="dash-range-line"></span>
                        <span id="dashRangeToLabel"   class="dash-range-label to-label">{{ $selectedCarbon->format('d M Y') }}</span>
                    </div>
                </div>
                {{-- Calendar — always visible, supports range selection --}}
                <div id="dashCalWrap" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px;box-shadow:0 1px 3px rgba(15,23,42,0.03);">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <button type="button" onclick="dashCalNav(-1)" class="dash-cal-nav-btn" aria-label="Previous month">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        </button>
                        <div id="dashCalTitle" style="font-size:14px;font-weight:700;color:#0f172a;letter-spacing:-0.1px;"></div>
                        <button type="button" onclick="dashCalNav(1)" class="dash-cal-nav-btn" aria-label="Next month">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                    </div>
                    <div id="dashCalGrid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;"></div>
                    <div id="dashCalHint" style="font-size:11px;color:#94a3b8;text-align:center;margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;font-weight:500;">
                        Pick a start date, then an end date
                    </div>
                    <div class="dash-cal-actions" style="display:flex;justify-content:space-between;margin-top:6px;">
                        <button type="button" onclick="dashCalClear()" style="background:none;border:none;color:#64748b;font-size:12px;font-weight:600;cursor:pointer;outline:none;padding:4px 8px;border-radius:6px;">Clear</button>
                        <button type="button" onclick="dashCalToday()" style="background:none;border:none;color:rgb(234, 88, 12);font-size:12px;font-weight:700;cursor:pointer;outline:none;padding:4px 8px;border-radius:6px;">Today</button>
                    </div>
                </div>
                </div>{{-- /dash-sheet-body (scroll area ends) --}}

                {{-- Mobile-only Cancel / Apply footer — desktop is hidden via CSS (max-width:767px shows it) --}}
                <div id="dashMobileFooter" style="display:none;gap:10px;margin-top:14px;">
                    <button type="button" onclick="dashMobileCancel()" style="flex:1;height:44px;border-radius:10px;border:1.5px solid #e5e7eb;background:#ffffff;color:#475569;font-size:14px;font-weight:600;cursor:pointer;outline:none;">
                        Cancel
                    </button>
                    <button type="button" onclick="dashMobileApply()" style="flex:1;height:44px;border-radius:10px;border:none;background:rgb(234, 88, 12);color:#ffffff;font-size:14px;font-weight:700;cursor:pointer;outline:none;box-shadow:0 2px 6px rgba(234,88,12,0.30);display:inline-flex;align-items:center;justify-content:center;gap:8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                        Apply
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Row 1: Today Stats --}}
    <div class="dash-row-today" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:16px;margin-bottom:20px;">

        <div class="dash-card">
            <div class="dash-icon" style="background:#dcfce7;color:#15803d;box-shadow:0 2px 8px rgba(21,128,61,0.15);">
                <i class="fa fa-shopping-cart"></i>
            </div>
            <div>
                <div class="dash-label" id="dashSalesLabel">{{ $selectedCarbon->isToday() ? "Today's Sales" : $selectedCarbon->format('d M') . ' Sales' }}</div>
                <div class="dash-value" id="dashSalesValue" style="color:#15803d;">{{ $currency }} {{ number_format($salesToday) }}</div>
                <div class="dash-sub"><span id="dashSalesOrders">{{ $todayOrders }}</span> orders</div>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-icon" style="background:#dbeafe;color:#1d4ed8;box-shadow:0 2px 8px rgba(29,78,216,0.15);">
                <i class="fa fa-truck"></i>
            </div>
            <div>
                <div class="dash-label" id="dashPurchaseLabel">{{ $selectedCarbon->isToday() ? "Today's Purchases" : $selectedCarbon->format('d M') . ' Purchases' }}</div>
                <div class="dash-value" id="dashPurchaseValue" style="color:#1d4ed8;">{{ $currency }} {{ number_format($purchaseToday) }}</div>
                <div class="dash-sub">Stock purchased</div>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-icon" style="background:#FFF5ED;color:rgb(234, 88, 12);box-shadow:0 2px 8px rgba(234,88,12,0.15);">
                <i class="fa fa-shopping-bag"></i>
            </div>
            <div>
                <div class="dash-label">Total Products</div>
                <div class="dash-value">{{ $totalProducts }}</div>
                <div class="dash-sub">Active products</div>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-icon" style="background:#f5f3ff;color:#7c3aed;box-shadow:0 2px 8px rgba(124,58,237,0.15);">
                <i class="fa fa-file-text-o"></i>
            </div>
            <div>
                <div class="dash-label" id="dashOrdersLabel">{{ $selectedCarbon->isToday() ? "Today's Orders" : $selectedCarbon->format('d M') . ' Orders' }}</div>
                <div class="dash-value" id="dashOrdersValue" style="color:#7c3aed;">{{ $todayOrders }}</div>
                <div class="dash-sub">Invoices created</div>
            </div>
        </div>

    </div>

    {{-- Row 2: All Time Stats --}}
    <div class="dash-row-alltime" style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;margin-bottom:20px;">

        <div class="dash-card">
            <div class="dash-icon" style="background:#FFF5ED;color:rgb(234, 88, 12);box-shadow:0 2px 8px rgba(234,88,12,0.15);">
                <i class="fa fa-bar-chart"></i>
            </div>
            <div>
                <div class="dash-label"><i class="fa fa-bar-chart" style="margin-right:4px;font-size:9px;"></i> Total Sales</div>
                <div class="dash-value">{{ $currency }} {{ number_format($salesAllTime) }}</div>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-icon" style="background:#f0f9ff;color:#0ea5e9;box-shadow:0 2px 8px rgba(14,165,233,0.15);">
                <i class="fa fa-credit-card"></i>
            </div>
            <div>
                <div class="dash-label"><i class="fa fa-credit-card" style="margin-right:4px;font-size:9px;"></i> Total Purchases</div>
                <div class="dash-value">{{ $currency }} {{ number_format($purchaseAllTime) }}</div>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-icon" style="background:{{ $net >= 0 ? '#dcfce7' : '#fee2e2' }};color:{{ $net >= 0 ? '#15803d' : '#dc2626' }};box-shadow:0 2px 8px rgba(0,0,0,0.1);">
                <i class="fa fa-{{ $net >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
            </div>
            <div>
                <div class="dash-label"><i class="fa fa-{{ $net >= 0 ? 'arrow-up' : 'arrow-down' }}" style="margin-right:4px;font-size:9px;"></i> Net {{ $net >= 0 ? 'Profit' : 'Loss' }}</div>
                <div class="dash-value" style="color:{{ $net >= 0 ? '#15803d' : '#dc2626' }};">{{ $net >= 0 ? '+' : '' }}{{ $currency }} {{ number_format($net) }}</div>
            </div>
        </div>

    </div>

    {{-- Row 3: Chart + Latest Products --}}
    <div class="dash-bottom-grid" style="display:grid;grid-template-columns:1fr 380px;gap:20px;margin-bottom:20px;">

        {{-- Sales Chart (Dynamic) --}}
        <div class="dash-panel">
            <div class="dash-panel-header">
                <span class="dash-panel-title">Sales & Purchases</span>
                <div style="display:flex;align-items:center;gap:6px;">
                    {{-- Month custom dropdown --}}
                    <div id="monthDropWrap" style="position:relative;">
                        <button type="button" onclick="toggleMonthDrop(event)" class="dc-hdr-select" style="font-size:12px;padding:5px 22px 5px 10px;min-width:60px;text-align:left;outline:none !important;box-shadow:none !important;" id="monthDropBtn">{{ ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][now()->month - 1] }}</button>
                        <div id="monthDrop" style="display:none;position:absolute;top:calc(100% + 4px);left:0;z-index:100;background:#fff;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);border:1px solid #f0f0f0;max-height:180px;overflow-y:auto;min-width:90px;animation:dcFadeIn 0.15s;">
                            @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $i => $m)
                            <div onclick="selectMonth({{ $i + 1 }},'{{ $m }}',event)" style="padding:8px 14px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.1s;color:{{ ($i + 1) == now()->month ? '#fff' : '#374151' }};background:{{ ($i + 1) == now()->month ? 'rgb(234, 88, 12)' : 'transparent' }};border-radius:6px;"
                                onmouseover="if(!this.dataset.active){this.style.background='#FFF5ED';this.style.color='rgb(234, 88, 12)';}else{this.style.background='#c2410c';}"
                                onmouseout="if(!this.dataset.active){this.style.background='transparent';this.style.color='#374151';}else{this.style.background='rgb(234, 88, 12)';}"
                                data-val="{{ $i + 1 }}" {{ ($i + 1) == now()->month ? 'data-active=1' : '' }}>{{ $m }}</div>
                            @endforeach
                        </div>
                    </div>
                    <input type="hidden" id="chartMonth" value="{{ now()->month }}">
                    {{-- Year custom dropdown --}}
                    <div id="yearDropWrap" style="position:relative;">
                        <button type="button" onclick="toggleYearDrop(event)" class="dc-hdr-select" style="font-size:12px;padding:5px 22px 5px 10px;min-width:60px;text-align:left;outline:none !important;box-shadow:none !important;" id="yearDropBtn">{{ now()->year }}</button>
                        <div id="yearDrop" style="display:none;position:absolute;top:calc(100% + 4px);right:0;z-index:100;background:#fff;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,0.15);border:1px solid #f0f0f0;max-height:180px;overflow-y:auto;min-width:80px;animation:dcFadeIn 0.15s;">
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <div onclick="selectYear({{ $y }},event)" style="padding:8px 14px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.1s;color:{{ $y == now()->year ? '#fff' : '#374151' }};background:{{ $y == now()->year ? 'rgb(234, 88, 12)' : 'transparent' }};border-radius:{{ $y == now()->year ? '6px' : '0' }};"
                                onmouseover="if(!this.dataset.active)this.style.background='#FFF5ED';this.style.color='rgb(234, 88, 12)';"
                                onmouseout="if(!this.dataset.active){this.style.background='transparent';this.style.color='#374151';}"
                                data-val="{{ $y }}" {{ $y == now()->year ? 'data-active=1' : '' }}>{{ $y }}</div>
                            @endfor
                        </div>
                    </div>
                    <input type="hidden" id="chartYear" value="{{ now()->year }}">
                </div>
            </div>
            {{-- Legend --}}
            <div class="dash-chart-legend" style="padding:12px 24px 0;display:flex;align-items:center;gap:16px;">
                <div style="display:flex;align-items:center;gap:5px;"><div style="width:10px;height:10px;border-radius:3px;background:rgb(234, 88, 12);"></div><span style="font-size:11px;color:#64748b;font-weight:600;">Sales</span></div>
                <div style="display:flex;align-items:center;gap:5px;"><div style="width:10px;height:10px;border-radius:3px;background:#3b82f6;"></div><span style="font-size:11px;color:#64748b;font-weight:600;">Purchases</span></div>
            </div>
            <div id="chartContainer" class="dash-chart-area" style="padding:16px 24px 24px;display:flex;align-items:flex-end;gap:2px;height:240px;overflow-x:scroll;-webkit-overflow-scrolling:touch;">
                <div style="flex:1;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:13px;">Loading...</div>
            </div>
            <div class="chart-scroll-bar" id="chartScrollBar">
                <div class="chart-scroll-thumb" id="chartScrollThumb"></div>
            </div>
        </div>

        {{-- Latest Updated Products --}}
        <div class="dash-panel">
            <div class="dash-panel-header">
                <span class="dash-panel-title">Latest Products</span>
                <a href="/management/products/view/index" style="font-size:11px;font-weight:600;color:rgb(234, 88, 12);text-decoration:none;">View All →</a>
            </div>
            <div style="padding:4px 0;">
                @foreach($latestProducts as $prod)
                <a href="/product_history/view?product={{ $prod->id }}" class="dash-product-item" style="text-decoration:none;display:flex;align-items:center;gap:12px;padding:12px 24px;transition:background 0.1s;" onmouseover="this.style.background='#fefaf6'" onmouseout="this.style.background='transparent'">
                    <div class="dash-product-icon" style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,rgb(234, 88, 12),#fb923c);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 6px rgba(234,88,12,0.2);">
                        <i class="fa fa-cube" style="font-size:13px;color:#fff;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $prod->name }}</div>
                        <div style="font-size:11px;color:#94a3b8;margin-top:1px;">{{ $currency }} {{ $prod->selling_price ?? '—' }}</div>
                    </div>
                    <div style="flex-shrink:0;">
                        @if($prod->is_active)
                            <span style="background:#dcfce7;color:#15803d;border-radius:20px;padding:2px 8px;font-size:10px;font-weight:600;">Active</span>
                        @else
                            <span style="background:#fee2e2;color:#dc2626;border-radius:20px;padding:2px 8px;font-size:10px;font-weight:600;">Inactive</span>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
        </div>

        {{-- Recent Invoices — mobile only. Latest 6 invoices across all dates with Paid/Partial/Unpaid badges --}}
        <div class="dash-recent-invoices" style="display:none;">
            <div class="dash-panel" style="background:#fff;border:1px solid #eaecf2;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,0.06);overflow:hidden;">
                <div class="dash-panel-header" style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    <span class="dash-panel-title" style="font-size:14px;font-weight:800;color:#0f1115;">Recent invoices</span>
                    <a href="/data_entry/sales_entry/view" style="font-size:12px;font-weight:700;color:rgb(234, 88, 12);text-decoration:none;display:inline-flex;align-items:center;gap:4px;">See all <span style="font-size:13px;">›</span></a>
                </div>
                <div id="dashRecentInvoicesList" style="padding:6px 0;">
                    @php $recent = collect($recentInvoices ?? $invoices)->take(6); @endphp
                    @forelse($recent as $inv)
                        @php
                            $total = (float) (method_exists($inv, 'getRelation') && $inv->relationLoaded('product') && $inv->product ? $inv->product->sum('sub_total') : 0);
                            $paid  = (float) \DB::table('customer_payments')->where('customer_invoice_id', $inv->id)->sum('amount');
                            if ($paid <= 0)            { $stLabel='Unpaid';  $stBg='#fef2f2'; $stColor='#dc2626'; }
                            elseif ($paid < $total)    { $stLabel='Partial'; $stBg='#fffbeb'; $stColor='#d97706'; }
                            else                       { $stLabel='Paid';    $stBg='#f0fdf4'; $stColor='#16a34a'; }
                            $invNo = '#INV-' . ($inv->other_invoice_id ?: $inv->id);
                            $whenC = $inv->created_at ? \Carbon\Carbon::parse($inv->created_at) : null;
                            if ($whenC) {
                                if ($whenC->isToday())         { $when = 'Today'; }
                                elseif ($whenC->isYesterday()) { $when = 'Yesterday'; }
                                else                            { $when = $whenC->format('d M'); }
                            } else { $when = ''; }
                        @endphp
                        <a href="/data_entry/sales_entry/invoice/{{ $inv->id }}" style="display:flex;align-items:center;gap:12px;padding:10px 16px;text-decoration:none;color:inherit;">
                            <div style="width:36px;height:36px;border-radius:10px;background:#fafafb;border:1px solid #eef0f3;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#9ca3af;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"></path></svg>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <span style="font-size:13.5px;font-weight:700;color:#0f1115;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $inv->customer ? $inv->customer->name : 'Guest' }}</span>
                                    <span style="font-size:10.5px;font-weight:700;color:{{ $stColor }};background:{{ $stBg }};border-radius:999px;padding:2px 8px;white-space:nowrap;">{{ $stLabel }}</span>
                                </div>
                                <div style="font-size:11px;color:#94a3b8;margin-top:2px;font-weight:500;">{{ $invNo }} @if($when) · {{ $when }} @endif</div>
                            </div>
                            <div style="flex-shrink:0;text-align:right;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">
                                <div style="font-size:14px;font-weight:800;color:#0f1115;">{{ $currency }}{{ number_format($total, 2) }}</div>
                                @if($paid > 0 && $paid < $total)
                                    <div style="font-size:10.5px;font-weight:600;color:#16a34a;margin-top:1px;">Paid {{ $currency }}{{ number_format($paid, 2) }}</div>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div style="padding:24px 16px;text-align:center;font-size:13px;color:#9ca3af;">No invoices yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Recent Activity — mobile only. Live mix of payments + sales + product additions, styled like Recent Invoices. --}}
        <div class="dash-recent-invoices" style="display:none;margin-top:14px;">
            <div class="dash-panel" style="background:#fff;border:1px solid #eaecf2;border-radius:16px;box-shadow:0 1px 4px rgba(0,0,0,0.06);overflow:hidden;">
                <div class="dash-panel-header" style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #f1f5f9;">
                    <span class="dash-panel-title" style="font-size:14px;font-weight:800;color:#0f1115;">Recent activity</span>
                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;color:#16a34a;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:999px;padding:2px 8px;"><span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#16a34a;"></span>Live</span>
                </div>
                <div id="dashRecentActivityList" style="padding:6px 0;">
                    @forelse(($recentActivity ?? []) as $a)
                        @php
                            $iconBg='#fafafb'; $iconBd='#eef0f3'; $iconClr=$a['color']??'#6b7280';
                            $svg='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"></path></svg>';
                            switch($a['icon']??''){
                                case 'cash':    $svg='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>'; break;
                                case 'card':    $svg='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>'; break;
                                case 'bank':    $svg='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V10M9 21V10M15 21V10M19 21V10M3 10l9-7 9 7"/></svg>'; break;
                                case 'cheque':  $svg='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6"/></svg>'; break;
                                case 'credit':  $svg='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>'; break;
                                case 'sale':    $svg='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>'; break;
                                case 'product': $svg='<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>'; break;
                            }
                            $amtTxt = ($a['amount']??0) > 0 ? $currency.number_format($a['amount'], 2) : '';
                            // Convert bg colour to a light tint for the pill (e.g. #16a34a -> #f0fdf4 style)
                            $pillBgMap = ['#16a34a'=>'#f0fdf4', '#1d4ed8'=>'#eff6ff', '#0e7490'=>'#ecfeff', '#6d28d9'=>'#f5f3ff', 'rgb(234, 88, 12)'=>'#fff7ed'];
                            $pillBg = $pillBgMap[$iconClr] ?? '#fafafb';
                        @endphp
                        <a href="{{ $a['link'] ?? '#' }}" style="display:flex;align-items:center;gap:14px;padding:14px 16px;text-decoration:none;color:inherit;border-bottom:1px solid #f5f6f8;">
                            <div style="width:42px;height:42px;border-radius:12px;background:{{ $iconBg }};border:1px solid {{ $iconBd }};display:flex;align-items:center;justify-content:center;flex-shrink:0;color:{{ $iconClr }};">{!! $svg !!}</div>
                            <div style="flex:1;min-width:0;">
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <span style="font-size:15px;font-weight:800;color:#0f1115;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $a['headline'] ?? ($a['subject'] ?? '') }}</span>
                                    <span style="display:inline-block;font-size:10.5px;font-weight:700;color:{{ $iconClr }};background:{{ $pillBg }};border-radius:999px;padding:2px 9px;white-space:nowrap;line-height:1.4;">{{ $a['pill'] ?? $a['title'] }}</span>
                                </div>
                                <div style="font-size:11.5px;color:#94a3b8;margin-top:4px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $a['ref'] ?? '' }}@if(!empty($a['when'])) · {{ $a['when'] }} @endif</div>
                            </div>
                            @if($amtTxt)
                                <div style="flex-shrink:0;font-size:15px;font-weight:800;color:#0f1115;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">{{ $amtTxt }}</div>
                            @endif
                        </a>
                    @empty
                        <div style="padding:24px 16px;text-align:center;font-size:13px;color:#9ca3af;">No recent activity.</div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>


</div>

<script>
// ── Dashboard Date Filter — real-time AJAX updates for Today's Sales / Purchases / Orders ──
(function(){
    const DASH_CURRENCY = '{{ $currency }}';
    const DASH_STATS_URL = '/dashboard/view/stats';
    const wrap     = document.getElementById('dashDateFilterWrap');
    const btn      = document.getElementById('dashDateBtn');
    const btnLabel = document.getElementById('dashDateBtnLabel');
    const caret    = document.getElementById('dashDateBtnCaret');
    const pop      = document.getElementById('dashDatePopover');
    const fromInp  = document.getElementById('dashDateFrom');
    const toInp    = document.getElementById('dashDateTo');

    // Backdrop only matters on mobile (CSS hides it on desktop because the popover positions normally)
    let backdrop = null;
    function isMobile() { return window.matchMedia('(max-width: 767px)').matches; }

    function openPopover() {
        pop.style.display = 'block';
        btn._open = true;
        btn.style.borderColor = 'rgb(234, 88, 12)';
        btn.style.background = '#fffbf5';
        caret.style.transform = 'rotate(180deg)';
        if (isMobile()) {
            backdrop = document.createElement('div');
            backdrop.id = 'dashDatePopoverBackdrop';
            backdrop.addEventListener('click', closePopover);
            document.body.appendChild(backdrop);
            // force reflow so the backdrop fade + sheet slide-up run from their start state
            void backdrop.offsetWidth;
            void pop.offsetWidth;
            backdrop.classList.add('open');
            pop.classList.add('dash-sheet-open');   // slides the sheet up from the bottom
        }
        document.addEventListener('click', onOutsideClick, true);
        dashSyncActivePreset();
    }
    // Mobile only: highlight the quick-select preset matching the current range when the sheet opens.
    // Desktop always has the class cleared (returns before adding), so its appearance is unchanged.
    function dashSyncActivePreset() {
        const presets = pop.querySelectorAll('.dash-preset-btn');
        presets.forEach(function(b){ b.classList.remove('active'); });
        if (!isMobile()) return;
        const from = fromInp.value, to = toInp.value, t = todayISO();
        const ranges = [
            [t, t],
            [isoDaysAgo(1), isoDaysAgo(1)],
            [isoDaysAgo(6), t],
            [isoDaysAgo(29), t],
            [isoMonthStart(0), t],
            [isoMonthStart(-1), isoMonthEnd(-1)],
        ];
        for (let i = 0; i < ranges.length; i++) {
            if (from === ranges[i][0] && to === ranges[i][1]) { if (presets[i]) presets[i].classList.add('active'); break; }
        }
    }
    function closePopover() {
        btn._open = false;
        btn.style.borderColor = '#e5e7eb';
        btn.style.background = '#fff';
        caret.style.transform = '';
        document.removeEventListener('click', onOutsideClick, true);
        const bd = backdrop; backdrop = null;
        if (isMobile() && pop.classList.contains('dash-sheet-open')) {
            // slide the sheet back down, fade the backdrop, then hide once the animation ends
            pop.classList.remove('dash-sheet-open');
            if (bd) bd.classList.remove('open');
            const p = pop;
            window.setTimeout(function() {
                if (!p.classList.contains('dash-sheet-open')) p.style.display = 'none';
                if (bd && bd.parentNode) bd.parentNode.removeChild(bd);
            }, 300);
        } else {
            pop.classList.remove('dash-sheet-open');
            pop.style.display = 'none';
            if (bd && bd.parentNode) bd.parentNode.removeChild(bd);
        }
    }
    function onOutsideClick(e) {
        if (wrap.contains(e.target)) return;
        if (backdrop && backdrop.contains(e.target)) return;
        closePopover();
    }
    window.dashToggleDatePicker = function(e) { e && e.stopPropagation(); btn._open ? closePopover() : openPopover(); };
    window.dashCloseDatePicker  = closePopover;

    function todayISO() { return new Date().toISOString().slice(0,10); }
    function isoDaysAgo(n) { const d = new Date(); d.setDate(d.getDate() - n); return d.toISOString().slice(0,10); }
    function isoMonthStart(offset) { const d = new Date(); d.setMonth(d.getMonth() + offset, 1); return d.toISOString().slice(0,10); }
    function isoMonthEnd(offset) { const d = new Date(); d.setMonth(d.getMonth() + offset + 1, 0); return d.toISOString().slice(0,10); }

    window.dashSetPreset = function(key) {
        let from, to;
        const t = todayISO();
        switch (key) {
            case 'today':     from = t;            to = t;            break;
            case 'yesterday': from = isoDaysAgo(1); to = from;        break;
            case 'last7':     from = isoDaysAgo(6); to = t;           break;
            case 'last30':    from = isoDaysAgo(29);to = t;           break;
            case 'thisMonth': from = isoMonthStart(0); to = t;        break;
            case 'lastMonth': from = isoMonthStart(-1); to = isoMonthEnd(-1); break;
            default: return;
        }
        fromInp.value = from; toInp.value = to;
        applyRange(from, to);
        closePopover();
    };

    window.dashApplyCustomRange = function() {
        let from = fromInp.value, to = toInp.value;
        if (!from && !to) return;
        if (!from) from = to; if (!to) to = from;
        if (from > to) { const tmp = from; from = to; to = tmp; fromInp.value = from; toInp.value = to; }
        applyRange(from, to);
        closePopover();
    };

    function fmt(n) { return Number(n || 0).toLocaleString(); }
    function setBusy(on) {
        ['dashSalesValue','dashPurchaseValue','dashOrdersValue','dashSalesOrders'].forEach(id => {
            const el = document.getElementById(id); if (el) el.style.opacity = on ? '0.5' : '1';
        });
        btn.disabled = on; btn.style.cursor = on ? 'wait' : 'pointer';
    }

    function applyRange(from, to, opts) {
        const silent = opts && opts.silent;
        if (!silent) setBusy(true);
        fetch(DASH_STATS_URL + '?from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to), { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
            .then(r => r.text().then(text => {
                try { return JSON.parse(text); }
                catch (e) {
                    // Server returned HTML (an exception page or login redirect) — log a small snippet so we can debug.
                    console.warn('Dashboard stats: non-JSON response (' + r.status + ')', text.slice(0, 200));
                    throw new Error('non-JSON response ' + r.status);
                }
            }))
            .then(res => {
                if (!res.success) {
                    console.warn('Dashboard stats error:', res.error || 'bad response', res.where ? '@' + res.where : '');
                    throw new Error(res.error || 'bad response');
                }
                const p = res.payload;
                const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
                setText('dashSalesValue',    DASH_CURRENCY + ' ' + fmt(p.sales_value));
                setText('dashPurchaseValue', DASH_CURRENCY + ' ' + fmt(p.purchase_value));
                setText('dashOrdersValue',   fmt(p.orders_count));
                setText('dashSalesOrders',   fmt(p.orders_count));
                if (p.is_today) {
                    setText('dashSalesLabel',    "Today's Sales");
                    setText('dashPurchaseLabel', "Today's Purchases");
                    setText('dashOrdersLabel',   "Today's Orders");
                } else if (p.is_single_day) {
                    setText('dashSalesLabel',    p.label + ' Sales');
                    setText('dashPurchaseLabel', p.label + ' Purchases');
                    setText('dashOrdersLabel',   p.label + ' Orders');
                } else {
                    // Range — keep card labels short so they don't overflow the small stat cards.
                    setText('dashSalesLabel',    'Range Sales');
                    setText('dashPurchaseLabel', 'Range Purchases');
                    setText('dashOrdersLabel',   'Range Orders');
                }
                const desktopLabel = p.is_today ? 'Today'
                    : (p.is_single_day ? (new Date(p.from).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}))
                    : (new Date(p.from).toLocaleDateString('en-GB',{day:'2-digit',month:'short'}) + ' – ' + new Date(p.to).toLocaleDateString('en-GB',{day:'2-digit',month:'short'})));
                if (btnLabel) btnLabel.textContent = desktopLabel;
                // Shorter label for the mobile button (no year)
                const mobLabel = p.is_today ? 'Today'
                    : (p.is_single_day ? (new Date(p.from).toLocaleDateString('en-GB',{day:'2-digit',month:'short'}))
                    : (new Date(p.from).toLocaleDateString('en-GB',{day:'2-digit',month:'short'}) + '–' + new Date(p.to).toLocaleDateString('en-GB',{day:'2-digit',month:'short'})));
                const mobLabelEl = document.getElementById('dashMobDateBtnLabel');
                if (mobLabelEl) mobLabelEl.textContent = mobLabel;
                // Mobile hero card + recent invoices + recent activity (silent during periodic refresh)
                updateHeroCard(p);
                updateRecentInvoices(p.recent_invoices || []);
                updateRecentActivity(p.recent_activity || []);
            })
            .catch(err => { console.error('Dashboard stats fetch failed', err); })
            .finally(() => { if (!silent) setBusy(false); });
    }

    // ── Mobile hero card live updater ──
    function updateHeroCard(p) {
        const hero = document.getElementById('dashHeroCard');
        if (!hero) return;
        const sales = Number(p.sales_value) || 0;
        const yesterday = Number(p.yesterday_value) || 0;
        const delta = sales - yesterday;
        const deltaPct = yesterday > 0 ? Math.round(((sales - yesterday) / yesterday) * 1000) / 10 : 0;

        const intPart = Math.trunc(sales);
        const decPart = Math.round((sales - intPart) * 100).toString().padStart(2, '0');
        const intEl = document.getElementById('dashHeroIntPart');
        const decEl = document.getElementById('dashHeroDecPart');
        if (intEl) intEl.textContent = intPart.toLocaleString('en-GB');
        if (decEl) decEl.textContent = '.' + decPart;

        const labelEl = document.getElementById('dashHeroLabel');
        // Keep the pill label short — long ranges would crowd the orange card.
        if (labelEl) labelEl.textContent = p.is_today ? "Today's sales" : (p.is_single_day ? "Sales · " + (p.label || '') : 'Sales');

        const pill = document.getElementById('dashHeroDeltaPill');
        const pillTxt = document.getElementById('dashHeroDeltaPillText');
        if (pill && pillTxt) {
            if (deltaPct !== 0 && p.is_today) {
                pill.style.display = 'inline-flex';
                pillTxt.textContent = (deltaPct >= 0 ? '↑ ' : '↓ ') + Math.abs(deltaPct) + '%';
            } else {
                pill.style.display = 'none';
            }
        }

        const sub = document.getElementById('dashHeroSub');
        if (sub) {
            const orders = Number(p.orders_count) || 0;
            const parts = [];
            if (delta !== 0 && p.is_today) {
                parts.push((delta >= 0 ? '+' : '-') + DASH_CURRENCY + Math.round(Math.abs(delta)).toLocaleString('en-GB') + ' vs yesterday');
            }
            if (orders) {
                parts.push(orders + (orders === 1 ? ' invoice' : ' invoices'));
            }
            sub.textContent = parts.join(' · ');
        }

        // Sparkline
        const spark = document.getElementById('dashHeroSpark');
        const sparkLine = document.getElementById('dashHeroSparkLine');
        const sparkArea = document.getElementById('dashHeroSparkArea');
        const chart = Array.isArray(p.sales_chart) ? p.sales_chart : [];
        if (spark && sparkLine && sparkArea && chart.length) {
            const vb = spark.getAttribute('viewBox').split(' ').map(Number);
            const W = vb[2] || 320, H = vb[3] || 60;
            const values = chart.map(d => Number(d.amount) || 0);
            const max = Math.max.apply(null, values) || 1;
            const n = Math.max(values.length - 1, 1);
            const pts = values.map((v, i) => {
                const x = Math.round((i / n) * W * 10) / 10;
                const y = Math.round((H - (v / max) * (H - 8) - 4) * 10) / 10;
                return x + ',' + y;
            });
            sparkLine.setAttribute('points', pts.join(' '));
            sparkArea.setAttribute('points', '0,' + H + ' ' + pts.join(' ') + ' ' + W + ',' + H);
            spark.style.display = 'block';
        }
    }

    function escapeHtml(str) { return String(str == null ? '' : str).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch])); }

    function updateRecentInvoices(items) {
        const list = document.getElementById('dashRecentInvoicesList');
        if (!list) return;
        if (!items.length) {
            list.innerHTML = '<div style="padding:24px 16px;text-align:center;font-size:13px;color:#9ca3af;">No invoices yet.</div>';
            return;
        }
        const html = items.map(it => {
            let stBg, stColor;
            if (it.status === 'Paid')         { stBg = '#f0fdf4'; stColor = '#16a34a'; }
            else if (it.status === 'Partial') { stBg = '#fffbeb'; stColor = '#d97706'; }
            else                              { stBg = '#fef2f2'; stColor = '#dc2626'; }
            const totalNum = Number(it.total || 0);
            const paidNum  = Number(it.paid  || 0);
            const amount = totalNum.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const paidStr = paidNum.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const paidLine = (paidNum > 0 && paidNum < totalNum)
                ? '<div style="font-size:10.5px;font-weight:600;color:#16a34a;margin-top:1px;">Paid ' + DASH_CURRENCY + paidStr + '</div>'
                : '';
            return '<a href="/data_entry/sales_entry/invoice/' + encodeURIComponent(it.id) + '" style="display:flex;align-items:center;gap:12px;padding:10px 16px;text-decoration:none;color:inherit;">'
                + '<div style="width:36px;height:36px;border-radius:10px;background:#fafafb;border:1px solid #eef0f3;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#9ca3af;">'
                + '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>'
                + '</div>'
                + '<div style="flex:1;min-width:0;">'
                + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">'
                + '<span style="font-size:13.5px;font-weight:700;color:#0f1115;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(it.customer) + '</span>'
                + '<span style="font-size:10.5px;font-weight:700;color:' + stColor + ';background:' + stBg + ';border-radius:999px;padding:2px 8px;white-space:nowrap;">' + escapeHtml(it.status) + '</span>'
                + '</div>'
                + '<div style="font-size:11px;color:#94a3b8;margin-top:2px;font-weight:500;">' + escapeHtml(it.invoice_no) + (it.when ? ' · ' + escapeHtml(it.when) : '') + '</div>'
                + '</div>'
                + '<div style="flex-shrink:0;text-align:right;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">'
                + '<div style="font-size:14px;font-weight:800;color:#0f1115;">' + DASH_CURRENCY + amount + '</div>'
                + paidLine
                + '</div>'
                + '</a>';
        }).join('');
        list.innerHTML = html;
    }

    // ── Recent activity feed (mixed payments + sales + products) ──
    const ACTIVITY_SVGS = {
        cash:    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>',
        card:    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>',
        bank:    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V10M9 21V10M15 21V10M19 21V10M3 10l9-7 9 7"/></svg>',
        cheque:  '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6"/></svg>',
        credit:  '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>',
        sale:    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>',
        product: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
        payment: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>',
    };
    const PILL_BG_MAP = { '#16a34a': '#f0fdf4', '#1d4ed8': '#eff6ff', '#0e7490': '#ecfeff', '#6d28d9': '#f5f3ff', 'rgb(234, 88, 12)': '#fff7ed' };
    function updateRecentActivity(items) {
        const list = document.getElementById('dashRecentActivityList');
        if (!list) return;
        if (!items.length) {
            list.innerHTML = '<div style="padding:24px 16px;text-align:center;font-size:13px;color:#9ca3af;">No recent activity.</div>';
            return;
        }
        const html = items.map(it => {
            const svg = ACTIVITY_SVGS[it.icon] || ACTIVITY_SVGS.payment;
            const color = it.color || '#6b7280';
            const pillBg = PILL_BG_MAP[color] || '#fafafb';
            const amt = Number(it.amount || 0);
            const amtTxt = amt > 0 ? DASH_CURRENCY + amt.toLocaleString('en-GB', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
            const headline = it.headline || it.subject || '';
            const pill = it.pill || it.title || '';
            const ref = it.ref || '';
            return '<a href="' + escapeHtml(it.link || '#') + '" style="display:flex;align-items:center;gap:14px;padding:14px 16px;text-decoration:none;color:inherit;border-bottom:1px solid #f5f6f8;">'
                + '<div style="width:42px;height:42px;border-radius:12px;background:#fafafb;border:1px solid #eef0f3;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:' + color + ';">' + svg + '</div>'
                + '<div style="flex:1;min-width:0;">'
                + '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">'
                + '<span style="font-size:15px;font-weight:800;color:#0f1115;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(headline) + '</span>'
                + '<span style="display:inline-block;font-size:10.5px;font-weight:700;color:' + color + ';background:' + pillBg + ';border-radius:999px;padding:2px 9px;white-space:nowrap;line-height:1.4;">' + escapeHtml(pill) + '</span>'
                + '</div>'
                + '<div style="font-size:11.5px;color:#94a3b8;margin-top:4px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + escapeHtml(ref) + (it.when ? ' · ' + escapeHtml(it.when) : '') + '</div>'
                + '</div>'
                + (amtTxt ? '<div style="flex-shrink:0;font-size:15px;font-weight:800;color:#0f1115;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;">' + amtTxt + '</div>' : '')
                + '</a>';
        }).join('');
        list.innerHTML = html;
    }

    // ── Client-side greeting (uses browser local time so it matches the user's actual hour) ──
    function updateGreetingAndDate() {
        const wordEl = document.getElementById('dashHeroGreetWord');
        if (wordEl) {
            const h = new Date().getHours();
            wordEl.textContent = h < 12 ? 'Good morning' : (h < 17 ? 'Good afternoon' : 'Good evening');
        }
        const dateEl = document.getElementById('dashHeroDate');
        if (dateEl) {
            const d = new Date();
            const day  = d.toLocaleDateString('en-GB', { weekday: 'long' });
            const dnum = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
            dateEl.textContent = day + ' · ' + dnum;
        }
    }
    updateGreetingAndDate();
    // Re-evaluate every minute so the greeting flips at noon/5pm even if the tab stays open.
    setInterval(updateGreetingAndDate, 60000);

    // ── Stats load ONCE on page load — per-second polling removed (it hit
    //    /dashboard/view/stats every second per open dashboard tab, loading the
    //    server and slowing other pages). This ONLY changes WHEN stats are
    //    fetched — it does not touch any layout/design. The date-range picker
    //    below still fetches on demand when the user changes the range.
    (function loadDashboardStatsOnce() {
        const f = (fromInp && fromInp.value) || todayISO();
        const t = (toInp && toInp.value) || f;
        applyRange(f, t, { silent: true });
    })();

    // ── Custom range calendar — single picker, two-click range selection ──
    const calTitle    = document.getElementById('dashCalTitle');
    const calGrid     = document.getElementById('dashCalGrid');
    const calHint     = document.getElementById('dashCalHint');
    const rangeSum    = document.getElementById('dashRangeSummary');
    const rangeFromEl = document.getElementById('dashRangeFromLabel');
    const rangeToEl   = document.getElementById('dashRangeToLabel');

    let dashCalViewYear, dashCalViewMonth; // currently displayed month (0-indexed)
    // Range selection state:
    //   pendingStart = ISO string when user has clicked the first date but not yet the second
    //   null = next click starts a new range
    let pendingStart = null;
    const MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const DOW_LABELS  = ['Su','Mo','Tu','We','Th','Fr','Sa'];

    function parseISO(s) {
        if (!s) return null;
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
        if (!m) return null;
        return new Date(+m[1], +m[2] - 1, +m[3]);
    }
    function toISO(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + dd;
    }
    function fmtDisplay(iso) {
        const d = parseISO(iso); if (!d) return '—';
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }
    function syncSummary() {
        if (pendingStart) {
            // Mid-selection: From shows the pending start, To shows a placeholder
            rangeFromEl.textContent = fmtDisplay(pendingStart);
            rangeToEl.textContent   = 'Pick end date';
            rangeSum.classList.add('pending');
            calHint.textContent     = 'Click the end date to complete the range';
        } else {
            rangeFromEl.textContent = fmtDisplay(fromInp.value);
            rangeToEl.textContent   = fmtDisplay(toInp.value);
            rangeSum.classList.remove('pending');
            calHint.textContent     = 'Pick a start date, then an end date';
        }
    }

    window.dashCalNav = function(delta) {
        dashCalViewMonth += delta;
        if (dashCalViewMonth < 0)  { dashCalViewMonth = 11; dashCalViewYear--; }
        if (dashCalViewMonth > 11) { dashCalViewMonth = 0;  dashCalViewYear++; }
        renderCal();
    };

    window.dashCalClear = function() {
        fromInp.value = '';
        toInp.value   = '';
        pendingStart  = null;
        syncSummary();
        renderCal();
    };

    window.dashCalToday = function() {
        const t = new Date();
        dashCalViewYear  = t.getFullYear();
        dashCalViewMonth = t.getMonth();
        // Today preset: from=today, to=today (single day range)
        const iso = toISO(t);
        fromInp.value = iso;
        toInp.value   = iso;
        pendingStart  = null;
        syncSummary();
        renderCal();
        applyRange(iso, iso);
    };

    // Mobile detection (re-checked at each pick — handles resize)
    function _isMobileViewport() { return window.matchMedia('(max-width: 767px)').matches; }

    // Two-click range picker:
    //   - 1st click  → set pendingStart, summary shows "Pick end date"
    //   - 2nd click  → set range (auto-orders if user clicks an earlier date), then:
    //                    • Desktop: auto-apply (fetch stats + close popover) — same as before
    //                    • Mobile : update From/To only; user must press "Apply" to commit
    //                               (gives the user a final chance to review before fetch)
    function pickDate(d) {
        const iso = toISO(d);
        if (!pendingStart) {
            // First click — start a new range
            pendingStart = iso;
            syncSummary();
            renderCal();
        } else {
            // Second click — complete the range
            let from = pendingStart, to = iso;
            if (from > to) { const tmp = from; from = to; to = tmp; }
            fromInp.value = from;
            toInp.value   = to;
            pendingStart  = null;
            syncSummary();
            renderCal();
            if (_isMobileViewport()) {
                // Mobile: wait for explicit Apply tap — don't auto-commit/close
                return;
            }
            applyRange(from, to);
            // Auto-close the popover so user sees the result
            setTimeout(function() { if (typeof closePopover === 'function') closePopover(); }, 220);
        }
    }

    // Mobile-only footer buttons
    window.dashMobileApply = function() {
        // If the user tapped a single date (start only, no end yet), apply it as a single-day
        // custom selection instead of ignoring it.
        if (pendingStart) {
            fromInp.value = pendingStart;
            toInp.value   = pendingStart;
            pendingStart  = null;
        }
        let from = fromInp.value, to = toInp.value;
        if (!from && !to) {
            // Nothing picked yet — just close
            closePopover();
            return;
        }
        if (!from) from = to;
        if (!to) to = from;
        if (from > to) { const tmp = from; from = to; to = tmp; fromInp.value = from; toInp.value = to; }
        pendingStart = null;
        syncSummary();
        applyRange(from, to);
        closePopover();
    };
    window.dashMobileCancel = function() {
        // Discard any in-progress range selection; revert summary to last committed state
        pendingStart = null;
        syncSummary();
        renderCal();
        closePopover();
    };

    function renderCal() {
        calTitle.textContent = MONTH_NAMES[dashCalViewMonth] + ' ' + dashCalViewYear;
        const today = new Date(); today.setHours(0,0,0,0);

        // Determine current selection for highlighting
        let rangeFrom = null, rangeTo = null;
        if (pendingStart) {
            rangeFrom = pendingStart;
            rangeTo   = pendingStart;
        } else {
            rangeFrom = fromInp.value || null;
            rangeTo   = toInp.value   || null;
        }

        // Build grid: leading muted days from previous month, current month days, trailing muted days
        const firstOfMonth = new Date(dashCalViewYear, dashCalViewMonth, 1);
        const startDow = firstOfMonth.getDay(); // 0=Sun
        const daysInMonth = new Date(dashCalViewYear, dashCalViewMonth + 1, 0).getDate();
        const prevMonthDays = new Date(dashCalViewYear, dashCalViewMonth, 0).getDate();

        let html = '';
        DOW_LABELS.forEach(function(d) { html += '<div class="dash-cal-dow">' + d + '</div>'; });
        for (let i = startDow - 1; i >= 0; i--) {
            const day = prevMonthDays - i;
            const cellDate = new Date(dashCalViewYear, dashCalViewMonth - 1, day);
            html += renderCell(cellDate, true, today, rangeFrom, rangeTo);
        }
        for (let d = 1; d <= daysInMonth; d++) {
            const cellDate = new Date(dashCalViewYear, dashCalViewMonth, d);
            html += renderCell(cellDate, false, today, rangeFrom, rangeTo);
        }
        const used = 7 + startDow + daysInMonth;
        const trailing = 49 - used;
        for (let d = 1; d <= trailing; d++) {
            const cellDate = new Date(dashCalViewYear, dashCalViewMonth + 1, d);
            html += renderCell(cellDate, true, today, rangeFrom, rangeTo);
        }
        calGrid.innerHTML = html;

        calGrid.querySelectorAll('.dash-cal-day:not(.disabled)').forEach(function(b) {
            b.addEventListener('click', function() {
                const iso = b.getAttribute('data-iso');
                const d = parseISO(iso);
                if (d) {
                    dashCalViewYear = d.getFullYear();
                    dashCalViewMonth = d.getMonth();
                    pickDate(d);
                }
            });
        });
    }

    function renderCell(cellDate, isMuted, today, rangeFrom, rangeTo) {
        const iso = toISO(cellDate);
        const isToday = cellDate.getTime() === today.getTime();
        const isFuture = cellDate.getTime() > today.getTime();
        const cls = ['dash-cal-day'];
        if (isMuted)   cls.push('muted');
        if (isToday)   cls.push('today');
        if (isFuture)  cls.push('disabled');
        // Range highlighting
        if (rangeFrom && rangeTo) {
            const isStart = iso === rangeFrom;
            const isEnd   = iso === rangeTo;
            if (isStart && isEnd) {
                cls.push('selected');
            } else if (isStart) {
                cls.push('selected', 'range-start');
            } else if (isEnd) {
                cls.push('selected', 'range-end');
            } else if (iso > rangeFrom && iso < rangeTo) {
                cls.push('in-range');
            }
        }
        return '<button type="button" class="' + cls.join(' ') + '" data-iso="' + iso + '">' + cellDate.getDate() + '</button>';
    }

    // Initial seed — show the month of the currently selected From date
    (function initCal() {
        const seedISO = fromInp.value || toISO(new Date());
        const seed    = parseISO(seedISO) || new Date();
        dashCalViewYear  = seed.getFullYear();
        dashCalViewMonth = seed.getMonth();
        syncSummary();
        renderCal();
    })();

    // Esc closes the popover and discards any pending start
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && btn._open) {
            pendingStart = null;
            syncSummary();
            renderCal();
            closePopover();
        }
    });
})();
</script>

<script>
// ── Sales/Purchases Chart ──
(function(){
    const container = document.getElementById('chartContainer');
    const monthSel = document.getElementById('chartMonth');
    const yearSel = document.getElementById('chartYear');
    const currency = '{{ $currency }}';

    function loadChart() {
        const m = monthSel.value, y = yearSel.value;
        container.innerHTML = '<div style="flex:1;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:13px;">Loading...</div>';
        fetch('/dashboard/view/chart-data?month=' + m + '&year=' + y)
            .then(r => r.json())
            .then(res => {
                if (!res.success || !res.payload.length) {
                    container.innerHTML = '<div style="flex:1;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:13px;">No data</div>';
                    return;
                }
                const data = res.payload;
                window._chartData = data;
                const maxVal = Math.max(...data.map(d => Math.max(d.sales, d.purchases))) || 1;
                // Tooltip
                let tip = document.getElementById('chartTip');
                if (!tip) {
                    tip = document.createElement('div');
                    tip.id = 'chartTip';
                    tip.style.cssText = 'position:fixed;z-index:99999;background:#1e293b;border-radius:12px;padding:14px 18px;pointer-events:none;opacity:0;transition:opacity 0.12s;min-width:200px;box-shadow:0 12px 32px rgba(0,0,0,0.25);';
                    document.body.appendChild(tip);
                }
                let html = '';
                data.forEach((d, i) => {
                    const colW = data.length > 20 ? 28 : 36;
                    if (d.future) {
                        html += '<div style="flex:0 0 '+colW+'px;min-width:'+colW+'px;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;padding:4px 0;opacity:0.3;">';
                        html += '<div style="flex:1;"></div>';
                        html += '<div style="font-size:10px;font-weight:600;color:#cbd5e1;line-height:1;">'+d.day+'</div>';
                        html += '</div>';
                    } else {
                        const sh = Math.max(3, (d.sales / maxVal) * 100);
                        const ph = Math.max(3, (d.purchases / maxVal) * 100);
                        html += '<div class="chart-col" data-idx="'+i+'" style="flex:0 0 '+colW+'px;min-width:'+colW+'px;">';
                        html += '<div style="flex:1;width:100%;display:flex;align-items:flex-end;justify-content:center;gap:2px;">';
                        html += '<div class="chart-bar-s" style="width:45%;max-width:20px;height:'+sh+'%;border-radius:6px 6px 0 0;background:rgb(234, 88, 12);opacity:'+(d.sales>0?1:0.12)+';transition:all 0.3s;transform-origin:bottom;"></div>';
                        html += '<div class="chart-bar-p" style="width:45%;max-width:20px;height:'+ph+'%;border-radius:6px 6px 0 0;background:linear-gradient(to top,#3b82f6,#60a5fa);opacity:'+(d.purchases>0?1:0.12)+';transition:all 0.3s;transform-origin:bottom;"></div>';
                        html += '</div>';
                        html += '<div class="chart-day" style="font-size:10px;font-weight:700;color:#94a3b8;line-height:1;transition:all 0.15s;">'+d.day+'</div>';
                        html += '</div>';
                    }
                });
                container.innerHTML = html;

                // Attach events via JS (not inline) to avoid JSON encoding issues
                container.querySelectorAll('.chart-col').forEach(col => {
                    const idx = parseInt(col.dataset.idx);
                    col.addEventListener('mouseenter', function(e) {
                        const d = window._chartData[idx];
                        if (!d) return;
                        const profit = d.sales - d.purchases;
                        const isP = profit >= 0;
                        tip.innerHTML =
                            '<div style="font-size:11px;font-weight:600;color:#94a3b8;margin-bottom:10px;">' + d.label + ', ' + d.day + '</div>' +
                            '<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;"><div style="width:8px;height:8px;border-radius:3px;background:rgb(234, 88, 12);flex-shrink:0;"></div><div style="flex:1;font-size:12px;color:#94a3b8;">Sales</div><div style="font-size:13px;font-weight:700;color:rgb(234, 88, 12);">' + currency + ' ' + d.sales.toLocaleString() + '</div></div>' +
                            '<div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;"><div style="width:8px;height:8px;border-radius:3px;background:#3b82f6;flex-shrink:0;"></div><div style="flex:1;font-size:12px;color:#94a3b8;">Purchases</div><div style="font-size:13px;font-weight:700;color:#60a5fa;">' + currency + ' ' + d.purchases.toLocaleString() + '</div></div>' +
                            '<div style="border-top:1px solid #334155;padding-top:8px;display:flex;align-items:center;justify-content:space-between;">' +
                            '<span style="font-size:11px;font-weight:700;color:#94a3b8;">' + (isP ? '▲ Profit' : '▼ Loss') + '</span>' +
                            '<span style="font-size:14px;font-weight:800;color:' + (isP ? '#4ade80' : '#f87171') + ';">' + (isP ? '+' : '') + currency + ' ' + profit.toLocaleString() + '</span></div>';
                        tip.style.opacity = '1';
                    });
                    col.addEventListener('mousemove', function(e) {
                        const isMobile = window.innerWidth <= 767;
                        if (isMobile) {
                            tip.style.left = '50%';
                            tip.style.transform = 'translateX(-50%)';
                            tip.style.top = (e.clientY - 120) + 'px';
                        } else {
                            tip.style.left = (e.clientX + 16) + 'px';
                            tip.style.transform = 'none';
                            tip.style.top = (e.clientY - 90) + 'px';
                        }
                    });
                    col.addEventListener('mouseleave', function() {
                        tip.style.opacity = '0';
                    });
                });
            })
            .catch(() => {
                container.innerHTML = '<div style="flex:1;display:flex;align-items:center;justify-content:center;color:#dc2626;font-size:13px;">Error loading data</div>';
            });
    }

    loadChart();

    // Custom orange scrollbar
    const chartEl = document.getElementById('chartContainer');
    const scrollBar = document.getElementById('chartScrollBar');
    const scrollThumb = document.getElementById('chartScrollThumb');

    function syncThumb() {
        if (!chartEl || !scrollThumb || !scrollBar) return;
        const cw = chartEl.clientWidth;
        const sw = chartEl.scrollWidth;
        if (sw <= cw + 1) { scrollBar.style.display = 'none'; return; }
        scrollBar.style.display = 'block';
        const ratio = cw / sw;
        const thumbW = Math.max(15, ratio * 100);
        const maxScroll = sw - cw;
        const pct = maxScroll > 0 ? chartEl.scrollLeft / maxScroll : 0;
        const barW = scrollBar.offsetWidth;
        const thumbPx = (thumbW / 100) * barW;
        const maxLeft = barW - thumbPx;
        scrollThumb.style.width = thumbPx + 'px';
        scrollThumb.style.left = (pct * maxLeft) + 'px';
    }

    // Listen to scroll via polling (most reliable across all browsers)
    let lastScrollLeft = -1;
    function pollScroll() {
        if (chartEl.scrollLeft !== lastScrollLeft) {
            lastScrollLeft = chartEl.scrollLeft;
            syncThumb();
        }
        requestAnimationFrame(pollScroll);
    }
    pollScroll();

    // Also sync after chart data loads
    new MutationObserver(function() { setTimeout(syncThumb, 200); }).observe(chartEl, { childList: true });
    window.addEventListener('resize', syncThumb);

    // Make thumb draggable
    let dragging = false, startX = 0, startLeft = 0;
    scrollThumb.addEventListener('mousedown', function(e) { dragging = true; startX = e.clientX; startLeft = scrollThumb.offsetLeft; e.preventDefault(); });
    scrollThumb.addEventListener('touchstart', function(e) { dragging = true; startX = e.touches[0].clientX; startLeft = scrollThumb.offsetLeft; }, { passive: true });
    document.addEventListener('mousemove', function(e) { if (!dragging) return; dragMove(e.clientX); });
    document.addEventListener('touchmove', function(e) { if (!dragging) return; dragMove(e.touches[0].clientX); }, { passive: true });
    document.addEventListener('mouseup', function() { dragging = false; });
    document.addEventListener('touchend', function() { dragging = false; });
    function dragMove(clientX) {
        const barW = scrollBar.offsetWidth;
        const thumbPx = scrollThumb.offsetWidth;
        const maxLeft = barW - thumbPx;
        let newLeft = startLeft + (clientX - startX);
        newLeft = Math.max(0, Math.min(newLeft, maxLeft));
        const pct = maxLeft > 0 ? newLeft / maxLeft : 0;
        chartEl.scrollLeft = pct * (chartEl.scrollWidth - chartEl.clientWidth);
    }

    // Custom dropdown handlers
    window.toggleMonthDrop = function(e) { e.stopPropagation(); document.getElementById('yearDrop').style.display='none'; const d=document.getElementById('monthDrop'); d.style.display=d.style.display==='none'?'block':'none'; };
    window.toggleYearDrop = function(e) { e.stopPropagation(); document.getElementById('monthDrop').style.display='none'; const d=document.getElementById('yearDrop'); d.style.display=d.style.display==='none'?'block':'none'; };
    window.selectMonth = function(val, label, e) {
        e.stopPropagation();
        monthSel.value = val;
        document.getElementById('monthDropBtn').textContent = label;
        document.getElementById('monthDrop').style.display = 'none';
        // Reset active states
        document.querySelectorAll('#monthDrop > div').forEach(el => { el.style.background='transparent'; el.style.color='#374151'; el.dataset.active=''; });
        e.currentTarget.style.background='rgb(234, 88, 12)'; e.currentTarget.style.color='#fff'; e.currentTarget.dataset.active='1';
        loadChart();
    };
    window.selectYear = function(val, e) {
        e.stopPropagation();
        yearSel.value = val;
        document.getElementById('yearDropBtn').textContent = val;
        document.getElementById('yearDrop').style.display = 'none';
        document.querySelectorAll('#yearDrop > div').forEach(el => { el.style.background='transparent'; el.style.color='#374151'; el.dataset.active=''; });
        e.currentTarget.style.background='rgb(234, 88, 12)'; e.currentTarget.style.color='#fff'; e.currentTarget.dataset.active='1';
        loadChart();
    };
    document.addEventListener('click', function() {
        const m = document.getElementById('monthDrop'); if (m) m.style.display = 'none';
        const y = document.getElementById('yearDrop');  if (y) y.style.display = 'none';
    });

})();

// ── Dashboard Calendar ──
(function(){
    const selected = '{{ $selectedDate }}';
    const todayStr = '{{ now()->toDateString() }}';
    let viewYear = parseInt('{{ $selectedCarbon->year }}');
    let viewMonth = parseInt('{{ $selectedCarbon->month }}') - 1;
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    const days = ['Su','Mo','Tu','We','Th','Fr','Sa'];

    window.toggleDashCal = function() {
        const cal = document.getElementById('dashCal');
        if (cal.style.display === 'none' || !cal.style.display) {
            cal.style.display = 'block';
            renderCal();
            document.addEventListener('click', closeCal);
        } else {
            cal.style.display = 'none';
            document.removeEventListener('click', closeCal);
        }
    };

    function closeCal(e) {
        const wrap = document.getElementById('dashDateWrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('dashCal').style.display = 'none';
            document.removeEventListener('click', closeCal);
        }
    }

    function renderCal() {
        const cal = document.getElementById('dashCal');
        const first = new Date(viewYear, viewMonth, 1);
        const lastDay = new Date(viewYear, viewMonth + 1, 0).getDate();
        const startDay = first.getDay();
        const today = new Date(todayStr + 'T00:00:00');
        const sel = new Date(selected + 'T00:00:00');

        let html = '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">';
        html += '<button type="button" class="dc-nav" onclick="dcNav(-1,event)">&#8249;</button>';
        html += '<div style="display:flex;align-items:center;gap:6px;">';
        // Month dropdown
        html += '<select onclick="event.stopPropagation()" onchange="dcSetMonth(this.value,event)" style="border:1.5px solid #fed7aa;border-radius:8px;padding:5px 8px;font-size:13px;font-weight:700;color:#1e293b;background:#FFF7F2;cursor:pointer;outline:none;appearance:none;-webkit-appearance:none;background-image:url(\'data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%278%27 height=%278%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%23F27420%27 stroke-width=%273%27%3E%3Cpolyline points=%276 9 12 15 18 9%27/%3E%3C/svg%3E\');background-repeat:no-repeat;background-position:right 6px center;padding-right:20px;">';
        months.forEach(function(m, i) {
            html += '<option value="' + i + '"' + (i === viewMonth ? ' selected' : '') + '>' + m + '</option>';
        });
        html += '</select>';
        // Year dropdown
        html += '<select onclick="event.stopPropagation()" onchange="dcSetYear(this.value,event)" style="border:1.5px solid #fed7aa;border-radius:8px;padding:5px 8px;font-size:13px;font-weight:700;color:#1e293b;background:#FFF7F2;cursor:pointer;outline:none;appearance:none;-webkit-appearance:none;background-image:url(\'data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%278%27 height=%278%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%23F27420%27 stroke-width=%273%27%3E%3Cpolyline points=%276 9 12 15 18 9%27/%3E%3C/svg%3E\');background-repeat:no-repeat;background-position:right 6px center;padding-right:20px;">';
        const curYear = today.getFullYear();
        for (let y = curYear; y >= curYear - 10; y--) {
            html += '<option value="' + y + '"' + (y === viewYear ? ' selected' : '') + '>' + y + '</option>';
        }
        html += '</select>';
        html += '</div>';
        html += '<button type="button" class="dc-nav" onclick="dcNav(1,event)">&#8250;</button>';
        html += '</div>';

        // Day headers
        html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:6px;">';
        days.forEach(d => {
            html += '<div style="text-align:center;font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;padding:4px 0;">' + d + '</div>';
        });
        html += '</div>';

        // Day grid
        html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;">';

        // Prev month days
        const prevLast = new Date(viewYear, viewMonth, 0).getDate();
        for (let i = startDay - 1; i >= 0; i--) {
            html += '<button type="button" class="dc-day dc-other" disabled>' + (prevLast - i) + '</button>';
        }

        // Current month days
        for (let d = 1; d <= lastDay; d++) {
            const dateObj = new Date(viewYear, viewMonth, d);
            const dateStr = viewYear + '-' + String(viewMonth+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
            const isToday = dateStr === todayStr;
            const isSel = dateStr === selected;
            const isFuture = dateObj > today;

            let cls = 'dc-day';
            if (isSel) cls += ' dc-selected';
            else if (isToday) cls += ' dc-today';
            if (isFuture) cls += ' dc-disabled';

            if (isFuture) {
                html += '<button type="button" class="' + cls + '" disabled>' + d + '</button>';
            } else {
                html += '<button type="button" class="' + cls + '" onclick="dcSelect(\'' + dateStr + '\')">' + d + '</button>';
            }
        }

        // Next month fill
        const totalCells = startDay + lastDay;
        const remaining = (7 - (totalCells % 7)) % 7;
        for (let i = 1; i <= remaining; i++) {
            html += '<button type="button" class="dc-day dc-other" disabled>' + i + '</button>';
        }

        html += '</div>';

        // Footer
        html += '<div style="display:flex;justify-content:space-between;margin-top:12px;padding-top:10px;border-top:1px solid #f1f5f9;">';
        html += '<button type="button" onclick="dcSelect(\'' + todayStr + '\')" style="border:none;background:none;font-size:12px;font-weight:600;color:rgb(234, 88, 12);cursor:pointer;padding:4px 8px;border-radius:6px;transition:all 0.1s;" onmouseover="this.style.background=\'#FFF5ED\'" onmouseout="this.style.background=\'none\'">Today</button>';
        if (selected !== todayStr) {
            html += '<button type="button" onclick="dcSelect(\'' + todayStr + '\')" style="border:none;background:none;font-size:12px;font-weight:500;color:#94a3b8;cursor:pointer;padding:4px 8px;">Reset</button>';
        }
        html += '</div>';

        cal.innerHTML = html;
    }

    window.dcNav = function(dir, e) {
        if(e) e.stopPropagation();
        viewMonth += dir;
        if (viewMonth > 11) { viewMonth = 0; viewYear++; }
        if (viewMonth < 0) { viewMonth = 11; viewYear--; }
        renderCal();
    };

    window.dcSetMonth = function(m, e) {
        if(e) e.stopPropagation();
        viewMonth = parseInt(m);
        renderCal();
    };

    window.dcSetYear = function(y, e) {
        if(e) e.stopPropagation();
        viewYear = parseInt(y);
        renderCal();
    };

    window.dcSelect = function(dateStr) {
        window.location.href = '/dashboard/view/index?date=' + dateStr;
    };
})();
</script>
@endsection
