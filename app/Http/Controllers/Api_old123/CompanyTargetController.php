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

    $data = DB::connection('sales_db')->table('group_names')->get(['group_id','name']);

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

    

    public function save_assign_target_for_sales(Request $request){
        $category = json_decode($request->category);
       // return $category;
        $data_array = [];
        foreach($category as $row){
            if($row->no_of_leads!='' || $row->no_of_packages!='' ){
            $lead_base_price = DB::connection('sales_db')->table('lead_base_package')->where('category_id',$row->category_id)->first();
  
            if($lead_base_price){
              $total_target_amount = $request->no_of_leads * $lead_base_price->base_price;
            }
            else{
               $total_target_amount = 0;

            }
            $emp_department = BasicInfo::where('emp_id',$request->employee)->first();

            $data_array[] = array('group_id'=>$row->group_id,'category_id'=>$row->category_id,'product_id'=>$request->product,
            'emp_id'=>$request->employee,'department_id'=>$emp_department->dept_id,
            'assign_data'=>$row->no_of_leads,
             'month'=>$request->month,'financial_year'=>$request->year,'remark'=>$request->remark,'created_by'=>$request->emp_id,
              'total_target_amount'=>$total_target_amount,'target_mode'=>$request->target_mode,
              'service_id'=>$request->service,'no_of_packages'=>$row->no_of_packages);
            }

            }
             DB::table('assign_target')->insert($data_array);
  
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

            $financial_year = $request->financial_year;



            $data = DB::table('assign_target')->where('created_by',$request->emp_id)->where('financial_year',$request->financial_year)->orderBy('id','DESC')->get();

            

        }

        else{

            $financial_year = $financialYear;

            $data = DB::table('assign_target')->where('created_by',$request->emp_id)->where('financial_year', $financialYear)->orderBy('id','DESC')->get();

          }

        $target_details = [];

        foreach($data as $row){

            if ($row->source_id == 1) {

                $source_from = 'Website';

            } elseif ($row->source_id == 2) {

                $source_from = 'Adword';

            }

            else{

                 $source_from = '';

                

            }



           [$startYear, $endYear] = explode('-', $financialYear);

           $start = Carbon::createFromDate($startYear, 4, 1);

           $end = Carbon::createFromDate($endYear, 3, 31)->endOfDay();

           $monthNumber = date('m', strtotime($row->month . ' 1'));



            $emp_id = BasicInfo::where('emp_id',$row->emp_id)->first();

            $group = DB::connection('sales_db')->table('group_names')->where('group_id',$row->group_id)->first();

            $category = DB::connection('sales_db')->table('product_category')->where('id',$row->category_id)->first();

            $source = DB::table('target_child_attribute')->where('id',$row->source_id)->first();

            $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first(); 



            $total_number_of_acheived = DB::connection('sales_db')->table('enquiry_info')

            ->where('group_id', $row->group_id)  

            ->where('category_id',$row->category_id)

            ->whereRaw("MONTH(created_date) = ?", [$monthNumber])

            ->where('source_type',$source_from)

            ->whereBetween('created_date', [$start, $end])

            ->count();



        $target_details[] = array('id'=>$row->id,'emp_fname'=>$emp_id->emp_fname,'emp_lname'=>$emp_id->emp_lame,

        'group'=>$group->name??'','source'=>$source->name ??'',

         'category'=>$category->category_name??'','remark'=>$row->remark,

         'value'=>$row->assign_data,'month'=>$row->month,'financial_year'=>$row->financial_year,

         'product'=>$product->product_name ??'','acheived'=>$total_number_of_acheived);

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

        $target_details = [];

        foreach($data as $row){

            $emp_id = BasicInfo::where('emp_id',$row->emp_id)->first();

           $group = DB::connection('sales_db')->table('group_names')->where('group_id',$row->group_id)->first();

           $category = DB::connection('sales_db')->table('product_category')->where('id',$row->category_id)->first();

           

           $source = DB::table('target_child_attribute')->where('id',$row->source_id)->first();



        $target_details[] = array('id'=>$row->id,'emp'=>$emp_id->emp_fname.' '.$emp_id->emp_lame,'group'=>$group->name??'',

        'category'=>$category->category_name??'','remark'=>$row->remark??'','value'=>$row->assign_data??'','month'=>$row->month??'','financial_year'=>$row->financial_year??'','source'=>$source->name??"");

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

            'attribute_id'=>$request->attribute,'department_id'=>$department,'parent_id'=>$request->followup_id);

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

                'attribute_id'=>$request->attribute,'department_id'=>$department,'parent_id'=>$request->followup_id]);

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

             DB::table('target_child_attribute')->insert($data);

             return response()->json(['status'=>200,'message'=>'Attribute Created Successfully']);

           }

      

           public function goal_sub_sub_attribute_list(Request $request){

              $subattribute_details = [];

              $data = DB::table('target_child_attribute')->paginate($request->per_page);

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

              $data = DB::table('target_child_attribute')->where('id',$id)->first();

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

                DB::table('target_child_attribute')->where('id',$id)->

                update(['name'=>$request->name,

                'subattribute_id'=>$request->attribute,'department_id'=>$department]);

                return response()->json(['status'=>200,'message'=>'Attribute Updated Successfully']);

      

             }

      

            public function get_sub_sub_attribute_status($id){

              $data = DB::table('target_child_attribute')->where('id',$id)->first();

              return response()->json(['status'=>200,'message'=>'Attribute Status','data'=>$data->status]);

              }

          public function sub_sub_attribute_status_update(Request $request){

              $request->validate([

                  'subattribute_id' => 'required',

                  'status' => 'required|in:0,1', 

              ]);

              //return $request->attribute_id;

              DB::table('target_child_attribute')->where('id',$request->subattribute_id)->update(['status'=>$request->status]);

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

              $data = DB::table('target_child_attribute')->where('subattribute_id',$id)->whereRaw("FIND_IN_SET(?, department_id)", [$dept_id])->get(['id','name']);



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

                    $data = DB::table('target_child_attribute')->where('id',$row->subattribute_id)->first();

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

                $data = \DB::table("target_child_attribute")

                    ->select("target_child_attribute.id","target_child_attribute.name")

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

        //     $data = \DB::table("target_child_attribute")

        //         ->select("target_child_attribute.id","target_child_attribute.name")

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
         //return $request->all();
     
         $input = $request->all();
         $validator = Validator::make($input, [
             'department' => 'required',
             'financial_year' => 'required',
             'goal' => 'required',
             'attribute' => 'required',
             'sub_attribute' => 'required',
             'product' => 'required',
             'inputValues' => 'required',
             'dataarray' => 'required',
         ]);
     
         if ($validator->fails()) {
             return response()->json(['errors' => $validator->errors()->all(), 'status' => 400]);
         }
     
         $value_array = [];
         $data = json_decode($request->input('inputValues'));
         $cat_data = json_decode($request->input('dataarray'));
     
         if ($request->target_type == 2) {
             foreach ($data as $row) {
                 $is_monthly_assign = $request->is_monthly_assign ? 1 : 0;
                 if (!empty($row->value)) {
                     $value_array[] = [
                         'department_id' => $request->department,
                         'attribute_id' => $request->attribute,
                         'subattribute_id' => $request->sub_attribute,
                         'child_attribute_id' => $row->id,
                         'child_attribute_value' => $row->value,
                         'financial_year' => $request->financial_year,
                         'created_by' => $request->emp_id,
                         'product_id' => $request->product,
                         'goal_id' => $request->goal,
                         'group_id' => $request->group_id,
                         'target_type' => $request->target_type,
                         'is_monthly_devide' => $is_monthly_assign,
                     ];
                 }
             }
     
             if (count($value_array) > 0) {
                 DB::table('save_company_target')->insert($value_array);
             }
     
         }
           elseif($request->target_type == 1) {
             $sum = 0;
             foreach ($cat_data as $ct) {
                 if ($ct->finalvalue === null) {
                     $val = $ct->value;
                     foreach ($cat_data as $inner_item) {
                         if ($ct->id === $inner_item->id && $inner_item->finalvalue !== null) {
                             $result = ($val * $inner_item->e_data) / 100;
                             $sum += $result;
     
                       $lead_base_price = DB::connection('sales_db')->table('lead_base_package')->where('category_id',$inner_item->data)->first();
                         if($lead_base_price){
                           $total_amount = $lead_base_price->base_price *  $result;
                         }
                         else{
                            $total_amount = 0;
     
                         }
     
                             $value_array[] = [
                                 'department_id' => $request->department,
                                 'attribute_id' => $request->attribute,
                                 'subattribute_id' => $request->sub_attribute,
                                 'child_attribute_id' => $inner_item->finalvalue,
                                 'child_attribute_value' => $result,
                                 'financial_year' => $request->financial_year,
                                 'created_by' => $request->emp_id,
                                 'product_id' => $request->product,
                                 'goal_id' => $request->goal,
                                 'service_id'=>$request->service,
                                 'category_id' => $inner_item->data,
                                 'remaining_value' => $result,
                                 'target_type' => $request->target_type,
                                 'no_of_leads' => $request->total,
                                 'total_target_amount'=>$total_amount,
                             ];
                         }
                     }
                 }
             }
             if ($sum < $request->total || $sum > $request->total ) {
                 return response()->json(['message' => 'Please enter correct percentage here', 'sum' => $sum]);
             }
             foreach ($value_array as $record) {
                 DB::table('save_company_target')->updateOrInsert(
                     [
                         'department_id' => $record['department_id'],
                         'attribute_id' => $record['attribute_id'],
                         'subattribute_id' => $record['subattribute_id'],
                         'child_attribute_id' => $record['child_attribute_id'],
                         'financial_year' => $record['financial_year'],
                         'goal_id' => $record['goal_id'],
                         'product_id' => $record['product_id'],
                         'target_type' => $record['target_type'],
                         'category_id' => $record['category_id'] ?? null,
                         'service_id'=>$record['service_id'] ?? null,
                     ],
                     [
                         'child_attribute_value' => $record['child_attribute_value'],
                         'created_by' => $record['created_by'],
                         'is_monthly_devide' => $record['is_monthly_devide'] ?? null,
                         'remaining_value' => $record['remaining_value'] ?? null,
                         'no_of_leads' => $record['no_of_leads'] ?? null,
                         'total_target_amount'=> $record['total_target_amount'] ?? null,
                     ]
                 );
             }
         }
         else {
            $value_array = [];
            $monthly_value_array = [];
            $months = [
                'April', 'May', 'June', 'July', 'August', 'September',
                'October', 'November', 'December', 'January', 'February', 'March'
            ];
        
            foreach (json_decode($request->data_obj) as $obj) {
                if ($obj->value != '') {
                    // Prepare single record for save_company_target
                    $record = [
                        'department_id' => $request->department,
                        'attribute_id' => $request->attribute,
                        'subattribute_id' => $request->sub_attribute,
                        'child_attribute_value' => $obj->value,
                        'financial_year' => $request->financial_year,
                        'created_by' => $request->emp_id,
                        'product_id' => $request->product,
                        'goal_id' => $request->goal,
                        'service_id' => $request->service,
                        'category_id' => $obj->id,
                        'target_type' => $request->target_type,
                        'group_id' => $request->group_id,
                    ];
        
                    // Insert into save_company_target and get target_id
                    $target_id = DB::table('save_company_target')->insertGetId($record);
        
                    // Distribute target value across months and prepare data for monthly_target_data
                    $monthly_value = $obj->value / count($months);
        
                    foreach ($months as $month) {
                        $monthly_value_array[] = [
                            'target_id' => $target_id,
                            'financial_year' => $request->financial_year,
                            'created_by' => $request->emp_id,
                            'product_id' => $request->product,
                            'service_id' => $request->service,
                            'category_id' => $obj->id,
                            'target_type' => $request->target_type,
                            'group_id' => $request->group_id,
                            'month' => $month,
                            'no_of_monthly_lead' => $monthly_value,
                            'after_edited_no_of_lead' => $monthly_value,
                        ];
                    }
                }
            }
            }
        
            // Insert all monthly data at once
            if (!empty($monthly_value_array)) {
                DB::table('monthly_lead_assign_to_groups')->insert($monthly_value_array);
            }
     
             return response()->json(['status' => 200, 'message' => 'Target Created/Updated Successfully']);
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

                    $total_data  = DB::table('save_company_target')->where('category_id',$id)

                    ->where('department_id',10)->where('financial_year',$financial_year)

                    ->sum('remaining_value');

                  $data =  DB::table('save_company_target')->where('category_id',$id)

                  ->where('department_id',10)->where('financial_year',$financial_year)->get();

                  foreach($data as $row){

                    $sub_attribute = DB::table('target_child_attribute')->where('id',$row->child_attribute_id)->first();

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
public function assign_to_groups($id, $group_id, $f_year)
{
    $grp_data = [];
    $group_ids_list = explode(',', $group_id);
    $unique_combinations = []; 
    $groups = DB::connection('sales_db')->table('group_names')
        ->whereIn('group_id', $group_ids_list)
        ->get(['group_id', 'name']);

    foreach ($groups as $grp) {
        $cat = DB::table('save_company_target')
            ->where('save_company_target.financial_year', $f_year)
            ->where('service_id', $id)
            ->get();

        foreach ($cat as $ct) {
            $category_details = DB::connection('sales_db')->table('product_category')
                ->where('id', $ct->category_id)
                ->first();

            $sub_cat = DB::table('target_child_attribute')->where('id',$ct->child_attribute_id)->first();

            $total_leads = DB::table('save_company_target')
                ->where('category_id', $ct->category_id)
                ->where('department_id', 10)
                ->where('financial_year', $f_year)
                ->sum('remaining_value');

                $total_source_leads = DB::table('save_company_target')
                    ->where('category_id', $ct->category_id)
                    ->where('child_attribute_id', $sub_cat->id)
                    ->where('department_id', 10)
                    ->where('financial_year', $f_year)
                    ->sum('child_attribute_value');

                $grp_data[] = [
                        'id' => $sub_cat->id, 
                        'name' => $sub_cat->name, 
                        'group_id' => $grp->group_id,
                        'cat_id' => $category_details->id,
                        'target_id' => $ct->id,
                        'category_name' => $category_details->category_name,
                        'group_name' => $grp->name,
                        'total_leads' => $total_leads,
                        'source_leads' => $total_source_leads
                    ];
                }
    }
    return response()->json(['status' => 200, 'data' => $grp_data]);
}







public function save_assign_leads_to_groups(Request $request) {

    $input = $request->all();
    //return  $input;

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

          $target_row = DB::table('save_company_target')->where('id',$row->target_id)->first();

          $remaining_leads = DB::table('save_assign_leads_to_groups')->where('category_id',$row->catId)->where('group_id',$row->finalValue)->where('source_id',$row->id)->where('target_id',$row->target_id)->first();

          if(!$remaining_leads){

             $leads_data =  DB::table('save_company_target')->where('category_id',$row->catId)->where('child_attribute_id',$row->id)->where('id',$row->target_id)->first();

              $leads =   DB::table('save_company_target')->where('category_id',$row->catId)->where('child_attribute_id',$row->id)->where('id',$row->target_id);

              if($leads_data->remaining_value>=$row->value){
                 $leads->update(['remaining_value'=>$leads_data->remaining_value - $row->value]);

              }

              else{
                return response()->json(['status'=>201,'message'=>'insufficient data']);

              }

           }

           else{

            if($remaining_leads->no_of_leads>$row->value){

              $diff = $remaining_leads->no_of_leads - $row->value;

               $leads_data =  DB::table('save_company_target')->where('category_id',$row->catId)->where('child_attribute_id',$row->id)->where('id',$row->target_id)->first();

                $leads =   DB::table('save_company_target')->where('category_id',$row->catId)->where('child_attribute_id',$row->id)
                ->where('id',$row->target_id);

              $leads->update(['remaining_value'=>$leads_data->remaining_value + $diff ]);
          }

            else{

                $diff =  $row->value - $remaining_leads->no_of_leads;

                 $leads_data =  DB::table('save_company_target')->where('category_id',$row->catId)->where('child_attribute_id',$row->id)->where('id',$row->target_id)->first();

                $leads =   DB::table('save_company_target')->where('category_id',$row->catId)->where('child_attribute_id',$row->id)
                ->where('id',$row->target_id);

                 $leads->update(['remaining_value'=>$leads_data->remaining_value - $diff ]);
               }

           }

           $lead_base_price = DB::connection('sales_db')->table('lead_base_package')->where('category_id',$row->catId)->first();
           if($lead_base_price){
            $total_amount = $row->value * $lead_base_price->base_price;

           }
           else{
             $total_amount = 0;

           }

            $data_array[] = [

                'group_id' => $row->finalValue,

                'category_id' => $row->catId,

                'source_id' => $row->id,

                'created_by' => $request->emp_id,

                'financial_year' => $request->financial_year,

                'no_of_leads' => $row->value,

                'target_id'=> $row->target_id,

                'service_id' => $target_row->service_id,

                'product_id' => $target_row->product_id,
                'total_target_amount'=>$total_amount,

            ];

        }

    }



    foreach ($months as $month) {

        foreach ($data as $row) {
           $lead_base_price = DB::connection('sales_db')->table('lead_base_package')->where('category_id',$row->catId)->first();

           if($lead_base_price){
              $total_leads = round($row->value / 12);
              $total_amount =  $lead_base_price->base_price * $total_leads;

           }
           else{
             $total_amount = 0;

           }

            if ($row->value != "") {


                $month_grp_data[] = [

                    'group_id' => $row->finalValue,

                    'category_id' => $row->catId,

                    'source_id' => $row->id,

                    'no_of_monthly_lead' => $row->value / 12,

                    'after_edited_no_of_lead' => $row->value / 12,

                    'created_by' => $request->emp_id,

                    'financial_year' => $request->financial_year,

                    'month' => $month,

                    'target_id'=> $row->target_id,

                    'service_id' => $target_row->service_id,

                    'product_id' => $target_row->product_id,

                   'total_target_amount'=> $total_amount,

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

                    'target_id'=> $item['target_id'],

                    

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

                    'target_id'=> $item['target_id'],

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

                    if($request->product){
                        $query->where('product_id', $request->product);

                    }

                    if($request->financial_year && $request->month){

                        $f_year = $request->financial_year;

                        // return 'kkk';

                          $query->where('financial_year',$request->financial_year)->where('month',$request->month);

                          $queryResult = $query->orderBy('id','DESC')->paginate($request->per_page);

             

                     }

                     if(!$request->financial_year || !$request->month){

                          $f_year =  $financialYear;

                         $queryResult = $query->where('financial_year',$financialYear)->where('month',$current_month)->orderBy('id','DESC')->paginate($request->per_page);

             

                     }

                     foreach($queryResult as $row){

                        $group = DB::connection('sales_db')->table('group_names')->where('group_id',$row->group_id)->first();

                        $category = DB::connection('sales_db')->table('product_category')->where('id',$row->category_id)->first();

                        $source = DB::table('target_child_attribute')->where('id',$row->source_id)->first();

                        $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();

                        $service = DB::connection('sales_db')->table('product_service')->where('id',$row->service_id)->first();

                        if($row->source_id==2){

                            $source_name = 'Adword';
                        }

                        elseif($row->source_id==1){

                             $source_name = 'Website';

                        }

                         [$startYear, $endYear] = explode('-', $financialYear);

                         $start = Carbon::createFromDate($startYear, 4, 1);

                         $end = Carbon::createFromDate($endYear, 3, 31)->endOfDay(); 

                         $monthNumber = date('m', strtotime($row->month . ' 1'));

                         

                       $achieved = DB::connection('sales_db')

                        ->table('enquiry_info')

                        ->where('source_type', $source_name)

                         ->where('category_id',$row->category_id)
                         ->where('group_id',$row->group_id)

                         ->whereRaw("MONTH(created_date) = ?", [$monthNumber])

                         ->whereBetween('created_date', [$start, $end])

                        ->count();



                        $remaining = $row->after_edited_no_of_lead - $achieved;

                        if($remaining>0){

                            $remaining_leads =  $remaining;

                        }

                        else{

                             $remaining_leads = 0;

                        }
                       $total_data[] = array('id'=>$row->id,'group'=>$group->name,'category'=>$category->category_name??'',

                        'source'=>$source->name,'no_of_monthly_lead'=>$row->after_edited_no_of_lead,'month'=>$row->month,

                        

                        'financial_year'=>$row->financial_year,'group_id'=>$row->group_id,'category_id'=>$row->category_id,

                        'source_id'=>$row->source_id,'acheived'=>$achieved,'remaining'=>$remaining_leads,
                        'product'=>$product->product_name,'service'=>$service->service_name);



                    }

                    return response()->json(['status' => 200, 'data'=>$total_data,'last_page'=>$queryResult->lastPage()]);


            }

            public function lead_edit($id,$group,$category){

                $data = DB::table('monthly_lead_assign_to_groups')->where('id',$id)->where('group_id',$group)->where('category_id',$category)->first();

                return response()->json(['status' => 200, 'data'=>$data->after_edited_no_of_lead]);
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

                $value = $data->after_edited_no_of_lead;

                if($value<$request->lead){

                    $diff = $request->lead - $value;

                    $month = $data->month;

                    $date = Carbon::createFromFormat('F', $month);

                    $date->addMonth();

                    $newMonthName =   $date->format('F');

                    $next_month_data =  DB::table('monthly_lead_assign_to_groups')->where('group_id',$group_id)

                    ->where('category_id',$category_id)->where('source_id',$request->source)

                    ->where('month',$newMonthName)->where('financial_year',$data->financial_year)->first();

                    if($next_month_data){
                        DB::table('monthly_lead_assign_to_groups')->where('group_id',$group_id)->where('category_id',$category_id)

                        ->where('source_id',$request->source)
    
                        ->where('month',$newMonthName)->where('financial_year',$data->financial_year)
    
                        ->update(['after_edited_no_of_lead'=>$next_month_data->after_edited_no_of_lead - $diff]);

                    }
                    else{
                        return response()->json(['status'=>204,'message'=>'You Can Not Edit Data']);
                    }

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

                    if($next_month_data){
                        DB::table('monthly_lead_assign_to_groups')->where('group_id',$group_id)->where('category_id',$category_id)

                        ->where('source_id',$request->source)
    
                        ->where('month',$newMonthName)->where('financial_year',$data->financial_year)
    
                         ->update(['after_edited_no_of_lead'=>$next_month_data->after_edited_no_of_lead + $diff]);

                    }
                    else{
                        return response()->json(['status'=>204,'message'=>'You Can Not Edit Data']);
                    }



                }

                DB::table('monthly_lead_assign_to_groups')->where('id',$id)->where('group_id',$group_id)->where('category_id',$category_id)->

                update(['after_edited_no_of_lead'=>$request->lead]);

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

                     ->sum('no_of_leads');



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

            $remainingLeadsData[] = [

                'id' => $row->id,

                'cat_id' => $row->cat_id,

                'remainingLeads' => $remainingLeads

            ];

        }

    }



    return response()->json([

        'status' => 200,

        'data' => $remainingLeadsData

    ]);

}

public function count_assign_lead($id,$group,$category,$source){

    $data = DB::table('monthly_lead_assign_to_groups')->where('id',$id)->where('group_id',$group)->where('category_id',$category)->sum('after_edited_no_of_lead');

    $assign_data = DB::table('assign_target')->where('group_id',$group)->where('category_id',$category)->where('source_id',$source)->where('department_id',10)->sum('assign_data');

 

    if($assign_data){

        $remaining = $data - $assign_data;
        if($remaining>0){
            $remaining_data =  $remaining ;

        }
            else{
                $remaining_data = 0;


        }

     }

     else{

        $remaining_data = $data;

      }

      return response()->json(['status'=>200,'data'=>$remaining_data]);

 

 

    }

   public function get_department_based_target(Request $request){

                $data_array = [];

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



         $filter = DB::table('save_company_target')->where('target_type',2)->whereNull('category_id')->where('department_id',$request->dept_id);

         

         if($request->goal){

          $filter->where('goal_id',$request->goal);

         }

          if($request->subattribute){

          $filter->where('attribute_id',$request->goal);

         }

          if($request->subsubattribute){

          $filter->where('subattribute_id',$request->goal);

          }

        $data = $filter->paginate(10);

        foreach($data as $row){

            $per_month_numbers = '-';

            $per_day_numbers = '-';



            if($row->value_type == "Numbers" && $row->is_monthly_devide==1){

               // return $row;

                $per_month_numbers = round($row->child_attribute_value/12);

                $per_day_numbers  = round($per_month_numbers/22);

            }

        $goal = DB::table('company_goals')->where('id',$row->goal_id)->first();

        //return  $goal;

        $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();

        $attribute = DB::table('target_attribute')->where('id',$row->attribute_id)->first();

        $subattribute = DB::table('target_subattribute')->where('id',$row->subattribute_id)->first();

        $child_attribute = DB::table('target_child_attribute')->where('id',$row->child_attribute_id)->first();

        $data_array[] = array('id'=>$row->id,'goal'=>$goal->attribute_name,

        'product'=>$product->product_name,'attribute'=>$attribute->attribute_name,

        'subattribute'=>$subattribute->subattribute_name,'child_attribute'=>$child_attribute->name,

        'attribute_value'=>$row->child_attribute_value,'finanacial_year'=>$row->financial_year,

        'per_day'=>$per_day_numbers,'per_month'=>$per_month_numbers,'type'=>$row->value_type);



      }

      return response()->json(['status'=>200,'data'=>$data_array,'last_page'=>$data->lastPage()]);







   }

   public function get_product(){

    $data = DB::table('product')->get(['id','product_name']);

    return response()->json(['status'=>200,'data'=>$data]);

   }



   public function get_target_saved_data(Request $request){

    $data_list = [];

    $data = DB::table('save_company_target')->select('product_id','department_id','goal_id','attribute_id','subattribute_id','financial_year','group_id')

    ->groupBy('product_id','department_id','goal_id','attribute_id','subattribute_id','financial_year','group_id');

    $attr_data =  DB::table('save_company_target')->select('product_id','department_id','goal_id','attribute_id','subattribute_id','financial_year','group_id')

    ->groupBy('product_id','department_id','goal_id','attribute_id','subattribute_id','financial_year','group_id');



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

            $financialYear = $request->financial_year;

            $data->where('financial_year',$request->financial_year);

            $attr_data->where('financial_year',$request->financial_year);

        }

        else{

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

            $data->where('financial_year',$financialYear);

            $attr_data->where('financial_year',$financialYear);

            

        }

        if($request->product){

            $data->where('product_id',$request->product);

            $attr_data->where('product_id',$request->product);

        }

        if($request->group){

            $data->where('group_id',$request->group);

            $attr_data->where('group_id',$request->group);

        }

          if($request->childattribute){

    $list =  $attr_data->where('target_type',2)->paginate($request->per_page);

    $data_list = [];



    foreach($list as $att) {

        $department = DB::table('department')->where('id', $att->department_id)->first();

        $goal = DB::table('company_goals')->where('id', $att->goal_id)->first();

        $attribute = DB::table('target_attribute')->where('id', $att->attribute_id)->first();

        $subattribute = DB::table('target_subattribute')->where('id', $att->subattribute_id)->first();

        $product = DB::connection('sales_db')->table('product')->where('id', $att->product_id)->first();



        $filter_item = DB::table('target_child_attribute')->whereIn('id', $request->childattribute)->get(['name', 'id']);



        $group = DB::connection('sales_db')->table('group_names')->where('group_id', $att->group_id)->first();

        if($group){

          $group_names = $group->name;

          }

          else{

            $group_names = '';



          }



        

        $filter_value = DB::table('save_company_target')

            ->where('department_id', $att->department_id)

            ->where('goal_id', $att->goal_id)

            ->where('product_id', $att->product_id)

            ->where('attribute_id', $att->attribute_id)

            ->where('subattribute_id', $att->subattribute_id)

            ->where('group_id',$att->group_id)

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

            'group'=>$group_names,

        ];

    }

}



if($request->childattribute){

    $list =  $attr_data->where('target_type',2)->paginate($request->per_page);

    $data_list = [];



    foreach($list as $att) {

           [$startYear, $endYear] = explode('-', $financialYear);

           $start = Carbon::createFromDate($startYear, 4, 1);

           $end = Carbon::createFromDate($endYear, 3, 31)->endOfDay();

           

        $department = DB::table('department')->where('id', $att->department_id)->first();

        $goal = DB::table('company_goals')->where('id', $att->goal_id)->first();

        $attribute = DB::table('target_attribute')->where('id', $att->attribute_id)->first();

        $subattribute = DB::table('target_subattribute')->where('id', $att->subattribute_id)->first();

        $product = DB::connection('sales_db')->table('product')->where('id', $att->product_id)->first();

        $total_acheived = DB::connection('sales_db')->table('clients_followup_log')->where('followup_id',$subattribute->parent_id)->whereBetween('created_date', [$start, $end])->count();

        

        if($total_acheived){

            $acheived_count = $total_acheived;

            

        }

        else{

            $acheived_count = 0;

            

        }

        

        $category = DB::connection('sales_db')->table('product_category')->where('id',$request->category)->first();

          $group = DB::connection('sales_db')->table('group_names')->where('group_id', $att->group_id)->first();

          if($group){

            $group_names = $group->name;

          }

          else{

            $group_names = '';



          }



        $filter_item = DB::table('target_child_attribute')->whereIn('id', $request->childattribute)->get(['name', 'id']);

        

        $filter_value = DB::table('save_company_target')

            ->where('department_id', $att->department_id)

            ->where('goal_id', $att->goal_id)

            ->where('product_id', $att->product_id)

            ->where('attribute_id', $att->attribute_id)

            ->where('subattribute_id', $att->subattribute_id)

            ->where('group_id',$att->group_id)

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

            'group'=> $group_names,

            'acheived'=>$acheived_count,

        ];

    }



}





  if(!$request->childattribute){

         $list = $data->where('target_type',2)->paginate($request->per_page);

        foreach($list as $row){

        $department = DB::table('department')->where('id',$row->department_id)->first();

        $goal = DB::table('company_goals')->where('id',$row->goal_id)->first();

        $attribute = DB::table('target_attribute')->where('id',$row->attribute_id)->first();

        $subattribute = DB::table('target_subattribute')->where('id',$row->subattribute_id)->first();

         $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();

        

          $group = DB::connection('sales_db')->table('group_names')->where('group_id', $row->group_id)->first();

           if($group){

            $group_names = $group->name;

          }

          else{

            $group_names = '';



          }



        $data_list[] = array('goal'=>$goal->attribute_name,'attribute'=>$attribute->attribute_name,

        'subattribute'=>$subattribute->subattribute_name,

        'product'=>$product->product_name,'year'=>$row->financial_year,'department'=>$department->department_name,'group'=>$group_names);

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

    $subattributes = DB::table('target_child_attribute')

        ->whereIn('id', [1, 2])

        ->get(['id as subattribute_id', 'name']);



    // Fetch all categories

    $categories = DB::connection('sales_db')->table('product_category')->where('service_id',$id)->get(['id', 'category_name']);



    foreach($categories as $category) {

        $list[] = [

            'category' => $category,

            'subattributes' => $subattributes->toArray(), // Assign the same subattributes to each category

        ];

    }



    return response()->json(['status' => 200, 'data' => $list]);

}



public function get_group_employee(Request $request){

    $emp_list = [];

    if($request->assigngroup){

      $data = DB::table('emp_basic_info')

      ->whereRaw('FIND_IN_SET(?, assigned_group)', [$request->assigngroup])->where('emp_status',1)

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

    $data = DB::table('save_assign_leads_to_groups')->where('financial_year',$financialYear,)->get(['group_id','category_id','source_id','no_of_leads']);

    return response()->json(['status'=>200,'data'=>$data]);





}

public function count_sales_lead_data($group,$category,$month,$year){

    $data = DB::table('monthly_lead_assign_to_groups')
           ->where('group_id',$group)->where('category_id',$category)
           ->where('month',$month)->where('financial_year',$year)
           ->sum('after_edited_no_of_lead');


    $assign_sales_data = DB::table('assign_target')
                       ->where('group_id',$group)->where('category_id',$category)
                       ->where('month',$month)->where('financial_year',$year)
                       ->where('department_id',3)->where('target_mode','individual')->sum('assign_data');

 

    if($assign_sales_data && $assign_sales_data>0){

       $remaining_sales = $data - $assign_sales_data;
       if($remaining_sales>0){
         $total_remaing_lead = $remaining_sales;
       }
       else{
        $total_remaing_lead = 0;

       }

     }

     else{
        $total_remaing_lead = $data;
      }

      return response()->json(['status'=>200,'sales_data'=> $total_remaing_lead]);

 

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

        $group = DB::connection('sales_db')->table('group_names')

        ->whereIn('group_id',$groups)

        ->pluck('name')

        ->implode(',');

        $emp_name = BasicInfo::where('emp_id',$row->emp_id)->first();

        $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();

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



    $active_groups = DB::connection('sales_db')->table('group_names')->whereNotIn('group_id', $groups_list)->get(['group_id','name']);

    

    return response()->json(['status'=>200,'data'=> $active_groups]);

}

public function save_assign_group(Request $request){

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



public function get_followup_status_list(){





  $data = DB::connection('sales_db')->table('followup_status')->get(['id','activity_name']);

  return response()->json(['status'=>200,'data'=>$data]);

}





function getFinancialYearDates($financialYear) {

    [$startYear, $endYear] = explode('-', $financialYear);

    $start = Carbon::createFromDate($startYear, 4, 1);

    $end = Carbon::createFromDate($endYear, 3, 31)->endOfDay(); 



    return [$start, $end];

}



public function get_lead_based_data(Request $request) {

  $group_name = '';

    $data = DB::table('save_company_target')->where('target_type',1);



    if ($request->product) {

        $data->where('product_id', $request->product);

    }



    if ($request->category) {

        $data->where('category_id', $request->category);

    }



    if ($request->financial_year) {

        $financialYear = $request->financial_year;

    } else {

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

    }



    $data->where('financial_year', $financialYear);



    // Fetch paginated data

    $data_array = $data->paginate(10);

    

    $list_data = [];



    foreach ($data_array as $row) {

        $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();

        $category = DB::connection('sales_db')->table('product_category')->where('id', $row->category_id)->first();

        $goal = DB::table('company_goals')->where('id', $row->goal_id)->first();

        $attribute = DB::table('target_attribute')->where('id', $row->attribute_id)->first();

        $child_attribute = DB::table('target_child_attribute')->where('id', $row->child_attribute_id)->first();



        $department = DB::table('department')->where('id', $row->department_id)->first();



        $source_from = '';

        if ($row->child_attribute_id == 1) {

            $source_from = 'Website';

        } elseif ($row->child_attribute_id == 2) {

            $source_from = 'Adword';

        }





         [$startYear, $endYear] = explode('-', $financialYear);

           $start = Carbon::createFromDate($startYear, 4, 1);

           $end = Carbon::createFromDate($endYear, 3, 31)->endOfDay(); 





        if ($request->group) {

            $total_leads_achieved = DB::connection('sales_db')

             ->table('enquiry_info')

             ->where('source_type', $source_from)

             ->where('group_id', $request->group)  

             ->whereBetween('created_date', [$start, $end])

              ->count();



            $group_name = DB::connection('sales_db')

            ->table('group_names')

              ->where('group_id', $request->group)

             ->first();

            }



        else{

          $total_leads_achieved = DB::connection('sales_db')

            ->table('enquiry_info')

            ->where('source_type', $source_from)

            ->whereBetween('created_date', [$start, $end])

            ->count(); 

            }



        $total_remaining = $row->child_attribute_value - $total_leads_achieved;

        if($total_remaining>0){

            $remaining_data = $total_remaining;

        }

        else{

            $remaining_data = 0;

            

        }



        $list_data[] = array(

            'id' => $row->id,

            'product' => $product->product_name ?? '', 

            'category' => $category->category_name ?? '', 

            'goal' => $goal->attribute_name ?? '', 

            'attribute' => $attribute->attribute_name ?? '', 

            'child_attribute' => $child_attribute->name ?? '', 

            'total' => $row->child_attribute_value,

            'achieved' => $total_leads_achieved,

            'remaining' =>$remaining_data,

            'year'=>      $row->financial_year,

            'department'=>$department->department_name,

            'group'=> $group_name->name ??' ',



        );

    }

  return response()->json(['status' => 200, 'data' => $list_data,'last_page'=>$data_array->lastPage()]);

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
     $emp_details = BasicInfo::where('emp_id', $request->id)->first();
     if($emp_details->assigned_group!=''){
        $group_id = explode(',', $emp_details->assigned_group);
     }
     else{
        $group_id = ' ';

     }
     $emp_managers = DB::table('employee_managers')
     ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->id])
     ->where('status', 1)
     ->where('dept_id', 3)
     ->pluck('emp_id')
     ->toArray();

     $emp_managers[] = $request->id;
     $emp_managers = array_unique($emp_managers);



     $monthly_data_assign = DB::table('monthly_lead_assign_to_groups')
       ->select('category_id', 'group_id', 'financial_year', 'month', 
        DB::raw('MIN(product_id) as product_id'),
        DB::raw('MIN(service_id) as service_id'),
        DB::raw('MIN(target_id) as target_id'),
        DB::raw('MIN(id) as id'),
        DB::raw('SUM(no_of_packages) as no_of_packages'),
        DB::raw('SUM(after_edited_no_of_lead) as total_value'))
       ->groupBy('category_id', 'group_id', 'financial_year', 'month')
       ->whereIn('target_type',[1,3])
       ->whereIn('group_id', $group_id);

       if ($request->category) {
           $monthly_data_assign->where('category_id', $request->category);
        }
        if ($request->group) {
           $monthly_data_assign->where('group_id', $request->group);
         }
         if ($request->product) {
            $monthly_data_assign->where('product_id', $request->product);
          }

         if ($request->financial_year && $request->month) {

            $monthly_data_assign->where('financial_year', $request->financial_year)
    
                ->where('month', $request->month);
    
            $queryResult = $monthly_data_assign->paginate($request->per_page);
    
        } else {
    
            $queryResult = $monthly_data_assign->where('financial_year', $financialYear)
    
                ->where('month', $current_month)
    
                ->paginate($request->per_page);
          }

          foreach ($queryResult as $row) {
            [$startYear, $endYear] = explode('-', $financialYear);
            $start = Carbon::createFromDate($startYear, 4, 1);
            $end = Carbon::createFromDate($endYear, 3, 31)->endOfDay();
            $monthNumber = date('m', strtotime($row->month . ' 1'));

            $group = DB::connection('sales_db')->table('group_names')->where('group_id', $row->group_id)->first();
            $category = DB::connection('sales_db')->table('product_category')->where('id', $row->category_id)->first();
            $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();
            $service = DB::connection('sales_db')->table('product_service')->where('id',$row->service_id)->first();

             $total_packages = DB::connection('sales_db')->table('package_info')
                               ->whereRaw("SUBSTRING_INDEX(group_id, ',', 1) = ?", [$row->group_id])
                               ->whereIn('exe_id',$emp_managers)
                               ->whereRaw("MONTH(created_date) = ?", [$monthNumber])->pluck('package_id');

             $total_acheived = DB::connection('sales_db')->table('service_info')->whereIn('package_id', $total_packages)
             ->where('category_id',$row->category_id)->sum('total_lead');

             $remaining = $row->total_value -  $total_acheived;
             if($remaining>0){
                $remaining_leads = $remaining;

             }
             else{
                $remaining_leads = 0;

             }

             $no_of_active_packages = DB::connection('sales_db')->table('package_info')
                               ->whereRaw("SUBSTRING_INDEX(group_id, ',', 1) = ?", [$row->group_id])
                               ->whereRaw("SUBSTRING_INDEX(category_id, ',', 1) = ?", [$row->category_id])
                               ->where('package_status',1)
                               ->whereIn('exe_id',$emp_managers)
                               ->count();

              $remaing_package_target = $row->no_of_packages - $no_of_active_packages;
              if($remaing_package_target>0){
                $remaining_packages =  $remaing_package_target;

              }
              else{
                $remaining_packages = 0;

              }

             $lead_data[] = [
               'group' => $group->name,
               'category' => $category->category_name,
               'value' => $row->total_value,
               'month' => $row->month,
               'target_id'=> $row->target_id,
               'financial_year' => $row->financial_year,
               'group_id' => $row->group_id,
               'category_id' => $row->category_id,
               'acheived'=>$total_acheived,
               'remaining'=>$remaining_leads,
               //'pkg'=> $total_packages,
               'product'=>$product->product_name,
               'service'=>$service->service_name,
               'id'=>$row->id,
               'no_of_packages'=>$row->no_of_packages??0,
               'acheived_packages'=>$no_of_active_packages,
               'remaining_packages'=>$remaining_packages,

            ];

        }

        return response()->json(['status' => 200, 'data' => $lead_data, 'last_page' => $queryResult->lastPage()]);

}

public function assign_lead_target_for_sales(Request $request){

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

    if($request->emp_id =='RIMS1'){
        $data = DB::table('assign_target')->where('department_id',3);

    }
    else{
        $data = DB::table('assign_target')->where('department_id',3)->where('created_by',$request->emp_id);

    }
    if($request->financial_year){
        $data->where('financial_year',$request->financial_year);

    }
    else{
        $data->where('financial_year',$financialYear);
    }
    $query = $data->paginate(5);

    foreach($query as $row){
        $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();
        $emp_details = DB::table('emp_basic_info')->where('emp_id',$row->emp_id)->first();
        $manager_details = DB::table('emp_basic_info')->where('emp_id',$emp_details->reporting_manager)->first();

        [$startYear, $endYear] = explode('-', $financialYear);
        $start = Carbon::createFromDate($startYear, 4, 1);
        $end = Carbon::createFromDate($endYear, 3, 31)->endOfDay();
        $monthNumber = date('m', strtotime($row->month . ' 1'));

        $group = DB::connection('sales_db')->table('group_names')->where('group_id', $row->group_id)->first();
        $category = DB::connection('sales_db')->table('product_category')->where('id', $row->category_id)->first();

         $total_packages = DB::connection('sales_db')->table('package_info')
                           ->whereRaw("SUBSTRING_INDEX(group_id, ',', 1) = ?", [$row->group_id])
                           ->where('exe_id', $row->emp_id)
                           ->whereRaw("MONTH(created_date) = ?", [$monthNumber])
                           ->whereBetween('created_date', [$start, $end])
                           ->pluck('package_id');

         $total_acheived = DB::connection('sales_db')->table('service_info')->whereIn('package_id', $total_packages)
         ->where('category_id',$row->category_id)->sum('total_lead');

         $remaining = $row->assign_data -  $total_acheived;
         if($remaining>0){
            $remaining_leads = $remaining;

         }
         else{
            $remaining_leads = 0;

         }
         $lead_data[] = [
            'group' => $group->name,
            'category' => $category->category_name,
            'product'=> $product->product_name??'',
            'value' => $row->assign_data??'',
            'emp_name'=>$emp_details->emp_fname.' '.$emp_details->emp_lame??'',
            'manager_name'=>$manager_details->emp_fname.' '.$manager_details->emp_lame??'',
            'month' => $row->month??'',
            'financial_year' => $row->financial_year??'',
            'group_id' => $row->group_id??'',
            'category_id' => $row->category_id??'',
            'acheived'=>$total_acheived??'',
            'remaining'=>$remaining_leads??'',
            'remark'=>$row->remark??'',
            //'pkg'=> $total_packages??'',

         ];

         }
        return response()->json(['status'=>200,'data'=>$lead_data,'last_page'=>$query->lastPage()]);

        
}

public function lead_amount_details(Request $request){
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
     $emp_details = BasicInfo::where('emp_id', $request->id)->first();
     if($emp_details->assigned_group!=''){
        $group_id = explode(',', $emp_details->assigned_group);
     }
     else{
        $group_id = ' ';

     }
     $emp_managers = DB::table('employee_managers')
     ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->id])
     ->where('status', 1)
     ->where('dept_id', 3)
     ->pluck('emp_id')
     ->toArray();

     $emp_managers[] = $request->id;
     $emp_managers = array_unique($emp_managers);

   $monthly_data_assign = DB::table('monthly_lead_assign_to_groups')
       ->select('category_id', 'group_id', 'financial_year', 'month',
        DB::raw('MIN(product_id) as product_id'),
        DB::raw('MIN(service_id) as service_id'),
        DB::raw('SUM(total_target_amount) as total_target_amount'),
        DB::raw('SUM(after_edited_no_of_lead) as total_value'))
       ->groupBy('category_id', 'group_id', 'financial_year', 'month')
       ->whereIn('group_id', $group_id)
       ->whereIn('target_type',[1]);

       if ($request->category) {
           $monthly_data_assign->where('category_id', $request->category);
        }
        if ($request->group) {
           $monthly_data_assign->where('group_id', $request->group);
         }

         if ($request->product) {
            $monthly_data_assign->where('product_id', $request->product);
          }

         if ($request->financial_year && $request->month) {

            $monthly_data_assign->where('financial_year', $request->financial_year)
    
                ->where('month', $request->month);
    
            $queryResult = $monthly_data_assign->paginate($request->per_page);
    
        } else {
    
            $queryResult = $monthly_data_assign->where('financial_year', $financialYear)
    
                ->where('month', $current_month)
    
                ->paginate($request->per_page);
          }

          foreach ($queryResult as $row) {
            [$startYear, $endYear] = explode('-', $financialYear);
            $start = Carbon::createFromDate($startYear, 4, 1);
            $end = Carbon::createFromDate($endYear, 3, 31)->endOfDay();
            $monthNumber = date('m', strtotime($row->month . ' 1'));

            $group = DB::connection('sales_db')->table('group_names')->where('group_id', $row->group_id)->first();
            $category = DB::connection('sales_db')->table('product_category')->where('id', $row->category_id)->first();
            $product = DB::connection('sales_db')->table('product')->where('id',$row->product_id)->first();
            $service = DB::connection('sales_db')->table('product_service')->where('id',$row->service_id)->first();

            $total_acheived = DB::connection('sales_db')->table('payment_history')
            ->leftJoin('package_info', 'package_info.package_id', '=', 'payment_history.package_id')
            ->whereRaw("SUBSTRING_INDEX(package_info.group_id, ',', 1) = ?", [$row->group_id])
            ->whereRaw("SUBSTRING_INDEX(package_info.category_id, ',', 1) = ?", [$row->category_id])
            ->where('payment_history.product_id', $row->product_id)
            ->whereIn('payment_history.exe_id', $emp_managers)
            ->whereRaw("MONTH(payment_history.created_date) = ?", [$monthNumber])
            ->whereBetween('payment_history.created_date', [$start, $end])
            ->sum(DB::raw('payment_history.paid_amount - payment_history.reg_amount - payment_history.tax_amount'));
        

            $total_remaining = $row->total_target_amount - $total_acheived;

            if($total_remaining>0){
                $remaining_target = $total_remaining;
            }
            else{
                $remaining_target = 0;


            }

              $lead_data[] = [
               'group' => $group->name,
               'category' => $category->category_name,
               'month' => $row->month,
               'acheived'=>$total_acheived,
               'remaining'=>$remaining_target,
               'amount' =>  $row->total_target_amount,
               'financial_year' => $row->financial_year,
               'group_id' => $row->group_id,
               'category_id' => $row->category_id,
               'product'=>$product->product_name,
               'service'=>$service->service_name,

            ];

        }

        return response()->json(['status' => 200, 'data' => $lead_data, 'last_page' => $queryResult->lastPage()]);

}

