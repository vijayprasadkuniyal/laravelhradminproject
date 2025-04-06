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
use App\Exports\AttendanceReportExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\WFH;

class EmpBasicinfoController extends Controller
{
    public function save_employee(Request $request){
      $data_array = [];
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
      $basic_info->emp_type = $request->emp_type;
      $basic_info->emp_fname = $request->first_name;
      $basic_info->emp_mname = $request->middle_name ?? '';
      $basic_info->emp_lame = $request->last_name ?? '';
      $basic_info->emp_father_name = $request->father_name ?? '';
      $basic_info->emp_sex = $request->gender ?? '';
      $basic_info->emp_phone = $request->phone ?? '';
      $basic_info->emp_office_phone = $request->office_phone ?? '';
      $basic_info->emp_email = $request->email ?? '';
      $basic_info->emp_office_email = $request->office_email ?? '';
      $basic_info->emp_dob = $request->Date_of_birth ?? '';
      $basic_info->emp_doj = $request->Date_of_join ?? '';
      $confirmation_date = $request->Date_of_join ?? '';
      $confirmation_date =  Carbon::parse($confirmation_date)->addMonth(6);
      $confirmation_date =  $confirmation_date->format('Y-m-d');
      $basic_info->emp_doc = $confirmation_date ?? '';
      $basic_info->login_id = $request->office_email ?? '';
      $basic_info->emp_id = $rims_id;
      $basic_info->login_password = Hash::make($request->password);
      $basic_info->reporting_manager = $request->manager ?? '';
      $basic_info->alternate_no = $request->alternate ?? '';
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

    $reportingManagerId = $request->manager;
    $managerIds = [];
    $managerInfo = null;
while ($reportingManagerId) {
    $managerInfo = BasicInfo::where('emp_id', $reportingManagerId)->first();
    if (!$managerInfo) {
        break;
    }

    $managerIds[] = $managerInfo->emp_id;
    $reportingManagerId = $managerInfo->reporting_manager;
}

$reporting_to = implode(',', $managerIds);
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
        ->leftjoin('branch_details', 'branch_details.id', '=', 'emp_basic_info.branch_id')
        ->leftjoin('department', 'department.id', '=', 'emp_basic_info.dept_id')
        ->leftjoin('designation', 'designation.id', '=', 'emp_basic_info.desi_id')
        ->leftjoin('employee_type', 'employee_type.id', '=', 'emp_basic_info.emp_type')
        ->select('emp_basic_info.id', 'company_details.business_name', 'branch_details.branch_name', 'department.department_name', 'designation.designation_name', 'employee_type.emp_type', 'emp_basic_info.emp_id', 'emp_basic_info.emp_fname', 'emp_basic_info.emp_mname', 'emp_basic_info.emp_lame', 'emp_basic_info.emp_father_name', 'emp_basic_info.emp_sex', 'emp_basic_info.emp_phone', 'emp_basic_info.emp_office_phone', 'emp_basic_info.emp_email', 'emp_basic_info.emp_office_email', 'emp_basic_info.emp_dob', 'emp_basic_info.emp_doj', 'emp_basic_info.emp_status', 'emp_basic_info.login_id');

    if ($request->dept_id) {
         $query->where('dept_id', $request->dept_id);
    }
    if($request->emp_type){
         $query->where('emp_basic_info.emp_type', $request->emp_type);

    }
    if($request->name){
        $query->where('emp_basic_info.emp_fname', 'like', "%$request->name%");

    }
    if($request->emp_status == 'active'){
        $query->where('emp_basic_info.emp_status',1);

    }
    else if($request->emp_status == 'inactive'){
        $query->where('emp_basic_info.emp_status',0);

    }
    if($request->confirmed_status =='probation'){
        $query->where('emp_basic_info.is_confirmed',0);


    }
    else if($request->confirmed_status =='confirmed'){
        $query->where('emp_basic_info.is_confirmed',1);
    }

    $emp_list = $query->where('is_deleted',0)->paginate($perPage);

    $totalPages = $emp_list->lastPage();
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
      $basic_info->emp_fname = $request->first_name ?? '';
      $basic_info->emp_mname = $request->middle_name ?? '';
      $basic_info->emp_lame = $request->last_name ?? '';
      $basic_info->emp_father_name = $request->father_name ?? '';
      $basic_info->emp_sex = $request->gender ?? '';
      $basic_info->emp_phone = $request->phone ?? '';
      $basic_info->emp_office_phone = $request->office_phone ?? '';
      $basic_info->emp_email = $request->email??'';
      $basic_info->emp_office_email = $request->office_email ?? '';
      $basic_info->emp_dob = $request->Date_of_birth ?? '';
      $basic_info->emp_doj = $request->Date_of_join ?? '';
      $confirmation_date = $request->Date_of_join ?? '';
      $confirmation_date =  Carbon::parse($confirmation_date)->addMonth(6);
      $confirmation_date =  $confirmation_date->format('Y-m-d') ?? '';
      $basic_info->emp_doc = $confirmation_date ?? '';
      $basic_info->login_id =$request->office_email ?? '';
      $basic_info->emp_dor = $request->date_of_resign ?? '';
      $basic_info->emp_eod = $request->end_of_date ?? '';
      $basic_info->alternate_no = $request->alternate ??'';
      $basic_info->reporting_manager = $request->manager ?? '';
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
      $basic_info->reporting_manager = $request->manager;
      if(($emp_manager_id != $request->manager) || ($emp_dept_id != $request->department) || ($emp_desi_id != $request->designation)){
          $last_inserted_id =  DB::table('employee_managers')
           ->where('emp_id', $basic_info->emp_id)
           ->orderBy('created_date','DESC')->first();
           
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

    $emp_reporting = DB::table('employee_managers')->where('emp_id',$request->id)->orderBy('id','DESC')->first();
    //return  $emp_reporting;

    DB::table('employee_managers')->where('emp_id',$request->id)->where('id',$emp_reporting->id)
         ->update(['status'=>$request->status,'to_date'=>Carbon::now()->format('Y-m-d')]);


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
    //DB::table('attendance_regularize_request')->where('seen_status',0)->update(['seen_status'=>1]);
    $list = [];
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
    $data = array('status'=>$atte_status,'remark'=>$request->remark,'action_by'=>$request->action_by);
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

   public function attendance_report(Request $request)
   {
     //return DB::table('attendance')->whereMonth('attendance_date','11')->where('emp_id','RIMS415')->get();
       $month = $request->month ?? Carbon::now()->month;
       $year = $request->year ?? Carbon::now()->year;
       $working_days = 0;
   
       $data = DB::table('attendance')
           ->leftJoin('emp_basic_info', 'emp_basic_info.emp_id', '=', 'attendance.emp_id')
           ->leftJoin('department', 'department.id', '=', 'emp_basic_info.dept_id')
           ->select(
               'emp_basic_info.emp_fname',
               'emp_basic_info.emp_lame',
               'emp_basic_info.emp_id',
               'emp_basic_info.dept_id',
               'attendance.attendance_date',
               'attendance.status',
               'attendance.leave_status',  
               'attendance.is_half_day',  
               'department.department_name'
           )
           ->whereMonth('attendance.attendance_date', $month)
           ->whereYear('attendance.attendance_date', $year)
           ->get();
   
       $roster = DB::table('emp_roster')
           ->whereMonth('week_off', $month)
           ->whereYear('week_off', $year)
           ->get()
           ->groupBy('emp_id');
   
       $leaves = DB::table('attendance')
           ->whereMonth('attendance_date', $month)
           ->whereYear('attendance_date', $year)
           ->where('leave_status', 1)
           ->where('is_half_day',0)
           ->get()
           ->groupBy('emp_id');
   
       $holidays = DB::table('leave_details')
           ->whereMonth('date', $month)
           ->whereYear('date', $year)
           ->pluck('date')
           ->toArray();
   
       $groupedData = [];
       foreach ($data as $item) {
           $empId = $item->emp_id;
           $date = Carbon::parse($item->attendance_date);
           $formattedDate = $date->format('Y-m-d');
           $weekday = $date->format('l');
           $isWeekOff = false;
           if ($item->dept_id != 4) {
               $isWeekOff = in_array($weekday, ['Saturday', 'Sunday']);
           }
           $isRosterOff = false;
           if($item->dept_id == 4) {
            $isRosterOff = isset($roster[$empId]) && $roster[$empId]->where('week_off', $formattedDate)->isNotEmpty();
           }
           $isLeave = isset($leaves[$empId]) && $leaves[$empId]->where('attendance_date', $formattedDate)->isNotEmpty();
           $isHoliday = in_array($formattedDate, $holidays);
   
           
           if ($isHoliday) {
               $status = 'HL'; 
               $working_days++;
           } elseif ($isWeekOff) {
               $status = 'WO'; 
               $working_days++;
           } elseif ($isRosterOff) {
               $working_days++;
               $status = 'WO'; 
           } elseif ($isLeave) {
               $working_days++;
               $status = 'L';  
           } elseif ($item->status == 1) {
               $working_days++;
               $status = 'P'; 
           } elseif (($item->status == 2 && $item->leave_status == 0 && $item->is_half_day == 1)
                      || ($item->leave_status == 0 && $item->is_half_day == 1)||($item->status == 2)) {
               $status = 'HD'; 
           }
            elseif($item->is_half_day == 1 && $item->leave_status == 1 && $item->status == 2 ){
                $working_days++;
                $status = 'P'; 
             }

            elseif ($item->status == 3) {
               $working_days++;
               $status = 'SL'; 
           }
           else{
            $status = 'A';

           }
   
           $empName = $item->emp_fname . ' ' . $item->emp_lame;
           if (!isset($groupedData[$empName])) {
               $groupedData[$empName] = [
                   'department' => $item->department_name,
                   'emp_id' => $item->emp_id,
                   'attendance' => [],
                   'counts' => [
                      // 'P' => 0,  
                       'A' => 0,   
                       'HD' => 0,  
                       'SL' => 0,  
                       'L' =>0,
                   ],
                   'total_working_days' =>$working_days,  
               ];
           }
   
           $groupedData[$empName]['attendance'][$formattedDate] = $status;
   
           if (isset($groupedData[$empName]['counts'][$status])) {
               $groupedData[$empName]['counts'][$status]++;
           }
       }
   
       $daysInMonth = Carbon::create($year, $month)->daysInMonth;
       foreach ($groupedData as $empName => $empData) {
           $totalHalfDay = $empData['counts']['HD'];
           $totalAbsent = $empData['counts']['A'];
           $totalWorkingDays = $daysInMonth - ($totalHalfDay * 0.5) - $totalAbsent;
           $groupedData[$empName]['total_working_days'] = $totalWorkingDays;
       }
   
       $dates = [];
       for ($day = 1; $day <= $daysInMonth; $day++) {
           $date = Carbon::create($year, $month, $day);
           $dates[] = [
               'date' => $date->format('Y-m-d'),
               'day_name' => $date->format('l'),
           ];
       }

       if ($request->has('download') && $request->download === 'excel') {
          $fileName = "Attendance_Report_{$month}_{$year}.xlsx";

        return Excel::download(new AttendanceReport($groupedData, $dates), $fileName);
    }
   
       return response()->json([
           'status' => 200,
           'dates' => $dates,
           'data' => $groupedData,
       ]);
   }

   public function send_welcome_mail_message(Request $request){

    $emp_details = BasicInfo::where('emp_id',$request->emp_id)->leftjoin('department','emp_basic_info.dept_id','department.id')
                  ->leftjoin('designation','designation.id','emp_basic_info.desi_id')
                  ->leftjoin('branch_details','branch_details.id','emp_basic_info.branch_id')
                  ->select('emp_basic_info.emp_fname','emp_basic_info.emp_lame','department.department_name',
                  'designation.designation_name','branch_details.branch_name','emp_basic_info.emp_office_email')
                  ->where('emp_id',$request->emp_id)->first();

    $body = view('email_template.welcome', [
        'first_name' => $emp_details->emp_fname,
        'last_name' => $emp_details->emp_lame,
        'email' => $emp_details->emp_office_email,
        'department'=>$emp_details->department_name,
        'designation'=>$emp_details->designation_name,
        'branch'=>$emp_details->branch_name,
    ])->render();

    $emailData = [
        'email_id' => '2',
        'email_to' =>  $emp_details->emp_office_email,
        'subject' => 'Welcome Mail',
        'body' => $body,
    ];

    $myRequest = new Request();
    $myRequest->replace($emailData);
    $returnData = CommunicationApis::send_email($myRequest);
    return response()->json(['status'=>200,'message'=>'Mail Send Successfully']);

}

public function get_all_emp_details(Request $request){
   if($request->latter_type == 'confirmation_latter'){
     $data = BasicInfo::where('dept_id',$request->dept_id)->where('emp_status',1)->get(['emp_id','emp_fname','emp_lame']);
   }
   else{
     $data = BasicInfo::where('dept_id',$request->dept_id)->get(['emp_id','emp_fname','emp_lame']);
   }

   return response()->json(['status'=>200,'data'=>$data]);

}
public function update_punch_in_time(Request $request){

    $current_date = Carbon::now()->format('Y-m-d');
    $emp_details = BasicInfo::where('emp_id',$request->emp_id)->first();
    $branch_details = Branch::where('id',$emp_details->branch_id)->first();
    //return  $branch_details;

    list($office_lat, $office_long) = explode(',', $branch_details->branch_lat_long);

    $office_lat = (float) $office_lat;
    $office_long = (float) $office_long;
    
    $current_lat = $request->latitude;
    $current_long = $request->longitude;

    $lat_long = $current_lat . ',' . $current_long;
    return  $lat_long;

    $distance = $this->haversineGreatCircleDistance($office_lat, $office_long, $current_lat, $current_long);
    //return $distance;
     if(floor($distance)<=$branch_details->distance){
          Attendance::where('emp_id',$request->emp_id)
          ->where('attendance_date',$current_date)->update(['punch_in_date_time'=>Carbon::now(),
          'login_lat_long'=>$lat_long]);

        return response()->json(['status'=>200,'message'=>'Punch In Successfully']);
    }

    else{
        $check_wfh = WFH::where('employee_id',$request->emp_id)
                     ->where('days_from',$current_date)->where('status',1)->exists();
        if($check_wfh){
            Attendance::where('emp_id',$request->emp_id)
          ->where('attendance_date',$current_date)->update(['punch_in_date_time'=>Carbon::now(),
          'login_lat_long'=>$lat_long]);

            return response()->json(['status'=>200,'message'=>'Punch In Successfully']);
        }
        else{
            return response()->json([
                'status' => 204,
                'message' => "You might not be within the office radius or connected to the office network. 
                              If you are working from home, make sure to request your manager to allow WFH before punching in."
            ]);
            
        }

     }
    }

private function haversineGreatCircleDistance($lat1, $long1, $lat2, $long2)
{
    $earthRadius = 6371000; 
    $lat1 = deg2rad($lat1);
    $long1 = deg2rad($long1);
    $lat2 = deg2rad($lat2);
    $long2 = deg2rad($long2);

    $latDiff = $lat2 - $lat1;
    $longDiff = $long2 - $long1;
    $a = sin($latDiff / 2) ** 2 + cos($lat1) * cos($lat2) * sin($longDiff / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}



public function punch_in_show_button_condition($emp_id)
{
    $current_date = Carbon::now()->format('Y-m-d');

    $attendance = Attendance::where('emp_id', $emp_id)
                            ->where('attendance_date', $current_date)
                            ->first();
    if ($attendance && $attendance->punch_in_date_time == '') {
        $punch_in_time_updated = 'Yes';
    } else {
        $punch_in_time_updated = 'No';
    }

    return response()->json(['status' => 200, 'show_button' => $punch_in_time_updated]);
}

// public function auto_update_break_time(){
//     $lunch_break_in_min = 40;
//     $tea_break_in_min = 15;
//     $meeting_break_in_min = 60;
//     $data = DB::table('store_employee_break_time')->whereNull('end_time')
//            ->where('date',Carbon::now()->format('Y-m-d'))->get();
//     foreach($data as $row){
//             $start_time = $row->start_time;
//             $current_time = Carbon::now();
//             $diffInMinutes = Carbon::parse($start_time)->diffInMinutes($current_time);
//             if($row->break_type =='lunch'){
//                 if($diffInMinutes>=$lunch_break_in_min){
//                     DB::table('store_employee_break_time')->
//                       where('date',Carbon::now()->format('Y-m-d'))
//                       ->update(['end_time'=>Carbon::now(),'status'=>0,'total_time'=>$diffInMinutes]);

//                 }

//             }
//             if($row->break_type =='tea'){
//                 if($diffInMinutes>=$tea_break_in_min){
//                     DB::table('store_employee_break_time')->
//                       where('date',Carbon::now()->format('Y-m-d'))
//                       ->update(['end_time'=>Carbon::now(),'status'=>0,'total_time'=>$diffInMinutes]);
                    
//                 }
                
//             }
//             if($row->break_type =='meeting_break'){
//                 if($diffInMinutes>=$meeting_break_in_min){
//                     DB::table('store_employee_break_time')->
//                       where('date',Carbon::now()->format('Y-m-d'))
//                       ->update(['end_time'=>Carbon::now(),'status'=>0,'total_time'=>$diffInMinutes]);
                    
//                 }
                
//             }
//             $total_break_time = DB::table('store_employee_break_time')
//                               ->where('emp_id',$row->emp_id)
//                               ->where('date',Carbon::now()->format('Y-m-d'))
//                               ->sum('total_time');
//             $total_break_time_in_hours = round($total_break_time / 60, 2);
//             Attendance::where('emp_id',$row->emp_id)
//                 ->where('attendance_date',Carbon::now()->format('Y-m-d'))
//                 ->update(['break_time'=>$total_break_time_in_hours]);
//             }

//             return response()->json(['status'=>200]);
//            }
        

   
}