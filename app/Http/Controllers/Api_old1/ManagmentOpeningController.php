<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ManagmentOpening;
use Validator;
use DB;
use App\Models\BasicInfo;
use App\Models\Account;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Sallary;
use App\Models\Branch;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\FollowupAttribute;

class ManagmentOpeningController extends Controller
{
    public function save_opening(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'title' => 'required',
        'department_from'=>'required',
        'opening_type'=>'required',
        //'replace_to'=>'required',
        'required_exprience'=>'required',
        'salary'=>'required',
        'skill'=>'required',
        'remark'=>'required',
       ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $job_open = new ManagmentOpening();
      $job_open->title = $request->title;
      $job_open->department_from = $request->department_from;
      $job_open->opening_type = $request->opening_type;
      $job_open->replace_to = $request->replace_to;
      $job_open->required_experience = $request->required_exprience;
      $job_open->salary_range = $request->salary;
      $job_open->skill = $request->skill;
      $job_open->remark = $request->remark;
      $job_open->created_by = $request->emp_id;
      $job_open->save();
      if($job_open){
         return response()->json(['status'=>200,'message'=>'Request Send Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }  
    }
    public function job_request_list($id){
      $data = ManagmentOpening::join('department','department.id','=','managment_opening.department_from')
             ->leftjoin('emp_basic_info','emp_basic_info.emp_id','=','managment_opening.replace_to')
             ->select('department.department_name','managment_opening.*','emp_basic_info.emp_fname')
             ->where('managment_opening.created_by',$id)
             ->get();
             return response()->json(['status'=>200,'message'=>'Job Request List','data'=>$data]);

    }
    public function job_position_request(){
      $data = ManagmentOpening::join('department','department.id','=','managment_opening.department_from')
             ->leftjoin('emp_basic_info','emp_basic_info.emp_id','=','managment_opening.replace_to')
             ->select('department.department_name','managment_opening.*','emp_basic_info.emp_fname')
             ->get();
             return response()->json(['status'=>200,'message'=>'Job Request List','data'=>$data]);

    }
    public function opening_status(Request $request){
      ManagmentOpening::where('id',$request->id)->update(['approved_status'=>$request->status,
        'approval_remark'=>$request->remark]);
      return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);

    }
    public function opening_status_edit($id){
        $data = ManagmentOpening::where('id',$id)->get(['id','approval_remark','approved_status']);
        return response()->json(['status'=>200,'message'=>'Job Opening Status','data'=>$data]);
    }
    public function opening_request_list_hr(){
        $data = ManagmentOpening::join('department','department.id','=','managment_opening.department_from')
              // ->leftjoin('assign_job_request_hr','assign_job_request_hr.request_id','managment_opening.id')
               //->leftjoin('emp_basic_info','emp_basic_info.emp_id','assign_job_request_hr.assign_to')
               ->select('department.department_name','managment_opening.*',)
              ->where('managment_opening.approved_status',1)
             ->get();
        return response()->json(['status'=>200,'message'=>'Job Opening List','data'=>$data]);

    }
   public function save_assign_job_request(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'remark' => 'required',
        'selected_employee' => 'required',
    ]);

    if($validator->fails()){
        $messages = $validator->messages();
        return response()->json(["messages" => $messages, 'status' => 400]);     
    }

    $emp_ids = explode(',', $request->selected_employee);
     foreach($emp_ids as $emp_id){
        DB::table('assign_job_request_hr')->updateOrInsert(
            [
                'request_id' => $request->request_id,
                'assign_to' => $emp_id,
            ],
            [
                'remark' => $request->remark,
                'assign_by' => $request->emp_id,
            ]
        );
    }

    return response()->json(['status' => 200, 'message' => 'Assign  successfully']);
}

