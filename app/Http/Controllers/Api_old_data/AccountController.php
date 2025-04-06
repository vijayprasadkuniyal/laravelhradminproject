<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use Validator;
use App\Events\TestEvent;
use App\Events\EnquiryCreated;

class AccountController extends Controller
{
    public function save_account(Request $request)
{    
    $message = 'hi sir kese ho';
        event(new TestEvent($message,'RIMS1'));
        return response()->json('Notification sent!');
    //event(new MyEvent('hello world'));
	 $input = $request->all();
     $validator = Validator::make($input, [
        'bank_name' => 'required',
        'account_no'=>'required',
        'ifsc_code' => 'required',
        'payment_mode'=>'required',
     ]);
      if($validator->fails()){
            $messages=$validator->messages();
            //$messages->add('company_logo', 'The company logo is required.');
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }

         $account = Account::updateOrInsert(
            ['emp_id' => $request->emp_id],
            [
                'bank_name' => $request->input('bank_name'),
                'account_no' => $request->input('account_no'),
                'ifsc_code' => $request->input('ifsc_code'),
                'payment_mode' => $request->input('payment_mode'),
                'emp_id'=>$request->emp_id,
                'uan_no'=>$request->pf,
                'esic_no'=>$request->esic,
                'created_by' => 1,
            ]
        );

        if($account) {
            return response()->json(['status'=>200,'message' => 'Account Created Or Updated Successfully']);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
}
public function edit_account($id){
  $data = Account::where('emp_id',$id)->get();
  if($data){
    return response()->json(['status'=>200,'message'=>'Account Details','account'=>$data]);
  }
  else{
    return response()->json(['status'=>500,'message'=>'no  data found','account'=>$data]);

  }
}
public function account_details($id){
	$account = Account::where('emp_id',$id)->get();
	if($account){
    return response()->json(['status'=>200,'message'=>'Account Details','account'=>$account]);
   }
  else{
    return response()->json(['status'=>500,'message'=>'no  data found']);
 }
}
public function account_status(Request $request){
    //dd('hi');
    $request->validate([
        'id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $account = Account::findOrFail($request->id);
    //dd($company);

    // Update the status of the company
    $account->status = $request->status;
    $account->save();
    if($account){
        return response()->json(['status' => 200, 'message' => 'Account status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_account_status($id){
    $account = Account::findOrFail($id);
    if($account){
        return response()->json(['status' => 200, 'message' => 'Account status','data'=>$account->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
}
