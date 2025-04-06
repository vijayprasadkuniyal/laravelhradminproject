<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\BasicInfo;
use Carbon\Carbon;
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
   // return $request->all();
    $total_followup_for_meeting_count = 0;
    $total_followup_for_payment_count = 0;
    $total_business_proposal_count = 0;
    $no_of_call_connected_count = 0;
    $total_dead_client_count = 0;
    $total_mature_client_count = 0;
    $total_amount_sum = 0;
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
            ->where('subattribute_id',16)->where('financial_year', $financialYear)->where('department_id',3)
            ->first();

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

    } elseif($request->manager && $request->employee) {
        $emp_managers = $request->employee;
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


    $followup_for_meeting = DB::connection('sales_db')->table('clients_followup_log');
    $followup_for_payment = DB::connection('sales_db')->table('clients_followup_log');
    $business_proposal = DB::connection('sales_db')->table('clients_followup_log');
    $no_of_call_connected = DB::connection('sales_db')->table('clients_followup_log');
    $total_mature_client = DB::connection('sales_db')->table('clients_followup_log');
    $total_dead_client = DB::connection('sales_db')->table('clients_followup_log');
    $total_amount = DB::connection('sales_db')->table('clients_followup_log');
    $forcast_accuracy = DB::connection('sales_db')->table('clients_followup_log');

    if($request->product) {
        $followup_for_meeting->where('product_id', $request->product);
        $followup_for_payment->where('product_id', $request->product);
        $business_proposal->where('product_id', $request->product);
        $no_of_call_connected->where('product_id', $request->product);
        $total_mature_client->where('product_id', $request->product);
        $total_dead_client->where('product_id', $request->product);
        $total_amount->where('product_id', $request->product);
        $forcast_accuracy->where('product_id',$request->product);
    }

    if($request->service) {
        $followup_for_meeting->where('service_id', $request->service);
        $followup_for_payment->where('service_id', $request->service);
        $business_proposal->where('service_id', $request->service);
        $no_of_call_connected->where('service_id', $request->service);
        $total_mature_client->where('service_id', $request->service);
        $total_dead_client->where('service_id', $request->service);
        $total_amount->where('service_id', $request->service);
        $forcast_accuracy->where('service_id',$request->service);
    }

    if($request->start_date && $request->end_date) {
        $followup_for_meeting->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $followup_for_payment->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $business_proposal->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $no_of_call_connected->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $total_mature_client->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $total_dead_client->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $total_amount->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $forcast_accuracy->whereBetween('created_date', [$request->start_date, $request->end_date]);
    } else {
        $followup_for_meeting->whereMonth('created_date', Carbon::now()->month);
        $followup_for_payment->whereMonth('created_date', Carbon::now()->month);
        $business_proposal->whereMonth('created_date', Carbon::now()->month);
        $no_of_call_connected->whereMonth('created_date', Carbon::now()->month);
        $total_mature_client->whereMonth('created_date', Carbon::now()->month);
        $total_dead_client->whereMonth('created_date', Carbon::now()->month);
        $total_amount->whereMonth('created_date', Carbon::now()->month);
        $forcast_accuracy->whereMonth('created_date', Carbon::now()->month);
    }

    if (!empty($emp_managers)) {
        $total_followup_for_meeting_count += $followup_for_meeting->whereIn('created_by', $emp_managers)->where('followup_id', 7)->count();
        $total_followup_for_payment_count += $followup_for_payment->whereIn('created_by', $emp_managers)->where('followup_id', 6)->count();
        $total_business_proposal_count += $business_proposal->whereIn('created_by', $emp_managers)->where('followup_id', 5)->count();
        $no_of_call_connected_count += $no_of_call_connected->whereIn('created_by', $emp_managers)->where('disposition', 'Connected')->count();
        $total_mature_client_count += $total_mature_client->whereIn('created_by', $emp_managers)->where('followup_id', 19)->count();
        $total_dead_client_count += $total_dead_client->whereIn('created_by', $emp_managers)->where('followup_id', 15)->count();
        $total_amount_sum += $total_amount->whereIn('created_by', $emp_managers)->sum('amount');
         $forcast_accuracy_data =  $forcast_accuracy->whereIn('created_by', $emp_managers)->where('followup_id',19)->count();
         //return $forcast_accuracy_data;
         if($forcast_accuracy_data>0){
            $forcast_accuracy_percent =  $forcast_accuracy_data/$no_of_call_connected_count*100;

        }
        else{
            $forcast_accuracy_percent = 0;

        }
        if($required_percent->child_attribute_value<=$forcast_accuracy_percent){
            $target_matched = 'yes';


        }
        else{
             $target_matched = 'No';

        }
    } else {
        $total_followup_for_meeting_count += $followup_for_meeting->where('created_by', $request->emp_id)->where('followup_id', 7)->count();
        $total_followup_for_payment_count += $followup_for_payment->where('created_by', $request->emp_id)->where('followup_id', 6)->count();
        $total_business_proposal_count += $business_proposal->where('created_by', $request->emp_id)->where('followup_id', 5)->count();
        $no_of_call_connected_count += $no_of_call_connected->where('created_by', $request->emp_id)->where('disposition', 'Connected')->count();
        $total_mature_client_count += $total_mature_client->where('created_by', $request->emp_id)->where('followup_id', 19)->count();
        $total_dead_client_count += $total_dead_client->where('created_by', $request->emp_id)->where('followup_id', 15)->count();
        $total_amount_sum += $total_amount->where('created_by', $request->emp_id)->sum('amount');
         $forcast_accuracy_data = $forcast_accuracy->where('created_by', $request->emp_id)->where('followup_id',19)->count();
        $forcast_accuracy_data =  $forcast_accuracy->where('created_by', $request->emp_id)->where('followup_id',19)->count();
         if($forcast_accuracy_data>0){
            $forcast_accuracy_percent =  $forcast_accuracy_data/$no_of_call_connected_count*100;

        }
        else{
            $forcast_accuracy_percent = 0;

        }
         if($required_percent->child_attribute_value<=$forcast_accuracy_percent){
            $target_matched = 'yes';


        }
        else{
             $target_matched = 'No';

        }
    }

    $total_data_array = [
        'meeting' => $total_followup_for_meeting_count,
        'followup_for_payment' => $total_followup_for_payment_count,
        'proposal' => $total_business_proposal_count,
        'connected_call' => $no_of_call_connected_count,
        'mature' => $total_mature_client_count,
        'dead' => $total_dead_client_count,
        'amount' => $total_amount_sum,
        'accuracy'=>$forcast_accuracy_percent,
        'target_match'=> $target_matched,
    ];

    return response()->json(['status' => 200, 'data' => $total_data_array]);
}



