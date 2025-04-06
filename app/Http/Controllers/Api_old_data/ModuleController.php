<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Validator;
use App\Models\Department;
use App\Models\Module;
use App\Models\MOdulePermission;

class ModuleController extends Controller
{
    public function save_module(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'module' => 'required',
            'department' => 'required|array',
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages();
            return response()->json(["messages" => $messages, 'status' => 400]);
        }

        $departments = (array)$request->department;

        foreach ($departments as $departmentId) {
            $module = DB::table('save_module')->insert([
                'module' => $request->module,
                'department' => $departmentId,
                'created_by'=>$request->emp_id,
                'status' => 1,
            ]);
        }

        if ($module) {
            return response()->json(['status' => 200, 'message' => 'Module Created Successfully']);
        } else {
            return response()->json(['status' => 500, 'message' => 'Something went wrong']);
        }
    }
    public function module_list(Request $request){
    	$module_list = DB::table('save_module')->join('department','save_module.department','=','department.id')
    	             ->select('department.department_name','save_module.*')
    	             ->paginate($request->per_page);
    	 if ($module_list) {
               return response()->json(['status' => 200, 'message' => 'Module List','data'=>$module_list,'last_page'=>$module_list->lastPage()]);
        } else {
            return response()->json(['status' => 500, 'message' => 'Something went wrong']);
        }

    }
    public function module_edit($id){
        $module = Module::findOrFail($id);
        if($module) {
            return response()->json(['status'=>200,'message' => 'Module Details','data'=>$module]);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }

    }
   /* public function module_update(Request $request,$id){
        $input = $request->all();
        $validator = Validator::make($input, [
           'module' => 'required',
           'department'=>'required',
           ]);
       if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      Module::where('')
       $departments = (array)$request->department;

        foreach ($departments as $departmentId) {
            $module = DB::table('save_module')->insert([
                'module' => $request->module,
                'department' => $departmentId,
                'created_by'=>$request->emp_id,
                'status' => 1,
            ]);
        }

      if($module) {
            return response()->json(['status'=>200,'message' => 'Module Updated  Successfully']);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }

    }*/
    public function update_module_status(Request $request){
    $request->validate([
        'module_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);
    $module = Module::findOrFail($request->module_id);
    $module->status = $request->status;
    $module->save();
    if($module){
        return response()->json(['status' => 200, 'message' => 'Module status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function module_status($id){
    $module = Module::findOrFail($id);
    if($module){
        return response()->json(['status' => 200, 'message' => 'module status','data'=>$module->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
public function department_based_module(Request $request){
	$department = Module::where('department',$request->department)->where('status',1)->get();
	return response()->json(['status' =>200, 'message' => 'module list','data'=>$department]);


}
public function submitted_module($id){
	$data = MOdulePermission::where('emp_id',$id)->where('status',1)->get();
	return response()->json(['status' =>200, 'message' => 'module list','data'=>$data]);


}
public function fetched_emp_permission($id){
    $data = ModulePermission::where('emp_id', $id)->where('status', 1)->get();
    $modules = [];

    foreach ($data as $row) {
        $module = Module::where('id', $row->module_name)->value('module');
        
        // Ensure the module is not null before adding it to the array
        if ($module !== null) {
            $modules[] = ['module' => $module];
        }
    }

    return response()->json(['status' => 200, 'message' => 'employee module permission', 'data' => $modules]);
}



}
