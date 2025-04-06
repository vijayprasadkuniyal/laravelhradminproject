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
use App\Models\Attendance;
use App\Models\Roster;
use DB;
use App\Events\LeaveEvent;
use Carbon\CarbonPeriod;

class LeaveTypeController extends Controller
{
     public function create_leave_type(Request $request){
     // return $request->all();
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
      $leave->assign_when_request = $request->is_checked;
      $leave->is_probation_leave = $request->probation_leave;

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
      $leave->assign_when_request = $request->is_checked;
      $leave->is_probation_leave = $request->probation_leave;
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
public function get_active_leavetype($id){
  //return 'jj';
    $leave_type = EmployeeLeaveBlance::join('create_leave_type', 'create_leave_type.id', '=', 'emp_leave_blance.leave_id')
    ->select('create_leave_type.leave_name', 'create_leave_type.id')
    ->where('emp_id', $id)
    ->whereIn('is_request_leave', [0, 1])
    ->where('emp_leave_blance.is_probation_leave', 0)
    ->where('year', Carbon::now()->year)
    ->distinct()
    ->get();


    return response()->json(['status' => 200, 'message' => 'Leave details','data'=>$leave_type]);


}
public function get_active_confirmation_leave(){
    $leave_type = LeaveType::where('is_probation_leave',1)->where('assign_when_request',0)->where('status',1)
               ->get();
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
            return response()->json(['status' => 500, 'message' => 'you can not apply more than '.$at_a_time,]);

        }
     }else{
        return response()->json(['status' => 500, 'message' => 'End Date should be grater than start date',]);
        

    }


    

}
// public function save_emp_leave(Request $request){
//     //return request()->all();

//     $input = $request->all();
//     $validator = Validator::make($input, [
//        'leave_type' => 'required',
//        'date_from'=>'required',
//        'date_to'=>'required',
//        'no_of_days'=>'required',
//        'message'=>'required',
//        ]);
//    if($validator->fails()){
//         $messages=$validator->messages();
//         return response()->json(["messages"=>$messages,'status'=>400]);     
//   }
//   $leave = new EmployeeLeave();
//   $leave->emp_id = $request->emp_id;
//   $emp_information = BasicInfo::where('emp_id',$request->emp_id)->first();
//   $leave->date_from = $request->date_from;
//   $leave->date_to = $request->date_to;
//   $start_date = Carbon::parse($request->date_from);
//   $end_date = Carbon::parse($request->date_to);
//   $roster = Roster::where('emp_id',$request->emp_id)->exists();
//   $mondayDate = null;
//     for ($date = $start_date->copy()->startOfWeek(); $date->lte($end_date); $date->addWeek()) {
//         if ($date->between($start_date, $end_date)) {
//             $mondayDate = $date->copy();
//             break;
//         }
//     }
//   if($mondayDate && !$roster){
//     //return 'kk';
//     $previousFridayDate = $mondayDate->copy()->previous(Carbon::FRIDAY)->toDateString();
//     $check_attendance = Attendance::where('emp_id',$request->emp_id)->where('attendance_date',$previousFridayDate)->first();
//    // return $check_attendance;
//     if($check_attendance && ($check_attendance->leave_status==1 || $check_attendance->status ==0 )){
//          return response()->json(['status'=>500,'message' => 'You Can not take this leave Sandwich rule',]); 

//      }
//    }
//    if(!$mondayDate && !$roster){
//     $previousMondayDate = $start_date->copy()->previous(Carbon::MONDAY)->toDateString();
//      $previousMondayDate = Carbon::parse($previousMondayDate);
//      $previousFridayDate = $previousMondayDate->copy()->previous(Carbon::FRIDAY)->toDateString();
//      $check_monday_attendance = Attendance::where('emp_id',$request->emp_id)->where('attendance_date',$previousMondayDate)->first();
//       $check_friday_attendance = Attendance::where('emp_id',$request->emp_id)->where('attendance_date',$previousFridayDate)->first();
//    // return $check_attendance;
//     if($check_monday_attendance && ($check_monday_attendance->leave_status==1 || $check_monday_attendance->status ==0)  &&$check_friday_attendance && ($check_friday_attendance->leave_status==1 || $check_friday_attendance->status ==0)   ){
//          return response()->json(['status'=>500,'message' => 'You Can not take this leave Sandwich rule',]);

//      }
//    }

