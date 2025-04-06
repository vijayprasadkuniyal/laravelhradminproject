<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveType;
use Validator;
use App\Models\BasicInfo;
Use \Carbon\Carbon;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBlance;

class LeaveTypeController extends Controller
{
     public function create_leave_type(Request $request){
         $input = $request->all();
         $validator = Validator::make($input, [
           'leave' => 'required',
           'days'=>'required',
           'atAtime' =>'required',
           ]);
       if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $leave = new LeaveType();
      $leave->leave_name = $request->leave;
      $leave->leave_days = $request->days;
      $leave->at_a_time =  $request->atAtime;
      $leave->save();
      if($leave) {
            return response()->json(['status'=>200,'message' => 'Leave Created  Successfully']);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }

    }
    public function leave_type_list(){
        $leave = LeaveType::get(['id','leave_name','leave_days','at_a_time','status']);
        if($leave) {
            return response()->json(['status'=>200,'message' => 'Leave Details','data'=>$leave]);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }


    }
    public function leave_type_edit($id){
        $leave = LeaveType::findOrFail($id);
        if($leave) {
            return response()->json(['status'=>200,'message' => 'Leave Details','data'=>$leave]);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }

    }
    public function leave_type_update(Request $request,$id){
       $input = $request->all();
         $validator = Validator::make($input, [
           'leave' => 'required',
           'days'=>'required',
           'atAtime' =>'required',
           ]);
       if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $leave = LeaveType::findOrFail($id);
      $leave->leave_name = $request->leave;
      $leave->leave_days = $request->days;
      $leave->at_a_time =  $request->atAtime;
      $leave->save();
      if($leave) {
            return response()->json(['status'=>200,'message' => 'Leave Updated  Successfully']);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }

    }
    public function leave_type_status(Request $request){
    $request->validate([
        'leave_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);
    $leave = LeaveType::findOrFail($request->leave_id);
    $leave->status = $request->status;
    $leave->save();
    if($leave){
        return response()->json(['status' => 200, 'message' => 'Leave status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_leave_type_status($id){
    $leave = LeaveType::findOrFail($id);
    if($leave){
        return response()->json(['status' => 200, 'message' => 'Leave status','data'=>$leave->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
public function get_active_leavetype(){
    $leave_type = LeaveType::where('status',1)->where('leave_name','!=','probation period leave')->get(['id','leave_name','leave_days','at_a_time','status']);
    return response()->json(['status' => 200, 'message' => 'Leave details','data'=>$leave_type]);


}
public function get_active_confirmation_leave(){
    $leave_type = LeaveType::where('status',1)->where('leave_name','=','probation period leave')->get(['id','leave_name','leave_days','at_a_time','status']);
    return response()->json(['status' => 200, 'message' => 'Leave details','data'=>$leave_type]);


}
public function get_confirmation_status($id){
    $confirmtion = BasicInfo::where('emp_id',$id)->get(['is_confirmed']);
    return response()->json(['status' => 200, 'message' => 'confirmation status','data'=> $confirmtion]);

}
public function emp_leave_count(Request $request){
    $start_date = Carbon::createFromFormat('Y-m-d', $request->input('start_date'));
    $end_date = Carbon::createFromFormat('Y-m-d', $request->input('end_date'));
    $leave_id = $request->leave_id;
    $diffInDays = $end_date->diffInDays($start_date)+1;
    if($start_date<=$end_date){
        $leave_type = LeaveType::where('id',$leave_id)->first();
        $at_a_time = $leave_type->at_a_time;
        if($at_a_time>= $diffInDays){
            return response()->json(['status' => 200, 'message' => 'Leave details','data'=>$diffInDays]);


        }
        else{
            return response()->json(['status' => 500, 'message' => 'you can not apply more then '.$at_a_time,]);

        }
     }else{
        return response()->json(['status' => 500, 'message' => 'End Date should be grater then start date',]);
        

    }


    

}
public function save_emp_leave(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
       'leave_type' => 'required',
       'date_from'=>'required',
       'date_to'=>'required',
       'no_of_days'=>'required',
       'message'=>'required',
       ]);
   if($validator->fails()){
        $messages=$validator->messages();
        return response()->json(["messages"=>$messages,'status'=>400]);     
  }
  $leave = new EmployeeLeave();
  $leave->emp_id = $request->emp_id;
  $emp_information = BasicInfo::where('id',$request->emp_id)->first();
  $leave->date_from = $request->date_from;
  $leave->date_to = $request->date_to;
  $leave->message = $request->message;
  $leave->no_of_days = $request->no_of_days;
  $leave->leave_id = $request->leave_type;
  $leave->reporting_manager = $emp_information->reporting_manager;
  $leave_blance = EmployeeLeaveBlance::where('emp_id',$request->emp_id)->where('leave_id',$request->leave_type)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->first();
  $remaining_leave = $leave_blance->remaining_leave;
  if($remaining_leave>0){
    $last_time_leave = EmployeeLeave::orderBy('id','DESC')->where('emp_id',$request->emp_id)->where('leave_id',$request->leave_type)->where('status',1)->whereRaw('YEAR(created_at) = ?', [Carbon::now()->year])->whereRaw('Month(created_at) = ?', [Carbon::now()->month])->first();
    if($last_time_leave && $last_time_leave->leave_id!=1){
        return response()->json(['status'=>500,'message' => 'You Can not take this leave now',]);

    }
    else{
        $leave->save();
       return response()->json(['status'=>200,'message' => 'Leave Created  Successfully']);

    }
  }
  else{
    return response()->json([ 'status'=>500,'message' => 'You Dont Have Leave PLease try another']);

  }
}
public function get_leave_list($id){
    $leave_list = EmployeeLeave::join('create_leave_type','create_leave_type.id','=','employee_leave.leave_id')
                 ->select('create_leave_type.leave_name','employee_leave.id','employee_leave.date_from','employee_leave.date_to','employee_leave.message','employee_leave.no_of_days',
                 'employee_leave.status')
                ->where('emp_id',$id)
                ->get();
    if($leave_list){
        return response()->json(['status'=>200,'message' => 'Leave details','data'=>$leave_list]);

    }
    else{
        return response()->json(['status'=>500,'message' => 'Leave details','data'=>$leave_list]);

    }



}
public function assign_leave(Request $request,$id){
    $emp_details = BasicInfo::where('emp_id',$id)->first();
    $confirmation_date =  $emp_details->emp_doc;
    if($confirmation_date !=null){
      $confirmation_date = Carbon::createFromFormat('Y-m-d', $confirmation_date)->format('Y');
    }
    else{
      $confirmation_date = '';

    }
    //return $emp_details; 
    if($emp_details){
        if($emp_details->is_confirmed==0){
            $leaves = LeaveType::where('leave_name','probation period leave')->first();
            $emp_leave_blance = EmployeeLeaveBlance::where('leave_id',$leaves->id)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->first();
            if($emp_leave_blance){
              $remaining_leave_data = $emp_leave_blance->remaining_leave;

             }
             else{
              $remaining_leave_data = $leaves->leave_days;

             }

            $leavedata = EmployeeLeaveBlance::updateOrInsert(
                ['emp_id' =>$id,'year'=>Carbon::now()->format('Y')],
                [
                    'leave_id' =>  $leaves->id,
                    'no_of_leave' => $leaves->leave_days,
                    'emp_id' => $id,
                    //'total_leave_taking' =>0,
                    'remaining_leave'=>$remaining_leave_data,
                    'created_date' => Carbon::now()->format('Y-m-d'),
                    'at_a_time'=>$leaves->at_a_time,
                    'is_probation_leave'=>1,
                    'leave_status'=>$leaves->status,
                    'year'=>Carbon::now()->format('Y'),
                ]
            );
            if($leavedata) {
                return response()->json(['status'=>200,'message' => 'Leave Created Or Updated Successfully']);
            } else {
                return response()->json(['message' => 'Something went wrong'], 500);
            }
          }
         else if($confirmation_date==Carbon::now()->format('Y')){
            $data =  BasicInfo::where('emp_id',$id)->first();
            $confirmation_date = $data->emp_doc;
            $yearEnd = date('Y-m-d', strtotime('Dec 31'));
            $start_date = Carbon::createFromFormat('Y-m-d', $confirmation_date);
            $end_date = Carbon::createFromFormat('Y-m-d', $yearEnd);
            $diffInmonth =  $start_date->diffInMonths($end_date);
            $leave_data = LeaveType::where('leave_name','!=','probation period leave')->where('status',1)->get();
            foreach($leave_data as $row){
               $emp_leave_blance = EmployeeLeaveBlance::where('leave_id',$row->id)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->first();
               if($emp_leave_blance){
                 $remaining_leave_data = $emp_leave_blance->remaining_leave;

               }
               else{
                $remaining_leave_data = $row->leave_days/12*$diffInmonth;

               }

              $leaveBalanceData = EmployeeLeaveBlance::updateOrInsert(
                    [
                        'emp_id' => $id,
                        'leave_id' => $row->id,
                        'year' => Carbon::now()->format('Y'),
                    ],
                    [
                        'emp_id' => $id,
                        'leave_id' => $row->id,
                        'no_of_leave' => $row->leave_days,
                        'remaining_leave'=>$remaining_leave_data,
                        'at_a_time' => $row->at_a_time,
                        'leave_status'=>$row->status,
                        'year' => Carbon::now()->format('Y'),
                        'created_date' => Carbon::now()->format('Y-m-d'),
                    ]
                );
            }
             return response()->json(['status' => 200, 'message' => 'Leave Created Or Updated Successfully']);
          }
        else{
          $leave_data = LeaveType::where('leave_name','!=','probation period leave')->where('status',1)->get();
          foreach($leave_data as $row){
            $emp_leave_blance = EmployeeLeaveBlance::where('leave_id',$row->id)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->first();
               if($emp_leave_blance){
                 $remaining_leave_data = $emp_leave_blance->remaining_leave;

               }
               else{
                $remaining_leave_data = $row->leave_days;

               }
              $leaveBalanceData = EmployeeLeaveBlance::updateOrInsert(
                    [
                        'emp_id' => $id,
                        'leave_id' => $row->id,
                        'year' => Carbon::now()->format('Y'),
                    ],
                    [ 
                        'emp_id' => $id,
                        'leave_id' => $row->id,
                        'no_of_leave' => $row->leave_days,
                        'remaining_leave'=>$remaining_leave_data,
                        'at_a_time' => $row->at_a_time,
                        'leave_status'=>$row->status,
                        'year' => Carbon::now()->format('Y'),
                        'created_date' => Carbon::now()->format('Y-m-d'),
                    ]
                );
            }
            return response()->json(['status' => 200, 'message' => 'Leave Created Or Updated Successfully']);
            

        }

    }
    else{
        return response()->json(['status'=>500,'message' => 'Leave details','data'=> $emp_details]);

    }


}
public function emp_leave_data($id){
  $emp_status = BasicInfo::where('emp_id',$id)->first();
  if($emp_status->is_confirmed ==0){
    $leave_blance = EmployeeLeaveBlance::join('create_leave_type','create_leave_type.id','=','emp_leave_blance.leave_id')
      ->select('create_leave_type.leave_name','emp_leave_blance.*')
      ->where('emp_id',$id)
      ->where('is_probation_leave',1)
      ->where('leave_status',1)
      ->whereRaw('YEAR(emp_leave_blance.created_date) = ?', [Carbon::now()->year])
      ->get();


    return response()->json(['status'=>200,'message' => 'probation leave  details','data'=>$leave_blance]);
    }
  else{
    $leave_blance = EmployeeLeaveBlance::join('create_leave_type','create_leave_type.id','=','emp_leave_blance.leave_id')
      ->select('create_leave_type.leave_name','emp_leave_blance.*')
      ->where('emp_id',$id)
      ->where('is_probation_leave',0)
      ->where('leave_status',1)
      ->whereRaw('YEAR(emp_leave_blance.created_date) = ?', [Carbon::now()->year])
      ->get();
     return response()->json(['status'=>200,'message' => 'Confirmation leave  details','data'=>$leave_blance]);
  }

}
}