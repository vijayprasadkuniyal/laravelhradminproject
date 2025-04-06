<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketRise;
use Carbon\Carbon;

class TicketRiseController extends Controller
{
    public function ticket_rise(Request $request){
            $input = $request->all();
            $validator = Validator::make($input, [
                'department_from' => 'required',
                'url'=>'required',
                'image'=>'required|image|mimes:jpg,png,jpeg',
                'enquiry'=>'required',
                'department_to'=>'required',
            ]);
        
              if($validator->fails()){
                    $messages=$validator->messages();
                    return response()->json(["messages"=>$messages,'status'=>400]);     
              }
            $ticket_rise = new TicketRise();
            $ticket_rise->ticket_id = Carbon::now()->format('H:i:s');
            $ticket_rise->req_url = $request->url;
            $ticket_rise->subject = $request->enquiry;
            $ticket_rise->remarks = $request->remarks;
            $ticket_rise->req_from_dept =  $request->department_from;
            $ticket_rise->req_to_department =  $request->department_to;
            $ticket_rise->created_by = $request->created_by;
            if($issue_image = $request->file('image')) {
                //dd('hi');
                    $destinationPath = 'ticket_rise_screenshort/';
                    $logoimage = date('YmdHis') . "." . $issue_image->getClientOriginalExtension();
                    $image->move($destinationPath, $logoimage);
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
}
