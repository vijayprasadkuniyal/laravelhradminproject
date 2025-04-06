<?php
namespace App\Http\Controllers\CustomerSupport;
use DB;
use File;
use App\Http\Controllers\Controller;
use App\Http\Controllers\SendSystem\LeadSendController;
use Illuminate\Http\Request;
use App\Models\adminSales\package;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Validator;

class csDetailsController extends Controller
{
    public function get_not_sent_enquiry(Request $request)
    {
        $engId = $request->enqId;
        $mobileNumber = $request->mobileNumber;

        // Initial query setup with necessary joins
        $enqDetailsQuery  = DB::connection('sales_db')->table('enquiry_info')
            ->select(
                'enquiry_info.id',
                'enquiry_info.created_date as recived_date',
                'enquiry_info.event_date',
                'enquiry_info.city_from',
                'enquiry_info.city_to',
                'enquiry_info.distance',
                'enquiry_info.customer_id',
                'enquiry_info.no_of_attempt',
                'customer_info.name',
                'customer_info.primary_no',
                'customer_info.email',
                'group_names.name as group_name',
                'product_service.service_name',
                'product_category.category_name'
            )
            ->leftJoin('customer_info', 'customer_info.id', '=', 'enquiry_info.customer_id')
            ->leftJoin('product_service', 'product_service.id', '=', 'enquiry_info.service_id')
            ->leftJoin('product_category', 'product_category.id', '=', 'enquiry_info.category_id')
            ->leftJoin('group_names', 'group_names.group_id', '=', 'enquiry_info.group_id');

        // Fetch enquiry by engId
        if ($engId) {
            $enqDetails = $enqDetailsQuery->where('enquiry_info.id', $engId)
                ->groupBy('enquiry_info.id')
                ->orderBy('enquiry_info.id','DESC')
                ->paginate($request->per_page);

        } elseif ($mobileNumber) {
            // Fetch enquiry by mobile number
            $custInfo = DB::connection('sales_db')->table('customer_info')
                ->select('id')->where('primary_no', $mobileNumber)
                ->first();

            if ($custInfo) {
                $enqDetails = $enqDetailsQuery->where('enquiry_info.customer_id', $custInfo->id)
                    ->groupBy('enquiry_info.id')
                    ->orderBy('enquiry_info.id','DESC')
                    ->paginate($request->per_page);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'No customer found with the given mobile number',
                    'data' => [],
                ]);
            }

        } else {
            
            $enqDetails = $enqDetailsQuery->where(function ($query) {
            $query->whereIn('enquiry_info.enq_status', [3, 0])
                  ->where('no_of_attempt', 0)
                  ->where('enquiry_info.created_date' ,'>', '2024-10-07 00:00:00')
                  ->where(function($timeQuery) {
                      $timeQuery->whereRaw("TIME(enquiry_info.created_date) > '08:00:00'")
                    ->whereRaw("TIME(enquiry_info.created_date) < '22:00:00'");
                  });
            })

                
            // $enqDetails = $enqDetailsQuery->where(function ($query) {
            //     $query->whereIn('enquiry_info.enq_status', [3,0])
            //           ->where('no_of_attempt', 0)
            //           ->whereRaw("TIME(enquiry_info.created_date) < '22:00:00'")
            //             ->orWhereRaw("TIME(enquiry_info.created_date) > '08:00:00'");
            // })
            
            // $enqDetails = $enqDetailsQuery->->where(function($query) {
            // $query->whereRaw("TIME(enquiry_info.created_date) > '22:00:00'")
            //     ->orWhereRaw("TIME(enquiry_info.created_date) < '08:00:00'");
            // })
          
            ->orderBy('enquiry_info.id','DESC')
            ->groupBy('enquiry_info.id')
            ->paginate($request->per_page);
        }

       
        if ($enqDetails->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'Enquiry not found',
                'data' => [],
                'last_page' => $enqDetails->lastPage()
            ]);
        }

        // Return successful response
        return response()->json([
            'status' => 200,
            'message' => 'Enquiry Details',
            'data' => $enqDetails,
            'last_page' => $enqDetails->lastPage()
        ]);
    }


    public function get_return_lead(Request $request)
    {
        if($request->enqId)
        {
            $engId = $request->enqId;
        }
        else
        {
            $engId='';
        }

        if($request->mobileNumber)
        {
            $mobileNumber = $request->mobileNumber;
        }
        else
        {
            $mobileNumber = ''; 
        }

        // Initialize query
        $returnLeadDetails = DB::connection('sales_db')->table('return_lead_info as rli')
            ->select(
                'rli.id as return_id', 'rli.enq_id', 'rli.lead_id', 'rli.customer_id', 
                'rli.client_id', 'rli.package_id', 'rli.reason_id', 'rli.client_remarks', 
                'rli.status', 'rli.created_at as return_date', 'ci.name as customer_name', 
                'ci.primary_no as customer_mobile', 'ci.email as customer_email', 
                'ei.city_from', 'ei.city_to', 'ei.otp_verified', 'ei.cs_verified', 
                'ei.address_from', 'ei.address_to', 'ei.event_date', 'ei.created_date as received_date', 
                'p.product_name', 'ps.service_name', 'pc.category_name'
            )
            ->leftJoin('customer_info as ci', 'ci.id', '=', 'rli.customer_id')
            ->leftJoin('enquiry_info as ei', 'ei.id', '=', 'rli.enq_id')
            ->leftJoin('return_lead_reasons as rlr', 'rlr.id', '=', 'rli.reason_id')
            ->leftJoin('product as p', 'p.id', '=', 'ei.product_id')
            ->leftJoin('product_service as ps', 'ps.id', '=', 'ei.service_id')
            ->leftJoin('product_category as pc', 'pc.id', '=', 'ei.category_id');

        // Fetch enquiry by engId
        if(!empty($engId)){
            $enqDetails = $returnLeadDetails->where('rli.enq_id', $engId)
                //->groupBy('rli.enq_id')
                ->paginate($request->per_page);
        }
        // Fetch enquiry by mobile number
        elseif (!empty($mobileNumber)) {
            $custInfo = DB::connection('sales_db')->table('customer_info')
                ->select('id')
                ->where('primary_no', $mobileNumber)
                ->first();

            if ($custInfo) {
                $enqDetails = $returnLeadDetails->where('rli.customer_id', $custInfo->id)
                    //->groupBy('rli.enq_id')
                    ->paginate($request->per_page);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'No customer found with the given mobile number',
                    'data' => []
                ]);
            }
        }
        // Fetch enquiries with status = 0
        else {
            $enqDetails = $returnLeadDetails->where('rli.status', 0)
                //->groupBy('rli.enq_id')
                ->paginate($request->per_page);
        }

        // Check if results are empty
        if ($enqDetails->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'Enquiry not found',
                'data' => [],
                'last_page' => $enqDetails->lastPage()
            ]);
        }

        // Return successful response
        return response()->json([
            'status' => 200,
            'message' => 'Enquiry Details',
            'data' => $enqDetails,
            'last_page' => $enqDetails->lastPage()
        ]);
    }





    public function get_enquiry_details_by_enqid(Request $request)
    {
        $engId = $request->enqId;
        $enqDetails = DB::connection('sales_db')->table('enquiry_info')
        ->select(
            'enquiry_info.id',
            'enquiry_info.created_date as received_date',
            'enquiry_info.event_date',
            'enquiry_info.city_from',
            'enquiry_info.city_to',
            'enquiry_info.distance',
            'enquiry_info.customer_id',
            'enquiry_info.no_of_attempt',
            'enquiry_info.sent_count',
            'enquiry_info.otp_verified',
            'enquiry_info.cs_verified',
            'enquiry_info.enq_status', 
            'enquiry_info.source_type',
            'enquiry_info.source',
            'enquiry_info.address_from',
            'enquiry_info.address_to',
            'enquiry_info.remarks',
            'customer_info.name',
            'customer_info.primary_no',
            'customer_info.email',
            'enquiry_info.verifed_date as verified_date',
            'enquiry_info.verified_by',
            //'enquiry_info.followup_status',
            'enq_status.name as followup_title',
           
            'group_names.name as group_name',
            'product_service.service_name',
            'product_category.category_name',
        )
        
        ->leftJoin('customer_info', 'customer_info.id', '=', 'enquiry_info.customer_id')
        ->leftJoin('product_service', 'product_service.id', '=', 'enquiry_info.service_id')
        ->leftJoin('product_category', 'product_category.id', '=', 'enquiry_info.category_id')
        ->leftJoin('group_names', 'group_names.group_id', '=', 'enquiry_info.group_id')
        ->leftJoin('enq_status', 'enq_status.id', '=', 'enquiry_info.followup_status')
        ->where('enquiry_info.id', $engId)
        ->groupBy('enquiry_info.id') 
        ->get();

        $enqDetails->map(function($item)
        {

            $clientInfo = DB::connection('sales_db')->table('leads_info')
            ->select('leads_info.id as lead_id','company_info.client_name','company_info.business_name','company_info.email','company_info.mobile_no')
            ->leftJoin('company_info','company_info.comp_id','=','leads_info.comp_id')
            ->where('enq_id',$item->id)
            ->get();
            $item->clientDetails = $clientInfo;
            return $item;
        });

        $enqDetails->map(function ($item) {
            $empName = DB::table('emp_basic_info')
                ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")
                ->where('emp_id', $item->verified_by)
                ->first();
            $item->verified_by = $empName->emp_name;
            return $item;
        });

    
        if($enqDetails->isEmpty())
        {
            return response()->json([
                'status' => 404,
                'message' => 'Enquiry not found',
                'data' => []
            ]);
        }

    
        return response()->json([
            'status' => 200,
            'message' => 'Enquiry Details',
            'data' => $enqDetails
        ]);
    }


    // public function get_otp_verified_not_sent_enq(Request $request)
    // {
    //     $enqDetails = DB::connection('sales_db')->table('enquiry_info')
    //     ->select('enquiry_info.id','enquiry_info.created_date as recived_date','enquiry_info.event_date','enquiry_info.city_from','enquiry_info.city_to','enquiry_info.distance','enquiry_info.customer_id','customer_info.name','customer_info.primary_no','customer_info.email','group_names.name as group_name','product_service.service_name','product_category.category_name','enquiry_info.no_of_attempt','enquiry_info.otp_verified')
    //     ->leftJoin('customer_info','customer_info.id','=','enquiry_info.customer_id')
    //     ->leftJoin('product_service','product_service.id','=','enquiry_info.service_id')
    //     ->leftJoin('product_category','product_category.id','=','enquiry_info.category_id')
    //     ->leftJoin('group_names','group_names.group_id','=','enquiry_info.group_id')
    //     ->where(array('enq_status'=>3,'otp_verified'=>1,'cs_verified'=>0))
    //     ->where(function($timeQuery){
    //           $timeQuery->whereRaw("TIME(enquiry_info.created_date) > '22:00:00'")
    //          ->orWhereRaw("TIME(enquiry_info.created_date) < '08:00:00'");
    //       })
        
    //     ->paginate($request->per_page);
        
    //     return response()->json(['status'=>200,'message'=>'Not Sent Enquiry Details','data'=>$enqDetails,'last_page'=>$enqDetails->lastPage()]);
    // }
    
    public function get_otp_verified_not_sent_enq(Request $request)
    {
        // Ensure per_page is set, with a default value if missing
        $perPage = $request->per_page ?? 15;
    
        $enqDetails = DB::connection('sales_db')
            ->table('enquiry_info')
            ->select(
                'enquiry_info.id',
                'enquiry_info.created_date as recived_date',
                'enquiry_info.event_date',
                'enquiry_info.city_from',
                'enquiry_info.city_to',
                'enquiry_info.distance',
                'enquiry_info.customer_id',
                'customer_info.name',
                'customer_info.primary_no',
                'customer_info.email',
                'group_names.name as group_name',
                'product_service.service_name',
                'product_category.category_name',
                'enquiry_info.no_of_attempt',
                'enquiry_info.otp_verified'
            )
            ->leftJoin('customer_info', 'customer_info.id', '=', 'enquiry_info.customer_id')
            ->leftJoin('product_service', 'product_service.id', '=', 'enquiry_info.service_id')
            ->leftJoin('product_category', 'product_category.id', '=', 'enquiry_info.category_id')
            ->leftJoin('group_names', 'group_names.group_id', '=', 'enquiry_info.group_id')
            ->where('enquiry_info.enq_status', 3)
            ->where('enquiry_info.otp_verified', 1)
            ->where('enquiry_info.cs_verified', 0)
            ->where(function ($timeQuery) {
                $timeQuery->whereRaw("TIME(enquiry_info.created_date) > '08:00:00'")
              ->whereRaw("TIME(enquiry_info.created_date)  < '22:00:00'");
            })
            ->paginate($perPage);
    
        return response()->json([
            'status' => 200,
            'message' => 'Not Sent Enquiry Details',
            'data' => $enqDetails,
            'last_page' => $enqDetails->lastPage()
        ]);
    }


    public function get_overnight_verified_enq(Request $request)
{
    $startTime = '22:00:00'; 
    $endTime = '08:00:00';  

    //$currentDate = now()->format('Y-m-d');
    $enqDetails = DB::connection('sales_db')->table('enquiry_info')
        ->select(
            'enquiry_info.id',
            'enquiry_info.created_date as recived_date',
            'enquiry_info.event_date',
            'enquiry_info.city_from',
            'enquiry_info.city_to',
            'enquiry_info.distance',
            'enquiry_info.customer_id',
            'customer_info.name',
            'customer_info.primary_no',
            'customer_info.email',
            'group_names.name as group_name',
            'product_service.service_name',
            'product_category.category_name',
            'enquiry_info.no_of_attempt',
            'enquiry_info.otp_verified'
        )
        ->leftJoin('customer_info', 'customer_info.id', '=', 'enquiry_info.customer_id')
        ->leftJoin('product_service', 'product_service.id', '=', 'enquiry_info.service_id')
        ->leftJoin('product_category', 'product_category.id', '=', 'enquiry_info.category_id')
        ->leftJoin('group_names', 'group_names.group_id', '=', 'enquiry_info.group_id')
        ->where([
            ['enq_status', 3],
            ['otp_verified', 1],
            ['cs_verified', 0],
        ])
        ->where('enquiry_info.created_date', '>', '2024-10-09 00:00:00')
        ->where(function($query) use ($startTime, $endTime) {
            $query->whereRaw("TIME(enquiry_info.created_date) >= ?", [$startTime])
                  ->orWhereRaw("TIME(enquiry_info.created_date) < ?", [$endTime]);
        })
        ->paginate($request->per_page);

    return response()->json(['status' => 200, 'message' => 'Not Sent Enquiry Details', 'data' => $enqDetails, 'last_page' => $enqDetails->lastPage()]);
}


    public function get_overnight_not_verified_enq(Request $request)
    {
        $today = date('Y-m-d');
        $enqDetails = DB::connection('sales_db')->table('enquiry_info')
        ->select('enquiry_info.id','enquiry_info.created_date as recived_date','enquiry_info.event_date','enquiry_info.city_from','enquiry_info.city_to','enquiry_info.distance','enquiry_info.customer_id','customer_info.name','customer_info.primary_no','customer_info.email','group_names.name as group_name','product_service.service_name','product_category.category_name','enquiry_info.no_of_attempt','enquiry_info.otp_verified')
        ->leftJoin('customer_info','customer_info.id','=','enquiry_info.customer_id')
        ->leftJoin('product_service','product_service.id','=','enquiry_info.service_id')
        ->leftJoin('product_category','product_category.id','=','enquiry_info.category_id')
        ->leftJoin('group_names','group_names.group_id','=','enquiry_info.group_id')
        //->where(array('enq_status'=>3,'otp_verified'=>0))

        // ->where([
        //     //['enq_status', 3],
        //     ['otp_verified', 0],
        //     ['no_of_attempt',0]
        // ])
        ->where('enquiry_info.created_date' ,'>','2024-10-09 22:00:00')
        ->where('enquiry_info.created_date','<',$today.' 08:00:00')
        ->where(array('otp_verified'=>0,'no_of_attempt'=>0))
        
        //created_date > '2024-10-09 22:00:00' and otp_verified =0 and no_of_attempt=0
        //->where('enquiry_info.created_date' ,'>', '2024-10-09 00:00:00')
        //  ->where(function($query) {
        //     $query->whereRaw("TIME(enquiry_info.created_date) > '08:00:00'");
        //       // ->orWhereRaw("TIME(enquiry_info.created_date) < '08:00:00'");
        // })
       ->paginate($request->per_page);

        return response()->json(['status'=>200,'message'=>'Not Sent Enquiry Details','data'=>$enqDetails,'last_page'=>$enqDetails->lastPage()]);
    }

    public function lmart_toll_free(Request $request)
    {
        $fromDate = $request->date_from;
        $toDate = $request->date_to;
        
        if($fromDate && $toDate)
        {
            
            $fromDate = Carbon::parse($fromDate)->format('Y-m-d');
            //return  $fromDate;
            $toDate = Carbon::parse($toDate)->format('Y-m-d');
        }
        else
        {
            $fromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
           
            $toDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }
        
        $results = DB::connection('sales_db')
            ->table('tata_lm_tollfree_incoming')
            ->whereRaw("start_stamp >= ? and start_stamp <= ?", [$fromDate.' 00:00:00', $toDate.' 23:59:59'])
            ->orderBy('id', 'desc');

        if($request->mobile_no){
           $results->where('customer_no_with_prefix', 'like', '%' . $request->mobile_no . '%');

           
        }
        if($request->comment){
             $results->where('call_comment', 'like', '%' . $request->comment . '%');
             //$results->where('call_comment',$request->comment);
         
        }
        $data_array = $results->paginate($request->per_page);

        return response()->json(['status'=>200,'message'=>'Lmart tollfree details','data'=>$data_array,'from_date'=>$fromDate,'to_date'=>$toDate,'last_page'=>$data_array->lastPage()]);
    }

    public function update_lead_info_details(Request $request)
    {
        $status = $request->status;
        $callId = $request->callId;
        $createdBy = $request->created_by;
        $remarks = $request->remarks;

        $commentInfo = DB::connection('sales_db')->table('tata_lm_tollfree_incoming')
        ->where('id',$callId)
        ->update(array('call_identity'=>$status,'call_comment'=>$remarks,'user_id'=>$createdBy));

        if($commentInfo)
        {
            return response()->json(['status'=>200,'message'=>'Comment Updated Successfully.']);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again.']);
        }
        
    }

    public function add_enquiry(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'emp_id' => 'required',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^[0-9]{10}$/',
            //'email' => 'nullable|email|max:255',
            //'secondaryPhone' => 'nullable|string|regex:/^[0-9]{10}$/',
            'moving_date' => 'required|date_format:Y-m-d',
            'service' => 'required|integer',
            'product' => 'required|integer',
            'category' => 'required',
        ]);

        // If validation fails, return an error response
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first(),
            ]);
        }

        // Extracting 'from_place' and 'to_place' data
        $fromState = $request->fromState ?? '';
        $fromCity = $request->fromCity ?? '';
        $fromLocality = $request->fromLocation ?? '';
        $fromAddress = $request->fromAddress  ?? '';
        $fromLat = $request->fromLat ?? '';
        $fromLng = $request->fromLng ?? '';

        $toState = $request->toState ?? '';
        $toCity = $request->toCity ?? '';
        $toLocality = $request->toLocation ?? '';
        $toAddress = $request->toAddress ?? '';
        $toLat = $request->toLat ?? '';
        $toLng = $request->toLng ?? '';

        if($fromLat && $fromLng && $toLat && $toLng)
        {
            $distance = csDetailsController::haversineGreatCircleDistance($fromLat, $fromLng, $toLat, $toLng);
        }
        else
        {
            $distance = 0;  
        }

        $group_details = DB::connection('sales_db')->table('rsms_city')->where('city_name', $fromCity);
        if($group_details->count() == 0){
            $group_details = DB::connection('sales_db')->table('rsms_city')->where('city_name','like',"$fromCity%");
        }
        $group_details = $group_details->first();
        if(isset($group_details->state_id)){
            $group_id = $group_details->state_id;
        }else
        {
            $group_id='';
        }


        $mainCategory = explode(',', $request->category);
        $categoryId = 0;
        if (in_array(2, $mainCategory)) {
            $categoryId = 2;
        } elseif (in_array(1, $mainCategory)) {
            $categoryId = 1;
        } elseif (in_array(3, $mainCategory)) {
            $categoryId = 3;
        } elseif (in_array(4, $mainCategory)) {
            $categoryId = 4;
        } elseif (in_array(5, $mainCategory)) {
            $categoryId = 5;
        } elseif (in_array(6, $mainCategory)) {
            $categoryId = 2; 
        } elseif (in_array(7, $mainCategory)) {
            $categoryId = 7;
        } else {
            $categoryId = $categoryId ??0;
        }

        // Check if customer already exists
        $checkCustomer = DB::connection('sales_db')
            ->table('customer_info')
            ->select('id')
            ->where('primary_no', $request->phone)
            ->orderBy('id', 'DESC')
            ->first();

        if(!empty($checkCustomer))
        {
            $customer_id = $checkCustomer->id;
            $enquiryUpdate = [
                'name' => $request->name,
                'primary_no' => $request->phone,
                'secondary_no' => $request->secondaryPhone,
                'email' => $request->email,
                'city' => $fromCity,
                'state' => $fromState,
                'locality' => $toLocality,
                'address' => $fromAddress,
                'status' => 2,
                'verified_by' => $request->emp_id,
            ];

            // Update the customer info
            $updateCustomer = DB::connection('sales_db')
                ->table('customer_info')
                ->where('id', $customer_id)
                ->update($enquiryUpdate);
        } else {
            // Insert a new customer record
            $enquiry = [
                'name' => $request->name,
                'primary_no' => $request->phone,
                'secondary_no' => $request->secondaryPhone,
                'email' => $request->email,
                'city' => $fromCity,
                'state' => $fromState,
                'locality' => $fromLocality,
                'address' => $fromAddress,
                'status' => 2,
                'verified_by' => $request->emp_id,
            ];

            $customer_id = DB::connection('sales_db')
                ->table('customer_info')
                ->insertGetId($enquiry);
        }

        if (empty($request->enqId))
        {
            // Insert a new enquiry
            $enquiry = [
                'customer_id' => $customer_id,
                'category_id' => $categoryId,
                'selected_category_id'=>$request->category,
                'product_id' => $request->product,
                'service_id' => $request->service,
                'group_id'=>$group_id,
                'city_from' => $fromCity,
                'city_to' => $toCity,
                'state_from' => $fromState,
                'state_to' => $toState,
                'locality_from' => $fromLocality,
                'locality_to' => $toLocality,
                'address_from' => $fromAddress,
                'address_to' => $toAddress,
                'event_date' => $request->moving_date,
               
                'lat_from' => $fromLat,
                'long_from' => $fromLng,
                'lat_to' => $toLat,
                'long_to' => $toLng,
                'cs_verified' => 1,
                'verified_by' => $request->emp_id,
                'verifed_date' => date('Y-m-d'),
                'no_of_attempt' => 1,
                'followup_status' => $request->enq_status,
                'remarks' => $request->enq_remarks,
                'source_from' => $request->source_from,
                'source' =>'CS', //$request->source_url,
                'source_type' => $request->source_type,
                'enq_for' => $request->enq_type ?? 0,
                'distance'=>$distance,
                'enq_tnc'=>1
            ];

            $enquiry_info = DB::connection('sales_db')
                ->table('enquiry_info')
                ->insertGetId($enquiry);

            $logArray = array(
                'enq_id'=>$enquiry_info,
                'followup_id'=>$request->enq_status,
                'remarks'=>$request->enq_remarks,
                'exe_id'=>$request->emp_id,
            );
    
            $logInsertId = DB::connection('sales_db')
            ->table('enquiry_attempt_log')
            ->insertGetId($logArray);


            if ($enquiry_info) {
                self::add_enq_item_info(
                    $request->emp_id,
                    $enquiry_info,
                    $request->selectedItemTypes,
                    $request->selectedItems,
                    $request->quantities
                );

                // Sending lead after adding enquiry
                $myRequest = new Request();
                if($request->enq_status ==1)
                {
                    //$myRequest->request->add(['enq_id' => $enquiry_info]);
                    //$sentEnq = (new LeadSendController())->leadSend($myRequest);
                    
                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://apiworkforce.r-ims.com/api/send-lead?enq_id='.$enquiry_info,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                }
                else
                {
                    $sentEnq='';
                }
                

                return response()->json([
                    'status' => 200,
                    'message' => 'Enquiry added successfully',
                    //'data' => $sentEnq,
                ]);
            } else {
                return response()->json([
                    'status' => 201,
                    'message' => 'Something went wrong with add enquiry. Please try Again',
                ]);
            }
        }
        else
        {
            $getNoOfAttempts = DB::connection('sales_db')->table('enquiry_info')
                ->select('no_of_attempt')
                ->where('id', $request->input('enqId'))
                ->first();
            // Update existing enquiry
            $update_enquiry_data = [
                'customer_id' => $customer_id,
                'category_id' => $categoryId,
                'selected_category_id' => $request->input('category'),
                'product_id' => $request->input('product'),
                'service_id' => $request->input('service'),
                'group_id'=>$group_id,
                'city_from' => $fromCity,
                'city_to' => $toCity,
                'state_from' => $fromState,
                'state_to' => $toState,
                'locality_from' => $fromLocality,
                'locality_to' => $toLocality,
                'address_from' => $fromAddress,
                'address_to' => $toAddress,
                'event_date' => $request->input('moving_date'),
                'lat_from' => $fromLat,
                'long_from' => $fromLng,
                'lat_to' => $toLat,
                'long_to' => $toLng,
                'cs_verified' => 1,
                'verified_by' => $request->input('emp_id'),
                'verifed_date' => date('Y-m-d'),
                'no_of_attempt' => $getNoOfAttempts->no_of_attempt + 1,
                'followup_status' => $request->input('enq_status'),
                'remarks' => $request->input('enq_remarks'),
                'source_from' => $request->input('source_from'),
                'source' => $request->input('source_url'),
                'source_type' => $request->input('source_type'),
                'enq_for' => $request->input('enq_type', 0),
                'distance'=>$distance,
                'enq_tnc'=>1
            ];

            $update_enquiry_info = DB::connection('sales_db')
                ->table('enquiry_info')
                ->where('id', $request->input('enqId'))
                ->update($update_enquiry_data);

                $logArray = array(
                    'enq_id'=>$request->input('enqId'),
                    'followup_id'=>$request->enq_status,
                    'remarks'=>$request->enq_remarks,
                    'exe_id'=>$request->emp_id,
                );
        
                $logInsertId = DB::connection('sales_db')
                ->table('enquiry_attempt_log')
                ->insertGetId($logArray);

            // Check if the update was successful $selectedItems
            if ($update_enquiry_info) {
                if($request->selectedItems)
                {
                    self::add_enq_item_info(
                        $request->emp_id,
                        $request->enqId,
                        $request->selectedItemTypes,
                        $request->selectedItems,
                        $request->quantities
                    );
                }

                 // Sending lead after update enquiry
                $myRequest = new Request();
                if($request->enq_status ==1)
                {
                    //$myRequest->request->add(['enq_id' => $enquiry_info]);
                    //$sentEnq = (new LeadSendController())->leadSend($myRequest);
                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://apiworkforce.r-ims.com/api/send-lead?enq_id='.$request->enqId,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                    
                }
                else
                {
                    $sentEnq=array();
                }

                return response()->json([
                    'status' => 200,
                    'message' => 'Enquiry updated successfully',
                    //'data' => $sentEnq,
                ]);
            } else {
                return response()->json([
                    'status' => 201,
                    'message' => 'Something went wrong with update enquiry. Please try Again',
                ]);
            }
        }
    }

    public static function haversineGreatCircleDistance( $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return round($angle * $earthRadius/1000);
    }



    public function add_enq_item_info($empId,$enqId,$selectedItemTypes,$selectedItems,$quantities)
    {
        // $selectedItems = $request->selectedItems;
        // $quantities = $request->quantities;
        $itemArray=array();
        foreach($selectedItems as $item => $value)
        {
             $itemInfo = DB::connection('sales_db')->table('item_info')
             ->select('item_info.*','product_item_type.item_type')
             ->leftJoin('product_item_type','product_item_type.id','=','item_info.type_id')
             ->where('item_info.id',$item)->first();
 
             $itemArray[$itemInfo->type_id] = array(
                 'product_id'=>$itemInfo->product_id,
                 'type_id'=>$itemInfo->type_id,
                 'item_type'=>$itemInfo->item_type,
             );
             // if quantity not set put 1...
            if($quantities[$itemInfo->id] < '1' || $quantities[$itemInfo->id] == '')
            {
                $quantities[$itemInfo->id] = '1';
            } 
             // assign array.. values.. here.. 
             $innerItem[$itemInfo->type_id][] = array(
                 'item_id'=>$itemInfo->id,
                 'item_name'=>$itemInfo->item_name,
                 'item_quantity'=>$quantities[$itemInfo->id],
             );
 
             $itemArray[$itemInfo->type_id]['item_info'] = $innerItem[$itemInfo->type_id];
        }

        $insetItemDetails = array(
            'enq_id'=>$enqId,
            'item_type'=>json_encode($selectedItemTypes),
            'item_list'=>json_encode($selectedItems),
            'item_quantity'=>json_encode($quantities),
            'item_detials'=>json_encode($itemArray),
            'created_by'=>$empId,
        );

        $checkEnq = DB::connection('sales_db')->table('enq_item_info')->where('enq_id',$enqId)->first();

        if($checkEnq)
        {
            $enquiryInfo = DB::connection('sales_db')->table('enq_item_info')->where('enq_id',$enqId)->update($insetItemDetails);
        }
        else
        {
            $enquiryInfo = DB::connection('sales_db')->table('enq_item_info')->insertGetId($insetItemDetails);
        }

        if($enquiryInfo)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function get_enquiry_by_id($id)
    {
        $enqDetails = DB::connection('sales_db')->table('enquiry_info')
        ->select('enquiry_info.*','customer_info.name','customer_info.primary_no','customer_info.secondary_no','customer_info.email','customer_info.address','enq_item_info.item_type','enq_item_info.item_list','enq_item_info.item_quantity')
        ->leftJoin('customer_info','customer_info.id','=','enquiry_info.customer_id')
        ->leftJoin('enq_item_info','enq_item_info.enq_id','=','enquiry_info.id')
        ->where('enquiry_info.id',$id)
        ->first();
        return response()->json(['status'=>200,'message'=>'Enquiry Details','data'=>$enqDetails]);
    }

    public function get_customer_history(Request $request)
    {
        $customerHistory = DB::connection('sales_db')->table('customer_info')
        ->select('enquiry_info.id','enquiry_info.created_date as recived_date','enquiry_info.event_date','enquiry_info.city_from','enquiry_info.city_to','enquiry_info.distance','enquiry_info.customer_id','customer_info.name','customer_info.primary_no','customer_info.email','group_names.name as group_name','product_service.service_name','product_category.category_name','enquiry_info.sent_count','enquiry_info.enq_status','enquiry_info.verified_by','enquiry_info.verifed_date','enquiry_info.otp_verified','enquiry_info.cs_verified','enquiry_info.address_from','enquiry_info.address_to')

        ->leftJoin('enquiry_info','enquiry_info.customer_id','=','customer_info.id')
        ->leftJoin('product_service','product_service.id','=','enquiry_info.service_id')
        ->leftJoin('product_category','product_category.id','=','enquiry_info.category_id')
        ->leftJoin('group_names','group_names.group_id','=','enquiry_info.group_id')
        ->where(array('customer_info.primary_no'=>$request->mobileNo))
        ->get();

        return response()->json(['status'=>200,'message'=>'Customer History Details','data'=>$customerHistory]);
    }

    public function get_social_enquiry(Request $request)
    {
        $fromDate = $request->date_from;
        $toDate = $request->date_to;

        if($fromDate && $toDate)
        {
            $fromDate = Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay();
            $toDate = Carbon::createFromFormat('Y-m-d', $toDate)->endOfDay();
        }
        else
        {
            $fromDate = Carbon::now()->startOfMonth();
            $toDate = Carbon::now()->endOfMonth();
        }
        
        $results = DB::connection('sales_db')
            ->table('social_enquiry')
            ->whereRaw("created_time >= ? and created_time <= ?", [$fromDate, $toDate])
            ->orderBy('id', 'desc')
            ->paginate($request->per_page);

        return response()->json(['status'=>200,'message'=>'Socail enquiry details','data'=>$results,'from_date'=>$fromDate,'to_date'=>$toDate,'last_page'=>$results->lastPage()]);
    }

    public function update_social_enq_status(Request $request)
    {
        $results = DB::connection('sales_db')->table('social_enquiry')
            ->where('id',$request->enqId)
            ->update(array('status'=>1));
        if($results)
        {
            return response()->json(['status'=>200,'message'=>'Status Updated Succesfully']);
        }
        else
        {
            return response()->json(['status'=>200,'message'=>'Something went wrong. Please Try agin']);
        }
        
    }

    public function get_sent_enquiry(Request $request)
    {
        $enqDetails = DB::connection('sales_db')->table('enquiry_info')
        ->select('enquiry_info.id','enquiry_info.created_date as recived_date','enquiry_info.event_date','enquiry_info.city_from','enquiry_info.city_to','enquiry_info.distance','enquiry_info.customer_id','customer_info.name','customer_info.primary_no','customer_info.email','group_names.name as group_name','product_service.service_name','product_category.category_name')
        ->leftJoin('customer_info','customer_info.id','=','enquiry_info.customer_id')
        ->leftJoin('product_service','product_service.id','=','enquiry_info.service_id')
        ->leftJoin('product_category','product_category.id','=','enquiry_info.category_id')
        ->leftJoin('group_names','group_names.group_id','=','enquiry_info.group_id')
        ->where(array('enq_status'=>1))
        ->orderBy('enquiry_info.id','DESC')
        ->limit(1500)
        ->paginate($request->per_page);
        
        $item_respone = $enqDetails->map(function($item)
        {

            $clientInfo = DB::connection('sales_db')->table('leads_info')
            ->select('leads_info.id as lead_id','leads_info.client_id','leads_info.comp_id','company_info.client_name','company_info.business_name','company_info.email','company_info.mobile_no','company_info.exe_id','lead_quote_status as quote_status')
            ->leftJoin('company_info','company_info.comp_id','=','leads_info.comp_id')
            ->where('enq_id',$item->id)
            ->get();
            
            $item->client_info = $clientInfo;
            return $item;
        });


        return response()->json(['status'=>200,'message'=>'Sent enquiry details','data'=>$enqDetails,'last_page'=>$enqDetails->lastPage()]);
    }

    public function get_item_details(Request $request)
    {
        $itemDetails = DB::connection('sales_db')->table('product_item_type as pit')
        ->select('pit.id','pit.item_type','pit.product_id','pit.service_id')
        ->where(array('status'=>1,'product_id'=>$request->product_id,'service_id'=>$request->service))
        ->get();

        $item_respone = $itemDetails->map(function($item)
        {

            $itemType = DB::connection('sales_db')->table('item_info')
            ->select('item_info.id as item_id','item_info.item_name')
            ->where('type_id',$item->id)
            ->get();
            
            $item->item_info = $itemType;
            return $item;
        });
        return response()->json(['status'=>200,'message'=>'Item details','data'=>$itemDetails]);
    }

    public function update_feedback_comment_client_wise(Request $request)
    {
        $updateQuotes = DB::connection('sales_db')->table('leads_info')
        ->where('id',$request->lead_id)
        ->update(array('quote_status'=>$request->quote_status));
        if($updateQuotes)
        {
            return response()->json(['status'=>200,'message'=>'Quotation Status Updated Successfully']);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try agin.']);
        }
    }

    public function get_enquiry_status()
    {
        $enStatus = DB::connection('sales_db')->table('enq_status')
        ->select('id','name')
        ->where('status',1)->get();
        return response()->json(['status'=>200,'message'=>'Enquiry Status details','data'=>$enStatus]);
    }

    public function get_client_details_by_enqid(Request $request)
    {
        $compIdsString = DB::connection('sales_db')->table('leads_info')
            ->select(DB::raw('GROUP_CONCAT(comp_id) as comp_ids'))
            ->where('enq_id', $request->enqId)
            ->first();

        $compIdsArray = explode(',', $compIdsString->comp_ids);
       
        $clientInfo = DB::connection('sales_db')->table('company_info')
            ->select('client_id', 'comp_id', 'client_name', 'business_name')
            ->whereIn('comp_id', $compIdsArray)
            ->get();
            //return $clientInfo;
        return response()->json(['status'=>200,'message'=>'Client Details','data'=>$clientInfo]);
    }
    
    public function save_enq_feedback_by_cs(Request $request)
    {
        if($request->has('followupDate') && !empty($request->followupDate)) {
            $followup_date = date('Y-m-d H:i', strtotime($request->followupDate));
        } else {
            $followup_date = '';
        }

        if($request->has('movingDate') && !empty($request->movingDate)) {
            $moving_date = date('Y-m-d H:i', strtotime($request->movingDate));
        } else {
            $moving_date = '';
        }

        if ($request->has('callBackDate') && !empty($request->callBackDate)) {
            $call_back_date = date('Y-m-d H:i', strtotime($request->callBackDate));
        } else {
            $call_back_date = '';
        }

        $insert_array = array(
            'enq_id' => $request->enqId,
            'status' => $request->status,
            'remarks' => $request->remarks,
            'followup_date' => $followup_date,
            'moving_date' => $moving_date,
            'call_back_date' => $call_back_date,
            'feedback_status' => $request->feedback_status,
            'budget' => $request->customer_budget,
            'exe_id' => $request->emp_id,
        );

        $insertId = DB::connection('sales_db')->table('customer_engagement_followup')
        ->insertGetId($insert_array);
        if($insertId)
        {
            return response()->json(['status'=>200,'message'=>'Feedback inserted successfully']);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again.']);
        }
    }

    public function get_enq_followup_history_details_by_enqid(Request $request)
    {
        $history = DB::connection('sales_db')->table('customer_engagement_followup as cef')
        ->select('cef.enq_id','cef.status as status_id','cef.followup_date','cef.moving_date','cef.call_back_date','cef.exe_id','cef.created_date','cef.feedback_status','enq_status.name as followup_name','cef.remarks')
        ->leftJoin('enq_status','enq_status.id','=','cef.status')
        ->where('enq_id',$request->enqId)
        ->get();

        $history->map(function ($item) {
            $empName = DB::table('emp_basic_info')
                ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")
                ->where('emp_id', $item->exe_id)
                ->first();
            $item->emp_name = $empName->emp_name;
            return $item;
        });
        return response()->json(['status'=>200,'message'=>'Feedback History Details','data'=>$history]);
    }

    

    // public function get_return_lead_by_return_id(Request $request)
    // {
    //     $details = DB::connection('sales_db')->table('return_lead_info as rli')
    //     ->select('rli.id as return_id','rli.enq_id','rli.lead_id','rli.customer_id','rli.client_id','rli.package_id','rli.client_remarks','rli.status','rli.created_at as return_date','ci.name as customer_name','ci.primary_no as customer_mobile','ci.email as customer_email','ei.city_from','ei.city_to','ei.otp_verified','ei.cs_verified','ei.address_from','ei.address_to','ei.event_date','ei.created_date as recived_date')
    //     ->leftJoin('customer_info as ci','ci.id','=','rli.customer_id','p.product_name','ps.service_name','pc.category_name')
    //     ->leftJoin('enquiry_info as ei','ei.id','=','rli.enq_id')
    //     //->leftJoin('return_lead_reasons as rlr','rlr.id','=','rli.reason_id')
    //     ->leftJoin('product as p','p.id','=','ei.product_id')
    //     ->leftJoin('product_service as ps','ps.id','=','ei.service_id')
    //     ->leftJoin('product_category as pc','pc.id','=','ei.category_id')
    //     ->where(array('rli.id'=>$request->return_id,'rli.status'=>0))
    //     ->get();

    //     $item_respone = $details->map(function($item)
    //     {
    //         $clientInfo = DB::connection('sales_db')->table('leads_info')
    //         ->select('leads_info.comp_id','company_info.client_name','company_info.business_name','company_info.email','company_info.mobile_no','company_info.exe_id')
    //         ->leftJoin('company_info','company_info.comp_id','=','leads_info.comp_id')
    //         ->where('leads_info.enq_id',$item->enq_id)
    //         ->get();
    //         $item->client_info = $clientInfo;
    //         return $item;
    //     });

        

    //     $return_info = $details->map(function($item)
    //     {
    //         $returnInfo = DB::connection('sales_db')->table('return_lead_info as rli')
    //         ->select('rli.id as lead_id','rli.client_id','rli.package_id','rli.client_remarks','rli.reason_id','company_info.client_name','company_info.business_name','company_info.email','company_info.mobile_no','rlr.title')

    //         ->leftJoin('company_info','company_info.comp_id','=','rli.client_id')
    //         ->leftJoin('return_lead_reasons as rlr','rlr.id','=','rli.reason_id')

    //         ->where(array('rli.enq_id'=>$item->enq_id,'rli.status'=>0))
    //         ->get();

    //         $item->return_info = $returnInfo;
    //         return $item;
    //     });

    //     return response()->json(['status'=>200,'message'=>'Return Lead History','data'=>$details]);
    // }

    public function get_return_lead_by_return_id(Request $request)
    {
        $details = DB::connection('sales_db')->table('return_lead_info as rli')
            ->select(
                'rli.id as return_id',
                'rli.enq_id',
                'rli.lead_id',
                'rli.customer_id',
                'rli.client_id',
                'rli.package_id',
                'rli.client_remarks',
                'rli.status',
                'rli.created_at as return_date',
                'ci.name as customer_name',
                'ci.primary_no as customer_mobile',
                'ci.email as customer_email',
                'ei.city_from',
                'ei.city_to',
                'ei.otp_verified',
                'ei.cs_verified',
                'ei.address_from',
                'ei.address_to',
                'ei.event_date',
                'ei.created_date as received_date',
                'p.product_name',
                'ps.service_name',
                'pc.category_name'
            )
            ->leftJoin('customer_info as ci', 'ci.id', '=', 'rli.customer_id')
            ->leftJoin('enquiry_info as ei', 'ei.id', '=', 'rli.enq_id')
            ->leftJoin('product as p', 'p.id', '=', 'ei.product_id')
            ->leftJoin('product_service as ps', 'ps.id', '=', 'ei.service_id')
            ->leftJoin('product_category as pc', 'pc.id', '=', 'ei.category_id')
            ->where([
                'rli.id' => $request->return_id,
                'rli.status' => 0
            ])
            ->get();

        // Map through details to add client and return info
        $details = $details->map(function($item)
        {
            // Fetch client info
            $clientInfo = DB::connection('sales_db')->table('leads_info')
                ->select(
                    'leads_info.comp_id',
                    'company_info.client_name',
                    'company_info.business_name',
                    'company_info.email',
                    'company_info.mobile_no',
                    'company_info.exe_id'
                )
                ->leftJoin('company_info', 'company_info.comp_id', '=', 'leads_info.comp_id')
                ->where('leads_info.enq_id', $item->enq_id)
                ->get();
            $item->client_info = $clientInfo;

            // Fetch return info
            $returnInfo = DB::connection('sales_db')->table('return_lead_info as rli')
                ->select(
                    'rli.id as lead_id',
                    'rli.client_id',
                    'rli.package_id',
                    'rli.client_remarks',
                    'rli.reason_id',
                    'company_info.client_name',
                    'company_info.business_name',
                    'company_info.email',
                    'company_info.mobile_no',
                    'rlr.title as reason_title',
                    'leads_info.comp_id',
                )
                ->leftJoin('leads_info', 'leads_info.id', '=', 'rli.lead_id')
                ->leftJoin('company_info', 'company_info.comp_id', '=', 'leads_info.comp_id')
                ->leftJoin('return_lead_reasons as rlr', 'rlr.id', '=', 'rli.reason_id')
                ->where([
                    'rli.enq_id' => $item->enq_id,
                    //'rli.status' => 0
                ])
                ->get();

            $item->return_info = $returnInfo;
            return $item;
        });

        return response()->json(['status' => 200, 'message' => 'Return Lead History', 'data' => $details]);
    }


    public function get_return_lead_status(Request $request)
    {
        $reasonStatus = DB::connection('sales_db')->table('return_lead_reasons')
        ->select('title as reason_title','id as reason_id')
        ->where(array('category'=>$request->is_fake_lead,'listing_to'=>'reason','status'=>1))
        ->get();

        $commentStatus = DB::connection('sales_db')->table('return_lead_reasons')
        ->select('title as comment_title','id as comment_id')
        ->where(array('category'=>$request->is_fake_lead,'listing_to'=>'comment','status'=>1))
        ->get();
        return response()->json(['status'=>200,'message'=>'Return Lead History','reason_status'=>$reasonStatus,'comment_status'=>$commentStatus]);
    }

    public function update_return_lead_status(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'created_by' => 'required',
            'lead_id' => 'required',
            'is_fake_lead' => 'required',
            'returnComment' => 'required',
            'returnStatus' => 'required',
            'remarks' => 'required',
        ]);

        if($validator->fails()){
            $messages = $validator->messages();
            return response()->json(['messages' => $messages, 'status' => 400]);
        }
        try
        {
            DB::connection('sales_db')->beginTransaction();

            $leadDetails = explode(',', $request->lead_id);
            foreach ($leadDetails as $row)
            {
                $status = ($request->is_fake_lead == 'yes') ? 1 : 2;
                $updateReturnLead = [
                    'verified_status' => $request->returnStatus,
                    'verified_comment' => $request->returnComment,
                    'status' => $status,
                    'verified_remarks' => $request->remarks,
                    'verified_by' => $request->created_by,
                    'verified_date' => date('Y-m-d H:i'),
                ];

                $updateLeadInfo = DB::connection('sales_db')->table('return_lead_info')
                    ->where('id', $row)
                    ->update($updateReturnLead);

                if ($updateLeadInfo)
                {
                    $leadInfo = DB::connection('sales_db')
                        ->table('return_lead_info as rli')
                        ->select('rli.id', 'rli.client_id', 'rli.package_id', 'rli.product_id', 'rli.service_id', 'rli.category_id', 'rli.enq_id', 'si.return_lead', 'si.approved_lead', 'si.id as service_info_id')
                        ->leftJoin('service_info as si', function($join) {
                            $join->on('si.package_id', '=', 'rli.package_id')
                                ->on('si.service_id', '=', 'rli.service_id')
                                ->on('si.category_id', '=', 'rli.category_id');
                        })
                        ->where('rli.id', $row)
                        ->first();

                    if ($status == 1) {
                        $approveLeads = $leadInfo->approved_lead + 1;
                        $returnLeads = $leadInfo->return_lead + 1;
                    } else {
                        $approveLeads = $leadInfo->approved_lead;
                        $returnLeads = $leadInfo->return_lead + 1;
                    }

                    $updateServiceInfo = [
                        'return_lead' => $returnLeads,
                        'approved_lead' => $approveLeads,
                    ];

                    DB::connection('sales_db')->table('service_info')
                        ->where('id', $leadInfo->service_info_id)
                        ->update($updateServiceInfo);
                }
            }

            DB::connection('sales_db')->commit();
            return response()->json(['status' => 200, 'message' => 'Return lead status updated successfully']);
            
        }
        catch (\Exception $e)
        {
            DB::connection('sales_db')->rollBack();
            return response()->json(['status' => 500, 'message' => 'Failed to update return lead status', 'error' => $e->getMessage()]);
        }
    }

    public function add_business_enquiry(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'mobile' => 'required',
            'name' => 'required',
            'business_name'=>'required',
            // 'email' => 'email',
            'group' => 'required',
            'city' => 'required',
            'product_id' => 'required',
            'service_id' => 'required',
            'category_id' => 'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $guestArray = array(
            'name'=>$request->name,
            'business_name'=>$request->business_name,
            'mobile_no'=>$request->mobile,
            'email_id'=>$request->email,
            'city'=>$request->city,
            'group_id'=>$request->group,
            'address'=>$request->address,
            'category'=>$request->category_id,
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            'reg_from'=>'CS',
            'created_by'=>$request->created_by,
            'status'=>0,
        );
        $guestUserId = DB::connection('sales_db')->table('guest_user_details')->insertGetId($guestArray);

        if($guestUserId)
        {
            return response()->json(['status'=>200,'message'=>'Business Leead Added Successfully','data'=>$guestUserId]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Somethingwent wrong in add business lead. Please try again.','followup_id'=>$followupId]);
        }
    }

    public function get_verify_enq_status()
    {
        $details = DB::connection('sales_db')->table('enq_status')
        ->select('id','name','type')
        ->where(array('type'=>'followup','status'=>1))
        ->get();

        return response()->json(['status'=>200,'message'=>'Verify enquiry status details ','data'=>$details]);
    }



    public function get_client_package_lead_details(Request $request)
    {
        $details = DB::connection('sales_db')->table('package_info')
        ->select('package_info.total_lead', 'package_info.sent_lead', 'package_info.package_name')
        ->selectRaw('SUM(return_lead) as return_lead, SUM(approved_lead) as approved_lead, (SUM(approved_lead) / package_info.total_lead) * 100 as approve_percent_lead')
        ->leftJoin('service_info', 'package_info.package_id', '=', 'service_info.package_id')
        ->where('package_info.package_id',$request->package_id)
        ->get();
        return response()->json(['status' => 200, 'message' => 'Package lead details', 'data' => $details]);
    }

    public function get_attempts_details_by_enqid(Request $request)
    {
        $details = DB::connection('sales_db')->table('enquiry_attempt_log')
        ->select('enq_id','enq_status.name as followup', 'remarks','attempted_date','exe_id')
        ->leftJoin('enq_status', 'enq_status.id', '=', 'enquiry_attempt_log.followup_id')
        ->where('enquiry_attempt_log.enq_id',$request->enqId)
        ->get();

        $details->map(function ($item) {
            $empName = DB::table('emp_basic_info')
                ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")
                ->where('emp_id', $item->exe_id)
                ->first();
            $item->emp_name = $empName->emp_name;
            return $item;
        });
        return response()->json(['status' => 200, 'message' => 'Attempted Details', 'data' => $details]);
    }

  public function no_of_attempt_on_enquiry(Request $request)
{
    $data = DB::connection('sales_db')->table('enquiry_info')->select(['id', 'customer_id', 'product_id', 'service_id', 'category_id', 'group_id', 'created_date','no_of_attempt','enq_status','cs_verified','customer_id']);


   if ($request->attempt_type == 'first_attempt') {
        $data->where('no_of_attempt', 1);
    } if ($request->attempt_type == 'second_attempt') {
        $data->where('no_of_attempt', 2);
    } if ($request->attempt_type == 'third_attempt') {
        $data->where('no_of_attempt', 3);
    }

    

    if ($request->group) {
        $data->where('group_id', $request->group);
    }
    if ($request->category) {
        $data->where('category_id', $request->category);
    }
    if ($request->service) {
        $data->where('service_id', $request->service);
    }
    if ($request->product) {
        $data->where('product_id', $request->product); 
    }

    if ($request->start_date && $request->end_date) {
        $data->whereDate('created_date', '>=', $request->start_date)
            ->whereDate('created_date', '<=', $request->end_date);
    } else {
        $data->whereDate('created_date', Carbon::now()->format('Y-m-d'));
    }

    $query = $data->where('enq_status',3)->where('cs_verified',0)->paginate(10);

    $results = $query->map(function ($row) use ($request) {
        $group = DB::connection('sales_db')->table('group_names')->where('group_id', $row->group_id)->first();
        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
        $service = DB::connection('sales_db')->table('product_service')->where('id', $row->service_id)->first();
        $category = DB::connection('sales_db')->table('product_category')->where('id', $row->category_id)->first();
        $customer = DB::connection('sales_db')->table('customer_info')->where('id', $row->customer_id)->first();


        $attempts_data  = DB::connection('sales_db')->table('enquiry_attempt_log')
            ->where('enq_id', $row->id)
            ->select('attempted_date', 'remarks', 'exe_id','followup_id');
            
        if($request->enq_status){
            $attempts_data->where('followup_id',$request->enq_status);

        }

        $attempts = $attempts_data->get();

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

        return [
            'enquiry_id' => $row->id,
            'created_date' => $row->created_date,
            'no_of_attempt'=>$row->no_of_attempt,
            'group_name' => $group->name ?? null,
            'product_name' => $product->product_name ?? null,
            'service_name' => $service->service_name ?? null,
            'category_name' => $category->category_name ?? null,
            'attempts' => strip_tags($formattedAttempts),
            'customer_name'=>$customer->name,
        ];
    });

    return response()->json(['status' => 200, 'message' => 'Enquiry Details', 'data' => $results,'last_page'=>$query->lastPage()]);
}

public function show_enq_status(){
    $data = DB::connection('sales_db')->table('enq_status')->where('type','followup')->where('status',1)->get();
    return response()->json(['status'=>200,'data'=>$data]);
}





}
