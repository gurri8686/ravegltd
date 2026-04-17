@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('scripts')
<x-ajax-form-data
    formId='saveInvoiceForm'
    :route="route('data_entry.purchase_entry.create.store')"
    method='POST'
/>
<script type="text/javascript">
    $(document).ready(function () {
        var dtable = $('#grid_element').DataTable({
            "processing": true,
            "serverSide": true,
            "order": [[0, "desc"]],
            "ajax": {
                "url": "{{route('data_entry.sales_entry.create.grid')}}",
                "type": "POST",
                "data": function (data) {
                    data._token = "{{ csrf_token() }}";
                }
            },
            "columns": [
                {"data":"id"},
                {"data":"customer_id"},
                {"data":"customer.name"},
            ]
        });

        /*$(document).on('click', '.delete-element', function () {
            var url = $(this).data('url');
            var _dtable = dtable;
            bootbox.confirm({
                message: "Are you sure to delete this entry?",
                buttons: {
                    confirm: {
                        label: 'Yes',
                        className: 'btn-success'
                    },
                    cancel: {
                        label: 'No',
                        className: 'btn-danger'
                    }
                },
                callback: function (result) {
                    if(result == true){
                        $.ajax({
                            url: url,
                            type: 'DELETE',
                            data: {"_token": "{{ csrf_token() }}"},
                            success: function (response) {
                                if (response.success == true) {
                                    dtable.draw();
                                } else {
                                    alert(response);
                                }
                            }
                        });
                    }
                }
            });

        });*/
    });
</script>
@endpush

@section('content')
    <section class="">
        <form id="saveInvoiceForm" method="post">
            <div class="modal-content">
                <div class="modal-header white" style="background:#F27420 !important;border-radius:0;">
                    <h4 class="modal-title" id="myModalLabel9"><i class="fa fa-tree"></i> New Purchase Entry</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            @csrf
                            <div class="form-group">
                                <label for="status">Supplier<span class="text-danger">*</span></label>
                                <select required class="form-control select2" name="supplier" id="supplier">
                                    @foreach($suppliers as $supplier)
                                    <option value="{{$supplier->id}}" @if($loop->first) selected @endif>{{$supplier->name}}</option>
                                    @endforeach
                                </select>
                                <div class="row">
                                    <div class="col-sm-12" data-validate="is_active"></div>
                                </div>
                            </div>
							
							<div class="form-group">
                                <label for="status">Other Invoice Id<span class="text-danger"></span></label>
                                <input type="text" name="other_invoice_id" valuye="" class="form-control" />
                                <div class="row">
                                    <div class="col-sm-12" data-validate="other_invoice_id"></div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    {{-- <button type="button" class="btn grey btn-outline-secondary" data-dismiss="modal">Close</button> --}}
                    <button type="submit" class="btn" style="background:#F27420;color:#fff;border:none;border-radius:10px;padding:10px 24px;font-weight:600;font-size:13px;">Start Invoice Creation <i class="fa fa-arrow-right" style="margin-left:6px;"></i></button>
                </div>
            </div>
        </form>

        {{-- <div class="modal-header bg-warning white mt-2">
            <h4 class="modal-title" id="myModalLabel9"><i class="fa fa-tree"></i> Invoices</h4>
        </div>
        <div class="card-body bg-white">
            <div class="table-responsive overflow-x">
                <table id="grid_element" class="datatable table table-striped table-hover display nowrap">
                    <thead class="thead">
                        <tr>
                            <th>#Invoice ID</th>
                            <th>Supplier ID</th>
                            <th>Supplier Name</th>
                        </tr>
                    </thead>
                    <tfoot class="thead">
                        <tr>
                            <th>#Invoice ID</th>
                            <th>Supplier ID</th>
                            <th>Supplier Name</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div> --}}

    </section>
@endsection
