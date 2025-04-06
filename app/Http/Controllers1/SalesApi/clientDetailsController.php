<?php
namespace App\Http\Controllers\SalesApi;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\adminSales\package;
use Carbon\Carbon;

use Validator;

class clientDetailsController extends Controller
{
    public function check_client_mobile_number($mobileNo)
    {
        $userInfo = DB::connection('sales_db')->table('guest_user_details')->where('mobile_no',$mobileNo)->count();
        if($userInfo >0)
        {
            return response()->json(['status'=>200,'message'=>'Client already added as a guest client.','data'=>$userInfo]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'This is a new client']);
        }        
    }

    public function get_city_details()
    {
        $cityInfo = DB::connection('sales_db')->table('group_names')->select('group_id','name')->where('status',1)->get();
        return response()->json(['status'=>200,'message'=>'City details','data'=>$cityInfo]);
    }

    public function get_followup_list($callStatus)
    {
        $statusInfo = DB::connection('sales_db')->table('followup_status')->select('id','activity_name','holding_days')->where(array('dispostion'=>$callStatus,'status'=>1))->get();
        return response()->json(['status'=>200,'message'=>'Followup Details','data'=>$statusInfo]);
    }
    
    public function get_next_followup_date($followupId)
    {
        $holdingDays = DB::connection('sales_db')->table('followup_status')->where(array('id'=>$followupId))->first('holding_days');
        return response()->json(['status'=>200,'message'=>'Followup Details','data'=>$holdingDays->holding_days]);
    }

    public function add_guest_client(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'mobile' => 'required',
            'name' => 'required',
            'email' => 'email',
            'city' => 'required',
            'product_id' => 'required',
            'service_id' => 'required',
            'category_id' => 'required',
            'disposition' => 'required',
            'followup_status' => 'required',
            'next_followup_date' => 'required',
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
            'address'=>$request->address,
            'category'=>$request->category_id,
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            'created_by'=>$request->created_by,
        );
        $guestUserId = DB::connection('sales_db')->table('guest_user_details')->insertGetId($guestArray);

        if($guestUserId)
        {
            $followupArray = array(
                'client_type'=>1,
                'client_id'=>$request->guestUserId,
                'product_id'=>$request->product_id,
                'service_id'=>$request->service_id,
                'category_id'=>$request->category_id,
                'disposition'=>$request->disposition,
                'followup_id'=>$request->followup_status,
                'remark'=>$request->remark,
                'next_followup_date'=>Carbon::parse($request->next_followup_date)->format('Y-m-d'),
                'remark'=>$request->remark,
                'call_type'=>1,
                'amount'=>$request->amount,
                'created_by'=>$request->created_by,
            );
            $followupId = DB::connection('sales_db')->table('clients_followup_log')->insertGetId($followupArray);
            if($followupId)
            {
                return response()->json(['status'=>200,'message'=>'User Created Successfully','data'=>$guestUserId]);
            }
            else
            {
                return response()->json(['status'=>201,'message'=>'Somethingwent wrong in client followup creation.','followup_id'=>$followupId]);
            }
            
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Somethingwent wrong in client creation.','followup_id'=>$followupId]);
        }
    }

    public function get_client_details($emp_id)
    {
         $clientInfo = DB::connection('sales_db')->table('guest_user_details')
        ->select('guest_user_details.id','guest_user_details.name','guest_user_details.mobile_no','guest_user_details.email_id','guest_user_details.business_name','product.product_name','product_service.service_name','product_category.category_name','group_names.name as group_name')
        ->leftJoin('group_names','group_names.group_id','=','guest_user_details.city')
        ->leftJoin('product','product.id','=','guest_user_details.product_id')
        ->leftJoin('product_service','product_service.id','=','guest_user_details.service_id')
        ->leftJoin('product_category','product_category.id','=','guest_user_details.category')
        ->where(array('guest_user_details.status'=>0,'guest_user_details.created_by'=>$emp_id))->get();
       
        if($clientInfo)
        {
            return response()->json(['status'=>200,'message'=>'Client Details','data'=>$clientInfo]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }
    
    public function get_today_followup_client_details($emp_id)
    {
        $today = date('Y-m-d');
        $clientInfo = DB::connection('sales_db')->table('clients_followup_log')
        ->select('clients_followup_log.disposition','guest_user_details.id','guest_user_details.name','guest_user_details.mobile_no','guest_user_details.email_id','guest_user_details.business_name','product.product_name','product_service.service_name','product_category.category_name','group_names.name as group_name')
        ->leftJoin('guest_user_details','guest_user_details.id','=','clients_followup_log.client_id')
        ->leftJoin('group_names','group_names.group_id','=','guest_user_details.city')
        ->leftJoin('product','product.id','=','clients_followup_log.product_id')
        ->leftJoin('product_service','product_service.id','=','clients_followup_log.service_id')
        ->leftJoin('product_category','product_category.id','=','clients_followup_log.category_id')
        ->where(array('clients_followup_log.status'=>1,'clients_followup_log.created_by'=>$emp_id,'clients_followup_log.next_followup_date'=>$today))->get();
        
        if($clientInfo)
        {
            return response()->json(['status'=>200,'message'=>'Client Details','data'=>$clientInfo]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function add_client_followup()
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'mobile' => 'required',
            'name' => 'required',
            'email' => 'email',
            'city' => 'required',
            'product_id' => 'required',
            'service_id' => 'required',
            'category_id' => 'required',
            'disposition' => 'required',
            'followup_status' => 'required',
            'next_followup_date' => 'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }
        $followupArray = array(
            'client_type'=>$request->category_id,
            'client_id'=>$request->category_id,
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            'category_id'=>$request->category_id,
            'disposition'=>$request->disposition,
            'followup_id'=>$request->followup_status,
            'remark'=>$request->remark,
            'next_followup_date'=>Carbon::parse($request->next_followup_date)->format('Y-m-d'),
            'remark'=>$request->remark,
            'call_type'=>1,
            'amount'=>$request->amount,
            'created_by'=>$request->created_by,
        );
        $followupId = DB::connection('sales_db')->table('clients_followup_log')->insertGetId($followupArray);
        if($followupId)
        {
            return response()->json(['status'=>200,'message'=>'User Created Successfully','data'=>$guestUserId]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Somethingwent wrong in client followup creation.','followup_id'=>$followupId]);
        }
    }


    public function check_client_followup_details($client_id)
    {
        $followupInfo = DB::connection('sales_db')->table('clients_followup_log')
        ->select('clients_followup_log.disposition','product.product_name','product_service.service_name','product_category.category_name','clients_followup_log.next_followup_date','clients_followup_log.created_by','clients_followup_log.created_date','clients_followup_log.client_id','clients_followup_log.remark','clients_followup_log.client_type','followup_status.activity_name')
        ->leftJoin('followup_status','followup_status.id','=','clients_followup_log.followup_id')
        ->leftJoin('product','product.id','=','clients_followup_log.product_id')
        ->leftJoin('product_service','product_service.id','=','clients_followup_log.service_id')
        ->leftJoin('product_category','product_category.id','=','clients_followup_log.category_id')
        ->where(array('clients_followup_log.client_id'=>$client_id))
        ->orderBy('clients_followup_log.id','desc')
        ->get();
        
        if($followupInfo)
        {
            return response()->json(['status'=>200,'message'=>'Followup Details','data'=>$followupInfo]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function check_client_details($client_id)
    {
        $clientInfo = DB::connection('sales_db')->table('guest_user_details as gud')
        ->select('gud.name','gud.business_name','gud.email_id','gud.mobile_no','gud.state','gud.zipcode','gud.address','gud.status','group_names.name as city_name')
        ->leftJoin('group_names','group_names.group_id','=','gud.city')
        ->where('id',$client_id)->first();
        if($clientInfo)
        {
            return response()->json(['status'=>200,'message'=>'Client Details','data'=>$clientInfo]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function get_document_type()
    {
        $documentType = DB::connection('sales_db')->table('sales_document')
        ->select('*')
        ->where('status',1)->get();
        if($documentType)
        {
            return response()->json(['status'=>200,'message'=>'Document Type','data'=>$documentType]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function get_organization_type()
    {
        $orgType = DB::connection('sales_db')->table('organization_type')
        ->select('*')
        ->where('status',1)->get();
        if($orgType)
        {
            return response()->json(['status'=>200,'message'=>'Document Type','data'=>$orgType]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function add_mature_client_details(Request $request)
    {
        $input = $request->all();
        
        $validator = Validator::make($input, [
            'orgTypeId' => 'required',
            'clientName' => 'required',
            'businessName' => 'required',
            'clientEmail' => 'required',
            'clientMobile' => 'required',
            'clientCity' => 'required',
            'clientState' => 'required',
            'clientPinNo' => 'required',
            'clientAddress' => 'required',
            'clientPanCardNo' => 'required',
            'created_by'=>'required',
            ]);
            if($validator->fails())
            {
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
            }

            $lastClientId = DB::connection('sales_db')->table('client_info')
            ->select('client_id')
            ->orderBy('id','desc')
            ->first();

            $newClientId = $lastClientId + 1;
            $clientInfoArray = array(
                'client_id'=>$newClientId,
                'client_name'=>$request->clientName,
                'business_name'=>$request->businessName,
                'email'=>$request->clientEmail,
                'mobile_no'=>$request->clientMobile,
                'city'=>$request->clientCity,
                'state'=>$request->clientState,
                'pin_no'=> $request->clientPinNo,
                'address'=>$request->clientAddress,
                'org_type'=>$request->orgTypeId,
                'status'=>0,
                'pan_card'=>$request->orgTypeId,
                'exe_id'=>$request->created_by,
            );
            $clientId = DB::connection('sales_db')->table('client_info')->insertGetId($clientInfoArray);
            if($clientId)
            {
                $companyInfoArray = array(
                    'client_id'=>$clientId,
                    'client_name'=>$request->clientName,
                    'business_name'=>$request->businessName,
                    'email'=>$request->clientEmail,
                    'mobile_no'=>$request->clientMobile,
                    'city'=>$request->clientCity,
                    'state'=>$request->clientState,
                    'pin_no'=> $request->clientPinNo,
                    'address'=>$request->clientAddress,
                    'status'=>0,
                    'exe_id'=>$request->created_by,
                );
                $companyId = DB::connection('sales_db')->table('company_info')->insertGetId($companyInfoArray);
                
                if($companyId)
                {
                    $walletArray = array(
                        'client_id'=>$clientId,
                        'created_by'=>$request->created_by
                    );
                    $walletId = DB::connection('sales_db')->table('wallet_info')->insertGetId($walletArray);
                    if($walletId)
                    {
                        return response()->json(['status'=>200,'message'=>'Client Created successfully','data'=>$companyId]);
                    }
                    else
                    {
                        return response()->json(['status'=>201,'message'=>'Something went wrong in wallet info creation. Please try again']);
                    }
                    
                }
                else
                {
                    return response()->json(['status'=>201,'message'=>'Something went wrong in comapny info creation. Please try again']);
                }
            }
            else
            {
                return response()->json(['status'=>201,'message'=>'Something went wrong in client info creation. Please try again']);
            }

    }

    public function check_mature_client_details($emp_id)
    {
        $matureClientInfo = DB::connection('sales_db')->table('client_info')
        ->select('*')
        ->where(array('status'=>0))->get();
        if($matureClientInfo)
        {
            return response()->json(['status'=>200,'message'=>'Mature Client Details','data'=>$matureClientInfo]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'No client found']);
        }
    }

    public function get_package_count($client_id)
    {
        $totalPackage = DB::connection('sales_db')->table('package_info')
        
        ->where(array('client_id'=>$client_id))
        ->count();
        
        $activePackage = DB::connection('sales_db')->table('package_info')
        
        ->where(array('package_status'=>1,'client_id'=>$client_id))
        ->count();
        
        $deactivePackage = DB::connection('sales_db')->table('package_info')
        
        ->where(array('package_status'=>0,'client_id'=>$client_id))
        ->count();

        $array = array(
            'total_package'=>$totalPackage,
            'active_package'=>$activePackage,
            'deactive_package'=>$deactivePackage,
        );
        return response()->json(['status'=>200,'message'=>'Client Packages Count','data'=>$array]);
    }

    public function get_client_package_details($client_id)
    {
        $clientPackageInfo = DB::connection('sales_db')->table('package_info')
        ->select('product.product_name','package_duration.name as duration_name','package_info.package_amount','package_info.tax_amount','package_info.package_start_date','package_info.package_end_date','package_info.package_id','package_info.total_lead','package_info.sent_lead','package_info.created_by','package_info.created_date','package_info.admin_status','package_info.finance_status','package_info.sales_status','package_info.package_status')
        ->leftJoin('product','product.id','=','package_info.product_id')
        ->leftJoin('package_duration','package_duration.id','=','package_info.package_duration')
        ->where(array('client_id'=>$client_id))
        ->get();
        
        if($clientPackageInfo)
        {
            return response()->json(['status'=>200,'message'=>'Client Packages Details','data'=>$clientPackageInfo]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'No Package found']);
        }
    }

    public function get_wallet_details($client_id)
    {
        $walletInfo = DB::connection('sales_db')->table('wallet_info')
        ->where(array('client_id'=>$client_id))
        ->first();
        return response()->json(['status'=>200,'message'=>'Client Wallet info','data'=>$walletInfo]);
    }

    public function get_wallet_history($client_id)
    {
        $walletHistory = DB::connection('sales_db')->table('wallet_history')
        ->select('client_info.client_name','wallet_history.*')
        ->leftJoin('client_info','client_info.client_id','=','wallet_history.client_id')
        ->where(array('wallet_history.client_id'=>$client_id))
        ->get();
        return response()->json(['status'=>200,'message'=>'Client Wallet History Count','data'=>$walletHistory]);
    }

    public function add_new_wallet_amount(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'wallet_id'=>'required',
            'amount' => 'required',
            'payment_type' => 'required',
            'transaction_id' => 'required',
            'remarks' => 'required',
            'created_by' => 'required',
            'client_id' => 'required',
            'payment_date'=>'required',
            ]);
            if($validator->fails())
            {
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
            }

            $balanceAmount = DB::connection('sales_db')->table('wallet_info')
            ->select('balance')
            ->where(array('wallet_id'=>$request->wallet_id,'client_id'=>$request->client_id))->first();

            $walletHistoryArray = array(
                'wallet_id'=>$request->wallet_id,
                'client_id'=>$request->client_id,
                'bank_name'=>$request->bankName,
                'payment_type'=>$request->payment_type,
                'transaction_id'=>$request->service_id,
                'utr_no'=>$request->utr_no,
                'transaction_mode'=>'Cr',
                'credit'=>$request->amount,
                'debit'=>0,
                'balance'=>$balanceAmount->balance + $request->amount,
                'status'=>0,
                'transaction_date'=>$request->payment_date,
                'remarks'=>$request->remarks,
            );
            $history_id = DB::connection('sales_db')->table('wallet_history')->insertGetId($walletHistoryArray);
            if($history_id)
            {
                return response()->json(['status'=>200,'message'=>'Amount added successfully. Waiting for financial approval. After approval, the amount will be reflected in your balance','data'=>$history_id]);
            }
            else
            {
                return response()->json(['status'=>201,'message'=>'Somethingwent wrong in client followup creation.','followup_id'=>$followupId]);
            }
    }
    public function sales_document_type_list(){
        $data = [];
        $document_list = DB::connection('sales_db')->table('sales_document')->get();
    
        foreach ($document_list as $row) {
            $package_type_id = explode(',', $row->package_type_id);
            $packageTypes = DB::connection('sales_db')->table('package_type')
                ->whereIn('id', $package_type_id)
                ->pluck('name')
                ->implode(',');
            $category =DB::connection('sales_db')->table('product_category')->where('id',$row->product_category_id)->first();
            if($category){
                $category = $category->category_name;

            }
            else{
                $category = ' ';
            }
            $data[] = [
                'id'=>$row->id,
                'package_name' => $packageTypes,
                'document_name'=>$row->document_name,
                'product_category'=>$category,
                'status'=>$row->status,
               ];
        }
    
        return response()->json(['status'=>200,'message'=>'Document List','data'=>$data]);


    }
    public function sales_package_type(){
        $package_type = DB::connection('sales_db')->table('package_type')->get(['id','name']);
        return response()->json(['status'=>200,'message'=>'Package Type List','data'=> $package_type]);
    }

    public function product_category(){
        $category_type = DB::connection('sales_db')->table('product_category')->get(['id','category_name']);
        return response()->json(['status'=>200,'message'=>'Product Category List','data'=>$category_type]);

    }
    public function save_sales_document(Request $request){
        $input = $request->all();
        //return $input;
            $validator = Validator::make($input, [
                'package_id' => 'required',
                'category'=>'required',
                'document_name'=>'required',
                'is_required'=>'required',
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
              $data = array('package_type_id'=>implode(',',$request->package_id),
              'product_category_id'=>$request->category,'document_name'=>$request->document_name,
              'is_required'=>$request->is_required,'created_by'=>$request->emp_id);
              DB::connection('sales_db')->table('sales_document')->insert($data);

             // return $request->is_required;
                return response()->json(['status'=>200,'message'=>'Document Created Successfully']);
        
           
             
        }
        public function sales_doc_status($id){
            $doc = DB::connection('sales_db')->table('sales_document')->where('id',$id)->first();
            return response()->json(['status'=>200,'message'=>'List Of Document','data'=>$doc->status]);

        }
        public function sales_document_status_change(Request $request){
            $request->validate([
                'doc_id' => 'required',
                'status' => 'required|in:0,1', 
            ]);
            $doc = DB::connection('sales_db')->table('sales_document')
               ->where('id',$request->doc_id)->update(['status'=>$request->status]);
        
            //$doc = SalesDocument::findOrFail($request->doc_id);
            //dd($company);
        
            // Update the status of the company
            //$doc->status = $request->status;
            //$doc->save();
           
                return response()->json(['status' => 200, 'message' => 'Document status updated successfully']);

        }
        public function sales_document_edit($id){
            $sales_doc =  DB::connection('sales_db')->table('sales_document')->where('id',$id)->first();
            return response()->json(['status' => 200, 'data'=>$sales_doc]);

        }
        public function sales_document_update(Request $request,$id){
            $input = $request->all();
        //return $input;
            $validator = Validator::make($input, [
                'package_id' => 'required',
                'category'=>'required',
                'document_name'=>'required',
                'is_required'=>'required',
            ]);

            $data = array('package_type_id'=>implode(',',$request->package_id),
            'product_category_id'=>$request->category,'document_name'=>$request->document_name,
            'is_required'=>$request->is_required,'created_by'=>$request->emp_id);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
              DB::connection('sales_db')->table('sales_document')->where('id',$id)
              ->update(['package_type_id'=>implode(',',$request->package_id),
              'product_category_id'=>$request->category,
              'document_name'=>$request->document_name,'is_required'=>$request->is_required,
              'created_by'=>$request->emp_id]);
             // return $request->is_required;
           
        
                return response()->json(['status'=>200,'message'=>'Document Updated Successfully']);

        }
        

}
