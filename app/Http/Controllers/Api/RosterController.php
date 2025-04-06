<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Roster;
use App\Models\BasicInfo;
use DB;
use Validator;
use Carbon\Carbon;
use App\Models\LeaveType;
use App\Models\EmployeeLeaveBlance;

class RosterController extends Controller
{
   public function get_manager_employee($id){
   $managers = DB::table('employee_managers')
    ->whereRaw("FIND_IN_SET(?, reporting_to)", [$id]) 
    ->where('status', 1)
    ->pluck('emp_id')
    ->toArray();
    $employee = BasicInfo::whereIn('emp_id', $managers)->get(['emp_id','emp_fname','emp_lame']);
    return response()->json(['status'=>200,'message'=>'Employee Details','data'=>$employee]);




   }
   public function create_roster(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
       'employee'=>'required',
       'week_off'=>'required',
       
     ]);
     if($validator->fails()){
           $messages=$validator->messages();
           //$messages->add('company_logo', 'The company logo is required.');
           return response()->json(["messages"=>$messages,'status'=>400]);     
     }
      $roster = new Roster();
      $roster->emp_id = $request->employee;
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
    $emp_id = $request->id;

   $managers = DB::table('employee_managers')
        ->where(function($query) use ($emp_id) {
            $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id]);
        })
        ->where('status', 1)
        ->pluck('emp_id')
        ->toArray();

    $emp_details[] = $emp_id;
    $emp_details = array_unique($emp_details); 
  
    $roster_list = Roster::join('emp_basic_info','emp_basic_info.emp_id','=','emp_roster.emp_id')
    ->select('emp_basic_info.emp_fname','emp_basic_info.emp_lame','emp_roster.emp_id','emp_roster.week_off','emp_roster.created_date','emp_roster.id','emp_roster.status')
    ->whereIn('emp_roster.emp_id',$managers)
    ->orderBy('id','DESC')
    ->paginate($request->per_page);
     return response()->json(['status'=>200,'message'=>'Roster Details','data'=>$roster_list,'last_page'=>$roster_list->lastPage()]);
    
   }
   public function roster_wise_attendance($id){
    $roster_attendance = Roster::where('emp_id',$id)->where('status',1)->get(['emp_id','week_off']);
    return response()->json(['status'=>200,'message'=>'Roster Attendance','data'=>$roster_attendance]);


   }
  public function show_emp_roster(Request $request) {
    $emp_id = $request->emp_id;

    $emp_details = DB::table('employee_managers')
        ->where(function($query) use ($emp_id) {
            $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id]);
        })
        //->where('status', 1)
        ->pluck('emp_id')
        ->toArray();

    $emp_details[] = $emp_id; 
    $emp_details = array_unique($emp_details); 

    // Fetch roster data
    $roster_data = DB::table('emp_roster')
        ->whereIn('emp_id', $emp_details)
        ->where('status', 1);

    if($request->month && $request->year){
      $roster_data->whereMonth('week_off',$request->month)->whereYear('week_off',$request->year);
     }
     else{
       $roster_data->whereMonth('week_off', Carbon::now()->month)
        ->whereYear('week_off', Carbon::now()->year);
      }

      $data = $roster_data->get();

  
    $attendance = [];

    foreach ($data as $row) {
        
        $employee = DB::table('emp_basic_info')
            ->select('emp_fname', 'emp_lame', 'emp_id')
            ->where('emp_id', $row->emp_id)
            ->first();

      
        if ($employee) {
            if (!isset($attendance[$employee->emp_id])) {
                $attendance[$employee->emp_id] = [
                'emp_name' => $employee->emp_fname . ' ' . $employee->emp_lame,
                'attendance_dates' => [], 
                'leave_dates' => [] 
              ];
            }
            
            $attendance[$employee->emp_id]['attendance_dates'] = array_merge(
                $attendance[$employee->emp_id]['attendance_dates'],
                explode(',', $row->week_off)
            );
        }
    }

    
    $leave_data = DB::table('employee_leave') 
        ->whereIn('emp_id', array_keys($attendance)) 
        //->whereMonth('leave_from', Carbon::now()->month)
        ->get();

    
    function getDatesBetween($startDate, $endDate) {
        $dates = [];
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = new \DateInterval('P1D'); 
        $period = new \DatePeriod($start, $interval, $end->modify('+1 day')); 

        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }
        return $dates;
    }

    
    foreach ($leave_data as $leave) {
        if (isset($attendance[$leave->emp_id])) {
            $leave_dates = getDatesBetween($leave->date_from, $leave->date_to);
            $attendance[$leave->emp_id]['leave_dates'] = array_merge(
                $attendance[$leave->emp_id]['leave_dates'],
                $leave_dates
            );
        }
    }

    $result = [];
    foreach ($attendance as $attendance_data) {
        $result[] = [
            'emp_name' => $attendance_data['emp_name'],
            'attendance_dates' => array_unique($attendance_data['attendance_dates']),
            'leave_dates' => array_unique($attendance_data['leave_dates']) 
        ];
    }

    return response()->json($result);
}


  public function request_to_change_roster(Request $request)
{
    $validator = Validator::make($request->all(), [
        'old_date' => 'required|date',
        'new_date' => 'required|date',
        'reason' => 'required',
    ]);

    if ($validator->fails()) {
        $messages = $validator->messages();
        return response()->json(['messages' => $messages, 'status' => 400]);
    }
    $oldDate = Carbon::parse($request->old_date);
    $newDate = Carbon::parse($request->new_date);

    $empRequest = DB::table('emp_change_roster_request')->insert([
        'emp_id' => $request->emp_id,
        'old_date' => $oldDate,
        'new_date' => $newDate,
        'reason' => $request->reason,
    ]);

    return response()->json(['status' => 200, 'message' => 'Request Sent Successfully']);
}
public function roster_change_request(Request $request){
  $emp_id = $request->emp_id;

   $emp_details = DB::table('employee_managers')
        ->where(function($query) use ($emp_id) {
            $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id]);
        })
        ->where('status', 1)
        ->pluck('emp_id')
        ->toArray();
    $emp_details = array_unique($emp_details); 


   $data = DB::table('emp_change_roster_request')->join('emp_basic_info','emp_change_roster_request.emp_id','=','emp_basic_info.emp_id')

         ->select('emp_basic_info.emp_fname','emp_basic_info.emp_lame','emp_change_roster_request.*',
          DB::raw('CASE 
                    WHEN emp_change_roster_request.status = 1 THEN "Approved"
                    WHEN emp_change_roster_request.status = 2 THEN "Rejected"
                    WHEN emp_change_roster_request.status = 0 THEN "Pending"
                    ELSE "Unknown"
                 END as status')


       )
         ->whereIn('emp_change_roster_request.emp_id',  $emp_details)
         ->orderBy('id','DESC')
         ->paginate($request->per_page);


         return response()->json(['status' => 200, 'message' => 'Roster change request','data'=>$data,'last_page'=>$data->lastPage()]);


}
public function roster_status(Request $request){
    //dd('hi');
    $request->validate([
        'roster_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $roster = Roster::findOrFail($request->roster_id);
    //dd($company);

    // Update the status of the company
    $roster->status = $request->status;
    $roster->save();
    if($roster){
        return response()->json(['status' => 200, 'message' => 'Roster status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_roster_status($id){
    $roster = Roster::findOrFail($id);
    if($roster){
        return response()->json(['status' => 200, 'message' => 'Roster status','data'=>$roster->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
public function edit_roster($id){
  $data = Roster::where('id',$id)->first();
  return response()->json(['status'=>200,'data'=>$data]);

}
public function update_roster(Request $request,$id){
  $data = array('week_off'=>$request->week_off);
  Roster::where('id',$id)->update($data);
  return response()->json(['status' =>200, 'message' => 'Roster Updated Successfully']);



}

public function roster_change_request_of_particular_employee(Request $request){

  $data = DB::table('emp_change_roster_request')->leftjoin('emp_basic_info','emp_basic_info.emp_id','emp_change_roster_request.action_by')
    ->select('emp_change_roster_request.emp_id','emp_change_roster_request.old_date','emp_change_roster_request.new_date','emp_change_roster_request.reason','emp_basic_info.emp_fname','emp_basic_info.emp_lame',
      DB::raw('CASE 
                    WHEN emp_change_roster_request.status = 1 THEN "Approved"
                    WHEN emp_change_roster_request.status = 2 THEN "Rejected"
                    WHEN emp_change_roster_request.status = 0 THEN "Pending"
                    ELSE "Unknown"
                 END as status')
    )
    ->where('emp_change_roster_request.emp_id',$request->emp_id)
    ->orderBy('emp_change_roster_request.id','DESC')
    ->get();

  return response()->json(['status'=>200,'data'=>$data]);


}

public function update_roster_request(Request $request){
  if($request->status==1){
    Roster::where('emp_id',$request->emp_id)
    ->where('week_off',$request->old_date)->update(['week_off'=>$request->new_date]);

    DB::table('emp_change_roster_request')->where('id',$request->id)->update(['status'=>$request->status,'action_by'=>$request->action_by]);
}
  elseif($request->status ==2){
    DB::table('emp_change_roster_request')->where('id',$request->id)->update(['status'=>$request->status,'action_by'=>$request->action_by]);

  }

  return response()->json(['status'=>200,'message'=>'Request Updated Successfully']);


}

public function emp_confirmation_details(Request $request){
  //return $request->all();
  $data = BasicInfo::leftjoin('department','department.id','emp_basic_info.dept_id')
              ->leftjoin('designation','designation.id','emp_basic_info.desi_id')
              ->leftjoin('branch_details','branch_details.id','emp_basic_info.branch_id')
              ->select('department.department_name','designation.designation_name','branch_details.branch_name','emp_basic_info.emp_id','emp_basic_info.emp_fname','emp_basic_info.emp_lame',
              'emp_basic_info.is_confirmed as confirmation_status','emp_basic_info.emp_doc','emp_basic_info.emp_doj',
                DB::raw('CASE 
                    WHEN emp_basic_info.is_confirmed = 1 THEN "Confirmed"
                    WHEN emp_basic_info.is_confirmed = 0 THEN "Not Confirmed"
                    ELSE "Unknown"
                 END as is_confirmed')



               )
              ->where('emp_status',1);

    if($request->start_date && $request->end_date){
      $data->whereDate('emp_basic_info.emp_doc', '>=', $request->start_date)
            ->whereDate('emp_basic_info.emp_doc', '<=', $request->end_date);
     }

    else{
      $data->whereMonth('emp_basic_info.emp_doc',Carbon::now()->month)
              ->whereYear('emp_basic_info.emp_doc',Carbon::now()->year);

    }

    $result = $data->paginate(10);


    return response()->json(['status'=>200,'data'=>$result,'last_page'=>$result->lastPage()]);
}

public function emp_confirmation_status_update(Request $request){
  $data_array = [];

  if($request->status ==1){
      DB::table('emp_basic_info')->where('emp_id',$request->emp_id)->update([
        'emp_doc'=>Carbon::now()->format('Y-m-d'),'is_confirmed'=>1]);

    $confirmation_leave = LeaveType::where('is_confirmation_leave',1)->where('assign_when_request',0)->get();
      $currentYear = Carbon::now()->year;
      $current_month = Carbon::now()->month;
      $month_diff = 12 - $current_month;

     foreach($confirmation_leave as $row){
       $per_month_leave = round($row->leave_days/12);
        $assigned_leave =  $per_month_leave* $month_diff+1;

         $check_exists = EmployeeLeaveBlance::where('leave_id',$row->id)
         ->where('emp_id', $request->emp_id)->where('year',Carbon::now()->year)->first();

        if(!$check_exists){
           $data_array[] = array('emp_id'=> $request->emp_id,'leave_id'=>$row->id,
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
    DB::table('emp_basic_info')->where('emp_id',$request->emp_id)->update([
        'emp_doc'=>$request->next_date]);

  }

  return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);

}

public function confirmation_notifications()
{
    $data = BasicInfo::where('emp_status', 1)
        ->whereDate('emp_doc', Carbon::now()->format('Y-m-d'))
        ->count();

    if ($data > 0) {
        $emp_details = BasicInfo::where('emp_status', 1)
            ->where('dept_id', 5)
            ->get(['emp_id']);

        foreach ($emp_details as $row) {
            $check_exists = DB::table('emp_notifications')
                ->where('emp_id', $row->emp_id)
                ->where('type', 'emp_confirmation')
                ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
                ->exists();

            if (!$check_exists) {
                $notification = 'Click To View Pending Confirmation Employee List';
                $data_array = [
                    'emp_id' => $row->emp_id,
                    'notification' => $notification,
                    'type' => 'emp_confirmation',
                ];
                DB::table('emp_notifications')->insert($data_array);
            }
        }
    }
}


 public function update_confirmation_seen_status(Request $request){
     //return $request->emp_id;

     DB::table('emp_notifications')->where('emp_id',$request->emp_id)
         ->where('type',$request->type)->update(['seen_status'=>1]);
        return response()->json(['status'=>200]);


}

public function cache_clear(){
 Artisan::call('optimize');
 Artisan::call('cache:clear');
 Artisan::call('config:cache');
 Artisan::call('route:cache');
 Artisan::call('view:clear');
}




}

