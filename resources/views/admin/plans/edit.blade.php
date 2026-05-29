@extends('admin.layout')

@section('title', 'Edit Plan')
@section('subtitle', $plan->name)

@section('content')
<div class="panel p-4" style="max-width:880px;">
    <form method="POST" action="{{ route('admin.plans.update', $plan->id) }}">
        @csrf
        @method('PUT')
        @include('admin.plans._form', ['plan' => $plan])
        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn-newvendor"><i class="bi bi-check-lg"></i> Save Changes</button>
            <a href="{{ route('admin.plans.index') }}" class="btn btn-light">Cancel</a>
        </div>
    </form>
</div>
@endsection
