@extends('layouts.invoice.customer')

@section('top')
<tbody>
  <tr>
	<td class="border-r pr-4">
	  <div>
		<p class="whitespace-nowrap text-slate-400 text-right">From - To Date</p>
		<p class="whitespace-nowrap font-bold text-main text-right">{{$start_date}} - {{$end_date}}</p>
	  </div>
	</td>
	<td class="pl-4">
	  <div>
		<p class="whitespace-nowrap text-slate-400 text-right">Today</p>
		<p class="whitespace-nowrap font-bold text-main text-right">{{date('Y-m-d')}}</p>
	  </div>
	</td>
  </tr>
</tbody>
@endSection

@section('content')
<div class="px-14 py-10 text-sm text-neutral-700">
        <table class="w-full border-collapse border-spacing-0">
          <thead>
            <tr>
              <td class="border-b-2 border-main pb-3 pl-3 font-bold text-main">#</td>
              <td class="border-b-2 border-main pb-3 pl-2 font-bold text-main">Date</td>
              <td class="border-b-2 border-main pb-3 pl-2 text-left font-bold text-main">Invoice</td>
              <td class="border-b-2 border-main pb-3 pl-2 text-left font-bold text-main">Amount</td>
              <td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Paid</td>
              <td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Credit/Adj</td>
              <td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Discount/Adj</td>
              <td class="border-b-2 border-main pb-3 pl-2 pr-3 {{$type != 'without-balance' ? 'text-left' : 'text-right'}} font-bold text-main">Balance</td>
				@if($type != 'without-balance')
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-right font-bold text-main">Running Bal</td>
				@endif
			</tr>
          </thead>
          <tbody>
			@if($type != 'without-balance')
			<tr>
				<td colspan=9 class="border-b py-3 pl-3 text-right font-bold text-main">Past Balance: {{$pastBalance}}</td>
			</tr>
			@endif
			
			<?php
			$i = 1;
			?>
			
			@php
				$i = 1;
				$total_net = 0;
				$total_paid = 0;
				$total_credit_adj = 0;
				$total_discount_adj = 0;
				$total_balance = 0;
				$running_balance = $pastBalance;
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
              <td class="border-b py-3 pl-3">{{$i}}.</td>
              <td class="border-b py-3 pl-2">{{$invoice['created_at']}}</td>
              <td class="border-b py-3 pl-2">{{$invoice['id']}}</td>
              <td class="border-b py-3 pl-2">{{$invoice['net_amount']}}</td>
              <td class="border-b py-3 pl-2">{{$invoice['total_paid']}}</td>
              <td class="border-b py-3 pl-2">{{$invoice['credit_adj']}}</td>
              <td class="border-b py-3 pl-2">0</td>
              <td class="border-b py-3 pl-2 {{$type != 'without-balance' ? 'text-left' : 'text-right'}}">{{$invoice['balance']}}</td>
				@if($type != 'without-balance')
				<td class="border-b py-3 pl-2 pr-3 text-right">{{$running_balance}}</td>
				@endif
            </tr>
			@endif
			<?php $i++; ?>
			@endforeach
			<tr>
				<td class="border-b py-3 pl-2 font-bold text-main">Total</td>
				<td class="border-b py-3 pl-2"></td>
				<td class="border-b py-3 pl-2"></td>

				<!-- Totals start here -->
				<td class="border-b py-3 pl-2 font-bold">{{env('CURRENCY_SYMBOL', '£')}} {{ $total_net }}</td>
				<td class="border-b py-3 pl-2 font-bold">{{env('CURRENCY_SYMBOL', '£')}} {{ $total_paid }}</td>
				<td class="border-b py-3 pl-2 font-bold">{{env('CURRENCY_SYMBOL', '£')}} {{ $total_credit_adj }}</td>
				<td class="border-b py-3 pl-2 font-bold">{{env('CURRENCY_SYMBOL', '£')}} {{ $total_discount_adj }}</td>
				<td class="border-b py-3 pl-2 font-bold {{$type != 'without-balance' ? 'text-left' : 'text-right'}}">{{env('CURRENCY_SYMBOL', '£')}} {{ $total_balance }}</td>
				@if($type != 'without-balance')
				<td class="border-b py-3 pl-2 font-bold pr-3 text-right">{{env('CURRENCY_SYMBOL', '£')}} {{ $running_balance }}</td>
				@endif
			</tr>
            <!--<tr>
              <td colspan="8">
                <table class="w-full border-collapse border-spacing-0">
                  <tbody>
                    <tr>
                      <td class="w-full"></td>
                      <td>
                        <table class="w-full border-collapse border-spacing-0">
                          <tbody>
                            <tr>
                              <td class="border-b p-3">
                                <div class="whitespace-nowrap text-slate-400">Net total:</div>
                              </td>
                              <td class="border-b p-3 text-right">
                                <div class="whitespace-nowrap font-bold text-main">$320.00</div>
                              </td>
                            </tr>
                            <tr>
                              <td class="p-3">
                                <div class="whitespace-nowrap text-slate-400">VAT total:</div>
                              </td>
                              <td class="p-3 text-right">
                                <div class="whitespace-nowrap font-bold text-main">$64.00</div>
                              </td>
                            </tr>
                            <tr>
                              <td class="bg-main p-3">
                                <div class="whitespace-nowrap font-bold text-white">Total:</div>
                              </td>
                              <td class="bg-main p-3 text-right">
                                <div class="whitespace-nowrap font-bold text-white">$384.00</div>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </td>
            </tr>-->
          </tbody>
        </table>
      </div>
@endSection