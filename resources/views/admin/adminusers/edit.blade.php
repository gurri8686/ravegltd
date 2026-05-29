@extends('admin.layout')

@section('title', 'Edit Admin User')
@section('subtitle', $admin->email)

@section('content')
<div class="panel p-4" style="max-width:760px;">
    <form method="POST" action="{{ route('admin.adminusers.update', $admin->id) }}">
        @csrf
        @method('PUT')
        @include('admin.adminusers._form', ['admin' => $admin])
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn-newvendor"><i class="bi bi-check-lg"></i> Save Changes</button>
            <a href="{{ route('admin.adminusers.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
