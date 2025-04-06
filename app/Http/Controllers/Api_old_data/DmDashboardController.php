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
           $package_over_running = $this->salesdb->table('package_info');
           $lead_feedback = $this->salesdb->table('customer_feedback');
           $sent_percent_zoopgo = $this->salesdb->table('enquiry_info')->where('product_id',2);
           $sent_percent_lmart = $this->salesdb->table('enquiry_info')->where('product_id',1);
           $per_client_return = $this->salesdb->table('return_lead_info');
          // $renewal_gone_due_to_lead = $this->salesdb->table('package_info');

       
          
           if ($request->group) {
               $total_enquiry->where('group_id', $request->input('group'));
               $not_sent->where('group_id', $request->input('group'));
               $organic_enquiry->where('group_id', $request->input('group'));
               $total_sent->where('group_id', $request->input('group'));
               $lead_sale->where('group_id',$request->input('group_id'));
               $toll_free->where('group_id',$request->input('group_id'));
               $package_over_running->where('group_id',$request->input('group_id'));
               $clients_id = $this->salesdb->table('client_info')
               ->where('group_id',$request->group)->pluck('client_id');
               $lead_feedback->whereIn('client_id', $clients_id);
               $sent_percent_zoopgo->where('group_id', $request->input('group'));
               $sent_percent_lmart->where('group_id', $request->input('group'));
               $per_client_return->whereIn('client_id',$clients_id);
              // $renewal_gone_due_to_lead->whereIn()



            
           }
           if ($request->product) {
               $total_enquiry->where('product_id', $request->input('product'));
               $not_sent->where('product_id', $request->input('product'));
               $organic_enquiry->where('product_id', $request->input('product'));
               $total_sent->where('product_id', $request->input('product'));
               $lead_sale->where('product_id',$request->input('product'));
               $toll_free->where('product_id',$request->input('product'));
               $package_over_running->where('product_id',$request->input('product'));
               $lead_feedback->where('product_name',$request->product);
              // $sent_percent_zoopgo->where('product_id', $request->input('product'));
               //$sent_percent_lmart->where('product_id', $request->input('product'));
               $per_client_return->where('product_id',$request->input('product'));

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
               $per_client_return->where('service_id',$request->input('service'));
           }
        //    if ($request->has('category')) {
        //        $total_enquiry->where('category_id', $request->input('category'));
        //        $not_sent->where('category_id', $request->input('category'));
        //        $organic_enquiry->where('category_id', $request->input('category'));
        //        $total_sent->where('category_id', $request->input('category'));
        //        $lead_sale->where('category_id',$request->input('category'));
        //        $toll_free->where('category_id',$request->input('category'));
        //        $package_over_running->where('category_id',$request->input('category'));
        //        $sent_percent_zoopgo->where('category_id',$request->input('category'));
        //        $sent_percent_lmart->where('category_id',$request->input('category'));
        //        $per_client_return->where('category_id',$request->input('category'));

        //    }
       
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
                $lead_feedback->whereDate('created_on', '>=',$startDate)
                ->whereDate('created_on', '<=', $endDate);
                $sent_percent_zoopgo->whereDate('created_date', '>=',$startDate)
                ->whereDate('created_date', '<=', $endDate);
                $sent_percent_lmart->whereDate('created_date', '>=',$startDate)
                ->whereDate('created_date', '<=', $endDate);
                $per_client_return->whereDate('created_at', '>=',$startDate)
                ->whereDate('created_at', '<=', $endDate);


           } else {
               
               $currentDate = Carbon::now()->format('Y-m-d');
               $total_enquiry->whereDate('created_date', $currentDate);
               $not_sent->whereDate('created_date', $currentDate);
               $organic_enquiry->whereDate('created_date', $currentDate);
               $total_sent->whereDate('created_date', $currentDate);
               $lead_sale->whereDate('created_date', $currentDate);
               $toll_free->whereDate('created_date', $currentDate);
               $package_over_running->whereDate('created_date', $currentDate);
               $lead_feedback->whereDate('created_on', $currentDate);
               $sent_percent_zoopgo->whereDate('created_date', $currentDate);
               $sent_percent_lmart->whereDate('created_date', $currentDate);
               $per_client_return->whereDate('created_at', $currentDate);


           }
       
           
           $total_enquiry_count = $total_enquiry->count();
           $total_not_sent = $not_sent->count();
           $total_organic_enquiry = $organic_enquiry->count();
           $total_sent_count =  $total_sent->count();
           $lead_sale_count = $lead_sale->sum('sent_count');
           $total_toll_free = $toll_free->count();
           $package_over_running_data = $package_over_running->where('package_status',0)->get();
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
           //$sent_percent_lmart =  $total_sent_enq_in_lmart/$total_enq_in_lmart*100;
           if ($total_enq_in_lmart != 0) {
            $sent_percent_lmart = round(($total_sent_enq_in_lmart / $total_enq_in_lmart) * 100);
          } else {
            
            $sent_percent_lmart = 0; 
        }
           $per_client_return_count = $per_client_return->count();
           $unique_client_id = $per_client_return->distinct('client_id')->count();
           if ($unique_client_id != 0) {
            $per_client_return_avarage = round($per_client_return_count/$unique_client_id);
          } else {
            
            $per_client_return_avarage = 0; 
        }
        //return  $per_client_return_avarag;

           foreach($package_over_running_data as $row){
            if($row->package_end_date ==''){
               
                if(Carbon::parse(Carbon::now())->greaterThan(Carbon::parse($row->package_end_expected_date))){
                    $count++;
                }

               }
              if($row->package_end_date!=''){
                
                if(Carbon::parse($row->package_end_date)->greaterThan(Carbon::parse($row->package_end_expected_date))){
                    $count++;
                  }


            }

           }
           $renwal_gone_due_to_lead =  $package_over_running->where('package_status',1)->get();
           $data_array = array('count' => $total_enquiry_count,'overrunning'=>$count,
           'lmart_percent'=> $sent_percent_lmart,'zoopgo_percent'=>$sent_percent_zoopgo,
           'not_sent'=>$total_not_sent,'organic_enquiry'=>$total_organic_enquiry,
           'sent'=>$total_sent_count,'sale'=>$lead_sale_count,'toll_free'=>$total_toll_free,
           'feedback'=>$lead_feedback_count,'return_avarage'=>$per_client_return_avarage);

       
          
           return response()->json(['status'=>200,'data'=>$data_array]);
       }
       
       public function dm_dashboard_enquiry_inner_page(Request $request)
       {
           $query = $this->salesdb->table('enquiry_info')
               ->leftJoin('customer_info', 'enquiry_info.customer_id', '=', 'customer_info.id')
               ->join('product', 'enquiry_info.product_id', '=', 'product.id')
               ->join('product_category', 'enquiry_info.category_id', '=', 'product_category.id')
               ->join('product_service', 'enquiry_info.service_id', '=', 'product_service.id')
               ->join('group_names', 'enquiry_info.group_id', '=', 'group_names.group_id')
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

           if ($request->product) {
            $query->where('enquiry_info.product_id',$request->product);
               
           }
           if ($request->group) {
               $query->where('enquiry_info.group_id', $request->group);
           }
           if ($request->service) {
               $query->where('enquiry_info.service_id', $request->service);
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
           
           if ($request->verified_status) {
               if ($request->verified_status == 'otp') {
                   $query->where('enquiry_info.otp_verified', 1);
               } elseif ($request->verified_status == 'cs') {
                   $query->where('enquiry_info.cs_verified', 1);
               }
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
        $data = $this->salesdb->table('package_info')->where('package_status', 0);
        
        // Apply filters
        if ($request->product) {
            $data->where('product_id', $request->product);
        }
        if ($request->group) {
            $data->where('group_id', $request->group);
        }
        if ($request->service) {
            $data->whereRaw('FIND_IN_SET(?, service_id)', [$request->service]);
        }
        if ($request->start_date && $request->end_date) {
            $data->whereDate('created_date', '>=', $request->start_date)
                 ->whereDate('created_date', '<=', $request->end_date);
        } else {
            $currentDate = Carbon::now()->format('Y-m-d');
            $data->whereDate('created_date', $currentDate);
        }
    
       
        $data_array = $data->paginate(1);
        
        foreach ($data_array->items() as $row) {
            if (Carbon::now()->greaterThan(Carbon::parse($row->package_end_expected_date))) {
                $product = $this->salesdb->table('product')->where('id', $row->product_id)->first();
                $client = $this->salesdb->table('client_info')->where('id', $row->client_id)->first();
                $group = $this->salesdb->table('group_names')->where('group_id', $row->group_id)->first();
                
                $service_ids = explode(',', $row->service_id);
                $service_names = $this->salesdb->table('product_service')
                    ->whereIn('id', $service_ids)
                    ->pluck('service_name')
                    ->toArray();
                $service_names_string = implode(', ', $service_names);
                
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
                    'group' => $group ? $group->name : '',
                    'package_start_date' => $row->package_start_date,
                    'expected_end_date' => $row->package_end_expected_date,
                    'end_date' => $row->package_end_date,
                    'created_date'=>$row->created_date
                ];
            }
        }
        if ($request->download) {
            //return 'jjj';
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

    



      
}
