<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

class CsDashboardController extends Controller
{
    public function cs_dashboard_show_count(Request $request)
    {
        //return $request->all();
        $startTime = '09:00:00';
        $endTime = '20:00:00';
        $count = 0;
        $total_time = 0;
        $call_count = 0;
        $escalation_count = 0;
    
        $query = DB::connection('sales_db')
            ->table('enquiry_info')
            ->where('cs_verified', 1)
            ->whereRaw('TIME(created_date) BETWEEN ? AND ?', [$startTime, $endTime]);
    
        $total_enquiry_zoopgo = DB::connection('sales_db')->table('enquiry_info')
            ->where('cs_verified', 1)->where('product_id', 2);
    
        $total_sent_enquiry_in_zoopgo = DB::connection('sales_db')->table('enquiry_info')
            ->where('cs_verified', 1)->where('product_id', 2)->where('enq_status', 1);
    
        $total_enquiry_lmart = DB::connection('sales_db')->table('enquiry_info')
            ->where('cs_verified', 1)->where('product_id', 1);
    
        $total_sent_enquiry_in_lmart = DB::connection('sales_db')->table('enquiry_info')
            ->where('cs_verified', 1)->where('product_id', 1)->where('enq_status', 1);
        $cs_verfied_return_per_executive = DB::connection('sales_db')->table('return_lead_info');


        $total_executive =  DB::connection('sales_db')->table('return_lead_info')->distinct('verified_by')->count();
        $pakage_pending_return = DB::connection('sales_db')->table('return_lead_info')->where('status',0);
        $lead_sale = DB::connection('sales_db')->table('enquiry_info')
        ->where('cs_verified', 1)->where('enq_status', 1);
        $total_cs_collection = DB::connection('sales_db')->table('enquiry_info')
        ->where('cs_verified', 1);
        $float =  DB::connection('sales_db')->table('enquiry_info')
        ->where('cs_verified', 0)->where('otp_verified',0);
        $call_leads =  DB::connection('sales_db')->table('enquiry_info')
        ->where('cs_verified',1)->where('source_type','TF');
        $social_media_enquiry =  DB::connection('sales_db')->table('enquiry_info')
        ->where('cs_verified',1)->whereNotIn('source_type',['TF','Organic','Adword']); 
        $return_verified = DB::connection('sales_db')->table('return_lead_info')->where('status','!=',0);
        $call_missed_lmart =  DB::connection('sales_db')->table('tata_lm_tollfree_incoming')->where('call_status','missed');
        $call_missed_zoopgo =  DB::connection('sales_db')->table('tata_zoopgo_tollfree_incoming')->where('call_status','missed');
        $call_escalation_lmart =  DB::connection('sales_db')->table('tata_lm_tollfree_incoming')->where('call_identity',2);

        $call_escalation_zoopgo =  DB::connection('sales_db')->table('tata_zoopgo_tollfree_incoming')->where('call_identity',2);
       
    
        if ($request->product) {
            $productId = $request->input('product');
            $query->where('product_id', $productId);
            $total_enquiry_zoopgo->where('product_id', $productId);
            $total_sent_enquiry_in_zoopgo->where('product_id', $productId);
            $total_enquiry_lmart->where('product_id', $productId);
            $total_sent_enquiry_in_lmart->where('product_id', $productId);
            $cs_verfied_return_per_executive->where('product_id', $productId);
            $pakage_pending_return->where('product_id', $productId);
            $lead_sale->where('product_id', $productId);
            $total_cs_collection->where('product_id', $productId);
            $float->where('product_id', $productId);
            $call_leads->where('product_id', $productId);
            $social_media_enquiry->where('product_id', $productId);
            $return_verified->where('product_id', $productId);
       }
    
        if($request->group) {
            $groupId = $request->input('group');
            $query->where('group_id', $groupId);
            $total_enquiry_zoopgo->where('group_id', $groupId);
            $total_sent_enquiry_in_zoopgo->where('group_id', $groupId);
            $total_enquiry_lmart->where('group_id', $groupId);
            $total_sent_enquiry_in_lmart->where('group_id', $groupId);
            $enq_ids = DB::connection('sales_db')->table('enquiry_info')->where('group_id', $groupId)
            ->pluck('id')->toArray();
            $cs_verfied_return_per_executive->whereIn('enq_id',$enq_ids);
            $pakage_pending_return->whereIn('enq_id',$enq_ids);
            $lead_sale->where('group_id', $groupId);
            $total_cs_collection->where('group_id', $groupId);
            $float->where('group_id', $groupId);
            $call_leads->where('group_id', $groupId);
            $social_media_enquiry->where('group_id', $groupId);
            $return_verified->whereIn('enq_id', $enq_ids);



            
        }
    
        if ($request->service) {
            $serviceId = $request->input('service');
            $query->where('service_id', $serviceId);
            $total_enquiry_zoopgo->where('service_id', $serviceId);
            $total_sent_enquiry_in_zoopgo->where('service_id', $serviceId);
            $total_enquiry_lmart->where('service_id', $serviceId);
            $total_sent_enquiry_in_lmart->where('service_id', $serviceId);
            $cs_verfied_return_per_executive->where('service_id', $serviceId);
            $pakage_pending_return->where('service_id', $serviceId);
            $lead_sale->where('service_id', $serviceId);
            $total_cs_collection->where('service_id', $serviceId);
            $float->where('service_id', $serviceId);
            $call_leads->where('service_id', $serviceId);
            $social_media_enquiry->where('service_id', $serviceId);
            $return_verified->where('service_id', $serviceId);
        }

        if($request->category){
                $categoryId = $request->input('category');
                $query->where('category_id',  $categoryId);
                $total_enquiry_zoopgo->where('category_id', $categoryId);
                $total_sent_enquiry_in_zoopgo->where('category_id', $categoryId);
                $total_enquiry_lmart->where('category_id', $categoryId);
                $total_sent_enquiry_in_lmart->where('category_id', $categoryId);
                $cs_verfied_return_per_executive->where('category_id', $categoryId);
                $pakage_pending_return->where('category_id', $categoryId);
                $lead_sale->where('category_id', $categoryId);
                $total_cs_collection->where('category_id', $categoryId);
                $float->where('category_id', $categoryId);
                $call_leads->where('category_id', $categoryId);
                $social_media_enquiry->where('category_id', $categoryId);
                $return_verified->where('category_id', $categoryId);



        }

        if($request->emp){
             $call_leads->where('verified_by',$request->emp);
             $total_cs_collection->where('verified_by',$request->emp);
             $lead_sale->where('verified_by',$request->emp);
             $return_verified->where('verified_by',$request->emp);
             $query->where('verified_by',$request->emp);




        }
    
        if ($request->start_date && $request->end_date) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $query->whereDate('created_date', '>=', $startDate)
                  ->whereDate('created_date', '<=', $endDate);
            $total_enquiry_zoopgo->whereDate('created_date', '>=', $startDate)
                                 ->whereDate('created_date', '<=', $endDate);
            $total_sent_enquiry_in_zoopgo->whereDate('created_date', '>=', $startDate)
                                         ->whereDate('created_date', '<=', $endDate);
            $total_enquiry_lmart->whereDate('created_date', '>=', $startDate)
                                 ->whereDate('created_date', '<=', $endDate);
            $total_sent_enquiry_in_lmart->whereDate('created_date', '>=', $startDate)
                                        ->whereDate('created_date', '<=', $endDate);
            $cs_verfied_return_per_executive->whereDate('created_at', '>=', $startDate)
                                            ->whereDate('created_at', '<=', $endDate);
            $pakage_pending_return->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);
            $lead_sale->whereDate('created_date', '>=', $startDate)
            ->whereDate('created_date', '<=', $endDate);
            $total_cs_collection->whereDate('created_date', '>=', $startDate)
            ->whereDate('created_date', '<=', $endDate);
            $float->whereDate('created_date', '>=', $startDate)
            ->whereDate('created_date', '<=', $endDate);
            $call_leads->whereDate('created_date', '>=', $startDate)
            ->whereDate('created_date', '<=', $endDate);
            $social_media_enquiry->whereDate('created_date', '>=', $startDate)
            ->whereDate('created_date', '<=', $endDate);
            $return_verified->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);
             $call_missed_lmart->whereDate('start_stamp', '>=', $startDate)
            ->whereDate('start_stamp', '<=', $endDate);
             $call_missed_zoopgo->whereDate('start_stamp', '>=', $startDate)
            ->whereDate('start_stamp', '<=', $endDate);
             $call_escalation_lmart->whereDate('start_stamp', '>=', $startDate)
            ->whereDate('start_stamp', '<=', $endDate);
            $call_escalation_zoopgo->whereDate('start_stamp', '>=', $startDate)
            ->whereDate('start_stamp', '<=', $endDate);



            
            
        } else {
            $currentmonth = Carbon::now()->month;
            $currentyear = Carbon::now()->year;

            $query->whereMonth('created_date', $currentmonth)
                  ->whereYear('created_date',$currentyear);
                  
            $total_enquiry_zoopgo->whereMonth('created_date', $currentmonth)
            ->whereYear('created_date',$currentyear);  

            $total_sent_enquiry_in_zoopgo->whereMonth('created_date', $currentmonth)
            ->whereYear('created_date',$currentyear);

            $total_sent_enquiry_in_lmart->whereMonth('created_date', $currentmonth)
            ->whereYear('created_date',$currentyear);

            $cs_verfied_return_per_executive->whereMonth('created_at', $currentmonth)
            ->whereYear('created_at',$currentyear);

            $lead_sale->whereMonth('created_date', $currentmonth)
            ->whereYear('created_date',$currentyear);

            $total_cs_collection->whereMonth('created_date', $currentmonth)
            ->whereYear('created_date',$currentyear);

            $call_leads->whereMonth('created_date', $currentmonth)
            ->whereYear('created_date',$currentyear);
            $social_media_enquiry->whereMonth('created_date', $currentmonth)
            ->whereYear('created_date',$currentyear);

            $return_verified->whereMonth('created_at', $currentmonth)
            ->whereYear('created_at',$currentyear);
            $pakage_pending_return->whereMonth('created_at', $currentmonth)
            ->whereYear('created_at',$currentyear);

            $float->whereMonth('created_date', $currentmonth)
            ->whereYear('created_date',$currentyear);

              $call_missed_lmart->whereMonth('start_stamp', $currentmonth);
              $call_missed_zoopgo->whereYear('start_stamp',$currentyear);

            $call_escalation_lmart->whereMonth('start_stamp', $currentmonth);
            $call_escalation_zoopgo->whereYear('start_stamp',$currentyear);

        }

        if($request->product ==1){
            $call_count =  $call_missed_lmart->count();
            $escalation_count = $call_escalation_lmart->count();
        }
        elseif($request->product ==2){
              $call_count =  $call_missed_zoopgo->count();
              $escalation_count =  $call_escalation_zoopgo->count();

        }
        else{
            $call_count =  $call_missed_lmart->count() + $call_missed_zoopgo->count();
            $escalation_count =  $call_escalation_lmart->count() + $call_escalation_zoopgo->count();

        }
    
       
        $total_enquiry_zoopgo_count = $total_enquiry_zoopgo->count();
        $total_sent_enquiry_in_zoopgo_count = $total_sent_enquiry_in_zoopgo->count();
        $total_enquiry_lmart_count = $total_enquiry_lmart->count();
        $total_sent_enquiry_in_lmart_count = $total_sent_enquiry_in_lmart->count();
        $total_return_lead = $cs_verfied_return_per_executive->count();
        $pending_return_lead = $pakage_pending_return->count();
        $total_lead_sale = $lead_sale->sum('sent_count');
        $total_collection = round($total_cs_collection->sum('sent_value'));
        $total_float =  $float->count();
        $total_call_leads =  $call_leads->count();
        $total_social_media_count = $social_media_enquiry->count();
        $new_verified = $total_cs_collection->count();
        $return_verified_count =  $return_verified->count();

    
        $zoopgo_sent_percent = $total_sent_enquiry_in_zoopgo_count > 0 
            ?
              round($total_sent_enquiry_in_zoopgo_count / $total_enquiry_zoopgo_count * 100) 

            : 0;
    
        $lmart_sent_percent = $total_sent_enquiry_in_lmart_count > 0 
            ? 
              round($total_sent_enquiry_in_lmart_count / $total_enquiry_lmart_count * 100) 
 
            : 0;
        if($total_executive>0){
            $per_executive_return_data = round($total_return_lead/$total_executive);

        }
        else{
            $per_executive_return_data = 0;

        }
    
        $data = $query->get();
        foreach ($data as $row) {
            if ($row->verifed_date) {
                $createdAt = $row->created_date;
                $verifiedAt = $row->verifed_date;
                $diffInMinutes = Carbon::parse($createdAt)->diffInMinutes(Carbon::parse($verifiedAt));
                $count++;
                $total_time += $diffInMinutes;
            }
        }
    
        $total_average_time = $count > 0 
            ? round($total_time / $count) 
            : 0;
    
        $data = [
            'verified_time_average' => round($total_average_time / 60) . ' Hours',
            'lmart_percent' => $lmart_sent_percent,
            'zoopgo_percent' => $zoopgo_sent_percent,
            'per_executive_return'=>$per_executive_return_data,
            'pending_return_lead'=>$pending_return_lead,
            'lead_sale'=>$total_lead_sale,
            'collection'=> $total_collection,
            'float'=>$total_float,
            'call_leads'=>$total_call_leads,
            'social_media_count'=>$total_social_media_count,
            'new_verified'=>$new_verified,
            'return_verified'=>$return_verified_count,
            'call_count'=>$call_count,
            'escalation_count'=>$escalation_count,
        ];
    
        return response()->json(['status' => 200, 'data' => $data]);
    }

    public function cs_employee(){
        $data = DB::table('emp_basic_info')->where('dept_id',4)->where('emp_status',1)->get(['emp_fname','emp_lame','emp_id']);

        return response()->json(['status'=>200,'data'=>$data]);

    }

    public function get_category_based_on_service_id($id){
        $data = DB::connection('sales_db')->table('product_category')->where('status',1)
               ->where('service_id',$id)->get(['id','category_name']);

        return response()->json(['status'=>200,'data'=>$data]);
    }

    public function get_current_day_lead_verification_details(Request $request){
        $data_array = [];
       $emp_details = DB::table('employee_managers')
        ->where(function($query) use ($request) {
            $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp]);
        })
        ->where('status', 1)
        ->where('dept_id',4)
        ->pluck('emp_id')
        ->toArray();
         $emp_details[] = $request->emp;
         $emp_details = array_unique($emp_details);
         
        $data = DB::connection('sales_db')->table('enquiry_info')->whereIn('verified_by', $emp_details);
        if($request->product){
             $data->where('product_id',$request->product);
            
        }
         if($request->group){
             $data->where('group_id',$request->group);
            
        }
        if($request->service){
             $data->where('service_id',$request->service);
            
        }
        if($request->category){
             $data->where('category_id',$request->category);
            
        }
        if($request->start_date && $request->end_date){
             $data->whereDate('verifed_date', '>=', $request->start_date)
            ->whereDate('verifed_date', '<=',$request->end_date);
            
            
        }
        else{
           $data->whereDate('verifed_date',Carbon::now()->format('Y-m-d'));
            
        }
        
        $query =  $data->paginate(10);
        
        foreach($query as $row){
            $group = DB::connection('sales_db')->table('group_names')->where('group_id',$row->group_id)->first();
            $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();
            $service = DB::connection('sales_db')->table('product_service')->where('id',$row->service_id)->first();
            $category = DB::connection('sales_db')->table('product_category')->where('id',$row->category_id)->first();
            $customer = DB::connection('sales_db')->table('customer_info')->where('id',$row->customer_id)->first();
                $createdAt =  $row->created_date;
                $verifiedAt =    $row->verifed_date;
                $diffInMinutes = Carbon::parse($createdAt)->diffInMinutes(Carbon::parse($verifiedAt));
                $verified_by = DB::table('emp_basic_info')->where('emp_id',$row->verified_by)->first();
                 $attempts = DB::connection('sales_db')->table('enquiry_attempt_log')
                 ->where('enq_id', $row->id)
                 ->select('attempted_date', 'remarks', 'exe_id', 'followup_id')
                 ->get();
                  $exeIds = $attempts->pluck('exe_id')->unique();
                  $followupIds = $attempts->pluck('followup_id')->unique();

                  $employees = DB::table('emp_basic_info')
                 ->whereIn('emp_id', $exeIds)
                 ->select('emp_id', DB::raw("CONCAT(emp_fname, ' ', emp_lame) as emp_name"))
                 ->get()
                 ->keyBy('emp_id');

                  $followups = DB::connection('sales_db')->table('enq_status')
                  ->whereIn('id', $followupIds)
                  ->select('id', 'name')
                  ->get()
                  ->keyBy('id');

                   $formattedAttempts = $attempts->map(function ($attempt) use ($employees, $followups) {
                     $empName = $employees[$attempt->exe_id]->emp_name ?? 'Unknown';
                    $followupName = $followups[$attempt->followup_id]->name ?? 'No Follow-up';
                      return "{$attempt->attempted_date} - {$followupName} ({$empName})";
                   })->implode("<br>");
                
                $data_array[] = array('id'=>$row->id,'product'=>$product->product_name??'','group'=>$group->name??'','service'=>$service->service_name??'','category'=> $category->category_name??'','customer'=>$customer->name??'','created_date'=>$row->created_date??'','verified_date'=>$row->verifed_date??'','emp_fname'=> $verified_by->emp_fname??'','emp_lname'=>$verified_by->emp_lame??'','time_gap'=>$diffInMinutes??'','no_of_attempts'=>$row->no_of_attempt,'attempts' => strip_tags($formattedAttempts));
            
            
        }
        return response()->json(['status'=>200,'data'=> $data_array,'last_page'=>$query->lastPage()]);
        
        
        
    }
}
    
