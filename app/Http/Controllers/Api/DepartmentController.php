<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use Validator;

class DepartmentController extends Controller
{
    public function save_department(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'name' => 'required',
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $department = new Department();
      $department->department_name = $request->name;
      $department->save();
      if($department){
         return response()->json(['status'=>200,'message'=>'Department Created Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }
    }
 public function department_list(){
    $department = Department::get(['id','department_name','status']);
    return response()->json(['status'=>200,'message'=>'List Of Departments','data'=>$department]);
  }
  public function update_department(Request $request,$id){
    $input = $request->all();
    $validator = Validator::make($input, [
        'name' => 'required'
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $department = Department::findOrFail($id);
      $department->department_name = $request->name;
      $department->save();
      if($department){
         return response()->json(['status'=>200,'message'=>'Department Updated Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }
  }
public function department_edit($id){
    $department = Department::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'Department Details','department'=>$department]);


}
public function department_status(Request $request){
    //dd('hi');
    $request->validate([
        'department_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $department = Department::findOrFail($request->department_id);
    //dd($company);

    // Update the status of the company
    $department->status = $request->status;
    $department->save();
    if($department){
        return response()->json(['status' => 200, 'message' => 'Department status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_department_status($id){
    $department = Department::findOrFail($id);
    if($department){
        return response()->json(['status' => 200, 'message' => 'Department status','data'=>$department->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
}
