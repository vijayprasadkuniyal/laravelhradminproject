<?php
namespace App\Http\Controllers\SalesApi;
use DB;
use Redirect;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Invoice\ClientInvoiceController;
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

    public function get_locality_list($group_id)
    {
        $locality = DB::connection('sales_db')->table('locality')
            ->select('locality_id as value','locality_name as label')
             ->where(array('status'=>1,'state_id'=>$group_id))
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
            'category_id' => 'required',
            'base_price' => 'required',
            //'package_type' => 'required',
            'lead_devide_percent' => 'required',
            'single_cat_price_inc' => 'required',
            'minimum_lead_count' => 'required',
            'single_cat_min_lead_count' => 'required',
            'drop_after_lead_count' => 'required',
            'drop_lead_rate_between' => 'required',
            'drop_rate_price' => 'required',
            'maximum_lead_drop' => 'required',
            //'to_location_price' => 'required',
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
            'package_type'=>$request->package_type,
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
            'category_id' => 'required',
            //'package_type'=>'required',
            'base_price' => 'required',
            'lead_devide_percent' => 'required',
            'single_cat_price_inc' => 'required',
            'min_days_count' => 'required',
            'single_cat_min_days_count' => 'required',
            'drop_after_lead_count' => 'required',
            'drop_lead_rate_between' => 'required',
            'drop_rate_price' => 'required',
            'maximum_lead_drop' => 'required',
            //'to_location_price' => 'required',
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
            'package_type'=>$request->package_type,
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
           //'category_id' => 'required',
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
            //'category_id' => 'required',
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
            //->where(array('lead_base_package.status'=>1))
            ->get();
        return response()->json(['status'=>200,'message'=>'Lead Based Package details','data'=>$leadbasedPackage]); 
    }

    public function get_unlimitedbased_packages_list()
    {
        $leadbasedPackage = DB::connection('sales_db')->table('unlimited_package')
            ->select('unlimited_package.*','product.product_name','product_service.service_name','product_category.category_name')
            ->leftJoin('product','product.id','=','unlimited_package.product_id')
            ->leftJoin('product_service','product_service.id','=','unlimited_package.service_id')
            ->leftJoin('product_category','product_category.id','=','unlimited_package.category_id')
            //->where(array('unlimited_package.status'=>1))
            ->get();
        return response()->json(['status'=>200,'message'=>'Unlimited Based Package details','data'=>$leadbasedPackage]); 
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

    // public function check_package_price(Request $request)
    // {
    //     $input = $request->all();
    //     $validator = Validator::make($input, [
    //         'services' => 'required',
    //         'category' => 'required',
    //         'group_id' => 'required',
    //         'package_type' => 'required'
    //     ]);
    //     if($validator->fails()){
    //         $messages=$validator->messages();
    //         return response()->json(["messages"=>$messages,'status'=>400]);
    //     }
        
    //     $return = array();
    //     $group_id = $request->post('group_id');
    //     $package_type = $request->post('package_type');

    //     $to_location = $request->post('to_location');
    //     $package_duration = $request->post('package_duration');
    //     $services = explode(",",$request->post('category'));
    //     //$services = explode(",",$request->post('category'));
    //     $total_leads = $request->post('total_leads');
    //     if($package_duration == "1"){
    //         $service_values = LeadBaseServiceModel::whereIn("category_id",$services)->where('package_type',$package_type)->get();
    //     }else if($package_duration == "2"){
    //         $service_values = UnlimitedServiceModel::whereIn("category_id",$services)->where('package_type',$package_type)->get();
    //     }

    //     $estimate_total_leads = 0;
    //     $single_estimate_total_leads = 0;
    //     $count = 0;
    //     $total_percentage = 0;
    //     $to_location_price = 0;

    //     foreach($service_values as $service_value)
    //     {
    //         $estimate_total_leads += $service_value['min_lead_count'];
    //         $single_estimate_total_leads += $service_value['single_cat_lead_min_count'];
    //         $total_percentage += $service_value['lead_devide_percent'];
    //         $to_location_price += $service_value['to_location_price'];
    //         $count++;
    //     }

    //     $to_location_price = $to_location_price / $count;

    //     if($count < 2)
    //     {
    //         $estimate_total_leads = $single_estimate_total_leads;
    //     }
    //     if($total_percentage < 100)
    //     {
    //         $estimate_total_leads = $single_estimate_total_leads;
    //     }

    //     $estimate_total_leads = round($estimate_total_leads / $count);


    //     if($total_leads < $estimate_total_leads)
    //     {
    //         $total_leads = $estimate_total_leads;
    //     }

    //     $include_child_array = array();

    //     $percentage_count = $this->get_percentage_count($total_leads,$service_values);

    //     foreach($service_values as $service_value){
    //         if($count > 1){
    //             if($total_percentage < 100){
    //                 //$return[$service_value['service_id']]['total_lead'] = round($total_leads / $count);
    //                 $return[$service_value['category_id']]['total_lead'] = $percentage_count[$service_value['service_id']];
    //                 if($service_value['increment_type'] == 0){
    //                     // for Direct Price... 
    //                     $return[$service_value['category_id']]['price'] = $service_value['base_price'] + $service_value['for_single_cat_price_inc'];
    //                 }else{ 
    //                     // for persentage... 
    //                 }
    //             }else{
    //                 $return[$service_value['category_id']]['total_lead'] = $percentage_count[$service_value['service_id']];
    //                 if($service_value['increment_type'] == 0){
    //                     // for Direct Price... 
    //                     $return[$service_value['category_id']]['price'] = $service_value['base_price'];
    //                 }else{ 
    //                     // for persentage... 
    //                 }
    //             }
    //         }else{
    //             $return[$service_value['service_id']]['total_lead'] = $total_leads;
    //             if($service_value['increment_type'] == 0){
    //                 // for Direct Price... 
    //                 $return[$service_value['category_id']]['price'] = $service_value['base_price'] + $service_value['for_single_cat_price_inc'];
    //             }else{ 
    //                 // for persentage... 
    //             }
    //         }

    //         if($to_location == 1){
    //             $return[$service_value['category_id']]['price'] = $return[$service_value['service_id']]['price'] + $service_value['to_location_price'];
    //         }

    //         $location_details = LeadBaseLocationModel::where("package_type",$package_type)->where("include_group",$group_id)->where("category_for",$service_value['service_id'])->first();
    //         if($location_details){
    //             $return[$service_value['service_id']]['price'] = $return[$service_value['service_id']]['price'] + $location_details->condition_value;
    //             $include_child_array = array_merge($include_child_array,explode(",",$location_details->requried_childs));
    //         }

    //         $drop_price = 0;
    //         $max_drop_rate = round((($service_value['max_lead_drop'] - $service_value['drop_price_after_lead']) / $service_value['drop_rate_between']) * $service_value['drop_rate_price']);

    //         $drop_rate_check = round(($percentage_count[$service_value['service_id']] - $service_value['drop_price_after_lead']) / $service_value['drop_rate_between']);
    //         if($drop_rate_check > 0){
    //             $drop_price = $drop_rate_check * $service_value['drop_rate_price'];
    //             if($max_drop_rate < $drop_price){
    //                 $drop_price = $max_drop_rate;
    //             }
    //         }
            
    //         $return[$service_value['service_id']]['price'] = $return[$service_value['service_id']]['price'] - $drop_price;

    //         $return[$service_value['service_id']]['to_location_price'] = $service_value['to_location_price'];
    //         $return[$service_value['service_id']]['service_id'] = $service_value['service_id'];

    //     }
    //     $final_lead_total = 0;
    //     $final_total_price = 0;
    //     foreach($return as $final_values){
    //         $final_lead_total += $final_values['total_lead'];
    //         $final_total_price += $final_values['price']*$final_values['total_lead'];
    //     }    
        
    //     return response()->json(["messages"=>"",'status'=>200,"data"=>$return,"total_leads"=>$final_lead_total,"total_price"=>$final_total_price,"include_locality"=>$include_child_array]);
    // }

    // public static function get_percentage_count($total,$services)
    // {
    //     $return = array();
    //     $count = 0;
    //     $total_percent = 0;
    //     foreach($services as $service_value){
    //         $total_percent += $service_value['lead_devide_percent'];
    //         $count++;
    //     }
    //     foreach($services as $service_value){
    //         $return[$service_value['category_id']] = round(($total * $service_value['lead_devide_percent']) / $total_percent);
    //     }
    //     return $return;
    // }

    public function check_package_price(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'services' => 'required',
            'category' => 'required',
            'group_id' => 'required',
            'product_id' => 'required'
        ]);
        if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);
        }
        
        $return = array();
        $productId = $request->post('product_id');
        $group_id = $request->post('group_id');
       // $package_type = $request->post('package_type');

        $to_location = $request->post('to_location');
        $package_duration = $request->post('package_duration');
        $services = explode(",",$request->post('category'));
        //$services = explode(",",$request->post('category'));
        $total_leads = $request->post('total_leads');
        if($package_duration == "1")
        {
            if($productId ==1)
            {
                $service_values = LeadBaseServiceModel::whereIn("category_id",$services)
                //->where('package_type',$package_type)
                ->get();
            }
            else
            {
                $service_values = LeadBaseServiceModel::whereIn("category_id",$services)
                //->where('package_type',$package_type)
                ->get();
            }

            
        }else if($package_duration == "2"){
            if($productId ==1)
            {
                $service_values = UnlimitedServiceModel::whereIn("category_id",$services)
                //->where('package_type',$package_type)
                ->get();
            }
            else
            {
                $service_values = UnlimitedServiceModel::whereIn("category_id",$services)->get();
            }
        }

        if(empty($service_values)){
            return response()->json(["messages"=>"Category base price not found!",'status'=>400]);
        }

        $estimate_total_leads = 0;
        $single_estimate_total_leads = 0;
        $count = 0;
        $total_percentage = 0;
        $to_location_price = 0;
        $min_per_day_lead=0;


        foreach($service_values as $service_value)
        {
            $min_per_day_lead += $service_value['per_day_lead'];
            $estimate_total_leads += $service_value['min_lead_count'];
            $single_estimate_total_leads += $service_value['single_cat_lead_min_count'];
            $total_percentage += $service_value['lead_devide_percent'];
            if($productId ==1)
            {
                $to_location_price += $service_value['to_location_price'];
            }
            else
            {
                $to_location_price=0;
            }
            $count++;
        }

        $to_location_price = $to_location_price / $count;

        if($count < 2)
        {
            $estimate_total_leads = $single_estimate_total_leads;
        }
        if($total_percentage < 100)
        {
            $estimate_total_leads = $single_estimate_total_leads;
        }

        $estimate_total_leads = round($estimate_total_leads / $count);

        if($package_duration == "2")
        {
            $min_per_day_lead = round($min_per_day_lead / $count);
            $total_leads = $total_leads * $min_per_day_lead;
        }

        if($total_leads < $estimate_total_leads)
        {
            $total_leads = $estimate_total_leads;
        }

        $include_child_array = array();

        $percentage_count = $this->get_percentage_count($total_leads,$service_values);

        foreach($service_values as $service_value){
            if($count > 1){
                if($total_percentage < 100){
                    //$return[$service_value['category_id']]['total_lead'] = round($total_leads / $count);
                    $return[$service_value['category_id']]['total_lead'] = $percentage_count[$service_value['category_id']];
                    if($service_value['increment_type'] == 0){
                        // for Direct Price... 
                        $return[$service_value['category_id']]['price'] = $service_value['base_price'] + $service_value['for_single_cat_price_inc'];
                    }else{ 
                        // for persentage... 
                    }
                }else{
                    $return[$service_value['category_id']]['total_lead'] = $percentage_count[$service_value['category_id']];
                    if($service_value['increment_type'] == 0){
                        // for Direct Price... 
                        $return[$service_value['category_id']]['price'] = $service_value['base_price'];
                    }else{ 
                        // for persentage... 
                    }
                }
            }else{
                $return[$service_value['category_id']]['total_lead'] = $total_leads;
                if($service_value['increment_type'] == 0){
                    // for Direct Price... 
                    $return[$service_value['category_id']]['price'] = $service_value['base_price'] + $service_value['for_single_cat_price_inc'];
                }else{ 
                    // for persentage... 
                }
            }

            if($to_location == 1 && $productId==1){
                $return[$service_value['category_id']]['price'] = $return[$service_value['category_id']]['price'] + $service_value['to_location_price'];
            }

            $location_details = LeadBaseLocationModel::
            //where("package_type",$package_type)
            where("include_group",$group_id)->where("category_for",$service_value['category_id'])->first();
            if($location_details){
                $return[$service_value['category_id']]['price'] = $return[$service_value['category_id']]['price'] + $location_details->condition_value;
                $include_child_array = array_merge($include_child_array,explode(",",$location_details->requried_childs));
            }

            $drop_price = 0;
            $max_drop_rate = round((($service_value['max_lead_drop'] - $service_value['drop_price_after_lead']) / $service_value['drop_rate_between']) * $service_value['drop_rate_price']);

            $drop_rate_check = round(($percentage_count[$service_value['category_id']] - $service_value['drop_price_after_lead']) / $service_value['drop_rate_between']);
            if($drop_rate_check > 0){
                $drop_price = $drop_rate_check * $service_value['drop_rate_price'];
                if($max_drop_rate < $drop_price){
                    $drop_price = $max_drop_rate;
                }
            }
            
            $return[$service_value['category_id']]['price'] = $return[$service_value['category_id']]['price'] - $drop_price;

            $return[$service_value['category_id']]['to_location_price'] = $service_value['to_location_price'];
            $return[$service_value['category_id']]['category_id'] = $service_value['category_id'];

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
            $return[$service_value['category_id']] = round(($total * $service_value['lead_devide_percent']) / $total_percent);
        }
        return $return;
    }
    public function create_pre_package(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            'category_id' => 'required',
            'group_id' => 'required',
            //'package_type' => 'required',
           // 'to_location' => 'required',
            'package_duration' => 'required',
            'service_details' => 'required',
            'package_category' => 'required',
            'total_leads' => 'required',
            'package_price' => 'required',
            'is_partial_payment' => 'required',
            'package_title' => 'required',
            'created_by'=>'required',
            ]);

            
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        // $localityIds=implode(",",$request->locality_id,"value");
        if($request->is_partial_payment ==1)
        {
            $partialLeads = round($request->total_leads * 33 / 100);
            $partialAmount = round($request->package_price / 2);
        }
        else
        {
            $partialLeads = '';
            $partialAmount = '';
        }

        $pacageDetails = array(
            'package_name'=>$request->package_title,
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            'category_id'=>$request->category_id,
            'group_id'=>$request->group_id,
            'city_id'=>$request->city_id,
            //'package_type'=>$request->package_type,
            'to_location'=>$request->to_location,
            'package_duration'=>$request->package_duration,
            'package_category'=>$request->package_category,
            'total_lead'=>$request->total_leads,
            'package_price'=>$request->package_price,
            'partial_leads'=>$partialLeads,
            'partial_price'=>$partialAmount,
            'is_partial_payment'=>$request->is_partial_payment,
            'tax_amount'=>$request->tax_amount,
            'service_charges'=>$request->service_charges,
            'total_amount'=>$request->total_amount,
            'created_by'=>$request->created_by,
        );
        
        $package_id = DB::connection('sales_db')->table('pre_package')->insertGetId($pacageDetails);
        $serviceDetailsJson = json_decode($request->service_details);
        if($package_id)
        {
            foreach($serviceDetailsJson as $serviceDetails)
            {
                if($request->is_partial_payment ==1)
                {
                    $partialLead = round($serviceDetails->total_lead * 33 / 100);
                    $partial_price = round($partialLead * $serviceDetails->price);
                }
                else
                {
                    $partialLead = '';
                    $partial_price = '';
                }
                $serviceArray = array(
                    'pre_package_id'=>$package_id,
                    'service_id'=>$request->service_id,
                    'category_id'=>$serviceDetails->category_id,
                    'total_lead'=>$serviceDetails->total_lead,
                    'total_price'=>$serviceDetails->price,
                    'partial_lead'=>$partialLead,
                    'partial_price'=>$partial_price,
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
        ->select('pre_package.id','pre_package.package_name','pre_package.package_status as status','pre_package.to_location','pre_package.total_lead','pre_package.total_amount as package_price','pre_package.created_by','product.product_name','group_names.name as group_name','package_duration.name as duration_name','package_type.name as package_type_name','package_category.name as category_name','pre_package.is_partial_payment','pre_package.group_id')
        
        ->selectRaw("GROUP_CONCAT(DISTINCT(ps.service_name)) as service_name_list")
        ->selectRaw("GROUP_CONCAT(DISTINCT(pc.category_name)) as category_name_list")
        ->selectRaw("GROUP_CONCAT(DISTINCT(locality.locality_name)) as city_name")
        ->leftJoin('product','product.id','=','pre_package.product_id')
       // ->leftJoin('product_category','product_category.id','=','pre_package.category_id')
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
        ->leftJoin('product_category as pc',function ($join3)
        {
            $join3->whereRaw("FIND_IN_SET(pc.id,pre_package.category_id)");
        })
        //->where('pre_package.package_status',1)
        ->groupBy('pre_package.id')
        ->orderBy('pre_package.id','DESC')
        ->get();
        return response()->json(['status'=>200,'message'=>'Pre Packages details','data'=>$prePackages]); 
    }

    public function get_lmart_pre_packages_list()
    {
        $prePackages = DB::connection('sales_db')->table('pre_package')
        ->select('pre_package.id','pre_package.package_name','pre_package.package_status as status','pre_package.to_location','pre_package.total_lead','pre_package.created_by','pre_package.product_id','product.product_name','group_names.name as group_name','package_duration.name as duration_name','package_type.name as package_type_name','package_category.name as category_name','package_category.id as category_id','pre_package.is_partial_payment','pre_package.total_amount as package_price','pre_package.city_id','pre_package.group_id')

        ->selectRaw("GROUP_CONCAT(DISTINCT(pc.category_name)) as category_name_list")
        ->selectRaw("GROUP_CONCAT(DISTINCT(locality.locality_name)) as city_name")
        ->leftJoin('product','product.id','=','pre_package.product_id')
        ->leftJoin('group_names','group_names.group_id','=','pre_package.group_id')
        ->leftJoin('package_duration','package_duration.id','=','pre_package.package_duration')
        ->leftJoin('package_type','package_type.id','=','pre_package.package_type')
        ->leftJoin('package_category','package_category.id','=','pre_package.package_category','pre_package.tax_amount','pre_package.service_charges')

        ->leftJoin('product_category as pc',function ($join1)
        {
            $join1->whereRaw("FIND_IN_SET(pc.id,pre_package.category_id)");
        })
        ->leftJoin('locality',function ($join2)
        {
            $join2->whereRaw("FIND_IN_SET(locality.locality_id,pre_package.city_id)");
        })
        ->groupBy('pre_package.id')
        ->orderBy('pre_package.id','DESC')
        ->get();
        //category_name
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

    // public function check_client_wallet_balance(Request $request)
    // {
    //     $client_details = DB::connection('sales_db')->table('wallet_info')->where('client_id',$request->client_id)->first();
    //     if($client_details)
    //     {
    //         $blance_amount = $client_details->balance_amount;
    //         if($request->isPartialPayment ==1)
    //         {
    //             $package_price = $request->price/2;
    //         }
    //         else
    //         {
    //             $package_price = $request->price;
    //         }
            
    //         //return $blance_amount;
    //         // if($package_price <= $blance_amount)
    //         // {
    //             $citesId = explode(',',$request->group_id);
    //             $cityList = DB::connection('sales_db')->table('locality')
    //             ->select('locality_id as value','locality_name as label')
    //             ->whereIn('state_id',$citesId)->get();
                
    //             $prePackageDetails = DB::connection('sales_db')->table('pre_package')->where('id',$request->id)->first();

    //             $offerDetails = DB::connection('sales_db')->table('offers_details')
    //             ->select(
    //                 'pre_package_id',
    //                 'id',
    //                 'offer_title',
    //                 'offer_description',
    //                 'offer_discount_type',
    //                 'offer_price_percent',
    //                 'offer_lead_percent',
    //                 'valid_from',
    //                 'valid_to',
    //                 'status'
    //             )
    //             ->whereRaw('FIND_IN_SET(?, pre_package_id)', [$request->id])
    //             ->get();

    //             $package_details = array(
    //                 'price'=>$request->price,
    //                 'total_lead'=>$request->total_lead,
    //                 'package_name'=>$request->name,
    //                 'package_id'=>$request->id,
    //                 'category_name'=>$request->category_name,
    //                 'service_name'=>$request->service_name,
    //                 'duration_name'=>$request->duration_name,
    //                 'balance_amount'=>$request->blance_amount,
    //                 'isPartialPayment'=>$request->isPartialPayment,
    //                 'taxAmount'=>round($request->price - ($request->price /1.18)),
    //                 'package_price'=>round($request->price-($request->price - ($request->price /1.18))),
    //                 'package_name'=>$request->package_name,
    //                 'cityDetails'=>$cityList,
    //                 'city_id'=>$citesId,
    //                 'group_id'=>$request->group_id,
    //                 'offerDetails'=>$offerDetails
    //             );
    //             return response()->json(['status'=>200,'data'=>$package_details]);
    //         // }
    //         // else
    //         // {
    //         //     return response()->json(['status'=>500,'message'=>'You dont have sufficent balance to buy this package. ']);
    //         // }
    //     }
    //     else
    //     {
    //         return response()->json(['status'=>500,'message'=>'client not found']);
    //     }
    // }
    
    // public function check_client_wallet_balance(Request $request)
    // {
    //     $client_details = DB::connection('sales_db')->table('wallet_info')->where('client_id',$request->client_id)->first();
    //     if($client_details)
    //     {
    //         $blance_amount = $client_details->balance_amount;
    //         if($request->isPartialPayment ==1)
    //         {
    //             $package_price = $request->price/2;
    //         }
    //         else
    //         {
    //             $package_price = $request->price;
    //         }
            
            
    //             $citesId = explode(',',$request->group_id);
    //             $prePackageDetails = DB::connection('sales_db')->table('pre_package')->where('id',$request->id)->first();

    //             $offerDetails = DB::connection('sales_db')->table('offers_details')
    //             ->select(
    //                 'pre_package_id',
    //                 'id',
    //                 'offer_title',
    //                 'offer_description',
    //                 'offer_discount_type',
    //                 'offer_price_percent',
    //                 'offer_lead_percent',
    //                 'valid_from',
    //                 'valid_to',
    //                 'status'
    //             )
    //             ->whereRaw('FIND_IN_SET(?, pre_package_id)', [$request->id])
    //             ->get();

    //             $prePackageGroupId = explode(',',$request->group_id);
    //             $groupDetails = DB::connection('sales_db')->table('group_names')
    //             ->select('group_id', 'name as group_name')
    //             ->whereIn('group_id',$prePackageGroupId)
    //             ->get();

    //             $package_details = array(
    //                 'price'=>$request->price,
    //                 'total_lead'=>$request->total_lead,
    //                 'package_name'=>$request->name,
    //                 'package_id'=>$request->id,
    //                 'category_name'=>$request->category_name,
    //                 'service_name'=>$request->service_name,
    //                 'duration_name'=>$request->duration_name,
    //                 'balance_amount'=>$request->blance_amount,
    //                 'isPartialPayment'=>$request->isPartialPayment,
    //                 'taxAmount'=>round($request->price - ($request->price /1.18)),
    //                 'package_price'=>round($request->price-($request->price - ($request->price /1.18))),
    //                 'package_name'=>$request->package_name,
                   
    //                 'city_id'=>$citesId,
    //                 'group_id'=>$request->group_id,
    //                 'groupDetails'=>$groupDetails,
    //                 'offerDetails'=>$offerDetails
    //             );
    //             return response()->json(['status'=>200,'data'=>$package_details]);
            
    //     }
    //     else
    //     {
    //         return response()->json(['status'=>500,'message'=>'client not found']);
    //     }
    // }

    public function check_client_wallet_balance(Request $request)
    {
        $client_details = DB::connection('sales_db')->table('wallet_info')->where('client_id',$request->client_id)->first();
        if($client_details)
        {
            $blance_amount = $client_details->balance_amount;
            if($request->isPartialPayment ==1)
            {
                $package_price = $request->price/2;
            }
            else
            {
                $package_price = $request->price;
            }
            
            
            $citesId = explode(',',$request->group_id);
            $prePackageDetails = DB::connection('sales_db')->table('pre_package')->where('id',$request->id)->first();

            $bannerDetails = DB::connection('sales_db')->table('package_category_type_assign')
            ->where('cat_id',$prePackageDetails->package_category)
            ->where('product_id',$prePackageDetails->product_id)
            ->where('type_id',7)
            ->where('status',1)
            ->first();
            if($bannerDetails)
            {
                $bannerStatus = 1;

                $bannerCities = $bannerDetails = DB::connection('sales_db')->table('banner_city')
                ->select('title','id')
                ->where('status','1')
                ->get();
            }
            else
            {
                $bannerCities = array();
                $bannerStatus = 0;
            }

            $offerDetails = DB::connection('sales_db')->table('offers_details')
            ->select(
                'pre_package_id',
                'id',
                'offer_title',
                'offer_description',
                'offer_discount_type',
                'offer_price_percent',
                'offer_lead_percent',
                'valid_from',
                'valid_to',
                'status'
            )
            ->whereRaw('FIND_IN_SET(?, pre_package_id)', [$request->id])
            ->get();

            $prePackageGroupId = explode(',',$request->group_id);
            $groupDetails = DB::connection('sales_db')->table('group_names')
            ->select('group_id', 'name as group_name')
            ->whereIn('group_id',$prePackageGroupId)
            ->get();

            $package_details = array(
                'price'=>$request->price,
                'total_lead'=>$request->total_lead,
                'package_name'=>$request->name,
                'package_id'=>$request->id,
                'category_name'=>$request->category_name,
                'service_name'=>$request->service_name,
                'duration_name'=>$request->duration_name,
                'balance_amount'=>$request->blance_amount,
                'isPartialPayment'=>$request->isPartialPayment,
                'taxAmount'=>round($request->price - ($request->price /1.18)),
                'package_price'=>round($request->price-($request->price - ($request->price /1.18))),
                'package_name'=>$request->package_name,
                'city_id'=>$citesId,
                'group_id'=>$request->group_id,
                'groupDetails'=>$groupDetails,
                'offerDetails'=>$offerDetails,
                'banner_status'=>$bannerStatus,
                'banner_cities'=>$bannerCities,
                'service_id'=>$prePackageDetails->service_id,
            );
            return response()->json(['status'=>200,'data'=>$package_details]);
            
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'client not found']);
        }
    }
    
    public function get_package_city_details($group_id)
    {
        $cityList = DB::connection('sales_db')->table('locality')
        ->select('locality_id as value','locality_name as label')
        ->where('state_id',$group_id)->get();

        return response()->json(['status'=>200,'data'=>$cityList]);
    }


    // public function check_offer_details(Request $request)
    // {
    //     $citesId = explode(',',$request->group_id);
    //     $cityList = DB::connection('sales_db')->table('locality')
    //     ->select('locality_id as value','locality_name as label')
    //     ->whereIn('state_id',$citesId)->get();
        
    //     $prePackageDetails = DB::connection('sales_db')->table('pre_package')->where('id',$request->id)->first();

    //     $offerDetails = DB::connection('sales_db')->table('offers_details')
    //     ->select(
    //         'pre_package_id',
    //         'id',
    //         'offer_title',
    //         'offer_description',
    //         'offer_discount_type',
    //         'offer_price_percent',
    //         'offer_lead_percent',
    //         'valid_from',
    //         'valid_to',
    //         'status'
    //     )
    //     ->whereRaw('FIND_IN_SET(?, pre_package_id)', [$request->id])
    //     ->get();

    //     $package_details = array(
    //         'price'=>$request->price,
    //         'total_lead'=>$request->total_lead,
    //         'package_name'=>$request->name,
    //         'package_id'=>$request->id,
    //         'category_name'=>$request->category_name,
    //         'service_name'=>$request->service_name,
    //         'duration_name'=>$request->duration_name,
    //         'balance_amount'=>$request->blance_amount,
    //         'isPartialPayment'=>$request->isPartialPayment,
    //         'taxAmount'=>round($request->price - ($request->price /1.18)),
    //         'package_price'=>round($request->price-($request->price - ($request->price /1.18))),
    //         'package_name'=>$request->package_name,
    //         'cityDetails'=>$cityList,
    //         'city_id'=>$citesId,
    //         'group_id'=>$request->group_id,
    //         'offerDetails'=>$offerDetails
    //     );
    //     return response()->json(['status'=>200,'data'=>$package_details]);
            
    // }

        public function check_offer_details(Request $request)
    {
        $citesId = explode(',',$request->group_id);
        $prePackageGroupId = explode(',',$request->group_id);
        $groupDetails = DB::connection('sales_db')->table('group_names')
        ->select('group_id', 'name as group_name')
        ->whereIn('group_id',$prePackageGroupId)
        ->get();
        
        $prePackageDetails = DB::connection('sales_db')->table('pre_package')->where('id',$request->id)->first();

        $offerDetails = DB::connection('sales_db')->table('offers_details')
        ->select(
            'pre_package_id',
            'id',
            'offer_title',
            'offer_description',
            'offer_discount_type',
            'offer_price_percent',
            'offer_lead_percent',
            'valid_from',
            'valid_to',
            'status'
        )
        ->whereRaw('FIND_IN_SET(?, pre_package_id)', [$request->id])
        ->get();

        $package_details = array(
            'price'=>$request->price,
            'total_lead'=>$request->total_lead,
            'package_name'=>$request->name,
            'package_id'=>$request->id,
            'category_name'=>$request->category_name,
            'service_name'=>$request->service_name,
            'duration_name'=>$request->duration_name,
            'balance_amount'=>$request->blance_amount,
            'isPartialPayment'=>$request->isPartialPayment,
            'taxAmount'=>round($request->price - ($request->price /1.18)),
            'package_price'=>round($request->price-($request->price - ($request->price /1.18))),
            'package_name'=>$request->package_name,
            //'cityDetails'=>$cityList,
            'city_id'=>$citesId,
            'group_id'=>$request->group_id,
            'groupDetails'=>$groupDetails,
            'offerDetails'=>$offerDetails
        );
        return response()->json(['status'=>200,'data'=>$package_details]);
            
    }
    
   
    
    // public function buy_new_package(Request $request)
    // {
    //     $input = $request->all();
    //     $validator = Validator::make($input, [
    //         'client_id' => 'required',
    //         'package_id' => 'required',
    //         'depositAmount' => 'required',
    //         'balanceAmount' => 'required',
    //         'dueLead' => 'required',
    //         'taxAmount' => 'required',
    //         'comp_id'=>'required',
    //         'payment_for'=>'required',
    //         'cities_id'=>'required',
    //         'created_by'=>'required',
    //         'isPartialPayment'=>'required',
    //         ]);
        
    //         if($validator->fails())
    //         {
    //             $messages=$validator->messages();
    //             return response()->json(["messages"=>$messages,'status'=>400]);
    //         }

    //         $wallet_info = DB::connection('sales_db')->table('wallet_info')->where('client_id',$request->client_id)->first();
    //         if($request->isPartialPayment==1)
    //         {
    //             $paidAmount = $request->depositAmount / 2;
    //         }
    //         else
    //         {
    //             $paidAmount = $request->depositAmount;           
    //         }
            
    //         if($wallet_info->balance_amount < $paidAmount)
    //         {
    //             return response()->json(["messages"=>'Insufficient Balance','status'=>201]);
    //         }
    //         else
    //         {
    //             $packageInfo = DB::connection('sales_db')->table('pre_package')->where('id',$request->package_id)->first();

    //             $companyInfoGet = DB::connection('sales_db')
    //                 ->table('company_info')
    //                 ->select(
    //                     'company_info.address',
    //                     'company_info.business_name',
    //                     'company_info.email',
    //                     'company_info.mobile_no',
    //                     'company_info.is_verified_email',
    //                     'company_info.gst_state',
    //                     'company_info.exe_id',
    //                     'company_info.group_id',
    //                     'document_info.doc_number as gstNumber'
    //                 )
    //                 ->leftJoin('document_info', function ($join) {
    //                     $join->on('document_info.comp_id', '=', 'company_info.comp_id')
    //                         ->where('document_info.doc_type_id', '=', 4);
    //                 })
    //                 ->where('company_info.comp_id', $request->comp_id)
    //                 ->get();
                    

    //             $companyInfoGet->map(function ($item) {
    //                 $empName = DB::table('emp_basic_info')
    //                     ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")
    //                     ->where('emp_id', $item->exe_id)
    //                     ->first();
    //                 $item->emp_name = $empName->emp_name;
    //                 return $item;
    //             });

    //             $companyInfo = $companyInfoGet->first();
                
    //             $cityDetailsAccording = DB::connection('sales_db')
    //                 ->table('locality')
    //                 ->selectRaw('GROUP_CONCAT(locality_id) as locality_id')
    //                 ->where('state_id', $request->group_id)
    //                 ->first();

                
    //             $citiesId = explode(',', $request->cities_id);

    //             $locality = DB::connection('sales_db')->table('rsms_city')
    //                 ->selectRaw('GROUP_CONCAT(city_id) as localityid')
    //                 ->whereIn('locality_id', $citiesId)
    //                 ->first();
    //             $todayDate = date('Y-m-d');
                
    //             if ($request->discountType == 2)
    //             {
    //                 $totalLeads = $request->afterDiscountLead;
    //             }
    //             else
    //             {
    //                 $totalLeads = $packageInfo->total_lead;
    //             }

                // if($request->isPartialPayment ==1)
                // {
                //     $dueStatus=1;
                //     $dueDate = Carbon::createFromFormat('Y-m-d',$todayDate)->addDays(7);
                // }
                // else
                // {
                //     $dueStatus=0;
                //     $dueDate ='';
                // }
    //             //address
               
    //             $package_info = array(
    //                 'product_id'=>$packageInfo->product_id,
    //                 'client_id'=>$request->client_id,
    //                 'comp_id'=>$request->comp_id,//for company id
    //                 'pre_package_id'=>$packageInfo->id,
    //                 'package_duration'=>$packageInfo->package_duration,
    //                 'package_name'=>$packageInfo->package_name,
    //                 'service_id'=>$packageInfo->service_id,
    //                 'category_id'=>$packageInfo->category_id,
    //                 'package_amount'=>$request->payblePackageAmount,
    //                 'paid_amount'=>$request->depositAmount - $request->reg_amount,
    //                 'to_location'=>$packageInfo->to_location,
    //                 'tax_amount'=>$request->taxAmount,
    //                 'due_amount'=>$request->balanceAmount,
    //                 'registration_amount'=>round($request->reg_amount / 1.18),
    //                 'total_lead'=>$totalLeads,
    //                 'due_date'=>$dueDate,
    //                 'due_lead'=>$request->dueLead,
    //                 'due_status'=>$dueStatus,
    //                 'package_category'=>$packageInfo->package_category,
    //                 'package_status'=>0,
    //                 'package_type'=>$request->payment_for,
    //                 'admin_status'=>0,
    //                 'finance_status'=>0,
    //                 'package_start_date'=>date('Y-m-d H:i'),
    //                 'package_end_expected_date'=>Carbon::createFromFormat('Y-m-d',$todayDate)->addDays(30),
    //                 'group_id'=>$request->group_id,
    //                 'city_id'=>$request->cities_id,
    //                 'locality_id'=>$locality->localityid,
    //                 'credit_point'=>floor(($request->depositAmount - $request->reg_amount) / 0.55),
    //                 'base_location'=>$companyInfo->address,
    //                 'exe_id'=>$request->created_by,
    //                 'created_by'=>$request->created_from,
    //             );
                
    //             $packageInfoId = DB::connection('sales_db')->table('package_info')->insertGetId($package_info);
    //             if($request->checkedOffers !='')
    //             {
    //                 if ($request->discountType == 1) {
    //                     $discountValue = round($request->payblePackageAmount /1.18);;
    //                     $beforeValue = round($packageInfo->total_amount /1.18);
    //                 } else {
    //                     $discountValue = $request->afterDiscountLead;
    //                     $beforeValue = $packageInfo->total_lead;
    //                 }
                    
    //                 // Correctly extract values from arrays
    //                 $checkedOffers = is_array($request->checkedOffers) ? $request->checkedOffers[$checkedOffers] : $request->checkedOffers;
    //                 $selectedPercent = is_array($request->selectedPercent) ? $request->selectedPercent[$checkedOffers] : $request->selectedPercent;
                    
    //                 $offerDetails = [
    //                     'offer_id' => $checkedOffers,
    //                     'client_id' => $request->client_id,
    //                     'comp_id' => $request->comp_id,
    //                     'product_id' => $packageInfo->product_id,
    //                     'service_id' => $packageInfo->service_id,
    //                     'category_id' => $packageInfo->category_id,
    //                     'current_package_id' => $packageInfoId,
    //                     'pre_package_id' => $request->package_id,
    //                     'discount_type' => $request->discountType,
    //                     'discount_type_id' => $selectedPercent,
    //                     'before_discount_value' => $beforeValue,
    //                     'after_discount_value' => $discountValue,
    //                     'created_by' => $request->created_from,
    //                     'exe_id' => $request->created_by,
    //                 ];
                    
    //                 // Insert into the database and get the inserted ID
    //                 $offerDetailsId = DB::connection('sales_db')->table('discount_grab_info')->insertGetId($offerDetails);
    //             }


    //             if($packageInfoId)
    //             {
                    
    //                 $serviceInfo = DB::connection('sales_db')->table('pre_package_service')->where('pre_package_id',$request->package_id)->get();

    //                     if($request->discountType == 2)
    //                     {
    //                         $lPercent = DB::connection('sales_db')->table('offer_discount_rate')
    //                         ->select('discount_percent')->where('id',$selectedPercent)->first();

    //                         $leadPercent=$lPercent->discount_percent;
    //                     }
    //                     else
    //                     {
    //                         $leadPercent=0;
    //                     }
                        
                    
    //                     foreach($serviceInfo as $row)
    //                     {
    //                         $totalLeads = round($row->total_lead * $leadPercent/100);
    //                         $serviceInfo = array(
    //                             'client_id'=>$request->client_id,
    //                             'comp_id'=>$request->comp_id,
    //                             'package_id'=>$packageInfoId,
    //                             'service_id'=>$row->service_id,
    //                             'category_id'=>$row->category_id,
    //                             'max_per_day'=>$row->max_per_day,
    //                             'total_lead'=>$row->total_lead + $totalLeads,
    //                             'balance_lead'=>$row->total_lead + $totalLeads,
    //                             'per_lead_price'=>$row->per_lead_price,
    //                             'per_lead_points'=>$row->per_lead_points,
    //                             'start_date'=>date('Y-m-d'),
    //                             'created_by'=>$request->created_by,
    //                         );
    //                         $serviceInfoId = DB::connection('sales_db')->table('service_info')->insertGetId($serviceInfo);
    //                     }

    //                     if($serviceInfoId)
    //                     {
    //                         $walletInfo = DB::connection('sales_db')->table('wallet_info')
    //                         ->select('wallet_id','client_id','balance_amount','debit_amount')
    //                         ->where('client_id',$request->client_id)
    //                         ->first();

    //                         $walletInfoArray =array(
    //                             'wallet_id'=>$walletInfo->wallet_id,
    //                             'client_id'=>$request->client_id,
    //                             'comp_id'=>$request->comp_id,
    //                             'transaction_mode'=>'Dr',
    //                             'transaction_id'=>$request->client_id.'-'.$request->comp_id.'-'.$walletInfo->wallet_id.'-'.$packageInfoId,
    //                             'debit'=>$request->depositAmount,
    //                             'balance'=>$walletInfo->balance_amount - $request->depositAmount,
    //                             'status'=>1,
    //                             'remarks'=>'Package created by wallet',
    //                             'transaction_date'=>date('Y-m-d H:i'),
    //                             'created_by'=>$request->created_by
    //                         );
                            
    //                         $historyInsert = DB::connection('sales_db')->table('wallet_history')->insertGetId($walletInfoArray);

    //                         if($historyInsert)
    //                         {
    //                             $walletinfoUpdate = array(
    //                                 'debit_amount'=>(float)$walletInfo->debit_amount + (float)$request->depositAmount,
    //                                 'balance_amount'=>(float)$walletInfo->balance_amount - (float)$request->depositAmount
    //                             );
    //                             $walletInfoUpdate = DB::connection('sales_db')->table('wallet_info')
    //                             ->where('wallet_id',$walletInfo->wallet_id)
    //                             ->update($walletinfoUpdate);
                                
    //                             if($walletInfoUpdate)
    //                             {
    //                                 $paymentInsert = array(
    //                                     'client_id'=>$request->client_id,
    //                                     'comp_id'=>$request->comp_id,
    //                                     'wallet_history_id'=>$historyInsert,
    //                                     'exe_id'=>$request->created_by,
    //                                     'paid_amount'=>$request->depositAmount,
    //                                     'tax_amount'=>$request->taxAmount,
    //                                     'reg_amount'=>$request->reg_amount,
    //                                     'package_id'=>$packageInfoId,
    //                                     'product_id'=>$packageInfo->product_id,
    //                                     'service_id'=>$packageInfo->service_id,
    //                                     'payment_for'=>$request->payment_for,
    //                                     'status'=>1,
    //                                     'created_by'=>$request->created_by,
    //                                 );

    //                                 $paymentInsertId = DB::connection('sales_db')->table('payment_history')->insertGetId($paymentInsert);
                                    
    //                                 if($paymentInsertId)
    //                                 {
    //                                     if($companyInfo->gst_state == 'Haryana'|| $companyInfo->gst_state == 'haryana' || $companyInfo->gst_state=='6')
    //                                     {
    //                                         $txtType=1;
    //                                     }
    //                                     else
    //                                     {
    //                                         $txtType=2;
    //                                     }

                                        
    //                                     $invoiceInfo = array(
    //                                         'pay_id'=>$paymentInsertId,
    //                                         'package_name'=>$packageInfo->package_name,
    //                                         'package_duration'=>$packageInfo->package_duration == 1 ? 'Lead Based' : 'Unlimited',
    //                                         'client_id'=>$request->client_id,
    //                                         'comp_id'=>$request->comp_id,
    //                                         'wallet_history_id'=>$historyInsert,
    //                                         'exe_id'=>$request->created_by,
    //                                         'package_amount'=>$request->payblePackageAmount,
    //                                         'due_amount'=>$request->balanceAmount,
    //                                         'paid_amount'=>$request->depositAmount,
    //                                         'tax_amount'=>$request->taxAmount,
    //                                         'reg_amount'=>$request->reg_amount,
    //                                         'package_id'=>$packageInfoId,
    //                                         'total_lead'=>$packageInfo->total_lead,
    //                                         'product_id'=>$packageInfo->product_id,
    //                                         'service_id'=>$packageInfo->service_id,
    //                                         'category_id'=>$packageInfo->category_id,
    //                                         'payment_for'=>$request->clientType,
    //                                         'business_name'=>$companyInfo->business_name,
    //                                         'email'=>$companyInfo->email,
    //                                         'mobile_no'=>$companyInfo->mobile_no,
    //                                         'is_verified_email'=>$companyInfo->is_verified_email,
    //                                         'gst_state'=>$companyInfo->gst_state,
    //                                         'executive_name'=>$companyInfo->emp_name,
    //                                         'gstNumber'=>$companyInfo->gstNumber,
    //                                         'taxType'=>$txtType,
    //                                         'status'=>1,
    //                                         //'admin_status'=>$adminStatus,
    //                                         'created_by'=>$request->created_by,
    //                                     );
                                        
    //                                     $retuenData = ClientInvoiceController::genrate_new_invoice($packageInfoId,$request->client_id,$invoiceInfo);
                                   
    //                                     if($retuenData ==1)
    //                                     {
    //                                         return response()->json(["messages"=>'Package created successfully.','status'=>200,'data'=>$package_info]);
    //                                     }
    //                                     else
    //                                     {
    //                                         return response()->json(["messages"=>'Something wrong with invoice creation','status'=>201]);
    //                                     }
    //                                 }
    //                                 else
    //                                 {
    //                                     return response()->json(["messages"=>'Something went wrong on payment history .','status'=>201]);
    //                                 }
    //                             }
    //                             else
    //                             {
    //                                 return response()->json(["messages"=>'Something went wrong on update wallet info.','status'=>201]);
    //                             }
    //                         }
    //                         else
    //                         {
    //                             return response()->json(["messages"=>'Something went wrong on update wallet history added.','status'=>201]);
    //                         }
    //                     }
    //                     else
    //                     {
    //                         return response()->json(["messages"=>'Something went wrong on package service.','status'=>201]);
    //                     }
    //             }
    //             else
    //             {
    //                 return response()->json(["messages"=>'Something went wrong on package creation.','status'=>201]);
    //             }
    //         }
    // }

    // 26-10-2024 backup
    // public function buy_new_package(Request $request)
    // {
    //     $input = $request->all();
    //     $validator = Validator::make($input, [
    //         'client_id' => 'required',
    //         'package_id' => 'required',
    //         'depositAmount' => 'required',
    //         'balanceAmount' => 'required',
    //         'dueLead' => 'required',
    //         'taxAmount' => 'required',
    //         'comp_id'=>'required',
    //         'payment_for'=>'required',
    //         'cities_id'=>'required',
    //         'created_by'=>'required',
    //         'isPartialPayment'=>'required',
    //         ]);
        
    //         if($validator->fails())
    //         {
    //             $messages=$validator->messages();
    //             return response()->json(["messages"=>$messages,'status'=>400]);
    //         }

    //         $wallet_info = DB::connection('sales_db')->table('wallet_info')->where('client_id',$request->client_id)->first();
    //         if($request->isPartialPayment==1)
    //         {
    //             $paidAmount = $request->depositAmount / 2;
    //         }
    //         else
    //         {
    //             $paidAmount = $request->depositAmount;           
    //         }
            
    //         if($wallet_info->balance_amount < $paidAmount)
    //         {
    //             return response()->json(["messages"=>'Insufficient Balance','status'=>201]);
    //         }
    //         else
    //         {
    //             $packageInfo = DB::connection('sales_db')->table('pre_package')->where('id',$request->package_id)->first();

    //             $companyInfoGet = DB::connection('sales_db')
    //                 ->table('company_info')
    //                 ->select(
    //                     'company_info.address',
    //                     'company_info.business_name',
    //                     'company_info.email',
    //                     'company_info.mobile_no',
    //                     'company_info.is_verified_email',
    //                     'company_info.gst_state',
    //                     'company_info.exe_id',
    //                     'company_info.group_id',
    //                     'document_info.doc_number as gstNumber'
    //                 )
    //                 ->leftJoin('document_info', function ($join) {
    //                     $join->on('document_info.comp_id', '=', 'company_info.comp_id')
    //                         ->where('document_info.doc_type_id', '=', 4);
    //                 })
    //                 ->where('company_info.comp_id', $request->comp_id)
    //                 ->get();
                    

    //             $companyInfoGet->map(function ($item)
    //             {
    //                 $empName = DB::table('emp_basic_info')
    //                     ->selectRaw("GROUP_CONCAT(emp_fname,' ',emp_lame) as emp_name")
    //                     ->where('emp_id', $item->exe_id)
    //                     ->first();
    //                 $item->emp_name = $empName->emp_name;
    //                 return $item;
    //             });

    //             $companyInfo = $companyInfoGet->first();
                
    //             $cityDetailsAccording = DB::connection('sales_db')
    //                 ->table('locality')
    //                 ->selectRaw('GROUP_CONCAT(locality_id) as locality_id')
    //                 ->where('state_id', $request->group_id)
    //                 ->first();

                
    //             $citiesId = explode(',', $request->cities_id);

    //             $locality = DB::connection('sales_db')->table('rsms_city')
    //                 ->selectRaw('GROUP_CONCAT(city_id) as localityid')
    //                 ->whereIn('locality_id', $citiesId)
    //                 ->first();
               
                
    //             if($request->discountType == 2)
    //             {
    //                 $totalLeads = $request->afterDiscountLead;
    //             }
    //             else
    //             {
    //                 $totalLeads = $packageInfo->total_lead;
    //             }

    //             if($request->isPartialPayment ==1)
    //             {$dueStatus=1;}else{$dueStatus=0;}
    //             //address
    //             $todayDate = date('Y-m-d');
    //             $package_info = array(
    //                 'product_id'=>$packageInfo->product_id,
    //                 'client_id'=>$request->client_id,
    //                 'comp_id'=>$request->comp_id,
    //                 'pre_package_id'=>$packageInfo->id,
    //                 'package_duration'=>$packageInfo->package_duration,
    //                 'package_name'=>$packageInfo->package_name,
    //                 'service_id'=>$packageInfo->service_id,
    //                 'category_id'=>$packageInfo->category_id,
    //                 'package_amount'=>$request->payblePackageAmount,
    //                 'paid_amount'=>$request->depositAmount - $request->reg_amount,
    //                 'tax_amount'=>$request->taxAmount,
    //                 'due_amount'=>$request->balanceAmount,
    //                 'registration_amount'=>round($request->reg_amount / 1.18),
    //                 'total_lead'=>$totalLeads,
    //                 'to_location'=>$packageInfo->to_location,
    //                 'due_date'=>Carbon::createFromFormat('Y-m-d',$todayDate)->addDays(7),
    //                 'due_lead'=>$request->dueLead,
    //                 'due_status'=>$dueStatus,
    //                 'package_status'=>0,
    //                 'package_type'=>$request->payment_for,
    //                 'admin_status'=>0,
    //                 'finance_status'=>0,
    //                 'package_start_date'=>date('Y-m-d H:i'),
    //                 'package_end_expected_date'=>Carbon::createFromFormat('Y-m-d',$todayDate)->addDays(30),
    //                 'group_id'=>$request->group_id,
    //                 'city_id'=>$request->cities_id,
    //                 'package_category'=>$packageInfo->package_category,
    //                 'locality_id'=>$locality->localityid,
    //                 'credit_point'=>floor(($request->depositAmount - $request->reg_amount) / 0.55),
    //                 'base_location'=>$companyInfo->address,
    //                 'exe_id'=>$request->created_by,
    //                 'created_by'=>$request->created_from,
    //             );
                
    //             $packageInfoId = DB::connection('sales_db')->table('package_info')->insertGetId($package_info);
    //             if($request->checkedOffers !='')
    //             {
    //                 if ($request->discountType == 1) {
    //                     $discountValue = round($request->payblePackageAmount /1.18);;
    //                     $beforeValue = round($packageInfo->total_amount /1.18);
    //                 } else {
    //                     $discountValue = $request->afterDiscountLead;
    //                     $beforeValue = $packageInfo->total_lead;
    //                 }
                    
    //                 // Correctly extract values from arrays
    //                 $checkedOffers = is_array($request->checkedOffers) ? $request->checkedOffers[$checkedOffers] : $request->checkedOffers;
    //                 $selectedPercent = is_array($request->selectedPercent) ? $request->selectedPercent[$checkedOffers] : $request->selectedPercent;
                    
    //                 $offerDetails = [
    //                     'offer_id' => $checkedOffers,
    //                     'client_id' => $request->client_id,
    //                     'comp_id' => $request->comp_id,
    //                     'product_id' => $packageInfo->product_id,
    //                     'service_id' => $packageInfo->service_id,
    //                     'category_id' => $packageInfo->category_id,
    //                     'current_package_id' => $packageInfoId,
    //                     'pre_package_id' => $request->package_id,
    //                     'discount_type' => $request->discountType,
    //                     'discount_type_id' => $selectedPercent,
    //                     'before_discount_value' => $beforeValue,
    //                     'after_discount_value' => $discountValue,
    //                     'created_by' => $request->created_from,
    //                     'exe_id' => $request->created_by,
    //                 ];
                    
    //                 // Insert into the database and get the inserted ID
    //                 $offerDetailsId = DB::connection('sales_db')->table('discount_grab_info')->insertGetId($offerDetails);
    //             }

                
    //             if($packageInfoId)
    //             {

    //                 $addtionalInfo = DB::connection('sales_db')->table('package_category_type_assign')
    //                 ->where('cat_id',$packageInfo->package_category)
    //                 ->where('product_id',$packageInfo->product_id)
    //                 ->where('type_status',1)
    //                 ->get();
                 
    
    //                 if(count($addtionalInfo) > 0)
    //                 {
    //                     if($request->banner_city_status == '1')
    //                     {
    //                         //$bannerCities = null;
    //                         $bannerCities = DB::connection('sales_db')
    //                             ->table('banner_city')
    //                             ->select('title', 'id', 'country_id', 'state_id')
    //                             ->where('id', $request->banner_city_id)
    //                             ->first();
    //                             $country_id = $bannerCities->country_id;
    //                             $city_id = $bannerCities->id;
    //                             $city_name = $bannerCities->title;
    //                     }
    //                     else
    //                     {
    //                         $country_id = 0;
    //                         $city_id = 0;
    //                         $city_name = '';
    //                     }
                    
                       
    //                     foreach ($addtionalInfo as $addInfo)
    //                     {
    //                         $addArray = [
    //                             'product_id' => $packageInfo->product_id,
    //                             'client_id' => $request->client_id,
    //                             'comp_id' => $request->comp_id,
    //                             'package_id' => $packageInfoId,
    //                             'category_type_id' => $addInfo->type_id,
    //                             'service_id' => $packageInfo->service_id,
    //                             'country_id' => $country_id,
    //                             'city_id' => $city_id,
    //                             'city_name' => $city_name,
    //                             'amount_paid' => 0,
    //                             'package_total_amt' => 0,
    //                             'package_start_date' => date('Y-m-d'),
    //                             'package_expire_date' => Carbon::createFromFormat('Y-m-d', $todayDate)->addDays(30),
    //                             'package_type' => $request->payment_for,
    //                             'package_status' => 1,
    //                             'executive_id' => $request->created_by,
    //                         ];
                    
                            
    //                         DB::connection('sales_db')
    //                             ->table('package_additional_service_info')
    //                             ->insertGetId($addArray);
    //                     }
    //                 }
    
                    

    //                 $serviceInfo = DB::connection('sales_db')->table('pre_package_service')->where('pre_package_id',$request->package_id)->get();

    //                     if($request->discountType == 2)
    //                     {
    //                         $lPercent = DB::connection('sales_db')->table('offer_discount_rate')
    //                         ->select('discount_percent')->where('id',$selectedPercent)->first();
    //                         $leadPercent=$lPercent->discount_percent;
    //                     }
    //                     else
    //                     {
    //                         $leadPercent=0;
    //                     }
                        
    //                     // foreach($serviceInfo as $row)
    //                     // {
    //                     //     $totalLeads = round($row->total_lead * $leadPercent/100);
    //                     //     $serviceInfo = array(
    //                     //         'client_id'=>$request->client_id,
    //                     //         'comp_id'=>$request->comp_id,
    //                     //         'package_id'=>$packageInfoId,
    //                     //         'service_id'=>$row->service_id,
    //                     //         'category_id'=>$row->category_id,
    //                     //         'total_lead'=>$row->total_lead + $totalLeads,
    //                     //         'balance_lead'=>$row->total_lead,
    //                     //         'per_lead_price'=>$row->per_lead_price,
    //                     //         'per_lead_points'=>$row->per_lead_points,
    //                     //         'start_date'=>date('Y-m-d'),
    //                     //         'created_by'=>$request->created_by,
    //                     //     );
    //                     //     $serviceInfoId = DB::connection('sales_db')->table('service_info')->insertGetId($serviceInfo);
    //                     // }

    //                     foreach($serviceInfo as $row)
    //                     {
    //                         $totalLeads = round($row->total_lead * $leadPercent/100);
    //                         $serviceInfo = array(
    //                             'client_id'=>$request->client_id,
    //                             'comp_id'=>$request->comp_id,
    //                             'package_id'=>$packageInfoId,
    //                             'service_id'=>$row->service_id,
    //                             'category_id'=>$row->category_id,
    //                             'max_per_day'=>$row->max_per_day,
    //                             'total_lead'=>$row->total_lead + $totalLeads,
    //                             'balance_lead'=>$row->total_lead + $totalLeads,
    //                             'per_lead_price'=>$row->per_lead_price,
    //                             'per_lead_points'=>$row->per_lead_points,
    //                             'start_date'=>date('Y-m-d'),
    //                             'service_status'=>1,
    //                             'created_by'=>$request->created_by,
    //                         );
    //                         $serviceInfoId = DB::connection('sales_db')->table('service_info')->insertGetId($serviceInfo);
    //                     }

    //                     if($serviceInfoId)
    //                     {
    //                         $walletInfo = DB::connection('sales_db')->table('wallet_info')
    //                         ->select('wallet_id','client_id','balance_amount','debit_amount')
    //                         ->where('client_id',$request->client_id)
    //                         ->first();

    //                         $walletInfoArray =array(
    //                             'wallet_id'=>$walletInfo->wallet_id,
    //                             'client_id'=>$request->client_id,
    //                             'comp_id'=>$request->comp_id,
    //                             'transaction_mode'=>'Dr',
    //                             'transaction_id'=>$request->client_id.'-'.$request->comp_id.'-'.$walletInfo->wallet_id.'-'.$packageInfoId,
    //                             'debit'=>$request->depositAmount,
    //                             'balance'=>$walletInfo->balance_amount - $request->depositAmount,
    //                             'status'=>1,
    //                             'remarks'=>'Package created by wallet',
    //                             'transaction_date'=>date('Y-m-d H:i'),
    //                             'created_by'=>$request->created_by
    //                         );
                            
    //                         $historyInsert = DB::connection('sales_db')->table('wallet_history')->insertGetId($walletInfoArray);

    //                         if($historyInsert)
    //                         {
    //                             $walletinfoUpdate = array(
    //                                 'debit_amount'=>(float)$walletInfo->debit_amount + (float)$request->depositAmount,
    //                                 'balance_amount'=>(float)$walletInfo->balance_amount - (float)$request->depositAmount
    //                             );
    //                             $walletInfoUpdate = DB::connection('sales_db')->table('wallet_info')
    //                             ->where('wallet_id',$walletInfo->wallet_id)
    //                             ->update($walletinfoUpdate);
                                
    //                             if($walletInfoUpdate)
    //                             {
    //                                 $paymentInsert = array(
    //                                     'client_id'=>$request->client_id,
    //                                     'comp_id'=>$request->comp_id,
    //                                     'wallet_history_id'=>$historyInsert,
    //                                     'exe_id'=>$request->created_by,
    //                                     'paid_amount'=>$request->depositAmount,
    //                                     'tax_amount'=>$request->taxAmount,
    //                                     'reg_amount'=>$request->reg_amount,
    //                                     'package_id'=>$packageInfoId,
    //                                     'product_id'=>$packageInfo->product_id,
    //                                     'service_id'=>$packageInfo->service_id,
    //                                     'payment_for'=>$request->payment_for,
    //                                     'status'=>1,
    //                                     'created_by'=>$request->created_by,
    //                                 );

    //                                 $paymentInsertId = DB::connection('sales_db')->table('payment_history')->insertGetId($paymentInsert);
                                    
    //                                 if($paymentInsertId)
    //                                 {
    //                                     if($companyInfo->gst_state == 'Haryana'|| $companyInfo->gst_state == 'haryana' || $companyInfo->gst_state=='6')
    //                                     {
    //                                         $txtType=1;
    //                                     }
    //                                     else
    //                                     {
    //                                         $txtType=2;
    //                                     }

                                        
    //                                     $invoiceInfo = array(
    //                                         'pay_id'=>$paymentInsertId,
    //                                         'package_name'=>$packageInfo->package_name,
    //                                         'package_duration'=>$packageInfo->package_duration == 1 ? 'Lead Based' : 'Unlimited',
    //                                         'client_id'=>$request->client_id,
    //                                         'comp_id'=>$request->comp_id,
    //                                         'wallet_history_id'=>$historyInsert,
    //                                         'exe_id'=>$request->created_by,
    //                                         'package_amount'=>$request->payblePackageAmount,
    //                                         'due_amount'=>$request->balanceAmount,
    //                                         'paid_amount'=>$request->depositAmount,
    //                                         'tax_amount'=>$request->taxAmount,
    //                                         'reg_amount'=>$request->reg_amount,
    //                                         'package_id'=>$packageInfoId,
    //                                         'total_lead'=>$packageInfo->total_lead,
    //                                         'product_id'=>$packageInfo->product_id,
    //                                         'service_id'=>$packageInfo->service_id,
    //                                         'category_id'=>$packageInfo->category_id,
    //                                         'payment_for'=>$request->clientType,
    //                                         'business_name'=>$companyInfo->business_name,
    //                                         'email'=>$companyInfo->email,
    //                                         'mobile_no'=>$companyInfo->mobile_no,
    //                                         'is_verified_email'=>$companyInfo->is_verified_email,
    //                                         'gst_state'=>$companyInfo->gst_state,
    //                                         'executive_name'=>$companyInfo->emp_name,
    //                                         'gstNumber'=>$companyInfo->gstNumber,
    //                                         'taxType'=>$txtType,
    //                                         'status'=>1,
    //                                         //'admin_status'=>$adminStatus,
    //                                         'created_by'=>$request->created_by,
    //                                     );
                                        
    //                                     $retuenData = ClientInvoiceController::genrate_new_invoice($packageInfoId,$request->client_id,$invoiceInfo);
                                   
    //                                     if($retuenData ==1)
    //                                     {
    //                                         return response()->json(["messages"=>'Package created successfully.','status'=>200,'data'=>$package_info]);
    //                                     }
    //                                     else
    //                                     {
    //                                         return response()->json(["messages"=>'Something wrong with invoice creation','status'=>201]);
    //                                     }
    //                                 }
    //                                 else
    //                                 {
    //                                     return response()->json(["messages"=>'Something went wrong on payment history .','status'=>201]);
    //                                 }
    //                             }
    //                             else
    //                             {
    //                                 return response()->json(["messages"=>'Something went wrong on update wallet info.','status'=>201]);
    //                             }
    //                         }
    //                         else
    //                         {
    //                             return response()->json(["messages"=>'Something went wrong on update wallet history added.','status'=>201]);
    //                         }
    //                     }
    //                     else
    //                     {
    //                         return response()->json(["messages"=>'Something went wrong on package service.','status'=>201]);
    //                     }
    //             }
    //             else
    //             {
    //                 return response()->json(["messages"=>'Something went wrong on package creation.','status'=>201]);
    //             }
    //         }
    // }
    public function buy_new_package(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'client_id' => 'required',
            'package_id' => 'required',
            'depositAmount' => 'required',
            'balanceAmount' => 'required',
            'dueLead' => 'required',
            'taxAmount' => 'required',
            'comp_id'=>'required',
            'payment_for'=>'required',
            'cities_id'=>'required',
            'created_by'=>'required',
            'isPartialPayment'=>'required',
            ]);
        
            if($validator->fails())
            {
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);
            }

            $wallet_info = DB::connection('sales_db')->table('wallet_info')->where('client_id',$request->client_id)->first();
            if($request->isPartialPayment==1)
            {
                $paidAmount = $request->depositAmount / 2;
            }
            else
            {
                $paidAmount = $request->depositAmount;           
            }
            
            if($wallet_info->balance_amount < $paidAmount)
            {
                return response()->json(["messages"=>'Insufficient Balance','status'=>201]);
            }
            else
            {
                $packageInfo = DB::connection('sales_db')->table('pre_package')->where('id',$request->package_id)->first();

                $companyInfoGet = DB::connection('sales_db')
                    ->table('company_info')
                    ->select(
                        'company_info.address',
                        'company_info.business_name',
                        'company_info.email',
                        'company_info.mobile_no',
                        'company_info.is_verified_email',
                        'company_info.gst_state',
                        'company_info.exe_id',
                        'company_info.group_id',
                        'document_info.doc_number as gstNumber'
                    )
                    ->leftJoin('document_info', function ($join) {
                        $join->on('document_info.comp_id', '=', 'company_info.comp_id')
                            ->where('document_info.doc_type_id', '=', 4);
                    })
                    ->where('company_info.comp_id', $request->comp_id)
                    ->get();
                    

                $companyInfoGet->map(function ($item)
                {
                    $empName = DB::table('emp_basic_info')
                        ->selectRaw("GROUP_CONCAT(emp_fname,' ',emp_lame) as emp_name")
                        ->where('emp_id', $item->exe_id)
                        ->first();
                    $item->emp_name = $empName->emp_name;
                    return $item;
                });

                $companyInfo = $companyInfoGet->first();
                
                $cityDetailsAccording = DB::connection('sales_db')
                    ->table('locality')
                    ->selectRaw('GROUP_CONCAT(locality_id) as locality_id')
                    ->where('state_id', $request->group_id)
                    ->first();

                
                $citiesId = explode(',', $request->cities_id);

                $locality = DB::connection('sales_db')->table('rsms_city')
                    ->selectRaw('GROUP_CONCAT(city_id) as localityid')
                    ->whereIn('locality_id', $citiesId)
                    ->first();
               
                
                if($request->discountType == 2)
                {
                    $totalLeads = $request->afterDiscountLead;
                }
                else
                {
                    $totalLeads = $packageInfo->total_lead;
                }

                if($request->isPartialPayment ==1)
                {$dueStatus=1;}else{$dueStatus=0;}
                //address package_radius
                $todayDate = date('Y-m-d');
                if($request->isPartialPayment ==1)
                {
                    $dueStatus=1;
                    $dueDate = Carbon::createFromFormat('Y-m-d',$todayDate)->addDays(7);
                }
                else
                {
                    $dueStatus=0;
                    $dueDate ='';
                }
                
                $package_info = array(
                    'product_id'=>$packageInfo->product_id,
                    'client_id'=>$request->client_id,
                    'comp_id'=>$request->comp_id,
                    'pre_package_id'=>$packageInfo->id,
                    'package_duration'=>$packageInfo->package_duration,
                    'package_name'=>$packageInfo->package_name,
                    'service_id'=>$packageInfo->service_id,
                    'category_id'=>$packageInfo->category_id,
                    'package_amount'=>$request->payblePackageAmount,
                    'paid_amount'=>$request->depositAmount - $request->reg_amount,
                    'tax_amount'=>$request->taxAmount,
                    'due_amount'=>$request->balanceAmount,
                    'registration_amount'=>round($request->reg_amount / 1.18),
                    'total_lead'=>$totalLeads,
                    'to_location'=>$packageInfo->to_location,
                    'due_date'=>$dueDate,
                    'due_lead'=>$request->dueLead,
                    'due_status'=>$dueStatus,
                    'package_status'=>0,
                    'package_type'=>$request->payment_for,
                    'admin_status'=>0,
                    'finance_status'=>0,
                    'package_start_date'=>date('Y-m-d H:i'),
                    'package_end_expected_date'=>Carbon::createFromFormat('Y-m-d',$todayDate)->addDays(30),
                    'group_id'=>$request->group_id,
                    'city_id'=>$request->cities_id,
                    'package_category'=>$packageInfo->package_category,
                    'locality_id'=>$locality->localityid,
                    'credit_point'=>floor(($request->depositAmount - $request->reg_amount) / 0.55),
                    //'base_location'=>$companyInfo->address,
                    'exe_id'=>$request->created_by,
                    'created_by'=>$request->created_from,
                    //for tempo package
                    //'package_radius'=>$request->selectedRedius,
                    //'min_distance'=>$packageInfo->min_distance,
                    //'max_distance'=>$packageInfo->max_distance,
                    //'package_select'=>$packageInfo->package_select,
                    'base_location'=>$request->baseLocation,
                    'base_lat'=>$request->fromLat,
                    'base_lon'=>$request->fromLng,
                );
                
                $packageInfoId = DB::connection('sales_db')->table('package_info')->insertGetId($package_info);
                if($request->checkedOffers !='')
                {
                    if ($request->discountType == 1) {
                        $discountValue = round($request->payblePackageAmount /1.18);;
                        $beforeValue = round($packageInfo->total_amount /1.18);
                    } else {
                        $discountValue = $request->afterDiscountLead;
                        $beforeValue = $packageInfo->total_lead;
                    }
                    
                    // Correctly extract values from arrays
                    $checkedOffers = is_array($request->checkedOffers) ? $request->checkedOffers[$checkedOffers] : $request->checkedOffers;
                    $selectedPercent = is_array($request->selectedPercent) ? $request->selectedPercent[$checkedOffers] : $request->selectedPercent;
                    
                    $offerDetails = [
                        'offer_id' => $checkedOffers,
                        'client_id' => $request->client_id,
                        'comp_id' => $request->comp_id,
                        'product_id' => $packageInfo->product_id,
                        'service_id' => $packageInfo->service_id,
                        'category_id' => $packageInfo->category_id,
                        'current_package_id' => $packageInfoId,
                        'pre_package_id' => $request->package_id,
                        'discount_type' => $request->discountType,
                        'discount_type_id' => $selectedPercent,
                        'before_discount_value' => $beforeValue,
                        'after_discount_value' => $discountValue,
                        'created_by' => $request->created_from,
                        'exe_id' => $request->created_by,
                    ];
                    
                    // Insert into the database and get the inserted ID
                    $offerDetailsId = DB::connection('sales_db')->table('discount_grab_info')->insertGetId($offerDetails);
                }

                
                if($packageInfoId)
                {
                    $addtionalInfo = DB::connection('sales_db')->table('package_category_type_assign')
                    ->where('cat_id',$packageInfo->package_category)
                    ->where('product_id',$packageInfo->product_id)
                    ->where('type_status',1)
                    ->get();
                 
                    if(count($addtionalInfo) > 0)
                    {
                        if($request->banner_city_status == '1')
                        {
                            //$bannerCities = null;
                            $bannerCities = DB::connection('sales_db')
                            ->table('banner_city')
                            ->select('title', 'id', 'country_id', 'state_id')
                            ->where('id', $request->banner_city_id)
                            ->first();
                            $country_id = $bannerCities->country_id;
                            $city_id = $bannerCities->id;
                            $city_name = $bannerCities->title;
                        }
                        else
                        {
                            $country_id = 0;
                            $city_id = 0;
                            $city_name = '';
                        }
                    
                       
                        foreach ($addtionalInfo as $addInfo)
                        {
                            $addArray = [
                                'product_id' => $packageInfo->product_id,
                                'client_id' => $request->client_id,
                                'comp_id' => $request->comp_id,
                                'package_id' => $packageInfoId,
                                'category_type_id' => $addInfo->type_id,
                                'service_id' => $packageInfo->service_id,
                                'country_id' => $country_id,
                                'city_id' => $city_id,
                                'city_name' => $city_name,
                                'amount_paid' => 0,
                                'package_total_amt' => 0,
                                'package_start_date' => date('Y-m-d'),
                                'package_expire_date' => Carbon::createFromFormat('Y-m-d', $todayDate)->addDays(30),
                                'package_type' => $request->payment_for,
                                'package_status' => 1,
                                'executive_id' => $request->created_by,
                            ];
                    
                            
                            DB::connection('sales_db')
                                ->table('package_additional_service_info')
                                ->insertGetId($addArray);
                        }
                    }
    
                    

                    $serviceInfo = DB::connection('sales_db')->table('pre_package_service')->where('pre_package_id',$request->package_id)->get();

                        if($request->discountType == 2)
                        {
                            $lPercent = DB::connection('sales_db')->table('offer_discount_rate')
                            ->select('discount_percent')->where('id',$selectedPercent)->first();
                            $leadPercent=$lPercent->discount_percent;
                        }
                        else
                        {
                            $leadPercent=0;
                        }
                        
                        foreach($serviceInfo as $row)
                        {
                            $totalLeads = round($row->total_lead * $leadPercent/100);
                            $serviceInfo = array(
                                'client_id'=>$request->client_id,
                                'comp_id'=>$request->comp_id,
                                'package_id'=>$packageInfoId,
                                'service_id'=>$row->service_id,
                                'category_id'=>$row->category_id,
                                'max_per_day'=>$row->max_per_day,
                                'total_lead'=>$row->total_lead + $totalLeads,
                                'balance_lead'=>$row->total_lead + $totalLeads,
                                'per_lead_price'=>$row->per_lead_price,
                                'per_lead_points'=>$row->per_lead_points,
                                'start_date'=>date('Y-m-d'),
                                'service_status'=>1,
                                'created_by'=>$request->created_by,
                            );
                            $serviceInfoId = DB::connection('sales_db')->table('service_info')->insertGetId($serviceInfo);
                        }

                        if($serviceInfoId)
                        {
                            $walletInfo = DB::connection('sales_db')->table('wallet_info')
                            ->select('wallet_id','client_id','balance_amount','debit_amount')
                            ->where('client_id',$request->client_id)
                            ->first();

                            $walletInfoArray =array(
                                'wallet_id'=>$walletInfo->wallet_id,
                                'client_id'=>$request->client_id,
                                'comp_id'=>$request->comp_id,
                                'transaction_mode'=>'Dr',
                                'transaction_id'=>$request->client_id.'-'.$request->comp_id.'-'.$walletInfo->wallet_id.'-'.$packageInfoId,
                                'debit'=>$request->depositAmount,
                                'balance'=>$walletInfo->balance_amount - $request->depositAmount,
                                'status'=>1,
                                'remarks'=>'Package created by wallet',
                                'transaction_date'=>date('Y-m-d H:i'),
                                'created_by'=>$request->created_by
                            );
                            
                            $historyInsert = DB::connection('sales_db')->table('wallet_history')->insertGetId($walletInfoArray);

                            if($historyInsert)
                            {
                                $walletinfoUpdate = array(
                                    'debit_amount'=>(float)$walletInfo->debit_amount + (float)$request->depositAmount,
                                    'balance_amount'=>(float)$walletInfo->balance_amount - (float)$request->depositAmount
                                );
                                $walletInfoUpdate = DB::connection('sales_db')->table('wallet_info')
                                ->where('wallet_id',$walletInfo->wallet_id)
                                ->update($walletinfoUpdate);
                                
                                if($walletInfoUpdate)
                                {
                                    $paymentInsert = array(
                                        'client_id'=>$request->client_id,
                                        'comp_id'=>$request->comp_id,
                                        'wallet_history_id'=>$historyInsert,
                                        'exe_id'=>$request->created_by,
                                        'paid_amount'=>$request->depositAmount,
                                        'tax_amount'=>$request->taxAmount,
                                        'reg_amount'=>$request->reg_amount,
                                        'package_id'=>$packageInfoId,
                                        'product_id'=>$packageInfo->product_id,
                                        'service_id'=>$packageInfo->service_id,
                                        'payment_for'=>$request->payment_for,
                                        'status'=>1,
                                        'created_by'=>$request->created_by,
                                    );

                                    $paymentInsertId = DB::connection('sales_db')->table('payment_history')->insertGetId($paymentInsert);
                                    
                                    if($paymentInsertId)
                                    {
                                        if($companyInfo->gst_state == 'Haryana'|| $companyInfo->gst_state == 'haryana' || $companyInfo->gst_state=='6')
                                        {
                                            $txtType=1;
                                        }
                                        else
                                        {
                                            $txtType=2;
                                        }

                                        
                                        $invoiceInfo = array(
                                            'pay_id'=>$paymentInsertId,
                                            'package_name'=>$packageInfo->package_name,
                                            'package_duration'=>$packageInfo->package_duration == 1 ? 'Lead Based' : 'Unlimited',
                                            'client_id'=>$request->client_id,
                                            'comp_id'=>$request->comp_id,
                                            'wallet_history_id'=>$historyInsert,
                                            'exe_id'=>$request->created_by,
                                            'package_amount'=>$request->payblePackageAmount,
                                            'due_amount'=>$request->balanceAmount,
                                            'paid_amount'=>$request->depositAmount,
                                            'tax_amount'=>$request->taxAmount,
                                            'reg_amount'=>$request->reg_amount,
                                            'package_id'=>$packageInfoId,
                                            'total_lead'=>$packageInfo->total_lead,
                                            'product_id'=>$packageInfo->product_id,
                                            'service_id'=>$packageInfo->service_id,
                                            'category_id'=>$packageInfo->category_id,
                                            'payment_for'=>$request->clientType,
                                            'business_name'=>$companyInfo->business_name,
                                            'email'=>$companyInfo->email,
                                            'mobile_no'=>$companyInfo->mobile_no,
                                            'is_verified_email'=>$companyInfo->is_verified_email,
                                            'gst_state'=>$companyInfo->gst_state,
                                            'executive_name'=>$companyInfo->emp_name,
                                            'gstNumber'=>$companyInfo->gstNumber,
                                            'taxType'=>$txtType,
                                            'status'=>1,
                                            //'admin_status'=>$adminStatus,
                                            'created_by'=>$request->created_by,
                                        );
                                        
                                        $retuenData = ClientInvoiceController::genrate_new_invoice($packageInfoId,$request->client_id,$invoiceInfo);
                                   
                                        if($retuenData ==1)
                                        {
                                            return response()->json(["messages"=>'Package created successfully.','status'=>200,'data'=>$package_info]);
                                        }
                                        else
                                        {
                                            return response()->json(["messages"=>'Something wrong with invoice creation','status'=>201]);
                                        }
                                    }
                                    else
                                    {
                                        return response()->json(["messages"=>'Something went wrong on payment history .','status'=>201]);
                                    }
                                }
                                else
                                {
                                    return response()->json(["messages"=>'Something went wrong on update wallet info.','status'=>201]);
                                }
                            }
                            else
                            {
                                return response()->json(["messages"=>'Something went wrong on update wallet history added.','status'=>201]);
                            }
                        }
                        else
                        {
                            return response()->json(["messages"=>'Something went wrong on package service.','status'=>201]);
                        }
                }
                else
                {
                    return response()->json(["messages"=>'Something went wrong on package creation.','status'=>201]);
                }
            }
    }

    public function package_due_payment(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'client_id' => 'required',
            'package_id' => 'required',
            'depositAmount' => 'required',
            'balanceAmount' => 'required',
            'taxAmount' => 'required',
            'payment_for'=>'required',
            'created_by'=>'required',
           ]);
        
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);
        }

        $wallet_info = DB::connection('sales_db')->table('wallet_info')->where('client_id',$request->client_id)->first();

        if($wallet_info->balance_amount < $request->depositAmount)
        {
            return response()->json(["messages"=>'Insufficient Balance','status'=>201]);
        }
        else
        {
            $packageInfo = DB::connection('sales_db')->table('package_info')->where('package_id',$request->package_id)->first();

            $walletInfo = DB::connection('sales_db')->table('wallet_info')
            ->select('wallet_id','client_id','balance_amount','debit_amount')
            ->where('client_id',$request->client_id)
            ->first();

            $walletInfoArray =array(
                'wallet_id'=>$walletInfo->wallet_id,
                'client_id'=>$request->client_id,
                'comp_id'=>$request->comp_id,
                'transaction_mode'=>'Dr',
                'debit'=>$request->depositAmount,
                'balance'=>$walletInfo->balance_amount - $request->depositAmount,
                'status'=>1,
                'remarks'=>'Due amount paid by wallet',
                'transaction_date'=>date('Y-m-d H:i'),
                'created_by'=>$request->created_by
            );
                
            $historyInsert = DB::connection('sales_db')->table('wallet_history')->insertGetId($walletInfoArray);

            if($historyInsert)
            {
                $walletinfoUpdate = array(
                    'debit_amount'=>(float)$walletInfo->debit_amount + (float)$request->depositAmount,
                    'balance_amount'=>(float)$walletInfo->balance_amount - (float)$request->depositAmount
                );
                $walletInfoUpdate = DB::connection('sales_db')->table('wallet_info')
                ->where('wallet_id',$walletInfo->wallet_id)
                ->update($walletinfoUpdate);
                
                if($walletInfoUpdate)
                {
                    $paymentInsert = array(
                        'client_id'=>$request->client_id,
                        'comp_id'=>$packageInfo->comp_id,
                        'wallet_history_id'=>$historyInsert,
                        'exe_id'=>$request->created_by,
                        'paid_amount'=>$request->depositAmount,
                        'tax_amount'=>$request->taxAmount,
                        'package_id'=>$request->package_id,
                        'product_id'=>$packageInfo->product_id,
                        'service_id'=>$packageInfo->service_id,
                        'payment_for'=>$request->payment_for,
                        'status'=>1,
                        'created_by'=>'1',
                    );

                    $paymentInsertId = DB::connection('sales_db')->table('payment_history')->insertGetId($paymentInsert);
                    
                    if($paymentInsertId)
                    {
                        $companyInfoGet = DB::connection('sales_db')
                        ->table('company_info')
                        ->select(
                            'company_info.address',
                            'company_info.business_name',
                            'company_info.email',
                            'company_info.mobile_no',
                            'company_info.is_verified_email',
                            'company_info.gst_state',
                            'company_info.exe_id',
                            'document_info.doc_number as gstNumber'
                        )
                        ->leftJoin('document_info', function ($join) {
                            $join->on('document_info.comp_id', '=', 'company_info.comp_id')
                                ->where('document_info.doc_type_id', '=', 4);
                        })
                        ->where('company_info.comp_id', $packageInfo->comp_id)
                        ->get();
    
                        $companyInfoGet->map(function ($item) {
                            $empName = DB::table('emp_basic_info')
                                ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")
                                ->where('emp_id', $item->exe_id)
                                ->first();
                            $item->emp_name = $empName->emp_name;
                            return $item;
                        });
        
                        $companyInfo = $companyInfoGet->first();

                        if($companyInfo->gst_state == 'Haryana' || $companyInfo->gst_state == 'haryana' || $companyInfo->gst_state == '6')
                        {
                            $txtType=1;
                        }
                        else
                        {
                            $txtType=2;
                        }
    
                        $invoiceInfo = array(
                            'pay_id'=>$paymentInsertId,
                            'package_name'=>$packageInfo->package_name,
                            'package_duration'=>$packageInfo->package_duration == 1 ? 'Lead Based' : 'Unlimited',
                            'client_id'=>$packageInfo->client_id,
                            'comp_id'=>$packageInfo->comp_id,
                            'wallet_history_id'=>$historyInsert,
                            'exe_id'=>$request->created_by,
                            'package_amount'=>$packageInfo->package_amount,
                            'old_paid_amount'=>$packageInfo->paid_amount,
                            'old_tax_amount'=>$packageInfo->tax_amount,
                            'due_amount'=>$request->due_amount,
                            'paid_amount'=>$request->depositAmount,
                            'tax_amount'=>$request->taxAmount,
                            'reg_amount'=>$request->regAmount,
                            'package_id'=>$request->package_id,
                            'total_lead'=>$packageInfo->total_lead,
                            'product_id'=>$packageInfo->product_id,
                            'service_id'=>$packageInfo->service_id,
                            'payment_for'=>$request->payment_for,
                            'business_name'=>$companyInfo->business_name,
                            'email'=>$companyInfo->email,
                            'mobile_no'=>$companyInfo->mobile_no,
                            'is_verified_email'=>$companyInfo->is_verified_email,
                            'gst_state'=>$companyInfo->gst_state,
                            'executive_name'=>$companyInfo->emp_name,
                            'gstNumber'=>$companyInfo->gstNumber,
                            'taxType'=>$txtType,
                            'taxType'=>2,
                            'status'=>1,
                            'category_id'=>$packageInfo->category_id,
                            
                        );
                        $retuenData = ClientInvoiceController::genrate_new_invoice($request->package_id,$request->client_id,$invoiceInfo);
                    
                        if($retuenData ==1)
                        {
                            return response()->json(["messages"=>'Due amount paid successfully.','status'=>200,'data'=>$invoiceInfo]);
                        }
                        else
                        {
                            return response()->json(["messages"=>'Something wrong with invoice creation','status'=>201]);
                        }
                    }
                    else
                    {
                        return response()->json(["messages"=>'Something went wrong on payment history .','status'=>201]);
                    }
                }
                else
                {
                    return response()->json(["messages"=>'Something went wrong on update wallet info.','status'=>201]);
                }
            }
            else
            {
                return response()->json(["messages"=>'Something went wrong on update wallet history added.','status'=>200]);
            }
        }
    }

   

    public function get_pre_package_list($productId,$cityId,$serviceId)
    {
       $packageInfo = DB::connection('sales_db')->table('pre_package')
        ->select('package_price','id','package_name')
        ->whereRaw('FIND_IN_SET(?, group_id)', [$cityId])
        ->whereRaw('FIND_IN_SET(?, service_id)', [$serviceId])
        ->where(array('product_id'=>$productId,'package_status'=>1))
        ->get();
        
        if($packageInfo)
        {
            return response()->json(['status'=>200,'data'=>$packageInfo]);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }

    public function check_service_base_price_details($productId,$serviceId,$categoryId)
    {
       
        $basePriceInfo = DB::connection('sales_db')->table('lead_base_package')
        ->select('id')
        ->where(array('product_id'=>$productId,'service_id'=>$serviceId,'category_id'=>$categoryId))
        ->first();
        if($basePriceInfo)
        {
            return 1;
        }
        else
        {
            return 2;
        }
       
    }

    public function view_leadbased_package_details_by_id($id)
    {
        $basePriceInfo = DB::connection('sales_db')->table('lead_base_package')
        ->select('*')
        ->where('id',$id)
        ->first();

        return response()->json(['status'=>200,'message'=>'Lead Based Package details','data'=>$basePriceInfo]);
    }

    public function update_lead_package_factor(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            'base_price' => 'required',
            //'package_type' => 'required',
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
            'detailsId'=>'required',
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
            //'package_type'=>$request->package_type,
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
        $leadFactorId = DB::connection('sales_db')->table('lead_base_package')
        ->where('id',$request->detailsId)
        ->update($leadFactor);
        if($leadFactorId)
        {
            return response()->json(['status'=>200,'message'=>'Lead Bas Package Factor Updated Successfully.']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }

    public function check_service_base_price_details_update($productId,$serviceId,$categoryId,$package_id)
    {
        
        $basePriceInfo = DB::connection('sales_db')->table('lead_base_package')
        ->select('id')
        ->where('id','!=',$package_id)
        ->where(array('product_id'=>$productId,'service_id'=>$serviceId,'category_id'=>$categoryId))
        ->first();
        if($basePriceInfo)
        {
            return 1;
        }
        else
        {
            return 2;
        }
        
    }

    public function view_leadbased_factor_details_by_id($id)
    {
        $basePriceInfo = DB::connection('sales_db')->table('lead_base_location_condition')
        ->select('*')
        ->where('id',$id)
        ->first();
        return response()->json(['status'=>200,'message'=>'Lead Based Factor details','data'=>$basePriceInfo]);
    }

    public function view_unlimited_factor_details_by_id($id)
    {
        $basePriceInfo = DB::connection('sales_db')->table('unlimited_location_condition')
        ->select('*')
        ->where('id',$id)
        ->first();
        return response()->json(['status'=>200,'message'=>'unlimited Factor details','data'=>$basePriceInfo]);
    }

    public function update_lead_location_price(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            'group_id' => 'required',
            'locality_id' => 'required',
            'location_price' => 'required',
            'leadbaseFactorId'=> 'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $updateLocationPrice = array(
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            'category_for'=>$request->category_id,
            'include_group'=>$request->group_id,
            'requried_childs'=>$request->locality_id,
            'condition_value'=>$request->location_price,
            'created_by'=>$request->created_by
        );


        $updateLocationDetails = DB::connection('sales_db')->table('lead_base_location_condition')
        ->where('id',$request->leadbaseFactorId)
        ->update($updateLocationPrice);
       
        if($updateLocationDetails)
        {
            return response()->json(['status'=>200,'message'=>'Leadbased location factor updated  successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }
    
    public function check_unlimited_service_base_price_details($productId,$serviceId,$categoryId)
    {
       
        $basePriceInfo = DB::connection('sales_db')->table('unlimited_package')
        ->select('id')
        ->where(array('product_id'=>$productId,'service_id'=>$serviceId,'category_id'=>$categoryId))
        ->first();
        if($basePriceInfo)
        {
            return 1;
        }
        else
        {
            return 2;
        }
        
    }

    public function check_unlimited_service_base_price_details_update($productId,$serviceId,$categoryId,$package_id)
    {
        $basePriceInfo = DB::connection('sales_db')->table('unlimited_package')
        ->select('id')
        ->where('id','!=',$package_id)
        ->where(array('product_id'=>$productId,'service_id'=>$serviceId,'category_id'=>$categoryId))
        ->first();
        if($basePriceInfo)
        {
            return 1;
        }
        else
        {
            return 2;
        }
    }

    public function view_unlimited_package_details_by_id($id)
    {
        $basePriceInfo = DB::connection('sales_db')->table('unlimited_package')
        ->select('*')
        ->where('id',$id)
        ->first();
        return response()->json(['status'=>200,'message'=>'Unlimited Package details','data'=>$basePriceInfo]);
    }

    public function update_unlimited_package_factor(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            //'package_type'=>'required',
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
            'id'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $unlimitedFactor = array(
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            //'package_type'=>$request->package_type,
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
        $updateId = DB::connection('sales_db')->table('unlimited_package')
        ->where('id',$request->id)
        ->update($unlimitedFactor);
        if($updateId)
        {
            return response()->json(['status'=>200,'message'=>'Unlimited package factor updated successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }

    public function update_unlimited_location_price(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            //'category_id' => 'required',
            'group_id' => 'required',
            'locality_id' => 'required',
            'location_price' => 'required',
            'created_by'=>'required',
            'id'=>'required',
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
        $unlimitedLocationPrice = DB::connection('sales_db')->table('unlimited_location_condition')
        ->where('id',$request->id)
        ->update($unlimitedLocationPrice);
        if($unlimitedLocationPrice)
        {
            return response()->json(['status'=>200,'message'=>'Unlimited location details updated successfully']);
        }
        else
        {
            return response()->json(['status'=>500,'message'=>'something went wrong']);
        }
    }

    public function check_attribute_details(Request $request)
    {
        $attributeInfo = DB::connection('sales_db')->table('product_attribute')
        ->select('id')
        ->where(array('product_id'=>$request->productId,'service_id'=>$request->serviceId,'attribute_name'=>$request->attributeName))->first();
        if($attributeInfo)
        {
           return 1; 
        }
        else
        {
           return 2;
        }
    }

    public function get_category_list_by_id($productId,$serviceId)
    {
        $categoryList = DB::connection('sales_db')->table('product_category')
        ->select('id as value','category_name as label')
        ->where(array('product_id'=>$productId,'service_id'=>$serviceId))->get();
        
        return response()->json(['status'=>200,'message'=>'Category listing according to service','data'=>$categoryList]);
    }

    public function get_attribute_list_by_id($productId,$serviceId,$categoryId)
    {
        $attributeList = DB::connection('sales_db')->table('product_attribute')
        ->select('id as value','attribute_name as label')
        ->where(array('product_id'=>$productId,'service_id'=>$serviceId,'category_id'=>$categoryId))->get();
        
        return response()->json(['status'=>200,'message'=>'Attribute listing according to product service and category','data'=>$attributeList]);
    }

    public function add_attribute_details(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            'category_id' => 'required',
            'new_attribute_name'=> 'required',
            'created_by'=>'required',
        ]);

        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        

        $categoryList = explode(',',$request->category_id);
        foreach($categoryList as $row)
        {
            $attributeArray = array(
                'product_id'=>$request->product_id,
                'service_id'=>$request->service_id,
                'category_id'=>$row,
                'attribute_name'=>$request->new_attribute_name,
                'created_by'=>$request->created_by,
            );
            $attributeId = DB::connection('sales_db')->table('product_attribute')->insertGetId($attributeArray);
        }
        

        if($attributeId)
        {
            return response()->json(['status'=>200,'message'=>'Attribute added successfully']);
        }
        else
        {
            return response()->json(['status'=>200,'message'=>'Something went wrong. Please try again.']);
        }
    }

    public function get_attribute_list()
    {
        $attributeList = DB::connection('sales_db')->table('product_attribute')
        ->select('product.product_name','product_service.service_name','product_attribute.*')
        ->leftjoin('product','product.id','=','product_attribute.product_id')
        ->leftjoin('product_service','product_service.id','=','product_attribute.service_id')
        ->get();
        
        return response()->json(['status'=>200,'message'=>'Category Factor List','data'=>$attributeList]);
    }

    public function add_attribute_price(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'product_id' => 'required',
            'service_id' => 'required',
            'category_id' => 'required',
            'attribute_id'=> 'required',
            'attribute_price'=>'required',
            'created_by'=>'required',
        ]);

        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $attributeList = explode(',',$request->attribute_id);
        foreach($attributeList as $row)
        {
            $attributeArray = array(
                'product_id'=>$request->product_id,
                'service_id'=>$request->service_id,
                'category_id'=>$request->category_id,
                'attribute_id'=>$row,
                'attribute_price'=>$request->attribute_price,
                'created_by'=>$request->created_by,
            );
            $attributeId = DB::connection('sales_db')->table('attribute_info')->insertGetId($attributeArray);
        }
        

        if($attributeId)
        {
            return response()->json(['status'=>200,'message'=>'Attribute added successfully']);
        }
        else
        {
            return response()->json(['status'=>200,'message'=>'Something went wrong. Please try again.']);
        }
    }

    public function get_attribute_price_details()
    {
        $attributeList = DB::connection('sales_db')->table('attribute_info')
        ->select('product.product_name','product_service.service_name','product_category.category_name','product_attribute.attribute_name','attribute_info.*')
        ->leftjoin('product','product.id','=','attribute_info.product_id')
        ->leftjoin('product_service','product_service.id','=','attribute_info.service_id')
        ->leftjoin('product_category','product_category.id','=','attribute_info.category_id')
        ->leftjoin('product_attribute','product_attribute.id','=','attribute_info.attribute_id')
        ->get();
        return response()->json(['status'=>200,'message'=>'Attribute List','data'=>$attributeList]);
    }

    public function update_category_status($id,$status)
    {
        if($status ==1)
        {
            $updateStatus=0;
        }
        else
        {
            $updateStatus=1;
        }
        $updateCategoryStatus = DB::connection('sales_db')->table('product_category')
        ->where('id',$id)
        ->update(array('status'=>$updateStatus));
        
        if($updateCategoryStatus)
        {
            return response()->json(['status'=>200,'message'=>'Category status updated successfully']);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong.Please try again']);
        }   
    }


    public function update_attribute_status($id,$status)
    {
        if($status ==1)
        {
            $updateStatus=0;
        }
        else
        {
            $updateStatus=1;
        }
        $updateCategoryStatus = DB::connection('sales_db')->table('product_attribute')
        ->where('id',$id)
        ->update(array('status'=>$updateStatus));
        
        if($updateCategoryStatus)
        {
            return response()->json(['status'=>200,'message'=>'Atytribute status updated successfully']);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }   
    }

    public function update_package_category_status($id,$status)
    {
        if($status ==1)
        {
            $updateStatus=0;
        }
        else
        {
            $updateStatus=1;
        }
        $updateCategoryStatus = DB::connection('sales_db')->table('package_category')
        ->where('id',$id)
        ->update(array('status'=>$updateStatus));
        
        if($updateCategoryStatus)
        {
            return response()->json(['status'=>200,'message'=>'Package Category status updated successfully']);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong.Please try again']);
        }   
    }


    public function update_pre_package_status($id,$status)
    {
        if($status ==1)
        {
            $updateStatus=0;
        }
        else
        {
            $updateStatus=1;
        }
        $updateCategoryStatus = DB::connection('sales_db')->table('pre_package')
        ->where('id',$id)
        ->update(array('package_status'=>$updateStatus));
        
        if($updateCategoryStatus)
        {
            return response()->json(['status'=>200,'message'=>'Package status updated successfully']);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong.Please try again']);
        }   
    }

    public function get_bank_details_listing()
    {
        $bankDetails = DB::connection('sales_db')->table('bank_info')
        ->select('id','bank_name')
        ->where('status',1)
        ->get();
        return response()->json(['status'=>200,'message'=>'Bank Details','data'=>$bankDetails]);
    }
}
