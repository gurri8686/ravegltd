<!DOCTYPE html>
<html>
   <body>
      <style>
         .invoice-text {
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
         padding: 5px;
         }
         .panel-title{
         padding: 0px;
         height: 10px;
         }
         .table.table1 td {
          border-bottom: none;
         }
         table.table1 {
        border-bottom: none;
      }
      </style>
      <body onload="window.print()">
         <div class="container">
            <table class="table table1" style="width:100%" >
               <tbody>
                  <tr>
                    <td>
                      @isset($companyDetails->invoice_logo)
                        <img class="brand-logo" style="float: left;" alt="stack admin logo" src="/img/{{$companyDetails->invoice_logo}}" weight="40px"  height='80px'/>
                      @else
                        <img class="brand-logo" style="float: left;" alt="stack admin logo" src="{{asset('loginDesign/img/dashboard-logo.png')}}" weight="40px"  height='80px'/>

                      @endisset
                    </td>
                    <td>

                      <span style="float: left;margin-left: 20px;margin-top: 2px;">
                      <!-- R & A Veg Ltd -->
                      @isset($companyDetails->company_name){{$companyDetails->company_name}}@endisset
                      <br>
                      @isset($companyDetails->address1){{$companyDetails->address1}}@endisset
                      <br>
                      @isset($companyDetails->country){{$companyDetails->country}}@endisset
                      <br>
                      @isset($companyDetails->zipcode){{$companyDetails->zipcode}}@endisset
                      <br>
                      @isset($companyDetails->country)<span>Email: </span>{{$companyDetails->email}}@endisset
                      <br>
                      @isset($companyDetails->telephone)<span>Phone: </span>+{{$companyDetails->telephone}}@endisset
                      <br>

                    </span>
                  </td>

                     <td>
                        <strong class="invoice-text">INVOICE</strong>
                     </td>
                     <!-- <td><h3 class="pull-right-1" style="float: right;">INVOICE </h3></td> -->
                     <!-- <td><a href="#" onclick="window.print()" target="">Print</a></td> -->
                  </tr>
                  <tr>
                     <td>
                     </td>
                  </tr>
               </tbody>
            </table>
            <br> <br> <br><br><br>
              @foreach($datas as $data)
            <div class="row">
               <div class="col-md-12">
                  <div class="panel panel-default">
                     <div class="panel-heading">
                        <h3 class="panel-title"><strong>Invoice Order summary</strong></h3>
                        <span>
                          <strong>Invoice Id : </strong>{{$data->id}}
                        <br/>
                          <strong>Invoice Date: </strong>{{ \Carbon\Carbon::parse($data->created_at)->format('Y-m-d') }}
                        </span>
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
                                <?php $subtoal = 0; ?>
                                   @foreach ($data->product as $item)
                                    <tr>
                                        <td>
                                            <?php $getjson = json_decode($item->product_info);

                                             ?>
                                             {{$getjson->name}}
                                        </td>
                                         <td class="text-center">{{$item->unit_price}}</td>
                                         <td class="text-center">{{$item->quantity}}</td>
                                         <td class="text-right">{{$item->sub_total}}</td>
                                         <?php $subtoal = $subtoal + $item->sub_total; ?>
                                    </tr>

                                @endforeach

                                 <tr>
                                    <td>
                                    </td>
                                    <td>
                                    </td>
                                    <td class="text-center"><strong>Subtotal</strong></td>
                                    <td class="text-center">{{($data->order)?$data->order->sub_total:""}}</td>
                                 </tr>
                                 <tr>
                                    <td>
                                    </td>
                                    <td>
                                    </td>
                                    <td class="text-center"><strong>Porterage</strong></td>
                                    <td class="text-center">{{($data->order)?$data->order->porterage:""}}</td>
                                 </tr>
                                 <tr>
                                    <td>
                                    </td>
                                    <td>
                                    </td>
                                    <td class="text-center"><strong>Vat</strong></td>
                                    <td class="text-center">{{($data->order)?$data->order->vat:""}}</td>
                                 </tr>
                                 <tr>
                                    <td>
                                    </td>
                                    <td>
                                    </td>
                                    <td class="text-center"><strong>Total</strong></td>
                                    <td class="text-center">{{($data->order)?$data->order->total:""}}</td>
                                 </tr>
                                 <!-- foreach ($order->lineItems as $line) or some such thing here -->
                              </tbody>
                           </table>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            @endforeach
         </div>
   </body>
</html>
