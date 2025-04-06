<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\ModeLs\Branch;
use Validator;

class BranchController extends Controller
{
  public function save_branch(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'branch_name' => 'required',
        'country'=>'required',
        'state'=>'required',
        'zip_id'=>'required',
        'full_address'=>'required',
        'branch_type'=>'required',
        'company_id'=>'required'
    ]);

      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $branch = new Branch();
      $branch->branch_name = $request->branch_name;
      $branch->country_id = $request->country;
      $branch->state_id = $request->state;
      $branch->zip_id = $request->zip_id;
      $branch->full_address = $request->full_address;
      $branch->branch_type = $request->branch_type;
      $branch->company_id = $request->company_id;
      $branch->save();
      if($branch){
          return response()->json(['status'=>200,'message'=>'Branch  Created Successfully']);
      }
      else{
         return response()->json(['status'=>500,'message'=>'something went wrong']);
      }
    }
 public function branch_list(){
   // $branch_type = BranchType::get(['id','branch_type','status']);
       $branch = Branch::join('country','country.id','=','branch_details.country_id')
            ->join('state','state.id','=','branch_details.state_id')
            ->join('company_details','company_details.id','=','branch_details.company_id')
            ->join('branch_type','branch_type.id','=','branch_details.branch_type')
            ->select('country.name','state.state_name','company_details.business_name','branch_type.branch_type','branch_details.branch_name','branch_details.zip_id','branch_details.full_address','branch_details.status','branch_details.id')
            ->get();
     return response()->json(['status'=>200,'message'=>'List Of Branches','data'=>$branch]);
    //return $this->sendResponse($branch_type, 'List Of Branch Type.');
  }
  public function update_branch(Request $request,$id){
     $input = $request->all();
     $validator = Validator::make($input, [
        'branch_name' => 'required',
        'country'=>'required',
        'state'=>'required',
        'zip_id'=>'required',
        'full_address'=>'required',
        'branch_type'=>'required',
        'company_id'=>'required'
    ]);

      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $branch = Branch::findOrFail($id);
      $branch->branch_name = $request->branch_name;
      $branch->country_id = $request->country;
      $branch->state_id = $request->state;
      $branch->zip_id = $request->zip_id;
      $branch->full_address = $request->full_address;
      $branch->branch_type = $request->branch_type;
      $branch->company_id = $request->company_id;
      $branch->save();
      if($branch){
          return response()->json(['status'=>200,'message'=>'Branch  Updated Successfully']);
      }
      else{
         return response()->json(['status'=>500,'message'=>'something went wrong']);
      }
  }
  public function branch_edit($id){
    $branch = Branch::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'List Of Branches','branch'=>$branch]);


}
public function branch_status(Request $request){
    //dd('hi');
    $request->validate([
        'branch_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    $branch = Branch::findOrFail($request->branch_id);
    //dd($company);

    // Update the status of the company
    $branch->status = $request->status;
    $branch->save();
    if($branch){
        return response()->json(['status' => 200, 'message' => 'Branch  status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_branch_status($id){
    $branch = Branch::findOrFail($id);
    if($branch){
        return response()->json(['status' => 200, 'message' => 'Branch  status','data'=>$branch->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
}
