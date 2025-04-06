<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\BasicInfo;
use Carbon\Carbon;
use App\Http\Controllers\ThirdPartyApi\CommunicationApis;
use Validator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
//use App\Models\

class SalesFunnelController extends Controller
{
   //  public function sales_funnel_details(Request $request){
   //      $list = [];
   //   $data = DB::connection('sales_db')->table('sales_funnel');
   //   if($request->manager){
   //      $data->where('created_by',$request->manager)->whereRaw('MONTH(created_date) = ?',[date('m')]);
   //   }
   //   if($request->start_date && $request->end_date){
   //      $data->whereBetween('created_date', [$request->start_date, $request->end_date]);

   //   }
   //   if($request->product){
   //      $data->where('product_id',$request->product);

   //   }
   // //   if($request->service){
   // //      $data->where('service_id',$request->service);

   // //   }
   //   $record = $data->select('created_by','product_id',)
   //   ->groupBy('created_by','product_id')->get();
   //   foreach($record as $row){
   //      $emp = DB::table('emp_basic_info')->where('emp_id',$row->created_by)->first();
   //      //return  $emp;
   //      $reporting_manager = DB::table('emp_basic_info')->where('emp_id',$emp->reporting_manager)->first();
   //      if($reporting_manager){
   //          $manager = $reporting_manager->emp_fname;

   //      }
   //      else{
   //          $manager = '';

   //      }
   //      $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();
   //     // $category = DB::connection('sales_db')->table('product_service')->where('id',$row->service_id)->first();
   //      //$task =  DB::connection('sales_db')->table('followup_status')->first();
   //      $sum = DB::connection('sales_db')->table('sales_funnel')->where('created_by',$row->created_by)
   //      ->where('product_id',$row->product_id)
   //       ->sum('no_of_task');
   //     $amount = DB::connection('sales_db')->table('sales_funnel')->where('created_by',$row->created_by)
   //     ->where('product_id',$row->product_id)
   //      ->sum('amount');
   //     $won = DB::connection('sales_db')->table('sales_funnel')->where('created_by',$row->created_by)
   //     ->where('product_id',$row->product_id)
   //      ->sum('won_status');
   //     $loss = DB::connection('sales_db')->table('sales_funnel')->where('created_by',$row->created_by)
   //     ->where('product_id',$row->product_id)
   //      ->sum('loss_status');

   //      $list[] = array('emp'=> $emp->emp_fname,'manager'=> $manager,
   //      'product'=>$product->product_name,'amount'=>$amount,'sum'=>$sum,'won'=> $won,'loss'=>$loss);



   //   }
   //   return response()->json(['status'=>200,'data'=> $list]);

   // }

public function sales_funnel_details(Request $request)
{
    $list = [];
    $startDate = $request->startDate ?: Carbon::now()->startOfMonth()->toDateString();
    $endDate = $request->endDate ?: Carbon::now()->endOfMonth()->toDateString();

    
    $reporting_manager_query = DB::table('employee_managers')
        ->where('dept_manager', $request->emp_id)
        ->where('status', 1)
        ->where('dept_id', 3);

    if ($request->manager) {
        $reporting_manager_query->where('emp_id', $request->manager);
    }

    $reporting_managers = $reporting_manager_query->get();

    foreach ($reporting_managers as $row) {
        $emp = DB::table('emp_basic_info')->where('emp_id', $row->emp_id)->first();
        $reporting_manager = DB::table('emp_basic_info')->where('emp_id', $request->emp_id)->first();

        $default_data = [
            'emp' => $emp->emp_fname,
            'emp_id' => $emp->emp_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'product_id' => null,
            'manager' => $reporting_manager->emp_fname,
            'product' => null,
            'amount' => 0,
            'sum' => 0,
            'won' => 0,
            'loss' => 0,
            'percent' => 0,
            'accuracy' => 'low',
        ];

        $followup_details_query = DB::connection('sales_db')->table('sales_funnel')
            ->select('reporting_manager', 'product_id')
            ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$row->emp_id]);

        if ($request->product) {
            $followup_details_query->where('product_id', $request->product);
        }

        if ($request->start_date && $request->end_date) {
            $followup_details_query->whereBetween('created_date', [$request->start_date, $request->end_date]);
        } else {
            $followup_details_query->whereBetween('created_date', [$startDate, $endDate]);
        }

        $sales_funnel_percent_required = DB::table('target_subattribute')
            ->where('subattribute_name', 'Sales Funnel')
            ->whereRaw('YEAR(created_date) = ?', [date('Y')])
            ->first();

        $required_percent = DB::table('save_company_target')
            ->where('subattribute_id', $sales_funnel_percent_required->id)
            ->first();

        $followup_details = $followup_details_query->get();

        $forcast_percent_row = DB::connection('sales_db')->table('sales_funnel')
            ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$row->emp_id])
            ->select('reporting_manager', 'product_id', 'package_id')
            ->groupBy('reporting_manager', 'product_id', 'package_id')
            ->get();

        $groupedData = [];
        foreach ($forcast_percent_row as $for) {
            $total = 0;
            $won_amount = 0;

            $lastRecords = DB::connection('sales_db')->table('sales_funnel')
                ->where('product_id', $for->product_id)
                ->where('package_id', $for->package_id)
                ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$row->emp_id])
                ->whereMonth('created_date', Carbon::now()->month)
                ->whereYear('created_date', Carbon::now()->year)
                ->orderBy('created_date', 'desc')
                ->get();

            foreach ($lastRecords as $lastRecord) {
        if ($lastRecord->task_id == 19) {
            $won_amount += $lastRecord->amount;
        } else {
            $total += $lastRecord->amount;
        }
    }

    $total += $won_amount;

    $percent = $total > 0 ? ($won_amount / $total) * 100 : 0;
    $accuracy = ($required_percent->child_attribute_value <= $percent) ? 'high' : 'low';

            $key = $for->product_id;
            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [
                    'emp' => $emp->emp_fname,
                    'emp_id' => $emp->emp_id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'product_id' => $for->product_id,
                    'manager' => $reporting_manager->emp_fname,
                    'product' => null,
                    'amount' => 0,
                    'sum' => 0,
                    'won' => 0,
                    'loss' => 0,
                    'percent' => intval($percent),
                    'accuracy' => $accuracy,
                    'total'=>$total,
                ];
            }

            $product = DB::connection('sales_db')->table('product')->where('id', $for->product_id)->first();

            $sum_query = DB::connection('sales_db')->table('sales_funnel')
                ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$row->emp_id])
                ->where('product_id', $for->product_id);

            $amount_query = DB::connection('sales_db')->table('sales_funnel')
                ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$row->emp_id])
                ->where('product_id', $for->product_id);

            $won_query = DB::connection('sales_db')->table('sales_funnel')
                ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$row->emp_id])
                ->where('product_id', $for->product_id);

            $loss_query = DB::connection('sales_db')->table('sales_funnel')
                ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$row->emp_id])
                ->where('product_id', $for->product_id);

            if ($request->start_date && $request->end_date) {
                $sum_query->whereBetween('created_date', [$request->start_date, $request->end_date]);
                $amount_query->whereBetween('created_date', [$request->start_date, $request->end_date]);
                $won_query->whereBetween('created_date', [$request->start_date, $request->end_date]);
                $loss_query->whereBetween('created_date', [$request->start_date, $request->end_date]);
            } else {
                $sum_query->whereBetween('created_date', [$startDate, $endDate]);
                $amount_query->whereBetween('created_date', [$startDate, $endDate]);
                $won_query->whereBetween('created_date', [$startDate, $endDate]);
                $loss_query->whereBetween('created_date', [$startDate, $endDate]);
            }

            $groupedData[$key]['product'] = $product ? $product->product_name : null;
            $groupedData[$key]['sum'] = $sum_query->sum('no_of_task');
            $groupedData[$key]['amount'] = $amount_query->sum('amount');
            $groupedData[$key]['won'] = $won_query->sum('won_status');
            $groupedData[$key]['loss'] = $loss_query->sum('loss_status');
        }

        if ($followup_details->isEmpty()) {
            $default_data['percent'] = 0;
            $default_data['accuracy'] = 'low';
            $list[] = $default_data;
        } else {
            $list = array_merge($list, array_values($groupedData));
        }
    }

    return response()->json(['status' => 200, 'data' => $list]);
}



public function view_sales_funnel_details($emp_id, $product_id, $start, $end)
{
    $list = [];
    
   
    $data = DB::connection('sales_db')->table('sales_funnel')
        ->select('reporting_manager', 'product_id', 'task_id')
        ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$emp_id])
        ->where('product_id', $product_id)
        ->whereBetween('created_date', [$start, $end])
        ->get();
    //return $data;

    
    $groupedData = [];

    foreach ($data as $row) {
        
        $managers = explode(',', $row->reporting_manager);
        if (in_array($emp_id, $managers)) {
           
            $key = $row->product_id;

            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [
                    'emp' => null,
                    'product' => null,
                    'amount' => 0,
                    'sum' => 0,
                    'won' => 0,
                    'loss' => 0,
                    'task' => null,
                ];
            }

            $sum = DB::connection('sales_db')->table('sales_funnel')
                ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$emp_id])
                ->where('product_id', $row->product_id)
                ->where('task_id', $row->task_id)
                ->whereBetween('created_date', [$start, $end])
                ->sum('no_of_task');
            //return  $sum;

            $amount = DB::connection('sales_db')->table('sales_funnel')
                ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$emp_id])
                ->where('product_id', $row->product_id)
                ->where('task_id', $row->task_id)
                ->whereBetween('created_date', [$start, $end])
                ->sum('amount');

            $won = DB::connection('sales_db')->table('sales_funnel')
                ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$emp_id])
                ->where('product_id', $row->product_id)
                ->where('task_id', $row->task_id)
                ->whereBetween('created_date', [$start, $end])
                ->sum('won_status');

            $loss = DB::connection('sales_db')->table('sales_funnel')
                ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$emp_id])
                ->where('product_id', $row->product_id)
                ->where('task_id', $row->task_id)
                ->whereBetween('created_date', [$start, $end])
                ->sum('loss_status');

            $task = DB::connection('sales_db')->table('followup_status')->where('id', $row->task_id)->first();

           
            if (!$groupedData[$key]['emp']) {
                $reporting = DB::table('emp_basic_info')->where('emp_id', $emp_id)->first();
                $groupedData[$key]['emp'] = $reporting->emp_fname;
            }

            if (!$groupedData[$key]['product']) {
                $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
                $groupedData[$key]['product'] = $product->product_name;
            }

            if (!$groupedData[$key]['task']) {
                $groupedData[$key]['task'] = $task->activity_name;
            }

        
            $groupedData[$key]['sum'] = $sum;
            $groupedData[$key]['amount'] = $amount;
            $groupedData[$key]['won']  = $won;
            $groupedData[$key]['loss'] = $loss;
        }
    }
    foreach ($groupedData as $item) {
        $list[] = $item;
    }

    return response()->json(['status' => 200, 'data' => $list]);
}

public function view_sales_funnel_team_details($emp_id, $product_id, $start, $end)
{
    //return $start .' '.$end;
    $list = [];
    $data = DB::connection('sales_db')->table('sales_funnel')
        ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$emp_id])
        ->where('product_id', $product_id)
        ->whereBetween('created_date', [$start, $end])
        ->select('created_by', 'product_id')
        ->groupBy('created_by', 'product_id')
        ->get();

    foreach ($data as $row) {
        $check_team = DB::connection('sales_db')->table('sales_funnel')
            ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$row->created_by])
            ->exists();
        
        //$reporting = DB::table('emp_basic_info')->where('emp_id', $emp_id)->first();
        $emp_name = DB::table('emp_basic_info')->where('emp_id', $row->created_by)->first();
        $reporting = DB::table('emp_basic_info')->where('emp_id', $emp_name->reporting_manager)->first();
        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();

        if ($check_team) {
            $reporting_managers = 'yes';
            $sum = DB::connection('sales_db')->table('sales_funnel')
                ->where('product_id', $row->product_id)
                ->whereBetween('created_date', [$start, $end])
                ->where(function ($query) use ($row) {
                    $query->where('created_by', $row->created_by)
                          ->orWhereRaw('FIND_IN_SET(?, reporting_manager)', [$row->created_by]);
                })
                ->sum('no_of_task');

            $amount = DB::connection('sales_db')->table('sales_funnel')
                ->where('product_id', $row->product_id)
                ->whereBetween('created_date', [$start, $end])
                ->where(function ($query) use ($row) {
                    $query->where('created_by', $row->created_by)
                          ->orWhereRaw('FIND_IN_SET(?, reporting_manager)', [$row->created_by]);
                })
                ->sum('amount');

            $won = DB::connection('sales_db')->table('sales_funnel')
                ->where('product_id', $row->product_id)
                ->whereBetween('created_date', [$start, $end])
                ->where(function ($query) use ($row) {
                    $query->where('created_by', $row->created_by)
                          ->orWhereRaw('FIND_IN_SET(?, reporting_manager)', [$row->created_by]);
                })
                ->sum('won_status');

            $loss = DB::connection('sales_db')->table('sales_funnel')
                ->where('product_id', $row->product_id)
                ->whereBetween('created_date', [$start, $end])
                ->where(function ($query) use ($row) {
                    $query->where('created_by', $row->created_by)
                          ->orWhereRaw('FIND_IN_SET(?, reporting_manager)', [$row->created_by]);
                })
                ->sum('loss_status');
        } else {
            $reporting_managers = 'No';
            $sum = DB::connection('sales_db')->table('sales_funnel')
                ->where('created_by', $row->created_by)
                ->where('product_id', $row->product_id)
                ->whereBetween('created_date', [$start, $end])
                ->sum('no_of_task');

            $amount = DB::connection('sales_db')->table('sales_funnel')
                ->where('created_by', $row->created_by)
                ->where('product_id', $row->product_id)
                ->whereBetween('created_date', [$start, $end])
                ->sum('amount');

            $won = DB::connection('sales_db')->table('sales_funnel')
                ->where('created_by', $row->created_by)
                ->where('product_id', $row->product_id)
                ->whereBetween('created_date', [$start, $end])
                ->sum('won_status');

            $loss = DB::connection('sales_db')->table('sales_funnel')
                ->where('created_by', $row->created_by)
                ->where('product_id', $row->product_id)
                ->whereBetween('created_date', [$start, $end])
                ->sum('loss_status');
        }

        $list[] = [
            'manager' => $reporting->emp_fname,
            'emp' => $emp_name->emp_fname,
            'emp_id'=>$emp_name->emp_id,
            'product_id'=>$product->id,
            'product' => $product->product_name,
            'amount' => $amount,
            'sum' => $sum,
            'won' => $won,
            'loss' => $loss,
            'reporting_managers'=> $reporting_managers
        ];
    }

    return response()->json(['status' => 200, 'data' => $list]);
}
public function get_team_member_details(Request $request){
    if(!$request->start_date && !$request->end_date){
       $start =  Carbon::now()->startOfMonth()->toDateString();
       $end = Carbon::now()->endOfMonth()->toDateString();

    }
    else{
        $start =  $request->start_date;
        $end = $request->end_date;

    }
    
    $list = [];
    $data = DB::connection('sales_db')->table('sales_funnel')->orWhereRaw('FIND_IN_SET(?, reporting_manager)', [$request->employee])->exists();
    if($data){
       // return $request->product_id;
      $details = DB::connection('sales_db')->table('sales_funnel')->where('product_id',$request->product_id)->where('created_by',$request->employee)->orWhereRaw('FIND_IN_SET(?, reporting_manager)', [$request->employee])
      ->select('created_by','product_id','task_id')->groupBy('created_by','product_id','task_id')->get();
      //return  $details;
      foreach($details as $row){
        $emp_name = DB::table('emp_basic_info')->where('emp_id', $row->created_by)->first();
        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
        $task = DB::connection('sales_db')->table('followup_status')->where('id',$row->task_id)->first();
        $sum = DB::connection('sales_db')->table('sales_funnel')
        ->where('created_by', $row->created_by)
        ->where('product_id', $row->product_id)
        ->where('task_id',$row->task_id)
        ->whereBetween('created_date', [$start, $end])
        ->sum('no_of_task');

       $amount = DB::connection('sales_db')->table('sales_funnel')
        ->where('created_by', $row->created_by)
        ->where('product_id', $row->product_id)
        ->where('task_id',$row->task_id)
        ->whereBetween('created_date', [$start, $end])
        ->sum('amount');

      $won = DB::connection('sales_db')->table('sales_funnel')
        ->where('created_by', $row->created_by)
        ->where('product_id', $row->product_id)
        ->where('task_id',$row->task_id)
        ->whereBetween('created_date', [$start, $end])
        ->sum('won_status');

       $loss = DB::connection('sales_db')->table('sales_funnel')
        ->where('created_by', $row->created_by)
        ->where('product_id', $row->product_id)
        ->where('task_id',$row->task_id)
        ->whereBetween('created_date', [$start, $end])
        ->sum('loss_status');
        $list[] = [
            'emp' => $emp_name->emp_fname.' '.$emp_name->emp_lame,
           // 'emp_id'=>$emp_name->emp_id,
            //'product_id'=>$product->id,
            'product' => $product->product_name,
            'amount' => $amount,
            'sum' => $sum,
            'won' => $won,
            'loss' => $loss,
            'task'=>$task->activity_name,
        ];
    


       }
       return response()->json(['status' => 200, 'data' => $list]);

      }
    else{
        $details = DB::connection('sales_db')->table('sales_funnel')->where('product_id',$request->product_id)->where('created_by',$request->employee)->orWhereRaw('FIND_IN_SET(?, reporting_manager)', [$request->employee])
      ->select('created_by','product_id','task_id')->groupBy('created_by','product_id','task_id')->get();
        foreach($details as $row){
          $emp_name = DB::table('emp_basic_info')->where('emp_id', $row->created_by)->first();
          $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
          $task = DB::connection('sales_db')->table('followup_status')->where('id',$row->task_id)->first();
        $sum = DB::connection('sales_db')->table('sales_funnel')
        ->where('created_by', $row->created_by)
        ->where('product_id', $row->product_id)
        ->where('task_id',$row->task_id)
         ->whereBetween('created_date', [$start, $end])
        ->sum('no_of_task');

       $amount = DB::connection('sales_db')->table('sales_funnel')
        ->where('created_by', $row->created_by)
        ->where('product_id', $row->product_id)
        ->where('task_id',$row->task_id)
         ->whereBetween('created_date', [$start, $end])
        ->sum('amount');

      $won = DB::connection('sales_db')->table('sales_funnel')
        ->where('created_by', $row->created_by)
        ->where('product_id', $row->product_id)
        ->where('task_id',$row->task_id)
        ->whereBetween('created_date', [$start, $end])
        ->sum('won_status');

       $loss = DB::connection('sales_db')->table('sales_funnel')
        ->where('created_by', $row->created_by)
        ->where('product_id', $row->product_id)
        ->where('task_id',$row->task_id)
        ->whereBetween('created_date', [$start, $end])
        ->sum('loss_status');
        $list[] = [
            'emp' => $emp_name->emp_fname.' '.$emp_name->emp_lame,
             'emp_id'=>$emp_name->emp_id,
            //'product_id'=>$product->id,
            'product' => $product->product_name,
            'amount' => $amount,
            'sum' => $sum,
            'won' => $won,
            'loss' => $loss,
            'task'=>$task->activity_name,
        ];
        }

    }
    return response()->json(['status' => 200, 'data' => $list]);

}
public function sales_funnel_manager_emp($id){
    //return 'testing';
    $list = [];
    $manager = [];
    $manager_data =  DB::table('emp_basic_info')->where('emp_id',$id)->first();
    $data = DB::table('employee_managers')->whereRaw('FIND_IN_SET(?, reporting_to)', [$id])->exists();
    if($data){
        $data = DB::table('employee_managers')->whereRaw('FIND_IN_SET(?, reporting_to)', [$id])->get();
        //return 'test';
        foreach($data as $row){
            $emp_details =  DB::table('emp_basic_info')->where('emp_id',$row->emp_id)->first();
            $list[] = array('emp-id'=>$emp_details->emp_id,'name'=>$emp_details->emp_fname.' '.$emp_details->emp_lame);
    
    
        }
        $list[] = array_push($list,['emp_id'=>$manager_data->emp_id,'name'=>$manager_data->emp_fname.' '.$manager_data->emp_lame]);
        
    }
    return response()->json(['status' => 200, 'data' =>$list]);

}
public function get_sales_followup_data(Request $request)
{
    $curr_date = Carbon::now()->format('Y-m-d');

    $data = DB::connection('sales_db')->table('clients_followup_log')
        ->select('product_id', 'service_id', 'followup_id', 'created_by', 'client_id')
        ->groupBy('product_id', 'service_id', 'followup_id', 'created_by', 'client_id')
        ->get();

    foreach ($data as $row) {
        $count = 0;
        $calc_data = DB::connection('sales_db')->table('clients_followup_log')
            ->where('product_id', $row->product_id)
            ->where('service_id', $row->service_id)
            ->where('followup_id', $row->followup_id)
            ->where('client_id', $row->client_id)
            ->where('created_by', $row->created_by)
            ->whereDate('created_date', $curr_date);

        $get_emp_manager = DB::table('employee_managers')
            ->where('emp_id', $row->created_by)
            ->where('status', 1)
            ->first();

        $upcoming = DB::connection('sales_db')->table('clients_followup_log')
            ->where('product_id', $row->product_id)
            ->where('service_id', $row->service_id)
            ->where('followup_id', $row->followup_id)
            ->where('client_id', $row->client_id)
            ->where('created_by', $row->created_by)
            ->whereMonth('next_followup_date', Carbon::now()->month)
            ->get();

        foreach ($upcoming as $followup) {
            if ($followup->next_followup_date) {
                $check_data = DB::connection('sales_db')->table('clients_followup_log')
                    ->where('product_id', $row->product_id)
                    ->where('service_id', $row->service_id)
                    ->where('followup_id', $row->followup_id)
                    ->where('client_id', $row->client_id)
                    ->where('created_by', $row->created_by)
                    ->whereDate('created_date', $followup->next_followup_date)
                    ->first();
                if ($check_data) {
                    $count = 0;
                } else {
                    $count += 1;
                }
            }
        }

        $dataToInsertOrUpdate = [
            'product_id' => $row->product_id,
            'service_id' => $row->service_id,
            'task_id' => $row->followup_id,
            'no_of_task' => $calc_data->count(),
            'amount' => $calc_data->sum('amount'),
            'created_by' => $row->created_by,
            'reporting_manager' => $get_emp_manager ? $get_emp_manager->reporting_to : null,
            'upcoming_followup' => $count,
            'client_id' => $row->client_id,
        ];

        DB::connection('sales_db')->table('sales_funnel')->updateOrInsert(
            [
                'product_id' => $row->product_id,
                'service_id' => $row->service_id,
                'task_id' => $row->followup_id,
                'client_id' => $row->client_id,
                'created_by'=>$row->created_by,
            ],
            $dataToInsertOrUpdate
        );
    }
}

public function count_sales_data(Request $request) {
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    if ($currentMonth >= 4) {
        $financialYearStart = $currentYear;
        $financialYearEnd = $currentYear + 1;
    } else {
        $financialYearStart = $currentYear - 1;
        $financialYearEnd = $currentYear;
    }

    $financialYear = $financialYearStart . '-' . $financialYearEnd;
    $required_percent = DB::table('save_company_target')
        ->where('subattribute_id', 16)
        ->where('financial_year', $financialYear)
        ->where('department_id', 3)
        ->first();

    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    // Base query
    $data = DB::connection('sales_db')->table('clients_followup_log')
        ->leftJoin('company_info', 'company_info.comp_id', 'clients_followup_log.comp_id')
        ->leftJoin('guest_user_details', 'guest_user_details.id', 'clients_followup_log.client_id')
        ->select('clients_followup_log.*', 'company_info.client_name', 'company_info.group_id as company_info_group', 'guest_user_details.group_id as guest_group')
        ->whereIn('clients_followup_log.created_by', $emp_managers);

    $total_call_connected = DB::connection('sales_db')->table('clients_followup_log')
        ->leftJoin('company_info', 'company_info.comp_id', 'clients_followup_log.comp_id')
        ->leftJoin('guest_user_details', 'guest_user_details.id', 'clients_followup_log.client_id')
        ->select('clients_followup_log.*', 'company_info.client_name', 'company_info.group_id as company_info_group', 'guest_user_details.group_id as guest_group')
        ->whereIn('clients_followup_log.created_by', $emp_managers);

     //$currentDate = now()->format('Y-m-d');

     $due_amount = DB::connection('sales_db')
     ->table('package_info')
     ->where('due_amount', '>', 0)
     ->where('due_date', '!=', '');

     $recent_inactive = DB::connection('sales_db')->table('package_info')
    ->where('package_end_date', '>=', Carbon::now()->subDays(5))
    ->where('package_status',4);


    $total_upcoming_renewal  = DB::connection('sales_db')->table('package_info');

    $carray_forward = DB::connection('sales_db')->table('clients_followup_log')
    ->leftJoin('company_info', 'company_info.comp_id', 'clients_followup_log.comp_id')
    ->leftJoin('guest_user_details', 'guest_user_details.id', 'clients_followup_log.client_id')
    ->select(
        'clients_followup_log.comp_id',
        'clients_followup_log.client_id',
        'clients_followup_log.created_by',
        'guest_user_details.name as guest_client_name',
        'guest_user_details.business_name as guest_client_business',
        DB::raw('MAX(clients_followup_log.created_date) as last_followup_date'),
        DB::raw('MAX(clients_followup_log.client_type) as client_type'),
        DB::raw('MAX(clients_followup_log.next_followup_date) as next_followup_date'),
        DB::raw('COUNT(clients_followup_log.next_followup_date) as next_followup_count'), 
        DB::raw('MAX(clients_followup_log.product_id) as product_id'),
        DB::raw('MAX(clients_followup_log.category_id) as category_id'),
        DB::raw('MAX(clients_followup_log.service_id) as service_id'),
        'company_info.client_name',
        'company_info.business_name as company_business_name',
        'guest_user_details.group_id as guest_group_id', 
        'company_info.group_id as company_group_id'
    )
    ->whereIn('clients_followup_log.created_by', $emp_managers)
    ->groupBy(
        'clients_followup_log.comp_id',
        'clients_followup_log.client_id',
        'clients_followup_log.created_by',
        'guest_user_details.name',
        'guest_user_details.business_name',
        'company_info.client_name',
        'company_info.business_name',
        'guest_user_details.group_id',
        'company_info.group_id'
    )
    ->having('next_followup_count', '>',1);

    // Apply filters
    if ($request->group) {
    $data->where(function ($query) use ($request) {
        $query->where(function ($subQuery) use ($request) {
            $subQuery->where('clients_followup_log.client_type', 1)
                ->where('guest_user_details.group_id', $request->group);
        })->orWhere(function ($subQuery) use ($request) {
            $subQuery->where('clients_followup_log.client_type', 2)
                ->where('company_info.group_id', $request->group);
        });
    });

    $total_call_connected->where(function ($query) use ($request) {
        $query->where(function ($subQuery) use ($request) {
            $subQuery->where('clients_followup_log.client_type', 1)
                ->where('guest_user_details.group_id', $request->group);
        })->orWhere(function ($subQuery) use ($request) {
            $subQuery->where('clients_followup_log.client_type', 2)
                ->where('company_info.group_id', $request->group);
        });
    });

    $recent_inactive->whereRaw('FIND_IN_SET(?, package_info.group_id)', [$request->group]);

    // Apply the same group filters to the $carray_forward query
    $carray_forward->where(function ($query) use ($request) {
        $query->where(function ($subQuery) use ($request) {
            $subQuery->where('clients_followup_log.client_type', 1)
                ->where('guest_user_details.group_id', $request->group);
        })->orWhere(function ($subQuery) use ($request) {
            $subQuery->where('clients_followup_log.client_type', 2)
                ->where('company_info.group_id', $request->group);
        });
    });

    $due_amount->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);
    $total_upcoming_renewal->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);



}


    if ($request->product) {
        $data->where('clients_followup_log.product_id', $request->product);
        $carray_forward->where('clients_followup_log.product_id', $request->product);
        $due_amount->where('product_id', $request->product);
        $total_upcoming_renewal->where('product_id',$request->product);
        $total_call_connected->where('clients_followup_log.product_id', $request->product);
        $recent_inactive->where('package_info.product_id', $request->product);
    }
    if ($request->service) {
        $recent_inactive->whereRaw('FIND_IN_SET(?, package_info.service_id)', [$request->service]);
        $data->where('clients_followup_log.service_id', $request->service);
        $carray_forward->where('clients_followup_log.service_id', $request->service);
        $due_amount->whereRaw('FIND_IN_SET(?, service_id)', [$request->service]);
        $total_upcoming_renewal->whereRaw('FIND_IN_SET(?, service_id)', [$request->service]);
        $total_call_connected->where('clients_followup_log.service_id', $request->service);
    }
    if ($request->category) {
        $recent_inactive->whereRaw('FIND_IN_SET(?, package_info.category_id)', [$request->category]);
        $data->where('clients_followup_log.category_id', $request->category);
        $carray_forward->where('clients_followup_log.category_id', $request->category);
        $due_amount->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);
        $total_upcoming_renewal->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);
        $total_call_connected->where('clients_followup_log.category_id', $request->category);
    }
    if ($request->start_date && $request->end_date) {
        $data->whereDate('clients_followup_log.next_followup_date', '>=', $request->start_date)
            ->whereDate('clients_followup_log.next_followup_date', '<=', $request->end_date);
        $carray_forward->whereDate('clients_followup_log.created_date', '>=', $request->start_date)
            ->whereDate('clients_followup_log.created_date', '<=', $request->end_date);
         $due_amount->whereDate('created_date', '>=', $request->start_date)
             ->whereDate('created_date', '<=', $request->end_date);
        $total_upcoming_renewal->whereDate('package_end_expected_date', '>=',$request->start_date)
             ->whereDate('package_end_expected_date', '<=', $request->end_date);
        $total_call_connected->whereDate('clients_followup_log.created_date', '>=', $request->start_date)
        ->whereDate('clients_followup_log.created_date', '<=', $request->end_date);
    } else {
        $data->whereDate('clients_followup_log.next_followup_date', Carbon::now()->format('Y-m-d'));
        $carray_forward->whereMonth('clients_followup_log.created_date', Carbon::now()->month);

        $total_upcoming_renewal->whereMonth('package_end_expected_date', Carbon::now()->month);

        $due_amount->where('due_date', '>=', Carbon::now()->subDays(40));

        $total_call_connected->whereDate('clients_followup_log.created_date', Carbon::now()->format('Y-m-d'));
          //->whereYear('package_end_expected_date', Carbon::now()->year);
    }

    $followup_for_meeting = $data->clone()->where('followup_id', 7)->count();
    $followup_for_payment = $data->clone()->whereIn('followup_id', [6, 9])->count();
    $business_proposal = $data->clone()->where('followup_id', 5)->count();
    $call_connected =  $total_call_connected->clone()->where('clients_followup_log.disposition', 'connected')->whereNotIn('clients_followup_log.followup_id',[24,25])->count();
    $mature_followup = $data->clone()->where('refer_package_id','!=',' ')->count();
    $dead_followup = $data->clone()->where('followup_id', 15)->count();

   $carry_forward_count = $carray_forward->groupBy('clients_followup_log.comp_id', 'clients_followup_log.client_id', 'clients_followup_log.created_by')->count();

   //$unique_group_count = $carry_forward_count->count();
//     $client_count = 0;

