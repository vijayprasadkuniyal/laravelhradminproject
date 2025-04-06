<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WFH;
Use \Carbon\Carbon;
use Validator;
use DB;
use App\Models\Attendance;
use App\Events\WfhEvent;
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
    $emp_details = BasicInfo::where('emp_id',$request->emp_id)->first();
    $message = $emp_details->emp_fname.' '.$emp_details->emp_lame.' '.'Send Work From Home Request  Click TO View';

    $type = 'wfh';
    $data= array('emp_id'=>$emp_details->reporting_manager,'notification'=> $message,'type'=>'wfh');
    DB::table('emp_notifications')->insert($data);
    //event(new WfhEvent($message,$manager_id->dept_manager,$type));

    //if($wfh){
        return response()->json([ 'status'=>200,'message' => 'Work From Home Applied Successfully']);
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
            DB::table('emp_notifications')->where('emp_id',$request->emp_id)->where('type','wfh_status')
                 ->update(['seen_status'=>1]);

            $wfh_list = WFH::where('employee_id', $request->emp_id)->orderBy('id','DESC')->paginate($request->per_page);
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
       //return $request->all();
        $data = [];

     foreach(json_decode($request->task) as $row){
        if($row->workingstatus!== "" && $row->hours!="" && $row->wfhtask!=''){
             $data[] = array('wfh_id'=>$request->wfh_id,'task'=>$row->wfhtask,
            'status'=>$row->workingstatus,'created_by'=>$request->emp_id,'total_hours'=>$row->hours);

         }
        else{
            return response()->json(['status'=>201,'message'=>'All Fields Are Required']);
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
        
        return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);

      }
      else{
        DB::table('wfh_task_details')->where('id',$id)
        ->update(['status'=>$request->statusdetails,'task'=>$request->taskdetails,'total_hours'=>$request->hours]);
        return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);


      }
       
        

     }
     public function get_wfh_task_status($id){
       $data =  DB::table('wfh_task_details')->where('id',$id)->get();
       return response()->json(['status'=>200,'data'=>$data]);

     }

     public function wfh_attendance_update(Request $request){
        $data = Attendance::where('emp_id',$request->emp_id)->whereDate('attendance_date',$request->date)->first();
        if($data){
            Attendance::where('emp_id',$request->emp_id)->where('attendance_date',$request->date)->update(['status'=>$request->status,'remark'=>$request->remark]);
            return response()->json(['status'=>200,'message'=>'Attendance Updated Successfully']);

            }
        else{
            return response()->json(['status'=>204,'message'=>'No Data Found For Update Attendance']);
        }
        }
    }
