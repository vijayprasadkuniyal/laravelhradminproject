<?php
namespace App\Http\Controllers\FinanceApi;
use DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Storage;

class financeController extends Controller
{


    // public function get_recived_amount()
    // {
    //     $walletLog = DB::connection('sales_db')->table('wallet_history')
    //             ->select('wallet_history.*','client_info.client_name','company_info.business_name','bank_info.bank_name as bankName')
    //             ->leftjoin('client_info','client_info.client_id','=','wallet_history.client_id')
    //             ->leftJoin('company_info','company_info.comp_id','=','wallet_history.comp_id')
    //             ->leftJoin('bank_info','bank_info.id','=','wallet_history.bank_name')
    //             ->where(array('wallet_history.status'=>0))
    //             ->get();
    //     return response()->json(['status'=>200,'message'=>'New Amount List','data'=>$walletLog]);
    // }

    public function get_recived_amount()
    {
        $walletLog = DB::connection('sales_db')->table('wallet_history')
                ->select('wallet_history.*','client_info.client_name','company_info.business_name','bank_info.bank_name as bankName')
                ->leftjoin('client_info','client_info.client_id','=','wallet_history.client_id')
                ->leftJoin('company_info','company_info.comp_id','=','wallet_history.comp_id')
                ->leftJoin('bank_info','bank_info.id','=','wallet_history.bank_name')
                ->whereIn('wallet_history.status',[0,3])
                ->get();

        

        

        return response()->json(['status'=>200,'message'=>'New Amount List','data'=>$walletLog]);
    }

    public function get_recived_amount_by_id($payment_id)
    {
        $walletLog = DB::connection('sales_db')->table('wallet_history')
        ->select('wallet_history.*','client_info.client_name','company_info.business_name','bank_info.bank_name as bankName')
        ->leftJoin('bank_info','bank_info.id','=','wallet_history.bank_name')
        ->leftjoin('client_info','client_info.client_id','=','wallet_history.client_id')
        ->leftJoin('company_info','company_info.comp_id','=','wallet_history.comp_id')
        ->where(array('wallet_history.id'=>$payment_id,'wallet_history.status'=>0))
        ->first();
        return response()->json(['status'=>200,'message'=>'New Amount details','data'=>$walletLog]);
    }

