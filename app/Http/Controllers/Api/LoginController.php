<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BasicInfo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Branch;
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
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'messages' => $validator->errors(),
            ]);
        }
    
        $emp = BasicInfo::where('login_id',$request->email)
            ->where('emp_status', 1)
            ->first();
    
        if (!$emp) {
            return response()->json([
                'status' => 400,
                'message' => 'Incorrect Email or Account Inactive',
            ]);
        }
    
        if (!Hash::check($request->input('password'), $emp->login_password)) {
            return response()->json([
                'status' => 400,
                'message' => 'Incorrect Password',
            ]);
        }
    
        $lastPasswordUpdate = DB::table('password_change_history')
            ->where('emp_id', $emp->emp_id)
            ->orderBy('id', 'DESC')
            ->first();
    
        $daysSinceLastUpdate = $lastPasswordUpdate
            ? Carbon::now()->diffInDays(Carbon::parse($lastPasswordUpdate->created_date))
            : 'not found';
    
        $token = $emp->createToken('Laravel Password Grant Client')->accessToken;
    
        $department = Department::where('id', $emp->dept_id)->value('department_name');
        $ipAddress = $request->ip;
        $access_status = 'No';

        $cs_panel_access = Branch::where('id',$emp->branch_id)
        ->whereRaw('FIND_IN_SET(?,ip_address)', [$ipAddress])->first();
        if($cs_panel_access){
            $access_status = 'Yes';

        }
        else{
            $check_work_from_home = WFH::where('employee_id',$emp->emp_id)
                                   ->whereDate('days_from',Carbon::now()->format('Y-m-d'))->where('status',1)->first();
            if($check_work_from_home){
                $access_status = 'Yes';

            }
            else{
                $access_status = 'No';
            }
           }
           $data = [
            'id' => $emp->id,
            'name' => $emp->emp_fname,
            'emp_id' => $emp->emp_id,
            'last_password_update_days' => $daysSinceLastUpdate,
            'branch' => $emp->branch_id,
            'department' => $department,
            'email' => $emp->login_id,
            'ip' => $ipAddress,
            'department_id' => $emp->dept_id,
            'desi_id' => $emp->desi_id,
            'cs_panel_access'=>$access_status,
        ];

        //return  $data;
    
        $attendance = Attendance::whereDate('attendance_date', Carbon::today())
         ->where('emp_id', $emp->emp_id)
         ->whereNull('login_date_time')
         ->first();

          if ($attendance) {
            $attendance->update([
                'login_date_time' => Carbon::now(),
                'ip_address' => $ipAddress,
                'login_device' => $request->header('User-Agent'),
            ]);
        }
        LoginHistory::create([
            'emp_id' => $emp->emp_id,
            'login_time' => Carbon::now('Asia/Kolkata'),
        ]);
    
        return response()->json([
            'status' => 200,
            'message' => 'Logged In Successfully',
            'token' => $token,
            'data' => $data,
        ]);
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
    //return $request->all();
    $check_break_status = DB::table('store_employee_break_time')
        ->where('emp_id', $request->emp_id)
        ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
        ->orderBy('id', 'DESC')
        ->first();

    $check_punch_in_time = Attendance::where('emp_id',$request->emp_id)
                         ->whereDate('attendance_date',Carbon::now()->format('Y-m-d'))
                         ->whereNotNull('punch_in_date_time')
                         ->first();

   
    if ((!$check_break_status || $check_break_status->status == 0) && $check_punch_in_time) {
        $startTime = Carbon::parse($request->start_time);
        $endTime = Carbon::parse($request->end_time);
        $idleTimeMinutes = $startTime->diffInMinutes($endTime);
        $data = [
            'emp_id' => $request->emp_id,
            'time_start_from' => $request->start_time,
            'time_end' => $request->end_time,
            'idle_time'=>$idleTimeMinutes,
            'date'=>Carbon::now()->format('Y-m-d'),
        ];
        
        DB::table('store_idle_time')->insert($data);
        $total_idle_time = DB::table('store_idle_time')->where('emp_id',$request->emp_id)
               ->where('date',Carbon::now()->format('Y-m-d'))->sum('idle_time');

        $hours = floor($total_idle_time / 60);
        $minutes = $total_idle_time % 60;
               
        $formatted_idle_time = sprintf('%02d:%02d', $hours, $minutes);
        
        Attendance::where('emp_id',$request->emp_id)
        ->where('attendance_date',Carbon::now()->format('Y-m-d'))->update(['idle_time'=>$formatted_idle_time]);
        
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

        $break_type_details = DB::table('break_time_history')->where('id',$request->break_type)->first();

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
               'created_date' => Carbon::now(),
               'date'=> Carbon::now()->format('Y-m-d'),
               'break_type'=>$request->break_type,
               'meeting_reason'=>$request->meeting_reason,
               'expected_end_time'=>Carbon::now()->addMinutes($break_type_details->time)
           ];
   
           DB::table('store_employee_break_time')->insert($data);
       } else {
           if ($check_last_status) {
              $start_time_for_break = Carbon::parse($check_last_status->start_time);
              $break_end_time = Carbon::now();
              $diffInMinutes = $start_time_for_break->diffInMinutes($break_end_time);
              if(Carbon::parse($check_last_status->expected_end_time)<$break_end_time){
                $extra_time = Carbon::parse($check_last_status->expected_end_time)->diffInMinutes($break_end_time);

              }
              else{
                $extra_time = 0;
              }
              
               DB::table('store_employee_break_time')
                   ->where('id', $check_last_status->id)
                   ->update([
                       'end_time' => Carbon::now(),
                       'emp_id' => $request->emp_id,
                       'status' => $request->status,
                       'total_time'=>$diffInMinutes,
                       'extra_break_time'=>$extra_time
                   ]);
           }
       }
       $total_break_time =  DB::table('store_employee_break_time')->where('emp_id',$request->emp_id)
       ->where('date',Carbon::now()->format('Y-m-d'))->sum('extra_break_time');

        $hours = floor($total_break_time / 60);
        $minutes = $total_break_time % 60;
        $formatted_idle_time = sprintf('%02d:%02d', $hours, $minutes);

        Attendance::where('emp_id',$request->emp_id)
        ->where('attendance_date',Carbon::now()->format('Y-m-d'))
        ->update(['break_time'=>$formatted_idle_time]);

        return response()->json(['status' => 200, 'current_status' => $status]);
   }
   
   public function get_break_time_status($emp_id)
{
    $check_last_status = DB::table('store_employee_break_time')
        ->where('emp_id', $emp_id)
        ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
        ->orderBy('id', 'DESC')
        ->first();
    if($check_last_status && $check_last_status->status == 0){
        $break_type = '';
        $status = 0;

    }
    else if($check_last_status && $check_last_status->status ==1){
      $break_type = $check_last_status->break_type;
      $status = 1;

    }
    else{
        $break_type = '';
         $status = 0;

    }

    return response()->json([
        'status' => 200,
        'break_status' => $status,
        'break_type'=>$break_type,
    ]);
}