public function service_wise_targets_details(Request $request)
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

   
    $emp_details = DB::table('emp_basic_info')
        ->where('emp_id', $request->emp_id)
       ->first();

    $group_id = explode(',',$emp_details->assigned_group);

   
    $data = DB::table('monthly_lead_assign_to_groups')
        ->where('product_id', $request->product)
        ->whereIn('group_id', $group_id);

    
    if ($request->financial_year) {
        $data->where('financial_year', $request->financial_year);
    } else {
        $data->where('financial_year', $financialYear);
    }
       $data_array = $data
        ->select('service_id', DB::raw('SUM(total_target_amount) as total_target_amount'))
        ->groupBy('service_id')
        ->get(); 

    return response()->json(['status'=>200, 'data' => $data_array]);
}

public function manager_team_targets(Request $request)
{
    $data_array = [];
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

    $f_year = $request->financial_year ?? $financialYear;
    [$startYear, $endYear] = explode('-', $f_year);
    $start = Carbon::createFromDate($startYear, 4, 1);
    $end = Carbon::createFromDate($endYear, 3, 31)->endOfDay();

    // Get month
    if ($request->month) {
        $monthNumber = date('m', strtotime($request->month . ' 1'));
        $monthname = $request->month;
    } else {
        $monthNumber = Carbon::now()->month;
        $monthname = Carbon::now()->format('F');
    }

    //$id = 'RIMS9'; 
    $emp_managers = DB::table('employee_managers')
        ->whereRaw('FIND_IN_SET(?, reporting_to)', [$request->id])
        ->where('dept_id', 3)
        ->pluck('emp_id')
        ->toArray();

    
    $emp_managers[] = $request->id;
    $emp_managers = array_unique($emp_managers);

   
    $total_targets = DB::table('assign_target')
        ->whereIn('emp_id', $emp_managers)
        ->select('emp_id', DB::raw('SUM(total_target_amount) as total_amount'),DB::raw('SUM(no_of_packages) as total_packages'));

   
    if ($request->financial_year && $request->month) {
        $total_targets->where('financial_year', $request->financial_year)
            ->where('month', $request->month);
    } else {
        $total_targets->where('financial_year', $financialYear)
            ->where('month', $monthname);
    }

    $result = $total_targets->groupBy('emp_id')->get();

    foreach ($result as $row) {
      
        $emp_name = DB::table('emp_basic_info')->where('emp_id', $row->emp_id)->first();

        $get_team = DB::table('employee_managers')
                   ->whereRaw('FIND_IN_SET(?, reporting_to)', [$row->emp_id])
                   ->where('dept_id', 3)
                   ->pluck('emp_id')
                   ->toArray();

    
    $get_team[] = $row->emp_id;
    $get_team = array_unique($get_team);
        $total_achieved = DB::connection('sales_db')->table('payment_history')
            ->whereIn('exe_id', $get_team)
            ->whereMonth('created_date', $monthNumber)
            ->whereBetween('created_date', [$start, $end])
            ->sum(DB::raw('paid_amount - reg_amount - tax_amount'));

        $achevied_packages = DB::connection('sales_db')->table('package_info')
            ->whereIn('exe_id', $get_team)
            ->where('package_status',1)
            ->count();

        $remaining_packages = $row->total_packages - $achevied_packages;
        if($remaining_packages>0){
          $no_of_remaining_packages = $remaining_packages;

        }
        else{
          $no_of_remaining_packages = 0;

        }

        
        $total_remaining = $row->total_amount - $total_achieved;
        $remaining_data = ($total_remaining > 0) ? $total_remaining : 0;

        if($total_achieved>0){
          $complete_percent = round($total_achieved/$row->total_amount*100);
        }
        else{
          $complete_percent = 0;

        }

        
        $details_data = DB::table('assign_target')
            ->where('emp_id', $row->emp_id)
            ->select('product_id', 'category_id', 'group_id', 'month', 'financial_year', DB::raw('SUM(total_target_amount) as total_amount'),DB::raw('SUM(no_of_packages) as total_packages'));

        if ($request->financial_year && $request->month) {
            $details_data->where('financial_year', $request->financial_year)
                ->where('month', $request->month);
        } else {
            $details_data->where('financial_year', $financialYear)
                ->where('month', $monthname);
        }

       
        $details_data_query = $details_data->groupBy('product_id', 'category_id', 'group_id', 'month', 'financial_year')->get();

        
        $details_data_array = [];

        foreach ($details_data_query as $details) {
            
            $product = DB::connection('sales_db')->table('product')->where('id', $details->product_id)->first();
            $category = DB::connection('sales_db')->table('product_category')->where('id', $details->category_id)->first();
            $group = DB::connection('sales_db')->table('group_names')->where('group_id', $details->group_id)->first();

            
            $total_achieved_detail = DB::connection('sales_db')->table('payment_history')
                ->leftJoin('package_info', 'package_info.package_id', '=', 'payment_history.package_id')
                ->whereRaw("SUBSTRING_INDEX(package_info.group_id, ',', 1) = ?", [$details->group_id])
                ->whereRaw("SUBSTRING_INDEX(package_info.category_id, ',', 1) = ?", [$details->category_id])
                ->where('payment_history.product_id', $details->product_id)
                ->whereIn('payment_history.exe_id', $get_team)
                ->whereRaw("MONTH(payment_history.created_date) = ?", [$monthNumber])
                ->whereBetween('payment_history.created_date', [$start, $end])
                ->sum(DB::raw('payment_history.paid_amount - payment_history.reg_amount - payment_history.tax_amount'));

           
            $remaining_target_amount = $details->total_amount - $total_achieved_detail;

            $remaining_target = ($remaining_target_amount > 0) ? $remaining_target_amount : 0;

            
            $details_data_array[] = [
                'emp_id' => $row->emp_id,
                'product' => $product->product_name ?? '',
                'category' => $category->category_name ?? '',
                'group' => $group->name ?? '',
                'total_target' => $details->total_amount,
                'achieved' => $total_achieved_detail,
                'remaining' => $remaining_target
            ];
        }

       
        $data_array[] = [
            'name' => $emp_name->emp_fname . ' ' . $emp_name->emp_lame,
            'total_target' => $row->total_amount ?? 0,
            'achieved' => $total_achieved ?? 0,
            'remaining' => $remaining_data ?? 0,
            'month' => $monthname,
            'year' => $f_year,
            'complete_percent'=>$complete_percent,
            'no_of_packages'=>$row->total_packages,
            'achevied_packages'=>$achevied_packages,
            'remaining_packages'=>$no_of_remaining_packages,
            'details' => $details_data_array ,
            'emp_id'=>$row->emp_id,
        ];
    }

    return response()->json([
        'status' => 200,
        'data' => $data_array, 
    ]);
}



