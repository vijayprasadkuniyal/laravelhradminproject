<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use Validator;
use Carbon\Carbon;

class LeaveController extends Controller
{
    public function create_leave(Request $request){
         //return $request->all();
         $input = $request->all();
        $validator = Validator::make($input, [
           'name' => 'required',
           'date'=>'required',
           'leave_type'=>'required',
           ]);
       if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      if($request->branch){
        foreach($request->branch as $row){
            $leave = new Leave();
            $leave->leave_name = $request->name;
            $leave->date = $request->date;
            $leave->leave_type = $request->leave_type;
            $leave->branch_id = $row;
            $leave->created_by = 1;
            $leave->save();
           }

      }
      else{
        $leave = new Leave();
            $leave->leave_name = $request->name;
            $leave->date = $request->date;
            $leave->leave_type = $request->leave_type;
            $leave->created_by = 1;
            $leave->save();

      }
      if($leave) {
            return response()->json(['status'=>200,'message' => 'Leave Created  Successfully']);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }

    }
    public function leave_list(Request $request){
        $perPage = $request->per_page;
        $leave = Leave::leftjoin('branch_details','branch_details.id','=','leave_details.branch_id')
                ->select('branch_details.branch_name','leave_details.id','leave_details.leave_name','leave_details.date','leave_details.status','leave_details.leave_type')
                ->paginate($perPage);

        if($leave) {
            return response()->json(['status'=>200,'message' => 'Leave Details','data'=>$leave,'last_page'=>$leave->lastPage()]);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }


    }
    public function leave_show_in_attendance(){
        $data = Leave::where('status',1)->get();
        return response()->json(['message' => 'list of leave','data'=> $data]);

    }
    public function leave_edit($id){
        $leave = Leave::findOrFail($id);
        if($leave) {
            return response()->json(['status'=>200,'message' => 'Leave Details','data'=>$leave]);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }

    }
    public function leave_update(Request $request,$id){
        $input = $request->all();
        $validator = Validator::make($input, [
           'name' => 'required',
           'date'=>'required',
           'leave_type'=>'required',
           ]);
       if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $leave = Leave::findOrFail($id);
      if($request->branch){
        foreach($request->branch as $row){
           // $leave = new Leave();
            $leave->leave_name = $request->name;
            $leave->date = $request->date;
            $leave->leave_type = $request->leave_type;
            $leave->branch_id = $row;
            $leave->created_by = 1;
            $leave->save();
           }

      }
      else{
            //$leave = new Leave();
            $leave->leave_name = $request->name;
            $leave->date = $request->date;
            $leave->leave_type = $request->leave_type;
            $leave->created_by = 1;
            $leave->save();

      }
      if($leave) {
            return response()->json(['status'=>200,'message' => 'Leave Updated  Successfully']);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }

    }
    public function leave_status(Request $request){
    $request->validate([
        'leave_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);
    $leave = Leave::findOrFail($request->leave_id);
    $leave->status = $request->status;
    $leave->save();
    if($leave){
        return response()->json(['status' => 200, 'message' => 'Leave status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_leave_status($id){
    $leave = Leave::findOrFail($id);
    if($leave){
        return response()->json(['status' => 200, 'message' => 'Leave status','data'=>$leave->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
public function current_year_leave($id){
    //$leave = Leave::whereRaw('YEAR(date) = ?', [Carbon::now()->year])->where('status',1)->orderBy('date','ASC')->get(['id','leave_name','date']);
    //$leave = Leave::where('leave_type','international')->orWhere('leave_type','national')->orwhere('branch_id',$id)->whereRaw('YEAR(date) = ?', [Carbon::now()->year])
    //->where('status',1)->orderBy('date','ASC')->get(['id','leave_name','date']);
    $leave = Leave::whereRaw('YEAR(date) = ?', [Carbon::now()->year])->where('status',1)
           ->where(function($query) use ($id) {
            $query->where('leave_type','international')
            ->orWhere('leave_type','national')
            ->orWhere('branch_id',$id);
           })
          ->orderBy('date','ASC')->get(['id','leave_name','date']);
    //return $leave;
    $leave_data = [];
    foreach($leave as $row){
            $date = Carbon::createFromFormat('Y-m-d', $row->date);
            $leave_day = Carbon::createFromFormat('Y-m-d', $row->date);
            $curr_date = Carbon::now()->format('Y-m-d');
            $curr_date = Carbon::createFromFormat('Y-m-d', $curr_date);
            $diffInmonth = ' ';
            if($leave_day>=$curr_date){
                $diffInmonth =  $curr_date->diffInDays($leave_day).' '.'Days';
             }
           

        $monthName = $date->format('F');
        $leave_date = $date->format('d').' '.$monthName;
        $name = $row->leave_name;
        $leave_data[] = array('id'=>$row->id,'name'=>$name,'date'=>$leave_date,'full_date'=>$row->date,'diff'=>$diffInmonth);
       }
      return response()->json(['status' => 200, 'message' => 'Leave details', 'data' =>$leave_data]);
    }
    public function show_leave_on_attendance($id){
        $leave = Leave::where('status',1)
           ->where(function($query) use ($id) {
             $query->where('leave_type','international')
            ->orWhere('leave_type','national')
            ->orWhere('branch_id',$id);
           })
           ->get(['id','leave_name','date']);
           return response()->json(['status' => 200, 'message' => 'Leave list', 'data' =>$leave]);



    }





}
