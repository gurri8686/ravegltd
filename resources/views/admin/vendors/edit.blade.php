@extends('admin.layout')

@section('title', 'Edit Vendor')
@section('subtitle', trim($vendor->first_name . ' ' . $vendor->last_name) ?: $vendor->email)

@section('content')
<form method="POST" action="{{ route('admin.vendors.update', $vendor->id) }}" enctype="multipart/form-data" id="vendorForm">
    @csrf
    @method('PUT')
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="panel p-4">
                @include('admin.vendors._form', ['vendor' => $vendor])
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn-newvendor"><i class="bi bi-check-lg"></i> Save Changes</button>
                    <a href="{{ route('admin.vendors.show', $vendor->id) }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            @include('admin.vendors._summary', ['isEdit' => true, 'vendor' => $vendor])
        </div>
    </div>
</form>
@endsection