public function managers_targets(Request $request)
{
    $data_array = [];
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

    $f_year = $request->financial_year ?? $financialYear;

    [$startYear, $endYear] = explode('-', $f_year);
    $start = Carbon::createFromDate($startYear, 4, 1);
    $end = Carbon::createFromDate($endYear, 3, 31)->endOfDay();
    if($request->month){
        $monthNumber = date('m', strtotime($request->month . ' 1'));
        $monthname = $request->month;

    }
    else{
        $monthname = Carbon::now()->format('F');
    }
    
    if($request->id == 'RIMS1'){
      $data = DB::table('emp_basic_info')
        ->where('desi_id', 38)
        ->where('emp_status', 1)
        ->get(['emp_id', 'emp_fname', 'emp_lame']);

    }
    else{
      $data = DB::table('emp_basic_info')
        ->where('desi_id', 38)
        ->where('emp_status', 1)
        ->where('emp_id',$request->id)
        ->get(['emp_id', 'emp_fname', 'emp_lame']);

    }

    $teamMembersMapping = [];
    foreach ($data as $row) {
        $get_team = DB::table('employee_managers')
            ->whereRaw('FIND_IN_SET(?, reporting_to)', [$row->emp_id])
            ->where('dept_id', 3)
            //->where('status', 1)
            ->pluck('emp_id')
            ->toArray();

        $teamMembers = array_unique(array_merge($get_team, [$row->emp_id]));
        $teamMembersMapping[$row->emp_id] = $teamMembers;
    }

    foreach ($teamMembersMapping as $managerId => $members) {
        $managerInfo = $data->firstWhere('emp_id', $managerId);
        $managerName = $managerInfo ? $managerInfo->emp_fname . ' ' . $managerInfo->emp_lame : 'Unknown';
        
        $emp_details = DB::table('emp_basic_info')
        ->where('emp_id', $managerId)
       ->first();

    $group_id = explode(',',$emp_details->assigned_group);

        $total_targets = DB::table('monthly_lead_assign_to_groups')
            ->whereIn('group_id',$group_id);

        $achevied = DB::connection('sales_db')->table('payment_history')
            ->whereIn('exe_id', $members)
            ->select(DB::raw('SUM(paid_amount - reg_amount - tax_amount) as net_paid_amount'));

        if ($request->financial_year && $request->month) {
            $total_targets->where('financial_year', $request->financial_year)
                ->where('month', $request->month);

            $achevied->whereMonth('created_date', $monthNumber)
                ->whereBetween('created_date', [$start, $end]);
                
        } else {
            $total_targets->where('financial_year', $financialYear)
                ->where('month',Carbon::now()->format('F'));

            $achevied->whereMonth('created_date', Carbon::now()->month)
                ->whereBetween('created_date', [$start, $end]);
        }

        $total_package_target = $total_targets->sum('no_of_packages');

        $achevied_packages = DB::connection('sales_db')->table('package_info')->whereIn('exe_id', $members)->where('package_status',1)->count();

        $remaining_packages =  $total_package_target - $achevied_packages;
        if($remaining_packages>0){
          $total_remaining_packages = $remaining_packages;

        }
        else{
           $total_remaining_packages = 0;

        }

        $remaining = $total_targets->sum('total_target_amount') -  $achevied->value('net_paid_amount');
        $remaining_targets = max($remaining, 0);
        if($achevied->value('net_paid_amount')>0){
          $complete_percent = round($achevied->value('net_paid_amount')/ $total_targets->sum('total_target_amount')*100);
        }
        else{
          $complete_percent = 0;

        }

        $data_array[] = [
            'manager_id' => $managerId,
            'name'=> $managerName,
            'total_targets' => $total_targets->sum('total_target_amount'),
            'total_acheived' =>  $achevied->value('net_paid_amount')??'0',
            'remaining' => $remaining_targets,
            'f_year'=>$f_year,
            'month'=>$monthname,
            'complete_percent'=> $complete_percent,
            'total_packages'=> $total_package_target,
            'achevied_packages'=> $achevied_packages,
            'remaining_packages'=>$total_remaining_packages,
        ];
    }

    return response()->json(['status' => 200, 'data' => $data_array]);
}