//    foreach ($carry_forward_count as $row) {
//         $no_of_times_carry_forward = DB::connection('sales_db')->table('clients_followup_log')
//             ->where('client_id', $row->client_id)
//             ->where('comp_id', $row->comp_id)
//             ->where('created_by', $row->created_by)
//             ->where('next_followup_date', '!=', '')
//             ->count();

//         $no_of_times_carry_forward = max(0, $no_of_times_carry_forward - 1);
//         if ($no_of_times_carry_forward > 0) {
//             $client_count++;
//          }
//         }

   $renewal_data  =  $total_upcoming_renewal
   ->whereIN('exe_id',$emp_managers)
   ->count();

  $renewal_amount  =  $total_upcoming_renewal
   ->whereIN('exe_id',$emp_managers)
   ->sum('package_amount');


    // Calculate accuracy
    if ($call_connected > 0) {
        $accuracy = round(($mature_followup / $call_connected) * 100);
        $target_matched = $accuracy >= $required_percent->child_attribute_value ? 'Yes' : 'No';
    } else {
        $accuracy = 0;
        $target_matched = 'No';
    }

    $total_data_array = [
        'meeting' => $followup_for_meeting,
        'followup_for_payment' => $followup_for_payment,
        'proposal' => $business_proposal,
        'connected_call' => $call_connected,
        'mature' => $mature_followup,
        'dead' => $dead_followup,
        'accuracy' => $accuracy,
        'target_match' => $target_matched,
        'carry_forward'=>$carry_forward_count,
        'due_amount'=>$due_amount->whereIn('exe_id',$emp_managers)->sum('due_amount'),
        'renewal_data'=>$renewal_data,
        'renewal_amount'=>$renewal_amount,
        'recent_inactive'=>$recent_inactive->count(),
    ];

    return response()->json(['status' => 200, 'data' => $total_data_array]);
}




public function get_followup_status_details(Request $request)
{
    $emp_managers = [];
    
    // Determine employee managers based on request parameters
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();
        
        $emp_managers[] = $request->manager;
    } elseif ($request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();
        
        $emp_managers[] = $request->emp_id;
    }

    $emp_managers = array_unique($emp_managers);

    // Base query for followup logs
    $dataQuery = DB::connection('sales_db')->table('clients_followup_log')
        ->select('product_id', 'service_id', 'followup_id', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as total_count'))
        ->groupBy('product_id', 'service_id', 'followup_id')
        ->where('amount', '!=', '');

    // Base queries for total counts
    $totalMatureQuery = DB::connection('sales_db')->table('clients_followup_log')->where('followup_id', 19);
    $totalDeadQuery = DB::connection('sales_db')->table('clients_followup_log')->where('followup_id', 15);

    // Apply filters based on request parameters
    if ($request->product) {
        $dataQuery->where('product_id', $request->product);
        $totalMatureQuery->where('product_id', $request->product);
        $totalDeadQuery->where('product_id', $request->product);
    }

    if ($request->service) {
        $dataQuery->where('service_id', $request->service);
        $totalMatureQuery->where('service_id', $request->service);
        $totalDeadQuery->where('service_id', $request->service);
    }

    if ($request->start_date && $request->end_date) {
        $dataQuery->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $totalMatureQuery->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $totalDeadQuery->whereBetween('created_date', [$request->start_date, $request->end_date]);
    } else {
        $dataQuery->whereMonth('created_date', Carbon::now()->month);
        $totalMatureQuery->whereMonth('created_date', Carbon::now()->month);
        $totalDeadQuery->whereMonth('created_date', Carbon::now()->month);
    }

    if (!empty($emp_managers)) {
        $dataQuery->whereIn('created_by', $emp_managers);

        $queryResults = $dataQuery->get();
        
        $totalMatureCount = $totalMatureQuery->count();
        $totalDeadCount = $totalDeadQuery->count();

        $dataArray = $queryResults->map(function ($row) use ($totalMatureCount, $totalDeadCount) {
            $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first(['product_name']);
            $service = DB::connection('sales_db')->table('product_service')->where('id', $row->service_id)->first(['service_name']);
            $status = DB::connection('sales_db')->table('followup_status')->where('id', $row->followup_id)->first(['activity_name']);

            $totalCount = $row->total_count;
            $wonPercent = $totalCount > 0 ? ($totalMatureCount / $totalCount) * 100 : 0;
            $lossPercent = $totalCount > 0 ? ($totalDeadCount / $totalCount) * 100 : 0;

            return [
                'product_id' => $row->product_id,
                'product' => $product->product_name ?? '',
                'service' => $service->service_name ?? '',
                'status' => $status->activity_name ?? '',
                'amount' => $row->total_amount,
                'total_no' => $row->total_count,
                'won' => $wonPercent,
                'loss' => $lossPercent,
                'accuracy' => $wonPercent,
            ];
        });

        return response()->json(['status' => 200, 'data' => $dataArray]);
    } else {
        return response()->json(['status' => 400, 'message' => 'No employees found.']);
    }
}


public function get_sales_team_details(Request $request) {
    $data_array = [];
    $data = DB::connection('sales_db')->table('sales_funnel')
        ->select('product_id', 'created_by')
        ->groupBy('product_id', 'created_by')
        ->orWhereRaw('FIND_IN_SET(?, reporting_manager)', [$request->emp_id])
        ->orWhere('created_by', $request->emp_id);

    if ($request->product) {
        $data->where('product_id', $request->product);
    }

    if ($request->start_date && $request->end_date) {
        $data->whereBetween('created_date', [$request->start_date, $request->end_date]);
    } else {
        $data->whereMonth('created_date', Carbon::now()->month);
    }

    $records = $data->get();

    foreach ($records as $row) {
        $emp_name = BasicInfo::where('emp_id', $row->created_by)->first();
        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();

        $total_followup = DB::connection('sales_db')->table('sales_funnel')->where('product_id',$row->product_id)->where('created_by', $row->created_by)->sum('no_of_task');
        $total_amount = DB::connection('sales_db')->table('sales_funnel')->where('product_id',$row->product_id)->where('created_by', $row->created_by)->sum('amount');
        $total_won = DB::connection('sales_db')->table('sales_funnel')->where('product_id',$row->product_id)->where('created_by', $row->created_by)->where('task_id', 19)->sum('no_of_task');
        $total_loss = DB::connection('sales_db')->table('sales_funnel')->where('product_id',$row->product_id)->where('created_by', $row->created_by)->where('task_id', 15)->sum('no_of_task');

        $forecast_accuracy = round($total_followup ? ($total_won / $total_followup) * 100 : 0);

        $data_array[] = [
            'emp_name' => $emp_name ? $emp_name->emp_fname . ' ' . $emp_name->emp_lame : 'Unknown',
            'total_followup' => $total_followup,
            'total_amount' => $total_amount,
            'won' => $total_won,
            'loss' => $total_loss,
            'accuracy' => $forecast_accuracy,
            'product' => $product ? $product->product_name : 'Unknown'
        ];
    }

    return response()->json(['status' => 200, 'data' => $data_array]);
}
public function show_sales_managers(){
    $data = BasicInfo::where('desi_id',38)->where('emp_status',1)->get(['emp_id','emp_fname','emp_lame']);
    return response()->json(['status'=>200,'message'=>'Managers List','data'=>$data]);
}


public function sales_dashboard_data(Request $request) {
    $regular_client_dead = 0;

    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager])
                      ->orWhere('dept_manager', $request->manager);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }
            $emp_groups = DB::table('emp_basic_info')->where('emp_id', $request->emp_id)->first();
            if($emp_groups){
                $assigned_group = $emp_groups->assigned_group;
                $group_id_details = $assigned_group ? explode(',', $assigned_group) : [];
            if($group_id_details){
                $group_ids = $group_id_details;
            }

            }
            else{
                $group_ids = [];

            }

            $due_amount = DB::connection('sales_db')
            ->table('package_info')
            ->where('due_amount', '>', 0)
            ->where('due_date', '!=', '');


             $not_sent_in_groups = DB::connection('sales_db')->table('enquiry_info')
             ->where('enq_status', 1)
              ->where('sent_count', '<', 4);

               $extra_sent_in_groups = DB::connection('sales_db')->table('package_info')
               ->where('package_duration', 2)
               ->where('sent_lead', '>', 'total_lead');

               $not_sent_in_category = DB::connection('sales_db')->table('enquiry_info')
               ->where('enq_status', 1)
               ->where('sent_count', '<', 4);

              $extra_sent_in_category = DB::connection('sales_db')->table('service_info')
              ->join('package_info', 'package_info.package_id', '=', 'service_info.package_id')
               ->where('package_info.package_duration', 2)
               ->where('service_info.sent_lead', '>', 'service_info.total_lead');

            $collection_query = DB::connection('sales_db')->table('payment_history')
             ->leftJoin('company_info','company_info.comp_id','payment_history.comp_id')
             ->leftJoin('group_names','group_names.group_id','company_info.group_id')
             ->select('payment_history.*','company_info.comp_id','company_info.group_id','group_names.group_id');

            $active_clients = DB::connection('sales_db')->table('company_info')->where('status',2);
            $active_packages =  DB::connection('sales_db')->table('package_info')->where('package_status',1);

            $followups_data = DB::connection('sales_db')->table('clients_followup_log')->leftJoin('company_info','company_info.comp_id','clients_followup_log.comp_id')
                ->leftJoin('group_names','group_names.group_id','company_info.group_id');
             $today_followup = DB::connection('sales_db')->table('clients_followup_log')->leftJoin('company_info','company_info.comp_id','clients_followup_log.comp_id')
                ->leftJoin('group_names','group_names.group_id','company_info.group_id');

            $today_collection =  DB::connection('sales_db')->table('payment_history')
             ->leftJoin('company_info','company_info.comp_id','payment_history.comp_id')
             ->leftJoin('group_names','group_names.group_id','company_info.group_id')
             ->select('payment_history.*','company_info.comp_id','company_info.group_id','group_names.group_id');

             $lead_sale = DB::connection('sales_db')->table('service_info')
              ->join('package_info', 'service_info.package_id', '=', 'package_info.package_id')
               ->join('group_names', 'group_names.group_id', '=', 'package_info.group_id')
              ->join('leads_info', 'leads_info.package_id', '=', 'service_info.package_id')
              ->join('product_category', 'product_category.id', '=', 'leads_info.category_id')
               ->where('leads_info.status', 1);


       $lead_sale_price = DB::connection('sales_db')->table('service_info')
        ->join('package_info', 'service_info.package_id', '=', 'package_info.package_id')
        ->join('group_names', 'group_names.group_id', '=', 'package_info.group_id')
        ->join('leads_info', 'leads_info.package_id', '=', 'service_info.package_id')
        ->join('product_category', 'product_category.id', '=', 'leads_info.category_id')
        ->where('leads_info.status', 1);


           $missing_followup = DB::connection('sales_db')->table('missing_followups_count')->where('is_followup_taken',0)->whereIn('created_by',$emp_managers)->count();

           $buffer_amount = DB::connection('sales_db')->table('wallet_info');



            $package_details = DB::connection('sales_db')->table('package_info');
            $total_upcoming_renewal  = DB::connection('sales_db')->table('package_info');

           $client_not_found = DB::connection('sales_db')->table('enquiry_info')->whereIn('followup_status',[22,23])->whereIn('group_id',$group_ids);
            $total_business_lead = DB::connection('sales_db')
            ->table('guest_user_details')
            ->where('status', 0)
            ->where(function ($query) use ($group_ids, $request) {
                $query->whereIn('group_id', $group_ids)
                      ->orWhere('created_by', $request->emp_id);
            });




      if($request->product){
        $client_not_found->where('product_id',$request->product);
        $lead_sale->where('package_info.product_id', $request->product);
        $collection_query->where('payment_history.product_id',$request->product);
        $active_clients->where('product_id',$request->product);
        $active_packages->where('product_id',$request->product);
        $followups_data->where('clients_followup_log.product_id',$request->product);
        $today_followup->where('clients_followup_log.product_id',$request->product);
        $today_collection->where('payment_history.product_id',$request->product);
        $package_details->where('product_id',$request->product);
        $total_upcoming_renewal->where('product_id',$request->product);
        $not_sent_in_groups->where('enquiry_info.product_id', $request->product);
        $extra_sent_in_groups->where('package_info.product_id', $request->product);
        $lead_sale_price->where('package_info.product_id', $request->product);
        $total_business_lead->where('product_id',$request->product);
        $due_amount->where('product_id', $request->product);


        
      }
      if($request->group){
         $due_amount->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);
         $client_not_found->where('group_id',$request->group);
         $lead_sale_price->where('package_info.group_id', $request->group);
         $lead_sale->where('package_info.group_id', $request->group);
         $total_upcoming_renewal->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);
         $package_details->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);
        $collection_query->where('company_info.group_id',$request->group);
        $active_clients->where('group_id',$request->group);
        $active_packages->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);
        $followups_data->where('company_info.group_id',$request->group);
        $today_followup->where('company_info.group_id',$request->group);
        $today_collection->where('company_info.group_id',$request->group);
        $not_sent_in_groups->where('enquiry_info.group_id', $request->group);
        $extra_sent_in_groups->where('package_info.group_id', $request->group);
        $total_business_lead->where('group_id',$request->group);


      }

      if($request->service){
         $client_not_found->where('service_id',$request->service);
         $lead_sale_price->where('package_info.service_id', $request->service);
         $lead_sale->where('package_info.service_id', $request->service);
        $package_details->whereRaw('FIND_IN_SET(?, service_id)', [$request->service]);
        $collection_query->where('payment_history.service_id',$request->service);
        $active_packages->where('service_id',$request->service);
        $followups_data->where('clients_followup_log.service_id',$request->service);
        $today_followup->where('clients_followup_log.service_id',$request->service);
        $today_collection->where('payment_history.service_id',$request->service);
        $total_upcoming_renewal->whereRaw('FIND_IN_SET(?, service_id)', [$request->service]);
        $total_business_lead->where('service_id',$request->service);
        $due_amount->whereRaw('FIND_IN_SET(?, service_id)', [$request->service]);

        
      }
      if($request->category){
         $client_not_found->where('category_id',$request->category);
         $lead_sale_price->where('service_info.category_id', $request->category);
         $total_upcoming_renewal->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);

        $package_details->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);
        $active_packages->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);
        $followups_data->where('clients_followup_log.category_id',$request->category);
        $today_followup->where('clients_followup_log.category_id',$request->category);
        $lead_sale->where('service_info.category_id', $request->category);
        $not_sent_in_category->where('category_id', $request->category);
        $extra_sent_in_category->where('service_info.category_id', $request->category);
        $total_business_lead->where('category',$request->category);
        $due_amount->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);

      }

      if($request->start_date && $request->end_date){
         $due_amount->whereDate('created_date', '>=', $request->start_date)
             ->whereDate('created_date', '<=', $request->end_date);

        $collection_query->whereDate('payment_history.created_date', '>=',$request->start_date)
        ->whereDate('payment_history.created_date', '<=', $request->end_date);

        $active_clients->whereDate('created_at', '>=',$request->start_date)
        ->whereDate('created_at', '<=', $request->end_date);

        $active_packages->whereDate('created_date', '>=',$request->start_date)
        ->whereDate('created_date', '<=', $request->end_date);

         $followups_data->whereDate('created_date', '>=',$request->start_date)
        ->whereDate('created_date', '<=', $request->end_date);

         $today_followup->whereDate('next_followup_date', '>=',$request->start_date)
        ->whereDate('next_followup_date', '<=', $request->end_date);

         $today_collection->whereDate('payment_history.created_date', '>=',$request->start_date)
        ->whereDate('payment_history.created_date', '<=', $request->end_date);

        $package_details->whereDate('created_date', '>=',$request->start_date)
                ->whereDate('created_date', '<=', $request->end_date);

        $total_upcoming_renewal->whereDate('package_end_expected_date', '>=',$request->start_date)
                ->whereDate('package_end_expected_date', '<=', $request->end_date);
        $lead_sale->whereDate('leads_info.sent_date', '>=', $request->start_date)
             ->whereDate('leads_info.sent_date', '<=', $request->end_date);

        $not_sent_in_groups->whereDate('created_date', '>=', $request->start_date)
             ->whereDate('created_date', '<=', $request->end_date);
        $extra_sent_in_groups->whereDate('created_date', '>=', $request->start_date)
                   ->whereDate('created_date', '<=', $request->end_date);
         $not_sent_in_category->whereDate('enquiry_info.created_date', '>=', $request->start_date)
             ->whereDate('enquiry_info.created_date', '<=', $request->end_date);
        $extra_sent_in_category->whereDate('service_info.created_date', '>=', $request->start_date)
            ->whereDate('service_info.created_date', '<=', $request->end_date);

         $lead_sale_price->whereDate('leads_info.sent_date', '>=', $request->start_date)
             ->whereDate('leads_info.sent_date', '<=', $request->end_date);

        $client_not_found->whereDate('created_date', '>=', $request->start_date)
             ->whereDate('created_date', '<=', $request->end_date);

        $total_business_lead->whereDate('created_at', '>=', $request->start_date)
             ->whereDate('created_at', '<=', $request->end_date);
        $buffer_amount->whereDate('created_date', '>=', $request->start_date)
             ->whereDate('created_date', '<=', $request->end_date);

      }
      else{
          $due_amount->where('due_date', '>=', Carbon::now()->subDays(40));

          $buffer_amount->whereMonth('created_date', Carbon::now()->month)
          ->whereYear('created_date', Carbon::now()->year);

          $collection_query->whereMonth('payment_history.created_date', Carbon::now()->month)
              ->whereYear('payment_history.created_date', Carbon::now()->year);

         $followups_data->whereMonth('clients_followup_log.created_date', Carbon::now()->month)
        ->whereYear('clients_followup_log.created_date', Carbon::now()->year);

        $today_followup->whereDate('clients_followup_log.next_followup_date', Carbon::now()->format('Y-m-d'));

         $today_collection->whereDate('payment_history.created_date', Carbon::now()->format('Y-m-d'));
         $package_details->whereMonth('created_date', Carbon::now()->month)->whereYear('created_date', Carbon::now()->year);
          $total_upcoming_renewal->whereMonth('package_end_expected_date', Carbon::now()->month)->whereYear('package_end_expected_date', Carbon::now()->year);
         $lead_sale->whereMonth('leads_info.sent_date', Carbon::now()->month);
         $not_sent_in_groups->whereMonth('created_date', Carbon::now()->month);
         $extra_sent_in_groups->whereMonth('created_date', Carbon::now()->month);
         $not_sent_in_category->whereMonth('enquiry_info.created_date', Carbon::now()->month);
         $extra_sent_in_category->whereMonth('service_info.created_date', Carbon::now()->month);
          $lead_sale_price->whereMonth('leads_info.sent_date', Carbon::now()->month);
          $client_not_found->whereDate('created_date', Carbon::now()->format('Y-m-d'));
          $total_business_lead->whereMonth('created_at', Carbon::now()->month)
                       ->whereYear('created_at', Carbon::now()->year);
              }

       $active_clients_counts = $active_clients->whereIn('exe_id', $emp_managers)->count();
       $active_packages_counts = $active_packages->whereIn('exe_id', $emp_managers)->count();
       $total_business_lead_count = $total_business_lead->count();

      $followup_count =  $followups_data->whereIn('clients_followup_log.created_by',$emp_managers)->whereNotIn('clients_followup_log.followup_id',[24,25])->count();

      $today_followup_sum = $today_followup->whereIn('followup_id',[6,9])->whereIn('clients_followup_log.created_by',$emp_managers)->count();

      $today_collection =  $today_collection->whereIn('payment_history.exe_id',$emp_managers)->sum('paid_amount');
      $total_due_amount = $due_amount->whereIn('exe_id',$emp_managers)->sum('due_amount');

      $total_mature_followup = $followups_data->where('clients_followup_log.refer_package_id','!=','')->whereIn('clients_followup_log.created_by',$emp_managers)->count();

      if($followup_count>0){
        $accuracy = round(($total_mature_followup/$followup_count)*100);
       }
       else{
         $accuracy = 0;
       }

        $renewal_data  =  $total_upcoming_renewal
                           ->whereIN('exe_id',$emp_managers)
                           ->count();

        $renewal_amount  =  $total_upcoming_renewal
                           ->whereIN('exe_id',$emp_managers)
                           ->sum('package_amount');

     $package_activation = $package_details->whereIn('exe_id', $emp_managers)
        ->where('package_type', '!=', 'float')
        ->where(function ($query) {
        $query->where(function ($query) {
            $query->where('admin_status', 0)
                  ->where('finance_status', 1);
        })
        ->orWhere(function ($query) {
            $query->where('admin_status', 1)
                  ->where('finance_status', 0);
        })
        ->orWhere(function ($query) {
            $query->where('admin_status', 0)
                  ->where('finance_status', 0);
        });
       })->count();

        $total_collection = clone $collection_query;
        $new_sale_query = clone $collection_query;
        $renew_sale_query = clone $collection_query;

        $total_collections =   $total_collection->whereIn('payment_history.exe_id',$emp_managers)->sum('paid_amount');

      $new_amount =   $new_sale_query->whereIn('payment_history.exe_id',$emp_managers)
      ->where('payment_for','New')->sum('paid_amount');

      $renew_amount =   $renew_sale_query->whereIn('payment_history.exe_id',$emp_managers)
      ->where('payment_for','Renew')->sum('paid_amount');

      $regular_client = DB::connection('sales_db')->table('company_info as ci')
       ->join('package_info as pi', function ($join) {
        $join->on('ci.client_id', '=', 'pi.client_id')
             ->on('ci.comp_id', '=', 'pi.comp_id');
       })
       ->select('ci.comp_id','ci.product_id','ci.client_id','ci.client_name','ci.business_name','ci.group_id','ci.exe_id','ci.created_at','ci.status', DB::raw('COUNT(pi.package_id) as package_count'))
    ->groupBy('ci.comp_id', 'ci.client_id')
    ->having('package_count', '>=', 5)
    ->whereIn('ci.status',[2,3])
    ->whereIn('ci.exe_id', $emp_managers)
    ->count();
  
      
       $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
       $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');
  
       $regular_client_dead = DB::connection('sales_db')
          ->table('package_info')
          ->select('client_id', 'comp_id', DB::raw('MAX(exe_id) as exe_id'), DB::raw('MAX(group_id) as group_id'), DB::raw('COUNT(*) as package_count'))
          ->whereNotIn('client_id', function ($query) use ($startOfMonth, $endOfMonth) {
              $query->select('client_id')
                    ->from('package_info')
                    ->whereBetween('created_date', [$startOfMonth, $endOfMonth])
                    ->groupBy('client_id');
          })
          ->groupBy('client_id', 'comp_id')
          ->havingRaw('COUNT(*) >= 5')
          ->whereIn('exe_id', $emp_managers)
          ->count();

          $buffer_amount_count = $buffer_amount->whereIn('created_by',$emp_managers)->sum('balance_amount');

          $total_lead_sale = $lead_sale->count();

          $check_today_missing_target = DB::connection('sales_db')
                                     ->table('kra_and_kpi_details')->whereIn('created_by',$emp_managers)->whereDate('created_date',Carbon::now()->format('Y-m-d'))->sum('remaining_followup');

          $check_extra_followup = DB::connection('sales_db')
                                     ->table('kra_and_kpi_details')->whereIn('created_by',$emp_managers)->whereDate('created_date',Carbon::now()->format('Y-m-d'))->sum('extra_followup');
          $missing_followup_sum =    DB::connection('sales_db')
                                     ->table('missing_followups_count')
                                     ->whereIn('created_by', $emp_managers)
                                     ->where('is_followup_taken',0)
                                     ->whereDate('missing_followup_date', Carbon::yesterday()->format('Y-m-d'))
                                    ->count();
             $groupedData = $not_sent_in_groups->select('enquiry_info.group_id',                        DB::raw('COUNT(*) as total_enquiries'), 
                                     DB::raw('SUM(sent_count) as total_sent_count'))
                                   ->leftJoin('group_names', 'enquiry_info.group_id', '=', 'group_names.group_id')
                                     ->whereIn('enquiry_info.group_id', $group_ids)
                                      ->get();

            $extra_sent_data_in_group = $extra_sent_in_groups->select('package_info.group_id', 
                                  DB::raw('SUM(total_lead) as total_lead'), 
                                  DB::raw('SUM(sent_lead) as sent_lead'))
        ->leftJoin('group_names', 'package_info.group_id', '=', 'group_names.group_id')
        ->whereIn('package_info.group_id', $group_ids)
        ->get();

         $extraSentMapping = [];
          foreach ($extra_sent_data_in_group as $extra) {
              $extraSentMapping[$extra->group_id] = $extra;
               }

           $groupLoss = [];
          foreach ($groupedData as $group) {
            $total_expected_sent_count = $group->total_enquiries * 4; 
            $total_diff_sent_count = $total_expected_sent_count - $group->total_sent_count;
            $not_sent = $total_expected_sent_count - $group->total_sent_count;
             $lead_difference = 0; 

        if (isset($extraSentMapping[$group->group_id])) {
            $total_lead = $extraSentMapping[$group->group_id]->total_lead ?? 0;
            $sent_lead = $extraSentMapping[$group->group_id]->sent_lead ?? 0;

            $lead_difference = $sent_lead - $total_lead;
            $total_diff_sent_count += $lead_difference; 
        }
         }

          $categorydata = $not_sent_in_category->select('enquiry_info.category_id', 'product_category.category_name',
                                  DB::raw('COUNT(*) as total_enquiries'), 
                                  DB::raw('SUM(sent_count) as total_sent_count'))
             ->leftJoin('product_category', 'enquiry_info.category_id', '=', 'product_category.id')
              ->get();

              $extra_sent_in_category_data = $extra_sent_in_category->select('service_info.category_id', 
                                  DB::raw('SUM(service_info.total_lead) as total_lead'), 
                                  DB::raw('SUM(service_info.sent_lead) as sent_lead'))
                ->leftJoin('product_category', 'service_info.category_id', '=', 'product_category.id')
              ->get();

         $extraSentMappingcategory = [];
         foreach ($extra_sent_in_category_data as $extra) {
             $extraSentMappingcategory[$extra->category_id] = $extra; 
           }
       $categoryloss = [];
       foreach ($categorydata as $cat) {
        $total_expected_sent_count = $cat->total_enquiries * 4; 
        $total_diff_sent_count_in_cat = $total_expected_sent_count - $cat->total_sent_count;
        $not_sent = $total_expected_sent_count - $cat->total_sent_count;

        $lead_difference_in_cat = 0; 

        if (isset($extraSentMappingcategory[$cat->category_id])) {
            $total_lead = $extraSentMappingcategory[$cat->category_id]->total_lead ?? 0;
            $sent_lead = $extraSentMappingcategory[$cat->category_id]->sent_lead ?? 0;

            $lead_difference_in_cat = $sent_lead - $total_lead; 
        }

        $total_diff_sent_count_in_cat += $lead_difference_in_cat; 
        }

        $lead_sale_price_query = $lead_sale_price->select(
            DB::raw('SUM(leads_info.lead_value) as total_sent_value'),
            DB::raw('COUNT(DISTINCT leads_info.package_id) as unique_package_count'),
            DB::raw('SUM(leads_info.lead_value) / NULLIF(COUNT(DISTINCT leads_info.package_id), 0) as average_value')
        )
        ->first();



         $data_list = array('renew' => $renewal_data,'renewal_amount'=>$renewal_amount,
                    'collection'=>$total_collections,'new_sale'=>$new_amount,'renew_sale'=>$renew_amount,
                    'package_activation'=>$package_activation,'due_amount'=>$total_due_amount,'no_of_followup'=>$followup_count,
                    'accuracy_percent'=>$accuracy,'missing_target'=>$check_today_missing_target,
                    'extra_followup'=>$check_extra_followup,'active_client'=>$regular_client,
                    'regular_client_dead'=>$regular_client_dead,
                    'today_collection'=>$today_collection,'followup_sum'=>$today_followup_sum,'total_active_clients'=> $active_clients->count(),'total_active_packages'=> $active_packages->count(),'lead_sale'=>$total_lead_sale,'group_loss'=>$total_diff_sent_count,'category_loss'=>$total_diff_sent_count_in_cat,'lead_sale_price'=>$lead_sale_price_query->average_value ?? 0,'client_not_found'=>$client_not_found->count(),'missing_followup'=>$missing_followup,'business_lead_count'=>$total_business_lead_count,'buffer_amount'=>$buffer_amount_count);

       return response()->json(['status'=>200,'data'=> $data_list,'message'=>'Sales Dashboard Data']);
}

public function sales_inner_page_description(Request $request)
{
    $data_array = [];
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager])
                      ->orWhere('dept_manager', $request->manager);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $query = DB::connection('sales_db')->table('payment_history')
        ->leftJoin('company_info', 'company_info.comp_id', 'payment_history.comp_id')
        ->leftJoin('group_names', 'group_names.group_id', 'company_info.group_id')
        ->leftJoin('product', 'product.id', 'payment_history.product_id')
        ->leftJoin('product_service', 'product_service.id', 'payment_history.service_id')
        ->leftjoin('package_info','package_info.package_id','payment_history.package_id')
        ->leftJoin('product_category', function($join) {
            $join->on(DB::raw("FIND_IN_SET(product_category.id, package_info.category_id)"), '>', DB::raw("0"));
         }) 
        ->select(
            'payment_history.*',
            'group_names.name as group_name',
            'product.product_name',
            'product_service.service_name',
            'company_info.client_name',
            'company_info.business_name',
           // 'package_info.service_id',
             DB::raw("GROUP_CONCAT(product_category.category_name SEPARATOR ', ') as category_names"),
        )
        ->groupBy('payment_history.pay_id');

    if ($request->type == 'DueAmount') {
        $query->where('payment_history.payment_for', 'Due Amount');
    }
    if ($request->type == 'NewRenew') {
        $query->where(function($query) {
            $query->where('payment_for', 'New')
                  ->orWhere('payment_for', 'ReNew');
        });
    }
    if ($request->product) {
        $query->where('payment_history.product_id', $request->product);
    }
    if ($request->service) {
        $query->where('payment_history.service_id', $request->service);
    }
    if ($request->category) {
        $query->whereRaw('FIND_IN_SET(?, package_info.category_id)', [$request->category]);
    }
    if ($request->group) {
        //$query->where('company_info.group_id', $request->group);
        $query->whereRaw('FIND_IN_SET(?, company_info.group_id)', [$request->group]);
    }
    if ($request->start_date && $request->end_date) {
        $query->whereDate('payment_history.created_date', '>=', $request->start_date)
              ->whereDate('payment_history.created_date', '<=', $request->end_date);
    } else {
        $query->whereMonth('payment_history.created_date', Carbon::now()->month)
               ->whereYear('payment_history.created_date', Carbon::now()->year);
    }

    if (!$request->download) {
        $paymentHistory = $query->whereIn('payment_history.exe_id', $emp_managers)->paginate(10);
        $employeeIds = $paymentHistory->pluck('exe_id')->unique();
        $employees = DB::table('emp_basic_info')
            ->whereIn('emp_id', $employeeIds)
            ->get()
            ->keyBy('emp_id');

        $managerIds = $employees->pluck('reporting_manager')->unique();
        $managers = DB::table('emp_basic_info')
            ->whereIn('emp_id', $managerIds)
            ->get()
            ->keyBy('emp_id');
        $paymentHistory->getCollection()->transform(function($item) use ($employees, $managers) {
            $employee = $employees->get($item->exe_id);
            $item->exe_first_name = $employee ? $employee->emp_fname : '';
            $item->exe_last_name = $employee ? $employee->emp_lame : '';

            $reportingManager = $managers->get($employee->reporting_manager);
            $item->reporting_manager_first_name = $reportingManager ? $reportingManager->emp_fname : '';
            $item->reporting_manager_last_name = $reportingManager ? $reportingManager->emp_lame : '';
            
            return $item;
        });

        return response()->json(['status' => 200, 'data' => $paymentHistory, 'last_page' => $paymentHistory->lastPage()]);
    } else {
        $filename = "sales_data_" . Carbon::now()->format('Ymd_His') . ".csv";
        $handle = fopen($filename, 'w+');
        fputcsv($handle, ['Client Name','Company Name', 'Amount', 'Product', 'Service', 'Employee Name', 'Manager', 'Payment For', 'Date', 'Group','Category']);
        $paymentHistory = $query->whereIn('payment_history.exe_id', $emp_managers)->get();

        $employeeIds = $paymentHistory->pluck('exe_id')->unique();
        $employees = DB::table('emp_basic_info')
            ->whereIn('emp_id', $employeeIds)
            ->get()
            ->keyBy('emp_id');
        $managerIds = $employees->pluck('reporting_manager')->unique();
        $managers = DB::table('emp_basic_info')
            ->whereIn('emp_id', $managerIds)
            ->get()
            ->keyBy('emp_id');

        foreach ($paymentHistory as $row) {
            $employee = $employees->get($row->exe_id);
            $row->exe_first_name = $employee ? $employee->emp_fname : 'Unknown';
            $row->exe_last_name = $employee ? $employee->emp_lame : 'Unknown';

            $reportingManager = $managers->get($employee->reporting_manager);
            $row->reporting_manager_first_name = $reportingManager ? $reportingManager->emp_fname : 'Unknown';
            $row->reporting_manager_last_name = $reportingManager ? $reportingManager->emp_lame : 'Unknown';

            fputcsv($handle, [
                $row->client_name,
                $row->business_name,
                $row->paid_amount,
                $row->product_name,
                $row->service_name,
                $row->exe_first_name . ' ' . $row->exe_last_name,
                $row->reporting_manager_first_name . ' ' . $row->reporting_manager_last_name,
                $row->payment_for,
                $row->created_date,
                $row->group_name,
                $row->category_names,
            ]);
        }

        fclose($handle);

        return response()->download($filename)->deleteFileAfterSend(true);
    }
}



