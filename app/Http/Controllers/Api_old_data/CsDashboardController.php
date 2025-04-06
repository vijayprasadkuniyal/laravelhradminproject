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
        $startTime = '09:00:00';
        $endTime = '20:00:00';
        $count = 0;
        $total_time = 0;
    
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
        $total_executive = $cs_verfied_return_per_executive->distinct('verified_by')->count();
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
        ->where('cs_verified',1)->whereNotIn('source_type',['TF','Adword','Oragnic'])
        $new_verified =  DB::connection('sales_db')->table('enquiry_info')
        ->where('cs_verified',1);

       
    
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
            $new_verified->where('product_id', $productId);





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
            $social_media_enquiry->where('group_id',$groupId);
            $new_verified->where('group_id',$groupId);



            
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
            $new_verified->where('service_id', $serviceId);
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
             $new_verified->whereDate('created_date', '>=', $startDate)
            ->whereDate('created_date', '<=', $endDate);

            
            
        } else {
            $currentDate = Carbon::now()->format('Y-m-d');
            $query->whereDate('created_date', $currentDate);
            $total_enquiry_zoopgo->whereDate('created_date', $currentDate);
            $total_sent_enquiry_in_zoopgo->whereDate('created_date', $currentDate);
            $total_enquiry_lmart->whereDate('created_date', $currentDate);
            $total_sent_enquiry_in_lmart->whereDate('created_date', $currentDate);
            $cs_verfied_return_per_executive->whereDate('created_at', $currentDate);
            $pakage_pending_return->whereDate('created_at', $currentDate);
            $lead_sale->whereDate('created_date', $currentDate);
            $total_cs_collection->whereDate('created_date', $currentDate);
            $float->whereDate('created_date', $currentDate);
            $call_leads->whereDate('created_date', $currentDate);
            $social_media_enquiry->whereDate('created_date', $currentDate);
            $new_verified->whereDate('created_date', $currentDate);
        }
    
       
        $total_enquiry_zoopgo_count = $total_enquiry_zoopgo->count();
        $total_sent_enquiry_in_zoopgo_count = $total_sent_enquiry_in_zoopgo->count();
        $total_enquiry_lmart_count = $total_enquiry_lmart->count();
        $total_sent_enquiry_in_lmart_count = $total_sent_enquiry_in_lmart->count();
        $total_return_lead = $cs_verfied_return_per_executive->count();
        $pending_return_lead = $pakage_pending_return->count();
        $total_lead_sale = $lead_sale->sum('sent_count');
        $total_collection = $total_cs_collection->sum('sent_value');
        $total_float =  $float->count();
        $total_call_leads =  $call_leads->count();
        $total_social_media_enquiry = $social_media_enquiry->count();

    
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
            'social_media_enquiry'=>$total_social_media_enquiry,
        ];
    
        return response()->json(['status' => 200, 'data' => $data]);
    }
}
    
