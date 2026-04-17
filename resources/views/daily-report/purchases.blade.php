@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
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
				<h2>Daily Book Purchase</h2>
                <div class="col-12 col-sm-6 col-lg-3"> </div>
                <div class="col-12 col-sm-6 col-lg-3"></div>
                <div class="col-12 col-sm-7 col-lg-3"></div>
                <div class="col-12 col-sm-8 col-lg-3 d-flex align-items-center">
                </div>
            </div>
    </div>
	
	<?php $cdate = date('Y-m-d'); ?>
    <div class="users-list-table">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
					<form id="search">
					<div class="row">
					
						<div class="col-lg-2 col-md-4">
							<div class="input-group input-group-sm mb-2">
								<div class="input-group-prepend">
								  <div class="input-group-text">From</div>
								</div>
								<input type="text" class="form-control form-control-sm border-light" id="min" name="min" value="{{$cdate}}">
							 </div>
						</div>
						<div class="col-lg-2 col-md-4">
							<div class="input-group mb-2 input-group-sm">
								<div class="input-group-prepend">
								  <div class="input-group-text">To</div>
								</div>
								<input type="text" class="form-control form-control-sm border-light" id="max" name="max" value="{{$cdate}}">
							 </div>
						</div>
						<div class="col-lg-2 col-md-4">
							<input type="submit" class="btn btn-primary filter-button" value="Search">
							<input type="button" class="btn btn-warning filter-button" value="Reset" onClick="clearInput()">
						</div>
					
					</div>
					</form>
                    <!-- datatable start -->
                    <div class="table-responsive">
                        <!--<table border="0" cellspacing="5" cellpadding="5">
                            <tbody>
                                <tr>
                                    <form id="search">
                                        <td>From:</td>
                                        <td><input type="text" class="form-control" id="min" name="min" value="{{$cdate}}"></td>
                                        <td>To:</td>
                                        <td><input type="text" class="form-control" id="max" name="max" value="{{$cdate}}"></td>
                                        <td class="text-center"><input type="submit" class="btn btn-primary filter-button" value="Search"></td>
                                        <td class="text-center"><input type="button" class="btn btn-warning filter-button" value="Clear" onClick="clearInput()"></td>
                                    </form>
                                </tr>
                            </tbody>
                        </table>-->
                        <table id="daily-book-purchases" class="display c-table table-hover">
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


@push('scripts')
   <script>
    function deleteinvoice(id){
        event.preventDefault();
        var del=confirm("Are you sure you want to delete this Purchase Invoice?\n");
          if (del==true){
            window.location.href = '/data_entry/purchase_entry/invoice/delete/'+id;
          }
          
      }
  </script>
@endpush
