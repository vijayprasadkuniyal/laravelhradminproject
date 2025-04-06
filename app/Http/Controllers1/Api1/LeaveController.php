<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use Validator;

class LeaveController extends Controller
{
    public function create_leave(Request $request){
         $input = $request->all();
        $validator = Validator::make($input, [
           'name' => 'required',
           'date'=>'required',
           ]);
       if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $leave = new Leave();
      $leave->leave_name = $request->name;
      $leave->date = $request->date;
      $leave->created_by = 1;
      $leave->save();
      if($leave) {
            return response()->json(['status'=>200,'message' => 'Leave Created  Successfully']);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }

    }
    public function leave_list(){
        $leave = Leave::get(['id','leave_name','date','status']);
        if($leave) {
            return response()->json(['status'=>200,'message' => 'Leave Details','data'=>$leave]);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }


    }
    public function leave_edit($id){
        $leave = Leave::findOrFail($id);
        if($leave) {
            return response()->json(['status'=>200,'message' => 'Leave Details','data'=>$leave]);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }

    }
    public function leave_update(Request $request,$id){
        $input = $request->all();
        $validator = Validator::make($input, [
           'name' => 'required',
           'date'=>'required',
           ]);
       if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $leave = Leave::findOrFail($id);
      $leave->leave_name = $request->name;
      $leave->date = $request->date;
      $leave->created_by = 1;
      $leave->save();
      if($leave) {
            return response()->json(['status'=>200,'message' => 'Leave Updated  Successfully']);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }

    }
    public function leave_status(Request $request){
    $request->validate([
        'leave_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);
    $leave = Leave::findOrFail($request->leave_id);
    $leave->status = $request->status;
    $leave->save();
    if($leave){
        return response()->json(['status' => 200, 'message' => 'Leave status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_leave_status($id){
    $leave = Leave::findOrFail($id);
    if($leave){
        return response()->json(['status' => 200, 'message' => 'Leave status','data'=>$leave->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
}
