<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class AdminProcessController extends Controller
{
    public function kyc_document_list(){
        $data = DB::connection('sales')->table('client_document_list')->join('client_basic_info','client_basic_info.id','client_document_list.client_id')
              ->join('sales_document','sales_document.id','client_document_list.document_id')
              ->select('client_basic_info.name','client_document_list.id','client_document_list.status','sales_document.document_name',
              'client_document_list.image','client_document_list.document_no','client_document_list.client_id')
              ->orderBy('id','Desc')
              ->get();
        return response()->json(['status'=>200,'message'=>'Kyc Document Details','data'=>$data]);
    }
    public function kyc_document_status($id){
    $kyc_document = DB::connection('sales')->table('client_document_list')->where('id',$id)->first();
    if($kyc_document){
      return response()->json(['status' => 200, 'message' => 'Kyc status','data'=>$kyc_document->status,'remark'=>$kyc_document->remark]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }
    }
    public function kyc_document_status_change(Request $request){
        //return $request->all();
        $request->validate([
            'doc_id' => 'required',
            'status' => 'required|in:0,1', 
        ]);

        $kyc_document = DB::connection('sales')->table('client_document_list')->where('id',$request->doc_id)->first();
        DB::connection('sales')->table('client_document_list')->where('id',$request->doc_id)->update(['status'=>$request->status,'remark'=>$request->remark]);
        $total_client_document =  DB::connection('sales')->table('client_document_list')->where('client_id',$request->client_id)->count();

        //return $request->client_id;
        $total_approved_document = DB::connection('sales')->table('client_document_list')->where('client_id',$request->client_id)->where('status',1)->count();
        if($total_client_document == $total_approved_document ){
            DB::connection('sales')->table('client_basic_info')->where('id',$request->client_id)->update(['admin_verification_status'=>1]);

        }
        else{
            DB::connection('sales')->table('client_basic_info')->where('id',$request->client_id)->update(['admin_verification_status'=>0]);


        }

        return response()->json(['status' => 200, 'message' => 'Kyc status updated successfully']);
    }
}
