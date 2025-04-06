<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\BasicInfo;
use Carbon\Carbon;
use App\Http\Controllers\ThirdPartyApi\CommunicationApis;
use Validator;
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

    $carray_forward =  DB::connection('sales_db')->table('clients_followup_log')
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
            DB::raw('MAX(clients_followup_log.product_id) as product_id'),
            DB::raw('MAX(clients_followup_log.category_id) as category_id'),
            DB::raw('MAX(clients_followup_log.service_id) as service_id'),
            'company_info.client_name',
            'company_info.business_name as company_business_name',
            'guest_user_details.group_id as guest_group_id', 

            'company_info.group_id as company_group_id' 
        )
        ->whereIn('clients_followup_log.created_by', $emp_managers);

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
}


    if ($request->product) {
        $data->where('clients_followup_log.product_id', $request->product);
        $carray_forward->where('clients_followup_log.product_id', $request->product);
    }
    if ($request->service) {
        $data->where('clients_followup_log.service_id', $request->service);
        $carray_forward->where('clients_followup_log.service_id', $request->service);
    }
    if ($request->category) {
        $data->where('clients_followup_log.category_id', $request->category);
        $carray_forward->where('clients_followup_log.category_id', $request->category);
    }
    if ($request->start_date && $request->end_date) {
        $data->whereDate('clients_followup_log.created_date', '>=', $request->start_date)
            ->whereDate('clients_followup_log.created_date', '<=', $request->end_date);
        $carray_forward->whereDate('clients_followup_log.created_date', '>=', $request->start_date)
            ->whereDate('clients_followup_log.created_date', '<=', $request->end_date);
    } else {
        $data->whereDate('clients_followup_log.created_date', Carbon::now()->format('Y-m-d'));
        $carray_forward->whereMonth('clients_followup_log.created_date', Carbon::now()->month);
    }

    // Use fresh queries for counting
    $followup_for_meeting = $data->clone()->where('followup_id', 7)->count();
    $followup_for_payment = $data->clone()->whereIn('followup_id', [6, 9])->count();
    $business_proposal = $data->clone()->where('followup_id', 5)->count();
    $call_connected = $data->clone()->where('clients_followup_log.disposition', 'connected')->count();
    $mature_followup = $data->clone()->where('followup_id', 19)->count();
    $dead_followup = $data->clone()->where('followup_id', 15)->count();

   $carry_forward_count = $carray_forward->groupBy('clients_followup_log.comp_id', 'clients_followup_log.client_id', 'clients_followup_log.created_by')->get();
    $unique_group_count = $carry_forward_count->count();

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
        'carry_forward'=>$unique_group_count,
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

     //return $emp_managers;

    $total_upcoming_renewal  = DB::connection('sales_db')->table('package_info');
    $collection_of_amount = DB::connection('sales_db')->table('payment_history')->leftJoin('company_info','company_info.comp_id','payment_history.comp_id');

    $package_pending_activation = DB::connection('sales_db')->table('package_info');

    $total_followup =  DB::connection('sales_db')->table('clients_followup_log')->leftJoin('company_info','company_info.client_id','clients_followup_log.client_id');

    $forcast_accuracy = DB::connection('sales_db')->table('clients_followup_log')->leftJoin('company_info','company_info.client_id','clients_followup_log.client_id');


    $count_regular_client =  DB::connection('sales_db')->table('package_info');

    $today_collection =  DB::connection('sales_db')->table('payment_history')->leftJoin('company_info','company_info.comp_id','payment_history.comp_id');

    $today_followup =  DB::connection('sales_db')->table('clients_followup_log')->leftJoin('company_info','company_info.client_id','clients_followup_log.client_id');

     if($request->group){
         $total_upcoming_renewal->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);

         $collection_of_amount->where('company_info.group_id',$request->group);

         $total_followup->where('company_info.group_id',$request->group);

         $package_pending_activation->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);

         $today_collection->where('company_info.group_id',$request->group);

         $today_followup->where('company_info.group_id',$request->group);

         $forcast_accuracy->where('company_info.group_id',$request->group);

         $count_regular_client->whereRaw('FIND_IN_SET(?, group_id)', [$request->group]);


    }
    
    if($request->product){
        $total_upcoming_renewal->where('product_id',$request->product);
        $collection_of_amount->where('payment_history.product_id',$request->product);
        
        $package_pending_activation->where('product_id',$request->product);
        $total_followup->where('product_id',$request->product);
        $forcast_accuracy->where('product_id',$request->product);
        $count_regular_client->where('product_id',$request->product);
        $today_collection->where('product_id',$request->product);
        $today_followup->where('product_id',$request->product);


    }
    if($request->service){
        $total_upcoming_renewal->whereRaw('FIND_IN_SET(?, service_id)', [$request->service]);
        $collection_of_amount->where('payment_history.service_id',$request->service);
       
        $package_pending_activation->whereRaw('FIND_IN_SET(?, service_id)', [$request->service]);
        $total_followup->where('service_id',$request->service);
        $forcast_accuracy->where('service_id',$request->category);
        $count_regular_client->whereRaw('FIND_IN_SET(?, service_id)', [$request->service]);
        $today_collection->where('payment_history.service_id',$request->category);
        $today_followup->where('service_id',$request->service);
        
        
    }
    if($request->category){
         $total_upcoming_renewal->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);

         $package_pending_activation->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);

         $total_followup->where('category_id',$request->category);

         $forcast_accuracy->where('category_id',$request->category);

         $count_regular_client->whereRaw('FIND_IN_SET(?, category_id)', [$request->category]);

         $today_followup->where('category_id',$request->category);

    }
    if($request->start_date && $request->end_date){
         $total_upcoming_renewal->whereDate('package_end_expected_date', '>=',$request->start_date)
                ->whereDate('package_end_expected_date', '<=', $request->end_date);

         $collection_of_amount->whereDate('created_date', '>=',$request->start_date)
                ->whereDate('created_date', '<=', $request->end_date);

         $package_pending_activation->whereDate('created_date', '>=',$request->start_date)
                ->whereDate('created_date', '<=', $request->end_date);

         $total_followup->whereDate('created_date', '>=',$request->start_date)
                ->whereDate('created_date', '<=', $request->end_date);

         $forcast_accuracy->whereDate('created_date', '>=',$request->start_date)
                ->whereDate('created_date', '<=', $request->end_date);


        //$regular_client_details = $count_regular_client->whereBetween('created_date', [$request->start_date, $request->end_date]);

    }
    if(!$request->start_date && !$request->end_date){
         $total_upcoming_renewal->whereMonth('package_end_expected_date', Carbon::now()->month)->whereYear('package_end_expected_date', Carbon::now()->year);

         $collection_of_amount->whereMonth('created_date', Carbon::now()->month)->whereYear('created_date', Carbon::now()->year);

         $package_pending_activation->whereMonth('created_date', Carbon::now()->month)->whereYear('created_date', Carbon::now()->year);

         $total_followup->whereMonth('created_date', Carbon::now()->month)->whereYear('created_date', Carbon::now()->year);
;

         $forcast_accuracy->whereMonth('created_date', Carbon::now()->month)->whereYear('created_date', Carbon::now()->year);
;

        //$regular_client_details = $count_regular_client->whereMonth('created_date', Carbon::now()->month);



    }
       // return 'kk';
        $renewal_data  =  $total_upcoming_renewal
                           ->whereIN('exe_id',$emp_managers)
                           ->count();

        $renewal_amount  =  $total_upcoming_renewal
                           ->whereIN('exe_id',$emp_managers)
                           ->sum('paid_amount');

        $total_collection  = $collection_of_amount
                           ->whereIN('payment_history.exe_id',$emp_managers)
                           ->sum('paid_amount');
                           
        $new_sale_query = clone $collection_of_amount;
        $renew_sale_query = clone $collection_of_amount;
        $due_amount_query = clone $collection_of_amount;

        $total_new_sale = $new_sale_query->whereIn('payment_history.exe_id',$emp_managers)->where('payment_for','new')->sum('paid_amount');

        $total_renew_sale =  $renew_sale_query->whereIn('payment_history.exe_id',$emp_managers)->where('payment_for','Renew')->sum('paid_amount');

        $due_amount =  $due_amount_query->whereIn('payment_history.exe_id',$emp_managers)->where('payment_for','Due Amount')->sum('paid_amount');

        $today_collection_sum  = $today_collection->sum('paid_amount');


