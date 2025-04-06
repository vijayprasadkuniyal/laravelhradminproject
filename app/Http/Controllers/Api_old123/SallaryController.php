<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sallary;
use Validator;
use App\Models\SalaryHistory;
use App\Models\Salaryattribute;
use PDF;
use App\Models\BasicInfo;
use Carbon\Carbon;
use App\models\EmployeeLeave;
use App\Models\LoginHours;
use DB;
use App\Models\Leave;
use App\Models\Roster;
use Illuminate\Support\Facades\View;
use App\Models\Attendance;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalaryReport;
use Illuminate\Support\Facades\Storage;
use App\Models\Account;

class SallaryController extends Controller
{
    public function sallary_calculate(Request $request){
        //return $request->all();
        $attributes = $request['attributes'];
        $attributes = json_decode($attributes);
        foreach($attributes as $row){
          if($row->value && $row->value!='' && $row->value!='null' && $row->value>0){
            $salary_attribute = Salaryattribute::where('id',$row->id)->first();
  
            $sallary_calculate = Sallary::updateOrInsert(
              ['emp_id' => $request->emp_id,'attribute_id'=>$row->id],
              [
                  'emp_id'=>$request->emp_id,
                  'attribute_id'=>$row->id,
                  'amount_per_anum'=>$row->value,
                  'amount_per_month'=>round($row->value/12),
                  'status'=>1,
                  'emp_package'=>$request->package_detail,
                  'is_metrocity_emp'=>$request->metrocity,
                  'is_deduction'=>$salary_attribute->is_deduction_attribute,
                  'is_contribute'=>$salary_attribute->is_contribution_attribute,
                  //'deduct_from_salary'=>1,
                  'created_by'=>$request->created_by,
                ]
            );
              DB::table('emp_salary_history')->where('emp_id',$request->emp_id)->where('attribute_id',$row->id)->update(['status'=>0]);
  
            $data[] = array('emp_id'=>$request->emp_id,
              'attribute_id'=>$row->id,'amount_per_anum'=>$row->value,'amount_per_month'=>round($row->value/12),'status'=>1,'emp_package'=>$request->package_detail,'is_metrocity_emp'=>$request->metrocity,'is_deduction'=>$salary_attribute->is_deduction_attribute,
                  'is_contribute'=>$salary_attribute->is_contribution_attribute,'created_by'=>$request->created_by);
  
        }
        }
        if($request->tds){
          $tds_attribute = Salaryattribute::where('id',12)->first();
          $sallary_calculate = Sallary::updateOrInsert(
              ['emp_id' => $request->emp_id,'attribute_id'=>12],
              [
                  'emp_id'=>$request->emp_id,
                  'attribute_id'=>12,
                  'amount_per_anum'=>$request->tds,
                  'amount_per_month'=>round($request->tds/12),
                  'status'=>1,
                  'emp_package'=>$request->package_detail,
                  'is_metrocity_emp'=>$request->metrocity,
                  'is_deduction'=>$tds_attribute->is_deduction_attribute,
                  'is_contribute'=>$tds_attribute->is_contribution_attribute,
                  //'deduct_from_salary'=>0,
                  'created_by'=>$request->created_by,
                ]
            );
            DB::table('emp_salary_history')->where('emp_id',$request->emp_id)->where('attribute_id',12)->update(['status'=>0]);
  
            $data[] = array('emp_id'=>$request->emp_id,
              'attribute_id'=>12,'amount_per_anum'=>$request->tds*12,'amount_per_month'=>round($request->tds/12),'status'=>1,'emp_package'=>$request->package_detail,'is_metrocity_emp'=>$request->metrocity,'is_deduction'=>$tds_attribute->is_deduction_attribute,
                  'is_contribute'=>$tds_attribute->is_contribution_attribute,'created_by'=>$request->created_by);
  
  
        }
  
        DB::table('emp_salary_history')->insert($data);
        return response()->json(['status'=>200,'message'=>'Sallary Created Or Updated Successfully']);
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
     public function edit_sallary($id){
      $sallary = Sallary::where('emp_id',$id)->where('status',1)->get();
      if($sallary){
            return response()->json(['status'=>200,'message'=>'Account Details','data'=>$sallary]);
         }
       else{
          return response()->json(['status'=>500,'message'=>'no  data found','data'=>$sallary]);
       }
     }
     public function salary_attribute(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
           'attribute' => 'required',
          // 'percentage'=>'required',
           'company'=>'required'
        ]);
         if($validator->fails()){
               $messages=$validator->messages();
               return response()->json(["messages"=>$messages,'status'=>400]);     
         }
         $data = new Salaryattribute();
         $data->attribute = $request->attribute;
         $data->percentage = $request->percentage;
         $data->is_deduction_attribute =  $request->is_checked;
         $data->company_id = $request->company;
         $data->amount =  $request->amount;
         $data->start_salary = $request->start_salary;
         $data->end_salary = $request->end_salary; 
         $data->deduction_from = $request->deduction;
         $data->is_contribution_attribute = $request->contributtion;
         $data->parent_attribute = $request->parent;
         $data->is_metrocity_attribute = $request->metrocity;
         $data->description = $request->description;
         $data->created_by = $request->emp_id;
         $data->save();
         if($data){
            return response()->json(['status'=>200,'message'=>'Salary attribute saved successfully']);

         }
         else{
            return response()->json(['status'=>500,'message'=>'Something went wrong']);

         }
      }
      public function attribute_list(){
        $attribute = Salaryattribute::join('company_details','company_details.id','=','salary_attribute.company_id')
                    ->select('company_details.business_name','salary_attribute.id','salary_attribute.attribute','salary_attribute.percentage','salary_attribute.status')
                  ->get();

        return response()->json(['status'=>200,'message'=>'attributes details','data'=>$attribute]);

      }
      public function edit_attribute(Request $request,$id){
         $attribute = Salaryattribute::findOrFail($id);
         return response()->json(['status'=>200,'message'=>'Attributes Details','attribute'=>$attribute]);


      }
      public function update_attribute(Request $request,$id){
       //return $request->all();
        $data = Salaryattribute::findOrFail($id);
        $input = $request->all();
        $validator = Validator::make($input, [
           'attribute' => 'required',
           //'percentage' => 'required',
           'company'=>'required'
        ]);
         if($validator->fails()){
               $messages=$validator->messages();
               return response()->json(["messages"=>$messages,'status'=>400]);     
         }
         $data->attribute = $request->attribute;
         $data->percentage = $request->percentage;
         $data->is_deduction_attribute =  $request->is_checked;
         $data->company_id = $request->company;
         $data->amount =  $request->amount;
         $data->start_salary = $request->start_salary;
         $data->end_salary = $request->end_salary; 
         $data->deduction_from = $request->deduction;
         $data->is_contribution_attribute = $request->contributtion;
         $data->parent_attribute = $request->parent ?? '';
         $data->is_metrocity_attribute = $request->metrocity;
         $data->description = $request->description;
         $data->created_by = $request->emp_id;
         $data->save();
         if($data){
            return response()->json(['status'=>200,'message'=>'Salary attribute updated successfully']);

         }
         else{
            return response()->json(['status'=>500,'message'=>'Something went wrong']);

         }

      }
      public function attribute_status(Request $request){
    //dd('hi');
    $request->validate([
        'attribute_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $attribute = Salaryattribute::findOrFail($request->attribute_id);
    //dd($company);

    // Update the status of the company
    $attribute->status = $request->status;
    $attribute->save();
    if($attribute){
        return response()->json(['status' => 200, 'message' => 'Attribute status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_attribute_status($id){
    $attribute = Salaryattribute::findOrFail($id);
    if($attribute){
        return response()->json(['status' => 200, 'message' => 'attribute status','data'=>$attribute->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }
///

}
public function salary_fields(){
  $data = Salaryattribute::where('status',1)->where('attribute','!=','TDS')->get();
  $tds = Salaryattribute::where('status',1)->where('attribute','=','TDS')->first();
  //return  $tds;
  if(count($data)>0){
    return response()->json(['status' => 200, 'message' => 'attribute List','data'=>$data,'tds'=>$tds]);

  }
  else{
     return response()->json(['status' => 500, 'message' => 'data not found']);

  }


}
public function get_salary($id){
 $salary = Sallary::join('salary_attribute','salary_attribute.id','emp_salary_info.attribute_id')
          ->select('salary_attribute.attribute','emp_salary_info.id','emp_salary_info.emp_id','emp_salary_info.amount_per_anum','amount_per_month','emp_salary_info.emp_package','emp_salary_info.status')
          ->where('emp_id',$id)
          ->get();
 return response()->json(['status'=>200,'message'=>'Salary Details','data'=>$salary]);


}
public function salary_status(Request $request){
    //dd('hi');
    $request->validate([
        'id' => 'required',
        'status' => 'required|in:0,1', 
    ]);
    $salary = Sallary::findOrFail($request->id);
    $salary->status = $request->status;
    $salary->save();
     $contributtion =Salaryattribute::where('parent_attribute',$salary->attribute_id)->first();
     if($contributtion){
       Sallary::where('attribute_id',$contributtion->id)->update(['status'=>$request->status]);
    }


    if($salary){
        return response()->json(['status' => 200, 'message' => 'salary status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_salary_status($id){
    $salary = Sallary::findOrFail($id);
    if($salary){
        return response()->json(['status' => 200, 'message' => 'salary status','data'=>$salary->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
public function submitted_salary_attribute($id){
  $data = Sallary::where('emp_id',$id)->where('status',1)->get();
  return response()->json(['status' =>200, 'message' => 'salary attribute','data'=>$data]);
}
public function calculate_salary_for_pdf($id){
  $data = Sallary::where('emp_id',$id)->where('status',1)->get();
  return response()->json(['status'=>200,'message'=>'salary details','data'=>$data]);



}
 public function generatePdf($emp_id,$month,$year)
{
    //return $request->all();
    $data = BasicInfo::join('branch_details', 'branch_details.id', '=', 'emp_basic_info.branch_id')
        ->leftjoin('department', 'department.id', '=', 'emp_basic_info.dept_id')
        ->leftjoin('designation', 'designation.id', '=', 'emp_basic_info.desi_id')
        ->leftjoin('emp_account_info','emp_account_info.emp_id','=','emp_basic_info.emp_id')
        ->select('emp_basic_info.id', 'branch_details.branch_name', 'department.department_name', 
        'designation.designation_name', 'emp_basic_info.emp_id', 'emp_basic_info.emp_fname', 'emp_basic_info.emp_mname', 
        'emp_basic_info.emp_lame', 'emp_basic_info.emp_doj','emp_account_info.bank_name','emp_account_info.account_no','emp_account_info.uan_no','emp_account_info.esic_no','emp_basic_info.emp_father_name','emp_basic_info.emp_sex','emp_basic_info.emp_dob','emp_account_info.ifsc_code')
        ->where('emp_basic_info.emp_id',$emp_id)
        ->first();
        //return $data;

        $salary_amount_data = DB::table('emp_monthly_salary')
                             ->leftjoin('salary_attribute','salary_attribute.id','=','emp_monthly_salary.attribute_id')
                             ->select('salary_attribute.attribute','emp_monthly_salary.amount','emp_monthly_salary.actual_amount')
                             ->where('is_deduction',0)
                            ->where('emp_id', $emp_id)
                            ->where('month',$month)
                            ->where('year',$year)
                           ->where('is_contribute',0)
                           ->get();

        $salary_amount_sum = DB::table('emp_monthly_salary')
                           ->join('salary_attribute','salary_attribute.id','=','emp_monthly_salary.attribute_id')
                           ->select('salary_attribute.attribute','emp_monthly_salary.amount')
                           ->where('is_deduction',0)
                          ->where('emp_id', $emp_id)
                          ->where('month',$month)
                            ->where('year',$year)
                         ->where('is_contribute',0)
                         ->sum('emp_monthly_salary.amount');

         $orignal_salary = DB::table('emp_monthly_salary')
                           ->join('salary_attribute','salary_attribute.id','=','emp_monthly_salary.attribute_id')
                           ->select('salary_attribute.attribute','emp_monthly_salary.amount')
                           ->where('is_deduction',0)
                          ->where('emp_id', $emp_id)
                          ->where('month',$month)
                          ->where('year',$year)
                         ->where('is_contribute',0)
                         ->sum('emp_monthly_salary.actual_amount');

        $salary_deduction_data = DB::table('emp_monthly_salary')
                 ->join('salary_attribute', 'salary_attribute.id', '=', 'emp_monthly_salary.attribute_id')
                  ->select('salary_attribute.attribute', 'emp_monthly_salary.amount')
                  ->where('emp_monthly_salary.emp_id', $emp_id)
                  ->where('is_deduction',1)
                  ->where('month',$month)
                  ->where('year',$year)
                  ->get();

         $salary_deduction_sum= DB::table('emp_monthly_salary')
                 ->join('salary_attribute', 'salary_attribute.id', '=', 'emp_monthly_salary.attribute_id')
                 ->select('salary_attribute.attribute', 'emp_monthly_salary.amount')
                 ->where('emp_monthly_salary.emp_id', $emp_id)
                 ->where('is_deduction',1)
                 ->where('month',$month)
                 ->where('year',$year)
                 ->sum('emp_monthly_salary.amount');

       $monthNumber = Carbon::parse($month)->month; 
       $numberOfDaysInMonth = Carbon::create($year, $monthNumber, 1)->daysInMonth;

       $total_working_days = DB::table('emp_monthly_salary')->where('emp_id',$emp_id)->where('month',$month)->where('year',$year)->first();

       $working_days = $total_working_days->working_days;

        $no_of_lop = $numberOfDaysInMonth - $total_working_days->working_days;
        
        $actual_salary =  $salary_amount_sum - $salary_deduction_sum;
        $salary_in_words =  $this->numberToWord($actual_salary);
        
        return view('pdf.salary', compact('data','salary_amount_data','salary_deduction_data','numberOfDaysInMonth','salary_deduction_sum',
        'salary_amount_sum','no_of_lop','month','salary_in_words','orignal_salary','working_days'));
        //return response()->json(['ok'=>'ok']);
       }

// public function salary_calc(Request $request)
// {
//     $package = $request->package;
//     $checkbox = $request->selectedCheckboxes;
//     $checkmetro = $request->metrocity;

//     $salary_attributes = Salaryattribute::where('is_deduction_attribute', 0)
//         ->where('is_contributtion_attribute',0)
//         ->where('status', 1)
//         ->where('attribute','!=','HRA')
//         ->get();

//     $salary_data = [];

//     // Precalculate basic salary and HRA
//     $basic_salary = Salaryattribute::where('id', 1)->first();
//     $basic_salary_per_anum = $package * $basic_salary->percentage / 100;
//     $basic_salary_per_month = $basic_salary_per_anum / 12;
//     if($checkmetro){
//        $hra = Salaryattribute::where('id', 2)->first();
//     }
//     else{
//       $hra = Salaryattribute::where('id', 3)->first();
//     }
//     $hra_per_anum = $basic_salary_per_anum * $hra->percentage / 100;
//     $hra_per_month = $hra_per_anum /12;

//     // $special_allowance = $package - ($basic_salary_per_anum +  $hra_per_anum);
//     // $special_allowance_per_month =   $special_allowance/12;
//     // $gross_salary =  $special_allowance_per_month +  $hra_per_month +  $basic_salary_per_month;
//     // $gross_salary_per_anum =  $special_allowance + $hra_per_anum +  $basic_salary_per_anum;


//      foreach ($salary_attributes as $row) {
//         $per_anum = '';
//         $per_month = '';

//           if ($row->deduction_from == 'basic') {
//             if (!empty($row->percentage) && empty($row->amount)) {
//                 $per_anum = round($basic_salary_per_anum * $row->percentage / 100);
//                 $per_month = round($per_anum / 12);
//             } elseif (empty($row->percentage) && !empty($row->amount)) {
//                 $per_anum = round(12 * $row->amount);
//                 $per_month = round($per_anum / 12);
//             }
//           }

//         if ($row->deduction_from == 'Package') {
//             if (!empty($row->percentage) && empty($row->amount)) {
//                 $per_anum = round($package * $row->percentage / 100);
//                 $per_month = round($per_anum / 12);
//             } elseif (empty($row->percentage) && !empty($row->amount)) {
//                 $per_anum = round(12 * $row->amount);
//                 $per_month = round($per_anum / 12);
//         }
//       }

//         if($row->deduction_from == 'ctc-(basic+hra)') {
//           $per_anum = round($special_allowance);
//           $per_month = round($special_allowance_per_month);
//          }
        
//           $salary_data[] = array(
//             'attribute_id' => $row->id,
//             'amount' => $per_anum,
//             'per_month' => $per_month,
//             'is_deduction' => $row->is_checked,
//             'is_contribute' => $row->for_contributtion
//         );
//     }

//      $hra_array = array(
//             'attribute_id' => $hra->id,
//             'amount' => round($hra_per_anum),
//             'per_month' => round($hra_per_month),
//             'is_deduction' => $hra->is_checked,
//             'is_contribute' => $hra->for_contributtion,
//       );

//      $salary_data[] = $hra_array;
//      if ($checkbox) {
//         foreach ($checkbox as $row) {
//             $checked_id = Salaryattribute::where('id', $row)->first();

//             if ($checked_id) {
//                 $per_anum = '';
//                 $per_month = '';

//                 if ($checked_id->deduction_from == 'basic') {
//                     if (!empty($checked_id->percentage) && empty($checked_id->amount) && empty($checked_id->start_salary) && empty($checked_id->end_salary)) {
//                         $per_anum = round($basic_salary_per_anum * $checked_id->percentage / 100);
//                         $per_month = round($per_anum / 12);
//                     } elseif (empty($checked_id->percentage) && !empty($checked_id->amount) && empty($checked_id->start_salary) && empty($checked_id->end_salary)) {
//                         $per_anum = round(12 * $checked_id->amount);
//                         $per_month = round($per_anum / 12);
//                     } elseif (!empty($checked_id->percentage) && empty($checked_id->amount) && !empty($checked_id->start_salary) && !empty($checked_id->end_salary)) {
//                       // return 'kk';
//                         if ($basic_salary_per_month >= $checked_id->start_salary && $basic_salary_per_month <= $checked_id->end_salary) {
//                             $per_anum = round($basic_salary_per_anum * $checked_id->percentage / 100);
//                             $per_month = round($per_anum / 12);
//                         }
//                     } elseif (empty($checked_id->percentage) && !empty($checked_id->amount) && !empty($checked_id->start_salary) && !empty($checked_id->end_salary)) {
//                         // return 'kk';
//                         if ($basic_salary_per_month >= $checked_id->start_salary && $basic_salary_per_month <= $checked_id->end_salary) {
//                             $per_anum = round(12 * $checked_id->amount);
//                             $per_month = round($per_anum / 12);
//                         }
//                     }
//                 }

//                 if ($checked_id->deduction_from == 'gross') {
//                   // return 'kk';
//                     if (!empty($checked_id->percentage) && empty($checked_id->amount) && empty($checked_id->start_salary) && empty($checked_id->end_salary)) {
//                         $per_anum = round($package * $checked_id->percentage / 100);
//                         $per_month = round($per_anum / 12);
//                     } elseif (empty($checked_id->percentage) && !empty($checked_id->amount) && empty($checked_id->start_salary) && empty($checked_id->end_salary)) {
//                         $per_anum = round(12 * $checked_id->amount);
//                         $per_month = round($per_anum / 12);
//                     } elseif (!empty($checked_id->percentage) && empty($checked_id->amount) && !empty($checked_id->start_salary) && !empty($checked_id->end_salary)) {
//                        // return 'kk';
//                         if ($gross_salary >= $checked_id->start_salary && $gross_salary <= $checked_id->end_salary) {
//                             $per_anum = round($gross_salary_per_anum * $checked_id->percentage / 100);
//                             $per_month = round($per_anum / 12);
//                         }
//                     } elseif (empty($checked_id->percentage) && !empty($checked_id->amount) && !empty($checked_id->start_salary) && !empty($checked_id->end_salary)) {
//                         if ($gross_salary >= $checked_id->start_salary && $gross_salary <= $checked_id->end_salary) {
//                             $per_anum = round(12 * $checked_id->amount);
//                             $per_month = round($per_anum / 12);
//                         }
//                     }
//                 }

//                 $salary_data[] = array(
//                     'attribute_id' => $checked_id->id,
//                     'amount' => $per_anum,
//                     'per_month' => $per_month,
//                     'is_deduction' => $checked_id->is_checked,
//                     'is_contribute' => $checked_id->for_contributtion
//                 );

//                 // Handle contribution
//                 $contribute = Salaryattribute::where('parent_attribute', $checked_id->id)->first();
//                 if ($contribute) {
//                     $per_anum = '';
//                     $per_month = '';

//                     if ($contribute->deduction_from == 'basic') {
//                         if (!empty($contribute->percentage) && empty($contribute->amount) && empty($contribute->start_salary) && empty($contribute->end_salary)) {
//                             $per_anum = round($basic_salary_per_anum * $contribute->percentage / 100);
//                             $per_month = round($per_anum / 12);
//                         } elseif (empty($contribute->percentage) && !empty($contribute->amount) && empty($contribute->start_salary) && empty($contribute->end_salary)) {
//                             $per_anum = round(12 * $contribute->amount);
//                             $per_month = round($per_anum / 12);
//                         } elseif (!empty($contribute->percentage) && empty($contribute->amount) && !empty($contribute->start_salary) && !empty($contribute->end_salary)) {
//                             if ($basic_salary_per_month >= $contribute->start_salary && $basic_salary_per_month <= $contribute->end_salary) {
//                                 $per_anum = round($basic_salary_per_anum * $contribute->percentage / 100);
//                                 $per_month = round($per_anum / 12);
//                             }
//                         } elseif (empty($contribute->percentage) && !empty($contribute->amount) && !empty($contribute->start_salary) && !empty($contribute->end_salary)) {
//                             if ($basic_salary_per_month >= $contribute->start_salary && $basic_salary_per_month <= $contribute->end_salary) {
//                                 $per_anum = round(12 * $contribute->amount);
//                                 $per_month = round($per_anum / 12);
//                             }
//                         }
//                     }

//                     if ($contribute->deduction_from == 'gross') {
//                         if (!empty($contribute->percentage) && empty($contribute->amount) && empty($contribute->start_salary) && empty($contribute->end_salary)) {
//                             $per_anum = round($package * $contribute->percentage / 100);
//                             $per_month = round($per_anum / 12);
//                         } elseif (empty($contribute->percentage) && !empty($contribute->amount) && empty($contribute->start_salary) && empty($contribute->end_salary)) {
//                             $per_anum = round(12 * $contribute->amount);
//                             $per_month = round($per_anum / 12);
//                         } elseif (!empty($contribute->percentage) && empty($contribute->amount) && !empty($contribute->start_salary) && !empty($contribute->end_salary)) {
//                             if ($gross_salary >= $contribute->start_salary && $gross_salary <= $contribute->end_salary) {
//                                 $per_anum = round($gross_salary_per_anum * $contribute->percentage / 100);
//                                 $per_month = round($per_anum / 12);
//                             }
//                         } elseif (empty($contribute->percentage) && !empty($contribute->amount) && !empty($contribute->start_salary) && !empty($contribute->end_salary)) {
//                             if ($gross >= $contribute->start_salary && $gross_salary <= $contribute->end_salary) {
//                                 $per_anum = round(12 * $contribute->amount);
//                                 $per_month = round($per_anum / 12);
//                             }
//                         }
//                     }

//                     $salary_data[] = array(
//                         'attribute_id' => $contribute->id,
//                         'amount' => $per_anum,
//                         'per_month' => $per_month,
//                         'is_deduction' => $contribute->is_checked,
//                         'is_contribute' => $contribute->for_contributtion
//                     );
//                 }
//             }
//         }
//     }

//     return response()->json(['status' => 200, 'data' => $salary_data]);
// }


public function salary_calc(Request $request)
{
    $package = $request->package;
    $checkbox = $request->selectedCheckboxes;
    $checkmetro = $request->metrocity;

    $basic_salary = Salaryattribute::where('id', 1)->first();
    if ($basic_salary && $basic_salary->percentage != '') {
        $basic_salary_per_anum = round($package * $basic_salary->percentage / 100);
        $basic_salary_per_month = round($basic_salary_per_anum / 12);
    }

    $hra = ($checkmetro == 1) ? Salaryattribute::where('id', 2)->first() : Salaryattribute::where('id', 3)->first();
    $hra_per_anum = round($basic_salary_per_anum * $hra->percentage / 100);
    $hra_per_month = round($hra_per_anum / 12);

    $special_allowance = $package - ($basic_salary_per_anum + $hra_per_anum);
    $special_allowance_per_month = round($special_allowance / 12);

    $gross_salary_per_month = $special_allowance_per_month + $hra_per_month + $basic_salary_per_month;

    $gross_salary_per_anum = $special_allowance + $hra_per_anum + $basic_salary_per_anum;

    $salary_attributes = Salaryattribute::where('is_deduction_attribute', 0)
        ->where('is_contribution_attribute', 0)
        ->where('status', 1)
        ->where('attribute', '!=', 'HRA')
        ->get();

    $salary_data = [];
    $added_attribute_ids = []; 

    function calculateDeduction($row, $base_salary_per_anum, $base_salary_per_month, $gross_salary_per_anum, $gross_salary_per_month)
    {
        $per_anum = 0;
        $per_month = 0;

        if ($row->start_salary && $row->end_salary) {
            $start_salary = $row->start_salary;
            $end_salary = $row->end_salary;
            if ($gross_salary_per_month >= $start_salary && $gross_salary_per_month <= $end_salary) {
                if ($row->percentage != '') {
                    $per_anum = round($base_salary_per_anum * $row->percentage / 100);
                } elseif ($row->amount != '') {
                    $per_anum = round($row->amount * 12);
                }
            }
        } elseif (!$row->start_salary && !$row->end_salary) {
            if ($row->percentage != '') {
                $per_anum = round($base_salary_per_anum * $row->percentage / 100);
            } elseif ($row->amount != '') {
                $per_anum = round($row->amount * 12);
            }
        }

        $per_month = round($per_anum / 12);
        return [$per_anum, $per_month];
    }

    foreach ($salary_attributes as $row) {
        if (in_array($row->id, $added_attribute_ids)) {
            continue; 
        }

        if ($row->deduction_from == 'Package') {
            list($per_anum, $per_month) = calculateDeduction($row, $package, null, $gross_salary_per_anum, $gross_salary_per_month);
        } elseif ($row->deduction_from == 'basic') {
            list($per_anum, $per_month) = calculateDeduction($row, $basic_salary_per_anum, $basic_salary_per_month, $gross_salary_per_anum, $gross_salary_per_month);
        } elseif ($row->deduction_from == 'ctc-(basic+hra)') {
            $per_anum = round($special_allowance);
            $per_month = round($special_allowance_per_month);
        } else {
            $per_anum = $per_month = 0;
        }

        $salary_data[] = [
            'attribute_id' => $row->id,
            'amount' => $per_anum,
            'per_month' => $per_month,
            'is_deduction' => $row->is_deduction_attribute,
            'is_contribute' => $row->is_contribution_attribute
        ];

        $added_attribute_ids[] = $row->id;
    }

    if (!in_array($hra->id, $added_attribute_ids)) {
        $hra_array = [
            'attribute_id' => $hra->id,
            'amount' => round($hra_per_anum),
            'per_month' => round($hra_per_month),
            'is_deduction' => $hra->is_deduction_attribute,
            'is_contribute' => $hra->is_contribution_attribute
        ];
        $salary_data[] = $hra_array;
        $added_attribute_ids[] = $hra->id;
    }

    if ($checkbox) {
        foreach ($checkbox as $result) {
            $row = Salaryattribute::where('id', $result)->first();
            if ($row && !in_array($row->id, $added_attribute_ids)) {
                
                if ($row->deduction_from == 'basic') {
                    
                    list($per_anum, $per_month) = calculateDeduction($row, $basic_salary_per_anum, $basic_salary_per_month, $gross_salary_per_anum, $gross_salary_per_month);
                } elseif ($row->deduction_from == 'gross') {

                    
                    list($per_anum, $per_month) = calculateDeduction($row, $gross_salary_per_anum, $gross_salary_per_month, $gross_salary_per_anum, $gross_salary_per_month);
                } else {
                    list($per_anum, $per_month) = calculateDeduction($row, $basic_salary_per_anum, $basic_salary_per_month, $gross_salary_per_anum, $gross_salary_per_month);
                }

                $salary_data[] = [
                    'attribute_id' => $row->id,
                    'amount' => $per_anum,
                    'per_month' => $per_month,
                    'is_deduction' => $row->is_deduction_attribute,
                    'is_contribute' => $row->is_contribution_attribute
                ];

                $added_attribute_ids[] = $row->id;

                $contribute = Salaryattribute::where('parent_attribute', $row->id)->first();
                if ($contribute && !in_array($contribute->id, $added_attribute_ids)) {
                    if ($contribute->deduction_from == 'basic') {
                        list($per_anum, $per_month) = calculateDeduction($contribute, $basic_salary_per_anum, $basic_salary_per_month, $gross_salary_per_anum, $gross_salary_per_month);
                    } elseif ($contribute->deduction_from == 'gross') {
                        list($per_anum, $per_month) = calculateDeduction($contribute, $gross_salary_per_anum, $gross_salary_per_month, $gross_salary_per_anum, $gross_salary_per_month);
                    } else {
                        list($per_anum, $per_month) = calculateDeduction($contribute, $basic_salary_per_anum, $basic_salary_per_month, $gross_salary_per_anum, $gross_salary_per_month);
                    }
                    $salary_data[] = [
                        'attribute_id' => $contribute->id,
                        'amount' => $per_anum,
                        'per_month' => $per_month,
                        'is_deduction' => $contribute->is_deduction_attribute,
                        'is_contribute' => $contribute->is_contribution_attribute
                    ];

                    $added_attribute_ids[] = $contribute->id;
                }
            }
        }
    }

    return response()->json(['status' => 200, 'data' => $salary_data]);
}

public function deduction_attribute(){
    $deduction = Salaryattribute::where('is_deduction_attribute', 1)-where('status',1)->where('is_contribution_attribute', 0)->get();
    $attribute = [];

    foreach ($deduction as $row) {
        $name = $row->attribute;

        if ($row->percentage!=" ") {
            $name .= ' ' . $row->percentage . ' %';
        }

        if ($row->amount !== null) {
            $name .= ' ' . $row->amount . ' RS';
        }

        $attribute[] = array('id' => $row->id, 'name' => $name);
    }

    return response()->json(['status' => 200, 'data' => $attribute]);
}

/*public function save_salary()
{
     $data = BasicInfo::where('emp_status', 1)->get();
    $db = [];

    foreach ($data as $row) {
        $emp_leave = EmployeeLeave::where('emp_id', $row->emp_id)
            ->where('status', 2)
            ->get();

        $emp_lop = 0;
        $month_days = [];
        $result = [];
        $approved_leave = [];
        $salary_amount = [];

        if (count($emp_leave) > 0) {

            foreach ($emp_leave as $leave) {
                $currentMonth = now()->format('m');
                $currentYear = now()->format('Y');
                $startDate = Carbon::parse($leave->date_from);
                $endDate = Carbon::parse($leave->date_to);

                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                    if ($date->format('m') == $currentMonth && $date->format('Y') == $currentYear) {
                        $result[] = $date->toDateString();
                    }
                }
            }
            //$emp_lop = count($result);

            $login_data = LoginHours::where('emp_id', $row->emp_id)
                ->whereIn(DB::raw('DATE(created_date)'), $result)
                ->get();
            $days = LoginHours::where('emp_id', $row->emp_id)->pluck('created_date')->toArray();
            //return $days;




            $missing_dates = array_diff($result, $days);
             //return $missing_dates;
            $emp_lop += count($missing_dates);


            if (count($login_data) > 0) {
                foreach ($login_data as $login) {
                    if ($login->logout_time == null) {
                        $emp_lop++;
                    } else {
                        $start = Carbon::parse($login->login_time);
                        $end = Carbon::parse($login->logout_time);

                        $diff_in_hour = $start->diff($end)->format('%H');

                        if ($diff_in_hour >= 6 && $diff_in_hour < 9) {
                            $emp_lop += 0.5;
                        }

                        if ($diff_in_hour < 6) {
                            $emp_lop++;
                        }
                    }
                }
            } else {
                // If there are no login records, assume all days in the leave period are LWP
                $emp_lop = count($result);
            }

        }
        $start_of_month  =  Carbon::now()->startOfMonth()->toDateString();
        $startDate = Carbon::parse($start_of_month);
        $end_of_month = Carbon::now()->endOfMonth()->toDateString();
        $endDate = Carbon::parse($end_of_month);
        $festival_dates = Leave::where('status',1)->pluck('date')->toArray();
       for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            $month_days[] = $date->toDateString();
        }
      $emp_approved_leave = EmployeeLeave::where('emp_id',$row->emp_id)->where('status',1)->get();
      if(count($emp_approved_leave)>0){
        foreach($emp_approved_leave as $approved){
                $currentMonth = now()->format('m');
                $currentYear = now()->format('Y');
                $startDate = Carbon::parse($approved->date_from);
                $endDate = Carbon::parse($approved->date_to);

                for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                    if ($date->format('m') == $currentMonth && $date->format('Y') == $currentYear) {
                        $approved_leave[] = $date->toDateString();
                    }
                }
               }
               $month_days = array_diff($month_days,$approved_leave);

              }


       if(count($result)>0){
         $remaing_days = array_diff($month_days, $result);
         $remaing_days = array_diff($remaing_days,$festival_dates);
         ///return  $remaing_days;
       }
       else{
        $remaing_days = array_diff($month_days,$festival_dates);
        //return  $remaing_days;
       }
      $roster = Roster::where('emp_id',$row->emp_id)->where('status',1)->whereRaw('YEAR(week_off) = ?', [Carbon::now()->year])->whereRaw('Month(week_off) = ?', [Carbon::now()->month])->pluck('week_off')->toArray();
      if(count($roster)){
       $remaing_days =  array_diff($remaing_days,$roster); 
      }
      else{
        // return 'kkk';
        $remaing_days = array_filter($remaing_days, function ($day) {
           $dayOfWeek = Carbon::parse($day)->dayOfWeek;
            return !in_array($dayOfWeek, [Carbon::SUNDAY, Carbon::SATURDAY]);
          });
 
       }
       $remaining_login_data = LoginHours::where('emp_id', $row->emp_id)
                ->whereIn('created_date',$remaing_days)
                ->get();
          //return $remaing_days;
      if(count($remaining_login_data)>0){
        foreach ($remaining_login_data as $login_data) {
                    if ($login_data->logout_time == null) {
                        $emp_lop++;
                    } else {
                        $start = Carbon::parse($login_data->login_time);
                        $end = Carbon::parse($login_data->logout_time);

                        $diff_in_hour = $start->diff($end)->format('%H');

                        if ($diff_in_hour >= 6 && $diff_in_hour < 9) {
                            $emp_lop += 0.5;
                        }

                        if ($diff_in_hour < 6) {
                            $emp_lop++;
                        }


                    }
                }
                 $not_login_dates = LoginHours::where('emp_id', $row->emp_id)->pluck('created_date')->toArray();
                       $not_login_dates = array_diff($remaing_days,$not_login_dates);
                      // return $not_login_dates;
                       $emp_lop += count($not_login_dates);
                       //return $emp_lop; 



       }
       else{
        //return 'kk';
        $emp_lop = count($remaing_days);
       }
         $now = Carbon::now();
         $year =  $now->year;
         $month =  $now->format('F');
         $year_and_month = $month.' '.$year;
        $db[] = ['emp_id' => $row->emp_id, 'no_of_lop' => $emp_lop,'month_year'=>$year_and_month];
        $total_salary = Sallary::where('emp_id',$row->emp_id)->where('status',1)->where('is_deduction',0)->where('is_contribute',0)->sum('amount_per_month');
        //return  $total_salary;
        $currentMonthDays = Carbon::now()->daysInMonth;
        $one_day_salary = round($total_salary/$currentMonthDays);
        $no_of_lop = $emp_lop;
        $no_of_workin_days = $currentMonthDays-$no_of_lop;
        $gross_salary = round($no_of_workin_days*$one_day_salary);
       //c f return $gross_salary;
        $emp_salary_data = Sallary::where('emp_id',$row->emp_id)->where('status',1)->get();
        //return  $emp_salary_data; 
        if(count($emp_salary_data)>0){
            foreach($emp_salary_data  as $emp_salary){
              //return 'hh';
               $basic_salary =  Salaryattribute::where('id', 1)->first();
               $basic_salary = $gross_salary*$basic_salary->percentage/100;
               //return $basic_salary;
               $hra =  Salaryattribute::where('id', 2)->first();
               $hra = $basic_salary*$hra->percentage/100;
               $salary_attribute = Salaryattribute::where('id',$emp_salary->attribute_id)->first();

               if($salary_attribute->attribute=='TDS' ){
                 $amount = Sallary::where('attribute_id',$salary_attribute->id)->where('emp_id',$row->emp_id)->first();
                 //return $amount; 
                  // return 'jjj'; 
                   $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount->amount_per_anum,'is_deduction'=>$salary_attribute->is_checked,
                   'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month);

               }
              // return $salary_attribute;
               if($salary_attribute->deduction_from == 'Package' && $salary_attribute->attribute!=='TDS' ){
                //return 'mmm';
                if($salary_attribute->percentage ===null && $salary_attribute->amount!==null){
                   $amount = $salary_attribute->amount;
                  // return 'jjj'; 
                   $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount,'is_deduction'=>$salary_attribute->is_checked,
                   'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month);
                   //return  $salary_amount; 


                }
                elseif($salary_attribute->amount ===null && $salary_attribute->percentage!==null ){
                  //return 'mmm';
                    $amount = round($salary_attribute->percentage*$gross_salary/100);
                    $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount,'is_deduction'=>$salary_attribute->is_checked,
                    'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month); 
                    //return  $salary_amount;             
                }
                elseif($salary_attribute->amount ===null && $salary_attribute->percentage===null){
                    $amount = round($gross_salary - ($basic_salary + $hra));
                    $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount,'is_deduction'=>$salary_attribute->is_checked,
                    'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month); 
                }

            }
            if($salary_attribute->deduction_from == 'basic' && $salary_attribute->attribute!=='TDS'){
                if($salary_attribute->percentage ===null && $salary_attribute->amount!==null){
                    //return 'mmm';
                   $amount = $salary_attribute->amount;
                   $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount,'is_deduction'=>$salary_attribute->is_checked,
                   'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month);
                }
                elseif($salary_attribute->amount ===null && $salary_attribute->percentage!==null){
                    //return 'mmm';
                    $amount = round($salary_attribute->percentage*$basic_salary/100);
                    $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount,'is_deduction'=>$salary_attribute->is_checked,
                    'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month);            
                }
                elseif($salary_attribute->amount ===null && $salary_attribute->percentage===null){
                    $amount = round($gross_salary - ($basic_salary + $hra));
                    $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount,'is_deduction'=>$salary_attribute->is_checked,
                    'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month);
                }

            }

        }

      }
      DB::table('emp_monthly_salary')->insert($salary_amount);
    }

    // Insert all records outside the loop
    //if (!empty($db)) {
        DB::table('emp_lop')->insert($db);
    //}
}*/
public function show_salary_list($id)
{
    $gross_salary = DB::table('emp_monthly_salary')
        ->select(
            'month',
            'year',
            DB::raw('SUM(CASE WHEN is_deduction = 0 AND is_contribute = 0 THEN actual_amount ELSE 0 END) as actual_salary'),
            DB::raw('SUM(CASE WHEN is_deduction = 0 AND is_contribute = 0 THEN amount ELSE 0 END) as total_salary'),
            DB::raw('SUM(CASE WHEN is_deduction = 1  THEN amount ELSE 0 END) as total_deduction'),
            DB::raw('
                CASE 
                    WHEN SUM(CASE WHEN is_deduction = 0 AND is_contribute = 0 THEN amount ELSE 0 END) 
                    - SUM(CASE WHEN is_deduction = 1  THEN amount ELSE 0 END) > 0 
                    THEN SUM(CASE WHEN is_deduction = 0 AND is_contribute = 0 THEN amount ELSE 0 END) 
                         - SUM(CASE WHEN is_deduction = 1  THEN amount ELSE 0 END)
                    ELSE 0
                END as net_salary'
            )
        )
        ->where('emp_id', $id)
        ->groupBy('month', 'year')
        ->get();

    return response()->json(['status' => 200, 'data' => $gross_salary]);
}

// public function save_salary() {
//     $emp_list = BasicInfo::where('emp_status', 1)->get();

//     foreach ($emp_list as $row) {
//         $lop = 0; // Reset $lop for each employee
//         $weekleave = 0;
//         $attendance_data = attendance::where('emp_id', $row->emp_id)->whereRaw('Month(attendance_date) = ?', [Carbon::now()->month]);
//         $attendance = attendance::where('emp_id', $row->emp_id)->whereRaw('Month(attendance_date) = ?', [Carbon::now()->month])->get();

//         $unpaid_leave = $attendance_data->where('leave_status', 1)->where('leave_type', 7)->get();
//         $leave = Leave::whereRaw('Month(date) = ?', [Carbon::now()->month])->where('status', 1)
//             ->where(function ($query) use ($row) {
//                 $query->where('leave_type', 'international')
//                     ->orWhere('leave_type', 'national')
//                     ->orWhere('branch_id', $row->branch_id);
//             })
//             ->get();
//         //return $leave;
//         $roster = Roster::where('emp_id', $row->emp_id)->where('status', 1)->get();

//         $no_of_dates = $attendance->pluck('attendance_date')->toArray();
//         $no_of_leave = $leave->pluck('date')->toArray();
//         $roster_date = $roster->pluck('week_off')->toArray();
//         $unpaid_dates = $unpaid_leave->pluck('attendance_date')->toArray();
//         $remaining_dates = array_diff($no_of_dates, $no_of_leave, $unpaid_dates);
//         $sunday_and_saturday =  $attendance->whereIn('weekday',['Sunday','Saturday']);
//         //return $sunday_and_saturday;
//       if (count($roster) == 0) {
//         foreach($sunday_and_saturday as $weekend){
//             //$weekleave = 0;
//           if($weekend->weekday == 'Saturday'){
//             //return 'mmm';
//             $saturday_date =$weekend->attendance_date;
//             $saturday_date = Carbon::parse($saturday_date);
//             $previousFridayDate = $saturday_date->copy()->previous(Carbon::FRIDAY)->toDateString();
//             $nextmondayDate = $saturday_date->copy()->next(Carbon::MONDAY)->toDateString();
//             $check_fridaay_leave  = Leave::where('date',$previousFridayDate)->where('status', 1)
//             ->where(function ($query) use ($row) {
//                 $query->where('leave_type', 'international')
//                     ->orWhere('leave_type', 'national')
//                     ->orWhere('branch_id', $row->branch_id);
//             })
//             ->get();

//             $check_monday_leave  = Leave::where('date',$nextmondayDate)->where('status', 1)
//             ->where(function ($query) use ($row) {
//                 $query->where('leave_type', 'international')
//                     ->orWhere('leave_type', 'national')
//                     ->orWhere('branch_id', $row->branch_id);
//             })
//             ->get();
//            //return  $check_fridaay_leave;

//             if(count($check_fridaay_leave)==0 && count($check_monday_leave)==0){
//               $friday_attendance = Attendance::where('attendance_date',$previousFridayDate)->first();
//               //return  $friday_attendance;
//               if($friday_attendance && ($friday_attendance->leave_status==1 || $friday_attendance->status==0)){
//                  //return 'jj';
//                 $monday_attendance = Attendance::where('attendance_date',$nextmondayDate)->first();
//                 //return $monday_attendance;
//                  if($monday_attendance && ($monday_attendance->leave_status==1 || $monday_attendance->status==0)){
//                   //return 'kkk';
//                     $weekleave =  $weekleave+1;
//                     }
//                  }

//                  }
//                 }
//                // return $weekleave;
//           if($weekend->weekday == 'Sunday'){
//             //return 'mmm';
//             $sunday_date =$weekend->attendance_date;
//             //return $sunday_date;
//             $sunday_date = Carbon::parse($sunday_date);
//             $previousFridayDate = $sunday_date->copy()->previous(Carbon::FRIDAY)->toDateString();
//             $nextmondayDate = $sunday_date->copy()->next(Carbon::MONDAY)->toDateString();
//             $check_fridaay_leave  = Leave::where('date',$previousFridayDate)->where('status', 1)
//             ->where(function ($query) use ($row) {
//                 $query->where('leave_type', 'international')
//                     ->orWhere('leave_type', 'national')
//                     ->orWhere('branch_id', $row->branch_id);
//             })
//             ->get();

//             $check_monday_leave  = Leave::where('date',$nextmondayDate)->where('status', 1)
//             ->where(function ($query) use ($row) {
//                 $query->where('leave_type', 'international')
//                     ->orWhere('leave_type', 'national')
//                     ->orWhere('branch_id', $row->branch_id);
//             })
//             ->get();

//             if(count($check_fridaay_leave)==0 && count($check_monday_leave)==0 ){
//               $friday_attendance = Attendance::where('attendance_date',$previousFridayDate)->first();
//               //return  $friday_attendance;
//               if($friday_attendance && ($friday_attendance->leave_status==1 || $friday_attendance->status==0)){
//                  //return 'jj';
//                 $monday_attendance = Attendance::where('attendance_date',$nextmondayDate)->first();
//                  if($monday_attendance && ($monday_attendance->leave_status==1 || $monday_attendance->status==0)){
//                     $weekleave =  $weekleave+1;
//                    // return $weekleave;
//                     }
//                  }

//                  }
//                 }

//                }
//              }
//                //return $weekleave;


//         if (count($roster) > 0) {
//             $remaining_dates = array_diff($remaining_dates, $roster_date);
//         }

//         if (count($roster) == 0) {
//             $remaining_dates = array_filter($remaining_dates, function ($day) {
//                 $dayOfWeek = Carbon::parse($day)->dayOfWeek;
//                 return !in_array($dayOfWeek, [Carbon::SUNDAY, Carbon::SATURDAY]);
//             });
//         }
//        // return  $remaining_dates;

//         $check_remaining_dates = $attendance->where('leave_status', !1)->whereIn('attendance_date', $remaining_dates);

//         foreach ($check_remaining_dates as $working) {
//             if ($working->status == 0) {
//                 $lop += 1;
//             } elseif ($working->status == 2) {
//                 $lop += 0.35; // Adjusted to add 0.35 without rounding
//             } elseif ($working->status == 3) {
//                 $lop += 0.5;
//             }
//             //return $lop;
//         }
//         $now = Carbon::now();
//         $year = $now->year;
//         $month = $now->format('F');
//         $year_and_month = $month . ' ' . $year;
//         //return $lop;
//          $total_salary = Sallary::where('emp_id',$row->emp_id)->where('status',1)->where('is_deduction',0)->where('is_contribute',0)->sum('amount_per_month');

//         $currentMonthDays = Carbon::now()->daysInMonth;
//         $one_day_salary = round($total_salary/$currentMonthDays);
//         $no_of_lop = round($lop)+$weekleave + count($unpaid_leave);
//         //return $no_of_lop;
//         $no_of_workin_days = $currentMonthDays-$no_of_lop;
//         $gross_salary = round($no_of_workin_days*$one_day_salary);
//        //c f return $gross_salary;
//         $emp_salary_data = Sallary::where('emp_id',$row->emp_id)->where('status',1)->get();
//         ////

//         if(count($emp_salary_data)>0){
//             foreach($emp_salary_data  as $emp_salary){
//               //return 'hh';
//                $basic_salary =  Salaryattribute::where('id', 1)->first();
//                $basic_salary = $gross_salary*$basic_salary->percentage/100;
//                //return $basic_salary;
//                $hra =  Salaryattribute::where('id', 2)->first();
//                $hra = $basic_salary*$hra->percentage/100;
//                $salary_attribute = Salaryattribute::where('id',$emp_salary->attribute_id)->first();

//                if($salary_attribute->attribute=='TDS' ){
//                  $amount = Sallary::where('attribute_id',$salary_attribute->id)->where('emp_id',$row->emp_id)->first();
//                  //return $amount; 
//                   // return 'jjj'; 
//                    $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount->amount_per_anum,'is_deduction'=>$salary_attribute->is_checked,
//                    'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month);

//                }
//               // return $salary_attribute;
//                if($salary_attribute->deduction_from == 'Package' && $salary_attribute->attribute!=='TDS' ){
//                 //return 'mmm';
//                 if($salary_attribute->percentage ===null && $salary_attribute->amount!==null){
//                    $amount = $salary_attribute->amount;
//                   // return 'jjj'; 
//                    $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount,'is_deduction'=>$salary_attribute->is_checked,
//                    'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month);
//                    //return  $salary_amount; 


//                 }
//                 elseif($salary_attribute->amount ===null && $salary_attribute->percentage!==null ){
//                   //return 'mmm';
//                     $amount = round($salary_attribute->percentage*$gross_salary/100);
//                     $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount,'is_deduction'=>$salary_attribute->is_checked,
//                     'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month); 
//                     //return  $salary_amount;             
//                 }
//                 elseif($salary_attribute->amount ===null && $salary_attribute->percentage===null){
//                     $amount = round($gross_salary - ($basic_salary + $hra));
//                     $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount,'is_deduction'=>$salary_attribute->is_checked,
//                     'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month); 
//                 }

//             }
//             if($salary_attribute->deduction_from == 'basic' && $salary_attribute->attribute!=='TDS'){
//                 if($salary_attribute->percentage ===null && $salary_attribute->amount!==null){
//                     //return 'mmm';
//                    $amount = $salary_attribute->amount;
//                    $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount,'is_deduction'=>$salary_attribute->is_checked,
//                    'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month);
//                 }
//                 elseif($salary_attribute->amount ===null && $salary_attribute->percentage!==null){
//                     //return 'mmm';
//                     $amount = round($salary_attribute->percentage*$basic_salary/100);
//                     $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount,'is_deduction'=>$salary_attribute->is_checked,
//                     'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month);            
//                 }
//                 elseif($salary_attribute->amount ===null && $salary_attribute->percentage===null){
//                     $amount = round($gross_salary - ($basic_salary + $hra));
//                     $salary_amount[] = array('emp_id'=>$row->emp_id,'amount'=>$amount,'is_deduction'=>$salary_attribute->is_checked,
//                     'is_contribute'=>$salary_attribute->for_contributtion,'month_year'=>$year_and_month,'attribute'=>$salary_attribute->id,'actual_amount'=>$emp_salary->amount_per_month);
//                 }

//             }

//         }
//       }
//         ////
//      //return $weekleave;

//         $db = ['emp_id' => $row->emp_id, 'no_of_lop' => round($lop)+$weekleave + count($unpaid_leave), 'month_year' => $year_and_month];
//         DB::table('emp_lop')->updateOrInsert(['emp_id' => $row->emp_id, 'month_year' => $year_and_month], $db);
//     }
//     DB::table('emp_monthly_salary')->insert($salary_amount);
// }
// public function save_salary() {
    
//     $emp_list = BasicInfo::where('emp_status', 1)->get();
//     $currentYear = Carbon::now()->year;
//     $currentMonth = 4; 
//     $total_gross_salary = 0;

   
//     $data = [];
//     $salary_amount = [];
//     $attendanceData = Attendance::whereIn('emp_id', $emp_list->pluck('emp_id'))
//         ->whereRaw('Month(attendance_date) = ?', [$currentMonth])
//         ->whereRaw('Year(attendance_date) = ?', [$currentYear])
//         ->get()
//         ->groupBy('emp_id');

//     $rosterData = Roster::whereIn('emp_id', $emp_list->pluck('emp_id'))
//         ->where('status', 1)
//         ->whereRaw('Month(week_off) = ?', [$currentMonth])
//         ->whereRaw('Year(week_off) = ?', [$currentYear])
//         ->get()
//         ->groupBy('emp_id');

//     $leaves = Leave::whereNull('branch_id')
//         ->whereRaw('Month(date) = ?', [$currentMonth])
//         ->whereRaw('Year(date) = ?', [$currentYear])
//         ->pluck('date')
//         ->toArray();

//     $branchLeaves = Leave::whereIn('branch_id', $emp_list->pluck('branch_id'))
//         ->whereRaw('Month(date) = ?', [$currentMonth])
//         ->whereRaw('Year(date) = ?', [$currentYear])
//         ->get()
//         ->groupBy('branch_id');

//     $currentMonthDays = Carbon::create(null, $currentMonth)->daysInMonth;
//     $textFileContent = '565939107';
//     $textFileContent .= "\n";

//     foreach ($emp_list as $row) {
//         $lop = 0;
//         $status3Count = 0;
//         $empId = $row->emp_id;
//         $dept_id = $row->dept_id;
//         if (!isset($attendanceData[$empId])) {
//             continue;
//         }

//         $total_unpaid_days = $attendanceData[$empId]->where('leave_type', 7)->pluck('attendance_date')->toArray();

//         $all_attendance_row = $attendanceData[$empId]->where('is_sandwich_leave', 0)->pluck('attendance_date')->toArray();

//         $week_off = isset($rosterData[$empId]) ? $rosterData[$empId]->pluck('week_off')->toArray() : [];

//         $all_attendance_row = array_diff($all_attendance_row, $week_off);
//         $all_attendance_row = array_diff($all_attendance_row, $leaves);

//         if (isset($branchLeaves[$row->branch_id])) {
//             $branch_leave = $branchLeaves[$row->branch_id]->pluck('date')->toArray();
//             $all_attendance_row = array_diff($all_attendance_row, $branch_leave);
//         }

//         $total_leaves = $attendanceData[$empId]->where('leave_type', '!=', 7)->where('leave_status', 1)->pluck('attendance_date')->toArray();
//         $all_attendance_row = array_diff($all_attendance_row, $total_leaves);
//         $final_array = array_diff($all_attendance_row, $total_unpaid_days);
//         //return $final_array;

//         foreach ($final_array as $att) {
//             $check_data = $attendanceData[$empId]->where('attendance_date', $att)->first();
//             if ($check_data) {
//                 $weekday = Carbon::parse($check_data->attendance_date)->format('l'); 
//                 if ($weekday != 'Sunday' && $weekday != 'Saturday') {
//                     if ($check_data->status == 0) {
//                         $lop += 1;
//                     } else if ($check_data->status == 2) {
//                         $lop += 0.5;
//                     } 
//                 }
//             }
//         }

//         $unpaid_leave = $attendanceData[$empId]->where('leave_type', 7)->count();
//         $sandwich_leave = $attendanceData[$empId]->where('is_sandwich_leave', 1)->count();
//         $lop += $unpaid_leave;
//         $lop += $sandwich_leave;

//         $now = Carbon::now();
//         $year = $now->year;
//         $month = Carbon::create(null, $currentMonth)->format('F');
//         $year_and_month = $month . ' ' . $year;

//         $data[] = ['emp_id' => $empId, 'no_of_lop' => $lop, 'month_year' => $year_and_month];

//         $total_salary = Sallary::where('emp_id', $row->emp_id)->where('status', 1)->where('is_deduction', 0)->where('is_contribute', 0)->sum('amount_per_month');
//         $one_day_salary = round($total_salary / $currentMonthDays);
//         //return $one_day_salary;
//         $no_of_working_days = $currentMonthDays - $lop;
//         $gross_salary = round($no_of_working_days * $one_day_salary);
//         $total_gross_salary +=  $gross_salary;

//         $emp_salary_data = Sallary::where('emp_id', $row->emp_id)->where('status', 1)->get();

//         if (count($emp_salary_data) > 0) {
//             foreach ($emp_salary_data as $emp_salary) {
//                 $basic_salary_attr = Salaryattribute::where('id', 1)->first();
//                 $basic_salary = $gross_salary * $basic_salary_attr->percentage / 100;

//                 $hra_attr = Salaryattribute::where('id', 2)->first();
//                 $hra = $basic_salary * $hra_attr->percentage / 100;

//                 $salary_attribute = Salaryattribute::where('id', $emp_salary->attribute_id)->first();

//                 if ($salary_attribute->attribute == 'TDS') {
//                     $amount = $emp_salary->amount_per_anum;
//                     $salary_amount[] = [
//                         'emp_id' => $row->emp_id,
//                         'amount' => $amount,
//                         'is_deduction' => $salary_attribute->is_checked,
//                         'is_contribute' => $salary_attribute->for_contributtion,
//                         'month_year' => $year_and_month,
//                         'attribute' => $salary_attribute->id,
//                         'actual_amount' => $emp_salary->amount_per_month
//                     ];
//                 } else {
//                     if ($salary_attribute->deduction_from == 'Package') {
//                         if (empty($salary_attribute->percentage) && !empty($salary_attribute->amount)) {
//                             $amount = $salary_attribute->amount;
//                         } elseif (empty($salary_attribute->amount) && !empty($salary_attribute->percentage)) {
//                             $amount = round($salary_attribute->percentage * $gross_salary / 100);
//                         } else {
//                             $amount = round($gross_salary - ($basic_salary + $hra));
//                         }
//                     } else if ($salary_attribute->deduction_from == 'basic') {
//                         if (empty($salary_attribute->percentage) && !empty($salary_attribute->amount)) {
//                             $amount = $salary_attribute->amount;
//                         } elseif (empty($salary_attribute->amount)  && !empty($salary_attribute->percentage)) {
//                             $amount = round($salary_attribute->percentage * $basic_salary / 100);
//                         } else {
//                             $amount = round($gross_salary - ($basic_salary + $hra));
//                         }
//                     }
//                     $salary_amount[] = [
//                         'emp_id' => $row->emp_id,
//                         'amount' => $amount,
//                         'is_deduction' => $salary_attribute->is_checked,
//                         'is_contribute' => $salary_attribute->for_contributtion,
//                         'month_year' => $year_and_month,
//                         'attribute' => $salary_attribute->id,
//                         'actual_amount' => $emp_salary->amount_per_month
//                     ];
//                 }
//             }
//         }
//     }
   
//     if (!empty($data)) {
//         DB::table('emp_lop')->insert($data);
//     }

//     if (!empty($salary_amount)) {
//         DB::table('emp_monthly_salary')->insert($salary_amount);
//     }
//         $now = Carbon::now();
//         $year = $now->year;
//         $month = Carbon::create(null, $currentMonth)->format('F');
//         $year_and_month = $month . ' ' . $year;

//     $get_report = BasicInfo::where('emp_status',1)->get();
//     $textFileContent .= "661405601083"." ".'INR6614' ." "  .'D'." "     .$total_gross_salary." ".  'SALARY PAYMENT-RIMS';
//     $textFileContent .= "\n";

//     foreach($get_report as $report){
//       $salary_amount_sum = DB::table('emp_monthly_salary')
//                            ->join('salary_attribute','salary_attribute.id','=','emp_monthly_salary.attribute')
//                            ->select('salary_attribute.attribute','emp_monthly_salary.amount')
//                            ->where('is_deduction',0)
//                            ->where('emp_id', $report->emp_id)
//                            ->where('month_year',$year_and_month)
//                            ->where('is_contribute',0)
//                            ->sum('emp_monthly_salary.amount');
//       $emp_account = Account::where('emp_id',$report->emp_id)->first();

//        $salary_deduction_sum= DB::table('emp_monthly_salary')
//                  ->join('salary_attribute', 'salary_attribute.id', '=', 'emp_monthly_salary.attribute')
//                  ->select('salary_attribute.attribute', 'emp_monthly_salary.amount')
//                  ->where('emp_monthly_salary.emp_id',$report->emp_id)
//                ->where(function($query) use ($month) {
//                $query->where('is_deduction', 1)
//                      ->orWhere('is_contribute', 1);
//                 })
//                ->where('month_year',$year_and_month)
//               ->sum('emp_monthly_salary.amount');

//   if($salary_amount_sum &&  $salary_deduction_sum && $emp_account){
//      $total_new_salary =  $salary_amount_sum -  $salary_deduction_sum;
//      $net_pay_data[] = ['emp_id' => $report->emp_id, 'net_pay' =>
//       $total_new_salary,'account_info'=>$emp_account->account_no,'working_days'=>$no_of_working_days];
//       $textFileContent.=$emp_account->account_no." ".'INR'.substr($emp_account->account_no,0,4)." "." C"." ".$total_new_salary.'for Salary' 
//       ;
//       $textFileContent .= "\n";



//   }

//    }
//     $fileName = 'net_pay_report_' . Carbon::now()->format('Y_m_d_H_i_s') . '.xlsx';
//     Excel::store(new SalaryReport($net_pay_data), 'public/' . $fileName);
//     //return response()->json(['file_path' => asset('storage/' . $fileName)]);

//     $textFileName = 'employee_salary_details_' . Carbon::now()->format('Y_m_d_H_i_s') . '.txt';
//     Storage::disk('public')->put($textFileName, $textFileContent);

//     return response()->download(storage_path("app/public/{$textFileName}"));


// }


public function get_tds_data($emp_id){
  $data = Sallary::where('emp_id',$emp_id)->where('attribute_id',12)->where('status',1)->first();
  if($data){
    $amount_per_anum = $data->amount_per_anum;

  }
  else{
    $amount_per_anum = '';
}

  $is_metrocity_employee = Sallary::where('emp_id',$emp_id)->orderBy('id','DESC')->where('status',1)->first();
  if($is_metrocity_employee){
    $metro_city_emp = $is_metrocity_employee->is_metrocity_emp;
  }
  else{
    $metro_city_emp = 0;

  }

   return response()->json(['status'=>200,'data'=>$amount_per_anum,'is_metro_city_emp'=>$metro_city_emp]);

}

public function get_gross_and_net_salary($emp_id){

    $gross_salary_per_anum = Sallary::where('is_deduction', 0)
        ->where('is_contribute', 0)
        ->where('emp_id', $emp_id)
        ->sum('amount_per_anum');
        
    $gross_salary_per_month = Sallary::where('is_deduction', 0)
        ->where('is_contribute', 0)
        ->where('emp_id', $emp_id)
        ->sum('amount_per_month');

   
    $total_deduction_per_anum = Sallary::where('is_deduction', 1)
        ->where('emp_id', $emp_id)
        ->orWhere('is_contribute', 1)
        ->where('emp_id', $emp_id) 
        ->sum('amount_per_anum');
        
    $total_deduction_per_month = Sallary::where('is_deduction', 1)
        ->where('emp_id', $emp_id)
        ->orWhere('is_contribute', 1)
        ->where('emp_id', $emp_id) 
        ->sum('amount_per_month');

    $net_salary_per_anum = max(0, $gross_salary_per_anum - $total_deduction_per_anum);
    $net_salary_per_month = max(0, $gross_salary_per_month - $total_deduction_per_month);

    $data = [
        'gross_salary_anum' => $gross_salary_per_anum,
        'gross_salary_month' => $gross_salary_per_month,
        'deduction_per_anum' => $total_deduction_per_anum,
        'deduction_per_month' => $total_deduction_per_month,
        'net_salary_anum' => $net_salary_per_anum,
        'net_salary_month' => $net_salary_per_month,
    ];

    return response()->json(['status' => 200, 'data' => $data]);

}

public function save_salary(){


}



}