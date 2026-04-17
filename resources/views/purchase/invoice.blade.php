<!DOCTYPE html>
<html>
<head>
<title>Page Title</title>
<link href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>
<script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
</head>
<style>
h3.pull-right-1 {
    font-size: 32px;
    FONT-WEIGHT: 600;
    COLOR: #428bca;
}
.invoice-title {
    DISPLAY: flex;
    JUSTIFY-CONTENT: SPACE-BETWEEN;
}
table.table {
    width: 100%;
}
table, th, td {
  border-bottom: 1px solid #ccc;
  border-collapse: collapse;
}
th, td {
  padding: 10px;
}
.align-top {
vertical-align: top !important;
}
.align-right{
	text-align:right;
}
.align-center{
	text-align:center;
}
</style>
<body onload="window.print()">
<div class="container">

    <table class="table" style="width:100%" >
        <tbody>
          <tr>
            <td><h2>
              @isset($companyDetails->invoice_logo)
                <img class="brand-logo" style="" alt="stack admin logo" src="{{config('filesystems.disks.public.url')}}/{{$companyDetails->invoice_logo}}" weight="40px" height='80px'/>
              @else
              <img class="brand-logo" style="" alt="stack admin logo" src="{{asset('loginDesign/img/dashboard-logo.png')}}" weight="40px" height='80px'/>

              @endisset
			</td>
			<td>
            </h2>
              
          </td>
            <td><h3>Invoice</h3></td>
            <!--<td><h3 class="pull-right-1" style="text-align: center;" >INVOICE </h3></td>-->
			<td class="align-right">
			<span style="">
              <!-- R & A Veg Ltd -->
              @isset($companyDetails->company_name)<h4>{{$companyDetails->company_name}}</h4>@endisset
              
              @isset($companyDetails->address1)<p>{{$companyDetails->address1}}</p>@endisset
              
              @isset($companyDetails->country)<p>{{$companyDetails->country}}</p>@endisset
              
              @isset($companyDetails->zipcode)<p>{{$companyDetails->zipcode}}</p>@endisset
              
              @isset($companyDetails->country)<span>Email: </span>{{$companyDetails->email}}@endisset
              
              @isset($companyDetails->telephone)<span>Phone: </span>+{{$companyDetails->telephone}}@endisset
              

            </span>
			</td>
          </tr>

          <tr>
            <td>
    			<address>
    				<strong>Supplier Detail:</strong><br>
					<?php $supplier =  \App\Models\Supplier::where(['id' => $data->supplier_id])->first(); ?>
					{{$supplier->name}}<br>
          {{($supplier->email)?"Email: ".$supplier->email:""}}<br>
          {{($supplier->address1)?"Address: ".$supplier->address1:""}} <br>
    				</address>
            </td>
            <td></td>
            <td>            
            </td>
			<td class="align-right">
				<address class="pull-right-1" style="margin-left: 37%;" >
                    <strong>Invoice Detail:</strong><br>
                    Invoice Id: #<b>{{$data->id}}</b><br>
                    Date: <b>{{$data->created_at}}</b>
                </address>
			</td>
          </tr>
        </tbody>
      </table>



    <div class="row">
    	<div class="col-md-12">
    		<div class="panel panel-default">
    			<div class="panel-heading">
    				<h3 class="panel-title"><strong>Order summary</strong></h3>
    			</div>
    			<div class="panel-body">
    				<div class="table-responsive">
    					<table class="table table-condensed">
    						<thead>
                                <tr>
        							<td><strong>Item</strong></td>
        							<td class="text-center"><strong>Price</strong></td>
        							<td class="text-center"><strong>Quantity</strong></td>
        							<td class="text-right"><strong>Totals</strong></td>
                                </tr>
    						</thead>
    						<tbody>
                                <?php 
								$subtoal = 0; 
								$total = [];
								?>
    							@foreach ($data->product as $item)

                                    <tr>
                                        <td>
                                            <?php $getjson = json_decode($item->product_info);

                                             ?>
                                             {{$getjson->name}}
                                        </td>
                                        <td class="text-center">{{$item->unit_price}}</td>
                                        <td class="text-center">{{$item->quantity}}</td>
                                        <td class="text-right">{{env('CURRENCY_SYMBOL', '£')}} {{$item->sub_total}}</td>
										<?php 
										$subtoal = $subtoal + $item->sub_total; 
										$total[] = $item->sub_total;
										?>
                                    </tr>

                                @endforeach
    							<!-- foreach ($order->lineItems as $line) or some such thing here -->
								<tr>
									<td></td><td></td>
									<td class="align-center"><b>Subtotal</b></td>
									<td class="align-right">{{env('CURRENCY_SYMBOL', '£')}} {{$subtoal}}</td>
								</tr>
								<tr>
									<td></td><td></td>
									<td class="align-center"><b>Porterage</b></td>
									<td class="align-right">{{env('CURRENCY_SYMBOL', '£')}} {{env('PORTERAGE')}}</td>
								</tr>
								<tr>
									<td></td><td></td>
									<td class="align-center"><b>Vat</b></td>
									<td class="align-right">{{env('VAT')}}%</td>
								</tr>
								<?php
								$amount = array_sum($total);      // Base bill
								$vatPercent = (float)env('VAT');     // VAT 5%
								$porterage = (float)env('PORTERAGE');     // Fixed porterage

								$vatAmount = ($amount * $vatPercent) / 100;
								$total = $amount + $vatAmount + $porterage;
								?>
								<tr>
									<td></td><td></td>
									<td class="align-center"><b>Total</b></td>
									<td class="align-right">{{env('CURRENCY_SYMBOL', '£')}} {{$total}}</td>
								</tr>

    						</tbody>
    					</table>
                        
    				</div>
    			</div>
    		</div>
    	</div>
    </div>
</div>
