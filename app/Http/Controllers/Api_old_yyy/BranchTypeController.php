<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BranchType;
use Validator;

class BranchTypeController extends Controller
{
    public function save_branch_type(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'branch_type' => 'required',
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $branch_type = new BranchType();
      $branch_type->branch_type = $request->branch_type;
      $branch_type->save();
      if($branch_type){
          return response()->json(['status'=>200,'message'=>'Branch Type Created Successfully']);
      }
      else{
         return response()->json(['status'=>500,'message'=>'something went wrong']);
      }
    }
 public function branch_type_list(){
    $branch_type = BranchType::get(['id','branch_type','status']);
    return response()->json(['status'=>200,'message'=>'List Of Branch Type','data'=>$branch_type]);
    //return $this->sendResponse($branch_type, 'List Of Branch Type.');
  }
  public function update_branch_type(Request $request,$id){
    $input = $request->all();
    $validator = Validator::make($input, [
        'branch_type' => 'required',
        ]);
       if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $branch_type = BranchType::findOrFail($id);
      $branch_type->branch_type = $request->branch_type;
      $branch_type->save();
      if($branch_type){
          return response()->json(['status'=>200,'message'=>'Branch Type Updated Successfully']);
      }
      else{
         return response()->json(['status'=>500,'message'=>'something went wrong']);
      }
  }
  public function branch_type_edit($id){
    $branch_type = BranchType::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'Branch Type Details','branch_type'=>$branch_type]);


}
public function branch_type_status(Request $request){
    //dd('hi');
    $request->validate([
        'branch_type_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    $branch_type = BranchType::findOrFail($request->branch_type_id);
    //dd($company);

    // Update the status of the company
    $branch_type->status = $request->status;
    $branch_type->save();
    if($branch_type){
        return response()->json(['status' => 200, 'message' => 'Branch Type status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_branch_type_status($id){
    $branch_type = BranchType::findOrFail($id);
    if($branch_type){
        return response()->json(['status' => 200, 'message' => 'Branch Type status','data'=>$branch_type->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
public function get_active_branch_type(){
    $branch_type = BranchType::where('status',1)->get(['id','branch_type']);
    if($branch_type){
        return response()->json(['status' => 200, 'message' => 'branch type details','data'=>$branch_type]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
}
