@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin');
@endsection



@section('content')
<section class="users-list-wrapper">
       <div class="users-list-filter px-1">
            <div class="row  mb-1">
                <div class="col-12 col-sm-6 col-lg-3"> </div>
                <div class="col-12 col-sm-6 col-lg-3"></div>
                <div class="col-12 col-sm-7 col-lg-3"></div>
                <div class="col-12 col-sm-8 col-lg-3 d-flex align-items-center">
                @if(hasPermissionMenu('company_details.create.create'))
                    <a class="btn btn-block btn-primary glow" href="{{ route('company_details.create.create') }}">Create New User</a>
                @endif
                </div>
            </div>
    </div>

    <div class="users-list-table">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <!-- datatable start -->
                    <div class="table-responsive">
                        <table id="users-list-datatable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Company ID</th>
                                    <th>Company Name</th>
                                    <th>Website</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Address 1</th>
                                    <th>Address 2</th>
                                    <th>Edit/Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($data as $row)
                            @php

                            $spanClass='';

                            @endphp
                                <tr>
                                    <td>{{$loop->index+1}}</td>
                                    <td>{{$row->company_id}}</td>
                                    <td>{{$row->company_name}}</td>
                                    <td>{{$row->website}}</td>
                                    <td>{{$row->email}}</td>
                                    <td>{{$row->mobile}}</td>
                                    <td>{{$row->address_1}}</td>
                                    <td>{{$row->address_2}}</td>
                                    <td>
                                        @if(hasPermissionMenu('management.customers.edit.edit'))
                                            <a  href="{{ route('CompanyDetails.edit', $row->id) }}"><i class="feather icon-edit"></i>&nbsp;&nbsp;&nbsp;&nbsp;</a>
                                        @endif
                                        @if(hasPermissionMenu('management.customers.delete.destroy'))
                                            <a  href="{{ route('CompanyDetails.destroy', $row->id) }}"
                                                onclick="event.preventDefault();document.getElementById('delete-form-{{ $row->id }}').submit();">
                                                <i class="feather icon-trash"></i>
                                            </a>
                                            <form id="delete-form-{{ $row->id }}" action="{{ route('CompanyDetails.destroy', $row->id) }}"
                                                method="post" >
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        @endif
                                    </td>
                                </tr>

                                @endforeach
                            </tbody>
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
