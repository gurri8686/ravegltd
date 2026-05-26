@extends('admin.layout')

@section('title', 'Edit Domain')
@section('subtitle', $site->subdomain)

@section('content')
<div class="panel p-4" style="max-width:760px;">
    <form method="POST" action="{{ route('admin.domains.update', $site->id) }}">
        @csrf
        @method('PUT')
        @include('admin.domains._form', ['site' => $site])
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn-newvendor"><i class="bi bi-check-lg"></i> Save Changes</button>
            <a href="{{ route('admin.domains.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
