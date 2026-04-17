@extends('layouts.test')
@section('content')
<?php
echo '<div class="p-4"><center><h3 class="mb-2">Last Balance: <b>'.$balance.'</b><br /></h3>';
echo '<table width="80%" border="1" class="table">';
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
	echo '<td><b>'.$p->customer_invoice_id.'</b></td>';
	
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
	echo '<td>0.00</td>';
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
		echo '<td class="text-success"><b>'.$show_balance.'</b></td>';
	}else{
		echo '<td class="text-danger"><b>'.$show_balance.'</b></td>';
	}
	echo '</tr>';
}
echo '</table></center></div>';
?>
@endsection