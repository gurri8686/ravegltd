@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection



@section('content')
<section class="users-list-wrapper">
	<div class="users-list-filter px-1">
		<div class="row  mb-1">
			<div class="col-md-12 col-sm-12 col-lg-9 p-0">
			  <h2>General Settings</h2>
			</div>
		</div>
    </div>
	<div id="general-settings-app"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
	></div>
</section>
@endsection
