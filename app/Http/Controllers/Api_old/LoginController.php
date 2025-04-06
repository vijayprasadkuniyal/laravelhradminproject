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
      //$agent = new Agent();

      if ($validator->fails()) {
          $messages = $validator->messages();
          return response()->json(["messages" => $messages, 'status' => 400]);
      }

      $emp = BasicInfo::where('login_id', $request->input('email'))->first();

      if ($emp) {
          if (Hash::check($request->input('password'), $emp->login_password)) {
              if(request()->ip() == '192.168.1.2'){
              $token = $emp->createToken('Laravel Password Grant Client')->accessToken;
              $department = Department::where('id',$emp->dept_id)->pluck('department_name');
              $ip = $request->ip();
              $data = array('id'=>$emp->id,'name'=>$emp->emp_fname,'emp_id'=>$emp->emp_id,'branch'=>$emp->branch_id,'department'=>$department[0],'email'=>$emp->login_id,'ip'=> $ip,'department_id'=>$emp->dept_id);
              //$login_details = array('emp_id'=>$emp->emp_id,'login_time'=>Carbon::now('Asia/Kolkata'));
              //$row = LoginHours::whereDate('created_date', Carbon::now()->format('Y-m-d'))->where('emp_id',$emp->emp_id)
              //->first();
              $row = Attendance::whereDate('attendance_date', Carbon::now()->format('Y-m-d'))->where('emp_id',$emp->emp_id)->where('login_date_time',null)->first();
              if($row){
                Attendance::whereDate('attendance_date', Carbon::now()->format('Y-m-d'))->where('emp_id',$emp->emp_id)
                ->update(['login_date_time'=>Carbon::now(),'ip_address'=>$ip,'login_device'=>$request->header('User-Agent')]);
              }
              // LoginHistory::insert($login_details);
              return response()->json(['status' => 200, 'message' => 'Logged In Successfully', 'token' => $token,'data'=>$data]);
            }
            else{
             $wfh = WFH::where('employee_id', $emp->emp_id)->where('status', 1)->get();
            $currentDate = Carbon::now();

          foreach ($wfh as $row) {
               $startDate = Carbon::parse($row->days_from, 'UTC');
               $endDate = Carbon::parse($row->days_to, 'UTC');

             for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                $result[] = [
                  'date' => $date->toDateString(),
                  'isCurrentDay' => $date->isSameDay($currentDate),
                   ];
                    if($date->isSameDay($currentDate)) {
                      $token = $emp->createToken('Laravel Password Grant Client')->accessToken;
                       $department = Department::where('id',$emp->dept_id)->pluck('department_name');
                         $ip = $request->ip();
                        $data = array('id'=>$emp->id,'name'=>$emp->emp_fname,'emp_id'=>$emp->emp_id,'branch'=>$emp->branch_id,'department'=>$department[0],'email'=>$emp->login_id,'ip'=> $ip,'department_id'=>$emp->dept_id);
                           //$login_details = array('emp_id'=>$emp->emp_id,'login_time'=>Carbon::now('Asia/Kolkata'));
                          //$row = LoginHours::whereDate('created_date', Carbon::now()->format('Y-m-d'))->where('emp_id',$emp->emp_id)
                         //->first();
                        $row = Attendance::whereDate('attendance_date', Carbon::now()->format('Y-m-d'))->where('emp_id',$emp->emp_id)->where('login_date_time',null)->first();
                     if($row){
                         Attendance::whereDate('attendance_date', Carbon::now()->format('Y-m-d'))->where('emp_id',$emp->emp_id)
                        ->update(['login_date_time'=>Carbon::now(),'ip_address'=>$ip,'login_device'=>$request->header('User-Agent')]);
                       }
              // LoginHistory::insert($login_details);
                     return response()->json(['status' => 200, 'message' => 'Logged In Successfully', 'token' => $token,'data'=>$data]);
                      }
                    else{
                       return response()->json(['status' => 500, 'message' => 'You Dont Have Access To Login From Outside Of Office']);


                    }
                 }
              }


            }

          } else {
              return response()->json(['status' => 400, 'message' => 'Incorrect password']);
          }
      } else {
          return response()->json(['status' => 400, 'message' => 'Incorrect Email']);
      }
  }
  public function logout_emp($id){
      $user = Auth::user()->token();
      //return $user;
      $user->revoke();
      ////$row = Attendance::where('emp_id', $id)
      //->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))
     //->first();
     $logout_time = Carbon::now();
     $overtime_hours = '';
     $row = Attendance::where('emp_id',$id)->where('login_date_time','!=',null)->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))->first();
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
      $status = 2;
      //short_leave
     }
     else if($diff_in_hour>=9){
      $status = 1;
      $overtime_hours = $diff_in_hour-9;

       //full_day
     }
     else if($diff_in_hour<7 && $diff_in_hour>=5 ){
      $status = 3;
      //half day
     }
     else{
      $status = 0;
      //absent
     }
      Attendance::where('emp_id',$id)->whereDate('attendance_date', Carbon::now()->format('Y-m-d'))
      ->update(['logout_date_time'=>$logout_time,'status'=>$status,'total_hours'=>$working_hours,'overtime_hours'=>$overtime_hours]);
      
      //return response()->json(['status'=>'200','message'=>'logout successfully']);

     }
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

  
}