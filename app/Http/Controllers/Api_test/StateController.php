<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\State;
use App\Models\Country;
use Validator;

class StateController extends Controller
{
    public function save_state(Request $request){
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
      $state = new State();
      $state->state_name = $request->name;
      $state->country_id = $request->country;
      $state->state_short_name = $request->short_name;
      $state->save();
      if($state){
         return response()->json(['status'=>200,'message'=>'State Created Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }
 }
 public function state_list(){
     $state = State::join('country','country.id','=','state.country_id')
           ->select('country.name as country_name','state.id as state_id','state.state_name','state.state_short_name','state.status')
           ->get();
      return response()->json(['status'=>200,'message'=>'List Of States','data'=>$state]);
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
public function state_edit($id){
    $state = State::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'List Of State Details','state'=>$state]);
    }
public function country_based_state($country_id) {
    try {
        $states = State::where('country_id', $country_id)->where('status',1)
            ->get(['id', 'state_name']);

        if ($states->isEmpty()) {
            return response()->json(['status' => 222, 'message' => 'No state data found']);
        }

        return response()->json(['status' => 200, 'message' => 'State details', 'data' => $states]);
    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'message' => 'Internal Server Error']);
    }
}
public function state_status(Request $request){
    //dd('hi');
    $request->validate([
        'state_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $state = State::findOrFail($request->state_id);
    //dd($company);

    // Update the status of the company
    $state->status = $request->status;
    $state->save();
    if($state){
        return response()->json(['status' => 200, 'message' => 'State status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_state_status($id){
    $state = State::findOrFail($id);
    if($state){
        return response()->json(['status' => 200, 'message' => 'State status','data'=>$state->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
public function get_active_state(){
    $state = State::where('status',1)->get(['id','state_name']);
    if($state){
        return response()->json(['status' => 200, 'message' => 'State details','data'=>$state]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }
}



}
