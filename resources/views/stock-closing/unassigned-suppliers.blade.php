@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@push('stylesheets')
<style>
.content-header { display: none !important; }
</style>
@endpush

@section('content')
<section class="users-list-wrapper">
    <div id="unassigned-suppliers-app"
        data-list-api="{{route('stock_closing.view.unassigned-suppliers.list')}}"
        data-assign-api="{{route('stock_closing.view.unassigned-suppliers.assign')}}"
        data-back-url="{{route('stock_check.view.index')}}"
        data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
    ></div>
</section>
@endsection
