<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TicketPriority;
use Validator;

class TicketPriorityController extends Controller
{
    public function save_priority(Request $request){
      //return $request->all();
        $input = $request->all();
        $validator = Validator::make($input, [
            'attribute_name' => 'required',
            'product' => 'required',
            'priority' => 'required',
            'required_time' => 'required',
            ]);
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
          $priority = new TicketPriority();
          $priority->attribute_name = $request->attribute_name;
          $priority->product = $request->product;
          $priority->priority = $request->priority;
          $priority->required_time = $request->required_time;
          $priority->created_by = $request->emp_id;
          $priority->save();
          if($priority){
             return response()->json(['status'=>200,'message'=>'Priority Created Successfully']);
         }
         else{
            return response()->json(['status'=>500,'message'=>'something went wrong']);
    
         }
          //return $this->sendResponse($country->only(['id','name','short_name']), 'country Created Successfully.');
     }
     public function priority_list(){
        $priority = TicketPriority::get(['id','attribute_name','product','priority','required_time','created_by','status']);
        return response()->json(['status'=>200,'message'=>'List Of Priority','data'=>$priority]);
      }
    public function priority_edit($id){
        $priority = TicketPriority::findOrFail($id);
        return response()->json(['status'=>200,'message'=>'List Of priority Details','data'=>$priority]);
        }
      public function update_priority(Request $request,$id){
        $input = $request->all();
        $validator = Validator::make($input, [
            'attribute_name' => 'required',
            'product' => 'required',
            'priority' => 'required',
            'required_time' => 'required',
            ]);
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
          $priority = TicketPriority::findOrFail($id);
          $priority->attribute_name = $request->attribute_name;
          $priority->product = $request->product;
          $priority->priority = $request->priority;
          $priority->required_time = $request->required_time;
          $priority->created_by = $request->emp_id;
          $priority->save();
          if($priority){
             return response()->json(['status'=>200,'message'=>'Priority Updated Successfully']);
         }
         else{
            return response()->json(['status'=>500,'message'=>'something went wrong']);
    
         }
    
    }
    public function priority_status(Request $request){
        //dd('hi');
        $request->validate([
            'priority_id' => 'required',
            'status' => 'required|in:0,1', 
        ]);
    
        // Find the company by ID
        $priority = TicketPriority::findOrFail($request->priority_id);
        //dd($company);
    
        // Update the status of the company
        $priority->status = $request->status;
        $priority->save();
        if($priority){
            return response()->json(['status' => 200, 'message' => 'priority status updated successfully']);
    
        }
        else{
            return response()->json(['status' => 500, 'message' => 'something went wrong']);
    
        }
    
    
    }
    public function get_priority_status($id){
        $priority = TicketPriority::findOrFail($id);
        if($priority){
            return response()->json(['status' => 200, 'message' => 'Priority status','data'=>$priority->status]);
    
        }
        else{
            return response()->json(['status' =>500, 'message' => 'data not found']);
    
        }
    
    
    }
    
}