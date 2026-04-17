<?php

namespace App\Http\Controllers;

use App\Models\RoleModel;
use Illuminate\Http\Request;
use App\Models\PermissionGroup as PermissionGroupModel;
use App\Models\PermissionModule as PermissionModuleModel;
use Validator;
use Response;
use Session;
use DB;
use App\Lib\Response as CustomResponse;
class RoleController extends Controller
{
    use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $allData = RoleModel::with('customerrole')->get();
        //return view('role.index',['data'=> $allData]);
        return view('role.index-new',['data'=> $allData]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('role.create');
    }

    public function list(Request $request)
    {
		$data = RoleModel::withCount('customerrole')->where('id','!=', 1)->get();
		return $this->successResponse($data);
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
                'name' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
            $Alreadyexist = RoleModel::where('name',$request->input('name'))->first();
            if(!$Alreadyexist)
            {
                $createData = new RoleModel;
                $createData->name = $request->input('name');
                $createData->guard_name = 'web';
                $record = $createData->save();
                $lastID  = $createData->id;
            }else
            {
                throw new \Exception("That Role already exists.");
            }
        //    echo 'LastID - '.$lastID;
        //    echo '<br>record - '.$record; die;
            if (!$record) {
                throw new \Exception("Error to add.");
            }
            return $this->successResponse(['message' => 'Role created successfully.', 'redirect' => route('management.settings.index').'?tab=roles']);

        } catch (\Exception $ex) {

            return $this->exceptionResponse($ex);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RoleModel  $roleModel
     * @return \Illuminate\Http\Response
     */
    public function show(RoleModel $roleModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RoleModel  $roleModel
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = RoleModel::where(['id'=>$id])->first();
        return view('role.edit',['data'=>$data]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\RoleModel  $roleModel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $rules = [
                'name' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
            $updateQuery = 0;
            $updateData = RoleModel::find($id);
            if($request->input('name') == $updateData->name)
            {
                $updateQuery = 1;
            }
            else{
                $Alreadyexist = RoleModel::where('name',$request->input('name'))->first();
                if(!$Alreadyexist)
                {
                    $updateQuery = 1;
                }
                else
                {
                    throw new \Exception("That Role already exists.");
                }
            }
            if($updateQuery==1)
            {
                $updateData->name = $request->input('name');
                $record = $updateData->update();
            }
            if (!$record) {
                throw new \Exception("Error to Updated Module");
            }
            return $this->successResponse(['message' => 'Role updated successfully.']);

        } catch (\Exception $ex) {

            return $this->exceptionResponse($ex);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RoleModel  $roleModel
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $role = RoleModel::find($id);
            if (!$role) {
                return $this->exceptionResponse(new \Exception('Role not found.'));
            }
            // Remove related records first to avoid FK constraint errors
            DB::table('model_has_roles')->where('role_id', $id)->delete();
            DB::table('role_has_permissions')->where('role_id', $id)->delete();
            $role->delete();
            return $this->successResponse(['message' => 'Role deleted successfully.']);
        } catch (\Exception $ex) {
            return $this->exceptionResponse($ex);
        }
    }
}
