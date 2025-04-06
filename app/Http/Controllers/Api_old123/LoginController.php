<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BasicInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Department;
use App\Models\Designation;
use Auth;
use App\Models\LoginHours;
Use \Carbon\Carbon;
use App\Models\LoginHistory;
use App\Models\Attendance;
use App\Models\WFH;
use DB;
use App\Models\Leave;
use App\Models\Branch;
//use Jenssegers\Agent\Agent;

class LoginController extends Controller
{
 public function login(Request $request)
{
    $input = $request->all();

    $validator = Validator::make($input, [
        'email' => 'required',
        'password' => 'required',
    ]);

    if ($validator->fails()) {
        $messages = $validator->messages();
        return response()->json(["messages" => $messages, 'status' => 400]);
    }

    $emp = BasicInfo::where('login_id', $request->input('email'))->where('emp_status', 1)->first();

    if ($emp) {
        if (Hash::check($request->input('password'), $emp->login_password)) {
            $last_password_update = DB::table('password_change_history')->where('emp_id',$emp->emp_id)->
            orderBy('id','DESC')->first();
            $current_date = Carbon::now();
            if($last_password_update){
                $password_change_date = $last_password_update->created_date;
                $current_date = Carbon::now(); 
                $start = Carbon::parse($password_change_date); 
                $end =  $current_date; 
               $days = $end->diffInDays($start); 

            }
            else{
                $days = 'not found';

            }
            // if(request()->ip() == '192.168.1.2'){
            if (2== 2 || $emp->panel_access ==1) {
                $token = $emp->createToken('Laravel Password Grant Client')->accessToken;
                $department = Department::where('id', $emp->dept_id)->pluck('department_name');
                $ip = $request->ip();
                $data = array('id' => $emp->id, 'name' => $emp->emp_fname, 'emp_id' => $emp->emp_id,'last_password_update_days'=>$days, 'branch' => $emp->branch_id, 'department' => $department[0], 'email' => $emp->login_id, 'ip' => $ip, 'department_id' => $emp->dept_id);
                
                $row = Attendance::whereDate('attendance_date', Carbon::now()->format('Y-m-d'))->where('emp_id', $emp->emp_id)->where('login_date_time', null)->first();
                if ($row) {
                    Attendance::whereDate('attendance_date', Carbon::now()->format('Y-m-d'))->where('emp_id', $emp->emp_id)
                        ->update(['login_date_time' => Carbon::now(), 'ip_address' => $ip, 'login_device' => $request->header('User-Agent')]);
                }

                $login_details = array('emp_id' => $emp->emp_id, 'login_time' => Carbon::now('Asia/Kolkata'));
                LoginHistory::insert($login_details);

                return response()->json(['status' => 200, 'message' => 'Logged In Successfully', 'token' => $token, 'data' => $data]);
            } else {
                $wfh = WFH::where('employee_id', $emp->emp_id)->where('status', 1)->get();
                $currentDate = Carbon::now();
                $isAllowed = false;

                foreach ($wfh as $row) {
                    $startDate = Carbon::parse($row->days_from);
                    $endDate = Carbon::parse($row->days_to);

                    for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                        if ($date->isSameDay($currentDate)) {
                            $isAllowed = true;
                            break 2; 
                        }
                    }
                }

                if ($isAllowed) {
                    $token = $emp->createToken('Laravel Password Grant Client')->accessToken;
                    $department = Department::where('id', $emp->dept_id)->pluck('department_name');
                    $ip = $request->ip();
                    $data = array('id' => $emp->id, 'name' => $emp->emp_fname, 'emp_id' => $emp->emp_id,'last_password_update_days'=>$days, 'branch' => $emp->branch_id, 'department' => $department[0], 'email' => $emp->login_id, 'ip' => $ip, 'department_id' => $emp->dept_id);
                    
                    $row = Attendance::whereDate('attendance_date', Carbon::now()->format('Y-m-d'))->where('emp_id', $emp->emp_id)->where('login_date_time', null)->first();
                    if ($row) {
                        Attendance::whereDate('attendance_date', Carbon::now()->format('Y-m-d'))->where('emp_id', $emp->emp_id)
                            ->update(['login_date_time' => Carbon::now(), 'ip_address' => $ip, 'login_device' => $request->header('User-Agent')]);
                    }
                     $login_details = array('emp_id' => $emp->emp_id, 'login_time' => Carbon::now('Asia/Kolkata'));
                     LoginHistory::insert($login_details);

                    return response()->json(['status' => 200, 'message' => 'Logged In Successfully', 'token' => $token, 'data' => $data]);
                } else {
                    return response()->json(['status' => 500, 'message' => 'You Don’t Have Access To Login From Outside Of Office']);
                }
            }
        } else {
            return response()->json(['status' => 400, 'message' => 'Incorrect Password']);
        }
    } else {
        return response()->json(['status' => 400, 'message' => 'Incorrect Email']);
    }
}



 
   public function session_destroy(){
    $user = Auth::user()->token();
     $user->revoke();
     return response()->json(['status'=>'200','message'=>'session expired']);
    }

    public function emp_profile($id){
     // $emp_details = [];
      $manager = '';
      $data = BasicInfo::where('emp_id',$id)->first();
      $reporting_manager = BasicInfo::where('emp_id',$data->reporting_manager)->first();
      if($reporting_manager){
        $manager =  $reporting_manager->emp_fname.' '.$reporting_manager->emp_mname.' '.$reporting_manager->emp_lame;
      }
      $department = Department::where('id',$data->dept_id)->first();
      $designation = Designation::where('id',$data->desi_id)->first();
      $emp_details = array('department'=>$department->department_name,'designation'=>$designation->designation_name,'manager'=>$manager,
      'doj'=>$data->emp_doj,'doc'=>$data->emp_doc,'emp_fname'=>$data->emp_fname,'emp_mname'=>$data->emp_mname,'emp_lame'=>$data->emp_lame,
      'profile'=>$data->profile_picture,'father_name'=>$data->emp_father_name);
      return response()->json(['status'=>'200','message'=>'Profile Details','data'=>$emp_details,]);

  }
  public function session_logout(){
    $user = Auth::user()->token()->id;
    $data =  DB::table('oauth_access_tokens')->where('id', $user)->first();
    if($data->last_active_time == ''){
        DB::table('oauth_access_tokens')->where('id', $user)->update(['last_active_time'=>Carbon::now()]);
    }
    else{
      //return 'jjj';
        $currentTime = Carbon::now();
        $activeTime = Carbon::parse($data->last_active_time);
        $differenceInMinutes = $currentTime->diffInHours($activeTime);
       // return $differenceInMinutes;
        if($differenceInMinutes >4) {
          //return 'kkk';
            return response()->json(['status'=>'500']);
        }
        else {
            DB::table('oauth_access_tokens')->where('id', $user)->update(['last_active_time'=>Carbon::now()]);
        }
    }
}

