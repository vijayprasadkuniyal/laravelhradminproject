<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\Vendor;
use App\Models\Category;
use Validator;
use App\Models\BasicInfo;
use App\Models\AssignStock;
Use \Carbon\Carbon;
use DB;
use App\Models\RaiseAssetRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StockImport;
use App\Exports\ExportStock;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;
use PDF;
use App\Exports\StockDetails;
use App\Exports\BranchDetails;
use App\Imports\ImportStock;

class StockController extends Controller

{
    public function add_stock(Request $request){
        $input = $request->all();
            $validator = Validator::make($input, [
                'category' => 'required',
                'brand_name'=>'required',
                'price'=>'required',
                'quantity'=>'required',
                'product_type'=>'required',
                'branch'=>'required',
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
            $stock = new Stock();
            $stock->category_id = $request->category;
            $stock->brand_name = $request->brand_name;
            $stock->product_id = $request->product_id;
            $stock->price = $request->price;
            $stock->vendor_id = $request->vendor;
            $stock->product_type = $request->product_type;
            $stock->device_id = $request->device_id;
            $stock->created_by = $request->emp_id;
            $stock->quantity = $request->quantity;
            $stock->remaining_quantity = $request->quantity;
            $stock->branch_id = $request->branch;
            $stock->save();
            if($stock){
                return response()->json(['status'=>200,'message'=>'stock Created Successfully']);
            }
            else{
                return response()->json(['message'=>'Issue in vendor creation']);
             }
          }
       public function stock_list(Request $request){
        //$vendor_list = Stock::get(['id','category','mobile','email','address','status']);
        $stock_list = Stock::join('category_details','category_details.id','=','stock_details.category_id')
                     ->leftjoin('vendor_details','vendor_details.id','=','stock_details.vendor_id')
                     ->select('category_details.category_name','vendor_details.name','stock_details.*',);
        if($request->category){
           $stock_list->where('category_id',$request->category);

        }
         $pagedResults =  $stock_list->paginate($request->per_page);
        return response()->json(['status'=>200,'message'=>'Stock Details','data'=>$pagedResults,'last_page' =>$pagedResults->lastPage()]);
       }
       public function stock_edit($id){
        $stock = Stock::findOrFail($id);
        return response()->json(['status'=>200,'message'=>'Vendor List','stock'=>$stock]);
      }
    public function stock_update(Request $request,$id){
        $input = $request->all();
            $validator = Validator::make($input, [
                'category' => 'required',
                'brand_name'=>'required',
                'price'=>'required',
                'product_type'=>'required',
                'quantity'=>'required',
                'branch'=>'required'
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
            $stock = Stock::findOrFail($id);
            $stock->category_id = $request->category;
            $stock->brand_name = $request->brand_name;
            $stock->product_id = $request->product_id;
            $stock->price = $request->price;
            $stock->vendor_id = $request->vendor;
            $stock->product_type = $request->product_type;
            $stock->device_id = $request->device_id;
            $stock->created_by = $request->emp_id;
            $stock->quantity = $request->quantity;
            $stock->branch_id = $request->branch;
            $stock->remaining_quantity = $request->quantity;
            $stock->save();
            if($stock){
                return response()->json(['status'=>200,'message'=>'stock Created Successfully']);
            }
            else{
                return response()->json(['message'=>'Issue in vendor creation']);
             }
          }

    public function stock_status($id){
        $stock = Stock::findOrFail($id);
       if($stock){
        return response()->json(['status' => 200, 'message' => 'stock status','data'=>$stock->status]);
      }
     else{
        return response()->json(['status' =>500, 'message' => 'data not found']);
      }

    }
    public function update_stock_status(Request $request){
        $request->validate([
            'stock_id' => 'required',
            'status' => 'required|in:0,1', 
        ]);
    
        $stock = Stock::findOrFail($request->stock_id);
        $stock->status = $request->status;
        $stock->save();
        if($stock){
            return response()->json(['status' => 200, 'message' => 'stock status updated successfully']);
    
        }
        else{
            return response()->json(['status' => 500, 'message' => 'something went wrong']);
    
        }

    }
  public function active_vendor(){
        $vendor_list = Vendor::where('status',1)->get(['id','name']);
        return response()->json(['status'=>200,'message'=>'vendor Details','data'=>$vendor_list]);

    }
    public function active_category(){
        $category_list = Category::where('status',1)->get(['id','category_name']);
        return response()->json(['status'=>200,'message'=>'Category Details','data'=>$category_list]);

    }
    //public function get_active_stock(){
        ////$stock_list = Stock::where('status',1)->get(['id','brand_name']);
        //return response()->json(['status'=>200,'message'=>'Stock Details','data'=>$stock_list]);
     //}
    public function category_based_stock($id,$branch){
        $stock_list = Stock::where('status',1)->where('category_id',$id)->where('branch_id',$branch)->where('remaining_quantity', '<>', 0)->get(['id','brand_name']);
        return response()->json(['status'=>200,'message'=>'Avalible Stock','data'=>$stock_list]);
     }
    public function department_employee($id){
        $data = BasicInfo::where('emp_status',1)->where('dept_id',$id)->get(['emp_id','emp_fname','emp_lame']);
        return response()->json(['status'=>200,'message'=>'Employee List','data'=>$data]);
    }
    public function assign_stock(Request $request){
        $input = $request->all();
        $validator = Validator::make($input, [
            'department' => 'required',
            'employee'=>'required',
            'category'=>'required',
            'stock'=>'required',
            'quantity'=>'required',
            'image'=>'required',
            'assign_date'=>'required',
        ]);
    
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
        $screenshort = '';
        $stock = new AssignStock();
        $stock->category_id = $request->category;
        $stock->department_id = $request->department;
        $stock->assign_to = $request->employee;
        $stock->stock_id = $request->stock;
        $stock->quantity = $request->quantity;
        $stock->created_by = $request->emp_id;
        $stock->assign_date = $request->assign_date;
        if($img = $request->file('image')) {
            //dd('hi');
                $destinationPath = 'term_and_condition/';
                $logoimage = date('YmdHis') . "." . $img->getClientOriginalExtension();
                $img->move($destinationPath, $logoimage);
                $screenshort = $logoimage;
                $stock->term_and_condition_image = $screenshort;
        }
        $stock_quantity = Stock::where('id',$request->stock)->first();
        if($stock_quantity->remaining_quantity == 0 || $stock_quantity->remaining_quantity<$request->quantity){

            return response()->json(["message"=>'Please Enter Another Quantity','status'=>201]); 
          } 

          else{

             $stock->save();
            Stock::where('id',$request->stock)->update(['remaining_quantity'=>$stock_quantity->remaining_quantity - $request->quantity]);
            $stock_details = array('assign_to'=>$request->employee,'stock_id'=>$request->stock,'quantity'=>$request->quantity,'created_by'=>$request->emp_id,
           'assign_date'=>$request->assign_date);
            DB::table('stock_history_details')->insert($stock_details);
            if($stock){
                return response()->json(['status'=>200,'message'=>'stock Assign Successfully']);
            }
            else{
                return response()->json(['message'=>'Issue in Assign Stock']);
             }

          }

        

    }
    public function assign_stock_list(Request $request){
        $assign_list = AssignStock::join('department','department.id','=','stock_assign.department_id')
                     ->join('category_details','category_details.id','=','stock_assign.category_id')
                     ->join('emp_basic_info','emp_basic_info.emp_id','=','stock_assign.assign_to')
                     ->join('stock_details','stock_details.id','=','stock_assign.stock_id')
                     ->select('category_details.category_name','emp_basic_info.emp_fname',
                     'department.department_name','stock_details.brand_name','stock_assign.id','stock_assign.quantity','stock_assign.assign_date','stock_assign.status')
                     ->paginate($request->per_page);
        return response()->json(['status'=>200,'message'=>'Assign Stock List','data'=>$assign_list,'last_page' => $assign_list->lastPage()]);

    }
public function edit_assign_stock($id){
    $stock = AssignStock::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'assign stock detail','data'=>$stock]);
}

public function update_assign_stock(Request $request,$id){
        $input = $request->all();
        $validator = Validator::make($input, [
            'department' => 'required',
            'employee'=>'required',
            'category'=>'required',
            //'stock'=>'required',
            'quantity'=>'required',
            'image'=>'required',
            'assign_date'=>'required',
        ]);
    
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
        $screenshort = '';
        $stock = AssignStock::findOrFail($id);
        $check_stock_quantity = Stock::where('id',$stock->stock_id)->first();
        if($request->stock){
          $selected_stock = $request->stock;
           Stock::where('id',$check_stock_quantity->id)->update(['remaining_quantity'=> $check_stock_quantity->remaining_quantity + $stock->quantity]);
             $stock_quantity = Stock::where('id',$request->stock)->first();
             Stock::where('id',$request->stock)->update(['remaining_quantity'=>$stock_quantity->remaining_quantity - $request->quantity]);

         }
        else{
          $selected_stock = $stock->stock_id;

        }
        $stock->category_id = $request->category;
        $stock->department_id = $request->department;
        $stock->assign_to = $request->employee;
        $stock->stock_id =  $selected_stock ;
        $stock->quantity = $request->quantity;
        $stock->created_by = $request->emp_id;
        $stock->assign_date = $request->assign_date;
        if($img = $request->file('image')) {
            //dd('hi');
                $destinationPath = 'term_and_condition/';
                $logoimage = date('YmdHis') . "." . $img->getClientOriginalExtension();
                $img->move($destinationPath, $logoimage);
                $screenshort = $logoimage;
                $stock->term_and_condition_image = $screenshort;
        }
        $stock_quantity = Stock::where('id',$request->stock)->first();
         if($stock_quantity->remaining_quantity == 0 || $stock_quantity->remaining_quantity<$request->quantity){

            return response()->json(["message"=>'Please Enter Another Quantity','status'=>200]); 
          } 

          else{
          $stock->save();
          AssignStock::where('id',$stock->id)->where('assign_to',$stock->assign_to)->update(['status'=>0,'return_status'=>0]);
          $stock_details = array('assign_to'=>$request->employee,'stock_id'=>$request->stock,'quantity'=>$request->quantity,'created_by'=>$request->emp_id,
         'assign_date'=>$request->assign_date);
          DB::table('stock_history_details')->insert($stock_details);
         if($stock){
            return response()->json(['status'=>200,'message'=>'stock Assign Updated Successfully']);
         }
         else{
            return response()->json(['message'=>'Issue in Update Stock']);
          }

           }

          }
    public function emp_stock_list($id){
        $assign_list = AssignStock::join('department','department.id','=','stock_assign.department_id')
                     ->join('category_details','category_details.id','=','stock_assign.category_id')
                     ->join('emp_basic_info','emp_basic_info.emp_id','=','stock_assign.assign_to')
                     ->join('stock_details','stock_details.id','=','stock_assign.stock_id')
                     ->select('category_details.category_name','emp_basic_info.emp_fname',
                     'department.department_name','stock_details.brand_name','stock_assign.id','stock_assign.quantity',
                     'stock_assign.assign_date','stock_assign.status','stock_assign.return_date','stock_assign.return_status',
                     'stock_assign.term_and_condition_image','stock_assign.stock_id','stock_assign.assign_to','stock_assign.admin_return_remark')
                     ->where('stock_assign.assign_to',$id)
                     ->get();
        return response()->json(['status'=>200,'message'=>'Employee Stock','data'=>$assign_list]);
        

    }
public function change_stock_status($id,$stock_id,$status,$reaject_reason,$emp_id,$employee_id){
    //return $status;
    $assign_stock = AssignStock::where('stock_id',$stock_id)->where('assign_to',$emp_id)->first();
    AssignStock::where('stock_id',$stock_id)->where('assign_to',$emp_id)->update(['status'=>$status,'reject_reason'=>$reaject_reason]);
    if($status ==1){
        $stock_status = 'Accept';
    }
    if($status ==2){
        $stock_status = 'Reject';
    }
    $stock_details = array('assign_to'=>$emp_id,'stock_id'=>$stock_id,'created_by'=>$employee_id,'status'=>$stock_status);
    DB::table('stock_history_details')->insert($stock_details);

    return response()->json(['status'=>200,'message'=>'Status Update Successfully']);
    }
public function return_stock($id,$return_reason,$emp_id,$employee_id){
        $assign_stock_data = AssignStock::where('id',$id)->first();
        AssignStock::where('id',$id)->where('assign_to',$emp_id)->update(['return_status'=>0,
        'return_request_date'=>Carbon::now()->format('Y-m-d'),'emp_return_remark'=>$return_reason,'for_return'=>1]);
        $stock_details = array('assign_to'=>$emp_id,'stock_id'=>$assign_stock_data->stock_id,'created_by'=>$employee_id,'return_status'=>'pending');
        DB::table('stock_history_details')->insert($stock_details);
    return response()->json(['status'=>200,'message'=>'Return Request Send Successfully']);


}
public function return_stock_list(Request $request){
    $list =  AssignStock::join('department','department.id','=','stock_assign.department_id')
                     ->join('category_details','category_details.id','=','stock_assign.category_id')
                     ->join('emp_basic_info','emp_basic_info.emp_id','=','stock_assign.assign_to')
                     ->join('stock_details','stock_details.id','=','stock_assign.stock_id')
                     ->select('category_details.category_name','emp_basic_info.emp_fname',
                     'department.department_name','stock_details.brand_name','stock_assign.id','stock_assign.quantity',
                     'stock_assign.assign_date','stock_assign.status','stock_assign.return_date','stock_assign.return_status',
                     'stock_assign.term_and_condition_image','stock_assign.stock_id','stock_assign.assign_to','stock_assign.emp_return_remark')
                     ->where('for_return',1)
                     ->orderBy('id','DESC')
                     ->paginate($request->per_page);
    return response()->json(['status'=>200,'message'=>'Return list','data'=>$list,'last_page' =>$list->lastPage()]);

}
public function return_stock_status($id,$emp_id,$stock_id,$date,$status,$remark,$employee_id){
    
    $assign_stock = AssignStock::where('stock_id',$stock_id)->where('assign_to',$emp_id)->first();
    AssignStock::where('id',$id)->where('assign_to',$emp_id)
    ->update(['return_status'=>$status,'return_date'=>$date,'admin_return_remark'=>$remark]);
    if($status ==1){
        $stock_details = Stock::where('id',$stock_id)->first();
        Stock::where('id',$stock_id)->update(['remaining_quantity'=>$stock_details->remaining_quantity +$assign_stock->quantity]);
     }
    if($status ==1){
        $return_status = 'Accept';
     }
    else{
        $return_status = 'Reject';
    }
    $stock_details = array('assign_to'=>$emp_id,'stock_id'=>$stock_id,'created_by'=>$employee_id,'return_status'=>$return_status,'return_date'=>$date);
    DB::table('stock_history_details')->insert($stock_details);
    return response()->json(['status'=>200,'message'=>'Stock Return Successfully',]);

}
public function admin_return_edit($id,$emp_id){
    $data = AssignStock::where('id',$id)->where('assign_to',$emp_id)->first(['admin_return_remark','return_date','return_status']);
    return response()->json(['status'=>200,'message'=>'Return Edit','data'=>$data]);

}
public function emp_return_edit($id,$emp_id){
    $data = AssignStock::where('id',$id)->where('assign_to',$emp_id)->first(['emp_return_remark']);
    return response()->json(['status'=>200,'message'=>'Return Edit','data'=>$data]);

}
public function emp_stock_reject($id,$emp_id){
    $data = AssignStock::where('id',$id)->where('assign_to',$emp_id)->first(['reject_reason']);
    return response()->json(['status'=>200,'message'=>'Reject Reason','data'=>$data]);
}
public function raise_asset_request(Request $request){
    $input = $request->all();
            $validator = Validator::make($input, [
                'category' => 'required',
                'brand_name'=>'required',
                'quantity'=>'required',
                'remark'=>'required',
                'branch'=>'required',
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
            $asset_request = new RaiseAssetRequest();
            $asset_request->category_id = $request->category;
            $asset_request->brand_name = $request->brand_name;
            $asset_request->quantity = $request->quantity;
            $asset_request->created_by = $request->emp_id;
            $asset_request->admin_remark = $request->remark;
            $asset_request->branch_id = $request->branch;
            $asset_request->save();
            if($asset_request){
                return response()->json(['status'=>200,'message'=>'Request Raise Successfully']);
            }
            else{
                return response()->json(['message'=>'Issue in Request Raise']);
             }



}
public function raise_asset_request_edit($id){
    $data = RaiseAssetRequest::findOrFail($id);
    return response()->json(['status'=>200,'message'=>'raise request','data'=>$data]);

}
public function update_asset_request(Request $request,$id){
    $input = $request->all();
            $validator = Validator::make($input, [
                'category' => 'required',
                'brand_name'=>'required',
                'quantity'=>'required',
                'remark'=>'required',
                'branch'=>'required',
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
            $asset_request = RaiseAssetRequest::findOrFail($id);
            $asset_request->category_id = $request->category;
            $asset_request->brand_name = $request->brand_name;
            $asset_request->quantity = $request->quantity;
            $asset_request->created_by = $request->emp_id;
            $asset_request->admin_remark = $request->remark;
            $asset_request->branch_id = $request->branch;
            $asset_request->save();
            RaiseAssetRequest::where('id',$id)->update(['status'=>0]);
            if($asset_request){
                return response()->json(['status'=>200,'message'=>'Request Updated Successfully']);
            }
            else{
                return response()->json(['message'=>'Issue in request updation']);
             }

}
public function raise_asset_request_list(){
    $data = RaiseAssetRequest::join('category_details','category_details.id','raise_asset_request.category_id')
        ->join('branch_details','branch_details.id','raise_asset_request.branch_id')
          ->select('category_details.category_name','raise_asset_request.id','raise_asset_request.brand_name','raise_asset_request.quantity',
          'raise_asset_request.status','raise_asset_request.admin_remark','raise_asset_request.finance_remark','branch_details.branch_name')
          ->get();
    return response()->json(['status'=>200,'message'=>'Request List','data'=> $data]);
    
}
public function change_asset_request_status(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'status' => 'required',
        'remark'=>'required',
    ]);

      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }

    RaiseAssetRequest::where('id',$request->raise_id)->update(['status'=>$request->status,'finance_remark'=>$request->remark,'action_by'=>$request->emp_id]);
    return response()->json(['status'=>200,'message'=>'Status Change Successfully']);


}
public function get_asset_request_status($id){
    $data = RaiseAssetRequest::findOrFail($id);
    if($data){
        return response()->json(['status'=>200,'message'=>'asset request status','data'=> $data]);

    }
    else{
        return response()->json(['status'=>200,'message'=>'Not Found','data'=> $data]);

    }

}
public function import_stock(Request $request){
    Excel::import(new StockImport, $request->file('file')->store('files'));
    return response()->json(['status'=>200,'message'=>'File Uploaded Successfully']);

}
public function export_excel(){
    return Excel::download(new ExportStock, 'stocks.xlsx');
}
public function count_stock($id){
    $data = Stock::where('id',$id)->first();
    if($data->remaining_quantity==0){
        return response()->json(['status'=>200,'message'=>'Stock Not Found Select Another']);

    }

}
public function asset_repair_list(){
    $list = [];
    $data = DB::table('asset_repair_details')->get();
    foreach($data as $row){
        $category = DB::table('category_details')
        ->where('id',$row->category_id)->first();
        $branch = DB::table('branch_details')->where('id',$row->branch_id)
        ->first();
        $stock = DB::table('stock_details')->where('id',$row->stock_id)->first();
        $list[] = array('id'=>$row->id,'category'=>$category->category_name,
        'stock'=>$stock->brand_name,'product_id'=>$row->product_id,
        'device_id'=>$row->device_id,'date'=>$row->date,'reason'=>$row->reason,
        'amount'=>$row->amount,'quantity'=>$row->quantity,'branch'=>$branch->branch_name);

    }
    return response()->json(['status'=>200,'data'=>$list,'message'=>'Asset Repair List']);

}
public function save_repair_asset(Request $request){
    $input = $request->all();
            $validator = Validator::make($input, [
                'category' => 'required',
                'stock'=>'required',
                'quantity'=>'required',
                'amount'=>'required',
                'date'=>'required',
                'message'=>'required',
                'branch'=>'required',

            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
    $data = array('category_id'=>$request->category,
    'stock_id'=>$request->stock,'product_id'=>$request->product,
    'device_id'=>$request->device,'quantity'=>$request->quantity,
    'amount'=>$request->amount,'date'=>$request->date,'reason'=>$request->message,
    'created_by'=>$request->emp_id,'branch_id'=>$request->branch);
    DB::table('asset_repair_details')->insert($data);
    return response()->json(['status'=>200,'data'=>$data,'message'=>'Save Successfully']);

}
public function edit_repair_asset($id){
    $data = DB::table('asset_repair_details')->where('id',$id)->first();
    return response()->json(['status'=>200,'data'=>$data]);

}
public function update_repair_asset(Request $request,$id){
    $input = $request->all();
            $validator = Validator::make($input, [
                'category' => 'required',
                'stock'=>'required',
                'quantity'=>'required',
                'amount'=>'required',
                'date'=>'required',
                'message'=>'required',
                'branch'=>'required',

            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
    $data = array('category_id'=>$request->category,
    'stock_id'=>$request->stock,'product_id'=>$request->product,
    'device_id'=>$request->device,'quantity'=>$request->quantity,
    'amount'=>$request->amount,'date'=>$request->date,'reason'=>$request->message,
    'created_by'=>$request->emp_id,'branch_id'=>$request->branch);
    DB::table('asset_repair_details')->where('id',$id)->update($data);
    return response()->json(['status'=>200,'data'=>$data,'message'=>'Update Successfully']);

}
public function get_asset_repair_report(Request $request){
    $start_date = $request->start_date;
    $end_date = $request->end_date;

    $data = DB::table('asset_repair_details')
        ->select('category_id', 'branch_id', 'stock_id', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(amount) as amount'))
        ->whereBetween('date', [$start_date, $end_date])
        ->groupBy('category_id', 'stock_id','branch_id')
        ->get();

    $reportData = [];
    
    foreach($data as $row){
        $category = DB::table('category_details')->where('id', $row->category_id)->first();
        $stock = DB::table('stock_details')->where('id', $row->stock_id)->first();
        $branch = DB::table('branch_details')->where('id',$row->branch_id)->first();
        
        $reportData[] = [
            'quantity' => $row->quantity,
            'category' => $category->category_name,
            'stock' => $stock->brand_name,
            'branch'=> $branch->branch_name,
            'sum' => $row->amount
        ];
    }

    $total_quantity = DB::table('asset_repair_details')
        ->whereBetween('date', [$start_date, $end_date])
        ->sum('quantity');

    $total_sum = DB::table('asset_repair_details')
        ->whereBetween('date', [$start_date, $end_date])
        ->sum('amount');

    
    $dataForView = [
        'data' => $reportData,
        'total_quantity' => $total_quantity,
        'total_sum' => $total_sum
    ];

    // Generate PDF
    $pdf = PDF::loadView('pdf.asset_report', $dataForView);
    return $pdf->download('asset_report.pdf');
}
public function export_avaliable_stock(Request $request){
  $data_array = [];
  $data = DB::table('stock_details');
  if($request->category){
    $data->where('category_id',$request->category);
  }

  $query = $data->where('status',1)->get();

  foreach($query as $row){
    $category_name = DB::table('category_details')->where('id',$row->category_id)->first();
     $total_count = DB::table('stock_details')->where('category_id',$row->category_id)->where('id',$row->id)->sum('quantity');
     $vendor = DB::table('vendor_details')->where('id',$row->vendor_id)->first();
     if($vendor){
      $name = $vendor->name;
     }
     else{
      $name = '';
     }
     $branch = DB::table('branch_details')->where('id',$row->branch_id)->first();

    $remaining_count = DB::table('stock_details')->where('category_id',$row->category_id)->where('id',$row->id)->sum('remaining_quantity');
    if($total_count>0){
      $sum = $total_count;
    }
    else{
      $sum = 0;
    }
    $data_array[] = array('category'=>$category_name->category_name,'stock'=>$row->brand_name,'total_count'=>$total_count,'remaining_quantity'=>$sum,'vendor'=> $name,'product_type'=>$row->product_type,'branch_name'=>$branch->branch_name,'product_id'=>$row->product_id,'device_id'=>$row->device_id,'price'=>$row->price,'created_by'=>$row->created_by);


  }
   return Excel::download(new StockDetails($data_array), 'stock_details.xlsx');

  }

  public function import_stock_excel(Request $request){
    $file = $request->file('import_stock');
    //Excel::import(new ImportStock, $file,null, \Maatwebsite\Excel\Excel::XLSX);
     Excel::import(new ImportStock, $request->file('file')->store('files'));
    return response()->json(['status'=>200,'message'=>'Import Successfully']);

  }

  public function branch_export(){
    $data = DB::table('branch_details')->select('id', 'branch_name')->get()->toArray();
    return Excel::download(new BranchDetails($data), 'branch_details.xlsx');

  }
  public function category_based_stock_list($id){
    $stock_list = Stock::where('status',1)->where('category_id',$id)->get(['id','brand_name']);
    return response()->json(['status'=>200,'message'=>'Avalible Stock','data'=>$stock_list]);
  }

  public function stock_history($id){
    $data_array = [];
    $data = DB::table('stock_history_details')->where('stock_id',$id)->orderBy('id','DESC')->get();
    foreach($data as $row){
        $assign_to = DB::table('emp_basic_info')->where('emp_id',$row->assign_to)->first();
        $created_by =  DB::table('emp_basic_info')->where('emp_id',$row->created_by)->first();
    $data_array[] = array('id'=>$row->id,'assign_to'=>$assign_to->emp_fname.' '.$assign_to->emp_lame,
    'assign_date'=>$row->assign_date,'return_date'=>$row->return_date,
    'return_status'=>$row->return_status,'status'=>$row->status,'created_by'=> $created_by->emp_fname.' '. $created_by->emp_lame);

    }
    return response()->json(['status'=>200,'data'=> $data_array]);

  }

  public function emp_stock_history($emp_id,$id){
    $data_array = [];
    $data = DB::table('stock_history_details')->where('assign_to',$emp_id)->where('stock_id',$id)->orderBy('id','DESC')->get();
    foreach($data as $row){
        $assign_to = DB::table('emp_basic_info')->where('emp_id',$row->assign_to)->first();
        $created_by =  DB::table('emp_basic_info')->where('emp_id',$row->created_by)->first();
    $data_array[] = array('id'=>$row->id,'assign_to'=>$assign_to->emp_fname.' '.$assign_to->emp_lame,
    'assign_date'=>$row->assign_date,'return_date'=>$row->return_date,
    'return_status'=>$row->return_status,'status'=>$row->status,'created_by'=> $created_by->emp_fname.' '. $created_by->emp_lame);

    }
    return response()->json(['status'=>200,'data'=> $data_array]);

  }


}