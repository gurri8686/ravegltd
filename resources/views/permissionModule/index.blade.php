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
                @if(hasPermissionMenu('management.roles.modules.create.create'))
                    <a class="btn btn-block btn-primary glow" href="{{ route('management.roles.modules.create.create') }}">Create New</a>
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
                                    <th>Module Title</th>
                                    <th>Module Font Icon</th>
                                    <th>Status</th>
                                    <th>Edit/Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($data as $row)
                            @php
                            $status='';
                            $spanClass='';
                                if($row->is_active==1)
                                {
                                    $spanClass = 'success';
                                    $status =  'Active';
                                }else{
                                    $spanClass = 'danger';
                                    $status = 'Inactive';
                                }
                            @endphp
                                <tr>
                                    <td>{{$loop->index+1}}</td>
                                    <td>{{$row->title}}</td>
                                    <td>{{$row->icon}}</td>
                                    <td><span class="badge badge-{{$spanClass}}">{{$status}} </span></td>
                                    <td>
                                        @if(hasPermissionMenu('management.roles.modules.edit.edit'))
                                            <a  href="{{ route('management.roles.modules.edit.edit', $row->id) }}"><i class="feather icon-edit"></i>&nbsp;&nbsp;&nbsp;&nbsp;</a>
                                        @endif
                                        @if(hasPermissionMenu('management.roles.modules.delete.destroy'))
                                            <a  href="{{ route('management.roles.modules.delete.destroy', $row->id) }}"
                                                onclick="event.preventDefault();document.getElementById('delete-form-{{ $row->id }}').submit();">
                                                <i class="feather icon-trash"></i>
                                            </a>
                                            <form id="delete-form-{{ $row->id }}" action="{{ route('management.roles.modules.delete.destroy', $row->id) }}"
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
