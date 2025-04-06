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
                'total_login_times'=>$working_hours,
                'week_day'=>$week_day,
              );
              return response()->json(['status' => 200, 'message' => 'Employee Details','data'=>$data]);

      //return response()->json([''])

    }
   public function daily_attendence($id)
{
    $daily_attendance = Attendance::where('emp_id', $id)->get();
     $break_time = '0:00';
     $idle_time = '0:00';
     $emp_details = BasicInfo::where('emp_id',$id)->first();
      $currentMonth = date('n');
            $currentYear = date('Y');
            if ($currentMonth >= 4) {
                $financialYearStart = $currentYear;
                $financialYearEnd = $currentYear + 1;
            } else {
                $financialYearStart = $currentYear - 1;
                $financialYearEnd = $currentYear;
            }
            
      $financialYear = $financialYearStart . '-' . $financialYearEnd;
   
    $data_array = [];
    if(count($daily_attendance)>0){
      foreach ($daily_attendance as $row) {
        $date = Carbon::parse($row->attendance_date)->format('Y-m-d');
        $weekday = Carbon::parse($row->attendance_date)->dayName;
        $break_time_record = DB::table('store_employee_break_time')
                ->select(DB::raw('SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_break_time'))
                ->where('emp_id', $id)
                ->whereDate('created_date', Carbon::parse($row->attendance_date)->format('Y-m-d'))
                ->groupBy('emp_id', DB::raw('DATE(created_date)'))
                ->first();

        $idle_time_records = DB::table('store_idle_time')
          ->select('emp_id', DB::raw('DATE(created_date) as created_date'), DB::raw('SUM(TIMESTAMPDIFF(MINUTE, time_start_from, time_end)) as total_idle_time'))
            ->where('emp_id', $id)
            ->whereDate('created_date', Carbon::parse($row->attendance_date)->format('Y-m-d'))
            ->groupBy('emp_id', DB::raw('DATE(created_date)'))
            ->first();


        if ($break_time_record) {
                $total_break_time_in_minutes = $break_time_record->total_break_time;
                $break_hours = intdiv($total_break_time_in_minutes, 60);
                $break_minutes = $total_break_time_in_minutes % 60;
                $break_time = sprintf('%d:%02d', $break_hours, $break_minutes);
            }

        if($idle_time_records){
                $total_idle_time_in_minutes = $idle_time_records->total_idle_time;
                $idle_hours = intdiv($total_idle_time_in_minutes, 60);
                $idle_minutes =$total_idle_time_in_minutes % 60;
                $idle_time = sprintf('%d:%02d', $idle_hours,  $idle_minutes);

        }
        //return  $break_time_records;


        //$login_time = Carbon::parse($row->login_date_time)->format('H:i:s');
        $working_hours = '';
        $status = '';
        $sign_out = '';
        $login_time = '';
       
       
/*
        if ($row->logout_date_time == '') {
            $login_time = Carbon::parse($row->login_date_time)->format('H:i:s');
            $sign_out = '';
            $working_hours = '00:00:00';
            $status = "Absent";
        }*/
        if($row->login_date_time == ''){
            $sign_out = '';
            $login_time = '';
            $working_hours = '00:00:00';
            $status = "Absent";
            
           

        }
         else if($row->login_date_time != '' && $row->logout_date_time == '' ){
            $sign_out = '';
            $login_time = Carbon::parse($row->login_date_time)->format('H:i:s');
            $working_hours = '00:00:00';
            $status = "Absent";

        }
        
        else {
            $login_time = Carbon::parse($row->login_date_time)->format('H:i:s');
            $sign_out = Carbon::parse($row->logout_date_time)->format('H:i:s');
            $start = Carbon::parse($row->login_date_time)->toDateTimeString();
            $start = new Carbon($start);
            $end = Carbon::parse($row->logout_date_time)->toDateTimeString();
            $end = new Carbon($end);
            $working_hours = $start->diff($end)->format('%H:%I:%S');
            $diff_in_hour = $start->diff($end)->format('%H');
            if($diff_in_hour>=9){
              $status = "Present";

            }
            else if($diff_in_hour>=7 && $diff_in_hour<9){
              $status = "Short Leave";
            }
            else if($diff_in_hour>=5 && $diff_in_hour<7){
              $status = "Half Day";
            }

            else{
              $status = "Absent";

            }

        }
          $data_array[] = array('date'=>$date,'login_time'=>$login_time,'logout_time'=>$sign_out,'working_hours'=> $working_hours,'status'=>$status,'weekday'=>$weekday,'break_time'=>$break_time,'idle_time'=>$idle_time);

    }
        return response()->json(['status' => 200, 'message' => 'Attendance Details','data'=>$data_array]);


    }
    else{
      return response()->json(['status' => 200, 'message' => 'Data Not Found','data'=>$data_array]);

    }


}

}