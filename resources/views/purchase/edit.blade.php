@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection



@section('content')
    <section class="">
    <div id="supplier-invoice-app" data-id="{{$invoice}}" 
			data-productscount="{{$productsCount}}" 
			data-supplierscount="{{$supplierCount}}" 
			data-currency="{{env('CURRENCY_SYMBOL', '£')}}"></div>
    </section>
@endsection
