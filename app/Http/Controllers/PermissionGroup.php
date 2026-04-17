<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use App\Models\PermissionGroup as PermissionGroupModel;
use App\Models\PermissionModule as PermissionModuleModel;
use App\Models\PermissionURLModel;
use Validator;
use Response;
use Session;
use DB;



class PermissionGroup extends Controller
{
    use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $allData = PermissionGroupModel::with('modules')->get();
        return view('permissionGroup.index',['data'=> $allData]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $modelData = PermissionModuleModel::all();
        $routeData = PermissionURLModel::all();
        return view('permissionGroup.create',['data'=> $modelData,'routeDate'=>$routeData]);
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
                'permission_module_id' => 'required',
                'title' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
            if($request->input('is_active')=='on')
            {
                    $is_active=1;
            }else{
                $is_active=0;
            }
            $Alreadyexist = PermissionGroupModel::where(['title'=>$request->input('title')])->first();;
            if($Alreadyexist=='')
            {
                $createData = new PermissionGroupModel;
                $createData->permission_module_id = $request->input('permission_module_id');
                $createData->title = $request->input('title');
                $createData->icon = $request->input('icon');
                $createData->permissions = serialize($request->input('routePermission'));
                $createData->is_active = $is_active;
                $record = $createData->save();
            }
            else
            {
                throw new \Exception("That Group already exists.");
            }
            if (!$record) {
                throw new \Exception("Error to add Module");
            }
            \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully added."]);
            return $this->successResponse(['redirect' => route('management.roles.groups.view.index')]);

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
        $modelData = PermissionModuleModel::all();
        $groupData = PermissionGroupModel::where(['id'=>$id])->first();
        //echo '<pre>';print_r($groupData); die;
        $routeData = PermissionURLModel::all();
        return view('permissionGroup.edit',['modelData'=>$modelData,'groupData'=>$groupData,'routeData'=>$routeData]);
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
                'title' => 'required',
            ];
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors()->messages());
            }
            if($request->input('is_active')=='on'){
                    $is_active=1;
            }else{
                $is_active=0;
            }
            $updateQuery = 0;
            $updateData = PermissionGroupModel::find($id);
            if($request->input('title') == $updateData->title)
            {
                $updateQuery = 1;
            }
            else
            {
                $Alreadyexist = PermissionGroupModel::where(['title'=>$request->input('title')])->first();;
                if($Alreadyexist=='')
                {
                    $updateQuery = 1;
                }
                else
                {
                    throw new \Exception("That Group already exists.");
                }
            }
            if($updateQuery==1)
            {
                $updateData->title = $request->input('title');
                $updateData->icon = $request->input('icon');
                $updateData->permission_module_id = $request->input('permission_module_id');
                $updateData->permissions = serialize($request->input('routePermission'));
                $updateData->is_active = $is_active;
                $record = $updateData->update();
            }
            if (!$record) {
                throw new \Exception("Error to Updated Module");
            }
            \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully updated."]);
            return $this->successResponse(['redirect' => route('management.roles.groups.view.index')]);

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
    public function destroy($id)
    {
        $ID = PermissionGroupModel::find($id);
        $ID->delete();
        \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully removed."]);
        return redirect()->route('management.roles.groups.view.index');
    }

}
