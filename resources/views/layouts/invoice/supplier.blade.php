<?php
$companyDetails = \App\Models\CompanyDetailModel::info();
$supplierDetails = \App\Models\Supplier::info($supplier_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ $companyDetails->company_name ?: 'R & A Veg Ltd' }} - Supplier History</title>
	<style>
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body {
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			background: #f3f4f6;
			color: #0f1115;
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
			line-height: 1.5;
		}
		.page {
			max-width: 1280px;
			margin: 24px auto;
			background: #ffffff;
			border-radius: 16px;
			box-shadow: 0 1px 3px rgba(15, 17, 21, 0.04), 0 1px 2px rgba(15, 17, 21, 0.03);
			overflow: hidden;
		}

		/* HEADER */
		.header {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			padding: 32px 40px 28px;
		}
		.brand-name {
			font-size: 30px;
			font-weight: 800;
			color: #f97316;
			letter-spacing: -0.5px;
			line-height: 1.1;
		}
		.brand-tagline {
			margin-top: 4px;
			font-size: 11px;
			font-weight: 600;
			color: #9ca3af;
			letter-spacing: 1.5px;
			text-transform: uppercase;
		}
		.meta-block {
			display: flex;
			gap: 56px;
			text-align: right;
		}
		.meta-item .meta-label {
			font-size: 11px;
			font-weight: 800;
			color: #f97316;
			letter-spacing: 1.2px;
			text-transform: uppercase;
			margin-bottom: 6px;
		}
		.meta-item .meta-value {
			font-size: 14px;
			font-weight: 400;
			color: #0f1115;
		}

		/* CONTACT BAR */
		.contact-bar {
			background: #f3f4f6;
			padding: 20px 40px;
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			font-size: 13px;
			color: #4b5563;
		}
		.contact-left p, .contact-right p {
			margin: 2px 0;
			line-height: 1.6;
		}
		.contact-left strong, .contact-right strong {
			font-weight: 700;
			color: #0f1115;
		}
		.contact-left a, .contact-right a {
			color: #f97316;
			text-decoration: none;
			font-weight: 500;
		}
		.contact-right {
			text-align: right;
		}
		.contact-right .name {
			font-weight: 700;
			color: #0f1115;
			font-size: 14px;
			margin-bottom: 4px;
		}

		/* CONTENT */
		.content {
			padding: 28px 40px 40px;
		}

		/* TABLE */
		.data-table {
			width: 100%;
			border-collapse: collapse;
		}
		.data-table thead th {
			font-size: 13px;
			font-weight: 700;
			color: #f97316;
			text-align: left;
			padding: 12px 14px;
			border-bottom: 1.5px solid #fde6d3;
			white-space: nowrap;
		}
		.data-table thead th.num { width: 50px; }
		.data-table thead th.right { text-align: right; }
		.data-table tbody td {
			padding: 14px;
			font-size: 13.5px;
			color: #0f1115;
			border-bottom: 1px solid #f3f4f6;
			vertical-align: middle;
		}
		.data-table tbody tr:last-child td {
			border-bottom: none;
		}
		.data-table .row-num { color: #9ca3af; font-weight: 500; }
		.data-table .row-date { color: #4b5563; }
		.data-table .row-invoice { color: #f97316; font-weight: 600; }
		.data-table .row-amount { font-weight: 700; }
		.data-table .row-zero { color: #d1d5db; }
		.data-table .right { text-align: right; }
		.data-table .past-balance-row td {
			border-bottom: 1.5px solid #fde6d3;
			padding: 14px;
			font-weight: 700;
			color: #f97316;
			text-align: right;
		}
		.data-table .past-balance-row .value {
			color: #0f1115;
			margin-left: 8px;
		}
		.data-table .total-row td {
			border-top: 2px solid #fde6d3;
			border-bottom: none;
			padding: 18px 14px;
			font-weight: 800;
			color: #f97316;
			font-size: 14px;
		}
		.data-table .total-row .total-label {
			color: #f97316;
			font-size: 14px;
			font-weight: 800;
			letter-spacing: 0.3px;
			text-transform: uppercase;
		}
		.data-table .total-row .currency {
			color: #f97316;
			font-weight: 700;
			margin-right: 4px;
		}

		/* NOTES */
		.notes-section {
			margin: 24px 40px 32px;
			padding: 20px 24px;
			background: #fffaf5;
			border: 1px solid #fde6d3;
			border-left: 4px solid #f97316;
			border-radius: 12px;
		}
		.notes-section .notes-label {
			color: #f97316;
			font-weight: 700;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 1.2px;
			margin-bottom: 8px;
		}
		.notes-section .notes-text {
			color: #4b5563;
			font-size: 13px;
			line-height: 1.6;
			font-style: italic;
		}

		@media print {
			body { background: #ffffff; }
			.page { box-shadow: none; margin: 0; border-radius: 0; max-width: 100%; }
			@page { margin: 16mm 12mm; }
		}
	</style>
</head>

<body>
	<div class="page">
		<!-- HEADER -->
		<div class="header">
			<div>
				<div class="brand-name">{{ $companyDetails->company_name ?: 'R & A Veg Ltd' }}</div>
				<div class="brand-tagline">Fruit and Veg is Power</div>
			</div>
			<div class="meta-block">
				@hasSection('top')
					@yield('top')
				@endif
			</div>
		</div>

		<!-- CONTACT BAR -->
		<div class="contact-bar">
			<div class="contact-left">
				<p>Phone: <strong>{{ $companyDetails->telephone ?: '—' }}</strong></p>
				<p>VAT: <strong>{{ $companyDetails->vat_no ?: '23456789' }}</strong></p>
				<p>Email: <a href="mailto:{{ $companyDetails->email ?? '' }}">{{ $companyDetails->email ?: '—' }}</a></p>
				@if(!empty($companyDetails->address1))
				<p>{{ $companyDetails->address1 }}{{ !empty($companyDetails->zipcode) ? ', ' . $companyDetails->zipcode : '' }}</p>
				@endif
			</div>
			<div class="contact-right">
				<div class="name">{{ $supplierDetails->name ?? '' }}</div>
				<p>Phone: <strong>{{ $supplierDetails->mobile ?: '—' }}</strong></p>
				<p>Email: <strong>{{ $supplierDetails->email ?: '—' }}</strong></p>
				@if(!empty($supplierDetails->address1))
				<p>{{ $supplierDetails->address1 }}</p>
				@endif
			</div>
		</div>

		<!-- CONTENT -->
		<div class="content">
			@hasSection('content')
				@yield('content')
			@endif
		</div>

		<!-- NOTES -->
		<div class="notes-section">
			<div class="notes-label">Notes</div>
			<div class="notes-text">This statement reflects the transactions and balances as of the date shown. Please review and contact us for any discrepancies.</div>
		</div>
	</div>

	@if(isset($print) && $print == 1)
	<script>
		window.onload = function() {
			window.print();
		};
	</script>
	@endif
</body>
</html>
