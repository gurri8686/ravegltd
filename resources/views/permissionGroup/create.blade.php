@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin');
@endsection

@push('scripts')
<x-ajax-form-data
    formId='saveGroupForm'
    :route="route('management.roles.groups.create.store')"
    method='POST'
/>
@endpush

@section('content')
<div class="row match-height">
    <div class="col-md-12">
        <div class="card" style="height: 988.725px;">
            <div class="card-header">
                <h4 class="card-title" id="basic-layout-form">Add New Group</h4>
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

                    <form class="form" id="saveGroupForm" name="saveGroupForm">
                    @csrf
                        <div class="form-body">
                            <h4 class="form-section"><i class="feather icon-user"></i> Group Information</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="title">Title<span class="text-danger">*</span></label>
                                        <input type="text" id="title" class="form-control" placeholder="Group Name" name="title">
                                        <div class="row"><div class="col-sm-12" data-validate="title"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="icon">Font Icon</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Font Awsome Icon" name="icon">
                                        <div class="row"><div class="col-sm-12" data-validate="icon"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="status">Permission Module<span class="text-danger">*</span></label>
                                        <select class="form-control" name="permission_module_id" id="permission_module_id">
                                            <option value="">--Select--</option>
                                            @foreach($data as $row)
                                            <option value="{{$row->id}}">{{$row->title}}</option>
                                            @endforeach
                                        </select>
                                        <div class="row"><div class="col-sm-12" data-validate="permission_module_id"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                <div class="form-group">
                                            <label for="status">Active</label><br>
                                             <input type="checkbox" name="is_active" class="switch form-control" id="is_active" data-switch-always checked/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="status"><b>Check Permission Route</b></label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="custom-check"><b>Check All</b>
                                        <input type="checkbox" class="parentValue" onclick="selectall(this,'Value')">
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                            </div>
                        <?php

                            $index = 1; $html='';
                       foreach($routeDate as $row){
                         if($index%2==1){
                                $html .= '<div class="row ">
                                            <div class="col-md-6 col-sm-6">
                                                <label class="custom-check">'.$row->name.'
                                                    <input type="checkbox" class="childValue" name="routePermission[]" value="'.$row->id.'">
                                                    <span class="checkmark"></span>
                                                </label>
                                            </div>';
                                        }else{
                                $html .= '<div class="col-md-6 col-sm-6">
                                                <label class="custom-check">'.$row->name.'
                                                    <input type="checkbox" class="childValue" name="routePermission[]" value="'.$row->id.'">
                                                    <span class="checkmark"></span>
                                                </label>
                                        </div>
                                </div>';

                            }
                            $index++;
                        } ?>
                        <?php echo $html; ?>
                        </div><br>
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