public function sales_upcoming_renewal_details(Request $request) {
    $data_array = [];

    // Determine employee IDs based on request parameters
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager])
                      ->orWhere('dept_manager', $request->manager);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif ($request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $query = DB::connection('sales_db')->table('package_info');

    if ($request->type === 'upcomingRenewal') {
        $query->whereMonth('package_end_expected_date', Carbon::now()->month)
              ->whereYear('package_end_expected_date', Carbon::now()->year);

        if ($request->start_date && $request->end_date) {
            $query->whereDate('package_end_expected_date', '>=', $request->start_date)
                  ->whereDate('package_end_expected_date', '<=', $request->end_date);
        }
    } elseif ($request->type === 'PackagePendingActivation') {
        $query->where(function($query) {
            $query->where('finance_status', 0)
                  ->orWhere('admin_status', 0);
        });

        if ($request->start_date && $request->end_date) {
            $query->whereDate('created_date', '>=', $request->start_date)
                  ->whereDate('created_date', '<=', $request->end_date);
        } else {
            $query->whereMonth('created_date', Carbon::now()->month)
                  ->whereYear('created_date', Carbon::now()->year);
        }
          $query->where('package_type', '!=', 'float');
    }

    if ($request->product) {
        $query->where('product_id', $request->product);
    }

    if ($request->service) {
        $query->where('service_id', $request->service);
    }
    if ($request->category) {
        $query->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);
    }

    if ($request->group) {
        $query->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);
    }
   if($request->download){
        $data = $query->whereIn('exe_id',$emp_managers)->get();
    }
    else{
        $data = $query->whereIn('exe_id',$emp_managers)->paginate(10);
    }

    foreach ($data as $row) {
        $group_ids = explode(',', $row->group_id);
        $category_ids = explode(',',$row->category_id);

        $groups = DB::connection('sales_db')->table('group_names')
            ->whereIn('group_id', $group_ids)
            ->pluck('name')
            ->implode(', ');

        $product = DB::connection('sales_db')->table('product')
            ->where('id', $row->product_id)
            ->value('product_name');

        $service = DB::connection('sales_db')->table('product_service')
            ->where('id', $row->service_id)
            ->value('service_name');

        $category = DB::connection('sales_db')->table('product_category')
                    ->whereIn('id', $category_ids)
                    ->pluck('category_name')
                    ->implode(' ,');

        $emp_name = DB::table('emp_basic_info')
            ->where('emp_id', $row->exe_id)
            ->first();

        $client_details = DB::connection('sales_db')->table('company_info')->where('comp_id',$row->comp_id)->where('client_id',$row->client_id)->first();
            
        if($emp_name && $emp_name->reporting_manager!='null' && $emp_name->reporting_manager!=''){
            $manager_name = DB::table('emp_basic_info')
            ->where('emp_id', $emp_name->reporting_manager)
            ->first();
            if($manager_name){
                $manager_name = $manager_name->emp_fname.' '.$manager_name->emp_lame;
            }
            
            
        }
        else{
             $manager_name = '';
            
        }
        if($request->type === 'upcomingRenewal'){
            $date = $row->package_end_expected_date;
        }
        else{
            $date = $row->created_date;

        }
        $company_details = DB::connection('sales_db')->table('company_info')->where('comp_id',$row->comp_id)->first();
            

        $data_array[] = [
            'id' => $row->package_id,
            'group' => $groups ?? '',
            'product' => $product ?? '',
            'service' => $service ?? '',
            'category'=>$category??'',
            'client'=>$company_details->client_name??'',
            'business_name'=>$company_details->business_name ??'',
            'emp_name' => ($emp_name->emp_fname ?? '') . ' ' . ($emp_name->emp_lame ?? ''),
            'manager_name' => $manager_name,
            'amount' => $row->package_amount,
            'created_date' =>$date,
            'client_id'=>$row->client_id??" ",
            'remarks'=>$row->remarks??"",
        ];
    }

    if ($request->download) {
        $filename = "sales_data_" . Carbon::now()->format('Ymd_His') . ".csv";
        $handle = fopen($filename, 'w+');
        fputcsv($handle, ['ID', 'Group', 'Product', 'Service','category','Client','Business Name', 'Employee Name', 'Manager', 'Amount', 'Date']);

        foreach ($data_array as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);

        return response()->download($filename)->deleteFileAfterSend(true);
    }

    return response()->json(['status' => 200, 'data' => $data_array, 'last_page' => $data->lastPage()]);
}

public function sales_dashboard_show_manager_team($id){
    $get_team = [];
    $reporting_manager = 0;
    $is_reporting_manager = BasicInfo::where('reporting_manager', $id)->exists();
    $emp_details = BasicInfo::where('emp_id', $id)->first();
    if($is_reporting_manager){

    $reporting_manager = 1;
     $get_team = BasicInfo::where('reporting_manager',$emp_details->emp_id)->where('emp_status',1)->orWhere('emp_id',$id)->get(['emp_id','emp_fname','emp_lame']);

      }
     return response()->json(['status'=>200,'data'=>$get_team,'reporting_manager'=> $reporting_manager]);

}

public function count_sales_kra_and_kpi()
{
    $sales_team = BasicInfo::where('dept_id', 3)->where('emp_status', 1)->where('desi_id','!=',38)->pluck('emp_id');
    $currentMonth = date('n');
    $currentYear = date('Y');

    if ($currentMonth >= 4) {
        $financialYearStart = $currentYear;
        $financialYearEnd = $currentYear + 1;
    } else {
        $financialYearStart = $currentYear - 1;
        $financialYearEnd = $currentYear;
    }
    
    $financialYear = $financialYearStart . '-' . $financialYearEnd;

    $assign_target = DB::table('save_company_target')
        ->where('subattribute_id', 13)
        ->where('department_id', 3)
        ->where('financial_year', $financialYear)
        ->first();

    if ($assign_target) {
        $per_month_followup = $assign_target->child_attribute_value / 12;
        $per_day_followup = round($per_month_followup / 22);
    } else {
        // Handle case where no target is assigned
        $per_day_followup = 0;
    }


    foreach ($sales_team as $emp_id) {
        $achieved_target = DB::connection('sales_db')
            ->table('clients_followup_log')
            ->where('created_by', $emp_id)
            ->whereIn('followup_id',[6,9])
            ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
            ->count();

        $last_inserted_record = DB::connection('sales_db')
            ->table('kra_and_kpi_details')
            ->where('created_by', $emp_id)
            ->whereDate('created_date', '!=', Carbon::now()->format('Y-m-d'))
            ->orderBy('id', 'desc')
            ->first();

        if ($last_inserted_record) {
            $target_followup = $per_day_followup + $last_inserted_record->remaining_followup;
        } else {
            $target_followup = $per_day_followup;
        }

        if ($achieved_target > $target_followup) {
            $remaining_followup = 0;
            $extra_followup = $achieved_target - $target_followup;
        } else if ($achieved_target < $target_followup) {
            $remaining_followup = $target_followup - $achieved_target;
            $extra_followup = 0;
        } else {
            $remaining_followup = 0;
            $extra_followup = 0;
        }

        DB::connection('sales_db')->table('kra_and_kpi_details')
            ->updateOrInsert(
                [
                    'created_by' => $emp_id,
                    'created_date' => Carbon::now()->format('Y-m-d')
                ],
                [
                    'assigned_followup' => $target_followup,
                    'total_achieved_followup' => $achieved_target,
                    'remaining_followup' => $remaining_followup,
                    'extra_followup' => $extra_followup
                ]
            );
    }
}

public function get_total_collection(Request $request) {
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
                      ->orWhere('dept_manager', $request->emp_id);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $query = DB::connection('sales_db')->table('payment_history')->leftJoin('company_info','company_info.client_id','payment_history.client_id');

    if ($request->group) {
        $query->where('company_info.group_id',$request->group);

     }

    if ($request->product) {
        $query->where('payment_history.product_id', $request->product);
    }

    if ($request->service) {
        $query->where('payment_history.service_id', $request->service); 
    }

    if ($request->start_date && $request->end_date) {
        $query->whereDate('payment_history.created_date', '>=',$request->start_date)
                ->whereDate('payment_history.created_date', '<=',$request->end_date);
    } else {
        $query->whereYear('payment_history.created_date', Carbon::now()->year);
    }

    if (!empty($emp_managers)) {
        $query->whereIn('payment_history.created_by', $emp_managers);
    }

    $data = $query->select(
            DB::raw('YEAR(created_date) as year'),
            DB::raw('MONTH(created_date) as month'),
            DB::raw('SUM(paid_amount) as total_collection')
        )
        ->groupBy('year', 'month')
        ->get();

    $months = [
        1 => 'January', 2 => 'February', 3 => 'March',
        4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September',
        10 => 'October', 11 => 'November', 12 => 'December'
    ];

    $all_months = collect(range(1, 12))->map(function ($month) use ($months) {
        return [
            'year' => Carbon::now()->year,
            'month' => $months[$month],
            'total_collection' => 0
        ];
    });

    $result = $all_months->map(function ($monthData) use ($data, $months) {
        $monthDataFromDB = $data->firstWhere('month', array_search($monthData['month'], $months));
        if ($monthDataFromDB) {
            $monthData['total_collection'] = $monthDataFromDB->total_collection;
        }
        return $monthData;
    });

    return response()->json(['status'=>200,'data'=>$result]);
}

public function get_regular_client_details(Request $request){
    $client_data = [];
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
                      ->orWhere('dept_manager', $request->emp_id);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    if($request->type =='regularClient'){
     $clientsWithPackages = DB::connection('sales_db')
    ->table('package_info')
    ->select('client_id','comp_id',DB::raw('MAX(exe_id) as exe_id'),DB::raw('MAX(group_id) as group_id'), DB::raw('COUNT(*) as package_count'))
    ->groupBy('client_id','comp_id')
    ->having('package_count', '>=',5)
    ->whereIn('exe_id',$emp_managers)
    ->paginate(10);
    }
    if($request->type =='regularClientDead'){
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');


        $clientsWithPackages = DB::connection('sales_db')
        ->table('package_info')
        ->select('client_id', 'comp_id', DB::raw('MAX(exe_id) as exe_id'), DB::raw('MAX(group_id) as group_id'), DB::raw('COUNT(*) as package_count'))
        ->whereNotIn('client_id', function ($query) use ($startOfMonth, $endOfMonth) {
            $query->select('client_id')
                  ->from('package_info')
                  ->whereBetween('created_date', [$startOfMonth, $endOfMonth])
                  ->groupBy('client_id');
        })
        ->groupBy('client_id', 'comp_id')
        ->havingRaw('COUNT(*) >= 5')
        ->whereIn('exe_id', $emp_managers)
        ->paginate(10);
    }
    
    
    foreach($clientsWithPackages as $row){
        $client_details = DB::connection('sales_db')->table('company_info')->where('comp_id',$row->comp_id)->first();
        
        $client_last_package = DB::connection('sales_db')->table('package_info')->where('client_id',$row->client_id)
        ->where('comp_id',$row->comp_id)->orderBy('package_id','DESC')->first();
        
        $emp_name = DB::table('emp_basic_info')->where('emp_id',$row->exe_id)->first();
        
        $emp_manager =  DB::table('emp_basic_info')->where('emp_id',$emp_name->reporting_manager)->first();
        
       if($client_details && $client_details->group_id ){
            $group = DB::connection('sales_db')->table('group_names')->where('group_id',$client_details->group_id)->first();
            $group_name = $group->name;

        }
        else{
            $group_name = ' ';

        }
        $client_data[] = array(
         'client_id' => $row->client_id ?? '',
         'client_name' => $client_details->client_name ?? '',
         'company_name'=>$client_details->business_name??'',
         'group' => $group_name,
         'last_package_date' => $client_last_package->created_date ?? '',
         'exe_fname' => $emp_name->emp_fname ?? '',
         'exe_lname' => $emp_name->emp_lame ?? '', 
         'manager_fname' => $emp_manager->emp_fname ?? '',
         'manager_lname' => $emp_manager->emp_lame ?? '',
         'package_count' => $row->package_count ?? '',
         'created_date' => $client_details->created_at ?? ''
       );
      } 

         return response()->json(['status'=>200,'data'=>$client_data,'last_page'=>$clientsWithPackages->lastPage()]);
    
}
   
   public function show_kra_and_kpi_sales_dashboard(Request $request){
    $startDate = Carbon::now()->startOfMonth();
    
    $endDate = Carbon::now()->endOfMonth();
    $dates = [];
    $currentDate = $startDate;
    while ($currentDate <= $endDate) {
        if ($currentDate->dayOfWeek != Carbon::SATURDAY && $currentDate->dayOfWeek != Carbon::SUNDAY) {
            $dates[] = $currentDate->toDateString();
        }
        $currentDate->addDay();
    }

   // return $dates;
    
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager])
                      ->orWhere('dept_manager', $request->manager);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();


        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
        $get_selected_id = [$request->manager];
    //return $get_selected_id;
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
        $get_selected_id = [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
        $get_selected_id = [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
                      ->orWhere('dept_manager', $request->emp_id);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();


        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
        $get_selected_id = [$request->emp_id];
    }
            $currentMonth = date('n');
            $currentYear = date('Y');
            if ($currentMonth >= 4) {
                $financialYearStart = $currentYear;
                $financialYearEnd = $currentYear + 1;
            } else {
                $financialYearStart = $currentYear - 1;
                $financialYearEnd = $currentYear;
            }
        $financialYear = $financialYearStart . '-' . $financialYearEnd;
        $payment_for_followup =  DB::table('save_company_target')->where('subattribute_id',13)
        ->where('department_id',3)->where('financial_year',$financialYear)->first();
       if($payment_for_followup){
         $per_month_payment_followup = $payment_for_followup->child_attribute_value/12;
         $per_day_payment_followup = round($per_month_payment_followup/22);

       }
       $business_proposal =  DB::table('save_company_target')->where('subattribute_id',14)
       ->where('department_id',3)->where('financial_year',$financialYear)->first();
      if($business_proposal){
         $per_month_proposal =  $business_proposal->child_attribute_value/12;
         $per_day_proposal = round($per_month_proposal/22);

      }
      $followup_for_meeting =  DB::table('save_company_target')->where('subattribute_id',15)
      ->where('department_id',3)->where('financial_year',$financialYear)->first();
      if($followup_for_meeting){
        $per_month_followup_for_meeting =  $followup_for_meeting->child_attribute_value/12;
       $per_day_followup_for_meeting = round($per_month_followup_for_meeting/22);

      }
      $calls_required_data = DB::table('save_company_target')->where('subattribute_id',12)
      ->where('department_id',3)->where('financial_year',$financialYear)->first();
      if($calls_required_data){
         $per_month_call = $calls_required_data->child_attribute_value/12;
         $per_day_call = round($per_month_call/22);

      }
      //return $get_selected_id;
      //exit;

      $total_per_day_target = $per_day_call+$per_day_followup_for_meeting+$per_day_proposal+ $per_day_payment_followup;
      $team_member_count_query = DB::table('employee_managers')
      ->where(function($query) use ($get_selected_id) {
          foreach ($get_selected_id as $id) {
              $query->orWhereRaw("FIND_IN_SET(?, reporting_to)", [$id]);
          }
      })
      ->where('status', 1)
      ->where('dept_id', 3)
      ->where('designation_id', '!=', 38)
      ->select('emp_id')
      ->distinct()
      ->pluck('emp_id')
      ->toArray(); 

     // return $team_member_count_query;
    $get_emp_details = BasicInfo::whereIn('emp_id',$get_selected_id)->get();
    //return  $get_emp_details;
    foreach($get_emp_details as $details)
         if($details->dept_id ==3 && $details->designation_id !=38){
            $team_member_count_query[] = $details->emp_id;
          }

         $team_member_count = count($team_member_count_query);
        // return $team_member_count;
         
      if($team_member_count>0){
        $total_count_of_per_day_target = $team_member_count*$total_per_day_target;
      }
      else{
        $total_count_of_per_day_target =  $total_per_day_target;
       }

       $query = DB::connection('sales_db')->table('clients_followup_log');
       //$zero_days_query = DB::connection('sales_db')->table('package_info');

      if($request->product){
        $query->where('product_id',$request->product);
       }
      if($request->category){
        $query->where('service_id',$request->service);
      }
      if($request->start_date && $request->end_date){
        $query->whereBetween('created_date', [$request->start_date, $request->end_date]);
      }
      else{
        $query->whereDate('created_date',Carbon::now()->format('Y-m-d'));
       }
       //return  $total_count_of_per_day_target;
     if(!empty($emp_managers)){
        $total_payment_for_followup_acheived  = (clone $query)->whereIn('created_by',$emp_managers)->whereIn('followup_id',[6,9])->count();
        $total_proposal_acheived  = (clone $query)->whereIn('created_by',$emp_managers)->where('followup_id',5)->count();
        $total_meeting_fixed   =   (clone $query)->whereIn('created_by',$emp_managers)->where('followup_id',1)->count();
        $total_connected_call =   (clone $query)->whereIn('created_by',$emp_managers)->count();
     }

      $total_acheived_sum = $total_payment_for_followup_acheived + $total_proposal_acheived+$total_meeting_fixed+$total_connected_call;
      $total_percent = round($total_acheived_sum/$total_count_of_per_day_target*100);
      return response()->json(['status'=>200,'data'=>$total_percent]);
      
    }

    public function zero_days_payment(Request $request) {
       if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
                      ->orWhere('dept_manager', $request->emp_id);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
             ->where('designation_id','!=',38)
            ->pluck('emp_id')->toArray();
            if($request->emp_id!='RIMS1'){
                 $emp_managers[] = $request->emp_id;
                
            }
        $emp_managers = array_unique($emp_managers);
    }
        //return $emp_managers;
    
        $query = DB::connection('sales_db')->table('package_info');
    
        if ($request->group) {
           $query->whereRaw('FIND_IN_SET(?,group_id)', [$request->group]);
        }
    
        if ($request->product) {
            $query->where('product_id', $request->product);
        }
    
        if ($request->category) {
             $query->whereRaw('FIND_IN_SET(?,service)', [$request->service]);
        }
        if ($request->start_date && $request->end_date) {
            
            $query->whereDate('created_date', '>=',$request->start_date)
                ->whereDate('created_date', '<=',$request->end_date);
        } else {
            $query->whereMonth('created_date',Carbon::now()->month)
                   ->whereYear('created_date',Carbon::now()->year);
        }
    
        $dates = [];
    
        for ($date = Carbon::parse($request->start_date); $date->lte(Carbon::now()) && $date->lte($request->end_date); $date->addDay()) {
           
                $dates[] = $date->format('Y-m-d');
            
        }
    
        $missingDatesByManager = [];
        $allExistingDates = [];
    
        foreach ($emp_managers as $manager_id) {
            $existingDates = DB::connection('sales_db')
                ->table('package_info')
                ->where('exe_id', $manager_id)
                ->whereIn(DB::raw('DATE(created_date)'), $dates)
                ->whereDate('created_date', '<=', Carbon::now()->format('Y-m-d'))
                ->pluck(DB::raw('DATE(created_date)'))
                ->toArray();
            
            $existingDates = array_map(function($date) {
                return Carbon::parse($date)->format('Y-m-d');
            }, $existingDates);
    
            $allExistingDates = array_merge($allExistingDates, $existingDates);
            $emp_name = BasicInfo::where('emp_id',$manager_id)->first();
    
            $missingDates = array_diff($dates, $existingDates);
            foreach ($missingDates as $missingDate) {
                $missingDatesByManager[] = [
                    'created_by' => $emp_name->emp_fname.' '.$emp_name->emp_lame,
                    'missing_date' => $missingDate,
                ];
            }
        }
        $allExistingDates = array_unique($allExistingDates);
        $globalMissingDates = array_diff($dates, $allExistingDates);
        $globalMissingDaysCount = count($globalMissingDates);

        return response()->json(['status'=>200,'data'=>$missingDatesByManager,'days_count'=>$globalMissingDaysCount]);
    
    }

    public function show_business_lead_data($emp_id,Request $request) {
        $data_array = [];
    
        $assigned_groups = BasicInfo::where('emp_id', $emp_id)->pluck('assigned_group')->first();
        $group_ids = explode(',', $assigned_groups);
        $group_ids = array_map('trim', $group_ids);
    
       
        $get_data = DB::connection('sales_db')
            ->table('guest_user_details')
            ->where('status', 0)
            ->where(function ($query) use ($group_ids, $emp_id) {
                $query->whereIn('group_id', $group_ids)
                      ->orWhere('created_by', $emp_id);
            });
        if($request->group){
            $get_data->where('group_id',$request->group);


        }
        if($request->product){
             $get_data->where('product_id',$request->product);

        }
        if($request->service){
            $get_data->where('service_id',$request->service);

        }
        if($request->category){
             $get_data->where('category_id',$request->category);

        }
        if ($request->start_date && $request->end_date) {
            
            $get_data->whereDate('created_at', '>=',$request->start_date)
                ->whereDate('created_at', '<=',$request->end_date);
        } else {
            $get_data->whereMonth('created_at',Carbon::now()->month)
                   ->whereYear('created_at',Carbon::now()->year);
        }

        $query = $get_data->get();
    
        
        foreach ($query as $row) {
            
            $group = DB::connection('sales_db')
                ->table('group_names')
                ->where('group_id', $row->group_id)
                ->first();

            $category = DB::connection('sales_db')->table('product_category')->where('id',$row->category)->first();
    
            
            $check_sales_member_exists = DB::table('emp_basic_info')
                ->whereRaw("FIND_IN_SET(?, assigned_group)", [$row->group_id])
                ->where('emp_status', 1)
                ->where('dept_id', 3)
                ->exists();
    
            
            $is_exists = $check_sales_member_exists ? 'Yes' : 'No';
    
           
            $data_array[] = [
                'id' => $row->id,
                'group' => $group ? $group->name : null, 
                'business' => $row->business_name,
                 'category'=> $category->category_name??"",
                'exists' => $is_exists
            ];
        }
    
        return response()->json(['status' => 200, 'data' => $data_array]);
    }
    

    public function update_business_lead_data(Request $request) {
        $details = DB::connection('sales_db')
        ->table('guest_user_details')
        ->where('id', $request->id)
        ->first();
        
        $check_mobile_no =  DB::connection('sales_db')->table('guest_user_details')
        ->where('mobile_no',$details->mobile_no)
        ->where('id','!=',$request->id)->exists();
        if($check_mobile_no){
            DB::connection('sales_db')
            ->table('guest_user_details')
            ->where('id', $request->id)
            ->update(['status' => 1, 'created_by' => $request->emp_id]);
    
        if($details->service_id){
            $service = $details->service_id;

        }
        else{
            $service = 0;
        }
         if($details->category){
            $category = $details->category;

        }
        else{
            $category = 0;
        }
        if($check_mobile_no){
            $check_exists = 'Yes';
        }
        else{
            $check_exists = 'No';
           }
    
        $data_array = [
            'client_type' => 1,
            'client_id' => $request->id,
            'product_id' => $details->product_id,
            'service_id' => $service,
            'category_id' => $category,
            'disposition' => 'Connected',
            'followup_id' => 25,
            'call_type' => 1,
            'remark' => 'duplicate',
            'created_by' => $request->emp_id,
            'created_date' => now(),
        ];
    
        DB::connection('sales_db')->table('clients_followup_log')->insert($data_array);
        return response()->json(['status' => 200, 'data' => $details,'exists'=>$check_exists]);

        }
        else{
            DB::connection('sales_db')
            ->table('guest_user_details')
            ->where('id', $request->id)
            ->update(['status' => 1, 'created_by' => $request->emp_id]);
    
        $details = DB::connection('sales_db')
            ->table('guest_user_details')
            ->where('id', $request->id)
            ->first();
        if($details->service_id){
            $service = $details->service_id;

        }
        else{
            $service = 0;
        }
         if($details->category){
            $category = $details->category;

        }
        else{
            $category = 0;
        }
        if($check_mobile_no){
            $check_exists = 'Yes';
        }
        else{
            $check_exists = 'No';
           }
    
        $data_array = [
            'client_type' => 1,
            'client_id' => $request->id,
            'product_id' => $details->product_id,
            'service_id' => $service,
            'category_id' => $category,
            'disposition' => 'Connected',
            'followup_id' => 24,
            'call_type' => 1,
            'remark' => 'business lead accepted',
            'created_by' => $request->emp_id,
            'created_date' => now(),
        ];
    
        DB::connection('sales_db')->table('clients_followup_log')->insert($data_array);
        return response()->json(['status' => 200, 'data' => $details,'exists'=>$check_exists]);

        }
        
    }

    public function update_business_lead_followup_data(Request $request){
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
            //'created_by'=>$request->created_by,
        );

          DB::connection('sales_db')->table('guest_user_details')->where('id',$request->id)->update($guestArray);
          
        $followup_data =  array(
            'client_type'=>1,
            'client_id'=>$request->id,
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

        DB::connection('sales_db')->table('clients_followup_log')->where('id',$request->id)->insert($followup_data);
        return response()->json(['status'=>200,'message'=>'Data Updated Successfully']);
    }

  public function today_missing_followup() {
    $data_array = [];
    $data = DB::connection('sales_db')->table('clients_followup_log')
        ->where('next_followup_date', Carbon::now()->subDay()->format('Y-m-d'))
        ->get();
    
    foreach ($data as $row) {
        $is_followup_taken_or_not = DB::connection('sales_db')->table('clients_followup_log')
            ->where('client_id', $row->client_id)
            ->where('comp_id', $row->comp_id)
            ->whereDate('created_date', $row->next_followup_date)
            ->where('id', '>', $row->id)
            ->first();
        if($is_followup_taken_or_not){
            $is_taken = 1;

        }
        else{
             $is_taken = 0;

        }
        $data_array[] = [
            'client_id' => $row->client_id,
            'comp_id' => $row->comp_id,
            'client_type' => $row->client_type,
            'is_followup_taken' => $is_taken,
            'created_by' => $row->created_by,
            'missing_followup_date'=>$row->next_followup_date,
        ];
    }

    $previous_remaing_followup =  DB::connection('sales_db')->table('missing_followups_count')->whereDate('created_date','<',carbon::now()->format('Y-m-d'))->where('is_followup_taken',0)->get();
       foreach($previous_remaing_followup as $row){
          $record =  DB::connection('sales_db')->table('clients_followup_log')->where('client_id', $row->client_id)
            ->where('comp_id', $row->comp_id)
            ->whereDate('created_date',carbon::now()->format('Y-m-d'))->first();
         if($record){
             DB::connection('sales_db')->table('missing_followups_count')->where('id', $row->id)->update(['is_followup_taken'=>1]);
          }
    }

    if (!empty($data_array)) {
        DB::connection('sales_db')->table('missing_followups_count')->insert($data_array);
    }

}





// public function count_sales_followup(Request $request){
//     $data_array = [];
    
//     if ($request->manager && !$request->employee) {
//         $emp_managers = DB::table('employee_managers')
//             ->where(function($query) use ($request) {
//                 $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
//             })
//             ->where('status', 1)
//             ->where('dept_id', 3)
//             ->pluck('emp_id')->toArray();

//         $emp_managers[] = $request->manager;
//         $emp_managers = array_unique($emp_managers);
//     } elseif (!$request->manager && $request->employee) {
//         $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
//     } elseif ($request->manager && $request->employee) {
//         $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
//     } else {
//         $emp_managers = DB::table('employee_managers')
//             ->where(function($query) use ($request) {
//                 $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
//             })
//             ->where('status', 1)
//             ->where('dept_id', 3)
//             ->pluck('emp_id')->toArray();

//         $emp_managers[] = $request->emp_id;
//         $emp_managers = array_unique($emp_managers);
//     }

//     $data = DB::connection('sales_db')->table('missing_followups_count')->whereIn('created_by',$emp_managers);
//     if($request->start_date && $request->end_date){
//          $data->whereDate('missing_followup_date', '>=', $request->start_date)
//              ->whereDate('missing_followup_date', '<=', $request->end_date);

//     }
//     else{
//         $data->where('missing_followup_date', Carbon::now()->subDay()->format('Y-m-d'));

//     }

//     $query = $data->paginate(10);
//     foreach($query as $row){
//         if($row->client_type==1){
//             $client_name = DB::connection('sales_db')->table('guest_user_details')->where('id',$row->client_id)->first();
//             $comp_name = '';
//             if($client_name){
//                 $name = $client_name->name;

//             }
//             else{
//                 $name = '';

//             }
//         }
//         else{
//             $client_name = DB::connection('sales_db')->table('company_info')
//                            ->where('comp_id',$row->comp_id)->first();
//             if($client_name){
//                 $name = $client_name->client_name;
//                 $comp_name = $client_name->business_name;
//             }
//             else{
//                 $name = '';
//                 $comp_name = '';

//             }

//         }
//         if($row->is_followup_taken==0){
//             $followup_taken = 'No';

//         }
//         else{
//             $followup_taken = 'Yes';

//         }
//         $emp_details = DB::table('emp_basic_info')->where('emp_id',$row->created_by)->first();
//         $emp_manager_details = DB::table('emp_basic_info')->where('emp_id',$emp_details->reporting_manager)->first();
//         if($emp_manager_details){
//             $manager_name = $emp_manager_details->emp_fname.' '.$emp_manager_details->emp_lame;

//         }
//        else{
//         $manager_name = '';
//        }


