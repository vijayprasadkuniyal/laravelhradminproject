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


class TeamRecordController extends Controller
{
    public function team_record_data(Request $request){
        $managers = DB::table('employee_managers')->where('manager_id',$request->id)->pluck('emp_id');
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
        ->paginate($request->per_page);
        return response()->json(['status'=>200,'message' => 'Team Details','data'=>$data,'last_page'=>$data->lastPage()]);





    }
    public function team_leave_data(Request $request){
        $emp_ids = BasicInfo::where('reporting_manager',$request->id)->pluck('emp_id');
        $page = 10;
        //return $emp_ids;
        $team_leave_data = EmployeeLeave::join('emp_basic_info','emp_basic_info.emp_id','=','employee_leave.emp_id')
                         ->join('create_leave_type','create_leave_type.id','=','employee_leave.leave_id')
                         ->select('emp_basic_info.emp_fname','emp_basic_info.emp_mname','emp_basic_info.emp_lame','employee_leave.id','employee_leave.date_from','employee_leave.date_to','employee_leave.message','employee_leave.no_of_days','create_leave_type.leave_name','emp_basic_info.emp_id','employee_leave.leave_id','employee_leave.status','employee_leave.rejection_reason')
                         ->whereIn('employee_leave.emp_id',$emp_ids)
                         ->paginate(10);
       return response()->json(['status'=>200,'message' => 'Leave Details','data'=> $team_leave_data,'last_page'=>$team_leave_data->lastPage()]);

    }

public function emp_leave_status(Request $request){
    $result = [];
    $emp_leave =  EmployeeLeave::where('id',$request->leaveId)->where('emp_id',$request->empId)->first();
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
        ]);
    }
}

  $leave_blance = EmployeeLeaveBlance::where('emp_id',$request->empId)->where('leave_id',$request->leave_type)->where('year',Carbon::now()->format('Y'))->first();

   //dd($leave_blance);
     if ($request->status != '1' && $emp_leave->status == '1') {
        $remaining_leave = $leave_blance->remaining_leave + $emp_leave->no_of_days;
        $blance = $leave_blance->total_leave_taking - $emp_leave->no_of_days;
     } elseif ($request->status == '1' && $emp_leave->status != '1') {
        if($leave_blance->remaining_leave>0){
         $remaining_leave = $leave_blance->remaining_leave - $request->no_of_days; 
         $blance = $leave_blance->total_leave_taking + $request->no_of_days;
        }
        else{
          return response()->json(['message' => 'Dont Have Leave To Accept']);

        }
        //$remaining_leave = $leave_blance->remaining_leave - $request->no_of_days;
     } else {
        $remaining_leave = $leave_blance->remaining_leave;
        $blance = $leave_blance->total_leave_taking;
     }
   $data = EmployeeLeave::where('id',$request->leaveId)->where('emp_id',$request->empId)->update(['status'=>$request->status,'rejection_reason'=>$request->rejectionReason]);

    EmployeeLeaveBlance::where('emp_id',$request->empId)->where('leave_id',$request->leave_type)->where('year',Carbon::now()->format('Y'))->update(['total_leave_taking'=>$blance,'remaining_leave'=>$remaining_leave]);
    if($request->status ==1){
      $notification = 'Your Leave Has Been Accepted on '.Carbon::now()->format('Y-m-d');
    }
    else{
      $notification = 'Your Leave Has Been Rejected on '.Carbon::now()->format('Y-m-d');

    }
    $notification_data = array('emp_id'=>$request->empId,'notification'=> $notification);
    DB::table('emp_notifications')->insert($notification_data);
    return response()->json(['status'=>200,'message' => 'Leave Status updated successfully']);



}
public function change_confirmation_status(Request $request){
   $status = $request->confirmation;
   if($status==1){
     $basic_info = BasicInfo::where('emp_id',$request->empId)->update(['emp_doc'=>Carbon::now()->format('Y-m-d'),'is_confirmed'=>$status]);
     $emp_history = array('emp_id'=>$request->empId,'emp_doc'=>Carbon::now()->format('Y-m-d'),'is_confirmed'=>$status);
     $notification = 'Hi Congratulations You Are Confirned On  '.Carbon::now()->format('Y-m-d');
     DB::table('emp_history')->insert($emp_history);


   }
   else{
     $basic_info = BasicInfo::where('emp_id',$request->empId)->update(['emp_doc'=>$request->date,'is_confirmed'=>0]);
     $emp_history = array('emp_id'=>$request->empId,'emp_doc'=>$request->date,'is_confirmed'=>0);
      $notification = 'Your Confirmation Status Has Been Postpond on '.Carbon::now()->format('Y-m-d');
      DB::table('emp_history')->insert($emp_history);
     }
     $notification_data = array('emp_id'=>$request->empId,'notification'=> $notification);
    DB::table('emp_notifications')->insert($notification_data);
    return response()->json(['status'=>200,'message' => 'Confirmation Status updated successfully']);




}
public function team_wfh(Request $request){
   // dd($request->all());
   $per_page = $request->per_page;
   //dd($per_page);
    $managers = DB::table('employee_managers')->where('manager_id',$request->emp_id)->where('manager_status',1)->pluck('emp_id');
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
      if($request->status ==1){
         $notification = 'Your Work From  Home Has Been Accepted on '.Carbon::now()->format('Y-m-d');
    }
    else{
      $notification = 'Your Work From  Home Has Been Rejected on '.Carbon::now()->format('Y-m-d');

    }
    $notification_data = array('emp_id'=>$request->emp_id,'notification'=> $notification);
    DB::table('emp_notifications')->insert($notification_data);
     return response()->json(['status'=>200,'message' => 'Updated Successfully']);
  }
}
