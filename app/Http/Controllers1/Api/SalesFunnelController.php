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

    // Retrieve all employees reporting to the given employee
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

            // Get the latest record where task_id is not 19
            $lastRecord = DB::connection('sales_db')->table('sales_funnel')
                ->where('product_id', $for->product_id)
                ->where('package_id', $for->package_id)
                ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$row->emp_id])
                ->where('task_id', '!=', 19)
                ->orderBy('created_date', 'desc')
                ->first();

            if ($lastRecord) {
                $total += $lastRecord->amount;
            }

            // Get the latest record where task_id is 19
            $lastWonRecord = DB::connection('sales_db')->table('sales_funnel')
                ->where('product_id', $for->product_id)
                ->where('package_id', $for->package_id)
                ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$row->emp_id])
                ->where('task_id', 19)
                ->orderBy('created_date', 'desc')
                ->first();

            if ($lastWonRecord) {
                $won_amount += $lastWonRecord->amount;
            }

            $total += $won_amount;
            //return $total;

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
    
    // Retrieve the sales funnel details considering comma-separated reporting_manager
    $data = DB::connection('sales_db')->table('sales_funnel')
        ->select('reporting_manager', 'product_id', 'task_id')
        ->whereRaw('FIND_IN_SET(?, reporting_manager)', [$emp_id])
        ->where('product_id', $product_id)
        ->whereBetween('created_date', [$start, $end])
        ->get();

    // Grouped data storage
    $groupedData = [];

    foreach ($data as $row) {
        // Get the actual manager IDs
        $managers = explode(',', $row->reporting_manager);

        // Check if the current emp_id is part of the managers
        if (in_array($emp_id, $managers)) {
            // Aggregate data from the sales funnel
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

            // Set employee and product details (if not already set)
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

            // Accumulate values
            $groupedData[$key]['sum'] = $sum;
            $groupedData[$key]['amount'] = $amount;
            $groupedData[$key]['won']  = $won;
            $groupedData[$key]['loss'] = $loss;
        }
    }

    // Convert grouped data to list
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
      $details = DB::connection('sales_db')->table('sales_funnel')->where('created_by',$request->employee)->orWhereRaw('FIND_IN_SET(?, reporting_manager)', [$request->employee])
      ->where('product_id',$request->product_id)->select('created_by','product_id','task_id')->groupBy('created_by','product_id','task_id')->get();
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
        $details = DB::connection('sales_db')->table('sales_funnel')->where('created_by',$request->employee)
        ->where('product_id',$request->product_id)->select('created_by','product_id','task_id')->groupBy('created_by','product_id','task_id')->get();
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



}