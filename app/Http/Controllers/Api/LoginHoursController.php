<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
Use \Carbon\Carbon;
use App\Models\BasicInfo;
use DB;

class LoginHoursController extends Controller
{
    public function calculate_login_hours($emp_id){
      // LoginHours::where('created_date', '2023-11-28 17:38:16')->update(['created_date' => '2023-11-29 17:38:16']);
        $login_hours = Attendance::where('emp_id', $emp_id)
        ->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))
       ->first();
      if(!$login_hours){
         return response()->json(['status' => 500, 'message' => 'Data not found for particular day']);

      }
       $emp_details = BasicInfo::where('emp_id',$emp_id)->first();
       $check_in =  $login_hours->login_date_time;
       $check_out = $login_hours->logout_date_time;
       $startTime = Carbon::parse($check_in);
       $current_time = Carbon::now()->toDateTimeString();
       $current_time = Carbon::parse($current_time);
       $endTime = Carbon::parse($check_out);
       $total_login_hours_day =  $startTime->diff($endTime)->format('%H:%I:%S');
       $working_hours = $current_time->diff($startTime)->format('%H:%I:%S');
       $week_day = Carbon::now()->format('Y-m-d');
       $week_day = Carbon::parse($week_day);
       $week_day = $week_day->format('l');
       $data = array(
                'first_name'=>$emp_details->emp_fname,
                'middle_name'=>$emp_details->emp_mname,
                'last_name'=>$emp_details->emp_lame,
                'total_working_hours_indays'=>$total_login_hours_day,
                'total_login_times'=>$login_hours->total_hours,
                'week_day'=>$week_day,
              );
              return response()->json(['status' => 200, 'message' => 'Employee Details','data'=>$data]);

      //return response()->json([''])

    }
   public function daily_attendence($id)
{
    $daily_attendance = Attendance::where('emp_id', $id)->get();
    $emp_info = BasicInfo::where('emp_id',$id)->first();
     $emp_details = BasicInfo::where('emp_id',$id)->first();
      // $currentMonth = date('n');
      //       $currentYear = date('Y');
      //       if ($currentMonth >= 4) {
      //           $financialYearStart = $currentYear;
      //           $financialYearEnd = $currentYear + 1;
      //       } else {
      //           $financialYearStart = $currentYear - 1;
      //           $financialYearEnd = $currentYear;
      //       }
            
      // $financialYear = $financialYearStart . '-' . $financialYearEnd;
   
    $data_array = [];
    if(count($daily_attendance)>0){
      foreach ($daily_attendance as $row) {
        $date = Carbon::parse($row->attendance_date)->format('Y-m-d');
        $weekday = Carbon::parse($row->attendance_date)->dayName;
        $working_hours = '';
        $status = '';
        $sign_out = '';
        $login_time = '';
        $punch_in_time = '';
        if($row->punch_in_date_time == ''){
            $sign_out = Carbon::parse($row->logout_date_time)->format('H:i:s')??'';
            $login_time = Carbon::parse($row->login_date_time)->format('H:i:s')??'';
            $punch_in_time = '00:00:00';
            $working_hours = '00:00:00';
            $status = "Absent";
        }
         else if($row->punch_in_date_time != '' && $row->logout_date_time == '' ){
            $sign_out = '00:00:00';
            $login_time = Carbon::parse($row->login_date_time)->format('H:i:s');
            $punch_in_time = Carbon::parse($row->punch_in_date_time)->format('H:i:s');
            $working_hours = '00:00:00';
            $status = "Absent";

        }
          else {
           $punch_in_time = Carbon::parse($row->punch_in_date_time)->format('H:i:s');
           $login_time = Carbon::parse($row->login_date_time)->format('H:i:s');
           $sign_out = Carbon::parse($row->logout_date_time)->format('H:i:s');
           $working_hours = $row->total_hours??'00:00:00';
           if($row->status==1){
            $status = 'Present';
           }
            if($row->status==2){
            $status = 'Half Day';
           }
           if($row->status==3){
            $status = 'Short Leave';
           }
           if($row->status==0){
            $status = 'Absent';
           }
         }

           $data_array[] = array('date'=>$date,'login_time'=>$login_time,
             'logout_time'=>$sign_out,'working_hours'=> $working_hours,'status'=>$status,
             'weekday'=>$weekday,'break_time'=>$row->break_time??0,'idle_time'=>$row->idle_time??0,
             'punch_in_time'=> $punch_in_time,'remark'=>$row->remark);

             }
            return response()->json(['status' => 200, 'message' => 'Attendance Details','data'=>$data_array,
                     'department_id'=>$emp_info->dept_id]);

           }
          else{
           return response()->json(['status' => 200, 'message' => 'Data Not Found',
                'data'=>$data_array,'department_id'=>$emp_info->dept_id]);
           }


}

}