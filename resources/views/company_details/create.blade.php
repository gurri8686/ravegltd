@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin');
@endsection

@push('scripts')
<x-ajax-form-data
    formId='saveCompanyDetailForm'
    :route="route('company_details.create.store')"
    method='POST'
/>
@endpush

@section('content')
<div class="row match-height">
    <div class="col-md-12">
        <div class="card" style="height: 988.725px;">
            <div class="card-header">
                <h4 class="card-title" id="basic-layout-form">Add Company Details</h4>
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

                    <form class="form" id="saveCompanyDetailForm" name="saveCompanyDetailForm">
                    @csrf
                        <div class="form-body">
                            <h4 class="form-section"><i class="feather icon-user"></i> Basic Information</h4>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="company_name">Company Name<span class="text-danger">*</span></label>
                                        <input type="text" id="company_name" class="form-control" placeholder="Enter Company Name" name="company_name">
                                        <div class="row"><div class="col-sm-12" data-validate="company_name"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="website">Website</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Website" name="website">
                                        <div class="row"><div class="col-sm-12" data-validate="website"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email<span class="text-danger">*</span></label>
                                        <input type="email" id="email" class="form-control" placeholder="Enter Email" name="email">
                                        <div class="row"><div class="col-sm-12" data-validate="email"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mobile">Mobile<span class="text-danger">*</span></label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Mobile" name="mobile" maxlength=11 pattern="[0-9]+">
                                        <div class="row"><div class="col-sm-12" data-validate="mobile"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="telephone">Telephone</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Telephone" name="telephone" maxlength=11 pattern="[0-9]+">
                                        <div class="row"><div class="col-sm-12" data-validate="telephone"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fax">Fax</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Fax" name="fax" maxlength=11 pattern="[0-9]+">
                                        <div class="row"><div class="col-sm-12" data-validate="fax"></div></div>
                                    </div>
                                </div>
                            </div>


                            <br>
                            <h4 class="form-section"><i class="feather icon-home"></i> Address</h4>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="address1">Address Line 1</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Address Line 1" name="address1">
                                        <div class="row"><div class="col-sm-12" data-validate="address1"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="address2">Address Line 2</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Address Line 2" name="address2">
                                        <div class="row"><div class="col-sm-12" data-validate="address2"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="country">Country</label>
                                        <input type="text" id="icon" class="form-control" placeholder="Enter Country" name="country">
                                        <div class="row"><div class="col-sm-12" data-validate="country"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="state">State</label>
                                        <input type="text" id="state" class="form-control" placeholder="Enter State" name="state">
                                        <div class="row"><div class="col-sm-12" data-validate="state"></div></div>
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="city">City</label>
                                        <input type="text" id="city" class="form-control" placeholder="Enter City" name="city">
                                        <div class="row"><div class="col-sm-12" data-validate="city"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="zipcode">Zipcode</label>
                                        <input type="text" id="zipcode" class="form-control" placeholder="Enter Zipcode" name="zipcode" maxlength=6 pattern="[0-9]+">
                                        <div class="row"><div class="col-sm-12" data-validate="zipcode"></div></div>
                                    </div>
                                </div>
                            </div>


                            <br>
                            <h4 class="form-section"><i class="feather icon-briefcase"></i> Accounting</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="vat_number">VAT number</label>
                                        <input type="text" id="vat_number" class="form-control" placeholder="Enter Vat Number" name="vat_number">
                                        <div class="row"><div class="col-sm-12" data-validate="vat_number"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="comp_reg_no">Comp. Reg. No.</label>
                                        <input type="text" id="comp_reg_no" class="form-control" placeholder="Enter Comp. Reg. No." name="comp_reg_no">
                                        <div class="row"><div class="col-sm-12" data-validate="comp_reg_no"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="bank_name">Bank Name</label>
                                        <input type="text" id="bank_name" class="form-control" placeholder="Enter Bank Name" name="bank_name">
                                        <div class="row"><div class="col-sm-12" data-validate="bank_name"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account_no">Account Number</label>
                                        <input type="text" id="account_no" class="form-control" placeholder="Account Number" name="account_no">
                                        <div class="row"><div class="col-sm-12" data-validate="account_no"></div></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="ifsc_code">IFSC</label>
                                        <input type="text" id="ifsc_code" class="form-control" placeholder="Enter IFSC" name="ifsc_code">
                                        <div class="row"><div class="col-sm-12" data-validate="ifsc_code"></div></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="eirl_no">EIRL Number</label>
                                        <input type="text" id="eirl_no" class="form-control" placeholder="Enter EIRL Number" name="eirl_no">
                                        <div class="row"><div class="col-sm-12" data-validate="eirl_no"></div></div>
                                    </div>
                                </div>
                            </div>

                            <br>
                            <h4 class="form-section"><i class="feather icon-file-text"></i> Others</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="remarks">Remarks/Notes</label>
                                        <textarea id="remarks" class="form-control" placeholder="Enter Remarks/Notes" name="remarks"></textarea>
                                        <div class="row"><div class="col-sm-12" data-validate="remarks"></div></div>
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