//         $data_array[] = array('id'=>$row->id,'client_id'=>$row->client_id??'','client_type'=>$row->client_type??'',
//             'client_name'=> $name??'','comp_name'=>$comp_name??'',
//             'missing_followup_date'=>$row->missing_followup_date,'emp_remark'=>$row->emp_remark,
//             'superadmin_remark'=>$row->superadmin_remark,
//             'manager_remark'=>$row->emp_remark,
//             'created_date'=>Carbon::parse($row->created_date)->format('Y-m-d'),'is_followup_taken'=>$followup_taken,
//             'emp_name'=>$emp_details->emp_fname.' '.$emp_details->emp_lame,'manager_details'=>$manager_name);
//          }
//          $total_missing_kra_kpis = DB::connection('sales_db')->table('kra_and_kpi_details')->whereIn('created_by',$emp_managers)->where('created_date',Carbon::now()->format('Y-m-d'))->sum('remaining_followup');

//         $total_missing_followups = DB::connection('sales_db')->table('missing_followups_count')->whereIn('created_by',$emp_managers)->where('is_followup_taken',0)->count();

//          return response()->json(['status'=>200,'data'=>$data_array,'last_page'=>$query->lastPage(),'missing_kra_kpi'=>$total_missing_kra_kpis,'missing_followup'=>$total_missing_followups]);

// }

public function send_sales_notification($emp_id){

    $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($emp_id) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $emp_id;
        $emp_managers = array_unique($emp_managers);

        //return $emp_managers;

    if(!empty($emp_managers)){
    $data = DB::connection('sales_db')->table('missing_followups_count')
    ->whereIn('created_by', $emp_managers)
    ->whereDate('missing_followup_date', Carbon::now()->subDay()->format('Y-m-d'))
    ->where('is_followup_taken',0)
    ->count();

    if($data>0){
        $type = 'missing_followup';
        $notification = 'Click To View Missing Followup';
        $data_array = array('emp_id'=>$emp_id,'type'=> $type,'notification'=>$notification);
        $get_last_record = DB::table('emp_notifications')->where('type','missing_followup')->where('emp_id',$emp_id)->whereDate('created_date',now()->format('Y-m-d'))->exists();
        if(!$get_last_record){
              DB::table('emp_notifications')->insert($data_array);

        }
      }
    $kra_kp_data = DB::connection('sales_db')->table('kra_and_kpi_details')->whereIn('created_by',$emp_managers) ->whereDate('created_date', Carbon::now()->subDay()->format('Y-m-d'))->sum('remaining_followup');

    if($kra_kp_data>0){
         $type = 'missing_kra_kpi';
         $notification = 'Click To View  Missing Kra Kpi';
        $data_array = array('emp_id'=>$emp_id,'type'=> $type,'notification'=>$notification);
         $get_last_record = DB::table('emp_notifications')->where('type','missing_kra_kpi')->where('emp_id',$emp_id)->whereDate('created_date',now()->format('Y-m-d'))->exists();
         if(!$get_last_record){
            DB::table('emp_notifications')->insert($data_array);

         }
      }
   }
}

public function missing_kra_kpi_history(Request $request) {

    $emp_managers = DB::table('employee_managers')
        ->where(function($query) use ($request) {
            $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
                  ->orWhere('dept_manager', $request->emp_id);
        })
        ->where('status', 1)
        ->where('dept_id', 3)
        ->pluck('emp_id')
        ->toArray();


    $emp_managers[] = $request->emp_id;
    $emp_managers = array_unique($emp_managers);

   
    $kra_kpi_history = DB::connection('sales_db')
        ->table('kra_and_kpi_details')
        ->whereIn('created_by', $emp_managers);
       
    if($request->start_date && $request->end_date){
         $kra_kpi_history->whereDate('created_date', '>=',$request->start_date)
                ->whereDate('created_date', '<=',$request->end_date);
        
    }
    else{
        $kra_kpi_history->whereDate('created_date', Carbon::now());
        
    }
    
    $data = $kra_kpi_history->paginate(10);

    
    $details_array = [];

    
    foreach ($data as $row) {
        $emp_name = BasicInfo::where('emp_id', $row->created_by)->first();
        $manager = BasicInfo::where('emp_id', $emp_name->reporting_manager)->first();

            $details_array[] = [
                'id' => $row->id,
                'emp_name' => $emp_name->emp_fname.' '.$emp_name->emp_lame,
                'manager' => $manager->emp_fname.' '.$manager->emp_lame,
                'count' => $row->remaining_followup,
                'date' => Carbon::parse($row->created_date)->format('Y-m-d'),
                'emp_remark'=>$row->emp_remark,
                'manager_remark'=>$row->manager_remark,
                'superadmin_remark'=>$row->superadmin_remark,
            ];
        }

   
    return response()->json(['status' => 200, 'data' => $details_array,'last_page'=>$data->lastPage()]);
}

public function update_missing_followup_seen_status($emp_id){
   DB::table('emp_notifications')->where('emp_id',$emp_id)->where('type','missing_followup')->update(['seen_status'=>1]);
   return response()->json(['status'=>200]);
}

public function assigned_business_lead_to_manager(Request $request){
    DB::connection('sales_db')->table('guest_user_details')->where('id',$request->id)->update(['created_by'=>$request->manager_id]);
    $get_client_data = DB::connection('sales_db')->table('guest_user_details')->where('id',$request->id)->first();
    if($get_client_data->category){
        $category = $get_client_data->category;

    }
    else{
        $category = 0;

    }

    if($get_client_data->service_id){
        $service = $get_client_data->service_id;

    }
    else{
       $service = 0;

    }
    $data = array('client_type'=>1,'client_id'=>$get_client_data->id,
    'product_id'=> $get_client_data->product_id,'category_id'=>$category,
    'service_id'=>$service,'disposition'=>'Connected','followup_id'=>25,
    'status'=>1,'created_by'=>$request->manager_id,'remark'=>'Business Lead Assigned');
    DB::connection('sales_db')->table('clients_followup_log')->insert($data);
    return response()->json(['status'=>200,'message'=>'Assigned Successfully']);


}

public function assigned_member_on_business_lead($id){
    $data = DB::connection('sales_db')->table('clients_followup_log')->where('client_id',$id)
    ->pluck('created_by');
    $get_emp_name  = DB::table('emp_basic_info')->whereIn('emp_id', $data)
    ->pluck('emp_fname')->implode(',');
    return response()->json(['status'=>200,'data'=>$get_emp_name]);
}

public function get_business_lead_status($id){
    $data = DB::connection('sales_db')->table('guest_user_details')->where('id',$id)->first();
    if($data->status==0){
        return response()->json(['status'=>200]);
    }
    else{
        return response()->json(['status'=>201,'message'=>'Already Picked By Someone']);

    }
}

// public function business_lead_escalation(){
//     $data = DB::connection('sales_db')->table('guest_user_details')->whereIn('reg_type',[2,3,4])
//     ->where('status',0)->get();
//     foreach($data as $row){

        
//      }


// }


public function calciulate_sent_percent_lmart_on_sales_dashboard($emp_id){
    $currentMonth = date('n');
    $currentYear = date('Y');
    if ($currentMonth >= 4) {
        $financialYearStart = $currentYear;
        $financialYearEnd = $currentYear + 1;
    } else {
        $financialYearStart = $currentYear - 1;
        $financialYearEnd = $currentYear;
    }
     $financialYear = $financialYearStart . '-' . $financialYearEnd;

    $data = BasicInfo::where('emp_id',$emp_id)->first();
    if($data->assigned_group!=''){
        $group_ids = explode(',',$data->assigned_group);
     $enquiry_details = DB::connection('sales_db')->table('enquiry_info')->whereIn('group_id', $group_ids)->whereMonth('created_date',Carbon::now()->month)->count();

     $total_sent_enquiry =  DB::connection('sales_db')->table('enquiry_info')->whereIn('group_id', $group_ids)->where('enq_status',1)->whereMonth('created_date',Carbon::now()->month)->count();

     $assigned_target = DB::table('save_company_target')->where('department_id',3)->where('attribute_id',3)->where('subattribute_id',5)->where('financial_year',$financialYear)->first();
    if($enquiry_details>0 &&  $assigned_target ){

        $total_sent_percent =  $total_sent_enquiry/$enquiry_details*100;
        if($total_sent_percent<$assigned_target->child_attribute_value){
            return response()->json(['status'=>200,'message'=>'Lead Sent Percent Of Lmart Less Than'.' '.$assigned_target->child_attribute_value]);

        }

    }
    else{
         return response()->json(['status'=>200,'message'=>'Lead Sent Percent Of Lmart Less Than'.' '.$assigned_target->child_attribute_value]);


    }
 }
 else{
    return response()->json(['status'=>201,'message'=>'Group Not Assigned']);

 }



}

public function show_enquiry_data_on_sales_dashboard(Request $request){
    $currentmonth = Carbon::now()->month;
    $currentyear = Carbon::now()->year;

     if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager])
                      ->orWhere('dept_manager', $request->manager);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }


     $query  =  DB::connection('sales_db')->table('service_info')
                       ->leftJoin('package_info','package_info.package_id','service_info.package_id')
                       ->leftJoin('company_info','company_info.comp_id','service_info.comp_id')
                       ->whereIn('service_info.created_by',$emp_managers );
                       
    $no_of_unique_packages =  DB::connection('sales_db')->table('package_info')
                       ->leftJoin('service_info','package_info.package_id','service_info.package_id')
                        ->leftJoin('company_info','company_info.comp_id','package_info.comp_id')
                        ->where('package_status',1)
                         ->whereIn('package_info.exe_id',$emp_managers );
                       
   if($request->product){
       $query->where('package_info.product_id',$request->product);
       $no_of_unique_packages->where('package_info.product_id',$request->product);
   }
   if($request->category){
      $query->where('service_info.category_id',$request->category);
       $no_of_unique_packages->where('service_info.category_id',$request->category);

   }
   if($request->service){
     $query->where('service_info.service_id',$request->service);
     $no_of_unique_packages->where('service_info.category_id',$request->category);

   }
    if($request->group){
      $query->where('company_info.group_id',$request->group);
       $no_of_unique_packages->where('company_info.group_id',$request->group);
     }
     
    if($request->start_date && $request->end_date){
     $query->whereDate('service_info.created_date', '>=',$request->start_date)
                ->whereDate('service_info.created_date', '<=',$request->end_date);
    $no_of_unique_packages->whereDate('package_info.created_date', '>=',$request->start_date)
                ->whereDate('package_info.created_date', '<=',$request->end_date);

    }
    else{

        $query->whereMonth('service_info.created_date', $currentmonth)
            ->whereYear('service_info.created_date',$currentyear);
         $no_of_unique_packages->whereMonth('package_info.created_date', $currentmonth)
            ->whereYear('package_info.created_date',$currentyear);
        }
        
    $total_lead_sale = $query->sum('service_info.sent_lead');
    $total_lead = $query->sum('service_info.total_lead');
    $blance_lead = $query->sum('service_info.balance_lead');
    $no_of_unique_packages =  $no_of_unique_packages->pluck('package_info.package_id');
    //return $no_of_unique_packages;
    
    $get_pkg_amount = $query->whereIn('service_info.package_id',$no_of_unique_packages)->sum('service_info.per_lead_price');
    if($no_of_unique_packages->count()>0){
        $lead_sale_price =  round(($get_pkg_amount*$total_lead_sale)/$no_of_unique_packages->count());
        
        
        
    }
    else{
        $lead_sale_price = 0;
        
    }
    if($total_lead>0){
         $sent_percent = round($total_lead_sale/$total_lead*100);
        
    }
    else{
        $sent_percent = 0;
    }
        
    $data_array = array('lead_sale'=>$total_lead_sale,'sale_price'=>$lead_sale_price,'group_loss'=>$blance_lead,'category_loss'=>$blance_lead,'sent_percent'=>$sent_percent);
    return response()->json(['status'=>200,'data'=>$data_array]);
    
}

  public function get_sales_forcast_escalation(Request $request,$emp_id){
    $currentmonth = Carbon::now()->month;
    $currentyear = Carbon::now()->year;
    $currentMonth = date('n');
    $currentYear = date('Y');
    if ($currentMonth >= 4) {
        $financialYearStart = $currentYear;
        $financialYearEnd = $currentYear + 1;
    } else {
        $financialYearStart = $currentYear - 1;
        $financialYearEnd = $currentYear;
    }
     $financialYear = $financialYearStart . '-' . $financialYearEnd;

    $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($emp_id) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id])
                      ->orWhere('dept_manager', $emp_id);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $emp_id;
        $emp_managers = array_unique($emp_managers);
        

        $get_sales_funnel_accuracy = DB::connection('sales_db')->table('clients_followup_log')
         ->whereIn('created_by',$emp_managers)->whereMonth('created_date',Carbon::now()->month)->count();
         

         $get_meture_followup =  DB::connection('sales_db')->table('clients_followup_log')->where('followup_id',19)
         ->whereIn('created_by',$emp_managers)->whereMonth('created_date',Carbon::now()->month)->count();

          $target_assign_forcast_accuracy  = DB::table('save_company_target')
         ->where('department_id',3)->where('attribute_id',11)->where('subattribute_id',16)
         ->where('financial_year',$financialYear)->first();

         if($get_sales_funnel_accuracy>0 && $target_assign_forcast_accuracy ){
            $forcast_accuracy =  round($get_meture_followup/$get_sales_funnel_accuracy)*100;

            if($forcast_accuracy<$target_assign_forcast_accuracy->child_attribute_value){
                return response()->json(['status'=>201,'message'=>'Forcast Accuracy Percent Less Than'.' '.$target_assign_forcast_accuracy->child_attribute_value]);
             }

         }
        }

        public function show_data_on_superadmin_dashboard(){
         $total_collection = DB::connection('sales_db')
                            ->table('payment_history')->whereMonth('created_date',Carbon::now()->month)->whereYear('created_date',Carbon::now()->year)->sum('paid_amount');
         $total_followups = DB::connection('sales_db')
                            ->table('clients_followup_log')->whereMonth('created_date',Carbon::now()->month)->whereYear('created_date',Carbon::now()->year)->whereIn('followup_id',[6,9])->count();
        $renew_sale =  DB::connection('sales_db')
                            ->table('payment_history')->whereMonth('created_date',Carbon::now()->month)->whereYear('created_date',Carbon::now()->year)->where('payment_for','Renew')->sum('paid_amount');


        $new_sale =  DB::connection('sales_db')
                            ->table('payment_history')->whereMonth('created_date',Carbon::now()->month)->whereYear('created_date',Carbon::now()->year)->where('payment_for','New')->sum('paid_amount');
        $total_lead_adwords = DB::connection('sales_db')
                            ->table('enquiry_info')->whereMonth('created_date',Carbon::now()->month)->whereYear('created_date',Carbon::now()->year)->where('source_type','Adword')->count();


        $total_lead_organic = DB::connection('sales_db')
                            ->table('enquiry_info')->whereMonth('created_date',Carbon::now()->month)->whereYear('created_date',Carbon::now()->year)->where('source_type','Website')->count();
        $total_raise_tickets = DB::table('ticket_rise')->count();

        $data_array = array('total_tickets'=>$total_raise_tickets,'collection'=>$total_collection,'followups'=>$total_followups,'new_sale'=> $new_sale,'renew'=>$renew_sale,'adword'=>$total_lead_adwords,'organic'=>$total_lead_organic);

        return response()->json(['status'=>200,'data'=> $data_array]);




        }

public function get_monthly_payment_followups(Request $request) {
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $query = DB::connection('sales_db')->table('clients_followup_log')->leftJoin('company_info','company_info.client_id','clients_followup_log.client_id')
        ->whereIn('followup_id',[6,9]);


    if ($request->group) {
        $query->where('company_info.group_id',$request->group);

     }

    if ($request->product) {
        $query->where('clients_followup_log.product_id', $request->product);
    }

    if ($request->service) {
        $query->where('clients_followup_log.service_id', $request->service); 
    }

    if ($request->category) {
        $query->where('clients_followup_log.category_id', $request->category); 
    }

    if ($request->start_date && $request->end_date) {
        $query->whereDate('clients_followup_log.created_date', '>=',$request->start_date)
                ->whereDate('clients_followup_log.created_date', '<=',$request->end_date);
    } else {
        $query->whereYear('clients_followup_log.created_date', Carbon::now()->year);
    }

    if (!empty($emp_managers)) {
        $query->whereIn('clients_followup_log.created_by', $emp_managers);
    }

    $data = $query->select(
            DB::raw('YEAR(clients_followup_log.created_date) as year'),
            DB::raw('MONTH(clients_followup_log.created_date) as month'),
            DB::raw('count(clients_followup_log.id) as total_payment_followup')
        )
        ->groupBy('year', 'month')
        ->get();

    $months = [
        1 => 'January', 2 => 'February', 3 => 'March',
        4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September',
        10 => 'October', 11 => 'November', 12 => 'December'
    ];

    $all_months = collect(range(1, 12))->map(function ($month) use ($months) {
        return [
            'year' => Carbon::now()->year,
            'month' => $months[$month],
            'total_payment_followup' => 0
        ];
    });

    $result = $all_months->map(function ($monthData) use ($data, $months) {
        $monthDataFromDB = $data->firstWhere('month', array_search($monthData['month'], $months));
        if ($monthDataFromDB) {
            $monthData['total_payment_followup'] = $monthDataFromDB->total_payment_followup;
        }
        return $monthData;
    });

    return response()->json(['status'=>200,'data'=>$result]);
}

public function save_sales_request(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
      'remarks'=>'required',
    ]);
     if($validator->fails()){
           $messages=$validator->messages();
           return response()->json(["messages"=>$messages,'status'=>400]);     
     }
     $get_company_id = DB::connection('sales_db')->table('package_info')->where('package_id',$request->package_id)->first();
     if($get_company_id){
        $company_id = $get_company_id->comp_id;

      }
     else{
        $company_id = 0;

      }

      $data = array('client_id'=>$request->client_id,'company_id'=>$company_id,
            'package_id'=>$request->package_id,'title'=>$request->title,
            'remarks'=>$request->remarks,'generated_by'=>$request->emp_id);

     DB::connection('sales_db')->table('sales_request_generation')->insert($data);
     return response()->json(['status'=>200,'message'=>'Request Send Successfully']);



}
public function get_sales_request_list(Request $request){
    // Base query with common joins and selects
    $query = DB::connection('sales_db')->table('sales_request_generation')
        ->leftjoin('company_info', 'company_info.comp_id', '=', 'sales_request_generation.company_id')
        ->leftjoin('company_info AS client_info', 'client_info.client_id', '=', 'sales_request_generation.client_id')
        ->leftjoin('hr_panel.emp_basic_info AS generated_by_info', 'sales_request_generation.generated_by', '=', 'generated_by_info.emp_id')
        ->leftjoin('hr_panel.emp_basic_info AS action_by_info', 'sales_request_generation.action_by', '=', 'action_by_info.emp_id')
        ->leftjoin('package_info', 'sales_request_generation.package_id', '=', 'package_info.package_id')
        ->select(
            'client_info.client_name',
            'company_info.business_name',
            'sales_request_generation.*',
            'generated_by_info.emp_fname AS employee_fname',
            'generated_by_info.emp_lame AS employee_last_name',
            'action_by_info.emp_fname AS action_by_fname',
            'action_by_info.emp_lame AS action_by_lname',
            'package_info.package_name',
        )
        ->orderBy('sales_request_generation.id', 'DESC');

    if ($request->dept_id == 3) {
        $query->where('sales_request_generation.generated_by', $request->emp_id);
    }
    $data = $query->get();
    $data = $data->map(function($item) {
        switch ($item->status) {
            case 0:
                $item->status_text = 'Pending';
                break;
            case 1:
                $item->status_text = 'Approved';
                break;
            case 2:
                $item->status_text = 'Rejected';
                break;
            default:
                $item->status_text = 'Unknown'; 
                break;
        }
        return $item;
    });
    return response()->json(['status' => 200, 'data' => $data]);
}

public function update_request_status(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
      'status'=>'required',
    ]);
     if($validator->fails()){
           $messages=$validator->messages();
           return response()->json(["messages"=>$messages,'status'=>400]);     
     }

     $data = array('status'=>$request->status,'action_by'=>$request->emp_id,
            'action_remark'=>$request->remarks,'action_date'=>Carbon::now());

    DB::connection('sales_db')->table('sales_request_generation')->where('id',$request->id)->update($data);
    return response()->json(['status'=>200,'message'=>'Request Updated Successfully']);
  }

  public function save_enquiry_history(){
    $data = [];
    $notification_data = [];
    $result = DB::connection('sales_db')
    ->table('enquiry_info')
    ->select(
        'group_id',
        'followup_status',
        DB::raw('COUNT(*) as count'),
        DB::raw('SUM(expected_value) as total_lead_amount'),
        DB::raw('MAX(created_date) as created_date'),
        DB::raw('MAX(id) as enq_id')
    )
    ->whereIn('followup_status', [13,17])
    ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
    ->groupBy('group_id','followup_status')
    ->get();
        foreach($result as $row){

            $data[] = array('group_id'=>$row->group_id,
            'no_of_enquiry'=>$row->count,'expected_amount'=>$row->total_lead_amount,'date'=>Carbon::parse($row->created_date)->format('Y-m-d'),'enq_status'=>$row->followup_status,
             );
         }
         DB::connection('sales_db')->table('enquiry_history')->insert($data);
         if(count($result)>0){
             $group_ids = DB::connection('sales_db')
            ->table('enquiry_info')
            ->whereIn('followup_status', [17,18])
            ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
            ->select('group_id')
            ->distinct()
            ->get();

             foreach($group_ids as $ids){

                $get_manaager_ids = DB::table('emp_basic_info')
                ->whereRaw('FIND_IN_SET(?, assigned_group)', [$ids->group_id])
                ->where('desi_id',38)
                ->where('emp_status',1)
                ->first();
                $notification_data[] = array('emp_id'=>$get_manaager_ids->emp_id,
                'type'=>'enquiry','notification'=>'Click To View Client Not Found');
             }
            DB::table('emp_notifications')->insert($notification_data);

         }


  }
  public function get_enquiry_history_list(Request $request){
      $data = [];
    $get_emp_groups = DB::table('emp_basic_info')->where('emp_id',$request->emp_id)->first();
    if($get_emp_groups->assigned_group){
        $group_ids =  explode(',',$get_emp_groups->assigned_group);
        $data = DB::connection('sales_db')->table('enquiry_history')
        ->join('group_names', 'group_names.group_id', '=', 'enquiry_history.group_id')
        ->join('enq_status', 'enq_status.id', '=', 'enquiry_history.enq_status')
        ->select('group_names.name as group_name','enq_status.name','enquiry_history.*')
        ->whereIn('enquiry_history.group_id', $group_ids)
        ->whereDate('date',Carbon::now()->subDay()->format('Y-m-d'))
        ->paginate(1);
    
     }
       return response()->json(['status'=>200,'data'=>$data,'last_page'=>$data->lastPage()]);

  }
   public function get_category_list_by_product_id($id){
    $data = DB::connection('sales_db')->table('product_category')->where('product_id',$id)->where('status',1)
    ->get(['id','category_name']);
    return response()->json(['status'=>200,'data'=>$data]);
  }
  
//   public function get_client_not_found_enquiry($emp_id){
//       $data = DB::table('emp_basic_info')->where('emp_id',$emp_id)->first();
//       if($data->assigned_groups){
//           $group_ids = explode(',',$data->assigned_groups);
//           $get_enquiry = DB::connection('sales_db')->table('enquiry_info')->Join('enq_status','enq_status.id','enquiry_info.followup_id')
//              ->leftJoin('group_names','group_names.group_id','enquiry_info.group_id')
//               ->leftJoin('')
//              ->whereIn('group_id',$data->assigned_groups)->whereIn('followup_status',[13,17]);
//           if($request->product){
//               $get_enquiry->where('product_id',$request->product);
              
              
//           }
//           if($request->group){
//               $get_enquiry->where('group_id',$request->group);
              
//           }
//           if($request->category){
//               $get_enquiry->where('category_id',$request->category);
              
//           }
//           if($request->service){
//               $get_enquiry->where('service_id',$request->service);
              
              
//           }
          
//           if($request->start_date && $request->end_date){
//               $get_enquiry->whereDate('enquiry_info.created_date', '>=',$request->start_date)
//                 ->whereDate('enquiry_info.created_date', '<=',$request->end_date);
              
              
//           }
//           else{
//               $get_enquiry->whereDate('enquiry_info.created_date',Carbon::now()->format('Y-m-d'));
              
//           }
          
//           $get_data = $get_enquiry->paginate(10);
          
          
          
//   }
      
//   }

public function add_remark_on_enquiry(Request $request){
    if($request->desi_id==38){
        $data = array('manager_remark'=>$request->remark);
        
    }
    if($request->desi_id==19){
        $data = array('superadmin_remark'=>$request->remark);
        
    }
    DB::connection('sales_db')->table('enquiry_history')->where('id',$request->id)->update($data);
    return response()->json(['status'=>200,'message'=>'Remark Added Successfully']);
    
    
}
public function get_enquiry_history_details(Request $request){
    $emp_groups = DB::table('emp_basic_info')->where('emp_id', $request->emp_id)->first();
            if($emp_groups){
                $assigned_group = $emp_groups->assigned_group;
                $group_id_details = $assigned_group ? explode(',', $assigned_group) : [];
            if($group_id_details){
                $group_ids = $group_id_details;
            }

            }
            else{
                $group_ids = [];

            }
    $data = DB::connection('sales_db')
    ->table('enquiry_info')
    ->leftJoin('group_names', 'group_names.group_id', '=', 'enquiry_info.group_id')
    ->leftJoin('product', 'product.id', '=', 'enquiry_info.product_id')
    ->leftJoin('product_service', 'product_service.id', '=', 'enquiry_info.service_id')
    ->leftJoin('product_category', 'product_category.id', '=', 'enquiry_info.category_id')
    ->leftJoin('enq_status', 'enq_status.id', '=', 'enquiry_info.followup_status')
    ->select(
        'enquiry_info.group_id',
        'enquiry_info.followup_status',
        DB::raw('DATE(enquiry_info.created_date) as created_date'),
        DB::raw('COUNT(enquiry_info.id) as enquiry_count'),
        DB::raw('SUM(enquiry_info.expected_value) as total_expected_value'),
        'group_names.name as group_name',
        'enq_status.name'
    )
    ->whereIn('enquiry_info.followup_status', [22, 23])
    ->whereIn('enquiry_info.group_id',$group_ids)
    ->groupBy('enquiry_info.group_id', 'enquiry_info.followup_status', 'created_date');

if ($request->group) {
    $data->where('enquiry_info.group_id', $request->group);
}

if ($request->start_date && $request->end_date) {
    $data->whereDate('enquiry_info.created_date', '>=', $request->start_date)
         ->whereDate('enquiry_info.created_date', '<=', $request->end_date);
} else {
    $data->whereDate('enquiry_info.created_date', Carbon::now()->format('Y-m-d'));
}

$records = $data->paginate(10);
return response()->json(['status'=>200,'data'=>$records,'last_page'=>$records->lastPage()]);
        
    }
    
   public function get_group_based_on_sales_emp($id) {
    $group_names = [];

    $data = DB::table('emp_basic_info')->where('emp_id', $id)->first();
    if (!empty($data->assigned_group)) {
        $group_ids = explode(',', $data->assigned_group);
        $group_names = DB::connection('sales_db')
            ->table('group_names')
            ->whereIn('group_id', $group_ids)
            ->get(['group_id', 'name']);
    }

    return response()->json([
        'status' => 200,
        'data' => $group_names
    ]);
}
public function add_remark_on_kra_kpis(Request $request)
{

    $get_record = DB::connection('sales_db')->table('kra_and_kpi_details')->where('id', $request->id)->first();

    if (!$get_record) {
        return response()->json(['status' => 404, 'message' => 'Record not found'], 404);
    }

    $get_emp_manager = DB::table('emp_basic_info')->where('emp_id', $get_record->created_by)->first();

    if (!$get_emp_manager) {
        return response()->json(['status' => 404, 'message' => 'Employee manager not found'], 404);
    }

    if ($request->emp_id == $get_record->created_by) {
        DB::connection('sales_db')->table('kra_and_kpi_details')
            ->where('id', $request->id)
            ->update(['emp_remark' => $request->remark]);
    }  else if ($request->emp_id == 'RIMS1') {
        DB::connection('sales_db')->table('kra_and_kpi_details')
            ->where('id', $request->id)
            ->update(['superadmin_remark' => $request->remark]);
    } else{
         DB::connection('sales_db')->table('kra_and_kpi_details')
            ->where('id', $request->id)
            ->update(['manager_remark' => $request->remark]);
    }

    return response()->json(['status' => 200, 'message' => 'Remark added successfully']);
}

