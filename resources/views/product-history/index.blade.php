@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }

/* ── Tablet / iPad (768px – 1024px) ── */
@media (min-width: 768px) and (max-width: 1024px) {

    /* Page header */
    .users-list-wrapper > div:first-child {
        padding: 14px 18px !important;
        border-radius: 14px !important;
        margin-bottom: 14px !important;
    }
    .users-list-wrapper > div:first-child h1 {
        font-size: 19px !important;
        font-weight: 800 !important;
    }
    .users-list-wrapper > div:first-child p {
        font-size: 11.5px !important;
    }

    /* Summary cards grid — 6-col trick: row1 = 3 cards (span 2 each), row2 = 2 cards (span 3 each) */
    .ph-summary-grid {
        grid-template-columns: repeat(6, 1fr) !important;
        gap: 14px !important;
        margin-bottom: 16px !important;
    }

    /* All cards span 2 cols = 3 per row */
    .ph-summary-grid > div {
        grid-column: span 2 !important;
        border-radius: 16px !important;
        padding: 18px 16px !important;
        gap: 8px !important;
        box-shadow: 0 1px 6px rgba(0,0,0,0.06) !important;
    }

    /* 4th and 5th card span 3 cols = 2 per row (50% width each) */
    .ph-summary-grid > div:nth-child(4),
    .ph-summary-grid > div:nth-child(5) {
        grid-column: span 3 !important;
    }

    /* Card label */
    .ph-summary-grid > div > div:first-child > span:first-child {
        font-size: 10.5px !important;
        font-weight: 700 !important;
        letter-spacing: 0.7px !important;
    }

    /* Card icon box */
    .ph-summary-grid > div > div:first-child > div {
        width: 34px !important;
        height: 34px !important;
        border-radius: 10px !important;
    }
    .ph-summary-grid > div > div:first-child > div i {
        font-size: 14px !important;
    }

    /* Card value (big number) */
    .ph-summary-grid > div > span:nth-child(2) {
        font-size: 28px !important;
        font-weight: 800 !important;
        line-height: 1.1 !important;
    }

    /* Card sub text */
    .ph-summary-grid > div > span:last-child {
        font-size: 11.5px !important;
        font-weight: 500 !important;
    }
}

@media (max-width: 767px) {
    .users-list-wrapper > div:first-child {
        padding: 12px 14px !important;
        border-radius: 12px !important;
        margin-bottom: 10px !important;
        flex-wrap: nowrap !important;
    }
    .users-list-wrapper > div:first-child > div { gap: 10px !important; flex: 1; min-width: 0; }
    .users-list-wrapper > div:first-child h1 { font-size: 17px !important; }
    .users-list-wrapper > div:first-child p { font-size: 11px !important; white-space: normal !important; }
    .users-list-wrapper > div:first-child a[title="Back to Products"] { width: 32px !important; height: 32px !important; border-radius: 8px !important; flex-shrink: 0 !important; }
    .users-list-wrapper > div:first-child > div > div[style*="width:40px"] { width: 38px !important; height: 38px !important; border-radius: 10px !important; flex-shrink: 0 !important; }
    .users-list-wrapper > div:first-child > div > div[style*="width:40px"] i { font-size: 16px !important; }
}
.nav.nav-tabs {
	border-bottom: none;
	background: #f9fafb;
	border-radius: 12px;
	padding: 4px;
	gap: 4px;
	display: flex;
}
.nav.nav-tabs .nav-item {
	flex: 1;
}
.nav.nav-tabs .nav-item .nav-link {
    width: 100%;
	border: none;
	border-right: none;
	border-radius: 10px;
	padding: 10px 16px;
	font-size: 13px;
	font-weight: 600;
	color: #6b7280;
	background: transparent;
	transition: all 0.2s ease;
	text-align: center;
}
.nav.nav-tabs .nav-item .nav-link:hover {
	color: #F27420;
	background: #FFF5ED;
}
.nav.nav-tabs .nav-item .nav-link.active {
	color: #fff;
	background: #F27420;
	box-shadow: 0 2px 8px rgba(242, 116, 32, 0.3);
	border: none;
	outline: none;
}
.nav.nav-tabs .nav-item .nav-link:focus,
.nav.nav-tabs .nav-item .nav-link:focus-visible {
	border: none;
	outline: none;
	box-shadow: none;
}
.nav.nav-tabs .nav-item .nav-link.active:focus,
.nav.nav-tabs .nav-item .nav-link.active:focus-visible {
	border: none;
	outline: none;
	box-shadow: 0 2px 8px rgba(242, 116, 32, 0.3);
}
.dropdown-menu .dropdown-item:hover,
.dropdown-menu .dropdown-item:focus {
	background: #FFF5ED !important;
	color: #F27420 !important;
}
#extraOptionsCollapse .dropdown {
	position: relative;
}
#extraOptionsCollapse .dropdown-menu.show {
	top: 100% !important;
	left: 50% !important;
	right: auto !important;
	transform: translateX(-50%) !important;
	min-width: 180px;
	width: auto;
}
input[type="date"]::-webkit-calendar-picker-indicator {
	opacity: 0.4;
	cursor: pointer;
}
input[type="date"]::-webkit-calendar-picker-indicator:hover {
	opacity: 0.7;
}
</style>
@endPush

@section('content')
<section class="users-list-wrapper">
<div style="margin-bottom:0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px 16px 0 0;padding:18px 24px;box-shadow:none;border:1px solid #eaecf2;border-bottom:none;">
	<div style="display:flex;align-items:center;gap:14px;">
		<div style="width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,#f97316 0%,#ea580c 100%);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(249,115,22,0.3);flex-shrink:0;">
			<i class="fa fa-cube" style="color:#fff;font-size:18px;"></i>
		</div>
		<div>
			<h1 style="font-size:18px;font-weight:800;color:#0f172a;line-height:1.2;letter-spacing:-0.3px;margin:0;font-family:inherit;">Product History</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Track product movements across suppliers, sales, and returns</p>
		</div>
	</div>
</div>
	<script>
	if (window.innerWidth <= 767) {
		var phCheck = setInterval(function() {
			var wrap = document.querySelector('#product-history-app .ph-table-wrap');
			if (wrap) {
				clearInterval(phCheck);
				wrap.style.overflowX = 'scroll';
				wrap.style.webkitOverflowScrolling = 'touch';
				wrap.style.width = '100%';
				// clear overflow-x:hidden on all parents up to body
				var el = wrap.parentElement;
				while (el && el.tagName !== 'BODY') {
					var cs = window.getComputedStyle(el);
					if (cs.overflowX === 'hidden') {
						el.style.overflowX = 'visible';
					}
					el = el.parentElement;
				}
			}
		}, 300);
	}
	</script>
	<div id="product-history-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-customer-list-api="{{route('product_history.view.customers')}}"
		data-supplier-list-api="{{route('product_history.view.suppliers')}}"
		data-product-list-api="{{route('product_history.view.products')}}"
		data-supplier-invoices-list-api="{{route('product_history.view.supplier_invoices')}}"
		data-supplier-returns-list-api="{{route('product_history.view.supplier_returns')}}"
		data-sales-list-api="{{route('product_history.view.sales')}}"
		data-customer-returns-api="{{route('product_history.view.customer_returns')}}"
		data-dumps-api="{{route('product_history.view.dumps')}}"
		data-print-api="{{route('print.product_history')}}"
		data-email-api="{{route('print.product_history_email')}}"
		data-statement-api="{{route('print.product_history_statement')}}"
		data-excel-api="{{route('excel.product_history')}}"
	></div>
</section>
@endsection
