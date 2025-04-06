<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DmDashboardController extends Controller
{
    protected $salesdb;

      public function __construct()
      {
         $this->salesdb = DB::connection('sales_db');
       }


       public function dm_dashboard_count(Request $request)
       {
        $count = 0;
           
           $total_enquiry = $this->salesdb->table('enquiry_info');
           $not_sent = $this->salesdb->table('enquiry_info')->where('enq_status',3);
           $organic_enquiry = $this->salesdb->table('enquiry_info')->where('source_type','Organic');
           $total_sent = $this->salesdb->table('enquiry_info')->where('enq_status',1);
           $lead_sale =  $this->salesdb->table('enquiry_info')->where('enq_status',1);
           $toll_free = $this->salesdb->table('enquiry_info')->where('source_type','TF');
           $package_over_running = $this->salesdb->table('package_info')->where('package_status',1);
           $lead_feedback = $this->salesdb->table('client_feedback');
           $sent_percent_zoopgo = $this->salesdb->table('enquiry_info')->where('product_id',2);
           $sent_percent_lmart = $this->salesdb->table('enquiry_info')->where('product_id',1);
           $per_day_required = $this->salesdb->table('service_info');
           $social_media_enquiry =  DB::connection('sales_db')->table('enquiry_info'); 

            $total_inactive_package = $this->salesdb->table('package_info')
                ->where('package_status', 0)
                ->whereColumn('package_end_date', '>', 'package_end_expected_date');
            
             if ($request->group) {
               $total_enquiry->where('group_id', $request->input('group'));
               $not_sent->where('group_id', $request->input('group'));
               $organic_enquiry->where('group_id', $request->input('group'));
               $total_sent->where('group_id', $request->input('group'));
               $lead_sale->where('group_id',$request->input('group_id'));
               $toll_free->where('group_id',$request->input('group_id'));
               $package_over_running->where('group_id',$request->input('group_id'));
               $enq_id = $this->salesdb->table('enquiry_info')
               ->where('group_id',$request->group)->pluck('id');
               $lead_feedback->whereIn('enq_id', $enq_id);
               $sent_percent_zoopgo->where('group_id', $request->input('group'));
               $sent_percent_lmart->where('group_id', $request->input('group'));
               $social_media_enquiry->where('group_id', $request->group_id);
               $total_inactive_package->where('group_id', $request->group_id);
               }
           if ($request->product) {
               $total_enquiry->where('product_id', $request->input('product'));
               $not_sent->where('product_id', $request->input('product'));
               $organic_enquiry->where('product_id', $request->input('product'));
               $total_sent->where('product_id', $request->input('product'));
               $lead_sale->where('product_id',$request->input('product'));
               $toll_free->where('product_id',$request->input('product'));
               $package_over_running->where('product_id',$request->input('product'));
               $lead_feedback->where('product_id',$request->product);
               $social_media_enquiry->where('product_id', $request->product);
               $total_inactive_package->where('product_id', $request->product);

           }
           if ($request->service) {
               $total_enquiry->where('service_id', $request->input('service'));
               $not_sent->where('service_id', $request->input('service'));
               $organic_enquiry->where('service_id', $request->input('service'));
               $total_sent->where('service_id', $request->input('service'));
               $lead_sale->where('service_id',$request->input('service'));
               $toll_free->where('service_id',$request->input('service'));
               $package_over_running->where('service_id',$request->input('service'));
               $sent_percent_zoopgo->where('service_id',$request->input('service'));
               $sent_percent_lmart->where('service_id',$request->input('service'));
               $per_day_required->where('service_id',$request->input('service'));
               $social_media_enquiry->where('service_id', $request->service);
               $total_inactive_package->where('service_id', $request->service);
               $enq_id = $this->salesdb->table('enquiry_info')
               ->where('service_id',$request->service)->pluck('id');
               $lead_feedback->whereIn('enq_id', $enq_id);
           }

            if ($request->category) {
               $total_enquiry->where('category_id', $request->input('category'));
               $not_sent->where('category_id', $request->input('category'));
               $organic_enquiry->where('category_id', $request->input('category'));
               $total_sent->where('category_id', $request->input('category'));
               $lead_sale->where('category_id',$request->input('category'));
               $toll_free->where('category_id',$request->input('category'));
               $package_over_running->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);
               $sent_percent_zoopgo->where('category_id',$request->input('category'));
               $sent_percent_lmart->where('category_id',$request->input('category'));
               $per_day_required->where('category_id',$request->input('category'));
               $social_media_enquiry->where('category_id',$request->category);
               $total_inactive_package->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);
               $enq_id = $this->salesdb->table('enquiry_info')
               ->where('category_id',$request->category)->pluck('id');
               $lead_feedback->whereIn('enq_id', $enq_id);
           }
       
           if ($request->start_date && $request->end_date) {
               $startDate = Carbon::parse($request->input('start_date'))->format('Y-m-d');
               $endDate = Carbon::parse($request->input('end_date'))->format('Y-m-d');
               $total_enquiry->whereDate('created_date', '>=', $startDate)
                             ->whereDate('created_date', '<=', $endDate);
                $not_sent->whereDate('created_date', '>=', $startDate)
                ->whereDate('created_date', '<=', $endDate);
                $organic_enquiry->whereDate('created_date', '>=', $startDate)
                ->whereDate('created_date', '<=', $endDate);
                $total_sent->whereDate('created_date', '>=',$startDate)
                ->whereDate('created_date', '<=', $endDate);
                $lead_sale->whereDate('created_date', '>=',$startDate)
                ->whereDate('created_date', '<=', $endDate);
                $toll_free->whereDate('created_date', '>=',$startDate)
                ->whereDate('created_date', '<=', $endDate);
                $package_over_running->whereDate('created_date', '>=',$startDate)
                ->whereDate('created_date', '<=', $endDate);
                $lead_feedback->whereDate('created_at', '>=',$startDate)
                ->whereDate('created_at', '<=', $endDate);
                $sent_percent_zoopgo->whereDate('created_date', '>=',$startDate)
                ->whereDate('created_date', '<=', $endDate);
                $sent_percent_lmart->whereDate('created_date', '>=',$startDate)
                ->whereDate('created_date', '<=', $endDate);
                //$per_day_required->whereDate('created_date', '>=',$startDate)
                //->whereDate('created_date', '<=', $endDate);
                $social_media_enquiry->whereDate('created_date', '>=',$startDate)
                ->whereDate('created_date', '<=', $endDate);
                $total_inactive_package->whereDate('created_date', '>=',$startDate)
                ->whereDate('created_date', '<=', $endDate);
                
               } else {
               
               $currentDate = Carbon::now()->format('Y-m-d');
               $total_enquiry->whereDate('created_date', $currentDate);
               $not_sent->whereDate('created_date', $currentDate);
               $organic_enquiry->whereDate('created_date', $currentDate);
               $total_sent->whereDate('created_date', $currentDate);
               $lead_sale->whereDate('created_date', $currentDate);
               $toll_free->whereDate('created_date', $currentDate);
               $package_over_running->whereDate('created_date', $currentDate);
               $lead_feedback->whereDate('created_at', $currentDate);
               $sent_percent_zoopgo->whereDate('created_date', $currentDate);
               $sent_percent_lmart->whereDate('created_date', $currentDate);
               //$per_day_required->whereDate('created_date', $currentDate);
               $social_media_enquiry->whereDate('created_date', $currentDate);
               $total_inactive_package->whereDate('created_date', $currentDate);
               
              }
       
           
           $total_enquiry_count = $total_enquiry->count();
           $total_not_sent = $not_sent->count();
           $total_organic_enquiry = $organic_enquiry->count();
           $total_sent_count =  $total_sent->count();
           $lead_sale_count = $lead_sale->sum('sent_count');
           $total_toll_free = $toll_free->count();
           $package_over_running_data = $package_over_running->get();
           $lead_feedback_count =  $lead_feedback->count();
           $total_enq_in_zoopgo = $sent_percent_zoopgo->count();
           $total_sent_enq_in_zoopgo = $sent_percent_zoopgo->where('enq_status',1)->count();
            if ($total_enq_in_zoopgo != 0) {
             $sent_percent_zoopgo = round(($total_sent_enq_in_zoopgo / $total_enq_in_zoopgo) * 100);
            } else {
              $sent_percent_zoopgo = 0; 
            }
           $total_enq_in_lmart = $sent_percent_lmart->count();
           $total_sent_enq_in_lmart = $sent_percent_lmart->where('enq_status',1)->count();
           if ($total_enq_in_lmart != 0) {
            $sent_percent_lmart = round(($total_sent_enq_in_lmart / $total_enq_in_lmart) * 100);
          } else {
            
            $sent_percent_lmart = 0; 
          }
           $per_day_required_sum = $per_day_required->sum('max_per_day');
           $total_social_media_enquiry = $social_media_enquiry
            ->whereNotIn('source_type',['TF','Organic','Adword'])->count();

           foreach($package_over_running_data as $row){
            if($row->package_end_expected_date !=''){
               
                if(Carbon::parse(Carbon::now())->greaterThan(Carbon::parse($row->package_end_expected_date))){
                    $count++;
                }

               }
            } 
           $total_inactive_package_query = $total_inactive_package->distinct()
                 ->pluck('client_id')->toArray();
            //return $total_inactive_package_query;
            
          $no_of_clients = 0;
         foreach ($total_inactive_package_query as $data_query) {
             $get_last_package = DB::connection('sales_db')->table('package_info')
            ->where('client_id', $data_query)
            ->where('package_status', 0)
            ->whereColumn('package_end_date', '>', 'package_end_expected_date')
            ->orderBy('package_id', 'DESC')
            ->first();

        if ($get_last_package) {
            $package_end_date = Carbon::parse($get_last_package->package_end_date)
              ->addDays(20)
              ->format('Y-m-d');
        
         $check_package_renew_or_not = DB::connection('sales_db')->table('package_info')
            ->where('client_id', $data_query)
            ->whereDate('created_date', '>=', $get_last_package->package_end_date)
            ->whereDate('created_date', '<=', $package_end_date)
            ->exists();
        //return  $check_package_renew_or_not;
        
        if (!$check_package_renew_or_not) {
            $no_of_clients++;
        }
      }
     }

           $data_array = array('count' => $total_enquiry_count,'overrunning'=>$count,
           'lmart_percent'=> $sent_percent_lmart,'zoopgo_percent'=>$sent_percent_zoopgo,
           'not_sent'=>$total_not_sent,'organic_enquiry'=>$total_organic_enquiry,
           'sent'=>$total_sent_count,'sale'=>$lead_sale_count,'toll_free'=>$total_toll_free,
           'feedback'=>$lead_feedback_count,'max_per_day'=>$per_day_required_sum,
           'social_media_enquiry'=>$total_social_media_enquiry,'renewal_gone'=>$no_of_clients);

       
          
           return response()->json(['status'=>200,'data'=>$data_array]);
       }
       
       public function dm_dashboard_enquiry_inner_page(Request $request)
       {

           $query = $this->salesdb->table('enquiry_info')
               ->leftJoin('customer_info', 'enquiry_info.customer_id', '=', 'customer_info.id')
               ->leftJoin('product', 'enquiry_info.product_id', '=', 'product.id')
               ->leftJoin('product_category', 'enquiry_info.category_id', '=', 'product_category.id')
               ->leftJoin('product_service', 'enquiry_info.service_id', '=', 'product_service.id')
               ->leftJoin('group_names', 'enquiry_info.group_id', '=', 'group_names.group_id')
               ->select(
                   'product.product_name',
                   'product_service.service_name',
                   'product_category.category_name',
                   'group_names.name as group_name',
                   'enquiry_info.sent_count',
                   'enquiry_info.source_type',
                   'enquiry_info.id',
                   'enquiry_info.created_date',
                   'customer_info.name',
                   'enquiry_info.source',
                   DB::raw(
                       "CASE 
                           WHEN enquiry_info.cs_verified = 1 THEN 'cs' 
                           WHEN enquiry_info.otp_verified = 1 THEN 'otp' 
                           ELSE 'not verified' 
                       END AS verification_status"
                   )
               );
           if($request->type =='not-sent' && !$request->enq_status){
            $query->where('enquiry_info.enq_status',3);

           }
           if($request->type =='organic-leads' && !$request->source_type){
            $query->where('enquiry_info.source_type','Organic');

           }
           if($request->type =='total-sent' && !$request->enq_status){
            $query->where('enquiry_info.enq_status',1);

           }
           if($request->type =='call-generated' && !$request->source_type){
            $query->where('enquiry_info.source_type','TF');

           }
           if($request->type =='leas-sale' && !$request->enq_status){
            $query->where('enquiry_info.enq_status',1);

           }

           if($request->social_media_enquiry && !$request->source_type){
            $query->whereNotIn('source_type',['TF','Organic','Adword']);


           }

           if ($request->product) {
            $query->where('enquiry_info.product_id',$request->product);
               
           }
           if ($request->group) {
               $query->where('enquiry_info.group_id', $request->group);
           }
           if ($request->service) {
               $query->where('enquiry_info.service_id', $request->service);
           }
           if($request->category){
            $query->where('enquiry_info.category_id', $request->category);

           }
           if ($request->start_date && $request->end_date) {
               $query->whereDate('enquiry_info.created_date', '>=', $request->start_date)
                   ->whereDate('enquiry_info.created_date', '<=', $request->end_date);
           } else {
               $currentDate = Carbon::now()->format('Y-m-d');
               $query->whereDate('enquiry_info.created_date', $currentDate);
           }
           if ($request->source_type) {
               $query->where('enquiry_info.source_type', $request->source_type);
           }
           if ($request->enq_status) {
               $query->where('enquiry_info.enq_status', $request->enq_status);
           }
           
           if ($request->verified_status == 'otp') {
                $query->where('enquiry_info.otp_verified', 1);
           } if ($request->verified_status == 'cs') {
                   $query->where('enquiry_info.cs_verified', 1);
               }
       
           if ($request->download) {
               
               $filename = "enq_data_" . Carbon::now()->format('Ymd_His') . ".csv";
               
               $response = new StreamedResponse(function() use ($query) {
                   $handle = fopen('php://output', 'w');
                   
                   
                   fputcsv($handle, [
                       'Product Name', 
                       'Service Name', 
                       'Category Name', 
                       'Group Name', 
                       'Sent Count', 
                       'Source Type', 
                       'Enq_ID', 
                       'Created Date', 
                       'Customer Name', 
                       'Source', 
                       'Verification Status'
                   ]);
               
                 
                   $results = $query->get();
                   foreach ($results as $row) {
                       fputcsv($handle, [
                           $row->product_name,
                           $row->service_name,
                           $row->category_name,
                           $row->group_name,
                           $row->sent_count,
                           $row->source_type,
                           $row->id,
                           $row->created_date,
                           $row->name,
                           $row->source,
                           $row->verification_status
                       ]);
                   }
               
                   fclose($handle);
               });
       
               $response->headers->set('Content-Type', 'text/csv');
               $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
       
               return $response;
           }
           
           $total_data = $query->paginate(10);
       
           return response()->json([
               'status' => 200,
               'data' => $total_data,
               'last_page' => $total_data->lastPage()
           ]);
       }
       
      public function source_type_list(){
        $data = $this->salesdb->table('enquiry_info')->where('source_type','!=','')->distinct('source_type')->get('source_type');
        return response()->json(['status'=>200,'data'=>$data]);
      }

      public function package_over_running(Request $request) {
        $list = [];
        
        // Start query builder
        $data = $this->salesdb->table('package_info')->where('package_status',1)->where('package_end_expected_date','!=','')->whereDate('package_end_expected_date', '<', Carbon::now()->format('Y-m-d'));
        
        // Apply filters
        if ($request->product) {
            $data->where('product_id', $request->product);
        }
        if ($request->group) {
            //$data->where('group_id', $request->group);
            $data->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);
        }
        if ($request->service) {
            $data->whereRaw('FIND_IN_SET(?, service_id)', [$request->service]);
        }

        if ($request->category) {
            $data->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);
        }
        if ($request->start_date && $request->end_date) {
            $data->whereDate('created_date', '>=', $request->start_date)
                 ->whereDate('created_date', '<=', $request->end_date);
        } else {
            $currentDate = Carbon::now()->format('Y-m-d');
            $data->whereDate('created_date', $currentDate);
        }
    
       if($request->download){
          $data_array = $data->get();

       }
       else{
          $data_array = $data->paginate(10);

       }
      
        
        foreach ($data_array as $row) {
            if ($row->package_end_expected_date && Carbon::now()->greaterThan(Carbon::parse($row->package_end_expected_date))) {
                $product = $this->salesdb->table('product')->where('id', $row->product_id)->first();
                $client = $this->salesdb->table('client_info')->where('id', $row->client_id)->first();
                //$group = $this->salesdb->table('group_names')->where('group_id', $row->group_id)->first();
                
                $service_ids = explode(',', $row->service_id);
                $service_names = $this->salesdb->table('product_service')
                    ->whereIn('id', $service_ids)
                    ->pluck('service_name')
                    ->toArray();
                $service_names_string = implode(', ', $service_names);


                 $group_ids = explode(',', $row->group_id);
                $group_names = $this->salesdb->table('group_names')
                    ->whereIn('group_id', $group_ids)
                    ->pluck('name')
                    ->toArray();
                $group_names_string = implode(', ', $group_names);
                
                $category_ids = explode(',', $row->category_id);
                $category_names = $this->salesdb->table('product_category')
                    ->whereIn('id', $category_ids)
                    ->pluck('category_name')
                    ->toArray();
                $category_names_string = implode(', ', $category_names);
                
                $list[] = [
                    'id' => $row->package_id,
                    'product' => $product ? $product->product_name : '',
                    'service' => $service_names_string,
                    'category' => $category_names_string,
                    'client' => $client ? $client->client_name : '',
                    'group' => $group_names_string,
                    'package_start_date' => $row->package_start_date,
                    'expected_end_date' => $row->package_end_expected_date,
                    'end_date' => $row->package_end_date,
                    'created_date'=>$row->created_date
                ];
            }
        }
        if ($request->download) {
                $filename = "overrunning_package" . Carbon::now()->format('Ymd_His') . ".csv";
                $handle = fopen($filename, 'w+');
                fputcsv($handle, ['package_id', 'Product', 'Service', 'Category', 'Client', 'Group', 'Package Start Date', 'Expected End Date','End Date','Created Date']);
    
                foreach ($list as $row) {
                    fputcsv($handle, $row);
                }
    
                fclose($handle);
    
                return response()->download($filename)->deleteFileAfterSend(true);
            }
        
        return response()->json([
            'status' => 200,
            'data' => $list,
            'last_page' => $data_array->lastPage()
        ]);
    }

