<?php

namespace App\Http\Controllers\SalesApi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Validator;

class AttributeController extends Controller
{
    //$connection = DB::connection('sales_db');

    public function add_points_attribute(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'product' => 'required',
            'country'=>'required',
            'service'=>'required',
            'attribute'=>'required',
        ]);
        if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }

    $data = array('country_id'=>$request->country,'product_id'=>$request->product,
    'service_id'=>$request->service,'subservice_id'=>$request->subservice,
    'attribute_name'=>$request->attribute,'created_by'=>$request->emp_id);
    DB::connection('sales_db')->table('attribute')->insert($data);
     return response()->json(['status'=>200,'message'=>'Attribute Added Successfully']);
    }

public function points_attribute_list(){
    $list = [];
    // $data = DB::connection('sales_db')->table('attribute')->join('country','country.id','attribute.country_id')
    //        ->join('product','product.id','attribute.product_id')
    //        ->join('product_service','product_service.id','attribute.service_id')
    //        ->leftjoin('product_category','product_category.id','attribute.subservice_id')
    //        ->select('country.name as country_name','product.product_name','product_service.service_name',
    //        'product_category.category_name','attribute.id','attribute.status','attribute.attribute_name')
    //        ->get();
    $data = DB::connection('sales_db')->table('attribute')->get();
    foreach($data as $row){
     $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();
     $country = DB::table('country')->where('id',$row->country_id)->first();
     $service = DB::connection('sales_db')->table('product_service')->where('id',$row->service_id)->first();
     $subservice = DB::connection('sales_db')->table('product_category')->where('id',$row->subservice_id)->first();
     if($subservice){
        $sub = $subservice->category_name;

     }
     else{
        $sub = '';

     }
     $list[] = array('id'=>$row->id,'product'=>$product->product_name,'country'=>$country->name,
     'service'=>$service->service_name,'subservice'=> $sub,'attribute_name'=>$row->attribute_name,'status'=>$row->status);

    }
    return response()->json(['status'=>200,'data'=>$list]);


}
public function points_attribute_edit($id){
 $data =DB::connection('sales_db')->table('attribute')->where('id',$id)->first();
 return response()->json(['status'=>200,'data'=>$data]);

}
public function points_attribute_update(Request $request,$id){
         $input = $request->all();
         //return $input;
        $validator = Validator::make($input, [
            'product' => 'required',
            'country'=>'required',
            'service'=>'required',
            'attribute'=>'required',
        ]);
        if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      if($request->subservice!=''){
        $subservice = $request->subservice;
      }
      else{
      
        $subservice = '';

      }
      DB::connection('sales_db')->table('attribute')->where('id',$id)->update(['country_id'=>$request->country,'product_id'=>$request->product,
     'service_id'=>$request->service,'subservice_id'=>$subservice,
     'attribute_name'=>$request->attribute,'created_by'=>$request->emp_id]);
     return response()->json(['status'=>200,'message'=>'Attribute Updated Successfully']);
    }


  public function points_attribute_status($id){
    $data = DB::connection('sales_db')->table('attribute')->where('id',$id)->first();
    return response()->json(['status'=>200,'data'=>$data->status]);

}
public function points_attribute_status_update(Request $request){
    $request->validate([
        'id' => 'required',
        'status' => 'required|in:0,1', 
    ]);
    $doc = DB::connection('sales_db')->table('attribute')
       ->where('id',$request->id)->update(['status'=>$request->status]);
    return response()->json(['status'=>200, 'message' => 'Attribute updated successfully']);

 }
 public function get_subservice($id){
    $data = DB::connection('sales_db')->table('product_category')->where('service_id',$id)->get(['id','category_name']);
    return response()->json(['status'=>200,'data'=>$data]);
 }


 public function add_points_subattribute(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'product' => 'required',
        'country'=>'required',
        'service'=>'required',
        'attribute'=>'required',
        'subattribute'=>'required'
    ]);
    if($validator->fails()){
        $messages=$validator->messages();
        return response()->json(["messages"=>$messages,'status'=>400]);     
  }

