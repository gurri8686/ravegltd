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

    /* Stat cards — 2x2 clean */
    .dash-row-today {
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important; margin-bottom: 12px !important;
    }
    .dash-row-today .dash-card:last-child:nth-child(odd) { grid-column: 1 / -1 !important; }
    .dash-card {
        padding: 14px !important; gap: 0 !important;
        border-radius: 14px !important; flex-direction: row !important;
        align-items: center !important;
        border: 1px solid #f0f2f5 !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03) !important;
    }
    .dash-card:hover { transform: none !important; box-shadow: 0 2px 8px rgba(0,0,0,0.03) !important; }
    .dash-icon {
        width: 36px !important; height: 36px !important;
        border-radius: 10px !important; font-size: 15px !important;
        margin-bottom: 0 !important; margin-right: 12px !important; flex-shrink: 0 !important;
    }
    .dash-label { font-size: 9px !important; letter-spacing: 0.4px !important; margin-bottom: 1px !important; }
    .dash-value { font-size: 16px !important; line-height: 1.2 !important; }
    .dash-sub { font-size: 9.5px !important; margin-top: 1px !important; color: #b0b8c4 !important; }

    /* All-time — unified strip */
    .dash-row-alltime {
        grid-template-columns: 1fr 1fr 1fr !important;
        gap: 0 !important; margin-bottom: 12px !important;
        background: linear-gradient(135deg, #fafbfc, #fff) !important;
        border-radius: 14px !important;
        border: 1px solid #f0f2f5 !important; overflow: hidden !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03) !important;
    }
    .dash-row-alltime .dash-card {
        padding: 14px 6px !important; text-align: center !important;
        align-items: center !important; border-radius: 0 !important;
        border: none !important; box-shadow: none !important;
        border-right: 1px solid #f0f2f5 !important;
        flex-direction: column !important;
    }
    .dash-row-alltime .dash-card:last-child { border-right: none !important; }
    .dash-row-alltime .dash-card:hover { transform: none !important; box-shadow: none !important; }
    .dash-row-alltime .dash-icon { display: none !important; }
    .dash-row-alltime .dash-label {
        font-size: 7.5px !important; text-align: center !important;
        letter-spacing: 0.3px !important; color: #94a3b8 !important; margin-bottom: 3px !important;
    }
    .dash-row-alltime .dash-label i { display: none !important; }
    .dash-row-alltime .dash-value { font-size: 14px !important; text-align: center !important; }
    .dash-row-alltime .dash-sub { display: none !important; }

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
</style>
@endpush

@php
    $currency = env('CURRENCY_SYMBOL', 'Rs');
    $salesAllTime = $blocks['sales_alltime'][0]->amount ?? 0;
    $purchaseAllTime = $blocks['purchase_alltime'][0]->amount ?? 0;
    $net = $salesAllTime - $purchaseAllTime;
    $chartMax = $salesChart->max('amount') ?: 1;
@endphp

@section('content')
<div style="max-width:1440px;margin:0 auto;">

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
                {{-- Quick presets --}}
                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:8px;">Quick select</div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:14px;">
                    <button type="button" onclick="dashSetPreset('today')"     class="dash-preset-btn">Today</button>
                    <button type="button" onclick="dashSetPreset('yesterday')" class="dash-preset-btn">Yesterday</button>
                    <button type="button" onclick="dashSetPreset('last7')"     class="dash-preset-btn">Last 7 days</button>
                    <button type="button" onclick="dashSetPreset('last30')"    class="dash-preset-btn">Last 30 days</button>
                    <button type="button" onclick="dashSetPreset('thisMonth')" class="dash-preset-btn">This month</button>
                    <button type="button" onclick="dashSetPreset('lastMonth')" class="dash-preset-btn">Last month</button>
                </div>
                {{-- Custom range — single calendar with range selection (click start, click end) --}}
                <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:8px;">Custom range</div>
                {{-- Range summary pill — read-only display of the picked From → To range --}}
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                    <input type="hidden" id="dashDateFrom" value="{{ $selectedDate }}">
                    <input type="hidden" id="dashDateTo"   value="{{ $selectedDate }}">
                    <div id="dashRangeSummary" class="dash-range-summary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span id="dashRangeFromLabel" class="dash-range-label from-label">{{ $selectedCarbon->format('d M Y') }}</span>
                        <span class="dash-range-sep">→</span>
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
                    <div style="display:flex;justify-content:space-between;margin-top:6px;">
                        <button type="button" onclick="dashCalClear()" style="background:none;border:none;color:#64748b;font-size:12px;font-weight:600;cursor:pointer;outline:none;padding:4px 8px;border-radius:6px;">Clear</button>
                        <button type="button" onclick="dashCalToday()" style="background:none;border:none;color:rgb(234, 88, 12);font-size:12px;font-weight:700;cursor:pointer;outline:none;padding:4px 8px;border-radius:6px;">Today</button>
                    </div>
                </div>

                {{-- Mobile-only Cancel / Apply footer — desktop is hidden via CSS (max-width:767px shows it) --}}
                <div id="dashMobileFooter" style="display:none;gap:10px;margin-top:14px;">
                    <button type="button" onclick="dashMobileCancel()" style="flex:1;height:44px;border-radius:10px;border:1.5px solid #e5e7eb;background:#ffffff;color:#475569;font-size:14px;font-weight:600;cursor:pointer;outline:none;">
                        Cancel
                    </button>
                    <button type="button" onclick="dashMobileApply()" style="flex:1;height:44px;border-radius:10px;border:none;background:rgb(234, 88, 12);color:#ffffff;font-size:14px;font-weight:700;cursor:pointer;outline:none;box-shadow:0 2px 6px rgba(234,88,12,0.30);">
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
            // force reflow so transition runs
            void backdrop.offsetWidth;
            backdrop.classList.add('open');
        }
        document.addEventListener('click', onOutsideClick, true);
    }
    function closePopover() {
        pop.style.display = 'none';
        btn._open = false;
        btn.style.borderColor = '#e5e7eb';
        btn.style.background = '#fff';
        caret.style.transform = '';
        if (backdrop && backdrop.parentNode) backdrop.parentNode.removeChild(backdrop);
        backdrop = null;
        document.removeEventListener('click', onOutsideClick, true);
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

    function applyRange(from, to) {
        setBusy(true);
        fetch(DASH_STATS_URL + '?from=' + encodeURIComponent(from) + '&to=' + encodeURIComponent(to), { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(res => {
                if (!res.success) throw new Error('bad response');
                const p = res.payload;
                document.getElementById('dashSalesValue').textContent    = DASH_CURRENCY + ' ' + fmt(p.sales_value);
                document.getElementById('dashPurchaseValue').textContent = DASH_CURRENCY + ' ' + fmt(p.purchase_value);
                document.getElementById('dashOrdersValue').textContent   = fmt(p.orders_count);
                document.getElementById('dashSalesOrders').textContent   = fmt(p.orders_count);
                if (p.is_today) {
                    document.getElementById('dashSalesLabel').textContent    = "Today's Sales";
                    document.getElementById('dashPurchaseLabel').textContent = "Today's Purchases";
                    document.getElementById('dashOrdersLabel').textContent   = "Today's Orders";
                } else {
                    document.getElementById('dashSalesLabel').textContent    = p.label + ' Sales';
                    document.getElementById('dashPurchaseLabel').textContent = p.label + ' Purchases';
                    document.getElementById('dashOrdersLabel').textContent   = p.label + ' Orders';
                }
                btnLabel.textContent = p.is_today ? 'Today'
                    : (p.is_single_day ? (new Date(p.from).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}))
                    : (new Date(p.from).toLocaleDateString('en-GB',{day:'2-digit',month:'short'}) + ' – ' + new Date(p.to).toLocaleDateString('en-GB',{day:'2-digit',month:'short'})));
            })
            .catch(err => { console.error('Dashboard stats fetch failed', err); })
            .finally(() => setBusy(false));
    }

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
    document.addEventListener('click', function() { document.getElementById('monthDrop').style.display='none'; document.getElementById('yearDrop').style.display='none'; });

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
