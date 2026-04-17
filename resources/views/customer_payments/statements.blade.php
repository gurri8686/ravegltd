@extends('layouts.test')
@section('content')

<form action="" method="get">
	<div class="row pl-4 pr-4 pt-4">
		<div class="col-4">
		@if($balance > 0)
			<h3 class="text-danger">Past Balance : {{$balance}} / Owned</h3>
		@else
			<h3 class="text-success">Past Balance : {{abs($balance)}} / Advance</h3>
		@endif
		</div>
		
		<div class="col-4">
		<h3>Select Customer</h3>
		<select class="form-control select2" name="customer">
		<option value="">--Select--</option>
		@foreach($customers as $c)
			<option {{$customer == $c->id ? "selected" : ""}} value="{{$c->id}}">{{$c->name}}</option>
		@endforeach
		</select>
		</div>
		
		<div class="col-4">
			<div class="row">
				<div class="col-4">
					<h3>Start Date</h3>
					<input type="date" id="startDate" value="{{$start_date}}" name="start_date" class="form-control" />
				</div>
				<div class="col-4">
					<h3>End Date</h3>
					<input type="date" id="endDate" value="{{$end_date}}" name="end_date" class="form-control" />
				</div>
				<div class="col-2 text-right">
					<h3>-</h3>
					<button type="submit" class="btn btn-danger">Search</button>
				</div>
				<div class="col-2 text-right">
					<h3>-</h3>
					<button type="button" class="btn btn-warning">Reset</button>
				</div>
			</div>
		</div>
	</div>
<form>
	
<?php
if($error == 1){
	echo '<p class="text-danger text-center mt-3">Select Customer, Start and End Date</p>';
}else{
?>
<?php
echo '<div class="p-4"><table width="80%" border="1" class="table">';
echo '<tr style="font-weight:bold;" class="bg-warning">';
echo '<td>No.</td>';
echo '<td>Date.</td>';
echo '<td>Invoice No.</td>';
echo '<td>Credit.</td>';
echo '<td>Cash Inv.</td>';
echo '<td>Paid.</td>';
echo '<td>Credit /Adj.</td>';
echo '<td>Discount /Adj.</td>';
echo '<td>Refunded.</td>';
echo '<td>Discount.</td>';
echo '<td>Note.</td>';
echo '<td>Balance.</td>';
echo '<td>Running Balance.</td>';
echo '</tr>';
$calc_balance = $balance;
$i = 0;
foreach($payments as $p){ 
	if($i == 0){
		if(empty((int)$p->amount) && empty((int)$p->credit)){
			$show_balance = $calc_balance + ($p->initiated == 1 ? $p->start_invoice : $p->debt);
			$calc_balance += ($p->initiated == 1 ? $p->start_invoice : $p->debt);
		}else{
			$show_balance = $calc_balance - ($p->amount + $p->credit);
			$calc_balance -= $p->amount + $p->credit;
		}
	}else{
		if(empty((int)$p->amount) && empty((int)$p->credit)){
			$show_balance = $calc_balance + ($p->initiated == 1 ? $p->start_invoice : $p->debt);
			$calc_balance += ($p->initiated == 1 ? $p->start_invoice : $p->debt);
		}else{
			$show_balance = $calc_balance - ($p->amount + $p->credit);
			$calc_balance -= $p->amount + $p->credit;
		}
	}
	$i++;
	echo '<tr>';
	echo '<td>'.$p->id.'</td>';
	echo '<td><b>'.$p->created_at.'</b></td>';
	echo '<td><b>'.str_replace('inv_', '', $p->customer_invoice_id).'</b></td>';
	
	if($p->credit > 0){
		echo '<td class="text-success"><b>'.($p->is_discounted ? '0.00' : $p->credit).'</b></td>';
	}else{
		echo '<td><b>'.($p->is_discounted ? '0.00' : $p->credit).'</b></td>';
	}
	
	echo '<td></td>';
	if($p->amount > 0){
		echo '<td class="text-success"><b>'.$p->amount.'</b></td>';
	}else{
		echo '<td><b>'.$p->amount.'</b></td>';
	}
	
	// Credit /Adj.
	echo '<td>'.($show_balance < 0 && $p->amount > 0 ? '<b class="text-success">'.abs($show_balance).'</b>' : '0.00').'</td>';
	echo '<td>'.($p->is_discounted ? '<b class="text-success">'.$p->credit.'</b>' : '0.00').'</td>';
	
	echo '<td>'.($p->is_refunded == 1 ? "<span class=\"text-success\"><b>Yes</b></span>" : "").'</td>';
	
	echo '<td>'.($p->is_discounted == 1 ? "<span class=\"text-success\"><b>Yes</b></span>" : "").'</td>';
	
	echo '<td>'.$p->note.'</td>';
	
	$debt = $p->initiated == 1 ? $p->start_invoice : $p->debt;
	
	if($debt > 0){
		echo '<td class="text-danger"><b>'.$debt.'</b></td>';
	}else{
		echo '<td><b>'.$debt.'</b></td>';
	}
	
	if($show_balance < 0){
		echo '<td class="text-success"><b>'.abs($show_balance).' / Advance</b></td>';
	}else{
		echo '<td class="text-danger"><b>'.$show_balance.' / Owned</b></td>';
	}
	echo '</tr>';
}
echo '</table></center></div>';

}?>
@endsection