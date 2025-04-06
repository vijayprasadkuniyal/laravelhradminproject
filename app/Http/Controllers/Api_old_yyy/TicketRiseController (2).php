<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TicketPriority;
use Validator;
use DB;

class TicketPriorityController extends Controller
{
    public function save_priority(Request $request){
      //return $request->all();
        $input = $request->all();
        $validator = Validator::make($input, [
            'attribute_name' => 'required',
            ]);
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
          $priority = new TicketPriority();
          $priority->attribute_name = $request->attribute_name;
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
        $priority = TicketPriority::get(['id','attribute_name','created_by','status']);
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
            ]);
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
          $priority = TicketPriority::findOrFail($id);
          $priority->attribute_name = $request->attribute_name;
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
    public function ticket_subattribute(){
        $data = TicketPriority::join('ticket_subattribute','ticket_subattribute.attribute_id','ticket_priority.id')
                ->select('ticket_priority.attribute_name','ticket_subattribute.id',
                'ticket_subattribute.name','ticket_subattribute.action_time','ticket_subattribute.completion_time',
                'ticket_subattribute.status')
                ->get();
        return response()->json(['status' =>200, 'data' =>$data]);

    }

    public function save_ticket_subattribute(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required',
            ]);
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
          $data = array('attribute_id'=>$request->attribute,'name'=>$request->name,
          'action_time'=>$request->action_time,'completion_time'=>$request->completion_time,
          'created_by'=>$request->emp_id);
        DB::table('ticket_subattribute')->insert($data);
        return response()->json(['status' =>200, 'message' =>'Subattribute Created Successfully']);
     
    }
   public function edit_ticket_subattribute($id){
    $data = DB::table('ticket_subattribute')->where('id',$id)->first();
    return response()->json(['status' =>200, 'data' =>$data]);

   }
 public function update_ticket_subattribute(Request $request,$id){
    $input = $request->all();
    $validator = Validator::make($input, [
        'name' => 'required',
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $data = array('attribute_id'=>$request->attribute,'name'=>$request->name,
      'action_time'=>$request->action_time,'completion_time'=>$request->completion_time,
      'created_by'=>$request->emp_id);
      DB::table('ticket_subattribute')->where('id',$id)->update($data);
      return response()->json(['status' =>200, 'message' =>'Subattribute Created Successfully']);

 }
 public function ticket_subattribute_status($id){
    $data = DB::table('ticket_subattribute')->where('id',$id)->first();
    return response()->json(['status' =>200, 'data' =>$data->status]);


 }
 public function ticket_subattribute_status_update(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'status' => 'required',
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      DB::table('ticket_subattribute')->where('id',$request->id)->update(['status'=>$request->status]);
      return response()->json(['status' =>200, 'message' =>'Status Updated Successfully']);

   }
   public function get_active_ticket_attribute(){
    $data = DB::table('ticket_priority')->where('status',1)->get(['id','attribute_name']);
    return response()->json(['status' =>200, 'data' =>$data]);

   }
    
}