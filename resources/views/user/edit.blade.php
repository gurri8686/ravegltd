@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection
@push('scripts')

<x-ajax-form-data
    formId='updateUserForm'
    :route="route('management.users.edit.update', $data->id)"
    method='POST'
/>
<x-file-uploader.krajee.includes />
<script>
$(document).ready(function() {
    $("#image").fileinput({
        maxFileSize: 2000, 
        showCaption: false,
        showUploadedThumbs: false,
        showClose: false, 
        initialPreviewAsData: true,
        initialPreviewFileType: 'image',
        initialPreview:["{{isset($data->image) ? config('filesystems.disks.public.url').'/'.$data->image : ''}}"],  
        showCaption: false, 
        dropZoneEnabled: false, 
        showUpload:false,
        maxFileCount: 1,
        allowedFileExtensions: ["jpg", "png", "gif"]
    });
});
</script>
@endpush
@push('stylesheets')
<style>
.content-header { display: none !important; }
.edit-form-card {
	border-radius: 16px;
	border: 1px solid #f0f0f0;
	box-shadow: 0 2px 12px rgba(0,0,0,0.06);
	background: #fff;
	padding: 28px 32px;
}
.edit-form-card .form-control {
	border: 1.5px solid #e5e7eb;
	border-radius: 10px;
	padding: 10px 14px;
	font-size: 13px;
	transition: border-color 0.2s;
}
.edit-form-card .form-control:focus {
	border-color: #F27420;
	box-shadow: 0 0 0 0.2rem rgba(242,116,32,0.15);
}
.edit-form-card select.form-control {
	border: 1.5px solid #e5e7eb;
	border-radius: 10px;
	padding: 10px 14px;
	font-size: 13px;
	appearance: none;
	-webkit-appearance: none;
	-moz-appearance: none;
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
	background-repeat: no-repeat;
	background-position: right 14px center;
	background-size: 14px;
	padding-right: 36px;
	cursor: pointer;
}
.edit-form-card select.form-control:focus {
	border-color: #F27420;
	box-shadow: 0 0 0 0.2rem rgba(242,116,32,0.15);
}
.edit-form-card select.form-control option {
	padding: 10px;
	font-size: 13px;
}
.edit-form-card label {
	font-size: 11px;
	font-weight: 700;
	color: #9ca3af;
	letter-spacing: 0.6px;
	text-transform: uppercase;
	margin-bottom: 6px;
}
.edit-form-card label .text-danger {
	color: #F27420 !important;
	font-size: 11px;
}
.btn-file-remove, .fileinput-remove, .kv-file-remove, .file-input .btn-default, .file-input .btn.btn-secondary {
	border: 1.5px solid #F27420 !important;
	color: #F27420 !important;
	background: #fff !important;
}
.btn-file-remove:hover, .fileinput-remove:hover, .kv-file-remove:hover, .file-input .btn-default:hover, .file-input .btn.btn-secondary:hover,
.btn-file-remove:focus, .fileinput-remove:focus, .kv-file-remove:focus, .file-input .btn-default:focus, .file-input .btn.btn-secondary:focus {
	background: #fff !important;
	color: #F27420 !important;
	box-shadow: none !important;
}
.edit-form-card .section-title {
	font-size: 14px;
	font-weight: 700;
	color: #374151;
	padding-bottom: 12px;
	margin-bottom: 20px;
	border-bottom: 1.5px solid #fed7aa;
	display: flex;
	align-items: center;
	gap: 8px;
}
.edit-form-card .section-title i {
	color: #F27420;
}
.edit-form-card .btn-save {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	height: 44px;
	background: #F27420;
	border: none;
	color: #fff;
	border-radius: 12px;
	padding: 0 28px;
	font-size: 13.5px;
	font-weight: 700;
	cursor: pointer;
	box-shadow: 0 2px 8px rgba(242,116,32,0.3);
	transition: all 0.15s;
	outline: none;
}
.edit-form-card .btn-save:hover {
	background: #e0600e;
}
.edit-form-card .btn-save:focus { outline: none; }
.edit-form-card .btn-cancel {
	display: inline-flex;
	align-items: center;
	gap: 6px;
	height: 44px;
	background: #fff;
	border: 1.5px solid #e2e8f0;
	color: #64748b;
	border-radius: 12px;
	padding: 0 24px;
	font-size: 13.5px;
	font-weight: 600;
	cursor: pointer;
	text-decoration: none;
	transition: all 0.15s;
	outline: none;
}
.edit-form-card .btn-cancel:hover {
	border-color: #F27420;
	color: #F27420;
	background: #FFF8F3;
}
.edit-form-card .btn-cancel:focus { outline: none; }
</style>
@endPush

@section('content')

