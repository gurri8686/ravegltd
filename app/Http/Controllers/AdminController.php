<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\user;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;
use Auth;
use App\Models\RoleModel;
use App\Models\PermissionRoleModel;
use App\Models\ModelHasRole;
use Illuminate\Validation\Rule;

use Redirect;

class AdminController extends Controller
{
  use CustomResponse;

    public function edit(){

      $id = \Auth::user()->id;
      $checkadmin = checkadmin($id);
      if(!$checkadmin){
          //return Redirect::to('dashboard/view/index');
      }

      $UserDetails = user::where(['id'=>$id])->first();
      $roleUser = ModelHasRole::where(['model_id'=>$id])->first();
      $roleDetails =  RoleModel::all();
      return view('admin.edit',['roleDetails'=>$roleDetails,'assignRole'=>$roleUser,'data'=>$UserDetails]);

    }
    public function update(Request $request)
    {

		//print_r($request->all()); exit;
      $id = \Auth::user()->id;

        $checkadmin = checkadmin($id);
        if(!$checkadmin){
            //return Redirect::to('dashboard/view/index');
        }
		try {
          $rules = [
              'first_name' => 'required',
              'last_name' => 'required',
			  'username' => 'required',
			  //'username'   => 'required|unique:users,username',
              //'email' => 'required',
              // 'role' => 'required',
          ];
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
          // if($request->input('is_active')=='on')
          // {
          //         $is_active=1;
          // }else{
          //     $is_active=0;
          // }
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
			// Image Upload — keep existing if no new file uploaded
			$imageName = $updateData->image;
			if ($request->hasFile('image')) {
				$wfile = $request->file('image');
				$wfilename = 'profile_' . time() . '.' . $wfile->getClientOriginalExtension();
				$wpath = $wfile->storeAs('profile', $wfilename, 'public');
				$imageName = $wpath;
			}
				// $deleteRoleUser = ModelHasRole::where('model_id',$id)->delete();
				// $updateData->assignRole($request->role);
				$updateData->first_name = $request->input('first_name');
				$updateData->last_name = $request->input('last_name');
				//$updateData->email = $request->input('email');
				$updateData->username = $request->input('username');
				$updateData->country = $request->input('country');
				$updateData->state = $request->input('state');
				$updateData->city = $request->input('city');
				$updateData->zipcode = $request->input('zipcode');
				$updateData->address = $request->input('address');
				$updateData->image =$imageName;
				// $updateData->is_active = $is_active;
				// $updateData->assignRole($request->role);
				$record = $updateData->update();
          }

          if (!$record) {
              throw new \Exception("Error to Updated Module");
          }
          /*if($imageName!='' && $request->image!='')
          {
              $request->image->move(public_path('img/userImage'), $imageName);
          }*/

          $imageUrl = $imageName ? asset('storage/' . $imageName) : null;
          return $this->successResponse(['message' => 'Profile updated successfully.', 'image_url' => $imageUrl]);

      } catch (\Exception $ex) {

          return $this->exceptionResponse($ex);
      }


    }

    public function updatePassword(Request $request)
    {
        try {
            $rules = [
                'current_password'  => 'required',
                'new_password'      => 'required|min:6',
                'confirm_password'  => 'required|same:new_password',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }

            $user = \Auth::user();
            if (!\Hash::check($request->input('current_password'), $user->password)) {
                return $this->validationErrorResponse(['current_password' => ['Current password is incorrect.']]);
            }

            $user->password = \Hash::make($request->input('new_password'));
            $user->save();


            return $this->successResponse(['message' => 'Password updated successfully.']);

        } catch (\Exception $ex) {
            return $this->exceptionResponse($ex);
        }
    }
}
