@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection



@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<section class="users-list-wrapper">
	 <div class="users-list-filter px-1">
            <div class="row  mb-1">
                <div class="col-md-12 col-sm-12 col-lg-8 p-0">
				<h2>Customers History</h2>
                  
                 </div>
			</div>
		</div>
  <div class="loader" id="loaderSection"style="display:none">
                      <div class="inloder">
                        <img class="loader-img"src="/img/loading-gif.webp">
                      </div>
                    </div>
       <div class="users-list-filter px-1">
            <div class="row  mb-1">
                <div class="col-12 col-sm-6 col-lg-3"> </div>
                <div class="col-12 col-sm-6 col-lg-3"></div>
                <div class="col-12 col-sm-7 col-lg-3"></div>
                <div class="col-12 col-sm-8 col-lg-3 d-flex align-items-center">
                </div>
            </div>
    </div>

    <div class="users-list-table">
        <div class="card">
            <div class="card-content ">
                <div class="card-body">
                    <!-- datatable start -->
					<?php $cdate = date('Y-m-d'); ?>
					
					<form id="search-history">
						@csrf
						<div class="row">
							<div class="col-lg-5 col-md-12">
							<label for="status">Customer<span class="text-danger">*</span></label><br />
							<select required class="form-control select2 select2-100 customerdropdown" name="customer" id="customerid">
								<option value="">-- Select --</option>
								@foreach($customers as $customer)
								 <option value="{{$customer->id}}">{{$customer->name}} </option>
								@endforeach
							</select>
							</div>
							<div class="col-lg-3 col-md-5">
								<label for="status">From<span class="text-danger">*</span></label>
								<input class="form-control" type="text" id="min" name="min" value="{{$cdate}}" />
							</div>
							<div class="col-lg-3 col-md-5">
								<label for="status">To<span class="text-danger">*</span></label>
								<input class="form-control" type="text" id="max" name="max" value="{{$cdate}}">
							</div>
							<div class="col-lg-1 col-md-2 text-right">
								<input type="submit" class="btn btn-primary mt-27" value="Search" />
							</div>
						</div>
					</form>
					<div class="row">
						<div class="col-12 text-center mt-2">
							<button class="btn p-1 text-dark" type="button" 
								data-toggle="collapse" 
								data-target="#advancedOptions" 
								aria-expanded="false" 
								aria-controls="collapseExample">
								Advanced Options <i class="fa fa-caret-down"></i>
							</button>
							<div class="collapse bg-gray" id="advancedOptions">
							  <div class="card card-body p-0">
								<div class="row p-1" style="background: #efefef;">
									<div class="col-12">
										<button onclick="documentAction('print_all')" class="btn bg-warning text-white">
											<i class="fa fa-print"></i> Print All
										</button>
										<button onclick="documentAction('email_all')" class="btn bg-warning text-white">
											<i class="fa fa-envelope"></i> Email All
										</button>
										<button onclick="documentAction('statement_all')" class="btn bg-warning text-white">
											<i class="fa fa-file"></i> Statement All
										</button>
										<button onclick="documentAction('download_all')" class="btn bg-warning text-white">
											<i class="fa fa-download"></i> Download All
										</button>
									</div>
									<div class="col-12 mt-2">
										<button onclick="documentAction('print_selected')" class="btn bg-info text-white">
											<i class="fa fa-print"></i> Print Selected
										</button>
										<button onclick="documentAction('email_selected')" class="btn bg-info text-white">
											<i class="fa fa-envelope"></i> Email Selected
										</button>
										<button onclick="documentAction('statement_selected')" class="btn bg-info text-white">
											<i class="fa fa-file"></i> Statement Selected
										</button>
										<button onclick="documentAction('downloaded_selected')" class="btn bg-info text-white">
											<i class="fa fa-download"></i> Download Selected
										</button>
									</div>
								</div>
							  </div>
							</div>
						</div>
					</div>
                    <div class="table-responsive">
                        <?php /*
						<table border="0" cellspacing="5" cellpadding="5" class="table-clr">
                            <tbody>
                                <tr>
                                    <form id="search-history">
                                      @csrf
                                      <td>
                                        <label for="status">Customer<span class="text-danger">*</span></label>
                                        <select required class="form-control select2 customerdropdown" name="customer" id="customerid">
                                            <option value="#"></option>
                                            @foreach($customers as $customer)
                                             <option value="{{$customer->id}}">{{$customer->name}} </option>
                                            @endforeach
                                        </select>
                                      </td>
                                      <?php $cdate = date('Y-m-d'); ?>
                                        <td>From:</td>
                                        <td><input type="text" id="min" name="min" value="{{$cdate}}"></td>
                                        <td>To:</td>
                                        <td><input type="text" id="max" name="max" value="{{$cdate}}"></td>
                                        <td class="text-center"><input type="submit" class="btn btn-primary filter-button" value="Search"></td>
                                        <!-- <td class="text-center"><input type="button" class="btn btn-warning filter-button" value="Clear" onClick="clearInput()"></td> -->
                                    </form>
                                </tr>
                            </tbody>
                        </table>
						*/ ?>
                          <!--<div id="pdfdownloadinvoice" class="row" style="opacity: 0; margin-top:50px">
							<div class="col-12 text-left">
								<button onclick="pdfdownload()" class="pdfdownload">Download</button>
								<button onclick="invoiceprint()" class="pdfdownload">Print</button>
							</div>
                          </div>-->
						  
						<!-- advanced options starts -->
						
						<!-- advanced options ends -->		  
                           <table id="customer_history-sales" class="table c-table table-hover">
                           </table>
                    </div>
                    <!-- datatable ends -->
                </div>
            </div>
        </div>
    </div>
    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>

