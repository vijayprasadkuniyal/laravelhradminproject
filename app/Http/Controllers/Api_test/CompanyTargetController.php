<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CompanyTarget;
use Validator;
use App\Models\Department;
use DB;
//use App\Models\BasicInfo;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TargetImport;
use App\Exports\ExportTarget;
use Carbon\Carbon;
use App\Models\BasicInfo;

class CompanyTargetController extends Controller
{
    public function company_target_list(){
        $data = CompanyTarget::join('product_details','product_details.id','company_goal_target.product_id')
              ->select('product_details.product_name','company_goal_target.id',
              'company_goal_target.date_from','company_goal_target.date_to',
              'company_goal_target.target_per_month','company_goal_target.target_per_year',
              'company_goal_target.achieved_target','company_goal_target.remark','company_goal_target.status')
              ->get();
         return response()->json(['status'=>200,'message'=>'Target List','data'=>$data]);
    }
   /* public function save_target(Request $request){
        $input = $request->all();
        return $input;
        $validator = Validator::make($input, [
            'inputValues' => 'required',
            'department'=>'required',
            'financial_year'=>'required',
            ]);
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }

        $target_data = [];
        $monthly_data = [];
        $data = json_decode($request->inputValues);
       foreach($data as $row){
        if($row->value!=''){
            $target_data[] = array('attribute_id'=>$row->id,'value'=>$row->value,'department_id'=>$request->department,
            'group_id'=>$request->group,'category_id'=>$cat,'created_by'=>$request->emp_id,'financial_year'=>$request->financial_year);
        }
       }
       DB::table('save_company_target')->insert($target_data);

       $months = [
        'April', 'May', 'June', 'July', 'August', 'September',
        'October', 'November', 'December', 'January', 'February', 'March'
        ];
         foreach($data as $row){
        if($row->value!=''){
            $monthly_target = $row->value / 12;
            for ($month = 4; $month <= 15; $month++) {
                $month_name = date("F", mktime(0, 0, 0, $month, 1));
                $monthly_data[] = [
                    'attribute_id' => $row->id,
                    'value' => $monthly_target,
                    'department_id' => $request->department,
                    'group_id' => $request->group,
                    'category_id' => $request->category,
                    'created_by' => $request->emp_id,
                    'financial_year' => $request->financial_year,
                    'month' => $month_name,
                ];
            }
        }
    }
    DB::table('company_monthly_target')->insert($monthly_data);

       return response()->json(['status' => 200, 'message' => 'Target Created Successfully',]);
    }*/
    public function get_target_status($id){
        $target = CompanyTarget::findOrFail($id);
        if($target){
            return response()->json(['status' => 200, 'message' => 'Target status','data'=>$target->status]);
    
        }
        else{
            return response()->json(['status' =>500, 'message' => 'data not found']);
    
        }
    
    
    }
    public function update_target_status(Request $request){
        //dd('hi');
        $request->validate([
            'target_id' => 'required',
            'status' => 'required|in:0,1', 
        ]);
    
        $target = CompanyTarget::findOrFail($request->target_id);
        $target->status = $request->status;
        $target->save();
        if($target){
            return response()->json(['status' => 200, 'message' => 'Target status updated successfully']);
    
        }
        else{
            return response()->json(['status' => 500, 'message' => 'something went wrong']);
    
        }
    
    
    }
    public function target_edit($id){
        $target = CompanyTarget::findOrFail($id);
        if($target){
            return response()->json(['status' => 200, 'message' => 'Target Details','data'=>$target]);
         }
        else{
            return response()->json(['status' => 500, 'message' => 'something went wrong']);
        }
       }
   public function update_target(Request $request,$id){
    $input = $request->all();
    $validator = Validator::make($input, [
        'product' => 'required',
        'start_date'=>'required',
        'end_date'=>'required',
        'target_per_month'=>'required',
        'remark'=>'required',
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $target = CompanyTarget::findOrFail($id);
      $target->product_id = $request->product;
      $target->date_from = $request->start_date;
      $target->date_to = $request->end_date;
      $target->target_per_month = $request->target_per_month;
      $per_year = $request->target_per_month*12;
      $target->target_per_year = $per_year;
      $target->remark = $request->remark;
      $target->created_by = $request->emp_id;
      $target->save();
      if($target){
         return response()->json(['status'=>200,'message'=>'Target Updated Successfully']);
     }
     else{
        return response()->json(['status'=>500,'message'=>'something went wrong']);

     }

    
   }
   public function kra_kpi_attribute($dept_id){
    $data = DB::table('kra_and_kpi_attribute')->where('department_id',$dept_id)->get(['id','attribute_name']);
    return response()->json(['status'=>200,'message'=>'kra and kpi attributes','data'=>$data]);

   }
   public function category_details($id){
    $data = DB::table('product_service')->where('product_id',$id)->get(['id','service_name']);
    return response()->json(['status'=>200,'message'=>'category Details','data'=>$data]);
   }
   public function group_details(){
    $data = DB::table('group_names')->get(['group_id','name']);
    return response()->json(['status'=>200,'message'=>'Group Details','data'=>$data]);

   }
   public function save_target_attribute(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'name' => 'required',
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $data = array('attribute_name'=>$request->name,
      'created_by'=>$request->emp_id);
       DB::table('company_goals')->insert($data);
       return response()->json(['status'=>200,'message'=>'Goal Created Successfully']);
     }
    public function attribute_list(Request $request){
        $attribute_details = [];
        $data = DB::table('company_goals')->paginate($request->per_page);
        foreach($data as $row){
           // $department = Department::where('id',$row->department_id)->first();
            $attribute_details[] = array('id'=>$row->id,'attribute_name'=>$row->attribute_name,
            'status'=>$row->status,'id'=>$row->id);

        }
        return response()->json(['status'=>200,'message'=>'Attribute List','data'=>$attribute_details,'last_page'=>$data->lastPage()]);


    }
    public function edit_attribute($id){
        $data = DB::table('company_goals')->where('id',$id)->first();
        return response()->json(['status'=>200,'data'=>$data]);
    }
    public function update_attribute(Request $request,$id){
        $input = $request->all();
        $validator = Validator::make($input, [
            'name' => 'required',
            ]);
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
          DB::table('company_goals')->where('id',$id)->
          update(['attribute_name'=>$request->name,
          'created_by'=>$request->emp_id]);
          return response()->json(['status'=>200,'message'=>'Attribute Updated Successfully']);

       }
    public function attribute_status($id){
        $data = DB::table('company_goals')->where('id',$id)->first();
        return response()->json(['status'=>200,'message'=>'Attribute Status','data'=>$data->status]);
     }
    public function attribute_status_update(Request $request){
        $request->validate([
            'attribute_id' => 'required',
            'status' => 'required|in:0,1', 
        ]);
        //return $att_id;
        DB::table('company_goals')->where('id',$request->attribute_id)->update(['status'=>$request->status]);
        return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);

    }
//     public function target_details(Request $request){
//     $target_details = [];
//        $currentMonth = date('n');
//         $currentYear = date('Y');
//         if ($currentMonth >= 4) {
//             $financialYearStart = $currentYear;
//             $financialYearEnd = $currentYear + 1;
//         } else {
//             $financialYearStart = $currentYear - 1;
//             $financialYearEnd = $currentYear;
//         }
        
//         $financialYear = $financialYearStart . '-' . $financialYearEnd;

//     // Initial query to retrieve all data grouped by department, category, and group
//       $query = CompanyTarget::select('department_id', 'category_id','attribute_id')
//               ->groupBy('department_id', 'category_id','attribute_id');

//           $get_attribute = CompanyTarget::select('department_id', 'category_id','attribute_id')
//               ->groupBy('department_id', 'category_id','attribute_id');
//           $get_group = CompanyTarget::select('department_id', 'category_id','attribute_id')
//               ->groupBy('department_id', 'category_id','attribute_id');

//     // Apply filters based on request parameters
//     if ($request->department) {
//         $query->where('department_id', $request->department);
//         $get_attribute->where('department_id', $request->department);
//         $get_group->where('department_id', $request->department);
//     }
//     if ($request->category) {
//         $query->where('category_id', $request->category);
//         $get_attribute->where('category_id', $request->category);
//         $get_group->where('category_id', $request->category);
//     }
//     if ($request->financial_year) {
//         $query->where('financial_year', $request->financial_year);
//         $get_attribute->where('financial_year',$request->financial_year);
//         $get_group->where('financial_year',$request->financial_year);
//     }

//     // If attribute filter is provided, retrieve details with attribute values
//     if ($request->attribute) {
//         //return $request->attribute;
//         $target_details =  [];
//         $x = $get_attribute->where('financial_year',$financialYear)->get();
//         foreach ($x as $row) {
//             //$group_name='';
//             $category_name = '';
//             $department = Department::where('id', $row->department_id)->first();
//             //$group = DB::table('group_names')->where('group_id', $row->group_id)->first();
//             $category = DB::table('product_category')->where('id', $row->category_id)->first();
//             if ($category) {
//                 $category_name = $category->category_name;
//             }
//             $attributes = DB::table('target_subattribute')->whereIn('id', $request->attribute)->get(['subattribute_name','id']);
//              $attributes_id = DB::table('kra_and_kpi_attribute')->where('id',$row->attribute_id)->first();
//              //\DB::enableQueryLog();
//             $values = CompanyTarget::
//                  where('department_id', $row->department_id)
//                  ->where('category_id', $row->category_id)
//                 ->whereIn('attribute_id', $request->attribute)
//                 ->where('financial_year',$request->financial_year)
//                 ->pluck('subattribute_amount', 'subattribute_id');

//             $target_details[] = [
//                 'department' => $department->department_name,
//                 'category' =>  $category_name,
//                 'attributes' => $attributes,
//                 'values' => $values,
//                 'attribute_id'=>$attributes_id->attribute_name,
//             ];
//         }
//     }

//     if ($request->group) {
//         $target_details =  [];
//         $gr = $get_group->where('financial_year',$financialYear)->get();
//         foreach ($gr as $col) {
//             //$group_name='';
//             $category_name = '';
//             $department = Department::where('id', $col->department_id)->first();
//             //$group = DB::table('group_names')->where('group_id', $row->group_id)->first();
//             $category = DB::table('product_category')->where('id', $col->category_id)->first();
//             if ($category) {
//                 $category_name = $category->category_name;
//             }
//             $group = DB::table('group_names')->whereIn('group_id', $request->group)->get(['name','group_id']);
//              $attributes_id = DB::table('kra_and_kpi_attribute')->where('id',$col->attribute_id)->first();

//             $group_data = CompanyTarget::
//                  where('department_id', $col->department_id)
//                 ->where('category_id', $col->category_id)
//                 ->whereIn('group_id', $request->group)
//                 ->where('financial_year',$request->financial_year)
//                 ->pluck('group_value','group_id');

//             $target_details[] = [
//                 'department' => $department->department_name,
//                 'category' =>  $category_name,
//                 'group' =>$group,
//                 'group_data' =>$group_data,
//                 'attribute_id'=>$attributes_id->attribute_name,
//             ];
//         }
//     }

//     // Retrieve default data if no attribute filter is provided
//     if (!$request->attribute && !$request->group) {
//         $default_data = $query->get();
//         foreach ($default_data as $row) {
//             $category_name = '';
//             $department = Department::where('id', $row->department_id)->first();
//             $attributes_id = DB::table('kra_and_kpi_attribute')->where('id',$row->attribute_id)->first();
//             //$group = DB::table('group_names')->where('group_id', $row->group_id)->first();
//             $category = DB::table('product_category')->where('id', $row->category_id)->first();
//             if ($category) {
//                 $category_name = $category->category_name;
//             }
//             $target_details[] = [
//                 'department' => $department->department_name,
//                 'category' =>  $category_name,
//                 'attribute_id'=>$attributes_id->attribute_name,
//             ];
//         }
//     }

//     return response()->json(['status' => 200, 'data' => $target_details]);
// }

    public function department_based_attribute($id){
        $data = DB::table('kra_and_kpi_attribute')->where('department_id',$id)->where('status',1)->get(['id','attribute_name']);
        return response()->json(['status'=>200,'data'=>$data]);
     }
     public function department_wise_target_details(Request $request) {
        $group_name = '';
        $category_name = '';
        $target_details = [];
        $currentMonth = date('n');
        $currentYear = date('Y');
        $currentMonthName = date("F");
        if ($currentMonth >= 4) {
            $financialYearStart = $currentYear;
            $financialYearEnd = $currentYear + 1;
        } else {
            $financialYearStart = $currentYear - 1;
            $financialYearEnd = $currentYear;
        }
        
        $financialYear = $financialYearStart . '-' . $financialYearEnd;
        
        $data = BasicInfo::where('reporting_manager', $request->emp_id)->first();
    
        if ($data) {
            $emp_details = BasicInfo::where('emp_id', $data->reporting_manager)->first();
            $query = DB::table('company_monthly_target')
                        ->where('department_id', $emp_details->dept_id);
    
            if ($request->category) {
                $query->where('category_id', $request->category);
            }
            if ($request->group) {
                $query->where('group_id', $request->group);
            }
            if ($request->attribute) {
                $query->whereIn('attribute_id', $request->attribute);
            }
            if($request->financial_year || $request->month){
                // return 'kkk';
                  $query->where('financial_year',$request->financial_year)->where('month',$request->month);
                  $queryResult = $query->orderBy('id','DESC')->paginate($request->per_page);
     
             }
             if(!$request->financial_year || !$request->month){
                 $queryResult = $query->where('financial_year',$financialYear)->where('month',$currentMonthName)->orderBy('id','DESC')->paginate($request->per_page);
     
             }
    
           // $queryResult = $query->paginate($request->per_page);
    
            foreach ($queryResult as $row) {
                $department = Department::find($row->department_id);
                if($row->is_group_attribute==1){
                  $group = DB::table('group_names')->where('group_id', $row->selected_attribute_id)->first();
                  $group = $group->name;
                  $selected_attribute_id = $row->selected_attribute_id;
                 }
                 else{
                   $sub_attribute = DB::table('target_subattribute')
                   ->where('id',$row->selected_attribute_id)->first();
                   $group = $sub_attribute->subattribute_name;
                  $selected_attribute_id = $row->selected_attribute_id;

                 }
                $category = DB::table('product_service')->where('id', $row->category_id)->first();
                $attribute = DB::table('kra_and_kpi_attribute')->where('id', $row->parent_attribute)->first();
                $category_name = $category ? $category->service_name : '';
    
                $target_details[] = [
                    'department' => $department ? $department->department_name : '',
                    'category' => $category_name,
                    'attribute' => $attribute ? $attribute->attribute_name : '',
                    'id' => $row->id,
                    'department_id' => $row->department_id,
                    'category_id' => $row->category_id,
                    'financial_year'=>$row->financial_year,
                    'month'=>$row->month,
                    'group'=>$group,
                    'value'=>$row->value,
                    'selected_attribute_id'=>$selected_attribute_id,
                    'is_group_attribute'=>$row->is_group_attribute,
                ];
            }
        }
    
        return response()->json([
            'status' => 200,
            'message' => 'Target Details',
            'data' => $target_details,
            'last_page' => $queryResult->lastPage(),
        ]);
    }
    
    public function save_assign_target(Request $request){
      //return $request->emp_ids;
      $data = [];
      $ids = json_decode($request->emp_ids);
      foreach($ids as $row){
       // return $row->emp_id;
      if($row->value!=" "){
       $data[] = array('category_id'=>$request->category, 'group_id'=>$request->group,
                      'department_id'=>$request->department,
                       'assign_data'=>$row->value,'created_by'=>$request->emp_id,
                       'remark'=>$request->remark,'emp_id'=>$row->emp_id,'is_sales_emp'=>$request->emp_type,'financial_year'=>$request->year,
                       'month'=>$request->month,'source_id'=>$request->source);


      }



     }
         DB::table('assign_target')->insert($data);
        return response()->json(['status'=>200,'message'=>'Target Assign Successfully']);

    }
    public function assign_target_list(Request $request){
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
        
        if($request->financial_year){
            $data = DB::table('assign_target')->where('created_by',$request->emp_id)->where('financial_year',$request->financial_year)->orderBy('id','DESC')->get();
            
        }
        else{
            $data = DB::table('assign_target')->where('created_by',$request->emp_id)->where('financial_year', $financialYear)->orderBy('id','DESC')->get();
          }
        //return $data;
        $target_details = [];
        foreach($data as $row){
            $emp_id = BasicInfo::where('emp_id',$row->emp_id)->first();
           // $dept_id = Department::where('id',$row->department_id)->first();
           $group = DB::table('group_names')->where('group_id',$row->group_id)->first();
           $category = DB::table('product_service')->where('id',$row->category_id)->first();
           $source = DB::table('sub_sub_attribute')->where('id',$row->source_id)->first();

        $target_details[] = array('id'=>$row->id,'emp'=>$emp_id->emp_fname,'group'=>$group->name,
        'category'=>$category->service_name,'remark'=>$row->remark,'value'=>$row->assign_data,'month'=>$row->month,'financial_year'=>$row->financial_year);
          }
        return response()->json(['status'=>200,'data'=>$target_details]);
        
    }
      public function import_target_excel(Request $request){
        Excel::import(new TargetImport, $request->file('file')->store('files'));
       return response()->json(['status'=>200,'message'=>'File Uploaded Successfully']);
      }
      public function export_target(Request $request){
        return Excel::download(new ExportTarget, 'target.xlsx');

    }
    public function emp_target_list(Request $request){
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
        
        if($request->financial_year){
            $data = DB::table('assign_target')->where('emp_id',$request->emp_id)->where('financial_year',$request->financial_year)->orderBy('id','DESC')->get();
            
        }
        else{
            $data = DB::table('assign_target')->where('emp_id',$request->emp_id)->where('financial_year', $financialYear)->orderBy('id','DESC')->get();
          }
        //return $data;
        $target_details = [];
        foreach($data as $row){
            $emp_id = BasicInfo::where('emp_id',$row->emp_id)->first();
           // $dept_id = Department::where('id',$row->department_id)->first();
           $group = DB::table('group_names')->where('group_id',$row->group_id)->first();
           $category = DB::table('product_service')->where('id',$row->category_id)->first();
           $source = DB::table('sub_sub_attribute')->where('id',$row->source_id)->first();

        $target_details[] = array('id'=>$row->id,'emp'=>$emp_id->emp_fname,'group'=>$group->name,
        'category'=>$category->service_name,'remark'=>$row->remark,'value'=>$row->assign_data,'month'=>$row->month,'financial_year'=>$row->financial_year);
          }
        return response()->json(['status'=>200,'data'=>$target_details]);


    }
    public function save_target_subattribute(Request $request){
      $input = $request->all();
      $department = $request->department;
      $validator = Validator::make($input, [
        'name' => 'required',
        'attribute'=>'required',
        ]);
      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
     // $dept_id = DB::table('kra_and_kpi_attribute')->where('id',$request->attribute)->first();
      $data = array('attribute_name'=>$request->name,
      'goal_id'=>$request->attribute,'created_by'=>$request->emp_id,'department_id'=>$department);
       DB::table('target_attribute')->insert($data);
       return response()->json(['status'=>200,'message'=>'Attribute Created Successfully']);
     }

     public function subattribute_list(Request $request){
        $subattribute_details = [];
        $data = DB::table('target_attribute')->paginate($request->per_page);
        foreach($data as $row){
            $dept = explode(',', $row->department_id);
                $department = DB::table('department')
                    ->whereIn('id', $dept)
                    ->pluck('department_name')
                    ->implode(',');

            $goal = DB::table('company_goals')->where('id',$row->goal_id)->first();
            $attribute_details[] = array('id'=>$row->id,'attribute_name'=>$row->attribute_name,
            'attribute'=> $goal->attribute_name,'status'=>$row->status,'department'=>$department);

        }
        return response()->json(['status'=>200,'message'=>'Attribute List','data'=>$attribute_details,'last_page'=>$data->lastPage()]);


    }
     public function edit_subattribute($id){
        $data = DB::table('target_attribute')->where('id',$id)->first();
        return response()->json(['status'=>200,'data'=>$data]);
    }

     public function update_subattribute(Request $request,$id){
        //return $request->all();
        $input = $request->all();
        $department = $request->department;
        $validator = Validator::make($input, [
            'name' => 'required',
            'attribute'=>'required',
            ]);
          if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
          // $dept_id = DB::table('kra_and_kpi_attribute')->where('id',$request->attribute)->first();
          DB::table('target_attribute')->where('id',$id)->
          update(['attribute_name'=>$request->name,
          'goal_id'=>$request->attribute,'created_by'=>$request->emp_id,'department_id'=>$department]);
          return response()->json(['status'=>200,'message'=>'Attribute Updated Successfully']);

       }

      public function subattribute_status($id){
        $data = DB::table('target_attribute')->where('id',$id)->first();
        return response()->json(['status'=>200,'message'=>'Attribute Status','data'=>$data->status]);
        }
    public function subattribute_status_update(Request $request){
        $request->validate([
            'subattribute_id' => 'required',
            'status' => 'required|in:0,1', 
        ]);
        //return $request->attribute_id;
        DB::table('target_attribute')->where('id',$request->subattribute_id)->update(['status'=>$request->status]);
        return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);

    }
    public function active_attribute(){
       $data = DB::table('company_goals')->where('status',1)->get(['id','attribute_name']);
       return response()->json(['status'=>200,'data'=>$data]);

    }
    public function get_subattribute($id){
       $id = explode(',',$id);
      $data = DB::table('target_subattribute')->whereIn('attribute_id',$id)->where('status',1)->get(['id','subattribute_name']);
      return response()->json(['status'=>200,'data'=>$data]);

    }
    public function department_subattribute($id){
      $data = DB::table('target_subattribute')->where('department_id',$id)->get(['id','subattribute_name']);
       return response()->json(['status'=>200,'data'=>$data]);

    }
//   public function save_target(Request $request)
// {
//     return $request->all();
//     $input = $request->all();
//     $category = explode(',', $request->category);
//     $attribute = explode(',', $request->attribute);
//     $target_data = [];
//     $month_target_data = [];
//     $grp_data = [];
//     $month_grp_data = [];

//     $validator = Validator::make($input, [
//         'department' => 'required',
//         'financial_year' => 'required',
//     ]);

//     if ($validator->fails()) {
//         $messages = $validator->messages();
//         return response()->json(["messages" => $messages, 'status' => 400]);
//     }

//     $data = json_decode($request->inputValues);
//     $group = json_decode($request->group_data);

//     foreach ($category as $cat) {
//         if ($request->total) {
//             foreach ($data as $row) {
//                 if ($row->value != '') {
//                     foreach ($attribute as $att) {
//                         $no_of_data = $request->valuetype == 'Percent' ?
//                             round($request->total * $row->value / 100) :
//                             $row->value;
//                         $per_month_data = round($no_of_data / 12);
//                         $target_data[] = [
//                             'category_id' => $cat,
//                             'total' => $request->total,
//                             'attribute_id' => $att,
//                             'subattribute_id' => $row->id,
//                             'subattribute_percent' => $row->value,
//                             'subattribute_amount' => $no_of_data,
//                             'created_by' => $request->emp_id,
//                             'financial_year' => $request->financial_year,
//                             'department_id' => $request->department
//                         ];

//                         foreach (range(4, 15) as $month) {
//                             $month_name = date("F", mktime(0, 0, 0, $month, 1));
//                             $month_target_data[] = [
//                                 'category_id' => $cat,
//                                 'total' => $request->total,
//                                 'parent_attribute' => $att,
//                                 'selected_attribute_id' => $row->id,
//                                 'value' => $per_month_data,
//                                 'created_by' => $request->emp_id,
//                                 'financial_year' => $request->financial_year,
//                                 'department_id' => $request->department,
//                                 'month' => $month_name,
//                                 'is_group_attribute' => 0
//                             ];
//                         }
//                     }
//                 }
//             }

//             foreach ($group as $grp) {
//                 if ($grp->value != '') {
//                     foreach ($attribute as $att) {
//                         $no_of_value = $request->valuetype == 'Percent' ?
//                             round($request->total * $grp->value / 100) :
//                             $grp->value;
//                         $month_data = round($no_of_value / 12);
//                         $grp_data[] = [
//                             'category_id' => $cat,
//                             'total' => $request->total,
//                             'attribute_id' => $att,
//                             'group_id' => $grp->id,
//                             'group_percent' => $grp->value,
//                             'group_value' => $no_of_value,
//                             'created_by' => $request->emp_id,
//                             'financial_year' => $request->financial_year,
//                             'department_id' => $request->department
//                         ];

//                         foreach (range(4, 15) as $month) {
//                             $month_name = date("F", mktime(0, 0, 0, $month, 1));
//                             $month_grp_data[] = [
//                                 'category_id' => $cat,
//                                 'total' => $request->total,
//                                 'parent_attribute' => $att,
//                                 'selected_attribute_id' => $grp->id,
//                                 'value' => $month_data,
//                                 'created_by' => $request->emp_id,
//                                 'financial_year' => $request->financial_year,
//                                 'department_id' => $request->department,
//                                 'month' => $month_name,
//                                 'is_group_attribute' => 1
//                             ];
//                         }
//                     }
//                 }
//             }
//         } elseif ($request->total == '') {
//             foreach ($data as $row) {
//                 if ($row->value != '') {
//                     foreach ($attribute as $att) {
//                         $no_of_data = $row->value;
//                         $per_month_data = $row->value;
//                         $target_data[] = [
//                             'category_id' => $cat,
//                             'total' => '',
//                             'attribute_id' => $att,
//                             'subattribute_id' => $row->id,
//                             'subattribute_percent' => $row->value,
//                             'subattribute_amount' => $no_of_data,
//                             'created_by' => $request->emp_id,
//                             'financial_year' => $request->financial_year,
//                             'department_id' => $request->department
//                         ];

//                         foreach (range(4, 15) as $month) {
//                             $month_name = date("F", mktime(0, 0, 0, $month, 1));
//                             $month_target_data[] = [
//                                 'category_id' => $cat,
//                                 'total' => '',
//                                 'parent_attribute' => $att,
//                                 'selected_attribute_id' => $row->id,
//                                 'value' => $per_month_data,
//                                 'created_by' => $request->emp_id,
//                                 'financial_year' => $request->financial_year,
//                                 'department_id' => $request->department,
//                                 'month' => $month_name,
//                                 'is_group_attribute' => 0
//                             ];
//                         }
//                     }
//                 }
//             }

//             foreach ($group as $grp) {
//                 if ($grp->value != '') {
//                     foreach ($attribute as $att) {
//                         $no_of_value = $grp->value;
//                         $month_data = $grp->value;
//                         $grp_data[] = [
//                             'category_id' => $cat,
//                             'total' => '',
//                             'attribute_id' => $att,
//                             'group_id' => $grp->id,
//                             'group_percent' => $grp->value,
//                             'group_value' => $no_of_value,
//                             'created_by' => $request->emp_id,
//                             'financial_year' => $request->financial_year,
//                             'department_id' => $request->department
//                         ];

//                         foreach (range(4, 15) as $month) {
//                             $month_name = date("F", mktime(0, 0, 0, $month, 1));
//                             $month_grp_data[] = [
//                                 'category_id' => $cat,
//                                 'total' => '',
//                                 'parent_attribute' => $att,
//                                 'selected_attribute_id' => $grp->id,
//                                 'value' => $month_data,
//                                 'created_by' => $request->emp_id,
//                                 'financial_year' => $request->financial_year,
//                                 'department_id' => $request->department,
//                                 'month' => $month_name,
//                                 'is_group_attribute' => 1
//                             ];
//                         }
//                     }
//                 }
//             }
//         }
//     }

//     DB::table('save_company_target')->insert($target_data);
//     DB::table('company_monthly_target')->insert($month_target_data);
//     DB::table('save_company_target')->insert($grp_data);
//     DB::table('company_monthly_target')->insert($month_grp_data);

//     return response()->json(['status' => 200, 'message' => 'Target Created Successfully']);
// }
public function save_map_attribute(Request $request){
      $input = $request->all();
     // return $input;
      $data = array('attribute_id'=>$request->attribute,
      'subattribute_id'=>$request->subattribute,'department_id'=>$request->department);
       DB::table('target_attribute_map')->insert($data);
       return response()->json(['status'=>200,'message'=>'Attribute Maped Successfully']);



}
 public function attribute_map_list(Request $request)
        {
            $data = [];
            $document_list = DB::table('target_attribute_map')->paginate($request->per_page);
        
            foreach ($document_list as $row) {
                $package_type_id = explode(',', $row->department_id);
                $packageTypes = DB::table('department')
                    ->whereIn('id', $package_type_id)
                    ->pluck('department_name')
                    ->implode(',');
                $attribute = DB::table('kra_and_kpi_attribute')->where('id',$row->attribute_id)->first();
                 $subattribute = DB::table('target_subattribute')->where('id',$row->subattribute_id)->first();
                $data[] = [
                    'id'=>$row->id,
                    'department' => $packageTypes,
                    'attribute'=>$attribute->attribute_name,
                    'subattribute'=>$subattribute->subattribute_name,
                   ];
            }
        
            return response()->json(['status'=>200,'message'=>'Document List','data'=>$data,'last_page'=> $document_list->lastPage()]);
        }
        public function get_active_attribute_list(){
            $data = DB::table('target_attribute')->where('status',1)->get(['id','attribute_name']);
            return response()->json(['status'=>200,'data'=>$data]);

        }