<section class="users-list-wrapper">
<div style="margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px;padding:18px 24px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;">
	<div style="display:flex;align-items:center;gap:14px;">
		<div style="width:48px;height:48px;border-radius:14px;background:#F27420;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(242,116,32,0.25);">
			<i class="fa fa-user-circle-o" style="color:#fff;font-size:20px;"></i>
		</div>
		<div>
			<h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Edit User</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Update user details</p>
		</div>
	</div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="edit-form-card">
                    <form class="form" id="updateUserForm" name="updateUserForm" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                        <div class="form-body">
                            <div class="section-title"><i class="feather icon-user"></i> User Information — #{{$data->id}}</div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name<span class="text-danger">*</span></label>
                                        <input type="text" id="first_name" class="form-control" placeholder="Enter First Name" value="{{$data->first_name}}" name="first_name">
                                        <div class="row"><div class="col-sm-12" data-validate="first_name"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">Last Name<span class="text-danger">*</span></label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Last Name" name="last_name" value="{{$data->last_name}}">
                                        <div class="row"><div class="col-sm-12" data-validate="last_name"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="email">Email<span class="text-danger">*</span></label>
                                        <input type="email" id="email" class="form-control" placeholder="Enter Email" name="email" value="{{$data->email}}">
                                        <div class="row"><div class="col-sm-12" data-validate="email"></div></div>
                                    </div>
                                </div>
								<div class="col-md-3">
                                    <div class="form-group">
                                        <label for="email">Username<span class="text-danger">*(<i>Unique</i>)</span></label>
                                        <input type="username" id="username" class="form-control" placeholder="Enter Username" name="username" value="{{$data->username}}">
                                        <div class="row"><div class="col-sm-12" data-validate="username"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" id="city" class="form-control" placeholder="Enter City" name="city" value="{{$data->city}}">
                                        <div class="row"><div class="col-sm-12" data-validate="city"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="zipcode">Zipcode</label>
                                        <input type="text" id="zipcode" class="form-control" placeholder="Enter Zipcode" name="zipcode" value="{{$data->zipcode}}">
                                        <div class="row"><div class="col-sm-12" data-validate="zipcode"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <input type="text" id="address" class="form-control" placeholder="Enter Address" name="address" value="{{$data->address}}">
                                        <div class="row"><div class="col-sm-12" data-validate="address"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="role">Role<span class="text-danger">*</span></label>
                                        <select class="form-control select2" name="role" id="role">
                                            <?php
                                                if($assignRole==''){
                                                    $assignRoleToUser='';
                                                 }
                                                 else
                                                 {
                                                    $assignRoleToUser=$assignRole->role_id;
                                                 }
                                            ?>
                                            @foreach($roleDetails as $roleDetail)
                                                <option value="{{$roleDetail->id}}" @if($roleDetail->id == $assignRoleToUser) selected @endif>{{$roleDetail->name}}</option>
                                            @endforeach
                                        </select>
                                        <div class="row"><div class="col-sm-12" data-validate="role"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="status">Active</label><br>
                                        <input type="checkbox" name="is_active" class="switch form-control" id="is_active" data-switch-always @if($data->is_active==1) checked @endif />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="state">Password</label>
                                        <input type="text" id="state" class="form-control" placeholder="Enter Password" name="password" >
                                        <div class="row"><div class="col-sm-12" data-validate="password"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">Confirm Password</label>
                                        <input type="text" id="confirm_password" class="form-control" placeholder="Confirm Password" name="password_confirmation" >
                                        <div class="row"><div class="col-sm-12" data-validate="password_confirmation"></div></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="role">Upload Image</label>
										<input type="file" name="image" id="image">
                                    </div>
                                </div>
								<?php
								/*
                                @if($data->image!='')
                                    <input type="hidden" name="imageP" id="imageP" value="{{$data->image}}">
                                    <a class="mr-4 my-1" href="#">
                                        <img src="{{asset('img/userImage/')}}/{{$data->image}}" id="preview" class="users-avatar-shadow rounded-circle" height="90" width="90">
                                    </a>
                                @else
                                    <a class="mr-4 my-1" href="#">
                                        <img src="{{asset('img/1024px-User_icon_2.svg.png')}}" id="preview" class="users-avatar-shadow rounded-circle" height="90" width="90">
                                    </a>
                                @endif
								*/
								?>
                            </div>

                        </div>

                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;margin-top:24px;padding-top:20px;border-top:1px solid #f0f0f0;">
                            <a href="{{ route('management.settings.index') }}?tab=users" class="btn-cancel">
                                <i class="fa fa-times" style="font-size:11px;color:#000;margin-right:4px;"></i> Cancel
                            </a>
                            <button type="submit" class="btn-save">
                                Save
                            </button>
                        </div>
                    </form>
        </div>
    </div>
</div>
</section>
@endsection
