@extends('admin.layout')

@section('title', 'Add Domain')
@section('subtitle', 'Create a new subdomain / custom domain')

@section('content')
<div class="panel p-4" style="max-width:760px;">
    <form method="POST" action="{{ route('admin.domains.store') }}">
        @csrf
        @include('admin.domains._form', ['site' => null])
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn-newvendor"><i class="bi bi-check-lg"></i> Create Domain</button>
            <a href="{{ route('admin.domains.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
