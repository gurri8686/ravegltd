@extends('layouts.invoice.customer')

@section('top')
<div class="meta-item">
	<div class="meta-label">From – To Date</div>
	<div class="meta-value">{{ !empty($start_date) ? \Carbon\Carbon::parse($start_date)->format('d M Y') : '—' }} – {{ !empty($end_date) ? \Carbon\Carbon::parse($end_date)->format('d M Y') : '—' }}</div>
</div>
<div class="meta-item">
	<div class="meta-label">Today</div>
	<div class="meta-value">{{ date('d M Y') }}</div>
</div>
@endSection

@section('content')
<style>
	/* Section block — header + table + empty state */
	.ph-section { margin-bottom: 28px; }
	.ph-section:last-child { margin-bottom: 0; }
	.ph-section-head {
		display: flex; align-items: center; gap: 10px;
		margin-bottom: 12px;
	}
	.ph-section-title {
		font-size: 18px; font-weight: 500; color: #0f1115;
		letter-spacing: -0.3px;
	}
	.ph-count-pill {
		display: inline-flex; align-items: center;
		padding: 3px 10px; border-radius: 999px;
		background: #f3f4f6; color: #6b7280;
		font-size: 11px; font-weight: 700;
		letter-spacing: 0.1px;
	}
	.ph-count-pill.has {
		background: #fff7ed; color: #f97316;
	}
	.ph-product-row {
		display: flex; align-items: center; gap: 12px;
		margin-bottom: 24px;
		font-size: 13px;
	}
	.ph-product-label {
		font-size: 11px; font-weight: 700; color: #f97316;
		letter-spacing: 0.8px; text-transform: uppercase;
	}
	.ph-product-pill {
		display: inline-flex; align-items: center;
		padding: 4px 12px; border-radius: 999px;
		background: #fff7ed; color: #f97316;
		border: 1px solid #fed7aa;
		font-size: 13px; font-weight: 700;
	}
	.ph-product-sku {
		color: #6b7280; font-size: 12px;
	}
	.ph-product-sku-val {
		font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
		font-weight: 700; color: #0f1115; margin-left: 6px;
	}
	.ph-empty-state {
		padding: 18px;
		text-align: center;
		color: #94a3b8;
		font-size: 13px;
		font-style: italic;
		background: #f9fafb;
		border-radius: 10px;
		border: 1px dashed #e5e7eb;
	}
	.ph-total-row td {
		padding: 14px;
		font-weight: 800;
		color: #f97316;
		font-size: 14px;
		background: #fff7ed;
		border-top: 2px solid #fed7aa;
		border-bottom: none;
	}
</style>

<!-- Product info row -->
<div class="ph-product-row">
	<span class="ph-product-label">Product</span>
	<span class="ph-product-pill">{{ $product->name ?? '' }}</span>
	@if(!empty($product->product_id))
		<span class="ph-product-sku">SKU <span class="ph-product-sku-val">{{ $product->product_id }}</span></span>
	@endif
</div>

<!-- Supplier section -->
<div class="ph-section">
	<div class="ph-section-head">
		<div class="ph-section-title">Supplier</div>
		<span class="ph-count-pill {{ count($suppliers) > 0 ? 'has' : '' }}">{{ count($suppliers) }} {{ count($suppliers) == 1 ? 'entry' : 'entries' }}</span>
	</div>
	<table class="data-table">
		<thead>
			<tr>
				<th class="num">SL No.</th>
				<th>Date</th>
				<th>Supplier Name</th>
				<th>Product</th>
				<th class="right">Quantity</th>
				<th class="right">Unit Price</th>
				<th class="right">Total</th>
			</tr>
		</thead>
		<tbody>
			@if(count($suppliers) === 0)
				<tr><td colspan="7"><div class="ph-empty-state">No supplier purchases recorded for this period.</div></td></tr>
			@else
				@php $i = 1; $supTotalQty = 0; $supTotal = 0; @endphp
				@foreach($suppliers as $entry)
					@php
						$rowTotal = ($entry['unit_price'] ?? 0) * ($entry['quantity'] ?? 0);
						$supTotalQty += ($entry['quantity'] ?? 0);
						$supTotal += $rowTotal;
					@endphp
					<tr>
						<td class="row-num">{{ $i }}</td>
						<td class="row-date">{{ uk_ts($entry['created_at'], 'd M Y') }}</td>
						<td class="row-date">{{ $entry['supplier']['name'] ?? '' }}</td>
						<td class="row-date">{{ $entry['product']['name'] ?? '' }}</td>
						<td class="right row-date">{{ $entry['quantity'] }}</td>
						<td class="right row-date">{{ number_format($entry['unit_price'], 2) }}</td>
						<td class="right row-amount">{{ number_format($rowTotal, 2) }}</td>
					</tr>
					@php $i++ @endphp
				@endforeach
				<tr class="ph-total-row">
					<td colspan="4">Total</td>
					<td class="right">{{ $supTotalQty }}</td>
					<td class="right">—</td>
					<td class="right">{{ env('CURRENCY_SYMBOL', '£') }} {{ number_format($supTotal, 2) }}</td>
				</tr>
			@endif
		</tbody>
	</table>
