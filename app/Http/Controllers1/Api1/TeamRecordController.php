<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BasicInfo;
use App\Models\EmployeeLeave;

class TeamRecordController extends Controller
{
    public function team_record_data($id){
        $team_data =  BasicInfo::join('company_details','company_details.id','=','emp_basic_info.company_id')
        ->join('branch_details','branch_details.id','=','emp_basic_info.branch_id')
        ->join('department','department.id','=','emp_basic_info.dept_id')
        ->join('designation','designation.id','=','emp_basic_info.desi_id')
        ->join('employee_type','employee_type.id','=','emp_basic_info.emp_type')
        ->select('emp_basic_info.id','company_details.business_name','branch_details.branch_name','department.department_name','designation.designation_name','employee_type.emp_type','emp_basic_info.emp_id','emp_basic_info.emp_fname','emp_basic_info.emp_mname','emp_basic_info.emp_lame','emp_basic_info.emp_father_name','emp_basic_info.emp_sex','emp_basic_info.emp_phone','emp_basic_info.emp_office_phone','emp_basic_info.emp_email','emp_basic_info.emp_office_email','emp_basic_info.emp_dob','emp_basic_info.emp_doj','emp_basic_info.emp_status','emp_basic_info.login_id','emp_basic_info.emp_doc')
        ->where('reporting_manager',$id)
        ->get();
        return response()->json(['status'=>200,'message' => 'Team Details','data'=>$team_data]);





    }
    public function team_leave_data($id){
        $team_leave_data = EmployeeLeave::join('emp_basic_info','emp_basic_info.id','=','employee_leave.emp_id')
                         ->join('create_leave_type','create_leave_type.id','=','employee_leave.leave_id')
                         ->select('emp_basic_info.emp_fname','emp_basic_info.emp_mname','emp_basic_info.emp_lame','employee_leave.id','employee_leave.date_from','employee_leave.date_to','employee_leave.message','employee_leave.no_of_days','create_leave_type.leave_name')
                         ->where('employee_leave.reporting_manager',$id)
                         ->get();
       return response()->json(['status'=>200,'message' => 'Leave Details','data'=> $team_leave_data]);

    }
}
