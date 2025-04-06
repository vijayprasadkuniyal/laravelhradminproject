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
        'end_date'=>'required',
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
      $job_open->expected_end_date = $request->end_date;
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
             ->select('department.department_name','managment_opening.*','emp_basic_info.emp_fname','managment_opening.close_status')
             ->where('managment_opening.created_by',$id)
             ->get();
             return response()->json(['status'=>200,'message'=>'Job Request List','data'=>$data]);

    }
    public function job_position_request(){
      $data = ManagmentOpening::join('department','department.id','=','managment_opening.department_from')
             ->leftjoin('emp_basic_info','emp_basic_info.emp_id','=','managment_opening.replace_to')
             ->select('department.department_name','managment_opening.*','emp_basic_info.emp_fname','managment_opening.close_status')
             ->orderBy('managment_opening.id','DESC')
             ->get();
             return response()->json(['status'=>200,'message'=>'Job Request List','data'=>$data]);

    }
    public function opening_status(Request $request){
      ManagmentOpening::where('id',$request->id)->update(['status'=>$request->status,
        'approval_remark'=>$request->remark]);
      return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);

    }
    public function opening_status_edit($id){
        $data = ManagmentOpening::where('id',$id)->get(['id','approval_remark','status']);
        return response()->json(['status'=>200,'message'=>'Job Opening Status','data'=>$data]);
    }
    public function opening_request_list_hr(){
        $data = ManagmentOpening::join('department','department.id','=','managment_opening.department_from')
              // ->leftjoin('assign_job_request_hr','assign_job_request_hr.request_id','managment_opening.id')
               //->leftjoin('emp_basic_info','emp_basic_info.emp_id','assign_job_request_hr.assign_to')
               ->select('department.department_name','managment_opening.*',)
               ->orderBy('managment_opening.id','DESC')
                ->where('managment_opening.status',1)
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

    public function numberToWord($num = '')
    {
        $num    = ( string ) ( ( int ) $num );
        
        if( ( int ) ( $num ) && ctype_digit( $num ) )
        {
            $words  = array( );
             
            $num    = str_replace( array( ',' , ' ' ) , '' , trim( $num ) );
             
            $list1  = array('','one','two','three','four','five','six','seven',
                'eight','nine','ten','eleven','twelve','thirteen','fourteen',
                'fifteen','sixteen','seventeen','eighteen','nineteen');
             
            $list2  = array('','ten','twenty','thirty','forty','fifty','sixty',
                'seventy','eighty','ninety','hundred');
             
            $list3  = array('','thousand','million','billion','trillion',
                'quadrillion','quintillion','sextillion','septillion',
                'octillion','nonillion','decillion','undecillion',
                'duodecillion','tredecillion','quattuordecillion',
                'quindecillion','sexdecillion','septendecillion',
                'octodecillion','novemdecillion','vigintillion');
             
            $num_length = strlen( $num );
            $levels = ( int ) ( ( $num_length + 2 ) / 3 );
            $max_length = $levels * 3;
            $num    = substr( '00'.$num , -$max_length );
            $num_levels = str_split( $num , 3 );
             
            foreach( $num_levels as $num_part )
            {
                $levels--;
                $hundreds   = ( int ) ( $num_part / 100 );
                $hundreds   = ( $hundreds ? ' ' . $list1[$hundreds] . ' Hundred' . ( $hundreds == 1 ? '' : 's' ) . ' ' : '' );
                $tens       = ( int ) ( $num_part % 100 );
                $singles    = '';
                 
                if( $tens < 20 ) { $tens = ( $tens ? ' ' . $list1[$tens] . ' ' : '' ); } else { $tens = ( int ) ( $tens / 10 ); $tens = ' ' . $list2[$tens] . ' '; $singles = ( int ) ( $num_part % 10 ); $singles = ' ' . $list1[$singles] . ' '; } $words[] = $hundreds . $tens . $singles . ( ( $levels && ( int ) ( $num_part ) ) ? ' ' . $list3[$levels] . ' ' : '' ); } $commas = count( $words ); if( $commas > 1 )
            {
                $commas = $commas - 1;
            }
             
            $words  = implode( ', ' , $words );
             
            $words  = trim( str_replace( ' ,' , ',' , ucwords( $words ) )  , ', ' );
            if( $commas )
            {
                $words  = str_replace( ',' , ' and' , $words );
            }
             
            return $words;
        }
        else if( ! ( ( int ) $num ) )
        {
            return 'Zero';
        }
        return '';
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
         'resource_from'=>$request->resource,'resume'=>$resume,'hr_status'=>$request->status,'hr_remark'=>$request->remark,'created_by'=>$request->emp_id,'salary_expectation'=>$request->salary);
          DB::table('candidate_followup_details')->insert($data);
          return response()->json(['status'=>200,'message'=>'Followup Saved Successfully']);
      }
      public function job_position_details($id){
      $data = ManagmentOpening::where('id',$id)->first();
      $department_name = Department::where('id',$data->department_from)->first();
      $get_branch_id = BasicInfo::where('emp_id', $data->created_by)->first();
      $branch = DB::table('branch_details')->where('id',$get_branch_id->branch_id)->first();
      $assign_to = DB::table('assign_job_request_hr')->where('request_id',$id)->pluck('assign_to')->toArray();
      $get_emp_names = BasicInfo::whereIn('emp_id',$assign_to)->pluck('emp_fname')->implode(', ');


      //$date = $data->created_date->format('Y-m-d');
      $details = array('department_name'=> $department_name->department_name,'id'=>$data->id,'opening_type'=>$data->opening_type,
      'replace_to'=>$data->replace_to,'exprience'=>$data->required_experience,'salary'=>$data->salary_range,'skill'=>$data->skill,
      'remark'=>$data->remark,'date'=>$data->created_date,'title'=>$data->title,'branch'=>$branch->branch_name,
      'end_date'=>$data->expected_end_date,'close_date'=>$data->close_date,'assign_member'=>$get_emp_names);
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
        // $validator = Validator::make($input, [
            
        // ]);
    
        //   if($validator->fails()){
        //         $messages=$validator->messages();
        //         return response()->json(["messages"=>$messages,'status'=>400]);     
        //   }
          $data = array('candidate_id'=>$request->candidate_id,'process_status'=>$request->proccess_status,
          'status'=>$request->status,'date'=>$request->date,'time'=>$request->time,
          'final_status'=>$request->final_status,'created_by'=>$request->emp_id,'opening_id'=>$request->job_id,'remark'=>$request->remark);
          if($request->final_status){
            DB::table('candidate_followup_details')->where('id',$request->candidate_id)
            ->where('job_id',$request->job_id)->update(['final_status'=>$request->final_status]);

          }
          if($request->proccess_status){
            DB::table('candidate_followup_details')->where('id',$request->candidate_id)
            ->where('job_id',$request->job_id)->update(['hr_status'=>$request->proccess_status]);

          }
          DB::table('candidate_followup_history')->insert($data);
          return response()->json(['status'=>200,'message'=>'Followup Saved Successfully']);
         }
      public function candidate_followup_history($id){
        $created_by_candidate = DB::table('candidate_followup_details')->where('id',$id)->first();
        $candidate_history = [];
        $data = DB::table('candidate_followup_history')->where('candidate_id',$id)->get();
        foreach($data as $row){
          $emp_name = BasicInfo::where('emp_id',$row->created_by)->first();
          $process_status = FollowupAttribute::where('id',$row->process_status)->first();
          $date = Carbon::parse($row->created_date)->format('Y-m-d');
          $candidate_history[] = array('proccess_status'=>$process_status->name,'status'=>$row->status,
          'date'=>$row->date,'time'=>$row->time,'final_status'=>$row->final_status,
          'created_by'=>$emp_name->emp_fname,'created_date'=>$date,'remark'=>$row->remark);
        }
        return response()->json(['status'=>200,'message'=>'Employee Followup History','data'=>$candidate_history,'created_by_id'=>$created_by_candidate->created_by]);

      }
      public function download_offer_leter(Request $request){
        $date_of_join  = '';
         $address = '';
         $emp_name  = '';
         $salary = '';
        $data = DB::table('candidate_followup_details')->where('id',$request->id)->where('job_id',$request->job_id)->first();
        if($data){
           $date_of_join = $data->date_of_joining;
           $address = $data->address;
           $emp_name = $data->candidate_name;
           $ctc = $data->final_ctc;
           $ctc_in_words =  $this->numberToWord($ctc);
           $fix_salary = $data->fixed_salary;
           $fix_salary_in_words = $this->numberToWord($fix_salary);

           $created_by_details = BasicInfo::where('emp_id',$data->created_by)->first();
           $name =  $created_by_details->emp_fname.' '.$created_by_details->emp_lame;

        }
        $date = Carbon::now()->format('Y-m-d');
        $job = ManagmentOpening::where('id',$request->job_id)->first();
        $job_title = $job->title;
        $manager_details = BasicInfo::where('emp_id',$job->created_by)->first();
        $manager_name = $manager_details->emp_fname.' '. $manager_details->emp_lame;
        $dept_name = Department::where('id', $manager_details->dept_id)->first();
        $dept_name =  $dept_name->department_name;


        $pdf = PDF::loadView('pdf.offer_letter',compact('date_of_join','address','emp_name','salary',
          'date','job_title','manager_name','dept_name','name','ctc','ctc_in_words','fix_salary','fix_salary_in_words'));
        $data = DB::table('candidate_followup_details')->where('id',$request->id)->where('job_id',$request->job_id)->update(['is_offer_latter_genrated'=>1]);

        return $pdf->download('offer_latter.pdf');
        //return view('pdf.offer_letter');
      }

      public function edit_candidate_profile($id){
        $data = DB::table('candidate_followup_details')->where('id',$id)->first();
        return response()->json(['status'=>200,'data'=>$data]);

      }

      public function update_candidate_profile(Request $request ,$id){
         $input = $request->all();
         $validator = Validator::make($input, [
            'name' => 'required',
            'resource'=>'required',
            'remark'=>'required',
        ]);
    
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }

          $data = array('candidate_name'=>$request->name,'mobile'=>$request->mobile,'email'=>$request->email,
         'resource_from'=>$request->resource,'hr_remark'=>$request->remark,'salary_expectation'=>$request->salary,'address'=>$request->address,'final_ctc'=>$request->final_salary,'date_of_joining'=>$request->doj,'fixed_salary'=>$request->fix_salary);
          DB::table('candidate_followup_details')->where('id',$id)->update($data);
           return response()->json(['status'=>200,'message'=>'Updated Successfully']);


      }
      public function close_job_position($id){
        ManagmentOpening::where('id',$id)->update(['close_status'=>1]);
        return response()->json(['status'=>200,'message'=>'Closed Successfully']);
      }

       public function action_on_request($id,$emp_id){

        $data  = array('request_id'=>$id,'assign_to'=>$emp_id,'assign_by'=>$emp_id);
        $check_exists_record =  DB::table('assign_job_request_hr')->where('request_id',$id)->where('assign_to',$emp_id)->exists();
        if($check_exists_record){
           return response()->json(['status'=>400,'message'=>'Already Assigned']);


        }
        DB::table('assign_job_request_hr')->insert($data);
        return response()->json(['status'=>200,'message'=>'Assigned To You Successfully']);
      }

}