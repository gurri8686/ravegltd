@extends('admin.layout')

@section('title', 'Add Admin User')
@section('subtitle', 'Create a platform admin account')

@section('content')
<div class="panel p-4" style="max-width:760px;">
    <form method="POST" action="{{ route('admin.adminusers.store') }}">
        @csrf
        @include('admin.adminusers._form', ['admin' => null])
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn-newvendor"><i class="bi bi-check-lg"></i> Create Admin</button>
            <a href="{{ route('admin.adminusers.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