    public function new_payment_amount(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'remarks' => 'required',
            'payment_id' => 'required',
            'status' => 'required',
            'created_by'=>'required',
            ]);
        if($validator->fails())
        {
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);
        }
        if($request->status ==1)
        {

            $walletHistory = DB::connection('sales_db')->table('wallet_history')
            ->select('wallet_id','client_id','credit','comp_id','created_by')->where('id',$request->payment_id)->first();

            $company_info = DB::connection('sales_db')->table('company_info')->where('comp_id', $walletHistory->comp_id)->first();
            if($company_info){
                $comp_name =  $company_info->business_name;
            }
            else{
                $comp_name = '';

            }

            //eturn $walletHistory;
        
            $updatePayment = array(
                'status'=>$request->status,
                'remarks'=>$request->remarks,
                'approved_by'=>$request->created_by
            );

            $walletUpdate = DB::connection('sales_db')->table('wallet_history')
            ->where('id',$request->payment_id)
            ->update($updatePayment);

            if($walletUpdate)
            {
                
                $walletInfoHistory = DB::connection('sales_db')->table('wallet_info')
                ->select('wallet_id','credit_amount','debit_amount','balance_amount')->where('wallet_id',$walletHistory->wallet_id)->first();
            

                // $updateWalletInfoTable = array(
                //     'credit_amount'=>$walletInfoHistory->credit_amount + $walletHistory->credit,
                //     'balance_amount'=>($walletInfoHistory->credit_amount + $walletHistory->credit) - $walletInfoHistory->debit_amount
                // );
                
                $updateWalletInfoTable = array(
                    'credit_amount' => (float)$walletInfoHistory->credit_amount + (float)$walletHistory->credit,
                    'balance_amount' => ((float)$walletInfoHistory->credit_amount + (float)$walletHistory->credit) - (float)$walletInfoHistory->debit_amount
                );


                $updateWalletInfo = DB::connection('sales_db')->table('wallet_info')
                ->where('wallet_id',$walletHistory->wallet_id)
                ->update($updateWalletInfoTable);

                $updateHistoryBalance = array(
                    'balance'=>($walletInfoHistory->credit_amount + $walletHistory->credit) - $walletInfoHistory->debit_amount
                );
                $walletUpdateBalance = DB::connection('sales_db')->table('wallet_history')
                ->where('id',$request->payment_id)
                ->update($updateHistoryBalance);

                if($walletUpdateBalance)
                {
                    if($walletHistory->created_by){
                        $notification_array = array('emp_id'=>$walletHistory->created_by,'notification'=>'Your Wallet Amount Is Approved for'.' '.$comp_name,'client_id'=>$walletHistory->client_id,'type'=>'payment_approved/cancel');
                        DB::table('emp_notifications')->insert($notification_array);


                    }
                    return response()->json(['status'=>200,'message'=>'amount approve successfully']);
                }
                else
                {
                    return response()->json(['status'=>200,'message'=>'something went wrong.']);
                }
            }
        }
        else
        {
            $updatePayment = array(
                'status'=>$request->status,
                'remarks'=>$request->remarks,
                'approved_by'=>$request->created_by
            );


             $walletHistory = DB::connection('sales_db')->table('wallet_history')
            ->select('wallet_id','client_id','credit','comp_id','created_by')->where('id',$request->payment_id)->first();

            $company_info = DB::connection('sales_db')->table('company_info')->where('comp_id', $walletHistory->comp_id)->first();
            if($company_info){
                $comp_name =  $company_info->business_name;
            }
            else{
                $comp_name = '';

            }


            $walletUpdate = DB::connection('sales_db')->table('wallet_history')
            ->where('id',$request->payment_id)
            ->update($updatePayment);
            if($walletUpdate)
            {
                if($walletHistory->created_by){
                        $notification_array = array('emp_id'=>$walletHistory->created_by,'notification'=>'Your Wallet Amount Is Rejected for'.' '.$comp_name,'client_id'=>$walletHistory->client_id,'type'=>'payment_approved/cancel');
                        DB::table('emp_notifications')->insert($notification_array);
                    }

                return response()->json(['status'=>200,'message'=>'Transation not found.']);
            }
            else
            {
                return response()->json(['status'=>200,'message'=>'Something went wrong on update status.']);
            }
        }
    }

    public function get_approved_amount_reports(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'emp_id' => 'required',
        ]);

        if($validator->fails())
        {
            $messages = $validator->messages();
            return response()->json(["messages" => $messages, 'status' => 400]);
        }

        $fromDate = $request->from_date ? Carbon::parse($request->from_date)->format('Y-m-d') : Carbon::now()->startOfMonth()->format('Y-m-d');
        $toDate = $request->to_date ? Carbon::parse($request->to_date)->format('Y-m-d') : Carbon::now()->endOfMonth()->format('Y-m-d');

       // $selectedEmp = $request->selectedEmp;

        $approvedHistory = DB::connection('sales_db')->table('wallet_history as wh')
            ->select('wh.wallet_id', 'wh.client_id', 'wh.credit', 'wh.transaction_id', 'wh.transaction_mode', 'wh.transaction_date', 'wh.remarks', 'wh.utr_no', 'wh.approved_by', 'wh.created_by', 'wh.created_date', 'wh.payment_type',  'wh.status')
            ->leftJoin('client_info as ci', 'ci.client_id', '=', 'wh.client_id')
            ->where('transaction_mode', 'Cr')
            ->whereIn('wh.status', [1, 2])
            // ->when($selectedEmp, function ($query) use ($selectedEmp) {
            //     $query->where('dh.download_by',$selectedEmp);
            // })
            
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('wh.created_date', [$fromDate.' 00:00:00', $toDate.' 23:59:59']);
            })
            ->orderBy('wh.id','DESC')
            ->paginate($request->per_page ?? 15); // Default to 15 if per_page is not set

        // Get all unique emp_id for created_by and approved_by
        $createdByIds = $approvedHistory->pluck('created_by')->unique();
        $approvedByIds = $approvedHistory->pluck('approved_by')->unique();
        $empIds = $createdByIds->merge($approvedByIds)->unique();

        // Fetch employee names
        $empNames = DB::table('emp_basic_info')
            ->whereIn('emp_id', $empIds)
            ->selectRaw("emp_id, CONCAT(emp_fname, ' ', emp_lame) as emp_name")
            ->pluck('emp_name', 'emp_id');

        // Map the employee names to the approvedHistory
        $approvedHistory->map(function($item) use ($empNames) {
            $item->created_by_name = $empNames[$item->created_by] ?? 'App';
            $item->approved_by_name = $empNames[$item->approved_by] ?? 'Auto';
            return $item;
        });

        return response()->json([
            'status' => 200,
            'message' => 'Payment History Details',
            'data' => $approvedHistory,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            //'selectedEmp' => $selectedEmp,
            'last_page' => $approvedHistory->lastPage()
        ]);
    }

    public function get_payment_history_reports(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'emp_id' => 'required',
        ]);
        if ($validator->fails()) {
            $messages = $validator->messages();
            return response()->json(["messages" => $messages, 'status' => 400]);
        }
    
        $fromDate = $request->from_date 
            ? Carbon::parse($request->from_date)->format('Y-m-d') 
            : Carbon::now()->startOfMonth()->format('Y-m-d');
    
        $toDate = $request->to_date 
            ? Carbon::parse($request->to_date)->format('Y-m-d') 
            : Carbon::now()->endOfMonth()->format('Y-m-d');
    
        $paymentHistory = DB::connection('sales_db')->table('payment_history as ph')
            ->select(
                'ph.client_id', 
                'ph.paid_amount', 
                'ph.comp_id', 
                'ph.tax_amount', 
                'ph.reg_amount', 
                'ph.package_id', 
                'ph.product_id', 
                'ph.service_id', 
                'ph.payment_for', 
                'ph.invoice_name', 
                'ph.invoice_status', 
                'ph.created_date', 
                //'ci.business_name', 
                'ph.created_by',
                'p.product_name',
                'ps.service_name'
            )
            ->leftJoin('client_info as ci', 'ci.client_id', '=', 'ph.client_id')
            ->leftJoin('product as p', 'p.id', '=', 'ph.product_id')
            ->leftJoin('product_service as ps', 'ps.id', '=', 'ph.service_id')
            ->whereIn('ph.invoice_status', [1, 2])
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('ph.created_date', [$fromDate.' 00:00:00', $toDate.' 23:59:59']);
            })
            ->paginate($request->per_page ?? 15); 
    
        $createdByIds = $paymentHistory->pluck('created_by')->unique();
        $empNames = DB::table('emp_basic_info')
            ->whereIn('emp_id', $createdByIds)
            ->selectRaw("emp_id, CONCAT(emp_fname, ' ', emp_lame) as emp_name")
            ->pluck('emp_name', 'emp_id');
        $paymentHistory->map(function ($item) use ($empNames) {
            $item->created_by_name = $empNames[$item->created_by] ?? 'App';
            return $item;
        });
    
        return response()->json([
            'status' => 200,
            'message' => 'Payment History Details',
            'data' => $paymentHistory,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'last_page' => $paymentHistory->lastPage()
        ]);
    }

    public function get_ledger_details_reports(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'emp_id' => 'required',
        ]);
        if($validator->fails())
        {
            $messages = $validator->messages();
            return response()->json(["messages" => $messages, 'status' => 400]);
        }
    
        $fromDate = $request->from_date 
            ? Carbon::parse($request->from_date)->format('Y-m-d') 
            : Carbon::now()->startOfMonth()->format('Y-m-d');
    
        $toDate = $request->to_date 
            ? Carbon::parse($request->to_date)->format('Y-m-d') 
            : Carbon::now()->endOfMonth()->format('Y-m-d');
    
        $ledgerHistory = DB::connection('sales_db')->table('ledger_details as ld')
        ->select(
            'ld.voucher_no', 
            'ld.type', 
            'ld.particulars', 
            'ld.payment_for', 
            'ld.invoice_amount', 
            'ld.paid_amount', 
            'ld.paid_amount', 
            'ld.gst_amount', 
            'ld.gst_type', 
            'ld.client_id', 
            'ld.package_id', 
            'ld.translation_date', 
            'ld.approved_date', 
            'ci.business_name', 
        )
        ->leftJoin('client_info as ci', 'ci.client_id', '=', 'ld.client_id')
        ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
            $query->whereBetween('ld.approved_date', [$fromDate.' 00:00:00', $toDate.' 23:59:59']);
        })
        ->get();
        //->paginate($request->per_page ?? 15); 

        return response()->json([
            'status' => 200,
            'message' => 'Ledger Details',
            'data' => $ledgerHistory,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            //'last_page' => $ledgerHistory->lastPage()
        ]);
    }

    public function downloadLedgerDetails(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'emp_id' => 'required',
        ]);
        if ($validator->fails()) {
            $messages = $validator->messages();
            return response()->json(["messages" => $messages, 'status' => 400]);
        }

        $fromDate = $request->from_date 
            ? Carbon::parse($request->from_date)->format('Y-m-d') 
            : Carbon::now()->startOfMonth()->format('Y-m-d');

        $toDate = $request->to_date 
            ? Carbon::parse($request->to_date)->format('Y-m-d') 
            : Carbon::now()->endOfMonth()->format('Y-m-d');

        $ledgerHistory = DB::connection('sales_db')->table('ledger_details as ld')
            ->select(
                'ld.voucher_no', 
                'ld.type', 
                'ld.particulars', 
                'ld.invoice_amount', 
                'ld.gst_amount', 
                'ld.approved_date', 
                'ci.business_name'
            )
            ->leftJoin('client_info as ci', 'ci.client_id', '=', 'ld.client_id')
            ->when($fromDate && $toDate, function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('ld.approved_date', [$fromDate.' 00:00:00', $toDate.' 23:59:59']);
            })
            ->get();

        // Header
        $fileContent = "Ledger Details Report\n";
        $fileContent .= "From Date: " . $fromDate . "\n";
        $fileContent .= "To Date: " . $toDate . "\n\n";
        $fileContent .= "S.No.\tVoucher No\t Type\tCredit \tDebit \t Date\n";
        
        // Body
        foreach($ledgerHistory as $index => $ledger)
        {
            $fileContent .= ($index + 1) . "\t";
            $fileContent .= str_pad($ledger->voucher_no, 15, " ") . "\t";
           
           // $fileContent .= str_pad($ledger->particulars, 30, " ") . "\t";
            $fileContent .= str_pad($ledger->type, 1, " ") . "\t";
            $fileContent .= str_pad(($ledger->type === 'Credit' ? $ledger->invoice_amount : '-'),1, " ") . "\t";
            $fileContent .= str_pad(($ledger->type === 'Debit' ? $ledger->invoice_amount : '-'), 1, " ") . "\t";
           // $fileContent .= str_pad($ledger->gst_amount, 10, " ") . "\t";
            $fileContent .= Carbon::parse($ledger->approved_date)->format('Y-m-d') . "\n";
        }

        // Store file content temporarily
        $fileName = 'ledger_details_' . Carbon::now()->format('Ymd_His') . '.txt';
        Storage::disk('local')->put($fileName, $fileContent);

        // Download the file
        return response()->download(storage_path("app/{$fileName}"))->deleteFileAfterSend(true);
    }

    



    

}
