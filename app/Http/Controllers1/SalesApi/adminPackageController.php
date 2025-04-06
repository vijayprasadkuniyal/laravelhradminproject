<?php
namespace App\Http\Controllers\SalesApi;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\adminSales\package;
use App\Models\commonModel\location;
use App\Models\commonModel\locality;
use App\Models\adminSales\location_fact;
use App\Models\adminSales\locality_fact;
use App\Models\adminSales\days_fact;
use App\Models\adminSales\lead_fact;
use App\Models\adminSales\category_fact;
use App\Models\adminSales\product;
use App\Models\adminSales\product_service;

use App\Models\adminSales\LeadBaseLocationModel;
use App\Models\adminSales\LeadBaseServiceModel;

use App\Models\adminSales\UnlimitedLocationModel;
use App\Models\adminSales\UnlimitedServiceModel;
use Validator;

class adminPackageController extends Controller
{
    public function get_duration_list($type)
    {
        if($type=='all')
        {
            //$duration = package::all();
            $duration = package::where('fact_type',1)->get();
        }
        else if($type=="active")
        {
            $duration = package::where(array('status'=>1,'fact_type'=>1))->get();
        }
        else
        {
            $duration = package::where(array('status'=>0,'fact_type'=>1))->get();
        }
        return response()->json(['status'=>200,'message'=>'Duration List','data'=>$duration]);
    }

    public function get_package_type_list($type)
    {
        if($type=='all')
        {
            //$duration = package::all();
            $duration = package::where('fact_type',2)->get();
        }
        else if($type=="active")
        {
            $duration = package::where(array('status'=>1,'fact_type'=>2))->get();
        }
        else
        {
            $duration = package::where(array('status'=>0,'fact_type'=>2))->get();
            
        }
        return response()->json(['status'=>200,'message'=>'Duration List','data'=>$duration]);
    }

    public function get_location_list()
    {
        $location = location::all();
        return response()->json(['status'=>200,'message'=>'Location List','data'=>$location]);
    }

    public function get_location_fact_list()
    {
        $location = location_fact::leftjoin('group_names','group_names.group_id','=','location_fact.location_id')
        ->select('group_names.name','location_fact.*')->get();
       return response()->json(['status'=>200,'message'=>'Location factor List','data'=>$location]);
    }

    public function get_locality_fact_list()
    {
        $locality = DB::connection('sales_db')->table('locality_fact')
            ->select('group_names.name','locality.locality_name','locality_fact.*')
            ->leftjoin('group_names','group_names.group_id','=','locality_fact.location_id')
            ->leftjoin('locality','locality.locality_id','=','locality_fact.locality_id')
            ->get();
        return response()->json(['status'=>200,'message'=>'Locality Factor List','data'=>$locality]);
    }

    public function get_locality_list($location_id)
    {
        $locality = DB::connection('sales_db')->table('locality')
            ->select('locality_id as value','locality_name as label')
             ->where(array('status'=>1,'location_id'=>$location_id))
            ->get();
        return response()->json(['status'=>200,'message'=>'Locality List','data'=>$locality]);
    }

    public function get_to_location_fact_list()
    {
        $toLocation = DB::connection('sales_db')->table('to_location_fact')
             ->where(array('status'=>1))
            ->get();
        return response()->json(['status'=>200,'message'=>'To Location List','data'=>$toLocation]);
    }

    

    public function add_location_factor(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'location_id' => 'required',
            'price' => 'required',
            'created_by'=>'required'
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }
        $location = new location_fact();
        // 1 = Duration
        $location->location_id = $request->location_id;
        $location->price = $request->price;
        $location->created_by = $request->created_by;

