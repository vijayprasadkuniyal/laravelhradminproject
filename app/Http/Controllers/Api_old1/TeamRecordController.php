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
            ->update(['leave_status' => $request->status,'weekday'=>$weekday,'leave_type'=>$request->leave_type]);
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
if($request->status == 2 && $emp_leave->is_half_day == 0){
 foreach($result as $dates){
     $date =  $dates['date'];
     $quarter = Carbon::parse($date)->quarter;
     $year = Carbon::parse($date)->year;

      $leave_type = LeaveType::where('id',$request->leave_type)->first();
       if($leave_type->is_assign_quartly==0){
          $emp_leave_data = EmployeeLeaveBlance::where('year',$year)->where('leave_id',$request->leave_type)
         ->where('emp_id',$request->empId)->first();
         if($emp_leave_data && $emp_leave_data->leave_taken>0){
                EmployeeLeaveBlance::where('year',$year)->where('leave_id',$request->leave_type) 
              ->where('emp_id',$request->empId)
             ->update(['remaining'=>$emp_leave_data->remaining + 1,'leave_taken'=>$emp_leave_data->leave_taken - 1]);

          }


        }
    else{
      $emp_leave_data = EmployeeLeaveBlance::where('quarter_no',$quarter)->where('year',$year)->where('leave_id',$request->leave_type)
     ->where('emp_id',$request->empId)->first();
     if($emp_leave_data && $emp_leave_data->leave_taken>0 ){
      EmployeeLeaveBlance::where('quarter_no',$quarter)->where('year',$year)->where('leave_id',$request->leave_type)
     ->where('emp_id',$request->empId)
     ->update(['remaining'=>$emp_leave_data->remaining + 1,'leave_taken'=>$emp_leave_data->leave_taken - 1]);

     }

    
  }
}
}
if($request->status == 2 && $emp_leave->is_half_day ==1){
 foreach($result as $dates){
     $date =  $dates['date'];
     $quarter = Carbon::parse($date)->quarter;
     $year = Carbon::parse($date)->year;

      $leave_type = LeaveType::where('id',$request->leave_type)->first();
       if($leave_type->is_assign_quartly==0){
          $emp_leave_data = EmployeeLeaveBlance::where('year',$year)->where('leave_id',$request->leave_type)
         ->where('emp_id',$request->empId)->first();
         if($emp_leave_data && $emp_leave_date->leave_taken>0){
                EmployeeLeaveBlance::where('year',$year)->where('leave_id',$request->leave_type) 
              ->where('emp_id',$request->empId)
             ->update(['remaining'=>$emp_leave_data->remaining + 0.5,'leave_taken'=>$emp_leave_data->leave_taken - 0.5]);

          }


        }
    else{
      $emp_leave_data = EmployeeLeaveBlance::where('quarter_no',$quarter)->where('year',$year)->where('leave_id',$request->leave_type)
     ->where('emp_id',$request->empId)->first();
     if($emp_leave_data && $emp_leave_data->leave_taken>0 ){
      EmployeeLeaveBlance::where('quarter_no',$quarter)->where('year',$year)->where('leave_id',$request->leave_type)
     ->where('emp_id',$request->empId)
     ->update(['remaining'=>$emp_leave_data->remaining + 0.5,'leave_taken'=>$emp_leave_data->leave_taken - 0.5]);

     }

    
  }
}
}
   $data = EmployeeLeave::where('id',$request->leaveId)->where('emp_id',$request->empId)->update(['status'=>$request->status,'rejection_reason'=>$request->rejectionReason,'action_by'=>$request->emp_id]);

    //EmployeeLeaveBlance::where('emp_id',$request->empId)->where('leave_id',$request->leave_type)->where('year',Carbon::now()->format('Y'))->update(['total_leave_taking'=>$blance,'remaining_leave'=>$remaining_leave]);
    if($request->status ==1){
      $notification = 'Your Leave Has Been Accepted on '.Carbon::now()->format('Y-m-d');
    }
    else{
      $notification = 'Your Leave Has Been Rejected on '.Carbon::now()->format('Y-m-d');

    }
    $emp_name = BasicInfo::where('emp_id',$request->emp_id)->first();
    $notification_data = array('emp_id'=>$request->empId,'notification'=> $notification,'action_by'=> $emp_name->emp_fname.' '.$emp_name->emp_lame);
    DB::table('emp_notifications')->insert($notification_data);
    return response()->json(['status'=>200,'message' => 'Leave Status updated successfully']);



}
public function change_confirmation_status(Request $request){
   $status = $request->confirmation;
   if($status==1){
     $basic_info = BasicInfo::where('emp_id',$request->empId)->update(['emp_doc'=>Carbon::now()->format('Y-m-d'),'is_confirmed'=>$status]);
     $emp_history = array('emp_id'=>$request->empId,'emp_doc'=>Carbon::now()->format('Y-m-d'),'is_confirmed'=>$status);
     $notification = 'Hi Congratulations You Are Confirned On  '.Carbon::now()->format('Y-m-d');

      $yearly_leave_days = [];
          $yearly_assign_leave = LeaveType::where('is_probation_leave',0)->where('assign_when_request',0)->where('is_assign_quartly',0)
          ->where('status',1)->get();
          foreach($yearly_assign_leave as $year){
            $check_exists = EmployeeLeaveBlance::where('leave_id',$year->id)->
            where('emp_id',$request->empId)->where('year',Carbon::now()->year)->first();
            if(!$check_exists){
              $yearly_leave_days[] = array('emp_id'=>$request->empId,'leave_id'=>$year->id,
              'year'=>Carbon::now()->year,'total_leave'=>$year->leave_days,'leave_taken'=>0,
              'remaining'=>$year->leave_days,'is_assign_quartly'=>1);

            }

          }
          EmployeeLeaveBlance::insert($yearly_leave_days);

     $confirm_leave = LeaveType::where('is_probation_leave',0)->where('assign_when_request',0)->where('is_assign_quartly',1)->where('status',1)->get();
     $assign_leave = EmployeeLeaveBlance::where('quarter_no',Carbon::now()->quarter)->where('is_probation_leave',0)->where('emp_id',$request->empId)->where('year',Carbon::now()->year)->count('year');
     if($assign_leave==0){
      foreach($confirm_leave as $row){
         $endOfQuarter = Carbon::now()->endOfQuarter()->format('Y-m-d');
         $endOfQuarter = Carbon::parse($endOfQuarter);
         $current_date = Carbon::now()->format('Y-m-d');
         $current_date = Carbon::parse($current_date);
         $diffInMonths = $current_date->diffInMonths($endOfQuarter);
        // return $diffInMonths;
        //return $diffInMonths;
         if($diffInMonths==1){
          $total = 1;
         }
         elseif ($diffInMonths==2){
          $total = 2;
         }
         elseif($diffInMonths==3){
          $total = 3;
         }
         else{
           $total = 0;

         }
         $data_array = array('emp_id'=>$request->empId,'leave_id'=>$row->id,
            'quarter_no'=>Carbon::now()->quarter,'quarter_start_from'=>$current_date,'quarter_end'=>$endOfQuarter,
            'year'=>Carbon::now()->year,'total_leave'=>$total,'leave_taken'=>0,'remaining'=>$total,);
            EmployeeLeaveBlance::insert($data_array);
        }

     }

     DB::table('emp_history')->insert($emp_history);


   }
   else{
     $basic_info = BasicInfo::where('emp_id',$request->empId)->update(['emp_doc'=>$request->date,'is_confirmed'=>0]);
     $emp_history = array('emp_id'=>$request->empId,'emp_doc'=>$request->date,'is_confirmed'=>0);
      $notification = 'Your Confirmation Status Has Been Postpond on '.Carbon::now()->format('Y-m-d');
      DB::table('emp_history')->insert($emp_history);
     }
      $emp_name = BasicInfo::where('emp_id',$request->action_by)->first();
     $notification_data = array('emp_id'=>$request->empId,'notification'=> $notification,'action_by'=> $emp_name->emp_fname.' '.$emp_name->emp_lame);
    DB::table('emp_notifications')->insert($notification_data);
    return response()->json(['status'=>200,'message' => 'Confirmation Status updated successfully']);




}
public function team_wfh(Request $request){

   $manager = DB::table('employee_managers')->where('dept_manager',$request->emp_id)->pluck('emp_id');
   foreach($manager as $row){
    DB::table('work_from_home')->where('employee_id',$row)->update(['seen_status'=>1]);
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
    $notification_data = array('emp_id'=>$request->emp_id,'notification'=> $notification,'action_by'=> $emp_name->emp_fname.' '.$emp_name->emp_lame);
    DB::table('emp_notifications')->insert($notification_data);
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

    return response()->json(['status' => 200, 'leave' => $leaveCount, 'wfh' => $wfhCount,'roster'=>$roster_change ]);
}

}
