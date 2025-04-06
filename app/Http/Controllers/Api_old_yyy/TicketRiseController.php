<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TicketRise;
use Carbon\Carbon;
use Validator;
use DB;
use App\Models\BasicInfo;
use App\Models\Department;
use App\Models\TicketPriority;
use App\Events\TicketEvent;

class TicketRiseController extends Controller
{
    public function ticket_rise(Request $request){
            //return $request->all();
            $group = '';
            $input = $request->all();
            $validator = Validator::make($input, [
                'department_to'=>'required',
                'attribute'=>'required',
                'subattribute'=>'required',
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
            $ticket_id = rand();
            if($request->department_to==3 && $request->group!=''){
                $group = $request->group;
                $emp_details = DB::table('emp_basic_info')->where('dept_id',3)->where('emp_status',1)->WhereRaw('FIND_IN_SET(?,assigned_group)', [$group])->pluck('emp_id');
                //$emp_id =  $emp_details->emp_id;
               // return $emp_id;
            }
            else{
               $emp_details = DB::table('emp_basic_info')->where('dept_id',$request->department_to)->where('emp_status',1)->pluck('emp_id');
               // $emp_id = $emp_details->emp_id;

            }
            $raise_by = DB::table('emp_basic_info')->where('emp_id',$request->emp_id)->first();

            $data = array('ticket_id'=>$ticket_id,
            'request_from_dept'=>$request->dept_id,'request_to_dept'=>$request->department_to,
            'product_id'=>$request->product,'group_id'=>$group,
            'attribute_id'=>$request->attribute,
            'subattribute_id'=>$request->subattribute,'issue_page_url'=>$request->url,
            'description'=>$request->description,'created_by'=>$request->emp_id);
             DB::table('ticket_rise')->insert($data);
             $message = $raise_by->emp_fname. ' '. 'Raise A Ticket For You Pls See';
             $type="ticket_raise";
             foreach($emp_details as $row){
                $notification_data[] = array('emp_id'=>$row,'notification'=>$message,'type'=>$type,'action_by'=>$raise_by->emp_fname,'ticket_id'=>$ticket_id);
                  event(new TicketEvent($message,$row,$type,$ticket_id));

             }
           
             DB::table('emp_notifications')->insert($notification_data);
             //return $emp_id;

             return response()->json(['status'=>200,'message'=>'Ticket Raised successfully']);


            }
         public function ticket_raise_notification($emp_id){
            $reporting_manager = BasicInfo::where('reporting_manager',$emp_id)->first();
            $data = [];
            $count = '';
            if($reporting_manager){
                $dept_id = BasicInfo::where('emp_id', $reporting_manager->reporting_manager)->first();
                $dept_id = $dept_id->dept_id;
                $count =  TicketRise::where('req_to_department',$dept_id)->where('seen_status',0)->count();
                $row = TicketRise::where('req_to_department',$dept_id)->where('seen_status',0)->where('cross_status',0)->orderBY('id','DESC')->first();
                if($row){
                    
                    $req_from_dept = Department::where('id',$row->req_from_dept)->first();
                    $req_from_dept = $req_from_dept->department_name;
                    $req_from_emp = BasicInfo::where('emp_id',$row->created_by)->first();
                    $req_from_emp = $req_from_emp->emp_fname;
                    $priority = TicketPriority::where('id',$row->priority_attribute)->first();
                    $priority = $priority->priority;
                   $data[] = array('id'=>$row->id,'priority'=>$priority,'dep_from'=> $req_from_dept,'emp_from'=>$req_from_emp,'message'=> 'Hi you have recevied Ticket from' .' '.$req_from_emp.' '.$req_from_dept,'date'=>$row->created_date);


                  }
                    return response()->json(['status'=>200,'message'=>'Ticket Raised Notification','data'=>$data,'count'=>$count]);



            }

         }
         public function cross_status($id){
            TicketRise::where('id',$id)->update(['cross_status'=>1]);
            return response()->json(['status'=>200,'message'=>' updated Successfully',]);



         }
         public function update_seen_status($id){
             $reporting_manager = BasicInfo::where('reporting_manager',$id)->first();

            TicketRise::where('req_to_department',$reporting_manager->dept_id)->update(['seen_status'=>1]);
            return response()->json(['status'=>200,'message'=>' updated Successfully',]);

         }
        public function ticket_raise_list(Request $request,$id){
                      $ticket_list = DB::table('ticket_rise')
                          ->join('department','department.id','=','ticket_rise.request_to_dept')
                          ->join('ticket_priority','ticket_priority.id','ticket_rise.attribute_id')
                          ->join('ticket_subattribute','ticket_subattribute.id','ticket_rise.subattribute_id')
            
                         ->leftJoin('group_names', function($join) {
                            $join->on(DB::raw('FIND_IN_SET(group_names.group_id, ticket_rise.group_id)'), '>', DB::raw('0'));
                        })
                          ->select('department.department_name','ticket_priority.attribute_name',
                           'ticket_subattribute.action_time','ticket_subattribute.completion_time',
                           'ticket_rise.id','ticket_rise.ticket_id','ticket_rise.close_status','ticket_rise.description',
                           'ticket_subattribute.name',
                           DB::raw('GROUP_CONCAT(group_names.name) as group_names'))
                          ->where('ticket_rise.created_by',$id)
                          ->groupBy(
                            'department.department_name',
                            'ticket_priority.attribute_name',
                            'ticket_rise.id',
                            'ticket_rise.ticket_id',
                            'ticket_rise.close_status',
                            'ticket_subattribute.action_time',
                            'ticket_subattribute.completion_time',
                            'ticket_subattribute.name',
                            'ticket_rise.description'
                        )
                          ->get();
        return response()->json(['status'=>200,'message'=>'ticket rise list','data'=>$ticket_list]);


        }
  public function ticket_receive_list(Request $request, $id)
{
    $check_record = DB::table('emp_notifications')->where('emp_id',$id)->where('type','ticket_raise')->where('seen_status',0)->exists();
    if($check_record){
      DB::table('emp_notifications')->where('emp_id',$id)->where('type','ticket_raise')->update(['seen_status'=>1]);

    }
    $data = BasicInfo::where('emp_id', $id)->where('emp_status',1)->first();
    $check_reporting_manager = BasicInfo::where('reporting_manager', $id)->where('emp_status',1)->exists();
    if($check_reporting_manager){
      $is_reporting_manager = 'Yes';
    }
    else{
       $is_reporting_manager = 'No';

    }
    if ($data->assigned_group!='') {
            $groups = $data->assigned_group;
            $group_array = explode(',', $groups);
                
                $ticket_data = DB::table('ticket_rise')
                    ->where('request_to_dept', $data->dept_id)
                    ->where(function($query) use ($group_array) {
                        foreach ($group_array as $group) {
                            $query->orWhereRaw('FIND_IN_SET(?, group_id)', [$group]);
                        }
                    })
                    ->join('emp_basic_info', 'ticket_rise.created_by', '=', 'emp_basic_info.emp_id')
                    ->join('department','ticket_rise.request_from_dept','=','department.id',)
                     ->join('ticket_priority','ticket_priority.id','=','ticket_rise.attribute_id',)
                      ->join('ticket_subattribute','ticket_subattribute.id','=','ticket_rise.subattribute_id',)
                       ->select('ticket_rise.id','ticket_rise.ticket_id','ticket_rise.ticket_status','ticket_rise.close_status', 'emp_basic_info.emp_fname as created_by_name','department.department_name','ticket_priority.attribute_name','ticket_subattribute.name','ticket_subattribute.action_time','ticket_subattribute.completion_time','ticket_rise.request_to_dept','ticket_rise.group_id',)
                    ->get();
                
                return response()->json(['status' => 200, 'data' => $ticket_data,'is_reporting_manager'=>$is_reporting_manager]);
            } else {
                $ticket_data = DB::table('ticket_rise')
                    ->where('request_to_dept', $data->dept_id)
                    ->join('emp_basic_info', 'ticket_rise.created_by', '=', 'emp_basic_info.emp_id')
                    ->join('department','ticket_rise.request_from_dept','=','department.id',)
                     ->join('ticket_priority','ticket_priority.id','=','ticket_rise.attribute_id',)
                      ->join('ticket_subattribute','ticket_subattribute.id','=','ticket_rise.subattribute_id',)
                       ->select('ticket_rise.id','ticket_rise.ticket_id','ticket_rise.ticket_status','ticket_rise.close_status', 'emp_basic_info.emp_fname as created_by_name','department.department_name','ticket_priority.attribute_name','ticket_subattribute.name','ticket_subattribute.action_time','ticket_subattribute.completion_time','ticket_rise.request_to_dept','ticket_rise.group_id')
                    ->get();
                
                return response()->json(['status' => 200, 'data' => $ticket_data,'is_reporting_manager'=>$is_reporting_manager]);
            }
}


    // public function department_employee($i){
    //     $emp_list = BasicInfo::where('emp_status',1)->where('dept_id',$id)->get(['emp_id','emp_fname','emp_lame']);
    //     return response()->json(['status'=>200,'message'=>'Department Employee ','data'=>$emp_list]);

    // }
    public function save_assign_ticket(Request $request){
            TicketRise::where('ticket_id',$request->ticket_id)->update(['ticket_status'=>1]);
            $check_exists = DB::table('ticket_task_assign')->where('ticket_id',$request->ticket_id)->first();
            $data = [];
             $input = $request->all();
             $validator = Validator::make($input, [
                'selected_team' => 'required',
            ]);
            if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
            if($check_exists && $check_exists->assign_to == $request->selected_team ){
              return response()->json(['status'=>201,'message'=>'Already Picked By This Person',]);

            }
            else{
               $data = array('ticket_id'=>$request->ticket_id,'assign_to'=>$request->selected_team,'assign_from'=>$request->emp_id,'remark'=>$request->remark);
              DB::table('ticket_task_assign')->insert($data);

              $raise_by = DB::table('emp_basic_info')->where('emp_id',$request->emp_id)->first();

              $message = $raise_by->emp_fname. ' '. 'Assign A Ticket For You Pls See';

             $type="ticket_assign";
             $notification_data = array('emp_id'=>$request->selected_team,'notification'=>$message,'type'=>$type,'action_by'=>$raise_by->emp_fname,'ticket_id'=>$request->ticket_id);
             DB::table('emp_notifications')->insert($notification_data);
              event(new TicketEvent($message,$request->selected_team,$type,$request->ticket_id));
             return response()->json(['status'=>200,'message'=>'Assign Successfully ',]);

            }
    }
public function get_assign_ticket_list($id){
   $check_record = DB::table('emp_notifications')->where('emp_id',$id)->where('type','ticket_assign')->where('seen_status',0)->exists();
    if($check_record){
      DB::table('emp_notifications')->where('emp_id',$id)->where('type','ticket_assign')->update(['seen_status'=>1]);
      }
         $ticket_data = DB::table('ticket_task_assign')
                    ->where('assign_to', $id)
                    ->join('ticket_rise', 'ticket_rise.ticket_id', '=', 'ticket_task_assign.ticket_id')
                    ->join('department','ticket_rise.request_from_dept','=','department.id',)
                    ->join('emp_basic_info','emp_basic_info.emp_id','ticket_task_assign.assign_from')
                     ->join('ticket_priority','ticket_priority.id','=','ticket_rise.attribute_id',)
                      ->join('ticket_subattribute','ticket_subattribute.id','=','ticket_rise.subattribute_id',)
                       ->select('ticket_rise.id','ticket_rise.ticket_id','ticket_rise.ticket_status','ticket_rise.close_status','department.department_name','ticket_priority.attribute_name','ticket_subattribute.name','ticket_subattribute.action_time','ticket_subattribute.completion_time','emp_basic_info.emp_fname','emp_basic_info.emp_lame','ticket_task_assign.assign_to')
                    ->get();
    return response()->json(['status'=>200,'data'=>$ticket_data]);
   

   }
   public function assign_notification($id){
    $assign_notification = DB::table('ticket_task_assign')->where('assign_to',$id)->where('cross_status',0)->where('seen_status',0)->orderBy('id','DESC')->first();
    if($assign_notification){
        $task_priority = TicketPriority::where('id',$assign_notification->ticket_priority)->first();
        $task_priority = $task_priority->priority;
        $assign_from = BasicInfo::where('emp_id',$assign_notification->assign_from)->first();
        $assign_from = $assign_from->emp_fname;
        return response()->json(['status'=>200,'priority'=>$task_priority,'message'=>'Hi You Have Assign Ticket From'.' '.$assign_from,'id'=>$assign_notification->id]);
     }
   }
   public function assign_notification_status($id){
    DB::table('ticket_task_assign')->where('id',$id)->update(['cross_status'=>1]);
    return response()->json(['status'=>200,'message'=>'status updated successfully']);

   }
   public function assign_ticket_update(Request $request){
    DB::table('ticket_task_assign')->where('ticket_id',$request->ticket_id)->where('assign_to',$request->emp_id)->update(['status'=>1]);
    $input = $request->all();
    $validator = Validator::make($input, [
        'status' => 'required',
    ]);

      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);   
      }
      $data = array('ticket_id'=>$request->ticket_id,'status'=>$request->status,
        'remark'=>$request->remark,'created_by'=>$request->emp_id);
       DB::table('ticket_history')->insert($data);
       $emp_details = DB::table('emp_basic_info')->where('emp_id',$request->emp_id)->first();
       $message = $emp_details->emp_fname.' '. ' Update A Ticket Status, Add  Remark';
       $type="ticket_status";
       $ticket_raise_by = TicketRise::where('ticket_id',$request->ticket_id)->first();
       $emp_manager = BasicInfo::where('emp_id',$request->emp_id)->first();
       $array = [$ticket_raise_by->created_by,$emp_manager->reporting_manager];
       foreach($array as $row){
        $notification_data = array('emp_id'=>$row,'notification'=>$message,'type'=>$type,'action_by'=>$emp_details->emp_fname,'ticket_id'=>$request->ticket_id);
         DB::table('emp_notifications')->insert($notification_data);
          event(new TicketEvent($message,$row,$type,$request->ticket_id));

       }

      return response()->json(['status'=>200,'message'=>'status Created successfully']);

   
   }
   public function priority_attribute(){
   $data = TicketPriority::where('status',1)->get(['id','attribute_name']);
   if(count($data)>0){
     return response()->json(['status'=>200,'message'=>'Priority Attribute List','data'=>$data]);
   }

   }
   public function update_status_click_on_notification($id){
    TicketRise::where('id',$id)->update(['seen_status'=>1]);
    return response()->json(['status'=>200,'message'=>'Click status update successfully']);

   }
   public function click_on_assign_notification($id){
    DB::table('ticket_task_assign')->where('id',$id)->update(['seen_status'=>1]);
    return response()->json(['status'=>200,'message'=>'Click status update successfully']);


   }
   public function view_ticket_status($ticket_id,Request $request){
    $check_record = DB::table('emp_notifications')->where('emp_id',$request->emp_id)->where('type','ticket_status')->exists();
    if( $check_record){
      DB::table('emp_notifications')->where('emp_id',$request->emp_id)->where('type','ticket_status')->update(['seen_status'=>1]);

    }

     $check_approval_data = DB::table('emp_notifications')->where('emp_id',$request->emp_id)->where('type','ticket_time_approval')->exists();
    if($check_approval_data){
      DB::table('emp_notifications')->where('emp_id',$request->emp_id)->where('type','ticket_time_approval')->update(['seen_status'=>1]);

    }

    $ticket_data =  DB::table('ticket_history')
                    ->join('emp_basic_info','ticket_history.created_by','=','emp_basic_info.emp_id')
                    ->select('emp_basic_info.emp_fname','ticket_history.ticket_id',
                    'ticket_history.created_date','ticket_history.manager_remark','ticket_history.ticket_raiser_remark','ticket_history.id','ticket_history.status','ticket_history.remark','emp_basic_info.reporting_manager')
                    ->where('ticket_id',$ticket_id)
                    ->get();
    return response()->json(['status'=>200,'message'=>'Ticket Status','data'=> $ticket_data]);
    }

