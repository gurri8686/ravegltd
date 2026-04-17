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
            <td class="align-top">
              <h2>
			    @isset($companyDetails->invoice_logo)
                  <img class="brand-logo" style="" alt="stack admin logo" src="{{config('filesystems.disks.public.url')}}/{{$companyDetails->invoice_logo}}" weight="40px"  height='80px'/>
                @else
                <img class="brand-logo" style="" alt="stack admin logo" src="{{asset('loginDesign/img/dashboard-logo.png')}}" weight="40px"  height='80px'/>
                @endisset
              </h2>
			  </td>
			  <td>
              
            </td>
            <td><h3 style="margin-top:40px;">Delivery Invoice</h3></td>
			<td class="align-right">
			<span style="">
              <!-- R & A Veg Ltd -->
              @isset($companyDetails->company_name)<h4>{{$companyDetails->company_name}}</h4>@endisset
              
              @isset($companyDetails->address1) <p>{{$companyDetails->address1}}</p> @endisset
              
              @isset($companyDetails->country)<p>{{$companyDetails->country}}</p> @endisset
              
              @isset($companyDetails->zipcode)<p>{{$companyDetails->zipcode}}</p> @endisset
              
              @isset($companyDetails->country)<p><span>Email: </span>{{$companyDetails->email}}</p> @endisset
              
              @isset($companyDetails->telephone)<p><span>Phone: </span>+{{$companyDetails->telephone}}</p> @endisset
              

            </span>
			</td>
			<!--<td class="aign-right aign-top"><h3 style="text-align: right;vertical-align: top;" >INVOICE </h3></td>-->
            
            <!-- <td><h3 class="pull-right-1" style="float: right;">INVOICE </h3></td> -->
            <!-- <td><a href="#" onclick="window.print()" target="">Print</a></td> -->
          </tr>

          <tr>
            <td style="text-align: left;">
    			<address>
    				<strong>Customer Detail:</strong><br>
					<?php $customer =  \App\Models\Customer::where(['id' => $data->customer_id])->first(); ?>
					{{$customer->name}}<br>
          {{($customer->email)?"Email: ".$customer->email:""}}<br>
          {{($customer->address1)?"Address: ".$customer->address1:""}} <br>
    				</address>
            </td>
            <td></td>
			<td></td>
            <td style="text-align: right;">
            <address class="pull-right-1" style="margin-left: 30%;" >
                    <strong>Invoice Detail:</strong><br>
                    Invoice Id: #<b>{{$data->id}}</b><br>
                    Date: <b>{{ $data->created_at }}</b>
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
                                
    						</thead>
    						<tbody>
								<tr>
        							<td><strong>Items</strong></td>
        							<td class="align-right"><strong>Quantity</strong></td>
                                </tr>
                                <?php 
								$subtoal = 0; 
								$total = [];
								$i = 1;
								?>
    							@foreach ($data->product as $item)
                                    <tr>
                                        <td>
                                            <?php $getjson = json_decode($item->product_info);
                                             ?>
											 {{$i}}. {{$getjson->name}} (<i>{{$item->remarks}}</i>)
                                        </td>
                                        <td class="align-right">{{$item->quantity}}</td>
                                    </tr>
									<?php $i++; ?>
                                @endforeach
								
    							<!-- foreach ($order->lineItems as $line) or some such thing here -->
    						</tbody>
    					</table>
                        
    				</div>
    			</div>
    		</div>
    	</div>
    </div>
</div>