public function get_followups_details(Request $request) {
    $data_array = [];

    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

      $data = DB::connection('sales_db')->table('clients_followup_log')
        ->leftJoin('company_info', 'company_info.comp_id', 'clients_followup_log.comp_id')
        ->leftJoin('guest_user_details', 'guest_user_details.id', 'clients_followup_log.client_id')
        ->select('clients_followup_log.*', 'company_info.client_name', 'company_info.group_id as company_info_group', 
        'guest_user_details.group_id as guest_group');


    if ($request->group) {
        $data->where(function ($query) use ($request) {
            $query->where(function ($subQuery) use ($request) {
                $subQuery->where('clients_followup_log.client_type', 1)
                    ->where('guest_user_details.group_id', $request->group);
            })->orWhere(function ($subQuery) use ($request) {
                $subQuery->where('clients_followup_log.client_type', 2)
                    ->where('company_info.group_id', $request->group);
            });
        });

        // $total_call_conncted->where(function ($query) use ($request) {
        //     $query->where(function ($subQuery) use ($request) {
        //         $subQuery->where('clients_followup_log.client_type', 1)
        //             ->where('guest_user_details.group_id', $request->group);
        //     })->orWhere(function ($subQuery) use ($request) {
        //         $subQuery->where('clients_followup_log.client_type', 2)
        //             ->where('company_info.group_id', $request->group);
        //     });
        // });
    }
    
    if ($request->product) {
        $data->where('clients_followup_log.product_id', $request->product);
        //$total_call_conncted->where('clients_followup_log.product_id', $request->product);
    }
    if ($request->service) {
        $data->where('clients_followup_log.service_id', $request->service);
        //$total_call_conncted->where('clients_followup_log.service_id', $request->service);
    }
    if ($request->category) {
        $data->where('clients_followup_log.category_id', $request->category);
        //$total_call_conncted->where('clients_followup_log.category_id', $request->category);

    }
    if ($request->start_date && $request->end_date) {
        if($request->type =='meeting_followup' || $request->type =='payment_followup' || $request->type =='business_proposal'){
             $data->whereDate('clients_followup_log.next_followup_date', '>=', $request->start_date)
            ->whereDate('clients_followup_log.next_followup_date', '<=', $request->end_date);
        }

        if($request->type == 'no_of_call_connected'){
           $data->whereDate('clients_followup_log.created_date', '>=', $request->start_date)
           ->whereDate('clients_followup_log.created_date', '<=', $request->end_date);


        }

      } else {
         if($request->type =='meeting_followup' || $request->type =='payment_followup' || $request->type =='business_proposal'){
           $data->whereDate('clients_followup_log.next_followup_date', Carbon::now()->format('Y-m-d'));
           }
        if($request->type == 'no_of_call_connected'){
            $data->whereDate('clients_followup_log.created_date', Carbon::now()->format('Y-m-d'));

        }
    }

    // Follow-up type filters
    if ($request->type == 'meeting_followup') {
        $data->where('clients_followup_log.followup_id', 7);
    }
    if ($request->type == 'payment_followup') {
        $data->whereIn('clients_followup_log.followup_id', [6, 9]);
    }
    if ($request->type == 'business_proposal') {
        $data->where('clients_followup_log.followup_id', 5);
    }
    if ($request->type == 'no_of_call_connected') {
        $data->where('clients_followup_log.disposition', 'Connected')->whereNotIn('followup_id',[24,25]);
    }
    // if ($request->type == 'mature_followup') {
    //     $data->where('clients_followup_log.followup_id', 19);
    // }
    // if ($request->type == 'dead_followup') {
    //     $data->where('clients_followup_log.followup_id', 15);
    // }
    // if ($request->type == 'total_amount') {
    //     $data->where('clients_followup_log.amount', '!=', ' ');
    // }

    $data->whereIn('clients_followup_log.created_by', $emp_managers);

    
    if($request->download = 'csv1'){
        $query = $data->get();
        foreach ($query as $row) {
            if ($row->client_type == 1) {
                $client_details = DB::connection('sales_db')->table('guest_user_details')->where('id', $row->client_id)->first();
    
                $client_name = $client_details->name??'';
                $group_id = $client_details->group_id??'';
                $comp_name =  $client_details->business_name??'';
                $mobile_no = $client_details->mobile_no??'';
            } else {
                $client_details = DB::connection('sales_db')->table('company_info')->where('comp_id', $row->comp_id)->first();
                $client_name = $client_details->client_name??'';
                $group_id = $client_details->group_id??'';
                $comp_name =  $client_details->business_name??'';
                $mobile_no = $client_details->mobile_no??'';
            }
    
            $group = DB::connection('sales_db')->table('group_names')->where('group_id', $row->client_type == 1 ? $group_id : $group_id)->first();
            $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
            $service = DB::connection('sales_db')->table('product_service')->where('id', $row->service_id)->first();
            $category = DB::connection('sales_db')->table('product_category')->where('id', $row->category_id)->first();
            $followup_status = DB::connection('sales_db')->table('followup_status')->where('id', $row->followup_id)->first();
            $emp_name = DB::table('emp_basic_info')->where('emp_id', $row->created_by)->first();
            $manager_name = DB::table('emp_basic_info')->where('emp_id', $emp_name->reporting_manager)->first();
    
            $total_no_of_pkg_buys = DB::connection('sales_db')->table('package_info')->where('comp_id',$row->comp_id)->where('client_id', $row->client_id)->count();
            $last_package_by_date = DB::connection('sales_db')->table('package_info')->where('comp_id',$row->comp_id)->where('client_id', $row->client_id)->orderBy('package_id', 'DESC')->first();
            
            $pkg_buy_date = $last_package_by_date ? $last_package_by_date->created_date : '';
    
            if ($row->refer_package_id != '') {
                $pkg_details = DB::connection('sales_db')->table('pre_package')->where('id', $row->refer_package_id)->first();
                $pkg_amount = $pkg_details->package_price;
                $pkg_type = $pkg_details->package_type;
            } else {
                $pkg_amount = '';
                $pkg_type = '';
            }
    
            $data_array[] = [
                'id' => $row->id,
                'client_type'=>$row->client_type,
                'client_id'=>$row->client_id,
                'client_name' => $client_name ?? '',
                'comp_name'=>$comp_name??'',
                'group' => $group->name??'',
                'product' => $product->product_name ?? '',
                'category' => $category->category_name ?? '',
                'service' => $service->service_name ?? '',
                'followup_status' => $followup_status->activity_name ?? '',
                'created_date' => $row->created_date ?? '',
                'amount' => $row->amount ?? '',
                'emp_name' => ($emp_name->emp_fname ?? '') . ' ' . ($emp_name->emp_lame ?? ''),
                'manager' => ($manager_name->emp_fname ?? '') . ' ' . ($manager_name->emp_lame ?? ''),
                'no_of_package_buys' => $total_no_of_pkg_buys ?? '0',
                'pkg_buy_last_date' => $pkg_buy_date,
                'package_type' => $pkg_details->package_amount ?? '',
                'pkg_amount' => $pkg_amount ?? '',
                'pkg_type' => $pkg_type ?? '',
                'next_followup_date'=>$row->next_followup_date ?? '',
                'mobile_no'=> $mobile_no??'',
            ];
        }
       // return $data_array;
        return Excel::download(new class($data_array) implements FromArray {
            protected $data;

           
            public function __construct($data)
            {
                $this->data = $data;
            }

           
            public function array(): array
            {
              
                return array_merge(
                    [['ID', 'Client Type', 'Client ID', 'Client Name', 'Company Name', 'Group', 'Product', 'Category', 'Service', 'Follow-up Status', 'Created Date', 'Amount', 'Employee Name', 'Manager', 'No of Package Buys', 'Package Buy Last Date', 'Package Type', 'Package Amount', 'Package Type', 'Next Follow-up Date', 'Mobile No']], // Headers
                    $this->data // Data rows
                );
            }
        }, 'follow_up_data.csv');

    }
    else{
        $query = $data->paginate(10);
        foreach ($query as $row) {
            if ($row->client_type == 1) {
                $client_details = DB::connection('sales_db')->table('guest_user_details')->where('id', $row->client_id)->first();
    
                $client_name = $client_details->name??'';
                $group_id = $client_details->group_id??'';
                $comp_name =  $client_details->business_name??'';
                $mobile_no = $client_details->mobile_no??'';
            } else {
                $client_details = DB::connection('sales_db')->table('company_info')->where('comp_id', $row->comp_id)->first();
                $client_name = $client_details->client_name??'';
                $group_id = $client_details->group_id??'';
                $comp_name =  $client_details->business_name??'';
                $mobile_no = $client_details->mobile_no??'';
            }
    
            $group = DB::connection('sales_db')->table('group_names')->where('group_id', $row->client_type == 1 ? $group_id : $group_id)->first();
            $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
            $service = DB::connection('sales_db')->table('product_service')->where('id', $row->service_id)->first();
            $category = DB::connection('sales_db')->table('product_category')->where('id', $row->category_id)->first();
            $followup_status = DB::connection('sales_db')->table('followup_status')->where('id', $row->followup_id)->first();
            $emp_name = DB::table('emp_basic_info')->where('emp_id', $row->created_by)->first();
            $manager_name = DB::table('emp_basic_info')->where('emp_id', $emp_name->reporting_manager)->first();
    
            $total_no_of_pkg_buys = DB::connection('sales_db')->table('package_info')->where('comp_id',$row->comp_id)->where('client_id', $row->client_id)->count();
            $last_package_by_date = DB::connection('sales_db')->table('package_info')->where('comp_id',$row->comp_id)->where('client_id', $row->client_id)->orderBy('package_id', 'DESC')->first();
            
            $pkg_buy_date = $last_package_by_date ? $last_package_by_date->created_date : '';
    
            if ($row->refer_package_id != '') {
                $pkg_details = DB::connection('sales_db')->table('pre_package')->where('id', $row->refer_package_id)->first();
                $pkg_amount = $pkg_details->package_price;
                $pkg_type = $pkg_details->package_type;
            } else {
                $pkg_amount = '';
                $pkg_type = '';
            }
    
            $data_array[] = [
                'id' => $row->id,
                'client_type'=>$row->client_type,
                'client_id'=>$row->client_id,
                'client_name' => $client_name ?? '',
                'comp_name'=>$comp_name??'',
                'group' => $group->name??'',
                'product' => $product->product_name ?? '',
                'category' => $category->category_name ?? '',
                'service' => $service->service_name ?? '',
                'followup_status' => $followup_status->activity_name ?? '',
                'created_date' => $row->created_date ?? '',
                'amount' => $row->amount ?? '',
                'emp_name' => ($emp_name->emp_fname ?? '') . ' ' . ($emp_name->emp_lame ?? ''),
                'manager' => ($manager_name->emp_fname ?? '') . ' ' . ($manager_name->emp_lame ?? ''),
                'no_of_package_buys' => $total_no_of_pkg_buys ?? '0',
                'pkg_buy_last_date' => $pkg_buy_date,
                'package_type' => $pkg_details->package_amount ?? '',
                'pkg_amount' => $pkg_amount ?? '',
                'pkg_type' => $pkg_type ?? '',
                'next_followup_date'=>$row->next_followup_date ?? '',
                'mobile_no'=> $mobile_no??'',
            ];
        }
    
        return response()->json(['status' => 200, 'data' => $data_array,'last_page'=>$query->lastPage()]);
    }



    
}


public function carry_forward_followups(Request $request) {
    $data_array = [];
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager])
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $data = DB::connection('sales_db')->table('clients_followup_log')
    ->leftJoin('company_info', 'company_info.comp_id', 'clients_followup_log.comp_id')
    ->leftJoin('guest_user_details', 'guest_user_details.id', 'clients_followup_log.client_id')
    ->select(
        'clients_followup_log.comp_id',
        'clients_followup_log.client_id',
        'clients_followup_log.created_by',
        'guest_user_details.name as guest_client_name',
        'guest_user_details.business_name as guest_client_business',
        DB::raw('MAX(clients_followup_log.created_date) as last_followup_date'),
        DB::raw('MAX(clients_followup_log.client_type) as client_type'),
        DB::raw('MAX(clients_followup_log.next_followup_date) as next_followup_date'),
        DB::raw('COUNT(clients_followup_log.next_followup_date) as next_followup_count'), 
        DB::raw('MAX(clients_followup_log.product_id) as product_id'),
        DB::raw('MAX(clients_followup_log.category_id) as category_id'),
        DB::raw('MAX(clients_followup_log.service_id) as service_id'),
        'company_info.client_name',
        'company_info.business_name as company_business_name',
        'guest_user_details.group_id as guest_group_id', 
        'company_info.group_id as company_group_id'
    )
    ->whereIn('clients_followup_log.created_by', $emp_managers)
    ->where('clients_followup_log.next_followup_date','!=','')
    ->where('clients_followup_log.disposition','Connected')
    ->groupBy(
        'clients_followup_log.comp_id',
        'clients_followup_log.client_id',
        'clients_followup_log.created_by',
        'guest_user_details.name',
        'guest_user_details.business_name',
        'company_info.client_name',
        'company_info.business_name',
        'guest_user_details.group_id',
        'company_info.group_id'
    )
    ->having('next_followup_count', '>',1);
    if ($request->product) {
        $data->where('clients_followup_log.product_id', $request->product);
    }
    if ($request->service) {
        $data->where('clients_followup_log.service_id', $request->service);
    }
    if ($request->category) {
        $data->where('clients_followup_log.category_id', $request->category);
    }

    if ($request->start_date && $request->end_date) {
        $data->whereDate('clients_followup_log.created_date', '>=', $request->start_date)
              ->whereDate('clients_followup_log.created_date', '<=', $request->end_date);
    } else {
        $data->whereMonth('clients_followup_log.created_date', Carbon::now()->month);
    }

    $total = $data->count();
    $results = $data->groupBy('clients_followup_log.comp_id', 'clients_followup_log.client_id', 'clients_followup_log.created_by')->paginate(10);
    
    foreach ($results as $row) {
        $no_of_times_carry_forward = DB::connection('sales_db')->table('clients_followup_log')
            ->where('client_id', $row->client_id)
            ->where('comp_id', $row->comp_id)
            ->where('created_by', $row->created_by)
            ->where('next_followup_date', '!=', '')
            ->count();

        $no_of_times_carry_forward = max(0, $no_of_times_carry_forward-1);

            $last_inserted_id = DB::connection('sales_db')->table('clients_followup_log')
                ->where('client_id', $row->client_id)
                ->where('comp_id', $row->comp_id)
                ->where('created_by', $row->created_by)
                ->where('next_followup_date', '!=', '')
                ->orderBy('created_date', 'DESC')
                ->first();

            $product = DB::connection('sales_db')->table('product')
                ->where('id', $row->product_id)
                ->first();
            $category = DB::connection('sales_db')->table('product_category')
                ->where('id', $row->category_id)
                ->first();
            $service = DB::connection('sales_db')->table('product_service')
                ->where('id', $row->service_id)
                ->first();

            $emp_name = DB::table('emp_basic_info')
                ->where('emp_id', $row->created_by)
                ->first();

            $manager_name = DB::table('emp_basic_info')
                ->where('emp_id', $emp_name->reporting_manager)
                ->first();

            if ($row->client_type == 1) {
                $client_name = $row->guest_client_name;
                $company_name = $row->guest_client_business;

                $group = DB::connection('sales_db')->table('group_names')
                    ->where('group_id', $row->guest_group_id)
                    ->first();
                $group_name = $group ? $group->name : '';
            } elseif ($row->client_type == 2) {
                $client_name = $row->client_name;
                $company_name = $row->company_business_name;
                $group = DB::connection('sales_db')->table('group_names')
                    ->where('group_id', $row->company_group_id)
                    ->first();
                $group_name = $group ? $group->name : '';
            } else {
                $client_name = 'Unknown';
                $group_name = '';
                $company_name = '';
            }

            $data_array[] = array(
                'comp_id' => $row->comp_id,
                'client_id' => $row->client_id,
                'client_type' => $row->client_type,
                'last_followup_date' => $last_inserted_id->created_date ?? null,
                'next_followup_date' => $last_inserted_id->next_followup_date ?? null,
                'no_of_times_carry_forward' => $no_of_times_carry_forward,
                'client_name' => $client_name,
                'company_name' => $company_name,
                'product' => $product->product_name ?? '',
                'category' => $category->category_name ?? '',
                'service' => $service->service_name ?? '',
                'emp_name' => ($emp_name->emp_fname ?? '') . ' ' . ($emp_name->emp_lame ?? ''),
                'manager' => ($manager_name->emp_fname ?? '') . ' ' . ($manager_name->emp_lame ?? ''),
                'group' => $group_name ?? '',
            );
        }

    return response()->json(['status' => 200, 'data' => $data_array, 'last_page' =>$results->lastPage(),'total' => $total]);
}








public function get_active_clients_details(Request $request) {
    $data_array = [];
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }
    $data = DB::connection('sales_db')->table('company_info');
    if($request->group) {
        $data->where('group_id', $request->group);
    }

    if($request->product) {
        $data->where('product_id', $request->product);
    }
    if($request->mobile_no){
        //return 'kkk';
        $data->where('mobile_no', 'like', "%$request->mobile_no%");

    }

    if($request->client_name){
        //return 'kkk';
        $data->where('client_name', 'like', "%$request->client_name%");

    }

    if($request->company_name){
        //return 'kkk';
        $data->where('business_name', 'like', "%$request->company_name%");

    }
    if($request->client_type){
        $data->where('status', $request->client_type);

    }
    else{
        $data->where('status', 2);

    }
    if($request->start_date && $request->end_date){
        $data->whereDate('created_at', '>=', $request->start_date)
              ->whereDate('created_at', '<=', $request->end_date);
        
    }

    $query = $data->whereIn('exe_id', $emp_managers)->orderBy('comp_id','DESC')->paginate(10);

    foreach ($query as $row) {
        $group_name = DB::connection('sales_db')->table('group_names')->where('group_id', $row->group_id)->first();
        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();

        $no_of_packages_buy = DB::connection('sales_db')->table('package_info')->where('comp_id', $row->comp_id)->where('client_id',$row->client_id)->count();

        $no_of_active_packages = DB::connection('sales_db')->table('package_info')->where('package_status',1)->where('comp_id', $row->comp_id)->where('client_id',$row->client_id)->count();

        $emp_details = DB::table('emp_basic_info')->where('emp_id', $row->exe_id)->first();

        if ($emp_details) {
            $emp_name = $emp_details->emp_fname . ' ' . ($emp_details->emp_lame ?? '');
            $manager_name = '';

            if ($emp_details->reporting_manager) {
                $manager = DB::table('emp_basic_info')->where('emp_id', $emp_details->reporting_manager)->first();
                $manager_name = $manager ? $manager->emp_fname . ' ' . ($manager->emp_lame ?? '') : '';
            }
        } else {
            $emp_name = '';
            $manager_name = '';
        }
        $client_type = '';
        if($row->status ==1){
            $client_type = 'Verified';

        }
        if($row->status ==2){
            $client_type = 'Active';

        }

        if($row->status ==3){
            $client_type = 'InActive';

        }
        if($row->status ==4){
            $client_type = 'Expire';

        }
        if($row->status ==5){
            $client_type = 'BlackList';

        }
        if($row->status ==6){
            $client_type = 'IsDeleted';

        }

        $data_array[] = [
            'id' => $row->comp_id,
            'client_name' => $row->client_name ?? '',
            'city' => $row->city ?? '',
            'group' => $group_name->name ?? '',
            'emp_name' => $emp_name,
            'manager_name' => $manager_name,
            'product'=>$product->product_name??'',
            'company_name'=>$row->business_name??'',
            'no_of_packages_buy'=>$no_of_packages_buy??'',
            'client_id'=>$row->client_id,
            'client_type'=> $client_type,
            'no_of_active_packages'=>$no_of_active_packages,
            'mobile'=>$row->mobile_no??'',
            'created_date'=>$row->created_at??'',
        ];
    }

    return response()->json(['status' => 200, 'data' => $data_array,'last_page'=>$query->lastPage()]);
}

public function get_active_packages_list(Request $request) {
    $data_array = [];
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $data = DB::connection('sales_db')->table('package_info')->leftJoin('company_info','company_info.comp_id','package_info.comp_id')
      ->select('package_info.*','company_info.mobile_no','company_info.client_name','company_info.business_name');
    if ($request->group) {
        $data->whereRaw('FIND_IN_SET(?, package_info.group_id)', [$request->group]);
    }
    if ($request->product) {
        $data->where('package_info.product_id', $request->product);
    }
    if ($request->service) {
        $data->whereRaw('FIND_IN_SET(?, package_info.service_id)', [$request->service]);
    }
    if ($request->category) {
        $data->whereRaw('FIND_IN_SET(?, package_info.category_id)', [$request->category]);
    }
    if ($request->start_date && $request->end_date) {
        $data->whereDate('package_info.created_date', '>=', $request->start_date)
             ->whereDate('package_info.created_date', '<=', $request->end_date);
    } 

     if($request->mobile_no){
        //return 'kkk';
        $data->where('company_info.mobile_no', 'like', "%$request->mobile_no%");

    }

    if($request->client_name){
        //return 'kkk';
        $data->where('company_info.client_name', 'like', "%$request->client_name%");

    }

    if($request->company_name){
        //return 'kkk';
        $data->where('company_info.business_name', 'like', "%$request->company_name%");

    }
    if($request->package_type){
        $data->where('package_info.package_status', $request->package_type);

    }
    else{
        $data->where('package_info.package_status', 1);
      }

    $query = $data->whereIn('package_info.exe_id', $emp_managers)->orderBy('package_info.package_id','DESC')->paginate(10);

    foreach ($query as $row) {
        $group_ids = explode(',', $row->group_id);
        $category_id = explode(',', $row->category_id);
        $service_id = explode(',', $row->service_id);

        $group_names = DB::connection('sales_db')->table('group_names')
            ->whereIn('group_id', $group_ids)
            ->pluck('name')
            ->implode(', ');

        $category = DB::connection('sales_db')->table('product_category')
            ->whereIn('id', $category_id)
            ->pluck('category_name')
            ->implode(', ');

        $service = DB::connection('sales_db')->table('product_service')
            ->whereIn('id', $service_id)
            ->pluck('service_name')
            ->implode(', ');
            $package_status = ' ';
        if($row->package_status ==1){
            $package_status = 'Active';

        }
        if($row->package_status ==2){
            $package_status = 'Stop By Admin,Sales,Client';

        }
        if($row->package_status ==3){
            $package_status = 'Stop Due To Due Payment ';

        }
        if($row->package_status ==4){
            $package_status = 'Expiry';

        }

        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
        
        $client = DB::connection('sales_db')->table('company_info')->where('comp_id', $row->comp_id)->first();

        $emp_details = DB::table('emp_basic_info')->where('emp_id', $row->exe_id)->first();
        if ($emp_details) {
            $emp_name = $emp_details->emp_fname . ' ' . ($emp_details->emp_lame ?? '');
            $manager_name = '';

            if ($emp_details->reporting_manager) {
                $manager = DB::table('emp_basic_info')->where('emp_id', $emp_details->reporting_manager)->first();
                $manager_name = $manager ? $manager->emp_fname . ' ' . ($manager->emp_lame ?? '') : '';
            }
        } else {
            $emp_name = '';
            $manager_name = '';
        }

        $data_array[] = [
            'id' => $row->package_id,
            'product' => $product->product_name ?? '',
            'service' => $service ?? '',
            'category' => $category ?? '',
            'group' => $group_names ?? '',
            'pkg_name' => $row->package_name ?? '',
            'client_name' => $client->client_name ?? '',
            'company'=>$client->business_name ?? '',
            'created_date' => $row->created_date ?? '',
            'emp_name' => $emp_name,
            'manager_name' => $manager_name,
            'pkg_type'=>$row->package_type??'',
            'package_status'=>$package_status??'',
            'client_id'=>$row->client_id,
            'mobile_no'=>$row->mobile_no??'',
        ];
    }

    return response()->json(['status' => 200, 'data' => $data_array, 'last_page' => $query->lastPage()]);
}

public function group_loss(Request $request)
{
     $emp_groups = DB::table('emp_basic_info')->where('emp_id', $request->emp_id)->first();
     $group_ids = $emp_groups->assigned_group ? explode(',', $emp_groups->assigned_group) : [];

    $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);

    $data = DB::connection('sales_db')->table('enquiry_info')
        ->where('sent_count', '<', 4)
        ->whereIn('followup_status', [22, 23])
        ->whereIn('enquiry_info.group_id',$group_ids);
    

    

    $extra_sent = DB::connection('sales_db')->table('service_info')
        ->leftJoin('package_info', 'package_info.package_id', '=', 'service_info.package_id')
        ->leftJoin('product_category', 'service_info.category_id', '=', 'product_category.id')
        ->select(
            'package_info.group_id',
            'service_info.sent_lead',
            'service_info.total_lead',
            'package_info.package_duration',
            'service_info.category_id',
            'product_category.category_name as category_name',
            // DB::raw('SUM(service_info.total_lead) as total_lead'),
            // DB::raw('SUM(service_info.sent_lead) as sent_lead')
        )
        ->where('package_info.package_duration', 2)
        ->whereNotNull('service_info.sent_lead')
        ->whereNotNull('service_info.total_lead')
        ->whereRaw('service_info.sent_lead > service_info.total_lead');

    if ($request->group) {
        $data->where('enquiry_info.group_id', $request->group);
        $extra_sent->where('package_info.group_id', $request->group);
    }

    if ($request->product) {
        $data->where('enquiry_info.product_id', $request->product);
        $extra_sent->where('package_info.product_id', $request->product);
    }

    if ($request->start_date && $request->end_date) {
        $data->whereDate('enquiry_info.created_date', '>=', $request->start_date)
            ->whereDate('enquiry_info.created_date', '<=', $request->end_date);
        $extra_sent->whereDate('package_info.created_date', '>=', $request->start_date)
            ->whereDate('package_info.created_date', '<=', $request->end_date);
    } 
    else{
        $data->whereDate('enquiry_info.created_date', Carbon::now()->format('Y-m-d'));
        $extra_sent->whereDate('package_info.created_date', Carbon::now()->format('Y-m-d'));
    }

    $groupedData = $data->select(
        'enquiry_info.group_id',
        'group_names.name as group_name',
        'enquiry_info.category_id',
        'product_category.category_name as category_name',
        DB::raw('COUNT(*) as total_enquiries'),
        DB::raw('SUM(sent_count) as total_sent_count')
    )
    ->leftJoin('group_names', 'enquiry_info.group_id', '=', 'group_names.group_id')
    ->leftJoin('product_category', 'product_category.id', '=', 'enquiry_info.category_id')
    ->groupBy('enquiry_info.group_id', 'group_names.name', 'enquiry_info.category_id', 'product_category.category_name')
    ->get();

    $extra_sent_data = $extra_sent->whereIn('package_info.exe_id',$emp_m)->get();
    //return $extra_sent_data;

    $groupNames = DB::connection('sales_db')->table('group_names')
        ->pluck('name', 'group_id')
        ->toArray();

    $groupLoss = [];

    foreach ($groupedData as $groupData) {
        $key = $groupData->group_id . '-' . $groupData->category_id;

        $groupLoss[$key] = [
            'group_id' => $groupData->group_id,
            'group_name' => $groupData->group_name ?? ($groupNames[$groupData->group_id] ?? 'Unknown'),
            'category_id' => $groupData->category_id,
            'category_name' => $groupData->category_name ?? 'Unknown',
            'total_enquiries' => $groupData->total_enquiries,
            'total_sent_count' => $groupData->total_sent_count,
            'total_expected_sent_count' => $groupData->total_enquiries * 4,
            'not_sent' => max(0, ($groupData->total_enquiries * 4) - $groupData->total_sent_count),
            'extra_sent' => 0,
            'total_diff_sent_count' => 0,
        ];
    }

    foreach ($extra_sent_data as $extraData) {
        $group_ids_from_extra_sent = explode(',', $extraData->group_id);

        foreach ($group_ids_from_extra_sent as $group_id) {
            $key = $group_id . '-' . $extraData->category_id;

            if (!isset($groupLoss[$key])) {
                $groupLoss[$key] = [
                    'group_id' => $group_id,
                    'group_name' => $groupNames[$group_id] ?? 'Unknown',
                    'category_id' => $extraData->category_id,
                    'category_name' => $extraData->category_name ?? 'Unknown',
                    'total_enquiries' => 0,
                    'total_sent_count' => 0,
                    'total_expected_sent_count' => 0,
                    'not_sent' => 0,
                    'extra_sent' => 0,
                    'total_diff_sent_count' => 0,
                ];
            }

            $groupLoss[$key]['extra_sent'] += max(0, $extraData->sent_lead - $extraData->total_lead);
        }
    }

    foreach ($groupLoss as &$loss) {
        $loss['total_diff_sent_count'] = ($loss['not_sent'] ?? 0) + ($loss['extra_sent'] ?? 0);
    }

    $totalLoss = array_sum(array_column($groupLoss, 'total_diff_sent_count'));

    return response()->json([
        'status' => 200,
        'total_loss' => $totalLoss,
        'data' => array_values($groupLoss)
    ]);
}













public function lead_sale(Request $request)
{
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_id = $request->emp_id;
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($emp_id) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $data = DB::connection('sales_db')->table('package_info');

    if ($request->group) {
        $data->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);
    }
    if ($request->product) {
        $data->where('product_id', $request->product);
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
    }
    else{
        $data->whereMonth('created_date',Carbon::now()->month);
    }
     $total_data =  $data->whereIn('exe_id', $emp_managers)->paginate(10);
     foreach($total_data as $row){
        $group_ids = explode(',', $row->group_id);
        $category_id = explode(',', $row->category_id);
        $service_id = explode(',', $row->service_id);

        $group_names = DB::connection('sales_db')->table('group_names')
            ->whereIn('group_id', $group_ids)
            ->pluck('name')
            ->implode(', ');

        $category = DB::connection('sales_db')->table('product_category')
            ->whereIn('id', $category_id)
            ->pluck('category_name')
            ->implode(', ');

        $service = DB::connection('sales_db')->table('product_service')
            ->whereIn('id', $service_id)
            ->pluck('service_name')
            ->implode(', ');

            $package_status = ' ';
        if($row->package_status ==1){
            $package_status = 'Active';

        }
        if($row->package_status ==2){
            $package_status = 'Stop By Admin,Sales,Client';

        }
        if($row->package_status ==3){
            $package_status = 'Stop Due To Due Payment ';

        }
        if($row->package_status ==4){
            $package_status = 'Expiry';

        }
        $no_of_packages = DB::connection('sales_db')->table('package_info')->where('client_id',$row->client_id)
                         ->where('comp_id',$row->comp_id)->count();

        //$last_followup_date = DB::connection('sales_db')->table('clients_followup_log')->where('client_id',$row->client_id)
                         //->where('comp_id',$row->comp_id)->orderBy('id','DESC')->first();

        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
        
        $client = DB::connection('sales_db')->table('company_info')->where('comp_id', $row->comp_id)->first();

        $emp_details = DB::table('emp_basic_info')->where('emp_id', $row->exe_id)->first();
        if ($emp_details) {
            $emp_name = $emp_details->emp_fname . ' ' . ($emp_details->emp_lame ?? '');
            $manager_name = '';

            if ($emp_details->reporting_manager) {
                $manager = DB::table('emp_basic_info')->where('emp_id', $emp_details->reporting_manager)->first();
                $manager_name = $manager ? $manager->emp_fname . ' ' . ($manager->emp_lame ?? '') : '';
            }
        } else {
            $emp_name = '';
            $manager_name = '';
        }

        $data_array[] = [
            'id' => $row->package_id,
            'product' => $product->product_name ?? '',
            'service' => $service ?? '',
            'category' => $category ?? '',
            'group' => $group_names ?? '',
            'pkg_name' => $row->package_name ?? '',
            'client_name' => $client->client_name ?? '',
            'company'=>$client->business_name ?? '',
            'created_date' => $row->created_date ?? '',
            'emp_name' => $emp_name,
            'manager_name' => $manager_name,
            'pkg_type'=>$row->package_type??'',
            'package_status'=>$package_status??'',
            'client_id'=>$row->client_id,
            'mobile_no'=>$row->mobile_no??'',
            'package_start_date'=>$row->package_start_date ?? '',
            'package_end_date'=>$row->package_end_date ?? '',
            'expected_end_date'=>$row->package_end_expected_date ?? '',
            'total_leads'=>$row->total_lead ?? '',
            'sent_lead'=>$row->sent_lead ?? '',
            //'no_of_packages'=>$no_of_packages ?? '',
            'last_followup_date'=>$last_followup_date->created_date??''
        ];
    }

    return response()->json(['status' => 200, 'data' => $data_array, 'last_page' => $total_data->lastPage()]);

}



