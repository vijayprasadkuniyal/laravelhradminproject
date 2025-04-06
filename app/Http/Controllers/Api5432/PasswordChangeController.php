<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BasicInfo;
use Hash;
use Validator;
use App\Models\ModulePermission;

class PasswordChangeController extends Controller
{
   public function change_password(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'password' => 'required',
        'confirm_password'=>'required',
    ]);

      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $password = $request->password;
      $confirm_password = $request->confirm_password;
      $emp_id = $request->emp_id;
      if($password == $confirm_password){
        $hash_password = Hash::make($password);
        $data = BasicInfo::where('emp_id', $emp_id)->update(['login_password'=>$hash_password]);
        if($data){
            return response()->json(['status'=>200,'message'=>'Password Change Successfully']);

        }
        else{
            return response()->json(['status'=>500,'message'=>'Emp Id Not Found']);

        }


      }
      else{
        return response()->json(['status'=>500,'message'=>'Password and confirm password are not matched']);

      }
    }
    public function save_permission(Request $request){
      $input = $request->all();
      //dd($input);
      $validator = Validator::make($input, [
        'modules'=>'required',
     ]);
      if($validator->fails()){
            $messages=$validator->messages();
            //$messages->add('company_logo', 'The company logo is required.');
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      ModulePermission::where('emp_id',$request->emp_id)->update(['status'=>0]);
     
      foreach($request->modules as $module){
        $data = [
          'emp_id' => $request->input('emp_id'),
          'module_name' => $module,
          'status'=>1,
      ];
        ModulePermission::insert($data);
       }
      return response()->json(["messages"=>'Module saved','status'=>200]);
      




    }
    public function get_permission($id){
      $permission = ModulePermission::where('emp_id',$id)->get();
      if($permission){
        return response()->json(["messages"=>'Permission List','status'=>200,'data'=>$permission]);

      }
      else{
        return response()->json(["messages"=>'No Data Found','status'=>500]);

      }
    }
    

   }