</div>

<!-- Supplier Returns -->
<div class="ph-section">
	<div class="ph-section-head">
		<div class="ph-section-title">Supplier Returns</div>
		<span class="ph-count-pill {{ count($supplier_returns) > 0 ? 'has' : '' }}">{{ count($supplier_returns) }} {{ count($supplier_returns) == 1 ? 'entry' : 'entries' }}</span>
	</div>
	<table class="data-table">
		<thead>
			<tr>
				<th class="num">SL No.</th>
				<th>Date</th>
				<th>Invoice</th>
				<th>Supplier Name</th>
				<th>Product</th>
				<th class="right">Quantity</th>
				<th class="right">Unit Price</th>
				<th class="right">Total</th>
			</tr>
		</thead>
		<tbody>
			@if(count($supplier_returns) === 0)
				<tr><td colspan="8"><div class="ph-empty-state">No supplier returns recorded for this period.</div></td></tr>
			@else
				@php $j = 1; $srTotalQty = 0; $srTotal = 0; @endphp
				@foreach($supplier_returns as $entry)
					@php
						$rowTotal = ($entry['price'] ?? 0) * ($entry['stock'] ?? 0);
						$srTotalQty += ($entry['stock'] ?? 0);
						$srTotal += $rowTotal;
					@endphp
					<tr>
						<td class="row-num">{{ $j }}</td>
						<td class="row-date">{{ uk_ts($entry['created_at'], 'd M Y') }}</td>
						<td class="row-invoice">{{ $entry['invoice_id'] ?? '' }}</td>
						<td class="row-date">{{ $entry['supplier']['name'] ?? '' }}</td>
						<td class="row-date">{{ $entry['product']['name'] ?? '' }}</td>
						<td class="right row-date">{{ $entry['stock'] }}</td>
						<td class="right row-date">{{ number_format($entry['price'], 2) }}</td>
						<td class="right row-amount">{{ number_format($rowTotal, 2) }}</td>
					</tr>
					@php $j++ @endphp
				@endforeach
				<tr class="ph-total-row">
					<td colspan="5">Total</td>
					<td class="right">{{ $srTotalQty }}</td>
					<td class="right">—</td>
					<td class="right">{{ env('CURRENCY_SYMBOL', '£') }} {{ number_format($srTotal, 2) }}</td>
				</tr>
			@endif
		</tbody>
	</table>
</div>

<!-- Sales -->
<div class="ph-section">
	<div class="ph-section-head">
		<div class="ph-section-title">Sales</div>
		<span class="ph-count-pill {{ count($sales) > 0 ? 'has' : '' }}">{{ count($sales) }} {{ count($sales) == 1 ? 'entry' : 'entries' }}</span>
	</div>
	<table class="data-table">
		<thead>
			<tr>
				<th class="num">SL No.</th>
				<th>Date</th>
				<th>Invoice No</th>
				<th>Customer Name</th>
				<th>Product</th>
				<th class="right">Quantity</th>
				<th class="right">Unit Price</th>
				<th class="right">Total</th>
			</tr>
		</thead>
		<tbody>
			@if(count($sales) === 0)
				<tr><td colspan="8"><div class="ph-empty-state">No sales recorded for this period.</div></td></tr>
			@else
				@php $k = 1; $sTotalQty = 0; $sTotal = 0; @endphp
				@foreach($sales as $entry)
					@php
						$rowTotal = ($entry['unit_price'] ?? 0) * ($entry['quantity'] ?? 0);
						$sTotalQty += ($entry['quantity'] ?? 0);
						$sTotal += $rowTotal;
					@endphp
					<tr>
						<td class="row-num">{{ $k }}</td>
						<td class="row-date">{{ uk_ts($entry['created_at'], 'd M Y') }}</td>
						<td class="row-invoice">{{ $entry['customer_invoice_id'] ?? '' }}</td>
						<td class="row-date">{{ $entry['customer']['name'] ?? '' }}</td>
						<td class="row-date">{{ $entry['product']['name'] ?? '' }}</td>
						<td class="right row-date">{{ $entry['quantity'] }}</td>
						<td class="right row-date">{{ number_format($entry['unit_price'], 2) }}</td>
						<td class="right row-amount">{{ number_format($rowTotal, 2) }}</td>
					</tr>
					@php $k++ @endphp
				@endforeach
				<tr class="ph-total-row">
					<td colspan="5">Total</td>
					<td class="right">{{ $sTotalQty }}</td>
					<td class="right">—</td>
					<td class="right">{{ env('CURRENCY_SYMBOL', '£') }} {{ number_format($sTotal, 2) }}</td>
				</tr>
			@endif
		</tbody>
	</table>
