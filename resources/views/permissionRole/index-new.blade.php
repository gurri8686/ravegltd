@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection



@section('content')
<section class="users-list-wrapper">
	<div id="roles-permission-index-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-list-api="{{route('management.roles.permission.view.list')}}"
	></div>
</section>
@endsection
