<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
Use \Carbon\Carbon;

class NotificationController extends Controller
{
    public function enquiry_list(Request $request){
    	 $emp_notifications = DB::table('emp_notifications')
        ->where('emp_id', $request->emp_id)
        ->whereRaw('MONTH(created_date) = ?', [Carbon::now()->month])
        ->orderBy('id', 'DESC')
        ->get(['id', 'notification', 'created_date']);

    // Enquiry List
     $enquiry_notifications = DB::table('superadmin_enquiry')
        ->where('selected_employee',$request->employee)
        ->orderBy('id', 'DESC')
        ->get(['id', 'message', 'created_date']);

    // Merge and Sort Notifications
   /* $enquiry_notification = DB::table('superadmin_enquiry')
      ->where('selected_employee',$request->employee)
      ->where('seen_status',0)
      ->orderBy('id','DESC')
      ->first();*/
    $notifications = collect($emp_notifications)
        ->merge($enquiry_notifications)
        ->sortByDesc('created_date')
        ->values();

    // Count
    $notification_count = $notifications->count();
   if(count($notifications)>0){
    return response()->json([
        'status' => 200,
        'data' => $notifications,
        'count' => $notification_count,
    ]);
}
//return response()->json(null, 204);
}
public function live_notification($id){
    $data = DB::table('superadmin_enquiry')->where('selected_employee',$id)->where('seen_status',0)->where('cross_status',0)
    ->orderBy('id','DESC')->first();
   // $count = DB::table('superadmin_enquiry')->where('selected_employee',$id)->where('seen_status',0)
   // ->orderBy('id','DESC')->count();
    if($data){
         return response()->json([
        'status' => 200,
        'data' => $data,
        //'no_of_count'=>$count,
        'message'=>'notification list',
    ]);
    return response()->json(null, 204);

    }
}
public function change_notification_status($id){
    DB::table('superadmin_enquiry')->where('id',$id)->update(['cross_status'=>1]);
    return response()->json(['status'=>200,'message'=>'status update successfully']);

}
public function seen_status($emp_id){
  DB::table('superadmin_enquiry')->where('selected_employee',$emp_id)->update(['seen_status'=>1]);
    return response()->json(['status'=>200,'message'=>'status update successfully']);


}
public function live_notification_count($id){
 $count = DB::table('superadmin_enquiry')->where('selected_employee',$id)->where('seen_status',0)
    ->orderBy('id','DESC')->count();
 if($count){
     return response()->json([
        'status' => 200,
        'data' => $count,
        'message'=>'no of count',
    ]);
  }


}
}