</div>

<!-- Customer Returns -->
<div class="ph-section">
	<div class="ph-section-head">
		<div class="ph-section-title">Customer Returns</div>
		<span class="ph-count-pill {{ count($customer_returns) > 0 ? 'has' : '' }}">{{ count($customer_returns) }} {{ count($customer_returns) == 1 ? 'entry' : 'entries' }}</span>
	</div>
	<table class="data-table">
		<thead>
			<tr>
				<th class="num">SL No.</th>
				<th>Date</th>
				<th>Invoice No</th>
				<th>Customer Name</th>
				<th>Product</th>
				<th class="right">Quantity</th>
				<th class="right">Unit Price</th>
				<th class="right">Total</th>
			</tr>
		</thead>
		<tbody>
			@if(count($customer_returns) === 0)
				<tr><td colspan="8"><div class="ph-empty-state">No customer returns recorded for this period.</div></td></tr>
			@else
				@php $m = 1; $crTotalQty = 0; $crTotal = 0; @endphp
				@foreach($customer_returns as $entry2)
					@php
						$rowTotal = ($entry2['price'] ?? 0) * ($entry2['stock'] ?? 0);
						$crTotalQty += ($entry2['stock'] ?? 0);
						$crTotal += $rowTotal;
					@endphp
					<tr>
						<td class="row-num">{{ $m }}</td>
						<td class="row-date">{{ uk_ts($entry2['created_at'], 'd M Y') }}</td>
						<td class="row-invoice">{{ $entry2['customer_invoice']['id'] ?? '' }}</td>
						<td class="row-date">{{ $entry2['customer']['name'] ?? '' }}</td>
						<td class="row-date">{{ $entry2['product']['name'] ?? '' }}</td>
						<td class="right row-date">{{ $entry2['stock'] }}</td>
						<td class="right row-date">{{ number_format($entry2['price'], 2) }}</td>
						<td class="right row-amount">{{ number_format($rowTotal, 2) }}</td>
					</tr>
					@php $m++ @endphp
				@endforeach
				<tr class="ph-total-row">
					<td colspan="5">Total</td>
					<td class="right">{{ $crTotalQty }}</td>
					<td class="right">—</td>
					<td class="right">{{ env('CURRENCY_SYMBOL', '£') }} {{ number_format($crTotal, 2) }}</td>
				</tr>
			@endif
		</tbody>
	</table>
</div>

<!-- Dumps -->
<div class="ph-section">
	<div class="ph-section-head">
		<div class="ph-section-title">Dumps</div>
		<span class="ph-count-pill {{ count($dumps) > 0 ? 'has' : '' }}">{{ count($dumps) }} {{ count($dumps) == 1 ? 'entry' : 'entries' }}</span>
	</div>
	<table class="data-table">
		<thead>
			<tr>
				<th class="num">SL No.</th>
				<th>Date</th>
				<th>Invoice No</th>
				<th>Supplier Name</th>
				<th>Product</th>
				<th class="right">Quantity</th>
				<th class="right">Unit Price</th>
				<th class="right">Total</th>
			</tr>
		</thead>
		<tbody>
			@if(count($dumps) === 0)
				<tr><td colspan="8"><div class="ph-empty-state">Nothing was dumped during this period.</div></td></tr>
			@else
				@php $n = 1; $dTotalQty = 0; $dTotal = 0; @endphp
				@foreach($dumps as $entry2)
					@php
						$rowTotal = ($entry2['price'] ?? 0) * ($entry2['stock'] ?? 0);
						$dTotalQty += ($entry2['stock'] ?? 0);
						$dTotal += $rowTotal;
					@endphp
					<tr>
						<td class="row-num">{{ $n }}</td>
						<td class="row-date">{{ uk_ts($entry2['created_at'], 'd M Y') }}</td>
						<td class="row-invoice">{{ $entry2['supplier_invoice']['id'] ?? '' }}</td>
						<td class="row-date">{{ $entry2['supplier']['name'] ?? '' }}</td>
						<td class="row-date">{{ $entry2['product']['name'] ?? '' }}</td>
						<td class="right row-date">{{ $entry2['stock'] }}</td>
						<td class="right row-date">{{ number_format($entry2['price'], 2) }}</td>
						<td class="right row-amount">{{ number_format($rowTotal, 2) }}</td>
					</tr>
					@php $n++ @endphp
				@endforeach
				<tr class="ph-total-row">
					<td colspan="5">Total</td>
					<td class="right">{{ $dTotalQty }}</td>
					<td class="right">—</td>
					<td class="right">{{ env('CURRENCY_SYMBOL', '£') }} {{ number_format($dTotal, 2) }}</td>
				</tr>
			@endif
		</tbody>
	</table>
</div>
@endSection
