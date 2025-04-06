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
    $managers = DB::table('employee_managers')->where('dept_manager',$request->id)->where('status',1)->pluck('emp_id');
    //return  $managers;
    $roster_list = Roster::join('emp_basic_info','emp_basic_info.emp_id','=','emp_roster.emp_id')
    ->select('emp_basic_info.emp_fname','emp_roster.emp_id','emp_roster.start_time','emp_roster.end_time','emp_roster.week_off','emp_roster.created_date','emp_roster.id','emp_roster.status')
    ->whereIn('emp_roster.emp_id',$managers)
    ->whereRaw('Month(emp_roster.created_date) = ?', [Carbon::now()->month])->paginate($request->per_page);
     return response()->json(['status'=>200,'message'=>'Roster Details','data'=>$roster_list,'last_page'=>$roster_list->lastPage()]);
    
   }
   public function roster_wise_attendance($id){
    $roster_attendance = Roster::where('emp_id',$id)->where('status',1)->get(['emp_id','week_off']);
    return response()->json(['status'=>200,'message'=>'Roster Attendance','data'=>$roster_attendance]);


   }
   public function show_emp_roster(Request $request){
       $data = Roster::where('emp_id',$request->emp_id)->orderBy('id','DESC')
              ->paginate($request->per_page);
      return response()->json(['status'=>200,'message'=>'Employee Roster Details','data'=> $data,'last_page'=>$data->lastPage()]);


   }
  public function request_to_change_roster(Request $request)
{
    $validator = Validator::make($request->all(), [
        'date' => 'required|date',
        'reason' => 'required',
    ]);

    if ($validator->fails()) {
        $messages = $validator->messages();
        return response()->json(['messages' => $messages, 'status' => 400]);
    }
    $oldDate = Carbon::parse($request->old_date);
    $newDate = Carbon::parse($request->date);

    $empRequest = DB::table('emp_change_roster_request')->insert([
        'emp_id' => $request->emp_id,
        'old_date' => $oldDate,
        'new_date' => $newDate,
        'reason' => $request->reason,
    ]);

    return response()->json(['status' => 200, 'message' => 'Request Sent Successfully']);
}
public function roster_change_request(Request $request){

   $managers = DB::table('employee_managers')->where('dept_manager',$request->emp_id)->pluck('emp_id');
   foreach($managers as $row){
    DB::table('emp_change_roster_request')->where('emp_id',$row)->update(['seen_status'=>1]);
   }
   $data = DB::table('emp_change_roster_request')->join('emp_basic_info','emp_change_roster_request.emp_id','=','emp_basic_info.emp_id')
         ->select('emp_basic_info.emp_fname','emp_change_roster_request.*')
         ->whereIn('emp_change_roster_request.emp_id', $managers)
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
  $data = array('week_off'=>$request->week_off,'start_time'=>$request->start_time,'end_time'=>$request->end_time);
  Roster::where('id',$id)->update($data);
  return response()->json(['status' =>200, 'message' => 'Roster Updated Successfully']);



}




}