    public function close_ticket(Request $request){
        TicketRise::where('ticket_id',$request->ticket_id)->update(['close_status'=>1,'close_date'=>Carbon::now()->format('Y-m-d'),'ticket_status'=>2]);
        DB::table('ticket_task_assign')->where('ticket_id',$request->ticket_id)->update(['status'=>2]);
         return response()->json(['status'=>200,'message'=>'Ticket Closed Successfully']);
    }
    public function ticket_active_subattribute($id){
        $data = DB::table('ticket_subattribute')->where('attribute_id',$id)->where('status',1)->get(['id','name']);
        return response()->json(['status'=>200,'data'=>$data]);
   }
   public function get_ticket_details($id){
    $data = TicketRise::where('ticket_id',$id)->first();
    $group_ids = explode(',', $data->group_id);
    $names = DB::table('group_names')
                    ->whereIn('group_id', $group_ids)
                    ->pluck('name')
                    ->implode(',');
    $assigned_member = DB::table('ticket_task_assign')->where('ticket_id',$id)->pluck('assign_to');
    $employeeNames = DB::table('emp_basic_info')->whereIn('emp_id', $assigned_member)->pluck('emp_fname');
    $assign_data = DB::table('ticket_task_assign')->where('ticket_id',$id)->pluck('assign_to');
    $assign_data = BasicInfo::whereIn('emp_id', $assign_data)->pluck('reporting_manager');
    $ticket_raise_by = DB::table('ticket_rise')->where('ticket_id',$id)->first();
    $assignedMemberNames = $employeeNames->implode(',');
    $assignids = $assigned_member->implode(',');
    $assign_from =  $assign_data->implode(',');
    $product = DB::table('product')->where('id',$data->id)->first();
    if($product){
      $products = $product->product_name;
    }
    else{
      $products = '';
    }
    $emp_name = DB::table('emp_basic_info')->where('emp_id',$data->created_by)->first();
    $new_time = DB::table('ticket_time_chnage_request')->where('ticket_id',$id)->where('status',1)->pluck('required_time_in_hour')->toArray();

    $commaSeparatedTimes = implode(', ',  $new_time);

    
    //return $assigned_member;
    $data = array('group_names'=>$names,'description'=>$data->description,'close_date'=>$data->close_date,'url'=>$data->url,'date' => Carbon::parse($data->created_date)->format('Y-m-d'));
    return response()->json(['status'=>200,'message'=>'Ticket Details','data'=>$data,'assign'=>$assignedMemberNames,'assign_array'=>$assignids,'assign_from'=>$assign_from,'raise_by'=>$ticket_raise_by->created_by,'product'=>$products,'ticket_raise_by'=>$emp_name->emp_fname,'new_time'=>$commaSeparatedTimes]);

   }