public function lead_sale_price(Request $request)
{
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager])
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif ($request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_id = 'RIMS1';

        $emp_managers = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id])
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $data = DB::connection('sales_db')->table('service_info')
        ->leftJoin('package_info', 'package_info.package_id', '=', 'service_info.package_id')
        ->select('service_info.category_id', 'service_info.total_lead', 'package_info.group_id', 'service_info.per_lead_price',
        'package_info.package_amount', 'package_info.created_date', 'package_info.exe_id', 
        'package_info.product_id', 'package_info.service_id');

    if ($request->product) {
        $data->where('package_info.product_id', $request->product);
    }
    if ($request->service) {
        $data->where('package_info.service_id', $request->service);
    }
    if ($request->group) {
        $data->whereRaw('FIND_IN_SET(?, package_info.group_id)', [$request->group]);
    }
    if ($request->category) {
        $data->where('service_info.category_id', $request->category);
    }
    if ($request->start_date && $request->end_date) {
        $data->whereDate('package_info.created_date', '>=', $request->start_date)
            ->whereDate('package_info.created_date', '<=', $request->end_date);
    } else {
        $data->whereDate('package_info.created_date', Carbon::now()->format('Y-m-d'));
            //->whereYear('package_info.created_date', Carbon::now()->year);
    }

    $data_array = $data->whereIn('package_info.exe_id', $emp_managers)->get();
    //return $data_array;

    $result = [];

    foreach ($data_array as $row) {
        $group_ids = explode(',', $row->group_id);
        foreach ($group_ids as $group_id) {
            if ($request->group && in_array($request->group, $group_ids)) {
                $groupCategoryKey = $request->group . '-' . $row->category_id;
            } else {
                $groupCategoryKey = $group_id . '-' . $row->category_id;
            }

            if (!isset($result[$groupCategoryKey])) {
                $result[$groupCategoryKey] = [
                    'total_lead_sum' => 0,
                    'num_of_packages' => 0,
                ];
            }

            $lead_contribution = $row->per_lead_price;
            $result[$groupCategoryKey]['total_lead_sum'] += $lead_contribution;
            $result[$groupCategoryKey]['num_of_packages'] += 1;
        }
    }

    foreach ($result as $key => $value) {
        if ($value['num_of_packages'] > 0) {
            $result[$key]['lead_sale_price'] = $value['total_lead_sum'] / $value['num_of_packages'];
        } else {
            $result[$key]['lead_sale_price'] = 0;
        }
    }

    $final_result = [];

    foreach ($result as $key => $value) {
        list($group_id, $category_id) = explode('-', $key);

        if ($request->group) {
            $group_name = DB::connection('sales_db')->table('group_names')
                ->where('group_id', $request->group)
                ->value('name');
        } else {
            $group_name = DB::connection('sales_db')->table('group_names')
                ->where('group_id', $group_id)
                ->value('name');
        }

        $category_name = DB::connection('sales_db')->table('product_category')
            ->where('id', $category_id)
            ->value('category_name');

        $final_result[] = [
            'group_id' => $group_id,
            'group_name' => $group_name,
            'category_id' => $category_id,
            'category_name' => $category_name,
            'lead_sale_price' => round($value['lead_sale_price']),
        ];
    }

    return  $final_result;

    
    $paginated_data = new \Illuminate\Pagination\LengthAwarePaginator(
        array_slice($final_result, ($request->page - 1) * 10, 10),
        count($final_result),
        10,
        $request->page,
        ['path' => url()->current()]
    );

    return response()->json([
        'status' => 200,
         'data' => $paginated_data,
         'last_page'=>$paginated_data->lastPage(),
    ]);
}



public function add_remark_on_followup_history(Request $request)
{

    $get_record = DB::connection('sales_db')->table('missing_followups_count')->where('id', $request->id)->first();

    if (!$get_record) {
        return response()->json(['status' => 404, 'message' => 'Record not found'], 404);
    }

    $get_emp_manager = DB::table('emp_basic_info')->where('emp_id', $get_record->created_by)->first();

    if (!$get_emp_manager) {
        return response()->json(['status' => 404, 'message' => 'Employee manager not found'], 404);
    }

    if ($request->emp_id == $get_record->created_by) {
        DB::connection('sales_db')->table('missing_followups_count')
            ->where('id', $request->id)
            ->update(['emp_remark' => $request->remark]);
    }  else if ($request->emp_id == 'RIMS1') {
        DB::connection('sales_db')->table('missing_followups_count')
            ->where('id', $request->id)
            ->update(['superadmin_remark' => $request->remark]);
    } else{
         DB::connection('sales_db')->table('missing_followups_count')
            ->where('id', $request->id)
            ->update(['manager_remark' => $request->remark]);
    }

    return response()->json(['status' => 200, 'message' => 'Remark added successfully']);
}


public function kra_kpi_seen_status($emp_id)
{
    DB::table('emp_notifications')->where('emp_id',$emp_id)->where('type','missing_kra_kpi')->update(['seen_status'=>1]);
    return response()->json(['status'=>200]);
}

public function due_amount_details(Request $request){
    $data_array = [];
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $data = DB::connection('sales_db')
    ->table('package_info')
    ->where('due_amount', '>', 0)
    ->where('due_date', '!=', '');

     if ($request->group) {
        $data->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);
    }
    if ($request->product) {
        $data->where('product_id', $request->product);
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
    }
    else{
        $data->where('due_date', '>=', Carbon::now()->subDays(40));
    }
     if($request->download){
        $query = $data->whereIn('exe_id', $emp_managers)->get();

     }
     else{
        $query = $data->whereIn('exe_id', $emp_managers)->paginate(10);

     }


    foreach ($query as $row) {
        $group_ids = explode(',', $row->group_id);
        $category_id = explode(',', $row->category_id);
        $service_id = explode(',', $row->service_id);

        $group_names = DB::connection('sales_db')->table('group_names')
            ->whereIn('group_id', $group_ids)
            ->pluck('name')
            ->implode(', ');

        $category = DB::connection('sales_db')->table('product_category')
            ->whereIn('id', $category_id)
            ->pluck('category_name')
            ->implode(', ');

        $service = DB::connection('sales_db')->table('product_service')
            ->whereIn('id', $service_id)
            ->pluck('service_name')
            ->implode(', ');

            $last_followup_date = DB::connection('sales_db')->table('clients_followup_log')->where('client_id',$row->client_id)
            ->where('comp_id',$row->comp_id)->orderBy('id','DESC')->first();

             $followup_taken = 'No'; 
            if ($last_followup_date && $last_followup_date->created_date) {
              if (Carbon::parse($last_followup_date->created_date)->format('Y-m-d') >= Carbon::parse($row->due_date)->format('Y-m-d')) {
                   $followup_taken = 'Yes';
                  }
                }

        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
        
        $client = DB::connection('sales_db')->table('company_info')->where('comp_id', $row->comp_id)->first();

        $emp_details = DB::table('emp_basic_info')->where('emp_id', $row->exe_id)->first();
        if ($emp_details) {
            $emp_name = $emp_details->emp_fname . ' ' . ($emp_details->emp_lame ?? '');
            $manager_name = '';

            if ($emp_details->reporting_manager) {
                $manager = DB::table('emp_basic_info')->where('emp_id', $emp_details->reporting_manager)->first();
                $manager_name = $manager ? $manager->emp_fname . ' ' . ($manager->emp_lame ?? '') : '';
            }
        } else {
            $emp_name = '';
            $manager_name = '';
        }

        $data_array[] = [
            'id' => $row->package_id,
            'product' => $product->product_name ?? '',
            'service' => $service ?? '',
            'category' => $category ?? '',
            'group' => $group_names ?? '',
            'pkg_name' => $row->package_name ?? '',
            'client_name' => $client->client_name ?? '',
            'company'=>$client->business_name ?? '',
            'created_date' => $row->created_date ?? '',
            'emp_name' => $emp_name,
            'manager_name' => $manager_name,
            'pkg_type'=>$row->package_type ??'',
            'amount'=>$row->due_amount ??'',
            'paid_amount'=>$row->paid_amount??'',
            'due_date'=>$row->due_date??'',
            'client_id'=>$row->client_id,
            'last_followup_date'=>$last_followup_date->created_date??" ",
            'followup_taken'=>$followup_taken??'',
            'mobile'=>$client->mobile_no??'',

        ];
    }
    if ($request->download = 'csv') {
        $csvData = array_map(function ($row) {
            unset($row['mobile']);
            return $row;
        }, $data_array);

        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="due_amount_details.csv"'];
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($csvData[0] ?? []));
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        fclose($output);

        return response()->stream(function () use ($csvData) {}, 200, $headers);
    }

    return response()->json(['status'=>200,'data'=>$data_array,'last_page'=>$query->lastPage()]);

}

public function get_manager_wise_collection(Request $request)
{

   

    $data = DB::table('emp_basic_info')
        ->where('desi_id', 38)
        ->where('emp_status', 1)
        ->get(['emp_id', 'emp_fname', 'emp_lame']);

    $teamMembersMapping = [];
    foreach ($data as $row) {
        $get_team = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$row->emp_id])
            ->where('dept_id', 3)
            ->where('status', 1)
            ->pluck('emp_id')
            ->toArray();

        $teamMembers = array_unique(array_merge($get_team, [$row->emp_id]));
        $teamMembersMapping[$row->emp_id] = $teamMembers;
    }

    $results = [];
    foreach ($teamMembersMapping as $managerId => $members) {
        $managerInfo = $data->firstWhere('emp_id', $managerId);
        $managerName = $managerInfo ? $managerInfo->emp_fname . ' ' . $managerInfo->emp_lame : 'Unknown';

        $active_packages = DB::connection('sales_db')->table('package_info')
                               ->where('package_status',1)->whereIn('exe_id',$members);

        $managerTotalQuery = DB::connection('sales_db')->table('payment_history')
                            ->leftJoin('company_info','company_info.comp_id','payment_history.comp_id')
            ->select(
                'company_info.group_id',
                'payment_history.product_id',
                DB::raw("'{$managerId}' as manager_id"),
                DB::raw("'{$managerName}' as manager_name"), 
                DB::raw('SUM(paid_amount) as total_paid'),
                DB::raw('SUM(CASE WHEN payment_for = "New" THEN paid_amount ELSE 0 END) as total_new'),
                DB::raw('SUM(CASE WHEN payment_for = "Renew" THEN paid_amount ELSE 0 END) as total_renew'),
                DB::raw('SUM(CASE WHEN payment_for = "Due Amount" THEN paid_amount ELSE 0 END) as total_due'),
                DB::raw('SUM(CASE WHEN payment_for = "Retention" THEN paid_amount ELSE 0 END) as total_retention')

            )
            ->whereIn('payment_history.exe_id', $members);
            //return $members;

            $total_app_packages = DB::connection('sales_db')->table('package_info')
                     ->leftJoin('payment_history', 'package_info.package_id', '=', 'payment_history.package_id')
                     ->selectRaw('
                             COUNT(DISTINCT package_info.package_id) as total_packages_from_app, 
                             COALESCE(SUM(payment_history.paid_amount), 0) as total_paid_amount_from_app
                      ')
                     ->where('package_info.created_by', 2) 
                     ->whereIn('package_info.exe_id', $members)
                     ->first();
             //return $total_app_packages;

            if ($request->start_date && $request->end_date) {
                 $managerTotalQuery->whereDate('payment_history.created_date', '>=',$request->start_date)
                 ->whereDate('payment_history.created_date', '<=', $request->end_date);

                 $active_packages->whereDate('created_date', '>=',$request->start_date)
                 ->whereDate('created_date', '<=', $request->end_date);
           } else { 
              $managerTotalQuery->whereMonth('payment_history.created_date', Carbon::now()->month)
              ->whereYear('payment_history.created_date', Carbon::now()->year);
            }

            if($request->group){
                $managerTotalQuery->where('company_info.group_id',$request->group);
                $active_packages->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);

            }

            if($request->product){
                $managerTotalQuery->where('payment_history.product_id',$request->product);
                $active_packages->where('product_id',$request->product);

            }


            $managerTotal = $managerTotalQuery->groupBy('manager_id')
            ->first();

            if (!$managerTotal) {
              $managerTotal = (object) [
                'manager_id' => $managerId,
                'manager_name' => $managerName,
                'total_paid' => 0,
                'total_new' => 0,
                'total_renew' => 0,
                'total_due' => 0,
                'total_retention' => 0,
                'active_packages'=>$active_packages->count(),
                'total_app_amount'=>$total_app_packages->total_paid_amount_from_app,
                'total_app_packages'=>$total_app_packages->total_packages_from_app,
            ];
        }

        if ($managerTotal) {
            $managerTotal->active_packages = $active_packages->count();
            $managerTotal->total_app_amount = $total_app_packages->total_paid_amount_from_app;
            $managerTotal->total_app_packages  = $total_app_packages->total_packages_from_app;

            $detailedQuery = DB::connection('sales_db')->table('payment_history')
                ->leftJoin('company_info', 'company_info.comp_id', 'payment_history.comp_id')
                ->leftJoin('group_names', 'group_names.group_id', 'company_info.group_id')
                // ->leftJoin('service_info','package_info.package_id','payment_history.package_id')
                // ->leftJoin('product_category','product_category.id','package_info.category_id')
                ->leftJoin('product','product.id','payment_history.product_id')
                ->select(
                    'group_names.group_id',
                    'group_names.name as group_name',
                    // 'product_category.category_name',
                    'product.product_name',
                    DB::raw('SUM(payment_history.paid_amount) as total_paid'),
                    DB::raw('SUM(CASE WHEN payment_for = "New" THEN payment_history.paid_amount ELSE 0 END) as total_new'),
                    DB::raw('SUM(CASE WHEN payment_for = "Renew" THEN payment_history.paid_amount ELSE 0 END) as total_renew'),
                    DB::raw('SUM(CASE WHEN payment_for = "Due Amount" THEN payment_history.paid_amount ELSE 0 END) as total_due'),
                    DB::raw('SUM(CASE WHEN payment_for = "Retention" THEN payment_history.paid_amount ELSE 0 END) as total_retention')
                )
                ->whereIn('payment_history.exe_id', $members);

                if ($request->start_date && $request->end_date) {
                    $detailedQuery->whereDate('payment_history.created_date', '>=',$request->start_date)
                    ->whereDate('payment_history.created_date', '<=', $request->end_date);
              } else { 
                $detailedQuery->whereMonth('payment_history.created_date', Carbon::now()->month)->whereYear('payment_history.created_date', Carbon::now()->year);
               }

             if ($request->group) {
                $detailedQuery->where('company_info.group_id', $request->group);
              }
                
               $detailedQuery =  $detailedQuery->groupBy('group_names.group_id', 'group_names.name','payment_history.product_id')
                ->get();

            $results[] = [
                'manager_id' => $managerId,
                'manager_name' => $managerName,
                'total' => $managerTotal,
                'details' => $detailedQuery,
            ];
        }
    }

    return response()->json(['status'=>200,'data'=>$results]);
}





public function view_manager_team_collection(Request $request)
{
    $get_team = DB::table('employee_managers')
        ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
        ->where('dept_id', 3)
        ->where('status', 1)
        ->pluck('emp_id')
        ->toArray();
    $get_team[] = $request->emp_id;
    $get_team = array_unique($get_team);

    $totalQuery = DB::connection('sales_db')->table('payment_history')
        ->leftJoin('company_info', 'company_info.comp_id', 'payment_history.comp_id')
        ->leftJoin('group_names', 'group_names.group_id', 'company_info.group_id')
        ->select(
            'payment_history.exe_id',
            DB::raw('SUM(paid_amount) as total_paid'),
            DB::raw('SUM(CASE WHEN payment_for = "New" THEN paid_amount ELSE 0 END) as total_new'),
            DB::raw('SUM(CASE WHEN payment_for = "Renew" THEN paid_amount ELSE 0 END) as total_renew'),
            DB::raw('SUM(CASE WHEN payment_for = "Due Amount" THEN paid_amount ELSE 0 END) as total_due'),
            DB::raw('SUM(CASE WHEN payment_for = "Retention" THEN paid_amount ELSE 0 END) as total_retention')
        )
        ->whereIn('payment_history.exe_id', $get_team);

    if ($request->group) {
        $totalQuery->where('group_names.group_id', $request->group);
    }

    if ($request->start_date && $request->end_date) {
        $totalQuery->whereDate('payment_history.created_date', '>=',$request->start_date)
        ->whereDate('payment_history.created_date', '<=', $request->end_date);
  } else { 
    $totalQuery->whereMonth('payment_history.created_date', Carbon::now()->month)->whereYear('payment_history.created_date', Carbon::now()->year);
   }

    $totalData = $totalQuery->groupBy('payment_history.exe_id')->get();
    $totalCollectionArray = [];
    foreach ($get_team as $empId) {

        $empName = DB::table('emp_basic_info')->where('emp_id', $empId)->first();
        $manager_name = DB::table('emp_basic_info')->where('emp_id', $empName->reporting_manager)->first();

        $totalCollectionArray[$empId] = [
            'emp_id' => $empId,
            'emp_name' => $empName->emp_fname . ' ' . $empName->emp_lame,
            'total_paid' => 0,
            'total_new' => 0,
            'total_renew' => 0,
            'total_due' => 0,
            'total_retention' => 0,
            'manager' =>$manager_name->emp_fname . ' ' . $manager_name->emp_lame,
            'details' => []
        ];
    }
    foreach ($totalData as $row) {
        $empName = DB::table('emp_basic_info')->where('emp_id', $row->exe_id)->first();
        $manager_name = DB::table('emp_basic_info')->where('emp_id', $empName->reporting_manager)->first();
        $totalCollectionArray[$row->exe_id] = [
            'emp_id' => $row->exe_id,
            'emp_name' => $empName->emp_fname . ' ' . $empName->emp_lame,
            'total_paid' => $row->total_paid,
            'total_new' => $row->total_new,
            'total_renew' => $row->total_renew,
            'total_due' => $row->total_due,
            'total_retention' => $row->total_retention,
            'manager'=>$manager_name->emp_fname . ' ' . $manager_name->emp_lame,
            'details' => []
        ];
    }
    $detailedQuery = DB::connection('sales_db')->table('payment_history')
        ->leftJoin('company_info', 'company_info.comp_id', 'payment_history.comp_id')
        ->leftJoin('group_names', 'group_names.group_id', 'company_info.group_id')
        //->leftJoin('package_info','package_info.package_id','payment_history.package_id')
        // ->leftJoin('product_category','product_category.id','package_info.category_id')
        ->leftJoin('product','product.id','payment_history.product_id')
        ->select(
            'payment_history.exe_id',
            'payment_for',
            'group_names.group_id',
            'group_names.name as group_name',
            'product.product_name',
            // 'product_category.category_name',
            DB::raw('SUM(payment_history.paid_amount) as total_paid'),
                    DB::raw('SUM(CASE WHEN payment_for = "New" THEN payment_history.paid_amount ELSE 0 END) as total_new'),
                    DB::raw('SUM(CASE WHEN payment_for = "Renew" THEN payment_history.paid_amount ELSE 0 END) as total_renew'),
                    DB::raw('SUM(CASE WHEN payment_for = "Due Amount" THEN payment_history.paid_amount ELSE 0 END) as total_due'),
                    DB::raw('SUM(CASE WHEN payment_for = "Retention" THEN payment_history.paid_amount ELSE 0 END) as total_retention')
        )
        ->whereIn('payment_history.exe_id', $get_team);

    if ($request->group) {
        $detailedQuery->where('group_names.group_id', $request->group);
    }

    if ($request->start_date && $request->end_date) {
        $detailedQuery->whereDate('payment_history.created_date', '>=',$request->start_date)
        ->whereDate('payment_history.created_date', '<=', $request->end_date);
  } else { 
    $detailedQuery->whereMonth('payment_history.created_date', Carbon::now()->month)->whereYear('payment_history.created_date', Carbon::now()->year);
   }

    $detailedData = $detailedQuery->groupBy('payment_history.exe_id', 'group_names.group_id', 'group_names.name','payment_history.product_id')->get();

    foreach ($detailedData as $row) {
        $empName = DB::table('emp_basic_info')->where('emp_id', $row->exe_id)->first();
        $empManager = DB::table('emp_basic_info')->where('emp_id', $empName->reporting_manager)->first();

        if (isset($totalCollectionArray[$row->exe_id])) {
            $totalCollectionArray[$row->exe_id]['details'][] = [
                'group' => $row->group_name,
                'new' => $row->total_new,
                'renew' => $row->total_renew,
                'due' => $row->total_due,
                'retention' => $row->total_retention,
                'emp_name' => $empName->emp_fname . ' ' . $empName->emp_lame,
                'manager_name' => $empManager ? $empManager->emp_fname . ' ' . $empManager->emp_lame : 'Unknown',
                'total_sum' => $row->total_paid,
                'product'=>$row->product_name,
            ];
        }
    }

    return response()->json([
        'status' => 200,
        'data' => array_values($totalCollectionArray),
    ]);
}

public function show_manager_wise_followup(Request $request){
    $data = DB::table('emp_basic_info')
        ->where('desi_id', 38)
        ->where('emp_status', 1)
        ->get(['emp_id', 'emp_fname', 'emp_lame']);

    $teamMembersMapping = [];
    foreach ($data as $row) {
        $get_team = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$row->emp_id])
            ->where('dept_id', 3)
            ->where('status', 1)
            ->pluck('emp_id')
            ->toArray();

        $teamMembers = array_unique(array_merge($get_team, [$row->emp_id]));
        $teamMembersMapping[$row->emp_id] = $teamMembers;
    }

    $results = [];
    foreach ($teamMembersMapping as $managerId => $members) {
        $managerInfo = $data->firstWhere('emp_id', $managerId);
        $managerName = $managerInfo ? $managerInfo->emp_fname . ' ' . $managerInfo->emp_lame : 'Unknown';

        $managerTotalQuery = DB::connection('sales_db')->table('clients_followup_log')
                            ->leftJoin('group_names','group_names.group_id','clients_followup_log.city_id')
                            ->leftJoin('pre_package','pre_package.id','clients_followup_log.refer_package_id')
                            ->leftJoin('package_info','package_info.client_id','clients_followup_log.client_id')
                            

            ->select(
                DB::raw("COUNT(CASE WHEN DATE(package_info.created_date) >= clients_followup_log.next_followup_date THEN 1 END) as matched_count"),
                DB::raw("'{$managerId}' as manager_id"),
                DB::raw("'{$managerName}' as manager_name"),
                DB::raw('SUM(DISTINCT pre_package.package_price) as total_paid'),
                DB::raw("COUNT(DISTINCT clients_followup_log.id) as id_count"),
                
            )
            ->whereIn('clients_followup_log.created_by', $members)->whereIn('followup_id',[6,9]);

            if ($request->start_date && $request->end_date) {
                 $managerTotalQuery->whereDate('clients_followup_log.next_followup_date', '>=',$request->start_date)
                 ->whereDate('clients_followup_log.next_followup_date', '<=', $request->end_date);
           } else { 
              $managerTotalQuery->whereMonth('clients_followup_log.next_followup_date', Carbon::now()->month)->whereYear('clients_followup_log.next_followup_date', Carbon::now()->year);
            }

            if($request->group){
                $managerTotalQuery->where('clients_followup_log.city_id',$request->group);

            }

            if($request->product){
                $managerTotalQuery->where('clients_followup_log.product_id',$request->product);

            }
            if($request->category){
                $managerTotalQuery->where('clients_followup_log.category_id',$request->category);

            }


            $managerTotal = $managerTotalQuery->groupBy('manager_id')
            ->first();

            if (!$managerTotal) {
              $managerTotal = (object) [
                'manager_id' => $managerId,
                'manager_name' => $managerName,
                'total_paid' => 0,
                'id_count' => 0,
                'matched_count'=>0,
            ];
        }

        if ($managerTotal) {
            $detailedQuery = DB::connection('sales_db')->table('clients_followup_log')
                            ->leftJoin('group_names','group_names.group_id','clients_followup_log.city_id')
                            ->leftJoin('pre_package','pre_package.id','clients_followup_log.refer_package_id')
                            ->leftJoin('product','product.id','clients_followup_log.product_id')
                            ->leftJoin('product_category','product_category.id','clients_followup_log.category_id')
                            ->select(
                           //'group_names.name',
                           'group_names.name as group_name',
                           'product_category.category_name',
                           'product.product_name',
                            DB::raw('SUM(pre_package.package_price) as total_paid'),
                            DB::raw('COUNT(clients_followup_log.id) as id_count')
                            
                        
                )
                ->whereIn('clients_followup_log.created_by', $members)->whereIn('followup_id',[6,9]);

                if ($request->start_date && $request->end_date) {
                    $detailedQuery->whereDate('clients_followup_log.next_followup_date', '>=',$request->start_date)
                    ->whereDate('clients_followup_log.next_followup_date', '<=', $request->end_date);
              } else { 
                 $detailedQuery->whereMonth('clients_followup_log.next_followup_date', Carbon::now()->month)->whereYear('clients_followup_log.next_followup_date', Carbon::now()->year);
               }
   
               if($request->group){
                   $detailedQuery->where('clients_followup_log.city_id',$request->group);
   
               }
   
               if($request->product){
                   $detailedQuery->where('clients_followup_log.product_id',$request->product);
   
               }
               if($request->category){
                   $detailedQuery->where('clients_followup_log.category_id',$request->category);
   
               }
                
               $detailedQuery =  $detailedQuery->groupBy('clients_followup_log.product_id','clients_followup_log.category_id','clients_followup_log.city_id')
                ->get();

            $results[] = [
                'manager_id' => $managerId,
                'manager_name' => $managerName,
                'total' => $managerTotal,
                'details' => $detailedQuery,
            ];
        }
    }

    return response()->json(['status'=>200,'data'=>$results]);

}

public function show_team_wise_payments_followups(Request $request)
{
    $get_team = DB::table('employee_managers')
        ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
        ->where('dept_id', 3)
        ->where('status', 1)
        ->pluck('emp_id')
        ->toArray();

    $get_team[] = $request->emp_id;
    $get_team = array_unique($get_team);

    $totalQuery = DB::connection('sales_db')->table('clients_followup_log')
        ->leftJoin('group_names', 'group_names.group_id', '=', 'clients_followup_log.city_id')
        ->leftJoin('pre_package', 'pre_package.id', '=', 'clients_followup_log.refer_package_id')
        ->leftJoin('package_info', 'package_info.client_id', '=', 'clients_followup_log.client_id')
        ->select(
            DB::raw("COUNT(CASE WHEN DATE(package_info.created_date) = clients_followup_log.next_followup_date THEN 1 END) as matched_count"),
            DB::raw('SUM(DISTINCT pre_package.package_price) as total_paid'),
            DB::raw("COUNT(DISTINCT clients_followup_log.id) as id_count"),
            'clients_followup_log.created_by'
        )
        ->whereIn('clients_followup_log.created_by', $get_team)
        ->whereIn('followup_id', [6, 9]);

    if ($request->start_date && $request->end_date) {
        $totalQuery->whereDate('clients_followup_log.next_followup_date', '>=', $request->start_date)
            ->whereDate('clients_followup_log.next_followup_date', '<=', $request->end_date);
    } else {
        $totalQuery->whereMonth('clients_followup_log.next_followup_date', Carbon::now()->month)
            ->whereYear('clients_followup_log.next_followup_date', Carbon::now()->year);
    }

    if ($request->group) {
        $totalQuery->where('clients_followup_log.city_id', $request->group);
    }

    if ($request->product) {
        $totalQuery->where('clients_followup_log.product_id', $request->product);
    }

    if ($request->category) {
        $totalQuery->where('clients_followup_log.category_id', $request->category);
    }

    $totalData = $totalQuery->groupBy('clients_followup_log.created_by')->get();

    $totalCollectionArray = [];
    foreach ($get_team as $empId) {
        $empName = DB::table('emp_basic_info')->where('emp_id', $empId)->first();
        $manager_name = DB::table('emp_basic_info')->where('emp_id', $empName->reporting_manager)->first();

        $totalCollectionArray[$empId] = [
            'emp_id' => $empId,
            'emp_name' => $empName->emp_fname . ' ' . $empName->emp_lame,
            'total_paid' => 0,
            'id_count' => 0,
            'matched_count' => 0,
            'manager' => $manager_name->emp_fname . ' ' . $manager_name->emp_lame,
            'details' => []
        ];
    }

    foreach ($totalData as $row) {
        if (isset($totalCollectionArray[$row->created_by])) {
            $totalCollectionArray[$row->created_by]['total_paid'] = $row->total_paid;
            $totalCollectionArray[$row->created_by]['id_count'] = $row->id_count;
            $totalCollectionArray[$row->created_by]['matched_count'] = $row->matched_count;
        }
    }

    $detailedQuery = DB::connection('sales_db')->table('clients_followup_log')
        ->leftJoin('group_names', 'group_names.group_id', '=', 'clients_followup_log.city_id')
        ->leftJoin('pre_package', 'pre_package.id', '=', 'clients_followup_log.refer_package_id')
        ->leftJoin('product', 'product.id', '=', 'clients_followup_log.product_id')
        ->leftJoin('product_category', 'product_category.id', '=', 'clients_followup_log.category_id')
        ->select(
            'clients_followup_log.created_by',
            'group_names.name as group_name',
            'product_category.category_name',
            'product.product_name',
            DB::raw('SUM(pre_package.package_price) as total_paid'),
            DB::raw('COUNT(clients_followup_log.id) as id_count')
        )
        ->whereIn('clients_followup_log.created_by', $get_team)
        ->whereIn('followup_id', [6, 9]);

    if ($request->start_date && $request->end_date) {
        $detailedQuery->whereDate('clients_followup_log.next_followup_date', '>=', $request->start_date)
            ->whereDate('clients_followup_log.next_followup_date', '<=', $request->end_date);
    } else {
        $detailedQuery->whereMonth('clients_followup_log.next_followup_date', Carbon::now()->month)
            ->whereYear('clients_followup_log.next_followup_date', Carbon::now()->year);
    }

    if ($request->group) {
        $detailedQuery->where('clients_followup_log.city_id', $request->group);
    }

    if ($request->product) {
        $detailedQuery->where('clients_followup_log.product_id', $request->product);
    }

    if ($request->category) {
        $detailedQuery->where('clients_followup_log.category_id', $request->category);
    }

    $detailedData = $detailedQuery->groupBy('clients_followup_log.created_by', 'clients_followup_log.product_id', 'clients_followup_log.category_id', 'clients_followup_log.city_id')->get();

    foreach ($detailedData as $row) {
        if (isset($totalCollectionArray[$row->created_by])) {
            $totalCollectionArray[$row->created_by]['details'][] = [
                'group' => $row->group_name,
                'product' => $row->product_name,
                'category' => $row->category_name,
                'total_sum' => $row->total_paid,
                'total_id' => $row->id_count,
            ];
        }
    }

    return response()->json([
        'status' => 200,
        'data' => array_values($totalCollectionArray),
    ]);
}

