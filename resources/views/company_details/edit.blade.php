@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>.content-header { display: none !important; }</style>
@endpush

@push('scripts')

<x-ajax-form-data
    formId='updateCompanyDetailsForm'
    :route="route('company_details.edit.update')"
    method='POST'
/>

<x-file-uploader.krajee.includes />

<script>
$(document).ready(function() {
    $("#invoice_logo").fileinput({
        maxFileSize: 2000, 
        showCaption: false,
        showUploadedThumbs: false,
        showClose: false, 
        initialPreviewAsData: true,
        initialPreviewFileType: 'image',
        initialPreview:["{{isset($data->invoice_logo) ? config('filesystems.disks.public.url').'/'.$data->invoice_logo : ''}}"],  
        showCaption: false, 
        dropZoneEnabled: false, 
        showUpload:false,
        maxFileCount: 1,
        allowedFileExtensions: ["jpg", "png", "gif"]
    });
    $("#website_logo").fileinput({
        maxFileSize: 2000, 
        showCaption: false,
        showUploadedThumbs: false,
        showClose: false, 
        initialPreviewAsData: true,
        initialPreviewFileType: 'image',
        initialPreview:["{{isset($data->website_logo) ? config('filesystems.disks.public.url').'/'.$data->website_logo : ''}}"], 
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
				<h2>Update Company Details</h2>
				</div>
			</div>
		</div>
<div class="row match-height">
    <div class="col-md-12">
        <div class="card" style="height: 988.725px;">
            <!--<div class="card-header">
                <h4 class="card-title" id="basic-layout-form">Update Company Details</h4>
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

                    <form class="form" id="updateCompanyDetailsForm" name="updateCompanyDetailsForm" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                        <div class="form-body">
                            <h4 class="form-section"><i class="feather icon-user"></i> Basic Information</h4>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="company_name">Company Name<span class="text-danger">*</span></label>
                                        <input type="text" id="company_name" class="form-control" placeholder="Enter Company Name" name="company_name" value="@isset($data->company_name){{$data->company_name}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="company_name"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="website">Website</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Website" name="website" value="@isset($data->website){{$data->website}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="website"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email<span class="text-danger">*</span></label>
                                        <input type="email" id="email" class="form-control" placeholder="Enter Email" name="email" value="@isset($data->email){{$data->email}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="email"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mobile">Mobile<span class="text-danger">*</span></label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Mobile" name="mobile" maxlength=16  value="@isset($data->mobile){{$data->mobile}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="mobile"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="telephone">Telephone</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Telephone" name="telephone" maxlength=16 pattern="[a-zA-Z0-9\s]+" value="@isset($data->telephone){{$data->telephone}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="telephone"></div></div>
                                    </div>
                                </div>
                                <!-- <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fax">Fax</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Fax" name="fax" maxlength=11 pattern="[0-9]+" value="@isset($data->fax){{$data->fax}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="fax"></div></div>
                                    </div>
                                </div> -->
                            </div>


                            <br>
                            <h4 class="form-section"><i class="feather icon-home"></i> Address</h4>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="address1">Address Line 1</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Address Line 1" name="address1" value="@isset($data->address1){{$data->address1}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="address1"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="address2">Address Line 2</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Address Line 2" name="address2" value="@isset($data->address2){{$data->address2}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="address2"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="country">Country</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Country" name="country" value="@isset($data->country){{$data->country}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="country"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="state">State</label>
                                        <input type="text" id="state" class="form-control" placeholder="Enter State" name="state" value="@isset($data->state){{$data->state}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="state"></div></div>
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" id="city" class="form-control" placeholder="Enter City" name="city" value="@isset($data->city){{$data->city}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="city"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="zipcode">Zipcode</label>
                                        <input type="text" id="zipcode" class="form-control" placeholder="Enter Zipcode" name="zipcode" maxlength=10  value="@isset($data->zipcode){{$data->zipcode}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="zipcode"></div></div>
                                    </div>
                                </div>
                            </div>


                            <br>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="vat_number">Web Site Logo</label>

                                        <br>
                                        <input type="file" name="website_logo" id="website_logo">
                                        <br>
                                        <br>
                                        @isset($data->invoice_logo)
                                        <!--<img class="brand-logo" style="float: left;" alt="stack admin logo" src="{{config('filesystems.disks.public.url')}}/{{$data->website_logo}}" weight="40px" height='80px'/>-->

                                        @endisset
                                        <!-- <input type="text" id="vat_number" class="form-control" placeholder="Enter Vat Number" name="vat_number" value="@isset($data->vat_number){{$data->vat_number}}@endisset"> -->
                                        <div class="row"><div class="col-sm-12" data-validate="vat_number"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="vat_number">Invoice Logo</label>
                                        <br>
                                        <!-- <input type="text" id="vat_number" class="form-control" placeholder="Enter Vat Number" name="vat_number" value="@isset($data->vat_number){{$data->vat_number}}@endisset"> -->
                                        <input type="file" name="invoice_logo" id="invoice_logo">
                                        <br>
                                        <br>
                                        @isset($data->invoice_logo)
                                        <!--<img class="invoice_logo" style="float: left;" alt="stack admin logo" src="{{config('filesystems.disks.public.url')}}/{{$data->invoice_logo}}" weight="40px" height='80px'/>-->

                                        @endisset
                                        <div class="row"><div class="col-sm-12" data-validate="vat_number"></div></div>
                                    </div>
                                </div>
                            </div>

                            <!-- <h4 class="form-section"><i class="feather icon-briefcase"></i> Accounting</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="vat_number">VAT number</label>
                                        <input type="text" id="vat_number" class="form-control" placeholder="Enter Vat Number" name="vat_number" value="@isset($data->vat_number){{$data->vat_number}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="vat_number"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="comp_reg_no">Comp. Reg. No.</label>
                                        <input type="text" id="comp_reg_no" class="form-control" placeholder="Enter Comp. Reg. No." name="comp_reg_no" value="@isset($data->comp_reg_no){{$data->comp_reg_no}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="comp_reg_no"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bank_name">Bank Name</label>
                                        <input type="text" id="bank_name" class="form-control" placeholder="Enter Bank Name" name="bank_name" value="@isset($data->bank_name){{$data->bank_name}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="bank_name"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account_no">Account Number</label>
                                        <input type="text" id="account_no" class="form-control" placeholder="Account Number" name="account_no" value="@isset($data->account_no){{$data->account_no}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="account_no"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ifsc_code">IFSC</label>
                                        <input type="text" id="ifsc_code" class="form-control" placeholder="Enter IFSC" name="ifsc_code" value="@isset($data->ifsc_code){{$data->ifsc_code}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="ifsc_code"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="eirl_no">EIRL Number</label>
                                        <input type="text" id="eirl_no" class="form-control" placeholder="Enter EIRL Number" name="eirl_no" value="@isset($data->eirl_no){{$data->eirl_no}}@endisset">
                                        <div class="row"><div class="col-sm-12" data-validate="eirl_no"></div></div>
                                    </div>
                                </div>
                            </div> -->

                            <br>
                            <h4 class="form-section"><i class="feather icon-file-text"></i> Others</h4>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="remarks">Remarks/Notes</label>
                                        <textarea id="remarks" class="form-control" placeholder="Enter Remarks/Notes" name="remarks">@isset($data->remarks){{$data->remarks}}@endisset</textarea>
                                        <div class="row"><div class="col-sm-12" data-validate="remarks"></div></div>
                                    </div>
                                </div>
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
