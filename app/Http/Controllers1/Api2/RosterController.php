<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Roster;
use App\Models\BasicInfo;
use DB;
use Validator;
use Carbon\Carbon;

class RosterController extends Controller
{
   public function get_manager_employee($id){
    $managers = DB::table('employee_managers')->where('manager_id',$id)->pluck('emp_id');
    $employee = BasicInfo::whereIn('emp_id', $managers)->get(['emp_id','emp_fname']);
    return response()->json(['status'=>200,'message'=>'Employee Details','data'=>$employee]);




   }
   public function create_roster(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
       'employee'=>'required',
       'start_time'=>'required',
       'end_time'=>'required',
       'week_off'=>'required',
       
     ]);
     if($validator->fails()){
           $messages=$validator->messages();
           //$messages->add('company_logo', 'The company logo is required.');
           return response()->json(["messages"=>$messages,'status'=>400]);     
     }
      $roster = new Roster();
      $roster->emp_id = $request->employee;
      $roster->start_time = $request->start_time;
      $roster->end_time = $request->end_time;
      $roster->week_off = $request->week_off;
      $roster->created_by = $request->emp_id;
      $roster->save();
      if($roster){
        return response()->json(['status'=>200,'message'=>'Roster Created']);

      }
      else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

      }


      


   }
   public function roster_list(Request $request){
    $current_month = Carbon::now();
    $current_month = $current_month->month;
    $managers = DB::table('employee_managers')->where('manager_id',$request->id)->where('manager_status',1)->pluck('emp_id');
    //return  $managers;
    $roster_list = Roster::join('emp_basic_info','emp_basic_info.emp_id','=','emp_roster.emp_id')
    ->select('emp_basic_info.emp_fname','emp_roster.emp_id','emp_roster.start_time','emp_roster.end_time','emp_roster.week_off','emp_roster.created_date','emp_roster.id')
    ->whereIn('emp_roster.emp_id',$managers)
    ->whereRaw('Month(emp_roster.created_date) = ?', [Carbon::now()->month])->paginate($request->per_page);
     return response()->json(['status'=>200,'message'=>'Roster Details','data'=>$roster_list,'last_page'=>$roster_list->lastPage()]);
    
   }
   public function roster_wise_attendance($id){
    $roster_attendance = Roster::where('emp_id',$id)->get(['emp_id','week_off']);
    return response()->json(['status'=>200,'message'=>'Roster Attendance','data'=>$roster_attendance]);


   }
   public function show_emp_roster(Request $request){
       $data = Roster::where('emp_id',$request->emp_id)->whereRaw('Month(emp_roster.created_date) = ?', [Carbon::now()->month])
              ->paginate($request->per_page);
      return response()->json(['status'=>200,'message'=>'Employee Roster Details','data'=> $data,'per_page'=>$data->lastPage()]);


   }

}
