@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin');
@endsection



@section('content')
    <section class="">
    <h1>Invoice #{{$invoice}}</h1>
        <div id="customer-invoice-app" data-id="{{$invoice}}"></div>
    </section>
@endsection
