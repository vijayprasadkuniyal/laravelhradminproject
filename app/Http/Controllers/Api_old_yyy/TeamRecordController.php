<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BasicInfo;
use App\Models\EmployeeLeave;
use App\Models\EmployeeLeaveBlance;
Use \Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\WFH;
use App\Models\Attendance;
use App\Models\LeaveType;
use App\Events\AcceptEvent;
//use App\Models\EmpLeaveBlance;

class TeamRecordController extends Controller
{
    public function team_record_data(Request $request){
      $managers = DB::table('employee_managers')->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->id])->pluck('emp_id');
     // return $managers;
        //$managers = DB::table('employee_managers')->where('manager_id',$request->id)->pluck('emp_id');
        $data = BasicInfo::join('company_details','company_details.id','=','emp_basic_info.company_id')
        ->join('branch_details','branch_details.id','=','emp_basic_info.branch_id')
        ->join('department','department.id','=','emp_basic_info.dept_id')
        ->join('designation','designation.id','=','emp_basic_info.desi_id')
        ->join('employee_type','employee_type.id','=','emp_basic_info.emp_type')
        ->select('emp_basic_info.id','company_details.business_name','branch_details.branch_name','department.department_name',
        'designation.designation_name','employee_type.emp_type','emp_basic_info.emp_id','emp_basic_info.emp_fname',
        'emp_basic_info.emp_mname','emp_basic_info.emp_lame','emp_basic_info.emp_father_name','emp_basic_info.emp_sex',
        'emp_basic_info.emp_phone','emp_basic_info.emp_office_phone','emp_basic_info.emp_email',
        'emp_basic_info.emp_office_email','emp_basic_info.emp_dob','emp_basic_info.emp_doj','emp_basic_info.emp_status',
        'emp_basic_info.login_id','emp_basic_info.emp_doc','emp_basic_info.is_confirmed')
        ->whereIn('emp_id',$managers)
        ->paginate(10);
        return response()->json(['status'=>200,'message' => 'Team Details','data'=>$data,'last_page'=>$data->lastPage()]);





    }
    public function team_leave_data(Request $request){
       $managers = DB::table('employee_managers')->where('dept_manager',$request->id)->pluck('emp_id');
      foreach($managers as $row){
          DB::table('employee_leave')->where('emp_id',$row)->update(['seen_status'=>1]);
      }

      $check_record = DB::table('emp_notifications')->where('emp_id',$request->id)->where('type','leave')->where('seen_status',0)->exists();
   if($check_record){
    DB::table('emp_notifications')->where('emp_id',$request->id)
    ->where('type','leave')->where('seen_status',0)->update(['seen_status'=>1]);

   }
      if($request->emp){
        $emp_ids =  BasicInfo::where('reporting_manager',$request->id)->where('emp_fname','LIKE',"%{$request->emp}%")->pluck('emp_id');
      }
      else{
        $emp_ids = BasicInfo::where('reporting_manager',$request->id)->pluck('emp_id');
      }       
       $page = 10;
        //return $emp_ids;
        $team_leave_data = EmployeeLeave::join('emp_basic_info','emp_basic_info.emp_id','=','employee_leave.emp_id')
                         ->join('create_leave_type','create_leave_type.id','=','employee_leave.leave_id')
                         ->select('emp_basic_info.emp_fname','emp_basic_info.emp_mname','emp_basic_info.emp_lame','employee_leave.id','employee_leave.date_from','employee_leave.date_to','employee_leave.message','employee_leave.no_of_days','create_leave_type.leave_name','emp_basic_info.emp_id','employee_leave.leave_id','employee_leave.status','employee_leave.rejection_reason')
                         ->whereIn('employee_leave.emp_id',$emp_ids)
                         ->orderBy('employee_leave.id','DESC')
                         ->paginate(10);
       return response()->json(['status'=>200,'message' => 'Leave Details','data'=> $team_leave_data,'last_page'=>$team_leave_data->lastPage()]);

    }

public function emp_leave_status(Request $request){
    $result = [];
    $emp_leave =  EmployeeLeave::where('id',$request->leaveId)->where('emp_id',$request->empId)->first();
    $emp_leave_blance = EmployeeLeaveBlance::where('leave_id',$request->leave_type)
    ->where('emp_id',$request->empId)->where('year',Carbon::now()->year)->first();
    if($emp_leave->is_half_day ==1){
      //return 'kkk';
      $half_day = 1;

    }
    else{
      //return 'hh';
      $half_day = 0;

    }
    $start_date = $emp_leave->date_from;
    $end_date = $emp_leave->date_to;
    $startDate = Carbon::parse($start_date);
    $endDate = Carbon::parse($end_date);
   for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
    $result[] = [
        'date' => $date->toDateString(),
        'weekday' => $date->format('l'),
    ];
}
  $datesToCheck = array_column($result, 'date');
 $existingDates = Attendance::whereIn('attendance_date', $datesToCheck)
    ->where('emp_id', $request->empId)
    ->pluck('attendance_date')
    ->toArray();