//   $leave->message = $request->message;
//   $leave->no_of_days = $request->no_of_days;
//   $leave->leave_id = $request->leave_type;
//   //$leave->reporting_manager = $emp_information->reporting_manager;
//   $leave_blance = EmployeeLeaveBlance::where('emp_id',$request->emp_id)->where('leave_id',$request->leave_type)
//   ->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->first();
//   $remaining_leave = $leave_blance->remaining_leave;
//   if($remaining_leave>=$request->no_of_days){
//     $last_time_leave = EmployeeLeave::orderBy('id','DESC')->where('emp_id',$request->emp_id)
//     ->where('leave_id',$request->leave_type)->where('status',1)
//     ->whereRaw('YEAR(created_at) = ?', [Carbon::now()->year])->whereRaw('Month(created_at) = ?', [Carbon::now()->month])->first();
//     if($last_time_leave && $last_time_leave->leave_id!=1){
//         return response()->json(['status'=>500,'message' => 'You Can not take this leave now',]);

//     }
//     else{
//         if($request->date_from<=Carbon::now() && $request->leave_type==1 ){
//             return response()->json(['status'=>500,'message' => 'You Can not take PL Same Day']);
//          }
//         else{
//             $leave->save();

//         }
//         //EmployeeLeaveBlance::where('leave_id',$request->leave_type)->update(['remaining_leave'])
//        return response()->json(['status'=>200,'message' => 'Leave Created  Successfully']);

//     }
//   }
//   else{
//     return response()->json([ 'status'=>500,'message' => 'You Dont Have Leave PLease try another']);

//   }
// }

// public function save_emp_leave(Request $request){
//       $input = $request->all();
//       $validator = Validator::make($input, [
//        'leave_type' => 'required',
//        'date_from'=>'required',
//        'date_to'=>'required',
//        'no_of_days'=>'required',
//        'message'=>'required',
//        ]);
//    if($validator->fails()){
//         $messages=$validator->messages();
//         return response()->json(["messages"=>$messages,'status'=>400]);     
//   }

//   $leave = new EmployeeLeave();
//   $leave->emp_id = $request->emp_id;
//   $leave->date_from = $request->date_from;
//   $leave->date_to = $request->date_to;
//   $leave->message = $request->message;
//   $leave->no_of_days = $request->no_of_days;
//   $leave->is_half_day = $request->is_half_day;
//   $leave->leave_id = $request->leave_type;
//   $check_leave_type = LeaveType::where('id',$request->leave_type)->first();
//   if($check_leave_type &&  $check_leave_type->is_assign_quartly==0){
//      $remaining_leave = EmployeeLeaveBlance::where('emp_id',$request->emp_id)->where('leave_id',$request->leave_type)
//       ->where('year',Carbon::now()->year)->first();
//       if($remaining_leave->remaining>0 && $remaining_leave->remaining>=$request->no_of_days){
//         //$check_current_month_leave = EmployeeLeave::where('id',$request->leave_type)->whereRaw('')
//        if($check_leave_type->is_request_leave==0){
//         $startDate = Carbon::parse($request->date_from);
//         $endDate = Carbon::parse($request->date_to);
//          $dates = [];
//         for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
//            $dates[] = $date->format('Y-m-d');
//           }
//         foreach($dates as $date_list){
//          if(Carbon::parse($date_list)->format('l') =='Monday'){
//            $lastFriday = Carbon::parse($date_list)->previous(Carbon::FRIDAY);
//            $attendance = Attendance::where('attendance_date', $lastFriday)->where('emp_id',$request->emp_id)->first();
//            if($attendance && $attendance->leave_status ==1){
//              $total_days  = [];
//            for ($dates = $lastFriday->copy()->addDay(); $dates->lt($date); $dates->addDay()) {
//                 $total_days[] = $dates->format('Y-m-d');
//             }
//             foreach($total_days as $row){
//               $attendance_row = Attendance::where('attendance_date',$row)->where('emp_id',$request->emp_id)->first();
//               if($attendance_row ){
//                 Attendance::where('attendance_date',$row)->where('emp_id',$request->emp_id)->update(['is_sandwich_leave'=>1]);


//               }
//               else{
//                 $data = array('emp_id'=>$request->emp_id,'attendance_date'=>$row,'weekday'=>Carbon::parse($row)->format('l') );
//                 Attendance::insert($data);

//               }
//               }

//               return response()->json(["message"=>'You Cant Apply Sandwich Rule Your 3 Days will Be Lop ']);


//            }

//           }

//         }
//        }
//         $leave->save();
//          EmployeeLeaveBlance::where('leave_id',$request->leave_type)->where('emp_id',$request->emp_id)
//         ->where('year',Carbon::now()->year)->update(['remaining'=> $remaining_leave->remaining - $request->no_of_days,
//        'leave_taken'=>$remaining_leave->leave_taken+$request->no_of_days]);
//         return response()->json(["message"=>'Leave Apply Successfully','status'=>200]);

//         }
//         else{
//           return response()->json(["message"=>'You Dont Have Sufficient Leave']);