public function get_followup_status_details(Request $request)
{
    $total_count_of_no = 0;
    $sum_of_amount = 0;

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

    } elseif($request->manager && $request->employee) {
        $emp_managers = $request->employee;
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


    $data = DB::connection('sales_db')->table('clients_followup_log')
        ->select('product_id', 'service_id', 'followup_id', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as total_count'))
        ->groupBy('product_id', 'service_id', 'followup_id')
        ->where('amount', '!=', '');

    if ($request->product) {
        $data->where('product_id', $request->product);
    }
    if ($request->service) {
        $data->where('service_id', $request->service);
    }
    if ($request->start_date && $request->end_date) {
        $sales_data = $data->whereBetween('created_date', [$request->start_date, $request->end_date]);
    } else {
        $sales_data = $data->whereMonth('created_date', Carbon::now()->month);
    }

    if (!empty($emp_managers)) {
        $query = $sales_data->whereIn('created_by', $emp_managers)->get();

        $data_array = $query->map(function ($row) use ($emp_managers) {
            $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
            $service = DB::connection('sales_db')->table('product_service')->where('id', $row->service_id)->first();
            $status_name = DB::connection('sales_db')->table('followup_status')->where('id', $row->followup_id)->first();

            return [
                'product_id' => $row->product_id,
                'product' => $product->product_name,
                'service' => $service->service_name,
                'status' => $status_name->activity_name,
                'amount' => $row->total_amount,
                'total_no' => $row->total_count
            ];
        });

        return response()->json(['status' => 200, 'data' => $data_array]);
    } else {
        $query = $sales_data->whereIn('created_by', [$request->emp_id])->get();

        $data_array = $query->map(function ($row) {
            $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
            $service = DB::connection('sales_db')->table('product_service')->where('id', $row->service_id)->first();
            $status_name = DB::connection('sales_db')->table('followup_status')->where('id', $row->followup_id)->first();

            return [
                'product_id' => $row->product_id,
                'product' => $product->product_name,
                'service' => $service->service_name,
                'status' => $status_name->activity_name,
                'amount' => $row->total_amount,
                'total_no' => $row->total_count
            ];
        });

        return response()->json(['status' => 200, 'data' => $data_array]);
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
    //$data_list = [];
    $total_renewal = 0;
    $total_renewal_amount = 0;
    $collection = 0;
    $new_sale_amount = 0;
    $renew_sale_amount = 0;
    $package_pending_activation_count = 0;
    $sum_of_due_amount = 0;
    $followup_count = 0;
    $total_mature_client  = 0;
    $total_dead = 0;
    $regular_client_dead = 0;
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

     //return $emp_managers;

    $total_upcoming_renewal  = DB::connection('sales_db')->table('package_info');
    $collection_of_amount = DB::connection('sales_db')->table('payment_history');
    $new_sale = DB::connection('sales_db')->table('payment_history');
    $renew_sale = DB::connection('sales_db')->table('payment_history');
    $package_pending_activation = DB::connection('sales_db')->table('package_info');
    $due_amount = DB::connection('sales_db')->table('payment_history');
    $total_followup = DB::connection('sales_db')->table('clients_followup_log');
    $forcast_accuracy = DB::connection('sales_db')->table('clients_followup_log');
    $renewal_dead = DB::connection('sales_db')->table('package_info');
    $count_regular_client =  DB::connection('sales_db')->table('package_info');
    $today_collection = DB::connection('sales_db')->table('payment_history');
    $today_followup = DB::connection('sales_db')->table('clients_followup_log');
    if($request->group){
        //return $request->group;
        $get_group_id = DB::connection('sales_db')->table('company_info')->where('group_id',$request->group)->pluck('client_id');
       // return  $get_group_id;
        
            //return 'b';
                $total_upcoming_renewal->whereIn('client_id',$get_group_id);
                $collection_of_amount->whereIn('client_id',$get_group_id);
                $new_sale->whereIn('client_id',$get_group_id);
                $renew_sale->whereIn('client_id',$get_group_id);
                $package_pending_activation->whereIn('client_id',$get_group_id);
                $today_collection->whereIn('client_id',$get_group_id);
                $today_followup->whereIn('client_id',$get_group_id);

    }
    //return  $total_upcoming_renewal->tosql();
    
    if($request->product){
        $total_upcoming_renewal->where('product_id',$request->product);
        $collection_of_amount->where('product_id',$request->product);
        $new_sale->where('product_id',$request->product);
        $renew_sale->where('product_id',$request->product);
        $package_pending_activation->where('product_id',$request->product);
        $due_amount->where('product_id',$request->product);
        $total_followup->where('product_id',$request->product);
        $forcast_accuracy->where('product_id',$request->product);
        $renewal_dead->where('product_id',$request->product);
        $count_regular_client->where('product_id',$request->product);
        $today_collection->where('product_id',$request->product);
        $today_followup->where('product_id',$request->product);


    }
    if($request->category){
        $total_upcoming_renewal->where('service_id',$request->category);
        $collection_of_amount->where('service_id',$request->category);
        $new_sale->where('service_id',$request->category);
        $renew_sale->where('service_id',$request->category);
        $package_pending_activation->where('service_id',$request->category);
        $due_amount->where('service_id',$request->category);
        $total_followup->where('service_id',$request->category);
        $forcast_accuracy->where('service_id',$request->category);
        $renewal_dead->where('service_id',$request->category);
        $count_regular_client->where('service_id',$request->category);
        $today_collection->where('service_id',$request->category);
        $today_collection->where('service_id',$request->category);
        
        
    }
    if($request->start_date && $request->end_date){
        $renewal_data_list = $total_upcoming_renewal->whereBetween('package_end_date', [$request->start_date, $request->end_date]);
        $total_collection_data = $collection_of_amount->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $new_sale_data =  $new_sale->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $renew_sale_data =  $renew_sale->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $package_pending =  $package_pending_activation->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $due_amount_data =  $due_amount->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $total_followup = $total_followup->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $total_forcast_accuracy = $forcast_accuracy->whereBetween('created_date', [$request->start_date, $request->end_date]);
        $total_renewal_dead =   $renewal_dead->whereBetween('package_end_date', [$request->start_date, $request->end_date]);
        //$regular_client_details = $count_regular_client->whereBetween('created_date', [$request->start_date, $request->end_date]);

    }
    if(!$request->start_date && !$request->end_date){
        $renewal_data_list = $total_upcoming_renewal->whereMonth('package_end_date', Carbon::now()->month);
        $total_collection_data = $collection_of_amount->whereMonth('created_date', Carbon::now()->month);
        $new_sale_data =  $new_sale->whereMonth('created_date', Carbon::now()->month);
        $renew_sale_data =  $renew_sale->whereMonth('created_date', Carbon::now()->month);
        $package_pending =  $package_pending_activation->whereMonth('created_date', Carbon::now()->month);
        $due_amount_data =  $due_amount->whereMonth('created_date', Carbon::now()->month);
        $total_followup = $total_followup->whereMonth('created_date', Carbon::now()->month);
        $total_forcast_accuracy = $forcast_accuracy->whereMonth('created_date', Carbon::now()->month);
        $total_renewal_dead = $renewal_dead->whereMonth('package_end_date', Carbon::now()->month);
        //$regular_client_details = $count_regular_client->whereMonth('created_date', Carbon::now()->month);



    }

    if (!empty($emp_managers)) {
       // return 'kk';
        $renewal_data  =  $renewal_data_list
                           ->whereIN('created_by',$emp_managers)
                           ->count();
        $renewal_amount  =  $renewal_data_list
                           ->whereIN('created_by',$emp_managers)
                           ->sum('paid_amount');
        $total_collection  = $total_collection_data
                           ->whereIN('created_by',$emp_managers)
                           ->sum('paid_amount');
        $total_new_sale = $new_sale_data->whereIN('created_by',$emp_managers)->where('payment_for','New')
                         ->sum('paid_amount');

        $total_renew_sale = $renew_sale_data->whereIN('created_by',$emp_managers)->where('payment_for','Renew')
        ->sum('paid_amount');

$total_active_client_ids = $count_regular_client
    ->whereIn('created_by', $emp_managers)
    ->pluck('client_id')
    ->unique();
//return $total_active_client_ids;

$clientsWithPackages = DB::connection('sales_db')
    ->table('package_info')
    ->whereIn('client_id', $total_active_client_ids)
    ->select('client_id', DB::raw('COUNT(*) as package_count'))
    ->groupBy('client_id')
    ->having('package_count', '>=',1)
    ->count();

$client_id_array = DB::connection('sales_db')
    ->table('package_info')
    ->whereIn('client_id', $total_active_client_ids)
    ->select('client_id', DB::raw('COUNT(*) as package_count'))
    ->groupBy('client_id')
    ->having('package_count', '>=',1)
    ->pluck('client_id');
foreach ($client_id_array as $check_count) {
    // Check if the client has bought a package in the current month
    $package_buy_or_not = DB::connection('sales_db')
        ->table('package_info')
        ->where('client_id', $check_count)
        ->whereMonth('created_date', Carbon::now()->month)
        ->exists();

    if (!$package_buy_or_not) {
        $regular_client_dead++;
    }
}
  $today_collection_sum = $today_collection->whereIn('created_by',$emp_managers)->whereDate('created_date',Carbon::now()
                         ->format('Y-m-d'))->sum('paid_amount');
  $today_followup_sum = $today_followup->whereIn('created_by',$emp_managers)->whereDate('created_date',Carbon::now()
  ->format('Y-m-d'))->whereIn('followup_id',[6,9])->count();

  //return $today_followup_sum;


$package_activation = $package_pending->whereIn('created_by', $emp_managers)
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
        $total_no_of_followup =  $total_followup->whereIn('created_by', $emp_managers)->where('disposition','connected')->count();
        $total_due_amount = $due_amount_data->whereIn('created_by', $emp_managers)
                        ->where('payment_for','Due Amount')->sum('paid_amount');
        $forcast_accuracy_data =  $total_forcast_accuracy->whereIn('created_by', $emp_managers)->where('followup_id',19)->count();

        $renewal_dead_data  = $total_renewal_dead->whereIN('created_by',$emp_managers)->distinct('client_id')->pluck('client_id');
        $check_today_missing_target = DB::connection('sales_db')
                                     ->table('per_day_achieved_followup')->whereIn('created_by',$emp_managers)->whereDate('created_date',Carbon::now()->format('Y-m-d'))->sum('remaining_followup');
        //return  $check_today_missing_target;
        $check_extra_followup = DB::connection('sales_db')
                                     ->table('per_day_achieved_followup')->whereIn('created_by',$emp_managers)->whereDate('created_date',Carbon::now()->format('Y-m-d'))->sum('extra_followup');
        
        $total_renewal += $renewal_data;
        $total_renewal_amount += $renewal_amount;
        $collection +=$total_collection;
        $new_sale_amount +=$total_new_sale;
        $renew_sale_amount +=$total_renew_sale;
        $package_pending_activation_count +=$package_activation;
        $sum_of_due_amount += $total_due_amount;
        $followup_count +=$total_no_of_followup;
        $total_mature_client +=$forcast_accuracy_data;
        if($forcast_accuracy_data>0){
            $forcast_accuracy_percent = $total_mature_client/$followup_count*100;

        }
        else{
            $forcast_accuracy_percent = 0;

        }

        $data_list = array('renew' => $total_renewal,'renewal_amount'=>$total_renewal_amount,
                    'collection'=>$collection,'new_sale'=>$new_sale_amount,'renew_sale'=> $renew_sale_amount,
                    'package_activation'=>$package_pending_activation_count,'due_amount'=>$sum_of_due_amount,'no_of_followup'=>$followup_count,
                    'accuracy_percent'=>$forcast_accuracy_percent,'missing_target'=>$check_today_missing_target,
                    'extra_followup'=>$check_extra_followup,'active_client'=>$clientsWithPackages,
                    'regular_client_dead'=>$regular_client_dead,
                    'today_collection'=>$today_collection_sum,'followup_sum'=>$today_followup_sum);
    } else {
       //return 'kkk';
        $renewal_data  =  $renewal_data_list
                           ->where('created_by',$request->emp_id)
                           ->count();
        $renewal_amount  =  $renewal_data_list
                           ->where('created_by',$request->emp_id)
                           ->sum('paid_amount');
        $total_collection  = $total_collection_data
                           ->where('created_by',$request->emp_id)
                           ->sum('paid_amount');
        $total_new_sale = $new_sale_data->where('created_by',$request->emp_id)->where('payment_for','New')
                         ->sum('paid_amount');
        $total_renew_sale = $renew_sale_data->where('created_by',$request->emp_id)->where('payment_for','Renew')
                         ->sum('paid_amount');

        $total_due_amount = $due_amount_data->where('created_by', $request->emp_id)
                         ->where('payment_for','Due Amount')->sum('paid_amount');

         $check_today_missing_target = DB::connection('sales_db')
                                     ->table('per_day_achieved_followup')->where('created_by',$request->emp_id)->whereDate('created_date',Carbon::now()->format('Y-m-d'))->sum('remaining_followup');
        $check_extra_followup = DB::connection('sales_db')
                                     ->table('per_day_achieved_followup')->where('created_by',$request->emp_id)->whereDate('created_date',Carbon::now()->format('Y-m-d'))->sum('extra_followup');
        
        $package_activation = $package_pending->where('created_by', $request->emp_id)
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
    $total_no_of_followup =  $total_followup->where('created_by', $request->emp_id)->where('disposition','connected')->count();
    $forcast_accuracy_data =  $total_forcast_accuracy->where('created_by', $request->emp_id)->where('followup_id',19)->count();

    $total_active_client_ids = $regular_client_details
    ->where('created_by', $request->emp_id)
    ->pluck('client_id')
    ->unique();

   $countClientsWithMoreThanFivePackages = DB::connection('sales_db')
    ->table('package_info')
    ->where('client_id', $total_active_client_ids)
    ->select('client_id', DB::raw('COUNT(*) as package_count'))
    ->groupBy('client_id')
    ->having('package_count', '>=',1)
    ->get()
    ->count();


        $total_renewal += $renewal_data;
        $total_renewal_amount += $renewal_amount;
        $collection +=$total_collection;
        $new_sale_amount +=$total_new_sale;
        $renew_sale_amount += $total_renew_sale;
        $package_pending_activation_count +=$package_activation;
        $sum_of_due_amount += $total_due_amount;
        $followup_count +=$total_no_of_followup;
        $total_mature_client +=$forcast_accuracy_data;
        if($forcast_accuracy_data>0){
            $forcast_accuracy_percent = $total_mature_client/$followup_count*100;

        }
        else{
            $forcast_accuracy_percent = 0;

        }

        $data_list = array('renew' => $total_renewal,'renewal_amount'=>$total_renewal_amount,
                    'collection'=>$collection,'new_sale'=>$new_sale_amount,'renew_sale'=> $renew_sale_amount,
                    'package_activation'=>$package_pending_activation_count,'due_amount'=>$sum_of_due_amount,'no_of_followup'=>$followup_count,
                    'accuracy_percent'=>$forcast_accuracy_percent,'missing_target'=>$check_today_missing_target,
                    'extra_followup'=>$check_extra_followup,'active_client'=>$countClientsWithMoreThanFivePackages);
   }
  return response()->json(['status'=>200,'data'=> $data_list,'message'=>'Sales Dashboard Data']);
}

public function sales_inner_page_description(Request $request)
{
    //return $request->all();
    $data_array = [];
    $sum = 0;
    $new_sale = 0;
    $renew_sale = 0;
    $emp_managers = [];

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
        $emp_managers = $request->employee;
        //return  $emp_managers;
    } 
    elseif($request->manager && $request->employee) {
        $emp_managers = $request->employee;
        //return  $emp_managers;
    }

    else {
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

    if($request->type =='totalCollection'){
         $query = DB::connection('sales_db')->table('payment_history');

    }
    elseif($request->type =='DueAmount'){
         $query = DB::connection('sales_db')->table('payment_history')->where('payment_for','Due Amount');

    }
    elseif($request->type == 'NewRenew'){
     $query = DB::connection('sales_db')
             ->table('payment_history')
             ->where(function($query) {
             $query->where('payment_for', 'New')
              ->orWhere('payment_for', 'ReNew');
    });


    }

    if ($request->product) {
        $query->where('product_id', $request->product);
    }

    if ($request->category) {
        $query->where('service_id', $request->category);
    }
    if($request->group){
        //return $request->group;
        $get_group_id = DB::connection('sales_db')->table('company_info')->where('group_id',$request->group)->get();
       // return  $get_group_id;
        
        if(count($get_group_id)==0){
           // return 'jjj';
            $query->where('client_id','');
        } else {
            foreach($get_group_id as $ids){
                $query->where('client_id',$ids->client_id);
            }
        }
    }

    if ($request->start_date && $request->end_date) {
        $query->whereBetween('created_date', [$request->start_date, $request->end_date]);
    } else {
        $query->whereMonth('created_date', Carbon::now()->month);
    }

    if (!empty($emp_managers)) {
        $total_collection_sum = $query->whereIn('created_by', $emp_managers)->sum('paid_amount');
        $new = DB::connection('sales_db')->table('payment_history')->where('payment_for','New')->whereIn('created_by', $emp_managers)->sum('paid_amount');
        $renew =  DB::connection('sales_db')->table('payment_history')->where('payment_for','ReNew')->whereIn('created_by', $emp_managers)->sum('paid_amount');
        $sum += $total_collection_sum;
        $new_sale+= $new;
        $renew_sale+=$renew;
        $total_collection_data = $query->whereIn('created_by', $emp_managers)->get();
    } else {
        $total_collection_sum = $query->where('created_by', $request->emp_id)->sum('paid_amount');
        $new = DB::connection('sales_db')->table('payment_history')->where('payment_for','New')->where('created_by', $emp_managers)->sum('paid_amount');
        $renew =  DB::connection('sales_db')->table('payment_history')->where('payment_for','ReNew')->where('created_by', $emp_managers)->sum('paid_amount');
        $sum += $total_collection_sum;
        $new_sale+= $new;
        $renew_sale+=$renew;
        $total_collection_data = $query->where('created_by', $request->emp_id)->get();
    }

    foreach ($total_collection_data as $row) {
        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
        $service = DB::connection('sales_db')->table('product_service')->where('id', $row->service_id)->first();
        $emp_name = BasicInfo::where('emp_id', $row->created_by)->first();
        $manager = BasicInfo::where('emp_id', $emp_name->reporting_manager)->first();
        $group = DB::connection('sales_db')->table('company_info')->where('client_id',$row->client_id)->where('comp_id',$row->comp_id)->first();
        if($group){
            $get_group_name = DB::connection('sales_db')->table('group_names')->where('group_id',$group->group_id)->first();
            $get_group_name =  $get_group_name->name;


        }
        else{
            $get_group_name = '';

        }

        $data_array[] = [
            'id' => $row->pay_id,
            'amount' => $row->paid_amount,
            'product' => $product->product_name,
            'service' => $service->service_name,
            'emp_name' => $emp_name->emp_fname . ' ' . $emp_name->emp_lame,
            'manager' => $manager->emp_fname . ' ' . $manager->emp_lame,
            'payment_for' => $row->payment_for,
            'date' => $row->created_date,
            'group'=> $get_group_name,
        ];
    }
     if ($request->download) {
        //return 'jjj';
            $filename = "sales_data_" . Carbon::now()->format('Ymd_His') . ".csv";
            $handle = fopen($filename, 'w+');
            fputcsv($handle, ['ID', 'Amount', 'Product', 'Service', 'Employee Name', 'Manager', 'Payment For', 'Date','Group']);

            foreach ($data_array as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);

            return response()->download($filename)->deleteFileAfterSend(true);
        }

    return response()->json(['status' => 200, 'data' => $data_array,'sum'=>$sum,'new_amount'=>$new_sale,
            'renew_amount'=>$renew_sale,]);
}


public function sales_upcoming_renewal_details(Request $request){
    //RETURN $request->all();

    $data_array = [];
    $sum = 0;
    $new_sale = 0;
    $renew_sale = 0;
    $emp_managers = [];
    $amount = 0;
    $pending_renewal_count = 0;

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
        $emp_managers = $request->employee;
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
    //return $emp_managers;

    if($request->type =='upcomingRenewal'){
         $query = DB::connection('sales_db')->table('package_info');

    };
    if($request->type =='PackagePendingActivation'){
     $query = DB::connection('sales_db')
             ->table('package_info')
             ->where(function($query) {
             $query->where('finance_status',0)
              ->orWhere('admin_status',0);
    });

    }

   if ($request->product) {
        $query->where('product_id', $request->product);
    }

    if ($request->category) {
        $query->where('service_id', $request->category);
    }
    if($request->group){
        $get_group_id = DB::connection('sales_db')->table('company_info')->where('group_id',$request->group)->get();
        if(count($get_group_id)==0){
            $query->where('client_id','');
        } else {
            foreach($get_group_id as $ids){
                $query->where('client_id',$ids->client_id);
            }
        }
    }
    if($request->type =='upcomingRenewal'){
         if ($request->start_date && $request->end_date) {
        $query->whereBetween('package_end_date', [$request->start_date, $request->end_date]);
    } else {
        $query->whereMonth('package_end_date', Carbon::now()->month);
    }
   }

   if($request->type =='PackagePendingActivation'){
         if ($request->start_date && $request->end_date) {
        $query->whereBetween('created_date', [$request->start_date, $request->end_date]);
    } else {
        $query->whereMonth('created_date', Carbon::now()->month);
    }
   }
    if (!empty($emp_managers)) {
        $total_collection_sum = $query->whereIn('created_by', $emp_managers)->count();
        $total_amount = $query->whereIn('created_by', $emp_managers)->sum('paid_amount');
        $sum += $total_collection_sum;
        $amount+=$total_amount;
        $total_collection_data = $query->whereIn('created_by', $emp_managers)->get();
        $renewal_pending = DB::connection('sales_db')->table('package_info')->whereIn('created_by', $emp_managers)->where('package_end_date','!=','')->get();
        foreach($renewal_pending as $total_count){
        $check_next_renewal =  DB::connection('sales_db')->table('package_info')->whereIn('created_by', $emp_managers)
        ->where('client_id',$total_count->client_id)->where('created_date','>',$total_count->package_end_date)->exists();
        if(!$check_next_renewal && Carbon::now() >$total_count->package_end_date ){
            $pending_renewal_count++;
        }
          }

    } else {
        $total_collection_sum = $query->where('created_by', $request->emp_id)->sum('paid_amount');
        $total_amount = $query->whereIn('created_by', $emp_managers)->sum('paid_amount');
        $sum += $total_collection_sum;
        $amount+=$total_amount;
        $total_collection_data = $query->where('created_by', $request->emp_id)->get();
        $renewal_pending = DB::connection('sales_db')->table('package_info')->where('created_by', $request->emp_id)->where('package_end_date','!=','')->get();
        foreach($renewal_pending as $total_count){
        $check_next_renewal =  DB::connection('sales_db')->table('package_info')->where('created_by', $request->emp_id)
        ->where('client_id',$total_count->client_id)->where('created_date','>',$total_count->package_end_date)->exists();
        if(!$check_next_renewal && Carbon::now() >$total_count->package_end_date ){
            $pending_renewal_count++;
        }
          }
    }

     //return $total_collection_data;

       foreach ($total_collection_data as $row) {
        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
        $service = DB::connection('sales_db')->table('product_service')->where('id', $row->service_id)->first();
        $emp_name = BasicInfo::where('emp_id', $row->created_by)->first();
        $manager = BasicInfo::where('emp_id', $emp_name->reporting_manager)->first();
        $group = DB::connection('sales_db')->table('company_info')->where('client_id',$row->client_id)->where('comp_id',$row->comp_id)->first();
        if($group){
            $get_group_name = DB::connection('sales_db')->table('group_names')->where('group_id',$group->group_id)->first();
            $get_group_name =  $get_group_name->name;


        }
        else{
            $get_group_name = '';

        }
        //if()

        $data_array[] = [
            'id' => $row->package_id,
            'amount' => $row->paid_amount,
            'product' => $product->product_name,
            'service' => $service->service_name,
            'emp_name' => $emp_name->emp_fname . ' ' . $emp_name->emp_lame,
            'manager' => $manager->emp_fname . ' ' . $manager->emp_lame,
            'package_name' => $row->package_name,
            'date' => $row->package_end_date,
            'created_date'=>$row->created_date,
            'group'=>$get_group_name,
            'type'=>$request->type,

        ];
    }
     if ($request->download) {
        //return 'jjj';
            $filename = "sales_data_" . Carbon::now()->format('Ymd_His') . ".csv";
            $handle = fopen($filename, 'w+');
            fputcsv($handle, ['ID', 'Amount', 'Product', 'Service', 'Employee Name', 'Manager', 'Package Name', 'Date','Group']);

            foreach ($data_array as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);

            return response()->download($filename)->deleteFileAfterSend(true);
        }
    

      return response()->json(['status' => 200, 'data' => $data_array,'sum'=>$sum,'amount'=>$amount, 'pending_renewal'=>$pending_renewal_count]);
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

public function count_sales_followup(){
    $data = DB::connection('sales_db')
    ->table('clients_followup_log')
    ->select('followup_id', 'created_by')
    ->where('followup_id', 6)
    ->whereDate('created_date', Carbon::now()->format('Y-m-d'))
    ->groupBy('followup_id', 'created_by')
    ->get();
    
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
    $assign_target = DB::table('save_company_target')->where('subattribute_id',13)
    ->where('department_id',3)->where('financial_year',$financialYear)->first();
    if($assign_target){
        $per_month_followup = $assign_target->child_attribute_value/12;
        $per_day_followup = round($per_month_followup/22);
    }

foreach($data as $row){
    $acheived_target = DB::connection('sales_db')->table('clients_followup_log')->where('followup_id',$row->followup_id)
    ->where('created_by',$row->created_by)->whereDate('created_date', Carbon::now()->format('Y-m-d'))->count();

    $last_inserted_record = DB::connection('sales_db')
    ->table('per_day_achieved_followup')
    ->where('created_by', $row->created_by)
    ->whereDate('created_date', '!=', Carbon::now()->format('Y-m-d'))
    ->orderBy('id', 'desc')
    ->first();



    if($last_inserted_record){
        $target_followup =  $per_day_followup + $last_inserted_record->remaining_followup;

    }
    else{
        $target_followup =  $per_day_followup;
       
    }
    if($acheived_target> $target_followup){
        $acheived_target -   $target_followup;
        $remaining_followup = 0;
        $extra_followup =  $acheived_target -   $target_followup;

    }
    else if($acheived_target< $target_followup ){
          $target_followup -  $acheived_target;
          $remaining_followup =   $target_followup -  $acheived_target;
          //return $target_followup;
          $extra_followup = 0;

    }
    else{
        $remaining_followup = 0;
        $extra_followup = 0;

    }

    DB::connection('sales_db')->table('per_day_achieved_followup')
    ->updateOrInsert(
        ['created_by' => $row->created_by,'created_date'=>Carbon::now()->format('Y-m-d')],
        [
            'assigned_followup'=>$target_followup,
            'total_achieved_followup'=>$acheived_target,
            'remaining_followup'=>$remaining_followup,
            'extra_followup'=>$extra_followup,
            'created_by'=>$row->created_by,

     ]
    );
}

}

public function get_total_collection(Request $request) {
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
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
                      ->orWhere('dept_manager', $request->emp_id);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

    $query = DB::connection('sales_db')->table('payment_history');

    if ($request->group) {
        $group_ids = DB::connection('sales_db')->table('company_info')
            ->where('group_id', $request->group)
            ->pluck('client_id', 'comp_id')->toArray();

        if (count($group_ids) == 0) {
            $query->where('client_id', '')->where('comp_id', '');
        } else {
            $query->where(function($query) use ($group_ids) {
                foreach ($group_ids as $client_id => $comp_id) {
                    $query->orWhere(function($query) use ($client_id, $comp_id) {
                        $query->where('client_id', $client_id)
                              ->where('comp_id', $comp_id);
                    });
                }
            });
        }
    }

    if ($request->product) {
        $query->where('product_id', $request->product);
    }

    if ($request->category) {
        $query->where('service_id', $request->category);  
    }

    if ($request->start_date && $request->end_date) {
        $query->whereBetween('created_date', [$request->start_date, $request->end_date]);
    } else {
        $query->whereYear('created_date', Carbon::now()->year);
    }

    if (!empty($emp_managers)) {
        $query->whereIn('created_by', $emp_managers);
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
                $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
                      ->orWhere('dept_manager', $request->emp_id);
            })
            ->where('status', 1)
            ->where('dept_id', 3)
            ->pluck('emp_id')->toArray();

        $emp_managers[] = $request->emp_id;
        $emp_managers = array_unique($emp_managers);
    }

   $query =   $query = DB::connection('sales_db')->table('package_info');
   //return $emp_managers; 


   if ($request->group) {
        $group_ids = DB::connection('sales_db')->table('company_info')
            ->where('group_id', $request->group)
            ->pluck('client_id', 'comp_id')->toArray();

        if (count($group_ids) == 0) {
            $query->where('client_id', '')->where('comp_id', '');
        } else {
            $query->where(function($query) use ($group_ids) {
                foreach ($group_ids as $client_id => $comp_id) {
                    $query->orWhere(function($query) use ($client_id, $comp_id) {
                        $query->where('client_id', $client_id)
                              ->where('comp_id', $comp_id);
                    });
                }
            });
        }
    }

    if ($request->product) {
        $query->where('product_id', $request->product);
    }

    if ($request->category) {
        $query->where('service_id', $request->category);
    }

    if (!empty($emp_managers)) {
        $data = $query->whereIn('created_by',$emp_managers)->pluck('client_id')
         ->unique();

         if($request->type =='regularClient'){
            $clientsWithPackages = DB::connection('sales_db')
              ->table('package_info')
             ->whereIn('client_id',$data)
             ->select('client_id', DB::raw('COUNT(*) as package_count'))
             ->groupBy('client_id')
             ->having('package_count', '>=',1)
              ->pluck('client_id');

         }
         elseif($request->type == 'regularClientDead'){
            $clientsWithPackages = [];
            $client_ids = DB::connection('sales_db')
            ->table('package_info')
            ->whereIn('client_id',$data)
            ->select('client_id', DB::raw('COUNT(*) as package_count'))
            ->groupBy('client_id')
            ->having('package_count', '>=',1)
            ->pluck('client_id');

            foreach ($client_ids as $check_count) {
             $package_buy_or_not = DB::connection('sales_db')
             ->table('package_info')
             ->where('client_id', $check_count)
             ->whereMonth('created_date', Carbon::now()->month)
            ->exists();
          if (!$package_buy_or_not) {
                $clientsWithPackages[] = $check_count;
             }
           }
         }

        foreach($clientsWithPackages as $row){

            $client_details = DB::connection('sales_db')->table('company_info')->where('client_id',$row)->first();

            $last_package_buy_record = DB::connection('sales_db')->table('package_info')->where('client_id',$row)->orderBy('package_id','DESC')->first();

            $product = DB::connection('sales_db')->table('product')->where('id',  $last_package_buy_record->product_id)->first();
           $service = DB::connection('sales_db')->table('product_service')->where('id',  $last_package_buy_record->service_id)->first();
           $emp_name = BasicInfo::where('emp_id', $last_package_buy_record->created_by)->first();
           $manager = BasicInfo::where('emp_id', $emp_name->reporting_manager)->first();
           if($client_details && $client_details->group_id!=' '){
             $get_group_name = DB::connection('sales_db')->table('group_names')->where('group_id',$client_details->group_id)->first();
             $group_name = $get_group_name->name;

           }
           else{
            $group_name = ' ';

           }
           if($client_details){
             $client_name = $client_details->client_name;
             $business_name = $client_details->business_name;
           }
           else{
             $client_name = '';
             $business_name = '';


           }

           $client_data[] = array('name'=> $client_name,'business_name'=>$business_name,'product'=>$product->product_name,'service'=>$service->service_name,'emp'=>$emp_name->emp_fname.' '.$emp_name->emp_lame,'manager'=>$manager->emp_fname.' '.$manager->emp_lame,'group'=>$group_name,'last_package_buy_date'=> $last_package_buy_record->created_date);
         }


         return response()->json(['status'=>200,'data'=>$client_data]);
       

       }
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
        $get_emp_info = BasicInfo::where('emp_id',$request->emp_id)->first();
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
                    $query->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->emp_id])
                          ->orWhere('dept_manager', $request->emp_id);
                })
                ->where('status', 1)
                ->where('dept_id', 3)
                ->pluck('emp_id')->toArray();
                if($get_emp_info->dept_id ==3 && $get_emp_info->desi_id!=38){
                    $emp_managers[] = $emp_id;
                 }
            
            $emp_managers = array_unique($emp_managers);
        }
        //return $emp_managers;
    
        $query = DB::connection('sales_db')->table('package_info');
    
        if ($request->group) {
            $get_group_id = DB::connection('sales_db')->table('company_info')->where('group_id', $request->group)->pluck('client_id');
            $query->whereIn('client_id', $get_group_id);
        }
    
        if ($request->product) {
            $query->where('product_id', $request->product);
        }
    
        if ($request->category) {
            $query->where('category_id', $request->category);
        }
    
        if ($request->start_date && $request->end_date) {
            $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
            $end_date = Carbon::parse($request->end_date)->format('Y-m-d');
        } else {
            $start_date = Carbon::now()->firstOfMonth()->format('Y-m-d');
            $end_date = Carbon::now()->endOfMonth()->format('Y-m-d');
        }
    
        $dates = [];
    
        for ($date = Carbon::parse($start_date); $date->lte(Carbon::now()) && $date->lte($end_date); $date->addDay()) {
            if (!$date->isWeekend()) {
                $dates[] = $date->format('Y-m-d');
            }
        }
    
        $missingDatesByManager = [];
        $allExistingDates = [];
    
        foreach ($emp_managers as $manager_id) {
            $existingDates = DB::connection('sales_db')
                ->table('package_info')
                ->where('created_by', $manager_id)
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
                    'created_by' => $emp_name->emp_fname,
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
        $get_emp_details = BasicInfo::where('emp_id', $emp_id)->pluck('assigned_group')->first();
        $group_ids = explode(',', $get_emp_details);
        $group_ids = array_map('trim', $group_ids);
    
        $get_data = DB::connection('sales_db')
            ->table('guest_user_details')
            ->where('status', 0)
            ->whereIn('reg_type', [2, 3, 4])
            ->whereIn('group_id', $group_ids)
            ->get();
        foreach($get_data as $row){
            $group = DB::connection('sales_db')->table('group_names')->where('group_id',$row->group_id)->first();
            $data_array[] = array('id'=>$row->id,'group'=>$group->name,'business'=>$row->business_name);
          }
        return response()->json(['status'=>200,'data'=>$data_array]);
    }

    // public function update_business_lead_data(Request $request){
    //     DB::connection('sales_db')->table('guest_user_details')->where('id'=>$request->id)->update(['status'=>1,'created_by'=>$request->emp_id]);
    //     $details = DB::connection('sales_db')->table('guest_user_details')->where('id',$request->id)->first();
    //     $data_array = array('client_type'=>1,'client_id'=>$request->id,'product_id'=>$details->product_id,'service_id'=>$details->service_id,'category'=>)
    //     }
    
}