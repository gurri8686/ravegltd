@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>.content-header { display: none !important; }</style>
@endpush

@push('scripts')

<x-ajax-form-data
    formId='updateAdminForm'
    :route="route('admin.edit.update')"
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
        initialPreview:["{{isset($data->image) && !empty($data->image) ? config('filesystems.disks.public.url').'/'.$data->image : ''}}"],  
        showCaption: false, 
        dropZoneEnabled: false, 
        showUpload:false,
        maxFileCount: 1,
        allowedFileExtensions: ["jpg", "png", "gif"]
    });
});
</script>
@endpush
@section('content')
<section class="">
	<div class="users-list-filter px-1">
            <div class="row  mb-1">
                <div class="col-12 col-sm-6 p-0 col-lg-3">
				<h2>Update Info</h2>
				</div>
			</div>
		</div>
<div class="row match-height">
    <div class="col-md-12">
        <div class="card" style="height: 988.725px;">
            <!--<div class="card-header">
                <h4 class="card-title" id="basic-layout-form">Update User</h4>
                <a class="heading-elements-toggle"><i class="fa fa-ellipsis-v font-medium-3"></i></a>
                <div class="heading-elements">
                    <ul class="list-inline mb-0">
                        <li><a data-action="collapse"><i class="feather icon-minus"></i></a></li>
                        <li><a data-action="reload"><i class="feather icon-rotate-cw"></i></a></li>
                        <li><a data-action="expand"><i class="feather icon-maximize"></i></a></li>
                        <li><a data-action="close"><i class="feather icon-x"></i></a></li>
                    </ul>
                </div>
            </div>-->
            <div class="card-content collapse show">
                <div class="card-body">

                    <form class="form" id="updateAdminForm" name="updateAdminForm" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                        <div class="form-body">
                            <h4 class="form-section"><i class="feather icon-user"></i> User Information</h4>

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
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email<span class="text-danger">*</span></label>
                                        <input type="email" id="email" class="form-control" placeholder="Enter Email" name="email" disabled value="{{$data->email}}">
                                        <div class="row"><div class="col-sm-12" data-validate="email"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" id="city" class="form-control" placeholder="Enter City" name="city" value="{{$data->city}}">
                                        <div class="row"><div class="col-sm-12" data-validate="city"></div></div>
                                    </div>
                                </div>
                                <!-- <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="country">Country</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Country" name="country" value="{{$data->country}}">
                                        <div class="row"><div class="col-sm-12" data-validate="country"></div></div>
                                    </div>
                                </div> -->
                            </div>
                            <!-- <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="state">State</label>
                                        <input type="text" id="state" class="form-control" placeholder="Enter State" name="state" value="{{$data->state}}">
                                        <div class="row"><div class="col-sm-12" data-validate="state"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" id="city" class="form-control" placeholder="Enter City" name="city" value="{{$data->city}}">
                                        <div class="row"><div class="col-sm-12" data-validate="city"></div></div>
                                    </div>
                                </div>
                            </div> -->
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
                            <!-- <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                        <label for="role">Role<span class="text-danger">*</span></label>
                                        <select class="form-control" name="role" id="role">
                                            <option value="">--Select--</option>
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
                            </div> -->
							

                            <div class="row">
									<div class="col-md-6">
										<div class="form-group">
											<label for="username">Username(<i>Unique</i>)</label>
											<input type="text" id="username" class="form-control" placeholder="Enter Username" name="username" value="{{$data->username}}">
											<div class="row"><div class="col-sm-12" data-validate="username"></div></div>
										</div>
								</div>
							
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <!--<input type="file" name="image" id="image" class="file" accept="image/*">-->
                                        <label for="role">Upload Image</label>
										<input type="file" name="image" id="image" />
                                        <!--<div class="input-group my-1">
                                            <input type="text" class="form-control" disabled placeholder="Upload File" id="file">
                                            <div class="input-group-append">
                                                <button type="button" class="browse btn btn-primary">Browse...</button>
                                            </div>
                                        </div>-->
                                        <div class="row"><div class="col-sm-12" data-validate="image"></div></div>


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

                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;padding-top:20px;border-top:1px solid #eef2f7;margin-top:20px;">
                            <a href="{{ route('dashboard.view.index') }}" style="height:44px;padding:0 24px;border-radius:12px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:13.5px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;transition:all 0.15s;outline:none;" onmouseover="this.style.borderColor='#F27420';this.style.color='#F27420';this.style.background='#FFF8F3';" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
                                <i class="fa fa-times" style="font-size:11px;color:#000;margin-right:4px;"></i> Cancel
                            </a>
                            <button type="submit" style="height:44px;padding:0 28px;border-radius:12px;border:none;background:#F27420;color:#fff;font-size:13.5px;font-weight:700;box-shadow:0 2px 8px rgba(242,116,32,0.3);display:inline-flex;align-items:center;cursor:pointer;outline:none;transition:all 0.15s;" onmouseover="this.style.background='#e0600e';" onmouseout="this.style.background='#F27420';">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</section>
@endsection
