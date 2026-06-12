<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ $companyDetails->company_name ?? 'R & A Veg Ltd' }} - Daily Sales Report</title>
	<style>
		* { box-sizing: border-box; margin: 0; padding: 0; }
		body {
			font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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
		.contact-left p, .contact-right p { margin: 2px 0; line-height: 1.6; }
		.contact-left strong { font-weight: 700; color: #0f1115; }
		.contact-left a { color: #f97316; text-decoration: none; font-weight: 500; }
		.contact-right { text-align: right; }
		.contact-right .report-title {
			font-size: 17px;
			font-weight: 800;
			color: #0f1115;
			margin-bottom: 4px;
		}
		.contact-right .report-sub {
			font-size: 12px;
			color: #6b7280;
		}

		/* CONTENT */
		.content { padding: 28px 40px 32px; }

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
		.data-table thead th.center { text-align: center; }
		.data-table tbody td {
			padding: 14px;
			font-size: 13.5px;
			color: #0f1115;
			border-bottom: 1px solid #f3f4f6;
			vertical-align: middle;
		}
		.data-table tbody tr:last-child td { border-bottom: none; }
		.data-table .row-num { color: #9ca3af; font-weight: 500; }
		.data-table .row-date { color: #4b5563; }
		.data-table .row-invoice { color: #f97316; font-weight: 600; }
		.data-table .row-customer { color: #0f1115; }
		.data-table .row-amount { font-weight: 700; }
		.data-table .right { text-align: right; }
		.data-table .center { text-align: center; }

		.empty-state {
			padding: 18px;
			text-align: center;
			color: #94a3b8;
			font-size: 13px;
			font-style: italic;
			background: #f9fafb;
			border-radius: 10px;
			border: 1px dashed #e5e7eb;
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
		.data-table .total-row .currency { color: #f97316; font-weight: 700; margin-right: 4px; }
		.data-table .total-row .total-paid { color: #15803d; }
		.data-table .total-row .total-pending { color: #f97316; }

		/* Status pills */
		.badge {
			padding: 3px 10px;
			border-radius: 999px;
			font-size: 11px;
			font-weight: 700;
			letter-spacing: 0.2px;
			border: 1px solid;
		}
		.badge-paid    { background: #ecfdf5; color: #15803d; border-color: #bde5c9; }
		.badge-partial { background: #fff7ed; color: #f97316; border-color: #fed7aa; }
		.badge-unpaid  { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }

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
			.no-print { display: none !important; }
			@page { margin: 16mm 12mm; }
		}
	</style>
</head>
<body onload="window.print()">
	<div class="page">
		<!-- HEADER -->
		<div class="header">
			<div>
				<div class="brand-name">{{ $companyDetails->company_name ?? 'R & A Veg Ltd' }}</div>
				<div class="brand-tagline">Fruit and Veg is Power</div>
			</div>
			<div class="meta-block">
				<div class="meta-item">
					<div class="meta-label">From – To Date</div>
					<div class="meta-value">{{ ($start_date && $end_date) ? \Carbon\Carbon::parse($start_date)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($end_date)->format('d M Y') : 'All time' }}</div>
				</div>
				<div class="meta-item">
					<div class="meta-label">Today</div>
					<div class="meta-value">{{ date('d M Y') }}</div>
				</div>
			</div>
		</div>

		<!-- CONTACT BAR -->
		<div class="contact-bar">
			<div class="contact-left">
				<p>Phone: <strong>{{ $companyDetails->telephone ?? '—' }}</strong></p>
				<p>VAT: <strong>{{ $companyDetails->vat_no ?? '23456789' }}</strong></p>
				<p>Email: <a href="mailto:{{ $companyDetails->email ?? '' }}">{{ $companyDetails->email ?? '—' }}</a></p>
				@if(!empty($companyDetails->address1))
				<p>{{ $companyDetails->address1 }}{{ !empty($companyDetails->zipcode) ? ', ' . $companyDetails->zipcode : '' }}</p>
				@endif
			</div>
			<div class="contact-right">
				<div class="report-title">Daily Sales Report</div>
				<div class="report-sub">Generated on {{ uk_ts(now(), 'd M Y, h:i A') }}</div>
			</div>
		</div>

		<!-- CONTENT -->
		<div class="content">
			<table class="data-table">
				<thead>
					<tr>
						<th>Invoice No.</th>
						<th>Date</th>
						<th>Customer</th>
						<th class="right">Total</th>
						<th class="right">Paid</th>
						<th class="center">Status</th>
						<th>Payments</th>
					</tr>
				</thead>
				<tbody>
					@forelse($invoices as $i => $invoice)
						@php
							$total      = $invoice->total ?? 0;
							$paid       = $invoice->total_paid ?? 0;
							$badgeClass = $paid >= $total && $total > 0 ? 'badge-paid' : ($paid > 0 ? 'badge-partial' : 'badge-unpaid');
							$badgeText  = $paid >= $total && $total > 0 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Unpaid');
							// Aggregate payment modes (matches UI's paymentsDisplay)
							$paymentTotals = [];
							foreach (($invoice->payments_list ?? $invoice->payments ?? []) as $p) {
								$mode = $p['payment_mode_type'] ?? ($p['payment_mode']['type'] ?? null);
								if (!$mode || $mode === 'Unknown') continue;
								$paymentTotals[$mode] = ($paymentTotals[$mode] ?? 0) + (float)($p['total_amount'] ?? 0);
							}
						@endphp
						<tr>
							<td class="row-invoice">#{{ $invoice->id }}</td>
							<td class="row-date">{{ uk_ts($invoice->created_at, 'd M Y') }}</td>
							<td class="row-customer">{{ $invoice->customer->name ?? '' }}</td>
							<td class="right row-amount">{{ $currency }} {{ number_format($total, 2) }}</td>
							<td class="right row-amount">{{ $paid > 0 ? $currency . ' ' . number_format($paid, 2) : '' }}</td>
							<td class="center"><span class="badge {{ $badgeClass }}">{{ $badgeText }}</span></td>
							<td>
								@if(!empty($paymentTotals))
									@foreach($paymentTotals as $mode => $amt)
										<div style="font-size:11px;color:#374151;">{{ $mode }} {{ $currency }} {{ number_format($amt, 2) }}</div>
									@endforeach
								@endif
							</td>
						</tr>
					@empty
						<tr><td colspan="7"><div class="empty-state">No invoices found for this period.</div></td></tr>
					@endforelse

					@if($invoices->count() > 0)
						<tr class="total-row">
							<td colspan="3" class="total-label">Total</td>
							<td class="right"><span class="currency">{{ $currency }}</span>{{ number_format($invoices->sum('total'), 2) }}</td>
							<td class="right total-paid"><span class="currency">{{ $currency }}</span>{{ number_format($invoices->sum('total_paid'), 2) }}</td>
							<td></td>
							<td></td>
						</tr>
					@endif
				</tbody>
			</table>
		</div>

		<!-- NOTES -->
		<div class="notes-section">
			<div class="notes-label">Notes</div>
			<div class="notes-text">This statement reflects the transactions and balances as of the date shown. Please review and contact us for any discrepancies.</div>
		</div>
	</div>
</body>
</html>
