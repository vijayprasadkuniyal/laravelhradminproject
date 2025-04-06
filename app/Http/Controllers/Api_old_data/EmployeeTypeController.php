<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmployeeType;
use Validator;
class EmployeeTypeController extends Controller
{
    public function save_emp_type(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'emp_type' => 'required',
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $emp_type  = new EmployeeType();
      $emp_type->emp_type = $request->emp_type;
      $emp_type->short_name = $request->short_name;
      $emp_type->save();
      if($emp_type){
         return response()->json(['status'=>200,'message'=>'Employee Type Created Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }
 }
 public function emp_type_list(){
    $emp_type = EmployeeType::get(['id','emp_type','status']);
    return response()->json(['status'=>200,'message'=>'List Of Employee Type','data'=>$emp_type]);
  }
public function emp_type_edit($id){
    $emp_type = EmployeeType::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'Emp Type Details','emp_type'=>$emp_type]);
    }
  public function update_emp_type(Request $request,$id){
    $input = $request->all();
    $validator = Validator::make($input, [
        'emp_type' => 'required',
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $emp_type  = EmployeeType::findOrFail($id);
      $emp_type->emp_type = $request->emp_type;
      $emp_type->short_name = $request->short_name;
      $emp_type->save();
      if($emp_type){
         return response()->json(['status'=>200,'message'=>'Employee Type Updated Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }

}
public function emp_type_status(Request $request){
    //dd('hi');
    $request->validate([
        'emp_type_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $emp_type = EmployeeType::findOrFail($request->emp_type_id);
    //dd($company);

    // Update the status of the company
    $emp_type->status = $request->status;
    $emp_type->save();
    if($emp_type){
        return response()->json(['status' => 200, 'message' => 'Employee Type  status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_emp_type_status($id){
    $emp_type = EmployeeType::findOrFail($id);
    if($emp_type){
        return response()->json(['status' => 200, 'message' => 'Emp Type status','data'=>$emp_type->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}

}
