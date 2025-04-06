<?php

namespace App\Http\Controllers\SalesApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\adminSales\followup;
use Validator;

class adminFollowupController extends Controller
{
    public function add_followup_status(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'dispostionName' => 'required',
            'name' => 'required',
            'holding_days'=>'required'
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }
        $followup = new followup();
        $followup_activity = str_replace(' ', '_', $request->name);
        $followup->dispostion = $request->dispostionName;
        $followup->followup_activity = $followup_activity;
        $followup->activity_name = $request->name;
        $followup->holding_days = $request->holding_days;
        $followup->save();
        if($followup)
        {
            return response()->json(['status'=>200,'message'=>'State Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }
    

    public function followup_list(){
        $followup = followup::all();
        return response()->json(['status'=>200,'message'=>'Followup List','data'=>$followup]);
    }

  public function update_state(Request $request,$id){
     $input = $request->all();
     $validator = Validator::make($input, [
        'name' => 'required',
        'short_name' => 'required',
        'country'=>'required'
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $state = State::findOrFail($id);
      $state->state_name = $request->name;
      $state->country_id = $request->country;
      $state->state_short_name = $request->short_name;
      $state->save();
      if($state){
         return response()->json(['status'=>200,'message'=>'State Updated Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }

}
public function followup_edit($id){
    $followup = State::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'List Of State Details','state'=>$followup]);
    }


public function followup_change_status(Request $request)
{
    $request->validate([
        'id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    $followup = followup::findOrFail($request->id);
    $followup->status = $request->status;
    $followup->save();

    if($request->status ==0)
    {
        $msg = "Followup Activity Deactivated successfully";
    }
    else
    {
        $msg = "Followup Activity Activated successfully";
    }
    
    if($followup)
    {
        return response()->json(['status' => 200, 'message' => $msg]);
    }
    else
    {
        return response()->json(['status' => 500, 'message' => 'something went wrong']);
    }
}

public function get_followup_status($id){
    $followup = followup::findOrFail($id);
    if($followup){
        return response()->json(['status' => 200, 'message' => 'State status','data'=>$followup->status]);

    }
    else
    {
        return response()->json(['status' =>500, 'message' => 'data not found']);
    }
}






}
