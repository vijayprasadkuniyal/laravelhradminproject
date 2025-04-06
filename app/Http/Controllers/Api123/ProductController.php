<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Validator;

class ProductController extends Controller
{
    public function save_product(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'company' => 'required',
        'product_name'=>'required',
        'short_name'=>'required'
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $product = new Product();
      $product->company_id = $request->company;
      $product->product_name = $request->product_name;
      $product->short_name = $request->short_name;
      $product->save();
      if($product){
         return response()->json(['status'=>200,'message'=>'Product Created Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }
 }
  public function product_list(){
     $product = Product::join('company_details','company_details.id','=','product_details.company_id')
           ->select('company_details.business_name','product_details.id as product_id','product_details.product_name','product_details.short_name','product_details.status')
           ->get();
     return response()->json(['status'=>200,'message'=>'List Of Products','data'=>$product]);
   }
  public function update_product(Request $request,$id){
     $input = $request->all();
     $validator = Validator::make($input, [
        'company' => 'required',
        'product_name'=>'required',
        'short_name'=>'required'
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $product = Product::findOrFail($id);
      $product->company_id = $request->company;
      $product->product_name = $request->product_name;
      $product->short_name = $request->short_name;
      $product->save();
       if($product){
         return response()->json(['status'=>200,'message'=>'Product Updated Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }

}
public function product_edit($id){
    $product = Product::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'Product Details','product'=>$product]);
}
public function product_status(Request $request){
    //dd('hi');
    $request->validate([
        'product_id' => 'required',
        'status' => 'required|in:0,1', 
    ]);

    // Find the company by ID
    $product = Product::findOrFail($request->product_id);
    //dd($company);

    // Update the status of the company
    $product->status = $request->status;
    $product->save();
    if($product){
        return response()->json(['status' => 200, 'message' => 'Product status updated successfully']);

    }
    else{
        return response()->json(['status' => 500, 'message' => 'something went wrong']);

    }


}
public function get_product_status($id){
    $product = Product::findOrFail($id);
    if($product){
        return response()->json(['status' => 200, 'message' => 'Product status','data'=>$product->status]);

    }
    else{
        return response()->json(['status' =>500, 'message' => 'data not found']);

    }


}
public function company_based_product($company_id) {
    try {
        $product = Product::where('company_id', $company_id)->where('status',1)
            ->get(['id', 'product_name']);

        if ($product->isEmpty()) {
            return response()->json(['status' => 222, 'message' => 'No product data found']);
        }

        return response()->json(['status' => 200, 'message' => 'product details', 'data' => $product]);
    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'message' => 'Internal Server Error']);
    }
}
}
