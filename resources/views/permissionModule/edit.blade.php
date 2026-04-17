@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin');
@endsection
@push('scripts')
<x-ajax-form-data
    formId='updatemoduleForm'
    :route="route('management.roles.modules.edit.update', $data->id)"
    method='POST'
/>
@endpush
@section('content')

<div class="row match-height">
    <div class="col-md-12">
        <div class="card" style="height: 988.725px;">
            <div class="card-header">
                <h4 class="card-title" id="basic-layout-form">Update Module</h4>
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

                    <form class="form" id="updatemoduleForm" name="updatemoduleForm">
                    @csrf
                    @method('PUT')
                        <div class="form-body">
                            <h4 class="form-section"><i class="feather icon-user"></i> Module Information</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="title">Title<span class="text-danger">*</span></label>
                                        <input type="text" id="title" class="form-control" placeholder="Module Name" name="title" value="{{$data->title}}">
                                        <div class="row"><div class="col-sm-12" data-validate="title"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="icon">Font Icon</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Font Awsome Icon" name="icon" value="{{$data->icon}}">
                                        <div class="row"><div class="col-sm-12" data-validate="icon"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                            <label for="status">Active</label><br>
                                             <input type="checkbox" name="is_active" class="switch form-control" id="is_active" data-switch-always @if($data->is_active==1) checked @endif />
                                    </div>
                                </div>
                            </div>
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
