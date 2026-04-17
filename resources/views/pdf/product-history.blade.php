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
		<h2 class="mb-20 font-16">Supplier</h2>
        <table class="w-full border-collapse border-spacing-0">
			<thead>
				<tr>
					<td class="border-b-2 border-main pb-3 pl-3 font-bold text-main">Sl No.</td>
					<td class="border-b-2 border-main pb-3 pl-2 font-bold text-main">Date</td>
					<td class="border-b-2 border-main pb-3 pl-2 text-left font-bold text-main">Supplier Name</td>
					<td class="border-b-2 border-main pb-3 pl-2 text-left font-bold text-main">Product</td>
					<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Quantity</td>
					<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Unit Price</td>
					<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main text-right">Total</td>
				</tr>
			</thead>
			<tbody>
				@php $i = 1; @endphp
				@foreach($suppliers as $entry)
				<tr>
					<td class="border-b py-3 pl-3">{{$i}}</td>
					<td class="border-b py-3 pl-2">{{$entry['created_at']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['supplier']['name']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['product']['name']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['quantity']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['unit_price']}}</td>
					<td class="border-b py-3 pl-2 text-right">{{$entry['unit_price'] * $entry['quantity'] }}</td>
				</tr>
				@php $i++ @endphp
				@endforeach
			</tbody>
        </table>
		<h2 class="mt-20 mb-20 font-16">Supplier Returns</h2>
		<table class="w-full border-collapse border-spacing-0">
          <thead>
            <tr>
				<td class="border-b-2 border-main pb-3 pl-3 font-bold text-main">Sl No.</td>
				<td class="border-b-2 border-main pb-3 pl-2 font-bold text-main">Date</td>
				<td class="border-b-2 border-main pb-3 pl-2 text-left font-bold text-main">Invoice</td>
				<td class="border-b-2 border-main pb-3 pl-2 text-left font-bold text-main">Supplier Name</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Product</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Quantity</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main text-left">Unit Price</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main text-right">Total</td>
			</tr>
          </thead>
          <tbody>
				@php $j = 1; @endphp
				@foreach($supplier_returns as $entry)
				<tr>
					<td class="border-b py-3 pl-3">{{$j}}</td>
					<td class="border-b py-3 pl-2">{{$entry['created_at']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['invoice_id']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['supplier']['name']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['product']['name']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['stock']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['price']}}</td>
					<td class="border-b py-3 pl-2 text-right">{{$entry['stock'] * $entry['price'] }}</td>
				</tr>
				@php $j++ @endphp
				@endforeach
          </tbody>
        </table>
		
		<h2 class="mt-20 mb-20 font-16">Sales</h2>
		<table class="w-full border-collapse border-spacing-0">
          <thead>
            <tr>
				<td class="border-b-2 border-main pb-3 pl-3 font-bold text-main">Sl No.</td>
				<td class="border-b-2 border-main pb-3 pl-2 font-bold text-main">Date</td>
				<td class="border-b-2 border-main pb-3 pl-2 text-left font-bold text-main">Invoice No</td>
				<td class="border-b-2 border-main pb-3 pl-2 text-left font-bold text-main">Customer Name</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Product</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Quantity</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main text-left">Unit Price</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main text-right">Total</td>
			</tr>
          </thead>
          <tbody>
				@php $k = 1; @endphp
				@foreach($sales as $entry)
				<tr>
					<td class="border-b py-3 pl-3">{{$k}}</td>
					<td class="border-b py-3 pl-2">{{$entry['created_at']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['customer_invoice_id']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['customer']['name']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['product']['name']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['quantity']}}</td>
					<td class="border-b py-3 pl-2">{{$entry['unit_price']}}</td>
					<td class="border-b py-3 pl-2 text-right">{{$entry['quantity'] * $entry['unit_price'] }}</td>
				</tr>
				@php $k++ @endphp
				@endforeach
          </tbody>
        </table>
		
		<h2 class="mt-20 mb-20 font-16">Customer Returns</h2>
		<table class="w-full border-collapse border-spacing-0">
          <thead>
            <tr>
				<td class="border-b-2 border-main pb-3 pl-3 font-bold text-main">Sl No.</td>
				<td class="border-b-2 border-main pb-3 pl-2 font-bold text-main">Date</td>
				<td class="border-b-2 border-main pb-3 pl-2 text-left font-bold text-main">Invoice No</td>
				<td class="border-b-2 border-main pb-3 pl-2 text-left font-bold text-main">Customer Name</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Product</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Quantity</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main text-left">Unit Price</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main text-right">Total</td>
			</tr>
          </thead>
          <tbody>
			<?php
			//print_r($customer_returns->toArray()); exit;
			?>
				@php $m = 1; @endphp
				@foreach($customer_returns as $entry2)
				<tr>
					<td class="border-b py-3 pl-3">{{$m}}</td>
					<td class="border-b py-3 pl-2">{{$entry2['created_at']}}</td>
					<td class="border-b py-3 pl-2">{{($entry2['customer_invoice']['id'])}}</td>
					<td class="border-b py-3 pl-2">{{$entry2['customer']['name']}}</td>
					<td class="border-b py-3 pl-2">{{$entry2['product']['name']}}</td>
					<td class="border-b py-3 pl-2">{{$entry2['stock']}}</td>
					<td class="border-b py-3 pl-2">{{$entry2['price']}}</td>
					<td class="border-b py-3 pl-2 text-right">{{$entry2['stock'] * $entry2['price'] }}</td>
				</tr>
				@php $m++ @endphp
				@endforeach
          </tbody>
        </table>
		
		<h2 class="mt-20 mb-20 font-16">Dumps</h2>
		<table class="w-full border-collapse border-spacing-0">
          <thead>
            <tr>
				<td class="border-b-2 border-main pb-3 pl-3 font-bold text-main">Sl No.</td>
				<td class="border-b-2 border-main pb-3 pl-2 font-bold text-main">Date</td>
				<td class="border-b-2 border-main pb-3 pl-2 text-left font-bold text-main">Invoice No</td>
				<td class="border-b-2 border-main pb-3 pl-2 text-left font-bold text-main">Supplier Name</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Product</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main">Quantity</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main text-left">Unit Price</td>
				<td class="border-b-2 border-main pb-3 pl-2 pr-3 text-left font-bold text-main text-right">Total</td>
			</tr>
          </thead>
          <tbody>
				@php $n = 1; @endphp
				@foreach($dumps as $entry2)
				<tr>
					<td class="border-b py-3 pl-3">{{$n}}</td>
					<td class="border-b py-3 pl-2">{{$entry2['created_at']}}</td>
					<td class="border-b py-3 pl-2">{{($entry2['supplier_invoice']['id'])}}</td>
					<td class="border-b py-3 pl-2">{{$entry2['supplier']['name']}}</td>
					<td class="border-b py-3 pl-2">{{$entry2['product']['name']}}</td>
					<td class="border-b py-3 pl-2">{{$entry2['stock']}}</td>
					<td class="border-b py-3 pl-2">{{$entry2['price']}}</td>
					<td class="border-b py-3 pl-2 text-right">{{$entry2['stock'] * $entry2['price'] }}</td>
				</tr>
				@php $n++ @endphp
				@endforeach
          </tbody>
        </table>
		
      </div>
@endSection