        public function save_goal_subattribute(Request $request){
            $input = $request->all();
            $department = $request->department;
            $validator = Validator::make($input, [
              'name' => 'required',
              'attribute'=>'required',
              ]);
            if($validator->fails()){
                  $messages=$validator->messages();
                  return response()->json(["messages"=>$messages,'status'=>400]);     
            }
           // $dept_id = DB::table('kra_and_kpi_attribute')->where('id',$request->attribute)->first();
            $data = array('subattribute_name'=>$request->name,
            'attribute_id'=>$request->attribute,'department_id'=>$department);
             DB::table('target_subattribute')->insert($data);
             return response()->json(['status'=>200,'message'=>'Attribute Created Successfully']);
           }
      
           public function goal_subattribute__list(Request $request){
              $subattribute_details = [];
              $data = DB::table('target_subattribute')->paginate($request->per_page);
              foreach($data as $row){
                  $dept = explode(',', $row->department_id);
                      $department = DB::table('department')
                          ->whereIn('id', $dept)
                          ->pluck('department_name')
                          ->implode(',');
      
                  $goal = DB::table('target_attribute')->where('id',$row->attribute_id)->first();
                  $attribute_details[] = array('id'=>$row->id,'subattribute_name'=>$row->subattribute_name,
                  'attribute_name'=> $goal->attribute_name,'status'=>$row->status,'department'=>$department);
      
              }
              return response()->json(['status'=>200,'message'=>'Attribute List','data'=>$attribute_details,'last_page'=>$data->lastPage()]);
      
      
          }
           public function edit_goal_subattribute($id){
              $data = DB::table('target_subattribute')->where('id',$id)->first();
              return response()->json(['status'=>200,'data'=>$data]);
          }
      
