<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Designation;
use Validator;

class DesignationController extends Controller
{
   public function save_designation(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'name' => 'required',
        'department'=>'required'
        ]);
     if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $designation = new Designation();
      $designation->designation_name = $request->name;
      $designation->department_id = $request->department;
      $designation->save();
     if($designation){
         return response()->json(['status'=>200,'message'=>'Designation Created Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }
 }
  public function designation_list(){
     $designation = Designation::join('department','department.id','=','designation.department_id')
           ->select('department.department_name','designation.id as designation_id','designation.designation_name','designation.status')
           ->get();
      return response()->json(['status'=>200,'message'=>'List Of Designation','data'=>$designation]);
   }
  public function update_designation(Request $request,$id){
     $input = $request->all();
      $validator = Validator::make($input, [
        'name' => 'required',
        'department'=>'required'
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $designation = Designation::findOrFail($id);
      $designation->designation_name = $request->name;
      $designation->department_id = $request->department;
      $designation->save();
      if($designation){
         return response()->json(['status'=>200,'message'=>'Designation Created Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }

}
public function designation_edit($id){
    //dd('hi');
    $designation = Designation::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'Designation Details','designation'=>$designation]);
}
public function designation_status(Request $request){
    //dd('hi');
    $request->validate([
        'designation_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $designation = Designation::findOrFail($request->designation_id);
    //dd($company);

    // Update the status of the company
    $designation->status = $request->status;
    $designation->save();
    if($designation){
        return response()->json(['status' => 200, 'message' => 'Designation status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_designation_status($id){
    $designation = Designation::findOrFail($id);
    if($designation){
        return response()->json(['status' => 200, 'message' => 'Designation status','data'=>$designation->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
}
