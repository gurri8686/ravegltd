@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection

@section('content')

<section class="">
       <div id="customer-invoice-app"
		data-id="{{$invoice}}"
		data-currency="{{env('CURRENCY_SYMBOL', '£')}}"
		data-show-suppliers="{{$showSuppliers}}"
		data-toggle-api="{{route('general_settings.save.save')}}"
		></div>
    </section>
@endsection