$clientsWithPackages = DB::connection('sales_db')
    ->table('package_info')
    ->select('client_id', DB::raw('COUNT(*) as package_count'))
    ->groupBy('client_id')
    ->having('package_count', '>=',1)
    ->whereIn('exe_id', $emp_managers)
    ->count();

$startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
$endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');


$clients_no_by_packages = DB::connection('sales_db')
    ->table('package_info')
    ->select('client_id')
    ->whereNotIn('client_id', function ($query) use ($startOfMonth, $endOfMonth) {
        $query->select('client_id')
              ->from('package_info')
              ->whereBetween('created_date', [$startOfMonth, $endOfMonth])
              ->groupBy('client_id');
    })
    ->groupBy('client_id')
    ->havingRaw('COUNT(*) >= 1')
     ->whereIn('exe_id', $emp_managers)
    ->count();
    
  $today_collection_sum = $today_collection->whereIn('payment_history.exe_id',$emp_managers)->whereDate('created_date',Carbon::now()
                         ->format('Y-m-d'))->sum('paid_amount');
                         
  $today_followup_sum = $today_followup->whereIn('clients_followup_log.created_by',$emp_managers)->whereDate('created_date',Carbon::now()
  ->format('Y-m-d'))->whereIn('followup_id',[6,9])->count();

  //return $today_followup_sum;