//         }
//       }
//   else{
//      $apply_leave_quarter = Carbon::parse($request->date_to)->quarter;
//      $check_leave_exists = EmployeeLeaveBlance::where('emp_id',$request->emp_id)->where('leave_id',$request->leave_type)->where('quarter_no',$apply_leave_quarter)
//                     ->where('year',Carbon::now()->year)->first();
//     if( $check_leave_exists){
//       $remaining_leave = EmployeeLeaveBlance::where('emp_id',$request->emp_id)->where('leave_id',$request->leave_type)->where('quarter_no',Carbon::now()->quarter)
//                     ->where('year',Carbon::now()->year)->first();
//     if($remaining_leave->remaining>0 && $remaining_leave->remaining>=$request->no_of_days){
//       $at_a_time = LeaveType::where('id',$request->leave_type)->first();
//       $check_current_month_leave = EmployeeLeave::where('leave_id', $request->leave_type)
//        ->where('emp_id',$request->emp_id)
//        ->whereMonth('date_to', Carbon::now()->month)
//        ->whereYear('date_to', Carbon::now()->year)
//        ->sum('no_of_days');

//        if($check_current_month_leave>=$at_a_time->at_a_time){
//          return response()->json(["message"=>'Already take in this month']);

//        }

//        $startDate = Carbon::parse($request->date_from);
//         $endDate = Carbon::parse($request->date_to);
//          $dates = [];
//         for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
//            $dates[] = $date->format('Y-m-d');
//           }
//         foreach($dates as $date_list){
//          if(Carbon::parse($date_list)->format('l') =='Monday'){
//            $lastFriday = Carbon::parse($date_list)->previous(Carbon::FRIDAY);
//            $attendance = Attendance::where('attendance_date', $lastFriday)->where('emp_id',$request->emp_id)->first();
//            if($attendance && $attendance->leave_status ==1){
//              $total_days  = [];
//            for ($dates = $lastFriday->copy()->addDay(); $dates->lt($date); $dates->addDay()) {
//                 $total_days[] = $dates->format('Y-m-d');
//             }
//             foreach($total_days as $row){
//               $attendance_row = Attendance::where('attendance_date',$row)->where('emp_id',$request->emp_id)->first();
//               if($attendance_row ){
//                 Attendance::where('attendance_date',$row)->where('emp_id',$request->emp_id)->update(['is_sandwich_leave'=>1]);


//               }
//               else{
//                 $data = array('emp_id'=>$request->emp_id,'attendance_date'=>$row,'weekday'=>Carbon::parse($row)->format('l') );
//                 Attendance::insert($data);

//               }
//               }

//               return response()->json(["message"=>'You Cant Apply Sandwich Rule Your 3 Days will Be Lop ']);


//            }

//           }

//         }
//      $leave->save();
//      EmployeeLeaveBlance::where('leave_id',$request->leave_type)->where('emp_id',$request->emp_id)
//      ->where('quarter_no',Carbon::now()->quarter)
//      ->where('year',Carbon::now()->year)->update(['remaining'=> $remaining_leave->remaining - $request->no_of_days,
//      'leave_taken'=>$remaining_leave->leave_taken+$request->no_of_days]);
//      $emp_name = BasicInfo::where('emp_id',$request->emp_id)->first();
//      $message = $emp_name->emp_fname.' '.'Send Leave Request Please Click TO View';
//      $manager_id = DB::table('employee_managers')->where('emp_id',$request->emp_id)->where('status',1)->first();

//     $notification_data = array('emp_id'=>$manager_id->dept_manager,'notification'=>$message,'type'=>'leave','action_by'=>$emp_name->emp_fname);
//     //return $notification_data;
//     DB::table('emp_notifications')->insert($notification_data);
//     $type = 'leave';

//     event(new LeaveEvent($message,$manager_id->dept_manager,$type));

//      return response()->json(["message"=>'Leave Apply Successfully','status'=>200]);

//     }
//     else{
//         return response()->json(["message"=>'You Dont Have Sufficient Leave']);

//      }
//    }
//    else{
//     return response()->json(["message"=>'Leave Not Avaliable for  This  Quarter Try Another Leave']);

//    }

//   }
//  }
public function get_leave_list(Request $request){

    $check_data = DB::table('emp_notifications')->where('emp_id',$request->id)->where('type','leave_status')->where('seen_status',0)->exists();
            if($check_data){
                DB::table('emp_notifications')->where('emp_id',$request->id)->where('type','leave_status')->where('seen_status',0)->update(['seen_status'=>1]);

            }

    $leave_list = EmployeeLeave::join('create_leave_type','create_leave_type.id','=','employee_leave.leave_id')
                 ->select('create_leave_type.leave_name','employee_leave.id','employee_leave.date_from','employee_leave.date_to','employee_leave.message','employee_leave.no_of_days',
                 'employee_leave.status','employee_leave.rejection_reason')
                ->where('emp_id',$request->id)
                ->orderBy('employee_leave.id','DESC')
                ->paginate($request->per_page);
    if($leave_list){
        return response()->json(['status'=>200,'message' => 'Leave details','data'=>$leave_list,'last_page'=>$leave_list->lastPage()]);

    }
    else{
        return response()->json(['status'=>500,'message' => 'Leave details','data'=>$leave_list]);

    }



}
// public function assign_leave(Request $request,$id){
    