</section>
@endsection

@push('scripts')
   <script>

   $(document).ready(function() {
       // Create date inputs
       minDate = new DateTime($('#min'), {
           format: 'YYYY-MM-DD'
       });
       maxDate = new DateTime($('#max'), {
           format: 'YYYY-MM-DD'
       });
   });

	function documentAction(type){
		let data = {
			customer_id: $('#customerid').val(),
			start_date : $('#min').val(),
			end_date : $('#max').val(),
		}
		let checkedValues = $('.checkAlllist:checked').map(function() {
			return $(this).val();
		}).get();
		
		/*const params = new URLSearchParams();

		Object.entries(data).forEach(([key, value]) => {
		  if (Array.isArray(value)) {
			value.forEach(v => params.append(key + '[]', v));
		  } else {
			params.append(key, value);
		  }
		});

		const queryString = params.toString();
		console.log(queryString);*/
		
		if(!$('#customerid').val() || !$('#min').val() || !$('#max').val()){
			alert('Customer, Start Date and End Date is Required!');
			return;
		}
		
		let _p = 0;
		switch(type){
			case 'print_all':
				_p = queryStringGenerator(data)
				break;
			
			case 'email_all':
				_p = queryStringGenerator(data)
				break;
				
			case 'statement_all':
				_p = queryStringGenerator(data)
				break;
			
			case 'download_all':
				_p = queryStringGenerator(data)
				break;
				
			case 'print_selected':
				if(checkedValues.length <= 0){
					alert('Please select minimum one row from list.')
					return;
				}
				data.ids = checkedValues
				_p = queryStringGenerator(data)
				break;
				
			case 'email_selected':
				if(checkedValues.length <= 0){
					alert('Please select minimum one row from list.')
					return;
				}
				data.ids = checkedValues
				_p = queryStringGenerator(data)
				break;
			
			case 'statement_selected':
				if(checkedValues.length <= 0){
					alert('Please select minimum one row from list.')
					return;
				}
				data.ids = checkedValues
				_p = queryStringGenerator(data)
				break;
				
			case 'downloaded_selected':
				if(checkedValues.length <= 0){
					alert('Please select minimum one row from list.')
					return;
				}
				data.ids = checkedValues
				_p = queryStringGenerator(data)
				break;
		}
		// redirect with url query string.
		window.open('/historical_reports/customer_history/report/document/'+type+'?'+_p, '_blank');
	}


   $("#search-history").submit(function(e) {

     var ev = document.getElementById("customerid");

     if(ev.value == "#"){
       alert("Please select customer Before search invoice  history!");
       return false;
     }

     tableColumns =[
         {name: 'allcheck' , title: '<input type="checkbox" id="checkAll" name="checkAll" value="">' , data: "allcheck" ,orderable:false},
		 {name: 'invoice_id' , title: 'Invoice No.' , data: "invoice_id", orderable:false},
         {name: 'created_at' , title: 'Date' , data: "created_at"},
         /*{name: 'salesman' , title: 'Salesman' , data: "salesman"},*/         
         /*{name: 'quantity' , title: 'QTY', data: "quantity" },*/
         {name: 'credit' , title: 'Credit Inv', data: "credit" },
         {name: 'cash' , title: 'Cash Inv', data: "cash" },
         {name: 'mode' , title: 'Payment Mode' , data: "mode" , orderable:false},
         {name: 'amount' , title: 'Balance' , data: "amount", orderable:false},
         {name: 'running_balance' , title: 'Running Balance' , data: "running_balance", orderable:false},
		 /*{name: 'actions' , title: '' , data: "actions", orderable:false},*/
     ];

      e.preventDefault(); // avoid to execute the actual submit of the form.
      formdata= $(this).serialize(); // serializes the form's elements.
      console.log(formdata);
      var actionUrl = '/historical_reports/customer_history/ajax/customer_history';

      if ($.fn.DataTable.isDataTable('#customer_history-sales')) {
        var table = $('#customer_history-sales').DataTable();
         table.destroy();

        }

       table=$('#customer_history-sales').DataTable({
					lengthMenu: [
						[10, 25, 50, 100, -1],  // values (-1 is special for All)
						[10, 25, 50, 100, "All"] // labels
					],
                    serverSide: true,
                    processing: true,
                    paging: true,
                    searching: false,
                    order: [[0, 'desc']],
                      // data:formdata,
                    ajax: {
                        url: actionUrl,
                        type: 'POST',
                        data: function (data) {
                            data._token = "{{csrf_token()}}";
                            data.min = $('#min').val();
                            data.max = $('#max').val();
                            data.custmer = ev.value;
                        },

                    },


                    dom: 'lBfrtip',
                    buttons: [
						{
							extend: 'copy',
							className: 'btn btn-primary filter-button'  // <-- Bootstrap small button
						},
						{
							extend: 'excel',
							className: 'btn btn-success filter-button'
						},
						{
							extend: 'pdf',
							className: 'btn btn-danger filter-button'
						},
						{
							extend: 'print',
							className: 'btn btn-info filter-button'
						}
					],
                    columns: tableColumns,
                });


  });


      function deleteinvoice(id){
        event.preventDefault();
        var del=confirm("Are you sure you want to delete this Invoice?\n");
          if (del==true){

            window.location.href = '/data_entry/sales_entry/invoice/delete/'+id;
          }
      }
      $(document).delegate('#checkAll','change',function(){

        if($('input[type=checkbox]').prop('checked')){

          $('input[type=checkbox]').prop('checked',true);

          $("#pdfdownloadinvoice").css("opacity", "1");

        }else{

          $('input[type=checkbox]').prop('checked',false);
              $("#pdfdownloadinvoice").css("opacity", "0");
        }

      });
      $(document).delegate('.checkAlllist','change',function(){
          $("#pdfdownloadinvoice").css("opacity", "1");
        if (!$(this).is(":checked")){
              $('#checkAll').prop('checked', false);
          }



      });


      function pdfdownload(){

        var invoices = new Array();
          $("input[name='checkAlllist']:checked").each(function() {
                invoices.push($(this).val());
              });
              if(invoices.length == 0){
               alert("Please select invoices for Detail!");
               return;
             }
              $("#loaderSection").css("display", "block");

             var actionUrl = '/historical_reports/customer_history/ajax/customer_history_pdf';
             $.ajax({
                     url: actionUrl,
                     type: 'POST',
                     data: {
                        _token : "{{csrf_token()}}",
                         invoices: invoices
                     },
                     xhrFields: {
                          responseType: 'blob'
                      },
                     success: function(data) {

                          $("#loaderSection").css("display", "none");

                          var blob = new Blob([data]);
                             var link = document.createElement('a');
                             link.href = window.URL.createObjectURL(blob);
                             link.download = "invoice.pdf";
                             link.click();
                        // window.open(data);
                        // window.location.replace(data);
                        // window.location.href = $location;
                      },
                 });

      }
      function invoiceprint(){
        var invoices = new Array();
        var invoicestring = '';

          $("input[name='checkAlllist']:checked").each(function() {
                invoices.push($(this).val());
                if(invoicestring == ''){
                  invoicestring += 'invoiceno[]='+$(this).val();
                }else{
                  invoicestring += '&invoiceno[]='+$(this).val();
                }
              });
              if(invoices.length == 0){
                  alert("Please select invoices for Detail!");
               return;

             }

          setCookie('invoices',invoices,'1',path = '/historical_reports/customer_history/customer_invoice_print');
          var actionUrl = '/historical_reports/customer_history/customer_invoice_print';
              window.open(actionUrl);
             // console.log(actionUrl);
      }

      function setCookie(key, value, expiry,path) {
          var expires = new Date();
          expires.setTime(expires.getTime() + (expiry * 24 * 60 * 60 * 1000));
          document.cookie = key + '=' + value + ';path=' + path;
      }





  </script>
@endpush
