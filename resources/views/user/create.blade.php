@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }
.pw-wrap{position:relative;display:block;}.pw-input{padding-right:42px!important;}.pw-eye{position:absolute;right:13px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;font-size:14px;transition:color 0.15s;user-select:none;}.pw-eye:hover{color:#f97316;}
</style>
@endpush

@push('scripts')
<x-ajax-form-data
    formId='saveUserForm'
    :route="route('management.users.create.store')"
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
        //initialPreview:["{{isset($data->invoice_logo) ? config('filesystems.disks.public.url').'/'.$data->invoice_logo : ''}}"],  
        showCaption: false, 
        dropZoneEnabled: false, 
        showUpload:false,
        maxFileCount: 1,
        allowedFileExtensions: ["jpg", "png", "gif"]
    });
});
</script>
<script>
function togglePw(fieldId,btn){var input=document.getElementById(fieldId);var icon=btn.querySelector('i');if(input.type==='password'){input.type='text';icon.className='fa fa-eye';btn.style.color='#f97316';}else{input.type='password';icon.className='fa fa-eye-slash';btn.style.color='';}}
</script>
@endpush

@section('content')
<section class="users-list-wrapper">
<div style="margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff;border-radius:16px;padding:18px 24px;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #f1f5f9;">
	<div style="display:flex;align-items:center;gap:14px;">
		<div style="width:48px;height:48px;border-radius:14px;background:#F27420;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 12px rgba(242,116,32,0.25);">
			<i class="fa fa-user-circle-o" style="color:#fff;font-size:20px;"></i>
		</div>
		<div>
			<h1 style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;margin:0;">Add User</h1>
			<p style="font-size:12.5px;color:#94a3b8;font-weight:500;margin:2px 0 0;">Create a new user account</p>
		</div>
	</div>
</div>
<div class="row match-height">
    <div class="col-md-12">
        <div class="card" style="height: 988.725px;">
            <!--<div class="card-header">
                <h4 class="card-title" id="basic-layout-form">Add New User</h4>
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

                    <form class="form" id="saveUserForm" name="saveUserForm" enctype="multipart/form-data">
                    @csrf
                        <div class="form-body">
                            <h4 class="form-section"><i class="feather icon-user"></i> User Information</h4>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name<span class="text-danger">*</span></label>
                                        <input type="text" id="first_name" class="form-control" placeholder="Enter First Name" name="first_name">
                                        <div class="row"><div class="col-sm-12" data-validate="first_name"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">Last Name<span class="text-danger">*</span></label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Last Name" name="last_name">
                                        <div class="row"><div class="col-sm-12" data-validate="last_name"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="email">Email<span class="text-danger">*</span></label>
                                        <input type="email" id="email" class="form-control" placeholder="Enter Email" name="email">
                                        <div class="row"><div class="col-sm-12" data-validate="email"></div></div>
                                    </div>
                                </div>
								<div class="col-md-3">
                                    <div class="form-group">
                                        <label for="email">Username<span class="text-danger">*(<i>Unique</i>)</span></label>
                                        <input type="username" id="username" class="form-control" placeholder="Enter Username" name="username" value="">
                                        <div class="row"><div class="col-sm-12" data-validate="username"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" id="city" class="form-control" placeholder="Enter City" name="city">
                                        <div class="row"><div class="col-sm-12" data-validate="city"></div></div>
                                    </div>
                                </div>
                                <!-- <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="country">Country</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Country" name="country">
                                        <div class="row"><div class="col-sm-12" data-validate="country"></div></div>
                                    </div>
                                </div> -->
                            </div>
                            <!-- <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="state">State</label>
                                        <input type="text" id="state" class="form-control" placeholder="Enter State" name="state">
                                        <div class="row"><div class="col-sm-12" data-validate="state"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" id="city" class="form-control" placeholder="Enter City" name="city">
                                        <div class="row"><div class="col-sm-12" data-validate="city"></div></div>
                                    </div>
                                </div>
                            </div> -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="zipcode">Zipcode</label>
                                        <input type="text" id="zipcode" class="form-control" placeholder="Enter Zipcode" name="zipcode">
                                        <div class="row"><div class="col-sm-12" data-validate="zipcode"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <input type="text" id="address" class="form-control" placeholder="Enter Address" name="address">
                                        <div class="row"><div class="col-sm-12" data-validate="address"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Password<span class="text-danger">*</span></label>
                                        <div class="pw-wrap">
                                            <input type="password" id="password" class="form-control pw-input" placeholder="Enter Password" name="password">
                                            <span class="pw-eye" onclick="togglePw('password',this)"><i class="fa fa-eye-slash"></i></span>
                                        </div>
                                        <div class="row"><div class="col-sm-12" data-validate="password"></div></div>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6">
                                    <div class="form-group">
                                        <label for="role">Role<span class="text-danger">*</span></label>
                                        <select class="form-control select2" name="role" id="role">
                                            @foreach($roleDetails as $roleDetail)
                                                <option value="{{$roleDetail->id}}" @if($loop->first) selected @endif>{{$roleDetail->name}}</option>
                                            @endforeach
                                        </select>
                                        <div class="row"><div class="col-sm-12" data-validate="role"></div></div>
                                    </div>

                                </div>
                            </div>
                            <div class="row">

                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <!--<input type="file" name="image" id="image" class="file" accept="image/*">-->
                                        <label for="role">Upload Image</label>
										<input type="file" name="image" id="image">
                                        <!--<div class="input-group my-1">
                                            <input type="text" class="form-control" disabled placeholder="Upload File" id="file">
                                            <div class="input-group-append">
                                                <button type="button" class="browse btn btn-primary">Browse...</button>
                                            </div>
                                        </div>
                                        <div class="row"><div class="col-sm-12" data-validate="image"></div></div>

                                        <a class="mr-6 my-2" href="#">
                                            <img src="{{asset('img/1024px-User_icon_2.svg.png')}}" id="preview" class="users-avatar-shadow rounded-circle" height="90" width="90">
                                        </a>-->
                                    </div>
                                </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="status" class="">Active</label><br>
                                            <input type="checkbox" name="is_active" class="switch mr-6 my-2" id="is_active" data-switch-always checked />
                                        </div>
                                    </div>
                            </div>


                        </div>

                        <div style="display:flex;align-items:center;justify-content:flex-end;gap:12px;padding-top:20px;border-top:1px solid #eef2f7;margin-top:20px;">
                            <a href="{{ route('management.settings.index') }}?tab=users" style="height:44px;padding:0 24px;border-radius:12px;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;font-size:13.5px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all 0.15s;outline:none;" onmouseover="this.style.borderColor='#F27420';this.style.color='#F27420';this.style.background='#FFF8F3';" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b';this.style.background='#fff';">
                                <i class="fa fa-times" style="font-size:11px;color:#000;margin-right:4px;"></i> Cancel
                            </a>
                            <button type="submit" class="btn" style="height:44px;padding:0 28px;border-radius:12px;border:none;background:#F27420;color:#fff;font-size:13.5px;font-weight:700;box-shadow:0 2px 8px rgba(242,116,32,0.3);display:inline-flex;align-items:center;gap:6px;outline:none;transition:all 0.15s;" onmouseover="this.style.background='#e0600e';" onmouseout="this.style.background='#F27420';">
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