   public function get_employee_details_with_group(Request $request){
    //return $request->all();
    $data = '';
    if($request->group!=''){
      //return 'kkk';
      $data = BasicInfo::where('dept_id',$request->department)->WhereRaw('FIND_IN_SET(?,assigned_group)', [$request->group])->where('emp_status',1)->get(['emp_id','emp_fname','emp_lame']);


    }
    else{
      $data = BasicInfo::where('dept_id',$request->department)->where('emp_status',1)->get(['emp_id','emp_fname','emp_lame']);

    }
     return response()->json(['status'=>200,'message'=>'Employee List','data'=>$data]);



   }
   public function get_ticket_solve_status(){
    $data = DB::table('ticket_solve_status')->where('status',1)->get(['id','name']);
    return response()->json(['status'=>200,'message'=>'Status List','data'=>$data]);


   }
   public function update_remark_of_ticket(Request $request){
    $ticket = TicketRise::where('ticket_id',$request->ticket_id)->first();
    $ticket_history_data =   DB::table('ticket_history')->where('id',$request->id)->first();
    $reporting_manager = BasicInfo::where('emp_id',$ticket_history_data->created_by)->first();

    if($request->emp_id == $ticket->created_by && $request->emp_id ==  $reporting_manager->reporting_manager){
     DB::table('ticket_history')->where('id',$request->id)->where('ticket_id',$request->ticket_id)->update(['ticket_raiser_remark'=>$request->remark,'manager_remark'=>$request->remark]);
    }
    elseif($request->emp_id == $ticket->created_by){
      DB::table('ticket_history')->where('id',$request->id)->where('ticket_id',$request->ticket_id)->update(['ticket_raiser_remark'=>$request->remark]);

    }
   else{
    DB::table('ticket_history')->where('id',$request->id)->where('ticket_id',$request->ticket_id)->update(['manager_remark'=>$request->remark]);

   }
   $emp_details = BasicInfo::where('emp_id',$request->emp_id)->first();
   $message = $emp_details->emp_fname.' '.'Add remark on Your Status';
   $type = 'ticket_status';
   $emp_id = DB::table('ticket_history')->where('id',$request->id)->where('ticket_id',$request->ticket_id)->first();
    $notification_data = array('emp_id'=>$emp_id->created_by,'notification'=>$message,'type'=>$type,'action_by'=>$emp_details->emp_fname,'ticket_id'=>$request->ticket_id);
    DB::table('emp_notifications')->insert($notification_data);
    event(new TicketEvent($message,$emp_id->created_by,$type,$request->ticket_id));



    return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);

   }
   public function ticket_list_raise_in_org(Request $request){
    $check_data = DB::table('emp_notifications')->where('type','action_time_check')->where('seen_status',0)->exists();
    if($check_data){
      DB::table('emp_notifications')->where('type','action_time_check')->where('seen_status',0)->update(['seen_status'=>1]);

    }
    $data = TicketRise::paginate($request->per_page);
    foreach($data as $row){
      $assign_record_exists = DB::table('ticket_task_assign')->where('ticket_id',$row->ticket_id)->exists();
      if($assign_record_exists){
        $exists = 'yes';


      }
      else{
        $exists = 'No';

      }
      $request_from_dept = Department::where('id',$row->request_from_dept)->first();
      $request_to_dept =  Department::where('id',$row->request_to_dept)->first();
      $attribute  = TicketPriority::where('id',$row->attribute_id)->first();
      $subattribute = DB::table('ticket_subattribute')->where('id',$row->subattribute_id)->first();



      $list[] = array('id'=>$row->id,'ticket_id'=>$row->ticket_id,'attribute'=>$attribute->attribute_name,'subattribute'=>$subattribute->name,'request_from'=> $request_from_dept->department_name,'request_to'=>$request_to_dept->department_name,'exists'=> $exists,'close_status'=>$row->close_status,'action_time'=>$subattribute->action_time,'completion_time'=>$subattribute->completion_time,'request_to_department'=>$row->request_to_dept,'group_id'=>$row->group_id);


    }
    return response()->json(['status'=>200,'data'=>$list,'last_page'=>$data->lastPage()]);


   }
   public function save_new_time_request(Request $request){
    $input = $request->all();
    $validator = Validator::make($input, [
        'new_time' => 'required',
         'remark' => 'required',
    ]);

      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $data = array('ticket_id'=>$request->ticket_id,'created_by'=>$request->emp_id,'required_time_in_hour'=>$request->new_time,'remark'=>$request->remark);
      DB::table('ticket_time_chnage_request')->insert($data);
      $ticket_details = TicketRise::where('ticket_id',$request->ticket_id)->first();
      $emp_details = BasicInfo::where('emp_id',$request->emp_id)->first();
      $message = $emp_details->emp_fname.' '.' send Request For Change Ticket Solve Time';
      $type = 'ticket_time_change';
      $notification_data = array('emp_id'=>$ticket_details->created_by,'notification'=>$message,'type'=>$type,'action_by'=>$emp_details->emp_fname,'ticket_id'=>$request->ticket_id);
        DB::table('emp_notifications')->insert($notification_data);
        event(new TicketEvent($message,$ticket_details->created_by,$type,$request->ticket_id));
       return response()->json(['status'=>200,'message'=>'Request Send Successfully']);




   }
   public function get_new_time_request(Request $request){
    $data = DB::table('ticket_time_chnage_request')->join('emp_basic_info','ticket_time_chnage_request.created_by','emp_basic_info.emp_id')
      ->select('ticket_time_chnage_request.*','emp_basic_info.emp_fname')
      ->where('ticket_time_chnage_request.created_by',$request->emp_id)->get();
      return response()->json(['status'=>200,'data'=>$data]);
    }

    public function ticket_time_for_approval(Request $request){
      $check_data = DB::table('emp_notifications')->where('emp_id',$request->emp_id)->where('type','ticket_time_change')->where('seen_status',0)->exists();
      if($check_data){
        DB::table('emp_notifications')->where('emp_id',$request->emp_id)->where('type','ticket_time_change')->where('seen_status',0)->update(['seen_status'=>1]);

      }

      $data =  DB::table('ticket_time_chnage_request')->join('emp_basic_info','ticket_time_chnage_request.created_by','emp_basic_info.emp_id')
            ->join('ticket_rise','ticket_rise.ticket_id','ticket_time_chnage_request.ticket_id')
      ->select('ticket_time_chnage_request.*','emp_basic_info.emp_fname')
      ->where('ticket_rise.created_by',$request->emp_id)
      ->get();
      return response()->json(['status'=>200,'data'=>$data]);
    }
    public function ticket_time_approval_status_update(Request $request){
      $data =  DB::table('ticket_time_chnage_request')->where('ticket_id',$request->ticket_id)->update(['status'=>$request->status,'action_by'=>$request->emp_id]);
      $ticket_details = TicketRise::where('ticket_id',$request->ticket_id)->first();
      $emp_details = BasicInfo::where('emp_id',$request->emp_id)->first();
      if($request->status == 1){
        $status = 'Approved';

      }
      else{
         $status = 'Rejected';

      }
      $message = $emp_details->emp_fname.' '. $status. ' '.'Your Time Change Request';
      $type = 'ticket_time_approval';
      $emp_id = DB::table('ticket_time_chnage_request')->where('ticket_id',$request->ticket_id)->first();
      $notification_data = array('emp_id'=> $emp_id->created_by,'notification'=>$message,'type'=>$type,'action_by'=>$emp_details->emp_fname,'ticket_id'=>$request->ticket_id);
        DB::table('emp_notifications')->insert($notification_data);
        event(new TicketEvent($message,$emp_id->created_by,$type,$request->ticket_id));
      return response()->json(['status'=>200,'message'=>'Request Updated Successfully']);

    }

    public function check_ticket_action_time()
{
    $data_array = [];
    $data = TicketRise::where('close_status', 0)->get();

    foreach ($data as $row) {
        $get_create_time = Carbon::parse($row->created_date);
        $current_time = Carbon::now();
        $attribute_data = DB::table('ticket_subattribute')->where('id', $row->subattribute_id)->first();
        $action_time = $attribute_data->action_time;
        $diffInMinutes = $current_time->diffInHours($get_create_time);
        //return $diffInMinutes;

        if ($diffInMinutes >= $action_time && $row->ticket_status == 0) {
            $check_data = DB::table('emp_notifications')->where('emp_id', 'RIMS1')
                ->where('ticket_id', $row->ticket_id)
                ->where('type', 'action_time_check')
                ->exists();

            if (!$check_data) {
                $message = 'Hi Ticket Id ' . $row->ticket_id . ' is Pending Please Assign To Anyone';
                $data_array[] = array(
                    'emp_id' => 'RIMS1',
                    'notification' => $message,
                    'ticket_id' => $row->ticket_id,
                    'type' => 'action_time_check',
                );
            }
        }
    }

    if (!empty($data_array)) {
        DB::table('emp_notifications')->insert($data_array);
    }
}

