@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection



@section('content')
<section class="users-list-wrapper">
       <div class="users-list-filter px-1">
            <div class="row  mb-1">
                <div class="col-12 col-sm-6 col-lg-3 p-0">
				<h2>Roles Permission</h2>
				</div>
                <div class="col-12 col-sm-6 col-lg-3"></div>
                <div class="col-12 col-sm-7 col-lg-3"></div>

            </div>
    </div>

    <div class="users-list-table">
        <div class="card">
            <div class="card-content">

                <div class="card-body">
                    <!-- datatable start -->

                    <div class="table-responsive">
                        <table id="users-list-datatable" class="table c-table table-hover">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Role Name</th>
                                    <th>Permission</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($data as $row)
                              @if($row->name != 'admin')
                                <tr>
                                    <td>{{$loop->index+1}}</td>
                                    <td><b>{{$row->name}}</b></td>
                                    <td>
                                    @if(hasPermissionMenu('management.roles.permission.edit.edit'))
                                        <a href="{{ route('management.roles.permission.edit.edit', $row->id) }}"><i class="feather icon-edit"></i> <b>Add permission</b></a>
                                    @endif
                                    <!-- @if(hasPermissionMenu('management.roles.permission.delete.destroy'))
                                        <a  href="{{ route('management.roles.permission.delete.destroy', $row->id) }}"
                                            onclick="event.preventDefault();document.getElementById('delete-form-{{ $row->id }}').submit();">
                                            <i class="feather icon-trash"></i>
                                        </a>
                                        <form id="delete-form-{{ $row->id }}" action="{{ route('management.roles.permission.delete.destroy', $row->id) }}"
                                            method="post" >
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                    @endif -->
                                    </td>
                                </tr>
                                @endif

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
