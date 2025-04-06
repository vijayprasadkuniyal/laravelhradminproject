<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\BasicInfo;

class EmpHistoryController extends Controller
{
    public function emp_salary_history($emp_id){
        $data = DB::table('emp_salary_history')
            ->select('emp_id', DB::raw("DATE_FORMAT(created_date, '%Y-%m-%d') as formatted_date"))
            ->where('emp_id', $emp_id)
            ->groupBy('emp_id', 'formatted_date')
            ->get();
    
        $salary_data = [];
    
        foreach ($data as $row) {
            $total_gross_salary = DB::table('emp_salary_history')
                ->where('emp_id', $row->emp_id)
                ->where(DB::raw("DATE_FORMAT(created_date, '%Y-%m-%d')"), $row->formatted_date)
                ->where('is_deduction',0)
                ->where('is_contribute',0)
                ->sum('amount_per_month');
            
            $total_deduction  = DB::table('emp_salary_history')
                ->where('emp_id', $row->emp_id)
                ->where(DB::raw("DATE_FORMAT(created_date, '%Y-%m-%d')"), $row->formatted_date)
                ->where('is_deduction',1)
                ->where('is_contribute',0)
                ->sum('amount_per_month');

            $total_contributton = DB::table('emp_salary_history')
                ->where('emp_id', $row->emp_id)
                ->where(DB::raw("DATE_FORMAT(created_date, '%Y-%m-%d')"), $row->formatted_date)
                ->where('is_deduction',1)
                ->where('is_contribute',1)
                ->sum('amount_per_month');
            $ctc = DB::table('emp_salary_history')
            ->where('emp_id', $row->emp_id)
            ->where(DB::raw("DATE_FORMAT(created_date, '%Y-%m-%d')"), $row->formatted_date)->first();
    
            $salary_data[] = [
                'emp_id' => $row->emp_id,
                'formatted_date' => $row->formatted_date,
                'total_gross_salary' => $total_gross_salary,
                'deduction'=>$total_deduction,
                'contributtion'=> $total_contributton,
                'ctc'=> $ctc->emp_package,
            ];
        }
    
        return response()->json(['status'=>200,'data'=>$salary_data]);
    }

    public function emp_leave_history($id){
        $data = DB::table('employee_leave')->join('create_leave_type','employee_leave.leave_id','create_leave_type.id')
               ->leftjoin('emp_basic_info','emp_basic_info.emp_id','employee_leave.action_by')
               ->select('employee_leave.date_from','employee_leave.date_to','employee_leave.no_of_days',
               'employee_leave.is_half_day','employee_leave.message','employee_leave.rejection_reason','employee_leave.status',
               'employee_leave.action_by','emp_basic_info.emp_fname','create_leave_type.leave_name')
               ->where('employee_leave.emp_id',$id)
               ->orderBy('employee_leave.id','DESC')
              ->get();
        return response()->json(['status'=>200,'data'=>$data]);

    }