// public  function get_emp_idle_time(Request $request){
//   if($request->employee){
//     $emp_managers = [$request->employee];

//   }
//   else{
//      $emp_managers = DB::table('employee_managers')
//             ->where(function($query) use ($request) {
//                 $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
//                       ->orWhere('dept_manager', $request->emp_id);
//             })
//             ->where('status', 1)
//             ->pluck('emp_id')->toArray();
//              $emp_managers[] = $request->emp_id;
//              $emp_managers = array_unique($emp_managers);

//   }

//   //return  $emp_managers;

//   if($request->type =='break'){
//     $data = DB::table('store_employee_break_time')->whereIn('emp_id',$emp_managers)
//     ->select('emp_id', DB::raw('DATE(created_date) as created_date'), DB::raw('SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_time'))
//     ->groupBy('emp_id', DB::raw('DATE(created_date)'));

//   }
//   else{
//      $data = DB::table('store_idle_time')->whereIn('emp_id',$emp_managers)
//      ->select('emp_id', DB::raw('DATE(created_date) as created_date'), DB::raw('SUM(TIMESTAMPDIFF(MINUTE, time_start_from, time_end)) as total_time'))
//     ->groupBy('emp_id', DB::raw('DATE(created_date)'));

//   }
//   if($request->start_date && $request->end_date){
//     $query =  $data->whereBetween('created_date', [$request->start_date, $request->end_date])
//     ->get();

//   }
//   else{
//      $query = $data->whereMonth('created_date', Carbon::now()->month)->get();
//      //return $query;

//   }

