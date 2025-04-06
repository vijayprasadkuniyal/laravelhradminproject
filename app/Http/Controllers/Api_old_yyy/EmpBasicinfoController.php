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
use App\Models\Attendance;
use App\Models\LeaveType;
use App\Models\EmployeeLeaveBlance;

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
      $department_name =  Department::where('id',$request->department)->pluck('department_name');
      //$basic_info->designation_name = $designation_name[0]; 
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
      $basic_info->login_id = $request->office_email;
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
         if($request->followup_candidate){
          DB::table('candidate_followup_details')->where('id',$request->followup_candidate)->update(['emp_id'=>$basic_info->emp_id]);

         }
         $emp_history = array('company_id'=>$request->company,'emp_id'=>$basic_info->emp_id,'branch_id'=>$request->branch,'dept_id'=>$request->department,'desi_id'=>$request->designation,'emp_type'=>$request->emp_type,
         'emp_fname'=>$request->first_name,'emp_mname'=>$request->middle_name,'emp_lame'=>$request->last_name,'emp_father_name'=>$request->father_name,'emp_sex'=>$request->gender,
         'emp_phone'=>$request->phone,'emp_office_phone'=>$request->office_phone,'emp_email'=>$request->email,'emp_office_email'=>$request->office_email,'emp_dob'=>$request->Date_of_birth,
         'emp_doj'=>$request->Date_of_join,'emp_doc'=>$confirmation_date,'login_id'=>$request->email,'emp_dor'=>$request->date_of_resign,'emp_eod'=>$request->end_of_date,'reporting_manager'=>$request->manager,
         'created_by'=>1,
        );
         DB::table('emp_history')->insert($emp_history);
         $basic_info->reporting_manager = $request->manager;

    $reportingManagerId = $request->manager;
    $managerIds = [];

    while ($reportingManagerId) {
        $managerInfo = BasicInfo::where('emp_id',$reportingManagerId)->first();
        //return $managerInfo;
        if (!$managerInfo) {
            break;
        }

        $managerIds[] = $managerInfo->emp_id;
        $reporting_to = implode(',',$managerIds);

        $reportingManagerId = $managerInfo->reporting_manager;
    }

        $employeeId = $basic_info->emp_id;
        DB::table('employee_managers')->insert([
            'emp_id' => $employeeId,
            'dept_manager'=>$request->manager,
            'reporting_to' => $reporting_to,
            'status'=>1,
            'from_date'=>Carbon::now()->format('Y-m-d'),
            'dept_id'=>$request->department,
            'designation_id'=>$request->designation,

        ]);

      $probation_leave = LeaveType::where('is_probation_leave',1)->where('assign_when_request',0)->get();
      $currentYear = Carbon::now()->year;
      $current_month = Carbon::now()->month;
      $month_diff = 12 - $current_month;
     foreach($probation_leave as $row){
        $per_month_leave = round($row->leave_days/12);
        $assigned_leave =  $per_month_leave* $month_diff+1;
        $check_exists = EmployeeLeaveBlance::where('leave_id',$row->leave_type)
        ->where('emp_id', $basic_info->emp_id)->where('year',Carbon::now()->year)->first();
        if(!$check_exists){

          $data_array[] = array('emp_id'=> $basic_info->emp_id,'leave_id'=>$row->id,
            'current_year_assign_leave'=>$assigned_leave,
            'previous_year_forward_leave'=>0,
            'total_leave'=>$assigned_leave,
            'leave_taken'=>0,'remaining'=>$assigned_leave,
            'year'=> $currentYear,
            'is_probation_leave'=>$row->is_probation_leave,
            'is_confirmation_leave'=>$row->is_confirmation_leave,'is_request_leave'=>$row->assign_when_request);


        }
        }

        DB::table('emp_leave_blance')->insert($data_array);



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
    if($request->emp_type){
        $emp_list = $query->where('emp_basic_info.emp_type', $request->emp_type)->where('is_deleted',0)->paginate($perPage);

    }
    if($request->name){
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
        //return 'hhhh'
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
      $emp_manager_id =  $basic_info->reporting_manager;
      $emp_dept_id =  $basic_info->dept_id;
      $emp_desi_id =  $basic_info->desi_id;

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
      $basic_info->login_id =$request->office_email;
      $basic_info->emp_dor = $request->date_of_resign;
      $basic_info->emp_eod = $request->end_of_date;
      $basic_info->alternate_no = $request->alternate;
      $basic_info->reporting_manager = $request->manager;
      if($basic_info->login_password !=$request->password){
        $basic_info->login_password = Hash::make($request->password);

      }
      $basic_info->created_by = 1;
      if($image = $request->file('pic')) {
        //return 'hi';
        $destinationPath = 'profile_picture/';
        $profile_image = date('YmdHis') . "." . $image->getClientOriginalExtension();
        $image->move($destinationPath, $profile_image);
        $profile = $profile_image;
        $basic_info->profile_picture= $profile;
      }
      $basic_info->save();
      $emp_history = array('company_id'=>$request->company,'emp_id'=>$basic_info->emp_id,'branch_id'=>$request->branch,'dept_id'=>$request->department,'desi_id'=>$request->designation,'emp_type'=>$request->emp_type,
                     'emp_fname'=>$request->first_name,'emp_mname'=>$request->middle_name,'emp_lame'=>$request->last_name,'emp_father_name'=>$request->father_name,'emp_sex'=>$request->gender,
                     'emp_phone'=>$request->phone,'emp_office_phone'=>$request->office_phone,'emp_email'=>$request->email,'emp_office_email'=>$request->office_email,'emp_dob'=>$request->Date_of_birth,
                     'emp_doj'=>$request->Date_of_join,'emp_doc'=>$confirmation_date,'login_id'=>$request->email,'emp_dor'=>$request->date_of_resign,'emp_eod'=>$request->end_of_date,'reporting_manager'=>$request->manager,
                     'created_by'=>1,
                    );
            DB::table('emp_history')->insert($emp_history);
            $basic_info->reporting_manager = $request->manager;





      if(($emp_manager_id != $request->manager) || ($emp_dept_id != $request->department) || ($emp_desi_id != $request->designation)){
          $last_inserted_id =  DB::table('employee_managers')
           ->where('emp_id', $basic_info->emp_id)
           ->orderBy('created_date','DESC')->first();
           //return 
        if($last_inserted_id){
          DB::table('employee_managers')->where('emp_id',$last_inserted_id->emp_id)
          ->where('created_date', $last_inserted_id->created_date)
          ->update(['status'=>0,'to_date'=>Carbon::now()->format('Y-m-d')]);
        }
        

         $reportingManagerId = $request->manager;
         $managerIds = [];

    while ($reportingManagerId) {
        $managerInfo = BasicInfo::where('emp_id',$reportingManagerId)->first();
        if (!$managerInfo) {
            break;
        }

        $managerIds[] = $managerInfo->emp_id;
        $reporting_to = implode(',',$managerIds);

        $reportingManagerId = $managerInfo->reporting_manager;
    }
    $employeeId = $basic_info->emp_id;
      DB::table('employee_managers')->insert([
        'emp_id' =>$employeeId,
        'dept_manager'=>$request->manager,
        'reporting_to' => $reporting_to,
        'status'=>1,
        'from_date'=>Carbon::now()->format('Y-m-d'),
        'dept_id'=>$request->department,
        'designation_id'=>$request->designation,

    ]);

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
    // $emp_data = [];
    // $get_manager = BasicInfo::
    //    // ->whereNotNull('reporting_manager')
    //      distinct('reporting_manager')
    //     ->get(['reporting_manager']);
    // //return $get_manager;

    // foreach ($get_manager as $data) {
    //     $emp_details = BasicInfo::where('dept_id',$id)->where('emp_id', $data->reporting_manager)->first();
    //     if ($emp_details) {
    //         $emp_data[] = [
    //             'id' => $emp_details->id,
    //             'emp_fname' => $emp_details->emp_fname,
    //             'emp_mname' => $emp_details->emp_mname,
    //             'emp_lname' => $emp_details->emp_lname,
    //             'emp_id' => $emp_details->emp_id
    //         ];
    //     }
    // }
    $data = BasicInfo::where('dept_id',$id)->where('emp_status',1)->orwhere('reporting_manager','=','0')->
    get(['emp_id','emp_fname','emp_lame']);
    
    return response()->json(['status' => 200, 'data' =>$data]);

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
      public function get_all_managers() {
        $emp_data = [];
        $get_manager = BasicInfo::
           // ->whereNotNull('reporting_manager')
             distinct('reporting_manager')
            ->get(['reporting_manager']);
        //return $get_manager;
    
        foreach ($get_manager as $data) {
            $emp_details = BasicInfo::where('emp_id', $data->reporting_manager)->where('emp_status',1)->first();
            if ($emp_details) {
                $emp_data[] = [
                    'id' => $emp_details->id,
                    'emp_fname' => $emp_details->emp_fname,
                    'emp_mname' => $emp_details->emp_mname,
                    'emp_lame' => $emp_details->emp_lame,
                    'emp_id' => $emp_details->emp_id
                ];
            }
        }
        
        return response()->json(['status' => 200, 'data' => $emp_data]);
    }
    
      public function manager_based_employee($id){
         $data = BasicInfo::where('reporting_manager',$id)->where('emp_status',1)->get(['id','emp_fname','emp_mname','emp_lame','emp_id']);
         return response()->json(['status'=>200,'data'=> $data]);
      }
      public function save_emp_manager(Request $request)
{
    $selectedEmployees = $request->input('selectedEmployees');
    $managerId = $request->input('managerId');

    
    $upperLevelManagers = $this->getUpperLevelManagersRecursive($managerId);
    $upperLevelManagerIds = array_map(function($manager) {
        return $manager->emp_id;
    }, $upperLevelManagers);

    $upperLevelManagerIdsString = implode(',', $upperLevelManagerIds);


    foreach ($selectedEmployees as $employeeId) {
        $emp_information = BasicInfo::where('emp_id', $employeeId)->first();
        $last_inserted_id = DB::table('employee_managers')->where('emp_id',$employeeId)
        ->orderBy('created_date','DESC')->first();
        if($last_inserted_id){
         DB::table('employee_managers')->where('emp_id',$last_inserted_id->emp_id)
        ->where('created_date', $last_inserted_id->created_date)
        ->update(['status'=>0,'to_date'=>Carbon::now()->format('Y-m-d')]);
        }

        BasicInfo::where('emp_id', $employeeId)->update(['reporting_manager' => $managerId]);

        DB::table('employee_managers')->insert([
            'emp_id' => $employeeId,
            'dept_manager' =>$managerId,
            'reporting_to' =>$upperLevelManagerIdsString,
            'status' => 1,
            'dept_id' => $emp_information->dept_id,
            'designation_id' => $emp_information->desi_id,
            'from_date' => Carbon::now()->format('Y-m-d'),
        ]);
    }

    return response()->json(['status' => 200, 'message' => 'Assigned successfully']);
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
           $record = Attendance::where('attendance_date', Carbon::now()->format('Y-m-d'))
           ->where('emp_id',$row->emp_id)->first();
           if(!$record){
            $current_date = Carbon::now()->format('Y-m-d');
            $dayOfWeek = Carbon::parse($current_date)->format('l');
            $data[] = array('emp_id'=>$row->emp_id,'attendance_date'=>$current_date,'weekday'=>$dayOfWeek);

           }
          }
             DB::table('attendance')->insert($data);
        }
   public function department_based_employee(Request $request){
        //$id = explode(',',$request->id);
        $data = BasicInfo::where('emp_status',1)->whereIn('dept_id',$request->id)->get(['emp_id','emp_fname','emp_lame']);
        return response()->json(['status'=>200,'message'=>'Employee List','data'=>$data]);

   }
   public function save_enquiry(Request $request){
     $emp_name = BasicInfo::where('emp_id',$request->employee_id)->first();
    $data = [];
    if($request->department && $request->employee){
      $emp_id = explode(',',$request->employee);
      foreach($emp_id as $row){
        $emp_dept = BasicInfo::where('emp_status',1)->where('emp_id',$row)->first();
        $data[] = array('selected_department'=>$emp_dept->dept_id,'selected_employee'=>$row,
          'message'=>$request->enquiry,'enquiry_from'=> $emp_name->emp_fname.' '.$emp_name->emp_lame);

      }
       DB::table('superadmin_enquiry')->insert($data); 
      }
    else{
      $dept_id = explode(',',$request->department);
      foreach($dept_id as $dept){
        $emp_id = BasicInfo::where('emp_status',1)->where('dept_id',$dept)->get();
        foreach($emp_id as $ids){
          $data[] = array('selected_department'=>$dept,'selected_employee'=>$ids->emp_id,
            'message'=>$request->enquiry,'enquiry_from'=> $emp_name->emp_fname.' '.$emp_name->emp_lame);

        }
       // $data[] = array('selected_department'=>$dept,'selected_employee'=>$emp_id->emp_id,'message'=>$request->enquiry);
      }
     DB::table('superadmin_enquiry')->insert($data);

    }
    return response()->json(['status'=>200,'message'=>'Enquiry send succesfully','data'=>$data]);
      
}
public function get_managers_details(){
    //$data = BasicInfo::where('dept_id',3)->distinct('reporting_manager');
    $data =  BasicInfo::where('dept_id',3)->distinct()->get(['reporting_manager']);
    $emp_details = [];
    foreach($data as $row){
      $employee = BasicInfo::where('emp_id',$row->reporting_manager)->where('dept_id',3)->where('emp_status',1)->first();
     // return $employee;
      if($employee){
        $emp_details[] = array('id'=>$employee->emp_id,'emp_name'=>$employee->emp_fname.' '.$employee->emp_lame);
      }

    }
    return response()->json(['status'=>200,'data'=>$emp_details]);
}
public function show_emp_history($id){
    $data = DB::table('employee_managers')->where('emp_id',$id)->get();
    $list = [];
    foreach($data as $row){
    $emp_name = BasicInfo::where('emp_id',$row->emp_id)->first();
    $emp_manager = BasicInfo::where('emp_id',$row->dept_manager)->first();
    if($emp_manager){
        $dept_manager = $emp_manager->emp_fname.' '.$emp_manager->emp_lame;
    }
    else{
        $dept_manager = '';

    }
    $dept = Department::where('id',$row->dept_id)->first();
    $desi= Designation::where('id',$row->designation_id)->first();
    $list[] = array('id'=>$row->id,'emp_name'=>$emp_name->emp_fname.' '.$emp_name->emp_lame
    ,'manager'=>$dept_manager,
    'department'=>$dept->department_name,'designation'=>$desi->designation_name,
    'from'=>$row->from_date,'to'=>$row->to_date);
    }
    return response()->json(['status'=>200,'data'=>$list]);


}
public function attendance_regularize_list($id){
    $data = DB::table('attendance_regularize_request')
    ->join('emp_basic_info','emp_basic_info.emp_id','=','attendance_regularize_request.emp_id')
    ->select('emp_basic_info.emp_fname','emp_basic_info.emp_lame',
    'attendance_regularize_request.id','attendance_regularize_request.date',
    'attendance_regularize_request.status','attendance_regularize_request.reason',
    'attendance_regularize_request.remark')
    ->where('attendance_regularize_request.emp_id',$id)->get();
     return response()->json(['status'=>200,'data'=>$data]);
}

public function show_regulrize_data($id){
    DB::table('attendance_regularize_request')->where('seen_status',0)->update(['seen_status'=>1]);
    $list = [];
    $dept_id = Department::where('id',$id)->first();
    if($dept_id->department_name == 'HR'){
        $list = DB::table('attendance_regularize_request')
        ->join('emp_basic_info','emp_basic_info.emp_id','=','attendance_regularize_request.emp_id')
        ->select('emp_basic_info.emp_fname','emp_basic_info.emp_lame',
        'attendance_regularize_request.id','attendance_regularize_request.date',
        'attendance_regularize_request.status','attendance_regularize_request.reason',
        'attendance_regularize_request.remark','attendance_regularize_request.emp_id')
        ->orderBy('attendance_regularize_request.id','DESC')
        ->get();
         return response()->json(['status'=>200,'data'=>$list]);

    }
    else{
        return response()->json(['status'=>200,'data'=>$list]);

    }
}
public function send_attendance_request(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'date'=>'required',
        'reason'=>'required',
        ]);
    if($validator->fails()){
         $messages=$validator->messages();
         return response()->json(["messages"=>$messages,'status'=>400]);     
   }
    $data = array('emp_id'=>$request->emp_id,'date'=>$request->date,
    'reason'=>$request->reason);
    DB::table('attendance_regularize_request')->insert($data);
    DB::table('attendance')->where('emp_id',$request->emp_id)
    ->where('attendance_date',$request->date)->update(['remark'=>$request->reason]);
    return response()->json(['status'=>200,'message'=>'Request Send Successfully']);
   }
   public function save_attendance_regularize(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'date'=>'required',
        'status'=>'required',
        ]);
    if($validator->fails()){
         $messages=$validator->messages();
         return response()->json(["messages"=>$messages,'status'=>400]);     
    }
    if($request->status ==0){
        $atte_status = 'Absent';


    }
    if($request->status ==1){
        $atte_status = 'Present';
        
    }
    if($request->status ==2){
        $atte_status = 'Half Day';

        
    }
    $data = array('status'=>$atte_status,'remark'=>$request->remark);
    DB::table('attendance_regularize_request')->where('id',$request->ids)->update($data);

    Attendance::where('emp_id',$request->emp_id)->
    where('attendance_date',$request->date)->update(['status'=>$request->status,'hr_remark'=>$request->remark]);
    return response()->json(['status'=>200,'message'=>'Updated Successfully']);




   }

   public function check_reporting_manager($emp_id){
    $emp_manager = 'No';
    if($emp_id !='RIMS1'){
      $data = BasicInfo::where('reporting_manager',$emp_id)->exists();
      if($data){
          $emp_manager = 'Yes';
       }
     else{
      $emp_manager = 'No';
      }
    }
   

    return response()->json(['status'=>200,'data'=>$emp_manager]);

   }



   

}