    public function emp_wfh_history(Request $request)
{
    
    $data = DB::table('work_from_home')
        ->leftjoin('emp_basic_info', 'emp_basic_info.emp_id', 'work_from_home.apporved_by')
        ->select('work_from_home.days_from', 'work_from_home.days_to', 'work_from_home.no_of_days',
            'work_from_home.reason_for_wfh', 'work_from_home.status', 'work_from_home.rejection_reason',
            'emp_basic_info.emp_fname', 'work_from_home.id', 'work_from_home.id')
        ->where('work_from_home.employee_id', $request->id)
        ->orderBy('work_from_home.id', 'DESC')
        ->paginate($request->per_page);
    if ($data->isEmpty()) {
        return response()->json(['status' => 200, 'data' => [], 'last_page' => 0]); 
    }

    return response()->json(['status' => 200, 'data' => $data, 'last_page' => $data->lastPage()]);
}


public function save_resign(Request $request){
    $data = array('emp_id'=>$request->emp_id,'reason'=>$request->reason);
    DB::table('emp_resign_details')->insert($data);
    return response()->json(['status'=>200,'message'=>'Resign Send Suceesfully']);



}
public function emp_resign_list($id){
    $data = DB::table('emp_resign_details')->where('emp_id',$id)->get();
    return response()->json(['status'=>200,'data'=>$data]);
}

public function show_team_resign_list(Request $request){
    $get_data = [];

    if($request->dept_id == 5){
        $get_data = DB::table('emp_resign_details')->join('emp_basic_info','emp_resign_details.emp_id','=','emp_basic_info.emp_id')
        ->join('department','department.id','emp_basic_info.dept_id')
        ->select('emp_resign_details.*','emp_basic_info.reporting_manager','emp_basic_info.emp_fname','emp_basic_info.emp_lame',
        'emp_basic_info.dept_id','department.department_name')
        ->get();

     }
    else{
    $emp_managers = DB::table('employee_managers')
    ->whereRaw("FIND_IN_SET(?, reporting_to)", [$request->emp_id]) 
    ->where('status', 1)
    ->pluck('emp_id')
    ->toArray();
    $get_data = DB::table('emp_resign_details')->join('emp_basic_info','emp_resign_details.emp_id','=','emp_basic_info.emp_id')
         ->join('department','department.id','emp_basic_info.dept_id')
        ->select('emp_resign_details.*','emp_basic_info.reporting_manager','emp_basic_info.emp_fname',
        'emp_basic_info.emp_lame','department.department_name')
        ->whereIn('emp_resign_details.emp_id',$emp_managers)
        ->get();

    }
    return response()->json(['status'=>200,'data'=>$get_data]);


    }

public function save_resign_remark(Request $request){
    $employee_id = $request->empid;
    $remarked_by = $request->emp_id;
    $remark = '';
    $status = '';
    $get_emp_details = DB::table('emp_basic_info')->where('emp_id', $employee_id)->first();
    if($get_emp_details->reporting_manager == $remarked_by && $request->dept_id==5){
        $remark = $request->remark;
        $status = $request->status;
       DB::table('emp_resign_details')->where('id',$request->id)
      ->update(['manager_status'=>$status,'manager_remark'=>$remark,'hr_status'=> $status,'hr_remark'=>$remark]);

    }
    elseif($get_emp_details->reporting_manager == $remarked_by && $request->dept_id!=5 ){
        $remark = $request->remark;
        $status = $request->status;
        DB::table('emp_resign_details')->where('id',$request->id)
        ->update(['manager_status'=>$status,'manager_remark'=>$remark]);

    }
    else{
        $remark = $request->remark;
        $status = $request->status;
        DB::table('emp_resign_details')->where('id',$request->id)
        ->update(['hr_status'=> $status,'hr_remark'=>$remark]);

    }
    return response()->json(['status'=>200,'message'=>'Remark Added Successfully']);


}
public function get_resign_description($id){
    $data = DB::table('emp_resign_details')->where('id',$id)->first();
    return response()->json(['status'=>200,'data'=>$data]);

}

public function emp_asset_details_for_fnf($id){
    $data = DB::table('stock_assign')->join('stock_details','stock_details.id','stock_assign.stock_id')
            ->select('stock_details.brand_name','stock_assign.return_status','stock_assign.return_date')
            ->where('assign_to',$id)->get();
    $emp_manager = DB::table('emp_basic_info')->where('emp_id',$id)->first();
    return response()->json(['status'=>200,'data'=>$data,'manager'=>$emp_manager->reporting_manager]);



}

public function save_resign_description_details(Request $request){
    if($request->type=='save_details'){
        $data = array('is_terminate'=>$request->terminate,'notice_period'=>$request->notice_period,
       'no_of_notice_period_days'=>$request->days,'last_working_day'=>$request->last_working_day,
       'is_notice_period_served'=>$request->notice_period_serve,'fnf_salary'=>$request->salary,
       'termination_reason'=>$request->reason);
        DB::table('emp_resign_details')->where('id',$request->id)->update($data);
        return response()->json(['status'=>200,'data'=>$data,'message'=>'Updated Successfully']);
   
       }
       else{
           $get_emp_details =  DB::table('emp_resign_details')->where('id',$request->id)->first();
           $stock_details = DB::table('stock_assign')->where('assign_to', $get_emp_details->emp_id)->where('return_status','!=',1)->count();
           if($stock_details>0){
               return response()->json(['status'=>400,'message'=>'Asset Not Submitted']);
   
           }
           else{
              $get_emp_details =  DB::table('emp_resign_details')->where('id',$request->id)->first();
              $data = array('is_terminate'=>$request->terminate,'notice_period'=>$request->notice_period,
              'no_of_notice_period_days'=>$request->days,'last_working_day'=>$request->last_working_day,
              'is_notice_period_served'=>$request->notice_period_serve,'fnf_salary'=>$request->salary,
              );
               DB::table('emp_resign_details')->where('id',$request->id)->update($data);
               DB::table('emp_basic_info')->where('emp_id',$request->employee)
               ->update(['emp_status'=>0,'emp_eod'=>$request->last_working_day,
               'emp_dor'=>$get_emp_details->created_date]);
                return response()->json(['status'=>200,'data'=>$data,'message'=>'Updated Successfully']);
   
           }
   
   
       }
    }



    
}
