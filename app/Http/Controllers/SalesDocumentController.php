<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalesDocument;
//use App\Models\Roster;
use DB;
use Validator;

class SalesDocumentController extends Controller
{
    public function sales_package_type(){
        $package_type = DB::table('sales_package_type')->get(['id','package_name']);
        return response()->json(['status'=>200,'message'=>'Document Type List','data'=> $package_type]);
    }
    public function product_category(){
        $category_type = DB::table('product_category')->get(['id','category_name']);
        return response()->json(['status'=>200,'message'=>'Product Category List','data'=>$category_type]);

    }
    public function save_sales_document(Request $request){
        $input = $request->all();
        //return $input;
            $validator = Validator::make($input, [
                'package_id' => 'required',
                'product_category'=>'required',
                'document_name'=>'required',
                'is_required'=>'required',
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
             // return $request->is_required;
            $document = new SalesDocument();
            $document->package_type_id = implode(' , ',$request->package_id);
            //return implode(',',$request->package_id);
            //return $document->package_type_id;
            $document->product_category_id = $request->product_category;
            $document->document_name = $request->document_name;
            $document->is_required = $request->is_required;
            $document->created_by = $request->emp_id;
            $document->save();
            if($document){
                return response()->json(['status'=>200,'message'=>'Document Created Successfully']);
            }
            else{
                return response()->json(['message'=>'Issue in document creation']);
             }
        }
        public function document_list()
        {
            $data = [];
            $document_list = SalesDocument::all();
        
            foreach ($document_list as $row) {
                $package_type_id = explode(',', $row->package_type_id);
                $packageTypes = DB::table('sales_package_type')
                    ->whereIn('id', $package_type_id)
                    ->pluck('package_name')
                    ->implode(',');
                $category = DB::table('product_category')->where('id',$row->product_category_id)->first();
                $data[] = [
                    'id'=>$row->id,
                    'package_name' => $packageTypes,
                    'document_name'=>$row->document_name,
                    'product_category'=>$category->category_name,
                    'status'=>$row->status,
                   ];
            }
        
            return response()->json(['status'=>200,'message'=>'Document List','data'=>$data]);
        }
        public function sales_doc_status($id){
            $doc = SalesDocument::findOrFail($id);
            return response()->json(['status'=>200,'message'=>'List Of Document','data'=>$doc]);

        }
        public function sales_document_status_change(Request $request){
            $request->validate([
                'doc_id' => 'required',
                'status' => 'required|in:0,1', 
            ]);
        
            $doc = SalesDocument::findOrFail($request->doc_id);
            //dd($company);
        
            // Update the status of the company
            $doc->status = $request->status;
            $doc->save();
            if($doc){
                return response()->json(['status' => 200, 'message' => 'Document status updated successfully']);
        
            }
            else{
                return response()->json(['status' => 500, 'message' => 'something went wrong']);
        
            }

        }
        public function sales_document_edit($id){
            $sales_doc = SalesDocument::findOrFail($id);
            return response()->json(['status' => 200, 'data'=>$sales_doc]);

        }
        public function sales_document_update(Request $request,$id){
            $input = $request->all();
        //return $input;
            $validator = Validator::make($input, [
                'package_id' => 'required',
                'product_category'=>'required',
                'document_name'=>'required',
                'is_required'=>'required',
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
             // return $request->is_required;
            $document = SalesDocument::findOrFail($id);
            $document->package_type_id = implode(' , ',$request->package_id);
            //return implode(',',$request->package_id);
            //return $document->package_type_id;
            $document->product_category_id = $request->product_category;
            $document->document_name = $request->document_name;
            $document->is_required = $request->is_required;
            $document->created_by = $request->emp_id;
            $document->save();
            if($document){
                return response()->json(['status'=>200,'message'=>'Document Updated Successfully']);
            }
            else{
                return response()->json(['message'=>'Issue in document Updation']);
             }

        }
        
        

}
