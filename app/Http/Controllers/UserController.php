<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;
use App\Models\RoleModel;
use App\Models\PermissionRoleModel;
use App\Models\ModelHasRole;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      if (request()->has('user')) {

        if(request()->user == 'active'){

        $allData = user::with('userrole')->where('is_active',1)->get();
            $selecteddata = 'active';
        }
        elseif(request()->user == 'inactive'){

          $allData = user::with('userrole')->where('is_active',0)->get();
            $selecteddata = 'inactive';
        }else{
          $allData = user::with('userrole')->get();
              $selecteddata = 'all';
        }

      }else{
        $allData = user::with('userrole')->where('is_active',1)->get();
          $selecteddata = 'active';
      }

       //return view('user.index',['data'=> $allData,'selecteddata'=> $selecteddata]);
       return view('user.index-new',['data'=> $allData,'selecteddata'=> $selecteddata]);
    }
	
	public function list(Request $request){
		$users = User::with('roles')->where('id','!=',1)->get()->map(function ($user) {
			$user->roles_list = $user->roles->pluck('name')->join(', ');
			$user->last_login_label = $this->formatLastLogin($user->last_login_at);
			return $user;
		});
		return $this->successResponse($users);
	}

	/**
	 * Human-friendly "last login" text: Today / Yesterday / N days ago / date.
	 */
	private function formatLastLogin($value)
	{
		if (empty($value)) {
			return 'Not logged in yet';
		}
		try {
			$dt = \Carbon\Carbon::parse($value);
		} catch (\Throwable $e) {
			return 'Not logged in yet';
		}
		if ($dt->isToday()) {
			return 'Today, ' . $dt->format('H:i');
		}
		if ($dt->isYesterday()) {
			return 'Yesterday, ' . $dt->format('H:i');
		}
		$days = $dt->diffInDays(\Carbon\Carbon::now());
		if ($days <= 7) {
			return $days . ' days ago';
		}
		return $dt->format('d M Y');
	}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //$roleDetails =   DB::select('select * from roles where id in (select role_id from role_has_permissions group by role_id)');
       // $roleDetails =   DB::select('select * from roles');
        $roleDetails = RoleModel::all();
        return view('user.create',['roleDetails'=>$roleDetails]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {				
        try{
            $rules = [
                'first_name' => 'required|min:3|regex:/(^[A-Za-z ]+$)+/',
                'last_name' => 'required|min:3|regex:/(^[A-Za-z ]+$)+/',
                'email' => 'required|email:filter|regex:/^[A-z0-9!@£$%^&*().\'~*_+\-]+$/',
                'password' => 'required',
                'role' => 'required',
				'username'   => 'required|unique:users,username',
            ];

            if($request->zipcode){
              $rules['zipcode'] = 'max:12|regex:/(^[A-Za-z0-9 ]+$)+/';
            }
            if($request->city){
              $rules['city'] = 'regex:/(^[A-Za-z0-9 ]+$)+/';
            }
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
            if($request->has('is_active'))
            {
                    $is_active=1;
            }else{
                    $is_active=0;
            }
			
            /*$imageName='';
            if($request->image)
            {
                if($request->image->extension())
                {
                    $imageName = time().'.'.$request->image->extension();
                }
            }*/
			
            $Alreadyemail = user::where('email',$request->input('email'))->first();
			$imageName = '';
            if(!$Alreadyemail)
            {
				/**
				 = ''; // default empty for new user
				 * Image Upload.
				 */
				if ($request->hasFile('image')) {
					$wfile = $request->file('image');
					$wfilename = 'profile_' . time() . '.' . $wfile->getClientOriginalExtension();
					// Save to storage/app/public/invoices
					$wpath = $wfile->storeAs('profile', $wfilename, 'public');
					// Save filename/path in database
					$imageName = $wpath;
				}
				
                $createData = new user;
                $createData->first_name = $request->input('first_name');
                $createData->last_name = $request->input('last_name');
                $createData->email = $request->input('email');
                // $createData->country = $request->input('country');
                // $createData->state = $request->input('state');
                $createData->city = $request->input('city');
                $createData->zipcode = $request->input('zipcode');
                $createData->address = $request->input('address');
				$createData->username = $request->input('username');
                $createData->password = Hash::make($request->input('password'));
                $createData->image =$imageName;
                $createData->is_active = $is_active;
                $createData->assignRole($request->role);

                $record = $createData->save();
            }
            else
            {
                throw new \Exception("<b>".$request->input('email')."</b>"." email already exists.");
            }
            if (!$record) {
                throw new \Exception("Error to add Module");
            }
            /*if($imageName!='')
            {
                $request->image->move(public_path('img/userImage'), $imageName);
            }*/
            return $this->successResponse(['message' => 'User created successfully.', 'redirect' => route('management.settings.index').'?tab=users']);

        } catch (\Exception $ex) {

            return $this->exceptionResponse($ex);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $UserDetails = user::where(['id'=>$id])->first();
        $roleUser = ModelHasRole::where(['model_id'=>$id])->first();
        $roleDetails =  RoleModel::all();
        return view('user.edit',['roleDetails'=>$roleDetails,'assignRole'=>$roleUser,'data'=>$UserDetails]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $rules = [
                'first_name' => 'required|min:3|regex:/(^[A-Za-z ]+$)+/',
                'last_name' => 'required|min:3|regex:/(^[A-Za-z ]+$)+/',
                'email' => 'required|email:filter|regex:/^[A-z0-9!@£$%^&*().\'~*_+\-]+$/',
                'role' => 'required',
				'username' => 'required',
            ];
            if($request->password){
              $rules['password'] =  'same:password_confirmation';

            }
            if($request->zipcode){
              $rules['zipcode'] = 'max:12|regex:/(^[A-Za-z0-9 ]+$)+/';
            }

            if($request->city){
              $rules['city'] = 'regex:/(^[A-Za-z0-9 ]+$)+/';
            }
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
            /*if($request->image)
            {
                if($request->image->extension())
                {
                    $imageName = time().'.'.$request->image->extension();
                }
            }
            else
            {
                $imageName =$request->imageP;
            }*/
            if($request->has('is_active'))
            {
                    $is_active=1;
            }else{
                $is_active=0;
            }
            $updateQuery = 0;
            $updateData = user::find($id);
            if($request->input('email') == $updateData->email)
            {
                $updateQuery = 1;
            }else{
                $Alreadyemail = user::where('email',$request->input('email'))->first();
                if(!$Alreadyemail)
                {
                    $updateQuery = 1;
                }
                else{
                    throw new \Exception("<b>".$request->input('email')."</b>"." email already exists.");
                }

            }
			
			$usernamRule = [
				'username'   => [
					'required',
					Rule::unique('users', 'username')->ignore($updateData->id), // ignore current user
				],
			];
			$usernamevalidator = Validator::make($request->all(), $usernamRule);
			if ($usernamevalidator->fails()) {
			  return $this->validationErrorResponse($usernamevalidator->errors()->messages());
			}
			
			
            if($updateQuery==1)
            {
				$imageName = $updateData->image; // keep existing if no new file
				if ($request->hasFile('image')) {
					$wfile = $request->file('image');
					$wfilename = 'profile_' . time() . '.' . $wfile->getClientOriginalExtension();
					// Save to storage/app/public/invoices
					$wpath = $wfile->storeAs('profile', $wfilename, 'public');
					// Save filename/path in database
					$imageName = $wpath;
				}
		
                $deleteRoleUser = ModelHasRole::where('model_id',$id)->delete();
                $updateData->assignRole($request->role);
                $updateData->first_name = $request->input('first_name');
                $updateData->last_name = $request->input('last_name');
                $updateData->email = $request->input('email');
                $updateData->country = $request->input('country');
                // $updateData->state = $request->input('state');
                $updateData->city = $request->input('city');
                $updateData->zipcode = $request->input('zipcode');
                $updateData->address = $request->input('address');
                $updateData->image =$imageName;
				$updateData->username =$request->input('username');
                $updateData->is_active = $is_active;
                $updateData->assignRole($request->role);
                if($request->password){
                  $updateData->password = Hash::make($request->input('password'));
                }
                $record = $updateData->update();
            }

            if (!$record) {
                throw new \Exception("Error to Updated Module");
            }
            /*if($imageName!='' && $request->image!='')
            {
                $request->image->move(public_path('img/userImage'), $imageName);
            }*/
            return $this->successResponse(['message' => 'User updated successfully.', 'redirect' => route('management.settings.index').'?tab=users']);

        } catch (\Exception $ex) {

            return $this->exceptionResponse($ex);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $ID = user::find($id);
        if ($ID) {
            $ID->delete();
        }
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
        }
        \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully removed."]);
        return redirect()->route('management.users.view.index');
    }
}
