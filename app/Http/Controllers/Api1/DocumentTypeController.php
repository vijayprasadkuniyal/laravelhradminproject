<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DocumentType;
use Validator;

class DocumentTypeController extends Controller
{
    public function save_document_type(Request $request){
     $input = $request->all();
     $validator = Validator::make($input, [
        'document_type' => 'required',
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $document_type  = new DocumentType();
      $document_type->document_name = $request->document_type;
      $document_type->save();
      if($document_type){
         return response()->json(['status'=>200,'message'=>'Document Type Created Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }
      //return $this->sendResponse($country->only(['id','name','short_name']), 'country Created Successfully.');
 }
 public function document_type_list(){
    $document_type = DocumentType::get(['id','document_name','status']);
    return response()->json(['status'=>200,'message'=>'List Of Document Type','data'=>$document_type]);
  }
public function document_type_edit($id){
    $document_type = DocumentType::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'Document Type Details','document_type'=>$document_type]);
    }
  public function update_document_type(Request $request,$id){
    $input = $request->all();
    $validator = Validator::make($input, [
        'document_type' => 'required',
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $document_type  = DocumentType::findOrFail($id);
      $document_type->document_name = $request->document_type;
      $document_type->save();
      if($document_type){
         return response()->json(['status'=>200,'message'=>'Document Type Updated Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }

}
public function document_type_status(Request $request){
    //dd('hi');
    $request->validate([
        'document_type_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $document_type = DocumentType::findOrFail($request->document_type_id);
    //dd($company);

    // Update the status of the company
    $document_type->status = $request->status;
    $document_type->save();
    if($document_type){
        return response()->json(['status' => 200, 'message' => 'Document Type  status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_document_type_status($id){
    $document_type = DocumentType::findOrFail($id);
    if($document_type){
        return response()->json(['status' => 200, 'message' => 'Document Type status','data'=>$document_type->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
public function get_active_documenttype(){
    $document_type = DocumentType::where('status',1)->get(['id','document_name']);
    if($document_type){
        return response()->json(['status'=>200,'message'=>'document list','data'=>$document_type]);
    }
    else{
        return response()->json(['status'=>200,'message'=>'data not found']);

    }
}
}