public function store_idle_time(Request $request)
{
    
    $check_break_status = DB::table('store_employee_break_time')
        ->where('emp_id', $request->emp_id)
        ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
        ->orderBy('id', 'DESC')
        ->first();
       // return $check_break_status;

   
    if (!$check_break_status || $check_break_status->status == 0) {
        $data = [
            'emp_id' => $request->emp_id,
            'time_start_from' => $request->time_start,
            'time_end' => $request->time_end
        ];
        
        DB::table('store_idle_time')->insert($data);

        return response()->json(['status' => 200, 'message' => 'Idle time stored successfully']);
    }

    return response()->json(['status' => 400, 'message' => 'Cannot store idle time while on break']);
}
   public function store_break_time(Request $request)
   {
       $check_last_status = DB::table('store_employee_break_time')
           ->where('emp_id', $request->emp_id)
           ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
           ->orderBy('id', 'DESC')
           ->first();
   
       if ($check_last_status) {
           $status = $check_last_status->status;
       } else {
           $status = 0;
       }
   
       if ($request->status == 1) {
           $data = [
               'emp_id' => $request->emp_id,
               'start_time' => Carbon::now(),
               'status' => $request->status,
               'created_date' => Carbon::now()->format('Y-m-d'),
           ];
   
           DB::table('store_employee_break_time')->insert($data);
       } else {
           if ($check_last_status) {
               DB::table('store_employee_break_time')
                   ->where('id', $check_last_status->id)
                   ->update([
                       'end_time' => Carbon::now(),
                       'emp_id' => $request->emp_id,
                       'status' => $request->status,
                   ]);
           }
       }
   
       return response()->json(['status' => 200, 'current_status' => $status]);
   }
   
   public function get_break_time_status($emp_id)
{
    $check_last_status = DB::table('store_employee_break_time')
        ->where('emp_id', $emp_id)
        ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
        ->orderBy('id', 'DESC')
        ->first();

    $status = $check_last_status ? $check_last_status->status : 0;
    $total_break_time_in_minutes = 0;
    $total_idle_time_in_minutes = 0;

    $break_time_records = DB::table('store_employee_break_time')
        ->where('emp_id', $emp_id)
        ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
        ->get();
    
    $idle_time_records = DB::table('store_idle_time')
        ->where('emp_id', $emp_id)
        ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
        ->get();

    foreach ($idle_time_records as $idle) {
        if ($idle->time_start_from && $idle->time_end) {
            $start_time = new Carbon($idle->time_start_from);
            $end_time = new Carbon($idle->time_end);
            $total_idle_time_in_minutes += $end_time->diffInMinutes($start_time);
        }
    }

    foreach ($break_time_records as $row) {
        if ($row->start_time && $row->end_time) {
            $start_time = new Carbon($row->start_time);
            $end_time = new Carbon($row->end_time);
            $total_break_time_in_minutes += $end_time->diffInMinutes($start_time);
        }
    }

    // Convert minutes to hours and minutes format
    $break_hours = intdiv($total_break_time_in_minutes, 60);
    $break_minutes = $total_break_time_in_minutes % 60;
    $formatted_break_time = sprintf('%d:%02d', $break_hours, $break_minutes);

    $idle_hours = intdiv($total_idle_time_in_minutes, 60);
    $idle_minutes = $total_idle_time_in_minutes % 60;
    $formatted_idle_time = sprintf('%d:%02d', $idle_hours, $idle_minutes);

    return response()->json([
        'status' => 200,
        'break_status' => $status,
        'break_time' => $formatted_break_time,
        'idle_count' => $formatted_idle_time,
    ]);
}

