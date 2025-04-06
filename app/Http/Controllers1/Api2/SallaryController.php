<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sallary;
use Validator;
use App\Models\SalaryHistory;
use App\Models\Salaryattribute;

class SallaryController extends Controller
{
    public function sallary_calculate(Request $request){
     $input = $request->all();
     //dd($input);
     $validator = Validator::make($input, [
        'package_detail' => 'required',
     ]);
      if($validator->fails()){
            $messages=$validator->messages();
            //$messages->add('company_logo', 'The company logo is required.');
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $checked_id = $request->checked_id;
      $package = $request->package_detail;
       $data = explode(',',$checked_id);
       $data = json_encode($data);
       $data = json_decode($data);
      foreach( $data as $row){
        $attribute_id = Salaryattribute::where('id',$row)->first();
        $basic_salary = $package*50/100;
         if($attribute_id->is_checked == 1){
           
           $salary = (int)$basic_salary*$attribute_id->percentage/100;

         }
         else{
          $salary = (int)$package*$attribute_id->percentage/100;
         }
          $sallary_calculate = Sallary::updateOrInsert(
            ['emp_id' => $request->emp_id,'attribute_id'=>$row],
            [
                'emp_package' =>$package,
                'emp_id'=>$request->emp_id,
                'attribute_id'=>$row,
                'amount'=>$salary,
                'basic_salary'=> $basic_salary,


            ]
        );


      }
        

        if($sallary_calculate) {
            return response()->json(['status'=>200,'message' => 'Sallary Created Or Updated Successfully']);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
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
           'percentage' => 'required',
           'company'=>'required'
        ]);
         if($validator->fails()){
               $messages=$validator->messages();
               return response()->json(["messages"=>$messages,'status'=>400]);     
         }
         $data = new Salaryattribute();
         $data->attribute = $request->attribute;
         $data->percentage = $request->percentage;
         $data->is_checked =  $request->is_checked;
         $data->company_id = $request->company;
         $data->created_by = 1;
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
        $data = Salaryattribute::findOrFail($id);
        $input = $request->all();
        $validator = Validator::make($input, [
           'attribute' => 'required',
           'percentage' => 'required',
           'company'=>'required'
        ]);
         if($validator->fails()){
               $messages=$validator->messages();
               return response()->json(["messages"=>$messages,'status'=>400]);     
         }
         $data->attribute = $request->attribute;
         $data->percentage = $request->percentage;
         $data->is_checked =  $request->is_checked;
         $data->company_id = $request->company;
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


}
public function salary_fields(){
  $data = Salaryattribute::where('status',1)->get(['id','attribute','percentage','is_checked']);
  if($data){
    return response()->json(['status' => 200, 'message' => 'attribute List','data'=>$data]);

  }
  else{
     return response()->json(['status' => 500, 'message' => 'data not found']);

  }


}
public function get_salary($id){
 $salary = Sallary::join('salary_attribute','salary_attribute.id','emp_salary_info.attribute_id')
          ->select('salary_attribute.attribute','emp_salary_info.id','emp_salary_info.emp_id','emp_salary_info.basic_salary','emp_salary_info.amount','emp_salary_info.emp_package','emp_salary_info.status')
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

    // Find the company by ID
    $salary = Sallary::findOrFail($request->id);
    //dd($company);

    // Update the status of the company
    $salary->status = $request->status;
    $salary->save();
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
}