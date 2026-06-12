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
<table class="data-table">
	<thead>
		<tr>
			<th class="num">#</th>
			<th>Date</th>
			<th>Invoice</th>
			<th class="right">Amount</th>
			<th class="right">Paid</th>
			<th class="right">Credit/Adj</th>
			<th class="right">Discount/Adj</th>
			<th class="right">Balance</th>
			@if($type != 'without-balance')
			<th class="right">Running Bal</th>
			@endif
		</tr>
	</thead>
	<tbody>
		@if($type != 'without-balance')
		<tr class="past-balance-row">
			<td colspan="{{ $type != 'without-balance' ? 9 : 8 }}">
				Past Balance: <span class="value">{{ $pastBalance }}</span>
			</td>
		</tr>
		@endif

		@php
			$i = 1;
			$total_net = 0;
			$total_paid = 0;
			$total_credit_adj = 0;
			$total_discount_adj = 0;
			$total_balance = 0;
			$running_balance = $pastBalance;
			$currency = env('CURRENCY_SYMBOL', '£');
		@endphp

		@foreach($invoices as $invoice)
			@if($invoice['is_credited'] != 1)
				@php
					$total_net += $invoice['net_amount'];
					$total_paid += $invoice['total_paid'];
					$total_credit_adj += $invoice['credit_adj'];
					$total_discount_adj += $invoice['total_discounted'];
					$total_balance += $invoice['balance'];
					$running_balance += $invoice['balance'];
				@endphp
				<tr>
					<td class="row-num">{{ $i }}.</td>
					<td class="row-date">{{ uk_ts($invoice['created_at'], 'd M Y') }}</td>
					<td class="row-invoice">{{ $invoice['id'] }}</td>
					<td class="right row-amount">{{ $invoice['net_amount'] }}</td>
					<td class="right {{ $invoice['total_paid'] == 0 ? 'row-zero' : 'row-amount' }}">{{ $invoice['total_paid'] }}</td>
					<td class="right {{ $invoice['credit_adj'] == 0 ? 'row-zero' : 'row-amount' }}">{{ $invoice['credit_adj'] }}</td>
					<td class="right row-zero">0</td>
					<td class="right row-amount">{{ $invoice['balance'] }}</td>
					@if($type != 'without-balance')
					<td class="right row-amount">{{ $running_balance }}</td>
					@endif
				</tr>
				@php $i++; @endphp
			@endif
		@endforeach

		<tr class="total-row">
			<td colspan="3" class="total-label">Total</td>
			<td class="right"><span class="currency">{{ $currency }}</span>{{ number_format($total_net, 2) }}</td>
			<td class="right"><span class="currency">{{ $currency }}</span>{{ number_format($total_paid, 2) }}</td>
			<td class="right"><span class="currency">{{ $currency }}</span>{{ number_format($total_credit_adj, 2) }}</td>
			<td class="right"><span class="currency">{{ $currency }}</span>{{ number_format($total_discount_adj, 2) }}</td>
			<td class="right"><span class="currency">{{ $currency }}</span>{{ number_format($total_balance, 2) }}</td>
			@if($type != 'without-balance')
			<td class="right"><span class="currency">{{ $currency }}</span>{{ number_format($running_balance, 2) }}</td>
			@endif
		</tr>
	</tbody>
</table>
@endSection
