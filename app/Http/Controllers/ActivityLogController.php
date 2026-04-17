<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Validator;
use Response;
use Session;
use DB;
use App\Lib\Response as CustomResponse;

class ActivityLogController extends Controller
{
	use CustomResponse;
    public function index(Request $request)
    {
        $query = Activity::with('causer', 'subject'); // load related models

        // Filters
        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest()->paginate(20);
        
        //return view('activity_logs.index', compact('logs'));
        return view('activity_logs.index-new', compact('logs'));
    }
	
	public function list(Request $request){
		//print_r($request->all()); exit;
        $query = Activity::with('causer', 'subject')->get(); // load related models
		return $this->successResponse($query);
	}
	
	public function users(Request $request){
		return $this->successResponse(\App\Models\User::all());
	}
	
	public function events(Request $request){
		return $this->successResponse([
			['id' => 'created', 'name' => 'Created'],
			['id' => 'updated', 'name' => 'Updated'],
			['id' => 'deleted', 'name' => 'Deleted'],
		]);
	}
}