public  function get_emp_idle_time(Request $request){
  if($request->employee){
    $emp_managers = [$request->employee];

  }
  else{
     $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
                      ->orWhere('dept_manager', $request->emp_id);
            })
            ->where('status', 1)
            ->pluck('emp_id')->toArray();
             $emp_managers[] = $request->emp_id;
             $emp_managers = array_unique($emp_managers);

  }

  //return  $emp_managers;

  if($request->type =='break'){
    $data = DB::table('store_employee_break_time')->whereIn('emp_id',$emp_managers)
    ->select('emp_id', DB::raw('DATE(created_date) as created_date'), DB::raw('SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_time'))
    ->groupBy('emp_id', DB::raw('DATE(created_date)'));

  }
  else{
     $data = DB::table('store_idle_time')->whereIn('emp_id',$emp_managers)
     ->select('emp_id', DB::raw('DATE(created_date) as created_date'), DB::raw('SUM(TIMESTAMPDIFF(MINUTE, time_start_from, time_end)) as total_time'))
    ->groupBy('emp_id', DB::raw('DATE(created_date)'));

  }
  if($request->start_date && $request->end_date){
    $query =  $data->whereBetween('created_date', [$request->start_date, $request->end_date])
    ->get();

  }
  else{
     $query = $data->whereMonth('created_date', Carbon::now()->month)->get();
     //return $query;

  }

 if (!empty($emp_managers)) {
        $total_time = $query->map(function ($record) use ($request) {
            $total_time_in_minutes = $record->total_time;
            $hours = intdiv($total_time_in_minutes, 60);
            $minutes = $total_time_in_minutes % 60;
            $formatted_time = sprintf('%d:%02d', $hours, $minutes);
            $emp_name = BasicInfo::where('emp_id', $record->emp_id)->first();

            return [
                'emp_id' => $record->emp_id,
                'created_date' => $record->created_date,
                'total_time' => $formatted_time,
                'emp_name' => $emp_name ? $emp_name->emp_fname . ' ' . $emp_name->emp_lname : 'N/A',
                'type' => $request->type,
            ];
        });

        return response()->json(['status' => 200, 'data' => $total_time]);
    }



   
}

