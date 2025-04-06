<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Validator;
use App\Models\FollowupAttribute;

class FollowupController extends Controller
{
    public function save_followup_status(Request $request){
        $input = $request->all();
         $validator = Validator::make($input, [
            'name' => 'required',
         ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $data = array('name'=>$request->name,'created_by'=>$request->emp_id);
      DB::table('candidate_followup_status_details')->insert($data);
      return response()->json(['status'=>200,'message'=>'Followup status created successfully']);
    }
    public function followup_list(){
        $data = DB::table('candidate_followup_status_details')->get(['status','name','id']);
        return response()->json(['status'=>200,'message'=>'Followup list','data'=>$data]);

    }
    public function followup_edit($id){
        $followup_attribute = FollowupAttribute::findOrFail($id);
        return response()->json(['status'=>200,'message'=>'Followup Details','data'=>$followup_attribute]);
        }
      public function update_followup_attribute(Request $request,$id){
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required',
            ]);
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
          $followup  = FollowupAttribute::findOrFail($id);
          $followup->name = $request->name;
          $followup->created_by = $request->emp_id;
          $followup->save();
          if($followup){
             return response()->json(['status'=>200,'message'=>'Followup status Updated successfully']);
         }
         else{
            return response()->json(['status'=>500,'message'=>'something went wrong']);
    
         }
    
    }
    public function followup_status(Request $request){
        $request->validate([
            'followup_id' => 'required',
            'status' => 'required|in:0,1', 
        ]);
    
        $followup = FollowupAttribute::findOrFail($request->followup_id);
        $followup->status = $request->status;
        $followup->save();
        if($followup){
            return response()->json(['status' => 200, 'message' => 'Followup   status updated successfully']);
    
        }
        else{
            return response()->json(['status' => 500, 'message' => 'something went wrong']);
    
        }
    
    
    }
    public function get_followup_status($id){
        $followup = FollowupAttribute::findOrFail($id);
        if($followup){
            return response()->json(['status' => 200, 'message' => 'followup status','data'=>$followup]);
    
        }
        else{
            return response()->json(['status' =>500, 'message' => 'data not found']);
    
        }
    
    
    }
    public function active_followup_details(){
        $data = FollowupAttribute::where('status',1)->get(['id','name']);
        return response()->json(['status' =>200, 'message' => 'Active Attribute List','data'=>$data]);

    }
}
