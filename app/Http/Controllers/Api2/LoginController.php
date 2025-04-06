<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BasicInfo;
use Illuminate\Support\Facades\Validator; // Added use statement
use Illuminate\Support\Facades\Hash; // Added use statement
use App\Models\Department;
use Auth;
use App\Models\LoginHours;
Use \Carbon\Carbon;
use App\Models\LoginHistory;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages();
            return response()->json(["messages" => $messages, 'status' => 400]);
        }

        $emp = BasicInfo::where('login_id', $request->input('email'))->first();

        if ($emp) {
            if (Hash::check($request->input('password'), $emp->login_password)) {
                $token = $emp->createToken('Laravel Password Grant Client')->accessToken;
                $department = Department::where('id',$emp->dept_id)->pluck('department_name');
                $data = array('id'=>$emp->id,'name'=>$emp->emp_fname,'emp_id'=>$emp->emp_id,'department'=>$department[0],'email'=>$emp->login_id);
                $login_details = array('emp_id'=>$emp->emp_id,'login_time'=>Carbon::now('Asia/Kolkata'));
                $row = LoginHours::whereDate('created_date', Carbon::now()->format('Y-m-d'))->where('emp_id',$emp->emp_id)
                ->first();
                if(!$row){
                  LoginHours::insert($login_details);
                }
                 LoginHistory::insert($login_details);
                return response()->json(['status' => 200, 'message' => 'Logged In Successfully', 'token' => $token,'data'=>$data]);

            } else {
                return response()->json(['status' => 400, 'message' => 'Incorrect password']);
            }
        } else {
            return response()->json(['status' => 400, 'message' => 'Incorrect Email']);
        }
    }
    public function logout_emp(Request $request){
        $user = Auth::user()->token();
        $user->revoke();
        $emp_id = $user->user_id;
        $row = LoginHours::where('emp_id', $emp_id)
        ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
       ->first();
        LoginHours::where('emp_id',$row->emp_id)->whereDate('created_date', Carbon::now()->format('Y-m-d'))->update(['logout_time'=>Carbon::now('Asia/Kolkata')]);
        LoginHistory::where('emp_id',$row->emp_id)->whereDate('created_date', Carbon::now()->format('Y-m-d'))->update(['logout_time'=>Carbon::now('Asia/Kolkata')]);
        return response()->json(['status'=>'200','message'=>'logout successfully']);


    }
    


}