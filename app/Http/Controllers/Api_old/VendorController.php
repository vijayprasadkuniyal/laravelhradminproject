<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;
use Validator;

class VendorController extends Controller
{
    public function create_vendor(Request $request){
        $input = $request->all();
            $validator = Validator::make($input, [
                'name' => 'required',
                'mobile'=>'required',
                'email'=>'required',
                'address'=>'required',
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
            $vendor = new Vendor();
            $vendor->name = $request->name;
            $vendor->mobile = $request->mobile;
            $vendor->email = $request->email;
            $vendor->address = $request->address;
            $vendor->created_by = $request->emp_id;
            $vendor->save();
            if($vendor){
                return response()->json(['status'=>200,'message'=>'Vendor Created Successfully']);
            }
            else{
                return response()->json(['message'=>'Issue in vendor creation']);
             }
          }
       public function vendor_list(){
        $vendor_list = Vendor::get(['id','name','mobile','email','address','status']);
        return response()->json(['status'=>200,'message'=>'Vendor List','data'=>$vendor_list]);
       }
       public function vendor_edit($id){
        $vendor = Vendor::findOrFail($id);
        return response()->json(['status'=>200,'message'=>'Vendor List','vendor'=>$vendor]);
      }
    public function vendor_update(Request $request,$id){
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required',
            'mobile'=>'required',
            'email'=>'required',
            'address'=>'required',
        ]);
    
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
        $vendor = Vendor::findOrFail($id);
        $vendor->name = $request->name;
        $vendor->mobile = $request->mobile;
        $vendor->email = $request->email;
        $vendor->address = $request->address;
        $vendor->created_by = $request->emp_id;
        $vendor->save();
        if($vendor){
            return response()->json(['status'=>200,'message'=>'Vendor Updated Successfully']);
        }
        else{
            return response()->json(['message'=>'Issue in vendor updation']);
         }

    }
    public function vendor_status($id){
        $vendor = Vendor::findOrFail($id);
       if($vendor){
        return response()->json(['status' => 200, 'message' => 'vendor status','data'=>$vendor->status]);
      }
     else{
        return response()->json(['status' =>500, 'message' => 'data not found']);
      }

    }
    public function update_vendor_status(Request $request){
        $request->validate([
            'vendor_id' => 'required',
            'status' => 'required|in:0,1', 
        ]);
    
        $vendor = Vendor::findOrFail($request->vendor_id);
        $vendor->status = $request->status;
        $vendor->save();
        if($vendor){
            return response()->json(['status' => 200, 'message' => 'vendor status updated successfully']);
    
        }
        else{
            return response()->json(['status' => 500, 'message' => 'something went wrong']);
    
        }

    }

}
