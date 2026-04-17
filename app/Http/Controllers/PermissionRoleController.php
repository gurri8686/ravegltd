<?php

namespace App\Http\Controllers;

use App\Models\PermissionRoleModel;
use Illuminate\Http\Request;
use App\Models\PermissionGroup as PermissionGroupModel;
use App\Models\PermissionModule as PermissionModuleModel;
use App\Models\PermissionURLModel;
use App\Models\WorkTimePermission;
use App\Models\RoleGroup;
use App\Models\RoleModel;
use Validator;
use Response;
use Session;
use DB;
use Illuminate\Support\Facades\Route;
use App\Lib\Response as CustomResponse;
class PermissionRoleController extends Controller
{
    use CustomResponse;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     **/
    public function index()
    {
        $allData = RoleModel::all();
        return view('permissionRole.index-new',['data'=> $allData]);
    }
	
	public function list()
    {
        $allData = RoleModel::where('id','!=',1)->get();
		return $this->successResponse($allData);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
       // $allDataModule = PermissionModuleModel::where('is_active',1)->get();
        //$allDataGroup = PermissionGroupModel::where('is_active',1)->get();

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
		/*echo "<pre>";print_r($request->all());die;
        print_r($request->all());
        die;*/
        DB::beginTransaction();
        try{
            $CheckboxValue = $request->checkbox;
            $roleid = $request->roleid;
            $record='';
            $CheckboxPermission = $request->permission;
            $start_time = $request->start_time;
            $end_time = $request->end_time;
            $is_24_hours = $request->is_24_hours;
            if($start_time || $end_time || $is_24_hours)
            {
                $alreadyExist = WorkTimePermission::where('role_id', $roleid)->get()->first();
                if($alreadyExist)
                {
                    $alreadyExist = $alreadyExist->toArray();
                    $workTimeData =  WorkTimePermission::find($alreadyExist['id']);
                    $workTimeData->start_time = $start_time;
                    $workTimeData->end_time = $end_time;
                    $workTimeData->is_24_hours = $is_24_hours;
                    $updateWorkTime = $workTimeData->update();
                }
                else
                {

                    $workTimeData = new WorkTimePermission;
                    $workTimeData->role_id = $roleid;
                    $workTimeData->start_time = $start_time;
                    $workTimeData->end_time = $end_time;
                    $workTimeData->is_24_hours = $is_24_hours;
                    $insertWorkTime = $workTimeData->save();
                }
            }

            $alreadyExist = WorkTimePermission::where('role_id', $roleid)->get()->first();
            if($alreadyExist)
            {
                $alreadyExist = $alreadyExist->toArray();
                $workTimeData =  WorkTimePermission::find($alreadyExist['id']);
                $workTimeData->view = '0';
                $workTimeData->edit = '0';
                $workTimeData->create = '0';
                $workTimeData->delete = '0';
                $updateWorkTime = $workTimeData->update();
            }


            if($CheckboxPermission)
            {
				
                $deleteRoleData = PermissionRoleModel::where('role_id',$roleid)->delete();
				
                foreach($CheckboxPermission as $checkpermission)
                {
                    if($CheckboxValue!='')
                    {
                        $alreadyExist = WorkTimePermission::where('role_id', $roleid)->get()->first();
                        if($alreadyExist)
                        {
                            $alreadyExist = $alreadyExist->toArray();
                            $workTimeData =  WorkTimePermission::find($alreadyExist['id']);
                            $workTimeData->$checkpermission = '1';
                            $updateWorkTime = $workTimeData->update();
                        }
                        else
                        {
                            $workTimeData = new WorkTimePermission;
                            $workTimeData->role_id = $roleid;
                            $workTimeData->$checkpermission = '1';
                            $insertWorkTime = $workTimeData->save();
                        }

                        foreach($CheckboxValue as  $row)
                        {

                            if($row)
                            {
								
								// Need testing temporary disabled.
                                /*if(preg_match("/data_entry.sales_entry.sales_customer/i", $row)){
                                    $permissionName = trim($row,'*').'ajaxCustomer';
                                    $permissionMainName = 	'data_entry.sales_entry';
                                } elseif(preg_match("/data_entry.sales_entry.sales_date/i", $row)){
                                    $permissionName = trim($row,'*').'ajaxDate';
                                    $permissionMainName = 	'data_entry.sales_entry';
                                }
                                elseif(preg_match("/daily_report.daily_book_sales/i", $row)){
                                  $permissionMainName = 	'daily_report.daily_book_sales.ajax.sales-list';
                                  $permissionName = trim($row,'*').$checkpermission.'.*';

                                }elseif(preg_match("/daily_report.daily_book_purchase/i", $row)){
                                  $permissionMainName = 	'daily_report.daily_book_purchase.ajax.purchases-list';
                                  $permissionName = trim($row,'*').$checkpermission.'.*';
                                }
                                elseif(preg_match("/historical_reports.customer_history/i", $row)){
                                  $permissionMainName = 	'historical_reports.customer_history.ajax.customer_history';
                                  $permissionName = trim($row,'*').$checkpermission.'.*';
                                }
                                else{
                                    $permissionName = trim($row,'*').$checkpermission.'.*';
                                }*/

								$permissionName = $row;

                                $alreadyExist = PermissionURLModel::where('name', $permissionName)->get()->first();
                                if($alreadyExist)
                                {
                                    $data = $alreadyExist->toArray();
                                }
                                if(!$alreadyExist)
                                {
                                    if(routeExist($permissionName))
                                    {


                                        $createData = new PermissionURLModel;
                                        $createData->name = $permissionName;
                                        $createData->guard_name = 'web';
                                        $insertPermissions = $createData->save();
                                        $lastID  = $createData->id;
                                        if($lastID)
                                        {
                                            $givePermissions = new PermissionRoleModel;
                                            $givePermissions->permission_id = $lastID;
                                            $givePermissions->role_id = $roleid;
                                            $record = $givePermissions->save();
                                        }
                                    }
                                }else
                                {
                                    if(isset($permissionMainName)){

                                      // if($permissionMainName == 'daily_report.daily_book_sales.ajax.sales-list'){
                                      //   $isExists = PermissionURLModel::where('name', 'like', '%' . $permissionMainName. '%')->get();
                                      //   foreach($isExists as  $exists)
                                      //   {
                                      //     $alreadyExistPermission = PermissionRoleModel::where(['permission_id'=>$exists['id'],'role_id'=>$roleid])->get()->first();
                                      //
                                      //     if(!$alreadyExistPermission)
                                      //     {
                                      //
                                      //         $givePermissions = new PermissionRoleModel;
                                      //         $givePermissions->permission_id = $exists['id'];
                                      //         $givePermissions->role_id = $roleid;
                                      //         $record = $givePermissions->save();
                                      //
                                      //     }
                                      //   }
                                      //   echo "<pre>";print_r($isExists);die;
                                      // }
                                        $isExists = PermissionURLModel::where('name', 'like', '%' . $permissionMainName. '%')->get();
                                        foreach($isExists as  $exists)
                                        {
                                            $alreadyExistPermission = PermissionRoleModel::where(['permission_id'=>$exists['id'],'role_id'=>$roleid])->get()->first();
                                            if(!$alreadyExistPermission)
                                            {
                                                $givePermissions = new PermissionRoleModel;
                                                $givePermissions->permission_id = $exists['id'];
                                                $givePermissions->role_id = $roleid;
                                                $record = $givePermissions->save();
                                            }
                                        }

                                    }
                                    $alreadyExistP = PermissionRoleModel::where(['permission_id'=>$data['id'],'role_id'=>$roleid])->get()->first();
                                    if(!$alreadyExistP)
                                    {
                                        $givePermissions = new PermissionRoleModel;
                                        $givePermissions->permission_id = $data['id'];
                                        $givePermissions->role_id = $roleid;
                                        $record = $givePermissions->save();
                                    }
                                }
                            }
                        }
                        if (!$record) {
                                throw new \Exception("Error to add");
                        }
                    }
                }
            }
            DB::commit();
            return $this->successResponse(['message' => 'Permissions saved successfully.']);

        }catch(\Exception $ex){
            DB::rollback();
            return $this->exceptionResponse($ex);
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PermissionRoleModel  $permissionRoleModel
     * @return \Illuminate\Http\Response
     */
    public function show(PermissionRoleModel $permissionRoleModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PermissionRoleModel  $permissionRoleModel
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        //$roleNameDetails = PermissionRoleModel::permissions();
        $workTimePData  = WorkTimePermission::where('role_id',$id)->orderBy('id','desc')->get()->first();
        if($workTimePData)
        {
            $workTimePData=$workTimePData->toArray();
        }
        $data  = PermissionRoleModel::with('permissions:id,name')->where('role_id',$id)->get()->toArray();

        $permissions = [];
        foreach($data as $row)
        {
            $permissions[] = $row['permissions']['name'];
        }
        $roleNameDetails = RoleModel::select('name')->where('id',$id)->first()->toArray();
        $roleName =$roleNameDetails['name'];


        return view('permissionRole.create',['roleid'=>$id,'roleName'=>$roleName,'permissionsExist'=>$permissions,'workTimeData'=>$workTimePData]);


        // $roleid =  $id;
        // $roleNameDetails = RoleModel::select('name')->where('id',$roleid)->first()->toArray();
        // $roleName =$roleNameDetails['name'];
        // $permissionRollData = RoleGroup::select('group_id')->where('role_id',$roleid)->get()->toArray();

        // $arrPermissionGroupID = array();
        // foreach($permissionRollData as $key =>$value)
        // {
        //     array_push($arrPermissionGroupID,$value['group_id']);

        // }
        // $moduleData = PermissionModuleModel::where('is_active',1)->get();
        // $groupData = PermissionGroupModel::where('is_active',1)->get()->toArray();
        // $groupDataArray = [];
        // foreach( $groupData as  $groupDataAll){
        //     extract($groupDataAll);
        //     $groupDataArray[$permission_module_id][] = $groupDataAll;

        // }
        // return view('permissionRole.create',['roleid'=>$roleid,'roleName'=>$roleName);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PermissionRoleModel  $permissionRoleModel
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PermissionRoleModel $permissionRoleModel)
    {

        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PermissionRoleModel  $permissionRoleModel
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $deleteRoleData = PermissionRoleModel::where('role_id',$id)->delete();
        $deleteRoleGroupData = RoleGroup::where('role_id',$id)->delete();

        \Session::flash('redirect', ['type' => 'success', 'message' => "Record successfully removed."]);
        return redirect()->route('management.roles.permission.view.index');
    }
}