//  if (!empty($emp_managers)) {
//         $total_time = $query->map(function ($record) use ($request) {
//             $total_time_in_minutes = $record->total_time;
//             $hours = intdiv($total_time_in_minutes, 60);
//             $minutes = $total_time_in_minutes % 60;
//             $formatted_time = sprintf('%d:%02d', $hours, $minutes);
//             $emp_name = BasicInfo::where('emp_id', $record->emp_id)->first();

//             return [
//                 'emp_id' => $record->emp_id,
//                 'created_date' => $record->created_date,
//                 'total_time' => $formatted_time,
//                 'emp_name' => $emp_name ? $emp_name->emp_fname . ' ' . $emp_name->emp_lname : 'N/A',
//                 'type' => $request->type,
//             ];
//         });

//         return response()->json(['status' => 200, 'data' => $total_time]);
//     }



   
// }

// public  function get_emp_break_time($id){
//  $break_time_records = DB::table('store_employee_break_time')
//     ->select('emp_id', DB::raw('DATE(created_date) as created_date'), DB::raw('SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_break_time'))
//     ->where('emp_id', $id)
//     ->groupBy('emp_id', DB::raw('DATE(created_date)'))
//     ->get();
    
// $break_time =  $break_time_records->map(function ($record) {
//     $total_break_time_in_minutes = $record->total_break_time;
//     $hours = intdiv($total_break_time_in_minutes, 60);
//     $minutes = $total_break_time_in_minutes % 60;
//     $formatted_break_time = sprintf('%d:%02d', $hours, $minutes);

//     return [
//         'emp_id' => $record->emp_id,
//         'created_date' => $record->created_date,
//         'total_break_time' =>  $formatted_break_time ,
//     ];
// });

 //return response()->json(['status'=>200,'data'=> $break_time]);
   
//}
public function break_time_report(Request $request)
{
    $emp_managers = DB::table('employee_managers')
        ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
        ->where('status', 1)
        ->pluck('emp_id')
        ->toArray();

    $emp_managers[] = $request->emp_id;
    $emp_managers = array_unique($emp_managers);
    //return $emp_managers;

    $query = DB::table('store_employee_break_time')
        ->leftJoin('emp_basic_info', 'emp_basic_info.emp_id', '=', 'store_employee_break_time.emp_id')
        ->leftJoin('department', 'department.id', '=', 'emp_basic_info.dept_id') 
        ->leftJoin('break_time_history','break_time_history.id','store_employee_break_time.break_type')
        ->select(
            'store_employee_break_time.emp_id', 
            'store_employee_break_time.date', 
            DB::raw('SUM(store_employee_break_time.total_time) as total_time'),
            DB::raw('SUM(store_employee_break_time.extra_break_time) as total_extra_time'),
            'emp_basic_info.emp_fname', 
            'emp_basic_info.emp_lame', 
            'department.department_name',
        )
        ->groupBy('store_employee_break_time.emp_id', 'store_employee_break_time.date',
         'emp_basic_info.emp_fname', 'emp_basic_info.emp_lame', 'department.department_name',)
        ->orderBy('store_employee_break_time.date', 'DESC');
    if ($request->emp_id) {
        $query->where('store_employee_break_time.emp_id', $request->emp_id);
    } else {
        $query->whereIn('store_employee_break_time.emp_id', $emp_managers);
    }

    $result = $query->paginate(10);

    $result->getCollection()->transform(function ($item) {
        $total_hours = floor($item->total_time / 60);
        $total_minutes = $item->total_time % 60;
        $formatted_total_time = sprintf('%02d:%02d',$total_hours, $total_minutes);

        $extra_hours = floor($item->total_extra_time/ 60);
        $extra_minutes = $item->total_extra_time % 60;
        $formatted_extra_time = sprintf('%02d:%02d',$extra_hours,$extra_minutes);

        $item->total_time_in_hours = $formatted_total_time;
        $item->extra_time_in_hours = $formatted_extra_time;
        return $item;
    });

    return response()->json(['status'=>200,'data'=>$result,'last_page'=>$result->lastPage()]);
}
public function get_break_time_details(Request $request){
    $data = DB::table('store_employee_break_time')
             ->leftJoin('break_time_history','break_time_history.id','store_employee_break_time.break_type')
             ->select('store_employee_break_time.*','break_time_history.break_name')
            ->where('emp_id',$request->emp_id)
            ->where('date',$request->date)->get();
    return response()->json(['status'=>200,'data'=>$data]);
}

