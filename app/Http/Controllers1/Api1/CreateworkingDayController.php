<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkingDay;
use Validator;

class CreateworkingDayController extends Controller
{
    public function create_working_day(Request $request){
        $input = $request->all();
       $validator = Validator::make($input, [
        'date' => 'required',
        'reason' => 'required'
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $working_day  = new WorkingDay();
      $working_day->date = $request->date;
      $working_day->reason = $request->reason;
      $working_day->save();
      if($working_day){
         return response()->json(['status'=>200,'message'=>' Working Day Created Successfully']);
     }
      else{
         return response()->json(['status'=>500,'message'=>'something went wrong']);

     }


    }
    public function working_day_list(){
        $working_day = WorkingDay::get(['id','date','reason','status','created_date']);
        return response()->json(['status'=>200,'message'=>'List Of Working Days','data'=>$working_day]);
      }
      public function working_day_edit($id){
        $working_day = WorkingDay::findOrFail($id);
        return response()->json(['status'=>200,'message'=>'Working Day Details','working_day'=>$working_day]);
    }

    public function update_working_day(Request $request,$id){
        $input = $request->all();
        $validator = Validator::make($input, [
            'date' => 'required',
            'reason' => 'required'
            ]);
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
          $working_day  = WorkingDay::findOrFail($id);
          $working_day->date = $request->date;
          $working_day->reason = $request->reason;
          $working_day->save();
          if($working_day){
             return response()->json(['status'=>200,'message'=>'Working Day Updated Successfully']);
         }
         else{
            return response()->json(['status'=>500,'message'=>'something went wrong']);
    
         }
    
    }
    public function working_day_status(Request $request){
        //dd('hi');
        $request->validate([
            'working_day_id' => 'required',
            'status' => 'required|in:0,1', 
        ]);
    
        $working_day = WorkingDay::findOrFail($request->working_day_id);
        $working_day->status = $request->status;
        $working_day->save();
        if($working_day){
            return response()->json(['status' => 200, 'message' => 'Working Day status updated successfully']);
    
        }
        else{
            return response()->json(['status' => 500, 'message' => 'something went wrong']);
    
        }
    
    
    }
    public function get_working_day_status($id){
        $working_day = WorkingDay::findOrFail($id);
        if($working_day){
            return response()->json(['status' => 200, 'message' => 'Working Day status','data'=>$working_day->status]);
    
        }
        else{
            return response()->json(['status' =>500, 'message' => 'data not found']);
    
        }
    
    
    }
    public function show_working_days(){
        $show_working_days = WorkingDay::where('status',1)->get(['date','reason']);
        return response()->json(['status' =>200, 'message' => 'list of working days','data'=> $show_working_days]);
      }
}
