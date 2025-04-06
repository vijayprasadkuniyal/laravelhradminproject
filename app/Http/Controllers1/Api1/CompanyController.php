<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use Validator;

class CompanyController extends Controller
{
     public function save_company(Request $request){
     $input = $request->all();
     $validator = Validator::make($input, [
        'business_name' => 'required',
        'short_name'=>'required',
        'client_name' => 'required',
        'first_mobile_no'=>'required',
        //'second_mobile_no'=>'required',
        'first_email_id'=>'required',
        //'second_email_id'=>'required',
        'country_id'=>'required',
        'state_id'=>'required',
        'zip_code'=>'required',
        'full_address'=>'required',
        'website_url'=>'required',
        //'company_logo'=>'required',
     ]);
      if($validator->fails()){
            $messages=$validator->messages();
            //$messages->add('company_logo', 'The company logo is required.');
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $company = new Company();
      $company->business_name = $request->business_name;
      $company->short_name = $request->short_name;
      $company->client_name =   $request->client_name;
      $company->primary_mobile_no = $request->first_mobile_no;
      $company->secondary_mobile_no = $request->second_mobile_no;
      $company->primary_email_id = $request->first_email_id;
      $company->secondary_email_id = $request->second_email_id;
      $company->country_id = $request->country_id;
      $company->state_id = $request->state_id;
      $company->zip_code = $request->zip_code;
      $company->full_address = $request->full_address;
      $company->website_url = $request->website_url;
      if($image = $request->file('company_logo')) {
        //dd('hi');
            $destinationPath = 'logoimage/';
            $logoimage = date('YmdHis') . "." . $image->getClientOriginalExtension();
            $image->move($destinationPath, $logoimage);
            $logo = $logoimage;
            $company->company_logo = $logo;
        }
      $company->save();
      if($company){
         return response()->json(['status'=>200,'message'=>'Company Created Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }
 }
 public function company_list(){
     $company = Company::join('country','country.id','=','company_details.country_id')
            ->join('state','state.id','=','company_details.state_id')
            ->select('country.name as country_name','state.state_name','company_details.business_name','company_details.client_name','company_details.primary_mobile_no','company_details.secondary_mobile_no','company_details.primary_email_id','company_details.secondary_email_id','company_details.zip_code','company_details.full_address','company_details.website_url','company_details.company_logo','company_details.short_name','company_details.id','company_details.status')
           ->get();
     return response()->json(['status'=>200,'message'=>'List Of Compaines','data'=>$company]);
   }
  public function update_company(Request $request,$id){
     $input = $request->all();
     $validator = Validator::make($input, [
        'business_name' => 'required',
        'short_name'=>'required',
        'client_name' => 'required',
        'first_mobile_no'=>'required',
        //'second_mobile_no'=>'required',
        'first_email_id'=>'required',
        //'second_email_id'=>'required',
        'country_id'=>'required',
        'state_id'=>'required',
        'zip_code'=>'required',
        'full_address'=>'required',
        'website_url'=>'required',
        //'company_logo'=>'required',
     ]);
      if($validator->fails()){
            $messages=$validator->messages();
            //$messages->add('company_logo', 'The company logo is required.');
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $company = Company::findOrFail($id);
      $company->business_name = $request->business_name;
      $company->short_name = $request->short_name;
      $company->client_name =   $request->client_name;
      $company->primary_mobile_no = $request->first_mobile_no;
      $company->secondary_mobile_no = $request->second_mobile_no;
      $company->primary_email_id = $request->first_email_id;
      $company->secondary_email_id = $request->second_email_id;
      $company->country_id = $request->country_id;
      $company->state_id = $request->state_id;
      $company->zip_code = $request->zip_code;
      $company->full_address = $request->full_address;
      $company->website_url = $request->website_url;
      if($image = $request->file('company_logo')) {
        //dd('hi');
            $destinationPath = 'logoimage/';
            $logoimage = date('YmdHis') . "." . $image->getClientOriginalExtension();
            $image->move($destinationPath, $logoimage);
            $logo = $logoimage;
            $company->company_logo = $logo;
        }
      $company->save();
      if($company){
         return response()->json(['status'=>200,'message'=>'Company Updated Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }

}
public function Company_edit($id){
    $company = Company::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'List Of Country Details','company'=>$company]);
}
public function company_status(Request $request){
    //dd('hi');
    $request->validate([
        'company_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $company = Company::findOrFail($request->company_id);
    //dd($company);

    // Update the status of the company
    $company->status = $request->status;
    $company->save();
    if($company){
        return response()->json(['status' => 200, 'message' => 'Company status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_company_status($id){
    $company = Company::findOrFail($id);
    if($company){
        return response()->json(['status' => 200, 'message' => 'Company status','data'=>$company->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
public function get_active_company(){
    $company = Company::where('status',1)->get(['id','business_name']);
    if($company){
        return response()->json(['status' => 200, 'message' => 'Company details','data'=>$company]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }
}
}