public function lead_feedback(Request $request){
    try {
        $lead_feedback = DB::connection('sales_db')->table('client_feedback')
            ->join('enquiry_info', 'client_feedback.enq_id', '=', 'enquiry_info.id')
            ->leftJoin('group_names', 'enquiry_info.group_id', '=', 'group_names.group_id')
            ->leftJoin('product', 'enquiry_info.product_id', '=', 'product.id')
            ->leftJoin('product_service', 'enquiry_info.service_id', '=', 'product_service.id')
            ->leftJoin('product_category', 'enquiry_info.category_id', '=', 'product_category.id')
            ->select('client_feedback.*', 
                'group_names.name as group_name',
                'product.product_name',
                'product_service.service_name',
                'product_category.category_name'
            );
        if ($request->product) {
            $lead_feedback->where('product.id', $request->product);
        }

        if ($request->group) {
            $lead_feedback->where('enquiry_info.group_id', $request->group);
        }

        if ($request->service) {
            $lead_feedback->where('enquiry_info.service_id', $request->service);
        }

        if ($request->category) {
            $lead_feedback->where('enquiry_info.category_id', $request->category);
        }

        if ($request->start_date && $request->end_date) {
            $lead_feedback->whereDate('client_feedback.created_at', '>=', $request->start_date)
                ->whereDate('client_feedback.created_at', '<=', $request->end_date);
        } else {
            $currentDate = Carbon::now()->format('Y-m-d');
            $lead_feedback->whereDate('client_feedback.created_at', $currentDate);
        }

        $data = $lead_feedback->paginate(10);
        //return $data;

        $data_array = $data->map(function ($row) {
            return [
                'id' => $row->id,
                'group' => $row->group_name ?? 'N/A',
                'product' => $row->product_name ?? 'N/A',
                'service' => $row->service_name ?? 'N/A',
                'category' => $row->category_name ?? 'N/A',
                'client_id' => $row->client_id,
                'enq_id' => $row->enq_id,
                'lead_id' => $row->lead_id,
                'comment' => $row->comment,
            ];
        });

        return response()->json(['status' => 200, 'data' => $data_array,'last_page'=>$data->lastPage()]);

    } catch (\Exception $e) {
        return response()->json(['status' => 500, 'message' => $e->getMessage()]);
    }
}
}
