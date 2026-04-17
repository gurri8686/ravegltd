@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin');
@endsection

@section('content')
<center>
        <h1>You are not allowed to work in current time slots.</h1>
		<h5>Please contact administrator for "Work Time"</h5/>
    </center>
@endsection