public function idle_time_report(Request $request)
{
    $emp_managers = DB::table('employee_managers')
        ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
        ->where('status', 1)
        ->pluck('emp_id')
        ->toArray();

    $emp_managers[] = $request->emp_id;
    $emp_managers = array_unique($emp_managers);
    //return $emp_managers;

    $query = DB::table('store_idle_time')
        ->leftJoin('emp_basic_info', 'emp_basic_info.emp_id', '=', 'store_idle_time.emp_id')
        ->leftJoin('department', 'department.id', '=', 'emp_basic_info.dept_id') 
        ->select(
            'store_idle_time.emp_id', 
            'store_idle_time.date', 
            DB::raw('SUM(store_idle_time.idle_time) as total_time'),
            'emp_basic_info.emp_fname', 
            'emp_basic_info.emp_lame', 
            'department.department_name',
        )
        ->groupBy('store_idle_time.emp_id', 'store_idle_time.date',
         'emp_basic_info.emp_fname', 'emp_basic_info.emp_lame', 'department.department_name',)
      ->orderBy('store_idle_time.date', 'DESC');

    if ($request->emp_id) {
        $query->where('store_idle_time.emp_id', $request->emp_id);
    } else {
        $query->whereIn('store_idle_time.emp_id', $emp_managers);
    }

    $result = $query->paginate(10);

    $result->getCollection()->transform(function ($item) {
        $total_hours = floor($item->total_time / 60);
        $total_minutes = $item->total_time % 60;
        $formatted_total_time = sprintf('%02d:%02d',$total_hours, $total_minutes);

        $item->total_time_in_hours = $formatted_total_time;
        return $item;
    });

    return response()->json(['status'=>200,'data'=>$result,'last_page'=>$result->lastPage()]);
}
public function get_idle_time_details(Request $request){
    $data = DB::table('store_idle_time')
            ->where('emp_id',$request->emp_id)
            ->whereDate('date',$request->date)->get();
    return response()->json(['status'=>200,'data'=>$data]);
}

public function break_attributes_name(){
    $data = DB::table('break_time_history')->where('status',1)->get();
    return response()->json(['status'=>200,'data'=>$data]);

}

