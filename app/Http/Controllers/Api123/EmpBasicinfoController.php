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
      $employee_id = BasicInfo::where('emp_type',$request->emp_type)->where('is_deleted',0)->orderBy('id','DESC')->first();
     // return $employee_id;
     if($employee_id){
        $emp_type = EmployeeType::where('id',$request->emp_type)->pluck('short_name');
        $employeeId = $employee_id->employee_id +1;
        $rims_id = $emp_type[0].$employeeId;
      }
      else{
        $emp_type = EmployeeType::where('id',$request->emp_type)->pluck('short_name');
        $employeeId = 1;
        $rims_id = $emp_type[0].$employeeId;

      }
      $basic_info = new BasicInfo();
      $basic_info->employee_id = $employeeId;
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
      $basic_info->emp_id = $rims_id;
      $basic_info->login_password = Hash::make($request->password);
      $basic_info->reporting_manager = $request->manager;
      $basic_info->alternate_no = $request->alternate;
      $basic_info->created_by = 1;
      if($image = $request->file('pic')) {
            $destinationPath = 'profile_picture/';
            $profile_image = date('YmdHis') . "." . $image->getClientOriginalExtension();
            $image->move($destinationPath, $profile_image);
            $profile = $profile_image;
            $basic_info->profile_picture= $profile;
        }

         $basic_info->save();
         $basic_info->reporting_manager = $request->manager;

    // Retrieve reporting manager and their managers until reaching the top level
    $reportingManagerId = $request->manager;
    $managerIds = [];

    while ($reportingManagerId) {
        $managerInfo = BasicInfo::where('emp_id',$reportingManagerId)->first();
        //return $managerInfo;
        if (!$managerInfo) {
            break;
        }

        $managerIds[] = $managerInfo->emp_id;

        $reportingManagerId = $managerInfo->reporting_manager;
    }

    // Store employee ID and all manager IDs in a new table
    $employeeId = $basic_info->emp_id;
    foreach ($managerIds as $managerId) {
        DB::table('employee_managers')->insert([
            'emp_id' => $employeeId,
            'manager_id' => $managerId,
            'manager_status'=>1,

        ]);
       }


      $data = BasicInfo::where('id',$basic_info->id)->get(['emp_id','emp_fname','emp_lame','emp_mname',]);
      if($basic_info){
          return response()->json(['status'=>200,'message'=>'Employee Created Successfully with Employee Id'.' '.$basic_info->emp_id,'data'=>$data]);
      }
      else{
         return response()->json(['status'=>500,'message'=>'something went wrong']);
      }
    }
    public function emp_list(Request $request)
{
    $perPage = $request->per_page;
    $query = BasicInfo::join('company_details', 'company_details.id', '=', 'emp_basic_info.company_id')
        ->join('branch_details', 'branch_details.id', '=', 'emp_basic_info.branch_id')
        ->join('department', 'department.id', '=', 'emp_basic_info.dept_id')
        ->join('designation', 'designation.id', '=', 'emp_basic_info.desi_id')
        ->join('employee_type', 'employee_type.id', '=', 'emp_basic_info.emp_type')
        ->select('emp_basic_info.id', 'company_details.business_name', 'branch_details.branch_name', 'department.department_name', 'designation.designation_name', 'employee_type.emp_type', 'emp_basic_info.emp_id', 'emp_basic_info.emp_fname', 'emp_basic_info.emp_mname', 'emp_basic_info.emp_lame', 'emp_basic_info.emp_father_name', 'emp_basic_info.emp_sex', 'emp_basic_info.emp_phone', 'emp_basic_info.emp_office_phone', 'emp_basic_info.emp_email', 'emp_basic_info.emp_office_email', 'emp_basic_info.emp_dob', 'emp_basic_info.emp_doj', 'emp_basic_info.emp_status', 'emp_basic_info.login_id');

    if ($request->dept_id) {
        $emp_list = $query->where('dept_id', $request->dept_id)->where('is_deleted',0)->paginate($perPage);
    }
    elseif($request->emp_type){
        $emp_list = $query->where('emp_basic_info.emp_type', $request->emp_type)->where('is_deleted',0)->paginate($perPage);

    }
    elseif($request->name){
        $emp_list = $query->where('emp_basic_info.emp_fname', 'like', "%$request->name%")->where('is_deleted',0)->paginate($perPage);

    }

     else {
        $emp_list = $query->where('is_deleted',0)->paginate($perPage);
    }
    $totalPages = $emp_list->lastPage();
   // return $totalPages;

    return response()->json(['status' => 200, 'message' => 'List Of Employee', 'data' => $emp_list,'total_pages'=>$totalPages]);
}
    public function edit_employee($id){
        $basic_info = BasicInfo::where('emp_id',$id)->first();
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
      $basic_info = BasicInfo::where('emp_id',$id)->first();
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
      $basic_info->alternate_no = $request->alternate;
      $basic_info->reporting_manager = $request->manager;
      $basic_info->created_by = 1;
      $basic_info->save();
      $emp_history = array('company_id'=>$request->company,'emp_id'=>$basic_info->emp_id,'branch_id'=>$request->branch,'dept_id'=>$request->department,'desi_id'=>$request->designation,'emp_type'=>$request->emp_type,
                     'emp_fname'=>$request->first_name,'emp_mname'=>$request->middle_name,'emp_lame'=>$request->last_name,'emp_father_name'=>$request->father_name,'emp_sex'=>$request->gender,
                     'emp_phone'=>$request->phone,'emp_office_phone'=>$request->office_phone,'emp_email'=>$request->email,'emp_office_email'=>$request->office_email,'emp_dob'=>$request->Date_of_birth,
                     'emp_doj'=>$request->Date_of_join,'emp_doc'=>$confirmation_date,'login_id'=>$request->email,'emp_dor'=>$request->date_of_resign,'emp_eod'=>$request->end_of_date,'reporting_manager'=>$request->manager,
                     'created_by'=>1,
                    );
            DB::table('emp_history')->insert($emp_history);
      $basic_info->reporting_manager = $request->manager;
      if($request->manager){
         DB::table('employee_managers')
           ->where('emp_id', $basic_info->emp_id) 
            ->update([
               'manager_status' =>0
             ]);
         $reportingManagerId = $request->manager;
         $managerIds = [];

    while ($reportingManagerId) {
        $managerInfo = BasicInfo::where('emp_id',$reportingManagerId)->first();
        if (!$managerInfo) {
            break;
        }

        $managerIds[] = $managerInfo->emp_id;

        $reportingManagerId = $managerInfo->reporting_manager;
    }

    // Store employee ID and all manager IDs in a new table
    $employeeId = $basic_info->emp_id;
    foreach ($managerIds as $managerId) {
        DB::table('employee_managers')->insert([
            'emp_id' => $employeeId,
            'manager_id' => $managerId,
            'manager_status'=>1,

        ]);
       }

      }

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

    $emp_status = BasicInfo::where('emp_id',$request->id)->first();
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
    $emp = BasicInfo::where('emp_id',$id)->first();
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
    ->select('emp_basic_info.id','company_details.business_name','branch_details.branch_name','department.department_name','designation.designation_name','employee_type.emp_type','emp_basic_info.emp_id','emp_basic_info.emp_fname','emp_basic_info.emp_mname','emp_basic_info.emp_lame','emp_basic_info.emp_father_name','emp_basic_info.emp_sex','emp_basic_info.emp_phone','emp_basic_info.emp_office_phone','emp_basic_info.emp_email','emp_basic_info.emp_office_email','emp_basic_info.emp_dob','emp_basic_info.emp_doj','emp_basic_info.emp_status','emp_basic_info.emp_doc','emp_basic_info.login_id','emp_basic_info.profile_picture')
    ->where('emp_id',$id)
    ->get();
    return response()->json(['status' => 200, 'message' => 'Profile Details', 'data' => $emp_profile]);
}
public function get_manager($id){
    $get_manager = BasicInfo::where('dept_id',$id)->where('designation_name','Manager')->get(['id','emp_fname','emp_mname','emp_lame','emp_id']);
    return response()->json(['status' => 200, 'message' => 'Manager Details', 'data' => $get_manager]);

}
public function get_current_month_birthday(){
    $data = BasicInfo::whereRaw('Month(emp_dob) = ?', [Carbon::now()->month])->where('emp_status',1)->orderBy('emp_dob','ASC')->get(['emp_fname','emp_lame','emp_dob','id']);
    $birthday_data = [];
    foreach($data as $row){
        $date = Carbon::createFromFormat('Y-m-d', $row->emp_dob);
        $monthName = $date->format('F');
        $birth_date = $date->format('d').' '.$monthName;
        $name = $row->emp_fname.' '.$row->emp_lame;
        $birthday_data[] = array('id'=>$row->id,'name'=>$name,'birth_date'=>$birth_date);
       }
      return response()->json(['status' => 200, 'message' => 'Birthday details', 'data' =>$birthday_data]);
    }
    public function show_birthday_notification($id){
        $data = BasicInfo::where('emp_id',$id)->first();
        $today = now()->format('m-d');
        $birthday = Carbon::parse($data->emp_dob)->format('m-d');
        return response()->json(['isBirthday' => ($today === $birthday),'name'=>$data->emp_fname]);
}
   public function update_emp_id(){
        $emp_list = BasicInfo::all();
        foreach($emp_list as $row){
            //$ids = EmployeeType::where('id',$row->emp_type)->first();
            $reporting_manager = BasicInfo::where('employee_id',$row->reporting_manager)->first();
            if($reporting_manager){
                BasicInfo::where('employee_id',$row->employee_id)->update(['reporting_manager'=>$reporting_manager->emp_id]);

            }
           // BasicInfo::where('employee_id',$row->employee_id)->update(['reporting_manager'=>$reporting_manager['emp_id']]);
         }
     }
     public function delete_emp($id){
        $data = BasicInfo::where('emp_id',$id)->update(['is_deleted'=>1,'emp_status'=>0]);
        return response()->json(['status'=>200,'message'=>'emp deleted successfully']);
      
      
      
      }
      public function get_all_managers(){
        $get_manager = BasicInfo::where('designation_name','Manager')->get(['id','emp_fname','emp_mname','emp_lame','emp_id']);
        return response()->json(['status'=>200,'data'=>$get_manager]);
      }
      public function manager_based_employee($id){
        $data = BasicInfo::where('reporting_manager',$id)->get(['id','emp_fname','emp_mname','emp_lame','emp_id']);
         return response()->json(['status'=>200,'data'=> $data]);
      }
      public function save_emp_manager(Request $request){
        
          $selectedEmployees = $request->input('selectedEmployees');
          $managerId = $request->input('managerId');
          //DB::table('employee_managers')->whereIn('emp_id',$selectedEmployees)->update(['manager_status'=>0]);
          DB::table('employee_managers')->whereIn('emp_id', $selectedEmployees)->update(['manager_status'=>0]);
      
          $upperLevelManagers = $this->getUpperLevelManagersRecursive($managerId);


      
          foreach ($selectedEmployees as $employeeId) {
              BasicInfo::where('emp_id', $employeeId)->update(['reporting_manager' => $managerId]);
      
              foreach ($upperLevelManagers as $upperLevelManager) {
                  DB::table('employee_managers')->insert([
                      'emp_id' => $employeeId,
                      'manager_id' => $upperLevelManager->emp_id,
                      'manager_status'=>1,
                  ]);
              }
          }
      
          return response()->json(['status' => 200,'message'=>'Assign successfully']);
      }
      
      private function getUpperLevelManagersRecursive($managerId, $upperLevelManagers = [])
      {
          $manager = BasicInfo::where('emp_id', $managerId)->first();
      
          if ($manager && $manager->reporting_manager !== 0) {
              $upperLevelManagers[] = $manager;
      
              return $this->getUpperLevelManagersRecursive($manager->reporting_manager, $upperLevelManagers);
          }
      
          return $upperLevelManagers;
      }
      public function create_attendance_row(){
        $emp_list = BasicInfo::where('emp_status',1)->get();
        $data = [];
        foreach($emp_list as $row){
            $current_date = Carbon::now()->format('Y-m-d');
            $dayOfWeek = Carbon::parse($current_date)->format('l');
            $data[] = array('emp_id'=>$row->emp_id,'attendance_date'=>$current_date,'weekday'=>$dayOfWeek);
          }
             DB::table('attendance')->insert($data);
        }
      
}