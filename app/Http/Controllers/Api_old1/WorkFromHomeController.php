<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WFH;
Use \Carbon\Carbon;
use Validator;
use DB;
use App\Models\BasicInfo;

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
        $list = [];
        $data = DB::table('wfh_task_details')->where('wfh_id',$id)->get();
        foreach($data as $row){
          $record = WFH::where('id',$row->wfh_id)->first();
          $emp_name = BasicInfo::where('emp_id',$record->employee_id)->first();

        }
        return response()->json(['status' => '200', 'data' => $data]);

    }
    public function update_wfh_task(Request $request){
        $data = [];

     foreach(json_decode($request->task) as $row){
        if($row->wfhtask!='' && $row->workingstatus!=''){
        $data[] = array('wfh_id'=>$request->wfh_id,'task'=>$row->wfhtask,
        'status'=>$row->workingstatus,'created_by'=>$request->emp_id);

        }
      }
      DB::table('wfh_task_details')->insert($data);
      return response()->json(['status'=>200,'message'=>'Task Updated Successfully']);



    }
        }