$data = array('country_id'=>$request->country,'product_id'=>$request->product,
'service_id'=>$request->service,'subservice_id'=>$request->subservice,
'attribute_id'=>$request->attribute,'name'=>$request->subattribute,'created_by'=>$request->emp_id);
DB::connection('sales_db')->table('subattribute')->insert($data);
 return response()->json(['status'=>200,'message'=>'SubAttribute Added Successfully']);
}

public function points_subattribute_list(){
$list = [];
// $data = DB::connection('sales_db')->table('attribute')->join('country','country.id','attribute.country_id')
//        ->join('product','product.id','attribute.product_id')
//        ->join('product_service','product_service.id','attribute.service_id')
//        ->leftjoin('product_category','product_category.id','attribute.subservice_id')
//        ->select('country.name as country_name','product.product_name','product_service.service_name',
//        'product_category.category_name','attribute.id','attribute.status','attribute.attribute_name')
//        ->get();
$data = DB::connection('sales_db')->table('subattribute')->get();
foreach($data as $row){
 $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();
 $country = DB::table('country')->where('id',$row->country_id)->first();
 $service = DB::connection('sales_db')->table('product_service')->where('id',$row->service_id)->first();
 $subservice = DB::connection('sales_db')->table('product_category')->where('id',$row->subservice_id)->first();
 $attribute = DB::connection('sales_db')->table('attribute')->where('id',$row->attribute_id)->first();
 if($subservice){
    $sub = $subservice->category_name;

 }
 else{
    $sub = '';

 }
 $list[] = array('id'=>$row->id,'product'=>$product->product_name,'country'=>$country->name,
 'service'=>$service->service_name,'subservice'=> $sub,'attribute'=>$attribute->attribute_name,'subattribute'=>$row->name,'status'=>$row->status);

}
return response()->json(['status'=>200,'data'=>$list]);


}
public function points_subattribute_edit($id){
$data =DB::connection('sales_db')->table('subattribute')->where('id',$id)->first();
return response()->json(['status'=>200,'data'=>$data]);

}
public function points_subattribute_update(Request $request,$id){
     $input = $request->all();
     //return $input;
    $validator = Validator::make($input, [
        'product' => 'required',
        'country'=>'required',
        'service'=>'required',
        'attribute'=>'required',
        'subattribute'=>'required',
    ]);
    if($validator->fails()){
        $messages=$validator->messages();
        return response()->json(["messages"=>$messages,'status'=>400]);     
  }
  if($request->subservice!=''){
    $subservice = $request->subservice;
  }
  else{
  
    $subservice = '';

  }
  DB::connection('sales_db')->table('subattribute')->where('id',$id)->update(['country_id'=>$request->country,'product_id'=>$request->product,
 'service_id'=>$request->service,'subservice_id'=>$subservice,
 'attribute_id'=>$request->attribute,'name'=>$request->subattribute,'created_by'=>$request->emp_id]);
 return response()->json(['status'=>200,'message'=>'SubAttribute Updated Successfully']);
}


public function points_subattribute_status($id){
$data = DB::connection('sales_db')->table('subattribute')->where('id',$id)->first();
return response()->json(['status'=>200,'data'=>$data->status]);

}
public function points_subattribute_status_update(Request $request){
$request->validate([
    'id' => 'required',
    'status' => 'required|in:0,1', 
]);
$doc = DB::connection('sales_db')->table('subattribute')
   ->where('id',$request->id)->update(['status'=>$request->status]);
return response()->json(['status'=>200, 'message' => 'Attribute updated successfully']);

}

public function points_active_attribute(){
    $data = DB::connection('sales_db')->table('attribute')->where('status',1)->get(['id','attribute_name']);
    return response()->json(['status'=>200,'data'=>$data]);
}



}
