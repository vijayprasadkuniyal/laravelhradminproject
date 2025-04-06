<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Response;

class AppApiController extends Controller
{
    public function save_opt_template(Request $request){
     $input = $request->all();
     $validator = Validator::make($input, [
        'api_key' => 'required',
        'template'=>'required',
        'message' => 'required',
        'use_as'=>'required',
        'product' => 'required',
        'otp_for'=>'required',
     ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }

      $data  = array('apikey'=>$request->api_key,'sender'=>$request->sender,
      'template_id'=>$request->template_id,'template'=>$request->template,
      'message'=>$request->message,'use_as'=>$request->use_as,
      'product_id'=>$request->product,'otp_for'=>$request->otp_for,'remarks'=>$request->remark);
      DB::connection('sales_db')->table('otp_template')->insert($data);
      return response()->json(['status'=>200,'message'=>'Template Saved Successfully']);


    }
    public function otp_template_list(){
        $data = DB::connection('sales_db')->table('otp_template')->join('product','product.id','otp_template.product_id')
               ->select(
                'otp_template.*',
                'product.product_name',
                DB::raw("CASE WHEN otp_template.otp_for = 1 THEN 'vendor' ELSE 'customer' END AS otp_for_type"),
                DB::raw("CASE WHEN otp_template.use_as = 1 THEN 'App' ELSE 'Site' END AS use_as_type "),
                 )
               ->paginate(10);
        return response()->json(['status'=>200,'data'=>$data,'last_page'=> $data->lastPage()]);

    }

    public function edit_otp_template($id){
      $data =  DB::connection('sales_db')->table('otp_template')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);

    }

