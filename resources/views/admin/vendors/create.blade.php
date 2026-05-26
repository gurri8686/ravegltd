@extends('admin.layout')

@section('title', 'Create New Vendor')
@section('subtitle', 'Add a new vendor account to the platform')

@section('content')
<form method="POST" action="{{ route('admin.vendors.store') }}" enctype="multipart/form-data" id="vendorForm">
    @csrf
    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="panel p-4">
                @include('admin.vendors._form', ['vendor' => null])
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn-newvendor"><i class="bi bi-check-lg"></i> Create Vendor</button>
                    <a href="{{ route('admin.vendors.index') }}" class="btn btn-light">Cancel</a>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            @include('admin.vendors._summary', ['isEdit' => false, 'vendor' => null])
        </div>
    </div>
</form>
@endsection