foreach ($result as $dateData) {
    $date = $dateData['date'];
    $weekday = $dateData['weekday'];
    if (in_array($date, $existingDates)) {
        Attendance::where('attendance_date', $date)
            ->where('emp_id', $request->empId)
            ->update(['leave_status' => $request->status,'weekday'=>$weekday,'leave_type'=>$request->leave_type,'is_half_day'=>$half_day,]);
    } else {
        Attendance::create([
            'attendance_date' => $date,
            'emp_id' => $request->empId,
            'leave_status' => $request->status,
            'weekday'=>$weekday,
            'leave_type'=>$request->leave_type,
            'is_half_day'=>$half_day,
        ]);
    }
}

if($request->status ==2){
  if( $emp_leave_blance->leave_taken>0){
    EmployeeLeaveBlance::where('leave_id',$request->leave_type)
    ->where('emp_id',$request->empId)->where('year',Carbon::now()->year)
    ->update(['leave_taken'=>$emp_leave_blance->leave_taken- $emp_leave->no_of_days,
      'remaining'=>$emp_leave_blance->remaining + $emp_leave->no_of_days ]);

  }
  else{
    return response()->json(['status'=>201,'message'=>'No Leave Pending For Approval']);
  }
   }

   $data = EmployeeLeave::where('id',$request->leaveId)->where('emp_id',$request->empId)->update(['status'=>$request->status,'rejection_reason'=>$request->rejectionReason,'action_by'=>$request->emp_id]);

    if($request->status ==1){
      $notification = 'Your Leave Has Been Accepted on '.Carbon::now()->format('Y-m-d');
    }
    else{
      $notification = 'Your Leave Has Been Rejected on '.Carbon::now()->format('Y-m-d');

    }
    $emp_name = BasicInfo::where('emp_id',$request->emp_id)->first();
    $notification_data = array('emp_id'=>$request->empId,'notification'=> $notification,'action_by'=> $emp_name->emp_fname.' '.$emp_name->emp_lame,'type'=>'leave_status');
    DB::table('emp_notifications')->insert($notification_data);

    $message = $notification;
    $type = 'leave_status';
        event(new AcceptEvent($message,$request->empId,$type));

    return response()->json(['status'=>200,'message' => 'Leave Status updated successfully']);



}
public function change_confirmation_status(Request $request){
   $status = $request->confirmation;
    $curr_date = Carbon::now();
    $currentYear = Carbon::now()->year;
    $current_month = Carbon::now()->month;
    $month_diff = 12 - $current_month;
   if($status==1){
     $basic_info = BasicInfo::where('emp_id',$request->empId)->update(['emp_doc'=>Carbon::now()->format('Y-m-d'),'is_confirmed'=>$status]);
      $confirmation_leave = LeaveType::where('is_confirmation_leave',1)->where('assign_when_request',0)->get();

     foreach($confirmation_leave as $row){
        $per_month_leave = round($row->leave_days/12);
        $assigned_leave =  $per_month_leave* $month_diff+1;
        $check_exists = EmployeeLeaveBlance::where('leave_id',$row->leave_type)
        ->where('emp_id',$request->empId)->where('year',Carbon::now()->year)->first();
        if(!$check_exists){

          $data_array[] = array('emp_id'=>$request->empId,'leave_id'=>$row->id,
            'current_year_assign_leave'=>$assigned_leave,
            'previous_year_forward_leave'=>0,
            'total_leave'=>$assigned_leave,
            'leave_taken'=>0,'remaining'=>$assigned_leave,
            'year'=> $currentYear,
            'is_probation_leave'=>$row->is_probation_leave,
            'is_confirmation_leave'=>$row->is_confirmation_leave,'is_request_leave'=>$row->assign_when_request);


        }
        }

        DB::table('emp_leave_blance')->insert($data_array);
     }
   else{
     $basic_info = BasicInfo::where('emp_id',$request->empId)->update(['emp_doc'=>$request->date,'is_confirmed'=>0]);

     }
     // $emp_name = BasicInfo::where('emp_id',$request->action_by)->first();
    return response()->json(['status'=>200,'message' => 'Confirmation Status updated successfully']);




}
public function team_wfh(Request $request){

   $manager = DB::table('employee_managers')->where('dept_manager',$request->emp_id)->pluck('emp_id');
   foreach($manager as $row){
    DB::table('work_from_home')->where('employee_id',$row)->update(['seen_status'=>1]);
   }
   $check_record = DB::table('emp_notifications')->where('emp_id',$request->emp_id)->where('type','wfh')->where('seen_status',0)->exists();
   if($check_record){
    DB::table('emp_notifications')->where('emp_id',$request->emp_id)
    ->where('type','wfh')->where('seen_status',0)->update(['seen_status'=>1]);

   }
   // dd($request->all());
   $per_page = $request->per_page;
   //dd($per_page);
    $managers = DB::table('employee_managers')->where('dept_manager',$request->emp_id)->where('status',1)->pluck('emp_id');
       $data = BasicInfo::join('work_from_home','work_from_home.employee_id','=','emp_basic_info.emp_id')
       ->select('emp_basic_info.emp_fname','emp_basic_info.emp_mname','emp_basic_info.emp_lame','work_from_home.days_from',
       'work_from_home.days_to','work_from_home.no_of_days','work_from_home.status','work_from_home.rejection_reason',
       'work_from_home.reason_for_wfh','work_from_home.id','work_from_home.employee_id')
       ->whereIn('emp_id',$managers)
       ->paginate($per_page);
        $totalPages = $data->lastPage();
      //return Log::info(DB::getQueryLog($data));

       return response()->json(['status'=>200,'message' => 'Work From Home Details','data'=>  $data,'last_page'=>$totalPages]);

}
public function work_from_home_status(Request $request){
     WFH::where('id',$request->id)->update(['status'=>$request->status,'rejection_reason'=>$request->rejectionReason]);
      $emp_name = BasicInfo::where('emp_id',$request->action_by)->first();
      if($request->status ==1){
         $notification = 'Your Work From  Home Has Been Accepted on '.Carbon::now()->format('Y-m-d');
    }
    else{
      $notification = 'Your Work From  Home Has Been Rejected on '.Carbon::now()->format('Y-m-d');

    }
    $type = 'wfh_status';
    $notification_data = array('emp_id'=>$request->emp_id,'notification'=> $notification,'action_by'=> $emp_name->emp_fname.' '.$emp_name->emp_lame,'type'=>'wfh_status');
    DB::table('emp_notifications')->insert($notification_data);
    $message = $notification;
    //return $request->emp_id;
        event(new AcceptEvent($message,$request->emp_id,$type));
     return response()->json(['status'=>200,'message' => 'Updated Successfully']);
  }
  public function team_member_leave($id){
    $data = [];
    $leaves = EmployeeLeave::where('emp_id',$id)->paginate(10);
    return $leaves;
    foreach($leaves as $row){
      if($row->status ==1){
        $status = 'Accepted';
      }
      else if($row->status ==2){
        $status = 'Rejected';
      }
      else{
        $status = 'pending';
      }
      $leave_type = LeaveType::where('id',$row->leave_id)->first();
      $data[] = array('id'=>$row->id,'leave_type'=>$leave_type->leave_name,
      'from'=>$row->date_from,'to'=>$row->date_to,'total_days'=>$row->no_of_days,
      'message'=>$row->message,'rejection_reason'=>$row->rejection_reason,'status'=>$status);
      }
      return response()->json(['status'=>200,'data' =>$data,'last_page'=>$leaves->lastPage()]);
    

  }

  public function team_data_count($id)
{
  $total_attendance_request = DB::table('attendance_regularize_request')->where('seen_status',0)->count();
    $team_emp = DB::table('employee_managers')
        ->where('dept_manager', $id)
        ->distinct()
        ->pluck('emp_id');

    $leaveCount = 0;
    $wfhCount = 0;
    $roster_change = 0;

    foreach ($team_emp as $row) {
        $leaves = EmployeeLeave::where('emp_id', $row)
            ->where('seen_status', 0)
            ->count('emp_id');
        $wfh = WFH::where('employee_id', $row)
            ->where('seen_status', 0)
            ->count('employee_id');
        $roster = DB::table('emp_change_roster_request')->where('emp_id',$row)->where('seen_status',0)
                 ->count('emp_id');

        $leaveCount += $leaves;
        $wfhCount += $wfh;
        $roster_change +=$roster;
    }

    return response()->json(['status' => 200, 'leave' => $leaveCount, 'wfh' => $wfhCount,'roster'=>$roster_change,'attendance_request'=>$total_attendance_request]);
}

}
