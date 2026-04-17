@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin');
@endsection

@push('scripts')
<x-ajax-form-data
    formId='saveProductForm'
    :route="route('settings.payment_methods.create.store')"
    method='POST'
/>
@endpush

@section('content')
<div class="row match-height">
    <div class="col-md-12">
        <div class="card" style="height: 988.725px;">
            <div class="card-header">
                <h4 class="card-title" id="basic-layout-form">Add Payment</h4>
                <a class="heading-elements-toggle"><i class="fa fa-ellipsis-v font-medium-3"></i></a>
                <div class="heading-elements">
                    <ul class="list-inline mb-0">
                        <li><a data-action="collapse"><i class="feather icon-minus"></i></a></li>
                        <li><a data-action="reload"><i class="feather icon-rotate-cw"></i></a></li>
                        <li><a data-action="expand"><i class="feather icon-maximize"></i></a></li>
                        <li><a data-action="close"><i class="feather icon-x"></i></a></li>
                    </ul>
                </div>
            </div>
            <div class="card-content collapse show">
                <div class="card-body">

                    <form class="form" id="saveProductForm" name="saveProductForm">
                    @csrf
                        <div class="form-body">
                            <h4 class="form-section"><i class="feather icon-user"></i>Payment Method</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="type">Payment<span class="text-danger">*</span></label>
                                        <input type="text" id="type" class="form-control" placeholder="Enter Payment Method" name="type">
                                        <div class="row"><div class="col-sm-12" data-validate="type"></div></div>
                                    </div>
                                </div>
                            </div>
                            <br>
                        </div>
                        <div class="form-actions text-right">
                            <button type="reset" class="btn btn-warning mr-1">
                                <i class="fa fa-times" style="font-size:11px;"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-check-square-o"></i> Save
                            </button>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