$package_activation = $package_pending_activation->whereIn('exe_id', $emp_managers)
        ->where(function ($query) {
            $query->where('admin_status', 0)
                  ->where('finance_status', 1)
                  ->orWhere(function ($query) {
                      $query->where('admin_status', 1)
                            ->where('finance_status', 0);
                  })
                  ->orWhere(function ($query) {
                      $query->where('admin_status', 0)
                            ->where('finance_status', 0);
                  });
        })->count();

        $total_no_of_followup =  $total_followup->whereIn('clients_followup_log.created_by', $emp_managers)->count();

        $forcast_accuracy_data = $forcast_accuracy->whereIn('clients_followup_log.created_by', $emp_managers)->where('followup_id',19)->count();
        

        

        $check_today_missing_target = DB::connection('sales_db')
                                     ->table('kra_and_kpi_details')->whereIn('created_by',$emp_managers)->whereDate('created_date',Carbon::now()->format('Y-m-d'))->sum('remaining_followup');
        //return  $check_today_missing_target;
        $check_extra_followup = DB::connection('sales_db')
                                     ->table('kra_and_kpi_details')->whereIn('created_by',$emp_managers)->whereDate('created_date',Carbon::now()->format('Y-m-d'))->sum('extra_followup');

        if($total_no_of_followup>0){
            $forcast_accuracy_percent = round(($forcast_accuracy_data/$total_no_of_followup)*100);

        }
        else{
            $forcast_accuracy_percent = 0;

        }

        $data_list = array('renew' => $renewal_data,'renewal_amount'=>$renewal_amount,
                    'collection'=>$total_collection,'new_sale'=>$total_new_sale,'renew_sale'=>$total_renew_sale,
                    'package_activation'=>$package_activation,'due_amount'=>$due_amount,'no_of_followup'=> $total_no_of_followup,
                    'accuracy_percent'=>$forcast_accuracy_percent,'missing_target'=>$check_today_missing_target,
                    'extra_followup'=>$check_extra_followup,'active_client'=>$clientsWithPackages,
                    'regular_client_dead'=>$clients_no_by_packages,
                    'today_collection'=>$today_collection_sum,'followup_sum'=>$today_followup_sum);

       return response()->json(['status'=>200,'data'=> $data_list,'message'=>'Sales Dashboard Data']);
}

