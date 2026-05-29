@extends('admin.layout')

@section('title', 'Add Plan')
@section('subtitle', 'Create a subscription plan tier')

@section('content')
<div class="panel p-4" style="max-width:880px;">
    <form method="POST" action="{{ route('admin.plans.store') }}">
        @csrf
        @include('admin.plans._form', ['plan' => null])
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn-newvendor"><i class="bi bi-check-lg"></i> Create Plan</button>
            <a href="{{ route('admin.plans.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