//     $emp_details = BasicInfo::where('emp_id',$id)->first();
//     $confirmation_date =  $emp_details->emp_doc;
//     if($confirmation_date !=null){
//       $confirmation_date = Carbon::createFromFormat('Y-m-d', $confirmation_date)->format('Y');
//     }
//     else{
//       $confirmation_date = '';

//     }
//     //return $emp_details; 
//     if($emp_details){
//         if($emp_details->is_confirmed==0){
//             $leaves = LeaveType::where('leave_name','probation period leave')->first();
//             $emp_leave_blance = EmployeeLeaveBlance::where('leave_id',$leaves->id)
//             ->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->first();
//             if($emp_leave_blance){
//               $remaining_leave_data = $emp_leave_blance->remaining_leave;

//              }
//              else{
//               $remaining_leave_data = $leaves->leave_days;

//              }

//             $leavedata = EmployeeLeaveBlance::updateOrInsert(
//                 ['emp_id' =>$id,'year'=>Carbon::now()->format('Y'),'leave_id'=>$leaves->id],
//                 [
//                     'leave_id' =>  $leaves->id,
//                     'no_of_leave' => $leaves->leave_days,
//                     'emp_id' => $id,
//                     //'total_leave_taking' =>0,
//                     'remaining_leave'=>$remaining_leave_data,
//                     'created_date' => Carbon::now()->format('Y-m-d'),
//                     'at_a_time'=>$leaves->at_a_time,
//                     'is_probation_leave'=>1,
//                     'leave_status'=>$leaves->status,
//                     'assign_on_request'=>$leaves->assign_when_request,
//                     'year'=>Carbon::now()->format('Y'),
//                 ]
//             );
//             if($leavedata) {
//                 return response()->json(['status'=>200,'message' => 'Leave Created Or Updated Successfully']);
//             } else {
//                 return response()->json(['message' => 'Something went wrong'], 500);
//             }
//           }
//          else if($confirmation_date==Carbon::now()->format('Y')){
//             $data =  BasicInfo::where('emp_id',$id)->first();
//             $confirmation_date = $data->emp_doc;
//             $yearEnd = date('Y-m-d', strtotime('Dec 31'));
//             $start_date = Carbon::createFromFormat('Y-m-d', $confirmation_date);
//             $end_date = Carbon::createFromFormat('Y-m-d', $yearEnd);
//             $diffInmonth =  $start_date->diffInMonths($end_date);
//             $leave_data = LeaveType::where('leave_name','!=','probation period leave')->where('status',1)->get();
//             foreach($leave_data as $row){
//                $emp_leave_blance = EmployeeLeaveBlance::where('leave_id',$row->id)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->first();
//                if($emp_leave_blance){
//                 $remaining_leave_data = $emp_leave_blance->remaining_leave;
//                 $no_of_leave =  $emp_leave_blance->no_of_leave;

//                }
//                else{
//                 $remaining_leave_data = $row->leave_days/12*$diffInmonth;
//                 $no_of_leave = $row->leave_days/12*$diffInmonth;

//                }

//               $leaveBalanceData = EmployeeLeaveBlance::updateOrInsert(
//                     [
//                         'emp_id' => $id,
//                         'leave_id' => $row->id,
//                         'year' => Carbon::now()->format('Y'),
//                     ],
//                     [
//                         'emp_id' => $id,
//                         'leave_id' => $row->id,
//                         'no_of_leave' => $no_of_leave,
//                         'remaining_leave'=>$remaining_leave_data,
//                         'at_a_time' => $row->at_a_time,
//                         'leave_status'=>$row->status,
//                         'assign_on_request'=>$row->assign_when_request,
//                         'year' => Carbon::now()->format('Y'),
//                         'created_date' => Carbon::now()->format('Y-m-d'),
//                     ]
//                 );
//             }
//             $check_last_year_pl = EmployeeLeaveBlance::where('leave_id',1)
//              ->where('emp_id', $id)
//              ->whereRaw('YEAR(created_date) = ?', [Carbon::now()->subYear()->year])
//             ->first();
//               $emp_pl_leave =  EmployeeLeaveBlance::where('leave_id',1)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->first();

