<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Lib\Response as CustomResponse;
use Illuminate\Support\Facades\DB;

class WashDatabaseController extends Controller
{
	use CustomResponse;
	
    public function showTables(Request $request){
		$tables = DB::select('SHOW TABLES');

		$excludedTables = [
			'users', 'migrations', 'model_has_permissions', 'model_has_roles',
			'oauth_access_tokens', 'oauth_auth_codes', 'oauth_clients',
			'oauth_personal_access_clients', 'oauth_refresh_tokens',
			'password_resets', 'permission_groups', 'permission_modules',
			'permissions', 'personal_access_tokens','transactions','activity_log','company_details','customers',
			'failed_jobs','files','jobs','passbooks','role_group','role_has_permissions','payments','roles','suppliers',
			'work_time_permissions','products'
		];

		if (empty($tables)) {
			$tableNames = [];
		} else {
			$first = (array) $tables[0];
			$key = array_key_first($first);

			$tableNames = collect($tables)
				->map(fn($table) => $table->$key)
				->reject(fn($name) => in_array($name, $excludedTables))
				->values()
				->toArray();
		}
		return view('wash.show-tables',compact('tableNames'));
	}
	
	public function truncateTables(Request $request){
		$tables = $request->input('tables', []);

		if (empty($tables)) {
			return back()->with('error', 'No tables selected.');
		}

		try {
			// Disable foreign key checks temporarily
			DB::statement('SET FOREIGN_KEY_CHECKS=0;');

			foreach ($tables as $table) {
				// Truncate each table
				DB::table($table)->truncate();
			}

			// Re-enable foreign key checks
			DB::statement('SET FOREIGN_KEY_CHECKS=1;');

			return back()->with('success', 'Selected tables have been truncated successfully.');
		} catch (\Exception $e) {
			// Always re-enable FK checks if something fails
			DB::statement('SET FOREIGN_KEY_CHECKS=1;');

			return back()->with('error', 'Error truncating tables: ' . $e->getMessage());
		}
	}
}
