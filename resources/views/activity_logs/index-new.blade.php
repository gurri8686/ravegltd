@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection



@section('content')
<section class="users-list-wrapper">
	<div class="row mb-1">
		<div class="col-md-12 col-sm-12 col-lg-10 p-0 px-1">
		  <h2>Activity Logs</h2>
		</div>
	</div>
	<div id="activity-log-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-list-api="{{route('activity_logs.users.view.list')}}"
		data-list-user-api="{{route('activity_logs.users.view.users')}}"
		data-list-events-api="{{route('activity_logs.users.view.events')}}"
	></div>
</section>
@endsection
