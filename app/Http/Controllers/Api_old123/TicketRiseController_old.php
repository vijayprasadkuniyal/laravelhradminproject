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
            }
            else if($request->department_to==3 && $request->group ==''){
                $group_ids = DB::table('group_names')->pluck('group_id')->toArray();
                $group = implode(',',$group_ids);
            }
            $data = array('ticket_id'=>$ticket_id,
            'request_from_dept'=>$request->dept_id,'request_to_dept'=>$request->department_to,
            'product_id'=>$request->product,'group_id'=>$group,
            'attribute_id'=>$request->attribute,
            'subattribute_id'=>$request->subattribute,'issue_page_url'=>$request->url,
            'description'=>$request->description,'created_by'=>$request->emp_id);
             DB::table('ticket_rise')->insert($data);
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
    public function ticket_receive_list(Request $request,$id){
        //return $request->all();
        $data = BasicInfo::where('reporting_manager',$id)->first();
        if($data){
            $check_groups = BasicInfo::where('emp_id',$data->reporting_manager)->first();
            //return $check_groups;
            $groups = $check_groups->assigned_group;
            if($groups){
                $group_array = explode(',', $groups);
                $ticket_data = DB::table('ticket_rise')->where('request_to_dept',$check_groups->dept_id)->whereRaw('FIND_IN_SET(?, group_id)',
                 [$group_array])->get();
                // return response()->json(['status'=>200,'data'=>$ticket_data]);
                
            }
            else{
                $ticket_data = DB::table('ticket_rise')->where('request_to_dept',$check_groups->dept_id)->get();
                return response()->json(['status'=>200,'data'=>$ticket_data]);

             }
        }
        


      }
    public function department_employee($id){
        $emp_list = BasicInfo::where('emp_status',1)->where('dept_id',$id)->get(['emp_id','emp_fname']);
        return response()->json(['status'=>200,'message'=>'Department Employee ','data'=>$emp_list]);

    }
    public function save_assign_ticket(Request $request){
        //return $request->all();
            $data = [];
             $input = $request->all();
             $validator = Validator::make($input, [
                'selected_team' => 'required',
                'remark'=>'required',
                'start_date'=>'required',
                'end_date'=>'required',
                'delivery_date'=>'required',
            ]);
            if($validator->fails()){
                $messages=$validator->messages();
                return response()->json(["messages"=>$messages,'status'=>400]);     
          }
            $team = explode(',',$request->selected_team);
            //return $team;

           foreach($team as $row){
             $ticket_priority = TicketRise::where('ticket_id',$request->ticket_id)->first();
             $ticket_priority = $ticket_priority->priority_attribute;
             //$attribute_name = TicketPriority::where('id', $ticket_priority->pariority_attribute);
            $data[] = array('ticket_id'=>$request->ticket_id,'assign_to'=>$row,'assign_from'=>$request->emp_id,'ticket_priority'=>$ticket_priority,'start_date'=>$request->start_date,'end_date'=>$request->end_date,'expected_date'=>$request->delivery_date,'remark'=>$request->remark);
             }
              DB::table('ticket_task_assign')->insert($data);
              return response()->json(['status'=>200,'message'=>'Assign Successfully ',]);
    }
public function get_assign_ticket_list($id){
    $assign_list = [];
    $data = DB::table('ticket_task_assign')->where('assign_to',$id)->get();
   // return $data;
    if(count($data)>0){
        foreach($data as $row){
            $priority_attribute = TicketPriority::where('id',$row->ticket_priority)->first();
            $priority_attribute =  $priority_attribute->attribute_name;
            $assign_from = BasicInfo::where('emp_id',$row->assign_from)->first();
            $assign_from = $assign_from->emp_fname;
            $closed_ticket = TicketRise::where('ticket_id',$row->ticket_id)->first();
            //return $closed_ticket;
            $assign_list[] = array('id'=>$row->id,'ticket_id'=>$row->ticket_id,'assign_from'=> $assign_from,
            'start_date'=>$row->start_date,'end_date'=>$row->end_date,'expected_date'=>$row->expected_date,
            'remark'=>$row->remark,'assign_date'=>$row->created_date,'ticket_priority'=>$priority_attribute,'close_status'=>$closed_ticket->close_status);
        }
        return response()->json(['status'=>200,'message'=>'Assign List ','data'=>$assign_list]);

        
    }
    else{
        return response()->json(['status'=>200,'message'=>'Assign List ','data'=>$assign_list]);
       }
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
   //return $request->all();
    $input = $request->all();
    $validator = Validator::make($input, [
        'status' => 'required',
    ]);

      if($validator->fails()){
            $messages=$validator->messages();
            return response()->json(["messages"=>$messages,'status'=>400]);     
      }
      $data = array('ticket_id'=>$request->ticket_id,'status'=>$request->status,
        'start_date'=>$request->start_date,'end_date'=>$request->end_date,
        'remark'=>$request->remark,'created_by'=>$request->emp_id);
       DB::table('ticket_history')->insert($data);
      return response()->json(['status'=>200,'message'=>'status updated successfully']);

   
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
                    'ticket_history.start_date','ticket_history.end_date','ticket_history.id','ticket_history.status','ticket_history.remark')
                    ->where('ticket_id',$ticket_id)
                    ->get();
    if(count($ticket_data)>0){
        return response()->json(['status'=>200,'message'=>'Ticket Status','data'=> $ticket_data]);
    }
           }
    public function close_ticket(Request $request){
        TicketRise::where('ticket_id',$request->ticket_id)->update(['close_status'=>1,'close_by'=>$request->emp_id,'close_date'=>Carbon::now()->format('Y-m-d')]);
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
    $data = array('group_names'=>$names,'description'=>$data->description,'url'=>$data->url);
    return response()->json(['status'=>200,'message'=>'Ticket Details','data'=>$data]);

   }
}