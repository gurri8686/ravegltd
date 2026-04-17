@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection



@section('content')
<section class="users-list-wrapper">
       <div class="users-list-filter px-1">
            <div class="row  mb-1">
                <div class="col-md-4 col-sm-12 col-lg-9 p-0">
                  <h2>Customers</h2>
                 </div>
                <div class="col-md-4 col-sm-12 col-lg-2 p-0 text-right sort-filter mb-1">
                    <select name="sort" id="sort" class="form-control">
                      <option value="all" @if($selecteddata=='all') selected @endif >All</option>
                      <option value="active"  @if($selecteddata=='active') selected @endif >Active</option>
                      <option value="inactive" @if($selecteddata=='inactive') selected @endif>In-Active</option>
                    </select>				
				</div>
                <div class="col-md-4 col-sm-12 col-lg-1 p-0 text-right">
				@if(hasPermissionMenu('management.customers.create.create'))
                    <a class="btn btn-primary glow w-100" href="{{ route('management.customers.create.create') }}">
					<i class="fa fa-plus"></i> Create New</a>
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
                                    <th>ID</th>
                                    <th>Customer ID</th>
                                    <th>Customer Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
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
                                    <td>{{$row->customer_id}}</td>
                                    <td>{{$row->name}}</td>
                                    <td>{{$row->email}}</td>
                                    <td>{{$row->mobile}}</td>
                                    <td>{{$row->address1}} <br> {{$row->address2}}</td>
                                    <td><span class="badge badge-{{$spanClass}}">{{$status}} </span></td>
                                    <td class="text-right">
									<div class="dropstart">
										<button class="btn btn-sm btn-warning dropdown-toggle" type="button" id="customerActionDropdown{{ $row->id }}" data-bs-toggle="dropdown" aria-expanded="false">
											Actions
										</button>
										<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="customerActionDropdown{{ $row->id }}">
											@if(hasPermissionMenu('management.customers.edit.edit'))
												<li>
													<a class="dropdown-item" href="{{ route('management.customers.edit.edit', $row->id) }}">
														<i class="btn btn-sm feather icon-edit"></i> Edit
													</a>
												</li>
											@endif

											{{-- Uncomment if delete is enabled
											@if(hasPermissionMenu('management.customers.delete.destroy'))
												<li>
													<a class="dropdown-item" href="#" onclick="event.preventDefault();document.getElementById('delete-form-{{ $row->id }}').submit();">
														<i class="feather icon-trash me-1"></i> Delete
													</a>
												</li>

												<form id="delete-form-{{ $row->id }}" action="{{ route('management.customers.delete.destroy', $row->id) }}" method="post">
													@csrf
													@method('DELETE')
												</form>
											@endif
											--}}
										</ul>
									</div>
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
@push('scripts')
   <script>
        $('#sort').on('change', function (e) {
        var optionSelected = $("option:selected", this);
        var valueSelected = this.value;
        window.location.href = '/management/customers/view/index?user='+valueSelected;
      });
  </script>
@endpush