//             if($check_last_year_pl && $check_last_year_pl->remaining_leave>0 && $emp_pl_leave->last_year_forward_leave!= $check_last_year_pl->remaining_leave ){
//               EmployeeLeaveBlance::where('leave_id',1)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->update(['last_year_forward_leave'=>$check_last_year_pl->remaining_leave,]);
//               $emp_pl_leave =  EmployeeLeaveBlance::where('leave_id',1)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->first();
//                  EmployeeLeaveBlance::where('leave_id',1)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->update(['no_of_leave'=>$emp_pl_leave->no_of_leave+$emp_pl_leave->last_year_forward_leave,
//                  'remaining_leave'=>$emp_pl_leave->remaining_leave+$emp_pl_leave->last_year_forward_leave]);


//             }
//              return response()->json(['status' => 200, 'message' => 'Leave Created Or Updated Successfully']);
//           }
//         else{
//           $leave_data = LeaveType::where('leave_name','!=','probation period leave')->where('status',1)->get();
//           foreach($leave_data as $row){
//             $emp_leave_blance = EmployeeLeaveBlance::where('leave_id',$row->id)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->first();
//                if($emp_leave_blance){
//                  $remaining_leave_data = $emp_leave_blance->remaining_leave;
//                  $no_of_days = $emp_leave_blance->no_of_leave;

//                }
//                else{
//                 $remaining_leave_data = $row->leave_days;
//                  $no_of_days = $row->leave_days;


//                }
//               $leaveBalanceData = EmployeeLeaveBlance::updateOrInsert(
//                     [
//                         'emp_id' => $id,
//                         'leave_id' => $row->id,
//                         'year' => Carbon::now()->format('Y'),
//                     ],
//                     [ 
//                         'emp_id' => $id,
//                         'leave_id' => $row->id,
//                         'no_of_leave' => $no_of_days,
//                         'remaining_leave'=>$remaining_leave_data,
//                         'at_a_time' => $row->at_a_time,
//                         'leave_status'=>$row->status,
//                         'assign_on_request'=>$row->assign_when_request,
//                         'year' => Carbon::now()->format('Y'),
//                         'created_date' => Carbon::now()->format('Y-m-d'),
//                     ]
//                 );
//             }
//              $check_last_year_pl = EmployeeLeaveBlance::where('leave_id',1)
//              ->where('emp_id', $id)
//              ->whereRaw('YEAR(created_date) = ?', [Carbon::now()->subYear()->year])
//             ->first();
//               $emp_pl_leave =  EmployeeLeaveBlance::where('leave_id',1)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->first();

//             if($check_last_year_pl && $check_last_year_pl->remaining_leave>0 && $emp_pl_leave->last_year_forward_leave!= $check_last_year_pl->remaining_leave ){
//               EmployeeLeaveBlance::where('leave_id',1)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->update(['last_year_forward_leave'=>$check_last_year_pl->remaining_leave,]);
//               $emp_pl_leave =  EmployeeLeaveBlance::where('leave_id',1)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->first();
//                  EmployeeLeaveBlance::where('leave_id',1)->where('emp_id',$id)->whereRaw('YEAR(created_date) = ?', [Carbon::now()->year])->update(['no_of_leave'=>$emp_pl_leave->no_of_leave+$emp_pl_leave->last_year_forward_leave,
//                  'remaining_leave'=>$emp_pl_leave->remaining_leave+$emp_pl_leave->last_year_forward_leave]);


//             }
//             return response()->json(['status' => 200, 'message' => 'Leave Created Or Updated Successfully']);
            

//         }

//     }
//     else{
//         return response()->json(['status'=>500,'message' => 'Leave details','data'=> $emp_details]);

//     }


