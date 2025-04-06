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
            $input = $request->all();
            $validator = Validator::make($input, [
                'department_from' => 'required',
                'url'=>'required',
                //'image'=>'image|mimes:jpg,png,jpeg',
                'enquiry'=>'required',
                'department_to'=>'required',
                'selectedpriority'=>'required',
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
            $ticket_rise = new TicketRise();
            $ticket_rise->ticket_id =  date('YmdHis');
            $ticket_rise->req_url = $request->url;
            $ticket_rise->subject = $request->enquiry;
            $ticket_rise->remarks = $request->remarks;
            $ticket_rise->req_from_dept =  $request->department_from;
            $ticket_rise->req_to_department =  $request->department_to;
            $ticket_rise->priority_attribute = $request->selectedpriority;
            $ticket_rise->created_by = $request->emp_id;
            if($issue_image = $request->file('image')) {
                //dd('hi');
                    $destinationPath = 'ticket_rise_screenshort/';
                    $logoimage = date('YmdHis') . "." . $issue_image->getClientOriginalExtension();
                    $issue_image->move($destinationPath, $logoimage);
                    $screenshort = $logoimage;
                    $ticket_rise->issue_image = $screenshort;
            }
            $ticket_rise->save();
            if($ticket_rise){
                return response()->json(['status'=>200,'message'=>'Ticket Raised Successfully']);
            }
            else{
                return response()->json(['message'=>'Issue in ticket raised']);

            }
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
            //$ticket_list = TicketRise::where('created_by',$id)->get(['']);
                      $ticket_list = DB::table('ticket_rise')
                          ->join('department','department.id','=','ticket_rise.req_to_department')
                          ->join('ticket_priority','ticket_priority.id','ticket_rise.priority_attribute')
                          ->select('department.department_name','ticket_priority.attribute_name','ticket_rise.id','ticket_rise.ticket_id','ticket_rise.subject','ticket_rise.req_url','ticket_rise.close_status')
                          ->where('ticket_rise.created_by',$id)
                          ->paginate($request->per_page);
                    return response()->json(['status'=>200,'message'=>'ticket rise list','data'=>$ticket_list,'last_page'=>$ticket_list->lastPage()]);


        }
    public function ticket_receive_list(Request $request,$id){
        $reporting_manager = BasicInfo::where('reporting_manager',$id)->first();
        $data = [];
        if($reporting_manager){
           $dept_id = BasicInfo::where('emp_id', $reporting_manager->reporting_manager)->first();
           $department = $dept_id->dept_id;
            $ticket_data = TicketRise::where('req_to_department',$department)->paginate($request->per_page);
            foreach($ticket_data as $row){
                $receive_from_department = Department::where('id',$row->req_from_dept)->first();
                $receive_from_department = $receive_from_department->department_name;
                $request_from_emp = BasicInfo::where('emp_id',$row->created_by)->first();
                $request_from_emp = $request_from_emp->emp_fname;
                $priority_attribute = TicketPriority::where('id',$row->priority_attribute)->first();
                $close_status = $row->close_status;
                $assign_members = DB::table('ticket_task_assign')->where('ticket_id',$row->ticket_id)->pluck('assign_to');
                $assign_members= BasicInfo::whereIn('emp_id', $assign_members)->pluck('emp_fname')->implode(', ');
                //return  $assign_members;
                //return
                $priority_attribute_name = $priority_attribute->attribute_name;
                $data[] = array('id'=>$row->id,'ticket_id'=>$row->ticket_id,'url'=>$row->req_url,'message'=>$row->subject,'department_id'=>$row->req_to_department,
                'assign_date'=>$row->created_date,'emp_from'=>$request_from_emp,
                'dept_from'=>$receive_from_department,'priority_attribute'=>$priority_attribute_name,'close_status'=>$close_status,'assign_members'=>$assign_members);
            }
            return response()->json(['status'=>200,'message'=>'ticket recieve list ','data'=> $data,'last_page'=>$ticket_data->lastPage()]);



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
}