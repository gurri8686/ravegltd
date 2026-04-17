@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }
@keyframes dcFadeIn{from{opacity:0;transform:translateY(-6px);filter:blur(2px)}to{opacity:1;transform:translateY(0);filter:blur(0)}}
.dc-day{width:40px;height:40px;border-radius:12px;border:none;font-size:13px;font-weight:600;cursor:pointer;outline:none;transition:all 0.15s;background:transparent;color:#1e293b;position:relative;}
.dc-day:hover{background:linear-gradient(135deg,#FFF5ED,#fff7ed);color:#F27420;transform:scale(1.08);}
.dc-day.dc-today{background:linear-gradient(135deg,#fef3e2,#fff7ed);color:#ea580c;font-weight:800;box-shadow:inset 0 0 0 2px #fed7aa;}
.dc-day.dc-selected{background:linear-gradient(135deg,#F27420,#ea580c) !important;color:#fff !important;box-shadow:0 4px 12px rgba(242,116,32,0.4);transform:scale(1.05);font-weight:700;}
.dc-day.dc-other{color:#e8ecf0;font-weight:400;pointer-events:none;opacity:0.15;}
.dc-day.dc-disabled{color:#e2e8f0;cursor:not-allowed;opacity:0.4;}
.dc-day.dc-disabled:hover{background:transparent;color:#e2e8f0;transform:none;}
.dc-nav{width:34px;height:34px;border-radius:10px;border:1.5px solid #e5e7eb;background:#fff;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;font-size:15px;color:#64748b;font-weight:700;transition:all 0.15s;outline:none;}
.dc-nav:hover{background:linear-gradient(135deg,#F27420,#ea580c);border-color:#F27420;color:#fff;box-shadow:0 3px 10px rgba(242,116,32,0.3);transform:scale(1.05);}
.dc-hdr-select{border:1.5px solid #e2e8f0;border-radius:10px;padding:7px 28px 7px 12px;font-size:13px;font-weight:700;color:#1e293b;background:#fff;cursor:pointer;outline:none;appearance:none;-webkit-appearance:none;transition:all 0.15s;background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2710%27 height=%2710%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%23F27420%27 stroke-width=%273%27 stroke-linecap=%27round%27%3E%3Cpolyline points=%276 9 12 15 18 9%27/%3E%3C/svg%3E');background-repeat:no-repeat;background-position:right 8px center;box-shadow:0 1px 3px rgba(0,0,0,0.04);}
.dc-hdr-select:hover{border-color:#F27420;background:#FFF5ED;}
.dc-hdr-select:focus{border-color:#F27420;background:#fff;box-shadow:none;outline:none;}
.dc-hdr-select option{padding:8px 12px;font-weight:500;font-size:13px;}
.dc-hdr-select option:checked{background:#F27420 linear-gradient(#F27420,#F27420);color:#fff;}
.dc-hdr-select option:hover{background:#FFF5ED linear-gradient(#FFF5ED,#FFF5ED);}
.dc-hdr-select::-webkit-scrollbar{width:4px;}
.dc-hdr-select::-webkit-scrollbar-thumb{background:#F27420;border-radius:4px;}
.dc-footer-btn{border:none;background:none;font-size:12px;font-weight:700;cursor:pointer;padding:6px 14px;border-radius:8px;transition:all 0.15s;}
.dc-footer-btn:hover{transform:scale(1.03);}
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
.chart-col:hover{background:rgba(242,116,32,0.04);}
.chart-col:hover .chart-bar-s{filter:brightness(1.1);transform:scaleX(1.15);}
.chart-col:hover .chart-bar-p{filter:brightness(1.1);transform:scaleX(1.15);}
.chart-col:hover .chart-day{color:#F27420 !important;font-weight:800 !important;}
#chartContainer{scrollbar-width:none !important;-ms-overflow-style:none !important;}
#chartContainer::-webkit-scrollbar{display:none !important;}
.chart-scroll-bar{height:4px;border-radius:10px;background:#f1f5f9;margin:8px 24px 0;position:relative;overflow:hidden;}
.chart-scroll-thumb{height:100%;border-radius:10px;background:#F27420;position:absolute;left:0;top:0;transition:left 0.1s;}

@media (max-width: 991px) {
    .dash-bottom-grid { grid-template-columns: 1fr !important; }
}

/* ── Tablet / iPad (768px – 1024px) ── */
@media (min-width: 768px) and (max-width: 1024px) {
    /* Today's stats — 2 × 2 grid so all 4 cards stay even */
    .dash-row-today {
        grid-template-columns: 1fr 1fr !important;
        gap: 12px !important;
        margin-bottom: 14px !important;
    }
    /* All-time — keep 3 cols but tighten gap */
    .dash-row-alltime {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 12px !important;
        margin-bottom: 14px !important;
    }
    /* Card padding & layout */
    .dash-card {
        padding: 16px 14px !important;
        gap: 12px !important;
        border-radius: 14px !important;
        align-items: center !important;
    }
    /* Icon */
    .dash-icon {
        width: 44px !important; height: 44px !important;
        border-radius: 12px !important; font-size: 17px !important;
        flex-shrink: 0 !important;
    }
    /* Label */
    .dash-label {
        font-size: 10px !important;
        letter-spacing: 0.3px !important;
        margin-bottom: 3px !important;
        white-space: normal !important;
        word-break: break-word !important;
    }
    /* Value — prevent overflow on large numbers */
    .dash-value {
        font-size: 20px !important;
        line-height: 1.15 !important;
        word-break: break-all !important;
        overflow-wrap: anywhere !important;
    }
    /* Sub text */
    .dash-sub {
        font-size: 10px !important;
        margin-top: 2px !important;
    }
    /* All-time row: hide icon to save space, center text */
    .dash-row-alltime .dash-icon { display: none !important; }
    .dash-row-alltime .dash-card {
        flex-direction: column !important;
        align-items: flex-start !important;
        padding: 14px 12px !important;
        gap: 4px !important;
    }
    .dash-row-alltime .dash-value { font-size: 17px !important; }
    .dash-row-alltime .dash-label { font-size: 9px !important; }
    /* Bottom grid — stack chart on top of products */
    .dash-bottom-grid {
        grid-template-columns: 1fr !important;
        gap: 14px !important;
    }
    /* Header */
    .dash-header-card {
        padding: 14px 18px !important;
        margin-bottom: 14px !important;
    }
    /* Chart panel */
    .dash-panel {
        border-radius: 14px !important;
        border: 1px solid #f0f0f0 !important;
    }
    .dash-panel-header {
        padding: 14px 18px !important;
        gap: 8px !important;
    }
    .dash-panel-title {
        font-size: 14px !important;
    }
    .dash-chart-legend {
        padding: 8px 18px 0 !important;
        gap: 12px !important;
    }
    .dash-chart-area {
        height: 200px !important;
        padding: 10px 16px 16px !important;
        gap: 2px !important;
    }
    .chart-scroll-bar {
        margin: 6px 18px 12px !important;
    }
    /* Products panel */
    .dash-product-item {
        padding: 11px 18px !important;
        gap: 12px !important;
    }
    .dash-product-icon {
        width: 38px !important;
        height: 38px !important;
        border-radius: 10px !important;
    }
    .dash-product-icon i {
        font-size: 13px !important;
    }
}

@media (max-width: 767px) {
    /* Header — compact */
    .dash-header-card {
        padding: 14px 16px !important; margin-bottom: 12px !important;
        border-radius: 14px !important; gap: 10px !important;
    }
    .dash-header-icon { width: 38px !important; height: 38px !important; border-radius: 10px !important; }
    .dash-header-icon i { font-size: 15px !important; }
    .dash-header-card h1 { font-size: 17px !important; }
    .dash-header-card p { font-size: 10.5px !important; margin: 0 !important; }

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

    {{-- Header --}}
    <div class="dash-header-card" style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px;padding:18px 24px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;">
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="dash-header-icon" style="width:48px;height:48px;border-radius:14px;background:#F27420;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(242,116,32,0.25);">
                <i class="fa fa-university" style="color:#fff;font-size:20px;"></i>
            </div>
            <div>
                <h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Dashboard</h1>
                <p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Business overview at a glance</p>
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
                <div class="dash-label">{{ $selectedCarbon->isToday() ? "Today's Sales" : $selectedCarbon->format('d M') . ' Sales' }}</div>
                <div class="dash-value" style="color:#15803d;">{{ $currency }} {{ number_format($salesToday) }}</div>
                <div class="dash-sub">{{ $todayOrders }} orders</div>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-icon" style="background:#dbeafe;color:#1d4ed8;box-shadow:0 2px 8px rgba(29,78,216,0.15);">
                <i class="fa fa-truck"></i>
            </div>
            <div>
                <div class="dash-label">{{ $selectedCarbon->isToday() ? "Today's Purchases" : $selectedCarbon->format('d M') . ' Purchases' }}</div>
                <div class="dash-value" style="color:#1d4ed8;">{{ $currency }} {{ number_format($purchaseToday) }}</div>
                <div class="dash-sub">Stock purchased</div>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-icon" style="background:#FFF5ED;color:#F27420;box-shadow:0 2px 8px rgba(242,116,32,0.15);">
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
                <div class="dash-label">{{ $selectedCarbon->isToday() ? "Today's Orders" : $selectedCarbon->format('d M') . ' Orders' }}</div>
                <div class="dash-value" style="color:#7c3aed;">{{ $todayOrders }}</div>
                <div class="dash-sub">Invoices created</div>
            </div>
        </div>

    </div>

    {{-- Row 2: All Time Stats --}}
    <div class="dash-row-alltime" style="display:grid;grid-template-columns:repeat(3, 1fr);gap:16px;margin-bottom:20px;">

        <div class="dash-card">
            <div class="dash-icon" style="background:#FFF5ED;color:#F27420;box-shadow:0 2px 8px rgba(242,116,32,0.15);">
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
                            <div onclick="selectMonth({{ $i + 1 }},'{{ $m }}',event)" style="padding:8px 14px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.1s;color:{{ ($i + 1) == now()->month ? '#fff' : '#374151' }};background:{{ ($i + 1) == now()->month ? '#F27420' : 'transparent' }};border-radius:6px;"
                                onmouseover="if(!this.dataset.active){this.style.background='#FFF5ED';this.style.color='#F27420';}else{this.style.background='#ea580c';}"
                                onmouseout="if(!this.dataset.active){this.style.background='transparent';this.style.color='#374151';}else{this.style.background='#F27420';}"
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
                            <div onclick="selectYear({{ $y }},event)" style="padding:8px 14px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.1s;color:{{ $y == now()->year ? '#fff' : '#374151' }};background:{{ $y == now()->year ? '#F27420' : 'transparent' }};border-radius:{{ $y == now()->year ? '6px' : '0' }};"
                                onmouseover="if(!this.dataset.active)this.style.background='#FFF5ED';this.style.color='#F27420';"
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
                <div style="display:flex;align-items:center;gap:5px;"><div style="width:10px;height:10px;border-radius:3px;background:#F27420;"></div><span style="font-size:11px;color:#64748b;font-weight:600;">Sales</span></div>
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
                <a href="/management/products/view/index" style="font-size:11px;font-weight:600;color:#F27420;text-decoration:none;">View All →</a>
            </div>
            <div style="padding:4px 0;">
                @foreach($latestProducts as $prod)
                <a href="/product_history/view?product={{ $prod->id }}" class="dash-product-item" style="text-decoration:none;display:flex;align-items:center;gap:12px;padding:12px 24px;transition:background 0.1s;" onmouseover="this.style.background='#fefaf6'" onmouseout="this.style.background='transparent'">
                    <div class="dash-product-icon" style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#F27420,#fb923c);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 6px rgba(242,116,32,0.2);">
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
                        html += '<div class="chart-bar-s" style="width:45%;max-width:20px;height:'+sh+'%;border-radius:6px 6px 0 0;background:linear-gradient(to top,#F27420,#fb923c);opacity:'+(d.sales>0?1:0.12)+';transition:all 0.3s;transform-origin:bottom;"></div>';
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
                            '<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;"><div style="width:8px;height:8px;border-radius:3px;background:#F27420;flex-shrink:0;"></div><div style="flex:1;font-size:12px;color:#94a3b8;">Sales</div><div style="font-size:13px;font-weight:700;color:#fb923c;">' + currency + ' ' + d.sales.toLocaleString() + '</div></div>' +
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
        e.currentTarget.style.background='#F27420'; e.currentTarget.style.color='#fff'; e.currentTarget.dataset.active='1';
        loadChart();
    };
    window.selectYear = function(val, e) {
        e.stopPropagation();
        yearSel.value = val;
        document.getElementById('yearDropBtn').textContent = val;
        document.getElementById('yearDrop').style.display = 'none';
        document.querySelectorAll('#yearDrop > div').forEach(el => { el.style.background='transparent'; el.style.color='#374151'; el.dataset.active=''; });
        e.currentTarget.style.background='#F27420'; e.currentTarget.style.color='#fff'; e.currentTarget.dataset.active='1';
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
        html += '<button type="button" onclick="dcSelect(\'' + todayStr + '\')" style="border:none;background:none;font-size:12px;font-weight:600;color:#F27420;cursor:pointer;padding:4px 8px;border-radius:6px;transition:all 0.1s;" onmouseover="this.style.background=\'#FFF5ED\'" onmouseout="this.style.background=\'none\'">Today</button>';
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
