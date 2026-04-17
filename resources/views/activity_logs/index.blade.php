@extends('layouts.main')

@section('sidebar')
    @include('layouts.sidebars.admin')
@endsection


@section('content')
<section class="">
	<div class="users-list-filter px-1">
            <div class="row  mb-1">
                <div class="col-12 col-sm-6 p-0 col-lg-3">
				<h2>Activity Logs</h2>
				</div>
			</div>
		</div>
 <div class="card">
        <div class="card-content">
            <div class="card-body">
    <!-- Filters -->
    <form method="GET" action="{{ route('activity_logs.users.view.index') }}" class="mb-3 row g-2">
        <div class="col-md-3">
            <select name="user_id" class="form-control">
                <option value="">-- Select User --</option>
                @foreach(\App\Models\User::all() as $user)
                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                        {{ $user->first_name }} {{ $user->last_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <select name="event" class="form-control">
                <option value="">-- Event --</option>
                <option value="created" {{ request('event')=='created'?'selected':'' }}>Created</option>
                <option value="updated" {{ request('event')=='updated'?'selected':'' }}>Updated</option>
                <option value="deleted" {{ request('event')=='deleted'?'selected':'' }}>Deleted</option>
            </select>
        </div>

        <div class="col-md-2">
            <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
        </div>

        <div class="col-md-2">
            <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <!-- Grid -->
    
    <div class="table-responsive"> 
    <table class="table table-bordered table-striped table table-hover dataTable no-footer">
        <thead>
            <tr>
                <th>Log ID</th>
                <th>User</th>
                <th>Event</th>
                <th>Model</th>
                <th>Description</th>
                <th>Properties</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                <tr>
                    <td>{{$log->id}}</td>
                    <td>#{{$log->causer->id}} {{$log->causer->first_name}} {{ $log->causer->last_name}}</td>
                    <td>{{ $log->event }}</td>
                    <td>{{ class_basename($log->subject_type) }}</td>
                    <td>{{ $log->description }}</td>
                    <th class="text-center">
                        <!--<textarea class="form-control">{{ json_encode($log->properties, JSON_PRETTY_PRINT) }}</textarea>-->
                        <a href="#" onclick="" class="btn btn-primary mr-1 btn-sm"><i class="feather icon-eye"></i> View</a>
                    </th>

                    <td>{{ $log->created_at->format('d M,y H:iA') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div></div></div></div>
    <!-- Pagination -->
     <div class="row">
        <div class="col-sm-12">
    {{ $logs->withQueryString()->links() }}
</div>
</div>
</section>
@endsection
