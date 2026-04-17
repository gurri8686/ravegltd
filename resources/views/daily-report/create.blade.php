@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin');
@endsection

@push('scripts')
<x-ajax-form-data
    formId='saveInvoiceForm'
    :route="route('data_entry.sales_entry.create.store')"
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
                <div class="modal-header bg-success white">
                    <h4 class="modal-title" id="myModalLabel9"><i class="fa fa-tree"></i> New Sales Entry</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            @csrf
                            <div class="form-group">
                                <label for="status">Customer<span class="text-danger">*</span></label>
                                <select required class="form-control select2" name="customer" id="customer">
                                    <option value="">--Select--</option>
                                    @foreach($customers as $customer)
                                    <option value="{{$customer->id}}">{{$customer->name}}</option>
                                    @endforeach
                                </select>
                                <div class="row">
                                    <div class="col-sm-12" data-validate="is_active"></div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    {{-- <button type="button" class="btn grey btn-outline-secondary" data-dismiss="modal">Close</button> --}}
                    <button type="submit" class="btn btn-outline-success">Create</button>
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
                            <th>Customer ID</th>
                            <th>Customer Name</th>
                        </tr>
                    </thead>
                    <tfoot class="thead">
                        <tr>
                            <th>#Invoice ID</th>
                            <th>Customer ID</th>
                            <th>Customer Name</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div> --}}

    </section>
@endsection
