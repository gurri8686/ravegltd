@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }
.sc-nav-card *:focus { outline:none !important; box-shadow:none !important; }
.sc-nav-card button:focus { outline:none !important; box-shadow:none !important; }
.sc-nav-card {
    background:#fff;border-radius:18px;border:1px solid #f0f0f0;
    box-shadow:0 4px 24px rgba(0,0,0,0.07),0 1px 3px rgba(0,0,0,0.04);
    margin-bottom:18px;overflow:clip;position:relative;
    min-height:500px;
}
.sc-nav-card::before { display:none; }

/* ── Desktop tabs ── */
.sc-nav-tabs {
    display:flex;align-items:center;gap:4px;width:100%;overflow-x:auto;scrollbar-width:none;padding:0 12px;
}
.sc-nav-tabs::-webkit-scrollbar { display:none; }
.sc-tab {
    flex:1;height:48px;padding:0 20px;display:inline-flex;align-items:center;justify-content:center;gap:7px;
    font-size:13px;font-weight:600;color:#6b7280;cursor:pointer;
    border:none;border-top:3px solid transparent;background:transparent;
    outline:none !important;box-shadow:none !important;
    white-space:nowrap;text-decoration:none;transition:all 0.16s ease;
}
.sc-tab i { font-size:13px; }
.sc-tab:hover { color:rgb(234, 88, 12);background:#fff7ed;text-decoration:none; }
.sc-tab.active { background:#fff7ed !important;border-top:3px solid rgb(234, 88, 12);color:rgb(234, 88, 12) !important;font-weight:700;border-radius:0 !important; }
.sc-tab.active i { color:rgb(234, 88, 12); }
.sc-section { display:none; }
.sc-section.active { display:block; }

/* ── Mobile only ── */
.sc-mobile-header { display:none; }
.sc-mobile-tab-bar { display:none; }
@media (max-width:767px) {
    /* Hide desktop header + desktop tab row */
    .sc-desktop-header { display:none !important; }
    .sc-nav-tabs { display:none !important; }
    .sc-mobile-tab-bar { display:none !important; }

    /* Compact mobile header */
    .sc-mobile-header {
        display:flex;align-items:center;justify-content:space-between;
        padding:10px 14px;gap:10px;border-bottom:1px solid #f1f5f9;
        background:#fff;
    }
    .sc-mob-brand {
        display:flex;align-items:center;gap:8px;flex-shrink:0;
    }
    .sc-mob-brand-icon {
        width:32px;height:32px;border-radius:10px;flex-shrink:0;
        background:rgb(234, 88, 12);
        display:flex;align-items:center;justify-content:center;
        box-shadow:0 2px 8px rgba(234,88,12,0.3);
    }
    .sc-mob-brand-icon i { color:#fff;font-size:15px; }
    .sc-mob-brand-text { font-size:14px;font-weight:800;color:#0f172a;letter-spacing:-0.2px; }

    /* Custom dropdown */
    .sc-mob-dd-wrap {
        position:relative;flex:1;min-width:0;
    }
    .sc-mob-dd-trigger {
        width:100%;height:36px;padding:0 10px 0 12px;
        border:1.5px solid #e8edf2;border-radius:10px;
        background:#fff;display:flex;align-items:center;justify-content:space-between;gap:6px;
        cursor:pointer;outline:none !important;box-shadow:none !important;transition:all 0.15s;
    }
    .sc-mob-dd-trigger.open {
        background:#fff7ed;border-color:rgb(234, 88, 12);
    }
    .sc-mob-dd-label {
        font-size:13px;font-weight:700;color:rgb(234, 88, 12);flex:1;text-align:left;
        overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
    }
    .sc-mob-dd-chevron {
        flex-shrink:0;transition:transform 0.2s;
    }
    .sc-mob-dd-trigger.open .sc-mob-dd-chevron { transform:rotate(180deg); }

    .sc-mob-dd-menu {
        display:none;position:absolute;top:calc(100% + 8px);right:0;
        min-width:200px;
        background:#fff;border-radius:16px;border:1px solid #f0f0f0;
        box-shadow:0 16px 48px rgba(0,0,0,0.16);overflow:hidden;z-index:9999;
        animation:scDdIn 0.18s cubic-bezier(0.16,1,0.3,1);
    }
    .sc-mob-dd-menu.open { display:block; }
    @keyframes scDdIn { from{opacity:0;transform:translateY(-8px) scale(0.97)} to{opacity:1;transform:translateY(0) scale(1)} }

    .sc-mob-dd-item {
        display:flex;align-items:center;justify-content:space-between;gap:10px;
        padding:12px 16px;font-size:13px;font-weight:600;color:#374151;
        cursor:pointer;transition:all 0.15s;border:none;background:none;
        width:100%;text-align:left;outline:none !important;box-shadow:none !important;
    }
    .sc-mob-dd-item:focus { outline:none !important;box-shadow:none !important; }
    .sc-mob-dd-item:hover { background:rgb(234, 88, 12);color:#fff; }
    .sc-mob-dd-item:active { background:#c2410c;color:#fff; }
    .sc-mob-dd-item.active { color:rgb(234, 88, 12);background:#fff7ed;font-weight:700; }
    .sc-mob-dd-item.active:hover { background:rgb(234, 88, 12);color:#fff; }
    .sc-mob-dd-item.active .sc-mob-dd-tick { display:flex; }
    .sc-mob-dd-tick {
        display:none;align-items:center;justify-content:center;
        width:20px;height:20px;border-radius:50%;background:rgb(234, 88, 12);flex-shrink:0;
    }
}
</style>
@endpush

@section('content')
<section class="users-list-wrapper">

<div class="sc-nav-card">

    {{-- Desktop header --}}
    <div class="sc-desktop-header" style="display:flex;align-items:center;gap:14px;padding:17px 20px 23px 14px;">
        <div style="width:40px;height:40px;border-radius:12px;background:rgb(234, 88, 12);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(234,88,12,0.3);flex-shrink:0;">
            <i class="fa fa-cubes" style="color:#fff;font-size:18px;"></i>
        </div>
        <div>
            <div style="font-size:18px;font-weight:800;color:#0f172a;line-height:1.2;letter-spacing:-0.3px;">Stock Manager</div>
            <div style="font-size:12px;color:#94a3b8;font-weight:500;margin-top:2px;">Manage all stock operations</div>
        </div>
    </div>

    {{-- Mobile header: compact brand + dropdown --}}
    <div class="sc-mobile-header">
        <div class="sc-mob-brand">
            <div class="sc-mob-brand-icon"><i class="fa fa-cubes"></i></div>
            <span class="sc-mob-brand-text">Stock Manager</span>
        </div>
        <div class="sc-mob-dd-wrap" id="sc-mob-dd-wrap">
            <button type="button" class="sc-mob-dd-trigger" id="sc-mob-dd-trigger" onclick="toggleScMobDd()">
                <span class="sc-mob-dd-label" id="sc-mob-dd-label">Stock Check</span>
                <svg class="sc-mob-dd-chevron" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="rgb(234, 88, 12)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="sc-mob-dd-menu" id="sc-mob-dd-menu">
                <button type="button" class="sc-mob-dd-item active" data-tab="stock-check" data-label="Stock Check" onclick="pickScMobTab(this)">
                    Stock Check
                    <span class="sc-mob-dd-tick"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                </button>
                <button type="button" class="sc-mob-dd-item" data-tab="stock-closing" data-label="Stock Closing" onclick="pickScMobTab(this)">
                    Stock Closing
                    <span class="sc-mob-dd-tick"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                </button>
                <button type="button" class="sc-mob-dd-item" data-tab="unassigned" data-label="Unassigned Suppliers" onclick="pickScMobTab(this)">
                    Unassigned Suppliers
                    <span class="sc-mob-dd-tick"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                </button>
                <button type="button" class="sc-mob-dd-item" data-tab="customer-return" data-label="Customer Return" onclick="pickScMobTab(this)">
                    Customer Return
                    <span class="sc-mob-dd-tick"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                </button>
                <button type="button" class="sc-mob-dd-item" data-tab="supplier-return" data-label="Supplier Return" onclick="pickScMobTab(this)">
                    Supplier Return
                    <span class="sc-mob-dd-tick"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                </button>
                <button type="button" class="sc-mob-dd-item" data-tab="dump" data-label="Dump" onclick="pickScMobTab(this)">
                    Dump
                    <span class="sc-mob-dd-tick"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Desktop Tabs row --}}
    <div style="border-top:1px solid #f1f5f9;">
        <div class="sc-nav-tabs">
            <button class="sc-tab active" onclick="switchScTab('stock-check', this)">
                <i class="fa fa-bar-chart"></i> Stock Check
            </button>
            <button class="sc-tab" onclick="switchScTab('stock-closing', this)">
                <i class="fa fa-lock"></i> Stock Closing
            </button>
            <button class="sc-tab" onclick="switchScTab('unassigned', this)">
                <i class="fa fa-chain-broken"></i> Unassigned Sup.
            </button>
            <button class="sc-tab" onclick="switchScTab('customer-return', this)">
                <i class="fa fa-undo"></i> Customer Return
            </button>
            <button class="sc-tab" onclick="switchScTab('supplier-return', this)">
                <i class="fa fa-truck"></i> Supplier Return
            </button>
            <button class="sc-tab" onclick="switchScTab('dump', this)">
                <i class="fa fa-trash"></i> Dump
            </button>
        </div>
    </div>


    {{-- ── Stock Check tab (default) ── --}}
    <div id="sc-tab-stock-check" class="sc-section active">
        <div id="stock-check-app"
            data-no-header="1"
            data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
            data-list-api="{{route('stock_check.view.list')}}"
            data-opening-stock-api="{{route('stock_check.view.openingStock')}}"
            data-new-stock-api="{{route('stock_check.view.newStock')}}"
            data-sales-api="{{route('stock_check.view.sales')}}"
            data-customer-return-api="{{route('stock_check.view.customerReturn')}}"
            data-dumps-api="{{route('stock_check.view.dumps')}}"
            data-supplier-return-api="{{route('stock_check.view.supplierReturn')}}"
            data-closing-stock-api="{{route('stock_check.view.closingStock')}}"
            data-print-url="{{route('stock_check.view.print')}}"
            data-excel-url="{{route('excel.stock_check')}}"
        ></div>
    </div>

    {{-- ── Stock Closing tab ── --}}
    <div id="sc-tab-stock-closing" class="sc-section">
        <div id="stock-closing-app"
            data-no-header="1"
            data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
            data-list-api="{{route('stock_closing.view.products')}}"
            data-save-one-api="{{route('stock_closing.create.save-one')}}"
            data-save-all-api="{{route('stock_closing.create.save-all')}}"
            data-edit-api="{{route('stock_closing.edit.edit')}}"
            data-excel-url="{{route('excel.stock_closing')}}"
            data-print-url="{{route('print.stock_closing')}}"
            data-query='@json(request()->query())'
        ></div>
    </div>

    {{-- ── Unassigned Suppliers tab ── --}}
    <div id="sc-tab-unassigned" class="sc-section">
        <div id="unassigned-suppliers-app"
            data-no-header="1"
            data-list-api="{{route('stock_closing.view.unassigned-suppliers.list')}}"
            data-assign-api="{{route('stock_closing.view.unassigned-suppliers.assign')}}"
            data-back-url="{{route('stock_check.view.index')}}"
            data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
            data-print-url="{{route('print.unassigned_suppliers')}}"
            data-excel-url="{{route('excel.unassigned_suppliers')}}"
        ></div>
    </div>

    {{-- ── Customer Return tab ── --}}
    <div id="sc-tab-customer-return" class="sc-section">
        <div id="customers-return-app"
            data-no-header="1"
            data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
            data-customer-id=""
            data-customers-list-api="/customer_return/view/customers"
            data-products-list-api="/customer_return/view/products"
            data-invoices-returns-api="/customer_return/view/returns"
            data-invoices-list-api="/customer_return/view/invoices"
            data-customer-products-api="/customer_return/view/customer-products"
            data-invoices-product-api="/customer_return/view/product"
            data-invoices-return-create-api="/customer_return/view/return/create"
            data-invoices-return-update-api="/customer_return/view/return/update"
            data-invoices-return-delete-api="/customer_return/view/return/delete"
            data-history-url="{{route('customer_return.view.history')}}"
            data-print-url="{{route('print.customer_return')}}"
            data-excel-url="{{route('excel.customer_return')}}"
            data-returnable-print-url="{{route('print.customer_returnable')}}"
            data-returnable-excel-url="{{route('excel.customer_returnable')}}"
        ></div>
    </div>

    {{-- ── Supplier Return tab ── --}}
    <div id="sc-tab-supplier-return" class="sc-section">
        <div id="suppliers-return-app"
            data-no-header="1"
            data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
            data-suppliers-list-api="/supplier_return/view/suppliers"
            data-products-list-api="/supplier_return/view/products"
            data-invoices-returns-api="/supplier_return/view/returns"
            data-invoices-list-api="/supplier_return/view/invoices"
            data-supplier-products-api="/supplier_return/view/supplier-products"
            data-invoices-product-api="/supplier_return/view/product"
            data-invoices-return-create-api="/supplier_return/view/return/create"
            data-invoices-return-update-api="/supplier_return/view/return/update"
            data-invoices-return-delete-api="/supplier_return/view/return/delete"
            data-history-url="{{route('supplier_return.view.history')}}"
            data-print-url="{{route('print.supplier_return')}}"
            data-excel-url="{{route('excel.supplier_return')}}"
            data-returnable-print-url="{{route('print.supplier_returnable')}}"
            data-returnable-excel-url="{{route('excel.supplier_returnable')}}"
        ></div>
    </div>

    {{-- ── Dump tab ── --}}
    <div id="sc-tab-dump" class="sc-section">
        <div id="dumps-return-app"
            data-no-header="1"
            data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
            data-suppliers-list-api="/dump_return/view/suppliers"
            data-products-list-api="/dump_return/view/products"
            data-supplier-products-api="/dump_return/view/supplier-products"
            data-invoices-returns-api="/dump_return/view/returns"
            data-invoices-list-api="/dump_return/view/invoices"
            data-invoices-product-api="/dump_return/view/product"
            data-invoices-return-create-api="/dump_return/view/return/create"
            data-invoices-return-update-api="/dump_return/view/return/update"
            data-invoices-return-delete-api="/dump_return/view/return/delete"
            data-history-url="{{route('dump_return.view.history')}}"
            data-print-url="{{route('print.dump')}}"
            data-excel-url="{{route('excel.dump')}}"
            data-dumpable-print-url="{{route('print.dumpable')}}"
            data-dumpable-excel-url="{{route('excel.dumpable')}}"
        ></div>
    </div>

</div>{{-- /.sc-nav-card --}}

</section>

<script>
// Prevent any focus-triggered scroll inside the card
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var card = document.querySelector('.sc-nav-card');
        if (card) {
            card.addEventListener('focus', function() {
                var y = window.scrollY;
                requestAnimationFrame(function() { window.scrollTo(0, y); });
            }, true);
        }
    });
})();

function dismissAllToasts() {
    // Dispatch event so React components can call toast.dismiss() — let React clean up its own DOM.
    // Direct innerHTML wipe was clashing with React reconciliation and crashing the tree.
    window.dispatchEvent(new CustomEvent('dismiss-all-toasts'));
}

function switchScTab(name, btn) {
    var scrollY = window.scrollY;
    dismissAllToasts();
    document.querySelectorAll('.sc-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.sc-tab').forEach(t => t.classList.remove('active'));
    document.getElementById('sc-tab-' + name).classList.add('active');
    btn.classList.add('active');
    requestAnimationFrame(function() { window.scrollTo(0, scrollY); });
    window.dispatchEvent(new CustomEvent('sc-tab-activated', { detail: { tab: name } }));
}

function toggleScMobDd() {
    var trigger = document.getElementById('sc-mob-dd-trigger');
    var menu = document.getElementById('sc-mob-dd-menu');
    var isOpen = menu.classList.contains('open');
    if (isOpen) {
        menu.classList.remove('open');
        trigger.classList.remove('open');
    } else {
        menu.classList.add('open');
        trigger.classList.add('open');
    }
}

function pickScMobTab(item) {
    var tab = item.dataset.tab;
    var label = item.dataset.label;
    var scrollY = window.scrollY;
    // Update label
    document.getElementById('sc-mob-dd-label').textContent = label;
    // Update active item
    document.querySelectorAll('.sc-mob-dd-item').forEach(i => i.classList.remove('active'));
    item.classList.add('active');
    // Close dropdown
    document.getElementById('sc-mob-dd-menu').classList.remove('open');
    document.getElementById('sc-mob-dd-trigger').classList.remove('open');
    // Switch section
    document.querySelectorAll('.sc-section').forEach(s => s.classList.remove('active'));
    document.getElementById('sc-tab-' + tab).classList.add('active');
    requestAnimationFrame(function() { window.scrollTo(0, scrollY); });
    dismissAllToasts();
    window.dispatchEvent(new CustomEvent('sc-tab-activated', { detail: { tab: tab } }));
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    var wrap = document.getElementById('sc-mob-dd-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('sc-mob-dd-menu').classList.remove('open');
        document.getElementById('sc-mob-dd-trigger').classList.remove('open');
    }
});
</script>
@endsection
