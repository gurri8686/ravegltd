@extends('layouts.main')

@section('sidebar')
@include('layouts.sidebars.admin')
@endsection



@section('content')
<section class="users-list-wrapper">
       <div class="users-list-filter px-1">
            <div class="row  mb-1">
                <div class="col-md-4 col-sm-12 col-lg-8 p-0">
				<h2>All Users</h2>
                  
                 </div>
                <div class="col-md-4 col-sm-12 col-lg-2 text-right sort-filter p-0 mb-1 pr-2">
					<select name="sort" id="sort" class="form-control form-select">
                      <option value="all" @if($selecteddata=='all') selected @endif >All</option>
                      <option value="active"  @if($selecteddata=='active') selected @endif >Active</option>
                      <option value="inactive" @if($selecteddata=='inactive') selected @endif>In-Active</option>
                    </select>
                  </div>
                <div class="col-md-4 col-sm-12 col-lg-2 text-right p-0">
				@if(hasPermissionMenu('management.users.create.create'))
                    <a class="btn btn-primary glow w-100" href="{{ route('management.users.create.create') }}"><i class="fa fa-plus"></i> Create New User</a>
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
                                    <th>#</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                              <?php $i=1; ?>
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
                            <?php
                            $checkadmin = checkadmin($row->id);
                            if(!$checkadmin){
                            if(auth()->user()->id != $row->id){

                            ?>
                                <tr>
                                    <td>{{$i}}</td>
                                    <td>{{$row->first_name}}</td>
                                    <td>{{$row->last_name}}</td>
                                    <td>{{$row->email}}</td>
                                    <td>
                                    <?php $rolename =  \App\Models\RoleModel::where('id',$row->userrole->role_id)->first();
                                    ?>
                                      {{$rolename->name}}
                                    </td>
                                    <td><span class="badge badge-{{$spanClass}}">{{$status}} </span></td>
                                    <td class="text-right">
    <div class="dropstart">
        <button class="btn btn-sm btn-warning dropdown-toggle" type="button" id="userActionDropdown{{ $row->id }}" data-bs-toggle="dropdown" aria-expanded="false">
            Actions
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userActionDropdown{{ $row->id }}">
            @if(hasPermissionMenu('management.users.edit.edit'))
                <li>
                    <a class="dropdown-item" href="{{ route('management.users.edit.edit', $row->id) }}">
                        <i class="btn btn-sm feather icon-edit"></i> Edit
                    </a>
                </li>
            @endif

            @if(hasPermissionMenu('management.users.delete.destroy'))
                <li>
                    <a class="dropdown-item" href="#" onclick="deleteuser({{ $row->id }})">
                        <i class="btn btn-sm feather icon-trash"></i> Delete
                    </a>
                </li>

                <form id="delete-form-{{ $row->id }}" action="{{ route('management.users.delete.destroy', $row->id) }}" method="post">
                    @csrf
                    @method('DELETE')
                </form>
            @endif
        </ul>
    </div>
</td>

                                </tr>
                                  <?php $i++; ?>
                              <?php }
                            }
                            ?>

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
        window.location.href = '/management/users/view/index?user='+valueSelected;
      });

      function deleteuser(id){
        event.preventDefault();
        var del=confirm("Are you sure you want to delete this User?\n");
          if (del==true){
              document.getElementById('delete-form-'+id).submit();
          }
      }
  </script>
@endpush
