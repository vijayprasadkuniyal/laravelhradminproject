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

  public function logout_emp($id){
      $remark = '';
      $user = Auth::user()->token();
      $user->revoke();
      $logout_time = Carbon::now();
      $overtime_hours = '';
      $row = Attendance::where('emp_id',$id)->where('login_date_time','!=',null)
     ->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))->first();
     if($row){
      $login_time = $row->login_date_time;
      $sign_out = Carbon::parse($logout_time)->format('H:i:s');
     $start = Carbon::parse($login_time)->toDateTimeString();
     $start = new Carbon($start);
     $end = Carbon::parse($logout_time)->toDateTimeString();
     $end = new Carbon($end);
     $working_hours = $start->diff($end)->format('%H:%I:%S');
     $diff_in_hour = $start->diff($end)->format('%H');
     if($diff_in_hour>=7 && $diff_in_hour<9){
      $status = 3;
      //short_leave
     }
     else if($diff_in_hour>=9){
      $status = 1;
      $overtime_hours = $diff_in_hour-9;

       //full_day
     }
     else if($diff_in_hour<7 && $diff_in_hour>=5 ){
      $status = 2;
      //half day
     }
     else{
      $status = 0;
      //absent
     }
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
      $get_team_member_count = DB::table('employee_managers')
      ->whereRaw('FIND_IN_SET(?, reporting_to)', [$id])
      ->where('status',1)->count();

       $payment_for_followup =  DB::table('save_company_target')->where('subattribute_id',13)
       ->where('department_id',3)->where('financial_year',$financialYear)->first();
       if($payment_for_followup){
         $per_month_payment_followup = $payment_for_followup->child_attribute_value/12;
         if($get_team_member_count>0){
            $per_day_payment_followup = round($per_month_payment_followup/22)*$get_team_member_count;

         }
         else{
            $per_day_payment_followup = round($per_month_payment_followup/22);
           }


       }
       $business_proposal =  DB::table('save_company_target')->where('subattribute_id',14)->where('department_id',3)
       ->where('financial_year',$financialYear)->first();
      if($business_proposal){
         $per_month_proposal =  $business_proposal->child_attribute_value/12;
         if($get_team_member_count>0){
            $per_day_proposal = round($per_month_proposal/22)*$get_team_member_count;

         }
         else{
            $per_day_proposal = round($per_month_proposal/22);
        }

      }
      $followup_for_meeting =  DB::table('save_company_target')->where('subattribute_id',15)
      ->where('department_id',3)->where('financial_year',$financialYear)->first();
      if($followup_for_meeting){
        $per_month_followup_for_meeting =  $followup_for_meeting->child_attribute_value/12;
        if($get_team_member_count>0){
            $per_day_followup_for_meeting = round($per_month_followup_for_meeting/22)*$get_team_member_count;

        }
        else{
            $per_day_followup_for_meeting = round($per_month_followup_for_meeting/22);
          }

      }
      $calls_required_data = DB::table('save_company_target')->where('subattribute_id',12)
      ->where('department_id',3)->where('financial_year',$financialYear)->first();
      if($calls_required_data){
         $per_month_call = $calls_required_data->child_attribute_value/12;
         if($get_team_member_count>0){
            $per_day_call = round($per_month_call/22)*$get_team_member_count;

         }
         else{
            $per_day_call = round($per_month_call/22);
          }
         }


     if($emp_details->dept_id ==3 ){
     if($emp_details->desi_id !=38){
      $get_data = DB::connection('sales_db')->table('clients_followup_log')->where('created_by',$id)
      ->whereDate('created_date',Carbon::now()->format('Y-m-d'));
      }
      else{
         $get_team_members = DB::table('employee_managers')
         ->whereRaw('FIND_IN_SET(?, reporting_to)', [$id])
         ->where('status',1)->pluck('emp_id')->toArray();
         $get_team_members[]  = $id;

         $get_data = DB::connection('sales_db')->table('clients_followup_log')->whereIn('created_by',$get_team_members)
                  ->whereDate('created_date',today()->format('Y-m-d'))->get();
       }
      $total_calls = (clone $get_data)->count();
      $total_payment_for_followup = (clone $get_data)->where('followup_id', 6)->count();
      $total_proposal = (clone $get_data)->where('followup_id', 5)->count();
      $total_meeting_fix = (clone $get_data)->where('followup_id', 7)->count();
      $call_percent =  round($total_calls/$per_day_call*100);
      $proposal_percent =  round($total_proposal/$per_day_proposal*100);
      $payment_for_followup_percent =  round($total_payment_for_followup/$per_day_payment_followup*100);
      $meeting_percent =  round($total_meeting_fix/$per_day_followup_for_meeting*100);
      $weekday = Carbon::now()->format('l');
      $check_leave = Leave::where('date',Carbon::now()->format('Y-m-d'))->exists();
      $check_emp_leave = Attendance::where('attendance_date',Carbon::now()->format('Y-m-d'))->where('leave_status',1)->where('emp_id',$id)->exists();
      if(!$check_leave && !$check_emp_leave && $weekday!='Sunday' && $weekday!='Saturday'){
        if( $call_percent<25 ||  $proposal_percent<25 || $payment_for_followup_percent<20 || $meeting_percent<25){
          $status = 0;
          $remark = 'Due To Low Kra and kpi Absent Will be Mark';
        }
         }
        }
       Attendance::where('emp_id',$id)->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))
       ->update(['logout_date_time'=>$logout_time,'status'=>$status,'total_hours'=>$working_hours,'overtime_hours'=>$overtime_hours,'remark'=>$remark]);
      

     }
      $logout_details = array('emp_id'=>$id,'logout_time'=>Carbon::now('Asia/Kolkata'));
       LoginHistory::insert($logout_details);
      return response()->json(['status'=>'200','message'=>'logout successfully']);
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
}