public function sales_partcular_member_target(Request $request){
    $data_array = [];
     $team_target_exists = '';
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
  
      $f_year = $request->financial_year ?? $financialYear;
  
      [$startYear, $endYear] = explode('-', $f_year);
      $start = Carbon::createFromDate($startYear, 4, 1);
      $end = Carbon::createFromDate($endYear, 3, 31)->endOfDay();
      if($request->month){
          $monthNumber = date('m', strtotime($request->month . ' 1'));
          $monthname = $request->month;
  
      }
      else{
          $monthname = Carbon::now()->format('F');
          $monthNumber = Carbon::now()->month;
  
      }
      $emp_id = $request->id;
  
      $emp_managers = DB::table('employee_managers')
              ->whereRaw('FIND_IN_SET(?, reporting_to)', [$emp_id])
              ->where('dept_id', 3)
              //->where('status', 1)
              ->pluck('emp_id')
              ->toArray();
  
       $emp_managers[] = $emp_id;
       $emp_managers = array_unique($emp_managers);
  
     $data = DB::table('assign_target')->where('emp_id',$emp_id);
       if($request->product){
         $data->where('product_id',$request->product);
  
       }
       if($request->category){
         $data->where('category_id',$request->category);
  
       }
       if($request->group){
         $data->where('group_id',$request->group);
  
       }
       if($request->financial_year && $request->month){
          $data->where('financial_year', $request->financial_year)
                ->where('month', $request->month);
        }
        else{
          $data->where('financial_year', $financialYear)
                ->where('month', $monthname);
         }
  
         if($request->financial_year && $request->month ){
          $f_year = $request->financial_year;
          $f_month = $request->month;
  
         }
         else{
          $f_year = $financialYear;
          $f_month = $monthname;
  
         }
  
         $data_query = $data->paginate(10);
         //return  $data_query;
  
         foreach($data_query as $row){
             $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
              $category = DB::connection('sales_db')->table('product_category')->where('id', $row->category_id)->first();
              $group = DB::connection('sales_db')->table('group_names')->where('group_id', $row->group_id)->first();
  
  
  
                if($row->target_mode == 'individual'){
  
                  $total_packages = DB::connection('sales_db')->table('package_info')
                                 ->whereRaw("SUBSTRING_INDEX(group_id, ',', 1) = ?", [$row->group_id])
                                 ->where('exe_id',$emp_id)
                                 ->whereRaw("MONTH(created_date) = ?", [$monthNumber])->pluck('package_id');
  
                  }
  
                  if($row->target_mode == 'team_based'){
  
                     $total_packages = DB::connection('sales_db')->table('package_info')
                                 ->whereRaw("SUBSTRING_INDEX(group_id, ',', 1) = ?", [$row->group_id])
                                 ->whereIn('exe_id',$emp_managers)
                                 ->whereRaw("MONTH(created_date) = ?", [$monthNumber])->pluck('package_id');
                  }
  
               $total_acheived_lead = DB::connection('sales_db')->table('service_info')->whereIn('package_id', $total_packages)
               ->where('category_id',$row->category_id)->sum('total_lead');
  
               $remaining  = $row->assign_data -  $total_acheived_lead;
  
               if( $remaining>0){
                 $remaining_leads = $remaining;
               }
               else{
                $remaining_leads = 0;
  
               }
  
               if($row->target_mode == 'individual'){
                  $total_acheived_amount = DB::connection('sales_db')->table('payment_history')
                 ->leftJoin('package_info', 'package_info.package_id', '=', 'payment_history.package_id')
                 ->whereRaw("SUBSTRING_INDEX(package_info.group_id, ',', 1) = ?", [$row->group_id])
                ->whereRaw("SUBSTRING_INDEX(package_info.category_id, ',', 1) = ?", [$row->category_id])
                ->where('payment_history.product_id', $row->product_id)
                ->where('payment_history.exe_id', $emp_id)
                ->whereRaw("MONTH(payment_history.created_date) = ?", [$monthNumber])
                ->whereBetween('payment_history.created_date', [$start, $end])
                ->sum(DB::raw('payment_history.paid_amount - payment_history.reg_amount - payment_history.tax_amount'));
  
                $total_achevied_packages = DB::connection('sales_db')->table('package_info')->where('package_status',1)->where('exe_id', $emp_id)->count();
  
               }
  
               if($row->target_mode == 'team_based'){
                  $total_acheived_amount = DB::connection('sales_db')->table('payment_history')
                 ->leftJoin('package_info', 'package_info.package_id', '=', 'payment_history.package_id')
                 ->whereRaw("SUBSTRING_INDEX(package_info.group_id, ',', 1) = ?", [$row->group_id])
                ->whereRaw("SUBSTRING_INDEX(package_info.category_id, ',', 1) = ?", [$row->category_id])
                ->where('payment_history.product_id', $row->product_id)
                ->whereIn('payment_history.exe_id', $emp_managers)
                ->whereRaw("MONTH(payment_history.created_date) = ?", [$monthNumber])
                ->whereBetween('payment_history.created_date', [$start, $end])
                ->sum(DB::raw('payment_history.paid_amount - payment_history.reg_amount - payment_history.tax_amount'));
  
                $total_achevied_packages = DB::connection('sales_db')->table('package_info')->where('package_status',1)->whereIn('exe_id', $emp_managers)->count();
                }
  
                $total_remaining_packages = $row->no_of_packages - $total_achevied_packages;
  
                if($total_remaining_packages>0){
                  $no_of_remaining_packages =  $total_remaining_packages;
  
                }
                else{
                   $no_of_remaining_packages = 0; 
  
                }
          
  
              $total_remaining_amount = $row->total_target_amount - $total_acheived_amount;
  
              if($total_remaining_amount>0){
                  $remaining_target_amount = $total_remaining_amount;
              }
              else{
                  $remaining_target_amount = 0;
                }
  
                $is_team_target = DB::table('assign_target')->where('financial_year',$f_year)->where('month',$f_month)->where('target_mode','team_based')->where('emp_id',$row->emp_id)->exists();
  
                if($is_team_target){
                  $team_target_exists = 'yes';
  
                }
                else{
                  $team_target_exists = 'No';
  
                }
  
                $data_array[] = array('group_id'=>$row->group_id,'category_id'=>$row->category_id,'month'=>$row->month,'year'=>$row->financial_year,'target_mode'=>$row->target_mode,'remark'=>$row->remark,'total_lead'=>$row->assign_data,'acheived_lead'=>$total_acheived_lead,'remaining_lead'=>$remaining_leads,'total_amount'=>$row->total_target_amount,'acheived_amount'=>$total_acheived_amount,'remaining_amount'=>$remaining_target_amount,'product'=>$product->product_name,'group'=>$group->name,'category'=>$category->category_name,'no_of_packages'=>$row->no_of_packages,'achevied_packages'=>$total_achevied_packages,'remaining_packages'=>$no_of_remaining_packages);
  
  
              }
  
              return response()->json(['status'=>200,'data'=>$data_array,'last_page'=>$data_query->lastPage(),'team_target_exists'=>$team_target_exists]);
  
           }

        public function active_target_type_list(){

            $data = DB::table('target_type')->where('status',1)->get(['id','type_name']);
            return response()->json(['status'=>200,'message'=>'Target Typw List','data'=>$data]);
      
      
          }

          public function save_target_type(Request $request){

            $input = $request->all();
        
            $validator = Validator::make($input, [
        
                'name' => 'required',
        
                ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
                }
        
              $data = array('type_name'=>$request->name,
        
              'created_by'=>$request->emp_id);
        
               DB::table('target_type')->insert($data);
        
               return response()->json(['status'=>200,'message'=>'Target Type Created Successfully']);
        
             }
        
            public function target_type_list(Request $request){
        
                $data = DB::table('target_type')->paginate($request->per_page);
        
                return response()->json(['status'=>200,'message'=>'Target Typw List','data'=>$data,'last_page'=>$data->lastPage()]);
        
               }
        
            public function edit_target_type($id){
        
                $data = DB::table('target_type')->where('id',$id)->first();
        
                return response()->json(['status'=>200,'data'=>$data]);
        
            }
        
            public function update_target_type(Request $request,$id){
        
                $input = $request->all();
        
                $validator = Validator::make($input, [
        
                    'name' => 'required',
        
                    ]);
        
                  if($validator->fails()){
        
                        $messages=$validator->messages();
        
                        return response()->json(["messages"=>$messages,'status'=>400]);     
        
                  }
        
                  DB::table('target_type')->where('id',$id)->
        
                  update(['type_name'=>$request->name,
        
                  'created_by'=>$request->emp_id]);
        
                  return response()->json(['status'=>200,'message'=>'Target Type Updated Successfully']);
        
        
        
               }
        
            public function target_type_status($id){
        
                $data = DB::table('target_type')->where('id',$id)->first();
        
                return response()->json(['status'=>200,'message'=>'Target Type Status','data'=>$data->status]);
        
             }
        
            public function target_type_status_update(Request $request){
        
                $request->validate([
        
                    'type_id' => 'required',
        
                    'status' => 'required|in:0,1', 
        
                ]);
        
                DB::table('target_type')->where('id',$request->type_id)->update(['status'=>$request->status]);
        
                return response()->json(['status'=>200,'message'=>'Target Type Status Updated Successfully']);
           }

        public function customer_target_list(Request $request){
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

            $data = DB::table('save_company_target')->whereNotIn('target_type',[1,2]);
            if($request->product){
                $data->where('product_id',$request->product);

            }
            if($request->category){
                $data->where('category_id',$request->category);

            }
            if($request->group){
                $data->where('group_id',$request->group);

            }
            if($request->department){
                $data->where('department_id',$request->department);

            }

            if($request->year){
                $data->where('financial_year',$request->year);

            }
            else{
                $data->where('financial_year',$financialYear);
            }
            $data_array = [];

            $query = $data->paginate(10);
            foreach($query as $row){
               $product = DB::connection('sales_db')->table('product')->where('id', $row->product_id)->first();
               $category = DB::connection('sales_db')->table('product_category')->where('id', $row->category_id)->first();
               $goal = DB::table('company_goals')->where('id', $row->goal_id)->first();
               $attribute = DB::table('target_attribute')->where('id', $row->attribute_id)->first();     
               $sub_attribute = DB::table('target_subattribute')->where('id', $row->subattribute_id)->first();       
               $department = DB::table('department')->where('id', $row->department_id)->first();
               $target_type = DB::table('target_type')->where('id',$row->target_type)->first();
               $group = DB::connection('sales_db')->table('group_names')->where('group_id',$row->group_id)->first();

               $data_array[] = array('product'=>$product->product_name??'','category'=> $category->category_name??'',
                               'goal'=>$goal->attribute_name??'','attribute'=>$attribute->attribute_name??'',
                               'department'=>$department->department_name??'','id'=>$row->id??'',
                               'target_type'=>$target_type->type_name??"",'no_of_data'=>$row->child_attribute_value??''
                               ,'year'=>$row->financial_year,'subattribute_name'=>$sub_attribute->subattribute_name,
                               'group'=> $group->name??'');

                 }
                return response()->json(['status'=>200,'data'=>$data_array,'last_page'=>$query->lastPage()]);
              }

              public function get_group_wise_employee(Request $request){
                $data = DB::table('emp_basic_info')
                        ->where('reporting_manager',$request->emp_id)
                       ->whereRaw('FIND_IN_SET(?, assigned_group)', [$request->group])
                       ->get(['emp_id','emp_fname','emp_lame']);

                return response()->json(['status'=>200,'data'=>$data]);
              }


              public function assign_target_no_counts(Request $request){
                $assign_data = DB::table('assign_target')->where('group_id',$request->group)->where('category_id',$request->category)->where('financial_year',$request->financial_year)->where('month',$request->month);
          
                 $total_data = DB::table('monthly_lead_assign_to_groups')->where('group_id',$request->group)->where('category_id',$request->category)->where('financial_year',$request->financial_year)->where('month',$request->month);
          
                $total_assign_lead = $assign_data->sum('assign_data');
          
                $total_no_of_packages_assign = $assign_data->sum('no_of_packages');
          
                if($total_assign_lead>0){
                  $remaining_lead =  $total_data->sum('after_edited_no_of_lead') - $assign_data->sum('assign_data');
                }
                else{
                   $remaining_lead = $total_data->sum('after_edited_no_of_lead');
          
                }
          
                if($total_no_of_packages_assign>0){
          
                  $remaining_packages = $total_data->sum('no_of_packages') - $assign_data->sum('no_of_packages');
          
                }
                else{
                  $remaining_packages = $total_data->sum('no_of_packages');
          
          
                }
          
                return response()->json(['status'=>200,'remaining_lead'=>$remaining_lead,'remaining_packages'=>$remaining_packages]);
          
          
              }

              public function no_of_remaining_lead_to_assign(Request $request){
                //return $request->all();
                $assign_data = DB::table('assign_target')->where('group_id',$request->group)
                ->where('category_id',$request->category)->where('financial_year',$request->financial_year)
                ->where('month',$request->month)->where('created_by',$request->emp_id);
          
                 $total_data = DB::table('assign_target')->where('group_id',$request->group)
                 ->where('category_id',$request->category)->where('financial_year',$request->financial_year)
                 ->where('month',$request->month)->where('emp_id',$request->emp_id);
          
                $total_assign_lead = $assign_data->sum('assign_data');
          
                $total_no_of_packages_assign = $assign_data->sum('no_of_packages');
          
                if($total_assign_lead>0){
                  $remaining_lead =  $total_data->sum('assign_data') - $assign_data->sum('assign_data');
                }
                else{
                   $remaining_lead = $total_data->sum('assign_data');
          
                }
          
                if($total_no_of_packages_assign>0){
          
                  $remaining_packages = $total_data->sum('no_of_packages') - $assign_data->sum('no_of_packages');
          
                }
                else{
                  $remaining_packages = $total_data->sum('no_of_packages');
          
          
                }
          
                return response()->json(['status'=>200,'remaining_lead'=>$remaining_lead,'remaining_packages'=>$remaining_packages]);

              }
              public function category_based_on_group_id_with_total_target_data(Request $request)
{
    $currentMonth = date('n');
    $currentYear = date('Y');
    $data_array = [];

    if ($currentMonth >= 4) {
        $financialYearStart = $currentYear;
        $financialYearEnd = $currentYear + 1;
    } else {
        $financialYearStart = $currentYear - 1;
        $financialYearEnd = $currentYear;
    }
    $financialYear = $financialYearStart . '-' . $financialYearEnd;

    if ($request->financial_year && $request->month) {
        $month = $request->month;
        $year = $request->financial_year;
    } else {
        $month = Carbon::now()->format('F');
        $year = $financialYear;
    }

    $group_ids = $request->group;

    // Loop through all the group ids
    foreach ($group_ids as $row) {
        $group_names = DB::connection('sales_db')->table('group_names')->where('group_id', $row)->first();
        $category = DB::connection('sales_db')->table('product_category')->where('service_id', $request->service)->get();

        // Prepare an array to store category data for each group
        $group_data = [
            'group_id' => $row,
            'group_name' => $group_names->name,
            'categories' => []
        ];

        // Loop through each category and get the targets and remaining data
        foreach ($category as $cat) {
            $total_lead_target = DB::table('monthly_lead_assign_to_groups')->where('month', $month)
                ->where('financial_year', $year)
                ->where('group_id', $row)
                ->where('category_id', $cat->id)
                ->sum('no_of_monthly_lead');

            $total_package_target = DB::table('monthly_lead_assign_to_groups')->where('month', $month)
                ->where('financial_year', $year)
                ->where('group_id', $row)
                ->where('category_id', $cat->id)
                ->sum('no_of_packages');

            $remaining_target_to_assign = DB::table('assign_target')->where('month', $month)
                ->where('financial_year', $year)
                ->where('group_id', $row)
                ->where('category_id', $cat->id)
                ->sum('assign_data');

            $remaining_packages_to_assign = DB::table('assign_target')->where('month', $month)
                ->where('financial_year', $year)
                ->where('group_id', $row)
                ->where('category_id', $cat->id)
                ->sum('no_of_packages');

            // Calculate the remaining leads and packages
            if ($remaining_target_to_assign > 0 || $remaining_packages_to_assign > 0) {
                $remaining_leads = $total_lead_target - $remaining_target_to_assign;
                $remaining_packages = $total_package_target - $remaining_packages_to_assign;
            } else {
                $remaining_leads = $total_lead_target;
                $remaining_packages = $total_package_target;
            }

            // Add category data to the group
            $group_data['categories'][] = [
                'category_id' => $cat->id,
                'category_name' => $cat->category_name,
                'no_of_leads' => $remaining_leads,
                'no_of_packages' => $remaining_packages,
            ];
        }

        // Add the group data to the final array
        $data_array[] = $group_data;
    }

    // Return the response with the grouped data
    return response()->json(['status' => 200, 'data' => $data_array]);
}



            }