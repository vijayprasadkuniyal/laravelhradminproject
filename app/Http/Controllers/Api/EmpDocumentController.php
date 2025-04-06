<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document;
use Validator;

class EmpDocumentController extends Controller
{
    public function save_emp_document(Request $request) {
    $input = $request->all();
    $validator = Validator::make($input, [
        'document' => 'required',
        'document_details' => 'required',
    ]);

    if ($validator->fails()) {
        $messages = $validator->messages();
        return response()->json(["messages" => $messages, 'status' => 400]);
    }

    $document = new Document();
    $document->doc_id = $request->document;
    $document->emp_id = $request->emp_id;
    $document->document_details = $request->document_details;
    $document->created_by = 1;

    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $destinationPath = 'employee_document/';
        $emp_document = date('YmdHis') . "." . $image->getClientOriginalExtension();
        $image->move(public_path($destinationPath), $emp_document);
        
        $baseUrl = url('/');
        $document->doc_url = $baseUrl . '/' . $destinationPath . $emp_document;
    }

    $document->save();

    $data = Document::where('id', $document->id)->get(['emp_id', 'doc_id', 'document_details', 'doc_url']);
    if ($document) {
        return response()->json(['status' => 200, 'message' => 'Document Saved Successfully', 'data' => $data]);
    } else {
        return response()->json(['status' => 500, 'message' => 'Something went wrong']);
    }
}

    public function document_list($id){
    	$document = Document::join('document_type','emp_document_info.doc_id','=','document_type.id')
    	         ->select('document_type.document_name','emp_document_info.id','emp_document_info.emp_id','emp_document_info.doc_url','emp_document_info.status')
    	         ->where('emp_id',$id)
    	         ->get();
      if($document){
         return response()->json(['status'=>200,'message'=>'Documents list','data'=>$document]);
       }
     else{
        return response()->json(['status'=>500,'message'=>'No Data Found']);

     }



    }
    public function edit_document($id){
    	$document = Document::findOrFail($id);
    	return response()->json(['status'=>200,'message'=>'Document Details','document'=>$document]);
     }
    public function update_document(Request $request,$id){
    	$input = $request->all();
       $validator = Validator::make($input, [
         'document' => 'required',
         'document_details'=>'required',
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $document  = Document::findOrFail($id);
      $document->doc_id = $request->document;
      $document->document_details = $request->document_details;
      if ($request->hasFile('image')) {
        $image = $request->file('image');
        $destinationPath = 'employee_document/';
        $emp_document = date('YmdHis') . "." . $image->getClientOriginalExtension();
        $image->move(public_path($destinationPath), $emp_document);
        
        $baseUrl = url('/');
        $document->doc_url = $baseUrl . '/' . $destinationPath . $emp_document;
    }
      $document->save();
      $data = Document::where('id',$document->id)->get(['emp_id','doc_id','document_details','doc_url',]);
      if($document){
         return response()->json(['status'=>200,'message'=>'Document Updated Successfully','data'=>$data]);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }

    }
    public function document_status(Request $request){
    //dd('hi');
    $request->validate([
        'id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $document = Document::findOrFail($request->id);
    //dd($company);

    // Update the status of the company
    $document->status = $request->status;
    $document->save();
    if($document){
        return response()->json(['status' => 200, 'message' => 'Document  status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_document_status($id){
    $document = Document::findOrFail($id);
    if($document){
        return response()->json(['status' => 200, 'message' => 'Document status','data'=>$document->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
}
