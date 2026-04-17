@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection



@section('content')
<section class="users-list-wrapper">
       <div class="users-list-filter px-1">
            <div class="row  mb-1">
				<div class="col-md-6 col-sm-12 col-lg-11 p-0"><h2 class="p-0">Roles Management</h2></div>
                <div class="col-md-6 col-sm-12 col-lg-1 p-0 text-right">
                @if(hasPermissionMenu('management.roles.role.create.create'))
                    <button class="btn btn-primary w-100"><a class="text-white" href="{{ route('management.roles.role.create.create') }}"><i class="fa fa-plus"></i> Create New</a></button>
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
                        <table id="users-list-datatable" class="table c-table table-hover">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Name</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php $i=1; ?>
                            @foreach($data as $row)
                              @if($row->name != 'admin')
                                <tr>
                                    <td>{{$i}}</td>
                                    <td>{{$row->name}}</td>
                                    <td class="text-right">
										<div class="dropstart">
											<button class="btn btn-sm btn-warning dropdown-toggle" type="button" id="actionDropdown{{ $row->id }}" data-bs-toggle="dropdown" aria-expanded="false">
												Actions
											</button>
											<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown{{ $row->id }}">
												@if(hasPermissionMenu('management.roles.role.edit.edit'))
													<li>
														<a class="dropdown-item" href="{{ route('management.roles.role.edit.edit', $row->id) }}">
															<i class="fa icon-pencil"></i> Edit
														</a>
													</li>
												@endif

												@if(hasPermissionMenu('management.roles.role.delete.destroy'))
													@if($row->customerrole->count() < 1)
														<li>
															<a class="dropdown-item" href="#" onclick="deleterole({{ $row->id }})">
																<i class="feather icon-trash me-1"></i> Delete
															</a>
														</li>
													@else
														<?php
															if($row->customerrole->count() == 1){
																 $msg = 'This role is assigned to a user so it can`t be deleted.';
															}else{
																 $msg = 'This role is assigned to multiple users so it can`t be deleted.';
															}
														?>
														<li>
															<a class="dropdown-item" href="#" onclick="checkdelete('{{ $msg }}')">
																<i class="fa fa-trash"></i> Delete
															</a>
														</li>
													@endif

													<form id="delete-form-{{ $row->id }}" action="{{ route('management.roles.role.delete.destroy', $row->id) }}" method="post">
														@csrf
														@method('DELETE')
													</form>
												@endif
											</ul>
										</div>
									</td>

                                </tr>
                                <?php $i++; ?>
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
@push('scripts')
<script>
function checkdelete(msg){

alert(msg);
}
function deleterole(id){

  event.preventDefault();

  var del=confirm("Are you sure you want to delete this Role?\n");
    if (del==true){
        document.getElementById('delete-form-'+id).submit();
    }


}
</script>
@endpush