public function package_inactive_notifications(Request $request) {
    //$emp_id = 'RIMS';
    $get_team = DB::table('employee_managers')
        ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
        ->where('dept_id', 3)
        ->where('status', 1)
        ->pluck('emp_id')
        ->toArray();

    $get_team[] = $request->emp_id;
    $get_team = array_unique($get_team);

    $currentDate = Carbon::now();

    $startOfCurrentWeek = $currentDate->startOfWeek();
    $endOfCurrentWeek = $currentDate->copy()->endOfWeek();

    $startOfPreviousWeek = $startOfCurrentWeek->copy()->subWeek();
    $endOfPreviousWeek = $startOfPreviousWeek->copy()->endOfWeek();

    $inactiveCounts = DB::connection('sales_db')->table('package_info')
        ->where(function($query) use ($startOfCurrentWeek, $endOfCurrentWeek, $startOfPreviousWeek, $endOfPreviousWeek) {
            $query->whereBetween('package_end_expected_date', [$startOfCurrentWeek, $endOfCurrentWeek])
                  ->orWhereBetween('package_end_date', [$startOfPreviousWeek, $endOfPreviousWeek]);
        })
        ->whereIn('exe_id', $get_team)
        ->get(['exe_id']);

    if ($inactiveCounts->count() > 0) {
        foreach ($inactiveCounts as $row) {
            $emp_details = DB::table('emp_basic_info')->where('emp_id', $row->exe_id)->first();
            
            if ($emp_details) {
                $reporting_manager = $emp_details->reporting_manager;
                $emp_array = [$row->exe_id, $reporting_manager,'RIMS1'];

                foreach ($emp_array as $emp_ids) {
                    $check_record_exists = DB::table('emp_notifications')
                        ->where('emp_id', $emp_ids)
                        ->where('type', 'package_inactive')
                        ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
                        ->exists();

                    if (!$check_record_exists) {
                        DB::table('emp_notifications')->insert([
                            'emp_id' => $emp_ids,
                            'type' => 'package_inactive',
                            'notification' => 'Click  To View Inactive Packages Details',
                        ]);
                    }
                }
            }
        }
    }
}
public function show_inactive_packages_details(Request $request){
    $data_array = [];
    $get_team = DB::table('employee_managers')
        ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
        ->where('dept_id', 3)
        ->where('status', 1)
        ->pluck('emp_id')
        ->toArray();

    $get_team[] = $request->emp_id;
    $get_team = array_unique($get_team);

    $currentDate = Carbon::now();

    $startOfCurrentWeek = $currentDate->startOfWeek();
    $endOfCurrentWeek = $currentDate->copy()->endOfWeek();

    $startOfPreviousWeek = $startOfCurrentWeek->copy()->subWeek();
    $endOfPreviousWeek = $startOfPreviousWeek->copy()->endOfWeek();

    $inactiveCounts = DB::connection('sales_db')->table('package_info')
        ->where(function($query) use ($startOfCurrentWeek, $endOfCurrentWeek, $startOfPreviousWeek, $endOfPreviousWeek) {
            $query->whereBetween('package_end_expected_date', [$startOfCurrentWeek, $endOfCurrentWeek])
                  ->orWhereBetween('package_end_date', [$startOfPreviousWeek, $endOfPreviousWeek]);
        })
        ->whereIn('exe_id', $get_team);

        if ($request->group) {
            $inactiveCounts->whereRaw('FIND_IN_SET(?, package_info.group_id)', [$request->group]);
        }
        if ($request->product) {
            $inactiveCounts->where('package_info.product_id', $request->product);
        }
        if ($request->service) {
            $inactiveCounts->whereRaw('FIND_IN_SET(?, package_info.service_id)', [$request->service]);
        }
        if ($request->category) {
            $inactiveCounts->whereRaw('FIND_IN_SET(?, package_info.category_id)', [$request->category]);
        }
        if($request->package_type){
            $inactiveCounts->where('package_info.package_status', $request->package_type);
          }

        $total_data = $inactiveCounts->paginate(10);

    foreach($total_data as $row){
        $group_ids = explode(',', $row->group_id);
        $category_id = explode(',', $row->category_id);
        $service_id = explode(',', $row->service_id);

        $group_names = DB::connection('sales_db')->table('group_names')
            ->whereIn('group_id', $group_ids)
            ->pluck('name')
            ->implode(', ');

        $category = DB::connection('sales_db')->table('product_category')
            ->whereIn('id', $category_id)
            ->pluck('category_name')
            ->implode(', ');

        $service = DB::connection('sales_db')->table('product_service')
            ->whereIn('id', $service_id)
            ->pluck('service_name')
            ->implode(', ');

            $package_status = ' ';
        if($row->package_status ==1){
            $package_status = 'Active';

        }
        if($row->package_status ==2){
            $package_status = 'Stop By Admin,Sales,Client';

        }
        if($row->package_status ==3){
            $package_status = 'Stop Due To Due Payment ';

        }
        if($row->package_status ==4){
            $package_status = 'Expiry';

        }
        $no_of_packages = DB::connection('sales_db')->table('package_info')->where('client_id',$row->client_id)
                         ->where('comp_id',$row->comp_id)->count();

        $last_followup_date = DB::connection('sales_db')->table('clients_followup_log')->where('client_id',$row->client_id)
                         ->where('comp_id',$row->comp_id)->orderBy('id','DESC')->first();

        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
        
        $client = DB::connection('sales_db')->table('company_info')->where('comp_id', $row->comp_id)->first();

        $emp_details = DB::table('emp_basic_info')->where('emp_id', $row->exe_id)->first();
        if ($emp_details) {
            $emp_name = $emp_details->emp_fname . ' ' . ($emp_details->emp_lame ?? '');
            $manager_name = '';

            if ($emp_details->reporting_manager) {
                $manager = DB::table('emp_basic_info')->where('emp_id', $emp_details->reporting_manager)->first();
                $manager_name = $manager ? $manager->emp_fname . ' ' . ($manager->emp_lame ?? '') : '';
            }
        } else {
            $emp_name = '';
            $manager_name = '';
        }

        $data_array[] = [
            'id' => $row->package_id,
            'product' => $product->product_name ?? '',
            'service' => $service ?? '',
            'category' => $category ?? '',
            'group' => $group_names ?? '',
            'pkg_name' => $row->package_name ?? '',
            'client_name' => $client->client_name ?? '',
            'company'=>$client->business_name ?? '',
            'created_date' => $row->created_date ?? '',
            'emp_name' => $emp_name,
            'manager_name' => $manager_name,
            'pkg_type'=>$row->package_type??'',
            'package_status'=>$package_status??'',
            'client_id'=>$row->client_id,
            'mobile_no'=>$row->mobile_no??'',
            'package_start_date'=>$row->package_start_date ?? '',
            'package_end_date'=>$row->package_end_date ?? '',
            'expected_end_date'=>$row->package_end_expected_date ?? '',
            'total_leads'=>$row->total_lead ?? '',
            'sent_lead'=>$row->sent_lead ?? '',
            'no_of_packages'=>$no_of_packages ?? '',
            'last_followup_date'=>$last_followup_date->created_date??''
        ];
    }

    return response()->json(['status' => 200, 'data' => $data_array, 'last_page' => $total_data->lastPage()]);
        
}

public function update_inactive_client_notification_seen_status($emp_id){
    DB::table('emp_notifications')->where('emp_id',$emp_id)->where('type','package_inactive')->update(['seen_status'=>1]);
    return response()->json(['status'=>200]);
}

public function seen_status_of_payment_approval(Request $request){
    DB::table('emp_notifications')->where('emp_id',$request->emp_id)
    ->where('type','payment_approved/cancel')->where('client_id',$request->client_id)->update(['seen_status'=>1]);
    return response()->json(['status'=>200]);
}

public function recent_inactive_packages(Request $request){
    //$emp_id = 'RIMS1';

    $data_array = [];
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $data = DB::connection('sales_db')->table('package_info')
             ->where('package_status',4)
             ->where('package_end_date', '>=', Carbon::now()->subDays(5));

    if ($request->group) {
        $data->whereRaw('FIND_IN_SET(?, package_info.group_id)', [$request->group]);
    }
    if ($request->product) {
        $data->where('package_info.product_id', $request->product);
    }
    if ($request->service) {
        $data->whereRaw('FIND_IN_SET(?, package_info.service_id)', [$request->service]);
    }
    if ($request->category) {
        $data->whereRaw('FIND_IN_SET(?, package_info.category_id)', [$request->category]);
     }
     $total_data =  $data->whereIn('exe_id', $emp_managers)->paginate(10);

     foreach($total_data as $row){
         $group_ids = explode(',', $row->group_id);
         $category_id = explode(',', $row->category_id);
         $service_id = explode(',', $row->service_id);
 
         $group_names = DB::connection('sales_db')->table('group_names')
             ->whereIn('group_id', $group_ids)
             ->pluck('name')
             ->implode(', ');
 
         $category = DB::connection('sales_db')->table('product_category')
             ->whereIn('id', $category_id)
             ->pluck('category_name')
             ->implode(', ');
 
         $service = DB::connection('sales_db')->table('product_service')
             ->whereIn('id', $service_id)
             ->pluck('service_name')
             ->implode(', ');
 
             $package_status = ' ';

         if($row->package_status ==1){
             $package_status = 'Active';
 
         }
         if($row->package_status ==2){
             $package_status = 'Stop By Admin,Sales,Client';
 
         }
         if($row->package_status ==3){
             $package_status = 'Stop Due To Due Payment ';
 
         }
         if($row->package_status ==4){
             $package_status = 'Expiry';
 
         }
         $no_of_packages = DB::connection('sales_db')->table('package_info')->where('client_id',$row->client_id)
                          ->where('comp_id',$row->comp_id)->count();
 
         $last_followup_date = DB::connection('sales_db')->table('clients_followup_log')->where('client_id',$row->client_id)
                          ->where('comp_id',$row->comp_id)->orderBy('id','DESC')->first();
        //return $last_followup_date->created_date;

        $followup_taken = 'No'; 

        if ($last_followup_date && $last_followup_date->created_date) {
               if (Carbon::parse($last_followup_date->created_date)->format('Y-m-d') >= Carbon::parse($row->package_end_date)->format('Y-m-d')) {
                $followup_taken = 'Yes';
            }
           }
 
         $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
         
         $client = DB::connection('sales_db')->table('company_info')->where('comp_id', $row->comp_id)->first();
 
         $emp_details = DB::table('emp_basic_info')->where('emp_id', $row->exe_id)->first();
         if ($emp_details) {
             $emp_name = $emp_details->emp_fname . ' ' . ($emp_details->emp_lame ?? '');
             $manager_name = '';
 
             if ($emp_details->reporting_manager) {
                 $manager = DB::table('emp_basic_info')->where('emp_id', $emp_details->reporting_manager)->first();
                 $manager_name = $manager ? $manager->emp_fname . ' ' . ($manager->emp_lame ?? '') : '';
             }
         } else {
             $emp_name = '';
             $manager_name = '';
         }
 
         $data_array[] = [
             'id' => $row->package_id,
             'product' => $product->product_name ?? '',
             'service' => $service ?? '',
             'category' => $category ?? '',
             'group' => $group_names ?? '',
             'pkg_name' => $row->package_name ?? '',
             'client_name' => $client->client_name ?? '',
             'company'=>$client->business_name ?? '',
             'created_date' => $row->created_date ?? '',
             'emp_name' => $emp_name,
             'manager_name' => $manager_name,
             'pkg_type'=>$row->package_type??'',
             'package_status'=>$package_status??'',
             'client_id'=>$row->client_id,
             'mobile_no'=>$client->mobile_no??'',
             'package_start_date'=>$row->package_start_date ?? '',
             'package_end_date'=>$row->package_end_date ?? '',
             'expected_end_date'=>$row->package_end_expected_date ?? '',
             'total_leads'=>$row->total_lead ?? '',
             'sent_lead'=>$row->sent_lead ?? '',
             'no_of_packages'=>$no_of_packages ?? '',
             'last_followup_date'=>$last_followup_date->created_date??'',
             'followup_taken'=>$followup_taken??''
         ];
     }
 
     return response()->json(['status' => 200, 'data' => $data_array, 'last_page' => $total_data->lastPage()]);
 }
  
 public function get_mature_followups(Request $request)
{
    $emp_id = 'RIMS1';
    $data_array = [];

    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function ($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function ($query) use ($emp_id) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $query = DB::connection('sales_db')
        ->table('clients_followup_log as cfl')
        ->leftJoin('package_info as pi', function ($join) {
            $join->on('pi.client_id', '=', 'cfl.client_id')
                ->on('pi.comp_id', '=', 'cfl.comp_id')
                ->whereRaw('pi.created_date >= cfl.next_followup_date');
        })
        ->whereIn('cfl.followup_id', [6, 9, 7, 5])
        ->whereIn('cfl.created_by', $emp_managers);

    if ($request->product) {
        $query->where('cfl.product_id', $request->product);
    }
    if ($request->service) {
        $query->where('cfl.service_id', $request->service);
    }
    if ($request->category) {
        $query->where('cfl.category_id', $request->category);
    }
    if ($request->group) {
        $query->where('cfl.city_id', $request->group);
    }
    if ($request->start_date && $request->end_date) {
        $query->whereBetween('cfl.next_followup_date', [$request->start_date, $request->end_date]);
    } else {
        $query->whereDate('cfl.next_followup_date', Carbon::now()->format('Y-m-d'));
    }

    // Fetch paginated data
    $data_query = $query->select(
        'cfl.*',
        'pi.package_name',
        'pi.created_date as package_created_date'
    )->paginate(10);

    // Process the results
    foreach ($data_query as $row) {
        if ($row->package_name) {
            $group_name = DB::connection('sales_db')
                ->table('group_names')
                ->where('group_id', $row->city_id)
                ->first();
            $product = DB::connection('sales_db')
                ->table('product')
                ->where('id', $row->product_id)
                ->first();
            $category = DB::connection('sales_db')
                ->table('product_category')
                ->where('id', $row->category_id)
                ->first();
            $service = DB::connection('sales_db')
                ->table('product_service')
                ->where('id', $row->service_id)
                ->first();
            $company = DB::connection('sales_db')
                ->table('company_info')
                ->where('comp_id', $row->comp_id)
                ->first();
            $emp_details = DB::table('emp_basic_info')
                ->where('emp_id', $row->created_by)
                ->first();

            $emp_name = $emp_details
                ? $emp_details->emp_fname . ' ' . ($emp_details->emp_lame ?? '')
                : '';
            $manager_name = '';

            if ($emp_details && $emp_details->reporting_manager) {
                $manager = DB::table('emp_basic_info')
                    ->where('emp_id', $emp_details->reporting_manager)
                    ->first();
                $manager_name = $manager
                    ? $manager->emp_fname . ' ' . ($manager->emp_lame ?? '')
                    : '';
            }

            $data_array[] = [
                'product' => $product->product_name ?? '',
                'category' => $category->category_name ?? '',
                'service' => $service->service_name ?? '',
                'group' => $group_name->name ?? '',
                'company' => $company->business_name ?? '',
                'client' => $company->client_name ?? '',
                'emp_name' => $emp_name,
                'manager_name' => $manager_name,
                'package_created_date' => $row->package_created_date ?? '',
                'followup_date' => $row->next_followup_date ?? '',
                'package_name' => $row->package_name ?? '',
                'client_id' => $row->client_id ?? '',
            ];
        }
    }

    return response()->json([
        'status' => 200,
        'data' => $data_array,
        'last_page' => $data_query->lastPage(),
    ]);
}

 public function sales_upcoming_renewal_data(Request $request)
{
    $emp_id = $request->emp_id;
    $data_array = [];

    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function ($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function ($query) use ($emp_id) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $emp_id;
        $emp_managers = array_unique($emp_managers);
    }
     $startDate = Carbon::now()->startOfDay();
     $endDate = Carbon::now()->addDays(5)->endOfDay();

   $records = DB::connection('sales_db')->table('package_info')
    ->where('package_status', 1) 
    ->where(function ($query) use ($startDate, $endDate) { 
        $query->where(function ($innerQuery) {
            $innerQuery->where('package_duration', 1)
                       ->whereRaw('total_lead - sent_lead = 10');
        })
        ->orWhere(function ($innerQuery) use ($startDate, $endDate) { 
            $innerQuery->where('package_duration', 2)
                       ->where('package_end_expected_date', '!=', '')
                       ->whereBetween('package_end_expected_date', [$startDate, $endDate]);
        });
    });
    
    if ($request->group) {
        $records->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);
    }
    if ($request->product) {
        $records->where('product_id', $request->product);
    }
    if ($request->service) {
        $records->whereRaw('FIND_IN_SET(?, service_id)', [$request->service]);
    }
    if ($request->category) {
        $records->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);
    }
    if ($request->start_date && $request->end_date) {
        $records->whereBetween('created_date', [$request->start_date, $request->end_date]);
    } else {
        $records->whereYear('created_date', Carbon::now()->year)
                ->whereMonth('created_date', Carbon::now()->month);
    }
    

    $total_data = $records->whereIn('exe_id',$emp_managers)->paginate(10);

    foreach($total_data as $row){
        $group_ids = explode(',', $row->group_id);
         $category_id = explode(',', $row->category_id);
         $service_id = explode(',', $row->service_id);
 
         $group_names = DB::connection('sales_db')->table('group_names')
             ->whereIn('group_id', $group_ids)
             ->pluck('name')
             ->implode(', ');
 
         $category = DB::connection('sales_db')->table('product_category')
             ->whereIn('id', $category_id)
             ->pluck('category_name')
             ->implode(', ');
 
         $service = DB::connection('sales_db')->table('product_service')
             ->whereIn('id', $service_id)
             ->pluck('service_name')
             ->implode(', ');
 
             $package_status = ' ';

         if($row->package_status ==1){
             $package_status = 'Active';
 
         }
         if($row->package_status ==2){
             $package_status = 'Stop By Admin,Sales,Client';
 
         }
         if($row->package_status ==3){
             $package_status = 'Stop Due To Due Payment ';
 
         }
         if($row->package_status ==4){
             $package_status = 'Expiry';
 
         }
         $blance_lead = $row->total_lead - $row->sent_lead;
         if($blance_lead>0){
            $blance_lead_total =  $blance_lead;

         }
         else{
            $blance_lead_total = 0;
         }
        $package_duration = DB::connection('sales_db')->table('package_duration')->where('id',$row->package_duration)->first();


         
         //$no_of_packages = DB::connection('sales_db')->table('package_info')->where('client_id',$row->client_id)
                          //->where('comp_id',$row->comp_id)->count();
 
         //$last_followup_date = DB::connection('sales_db')->table('clients_followup_log')->where('client_id',$row->client_id)
                          //->where('comp_id',$row->comp_id)->orderBy('id','DESC')->first();
        //return $last_followup_date->created_date;

        //$followup_taken = 'No'; 

        // if ($last_followup_date && $last_followup_date->created_date) {
        //        if (Carbon::parse($last_followup_date->created_date)->format('Y-m-d') >= Carbon::parse($row->package_end_date)->format('Y-m-d')) {
        //         $followup_taken = 'Yes';
        //     }
        //    }
 
         $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
         
         $client = DB::connection('sales_db')->table('company_info')->where('comp_id', $row->comp_id)->first();
 
         $emp_details = DB::table('emp_basic_info')->where('emp_id', $row->exe_id)->first();
         if ($emp_details) {
             $emp_name = $emp_details->emp_fname . ' ' . ($emp_details->emp_lame ?? '');
             $manager_name = '';
 
             if ($emp_details->reporting_manager) {
                 $manager = DB::table('emp_basic_info')->where('emp_id', $emp_details->reporting_manager)->first();
                 $manager_name = $manager ? $manager->emp_fname . ' ' . ($manager->emp_lame ?? '') : '';
             }
         } else {
             $emp_name = '';
             $manager_name = '';
         }
 
         $data_array[] = [
             'id' => $row->package_id,
             'product' => $product->product_name ?? '',
             'service' => $service ?? '',
             'category' => $category ?? '',
             'group' => $group_names ?? '',
             'pkg_name' => $row->package_name ?? '',
             'client_name' => $client->client_name ?? '',
             'company'=>$client->business_name ?? '',
             'created_date' => $row->created_date ?? '',
             'emp_name' => $emp_name,
             'manager_name' => $manager_name,
             'pkg_type'=>$row->package_type??'',
             'package_status'=>$package_status??'',
             'client_id'=>$row->client_id,
             'mobile_no'=>$client->mobile_no??'',
             'package_start_date'=>$row->package_start_date ?? '',
             'package_end_date'=>$row->package_end_date ?? '',
             'expected_end_date'=>$row->package_end_expected_date ?? '',
             'total_leads'=>$row->total_lead ?? '',
             'sent_lead'=>$row->sent_lead ?? '',
             'blance_lead'=>$blance_lead_total,
             'pkg_duration'=>$package_duration->name??'',
             'price'=>$row->package_amount??'',
             //'no_of_packages'=>$no_of_packages ?? '',
             //'last_followup_date'=>$last_followup_date->created_date??'',
             //'followup_taken'=>$followup_taken??''
         ];

     }
     if ($request->download == 'csv') {
        $csvData = array_map(function ($row) {
            unset($row['mobile_no']); // Exclude mobile field
            return $row;
        }, $data_array);

        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="due_amount_details.csv"'];
        $output = fopen('php://output', 'w');
        fputcsv($output, array_keys($csvData[0] ?? []));
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        fclose($output);

        return response()->stream(function () use ($csvData) {}, 200, $headers);
    }
 
     return response()->json(['status' => 200, 'data' => $data_array, 'last_page' => $total_data->lastPage()]);

    }

    public function lead_sale_price_sum(Request $request)
{
    $data = DB::connection('sales_db')
        ->table('service_info')
        ->join('package_info', 'package_info.package_id', '=', 'service_info.package_id')
        ->selectRaw('
            package_info.package_id,
            package_info.package_amount,
            service_info.total_lead
        ') 
        //->whereDate('package_info.created_date', Carbon::now()->format('Y-m-d'))
        ->where('package_info.package_amount', '!=', '')
        ->where('service_info.total_lead', '!=', '');

    if ($request->product) {
        $data->where('package_info.product_id', $request->product);
    }

    if ($request->category) {
        $data->where('service_info.category_id', $request->category);
    }

    if ($request->service) {
        $data->where('service_info.service_id', $request->service);
    }

    if ($request->group) {
        $data->where('package_info.group_id', $request->group);
    }
    if($request->start_date && $request->end_date){
        $data->whereDate('package_info.created_date', '>=', $request->start_date)
              ->whereDate('package_info.created_date', '<=', $request->end_date);
    }
    else{
        $data->whereMonth('package_info.created_date', Carbon::now()->month)
        ->whereYear('package_info.created_date', Carbon::now()->year);

    }

    $total_data = $data->get();
    //return $total_data;

    $totalSalePrice = 0;
    $totalLeads = 0;
    $totalPrice = 0;
    $packageData = [];
    $packageCount = 0; 

    foreach ($total_data as $row) {
        if (!isset($packageData[$row->package_id])) {
            $packageData[$row->package_id] = [
                'total_amount' => $row->package_amount,
                'total_lead' => $row->total_lead,
            ];
            $packageCount++; 
        } else {
            $packageData[$row->package_id]['total_lead'] += $row->total_lead;
        }
    }

    foreach ($packageData as $packageId => $data) {
        $totalPrice += $data['total_amount'];

        if ($data['total_lead'] > 0) {
            $totalSalePrice += $data['total_amount'] / $data['total_lead'];
        }

        $totalLeads += $data['total_lead'];
    }

    if ($packageCount > 0) {
        $totalSalePrice = $totalSalePrice / $packageCount;
    }

    return response()->json([
        'status' => 'success',
        'total_sale_price' => round($totalSalePrice),
        'total_lead' => $totalLeads,
        'price' => $totalPrice,
        'package_count' => $packageCount,
    ]);
}

//  public function category_loss(Request $request) {

//     $data = DB::connection('sales_db')->table('enquiry_info')
//            ->where('sent_count','<', 4)
//            ->whereIn('followup_status',[22,23]);

//     $extra_sent = DB::connection('sales_db')->table('service_info')
//         ->join('package_info', 'package_info.package_id', '=', 'service_info.package_id')
//         ->where('package_info.package_duration', 2)
//         ->where('service_info.sent_lead', '>', 'service_info.total_lead');

//     if ($request->category) {
//         $data->where('category_id', $request->category);
//         $extra_sent->where('service_info.category_id', $request->category);
//     }

//     if ($request->start_date && $request->end_date) {
//         $data->whereDate('enquiry_info.created_date', '>=', $request->start_date)
//              ->whereDate('enquiry_info.created_date', '<=', $request->end_date);
//         $extra_sent->whereDate('service_info.created_date', '>=', $request->start_date)
//                    ->whereDate('service_info.created_date', '<=', $request->end_date);
//     } else {
//         $data->whereMonth('enquiry_info.created_date', Carbon::now()->month);
//         $extra_sent->whereMonth('service_info.created_date', Carbon::now()->month);
//     }

//     $groupedData = $data->select('enquiry_info.category_id', 'product_category.category_name',
//                                   DB::raw('COUNT(*) as total_enquiries'), 
//                                   DB::raw('SUM(sent_count) as total_sent_count'))
//         ->leftJoin('product_category', 'enquiry_info.category_id', '=', 'product_category.id')
//         ->groupBy('enquiry_info.category_id', 'product_category.category_name')
//         ->get();

//     $extra_sent_data = $extra_sent->select('service_info.category_id', 
//                                   DB::raw('SUM(service_info.total_lead) as total_lead'), 
//                                   DB::raw('SUM(service_info.sent_lead) as sent_lead'))
//         ->leftJoin('product_category', 'service_info.category_id', '=', 'product_category.id')
//         ->groupBy('service_info.category_id')
//         ->get();

//     $extraSentMapping = [];
//     foreach ($extra_sent_data as $extra) {
//         $extraSentMapping[$extra->category_id] = $extra; 
//     }

//     $groupLoss = [];
//     foreach ($groupedData as $group) {
//         $total_expected_sent_count = $group->total_enquiries * 4; 
//         $total_diff_sent_count = $total_expected_sent_count - $group->total_sent_count;
//         $not_sent = $total_expected_sent_count - $group->total_sent_count;

//         $lead_difference = 0; 

//         if (isset($extraSentMapping[$group->category_id])) {
//             $total_lead = $extraSentMapping[$group->category_id]->total_lead ?? 0;
//             $sent_lead = $extraSentMapping[$group->category_id]->sent_lead ?? 0;

//             //$lead_difference = $sent_lead - $total_lead; 
//             $lead_difference = max(0, $sent_lead - $total_lead);
//         }

//         $total_diff_sent_count += $lead_difference; 

//         $groupLoss[] = [
//             'category_id' => $group->category_id,
//             'category_name' => $group->category_name,
//             'extra_sent' => $lead_difference,
//             'not_sent' => $not_sent,
//             'total_diff_sent_count' => $total_diff_sent_count,
//         ];
//     }

//     return response()->json(['status' => 200, 'data' => $groupLoss]);
// }

 public function sales_missing_followup(Request $request){
    $data_array = [];
    $missing_followup_count = 0;

    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager])
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif ($request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        //$emp_idss = 'RIMS1';
        $emp_managers = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $data = DB::connection('sales_db')->table('clients_followup_log')
        ->leftJoin('company_info', 'company_info.comp_id', '=', 'clients_followup_log.comp_id')
        ->leftJoin('guest_user_details', 'guest_user_details.id', '=', 'clients_followup_log.client_id')
        ->select(
            'clients_followup_log.*', 
            'company_info.client_name', 
            'company_info.group_id as company_info_group', 
            'guest_user_details.group_id as guest_group'
        )
        ->whereIn('clients_followup_log.created_by', $emp_managers);

    if ($request->group) {
        $data->where(function ($query) use ($request) {
            $query->where(function ($subQuery) use ($request) {
                $subQuery->where('clients_followup_log.client_type', 1)
                    ->where('guest_user_details.group_id', $request->group);
            })->orWhere(function ($subQuery) use ($request) {
                $subQuery->where('clients_followup_log.client_type', 2)
                    ->where('company_info.group_id', $request->group);
            });
        });
    }

    if ($request->product) {
        $data->where('clients_followup_log.product_id', $request->product);
    }
    if ($request->service) {
        $data->where('clients_followup_log.service_id', $request->service);
    }
    if ($request->category) {
        $data->where('clients_followup_log.category_id', $request->category);
    }

    if ($request->start_date && $request->end_date) {
        $data->whereBetween('clients_followup_log.next_followup_date', [$request->start_date, $request->end_date]);
    } else {
        $data->whereDate('clients_followup_log.next_followup_date', Carbon::now()->format('Y-m-d'));
    }
       $query = $data->whereIn('clients_followup_log.followup_id', [6, 9, 7, 5])->get();

    foreach ($query as $row) {
        $check_package_created_or_not = DB::connection('sales_db')->table('package_info')
                                      ->where('client_id',$row->client_id)->where('comp_id',$row->comp_id)
                                      ->whereDate('created_date','>=',$row->next_followup_date)
                                      ->first();


        if(!$check_package_created_or_not){
              $check_followup_taken = DB::connection('sales_db')->table('clients_followup_log')
                                    ->where('client_id',$row->client_id)->where('comp_id',$row->comp_id)
                                     ->whereDate('created_date','>=',$row->next_followup_date)
                                     ->first();
             if(!$check_followup_taken){
                $missing_followup_count++;
                if ($row->client_type == 1) {
                    $client_details = DB::connection('sales_db')->table('guest_user_details')->where('id', $row->client_id)->first();
                    $client_name = $client_details->name ?? '';
                    $group_id = $client_details->group_id ?? '';
                    $comp_name =  $client_details->business_name ?? '';
                } else {
                    $client_details = DB::connection('sales_db')->table('company_info')->where('comp_id', $row->comp_id)->first();
                    $client_name = $client_details->client_name ?? '';
                    $group_id = $client_details->group_id ?? '';
                    $comp_name =  $client_details->business_name ?? '';
                }
        
                // Fetch additional information
                $group = DB::connection('sales_db')->table('group_names')->where('group_id', $group_id)->first();
                $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
                $service = DB::connection('sales_db')->table('product_service')->where('id', $row->service_id)->first();
                $category = DB::connection('sales_db')->table('product_category')->where('id', $row->category_id)->first();
                $followup_status = DB::connection('sales_db')->table('followup_status')->where('id', $row->followup_id)->first();
                $emp_name = DB::table('emp_basic_info')->where('emp_id', $row->created_by)->first();
                $manager_name = DB::table('emp_basic_info')->where('emp_id', $emp_name->reporting_manager)->first();
        
                if ($row->refer_package_id != '') {
                    $pkg_details = DB::connection('sales_db')->table('pre_package')->where('id', $row->refer_package_id)->first();
                    $pkg_amount = $pkg_details->package_price ?? '';
                    $pkg_type = $pkg_details->package_type ?? '';
                } else {
                    $pkg_amount = '';
                    $pkg_type = '';
                }
        
                $data_array[] = [
                    'id' => $row->id,
                    'client_type' => $row->client_type,
                    'client_id' => $row->client_id,
                    'client_name' => $client_name,
                    'comp_name' => $comp_name,
                    'group' => $group->name ?? '',
                    'product' => $product->product_name ?? '',
                    'category' => $category->category_name ?? '',
                    'service' => $service->service_name ?? '',
                    'followup_status' => $followup_status->activity_name ?? '',
                    'created_date' => $row->created_date ?? '',
                    'emp_name' => ($emp_name->emp_fname ?? '') . ' ' . ($emp_name->emp_lame ?? ''),
                    'manager' => ($manager_name->emp_fname ?? '') . ' ' . ($manager_name->emp_lame ?? ''),
                    'package_type' => $pkg_type,
                    'pkg_amount' => $pkg_amount,
                    'next_followup_date' => $row->next_followup_date ?? '',
                ];
            }
        
            }
        }
        return response()->json(['status' => 200, 'data' => $data_array,
                  'missing_followup'=>$missing_followup_count]);

       }

    public function count_sales_followup(Request $request){
        $data_array = [];
        $missing_followup_count = 0;

     if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager])
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif ($request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        //$emp_idss = 'RIMS1';
        $emp_managers = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }
    $data = DB::connection('sales_db')->table('clients_followup_log')
    ->leftJoin('company_info', 'company_info.comp_id', '=', 'clients_followup_log.comp_id')
    ->leftJoin('guest_user_details', 'guest_user_details.id', '=', 'clients_followup_log.client_id')
    ->select(
        'clients_followup_log.*', 
        'company_info.client_name', 
        'company_info.group_id as company_info_group', 
        'guest_user_details.group_id as guest_group'
    )
    ->whereIn('clients_followup_log.created_by', $emp_managers);

if ($request->group) {
    $data->where(function ($query) use ($request) {
        $query->where(function ($subQuery) use ($request) {
            $subQuery->where('clients_followup_log.client_type', 1)
                ->where('guest_user_details.group_id', $request->group);
        })->orWhere(function ($subQuery) use ($request) {
            $subQuery->where('clients_followup_log.client_type', 2)
                ->where('company_info.group_id', $request->group);
        });
    });
}

if ($request->product) {
    $data->where('clients_followup_log.product_id', $request->product);
}
if ($request->service) {
    $data->where('clients_followup_log.service_id', $request->service);
}
if ($request->category) {
    $data->where('clients_followup_log.category_id', $request->category);
}