public function check_ticket_working_status($id){
  //return $id;
  $data_array = [];
  $notification_data = [];
  $data = DB::table('ticket_task_assign')->where('status',0)->where('assign_to',$id)->get();
  foreach($data as $row){
    $get_create_time = Carbon::parse($row->created_date);
    $current_time = Carbon::now();
    $diffInMinutes = $current_time->diffInHours($get_create_time);
    $check_ticket_time = DB::table('ticket_time_chnage_request')->where('ticket_id',$row->ticket_id)->first();
    if($check_ticket_time){
      $time = $check_ticket_time->required_time_in_hour;
     }
     else{
      $ticket_data = TicketRise::where('ticket_id',$row->ticket_id)->first();
      $subattribute = DB::table('ticket_subattribute')->where('id',$ticket_data->subattribute_id)->first();
      $time = $subattribute->completion_time;

     }
     if($diffInMinutes>=$time && $ticket_data->close_status ==0){
      $check_exists_data = DB::table('ticket_points_details')->where('emp_id',$id)->where('ticket_id',$row->ticket_id)->exists();
      if(!$check_exists_data){
         $data_array[] = array('emp_id'=>$id,'ticket_id'=>$row->ticket_id,'points'=>'-2');
         $message = 'Ticket Id'.' '. $row->ticket_id.' '. 'Is Pending ';
         $type = 'ticket_assign';
         $notification_data[] = array('emp_id'=>$id,'notification'=>$message,'type'=>$type,'ticket_id'=>$row->ticket_id);


      }
     }
     }

     DB::table('ticket_points_details')->insert($data_array);
     DB::table('emp_notifications')->insert($notification_data);
     return response()->json(['status'=>200]);




}

public function dept_list_for_ticket_raise(){
  $data = DB::table('department')->where('id','!=',9)->where('status',1)->get(['id','department_name']);
  return response()->json(['status'=>200,'data'=>$data]);
}


public function pick_ticket(Request $request){
  $check_exists = DB::table('ticket_task_assign')->where('ticket_id',$request->ticket_id)->first();
  if($check_exists && $check_exists->assign_to == $request->emp_id){
    return response()->json(['status'=>400,'message'=>'Alredy Picked By You Pls Add Work Status']);
   }
  else{
    $data = array('ticket_id'=>$request->ticket_id,'assign_to'=>$request->emp_id,'assign_from'=>$request->emp_id,'status'=>1);
    DB::table('ticket_rise')->where('ticket_id',$request->ticket_id)->update(['ticket_status'=>1]);
DB::table('ticket_task_assign')->insert($data);
return response()->json(['status'=>200,'message'=>'Ticket Assign To You Successfully']);

  }

}


}