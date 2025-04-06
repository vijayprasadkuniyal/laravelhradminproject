<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WFH;
Use \Carbon\Carbon;
use Validator;

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
    $wfh = new WFH();
    $wfh->days_from = $request->date_from;
    $wfh->days_to = $request->date_to;
    $wfh->no_of_days = $request->no_of_days;
    $wfh->reason_for_wfh = $request->reason;
    $wfh->employee_id = $request->emp_id;
    $wfh->save();
    if($wfh){
        return response()->json([ 'status'=>200,'message' => 'Applied Successfully']);
    }
    else{
        return response()->json([ 'status'=>500,'message' => 'Something went wrong']);

    }





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

        // Loop through each day and add it to the array
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
        }