public  function get_emp_break_time($id){
 $break_time_records = DB::table('store_employee_break_time')
    ->select('emp_id', DB::raw('DATE(created_date) as created_date'), DB::raw('SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_break_time'))
    ->where('emp_id', $id)
    ->groupBy('emp_id', DB::raw('DATE(created_date)'))
    ->get();
    
$break_time =  $break_time_records->map(function ($record) {
    $total_break_time_in_minutes = $record->total_break_time;
    $hours = intdiv($total_break_time_in_minutes, 60);
    $minutes = $total_break_time_in_minutes % 60;
    $formatted_break_time = sprintf('%d:%02d', $hours, $minutes);

    return [
        'emp_id' => $record->emp_id,
        'created_date' => $record->created_date,
        'total_break_time' =>  $formatted_break_time ,
    ];
});

return response()->json(['status'=>200,'data'=> $break_time]);
   
}

public function logout_emp(Request $request)
{
    $user = Auth::user()->token();
    $working_hours = '00:00:00';
    //$user->revoke(); 
    $logout_time = Carbon::now();
    $overtime_hours = '';
    $row = Attendance::where('emp_id', $request->emp_id)
        ->where('login_date_time', '!=', null)
        ->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))
        ->first();

    $emp_details = BasicInfo::where('emp_id', $request->emp_id)->first();
    $emp_branch = Branch::where('id', $emp_details->branch_id)->first();
    list($office_lat, $office_long) = explode(',', $emp_branch->branch_lat_long);
    $current_lat = $request->latitude;
    $current_long = $request->longitude;
    $lat_long = $current_lat . ',' . $current_long;

    if ($row) {
        if ($emp_details->dept_id != 3) {
            if ($row->punch_in_date_time == '') {
                $status = 0;
                $remark = 'Did Not Punch In';
                $working_hours = '00:00:00';
            } else {
                $punch_in_time = Carbon::parse($row->punch_in_date_time);
                $logout_time = Carbon::now();
                $diff = $punch_in_time->diff($logout_time);
                $totaltime = $diff->h * 60 + $diff->i;
                //$formatted_diff = $diff->format('%h:%i:%s');
                 //return $formatted_diff;
                if ($emp_details->dept_id == 9) {
                     $minutes_to_subtract = $row->idle_time + $row->break_time;
                     $minutes_to_subtract = round($minutes_to_subtract*60);

                     $totalMinutes = $totaltime-$minutes_to_subtract;
                     if ($adjusted_time < 0) {
                             $adjusted_time = 0;
                      }

                      $adjusted_hours = floor($totalMinutes / 60); 
                      $adjusted_remaining_minutes = $totalMinutes % 60; 
                      $formatted_diff = sprintf('%02d:%02d:%02d', $adjusted_hours, $adjusted_remaining_minutes, 0);

                      //return $adjusted_diff;
                }
                else{
                   //$diff = $punch_in_time->diff($logout_time);
                   $totalMinutes = $diff->h * 60 + $diff->i;
                   $formatted_diff = $diff->format('%h:%i:%s');

                }

                if ($totalMinutes >= 270 && $totalMinutes < 480) {
                    $status = 2;
                    $remark = 'Due To Less Working Hours Half Day Will Be Marked';
                    $working_hours = $formatted_diff;
                } elseif ($totalMinutes >= 480 && $totalMinutes < 540) {
                    $status = 3;
                    $remark = 'Due To Less Working Hours Short Leave Will Be Marked';
                    $working_hours = $formatted_diff;
                } elseif ($totalMinutes >= 540) {
                    $status = 1;
                    $working_hours = $formatted_diff;
                }
            }

            $distance = $this->haversineGreatCircleDistance($office_lat, $office_long, $current_lat, $current_long);

            if (floor($distance) <= $emp_branch->distance) {
                Attendance::where('emp_id', $request->emp_id)
                    ->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))
                    ->update([
                        'status' => $status,
                        'logout_lat_long' => $lat_long,
                        'remark' => $remark,
                        'logout_date_time' => $logout_time,
                        'total_hours' => $working_hours
                    ]);

                return response()->json(['status' => 200, 'message' => 'Logout Successfully']);
            } else {
                $check_wfh = WFH::where('employee_id', $request->emp_id)
                    ->where('days_from', Carbon::now()->format('Y-m-d'))
                    ->where('status', 1)
                    ->exists();

                if ($check_wfh) {
                    Attendance::where('emp_id', $request->emp_id)
                        ->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))
                        ->update([
                            'status' => $status,
                            'logout_lat_long' => $lat_long,
                            'remark' => $remark,
                            'logout_date_time' => $logout_time,
                            'total_hours' => $working_hours
                        ]);

                    return response()->json(['status' => 200, 'message' => 'Logout Successfully']);
                } else {
                    return response()->json([
                        'status' => 204,
                        'message' => "You might not be within the office radius",
                    ]);
                }
            }
        }
        else{
          return $this->calc_kra_kpi($request->emp_id,$row->punch_in_date_time,$row->idle_time,$row->break_time,$emp_branch->branch_lat_long,$current_lat,$current_long);

        }
    }
}


   private function haversineGreatCircleDistance($lat1, $long1, $lat2, $long2)
{
    $earthRadius = 6371000; 
    $lat1 = deg2rad($lat1);
    $long1 = deg2rad($long1);
    $lat2 = deg2rad($lat2);
    $long2 = deg2rad($long2);

    $latDiff = $lat2 - $lat1;
    $longDiff = $long2 - $long1;
    $a = sin($latDiff / 2) ** 2 + cos($lat1) * cos($lat2) * sin($longDiff / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

private function calc_kra_kpi($emp_id,$punch_in_date_time,$idle_time,$break_time,$branch_lat_long,$current_let,$current_long){

    $total_call_required = 0;
    $total_payments_followups_required = 0;

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

  $get_team = BasicInfo::where('reporting_manager',$emp_id)
              ->where('emp_status',1)->count();
  if($get_team>0){
    $total_team = BasicInfo::where('reporting_manager', $emp_id)
    ->where('emp_status', 1)
    ->whereRaw('DATEDIFF(CURRENT_DATE, emp_doj) > 15')
    ->count();

    $payment_for_followup =  DB::table('save_company_target')->where('subattribute_id',13)
       ->where('department_id',3)->where('financial_year',$financialYear)->first();

    if($payment_for_followup){
      $per_month_payment_followup = $payment_for_followup->child_attribute_value/12;
      $per_day_payment_followups = round($per_month_payment_followup/22);
      $total_payments_followups_required =  $per_day_payment_followups*$total_team;

    }
    $calls_required_data = DB::table('save_company_target')->where('subattribute_id',12)
      ->where('department_id',3)->where('financial_year',$financialYear)->first();

    if($calls_required_data){
      $per_month_call  = $calls_required_data->child_attribute_value/12;
      $per_day_call = round($per_month_call/22);
      $total_call_required =   $per_day_call*$total_team;

    }

       $get_team_members = DB::table('employee_managers')
         ->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id])
         ->where('status',1)->pluck('emp_id')->toArray();
         $get_team_members[]  = $emp_id;

     $get_total_payments_followups = DB::connection('sales_db')->table('clients_followup_log')->whereIn('created_by',$get_team_members)
                  ->whereDate('created_date',today()->format('Y-m-d'))->count();

     if($total_payments_followups_required>0){
        $acheived_percent = round($get_total_payments_followups
                        /$total_payments_followups_required)*100;

        if($acheived_percent>0 && $acheived_percent<=50){
          $status == 2;
          $remark = 'Due To Less Kra And Kpi Half Day Will Be Mark'

        }
         else if($acheived_percent>50 && $acheived_percent<=70){
          $status == 3;
          $remark = 'Due To Less Kra And Kpi Short Leave Will Be Mark'

        }

        else($acheived_percent>70){
          $status == 1;

        }


     }











    }

   else{

   }
 
}


}