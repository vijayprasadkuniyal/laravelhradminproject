<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\BasicInfo;
use carbon\Carbon;
use App\Http\Controllers\ThirdPartyApi\CommunicationApis;
use App\Models\Department;
use App\Models\Branch;

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
    
    public function send_birthday_mail() {
        $currentDate = Carbon::now();
        $currentMonth = $currentDate->month;
        $currentDay = $currentDate->day;
    
        $data = DB::table('emp_basic_info')
            ->where('emp_status', 1)
            ->whereRaw('MONTH(emp_dob) = ? AND DAY(emp_dob) = ?', [$currentMonth, $currentDay])
            ->get();

        
        
    
        foreach ($data as $row) {
            $emailData = [
                'email_id' => '2',
                'email_to' => $row->emp_office_email,
                'subject' => 'Happy Birthday' .' ' . $row->emp_fname . ' ' . $row->emp_lame,
                'body' => '<!DOCTYPE html>
                            <html lang="en">
                            <head>
                                <meta charset="UTF-8">
                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                <title>Document</title>
                            </head>
                            <body>
                                <div style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif">
                                    <div style="width:auto;margin:0px auto;border:1px solid #ddd">
                                        <div style="font-size:16px;padding:15px">
                                            <div color="#365f91" face="French Script MT">
                                                <span style="font-size:34.6667px;letter-spacing:0.133333px">
                                                    <p>Dear ' . $row->emp_fname . ' ' . $row->emp_lame . '</p>
                                                </span>
                                            </div>
                                            <br>
                                            <b style="font-family:Calibri,sans-serif;font-size:11pt">
                                                <span style="font-size:26pt;line-height:39.8667px;font-family:French Script MT;color:rgb(54,95,145);letter-spacing:0.1pt;background-image:initial;background-position:initial;background-size:initial;background-repeat:initial;background-origin:initial;background-clip:initial">
                                                    Wishing you a great&nbsp;<span>birthday</span>&nbsp;and a memorable year. From all of us...&nbsp;
                                                </span>
                                            </b>
                                            <br>
                                            <b style="font-family:Calibri,sans-serif;font-size:11pt">
                                                <span style="font-size:26pt;line-height:39.8667px;font-family:French Script MT;color:rgb(54,95,145);letter-spacing:0.1pt;background-image:initial;background-position:initial;background-size:initial;background-repeat:initial;background-origin:initial;background-clip:initial">
                                                    <img width="50%" src="https://ci3.googleusercontent.com/meips/ADKq_NZXkaHarnvIhp6nqL4qMbeHrJaglT1DkE0GC79X_WJZLrAmBdcXfkWZB9Q_IpfFZs31ClzbSkzDYQ6Lfwlz8pzdFEGNbEuLpJTap07Sj7XRAv8ziZZ95fLI8Q=s0-d-e1-ft#http://www.hr.r-ims.com/scripts/images/birthdaymailcontent/bday6.gif" alt="" class="CToWUd a6T" data-bit="iit" tabindex="0">
                                                </span>
                                            </b>
                                            <br>
                                            <b style="font-family:Calibri,sans-serif;font-size:11pt">
                                                <span style="font-size:26pt;line-height:39.8667px;font-family:French Script MT;color:rgb(54,95,145);letter-spacing:0.1pt;background-image:initial;background-position:initial;background-size:initial;background-repeat:initial;background-origin:initial;background-clip:initial">
                                                    Regards&nbsp;
                                                </span>
                                            </b>
                                            <br>
                                            <b style="font-family:Calibri,sans-serif;font-size:11pt">
                                                <span style="font-size:26pt;line-height:39.8667px;font-family:French Script MT;color:rgb(54,95,145);letter-spacing:0.1pt;background-image:initial;background-position:initial;background-size:initial;background-repeat:initial;background-origin:initial;background-clip:initial">
                                                    Rims Family
                                                </span>
                                            </b>
                                        </div>
                                    </div>
                                </div>
                            </body>
                            </html>',
            ];
    
            $myRequest = new Request();
            $myRequest->replace($emailData);
            $returnData = CommunicationApis::send_email($myRequest);
        }
    }

    public function check_emp_anniversary() {
        $currentDate = Carbon::now();
        $currentMonth = $currentDate->month;
        $currentDay = $currentDate->day;
    
        $data = DB::table('emp_basic_info')
            ->where('emp_status', 1)
            ->whereRaw('MONTH(emp_doj) = ? AND DAY(emp_doj) = ?', [$currentMonth, $currentDay])
            ->get();
        $total_employee_list = DB::table('emp_basic_info')
        ->where('emp_status', 2)->get();
    
        foreach ($data as $row) {
            $doj = Carbon::parse($row->emp_doj);
            $diffInYears = $doj->diffInYears($currentDate);
        if($diffInYears>0){
         foreach($total_employee_list as $record){
            $dept_id = BasicInfo::where('emp_id', $row->emp_id)->first();
            $dept_name = Department::where('id', $dept_id->dept_id)->first();
            $branch_name = Branch::where('id', $row->branch_id)->first();
    
            $emailData = [
                'email_id' => '2',
                'email_to' => $record->emp_office_email,
                'subject' => "Join us in celebrating {$row->emp_fname} {$row->emp_lame}'s {$diffInYears}-year anniversary!",
                'body' => '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Work Anniversary Email</title>
    </head>
    <body style="margin: 0; text-align: center;">
        <div style="text-decoration-style: initial; text-decoration-color: initial; border-collapse: collapse; background-color: #fff; font-size: 16px; height: auto; width: 800px; padding-top: 50px; margin: 0 auto;">
            <div style="margin-left: 100px; border-collapse: collapse; width: 700px; border-radius: 30px; background-color: rgb(255,255,255); vertical-align: top; height: 950px; background:url(https://ci3.googleusercontent.com/meips/ADKq_NY7ejIbHpiUOx7PCxvfU6zwFJ0JehEfT4u9uPIneL6H3rp8Aph_6MCKvh3RjQhpOFpQfFcpGis1ekUGQnN9dY8FlaotVOB1s6RKQKSWLw=s0-d-e1-ft#https://hr.r-ims.com/scripts/Happy-work-aniversary-1.gif); background-repeat: no-repeat; background-size: cover;">
                <p style="width: 100%; height: 120px; text-align: center; padding-top: 41px;">
                    <img src="https://test.biz2bizmart.com/profile_picture/profile_image.jpg" style="width: 120px; height: 120px; border-radius: 50%;" class="CToWUd" alt="Profile Image">
                </p>
                <div style="margin-left: 50px;">
                    <table width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; width: 550px; table-layout: fixed;">
                        <tbody>
                            <tr>
                                <td style="margin: 0px;">
                                    <div style="color: rgb(100, 127, 166); line-height: 1.4; padding: 30px 20px 20px 50px;">
                                        <h4>Dear <b style="color: #22b574;">' . $row->emp_fname . ' ' . $row->emp_lame . '</b> ( <b>' . $dept_name->department_name . ' in ' . $branch_name->branch_name . '</b>)</h4>
    
                                        <div face="-webkit-pictograph" size="3">
                                            Time flies so fast! Wishing a happy work anniversary for completing ' . $diffInYears . ' years of service with RIMS and being an integral part of its journey and success. Thank you for being with us and wishing for many more successful years ahead.
                                        </div>
    
                                        <h5 style="text-align: right;">All the best<br> &nbsp;&nbsp;RIMS Family</h5><br>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table> 
                </div> 
            </div>
        </div>
    </body>
    </html>',
            ];
    
            
            $myRequest = new Request();
            $myRequest->replace($emailData);
            $returnData = CommunicationApis::send_email($myRequest);
        }
    }
}
    }
    
    


    
}
