<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use Validator;

class ServiceController extends Controller
{
 public function save_service(Request $request){
     $input = $request->all();
     $validator = Validator::make($input, [
        'service_name' => 'required',
        'short_name'=>'required',
        'company'=>'required',
        'product'=>'required',
    ]);
        if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $service = new Service();
      $service->company_id = $request->company;
      $service->product_id = $request->product;
      $service->service_name = $request->service_name;
      $service->service_short_name   = $request->short_name;
      $service->save();
      if($service){
          return response()->json(['status'=>200,'message'=>'Service  Created Successfully']);
      }
      else{
         return response()->json(['status'=>500,'message'=>'something went wrong']);
      }
    }
 public function service_list(){
     $service = Service::join('company_details','company_details.id','=','service_details.company_id')
              ->join('product_details','product_details.id','=','service_details.product_id')
              ->select('company_details.business_name','product_details.product_name','service_details.id','service_details.service_name','service_details.service_short_name','service_details.status')
              ->get();
     return response()->json(['status'=>200,'message'=>'List Of Services','data'=>$service]);
  }
  public function update_service(Request $request,$id){
     $input = $request->all();
     $validator = Validator::make($input, [
        'service_name' => 'required',
        'short_name'=>'required',
        'company'=>'required',
        'product'=>'required',
    ]);
        if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $service = Service::findOrFail($id);
      $service->company_id = $request->company;
      $service->product_id = $request->product;
      $service->service_name = $request->service_name;
      $service->service_short_name   = $request->short_name;
      $service->save();
      if($service){
          return response()->json(['status'=>200,'message'=>'Service  Updated Successfully']);
      }
      else{
         return response()->json(['status'=>500,'message'=>'something went wrong']);
      }
  }
  public function service_edit($id){
    $service = Service::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'List Of Services','service'=>$service]);


}
public function service_status(Request $request){
    //dd('hi');
    $request->validate([
        'service_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    $service = Service::findOrFail($request->service_id);
    //dd($company);

    // Update the status of the company
    $service->status = $request->status;
    $service->save();
    if($service){
        return response()->json(['status' => 200, 'message' => 'Service  status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_service_status($id){
    $service = Service::findOrFail($id);
    if($service){
        return response()->json(['status' => 200, 'message' => 'Service  status','data'=>$service->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
}
