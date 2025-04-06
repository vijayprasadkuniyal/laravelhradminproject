<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use App\Models\BasicInfo;

class HrDashboardController extends Controller
{
   public function hr_dashboard_count(Request $request){
    $total_employee = DB::table('emp_basic_info');
    $active_employee = DB::table('emp_basic_info')->where('emp_status',1);
    $total_salary = DB::table('emp_monthly_salary');
    $total_leaves = DB::table('employee_leave')->where('status',1);
    $total_wfh = DB::table('work_from_home')->where('status',1);
    $total_roster = DB::table('emp_roster')->where('status',1);
    $attendane_regularize = DB::table('attendance_regularize_request');
    $job_request = DB::table('managment_opening');
    $offer_letter = DB::table('candidate_followup_details')->where('is_offer_latter_genrated',1);
    $pip = DB::table('employee_pip');

    if($request->department){
        $total_employee->where('dept_id',$request->department);
        $active_employee->where('dept_id',$request->department);
        $get_employee_id = DB::table('emp_basic_info')
        ->where('dept_id',$request->department)->pluck('emp_id');
        $total_salary->whereIn('emp_id',$get_employee_id);
        $total_leaves->whereIn('emp_id',$get_employee_id);
        $total_wfh->whereIn('employee_id',$get_employee_id);
        $total_roster->whereIn('emp_id',$get_employee_id);
        $attendane_regularize->whereIn('emp_id',$get_employee_id);
        $job_request->where('department_from',$request->department);
        $pip->where('department_id',$request->department);
    }

    if($request->branch){
        $total_employee->where('branch_id',$request->branch);
        $active_employee->where('branch_id',$request->branch);
        $get_employee_id = DB::table('emp_basic_info')
        ->where('branch_id',$request->department)->pluck('emp_id');
        $total_salary->whereIn('emp_id',$get_employee_id);
        $total_leaves->whereIn('emp_id',$get_employee_id);
        $total_wfh->whereIn('employee_id',$get_employee_id);
        $total_roster->whereIn('emp_id',$get_employee_id);
        $attendane_regularize->whereIn('emp_id',$get_employee_id);

    }
    if($request->emp_type){
        $total_employee->where('emp_type',$request->emp_type);
        $active_employee->where('emp_type',$request->emp_type);
        $get_employee_id = DB::table('emp_basic_info')
        ->where('emp_type',$request->emp_type)->pluck('emp_id');
        $total_salary->whereIn('emp_id',$get_employee_id);
        $total_leaves->whereIn('emp_id',$get_employee_id);
        $total_wfh->whereIn('employee_id',$get_employee_id);
        $total_roster->whereIn('emp_id',$get_employee_id);
        $attendane_regularize->whereIn('emp_id',$get_employee_id);


    }
    if($request->emp_id){
        $total_salary->where('emp_id',$request->emp_id);
        $total_leaves->where('emp_id',$request->emp_id);
        $total_wfh->where('employee_id',$request->emp_id);
        $total_roster->where('emp_id',$request->emp_id);
        $attendane_regularize->where('emp_id',$request->emp_id);
        $offer_letter->where('created_by',$request->emp_id);
        $pip->where('emp_id',$request->emp_id);



    }
    if($request->start_date && $request->end_date){
        $total_employee->whereDate('created_date', '>=', $request->start_date)
        ->whereDate('created_date', '<=', $request->end_date);
        $active_employee->whereDate('created_date', '>=', $request->start_date)
        ->whereDate('created_date', '<=', $request->end_date);
        $total_salary->whereDate('created_date', '>=', $request->start_date)
        ->whereDate('created_date', '<=', $request->end_date);
        $total_leaves->whereDate('created_date', '>=', $request->start_date)
        ->whereDate('created_date', '<=', $request->end_date);
        $total_wfh->whereDate('created_date', '>=', $request->start_date)
        ->whereDate('created_date', '<=', $request->end_date);
        $total_roster->whereDate('created_date', '>=', $request->start_date)
        ->whereDate('created_date', '<=', $request->end_date);
        $attendane_regularize->whereDate('created_date', '>=', $request->start_date)
        ->whereDate('created_date', '<=', $request->end_date);
        $job_request->whereDate('created_date', '>=', $request->start_date)
        ->whereDate('created_date', '<=', $request->end_date);
         $pip->whereDate('created_date', '>=', $request->start_date)
        ->whereDate('created_date', '<=', $request->end_date);
      
    }
    $data = array('total_employee'=>$total_employee->count(),
    'active_employee'=>$active_employee->count(),'total_salary'=>$total_salary->sum('amount',),
    'leaves'=>$total_leaves->count(),'total_wfh'=>$total_wfh->count(),'roster'=>$total_roster->count(),
    'attendance_regularize'=>$attendane_regularize->count(),'job_request'=> $job_request->count(),'offer_letter_count'=>$offer_letter->count(),'pip'=>$pip->count());
     return response()->json(['status'=>200,'data'=>$data]);
    }
    public function show_emp_count_on_graph(Request $request){
        $data = BasicInfo::select(DB::raw('YEAR(emp_doj) as year'), DB::raw('COUNT(*) as count'))
            ->groupBy('year')
            ->orderBy('year');

        if($request->department){
            $data->where('dept_id',$request->department);
        }
        if($request->emp_type){
            $data->where('emp_type',$request->emp_type);

        }
        if($request->branch){
            $data->where('branch_id',$request->branch);

        }
        if($request->start_date && $request->end_date){
            $data->whereDate('emp_doj', '>=', $request->start_date)
            ->whereDate('emp_doj', '<=', $request->end_date);

        }

       return response()->json(['status'=>200,'data'=>$data->get()]);

   
}

}