        $location->save();
        if($location)
        {
            return response()->json(['status'=>200,'message'=>'Location factor created successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }

    
    public function add_locality_factor(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'location_id' => 'required',
            'locality_id'=>'required',
            'price' => 'required',
            'created_by'=>'required'
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }
        $location = new locality_fact();
        // 1 = Duration
        $location->location_id = $request->location_id;
        $location->locality_id = $request->locality_id;
        $location->price = $request->price;
        $location->created_by = $request->created_by;

        $location->save();
        if($location)
        {
            return response()->json(['status'=>200,'message'=>'Locality factor created successfully']);
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

    public function update_state(Request $request,$id)
    {
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
            $service = product_service::all();
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

    public function get_duration_type_fact_list($fact_type)
    {
        $durationFact = package::where(array('status'=>1,'fact_type'=>$fact_type))->get();
        if($durationFact)
        {
            return response()->json(['status'=>200,'message'=>'Duration and package Type List','data'=>$durationFact]);
        }
        else
        {
            return response()->json(['status' => 500, 'message' => 'Record not found']);
        }
    }

    public function get_location_wise_locality_fact($location_id)
    {
        $locationId = explode(',',$location_id);
        $localityArray = array();
        $location = DB::connection('sales_db')->table('group_names')->whereIn('group_id',$locationId)->get(['group_id','name']);
        foreach($location as $loc)
        {
            $locality = DB::connection('sales_db')->table('locality')
            ->where(array('status'=>1,'location_id'=>$loc->group_id))
            ->get(['locality_name','locality_id']); 
           $localityArray[] = array(
            'loc_name'=>$loc->name,
            'loc_id'=>$loc->group_id,
            'locality'=>$locality,
            );
        }

        
        return response()->json(['status'=>200,'message'=>'Locality List','data'=>$localityArray]);
    }

    public function get_day_fact_list()
    {
        $dayFact = DB::connection('sales_db')->table('days_factor')
             ->where(array('status'=>1))
            ->get();
        return response()->json(['status'=>200,'message'=>'Days factor','data'=>$dayFact]);
    }

    public function add_days_factor(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'range_from' => 'required',
            'range_to'=>'required',
            'per_day_lead'=>'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }
        $days = new days_fact();
        $days->range_from = $request->range_from;
        $days->range_to = $request->range_to;
        $days->lead_per_day = $request->per_day_lead;
        $days->price = $request->price;
        $days->created_by = $request->created_by;
        $days->save();
        
        if($days)
        {
            return response()->json(['status'=>200,'message'=>'Days Factor Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    
    }

    public function get_lead_fact_list()
    {
        $leadFact = DB::connection('sales_db')->table('lead_factor')
            // ->where(array('status'=>1))
            ->get();
        return response()->json(['status'=>200,'message'=>'Lead factor','data'=>$leadFact]);
    }

    public function add_lead_factor(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'leads' => 'required',
            'lead_extra'=>'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }
        $lead = new lead_fact();
        $lead->leads = $request->leads;
        $lead->extra_lead = $request->lead_extra;
        $lead->created_by = $request->created_by;
        $lead->save();
        
        if($lead)
        {
            return response()->json(['status'=>200,'message'=>'Lead Factor Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    
    }

    public function get_category_fact_list()
    {
        $categoryFact = DB::connection('sales_db')->table('category_fact')
        ->select('product.product_name','product_service.service_name','product_category.category_name','category_fact.*')
        ->leftjoin('product','product.id','=','category_fact.product_id')
        ->leftjoin('product_service','product_service.id','=','category_fact.service_id')
        ->leftjoin('product_category','product_category.id','=','category_fact.category_id')
        //->where(array('product_category.statue'=>1,'product_category.service_id'=>$service_id))
        ->get();
        return response()->json(['status'=>200,'message'=>'Category Factor List','data'=>$categoryFact]);
    }

    public function  get_category_list_by_service_id($service_id)
    {
        $categoryList = DB::connection('sales_db')->table('product_category')
            ->where(array('status'=>1,'service_id'=>$service_id))
            ->get();
        return response()->json(['status'=>200,'message'=>'Category List','data'=>$categoryList]);
    }

    public function add_category_factor_details(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id'=>'required',
            'category_id'=>'required',
            'base_price'=>'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }
        $lead = new category_fact();
        $lead->product_id = $request->product_id;
        $lead->service_id = $request->service_id;
        $lead->category_id = $request->category_id;
        $lead->base_price = $request->base_price;
        $lead->created_by = $request->created_by;
        $lead->save();
        
        if($lead)
        {
            return response()->json(['status'=>200,'message'=>'Category Factor Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }

    public function get_product_list()
    {
        $product = product::where('status',1)->get();
        return response()->json(['status'=>200,'message'=>'Product List','data'=>$product]);        
    }

    public function get_service_list($productId)
    {
        $service = product_service::where(array('status'=>1,'product_id'=>$productId))->get();
        return response()->json(['status'=>200,'message'=>'Product List','data'=>$service]);        
    }

    public function check_package_price_details(Request $request)
    {
        //$input = $request->all();
        // $validator = Validator::make($input, [
        //     'product_id' => 'required',
        //     'service_id'=>'required',
        //     'category_id'=>'required',
        //     'duration_id' => 'required',
        //     'is_to_location'=>'required',
        //     'package_type_id'=>'required',
        //     'location_id' => 'required',
        //     ]);
            
            // if($validator->fails())
            // {
            //     $messages=$validator->messages();
            //     return response()->json(["messages"=>$messages,'status'=>400]);     
            // }'
        $productId = $request->product_id;
        $serviceId = $request->service_id;
        $categoryId = $request->category_id;
        $durationId = $request->duration_id;
        $isToLocation = $request->is_to_location;
        $packageTypeId = $request->package_type_id;
        $locationId = $request->location_id;
        $noOfLead = $request->noOfLead;
        
        $categoryArray=array();

        $categoryFactPrice = DB::connection('sales_db')->table('category_fact')
                ->select('product_category.category_name','category_fact.base_price','category_fact.id')
                ->leftjoin('product_category','product_category.id','=','category_fact.category_id')
                ->where(array('category_fact.category_id'=>$categoryId))
                ->first();

        $categoryPrice = $categoryFactPrice->base_price * $noOfLead;
        $categoryFactPrice =array(
            'categoryName'=>$categoryFactPrice->category_name,
            'categoryPrice'=>$categoryPrice,
        );
       

        $durationFactPrice = DB::connection('sales_db')->table('dure_and_pkg_type_fact')
                ->select('name','fact_price')
                ->where(array('dure_and_pkg_type_fact.id'=>$durationId))
                ->first();
        $durationPrice = $durationFactPrice->fact_price * $noOfLead;
        $durationFactPrice =array(
            'durationName'=>$durationFactPrice->name,
            'durationPrice'=>$durationPrice,
        );


        $toLocationFactPrice = DB::connection('sales_db')->table('to_location_fact')
                ->select('price')
                ->where('status',1)
                ->first();
        $toLocationPrice = $toLocationFactPrice->price * $noOfLead;
        
        $toLocationFactDetails =array(
            'isToLocation'=>'Is to Location',
            'toLocationPrice'=>$toLocationPrice,
        );

        $typeFactPrice = DB::connection('sales_db')->table('dure_and_pkg_type_fact')
                ->select('name','fact_price')
                ->where(array('dure_and_pkg_type_fact.id'=>$packageTypeId))
                ->first();
        $typePrice = $typeFactPrice->fact_price * $noOfLead;
        $typeFactDetails =array(
            'typeName'=>$typeFactPrice->name,
            'typePrice'=>$typePrice,
        );

        
        $totalPrice = $categoryPrice + $durationPrice + $toLocationPrice + $typePrice;

        $array = array(
            "categoryDetails"=>$categoryFactPrice,
            "durationDetails"=>$durationFactPrice,
            "toLocationFactDetails"=>$toLocationFactDetails,
            "typeFactDetails"=>$typeFactDetails,
            "totalPrice"=>$totalPrice,
        );
        return response()->json(['status'=>200,'message'=>'Package List','data'=>$array]);
    }

    public function add_lead_package_factor(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            //'category_id' => 'required',
            'base_price' => 'required',
            'lead_devide_percent' => 'required',
            'single_cat_price_inc' => 'required',
            'minimum_lead_count' => 'required',
            'single_cat_min_lead_count' => 'required',
            'drop_after_lead_count' => 'required',
            'drop_lead_rate_between' => 'required',
            'drop_rate_price' => 'required',
            'maximum_lead_drop' => 'required',
            'to_location_price' => 'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $leadFactor = array(
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            'category_id'=>$request->category_id,
            'base_price'=>$request->base_price,
            'lead_devide_percent'=>$request->lead_devide_percent,
            'for_single_cat_price_inc'=>$request->single_cat_price_inc,
            'min_lead_count'=>$request->minimum_lead_count,
            'single_cat_lead_min_count'=>$request->single_cat_min_lead_count,
            'drop_price_after_lead'=>$request->drop_after_lead_count,
            'drop_rate_between'=>$request->drop_lead_rate_between,
            'drop_rate_price'=>$request->drop_rate_price,
            'max_lead_drop'=>$request->maximum_lead_drop,
            'to_location_price'=>$request->to_location_price,
            'created_by'=>$request->created_by,

        );
        $leadFactorId = DB::connection('sales_db')->table('lead_base_package')->insertGetId($leadFactor);
        if($leadFactorId)
        {
            return response()->json(['status'=>200,'message'=>'Lead Bas Package Factor Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }


    public function add_unlimited_package_factor(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            //'category_id' => 'required',
            'base_price' => 'required',
            'lead_devide_percent' => 'required',
            'single_cat_price_inc' => 'required',
            'min_days_count' => 'required',
            'single_cat_min_days_count' => 'required',
            'drop_after_lead_count' => 'required',
            'drop_lead_rate_between' => 'required',
            'drop_rate_price' => 'required',
            'maximum_lead_drop' => 'required',
            'to_location_price' => 'required',
            'per_day_lead' => 'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $unlimitedFactor = array(
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            'category_id'=>$request->category_id,
            'base_price'=>$request->base_price,
            'lead_devide_percent'=>$request->lead_devide_percent,
            'for_single_cat_price_inc'=>$request->single_cat_price_inc,
            'min_days_count'=>$request->min_days_count,
            'single_cat_days_min_count'=>$request->single_cat_min_days_count,
            'drop_price_after_lead'=>$request->drop_after_lead_count,
            'drop_rate_between'=>$request->drop_lead_rate_between,
            'drop_rate_price'=>$request->drop_rate_price,
            'max_lead_drop'=>$request->maximum_lead_drop,
            'to_location_price'=>$request->to_location_price,
            'per_day_lead'=>$request->per_day_lead,
            'created_by'=>$request->created_by,
        );
        $leadFactorId = DB::connection('sales_db')->table('unlimited_package')->insertGetId($unlimitedFactor);
        if($leadFactorId)
        {
            return response()->json(['status'=>200,'message'=>'Unlimited Package Factor Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }

    public function get_package_type()
    {
        $packageType = DB::connection('sales_db')->table('package_type')
            ->where(array('status'=>1))
            ->get();
        return response()->json(['status'=>200,'message'=>'Package Type List','data'=>$packageType]); 
    }

    public function get_group_list()
    {
        $groupList = DB::connection('sales_db')->table('group_names')
            ->select('group_id','name')
            ->where(array('status'=>1))
            ->get();
        return response()->json(['status'=>200,'message'=>'Group List','data'=>$groupList]);
    }

    public function add_lead_location_price(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            'category_id' => 'required',
            'group_id' => 'required',
            'locality_id' => 'required',
            'location_price' => 'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

       // $localityIds=implode(",",$request->locality_id,"value");

        $leadLocationPrice = array(
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            'category_for'=>$request->category_id,
            'include_group'=>$request->group_id,
            'requried_childs'=>$request->locality_id,
            'condition_value'=>$request->location_price,
            'created_by'=>$request->created_by,
        );
        $leadLocationPrice = DB::connection('sales_db')->table('lead_base_location_condition')->insertGetId($leadLocationPrice);
        if($leadLocationPrice)
        {
            return response()->json(['status'=>200,'message'=>'Lead Based Location Price Created Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }

    public function add_unlimited_location_price(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            'category_id' => 'required',
            'group_id' => 'required',
            'locality_id' => 'required',
            'location_price' => 'required',
            'created_by'=>'required',
            ]);
            if($validator->fails())
            {
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
            }

            $unlimitedLocationPrice = array(
                'product_id'=>$request->product_id,
                'service_id'=>$request->service_id,
                'category_for'=>$request->category_id,
                'include_group'=>$request->group_id,
                'requried_childs'=>$request->locality_id,
                'condition_value'=>$request->location_price,
                'created_by'=>$request->created_by,
            );
        $unlimitedLocationPrice = DB::connection('sales_db')->table('unlimited_location_condition')->insertGetId($unlimitedLocationPrice);
        if($unlimitedLocationPrice)
        {
            return response()->json(['status'=>200,'message'=>'Unlimited Location Price Created Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }

    public function get_leadbased_packages_list()
    {
        $leadbasedPackage = DB::connection('sales_db')->table('lead_base_package')
            ->select('lead_base_package.*','product.product_name','product_service.service_name','product_category.category_name')
            ->leftJoin('product','product.id','=','lead_base_package.product_id')
            ->leftJoin('product_service','product_service.id','=','lead_base_package.service_id')
            ->leftJoin('product_category','product_category.id','=','lead_base_package.category_id')
            ->where(array('lead_base_package.status'=>1))
            ->get();
        return response()->json(['status'=>200,'message'=>'Lead Based Package details','data'=>$leadbasedPackage]); 
    }

    public function get_leadbased_packages_details($product_id,$service_id)
    {
        $details = DB::connection('sales_db')->table('lead_base_package')
        ->select('lead_base_package.*')
        ->where(array('product_id'=>$product_id,'service_id'=>$service_id))
        ->first();
        return response()->json(['status'=>200,'message'=>'Lead Based Package details','data'=>$details]); 
    }

    public function get_package_duration()
    {
        $packageDuration = DB::connection('sales_db')->table('package_duration')
        ->where(array('status'=>1))
        ->get();
        return response()->json(['status'=>200,'message'=>'Package Duration','data'=>$packageDuration]); 
    }

    public function get_package_category_by_product_id($product_id)
    {
        $packageCategory = DB::connection('sales_db')->table('package_category')
        ->where(array('status'=>1,'product_id'=>$product_id))
        ->get();
        return response()->json(['status'=>200,'message'=>'Package Category List','data'=>$packageCategory]); 
    }

    public function get_package_category_type_by_category_id($product_id,$category_id)
    {
        $packageCategoryType = DB::connection('sales_db')->table('package_category_type_assign as pcta')
        ->select('pct.name','pct.amount','pct.tax_amount','pct.total_amount','pcta.type_status','pcta.id')
        ->leftJoin('package_category_type as pct','pct.id','=','pcta.type_id')
        //->whereIn(array('pcta.package_type_id'=>$package_type))
        //,'pcta.service_id'=>$service_id
        ->where(array('pcta.status'=>1,'pcta.product_id'=>$product_id,'pcta.cat_id'=>$category_id))
        ->get();
        return response()->json(['status'=>200,'message'=>'Package category type list','data'=>$packageCategoryType]); 
    }

    public function check_package_price(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'services' => 'required',
            'group_id' => 'required',
            'package_type' => 'required'
        ]);
        if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);
        }
        
        $return = array();
        $group_id = $request->post('group_id');
        $package_type = $request->post('package_type');

        $to_location = $request->post('to_location');
        $package_duration = $request->post('package_duration');
        $services = explode(",",$request->post('services'));
        $total_leads = $request->post('total_leads');
        if($package_duration == "1"){
            $service_values = LeadBaseServiceModel::whereIn("service_id",$services)->get();
        }else if($package_duration == "2"){
            $service_values = UnlimitedServiceModel::whereIn("service_id",$services)->get();
        }

        $estimate_total_leads = 0;
        $single_estimate_total_leads = 0;
        $count = 0;
        $total_percentage = 0;
        $to_location_price = 0;

        foreach($service_values as $service_value)
        {
            $estimate_total_leads += $service_value['min_lead_count'];
            $single_estimate_total_leads += $service_value['single_cat_lead_min_count'];
            $total_percentage += $service_value['lead_devide_percent'];
            $to_location_price += $service_value['to_location_price'];
            $count++;
        }

        $to_location_price = $to_location_price / $count;

        if($count < 2){
            $estimate_total_leads = $single_estimate_total_leads;
        }
        if($total_percentage < 100){
            $estimate_total_leads = $single_estimate_total_leads;
        }

        $estimate_total_leads = round($estimate_total_leads / $count);


        if($total_leads < $estimate_total_leads){
            $total_leads = $estimate_total_leads;
        }

        $include_child_array = array();

        $percentage_count = $this->get_percentage_count($total_leads,$service_values);

        foreach($service_values as $service_value){
            if($count > 1){
                if($total_percentage < 100){
                    //$return[$service_value['service_id']]['total_lead'] = round($total_leads / $count);
                    $return[$service_value['service_id']]['total_lead'] = $percentage_count[$service_value['service_id']];
                    if($service_value['increment_type'] == 0){
                        // for Direct Price... 
                        $return[$service_value['service_id']]['price'] = $service_value['base_price'] + $service_value['for_single_cat_price_inc'];
                    }else{ 
                        // for persentage... 
                    }
                }else{
                    $return[$service_value['service_id']]['total_lead'] = $percentage_count[$service_value['service_id']];
                    if($service_value['increment_type'] == 0){
                        // for Direct Price... 
                        $return[$service_value['service_id']]['price'] = $service_value['base_price'];
                    }else{ 
                        // for persentage... 
                    }
                }
            }else{
                $return[$service_value['service_id']]['total_lead'] = $total_leads;
                if($service_value['increment_type'] == 0){
                    // for Direct Price... 
                    $return[$service_value['service_id']]['price'] = $service_value['base_price'] + $service_value['for_single_cat_price_inc'];
                }else{ 
                    // for persentage... 
                }
            }

            if($to_location == "yes"){
                $return[$service_value['service_id']]['price'] = $return[$service_value['service_id']]['price'] + $service_value['to_location_price'];
            }

            $location_details = LeadBaseLocationModel::where("package_type",$package_type)->where("include_group",$group_id)->where("category_for",$service_value['service_id'])->first();
            if($location_details){
                $return[$service_value['service_id']]['price'] = $return[$service_value['service_id']]['price'] + $location_details->condition_value;
                $include_child_array = array_merge($include_child_array,explode(",",$location_details->requried_childs));
            }

            $drop_price = 0;
            $max_drop_rate = round((($service_value['max_lead_drop'] - $service_value['drop_price_after_lead']) / $service_value['drop_rate_between']) * $service_value['drop_rate_price']);

            $drop_rate_check = round(($percentage_count[$service_value['service_id']] - $service_value['drop_price_after_lead']) / $service_value['drop_rate_between']);
            if($drop_rate_check > 0){
                $drop_price = $drop_rate_check * $service_value['drop_rate_price'];
                if($max_drop_rate < $drop_price){
                    $drop_price = $max_drop_rate;
                }
            }
            
            $return[$service_value['service_id']]['price'] = $return[$service_value['service_id']]['price'] - $drop_price;

            $return[$service_value['service_id']]['to_location_price'] = $service_value['to_location_price'];
            $return[$service_value['service_id']]['service_id'] = $service_value['service_id'];

        }
        $final_lead_total = 0;
        $final_total_price = 0;
        foreach($return as $final_values){
            $final_lead_total += $final_values['total_lead'];
            $final_total_price += $final_values['price']*$final_values['total_lead'];
        }    
        
        return response()->json(["messages"=>"",'status'=>200,"data"=>$return,"total_leads"=>$final_lead_total,"total_price"=>$final_total_price,"include_locality"=>$include_child_array]);
    }

    public static function get_percentage_count($total,$services)
    {
        $return = array();
        $count = 0;
        $total_percent = 0;
        foreach($services as $service_value){
            $total_percent += $service_value['lead_devide_percent'];
            $count++;
        }
        foreach($services as $service_value){
            $return[$service_value['service_id']] = round(($total * $service_value['lead_devide_percent']) / $total_percent);
        }
        return $return;
    }

    public function create_pre_package(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            'group_id' => 'required',
            'package_type' => 'required',
            'to_location' => 'required',
            'package_duration' => 'required',
            'service_details' => 'required',
            'package_category' => 'required',
            'total_leads' => 'required',
            'package_price' => 'required',
            'created_by'=>'required',
            ]);

            
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        // $localityIds=implode(",",$request->locality_id,"value");

        $pacageDetails = array(
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            'group_id'=>$request->group_id,
            'city_id'=>$request->city_id,
            'package_type'=>$request->package_type,
            'to_location'=>$request->to_location,
            'package_duration'=>$request->package_duration,
            'package_category'=>$request->package_category,
            'total_lead'=>$request->total_leads,
            'package_price'=>$request->package_price,
            'created_by'=>$request->created_by,
        );
        //return $request->service_details;

        $package_id = DB::connection('sales_db')->table('pre_package')->insertGetId($pacageDetails);
        $serviceDetailsJson = json_decode($request->service_details);
        if($package_id)
        {
            foreach($serviceDetailsJson as $serviceDetails)
            {
                $serviceArray = array(
                    'pre_package_id'=>$package_id,
                    'service_id'=>$serviceDetails->service_id,
                    'total_lead'=>$serviceDetails->total_lead,
                    'total_price'=>$serviceDetails->price,
                    'created_by'=>$request->created_by,
                );
                $serviceId = DB::connection('sales_db')->table('pre_package_service')->insertGetId($serviceArray);
            } 

            return response()->json(['status'=>200,'message'=>'Package Created Successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }

    public function get_pre_packages_list()
    {
        $prePackages = DB::connection('sales_db')->table('pre_package')
        ->select('pre_package.id','pre_package.package_status as status','pre_package.to_location','pre_package.total_lead','pre_package.package_price','pre_package.created_by','product.product_name','group_names.name as group_name','package_duration.name as duration_name','package_type.name as package_type_name','package_category.name as category_name')
        ->selectRaw("GROUP_CONCAT(DISTINCT(ps.service_name)) as service_name_list")
        ->selectRaw("GROUP_CONCAT(DISTINCT(locality.locality_name)) as city_name")
        ->leftJoin('product','product.id','=','pre_package.product_id')
        ->leftJoin('group_names','group_names.group_id','=','pre_package.group_id')
        ->leftJoin('package_duration','package_duration.id','=','pre_package.package_duration')
        ->leftJoin('package_type','package_type.id','=','pre_package.package_type')
        ->leftJoin('package_category','package_category.id','=','pre_package.package_category')
        ->leftJoin('product_service as ps',function ($join1)
        {
            $join1->whereRaw("FIND_IN_SET(ps.id,pre_package.service_id)");
        })
        ->leftJoin('locality',function ($join2)
        {
            $join2->whereRaw("FIND_IN_SET(locality.locality_id,pre_package.city_id)");
        })
        ->groupBy('pre_package.id')
        ->get();
        return response()->json(['status'=>200,'message'=>'Pre Packages details','data'=>$prePackages]); 
    }

    public function get_lmart_pre_packages_list()
    {
        $prePackages = DB::connection('sales_db')->table('pre_package')
        ->select('pre_package.id','pre_package.package_status as status','pre_package.to_location','pre_package.total_lead','pre_package.package_price','pre_package.created_by','pre_package.product_id','product.product_name','group_names.name as group_name','package_duration.name as duration_name','package_type.name as package_type_name','package_category.name as category_name','package_category.id as category_id')
        ->selectRaw("GROUP_CONCAT(DISTINCT(ps.service_name)) as service_name_list")
        ->selectRaw("GROUP_CONCAT(DISTINCT(locality.locality_name)) as city_name")
        ->leftJoin('product','product.id','=','pre_package.product_id')
        ->leftJoin('group_names','group_names.group_id','=','pre_package.group_id')
        ->leftJoin('package_duration','package_duration.id','=','pre_package.package_duration')
        ->leftJoin('package_type','package_type.id','=','pre_package.package_type')
        ->leftJoin('package_category','package_category.id','=','pre_package.package_category')
        ->leftJoin('product_service as ps',function ($join1)
        {
            $join1->whereRaw("FIND_IN_SET(ps.id,pre_package.service_id)");
        })
        ->leftJoin('locality',function ($join2)
        {
            $join2->whereRaw("FIND_IN_SET(locality.locality_id,pre_package.city_id)");
        })
        ->groupBy('pre_package.id')
        ->get();

        $packageCategory_respone = $prePackages->map(function($item){
            $packageCategoryType = DB::connection('sales_db')->table('package_category_type_assign as pcta')
            ->select('pct.name','pct.amount','pct.tax_amount','pct.total_amount','pcta.type_status','pcta.id')
            ->leftJoin('package_category_type as pct','pct.id','=','pcta.type_id')
            ->where(array('pcta.status'=>1,'pcta.product_id'=>$item->product_id,'pcta.cat_id'=>$item->category_id))
            ->get();

            $item->assign_cat = $packageCategoryType;
            return $item;
        });
        return response()->json(['status'=>200,'message'=>'Pre Packages details','data'=>$prePackages]);
    }

    public function check_client_wallet_balance(Request $request)
    {
        $client_details = DB::connection('sales_db')->table('wallet_info')->where('client_id',$request->client_id)->first();
        if($client_details)
        {
            $blance_amount = $client_details->balance_amount;
            $package_price = $request->price/2;
            //return $blance_amount;
            if($package_price <= $blance_amount)
            {
                $package_details = array(
                    'price'=>$request->price,
                    'total_lead'=>$request->total_lead,
                    'package_name'=>$request->name,
                    'package_id'=>$request->id,
                    'category_name'=>$request->category_name,
                    'service_name'=>$request->service_name,
                    'duration_name'=>$request->duration_name,
                    'balance_amount'=>$request->blance_amount,
                );
                return response()->json(['status'=>200,'data'=>$package_details]);
            }
            else
            {
                return response()->json(['status'=>500,'message'=>'You Dont Have Sufficent Blance']);
            }
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'client not found']);
        }
    }

    public function buy_new_package(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'client_id' => 'required',
            'package_id' => 'required',
            'depositAmount' => 'required',
            'created_by'=>'required',
            ]);
        
            if($validator->fails())
            {
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);
            }

            $wallet_info = DB::connection('sales_db')->table('wallet_info')->where('client_id',$request->client_id)->first();

            if($wallet_info->balance_amount <= $request->depositAmount)
            {
                return response()->json(["messages"=>'Insufficient Balance','status'=>201]);
            }
            else
            {
                $packageInfo = DB::connection('sales_db')->table('pre_package')->where('id',$request->package_id)->first();

                $package_info = array(
                    'product_id'=>$packageInfo->product_id,
                    'pre_package_id'=>$packageInfo->id,
                    'package_duration'=>$packageInfo->package_duration,
                    'package_name'=>'Moving Package',
                    'service_id'=>$packageInfo->service_id,
                    'package_amount'=>$packageInfo->package_price,
                    'tax_amount'=>$packageInfo->package_price,
                    'paid_amount'=>$packageInfo->package_price,
                    'due_amount'=>$packageInfo->package_price-$request->depositAmount,
                    'due_date'=>'',
                    'due_lead'=>0,
                    'due_status'=>0,
                    'package_status'=>0,
                    'admin_status'=>0,
                    'finance_status'=>0,
                    'sales_status'=>1,
                    'package_start_date'=>date('Y-m-d H:i'),
                    'package_end_date'=>'',
                    'created_by'=>$request->created_by,
                );

                $packageInfoId = DB::connection('sales_db')->table('package_info')->insertGetId($package_info);

                if($packageInfoId)
                {
                    $serviceInfo = DB::connection('sales_db')->table('pre_package_service')->where('pre_package_id',$request->package_id)->get();
                    
                    foreach($serviceInfo as $row)
                    {
                        $serviceInfo = array(
                            'package_id'=>$packageInfoId,
                            'service_id'=>$row->service_id,
                            'total_lead'=>$row->total_lead,
                            'total_price'=>$row->total_price,
                            'balance_lead'=>$row->total_lead,
                        );
                        $serviceInfoId = DB::connection('sales_db')->table('service_info')->insertGetId($serviceInfo);
                    }
                    
                    return response()->json(["messages"=>'Package created successfully.','status'=>200]);
                }
            }
    }

}