    public function update_otp_template(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
         'api_key' => 'required',
         'template'=>'required',
         'message' => 'required',
         'use_as'=>'required',
         'product' => 'required',
         'otp_for'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
        $data  = array('apikey'=>$request->api_key,'sender'=>$request->sender,
       'template_id'=>$request->template_id,'template'=>$request->template,
       'message'=>$request->message,'use_as'=>$request->use_as,
       'product_id'=>$request->product,'otp_for'=>$request->otp_for,'remarks'=>$request->remark);
        DB::connection('sales_db')->table('otp_template')->where('id',$request->id)->update($data);
        return response()->json(['status'=>200,'message'=>'Template Updated Successfully']);

    }
    public function get_otp_template_status($id){
      $data = DB::connection('sales_db')->table('otp_template')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data->status]);
    }
    public function update_otp_template_status(Request $request){
      DB::connection('sales_db')->table('otp_template')->where('id',$request->id)->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);


    }

    public function get_all_client_reports(Request $request)
{
    $input = $request->all();
    $validator = Validator::make($input, [
        // 'emp_id' => 'required'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'messages' => $validator->messages(),
            'status' => 400
        ]);
    }

    $groupId = $request->group;
    $productId = $request->product;
    $enqStatus = 1; // Assuming static value for now
    $fromDate = "2024-05-20";
    $toDate = $request->to_date
        ? Carbon::parse($request->to_date)->format('Y-m-d')
        : Carbon::now()->endOfMonth()->format('Y-m-d');

    $enqInfo = DB::connection('sales_db')->table('enquiry_info as ei')
        ->select(
            'ei.sent_count',
            'ei.enq_status',
            'ei.expected_value',
            'ei.sent_value',
            'ei.event_date',
            'ei.otp_verified',
            'ei.cs_verified',
            'ei.created_date',
            'gm.name as group_name',
            'p.product_name as product_name',
            'ps.service_name',
            'pc.category_name'
        )
        ->leftJoin('customer_info as ci', 'ci.id', '=', 'ei.customer_id')
        ->leftJoin('group_names as gm', 'gm.group_id', '=', 'ei.group_id')
        ->leftJoin('product as p', 'p.id', '=', 'ei.product_id')
        ->leftJoin('product_service as ps', 'ps.id', '=', 'ei.service_id')
        ->leftJoin('product_category as pc', 'ps.id', '=', 'ei.category_id')
        ->whereBetween('ei.created_date', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59'])
        ->when($groupId, function ($query) use ($groupId) {
            $query->where('ei.group_id', $groupId);
        })
        ->when($productId, function ($query) use ($productId) {
            $query->where('ei.product_id', $productId);
        })
        ->when($enqStatus, function ($query) use ($enqStatus) {
            $query->where('ei.enq_status', $enqStatus);
        })
        ->limit(50)
        ->get();

    $csvData = [];

    // Header row
    $csvData[] = [
        'Sent Count', 'Enquiry Status',
        'Expected Value', 'Sent Value', 'Event Date', 'OTP Verified',
        'CS Verified', 'Created Date', 'Group Name', 'Product Name',
        'Service Name', 'Category Name'
    ];

    // Data rows
    foreach ($enqInfo as $enquiry) {
        $csvData[] = [
            $enquiry->sent_count,
            $enquiry->enq_status,
            $enquiry->expected_value,
            $enquiry->sent_value,
            $enquiry->event_date,
            $enquiry->otp_verified ? 'Yes' : 'No',
            $enquiry->cs_verified ? 'Yes' : 'No',
            $enquiry->created_date,
            $enquiry->group_name,
            $enquiry->product_name,
            $enquiry->service_name,
            $enquiry->category_name
        ];
    }

    // Generate CSV file content
    $filename = "enquiry_reports_" . date('YmdHis') . ".csv";
    $handle = fopen('php://output', 'w');

    // Set the headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Write data to CSV
    foreach ($csvData as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);
}

    public function app_version_add(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
         'client_type' => 'required',
         'product'=>'required',
         'version' => 'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }

    $check_alredy_exists_version = DB::connection('sales_db')->table('app_version')->where('product_id',$request->product)
      ->where('client_type',$request->client_type)->exists();
    if(!$check_alredy_exists_version){
      $data = array('client_type'=>$request->client_type,'product_id'=>$request->product,
      'version_id'=>$request->version,'created_by'=>$request->emp_id);
      DB::connection('sales_db')->table('app_version')->insert($data);
      return response()->json(['status'=>200,'message'=>'Version Added Successfully']);
    }
    else{
      return response()->json(['status'=>201,'message'=>'Version Already Exists For This Product']);
    }
 

    }

    public function app_version_list(){
      $data = DB::connection('sales_db')->table('app_version')->join('product','product.id','app_version.product_id')
            ->select('app_version.version_id',
                     'product.product_name',
                     'app_version.id',
                     DB::raw("CASE WHEN app_version.client_type = 0 THEN 'Vendor/Client' ELSE 'Customer' END AS otp_version_type"),
        )
        ->paginate(10);
      return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);

    }
    public function edit_app_version($id){
      $data =  DB::connection('sales_db')->table('app_version')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);

    }

    public function update_app_version(Request $request){
      $input = $request->all();
      //return $input;
      $validator = Validator::make($input, [
         'client_type' => 'required',
         'product'=>'required',
         'version' => 'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }

      $data = array('client_type'=>$request->client_type,'product_id'=>$request->product,
      'version_id'=>$request->version);

      DB::connection('sales_db')->table('app_version')->where('id',$request->id)->update($data);

      return response()->json(['status'=>200,'message'=>'Version Updated Successfully']);
 

    }

    public function add_item_type(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
           'product' => 'required',
           'service'=>'required',
           'item_type' => 'required',
        ]);
         if($validator->fails()){
               $messages=$validator->messages();
               return response()->json(["messages"=>$messages,'status'=>400]);     
         }
  
      $check_alredy_exists_item = DB::connection('sales_db')->table('product_item_type')->where('product_id',$request->product)
        ->where('service_id',$request->service)->where('item_type',$request->item_type)->exists();
      if(!$check_alredy_exists_item){
        $data = array('product_id'=>$request->product,'service_id'=>$request->service,
        'item_type'=>$request->item_type,'created_by'=>$request->emp_id);

        DB::connection('sales_db')->table('product_item_type')->insert($data);
        return response()->json(['status'=>200,'message'=>'Item Type Added Successfully']);
      }
      else{
        return response()->json(['status'=>201,'message'=>'Type Already Exists For This Product']);
      }

    }

    public function item_type_list(){
      $data = DB::connection('sales_db')->table('product_item_type')->join('product','product.id','product_item_type.product_id')
            ->join('product_service','product_service.id','product_item_type.service_id')
            ->select('product_item_type.id','product_item_type.item_type',
                'product_item_type.status','product.product_name','product_service.service_name')
            ->paginate(10);
           return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);

    }

    public function item_type_edit($id){
      $data = DB::connection('sales_db')->table('product_item_type')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);

    }

    public function item_type_update(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
         'product' => 'required',
         'service'=>'required',
         'item_type' => 'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
      $data = array('product_id'=>$request->product,'service_id'=>$request->service,
      'item_type'=>$request->item_type,'created_by'=>$request->emp_id);

      DB::connection('sales_db')->table('product_item_type')->where('id',$request->id)->update($data);
      return response()->json(['status'=>200,'message'=>'Item Type Updated Successfully']);

    }
    public function update_item_type_status(Request $request){
      DB::connection('sales_db')->table('product_item_type')->where('id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);


    }
    public function get_active_item_type($id){
      $data = DB::connection('sales_db')->table('product_item_type')->where('service_id',$id)->where('status',1)->get(['id','item_type']);
      return response()->json(['status'=>200,'data'=>$data]);
    }

    public function add_item_info(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
         'product' => 'required',
         'service'=>'required',
         'item_type' => 'required',
         'item_name'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }

    $check_alredy_exists_item = DB::connection('sales_db')->table('item_info')->where('product_id',$request->product)
      ->where('service_id',$request->service)->where('type_id',$request->item_type)->where('item_name',$request->item_name)->exists();
    if(!$check_alredy_exists_item){
      $data = array('product_id'=>$request->product,'service_id'=>$request->service,
      'type_id'=>$request->item_type,'created_by'=>$request->emp_id,'item_name'=>$request->item_name);

      DB::connection('sales_db')->table('item_info')->insert($data);
      return response()->json(['status'=>200,'message'=>'Item  Added Successfully']);
    }
    else{
      return response()->json(['status'=>201,'message'=>' Item  Already Exists For This Product And Service']);
    }


    }
    public function item_list(){
      $data = DB::connection('sales_db')->table('item_info')->join('product','product.id','item_info.product_id')
      ->join('product_service','product_service.id','item_info.service_id')
      ->join('product_item_type','product_item_type.id','item_info.type_id')
      ->select('item_info.id','item_info.item_name',
          'item_info.status','product.product_name','product_service.service_name','product_item_type.item_type')
        ->paginate(10);
       return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);

    }
    public function item_details($id){
      $data =  DB::connection('sales_db')->table('item_info')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);

    }

    public function item_update(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
         'product' => 'required',
         'service'=>'required',
         'item_type' => 'required',
         'item_name'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
      $data = array('product_id'=>$request->product,'service_id'=>$request->service,
      'type_id'=>$request->item_type,'created_by'=>$request->emp_id,'item_name'=>$request->item_name);

      DB::connection('sales_db')->table('item_info')->where('id',$request->id)->update($data);
      return response()->json(['status'=>200,'message'=>'Item  Updated Successfully']);
    }


    public function item_status_update(Request $request){
      DB::connection('sales_db')->table('item_info')->where('id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);

    }

    public function add_transport_charge_type(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'charge_type'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('charge_type'=>$request->charge_type,'created_by'=>$request->emp_id);
       DB::connection('sales_db')->table('transport_charges_type')->insert($data);
       return response()->json(['status'=>200,'message'=>'Charge Type Saved Successfully']);

    }

    public function transport_charges_type_list(){
      $data = DB::connection('sales_db')->table('transport_charges_type')->paginate(10);
      return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);

    }

    public function transport_charges_type_details($id){
      $data = DB::connection('sales_db')->table('transport_charges_type')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);

    }
    public function transport_charges_type_update(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'charge_type'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('charge_type'=>$request->charge_type,'created_by'=>$request->emp_id);
       DB::connection('sales_db')->table('transport_charges_type')->where('id',$request->id)->update($data);
       return response()->json(['status'=>200,'message'=>'Charge Type Updated Successfully']);

    }

    public function transport_charges_type_status_update(Request $request){
      DB::connection('sales_db')->table('transport_charges_type')->where('id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);

    }

    public function add_genric_change_type(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'product'=>'required',
        'title'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('product_id'=>$request->product,'created_by'=>$request->emp_id,'title'=>$request->title);
       DB::connection('sales_db')->table('genric_change_type')->insert($data);
       return response()->json(['status'=>200,'message'=>'Genric Type Saved Successfully']);


    }

    public function genric_type_list(){
      $data =  DB::connection('sales_db')->table('genric_change_type')->join('product','product.id','genric_change_type.product_id')
      ->select('genric_change_type.id','product.product_name','genric_change_type.title','genric_change_type.status')
      ->paginate(10);
     return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);
    }

    public function genric_type_details($id){
      $data =  DB::connection('sales_db')->table('genric_change_type')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);
    }

    public function generic_type_update(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'product'=>'required',
        'title'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('product_id'=>$request->product,'created_by'=>$request->emp_id,'title'=>$request->title);
       DB::connection('sales_db')->table('genric_change_type')->where('id',$request->id)->update($data);
       return response()->json(['status'=>200,'message'=>'Genric Type Updated Successfully']);

    }

    public function generic_type_status_update(Request $request){
      DB::connection('sales_db')->table('genric_change_type')->where('id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);

    }

    public function add_support_links(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'product'=>'required',
        'title'=>'required',
        'url'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('product_id'=>$request->product,'links_url'=>$request->url,'title'=>$request->title);
       DB::connection('sales_db')->table('support_links_info')->insert($data);
       return response()->json(['status'=>200,'message'=>'Links Saved Successfully']);

    }
    public function support_links_list(){
      $data =  DB::connection('sales_db')->table('support_links_info')->join('product','product.id','support_links_info.product_id')
      ->select('support_links_info.id','product.product_name','support_links_info.title',
      'support_links_info.status','support_links_info.links_url')
      ->paginate(10);
     return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);


    }
    public function support_links_details($id){
      $data =  DB::connection('sales_db')->table('support_links_info')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);

    }
    public function support_links_update(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'product'=>'required',
        'title'=>'required',
        'url'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('product_id'=>$request->product,'links_url'=>$request->url,'title'=>$request->title);
       DB::connection('sales_db')->table('support_links_info')->where('id',$request->id)->update($data);
       return response()->json(['status'=>200,'message'=>'Links Updated Successfully']);


    }
    public function support_links_status_update(Request $request){
      DB::connection('sales_db')->table('support_links_info')->where('id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);

    }

    public function add_locality(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'locality'=>'required',
        'state'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('state_id'=>$request->state,'locality_name'=>$request->locality);
       DB::connection('sales_db')->table('locality')->insert($data);
       return response()->json(['status'=>200,'message'=>'Locality Saved Successfully']);

    }

     public function locality_list() {
    // Use 'sales_db' for 'locality' table
    $data = DB::connection('sales_db')
            ->table('locality')
            ->leftJoin(
                DB::raw('hr_panel.state as state'), // Explicitly use the database name
                'state.id', '=', 'locality.state_id'
            )
            ->select('state.state_name', 'locality.locality_name', 'locality.locality_id', 'locality.status')
            ->paginate(10);

    return response()->json([
        'status' => 200,
        'data' => $data,
        'last_page' => $data->lastPage()
    ]);
}
    public function get_locality_details($id){
      $data =  DB::connection('sales_db')->table('locality')->where('locality_id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);
    }

    public function update_locality_details(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'locality'=>'required',
        'state'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('state_id'=>$request->state,'locality_name'=>$request->locality);
       DB::connection('sales_db')->table('locality')->where('locality_id',$request->id)->update($data);
       return response()->json(['status'=>200,'message'=>'Locality Updated  Successfully']);

    }
    public function update_locality_status(Request $request){
      DB::connection('sales_db')->table('locality')->where('locality_id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);

    }

    public function get_country_currency_type($id){
      $data = DB::table('country')->where('id',$id)->first();
      if($data->currency_type){
        $currency_type = $data->currency_type;
      }
      else{
        $currency_type  = '';

      }
      return response()->json(['status'=>200,'data'=>$currency_type]);

    }

    public function  save_base_point_info(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'product'=>'required',
        'country'=>'required',
        'per_point'=>'required',
        'per_point_price'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('country_id'=>$request->country,'product_id'=>$request->product,'per_point'=>$request->per_point,
       'currency_type'=>$request->currency_type,'per_point_price'=>$request->per_point_price,'created_by'=>$request->emp_id);
       DB::connection('sales_db')->table('base_point_info')->insert($data);
       return response()->json(['status'=>200,'message'=>'Base point Saved Successfully']);

    }

    public function base_points_list(){
      $data = DB::connection('sales_db')->table('base_point_info')->join('hr_panel.country','country.id','base_point_info.country_id')
              ->join('product','product.id','base_point_info.product_id')
              ->select('country.name','product.product_name','base_point_info.*')
              ->paginate(10);
     return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);

    }

    public function get_base_points_details($id){
      $data = DB::connection('sales_db')->table('base_point_info')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);
    }

    public function update_base_points(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'product'=>'required',
        'country'=>'required',
        'per_point'=>'required',
        'per_point_price'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('country_id'=>$request->country,'product_id'=>$request->product,'per_point'=>$request->per_point,
       'currency_type'=>$request->currency_type,'per_point_price'=>$request->per_point_price,'created_by'=>$request->emp_id);
        DB::connection('sales_db')->table('base_point_info')->where('id',$request->id)->update($data);
       return response()->json(['status'=>200,'message'=>'Base point Updated Successfully']);



    }

    public function update_base_points_status(Request $request){
      DB::connection('sales_db')->table('base_point_info')->where('id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);

    }

    public function add_source_from(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'product'=>'required',
        'source_from'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('product_id'=>$request->product,'source_name'=>$request->source_from,
       'created_by'=>$request->emp_id);
       DB::connection('sales_db')->table('source_from')->insert($data);
       return response()->json(['status'=>200,'message'=>'Source Saved Successfully']);

    }

    public function get_source_from_list(){
       $data = DB::connection('sales_db')->table('source_from')
      ->join('product','product.id','source_from.product_id')
      ->select('product.product_name','source_from.*')
      ->paginate(10);
      return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);


    }
    public function get_source_from_details($id){
      $data = DB::connection('sales_db')->table('source_from')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);
    }

    public function update_source_from(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'product'=>'required',
        'source_from'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('product_id'=>$request->product,'source_name'=>$request->source_from,
       'created_by'=>$request->emp_id);
       DB::connection('sales_db')->table('source_from')->where('id',$request->id)->update($data);
       return response()->json(['status'=>200,'message'=>'Source Updated Successfully']);

    }

    public function update_source_from_status(Request $request){
      DB::connection('sales_db')->table('source_from')->where('id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);

    }

    public function add_complaint_status(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'title'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('title'=>$request->title,
       'created_by'=>$request->emp_id);
       DB::connection('sales_db')->table('complaint_status')->insert($data);
       return response()->json(['status'=>200,'message'=>'Status Saved Successfully']);



    }

    public function get_complaint_list(){
      $data = DB::connection('sales_db')->table('complaint_status')->paginate(10);
      return response()->json(['status'=>200,'data'=>$data]);
    }

    public function get_complain_status_details($id){
      $data = DB::connection('sales_db')->table('complaint_status')->where('id',$id)->first();
      return response()->json(['data'=>$data,'status'=>200]);

    }

    public function complain_status_update(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'title'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('title'=>$request->title,
       'created_by'=>$request->emp_id);
        DB::connection('sales_db')->table('complaint_status')->where('id',$request->id)->update($data);
        return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);

    }

    public function complain_status_change(Request $request){
      DB::connection('sales_db')->table('complaint_status')->where('id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);

    }

    public function add_payment_gateway_info(Request $request){
      $paymentGatewayicons = '';
      $input = $request->all();
      $validator = Validator::make($input, [
        'product'=>'required',
        'gateway_name'=>'required',
        'merchant_id'=>'required',
        'merchant_name'=>'required',
        'merchant_key'=>'required',
        'salt'=>'required',
        'sort_order'=>'required',
        'icon'=>'required',
       ]);

       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }

       if ($request->hasFile('icon')) {
        $image = $request->file('icon');
        $destinationPath = 'payment_gateway_icons/';
        $iconImageName = date('YmdHis') . "." . $image->getClientOriginalExtension();
        $image->move(public_path($destinationPath), $iconImageName);
        $iconImageUrl = url($destinationPath . $iconImageName);
        $paymentGatewayicons = $iconImageUrl;
    }
    
       $data  = array('product_id'=>$request->product,'gateway_name'=>$request->gateway_name,
       'merchant_id'=>$request->merchant_id,'merchant_name'=>$request->merchant_name,
       'merchant_key'=>$request->merchant_key,'salt'=>$request->salt,
       'sort_order'=>$request->sort_order,'icon_url'=> $paymentGatewayicons);

       DB::connection('sales_db')->table('payment_gateway_info')->insert($data);
       return response()->json(['status'=>200,'message'=>'Gateway Added Successfully']);

    }

    public function get_payment_gateway_list(){
      $data = DB::connection('sales_db')->table('payment_gateway_info')
      ->join('product','product.id','payment_gateway_info.product_id')
      ->select('product.product_name','payment_gateway_info.*')
      ->paginate(10);
      return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);
    }

    public function get_payment_gateway_details($id){
      $data = DB::connection('sales_db')->table('payment_gateway_info')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);
    }

    public function update_payment_gateway(Request $request){
      //$paymentGatewayicons = '';
      $input = $request->all();
      $validator = Validator::make($input, [
        'product'=>'required',
        'gateway_name'=>'required',
        'merchant_id'=>'required',
        'merchant_name'=>'required',
        'merchant_key'=>'required',
        'salt'=>'required',
        'sort_order'=>'required',
       ]);

       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }

       if ($request->hasFile('icon')) {
        $image = $request->file('icon');
        $destinationPath = 'payment_gateway_icons/';
        $iconImageName = date('YmdHis') . "." . $image->getClientOriginalExtension();
        $image->move(public_path($destinationPath), $iconImageName);
        $iconImageUrl = url($destinationPath . $iconImageName);
        $paymentGatewayicons = $iconImageUrl;
        DB::connection('sales_db')->table('payment_gateway_info')->where('id',$request->id)
            ->update(['icon_url'=>$paymentGatewayicons]);

    }
    
       $data  = array('product_id'=>$request->product,'gateway_name'=>$request->gateway_name,
       'merchant_id'=>$request->merchant_id,'merchant_name'=>$request->merchant_name,
       'merchant_key'=>$request->merchant_key,'salt'=>$request->salt,
       'sort_order'=>$request->sort_order);

       DB::connection('sales_db')->table('payment_gateway_info')->where('id',$request->id)->update($data);
       return response()->json(['status'=>200,'message'=>'Gateway Updated Successfully']);

    }


    public function update_gateway_status(Request $request){
      DB::connection('sales_db')->table('payment_gateway_info')->where('id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);

    }
    public function update_gateway_app_status(Request $request){
      //return $request->all();
      DB::connection('sales_db')->table('payment_gateway_info')->where('id',$request->id)
      ->update(['app_status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'App Status Updated SUccessfully']);

    }
    public function get_active_service_list(){
      $data = DB::connection('sales_db')->table('product_service')->where('status',1)->get();
      return response()->json(['status'=>200,'data'=>$data]);
    }

    public function save_milestone_cancel_reason(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'service'=>'required',
        //'category'=>'required',
        'title'=>'required',
        'use_as'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('service_id'=>$request->service,'category_id'=>implode(' , ',$request->category_array),'title'=>$request->title,
       'use_as'=>$request->use_as,'created_by'=>$request->created_by);
       DB::connection('sales_db')->table('milestone_cancel_reason')->insert($data);
       return response()->json(['status'=>200,'message'=>'Reason Saved Successfully']);



    }
    public function get_milestone_cancel_reason_list(){
      $data = DB::connection('sales_db')->table('milestone_cancel_reason')->paginate(10);
      foreach($data as $row){
        $category_id = explode(',',$row->category_id);
        $category_names = DB::connection('sales_db')->table('product_category')
        ->whereIn('id',$category_id)
        ->pluck('category_name')
        ->implode(',');
        $service_name =  DB::connection('sales_db')->table('product_service')
                      ->where('id',$row->service_id)->first();
        if($service_name){
          $service_names = $service_name->service_name;

        }
        else{
          $service_names = '';

        }

        if($row->use_as == 0){
          $site = 'Customer Support';


        }
        else{
          $site = 'Vendor Side';

        }
        $data_array[] = array('id'=>$row->id,'service'=>$service_names,
        'category'=>$category_names,'title'=>$row->title,'use_as'=>$site,'status'=>$row->status);



      }
      return response()->json(['status'=>200,'data'=>$data_array,'last_page'=>$data->lastPage()]);

    }

    public function milestone_reason_details($id){
      $data = DB::connection('sales_db')->table('milestone_cancel_reason')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);

    }

    public function update_milestone_reason_details(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'service'=>'required',
        //'category'=>'required',
        'title'=>'required',
        'use_as'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('service_id'=>$request->service,'category_id'=>implode(' , ',$request->category_array),
       'title'=>$request->title,
       'use_as'=>$request->use_as,'created_by'=>$request->created_by);
       DB::connection('sales_db')->table('milestone_cancel_reason')->where('id',$request->id)->update($data);
       return response()->json(['status'=>200,'message'=>'Reason Updated Successfully']);
      
    }

    public function mileston_reason_status_update(Request $request){

      DB::connection('sales_db')->table('milestone_cancel_reason')->where('id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);
    }

    public function add_app_call_reason(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'reason'=>'required',
        'call_type'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('call_type'=>$request->call_type,'reason_name'=>$request->reason);

       DB::connection('sales_db')->table('app_call_log_reason')->insert($data);

       return response()->json(['status'=>200,'message'=>'Reason Saved Successfully']);


    }
    public function app_call_log_reason_list(){
      $data = DB::connection('sales_db')->table('app_call_log_reason')->paginate(10);
      return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);

    }

    public function app_call_log_reason_details($id){
      $data = DB::connection('sales_db')->table('app_call_log_reason')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);
    }

    public function app_call_log_reason_update(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'reason'=>'required',
        'call_type'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('call_type'=>$request->call_type,'reason_name'=>$request->reason);

       DB::connection('sales_db')->table('app_call_log_reason')->where('id',$request->id)->update($data);

       return response()->json(['status'=>200,'message'=>'Reason Updated Successfully']);

    }

    public function app_call_log_reason_status_update(Request $request){
      DB::connection('sales_db')->table('app_call_log_reason')->where('id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);


    }

    public function add_support_service(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'product'=>'required',
        'title'=>'required',
        'sort_order'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('product_id'=>$request->product,'title'=>$request->title,'created_by'=>$request->emp_id,
                'sort_order'=>$request->sort_order
                );

       DB::connection('sales_db')->table('support_services')->insert($data);

       return response()->json(['status'=>200,'message'=>'Added Successfully']);


    }

    public function get_support_service_list(){
      $data = DB::connection('sales_db')->table('support_services')
      ->join('product','product.id','support_services.product_id')
      ->select('product.product_name','support_services.*')
      ->paginate(10);
      return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);
    }

    public function get_support_service_details($id){
      $data = DB::connection('sales_db')->table('support_services')->where('id',$id)->first();
      return response()->json(['status'=>200,'data'=>$data]);
    }

    public function update_support_service(Request $request){
      $input = $request->all();
      $validator = Validator::make($input, [
        'product'=>'required',
        'title'=>'required',
        'sort_order'=>'required',
      ]);
       if($validator->fails()){
             $messages=$validator->messages();
             return response()->json(["messages"=>$messages,'status'=>400]);     
       }
 
       $data  = array('product_id'=>$request->product,'title'=>$request->title,'created_by'=>$request->emp_id,
       'sort_order'=>$request->sort_order);

       DB::connection('sales_db')->table('support_services')->where('id',$request->id)->update($data);

       return response()->json(['status'=>200,'message'=>'Updated  Successfully']);

    }

    public function update_support_service_status(Request $request){
      DB::connection('sales_db')->table('support_services')->where('id',$request->id)
      ->update(['status'=>$request->status]);
      return response()->json(['status'=>200,'message'=>'Status Updated SUccessfully']);


    }
    public function get_active_support_service(){
      $data = DB::connection('sales_db')->table('support_services')->where('status',1)->get(['id','title']);
      return response()->json(['status'=>200,'data'=>$data]);

    }




    
}
