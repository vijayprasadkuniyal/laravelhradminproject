<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WFH;
Use \Carbon\Carbon;
use Validator;
use DB;
use App\Models\Attendance;

class WorkFromHomeController extends Controller
{
    public function apply_wfh(Request $request){
        $input = $request->all();
       $validator = Validator::make($input, [
       'date_from'=>'required',
       'date_to'=>'required',
       'no_of_days'=>'required',
       'reason'=>'required',
       ]);
   if($validator->fails()){
        $messages=$validator->messages();
        return response()->json(["messages"=>$messages,'status'=>400]);     
  }
     $start_date = Carbon::parse($request->date_from);
     $end_date = Carbon::parse($request->date_to);
     $dates = [];
    for ($date = $start_date; $date->lte($end_date); $date->addDay()) {
        $dates[] = $date->format('Y-m-d');
     }
    foreach($dates as $row){
      $data[] = array('days_from'=>$row,'days_to'=>$row,'no_of_days'=>1,'reason_for_wfh'=>$request->reason,'employee_id'=>$request->emp_id);

    }
    WFH::insert($data);
    //if($wfh){
        return response()->json([ 'status'=>200,'message' => 'Applied Successfully']);
    //}
    //else{
        //return response()->json([ 'status'=>500,'message' => 'Something went wrong']);

    //}





    }
    public function count_days_wfh(Request $request){
        $start_date = Carbon::createFromFormat('Y-m-d', $request->input('start_date'));
        $end_date = Carbon::createFromFormat('Y-m-d', $request->input('end_date'));
        $diffInDays = $end_date->diffInDays($start_date)+1;
        if($start_date<=$end_date){
             return response()->json(['status' => 200, 'message' => 'Leave details','data'=>$diffInDays]);
         }
         else{
            return response()->json(['status' => 500, 'message' => 'End Date should be grater then start date',]);
        }
        }
        public function wfh_list(Request $request){
            $wfh_list = WFH::where('employee_id', $request->emp_id)->paginate($request->per_page);
            return response()->json(['status' => 200, 'message' => 'Work From Home Details','data'=> $wfh_list,'last_page'=>$wfh_list->lastPage()]);
        }


        public function work_from_home_attendance($id){
                $wfh_list = WFH::where('employee_id',$id)->where('status',1)->get();
                $result = [];

              foreach ($wfh_list as $row) {
                $status = ($row->status == 1) ? 'Approved' : (($row->status == 2) ? 'Reject' : 'Pending');

           $startDate = Carbon::parse($row->days_from);
           $endDate = Carbon::parse($row->days_to);

          for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $result[] = [
                'id' => $row->id,
                'date' => $date->toDateString(),
                'status' => $status,
            ];
        }
    }

    return response()->json(['status' => '200', 'wfh_details' => $result]);


               }
    public function get_wfh_task($id){
        $emp_id = WFH::where('id',$id)->first();
        $data = DB::table('wfh_task_details')->where('wfh_id',$id)->get();
        return response()->json(['status' => '200', 'data' => $data,
        'employee_id'=> $emp_id->employee_id]);

    }
    public function update_wfh_task(Request $request){
       // return $request->all();
        $data = [];

     foreach(json_decode($request->task) as $row){
        if($row->workingstatus!== ""){
             $data[] = array('wfh_id'=>$request->wfh_id,'task'=>$row->wfhtask,
            'status'=>$row->workingstatus,'created_by'=>$request->emp_id);

         }
        else{
            return response()->json(['status'=>201,'message'=>'Pls fill Proper Task']);
          }
      }
      DB::table('wfh_task_details')->insert($data);
      return response()->json(['status'=>200,'message'=>'Task Updated Successfully']);
     }
     public function update_wfh_task_status(Request $request,$id){
        //return $request->all();
      if($request->type=='manager'){
        DB::table('wfh_task_details')->where('id',$id)
        ->update(['manager_status'=>$request->status,'remark'=>$request->remark]);
        $record = DB::table('wfh_task_details')->where('id',$id)->first();
        $get_wfh_id = $record->wfh_id;
        $get_emp_id = WFH::where('id', $get_wfh_id)->first();
        //$get_date = WFH::where('id', $get_wfh_id)->first();

        $total_task =  DB::table('wfh_task_details')->where('wfh_id',$get_wfh_id)->count();
        $total_approval_task = DB::table('wfh_task_details')->where('wfh_id',$get_wfh_id)
         ->where('manager_status','Approved')
        ->count();

        if($total_task>1 && $total_approval_task>0){
            $attendance_percent = $total_approval_task/$total_task;
            $attendance_percent = round($attendance_percent*100);
            if($attendance_percent>=75){
                $status = 1;

            }
            else if($attendance_percent>=50 && $attendance_percent<75){
                $status = 2;

            }
            else{
                $status = 0;

            }
            Attendance::where('emp_id',$get_emp_id->employee_id)
            ->where('attendance_date', $get_emp_id->days_from)->update(['status'=>$status]);



        }
        else{
            $status = 0;
            Attendance::where('emp_id',$get_emp_id->employee_id)
            ->where('attendance_date', $get_emp_id->days_from)->update(['status'=>$status]);

        }
        return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);

      }
      else{
        DB::table('wfh_task_details')->where('id',$id)
        ->update(['status'=>$request->statusdetails,'task'=>$request->taskdetails]);
        return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);


      }
       
        

     }
     public function get_wfh_task_status($id){
       $data =  DB::table('wfh_task_details')->where('id',$id)->get();
       return response()->json(['status'=>200,'data'=>$data]);

     }
    }
