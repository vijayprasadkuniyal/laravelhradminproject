<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;
use Validator;
use Illuminate\Support\Facades\Auth;
class CountryController extends Controller
{
   public function save_country(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'name' => 'required',
        'short_name' => 'required'
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $country = new Country();
      $country->name = $request->name;
      $country->short_name = $request->short_name;
      $country->save();
      if($country){
         return response()->json(['status'=>200,'message'=>'country Created Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }
      //return $this->sendResponse($country->only(['id','name','short_name']), 'country Created Successfully.');
 }
 public function country_list(){
    $country = Country::get(['id','name','short_name','status']);
    return response()->json(['status'=>200,'message'=>'List Of Countries','data'=>$country]);
  }
public function country_edit($id){
    $country = Country::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'List Of Country Details','country'=>$country]);
    }
  public function update_country(Request $request,$id){
    $input = $request->all();
    $validator = Validator::make($input, [
        'name' => 'required',
        'short_name' => 'required'
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $country = Country::findOrFail($id);
      $country->name = $request->name;
      $country->short_name = $request->short_name;
      $country->save();
      if($country){
         return response()->json(['status'=>200,'message'=>'country Updated Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }

}
public function country_status(Request $request){
    //dd('hi');
    $request->validate([
        'country_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $country = Country::findOrFail($request->country_id);
    //dd($company);

    // Update the status of the company
    $country->status = $request->status;
    $country->save();
    if($country){
        return response()->json(['status' => 200, 'message' => 'Country status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_country_status($id){
    $country = Country::findOrFail($id);
    if($country){
        return response()->json(['status' => 200, 'message' => 'Country status','data'=>$country->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
public function get_active_country(){
    $country = Country::where('status',1)->get(['id','name']);
     if($country){
        return response()->json(['status' => 200, 'message' => 'Country details','data'=>$country]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }
}






}