// }
public function emp_leave_data($id){
  $emp_status = BasicInfo::where('emp_id',$id)->first();
  if($emp_status->is_confirmed ==0){
    $leave_blance = EmployeeLeaveBlance::join('create_leave_type','create_leave_type.id','=','emp_leave_blance.leave_id')
      ->select('create_leave_type.leave_name','emp_leave_blance.leave_id','emp_leave_blance.total_leave','emp_leave_blance.leave_taken','emp_leave_blance.remaining','emp_leave_blance.previous_year_forward_leave','emp_leave_blance.current_year_assign_leave')
      ->where('emp_id',$id)
      ->where('emp_leave_blance.is_probation_leave',1)
      ->where('is_request_leave',0)
      ->where('create_leave_type.status',1)
      ->where('year',Carbon::now()->year)
      ->distinct()
      ->get();


    return response()->json(['status'=>200,'message' => 'probation leave  details','data'=>$leave_blance]);
    }
  else{
     $leave_blance = EmployeeLeaveBlance::join('create_leave_type','create_leave_type.id','=','emp_leave_blance.leave_id')
      ->select('create_leave_type.leave_name','emp_leave_blance.leave_id','emp_leave_blance.total_leave','emp_leave_blance.leave_taken','emp_leave_blance.remaining','emp_leave_blance.previous_year_forward_leave','emp_leave_blance.current_year_assign_leave')
      ->where('emp_id',$id)
      ->where('emp_leave_blance.is_probation_leave',0)
      ->whereIn('is_request_leave',[0,1])
      ->where('create_leave_type.status',1)
      ->where('year',Carbon::now()->year)
      ->distinct()
      ->get();

     return response()->json(['status'=>200,'message' => 'Confirmation leave  details','data'=>$leave_blance]);
  }

}
public function emp_leave_taken_list($id) {
    $leave_list = EmployeeLeave::join('create_leave_type', 'create_leave_type.id', 'employee_leave.leave_id')
        ->select('create_leave_type.leave_name', 'employee_leave.*')
        ->where('emp_id', $id)
        ->where('employee_leave.status',1)
        ->where('employee_leave.is_half_day',0)
        ->get();

    $result = [];

    foreach ($leave_list as $row) {
        $status = ($row->status == 1) ? 'Accept' : (($row->status == 2) ? 'Reject' : 'Pending');

        $startDate = Carbon::parse($row->date_from);
        $endDate = Carbon::parse($row->date_to);
        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $result[] = [
                'id' => $row->id,
                'name' => $row->leave_name,
                'date' => $date->toDateString(),
                'status' => $status,
            ];
        }
    }

    return response()->json(['status' => '200', 'leave_entries' => $result]);
}
public function leave_permission($id){
   $leave_blance = LeaveType::where('assign_when_request',1)->where('is_probation_leave',0)->where('is_assign_quartly',0)->where('status',1)->get();
   foreach($leave_blance as $row){
    $leave_request = EmployeeLeaveBlance::where('emp_id',$id)->where('leave_id',$row->id)->first();
    if($leave_request){
        $data[] = array('id'=>$row->id,'leave_name'=>$row->leave_name,'assign_status'=>1);
    }
    else{
        $data[] = array('id'=>$row->id,'leave_name'=>$row->leave_name,'assign_status'=>0);

    }
   }
    return response()->json(['status'=>200,'message' => 'leave  details','data'=>$data]);
  }
  public function update_leave_permission(Request $request){
    //return 'kkk';
   // $firstOfQuarter = Carbon::now()->firstOfQuarter()->format('Y-m-d');
   // $endOfQuarter = Carbon::now()->endOfQuarter()->format('Y-m-d');

    $leave = LeaveType::where('id',$request->leave_id)->first();
    //return $leave;
    $data = array('leave_id'=>$leave->id,
    'year'=>Carbon::now()->year,'emp_id'=>$request->emp_id,
    'is_request_leave'=>1,'total_leave'=>$leave->leave_days,
    'leave_taken'=>0,'remaining'=>$leave->leave_days);
    EmployeeLeaveBlance::insert($data);
    return response()->json(['status'=>200,'message'=>'Status Updted Successfully']);

  }

  public function assign_leaveeee(){
    $data_array = [];
    $firstOfQuarter = Carbon::now()->firstOfQuarter()->format('Y-m-d');
    $endOfQuarter = Carbon::now()->endOfQuarter()->format('Y-m-d');
    $curr_date = Carbon::now()->format('Y-m-d');
    $quarter =  Carbon::now()->quarter; 
    $previousQuarter = Carbon::now()->subQuarter()->quarter;
    if($firstOfQuarter != $curr_date){
     $emp_details = BasicInfo::where('emp_status',1)->get();
     foreach($emp_details as $row){
        if($row->is_confirmed==0){
         //return $previousQuarter;
         $probation_leave = LeaveType::where('is_probation_leave',1)->where('status',1)->get();
         foreach($probation_leave as $leave){
            $previous_month_leave = DB::table('emp_leave_blance')
             ->where('quarter_no',$previousQuarter)->where('year',Carbon::now()->year)
             ->where('leave_id',$leave->id)
             ->where('emp_id',$row->emp_id)->first();
             if($previous_month_leave){
                //return 'y';
                $leave_blance =  $previous_month_leave->remaining;
             }
             else{
                //return 'else';
                $leave_blance = 0;

             }
             $leave_blance = $leave->leave_days/4;

            $data_array = array('emp_id'=>$row->emp_id,'leave_id'=>$leave->id,
            'quarter_no'=>$quarter,'quarter_start_from'=>$curr_date,'quarter_end'=>$endOfQuarter,
            'year'=>Carbon::now()->year,'total_leave'=>$leave_blance,'leave_taken'=>0,'remaining'=>$leave_blance,'is_probation_leave'=>1);
            EmployeeLeaveBlance::insert($data_array);
          }
        
        }
        else{
          $yearly_leave_days = [];
          $yearly_assign_leave = LeaveType::where('is_probation_leave',0)->where('assign_when_request',0)->where('is_assign_quartly',0)
          ->where('status',1)->get();
          foreach($yearly_assign_leave as $year){
            $check_exists = EmployeeLeaveBlance::where('leave_id',$year->id)->
            where('emp_id',$row->emp_id)->where('year',Carbon::now()->year)->first();
            if(!$check_exists){
              $yearly_leave_days[] = array('emp_id'=>$row->emp_id,'leave_id'=>$year->id,
              'year'=>Carbon::now()->year,'total_leave'=>$year->leave_days,'leave_taken'=>0,
              'remaining'=>$year->leave_days,'is_assign_quartly'=>0);

            }

          }
          EmployeeLeaveBlance::insert($yearly_leave_days);


          $confirm_leave = LeaveType::where('is_probation_leave',0)->where('assign_when_request',0)->where('is_assign_quartly',1)
          ->where('status',1)->get();
          foreach($confirm_leave as $data){
             $curr_year_leave = DB::table('emp_leave_blance')
              ->where('year',Carbon::now()->year)
             ->where('leave_id',$data->id)
             ->where('emp_id',$row->emp_id)->first();
            if($curr_year_leave){
               $previous_month_leave = DB::table('emp_leave_blance')
               ->where('quarter_no',$previousQuarter)->where('year',Carbon::now()->year)
               ->where('leave_id',$data->id)
               ->where('emp_id',$row->emp_id)->first();
               if($previous_month_leave){
                  $leave_blance = $data->leave_days/4 + $previous_month_leave->remaining;

               }
               else{
                 $leave_blance = $data->leave_days/4;

               }
            }
            else{
              //return 'lll';
              $currentYear = now()->year;
              $previousYear = $currentYear - 1;
             // return  $previousYear;
              $previous_year_pl = EmployeeLeaveBlance::where('year',$previousYear)
              ->where('emp_id',$row->emp_id)->where('leave_id',1)->orderBy('id','DESC')->first();
             // return  $previous_year_pl;
              if($previous_year_pl){
                $pl = $previous_year_pl->remaining;
                if($data->id ==1){
                  $leave_blance = $data->leave_days/4 + $pl/2;
                }
                else{
                  $leave_blance = $data->leave_days/4;

                }


              }

              else{
                $leave_blance = $data->leave_days/4;

              }

              }
            $data_array = array('emp_id'=>$row->emp_id,'leave_id'=>$data->id,
            'quarter_no'=>$quarter,'quarter_start_from'=>$curr_date,'quarter_end'=>$endOfQuarter,
            'year'=>Carbon::now()->year,'total_leave'=>$leave_blance,'leave_taken'=>0,'remaining'=>$leave_blance,'is_assign_quartly'=>1);
            EmployeeLeaveBlance::insert($data_array);

          }
             }
          }

       }
}

