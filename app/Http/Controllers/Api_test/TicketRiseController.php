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
                $emp_details = DB::table('emp_basic_info')->where('dept_id',3)->where('reporting_manager','RIMS1')->WhereRaw('FIND_IN_SET(?,assigned_group)', [$group])->first();
                $emp_id =  $emp_details->emp_id;
                //return $emp_id;
            }
            $emp_details = DB::table('emp_basic_info')->where('dept_id',$request->department_to)->where('reporting_manager','RIMS1')->first();
            $emp_id = $emp_details->emp_id;
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
             $notification_data = array('emp_id'=>$emp_id,'notification'=>$message,'type'=>$type,'action_by'=>$request->emp_id);
             DB::table('emp_notifications')->insert($notification_data);
              event(new TicketEvent($message,$emp_id,$type));

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
    $data = BasicInfo::where('reporting_manager', $id)->first();
    
    if ($data) {
        $check_groups = BasicInfo::where('emp_id', $data->reporting_manager)->first();
        
        if ($check_groups) {
            $groups = $check_groups->assigned_group;
            
            if ($groups) {
                $group_array = explode(',', $groups);
                
                $ticket_data = DB::table('ticket_rise')
                    ->where('request_to_dept', $check_groups->dept_id)
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
                
                return response()->json(['status' => 200, 'data' => $ticket_data]);
            } else {
                $ticket_data = DB::table('ticket_rise')
                    ->where('request_to_dept', $check_groups->dept_id)
                    ->join('emp_basic_info', 'ticket_rise.created_by', '=', 'emp_basic_info.emp_id')
                    ->join('department','ticket_rise.request_from_dept','=','department.id',)
                     ->join('ticket_priority','ticket_priority.id','=','ticket_rise.attribute_id',)
                      ->join('ticket_subattribute','ticket_subattribute.id','=','ticket_rise.subattribute_id',)
                       ->select('ticket_rise.id','ticket_rise.ticket_id','ticket_rise.ticket_status','ticket_rise.close_status', 'emp_basic_info.emp_fname as created_by_name','department.department_name','ticket_priority.attribute_name','ticket_subattribute.name','ticket_subattribute.action_time','ticket_subattribute.completion_time','ticket_rise.request_to_dept','ticket_rise.group_id')
                    ->get();
                
                return response()->json(['status' => 200, 'data' => $ticket_data]);
            }
        } else {
            return response()->json(['status' => 404, 'message' => 'Reporting manager not found.']);
        }
    } else {
        return response()->json(['status' => 404, 'message' => 'Employee data not found.']);
    }
}


    public function department_employee($id){
        $emp_list = BasicInfo::where('emp_status',1)->where('dept_id',$id)->get(['emp_id','emp_fname']);
        return response()->json(['status'=>200,'message'=>'Department Employee ','data'=>$emp_list]);

    }
    public function save_assign_ticket(Request $request){
            TicketRise::where('ticket_id',$request->ticket_id)->update(['ticket_status'=>1]);
            $data = [];
             $input = $request->all();
             $validator = Validator::make($input, [
                'selected_team' => 'required',
            ]);
            if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
            $data = array('ticket_id'=>$request->ticket_id,'assign_to'=>$request->selected_team,'assign_from'=>$request->emp_id,'remark'=>$request->remark);
              DB::table('ticket_task_assign')->insert($data);

              $raise_by = DB::table('emp_basic_info')->where('emp_id',$request->emp_id)->first();

              $message = $raise_by->emp_fname. ' '. 'Assign A Ticket For You Pls See';

             $type="ticket_assign";
             $notification_data = array('emp_id'=>$request->selected_team,'notification'=>$message,'type'=>$type,'action_by'=>$raise_by->emp_fname);
             DB::table('emp_notifications')->insert($notification_data);
              event(new TicketEvent($message,$request->selected_team,$type));
             return response()->json(['status'=>200,'message'=>'Assign Successfully ',]);
    }
public function get_assign_ticket_list($id){
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
   public function view_ticket_status($ticket_id){
    $ticket_data =  DB::table('ticket_history')
                    ->join('emp_basic_info','ticket_history.created_by','=','emp_basic_info.emp_id')
                    ->select('emp_basic_info.emp_fname','ticket_history.ticket_id',
                    'ticket_history.created_date','ticket_history.manager_remark','ticket_history.ticket_raiser_remark','ticket_history.id','ticket_history.status','ticket_history.remark')
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
    $assign_data = DB::table('ticket_task_assign')->where('ticket_id',$id)->pluck('assign_from');
    $ticket_raise_by = DB::table('ticket_rise')->where('ticket_id',$id)->first();
    $assignedMemberNames = $employeeNames->implode(',');
    $assignids = $assigned_member->implode(',');
    $assign_from =  $assign_data->implode(',');
    $product = DB::table('product')->where('id',$data->id)->first();
    if($product){
      $product_name = $product->product_name;
    }
    else{
      $product_name = '';


    }
    $emp_name = DB::table('emp_basic_info')->where('emp_id',$data->created_by)->first();
    $new_time = DB::table('ticket_time_chnage_request')->where('ticket_id',$id)->where('status',1)->first();
    if($new_time){
      $time = $new_time->required_time_in_hour;

    }
    else{
      $time = '';

    }
    //return $assigned_member;
    $data = array('group_names'=>$names,'description'=>$data->description,'close_date'=>$data->close_date,'url'=>$data->url,'date' => Carbon::parse($data->created_date)->format('Y-m-d'));
    return response()->json(['status'=>200,'message'=>'Ticket Details','data'=>$data,'assign'=>$assignedMemberNames,'assign_array'=>$assignids,'assign_from'=>$assign_from,'raise_by'=>$ticket_raise_by->created_by,'product'=>$product_name,'ticket_raise_by'=>$emp_name->emp_fname,'new_time'=>$time]);

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
    if($request->emp_id == $ticket->created_by){
     DB::table('ticket_history')->where('id',$request->id)->where('ticket_id',$request->ticket_id)->update(['ticket_raiser_remark'=>$request->remark]);
   }
   else{
    DB::table('ticket_history')->where('id',$request->id)->where('ticket_id',$request->ticket_id)->update(['manager_remark'=>$request->remark]);

   }
    return response()->json(['status'=>200,'message'=>'Status Updated Successfully']);

   }
   public function ticket_list_raise_in_org(Request $request){
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
      return response()->json(['status'=>200,'message'=>'Request Send Successfully']);



   }
   public function get_new_time_request(Request $request){
    $data = DB::table('ticket_time_chnage_request')->join('emp_basic_info','ticket_time_chnage_request.created_by','emp_basic_info.emp_id')
      ->select('ticket_time_chnage_request.*','emp_basic_info.emp_fname')
      ->where('ticket_time_chnage_request.created_by',$request->emp_id)->get();
      return response()->json(['status'=>200,'data'=>$data]);
    }

    public function ticket_time_for_approval(){
      $data =  DB::table('ticket_time_chnage_request')->join('emp_basic_info','ticket_time_chnage_request.created_by','emp_basic_info.emp_id')
      ->select('ticket_time_chnage_request.*','emp_basic_info.emp_fname')->get();
      return response()->json(['status'=>200,'data'=>$data]);
    }
    public function ticket_time_approval_status_update(Request $request){
      $data =  DB::table('ticket_time_chnage_request')->where('ticket_id',$request->ticket_id)->update(['status'=>$request->status,'action_by'=>$request->emp_id]);
      return response()->json(['status'=>200,'message'=>'Request Updated Successfully']);

    }

}