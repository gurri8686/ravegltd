@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection



@section('content')
@include('product._tabs')
<section class="users-list-wrapper">
       <div class="users-list-filter px-1">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <h2 class="mb-0">All Products</h2>
                @if(hasPermissionMenu('management.products.create.create'))
                    <a class="btn btn-primary glow" href="{{ route('management.products.create.create') }}" style="white-space: nowrap; padding: 6px 12px; font-size: 13px;">
                        <i class="fa fa-plus"></i> Create New</a>
                @endif
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
                                    <th>#</th>
                                    <th>Product ID</th>
                                    <th>Product Name</th>
                                    <th>Description</th>
                                    <th>Selling Price</th>
                                    <th>Tax/Vat</th>
                                    <th>Unit Weight</th>
                                    <th>Added By</th>
                                    <th>Date</th>
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
                                    <td>{{$row->product_id}}</td>
                                    <td>{{$row->name}}</td>
                                    <td>{{$row->description}}</td>
                                    <td>{{$row->selling_price}}</td>
                                    <td>{{$row->tax_vat}}</td>
                                    <td>{{$row->unit_weight}}</td>
                                    <td>{{($row->user)?$row->user->first_name:""}}</td>
                                    <td>{{ uk_ts($row->created_at, 'Y-m-d') }}</td>
                                    <td><span class="badge badge-{{$spanClass}}">{{$status}} </span></td>
                                    <td class="text-right">
									<div class="dropstart">
										<button class="btn btn-sm btn-warning dropdown-toggle" type="button" id="productActionDropdown{{ $row->id }}" data-bs-toggle="dropdown" aria-expanded="false">
											Actions
										</button>
										<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="productActionDropdown{{ $row->id }}">
											@if(hasPermissionMenu('management.products.edit.edit'))
												<li>
													<a class="dropdown-item" href="{{ route('management.products.edit.edit', $row->id) }}">
														<i class="btn btn-sm feather icon-edit"></i> Edit
													</a>
												</li>
											@endif
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
