<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BasicInfo;
use Validator;
use Hash;
use DB;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeType;
use App\Models\Account;
use Carbon\Carbon;

class EmpBasicinfoController extends Controller
{
    public function save_employee(Request $request){
    if (BasicInfo::count() === 0) {
        $employeeId = 1;
    } else {
        $Id = DB::table('emp_basic_info')->max('id') + 1;
        $employeeId = $Id;
    }

     $input = $request->all();
     $validator = Validator::make($input, [
        'company' => 'required',
        'branch'=>'required',
        'department' => 'required',
        'designation'=>'required',
        'emp_type'=>'required',
        'first_name'=>'required',
        'father_name'=>'required',
        'gender'=>'required',
        'phone'=>'required',
        'email'=>'required',
        'Date_of_birth'=>'required',
        'Date_of_join'=>'required',
        
      ]);
      if($validator->fails()){
            $messages=$validator->messages();
            //$messages->add('company_logo', 'The company logo is required.');
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $basic_info = new BasicInfo();
      $basic_info->emp_id = $employeeId;
      $basic_info->company_id = $request->company;
      $basic_info->branch_id = $request->branch;
      $basic_info->dept_id = $request->department;
      $basic_info->desi_id = $request->designation;
      $designation_name = Designation::where('id',$request->designation)->pluck('designation_name');
      $basic_info->designation_name = $designation_name[0];
      $basic_info->emp_type = $request->emp_type;
      $basic_info->emp_fname = $request->first_name;
      $basic_info->emp_mname = $request->middle_name;
      $basic_info->emp_lame = $request->last_name;
      $basic_info->emp_father_name = $request->father_name;
      $basic_info->emp_sex = $request->gender;
      $basic_info->emp_phone = $request->phone;
      $basic_info->emp_office_phone = $request->office_phone;
      $basic_info->emp_email = $request->email;
      $basic_info->emp_office_email = $request->office_email;
      $basic_info->emp_dob = $request->Date_of_birth;
      $basic_info->emp_doj = $request->Date_of_join;
      $confirmation_date = $request->Date_of_join;
      $confirmation_date =  Carbon::parse($confirmation_date)->addMonth(6);
      $confirmation_date =  $confirmation_date->format('Y-m-d');
      $basic_info->emp_doc = $confirmation_date;
      $basic_info->login_id = $request->email;
      $basic_info->login_password = Hash::make($request->password);
      $basic_info->reporting_manager = $request->manager;
      $basic_info->created_by = 1;
      if($image = $request->file('pic')) {
        //dd('hi');
            $destinationPath = 'profile_picture/';
            $profile_image = date('YmdHis') . "." . $image->getClientOriginalExtension();
            $image->move($destinationPath, $profile_image);
            $profile = $profile_image;
            $basic_info->profile_picture= $profile;
        }
      $basic_info->save();
      $data = BasicInfo::where('id',$basic_info->id)->get(['emp_id','emp_fname','emp_lame','emp_mname',]);
      if($basic_info){
          return response()->json(['status'=>200,'message'=>'Employee Created Successfully','data'=>$data]);
      }
      else{
         return response()->json(['status'=>500,'message'=>'something went wrong']);
      }
    }
    public function emp_list(){
        $emp_list = BasicInfo::join('company_details','company_details.id','=','emp_basic_info.company_id')
                  ->join('branch_details','branch_details.id','=','emp_basic_info.branch_id')
                  ->join('department','department.id','=','emp_basic_info.dept_id')
                  ->join('designation','designation.id','=','emp_basic_info.desi_id')
                  ->join('employee_type','employee_type.id','=','emp_basic_info.emp_type')
                  ->select('emp_basic_info.id','company_details.business_name','branch_details.branch_name','department.department_name','designation.designation_name','employee_type.emp_type','emp_basic_info.emp_id','emp_basic_info.emp_fname','emp_basic_info.emp_mname','emp_basic_info.emp_lame','emp_basic_info.emp_father_name','emp_basic_info.emp_sex','emp_basic_info.emp_phone','emp_basic_info.emp_office_phone','emp_basic_info.emp_email','emp_basic_info.emp_office_email','emp_basic_info.emp_dob','emp_basic_info.emp_doj','emp_basic_info.emp_status','emp_basic_info.login_id')
                  ->get();
        return response()->json(['status'=>200,'message'=>'List Of Employee','data'=>$emp_list]);

    }
    public function edit_employee($id){
        $basic_info = BasicInfo::findOrFail($id);
         return response()->json(['status'=>200,'message'=>'Basic Info Details','basic_info'=>$basic_info]);
     }
     public function update_employee(Request $request,$id){
        $input = $request->all();
        $validator = Validator::make($input, [
        'company' => 'required',
        'branch'=>'required',
        'department' => 'required',
        'designation'=>'required',
        'emp_type'=>'required',
        'first_name'=>'required',
        'father_name'=>'required',
        'gender'=>'required',
        'phone'=>'required',
        'email'=>'required',
        'Date_of_birth'=>'required',
        'Date_of_join'=>'required',
        
      ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $basic_info = BasicInfo::findOrFail($id);
      $basic_info->company_id = $request->company;
      $basic_info->branch_id = $request->branch;
      $basic_info->dept_id = $request->department;
      $basic_info->desi_id = $request->designation;
      $basic_info->emp_type = $request->emp_type;
      $basic_info->emp_fname = $request->first_name;
      $basic_info->emp_mname = $request->middle_name;
      $basic_info->emp_lame = $request->last_name;
      $basic_info->emp_father_name = $request->father_name;
      $basic_info->emp_sex = $request->gender;
      $basic_info->emp_phone = $request->phone;
      $basic_info->emp_office_phone = $request->office_phone;
      $basic_info->emp_email = $request->email;
      $basic_info->emp_office_email = $request->office_email;
      $basic_info->emp_dob = $request->Date_of_birth;
      $basic_info->emp_doj = $request->Date_of_join;
      $confirmation_date = $request->Date_of_join;
      $confirmation_date =  Carbon::parse($confirmation_date)->addMonth(6);
      $confirmation_date =  $confirmation_date->format('Y-m-d');
      $basic_info->emp_doc = $confirmation_date;
      $basic_info->login_id = $request->email;
      $basic_info->emp_dor = $request->date_of_resign;
      $basic_info->emp_eod = $request->end_of_date;
      $basic_info->login_password = Hash::make($request->password);
      $basic_info->reporting_manager = $request->manager;
      $basic_info->created_by = 1;
      $basic_info->save();
      $data = BasicInfo::where('id',$basic_info->id)->get(['emp_id','emp_fname','emp_lame','emp_mname',]);
      if($basic_info){
          return response()->json(['status'=>200,'message'=>'Employee Updated Successfully','data'=>$data]);
      }
      else{
         return response()->json(['status'=>500,'message'=>'something went wrong']);
      }

     }
    public function employee_status(Request $request){
    //dd('hi');
    $request->validate([
        'id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    $emp_status = BasicInfo::findOrFail($request->id);
    $emp_status->emp_status = $request->status;
    $emp_status->save();
    if($emp_status){
        return response()->json(['status' => 200, 'message' => 'Employee status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_employee_status($id){
    $emp = BasicInfo::findOrFail($id);
    if($emp){
        return response()->json(['status' => 200, 'message' => 'Employee status','data'=>$emp->emp_status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
public function company_based_branch($company_id){
    try {
        $branch = Branch::where('company_id', $company_id)->where('status',1)
            ->get(['id', 'branch_name']);

        if ($branch->isEmpty()) {
            return response()->json(['status' => 222, 'message' => 'No Branch data found']);
        }

        return response()->json(['status' => 200, 'message' => 'Branch details', 'data' => $branch]);
    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'message' => 'Internal Server Error']);
    }


}
public function active_departments_list(){
    $department = Department::where('status',1)->get(['id','department_name']);
    if($department){
        return response()->json(['status' => 200, 'message' => 'Department details','data'=>$department]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }
}
public function department_based_designation($department_id){
    try {
        $designation = Designation::where('department_id', $department_id)->where('status',1)
            ->get(['id', 'designation_name']);

        if ($designation->isEmpty()) {
            return response()->json(['status' => 222, 'message' => 'No Branch data found']);
        }

        return response()->json(['status' => 200, 'message' => 'Designation details', 'data' => $designation]);
    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'message' => 'Internal Server Error']);
    }
}
public function active_employee_type(){
    $emp_type = EmployeeType::where('status',1)->get(['id','emp_type']);
    return response()->json(['status' => 200, 'message' => 'Emp Type details', 'data' => $emp_type]);


}
public function emp_profile($id){
    $emp_profile = BasicInfo::join('company_details','company_details.id','=','emp_basic_info.company_id')
    ->join('branch_details','branch_details.id','=','emp_basic_info.branch_id')
    ->join('department','department.id','=','emp_basic_info.dept_id')
    ->join('designation','designation.id','=','emp_basic_info.desi_id')
    ->join('employee_type','employee_type.id','=','emp_basic_info.emp_type')
    ->select('emp_basic_info.id','company_details.business_name','branch_details.branch_name','department.department_name','designation.designation_name','employee_type.emp_type','emp_basic_info.emp_id','emp_basic_info.emp_fname','emp_basic_info.emp_mname','emp_basic_info.emp_lame','emp_basic_info.emp_father_name','emp_basic_info.emp_sex','emp_basic_info.emp_phone','emp_basic_info.emp_office_phone','emp_basic_info.emp_email','emp_basic_info.emp_office_email','emp_basic_info.emp_dob','emp_basic_info.emp_doj','emp_basic_info.emp_status','emp_basic_info.login_id','emp_basic_info.profile_picture')
    ->where('emp_id',$id)
    ->get();
    return response()->json(['status' => 200, 'message' => 'Profile Details', 'data' => $emp_profile]);
}
public function get_manager($id){
    $get_manager = BasicInfo::where('dept_id',$id)->where('designation_name','Manager')->get(['id','emp_fname','emp_mname','emp_lame']);
    return response()->json(['status' => 200, 'message' => 'Manager Details', 'data' => $get_manager]);

}

}