    public function edit_assign_list($id){
        $data = DB::table('assign_job_request_hr')->where('request_id',$id)->get(['assign_to','remark']);
        return response()->json(['status'=>200,'message'=>'Assign List Edit','data'=>$data]);

    }
   public function followup_details($id){
    $data = DB::table('assign_job_request_hr')
           ->join('managment_opening','managment_opening.id','assign_job_request_hr.request_id')
           ->join('department','department.id','managment_opening.department_from')
           ->select('department.department_name','managment_opening.title',
           'managment_opening.required_experience','managment_opening.skill',
           'managment_opening.salary_range','managment_opening.replace_to','assign_job_request_hr.*')
           ->where('assign_to',$id)
           ->get();
     return response()->json(['status'=>200,'message'=>'Followup details','data'=>$data]);


   }
   public function save_followup_details(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required',
            'resource'=>'required',
            'status'=>'required',
            'remark'=>'required',
        ]);
    
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
        $resume = '';
        if($img = $request->file('image')) {
            //dd('hi');
                $destinationPath = 'candidate_followup/';
                $cv = date('YmdHis') . "." . $img->getClientOriginalExtension();
                $img->move($destinationPath, $cv);
                $resume = $cv;
        }
        $data = array('job_id'=>$request->job_id,'candidate_name'=>$request->name,'mobile'=>$request->mobile,'email'=>$request->email,
         'resource_from'=>$request->resource,'resume'=>$resume,'hr_status'=>$request->status,'hr_remark'=>$request->remark,'created_by'=>$request->emp_id);
          DB::table('candidate_followup_details')->insert($data);
          return response()->json(['status'=>200,'message'=>'Followup Saved Successfully']);
      }
      public function job_position_details($id){
      $data = ManagmentOpening::where('id',$id)->first();
      $department_name = Department::where('id',$data->department_from)->first();
      //$date = $data->created_date->format('Y-m-d');
      $details = array('department_name'=> $department_name->department_name,'id'=>$data->id,'opening_type'=>$data->opening_type,
      'replace_to'=>$data->replace_to,'exprience'=>$data->required_experience,'salary'=>$data->salary_range,'skill'=>$data->skill,
      'remark'=>$data->remark,'date'=>$data->created_date,'title'=>$data->title);
        return response()->json(['status'=>200,'message'=>'Job Details','data'=>$details]);

      }
      public function followup_candidate_details($id,$emp_id){
        //$data = DB::table('candidate_followup_details')->where('job_id',$id)->where('created_by',$emp_id)
        //->get();
        $data = DB::table('candidate_followup_details')->join('emp_basic_info','candidate_followup_details.created_by','emp_basic_info.emp_id')
               ->join('candidate_followup_status_details','candidate_followup_status_details.id','candidate_followup_details.hr_status')
               ->select('emp_basic_info.emp_fname','candidate_followup_status_details.name','candidate_followup_details.*')
               ->where('candidate_followup_details.job_id',$id)
               ->where('candidate_followup_details.created_by',$emp_id)
               ->get();
         return response()->json(['status'=>200,'message'=>'Candidate Details','data'=>$data]);

      }
      public function get_employee_details($id){
        $emp_details = BasicInfo::where('emp_id',$id)->first();
        $emp_department = Department::where('id',$emp_details->dept_id)->first();
        $emp_designation = Designation::where('id',$emp_details->desi_id)->first();
        $branch = Branch::where('id',$emp_details->branch_id)->first();
        $emp_salary = Sallary::where('emp_id',$id)->first();
        if($emp_salary){
          $package = $emp_salary->emp_package;

        }
        else{
          $package = '';

        }
        $data = array('emp_name'=>$emp_details->emp_fname,'emp_department'=>$emp_department->department_name,
        'emp_designation'=>$emp_designation->designation_name,'emp_branch'=>$branch->branch_name,
        'emp_package'=> $package,'doj'=>$emp_details->emp_doj);
        return response()->json(['status'=>200,'message'=>'Employee Details','data'=>$data]);
      }
      public function candidate_list($id){
        $data = DB::table('candidate_followup_details')->join('emp_basic_info','emp_basic_info.emp_id','candidate_followup_details.created_by')
                  ->join('candidate_followup_status_details','candidate_followup_status_details.id','candidate_followup_details.hr_status')
              ->select('emp_basic_info.emp_fname','candidate_followup_details.*','candidate_followup_status_details.name')
              ->where('job_id',$id)
              ->get();
        return response()->json(['status'=>200,'message'=>'Candidate Details','data'=>$data]);
        
      }
      public function candidate_details($id){
        $data = DB::table('candidate_followup_details')->where('id',$id)->first();
        return response()->json(['status'=>200,'message'=>'Candidate Details','data'=>$data]);
      }
      public function save_candidate_followup(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'proccess_status' => 'required',
            'status'=>'required',
        ]);
    
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
          $data = array('candidate_id'=>$request->candidate_id,'process_status'=>$request->proccess_status,
          'status'=>$request->status,'date'=>$request->date,'time'=>$request->time,
          'final_status'=>$request->final_status,'date_of_join'=>$request->doj,'created_by'=>$request->emp_id);
          DB::table('candidate_followup_history')->insert($data);
          return response()->json(['status'=>200,'message'=>'Followup Saved Successfully']);
         }
      public function candidate_followup_history($id){
        $candidate_history = [];
        $data = DB::table('candidate_followup_history')->where('candidate_id',$id)->get();
        foreach($data as $row){
          $emp_name = BasicInfo::where('emp_id',$row->created_by)->first();
          $process_status = FollowupAttribute::where('id',$row->process_status)->first();
          $date = Carbon::parse($row->created_date)->format('Y-m-d');
          $candidate_history[] = array('proccess_status'=>$process_status->name,'status'=>$row->status,
          'date'=>$row->date,'time'=>$row->time,'final_status'=>$row->final_status,
          'data_of_join'=>$row->date_of_join,'created_by'=>$emp_name->emp_fname,'created_date'=>$date);
        }
        return response()->json(['status'=>200,'message'=>'Employee Followup History','data'=>$candidate_history]);

      }
      public function download_offer_leter(){
        $pdf = PDF::loadView('pdf.offer_letter');
        return $pdf->download('invoice.pdf');
        //return view('pdf.offer_letter');
      }

}