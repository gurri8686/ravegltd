@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin');
@endsection

@push('scripts')
<x-ajax-table-data
    tableId='daily-book-purchases'
    :route="route('daily_report.daily_book_purchase.ajax.purchases-list')"
    method='POST'
/>
@endpush

@section('content')
<section class="users-list-wrapper">
       <div class="users-list-filter px-1">
            <div class="row  mb-1">
                <div class="col-12 col-sm-6 col-lg-3"> </div>
                <div class="col-12 col-sm-6 col-lg-3"></div>
                <div class="col-12 col-sm-7 col-lg-3"></div>
                <div class="col-12 col-sm-8 col-lg-3 d-flex align-items-center">
                </div>
            </div>
    </div>

    <div class="users-list-table">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <!-- datatable start -->
                    <div class="table-responsive">
                        <table border="0" cellspacing="5" cellpadding="5">
                            <tbody>
                                <tr>
                                    <form id="search">
                                        <td>From:</td>
                                        <td><input type="text" id="min" name="min"></td>
                                        <td>To:</td>
                                        <td><input type="text" id="max" name="max"></td>
                                        <td class="text-center"><input type="submit" class="btn btn-primary filter-button" value="Search"></td>
                                        <td class="text-center"><input type="button" class="btn btn-warning filter-button" value="Clear" onClick="clearInput()"></td>
                                    </form>
                                </tr>
                            </tbody>
                        </table>
                        <table id="daily-book-purchases" class="table table-hover">
                        </table>
                    </div>
                    <!-- datatable ends -->
                </div>
            </div>
        </div>
    </div>
    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>
</section>
@endsection