if ($request->start_date && $request->end_date) {
    $data->whereBetween('clients_followup_log.next_followup_date', [$request->start_date, $request->end_date]);
} else {
    $data->whereDate('clients_followup_log.next_followup_date', Carbon::now()->format('Y-m-d'));
}
   $query = $data->whereIn('clients_followup_log.followup_id', [6, 9, 7, 5])->get();
   $total_missing_kra_kpis = DB::connection('sales_db')->table('kra_and_kpi_details')->whereIn('created_by',$emp_managers)
   ->where('created_date',Carbon::now()->format('Y-m-d'))
   ->sum('remaining_followup');

   foreach ($query as $row) {
    $check_package_created_or_not = DB::connection('sales_db')->table('package_info')
                                  ->where('client_id',$row->client_id)->where('comp_id',$row->comp_id)
                                  ->whereDate('created_date','>=',$row->next_followup_date)
                                  ->first();


    if(!$check_package_created_or_not){
          $check_followup_taken = DB::connection('sales_db')->table('clients_followup_log')
                                ->where('client_id',$row->client_id)->where('comp_id',$row->comp_id)
                                 ->whereDate('created_date','>=',$row->next_followup_date)
                                 ->first();
         if(!$check_followup_taken){
            $missing_followup_count++;
        }
    }
}

return response()->json(['status'=>200,'missing_followup'=>$missing_followup_count,'missing_kra_kpi'=>$total_missing_kra_kpis,]);



}

public function buffer_amount_details(Request $request){
    $result = [];

    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager])
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif ($request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_idss = $request->emp_id;
        $emp_managers = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_idss])
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')
            ->toArray();

        $emp_managers[] = $emp_idss;
        $emp_managers = array_unique($emp_managers);
    }

       $data = DB::connection('sales_db')->table('wallet_info')
           ->leftjoin('client_info','client_info.id','wallet_info.client_id')
           ->leftjoin('group_names','group_names.group_id','client_info.group_id')
           ->select('wallet_info.*','client_info.id','client_info.exe_id',
           'client_info.client_name','client_info.group_id','group_names.name')
           ->whereIn('client_info.exe_id',$emp_managers);


       if($request->group){
          $data->where('client_info.group_id',$request->group);
       }
    //    if($request->start_date && $request->end_date){
    //     $data->whereDate('wallet_info.created_date', '>=', $request->start_date)
    //          ->whereDate('wallet_info.created_date', '<=', $request->end_date);
    //     }
    //    else{
    //     $data->whereMonth('wallet_info.created_date',Carbon::now()->month);

    //    }
       $data_array = $data->paginate(10);

        foreach($data_array as $row){
         $emp_details = DB::table('emp_basic_info')->where('emp_id',$row->exe_id)->first();
         $emp_manager =  DB::table('emp_basic_info')->where('emp_id',$emp_details->reporting_manager)->first();
         $result[] = array('client_id'=>$row->id,'client_name'=>$row->client_name,
         'amount'=>$row->balance_amount,'group'=>$row->name,
         'emp_name'=> $emp_details->emp_fname.' '.$emp_details->emp_lame,'manager'=>$emp_manager->emp_fname.' '.$emp_manager->emp_lame);
         }

      

       return response()->json(['status'=>200,'data'=>$result,'last_page'=>$data_array->lastPage()]);
      }

      public function category_loss(Request $request)
      {

          $currentMonth = date('n');
          $currentYear = date('Y');
      
          if ($currentMonth >= 4) {
              $financialYearStart = $currentYear;
              $financialYearEnd = $currentYear + 1;
          } else {
              $financialYearStart = $currentYear - 1;
              $financialYearEnd = $currentYear;
          }
      
          $financialYear = $financialYearStart . '-' . $financialYearEnd;
      
          $pyment_followups = DB::table('save_company_target')
              ->where('subattribute_id', 13)
              ->where('financial_year', $financialYear)
              ->first();
      
          $proposal_send = DB::table('save_company_target')
              ->where('subattribute_id', 14)
              ->where('financial_year', $financialYear)
              ->first();
      
          $meeting_followups = DB::table('save_company_target')
              ->where('subattribute_id', 15)
              ->where('financial_year', $financialYear)
              ->first();
      
          $call_connected = DB::table('save_company_target')
              ->where('subattribute_id', 12)
              ->where('financial_year', $financialYear)
              ->first();

          $date = Carbon::now();  
          $daysInMonth = $date->daysInMonth; 
          //return $daysInMonth;

      
          $monthly_payment_followups = round($pyment_followups->child_attribute_value / 12);
          $per_day_payment_followups = round($monthly_payment_followups / $daysInMonth);
      
          $monthly_proposal_send = round($proposal_send->child_attribute_value / 12);
          $per_day_proposal_send = round($monthly_proposal_send / $daysInMonth);
      
          $monthly_meeting = round($meeting_followups->child_attribute_value / 12);
          $per_day_meeting = round($monthly_meeting / $daysInMonth);
      
          $monthly_call_connected = round($call_connected->child_attribute_value / 12);
          $per_day_call_connected = round($monthly_call_connected / $daysInMonth);
      
          $total_targeted_data = $per_day_call_connected + $per_day_meeting + $per_day_proposal_send + $per_day_payment_followups;
      
      
          $emp_id = 'RIMS1';
      
          if ($emp_id == 'RIMS1') {
              $data = DB::table('emp_basic_info')
                  ->where('desi_id', 38)
                  ->where('emp_status', 1)
                  ->get(['emp_id', 'emp_fname', 'emp_lame']);
          } else {
              $data = DB::table('emp_basic_info')
                  ->where('emp_id', $request->emp_id)
                  ->get(['emp_id', 'emp_fname', 'emp_lame']);
          }
      
          $teamMembersMapping = [];
          foreach ($data as $row) {
              $get_team = DB::table('employee_managers')
                  ->whereRaw('FIND_IN_SET(?, reporting_to)', [$row->emp_id])
                  ->where('dept_id', 3)
                  ->where('status', 1)
                  ->pluck('emp_id')
                  ->toArray();
      
              $teamMembers = array_unique(array_merge($get_team, [$row->emp_id]));
              $teamMembersMapping[$row->emp_id] = $teamMembers;
          }
      
          $results = [];
          foreach ($teamMembersMapping as $managerId => $members) {
              $managerInfo = $data->firstWhere('emp_id', $managerId);
              $managerName = $managerInfo ? $managerInfo->emp_fname . ' ' . $managerInfo->emp_lame : 'Unknown';
              $total_team_count = DB::table('emp_basic_info')->where('reporting_manager',$managerId)->where('emp_status',1)->count();
      
              $managerTotalQuery = DB::connection('sales_db')->table('clients_followup_log')
                  ->select(
                      DB::raw("'{$managerId}' as manager_id"),
                      DB::raw("'{$managerName}' as manager_name"),
                      DB::raw('SUM(CASE WHEN clients_followup_log.followup_id IN (6, 9) THEN 1 ELSE 0 END) as payment_followup'),
                      DB::raw('SUM(CASE WHEN clients_followup_log.followup_id = 5 THEN 1 ELSE 0 END) as proposal_send'),
                      DB::raw('SUM(CASE WHEN clients_followup_log.followup_id = 7 THEN 1 ELSE 0 END) as meeting_fix')
                  )
                  ->whereIn('clients_followup_log.created_by', $members);
      
              $noOfCallConnected = DB::connection('sales_db')->table('clients_followup_log')
                  ->whereIn('clients_followup_log.created_by', $members)
                  ->where('disposition', 'connected')
                  ->whereNotIn('followup_id', [24, 25]);
      
              if ($request->start_date && $request->end_date) {
                //return 'lll';
                  $start = Carbon::parse($request->start_date);
                  $end = Carbon::parse($request->end_date);
                  $days = $start->diffInDays($end) + 1;
                  //return  $days;
                  if($total_team_count>0){
                    $payment_for_foolowups_count =  $per_day_payment_followups*$days*$total_team_count;
                    $meeting_fix_count = $per_day_meeting*$days*$total_team_count;
                    $proposal_send_count =  $per_day_proposal_send*$days*$total_team_count;
                    $call_connected_count =  $per_day_call_connected*$days*$total_team_count;

                  }
                  else{
                    $payment_for_foolowups_count =  $per_day_payment_followups*$days;
                    $meeting_fix_count = $per_day_meeting*$days;
                    $proposal_send_count =  $per_day_proposal_send*$days;
                    $call_connected_count =  $per_day_call_connected*$days;
                    
                  }
                  
                  
                  $total_assigned_target = $payment_for_foolowups_count +  $meeting_fix_count + $proposal_send_count
                                                + $call_connected_count ;
      
                  $managerTotalQuery->whereDate('clients_followup_log.created_date', '>=', $request->start_date)
                      ->whereDate('clients_followup_log.created_date', '<=', $request->end_date);
      
                  $noOfCallConnected->whereDate('clients_followup_log.created_date', '>=', $request->start_date)
                      ->whereDate('clients_followup_log.created_date', '<=', $request->end_date);
              } else {
                if($total_team_count>0){
                    $payment_for_foolowups_count =  $per_day_payment_followups*$total_team_count;
                    $meeting_fix_count = $per_day_meeting*$total_team_count;
                    $proposal_send_count =  $per_day_proposal_send*$total_team_count;
                    $call_connected_count =  $per_day_call_connected*$total_team_count;

                }
                else{
                    $payment_for_foolowups_count =  $per_day_payment_followups;
                    $meeting_fix_count = $per_day_meeting;
                    $proposal_send_count =  $per_day_proposal_send;
                    $call_connected_count =  $per_day_call_connected;
                }
               
                
                $total_assigned_target = $payment_for_foolowups_count +  $meeting_fix_count + $proposal_send_count
                                              + $call_connected_count ;
                  $managerTotalQuery->whereDate('clients_followup_log.created_date', Carbon::now()->format('Y-m-d'));
      
                  $noOfCallConnected->whereDate('clients_followup_log.created_date', Carbon::now()->format('Y-m-d'));
              }
      
              if ($request->group) {
                  $managerTotalQuery->where('clients_followup_log.city_id', $request->group);
                  $noOfCallConnected->where('clients_followup_log.city_id', $request->group);
              }
      
              if ($request->product) {
                  $managerTotalQuery->where('clients_followup_log.product_id', $request->product);
                  $noOfCallConnected->where('clients_followup_log.product_id', $request->product);
              }
      
              if ($request->category) {
                  $managerTotalQuery->where('clients_followup_log.category_id', $request->category);
                  $noOfCallConnected->where('clients_followup_log.category_id', $request->category);
              }
      
              $managerTotal = $managerTotalQuery->groupBy('manager_id')->first();
      
              if (!$managerTotal) {
                  $managerTotal = (object)[
                      'manager_id' => $managerId,
                      'manager_name' => $managerName,
                      'payment_followup'=>0,
                      'proposal_send'=>0,
                      'meeting_fix'=>0,
                      'call_connected'=>0,
                      'total_required' => $total_assigned_target,
                      'total_acheived' => 0,
                      'total_remaining' => $total_assigned_target,
                      'per_day_payment_followups'=>$payment_for_foolowups_count,
                      'per_day_proposal_send'=> $proposal_send_count,
                      'per_day_meeting_fix'=>$meeting_fix_count,
                      'per_day_call_connected'=>$call_connected_count,
                      'complete_percent'=>0,
                  ];
              } else {
                  $managerTotal->call_connected = $noOfCallConnected->count();
                  $managerTotal->total_required = $total_assigned_target;
                  $managerTotal->total_acheived =
                      ($managerTotal->payment_followup ?? 0) +
                      ($managerTotal->meeting_fix ?? 0) +
                      ($managerTotal->proposal_send ?? 0) +
                       ($managerTotal->call_connected ?? 0);
      
                     $managerTotal->total_remaining = $total_assigned_target - $managerTotal->total_acheived;
                     $managerTotal->per_day_payment_followups =$payment_for_foolowups_count;
                     $managerTotal->per_day_proposal_send = $proposal_send_count;
                     $managerTotal->per_day_meeting_fix =$meeting_fix_count;
                     $managerTotal->per_day_call_connected =$call_connected_count;
                     if($managerTotal->total_acheived>0){
                        $managerTotal->complete_percent = round(($managerTotal->total_acheived/$managerTotal->total_required)*100);
                        }
                     else{
                         $managerTotal->complete_percent = 0;

                     }
              }
      
              if ($managerTotal) {
                   $results[] = [
                      'manager_id' => $managerId,
                      'manager_name' => $managerName,
                      'total' => $managerTotal,
                  ];
              }
          }
      
          return response()->json(['status' => 200, 'data' => $results]);
      }


      public function team_kra_kpis(Request $request) {
           $emp_id = $request->emp_id;

           $get_team = DB::table('employee_managers')
             ->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id])
             ->where('dept_id', 3)
             ->where('status', 1)
            ->distinct()
            ->pluck('emp_id')
           ->toArray();
       
            $currentMonth = date('n');
            $currentYear = date('Y');
        
            if ($currentMonth >= 4) {
                $financialYearStart = $currentYear;
                $financialYearEnd = $currentYear + 1;
            } else {
                $financialYearStart = $currentYear - 1;
                $financialYearEnd = $currentYear;
            }
        
            $financialYear = $financialYearStart . '-' . $financialYearEnd;
        
            $pyment_followups = DB::table('save_company_target')
                ->where('subattribute_id', 13)
                ->where('financial_year', $financialYear)
                ->first();
        
            $proposal_send = DB::table('save_company_target')
                ->where('subattribute_id', 14)
                ->where('financial_year', $financialYear)
                ->first();
        
            $meeting_followups = DB::table('save_company_target')
                ->where('subattribute_id', 15)
                ->where('financial_year', $financialYear)
                ->first();
        
            $call_connected = DB::table('save_company_target')
                ->where('subattribute_id', 12)
                ->where('financial_year', $financialYear)
                ->first();
  
            $date = Carbon::now();  

            $daysInMonth = $date->daysInMonth; 
            //return $daysInMonth;
  
        
            $monthly_payment_followups = round($pyment_followups->child_attribute_value / 12);
            $per_day_payment_followups = round($monthly_payment_followups / $daysInMonth);
        
            $monthly_proposal_send = round($proposal_send->child_attribute_value / 12);
            $per_day_proposal_send = round($monthly_proposal_send / $daysInMonth);
        
            $monthly_meeting = round($meeting_followups->child_attribute_value / 12);
            $per_day_meeting = round($monthly_meeting / $daysInMonth);
        
            $monthly_call_connected = round($call_connected->child_attribute_value / 12);
            $per_day_call_connected = round($monthly_call_connected / $daysInMonth);
        
            $total_targeted_data = $per_day_call_connected + $per_day_meeting + $per_day_proposal_send + $per_day_payment_followups;
            $results = [];
    
           foreach ($get_team as $team_member) {
             $emp_name = DB::table('emp_basic_info')->where('emp_id',$team_member)->first();

             $query = DB::connection('sales_db')->table('clients_followup_log')
                ->select(
                    'created_by',
                    DB::raw('SUM(CASE WHEN followup_id IN (6, 9) THEN 1 ELSE 0 END) as payment_followup'),
                    DB::raw('SUM(CASE WHEN followup_id = 5 THEN 1 ELSE 0 END) as proposal_send'),
                    DB::raw('SUM(CASE WHEN followup_id = 7 THEN 1 ELSE 0 END) as meeting_fix'),
                    DB::raw('SUM(CASE WHEN disposition = "connected" AND followup_id NOT IN (24, 25) THEN 1 ELSE 0 END) as call_connected')
                )
                ->where('created_by', $team_member);
    
            if ($request->start_date && $request->end_date) {
                $start = Carbon::parse($request->start_date);
                $end = Carbon::parse($request->end_date);
                $days = $start->diffInDays($end) + 1;
    
                $per_day_targets = $total_targeted_data*$days;
                $payment_for_followups_data =  $per_day_payment_followups*$days;
                $call_connected_data = $per_day_call_connected*$days;
                $meeting_fix_data =  $per_day_meeting*$days ;
                $proposal_send_data =  $per_day_proposal_send*$days;

                $query->whereDate('clients_followup_log.created_date', '>=', $request->start_date)
                ->whereDate('clients_followup_log.created_date', '<=', $request->end_date);

            } else {
                $per_day_targets = $total_targeted_data;
                $payment_for_followups_data =  $per_day_payment_followups;
                $call_connected_data = $per_day_call_connected;
                $meeting_fix_data =  $per_day_meeting;
                $proposal_send_data =  $per_day_proposal_send;
                $query->whereDate('clients_followup_log.created_date', Carbon::now()->format('Y-m-d'));
            }
    
            if ($request->group) {
                $query->where('clients_followup_log.created_date.city_id', $request->group);
            }
    
            if ($request->product) {
                $query->where('clients_followup_log.created_date.product_id', $request->product);
            }
    
            if ($request->category) {
                $query->where('clients_followup_log.created_date.category_id', $request->category);
            }
    
            $team_data = $query->first();
    
            if (!$team_data) {
                $team_data = (object) [
                      'emp_id'=>$team_member,
                      'emp_name'=>$emp_name->emp_fname.' '.$emp_name->emp_lame,
                      'payment_followup'=>0,
                      'proposal_send'=>0,
                      'meeting_fix'=>0,
                      'call_connected'=>0,
                      'total_required' =>$per_day_targets,
                      'total_acheived' => 0,
                      'total_remaining' => $per_day_targets,
                      'per_day_payment_followups'=>$payment_for_followups_data,
                      'per_day_proposal_send'=> $proposal_send_data,
                      'per_day_meeting_fix'=> $meeting_fix_data,
                      'per_day_call_connected'=>$call_connected_data,
                      'complete_percent'=>0,
                ];
            }
    
            $total_achieved = $team_data->payment_followup + $team_data->proposal_send + $team_data->meeting_fix + $team_data->call_connected;
            //$total_assigned_target = array_sum($per_day_targets);
    
            $results[] = [
                      'emp_id'=>$team_member,
                      'emp_name'=>$emp_name->emp_fname.' '.$emp_name->emp_lame,
                      'payment_followup'=>$team_data->payment_followup??0,
                      'proposal_send'=>$team_data->proposal_send??0,
                      'meeting_fix'=>$team_data->meeting_fix??0,
                      'call_connected'=>$team_data->call_connected??0,
                      'total_required' =>$per_day_targets,
                      'total_acheived' =>$total_achieved,
                      'total_remaining' => $per_day_targets - $total_achieved,
                      'per_day_payment_followups'=>$payment_for_followups_data,
                      'per_day_proposal_send'=> $proposal_send_data,
                      'per_day_meeting_fix'=> $meeting_fix_data,
                      'per_day_call_connected'=>$call_connected_data,
                      'complete_percent'=>$total_achieved > 0 ? round(($total_achieved / $per_day_targets) * 100, 2) : 0,
            ];
        }
    
        return response()->json(['status' => 200, 'data' => $results]);
    }

    public function count_active_packages_with_category_details(Request $request)
{
    $data_array = [];
    
    // Fetch all active employees
    $employees = DB::table('emp_basic_info')->where('emp_status', 1)->limit(50)->get();

    foreach ($employees as $employee) {
        $managerIds = [];
        $currentManagerId = $employee->reporting_manager;

        // Build reporting hierarchy
        while ($currentManagerId) {
            $managerInfo = DB::table('emp_basic_info')
                ->where('emp_id', $currentManagerId)
                ->first();

            if (!$managerInfo) {
                break; // Exit if no valid manager is found
            }

            $managerIds[] = $managerInfo->emp_id;
            $currentManagerId = $managerInfo->reporting_manager; // Traverse up the hierarchy
        }

        $data_array[] = [
            'emp_id' => $employee->emp_id,
            'dept_manager' => $employee->reporting_manager,
            'reporting_to' => implode(',', $managerIds), // Convert array to string
            'status' => $employee->emp_status,
            'from_date' => $employee->emp_doj,
            'dept_id' => $employee->dept_id,
            'designation_id' => $employee->desi_id,
        ];
    }

    // Return the result as JSON
    DB::table('employee_managers')->insert($data_array);


        // $emp_idss = $request->emp_id;
        // $emp_managers = DB::table('employee_managers')
        //     ->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_idss])
        //     ->where('status', 1)
        //     ->where('dept_id', 3)
        //     ->pluck('emp_id')
        //     ->toArray();

        // $emp_managers[] = $emp_idss;
        // $emp_managers = array_unique($emp_managers);

        // $data = DB::connection('sales_db')->table('package_info');
        // if($request->product){
        //     $data->where('product_id',$request->product);

        // }
        // if($request->service){
        //     $data->where('service_id',$request->service);

        // }

        // $get_category_details = DB::connection('sales_db')->table('product_category')
        //                           ->where('product_id', $request->product)
        //                            ->where('service_id', $request->service)
        //                            ->where('stat')
        //                             ->get(['id', 'category_name']);
        //   $comboRecord = collect([[
        //                 'id' => 'combo',  
        //                 'category_name' => 'Combo',  
        //                 ]]);
 
        //   $get_category_details = $get_category_details->merge($comboRecord);

        //   $get_category_details = $get_category_details->values(); 

        //   $total_active_packages = $data->whereIn('exe_id',$emp_managers)->where('package_status',1)->count();

        // return response()->json(['status'=>200,'data'=>$get_category_details,'count'=>$total_active_packages]);

    }

    public function get_group_category_packages_count(Request $request)
{
    $emp_id = 'RIMS1'; 
    $emp_managers = DB::table('employee_managers')
        ->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id])
        ->where('status', 1)
        ->where('dept_id', 3)
        ->pluck('emp_id')
        ->toArray();

    $emp_managers[] = $emp_id;
    $emp_managers = array_unique($emp_managers);

    //return $emp_managers;

    $normalPackagesData = DB::connection('sales_db')->table('package_info')
        ->join('group_names', 'package_info.group_id', '=', 'group_names.group_id')
        ->join('product_category', 'product_category.id', '=', 'package_info.category_id')
        ->select(
            'package_info.category_id',
            DB::raw('SUBSTRING_INDEX(package_info.group_id, ",", 1) as group_id'),
            'package_info.package_status',
            'product_category.category_name',
            'group_names.name as group_name',
            DB::raw('COUNT(*) as total_count')
        )
        ->where('package_info.package_status', 1)
       ->whereRaw('LENGTH(package_info.category_id) - LENGTH(REPLACE(package_info.category_id, ",", "")) = 0')
        // ->whereIn('package_info.exe_id', $emp_managers)
        // ->where('package_info.product_id',$request->product)
        // ->where('package_info.service_id',$request->service)
        ->groupBy(
            DB::raw('SUBSTRING_INDEX(package_info.group_id, ",", 1)'), 
            'package_info.category_id',
            'group_names.name'
        )
        ->get();

    $comboPackagesData = DB::connection('sales_db')->table('package_info')
        ->join('group_names', 'package_info.group_id', '=', 'group_names.group_id')
        ->join('product_category', 'product_category.id', '=', 'package_info.category_id')
        ->select(
            DB::raw('SUBSTRING_INDEX(package_info.group_id, ",", 1) as group_id'),
            'group_names.name as group_name',
            DB::raw('COUNT(*) as total_count'),
            'package_info.category_id',

        )
        ->where('package_info.package_status', 1)
        ->whereRaw('LENGTH(package_info.category_id) - LENGTH(REPLACE(package_info.category_id, ",", "")) > 0')  
        // ->whereIn('package_info.exe_id', $emp_managers)
        // ->where('package_info.product_id',$request->product)
        // ->where('package_info.service_id',$request->service)
        ->groupBy(
            DB::raw('SUBSTRING_INDEX(package_info.group_id, ",", 1)'), 
            'group_names.name'
        )
        ->get();
    $allData = $normalPackagesData->merge($comboPackagesData);
     //return  $allData;
    $groupedData = $allData->groupBy('group_name')->map(function($group) {
        return $group->map(function($item) {
            if (strpos($item->category_id, ',') === false) {

                return [
                    'category_name' => $item->category_name,
                    'total_count' => $item->total_count,
                    'category_id' => $item->category_id, 
                ];
            } else {
                return [
                    'total_count' => $item->total_count,
                    'category_ids'=>$item->category_id,
                    'category_id' => 'combo',
                ];
            }
        });
    });

    return response()->json(['status' => 200, 'data' => $groupedData]);
}

public function stop_packages_report(Request $request){
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            //->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
                      ->orWhere('dept_manager', $request->emp_id);
            })
            //->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $query = DB::connection('sales_db')->table('package_info')
    ->leftJoin('package_enable_disable_history', function ($join) {
        $join->on('package_enable_disable_history.package_id', '=', 'package_info.package_id')
            ->whereRaw('package_enable_disable_history.id = (
                SELECT MAX(id) 
                FROM package_enable_disable_history 
                WHERE package_enable_disable_history.package_id = package_info.package_id
            )');
    })
    ->select(
        'package_info.product_id','package_info.service_id',
        'package_info.package_id',
        'package_info.category_id','package_info.client_id','package_info.comp_id',
        'package_info.total_lead','package_info.sent_lead',
        'package_info.group_id','package_info.exe_id','package_info.package_name',
        'package_enable_disable_history.package_start_date as start_date',
        'package_enable_disable_history.remarks as last_remark',
        'package_enable_disable_history.id as latest_id',
        'package_enable_disable_history.created_date as last_request_date',
        'package_enable_disable_history.created_by as requested_by',
    )
    ->where('package_info.package_status', 2)
    ->paginate(10);

    foreach($query as $row){
        $group_ids = explode(',', $row->group_id);
        $category_id = explode(',', $row->category_id);
        $service_id = explode(',', $row->service_id);

        $group_names = DB::connection('sales_db')->table('group_names')
            ->whereIn('group_id', $group_ids)
            ->pluck('name')
            ->implode(', ');

        $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();

        $category = DB::connection('sales_db')->table('product_category')
            ->whereIn('id', $category_id)
            ->pluck('category_name')
            ->implode(', ');

        $service = DB::connection('sales_db')->table('product_service')
            ->whereIn('id', $service_id)
            ->pluck('service_name')
            ->implode(', ');

       $client_details = DB::connection('sales_db')->table('company_info')->where('comp_id', $row->comp_id)->first();
       if($client_details){
        $client_name = $client_details->client_name;
        $comp_name = $client_details->business_name;
       }
       else{
        $client_name = ' ';
        $comp_name = ' ';

       }
       if($row->requested_by == 'App'){
        $requested_by = 'App';
       }
       else{
        $requested_by_name = DB::table('emp_basic_info')->where('emp_id',$row->requested_by)->first();
        if($requested_by_name){
            $requested_by = $emp_details->emp_fname.' '.$emp_details->emp_lame;
        }
        else{
            $requested_by = '';

        }
    }
       $emp_details = DB::table('emp_basic_info')->where('emp_id',$row->exe_id)->first();
       $emp_manager = DB::table('emp_basic_info')->where('emp_id',$emp_details->reporting_manager)->first();

        $data_array[] = array('package_name'=>$row->package_name,
                     'group'=>$group_names,'product'=>$product->product_name,
                     'service'=>$service,'category'=> $category,'total_lead'=>$row->total_lead,
                     'sent_lead'=>$row->sent_lead,'client_id'=>$row->client_id,'client_name'=> $client_name,
                     'comp_name'=>$comp_name,'requested_by'=>$requested_by,
                     'requested_date'=>$row->last_request_date,
                     'emp_name'=>$emp_details->emp_fname.' '.$emp_details->emp_lame,
                     'manager_name'=>$emp_manager->emp_fname.' '.$emp_manager->emp_lame,
                     'package_start_date'=>$row->start_date,'remark'=> $row->last_remark,'id'=>$row->id);


      }

      return response()->json(['status'=>200,'data'=>$data_array,'last_page'=>$query->lastPage()]);
   
}

public function lead_not_sent_more_then_two_days(Request $request){
    
    $data_array = [];
    $current_date = Carbon::now()->format('Y-m-d');
    if ($request->manager && !$request->employee) {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->manager]);
            })
            //->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->manager;
        $emp_managers = array_unique($emp_managers);
    } elseif (!$request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } elseif ($request->manager && $request->employee) {
        $emp_managers = is_array($request->employee) ? $request->employee : [$request->employee];
    } else {
        $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($request) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
                      ->orWhere('dept_manager', $request->emp_id);
            })
            //->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

     $data  = DB::connection('sales_db')->table('package_info')
        ->where('package_status', 1)
        ->where(function ($query) use ($current_date) {
            $query->where(function ($subQuery) use ($current_date) {
                $subQuery->whereNotNull('last_lead_sent_date')
                    ->where('last_lead_sent_date', '!=', '')
                    ->where('sent_lead', '>', 0) 
                    ->whereRaw("DATEDIFF(?, last_lead_sent_date) > 2", [$current_date]);
            })
            ->orWhere(function ($subQuery) use ($current_date) {
                $subQuery->whereNull('last_lead_sent_date')
                    ->orWhere('last_lead_sent_date', '=', '')
                    ->whereRaw("DATEDIFF(?, package_start_date) > 2", [$current_date]);
            });
        });
    
    if($request->product){
        $data->where('package_info.product_id',$request->product);

    }
    if($request->service){
        $data->where('package_info.service_id',$request->service);

    }
    if($request->category){
        $data->where('package_info.category_id',$request->category);

    }
    if($request->group){
        $data->where('package_info.group_id',$request->group);

    }
    $result = $data->paginate(10);

    foreach($result as $row){

        $group_ids = explode(',', $row->group_id);
        $category_id = explode(',', $row->category_id);
        $service_id = explode(',', $row->service_id);

        $group_names = DB::connection('sales_db')->table('group_names')
            ->whereIn('group_id', $group_ids)
            ->pluck('name')
            ->implode(', ');

        $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();

        $category = DB::connection('sales_db')->table('product_category')
            ->whereIn('id', $category_id)
            ->pluck('category_name')
            ->implode(', ');

        $service = DB::connection('sales_db')->table('product_service')
            ->whereIn('id', $service_id)
            ->pluck('service_name')
            ->implode(', ');

       $client_details = DB::connection('sales_db')->table('company_info')->where('comp_id', $row->comp_id)->first();
       if($client_details){
        $client_name = $client_details->client_name;
        $comp_name = $client_details->business_name;
       }
       else{
        $client_name = ' ';
        $comp_name = ' ';

       }
       if (!empty($row->last_lead_sent_date)) {
            $last_lead_sent_date = Carbon::parse($row->last_lead_sent_date);
            $date_diff = $last_lead_sent_date->diffInDays($current_date);
        } else {
             $package_start_date = Carbon::parse($row->package_start_date);
             $date_diff = $package_start_date->diffInDays($current_date);
        }

       $emp_details = DB::table('emp_basic_info')->where('emp_id',$row->exe_id)->first();
       $emp_manager = DB::table('emp_basic_info')->where('emp_id',$emp_details->reporting_manager)->first();

        $data_array[] = array('package_name'=>$row->package_name,
                     'group'=>$group_names,'product'=>$product->product_name,
                     'service'=>$service,'category'=> $category,'total_lead'=>$row->total_lead,
                     'sent_lead'=>$row->sent_lead,'client_id'=>$row->client_id,'client_name'=> $client_name,
                     'comp_name'=>$comp_name,
                     'emp_name'=>$emp_details->emp_fname.' '.$emp_details->emp_lame,
                     'manager_name'=>$emp_manager->emp_fname.' '.$emp_manager->emp_lame,
                     'package_start_date'=>$row->package_start_date,'id'=>$row->package_id,
                     'package_name'=>$row->package_name,'date_diff'=>$date_diff,
                     'last_lead_sent_date'=>$row->last_lead_sent_date);
                }
        return response()->json(['status'=>200,'data'=>$data_array,'last_page'=>$result->lastPage()]);
    }

}

    