public function assign_leave(){
   //$previous_year_remaing_pl = 0;
  $data = BasicInfo::where('emp_status',1)->get(['emp_id','is_confirmed','emp_doc']);
  $curr_date = Carbon::now();
  $currentYear = Carbon::now()->year;
  $current_month = Carbon::now()->month;
  $month_diff = 12 - $current_month;
  $lastDateOfYear = Carbon::create($currentYear, 12, 31)->toDateString();
  foreach($data as $row){
    if($row->is_confirmed==0){
      $probation_leave = LeaveType::where('is_probation_leave',1)->get();
      foreach($probation_leave as $probation){
        $per_month_leave = round($probation->leave_days/12);
        $assigned_leave =  $per_month_leave* $month_diff+1;
        $check_exists = EmployeeLeaveBlance::where('emp_id',$row->emp_id)
        ->where('leave_id',$probation->id)->where('year',$currentYear)->exists();
        if(!$check_exists){
          $data_array = array('leave_id'=>$probation->id,
            'emp_id'=>$row->emp_id,'year'=>$currentYear,'current_year_assign_leave'=>$assigned_leave,'total_leave'=>$assigned_leave,'remaining'=>$assigned_leave,'is_probation_leave'=>$probation->is_probation_leave,
              'is_request_leave'=>$probation->assign_when_request);
              EmployeeLeaveBlance::insert($data_array);
           }

        
      }

     }
     else{
      $confirmed_leave = LeaveType::where('is_probation_leave',0)->where('assign_when_request',0)->get();
      foreach($confirmed_leave as $confirm){
          $previous_year_pl = EmployeeLeaveBlance::where('emp_id',$row->emp_id)
          ->where('leave_id',1)->where('year',$currentYear-1)->first();
          if($previous_year_pl){
            $previous_year_remaing_pl =  round($previous_year_pl->remaining/2);

          }
          if($confirm->id==1){
            $pl =  $previous_year_remaing_pl;

          }
          else{
            $pl = 0;
          }
         $per_month_leave = round($confirm->leave_days/12);
         $assigned_leave =  $per_month_leave* $month_diff+1;
         $check_exists = EmployeeLeaveBlance::where('emp_id',$row->emp_id)
        ->where('leave_id',$confirm->id)->where('year',$currentYear)->exists();
        if(!$check_exists){
          $data_array = array('leave_id'=>$confirm->id,
            'emp_id'=>$row->emp_id,'year'=>$currentYear,'current_year_assign_leave'=>$assigned_leave,'total_leave'=>$assigned_leave+$pl,'remaining'=>$assigned_leave+$pl,'is_probation_leave'=>$confirm->is_probation_leave,
              'is_request_leave'=>$confirm->assign_when_request,'previous_year_forward_leave'=>$pl);
              EmployeeLeaveBlance::insert($data_array);
           }
           }

     }


    }
   }

  public function save_emp_leave(Request $request)
{
    // Validate input
    $input = $request->all();
    $validator = Validator::make($input, [
        'leave_type' => 'required',
        'date_from' => 'required',
        'date_to' => 'required',
        'no_of_days' => 'required',
        'message' => 'required',
        'emp_id' => 'required',
    ]);

    if ($validator->fails()) {
        $messages = $validator->messages();
        return response()->json(['messages' => $messages, 'status' => 400]);
    }

    $leave_details = EmployeeLeaveBlance::where('emp_id',$request->emp_id)->where('leave_id',$request->leave_type)->where('year',Carbon::now()->year)->first();
    if($leave_details->remaining>0){
      $remaining = $leave_details->remaining - $request->no_of_days;
    }
    else{
       $remaining = 0;

    }

    $leave = new EmployeeLeave();
    $leave->emp_id = $request->emp_id;
    $leave->date_from = $request->date_from;
    $leave->date_to = $request->date_to;
    $leave->message = $request->message;
    $leave->no_of_days = $request->no_of_days;
    $leave->is_half_day = $request->is_half_day;
    $leave->leave_id = $request->leave_type;

    $check_leave_blance = EmployeeLeaveBlance::where('leave_id', $request->leave_type)
        ->where('emp_id', $request->emp_id)
        ->where('year', Carbon::now()->year)
        ->first();
    if (!$check_leave_blance || $check_leave_blance->total_leave < $request->no_of_days) {
        return response()->json(['message' => 'Insufficient Leave Balance. Try another leave.']);
    }

    $start_date = Carbon::parse($request->date_from);
    $end_date = Carbon::parse($request->date_to);
    $start_date_month = $start_date->month;

    $check_leave_taken_months = EmployeeLeave::where('emp_id', $request->emp_id)
        ->whereMonth('date_from', $start_date_month)
        ->where('leave_id',$request->leave_type)
        ->where('status', 1)
        ->count();
      //return  $check_leave_taken_months;

    $total_assign_leave_in_a_month = LeaveType::where('id', $request->leave_type)
        ->where('status', 1)
        ->first();

    if ($total_assign_leave_in_a_month && $total_assign_leave_in_a_month->at_a_time <= $check_leave_taken_months) {
        return response()->json(['message' => 'Leave limit exceeded for the month.']);
    }

    $mondays = [];
    $period = CarbonPeriod::create($start_date, $end_date);

    foreach ($period as $date) {
        if ($date->isMonday()) {
            $mondays[] = $date;
        }
    }

    if (count($mondays) > 0) {
        $firstMonday = $mondays[0];
        $adjustedDate = $firstMonday->copy()->subDays(3);
        $formattedAdjustedDate = $adjustedDate->format('Y-m-d');

        $check_leave = DB::table('leave_details')
            ->where('date', $formattedAdjustedDate)
            ->where('status', 1)
            ->first();

        if (!$check_leave) {
            $check_friday_attendance = DB::table('attendance')
                ->where('attendance_date', $formattedAdjustedDate)
                ->where('emp_id', $request->emp_id)
                ->first();

            if ($check_friday_attendance && ($check_friday_attendance->leave_status == 1 || $check_friday_attendance->status == 0) && $check_friday_attendance->is_half_day == 0) {
                $sandwich_days = [];
                $period_days = CarbonPeriod::create($adjustedDate, $end_date);

                foreach ($period_days as $day) {
                    $sandwich_days[] = $day->format('Y-m-d');
                }

                DB::table('attendance')
                    ->where('emp_id', $request->emp_id)
                    ->whereIn('attendance_date', $sandwich_days)
                    ->update(['is_sandwich_leave' => 1]);

                return response()->json(['message' => 'Cannot take leave due to sandwich rule.']);
            }
        }

    }
        $leave->save();
        EmployeeLeaveBlance::where('emp_id',$request->emp_id)->where('leave_id',$request->leave_type)->where('year',Carbon::now()->year)->update(['leave_taken'=>$leave_details->leave_taken + $request->no_of_days,'remaining'=>$remaining]);
         return response()->json(['message' => 'Leave saved successfully.']);

    
}


}