public function logout_emp(Request $request)
{
    $user = Auth::user()->token();
    $working_hours = '00:00:00';
    //$user->revoke(); 
    $logout_time = Carbon::now()->format('Y-m-d H:i:s');
    $overtime_hours = '';
    $row = Attendance::where('emp_id', $request->emp_id)
        ->where('login_date_time', '!=', null)
        ->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))
        ->first();

    $emp_details = BasicInfo::where('emp_id', $request->emp_id)->first();
    //return $emp_details;
    $emp_branch = Branch::where('id', $emp_details->branch_id)->first();
    //return $emp_branch;
    list($office_lat, $office_long) = explode(',', $emp_branch->branch_lat_long);
    $current_lat = $request->latitude;
    $current_long = $request->longitude;
    $lat_long = $current_lat . ',' . $current_long;

    if ($row) {
        if ($emp_details->dept_id != 3) {
            if ($row->punch_in_date_time == '') {
                $status = 0;
                $remark = 'Less Working Hours';
                $working_hours = '00:00:00';
                $status_name = 'Absent';
            } else {
                $punch_in_time = Carbon::parse($row->punch_in_date_time);
                $logout_time = Carbon::now()->format('Y-m-d H:i:s');
                $diff = $punch_in_time->diff($logout_time);
                $totaltime = $diff->h * 60 + $diff->i;
                //$formatted_diff = $diff->format('%h:%i:%s');
                 //return $formatted_diff;
                if ($emp_details->dept_id ==4) {
                     $idle_time = $row->idle_time;
                     list($idle_hours, $idle_minutes) = explode(":",$idle_time);
                     $totalidleMinutes = ($idle_hours * 60) + $idle_minutes;

                     $break_time = $row->break_time;
                     list($break_hours, $break_minutes) = explode(":",$break_time);
                     $totalbreakMinutes = ($break_hours * 60) + $break_minutes;

                     $minutes_to_subtract = $totalidleMinutes + $totalbreakMinutes;

                     $totalMinutes = $totaltime-$minutes_to_subtract;
                     if ($totalMinutes < 0) {
                             $totalMinutes = 0;
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

                if ($totalMinutes >=270 && $totalMinutes<480) {
                    $status = 2;
                    $remark = 'Due To Less Working Hours Half Day Will Be Marked';
                    $status_name = 'Half Day';
                    $working_hours = $formatted_diff;

                } elseif ($totalMinutes >=480 && $totalMinutes<540) {
                    $status = 3;
                    $remark = 'Due To Less Working Hours Short Leave Will Be Marked';
                    $working_hours = $formatted_diff;
                    $status_name = 'Short Leave';

                } elseif ($totalMinutes >= 540) {
                    $status = 1;
                    $working_hours = $formatted_diff;
                    $status_name = 'Present';
                    $remark = '';
                }
                else{
                    $status = 0;
                    $status_name = 'Absent';
                    $working_hours = $formatted_diff;
                    $remark = 'Less Working Hours';

                }
            }

            $distance = $this->haversineGreatCircleDistance($office_lat, $office_long, $current_lat, $current_long);
            //return $distance;

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
                    $data = array('status_name'=>$status_name,'login_time'=>$row->login_date_time,
                            'punch_in'=>$row->punch_in_date_time,'total_hours'=> $working_hours,'remark'=>$remark,
                            'logout_time'=>$logout_time);

                return response()->json(['status' => 200,'data'=>$data]);
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
                        $data = array('status_name'=>$status_name,'login_time'=>$row->login_date_time,
                            'punch_in'=>$row->punch_in_date_time,'total_hours'=> $working_hours,'remark'=>$remark,
                            'logout_time'=>$logout_time);

                    return response()->json(['status' => 200,'data'=>$data]);
                } else {
                    $data = array('status_name'=>$status_name,'login_time'=>$row->login_date_time,
                            'punch_in'=>$row->punch_in_date_time,'total_hours'=>$working_hours,'remark'=>$remark,
                            'logout_time'=>$logout_time);

                    return response()->json([
                        'status' => 200,
                        'message' => "You might not be within the office radius So Logout Time Not Updated ",
                        'data'=>$data,
                    ]);
                }
            }
        }
        else{
          return $this->calc_kra_kpi($request->emp_id,$row->punch_in_date_time,$row->idle_time,$row->break_time,
                  $emp_branch->branch_lat_long,$current_lat,$current_long,$emp_branch->distance,$row->login_date_time);

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

private function calc_kra_kpi($emp_id,$punch_in_date_time,$idle_time,$break_time,$branch_lat_long,$current_let,$current_long,
$distance_in_m,$login_date_time){
    $total_call_required = 0;
    $total_payments_followups_required = 0;

    list($office_lat, $office_long) = explode(',', $branch_lat_long);

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

        $get_total_payments_followups = DB::connection('sales_db')->table('clients_followup_log')
                                        ->whereIn('created_by',$get_team_members)
                                        ->whereIn('followup_id',[6,9])
                                        ->whereDate('created_date',today()->format('Y-m-d'))
                                        ->count();

        $get_total_call_required = DB::connection('sales_db')->table('clients_followup_log')
                                        ->whereIn('created_by',$get_team_members)
                                        ->where('disposition','Connected')
                                        ->whereDate('created_date',today()->format('Y-m-d'))
                                        ->count();
        

     if($total_payments_followups_required>0 && $total_call_required>0){
        $acheived_percent_payment_followups  = round($get_total_payments_followups
                        /$total_payments_followups_required)*100;

        $acheived_percent_call_required  = round($get_total_call_required
                        /$total_call_required)*100;
       }
       else{
        $acheived_percent_payment_followups = 100;
        $acheived_percent_call_required = 100;

       }
       //return $get_total_payments_followups;

        if ($punch_in_date_time == '') {
            $status = 0;
            $remark = 'Less Working Hours';
            $working_hours = '00:00:00';
            $status_name = 'Absent';
            }
        else{
            $punch_in_time = Carbon::parse($punch_in_date_time);
            $logout_time = Carbon::now()->format('Y-m-d H:i:s');
            $diff = $punch_in_time->diff($logout_time);
            $totaltime = $diff->h * 60 + $diff->i;

             //$idle_time = $idle_time;
             list($idle_hours, $idle_minutes) = explode(":",$idle_time);
             $totalidleMinutes = ($idle_hours * 60) + $idle_minutes;

             //$break_time = $row->break_time;
             list($break_hours, $break_minutes) = explode(":",$break_time);
             $totalbreakMinutes = ($break_hours * 60) + $break_minutes;
             $minutes_to_subtract = $totalidleMinutes + $totalbreakMinutes;

             $totalMinutes = $totaltime - $minutes_to_subtract;
            if($totalMinutes < 0) {
                  $totalMinutes = 0;
                }

            $adjusted_hours = floor($totalMinutes / 60); 
            $adjusted_remaining_minutes = $totalMinutes % 60; 
            $formatted_diff = sprintf('%02d:%02d:%02d', $adjusted_hours, $adjusted_remaining_minutes, 0);

            if($totalMinutes>=270 && $totalMinutes <420 && $acheived_percent_payment_followups>=50) {
                $status = 2;
                $remark = 'Due To Less Working Hours Half Day Will Be Marked';
                $working_hours = $formatted_diff;
                $status_name = 'Half Day';
            }

            elseif ($totalMinutes >=270 && $totalMinutes <420 && $acheived_percent_payment_followups>=30 &&
                         $acheived_percent_call_required>=20) {
                            
                $status = 2;
                $remark = 'Due To Less Working Hours Half Day Will Be Marked';
                $working_hours = $formatted_diff;
                $status_name = 'Half Day';
            }

            elseif($totalMinutes>=420 && $acheived_percent_payment_followups>=60) {
                $status = 1;
                $remark = '';
                $working_hours = $formatted_diff;
                $status_name = 'Present';
            }

            elseif($totalMinutes>=420 && $acheived_percent_payment_followups>=50 && $acheived_percent_payment_followups<60 ) {
                $status = 2;
                $remark = '';
                $working_hours = $formatted_diff;
                $status_name = 'Half Day';
            }

            else{
                $status = 0;
                $remark = 'Due To Less Kra Kpi or working hours Absent Will Be Marked';
                $working_hours = $formatted_diff;
                $status_name = 'Absent';
            }

           }
           //return  $remark;
           $distance = $this->haversineGreatCircleDistance($office_lat, $office_long, $current_let, $current_long);

           if (floor($distance) <= $distance_in_m) {
                Attendance::where('emp_id', $emp_id)
                ->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))
                ->update([
                    'status' => $status,
                    'logout_lat_long' => $branch_lat_long,
                    'remark' => $remark,
                    'logout_date_time' => $logout_time,
                    'total_hours' => $working_hours,
                ]);

                $data = array('status_name'=>$status_name,'login_time'=>$login_date_time,
                            'punch_in'=>$punch_in_date_time,'total_hours'=> $working_hours,'remark'=>$remark,
                            'logout_time'=>$logout_time);

            return response()->json(['status' => 200, 'message' => 'Logout Successfully','data'=>$data]);
        } else {
            $check_wfh = WFH::where('employee_id', $emp_id)
                ->where('days_from', Carbon::now()->format('Y-m-d'))
                ->where('status', 1)
                ->exists();

            if ($check_wfh) {
                Attendance::where('emp_id', $emp_id)
                    ->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))
                    ->update([
                        'status' => $status,
                        'logout_lat_long' => $branch_lat_long,
                        'remark' => $remark,
                        'logout_date_time' => $logout_time,
                        'total_hours' => $working_hours,
                    ]);
                    $data = array('status_name'=>$status_name,'login_time'=>$login_date_time,
                            'punch_in'=>$punch_in_date_time,'total_hours'=> $working_hours,'remark'=>$remark,
                            'logout_time'=>$logout_time);

                return response()->json(['status' => 200, 'message' => 'Logout Successfully','data'=>$data]);
            } else {
                $data = array('status_name'=>$status_name,'login_time'=>$login_date_time,
                            'punch_in'=>$punch_in_date_time,'total_hours'=> $working_hours,'remark'=>$remark,
                            'logout_time'=>$logout_time);

                return response()->json([
                    'status' => 200,
                    'message' => "You might not be within the office radius",
                    'data'=>$data,
                ]);
            }
        }

    }

   else{
    $emp_join_date = BasicInfo::where('emp_id',$emp_id)->whereRaw('DATEDIFF(CURRENT_DATE, emp_doj) > 15')->first();
    if(!$emp_join_date){
        if(!$punch_in_date_time){
            $status = 0;
            $remark = 'Less Working Hours';
            $working_hours = '00:00:00';
            $status_name = 'Absent';
        }
        else{
            $punch_in_time = Carbon::parse($punch_in_date_time);
            $logout_time = Carbon::now();
            $diff = $punch_in_time->diff($logout_time);
            $totaltime = $diff->h * 60 + $diff->i;
             list($idle_hours, $idle_minutes) = explode(":",$idle_time);
             $totalidleMinutes = ($idle_hours * 60) + $idle_minutes;

             //$break_time = $row->break_time;
             list($break_hours, $break_minutes) = explode(":",$break_time);
             $totalbreakMinutes = ($break_hours * 60) + $break_minutes;

             $minutes_to_subtract = $totalidleMinutes + $totalbreakMinutes;
             $totalMinutes = $totaltime - $minutes_to_subtract;
            if($totalMinutes < 0) {
                  $totalMinutes = 0;
                }

            $adjusted_hours = floor($totalMinutes / 60); 
            $adjusted_remaining_minutes = $totalMinutes % 60; 
            $formatted_diff = sprintf('%02d:%02d:%02d', $adjusted_hours, $adjusted_remaining_minutes, 0);

             if ($totalMinutes >=270 && $totalMinutes<480) {
                    $status = 2;
                    $remark = 'Due To Less Working Hours Half Day Will Be Marked';
                    $status_name = 'Half Day';
                    $working_hours = $formatted_diff;

                } elseif ($totalMinutes >=480 && $totalMinutes<540) {
                    $status = 3;
                    $remark = 'Due To Less Working Hours Short Leave Will Be Marked';
                    $working_hours = $formatted_diff;
                    $status_name = 'Short Leave';

                } elseif ($totalMinutes >= 540) {
                    $status = 1;
                    $working_hours = $formatted_diff;
                    $status_name = 'Present';
                    $remark = '';
                }
                else{
                    $status = 0;
                    $status_name = 'Absent';
                    $working_hours = $formatted_diff;
                    $remark = 'Less Working Hours';

                }

          }
         }
    else{
        $payment_for_followup =  DB::table('save_company_target')->where('subattribute_id',13)
        ->where('department_id',3)->where('financial_year',$financialYear)->first();
 
     if($payment_for_followup){
       $per_month_payment_followup = $payment_for_followup->child_attribute_value/12;
       $per_day_payment_followups = round($per_month_payment_followup/22);
       $total_payments_followups_required =  $per_day_payment_followups;
 
     }
     $calls_required_data = DB::table('save_company_target')->where('subattribute_id',12)
       ->where('department_id',3)->where('financial_year',$financialYear)->first();
 
     if($calls_required_data){
       $per_month_call  = $calls_required_data->child_attribute_value/12;
       $per_day_call = round($per_month_call/22);
       $total_call_required =   $per_day_call;
       //return $total_call_required;
 
     }
 
    $get_total_payments_followups = DB::connection('sales_db')->table('clients_followup_log')
                                         ->where('created_by',$emp_id)
                                         ->whereIn('followup_id',[6,9])
                                         ->whereDate('created_date',today()->format('Y-m-d'))
                                         ->count();
 
    $get_total_call_required = DB::connection('sales_db')->table('clients_followup_log')
                                         ->where('created_by',$emp_id)
                                         ->where('disposition','Connected')
                                         ->whereDate('created_date',today()->format('Y-m-d'))
                                         ->count();
         
 
      if($total_payments_followups_required>0 && $total_call_required>0){
         $acheived_percent_payment_followups  = round($get_total_payments_followups
                         /$total_payments_followups_required)*100;
 
         $acheived_percent_call_required  = round($get_total_call_required
                         /$total_call_required)*100;
        }
        else{
         $acheived_percent_payment_followups = 100;
         $acheived_percent_call_required = 100;
 
        }
 
         if ($punch_in_date_time == '') {
             $status = 0;
             $remark = 'Less Working Hours';
             $working_hours = '00:00:00';
             $status_name = 'Absent';
             }
         else{
             $punch_in_time = Carbon::parse($punch_in_date_time);
             $logout_time = Carbon::now()->format('Y-m-d H:i:s');
             $diff = $punch_in_time->diff($logout_time);
             $totaltime = $diff->h * 60 + $diff->i;
             list($idle_hours, $idle_minutes) = explode(":",$idle_time);
             $totalidleMinutes = ($idle_hours * 60) + $idle_minutes;

             //$break_time = $row->break_time;
             list($break_hours, $break_minutes) = explode(":",$break_time);
             $totalbreakMinutes = ($break_hours * 60) + $break_minutes;

             $minutes_to_subtract = $totalidleMinutes + $totalbreakMinutes;
             $totalMinutes = $totaltime - $minutes_to_subtract;
             if($totalMinutes < 0) {
                   $totalMinutes = 0;
                 }
 
             $adjusted_hours = floor($totalMinutes / 60); 
             $adjusted_remaining_minutes = $totalMinutes % 60; 
             $formatted_diff = sprintf('%02d:%02d:%02d', $adjusted_hours, $adjusted_remaining_minutes, 0);
 
              if($totalMinutes>=270 && $totalMinutes <420 && $acheived_percent_payment_followups>=50) {
                $status = 2;
                $remark = 'Due To Less Working Hours Half Day Will Be Marked';
                $working_hours = $formatted_diff;
                $status_name = 'Half Day';
            }

            elseif ($totalMinutes >=270 && $totalMinutes <420 && $acheived_percent_payment_followups>=30 &&
                         $acheived_percent_call_required>=20) {
                            
                $status = 2;
                $remark = 'Due To Less Working Hours Half Day Will Be Marked';
                $working_hours = $formatted_diff;
                $status_name = 'Half Day';
            }

            elseif($totalMinutes>=420 && $acheived_percent_payment_followups>=60) {
                $status = 1;
                $remark = '';
                $working_hours = $formatted_diff;
                $status_name = 'Present';
            }

            elseif($totalMinutes>=420 && $acheived_percent_payment_followups>=50 && $acheived_percent_payment_followups<60 ) {
                $status = 2;
                $remark = '';
                $working_hours = $formatted_diff;
                $status_name = 'Half Day';
            }

            else{
                $status = 0;
                $remark = 'Due To Less Kra Kpi or working hours Absent Will Be Marked';
                $working_hours = $formatted_diff;
                $status_name = 'Absent';
            }
 
            }
            //return  $remark;
            $distance = $this->haversineGreatCircleDistance($office_lat, $office_long, $current_let, $current_long);
 
            if (floor($distance) <= $distance_in_m) {
                 Attendance::where('emp_id', $emp_id)
                 ->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))
                 ->update([
                     'status' => $status,
                     'logout_lat_long' => $branch_lat_long,
                     'remark' => $remark,
                     'logout_date_time' => $logout_time,
                     'total_hours' => $working_hours
                 ]);

                 $data = array('status_name'=>$status_name,'login_time'=>$login_date_time,
                 'punch_in'=>$punch_in_date_time,'total_hours'=> $working_hours,'remark'=>$remark,
                 'logout_time'=>$logout_time);
 
             return response()->json(['status' => 200, 'message' => 'Logout Successfully','data'=>$data]);
         } else {
             $check_wfh = WFH::where('employee_id', $emp_id)
                 ->where('days_from', Carbon::now()->format('Y-m-d'))
                 ->where('status', 1)
                 ->exists();
 
             if ($check_wfh) {
                 Attendance::where('emp_id', $emp_id)
                     ->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))
                     ->update([
                         'status' => $status,
                         'logout_lat_long' => $branch_lat_long,
                         'remark' => $remark,
                         'logout_date_time' => $logout_time,
                         'total_hours' => $working_hours
                     ]);
                     $data = array('status_name'=>$status_name,'login_time'=>$login_date_time,
                     'punch_in'=>$punch_in_date_time,'total_hours'=> $working_hours,'remark'=>$remark,
                     'logout_time'=>$logout_time);
     
                 return response()->json(['status' => 200, 'message' => 'Logout Successfully','data'=>$data]);
             } else {
                $data = array('status_name'=>$status_name,'login_time'=>$login_date_time,
                     'punch_in'=>$punch_in_date_time,'total_hours'=> $working_hours,'remark'=>$remark,
                     'logout_time'=>$logout_time);

                 return response()->json([
                     'status' => 200,
                     'message' => "You might not be within the office radius",
                     'data'=>$data,
                 ]);
             }
         }

        ////
        }
    }
 }

}