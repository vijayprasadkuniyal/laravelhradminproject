<?php

namespace App\Http\Controllers\SalesApi;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\adminSales\product;
use App\Models\adminSales\product_service;
use App\Models\adminSales\product_category;
use Validator;

class adminProductController extends Controller
{
    public function add_product_details(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_name' => 'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }
        $product = new product();
        $product->product_name = $request->product_name;
        $product->created_by = $request->created_by;
        $product->save();
        if($product)
        {
            return response()->json(['status'=>200,'message'=>'Product Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }
    

    public function sales_product_list($type)
    {
        if($type=='all')
        {
            $product = product::all();
        }
        else if($type=="active")
        {
            $product = product::where('status',1)->get();
        }
        else
        {
            $product = product::where('status',0)->get();
        }
        return response()->json(['status'=>200,'message'=>'Product List','data'=>$product]);
    }

    public function update_state(Request $request,$id){
     $input = $request->all();
     $validator = Validator::make($input, [
        'name' => 'required',
        'short_name' => 'required',
        'country'=>'required'
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $state = State::findOrFail($id);
      $state->state_name = $request->name;
      $state->country_id = $request->country;
      $state->state_short_name = $request->short_name;
      $state->save();
      if($state){
         return response()->json(['status'=>200,'message'=>'State Updated Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }

}


    public function sales_product_change_status(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'status' => 'required|in:0,1', 
        ]);

        $product = product::findOrFail($request->id);
        $product->status = $request->status;
        $product->save();

        if($request->status ==0)
        {
            $msg = "Product Deactivated successfully";
        }
        else
        {
            $msg = "Product Activated successfully";
        }
        
        if($product)
        {
            return response()->json(['status' => 200, 'message' => $msg]);
        }
        else
        {
            return response()->json(['status' => 500, 'message' => 'something went wrong']);
        }
    }

    public function sales_product_status($id)
    {
        $product = product::findOrFail($id);
        if($product)
        {
            return response()->json(['status' => 200, 'message' => 'Product status','data'=>$product->status]);
        }
        else
        {
            return response()->json(['status' =>500, 'message' => 'data not found']);
        }
    }

    public function add_service_details(Request $request)
    {
    
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'new_service_name'=>'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }
        $service = new product_service();
        $service->product_id = $request->product_id;
        $service->service_name = $request->new_service_name;
        $service->created_by = $request->created_by;
        $service->save();
        if($service)
        {
            return response()->json(['status'=>200,'message'=>'Services Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    
    }
    
    public function sales_service_list($type)
    {
        if($type=='all')
        {
            $service = DB::connection('sales_db')->table('product_service')
            ->select('product.product_name','product_service.*')
            ->leftjoin('product','product.id','=','product_service.product_id')
            ->get();
           
        }
        else if($type=="active")
        {
            $service = product_service::where('status',1)->get();
        }
        else
        {
            $service = product_service::where('status',0)->get();
        }
       
        return response()->json(['status'=>200,'message'=>'Service List','data'=>$service]);
    }

    public function sales_service_status($id)
    {
        $service = product_service::findOrFail($id);
        if($service)
        {
            return response()->json(['status' => 200, 'message' => 'Service status','data'=>$service->status]);
        }
        else
        {
            return response()->json(['status' =>500, 'message' => 'data not found']);
        }
    }

    public function sales_service_change_status(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'status' => 'required|in:0,1', 
        ]);

        $service = product_service::findOrFail($request->id);
        $service->status = $request->status;
        $service->save();

        if($request->status ==0)
        {
            $msg = "Product Deactivated successfully";
        }
        else
        {
            $msg = "Product Activated successfully";
        }
        
        if($service)
        {
            return response()->json(['status' => 200, 'message' => $msg]);
        }
        else
        {
            return response()->json(['status' => 500, 'message' => 'something went wrong']);
        }
    }


    public function get_category_list()
    {
        $locality = DB::connection('sales_db')->table('product_category')
            ->select('product.product_name','product_service.service_name','product_category.*')
            ->leftjoin('product','product.id','=','product_category.product_id')
            ->leftjoin('product_service','product_service.id','=','product_category.service_id')
            ->get();
        return response()->json(['status'=>200,'message'=>'Locality details List','data'=>$locality]);
    }

    public function get_service_list_by_product_id($product_id)
    {
        $locality = DB::connection('sales_db')->table('product_service')
            ->select('product_service.id','service_name')
            ->where(array('product_id'=>$product_id,'status'=>1))
            ->get();
        return response()->json(['status'=>200,'message'=>'Locality details List','data'=>$locality]);
    }

    public function add_category_details(Request $request)
    {
    
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            'new_category_name'=>'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $category = new product_category();
        $category->product_id = $request->product_id;
        $category->service_id = $request->service_id;
        $category->category_name = $request->new_category_name;
        $category->created_by = $request->created_by;
        $category->save();
        if($category)
        {
            return response()->json(['status'=>200,'message'=>'Category Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'Something went wrong']);
        }
    
    }

    public function get_package_category_list($productId)
    {
        $packageCategory = DB::connection('sales_db')->table('package_category')
            ->select('package_category.*','product.product_name')
            ->leftJoin('product','product.id','=','package_category.product_id')
            ->where('package_category.product_id',$productId)
            ->where('package_category.status',1)
            ->get();
        return response()->json(['status'=>200,'message'=>'package category List','data'=>$packageCategory]);
    }

    public function get_package_category_listing()
    {
        $packageCategory = DB::connection('sales_db')->table('package_category')
            ->select('package_category.*','product.product_name')
            ->leftJoin('product','product.id','=','package_category.product_id')
            //->where('package_category.product_id',$productId)
            //->where('package_category.status',1)
            ->get();
        return response()->json(['status'=>200,'message'=>'package category List','data'=>$packageCategory]);
    }

    public function add_package_category_details(Request $request)
    {
    
        $input = $request->all();
        $validator = Validator::make($input, [
            'new_category_name'=>'required',
            'product_id'=>'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $saveCategory = array(
            'product_id'=>$request->product_id,
            'name'=>$request->new_category_name,
            'created_by'=>$request->created_by,
        );
        $packageCategoryId = DB::connection('sales_db')->table('package_category')->insertGetId($saveCategory);
        
        if($packageCategoryId)
        {
            return response()->json(['status'=>200,'message'=>'Package Category Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'Something went wrong']);
        }
    
    }

    public function add_package_category_type_details(Request $request)
    {
    
        $input = $request->all();
        $validator = Validator::make($input, [
            'new_category_name'=>'required',
            'created_by'=>'required',
            'service_details'=>'required',
            'amount'=>'required',
            'tax_amount'=>'required',
            'total_amount'=>'required',
            'product_id'=>'required',
            'package_type_id'=>'required',
            'service_id'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $saveCategory = array(
            'product_id'=>$request->product_id,
            'name'=>$request->new_category_name,
            'amount'=>$request->amount,
            'tax_amount'=>$request->tax_amount,
            'total_amount'=>$request->total_amount,
            'created_by'=>$request->created_by,
            'package_type_id'=>$request->package_type_id,
            'service_id'=>$request->service_id,
        );
        $packageCategoryId = DB::connection('sales_db')->table('package_category_type')->insertGetId($saveCategory);
        
        if($packageCategoryId)
        {
            $serviceDetails = json_decode($request->service_details);
            //return $serviceDetails;
            foreach($serviceDetails as $row)
            {
                $asignArray = array(
                    'product_id'=>$request->product_id,
                    'package_type_id'=>$request->package_type_id,
                    'service_id'=>$request->service_id,
                    'type_id'=>$packageCategoryId,
                    'cat_id'=>$row->id,
                    'type_status'=>$row->value,
                    'created_by'=>$request->created_by,
                );
                $assignId = DB::connection('sales_db')->table('package_category_type_assign')->insertGetId($asignArray);
            }
            if($assignId)
            {
                return response()->json(['status'=>200,'message'=>'Package Category Type Created Successfully']);
            }
            else
            {
                return response()->json(['status'=>500,'message'=>'Something went wrong in  assign category.']);
            }
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'Something went wrong']);
        }
    }

    public function get_package_category_type_list()
    {
        $packageCategory_details = DB::connection('sales_db')->table('package_category_type')
            ->select('package_category_type.*','product.product_name')
            ->selectRaw("GROUP_CONCAT(DISTINCT(pt.name)) as package_type")
            ->selectRaw("GROUP_CONCAT(DISTINCT(ps.service_name)) as service_name")
            ->leftJoin('product','product.id','=','package_category_type.product_id')

            ->leftJoin('package_type as pt',function ($join) {
                $join->whereRaw("FIND_IN_SET(pt.id ,package_category_type.package_type_id)");
            })

            ->leftJoin('product_service as ps',function ($join1)
            {
                $join1->whereRaw("FIND_IN_SET(ps.id,package_category_type.service_id)");
            })

            //->leftJoin('package_category_type_assign as pcta','pcta.type_id')
            ->where('package_category_type.status',1)
            ->groupBy('package_category_type.id')
            ->get();

            $packageCategory_respone = $packageCategory_details->map(function($item){
                $other_data = DB::connection('sales_db')->table('package_category_type_assign')
                ->select('package_category_type_assign.id','type_id','cat_id','pc.name','type_status')
                ->leftJoin('package_category as pc','pc.id','=','package_category_type_assign.cat_id')
                ->where(array('package_category_type_assign.product_id'=>$item->product_id,'package_category_type_assign.type_id'=>$item->id))
                ->get();

                $item->assign_cat = $other_data;
                return $item;
            });

            return response()->json(['status'=>200,'message'=>'package category type List','data'=>$packageCategory_respone]);
    }

    public function get_service_list_by_product_id_for_category($product_id)
    {
        $serviceDetails = DB::connection('sales_db')->table('product_service')
            ->select('id','service_name')
            ->where(array('product_id'=>$product_id,'status'=>1))
            ->get();
        return response()->json(['status'=>200,'message'=>'Locality details List','data'=>$serviceDetails]);
    }

    public function check_product_details($productName)
    {
        $productDetails = DB::connection('sales_db')->table('product')
        ->select('product_name')
        ->where('product_name','like', '%'.$productName.'%')
        ->first();
        if($productDetails)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function check_service_details($productId,$serviceName)
    {
        $serviceDetails = DB::connection('sales_db')->table('product_service')
        ->select('service_name')
        ->where('product_id',$productId)
        ->where('service_name','like', '%'.$serviceName.'%')
        ->first();
        if($serviceDetails)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function check_category_details($productId,$serviceId,$categoryName)
    {
        $categoryDetails = DB::connection('sales_db')->table('product_category')
        ->select('category_name')
        ->where('product_id',$productId)
        ->where('service_id',$serviceId)
        ->where('category_name','like', '%'.$categoryName.'%')
        ->first();
        if($categoryDetails)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function check_package_category_details($productId,$categoryName)
    {
        $categoryDetails = DB::connection('sales_db')->table('package_category')
        ->select('name')
        ->where('product_id',$productId)
        ->where('name','like', '%'.$categoryName.'%')
        ->first();
        if($categoryDetails)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function check_package_category_type_details($productId,$categoryName)
    {
        $packageCategoryDetails = DB::connection('sales_db')->table('package_category_type')
        ->select('name')
        ->where('product_id',$productId)
        ->where('name','like', '%'.$categoryName.'%')
        ->first();
        if($packageCategoryDetails)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function package_status_change($id)
    {
        $currentStatus = DB::connection('sales_db')->table('lead_base_package')
        ->select('status')
        ->where('id',$id)
        ->first();

        if($currentStatus->status == 1)
        {
            $status = 0;
        }
        else
        {
            $status = 1;
        }

        $updateStatus = array(
            'status'=>$status
        );
       
        $packageCategoryDetails = DB::connection('sales_db')->table('lead_base_package')
        ->where('id',$id)
        ->update($updateStatus);
    
        if($status ==0)
        {
            $msg = "Product Deactivated successfully";
        }
        else
        {
            $msg = "Product Activated successfully";
        }
        
        if($packageCategoryDetails)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function check_leadbased_location_price($productid,$serviceid,$groupid)
    {
        $checkLocation = DB::connection('sales_db')->table('lead_base_location_condition')
        ->select('id')
        ->where(array('product_id'=>$productid,'service_id'=>$serviceid,'include_group'=>$groupid))
        ->first();
        if($checkLocation)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function get_leadbased_location_factor()
    {
        $details = DB::connection('sales_db')->table('lead_base_location_condition as lblc')
        ->select('lblc.id','lblc.id','lblc.product_id','lblc.service_id','lblc.include_group','lblc.condition_value as location_price','lblc.created_by','product.product_name',
        'product_service.service_name','group_names.name as group_name','lblc.status')         
        ->selectRaw("GROUP_CONCAT(DISTINCT(locality.locality_name)) as city_name")
        ->leftJoin('product','product.id','=','lblc.product_id')
        ->leftJoin('product_service','product_service.id','=','lblc.service_id')
        ->leftJoin('group_names','group_names.group_id','=','lblc.include_group')
        ->leftJoin('locality',function ($join1)
        {
            $join1->whereRaw("FIND_IN_SET(locality.locality_id,lblc.requried_childs)");
        })
        ->groupBy('id')
        ->get();
        return response()->json(['status'=>200,'message'=>'Lead based location factor details','data'=>$details]);
    }

    public function leadbase_factor_package_status_change($id)
    {
        $currentStatus = DB::connection('sales_db')->table('lead_base_location_condition')
        ->select('status')
        ->where('id',$id)
        ->first();

        if($currentStatus->status == 1)
        {
            $status = 0;
        }
        else
        {
            $status = 1;
        }

        $updateStatus = array(
            'status'=>$status
        );
       
        $leadbaseFactorDetails = DB::connection('sales_db')->table('lead_base_location_condition')
        ->where('id',$id)
        ->update($updateStatus);
    
        if($status ==0)
        {
            $msg = "Leadbase location factor deactivated successfully";
        }
        else
        {
            $msg = "Leadbase location factor activated successfully";
        }
        
        if($leadbaseFactorDetails)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function check_leadbased_location_price_by_id($productid,$serviceid,$groupid,$id)
    {
        $checkLocation = DB::connection('sales_db')->table('lead_base_location_condition')
        ->select('id')
        ->where('id','!=',$id)
        ->where(array('product_id'=>$productid,'service_id'=>$serviceid,'include_group'=>$groupid))
        ->first();
        if($checkLocation)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function unlimited_package_status_change($id)
    {
        $currentStatus = DB::connection('sales_db')->table('unlimited_package')
        ->select('status')
        ->where('id',$id)
        ->first();

        if($currentStatus->status == 1)
        {
            $status = 0;
        }
        else
        {
            $status = 1;
        }

        $updateStatus = array(
            'status'=>$status
        );
       
        $packageCategoryDetails = DB::connection('sales_db')->table('unlimited_package')
        ->where('id',$id)
        ->update($updateStatus);
    
        if($status ==0)
        {
            $msg = "Product Deactivated successfully";
        }
        else
        {
            $msg = "Product Activated successfully";
        }
        
        if($packageCategoryDetails)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function check_unlimited_location_price($productid,$serviceid,$groupid)
    {
        $checkLocation = DB::connection('sales_db')->table('unlimited_location_condition')
        ->select('id')
        ->where(array('product_id'=>$productid,'service_id'=>$serviceid,'include_group'=>$groupid))
        ->first();
        if($checkLocation)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function check_unlimited_location_price_by_id($productid,$serviceid,$groupid,$id)
    {
        $checkLocation = DB::connection('sales_db')->table('unlimited_location_condition')
        ->select('id')
        ->where('id','!=',$id)
        ->where(array('product_id'=>$productid,'service_id'=>$serviceid,'include_group'=>$groupid))
        ->first();
        if($checkLocation)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function get_unlimited_location_factor()
    {
        $details = DB::connection('sales_db')->table('unlimited_location_condition as ulc')
        ->select('ulc.id','ulc.id','ulc.product_id','ulc.service_id','ulc.include_group','ulc.condition_value as location_price','ulc.created_by','product.product_name',
        'product_service.service_name','group_names.name as group_name','ulc.status')         
        ->selectRaw("GROUP_CONCAT(DISTINCT(locality.locality_name)) as city_name")
        ->leftJoin('product','product.id','=','ulc.product_id')
        ->leftJoin('product_service','product_service.id','=','ulc.service_id')
        ->leftJoin('group_names','group_names.group_id','=','ulc.include_group')
        ->leftJoin('locality',function ($join1)
        {
            $join1->whereRaw("Find_in_set(locality.locality_id,ulc.requried_childs)");
        })
        ->groupBy('id')
        ->get();
        return response()->json(['status'=>200,'message'=>'Unlimited location factor details','data'=>$details]);
    }

    public function unlimited_factor_package_status_change($id)
    {
        $currentStatus = DB::connection('sales_db')->table('unlimited_location_condition')
        ->select('status')
        ->where('id',$id)
        ->first();

        if($currentStatus->status == 1)
        {
            $status = 0;
        }
        else
        {
            $status = 1;
        }

        $updateStatus = array(
            'status'=>$status
        );
       
        $leadbaseFactorDetails = DB::connection('sales_db')->table('unlimited_location_condition')
        ->where('id',$id)
        ->update($updateStatus);
    
        if($status ==0)
        {
            $msg = "Unlimited location factor deactivated successfully";
        }
        else
        {
            $msg = "Unlimited location factor activated successfully";
        }
        
        if($leadbaseFactorDetails)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function get_source_from()
    {
        $details = DB::connection('sales_db')->table('source_from')
        ->where('status',1)
        ->get();
        return response()->json(['status'=>200,'message'=>'Source from details ','data'=>$details]);
    }

    public function get_enq_status()
    {
        $details = DB::connection('sales_db')->table('return_lead_reasons')
        ->where(array('status'=>1,'use_for'=>0))
        ->get();
        return response()->json(['status'=>200,'message'=>'Source from details ','data'=>$details]);
    }

}