public function sales_inner_page_description(Request $request)
{
    $data_array = [];

    // Determine the list of employee managers based on the request
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

    // Query the payment_history table
    $query = DB::connection('sales_db')->table('payment_history')
        ->leftJoin('company_info', 'company_info.comp_id', 'payment_history.comp_id')
        ->leftJoin('group_names', 'group_names.group_id', 'company_info.group_id')
        ->leftJoin('product', 'product.id', 'payment_history.product_id')
        ->leftJoin('product_service', 'product_service.id', 'payment_history.service_id')
        ->select(
            'payment_history.*',
            'group_names.name as group_name',
            'product.product_name',
            'product_service.service_name'
        );

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
    if ($request->group) {
        $query->where('company_info.group_id', $request->group);
    }
    if ($request->start_date && $request->end_date) {
        $query->whereDate('payment_history.created_date', '>=', $request->start_date)
              ->whereDate('payment_history.created_date', '<=', $request->end_date);
    } else {
        $query->whereMonth('payment_history.created_date', Carbon::now()->month);
    }

    // Apply pagination if not downloading
    if (!$request->download) {
        $paymentHistory = $query->whereIn('payment_history.exe_id', $emp_managers)->paginate(10);

        // Fetch employee information
        $employeeIds = $paymentHistory->pluck('exe_id')->unique();
        $employees = DB::table('emp_basic_info')
            ->whereIn('emp_id', $employeeIds)
            ->get()
            ->keyBy('emp_id');

        // Fetch reporting managers
        $managerIds = $employees->pluck('reporting_manager')->unique();
        $managers = DB::table('emp_basic_info')
            ->whereIn('emp_id', $managerIds)
            ->get()
            ->keyBy('emp_id');

        // Map the employee data to the payment history data
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
        // Handle CSV export
        $filename = "sales_data_" . Carbon::now()->format('Ymd_His') . ".csv";
        $handle = fopen($filename, 'w+');
        fputcsv($handle, ['ID', 'Amount', 'Product', 'Service', 'Employee Name', 'Manager', 'Payment For', 'Date', 'Group']);

        // Fetch the data for CSV export
        $paymentHistory = $query->whereIn('payment_history.exe_id', $emp_managers)->get();

        // Fetch employee information
        $employeeIds = $paymentHistory->pluck('exe_id')->unique();
        $employees = DB::table('emp_basic_info')
            ->whereIn('emp_id', $employeeIds)
            ->get()
            ->keyBy('emp_id');

        // Fetch reporting managers
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
                $row->pay_id,
                $row->paid_amount,
                $row->product_name,
                $row->service_name,
                $row->exe_first_name . ' ' . $row->exe_last_name,
                $row->reporting_manager_first_name . ' ' . $row->reporting_manager_last_name,
                $row->payment_for,
                $row->created_date,
                $row->group_name
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
    }

    if ($request->product) {
        $query->where('product_id', $request->product);
    }

    if ($request->service) {
        $query->where('service_id', $request->service);
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

        $emp_name = DB::table('emp_basic_info')
            ->where('emp_id', $row->exe_id)
            ->first();
            
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
            

        $data_array[] = [
            'id' => $row->package_id,
            'group' => $groups ?? '',
            'product' => $product ?? '',
            'service' => $service ?? '',
            'emp_name' => ($emp_name->emp_fname ?? '') . ' ' . ($emp_name->emp_lame ?? ''),
            'manager_name' => $manager_name,
            'amount' => $row->paid_amount,
            'created_date' => $row->created_date,
        ];
    }

    if ($request->download) {
        $filename = "sales_data_" . Carbon::now()->format('Ymd_His') . ".csv";
        $handle = fopen($filename, 'w+');
        fputcsv($handle, ['ID', 'Group', 'Product', 'Service', 'Employee Name', 'Manager', 'Amount', 'Date']);

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
    if($emp_details->dept_id==3 &&  $is_reporting_manager){

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
    ->select('client_id',DB::raw('MAX(exe_id) as exe_id'),DB::raw('MAX(group_id) as group_id'), DB::raw('COUNT(*) as package_count'))
    ->groupBy('client_id')
    ->having('package_count', '>=',1)
    ->whereIn('exe_id',$emp_managers)
    ->paginate(10);
    }
    if($request->type =='regularClientDead'){
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');


    $clientsWithPackages = DB::connection('sales_db')
    ->table('package_info')
    ->select('client_id',DB::raw('MAX(exe_id) as exe_id'),DB::raw('MAX(group_id) as group_id'), DB::raw('COUNT(*) as package_count'))
    ->whereNotIn('client_id', function ($query) use ($startOfMonth, $endOfMonth) {
        $query->select('client_id')
              ->from('package_info')
              ->whereBetween('created_date', [$startOfMonth, $endOfMonth])
              ->groupBy('client_id');
    })
      ->groupBy('client_id')
      ->havingRaw('COUNT(*) >= 1')
     ->whereIn('exe_id', $emp_managers)
     ->paginate(10);
    }
    
    foreach($clientsWithPackages as $row){
        $client_details = DB::connection('sales_db')->table('client_info')->where('client_id',$row->client_id)->first();
        
        $client_last_package = DB::connection('sales_db')->table('package_info')->where('client_id',$row->client_id)->orderBy('package_id','DESC')->first();
        
        $emp_name = DB::table('emp_basic_info')->where('emp_id',$row->exe_id)->first();
        
        $emp_manager =  DB::table('emp_basic_info')->where('emp_id',$emp_name->reporting_manager)->first();
        
        $group = DB::connection('sales_db')->table('group_names')->where('group_id',$row->group_id)->first();
        
       $client_data[] = array(
         'client_id' => $row->client_id ?? '',
         'client_name' => $client_details->client_name ?? '',
         'group' => $group->name ?? '',
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
        $total_payment_for_followup_acheived  = (clone $query)->whereIn('created_by',$emp_managers)->where('followup_id',6)->count();
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

    public function show_business_lead_data($emp_id) {
        $data_array = [];
    
        $assigned_groups = BasicInfo::where('emp_id', $emp_id)->pluck('assigned_group')->first();
        $group_ids = explode(',', $assigned_groups);
        $group_ids = array_map('trim', $group_ids);
    
       
        $get_data = DB::connection('sales_db')
            ->table('guest_user_details')
            ->where('status', 0)
            ->whereIn('reg_type', [2, 3, 4])
            ->where(function ($query) use ($group_ids, $emp_id) {
                $query->whereIn('group_id', $group_ids)
                      ->orWhere('created_by', $emp_id);
            })
            ->get();
    
        
        foreach ($get_data as $row) {
            
            $group = DB::connection('sales_db')
                ->table('group_names')
                ->where('group_id', $row->group_id)
                ->first();
    
            
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
    $sales_team = BasicInfo::where('dept_id', 3)
        ->where('emp_status', 1)
        ->where('desi_id', '!=', 38)
        ->pluck('emp_id');

    foreach ($sales_team as $row) {
        $client_id_array = [];
        $current_date = Carbon::now()->format('Y-m-d');

        // Fetch today's follow-ups for the current sales team member
        $followups_today = DB::connection('sales_db')->table('clients_followup_log')
            ->where('created_by', $row)
            ->whereDate('next_followup_date', $current_date)
            ->pluck('client_id')
            ->toArray();

        // Fetch the last inserted record which is not from today
        $last_inserted_record = DB::connection('sales_db')->table('missing_followups_count')
            ->where('created_by', $row)
            ->whereDate('created_date', '!=', $current_date)
            ->orderBy('id', 'DESC')
            ->first();

        if ($last_inserted_record) {
            $last_client_id = $last_inserted_record->client_id;
            $merged_data = array_merge($followups_today, explode(',', $last_client_id));
            $merged_data = array_unique($merged_data);
            $missing_followup = $last_inserted_record->left_followup;
        } else {
            $merged_data = $followups_today;
            $missing_followup = 0;
        }

        // Check if each client has a follow-up created today
        foreach ($merged_data as $value) {
            $exists = DB::connection('sales_db')->table('clients_followup_log')
                ->where('created_by', $row)
                ->where('client_id', $value)
                ->whereDate('created_date', $current_date)
                ->exists();

            if (!$exists) {
                $client_id_array[] = $value;
            }
        }

        $total_target = count($merged_data);
        $missing = count($client_id_array);
        $achieved = $total_target - $missing;
        $total_achieved = $achieved > 0 ? $achieved : 0;

        DB::connection('sales_db')->table('missing_followups_count')->updateOrInsert(
            [
                'created_by' => $row,
                'created_date' => $current_date
            ],
            [
                'today_followup' => $total_target,
                'left_followup' => $missing,
                'acheived_followup' => $total_achieved,
                'client_id' => implode(',', $client_id_array),
            ]
        );
    }
}



public function count_sales_followup(Request $request){
    $details_array = [];
     if($request->manager && !$request->employee) {
       //return 'kk';
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

    } elseif(!$request->manager && $request->employee) {
        $emp_managers = [$request->employee];
        //return  $emp_managers;
    } 
    elseif($request->manager && $request->employee) {
       // return $request->employee;
        $emp_managers = $request->employee;
        if (!is_array($emp_managers)) {
            $emp_managers = [$emp_managers];
        }
        //return  $emp_managers;
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

    $data = DB::connection('sales_db')->table('missing_followups_count')->whereIn('created_by',$emp_managers)->whereDate('created_date',now()->format('Y-m-d'))->sum('left_followup');
    $kra_kp_data = DB::connection('sales_db')->table('kra_and_kpi_details')->whereIn('created_by',$emp_managers)->whereDate('created_date',now()->format('Y-m-d'))->sum('remaining_followup');

    $followup_history = DB::connection('sales_db')
    ->table('missing_followups_count')
    ->whereIn('created_by', $emp_managers)
     ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
    ->get();

foreach ($followup_history as $clients) {
    if ($clients->client_id != '') {
        $client_ids = explode(',', $clients->client_id);
          foreach ($client_ids as $client_id) {
            $client_id = trim($client_id);
            $client_name = DB::connection('sales_db')
                ->table('guest_user_details') 
                ->where('id', $client_id)
                ->value('name'); 

            $emp_name = BasicInfo::where('emp_id',$clients->created_by)->first();
            $emp_manager = BasicInfo::where('emp_id',$emp_name->reporting_manager)->first();
            $details_array[] = array('id'=>$clients->id,'client_name'=>$client_name,'emp_name'=>$emp_name->emp_fname.' '.$emp_name->emp_lame,'manager'=>$emp_manager->emp_fname.' '.$emp_manager->emp_lame,'date'=>$clients->created_date);


        }
    }
}
   


    return response()->json(['status'=>200,'followup_data'=>$data,'kra_kpi_data'=>$kra_kp_data,'details'=>$details_array]);

}

public function send_sales_notification($emp_id){

    $emp_managers = DB::table('employee_managers')
            ->where(function($query) use ($emp_id) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id])
                      ->orWhere('dept_manager', $emp_id);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();
    if(!empty($emp_managers)){
    $data = DB::connection('sales_db')->table('missing_followups_count')
    ->whereIn('created_by', $emp_managers)
    ->whereDate('created_date', Carbon::now()->subDay()->format('Y-m-d'))
    ->sum('left_followup');

    if($data>0){
        $type = 'missing_followup';
        $notification = 'Click To View Yesterday Missing Followup';
        $data_array = array('emp_id'=>$emp_id,'type'=> $type,'notification'=>$notification);
        $get_last_record = DB::table('emp_notifications')->where('type','missing_followup')->where('emp_id',$emp_id)->whereDate('created_date',now()->format('Y-m-d'))->exists();
        if(!$get_last_record){
              DB::table('emp_notifications')->insert($data_array);

        }




    }
    $kra_kp_data = DB::connection('sales_db')->table('kra_and_kpi_details')->whereIn('created_by',$emp_managers) ->whereDate('created_date', Carbon::now()->subDay()->format('Y-m-d'))->sum('remaining_followup');

    if($kra_kp_data>0){
         $type = 'missing_kra_kpi';
         $notification = 'Click To View Yesterday Missing Kra Kpi';
        $data_array = array('emp_id'=>$emp_id,'type'=> $type,'notification'=>$notification);
         $get_last_record = DB::table('emp_notifications')->where('type','missing_kra_kpi')->where('emp_id',$emp_id)->whereDate('created_date',now()->format('Y-m-d'))->exists();
         if(!$get_last_record){
            DB::table('emp_notifications')->insert($data_array);

         }
      }
   }
}

public function missing_kra_kpi_history(Request $request) {
    DB::table('emp_notifications')->where('emp_id',$request->emp_id)->where('type','missing_kra_kpi')->update(['seen_status'=>1]);

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
       // ->whereDate('created_date', Carbon::now()->subDay()->format('Y-m-d'))
       
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
                'emp_name' => $emp_name->emp_fname,
                'manager' => $manager->emp_fname,
                'count' => $row->remaining_followup,
                'date' => $row->created_date,
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

        $data_array = array('collection'=>$total_collection,'followups'=>$total_followups,'new_sale'=> $new_sale,'renew'=>$renew_sale,'adword'=>$total_lead_adwords,'organic'=>$total_lead_organic);

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
    $data_array = [];
    $data = DB::connection('sales_db')->table('enquiry_info');
    if($request->group){
        $data->where('group_id',$request->group);
        
    }
    else{
        $data->where('group_id',$request->group_id);
        
    }
    if($request->product){
        $data->where('product_id',$request->product);
        
    }
    if($request->category){
        $data->where('category_id',$request->category);
        
        
    }
    if($request->service){
         $data->where('service_id',$request->service);
        
    }
     if($request->start_date && $request->end_date){
        $data->whereDate('created_date', '>=',$request->start_date)
        ->whereDate('created_date', '<=',$request->end_date);
        
    }
    else{
         $data->whereDate('created_date',$request->date);
    }
    $records = $data->whereIn('followup_status',[13,17])->paginate(10);
    foreach($records as $row){
            $group = DB::connection('sales_db')->table('group_names')->where('group_id',$row->group_id)->first();
            $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();
            $service = DB::connection('sales_db')->table('product_service')->where('id',$row->service_id)->first();
            $category = DB::connection('sales_db')->table('product_category')->where('id',$row->category_id)->first();
            $customer = DB::connection('sales_db')->table('customer_info')->where('id',$row->customer_id)->first();
            $enq_status = DB::connection('sales_db')->table('enq_status')->where('id',$row->followup_status)->first();
                
            $data_array[] = array('id'=>$row->id,'product'=>$product->product_name??'','group'=>$group->name??'','service'=>$service->service_name??'','category'=> $category->category_name??'','customer'=>$customer->name??'','created_date'=>$row->created_date,'enq_status'=>$enq_status->name??'','sent_count'=>$row->sent_count,'expected_amount'=>$row->expected_value);
            
            
        
    }
      return response()->json(['status'=>200,'data'=>$data_array,'last_page'=>$records->lastPage()]);
        
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
    } else if ($request->emp_id == $get_emp_manager->reporting_manager) {
        DB::connection('sales_db')->table('kra_and_kpi_details')
            ->where('id', $request->id)
            ->update(['manager_remark' => $request->remark]);
    } else if ($request->emp_id == 'RIMS1') {
        DB::connection('sales_db')->table('kra_and_kpi_details')
            ->where('id', $request->id)
            ->update(['superadmin_remark' => $request->remark]);
    } else {
        return response()->json(['status' => 403, 'message' => 'Unauthorized to add remark'], 403);
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
        ->select('clients_followup_log.*', 'company_info.client_name', 'company_info.group_id as company_info_group', 'guest_user_details.group_id as guest_group');

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
        $data->whereDate('clients_followup_log.created_date', '>=', $request->start_date)
            ->whereDate('clients_followup_log.created_date', '<=', $request->end_date);
    } else {
        $data->whereDate('clients_followup_log.created_date', Carbon::now()->format('Y-m-d'));
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
        $data->where('clients_followup_log.disposition', 'Connected');
    }
    if ($request->type == 'mature_followup') {
        $data->where('clients_followup_log.followup_id', 19);
    }
    if ($request->type == 'dead_followup') {
        $data->where('clients_followup_log.followup_id', 15);
    }
    if ($request->type == 'total_amount') {
        $data->where('clients_followup_log.amount', '!=', ' ');
    }

    $data->whereIn('clients_followup_log.created_by', $emp_managers);
    $query = $data->paginate(10);

    foreach ($query as $row) {
        if ($row->client_type == 1) {
            $client_details = DB::connection('sales_db')->table('guest_user_details')->where('id', $row->client_id)->first();

            $client_name = $client_details->name??'';
            $group_id = $client_details->group_id??'';
            $comp_name =  $client_details->business_name??'';
        } else {
            $client_details = DB::connection('sales_db')->table('company_info')->where('comp_id', $row->comp_id)->first();
            $client_name = $client_details->client_name??'';
            $group_id = $client_details->group_id??'';
            $comp_name =  $client_details->business_name??'';
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
        ];
    }

    return response()->json(['status' => 200, 'data' => $data_array]);
}


public function carry_forward_followups(Request $request) {
    $data_array = [];
    
    // Determine employee managers
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
        ->select(
            'clients_followup_log.comp_id',
            'clients_followup_log.client_id',
            'clients_followup_log.created_by',
            'guest_user_details.name as guest_client_name',
            'guest_user_details.business_name as guest_client_business',
            DB::raw('MAX(clients_followup_log.created_date) as last_followup_date'),
            DB::raw('MAX(clients_followup_log.client_type) as client_type'),
            DB::raw('MAX(clients_followup_log.next_followup_date) as next_followup_date'),
            DB::raw('MAX(clients_followup_log.product_id) as product_id'),
            DB::raw('MAX(clients_followup_log.category_id) as category_id'),
            DB::raw('MAX(clients_followup_log.service_id) as service_id'),
            'company_info.client_name',
            'company_info.business_name as company_business_name',
            'guest_user_details.group_id as guest_group_id', 

            'company_info.group_id as company_group_id' 
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

    if($request->product){
        $data->where('clients_followup_log.product_id',$request->product);

    }
    if($request->service){
         $data->where('clients_followup_log.service_id',$request->service);

    }
    if($request->category){
         $data->where('clients_followup_log.category_id',$request->category);

    }

    if ($request->start_date && $request->end_date) {
        $data->whereDate('clients_followup_log.created_date', '>=', $request->start_date)
              ->whereDate('clients_followup_log.created_date', '<=', $request->end_date);
    } else {
        $data->whereMonth('clients_followup_log.created_date', Carbon::now()->month);
    }

    $query = $data->groupBy('clients_followup_log.comp_id', 'clients_followup_log.client_id', 'clients_followup_log.created_by')
                  ->paginate(10);

   
    foreach ($query as $row) {
        $no_of_times_carry_forward = DB::connection('sales_db')->table('clients_followup_log')
            ->where('client_id', $row->client_id)
            ->where('comp_id', $row->comp_id)
            ->where('created_by', $row->created_by)
            ->where('next_followup_date', '!=', '')
            ->count();

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

             $group =  DB::connection('sales_db')->table('group_names')
            ->where('group_id', $row->guest_group_id)
            ->first();
            if($group){
                $group_name = $group->name;
            }
            else{
                 $group_name = '';

            }

        } elseif ($row->client_type == 2) {
             $client_name = $row->client_name;
             $company_name = $row->company_business_name;
             $group =  DB::connection('sales_db')->table('group_names')
            ->where('group_id', $row->company_group_id)
            ->first();

            if($group){
                $group_name = $group->name;
            }
            else{
                 $group_name = '';

            }
        } else {
            $client_name = 'Unknown';
            $group_name = '';
            $company_name = '';
        }

        $data_array[] = array(
            'comp_id' => $row->comp_id,
            'client_id' => $row->client_id,
            'client_type'=>$row->client_type,
            'last_followup_date' => $last_inserted_id->created_date ?? null,
            'next_followup_date' => $last_inserted_id->next_followup_date ?? null,
            'no_of_times_carry_forward' => $no_of_times_carry_forward,
            'client_name' => $client_name,
            'company_name'=>$company_name,
            'product' => $product->product_name ?? '',
            'category' => $category->category_name ?? '',
            'service' => $service->service_name ?? '',
            'emp_name' => ($emp_name->emp_fname ?? '') . ' ' . ($emp_name->emp_lame ?? ''),
            'manager' => ($manager_name->emp_fname ?? '') . ' ' . ($manager_name->emp_lame ?? ''),
            'group'=>$group_name??'',
        );
    }

    return response()->json(['status' => 200, 'data' => $data_array,'last_page'=>$query->lastPage()]);
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
    $data = DB::connection('sales_db')->table('company_info')->where('status', 2);
    if($request->group) {
        $data->where('group_id', $request->group);
    }

    if($request->product) {
        $data->where('product_id', $request->product);
    }

    $query = $data->whereIn('exe_id', $emp_managers)->paginate(10);

    foreach ($query as $row) {
        $group_name = DB::connection('sales_db')->table('group_names')->where('group_id', $row->group_id)->first();
        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();

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
            'id' => $row->comp_id,
            'client_name' => $row->client_name ?? '',
            'city' => $row->city ?? '',
            'group' => $group_name->name ?? '',
            'emp_name' => $emp_name,
            'manager_name' => $manager_name,
            'product'=>$product->product_name??'',
            'company_name'=>$row->business_name??'',
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

    $data = DB::connection('sales_db')->table('package_info')->where('package_status', 1);
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
    } else {
        $data->whereMonth('created_date', Carbon::now()->month);
    }

    $query = $data->whereIn('exe_id', $emp_managers)->paginate(10);

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
        ];
    }

    return response()->json(['status' => 200, 'data' => $data_array, 'last_page' => $query->lastPage()]);
}

public function group_loss(Request $request) {
    $emp_groups = DB::table('emp_basic_info')->where('emp_id', $request->emp_id)->first();

    $group_ids = $emp_groups->assigned_group ? explode(',', $emp_groups->assigned_group) : [];

    $data = DB::connection('sales_db')->table('enquiry_info')
        ->where('enq_status', 1)
        ->where('sent_count', '<', 4);

    $extra_sent = DB::connection('sales_db')->table('package_info')
        ->where('package_duration', 2)
        ->where('sent_lead', '>', 'total_lead');

    if ($request->group) {
        $data->where('enquiry_info.group_id', $request->group);
        $extra_sent->where('package_info.group_id', $request->group);
    }

    if($request->product){
        $data->where('enquiry_info.product_id', $request->product);
        $extra_sent->where('package_info.product_id', $request->product);

    }

    if ($request->start_date && $request->end_date) {
        $data->whereDate('created_date', '>=', $request->start_date)
             ->whereDate('created_date', '<=', $request->end_date);
        $extra_sent->whereDate('created_date', '>=', $request->start_date)
                   ->whereDate('created_date', '<=', $request->end_date);
    } else {
        $data->whereMonth('created_date', Carbon::now()->month);
        $extra_sent->whereMonth('created_date', Carbon::now()->month);
    }

    $groupedData = $data->select('enquiry_info.group_id', 'group_names.name', 
                                  DB::raw('COUNT(*) as total_enquiries'), 
                                  DB::raw('SUM(sent_count) as total_sent_count'))
        ->leftJoin('group_names', 'enquiry_info.group_id', '=', 'group_names.group_id')
        ->groupBy('enquiry_info.group_id', 'group_names.name')
        ->whereIn('enquiry_info.group_id', $group_ids)
        ->get();

    $extra_sent_data = $extra_sent->select('package_info.group_id', 
                                  DB::raw('SUM(total_lead) as total_lead'), 
                                  DB::raw('SUM(sent_lead) as sent_lead'))
        ->leftJoin('group_names', 'package_info.group_id', '=', 'group_names.group_id')
        ->groupBy('package_info.group_id')
        ->whereIn('package_info.group_id', $group_ids)
        ->get();

    $extraSentMapping = [];
    foreach ($extra_sent_data as $extra) {
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

        $groupLoss[] = [
            'group_id' => $group->group_id,
            'group_name' => $group->name,
            'not_sent' => $not_sent,
            'extra_sent' => $lead_difference,
            'total_diff_sent_count' => $total_diff_sent_count,
        ];
    }

    return response()->json(['status' => 200, 'data' => $groupLoss]);
}



public function category_loss(Request $request) {
    $data = DB::connection('sales_db')->table('enquiry_info')
        ->where('enq_status', 1)
        ->where('sent_count', '<', 4);

    $extra_sent = DB::connection('sales_db')->table('service_info')
        ->join('package_info', 'package_info.package_id', '=', 'service_info.package_id')
        ->where('package_info.package_duration', 2)
        ->where('service_info.sent_lead', '>', 'service_info.total_lead');

    if ($request->category) {
        $data->where('category_id', $request->category);
        $extra_sent->where('service_info.category_id', $request->category);
    }

    if ($request->start_date && $request->end_date) {
        $data->whereDate('enquiry_info.created_date', '>=', $request->start_date)
             ->whereDate('enquiry_info.created_date', '<=', $request->end_date);
        $extra_sent->whereDate('service_info.created_date', '>=', $request->start_date)
                   ->whereDate('service_info.created_date', '<=', $request->end_date);
    } else {
        $data->whereMonth('enquiry_info.created_date', Carbon::now()->month);
        $extra_sent->whereMonth('service_info.created_date', Carbon::now()->month);
    }

    $groupedData = $data->select('enquiry_info.category_id', 'product_category.category_name',
                                  DB::raw('COUNT(*) as total_enquiries'), 
                                  DB::raw('SUM(sent_count) as total_sent_count'))
        ->leftJoin('product_category', 'enquiry_info.category_id', '=', 'product_category.id')
        ->groupBy('enquiry_info.category_id', 'product_category.category_name')
        ->get();

    $extra_sent_data = $extra_sent->select('service_info.category_id', 
                                  DB::raw('SUM(service_info.total_lead) as total_lead'), 
                                  DB::raw('SUM(service_info.sent_lead) as sent_lead'))
        ->leftJoin('product_category', 'service_info.category_id', '=', 'product_category.id')
        ->groupBy('service_info.category_id')
        ->get();

    $extraSentMapping = [];
    foreach ($extra_sent_data as $extra) {
        $extraSentMapping[$extra->category_id] = $extra; 
    }

    $groupLoss = [];
    foreach ($groupedData as $group) {
        $total_expected_sent_count = $group->total_enquiries * 4; 
        $total_diff_sent_count = $total_expected_sent_count - $group->total_sent_count;
        $not_sent = $total_expected_sent_count - $group->total_sent_count;

        $lead_difference = 0; 

        if (isset($extraSentMapping[$group->category_id])) {
            $total_lead = $extraSentMapping[$group->category_id]->total_lead ?? 0;
            $sent_lead = $extraSentMapping[$group->category_id]->sent_lead ?? 0;

            $lead_difference = $sent_lead - $total_lead; 
        }

        $total_diff_sent_count += $lead_difference; 

        $groupLoss[] = [
            'category_id' => $group->category_id,
            'category_name' => $group->category_name,
            'extra_sent' => $lead_difference,
            'not_sent' => $not_sent,
            'total_diff_sent_count' => $total_diff_sent_count,
        ];
    }

    return response()->json(['status' => 200, 'data' => $groupLoss]);
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

    $data = DB::connection('sales_db')->table('service_info')
        ->leftJoin('package_info', 'service_info.package_id', '=', 'package_info.package_id')
        ->leftJoin('group_names', 'group_names.group_id', '=', 'package_info.group_id')
        ->leftJoin('product_category', 'product_category.id', '=', 'service_info.category_id');

    if ($request->product) {
        $data->where('package_info.product_id', $request->product);
    }
    if ($request->group) {
        $data->where('package_info.group_id', $request->group);
    }
    if ($request->service) {
        $data->where('package_info.service_id', $request->service);
    }
    if ($request->category) {
        $data->where('service_info.category_id', $request->category);
    }
    $query = $data->whereIn('package_info.exe_id', $emp_managers)
        ->select(
            'group_names.name',
            'product_category.category_name',
            'product_category.id',
            'group_names.group_id',
            DB::raw('SUM(service_info.sent_lead) as total_sent_lead')
        )
        ->groupBy('group_names.group_id', 'product_category.id')
        ->get();

    return response()->json(['status'=>200,'data'=>$query]);
}

public function lead_sale_price(Request $request,$emp_id){

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
            ->where(function($query) use ($emp_id) {
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id]);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $data = DB::connection('sales_db')->table('service_info')
        ->leftJoin('package_info', 'service_info.package_id', '=', 'package_info.package_id')
        ->leftJoin('group_names', 'group_names.group_id', '=', 'package_info.group_id')
        ->leftJoin('product_category', 'product_category.id', '=', 'service_info.category_id')
        ->leftjoin('leads_info','leads_info.package_id','service_info.package_id');

    if ($request->product) {
        $data->where('package_info.product_id', $request->product);
    }
    if ($request->group) {
        $data->where('package_info.group_id', $request->group);
    }
    if ($request->service) {
        $data->where('package_info.service_id', $request->service);
    }
    if ($request->category) {
        $data->where('service_info.category_id', $request->category);
    }
    if ($request->start_date && $request->end_date) {
        $data->whereDate('leads_info.sent_date', '>=', $request->start_date)
             ->whereDate('leads_info.sent_date', '<=', $request->end_date);
    } else {
        $data->whereMonth('leads_info.sent_date', Carbon::now()->month);
    }

    $query = $data->whereIn('package_info.exe_id', $emp_managers)
        ->select(
            'group_names.name',
            'product_category.category_name',
            'product_category.id',
            'group_names.group_id',
            DB::raw('SUM(leads_info.lead_value) as total_sent_value')
        )
        ->groupBy('package_info.group_id', 'service_info.category_id')
        ->get();

    return response()->json(['status'=>200,'data'=>$query]);

}


}
    