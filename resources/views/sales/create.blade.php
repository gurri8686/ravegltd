@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('styles')
<style>
    /* Hide breadcrumb/page header on this page */
    .content-header { display: none !important; }

    /* Orange select2 */
    .select2-container--default .select2-selection--single {
        height: 38px;
        border-color: #ced4da;
        border-radius: 4px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
        color: #333;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #f97316 !important;
        box-shadow: 0 0 0 0.2rem rgba(249,115,22,0.25) !important;
        outline: none;
    }
    .select2-container--default .select2-results__option--highlighted,
    .select2-container--default .select2-results__option--highlighted[aria-selected],
    .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: #f97316 !important;
        color: #fff !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field:focus {
        border-color: #f97316 !important;
        outline: none;
    }
</style>
@endpush

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
			responsive: true,
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
    });
</script>
@endpush

@section('content')
    <section class="">
        <form id="saveInvoiceForm" method="post" action="{{ route('data_entry.sales_entry.create.store') }}">
            <div class="modal-content">
                <div class="modal-header" style="background:#f97316; color:#fff; border-bottom:2px solid #e8650e; padding:12px 16px; border-radius:0;">
                    <h4 class="modal-title" style="font-size:16px; font-weight:700; letter-spacing:0.3px; margin:0;">
                        <i class="fa fa-tree" style="margin-right:8px;"></i> New Sales Entry
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 p-2">
                            @csrf
                            <div class="form-group">
                                <label for="customer" style="font-size:13px; font-weight:600; color:#555;">Customer<span class="text-danger">*</span></label>
                                <select required class="form-control w-100 select2" name="customer" id="customer">
                                    <option value="#">Guest Customer</option>
                                    @foreach($customers as $customer)
                                    <option value="{{$customer->id}}">{{$customer->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="py-2 border border-top-1">
                    <div class="col-12 text-right">
                        <button type="submit" style="background:#f97316; border:none; color:#fff; padding:8px 22px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer; letter-spacing:0.2px;">
                            Start Invoice Creation <i class="fa fa-arrow-right" style="margin-left:6px;"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection
