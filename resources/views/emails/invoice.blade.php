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
</style>
<body>
<div class="container">
   
    <table class="table" style="width:100%" >
        
        <tbody>
          <tr>
            <td><h2>COMPANY NAME</h2></td>
            <td><h3 class="pull-right-1">INVOICE </h3></td>
            <td></td>
          </tr>
         
          <tr>
            <td>
    				
                <address>
                    <strong>{{$data['type']}} Detail:</strong><br>
                        {{$data['name']}}
                </address>
            </td>
            <td></td>
            <td>
                <address>
        			<strong>Shipped To:</strong><br>
    					Jane Smith<br>
    					1234 Main<br>
    					Apt. 4B<br>
    					Springfield, ST 54321
    				</address>
            </td>

    
            
          </tr>

          <tr>
            <td>
                <address>
                    <strong>INVOICE ID:</strong><br>
                    {{$data['id']}}
                </address>
            </td>
            <td></td>
            <td >
                <address>
                    <strong>Order Date:</strong><br>
                    {{$data['date']}}<br><br>
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
							foreach($data['productdata'] as $product){ 
								


								?>
						
									<tr>
    								<td style="width:25%"><?php echo $product['product_id']; ?></td>
    								<td class="text-center"  style="width:25%"><?php echo $product['quantity']; ?></td>
    								<td class="text-center"  style="width:25%"><?php echo $product['unit_price']; ?></td>
    								<td class="text-right"  style="width:25%"><?php echo $product['sub_total']; ?></td>
    							</tr>
								<?php
							}
								
								
								?>
    							<!-- foreach ($order->lineItems as $line) or some such thing here -->
    							
    							
    						</tbody>
    					</table>
                        <table style="width:100%">
                            <tbody>
                                <tr>
    								<td class="thick-line"  style="width:25%"></td>
    								<td class="thick-line"  style="width:25%"></td>
    								<td class="thick-line text-center"  style="width:25%"><strong>Subtotal</strong></td>
    								<td class="thick-line text-right"  style="width:25%">{{$data['subtotal']}}</td>
    							</tr>
    							<tr>
    								<td class="no-line"  style="width:25%"></td>
    								<td class="no-line"  style="width:25%"></td>
    								<td class="no-line text-center"  style="width:25%"><strong>Porterage</strong></td>
    								<td class="no-line text-right"  style="width:25%">{{$data['porterage']}}</td>
    							</tr>
								<tr>
    								<td class="no-line"  style="width:25%"></td>
    								<td class="no-line"  style="width:25%"></td>
    								<td class="no-line text-center"  style="width:25%"><strong>Vat</strong></td>
    								<td class="no-line text-right"  style="width:25%">{{$data['vat']}}</td>
    							</tr>
    							<tr>
    								<td class="no-line"  style="width:25%"></td>
    								<td class="no-line"  style="width:25%"></td>
    								<td class="no-line text-center"  style="width:25%"><strong>Total</strong></td>
    								<td class="no-line text-right"  style="width:25%">{{$data['total']}}</td>
    							</tr>
                            </tbody>
                        </table>
    				</div>
    			</div>
    		</div>
    	</div>
    </div>
</div>
