<script type="text/javascript">
    var tableColumns = formData = [];
    var minDate, maxDate;
    // var formData = [];
    switch('{{$tableId}}') {
    case 'daily-book-sales':
        tableColumns =[
            {name: 'invoice_id' , title: 'Invoice No' , data: "invoice_id", orderable:false},
            /*{name: 'created_at' , title: 'Date' , data: "created_at"},*/
            /*{name: 'salesman' , title: 'Salesman' , data: "salesman"},*/
            {name: 'quantity' , title: 'Qty', data: "quantity" },
            {name: 'customer_name' , title: 'Customer Name' , data: "customer_name"},
            {name: 'amount' , title: 'Amount' , data: "amount", orderable:false},
            /*{name: 'mode' , title: 'Payment Mode' , data: "mode" , orderable:false},*/
			
			{name: 'balance' , title: 'Balance' , data: "balance" , orderable:false},
			{name: 'cash' , title: 'Cash' , data: "cash" , orderable:false},
			{name: 'cheque' , title: 'Cheque' , data: "cheque" , orderable:false},
			{name: 'card' , title: 'Card' , data: "card" , orderable:false},
			
            {name: 'action' , title: '' , data: "action" , orderable:false},
        ];
        break;
    case 'daily-book-purchases':
        tableColumns =[
            {name: 'invoice_id' , title: 'Invoice No' , data: "invoice_id", orderable:false},
            {name: 'created_at' , title: 'Date' , data: "created_at"},
            {name: 'salesman' , title: 'Salesman' , data: "salesman"},
            {name: 'supplier_name' , title: 'Supplier Name' , data: "supplier_name"},
            {name: 'amount' , title: 'Invoice Amnt' , data: "amount", orderable:false},
            {name: 'action' , title: '' , data: "action" , orderable:false},
        ];
        break;
        case 'payments-list':
        tableColumns =[
            {data: "s_no",
            render: function (data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            }},
            {name: 'type' , title: 'Type' , data: "type"},
            {name: 'created_at' , title: 'Date' , data: "created_at"},
            {name: 'options' , title: 'Options' , data: "options"},
        ];
        break;
        case 'passbooks-table':
        tableColumns =[
            {data: "s_no",
            render: function (data, type, row, meta) {
                return meta.row + meta.settings._iDisplayStart + 1;
            }},
            {name: 'created_at' , title: 'Date' , data: "created_at"},
            {name: 'customer' , title: 'Customer' , data: "customer"},
            {name: 'amount' , title: 'Amount' , data: "amount"},
            {name: 'payment_id' , title: 'Mode' , data: "payment_id", orderable:false},
            {name: 'type' , title: 'type' , data: "type"},
            {name: 'purpose' , title: 'Purpose' , data: "purpose" , orderable:false}
        ];
        break;
    default:
        // code block
    }
 //Custom filtering function which will search data in column four between two values
 $.fn.dataTable.ext.search.push(
     function( settings, data, dataIndex ) {
         var min = minDate.val();
         var max = maxDate.val();
         var date = new Date( data[4] );
  
         if (
             ( min === null && max === null ) ||
             ( min === null && date <= max ) ||
             ( min <= date   && max === null ) ||
             ( min <= date   && date <= max )
         ) {
             return true;
         }
         return false;
     }
 );
 $(document).ready(function() {
     // Create date inputs
     minDate = new DateTime($('#min'), {
         format: 'YYYY-MM-DD'
     });
     maxDate = new DateTime($('#max'), {
         format: 'YYYY-MM-DD'
     });    
 });
    table=$('#{{$tableId}}').DataTable({    
		drawCallback: function() {
			$('[data-bs-toggle="tooltip"]').tooltip(); 
		},
        serverSide: true,                                                                                                                                                                                                                     
        processing: true,
		responsive: true,
		order: [[0, 'desc']],
        ajax: {
            url: '{{$route}}',
            type: '{{$method}}',
            // data:{ _token: "{{csrf_token()}}"},
            data: function (data) {
                data._token = "{{csrf_token()}}";
                data.min = $('#min').val();
                data.max = $('#max').val();
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
		language: {
        lengthMenu: "Show _MENU_ Records",   // Entries text
			search: ""                           // Remove “Search:” label
		},
		initComplete: function() {
			// Apply Bootstrap small classes
			$('.dataTables_length select').addClass('form-control form-control-sm');
			$('.dataTables_filter input').addClass('form-control form-control-sm');

			// Add placeholder to search input
			$('.dataTables_filter input').attr('placeholder', 'Search...');
		}
    });
    $(document).ready(function() {
     // Refilter the table
        $( "#search" ).submit(function( event ) {
            event.preventDefault();
            table.draw();
        });   
    });

    function print(invoiceId, type){
        if(type == 0){
            // sale
            window.open("{{route('data_entry.sales_entry.invoice.invoiceview', '')}}"+"/"+invoiceId, '_blank');
        } else {
             // supply
            window.open("{{route('data_entry.purchase_entry.invoice.invoiceview', '')}}"+"/"+invoiceId, '_blank');
        }
        // window.location.href = "{{route('data_entry.sales_entry.invoice.invoiceview', '')}}"+"/"+invoiceId;
    } 
	
	function delivery(invoiceId, type){
        if(type == 0){
            // sale
            window.open("{{route('data_entry.sales_entry.invoice.invoiceview-delivery', '')}}"+"/"+invoiceId, '_blank');
        } else {
             // supply
            window.open("{{route('data_entry.purchase_entry.invoice.invoiceview', '')}}"+"/"+invoiceId, '_blank');
        }
        // window.location.href = "{{route('data_entry.sales_entry.invoice.invoiceview', '')}}"+"/"+invoiceId;
    } 

    function download(invoiceId, type){
        if(type == 0){
            // sale
            window.open("{{route('data_entry.sales_entry.invoice.invoicedownload', '')}}"+"/"+invoiceId, '_blank');
        } else {
             // supply
            window.open("{{route('data_entry.purchase_entry.invoice.invoicedownload', '')}}"+"/"+invoiceId, '_blank');
        }
    }

    function mail(invoiceId, type){
        if(type == 0){
            // sale
            var mailURL = "{{route('data_entry.sales_entry.invoice.mail', '')}}"+"/"+invoiceId;
        } else {
            var mailURL = "{{route('data_entry.purchase_entry.invoice.mail', '')}}"+"/"+invoiceId;
        }
        return $.ajax({
                url: mailURL,
                type: 'GET',
                data: {
                    invoice_id: invoiceId
                },
                success: function(data) {
                    if(data == 1){
                        alert("Email sent successfully");
                    } else {
                        alert("Email not found");
                    }
            },
            });
    }  
	
	function email(invoiceId, type){
        if(type == 0){
            // sale
            var mailURL = "{{route('data_entry.sales_entry.invoice_email.send.send', '')}}"+"/"+invoiceId;
        } else {
            var mailURL = "{{route('data_entry.purchase_entry.invoice.mail', '')}}"+"/"+invoiceId;
        }
        return $.ajax({
                url: mailURL,
                type: 'GET',
                data: {
                    invoice_id: invoiceId
                },
                success: function(data) {
                    if(data == 1){
                        alert("Email sent successfully");
                    } else {
                        alert("Email not found");
                    }
            },
            });
    }  

    function clearInput(){
        $('#min').val('');
        $('#max').val('');
        table.draw();
    }

    function editSaleInvoice(id, type){
        console.log('sale -'+id);
    }

    function editPurchaseInvoice(id, type){
        console.log('purchase -'+id);
    }

</script>