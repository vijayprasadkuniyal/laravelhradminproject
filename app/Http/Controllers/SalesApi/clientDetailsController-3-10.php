<?php
namespace App\Http\Controllers\SalesApi;
use DB;
use File;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\adminSales\package;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Validator;
use App\Http\Controllers\ThirdPartyApi\CommunicationApis;
use App\Events\RaiseRequest;

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
            'business_name'=>'required',
            //'email' => 'email',
            'group' => 'required',
            'city' => 'required',
            'product_id' => 'required',
            'service_id' => 'required',
            'disposition' => 'required',
            'followup_status' => 'required',
            'remarks' => 'required',
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
            'created_by'=>$request->created_by,
        );
        $guestUserId = DB::connection('sales_db')->table('guest_user_details')->insertGetId($guestArray);

        if($guestUserId)
        {
            $followupArray = array(
                'client_type'=>1,
                'client_id'=>$guestUserId,
                'product_id'=>$request->product_id,
                'service_id'=>$request->service_id,
                'category_id'=>$request->category_id,
                'disposition'=>$request->disposition,
                'followup_id'=>$request->followup_status,
                'remark'=>$request->remark,
                'next_followup_date'=>Carbon::parse($request->next_followup_date)->format('Y-m-d'),
                'remark'=>$request->remarks,
                'call_type'=>1,
                'refer_package_id'=>$request->prePackageId,
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


    public function add_client_followup(Request $request)
    {
        $input = $request->all();
        //return $input;
        $validator = Validator::make($input, [
            'client_id' => 'required',
            'client_type' => 'required',
            'city_id' => 'required',
            'product_id' => 'required',
            'service_id' => 'required',
            //'refer_package_id' => 'required',
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
            'client_type'=>$request->client_type,
            'client_id'=>$request->client_id,
            'city_id'=>$request->city_id,
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            'category_id'=>$request->category_id,
            'disposition'=>$request->disposition,
            'followup_id'=>$request->followup_status,
            'next_followup_date'=>Carbon::parse($request->next_followup_date)->format('Y-m-d'),
            'remark'=>$request->remarks,
            'call_type'=>1,
            'refer_package_id'=>$request->refer_package_id,
            'created_by'=>$request->created_by,
        );
        $followupId = DB::connection('sales_db')->table('clients_followup_log')->insertGetId($followupArray);
        if($followupId)
        {
            return response()->json(['status'=>200,'message'=>'Followup created Successfully','data'=>$followupId]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong in client followup creation.','followup_id'=>$followupId]);
        }
    }


    public function check_client_followup_details($client_id)
    {
        // $followupInfo = DB::connection('sales_db')->table('clients_followup_log')
        // ->select('clients_followup_log.disposition','product.product_name','product_service.service_name','product_category.category_name','clients_followup_log.next_followup_date','clients_followup_log.created_by','clients_followup_log.created_date','clients_followup_log.client_id','clients_followup_log.remark','clients_followup_log.client_type','followup_status.activity_name')

        // ->leftJoin('followup_status','followup_status.id','=','clients_followup_log.followup_id')
        // ->leftJoin('product','product.id','=','clients_followup_log.product_id')
        // ->leftJoin('product_service','product_service.id','=','clients_followup_log.service_id')
        // ->leftJoin('product_category','product_category.id','=','clients_followup_log.category_id')
        // ->where(array('clients_followup_log.client_id'=>$client_id))
        // ->orderBy('clients_followup_log.id','desc')
        // ->get(); created_by

        $followupInfo = DB::connection('sales_db')->table('clients_followup_log')
        ->select('clients_followup_log.*','product.product_name','product_service.service_name','product_category.category_name','followup_status.activity_name','pre_package.package_name','pre_package.package_price','group_names.name as city_name')

        ->leftJoin('product','product.id','=','clients_followup_log.product_id')
        ->leftJoin('product_service','product_service.id','=','clients_followup_log.service_id')
        ->leftJoin('product_category','product_category.id','=','clients_followup_log.category_id')
        ->leftJoin('followup_status','followup_status.id','=','clients_followup_log.followup_id')
        ->leftJoin('pre_package','pre_package.id','=','clients_followup_log.refer_package_id')
        ->leftJoin('group_names','group_names.group_id','=','clients_followup_log.city_id')
        
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

    public function check_last_followup_details(Request $request)
    {
        $followupInfo = DB::connection('sales_db')->table('clients_followup_log as cfl')
        ->select('cfl.next_followup_date', 'cfl.created_by', 'cfl.client_id', 'cfl.followup_id',
        'cfl.created_date')
        ->selectRaw('DATEDIFF(cfl.next_followup_date, cfl.created_date) as dateDifference')
        ->leftJoin('followup_status', 'followup_status.id', '=', 'cfl.followup_id')
        ->where('client_id',$request->client_id)
        ->orderBy('cfl.id','DESC')
        ->first();

        $loginEmplId = $request->emp_id;

        $nextFollowupDate = $followupInfo->next_followup_date;
        $followupStatusId = ['4', '5', '6', '7', '9', '19', '20', '21', '22'];
        $followupId = $followupInfo->followup_id;
        $followupExist = in_array($followupId, $followupStatusId);
        
        
        
        

        if ($followupInfo) {
            if ($nextFollowupDate > date('Y-m-d')) 
            {
                if ($loginEmplId == $followupInfo->created_by) {
                    $remaingDays='0';
                    $followupStatus = '1'; // Show follow-up button
                } else {
                    $remaingDays='0';
                    $followupStatus = '2'; // Hide follow-up button
                }
            }
            else 
            {
                $remaingDays = $followupInfo->dateDifference;
                $followupStatus = '2'; // Hide follow-up button
            }
        } else {
            $remaingDays='0';
            $followupStatus = '1'; // Show follow-up button
        }
        

        return response()->json(['status'=>200,'remaingDays'=>$remaingDays,'followupStatus'=>$followupStatus,'lastFolloeupBy'=>$followupInfo->created_by,'clientStatus'=>'4','nextFollowupDate'=>$nextFollowupDate]);
    }

    public function check_client_details($client_id)
    {
        $clientInfo = DB::connection('sales_db')->table('guest_user_details as gud')
        ->select('gud.name','gud.business_name','gud.email_id','gud.mobile_no','gud.state','gud.zipcode','gud.address','gud.status','gud.city as city_name','gud.state','gud.pancard','gud.group_id','gud.product_id','gud.service_id')
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



    public function get_document_type($org_type, $product_id, $service_id)
    {
        $documentType = DB::connection('sales_db')->table('sales_document')
            ->select('sales_document.*', 'document_type.document_name')
            ->leftJoin('document_type', 'document_type.id', '=', 'sales_document.doc_type_id')
            ->where('sales_document.status', 1)
            ->whereRaw('FIND_IN_SET(?, org_id)', [$org_type])
            ->whereRaw('FIND_IN_SET(?, product_id)', [$product_id])
            ->whereRaw('FIND_IN_SET(?, service_id)', [$service_id])
            ->get();

        if($documentType)
        {
            return response()->json(['status' => 200, 'message' => 'Document Type', 'data' => $documentType]);
        }
        else
        {
            return response()->json(['status' => 201, 'message' => 'Something went wrong. Please try again']);
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
            //client details
            'clientName' => 'required',
            'clientEmail' => 'required',
            'clientMobile' => 'required',
            'clientCity' => 'required',
            'clientState' => 'required',
            'clientZipcode' => 'required',
            'clientAddress' => 'required',
            'clientPanCardNo' => 'required',
            'created_by'=>'required',
            'guestClientId'=>'required',
            //company details
            'businessName' => 'required',
            'orgTypeId' => 'required',
            'clientProduct' => 'required',
            'clientService' => 'required',
            'businessEmail'=>'required',
            'businessMobile'=>'required',
            'companyCity'=>'required',
            'companyState'=>'required',
            'companyAddress'=>'required',
           //'companyPanCardNo'=>'required',
            'companyZipcode'=>'required',
            'clientGroup'=>'required',
        ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $lastClientId = DB::connection('sales_db')->table('client_info')
        ->select('id')
        ->orderBy('id','desc')
        ->first();

            $newClientId = $lastClientId->id + 1;
            $clientInfoArray = array(
                'client_id'=>$newClientId,
                'guest_client_id'=>$request->guestClientId,
                'client_name'=>$request->clientName,
                'email'=>$request->clientEmail,
                'mobile_no'=>$request->clientMobile,
                'city'=>$request->clientCity,
                'state'=>$request->clientState,
                'group_id'=>$request->clientGroup,
                'zipcode'=> $request->clientZipcode,
                'address'=>$request->clientAddress,
                'status'=>1,
                'pan_card'=>$request->clientPanCardNo,
                'exe_id'=>$request->created_by,
            );
            $clientId = DB::connection('sales_db')->table('client_info')->insertGetId($clientInfoArray);
            if($clientId)
            {
                $companyInfoArray = array(
                    'client_id'=>$clientId,
                    'client_name'=>$request->clientName,
                    'business_name'=>$request->businessName,
                    'email'=>$request->businessEmail,
                    'mobile_no'=>$request->businessMobile,
                    'city'=>$request->companyCity,
                    'gst_state'=>$request->companyState,
                    'zipcode'=> $request->companyZipcode,
                    'address'=>$request->companyAddress,
                    'product_id'=>$request->clientProduct,
                    'client_services'=>$request->clientService,
                    'organization_type'=>$request->orgTypeId,
                    'group_id'=>$request->clientGroup,
                    'status'=>1,
                    'exe_id'=>$request->created_by,
                );
                $companyId = DB::connection('sales_db')->table('company_info')->insertGetId($companyInfoArray);
                
                if($companyId)
                {
                    $walletArray = array(
                        'client_id'=>$clientId,
                        'guest_client_id'=>$request->guestClientId,
                        'created_by'=>$request->created_by
                    );
                    $walletId = DB::connection('sales_db')->table('wallet_info')->insertGetId($walletArray);
                    if($walletId)
                    {

                        $guestUpdateArray = array(
                            'name'=>$request->clientName,
                            'business_name'=>$request->businessName,
                            'mobile_no'=>$request->clientMobile,
                            'email_id'=>$request->clientEmail,
                            'city'=>$request->clientCity,
                            'state'=>$request->clientState,
                            'address'=>$request->clientAddress,
                            'zipcode'=>$request->clientZipcode,
                            'pancard'=>$request->clientPanCardNo,
                            'client_id'=>$clientId,
                            'status'=>1,
                            'is_matured'=>0,
                            'mature_date'=>date('Y-m-d'),
                            'mature_by'=>$request->created_by,
                        );

                        DB::connection('sales_db')->table('guest_user_details')
                        ->where('id',$request->guestClientId)
                        ->update($guestUpdateArray);

                        //mature client_id and comp_id updated to document_info table.
                        DB::connection('sales_db')->table('document_info')
                        ->where('guest_client_id',$request->guestClientId)
                        ->update(array('client_id'=>$clientId,'comp_id'=>$companyId));
                        
                        
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

    public function add_new_company_details(Request $request)
    {
        $input = $request->all();
        
        $validator = Validator::make($input,[
            'orgTypeId' => 'required',
            'clientProduct' => 'required',
            'clientService' => 'required',
            'businessName' => 'required',
            'clientEmail' => 'required',
            'clientMobile' => 'required',
            'clientCity' => 'required',
            'clientState' => 'required',
            'clientPinNo' => 'required',
            'clientAddress' => 'required',
            'clientPanCardNo' => 'required',
            'created_by'=>'required',
            'client_id'=>'required',
            'group_id'=>'required',
            ]);
            if($validator->fails())
            {
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
            }

            $companyInfoArray = array(
                'client_id'=>$request->client_id,
                'product_id'=>$request->clientProduct,
                'client_services'=>$request->clientService,
                'group_id'=>$request->group_id,
                'business_name'=>$request->businessName,
                'email'=>$request->clientEmail,
                'mobile_no'=>$request->clientMobile,
                'city'=>$request->clientCity,
                'gst_state'=>$request->clientState,
                'zipcode'=> $request->clientPinNo,
                'address'=>$request->clientAddress,
                'organization_type'=>$request->orgTypeId,
                'status'=>1,
                'exe_id'=>$request->created_by,
            );
            $companyId = DB::connection('sales_db')->table('company_info')->insertGetId($companyInfoArray);
            
            if($companyId)
            {
                $docUpdate = DB::connection('sales_db')->table('document_info')
                    ->where(array('client_id'=>$request->client_id,'comp_id'=>$request->client_id))
                    ->update(array('comp_id'=>$companyId));
                if($docUpdate)
                {
                    return response()->json(['status'=>200,'message'=>'Client Created successfully','data'=>$companyId]);
                }
                else
                {
                    return response()->json(['status'=>200,'message'=>'Client Created successfully','data'=>$companyId]);
                }
            }
            else
            {
                return response()->json(['status'=>201,'message'=>'Something went wrong in comapny info creation. Please try again']);
            }
        }

    public function check_mature_client_details($emp_id)
    {
        $matureClientInfo = DB::connection('sales_db')->table('client_info')
        ->select('client_info.*','client_status.status_name')
        ->leftJoin('client_status','client_status.id','=','client_info.status')
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


    public function check_mature_client_company_details($emp_id,$clientid)
    {
        $matureClientCompanyInfo = DB::connection('sales_db')->table('client_info')
        ->select('*')
        ->where(array('client_id'=>$clientid))->get();
        if($matureClientCompanyInfo)
        {
            return response()->json(['status'=>200,'message'=>'Mature client company details','data'=>$matureClientCompanyInfo]);
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
        ->select('product.product_name','package_duration.name as duration_name','package_info.package_amount','package_info.tax_amount','package_info.package_start_date','package_info.package_end_date','package_info.package_id','package_info.total_lead','package_info.sent_lead','package_info.created_by','package_info.created_date','package_info.admin_status','package_info.finance_status','package_info.package_status','package_info.paid_amount','package_info.due_amount','package_info.due_status','package_info.due_lead','package_info.package_name',

        'pre_package.id','pre_package.package_status as status','pre_package.total_lead','pre_package.is_partial_payment','pre_package.total_amount as package_price','package_category.name as category_name','package_type.name as package_type_name','pre_package.city_id','pre_package.group_id')


        ->selectRaw("GROUP_CONCAT(DISTINCT(payment_history.invoice_name)) as invoice_url")
        ->selectRaw("GROUP_CONCAT(DISTINCT(ps.service_name)) as service_name_list")
        ->leftJoin('pre_package','pre_package.id','=','package_info.pre_package_id')
        ->leftJoin('product','product.id','=','package_info.product_id')
        ->leftJoin('package_duration','package_duration.id','=','package_info.package_duration')
        ->leftJoin('package_type','package_type.id','=','pre_package.package_type')
        ->leftJoin('package_category','package_category.id','=','pre_package.package_category')
        ->leftJoin('payment_history',function($join1)
        {
            $join1->whereRaw("Find_in_set(payment_history.package_id,package_info.package_id)");
        })
        ->leftJoin('product_service as ps',function($join2)
        {
            $join2->whereRaw("FIND_IN_SET(ps.id,pre_package.service_id)");
        })
        ->where(array('package_info.client_id'=>$client_id))
        ->groupBy('package_info.package_id')
        ->orderBy('package_info.package_id','DESC')
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

    public function get_wallet_details($client_id,$clientType)
    {
        if($clientType ==1)
        {
            $walletInfo = DB::connection('sales_db')->table('wallet_info')
        ->where(array('client_id'=>$client_id))
        ->first();
        }
        else
        {
            $walletInfo = DB::connection('sales_db')->table('wallet_info')
        ->where(array('guest_client_id'=>$client_id))
        ->first();
        }
        
        return response()->json(['status'=>200,'message'=>'Client Wallet info','data'=>$walletInfo]);
    }

    public function get_wallet_history(Request $request,$client_id)
    {
        $walletHistory = DB::connection('sales_db')->table('wallet_history')
        ->select('client_info.client_name','wallet_history.*')
        ->leftJoin('client_info','client_info.client_id','=','wallet_history.client_id')
        ->where(array('wallet_history.client_id'=>$client_id))
        ->orderBy('id','DESC')
        ->paginate($request->per_page);
        
        return response()->json(['status'=>200,'message'=>'Client Wallet History Count','data'=>$walletHistory,'last_page'=>$walletHistory->lastPage()]);
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

            $checkTxnId = DB::connection('sales_db')->table('wallet_history')
            ->where(array('transaction_id'=>$request->transaction_id))->first();
            if($checkTxnId)
            {
                //$messages=$validator->messages();
                return response()->json(["message"=>'The transaction has already been added to client wallet. You should check client payment history.','status'=>201]); 
            }
            $walletHistoryArray = array(
                'wallet_id'=>$request->wallet_id,
                'client_id'=>$request->client_id,
                'bank_name'=>$request->bankName,
                'payment_type'=>$request->payment_type,
                'transaction_id'=>$request->transaction_id,
                'utr_no'=>$request->utr_no,
                'transaction_mode'=>'Cr',
                'credit'=>$request->amount,
                'debit'=>0,
                //'balance'=>$balanceAmount->balance_amount + $request->amount,
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
                return response()->json(['status'=>201,'message'=>'Something went wrong. Please try agian after some time.']);
            }
    }

    public function get_mature_client_details($emp_id)
    {
        $clientInfo = DB::connection('sales_db')->table('client_info')
        ->select('client_info.id','client_info.client_id','client_info.client_name','client_info.adhar_card','client_info.mobile_no','client_info.email','client_info.zipcode','client_info.address','client_info.pan_card','client_info.status','client_info.exe_id','client_info.city')
        ->where(array('client_info.exe_id'=>$emp_id))->get();
       
        if($clientInfo)
        {
            return response()->json(['status'=>200,'message'=>'Client Details','data'=>$clientInfo]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function mature_client_details($client_id)
    {
        $clientInfo = DB::connection('sales_db')->table('client_info')
        ->select('client_info.id','client_info.client_id','client_info.client_name','client_info.adhar_card','client_info.mobile_no','client_info.email','client_info.zipcode','client_info.address','client_info.pan_card','client_info.status','client_info.exe_id','client_info.city','client_info.state','client_status.status_name')
        ->leftJoin('client_status','client_status.id','=','client_info.status')
        ->where(array('client_info.client_id'=>$client_id))->first();
        
        if($clientInfo)
        {
            return response()->json(['status'=>200,'message'=>'Client details by client id','data'=>$clientInfo]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function get_client_followup_details($client_id)
    {
        $clientFollowupInfo = DB::connection('sales_db')->table('clients_followup_log')
        ->select('clients_followup_log.*','product.product_name','product_service.service_name','product_category.category_name','followup_status.activity_name','pre_package.package_name','pre_package.package_price','group_names.name as city_name')

        ->leftJoin('product','product.id','=','clients_followup_log.product_id')
        ->leftJoin('product_service','product_service.id','=','clients_followup_log.service_id')
        ->leftJoin('product_category','product_category.id','=','clients_followup_log.category_id')
        ->leftJoin('followup_status','followup_status.id','=','clients_followup_log.followup_id')
        ->leftJoin('pre_package','pre_package.id','=','clients_followup_log.refer_package_id')
        ->leftJoin('group_names','group_names.group_id','=','clients_followup_log.city_id')
        ->where(array('client_info.client_id'=>$client_id))->get();
        
        if($clientFollowupInfo)
        {
            return response()->json(['status'=>200,'message'=>'Client details by client id','data'=>$clientFollowupInfo]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Followup not found.']);
        }
    }

    public function get_sale_package_details($package_id)
    {
        $prePackageDetails = DB::connection('sales_db')->table('pre_package')
        ->select('package_price','total_lead')
        ->where('id',$package_id)
        ->first();
        return response()->json(['status'=>200,'message'=>'Pre Package details by package id','data'=>$prePackageDetails]);
    }

    public function get_tax_and_reg_details($product_id)
    {
        $prePackageDetails = DB::connection('sales_db')->table('reg_and_tax_details')
        ->select('reg_amount','gst_percent')
        ->where('id',$product_id)
        ->first();
        return response()->json(['status'=>200,'message'=>'gst and reg details by product id','data'=>$prePackageDetails]);
    }

    public function create_client_package()
    {
        $input = $request->all();
        //return $input;
        $validator = Validator::make($input, [
            'client_id' => 'required',
            'client_type' => 'required',
            'city_id' => 'required',
            'product_id' => 'required',
            'service_id' => 'required',
            'refer_package_id' => 'required',
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
            'client_type'=>$request->client_type,
            'client_id'=>$request->client_id,
            'city_id'=>$request->city_id,
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            'category_id'=>$request->category_id,
            'disposition'=>$request->disposition,
            'followup_id'=>$request->followup_status,
            //'next_followup_date'=>Carbon::parse($request->next_followup_date)->format('Y-m-d'),
            'remark'=>$request->remarks,
            'call_type'=>1,
            'refer_package_id'=>$request->refer_package_id,
            'created_by'=>$request->created_by,
        );
        $followupId = DB::connection('sales_db')->table('clients_followup_log')->insertGetId($followupArray);
        if($followupId)
        {
            
            return response()->json(['status'=>200,'message'=>'Followup created Successfully','data'=>$followupId]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong in client followup creation.','followup_id'=>$followupId]);
        }
    }


    public function check_client_pancard_number($pancard_no,$clientId)
    {
        $check_pancard = DB::connection('sales_db')->table('document_info')
        ->select('doc_number')
        ->where(array('doc_number'=>$pancard_no,'doc_type_id'=>2))
        ->where('guest_client_id','!=',$clientId)
        ->first();
        if($check_pancard)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function check_company_pancard_number($pancard_no)
    {
        $check_pancard = DB::connection('sales_db')->table('document_info')
        ->select('doc_number')
        ->where(array('doc_number'=>$pancard_no,'doc_type_id'=>2))
        ->first();
        if($check_pancard)
        {
            return 1;
        }
        else
        {
            return 0;
        }
    }

    public function get_client_company_details($client_id)
    {
        $compamyDetails = DB::connection('sales_db')->table('company_info')
        ->select('*')
        ->where('client_id',$client_id)
        ->get();

        $compamyDetails->map(function ($item) {
            $empName = DB::table('emp_basic_info')
                ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")
                ->where('emp_id', $item->exe_id)
                ->first();
            $item->emp_name = $empName->emp_name;
            return $item;
        });
        return response()->json(['status'=>200,'message'=>'Client Compamny Details','data'=>$compamyDetails]);
    }

    public function renew_current_package($id)
    {
        $packageInfo = DB::connection('sales_db')->table('package_info')
        ->select('*')
        ->where('package_id',$id)
        ->first();

        $walletInfo = DB::connection('sales_db')->table('wallet_info')
        ->select('balance_amount')
        ->where('client_id',$packageInfo->client_id)
        ->first();

        if($walletInfo->balance_amount < $packageInfo->package_amount)
        {
            return response()->json(['status'=>200,'message'=>'You dont have sufficent balance for buy this package']);
        }
        else
        {

        }
    }

    public function get_tax_and_service_charges($productId)
    {
        $taxAndServiceCharges = DB::connection('sales_db')->table('reg_and_tax_details')
        ->select('*')
        ->where(array('product_id'=>$productId,'status'=>1))
        ->first();
        return response()->json(['status'=>200,'message'=>'tax and service charges','data'=>$taxAndServiceCharges]);
    }

    public function uplode_client_document(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
          'document_type' => 'required',
          'document_no'=>'required',
          'document_file'=>'required',
          'client_id'=>'required',
          'created_by'=>'required',
         ]);

        if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $docFiles = $request->file('document_file');
       
        if($docFiles)
        {
            $destinationPath = 'client_documents/'.$request->client_id;
            if(!File::exists($destinationPath))
            {
                File::makeDirectory($destinationPath, $mode = 0777, true, true);
            }
            $docUrl=array();
            $i=1;
            foreach($docFiles as $docFile)
            {
                $client_document = $i++.'_'.date('YmdHis')."." .$docFile->getClientOriginalExtension();
                $docFile->move($destinationPath, $client_document);
                //$docUrl[] = url('/').'/client_documents/'.$request->client_id.'/'.$client_document;
                $docUrl[] = 'client_documents/'.$request->client_id.'/'.$client_document;
            }
        }

        $docUrls=implode(',',$docUrl);
        

        $checkDoc = DB::connection('sales_db')->table('document_info')
        ->select('*')
        ->where(array('guest_client_id'=>$request->client_id,'doc_type_id'=>$request->document_type))
        ->first();
        if($checkDoc)
        {
            $update_doc = array(
                'guest_client_id'=>$request->client_id,
                'doc_type_id'=>$request->document_type,
                'doc_url'=>$docUrls,
                'doc_number'=>$request->document_no,
                'created_by'=>$request->created_by,
                'status'=>0,
            );
            $docInfo = DB::connection('sales_db')->table('document_info')
            ->where(array('guest_client_id'=>$request->client_id,'doc_type_id'=>$request->document_type))
            ->update($update_doc);
            $message = 'Document uploded and update successfully';
        }
        else
        {
            $insert_doc = array(
                'guest_client_id'=>$request->client_id,
                'doc_type_id'=>$request->document_type,
                'doc_url'=>$docUrls,
                'doc_number'=>$request->document_no,
                'created_by'=>$request->created_by,
            );
            $docInfo = DB::connection('sales_db')->table('document_info')->insertGetId($insert_doc);
            $message = 'Document uploded and insert successfully';
        }
        
        if($docInfo)
        {
            return response()->json(['status'=>200,'message'=>$message]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong']);
        }
    }

    public function uplode_company_document(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
          'document_type' => 'required',
          'document_no'=>'required',
          'document_file'=>'required',
          'client_id'=>'required',
          'comp_id'=>'required',
          'created_by'=>'required',
         ]);

        if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $docFiles = $request->file('document_file');
       
        if($docFiles)
        {
            $destinationPath = 'client_documents/'.$request->client_id;
            if(!File::exists($destinationPath))
            {
                File::makeDirectory($destinationPath, $mode = 0777, true, true);
            }
            $docUrl=array();
            $i=1;
            foreach($docFiles as $docFile)
            {
                $client_document = $i++.'_'.date('YmdHis')."." .$docFile->getClientOriginalExtension();
                $docFile->move($destinationPath, $client_document);
                $docUrl[] = url('/').'/client_documents/'.$request->client_id.'/'.$client_document;
            }
        }

        $docUrls=implode(',',$docUrl);
        

        $checkDoc = DB::connection('sales_db')->table('document_info')
        ->select('*')
        ->where(array('client_id'=>$request->client_id,'comp_id'=>$request->comp_id,'doc_type_id'=>$request->document_type))
        ->first();
        if($checkDoc)
        {
            $update_doc = array(
                'client_id'=>$request->client_id,
                'comp_id'=>$request->comp_id,
                'doc_type_id'=>$request->document_type,
                'doc_url'=>$docUrls,
                'doc_number'=>$request->document_no,
                'created_by'=>$request->created_by,
                'status'=>0,
            );
            $docInfo = DB::connection('sales_db')->table('document_info')
            ->where(array('client_id'=>$request->client_id,'doc_type_id'=>$request->document_type,'comp_id'=>$request->comp_id))
            ->update($update_doc);
            $message = 'Document uploded and update successfully';
        }
        else
        {
            $insert_doc = array(
                'client_id'=>$request->client_id,
                'comp_id'=>$request->comp_id,
                'doc_type_id'=>$request->document_type,
                'doc_url'=>$docUrls,
                'doc_number'=>$request->document_no,
                'created_by'=>$request->created_by,
            );
            $docInfo = DB::connection('sales_db')->table('document_info')->insertGetId($insert_doc);
            $message = 'Document uploded and insert successfully';
        }
        
        if($docInfo)
        {
            return response()->json(['status'=>200,'message'=>$message]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong']);
        }
    }

    public function get_client_uploded_document($client_id)
    {
        $docDetails = DB::connection('sales_db')->table('document_info')
        ->select('document_info.*','document_type.document_name')
        ->leftJoin('document_type','document_type.id','=','document_info.doc_type_id')
        ->where('document_info.guest_client_id',$client_id)
        ->get();
        return response()->json(['status'=>200,'message'=>'Client document details','data'=>$docDetails]);
    }

    public function get_company_uploded_document($client_id)
    {
        $docDetails = DB::connection('sales_db')->table('document_info')
        ->select('document_info.*','document_type.document_name')
        ->leftJoin('document_type','document_type.id','=','document_info.doc_type_id')
        ->leftJoin('sales_document','sales_document.id','=','document_info.doc_type_id')
        ->where(array('client_id'=>$client_id,'comp_id'=>$client_id))
        ->get();
        return response()->json(['status'=>200,'message'=>'Client document details','data'=>$docDetails]);
    }

    public function get_new_kyc_details()
    {
        $clientDetails = DB::connection('sales_db')->table('document_info')
        ->select('company_info.comp_id','company_info.client_name','company_info.business_name','company_info.status','company_info.created_at','company_info.exe_id','company_info.client_id','company_info.created_by')
        ->leftJoin('company_info','company_info.comp_id','=','document_info.comp_id')
        ->where(array('document_info.status'=>0))
        ->groupBy('document_info.comp_id')
        ->get();
        
        $clientDetails->map(function($item){
            $empName = DB::table('emp_basic_info')
            ->selectRaw("GROUP_CONCAT(emp_fname ,' ', emp_lame) as emp_name")
            ->where('emp_id',$item->exe_id)
            ->first();
            $item->emp_name = $empName->emp_name;
            return $item;    
        });

        $packageDetails = DB::connection('sales_db')->table('package_info as pi')
        ->select(
            'pi.package_id', 
            'pi.product_id', 
            'pi.service_id', 
            'pi.client_id', 
            'pi.comp_id', 
            'pi.package_name', 
            'pi.total_lead',
            'pi.sent_lead',
            'pi.package_type', 
            'pi.admin_status', 
            'pi.finance_status', 
            'pi.created_date', 
            'pi.created_by', 
            'pi.exe_id', 
            'pi.package_duration', 
            'pi.category_id', 
            'ci.business_name',
            'ci.organization_type as org_type',
            'pd.name as duration'
        )
        ->leftJoin('company_info as ci', 'ci.comp_id', '=', 'pi.comp_id')
        ->leftJoin('package_duration as pd', 'pd.id', '=', 'pi.package_duration')
        //->where('pi.client_id', $client_id)
        ->where(function ($query) {
            $query->where('pi.admin_status', 0)
                ->orWhere('pi.package_status', 0);
        })
        ->orderBy('package_id','DESC')
        ->limit(5)
        ->get();
        $packageDetails->map(function($item) {
            $empName = DB::table('emp_basic_info')
                ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")  // Corrected emp_lname
                ->where('emp_id', $item->exe_id)
                ->first();
            $item->emp_name = $empName->emp_name;
            return $item;
        });
    
        return response()->json(['status'=>200,'message'=>'Client document details','data'=>$clientDetails,'packageDetails'=>$packageDetails]);
    }

    // public function get_client_kyc_details($client_id)
    // {
    //     $clientDetails = DB::connection('sales_db')->table('document_info')
    //     ->select('document_info.*','document_type.document_name','ci.business_name','ci.client_name','ci.email','ci.mobile_no','ci.city','ci.address','ci.exe_id','ci.created_by as created_from')
    //     ->leftJoin('document_type','document_type.id','=','document_info.doc_type_id')
    //     ->leftJoin('company_info as ci','ci.comp_id','=','document_info.comp_id')
    //     ->leftJoin('sales_document','sales_document.id','=','document_info.doc_type_id')
    //     ->where(array('document_info.client_id'=>$client_id))
    //     ->orderBy('document_info.status')
    //     ->get();

    //     $clientDetails->map(function($item){
    //         $empName = DB::table('emp_basic_info')
    //         ->selectRaw("GROUP_CONCAT(emp_fname ,' ', emp_lame) as emp_name")
    //         ->where('emp_id',$item->exe_id)
    //         ->first();
    //         $item->emp_name = $empName->emp_name;
    //         return $item;    
    //     });

    //     $packageDetails = DB::connection('sales_db')->table('package_info as pi')
    //     ->select('pi.package_id', 'pi.client_id', 'pi.comp_id', 'pi.package_name', 'pi.total_lead', 'pi.sent_lead', 'pi.package_type', 'pi.admin_status', 'pi.finance_status', 'pi.created_date', 'pi.created_by', 'pi.exe_id', 'pi.package_duration', 'ci.business_name', 'pd.name as duration')
    //     ->leftJoin('company_info as ci', 'ci.comp_id', '=', 'pi.comp_id')
    //     ->leftJoin('package_duration as pd', 'pd.id', '=', 'pi.package_duration')
    //     ->where(function ($query) {
    //         $query->where(array('pi.client_id'=>$client_id))
    //             ->orWhere('pi.admin_status', 0) 
    //             ->orWhere('pi.package_status', 0); 
    //     })
    //     ->get();

    //     return response()->json(['status'=>200,'message'=>'Client document details','data'=>$clientDetails,'packageDetails'=>$packageDetails]);
    // }

    public function get_client_kyc_details($client_id)
    {
        $clientDetails = DB::connection('sales_db')->table('document_info')
            ->select(
                'document_info.*', 
                'document_type.document_name', 
                'ci.business_name', 
                'ci.client_name', 
                'ci.email', 
                'ci.mobile_no',
                
                'ci.city', 
                'ci.address', 
                'ci.exe_id', 
                'ci.created_by as created_from'
            )
            ->leftJoin('document_type', 'document_type.id', '=', 'document_info.doc_type_id')
            ->leftJoin('company_info as ci', 'ci.comp_id', '=', 'document_info.comp_id')
            ->where('document_info.client_id', $client_id)
            ->orderBy('document_info.status')
            ->get();

        $clientDetails->map(function($item) {
            $empName = DB::table('emp_basic_info')
                ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")  // Corrected emp_lname
                ->where('emp_id', $item->exe_id)
                ->first();
            $item->emp_name = $empName->emp_name;
            return $item;
        });

         $packageDetails = DB::connection('sales_db')->table('package_info as pi')
        ->select(
            'pi.package_id', 
            'pi.product_id', 
            'pi.service_id', 
            'pi.client_id', 
            'pi.comp_id', 
            'pi.package_name', 
            'pi.total_lead',
            'pi.sent_lead',
            'pi.package_type', 
            'pi.admin_status', 
            'pi.finance_status', 
            'pi.created_date', 
            'pi.created_by', 
            'pi.exe_id', 
            'pi.package_duration', 
            'pi.category_id', 
            'ci.business_name',
            'ci.organization_type as org_type',
            'pd.name as duration'
        )
        ->leftJoin('company_info as ci', 'ci.comp_id', '=', 'pi.comp_id')
        ->leftJoin('package_duration as pd', 'pd.id', '=', 'pi.package_duration')
        ->where('pi.client_id', $client_id)
        ->where(function ($query) {
            $query->where('pi.admin_status', 0)
                ->orWhere('pi.package_status', 0);
        })
        ->get();
        $packageDetails->map(function($item) {
            $empName = DB::table('emp_basic_info')
                ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")  // Corrected emp_lname
                ->where('emp_id', $item->exe_id)
                ->first();
            $item->emp_name = $empName->emp_name;
            return $item;
        });

        return response()->json([
            'status' => 200,
            'message' => 'Client document details',
            'data' => $clientDetails,
            'packageDetails' => $packageDetails
        ]);
    }



    public function approve_client_document(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'remarks' => 'required',
            'doc_id' => 'required',
            'status' => 'required',
            'created_by'=>'required',
            ]);

            if($validator->fails())
            {
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);
            }

            $updateDocument = array(
                'status'=>$request->status,
                'remark'=>$request->remarks,
                'verified_by'=>$request->created_by
            );

            $walletUpdate = DB::connection('sales_db')->table('document_info')
            ->where('id',$request->doc_id)
            ->update($updateDocument);

            return response()->json(['status'=>200,'message'=>'Document status update successfully','data'=>$updateDocument]);
    }

    

    public function get_company_compititor_details($package_id)
    {
        $packagesInfo = DB::connection('sales_db')->table('package_info')
        ->select('compititor_id')
        ->where('package_id',$package_id)
        ->first();
        $compititorInfo = explode(',',$packagesInfo->compititor_id);
        if($packagesInfo->compititor_id)
        {
            foreach($compititorInfo as $compiDetails)
            {
                $compititorInfo1 = DB::connection('sales_db')->table('company_info')
                ->select('business_name','mobile_no','email','client_name','comp_id')
                ->where('comp_id',$compiDetails)
                ->first();

                $compititorInfo2[] = array(
                    'business_name'=>$compititorInfo1->business_name,
                    'mobile_no'=>$compititorInfo1->mobile_no,
                    'client_name'=>$compititorInfo1->client_name,
                    'email'=>$compititorInfo1->email,
                    'comp_id'=>$compititorInfo1->comp_id,
                );
            }
        }
        else
        {
            $compititorInfo2=array();
        }
        return response()->json(['status'=>200,'message'=>'Package details','data'=>$compititorInfo2]);
    }

    public function get_client_package_details_by_id($package_id)
    {
        $packagesInfo = DB::connection('sales_db')->table('package_info')
        ->select('product.product_name','product_service.service_name','product_category.category_name','package_info.*')
        ->leftjoin('product','product.id','=','package_info.product_id')
        ->leftjoin('product_service','product_service.id','=','package_info.service_id')
        ->leftjoin('product_category','product_category.id','=','package_info.category_id')
        ->where('package_info.package_id',$package_id)
        ->get();

        foreach($packagesInfo as $package)
        {
            $groupDetails = explode(',',$package->group_id);
            $cityDetails = explode(',',$package->city_id);
            $localityDetails = explode(',',$package->locality_id);
        }

        $packageCategory_respone = $packagesInfo->map(function($item){
            $invoiceAndPaymentInfo = DB::connection('sales_db')->table('payment_history as ph')
            ->select('ph.payment_for','ph.invoice_name','ph.status as invoice_status','paid_amount','tax_amount','created_date','created_by','invoice_number')
            ->where('ph.package_id',$item->package_id)
            ->get();
            $item->invoiceInfo = $invoiceAndPaymentInfo;
            return $item;
        });

        $packageCategory_respone = $packagesInfo->map(function($item)
        {

            $groupListing = DB::connection('sales_db')->table('group_names')
                ->select("relation_id")
                ->where('group_id',$item->group_id)
                ->first();
            $groupsID = $item->group_id.','.$groupListing->relation_id;
            $groupArray = explode(',',$groupsID);
            
            $groupAndSubGroupInfo = DB::connection('sales_db')->table('group_names as gm')
            ->select('gm.group_id','gm.name','gm.tier')
            ->whereIn('gm.group_id',$groupArray)
            ->get();

            foreach($groupAndSubGroupInfo as $groupRow)
            {
                $localityListing = DB::connection('sales_db')->table('locality')
                ->select('locality_id','locality_name')
                ->where('state_id',$groupRow->group_id)
                ->get();

                $packageCategory_respone = $localityListing->map(function($item1){ 
                    $localityInfo = DB::connection('sales_db')->table('rsms_city')
                    ->select('locality_id','city_id','city_name')
                    ->where('locality_id',$item1->locality_id)
                    ->get();
                    $item1->localityInfo = $localityInfo;
                });

                $groupDataArray[] = array(
                    'group_id'=>$groupRow->group_id,
                    'group_name'=>$groupRow->name,
                    'city_info'=>$localityListing,
                );
            }
            $item->groupInfo = $groupDataArray;
            return $item;
        });
        return response()->json(['status'=>200,'message'=>'Package details','data'=>$packagesInfo,'groupDetails'=>$groupDetails,'cityDetails'=>$cityDetails,'localityDetails'=>$localityDetails]);
    }

    public function update_package_area(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'created_by' => 'required',
            'client_id' => 'required',
            'package_id' => 'required',
            'locality_id' => 'required',
            'city_id' => 'required',
            'group_id' => 'required',
            ]);

        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $changeAreaGenric = array(
            'client_id'=>$request->client_id,
            'package_id'=>$request->package_id,
            'change_type'=>1,// 1= area change
            'update_by'=>'Sales',
            'created_by'=>$request->created_by,
        );
        $insertData = DB::connection('sales_db')->table('sales_generic_change_detials')->insertGetId($changeAreaGenric);
        if($insertData)
        {
            $areaCount = DB::connection('sales_db')->table('package_info')->select('count_area_update')->where('package_id',$request->package_id)->first();
        
            $areaUpdate = array(
                'group_id'=>$request->group_id,
                'city_id'=>$request->city_id,
                'locality_id'=>$request->locality_id,
                'count_area_update'=>$areaCount->count_area_update + 1,
            );

            $packagesInfo = DB::connection('sales_db')->table('package_info')
            ->where(array('package_id'=>$request->package_id))
            ->update($areaUpdate);

            if($packagesInfo)
            {
                return 1;
            }
            else
            {
                return 2;
            }
        }
        else
        {
            return 2;
        }
    }

    public function package_active_deactive(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'created_by' => 'required',
            'client_id' => 'required',
            'package_id' => 'required',
            'package_start_date' => 'required',
            'package_stop_remarks' => 'required',
            'package_status' => 'required',
            ]);

        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }
        if($request->package_status==1)
        {
            $packageStatus = 0;
            

        }
        else
        {
            $packageStatus = 1;
           
        }


        $packageHistory = array(
            'client_id'=>$request->client_id,
            'package_id'=>$request->package_id,
            'package_status'=>$packageStatus,
            'status_type'=>1,// 	1=sales, 2=client, 3=admin, 4=system	
            'package_start_date'=>$request->package_start_date,
            'remarks'=>$request->package_stop_remarks,
            'created_by'=>$request->created_by,
        );
        $insertData = DB::connection('sales_db')->table('package_enable_disable_history')->insertGetId($packageHistory);

        if($insertData)
        {
            $statusUpdate = array(
                'package_status'=>$packageStatus,
            );

            $packagesInfo = DB::connection('sales_db')->table('package_info')
            ->where(array('package_id'=>$request->package_id))
            ->update($statusUpdate);

            if($packagesInfo)
            {
                return response()->json(['status'=>200,'message'=>'Package status updated successfully']);
            }
            else
            {
                return response()->json(['status'=>201,'message'=>'Something went wrong. please try again']);
            }
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. please try again']);
        }
    }

    public function get_package_enable_disable_history($pkgId)
    {
        $packagesHistory = DB::connection('sales_db')->table('package_enable_disable_history')
        ->select('*')
        ->where('package_id',$pkgId)
        ->get();

        return response()->json(['status'=>200,'message'=>'Package history details','data'=>$packagesHistory]);
    }

    public function get_compititor_details($mobileNo)
    {
        $compDetails = DB::connection('sales_db')->table('company_info')
        ->select('company_info.business_name','company_info.email','company_info.mobile_no','company_info.comp_id','package_info.package_id')
        ->leftJoin('package_info','package_info.comp_id','=','company_info.comp_id')
        ->where(array('company_info.mobile_no'=>$mobileNo,'package_info.package_status'=>1))
        ->first();

        if($compDetails)
        {
            return response()->json(['status'=>200,'message'=>'Company details','data'=>$compDetails]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'No record found for this number, or the package is currently not activated on this number.']);
        }
        
    }

    public function set_compititor($compititorId,$packagId,$compId,$pkgId)
    {
        $compititorDetails = DB::connection('sales_db')->table('package_info')
        ->select('compititor_id')
        ->where(array('comp_id'=>$compId,'package_id'=>$pkgId))
        ->first();

        $compititorIds = explode(',', $compititorDetails->compititor_id);
        $length = count($compititorIds);
        if($length >=2)
        {
            return response()->json(['status'=>201,'message'=>'This package already has '.$length.' competitors added, and you cannot add more than 2 competitors. To add a new competitor, you must first remove an existing one.']);

            exit;
        }
        else
        {

        

            if($compititorDetails->compititor_id)
            {
                $compititorIdUpdate = $compititorDetails->compititor_id.','.$compititorId;
            }
            else
            {
                $compititorIdUpdate = $compititorId;
            }
            
            $compititorUpdate = DB::connection('sales_db')->table('package_info')
            ->where(array('comp_id'=>$compId,'package_id'=>$pkgId))
            ->update(array('compititor_id'=>$compititorIdUpdate));
            if($compititorUpdate)
            {
                $compititorDetails1 = DB::connection('sales_db')->table('package_info')
                ->select('compititor_id')
                ->where(array('comp_id'=>$compititorId,'package_id'=>$packagId))
                //->where('comp_id',$compititorId)
                ->first();

                if($compititorDetails1->compititor_id)
                {
                    $compititorIdUpdate1 = $compititorDetails1->compititor_id.','.$compId;
                }
                else
                {
                    $compititorIdUpdate1 = $compId;
                }
            
                $compititorUpdate1 = DB::connection('sales_db')->table('package_info')
                ->where(array('comp_id'=>$compititorId,'package_id'=>$packagId))
                ->update(array('compititor_id'=>$compititorIdUpdate1));
                if($compititorUpdate1)
                {
                    return response()->json(['status'=>200,'message'=>'Compititor set successfully']);
                }
                else
                {
                    return response()->json(['status'=>201,'message'=>'Something went wrong. Please try agian..']);
                }
            }
            else
            {
                return response()->json(['status'=>201,'message'=>'Something went wrong. Please try agian.']);
            }
        }
    }


    public function remove_compititor($comp_id,$packageId,$compId)
    {
        $compititorDetails = DB::connection('sales_db')->table('package_info')
        ->select('compititor_id')
        ->where(array('package_id'=>$packageId))
        ->first();
        
        $newCompititor = self::removeValueFromCommaSeparatedString($compititorDetails->compititor_id, $comp_id);
        
        $compititorUpdate = DB::connection('sales_db')->table('package_info')
            ->where(array('package_id'=>$packageId))
            ->update(array('compititor_id'=>$newCompititor));
        
        if($compititorUpdate)
        {
            return response()->json(['status'=>200,'message'=>'Compititor remove successfully']);
            // $compititorDetails1 = DB::connection('sales_db')->table('package_info')
            // ->select('compititor_id')
            // ->where(array('package_id'=>$pkgid))
            // ->first();
            // $newCompititor1 = self::removeValueFromCommaSeparatedString($compititorDetails1->compititor_id, $compId);

            // $compititorUpdate1 = DB::connection('sales_db')->table('package_info')
            // ->where(array('package_id'=>$pkgid))
            // ->update(array('compititor_id'=>$newCompititor1));
            // if($compititorUpdate1)
            // {
            //     return response()->json(['status'=>200,'message'=>'Compititor remove successfully']);
            // }
            // else
            // {
            //     return response()->json(['status'=>201,'message'=>'Something  went wrong, Please try again.']);
            // }
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something  went wrong, Please try again.']);
        }

    }

    function removeValueFromCommaSeparatedString(string $string, $value)
    {
        $array = explode(',', $string);
        $array = array_map('trim', $array);
        $array = array_filter($array, function($item) use ($value) {
            return $item !== $value;
        });
        return implode(',', $array);
    }

    public function get_to_location_details($pkgId)
    {
         $packagesInfo = DB::connection('sales_db')->table('package_info')
        ->select('package_info.to_group','package_info.to_city')
        ->where('package_info.package_id',$pkgId)
        ->get();
        
        foreach($packagesInfo as $package)
        {
            $groupDetails = explode(',',$package->to_group);
            $cityDetails = explode(',',$package->to_city);
        }
        
        $packageCategory_respone = $packagesInfo->map(function($item)
        {
            $groupInfo = DB::connection('sales_db')->table('group_names as gm')
            ->select('gm.group_id','gm.name as group_name','gm.tier')
            ->where('gm.status',1)
            ->get();
            foreach($groupInfo as $groupRow)
            {
                $cityListing = DB::connection('sales_db')->table('locality')
                ->select('locality_id as city_id','locality_name as city_name')
                ->where('state_id',$groupRow->group_id)
                ->get();
                
                $groupDetails[] = array(
                    'group_id'=>$groupRow->group_id,
                    'group_name'=>$groupRow->group_name,
                    'city_details'=>$cityListing
                );
            }
            $item->groupInfo = $groupDetails;
            return $item;
        });
           
        
        return response()->json(['status'=>200,'message'=>'to location array','data'=>$packagesInfo,'groupDetails'=>$groupDetails,'cityDetails'=>$cityDetails]);   
    }

    public function update_package_to_location_area(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'created_by' => 'required',
            'client_id' => 'required',
            'package_id' => 'required',
            'to_city' => 'required',
            'to_group' => 'required',
            ]);

        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $changeAreaGenric = array(
            'client_id'=>$request->client_id,
            'package_id'=>$request->package_id,
            'change_type'=>2,// 2 = To Location area change
            'update_by'=>'Sales',
            'created_by'=>$request->created_by,
        );
        
        $insertData = DB::connection('sales_db')->table('sales_generic_change_detials')->insertGetId($changeAreaGenric);

        if($insertData)
        {
            $areaUpdate = array(
                'to_group'=>$request->to_group,
                'to_city'=>$request->to_city,
            );

            $packagesInfo = DB::connection('sales_db')->table('package_info')
            ->where(array('package_id'=>$request->package_id))
            ->update($areaUpdate);

            if($packagesInfo)
            {
                return 1;
            }
            else
            {
                return 2;
            }
        }
        else
        {
            return 2;
        }
    }

    public function get_today_followup_client_details(Request $request)
    {
        $today = date('Y-m-d');
        $today = date('Y-m-d');
        if($request->emp_id)
        {
            $empIds = self::get_executive_id($request->emp_id);
        }
        else
        {
            $empIds='';
        }



        $clientInfo = DB::connection('sales_db')->table('clients_followup_log')
        ->select('clients_followup_log.disposition','guest_user_details.id','guest_user_details.name','guest_user_details.mobile_no','guest_user_details.email_id','guest_user_details.business_name','product.product_name','product_service.service_name','product_category.category_name','group_names.name as group_name','clients_followup_log.client_type','followup_status.activity_name')

        ->leftJoin('guest_user_details','guest_user_details.id','=','clients_followup_log.client_id')
        ->leftJoin('group_names','group_names.group_id','=','guest_user_details.city')
        ->leftJoin('product','product.id','=','clients_followup_log.product_id')
        ->leftJoin('product_service','product_service.id','=','clients_followup_log.service_id')
        ->leftJoin('product_category','product_category.id','=','clients_followup_log.category_id')
        ->leftJoin('followup_status','followup_status.id','=','clients_followup_log.followup_id')

        ->when($empIds, fn ($query) => $query->whereIn('clients_followup_log.created_by',$empIds))
        ->when($request->mobileNo, fn ($query) => $query->where('guest_user_details.mobile_no',$request->mobileNo))
        ->when($request->selectedStatus, fn ($query) => $query->where('clients_followup_log.followup_id',$request->selectedStatus))

        ->where(array('clients_followup_log.status'=>1,'clients_followup_log.next_followup_date'=>$today))
        ->paginate($request->per_page);

        return response()->json([
            'status' => 200,
            'message' => 'Followup Details',
            'data' => $clientInfo,
            'last_page' => $clientInfo->lastPage()
        ]);
    }
    

    public function get_left_followup_client_details(Request $request)
    {
        $today = date('Y-m-d');
        if($request->emp_id)
        {
            $empIds = self::get_executive_id($request->emp_id);
        }
        else
        {
            $empIds='';
        }
        $clientInfo = DB::connection('sales_db')->table('clients_followup_log')
        ->select('clients_followup_log.disposition','guest_user_details.id','guest_user_details.name','guest_user_details.mobile_no','guest_user_details.email_id','guest_user_details.business_name','product.product_name','product_service.service_name','product_category.category_name','group_names.name as group_name','clients_followup_log.client_type')
        ->leftJoin('guest_user_details','guest_user_details.id','=','clients_followup_log.client_id')
        ->leftJoin('group_names','group_names.group_id','=','guest_user_details.city')
        ->leftJoin('product','product.id','=','clients_followup_log.product_id')
        ->leftJoin('product_service','product_service.id','=','clients_followup_log.service_id')
        ->leftJoin('product_category','product_category.id','=','clients_followup_log.category_id')
        
        ->when($empIds, fn ($query) => $query->whereIn('clients_followup_log.created_by',$empIds))
        ->when($request->mobileNo, fn ($query) => $query->where('guest_user_details.mobile_no',$request->mobileNo))
        ->when($request->selectedStatus, fn ($query) => $query->where('clients_followup_log.followup_id',$request->selectedStatus))

        ->where(array('clients_followup_log.status'=>0))
        ->where('clients_followup_log.next_followup_date', '<',$today)
        ->paginate($request->per_page);
        
        if($clientInfo)
        {

            return response()->json([
                'status' => 200,
                'message' => 'Followup Details',
                'data' => $clientInfo,
                'last_page' => $clientInfo->lastPage()
            ]);
          
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function get_active_client_details(Request $request)
    {
        //$emp_id='RIMS25';
        if($request->emp_id)
        {
            $empIds = self::get_executive_id($request->emp_id);
        }
        else
        {
            $empIds='';
        }
        $clientInfo = DB::connection('sales_db')->table('client_info')
        ->select('client_info.id','client_info.client_id','client_info.client_name','client_info.adhar_card','client_info.email','client_info.mobile_no','client_info.city','client_info.exe_id','client_info.status')
        ->where('client_info.status',2)
        //->whereIn('client_info.exe_id',$empIds)
        ->when($empIds, fn ($query) => $query->whereIn('client_info.exe_id',$empIds))
        ->when($request->mobileNo, fn ($query) => $query->where('client_info.mobile_no',$request->mobileNo))
        ->paginate($request->per_page);

        $clientInfo->map(function($item){
            $empName = DB::table('emp_basic_info')
            ->selectRaw("GROUP_CONCAT(emp_fname ,' ', emp_lame) as emp_name")
            ->where('emp_id',$item->exe_id)
            ->first();
            $item->emp_name = $empName->emp_name;
            return $item;    
        });

        if($clientInfo)
        {
           return response()->json(['status'=>200,'message'=>'Active Client Details','data'=>$clientInfo,'last_page'=>$clientInfo->lastPage()]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function get_inactive_client_details(Request $request)
    {
        if($request->emp_id)
        {
            $empIds = self::get_executive_id($request->emp_id);
        }
        else
        {
            $empIds='';
        }
        $clientInfo = DB::connection('sales_db')->table('client_info')
        ->select('client_info.id','client_info.client_id','client_info.client_name','client_info.adhar_card','client_info.email','client_info.mobile_no','client_info.city','client_info.exe_id','client_info.status')
        ->where('client_info.status',3)
        ->when($empIds, fn ($query) => $query->whereIn('client_info.exe_id',$empIds))
        ->when($request->mobileNo, fn ($query) => $query->where('client_info.mobile_no',$request->mobileNo))
        //->whereIn('client_info.exe_id',$empIds)
        ->paginate($request->per_page);

        $clientInfo->map(function($item){
            $empName = DB::table('emp_basic_info')
            ->selectRaw("GROUP_CONCAT(emp_fname ,' ', emp_lame) as emp_name")
            ->where('emp_id',$item->exe_id)
            ->first();
            $item->emp_name = $empName->emp_name;
            return $item;    
        });

        if($clientInfo)
        {
           return response()->json(['status'=>200,'message'=>'Inactive Client Details','data'=>$clientInfo,'last_page'=>$clientInfo->lastPage()]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function get_expire_client_details(Request $request)
    {
        if($request->emp_id)
        {
            $empIds = self::get_executive_id($request->emp_id);
        }
        else
        {
            $empIds='';
        }

        $clientInfo = DB::connection('sales_db')->table('client_info')
        ->select('client_info.id','client_info.client_id','client_info.client_name','client_info.adhar_card','client_info.email','client_info.mobile_no','client_info.city','client_info.exe_id','client_info.status')
        ->when($empIds, fn ($query) => $query->whereIn('client_info.exe_id',$empIds))
        ->when($request->mobileNo, fn ($query) => $query->where('client_info.mobile_no',$request->mobileNo))
        //->where('client_info.status',4)
        ->paginate($request->per_page);

        $clientInfo->map(function($item)
        {
            $empName = DB::table('emp_basic_info')
            ->selectRaw("GROUP_CONCAT(emp_fname ,' ', emp_lame) as emp_name")
            ->where('emp_id',$item->exe_id)
            ->first();
            $item->emp_name = $empName->emp_name;
            return $item;    
        });

        if($clientInfo)
        {
           return response()->json(['status'=>200,'message'=>'Expire Client Details','data'=>$clientInfo,'last_page'=>$clientInfo->lastPage()]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function get_client_details(Request $request)
    {
        if($request->emp_id)
        {
            $empIds = self::get_executive_id($request->emp_id);
        }
        else
        {
            $empIds='';
        }
        $clientInfo = DB::connection('sales_db')->table('guest_user_details')
        ->select('guest_user_details.id','guest_user_details.name','guest_user_details.mobile_no','guest_user_details.email_id','guest_user_details.business_name','product.product_name','product_service.service_name','product_category.category_name','group_names.name as group_name')
        
        ->leftJoin('group_names','group_names.group_id','=','guest_user_details.city')
        ->leftJoin('product','product.id','=','guest_user_details.product_id')
        ->leftJoin('product_service','product_service.id','=','guest_user_details.service_id')
        ->leftJoin('product_category','product_category.id','=','guest_user_details.category')

        ->when($empIds, fn ($query) => $query->whereIn('guest_user_details.created_by',$empIds))
        ->when($request->mobileNo, fn ($query) => $query->where('guest_user_details.mobile_no',$request->mobileNo))
        
        ->where(array('guest_user_details.status'=>0,'guest_user_details.created_by'=>$request->emp_id))
        ->orderBy('guest_user_details.id','DESC')
        ->paginate($request->per_page);
       
        if($clientInfo)
        {
            return response()->json(['status'=>200,'message'=>'Client Details','data'=>$clientInfo,'last_page'=>$clientInfo->lastPage()]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function get_verified_client_details(Request $request)
    {   
        if($request->emp_id)
        {
            $empIds = self::get_executive_id($request->emp_id);
        }
        else
        {
            $empIds='';
        }
        
        $clientInfo = DB::connection('sales_db')->table('client_info')
        ->select('client_info.id','client_info.client_id','client_info.client_name','client_info.adhar_card','client_info.email','client_info.mobile_no','client_info.city','client_info.exe_id','client_info.status')
      
        ->when($empIds, fn ($query) => $query->whereIn('client_info.exe_id',$empIds))
        ->when($request->mobileNo, fn ($query) => $query->where('client_info.mobile_no',$request->mobileNo))
        ->whereIn('client_info.status',array(0,1))
        ->paginate($request->per_page);

        $clientInfo->map(function($item){
            $empName = DB::table('emp_basic_info')
            ->selectRaw("GROUP_CONCAT(emp_fname ,' ', emp_lame) as emp_name")
            ->where('emp_id',$item->exe_id)
            ->first();
            $item->emp_name = $empName->emp_name;
            return $item;    
        });

        if($clientInfo)
        {
           return response()->json(['status'=>200,'message'=>'Verified Client Details','data'=>$clientInfo,'last_page'=>$clientInfo->lastPage()]);
        }
        else
        {
            return response()->json(['status'=>201,'message'=>'Something went wrong. Please try again']);
        }
    }

    public function get_executive_details($empId)
    {
        $emp_managers = DB::table('employee_managers')
        ->select('employee_managers.emp_id','emp_basic_info.emp_fname','emp_basic_info.emp_lame as emp_lname')
        ->leftJoin('emp_basic_info','emp_basic_info.emp_id','=','employee_managers.emp_id')
        ->whereRaw("FIND_IN_SET(?, reporting_to)", [$empId])
        ->orWhere("employee_managers.emp_id",'=',$empId)  
        ->where('status', 1)
        ->groupBy('employee_managers.emp_id')
        ->get();
        return response()->json(['status'=>200,'message'=>'Executive Details','data'=>$emp_managers]);
    }

    public function get_executive_id($empId)
    {
        $emp_ids= DB::table('employee_managers')
        ->whereRaw("FIND_IN_SET(?, reporting_to)", [$empId]) 
        ->where('status', 1)
        ->pluck('emp_id')
        ->toArray();
        $emp_ids[] = $empId;
        return $emp_ids;
    }

    public function get_package_category(Request $request)
    {
        $productId = $request->product_id;
        $categoryInfo = DB::connection('sales_db')->table('package_category')
        ->select('id','product_id','name')
        ->where('product_id',$productId)
        ->get();
        return response()->json(['status'=>200,'message'=>'package Category Details','data'=>$categoryInfo]);
    }

    // public function get_pre_package_by_category(Request $request)
    // {
    //     $productId = $request->product_id;
    //     $categoryId = $request->category_id;
    //     $group = $request->group;
    //     $service = $request->service;
       
    //     $prePackages = DB::connection('sales_db')->table('pre_package')
    //     ->select(
    //         'pre_package.id',
    //         'pre_package.package_name',
    //         'pre_package.package_status as status',
    //         'pre_package.to_location',
    //         'pre_package.total_lead',
    //         'pre_package.created_by',
    //         'pre_package.product_id',
    //         'product.product_name',
    //         'group_names.name as group_name',
    //         'package_duration.name as duration_name',
    //         'package_type.name as package_type_name',
    //         'package_category.name as category_name',
    //         'package_category.id as category_id',
    //         'pre_package.is_partial_payment',
    //         'pre_package.total_amount as package_price',
    //         'pre_package.city_id',
    //         'pre_package.group_id',
    //         'pre_package.tax_amount',
    //         'pre_package.service_charges'
    //     )
    //     ->selectRaw("GROUP_CONCAT(DISTINCT(pc.category_name)) as category_name_list")
    //     ->selectRaw("GROUP_CONCAT(DISTINCT(locality.locality_name)) as city_name")
    //     ->selectRaw("COUNT(offers_details.id) as active_offers")

    //     ->leftJoin('product', 'product.id', '=', 'pre_package.product_id')
    //     ->leftJoin('group_names', 'group_names.group_id', '=', 'pre_package.group_id')
    //     ->leftJoin('package_duration', 'package_duration.id', '=', 'pre_package.package_duration')
    //     ->leftJoin('package_type', 'package_type.id', '=', 'pre_package.package_type')
    //     ->leftJoin('package_category', 'package_category.id', '=', 'pre_package.package_category')

    //     ->leftJoin('product_category as pc', function ($join) {
    //         $join->whereRaw("FIND_IN_SET(pc.id, pre_package.category_id)");
    //     })
    //     ->leftJoin('locality', function ($join) {
    //         $join->whereRaw("FIND_IN_SET(locality.locality_id, pre_package.city_id)");
    //     })
    //     ->leftJoin('offers_details', function ($join) {
    //         $join->on(DB::raw('FIND_IN_SET(pre_package.id, offers_details.pre_package_id)'), '>', DB::raw('0'))
    //             ->where('offers_details.status', 1);
    //     })

    //     ->when($categoryId, fn ($query) => $query->where('pre_package.category_id', $categoryId))
    //     ->when($productId, fn ($query) => $query->where('pre_package.product_id', $productId))
    //     ->when($group, fn ($query) => $query->whereIn('pre_package.group_id', [$group]))
    //     ->when($service, fn ($query) => $query->where('pre_package.service_id', $service))
    //     ->where('pre_package.package_status', 1)
    //     ->groupBy('pre_package.id')
    //     ->orderBy('pre_package.id', 'DESC')
    //     ->get();
    //     //category_name
    //     $packageCategory_respone = $prePackages->map(function($item){
    //         $packageCategoryType = DB::connection('sales_db')->table('package_category_type_assign as pcta')
    //         ->select('pct.name','pct.amount','pct.tax_amount','pct.total_amount','pcta.type_status','pcta.id')
    //         ->leftJoin('package_category_type as pct','pct.id','=','pcta.type_id')
    //         ->where(array('pcta.status'=>1,'pcta.product_id'=>$item->product_id,'pcta.cat_id'=>$item->category_id))
    //         ->get();

    //         $item->assign_cat = $packageCategoryType;
    //         return $item;
    //     });
    //     return response()->json(['status'=>200,'message'=>'Pre Packages details','data'=>$prePackages]);
    // }

    public function get_pre_package_by_category(Request $request)
    {
        $productId = $request->product_id;
        $categoryId = $request->category_id;
        $group = $request->group;
        $service = $request->service;

        $prePackages = DB::connection('sales_db')->table('pre_package')
            ->select(
                'pre_package.id',
                'pre_package.package_name',
                'pre_package.package_status as status',
                'pre_package.to_location',
                'pre_package.total_lead',
                'pre_package.created_by',
                'pre_package.product_id',
                'product.product_name',
                'group_names.name as group_name',
                'package_duration.name as duration_name',
                'package_type.name as package_type_name',
                'package_category.name as category_name',
                'package_category.id as category_id',
                'pre_package.is_partial_payment',
                'pre_package.total_amount as package_price',
                'pre_package.city_id',
                'pre_package.group_id',
                'pre_package.tax_amount',
                'pre_package.service_charges'
            )
            ->addSelect(DB::raw("(
                SELECT GROUP_CONCAT(DISTINCT(pc.category_name)) 
                FROM product_category pc
                WHERE FIND_IN_SET(pc.id, pre_package.category_id)
            ) as category_name_list"))
            ->addSelect(DB::raw("(
                SELECT GROUP_CONCAT(DISTINCT(locality.locality_name)) 
                FROM locality 
                WHERE FIND_IN_SET(locality.locality_id, pre_package.city_id)
            ) as city_name"))
            ->addSelect(DB::raw("(
                SELECT COUNT(offers_details.id) 
                FROM offers_details 
                WHERE FIND_IN_SET(pre_package.id, offers_details.pre_package_id) 
                AND offers_details.status = 1
            ) as active_offers"))
            
            ->leftJoin('product', 'product.id', '=', 'pre_package.product_id')
            //->leftJoin('group_names', 'group_names.group_id', '=', 'pre_package.group_id')
            ->leftJoin('group_names', function ($join) {
                $join->whereRaw("FIND_IN_SET(group_names.group_id, pre_package.group_id)");
            })
            ->leftJoin('package_duration', 'package_duration.id', '=', 'pre_package.package_duration')
            ->leftJoin('package_type', 'package_type.id', '=', 'pre_package.package_type')
            ->leftJoin('package_category', 'package_category.id', '=', 'pre_package.package_category')

            ->when($categoryId, fn ($query) => $query->where('pre_package.category_id', $categoryId))
            ->when($productId, fn ($query) => $query->where('pre_package.product_id', $productId))
            //->when($group, fn ($query) => $query->whereIn('pre_package.group_id', [$group]))
            ->when($group, fn($query) => $query->whereRaw("FIND_IN_SET(?, pre_package.group_id)",[$group]))
            ->when($service, fn ($query) => $query->where('pre_package.service_id', $service))
            ->where('pre_package.package_status', 1)
            ->groupBy('pre_package.id')
            ->orderBy('pre_package.id', 'DESC')
            ->get();

        // Map and attach category details using eager loading or subqueries for better performance
        $packageCategory_respone = $prePackages->map(function ($item) {
            $packageCategoryType = DB::connection('sales_db')->table('package_category_type_assign as pcta')
                ->select('pct.name', 'pct.amount', 'pct.tax_amount', 'pct.total_amount', 'pcta.type_status', 'pcta.id')
                ->leftJoin('package_category_type as pct', 'pct.id', '=', 'pcta.type_id')
                ->where('pcta.status', 1)
                ->where('pcta.product_id', $item->product_id)
                ->where('pcta.cat_id', $item->category_id)
                ->get();

            $item->assign_cat = $packageCategoryType;
            return $item;
        });

        return response()->json(['status' => 200, 'message' => 'Pre Packages details', 'data' => $prePackages]);
    }

    public function get_package_leads_details($pkgId)
    {
        $leadDetails = DB::connection('sales_db')->table('leads_info')
        ->select('leads_info.id','leads_info.enq_id','leads_info.client_id','leads_info.package_id','leads_info.sent_date','leads_info.status','enquiry_info.city_from','enquiry_info.city_to','enquiry_info.distance','enquiry_info.customer_id','customer_info.name','customer_info.primary_no','customer_info.email')
        ->leftJoin('enquiry_info','enquiry_info.id','=','leads_info.enq_id')
        ->leftJoin('customer_info','customer_info.id','=','enquiry_info.customer_id')
        ->where('package_id',$pkgId)
        ->get();
        return response()->json(['status'=>200,'message'=>'Lead details','data'=>$leadDetails]);
    }



    public function get_client_followup_status($followupStatus)
    {
        $followupDetails = DB::connection('sales_db')->table('followup_status')
        ->select('activity_name','id')
        ->where('dispostion',$followupStatus)
        ->get();
        return response()->json(['status'=>200,'message'=>'Followup Details','data'=>$followupDetails]);
    }

    public function sent_purposal_to_client(Request $request)
    {
        $pdfPath = public_path().'/purposal/logisticmart_proposal.pdf';
        if($request->user_type == 'guest')
        {
            $clientInfo = DB::connection('sales_db')->table('guest_user_details')
            ->select('name','mobile_no', 'email_id', 'city', 'state')
            ->where('id', $request->client_id)
            ->first();
        }
        else
        {
            $clientInfo = DB::connection('sales_db')->table('client_info')
            ->select('client_name as name','mobile_no','email as email_id','city','state')
            ->where('client_id', $request->client_id)
            ->first();
        }

     

        $prePackages = DB::connection('sales_db')->table('pre_package')
            ->select(
                'pre_package.id',
                'pre_package.package_name',
                'pre_package.package_status as status',
                'pre_package.to_location',
                'pre_package.total_lead',
                'pre_package.created_by',
                'pre_package.product_id',
                'pre_package.service_id',
                'product.product_name',
                'group_names.name as group_name',
                'package_duration.name as duration_name',
                'package_type.name as package_type_name',
                'package_category.name as category_name',
                'package_category.id as category_id',
                'pre_package.is_partial_payment',
                'pre_package.total_amount as package_price',
                'pre_package.city_id',
               
            )
            ->selectRaw("GROUP_CONCAT(DISTINCT(pc.category_name)) as category_name_list")
            ->selectRaw("GROUP_CONCAT(DISTINCT(locality.locality_name)) as city_name")
            ->leftJoin('product', 'product.id', '=', 'pre_package.product_id')
            ->leftJoin('group_names', 'group_names.group_id', '=', 'pre_package.group_id')
            ->leftJoin('package_duration', 'package_duration.id', '=', 'pre_package.package_duration')
            ->leftJoin('package_type', 'package_type.id', '=', 'pre_package.package_type')
            ->leftJoin('package_category', 'package_category.id', '=', 'pre_package.package_category')
            ->leftJoin('product_category as pc', function ($join) {
                $join->whereRaw("FIND_IN_SET(pc.id, pre_package.category_id)");
            })
            ->leftJoin('locality', function ($join) {
                $join->whereRaw("FIND_IN_SET(locality.locality_id, pre_package.city_id)");
            })
            ->where('pre_package.id', $request->id)
            ->groupBy('pre_package.id')
            ->orderBy('pre_package.id', 'DESC')
            ->get();

        // Fetch and assign package category types
        $prePackages->map(function ($item) {
            $packageCategoryType = DB::connection('sales_db')->table('package_category_type_assign as pcta')
                ->select('pct.name', 'pct.amount', 'pct.tax_amount', 'pct.total_amount', 'pcta.type_status', 'pcta.id')
                ->leftJoin('package_category_type as pct', 'pct.id', '=', 'pcta.type_id')
                ->where([
                    'pcta.status' => 1,
                    'pcta.product_id' => $item->product_id,
                    'pcta.cat_id' => $item->category_id
                ])
                ->get();

            $item->assign_cat = $packageCategoryType;
            return $item;
        });

        $discountType = $request->discountType;
        $discountPercent = $request->discountPercent;
        $regAmount = $request->regAmount;
        $totalLead = $request->total_lead;
        $extraLead = round($totalLead * $discountPercent / 100); 
        $packagePrice=$request->package_price;
        
        $paybale_price=$request->paybale_price;
        $offerbleAmount = $paybale_price - $regAmount;
        $offerMassage = '';
        //package_id,package_price,paybale_price,discountType,discountPercent,regAmount
        if($discountType ==1)
        {
            $offerMassage = "You will save ₹ ".$packagePrice - $offerbleAmount." on this package.";
        }
        else if($discountType == 2)
        {
            $offerMassage = "You will get an extra ".$extraLead." leads with this package..";
        }
        else
        {
            $offerMassage = '';
        }
       
         //
         if($request->user_type== 'guest')
         {
             $clientType = 0;
             $guestId=$request->client_id;
             $clientId=0;
         }
         else
         {
             $clientType = 1;
             $guestId=0;
             $clientId=$request->client_id;
         }

         $packageDetails = $prePackages->first();
        
        $offerCategory = $request->input('offer_category.'.$request->offer_id); 
        $offerRate = $request->input('offer_rate.'.$request->offer_id); 

         $insertProposalData = array(
             'client_type'=>$clientType,
             'company_type'=>$request->companyType,
             'pre_package_id'=>$packageDetails->id,
             'sent_to'=>$clientInfo->email_id,
             'client_id'=>$clientId,
             'comp_id'=>$request->comp_id,
             'service_id'=>$packageDetails->service_id,
             'guest_client_id'=>$guestId,
             'discount_type'=>$discountType == '' ? 0 : $discountType,
             'offer_id'=>$request->offer_id,
             'offer_category'=> $offerCategory,
             'offer_rate'=>$offerRate,
             'package_amount'=>$packagePrice,
             'reg_amount'=>round($regAmount / 1.18),
             'payable_amount'=>$paybale_price,
             'is_partial_payment'=>$packageDetails->is_partial_payment,
             'discount_price'=>$packagePrice - $offerbleAmount,
             'extra_leads'=>$extraLead,
             'status'=>1,
             'sent_by'=>$request->emp_id,
             'valid_from'=>date('Y-m-d'),
             'valid_to'=>Carbon::now()->addDays(7)->format('Y-m-d'),
         );
 
         $proposalId = DB::connection('sales_db')->table('proposal_info')->insertGetId($insertProposalData);
        
        
        
        $emailBody = $this->prepareEmailBody($clientInfo, $prePackages, $offerMassage,$discountPercent,$discountType,$paybale_price,$extraLead,$proposalId);
        
        $emailData = [
            'email_id' => '2',
            'email_to' => 'deepak.singh@r-ims.com',
            'subject' => "LogisticMart Proposal",
            'body' => $emailBody,
            'attach' => [ 
                'path' => $pdfPath,
                'as' => 'logisticmart_proposal.pdf',
                'mime' => 'application/pdf',
            ],
        ];

        // Send the email
        $myRequest = new Request();
        $myRequest->replace($emailData);
        $returnData = CommunicationApis::send_email($myRequest);

        if($returnData)
        {
            return response()->json(['status' => 200, 'message' => 'Proposal sent successfully','data'=>$returnData]);
        }
        else
        {
            return response()->json(['status' => 201, 'message' => 'Sometnig went wrong . Please try again']);
        }
       
    }

    
    private function prepareEmailBody($clientInfo, $prePackages, $offerMassage,$discountPercent,$discountType,$paybale_price,$extraLead,$proposalId)
    {
        $package = $prePackages->first(); 
        $assignCategories = $package->assign_cat ?? [];
        $packageId = $package->id;
        
        
        $categoryRows = '';
        foreach ($assignCategories as $cat) {
            $categoryRows .= "
            <tr>
            <td style='text-align:start;'>{$cat->name}</td>
            <td style='text-align:right;'>".($cat->type_status == 1 ? '✅' : '❌')."</td>
            </tr>";
        }


        
        return '<!DOCTYPE html>
            <html lang="en">

            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Perposal Packages</title>
            </head>

            <body style="margin: 0;overflow-y: scroll; overflow-x: hidden;">
                <style>
                    @media screen and (min-width: 480px) {
                        
                    }
                </style>
                <div style="max-width: 660px;width: 95%; margin: 0 auto;">
                    <!-- logos  -->
                    <div style="background-color: #F7F7F7;display: table; text-align: center; width: 100%;">
                        <div style="width: 33.33%;display: inline-block;">
                            <div><img style="padding: 10px;max-width: calc(100% - 20px);max-height:50px;"
                                    src="https://biz2bizmart.com/zoopgotheams/MyEmailers/zoopgo/Images/logo1.png" alt=""></div>
                            <div><img style="padding: 10px;max-width: calc(100% - 20px);max-height:50px;"
                                    src="https://biz2bizmart.com/zoopgotheams/MyEmailers/zoopgo/Images/logo6.png" alt=""></div>
                        </div>
                        <div style="width: 33.33%; display: inline-block; position: relative; bottom: 30px;">
                            <img style="padding: 10px;max-width: calc(100% - 20px);max-height:50px;"
                                src="https://biz2bizmart.com/zoopgotheams/MyEmailers/zoopgo/Images/rims-logo.png" alt="">
                        </div>
                        <div style="width: 33.33%;display: inline-block;">
                            <div> <img style="padding: 10px;max-width: calc(100% - 20px);max-height:50px;"
                                    src="https://biz2bizmart.com/zoopgotheams/MyEmailers/zoopgo/Images/logo4.png" alt="">
                            </div>
                            <div> <img style="padding: 10px;max-width: calc(100% - 20px);max-height:50px;"
                                    src="https://biz2bizmart.com/zoopgotheams/MyEmailers/zoopgo/Images/logo2.png" alt="">
                            </div>
                        </div>
                    </div>
                    <!-- logos  -->

                    <!-- banner  -->
                    <div
                        style=" background-image: url(https://biz2bizmart.com/zoopgotheams/MyEmailers/zoopgo/Images/banner.jpg); background-position: center; background-repeat: no-repeat; background-size: cover;">
                        <h1 style="text-transform: capitalize; color: white; padding: 90px 10px; text-align: center; margin: 0;">
                            Perposal For Business Partnership</h1>
                    </div>
                    <!-- banner  -->

                    <!-- content  -->
                    <div style="padding: 10px;">
                        <h2 style="color: #02418a;">INTRODUCTION</h2>
                        <p>LogisticMart is a leading aggregator, powered by RIMS Bizzserve Pvt Ltd since its inception in the year 2018.</p>
                        <p>With a strong focus on simplifying logistical operations, it ensures clients an exclusive platform where they meet all their transportation, or logistics needs. Specializing in a range of services, including Packers and Movers, Transportation, Cargos, Warehouse, Bike Ride, Package Delivery, and more.</p>
                        <p>Beyond LogisticMart, RIMS Bizzserve has also a pan India presence with other services through ZoopGo, MMC Garage, and MoveMyCar as its innovative products.</p>
                        <p>Backed by an extensive network of verified service providers, LogisticMart continues to be a trusted platform for logistics solutions.</p>
                    </div>
                    <!-- content  -->

                    <!-- cards  -->
                    <div style="background-color: #f5f5f5; padding: 20px; margin: 20px 0 0 0;">
                        <h2 style="text-align: center; width: 100%; margin: 0; color: #1d5fa3; text-transform: capitalize;">
                            Lead Packages & Plans:</h2>
                        <p style="text-align: center; margin-top: 0;">Our plans are designed on the vendor’s needs. Choose the plan that best works for you!</p>

                        <div style="display: block;  width: 100%; text-align: center; width: 100%;">

                            <div style="width: 90%;max-width:360px;  padding: 6px;vertical-align: top;display: inline-block; margin: 10px 0;" valign="top">
                                <div style="padding: 10px; border-radius: 6px; background-color: white; ">
                                    <div style="background-color: rgb(29, 95, 163); color: white; padding: 10px; font-weight: 600; text-align: center;">
                             ' . htmlspecialchars($package->package_name) . '
                         </div>

                        
                         <div style="font-weight: 600; font-size: 15px; margin-top:7px; text-align: center;">
                             ' . htmlspecialchars(str_replace(',', ' + ', $package->category_name_list)) . '
                         </div>
                         <h5>' . htmlspecialchars($package->duration_name) . ' (' . htmlspecialchars($package->package_type_name) . ' ' . $package->total_lead . ' Lead)</h5>
                         <table style="width: 100%;">
                             ' . $categoryRows . '
                         </table>
                         <h6 style="text-align:center;margin:0 ;margin-bottom:5px;"> Is Partial Payment: ' . ($package->is_partial_payment == 1 ? 'Yes' : 'No') . '</h6>
                        <h5 style="text-align:center;margin:0 ;margin-bottom:5px;" > Package Price: ₹' . htmlspecialchars($package->package_price) . '</h5>


                         '. ($discountPercent ? '
                         <h5 style="text-align:center;margin:0 ;margin-bottom:5px;">Discount Offer: ' . htmlspecialchars($discountPercent) . ' %</h5>

                          '. ($discountType == 1 ? '
                         <h5 style="text-align:center;margin:0 ;margin-bottom:5px;">After Discount: ₹' . htmlspecialchars($paybale_price) . '</h5>
                         ' : '
                             <h5 style="text-align:centermargin:0 ;margin-bottom:5px;Package Leads: ' . htmlspecialchars($package->total_lead).'</h5>
                             <h5 style="text-align:center;margin:0 ;margin-bottom:5px;">Extra Leads: ' . htmlspecialchars($extraLead) . '</h5>
                             <h5 style="text-align:center;margin:0 ;margin-bottom:5px;">Total Leads: ' . htmlspecialchars($extraLead + $package->total_lead). '</h5>
                         ') . '

                         <h5 style="text-align:center; color:red;margin:0 ;margin-bottom:5px;">' . htmlspecialchars($offerMassage) . '</h5>
                         ' : '') . '
                            <div style=" margin-bottom: 10px; text-align: center;">
                                <div style=" margin-bottom: 10px; text-align: center;">
                                    <a style="text-decoration: none; display: inline-block; background-color: #02418a; padding: 8px 16px; color: white; border-radius: 4px;"
                                    href="https://apiworkforce.r-ims.com/app/'.$proposalId.'">Buy Now</a>
                                </div>
                            </div>
                     </div>
                        

                                </div>
                            </div>
                           
                        </div>
                    </div>
                    <!-- cards  -->

                    <!-- benifits  -->
                    <div style="padding: 20px;">
                        <h2 style="text-align: center; width: 100%; margin-top: 0;  color: #1d5fa3; text-transform: capitalize;">
                            benefits of
                            partnership</h2>
                        <div
                            style="display: inline-table; margin: 10px 0; padding: 10px ; border-radius: 10px;  border: .25px solid #1ab64f;">
                            <p
                                style="width: 10%;height: 100%; display: table-cell; margin: 0;background-image: url(https://biz2bizmart.com/zoopgotheams/MyEmailers/zoopgo/Images/1.png);background-position: center; background-repeat: no-repeat; background-size: contain;">
                            </p>
                            <p style="width: 90%;font-size:14px ;display: inherit;margin: 0;padding: 10px;">
                                <span style="font-weight:bold;color:#1ab64f">Best quality of leads at affordable price : </span>
                                At LogisticMart, service professionals receive genuine and verified leads of customers looking service in your selected area.
                            </p>
                        </div>
                        <div
                            style="display: inline-table; margin: 10px 0; padding: 10px ; border-radius: 10px; border: .25px solid #1ab64f;">
                            <p
                                style="width: 10%;height: 100%; display: table-cell; margin: 0;background-image: url(https://biz2bizmart.com/zoopgotheams/MyEmailers/zoopgo/Images/2.png);background-position: center; background-repeat: no-repeat; background-size: contain;">
                            </p>
                            <p style="width: 90%;font-size:14px ;display: inherit;margin: 0;padding: 10px;">
                                <span style="font-weight:bold;color:#1ab64f">Leads served in real time and at same time : </span>
                               Leads deliver to limited professionals as soon as customer raise a request through our mobile app, website, dashboard, and message and email. Every professionals get the lead at same time.
                            </p>
                        </div>
                        <div
                            style="display: inline-table; margin: 10px 0; padding: 10px ; border-radius: 10px; border: .25px solid #1ab64f; ">
                            <p
                                style="width: 10%;height: 100%; display: table-cell; margin: 0;background-image: url(https://biz2bizmart.com/zoopgotheams/MyEmailers/zoopgo/Images/3.png);background-position: center; background-repeat: no-repeat; background-size: contain;">
                            </p>
                            <p style="width: 90%;font-size:14px ;display: inherit;margin: 0;padding: 10px;">
                                <span style="font-weight:bold;color:#1ab64f">Best conversion guaranteed : </span>
                                We at LogisticMart keep the competition to minimum numbers so that every service provider has an opportunity to talk to the customers, understand the requirement and provide their estimates. One lead does not share to more than 4 service professionals.
                            </p>
                        </div>
                        <div
                            style="display: inline-table; margin: 10px 0; padding: 10px ; border-radius: 10px; border: .25px solid #1ab64f;">
                            <p
                                style="width: 10%;height: 100%; display: table-cell; margin: 0;background-image: url(https://biz2bizmart.com/zoopgotheams/MyEmailers/zoopgo/Images/4.png);background-position: center; background-repeat: no-repeat; background-size: contain;">
                            </p>
                            <p style="width: 90%;font-size:14px ;display: inherit;margin: 0;padding: 10px;">
                                <span style="font-weight:bold;color:#1ab64f">Friendly customer support : </span>
                                We love to hear from our clients, our customer support team is clients friendly and happy to assist and help you in case of any query
                            </p>
                        </div>
                        <div
                            style="display: inline-table; margin: 10px 0; padding: 10px ; border-radius: 10px; border: .25px solid #1ab64f;">
                            <p
                                style="width: 10%;height: 100%; display: table-cell; margin: 0;background-image: url(https://biz2bizmart.com/zoopgotheams/MyEmailers/zoopgo/Images/5.png);background-position: center; background-repeat: no-repeat; background-size: contain;">
                            </p>
                            <p style="width: 90%;font-size:14px ;display: inherit;margin: 0;padding: 10px;">
                                <span style="font-weight:bold;color:#1ab64f">Easy to use our mobile app : </span>
                            We have an easy to use mobile app, through which you can manage all your customers
                            </p>
                        </div>



                    </div>
                    <!-- benifits  -->

                    <!-- footer  -->
                    <div
                        style="  padding: 50px; text-align: center; background-image: url(https://biz2bizmart.com/zoopgotheams/MyEmailers/zoopgo/Images/footer-bg.png);background-position: center; background-repeat: no-repeat; background-size: cover;">

                    </div>
                    <!-- footer  -->
                </div>
            </body>

            </html>';
    
    }

    public function get_guest_client_details(Request $request) {
        $details = DB::connection('sales_db')
        ->table('guest_user_details')
        ->where('id', $request->id)
        ->first();
        return response()->json(['status' => 200, 'message' => 'Guest Client Details','data'=>$details]);
    }

    public function update_guest_client_details(Request $request)
    {
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
            'created_by'=>$request->created_by,
        );
        $updateDetails = DB::connection('sales_db')->table('guest_user_details')
        ->where('id',$request->editClientId)
        ->update($guestArray);

        if($updateDetails)
        {
            return response()->json(['status' => 200, 'message' => 'Client details updated successfully']);
        }
        else
        {
            return response()->json(['status' => 201, 'message' => 'Something went wrong. Please try agian.']);
        }
    }

    public function get_client_feedback_details(Request $request)
    {
        $feedbackDetails = DB::connection('sales_db')->table('client_feedback')
        ->select('client_feedback.*','app_call_log_reason.reason_name')
        ->leftJoin('app_call_log_reason','app_call_log_reason.id','=','client_feedback.reason_id')
        ->where(array('client_id'=>$request->clientId))
        ->paginate($request->per_page);
        return response()->json(['status'=>200,'message'=>'Client Feedback Details','data'=>$feedbackDetails,'last_page'=>$feedbackDetails->lastPage()]); 
    }

    public function get_package_offer_details(Request $request)
    {
        $selectedPercent = $request->selectedPercent; // Assuming selectedPercent is an associative array
        $checkedOfferId = $request->checkedOffers; // Assuming checkedOffers is the ID of the selected offer
        $offerDetails = DB::connection('sales_db')->table('offer_discount_rate as odr')
        ->select('odr.discount_percent','odr.discount_type')
        ->where('odr.id', $selectedPercent)
        ->first();
        return response()->json(['status' => 200, 'message' => 'Offer Details','percent'=>$offerDetails->discount_percent,'type'=>$offerDetails->discount_type]);
    }

    // public function checke_company_package_info(Request $request)
    // {
    //     $today =date('Y-m-d');
    //     $packageCounts = DB::connection('sales_db')->table('package_info')
    //     ->selectRow(
    //         'date_diff(package_end_date ,$today as =clientDateDiff)',
    //         'date_diff(package_end_date ,$today as =companyDateDiff)')
    //     ->selectRaw(
    //         'SUM(CASE WHEN client_id = ? THEN 1 ELSE 0 END) as client_no_of_pkg, 
    //         SUM(CASE WHEN comp_id = ? THEN 1 ELSE 0 END) as company_no_of_pkg',
    //         [$request->client_id, $request->comp_id]
    //     )
    //     ->first();

    //     if($packageCounts->client_no_of_pkg > 0)
    //     {
    //         if($packageCounts->clientDateDiff > 50)
    //         {
    //             $clientType = "retention";
    //         }
    //         else
    //         {
    //             $clientType = "Renew";
    //         }
           
    //     }
    //     else
    //     {
    //         $clientType = "New";
    //     }

    //     if($packageCounts->company_no_of_pkg > 0)
    //     {
    //         if($packageCounts->companyDateDiff > 50)
    //         {
    //             $clientType = "retention";
    //         }
    //         else
    //         {
    //             $clientType = "Renew";
    //         }
    //     }
    //     else
    //     {
    //         $companyType = "New";
    //     }
       

    //     return response()->json(['status' => 200, 'message' => 'Package Details client and company wise','companyType'=>$companyType,'clientType'=>$clientType]);

    // }

    public function check_company_package_info(Request $request)
    {
        if($request->comp_id !="New")
        {
            $packageCounts = DB::connection('sales_db')->table('package_info')
            ->selectRaw(
                'SUM(CASE WHEN client_id = ? THEN 1 ELSE 0 END) as client_no_of_pkg, 
                SUM(CASE WHEN comp_id = ? THEN 1 ELSE 0 END) as company_no_of_pkg,
                MAX(CASE WHEN client_id = ? THEN package_end_date ELSE NULL END) as lastClientPackageEndDate,
                MAX(CASE WHEN comp_id = ? THEN package_end_date ELSE NULL END) as lastCompanyPackageEndDate',
                [$request->client_id, $request->comp_id, $request->client_id, $request->comp_id]
            )
            ->first();
        }
        //use Carbon\Carbon;
        if($request->comp_id !="New")
        {
            $today = Carbon::today();

            // Calculate absolute date differences
            $clientDateDiff = $packageCounts->lastClientPackageEndDate 
                ? abs($today->diffInDays(Carbon::parse($packageCounts->lastClientPackageEndDate), false)) 
                : null;

            $companyDateDiff = $packageCounts->lastCompanyPackageEndDate 
                ? abs($today->diffInDays(Carbon::parse($packageCounts->lastCompanyPackageEndDate), false)) 
                : null;

            // Determine client type based on the date difference
            if ($packageCounts->client_no_of_pkg > 0) {
                $clientType = $clientDateDiff > 50 ? "Retention" : "Renew";
            } else {
                $clientType = "New";
            }
            
            // Determine company type based on the date difference
            if($packageCounts->company_no_of_pkg > 0) {
                $companyType = $companyDateDiff > 50 ? "Retention" : "Renew";
            } else {
                $companyType = "New";
            }
            if($companyType=='New' && $clientType=='New')
            {
                $packageType = 'New';
            }
            else
            {
                $packageType=$companyType;
            }
        }

        //reg and service details
        $prePackageDetails = DB::connection('sales_db')->table('pre_package')
           ->select('product_id','service_id')
           ->where('id',$request->pre_package_id)
           ->first();

        $regDetails = DB::connection('sales_db')->table('reg_and_tax_details')
           ->select('id','reg_amount','gst_percent','service_charges')
           ->where(array('product_id'=>$prePackageDetails->product_id,'service_id'=>$prePackageDetails->service_id,'status'=>1))
           ->first();
        if($request->comp_id != "New")
        {
            if($companyType=='New')
            {
            $regAmount = $regDetails->reg_amount;
            
            }
            else
            {
                $regAmount = 0;
            }
            $totalWithGST = round($regAmount + ($regAmount * 0.18)); 
        }

       
        
        if($request->comp_id=="New")
        {   
            return response()->json([
                'status' => 200,
                'message' => 'Package Details client and company wise',
                'companyType' => 'New',
                'clientType' => 'New',
                'packageType' =>'New',
                'dateDiff'=>0,
                'regAmount' => round($regDetails->reg_amount + ($regDetails->reg_amount * 0.18)),
                'serviceCharge'=>$regDetails->service_charges,
            ]);
           
        }
        else
        {
            return response()->json([
                'status' => 200,
                'message' => 'Package Details client and company wise',
                'companyType' => $companyType,
                'clientType' => $clientType,
                'packageType' =>$packageType,
                'dateDiff'=>$companyDateDiff,
                'regAmount' => $totalWithGST,
                'serviceCharge'=>$regDetails->service_charges,
            ]);
        }
        
    }

    public function check_duplicate_transaction($trnId)
    {
        $transactionInfo = DB::connection('sales_db')->table('wallet_history')
            ->select('transaction_id')
            ->where('transaction_id', $trnId)
            ->orWhere('utr_no', $trnId)
            ->first();
        return response()->json(['isDuplicate' => $transactionInfo ? true : false]);
    }

    public function check_duplicate_utr($utrNo)
    {
        $utrInfo = DB::connection('sales_db')->table('wallet_history')
            ->select('utr_no')
            ->where('utr_no', $utrNo)
            ->orWhere('transaction_id', $utrNo)
            ->first();
            
        return response()->json(['isDuplicate' => $utrInfo ? true : false]);
    }

    public function get_state_list()
    {
        $stateInfo = DB::connection('sales_db')->table('state')
        ->where('status',1)
        ->get();
        return response()->json(['status' => 200, 'message' => 'State List','data'=>$stateInfo]);
    }

    public function get_city_by_state_name($stateId)
    {
        $cityInfo = DB::connection('sales_db')->table('cities')
        ->where('state_id',$stateId)
        ->get();

        if($cityInfo)
        {
            return response()->json(['status' => 200, 'message' => 'City List','data'=>$cityInfo]);
        }
        else
        {
            return response()->json(['status' => 201, 'message' => 'City not found.']);
        }
    }

   

    public function update_admin_package_status(Request $request)
    {
       
        $categoryArray = explode(',', $request->packageCategory); 

        if($request->adminStatus == 1) 
        {
           
            $documentInfo = DB::connection('sales_db')
                ->table('sales_document')
                ->select('doc_type_id')
                ->where(function($query) use ($categoryArray, $request) {
                    foreach ($categoryArray as $category) {
                        $query->whereRaw('FIND_IN_SET(?, service_id)', [$category]);
                    }
                    $query->whereRaw('FIND_IN_SET(?, org_id)', [$request->orgType]);
                })
                ->where([
                    'product_id' => $request->product_id,
                    'is_required' => 1,
                    'status' => 1
                ])
                ->get();

            $missingDocTypeIds = [];

            // Check if documents exist and their status
            foreach ($documentInfo as $doc) 
            {
                $docStatus = DB::connection('sales_db')
                    ->table('document_info')
                    ->where([
                        'comp_id' => $request->company_id,
                        'doc_type_id' => $doc->doc_type_id,
                    ])
                    ->whereIn('status', [1])
                    ->exists();

                // If the document doesn't exist or its status is not approved
                if (!$docStatus) 
                {
                    $missingDocTypeIds[] = $doc->doc_type_id;
                }
            }

            // If there are missing or rejected documents, return an error message
            if (!empty($missingDocTypeIds)) 
            {
                $missingDoc = DB::connection('sales_db')
                    ->table('document_type')
                    ->selectRaw('GROUP_CONCAT(document_name) as docName')
                    ->whereIn('id', $missingDocTypeIds) 
                    ->first();

                return response()->json([
                    'status' => 201, 
                    'message' => 'The following documents are either missing, pending approval, or have been rejected: ( '.$missingDoc->docName.' )'
                ]);
            } 
            else 
            {
                // Update package info with active status
                $updateArray = [
                    'remarks' => $request->adminRemarks,
                    'admin_status' => $request->adminStatus,
                    'package_status' => 1,
                    'package_start_date'=>date('Y-m-d H:i'),
                    'package_end_expected_date'=>Carbon::createFromFormat('Y-m-d',$todayDate)->addDays(30),
                ];

                $packageInfo = DB::connection('sales_db')
                    ->table('package_info')
                    ->where('package_id', $request->pkgId)
                    ->update($updateArray);

                if($packageInfo) 
                {
                    return response()->json(['status' => 200, 'message' => 'Admin status and package activated successfully.']);
                } 
                else 
                {
                    return response()->json(['status' => 201, 'message' => 'Something went wrong. Please try again']);
                }
            }
        } 
        else 
        {
            // Update package info without activating the package
            $updateArray = [
                'remarks' => $request->adminRemarks,
                'admin_status' => $request->adminStatus,
                
            ];

            $packageInfo = DB::connection('sales_db')
                ->table('package_info')
                ->where('package_id', $request->pkgId)
                ->update($updateArray);

            return response()->json(['status' => 200, 'message' => 'Admin status deactivated successfully']);
        }
    }

    public function get_request_service_details()
    {
        $requestService = DB::connection('sales_db')
        ->table('support_services')
        ->select('id','title')
        ->where('status',1)
        ->whereIn('ticket_visibility',[2,3])
        ->get();
        return response()->json(['status' => 200, 'message' => 'Request service details','data'=>$requestService]);
    }

    public function get_request_category_details(Request $request)
    {
        $requestCategory = DB::connection('sales_db')
        ->table('support_category')
        ->select('id','title')
        ->where(array('support_service_id'=>$request->service_id,'status'=>1))
        ->get();

        return response()->json(['status' => 200, 'message' => 'Request category details','data'=>$requestCategory]);
    }

    public function get_request_active_package_details(Request $request)
    {
      
        $packageDetails = DB::connection('sales_db')
            ->table('package_info')
            ->select('package_id', 'package_name')
            ->where('client_id', $request->client_id)
            ->where('package_status', '!=', 4)
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Package details',
            'data' => $packageDetails
        ]);
    }

    

    public function get_raise_request_details(Request $request)
    {
        $requestService = DB::connection('sales_db')
            ->table('support_request as sr')
            ->select(
                'sr.id', 'sr.status', 'sr.support_category_id', 'sr.support_service_id', 
                'sr.client_id', 'sr.company_id', 'sr.complaint', 'sr.assign_to', 'sr.created_at', 
                'sr.created_by', 'sc.title as req_title', 'sr.exe_id', 'ss.title as service_title', 
                'ci.business_name', 'ts.title as ticket_title'
            )
            ->leftJoin('company_info as ci', 'ci.comp_id', '=', 'sr.company_id')
            ->leftJoin('support_services as ss', 'ss.id', '=', 'sr.support_service_id')
            ->leftJoin('support_category as sc', 'sc.id', '=', 'sr.support_category_id')
            ->leftJoin('ticket_status as ts', 'ts.id', '=', 'sr.status')
            ->where('sr.client_id',$request->client_id)
            ->paginate($request->per_page ?? 10);

            $requestService->map(function ($item) {
                $empName = DB::table('emp_basic_info')
                    ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")
                    ->where('emp_id', $item->exe_id)
                    ->first();
                $item->emp_name = $empName->emp_name ?? 'N/A';

                $assignEmp = DB::table('emp_basic_info')
                    ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")
                    ->where('emp_id', $item->assign_to)
                    ->first();
                $item->assign_emp = $assignEmp->emp_name ?? 'N/A';
                
                return $item;
            });

            return response()->json([
                'status' => 200,
                'message' => 'Raise Request Details',
                'data' => $requestService,
                'last_page' => $requestService->lastPage()
            ]);
    }

    public function raise_new_requeat(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
          'product_id' => 'required',
          'service_id'=>'required',
          'requestType'=>'required',
          'requestCategoryType'=>'required',
          'remarks'=>'required',
          'created_by'=>'required',
          'client_id'=>'required',
          'emp_id'=>'required',
          'companyId'=>'required',
        ]);

        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
        }

        $raiseRequest = array(
            'product_id'=>$request->product_id,
            'service_id'=>$request->service_id,
            'support_service_id'=>$request->requestType,
            'support_category_id'=>$request->requestCategoryType,
            'complaint'=>$request->remarks,
            'exe_id'=>$request->exe_id,
            'created_by'=>$request->created_by,
            'package_id'=>$request->packageId,
            'company_id'=>$request->companyId,
            'client_id'=>$request->client_id,
        );

        $raiseRequestId = DB::connection('sales_db')->table('support_request')->insertGetId($raiseRequest);
        if($raiseRequestId)
        {
            // $message = 'abc';
            // $emp_id = 'RIMS1';
            // $type = 'hgjkhkjhkjh';
            // event(new RaiseRequest($message,$emp_id,$type));

            return response()->json(['status' => 200, 'message' => 'Request raise successfully']);
        }
        else
        {
            return response()->json(['status' => 201, 'message' => 'Something went wrong. Please try again']);
        }
    }

    public function get_company_list(Request $request)
    {
        $clientId=$request->client_id;
        $companyList = DB::connection('sales_db')
        ->table('company_info')
        ->select('comp_id','business_name')
        ->where('client_id',$clientId)
        ->get();
        return response()->json(['status' => 200, 'message' => 'Company List','data'=>$companyList]);
    }

    public function get_new_raise_request_details(Request $request)
    {
        $requestService = DB::connection('sales_db')
            ->table('support_request as sr')
            ->select(
                'sr.id', 'sr.status', 'sr.support_category_id', 'sr.support_service_id', 
                'sr.client_id', 'sr.company_id', 'sr.complaint', 'sr.assign_to', 'sr.created_at', 
                'sr.created_by', 'sc.title as req_title', 'sr.exe_id', 'ss.title as service_title', 
                'ci.business_name', 'ts.title as ticket_title'
            )
            ->leftJoin('company_info as ci', 'ci.comp_id', '=', 'sr.company_id')
            ->leftJoin('support_services as ss', 'ss.id', '=', 'sr.support_service_id')
            ->leftJoin('support_category as sc', 'sc.id', '=', 'sr.support_category_id')
            ->leftJoin('ticket_status as ts', 'ts.id', '=', 'sr.status')
            ->whereIn('sr.status',[1,2,3])
            ->paginate($request->per_page ?? 10);

            $requestService->map(function ($item) {
                $empName = DB::table('emp_basic_info')
                    ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")
                    ->where('emp_id', $item->exe_id)
                    ->first();
                $item->emp_name = $empName->emp_name ?? 'N/A';

                $assignEmp = DB::table('emp_basic_info')
                    ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")
                    ->where('emp_id', $item->assign_to)
                    ->first();
                $item->assign_emp = $assignEmp->emp_name ?? 'N/A';
                
                return $item;
            });

            return response()->json([
                'status' => 200,
                'message' => 'Raise Request Details',
                'data' => $requestService,
                'last_page' => $requestService->lastPage()
            ]);
    }

    public function get_request_details_by_id(Request $request)
    {
        $requestService = DB::connection('sales_db')
        ->table('support_request as sr')
        ->select(
            'sr.id', 'sr.status', 'sr.support_category_id', 'sr.support_service_id', 
            'sr.client_id', 'sr.company_id', 'sr.complaint', 'sr.assign_to', 'sr.created_at', 
            'sr.created_by','sc.title as req_title', 'sr.exe_id', 'ss.title as service_title', 
            'ci.business_name', 'ts.title as ticket_title','pi.package_id','pi.package_name','pi.package_status','pi.package_duration','pi.total_lead','pi.sent_lead','pi.package_type'
        )
        ->leftJoin('company_info as ci', 'ci.comp_id', '=', 'sr.company_id')
        ->leftJoin('support_services as ss', 'ss.id', '=', 'sr.support_service_id')
        ->leftJoin('support_category as sc', 'sc.id', '=', 'sr.support_category_id')
        ->leftJoin('package_info as pi','pi.package_id','=','sr.package_id')
        ->leftJoin('ticket_status as ts', 'ts.id', '=', 'sr.status')
        ->where('sr.id',$request->id)
        ->get();

        $requestService->map(function ($item) {
            $empName = DB::table('emp_basic_info')
                ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")
                ->where('emp_id', $item->exe_id)
                ->first();
            $item->emp_name = $empName->emp_name ?? 'N/A';

            $assignEmp = DB::table('emp_basic_info')
                ->selectRaw("GROUP_CONCAT(emp_fname, ' ', emp_lame) as emp_name")
                ->where('emp_id', $item->assign_to)
                ->first();
            $item->assign_emp = $assignEmp->emp_name ?? 'N/A';
            
            return $item;
        });

        return response()->json([
            'status' => 200,
            'message' => 'Raise Request Details',
            'data' => $requestService,
        ]);
    }

    public function change_package_status_by_admin(Request $request)
    {
        $pkgArray = array('package_status'=>$request->status);
        $packageInfo = DB::connection('sales_db')->table('package_info')
        ->where('package_id',$request->packageId)
        ->update($pkgArray);

        if($packageInfo)
        {
            $pkgArray = array(
                'client_id'=>$request->clientId,
                'package_id'=>$request->packageId,
                'package_status'=>$request->status==1?1:0,
                'status_type'=>3,
                'package_start_date'=>$request->status,
                'remarks'=>$request->packageRemarks
            );
            $history = DB::connection('sales_db')->table('package_enable_disable_history')->insertGetId($pkgArray);
            if($history)
            {
                return response()->json([
                    'status' => 200,
                    'message' => 'Package status updated successfully',
                ]);
            }
            else
            {
                return response()->json([
                    'status' => 201,
                    'message' => 'Something went wrong with insert in history details data. Please try Again.',
                ]);
            }
        }
        else
        {
            return response()->json([
                'status' => 201,
                'message' => 'Something went wrong with update package status. Please try Again.',
            ]);
        }
    } 
    
    public function get_ticket_status_details()
    {
        $ticketDetails = DB::connection('sales_db')
        ->table('ticket_status')
        ->select('id','title')
        ->where('status',1)
        ->get();
        
        return response()->json([
            'status' => 200,
            'message' => 'Ticket Details',
            'data'=> $ticketDetails
        ]);
    }

    public function update_request_status(Request $request)
    {
        $updateArray = array(
            'status'=>$request->ticketStatusId
        );
        $reqArray = DB::connection('sales_db')->table('support_request')
        ->where('id',$request->reqId)
        ->update($updateArray);
        if($reqArray)
        {
            $insertArray = array(
                'support_request_id'=>$request->reqId,
                'remarks'=>$request->requestRemarks,
                'followup_by'=>$request->followupBy,
                'followup_by_id'=>$request->emp_id,
                'status'=>$request->ticketStatusId,
                'updated_at'=>date('Y-m-d H:i')
            );

            $insertId = DB::connection('sales_db')->table('support_history')->insertGetId($insertArray);
            if($insertId)
            {
                return response()->json([
                    'status' => 200,
                    'message' => 'Request Status updated successfully',
                ]);
            }
            else
            {
                return response()->json([
                    'status' => 201,
                    'message' => 'Something went wrong with insert history data',
                ]);
            }
           
        }
        else
        {
            return response()->json([
                'status' => 201,
                'message' => 'Something went wrong with update request status',
               
            ]);
        }
    }

}