           public function update_goal_subattribute(Request $request,$id){
              //return $request->all();
              $input = $request->all();
                 $department = $request->department;
                $validator = Validator::make($input, [
                   'name' => 'required',
                   'attribute'=>'required',
                    ]);
                if($validator->fails()){
                  $messages=$validator->messages();
                  return response()->json(["messages"=>$messages,'status'=>400]);     
                }
                // $dept_id = DB::table('kra_and_kpi_attribute')->where('id',$request->attribute)->first();
                DB::table('target_subattribute')->where('id',$id)->
                update(['subattribute_name'=>$request->name,
                'attribute_id'=>$request->attribute,'department_id'=>$department]);
                return response()->json(['status'=>200,'message'=>'Attribute Updated Successfully']);
      
             }
      
            public function get_subattribute_status($id){
              $data = DB::table('target_subattribute')->where('id',$id)->first();
              return response()->json(['status'=>200,'message'=>'Attribute Status','data'=>$data->status]);
              }
          public function sub_attribute_status_update(Request $request){
              $request->validate([
                  'subattribute_id' => 'required',
                  'status' => 'required|in:0,1', 
              ]);
              //return $request->attribute_id;
              DB::table('target_subattribute')->where('id',$request->subattribute_id)->update(['status'=>$request->status]);
              return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);
      
          }
          public function active_goal_subattributes_list(){
            $data = DB::table('target_subattribute')->where('status',1)->get(['id','subattribute_name']);
             return response()->json(['status'=>200,'data'=>$data]);
          }

           public function save_goal_sub_sub_attribute(Request $request){
            $input = $request->all();
            $department = $request->department;
            $validator = Validator::make($input, [
              'name' => 'required',
              'attribute'=>'required',
              ]);
            if($validator->fails()){
                  $messages=$validator->messages();
                  return response()->json(["messages"=>$messages,'status'=>400]);     
            }
           // $dept_id = DB::table('kra_and_kpi_attribute')->where('id',$request->attribute)->first();
            $data = array('name'=>$request->name,
            'subattribute_id'=>$request->attribute,'department_id'=>$department);
             DB::table('sub_sub_attribute')->insert($data);
             return response()->json(['status'=>200,'message'=>'Attribute Created Successfully']);
           }
      
           public function goal_sub_sub_attribute_list(Request $request){
              $subattribute_details = [];
              $data = DB::table('sub_sub_attribute')->paginate($request->per_page);
              foreach($data as $row){
                  $dept = explode(',', $row->department_id);
                      $department = DB::table('department')
                          ->whereIn('id', $dept)
                          ->pluck('department_name')
                          ->implode(',');
      
                  $goal = DB::table('target_subattribute')->where('id',$row->subattribute_id)->first();
                  $attribute_details[] = array('id'=>$row->id,'subattribute_name'=>$row->name,
                  'attribute_name'=> $goal->subattribute_name,'status'=>$row->status,'department'=>$department);
      
              }
              return response()->json(['status'=>200,'message'=>'Attribute List','data'=>$attribute_details,'last_page'=>$data->lastPage()]);
      
      
          }
           public function edit_goal_sub_sub_attribute($id){
              $data = DB::table('sub_sub_attribute')->where('id',$id)->first();
              return response()->json(['status'=>200,'data'=>$data]);
          }
      
           public function update_goal_sub_sub_attribute(Request $request,$id){
              //return $request->all();
              $input = $request->all();
                 $department = $request->department;
                $validator = Validator::make($input, [
                   'name' => 'required',
                   'attribute'=>'required',
                    ]);
                if($validator->fails()){
                  $messages=$validator->messages();
                  return response()->json(["messages"=>$messages,'status'=>400]);     
                }
                // $dept_id = DB::table('kra_and_kpi_attribute')->where('id',$request->attribute)->first();
                DB::table('sub_sub_attribute')->where('id',$id)->
                update(['name'=>$request->name,
                'subattribute_id'=>$request->attribute,'department_id'=>$department]);
                return response()->json(['status'=>200,'message'=>'Attribute Updated Successfully']);
      
             }
      
            public function get_sub_sub_attribute_status($id){
              $data = DB::table('sub_sub_attribute')->where('id',$id)->first();
              return response()->json(['status'=>200,'message'=>'Attribute Status','data'=>$data->status]);
              }
          public function sub_sub_attribute_status_update(Request $request){
              $request->validate([
                  'subattribute_id' => 'required',
                  'status' => 'required|in:0,1', 
              ]);
              //return $request->attribute_id;
              DB::table('sub_sub_attribute')->where('id',$request->subattribute_id)->update(['status'=>$request->status]);
              return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);
      
          }
          public function goal_based_attribute($id,$dept_id){
            $data = DB::table('target_attribute')->where('goal_id',$id)->whereRaw("FIND_IN_SET(?, department_id)", [$dept_id])->get(['id','attribute_name']);

             return response()->json(['status'=>200,'data'=>$data]);
          }
          public function goal_based_subattribute($id,$dept_id){
              $data = DB::table('target_subattribute')->where('attribute_id',$id)->whereRaw("FIND_IN_SET(?, department_id)", [$dept_id])->get(['id','subattribute_name']);

             return response()->json(['status'=>200,'data'=>$data]);

          }
           public function goal_based_sub_subattribute($id,$dept_id){
              $data = DB::table('sub_sub_attribute')->where('subattribute_id',$id)->whereRaw("FIND_IN_SET(?, department_id)", [$dept_id])->get(['id','name']);

             return response()->json(['status'=>200,'data'=>$data]);

          }
        //   public function save_target(Request $request){
        //     $data = json_decode($request->inputValues);
        //     $category = json_decode($request->category_data);
        //     $target_data = [];
        //     $category_data = [];
        //         foreach($data as $row){
        //             if ($row->value != ''){
        //                 if($request->valuetype =='Percent'){
        //                     $calc_data = $request->total*$row->value/100;

        //                 }
        //                 else{
        //                     $calc_data = $row->value;
        //                 }
        //                 $target_data[] = array('product_id'=>$request->group,'department_id'=>$request->department,
        //                 'goal_id'=>$request->goal,'attribute_id'=>$request->attribute,'subattribute_id'=>$row->id,
        //                 'subattribute_value'=>$calc_data,'total_number'=>$request->total,
        //                 'financial_year'=>$request->financial_year,'created_by'=>$request->emp_id);
        //               }


        //         }
        //         DB::table('save_company_target')->insert($target_data);
        //        foreach($category as $ct){
        //         if ($row->value != ''){
        //             if($request->valuetype =='Percent'){
        //                 $cat_data = $request->total*$ct->value/100;

        //             }
        //             else{
        //                 $cat_data = $ct->value;
        //             }
        //             $category_data[] = array('product_id'=>$request->group,'department_id'=>$request->department,
        //             'goal_id'=>$request->goal,'attribute_id'=>$request->attribute,'category_id'=>$ct->id,
        //             'category_value'=>$cat_data,'total_number'=>$request->total,
        //             'financial_year'=>$request->financial_year,'created_by'=>$request->emp_id,'is_category'=>1);
        //           }

        //        }

        //        DB::table('save_company_target')->insert($category_data);
        //        return response()->json(['status'=>200]);



        //   }
          public function target_details(){
            $data = DB::table('save_company_target')->get();
           // return $data;
            $target_data =  [];
            foreach($data as $row){
                if($row->is_category==0){
                    $data = DB::table('sub_sub_attribute')->where('id',$row->subattribute_id)->first();
                    $data = $data->name;
                    $value = $row->subattribute_value;

                }
                if($row->is_category==1){
                    $data = DB::table('product_category')->where('id',$row->category_id)->first();
                    $data = $data->service_name;
                    $value = $row->category_value;

                }
                $goal = DB::table('company_goals')->where('id',$row->goal_id)->first();
                $attribute = DB::table('target_attribute')->where('id',$row->attribute_id)->first();
                $department = DB::table('department')->where('id',$row->department_id)->first();
                
                $target_data[] = array('id'=>$row->id,'subattribute'=>$data,'value'=>$value,'goal'=>$goal->attribute_name,
                'attribute'=>$attribute->attribute_name,'department'=>$department->department_name);
               }
            return response()->json(['status'=>200,'data'=>$target_data]);


          }public function source_category(Request $request){
            // Get the search parameter from the request
            $search = $request->input('sourcecategory'); // Assuming 'sourcecategory' is the parameter name
            
            // Convert the comma-separated string to an array
            $searchArray = explode(',', $search);
            $dt = [];
            
            foreach($searchArray as $row){
                $category_name = DB::table('product_service')->where('id',$row)->first();
                $data = \DB::table("sub_sub_attribute")
                    ->select("sub_sub_attribute.id","sub_sub_attribute.name")
                    ->whereRaw("FIND_IN_SET('$row', mapping_category)")
                    ->get();
                
                $rowData = [];
                foreach($data as $item){
                    $rowData[] = ['id' => $item->id, 'name' => $item->name,'cat_id'=>$row,'category_name'=>$category_name->service_name];
                }
                
                $dt[] = $rowData;
            }
            
            return response()->json(['status'=>200,'data' => $dt]);
        
            // // Initialize an empty array to store the WHERE conditions
            // $whereConditions = [];
        
            // // Iterate over the search array to construct the WHERE conditions
            // foreach ($searchArray as $value) {
            //     $whereConditions[] = "FIND_IN_SET('$value', mapping_category)";
            // }

            // return  $whereConditions;
        
            // Construct the WHERE clause by joining the conditions with OR
        //     $whereClause = implode(' OR ', $whereConditions);
        
        //     // Fetch data from the database based on the constructed WHERE clause
        //     $data = \DB::table("sub_sub_attribute")
        //         ->select("sub_sub_attribute.id","sub_sub_attribute.name")
        //         ->whereRaw($whereClause)
        //         ->get();
        //        $db_data = [];
        //      if(!empty($data)){
        //       foreach ($searchArray as $id) {
        //          $db_data[$id] = $data;
        //        }
                
        //   }
           //return response()->json(['status' => 200, 'data' =>$data]);
        }

        public function save_target(Request $request)
        {
            $input = $request->all();
          //return $input;
            
            $validator = Validator::make($input, [
                'department' => 'required',
                'financial_year' => 'required',
                'goal' => 'required',
                'attribute' => 'required',
                'sub_attribute' => 'required',
                'group' => 'required',
                'inputValues' => 'required', 
                'dataarray' => 'required',   
                'is_checked_category' => 'required|boolean',
            ]);
        
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()->all(), 'status' => 400]);
            }
        
            $value_array = [];
        
            $data = json_decode($request->input('inputValues'));
            $cat_data = json_decode($request->input('dataarray'));
        
            if ($request->is_checked_category == 0) {
                foreach ($data as $row) {
                    if (!empty($row->value)) {
                        $value_array[] = [
                            'department_id' => $request->department,
                            'attribute_id' => $request->attribute,
                            'subattribute_id' => $request->sub_attribute,
                            'child_attribute_id' => $row->id,
                            'child_attribute_value' => $row->value,
                            'financial_year' => $request->financial_year,
                            'created_by' => $request->emp_id,
                            'product_id' => $request->group,
                            'goal_id' => $request->goal,
                        ];
                    }
                }
            } else {
                $sum = 0;
                foreach ($cat_data as $ct) {
                    if ($ct->finalvalue==null) {
                         $val  = $ct->value;
                         foreach($cat_data as $inner_item){
                          if($ct->id === $inner_item->id  && $inner_item->finalvalue!==null ){
                            $reault = ($val*$inner_item->e_data)/100;
                            $sum = $sum+$reault;
                             $value_array[] = [
                            'department_id' => $request->department,
                            'attribute_id' => $request->attribute,
                            'subattribute_id' => $request->sub_attribute,
                            'child_attribute_id' => $inner_item->finalvalue,
                            'child_attribute_value' => $reault,
                            'financial_year' => $request->financial_year,
                            'created_by' => $request->emp_id,
                            'product_id' => $request->group,
                            'goal_id' => $request->goal,
                            'category_id' => $inner_item->data,
                            'remaining_value'=>$reault,
                        ];



                  }


          }
             }
            }
            if($sum<$request->total){
                return response()->json(['message' => 'no','sum'=>$sum]);
  
  
              }
          }
        
            DB::table('save_company_target')->insert($value_array);
        
            return response()->json(['status' => 200, 'message' => 'Target Created Successfully']);
        }
           public function show_assign_data($id,$emp_id,$financial_year){
            $data_array = [];
            $total_data = '';
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

           $reporting_manager = DB::table('emp_basic_info')->where('reporting_manager',$emp_id)->first();
           //return $reporting_manager;
            if($reporting_manager){
                $emp_details = DB::table('emp_basic_info')->where('emp_id', $reporting_manager->reporting_manager)->first();
                //return $emp_details;
                if($emp_details->dept_id == 10){
                   // return 'jj';
                    $total_data  = DB::table('save_company_target')->where('category_id',$id)
                    ->where('department_id',10)->where('financial_year',$financial_year)
                    ->sum('remaining_value');
                  $data =  DB::table('save_company_target')->where('category_id',$id)
                  ->where('department_id',10)->where('financial_year',$financial_year)->get();
                  foreach($data as $row){
                    $sub_attribute = DB::table('sub_sub_attribute')->where('id',$row->child_attribute_id)->first();
                    //$value =
                    $data_array[] = array('sub_attributes'=>$sub_attribute->name,'value'=>$row->remaining_value,'id'=>$row->id);
                  }
                   return response()->json(['status' => 200, 'data'=>$data_array,'total'=>$total_data]);

                }
                else{
                    return response()->json(['status' => 200, 'data'=>$data_array,'total'=>$total_data]);

                }
                //$department_id =  DB::table('emp_basic_info')->where('dept_id',$emp_id)->first();
    


            }
            else{
                return response()->json(['status' => 200, 'data'=>$data_array,'total'=>$total_data]);

            }
                 

            }
            public function assign_to_groups($id,$group_id){
                $grp_data = [];
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
                    $group_ids_list = explode(',',$group_id);
                $groups = DB::table('group_names')->whereIn('group_id',$group_ids_list)->get(['group_id','name']);
                foreach($groups as $grp){
                   $cat =  DB::table('product_service')->where('product_id',$id)->get();
                   foreach($cat as $ct){
                    $sub_cat = DB::table('sub_sub_attribute')->whereIn('id',[1,2])->get(['id','name']);
                    $total_leads = DB::table('save_company_target')
                    ->where('category_id',$ct->id)->where('department_id',10)->where('financial_year',$financialYear)->sum('child_attribute_value');
                    foreach($sub_cat as $sub){
                       $total_source_leads =  DB::table('save_company_target')->where('category_id',$ct->id)
                      ->where('child_attribute_id',$sub->id)->where('department_id',10)->where('financial_year',$financialYear)->sum('child_attribute_value');
                      $grp_data[] = array('id'=>$sub->id,'name'=>$sub->name,'group_id'=>$grp->group_id,'cat_id'=>$ct->id,
                      'category_name'=>$ct->service_name,'group_name'=>$grp->name,'total_leads'=>$total_leads,'source_leads'=>$total_source_leads);

                    }

                   }

                }
                return response()->json(['status'=>200,'data'=>$grp_data]);

            }
          public function save_assign_leads_to_groups(Request $request) {
    $input = $request->all();
    $months = [
        'April', 'May', 'June', 'July', 'August', 'September',
        'October', 'November', 'December', 'January', 'February', 'March'
    ];

    $validator = Validator::make($input, [
        'valuesdata' => 'required',
        'financial_year' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()->all(), 'status' => 400]);
    }

    $data = json_decode($request->valuesdata);

    foreach ($data as $row) {
        if ($row->value != "") {
          $remaining_leads = DB::table('save_assign_leads_to_groups')->where('category_id',$row->catId)->where('group_id',$row->finalValue)->where('source_id',$row->id)->first();
          if(!$remaining_leads){
              $leads_data =  DB::table('save_company_target')->where('category_id',$row->catId)->where('child_attribute_id',$row->id)->first();
              $leads =   DB::table('save_company_target')->where('category_id',$row->catId)->where('child_attribute_id',$row->id);
              $leads->update(['remaining_value'=>$leads_data->remaining_value - $row->value ]);
           }
           else{
            if($remaining_leads->value>$row->value){
              $diff = $remaining_leads->value - $row->value;
               $leads_data =  DB::table('save_company_target')->where('category_id',$row->catId)->where('child_attribute_id',$row->id)->first();
                $leads =   DB::table('save_company_target')->where('category_id',$row->catId)->where('child_attribute_id',$row->id);
              $leads->update(['remaining_value'=>$leads_data->remaining_value + $diff ]);



            }
            else{
               $diff =  $row->value - $remaining_leads->value;
                $leads_data =  DB::table('save_company_target')->where('category_id',$row->catId)->where('child_attribute_id',$row->id)->first();
                 $leads  =  DB::table('save_company_target')->where('category_id',$row->catId)->where('child_attribute_id',$row->id);
                 $leads->update(['remaining_value'=>$leads_data->remaining_value - $diff ]);


            }
           }
            $data_array[] = [
                'group_id' => $row->finalValue,
                'category_id' => $row->catId,
                'source_id' => $row->id,
                'value' => $row->value,
                'created_by' => $request->emp_id,
                'financial_year' => $request->financial_year,
            ];
        }
    }

    foreach ($months as $month) {
        foreach ($data as $row) {
            if ($row->value != "") {
                $month_grp_data[] = [
                    'group_id' => $row->finalValue,
                    'category_id' => $row->catId,
                    'source_id' => $row->id,
                    'value' => $row->value / 12,
                    'created_by' => $request->emp_id,
                    'financial_year' => $request->financial_year,
                    'month' => $month,
                ];
            }
        }
    }

    foreach ($data_array as $item) {
        DB::table('save_assign_leads_to_groups')
            ->updateOrInsert(
                [
                    'group_id' => $item['group_id'],
                    'category_id' => $item['category_id'],
                    'source_id' => $item['source_id'],
                    'financial_year' => $item['financial_year'],
                ],
                $item
            );
    }

    foreach ($month_grp_data as $item) {
        DB::table('monthly_lead_assign_to_groups')
            ->updateOrInsert(
                [
                    'group_id' => $item['group_id'],
                    'category_id' => $item['category_id'],
                    'source_id' => $item['source_id'],
                    'financial_year' => $item['financial_year'],
                    'month' => $item['month'],
                ],
                $item
            );
    }

    return response()->json(['status' => 200, 'message' => 'Assigned To Group Successfully']);
}


            public function get_monthly_data(Request $request){
                $total_data = [];
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
                    $current_month = Carbon::now()->format('F');
                    //return  $current_month;
                    $query = DB::table('monthly_lead_assign_to_groups');
                    if ($request->category) {
                        $query->where('category_id', $request->category);
                    }
                    if ($request->group) {
                        $query->where('group_id', $request->group);
                    }
                    if($request->financial_year && $request->month){
                        // return 'kkk';
                          $query->where('financial_year',$request->financial_year)->where('month',$request->month);
                          $queryResult = $query->orderBy('id','DESC')->paginate($request->per_page);
             
                     }
                     if(!$request->financial_year || !$request->month){
                         $queryResult = $query->where('financial_year',$financialYear)->where('month',$current_month)->orderBy('id','DESC')->paginate($request->per_page);
             
                     }
                    if($request->additional){
                      $query->select(
                       'category_id',
                       'group_id',
                       'financial_year',
                       'month',
                       'id',
                        DB::raw('SUM(value) as total_value')
                        )->groupBy('category_id', 'group_id', 'financial_year', 'month','id');

                        $queryResult = $query->first();
                       if ($queryResult) {
            $sum = DB::table('monthly_lead_assign_to_groups')->where('category_id',$queryResult->category_id)->where('group_id',$queryResult->group_id)->where('month',$queryResult->month)->where('financial_year',$queryResult->financial_year)->sum('value');
            $group = DB::table('group_names')->where('group_id', $queryResult->group_id)->first();
            $category = DB::table('product_service')->where('id', $queryResult->category_id)->first();
            $total_data[] = [
                'group' => $group->name,
                'category' => $category->service_name,
                'total_value' => $queryResult->total_value,
                'month' => $queryResult->month,
                'financial_year' => $queryResult->financial_year,
                'group_id' => $queryResult->group_id,
                'category_id' => $queryResult->category_id,
            ];

            return response()->json(['status' => 200, 'data' => $total_data,'sum'=>$sum]);
        }
      }
                    else{
                     foreach($queryResult as $row){
                        $group = DB::table('group_names')->where('group_id',$row->group_id)->first();
                        $category = DB::table('product_service')->where('id',$row->category_id)->first();
                        $source = DB::table('sub_sub_attribute')->where('id',$row->source_id)->first();
                        $total_data[] = array('id'=>$row->id,'group'=>$group->name,'category'=>$category->service_name,
                        'source'=>$source->name,'value'=>$row->value,'month'=>$row->month,
                        'financial_year'=>$row->financial_year,'group_id'=>$row->group_id,'category_id'=>$row->category_id,'source_id'=>$row->source_id);

                    }
                    return response()->json(['status' => 200, 'data'=>$total_data,'last_page'=>$queryResult->lastPage()]);
                   }
            }
            public function lead_edit($id,$group,$category){
                $data = DB::table('monthly_lead_assign_to_groups')->where('id',$id)->where('group_id',$group)->where('category_id',$category)->first();
                return response()->json(['status' => 200, 'data'=>$data->value]);


            }
            public function save_edit_lead(Request $request,$id,$group_id,$category_id){
                $input = $request->all();
                $validator = Validator::make($input, [
                   'lead' => 'required',
                    ]);
                if($validator->fails()){
                  $messages=$validator->messages();
                  return response()->json(["messages"=>$messages,'status'=>400]);     
                }
                $data = DB::table('monthly_lead_assign_to_groups')->where('id',$id)->where('group_id',$group_id)->where('category_id',$category_id)->first();
                $value = $data->value;
                if($value<$request->lead){
                    $diff = $request->lead - $value;
                    $month = $data->month;
                    $date = Carbon::createFromFormat('F', $month);
                    $date->addMonth();
                    $newMonthName =   $date->format('F');
                    $next_month_data =  DB::table('monthly_lead_assign_to_groups')->where('group_id',$group_id)
                    ->where('category_id',$category_id)->where('source_id',$request->source)
                    ->where('month',$newMonthName)->where('financial_year',$data->financial_year)->first();
                     DB::table('monthly_lead_assign_to_groups')->where('group_id',$group_id)->where('category_id',$category_id)
                    ->where('source_id',$request->source)
                    ->where('month',$newMonthName)->where('financial_year',$data->financial_year)
                    ->update(['value'=>$next_month_data->value - $diff]);


                 }
                if($value>$request->lead){
                    $diff =  $value - $request->lead;
                    $month = $data->month;
                    $date = Carbon::createFromFormat('F', $month);
                    $date->addMonth();
                    $newMonthName =   $date->format('F');
                    $next_month_data =  DB::table('monthly_lead_assign_to_groups')->where('group_id',$group_id)
                    ->where('category_id',$category_id)->where('source_id',$request->source)
                    ->where('month',$newMonthName)->where('financial_year',$data->financial_year)->first();
                     DB::table('monthly_lead_assign_to_groups')->where('group_id',$group_id)->where('category_id',$category_id)
                    ->where('source_id',$request->source)
                    ->where('month',$newMonthName)->where('financial_year',$data->financial_year)
                     ->update(['value'=>$next_month_data->value + $diff]);

                }
                // $dept_id = DB::table('kra_and_kpi_attribute')->where('id',$request->attribute)->first();
                DB::table('monthly_lead_assign_to_groups')->where('id',$id)->where('group_id',$group_id)->where('category_id',$category_id)->
                update(['value'=>$request->lead]);
                return response()->json(['status'=>200,'message'=>'Data Updated Successfully']);



            }
           public function calculate_data(Request $request){
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
    $data = json_decode($request->valuesdata);
    $remainingLeadsData = [];

    foreach($data as $row){
        if(isset($row->sum) && isset($row->cat_id)){
            $exists = DB::table('save_assign_leads_to_groups')
                     ->where('category_id', $row->cat_id)
                     ->where('source_id',$row->id)
                     ->where('financial_year',$financialYear)
                     ->sum('value');

            $sum = DB::table('save_company_target')
                      ->where('category_id', $row->cat_id)
                      ->where('child_attribute_id', $row->id)
                      ->where('financial_year',$financialYear)
                      ->sum('child_attribute_value');
                      if($exists){
                        //return 'ji';
                        $remainingLeads = $sum - $exists;
                     }
                     else{
                        $remainingLeads = $sum - $row->sum;

                     }

            // Add remaining leads to the response data array
            $remainingLeadsData[] = [
                'id' => $row->id,
                'cat_id' => $row->cat_id,
                'remainingLeads' => $remainingLeads
            ];
        }
    }

    // Return the array of remaining leads data
    return response()->json([
        'status' => 200,
        'data' => $remainingLeadsData
    ]);
}
public function count_assign_lead($id,$group,$category,$source){
    $data = DB::table('monthly_lead_assign_to_groups')->where('id',$id)->where('group_id',$group)->where('category_id',$category)->sum('value');
    $assign_data = DB::table('assign_target')->where('group_id',$group)->where('category_id',$category)->where('source_id',$source)->where('is_sales_emp',0)->sum('assign_data');
   // $assign_sales_data = DB::table('assign_target')->where('group_id',$group)->where('category_id',$category)->where('source_id',$source)->where('is_sales_emp',1)->sum('assign_data');
 
    if($assign_data){
        $remaining = $data - $assign_data;
       // $remaining_sales = $data - $assign_sales_data;
     }
     else{
       $remaining = $data;
     //  $remaining_sales = $data;
 
 
     }
      return response()->json(['status'=>200,'data'=>$remaining]);
 
 
    }
   public function get_department_based_target(Request $request){
   // return 'kk';
               $target_leads = [];
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
      $reporting_manager = DB::table('emp_basic_info')->where('reporting_manager',$request->emp_id)->first();
           //return $reporting_manager;
     if($reporting_manager){
      //return 'kk';
        $emp_details = DB::table('emp_basic_info')->where('emp_id', $reporting_manager->reporting_manager)->first();
         $target_data = DB::table('save_company_target')->whereNull('category_id')->where('department_id',$emp_details->dept_id)->select('product_id','attribute_id','subattribute_id','goal_id','financial_year')->groupBy('product_id','attribute_id','subattribute_id','goal_id','financial_year');

         $filter = DB::table('save_company_target')->whereNull('category_id')->where('department_id',$emp_details->dept_id)->select('product_id','attribute_id','subattribute_id','goal_id','financial_year')->groupBy('product_id','attribute_id','subattribute_id','goal_id','financial_year');

         if($request->goal){
          $target_data->where('goal_id',$request->goal);
          $filter->where('goal_id',$request->goal);
         }
          if($request->subattribute){
          $target_data->where('attribute_id',$request->subattribute);
          $filter->where('goal_id',$request->goal);
         }
          if($request->subsubattribute){
          $target_data->where('subattribute_id',$request->subsubattribute);
          $filter->where('goal_id',$request->goal);
          }
          if($request->childattribute){
             $list  = $target_data->paginate($request->per_page);
             foreach($list as $item){
               $goal = DB::table('company_goals')->where('id',$item->goal_id)->first();
               $attribute = DB::table('target_attribute')->where('id',$item->attribute_id)->first();
               $subattribute = DB::table('target_subattribute')->where('id',$item->subattribute_id)->first();
               $filter_item = DB::table('sub_sub_attribute')->whereIn('id', $request->childattribute)->get(['name','id']);
               $filter_value = DB::table('save_company_target')->where('goal_id',$item->goal_id)->where('product_id',$item->product_id)->where('attribute_id',$item->attribute_id)->where('subattribute_id',$item->subattribute_id)->whereIn('child_attribute_id',$request->childattribute)->pluck('child_attribute_value','child_attribute_id',);
                $target_leads[] = array('goal'=>$goal->attribute_name,'attribute'=>$attribute->attribute_name,'subattribute'=>$subattribute->subattribute_name,'product'=>$item->product_id,'child_attribute'=>$filter_item,'child_value'=>$filter_value,'year'=>$item->financial_year);

                 }
                  }
              if(!$request->childattribute){

                $list = $target_data->paginate($request->per_page);
              
             foreach($list as $row){
                  $goal = DB::table('company_goals')->where('id',$row->goal_id)->first();
                  $attribute = DB::table('target_attribute')->where('id',$row->attribute_id)->first();
            $subattribute = DB::table('target_subattribute')->where('id',$row->subattribute_id)->first();
             $target_leads[] = array('goal'=>$goal->attribute_name,'attribute'=>$attribute->attribute_name,
             'subattribute'=>$subattribute->subattribute_name,'product'=>$row->product_id,
             'year'=>$row->financial_year);

            }


              }
            }

            return response()->json(['status'=>200,'data'=>$target_leads,'last_page'=>$list->lastPage()]);



   }
   public function get_product(){
    $data = DB::table('product')->get(['id','product_name']);
    return response()->json(['status'=>200,'data'=>$data]);
   }

   public function get_target_saved_data(Request $request){
    $data_list = [];
    $data = DB::table('save_company_target')->select('product_id','department_id','goal_id','attribute_id','subattribute_id','financial_year')
    ->groupBy('product_id','department_id','goal_id','attribute_id','subattribute_id','financial_year');
    $attr_data =  DB::table('save_company_target')->select('product_id','department_id','goal_id','attribute_id','subattribute_id','financial_year')
    ->groupBy('product_id','department_id','goal_id','attribute_id','subattribute_id','financial_year');

    if($request->goal){
        $data->where('goal_id',$request->goal);
        $attr_data->where('goal_id',$request->goal);
       }
        if($request->subattribute){
        $data->where('attribute_id',$request->subattribute);
        $attr_data->where('attribute_id',$request->subattribute);
       }
        if($request->subsubattribute){
        $data->where('subattribute_id',$request->subsubattribute);
        $attr_data->where('subattribute_id',$request->subsubattribute);
        }
        if($request->department){
            $data->where('department_id',$request->department);
            $attr_data->where('department_id',$request->department);
        }
        if($request->financial_year){
            $data->where('financial_year',$request->financial_year);
            $attr_data->where('financial_year',$request->financial_year);
        }
        if($request->product){
            $data->where('product_id',$request->product);
            $attr_data->where('product_id',$request->product);
        }
          if($request->childattribute && !$request->category){
    $list =  $attr_data->paginate($request->per_page);
    $data_list = [];

    foreach($list as $att) {
        $department = DB::table('department')->where('id', $att->department_id)->first();
        $goal = DB::table('company_goals')->where('id', $att->goal_id)->first();
        $attribute = DB::table('target_attribute')->where('id', $att->attribute_id)->first();
        $subattribute = DB::table('target_subattribute')->where('id', $att->subattribute_id)->first();
        $product = DB::table('product')->where('id', $att->product_id)->first();

        $filter_item = DB::table('sub_sub_attribute')->whereIn('id', $request->childattribute)->get(['name', 'id']);
        
        $filter_value = DB::table('save_company_target')
            ->where('department_id', $att->department_id)
            ->where('goal_id', $att->goal_id)
            ->where('product_id', $att->product_id)
            ->where('attribute_id', $att->attribute_id)
            ->where('subattribute_id', $att->subattribute_id)
            ->whereIn('child_attribute_id', $request->childattribute)
            ->pluck('child_attribute_value', 'child_attribute_id');
        
        $data_list[] = [
            'goal' => $goal->attribute_name,
            'attribute' => $attribute->attribute_name,
            'subattribute' => $subattribute->subattribute_name,
            'product' => $product->product_name,
            'year' => $att->financial_year,
            'department' => $department->department_name,
            'attribute_ids' => $filter_item, // Corrected key-value pair
            'attribute_values' => $filter_value
        ];
    }
}

if($request->childattribute && $request->category){
    $list =  $attr_data->paginate($request->per_page);
    $data_list = [];

    foreach($list as $att) {
        $department = DB::table('department')->where('id', $att->department_id)->first();
        $goal = DB::table('company_goals')->where('id', $att->goal_id)->first();
        $attribute = DB::table('target_attribute')->where('id', $att->attribute_id)->first();
        $subattribute = DB::table('target_subattribute')->where('id', $att->subattribute_id)->first();
        $product = DB::table('product')->where('id', $att->product_id)->first();
        $category = DB::table('product_service')->where('id',$request->category)->first();

        $filter_item = DB::table('sub_sub_attribute')->whereIn('id', $request->childattribute)->get(['name', 'id']);
        
        $filter_value = DB::table('save_company_target')
            ->where('department_id', $att->department_id)
            ->where('category_id',$request->category)
            ->where('goal_id', $att->goal_id)
            ->where('product_id', $att->product_id)
            ->where('attribute_id', $att->attribute_id)
            ->where('subattribute_id', $att->subattribute_id)
            ->whereIn('child_attribute_id', $request->childattribute)
            ->pluck('child_attribute_value', 'child_attribute_id');
        
        $data_list[] = [
            'goal' => $goal->attribute_name,
            'attribute' => $attribute->attribute_name,
            'subattribute' => $subattribute->subattribute_name,
            'product' => $product->product_name,
            'year' => $att->financial_year,
            'department' => $department->department_name,
            'attribute_ids' => $filter_item, // Corrected key-value pair
            'attribute_values' => $filter_value,
            'category'=>$category->service_name,
        ];
    }

}


        if(!$request->childattribute){
         $list = $data->paginate($request->per_page);
        foreach($list as $row){
        $department = DB::table('department')->where('id',$row->department_id)->first();
        $goal = DB::table('company_goals')->where('id',$row->goal_id)->first();
        $attribute = DB::table('target_attribute')->where('id',$row->attribute_id)->first();
        $subattribute = DB::table('target_subattribute')->where('id',$row->subattribute_id)->first();
        $product = DB::table('product')->where('id',$row->product_id)->first();
        $data_list[] = array('goal'=>$goal->attribute_name,'attribute'=>$attribute->attribute_name,
        'subattribute'=>$subattribute->subattribute_name,
        'product'=>$product->product_name,'year'=>$row->financial_year,'department'=>$department->department_name);
       }

        }
      return response()->json(['status'=>200,'data'=>$data_list,'last_page'=>$list->lastPage()]);

   }
   public function product_service(){
    $data = DB::table('product_service')->get(['id','service_name']);
    return response()->json(['status'=>200,'data'=>$data]);
   }
  public function get_category_details($id) {
    $list = [];

    // Fetch subattributes with IDs 1 and 2 (assuming these are the IDs of the subattributes)
    $subattributes = DB::table('sub_sub_attribute')
        ->whereIn('id', [1, 2])
        ->get(['id as subattribute_id', 'name']);

    // Fetch all categories
    $categories = DB::table('product_service')->where('product_id',$id)->get(['id', 'service_name']);

    foreach($categories as $category) {
        $list[] = [
            'category' => $category,
            'subattributes' => $subattributes->toArray(), // Assign the same subattributes to each category
        ];
    }

    return response()->json(['status' => 200, 'data' => $list]);
}
public function get_sales_lead(Request $request){
    $lead_data = [];
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
               $current_month = Carbon::now()->format('F');
$data = BasicInfo::where('emp_id',$request->id)->first();
if($data){
 if($data->reporting_manager =='RIMS1' || $data->reporting_manager=='0' ){
   if($data->assigned_group!=''){
       $group_id = explode(',',$data->assigned_group);
       $monthly_data_assign = DB::table('monthly_lead_assign_to_groups')->select('category_id',
            'group_id',
            'financial_year',
            'month',
            DB::raw('SUM(value) as total_value'))->groupBy('category_id', 'group_id', 'financial_year', 'month',)->whereIn('group_id', $group_id);
       if ($request->category) {
           $monthly_data_assign->where('category_id', $request->category);
       }
       if ($request->group) {
           $monthly_data_assign->where('group_id', $request->group);
       }
       if($request->financial_year && $request->month){
           // return 'kkk';
             $monthly_data_assign->where('financial_year',$request->financial_year)->where('month',$request->month);
             $queryResult =$monthly_data_assign->orderBy('id','DESC')->paginate($request->per_page);

        }
        if(!$request->financial_year || !$request->month){
            $queryResult = $monthly_data_assign->where('financial_year',$financialYear)->where('month',$current_month)->orderBy('id','DESC')
            ->paginate($request->per_page);

        }
        if($request->additional){
           $monthly_data_assign->select(
            'category_id',
            'group_id',
            'financial_year',
            'month',
            'id',
             DB::raw('SUM(value) as total_value')
             )->groupBy('category_id', 'group_id', 'financial_year', 'month','id');

             $queryResult = $monthly_data_assign->first();
            if ($queryResult) {
 $sum = DB::table('monthly_lead_assign_to_groups')->where('category_id',$queryResult->category_id)->where('group_id',$queryResult->group_id)->where('month',$queryResult->month)->where('financial_year',$queryResult->financial_year)->sum('value');
 $group = DB::table('group_names')->where('group_id', $queryResult->group_id)->first();
 $category = DB::table('product_service')->where('id', $queryResult->category_id)->first();
 $total_data[] = [
     'group' => $group->name,
     'category' => $category->service_name,
     'total_value' => $queryResult->total_value,
     'month' => $queryResult->month,
     'financial_year' => $queryResult->financial_year,
     'group_id' => $queryResult->group_id,
     'category_id' => $queryResult->category_id,
 ];

 return response()->json(['status' => 200, 'data' => $total_data,'sum'=>$sum]);
}
}
else{
       foreach($queryResult as $row){
           $group = DB::table('group_names')->where('group_id',$row->group_id)->first();
           $category = DB::table('product_service')->where('id',$row->category_id)->first();
           //$source = DB::table('sub_sub_attribute')->where('id',$row->source_id)->first();
           $lead_data[] = array('group'=>$group->name,'category'=>$category->service_name,
            'value'=>$row->total_value,'month'=>$row->month,
           'financial_year'=>$row->financial_year,'group_id'=>$row->group_id,'category_id'=>$row->category_id);


       }
       return response()->json(['status'=>200,'data'=>$lead_data,'last_page'=>$queryResult->lastPage()]);
       
     }
   }
   else{
       return response()->json(['status'=>200,'data'=>$lead_data]);

   }

}
else{
   return response()->json(['status'=>200,'data'=>$lead_data]);


}
}
else{
return response()->json(['status'=>200,'data'=>$lead_data]);

}



}
public function get_group_employee(Request $request){
    $emp_list = [];
    if($request->assigngroup){
      $data = DB::table('emp_basic_info')
      ->whereRaw('FIND_IN_SET(?, assigned_group)', [$request->assigngroup])
      ->get();
   foreach($data as $row){
    $emp_list[] = array('id'=>$row->id,'emp_id'=>$row->emp_id,'emp_fname'=>$row->emp_fname);
   }
   return response()->json(['status'=>200,'data'=>$emp_list]);
}
else{
    return response()->json(['status'=>200,'data'=>$emp_list]);

}
}
public function show_group_saved_data(){
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
    $data = DB::table('save_assign_leads_to_groups')->where('financial_year',$financialYear,)->get(['group_id','category_id','source_id','value']);
    return response()->json(['status'=>200,'data'=>$data]);


}
public function count_sales_lead_data($group,$category,$month,$year){
    $data = DB::table('monthly_lead_assign_to_groups')->where('group_id',$group)->where('category_id',$category)->where('month',$month)->where('financial_year',$year)->sum('value');
    //$assign_data = DB::table('assign_target')->where('group_id',$group)->where('category_id',$category)->where('month',$month)->where('financial_year',$year)->where('is_sales_emp',1)->sum('assign_data');
   $assign_sales_data = DB::table('assign_target')->where('group_id',$group)->where('category_id',$category)->where('month',$month)->where('financial_year',$year)->where('is_sales_emp',1)->sum('assign_data');
 
    if($assign_sales_data){
        //$remaining = $data - $assign_data;
       $remaining_sales = $data - $assign_sales_data;
     }
     else{
      // $remaining = $data;
     $remaining_sales = $data;
 
 
     }
      return response()->json(['status'=>200,'sales_data'=>$remaining_sales]);
 
 }
 public function emp_data(Request $request){
    $data = BasicInfo::whereIn('emp_id',$request->emp_ids)->get(['emp_id','emp_fname']);
    return response()->json(['status'=>200,'data'=>$data]);
  
  }
  public function employee_group_list(){
    $details = [];
    $data = DB::table('employee_assign_groups')->get(['id','emp_id','group_id','product_id']);
    foreach($data as $row){
        $groups = explode(',', $row->group_id);
        $group = DB::table('group_names')
        ->whereIn('group_id',$groups)
        ->pluck('name')
        ->implode(',');
        $emp_name = BasicInfo::where('emp_id',$row->emp_id)->first();
        $product = DB::table('product')->where('id',$row->product_id)->first();
        $details[] = array('id'=>$row->id,'group'=>$group,
        'product'=>$product->product_name,'emp'=>$emp_name->emp_fname);

    }
    return response()->json(['status'=>200,'data'=>$details]);

  }
  public function get_active_groups($id){
    $data = DB::table('employee_assign_groups')->where('product_id',$id)->get();
    $groups_list = []; 

    foreach($data as $row){
        $groups = explode(',', $row->group_id);
        $groups_list = array_merge($groups_list, $groups);
    }

    $active_groups = DB::table('group_names')->whereNotIn('group_id', $groups_list)->get(['group_id','name']);
    
    return response()->json(['status'=>200,'data'=> $active_groups]);
}
public function save_assign_group(Request $request){
    //return $request->group_id;
                $input = $request->all();
   
    $validator = Validator::make($input, [
                'product' => 'required',
                'employee'=>'required',
                    ]);
    if($validator->fails()){
        $messages=$validator->messages();
        return response()->json(["messages"=>$messages,'status'=>400]);     
    }
   // $group = implode(',',$request->group_id);
    $data = array('product_id'=>$request->product,
    'emp_id'=>$request->employee,'group_id'=>$request->group_id,'created_by'=>$request->emp_id);
    DB::table('employee_assign_groups')->insert($data);
    return response()->json(['status'=>200,'message'=>'Group Assign Successfully']);



}



}